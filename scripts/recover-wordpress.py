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


def reject_duplicate_json_object(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
    result: dict[str, Any] = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f"Duplicate JSON key: {key}")
        result[key] = value
    return result


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


def load_orphaned_rollback_proof(
    deployer: Any,
    raw_path: str,
) -> dict[str, Any] | None:
    if not raw_path:
        return None
    candidate = Path(raw_path)
    path = (ROOT / candidate).resolve() if not candidate.is_absolute() else candidate.resolve()
    proof_root = (ROOT / "docs" / "recovery-proofs").resolve()
    if proof_root not in path.parents or path.suffix.lower() != ".json":
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
    if (
        not isinstance(envelope, dict)
        or set(envelope) != {"schema", "proof", "proof_sha256"}
        or envelope.get("schema") != "complete99-orphaned-rollback-proof/v1"
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
    if (
        set(proof) != {"failed_run", "prior_run"}
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
        or failed["run_id"] == prior["run_id"]
        or str(failed["run_id"]) not in failed["deployment_id"]
        or str(prior["run_id"]) not in prior["deployment_id"]
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
    return {
        "path": str(path.relative_to(ROOT)).replace("\\", "/"),
        "proof": proof,
        "proof_sha256": proof_sha256,
    }


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
    args = parser.parse_args()
    orphaned_proof = load_orphaned_rollback_proof(
        deployer,
        str(args.orphaned_rollback_proof),
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
    if args.discover:
        owner_id, discovery = discover_lock_owner(
            deployer,
            client,
            probe_id,
            args.local_test,
            target_host,
            allowed_hosts,
        )
        if not owner_id:
            if orphaned_proof is not None:
                raise deployer.DeployError(
                    "Reviewed orphaned rollback proof was supplied but no lock owner exists"
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
                "started_at": time.strftime(
                    "%Y-%m-%dT%H:%M:%SZ", time.gmtime()
                ),
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
        "started_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
    }
    primary_error: Exception | None = None
    try:
        code = deployer.render_bridge(
            token,
            args.deployment_id,
            8 * 1024 * 1024,
            args.local_test,
            "",
            target_host=target_host,
            allowed_hosts=allowed_hosts,
        )
        creation_attempted = True
        snippet_id = deployer.create_snippet(client, code, args.deployment_id)
        audit["bootstrap_cleanup"] = deployer.remove_bootstrap_snippet(
            client,
            token,
            args.deployment_id,
        )
        status = deployer.bridge_call(
            client, "status", token, args.deployment_id
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
        audit["bridge_site_identity"] = deployer.verify_bridge_site_identity(
            status,
            target_host,
        )
        audit["initial_status"] = {
            "phase": status.get("phase", ""),
            "state_exists": bool(status.get("state_exists")),
            "lock_owned": bool(status.get("lock_owned")),
            "recovery_ready": bool(status.get("recovery_ready")),
        }

        exact_orphaned_lock = (
            status.get("phase") == "rolling_back"
            and not status.get("state_exists")
            and status.get("lock_owned")
            and status.get("recovery_ready")
            and status.get("process_lock_available")
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
            status = deployer.poll_deployment_status(
                client,
                token,
                args.deployment_id,
            )
            audit["reconciled_status"] = {
                "phase": status.get("phase", ""),
                "state_exists": bool(status.get("state_exists")),
                "lock_owned": bool(status.get("lock_owned")),
            }

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
                    == prior["database_fingerprint"]
                    and expected_robots_sha256 == prior["robots_sha256"]
                )
            if not exact_identity:
                raise deployer.DeployError(
                    "Committed recovery refused cleanup without the exact healthy release identity"
                )
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

    print(
        json.dumps(
            {
                "audit": str(audit_path),
                "deployment_id": args.deployment_id,
                "result": audit["result"],
            }
        )
    )
    if primary_error:
        raise primary_error
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(f"RECOVERY FAILED: {error}", file=sys.stderr)
        raise SystemExit(1)
