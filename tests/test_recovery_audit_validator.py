from __future__ import annotations

import copy
import hashlib
import importlib.util
import json
import shutil
import sys
import tempfile
import unittest
from pathlib import Path
from typing import Callable
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]


def copy_historical_1_18_dist(destination: Path) -> Path:
    source = ROOT / "plugin-dist"
    destination.mkdir(parents=True, exist_ok=True)
    shutil.copy2(
        source / "complete99-platform-1.18.0.zip",
        destination / "complete99-platform-1.18.0.zip",
    )
    shutil.copy2(
        source / "complete99-platform-1.18.0-integrity.json",
        destination / "complete99-platform-integrity.json",
    )
    return destination


def load_module(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Could not load {path}")
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


VALIDATOR = load_module(
    "complete99_recovery_audit_validator",
    ROOT / "scripts" / "validate-recovery-audit.py",
)


FAILED_ID = "c99-prod-31171940371-1"
PRIOR_ID = "c99-prod-31158626196-1"
PROBE_ID = "c99-recovery-probe-40000000000-1"
PROOF_SHA = "a" * 64
PLUGIN_SHA = "b" * 64
DATABASE_SHA = "c" * 64
ROBOTS_SHA = "d" * 64
RECEIPT_SHA = "e" * 64
BODY_SHA = "f" * 64
OBSERVED_DATABASE_SHA = "7" * 64
RECONCILED_DATABASE_SHA = "8" * 64
V2_RECEIPT_SCHEMA = "complete99-orphaned-rollback-receipt/v2"
TEST_PRIOR_PROOF_SHA = "a4ce856b05d66fcfe6a7ac062bfce6c336f12798d9022427e44a75c827304733"


def cleanup_record() -> dict[str, object]:
    return {
        "snippet_deleted": True,
        "snippet_active": False,
        "row_absence_verified": True,
        "route_404": True,
    }


def observation_cleanup_record() -> dict[str, object]:
    return {**cleanup_record(), "removed_ids": []}


def bootstrap_cleanup_record() -> dict[str, object]:
    return {
        "exact_name": "c99-deploy-bootstrap",
        "known_id": 5,
        "known_id_matched": False,
        "removed_ids": [],
        "row_absence_verified": True,
    }


def finalize_record() -> dict[str, object]:
    return {
        "finalized": True,
        "lock_released": True,
        "state_removed": True,
    }


def write_reviewed_proof(repository_root: Path) -> tuple[Path, dict[str, object], str]:
    proof: dict[str, object] = {
        "failed_run": {
            "artifact_sha256": PROOF_SHA,
            "audit_sha256": "1" * 64,
            "candidate_database_fingerprint": "2" * 64,
            "candidate_plugin_sha256": "3" * 64,
            "candidate_version": "1.17.0",
            "commit": "4" * 40,
            "deployment_id": FAILED_ID,
            "run_id": 31171940371,
        },
        "prior_run": {
            "active": True,
            "audit_sha256": "5" * 64,
            "commit": "6" * 40,
            "database_fingerprint": DATABASE_SHA,
            "database_version": "1.16.0",
            "deployment_id": PRIOR_ID,
            "plugin_sha256": PLUGIN_SHA,
            "robots_exists": True,
            "robots_sha256": ROBOTS_SHA,
            "run_id": 31158626196,
            "sync_configured": True,
            "version": "1.16.0",
        },
    }
    canonical = json.dumps(
        proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    digest = hashlib.sha256(canonical).hexdigest()
    envelope = {
        "schema": "complete99-orphaned-rollback-proof/v1",
        "proof": proof,
        "proof_sha256": digest,
    }
    path = repository_root / "docs" / "recovery-proofs" / f"{FAILED_ID}.json"
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(envelope), encoding="utf-8")
    return path, envelope, digest


def orphaned_audit(proof_digest: str) -> dict[str, object]:
    return {
        "deployment_id": FAILED_ID,
        "decision": "finish_orphaned_rollback_cleanup",
        "discovery": {
            "probe_id": PROBE_ID,
            "owner_deployment_id": FAILED_ID,
            "owner_phase": "rolling_back",
            "result": "owner-discovered",
            "cleanup": cleanup_record(),
        },
        "initial_status": {
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
        },
        "orphaned_rollback_proof": {
            "path": f"docs/recovery-proofs/{FAILED_ID}.json",
            "proof_sha256": proof_digest,
        },
        "orphaned_rollback_reconciliation": {
            "evidence_directory_exists": False,
            "evidence_directory_sha256": "",
            "lock_retained": True,
            "marker_corrected": True,
            "phase": "committed",
            "proof_sha256": proof_digest,
            "receipt_sha256": RECEIPT_SHA,
        },
        "reconciled_status": {
            "phase": "committed",
            "state_exists": False,
            "lock_owned": True,
        },
        "health": {
            "component": "complete99-platform",
            "database_version": "1.16.0",
            "deployment_id": PRIOR_ID,
            "status": "ok",
            "sync_configured": True,
            "version": "1.16.0",
        },
        "rendered_home": {
            "body_sha256": BODY_SHA,
            "deployment_id": PRIOR_ID,
            "exact_path": "/",
            "version": "1.16.0",
        },
        "orphaned_rollback_robots": {
            "sha256": ROBOTS_SHA,
            "status": 200,
        },
        "finalize": finalize_record(),
        "cleanup": cleanup_record(),
        "result": "recovered",
    }


def orphaned_receipt_resume_audit(proof_digest: str) -> dict[str, object]:
    audit = orphaned_audit(proof_digest)
    del audit["orphaned_rollback_reconciliation"]
    del audit["reconciled_status"]
    audit["initial_status"] = {
        "phase": "committed",
        "state_exists": False,
        "lock_owned": True,
        "recovery_ready": True,
    }
    audit["initial_orphaned_rollback_receipt"] = {
        "phase": "committed",
        "state_exists": False,
        "lock_owned": True,
        "committed_outcome": "rolled_back",
        "committed_expected_active": True,
        "committed_expected_absent": False,
        "committed_expected_version": "1.16.0",
        "committed_expected_deployment": PRIOR_ID,
        "committed_expected_plugin_sha256": PLUGIN_SHA,
        "committed_expected_database_fingerprint": DATABASE_SHA,
        "committed_expected_robots_exists": True,
        "committed_expected_robots_sha256": ROBOTS_SHA,
        "committed_expected_sync_configured": True,
        "orphaned_recovery_proof_sha256": proof_digest,
        "orphaned_recovery_receipt_sha256": RECEIPT_SHA,
        "orphaned_reconciled_from": "rolling_back",
        "orphaned_observed_deployment": FAILED_ID,
        "orphaned_recovery_evidence_exists": False,
        "orphaned_recovery_evidence_sha256": "",
    }
    return audit


def database_manifest() -> tuple[dict[str, object], str]:
    manifest: dict[str, object] = {
        "schema": "complete99-database-snapshot-manifest/v1",
        "sync_secret_existed": True,
        "sync_secret_configured": True,
    }
    for index, component in enumerate(
        (
            "options_without_deployment_marker",
            "posts",
            "postmeta",
            "seed_ids",
            "evaluation_ids",
        ),
        start=1,
    ):
        manifest[f"{component}_count"] = index
        manifest[f"{component}_sha256"] = format(index, "x") * 64
    canonical = json.dumps(
        manifest,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    return manifest, hashlib.sha256(canonical).hexdigest()


def orphaned_observation_audit(proof_digest: str) -> dict[str, object]:
    manifest, manifest_sha256 = database_manifest()
    current_database = "7" * 64
    projected_database = "8" * 64
    return {
        "bootstrap_cleanup": bootstrap_cleanup_record(),
        "bridge_site_identity": {
            "home_host": "complete99.co.il",
            "rest_host": "complete99.co.il",
            "siteurl_host": "complete99.co.il",
        },
        "deployment_id": FAILED_ID,
        "decision": "observe_orphaned_rollback",
        "discovery": {
            "bootstrap_cleanup": bootstrap_cleanup_record(),
            "probe_id": PROBE_ID,
            "owner_deployment_id": FAILED_ID,
            "owner_phase": "rolling_back",
            "result": "owner-discovered",
            "cleanup": observation_cleanup_record(),
        },
        "finished_at": "2026-08-07T12:00:01Z",
        "identity": {
            "id": 1,
            "roles": ["administrator"],
            "site_identity": {
                "home": "https://complete99.co.il",
                "url": "https://complete99.co.il",
            },
        },
        "initial_status": {
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
            "process_lock_available": True,
            "database_fingerprint": current_database,
            "projected_deployment_id": PRIOR_ID,
            "projected_database_fingerprint": projected_database,
            "database_manifest_sha256": manifest_sha256,
            "database_storage": {"engine": "INNODB", "tables": 3},
        },
        "local_test": False,
        "orphaned_rollback_proof": {
            "path": f"docs/recovery-proofs/{FAILED_ID}.json",
            "proof_sha256": proof_digest,
        },
        "orphaned_rollback_observation": {
            "schema": "complete99-orphaned-rollback-observation/v1",
            "deployment_id": FAILED_ID,
            "proof_sha256": proof_digest,
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
            "process_lock_available": True,
            "current_version": "1.16.0",
            "current_database_version": "1.16.0",
            "current_active": True,
            "current_plugin_sha256": PLUGIN_SHA,
            "current_deployment": FAILED_ID,
            "current_database_fingerprint": current_database,
            "projected_deployment_id": PRIOR_ID,
            "projected_database_fingerprint": projected_database,
            "historical_baseline_database_fingerprint": DATABASE_SHA,
            "historical_baseline_matches_projection": False,
            "current_sync_configured": True,
            "current_robots_sha256": ROBOTS_SHA,
            "database_manifest": manifest,
            "database_manifest_sha256": manifest_sha256,
            "database_storage": {"engine": "INNODB", "tables": 3},
            "failed_candidate_database_fingerprint": "2" * 64,
        },
        "cleanup": observation_cleanup_record(),
        "result": "orphaned-rollback-observed",
        "started_at": "2026-08-07T12:00:00Z",
    }


def write_reviewed_v2_proof(
    repository_root: Path,
) -> tuple[Path, dict[str, object], str]:
    historical_path, historical_envelope, historical_digest = write_reviewed_proof(
        repository_root
    )
    attestation = orphaned_observation_audit(historical_digest)
    attestation_path = (
        repository_root
        / "docs"
        / "recovery-proofs"
        / "observations"
        / f"{FAILED_ID}-run-40000000000.json"
    )
    attestation_path.parent.mkdir(parents=True, exist_ok=True)
    attestation_path.write_text(
        json.dumps(attestation, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    attestation_digest = hashlib.sha256(attestation_path.read_bytes()).hexdigest()
    manifest, manifest_sha256 = database_manifest()
    reconciliation = {
        "attestation_audit_sha256": attestation_digest,
        "attestation_path": attestation_path.relative_to(repository_root).as_posix(),
        "attestation_run_id": 40000000000,
        "attestation_sha256": attestation_digest,
        "attestation_source_commit": "9" * 40,
        "baseline_database_fingerprint": DATABASE_SHA,
        "expected_reconciled_database_fingerprint": RECONCILED_DATABASE_SHA,
        "mode": "preserve-reviewed-drift-marker-only",
        "observed_database_fingerprint": OBSERVED_DATABASE_SHA,
        "observed_deployment": FAILED_ID,
        "preserved_manifest": manifest,
        "preserved_manifest_sha256": manifest_sha256,
        "prior_proof_sha256": historical_digest,
        "schema": "complete99-orphaned-database-reconciliation/v1",
        "target_deployment": PRIOR_ID,
        "transactional_storage": {"engine": "INNODB", "tables": 3},
    }
    proof = {
        **historical_envelope["proof"],
        "database_reconciliation": reconciliation,
    }
    canonical = json.dumps(
        proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    proof_digest = hashlib.sha256(canonical).hexdigest()
    envelope = {
        "schema": "complete99-orphaned-rollback-proof/v2",
        "proof": proof,
        "proof_sha256": proof_digest,
    }
    proof_path = historical_path.with_name(f"{FAILED_ID}-v2.json")
    proof_path.write_text(
        json.dumps(envelope, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    return proof_path, envelope, proof_digest


def v2_recovery_audit(
    proof_digest: str,
    *,
    marker_rows_affected: int = 1,
    receipt_resume: bool = False,
) -> dict[str, object]:
    manifest, manifest_sha256 = database_manifest()
    attestation_bytes = (
        json.dumps(
            orphaned_observation_audit(TEST_PRIOR_PROOF_SHA),
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n"
    ).encode("utf-8")
    attestation_sha256 = hashlib.sha256(attestation_bytes).hexdigest()
    marker_transition = (
        "corrected" if marker_rows_affected == 1 else "already-correct"
    )
    audit = orphaned_audit(proof_digest)
    audit.update(
        {
            "bootstrap_cleanup": bootstrap_cleanup_record(),
            "bridge_site_identity": {
                "home_host": "complete99.co.il",
                "rest_host": "complete99.co.il",
                "siteurl_host": "complete99.co.il",
            },
            "finished_at": "2026-08-07T14:00:01Z",
            "identity": {
                "id": 1,
                "roles": ["administrator"],
                "site_identity": {
                    "home": "https://complete99.co.il",
                    "url": "https://complete99.co.il",
                },
            },
            "local_test": False,
            "started_at": "2026-08-07T14:00:00Z",
        }
    )
    audit["cleanup"] = observation_cleanup_record()
    audit["discovery"] = {
        "bootstrap_cleanup": bootstrap_cleanup_record(),
        "cleanup": observation_cleanup_record(),
        "owner_deployment_id": FAILED_ID,
        "owner_phase": "committed" if receipt_resume else "rolling_back",
        "probe_id": PROBE_ID,
        "result": "owner-discovered",
    }
    audit["orphaned_rollback_proof"] = {
        "path": f"docs/recovery-proofs/{FAILED_ID}-v2.json",
        "proof_sha256": proof_digest,
    }
    audit["initial_status"] = {
        "database_fingerprint": (
            RECONCILED_DATABASE_SHA
            if receipt_resume or marker_rows_affected == 0
            else OBSERVED_DATABASE_SHA
        ),
        "database_manifest_sha256": manifest_sha256,
        "database_storage": {"engine": "INNODB", "tables": 3},
        "lock_owned": True,
        "phase": "committed" if receipt_resume else "rolling_back",
        "process_lock_available": True,
        "projected_database_fingerprint": RECONCILED_DATABASE_SHA,
        "projected_deployment_id": PRIOR_ID,
        "recovery_ready": not receipt_resume,
        "state_exists": False,
    }
    audit["pre_finalize_orphaned_identity"] = {
        "phase": "committed",
        "state_exists": False,
        "lock_owned": True,
        "recovery_ready": False,
        "process_lock_available": True,
        "current_active": True,
        "current_target_dir_exists": True,
        "current_plugin_main_exists": True,
        "current_version": "1.16.0",
        "current_database_version": "1.16.0",
        "current_deployment": PRIOR_ID,
        "current_plugin_sha256": PLUGIN_SHA,
        "current_sync_configured": True,
        "current_robots_sha256": ROBOTS_SHA,
        "database_fingerprint": RECONCILED_DATABASE_SHA,
        "database_manifest_sha256": manifest_sha256,
    }
    audit["orphaned_rollback_reconciliation"] = {
        "attestation_audit_sha256": attestation_sha256,
        "attestation_run_id": 40000000000,
        "attestation_sha256": attestation_sha256,
        "attestation_source_commit": "9" * 40,
        "evidence_directory_exists": False,
        "evidence_directory_sha256": "",
        "historical_baseline_database_fingerprint": DATABASE_SHA,
        "lock_retained": True,
        "marker_corrected": marker_rows_affected == 1,
        "marker_rows_affected": marker_rows_affected,
        "marker_transition": marker_transition,
        "mode": "preserve-reviewed-drift-marker-only",
        "observed_database_fingerprint": OBSERVED_DATABASE_SHA,
        "phase": "committed",
        "preserved_manifest_sha256": manifest_sha256,
        "proof_sha256": proof_digest,
        "prior_proof_sha256": TEST_PRIOR_PROOF_SHA,
        "receipt_schema": V2_RECEIPT_SCHEMA,
        "receipt_sha256": RECEIPT_SHA,
        "reconciled_database_fingerprint": RECONCILED_DATABASE_SHA,
        "response_recovered": False,
    }
    audit["reconciled_status"] = {
        "current_deployment": PRIOR_ID,
        "database_fingerprint": RECONCILED_DATABASE_SHA,
        "database_manifest_sha256": manifest_sha256,
        "expected_sha256": PROOF_SHA,
        "expected_version": "1.17.0",
        "installed_plugin_sha256": "3" * 64,
        "lock_owned": True,
        "orphaned_historical_baseline_database_fingerprint": DATABASE_SHA,
        "orphaned_marker_rows_affected": marker_rows_affected,
        "orphaned_marker_transition": marker_transition,
        "orphaned_observed_database_fingerprint": OBSERVED_DATABASE_SHA,
        "orphaned_preserved_manifest_sha256": manifest_sha256,
        "orphaned_reconciliation_mode": "preserve-reviewed-drift-marker-only",
        "orphaned_prior_proof_sha256": TEST_PRIOR_PROOF_SHA,
        "orphaned_attestation_run_id": 40000000000,
        "orphaned_attestation_sha256": attestation_sha256,
        "orphaned_attestation_audit_sha256": attestation_sha256,
        "orphaned_attestation_source_commit": "9" * 40,
        "orphaned_recovery_proof_sha256": proof_digest,
        "orphaned_recovery_receipt_schema": V2_RECEIPT_SCHEMA,
        "orphaned_recovery_receipt_sha256": RECEIPT_SHA,
        "phase": "committed",
        "post_install_database_fingerprint": "2" * 64,
        "state_exists": False,
    }
    if receipt_resume:
        del audit["orphaned_rollback_reconciliation"]
        del audit["reconciled_status"]
        audit["initial_orphaned_rollback_receipt"] = {
            "phase": "committed",
            "state_exists": False,
            "lock_owned": True,
            "expected_sha256": PROOF_SHA,
            "expected_version": "1.17.0",
            "installed_plugin_sha256": "3" * 64,
            "post_install_database_fingerprint": "2" * 64,
            "committed_outcome": "rolled_back",
            "committed_expected_active": True,
            "committed_expected_absent": False,
            "committed_expected_version": "1.16.0",
            "committed_expected_deployment": PRIOR_ID,
            "committed_expected_plugin_sha256": PLUGIN_SHA,
            "committed_expected_database_fingerprint": RECONCILED_DATABASE_SHA,
            "committed_expected_robots_exists": True,
            "committed_expected_robots_sha256": ROBOTS_SHA,
            "committed_expected_sync_configured": True,
            "orphaned_recovery_proof_sha256": proof_digest,
            "orphaned_recovery_receipt_sha256": RECEIPT_SHA,
            "orphaned_reconciled_from": "rolling_back",
            "orphaned_observed_deployment": FAILED_ID,
            "orphaned_recovery_evidence_exists": False,
            "orphaned_recovery_evidence_sha256": "",
            "orphaned_recovery_receipt_schema": V2_RECEIPT_SCHEMA,
            "orphaned_historical_baseline_database_fingerprint": DATABASE_SHA,
            "orphaned_observed_database_fingerprint": OBSERVED_DATABASE_SHA,
            "orphaned_preserved_manifest_sha256": manifest_sha256,
            "orphaned_marker_rows_affected": marker_rows_affected,
            "orphaned_marker_transition": marker_transition,
            "orphaned_reconciliation_mode": "preserve-reviewed-drift-marker-only",
            "orphaned_prior_proof_sha256": TEST_PRIOR_PROOF_SHA,
            "orphaned_attestation_run_id": 40000000000,
            "orphaned_attestation_sha256": attestation_sha256,
            "orphaned_attestation_audit_sha256": attestation_sha256,
            "orphaned_attestation_source_commit": "9" * 40,
        }
    return audit


def write_audit(audit_root: Path, audit: dict[str, object]) -> Path:
    audit_root.mkdir(parents=True, exist_ok=True)
    path = audit_root / f"{audit['deployment_id']}.json"
    path.write_text(json.dumps(audit), encoding="utf-8")
    return path.resolve()


def interrupted_identities() -> tuple[
    dict[str, object],
    dict[str, object],
    dict[str, object],
    dict[str, str],
]:
    manifest, manifest_sha256 = database_manifest()
    failed = {
        "artifact_sha256": "1" * 64,
        "baseline_database_fingerprint": "2" * 64,
        "commit": "3" * 40,
        "deployment_id": "c99-prod-40000000002-1",
        "installed_plugin_sha256": "4" * 64,
        "run_id": 40000000002,
        "source_sha256": "5" * 64,
        "version": "1.18.0",
    }
    prior = {
        "active": True,
        "commit": "6" * 40,
        "database_fingerprint": "2" * 64,
        "database_version": "1.17.0",
        "deployment_id": "c99-prod-40000000001-1",
        "plugin_sha256": "7" * 64,
        "robots_sha256": "8" * 64,
        "run_id": 40000000001,
        "sync_configured": True,
        "version": "1.17.0",
    }
    database = {
        "manifest": manifest,
        "manifest_sha256": manifest_sha256,
        "storage": {"engine": "INNODB", "tables": 3},
    }
    recovery_identity = {
        "database_fingerprint": "9" * 64,
        "database_manifest_sha256": manifest_sha256,
    }
    return failed, prior, database, recovery_identity


def interrupted_discovery(
    failed_id: str,
    probe_id: str,
    owner_phase: str = "installing",
) -> dict[str, object]:
    return {
        "bootstrap_cleanup": bootstrap_cleanup_record(),
        "cleanup": {**cleanup_record(), "removed_ids": [31]},
        "owner_deployment_id": failed_id,
        "owner_phase": owner_phase,
        "probe_id": probe_id,
        "result": "owner-discovered",
    }


def interrupted_common(deployment_id: str) -> dict[str, object]:
    return {
        "bootstrap_cleanup": bootstrap_cleanup_record(),
        "bridge_site_identity": {
            "home_host": "complete99.co.il",
            "rest_host": "complete99.co.il",
            "siteurl_host": "complete99.co.il",
        },
        "cleanup": {**cleanup_record(), "removed_ids": [32]},
        "deployment_id": deployment_id,
        "finished_at": "2026-08-08T00:00:01Z",
        "identity": {
            "id": 1,
            "roles": ["administrator"],
            "site_identity": {
                "home": "https://complete99.co.il",
                "url": "https://complete99.co.il",
            },
        },
        "local_test": False,
        "started_at": "2026-08-08T00:00:00Z",
    }


def interrupted_health_home_robots(
    failed: dict[str, object],
    prior: dict[str, object],
    prefix: str = "",
) -> dict[str, object]:
    return {
        f"{prefix}health": {
            "component": "complete99-platform",
            "database_version": failed["version"],
            "deployment_id": failed["deployment_id"],
            "status": "ok",
            "sync_configured": True,
            "version": failed["version"],
        },
        f"{prefix}rendered_home": {
            "body_sha256": "a" * 64,
            "deployment_id": failed["deployment_id"],
            "exact_path": "/",
            "version": failed["version"],
        },
        f"{prefix}robots": {"sha256": prior["robots_sha256"], "status": 200},
    }


def interrupted_observation_audit() -> tuple[
    dict[str, object], dict[str, object], dict[str, object], dict[str, str]
]:
    failed, prior, database, recovery_identity = interrupted_identities()
    proof_path = f"docs/recovery-proofs/{failed['deployment_id']}.json"
    probe_id = "c99-recovery-probe-40000000003-1"
    observation = VALIDATOR.expected_interrupted_observation(
        failed,
        prior,
        recovery_identity["database_fingerprint"],
        database["manifest"],
        database["manifest_sha256"],
        database["storage"],
        failed["installed_plugin_sha256"],
    )
    audit = {
        **interrupted_common(str(failed["deployment_id"])),
        **interrupted_health_home_robots(failed, prior),
        "commit": "b" * 40,
        "decision": "observe_interrupted_forward",
        "discovery": interrupted_discovery(str(failed["deployment_id"]), probe_id),
        "interrupted_forward_observation": observation,
        "interrupted_forward_proof": {
            "path": proof_path,
            "proof_sha256": "c" * 64,
            "schema": "complete99-interrupted-forward-proof/v1",
        },
        "result": "interrupted_forward_observed",
    }
    context = {
        "probe_id": probe_id,
        "proof_path": proof_path,
        "proof_sha256": "c" * 64,
    }
    return audit, failed, prior, {**recovery_identity, **context}


def interrupted_database_mismatch_audit() -> tuple[
    dict[str, object], dict[str, object], dict[str, object], dict[str, object]
]:
    failed, prior, database, recovery_identity = interrupted_identities()
    current_manifest = copy.deepcopy(database["manifest"])
    current_manifest["posts_sha256"] = "d" * 64
    current_manifest_sha256 = VALIDATOR.canonical_json_sha256(current_manifest)
    current_fingerprint = "e" * 64
    safe_status = {
        "adopted_forward_no_rollback": False,
        "baseline_database_fingerprint": failed["baseline_database_fingerprint"],
        "baseline_database_journal_valid": True,
        "baseline_sync_configured": True,
        "baseline_sync_secret_existed": True,
        "current_active": True,
        "current_database_version": failed["version"],
        "current_deployment": failed["deployment_id"],
        "current_plugin_main_exists": True,
        "current_plugin_sha256": failed["installed_plugin_sha256"],
        "current_robots_sha256": prior["robots_sha256"],
        "current_sync_configured": True,
        "current_target_dir_exists": True,
        "current_version": failed["version"],
        "database_fingerprint": current_fingerprint,
        "database_fingerprint_available": True,
        "database_manifest": current_manifest,
        "database_manifest_sha256": current_manifest_sha256,
        "database_restored": False,
        "database_storage": database["storage"],
        "deployment_id": failed["deployment_id"],
        "expected_sha256": failed["artifact_sha256"],
        "expected_version": failed["version"],
        "had_plugin": True,
        "installed_plugin_sha256": failed["installed_plugin_sha256"],
        "interrupted_forward_candidate": False,
        "interrupted_forward_database_manifest_sha256": "",
        "interrupted_forward_proof_sha256": "",
        "lock_owned": True,
        "migration_failed": False,
        "migration_invariants_valid": True,
        "no_rollback_artifacts": True,
        "phase": "installing",
        "post_install_database_fingerprint": recovery_identity[
            "database_fingerprint"
        ],
        "prior_active": True,
        "prior_deployment": prior["deployment_id"],
        "prior_plugin_main_exists": True,
        "prior_plugin_sha256": prior["plugin_sha256"],
        "prior_target_dir_exists": True,
        "prior_version": prior["version"],
        "process_lock_available": True,
        "recovery_ready": True,
        "robots_applied": True,
        "robots_managed_sha256": prior["robots_sha256"],
        "robots_prior_exists": True,
        "robots_prior_sha256": prior["robots_sha256"],
        "robots_restored": False,
        "runtime_loaded": True,
        "runtime_version": failed["version"],
        "state_exists": True,
    }
    receipt = {
        "database_fingerprint": current_fingerprint,
        "database_identity_changed": True,
        "database_manifest": current_manifest,
        "database_manifest_sha256": current_manifest_sha256,
        "database_storage": database["storage"],
        "historical_database_fingerprint": recovery_identity[
            "database_fingerprint"
        ],
        "historical_database_manifest_sha256": recovery_identity[
            "database_manifest_sha256"
        ],
        "mismatches": list(VALIDATOR.INTERRUPTED_FORWARD_DATABASE_MISMATCHES),
        "proof_consumed": False,
        "safe_status": safe_status,
        "safe_status_sha256": VALIDATOR.canonical_json_sha256(safe_status),
        "schema": "complete99-interrupted-forward-observation/v2",
    }
    proof_path = f"docs/recovery-proofs/{failed['deployment_id']}.json"
    proof_sha256 = "c" * 64
    probe_id = "c99-recovery-probe-40000000003-1"
    audit = {
        **interrupted_common(str(failed["deployment_id"])),
        **interrupted_health_home_robots(failed, prior),
        "commit": "b" * 40,
        "decision": "observe_interrupted_forward_database_mismatch",
        "discovery": interrupted_discovery(str(failed["deployment_id"]), probe_id),
        "interrupted_forward_observation": receipt,
        "interrupted_forward_proof": {
            "path": proof_path,
            "proof_sha256": proof_sha256,
            "schema": "complete99-interrupted-forward-proof/v1",
        },
        "proof_consumed": False,
        "result": "interrupted_forward_database_mismatch_observed",
    }
    context = {
        "current_database_fingerprint": current_fingerprint,
        "current_database_manifest": current_manifest,
        "current_database_manifest_sha256": current_manifest_sha256,
        "current_database_storage": database["storage"],
        "probe_id": probe_id,
        "proof_path": proof_path,
        "proof_sha256": proof_sha256,
        "recovery_identity": recovery_identity,
    }
    return audit, failed, prior, context


def interrupted_mismatch_diagnostic_audit() -> tuple[
    dict[str, object], dict[str, object], dict[str, object], dict[str, object]
]:
    audit, failed, prior, context = interrupted_database_mismatch_audit()
    _, _, historical_database, recovery_identity = interrupted_identities()
    safe = audit["interrupted_forward_observation"]["safe_status"]
    safe["database_manifest"] = historical_database["manifest"]
    safe["database_manifest_sha256"] = historical_database["manifest_sha256"]
    mismatches = VALIDATOR.interrupted_status_mismatches(
        safe,
        failed,
        prior,
        recovery_identity,
    )
    audit["decision"] = "observe_interrupted_forward_mismatch_diagnostic"
    audit["interrupted_forward_observation"] = {
        "diagnostic_only": True,
        "mismatches": mismatches,
        "proof_consumed": False,
        "recovery_authority": False,
        "safe_status": safe,
        "safe_status_sha256": VALIDATOR.canonical_json_sha256(safe),
        "schema": "complete99-interrupted-forward-observation/v3",
    }
    audit["result"] = "interrupted_forward_mismatch_diagnostic_observed"
    context["recovery_identity"] = recovery_identity
    return audit, failed, prior, context


def write_interrupted_database_mismatch_proofs(
    repository_root: Path,
) -> tuple[Path, Path, dict[str, object], dict[str, str]]:
    audit, failed_base, prior_base, context = interrupted_database_mismatch_audit()
    observations = repository_root / "docs" / "recovery-proofs" / "observations"
    observations.mkdir(parents=True)

    source_paths: list[str] = []
    source_digests: list[str] = []
    for name in ("c99-failed-deploy", "c99-failed-recovery", "c99-prior-deploy"):
        path = observations / f"{name}.json"
        path.write_text("{}", encoding="utf-8")
        source_paths.append(path.relative_to(repository_root).as_posix())
        source_digests.append(hashlib.sha256(path.read_bytes()).hexdigest())

    failed = {
        **failed_base,
        "deploy_audit_path": source_paths[0],
        "deploy_audit_sha256": source_digests[0],
        "recovery_audit_path": source_paths[1],
        "recovery_audit_sha256": source_digests[1],
    }
    prior = {
        **prior_base,
        "deploy_audit_path": source_paths[2],
        "deploy_audit_sha256": source_digests[2],
    }
    base_proof = {"failed_run": failed, "prior_run": prior}
    base_proof_sha256 = VALIDATOR.canonical_json_sha256(base_proof)
    proof_root = repository_root / "docs" / "recovery-proofs"
    historical_path = proof_root / f"{failed['deployment_id']}.json"
    historical_path.write_text(
        json.dumps(
            {
                "proof": base_proof,
                "proof_sha256": base_proof_sha256,
                "schema": "complete99-interrupted-forward-proof/v1",
            }
        ),
        encoding="utf-8",
    )

    audit["interrupted_forward_proof"]["proof_sha256"] = base_proof_sha256
    observation_path = observations / "c99-database-mismatch-observation.json"
    observation_path.write_text(json.dumps(audit), encoding="utf-8")
    observation_sha256 = hashlib.sha256(observation_path.read_bytes()).hexdigest()
    adoption = {
        "observation_audit_path": observation_path.relative_to(
            repository_root
        ).as_posix(),
        "observation_audit_sha256": observation_sha256,
        "observation_commit": audit["commit"],
        "observation_proof_sha256": base_proof_sha256,
        "observation_run_id": 40000000003,
        "observed_database_fingerprint": context[
            "current_database_fingerprint"
        ],
        "observed_database_manifest": context["current_database_manifest"],
        "observed_database_manifest_sha256": context[
            "current_database_manifest_sha256"
        ],
        "observed_database_storage": context["current_database_storage"],
        "observed_deployment_id": failed["deployment_id"],
        "observed_plugin_sha256": failed["installed_plugin_sha256"],
        "observed_robots_sha256": prior["robots_sha256"],
        "observed_version": failed["version"],
        "schema": "complete99-interrupted-forward-adoption/v2",
        "target_artifact_sha256": failed["artifact_sha256"],
        "target_installed_plugin_sha256": failed["installed_plugin_sha256"],
    }
    proof = {**base_proof, "forward_adoption": adoption}
    v2_path = proof_root / f"{failed['deployment_id']}-v2.json"
    v2_path.write_text(
        json.dumps(
            {
                "proof": proof,
                "proof_sha256": VALIDATOR.canonical_json_sha256(proof),
                "schema": "complete99-interrupted-forward-proof/v2",
            }
        ),
        encoding="utf-8",
    )
    return v2_path, observation_path, audit, context["recovery_identity"]


def interrupted_already_finalized_audit(
    *,
    include_stale_probe: bool = False,
) -> tuple[dict[str, object], dict[str, object], str]:
    failed, prior, database, recovery_identity = interrupted_identities()
    probe_id = "c99-recovery-probe-40000000004-1"
    proof_sha256 = "d" * 64
    adoption = {
        "observed_database_fingerprint": recovery_identity[
            "database_fingerprint"
        ],
        "observed_database_manifest": database["manifest"],
        "observed_database_manifest_sha256": database["manifest_sha256"],
        "observed_database_storage": database["storage"],
    }
    loaded = {
        "path": f"docs/recovery-proofs/{failed['deployment_id']}-v2.json",
        "proof": {
            "failed_run": failed,
            "forward_adoption": adoption,
            "prior_run": prior,
        },
        "proof_sha256": proof_sha256,
        "schema": "complete99-interrupted-forward-proof/v2",
    }
    probe_finalize = {
        "cache_purge": {"not_required": True},
        "finalized": True,
        "lock_released": True,
        "response_recovered": False,
        "state_removed": True,
    }
    audit = {
        **interrupted_common(probe_id),
        **interrupted_health_home_robots(failed, prior),
        "decision": "attest_interrupted_forward_finalized",
        "discovery": {
            "probe_id": probe_id,
            "probe_lock_retained_for_attestation": True,
            "result": "no-owner",
        },
        "interrupted_forward_finalized_attestation": (
            VALIDATOR.expected_interrupted_finalized_attestation(
                loaded,
                probe_id,
            )
        ),
        "interrupted_forward_proof": {
            "path": loaded["path"],
            "proof_sha256": proof_sha256,
            "schema": "complete99-interrupted-forward-proof/v2",
        },
        "probe_finalize": probe_finalize,
        "result": "already-recovered",
    }
    if include_stale_probe:
        stale_id = "c99-recovery-probe-40000000003-1"
        audit["stale_probe_recovery"] = {
            "bootstrap_cleanup": bootstrap_cleanup_record(),
            "bridge_site_identity": {
                "home_host": "complete99.co.il",
                "rest_host": "complete99.co.il",
                "siteurl_host": "complete99.co.il",
            },
            "cleanup": {**cleanup_record(), "removed_ids": [30]},
            "interrupted_forward_proof_sha256": proof_sha256,
            "probe_finalize": copy.deepcopy(probe_finalize),
            "reservation_status": {
                "adopted_forward_no_rollback": False,
                "deployment_id": stale_id,
                "interrupted_forward_candidate": False,
                "lock_owned": True,
                "no_rollback_artifacts": True,
                "phase": "reserved",
                "process_lock_available": True,
                "recovery_ready": True,
                "state_exists": False,
            },
        }
    return audit, loaded, probe_id


class RecoveryAuditValidatorTests(unittest.TestCase):
    def validate(
        self,
        repository_root: Path,
        audit_root: Path,
        audit: dict[str, object],
        proof_path: str,
        *,
        expect_observation: bool = False,
    ) -> dict[str, object]:
        audit_path = write_audit(audit_root, audit)
        summary = json.dumps(
            {
                "audit": str(audit_path),
                "deployment_id": audit["deployment_id"],
                "result": audit["result"],
            }
        )
        return VALIDATOR.validate_recovery_audit(
            summary,
            proof_path,
            audit_root,
            PROBE_ID,
            repository_root=repository_root,
            expect_observation=expect_observation,
        )

    def test_repository_interrupted_forward_v1_proof_and_dist_are_exact(self) -> None:
        proof = VALIDATOR.load_interrupted_forward_proof(
            "docs/recovery-proofs/c99-prod-31217684760-1.json",
            ROOT,
        )
        with tempfile.TemporaryDirectory() as directory:
            package = VALIDATOR.validate_interrupted_forward_dist(
                copy_historical_1_18_dist(Path(directory)),
                proof,
            )
        self.assertEqual("complete99-interrupted-forward-proof/v1", proof["schema"])
        self.assertEqual("1.18.0", package["version"])
        self.assertEqual(
            proof["proof"]["failed_run"]["installed_plugin_sha256"],
            package["installed_sha256"],
        )

    def test_repository_1_22_pending_stabilization_proof_is_exact(self) -> None:
        proof = VALIDATOR.load_interrupted_forward_proof(
            "docs/recovery-proofs/c99-prod-31598196288-1.json",
            ROOT,
        )
        package = VALIDATOR.validate_interrupted_forward_dist(
            ROOT / "plugin-dist",
            proof,
        )
        self.assertEqual("complete99-interrupted-forward-proof/v1", proof["schema"])
        self.assertEqual("1.22.0", package["version"])
        self.assertEqual(
            "9482ec75a92818e870e263036e291df9def80ad810414fb5d661e2cdb66908eb",
            proof["recovery_identity"]["database_fingerprint"],
        )

    def test_repository_robots_checkpoint_adoption_v3_proof_is_exact(self) -> None:
        proof = VALIDATOR.load_interrupted_forward_proof(
            "docs/recovery-proofs/c99-prod-31217684760-1-v2.json",
            ROOT,
        )
        with tempfile.TemporaryDirectory() as directory:
            package = VALIDATOR.validate_interrupted_forward_dist(
                copy_historical_1_18_dist(Path(directory)),
                proof,
            )
        adoption = proof["proof"]["forward_adoption"]
        receipt = proof["reviewed_forward_observation"]
        self.assertEqual("complete99-interrupted-forward-proof/v2", proof["schema"])
        self.assertEqual(
            "complete99-interrupted-forward-adoption/v3",
            adoption["schema"],
        )
        self.assertEqual(
            "bb55df5c5c3ff11780ce21fdfbbc75678547b5a9bc16ca48a86a933e19fdf32d",
            proof["proof_sha256"],
        )
        self.assertEqual(
            "e253c43e8822a8ddc6340206fae216690ed644a0fd524ca45dd56960293fb2a8",
            adoption["observation_audit_sha256"],
        )
        self.assertEqual(
            "55d9b71b3f71058e35d0929cbbd3cd68973088e87a75383dd6e90c6838edc33b",
            receipt["safe_status_sha256"],
        )
        self.assertEqual(
            VALIDATOR.INTERRUPTED_FORWARD_ROBOTS_CHECKPOINT_MISMATCHES,
            receipt["mismatches"],
        )
        self.assertEqual("1.18.0", package["version"])

    def test_independent_robots_checkpoint_authority_rejects_tampering(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            shutil.copytree(
                ROOT / "docs" / "recovery-proofs",
                repository_root / "docs" / "recovery-proofs",
            )
            proof_path = (
                repository_root
                / "docs"
                / "recovery-proofs"
                / "c99-prod-31217684760-1-v2.json"
            )
            original_proof = json.loads(proof_path.read_text(encoding="utf-8"))
            original_adoption = original_proof["proof"]["forward_adoption"]
            audit_path = repository_root / original_adoption[
                "observation_audit_path"
            ]
            original_audit = json.loads(audit_path.read_text(encoding="utf-8"))
            recovery_identity = {
                "database_fingerprint": original_audit[
                    "interrupted_forward_observation"
                ]["safe_status"]["database_fingerprint"],
                "database_manifest_sha256": original_audit[
                    "interrupted_forward_observation"
                ]["safe_status"]["database_manifest_sha256"],
            }

            def load_changed(
                audit_mutation: Callable[[dict[str, object]], None] | None,
                proof_mutation: Callable[[dict[str, object]], None] | None,
                *,
                recompute_receipt: bool = False,
                rebind_audit: bool = True,
            ) -> None:
                envelope = copy.deepcopy(original_proof)
                audit = copy.deepcopy(original_audit)
                if audit_mutation is not None:
                    audit_mutation(audit)
                if recompute_receipt:
                    receipt = audit["interrupted_forward_observation"]
                    safe = receipt["safe_status"]
                    receipt["safe_status_sha256"] = (
                        VALIDATOR.canonical_json_sha256(safe)
                    )
                    receipt["mismatches"] = VALIDATOR.interrupted_status_mismatches(
                        safe,
                        envelope["proof"]["failed_run"],
                        envelope["proof"]["prior_run"],
                        recovery_identity,
                    )
                audit_path.write_text(json.dumps(audit), encoding="utf-8")
                if rebind_audit:
                    envelope["proof"]["forward_adoption"][
                        "observation_audit_sha256"
                    ] = hashlib.sha256(audit_path.read_bytes()).hexdigest()
                if proof_mutation is not None:
                    proof_mutation(envelope["proof"])
                envelope["proof_sha256"] = VALIDATOR.canonical_json_sha256(
                    envelope["proof"]
                )
                proof_path.write_text(json.dumps(envelope), encoding="utf-8")
                VALIDATOR.load_interrupted_forward_proof(
                    str(proof_path),
                    repository_root,
                )

            cases = (
                (
                    "v1 schema confusion",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "schema", "complete99-interrupted-forward-adoption/v1"
                    ),
                    False,
                    True,
                ),
                (
                    "v2 schema confusion",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "schema", "complete99-interrupted-forward-adoption/v2"
                    ),
                    False,
                    True,
                ),
                (
                    "observation mismatches",
                    lambda audit: audit["interrupted_forward_observation"].__setitem__(
                        "mismatches", ["robots_applied"]
                    ),
                    None,
                    False,
                    True,
                ),
                (
                    "candidate",
                    lambda audit: audit["interrupted_forward_observation"][
                        "safe_status"
                    ].__setitem__("interrupted_forward_candidate", True),
                    None,
                    True,
                    True,
                ),
                (
                    "robots applied",
                    lambda audit: audit["interrupted_forward_observation"][
                        "safe_status"
                    ].__setitem__("robots_applied", True),
                    None,
                    True,
                    True,
                ),
                (
                    "robots managed",
                    lambda audit: audit["interrupted_forward_observation"][
                        "safe_status"
                    ].__setitem__("robots_managed_sha256", "0" * 64),
                    None,
                    True,
                    True,
                ),
                (
                    "unrelated invariant",
                    lambda audit: audit["interrupted_forward_observation"][
                        "safe_status"
                    ].__setitem__("migration_invariants_valid", False),
                    None,
                    True,
                    True,
                ),
                (
                    "diagnostic authority",
                    lambda audit: audit["interrupted_forward_observation"].__setitem__(
                        "recovery_authority", True
                    ),
                    None,
                    False,
                    True,
                ),
                (
                    "database identity",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "observed_database_fingerprint", "0" * 64
                    ),
                    False,
                    True,
                ),
                (
                    "plugin identity",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "observed_plugin_sha256", "0" * 64
                    ),
                    False,
                    True,
                ),
                (
                    "robots identity",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "observed_robots_sha256", "0" * 64
                    ),
                    False,
                    True,
                ),
                (
                    "public health",
                    lambda audit: audit["health"].__setitem__("status", "failed"),
                    None,
                    False,
                    True,
                ),
                (
                    "cleanup",
                    lambda audit: audit["cleanup"].__setitem__("route_404", False),
                    None,
                    False,
                    True,
                ),
                (
                    "raw audit digest",
                    lambda audit: audit.__setitem__("result", "failed"),
                    None,
                    False,
                    False,
                ),
            )
            for label, audit_mutation, proof_mutation, recompute, rebind in cases:
                with self.subTest(tamper=label), self.assertRaises(
                    VALIDATOR.AuditValidationError
                ):
                    load_changed(
                        audit_mutation,
                        proof_mutation,
                        recompute_receipt=recompute,
                        rebind_audit=rebind,
                    )

    def test_interrupted_observation_binds_commit_path_digest_and_probe(self) -> None:
        audit, failed, prior, context = interrupted_observation_audit()

        def validate(value: dict[str, object]) -> None:
            VALIDATOR.validate_interrupted_observation_audit(
                value,
                failed,
                prior,
                {
                    "database_fingerprint": context["database_fingerprint"],
                    "database_manifest_sha256": context[
                        "database_manifest_sha256"
                    ],
                },
                context["proof_path"],
                context["proof_sha256"],
                context["probe_id"],
                expected_commit="b" * 40,
            )

        validate(audit)
        for label, mutate, message in (
            (
                "commit",
                lambda value: value.__setitem__("commit", "d" * 40),
                "commit",
            ),
            (
                "proof path",
                lambda value: value["interrupted_forward_proof"].__setitem__(
                    "path", "docs/recovery-proofs/other.json"
                ),
                "path or digest",
            ),
            (
                "proof digest",
                lambda value: value["interrupted_forward_proof"].__setitem__(
                    "proof_sha256", "e" * 64
                ),
                "path or digest",
            ),
            (
                "probe",
                lambda value: value["discovery"].__setitem__(
                    "probe_id", "c99-recovery-probe-40000000004-1"
                ),
                "discovery identity",
            ),
        ):
            with self.subTest(label=label):
                changed = copy.deepcopy(audit)
                mutate(changed)
                with self.assertRaisesRegex(VALIDATOR.AuditValidationError, message):
                    validate(changed)

    def test_database_mismatch_observation_is_exact_unconsumed_evidence(self) -> None:
        audit, failed, prior, context = interrupted_database_mismatch_audit()

        def validate(value: dict[str, object]) -> None:
            VALIDATOR.validate_interrupted_database_mismatch_observation_audit(
                value,
                failed,
                prior,
                context["recovery_identity"],
                str(context["proof_path"]),
                str(context["proof_sha256"]),
                str(context["probe_id"]),
                expected_commit="b" * 40,
            )

        validate(audit)
        for label, mutate, message in (
            (
                "fingerprint did not drift",
                lambda value: value["interrupted_forward_observation"][
                    "safe_status"
                ].__setitem__(
                    "database_fingerprint",
                    context["recovery_identity"]["database_fingerprint"],
                ),
                "both reviewed database identities|identity changed",
            ),
            (
                "manifest did not drift",
                lambda value: value["interrupted_forward_observation"][
                    "safe_status"
                ].__setitem__(
                    "database_manifest_sha256",
                    context["recovery_identity"]["database_manifest_sha256"],
                ),
                "manifest|both reviewed database identities",
            ),
            (
                "candidate",
                lambda value: value["interrupted_forward_observation"][
                    "safe_status"
                ].__setitem__("interrupted_forward_candidate", True),
                "both reviewed database identities",
            ),
            (
                "prior identity",
                lambda value: value["interrupted_forward_observation"][
                    "safe_status"
                ].__setitem__("prior_plugin_sha256", "0" * 64),
                "both reviewed database identities",
            ),
            (
                "unsafe lineage",
                lambda value: value["interrupted_forward_observation"][
                    "safe_status"
                ].__setitem__(
                    "post_install_database_fingerprint", "secret-like-value"
                ),
                "both reviewed database identities",
            ),
            (
                "mismatch list",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "mismatches", ["database_fingerprint"]
                ),
                "receipt schema",
            ),
            (
                "proof consumed",
                lambda value: value.__setitem__("proof_consumed", True),
                "audit schema",
            ),
            (
                "result",
                lambda value: value.__setitem__(
                    "result", "interrupted_forward_observed"
                ),
                "audit schema",
            ),
            (
                "commit",
                lambda value: value.__setitem__("commit", "d" * 40),
                "commit",
            ),
            (
                "probe run",
                lambda value: value["discovery"].__setitem__(
                    "probe_id", "c99-recovery-probe-40000000004-1"
                ),
                "discovery identity",
            ),
            (
                "proof path",
                lambda value: value["interrupted_forward_proof"].__setitem__(
                    "path", "docs/recovery-proofs/other.json"
                ),
                "proof path or digest",
            ),
            (
                "proof SHA",
                lambda value: value["interrupted_forward_proof"].__setitem__(
                    "proof_sha256", "0" * 64
                ),
                "proof path or digest",
            ),
            (
                "receipt schema",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "schema", "complete99-interrupted-forward-observation/v1"
                ),
                "receipt schema",
            ),
            (
                "safe status digest",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "safe_status_sha256", "0" * 64
                ),
                "receipt identity",
            ),
            (
                "health",
                lambda value: value["health"].__setitem__(
                    "deployment_id", "c99-prod-other-40000000002-1"
                ),
                "health",
            ),
            (
                "cleanup",
                lambda value: value["cleanup"].__setitem__("route_404", False),
                "cleanup",
            ),
        ):
            with self.subTest(label=label):
                changed = copy.deepcopy(audit)
                mutate(changed)
                with self.assertRaisesRegex(
                    VALIDATOR.AuditValidationError,
                    message,
                ):
                    validate(changed)

        non_database_mutations = {
            "deployment_id": "c99-prod-other-40000000002-1",
            "phase": "installed",
            "state_exists": False,
            "lock_owned": False,
            "recovery_ready": False,
            "process_lock_available": False,
            "expected_sha256": "0" * 64,
            "expected_version": "1.17.0",
            "installed_plugin_sha256": "0" * 64,
            "current_target_dir_exists": False,
            "current_plugin_main_exists": False,
            "current_plugin_sha256": "0" * 64,
            "current_active": False,
            "current_version": "1.17.0",
            "runtime_loaded": False,
            "runtime_version": "1.17.0",
            "migration_failed": True,
            "migration_invariants_valid": False,
            "no_rollback_artifacts": False,
            "database_restored": True,
            "baseline_database_journal_valid": False,
            "baseline_sync_secret_existed": False,
            "baseline_sync_configured": False,
            "current_deployment": "c99-prod-other-40000000002-1",
            "current_database_version": "1.17.0",
            "baseline_database_fingerprint": "0" * 64,
            "current_sync_configured": False,
            "database_fingerprint_available": False,
            "had_plugin": False,
            "prior_target_dir_exists": False,
            "prior_plugin_main_exists": False,
            "prior_plugin_sha256": "0" * 64,
            "prior_version": "1.16.0",
            "prior_active": False,
            "prior_deployment": "c99-prod-other-40000000001-1",
            "robots_applied": False,
            "robots_restored": True,
            "robots_prior_exists": False,
            "robots_prior_sha256": "0" * 64,
            "robots_managed_sha256": "0" * 64,
            "current_robots_sha256": "0" * 64,
            "adopted_forward_no_rollback": True,
            "interrupted_forward_candidate": True,
            "interrupted_forward_proof_sha256": "0" * 64,
            "interrupted_forward_database_manifest_sha256": "0" * 64,
            "post_install_database_fingerprint": "secret-like-value",
        }
        for field, replacement in non_database_mutations.items():
            with self.subTest(independent_non_database_field=field):
                changed = copy.deepcopy(audit)
                changed["interrupted_forward_observation"]["safe_status"][
                    field
                ] = replacement
                with self.assertRaises(VALIDATOR.AuditValidationError):
                    validate(changed)

        loaded = {
            "path": context["proof_path"],
            "proof": {"failed_run": failed, "prior_run": prior},
            "proof_sha256": context["proof_sha256"],
            "recovery_identity": context["recovery_identity"],
            "schema": "complete99-interrupted-forward-proof/v1",
        }
        with tempfile.TemporaryDirectory() as temporary:
            audit_root = Path(temporary)
            audit_path = write_audit(audit_root, audit)
            summary = json.dumps(
                {
                    "audit": str(audit_path),
                    "deployment_id": failed["deployment_id"],
                    "proof_consumed": False,
                    "result": "interrupted_forward_database_mismatch_observed",
                }
            )
            with mock.patch.object(
                VALIDATOR,
                "load_interrupted_forward_proof",
                return_value=loaded,
            ), mock.patch.object(
                VALIDATOR,
                "validate_interrupted_forward_dist",
            ):
                result = VALIDATOR.validate_recovery_audit(
                    summary,
                    "",
                    audit_root,
                    str(context["probe_id"]),
                    interrupted_forward_proof_path="proof.json",
                    expect_interrupted_forward=True,
                    expect_observation=True,
                    dist=Path("plugin-dist"),
                )
        self.assertFalse(result["proof_consumed"])
        self.assertTrue(result["proof_observed"])

    def test_mismatch_diagnostic_is_independent_bounded_evidence_only(self) -> None:
        audit, failed, prior, context = interrupted_mismatch_diagnostic_audit()

        def validate(value: dict[str, object]) -> None:
            VALIDATOR.validate_interrupted_mismatch_diagnostic_audit(
                value,
                failed,
                prior,
                context["recovery_identity"],
                str(context["proof_path"]),
                str(context["proof_sha256"]),
                str(context["probe_id"]),
                expected_commit="b" * 40,
            )

        validate(audit)
        receipt = audit["interrupted_forward_observation"]
        self.assertEqual(
            ["database_fingerprint", "interrupted_forward_candidate"],
            receipt["mismatches"],
        )

        for phase in (
            "failed",
            "installed_pending_cleanup",
            "installed_pending_stabilization",
        ):
            with self.subTest(bounded_bridge_phase=phase):
                changed = copy.deepcopy(audit)
                changed_receipt = changed["interrupted_forward_observation"]
                safe = changed_receipt["safe_status"]
                safe["phase"] = phase
                changed["discovery"]["owner_phase"] = phase
                if phase == "installed_pending_stabilization":
                    safe.update(
                        {
                            "campaign_capacity_diagnostic": {
                                "campaign_cohort_inspectable": True,
                                "fresh_install_empty": True,
                                "lifecycle_reserve_inspectable": False,
                                "operations_cohort_inspectable": True,
                                "prior_inactive_receipt_valid": False,
                                "quarantine_reserve_inspectable": False,
                            },
                            "campaign_lifecycle": {
                                "canonical": True,
                                "generation": 7,
                                "state": "active",
                            },
                            "campaign_operational": {
                                "cache_ready": True,
                                "capabilities_ready": True,
                                "capacity_inspectable": True,
                                "capacity_ready": True,
                                "capacity_write_ready": True,
                                "cron_inspectable": True,
                                "cron_ready": False,
                                "evidence_inspectable": True,
                                "evidence_ready": True,
                                "ready": False,
                                "suppression_inspectable": True,
                                "suppression_invalid": False,
                                "suppression_ready": True,
                                "suppression_recoverable_pending": False,
                            },
                            "candidate_activation_completed_at": 1_786_533_000,
                            "candidate_activation_phase": "complete",
                            "candidate_activation_required": True,
                            "candidate_database_fingerprint": "e" * 64,
                            "candidate_prior_active": True,
                            "candidate_requested_active": True,
                            "forward_ready": True,
                            "forward_stabilization_candidate": True,
                            "migration_invariant_checks": {
                                "campaigns": True,
                                "content": True,
                                "culinary_science": True,
                                "evaluation_catalog": True,
                                "ops": True,
                                "settings": True,
                            },
                            "temp_removed": True,
                        }
                    )
                changed_receipt["mismatches"] = (
                    VALIDATOR.interrupted_status_mismatches(
                        safe,
                        failed,
                        prior,
                        context["recovery_identity"],
                    )
                )
                changed_receipt["safe_status_sha256"] = (
                    VALIDATOR.canonical_json_sha256(safe)
                )
                validate(changed)

        for label, mutate in (
            (
                "proof consumed",
                lambda value: value.__setitem__("proof_consumed", True),
            ),
            (
                "diagnostic authority",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "recovery_authority", True
                ),
            ),
            (
                "diagnostic flag",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "diagnostic_only", False
                ),
            ),
            (
                "receipt schema",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "schema", "complete99-interrupted-forward-observation/v2"
                ),
            ),
            (
                "mismatch order",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "mismatches", list(reversed(receipt["mismatches"]))
                ),
            ),
            (
                "safe digest",
                lambda value: value["interrupted_forward_observation"].__setitem__(
                    "safe_status_sha256", "0" * 64
                ),
            ),
            (
                "result",
                lambda value: value.__setitem__(
                    "result", "interrupted_forward_observed"
                ),
            ),
            (
                "proof path",
                lambda value: value["interrupted_forward_proof"].__setitem__(
                    "path", "docs/recovery-proofs/other.json"
                ),
            ),
            (
                "health",
                lambda value: value["health"].__setitem__(
                    "deployment_id", "c99-prod-other-40000000002-1"
                ),
            ),
            (
                "home",
                lambda value: value["rendered_home"].__setitem__(
                    "exact_path", "/en/"
                ),
            ),
            (
                "robots",
                lambda value: value["robots"].__setitem__("status", 404),
            ),
            (
                "cleanup",
                lambda value: value["cleanup"].__setitem__("route_404", False),
            ),
        ):
            with self.subTest(tamper=label):
                changed = copy.deepcopy(audit)
                mutate(changed)
                with self.assertRaises(VALIDATOR.AuditValidationError):
                    validate(changed)

        reviewed_mutations = {
            "adopted_forward_no_rollback": True,
            "baseline_database_fingerprint": "0" * 64,
            "baseline_database_journal_valid": False,
            "baseline_sync_configured": False,
            "baseline_sync_secret_existed": False,
            "current_active": False,
            "current_database_version": "1.17.0",
            "current_deployment": "c99-prod-other-40000000002-1",
            "current_plugin_main_exists": False,
            "current_plugin_sha256": "0" * 64,
            "current_robots_sha256": "0" * 64,
            "current_sync_configured": False,
            "current_target_dir_exists": False,
            "current_version": "1.17.0",
            "database_fingerprint_available": False,
            "database_restored": True,
            "deployment_id": "c99-prod-other-40000000002-1",
            "expected_sha256": "0" * 64,
            "expected_version": "1.17.0",
            "had_plugin": False,
            "installed_plugin_sha256": "0" * 64,
            "interrupted_forward_database_manifest_sha256": "0" * 64,
            "interrupted_forward_proof_sha256": "0" * 64,
            "lock_owned": False,
            "migration_failed": True,
            "migration_invariants_valid": False,
            "no_rollback_artifacts": False,
            "phase": "installed",
            "prior_active": False,
            "prior_deployment": "c99-prod-other-40000000001-1",
            "prior_plugin_main_exists": False,
            "prior_plugin_sha256": "0" * 64,
            "prior_target_dir_exists": False,
            "prior_version": "1.16.0",
            "process_lock_available": False,
            "recovery_ready": False,
            "robots_applied": False,
            "robots_managed_sha256": "0" * 64,
            "robots_prior_exists": False,
            "robots_prior_sha256": "0" * 64,
            "robots_restored": True,
            "runtime_loaded": False,
            "runtime_version": "1.17.0",
            "state_exists": False,
        }
        for field, replacement in reviewed_mutations.items():
            with self.subTest(deterministic_mismatch=field):
                changed = copy.deepcopy(audit)
                changed_receipt = changed["interrupted_forward_observation"]
                changed_receipt["safe_status"][field] = replacement
                changed_receipt["safe_status_sha256"] = (
                    VALIDATOR.canonical_json_sha256(changed_receipt["safe_status"])
                )
                with self.assertRaisesRegex(
                    VALIDATOR.AuditValidationError,
                    "receipt identity",
                ):
                    validate(changed)

        unsafe_cases: list[tuple[str, Callable[[dict[str, object]], None]]] = []
        for field in VALIDATOR.INTERRUPTED_FORWARD_SAFE_BOOLEAN_FIELDS:
            unsafe_cases.append(
                (field, lambda value, key=field: value.__setitem__(key, "false"))
            )
        for field in VALIDATOR.INTERRUPTED_FORWARD_SAFE_DIGEST_FIELDS:
            unsafe_cases.append(
                (field, lambda value, key=field: value.__setitem__(key, "secret"))
            )
        for field in VALIDATOR.INTERRUPTED_FORWARD_SAFE_DEPLOYMENT_FIELDS:
            unsafe_cases.append(
                (field, lambda value, key=field: value.__setitem__(key, "secret"))
            )
        for field in VALIDATOR.INTERRUPTED_FORWARD_SAFE_VERSION_FIELDS:
            unsafe_cases.append(
                (field, lambda value, key=field: value.__setitem__(key, "1." * 80 + "0"))
            )
        unsafe_cases.extend(
            (
                ("phase", lambda value: value.__setitem__("phase", "secret")),
                (
                    "manifest count",
                    lambda value: value["database_manifest"].__setitem__(
                        "posts_count", 9_223_372_036_854_775_808
                    ),
                ),
            )
        )
        for label, mutate in unsafe_cases:
            with self.subTest(unsafe_field=label):
                changed = copy.deepcopy(audit)
                changed_receipt = changed["interrupted_forward_observation"]
                mutate(changed_receipt["safe_status"])
                if label == "manifest count":
                    changed_receipt["safe_status"]["database_manifest_sha256"] = (
                        VALIDATOR.canonical_json_sha256(
                            changed_receipt["safe_status"]["database_manifest"]
                        )
                    )
                changed_receipt["safe_status_sha256"] = (
                    VALIDATOR.canonical_json_sha256(changed_receipt["safe_status"])
                )
                with self.assertRaises(VALIDATOR.AuditValidationError):
                    validate(changed)

        loaded = {
            "path": context["proof_path"],
            "proof": {"failed_run": failed, "prior_run": prior},
            "proof_sha256": context["proof_sha256"],
            "recovery_identity": context["recovery_identity"],
            "schema": "complete99-interrupted-forward-proof/v1",
        }
        with tempfile.TemporaryDirectory() as temporary:
            audit_root = Path(temporary)
            audit_path = write_audit(audit_root, audit)
            summary = json.dumps(
                {
                    "audit": str(audit_path),
                    "deployment_id": failed["deployment_id"],
                    "proof_consumed": False,
                    "result": "interrupted_forward_mismatch_diagnostic_observed",
                }
            )
            with mock.patch.object(
                VALIDATOR,
                "load_interrupted_forward_proof",
                return_value=loaded,
            ), mock.patch.object(
                VALIDATOR,
                "validate_interrupted_forward_dist",
            ):
                result = VALIDATOR.validate_recovery_audit(
                    summary,
                    "",
                    audit_root,
                    str(context["probe_id"]),
                    interrupted_forward_proof_path="proof.json",
                    expect_interrupted_forward=True,
                    expect_observation=True,
                    dist=Path("plugin-dist"),
                )
        self.assertFalse(result["proof_consumed"])
        self.assertTrue(result["proof_observed"])

    def test_adoption_v2_loader_alone_can_bind_reviewed_database_drift(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            v2_path, observation_path, _, recovery_identity = (
                write_interrupted_database_mismatch_proofs(repository_root)
            )
            original_v2 = v2_path.read_bytes()
            original_observation = observation_path.read_bytes()

            def load() -> dict[str, object]:
                with mock.patch.object(
                    VALIDATOR,
                    "validate_interrupted_source_audits",
                    return_value=recovery_identity,
                ):
                    return VALIDATOR.load_interrupted_forward_proof(
                        str(v2_path),
                        repository_root,
                    )

            loaded = load()
            self.assertEqual(
                "complete99-interrupted-forward-adoption/v2",
                loaded["proof"]["forward_adoption"]["schema"],
            )

            def rewrite_v2(mutate: Callable[[dict[str, object]], None]) -> None:
                envelope = json.loads(original_v2)
                mutate(envelope)
                envelope["proof_sha256"] = VALIDATOR.canonical_json_sha256(
                    envelope["proof"]
                )
                v2_path.write_text(json.dumps(envelope), encoding="utf-8")

            cases = (
                (
                    "v1 adoption cannot bind drift",
                    lambda envelope: envelope["proof"]["forward_adoption"].__setitem__(
                        "schema", "complete99-interrupted-forward-adoption/v1"
                    ),
                    "adoption identity",
                ),
                (
                    "v2 adoption requires fingerprint drift",
                    lambda envelope: envelope["proof"]["forward_adoption"].__setitem__(
                        "observed_database_fingerprint",
                        recovery_identity["database_fingerprint"],
                    ),
                    "adoption identity",
                ),
                (
                    "v2 adoption requires manifest drift",
                    lambda envelope: envelope["proof"]["forward_adoption"].__setitem__(
                        "observed_database_manifest_sha256",
                        recovery_identity["database_manifest_sha256"],
                    ),
                    "adoption identity",
                ),
                (
                    "observation digest",
                    lambda envelope: envelope["proof"]["forward_adoption"].__setitem__(
                        "observation_audit_sha256", "0" * 64
                    ),
                    "digest does not match",
                ),
            )
            for label, mutate, message in cases:
                with self.subTest(label=label):
                    v2_path.write_bytes(original_v2)
                    observation_path.write_bytes(original_observation)
                    rewrite_v2(mutate)
                    with self.assertRaisesRegex(
                        VALIDATOR.AuditValidationError,
                        message,
                    ):
                        load()

            v2_path.write_bytes(original_v2)
            tampered_observation = json.loads(original_observation)
            tampered_observation["proof_consumed"] = True
            observation_path.write_text(
                json.dumps(tampered_observation),
                encoding="utf-8",
            )
            envelope = json.loads(original_v2)
            envelope["proof"]["forward_adoption"][
                "observation_audit_sha256"
            ] = hashlib.sha256(observation_path.read_bytes()).hexdigest()
            envelope["proof_sha256"] = VALIDATOR.canonical_json_sha256(
                envelope["proof"]
            )
            v2_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "audit schema",
            ):
                load()

            v2_path.write_bytes(original_v2)
            diagnostic, _, _, _ = interrupted_mismatch_diagnostic_audit()
            observation_path.write_text(json.dumps(diagnostic), encoding="utf-8")
            envelope = json.loads(original_v2)
            envelope["proof"]["forward_adoption"][
                "observation_audit_sha256"
            ] = hashlib.sha256(observation_path.read_bytes()).hexdigest()
            envelope["proof_sha256"] = VALIDATOR.canonical_json_sha256(
                envelope["proof"]
            )
            v2_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "audit schema",
            ):
                load()

            direct_receipt = (
                repository_root
                / "docs"
                / "recovery-proofs"
                / "c99-diagnostic-v3.json"
            )
            direct_receipt.write_text(
                json.dumps(diagnostic["interrupted_forward_observation"]),
                encoding="utf-8",
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "proof schema",
            ):
                VALIDATOR.load_interrupted_forward_proof(
                    str(direct_receipt),
                    repository_root,
                )

            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "direct reviewed JSON|proof schema",
            ):
                VALIDATOR.load_interrupted_forward_proof(
                    str(observation_path),
                    repository_root,
                )

    def test_interrupted_recovery_binds_adoption_finalize_and_cleanup(self) -> None:
        failed, prior, database, recovery_identity = interrupted_identities()
        adoption = {
            "observed_database_fingerprint": recovery_identity[
                "database_fingerprint"
            ],
            "observed_database_manifest": database["manifest"],
            "observed_database_manifest_sha256": database["manifest_sha256"],
            "observed_database_storage": database["storage"],
        }
        proof_sha256 = "d" * 64
        loaded = {
            "path": f"docs/recovery-proofs/{failed['deployment_id']}-v2.json",
            "proof": {
                "failed_run": failed,
                "forward_adoption": adoption,
                "prior_run": prior,
            },
            "proof_sha256": proof_sha256,
        }
        pre_adoption = VALIDATOR.expected_interrupted_observation(
            failed,
            prior,
            recovery_identity["database_fingerprint"],
            database["manifest"],
            database["manifest_sha256"],
            database["storage"],
            failed["installed_plugin_sha256"],
        )
        receipt = {
            "adopted_forward_no_rollback": True,
            "cache_purge": {"deferred_to_finalize": True},
            "database_manifest": database["manifest"],
            "database_manifest_sha256": database["manifest_sha256"],
            "database_storage": database["storage"],
            "database_version": failed["version"],
            "deployment_id": failed["deployment_id"],
            "idempotent": False,
            "installed_plugin_sha256": failed["installed_plugin_sha256"],
            "interrupted_forward_proof_sha256": proof_sha256,
            "post_install_database_fingerprint": recovery_identity[
                "database_fingerprint"
            ],
            "stabilized": True,
            "stabilized_from_phase": "installing",
            "version": failed["version"],
        }
        status = {
            "adopted_forward_no_rollback": True,
            "database_fingerprint": recovery_identity["database_fingerprint"],
            "database_manifest_sha256": database["manifest_sha256"],
            "deployment_id": failed["deployment_id"],
            "installed_plugin_sha256": failed["installed_plugin_sha256"],
            "interrupted_forward_proof_sha256": proof_sha256,
            "phase": "installed",
            "state_exists": True,
            "version": failed["version"],
        }
        probe_id = "c99-recovery-probe-40000000004-1"
        audit = {
            **interrupted_common(str(failed["deployment_id"])),
            **interrupted_health_home_robots(failed, prior),
            **interrupted_health_home_robots(failed, prior, "pre_adoption_"),
            "adopted_forward_no_rollback": True,
            "decision": "adopt_interrupted_forward",
            "discovery": interrupted_discovery(str(failed["deployment_id"]), probe_id),
            "finalize": finalize_record(),
            "interrupted_forward_adoption": {"receipt": receipt, "status": status},
            "interrupted_forward_proof": {
                "path": loaded["path"],
                "proof_sha256": proof_sha256,
                "schema": "complete99-interrupted-forward-proof/v2",
            },
            "pre_adoption_observation": pre_adoption,
            "result": "recovered",
        }
        VALIDATOR.validate_interrupted_forward_recovery_audit(
            audit,
            loaded,
            probe_id,
        )
        resumed = copy.deepcopy(audit)
        for key in (
            "pre_adoption_health",
            "pre_adoption_observation",
            "pre_adoption_rendered_home",
            "pre_adoption_robots",
        ):
            del resumed[key]
        resumed["interrupted_forward_adoption"]["receipt"]["idempotent"] = True
        resumed["discovery"]["owner_phase"] = "installed"
        VALIDATOR.validate_interrupted_forward_recovery_audit(
            resumed,
            loaded,
            probe_id,
        )
        for phase in ("committing", "commit_failed", "committed", "cleanup_failed"):
            with self.subTest(finalize_resume_phase=phase):
                terminal = copy.deepcopy(resumed)
                del terminal["interrupted_forward_adoption"]
                terminal["discovery"]["owner_phase"] = phase
                terminal["interrupted_forward_finalize_resume"] = {
                    "adopted_forward_no_rollback": True,
                    "committed_expected_active": True,
                    "committed_expected_absent": False,
                    "committed_expected_deployment": failed["deployment_id"],
                    "committed_expected_plugin_sha256": failed[
                        "installed_plugin_sha256"
                    ],
                    "committed_expected_robots_exists": True,
                    "committed_expected_robots_sha256": prior["robots_sha256"],
                    "committed_expected_version": failed["version"],
                    "committed_outcome": "installed",
                    "database_fingerprint": recovery_identity[
                        "database_fingerprint"
                    ],
                    "database_manifest_sha256": database["manifest_sha256"],
                    "deployment_id": failed["deployment_id"],
                    "installed_plugin_sha256": failed[
                        "installed_plugin_sha256"
                    ],
                    "interrupted_forward_proof_sha256": proof_sha256,
                    "phase": phase,
                    "schema": "complete99-interrupted-forward-finalize-resume/v1",
                    "state_exists": True,
                    "version": failed["version"],
                }
                VALIDATOR.validate_interrupted_forward_recovery_audit(
                    terminal,
                    loaded,
                    probe_id,
                )
                if phase in {"committed", "cleanup_failed"}:
                    lock_only = copy.deepcopy(terminal)
                    lock_only["interrupted_forward_finalize_resume"][
                        "state_exists"
                    ] = False
                    VALIDATOR.validate_interrupted_forward_recovery_audit(
                        lock_only,
                        loaded,
                        probe_id,
                    )
                else:
                    missing_state = copy.deepcopy(terminal)
                    missing_state["interrupted_forward_finalize_resume"][
                        "state_exists"
                    ] = False
                    with self.assertRaisesRegex(
                        VALIDATOR.AuditValidationError,
                        "finalize-resume receipt",
                    ):
                        VALIDATOR.validate_interrupted_forward_recovery_audit(
                            missing_state,
                            loaded,
                            probe_id,
                        )
                terminal["discovery"]["owner_phase"] = "installed"
                with self.assertRaisesRegex(
                    VALIDATOR.AuditValidationError,
                    "discovery identity",
                ):
                    VALIDATOR.validate_interrupted_forward_recovery_audit(
                        terminal,
                        loaded,
                        probe_id,
                    )
        for mismatched in (copy.deepcopy(audit), copy.deepcopy(resumed)):
            mismatched["interrupted_forward_adoption"]["receipt"]["idempotent"] = (
                not mismatched["interrupted_forward_adoption"]["receipt"][
                    "idempotent"
                ]
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "idempotency",
            ):
                VALIDATOR.validate_interrupted_forward_recovery_audit(
                    mismatched,
                    loaded,
                    probe_id,
                )
        for label, mutate, message in (
            (
                "adoption status",
                lambda value: value["interrupted_forward_adoption"]["status"].__setitem__(
                    "interrupted_forward_proof_sha256", "e" * 64
                ),
                "adoption status",
            ),
            (
                "finalize",
                lambda value: value["finalize"].__setitem__("state_removed", False),
                "finalize",
            ),
            (
                "cleanup",
                lambda value: value["cleanup"].__setitem__("route_404", False),
                "cleanup",
            ),
        ):
            with self.subTest(label=label):
                changed = copy.deepcopy(audit)
                mutate(changed)
                with self.assertRaisesRegex(VALIDATOR.AuditValidationError, message):
                    VALIDATOR.validate_interrupted_forward_recovery_audit(
                        changed,
                        loaded,
                        probe_id,
                    )

    def test_robots_checkpoint_recovery_binds_pre_adoption_v3_and_resume(
        self,
    ) -> None:
        loaded = VALIDATOR.load_interrupted_forward_proof(
            "docs/recovery-proofs/c99-prod-31217684760-1-v2.json",
            ROOT,
        )
        proof = loaded["proof"]
        failed = proof["failed_run"]
        prior = proof["prior_run"]
        adoption = proof["forward_adoption"]
        probe_id = "c99-recovery-probe-40000000004-1"
        receipt = {
            "adopted_forward_no_rollback": True,
            "cache_purge": {"deferred_to_finalize": True},
            "database_manifest": adoption["observed_database_manifest"],
            "database_manifest_sha256": adoption[
                "observed_database_manifest_sha256"
            ],
            "database_storage": adoption["observed_database_storage"],
            "database_version": failed["version"],
            "deployment_id": failed["deployment_id"],
            "idempotent": False,
            "installed_plugin_sha256": failed["installed_plugin_sha256"],
            "interrupted_forward_proof_sha256": loaded["proof_sha256"],
            "post_install_database_fingerprint": adoption[
                "observed_database_fingerprint"
            ],
            "stabilized": True,
            "stabilized_from_phase": "installing",
            "version": failed["version"],
        }
        status = {
            "adopted_forward_no_rollback": True,
            "database_fingerprint": adoption["observed_database_fingerprint"],
            "database_manifest_sha256": adoption[
                "observed_database_manifest_sha256"
            ],
            "deployment_id": failed["deployment_id"],
            "installed_plugin_sha256": failed["installed_plugin_sha256"],
            "interrupted_forward_proof_sha256": loaded["proof_sha256"],
            "phase": "installed",
            "state_exists": True,
            "version": failed["version"],
        }
        audit = {
            **interrupted_common(failed["deployment_id"]),
            **interrupted_health_home_robots(failed, prior),
            **interrupted_health_home_robots(failed, prior, "pre_adoption_"),
            "adopted_forward_no_rollback": True,
            "decision": "adopt_interrupted_forward",
            "discovery": interrupted_discovery(failed["deployment_id"], probe_id),
            "finalize": finalize_record(),
            "interrupted_forward_adoption": {"receipt": receipt, "status": status},
            "interrupted_forward_proof": {
                "path": loaded["path"],
                "proof_sha256": loaded["proof_sha256"],
                "schema": "complete99-interrupted-forward-proof/v2",
            },
            "pre_adoption_observation": loaded[
                "reviewed_forward_observation"
            ],
            "result": "recovered",
        }
        VALIDATOR.validate_interrupted_forward_recovery_audit(
            audit,
            loaded,
            probe_id,
        )

        for label, mutate in (
            (
                "extra mismatch",
                lambda value: value["pre_adoption_observation"][
                    "mismatches"
                ].append("state_exists"),
            ),
            (
                "safe status",
                lambda value: value["pre_adoption_observation"][
                    "safe_status"
                ].__setitem__("robots_applied", True),
            ),
            (
                "v1 receipt",
                lambda value: value.__setitem__(
                    "pre_adoption_observation",
                    VALIDATOR.expected_interrupted_observation(
                        failed,
                        prior,
                        adoption["observed_database_fingerprint"],
                        adoption["observed_database_manifest"],
                        adoption["observed_database_manifest_sha256"],
                        adoption["observed_database_storage"],
                        "",
                    ),
                ),
            ),
        ):
            with self.subTest(pre_adoption_tamper=label):
                changed = copy.deepcopy(audit)
                mutate(changed)
                with self.assertRaisesRegex(
                    VALIDATOR.AuditValidationError,
                    "robots checkpoint changed",
                ):
                    VALIDATOR.validate_interrupted_forward_recovery_audit(
                        changed,
                        loaded,
                        probe_id,
                    )

        resumed = copy.deepcopy(audit)
        for key in (
            "pre_adoption_health",
            "pre_adoption_observation",
            "pre_adoption_rendered_home",
            "pre_adoption_robots",
        ):
            del resumed[key]
        resumed["interrupted_forward_adoption"]["receipt"]["idempotent"] = True
        resumed["discovery"]["owner_phase"] = "installed"
        VALIDATOR.validate_interrupted_forward_recovery_audit(
            resumed,
            loaded,
            probe_id,
        )

    def test_interrupted_already_finalized_audit_is_exact_and_dispatchable(
        self,
    ) -> None:
        audit, loaded, probe_id = interrupted_already_finalized_audit(
            include_stale_probe=True
        )
        VALIDATOR.validate_interrupted_forward_already_finalized_audit(
            audit,
            loaded,
            probe_id,
        )
        for label, mutate, message in (
            (
                "extra audit field",
                lambda value: value.__setitem__("unexpected", True),
                "terminal path",
            ),
            (
                "attestation field",
                lambda value: value[
                    "interrupted_forward_finalized_attestation"
                ].__setitem__("proof_sha256", "0" * 64),
                "attestation receipt",
            ),
            (
                "probe finalize",
                lambda value: value["probe_finalize"].__setitem__(
                    "state_removed", False
                ),
                "probe finalization",
            ),
            (
                "stale state",
                lambda value: value["stale_probe_recovery"][
                    "reservation_status"
                ].__setitem__("state_exists", True),
                "stale probe reservation",
            ),
            (
                "stale proof",
                lambda value: value["stale_probe_recovery"].__setitem__(
                    "interrupted_forward_proof_sha256", "0" * 64
                ),
                "proof digest",
            ),
        ):
            with self.subTest(label=label):
                changed = copy.deepcopy(audit)
                mutate(changed)
                with self.assertRaisesRegex(
                    VALIDATOR.AuditValidationError,
                    message,
                ):
                    VALIDATOR.validate_interrupted_forward_already_finalized_audit(
                        changed,
                        loaded,
                        probe_id,
                    )

        with tempfile.TemporaryDirectory() as temporary:
            audit_root = Path(temporary)
            audit_path = write_audit(audit_root, audit)
            summary = json.dumps(
                {
                    "audit": str(audit_path),
                    "deployment_id": probe_id,
                    "result": "already-recovered",
                }
            )
            with mock.patch.object(
                VALIDATOR,
                "load_interrupted_forward_proof",
                return_value=loaded,
            ), mock.patch.object(
                VALIDATOR,
                "validate_interrupted_forward_dist",
            ):
                result = VALIDATOR.validate_recovery_audit(
                    summary,
                    "",
                    audit_root,
                    probe_id,
                    interrupted_forward_proof_path="proof.json",
                    expect_interrupted_forward=True,
                    dist=Path("plugin-dist"),
                )
        self.assertEqual("already-recovered", result["result"])
        self.assertTrue(result["proof_consumed"])

    def test_exact_orphaned_recovery_proof_and_audit_pass(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                orphaned_audit(proof_digest),
                str(proof_path.relative_to(repository_root)),
            )
        self.assertEqual(
            {
                "deployment_id": FAILED_ID,
                "proof_consumed": True,
                "result": "recovered",
                "validated": True,
            },
            result,
        )

    def test_exact_durable_receipt_resume_proof_and_audit_pass(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                orphaned_receipt_resume_audit(proof_digest),
                str(proof_path.relative_to(repository_root)),
            )
        self.assertTrue(result["proof_consumed"])
        self.assertEqual("recovered", result["result"])

    def test_exact_orphaned_observation_passes_without_consuming_proof(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                orphaned_observation_audit(proof_digest),
                str(proof_path.relative_to(repository_root)),
                expect_observation=True,
            )
        self.assertEqual(
            {
                "deployment_id": FAILED_ID,
                "proof_consumed": False,
                "proof_observed": True,
                "result": "orphaned-rollback-observed",
                "validated": True,
            },
            result,
        )

    def test_exact_v2_fresh_retry_and_durable_receipt_paths_pass(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_v2_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"
            cases = (
                ("fresh marker", v2_recovery_audit(proof_digest)),
                (
                    "lost response recovered from durable receipt",
                    v2_recovery_audit(proof_digest),
                ),
                (
                    "prior marker retry",
                    v2_recovery_audit(
                        proof_digest,
                        marker_rows_affected=0,
                    ),
                ),
                (
                    "durable receipt resume",
                    v2_recovery_audit(
                        proof_digest,
                        receipt_resume=True,
                    ),
                ),
            )
            cases[1][1]["orphaned_rollback_reconciliation"][
                "response_recovered"
            ] = True
            for label, audit in cases:
                with self.subTest(label=label):
                    result = self.validate(
                        repository_root,
                        audit_root,
                        audit,
                        proof_argument,
                    )
                    self.assertEqual(
                        {
                            "deployment_id": FAILED_ID,
                            "proof_consumed": True,
                            "result": "recovered",
                            "validated": True,
                        },
                        result,
                    )

    def test_v2_proof_binds_exact_raw_attestation_and_rejects_schema_confusion(
        self,
    ) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

            attestation_path = repository_root / envelope["proof"][
                "database_reconciliation"
            ]["attestation_path"]
            attestation_path.write_bytes(attestation_path.read_bytes() + b" ")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "attestation digest does not match",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

    def test_validator_rejects_crossed_candidate_identity_and_float_storage(
        self,
    ) -> None:
        def rehash(envelope: dict[str, object]) -> None:
            envelope["proof_sha256"] = hashlib.sha256(
                json.dumps(
                    envelope["proof"],
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()

        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            envelope["proof"]["failed_run"]["deployment_id"] = (
                "c99-prod-x31171940371x-1"
            )
            rehash(envelope)
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "run identities conflict",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            proof = envelope["proof"]
            proof["failed_run"]["candidate_version"] = proof["prior_run"][
                "version"
            ]
            proof["failed_run"]["candidate_plugin_sha256"] = proof[
                "prior_run"
            ]["plugin_sha256"]
            proof["failed_run"]["candidate_database_fingerprint"] = proof[
                "prior_run"
            ]["database_fingerprint"]
            rehash(envelope)
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "indistinguishable from the prior release",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

        for label, raw_path in (
            (
                "internal parent",
                "docs/recovery-proofs/observations/../observations/"
                "c99-prod-31171940371-1-run-40000000000.json",
            ),
            (
                "case changed prefix",
                "Docs/recovery-proofs/observations/"
                "c99-prod-31171940371-1-run-40000000000.json",
            ),
            (
                "uppercase suffix",
                "docs/recovery-proofs/observations/"
                "c99-prod-31171940371-1-run-40000000000.JSON",
            ),
        ):
            with self.subTest(label=label), tempfile.TemporaryDirectory() as temporary:
                repository_root = Path(temporary)
                proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
                envelope["proof"]["database_reconciliation"][
                    "attestation_path"
                ] = raw_path
                envelope["proof_sha256"] = hashlib.sha256(
                    json.dumps(
                        envelope["proof"],
                        ensure_ascii=False,
                        separators=(",", ":"),
                        sort_keys=True,
                    ).encode("utf-8")
                ).hexdigest()
                proof_path.write_text(json.dumps(envelope), encoding="utf-8")
                with self.assertRaisesRegex(
                    VALIDATOR.AuditValidationError,
                    "escaped its evidence root",
                ):
                    VALIDATOR.load_reviewed_proof(
                        str(proof_path),
                        repository_root,
                    )

        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            envelope["proof"]["database_reconciliation"][
                "transactional_storage"
            ]["tables"] = 3.0
            rehash(envelope)
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "not fully transactional",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            envelope["schema"] = "complete99-orphaned-rollback-proof/v1"
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "unexpected fields",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            envelope["proof"]["database_reconciliation"]["unexpected"] = True
            envelope["proof_sha256"] = hashlib.sha256(
                json.dumps(
                    envelope["proof"],
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "reconciliation fields are invalid",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            envelope["proof"]["database_reconciliation"][
                "attestation_path"
            ] = "docs/recovery-proofs/../outside.json"
            envelope["proof_sha256"] = hashlib.sha256(
                json.dumps(
                    envelope["proof"],
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "escaped its evidence root",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_v2_proof(repository_root)
            reconciliation = envelope["proof"]["database_reconciliation"]
            attestation_path = repository_root / reconciliation["attestation_path"]
            original = attestation_path.read_text(encoding="utf-8")
            duplicate = (
                '{\n  "deployment_id": "' + FAILED_ID + '",' + original[1:]
            )
            attestation_path.write_text(duplicate, encoding="utf-8", newline="\n")
            attestation_digest = hashlib.sha256(
                attestation_path.read_bytes()
            ).hexdigest()
            reconciliation["attestation_sha256"] = attestation_digest
            reconciliation["attestation_audit_sha256"] = attestation_digest
            envelope["proof_sha256"] = hashlib.sha256(
                json.dumps(
                    envelope["proof"],
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "duplicate key",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

    def test_repository_v2_proof_binds_the_exact_production_observation(self) -> None:
        proof_path = (
            ROOT
            / "docs"
            / "recovery-proofs"
            / f"{FAILED_ID}-v2.json"
        )
        _, proof, digest = VALIDATOR.load_reviewed_proof(
            str(proof_path),
            ROOT,
        )
        self.assertEqual(
            "fb5494a81454b6af12f00148ac9524cbc7a1ed35c17972e6de69050e2a4557d1",
            digest,
        )
        reconciliation = proof["database_reconciliation"]
        self.assertEqual(
            "5f35544ae1ae7c49b0e3c9675b8f19d57e9bb2a7da0e19762b8178c597983dab",
            reconciliation["observed_database_fingerprint"],
        )
        self.assertEqual(
            "97dcbcde203aa3f7d1ac849c9f0136bdfc88d3c59bec1290219e0e840a591d1c",
            reconciliation["expected_reconciled_database_fingerprint"],
        )
        self.assertEqual(
            "db93ccabda28b2848161d445e35b8010de18c89f3764b07b5434e76ffce6351f",
            reconciliation["attestation_sha256"],
        )

    def test_validator_rejects_attestation_parent_symlink(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, _ = write_reviewed_v2_proof(repository_root)
            observation_dir = (
                repository_root
                / "docs"
                / "recovery-proofs"
                / "observations"
            )
            original_is_symlink = Path.is_symlink
            for symlink_component in (
                repository_root / "docs",
                observation_dir,
            ):
                with self.subTest(component=symlink_component.name):
                    def is_symlink(candidate: Path) -> bool:
                        return (
                            candidate == symlink_component
                            or original_is_symlink(candidate)
                        )

                    with mock.patch.object(
                        Path,
                        "is_symlink",
                        autospec=True,
                        side_effect=is_symlink,
                    ), self.assertRaisesRegex(
                        VALIDATOR.AuditValidationError,
                        "escaped its evidence root",
                    ):
                        VALIDATOR.load_reviewed_proof(
                            str(proof_path),
                            repository_root,
                        )

    def test_v2_audit_rejects_baseline_substitution_crossed_state_and_extras(
        self,
    ) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_v2_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"
            cases: list[tuple[str, dict[str, object], str]] = []

            baseline_receipt = v2_recovery_audit(
                proof_digest,
                receipt_resume=True,
            )
            baseline_receipt["initial_orphaned_rollback_receipt"][
                "committed_expected_database_fingerprint"
            ] = DATABASE_SHA
            cases.append(("baseline receipt", baseline_receipt, "failed for"))

            changed_candidate_receipt = v2_recovery_audit(
                proof_digest,
                receipt_resume=True,
            )
            changed_candidate_receipt["initial_orphaned_rollback_receipt"][
                "post_install_database_fingerprint"
            ] = "0" * 64
            cases.append(
                (
                    "changed candidate receipt",
                    changed_candidate_receipt,
                    "V2 durable receipt failed for post_install_database_fingerprint",
                )
            )

            crossed = v2_recovery_audit(proof_digest)
            crossed["initial_status"]["database_fingerprint"] = (
                RECONCILED_DATABASE_SHA
            )
            cases.append(("crossed marker state", crossed, "authorized marker state"))

            wrong_manifest = v2_recovery_audit(proof_digest)
            wrong_manifest["reconciled_status"][
                "database_manifest_sha256"
            ] = "0" * 64
            cases.append(("changed manifest", wrong_manifest, "durable receipt"))

            wrong_fresh_candidate = v2_recovery_audit(proof_digest)
            wrong_fresh_candidate["reconciled_status"]["expected_sha256"] = (
                "0" * 64
            )
            cases.append(
                (
                    "changed fresh candidate identity",
                    wrong_fresh_candidate,
                    "V2 reconciled status differs from its durable receipt",
                )
            )

            bool_int_alias = v2_recovery_audit(proof_digest)
            bool_int_alias["reconciled_status"]["lock_owned"] = 1
            cases.append(
                (
                    "boolean integer alias",
                    bool_int_alias,
                    "Reconciled lock was not retained",
                )
            )

            receipt_bool_int_alias = v2_recovery_audit(
                proof_digest,
                receipt_resume=True,
            )
            receipt_bool_int_alias["initial_orphaned_rollback_receipt"][
                "committed_expected_active"
            ] = 1
            cases.append(
                (
                    "receipt boolean integer alias",
                    receipt_bool_int_alias,
                    "Initial receipt failed for committed_expected_active",
                )
            )

            wrong_schema = v2_recovery_audit(proof_digest)
            wrong_schema["orphaned_rollback_reconciliation"][
                "receipt_schema"
            ] = "complete99-orphaned-rollback-receipt/v1"
            cases.append(("receipt schema confusion", wrong_schema, "reviewed proof"))

            reversed_timestamps = v2_recovery_audit(proof_digest)
            reversed_timestamps["started_at"] = "2026-08-07T14:00:02Z"
            cases.append(
                (
                    "reversed recovery timestamps",
                    reversed_timestamps,
                    "finished before it started",
                )
            )

            extra = v2_recovery_audit(proof_digest)
            extra["unexpected"] = True
            cases.append(("extra audit field", extra, "unexpected or missing fields"))

            extra_receipt = v2_recovery_audit(proof_digest)
            extra_receipt["orphaned_rollback_reconciliation"][
                "unexpected"
            ] = True
            cases.append(
                ("extra receipt field", extra_receipt, "audit fields are invalid")
            )

            coerced_response = v2_recovery_audit(proof_digest)
            coerced_response["orphaned_rollback_reconciliation"][
                "response_recovered"
            ] = "true"
            cases.append(
                (
                    "coerced lost response flag",
                    coerced_response,
                    "marker transition is invalid",
                )
            )

            changed_pre_finalize_identity = v2_recovery_audit(proof_digest)
            changed_pre_finalize_identity["pre_finalize_orphaned_identity"][
                "current_plugin_sha256"
            ] = "0" * 64
            cases.append(
                (
                    "changed pre-finalize identity",
                    changed_pre_finalize_identity,
                    "pre-finalize identity differs from the reviewed release",
                )
            )

            both = v2_recovery_audit(proof_digest)
            both["initial_orphaned_rollback_receipt"] = v2_recovery_audit(
                proof_digest,
                receipt_resume=True,
            )["initial_orphaned_rollback_receipt"]
            cases.append(("ambiguous receipt paths", both, "terminal receipt path"))

            for label, audit, error in cases:
                with self.subTest(label=label), self.assertRaisesRegex(
                    VALIDATOR.AuditValidationError,
                    error,
                ):
                    self.validate(
                        repository_root,
                        audit_root,
                        audit,
                        proof_argument,
                    )

    def test_observation_rejects_manifest_tampering_and_mutation_fields(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"

            tampered = orphaned_observation_audit(proof_digest)
            tampered["orphaned_rollback_observation"]["database_manifest"][
                "posts_count"
            ] = 99
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "digest does not match",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    tampered,
                    proof_argument,
                    expect_observation=True,
                )

            mutated = orphaned_observation_audit(proof_digest)
            mutated["finalize"] = finalize_record()
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "unexpected or missing fields",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    mutated,
                    proof_argument,
                    expect_observation=True,
                )

            false_comparison = orphaned_observation_audit(proof_digest)
            false_comparison["orphaned_rollback_observation"][
                "historical_baseline_matches_projection"
            ] = True
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "does not match its fingerprints",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    false_comparison,
                    proof_argument,
                    expect_observation=True,
                )

            extra_field = orphaned_observation_audit(proof_digest)
            extra_field["rollback"] = {"raw": "must-not-be-certified"}
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "unexpected or missing fields",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    extra_field,
                    proof_argument,
                    expect_observation=True,
                )

            local = orphaned_observation_audit(proof_digest)
            local["local_test"] = True
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "not production-only",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    local,
                    proof_argument,
                    expect_observation=True,
                )

            nontransactional = orphaned_observation_audit(proof_digest)
            nontransactional["orphaned_rollback_observation"][
                "database_storage"
            ]["engine"] = "MYISAM"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "not fully transactional",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    nontransactional,
                    proof_argument,
                    expect_observation=True,
                )

    def test_orphaned_proof_requires_exactly_one_consumption_shape(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"

            both = orphaned_audit(proof_digest)
            both["initial_orphaned_rollback_receipt"] = (
                orphaned_receipt_resume_audit(proof_digest)[
                    "initial_orphaned_rollback_receipt"
                ]
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "exactly one orphaned rollback consumption path",
            ):
                self.validate(repository_root, audit_root, both, proof_argument)

            neither = orphaned_audit(proof_digest)
            del neither["orphaned_rollback_reconciliation"]
            del neither["reconciled_status"]
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "exactly one orphaned rollback consumption path",
            ):
                self.validate(repository_root, audit_root, neither, proof_argument)

    def test_proof_rejects_wrong_stage_decision_and_cleanup(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"
            cases: list[tuple[str, Callable[[dict[str, object]], None]]] = [
                (
                    "wrong decision",
                    lambda value: value.__setitem__("decision", "already_finalized"),
                ),
                (
                    "wrong orphan phase",
                    lambda value: value["initial_status"].__setitem__(
                        "phase", "committed"
                    ),
                ),
                (
                    "state unexpectedly exists",
                    lambda value: value["initial_status"].__setitem__(
                        "state_exists", True
                    ),
                ),
                (
                    "reconciliation lost lock",
                    lambda value: value["orphaned_rollback_reconciliation"].__setitem__(
                        "lock_retained", False
                    ),
                ),
                (
                    "finalization retained lock",
                    lambda value: value["finalize"].__setitem__(
                        "lock_released", False
                    ),
                ),
                (
                    "snippet row remained",
                    lambda value: value["cleanup"].__setitem__(
                        "row_absence_verified", False
                    ),
                ),
            ]
            for label, mutate in cases:
                with self.subTest(label=label):
                    audit = copy.deepcopy(orphaned_audit(proof_digest))
                    mutate(audit)
                    with self.assertRaises(VALIDATOR.AuditValidationError):
                        self.validate(
                            repository_root,
                            audit_root,
                            audit,
                            proof_argument,
                        )

    def test_supplied_proof_rejects_no_owner_instead_of_succeeding(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, _ = write_reviewed_proof(repository_root)
            audit = {
                "deployment_id": PROBE_ID,
                "discovery": {
                    "probe_id": PROBE_ID,
                    "result": "no-owner",
                    "finalize": finalize_record(),
                    "cleanup": cleanup_record(),
                },
                "result": "no-recovery-needed",
            }
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "exact failed deployment was not recovered",
            ):
                self.validate(
                    repository_root,
                    repository_root / "recovery-audit",
                    audit,
                    str(proof_path.relative_to(repository_root)),
                )

    def test_no_proof_accepts_only_exact_no_owner_probe_with_full_cleanup(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            audit = {
                "deployment_id": PROBE_ID,
                "discovery": {
                    "probe_id": PROBE_ID,
                    "result": "no-owner",
                    "finalize": finalize_record(),
                    "cleanup": cleanup_record(),
                },
                "result": "no-recovery-needed",
            }
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                audit,
                "",
            )
            self.assertFalse(result["proof_consumed"])

            audit["discovery"]["cleanup"]["route_404"] = False
            with self.assertRaises(VALIDATOR.AuditValidationError):
                self.validate(
                    repository_root,
                    repository_root / "recovery-audit",
                    audit,
                    "",
                )

    def test_tampered_or_escaped_proof_and_wrong_audit_path_fail(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, proof_digest = write_reviewed_proof(repository_root)
            audit_root = repository_root / "recovery-audit"
            audit = orphaned_audit(proof_digest)
            proof_argument = str(proof_path.relative_to(repository_root))

            escaped = repository_root / "outside.json"
            escaped.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "under docs/recovery-proofs",
            ):
                self.validate(repository_root, audit_root, audit, str(escaped))

            envelope["proof"]["prior_run"]["version"] = "1.15.0"
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "digest does not match",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    audit,
                    proof_argument,
                )

            _, _, proof_digest = write_reviewed_proof(repository_root)
            audit = orphaned_audit(proof_digest)
            audit_path = write_audit(audit_root, audit)
            wrong_path = repository_root / "other" / audit_path.name
            wrong_path.parent.mkdir()
            wrong_path.write_text(audit_path.read_text(encoding="utf-8"), encoding="utf-8")
            summary = json.dumps(
                {
                    "audit": str(wrong_path.resolve()),
                    "deployment_id": FAILED_ID,
                    "result": "recovered",
                }
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "wrong audit file",
            ):
                VALIDATOR.validate_recovery_audit(
                    summary,
                    proof_argument,
                    audit_root,
                    PROBE_ID,
                    repository_root=repository_root,
                )

    def test_proof_schema_types_and_duplicate_keys_are_strict(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_proof(repository_root)

            duplicate = (
                '{"schema":"complete99-orphaned-rollback-proof/v1",'
                '"schema":"complete99-orphaned-rollback-proof/v1",'
                f'"proof":{json.dumps(envelope["proof"])},'
                f'"proof_sha256":"{envelope["proof_sha256"]}"}}'
            )
            proof_path.write_text(duplicate, encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "duplicate key",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

            cases: list[tuple[str, Callable[[dict[str, object]], None]]] = [
                (
                    "boolean run ID",
                    lambda value: value["proof"]["failed_run"].__setitem__(
                        "run_id", True
                    ),
                ),
                (
                    "numeric digest",
                    lambda value: value["proof"]["prior_run"].__setitem__(
                        "plugin_sha256", 123
                    ),
                ),
                (
                    "unexpected identity field",
                    lambda value: value["proof"]["prior_run"].__setitem__(
                        "unexpected", "field"
                    ),
                ),
            ]
            for label, mutate in cases:
                with self.subTest(label=label):
                    strict_envelope = copy.deepcopy(envelope)
                    mutate(strict_envelope)
                    canonical = json.dumps(
                        strict_envelope["proof"],
                        ensure_ascii=False,
                        separators=(",", ":"),
                        sort_keys=True,
                    ).encode("utf-8")
                    strict_envelope["proof_sha256"] = hashlib.sha256(
                        canonical
                    ).hexdigest()
                    proof_path.write_text(
                        json.dumps(strict_envelope),
                        encoding="utf-8",
                    )
                    with self.assertRaises(VALIDATOR.AuditValidationError):
                        VALIDATOR.load_reviewed_proof(
                            str(proof_path),
                            repository_root,
                        )

    def test_exact_stage_matrix_validates_success_and_recovered_failure(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            deploy_audits = root / "deploy-audit"
            recovery_audits = root / "recovery-audit"
            commerce_audits = root / "commerce-audit"
            dry_id = "c99-dry-40000000000-1"
            production_id = "c99-prod-40000000000-1"
            commerce_id = "c99-commerce-40000000000-1"
            write_audit(
                deploy_audits,
                {
                    "deployment_id": dry_id,
                    "result": "dry-run-passed",
                    "cleanup": cleanup_record(),
                },
            )
            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "deployed",
                    "cleanup": cleanup_record(),
                },
            )
            write_audit(
                commerce_audits,
                {
                    "deployment_id": commerce_id,
                    "result": "verified",
                    "cleanup": cleanup_record(),
                },
            )
            success = VALIDATOR.validate_stage_outcomes(
                preflight_outcome="success",
                production_outcome="success",
                mutation_outcome="skipped",
                mutation_started="",
                recovery_outcome="skipped",
                commerce_outcome="success",
                commerce_recovery_outcome="skipped",
                dry_run_id=dry_id,
                production_id=production_id,
                commerce_id=commerce_id,
                deploy_audit_root=deploy_audits,
                recovery_audit_root=recovery_audits,
                commerce_audit_root=commerce_audits,
            )
            self.assertTrue(success["validated"])

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "failed",
                },
            )
            write_audit(
                recovery_audits,
                {
                    "deployment_id": production_id,
                    "decision": "rollback_interrupted_mutation",
                    "result": "recovered",
                    "cleanup": cleanup_record(),
                },
            )
            (commerce_audits / f"{commerce_id}.json").unlink()
            recovered = VALIDATOR.validate_stage_outcomes(
                preflight_outcome="success",
                production_outcome="failure",
                mutation_outcome="success",
                mutation_started="true",
                recovery_outcome="success",
                commerce_outcome="skipped",
                commerce_recovery_outcome="skipped",
                dry_run_id=dry_id,
                production_id=production_id,
                commerce_id=commerce_id,
                deploy_audit_root=deploy_audits,
                recovery_audit_root=recovery_audits,
                commerce_audit_root=commerce_audits,
            )
            self.assertTrue(recovered["mutation_started"])

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "deployed",
                    "cleanup": cleanup_record(),
                },
            )
            (recovery_audits / f"{production_id}.json").unlink()
            write_audit(
                commerce_audits,
                {
                    "deployment_id": commerce_id,
                    "result": "failed",
                },
            )
            commerce_recovery = {
                "audit_id": f"{commerce_id}-recovery",
                "deployment_id": commerce_id,
                "result": "verified",
                "cleanup": cleanup_record(),
            }
            commerce_recovery_path = commerce_audits / f"{commerce_id}-recovery.json"
            commerce_recovery_path.write_text(
                json.dumps(commerce_recovery),
                encoding="utf-8",
            )
            commerce_recovered = VALIDATOR.validate_stage_outcomes(
                preflight_outcome="success",
                production_outcome="success",
                mutation_outcome="skipped",
                mutation_started="",
                recovery_outcome="skipped",
                commerce_outcome="failure",
                commerce_recovery_outcome="success",
                dry_run_id=dry_id,
                production_id=production_id,
                commerce_id=commerce_id,
                deploy_audit_root=deploy_audits,
                recovery_audit_root=recovery_audits,
                commerce_audit_root=commerce_audits,
            )
            self.assertEqual("success", commerce_recovered["commerce_recovery_outcome"])

    def test_exact_stage_matrix_rejects_missing_or_impossible_audits(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            deploy_audits = root / "deploy-audit"
            recovery_audits = root / "recovery-audit"
            commerce_audits = root / "commerce-audit"
            dry_id = "c99-dry-40000000000-1"
            production_id = "c99-prod-40000000000-1"
            commerce_id = "c99-commerce-40000000000-1"
            write_audit(
                deploy_audits,
                {
                    "deployment_id": dry_id,
                    "result": "dry-run-passed",
                    "cleanup": cleanup_record(),
                },
            )
            base = {
                "preflight_outcome": "success",
                "production_outcome": "success",
                "mutation_outcome": "skipped",
                "mutation_started": "",
                "recovery_outcome": "skipped",
                "commerce_outcome": "success",
                "commerce_recovery_outcome": "skipped",
                "dry_run_id": dry_id,
                "production_id": production_id,
                "commerce_id": commerce_id,
                "deploy_audit_root": deploy_audits,
                "recovery_audit_root": recovery_audits,
                "commerce_audit_root": commerce_audits,
            }
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "production audit is missing",
            ):
                VALIDATOR.validate_stage_outcomes(**base)

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "deployed",
                    "cleanup": cleanup_record(),
                },
            )
            write_audit(
                commerce_audits,
                {
                    "deployment_id": commerce_id,
                    "result": "verified",
                    "cleanup": cleanup_record(),
                },
            )
            impossible = dict(base)
            impossible["recovery_outcome"] = "success"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "Recovery ran after successful production",
            ):
                VALIDATOR.validate_stage_outcomes(**impossible)

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "failed",
                },
            )
            missing_recovery = dict(base)
            missing_recovery.update(
                {
                    "production_outcome": "failure",
                    "mutation_outcome": "success",
                    "mutation_started": "true",
                    "recovery_outcome": "success",
                    "commerce_outcome": "skipped",
                }
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "production recovery audit is missing",
            ):
                VALIDATOR.validate_stage_outcomes(**missing_recovery)

    def test_observation_stage_matrix_requires_every_mutation_stage_skipped(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            arguments = {
                "observation_only": True,
                "preflight_outcome": "success",
                "production_outcome": "skipped",
                "mutation_outcome": "skipped",
                "mutation_started": "",
                "recovery_outcome": "skipped",
                "commerce_outcome": "skipped",
                "commerce_recovery_outcome": "skipped",
                "dry_run_id": "c99-dry-40000000000-1",
                "production_id": "c99-prod-40000000000-1",
                "commerce_id": "c99-commerce-40000000000-1",
                "deploy_audit_root": root / "deploy-audit",
                "recovery_audit_root": root / "recovery-audit",
                "commerce_audit_root": root / "commerce-audit",
            }
            result = VALIDATOR.validate_stage_outcomes(**arguments)
            self.assertTrue(result["observation_only"])

            crossed = dict(arguments)
            crossed["production_outcome"] = "success"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "crossed the platform mutation path",
            ):
                VALIDATOR.validate_stage_outcomes(**crossed)

            for field in (
                "production_outcome",
                "mutation_outcome",
                "recovery_outcome",
                "commerce_outcome",
                "commerce_recovery_outcome",
            ):
                with self.subTest(cancelled_field=field):
                    cancelled = dict(arguments)
                    cancelled[field] = "cancelled"
                    with self.assertRaisesRegex(
                        VALIDATOR.AuditValidationError,
                        "crossed the platform mutation path",
                    ):
                        VALIDATOR.validate_stage_outcomes(**cancelled)

            marker = dict(arguments)
            marker["mutation_started"] = "false"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "crossed the platform mutation path",
            ):
                VALIDATOR.validate_stage_outcomes(**marker)

    def test_recovery_only_stage_matrix_runs_dry_run_and_commerce_without_redeploy(
        self,
    ) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            deploy_audits = root / "deploy-audit"
            recovery_audits = root / "recovery-audit"
            commerce_audits = root / "commerce-audit"
            dry_id = "c99-dry-40000000001-1"
            production_id = "c99-prod-40000000001-1"
            commerce_id = "c99-commerce-40000000001-1"
            write_audit(
                deploy_audits,
                {
                    "deployment_id": dry_id,
                    "result": "dry-run-passed",
                    "cleanup": cleanup_record(),
                },
            )
            write_audit(
                commerce_audits,
                {
                    "deployment_id": commerce_id,
                    "result": "verified",
                    "cleanup": cleanup_record(),
                },
            )
            arguments = {
                "recovery_only": True,
                "platform_recovered": True,
                "preflight_outcome": "success",
                "production_outcome": "skipped",
                "mutation_outcome": "skipped",
                "mutation_started": "",
                "recovery_outcome": "skipped",
                "commerce_outcome": "success",
                "commerce_recovery_outcome": "skipped",
                "dry_run_id": dry_id,
                "production_id": production_id,
                "commerce_id": commerce_id,
                "deploy_audit_root": deploy_audits,
                "recovery_audit_root": recovery_audits,
                "commerce_audit_root": commerce_audits,
            }

            result = VALIDATOR.validate_stage_outcomes(**arguments)
            self.assertTrue(result["recovery_only"])
            self.assertTrue(result["platform_recovered"])
            self.assertEqual("success", result["commerce_outcome"])

            for field, value, message in (
                ("platform_recovered", False, "did not prove"),
                ("production_outcome", "success", "crossed the new platform"),
                ("mutation_started", "false", "crossed the new platform"),
            ):
                with self.subTest(field=field):
                    invalid = dict(arguments)
                    invalid[field] = value
                    with self.assertRaisesRegex(
                        VALIDATOR.AuditValidationError,
                        message,
                    ):
                        VALIDATOR.validate_stage_outcomes(**invalid)

            conflicting = dict(arguments)
            conflicting["observation_only"] = True
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "modes conflict",
            ):
                VALIDATOR.validate_stage_outcomes(**conflicting)


if __name__ == "__main__":
    unittest.main()
