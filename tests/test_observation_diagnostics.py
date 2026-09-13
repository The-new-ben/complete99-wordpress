from __future__ import annotations

import copy
import importlib.util
import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location("c99_observation_diagnostics", ROOT / "scripts/recover-wordpress.py")
assert SPEC and SPEC.loader
RECOVER = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(RECOVER)


def status_fixture():
    return {
        "observation_checks_executed": {key: True for key in ("migration_invariant_checks", "campaign_operational", "campaign_capacity_diagnostic")},
        "migration_invariant_checks": {key: key != "campaigns" for key in RECOVER.INTERRUPTED_FORWARD_MIGRATION_INVARIANT_KEYS},
        "campaign_operational": {key: False for key in RECOVER.INTERRUPTED_FORWARD_CAMPAIGN_OPERATIONAL_KEYS},
        "campaign_capacity_diagnostic": {key: True for key in RECOVER.INTERRUPTED_FORWARD_CAMPAIGN_CAPACITY_DIAGNOSTIC_KEYS},
    }


def test_diagnostic_is_bounded_non_mutating_and_has_no_authority():
    status = status_fixture()
    status["private_payload"] = "DO_NOT_LOG_ME"
    status["campaign_operational"]["error"] = "DO_NOT_LOG_ME"
    before = copy.deepcopy(status)
    result = RECOVER.bounded_observation_diagnostics(status)
    assert status == before
    assert result["recovery_authority"] is False
    assert result["groups"]["migration_invariant_checks"]["checks"]["campaigns"] is False
    assert all(group["available"] for group in result["groups"].values())
    assert "DO_NOT_LOG_ME" not in json.dumps(result)


def test_missing_invalid_and_non_boolean_data_is_not_reported_as_valid():
    for value in (None, 0, 1, "false", [], {"secret": "DO_NOT_LOG_ME"}):
        status = status_fixture()
        status["campaign_operational"]["ready"] = value
        result = RECOVER.bounded_observation_diagnostics(status)
        assert result["groups"]["campaign_operational"] == {"available": False, "checks": {}}
        assert "DO_NOT_LOG_ME" not in json.dumps(result)
    for status in (None, {}, [], "DO_NOT_LOG_ME"):
        assert all(not group["available"] for group in RECOVER.bounded_observation_diagnostics(status)["groups"].values())


def test_bridge_projects_existing_read_only_diagnostics_in_pending_activation():
    bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
    status_route = bridge.split("$route_prefix . '/status'", 1)[1].split("$route_prefix . '/attest-interrupted-finalized'", 1)[0]
    assert "$campaign_diagnostic_phase = in_array( $phase, array( 'candidate_activation_pending', 'installed_pending_stabilization' ), true );" in status_route
    assert "$runtime_loaded && $campaign_diagnostic_phase && method_exists( 'Complete99_Ops', 'status_snapshot' )" in status_route
    assert "$runtime_loaded && $campaign_diagnostic_phase && $campaign_lifecycle['canonical']" in status_route
    assert "'observation_checks_executed' => $observation_checks_executed" in status_route


def test_default_false_maps_are_unavailable_until_checks_actually_execute():
    for executed in (None, {}, False, {"campaign_operational": 1}, {"campaign_operational": False}):
        status = status_fixture()
        status["observation_checks_executed"] = executed
        result = RECOVER.bounded_observation_diagnostics(status)
        assert all(not group["available"] for group in result["groups"].values())


def test_diagnostic_log_does_not_change_signed_audit_shape():
    source = (ROOT / "scripts/recover-wordpress.py").read_text(encoding="utf-8")
    branch = source.split("            if interrupted_observe_only:\n", 1)[1].split("                raise ObservationComplete()", 1)[0]
    assert '"OBSERVATION_DIAGNOSTICS "' in branch
    assert "bounded_observation_diagnostics(status)" in branch
    assert "file=sys.stderr" in branch
    assert 'audit["observation_diagnostics"]' not in source


def test_campaign_failure_is_an_allowlisted_code_not_an_exception():
    for code in ("media_rights_authority", "media_rights_registry", "schema", "capacity", "lifecycle", "evidence", "suppression", "capabilities", "unknown"):
        status = status_fixture()
        status["campaign_invariant_failure"] = code
        assert RECOVER.bounded_observation_diagnostics(status)["campaign_invariant"] == {
            "available": True, "failure_code": code,
        }
    for value in (None, [], {}, 1, "DO_NOT_LOG_ME", "passed", "unavailable"):
        status = status_fixture()
        status["campaign_invariant_failure"] = value
        result = RECOVER.bounded_observation_diagnostics(status)
        assert result["campaign_invariant"] == {"available": False, "failure_code": "unavailable"}
        assert "DO_NOT_LOG_ME" not in json.dumps(result)


def test_campaign_failure_requires_executed_consistent_migration_check():
    status = status_fixture()
    status["campaign_invariant_failure"] = "lifecycle"
    status["observation_checks_executed"]["migration_invariant_checks"] = False
    assert RECOVER.bounded_observation_diagnostics(status)["campaign_invariant"]["available"] is False
    status["observation_checks_executed"]["migration_invariant_checks"] = True
    status["migration_invariant_checks"]["campaigns"] = True
    assert RECOVER.bounded_observation_diagnostics(status)["campaign_invariant"]["available"] is False
    status["campaign_invariant_failure"] = "passed"
    assert RECOVER.bounded_observation_diagnostics(status)["campaign_invariant"] == {
        "available": True, "failure_code": "passed",
    }


def test_bridge_failure_codes_correspond_to_real_invariant_messages():
    import re
    bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
    campaigns = (ROOT / "plugin/complete99-platform/includes/class-complete99-campaigns.php").read_text(encoding="utf-8")
    mapping = bridge.split("$campaign_invariant_failure_codes = array(", 1)[1].split(");", 1)[0]
    pairs = re.findall(r"'([^']+)' => '([^']+)'", mapping)
    assert len(pairs) == 8
    for message, code in pairs:
        assert message in campaigns
        assert code in {"media_rights_authority", "schema", "capacity", "lifecycle", "evidence", "suppression", "capabilities"}
    assert "$campaign_invariant_failure_codes[ $error->getMessage() ] ?? 'unknown'" in bridge
    assert "'campaign_invariant_failure' => $campaign_invariant_failure" in bridge


def test_missing_and_invalid_media_registry_cannot_collapse_to_unknown():
    import shutil
    import subprocess
    bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
    start = bridge.index("if ( 'unknown' === $campaign_invariant_failure && is_callable")
    end = bridge.index("\n\t\t\t\t\t\t\t\t\t}", start) + len("\n\t\t\t\t\t\t\t\t\t}")
    block = bridge[start:end]
    php = shutil.which("php")
    assert php
    # Execute the actual bridge fallback, replacing only the read-only registry source.
    for message in ("consumer_media_rights.missing", "registry.schema", "registry.records.0.rights_boundary", "PRIVATE_DO_NOT_LOG"):
        program = ("class Complete99_Consumer_Media_Rights { public static function assert_invariants() { throw new RuntimeException($GLOBALS['failure']); } } "
                   + "$failure = json_decode(stream_get_contents(STDIN)); $campaign_invariant_failure = 'unknown'; "
                   + block + " echo $campaign_invariant_failure;")
        result = subprocess.run([php, "-r", program], input=json.dumps(message), text=True, capture_output=True, check=True)
        assert result.stdout == "media_rights_registry"
        assert message not in result.stdout
    program = ("class Complete99_Consumer_Media_Rights { public static function assert_invariants() { return true; } } "
               + "$campaign_invariant_failure = 'unknown'; " + block + " echo $campaign_invariant_failure;")
    assert subprocess.run([php, "-r", program], text=True, capture_output=True, check=True).stdout == "unknown"


def repair_status_fixture():
    status = status_fixture()
    status.update({
        "state_exists": True,
        "candidate_repair_started": True,
        "candidate_repair_no_rollback": True,
        "current_plugin_sha256": "b" * 64,
        "installed_plugin_sha256": "b" * 64,
        "interrupted_forward_proof_sha256": "a" * 64,
        "candidate_repair_receipt": {
            "charset": "utf8mb4", "collation": "utf8mb4_unicode_ci",
            "column": "external_state", "completed_at": 1786500000,
            "default": None, "nullable": False,
            "from_type": "varchar(24)", "to_type": "varchar(32)",
            "plugin_before_sha256": "c" * 64, "plugin_after_sha256": "b" * 64,
            "proof_sha256": "a" * 64,
            "source_before_sha256": "d" * 64, "source_after_sha256": "e" * 64,
            "schema": "complete99-campaign-provider-receipt-width-repair/v1",
            "source_path": "includes/class-complete99-campaigns.php",
            "table": "privateprefix_c99_campaign_provider_receipts",
        },
    })
    return status


def test_repair_receipt_diagnostic_is_evidence_not_recovery_permission():
    status = repair_status_fixture()
    before = copy.deepcopy(status)
    result = RECOVER.bounded_observation_diagnostics(status)
    assert result["candidate_repair"] == {
        "available": True, "started": True, "no_rollback": True,
        "receipt_present": True, "receipt_shape_valid": True,
        "receipt_matches_current_plugin": True, "receipt_matches_state_proof": True,
        "completed_at": 1786500000,
    }
    assert result["recovery_authority"] is False
    assert status == before
    assert "privateprefix" not in json.dumps(result)
    assert "source_path" not in json.dumps(result)
    for key in ("current_plugin_sha256", "installed_plugin_sha256", "interrupted_forward_proof_sha256"):
        changed = copy.deepcopy(status)
        changed[key] = "f" * 64
        projected = RECOVER.bounded_observation_diagnostics(changed)["candidate_repair"]
        binding = "receipt_matches_state_proof" if key == "interrupted_forward_proof_sha256" else "receipt_matches_current_plugin"
        assert projected[binding] is False


def test_repair_diagnostic_rejects_missing_malformed_and_private_receipts():
    for key in repair_status_fixture()["candidate_repair_receipt"]:
        for value in (None, True, [], {}, "DO_NOT_LOG_ME"):
            status = repair_status_fixture()
            status["candidate_repair_receipt"][key] = value
            if key == "default" and value is None:
                continue
            result = RECOVER.bounded_observation_diagnostics(status)
            assert result["candidate_repair"]["receipt_shape_valid"] is False, (key, value)
            assert result["candidate_repair"]["receipt_matches_current_plugin"] is None
            assert "DO_NOT_LOG_ME" not in json.dumps(result)
    for receipt in (None, [], "DO_NOT_LOG_ME", {}, {"private": "DO_NOT_LOG_ME"}):
        status = repair_status_fixture()
        status["candidate_repair_receipt"] = receipt
        result = RECOVER.bounded_observation_diagnostics(status)
        assert result["candidate_repair"]["receipt_shape_valid"] is False
        assert "DO_NOT_LOG_ME" not in json.dumps(result)
    for key in ("state_exists", "candidate_repair_started", "candidate_repair_no_rollback"):
        status = repair_status_fixture()
        status[key] = 1
        assert RECOVER.bounded_observation_diagnostics(status)["candidate_repair"]["available"] is False


def test_lifecycle_requires_a_real_snapshot_and_canonical_finite_state():
    for state in ("active", "inactive", "suspending"):
        status = status_fixture()
        status["database_fingerprint_available"] = True
        status["campaign_lifecycle"] = {"canonical": True, "generation": 7, "state": state}
        assert RECOVER.bounded_observation_diagnostics(status)["campaign_lifecycle"] == {
            "available": True, "generation": 7, "state": state,
        }
        for key, value in (("canonical", 1), ("canonical", False), ("generation", True),
                           ("generation", 0), ("generation", 2**64), ("state", []),
                           ("state", "DO_NOT_LOG_ME")):
            changed = copy.deepcopy(status)
            changed["campaign_lifecycle"][key] = value
            result = RECOVER.bounded_observation_diagnostics(changed)
            assert result["campaign_lifecycle"] == {"available": False, "generation": None, "state": "unavailable"}
            assert "DO_NOT_LOG_ME" not in json.dumps(result)
        status["database_fingerprint_available"] = False
        assert RECOVER.bounded_observation_diagnostics(status)["campaign_lifecycle"]["available"] is False
