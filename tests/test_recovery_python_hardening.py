from __future__ import annotations

import copy
import contextlib
import hashlib
import http.client
import importlib.util
import json
import re
import sys
import tempfile
import types
import unittest
import urllib.error
import urllib.parse
from pathlib import Path
from typing import Any
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]


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


if __name__ == "__main__":
    unittest.main()
