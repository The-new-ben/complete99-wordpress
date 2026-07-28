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


def main() -> int:
    deployer = load_deployer()
    parser = argparse.ArgumentParser()
    parser.add_argument("--deployment-id", required=True)
    parser.add_argument("--base-url", default=os.environ.get("WP_BASE_URL", ""))
    parser.add_argument(
        "--user",
        default=os.environ.get("WP_DEPLOY_USER", os.environ.get("WP_USER", "")),
    )
    parser.add_argument("--local-test", action="store_true")
    parser.add_argument("--audit-dir", type=Path, default=ROOT / "recovery-audit")
    args = parser.parse_args()
    app_password = os.environ.get("WP_APP_PASSWORD", "")
    if not args.base_url or not args.user or not app_password:
        raise deployer.DeployError(
            "WP_BASE_URL, WP_DEPLOY_USER and WP_APP_PASSWORD are required"
        )
    deployer.validate_target_url(args.base_url, args.local_test)
    if not deployer.re.fullmatch(r"[A-Za-z0-9._-]{8,96}", args.deployment_id):
        raise deployer.DeployError("Recovery deployment ID is invalid")

    client = deployer.Client(
        args.base_url,
        args.user,
        app_password,
        allow_local_http=args.local_test,
    )
    token = secrets.token_urlsafe(36)
    snippet_id: int | None = None
    creation_attempted = False
    audit: dict[str, Any] = {
        "deployment_id": args.deployment_id,
        "local_test": args.local_test,
        "result": "started",
        "started_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
    }
    primary_error: Exception | None = None
    try:
        audit["identity"] = deployer.authenticate(client)
        deployer.ensure_code_snippets(client, False)
        code = deployer.render_bridge(
            token,
            args.deployment_id,
            8 * 1024 * 1024,
            args.local_test,
            "",
        )
        creation_attempted = True
        snippet_id = deployer.create_snippet(client, code, args.deployment_id)
        status = deployer.bridge_call(
            client, "status", token, args.deployment_id
        )
        if (
            status.get("phase")
            in {"locked", "prepared", "installing", "rolling_back", "committing"}
            and not status.get("recovery_ready")
        ):
            status = deployer.poll_deployment_status(
                client, token, args.deployment_id
            )
        audit["initial_status"] = {
            "phase": status.get("phase", ""),
            "state_exists": bool(status.get("state_exists")),
            "lock_owned": bool(status.get("lock_owned")),
            "recovery_ready": bool(status.get("recovery_ready")),
        }

        phase = str(status.get("phase", ""))
        if phase in {"committed", "cleanup_failed"}:
            if (
                status.get("current_active")
                and status.get("current_version")
                and status.get("current_deployment")
            ):
                audit["health"] = deployer.verify_health(
                    client,
                    str(status["current_version"]),
                    str(status["current_deployment"]),
                )
                audit["rendered_home"] = deployer.verify_rendered_home(
                    client,
                    str(status["current_version"]),
                    str(status["current_deployment"]),
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
