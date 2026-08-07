#!/usr/bin/env python3
"""Fail closed unless one preflight recovery audit proves its exact outcome."""

from __future__ import annotations

import argparse
import hashlib
import io
import json
import os
import re
import zipfile
from pathlib import Path, PurePosixPath
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


def exact_json_equal(left: Any, right: Any) -> bool:
    """Compare decoded JSON without Python's bool/int equality aliasing."""
    if type(left) is not type(right):
        return False
    if isinstance(left, dict):
        return set(left) == set(right) and all(
            exact_json_equal(left[key], right[key]) for key in left
        )
    if isinstance(left, list):
        return len(left) == len(right) and all(
            exact_json_equal(left_item, right_item)
            for left_item, right_item in zip(left, right, strict=True)
        )
    return left == right


def resolves_without_indirection(unresolved: Path, resolved: Path) -> bool:
    """Reject symlinks and Windows junctions in reviewed evidence paths."""
    lexical = os.path.normcase(os.path.abspath(os.fspath(unresolved)))
    canonical = os.path.normcase(os.fspath(resolved))
    return lexical == canonical


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
    unresolved_path = (
        repository_root / candidate if not candidate.is_absolute() else candidate
    )
    path = unresolved_path.resolve()
    unresolved_proof_root = repository_root / "docs" / "recovery-proofs"
    proof_root = unresolved_proof_root.resolve()
    require(
        resolves_without_indirection(unresolved_proof_root, proof_root)
        and resolves_without_indirection(unresolved_path, path)
        and proof_root in path.parents
        and path.suffix.lower() == ".json",
        "Recovery proof must be a JSON file under docs/recovery-proofs",
    )
    envelope = read_json(path, "Recovery proof")
    proof_schema = envelope.get("schema")
    require(
        set(envelope) == {"schema", "proof", "proof_sha256"}
        and type(proof_schema) is str
        and proof_schema
        in {
            "complete99-orphaned-rollback-proof/v1",
            "complete99-orphaned-rollback-proof/v2",
        },
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
    expected_proof_keys = (
        {"database_reconciliation", "failed_run", "prior_run"}
        if proof_schema == "complete99-orphaned-rollback-proof/v2"
        else {"failed_run", "prior_run"}
    )
    require(
        set(proof) == expected_proof_keys
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
        and failed["run_id"] > prior["run_id"]
        and f"-{failed['run_id']}-" in failed["deployment_id"]
        and f"-{prior['run_id']}-" in prior["deployment_id"],
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
    require(
        failed["candidate_version"] != prior["version"]
        and failed["candidate_plugin_sha256"] != prior["plugin_sha256"]
        and failed["candidate_database_fingerprint"]
        != prior["database_fingerprint"],
        "Failed candidate identity is indistinguishable from the prior release",
    )
    if proof_schema == "complete99-orphaned-rollback-proof/v2":
        validate_v2_database_reconciliation(
            proof,
            proof_root,
            repository_root.resolve(),
        )
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
        and type(storage.get("tables")) is int
        and storage.get("tables") == 3,
        f"{label} is not fully transactional",
    )


def canonical_json_sha256(value: dict[str, Any]) -> str:
    canonical = json.dumps(
        value,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    return hashlib.sha256(canonical).hexdigest()


def load_bound_interrupted_audit(
    raw_path: Any,
    expected_sha256: Any,
    repository_root: Path,
    label: str,
) -> dict[str, Any]:
    """Load one byte-bound observation audit without accepting path indirection."""
    require(
        type(raw_path) is str
        and type(expected_sha256) is str
        and DIGEST.fullmatch(expected_sha256) is not None
        and "\\" not in raw_path,
        f"{label} audit identity is invalid",
    )
    relative = Path(raw_path)
    expected_relative_root = Path("docs/recovery-proofs/observations")
    unresolved_root = repository_root / expected_relative_root
    resolved_root = unresolved_root.resolve()
    unresolved_path = repository_root / relative
    resolved_path = unresolved_path.resolve()
    require(
        not relative.is_absolute()
        and relative.as_posix() == raw_path
        and relative.parent == expected_relative_root
        and relative.suffix == ".json"
        and relative.name.startswith("c99-")
        and ".." not in relative.parts
        and resolves_without_indirection(unresolved_root, resolved_root)
        and resolves_without_indirection(unresolved_path, resolved_path)
        and resolved_path.parent == resolved_root,
        f"{label} audit must be a direct reviewed JSON file under the observation evidence root",
    )
    try:
        raw = resolved_path.read_bytes()
        decoded = raw.decode("utf-8")
    except (OSError, UnicodeDecodeError) as error:
        raise AuditValidationError(f"{label} audit could not be read") from error
    require(
        hashlib.sha256(raw).hexdigest() == expected_sha256,
        f"{label} audit digest does not match",
    )
    return parse_json(decoded, f"{label} audit")


def validate_interrupted_cleanup(value: Any, label: str) -> None:
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
    removed_ids = cleanup.get("removed_ids")
    require(
        isinstance(removed_ids, list)
        and bool(removed_ids)
        and all(type(value) is int and value > 0 for value in removed_ids),
        f"{label} removed IDs are invalid",
    )
    require(
        cleanup.get("route_404") is True
        and cleanup.get("row_absence_verified") is True
        and cleanup.get("snippet_active") is False
        and cleanup.get("snippet_deleted") is True,
        f"{label} did not prove complete cleanup",
    )


def validate_interrupted_common_audit(
    audit: dict[str, Any],
    deployment_id: str,
    label: str,
) -> None:
    require(
        audit.get("deployment_id") == deployment_id
        and audit.get("local_test") is False,
        f"{label} audit identity is invalid",
    )
    started_at = audit.get("started_at")
    finished_at = audit.get("finished_at")
    require(
        type(started_at) is str
        and UTC_TIMESTAMP.fullmatch(started_at) is not None
        and type(finished_at) is str
        and UTC_TIMESTAMP.fullmatch(finished_at) is not None
        and finished_at >= started_at,
        f"{label} timestamps are invalid",
    )
    require(
        exact_json_equal(
            audit.get("bridge_site_identity"),
            {
                "home_host": "complete99.co.il",
                "rest_host": "complete99.co.il",
                "siteurl_host": "complete99.co.il",
            },
        ),
        f"{label} bridge site identity is invalid",
    )
    require(
        exact_json_equal(
            audit.get("identity"),
            {
                "id": 1,
                "roles": ["administrator"],
                "site_identity": {
                    "home": "https://complete99.co.il",
                    "url": "https://complete99.co.il",
                },
            },
        ),
        f"{label} authentication identity is invalid",
    )
    bootstrap = require_mapping(audit.get("bootstrap_cleanup"), f"{label} bootstrap")
    require(
        set(bootstrap)
        == {
            "exact_name",
            "known_id",
            "known_id_matched",
            "removed_ids",
            "row_absence_verified",
        }
        and bootstrap.get("exact_name") == "c99-deploy-bootstrap"
        and type(bootstrap.get("known_id")) is int
        and bootstrap["known_id"] > 0
        and bootstrap.get("known_id_matched") is False
        and bootstrap.get("removed_ids") == []
        and bootstrap.get("row_absence_verified") is True,
        f"{label} bootstrap cleanup is invalid",
    )
    validate_interrupted_cleanup(audit.get("cleanup"), f"{label} cleanup")


def validate_interrupted_discovery(
    value: Any,
    failed_deployment_id: str,
    expected_probe_id: str,
    expected_owner_phase: str,
    label: str,
) -> None:
    discovery = require_mapping(value, label)
    require(
        set(discovery)
        == {
            "bootstrap_cleanup",
            "cleanup",
            "owner_deployment_id",
            "owner_phase",
            "probe_id",
            "result",
        }
        and discovery.get("probe_id") == expected_probe_id
        and discovery.get("owner_deployment_id") == failed_deployment_id
        and discovery.get("owner_phase") == expected_owner_phase
        and discovery.get("result") == "owner-discovered",
        f"{label} identity is invalid",
    )
    require(
        exact_json_equal(
            discovery.get("bootstrap_cleanup"),
            {
                "exact_name": "c99-deploy-bootstrap",
                "known_id": 5,
                "known_id_matched": False,
                "removed_ids": [],
                "row_absence_verified": True,
            },
        ),
        f"{label} bootstrap cleanup is invalid",
    )
    cleanup = require_mapping(discovery.get("cleanup"), f"{label} cleanup")
    removed_ids = cleanup.get("removed_ids")
    require(
        set(cleanup)
        == {
            "removed_ids",
            "route_404",
            "row_absence_verified",
            "snippet_active",
            "snippet_deleted",
        }
        and isinstance(removed_ids, list)
        and len(removed_ids) == 1
        and type(removed_ids[0]) is int
        and removed_ids[0] > 0
        and cleanup.get("route_404") is True
        and cleanup.get("row_absence_verified") is True
        and cleanup.get("snippet_active") is False
        and cleanup.get("snippet_deleted") is True,
        f"{label} cleanup is invalid",
    )


def validate_interrupted_source_audits(
    failed: dict[str, Any],
    prior: dict[str, Any],
    failed_audit: dict[str, Any],
    recovery_audit: dict[str, Any],
    prior_audit: dict[str, Any],
) -> dict[str, str]:
    validate_interrupted_common_audit(
        failed_audit,
        failed["deployment_id"],
        "Interrupted forward failed deploy",
    )
    validate_interrupted_common_audit(
        recovery_audit,
        failed["deployment_id"],
        "Interrupted forward failed recovery",
    )
    validate_interrupted_common_audit(
        prior_audit,
        prior["deployment_id"],
        "Interrupted forward prior deploy",
    )

    failed_preflight = require_mapping(
        failed_audit.get("preflight"),
        "Interrupted forward failed deploy preflight",
    )
    require(
        failed_audit.get("dry_run") is False
        and failed_audit.get("result") == "failed"
        and failed_audit.get("error") == "HTTPDeployError"
        and failed_audit.get("failed_gate") == "install"
        and failed_audit.get("commit") == failed["commit"]
        and failed_audit.get("version") == failed["version"]
        and failed_audit.get("sha256") == failed["artifact_sha256"]
        and failed_audit.get("source_sha256") == failed["source_sha256"]
        and failed_audit.get("artifact")
        == f"complete99-platform-{failed['version']}.zip"
        and failed_preflight.get("current_active") is prior["active"]
        and failed_preflight.get("current_deployment") == prior["deployment_id"]
        and failed_preflight.get("current_version") == prior["version"]
        and failed_preflight.get("database_fingerprint")
        == failed["baseline_database_fingerprint"]
        and failed_preflight.get("robots_prior_exists") is True
        and failed_preflight.get("robots_prior_sha256") == prior["robots_sha256"]
        and failed_preflight.get("had_plugin") is True
        and failed_preflight.get("target_dir_exists") is True
        and failed_preflight.get("plugin_main_exists") is True
        and failed_preflight.get("direct_filesystem") is True
        and exact_json_equal(
            failed_preflight.get("transactional_storage"),
            {"engine": "INNODB", "tables": 3},
        ),
        "Interrupted forward failed deploy audit conflicts with the proof",
    )
    require(
        exact_json_equal(
            failed_audit.get("prior_health"),
            {
                "component": "complete99-platform",
                "database_version": prior["database_version"],
                "deployment_id": prior["deployment_id"],
                "status": "ok",
                "sync_configured": prior["sync_configured"],
                "version": prior["version"],
            },
        ),
        "Interrupted forward failed deploy prior health conflicts with the proof",
    )
    failed_home = require_mapping(
        failed_audit.get("prior_rendered_home"),
        "Interrupted forward failed deploy prior home",
    )
    require(
        failed_home.get("deployment_id") == prior["deployment_id"]
        and failed_home.get("exact_path") == "/"
        and failed_home.get("version") == prior["version"],
        "Interrupted forward failed deploy prior home conflicts with the proof",
    )
    require_digest(failed_home.get("body_sha256"), "Failed deploy prior home")
    require(
        exact_json_equal(
            failed_audit.get("failure_rollback"),
            {"error": "HTTPDeployError", "rolled_back": False},
        ),
        "Interrupted forward failed deploy rollback state is invalid",
    )

    recovery_status = require_mapping(
        recovery_audit.get("initial_status"),
        "Interrupted forward failed recovery status",
    )
    require(
        recovery_audit.get("result") == "failed"
        and recovery_audit.get("error") == "HTTPDeployError"
        and recovery_audit.get("discovery") is None
        and set(recovery_status)
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
        }
        and recovery_status.get("phase") == "installing"
        and recovery_status.get("state_exists") is True
        and recovery_status.get("lock_owned") is True
        and recovery_status.get("recovery_ready") is True
        and recovery_status.get("process_lock_available") is True
        and recovery_status.get("projected_database_fingerprint") == ""
        and recovery_status.get("projected_deployment_id") == ""
        and recovery_status.get("database_storage") == [],
        "Interrupted forward failed recovery audit conflicts with the proof",
    )
    recovery_database_fingerprint = require_digest(
        recovery_status.get("database_fingerprint"),
        "Interrupted forward failed recovery database fingerprint",
    )
    recovery_manifest_sha256 = require_digest(
        recovery_status.get("database_manifest_sha256"),
        "Interrupted forward failed recovery database manifest",
    )
    require(
        recovery_database_fingerprint
        not in {
            failed["baseline_database_fingerprint"],
            prior["database_fingerprint"],
        },
        "Interrupted forward failed recovery database identity is invalid",
    )

    prior_install = require_mapping(
        prior_audit.get("install"),
        "Interrupted forward prior install",
    )
    require(
        prior_audit.get("dry_run") is False
        and prior_audit.get("result") == "deployed"
        and prior_audit.get("commit") == prior["commit"]
        and prior_audit.get("version") == prior["version"]
        and prior_install.get("installed_plugin_sha256") == prior["plugin_sha256"]
        and prior_install.get("robots_sha256") == prior["robots_sha256"],
        "Interrupted forward prior deploy audit conflicts with the proof",
    )
    require(
        exact_json_equal(
            prior_audit.get("health"),
            {
                "component": "complete99-platform",
                "database_version": prior["database_version"],
                "deployment_id": prior["deployment_id"],
                "status": "ok",
                "sync_configured": prior["sync_configured"],
                "version": prior["version"],
            },
        ),
        "Interrupted forward prior health conflicts with the proof",
    )
    prior_home = require_mapping(
        prior_audit.get("rendered_home"),
        "Interrupted forward prior home",
    )
    require(
        prior_home.get("deployment_id") == prior["deployment_id"]
        and prior_home.get("exact_path") == "/"
        and prior_home.get("version") == prior["version"],
        "Interrupted forward prior rendered home conflicts with the proof",
    )
    require_digest(prior_home.get("body_sha256"), "Interrupted forward prior home")
    require(
        exact_json_equal(
            prior_audit.get("robots"),
            {"sha256": prior["robots_sha256"], "status": 200},
        ),
        "Interrupted forward prior robots.txt conflicts with the proof",
    )
    validate_finalize(prior_audit.get("finalize"), "Interrupted forward prior finalize")
    return {
        "database_fingerprint": recovery_database_fingerprint,
        "database_manifest_sha256": recovery_manifest_sha256,
    }


def expected_interrupted_observation(
    failed: dict[str, Any],
    prior: dict[str, Any],
    database_fingerprint: str,
    database_manifest: dict[str, Any],
    database_manifest_sha256: str,
    database_storage: dict[str, Any],
    recorded_plugin_sha256: str,
) -> dict[str, Any]:
    return {
        "adopted_forward_no_rollback": False,
        "baseline_database_fingerprint": failed["baseline_database_fingerprint"],
        "current_active": True,
        "current_database_version": failed["version"],
        "current_deployment": failed["deployment_id"],
        "current_plugin_main_exists": True,
        "current_plugin_sha256": failed["installed_plugin_sha256"],
        "current_robots_sha256": prior["robots_sha256"],
        "current_sync_configured": True,
        "current_target_dir_exists": True,
        "current_version": failed["version"],
        "database_fingerprint": database_fingerprint,
        "database_manifest": database_manifest,
        "database_manifest_sha256": database_manifest_sha256,
        "database_storage": database_storage,
        "deployment_id": failed["deployment_id"],
        "expected_sha256": failed["artifact_sha256"],
        "expected_version": failed["version"],
        "interrupted_forward_database_manifest_sha256": "",
        "interrupted_forward_candidate": True,
        "interrupted_forward_proof_sha256": "",
        "lock_owned": True,
        "migration_failed": False,
        "migration_invariants_valid": True,
        "no_rollback_artifacts": True,
        "phase": "installing",
        "process_lock_available": True,
        "recorded_installed_plugin_sha256": recorded_plugin_sha256,
        "recovery_ready": True,
        "robots_applied": True,
        "robots_managed_sha256": prior["robots_sha256"],
        "runtime_loaded": True,
        "runtime_version": failed["version"],
        "schema": "complete99-interrupted-forward-observation/v1",
        "state_exists": True,
    }


def validate_interrupted_health_home_robots(
    audit: dict[str, Any],
    failed: dict[str, Any],
    prior: dict[str, Any],
    *,
    prefix: str = "",
    label: str,
) -> None:
    require(
        exact_json_equal(
            audit.get(f"{prefix}health"),
            {
                "component": "complete99-platform",
                "database_version": failed["version"],
                "deployment_id": failed["deployment_id"],
                "status": "ok",
                "sync_configured": True,
                "version": failed["version"],
            },
        ),
        f"{label} health is invalid",
    )
    home = require_mapping(audit.get(f"{prefix}rendered_home"), f"{label} home")
    require(
        set(home) == {"body_sha256", "deployment_id", "exact_path", "version"}
        and home.get("deployment_id") == failed["deployment_id"]
        and home.get("exact_path") == "/"
        and home.get("version") == failed["version"],
        f"{label} rendered home is invalid",
    )
    require_digest(home.get("body_sha256"), f"{label} rendered home")
    require(
        exact_json_equal(
            audit.get(f"{prefix}robots"),
            {"sha256": prior["robots_sha256"], "status": 200},
        ),
        f"{label} robots.txt is invalid",
    )


def validate_interrupted_observation_audit(
    audit: dict[str, Any],
    failed: dict[str, Any],
    prior: dict[str, Any],
    recovery_identity: dict[str, str],
    proof_path: str,
    proof_sha256: str,
    expected_probe_id: str,
    *,
    expected_commit: str | None = None,
    expected_manifest: dict[str, Any] | None = None,
    expected_storage: dict[str, Any] | None = None,
) -> None:
    validate_interrupted_common_audit(
        audit,
        failed["deployment_id"],
        "Interrupted forward observation",
    )
    require(
        set(audit)
        == {
            "bootstrap_cleanup",
            "bridge_site_identity",
            "cleanup",
            "commit",
            "decision",
            "deployment_id",
            "discovery",
            "finished_at",
            "health",
            "identity",
            "interrupted_forward_observation",
            "interrupted_forward_proof",
            "local_test",
            "rendered_home",
            "result",
            "robots",
            "started_at",
        }
        and audit.get("decision") == "observe_interrupted_forward"
        and audit.get("result") == "interrupted_forward_observed",
        "Interrupted forward observation audit schema is invalid",
    )
    commit = audit.get("commit")
    require(
        type(commit) is str
        and COMMIT.fullmatch(commit) is not None
        and commit not in {failed["commit"], prior["commit"]}
        and (expected_commit is None or commit == expected_commit),
        "Interrupted forward observation commit is invalid",
    )
    validate_interrupted_discovery(
        audit.get("discovery"),
        failed["deployment_id"],
        expected_probe_id,
        "installing",
        "Interrupted forward observation discovery",
    )
    require(
        exact_json_equal(
            audit.get("interrupted_forward_proof"),
            {
                "path": proof_path,
                "proof_sha256": proof_sha256,
                "schema": "complete99-interrupted-forward-proof/v1",
            },
        ),
        "Interrupted forward observation proof path or digest changed",
    )
    observation = require_mapping(
        audit.get("interrupted_forward_observation"),
        "Interrupted forward observation receipt",
    )
    manifest = require_mapping(
        observation.get("database_manifest"),
        "Interrupted forward observation database manifest",
    )
    storage = require_mapping(
        observation.get("database_storage"),
        "Interrupted forward observation database storage",
    )
    validate_database_manifest(
        manifest,
        observation.get("database_manifest_sha256"),
        "Interrupted forward observation database manifest",
    )
    validate_database_storage(storage, "Interrupted forward observation database storage")
    require(
        observation.get("database_fingerprint")
        == recovery_identity["database_fingerprint"]
        and observation.get("database_manifest_sha256")
        == recovery_identity["database_manifest_sha256"]
        and (expected_manifest is None or exact_json_equal(manifest, expected_manifest))
        and (expected_storage is None or exact_json_equal(storage, expected_storage)),
        "Interrupted forward observation database identity changed",
    )
    recorded = observation.get("recorded_installed_plugin_sha256")
    require(
        recorded in {"", failed["installed_plugin_sha256"]}
        and exact_json_equal(
            observation,
            expected_interrupted_observation(
                failed,
                prior,
                recovery_identity["database_fingerprint"],
                manifest,
                recovery_identity["database_manifest_sha256"],
                storage,
                recorded,
            ),
        ),
        "Interrupted forward observation receipt conflicts with the reviewed state",
    )
    validate_interrupted_health_home_robots(
        audit,
        failed,
        prior,
        label="Interrupted forward observation",
    )


def load_interrupted_forward_proof(
    raw_path: str,
    repository_root: Path,
) -> dict[str, Any]:
    candidate = Path(raw_path)
    unresolved_path = (
        repository_root / candidate if not candidate.is_absolute() else candidate
    )
    path = unresolved_path.resolve()
    unresolved_proof_root = repository_root / "docs" / "recovery-proofs"
    proof_root = unresolved_proof_root.resolve()
    require(
        resolves_without_indirection(unresolved_proof_root, proof_root)
        and resolves_without_indirection(unresolved_path, path)
        and path.parent == proof_root
        and path.suffix == ".json",
        "Interrupted forward proof must be a direct reviewed JSON file under docs/recovery-proofs",
    )
    envelope = read_json(path, "Interrupted forward proof")
    schema = envelope.get("schema")
    require(
        set(envelope) == {"schema", "proof", "proof_sha256"}
        and schema
        in {
            "complete99-interrupted-forward-proof/v1",
            "complete99-interrupted-forward-proof/v2",
        },
        "Interrupted forward proof schema is invalid",
    )
    proof = require_mapping(envelope.get("proof"), "Interrupted forward proof payload")
    proof_sha256 = canonical_json_sha256(proof)
    require(
        envelope.get("proof_sha256") == proof_sha256,
        "Interrupted forward proof digest does not match",
    )
    failed = require_mapping(proof.get("failed_run"), "Interrupted forward failed run")
    prior = require_mapping(proof.get("prior_run"), "Interrupted forward prior run")
    require(
        set(proof)
        == (
            {"failed_run", "forward_adoption", "prior_run"}
            if schema == "complete99-interrupted-forward-proof/v2"
            else {"failed_run", "prior_run"}
        )
        and set(failed)
        == {
            "artifact_sha256",
            "baseline_database_fingerprint",
            "commit",
            "deploy_audit_path",
            "deploy_audit_sha256",
            "deployment_id",
            "installed_plugin_sha256",
            "recovery_audit_path",
            "recovery_audit_sha256",
            "run_id",
            "source_sha256",
            "version",
        }
        and set(prior)
        == {
            "active",
            "commit",
            "database_fingerprint",
            "database_version",
            "deploy_audit_path",
            "deploy_audit_sha256",
            "deployment_id",
            "plugin_sha256",
            "robots_sha256",
            "run_id",
            "sync_configured",
            "version",
        },
        "Interrupted forward proof identities are missing or contain unexpected fields",
    )
    for record, label in ((failed, "failed"), (prior, "prior")):
        require(
            type(record.get("run_id")) is int
            and record["run_id"] > 0
            and type(record.get("commit")) is str
            and COMMIT.fullmatch(record["commit"]) is not None
            and type(record.get("deployment_id")) is str
            and DEPLOYMENT_ID.fullmatch(record["deployment_id"]) is not None,
            f"Interrupted forward {label} run identity is invalid",
        )
    for record, fields, label in (
        (
            failed,
            (
                "artifact_sha256",
                "baseline_database_fingerprint",
                "deploy_audit_sha256",
                "installed_plugin_sha256",
                "recovery_audit_sha256",
                "source_sha256",
            ),
            "failed",
        ),
        (
            prior,
            (
                "database_fingerprint",
                "deploy_audit_sha256",
                "plugin_sha256",
                "robots_sha256",
            ),
            "prior",
        ),
    ):
        for field in fields:
            require_digest(record.get(field), f"Interrupted forward {label} {field}")
    require(
        type(failed.get("version")) is str
        and VERSION.fullmatch(failed["version"]) is not None
        and type(prior.get("version")) is str
        and VERSION.fullmatch(prior["version"]) is not None
        and prior.get("database_version") == prior["version"]
        and prior.get("active") is True
        and prior.get("sync_configured") is True
        and failed["deployment_id"] != prior["deployment_id"]
        and failed["run_id"] > prior["run_id"]
        and f"-{failed['run_id']}-" in failed["deployment_id"]
        and f"-{prior['run_id']}-" in prior["deployment_id"]
        and failed["commit"] != prior["commit"]
        and failed["version"] != prior["version"]
        and failed["installed_plugin_sha256"] != prior["plugin_sha256"]
        and failed["baseline_database_fingerprint"] == prior["database_fingerprint"],
        "Interrupted forward reviewed identities are invalid",
    )
    failed_audit = load_bound_interrupted_audit(
        failed["deploy_audit_path"],
        failed["deploy_audit_sha256"],
        repository_root,
        "Interrupted forward failed deploy",
    )
    recovery_audit = load_bound_interrupted_audit(
        failed["recovery_audit_path"],
        failed["recovery_audit_sha256"],
        repository_root,
        "Interrupted forward failed recovery",
    )
    prior_audit = load_bound_interrupted_audit(
        prior["deploy_audit_path"],
        prior["deploy_audit_sha256"],
        repository_root,
        "Interrupted forward prior deploy",
    )
    require(
        len(
            {
                failed["deploy_audit_path"],
                failed["recovery_audit_path"],
                prior["deploy_audit_path"],
            }
        )
        == 3,
        "Interrupted forward source audit paths must be distinct",
    )
    recovery_identity = validate_interrupted_source_audits(
        failed,
        prior,
        failed_audit,
        recovery_audit,
        prior_audit,
    )
    base_proof = {"failed_run": failed, "prior_run": prior}
    base_proof_sha256 = canonical_json_sha256(base_proof)
    adoption = proof.get("forward_adoption")
    if schema == "complete99-interrupted-forward-proof/v2":
        adoption = require_mapping(adoption, "Interrupted forward adoption")
        require(
            set(adoption)
            == {
                "observation_audit_path",
                "observation_audit_sha256",
                "observation_commit",
                "observation_proof_sha256",
                "observation_run_id",
                "observed_database_fingerprint",
                "observed_database_manifest",
                "observed_database_manifest_sha256",
                "observed_database_storage",
                "observed_deployment_id",
                "observed_plugin_sha256",
                "observed_robots_sha256",
                "observed_version",
                "schema",
                "target_artifact_sha256",
                "target_installed_plugin_sha256",
            }
            and adoption.get("schema")
            == "complete99-interrupted-forward-adoption/v1"
            and type(adoption.get("observation_run_id")) is int
            and adoption["observation_run_id"] > failed["run_id"]
            and type(adoption.get("observation_commit")) is str
            and COMMIT.fullmatch(adoption["observation_commit"]) is not None
            and adoption["observation_commit"]
            not in {failed["commit"], prior["commit"]}
            and adoption.get("observation_proof_sha256") == base_proof_sha256
            and adoption.get("target_artifact_sha256") == failed["artifact_sha256"]
            and adoption.get("target_installed_plugin_sha256")
            == failed["installed_plugin_sha256"]
            and adoption.get("observed_deployment_id") == failed["deployment_id"]
            and adoption.get("observed_plugin_sha256")
            == failed["installed_plugin_sha256"]
            and adoption.get("observed_robots_sha256") == prior["robots_sha256"]
            and adoption.get("observed_version") == failed["version"]
            and adoption.get("observed_database_fingerprint")
            == recovery_identity["database_fingerprint"]
            and adoption.get("observed_database_manifest_sha256")
            == recovery_identity["database_manifest_sha256"],
            "Interrupted forward v2 adoption identity is invalid",
        )
        for field in (
            "observation_audit_sha256",
            "observed_database_fingerprint",
            "observed_database_manifest_sha256",
            "observed_plugin_sha256",
            "observed_robots_sha256",
            "target_artifact_sha256",
            "target_installed_plugin_sha256",
        ):
            require_digest(adoption.get(field), f"Interrupted forward v2 {field}")
        manifest = require_mapping(
            adoption.get("observed_database_manifest"),
            "Interrupted forward v2 observed database manifest",
        )
        storage = require_mapping(
            adoption.get("observed_database_storage"),
            "Interrupted forward v2 observed database storage",
        )
        validate_database_manifest(
            manifest,
            adoption["observed_database_manifest_sha256"],
            "Interrupted forward v2 observed database manifest",
        )
        validate_database_storage(storage, "Interrupted forward v2 observed database storage")
        observation_audit = load_bound_interrupted_audit(
            adoption["observation_audit_path"],
            adoption["observation_audit_sha256"],
            repository_root,
            "Interrupted forward observation",
        )
        historical_path = (
            repository_root
            / "docs"
            / "recovery-proofs"
            / f"{failed['deployment_id']}.json"
        )
        historical = load_interrupted_forward_proof(str(historical_path), repository_root)
        historical_relative = historical_path.relative_to(repository_root).as_posix()
        require(
            historical.get("schema") == "complete99-interrupted-forward-proof/v1"
            and historical.get("path") == historical_relative
            and historical.get("proof_sha256") == base_proof_sha256
            and exact_json_equal(historical.get("proof"), base_proof),
            "Interrupted forward v2 historical proof does not match",
        )
        require(
            adoption["observation_audit_path"]
            not in {
                failed["deploy_audit_path"],
                failed["recovery_audit_path"],
                prior["deploy_audit_path"],
            },
            "Interrupted forward observation audit must be distinct from source evidence",
        )
        validate_interrupted_observation_audit(
            observation_audit,
            failed,
            prior,
            recovery_identity,
            historical_relative,
            base_proof_sha256,
            f"c99-recovery-probe-{adoption['observation_run_id']}-1",
            expected_commit=adoption["observation_commit"],
            expected_manifest=manifest,
            expected_storage=storage,
        )
    return {
        "base_proof_sha256": base_proof_sha256,
        "path": path.relative_to(repository_root.resolve()).as_posix(),
        "proof": proof,
        "proof_sha256": proof_sha256,
        "recovery_identity": recovery_identity,
        "schema": schema,
    }


def installed_zip_sha256(raw: bytes) -> str:
    entries: list[bytes] = []
    seen: set[str] = set()
    try:
        with zipfile.ZipFile(io.BytesIO(raw)) as archive:
            for info in archive.infolist():
                if info.is_dir():
                    continue
                path = PurePosixPath(info.filename)
                require(
                    len(path.parts) >= 2
                    and path.parts[0] == "complete99-platform"
                    and ".." not in path.parts,
                    "Interrupted forward package installed path is invalid",
                )
                relative = path.relative_to("complete99-platform").as_posix()
                require(
                    relative not in seen,
                    "Interrupted forward package installed path is duplicated",
                )
                seen.add(relative)
                digest = hashlib.sha256(archive.read(info)).hexdigest().encode("ascii")
                entries.append(relative.encode("utf-8") + b"\0" + digest)
    except (KeyError, OSError, RuntimeError, zipfile.BadZipFile) as error:
        raise AuditValidationError(
            "Interrupted forward package installed digest could not be computed"
        ) from error
    require(bool(entries), "Interrupted forward package contains no installed files")
    return hashlib.sha256(b"\n".join(sorted(entries))).hexdigest()


def validate_interrupted_forward_dist(
    dist: Path,
    loaded: dict[str, Any],
) -> dict[str, Any]:
    failed = loaded["proof"]["failed_run"]
    dist_root = dist.resolve()
    metadata_path = (dist_root / "complete99-platform-integrity.json").resolve()
    require(
        metadata_path.parent == dist_root,
        "Interrupted forward release metadata escaped its dist root",
    )
    metadata = read_json(metadata_path, "Interrupted forward release metadata")
    artifact_name = f"complete99-platform-{failed['version']}.zip"
    require(
        metadata.get("slug") == "complete99-platform"
        and metadata.get("type") == "plugin"
        and metadata.get("artifact") == artifact_name,
        "Interrupted forward release metadata is not allowlisted",
    )
    artifact = (dist_root / artifact_name).resolve()
    require(
        artifact.parent == dist_root,
        "Interrupted forward artifact escaped its dist root",
    )
    try:
        raw = artifact.read_bytes()
    except OSError as error:
        raise AuditValidationError(
            "Interrupted forward release package could not be loaded"
        ) from error
    artifact_sha256 = hashlib.sha256(raw).hexdigest()
    require(
        metadata.get("version") == failed["version"]
        and metadata.get("sha256") == failed["artifact_sha256"]
        and metadata.get("source_sha256") == failed["source_sha256"]
        and metadata.get("installed_sha256") == failed["installed_plugin_sha256"]
        and type(metadata.get("size")) is int
        and metadata["size"] == len(raw)
        and artifact_sha256 == failed["artifact_sha256"]
        and installed_zip_sha256(raw) == failed["installed_plugin_sha256"],
        "Interrupted forward proof does not match the exact reviewed release package",
    )
    return {
        "artifact": artifact_name,
        "installed_sha256": failed["installed_plugin_sha256"],
        "sha256": failed["artifact_sha256"],
        "source_sha256": failed["source_sha256"],
        "version": failed["version"],
    }


def validate_interrupted_adoption(
    value: Any,
    loaded: dict[str, Any],
) -> bool:
    adoption_audit = require_mapping(value, "Interrupted forward adoption")
    require(
        set(adoption_audit) == {"receipt", "status"},
        "Interrupted forward adoption fields are invalid",
    )
    proof = loaded["proof"]
    failed = proof["failed_run"]
    adoption = proof["forward_adoption"]
    receipt = require_mapping(
        adoption_audit.get("receipt"),
        "Interrupted forward adoption receipt",
    )
    expected_receipt_keys = {
        "adopted_forward_no_rollback",
        "cache_purge",
        "database_manifest",
        "database_manifest_sha256",
        "database_storage",
        "database_version",
        "deployment_id",
        "idempotent",
        "installed_plugin_sha256",
        "interrupted_forward_proof_sha256",
        "post_install_database_fingerprint",
        "stabilized",
        "stabilized_from_phase",
        "version",
    }
    idempotent = receipt.get("idempotent")
    require(
        set(receipt) == expected_receipt_keys
        and receipt.get("stabilized") is True
        and type(idempotent) is bool
        and receipt.get("adopted_forward_no_rollback") is True
        and receipt.get("stabilized_from_phase") == "installing"
        and receipt.get("version") == failed["version"]
        and receipt.get("database_version") == failed["version"]
        and receipt.get("deployment_id") == failed["deployment_id"]
        and receipt.get("installed_plugin_sha256")
        == failed["installed_plugin_sha256"]
        and receipt.get("post_install_database_fingerprint")
        == adoption["observed_database_fingerprint"]
        and receipt.get("interrupted_forward_proof_sha256")
        == loaded["proof_sha256"]
        and receipt.get("database_manifest_sha256")
        == adoption["observed_database_manifest_sha256"]
        and exact_json_equal(
            receipt.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        and exact_json_equal(
            receipt.get("database_storage"),
            adoption["observed_database_storage"],
        )
        and exact_json_equal(
            receipt.get("cache_purge"),
            {"deferred_to_finalize": True},
        ),
        "Interrupted forward stabilization receipt is invalid",
    )
    validate_database_manifest(
        receipt.get("database_manifest"),
        receipt.get("database_manifest_sha256"),
        "Interrupted forward adoption receipt database",
    )
    validate_database_storage(
        receipt.get("database_storage"),
        "Interrupted forward adoption receipt database",
    )
    require(
        exact_json_equal(
            adoption_audit.get("status"),
            {
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
            },
        ),
        "Interrupted forward adoption status is invalid",
    )
    return idempotent


def validate_interrupted_finalize_resume(
    value: Any,
    loaded: dict[str, Any],
) -> str:
    receipt = require_mapping(value, "Interrupted forward finalize resume")
    proof = loaded["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof["forward_adoption"]
    phase = receipt.get("phase")
    state_exists = receipt.get("state_exists")
    require(
        phase in {"committing", "commit_failed", "committed", "cleanup_failed"}
        and type(state_exists) is bool
        and (state_exists or phase in {"committed", "cleanup_failed"})
        and exact_json_equal(
            receipt,
            {
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
                "database_fingerprint": adoption[
                    "observed_database_fingerprint"
                ],
                "database_manifest_sha256": adoption[
                    "observed_database_manifest_sha256"
                ],
                "deployment_id": failed["deployment_id"],
                "installed_plugin_sha256": failed["installed_plugin_sha256"],
                "interrupted_forward_proof_sha256": loaded["proof_sha256"],
                "phase": phase,
                "schema": "complete99-interrupted-forward-finalize-resume/v1",
                "state_exists": state_exists,
                "version": failed["version"],
            },
        ),
        "Interrupted forward finalize-resume receipt is invalid",
    )
    return str(phase)


def validate_interrupted_forward_recovery_audit(
    audit: dict[str, Any],
    loaded: dict[str, Any],
    expected_probe_id: str,
) -> None:
    proof = loaded["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof["forward_adoption"]
    validate_interrupted_common_audit(
        audit,
        failed["deployment_id"],
        "Interrupted forward recovery",
    )
    base_keys = {
            "adopted_forward_no_rollback",
            "bootstrap_cleanup",
            "bridge_site_identity",
            "cleanup",
            "decision",
            "deployment_id",
            "discovery",
            "finalize",
            "finished_at",
            "health",
            "identity",
            "interrupted_forward_proof",
            "local_test",
            "rendered_home",
            "result",
            "robots",
            "started_at",
        }
    has_pre_adoption = any(key.startswith("pre_adoption_") for key in audit)
    has_adoption = "interrupted_forward_adoption" in audit
    has_finalize_resume = "interrupted_forward_finalize_resume" in audit
    require(
        has_adoption is not has_finalize_resume,
        "Interrupted forward recovery requires exactly one adoption/finalize-resume receipt",
    )
    expected_keys = base_keys | (
        {"interrupted_forward_adoption"}
        | {
            "pre_adoption_health",
            "pre_adoption_observation",
            "pre_adoption_rendered_home",
            "pre_adoption_robots",
        }
        if has_pre_adoption
        else (
            {"interrupted_forward_adoption"}
            if has_adoption
            else {"interrupted_forward_finalize_resume"}
        )
    )
    if "stale_probe_recovery" in audit:
        expected_keys.add("stale_probe_recovery")
    require(
        set(audit) == expected_keys
        and audit.get("result") == "recovered"
        and audit.get("decision") == "adopt_interrupted_forward"
        and audit.get("adopted_forward_no_rollback") is True,
        "Interrupted forward recovery used the wrong terminal path",
    )
    expected_owner_phase = (
        "installing"
        if has_pre_adoption
        else (
            "installed"
            if has_adoption
            else validate_interrupted_finalize_resume(
                audit.get("interrupted_forward_finalize_resume"),
                loaded,
            )
        )
    )
    validate_interrupted_discovery(
        audit.get("discovery"),
        failed["deployment_id"],
        expected_probe_id,
        expected_owner_phase,
        "Interrupted forward recovery discovery",
    )
    if "stale_probe_recovery" in audit:
        validate_stale_interrupted_probe_recovery(
            audit["stale_probe_recovery"],
            loaded,
        )
    require(
        exact_json_equal(
            audit.get("interrupted_forward_proof"),
            {
                "path": loaded["path"],
                "proof_sha256": loaded["proof_sha256"],
                "schema": "complete99-interrupted-forward-proof/v2",
            },
        ),
        "Interrupted forward recovery proof path or digest changed",
    )
    if has_pre_adoption:
        pre_adoption = require_mapping(
            audit.get("pre_adoption_observation"),
            "Interrupted forward pre-adoption observation",
        )
        require(
            exact_json_equal(
                pre_adoption,
                expected_interrupted_observation(
                    failed,
                    prior,
                    adoption["observed_database_fingerprint"],
                    adoption["observed_database_manifest"],
                    adoption["observed_database_manifest_sha256"],
                    adoption["observed_database_storage"],
                    pre_adoption.get("recorded_installed_plugin_sha256"),
                ),
            ),
            "Interrupted forward pre-adoption observation is invalid",
        )
        require(
            pre_adoption.get("recorded_installed_plugin_sha256")
            in {"", failed["installed_plugin_sha256"]},
            "Interrupted forward pre-adoption plugin receipt is invalid",
        )
        validate_interrupted_health_home_robots(
            audit,
            failed,
            prior,
            prefix="pre_adoption_",
            label="Interrupted forward pre-adoption",
        )
    validate_database_manifest(
        adoption["observed_database_manifest"],
        adoption["observed_database_manifest_sha256"],
        "Interrupted forward adopted database",
    )
    validate_database_storage(
        adoption["observed_database_storage"],
        "Interrupted forward adopted database",
    )
    if has_adoption:
        require(
            validate_interrupted_adoption(
                audit.get("interrupted_forward_adoption"), loaded
            )
            is (not has_pre_adoption),
            "Interrupted forward adoption idempotency does not match its audit shape",
        )
    validate_interrupted_health_home_robots(
        audit,
        failed,
        prior,
        label="Interrupted forward recovery",
    )
    validate_finalize(audit.get("finalize"), "Interrupted forward recovery finalize")


def expected_interrupted_finalized_attestation(
    loaded: dict[str, Any],
    probe_id: str,
) -> dict[str, Any]:
    proof = loaded["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof["forward_adoption"]
    return {
        "active": True,
        "already_finalized": True,
        "current_database_version": failed["version"],
        "current_deployment": failed["deployment_id"],
        "database_fingerprint": adoption["observed_database_fingerprint"],
        "database_manifest": adoption["observed_database_manifest"],
        "database_manifest_sha256": adoption[
            "observed_database_manifest_sha256"
        ],
        "database_storage": adoption["observed_database_storage"],
        "finalized_deployment_id": failed["deployment_id"],
        "migration_failed": False,
        "migration_invariants_valid": True,
        "plugin_sha256": failed["installed_plugin_sha256"],
        "probe_deployment_id": probe_id,
        "probe_lock_phase": "reserved",
        "proof_sha256": loaded["proof_sha256"],
        "robots_sha256": prior["robots_sha256"],
        "runtime_loaded": True,
        "schema": "complete99-interrupted-forward-finalized-attestation/v1",
        "sync_configured": True,
        "target_artifacts_absent": True,
        "target_state_absent": True,
        "version": failed["version"],
    }


def validate_interrupted_finalized_attestation_receipt(
    value: Any,
    loaded: dict[str, Any],
    probe_id: str,
    label: str,
) -> None:
    attestation = require_mapping(value, label)
    validate_database_manifest(
        attestation.get("database_manifest"),
        attestation.get("database_manifest_sha256"),
        f"{label} database",
    )
    validate_database_storage(
        attestation.get("database_storage"),
        f"{label} database",
    )
    require(
        exact_json_equal(
            attestation,
            expected_interrupted_finalized_attestation(loaded, probe_id),
        ),
        f"{label} receipt is invalid",
    )


def validate_interrupted_probe_finalize(value: Any, label: str) -> None:
    probe_finalize = require_mapping(value, label)
    require(
        set(probe_finalize)
        == {
            "cache_purge",
            "finalized",
            "lock_released",
            "response_recovered",
            "state_removed",
        }
        and isinstance(probe_finalize.get("cache_purge"), dict)
        and probe_finalize.get("finalized") is True
        and probe_finalize.get("lock_released") is True
        and type(probe_finalize.get("response_recovered")) is bool
        and probe_finalize.get("state_removed") is True,
        f"{label} is invalid",
    )


def validate_stale_interrupted_probe_recovery(
    value: Any,
    loaded: dict[str, Any],
) -> None:
    evidence = require_mapping(value, "Interrupted forward stale probe recovery")
    require(
        set(evidence)
        == {
            "bootstrap_cleanup",
            "bridge_site_identity",
            "cleanup",
            "interrupted_forward_proof_sha256",
            "probe_finalize",
            "reservation_status",
        },
        "Interrupted forward stale probe recovery fields are invalid",
    )
    status = require_mapping(
        evidence.get("reservation_status"),
        "Interrupted forward stale probe reservation",
    )
    probe_id = status.get("deployment_id")
    require(
        type(probe_id) is str
        and probe_id.startswith("c99-recovery-probe-")
        and DEPLOYMENT_ID.fullmatch(probe_id) is not None
        and exact_json_equal(
            status,
            {
                "adopted_forward_no_rollback": False,
                "deployment_id": probe_id,
                "interrupted_forward_candidate": False,
                "lock_owned": True,
                "no_rollback_artifacts": True,
                "phase": "reserved",
                "process_lock_available": True,
                "recovery_ready": True,
                "state_exists": False,
            },
        ),
        "Interrupted forward stale probe reservation is invalid",
    )
    validate_bootstrap_cleanup(
        evidence.get("bootstrap_cleanup"),
        "Interrupted forward stale probe bootstrap cleanup",
    )
    require(
        exact_json_equal(
            evidence.get("bridge_site_identity"),
            {
                "home_host": "complete99.co.il",
                "rest_host": "complete99.co.il",
                "siteurl_host": "complete99.co.il",
            },
        ),
        "Interrupted forward stale probe bridge identity is invalid",
    )
    require(
        evidence.get("interrupted_forward_proof_sha256")
        == loaded["proof_sha256"],
        "Interrupted forward stale probe proof digest changed",
    )
    validate_interrupted_probe_finalize(
        evidence.get("probe_finalize"),
        "Interrupted forward stale probe finalization",
    )
    validate_interrupted_cleanup(
        evidence.get("cleanup"),
        "Interrupted forward stale probe cleanup",
    )


def validate_interrupted_forward_already_finalized_audit(
    audit: dict[str, Any],
    loaded: dict[str, Any],
    expected_probe_id: str,
) -> None:
    """Authenticate the proof-bound, read-only already-finalized v2 path."""
    proof = loaded["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof["forward_adoption"]
    validate_interrupted_common_audit(
        audit,
        expected_probe_id,
        "Interrupted forward finalized attestation",
    )
    expected_audit_keys = {
            "bootstrap_cleanup",
            "bridge_site_identity",
            "cleanup",
            "decision",
            "deployment_id",
            "discovery",
            "finished_at",
            "health",
            "identity",
            "interrupted_forward_finalized_attestation",
            "interrupted_forward_proof",
            "local_test",
            "probe_finalize",
            "rendered_home",
            "result",
            "robots",
            "started_at",
        }
    if "stale_probe_recovery" in audit:
        expected_audit_keys.add("stale_probe_recovery")
    require(
        set(audit) == expected_audit_keys
        and audit.get("decision")
        == "attest_interrupted_forward_finalized"
        and audit.get("result") == "already-recovered",
        "Interrupted forward finalized attestation used the wrong terminal path",
    )
    require(
        exact_json_equal(
            audit.get("discovery"),
            {
                "probe_id": expected_probe_id,
                "probe_lock_retained_for_attestation": True,
                "result": "no-owner",
            },
        ),
        "Interrupted forward finalized discovery is invalid",
    )
    require(
        exact_json_equal(
            audit.get("interrupted_forward_proof"),
            {
                "path": loaded["path"],
                "proof_sha256": loaded["proof_sha256"],
                "schema": "complete99-interrupted-forward-proof/v2",
            },
        ),
        "Interrupted forward finalized proof path or digest changed",
    )
    validate_interrupted_finalized_attestation_receipt(
        audit.get("interrupted_forward_finalized_attestation"),
        loaded,
        expected_probe_id,
        "Interrupted forward finalized attestation",
    )
    validate_interrupted_health_home_robots(
        audit,
        failed,
        prior,
        label="Interrupted forward finalized attestation",
    )
    validate_interrupted_probe_finalize(
        audit.get("probe_finalize"),
        "Interrupted forward probe finalization",
    )
    if "stale_probe_recovery" in audit:
        validate_stale_interrupted_probe_recovery(
            audit["stale_probe_recovery"],
            loaded,
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
    require(
        audit["finished_at"] >= audit["started_at"],
        "Observation audit finished before it started",
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
        require(
            exact_json_equal(observation.get(key), expected),
            f"Observation identity failed for {key}",
        )
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
        exact_json_equal(
            initial_status.get("database_storage"),
            observation.get("database_storage"),
        ),
        "Observation status storage and attestation differ",
    )
    validate_observation_cleanup(
        audit.get("cleanup"),
        "Observation bridge cleanup",
    )


def validate_v2_database_reconciliation(
    proof: dict[str, Any],
    proof_root: Path,
    repository_root: Path,
) -> None:
    failed = require_mapping(proof.get("failed_run"), "Failed-run proof")
    prior = require_mapping(proof.get("prior_run"), "Prior-run proof")
    reconciliation = require_mapping(
        proof.get("database_reconciliation"),
        "Reviewed database reconciliation",
    )
    expected_keys = {
        "attestation_audit_sha256",
        "attestation_path",
        "attestation_run_id",
        "attestation_sha256",
        "attestation_source_commit",
        "baseline_database_fingerprint",
        "expected_reconciled_database_fingerprint",
        "mode",
        "observed_database_fingerprint",
        "observed_deployment",
        "preserved_manifest",
        "preserved_manifest_sha256",
        "prior_proof_sha256",
        "schema",
        "target_deployment",
        "transactional_storage",
    }
    require(
        set(reconciliation) == expected_keys,
        "Reviewed database reconciliation fields are invalid",
    )
    require(
        reconciliation.get("schema")
        == "complete99-orphaned-database-reconciliation/v1"
        and reconciliation.get("mode")
        == "preserve-reviewed-drift-marker-only",
        "Reviewed database reconciliation identity is invalid",
    )
    attestation_run_id = reconciliation.get("attestation_run_id")
    require(
        type(attestation_run_id) is int
        and attestation_run_id > failed.get("run_id", 0),
        "Reviewed attestation run ID is invalid",
    )
    source_commit = reconciliation.get("attestation_source_commit")
    require(
        type(source_commit) is str
        and COMMIT.fullmatch(source_commit) is not None
        and source_commit
        not in {failed.get("commit"), prior.get("commit")},
        "Reviewed attestation source commit is invalid",
    )
    for key in (
        "attestation_audit_sha256",
        "attestation_sha256",
        "baseline_database_fingerprint",
        "expected_reconciled_database_fingerprint",
        "observed_database_fingerprint",
        "preserved_manifest_sha256",
        "prior_proof_sha256",
    ):
        require_digest(
            reconciliation.get(key),
            f"Reviewed database reconciliation {key}",
        )
    validate_database_manifest(
        reconciliation.get("preserved_manifest"),
        reconciliation.get("preserved_manifest_sha256"),
        "Reviewed preserved database manifest",
    )
    validate_database_storage(
        reconciliation.get("transactional_storage"),
        "Reviewed transactional database storage",
    )

    base_proof = {"failed_run": failed, "prior_run": prior}
    canonical_base = json.dumps(
        base_proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    prior_proof_sha256 = hashlib.sha256(canonical_base).hexdigest()
    baseline_database_fingerprint = prior.get("database_fingerprint")
    observed_database_fingerprint = reconciliation.get(
        "observed_database_fingerprint"
    )
    reconciled_database_fingerprint = reconciliation.get(
        "expected_reconciled_database_fingerprint"
    )
    require(
        reconciliation.get("prior_proof_sha256") == prior_proof_sha256
        and reconciliation.get("observed_deployment")
        == failed.get("deployment_id")
        and reconciliation.get("target_deployment")
        == prior.get("deployment_id")
        and reconciliation.get("baseline_database_fingerprint")
        == baseline_database_fingerprint
        and len(
            {
                baseline_database_fingerprint,
                observed_database_fingerprint,
                reconciled_database_fingerprint,
            }
        )
        == 3,
        "Reviewed database reconciliation conflicts with its historical proof",
    )

    unresolved_historical_path = (
        proof_root / f"{failed.get('deployment_id', '')}.json"
    )
    historical_path = unresolved_historical_path.resolve()
    require(
        resolves_without_indirection(
            unresolved_historical_path,
            historical_path,
        )
        and historical_path.parent == proof_root
        and historical_path.is_file(),
        "Historical recovery proof bound by v2 is missing",
    )
    historical_envelope = read_json(historical_path, "Historical recovery proof")
    require(
        set(historical_envelope) == {"schema", "proof", "proof_sha256"}
        and historical_envelope.get("schema")
        == "complete99-orphaned-rollback-proof/v1"
        and exact_json_equal(historical_envelope.get("proof"), base_proof)
        and historical_envelope.get("proof_sha256") == prior_proof_sha256,
        "Historical recovery proof does not match the v2 base proof",
    )

    raw_attestation_path = reconciliation.get("attestation_path")
    require(
        type(raw_attestation_path) is str and bool(raw_attestation_path),
        "Reviewed attestation path is invalid",
    )
    relative_attestation = Path(raw_attestation_path)
    expected_relative_root = Path("docs/recovery-proofs/observations")
    try:
        evidence_relative = relative_attestation.relative_to(
            expected_relative_root
        )
    except ValueError:
        evidence_relative = None
    unresolved_attestation_root = repository_root / expected_relative_root
    attestation_root = unresolved_attestation_root.resolve()
    unresolved_attestation_path = repository_root / relative_attestation
    attestation_path = unresolved_attestation_path.resolve()
    symlink_in_evidence_path = False
    evidence_cursor = repository_root
    for part in relative_attestation.parts:
        evidence_cursor /= part
        if evidence_cursor.is_symlink():
            symlink_in_evidence_path = True
            break
    require(
        not relative_attestation.is_absolute()
        and raw_attestation_path.startswith(
            "docs/recovery-proofs/observations/"
        )
        and relative_attestation.as_posix() == raw_attestation_path
        and ".." not in raw_attestation_path
        and ".." not in relative_attestation.parts
        and resolves_without_indirection(
            unresolved_attestation_root,
            attestation_root,
        )
        and resolves_without_indirection(
            unresolved_attestation_path,
            attestation_path,
        )
        and attestation_root in attestation_path.parents
        and raw_attestation_path.endswith(".json")
        and evidence_relative is not None
        and not symlink_in_evidence_path,
        "Reviewed attestation escaped its evidence root",
    )
    try:
        raw_attestation = attestation_path.read_bytes()
        attestation = parse_json(
            raw_attestation.decode("utf-8"),
            "Reviewed observation attestation",
        )
    except (OSError, UnicodeDecodeError) as error:
        raise AuditValidationError(
            "Reviewed observation attestation could not be read"
        ) from error
    attestation_sha256 = hashlib.sha256(raw_attestation).hexdigest()
    require(
        reconciliation.get("attestation_sha256") == attestation_sha256
        and reconciliation.get("attestation_audit_sha256")
        == attestation_sha256,
        "Reviewed observation attestation digest does not match",
    )

    expected_probe_id = f"c99-recovery-probe-{attestation_run_id}-1"
    validate_orphaned_observation_audit(
        attestation,
        historical_path,
        base_proof,
        prior_proof_sha256,
        repository_root,
        expected_probe_id,
    )
    observation = require_mapping(
        attestation.get("orphaned_rollback_observation"),
        "Reviewed observation payload",
    )
    require(
        observation.get("current_deployment") == failed.get("deployment_id")
        and observation.get("current_database_fingerprint")
        == observed_database_fingerprint
        and observation.get("projected_deployment_id")
        == prior.get("deployment_id")
        and observation.get("projected_database_fingerprint")
        == reconciled_database_fingerprint
        and observation.get("historical_baseline_database_fingerprint")
        == baseline_database_fingerprint
        and observation.get("historical_baseline_matches_projection") is False
        and exact_json_equal(
            observation.get("database_manifest"),
            reconciliation.get("preserved_manifest"),
        )
        and observation.get("database_manifest_sha256")
        == reconciliation.get("preserved_manifest_sha256")
        and exact_json_equal(
            observation.get("database_storage"),
            reconciliation.get("transactional_storage"),
        ),
        "Reviewed observation does not authorize the v2 database reconciliation",
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
    expected_probe_id: str,
) -> None:
    failed = require_mapping(proof.get("failed_run"), "Failed-run proof")
    prior = require_mapping(proof.get("prior_run"), "Prior-run proof")
    reconciliation_value = proof.get("database_reconciliation")
    proof_is_v2 = isinstance(reconciliation_value, dict)
    reconciliation = (
        require_mapping(
            reconciliation_value,
            "Reviewed database reconciliation",
        )
        if proof_is_v2
        else {}
    )
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

    if proof_is_v2:
        fresh_fields = {
            "orphaned_rollback_reconciliation",
            "reconciled_status",
        }
        receipt_fields = {"initial_orphaned_rollback_receipt"}
        expected_fields = {
            "bootstrap_cleanup",
            "bridge_site_identity",
            "cleanup",
            "decision",
            "deployment_id",
            "discovery",
            "finalize",
            "finished_at",
            "health",
            "identity",
            "initial_status",
            "local_test",
            "orphaned_rollback_proof",
            "orphaned_rollback_robots",
            "pre_finalize_orphaned_identity",
            "rendered_home",
            "result",
            "started_at",
        }
        has_fresh_shape = fresh_fields <= set(audit) and not (
            receipt_fields & set(audit)
        )
        has_receipt_shape = receipt_fields <= set(audit) and not (
            fresh_fields & set(audit)
        )
        require(
            has_fresh_shape != has_receipt_shape,
            "V2 recovery audit must contain exactly one terminal receipt path",
        )
        expected_fields |= fresh_fields if has_fresh_shape else receipt_fields
        require(
            set(audit) == expected_fields,
            "V2 recovery audit contains unexpected or missing fields",
        )
        require(audit.get("local_test") is False, "V2 recovery audit is not production-only")
        for key in ("started_at", "finished_at"):
            value = audit.get(key)
            require(
                type(value) is str and UTC_TIMESTAMP.fullmatch(value) is not None,
                f"V2 recovery audit {key} is invalid",
            )
        require(
            audit["finished_at"] >= audit["started_at"],
            "V2 recovery audit finished before it started",
        )
        validate_bootstrap_cleanup(
            audit.get("bootstrap_cleanup"),
            "V2 recovery bootstrap cleanup",
        )
        bridge_identity = require_mapping(
            audit.get("bridge_site_identity"),
            "V2 recovery bridge identity",
        )
        require(
            bridge_identity
            == {
                "home_host": "complete99.co.il",
                "rest_host": "complete99.co.il",
                "siteurl_host": "complete99.co.il",
            },
            "V2 recovery bridge identity changed",
        )
        identity = require_mapping(audit.get("identity"), "V2 recovery identity")
        require(
            set(identity) == {"id", "roles", "site_identity"}
            and type(identity.get("id")) is int
            and identity.get("id") > 0
            and isinstance(identity.get("roles"), list)
            and all(type(role) is str and role for role in identity["roles"]),
            "V2 recovery authentication identity is invalid",
        )
        site_identity = require_mapping(
            identity.get("site_identity"),
            "V2 recovery REST identity",
        )
        require(
            site_identity
            == {
                "home": "https://complete99.co.il",
                "url": "https://complete99.co.il",
            },
            "V2 recovery REST identity changed",
        )

    discovery = require_mapping(audit.get("discovery"), "Recovery discovery")
    require(
        discovery.get("result") == "owner-discovered"
        and discovery.get("owner_deployment_id") == expected_deployment_id,
        "Recovery discovery did not find the proof's exact lock owner",
    )
    if proof_is_v2:
        require(
            set(discovery)
            == {
                "bootstrap_cleanup",
                "cleanup",
                "owner_deployment_id",
                "owner_phase",
                "probe_id",
                "result",
            }
            and discovery.get("probe_id") == expected_probe_id
            and discovery.get("owner_phase")
            in {"rolling_back", "committed", "cleanup_failed"},
            "V2 recovery discovery identity is invalid",
        )
        validate_bootstrap_cleanup(
            discovery.get("bootstrap_cleanup"),
            "V2 discovery bootstrap cleanup",
        )
        validate_observation_cleanup(
            discovery.get("cleanup"),
            "V2 discovery bridge cleanup",
        )
    else:
        validate_cleanup(discovery.get("cleanup"), "Discovery bridge cleanup")

    expected_path = proof_path.relative_to(repository_root.resolve()).as_posix()
    proof_record = require_mapping(
        audit.get("orphaned_rollback_proof"),
        "Recovery proof audit",
    )
    if proof_is_v2:
        require(
            set(proof_record) == {"path", "proof_sha256"},
            "V2 recovery proof audit fields are invalid",
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
    if proof_is_v2:
        require(
            discovery.get("owner_phase")
            == (
                "rolling_back"
                if has_reconciliation
                else initial_status.get("phase")
            ),
            "V2 discovery phase differs from its recovery path",
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
    committed_database_fingerprint = (
        require_digest(
            reconciliation.get("expected_reconciled_database_fingerprint"),
            "Reviewed reconciled database fingerprint",
        )
        if proof_is_v2
        else prior_database_fingerprint
    )
    prior_robots_sha256 = require_digest(prior.get("robots_sha256"), "Prior robots.txt")

    if proof_is_v2:
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
            "V2 initial status fields are invalid",
        )
        require(
            initial_status.get("state_exists") is False
            and initial_status.get("lock_owned") is True
            and initial_status.get("process_lock_available") is True
            and initial_status.get("projected_deployment_id")
            == prior_deployment_id
            and initial_status.get("projected_database_fingerprint")
            == committed_database_fingerprint
            and initial_status.get("database_manifest_sha256")
            == reconciliation.get("preserved_manifest_sha256"),
            "V2 initial status does not match the reviewed database state",
        )
        validate_database_storage(
            initial_status.get("database_storage"),
            "V2 initial database storage",
        )
        require(
            exact_json_equal(
                initial_status.get("database_storage"),
                reconciliation.get("transactional_storage"),
            ),
            "V2 initial database storage changed",
        )
        pre_finalize_identity = require_mapping(
            audit.get("pre_finalize_orphaned_identity"),
            "V2 pre-finalize orphaned identity",
        )
        require(
            set(pre_finalize_identity)
            == {
                "phase",
                "state_exists",
                "lock_owned",
                "recovery_ready",
                "process_lock_available",
                "current_active",
                "current_target_dir_exists",
                "current_plugin_main_exists",
                "current_version",
                "current_database_version",
                "current_deployment",
                "current_plugin_sha256",
                "current_sync_configured",
                "current_robots_sha256",
                "database_fingerprint",
                "database_manifest_sha256",
            },
            "V2 pre-finalize identity fields are invalid",
        )
        require(
            pre_finalize_identity.get("phase")
            == (
                "committed"
                if has_reconciliation
                else initial_status.get("phase")
            )
            and pre_finalize_identity.get("state_exists") is False
            and pre_finalize_identity.get("lock_owned") is True
            and pre_finalize_identity.get("recovery_ready") is False
            and pre_finalize_identity.get("process_lock_available") is True
            and pre_finalize_identity.get("current_active") is True
            and pre_finalize_identity.get("current_target_dir_exists") is True
            and pre_finalize_identity.get("current_plugin_main_exists") is True
            and pre_finalize_identity.get("current_version") == prior_version
            and pre_finalize_identity.get("current_database_version")
            == prior_version
            and pre_finalize_identity.get("current_deployment")
            == prior_deployment_id
            and pre_finalize_identity.get("current_plugin_sha256")
            == prior_plugin_sha256
            and pre_finalize_identity.get("current_sync_configured") is True
            and pre_finalize_identity.get("current_robots_sha256")
            == prior_robots_sha256
            and pre_finalize_identity.get("database_fingerprint")
            == committed_database_fingerprint
            and pre_finalize_identity.get("database_manifest_sha256")
            == reconciliation.get("preserved_manifest_sha256"),
            "V2 pre-finalize identity differs from the reviewed release",
        )

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

        if proof_is_v2:
            expected_reconciliation_keys = {
                "evidence_directory_exists",
                "evidence_directory_sha256",
                "attestation_audit_sha256",
                "attestation_run_id",
                "attestation_sha256",
                "attestation_source_commit",
                "historical_baseline_database_fingerprint",
                "lock_retained",
                "marker_corrected",
                "marker_rows_affected",
                "marker_transition",
                "mode",
                "observed_database_fingerprint",
                "phase",
                "preserved_manifest_sha256",
                "prior_proof_sha256",
                "proof_sha256",
                "receipt_schema",
                "receipt_sha256",
                "reconciled_database_fingerprint",
                "response_recovered",
            }
            require(
                set(reconciliation) == expected_reconciliation_keys,
                "V2 reconciliation audit fields are invalid",
            )
            marker_rows_affected = reconciliation.get("marker_rows_affected")
            marker_transition = reconciliation.get("marker_transition")
            marker_corrected = reconciliation.get("marker_corrected")
            require(
                type(marker_rows_affected) is int
                and marker_rows_affected in {0, 1}
                and marker_transition in {"corrected", "already-correct"}
                and type(marker_corrected) is bool
                and type(reconciliation.get("response_recovered")) is bool
                and marker_corrected is (marker_rows_affected == 1)
                and (marker_transition == "corrected")
                is (marker_rows_affected == 1),
                "V2 reconciliation marker transition is invalid",
            )
            require(
                reconciliation.get("receipt_schema")
                == "complete99-orphaned-rollback-receipt/v2"
                and reconciliation.get(
                    "historical_baseline_database_fingerprint"
                )
                == prior_database_fingerprint
                and reconciliation.get("observed_database_fingerprint")
                == proof["database_reconciliation"].get(
                    "observed_database_fingerprint"
                )
                and reconciliation.get("reconciled_database_fingerprint")
                == committed_database_fingerprint
                and reconciliation.get("preserved_manifest_sha256")
                == proof["database_reconciliation"].get(
                    "preserved_manifest_sha256"
                )
                and reconciliation.get("mode")
                == proof["database_reconciliation"].get("mode")
                and reconciliation.get("prior_proof_sha256")
                == proof["database_reconciliation"].get("prior_proof_sha256")
                and reconciliation.get("attestation_run_id")
                == proof["database_reconciliation"].get("attestation_run_id")
                and reconciliation.get("attestation_sha256")
                == proof["database_reconciliation"].get("attestation_sha256")
                and reconciliation.get("attestation_audit_sha256")
                == proof["database_reconciliation"].get(
                    "attestation_audit_sha256"
                )
                and reconciliation.get("attestation_source_commit")
                == proof["database_reconciliation"].get(
                    "attestation_source_commit"
                ),
                "V2 reconciliation audit differs from the reviewed proof",
            )
            expected_initial_database_fingerprint = (
                proof["database_reconciliation"][
                    "observed_database_fingerprint"
                ]
                if marker_rows_affected == 1
                else committed_database_fingerprint
            )
            require(
                initial_status.get("phase") == "rolling_back"
                and initial_status.get("recovery_ready") is True
                and initial_status.get("database_fingerprint")
                == expected_initial_database_fingerprint,
                "V2 reconciliation did not start from an authorized marker state",
            )

        reconciled = require_mapping(audit.get("reconciled_status"), "Reconciled status")
        require(reconciled.get("phase") == "committed", "Reconciled status was not committed")
        require(reconciled.get("state_exists") is False, "Reconciled state journal remained")
        require(reconciled.get("lock_owned") is True, "Reconciled lock was not retained")
        if proof_is_v2:
            expected_status_keys = {
                "current_deployment",
                "database_fingerprint",
                "database_manifest_sha256",
                "expected_sha256",
                "expected_version",
                "installed_plugin_sha256",
                "lock_owned",
                "orphaned_historical_baseline_database_fingerprint",
                "orphaned_marker_rows_affected",
                "orphaned_marker_transition",
                "orphaned_observed_database_fingerprint",
                "orphaned_preserved_manifest_sha256",
                "orphaned_reconciliation_mode",
                "orphaned_prior_proof_sha256",
                "orphaned_attestation_run_id",
                "orphaned_attestation_sha256",
                "orphaned_attestation_audit_sha256",
                "orphaned_attestation_source_commit",
                "orphaned_recovery_proof_sha256",
                "orphaned_recovery_receipt_schema",
                "orphaned_recovery_receipt_sha256",
                "phase",
                "post_install_database_fingerprint",
                "state_exists",
            }
            require(
                set(reconciled) == expected_status_keys,
                "V2 reconciled status fields are invalid",
            )
            expected_status = {
                "current_deployment": prior_deployment_id,
                "database_fingerprint": committed_database_fingerprint,
                "database_manifest_sha256": proof["database_reconciliation"][
                    "preserved_manifest_sha256"
                ],
                "expected_sha256": failed.get("artifact_sha256"),
                "expected_version": failed.get("candidate_version"),
                "installed_plugin_sha256": failed.get(
                    "candidate_plugin_sha256"
                ),
                "lock_owned": True,
                "orphaned_historical_baseline_database_fingerprint": prior_database_fingerprint,
                "orphaned_marker_rows_affected": reconciliation[
                    "marker_rows_affected"
                ],
                "orphaned_marker_transition": reconciliation[
                    "marker_transition"
                ],
                "orphaned_observed_database_fingerprint": proof[
                    "database_reconciliation"
                ]["observed_database_fingerprint"],
                "orphaned_preserved_manifest_sha256": proof[
                    "database_reconciliation"
                ]["preserved_manifest_sha256"],
                "orphaned_reconciliation_mode": proof[
                    "database_reconciliation"
                ]["mode"],
                "orphaned_prior_proof_sha256": proof[
                    "database_reconciliation"
                ]["prior_proof_sha256"],
                "orphaned_attestation_run_id": proof[
                    "database_reconciliation"
                ]["attestation_run_id"],
                "orphaned_attestation_sha256": proof[
                    "database_reconciliation"
                ]["attestation_sha256"],
                "orphaned_attestation_audit_sha256": proof[
                    "database_reconciliation"
                ]["attestation_audit_sha256"],
                "orphaned_attestation_source_commit": proof[
                    "database_reconciliation"
                ]["attestation_source_commit"],
                "orphaned_recovery_proof_sha256": proof_sha256,
                "orphaned_recovery_receipt_schema": "complete99-orphaned-rollback-receipt/v2",
                "orphaned_recovery_receipt_sha256": reconciliation[
                    "receipt_sha256"
                ],
                "post_install_database_fingerprint": failed.get(
                    "candidate_database_fingerprint"
                ),
                "phase": "committed",
                "state_exists": False,
            }
            require(
                exact_json_equal(reconciled, expected_status),
                "V2 reconciled status differs from its durable receipt",
            )
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
        if proof_is_v2:
            receipt_keys.update(
                {
                    "expected_sha256",
                    "expected_version",
                    "installed_plugin_sha256",
                    "post_install_database_fingerprint",
                    "orphaned_historical_baseline_database_fingerprint",
                    "orphaned_marker_rows_affected",
                    "orphaned_marker_transition",
                    "orphaned_observed_database_fingerprint",
                    "orphaned_preserved_manifest_sha256",
                    "orphaned_recovery_receipt_schema",
                    "orphaned_reconciliation_mode",
                    "orphaned_prior_proof_sha256",
                    "orphaned_attestation_run_id",
                    "orphaned_attestation_sha256",
                    "orphaned_attestation_audit_sha256",
                    "orphaned_attestation_source_commit",
                }
            )
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
            "committed_expected_database_fingerprint": committed_database_fingerprint,
            "committed_expected_robots_exists": True,
            "committed_expected_robots_sha256": prior_robots_sha256,
            "committed_expected_sync_configured": True,
            "orphaned_recovery_proof_sha256": proof_sha256,
            "orphaned_reconciled_from": "rolling_back",
            "orphaned_observed_deployment": expected_deployment_id,
        }
        for key, expected in expected_receipt.items():
            require(
                exact_json_equal(receipt.get(key), expected),
                f"Initial receipt failed for {key}",
            )
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
        if proof_is_v2:
            marker_rows_affected = receipt.get("orphaned_marker_rows_affected")
            marker_transition = receipt.get("orphaned_marker_transition")
            require(
                type(marker_rows_affected) is int
                and marker_rows_affected in {0, 1}
                and marker_transition in {"corrected", "already-correct"}
                and (marker_transition == "corrected")
                is (marker_rows_affected == 1),
                "V2 durable receipt marker transition is invalid",
            )
            expected_v2_receipt = {
                "expected_sha256": failed.get("artifact_sha256"),
                "expected_version": failed.get("candidate_version"),
                "installed_plugin_sha256": failed.get(
                    "candidate_plugin_sha256"
                ),
                "post_install_database_fingerprint": failed.get(
                    "candidate_database_fingerprint"
                ),
                "orphaned_historical_baseline_database_fingerprint": prior_database_fingerprint,
                "orphaned_observed_database_fingerprint": proof[
                    "database_reconciliation"
                ]["observed_database_fingerprint"],
                "orphaned_preserved_manifest_sha256": proof[
                    "database_reconciliation"
                ]["preserved_manifest_sha256"],
                "orphaned_recovery_receipt_schema": "complete99-orphaned-rollback-receipt/v2",
                "orphaned_reconciliation_mode": proof[
                    "database_reconciliation"
                ]["mode"],
                "orphaned_prior_proof_sha256": proof[
                    "database_reconciliation"
                ]["prior_proof_sha256"],
                "orphaned_attestation_run_id": proof[
                    "database_reconciliation"
                ]["attestation_run_id"],
                "orphaned_attestation_sha256": proof[
                    "database_reconciliation"
                ]["attestation_sha256"],
                "orphaned_attestation_audit_sha256": proof[
                    "database_reconciliation"
                ]["attestation_audit_sha256"],
                "orphaned_attestation_source_commit": proof[
                    "database_reconciliation"
                ]["attestation_source_commit"],
            }
            for key, expected in expected_v2_receipt.items():
                require(
                    exact_json_equal(receipt.get(key), expected),
                    f"V2 durable receipt failed for {key}",
                )
            require(
                initial_status.get("phase") in {"committed", "cleanup_failed"}
                and initial_status.get("recovery_ready") is False
                and initial_status.get("database_fingerprint")
                == committed_database_fingerprint,
                "V2 durable receipt resume did not start from committed state",
            )

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
        require(
            exact_json_equal(health.get(key), expected),
            f"Recovered health failed for {key}",
        )

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
    if proof_is_v2:
        validate_observation_cleanup(
            audit.get("cleanup"),
            "V2 orphaned recovery bridge cleanup",
        )
    else:
        validate_cleanup(audit.get("cleanup"), "Orphaned recovery bridge cleanup")


def validate_recovery_audit(
    summary_json: str,
    proof_path: str,
    audit_root: Path,
    expected_probe_id: str,
    *,
    repository_root: Path = ROOT,
    expect_observation: bool = False,
    interrupted_forward_proof_path: str = "",
    expect_interrupted_forward: bool = False,
    dist: Path | None = None,
) -> dict[str, Any]:
    require(
        not (proof_path and interrupted_forward_proof_path),
        "Orphaned rollback and interrupted forward proofs are mutually exclusive",
    )
    require(
        expect_interrupted_forward is bool(interrupted_forward_proof_path),
        "Interrupted forward expectation requires exactly its reviewed proof",
    )
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
    if expect_interrupted_forward:
        allowed_results = (
            {"interrupted_forward_observed"}
            if expect_observation
            else {"recovered", "already-recovered"}
        )
    else:
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

    if interrupted_forward_proof_path:
        loaded = load_interrupted_forward_proof(
            interrupted_forward_proof_path,
            repository_root.resolve(),
        )
        expected_schema = (
            "complete99-interrupted-forward-proof/v1"
            if expect_observation
            else "complete99-interrupted-forward-proof/v2"
        )
        require(
            loaded["schema"] == expected_schema,
            "Interrupted forward proof version does not match observation/recovery mode",
        )
        if result == "already-recovered":
            require(
                deployment_id == expected_probe_id,
                "Interrupted forward finalized attestation used the wrong probe",
            )
        else:
            require(
                deployment_id
                == loaded["proof"]["failed_run"]["deployment_id"],
                "Interrupted forward proof was used for the wrong failed deployment",
            )
        require(dist is not None, "Interrupted forward validation requires --dist")
        validate_interrupted_forward_dist(dist, loaded)
        if expect_observation:
            validate_interrupted_observation_audit(
                audit,
                loaded["proof"]["failed_run"],
                loaded["proof"]["prior_run"],
                loaded["recovery_identity"],
                loaded["path"],
                loaded["proof_sha256"],
                expected_probe_id,
            )
            proof_consumed = False
        elif result == "already-recovered":
            validate_interrupted_forward_already_finalized_audit(
                audit,
                loaded,
                expected_probe_id,
            )
            proof_consumed = True
        else:
            validate_interrupted_forward_recovery_audit(
                audit,
                loaded,
                expected_probe_id,
            )
            proof_consumed = True
    elif proof_path:
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
                expected_probe_id,
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
    if expect_interrupted_forward:
        validated["interrupted_forward"] = True
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
    recovery_only: bool = False,
    platform_recovered: bool = False,
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
        require(not recovery_only, "Observation-only and recovery-only modes conflict")
        require(
            platform_recovered is False,
            "Observation-only preflight reported a completed platform recovery",
        )
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

    if recovery_only:
        require(
            preflight_outcome == "success" and platform_recovered is True,
            "Recovery-only preflight did not prove the reviewed platform recovery",
        )
        passed_dry_run = require_stage_result(dry_audit, "dry-run-passed", "dry-run")
        validate_cleanup(passed_dry_run.get("cleanup"), "Dry-run bridge cleanup")
        require(
            production_outcome == "skipped"
            and mutation_outcome == "skipped"
            and recovery_outcome == "skipped"
            and mutation_started == "",
            "Recovery-only run crossed the new platform deployment path",
        )
        require(
            production_audit is None and recovery_audit is None,
            "Recovery-only run created an audit for a new platform deployment",
        )
        commerce = validate_commerce_outcomes(
            platform_succeeded=True,
            commerce_outcome=commerce_outcome,
            commerce_recovery_outcome=commerce_recovery_outcome,
            commerce_id=commerce_id,
            commerce_audit_root=commerce_audit_root,
        )
        return {
            **commerce,
            "observation_only": False,
            "platform_recovered": True,
            "preflight_outcome": preflight_outcome,
            "production_outcome": production_outcome,
            "recovery_only": True,
            "recovery_outcome": recovery_outcome,
            "validated": True,
        }

    require(
        platform_recovered is False,
        "Normal deployment unexpectedly reported a reviewed platform recovery",
    )

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
    parser.add_argument("--expect-interrupted-forward", action="store_true")
    parser.add_argument(
        "--summary-json",
        default=os.environ.get("COMPLETE99_RECOVERY_SUMMARY_JSON", ""),
    )
    parser.add_argument(
        "--proof",
        default=os.environ.get("COMPLETE99_ORPHANED_ROLLBACK_PROOF", ""),
    )
    parser.add_argument(
        "--interrupted-forward-proof",
        default=os.environ.get("COMPLETE99_INTERRUPTED_FORWARD_PROOF", ""),
    )
    parser.add_argument("--dist", type=Path)
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
            recovery_only=os.environ.get("COMPLETE99_RECOVERY_ONLY", "").lower()
            == "true",
            platform_recovered=os.environ.get(
                "COMPLETE99_PLATFORM_RECOVERED", ""
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
            interrupted_forward_proof_path=args.interrupted_forward_proof,
            expect_interrupted_forward=args.expect_interrupted_forward,
            dist=args.dist,
        )
    print(json.dumps(result, sort_keys=True))
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except AuditValidationError as error:
        print(f"RECOVERY AUDIT VALIDATION FAILED: {error}", file=os.sys.stderr)
        raise SystemExit(1)
