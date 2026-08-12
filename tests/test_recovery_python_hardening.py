from __future__ import annotations

import copy
import contextlib
import hashlib
import http.client
import importlib.util
import json
import re
import shutil
import sys
import tempfile
import types
import unittest
import urllib.error
import urllib.parse
from pathlib import Path
from typing import Any, Callable
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


def load_module(name: str, path: Path) -> Any:
    spec = importlib.util.spec_from_file_location(name, path)
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


DEPLOY = load_module(
    "complete99_recovery_regression_deployer",
    ROOT / "scripts" / "deploy-wordpress.py",
)
RECOVER = load_module(
    "complete99_recovery_regression_driver",
    ROOT / "scripts" / "recover-wordpress.py",
)


def make_client(*, timeout: int = 180) -> Any:
    return DEPLOY.Client(
        "http://127.0.0.1",
        "local-admin",
        "local-test-only",
        allow_local_http=True,
        timeout=timeout,
    )


def valid_proof() -> dict[str, Any]:
    return {
        "failed_run": {
            "artifact_sha256": "1" * 64,
            "audit_sha256": "2" * 64,
            "candidate_plugin_sha256": "3" * 64,
            "candidate_database_fingerprint": "4" * 64,
            "candidate_version": "1.17.0",
            "commit": "5" * 40,
            "deployment_id": "c99-prod-failed-1234-1",
            "run_id": 1234,
        },
        "prior_run": {
            "active": True,
            "audit_sha256": "6" * 64,
            "commit": "7" * 40,
            "database_fingerprint": "8" * 64,
            "database_version": "1.16.0",
            "deployment_id": "c99-prod-prior-1200-1",
            "plugin_sha256": "9" * 64,
            "robots_exists": True,
            "robots_sha256": "a" * 64,
            "run_id": 1200,
            "sync_configured": True,
            "version": "1.16.0",
        },
    }


def proof_record(proof: dict[str, Any]) -> dict[str, Any]:
    canonical = json.dumps(
        proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    return {
        "schema": "complete99-orphaned-rollback-proof/v1",
        "proof": proof,
        "proof_sha256": hashlib.sha256(canonical).hexdigest(),
    }


def database_manifest() -> tuple[dict[str, Any], str]:
    manifest: dict[str, Any] = {
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


def interrupted_forward_loaded(*, version: int = 1) -> dict[str, Any]:
    manifest, manifest_sha256 = database_manifest()
    failed = {
        "artifact_sha256": "1" * 64,
        "baseline_database_fingerprint": "2" * 64,
        "commit": "3" * 40,
        "deploy_audit_path": "docs/recovery-proofs/observations/c99-failed-deploy.json",
        "deploy_audit_sha256": "4" * 64,
        "deployment_id": "c99-prod-2000-1",
        "installed_plugin_sha256": "5" * 64,
        "recovery_audit_path": "docs/recovery-proofs/observations/c99-failed-recovery.json",
        "recovery_audit_sha256": "6" * 64,
        "run_id": 2000,
        "source_sha256": "7" * 64,
        "version": "1.18.0",
    }
    prior = {
        "active": True,
        "commit": "8" * 40,
        "database_fingerprint": "2" * 64,
        "database_version": "1.17.0",
        "deploy_audit_path": "docs/recovery-proofs/observations/c99-prior-deploy.json",
        "deploy_audit_sha256": "9" * 64,
        "deployment_id": "c99-prod-1900-1",
        "plugin_sha256": "a" * 64,
        "robots_sha256": "b" * 64,
        "run_id": 1900,
        "sync_configured": True,
        "version": "1.17.0",
    }
    proof: dict[str, Any] = {"failed_run": failed, "prior_run": prior}
    base_sha256 = RECOVER.canonical_proof_sha256(proof)
    if version == 2:
        proof["forward_adoption"] = {
            "observation_audit_path": "docs/recovery-proofs/observations/c99-observation.json",
            "observation_audit_sha256": "c" * 64,
            "observation_commit": "d" * 40,
            "observation_proof_sha256": base_sha256,
            "observation_run_id": 2100,
            "observed_database_fingerprint": "e" * 64,
            "observed_database_manifest": manifest,
            "observed_database_manifest_sha256": manifest_sha256,
            "observed_database_storage": {"engine": "INNODB", "tables": 3},
            "observed_deployment_id": failed["deployment_id"],
            "observed_plugin_sha256": failed["installed_plugin_sha256"],
            "observed_robots_sha256": prior["robots_sha256"],
            "observed_version": failed["version"],
            "schema": "complete99-interrupted-forward-adoption/v1",
            "target_artifact_sha256": failed["artifact_sha256"],
            "target_installed_plugin_sha256": failed[
                "installed_plugin_sha256"
            ],
        }
    return {
        "base_proof_sha256": base_sha256,
        "path": (
            "docs/recovery-proofs/c99-prod-2000-1-v2.json"
            if version == 2
            else "docs/recovery-proofs/c99-prod-2000-1.json"
        ),
        "proof": proof,
        "proof_sha256": RECOVER.canonical_proof_sha256(proof),
        "recovery_identity": {
            "database_fingerprint": (
                "e" * 64 if version == 2 else "e" * 64
            ),
            "database_manifest_sha256": manifest_sha256,
        },
        "schema": f"complete99-interrupted-forward-proof/v{version}",
    }


def interrupted_forward_status(
    loaded: dict[str, Any],
    *,
    adopted: bool = False,
    phase: str | None = None,
    state_exists: bool = True,
) -> dict[str, Any]:
    proof = loaded["proof"]
    failed = proof["failed_run"]
    prior = proof["prior_run"]
    adoption = proof.get("forward_adoption")
    manifest, manifest_sha256 = database_manifest()
    database_fingerprint = "e" * 64
    if isinstance(adoption, dict):
        manifest = adoption["observed_database_manifest"]
        manifest_sha256 = adoption["observed_database_manifest_sha256"]
        database_fingerprint = adoption["observed_database_fingerprint"]
    status_phase = phase or ("installed" if adopted else "installing")
    terminal = status_phase in {
        "committing",
        "commit_failed",
        "committed",
        "cleanup_failed",
    }
    return {
        "adopted_forward_no_rollback": adopted,
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
        "database_fingerprint": database_fingerprint,
        "database_fingerprint_available": True,
        "database_manifest": manifest,
        "database_manifest_sha256": manifest_sha256,
        "database_restored": False,
        "database_storage": {"engine": "INNODB", "tables": 3},
        "deployment_id": failed["deployment_id"],
        "expected_sha256": failed["artifact_sha256"],
        "expected_version": failed["version"],
        "had_plugin": True,
        "installed_plugin_sha256": (
            failed["installed_plugin_sha256"] if adopted else ""
        ),
        "interrupted_forward_candidate": not adopted,
        "interrupted_forward_database_manifest_sha256": (
            manifest_sha256 if adopted else ""
        ),
        "interrupted_forward_proof_sha256": (
            loaded["proof_sha256"] if adopted else ""
        ),
        "lock_owned": True,
        "migration_failed": False,
        "migration_invariants_valid": True,
        "no_rollback_artifacts": True,
        "phase": status_phase,
        "post_install_database_fingerprint": (
            database_fingerprint
        ),
        "prior_active": True,
        "prior_deployment": prior["deployment_id"],
        "prior_plugin_main_exists": True,
        "prior_plugin_sha256": prior["plugin_sha256"],
        "prior_target_dir_exists": True,
        "prior_version": prior["version"],
        "process_lock_available": True,
        "recovery_ready": (not adopted) or status_phase == "committing",
        "robots_applied": True,
        "robots_managed_sha256": prior["robots_sha256"],
        "robots_prior_exists": True,
        "robots_prior_sha256": prior["robots_sha256"],
        "robots_restored": False,
        "runtime_loaded": True,
        "runtime_version": failed["version"],
        "stabilized": adopted,
        "state_exists": state_exists,
        "committed_outcome": "installed" if terminal else "",
        "committed_expected_active": terminal,
        "committed_expected_absent": False,
        "committed_expected_version": failed["version"] if terminal else "",
        "committed_expected_deployment": (
            failed["deployment_id"] if terminal else ""
        ),
        "committed_expected_plugin_sha256": (
            failed["installed_plugin_sha256"] if terminal else ""
        ),
        "committed_expected_robots_exists": terminal,
        "committed_expected_robots_sha256": (
            prior["robots_sha256"] if terminal else ""
        ),
    }


def interrupted_robots_checkpoint_loaded() -> tuple[
    dict[str, Any], dict[str, Any]
]:
    loaded = interrupted_forward_loaded(version=2)
    loaded["proof"]["forward_adoption"][
        "schema"
    ] = "complete99-interrupted-forward-adoption/v3"
    loaded["proof_sha256"] = RECOVER.canonical_proof_sha256(loaded["proof"])
    status = interrupted_forward_status(loaded)
    status["interrupted_forward_candidate"] = False
    status["robots_applied"] = False
    status["robots_managed_sha256"] = ""
    receipt = RECOVER.capture_interrupted_forward_mismatch_diagnostic(
        DEPLOY,
        status,
        loaded,
    )
    loaded["reviewed_forward_observation"] = receipt
    return loaded, status


def interrupted_finalized_attestation(
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


class _Response:
    def __init__(self, url: str, body: bytes = b"{}", status: int = 200) -> None:
        self.url = url
        self.body = body
        self.status = status

    def __enter__(self) -> "_Response":
        return self

    def __exit__(self, *_args: object) -> None:
        return None

    def geturl(self) -> str:
        return self.url

    def read(self, *_args: object) -> bytes:
        return self.body


class _TruncatedResponse(_Response):
    def read(self, *_args: object) -> bytes:
        raise http.client.IncompleteRead(b"{", 1)


class _TruncatedHTTPError(urllib.error.HTTPError):
    def read(self, *_args: object) -> bytes:
        raise http.client.IncompleteRead(b"{", 1)


class _SequenceOpener:
    def __init__(self, outcomes: list[object]) -> None:
        self.outcomes = list(outcomes)
        self.timeouts: list[int] = []

    def open(self, request: Any, timeout: int) -> Any:
        self.timeouts.append(timeout)
        outcome = self.outcomes.pop(0)
        if isinstance(outcome, BaseException):
            raise outcome
        return outcome


class SafeReadRetryTests(unittest.TestCase):
    def test_client_rejects_duplicate_json_response_keys(self) -> None:
        raw = b'{"phase":"committed","phase":"rolling_back"}'
        parsed = DEPLOY.Client._parse_json_response(raw)
        self.assertEqual(
            {"invalid_json_response": True, "length": len(raw)},
            parsed,
        )

    def test_duplicate_json_403_never_replays_mutation_via_rest_fallback(
        self,
    ) -> None:
        client = make_client()
        request_once = mock.Mock(
            return_value=(403, b'{"code":"blocked","code":"blocked"}')
        )
        with mock.patch.object(
            client,
            "_request_once",
            request_once,
        ), self.assertRaisesRegex(DEPLOY.DeployError, "invalid JSON"):
            client.request(
                "POST",
                "/wp-json/complete99-deploy/v1/example/reconcile-orphaned-rollback",
                {"token": "secret"},
            )
        self.assertEqual(1, request_once.call_count)
        self.assertFalse(client.use_query_rest_transport)

    def test_expected_404_with_duplicate_json_is_not_cleanup_proof(self) -> None:
        client = make_client()
        request_once = mock.Mock(
            return_value=(404, b'{"code":"rest_no_route","code":"other"}')
        )
        with mock.patch.object(
            client,
            "_request_once",
            request_once,
        ), self.assertRaisesRegex(DEPLOY.DeployError, "invalid JSON"):
            client.request(
                "POST",
                "/wp-json/complete99-deploy/v1/example/preflight",
                {"token": "secret"},
                expected=(404,),
            )
        self.assertEqual(1, request_once.call_count)

    def test_public_health_json_rejects_duplicate_keys(self) -> None:
        client = make_client()
        raw = b'{"status":"bad","status":"ok"}'
        with mock.patch.object(
            client,
            "_bounded_public_read",
            return_value=(200, raw),
        ), self.assertRaisesRegex(DEPLOY.DeployError, "invalid JSON"):
            client.request_public_json("/wp-json/complete99/v1/health")

    def test_authenticated_get_retries_with_exact_delays_and_timeout_cap(self) -> None:
        client = make_client()
        request_once = mock.Mock(
            side_effect=[
                DEPLOY.NetworkDeployError("first"),
                DEPLOY.NetworkDeployError("second"),
                (200, b"{}"),
            ]
        )
        with mock.patch.object(client, "_request_once", request_once), mock.patch.object(
            DEPLOY.time, "sleep"
        ) as sleep:
            self.assertEqual((200, {}), client.request("GET", "/safe-read"))

        self.assertEqual(3, request_once.call_count)
        self.assertEqual([mock.call(2), mock.call(5)], sleep.call_args_list)
        self.assertEqual(
            [30, 30, 30],
            [call.kwargs["network_timeout"] for call in request_once.call_args_list],
        )

    def test_pretty_rest_fallback_has_its_own_safe_retry_budget(self) -> None:
        client = make_client()
        calls: list[str] = []

        def request_once(
            _method: str,
            path: str,
            _body: bytes | None,
            _headers: dict[str, str],
            *,
            network_timeout: int | None = None,
        ) -> tuple[int, bytes]:
            self.assertEqual(30, network_timeout)
            calls.append(path)
            if len(calls) == 1:
                return 403, b"<html>blocked by proxy</html>"
            if len(calls) in {2, 3}:
                return 503, b'{"code":"temporary"}'
            return 200, b"{}"

        with mock.patch.object(client, "_request_once", side_effect=request_once), mock.patch.object(
            DEPLOY.time, "sleep"
        ) as sleep:
            self.assertEqual(
                (200, {}),
                client.request("GET", "/wp-json/example/v1/status"),
            )

        self.assertEqual(
            [
                "/wp-json/example/v1/status",
                "/?rest_route=/example/v1/status",
                "/?rest_route=/example/v1/status",
                "/?rest_route=/example/v1/status",
            ],
            calls,
        )
        self.assertEqual([mock.call(2), mock.call(5)], sleep.call_args_list)
        self.assertTrue(client.use_query_rest_transport)

    def test_status_post_retries_but_mutation_post_is_attempted_once(self) -> None:
        status_client = make_client()
        status_once = mock.Mock(
            side_effect=[
                DEPLOY.NetworkDeployError("first"),
                DEPLOY.NetworkDeployError("second"),
                (200, b'{"phase":"finalized"}'),
            ]
        )
        with mock.patch.object(
            status_client, "_request_once", status_once
        ), mock.patch.object(DEPLOY.time, "sleep") as sleep:
            status = DEPLOY.bridge_call(
                status_client,
                "status",
                "temporary-token",
                "c99-prod-status-1234",
            )
        self.assertEqual("finalized", status["phase"])
        self.assertEqual(3, status_once.call_count)
        self.assertEqual([mock.call(2), mock.call(5)], sleep.call_args_list)
        self.assertEqual(
            [30, 30, 30],
            [call.kwargs["network_timeout"] for call in status_once.call_args_list],
        )

        mutation_client = make_client()
        mutation_once = mock.Mock(side_effect=DEPLOY.NetworkDeployError("lost"))
        with mock.patch.object(
            mutation_client, "_request_once", mutation_once
        ), mock.patch.object(DEPLOY.time, "sleep") as mutation_sleep:
            with self.assertRaises(DEPLOY.NetworkDeployError):
                DEPLOY.bridge_call(
                    mutation_client,
                    "rollback",
                    "temporary-token",
                    "c99-prod-status-1234",
                )
        self.assertEqual(1, mutation_once.call_count)
        mutation_sleep.assert_not_called()

    def test_status_does_not_retry_nontransient_malformed_or_redirect_responses(
        self,
    ) -> None:
        cases = (
            (409, b'{"code":"c99_conflict","data":{"status":409}}'),
            (200, b"not-json"),
            (302, b""),
        )
        for status, body in cases:
            with self.subTest(status=status, body=body):
                client = make_client()
                request_once = mock.Mock(return_value=(status, body))
                with mock.patch.object(
                    client, "_request_once", request_once
                ), mock.patch.object(DEPLOY.time, "sleep") as sleep:
                    with self.assertRaises(DEPLOY.DeployError):
                        DEPLOY.bridge_call(
                            client,
                            "status",
                            "temporary-token",
                            "c99-prod-status-1234",
                        )
                self.assertEqual(1, request_once.call_count)
                sleep.assert_not_called()

    def test_truncated_success_and_error_bodies_are_normalized_and_retried(self) -> None:
        url = "http://127.0.0.1/safe-read"
        for first in (
            _TruncatedResponse(url),
            _TruncatedHTTPError(url, 503, "temporary", {}, None),
        ):
            with self.subTest(first=type(first).__name__):
                client = make_client()
                opener = _SequenceOpener([first, _Response(url)])
                client.opener = opener
                with mock.patch.object(DEPLOY.time, "sleep") as sleep:
                    self.assertEqual((200, {}), client.request("GET", "/safe-read"))
                self.assertEqual([30, 30], opener.timeouts)
                sleep.assert_called_once_with(2)

        robots_url = "http://127.0.0.1/robots.txt"
        for first in (
            _TruncatedResponse(robots_url),
            _TruncatedHTTPError(robots_url, 503, "temporary", {}, None),
        ):
            with self.subTest(public_first=type(first).__name__):
                client = make_client()
                opener = _SequenceOpener([first, _Response(robots_url, b"ok")])
                client.opener = opener
                with mock.patch.object(DEPLOY.time, "sleep") as sleep:
                    self.assertEqual(
                        (200, b"ok"),
                        client.request_anonymous_bytes("/robots.txt"),
                    )
                self.assertEqual([30, 30], opener.timeouts)
                sleep.assert_called_once_with(2)


class FinalizeRecoveryTests(unittest.TestCase):
    def exact_response(self) -> dict[str, Any]:
        return {
            "cache_purge": {},
            "finalized": True,
            "lock_released": True,
            "state_removed": True,
        }

    def test_finalize_retries_semantically_invalid_success_once(self) -> None:
        bridge = mock.Mock(side_effect=[{}, self.exact_response()])
        with mock.patch.object(DEPLOY, "bridge_call", bridge), mock.patch.object(
            DEPLOY,
            "poll_deployment_status",
        ) as status:
            result = DEPLOY.finalize_deployment(
                object(),
                "token",
                "c99-prod-finalize-1234",
            )
        self.assertTrue(result["response_recovered"])
        self.assertEqual(2, bridge.call_count)
        status.assert_not_called()

    def test_finalize_rejects_truthy_non_boolean_receipt(self) -> None:
        truthy = {
            "cache_purge": {},
            "finalized": 1,
            "lock_released": "yes",
            "state_removed": 1,
        }
        bridge = mock.Mock(side_effect=[truthy, self.exact_response()])
        with mock.patch.object(DEPLOY, "bridge_call", bridge):
            result = DEPLOY.finalize_deployment(
                object(),
                "token",
                "c99-prod-finalize-1234",
            )
        self.assertTrue(result["response_recovered"])
        self.assertEqual(2, bridge.call_count)

    def test_finalize_recovers_released_lock_after_two_invalid_responses(
        self,
    ) -> None:
        bridge = mock.Mock(side_effect=[{}, {"finalized": "yes"}])
        status = {
            "phase": "finalized",
            "state_exists": False,
            "lock_owned": False,
        }
        with mock.patch.object(DEPLOY, "bridge_call", bridge), mock.patch.object(
            DEPLOY,
            "poll_deployment_status",
            return_value=status,
        ) as status_call:
            result = DEPLOY.finalize_deployment(
                object(),
                "token",
                "c99-prod-finalize-1234",
            )
        self.assertTrue(result["response_recovered"])
        self.assertTrue(result["finalized"])
        self.assertEqual(2, bridge.call_count)
        status_call.assert_called_once()


class CompletedRollbackTests(unittest.TestCase):
    def test_confirmed_rollback_is_never_issued_a_second_time(self) -> None:
        args = types.SimpleNamespace(
            allowed_deploy_hosts="",
            audit_dir=Path("unused-audit"),
            base_url="http://127.0.0.1",
            bootstrap_code_snippets=False,
            deployment_id="c99-prod-rollback-1234",
            dist=Path("unused-dist"),
            dry_run=False,
            fault_injection="",
            local_test=True,
            mutation_marker=None,
            rollback_exercise=True,
            user="local-admin",
        )
        plugin_sha = "b" * 64
        robots_sha = "c" * 64
        metadata = {
            "artifact": "complete99-platform.zip",
            "installed_sha256": plugin_sha,
            "sha256": "a" * 64,
            "slug": DEPLOY.SLUG,
            "source_sha256": "d" * 64,
            "version": "1.17.0",
        }
        preflight = {
            "allowed_slug": DEPLOY.SLUG,
            "auto_update_enabled": False,
            "current_active": True,
            "current_deployment": "c99-prod-prior-1234",
            "current_version": "1.16.0",
            "had_plugin": True,
            "lock_reserved": True,
            "ready": True,
        }
        install = {
            "installed_plugin_sha256": plugin_sha,
            "robots_sha256": robots_sha,
            "sha256": metadata["sha256"],
            "temp_removed": True,
            "version": metadata["version"],
        }
        rollback = {
            "baseline_database_fingerprint": "e" * 64,
            "database_restore": {"restored": True},
            "had_plugin": True,
            "prior_active": True,
            "prior_deployment": "c99-prod-prior-1234",
            "prior_plugin_sha256": "f" * 64,
            "prior_version": "1.16.0",
            "robots_prior_exists": True,
            "robots_prior_sha256": robots_sha,
            "robots_restore": {"restored": True},
            "rolled_back": True,
        }
        rollback_call = mock.Mock(return_value=rollback)
        journal_identity = mock.Mock(
            side_effect=[
                (True, robots_sha),
                DEPLOY.DeployError("journal identity injected failure"),
                (True, robots_sha),
            ]
        )
        write_audit = mock.Mock(return_value=Path("unused-audit.json"))

        patches = (
            mock.patch.object(DEPLOY.argparse.ArgumentParser, "parse_args", return_value=args),
            mock.patch.object(DEPLOY, "load_artifact", return_value=(metadata, Path(metadata["artifact"]), b"x")),
            mock.patch.object(DEPLOY, "Client", return_value=object()),
            mock.patch.object(DEPLOY, "authenticate", return_value={"id": 1}),
            mock.patch.object(DEPLOY, "ensure_code_snippets"),
            mock.patch.object(DEPLOY, "render_bridge", return_value="bridge"),
            mock.patch.object(DEPLOY, "arm_live_mutation_recovery"),
            mock.patch.object(DEPLOY, "create_snippet", return_value=9),
            mock.patch.object(DEPLOY, "remove_bootstrap_snippet", return_value={}),
            mock.patch.object(DEPLOY, "preflight_with_recovery", return_value=preflight),
            mock.patch.object(DEPLOY, "verify_bridge_site_identity", return_value={}),
            mock.patch.object(DEPLOY, "remove_prefixed_snippets", return_value={}),
            mock.patch.object(DEPLOY, "verify_health", return_value={"status": "ok"}),
            mock.patch.object(DEPLOY, "verify_rendered_home", return_value={}),
            mock.patch.object(
                DEPLOY,
                "stage_artifact",
                return_value={"complete": True, "artifact_sha256": metadata["sha256"]},
            ),
            mock.patch.object(DEPLOY, "install_with_recovery", return_value=install),
            mock.patch.object(DEPLOY, "stabilize_deployment", return_value={"stabilized": True}),
            mock.patch.object(DEPLOY, "verify_managed_robots", return_value={}),
            mock.patch.object(DEPLOY, "rollback_with_recovery", rollback_call),
            mock.patch.object(DEPLOY, "verify_robots_journal_identity", journal_identity),
            mock.patch.object(DEPLOY, "verify_rollback_integrity", return_value={}),
            mock.patch.object(DEPLOY, "verify_prior_robots", return_value={}),
            mock.patch.object(DEPLOY, "finalize_deployment", return_value={"finalized": True}),
            mock.patch.object(DEPLOY, "delete_snippet_and_prove_404", return_value={}),
            mock.patch.object(DEPLOY, "write_audit", write_audit),
        )
        with mock.patch.dict(
            DEPLOY.os.environ,
            {
                "COMPLETE99_WORDPRESS_SYNC_SECRET": "",
                "WP_APP_PASSWORD": "local-test-only",
            },
        ):
            with contextlib.ExitStack() as stack:
                for patcher in patches:
                    stack.enter_context(patcher)
                with self.assertRaisesRegex(
                    DEPLOY.DeployError,
                    "journal identity injected failure",
                ):
                    DEPLOY.main()

        self.assertEqual(1, rollback_call.call_count)
        audit = write_audit.call_args.args[1]
        self.assertEqual(
            {
                "already_completed": True,
                "finalized": True,
                "second_rollback_refused": True,
            },
            audit["completed_rollback_recovery"],
        )


class OrphanObservationTests(unittest.TestCase):
    def status(self) -> dict[str, Any]:
        proof = valid_proof()
        manifest, manifest_sha256 = database_manifest()
        return {
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
            "process_lock_available": True,
            "current_version": proof["prior_run"]["version"],
            "current_database_version": proof["prior_run"]["database_version"],
            "current_active": True,
            "current_plugin_sha256": proof["prior_run"]["plugin_sha256"],
            "current_sync_configured": True,
            "current_robots_sha256": proof["prior_run"]["robots_sha256"],
            "current_deployment": proof["failed_run"]["deployment_id"],
            "database_fingerprint": "b" * 64,
            "projected_deployment_id": proof["prior_run"]["deployment_id"],
            "projected_database_fingerprint": "c" * 64,
            "database_manifest": manifest,
            "database_manifest_sha256": manifest_sha256,
            "database_storage": {"engine": "INNODB", "tables": 3},
        }

    def test_observation_is_non_secret_strict_and_mutation_free(self) -> None:
        proof = valid_proof()
        observed = DEPLOY.observe_orphaned_rollback(
            proof["failed_run"]["deployment_id"],
            self.status(),
            proof,
            "d" * 64,
        )
        self.assertEqual(
            "complete99-orphaned-rollback-observation/v1",
            observed["schema"],
        )
        self.assertEqual("b" * 64, observed["current_database_fingerprint"])
        self.assertEqual("c" * 64, observed["projected_database_fingerprint"])
        self.assertFalse(observed["historical_baseline_matches_projection"])
        serialized = json.dumps(observed, sort_keys=True)
        for forbidden in ("option_value", "post_content", "meta_value", "user_roles"):
            self.assertNotIn(forbidden, serialized)

    def test_observation_rejects_extra_manifest_fields_and_stale_identity(self) -> None:
        proof = valid_proof()
        extra = self.status()
        extra["database_manifest"] = dict(extra["database_manifest"])
        extra["database_manifest"]["unexpected"] = True
        with self.assertRaisesRegex(DEPLOY.DeployError, "manifest is invalid"):
            DEPLOY.observe_orphaned_rollback(
                proof["failed_run"]["deployment_id"],
                extra,
                proof,
                "d" * 64,
            )

        stale = self.status()
        stale["current_plugin_sha256"] = "f" * 64
        with self.assertRaisesRegex(DEPLOY.DeployError, "live state"):
            DEPLOY.observe_orphaned_rollback(
                proof["failed_run"]["deployment_id"],
                stale,
                proof,
                "d" * 64,
            )

        mistyped = self.status()
        mistyped["process_lock_available"] = "true"
        with self.assertRaisesRegex(DEPLOY.DeployError, "live state"):
            DEPLOY.observe_orphaned_rollback(
                proof["failed_run"]["deployment_id"],
                mistyped,
                proof,
                "d" * 64,
            )

        nontransactional = self.status()
        nontransactional["database_storage"] = {
            "engine": "MYISAM",
            "tables": 3,
        }
        with self.assertRaisesRegex(DEPLOY.DeployError, "not transactional"):
            DEPLOY.observe_orphaned_rollback(
                proof["failed_run"]["deployment_id"],
                nontransactional,
                proof,
                "d" * 64,
            )


class V2ReconciliationTests(unittest.TestCase):
    def envelope(self) -> dict[str, Any]:
        return json.loads(
            (
                ROOT
                / "docs"
                / "recovery-proofs"
                / "c99-prod-31171940371-1-v2.json"
            ).read_text(encoding="utf-8")
        )

    def initial_status(self, proof: dict[str, Any]) -> dict[str, Any]:
        failed = proof["failed_run"]
        prior = proof["prior_run"]
        reconciliation = proof["database_reconciliation"]
        return {
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
            "process_lock_available": True,
            # The extant production orphan predates durable candidate identity
            # fields in the lock. With its state journal absent, the bridge
            # truthfully returns empty values and the reviewed proof supplies
            # the immutable historical binding.
            "expected_sha256": "",
            "expected_version": "",
            "installed_plugin_sha256": "",
            "post_install_database_fingerprint": "",
            "current_version": prior["version"],
            "current_database_version": prior["database_version"],
            "current_active": prior["active"],
            "current_plugin_sha256": prior["plugin_sha256"],
            "current_sync_configured": prior["sync_configured"],
            "current_robots_sha256": prior["robots_sha256"],
            "current_deployment": failed["deployment_id"],
            "database_fingerprint": reconciliation[
                "observed_database_fingerprint"
            ],
            "projected_deployment_id": prior["deployment_id"],
            "projected_database_fingerprint": reconciliation[
                "expected_reconciled_database_fingerprint"
            ],
            "database_manifest": reconciliation["preserved_manifest"],
            "database_manifest_sha256": reconciliation[
                "preserved_manifest_sha256"
            ],
            "database_storage": reconciliation["transactional_storage"],
        }

    def test_v2_missing_candidate_identity_is_allowed_only_for_absent_state(
        self,
    ) -> None:
        envelope = self.envelope()
        proof = envelope["proof"]
        status = self.initial_status(proof)

        DEPLOY.validate_orphaned_rollback_live_state(
            proof["failed_run"]["deployment_id"],
            status,
            proof,
        )

        status["state_exists"] = True
        with self.assertRaisesRegex(DEPLOY.DeployError, "expected_sha256"):
            DEPLOY.validate_orphaned_rollback_live_state(
                proof["failed_run"]["deployment_id"],
                status,
                proof,
            )

    def test_v2_present_candidate_identity_must_match_reviewed_proof(self) -> None:
        envelope = self.envelope()
        proof = envelope["proof"]
        status = self.initial_status(proof)
        status["expected_sha256"] = "0" * 64

        with self.assertRaisesRegex(DEPLOY.DeployError, "expected_sha256"):
            DEPLOY.validate_orphaned_rollback_live_state(
                proof["failed_run"]["deployment_id"],
                status,
                proof,
            )

    def committed_status(
        self,
        proof: dict[str, Any],
        proof_sha256: str,
        receipt_sha256: str,
        *,
        marker_rows_affected: int,
    ) -> dict[str, Any]:
        failed = proof["failed_run"]
        prior = proof["prior_run"]
        reconciliation = proof["database_reconciliation"]
        marker_transition = (
            "corrected" if marker_rows_affected == 1 else "already-correct"
        )
        return {
            "phase": "committed",
            "state_exists": False,
            "lock_owned": True,
            "expected_sha256": failed["artifact_sha256"],
            "expected_version": failed["candidate_version"],
            "installed_plugin_sha256": failed[
                "candidate_plugin_sha256"
            ],
            "post_install_database_fingerprint": failed[
                "candidate_database_fingerprint"
            ],
            "committed_outcome": "rolled_back",
            "committed_expected_active": prior["active"],
            "committed_expected_absent": False,
            "committed_expected_version": prior["version"],
            "committed_expected_deployment": prior["deployment_id"],
            "committed_expected_plugin_sha256": prior["plugin_sha256"],
            "committed_expected_database_fingerprint": reconciliation[
                "expected_reconciled_database_fingerprint"
            ],
            "committed_expected_robots_exists": prior["robots_exists"],
            "committed_expected_robots_sha256": prior["robots_sha256"],
            "committed_expected_sync_configured": prior["sync_configured"],
            "orphaned_recovery_proof_sha256": proof_sha256,
            "orphaned_recovery_receipt_sha256": receipt_sha256,
            "orphaned_recovery_receipt_schema": "complete99-orphaned-rollback-receipt/v2",
            "orphaned_recovery_evidence_exists": False,
            "orphaned_recovery_evidence_sha256": "",
            "orphaned_reconciled_from": "rolling_back",
            "orphaned_observed_deployment": failed["deployment_id"],
            "orphaned_reconciliation_mode": reconciliation["mode"],
            "orphaned_prior_proof_sha256": reconciliation[
                "prior_proof_sha256"
            ],
            "orphaned_attestation_run_id": reconciliation[
                "attestation_run_id"
            ],
            "orphaned_attestation_sha256": reconciliation[
                "attestation_sha256"
            ],
            "orphaned_attestation_audit_sha256": reconciliation[
                "attestation_audit_sha256"
            ],
            "orphaned_attestation_source_commit": reconciliation[
                "attestation_source_commit"
            ],
            "orphaned_historical_baseline_database_fingerprint": reconciliation[
                "baseline_database_fingerprint"
            ],
            "orphaned_observed_database_fingerprint": reconciliation[
                "observed_database_fingerprint"
            ],
            "orphaned_preserved_manifest_sha256": reconciliation[
                "preserved_manifest_sha256"
            ],
            "orphaned_marker_rows_affected": marker_rows_affected,
            "orphaned_marker_transition": marker_transition,
            "current_deployment": prior["deployment_id"],
            "database_fingerprint": reconciliation[
                "expected_reconciled_database_fingerprint"
            ],
            "database_manifest_sha256": reconciliation[
                "preserved_manifest_sha256"
            ],
        }

    def response(
        self,
        proof: dict[str, Any],
        receipt_sha256: str,
        *,
        marker_rows_affected: int,
    ) -> dict[str, Any]:
        reconciliation = proof["database_reconciliation"]
        return {
            "reconciled": True,
            "phase": "committed",
            "lock_retained": True,
            "receipt_schema": "complete99-orphaned-rollback-receipt/v2",
            "receipt_sha256": receipt_sha256,
            "evidence_directory_exists": False,
            "evidence_directory_sha256": "",
            "marker_corrected": marker_rows_affected == 1,
            "marker_rows_affected": marker_rows_affected,
            "marker_transition": (
                "corrected"
                if marker_rows_affected == 1
                else "already-correct"
            ),
            "historical_baseline_database_fingerprint": reconciliation[
                "baseline_database_fingerprint"
            ],
            "observed_database_fingerprint": reconciliation[
                "observed_database_fingerprint"
            ],
            "reconciled_database_fingerprint": reconciliation[
                "expected_reconciled_database_fingerprint"
            ],
            "preserved_manifest_sha256": reconciliation[
                "preserved_manifest_sha256"
            ],
        }

    def test_v2_request_binds_reviewed_fields_and_validates_row_one_receipt(self) -> None:
        envelope = self.envelope()
        proof = envelope["proof"]
        receipt_sha256 = "f" * 64
        calls: list[tuple[str, dict[str, Any]]] = []

        def bridge_call(
            _client: object,
            action: str,
            _token: str,
            _deployment_id: str,
            **fields: Any,
        ) -> dict[str, Any]:
            calls.append((action, fields))
            if action == "reconcile-orphaned-rollback":
                return self.response(
                    proof,
                    receipt_sha256,
                    marker_rows_affected=1,
                )
            return self.committed_status(
                proof,
                envelope["proof_sha256"],
                receipt_sha256,
                marker_rows_affected=1,
            )

        with mock.patch.object(DEPLOY, "bridge_call", side_effect=bridge_call):
            result = DEPLOY.reconcile_orphaned_rollback(
                object(),
                "token",
                proof["failed_run"]["deployment_id"],
                self.initial_status(proof),
                proof,
                envelope["proof_sha256"],
            )

        self.assertEqual(
            ["reconcile-orphaned-rollback", "status"],
            [action for action, _fields in calls],
        )
        request = calls[0][1]
        reconciliation = proof["database_reconciliation"]
        self.assertEqual(proof, request["reviewed_proof"])
        self.assertEqual(
            reconciliation["observed_database_fingerprint"],
            request["expected_observed_database_fingerprint"],
        )
        self.assertEqual(
            reconciliation["expected_reconciled_database_fingerprint"],
            request["expected_reconciled_database_fingerprint"],
        )
        self.assertEqual(1, result["marker_rows_affected"])
        self.assertFalse(result["response_recovered"])

    def test_v2_lost_mutation_response_uses_one_read_only_status_and_never_retries(self) -> None:
        envelope = self.envelope()
        proof = envelope["proof"]
        receipt_sha256 = "e" * 64
        calls: list[str] = []

        def bridge_call(
            _client: object,
            action: str,
            _token: str,
            _deployment_id: str,
            **_fields: Any,
        ) -> dict[str, Any]:
            calls.append(action)
            if action == "reconcile-orphaned-rollback":
                raise DEPLOY.NetworkDeployError("response lost")
            return self.committed_status(
                proof,
                envelope["proof_sha256"],
                receipt_sha256,
                marker_rows_affected=1,
            )

        with mock.patch.object(DEPLOY, "bridge_call", side_effect=bridge_call):
            result = DEPLOY.reconcile_orphaned_rollback(
                object(),
                "token",
                proof["failed_run"]["deployment_id"],
                self.initial_status(proof),
                proof,
                envelope["proof_sha256"],
            )

        self.assertEqual(["reconcile-orphaned-rollback", "status"], calls)
        self.assertTrue(result["response_recovered"])
        self.assertEqual(receipt_sha256, result["receipt_sha256"])

    def test_v2_ambiguous_http_error_uses_read_only_receipt_recovery(self) -> None:
        envelope = self.envelope()
        proof = envelope["proof"]
        receipt_sha256 = "c" * 64
        calls: list[str] = []

        def bridge_call(
            _client: object,
            action: str,
            _token: str,
            _deployment_id: str,
            **_fields: Any,
        ) -> dict[str, Any]:
            calls.append(action)
            if action == "reconcile-orphaned-rollback":
                raise DEPLOY.HTTPDeployError(
                    "ambiguous gateway response",
                    status=502,
                    code="gateway_error",
                    data={},
                )
            return self.committed_status(
                proof,
                envelope["proof_sha256"],
                receipt_sha256,
                marker_rows_affected=0,
            )

        with mock.patch.object(DEPLOY, "bridge_call", side_effect=bridge_call):
            result = DEPLOY.reconcile_orphaned_rollback(
                object(),
                "token",
                proof["failed_run"]["deployment_id"],
                self.initial_status(proof),
                proof,
                envelope["proof_sha256"],
            )

        self.assertEqual(["reconcile-orphaned-rollback", "status"], calls)
        self.assertTrue(result["response_recovered"])
        self.assertEqual("already-correct", result["marker_transition"])

    def test_v2_invalid_success_response_uses_read_only_receipt_recovery(
        self,
    ) -> None:
        envelope = self.envelope()
        proof = envelope["proof"]
        receipt_sha256 = "a" * 64
        wrong_fingerprint = self.response(
            proof,
            receipt_sha256,
            marker_rows_affected=1,
        )
        wrong_fingerprint["observed_database_fingerprint"] = "0" * 64
        wrong_receipt = self.response(
            proof,
            "b" * 64,
            marker_rows_affected=1,
        )

        for label, invalid_response in (
            ("empty object", {}),
            ("wrong reviewed fingerprint", wrong_fingerprint),
            ("wrong durable receipt", wrong_receipt),
        ):
            calls: list[str] = []

            def bridge_call(
                _client: object,
                action: str,
                _token: str,
                _deployment_id: str,
                **_fields: Any,
            ) -> dict[str, Any]:
                calls.append(action)
                if action == "reconcile-orphaned-rollback":
                    return invalid_response
                return self.committed_status(
                    proof,
                    envelope["proof_sha256"],
                    receipt_sha256,
                    marker_rows_affected=1,
                )

            with self.subTest(label=label), mock.patch.object(
                DEPLOY,
                "bridge_call",
                side_effect=bridge_call,
            ):
                result = DEPLOY.reconcile_orphaned_rollback(
                    object(),
                    "token",
                    proof["failed_run"]["deployment_id"],
                    self.initial_status(proof),
                    proof,
                    envelope["proof_sha256"],
                )
            self.assertTrue(result["response_recovered"])
            self.assertEqual(["reconcile-orphaned-rollback", "status"], calls)

    def test_v2_crossed_marker_and_fingerprint_fails_before_mutation(self) -> None:
        envelope = self.envelope()
        proof = envelope["proof"]
        status = self.initial_status(proof)
        status["current_deployment"] = proof["prior_run"]["deployment_id"]
        bridge = mock.Mock()
        with mock.patch.object(DEPLOY, "bridge_call", bridge), self.assertRaisesRegex(
            DEPLOY.DeployError,
            "reviewed v2 attestation",
        ):
            DEPLOY.reconcile_orphaned_rollback(
                object(),
                "token",
                proof["failed_run"]["deployment_id"],
                status,
                proof,
                envelope["proof_sha256"],
            )
        bridge.assert_not_called()

    def test_v1_reconciliation_response_and_audit_shape_remain_unchanged(self) -> None:
        proof = valid_proof()
        proof_sha256 = proof_record(proof)["proof_sha256"]
        failed = proof["failed_run"]
        prior = proof["prior_run"]
        receipt_sha256 = "d" * 64
        status = {
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
            "process_lock_available": True,
            "current_version": prior["version"],
            "current_database_version": prior["database_version"],
            "current_active": prior["active"],
            "current_plugin_sha256": prior["plugin_sha256"],
            "current_sync_configured": prior["sync_configured"],
            "current_robots_sha256": prior["robots_sha256"],
            "current_deployment": failed["deployment_id"],
        }
        response = {
            "reconciled": True,
            "phase": "committed",
            "lock_retained": True,
            "marker_corrected": True,
            "receipt_sha256": receipt_sha256,
            "evidence_directory_exists": False,
            "evidence_directory_sha256": "",
        }
        committed = {
            "phase": "committed",
            "state_exists": False,
            "lock_owned": True,
            "committed_outcome": "rolled_back",
            "committed_expected_active": prior["active"],
            "committed_expected_absent": False,
            "committed_expected_version": prior["version"],
            "committed_expected_deployment": prior["deployment_id"],
            "committed_expected_plugin_sha256": prior["plugin_sha256"],
            "committed_expected_database_fingerprint": prior[
                "database_fingerprint"
            ],
            "committed_expected_robots_exists": prior["robots_exists"],
            "committed_expected_robots_sha256": prior["robots_sha256"],
            "committed_expected_sync_configured": prior["sync_configured"],
            "orphaned_recovery_proof_sha256": proof_sha256,
            "orphaned_recovery_receipt_sha256": receipt_sha256,
        }
        with mock.patch.object(
            DEPLOY,
            "bridge_call",
            return_value=response,
        ), mock.patch.object(
            DEPLOY,
            "poll_deployment_status",
            return_value=committed,
        ):
            result = DEPLOY.reconcile_orphaned_rollback(
                object(),
                "token",
                failed["deployment_id"],
                status,
                proof,
                proof_sha256,
            )
        self.assertEqual(
            {
                "evidence_directory_exists",
                "evidence_directory_sha256",
                "lock_retained",
                "marker_corrected",
                "phase",
                "proof_sha256",
                "receipt_sha256",
            },
            set(result),
        )


class RecoveryProofTests(unittest.TestCase):
    def _write_proof(
        self,
        root: Path,
        proof: dict[str, Any],
        *,
        name: str = "proof.json",
        digest_override: str = "",
    ) -> Path:
        proof_dir = root / "docs" / "recovery-proofs"
        proof_dir.mkdir(parents=True, exist_ok=True)
        envelope = proof_record(proof)
        if digest_override:
            envelope["proof_sha256"] = digest_override
        path = proof_dir / name
        path.write_text(json.dumps(envelope), encoding="utf-8")
        return path

    def _write_production_v2_fixture(
        self,
        root: Path,
        *,
        envelope: dict[str, Any] | None = None,
        observation_bytes: bytes | None = None,
        historical_bytes: bytes | None = None,
    ) -> Path:
        proof_dir = root / "docs" / "recovery-proofs"
        observation_dir = proof_dir / "observations"
        observation_dir.mkdir(parents=True)
        source_root = ROOT / "docs" / "recovery-proofs"
        if envelope is None:
            envelope = json.loads(
                (source_root / "c99-prod-31171940371-1-v2.json").read_text(
                    encoding="utf-8"
                )
            )
        if observation_bytes is None:
            observation_bytes = (
                source_root
                / "observations"
                / "c99-prod-31171940371-1-run-31185136097.json"
            ).read_bytes()
        if historical_bytes is None:
            historical_bytes = (
                source_root / "c99-prod-31171940371-1.json"
            ).read_bytes()
        proof_path = proof_dir / "v2.json"
        proof_path.write_text(json.dumps(envelope), encoding="utf-8")
        (
            observation_dir
            / "c99-prod-31171940371-1-run-31185136097.json"
        ).write_bytes(observation_bytes)
        (proof_dir / "c99-prod-31171940371-1.json").write_bytes(
            historical_bytes
        )
        return proof_path

    def test_proof_loader_enforces_path_digest_and_strict_scalar_types(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            valid_path = self._write_proof(root, valid_proof(), name="valid.json")
            with mock.patch.object(RECOVER, "ROOT", root):
                loaded = RECOVER.load_orphaned_rollback_proof(
                    DEPLOY,
                    str(valid_path),
                )
            self.assertIsNotNone(loaded)
            assert loaded is not None
            self.assertEqual("docs/recovery-proofs/valid.json", loaded["path"])

            outside = root / "outside.json"
            outside.write_text(json.dumps(proof_record(valid_proof())), encoding="utf-8")
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaises(
                DEPLOY.DeployError
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(outside))

            bad_digest = self._write_proof(
                root,
                valid_proof(),
                name="bad-digest.json",
                digest_override="0" * 64,
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaises(
                DEPLOY.DeployError
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(bad_digest))

            duplicate = root / "docs" / "recovery-proofs" / "duplicate.json"
            duplicate.write_text(
                '{"schema":"complete99-orphaned-rollback-proof/v1",'
                '"schema":"complete99-orphaned-rollback-proof/v1",'
                '"proof":{},"proof_sha256":"' + "0" * 64 + '"}',
                encoding="utf-8",
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaises(
                DEPLOY.DeployError
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(duplicate))

            extra = proof_record(valid_proof())
            extra["unexpected"] = True
            extra_path = root / "docs" / "recovery-proofs" / "extra.json"
            extra_path.write_text(json.dumps(extra), encoding="utf-8")
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaises(
                DEPLOY.DeployError
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(extra_path))

            mutations = (
                ("boolean run ID", ("failed_run", "run_id"), True),
                ("numeric commit", ("failed_run", "commit"), int("5" * 40)),
                (
                    "numeric audit digest",
                    ("prior_run", "audit_sha256"),
                    int("6" * 64),
                ),
            )
            for label, keys, value in mutations:
                with self.subTest(label=label):
                    malformed = copy.deepcopy(valid_proof())
                    malformed[keys[0]][keys[1]] = value
                    malformed_path = self._write_proof(
                        root,
                        malformed,
                        name=re.sub(r"[^a-z]+", "-", label) + ".json",
                    )
                    with mock.patch.object(
                        RECOVER, "ROOT", root
                    ), self.assertRaises(DEPLOY.DeployError):
                        RECOVER.load_orphaned_rollback_proof(
                            DEPLOY,
                            str(malformed_path),
                        )

            embedded_run = valid_proof()
            embedded_run["failed_run"]["deployment_id"] = (
                "c99-prod-failed-x1234x-1"
            )
            embedded_path = self._write_proof(
                root,
                embedded_run,
                name="embedded-run-id.json",
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "reviewed identities",
            ):
                RECOVER.load_orphaned_rollback_proof(
                    DEPLOY,
                    str(embedded_path),
                )

    def test_v2_proof_binds_the_exact_committed_observation_bytes(self) -> None:
        proof_source = (
            ROOT
            / "docs"
            / "recovery-proofs"
            / "c99-prod-31171940371-1-v2.json"
        )
        historical_source = (
            ROOT
            / "docs"
            / "recovery-proofs"
            / "c99-prod-31171940371-1.json"
        )
        observation_source = (
            ROOT
            / "docs"
            / "recovery-proofs"
            / "observations"
            / "c99-prod-31171940371-1-run-31185136097.json"
        )
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_dir = root / "docs" / "recovery-proofs"
            observation_dir = proof_dir / "observations"
            observation_dir.mkdir(parents=True)
            proof_path = proof_dir / proof_source.name
            historical_path = proof_dir / historical_source.name
            observation_path = observation_dir / observation_source.name
            proof_path.write_bytes(proof_source.read_bytes())
            historical_path.write_bytes(historical_source.read_bytes())
            observation_path.write_bytes(observation_source.read_bytes())

            with mock.patch.object(RECOVER, "ROOT", root):
                loaded = RECOVER.load_orphaned_rollback_proof(
                    DEPLOY,
                    str(proof_path),
                )
            self.assertIsNotNone(loaded)
            assert loaded is not None
            self.assertEqual(
                "complete99-orphaned-rollback-proof/v2",
                loaded["schema"],
            )
            self.assertEqual(
                "db93ccabda28b2848161d445e35b8010de18c89f3764b07b5434e76ffce6351f",
                hashlib.sha256(observation_path.read_bytes()).hexdigest(),
            )

            observation_path.write_bytes(observation_path.read_bytes() + b"\n")
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "attestation digest",
            ):
                RECOVER.load_orphaned_rollback_proof(
                    DEPLOY,
                    str(proof_path),
                )

    def test_v2_attestation_duplicate_keys_fail_even_with_rehashed_proof(self) -> None:
        envelope = json.loads(
            (
                ROOT
                / "docs"
                / "recovery-proofs"
                / "c99-prod-31171940371-1-v2.json"
            ).read_text(encoding="utf-8")
        )
        raw_observation = (
            ROOT
            / "docs"
            / "recovery-proofs"
            / "observations"
            / "c99-prod-31171940371-1-run-31185136097.json"
        ).read_text(encoding="utf-8")
        duplicated = raw_observation.replace(
            '  "result": "orphaned-rollback-observed",',
            '  "result": "orphaned-rollback-observed",\n  "result": "orphaned-rollback-observed",',
            1,
        ).encode("utf-8")
        attestation_sha256 = hashlib.sha256(duplicated).hexdigest()
        reconciliation = envelope["proof"]["database_reconciliation"]
        reconciliation["attestation_sha256"] = attestation_sha256
        reconciliation["attestation_audit_sha256"] = attestation_sha256
        envelope["proof_sha256"] = hashlib.sha256(
            json.dumps(
                envelope["proof"],
                ensure_ascii=False,
                separators=(",", ":"),
                sort_keys=True,
            ).encode("utf-8")
        ).hexdigest()

        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_dir = root / "docs" / "recovery-proofs"
            observation_dir = proof_dir / "observations"
            observation_dir.mkdir(parents=True)
            proof_path = proof_dir / "v2.json"
            historical_path = proof_dir / "c99-prod-31171940371-1.json"
            observation_path = (
                observation_dir
                / "c99-prod-31171940371-1-run-31185136097.json"
            )
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            historical_path.write_bytes(
                (
                    ROOT
                    / "docs"
                    / "recovery-proofs"
                    / "c99-prod-31171940371-1.json"
                ).read_bytes()
            )
            observation_path.write_bytes(duplicated)
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "attestation could not be read",
            ):
                RECOVER.load_orphaned_rollback_proof(
                    DEPLOY,
                    str(proof_path),
                )

    def test_v2_attestation_parent_symlink_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(root)
            observation_dir = root / "docs" / "recovery-proofs" / "observations"
            original_is_symlink = Path.is_symlink
            for symlink_component in (root / "docs", observation_dir):
                with self.subTest(component=symlink_component.name):
                    def is_symlink(candidate: Path) -> bool:
                        return (
                            candidate == symlink_component
                            or original_is_symlink(candidate)
                        )

                    with mock.patch.object(RECOVER, "ROOT", root), mock.patch.object(
                        Path,
                        "is_symlink",
                        autospec=True,
                        side_effect=is_symlink,
                    ), self.assertRaisesRegex(
                        DEPLOY.DeployError,
                        "attestation must be under",
                    ):
                        RECOVER.load_orphaned_rollback_proof(
                            DEPLOY,
                            str(proof_path),
                        )

    def test_v2_loader_rejects_weak_pre_mutation_evidence_contracts(self) -> None:
        source_root = ROOT / "docs" / "recovery-proofs"

        def production_envelope() -> dict[str, Any]:
            return json.loads(
                (source_root / "c99-prod-31171940371-1-v2.json").read_text(
                    encoding="utf-8"
                )
            )

        def rehash(envelope: dict[str, Any]) -> None:
            envelope["proof_sha256"] = hashlib.sha256(
                json.dumps(
                    envelope["proof"],
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()

        def bind_observation(
            envelope: dict[str, Any],
            observation: dict[str, Any],
        ) -> bytes:
            observation_bytes = (
                json.dumps(
                    observation,
                    ensure_ascii=False,
                    indent=2,
                    sort_keys=True,
                )
                + "\n"
            ).encode("utf-8")
            observation_sha256 = hashlib.sha256(observation_bytes).hexdigest()
            reconciliation = envelope["proof"]["database_reconciliation"]
            reconciliation["attestation_sha256"] = observation_sha256
            reconciliation["attestation_audit_sha256"] = observation_sha256
            rehash(envelope)
            return observation_bytes

        older_run = production_envelope()
        older_run["proof"]["database_reconciliation"]["attestation_run_id"] = (
            older_run["proof"]["failed_run"]["run_id"]
        )
        rehash(older_run)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(
                root,
                envelope=older_run,
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "reconciliation identity",
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(proof_path))

        reused_source_commit = production_envelope()
        reused_source_commit["proof"]["database_reconciliation"][
            "attestation_source_commit"
        ] = reused_source_commit["proof"]["failed_run"]["commit"]
        rehash(reused_source_commit)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(
                root,
                envelope=reused_source_commit,
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "reconciliation identity",
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(proof_path))

        bool_int_alias = production_envelope()
        observation = json.loads(
            (
                source_root
                / "observations"
                / "c99-prod-31171940371-1-run-31185136097.json"
            ).read_text(encoding="utf-8")
        )
        observation["orphaned_rollback_observation"]["state_exists"] = 0
        observation["initial_status"]["state_exists"] = 0
        observation_bytes = bind_observation(bool_int_alias, observation)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(
                root,
                envelope=bool_int_alias,
                observation_bytes=observation_bytes,
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "attestation",
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(proof_path))

        observed_baseline = production_envelope()
        observed_baseline_reconciliation = observed_baseline["proof"][
            "database_reconciliation"
        ]
        observed_baseline_reconciliation[
            "observed_database_fingerprint"
        ] = observed_baseline_reconciliation["baseline_database_fingerprint"]
        rehash(observed_baseline)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(
                root,
                envelope=observed_baseline,
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "reviewed state conflicts",
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(proof_path))

        for label, raw_path in (
            (
                "dot segment",
                "docs/recovery-proofs/observations/./"
                "c99-prod-31171940371-1-run-31185136097.json",
            ),
            (
                "internal parent segment",
                "docs/recovery-proofs/observations/../observations/"
                "c99-prod-31171940371-1-run-31185136097.json",
            ),
            (
                "case changed prefix",
                "Docs/recovery-proofs/observations/"
                "c99-prod-31171940371-1-run-31185136097.json",
            ),
            (
                "uppercase suffix",
                "docs/recovery-proofs/observations/"
                "c99-prod-31171940371-1-run-31185136097.JSON",
            ),
            (
                "double-dot filename",
                "docs/recovery-proofs/observations/"
                "c99-prod-31171940371-1-run-31185136097..json",
            ),
        ):
            with self.subTest(label=label), tempfile.TemporaryDirectory() as directory:
                noncanonical_path = production_envelope()
                noncanonical_path["proof"]["database_reconciliation"][
                    "attestation_path"
                ] = raw_path
                rehash(noncanonical_path)
                root = Path(directory)
                proof_path = self._write_production_v2_fixture(
                    root,
                    envelope=noncanonical_path,
                )
                with mock.patch.object(
                    RECOVER,
                    "ROOT",
                    root,
                ), self.assertRaisesRegex(
                    DEPLOY.DeployError,
                    "attestation must be under",
                ):
                    RECOVER.load_orphaned_rollback_proof(
                        DEPLOY,
                        str(proof_path),
                    )

        for label, started_at, finished_at in (
            ("invalid timestamp", "reviewed", "2026-08-07T13:58:29Z"),
            (
                "reversed timestamps",
                "2026-08-07T13:58:30Z",
                "2026-08-07T13:58:29Z",
            ),
        ):
            with self.subTest(label=label), tempfile.TemporaryDirectory() as directory:
                timestamp_envelope = production_envelope()
                observation = json.loads(
                    (
                        source_root
                        / "observations"
                        / "c99-prod-31171940371-1-run-31185136097.json"
                    ).read_text(encoding="utf-8")
                )
                observation["started_at"] = started_at
                observation["finished_at"] = finished_at
                observation_bytes = bind_observation(
                    timestamp_envelope,
                    observation,
                )
                root = Path(directory)
                proof_path = self._write_production_v2_fixture(
                    root,
                    envelope=timestamp_envelope,
                    observation_bytes=observation_bytes,
                )
                with mock.patch.object(
                    RECOVER,
                    "ROOT",
                    root,
                ), self.assertRaisesRegex(
                    DEPLOY.DeployError,
                    "cleanup contract",
                ):
                    RECOVER.load_orphaned_rollback_proof(
                        DEPLOY,
                        str(proof_path),
                    )

        float_storage = production_envelope()
        float_storage["proof"]["database_reconciliation"][
            "transactional_storage"
        ]["tables"] = 3.0
        rehash(float_storage)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(
                root,
                envelope=float_storage,
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "storage identity",
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(proof_path))

        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(
                root,
                historical_bytes=b"{}\n",
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "historical proof",
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(proof_path))

        cleanup_envelope = production_envelope()
        observation = json.loads(
            (
                source_root
                / "observations"
                / "c99-prod-31171940371-1-run-31185136097.json"
            ).read_text(encoding="utf-8")
        )
        observation["cleanup"]["route_404"] = False
        cleanup_bytes = (
            json.dumps(observation, ensure_ascii=False, indent=2, sort_keys=True)
            + "\n"
        ).encode("utf-8")
        cleanup_sha256 = hashlib.sha256(cleanup_bytes).hexdigest()
        cleanup_reconciliation = cleanup_envelope["proof"][
            "database_reconciliation"
        ]
        cleanup_reconciliation["attestation_sha256"] = cleanup_sha256
        cleanup_reconciliation["attestation_audit_sha256"] = cleanup_sha256
        rehash(cleanup_envelope)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_path = self._write_production_v2_fixture(
                root,
                envelope=cleanup_envelope,
                observation_bytes=cleanup_bytes,
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "cleanup contract",
            ):
                RECOVER.load_orphaned_rollback_proof(DEPLOY, str(proof_path))

    def _run_recovery_with_proof(
        self,
        *,
        discovered_owner: str,
        status: dict[str, Any] | None,
        proof_owner: str = "c99-prod-failed-1234-1",
    ) -> tuple[mock.Mock, mock.Mock, mock.Mock]:
        args = types.SimpleNamespace(
            allowed_deploy_hosts="",
            audit_dir=Path("unused-recovery-audit"),
            base_url="http://127.0.0.1",
            bootstrap_code_snippets=False,
            deployment_id="",
            discover=True,
            local_test=True,
            orphaned_rollback_proof="proof.json",
            probe_id="c99-recovery-probe-1234",
            user="local-admin",
        )
        proof = valid_proof()
        proof["failed_run"]["deployment_id"] = proof_owner
        loaded_proof = {
            "path": "docs/recovery-proofs/proof.json",
            "proof": proof,
            "proof_sha256": "d" * 64,
        }
        rollback = mock.Mock()
        reconcile = mock.Mock()
        finalize = mock.Mock(return_value={"finalized": True})
        fake_deployer = types.SimpleNamespace(
            ALLOWED_PRODUCTION_HOSTS=set(),
            Client=mock.Mock(return_value=object()),
            DeployError=DEPLOY.DeployError,
            authenticate=mock.Mock(return_value={"id": 1}),
            bridge_call=mock.Mock(return_value=status or {}),
            create_snippet=mock.Mock(return_value=5),
            delete_snippet_and_prove_404=mock.Mock(return_value={}),
            ensure_code_snippets=mock.Mock(),
            finalize_deployment=finalize,
            parse_allowed_deploy_hosts=mock.Mock(return_value=set()),
            poll_deployment_status=mock.Mock(return_value=status or {}),
            re=re,
            reconcile_orphaned_rollback=reconcile,
            remove_bootstrap_snippet=mock.Mock(return_value={}),
            render_bridge=mock.Mock(return_value="bridge"),
            validate_target_url=mock.Mock(
                return_value=urllib.parse.urlsplit("http://127.0.0.1")
            ),
            verify_bridge_site_identity=mock.Mock(return_value={}),
            write_audit=mock.Mock(return_value=Path("unused-recovery-audit.json")),
        )

        with mock.patch.object(
            RECOVER.argparse.ArgumentParser,
            "parse_args",
            return_value=args,
        ), mock.patch.object(RECOVER, "load_deployer", return_value=fake_deployer), mock.patch.object(
            RECOVER,
            "load_orphaned_rollback_proof",
            return_value=loaded_proof,
        ), mock.patch.object(
            RECOVER,
            "discover_lock_owner",
            return_value=(discovered_owner, {"result": "owner-discovered"}),
        ), mock.patch.object(
            RECOVER,
            "rollback_and_verify",
            rollback,
        ), mock.patch.dict(
            RECOVER.os.environ,
            {"WP_APP_PASSWORD": "local-test-only"},
        ):
            with self.assertRaises(DEPLOY.DeployError):
                RECOVER.main()
        return rollback, reconcile, finalize

    def test_supplied_proof_fails_closed_when_discovery_finds_no_owner(self) -> None:
        rollback, reconcile, finalize = self._run_recovery_with_proof(
            discovered_owner="",
            status=None,
        )
        rollback.assert_not_called()
        reconcile.assert_not_called()
        finalize.assert_not_called()

    def test_supplied_proof_fails_closed_for_wrong_owner(self) -> None:
        rollback, reconcile, finalize = self._run_recovery_with_proof(
            discovered_owner="c99-prod-other-1234",
            status={
                "lock_owned": True,
                "phase": "rolling_back",
                "recovery_ready": True,
                "state_exists": False,
            },
        )
        rollback.assert_not_called()
        reconcile.assert_not_called()
        finalize.assert_not_called()

    def test_supplied_proof_fails_closed_for_wrong_phase(self) -> None:
        rollback, reconcile, finalize = self._run_recovery_with_proof(
            discovered_owner="c99-prod-failed-1234-1",
            status={
                "lock_owned": True,
                "phase": "failed",
                "recovery_ready": True,
                "state_exists": True,
            },
        )
        rollback.assert_not_called()
        reconcile.assert_not_called()
        finalize.assert_not_called()


class InterruptedForwardRecoveryTests(unittest.TestCase):
    def test_real_1_22_pending_stabilization_v1_proof_is_bound(self) -> None:
        loaded = RECOVER.load_interrupted_forward_proof(
            DEPLOY,
            "docs/recovery-proofs/c99-prod-31598196288-1.json",
        )
        self.assertIsNotNone(loaded)
        assert loaded is not None
        package = RECOVER.validate_interrupted_forward_dist(
            DEPLOY,
            ROOT / "plugin-dist",
            loaded,
        )
        self.assertEqual("complete99-interrupted-forward-proof/v1", loaded["schema"])
        self.assertEqual("1.22.0", package["version"])
        self.assertEqual(
            "9482ec75a92818e870e263036e291df9def80ad810414fb5d661e2cdb66908eb",
            loaded["recovery_identity"]["database_fingerprint"],
        )

    def test_pending_stabilization_diagnostic_captures_candidate_checkpoint(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        status = interrupted_forward_status(loaded)
        status.update(
            {
                "candidate_activation_completed_at": 1_786_533_000,
                "candidate_activation_phase": "complete",
                "candidate_activation_required": True,
                "candidate_database_fingerprint": "e" * 64,
                "candidate_prior_active": True,
                "candidate_requested_active": True,
                "forward_ready": True,
                "forward_stabilization_candidate": True,
                "interrupted_forward_candidate": False,
                "phase": "installed_pending_stabilization",
                "recovery_ready": False,
                "temp_removed": True,
            }
        )
        observed = RECOVER.capture_interrupted_forward_mismatch_diagnostic(
            DEPLOY,
            status,
            loaded,
        )
        self.assertEqual(
            "installed_pending_stabilization",
            observed["safe_status"]["phase"],
        )
        self.assertTrue(
            observed["safe_status"]["forward_stabilization_candidate"]
        )
        self.assertEqual(
            "e" * 64,
            observed["safe_status"]["candidate_database_fingerprint"],
        )
        self.assertFalse(observed["recovery_authority"])

    def test_real_v1_proof_and_exact_dist_are_bound(self) -> None:
        loaded = RECOVER.load_interrupted_forward_proof(
            DEPLOY,
            "docs/recovery-proofs/c99-prod-31217684760-1.json",
        )
        self.assertIsNotNone(loaded)
        assert loaded is not None
        self.assertEqual(
            "complete99-interrupted-forward-proof/v1",
            loaded["schema"],
        )
        self.assertEqual(
            "c86ebc2ce56ce6d66c9046fa3b7285754c3c38c68bb05b8f1a91748cc038e311",
            loaded["proof_sha256"],
        )
        with tempfile.TemporaryDirectory() as directory:
            package = RECOVER.validate_interrupted_forward_dist(
                DEPLOY,
                copy_historical_1_18_dist(Path(directory)),
                loaded,
            )
        self.assertEqual("1.18.0", package["version"])
        self.assertEqual(
            "8216376a993505e18bf616362df1db6318d9382319d53d70e58390bcdb60becc",
            package["installed_sha256"],
        )

    def test_real_robots_checkpoint_adoption_v3_proof_is_exact(self) -> None:
        loaded = RECOVER.load_interrupted_forward_proof(
            DEPLOY,
            "docs/recovery-proofs/c99-prod-31217684760-1-v2.json",
        )
        self.assertIsNotNone(loaded)
        assert loaded is not None
        adoption = loaded["proof"]["forward_adoption"]
        receipt = loaded["reviewed_forward_observation"]
        self.assertEqual(
            "complete99-interrupted-forward-proof/v2", loaded["schema"]
        )
        self.assertEqual(
            "complete99-interrupted-forward-adoption/v3",
            adoption["schema"],
        )
        self.assertEqual(
            "bb55df5c5c3ff11780ce21fdfbbc75678547b5a9bc16ca48a86a933e19fdf32d",
            loaded["proof_sha256"],
        )
        self.assertEqual(31229946737, adoption["observation_run_id"])
        self.assertEqual(
            "e253c43e8822a8ddc6340206fae216690ed644a0fd524ca45dd56960293fb2a8",
            adoption["observation_audit_sha256"],
        )
        self.assertEqual(
            "55d9b71b3f71058e35d0929cbbd3cd68973088e87a75383dd6e90c6838edc33b",
            receipt["safe_status_sha256"],
        )
        self.assertEqual(
            RECOVER.INTERRUPTED_FORWARD_ROBOTS_CHECKPOINT_MISMATCHES,
            receipt["mismatches"],
        )
        self.assertTrue(receipt["diagnostic_only"])
        self.assertFalse(receipt["recovery_authority"])
        self.assertFalse(receipt["proof_consumed"])
        with tempfile.TemporaryDirectory() as directory:
            package = RECOVER.validate_interrupted_forward_dist(
                DEPLOY,
                copy_historical_1_18_dist(Path(directory)),
                loaded,
            )
        self.assertEqual("1.18.0", package["version"])
        self.assertEqual(
            "8216376a993505e18bf616362df1db6318d9382319d53d70e58390bcdb60becc",
            package["installed_sha256"],
        )

    def test_robots_checkpoint_adoption_v3_rejects_every_authority_drift(
        self,
    ) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            shutil.copytree(
                ROOT / "docs" / "recovery-proofs",
                root / "docs" / "recovery-proofs",
            )
            proof_path = (
                root
                / "docs"
                / "recovery-proofs"
                / "c99-prod-31217684760-1-v2.json"
            )
            original_proof = json.loads(proof_path.read_text(encoding="utf-8"))
            adoption = original_proof["proof"]["forward_adoption"]
            audit_path = root / adoption["observation_audit_path"]
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
                *,
                mutate_audit: Callable[[dict[str, Any]], None] | None = None,
                mutate_proof: Callable[[dict[str, Any]], None] | None = None,
                recompute_receipt: bool = False,
                rebind_audit: bool = True,
            ) -> None:
                envelope = copy.deepcopy(original_proof)
                audit = copy.deepcopy(original_audit)
                if mutate_audit is not None:
                    mutate_audit(audit)
                if recompute_receipt:
                    receipt = audit["interrupted_forward_observation"]
                    safe = receipt["safe_status"]
                    receipt["safe_status_sha256"] = (
                        RECOVER.canonical_proof_sha256(safe)
                    )
                    receipt["mismatches"] = (
                        RECOVER.interrupted_forward_status_mismatches(
                            safe,
                            {
                                "proof": {
                                    "failed_run": envelope["proof"]["failed_run"],
                                    "prior_run": envelope["proof"]["prior_run"],
                                },
                                "recovery_identity": recovery_identity,
                            },
                        )
                    )
                audit_path.write_text(json.dumps(audit), encoding="utf-8")
                if rebind_audit:
                    envelope["proof"]["forward_adoption"][
                        "observation_audit_sha256"
                    ] = hashlib.sha256(audit_path.read_bytes()).hexdigest()
                if mutate_proof is not None:
                    mutate_proof(envelope["proof"])
                envelope["proof_sha256"] = RECOVER.canonical_proof_sha256(
                    envelope["proof"]
                )
                proof_path.write_text(json.dumps(envelope), encoding="utf-8")
                with mock.patch.object(RECOVER, "ROOT", root):
                    RECOVER.load_interrupted_forward_proof(
                        DEPLOY,
                        str(proof_path),
                    )

            cases: tuple[
                tuple[
                    str,
                    Callable[[dict[str, Any]], None] | None,
                    Callable[[dict[str, Any]], None] | None,
                    bool,
                    bool,
                ],
                ...,
            ] = (
                (
                    "adoption v1 cannot bind diagnostic",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "schema", "complete99-interrupted-forward-adoption/v1"
                    ),
                    False,
                    True,
                ),
                (
                    "adoption v2 requires database drift",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "schema", "complete99-interrupted-forward-adoption/v2"
                    ),
                    False,
                    True,
                ),
                (
                    "database fingerprint",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "observed_database_fingerprint", "0" * 64
                    ),
                    False,
                    True,
                ),
                (
                    "database manifest",
                    None,
                    lambda proof: proof["forward_adoption"].__setitem__(
                        "observed_database_manifest_sha256", "0" * 64
                    ),
                    False,
                    True,
                ),
                (
                    "missing mismatch",
                    lambda audit: audit["interrupted_forward_observation"].__setitem__(
                        "mismatches", ["interrupted_forward_candidate"]
                    ),
                    None,
                    False,
                    True,
                ),
                (
                    "extra mismatch",
                    lambda audit: audit["interrupted_forward_observation"][
                        "mismatches"
                    ].append("state_exists"),
                    None,
                    False,
                    True,
                ),
                (
                    "reordered mismatches",
                    lambda audit: audit["interrupted_forward_observation"].__setitem__(
                        "mismatches",
                        list(
                            reversed(
                                audit["interrupted_forward_observation"][
                                    "mismatches"
                                ]
                            )
                        ),
                    ),
                    None,
                    False,
                    True,
                ),
                (
                    "candidate checkpoint already true",
                    lambda audit: audit["interrupted_forward_observation"][
                        "safe_status"
                    ].__setitem__("interrupted_forward_candidate", True),
                    None,
                    True,
                    True,
                ),
                (
                    "robots checkpoint already applied",
                    lambda audit: audit["interrupted_forward_observation"][
                        "safe_status"
                    ].__setitem__("robots_applied", True),
                    None,
                    True,
                    True,
                ),
                (
                    "robots checkpoint contains another digest",
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
                    ].__setitem__("current_active", False),
                    None,
                    True,
                    True,
                ),
                (
                    "receipt grants authority",
                    lambda audit: audit["interrupted_forward_observation"].__setitem__(
                        "recovery_authority", True
                    ),
                    None,
                    False,
                    True,
                ),
                (
                    "receipt consumes proof",
                    lambda audit: audit["interrupted_forward_observation"].__setitem__(
                        "proof_consumed", True
                    ),
                    None,
                    False,
                    True,
                ),
                (
                    "public robots",
                    lambda audit: audit["robots"].__setitem__("sha256", "0" * 64),
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
                    "probe",
                    lambda audit: audit["discovery"].__setitem__(
                        "probe_id", "c99-recovery-probe-31229946738-1"
                    ),
                    None,
                    False,
                    True,
                ),
                (
                    "commit",
                    lambda audit: audit.__setitem__("commit", "0" * 40),
                    None,
                    False,
                    True,
                ),
                (
                    "unbound audit bytes",
                    lambda audit: audit.__setitem__("result", "failed"),
                    None,
                    False,
                    False,
                ),
            )
            for label, audit_mutation, proof_mutation, recompute, rebind in cases:
                with self.subTest(authority_drift=label), self.assertRaises(
                    DEPLOY.DeployError
                ):
                    load_changed(
                        mutate_audit=audit_mutation,
                        mutate_proof=proof_mutation,
                        recompute_receipt=recompute,
                        rebind_audit=rebind,
                    )

    def test_v1_proof_rejects_duplicate_keys_and_path_indirection(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_root = root / "docs" / "recovery-proofs"
            proof_root.mkdir(parents=True)
            duplicate = proof_root / "duplicate.json"
            duplicate.write_text(
                '{"schema":"complete99-interrupted-forward-proof/v1",'
                '"schema":"complete99-interrupted-forward-proof/v1",'
                '"proof":{},"proof_sha256":"' + "0" * 64 + '"}',
                encoding="utf-8",
            )
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "could not be read",
            ):
                RECOVER.load_interrupted_forward_proof(DEPLOY, str(duplicate))

            outside = root / "outside.json"
            outside.write_text("{}", encoding="utf-8")
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "direct reviewed JSON",
            ):
                RECOVER.load_interrupted_forward_proof(DEPLOY, str(outside))

    def test_adoption_v2_loader_selects_only_database_mismatch_audit(self) -> None:
        loaded = interrupted_forward_loaded(version=2)
        failed = loaded["proof"]["failed_run"]
        base_proof = {
            "failed_run": failed,
            "prior_run": loaded["proof"]["prior_run"],
        }
        historical_identity = {
            "database_fingerprint": "0" * 64,
            "database_manifest_sha256": "1" * 64,
        }
        adoption = loaded["proof"]["forward_adoption"]
        adoption["schema"] = "complete99-interrupted-forward-adoption/v2"
        proof = {**base_proof, "forward_adoption": adoption}
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_root = root / "docs" / "recovery-proofs"
            proof_root.mkdir(parents=True)
            historical_path = proof_root / f"{failed['deployment_id']}.json"
            historical_path.write_text(
                json.dumps(
                    {
                        "proof": base_proof,
                        "proof_sha256": RECOVER.canonical_proof_sha256(
                            base_proof
                        ),
                        "schema": "complete99-interrupted-forward-proof/v1",
                    }
                ),
                encoding="utf-8",
            )
            v2_path = proof_root / f"{failed['deployment_id']}-v2.json"

            def write_v2(value: dict[str, Any]) -> None:
                v2_path.write_text(
                    json.dumps(
                        {
                            "proof": value,
                            "proof_sha256": RECOVER.canonical_proof_sha256(value),
                            "schema": "complete99-interrupted-forward-proof/v2",
                        }
                    ),
                    encoding="utf-8",
                )

            write_v2(proof)
            observation = {"schema": "sentinel-observation"}

            def bound_audit(
                _deployer: Any,
                raw_path: str,
                _expected_sha256: str,
                _label: str,
            ) -> dict[str, Any]:
                return (
                    observation
                    if raw_path == adoption["observation_audit_path"]
                    else {}
                )

            mismatch_validator = mock.Mock()
            legacy_validator = mock.Mock()

            def load() -> dict[str, Any]:
                with mock.patch.object(
                    RECOVER,
                    "ROOT",
                    root,
                ), mock.patch.object(
                    RECOVER,
                    "load_bound_recovery_audit",
                    side_effect=bound_audit,
                ), mock.patch.object(
                    RECOVER,
                    "validate_interrupted_forward_source_audits",
                    return_value=historical_identity,
                ), mock.patch.object(
                    RECOVER,
                    "validate_interrupted_forward_database_mismatch_observation_audit",
                    mismatch_validator,
                ), mock.patch.object(
                    RECOVER,
                    "validate_interrupted_forward_observation_audit",
                    legacy_validator,
                ):
                    return RECOVER.load_interrupted_forward_proof(
                        DEPLOY,
                        str(v2_path),
                    )

            result = load()
            self.assertEqual(
                "complete99-interrupted-forward-adoption/v2",
                result["proof"]["forward_adoption"]["schema"],
            )
            mismatch_validator.assert_called_once()
            legacy_validator.assert_not_called()

            for label, mutate in (
                (
                    "v1 cannot bind drift",
                    lambda value: value["forward_adoption"].__setitem__(
                        "schema", "complete99-interrupted-forward-adoption/v1"
                    ),
                ),
                (
                    "v2 requires fingerprint drift",
                    lambda value: value["forward_adoption"].__setitem__(
                        "observed_database_fingerprint",
                        historical_identity["database_fingerprint"],
                    ),
                ),
                (
                    "v2 requires manifest drift",
                    lambda value: value["forward_adoption"].__setitem__(
                        "observed_database_manifest_sha256",
                        historical_identity["database_manifest_sha256"],
                    ),
                ),
            ):
                with self.subTest(label=label):
                    changed = copy.deepcopy(proof)
                    mutate(changed)
                    write_v2(changed)
                    with self.assertRaisesRegex(
                        DEPLOY.DeployError,
                        "adoption identity",
                    ):
                        load()

    def test_interrupted_status_requires_every_forward_safety_signal(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        status = interrupted_forward_status(loaded)
        observed = RECOVER.validate_interrupted_forward_status(
            DEPLOY,
            status,
            loaded,
        )
        self.assertTrue(observed["interrupted_forward_candidate"])
        self.assertEqual(
            loaded["proof"]["failed_run"]["installed_plugin_sha256"],
            observed["current_plugin_sha256"],
        )

        mutations = {
            "wrong tree": ("current_plugin_sha256", "f" * 64),
            "wrong runtime": ("runtime_version", "1.17.0"),
            "failed invariants": ("migration_invariants_valid", False),
            "rollback artifact": ("no_rollback_artifacts", False),
            "wrong marker": ("current_deployment", "c99-prod-other-2000-1"),
            "wrong database": ("database_fingerprint", "f" * 64),
            "wrong robots": ("current_robots_sha256", "f" * 64),
            "not candidate": ("interrupted_forward_candidate", False),
        }
        for label, (field, value) in mutations.items():
            with self.subTest(label=label):
                malformed = copy.deepcopy(status)
                malformed[field] = value
                with self.assertRaisesRegex(
                    DEPLOY.DeployError,
                    "exact reviewed live state",
                ):
                    RECOVER.validate_interrupted_forward_status(
                        DEPLOY,
                        malformed,
                        loaded,
                    )

    def test_database_mismatch_observation_requires_coupled_drift_only(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        status = interrupted_forward_status(loaded)
        historical_manifest = copy.deepcopy(status["database_manifest"])
        manifest = copy.deepcopy(historical_manifest)
        manifest["posts_sha256"] = "f" * 64
        status["database_manifest"] = manifest
        status["database_manifest_sha256"] = hashlib.sha256(
            json.dumps(
                manifest,
                ensure_ascii=False,
                separators=(",", ":"),
                sort_keys=True,
            ).encode("utf-8")
        ).hexdigest()
        status["database_fingerprint"] = "f" * 64
        status["interrupted_forward_candidate"] = False
        receipt = RECOVER.validate_interrupted_forward_database_mismatch_status(
            DEPLOY,
            status,
            loaded,
        )
        self.assertEqual(
            "complete99-interrupted-forward-observation/v2",
            receipt["schema"],
        )
        self.assertFalse(receipt["proof_consumed"])
        self.assertEqual(
            RECOVER.INTERRUPTED_FORWARD_DATABASE_MISMATCHES,
            receipt["mismatches"],
        )
        self.assertEqual(
            RECOVER.canonical_proof_sha256(receipt["safe_status"]),
            receipt["safe_status_sha256"],
        )

        mutations = (
            ("deployment", "deployment_id", "c99-prod-other-2000-1"),
            ("phase", "phase", "installed"),
            ("state", "state_exists", False),
            ("lock", "lock_owned", False),
            ("lease", "recovery_ready", False),
            ("process lock", "process_lock_available", False),
            ("artifact", "expected_sha256", "0" * 64),
            ("expected version", "expected_version", "1.17.0"),
            ("recorded plugin", "installed_plugin_sha256", "0" * 64),
            ("target dir", "current_target_dir_exists", False),
            ("plugin main", "current_plugin_main_exists", False),
            ("plugin tree", "current_plugin_sha256", "0" * 64),
            ("active", "current_active", False),
            ("header version", "current_version", "1.17.0"),
            ("runtime loaded", "runtime_loaded", False),
            ("runtime version", "runtime_version", "1.17.0"),
            ("migration failed", "migration_failed", True),
            ("migration invariants", "migration_invariants_valid", False),
            ("rollback", "no_rollback_artifacts", False),
            ("database restored", "database_restored", True),
            ("journal", "baseline_database_journal_valid", False),
            ("baseline sync exists", "baseline_sync_secret_existed", False),
            ("baseline sync configured", "baseline_sync_configured", False),
            ("marker", "current_deployment", "c99-prod-other-2000-1"),
            ("database version", "current_database_version", "1.17.0"),
            ("baseline fingerprint", "baseline_database_fingerprint", "0" * 64),
            ("current sync", "current_sync_configured", False),
            ("fingerprint unavailable", "database_fingerprint_available", False),
            ("prior plugin absent", "had_plugin", False),
            ("prior target", "prior_target_dir_exists", False),
            ("prior main", "prior_plugin_main_exists", False),
            ("prior plugin", "prior_plugin_sha256", "0" * 64),
            ("prior version", "prior_version", "1.16.0"),
            ("prior active", "prior_active", False),
            ("prior deployment", "prior_deployment", "c99-prod-other-1900-1"),
            ("robots applied", "robots_applied", False),
            ("robots restored", "robots_restored", True),
            ("prior robots absent", "robots_prior_exists", False),
            ("prior robots", "robots_prior_sha256", "0" * 64),
            ("managed robots", "robots_managed_sha256", "0" * 64),
            ("current robots", "current_robots_sha256", "0" * 64),
            ("adopted", "adopted_forward_no_rollback", True),
            ("candidate true", "interrupted_forward_candidate", True),
            ("state proof", "interrupted_forward_proof_sha256", "0" * 64),
            (
                "state manifest proof",
                "interrupted_forward_database_manifest_sha256",
                "0" * 64,
            ),
            (
                "unsafe lineage",
                "post_install_database_fingerprint",
                "secret-like-value",
            ),
        )
        for label, field, replacement in mutations:
            with self.subTest(label=label):
                changed = copy.deepcopy(status)
                changed[field] = replacement
                with self.assertRaises(DEPLOY.DeployError):
                    RECOVER.validate_interrupted_forward_database_mismatch_status(
                        DEPLOY,
                        changed,
                        loaded,
                    )

        only_fingerprint = copy.deepcopy(status)
        only_fingerprint["database_manifest"] = historical_manifest
        only_fingerprint["database_manifest_sha256"] = loaded[
            "recovery_identity"
        ]["database_manifest_sha256"]
        with self.assertRaises(DEPLOY.DeployError):
            RECOVER.validate_interrupted_forward_database_mismatch_status(
                DEPLOY,
                only_fingerprint,
                loaded,
            )
        only_manifest = copy.deepcopy(status)
        only_manifest["database_fingerprint"] = loaded["recovery_identity"][
            "database_fingerprint"
        ]
        with self.assertRaises(DEPLOY.DeployError):
            RECOVER.validate_interrupted_forward_database_mismatch_status(
                DEPLOY,
                only_manifest,
                loaded,
            )
        invalid_storage = copy.deepcopy(status)
        invalid_storage["database_storage"] = {"engine": "MYISAM", "tables": 3}
        with self.assertRaises(DEPLOY.DeployError):
            RECOVER.validate_interrupted_forward_database_mismatch_status(
                DEPLOY,
                invalid_storage,
                loaded,
            )

    def test_mismatch_diagnostic_is_bounded_exact_and_never_authority(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        exact = interrupted_forward_status(loaded)
        status = copy.deepcopy(exact)
        status["database_fingerprint"] = "f" * 64
        status["interrupted_forward_candidate"] = False
        status["private_option_value"] = "must-never-be-captured"

        receipt = RECOVER.capture_interrupted_forward_mismatch_diagnostic(
            DEPLOY,
            status,
            loaded,
        )
        self.assertEqual(
            "complete99-interrupted-forward-observation/v3",
            receipt["schema"],
        )
        self.assertTrue(receipt["diagnostic_only"])
        self.assertFalse(receipt["proof_consumed"])
        self.assertFalse(receipt["recovery_authority"])
        self.assertEqual(
            ["database_fingerprint", "interrupted_forward_candidate"],
            receipt["mismatches"],
        )
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            proof_root = root / "docs" / "recovery-proofs"
            proof_root.mkdir(parents=True)
            receipt_path = proof_root / "c99-diagnostic.json"
            receipt_path.write_text(json.dumps(receipt), encoding="utf-8")
            with mock.patch.object(RECOVER, "ROOT", root), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "proof schema",
            ):
                RECOVER.load_interrupted_forward_proof(
                    DEPLOY,
                    str(receipt_path),
                )
        self.assertNotIn("private_option_value", receipt["safe_status"])
        self.assertEqual(
            RECOVER.canonical_proof_sha256(receipt["safe_status"]),
            receipt["safe_status_sha256"],
        )

        mutations = {
            "adopted_forward_no_rollback": True,
            "baseline_database_fingerprint": "0" * 64,
            "baseline_database_journal_valid": False,
            "baseline_sync_configured": False,
            "baseline_sync_secret_existed": False,
            "current_active": False,
            "current_database_version": "1.17.0",
            "current_deployment": "c99-prod-other-2000-1",
            "current_plugin_main_exists": False,
            "current_plugin_sha256": "0" * 64,
            "current_robots_sha256": "0" * 64,
            "current_sync_configured": False,
            "current_target_dir_exists": False,
            "current_version": "1.17.0",
            "database_fingerprint": "f" * 64,
            "database_fingerprint_available": False,
            "database_restored": True,
            "deployment_id": "c99-prod-other-2000-1",
            "expected_sha256": "0" * 64,
            "expected_version": "1.17.0",
            "had_plugin": False,
            "installed_plugin_sha256": "0" * 64,
            "interrupted_forward_candidate": False,
            "interrupted_forward_database_manifest_sha256": "0" * 64,
            "interrupted_forward_proof_sha256": "0" * 64,
            "lock_owned": False,
            "migration_failed": True,
            "migration_invariants_valid": False,
            "no_rollback_artifacts": False,
            "phase": "installed",
            "prior_active": False,
            "prior_deployment": "c99-prod-other-1900-1",
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
        for field, replacement in mutations.items():
            with self.subTest(reviewed_predicate=field):
                changed = copy.deepcopy(exact)
                changed[field] = replacement
                captured = RECOVER.capture_interrupted_forward_mismatch_diagnostic(
                    DEPLOY,
                    changed,
                    loaded,
                )
                self.assertIn(field, captured["mismatches"])
                self.assertEqual(
                    sorted(captured["mismatches"]),
                    captured["mismatches"],
                )

        manifest_changed = copy.deepcopy(exact)
        manifest_changed["database_manifest"] = copy.deepcopy(
            exact["database_manifest"]
        )
        manifest_changed["database_manifest"]["posts_sha256"] = "0" * 64
        manifest_changed["database_manifest_sha256"] = hashlib.sha256(
            json.dumps(
                manifest_changed["database_manifest"],
                ensure_ascii=False,
                separators=(",", ":"),
                sort_keys=True,
            ).encode("utf-8")
        ).hexdigest()
        captured = RECOVER.capture_interrupted_forward_mismatch_diagnostic(
            DEPLOY,
            manifest_changed,
            loaded,
        )
        self.assertEqual(["database_manifest_sha256"], captured["mismatches"])

        for phase in (
            "failed",
            "installed_pending_cleanup",
            "installed_pending_stabilization",
        ):
            with self.subTest(bounded_bridge_phase=phase):
                phase_changed = copy.deepcopy(exact)
                phase_changed["phase"] = phase
                if phase == "installed_pending_stabilization":
                    phase_changed.update(
                        {
                            "candidate_activation_completed_at": 1_786_533_000,
                            "candidate_activation_phase": "complete",
                            "candidate_activation_required": True,
                            "candidate_database_fingerprint": "e" * 64,
                            "candidate_prior_active": True,
                            "candidate_requested_active": True,
                            "forward_ready": True,
                            "forward_stabilization_candidate": True,
                            "temp_removed": True,
                        }
                    )
                captured = RECOVER.capture_interrupted_forward_mismatch_diagnostic(
                    DEPLOY,
                    phase_changed,
                    loaded,
                )
                self.assertEqual(["phase"], captured["mismatches"])

        with self.assertRaisesRegex(DEPLOY.DeployError, "no reviewed mismatch"):
            RECOVER.capture_interrupted_forward_mismatch_diagnostic(
                DEPLOY,
                exact,
                loaded,
            )

        paired_database_only = copy.deepcopy(manifest_changed)
        paired_database_only["database_fingerprint"] = "f" * 64
        paired_database_only["interrupted_forward_candidate"] = False
        with self.assertRaisesRegex(DEPLOY.DeployError, "observation v2"):
            RECOVER.capture_interrupted_forward_mismatch_diagnostic(
                DEPLOY,
                paired_database_only,
                loaded,
            )

    def test_mismatch_diagnostic_rejects_every_unbounded_safe_field(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        exact = interrupted_forward_status(loaded)
        exact["interrupted_forward_candidate"] = False
        unsafe_cases: list[tuple[str, Callable[[dict[str, Any]], None]]] = []
        for field in RECOVER.INTERRUPTED_FORWARD_SAFE_BOOLEAN_FIELDS:
            unsafe_cases.append(
                (field, lambda value, key=field: value.__setitem__(key, "false"))
            )
        for field in RECOVER.INTERRUPTED_FORWARD_SAFE_DIGEST_FIELDS:
            unsafe_cases.append(
                (field, lambda value, key=field: value.__setitem__(key, "secret"))
            )
        for field in RECOVER.INTERRUPTED_FORWARD_SAFE_DEPLOYMENT_FIELDS:
            unsafe_cases.append(
                (field, lambda value, key=field: value.__setitem__(key, "secret"))
            )
        for field in RECOVER.INTERRUPTED_FORWARD_SAFE_VERSION_FIELDS:
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
                (
                    "storage",
                    lambda value: value.__setitem__(
                        "database_storage", {"engine": "MYISAM", "tables": 3}
                    ),
                ),
            )
        )
        for label, mutate in unsafe_cases:
            with self.subTest(unsafe_field=label):
                changed = copy.deepcopy(exact)
                mutate(changed)
                if label == "manifest count":
                    changed["database_manifest_sha256"] = hashlib.sha256(
                        json.dumps(
                            changed["database_manifest"],
                            ensure_ascii=False,
                            separators=(",", ":"),
                            sort_keys=True,
                        ).encode("utf-8")
                    ).hexdigest()
                with self.assertRaises(DEPLOY.DeployError):
                    RECOVER.capture_interrupted_forward_mismatch_diagnostic(
                        DEPLOY,
                        changed,
                        loaded,
                    )

    def test_bridge_markers_bind_v2_proof_and_reviewed_database(self) -> None:
        loaded = interrupted_forward_loaded(version=2)
        fields = RECOVER.interrupted_forward_bridge_fields(loaded)
        failed = loaded["proof"]["failed_run"]
        adoption = loaded["proof"]["forward_adoption"]
        self.assertEqual(failed["artifact_sha256"], fields["expected_artifact_sha256"])
        self.assertEqual(
            failed["installed_plugin_sha256"],
            fields["expected_plugin_sha256"],
        )
        self.assertEqual(
            loaded["proof_sha256"],
            fields["interrupted_forward_proof_sha256"],
        )
        self.assertEqual(
            loaded["proof"]["forward_adoption"]["observed_database_storage"],
            fields["reviewed_database_storage"],
        )
        self.assertEqual(
            adoption["observed_database_fingerprint"],
            fields["reviewed_database_fingerprint"],
        )
        attestation_fields = RECOVER.interrupted_forward_bridge_fields(
            loaded,
            enable_finalized_attestation=True,
        )
        self.assertTrue(
            attestation_fields["interrupted_forward_finalized_attestation"]
        )
        self.assertEqual(
            failed["deployment_id"],
            attestation_fields[
                "interrupted_forward_target_deployment_id"
            ],
        )
        self.assertEqual(
            adoption["observed_database_manifest"],
            attestation_fields["reviewed_database_manifest"],
        )

    def test_finalized_attestation_client_rejects_any_schema_or_identity_drift(
        self,
    ) -> None:
        loaded = interrupted_forward_loaded(version=2)
        probe_id = "c99-recovery-probe-2200-1"
        exact = interrupted_finalized_attestation(loaded, probe_id)
        self.assertEqual(
            exact,
            RECOVER.validate_interrupted_forward_finalized_attestation(
                DEPLOY,
                exact,
                probe_id,
                loaded,
            ),
        )
        for key in exact:
            with self.subTest(missing=key):
                changed = copy.deepcopy(exact)
                del changed[key]
                with self.assertRaises(DEPLOY.DeployError):
                    RECOVER.validate_interrupted_forward_finalized_attestation(
                        DEPLOY,
                        changed,
                        probe_id,
                        loaded,
                    )
        extra = copy.deepcopy(exact)
        extra["unexpected"] = True
        with self.assertRaisesRegex(DEPLOY.DeployError, "schema"):
            RECOVER.validate_interrupted_forward_finalized_attestation(
                DEPLOY,
                extra,
                probe_id,
                loaded,
            )
        for field, replacement in (
            ("proof_sha256", "0" * 64),
            ("database_fingerprint", "0" * 64),
            ("plugin_sha256", "0" * 64),
            ("robots_sha256", "0" * 64),
            ("target_state_absent", False),
            ("runtime_loaded", False),
        ):
            with self.subTest(field=field):
                changed = copy.deepcopy(exact)
                changed[field] = replacement
                with self.assertRaises(DEPLOY.DeployError):
                    RECOVER.validate_interrupted_forward_finalized_attestation(
                        DEPLOY,
                        changed,
                        probe_id,
                        loaded,
                    )

    def test_fresh_finalized_attestation_releases_probe_on_read_failure(
        self,
    ) -> None:
        loaded = interrupted_forward_loaded(version=2)
        probe_id = "c99-recovery-probe-2201-1"
        invalid = interrupted_finalized_attestation(loaded, probe_id)
        invalid["database_fingerprint"] = "0" * 64
        order: list[str] = []
        fake = types.SimpleNamespace(
            DeployError=DEPLOY.DeployError,
            HTTPDeployError=DEPLOY.HTTPDeployError,
            bridge_call=mock.Mock(return_value=invalid),
            create_snippet=mock.Mock(return_value=41),
            delete_snippet_and_prove_404=mock.Mock(
                side_effect=lambda *_args: order.append("cleanup") or {}
            ),
            finalize_deployment=mock.Mock(
                side_effect=lambda *_args: order.append("finalize")
                or {"finalized": True}
            ),
            preflight_with_recovery=mock.Mock(
                return_value={"lock_reserved": True}
            ),
            re=re,
            remove_bootstrap_snippet=mock.Mock(return_value={}),
            render_bridge=mock.Mock(return_value="bridge"),
            verify_bridge_site_identity=mock.Mock(return_value={}),
        )
        with self.assertRaisesRegex(DEPLOY.DeployError, "exact reviewed release"):
            RECOVER.discover_interrupted_forward_owner_or_finalized(
                fake,
                object(),
                probe_id,
                True,
                "localhost",
                {"localhost"},
                loaded,
            )
        self.assertEqual(["finalize", "cleanup"], order)
        fake.finalize_deployment.assert_called_once()

    def test_stale_probe_release_is_reserved_state_free_and_waits_for_lease(
        self,
    ) -> None:
        loaded = interrupted_forward_loaded(version=2)
        probe_id = "c99-recovery-probe-2202-1"

        def status(*, ready: bool) -> dict[str, Any]:
            return {
                "adopted_forward_no_rollback": False,
                "deployment_id": probe_id,
                "interrupted_forward_candidate": False,
                "lock_owned": True,
                "no_rollback_artifacts": True,
                "phase": "reserved",
                "process_lock_available": True,
                "recovery_ready": ready,
                "state_exists": False,
            }

        fake = types.SimpleNamespace(
            DeployError=DEPLOY.DeployError,
            bridge_call=mock.Mock(return_value=status(ready=False)),
            create_snippet=mock.Mock(return_value=42),
            delete_snippet_and_prove_404=mock.Mock(return_value={}),
            finalize_deployment=mock.Mock(return_value={"finalized": True}),
            poll_deployment_status=mock.Mock(return_value=status(ready=True)),
            remove_bootstrap_snippet=mock.Mock(return_value={}),
            render_bridge=mock.Mock(return_value="bridge"),
            re=re,
            verify_bridge_site_identity=mock.Mock(return_value={}),
        )
        evidence = RECOVER.release_stale_interrupted_forward_probe(
            fake,
            object(),
            probe_id,
            True,
            "localhost",
            {"localhost"},
            loaded,
        )
        self.assertTrue(evidence["reservation_status"]["recovery_ready"])
        self.assertEqual(loaded["proof_sha256"], evidence["interrupted_forward_proof_sha256"])
        fake.poll_deployment_status.assert_called_once()
        fake.finalize_deployment.assert_called_once()
        rendered_fields = fake.render_bridge.call_args.kwargs
        self.assertFalse(
            rendered_fields.get(
                "interrupted_forward_finalized_attestation",
                False,
            )
        )

        for label, field, replacement in (
            ("non-reserved", "phase", "installed"),
            ("state exists", "state_exists", True),
            ("rollback artifact", "no_rollback_artifacts", False),
        ):
            with self.subTest(label=label):
                invalid_status = status(ready=True)
                invalid_status[field] = replacement
                invalid_fake = copy.copy(fake)
                invalid_fake.bridge_call = mock.Mock(return_value=invalid_status)
                invalid_fake.finalize_deployment = mock.Mock()
                with self.assertRaisesRegex(DEPLOY.DeployError, "read-only reservation"):
                    RECOVER.release_stale_interrupted_forward_probe(
                        invalid_fake,
                        object(),
                        probe_id,
                        True,
                        "localhost",
                        {"localhost"},
                        loaded,
                    )
                invalid_fake.finalize_deployment.assert_not_called()
        with self.assertRaisesRegex(DEPLOY.DeployError, "exact probe owner"):
            RECOVER.release_stale_interrupted_forward_probe(
                fake,
                object(),
                "c99-prod-not-a-probe-1",
                True,
                "localhost",
                {"localhost"},
                loaded,
            )

    def test_main_emits_already_recovered_for_exact_no_owner_attestation(
        self,
    ) -> None:
        loaded = interrupted_forward_loaded(version=2)
        failed = loaded["proof"]["failed_run"]
        prior = loaded["proof"]["prior_run"]
        probe_id = "c99-recovery-probe-2203-1"
        args = types.SimpleNamespace(
            allowed_deploy_hosts="",
            audit_dir=Path("unused-interrupted-audit"),
            base_url="http://127.0.0.1",
            bootstrap_code_snippets=False,
            deployment_id="",
            discover=True,
            dist=Path("plugin-dist"),
            interrupted_forward_observe_only=False,
            interrupted_forward_proof="proof.json",
            local_test=True,
            observe_orphaned_rollback=False,
            orphaned_rollback_proof="",
            probe_id=probe_id,
            recovery_only=True,
            user="local-admin",
        )
        write_audit = mock.Mock(
            return_value=Path("unused-interrupted-audit.json")
        )
        fake = types.SimpleNamespace(
            ALLOWED_PRODUCTION_HOSTS=set(),
            Client=mock.Mock(return_value=object()),
            DeployError=DEPLOY.DeployError,
            authenticate=mock.Mock(return_value={"id": 1}),
            ensure_code_snippets=mock.Mock(),
            parse_allowed_deploy_hosts=mock.Mock(return_value=set()),
            re=re,
            validate_target_url=mock.Mock(
                return_value=urllib.parse.urlsplit("http://127.0.0.1")
            ),
            write_audit=write_audit,
        )
        finalize = {
            "cache_purge": {"not_required": True},
            "finalized": True,
            "lock_released": True,
            "response_recovered": False,
            "state_removed": True,
        }
        finalized = {
            "bootstrap_cleanup": {},
            "bridge_site_identity": {},
            "cleanup": {},
            "health": {"deployment_id": failed["deployment_id"]},
            "interrupted_forward_finalized_attestation": (
                interrupted_finalized_attestation(loaded, probe_id)
            ),
            "probe_finalize": finalize,
            "rendered_home": {"deployment_id": failed["deployment_id"]},
            "robots": {"sha256": prior["robots_sha256"]},
        }
        discovery = {
            "probe_id": probe_id,
            "probe_lock_retained_for_attestation": True,
            "result": "no-owner",
        }
        with mock.patch.object(
            RECOVER.argparse.ArgumentParser,
            "parse_args",
            return_value=args,
        ), mock.patch.object(
            RECOVER,
            "load_deployer",
            return_value=fake,
        ), mock.patch.object(
            RECOVER,
            "load_orphaned_rollback_proof",
            return_value=None,
        ), mock.patch.object(
            RECOVER,
            "load_interrupted_forward_proof",
            return_value=loaded,
        ), mock.patch.object(
            RECOVER,
            "validate_interrupted_forward_dist",
            return_value={},
        ), mock.patch.object(
            RECOVER,
            "discover_interrupted_forward_owner_or_finalized",
            return_value=("", discovery, finalized),
        ), mock.patch.dict(
            RECOVER.os.environ,
            {"WP_APP_PASSWORD": "local-test-only"},
        ):
            self.assertEqual(0, RECOVER.main())
        audit = write_audit.call_args.args[1]
        self.assertEqual("already-recovered", audit["result"])
        self.assertEqual(
            "attest_interrupted_forward_finalized",
            audit["decision"],
        )
        self.assertEqual(probe_id, audit["deployment_id"])

    def test_adoption_accepts_only_exact_initial_or_idempotent_receipt(self) -> None:
        loaded = interrupted_forward_loaded(version=2)
        failed = loaded["proof"]["failed_run"]
        adoption = loaded["proof"]["forward_adoption"]
        for idempotent in (False, True):
            with self.subTest(idempotent=idempotent):
                response = {
                    "adopted_forward_no_rollback": True,
                    "cache_purge": {"deferred_to_finalize": True},
                    "database_manifest": adoption[
                        "observed_database_manifest"
                    ],
                    "database_manifest_sha256": adoption[
                        "observed_database_manifest_sha256"
                    ],
                    "database_storage": adoption["observed_database_storage"],
                    "database_version": failed["version"],
                    "deployment_id": failed["deployment_id"],
                    "idempotent": idempotent,
                    "installed_plugin_sha256": failed[
                        "installed_plugin_sha256"
                    ],
                    "interrupted_forward_proof_sha256": loaded["proof_sha256"],
                    "post_install_database_fingerprint": adoption[
                        "observed_database_fingerprint"
                    ],
                    "stabilized": True,
                    "stabilized_from_phase": "installing",
                    "version": failed["version"],
                }
                status = interrupted_forward_status(loaded, adopted=True)
                bridge_call = mock.Mock(side_effect=[response, status])
                fake_deployer = types.SimpleNamespace(
                    DeployError=DEPLOY.DeployError,
                    bridge_call=bridge_call,
                    re=re,
                )
                result = RECOVER.adopt_interrupted_forward(
                    fake_deployer,
                    object(),
                    "token",
                    failed["deployment_id"],
                    loaded,
                )
                self.assertEqual(idempotent, result["receipt"]["idempotent"])
                self.assertTrue(result["status"]["adopted_forward_no_rollback"])
                self.assertEqual(
                    ["stabilize", "status"],
                    [call.args[1] for call in bridge_call.call_args_list],
                )
                self.assertEqual(
                    loaded["proof_sha256"],
                    bridge_call.call_args_list[0].kwargs[
                        "interrupted_forward_proof_sha256"
                    ],
                )

    def test_finalize_resume_accepts_only_exact_adopted_terminal_identity(self) -> None:
        loaded = interrupted_forward_loaded(version=2)
        for phase in ("committing", "commit_failed", "committed", "cleanup_failed"):
            with self.subTest(phase=phase):
                status = interrupted_forward_status(
                    loaded,
                    adopted=True,
                    phase=phase,
                )
                receipt = RECOVER.validate_interrupted_forward_finalize_status(
                    DEPLOY,
                    status,
                    loaded,
                )
                self.assertEqual(phase, receipt["phase"])
                self.assertEqual(
                    "complete99-interrupted-forward-finalize-resume/v1",
                    receipt["schema"],
                )
                for field, replacement in (
                    ("interrupted_forward_proof_sha256", "0" * 64),
                    ("committed_expected_deployment", "c99-prod-other-1"),
                    ("current_plugin_sha256", "0" * 64),
                    ("database_manifest_sha256", "0" * 64),
                    ("current_robots_sha256", "0" * 64),
                ):
                    changed = copy.deepcopy(status)
                    changed[field] = replacement
                    with self.assertRaisesRegex(
                        DEPLOY.DeployError,
                        "exact adopted release|database manifest",
                    ):
                        RECOVER.validate_interrupted_forward_finalize_status(
                            DEPLOY,
                            changed,
                            loaded,
                        )

        for phase in ("committed", "cleanup_failed"):
            with self.subTest(lock_only_phase=phase):
                status = interrupted_forward_status(
                    loaded,
                    adopted=True,
                    phase=phase,
                    state_exists=False,
                )
                receipt = RECOVER.validate_interrupted_forward_finalize_status(
                    DEPLOY,
                    status,
                    loaded,
                )
                self.assertFalse(receipt["state_exists"])

        invalid_lock_only = interrupted_forward_status(
            loaded,
            adopted=True,
            phase="commit_failed",
            state_exists=False,
        )
        with self.assertRaisesRegex(
            DEPLOY.DeployError,
            "exact adopted release",
        ):
            RECOVER.validate_interrupted_forward_finalize_status(
                DEPLOY,
                invalid_lock_only,
                loaded,
            )

    def _run_interrupted_main(
        self,
        *,
        loaded: dict[str, Any],
        status: dict[str, Any],
        observe_only: bool,
        stale_probe_recovery: dict[str, Any] | None = None,
    ) -> tuple[Any, mock.Mock, mock.Mock, mock.Mock]:
        failed = loaded["proof"]["failed_run"]
        args = types.SimpleNamespace(
            allowed_deploy_hosts="",
            audit_dir=Path("unused-interrupted-audit"),
            base_url="http://127.0.0.1",
            bootstrap_code_snippets=False,
            deployment_id="",
            discover=True,
            dist=Path("plugin-dist"),
            interrupted_forward_observe_only=observe_only,
            interrupted_forward_proof="proof.json",
            local_test=True,
            observe_orphaned_rollback=False,
            orphaned_rollback_proof="",
            probe_id="c99-recovery-probe-2100-1",
            recovery_only=not observe_only,
            user="local-admin",
        )
        bridge_call = mock.Mock(return_value=status)
        finalize = mock.Mock(return_value={"finalized": True})
        write_audit = mock.Mock(return_value=Path("unused-interrupted-audit.json"))
        fake_deployer = types.SimpleNamespace(
            ALLOWED_PRODUCTION_HOSTS=set(),
            Client=mock.Mock(return_value=object()),
            DeployError=DEPLOY.DeployError,
            authenticate=mock.Mock(return_value={"id": 1}),
            bridge_call=bridge_call,
            create_snippet=mock.Mock(return_value=5),
            delete_snippet_and_prove_404=mock.Mock(
                return_value={
                    "removed_ids": [6],
                    "route_404": True,
                    "row_absence_verified": True,
                    "snippet_active": False,
                    "snippet_deleted": True,
                }
            ),
            ensure_code_snippets=mock.Mock(),
            finalize_deployment=finalize,
            parse_allowed_deploy_hosts=mock.Mock(return_value=set()),
            poll_deployment_status=mock.Mock(return_value=status),
            re=re,
            remove_bootstrap_snippet=mock.Mock(return_value={}),
            render_bridge=mock.Mock(return_value="bridge"),
            validate_target_url=mock.Mock(
                return_value=urllib.parse.urlsplit("http://127.0.0.1")
            ),
            verify_bridge_site_identity=mock.Mock(return_value={}),
            verify_health=mock.Mock(
                return_value={
                    "component": "complete99-platform",
                    "database_version": failed["version"],
                    "deployment_id": failed["deployment_id"],
                    "status": "ok",
                    "sync_configured": True,
                    "version": failed["version"],
                }
            ),
            verify_managed_robots=mock.Mock(
                return_value={
                    "sha256": loaded["proof"]["prior_run"]["robots_sha256"],
                    "status": 200,
                }
            ),
            verify_rendered_home=mock.Mock(
                return_value={
                    "body_sha256": "f" * 64,
                    "deployment_id": failed["deployment_id"],
                    "exact_path": "/",
                    "version": failed["version"],
                }
            ),
            write_audit=write_audit,
        )
        adopt = mock.Mock(
            return_value={
                "receipt": {"idempotent": status.get("phase") == "installed"},
                "status": {"adopted_forward_no_rollback": True},
            }
        )
        rollback = mock.Mock()
        owner_discovery = {
            "bootstrap_cleanup": {
                "exact_name": "c99-deploy-bootstrap",
                "known_id": 5,
                "known_id_matched": False,
                "removed_ids": [],
                "row_absence_verified": True,
            },
            "cleanup": {
                "removed_ids": [4],
                "route_404": True,
                "row_absence_verified": True,
                "snippet_active": False,
                "snippet_deleted": True,
            },
            "owner_deployment_id": failed["deployment_id"],
            "owner_phase": status["phase"],
            "probe_id": "c99-recovery-probe-2100-1",
            "result": "owner-discovered",
        }
        interrupted_discovery_result: Any = (
            failed["deployment_id"],
            owner_discovery,
            None,
        )
        if stale_probe_recovery is not None:
            interrupted_discovery_result = [
                (
                    "c99-recovery-probe-2099-1",
                    {
                        "owner_deployment_id": "c99-recovery-probe-2099-1",
                        "owner_phase": "reserved",
                        "probe_id": "c99-recovery-probe-2100-1",
                        "result": "owner-discovered",
                    },
                    None,
                ),
                (failed["deployment_id"], owner_discovery, None),
            ]
        with mock.patch.object(
            RECOVER.argparse.ArgumentParser,
            "parse_args",
            return_value=args,
        ), mock.patch.object(
            RECOVER,
            "load_deployer",
            return_value=fake_deployer,
        ), mock.patch.object(
            RECOVER,
            "load_orphaned_rollback_proof",
            return_value=None,
        ), mock.patch.object(
            RECOVER,
            "load_interrupted_forward_proof",
            return_value=loaded,
        ), mock.patch.object(
            RECOVER,
            "validate_interrupted_forward_dist",
            return_value={},
        ), mock.patch.object(
            RECOVER,
            "discover_lock_owner",
            return_value=(
                failed["deployment_id"],
                {
                    "bootstrap_cleanup": {
                        "exact_name": "c99-deploy-bootstrap",
                        "known_id": 5,
                        "known_id_matched": False,
                        "removed_ids": [],
                        "row_absence_verified": True,
                    },
                    "cleanup": {
                        "removed_ids": [4],
                        "route_404": True,
                        "row_absence_verified": True,
                        "snippet_active": False,
                        "snippet_deleted": True,
                    },
                    "owner_deployment_id": failed["deployment_id"],
                    "owner_phase": status["phase"],
                    "probe_id": "c99-recovery-probe-2100-1",
                    "result": "owner-discovered",
                },
            ),
        ), mock.patch.object(
            RECOVER,
            "discover_interrupted_forward_owner_or_finalized",
            **(
                {"side_effect": interrupted_discovery_result}
                if isinstance(interrupted_discovery_result, list)
                else {"return_value": interrupted_discovery_result}
            ),
        ), mock.patch.object(
            RECOVER,
            "release_stale_interrupted_forward_probe",
            return_value=stale_probe_recovery,
        ), mock.patch.object(
            RECOVER,
            "adopt_interrupted_forward",
            adopt,
        ), mock.patch.object(
            RECOVER,
            "rollback_and_verify",
            rollback,
        ), mock.patch.dict(
            RECOVER.os.environ,
            {
                "GITHUB_SHA": "d" * 40,
                "WP_APP_PASSWORD": "local-test-only",
            },
        ):
            result = RECOVER.main()
        self.assertEqual(0, result)
        return fake_deployer, adopt, rollback, write_audit

    def test_stale_probe_evidence_survives_normal_target_resume(self) -> None:
        loaded = interrupted_forward_loaded(version=2)
        stale_evidence = {"reservation_status": {"phase": "reserved"}}
        _, _, _, write_audit = self._run_interrupted_main(
            loaded=loaded,
            status=interrupted_forward_status(loaded, adopted=True),
            observe_only=False,
            stale_probe_recovery=stale_evidence,
        )
        audit = write_audit.call_args.args[1]
        self.assertEqual(stale_evidence, audit["stale_probe_recovery"])

    def test_observation_path_never_stabilizes_rolls_back_or_finalizes(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        fake, adopt, rollback, write_audit = self._run_interrupted_main(
            loaded=loaded,
            status=interrupted_forward_status(loaded),
            observe_only=True,
        )
        adopt.assert_not_called()
        rollback.assert_not_called()
        fake.finalize_deployment.assert_not_called()
        self.assertEqual(
            ["status"],
            [call.args[1] for call in fake.bridge_call.call_args_list],
        )
        self.assertEqual(
            loaded["proof"]["failed_run"]["deployment_id"],
            fake.bridge_call.call_args.kwargs["projected_deployment_id"],
        )
        audit = write_audit.call_args.args[1]
        self.assertEqual("interrupted_forward_observed", audit["result"])
        self.assertEqual("observe_interrupted_forward", audit["decision"])

    def test_database_mismatch_observation_succeeds_as_unconsumed_evidence(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        status = interrupted_forward_status(loaded)
        manifest = copy.deepcopy(status["database_manifest"])
        manifest["postmeta_sha256"] = "f" * 64
        status["database_manifest"] = manifest
        status["database_manifest_sha256"] = hashlib.sha256(
            json.dumps(
                manifest,
                ensure_ascii=False,
                separators=(",", ":"),
                sort_keys=True,
            ).encode("utf-8")
        ).hexdigest()
        status["database_fingerprint"] = "f" * 64
        status["interrupted_forward_candidate"] = False
        fake, adopt, rollback, write_audit = self._run_interrupted_main(
            loaded=loaded,
            status=status,
            observe_only=True,
        )
        adopt.assert_not_called()
        rollback.assert_not_called()
        fake.finalize_deployment.assert_not_called()
        fake.verify_health.assert_called_once()
        fake.verify_rendered_home.assert_called_once()
        fake.verify_managed_robots.assert_called_once()
        fake.delete_snippet_and_prove_404.assert_called_once()
        audit = write_audit.call_args.args[1]
        self.assertEqual(
            "interrupted_forward_database_mismatch_observed",
            audit["result"],
        )
        self.assertEqual(
            "observe_interrupted_forward_database_mismatch",
            audit["decision"],
        )
        self.assertFalse(audit["proof_consumed"])
        bootstrap = {
            "exact_name": "c99-deploy-bootstrap",
            "known_id": 5,
            "known_id_matched": False,
            "removed_ids": [],
            "row_absence_verified": True,
        }
        audit["bootstrap_cleanup"] = bootstrap
        audit["bridge_site_identity"] = {
            "home_host": "complete99.co.il",
            "rest_host": "complete99.co.il",
            "siteurl_host": "complete99.co.il",
        }
        audit["identity"] = {
            "id": 1,
            "roles": ["administrator"],
            "site_identity": {
                "home": "https://complete99.co.il",
                "url": "https://complete99.co.il",
            },
        }
        audit["local_test"] = False
        receipt = audit["interrupted_forward_observation"]
        adoption = {
            "observation_commit": "d" * 40,
            "observation_proof_sha256": loaded["proof_sha256"],
            "observation_run_id": 2100,
            "observed_database_fingerprint": receipt["database_fingerprint"],
            "observed_database_manifest": receipt["database_manifest"],
            "observed_database_manifest_sha256": receipt[
                "database_manifest_sha256"
            ],
            "observed_database_storage": receipt["database_storage"],
            "observed_deployment_id": loaded["proof"]["failed_run"][
                "deployment_id"
            ],
            "observed_plugin_sha256": loaded["proof"]["failed_run"][
                "installed_plugin_sha256"
            ],
            "observed_robots_sha256": loaded["proof"]["prior_run"][
                "robots_sha256"
            ],
            "observed_version": loaded["proof"]["failed_run"]["version"],
        }
        RECOVER.validate_interrupted_forward_database_mismatch_observation_audit(
            DEPLOY,
            audit,
            loaded["proof"]["failed_run"],
            loaded["proof"]["prior_run"],
            loaded["recovery_identity"],
            adoption,
        )

    def test_mismatch_diagnostic_observation_exits_before_any_mutation(self) -> None:
        loaded = interrupted_forward_loaded(version=1)
        status = interrupted_forward_status(loaded)
        status["database_fingerprint"] = "f" * 64
        status["interrupted_forward_candidate"] = False
        fake, adopt, rollback, write_audit = self._run_interrupted_main(
            loaded=loaded,
            status=status,
            observe_only=True,
        )
        adopt.assert_not_called()
        rollback.assert_not_called()
        fake.finalize_deployment.assert_not_called()
        fake.verify_health.assert_called_once()
        fake.verify_rendered_home.assert_called_once()
        fake.verify_managed_robots.assert_called_once()
        fake.delete_snippet_and_prove_404.assert_called_once()
        audit = write_audit.call_args.args[1]
        self.assertEqual(
            "interrupted_forward_mismatch_diagnostic_observed",
            audit["result"],
        )
        self.assertEqual(
            "observe_interrupted_forward_mismatch_diagnostic",
            audit["decision"],
        )
        self.assertFalse(audit["proof_consumed"])
        receipt = audit["interrupted_forward_observation"]
        self.assertTrue(receipt["diagnostic_only"])
        self.assertFalse(receipt["recovery_authority"])
        self.assertEqual(
            ["database_fingerprint", "interrupted_forward_candidate"],
            receipt["mismatches"],
        )
        audit["bootstrap_cleanup"] = {
            "exact_name": "c99-deploy-bootstrap",
            "known_id": 5,
            "known_id_matched": False,
            "removed_ids": [],
            "row_absence_verified": True,
        }
        audit["bridge_site_identity"] = {
            "home_host": "complete99.co.il",
            "rest_host": "complete99.co.il",
            "siteurl_host": "complete99.co.il",
        }
        audit["identity"] = {
            "id": 1,
            "roles": ["administrator"],
            "site_identity": {
                "home": "https://complete99.co.il",
                "url": "https://complete99.co.il",
            },
        }
        audit["local_test"] = False
        adoption = {
            "observation_commit": "d" * 40,
            "observation_proof_sha256": loaded["proof_sha256"],
            "observation_run_id": 2100,
            "observed_database_fingerprint": "f" * 64,
            "observed_database_manifest": status["database_manifest"],
            "observed_database_manifest_sha256": status[
                "database_manifest_sha256"
            ],
            "observed_database_storage": status["database_storage"],
            "observed_deployment_id": loaded["proof"]["failed_run"][
                "deployment_id"
            ],
            "observed_plugin_sha256": loaded["proof"]["failed_run"][
                "installed_plugin_sha256"
            ],
            "observed_robots_sha256": loaded["proof"]["prior_run"][
                "robots_sha256"
            ],
            "observed_version": loaded["proof"]["failed_run"]["version"],
        }
        with self.assertRaisesRegex(DEPLOY.DeployError, "audit schema"):
            RECOVER.validate_interrupted_forward_database_mismatch_observation_audit(
                DEPLOY,
                audit,
                loaded["proof"]["failed_run"],
                loaded["proof"]["prior_run"],
                loaded["recovery_identity"],
                adoption,
            )

    def test_robots_checkpoint_adoption_requires_exact_live_v3_receipt(self) -> None:
        loaded, status = interrupted_robots_checkpoint_loaded()
        self.assertEqual(
            loaded["reviewed_forward_observation"],
            RECOVER.validate_interrupted_forward_robots_checkpoint_status(
                DEPLOY,
                status,
                loaded,
            ),
        )
        fake, adopt, rollback, write_audit = self._run_interrupted_main(
            loaded=loaded,
            status=status,
            observe_only=False,
        )
        self.assertEqual(
            loaded["proof"]["failed_run"]["deployment_id"],
            fake.bridge_call.call_args.kwargs["projected_deployment_id"],
        )
        adopt.assert_called_once()
        rollback.assert_not_called()
        fake.verify_health.assert_called()
        fake.verify_rendered_home.assert_called()
        fake.verify_managed_robots.assert_called()
        audit = write_audit.call_args.args[1]
        self.assertEqual("recovered", audit["result"])
        self.assertEqual(
            loaded["reviewed_forward_observation"],
            audit["pre_adoption_observation"],
        )
        self.assertTrue(audit["adopted_forward_no_rollback"])

        for field, replacement in (
            ("current_active", False),
            ("database_fingerprint", "0" * 64),
            ("current_robots_sha256", "0" * 64),
            ("robots_applied", True),
            ("robots_managed_sha256", "0" * 64),
        ):
            with self.subTest(live_drift=field):
                changed = copy.deepcopy(status)
                changed[field] = replacement
                with self.assertRaises(DEPLOY.DeployError):
                    RECOVER.validate_interrupted_forward_robots_checkpoint_status(
                        DEPLOY,
                        changed,
                        loaded,
                    )

    def test_robots_checkpoint_adoption_v3_resumes_idempotently(self) -> None:
        loaded, _ = interrupted_robots_checkpoint_loaded()
        status = interrupted_forward_status(loaded, adopted=True)
        fake, adopt, rollback, write_audit = self._run_interrupted_main(
            loaded=loaded,
            status=status,
            observe_only=False,
        )
        adopt.assert_called_once()
        rollback.assert_not_called()
        fake.finalize_deployment.assert_called_once()
        audit = write_audit.call_args.args[1]
        self.assertEqual("recovered", audit["result"])
        self.assertNotIn("pre_adoption_observation", audit)
        self.assertTrue(
            audit["interrupted_forward_adoption"]["receipt"]["idempotent"]
        )

    def test_recovery_resumes_durable_adoption_through_idempotent_receipt(self) -> None:
        loaded = interrupted_forward_loaded(version=2)
        fake, adopt, rollback, write_audit = self._run_interrupted_main(
            loaded=loaded,
            status=interrupted_forward_status(loaded, adopted=True),
            observe_only=False,
        )
        adopt.assert_called_once()
        rollback.assert_not_called()
        fake.finalize_deployment.assert_called_once()
        audit = write_audit.call_args.args[1]
        self.assertEqual("recovered", audit["result"])
        self.assertEqual("adopt_interrupted_forward", audit["decision"])
        self.assertTrue(audit["adopted_forward_no_rollback"])
        self.assertTrue(
            audit["interrupted_forward_adoption"]["receipt"]["idempotent"]
        )
        self.assertNotIn("pre_adoption_observation", audit)

    def test_recovery_resumes_each_stateful_adopted_finalize_phase(self) -> None:
        loaded = interrupted_forward_loaded(version=2)
        for phase in ("committing", "commit_failed", "committed", "cleanup_failed"):
            with self.subTest(phase=phase):
                fake, adopt, rollback, write_audit = self._run_interrupted_main(
                    loaded=loaded,
                    status=interrupted_forward_status(
                        loaded,
                        adopted=True,
                        phase=phase,
                    ),
                    observe_only=False,
                )
                adopt.assert_not_called()
                rollback.assert_not_called()
                fake.finalize_deployment.assert_called_once()
                audit = write_audit.call_args.args[1]
                self.assertEqual(
                    phase,
                    audit["interrupted_forward_finalize_resume"]["phase"],
                )
                self.assertNotIn("interrupted_forward_adoption", audit)

        for phase in ("committed", "cleanup_failed"):
            with self.subTest(lock_only_phase=phase):
                fake, adopt, rollback, write_audit = self._run_interrupted_main(
                    loaded=loaded,
                    status=interrupted_forward_status(
                        loaded,
                        adopted=True,
                        phase=phase,
                        state_exists=False,
                    ),
                    observe_only=False,
                )
                adopt.assert_not_called()
                rollback.assert_not_called()
                fake.finalize_deployment.assert_called_once()
                audit = write_audit.call_args.args[1]
                self.assertFalse(
                    audit["interrupted_forward_finalize_resume"]["state_exists"]
                )


if __name__ == "__main__":
    unittest.main()
