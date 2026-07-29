#!/usr/bin/env python3
"""Recreate a temporary bridge and recover one interrupted Complete99 deployment."""

from __future__ import annotations

import argparse
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
    args = parser.parse_args()
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
        args.deployment_id = owner_id

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
                )
                audit["rendered_home"] = deployer.verify_rendered_home(
                    client,
                    expected_version,
                    expected_deployment,
                )
            else:
                audit["committed_inactive_plugin"] = (
                    deployer.verify_inactive_plugin(client, expected_version)
                )
            audit["finalize"] = deployer.finalize_deployment(
                client, token, args.deployment_id
            )
            audit["decision"] = "finish_committed_cleanup"
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
            expected_version = str(status.get("expected_version", ""))
            installed_plugin_sha256 = str(
                status.get("installed_plugin_sha256", "")
            )
            audit["stabilize"] = deployer.stabilize_deployment(
                client,
                token,
                args.deployment_id,
                expected_version,
                installed_plugin_sha256,
            )
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
            audit["decision"] = "stabilize_completed_forward_migration"
        else:
            rollback = deployer.rollback_with_recovery(
                client, token, args.deployment_id
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
                client, token, args.deployment_id, rollback
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
                    args.deployment_id,
                )
            elif rollback.get("had_plugin"):
                audit["prior_inactive_plugin"] = deployer.verify_inactive_plugin(
                    client, str(rollback.get("prior_version", ""))
                )
            else:
                audit["prior_absence"] = deployer.verify_plugin_absent(client)
            audit["finalize"] = deployer.finalize_deployment(
                client, token, args.deployment_id
            )
            audit["decision"] = "rollback_interrupted_mutation"
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
