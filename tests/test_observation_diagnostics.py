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


def test_diagnostic_log_does_not_change_signed_audit_shape():
    source = (ROOT / "scripts/recover-wordpress.py").read_text(encoding="utf-8")
    branch = source.split("            if interrupted_observe_only:\n", 1)[1].split("                raise ObservationComplete()", 1)[0]
    assert '"OBSERVATION_DIAGNOSTICS "' in branch
    assert "bounded_observation_diagnostics(status)" in branch
    assert "file=sys.stderr" in branch
    assert 'audit["observation_diagnostics"]' not in source
