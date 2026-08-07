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
            "deployment_id": "c99-prod-failed-1234",
            "run_id": 1234,
        },
        "prior_run": {
            "active": True,
            "audit_sha256": "6" * 64,
            "commit": "7" * 40,
            "database_fingerprint": "8" * 64,
            "database_version": "1.16.0",
            "deployment_id": "c99-prod-prior-1200",
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

    def _run_recovery_with_proof(
        self,
        *,
        discovered_owner: str,
        status: dict[str, Any] | None,
        proof_owner: str = "c99-prod-failed-1234",
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
            discovered_owner="c99-prod-failed-1234",
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
