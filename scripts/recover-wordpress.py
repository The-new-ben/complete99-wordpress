#!/usr/bin/env python3
"""Recreate a temporary bridge and recover one interrupted Complete99 deployment."""

from __future__ import annotations

import argparse
import hashlib
import importlib.util
import json
import os
import secrets
import sys
import time
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parents[1]
DEPLOYER_PATH = Path(__file__).with_name("deploy-wordpress.py")


class ObservationComplete(RuntimeError):
    """Internal control flow after a mutation-free orphan observation."""


def reject_duplicate_json_object(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
    result: dict[str, Any] = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f"Duplicate JSON key: {key}")
        result[key] = value
    return result


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


def load_deployer() -> Any:
    spec = importlib.util.spec_from_file_location("complete99_deployer", DEPLOYER_PATH)
    if spec is None or spec.loader is None:
        raise RuntimeError("The Complete99 deployer could not be loaded")
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


def validate_recovery_id(deployer: Any, value: str, label: str) -> str:
    if not deployer.re.fullmatch(r"[A-Za-z0-9._-]{8,96}", value) or not value.startswith(
        "c99-"
    ):
        raise deployer.DeployError(f"{label} is not a valid Complete99 deployment ID")
    return value


def validate_database_manifest(
    deployer: Any,
    manifest: Any,
    manifest_sha256: Any,
    label: str,
) -> None:
    components = [
        "options_without_deployment_marker",
        "posts",
        "postmeta",
        "seed_ids",
        "evaluation_ids",
    ]
    schema = manifest.get("schema") if isinstance(manifest, dict) else None
    if schema == "complete99-database-snapshot-manifest/v3":
        components.extend(["ops_tables", "campaign_tables"])
    elif schema == "complete99-database-snapshot-manifest/v2":
        components.append("ops_tables")
    elif schema != "complete99-database-snapshot-manifest/v1":
        raise deployer.DeployError(f"{label} manifest identity is invalid")
    expected_keys = {
        "schema",
        "sync_secret_existed",
        "sync_secret_configured",
    }
    for component in components:
        expected_keys.add(f"{component}_count")
        expected_keys.add(f"{component}_sha256")
    digest = deployer.re.compile(r"[a-f0-9]{64}")
    if (
        not isinstance(manifest, dict)
        or set(manifest) != expected_keys
        or manifest.get("sync_secret_existed") is not True
        or manifest.get("sync_secret_configured") is not True
        or type(manifest_sha256) is not str
        or digest.fullmatch(manifest_sha256) is None
    ):
        raise deployer.DeployError(f"{label} manifest identity is invalid")
    for component in components:
        count = manifest.get(f"{component}_count")
        component_sha256 = manifest.get(f"{component}_sha256")
        if (
            type(count) is not int
            or count < 0
            or count > 9223372036854775807
            or (component in {"ops_tables", "campaign_tables"} and count != 7)
            or type(component_sha256) is not str
            or digest.fullmatch(component_sha256) is None
        ):
            raise deployer.DeployError(
                f"{label} manifest component is invalid for {component}"
            )
    canonical = json.dumps(
        manifest,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    if not secrets.compare_digest(
        manifest_sha256,
        hashlib.sha256(canonical).hexdigest(),
    ):
        raise deployer.DeployError(f"{label} manifest digest does not match")


def validate_v2_database_reconciliation(
    deployer: Any,
    proof: dict[str, Any],
    proof_root: Path,
) -> None:
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    reconciliation = proof.get("database_reconciliation")
    reconciliation_keys = {
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
    digest = deployer.re.compile(r"[a-f0-9]{64}")
    commit = deployer.re.compile(r"[a-f0-9]{40}")
    if (
        not isinstance(reconciliation, dict)
        or set(reconciliation) != reconciliation_keys
        or reconciliation.get("schema")
        != "complete99-orphaned-database-reconciliation/v1"
        or reconciliation.get("mode")
        != "preserve-reviewed-drift-marker-only"
        or type(reconciliation.get("attestation_run_id")) is not int
        or reconciliation["attestation_run_id"] <= failed["run_id"]
        or type(reconciliation.get("attestation_source_commit")) is not str
        or commit.fullmatch(reconciliation["attestation_source_commit"]) is None
        or reconciliation["attestation_source_commit"]
        in {failed["commit"], prior["commit"]}
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 reconciliation identity is invalid"
        )
    for field in (
        "attestation_audit_sha256",
        "attestation_sha256",
        "baseline_database_fingerprint",
        "expected_reconciled_database_fingerprint",
        "observed_database_fingerprint",
        "preserved_manifest_sha256",
        "prior_proof_sha256",
    ):
        value = reconciliation.get(field)
        if type(value) is not str or digest.fullmatch(value) is None:
            raise deployer.DeployError(
                f"Orphaned rollback v2 digest is invalid for {field}"
            )
    storage = reconciliation.get("transactional_storage")
    if (
        not isinstance(storage, dict)
        or set(storage) != {"engine", "tables"}
        or storage.get("engine")
        not in {"INNODB", "XTRADB", "INNODB,XTRADB"}
        or type(storage.get("tables")) is not int
        or storage.get("tables") != 3
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 storage identity is invalid"
        )
    validate_database_manifest(
        deployer,
        reconciliation.get("preserved_manifest"),
        reconciliation.get("preserved_manifest_sha256"),
        "Orphaned rollback v2",
    )
    base_proof = {"failed_run": failed, "prior_run": prior}
    canonical_base = json.dumps(
        base_proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    expected_prior_proof_sha256 = hashlib.sha256(canonical_base).hexdigest()
    if (
        reconciliation.get("prior_proof_sha256")
        != expected_prior_proof_sha256
        or reconciliation.get("observed_deployment")
        != failed["deployment_id"]
        or reconciliation.get("target_deployment")
        != prior["deployment_id"]
        or reconciliation.get("baseline_database_fingerprint")
        != prior["database_fingerprint"]
        or reconciliation.get("observed_database_fingerprint")
        == prior["database_fingerprint"]
        or reconciliation.get("observed_database_fingerprint")
        == reconciliation.get("expected_reconciled_database_fingerprint")
        or reconciliation.get("expected_reconciled_database_fingerprint")
        == prior["database_fingerprint"]
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 reviewed state conflicts with its base proof"
        )
    raw_attestation_path = reconciliation.get("attestation_path")
    if type(raw_attestation_path) is not str:
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation path is invalid"
        )
    relative_attestation = Path(raw_attestation_path)
    expected_relative_root = Path("docs/recovery-proofs/observations")
    try:
        evidence_relative = relative_attestation.relative_to(
            expected_relative_root
        )
    except ValueError:
        evidence_relative = None
    unresolved_attestation_root = ROOT / expected_relative_root
    attestation_root = unresolved_attestation_root.resolve()
    unresolved_attestation_path = ROOT / relative_attestation
    attestation_path = unresolved_attestation_path.resolve()
    symlink_in_evidence_path = False
    evidence_cursor = ROOT
    for part in relative_attestation.parts:
        evidence_cursor /= part
        if evidence_cursor.is_symlink():
            symlink_in_evidence_path = True
            break
    if (
        relative_attestation.is_absolute()
        or not raw_attestation_path.startswith(
            "docs/recovery-proofs/observations/"
        )
        or relative_attestation.as_posix() != raw_attestation_path
        or ".." in raw_attestation_path
        or ".." in relative_attestation.parts
        or not resolves_without_indirection(
            unresolved_attestation_root,
            attestation_root,
        )
        or not resolves_without_indirection(
            unresolved_attestation_path,
            attestation_path,
        )
        or attestation_root not in attestation_path.parents
        or not raw_attestation_path.endswith(".json")
        or evidence_relative is None
        or symlink_in_evidence_path
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation must be under the observation evidence root"
        )
    try:
        raw_attestation = attestation_path.read_bytes()
        attestation = json.loads(
            raw_attestation.decode("utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
    except (OSError, UnicodeDecodeError, json.JSONDecodeError, ValueError) as error:
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation could not be read"
        ) from error
    attestation_digest = hashlib.sha256(raw_attestation).hexdigest()
    if (
        not secrets.compare_digest(
            reconciliation["attestation_sha256"],
            attestation_digest,
        )
        or not secrets.compare_digest(
            reconciliation["attestation_audit_sha256"],
            attestation_digest,
        )
        or not secrets.compare_digest(
            reconciliation["attestation_sha256"],
            reconciliation["attestation_audit_sha256"],
        )
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation digest does not match"
        )
    attestation_keys = {
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
    }
    if (
        not isinstance(attestation, dict)
        or set(attestation) != attestation_keys
        or attestation.get("decision") != "observe_orphaned_rollback"
        or attestation.get("deployment_id") != failed["deployment_id"]
        or attestation.get("local_test") is not False
        or attestation.get("result") != "orphaned-rollback-observed"
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation schema is invalid"
        )
    observation = attestation.get("orphaned_rollback_observation")
    observation_keys = {
        "current_active",
        "current_database_fingerprint",
        "current_database_version",
        "current_deployment",
        "current_plugin_sha256",
        "current_robots_sha256",
        "current_sync_configured",
        "current_version",
        "database_manifest",
        "database_manifest_sha256",
        "database_storage",
        "deployment_id",
        "failed_candidate_database_fingerprint",
        "historical_baseline_database_fingerprint",
        "historical_baseline_matches_projection",
        "lock_owned",
        "phase",
        "process_lock_available",
        "projected_database_fingerprint",
        "projected_deployment_id",
        "proof_sha256",
        "recovery_ready",
        "schema",
        "state_exists",
    }
    if (
        not isinstance(observation, dict)
        or set(observation) != observation_keys
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation observation is invalid"
        )
    expected_observation = {
        "current_active": prior["active"],
        "current_database_fingerprint": reconciliation[
            "observed_database_fingerprint"
        ],
        "current_database_version": prior["database_version"],
        "current_deployment": failed["deployment_id"],
        "current_plugin_sha256": prior["plugin_sha256"],
        "current_robots_sha256": prior["robots_sha256"],
        "current_sync_configured": prior["sync_configured"],
        "current_version": prior["version"],
        "database_manifest": reconciliation["preserved_manifest"],
        "database_manifest_sha256": reconciliation[
            "preserved_manifest_sha256"
        ],
        "database_storage": reconciliation["transactional_storage"],
        "deployment_id": failed["deployment_id"],
        "failed_candidate_database_fingerprint": failed[
            "candidate_database_fingerprint"
        ],
        "historical_baseline_database_fingerprint": prior[
            "database_fingerprint"
        ],
        "historical_baseline_matches_projection": False,
        "lock_owned": True,
        "phase": "rolling_back",
        "process_lock_available": True,
        "projected_database_fingerprint": reconciliation[
            "expected_reconciled_database_fingerprint"
        ],
        "projected_deployment_id": prior["deployment_id"],
        "proof_sha256": reconciliation["prior_proof_sha256"],
        "recovery_ready": True,
        "schema": "complete99-orphaned-rollback-observation/v1",
        "state_exists": False,
    }
    if not exact_json_equal(observation, expected_observation):
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation does not match the reviewed state"
        )
    expected_initial_status = {
        "database_fingerprint": reconciliation["observed_database_fingerprint"],
        "database_manifest_sha256": reconciliation["preserved_manifest_sha256"],
        "database_storage": reconciliation["transactional_storage"],
        "lock_owned": True,
        "phase": "rolling_back",
        "process_lock_available": True,
        "projected_database_fingerprint": reconciliation[
            "expected_reconciled_database_fingerprint"
        ],
        "projected_deployment_id": prior["deployment_id"],
        "recovery_ready": True,
        "state_exists": False,
    }
    discovery = attestation.get("discovery")
    orphaned_proof = attestation.get("orphaned_rollback_proof")
    if (
        not exact_json_equal(
            attestation.get("initial_status"),
            expected_initial_status,
        )
        or not exact_json_equal(
            attestation.get("bridge_site_identity"),
            {
            "home_host": "complete99.co.il",
            "rest_host": "complete99.co.il",
            "siteurl_host": "complete99.co.il",
            },
        )
        or not isinstance(discovery, dict)
        or discovery.get("owner_deployment_id") != failed["deployment_id"]
        or discovery.get("owner_phase") != "rolling_back"
        or discovery.get("probe_id")
        != f"c99-recovery-probe-{reconciliation['attestation_run_id']}-1"
        or discovery.get("result") != "owner-discovered"
        or not exact_json_equal(
            orphaned_proof,
            {
                "path": "docs/recovery-proofs/c99-prod-31171940371-1.json",
                "proof_sha256": reconciliation["prior_proof_sha256"],
            },
        )
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation provenance is invalid"
        )
    expected_bootstrap_cleanup = {
        "exact_name": "c99-deploy-bootstrap",
        "known_id": 5,
        "known_id_matched": False,
        "removed_ids": [],
        "row_absence_verified": True,
    }
    expected_final_cleanup = {
        "removed_ids": [171],
        "route_404": True,
        "row_absence_verified": True,
        "snippet_active": False,
        "snippet_deleted": True,
    }
    expected_discovery_cleanup = {
        "removed_ids": [170],
        "route_404": True,
        "row_absence_verified": True,
        "snippet_active": False,
        "snippet_deleted": True,
    }
    expected_discovery = {
        "bootstrap_cleanup": expected_bootstrap_cleanup,
        "cleanup": expected_discovery_cleanup,
        "owner_deployment_id": failed["deployment_id"],
        "owner_phase": "rolling_back",
        "probe_id": f"c99-recovery-probe-{reconciliation['attestation_run_id']}-1",
        "result": "owner-discovered",
    }
    identity = attestation.get("identity")
    timestamp = deployer.re.compile(
        r"[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z"
    )
    started_at = attestation.get("started_at")
    finished_at = attestation.get("finished_at")
    if (
        not exact_json_equal(
            attestation.get("bootstrap_cleanup"),
            expected_bootstrap_cleanup,
        )
        or not exact_json_equal(
            attestation.get("cleanup"),
            expected_final_cleanup,
        )
        or not exact_json_equal(discovery, expected_discovery)
        or not exact_json_equal(
            identity,
            {
                "id": 1,
                "roles": ["administrator"],
                "site_identity": {
                    "home": "https://complete99.co.il",
                    "url": "https://complete99.co.il",
                },
            },
        )
        or type(started_at) is not str
        or timestamp.fullmatch(started_at) is None
        or type(finished_at) is not str
        or timestamp.fullmatch(finished_at) is None
        or finished_at < started_at
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 attestation cleanup contract is invalid"
        )

    historical_relative = Path(orphaned_proof["path"])
    unresolved_historical_path = ROOT / historical_relative
    historical_path = unresolved_historical_path.resolve()
    if (
        historical_relative.is_absolute()
        or not resolves_without_indirection(
            unresolved_historical_path,
            historical_path,
        )
        or proof_root not in historical_path.parents
        or historical_path.parent != proof_root
        or historical_path.suffix.lower() != ".json"
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 historical proof path is invalid"
        )
    try:
        historical_envelope = json.loads(
            historical_path.read_text(encoding="utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
    except (OSError, UnicodeDecodeError, json.JSONDecodeError, ValueError) as error:
        raise deployer.DeployError(
            "Orphaned rollback v2 historical proof could not be read"
        ) from error
    historical_proof = (
        historical_envelope.get("proof")
        if isinstance(historical_envelope, dict)
        else None
    )
    historical_canonical = (
        json.dumps(
            historical_proof,
            ensure_ascii=False,
            separators=(",", ":"),
            sort_keys=True,
        ).encode("utf-8")
        if isinstance(historical_proof, dict)
        else b""
    )
    if (
        not isinstance(historical_envelope, dict)
        or set(historical_envelope) != {"schema", "proof", "proof_sha256"}
        or historical_envelope.get("schema")
        != "complete99-orphaned-rollback-proof/v1"
        or not exact_json_equal(historical_proof, base_proof)
        or historical_envelope.get("proof_sha256")
        != reconciliation["prior_proof_sha256"]
        or hashlib.sha256(historical_canonical).hexdigest()
        != reconciliation["prior_proof_sha256"]
    ):
        raise deployer.DeployError(
            "Orphaned rollback v2 historical proof does not match"
        )


def load_orphaned_rollback_proof(
    deployer: Any,
    raw_path: str,
) -> dict[str, Any] | None:
    if not raw_path:
        return None
    candidate = Path(raw_path)
    unresolved_path = ROOT / candidate if not candidate.is_absolute() else candidate
    path = unresolved_path.resolve()
    unresolved_proof_root = ROOT / "docs" / "recovery-proofs"
    proof_root = unresolved_proof_root.resolve()
    if (
        not resolves_without_indirection(unresolved_proof_root, proof_root)
        or not resolves_without_indirection(unresolved_path, path)
        or proof_root not in path.parents
        or path.suffix.lower() != ".json"
    ):
        raise deployer.DeployError(
            "Orphaned rollback proof must be a reviewed JSON file under docs/recovery-proofs"
        )
    try:
        envelope = json.loads(
            path.read_text(encoding="utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
    except (OSError, UnicodeDecodeError, json.JSONDecodeError, ValueError) as error:
        raise deployer.DeployError("Orphaned rollback proof could not be read") from error
    proof_schema = envelope.get("schema") if isinstance(envelope, dict) else None
    if (
        not isinstance(envelope, dict)
        or set(envelope) != {"schema", "proof", "proof_sha256"}
        or proof_schema
        not in {
            "complete99-orphaned-rollback-proof/v1",
            "complete99-orphaned-rollback-proof/v2",
        }
        or not isinstance(envelope.get("proof"), dict)
    ):
        raise deployer.DeployError("Orphaned rollback proof schema is invalid")
    proof = envelope["proof"]
    canonical = json.dumps(
        proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    proof_sha256 = hashlib.sha256(canonical).hexdigest()
    if envelope.get("proof_sha256") != proof_sha256:
        raise deployer.DeployError("Orphaned rollback proof digest does not match")
    failed = proof.get("failed_run")
    prior = proof.get("prior_run")
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
    digest = deployer.re.compile(r"[a-f0-9]{64}")
    commit = deployer.re.compile(r"[a-f0-9]{40}")
    version = deployer.re.compile(r"[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?")
    expected_proof_keys = (
        {"failed_run", "prior_run", "database_reconciliation"}
        if proof_schema == "complete99-orphaned-rollback-proof/v2"
        else {"failed_run", "prior_run"}
    )
    if (
        set(proof) != expected_proof_keys
        or not isinstance(failed, dict)
        or not isinstance(prior, dict)
        or set(failed) != failed_keys
        or set(prior) != prior_keys
    ):
        raise deployer.DeployError("Orphaned rollback proof identities are missing")
    for record, label in ((failed, "failed"), (prior, "prior")):
        if (
            type(record.get("run_id")) is not int
            or record["run_id"] <= 0
            or type(record.get("commit")) is not str
            or commit.fullmatch(record["commit"]) is None
            or type(record.get("audit_sha256")) is not str
            or digest.fullmatch(record["audit_sha256"]) is None
            or type(record.get("deployment_id")) is not str
        ):
            raise deployer.DeployError(
                f"Orphaned rollback {label} audit identity is invalid"
            )
        validate_recovery_id(
            deployer,
            record["deployment_id"],
            f"Orphaned rollback {label} deployment ID",
        )
    if (
        failed["deployment_id"] == prior["deployment_id"]
        or failed["run_id"] <= prior["run_id"]
        or f"-{failed['run_id']}-" not in failed["deployment_id"]
        or f"-{prior['run_id']}-" not in prior["deployment_id"]
        or type(failed.get("artifact_sha256")) is not str
        or digest.fullmatch(failed["artifact_sha256"]) is None
        or type(failed.get("candidate_plugin_sha256")) is not str
        or digest.fullmatch(failed["candidate_plugin_sha256"]) is None
        or type(failed.get("candidate_database_fingerprint")) is not str
        or digest.fullmatch(failed["candidate_database_fingerprint"]) is None
        or type(failed.get("candidate_version")) is not str
        or version.fullmatch(failed["candidate_version"]) is None
        or type(prior.get("version")) is not str
        or version.fullmatch(prior["version"]) is None
        or type(prior.get("database_version")) is not str
        or prior.get("database_version") != prior.get("version")
        or prior.get("active") is not True
        or prior.get("robots_exists") is not True
        or prior.get("sync_configured") is not True
        or type(prior.get("plugin_sha256")) is not str
        or digest.fullmatch(prior["plugin_sha256"]) is None
        or type(prior.get("database_fingerprint")) is not str
        or digest.fullmatch(prior["database_fingerprint"]) is None
        or type(prior.get("robots_sha256")) is not str
        or digest.fullmatch(prior["robots_sha256"]) is None
        or failed["candidate_version"] == prior["version"]
        or failed["candidate_plugin_sha256"] == prior["plugin_sha256"]
        or failed["candidate_database_fingerprint"]
        == prior["database_fingerprint"]
    ):
        raise deployer.DeployError("Orphaned rollback reviewed identities are invalid")
    if proof_schema == "complete99-orphaned-rollback-proof/v2":
        validate_v2_database_reconciliation(deployer, proof, proof_root)
    return {
        "path": str(path.relative_to(ROOT)).replace("\\", "/"),
        "proof": proof,
        "schema": proof_schema,
        "proof_sha256": proof_sha256,
    }


def canonical_proof_sha256(proof: dict[str, Any]) -> str:
    canonical = json.dumps(
        proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    return hashlib.sha256(canonical).hexdigest()


def load_bound_recovery_audit(
    deployer: Any,
    raw_path: Any,
    expected_sha256: Any,
    label: str,
) -> dict[str, Any]:
    """Load one byte-bound reviewed audit without accepting path indirection."""
    digest = deployer.re.compile(r"[a-f0-9]{64}")
    if (
        type(raw_path) is not str
        or type(expected_sha256) is not str
        or digest.fullmatch(expected_sha256) is None
        or "\\" in raw_path
    ):
        raise deployer.DeployError(f"{label} audit identity is invalid")
    relative = Path(raw_path)
    expected_relative_root = Path("docs/recovery-proofs/observations")
    unresolved_root = ROOT / expected_relative_root
    resolved_root = unresolved_root.resolve()
    unresolved_path = ROOT / relative
    resolved_path = unresolved_path.resolve()
    path_has_symlink = False
    cursor = ROOT
    for part in relative.parts:
        cursor /= part
        if cursor.is_symlink():
            path_has_symlink = True
            break
    if (
        relative.is_absolute()
        or relative.as_posix() != raw_path
        or relative.parent != expected_relative_root
        or relative.suffix != ".json"
        or not relative.name.startswith("c99-")
        or ".." in relative.parts
        or path_has_symlink
        or not resolves_without_indirection(unresolved_root, resolved_root)
        or not resolves_without_indirection(unresolved_path, resolved_path)
        or resolved_path.parent != resolved_root
    ):
        raise deployer.DeployError(
            f"{label} audit must be a direct reviewed JSON file under the observation evidence root"
        )
    try:
        raw = resolved_path.read_bytes()
        audit = json.loads(
            raw.decode("utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
    except (OSError, UnicodeDecodeError, json.JSONDecodeError, ValueError) as error:
        raise deployer.DeployError(f"{label} audit could not be read") from error
    if not secrets.compare_digest(hashlib.sha256(raw).hexdigest(), expected_sha256):
        raise deployer.DeployError(f"{label} audit digest does not match")
    if not isinstance(audit, dict):
        raise deployer.DeployError(f"{label} audit schema is invalid")
    return audit


def validate_reviewed_audit_common(
    deployer: Any,
    audit: dict[str, Any],
    deployment_id: str,
    label: str,
) -> None:
    timestamp = deployer.re.compile(
        r"[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z"
    )
    started_at = audit.get("started_at")
    finished_at = audit.get("finished_at")
    cleanup = audit.get("cleanup")
    bootstrap = audit.get("bootstrap_cleanup")
    identity = audit.get("identity")
    if (
        audit.get("deployment_id") != deployment_id
        or audit.get("local_test") is not False
        or type(started_at) is not str
        or timestamp.fullmatch(started_at) is None
        or type(finished_at) is not str
        or timestamp.fullmatch(finished_at) is None
        or finished_at < started_at
        or not exact_json_equal(
            audit.get("bridge_site_identity"),
            {
                "home_host": "complete99.co.il",
                "rest_host": "complete99.co.il",
                "siteurl_host": "complete99.co.il",
            },
        )
        or not exact_json_equal(
            identity,
            {
                "id": 1,
                "roles": ["administrator"],
                "site_identity": {
                    "home": "https://complete99.co.il",
                    "url": "https://complete99.co.il",
                },
            },
        )
        or not isinstance(bootstrap, dict)
        or set(bootstrap)
        != {
            "exact_name",
            "known_id",
            "known_id_matched",
            "removed_ids",
            "row_absence_verified",
        }
        or bootstrap.get("exact_name") != "c99-deploy-bootstrap"
        or type(bootstrap.get("known_id")) is not int
        or bootstrap.get("known_id", 0) <= 0
        or bootstrap.get("known_id_matched") is not False
        or bootstrap.get("removed_ids") != []
        or bootstrap.get("row_absence_verified") is not True
        or not isinstance(cleanup, dict)
        or set(cleanup)
        != {
            "removed_ids",
            "route_404",
            "row_absence_verified",
            "snippet_active",
            "snippet_deleted",
        }
        or not isinstance(cleanup.get("removed_ids"), list)
        or not cleanup["removed_ids"]
        or any(type(value) is not int or value <= 0 for value in cleanup["removed_ids"])
        or cleanup.get("route_404") is not True
        or cleanup.get("row_absence_verified") is not True
        or cleanup.get("snippet_active") is not False
        or cleanup.get("snippet_deleted") is not True
    ):
        raise deployer.DeployError(f"{label} audit provenance is invalid")


def validate_transactional_storage(
    deployer: Any,
    storage: Any,
    label: str,
) -> None:
    if (
        not isinstance(storage, dict)
        or set(storage) != {"engine", "tables"}
        or storage.get("engine") not in {"INNODB", "XTRADB", "INNODB,XTRADB"}
        or type(storage.get("tables")) is not int
        or storage.get("tables") != 3
    ):
        raise deployer.DeployError(f"{label} transactional storage is invalid")


def validate_interrupted_forward_source_audits(
    deployer: Any,
    failed: dict[str, Any],
    prior: dict[str, Any],
    failed_audit: dict[str, Any],
    recovery_audit: dict[str, Any],
    prior_audit: dict[str, Any],
) -> dict[str, str]:
    validate_reviewed_audit_common(
        deployer,
        failed_audit,
        failed["deployment_id"],
        "Interrupted forward failed deploy",
    )
    validate_reviewed_audit_common(
        deployer,
        recovery_audit,
        failed["deployment_id"],
        "Interrupted forward failed recovery",
    )
    validate_reviewed_audit_common(
        deployer,
        prior_audit,
        prior["deployment_id"],
        "Interrupted forward prior deploy",
    )
    failed_preflight = failed_audit.get("preflight")
    failed_health = failed_audit.get("prior_health")
    failed_home = failed_audit.get("prior_rendered_home")
    failed_cleanup = failed_audit.get("failure_rollback")
    failed_error = failed_audit.get("error")
    if (
        failed_audit.get("dry_run") is not False
        or failed_audit.get("result") != "failed"
        or failed_error not in {"DeployError", "HTTPDeployError"}
        or failed_audit.get("failed_gate") != "install"
        or failed_audit.get("commit") != failed["commit"]
        or failed_audit.get("version") != failed["version"]
        or failed_audit.get("sha256") != failed["artifact_sha256"]
        or failed_audit.get("source_sha256") != failed["source_sha256"]
        or failed_audit.get("artifact")
        != f"complete99-platform-{failed['version']}.zip"
        or not isinstance(failed_preflight, dict)
        or failed_preflight.get("current_active") is not prior["active"]
        or failed_preflight.get("current_deployment") != prior["deployment_id"]
        or failed_preflight.get("current_version") != prior["version"]
        or failed_preflight.get("database_fingerprint")
        != failed["baseline_database_fingerprint"]
        or failed_preflight.get("robots_prior_exists") is not True
        or failed_preflight.get("robots_prior_sha256") != prior["robots_sha256"]
        or failed_preflight.get("had_plugin") is not True
        or failed_preflight.get("target_dir_exists") is not True
        or failed_preflight.get("plugin_main_exists") is not True
        or failed_preflight.get("direct_filesystem") is not True
        or not exact_json_equal(
            failed_preflight.get("transactional_storage"),
            {"engine": "INNODB", "tables": 3},
        )
        or not exact_json_equal(
            failed_health,
            {
                "component": "complete99-platform",
                "database_version": prior["database_version"],
                "deployment_id": prior["deployment_id"],
                "status": "ok",
                "sync_configured": prior["sync_configured"],
                "version": prior["version"],
            },
        )
        or not isinstance(failed_home, dict)
        or failed_home.get("deployment_id") != prior["deployment_id"]
        or failed_home.get("exact_path") != "/"
        or failed_home.get("version") != prior["version"]
        or deployer.re.fullmatch(
            r"[a-f0-9]{64}", str(failed_home.get("body_sha256", ""))
        )
        is None
        or not exact_json_equal(
            failed_cleanup,
            {"error": "HTTPDeployError", "rolled_back": False},
        )
    ):
        raise deployer.DeployError(
            "Interrupted forward failed deploy audit conflicts with the proof"
        )

    raw_recovery_status = recovery_audit.get("initial_status")
    recovery_status = (
        raw_recovery_status if isinstance(raw_recovery_status, dict) else {}
    )
    recovery_status_shape = {
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
    legacy_interrupted_install = (
        failed_error == "HTTPDeployError"
        and recovery_status.get("phase") == "installing"
        and recovery_status.get("recovery_ready") is True
        and "stabilization_failure" not in recovery_audit
    )
    pending_stabilization = (
        failed_error == "DeployError"
        and recovery_status.get("phase") == "installed_pending_stabilization"
        and recovery_status.get("recovery_ready") is False
        and exact_json_equal(
            recovery_audit.get("stabilization_failure"),
            {
                "error": "HTTPDeployError",
                "phase": "installed_pending_stabilization",
            },
        )
    )
    if (
        recovery_audit.get("result") != "failed"
        or recovery_audit.get("error") != "HTTPDeployError"
        or recovery_audit.get("discovery") is not None
        or not isinstance(raw_recovery_status, dict)
        or set(recovery_status) != recovery_status_shape
        or not (legacy_interrupted_install or pending_stabilization)
        or recovery_status.get("state_exists") is not True
        or recovery_status.get("lock_owned") is not True
        or recovery_status.get("process_lock_available") is not True
        or recovery_status.get("projected_database_fingerprint") != ""
        or recovery_status.get("projected_deployment_id") != ""
        or recovery_status.get("database_storage") != []
    ):
        raise deployer.DeployError(
            "Interrupted forward failed recovery audit conflicts with the proof"
        )
    digest = deployer.re.compile(r"[a-f0-9]{64}")
    recovery_database_fingerprint = recovery_status.get("database_fingerprint")
    recovery_manifest_sha256 = recovery_status.get("database_manifest_sha256")
    if (
        type(recovery_database_fingerprint) is not str
        or digest.fullmatch(recovery_database_fingerprint) is None
        or recovery_database_fingerprint
        in {failed["baseline_database_fingerprint"], prior["database_fingerprint"]}
        or type(recovery_manifest_sha256) is not str
        or digest.fullmatch(recovery_manifest_sha256) is None
    ):
        raise deployer.DeployError(
            "Interrupted forward failed recovery database identity is invalid"
        )

    prior_install = prior_audit.get("install")
    prior_health = prior_audit.get("health")
    prior_home = prior_audit.get("rendered_home")
    prior_robots = prior_audit.get("robots")
    prior_finalize = prior_audit.get("finalize")
    if (
        prior_audit.get("dry_run") is not False
        or prior_audit.get("result") != "deployed"
        or prior_audit.get("commit") != prior["commit"]
        or prior_audit.get("version") != prior["version"]
        or not isinstance(prior_install, dict)
        or prior_install.get("installed_plugin_sha256") != prior["plugin_sha256"]
        or prior_install.get("robots_sha256") != prior["robots_sha256"]
        or not exact_json_equal(
            prior_health,
            {
                "component": "complete99-platform",
                "database_version": prior["database_version"],
                "deployment_id": prior["deployment_id"],
                "status": "ok",
                "sync_configured": prior["sync_configured"],
                "version": prior["version"],
            },
        )
        or not isinstance(prior_home, dict)
        or prior_home.get("deployment_id") != prior["deployment_id"]
        or prior_home.get("exact_path") != "/"
        or prior_home.get("version") != prior["version"]
        or deployer.re.fullmatch(
            r"[a-f0-9]{64}", str(prior_home.get("body_sha256", ""))
        )
        is None
        or not exact_json_equal(
            prior_robots,
            {"sha256": prior["robots_sha256"], "status": 200},
        )
        or not isinstance(prior_finalize, dict)
        or prior_finalize.get("finalized") is not True
        or prior_finalize.get("lock_released") is not True
        or prior_finalize.get("state_removed") is not True
    ):
        raise deployer.DeployError(
            "Interrupted forward prior deploy audit conflicts with the proof"
        )
    return {
        "database_fingerprint": recovery_database_fingerprint,
        "database_manifest_sha256": recovery_manifest_sha256,
    }


def validate_interrupted_forward_observation_audit(
    deployer: Any,
    audit: dict[str, Any],
    failed: dict[str, Any],
    prior: dict[str, Any],
    adoption: dict[str, Any],
) -> None:
    validate_reviewed_audit_common(
        deployer,
        audit,
        failed["deployment_id"],
        "Interrupted forward observation",
    )
    expected_keys = {
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
    observation = audit.get("interrupted_forward_observation")
    proof_record = audit.get("interrupted_forward_proof")
    discovery = audit.get("discovery")
    discovery_bootstrap = (
        discovery.get("bootstrap_cleanup") if isinstance(discovery, dict) else None
    )
    discovery_cleanup = (
        discovery.get("cleanup") if isinstance(discovery, dict) else None
    )
    expected_historical_path = (
        f"docs/recovery-proofs/{failed['deployment_id']}.json"
    )
    if (
        set(audit) != expected_keys
        or audit.get("commit") != adoption["observation_commit"]
        or audit.get("decision") != "observe_interrupted_forward"
        or audit.get("result") != "interrupted_forward_observed"
        or not isinstance(discovery, dict)
        or set(discovery)
        != {
            "bootstrap_cleanup",
            "cleanup",
            "owner_deployment_id",
            "owner_phase",
            "probe_id",
            "result",
        }
        or discovery.get("probe_id")
        != f"c99-recovery-probe-{adoption['observation_run_id']}-1"
        or discovery.get("owner_deployment_id") != failed["deployment_id"]
        or discovery.get("owner_phase") != "installing"
        or discovery.get("result") != "owner-discovered"
        or not exact_json_equal(
            discovery_bootstrap,
            {
                "exact_name": "c99-deploy-bootstrap",
                "known_id": 5,
                "known_id_matched": False,
                "removed_ids": [],
                "row_absence_verified": True,
            },
        )
        or not isinstance(discovery_cleanup, dict)
        or set(discovery_cleanup)
        != {
            "removed_ids",
            "route_404",
            "row_absence_verified",
            "snippet_active",
            "snippet_deleted",
        }
        or not isinstance(discovery_cleanup.get("removed_ids"), list)
        or len(discovery_cleanup["removed_ids"]) != 1
        or type(discovery_cleanup["removed_ids"][0]) is not int
        or discovery_cleanup["removed_ids"][0] <= 0
        or discovery_cleanup.get("route_404") is not True
        or discovery_cleanup.get("row_absence_verified") is not True
        or discovery_cleanup.get("snippet_active") is not False
        or discovery_cleanup.get("snippet_deleted") is not True
        or not isinstance(observation, dict)
        or not exact_json_equal(
            proof_record,
            {
                "path": expected_historical_path,
                "proof_sha256": adoption["observation_proof_sha256"],
                "schema": "complete99-interrupted-forward-proof/v1",
            },
        )
    ):
        raise deployer.DeployError(
            "Interrupted forward observation audit schema is invalid"
        )
    expected_observation = {
        "adopted_forward_no_rollback": False,
        "baseline_database_fingerprint": failed[
            "baseline_database_fingerprint"
        ],
        "current_active": True,
        "current_database_version": adoption["observed_version"],
        "current_deployment": adoption["observed_deployment_id"],
        "current_plugin_main_exists": True,
        "current_plugin_sha256": adoption["observed_plugin_sha256"],
        "current_robots_sha256": adoption["observed_robots_sha256"],
        "current_sync_configured": True,
        "current_target_dir_exists": True,
        "current_version": adoption["observed_version"],
        "database_fingerprint": adoption["observed_database_fingerprint"],
        "database_manifest": adoption["observed_database_manifest"],
        "database_manifest_sha256": adoption[
            "observed_database_manifest_sha256"
        ],
        "database_storage": adoption["observed_database_storage"],
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
        "recorded_installed_plugin_sha256": observation.get(
            "recorded_installed_plugin_sha256"
        ),
        "recovery_ready": True,
        "robots_applied": True,
        "robots_managed_sha256": adoption["observed_robots_sha256"],
        "runtime_loaded": True,
        "runtime_version": adoption["observed_version"],
        "schema": "complete99-interrupted-forward-observation/v1",
        "state_exists": True,
    }
    recorded_plugin = observation.get("recorded_installed_plugin_sha256")
    if (
        recorded_plugin not in {"", failed["installed_plugin_sha256"]}
        or not exact_json_equal(observation, expected_observation)
        or not exact_json_equal(
            audit.get("health"),
            {
                "component": "complete99-platform",
                "database_version": adoption["observed_version"],
                "deployment_id": adoption["observed_deployment_id"],
                "status": "ok",
                "sync_configured": True,
                "version": adoption["observed_version"],
            },
        )
        or not exact_json_equal(
            audit.get("robots"),
            {"sha256": adoption["observed_robots_sha256"], "status": 200},
        )
        or not isinstance(audit.get("rendered_home"), dict)
        or audit["rendered_home"].get("deployment_id")
        != adoption["observed_deployment_id"]
        or audit["rendered_home"].get("version") != adoption["observed_version"]
        or audit["rendered_home"].get("exact_path") != "/"
        or deployer.re.fullmatch(
            r"[a-f0-9]{64}",
            str(audit["rendered_home"].get("body_sha256", "")),
        )
        is None
    ):
        raise deployer.DeployError(
            "Interrupted forward observation audit conflicts with the reviewed state"
        )


def validate_interrupted_forward_database_mismatch_observation_audit(
    deployer: Any,
    audit: dict[str, Any],
    failed: dict[str, Any],
    prior: dict[str, Any],
    recovery_identity: dict[str, str],
    adoption: dict[str, Any],
) -> None:
    """Authenticate a DB-drift observation without treating it as authority."""
    validate_reviewed_audit_common(
        deployer,
        audit,
        failed["deployment_id"],
        "Interrupted forward database mismatch observation",
    )
    expected_keys = {
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
        "proof_consumed",
        "rendered_home",
        "result",
        "robots",
        "started_at",
    }
    discovery = audit.get("discovery")
    discovery_bootstrap = (
        discovery.get("bootstrap_cleanup") if isinstance(discovery, dict) else None
    )
    discovery_cleanup = (
        discovery.get("cleanup") if isinstance(discovery, dict) else None
    )
    expected_historical_path = (
        f"docs/recovery-proofs/{failed['deployment_id']}.json"
    )
    observation = audit.get("interrupted_forward_observation")
    if (
        set(audit) != expected_keys
        or audit.get("commit") != adoption["observation_commit"]
        or audit.get("decision")
        != "observe_interrupted_forward_database_mismatch"
        or audit.get("result")
        != "interrupted_forward_database_mismatch_observed"
        or audit.get("proof_consumed") is not False
        or not isinstance(discovery, dict)
        or set(discovery)
        != {
            "bootstrap_cleanup",
            "cleanup",
            "owner_deployment_id",
            "owner_phase",
            "probe_id",
            "result",
        }
        or discovery.get("probe_id")
        != f"c99-recovery-probe-{adoption['observation_run_id']}-1"
        or discovery.get("owner_deployment_id") != failed["deployment_id"]
        or discovery.get("owner_phase") != "installing"
        or discovery.get("result") != "owner-discovered"
        or not exact_json_equal(
            discovery_bootstrap,
            {
                "exact_name": "c99-deploy-bootstrap",
                "known_id": 5,
                "known_id_matched": False,
                "removed_ids": [],
                "row_absence_verified": True,
            },
        )
        or not isinstance(discovery_cleanup, dict)
        or set(discovery_cleanup)
        != {
            "removed_ids",
            "route_404",
            "row_absence_verified",
            "snippet_active",
            "snippet_deleted",
        }
        or not isinstance(discovery_cleanup.get("removed_ids"), list)
        or len(discovery_cleanup["removed_ids"]) != 1
        or type(discovery_cleanup["removed_ids"][0]) is not int
        or discovery_cleanup["removed_ids"][0] <= 0
        or discovery_cleanup.get("route_404") is not True
        or discovery_cleanup.get("row_absence_verified") is not True
        or discovery_cleanup.get("snippet_active") is not False
        or discovery_cleanup.get("snippet_deleted") is not True
        or not isinstance(observation, dict)
        or not exact_json_equal(
            audit.get("interrupted_forward_proof"),
            {
                "path": expected_historical_path,
                "proof_sha256": adoption["observation_proof_sha256"],
                "schema": "complete99-interrupted-forward-proof/v1",
            },
        )
    ):
        raise deployer.DeployError(
            "Interrupted forward database mismatch observation audit schema is invalid"
        )
    expected_observation = validate_interrupted_forward_database_mismatch_status(
        deployer,
        observation.get("safe_status"),
        {
            "proof": {"failed_run": failed, "prior_run": prior},
            "recovery_identity": recovery_identity,
        },
    )
    if (
        not exact_json_equal(observation, expected_observation)
        or observation.get("database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or observation.get("database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
        or not exact_json_equal(
            observation.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        or not exact_json_equal(
            observation.get("database_storage"),
            adoption["observed_database_storage"],
        )
        or not exact_json_equal(
            audit.get("health"),
            {
                "component": "complete99-platform",
                "database_version": adoption["observed_version"],
                "deployment_id": adoption["observed_deployment_id"],
                "status": "ok",
                "sync_configured": True,
                "version": adoption["observed_version"],
            },
        )
        or not exact_json_equal(
            audit.get("robots"),
            {"sha256": adoption["observed_robots_sha256"], "status": 200},
        )
        or not isinstance(audit.get("rendered_home"), dict)
        or audit["rendered_home"].get("deployment_id")
        != adoption["observed_deployment_id"]
        or audit["rendered_home"].get("version")
        != adoption["observed_version"]
        or audit["rendered_home"].get("exact_path") != "/"
        or deployer.re.fullmatch(
            r"[a-f0-9]{64}",
            str(audit["rendered_home"].get("body_sha256", "")),
        )
        is None
    ):
        raise deployer.DeployError(
            "Interrupted forward database mismatch observation conflicts with the reviewed state"
        )


def validate_interrupted_forward_robots_checkpoint_observation_audit(
    deployer: Any,
    audit: dict[str, Any],
    failed: dict[str, Any],
    prior: dict[str, Any],
    recovery_identity: dict[str, str],
    adoption: dict[str, Any],
) -> dict[str, Any]:
    """Authenticate the one reviewed v3 robots-checkpoint diagnostic."""
    validate_reviewed_audit_common(
        deployer,
        audit,
        failed["deployment_id"],
        "Interrupted forward robots checkpoint observation",
    )
    expected_keys = {
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
        "proof_consumed",
        "rendered_home",
        "result",
        "robots",
        "started_at",
    }
    discovery = audit.get("discovery")
    discovery_bootstrap = (
        discovery.get("bootstrap_cleanup") if isinstance(discovery, dict) else None
    )
    discovery_cleanup = (
        discovery.get("cleanup") if isinstance(discovery, dict) else None
    )
    expected_historical_path = (
        f"docs/recovery-proofs/{failed['deployment_id']}.json"
    )
    observation = audit.get("interrupted_forward_observation")
    if (
        set(audit) != expected_keys
        or audit.get("commit") != adoption["observation_commit"]
        or audit.get("decision")
        != "observe_interrupted_forward_mismatch_diagnostic"
        or audit.get("result")
        != "interrupted_forward_mismatch_diagnostic_observed"
        or audit.get("proof_consumed") is not False
        or not isinstance(discovery, dict)
        or set(discovery)
        != {
            "bootstrap_cleanup",
            "cleanup",
            "owner_deployment_id",
            "owner_phase",
            "probe_id",
            "result",
        }
        or discovery.get("probe_id")
        != f"c99-recovery-probe-{adoption['observation_run_id']}-1"
        or discovery.get("owner_deployment_id") != failed["deployment_id"]
        or discovery.get("owner_phase") != "installing"
        or discovery.get("result") != "owner-discovered"
        or not exact_json_equal(
            discovery_bootstrap,
            {
                "exact_name": "c99-deploy-bootstrap",
                "known_id": 5,
                "known_id_matched": False,
                "removed_ids": [],
                "row_absence_verified": True,
            },
        )
        or not isinstance(discovery_cleanup, dict)
        or set(discovery_cleanup)
        != {
            "removed_ids",
            "route_404",
            "row_absence_verified",
            "snippet_active",
            "snippet_deleted",
        }
        or not isinstance(discovery_cleanup.get("removed_ids"), list)
        or len(discovery_cleanup["removed_ids"]) != 1
        or type(discovery_cleanup["removed_ids"][0]) is not int
        or discovery_cleanup["removed_ids"][0] <= 0
        or discovery_cleanup.get("route_404") is not True
        or discovery_cleanup.get("row_absence_verified") is not True
        or discovery_cleanup.get("snippet_active") is not False
        or discovery_cleanup.get("snippet_deleted") is not True
        or not isinstance(observation, dict)
        or not exact_json_equal(
            audit.get("interrupted_forward_proof"),
            {
                "path": expected_historical_path,
                "proof_sha256": adoption["observation_proof_sha256"],
                "schema": "complete99-interrupted-forward-proof/v1",
            },
        )
    ):
        raise deployer.DeployError(
            "Interrupted forward robots checkpoint observation audit schema is invalid"
        )
    if set(observation) != {
        "diagnostic_only",
        "mismatches",
        "proof_consumed",
        "recovery_authority",
        "safe_status",
        "safe_status_sha256",
        "schema",
    }:
        raise deployer.DeployError(
            "Interrupted forward robots checkpoint receipt schema is invalid"
        )
    safe_status = validate_interrupted_forward_safe_status_shape(
        deployer,
        observation.get("safe_status"),
        "Interrupted forward robots checkpoint",
    )
    expected_mismatches = interrupted_forward_status_mismatches(
        safe_status,
        {
            "proof": {"failed_run": failed, "prior_run": prior},
            "recovery_identity": recovery_identity,
        },
    )
    if (
        observation.get("schema")
        != "complete99-interrupted-forward-observation/v3"
        or observation.get("diagnostic_only") is not True
        or observation.get("proof_consumed") is not False
        or observation.get("recovery_authority") is not False
        or not exact_json_equal(
            observation.get("mismatches"),
            INTERRUPTED_FORWARD_ROBOTS_CHECKPOINT_MISMATCHES,
        )
        or expected_mismatches
        != INTERRUPTED_FORWARD_ROBOTS_CHECKPOINT_MISMATCHES
        or observation.get("safe_status_sha256")
        != canonical_proof_sha256(safe_status)
        or safe_status.get("robots_applied") is not False
        or safe_status.get("robots_managed_sha256") != ""
        or safe_status.get("interrupted_forward_candidate") is not False
        or safe_status.get("database_fingerprint")
        != recovery_identity["database_fingerprint"]
        or safe_status.get("database_manifest_sha256")
        != recovery_identity["database_manifest_sha256"]
        or safe_status.get("database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or safe_status.get("database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
        or not exact_json_equal(
            safe_status.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        or not exact_json_equal(
            safe_status.get("database_storage"),
            adoption["observed_database_storage"],
        )
        or safe_status.get("deployment_id")
        != adoption["observed_deployment_id"]
        or safe_status.get("current_plugin_sha256")
        != adoption["observed_plugin_sha256"]
        or safe_status.get("current_robots_sha256")
        != adoption["observed_robots_sha256"]
        or safe_status.get("current_version") != adoption["observed_version"]
        or not exact_json_equal(
            audit.get("health"),
            {
                "component": "complete99-platform",
                "database_version": adoption["observed_version"],
                "deployment_id": adoption["observed_deployment_id"],
                "status": "ok",
                "sync_configured": True,
                "version": adoption["observed_version"],
            },
        )
        or not exact_json_equal(
            audit.get("robots"),
            {"sha256": adoption["observed_robots_sha256"], "status": 200},
        )
        or not isinstance(audit.get("rendered_home"), dict)
        or audit["rendered_home"].get("deployment_id")
        != adoption["observed_deployment_id"]
        or audit["rendered_home"].get("version")
        != adoption["observed_version"]
        or audit["rendered_home"].get("exact_path") != "/"
        or deployer.re.fullmatch(
            r"[a-f0-9]{64}",
            str(audit["rendered_home"].get("body_sha256", "")),
        )
        is None
    ):
        raise deployer.DeployError(
            "Interrupted forward robots checkpoint observation conflicts with the reviewed state"
        )
    return observation


def load_interrupted_forward_proof(
    deployer: Any,
    raw_path: str,
) -> dict[str, Any] | None:
    """Load and authenticate a reviewed interrupted-forward proof envelope."""
    if not raw_path:
        return None
    candidate = Path(raw_path)
    unresolved_path = ROOT / candidate if not candidate.is_absolute() else candidate
    path = unresolved_path.resolve()
    unresolved_proof_root = ROOT / "docs" / "recovery-proofs"
    proof_root = unresolved_proof_root.resolve()
    if (
        not resolves_without_indirection(unresolved_proof_root, proof_root)
        or not resolves_without_indirection(unresolved_path, path)
        or proof_root not in path.parents
        or path.parent != proof_root
        or path.suffix != ".json"
        or path.is_symlink()
    ):
        raise deployer.DeployError(
            "Interrupted forward proof must be a direct reviewed JSON file under docs/recovery-proofs"
        )
    try:
        envelope = json.loads(
            path.read_text(encoding="utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
    except (OSError, UnicodeDecodeError, json.JSONDecodeError, ValueError) as error:
        raise deployer.DeployError(
            "Interrupted forward proof could not be read"
        ) from error
    schema = envelope.get("schema") if isinstance(envelope, dict) else None
    if (
        not isinstance(envelope, dict)
        or set(envelope) != {"schema", "proof", "proof_sha256"}
        or schema
        not in {
            "complete99-interrupted-forward-proof/v1",
            "complete99-interrupted-forward-proof/v2",
        }
        or not isinstance(envelope.get("proof"), dict)
    ):
        raise deployer.DeployError("Interrupted forward proof schema is invalid")
    proof = envelope["proof"]
    proof_sha256 = canonical_proof_sha256(proof)
    if not secrets.compare_digest(str(envelope.get("proof_sha256", "")), proof_sha256):
        raise deployer.DeployError("Interrupted forward proof digest does not match")
    expected_proof_keys = (
        {"failed_run", "forward_adoption", "prior_run"}
        if schema == "complete99-interrupted-forward-proof/v2"
        else {"failed_run", "prior_run"}
    )
    failed = proof.get("failed_run")
    prior = proof.get("prior_run")
    failed_keys = {
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
    prior_keys = {
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
    }
    if (
        set(proof) != expected_proof_keys
        or not isinstance(failed, dict)
        or set(failed) != failed_keys
        or not isinstance(prior, dict)
        or set(prior) != prior_keys
    ):
        raise deployer.DeployError(
            "Interrupted forward proof identities are missing"
        )
    digest = deployer.re.compile(r"[a-f0-9]{64}")
    commit = deployer.re.compile(r"[a-f0-9]{40}")
    version = deployer.re.compile(
        r"[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?"
    )
    for record, label in ((failed, "failed"), (prior, "prior")):
        if (
            type(record.get("run_id")) is not int
            or record["run_id"] <= 0
            or type(record.get("commit")) is not str
            or commit.fullmatch(record["commit"]) is None
            or type(record.get("deployment_id")) is not str
        ):
            raise deployer.DeployError(
                f"Interrupted forward {label} run identity is invalid"
            )
        validate_recovery_id(
            deployer,
            record["deployment_id"],
            f"Interrupted forward {label} deployment ID",
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
            if (
                type(record.get(field)) is not str
                or digest.fullmatch(record[field]) is None
            ):
                raise deployer.DeployError(
                    f"Interrupted forward {label} digest is invalid for {field}"
                )
    if (
        type(failed.get("version")) is not str
        or version.fullmatch(failed["version"]) is None
        or type(prior.get("version")) is not str
        or version.fullmatch(prior["version"]) is None
        or prior.get("database_version") != prior["version"]
        or prior.get("active") is not True
        or prior.get("sync_configured") is not True
        or failed["deployment_id"] == prior["deployment_id"]
        or failed["run_id"] <= prior["run_id"]
        or f"-{failed['run_id']}-" not in failed["deployment_id"]
        or f"-{prior['run_id']}-" not in prior["deployment_id"]
        or failed["commit"] == prior["commit"]
        or failed["version"] == prior["version"]
        or failed["installed_plugin_sha256"] == prior["plugin_sha256"]
        or failed["baseline_database_fingerprint"]
        != prior["database_fingerprint"]
    ):
        raise deployer.DeployError(
            "Interrupted forward reviewed identities are invalid"
        )
    failed_audit = load_bound_recovery_audit(
        deployer,
        failed["deploy_audit_path"],
        failed["deploy_audit_sha256"],
        "Interrupted forward failed deploy",
    )
    recovery_audit = load_bound_recovery_audit(
        deployer,
        failed["recovery_audit_path"],
        failed["recovery_audit_sha256"],
        "Interrupted forward failed recovery",
    )
    prior_audit = load_bound_recovery_audit(
        deployer,
        prior["deploy_audit_path"],
        prior["deploy_audit_sha256"],
        "Interrupted forward prior deploy",
    )
    if len(
        {
            failed["deploy_audit_path"],
            failed["recovery_audit_path"],
            prior["deploy_audit_path"],
        }
    ) != 3:
        raise deployer.DeployError(
            "Interrupted forward source audit paths must be distinct"
        )
    recovery_identity = validate_interrupted_forward_source_audits(
        deployer,
        failed,
        prior,
        failed_audit,
        recovery_audit,
        prior_audit,
    )
    base_proof = {"failed_run": failed, "prior_run": prior}
    base_proof_sha256 = canonical_proof_sha256(base_proof)
    adoption = proof.get("forward_adoption")
    reviewed_forward_observation: dict[str, Any] | None = None
    if schema == "complete99-interrupted-forward-proof/v2":
        adoption_schema = (
            adoption.get("schema") if isinstance(adoption, dict) else None
        )
        adoption_keys = {
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
        if (
            not isinstance(adoption, dict)
            or set(adoption) != adoption_keys
            or adoption_schema
            not in {
                "complete99-interrupted-forward-adoption/v1",
                "complete99-interrupted-forward-adoption/v2",
                "complete99-interrupted-forward-adoption/v3",
            }
            or type(adoption.get("observation_run_id")) is not int
            or adoption["observation_run_id"] <= failed["run_id"]
            or type(adoption.get("observation_commit")) is not str
            or commit.fullmatch(adoption["observation_commit"]) is None
            or adoption["observation_commit"]
            in {failed["commit"], prior["commit"]}
            or adoption.get("observation_proof_sha256") != base_proof_sha256
            or adoption.get("target_artifact_sha256")
            != failed["artifact_sha256"]
            or adoption.get("target_installed_plugin_sha256")
            != failed["installed_plugin_sha256"]
            or adoption.get("observed_deployment_id")
            != failed["deployment_id"]
            or adoption.get("observed_plugin_sha256")
            != failed["installed_plugin_sha256"]
            or adoption.get("observed_robots_sha256") != prior["robots_sha256"]
            or adoption.get("observed_version") != failed["version"]
            or (
                adoption_schema
                in {
                    "complete99-interrupted-forward-adoption/v1",
                    "complete99-interrupted-forward-adoption/v3",
                }
                and (
                    adoption.get("observed_database_fingerprint")
                    != recovery_identity["database_fingerprint"]
                    or adoption.get("observed_database_manifest_sha256")
                    != recovery_identity["database_manifest_sha256"]
                )
            )
            or (
                adoption_schema
                == "complete99-interrupted-forward-adoption/v2"
                and (
                    adoption.get("observed_database_fingerprint")
                    == recovery_identity["database_fingerprint"]
                    or adoption.get("observed_database_manifest_sha256")
                    == recovery_identity["database_manifest_sha256"]
                )
            )
        ):
            raise deployer.DeployError(
                "Interrupted forward v2 adoption identity is invalid"
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
            value = adoption.get(field)
            if type(value) is not str or digest.fullmatch(value) is None:
                raise deployer.DeployError(
                    f"Interrupted forward v2 digest is invalid for {field}"
                )
        validate_database_manifest(
            deployer,
            adoption.get("observed_database_manifest"),
            adoption.get("observed_database_manifest_sha256"),
            "Interrupted forward v2 observed database",
        )
        validate_transactional_storage(
            deployer,
            adoption.get("observed_database_storage"),
            "Interrupted forward v2 observed database",
        )
        observation_audit = load_bound_recovery_audit(
            deployer,
            adoption["observation_audit_path"],
            adoption["observation_audit_sha256"],
            "Interrupted forward observation",
        )
        historical_path = (
            ROOT
            / "docs"
            / "recovery-proofs"
            / f"{failed['deployment_id']}.json"
        )
        historical = load_interrupted_forward_proof(
            deployer,
            str(historical_path),
        )
        if (
            historical is None
            or historical.get("schema")
            != "complete99-interrupted-forward-proof/v1"
            or historical.get("path")
            != f"docs/recovery-proofs/{failed['deployment_id']}.json"
            or historical.get("proof_sha256") != base_proof_sha256
            or not exact_json_equal(historical.get("proof"), base_proof)
        ):
            raise deployer.DeployError(
                "Interrupted forward v2 historical proof does not match"
            )
        if adoption["observation_audit_path"] in {
            failed["deploy_audit_path"],
            failed["recovery_audit_path"],
            prior["deploy_audit_path"],
        }:
            raise deployer.DeployError(
                "Interrupted forward observation audit must be distinct from source evidence"
            )
        if adoption_schema == "complete99-interrupted-forward-adoption/v2":
            validate_interrupted_forward_database_mismatch_observation_audit(
                deployer,
                observation_audit,
                failed,
                prior,
                recovery_identity,
                adoption,
            )
        elif adoption_schema == "complete99-interrupted-forward-adoption/v3":
            reviewed_forward_observation = (
                validate_interrupted_forward_robots_checkpoint_observation_audit(
                    deployer,
                    observation_audit,
                    failed,
                    prior,
                    recovery_identity,
                    adoption,
                )
            )
        else:
            validate_interrupted_forward_observation_audit(
                deployer,
                observation_audit,
                failed,
                prior,
                adoption,
            )
    loaded = {
        "base_proof_sha256": base_proof_sha256,
        "path": str(path.relative_to(ROOT)).replace("\\", "/"),
        "proof": proof,
        "proof_sha256": proof_sha256,
        "recovery_identity": recovery_identity,
        "schema": schema,
    }
    if reviewed_forward_observation is not None:
        loaded["reviewed_forward_observation"] = reviewed_forward_observation
    return loaded


def validate_interrupted_forward_dist(
    deployer: Any,
    dist: Path,
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    failed = loaded_proof["proof"]["failed_run"]
    try:
        metadata, artifact, raw = deployer.load_artifact(dist.resolve())
    except (OSError, ValueError, json.JSONDecodeError) as error:
        raise deployer.DeployError(
            "Interrupted forward release package could not be loaded"
        ) from error
    if (
        metadata.get("version") != failed["version"]
        or metadata.get("sha256") != failed["artifact_sha256"]
        or metadata.get("source_sha256") != failed["source_sha256"]
        or metadata.get("installed_sha256")
        != failed["installed_plugin_sha256"]
        or artifact.name != f"complete99-platform-{failed['version']}.zip"
        or hashlib.sha256(raw).hexdigest() != failed["artifact_sha256"]
        or deployer.installed_digest(raw) != failed["installed_plugin_sha256"]
    ):
        raise deployer.DeployError(
            "Interrupted forward proof does not match the exact reviewed release package"
        )
    return {
        "artifact": artifact.name,
        "installed_sha256": metadata["installed_sha256"],
        "sha256": metadata["sha256"],
        "source_sha256": metadata["source_sha256"],
        "version": metadata["version"],
    }


def interrupted_forward_bridge_fields(
    loaded_proof: dict[str, Any],
    *,
    enable_finalized_attestation: bool = False,
) -> dict[str, Any]:
    proof = loaded_proof["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof.get("forward_adoption")
    reviewed_database_fingerprint = loaded_proof["recovery_identity"][
        "database_fingerprint"
    ]
    reviewed_database_manifest_sha256 = loaded_proof["recovery_identity"][
        "database_manifest_sha256"
    ]
    reviewed_database_manifest: dict[str, Any] = {}
    reviewed_database_storage: dict[str, Any] = {}
    if isinstance(adoption, dict):
        reviewed_database_fingerprint = adoption[
            "observed_database_fingerprint"
        ]
        reviewed_database_manifest_sha256 = adoption[
            "observed_database_manifest_sha256"
        ]
        reviewed_database_manifest = adoption["observed_database_manifest"]
        reviewed_database_storage = adoption["observed_database_storage"]
    return {
        "expected_artifact_sha256": failed["artifact_sha256"],
        "expected_plugin_sha256": failed["installed_plugin_sha256"],
        "expected_version": failed["version"],
        "interrupted_forward_finalized_attestation": (
            enable_finalized_attestation
            and loaded_proof.get("schema")
            == "complete99-interrupted-forward-proof/v2"
        ),
        "interrupted_forward_proof_sha256": loaded_proof["proof_sha256"],
        "interrupted_forward_target_deployment_id": (
            failed["deployment_id"] if enable_finalized_attestation else ""
        ),
        "prior_database_fingerprint": prior["database_fingerprint"],
        "prior_deployment_id": prior["deployment_id"],
        "prior_plugin_sha256": prior["plugin_sha256"],
        "prior_robots_sha256": prior["robots_sha256"],
        "prior_version": prior["version"],
        "reviewed_database_fingerprint": reviewed_database_fingerprint,
        "reviewed_database_manifest": reviewed_database_manifest,
        "reviewed_database_manifest_sha256": reviewed_database_manifest_sha256,
        "reviewed_database_storage": reviewed_database_storage,
    }


def validate_interrupted_forward_status(
    deployer: Any,
    status: dict[str, Any],
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    """Prove the exact still-interrupted forward release without mutating it."""
    proof = loaded_proof["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof.get("forward_adoption")
    observed_database_fingerprint = loaded_proof["recovery_identity"][
        "database_fingerprint"
    ]
    observed_database_manifest_sha256 = loaded_proof["recovery_identity"][
        "database_manifest_sha256"
    ]
    if isinstance(adoption, dict):
        observed_database_fingerprint = adoption[
            "observed_database_fingerprint"
        ]
        observed_database_manifest_sha256 = adoption[
            "observed_database_manifest_sha256"
        ]
    validate_database_manifest(
        deployer,
        status.get("database_manifest"),
        status.get("database_manifest_sha256"),
        "Interrupted forward live database",
    )
    validate_transactional_storage(
        deployer,
        status.get("database_storage"),
        "Interrupted forward live database",
    )
    recorded_plugin_sha256 = status.get("installed_plugin_sha256")
    if (
        not isinstance(status, dict)
        or status.get("deployment_id") != failed["deployment_id"]
        or status.get("phase") != "installing"
        or status.get("state_exists") is not True
        or status.get("lock_owned") is not True
        or status.get("recovery_ready") is not True
        or status.get("process_lock_available") is not True
        or status.get("expected_sha256") != failed["artifact_sha256"]
        or status.get("expected_version") != failed["version"]
        or recorded_plugin_sha256
        not in {"", failed["installed_plugin_sha256"]}
        or status.get("current_target_dir_exists") is not True
        or status.get("current_plugin_main_exists") is not True
        or status.get("current_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or status.get("current_active") is not True
        or status.get("current_version") != failed["version"]
        or status.get("runtime_loaded") is not True
        or status.get("runtime_version") != failed["version"]
        or status.get("migration_failed") is not False
        or status.get("migration_invariants_valid") is not True
        or status.get("no_rollback_artifacts") is not True
        or status.get("baseline_database_journal_valid") is not True
        or status.get("baseline_sync_secret_existed") is not True
        or status.get("baseline_sync_configured") is not True
        or status.get("current_deployment") != failed["deployment_id"]
        or status.get("current_database_version") != failed["version"]
        or status.get("baseline_database_fingerprint")
        != failed["baseline_database_fingerprint"]
        or status.get("database_fingerprint")
        != observed_database_fingerprint
        or status.get("database_manifest_sha256")
        != observed_database_manifest_sha256
        or status.get("current_sync_configured") is not True
        or status.get("robots_applied") is not True
        or status.get("robots_managed_sha256") != prior["robots_sha256"]
        or status.get("current_robots_sha256") != prior["robots_sha256"]
        or status.get("adopted_forward_no_rollback") is not False
        or status.get("interrupted_forward_candidate") is not True
        or status.get("interrupted_forward_proof_sha256") != ""
        or status.get("interrupted_forward_database_manifest_sha256") != ""
    ):
        raise deployer.DeployError(
            "Interrupted forward observation did not match the exact reviewed live state"
        )
    if isinstance(adoption, dict) and (
        not exact_json_equal(
            status.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        or not exact_json_equal(
            status.get("database_storage"),
            adoption["observed_database_storage"],
        )
    ):
        raise deployer.DeployError(
            "Interrupted forward v2 proof does not match the live database evidence"
        )
    return {
        "adopted_forward_no_rollback": False,
        "baseline_database_fingerprint": failed[
            "baseline_database_fingerprint"
        ],
        "current_active": True,
        "current_database_version": failed["version"],
        "current_deployment": failed["deployment_id"],
        "current_plugin_main_exists": True,
        "current_plugin_sha256": failed["installed_plugin_sha256"],
        "current_robots_sha256": prior["robots_sha256"],
        "current_sync_configured": True,
        "current_target_dir_exists": True,
        "current_version": failed["version"],
        "database_fingerprint": observed_database_fingerprint,
        "database_manifest": status["database_manifest"],
        "database_manifest_sha256": observed_database_manifest_sha256,
        "database_storage": status["database_storage"],
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


INTERRUPTED_FORWARD_DATABASE_MISMATCHES = [
    "database_fingerprint",
    "database_manifest_sha256",
    "interrupted_forward_candidate",
]

INTERRUPTED_FORWARD_ROBOTS_CHECKPOINT_MISMATCHES = [
    "interrupted_forward_candidate",
    "robots_applied",
    "robots_managed_sha256",
]

INTERRUPTED_FORWARD_SAFE_STATUS_KEYS = (
    "adopted_forward_no_rollback",
    "baseline_database_fingerprint",
    "baseline_database_journal_valid",
    "baseline_sync_configured",
    "baseline_sync_secret_existed",
    "current_active",
    "current_database_version",
    "current_deployment",
    "current_plugin_main_exists",
    "current_plugin_sha256",
    "current_robots_sha256",
    "current_sync_configured",
    "current_target_dir_exists",
    "current_version",
    "database_fingerprint",
    "database_fingerprint_available",
    "database_manifest",
    "database_manifest_sha256",
    "database_restored",
    "database_storage",
    "deployment_id",
    "expected_sha256",
    "expected_version",
    "had_plugin",
    "installed_plugin_sha256",
    "interrupted_forward_candidate",
    "interrupted_forward_database_manifest_sha256",
    "interrupted_forward_proof_sha256",
    "lock_owned",
    "migration_failed",
    "migration_invariants_valid",
    "no_rollback_artifacts",
    "phase",
    "post_install_database_fingerprint",
    "prior_active",
    "prior_deployment",
    "prior_plugin_main_exists",
    "prior_plugin_sha256",
    "prior_target_dir_exists",
    "prior_version",
    "process_lock_available",
    "recovery_ready",
    "robots_applied",
    "robots_managed_sha256",
    "robots_prior_exists",
    "robots_prior_sha256",
    "robots_restored",
    "runtime_loaded",
    "runtime_version",
    "state_exists",
)

INTERRUPTED_FORWARD_SAFE_BOOLEAN_FIELDS = (
    "adopted_forward_no_rollback",
    "baseline_database_journal_valid",
    "baseline_sync_configured",
    "baseline_sync_secret_existed",
    "current_active",
    "current_plugin_main_exists",
    "current_sync_configured",
    "current_target_dir_exists",
    "database_fingerprint_available",
    "database_restored",
    "had_plugin",
    "interrupted_forward_candidate",
    "lock_owned",
    "migration_failed",
    "migration_invariants_valid",
    "no_rollback_artifacts",
    "prior_active",
    "prior_plugin_main_exists",
    "prior_target_dir_exists",
    "process_lock_available",
    "recovery_ready",
    "robots_applied",
    "robots_prior_exists",
    "robots_restored",
    "runtime_loaded",
    "state_exists",
)

INTERRUPTED_FORWARD_SAFE_DIGEST_FIELDS = (
    "baseline_database_fingerprint",
    "current_plugin_sha256",
    "current_robots_sha256",
    "database_fingerprint",
    "expected_sha256",
    "installed_plugin_sha256",
    "interrupted_forward_database_manifest_sha256",
    "interrupted_forward_proof_sha256",
    "post_install_database_fingerprint",
    "prior_plugin_sha256",
    "robots_managed_sha256",
    "robots_prior_sha256",
)

INTERRUPTED_FORWARD_SAFE_DEPLOYMENT_FIELDS = (
    "current_deployment",
    "deployment_id",
    "prior_deployment",
)

INTERRUPTED_FORWARD_SAFE_VERSION_FIELDS = (
    "current_database_version",
    "current_version",
    "expected_version",
    "prior_version",
    "runtime_version",
)

INTERRUPTED_FORWARD_STABILIZATION_SAFE_STATUS_KEYS = (
    "candidate_activation_completed_at",
    "candidate_activation_phase",
    "candidate_activation_required",
    "candidate_database_fingerprint",
    "candidate_prior_active",
    "candidate_requested_active",
    "forward_ready",
    "forward_stabilization_candidate",
    "temp_removed",
)

INTERRUPTED_FORWARD_SAFE_PHASES = {
    "",
    "cleanup_failed",
    "commit_failed",
    "committed",
    "committing",
    "failed",
    "finalized",
    "candidate_activation_pending",
    "candidate_activation_complete",
    "installed",
    "installed_pending_cleanup",
    "installed_pending_stabilization",
    "installing",
    "locked",
    "prepared",
    "reserved",
    "rollback_failed",
    "rolled_back",
    "rolling_back",
}


def validate_interrupted_forward_safe_status_shape(
    deployer: Any,
    status: Any,
    label: str,
) -> dict[str, Any]:
    """Return only bounded non-secret status fields after strict type checks."""
    if not isinstance(status, dict) or any(
        key not in status for key in INTERRUPTED_FORWARD_SAFE_STATUS_KEYS
    ):
        raise deployer.DeployError(f"{label} safe status fields are invalid")
    validate_database_manifest(
        deployer,
        status.get("database_manifest"),
        status.get("database_manifest_sha256"),
        label,
    )
    validate_transactional_storage(
        deployer,
        status.get("database_storage"),
        label,
    )
    if any(
        status["database_manifest"].get(f"{component}_count")
        > 9_223_372_036_854_775_807
        for component in (
            "options_without_deployment_marker",
            "posts",
            "postmeta",
            "seed_ids",
            "evaluation_ids",
        )
    ):
        raise deployer.DeployError(f"{label} manifest count is unbounded")
    safe_status = {
        key: status[key] for key in INTERRUPTED_FORWARD_SAFE_STATUS_KEYS
    }
    if safe_status.get("phase") == "installed_pending_stabilization":
        if any(
            key not in status
            for key in INTERRUPTED_FORWARD_STABILIZATION_SAFE_STATUS_KEYS
        ):
            raise deployer.DeployError(
                f"{label} stabilization checkpoint fields are missing"
            )
        safe_status.update(
            {
                key: status[key]
                for key in INTERRUPTED_FORWARD_STABILIZATION_SAFE_STATUS_KEYS
            }
        )
    digest = deployer.re.compile(r"[a-f0-9]{64}")
    version = deployer.re.compile(
        r"[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?"
    )
    if any(
        type(safe_status[field]) is not bool
        for field in INTERRUPTED_FORWARD_SAFE_BOOLEAN_FIELDS
    ):
        raise deployer.DeployError(f"{label} safe boolean status is invalid")
    if any(
        type(safe_status[field]) is not str
        or (
            safe_status[field] != ""
            and digest.fullmatch(safe_status[field]) is None
        )
        for field in INTERRUPTED_FORWARD_SAFE_DIGEST_FIELDS
    ):
        raise deployer.DeployError(f"{label} safe digest status is invalid")
    if any(
        type(safe_status[field]) is not str
        or (
            safe_status[field] != ""
            and (
                not safe_status[field].startswith("c99-")
                or deployer.re.fullmatch(
                    r"[A-Za-z0-9._-]{8,96}", safe_status[field]
                )
                is None
            )
        )
        for field in INTERRUPTED_FORWARD_SAFE_DEPLOYMENT_FIELDS
    ):
        raise deployer.DeployError(
            f"{label} safe deployment identity is invalid"
        )
    if any(
        type(safe_status[field]) is not str
        or len(safe_status[field]) > 64
        or (
            safe_status[field] != ""
            and version.fullmatch(safe_status[field]) is None
        )
        for field in INTERRUPTED_FORWARD_SAFE_VERSION_FIELDS
    ):
        raise deployer.DeployError(f"{label} safe version status is invalid")
    if (
        type(safe_status.get("phase")) is not str
        or safe_status["phase"] not in INTERRUPTED_FORWARD_SAFE_PHASES
    ):
        raise deployer.DeployError(f"{label} safe phase status is invalid")
    if safe_status["phase"] == "installed_pending_stabilization" and (
        type(safe_status.get("candidate_activation_completed_at")) is not int
        or safe_status["candidate_activation_completed_at"] <= 0
        or safe_status["candidate_activation_completed_at"]
        > 9_223_372_036_854_775_807
        or safe_status.get("candidate_activation_phase") != "complete"
        or type(safe_status.get("candidate_database_fingerprint")) is not str
        or digest.fullmatch(safe_status["candidate_database_fingerprint"])
        is None
        or any(
            type(safe_status.get(field)) is not bool
            for field in (
                "candidate_activation_required",
                "candidate_prior_active",
                "candidate_requested_active",
                "forward_ready",
                "forward_stabilization_candidate",
                "temp_removed",
            )
        )
    ):
        raise deployer.DeployError(
            f"{label} stabilization checkpoint status is invalid"
        )
    return safe_status


def interrupted_forward_status_mismatches(
    safe_status: dict[str, Any],
    loaded_proof: dict[str, Any],
) -> list[str]:
    """Name every reviewed predicate that differs, in canonical order."""
    failed = loaded_proof["proof"]["failed_run"]
    prior = loaded_proof["proof"]["prior_run"]
    recovery_identity = loaded_proof["recovery_identity"]
    expected = {
        "adopted_forward_no_rollback": False,
        "baseline_database_fingerprint": failed[
            "baseline_database_fingerprint"
        ],
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
        "database_fingerprint": recovery_identity["database_fingerprint"],
        "database_fingerprint_available": True,
        "database_manifest_sha256": recovery_identity[
            "database_manifest_sha256"
        ],
        "database_restored": False,
        "deployment_id": failed["deployment_id"],
        "expected_sha256": failed["artifact_sha256"],
        "expected_version": failed["version"],
        "had_plugin": True,
        "interrupted_forward_candidate": True,
        "interrupted_forward_database_manifest_sha256": "",
        "interrupted_forward_proof_sha256": "",
        "lock_owned": True,
        "migration_failed": False,
        "migration_invariants_valid": True,
        "no_rollback_artifacts": True,
        "phase": "installing",
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
    mismatches = [
        field
        for field, expected_value in expected.items()
        if not exact_json_equal(safe_status.get(field), expected_value)
    ]
    if safe_status.get("installed_plugin_sha256") not in {
        "",
        failed["installed_plugin_sha256"],
    }:
        mismatches.append("installed_plugin_sha256")
    return sorted(mismatches)


def capture_interrupted_forward_mismatch_diagnostic(
    deployer: Any,
    status: Any,
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    """Capture a non-authoritative receipt for a safely shaped mismatch."""
    safe_status = validate_interrupted_forward_safe_status_shape(
        deployer,
        status,
        "Interrupted forward mismatch diagnostic",
    )
    mismatches = interrupted_forward_status_mismatches(
        safe_status,
        loaded_proof,
    )
    if not mismatches:
        raise deployer.DeployError(
            "Interrupted forward mismatch diagnostic had no reviewed mismatch"
        )
    if mismatches == INTERRUPTED_FORWARD_DATABASE_MISMATCHES:
        raise deployer.DeployError(
            "Interrupted forward database-only mismatch requires observation v2"
        )
    return {
        "diagnostic_only": True,
        "mismatches": mismatches,
        "proof_consumed": False,
        "recovery_authority": False,
        "safe_status": safe_status,
        "safe_status_sha256": canonical_proof_sha256(safe_status),
        "schema": "complete99-interrupted-forward-observation/v3",
    }


def validate_interrupted_forward_robots_checkpoint_status(
    deployer: Any,
    status: Any,
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    """Recreate the reviewed v3 receipt exactly before any adoption mutation."""
    adoption = loaded_proof["proof"].get("forward_adoption")
    reviewed = loaded_proof.get("reviewed_forward_observation")
    if (
        not isinstance(adoption, dict)
        or adoption.get("schema")
        != "complete99-interrupted-forward-adoption/v3"
        or not isinstance(reviewed, dict)
    ):
        raise deployer.DeployError(
            "Interrupted forward robots checkpoint requires its reviewed adoption v3 proof"
        )
    observed = capture_interrupted_forward_mismatch_diagnostic(
        deployer,
        status,
        loaded_proof,
    )
    if not exact_json_equal(observed, reviewed):
        raise deployer.DeployError(
            "Interrupted forward robots checkpoint changed after review"
        )
    return observed


def validate_interrupted_forward_database_mismatch_status(
    deployer: Any,
    status: dict[str, Any],
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    """Capture DB-only drift while proving every other forward invariant."""
    if not isinstance(status, dict):
        raise deployer.DeployError(
            "Interrupted forward database mismatch status is invalid"
        )
    safe_status = validate_interrupted_forward_safe_status_shape(
        deployer,
        status,
        "Interrupted forward drift database",
    )
    proof = loaded_proof["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    historical_database_fingerprint = loaded_proof["recovery_identity"][
        "database_fingerprint"
    ]
    historical_database_manifest_sha256 = loaded_proof["recovery_identity"][
        "database_manifest_sha256"
    ]
    validate_database_manifest(
        deployer,
        status.get("database_manifest"),
        status.get("database_manifest_sha256"),
        "Interrupted forward drift database",
    )
    validate_transactional_storage(
        deployer,
        status.get("database_storage"),
        "Interrupted forward drift database",
    )
    current_database_fingerprint = status.get("database_fingerprint")
    current_database_manifest_sha256 = status.get("database_manifest_sha256")
    recorded_plugin_sha256 = status.get("installed_plugin_sha256")
    if (
        type(current_database_fingerprint) is not str
        or deployer.re.fullmatch(
            r"[a-f0-9]{64}", current_database_fingerprint
        )
        is None
        or current_database_fingerprint == historical_database_fingerprint
        or current_database_manifest_sha256
        == historical_database_manifest_sha256
        or status.get("deployment_id") != failed["deployment_id"]
        or status.get("phase") != "installing"
        or status.get("state_exists") is not True
        or status.get("lock_owned") is not True
        or status.get("recovery_ready") is not True
        or status.get("process_lock_available") is not True
        or status.get("expected_sha256") != failed["artifact_sha256"]
        or status.get("expected_version") != failed["version"]
        or recorded_plugin_sha256
        not in {"", failed["installed_plugin_sha256"]}
        or status.get("current_target_dir_exists") is not True
        or status.get("current_plugin_main_exists") is not True
        or status.get("current_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or status.get("current_active") is not True
        or status.get("current_version") != failed["version"]
        or status.get("runtime_loaded") is not True
        or status.get("runtime_version") != failed["version"]
        or status.get("migration_failed") is not False
        or status.get("migration_invariants_valid") is not True
        or status.get("no_rollback_artifacts") is not True
        or status.get("database_restored") is not False
        or status.get("baseline_database_journal_valid") is not True
        or status.get("baseline_sync_secret_existed") is not True
        or status.get("baseline_sync_configured") is not True
        or status.get("current_deployment") != failed["deployment_id"]
        or status.get("current_database_version") != failed["version"]
        or status.get("baseline_database_fingerprint")
        != failed["baseline_database_fingerprint"]
        or (
            status.get("post_install_database_fingerprint") != ""
            and (
                type(status.get("post_install_database_fingerprint"))
                is not str
                or deployer.re.fullmatch(
                    r"[a-f0-9]{64}",
                    status["post_install_database_fingerprint"],
                )
                is None
            )
        )
        or status.get("current_sync_configured") is not True
        or status.get("database_fingerprint_available") is not True
        or status.get("had_plugin") is not True
        or status.get("prior_target_dir_exists") is not True
        or status.get("prior_plugin_main_exists") is not True
        or status.get("prior_plugin_sha256") != prior["plugin_sha256"]
        or status.get("prior_version") != prior["version"]
        or status.get("prior_active") is not True
        or status.get("prior_deployment") != prior["deployment_id"]
        or status.get("robots_applied") is not True
        or status.get("robots_restored") is not False
        or status.get("robots_prior_exists") is not True
        or status.get("robots_prior_sha256") != prior["robots_sha256"]
        or status.get("robots_managed_sha256") != prior["robots_sha256"]
        or status.get("current_robots_sha256") != prior["robots_sha256"]
        or status.get("adopted_forward_no_rollback") is not False
        or status.get("interrupted_forward_candidate") is not False
        or status.get("interrupted_forward_proof_sha256") != ""
        or status.get("interrupted_forward_database_manifest_sha256") != ""
    ):
        raise deployer.DeployError(
            "Interrupted forward mismatch was not isolated to both reviewed database identities"
        )
    safe_status_sha256 = canonical_proof_sha256(safe_status)
    return {
        "database_fingerprint": current_database_fingerprint,
        "database_identity_changed": True,
        "database_manifest": status["database_manifest"],
        "database_manifest_sha256": current_database_manifest_sha256,
        "database_storage": status["database_storage"],
        "historical_database_fingerprint": historical_database_fingerprint,
        "historical_database_manifest_sha256": (
            historical_database_manifest_sha256
        ),
        "mismatches": list(INTERRUPTED_FORWARD_DATABASE_MISMATCHES),
        "proof_consumed": False,
        "safe_status": safe_status,
        "safe_status_sha256": safe_status_sha256,
        "schema": "complete99-interrupted-forward-observation/v2",
    }


def validate_interrupted_forward_adoption_status(
    deployer: Any,
    status: dict[str, Any],
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    proof = loaded_proof["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof.get("forward_adoption")
    if not isinstance(adoption, dict):
        raise deployer.DeployError(
            "Interrupted forward adoption requires a reviewed v2 proof"
        )
    validate_database_manifest(
        deployer,
        status.get("database_manifest"),
        status.get("database_manifest_sha256"),
        "Adopted interrupted forward database",
    )
    validate_transactional_storage(
        deployer,
        status.get("database_storage"),
        "Adopted interrupted forward database",
    )
    if (
        status.get("deployment_id") != failed["deployment_id"]
        or status.get("phase") != "installed"
        or status.get("state_exists") is not True
        or status.get("lock_owned") is not True
        or status.get("process_lock_available") is not True
        or status.get("stabilized") is not True
        or status.get("expected_sha256") != failed["artifact_sha256"]
        or status.get("expected_version") != failed["version"]
        or status.get("installed_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or status.get("current_target_dir_exists") is not True
        or status.get("current_plugin_main_exists") is not True
        or status.get("current_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or status.get("current_active") is not True
        or status.get("current_version") != failed["version"]
        or status.get("runtime_loaded") is not True
        or status.get("runtime_version") != failed["version"]
        or status.get("migration_failed") is not False
        or status.get("migration_invariants_valid") is not True
        or status.get("no_rollback_artifacts") is not True
        or status.get("current_deployment") != failed["deployment_id"]
        or status.get("current_database_version") != failed["version"]
        or status.get("database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or status.get("post_install_database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or status.get("database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
        or not exact_json_equal(
            status.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        or not exact_json_equal(
            status.get("database_storage"),
            adoption["observed_database_storage"],
        )
        or status.get("current_sync_configured") is not True
        or status.get("robots_applied") is not True
        or status.get("robots_managed_sha256") != prior["robots_sha256"]
        or status.get("current_robots_sha256") != prior["robots_sha256"]
        or status.get("adopted_forward_no_rollback") is not True
        or status.get("interrupted_forward_proof_sha256")
        != loaded_proof["proof_sha256"]
        or status.get("interrupted_forward_database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
    ):
        raise deployer.DeployError(
            "Interrupted forward adoption receipt did not match the exact reviewed release"
        )
    return {
        "adopted_forward_no_rollback": True,
        "database_fingerprint": adoption["observed_database_fingerprint"],
        "database_manifest_sha256": adoption[
            "observed_database_manifest_sha256"
        ],
        "deployment_id": failed["deployment_id"],
        "installed_plugin_sha256": failed["installed_plugin_sha256"],
        "interrupted_forward_proof_sha256": loaded_proof["proof_sha256"],
        "phase": "installed",
        "state_exists": True,
        "version": failed["version"],
    }


def validate_interrupted_forward_finalize_status(
    deployer: Any,
    status: dict[str, Any],
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    """Authenticate one adopted release whose finalization was interrupted."""
    proof = loaded_proof["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof.get("forward_adoption")
    phase = status.get("phase")
    terminal_phases = {"committing", "commit_failed", "committed", "cleanup_failed"}
    state_exists = status.get("state_exists") is True
    lock_only = (
        status.get("state_exists") is False
        and phase in {"committed", "cleanup_failed"}
    )
    if not isinstance(adoption, dict) or phase not in terminal_phases:
        raise deployer.DeployError(
            "Interrupted forward finalize resume requires a reviewed adopted terminal state"
        )
    validate_database_manifest(
        deployer,
        status.get("database_manifest"),
        status.get("database_manifest_sha256"),
        "Interrupted forward finalize-resume database",
    )
    validate_transactional_storage(
        deployer,
        status.get("database_storage"),
        "Interrupted forward finalize-resume database",
    )
    if (
        status.get("deployment_id") != failed["deployment_id"]
        or not (state_exists or lock_only)
        or status.get("lock_owned") is not True
        or status.get("process_lock_available") is not True
        or status.get("recovery_ready") is not (phase == "committing")
        or status.get("stabilized") is not True
        or status.get("expected_sha256") != failed["artifact_sha256"]
        or status.get("expected_version") != failed["version"]
        or status.get("installed_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or status.get("current_target_dir_exists") is not True
        or status.get("current_plugin_main_exists") is not True
        or status.get("current_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or status.get("current_active") is not True
        or status.get("current_version") != failed["version"]
        or status.get("runtime_loaded") is not True
        or status.get("runtime_version") != failed["version"]
        or status.get("migration_failed") is not False
        or status.get("migration_invariants_valid") is not True
        or status.get("no_rollback_artifacts") is not True
        or status.get("interrupted_forward_candidate") is not False
        or status.get("current_deployment") != failed["deployment_id"]
        or status.get("current_database_version") != failed["version"]
        or status.get("database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or status.get("post_install_database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or status.get("database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
        or not exact_json_equal(
            status.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        or not exact_json_equal(
            status.get("database_storage"),
            adoption["observed_database_storage"],
        )
        or status.get("current_sync_configured") is not True
        or status.get("robots_applied") is not True
        or status.get("robots_managed_sha256") != prior["robots_sha256"]
        or status.get("current_robots_sha256") != prior["robots_sha256"]
        or status.get("adopted_forward_no_rollback") is not True
        or status.get("interrupted_forward_proof_sha256")
        != loaded_proof["proof_sha256"]
        or status.get("interrupted_forward_database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
        or status.get("committed_outcome") != "installed"
        or status.get("committed_expected_active") is not True
        or status.get("committed_expected_absent") is not False
        or status.get("committed_expected_version") != failed["version"]
        or status.get("committed_expected_deployment")
        != failed["deployment_id"]
        or status.get("committed_expected_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or status.get("committed_expected_robots_exists") is not True
        or status.get("committed_expected_robots_sha256")
        != prior["robots_sha256"]
    ):
        raise deployer.DeployError(
            "Interrupted forward finalize resume did not match the exact adopted release"
        )
    return {
        "adopted_forward_no_rollback": True,
        "committed_expected_active": True,
        "committed_expected_absent": False,
        "committed_expected_deployment": failed["deployment_id"],
        "committed_expected_plugin_sha256": failed["installed_plugin_sha256"],
        "committed_expected_robots_exists": True,
        "committed_expected_robots_sha256": prior["robots_sha256"],
        "committed_expected_version": failed["version"],
        "committed_outcome": "installed",
        "database_fingerprint": adoption["observed_database_fingerprint"],
        "database_manifest_sha256": adoption[
            "observed_database_manifest_sha256"
        ],
        "deployment_id": failed["deployment_id"],
        "installed_plugin_sha256": failed["installed_plugin_sha256"],
        "interrupted_forward_proof_sha256": loaded_proof["proof_sha256"],
        "phase": phase,
        "schema": "complete99-interrupted-forward-finalize-resume/v1",
        "state_exists": state_exists,
        "version": failed["version"],
    }


def validate_interrupted_forward_finalized_attestation(
    deployer: Any,
    response: dict[str, Any],
    probe_id: str,
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    """Validate the exact proof-only attestation for an already finalized v2 target."""
    proof = loaded_proof["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof.get("forward_adoption")
    expected_keys = {
        "active",
        "already_finalized",
        "current_database_version",
        "current_deployment",
        "database_fingerprint",
        "database_manifest",
        "database_manifest_sha256",
        "database_storage",
        "finalized_deployment_id",
        "migration_failed",
        "migration_invariants_valid",
        "plugin_sha256",
        "probe_deployment_id",
        "probe_lock_phase",
        "proof_sha256",
        "robots_sha256",
        "runtime_loaded",
        "schema",
        "sync_configured",
        "target_artifacts_absent",
        "target_state_absent",
        "version",
    }
    if (
        loaded_proof.get("schema")
        != "complete99-interrupted-forward-proof/v2"
        or not isinstance(adoption, dict)
        or not isinstance(response, dict)
        or set(response) != expected_keys
    ):
        raise deployer.DeployError(
            "Interrupted forward finalized attestation schema is invalid"
        )
    validate_database_manifest(
        deployer,
        response.get("database_manifest"),
        response.get("database_manifest_sha256"),
        "Interrupted forward finalized attestation database",
    )
    validate_transactional_storage(
        deployer,
        response.get("database_storage"),
        "Interrupted forward finalized attestation database",
    )
    if (
        response.get("schema")
        != "complete99-interrupted-forward-finalized-attestation/v1"
        or response.get("already_finalized") is not True
        or response.get("proof_sha256") != loaded_proof["proof_sha256"]
        or response.get("probe_deployment_id") != probe_id
        or response.get("finalized_deployment_id") != failed["deployment_id"]
        or response.get("version") != failed["version"]
        or response.get("plugin_sha256")
        != failed["installed_plugin_sha256"]
        or response.get("database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or response.get("database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
        or not exact_json_equal(
            response.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        or not exact_json_equal(
            response.get("database_storage"),
            adoption["observed_database_storage"],
        )
        or response.get("current_deployment") != failed["deployment_id"]
        or response.get("current_database_version") != failed["version"]
        or response.get("active") is not True
        or response.get("runtime_loaded") is not True
        or response.get("migration_failed") is not False
        or response.get("migration_invariants_valid") is not True
        or response.get("sync_configured") is not True
        or response.get("robots_sha256") != prior["robots_sha256"]
        or response.get("target_state_absent") is not True
        or response.get("target_artifacts_absent") is not True
        or response.get("probe_lock_phase") != "reserved"
    ):
        raise deployer.DeployError(
            "Interrupted forward finalized attestation does not match the exact reviewed release"
        )
    return response


def adopt_interrupted_forward(
    deployer: Any,
    client: Any,
    token: str,
    deployment_id: str,
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    proof = loaded_proof["proof"]
    failed = proof["failed_run"]
    adoption = proof.get("forward_adoption")
    if (
        loaded_proof.get("schema")
        != "complete99-interrupted-forward-proof/v2"
        or not isinstance(adoption, dict)
        or deployment_id != failed["deployment_id"]
    ):
        raise deployer.DeployError(
            "Interrupted forward adoption requires the exact reviewed v2 deployment"
        )
    response = deployer.bridge_call(
        client,
        "stabilize",
        token,
        deployment_id,
        interrupted_forward_proof_sha256=loaded_proof["proof_sha256"],
    )
    expected_response_keys = {
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
    validate_database_manifest(
        deployer,
        response.get("database_manifest"),
        response.get("database_manifest_sha256"),
        "Interrupted forward adoption response",
    )
    validate_transactional_storage(
        deployer,
        response.get("database_storage"),
        "Interrupted forward adoption response",
    )
    idempotent = response.get("idempotent")
    expected_cache_purge = {"deferred_to_finalize": True}
    if (
        set(response) != expected_response_keys
        or response.get("stabilized") is not True
        or type(idempotent) is not bool
        or response.get("adopted_forward_no_rollback") is not True
        or response.get("stabilized_from_phase") != "installing"
        or response.get("version") != failed["version"]
        or response.get("database_version") != failed["version"]
        or response.get("deployment_id") != failed["deployment_id"]
        or response.get("installed_plugin_sha256")
        != failed["installed_plugin_sha256"]
        or response.get("post_install_database_fingerprint")
        != adoption["observed_database_fingerprint"]
        or response.get("interrupted_forward_proof_sha256")
        != loaded_proof["proof_sha256"]
        or response.get("database_manifest_sha256")
        != adoption["observed_database_manifest_sha256"]
        or not exact_json_equal(
            response.get("database_manifest"),
            adoption["observed_database_manifest"],
        )
        or not exact_json_equal(
            response.get("database_storage"),
            adoption["observed_database_storage"],
        )
        or not exact_json_equal(
            response.get("cache_purge"),
            expected_cache_purge,
        )
    ):
        raise deployer.DeployError(
            "Interrupted forward stabilization did not return the exact adoption receipt"
        )
    status = deployer.bridge_call(
        client,
        "status",
        token,
        deployment_id,
    )
    status_receipt = validate_interrupted_forward_adoption_status(
        deployer,
        status,
        loaded_proof,
    )
    return {"receipt": response, "status": status_receipt}


def discover_lock_owner(
    deployer: Any,
    client: Any,
    probe_id: str,
    local_test: bool,
    target_host: str,
    allowed_hosts: set[str],
) -> tuple[str, dict[str, Any]]:
    token = secrets.token_urlsafe(36)
    snippet_id: int | None = None
    creation_attempted = False
    owner_id = ""
    discovery: dict[str, Any] = {"probe_id": probe_id, "result": "started"}
    primary_error: Exception | None = None
    try:
        code = deployer.render_bridge(
            token,
            probe_id,
            8 * 1024 * 1024,
            local_test,
            "",
            target_host=target_host,
            allowed_hosts=allowed_hosts,
        )
        creation_attempted = True
        snippet_id = deployer.create_snippet(client, code, probe_id)
        discovery["bootstrap_cleanup"] = deployer.remove_bootstrap_snippet(
            client,
            token,
            probe_id,
        )
        try:
            reservation = deployer.preflight_with_recovery(client, token, probe_id)
        except deployer.HTTPDeployError as error:
            if error.code != "c99_deploy_locked":
                raise
            raw_owner = str(error.data.get("deployment_id", ""))
            owner_id = validate_recovery_id(
                deployer, raw_owner, "Discovered lock owner"
            )
            discovery.update(
                {
                    "owner_deployment_id": owner_id,
                    "owner_phase": str(error.data.get("phase", "")),
                    "result": "owner-discovered",
                }
            )
        else:
            if not reservation.get("lock_reserved"):
                raise deployer.DeployError(
                    "Lock discovery probe did not reserve an empty deployment lock"
                )
            discovery["site_identity"] = deployer.verify_bridge_site_identity(
                reservation,
                target_host,
            )
            discovery["finalize"] = deployer.finalize_deployment(
                client, token, probe_id
            )
            discovery["result"] = "no-owner"
    except Exception as error:
        primary_error = error
    finally:
        try:
            discovery["cleanup"] = deployer.delete_snippet_and_prove_404(
                client,
                snippet_id,
                token,
                probe_id,
                creation_attempted,
            )
        except Exception as cleanup_error:
            discovery["cleanup"] = {
                "snippet_deleted": False,
                "route_404": False,
                "error": type(cleanup_error).__name__,
            }
            if primary_error is None:
                primary_error = cleanup_error
    if primary_error is not None:
        raise primary_error
    return owner_id, discovery


def discover_interrupted_forward_owner_or_finalized(
    deployer: Any,
    client: Any,
    probe_id: str,
    local_test: bool,
    target_host: str,
    allowed_hosts: set[str],
    loaded_proof: dict[str, Any],
) -> tuple[str, dict[str, Any], dict[str, Any] | None]:
    """Discover an owner or attest the exact already-finalized v2 target."""
    if loaded_proof.get("schema") != "complete99-interrupted-forward-proof/v2":
        raise deployer.DeployError(
            "Finalized interrupted-forward discovery requires the reviewed v2 proof"
        )
    token = secrets.token_urlsafe(36)
    snippet_id: int | None = None
    creation_attempted = False
    owner_id = ""
    discovery: dict[str, Any] = {"probe_id": probe_id, "result": "started"}
    finalized: dict[str, Any] | None = None
    primary_error: Exception | None = None
    reservation_acquired = False
    probe_finalized = False
    try:
        code = deployer.render_bridge(
            token,
            probe_id,
            8 * 1024 * 1024,
            local_test,
            "",
            target_host=target_host,
            allowed_hosts=allowed_hosts,
            **interrupted_forward_bridge_fields(
                loaded_proof,
                enable_finalized_attestation=True,
            ),
        )
        creation_attempted = True
        snippet_id = deployer.create_snippet(client, code, probe_id)
        bootstrap_cleanup = deployer.remove_bootstrap_snippet(
            client,
            token,
            probe_id,
        )
        try:
            reservation = deployer.preflight_with_recovery(client, token, probe_id)
        except deployer.HTTPDeployError as error:
            if error.code != "c99_deploy_locked":
                raise
            raw_owner = str(error.data.get("deployment_id", ""))
            owner_id = validate_recovery_id(
                deployer,
                raw_owner,
                "Discovered lock owner",
            )
            discovery.update(
                {
                    "bootstrap_cleanup": bootstrap_cleanup,
                    "owner_deployment_id": owner_id,
                    "owner_phase": str(error.data.get("phase", "")),
                    "result": "owner-discovered",
                }
            )
        else:
            if reservation.get("lock_reserved") is not True:
                raise deployer.DeployError(
                    "Finalized attestation probe did not reserve an empty deployment lock"
                )
            reservation_acquired = True
            bridge_site_identity = deployer.verify_bridge_site_identity(
                reservation,
                target_host,
            )
            attestation_response = deployer.bridge_call(
                client,
                "attest-interrupted-finalized",
                token,
                probe_id,
                interrupted_forward_proof_sha256=loaded_proof[
                    "proof_sha256"
                ],
            )
            attestation = validate_interrupted_forward_finalized_attestation(
                deployer,
                attestation_response,
                probe_id,
                loaded_proof,
            )
            proof = loaded_proof["proof"]
            failed = proof["failed_run"]
            prior = proof["prior_run"]
            health = deployer.verify_health(
                client,
                failed["version"],
                failed["deployment_id"],
                require_sync_configured=True,
            )
            rendered_home = deployer.verify_rendered_home(
                client,
                failed["version"],
                failed["deployment_id"],
                prior["deployment_id"],
            )
            robots = deployer.verify_managed_robots(
                client,
                prior["robots_sha256"],
            )
            probe_finalize = deployer.finalize_deployment(
                client,
                token,
                probe_id,
            )
            probe_finalized = True
            discovery = {
                "probe_id": probe_id,
                "probe_lock_retained_for_attestation": True,
                "result": "no-owner",
            }
            finalized = {
                "bootstrap_cleanup": bootstrap_cleanup,
                "bridge_site_identity": bridge_site_identity,
                "health": health,
                "interrupted_forward_finalized_attestation": attestation,
                "probe_finalize": probe_finalize,
                "rendered_home": rendered_home,
                "robots": robots,
            }
    except Exception as error:
        primary_error = error
    finally:
        if reservation_acquired and not probe_finalized:
            try:
                discovery["probe_finalize_cleanup"] = (
                    deployer.finalize_deployment(
                        client,
                        token,
                        probe_id,
                    )
                )
                probe_finalized = True
            except Exception as finalize_error:
                discovery["probe_finalize_cleanup"] = {
                    "finalized": False,
                    "error": type(finalize_error).__name__,
                }
                if primary_error is None:
                    primary_error = finalize_error
        try:
            cleanup = deployer.delete_snippet_and_prove_404(
                client,
                snippet_id,
                token,
                probe_id,
                creation_attempted,
            )
            if finalized is not None:
                finalized["cleanup"] = cleanup
            else:
                discovery["cleanup"] = cleanup
        except Exception as cleanup_error:
            cleanup = {
                "snippet_deleted": False,
                "route_404": False,
                "error": type(cleanup_error).__name__,
            }
            if finalized is not None:
                finalized["cleanup"] = cleanup
            else:
                discovery["cleanup"] = cleanup
            if primary_error is None:
                primary_error = cleanup_error
    if primary_error is not None:
        raise primary_error
    return owner_id, discovery, finalized


def release_stale_interrupted_forward_probe(
    deployer: Any,
    client: Any,
    probe_owner_id: str,
    local_test: bool,
    target_host: str,
    allowed_hosts: set[str],
    loaded_proof: dict[str, Any],
) -> dict[str, Any]:
    """Authenticate and release one stale, read-only v2 discovery probe."""
    failed_id = loaded_proof["proof"]["failed_run"]["deployment_id"]
    validate_recovery_id(deployer, probe_owner_id, "Stale recovery probe ID")
    if (
        loaded_proof.get("schema")
        != "complete99-interrupted-forward-proof/v2"
        or not probe_owner_id.startswith("c99-recovery-probe-")
        or probe_owner_id == failed_id
    ):
        raise deployer.DeployError(
            "Interrupted forward stale-probe cleanup requires an exact probe owner"
        )
    token = secrets.token_urlsafe(36)
    snippet_id: int | None = None
    creation_attempted = False
    evidence: dict[str, Any] = {}
    primary_error: Exception | None = None
    try:
        code = deployer.render_bridge(
            token,
            probe_owner_id,
            8 * 1024 * 1024,
            local_test,
            "",
            target_host=target_host,
            allowed_hosts=allowed_hosts,
            **interrupted_forward_bridge_fields(loaded_proof),
        )
        creation_attempted = True
        snippet_id = deployer.create_snippet(
            client,
            code,
            probe_owner_id,
        )
        evidence["bootstrap_cleanup"] = deployer.remove_bootstrap_snippet(
            client,
            token,
            probe_owner_id,
        )
        status = deployer.bridge_call(
            client,
            "status",
            token,
            probe_owner_id,
        )
        if (
            status.get("deployment_id") != probe_owner_id
            or status.get("phase") != "reserved"
            or status.get("state_exists") is not False
            or status.get("lock_owned") is not True
            or status.get("process_lock_available") is not True
            or status.get("interrupted_forward_candidate") is not False
            or status.get("adopted_forward_no_rollback") is not False
            or status.get("no_rollback_artifacts") is not True
        ):
            raise deployer.DeployError(
                "Interrupted forward stale probe is not an exact read-only reservation"
            )
        if status.get("recovery_ready") is not True:
            status = deployer.poll_deployment_status(
                client,
                token,
                probe_owner_id,
            )
        if (
            status.get("deployment_id") != probe_owner_id
            or status.get("phase") != "reserved"
            or status.get("state_exists") is not False
            or status.get("lock_owned") is not True
            or status.get("process_lock_available") is not True
            or status.get("recovery_ready") is not True
            or status.get("interrupted_forward_candidate") is not False
            or status.get("adopted_forward_no_rollback") is not False
            or status.get("no_rollback_artifacts") is not True
        ):
            raise deployer.DeployError(
                "Interrupted forward stale probe did not become safely recoverable"
            )
        evidence["bridge_site_identity"] = (
            deployer.verify_bridge_site_identity(status, target_host)
        )
        evidence["interrupted_forward_proof_sha256"] = loaded_proof[
            "proof_sha256"
        ]
        evidence["reservation_status"] = {
            "adopted_forward_no_rollback": False,
            "deployment_id": probe_owner_id,
            "interrupted_forward_candidate": False,
            "lock_owned": True,
            "no_rollback_artifacts": True,
            "phase": "reserved",
            "process_lock_available": True,
            "recovery_ready": True,
            "state_exists": False,
        }
        evidence["probe_finalize"] = deployer.finalize_deployment(
            client,
            token,
            probe_owner_id,
        )
    except Exception as error:
        primary_error = error
    finally:
        try:
            evidence["cleanup"] = deployer.delete_snippet_and_prove_404(
                client,
                snippet_id,
                token,
                probe_owner_id,
                creation_attempted,
            )
        except Exception as cleanup_error:
            evidence["cleanup"] = {
                "snippet_deleted": False,
                "route_404": False,
                "error": type(cleanup_error).__name__,
            }
            if primary_error is None:
                primary_error = cleanup_error
    if primary_error is not None:
        raise primary_error
    return evidence


def rollback_and_verify(
    deployer: Any,
    client: Any,
    token: str,
    deployment_id: str,
    audit: dict[str, Any],
    decision: str,
) -> None:
    """Rollback one uncommitted mutation and prove the exact prior state."""
    rollback = deployer.rollback_with_recovery(
        client,
        token,
        deployment_id,
    )
    if not rollback.get("rolled_back") or not rollback.get("database_restore"):
        raise deployer.DeployError("Recovery rollback was not confirmed")
    audit["rollback"] = {
        "rolled_back": True,
        "had_plugin": bool(rollback.get("had_plugin")),
        "prior_active": bool(rollback.get("prior_active")),
        "prior_version": rollback.get("prior_version", ""),
        "prior_deployment": rollback.get("prior_deployment", ""),
    }
    audit["rollback_integrity"] = deployer.verify_rollback_integrity(
        client,
        token,
        deployment_id,
        rollback,
    )
    if rollback.get("prior_active"):
        audit["prior_health"] = deployer.verify_health(
            client,
            str(rollback.get("prior_version", "")),
            str(rollback.get("prior_deployment", "")),
        )
        audit["prior_rendered_home"] = deployer.verify_rendered_home(
            client,
            str(rollback.get("prior_version", "")),
            str(rollback.get("prior_deployment", "")),
            deployment_id,
        )
    elif rollback.get("had_plugin"):
        audit["prior_inactive_plugin"] = deployer.verify_inactive_plugin(
            client,
            str(rollback.get("prior_version", "")),
        )
    else:
        audit["prior_absence"] = deployer.verify_plugin_absent(client)
    audit["finalize"] = deployer.finalize_deployment(
        client,
        token,
        deployment_id,
    )
    audit["decision"] = decision


def recover_forward_candidate(
    deployer: Any,
    client: Any,
    token: str,
    deployment_id: str,
    status: dict[str, Any],
    audit: dict[str, Any],
) -> None:
    """Stabilize a clean forward candidate or rollback its exact prior state."""
    phase = str(status.get("phase", ""))
    expected_version = str(status.get("expected_version", ""))
    installed_plugin_sha256 = str(status.get("installed_plugin_sha256", ""))
    try:
        audit["stabilize"] = deployer.stabilize_deployment(
            client,
            token,
            deployment_id,
            expected_version,
            installed_plugin_sha256,
        )
    except Exception as stabilization_error:
        audit["stabilization_failure"] = {
            "error": type(stabilization_error).__name__,
            "phase": phase,
        }
        rollback_and_verify(
            deployer,
            client,
            token,
            deployment_id,
            audit,
            "rollback_failed_forward_stabilization",
        )
        return

    audit["health"] = deployer.verify_health(
        client,
        expected_version,
        deployment_id,
    )
    audit["rendered_home"] = deployer.verify_rendered_home(
        client,
        expected_version,
        deployment_id,
    )
    audit["finalize"] = deployer.finalize_deployment(
        client,
        token,
        deployment_id,
    )
    audit["decision"] = "stabilize_completed_forward_migration"


def main() -> int:
    deployer = load_deployer()
    parser = argparse.ArgumentParser()
    parser.add_argument("--deployment-id", default="")
    parser.add_argument("--discover", action="store_true")
    parser.add_argument("--probe-id", default="")
    parser.add_argument("--bootstrap-code-snippets", action="store_true")
    parser.add_argument("--base-url", default=os.environ.get("WP_BASE_URL", ""))
    parser.add_argument(
        "--user",
        default=os.environ.get("WP_DEPLOY_USER", os.environ.get("WP_USER", "")),
    )
    parser.add_argument(
        "--allowed-deploy-hosts",
        default=os.environ.get("WP_ALLOWED_DEPLOY_HOSTS", ""),
    )
    parser.add_argument("--local-test", action="store_true")
    parser.add_argument("--audit-dir", type=Path, default=ROOT / "recovery-audit")
    parser.add_argument(
        "--orphaned-rollback-proof",
        default=os.environ.get("COMPLETE99_ORPHANED_ROLLBACK_PROOF", ""),
    )
    parser.add_argument("--observe-orphaned-rollback", action="store_true")
    parser.add_argument(
        "--interrupted-forward-proof",
        default=os.environ.get("COMPLETE99_INTERRUPTED_FORWARD_PROOF", ""),
    )
    parser.add_argument(
        "--interrupted-forward-observe-only",
        action="store_true",
    )
    parser.add_argument("--recovery-only", action="store_true")
    parser.add_argument("--dist", type=Path)
    args = parser.parse_args()
    observe_only = bool(getattr(args, "observe_orphaned_rollback", False))
    interrupted_observe_only = bool(
        getattr(args, "interrupted_forward_observe_only", False)
    )
    recovery_only = bool(getattr(args, "recovery_only", False))
    raw_orphaned_proof = str(getattr(args, "orphaned_rollback_proof", ""))
    raw_interrupted_proof = str(
        getattr(args, "interrupted_forward_proof", "")
    )
    if raw_orphaned_proof and raw_interrupted_proof:
        raise deployer.DeployError(
            "Orphaned rollback and interrupted forward proofs are mutually exclusive"
        )
    if recovery_only and raw_orphaned_proof:
        raise deployer.DeployError(
            "--recovery-only is not valid for orphaned rollback recovery"
        )
    orphaned_proof = load_orphaned_rollback_proof(
        deployer,
        raw_orphaned_proof,
    )
    interrupted_proof = load_interrupted_forward_proof(
        deployer,
        raw_interrupted_proof,
    )
    if observe_only and (not args.discover or orphaned_proof is None):
        raise deployer.DeployError(
            "Orphaned rollback observation requires --discover and a reviewed proof"
        )
    if (
        observe_only
        and orphaned_proof is not None
        and orphaned_proof.get("schema")
        != "complete99-orphaned-rollback-proof/v1"
    ):
        raise deployer.DeployError(
            "Orphaned rollback observation requires the historical v1 proof"
        )
    if interrupted_observe_only and interrupted_proof is None:
        raise deployer.DeployError(
            "Interrupted forward observation requires a reviewed proof"
        )
    if interrupted_proof is not None:
        if not args.discover:
            raise deployer.DeployError(
                "Interrupted forward recovery requires --discover"
            )
        if getattr(args, "dist", None) is None:
            raise deployer.DeployError(
                "Interrupted forward recovery requires --dist"
            )
        if interrupted_observe_only and interrupted_proof.get("schema") != (
            "complete99-interrupted-forward-proof/v1"
        ):
            raise deployer.DeployError(
                "Interrupted forward observation requires the reviewed v1 proof"
            )
        if interrupted_observe_only and recovery_only:
            raise deployer.DeployError(
                "Interrupted forward observation cannot use --recovery-only"
            )
        if not interrupted_observe_only and interrupted_proof.get("schema") != (
            "complete99-interrupted-forward-proof/v2"
        ):
            raise deployer.DeployError(
                "Interrupted forward adoption requires the reviewed v2 proof"
            )
        if not interrupted_observe_only and not recovery_only:
            raise deployer.DeployError(
                "Interrupted forward adoption requires --recovery-only"
            )
        interrupted_package = validate_interrupted_forward_dist(
            deployer,
            args.dist,
            interrupted_proof,
        )
    else:
        interrupted_package = None
        if interrupted_observe_only:
            raise deployer.DeployError(
                "Interrupted forward observation requires its reviewed proof"
            )
        if getattr(args, "dist", None) is not None:
            raise deployer.DeployError(
                "--dist is only valid with --interrupted-forward-proof"
            )
        if recovery_only:
            raise deployer.DeployError(
                "--recovery-only requires an interrupted forward v2 proof"
            )
    app_password = os.environ.get("WP_APP_PASSWORD", "")
    if not args.base_url or not args.user or not app_password:
        raise deployer.DeployError(
            "WP_BASE_URL, WP_DEPLOY_USER and WP_APP_PASSWORD are required"
        )
    target = deployer.validate_target_url(
        args.base_url,
        args.local_test,
        args.allowed_deploy_hosts,
    )
    target_host = (target.hostname or "").lower()
    allowed_hosts = (
        {target_host}
        if args.local_test
        else deployer.ALLOWED_PRODUCTION_HOSTS
        | deployer.parse_allowed_deploy_hosts(args.allowed_deploy_hosts)
    )
    if args.discover:
        probe_id = args.probe_id or (
            f"c99-recovery-probe-{int(time.time())}-{secrets.token_hex(4)}"
        )
        validate_recovery_id(deployer, probe_id, "Recovery probe ID")
    elif args.deployment_id:
        validate_recovery_id(deployer, args.deployment_id, "Recovery deployment ID")
    else:
        raise deployer.DeployError(
            "Provide --deployment-id or use --discover for owning-lock recovery"
        )

    client = deployer.Client(
        args.base_url,
        args.user,
        app_password,
        allow_local_http=args.local_test,
        allowed_deploy_hosts=args.allowed_deploy_hosts,
    )
    identity = deployer.authenticate(client)
    deployer.ensure_code_snippets(client, args.bootstrap_code_snippets)
    discovery: dict[str, Any] | None = None
    stale_probe_recovery: dict[str, Any] | None = None
    recovery_started_at = time.strftime(
        "%Y-%m-%dT%H:%M:%SZ", time.gmtime()
    )
    if args.discover:
        finalized_evidence: dict[str, Any] | None = None
        if (
            interrupted_proof is not None
            and interrupted_proof.get("schema")
            == "complete99-interrupted-forward-proof/v2"
            and recovery_only
        ):
            owner_id, discovery, finalized_evidence = (
                discover_interrupted_forward_owner_or_finalized(
                    deployer,
                    client,
                    probe_id,
                    args.local_test,
                    target_host,
                    allowed_hosts,
                    interrupted_proof,
                )
            )
            failed_interrupted_id = interrupted_proof["proof"]["failed_run"][
                "deployment_id"
            ]
            if (
                owner_id
                and owner_id != failed_interrupted_id
                and owner_id.startswith("c99-recovery-probe-")
            ):
                stale_probe_recovery = (
                    release_stale_interrupted_forward_probe(
                        deployer,
                        client,
                        owner_id,
                        args.local_test,
                        target_host,
                        allowed_hosts,
                        interrupted_proof,
                    )
                )
                owner_id, discovery, finalized_evidence = (
                    discover_interrupted_forward_owner_or_finalized(
                        deployer,
                        client,
                        probe_id,
                        args.local_test,
                        target_host,
                        allowed_hosts,
                        interrupted_proof,
                    )
                )
                if finalized_evidence is not None:
                    finalized_evidence["stale_probe_recovery"] = (
                        stale_probe_recovery
                    )
        else:
            owner_id, discovery = discover_lock_owner(
                deployer,
                client,
                probe_id,
                args.local_test,
                target_host,
                allowed_hosts,
            )
        if finalized_evidence is not None:
            audit = {
                "bootstrap_cleanup": finalized_evidence[
                    "bootstrap_cleanup"
                ],
                "bridge_site_identity": finalized_evidence[
                    "bridge_site_identity"
                ],
                "cleanup": finalized_evidence["cleanup"],
                "decision": "attest_interrupted_forward_finalized",
                "deployment_id": probe_id,
                "discovery": discovery,
                "finished_at": time.strftime(
                    "%Y-%m-%dT%H:%M:%SZ", time.gmtime()
                ),
                "health": finalized_evidence["health"],
                "identity": identity,
                "interrupted_forward_finalized_attestation": (
                    finalized_evidence[
                        "interrupted_forward_finalized_attestation"
                    ]
                ),
                "interrupted_forward_proof": {
                    "path": interrupted_proof["path"],
                    "proof_sha256": interrupted_proof["proof_sha256"],
                    "schema": interrupted_proof["schema"],
                },
                "local_test": args.local_test,
                "probe_finalize": finalized_evidence["probe_finalize"],
                "rendered_home": finalized_evidence["rendered_home"],
                "result": "already-recovered",
                "robots": finalized_evidence["robots"],
                "started_at": recovery_started_at,
            }
            if "stale_probe_recovery" in finalized_evidence:
                audit["stale_probe_recovery"] = finalized_evidence[
                    "stale_probe_recovery"
                ]
            audit_path = deployer.write_audit(
                args.audit_dir.resolve(),
                audit,
            )
            print(
                json.dumps(
                    {
                        "audit": str(audit_path),
                        "deployment_id": probe_id,
                        "result": audit["result"],
                    }
                )
            )
            return 0
        if not owner_id:
            if orphaned_proof is not None or interrupted_proof is not None:
                raise deployer.DeployError(
                    "Reviewed recovery proof was supplied but no lock owner exists"
                )
            audit = {
                "deployment_id": probe_id,
                "discovery": discovery,
                "finished_at": time.strftime(
                    "%Y-%m-%dT%H:%M:%SZ", time.gmtime()
                ),
                "identity": identity,
                "local_test": args.local_test,
                "result": "no-recovery-needed",
                "started_at": recovery_started_at,
            }
            audit_path = deployer.write_audit(args.audit_dir.resolve(), audit)
            print(
                json.dumps(
                    {
                        "audit": str(audit_path),
                        "deployment_id": probe_id,
                        "result": audit["result"],
                    }
                )
            )
            return 0
        if (
            orphaned_proof is not None
            and orphaned_proof["proof"]["failed_run"]["deployment_id"]
            != owner_id
        ):
            raise deployer.DeployError(
                "Reviewed orphaned rollback proof does not own the discovered lock"
            )
        if (
            interrupted_proof is not None
            and interrupted_proof["proof"]["failed_run"]["deployment_id"]
            != owner_id
        ):
            raise deployer.DeployError(
                "Reviewed interrupted forward proof does not own the discovered lock"
            )
        args.deployment_id = owner_id
    elif (
        orphaned_proof is not None
        and orphaned_proof["proof"]["failed_run"]["deployment_id"]
        != args.deployment_id
    ):
        raise deployer.DeployError(
            "Reviewed orphaned rollback proof does not own the requested lock"
        )

    token = secrets.token_urlsafe(36)
    snippet_id: int | None = None
    creation_attempted = False
    audit: dict[str, Any] = {
        "deployment_id": args.deployment_id,
        "discovery": discovery,
        "identity": identity,
        "local_test": args.local_test,
        "result": "started",
        "started_at": recovery_started_at,
    }
    if stale_probe_recovery is not None:
        audit["stale_probe_recovery"] = stale_probe_recovery
    primary_error: Exception | None = None
    try:
        render_fields = (
            interrupted_forward_bridge_fields(interrupted_proof)
            if interrupted_proof is not None
            else {}
        )
        code = deployer.render_bridge(
            token,
            args.deployment_id,
            8 * 1024 * 1024,
            args.local_test,
            "",
            target_host=target_host,
            allowed_hosts=allowed_hosts,
            **render_fields,
        )
        creation_attempted = True
        snippet_id = deployer.create_snippet(client, code, args.deployment_id)
        audit["bootstrap_cleanup"] = deployer.remove_bootstrap_snippet(
            client,
            token,
            args.deployment_id,
        )
        status_fields: dict[str, Any] = {}
        if orphaned_proof is not None and (
            observe_only
            or orphaned_proof.get("schema")
            == "complete99-orphaned-rollback-proof/v2"
        ):
            status_fields["projected_deployment_id"] = orphaned_proof["proof"][
                "prior_run"
            ]["deployment_id"]
        status = deployer.bridge_call(
            client,
            "status",
            token,
            args.deployment_id,
            **status_fields,
        )
        if (
            status.get("phase")
            in {
                "reserved",
                "locked",
                "prepared",
                "installing",
                "rolling_back",
                "committing",
            }
            and not status.get("recovery_ready")
        ):
            status = deployer.poll_deployment_status(
                client, token, args.deployment_id
            )
            if status_fields:
                status = deployer.bridge_call(
                    client,
                    "status",
                    token,
                    args.deployment_id,
                    **status_fields,
                )
        audit["bridge_site_identity"] = deployer.verify_bridge_site_identity(
            status,
            target_host,
        )
        audit["initial_status"] = {
            "phase": status.get("phase", ""),
            "state_exists": bool(status.get("state_exists")),
            "lock_owned": bool(status.get("lock_owned")),
            "recovery_ready": bool(status.get("recovery_ready")),
            "process_lock_available": bool(
                status.get("process_lock_available")
            ),
            "database_fingerprint": status.get("database_fingerprint", ""),
            "projected_deployment_id": status.get(
                "projected_deployment_id", ""
            ),
            "projected_database_fingerprint": status.get(
                "projected_database_fingerprint", ""
            ),
            "database_manifest_sha256": status.get(
                "database_manifest_sha256", ""
            ),
            "database_storage": status.get("database_storage", {}),
        }

        if interrupted_proof is not None:
            failed_forward = interrupted_proof["proof"]["failed_run"]
            prior_forward = interrupted_proof["proof"]["prior_run"]
            audit.pop("initial_status", None)
            audit["interrupted_forward_proof"] = {
                "path": interrupted_proof["path"],
                "proof_sha256": interrupted_proof["proof_sha256"],
                "schema": interrupted_proof["schema"],
            }
            if interrupted_observe_only:
                recovery_identity = interrupted_proof["recovery_identity"]
                database_drift = (
                    status.get("database_fingerprint")
                    != recovery_identity["database_fingerprint"]
                    and status.get("database_manifest_sha256")
                    != recovery_identity["database_manifest_sha256"]
                )
                observation_kind = "database-mismatch" if database_drift else "exact"
                try:
                    observation = (
                        validate_interrupted_forward_database_mismatch_status(
                            deployer,
                            status,
                            interrupted_proof,
                        )
                        if database_drift
                        else validate_interrupted_forward_status(
                            deployer,
                            status,
                            interrupted_proof,
                        )
                    )
                except deployer.DeployError:
                    observation = capture_interrupted_forward_mismatch_diagnostic(
                        deployer,
                        status,
                        interrupted_proof,
                    )
                    observation_kind = "mismatch-diagnostic"
                observation_commit = os.environ.get("GITHUB_SHA", "")
                if args.local_test and not observation_commit:
                    observation_commit = "0" * 40
                if (
                    deployer.re.fullmatch(r"[a-f0-9]{40}", observation_commit)
                    is None
                ):
                    raise deployer.DeployError(
                        "Interrupted forward observation requires the exact workflow commit"
                    )
                audit["commit"] = observation_commit
                audit["interrupted_forward_observation"] = observation
                audit["health"] = deployer.verify_health(
                    client,
                    failed_forward["version"],
                    failed_forward["deployment_id"],
                    require_sync_configured=True,
                )
                audit["rendered_home"] = deployer.verify_rendered_home(
                    client,
                    failed_forward["version"],
                    failed_forward["deployment_id"],
                    prior_forward["deployment_id"],
                )
                audit["robots"] = deployer.verify_managed_robots(
                    client,
                    prior_forward["robots_sha256"],
                )
                if observation_kind == "database-mismatch":
                    audit["decision"] = (
                        "observe_interrupted_forward_database_mismatch"
                    )
                    audit["proof_consumed"] = False
                    audit["result"] = (
                        "interrupted_forward_database_mismatch_observed"
                    )
                elif observation_kind == "mismatch-diagnostic":
                    audit["decision"] = (
                        "observe_interrupted_forward_mismatch_diagnostic"
                    )
                    audit["proof_consumed"] = False
                    audit["result"] = (
                        "interrupted_forward_mismatch_diagnostic_observed"
                    )
                else:
                    audit["decision"] = "observe_interrupted_forward"
                    audit["result"] = "interrupted_forward_observed"
                raise ObservationComplete()

            if status.get("phase") == "installing":
                forward_adoption = interrupted_proof["proof"].get(
                    "forward_adoption"
                )
                if (
                    isinstance(forward_adoption, dict)
                    and forward_adoption.get("schema")
                    == "complete99-interrupted-forward-adoption/v3"
                ):
                    observation = (
                        validate_interrupted_forward_robots_checkpoint_status(
                            deployer,
                            status,
                            interrupted_proof,
                        )
                    )
                else:
                    observation = validate_interrupted_forward_status(
                        deployer,
                        status,
                        interrupted_proof,
                    )
                audit["pre_adoption_observation"] = observation
                audit["pre_adoption_health"] = deployer.verify_health(
                    client,
                    failed_forward["version"],
                    failed_forward["deployment_id"],
                    require_sync_configured=True,
                )
                audit["pre_adoption_rendered_home"] = (
                    deployer.verify_rendered_home(
                        client,
                        failed_forward["version"],
                        failed_forward["deployment_id"],
                        prior_forward["deployment_id"],
                    )
                )
                audit["pre_adoption_robots"] = deployer.verify_managed_robots(
                    client,
                    prior_forward["robots_sha256"],
                )
                audit["interrupted_forward_adoption"] = (
                    adopt_interrupted_forward(
                        deployer,
                        client,
                        token,
                        args.deployment_id,
                        interrupted_proof,
                    )
                )
            elif (
                status.get("phase") == "installed"
                and status.get("adopted_forward_no_rollback") is True
            ):
                audit["interrupted_forward_adoption"] = adopt_interrupted_forward(
                    deployer,
                    client,
                    token,
                    args.deployment_id,
                    interrupted_proof,
                )
            elif (
                status.get("phase")
                in {"committing", "commit_failed", "committed", "cleanup_failed"}
                and status.get("adopted_forward_no_rollback") is True
            ):
                audit["interrupted_forward_finalize_resume"] = (
                    validate_interrupted_forward_finalize_status(
                        deployer,
                        status,
                        interrupted_proof,
                    )
                )
            else:
                raise deployer.DeployError(
                    "Interrupted forward v2 recovery found neither the reviewed candidate nor its durable adoption receipt"
                )
            audit["health"] = deployer.verify_health(
                client,
                failed_forward["version"],
                failed_forward["deployment_id"],
                require_sync_configured=True,
            )
            audit["rendered_home"] = deployer.verify_rendered_home(
                client,
                failed_forward["version"],
                failed_forward["deployment_id"],
                prior_forward["deployment_id"],
            )
            audit["robots"] = deployer.verify_managed_robots(
                client,
                prior_forward["robots_sha256"],
            )
            audit["finalize"] = deployer.finalize_deployment(
                client,
                token,
                args.deployment_id,
            )
            audit["adopted_forward_no_rollback"] = True
            audit["decision"] = "adopt_interrupted_forward"
            audit["result"] = "recovered"
            raise ObservationComplete()

        exact_orphaned_lock = (
            status.get("phase") == "rolling_back"
            and not status.get("state_exists")
            and status.get("lock_owned")
            and status.get("recovery_ready")
            and status.get("process_lock_available")
        )
        v2_reconciliation = (
            orphaned_proof["proof"].get("database_reconciliation")
            if orphaned_proof is not None
            and orphaned_proof.get("schema")
            == "complete99-orphaned-rollback-proof/v2"
            else None
        )
        matching_orphaned_receipt = (
            status.get("phase") in {"committed", "cleanup_failed"}
            and not status.get("state_exists")
            and status.get("lock_owned")
            and orphaned_proof is not None
            and status.get("orphaned_recovery_proof_sha256")
            == orphaned_proof["proof_sha256"]
            and deployer.re.fullmatch(
                r"[a-f0-9]{64}",
                str(status.get("orphaned_recovery_receipt_sha256", "")),
            )
            is not None
        )
        if matching_orphaned_receipt and isinstance(v2_reconciliation, dict):
            prior = orphaned_proof["proof"]["prior_run"]
            marker_rows_affected = status.get(
                "orphaned_marker_rows_affected"
            )
            marker_transition = status.get("orphaned_marker_transition")
            receipt_evidence_exists = status.get(
                "orphaned_recovery_evidence_exists"
            )
            receipt_evidence_sha256 = status.get(
                "orphaned_recovery_evidence_sha256"
            )
            receipt_evidence_valid = (
                type(receipt_evidence_exists) is bool
                and type(receipt_evidence_sha256) is str
                and (
                    (
                        receipt_evidence_exists
                        and deployer.re.fullmatch(
                            r"[a-f0-9]{64}", receipt_evidence_sha256
                        )
                        is not None
                    )
                    or (
                        not receipt_evidence_exists
                        and receipt_evidence_sha256 == ""
                    )
                )
            )
            failed = orphaned_proof["proof"]["failed_run"]
            matching_orphaned_receipt = (
                status.get("state_exists") is False
                and status.get("lock_owned") is True
                and status.get("recovery_ready") is False
                and status.get("process_lock_available") is True
                and receipt_evidence_valid
                and status.get("committed_outcome") == "rolled_back"
                and status.get("committed_expected_active")
                is prior["active"]
                and status.get("committed_expected_absent") is False
                and status.get("committed_expected_version")
                == prior["version"]
                and status.get("committed_expected_deployment")
                == prior["deployment_id"]
                and status.get("committed_expected_plugin_sha256")
                == prior["plugin_sha256"]
                and status.get("committed_expected_robots_exists")
                is prior["robots_exists"]
                and status.get("committed_expected_robots_sha256")
                == prior["robots_sha256"]
                and status.get("committed_expected_sync_configured")
                is prior["sync_configured"]
                and status.get("current_active") is prior["active"]
                and status.get("current_target_dir_exists") is True
                and status.get("current_plugin_main_exists") is True
                and status.get("current_version") == prior["version"]
                and status.get("current_database_version")
                == prior["database_version"]
                and status.get("current_plugin_sha256")
                == prior["plugin_sha256"]
                and status.get("current_sync_configured")
                is prior["sync_configured"]
                and status.get("current_robots_sha256")
                == prior["robots_sha256"]
                and status.get("orphaned_recovery_receipt_schema")
                == "complete99-orphaned-rollback-receipt/v2"
                and status.get("orphaned_reconciliation_mode")
                == v2_reconciliation["mode"]
                and status.get("orphaned_prior_proof_sha256")
                == v2_reconciliation["prior_proof_sha256"]
                and status.get("orphaned_attestation_run_id")
                == v2_reconciliation["attestation_run_id"]
                and status.get("orphaned_attestation_sha256")
                == v2_reconciliation["attestation_sha256"]
                and status.get("orphaned_attestation_audit_sha256")
                == v2_reconciliation["attestation_audit_sha256"]
                and status.get("orphaned_attestation_source_commit")
                == v2_reconciliation["attestation_source_commit"]
                and status.get(
                    "orphaned_historical_baseline_database_fingerprint"
                )
                == v2_reconciliation["baseline_database_fingerprint"]
                and status.get("orphaned_observed_database_fingerprint")
                == v2_reconciliation["observed_database_fingerprint"]
                and status.get("orphaned_preserved_manifest_sha256")
                == v2_reconciliation["preserved_manifest_sha256"]
                and status.get("committed_expected_database_fingerprint")
                == v2_reconciliation[
                    "expected_reconciled_database_fingerprint"
                ]
                and status.get("database_fingerprint")
                == v2_reconciliation[
                    "expected_reconciled_database_fingerprint"
                ]
                and status.get("database_manifest_sha256")
                == v2_reconciliation["preserved_manifest_sha256"]
                and status.get("current_deployment")
                == prior["deployment_id"]
                and status.get("expected_sha256")
                == failed["artifact_sha256"]
                and status.get("expected_version")
                == failed["candidate_version"]
                and status.get("installed_plugin_sha256")
                == failed["candidate_plugin_sha256"]
                and status.get("post_install_database_fingerprint")
                == failed["candidate_database_fingerprint"]
                and status.get("orphaned_reconciled_from")
                == "rolling_back"
                and status.get("orphaned_observed_deployment")
                == failed["deployment_id"]
                and type(marker_rows_affected) is int
                and marker_rows_affected in {0, 1}
                and marker_transition in {"corrected", "already-correct"}
                and (marker_rows_affected == 1)
                == (marker_transition == "corrected")
            )
        if orphaned_proof is not None:
            if not (exact_orphaned_lock or matching_orphaned_receipt):
                raise deployer.DeployError(
                    "Reviewed orphaned rollback proof was not consumed by its exact recovery state"
                )
            audit["orphaned_rollback_proof"] = {
                "path": orphaned_proof["path"],
                "proof_sha256": orphaned_proof["proof_sha256"],
            }
            if matching_orphaned_receipt:
                audit["initial_orphaned_rollback_receipt"] = {
                    "phase": status.get("phase", ""),
                    "state_exists": bool(status.get("state_exists")),
                    "lock_owned": bool(status.get("lock_owned")),
                    "committed_outcome": status.get("committed_outcome", ""),
                    "committed_expected_active": bool(
                        status.get("committed_expected_active")
                    ),
                    "committed_expected_absent": bool(
                        status.get("committed_expected_absent")
                    ),
                    "committed_expected_version": status.get(
                        "committed_expected_version", ""
                    ),
                    "committed_expected_deployment": status.get(
                        "committed_expected_deployment", ""
                    ),
                    "committed_expected_plugin_sha256": status.get(
                        "committed_expected_plugin_sha256", ""
                    ),
                    "committed_expected_database_fingerprint": status.get(
                        "committed_expected_database_fingerprint", ""
                    ),
                    "committed_expected_robots_exists": bool(
                        status.get("committed_expected_robots_exists")
                    ),
                    "committed_expected_robots_sha256": status.get(
                        "committed_expected_robots_sha256", ""
                    ),
                    "committed_expected_sync_configured": bool(
                        status.get("committed_expected_sync_configured")
                    ),
                    "orphaned_recovery_proof_sha256": status.get(
                        "orphaned_recovery_proof_sha256", ""
                    ),
                    "orphaned_recovery_receipt_sha256": status.get(
                        "orphaned_recovery_receipt_sha256", ""
                    ),
                    "orphaned_reconciled_from": status.get(
                        "orphaned_reconciled_from", ""
                    ),
                    "orphaned_observed_deployment": status.get(
                        "orphaned_observed_deployment", ""
                    ),
                    "orphaned_recovery_evidence_exists": bool(
                        status.get("orphaned_recovery_evidence_exists")
                    ),
                    "orphaned_recovery_evidence_sha256": status.get(
                        "orphaned_recovery_evidence_sha256", ""
                    ),
                }
                if (
                    orphaned_proof.get("schema")
                    == "complete99-orphaned-rollback-proof/v2"
                ):
                    audit["initial_orphaned_rollback_receipt"].update(
                        {
                            "expected_sha256": status.get(
                                "expected_sha256", ""
                            ),
                            "expected_version": status.get(
                                "expected_version", ""
                            ),
                            "installed_plugin_sha256": status.get(
                                "installed_plugin_sha256", ""
                            ),
                            "post_install_database_fingerprint": status.get(
                                "post_install_database_fingerprint", ""
                            ),
                            "orphaned_reconciliation_mode": status.get(
                                "orphaned_reconciliation_mode", ""
                            ),
                            "orphaned_prior_proof_sha256": status.get(
                                "orphaned_prior_proof_sha256", ""
                            ),
                            "orphaned_attestation_run_id": status.get(
                                "orphaned_attestation_run_id", 0
                            ),
                            "orphaned_attestation_sha256": status.get(
                                "orphaned_attestation_sha256", ""
                            ),
                            "orphaned_attestation_audit_sha256": status.get(
                                "orphaned_attestation_audit_sha256", ""
                            ),
                            "orphaned_attestation_source_commit": status.get(
                                "orphaned_attestation_source_commit", ""
                            ),
                            "orphaned_recovery_receipt_schema": status.get(
                                "orphaned_recovery_receipt_schema", ""
                            ),
                            "orphaned_historical_baseline_database_fingerprint": status.get(
                                "orphaned_historical_baseline_database_fingerprint",
                                "",
                            ),
                            "orphaned_observed_database_fingerprint": status.get(
                                "orphaned_observed_database_fingerprint", ""
                            ),
                            "orphaned_preserved_manifest_sha256": status.get(
                                "orphaned_preserved_manifest_sha256", ""
                            ),
                            "orphaned_marker_rows_affected": status.get(
                                "orphaned_marker_rows_affected", -1
                            ),
                            "orphaned_marker_transition": status.get(
                                "orphaned_marker_transition", ""
                            ),
                        }
                    )

        if observe_only:
            if not exact_orphaned_lock or orphaned_proof is None:
                raise deployer.DeployError(
                    "Orphaned rollback observation requires the exact stale rolling_back state"
                )
            audit["orphaned_rollback_observation"] = (
                deployer.observe_orphaned_rollback(
                    args.deployment_id,
                    status,
                    orphaned_proof["proof"],
                    orphaned_proof["proof_sha256"],
                )
            )
            audit["decision"] = "observe_orphaned_rollback"
            audit["result"] = "orphaned-rollback-observed"
            raise ObservationComplete()

        if exact_orphaned_lock:
            if orphaned_proof is None:
                raise deployer.DeployError(
                    "Orphaned rollback requires a reviewed recovery proof"
                )
            if (
                orphaned_proof["proof"]["failed_run"]["deployment_id"]
                != args.deployment_id
            ):
                raise deployer.DeployError(
                    "Orphaned rollback proof does not own the discovered lock"
                )
            audit["orphaned_rollback_reconciliation"] = (
                deployer.reconcile_orphaned_rollback(
                    client,
                    token,
                    args.deployment_id,
                    status,
                    orphaned_proof["proof"],
                    orphaned_proof["proof_sha256"],
                )
            )
            status = (
                deployer.bridge_call(
                    client,
                    "status",
                    token,
                    args.deployment_id,
                    projected_deployment_id=orphaned_proof["proof"][
                        "prior_run"
                    ]["deployment_id"],
                )
                if orphaned_proof.get("schema")
                == "complete99-orphaned-rollback-proof/v2"
                else deployer.poll_deployment_status(
                    client,
                    token,
                    args.deployment_id,
                )
            )
            audit["reconciled_status"] = {
                "phase": status.get("phase", ""),
                "state_exists": bool(status.get("state_exists")),
                "lock_owned": bool(status.get("lock_owned")),
            }
            if (
                orphaned_proof.get("schema")
                == "complete99-orphaned-rollback-proof/v2"
            ):
                audit["reconciled_status"].update(
                    {
                        "expected_sha256": status.get(
                            "expected_sha256", ""
                        ),
                        "expected_version": status.get(
                            "expected_version", ""
                        ),
                        "installed_plugin_sha256": status.get(
                            "installed_plugin_sha256", ""
                        ),
                        "post_install_database_fingerprint": status.get(
                            "post_install_database_fingerprint", ""
                        ),
                        "orphaned_reconciliation_mode": status.get(
                            "orphaned_reconciliation_mode", ""
                        ),
                        "orphaned_prior_proof_sha256": status.get(
                            "orphaned_prior_proof_sha256", ""
                        ),
                        "orphaned_attestation_run_id": status.get(
                            "orphaned_attestation_run_id", 0
                        ),
                        "orphaned_attestation_sha256": status.get(
                            "orphaned_attestation_sha256", ""
                        ),
                        "orphaned_attestation_audit_sha256": status.get(
                            "orphaned_attestation_audit_sha256", ""
                        ),
                        "orphaned_attestation_source_commit": status.get(
                            "orphaned_attestation_source_commit", ""
                        ),
                        "database_fingerprint": status.get(
                            "database_fingerprint", ""
                        ),
                        "database_manifest_sha256": status.get(
                            "database_manifest_sha256", ""
                        ),
                        "current_deployment": status.get(
                            "current_deployment", ""
                        ),
                        "orphaned_recovery_proof_sha256": status.get(
                            "orphaned_recovery_proof_sha256", ""
                        ),
                        "orphaned_recovery_receipt_sha256": status.get(
                            "orphaned_recovery_receipt_sha256", ""
                        ),
                        "orphaned_recovery_receipt_schema": status.get(
                            "orphaned_recovery_receipt_schema", ""
                        ),
                        "orphaned_historical_baseline_database_fingerprint": status.get(
                            "orphaned_historical_baseline_database_fingerprint",
                            "",
                        ),
                        "orphaned_observed_database_fingerprint": status.get(
                            "orphaned_observed_database_fingerprint", ""
                        ),
                        "orphaned_preserved_manifest_sha256": status.get(
                            "orphaned_preserved_manifest_sha256", ""
                        ),
                        "orphaned_marker_rows_affected": status.get(
                            "orphaned_marker_rows_affected", -1
                        ),
                        "orphaned_marker_transition": status.get(
                            "orphaned_marker_transition", ""
                        ),
                    }
                )

        phase = str(status.get("phase", ""))
        if phase in {"committed", "cleanup_failed"}:
            committed_outcome = str(status.get("committed_outcome", ""))
            expected_active = bool(status.get("committed_expected_active"))
            expected_absent = bool(status.get("committed_expected_absent"))
            expected_version = str(status.get("committed_expected_version", ""))
            expected_deployment = str(
                status.get("committed_expected_deployment", "")
            )
            expected_plugin_sha256 = str(
                status.get("committed_expected_plugin_sha256", "")
            )
            expected_database_fingerprint = str(
                status.get("committed_expected_database_fingerprint", "")
            )
            expected_robots_exists = bool(
                status.get("committed_expected_robots_exists")
            )
            expected_robots_sha256 = str(
                status.get("committed_expected_robots_sha256", "")
            )
            expected_sync_configured = bool(
                status.get("committed_expected_sync_configured")
            )
            orphaned_receipt = bool(
                status.get("orphaned_recovery_receipt_sha256")
            )
            exact_identity = (
                committed_outcome in {"installed", "rolled_back"}
                and bool(status.get("current_active")) == expected_active
                and str(status.get("current_deployment", ""))
                == expected_deployment
            )
            if expected_absent:
                exact_identity = (
                    exact_identity
                    and not status.get("current_target_dir_exists")
                    and not status.get("current_plugin_main_exists")
                    and not expected_active
                    and not expected_version
                    and not expected_plugin_sha256
                )
            else:
                exact_identity = (
                    exact_identity
                    and bool(status.get("current_target_dir_exists"))
                    and bool(status.get("current_plugin_main_exists"))
                    and deployer.re.fullmatch(
                        r"[a-f0-9]{64}", expected_plugin_sha256
                    )
                    is not None
                    and status.get("current_version") == expected_version
                    and status.get("current_plugin_sha256")
                    == expected_plugin_sha256
                )
            if orphaned_receipt:
                if orphaned_proof is None:
                    raise deployer.DeployError(
                        "Orphaned rollback receipt requires its reviewed proof"
                    )
                prior = orphaned_proof["proof"]["prior_run"]
                reconciliation = orphaned_proof["proof"].get(
                    "database_reconciliation"
                )
                reviewed_database_fingerprint = (
                    reconciliation["expected_reconciled_database_fingerprint"]
                    if isinstance(reconciliation, dict)
                    else prior["database_fingerprint"]
                )
                exact_identity = (
                    exact_identity
                    and committed_outcome == "rolled_back"
                    and status.get("orphaned_recovery_proof_sha256")
                    == orphaned_proof["proof_sha256"]
                    and deployer.re.fullmatch(
                        r"[a-f0-9]{64}", expected_database_fingerprint
                    )
                    is not None
                    and status.get("database_fingerprint")
                    == expected_database_fingerprint
                    and status.get("current_database_version")
                    == expected_version
                    and expected_sync_configured
                    and bool(status.get("current_sync_configured"))
                    and expected_robots_exists
                    and deployer.re.fullmatch(
                        r"[a-f0-9]{64}", expected_robots_sha256
                    )
                    is not None
                    and status.get("current_robots_sha256")
                    == expected_robots_sha256
                    and expected_version == prior["version"]
                    and expected_deployment == prior["deployment_id"]
                    and expected_plugin_sha256 == prior["plugin_sha256"]
                    and expected_database_fingerprint
                    == reviewed_database_fingerprint
                    and expected_robots_sha256 == prior["robots_sha256"]
                )
                if isinstance(reconciliation, dict):
                    marker_rows_affected = status.get(
                        "orphaned_marker_rows_affected"
                    )
                    marker_transition = status.get(
                        "orphaned_marker_transition"
                    )
                    exact_identity = (
                        exact_identity
                        and status.get("state_exists") is False
                        and status.get("lock_owned") is True
                        and status.get("recovery_ready") is False
                        and status.get("process_lock_available") is True
                        and status.get("current_target_dir_exists") is True
                        and status.get("current_plugin_main_exists") is True
                        and type(
                            status.get("orphaned_recovery_evidence_exists")
                        )
                        is bool
                        and (
                            (
                                status.get(
                                    "orphaned_recovery_evidence_exists"
                                )
                                is True
                                and deployer.re.fullmatch(
                                    r"[a-f0-9]{64}",
                                    str(
                                        status.get(
                                            "orphaned_recovery_evidence_sha256",
                                            "",
                                        )
                                    ),
                                )
                                is not None
                            )
                            or (
                                status.get(
                                    "orphaned_recovery_evidence_exists"
                                )
                                is False
                                and status.get(
                                    "orphaned_recovery_evidence_sha256", ""
                                )
                                == ""
                            )
                        )
                        and status.get("orphaned_reconciled_from")
                        == "rolling_back"
                        and status.get("orphaned_observed_deployment")
                        == orphaned_proof["proof"]["failed_run"][
                            "deployment_id"
                        ]
                        and status.get("orphaned_recovery_receipt_schema")
                        == "complete99-orphaned-rollback-receipt/v2"
                        and status.get("orphaned_reconciliation_mode")
                        == reconciliation["mode"]
                        and status.get("orphaned_prior_proof_sha256")
                        == reconciliation["prior_proof_sha256"]
                        and status.get("orphaned_attestation_run_id")
                        == reconciliation["attestation_run_id"]
                        and status.get("orphaned_attestation_sha256")
                        == reconciliation["attestation_sha256"]
                        and status.get("orphaned_attestation_audit_sha256")
                        == reconciliation["attestation_audit_sha256"]
                        and status.get("orphaned_attestation_source_commit")
                        == reconciliation["attestation_source_commit"]
                        and status.get(
                            "orphaned_historical_baseline_database_fingerprint"
                        )
                        == reconciliation["baseline_database_fingerprint"]
                        and status.get(
                            "orphaned_observed_database_fingerprint"
                        )
                        == reconciliation["observed_database_fingerprint"]
                        and status.get("orphaned_preserved_manifest_sha256")
                        == reconciliation["preserved_manifest_sha256"]
                        and status.get("database_manifest_sha256")
                        == reconciliation["preserved_manifest_sha256"]
                        and type(marker_rows_affected) is int
                        and marker_rows_affected in {0, 1}
                        and marker_transition
                        in {"corrected", "already-correct"}
                        and (marker_rows_affected == 1)
                        == (marker_transition == "corrected")
                    )
            if not exact_identity:
                raise deployer.DeployError(
                    "Committed recovery refused cleanup without the exact healthy release identity"
                )
            if orphaned_receipt and isinstance(reconciliation, dict):
                audit["pre_finalize_orphaned_identity"] = {
                    "phase": status.get("phase", ""),
                    "state_exists": status.get("state_exists"),
                    "lock_owned": status.get("lock_owned"),
                    "recovery_ready": status.get("recovery_ready"),
                    "process_lock_available": status.get(
                        "process_lock_available"
                    ),
                    "current_active": status.get("current_active"),
                    "current_target_dir_exists": status.get(
                        "current_target_dir_exists"
                    ),
                    "current_plugin_main_exists": status.get(
                        "current_plugin_main_exists"
                    ),
                    "current_version": status.get("current_version", ""),
                    "current_database_version": status.get(
                        "current_database_version", ""
                    ),
                    "current_deployment": status.get(
                        "current_deployment", ""
                    ),
                    "current_plugin_sha256": status.get(
                        "current_plugin_sha256", ""
                    ),
                    "current_sync_configured": status.get(
                        "current_sync_configured"
                    ),
                    "current_robots_sha256": status.get(
                        "current_robots_sha256", ""
                    ),
                    "database_fingerprint": status.get(
                        "database_fingerprint", ""
                    ),
                    "database_manifest_sha256": status.get(
                        "database_manifest_sha256", ""
                    ),
                }
            if expected_absent:
                audit["committed_absence"] = deployer.verify_plugin_absent(client)
            elif expected_active:
                audit["health"] = deployer.verify_health(
                    client,
                    expected_version,
                    expected_deployment,
                    require_sync_configured=expected_sync_configured,
                )
                audit["rendered_home"] = deployer.verify_rendered_home(
                    client,
                    expected_version,
                    expected_deployment,
                    args.deployment_id if orphaned_receipt else "",
                )
                if orphaned_receipt:
                    audit["orphaned_rollback_robots"] = (
                        deployer.verify_prior_robots(
                            client,
                            expected_robots_exists,
                            expected_robots_sha256,
                        )
                    )
            else:
                audit["committed_inactive_plugin"] = (
                    deployer.verify_inactive_plugin(client, expected_version)
                )
            audit["finalize"] = deployer.finalize_deployment(
                client, token, args.deployment_id
            )
            audit["decision"] = (
                "finish_orphaned_rollback_cleanup"
                if orphaned_receipt
                else "finish_committed_cleanup"
            )
        elif phase == "finalized" and not status.get("state_exists") and not status.get(
            "lock_owned"
        ):
            audit["decision"] = "already_finalized"
        elif not status.get("state_exists"):
            if phase == "reserved" or (
                phase == "locked" and status.get("recovery_ready")
            ):
                audit["finalize"] = deployer.finalize_deployment(
                    client, token, args.deployment_id
                )
                audit["decision"] = "release_unstarted_lock"
            else:
                raise deployer.DeployError(
                    f"Recovery found no rollback state for phase={phase or 'missing'}"
                )
        elif (
            phase == "installed"
            and status.get("stabilized")
            and status.get("current_active")
            and status.get("current_version") == status.get("expected_version")
            and status.get("current_database_version")
            == status.get("expected_version")
            and status.get("current_deployment") == args.deployment_id
            and status.get("current_plugin_sha256")
            == status.get("installed_plugin_sha256")
            and not status.get("sync_configuration_pending")
            and status.get("database_fingerprint")
            == status.get("post_install_database_fingerprint")
        ):
            expected_version = str(status.get("expected_version", ""))
            audit["health"] = deployer.verify_health(
                client,
                expected_version,
                args.deployment_id,
            )
            audit["rendered_home"] = deployer.verify_rendered_home(
                client,
                expected_version,
                args.deployment_id,
            )
            audit["finalize"] = deployer.finalize_deployment(
                client, token, args.deployment_id
            )
            audit["decision"] = "finish_stabilized_forward_cleanup"
        elif (
            phase
            in {
                "installed",
                "installed_pending_cleanup",
                "installed_pending_stabilization",
            }
            and status.get("forward_stabilization_candidate")
            and status.get("current_active")
            and status.get("current_version") == status.get("expected_version")
            and status.get("current_database_version")
            == status.get("expected_version")
            and deployer.re.fullmatch(
                r"[a-f0-9]{64}",
                str(status.get("installed_plugin_sha256", "")),
            )
            is not None
            and status.get("current_plugin_sha256")
            == status.get("installed_plugin_sha256")
        ):
            recover_forward_candidate(
                deployer,
                client,
                token,
                args.deployment_id,
                status,
                audit,
            )
        else:
            rollback_and_verify(
                deployer,
                client,
                token,
                args.deployment_id,
                audit,
                "rollback_interrupted_mutation",
            )
        audit["result"] = "recovered"
    except ObservationComplete:
        pass
    except Exception as error:
        primary_error = error
        audit["result"] = "failed"
        audit["error"] = type(error).__name__
    finally:
        try:
            audit["cleanup"] = deployer.delete_snippet_and_prove_404(
                client,
                snippet_id,
                token,
                args.deployment_id,
                creation_attempted,
            )
        except Exception as cleanup_error:
            audit["cleanup"] = {
                "snippet_deleted": False,
                "route_404": False,
                "error": type(cleanup_error).__name__,
            }
            if primary_error is None:
                primary_error = cleanup_error
                audit["result"] = "failed"
        audit["finished_at"] = time.strftime(
            "%Y-%m-%dT%H:%M:%SZ", time.gmtime()
        )
        audit_path = deployer.write_audit(args.audit_dir.resolve(), audit)

    summary = {
        "audit": str(audit_path),
        "deployment_id": args.deployment_id,
        "result": audit["result"],
    }
    if "proof_consumed" in audit:
        summary["proof_consumed"] = audit["proof_consumed"]
    print(json.dumps(summary))
    if primary_error:
        raise primary_error
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(f"RECOVERY FAILED: {error}", file=sys.stderr)
        raise SystemExit(1)
