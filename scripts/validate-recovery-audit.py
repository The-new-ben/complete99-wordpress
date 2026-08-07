#!/usr/bin/env python3
"""Fail closed unless one preflight recovery audit proves its exact outcome."""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
DIGEST = re.compile(r"[a-f0-9]{64}")
COMMIT = re.compile(r"[a-f0-9]{40}")
VERSION = re.compile(r"[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?")
DEPLOYMENT_ID = re.compile(r"c99-[A-Za-z0-9._-]{4,92}")
UTC_TIMESTAMP = re.compile(r"[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z")
GENERIC_RECOVERY_DECISIONS = {
    "already_finalized",
    "finish_committed_cleanup",
    "finish_stabilized_forward_cleanup",
    "release_unstarted_lock",
    "rollback_failed_forward_stabilization",
    "rollback_interrupted_mutation",
    "stabilize_completed_forward_migration",
}


class AuditValidationError(RuntimeError):
    """A recovery summary, proof, or audit did not satisfy the release contract."""


def require(condition: bool, message: str) -> None:
    if not condition:
        raise AuditValidationError(message)


def reject_duplicate_keys(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
    result: dict[str, Any] = {}
    for key, value in pairs:
        if key in result:
            raise AuditValidationError(f"JSON contains duplicate key: {key}")
        result[key] = value
    return result


def parse_json(raw: str, label: str) -> dict[str, Any]:
    try:
        value = json.loads(raw, object_pairs_hook=reject_duplicate_keys)
    except (json.JSONDecodeError, UnicodeDecodeError) as error:
        raise AuditValidationError(f"{label} is not valid JSON") from error
    require(isinstance(value, dict), f"{label} must be a JSON object")
    return value


def read_json(path: Path, label: str) -> dict[str, Any]:
    try:
        raw = path.read_text(encoding="utf-8")
    except (OSError, UnicodeDecodeError) as error:
        raise AuditValidationError(f"{label} could not be read") from error
    return parse_json(raw, label)


def require_mapping(value: Any, label: str) -> dict[str, Any]:
    require(isinstance(value, dict), f"{label} must be an object")
    return value


def require_digest(value: Any, label: str) -> str:
    require(type(value) is str, f"{label} must be a SHA-256 string")
    require(DIGEST.fullmatch(value) is not None, f"{label} must be a SHA-256 digest")
    return value


def validate_cleanup(value: Any, label: str) -> None:
    cleanup = require_mapping(value, label)
    required = {
        "snippet_deleted": True,
        "snippet_active": False,
        "row_absence_verified": True,
        "route_404": True,
    }
    for key, expected in required.items():
        require(cleanup.get(key) is expected, f"{label} failed for {key}")


def validate_observation_cleanup(value: Any, label: str) -> None:
    cleanup = require_mapping(value, label)
    require(
        set(cleanup)
        == {
            "removed_ids",
            "route_404",
            "row_absence_verified",
            "snippet_active",
            "snippet_deleted",
        },
        f"{label} fields are invalid",
    )
    validate_cleanup(cleanup, label)
    removed_ids = cleanup.get("removed_ids")
    require(
        isinstance(removed_ids, list)
        and all(type(value) is int and value > 0 for value in removed_ids)
        and removed_ids == sorted(set(removed_ids)),
        f"{label} removed IDs are invalid",
    )


def validate_bootstrap_cleanup(value: Any, label: str) -> None:
    cleanup = require_mapping(value, label)
    require(
        set(cleanup)
        == {
            "exact_name",
            "known_id",
            "known_id_matched",
            "removed_ids",
            "row_absence_verified",
        },
        f"{label} fields are invalid",
    )
    require(
        cleanup.get("exact_name") == "c99-deploy-bootstrap"
        and cleanup.get("known_id") == 5
        and type(cleanup.get("known_id_matched")) is bool
        and cleanup.get("row_absence_verified") is True,
        f"{label} identity is invalid",
    )
    removed_ids = cleanup.get("removed_ids")
    require(
        isinstance(removed_ids, list)
        and all(type(value) is int and value > 0 for value in removed_ids)
        and removed_ids == sorted(set(removed_ids)),
        f"{label} removed IDs are invalid",
    )


def validate_finalize(value: Any, label: str) -> None:
    finalize = require_mapping(value, label)
    for key in ("finalized", "lock_released", "state_removed"):
        require(finalize.get(key) is True, f"{label} failed for {key}")


def load_reviewed_proof(
    raw_path: str,
    repository_root: Path,
) -> tuple[Path, dict[str, Any], str]:
    candidate = Path(raw_path)
    path = (
        (repository_root / candidate).resolve()
        if not candidate.is_absolute()
        else candidate.resolve()
    )
    proof_root = (repository_root / "docs" / "recovery-proofs").resolve()
    require(
        proof_root in path.parents and path.suffix.lower() == ".json",
        "Recovery proof must be a JSON file under docs/recovery-proofs",
    )
    envelope = read_json(path, "Recovery proof")
    require(
        set(envelope) == {"schema", "proof", "proof_sha256"}
        and type(envelope.get("schema")) is str
        and envelope.get("schema") == "complete99-orphaned-rollback-proof/v1",
        "Recovery proof schema is invalid",
    )
    proof = require_mapping(envelope.get("proof"), "Recovery proof payload")
    failed = require_mapping(proof.get("failed_run"), "Failed-run proof")
    prior = require_mapping(proof.get("prior_run"), "Prior-run proof")
    failed_keys = {
        "artifact_sha256",
        "audit_sha256",
        "candidate_database_fingerprint",
        "candidate_plugin_sha256",
        "candidate_version",
        "commit",
        "deployment_id",
        "run_id",
    }
    prior_keys = {
        "active",
        "audit_sha256",
        "commit",
        "database_fingerprint",
        "database_version",
        "deployment_id",
        "plugin_sha256",
        "robots_exists",
        "robots_sha256",
        "run_id",
        "sync_configured",
        "version",
    }
    require(
        set(proof) == {"failed_run", "prior_run"}
        and set(failed) == failed_keys
        and set(prior) == prior_keys,
        "Recovery proof identities are missing or contain unexpected fields",
    )
    canonical = json.dumps(
        proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    proof_sha256 = hashlib.sha256(canonical).hexdigest()
    require(
        type(envelope.get("proof_sha256")) is str
        and envelope.get("proof_sha256") == proof_sha256,
        "Recovery proof digest does not match",
    )
    for record, label in ((failed, "Failed-run"), (prior, "Prior-run")):
        require(
            type(record.get("run_id")) is int and record["run_id"] > 0,
            f"{label} run ID is invalid",
        )
        require(
            type(record.get("commit")) is str
            and COMMIT.fullmatch(record["commit"]) is not None,
            f"{label} commit is invalid",
        )
        require_digest(record.get("audit_sha256"), f"{label} audit")
        require(
            type(record.get("deployment_id")) is str
            and DEPLOYMENT_ID.fullmatch(record["deployment_id"]) is not None,
            f"{label} deployment ID is invalid",
        )
    require(
        failed["deployment_id"] != prior["deployment_id"]
        and failed["run_id"] != prior["run_id"]
        and str(failed["run_id"]) in failed["deployment_id"]
        and str(prior["run_id"]) in prior["deployment_id"],
        "Recovery proof run identities conflict",
    )
    require_digest(failed.get("artifact_sha256"), "Failed artifact")
    require_digest(failed.get("candidate_plugin_sha256"), "Failed candidate plugin")
    require_digest(
        failed.get("candidate_database_fingerprint"),
        "Failed candidate database fingerprint",
    )
    require(
        type(failed.get("candidate_version")) is str
        and VERSION.fullmatch(failed["candidate_version"]) is not None,
        "Failed candidate version is invalid",
    )
    require(
        type(prior.get("version")) is str
        and VERSION.fullmatch(prior["version"]) is not None
        and type(prior.get("database_version")) is str
        and prior["database_version"] == prior["version"],
        "Prior version identity is invalid",
    )
    require(prior.get("active") is True, "Prior plugin was not active")
    require(prior.get("robots_exists") is True, "Prior robots.txt did not exist")
    require(prior.get("sync_configured") is True, "Prior sync was not configured")
    require_digest(prior.get("plugin_sha256"), "Prior plugin")
    require_digest(prior.get("database_fingerprint"), "Prior database fingerprint")
    require_digest(prior.get("robots_sha256"), "Prior robots.txt")
    return path, proof, proof_sha256


def validate_database_manifest(value: Any, digest: Any, label: str) -> None:
    manifest = require_mapping(value, label)
    components = (
        "options_without_deployment_marker",
        "posts",
        "postmeta",
        "seed_ids",
        "evaluation_ids",
    )
    expected_keys = {
        "schema",
        "sync_secret_existed",
        "sync_secret_configured",
    }
    for component in components:
        expected_keys.add(f"{component}_count")
        expected_keys.add(f"{component}_sha256")
    require(set(manifest) == expected_keys, f"{label} fields are invalid")
    require(
        manifest.get("schema") == "complete99-database-snapshot-manifest/v1",
        f"{label} schema is invalid",
    )
    require(
        manifest.get("sync_secret_existed") is True
        and manifest.get("sync_secret_configured") is True,
        f"{label} sync identity is invalid",
    )
    for component in components:
        count = manifest.get(f"{component}_count")
        require(
            type(count) is int and count >= 0,
            f"{label} count is invalid for {component}",
        )
        require_digest(
            manifest.get(f"{component}_sha256"),
            f"{label} {component}",
        )
    canonical = json.dumps(
        manifest,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    require(
        require_digest(digest, f"{label} digest")
        == hashlib.sha256(canonical).hexdigest(),
        f"{label} digest does not match",
    )


def validate_database_storage(value: Any, label: str) -> None:
    storage = require_mapping(value, label)
    require(set(storage) == {"engine", "tables"}, f"{label} fields are invalid")
    require(
        storage.get("engine") in {"INNODB", "XTRADB", "INNODB,XTRADB"}
        and storage.get("tables") == 3,
        f"{label} is not fully transactional",
    )


def validate_orphaned_observation_audit(
    audit: dict[str, Any],
    proof_path: Path,
    proof: dict[str, Any],
    proof_sha256: str,
    repository_root: Path,
    expected_probe_id: str,
) -> None:
    failed = require_mapping(proof.get("failed_run"), "Failed-run proof")
    prior = require_mapping(proof.get("prior_run"), "Prior-run proof")
    expected_deployment_id = failed.get("deployment_id")
    require(
        audit.get("deployment_id") == expected_deployment_id,
        "Observation audit does not belong to the proof's failed deployment",
    )
    require(
        set(audit)
        == {
            "bootstrap_cleanup",
            "bridge_site_identity",
            "cleanup",
            "decision",
            "deployment_id",
            "discovery",
            "finished_at",
            "identity",
            "initial_status",
            "local_test",
            "orphaned_rollback_observation",
            "orphaned_rollback_proof",
            "result",
            "started_at",
        },
        "Observation audit contains unexpected or missing fields",
    )
    require(audit.get("local_test") is False, "Observation audit is not production-only")
    for key in ("started_at", "finished_at"):
        value = audit.get(key)
        require(
            type(value) is str and UTC_TIMESTAMP.fullmatch(value) is not None,
            f"Observation audit {key} is invalid",
        )
    identity = require_mapping(audit.get("identity"), "Observation identity")
    require(
        set(identity) == {"id", "roles", "site_identity"}
        and type(identity.get("id")) is int
        and identity.get("id") > 0
        and isinstance(identity.get("roles"), list)
        and all(type(role) is str and role for role in identity["roles"]),
        "Observation authentication identity is invalid",
    )
    rest_identity = require_mapping(
        identity.get("site_identity"),
        "Observation REST identity",
    )
    require(
        set(rest_identity) == {"home", "url"}
        and all(
            type(rest_identity.get(key)) is str and rest_identity.get(key)
            for key in ("home", "url")
        ),
        "Observation REST identity is invalid",
    )
    bridge_identity = require_mapping(
        audit.get("bridge_site_identity"),
        "Observation bridge identity",
    )
    require(
        set(bridge_identity) == {"home_host", "rest_host", "siteurl_host"}
        and all(
            type(bridge_identity.get(key)) is str and bridge_identity.get(key)
            for key in ("home_host", "rest_host", "siteurl_host")
        ),
        "Observation bridge identity is invalid",
    )
    validate_bootstrap_cleanup(
        audit.get("bootstrap_cleanup"),
        "Observation bootstrap cleanup",
    )
    require(
        audit.get("result") == "orphaned-rollback-observed"
        and audit.get("decision") == "observe_orphaned_rollback",
        "Observation audit used the wrong terminal path",
    )
    require(
        "orphaned_rollback_reconciliation" not in audit
        and "reconciled_status" not in audit
        and "finalize" not in audit,
        "Observation audit crossed a recovery mutation path",
    )
    discovery = require_mapping(audit.get("discovery"), "Observation discovery")
    require(
        set(discovery)
        == {
            "bootstrap_cleanup",
            "cleanup",
            "owner_deployment_id",
            "owner_phase",
            "probe_id",
            "result",
        },
        "Observation discovery fields are invalid",
    )
    require(
        discovery.get("result") == "owner-discovered"
        and discovery.get("owner_deployment_id") == expected_deployment_id,
        "Observation did not find the proof's exact lock owner",
    )
    require(
        discovery.get("probe_id") == expected_probe_id
        and discovery.get("owner_phase") == "rolling_back",
        "Observation discovery identity is invalid",
    )
    validate_bootstrap_cleanup(
        discovery.get("bootstrap_cleanup"),
        "Observation discovery bootstrap cleanup",
    )
    validate_observation_cleanup(
        discovery.get("cleanup"),
        "Observation discovery cleanup",
    )
    expected_path = proof_path.relative_to(repository_root.resolve()).as_posix()
    proof_record = require_mapping(
        audit.get("orphaned_rollback_proof"),
        "Observation proof audit",
    )
    require(
        set(proof_record) == {"path", "proof_sha256"},
        "Observation proof audit fields are invalid",
    )
    require(proof_record.get("path") == expected_path, "Observation proof path changed")
    require(
        proof_record.get("proof_sha256") == proof_sha256,
        "Observation used a different proof digest",
    )
    initial_status = require_mapping(audit.get("initial_status"), "Observation status")
    require(
        set(initial_status)
        == {
            "database_fingerprint",
            "database_manifest_sha256",
            "database_storage",
            "lock_owned",
            "phase",
            "process_lock_available",
            "projected_database_fingerprint",
            "projected_deployment_id",
            "recovery_ready",
            "state_exists",
        },
        "Observation status fields are invalid",
    )
    require(
        initial_status.get("phase") == "rolling_back"
        and initial_status.get("state_exists") is False
        and initial_status.get("lock_owned") is True
        and initial_status.get("recovery_ready") is True
        and initial_status.get("process_lock_available") is True,
        "Observation did not inspect the exact stale rolling_back state",
    )
    observation = require_mapping(
        audit.get("orphaned_rollback_observation"),
        "Orphaned rollback observation",
    )
    expected_observation_keys = {
        "schema",
        "deployment_id",
        "proof_sha256",
        "phase",
        "state_exists",
        "lock_owned",
        "recovery_ready",
        "process_lock_available",
        "current_version",
        "current_database_version",
        "current_active",
        "current_plugin_sha256",
        "current_deployment",
        "current_database_fingerprint",
        "projected_deployment_id",
        "projected_database_fingerprint",
        "historical_baseline_database_fingerprint",
        "historical_baseline_matches_projection",
        "current_sync_configured",
        "current_robots_sha256",
        "database_manifest",
        "database_manifest_sha256",
        "database_storage",
        "failed_candidate_database_fingerprint",
    }
    require(
        set(observation) == expected_observation_keys,
        "Orphaned rollback observation fields are invalid",
    )
    expected_identity = {
        "schema": "complete99-orphaned-rollback-observation/v1",
        "deployment_id": expected_deployment_id,
        "proof_sha256": proof_sha256,
        "phase": "rolling_back",
        "state_exists": False,
        "lock_owned": True,
        "recovery_ready": True,
        "process_lock_available": True,
        "current_version": prior.get("version"),
        "current_database_version": prior.get("database_version"),
        "current_active": prior.get("active"),
        "current_plugin_sha256": prior.get("plugin_sha256"),
        "projected_deployment_id": prior.get("deployment_id"),
        "historical_baseline_database_fingerprint": prior.get(
            "database_fingerprint"
        ),
        "current_sync_configured": prior.get("sync_configured"),
        "current_robots_sha256": prior.get("robots_sha256"),
        "failed_candidate_database_fingerprint": failed.get(
            "candidate_database_fingerprint"
        ),
    }
    for key, expected in expected_identity.items():
        require(observation.get(key) == expected, f"Observation identity failed for {key}")
    require(
        observation.get("current_deployment")
        in {failed.get("deployment_id"), prior.get("deployment_id")},
        "Observation found an unreviewed deployment marker",
    )
    for key in (
        "current_database_fingerprint",
        "projected_database_fingerprint",
        "historical_baseline_database_fingerprint",
    ):
        require_digest(observation.get(key), f"Observation {key}")
    require(
        type(observation.get("historical_baseline_matches_projection")) is bool,
        "Observation baseline comparison is invalid",
    )
    require(
        observation.get("historical_baseline_matches_projection")
        is (
            observation.get("projected_database_fingerprint")
            == observation.get("historical_baseline_database_fingerprint")
        ),
        "Observation baseline comparison does not match its fingerprints",
    )
    validate_database_manifest(
        observation.get("database_manifest"),
        observation.get("database_manifest_sha256"),
        "Observation database manifest",
    )
    validate_database_storage(
        observation.get("database_storage"),
        "Observation database storage",
    )
    require(
        initial_status.get("database_fingerprint")
        == observation.get("current_database_fingerprint")
        and initial_status.get("projected_deployment_id")
        == observation.get("projected_deployment_id")
        and initial_status.get("projected_database_fingerprint")
        == observation.get("projected_database_fingerprint")
        and initial_status.get("database_manifest_sha256")
        == observation.get("database_manifest_sha256"),
        "Observation status and attestation differ",
    )
    require(
        initial_status.get("database_storage")
        == observation.get("database_storage"),
        "Observation status storage and attestation differ",
    )
    validate_observation_cleanup(
        audit.get("cleanup"),
        "Observation bridge cleanup",
    )


def validate_no_owner_audit(
    audit: dict[str, Any],
    deployment_id: str,
    expected_probe_id: str,
) -> None:
    require(
        deployment_id == expected_probe_id,
        "No-owner recovery audit does not belong to the exact preflight probe",
    )
    discovery = require_mapping(audit.get("discovery"), "Recovery discovery")
    require(discovery.get("probe_id") == expected_probe_id, "Probe identity changed")
    require(discovery.get("result") == "no-owner", "Recovery did not prove no owner")
    validate_finalize(discovery.get("finalize"), "Probe finalization")
    validate_cleanup(discovery.get("cleanup"), "Probe bridge cleanup")


def validate_orphaned_proof_audit(
    audit: dict[str, Any],
    proof_path: Path,
    proof: dict[str, Any],
    proof_sha256: str,
    repository_root: Path,
) -> None:
    failed = require_mapping(proof.get("failed_run"), "Failed-run proof")
    prior = require_mapping(proof.get("prior_run"), "Prior-run proof")
    expected_deployment_id = failed.get("deployment_id")
    require(
        type(expected_deployment_id) is str
        and DEPLOYMENT_ID.fullmatch(expected_deployment_id) is not None,
        "Failed-run deployment ID is invalid",
    )
    require(
        audit.get("deployment_id") == expected_deployment_id,
        "Recovery audit does not belong to the proof's failed deployment",
    )
    require(
        audit.get("result") == "recovered",
        "Reviewed recovery proof was supplied but exact recovery did not complete",
    )
    require(
        audit.get("decision") == "finish_orphaned_rollback_cleanup",
        "Reviewed recovery proof was consumed by the wrong recovery path",
    )

    discovery = require_mapping(audit.get("discovery"), "Recovery discovery")
    require(
        discovery.get("result") == "owner-discovered"
        and discovery.get("owner_deployment_id") == expected_deployment_id,
        "Recovery discovery did not find the proof's exact lock owner",
    )
    validate_cleanup(discovery.get("cleanup"), "Discovery bridge cleanup")

    expected_path = proof_path.relative_to(repository_root.resolve()).as_posix()
    proof_record = require_mapping(
        audit.get("orphaned_rollback_proof"),
        "Recovery proof audit",
    )
    require(proof_record.get("path") == expected_path, "Recovery proof path changed")
    require(
        proof_record.get("proof_sha256") == proof_sha256,
        "Recovery audit used a different proof digest",
    )

    initial_status = require_mapping(audit.get("initial_status"), "Initial status")
    has_reconciliation = (
        "orphaned_rollback_reconciliation" in audit or "reconciled_status" in audit
    )
    has_receipt_resume = "initial_orphaned_rollback_receipt" in audit
    require(
        has_reconciliation != has_receipt_resume,
        "Recovery audit must prove exactly one orphaned rollback consumption path",
    )

    prior_version = prior.get("version")
    prior_deployment_id = prior.get("deployment_id")
    require(prior.get("active") is True, "Reviewed prior plugin was not active")
    require(prior.get("sync_configured") is True, "Reviewed prior sync was not configured")
    require(prior.get("robots_exists") is True, "Reviewed prior robots.txt was absent")
    prior_plugin_sha256 = require_digest(prior.get("plugin_sha256"), "Prior plugin")
    prior_database_fingerprint = require_digest(
        prior.get("database_fingerprint"),
        "Prior database fingerprint",
    )
    prior_robots_sha256 = require_digest(prior.get("robots_sha256"), "Prior robots.txt")

    if has_reconciliation:
        require(
            initial_status.get("phase") == "rolling_back",
            "New reconciliation proof was used outside rolling_back",
        )
        require(
            initial_status.get("state_exists") is False,
            "Orphaned state journal still existed",
        )
        require(initial_status.get("lock_owned") is True, "Orphaned lock was not owned")
        require(
            initial_status.get("recovery_ready") is True,
            "Orphaned lock was not recovery-ready",
        )
        reconciliation = require_mapping(
            audit.get("orphaned_rollback_reconciliation"),
            "Orphaned rollback reconciliation",
        )
        require(
            reconciliation.get("phase") == "committed",
            "Reconciliation was not committed",
        )
        require(reconciliation.get("lock_retained") is True, "Reconciliation lost its lock")
        require(
            reconciliation.get("proof_sha256") == proof_sha256,
            "Reconciliation used a different proof digest",
        )
        require_digest(reconciliation.get("receipt_sha256"), "Recovery receipt")
        evidence_exists = reconciliation.get("evidence_directory_exists")
        require(type(evidence_exists) is bool, "Recovery evidence state is invalid")
        evidence_sha256 = reconciliation.get("evidence_directory_sha256")
        require(type(evidence_sha256) is str, "Recovery evidence digest type is invalid")
        if evidence_exists:
            require_digest(evidence_sha256, "Recovery evidence directory")
        else:
            require(not evidence_sha256, "Absent recovery evidence has a digest")

        reconciled = require_mapping(audit.get("reconciled_status"), "Reconciled status")
        require(reconciled.get("phase") == "committed", "Reconciled status was not committed")
        require(reconciled.get("state_exists") is False, "Reconciled state journal remained")
        require(reconciled.get("lock_owned") is True, "Reconciled lock was not retained")
    else:
        receipt = require_mapping(
            audit.get("initial_orphaned_rollback_receipt"),
            "Initial orphaned rollback receipt",
        )
        receipt_keys = {
            "phase",
            "state_exists",
            "lock_owned",
            "committed_outcome",
            "committed_expected_active",
            "committed_expected_absent",
            "committed_expected_version",
            "committed_expected_deployment",
            "committed_expected_plugin_sha256",
            "committed_expected_database_fingerprint",
            "committed_expected_robots_exists",
            "committed_expected_robots_sha256",
            "committed_expected_sync_configured",
            "orphaned_recovery_proof_sha256",
            "orphaned_recovery_receipt_sha256",
            "orphaned_reconciled_from",
            "orphaned_observed_deployment",
            "orphaned_recovery_evidence_exists",
            "orphaned_recovery_evidence_sha256",
        }
        require(
            set(receipt) == receipt_keys,
            "Initial orphaned rollback receipt fields are incomplete",
        )
        require(
            receipt.get("phase") in {"committed", "cleanup_failed"}
            and receipt.get("phase") == initial_status.get("phase"),
            "Initial orphaned rollback receipt phase is invalid",
        )
        require(
            receipt.get("state_exists") is False
            and initial_status.get("state_exists") is False,
            "Durable orphaned rollback receipt unexpectedly has a state journal",
        )
        require(
            receipt.get("lock_owned") is True and initial_status.get("lock_owned") is True,
            "Durable orphaned rollback receipt did not retain its lock",
        )
        expected_receipt = {
            "committed_outcome": "rolled_back",
            "committed_expected_active": True,
            "committed_expected_absent": False,
            "committed_expected_version": prior_version,
            "committed_expected_deployment": prior_deployment_id,
            "committed_expected_plugin_sha256": prior_plugin_sha256,
            "committed_expected_database_fingerprint": prior_database_fingerprint,
            "committed_expected_robots_exists": True,
            "committed_expected_robots_sha256": prior_robots_sha256,
            "committed_expected_sync_configured": True,
            "orphaned_recovery_proof_sha256": proof_sha256,
            "orphaned_reconciled_from": "rolling_back",
            "orphaned_observed_deployment": expected_deployment_id,
        }
        for key, expected in expected_receipt.items():
            require(receipt.get(key) == expected, f"Initial receipt failed for {key}")
        require_digest(
            receipt.get("orphaned_recovery_receipt_sha256"),
            "Initial orphaned rollback receipt",
        )
        receipt_evidence_exists = receipt.get("orphaned_recovery_evidence_exists")
        require(type(receipt_evidence_exists) is bool, "Receipt evidence state is invalid")
        receipt_evidence_sha256 = receipt.get("orphaned_recovery_evidence_sha256")
        require(type(receipt_evidence_sha256) is str, "Receipt evidence digest type is invalid")
        if receipt_evidence_exists:
            require_digest(receipt_evidence_sha256, "Receipt evidence directory")
        else:
            require(not receipt_evidence_sha256, "Absent receipt evidence has a digest")

    health = require_mapping(audit.get("health"), "Recovered health")
    expected_health = {
        "component": "complete99-platform",
        "database_version": prior_version,
        "deployment_id": prior_deployment_id,
        "status": "ok",
        "sync_configured": True,
        "version": prior_version,
    }
    for key, expected in expected_health.items():
        require(health.get(key) == expected, f"Recovered health failed for {key}")

    rendered = require_mapping(audit.get("rendered_home"), "Recovered rendered body")
    require(rendered.get("exact_path") == "/", "Rendered recovery used the wrong path")
    require(rendered.get("version") == prior_version, "Rendered recovery version changed")
    require(
        rendered.get("deployment_id") == prior_deployment_id,
        "Rendered recovery deployment changed",
    )
    require_digest(rendered.get("body_sha256"), "Recovered rendered body")

    robots = require_mapping(
        audit.get("orphaned_rollback_robots"),
        "Recovered robots.txt",
    )
    require(robots.get("status") == 200, "Recovered robots.txt was not public")
    require(robots.get("sha256") == prior_robots_sha256, "Recovered robots.txt changed")
    validate_finalize(audit.get("finalize"), "Orphaned recovery finalization")
    validate_cleanup(audit.get("cleanup"), "Orphaned recovery bridge cleanup")


def validate_recovery_audit(
    summary_json: str,
    proof_path: str,
    audit_root: Path,
    expected_probe_id: str,
    *,
    repository_root: Path = ROOT,
    expect_observation: bool = False,
) -> dict[str, Any]:
    require(
        DEPLOYMENT_ID.fullmatch(expected_probe_id) is not None,
        "Expected preflight probe ID is invalid",
    )
    summary = parse_json(summary_json, "Recovery process summary")
    deployment_id = summary.get("deployment_id")
    result = summary.get("result")
    require(
        type(deployment_id) is str
        and DEPLOYMENT_ID.fullmatch(deployment_id) is not None,
        "Recovery summary deployment ID is invalid",
    )
    allowed_results = (
        {"orphaned-rollback-observed"}
        if expect_observation
        else {"no-recovery-needed", "recovered"}
    )
    require(type(result) is str and result in allowed_results, "Recovery summary result is invalid")

    expected_audit = (audit_root.resolve() / f"{deployment_id}.json").resolve()
    reported_value = summary.get("audit")
    require(type(reported_value) is str, "Recovery summary audit path must be a string")
    reported = Path(reported_value)
    require(reported.is_absolute(), "Recovery summary audit path must be absolute")
    require(reported.resolve() == expected_audit, "Recovery summary points to the wrong audit file")
    require(expected_audit.is_file(), "Exact recovery audit file is missing")
    audit = read_json(expected_audit, "Recovery audit")
    require(audit.get("deployment_id") == deployment_id, "Recovery audit deployment ID changed")
    require(audit.get("result") == result, "Recovery audit result differs from its process summary")

    if proof_path:
        reviewed_path, proof, proof_sha256 = load_reviewed_proof(
            proof_path,
            repository_root.resolve(),
        )
        if expect_observation:
            require(
                result == "orphaned-rollback-observed",
                "Reviewed proof did not produce an orphaned rollback observation",
            )
        else:
            require(
                result == "recovered",
                "Reviewed proof was supplied but the exact failed deployment was not recovered",
            )
        failed = require_mapping(proof.get("failed_run"), "Failed-run proof")
        require(
            deployment_id == failed.get("deployment_id"),
            "Reviewed proof was used for the wrong failed deployment",
        )
        if expect_observation:
            validate_orphaned_observation_audit(
                audit,
                reviewed_path,
                proof,
                proof_sha256,
                repository_root.resolve(),
                expected_probe_id,
            )
            proof_consumed = False
        else:
            validate_orphaned_proof_audit(
                audit,
                reviewed_path,
                proof,
                proof_sha256,
                repository_root.resolve(),
            )
            proof_consumed = True
    elif result == "no-recovery-needed":
        validate_no_owner_audit(audit, deployment_id, expected_probe_id)
        proof_consumed = False
    else:
        require(
            audit.get("decision") in GENERIC_RECOVERY_DECISIONS,
            "Preflight recovery completed through an unrecognized path",
        )
        validate_cleanup(audit.get("cleanup"), "Preflight recovery bridge cleanup")
        proof_consumed = False

    validated = {
        "deployment_id": deployment_id,
        "proof_consumed": proof_consumed,
        "result": result,
        "validated": True,
    }
    if expect_observation:
        validated["proof_observed"] = True
    return validated


def read_exact_stage_audit(
    audit_root: Path,
    deployment_id: str,
    label: str,
    *,
    audit_id: str | None = None,
) -> dict[str, Any] | None:
    filename_id = audit_id or deployment_id
    require(
        DEPLOYMENT_ID.fullmatch(filename_id) is not None,
        f"{label} audit filename identity is invalid",
    )
    path = (audit_root.resolve() / f"{filename_id}.json").resolve()
    require(path.parent == audit_root.resolve(), f"{label} audit path escaped its root")
    if not path.is_file():
        return None
    audit = read_json(path, f"{label} audit")
    require(audit.get("deployment_id") == deployment_id, f"{label} audit identity changed")
    if audit_id is not None:
        require(audit.get("audit_id") == audit_id, f"{label} audit ID changed")
    require(type(audit.get("result")) is str, f"{label} audit result is invalid")
    return audit


def require_stage_result(
    audit: dict[str, Any] | None,
    expected_result: str,
    label: str,
) -> dict[str, Any]:
    require(audit is not None, f"Exact {label} audit is missing")
    require(audit.get("result") == expected_result, f"{label} audit has the wrong result")
    return audit


def validate_commerce_cleanup(value: Any, label: str) -> None:
    cleanup = require_mapping(value, label)
    require(cleanup.get("row_absence_verified") is True, f"{label} left its snippet row")
    require(cleanup.get("route_404") is True, f"{label} left its route executable")


def validate_commerce_outcomes(
    *,
    platform_succeeded: bool,
    commerce_outcome: str,
    commerce_recovery_outcome: str,
    commerce_id: str,
    commerce_audit_root: Path,
) -> dict[str, Any]:
    commerce_audit = read_exact_stage_audit(
        commerce_audit_root,
        commerce_id,
        "Commerce",
    )
    commerce_recovery_audit = read_exact_stage_audit(
        commerce_audit_root,
        commerce_id,
        "Commerce recovery",
        audit_id=f"{commerce_id}-recovery",
    )
    if not platform_succeeded:
        require(
            commerce_outcome in {"skipped", "cancelled"}
            and commerce_recovery_outcome in {"skipped", "cancelled"},
            "Commerce ran without a successful platform deployment",
        )
        require(commerce_audit is None, "Commerce audit exists for a skipped commerce stage")
        require(
            commerce_recovery_audit is None,
            "Commerce recovery audit exists for a skipped commerce stage",
        )
        return {
            "commerce_outcome": commerce_outcome,
            "commerce_recovery_outcome": commerce_recovery_outcome,
        }

    require(
        commerce_outcome in {"success", "failure"},
        "Commerce was skipped after a successful platform deployment",
    )
    if commerce_outcome == "success":
        verified = require_stage_result(commerce_audit, "verified", "commerce")
        validate_commerce_cleanup(verified.get("cleanup"), "Commerce bridge cleanup")
        require(
            commerce_recovery_outcome == "skipped",
            "Commerce recovery ran after verified commerce",
        )
        require(
            commerce_recovery_audit is None,
            "Commerce recovery audit exists after verified commerce",
        )
    else:
        require_stage_result(commerce_audit, "failed", "commerce")
        require(
            commerce_recovery_outcome in {"success", "failure"},
            "Failed commerce did not run its recovery step",
        )
        expected_result = (
            "verified" if commerce_recovery_outcome == "success" else "failed"
        )
        recovery = require_stage_result(
            commerce_recovery_audit,
            expected_result,
            "commerce recovery",
        )
        if commerce_recovery_outcome == "success":
            validate_commerce_cleanup(
                recovery.get("cleanup"),
                "Commerce recovery cleanup",
            )
    return {
        "commerce_outcome": commerce_outcome,
        "commerce_recovery_outcome": commerce_recovery_outcome,
    }


def validate_stage_outcomes(
    *,
    preflight_outcome: str,
    production_outcome: str,
    mutation_outcome: str,
    mutation_started: str,
    recovery_outcome: str,
    commerce_outcome: str,
    commerce_recovery_outcome: str,
    dry_run_id: str,
    production_id: str,
    commerce_id: str,
    deploy_audit_root: Path,
    recovery_audit_root: Path,
    commerce_audit_root: Path,
    observation_only: bool = False,
) -> dict[str, Any]:
    outcomes = {"success", "failure", "cancelled", "skipped"}
    for value, label in (
        (preflight_outcome, "Preflight"),
        (production_outcome, "Production"),
        (mutation_outcome, "Mutation detector"),
        (recovery_outcome, "Recovery"),
        (commerce_outcome, "Commerce"),
        (commerce_recovery_outcome, "Commerce recovery"),
    ):
        require(value in outcomes, f"{label} step outcome is missing or invalid")
    require(
        mutation_started in {"", "true", "false"},
        "Mutation-edge output is invalid",
    )
    for value, label in (
        (dry_run_id, "Dry-run"),
        (production_id, "Production"),
        (commerce_id, "Commerce"),
    ):
        require(
            type(value) is str and DEPLOYMENT_ID.fullmatch(value) is not None,
            f"{label} deployment ID is invalid",
        )

    dry_audit = read_exact_stage_audit(deploy_audit_root, dry_run_id, "Dry-run")
    production_audit = read_exact_stage_audit(
        deploy_audit_root,
        production_id,
        "Production",
    )
    recovery_audit = read_exact_stage_audit(
        recovery_audit_root,
        production_id,
        "Production recovery",
    )

    if observation_only:
        require(preflight_outcome == "success", "Observation preflight did not succeed")
        require(dry_audit is None, "Observation-only run created a dry-run audit")
        require(
            production_outcome == "skipped"
            and mutation_outcome == "skipped"
            and recovery_outcome == "skipped"
            and commerce_outcome == "skipped"
            and commerce_recovery_outcome == "skipped"
            and mutation_started == "",
            "Observation-only run crossed the platform mutation path",
        )
        require(
            production_audit is None and recovery_audit is None,
            "Observation-only run created a platform mutation audit",
        )
        commerce = validate_commerce_outcomes(
            platform_succeeded=False,
            commerce_outcome=commerce_outcome,
            commerce_recovery_outcome=commerce_recovery_outcome,
            commerce_id=commerce_id,
            commerce_audit_root=commerce_audit_root,
        )
        return {
            **commerce,
            "observation_only": True,
            "preflight_outcome": preflight_outcome,
            "production_outcome": production_outcome,
            "recovery_outcome": recovery_outcome,
            "validated": True,
        }

    if preflight_outcome == "success":
        passed_dry_run = require_stage_result(dry_audit, "dry-run-passed", "dry-run")
        validate_cleanup(passed_dry_run.get("cleanup"), "Dry-run bridge cleanup")
        require(
            production_outcome != "skipped",
            "Production was skipped after a successful preflight",
        )
    else:
        require(
            production_outcome in {"skipped", "cancelled"},
            "Production ran without a successful preflight",
        )
        require(
            mutation_outcome in {"skipped", "cancelled"}
            and recovery_outcome in {"skipped", "cancelled"},
            "Mutation recovery ran without a successful preflight",
        )
        require(production_audit is None, "Production audit exists for a skipped production stage")
        require(recovery_audit is None, "Recovery audit exists for a skipped production stage")
        if dry_audit is not None:
            require(
                dry_audit.get("result") == "failed",
                "Failed preflight has a non-failed dry-run audit",
            )
        commerce = validate_commerce_outcomes(
            platform_succeeded=False,
            commerce_outcome=commerce_outcome,
            commerce_recovery_outcome=commerce_recovery_outcome,
            commerce_id=commerce_id,
            commerce_audit_root=commerce_audit_root,
        )
        return {
            **commerce,
            "preflight_outcome": preflight_outcome,
            "production_outcome": production_outcome,
            "recovery_outcome": recovery_outcome,
            "validated": True,
        }

    if production_outcome == "success":
        deployed = require_stage_result(production_audit, "deployed", "production")
        validate_cleanup(deployed.get("cleanup"), "Production bridge cleanup")
        require(
            mutation_outcome == "skipped" and not mutation_started,
            "Mutation detector ran after a successful production stage",
        )
        require(recovery_outcome == "skipped", "Recovery ran after successful production")
        require(recovery_audit is None, "Recovery audit exists after successful production")
    elif production_outcome == "failure":
        failed_production = require_stage_result(production_audit, "failed", "production")
        require(
            mutation_outcome == "success" and mutation_started in {"true", "false"},
            "Failed production did not produce an exact mutation-edge decision",
        )
        if mutation_started == "true":
            require(
                recovery_outcome in {"success", "failure"},
                "Crossed mutation edge did not run recovery",
            )
            expected_recovery_result = (
                "recovered" if recovery_outcome == "success" else "failed"
            )
            exact_recovery = require_stage_result(
                recovery_audit,
                expected_recovery_result,
                "production recovery",
            )
            if recovery_outcome == "success":
                require(
                    exact_recovery.get("decision") in GENERIC_RECOVERY_DECISIONS,
                    "Production recovery used an unrecognized path",
                )
                validate_cleanup(
                    exact_recovery.get("cleanup"),
                    "Production recovery bridge cleanup",
                )
        else:
            require(recovery_outcome == "skipped", "Recovery ran before the mutation edge")
            require(recovery_audit is None, "Recovery audit exists before the mutation edge")
        require(failed_production.get("result") == "failed", "Production failure changed")
    else:
        require(
            production_outcome == "cancelled",
            "Successful preflight produced an invalid production outcome",
        )
        require(
            mutation_outcome in {"skipped", "cancelled"}
            and recovery_outcome in {"skipped", "cancelled"},
            "Cancelled production ran an untrusted recovery stage",
        )
        if production_audit is not None:
            require(
                production_audit.get("result") in {"failed", "deployed"},
                "Cancelled production left a non-terminal audit",
            )
        require(recovery_audit is None, "Cancelled production has a recovery audit")

    commerce = validate_commerce_outcomes(
        platform_succeeded=production_outcome == "success",
        commerce_outcome=commerce_outcome,
        commerce_recovery_outcome=commerce_recovery_outcome,
        commerce_id=commerce_id,
        commerce_audit_root=commerce_audit_root,
    )
    return {
        **commerce,
        "mutation_started": mutation_started == "true",
        "preflight_outcome": preflight_outcome,
        "production_outcome": production_outcome,
        "recovery_outcome": recovery_outcome,
        "validated": True,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--stage-outcomes", action="store_true")
    parser.add_argument("--expect-observation", action="store_true")
    parser.add_argument(
        "--summary-json",
        default=os.environ.get("COMPLETE99_RECOVERY_SUMMARY_JSON", ""),
    )
    parser.add_argument(
        "--proof",
        default=os.environ.get("COMPLETE99_ORPHANED_ROLLBACK_PROOF", ""),
    )
    parser.add_argument("--audit-root", type=Path, default=ROOT / "recovery-audit")
    parser.add_argument("--deploy-audit-root", type=Path, default=ROOT / "deploy-audit")
    parser.add_argument("--commerce-audit-root", type=Path, default=ROOT / "commerce-audit")
    parser.add_argument("--expected-probe-id", default="")
    parser.add_argument("--dry-run-id", default="")
    parser.add_argument("--production-id", default="")
    parser.add_argument("--commerce-id", default="")
    args = parser.parse_args()
    if args.stage_outcomes:
        result = validate_stage_outcomes(
            observation_only=os.environ.get(
                "COMPLETE99_OBSERVATION_ONLY", ""
            ).lower()
            == "true",
            preflight_outcome=os.environ.get("COMPLETE99_PREFLIGHT_OUTCOME", ""),
            production_outcome=os.environ.get("COMPLETE99_PRODUCTION_OUTCOME", ""),
            mutation_outcome=os.environ.get("COMPLETE99_MUTATION_OUTCOME", ""),
            mutation_started=os.environ.get("COMPLETE99_MUTATION_STARTED", ""),
            recovery_outcome=os.environ.get("COMPLETE99_RECOVERY_OUTCOME", ""),
            commerce_outcome=os.environ.get("COMPLETE99_COMMERCE_OUTCOME", ""),
            commerce_recovery_outcome=os.environ.get(
                "COMPLETE99_COMMERCE_RECOVERY_OUTCOME",
                "",
            ),
            dry_run_id=args.dry_run_id,
            production_id=args.production_id,
            commerce_id=args.commerce_id,
            deploy_audit_root=args.deploy_audit_root,
            recovery_audit_root=args.audit_root,
            commerce_audit_root=args.commerce_audit_root,
        )
    else:
        require(bool(args.summary_json), "Recovery process summary is missing")
        require(bool(args.expected_probe_id), "Expected preflight probe ID is missing")
        result = validate_recovery_audit(
            args.summary_json,
            args.proof,
            args.audit_root,
            args.expected_probe_id,
            expect_observation=args.expect_observation,
        )
    print(json.dumps(result, sort_keys=True))
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except AuditValidationError as error:
        print(f"RECOVERY AUDIT VALIDATION FAILED: {error}", file=os.sys.stderr)
        raise SystemExit(1)
