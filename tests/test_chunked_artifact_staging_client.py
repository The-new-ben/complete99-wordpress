from __future__ import annotations

import base64
import contextlib
import hashlib
import importlib.util
import sys
import types
import unittest
from pathlib import Path
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location(
    "complete99_deploy_chunk_client",
    ROOT / "scripts" / "deploy-wordpress.py",
)
assert SPEC is not None and SPEC.loader is not None
DEPLOY = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = DEPLOY
SPEC.loader.exec_module(DEPLOY)


def exact_receipt(
    deployment_id: str,
    offset: int,
    size: int,
    total_size: int,
    artifact_sha256: str,
) -> dict[str, object]:
    next_offset = offset + size
    complete = next_offset == total_size
    return {
        "deployment_id": deployment_id,
        "accepted_offset": offset,
        "next_offset": next_offset,
        "total_bytes": next_offset,
        "complete": complete,
        "artifact_sha256": artifact_sha256 if complete else "",
    }


class ChunkedArtifactStagingClientTests(unittest.TestCase):
    def test_main_finalizes_owned_reserved_stage_after_preclaim_run_failure(self) -> None:
        args = types.SimpleNamespace(
            allowed_deploy_hosts="",
            audit_dir=Path("unused-audit"),
            base_url="http://127.0.0.1",
            bootstrap_code_snippets=False,
            deployment_id="c99-prod-reserved-stage-1234",
            dist=Path("unused-dist"),
            dry_run=False,
            fault_injection="",
            local_test=True,
            mutation_marker=None,
            rollback_exercise=False,
            user="local-admin",
        )
        raw = b"reviewed-artifact"
        metadata = {
            "artifact": "complete99-platform.zip",
            "installed_sha256": "b" * 64,
            "sha256": hashlib.sha256(raw).hexdigest(),
            "slug": DEPLOY.SLUG,
            "source_sha256": "c" * 64,
            "version": "1.17.0",
        }
        preflight = {
            "allowed_slug": DEPLOY.SLUG,
            "auto_update_enabled": False,
            "current_active": False,
            "current_deployment": "",
            "current_version": "",
            "had_plugin": False,
            "lock_reserved": True,
            "ready": True,
        }
        reserved = {
            "state_exists": False,
            "lock_owned": True,
            "phase": "reserved",
            "recovery_ready": False,
        }
        run_error = DEPLOY.HTTPDeployError(
            "run rejected before lease claim",
            status=507,
            code="c99_deploy_disk_space",
            data={"status": 507},
        )
        fake_client = object()
        finalize = mock.Mock(return_value={"finalized": True})
        rollback = mock.Mock()
        bridge = mock.Mock(return_value=reserved)
        write_audit = mock.Mock(return_value=Path("unused-audit.json"))
        patches = (
            mock.patch.object(DEPLOY.argparse.ArgumentParser, "parse_args", return_value=args),
            mock.patch.object(DEPLOY, "load_artifact", return_value=(metadata, Path(metadata["artifact"]), raw)),
            mock.patch.object(DEPLOY, "Client", return_value=fake_client),
            mock.patch.object(DEPLOY, "authenticate", return_value={"id": 1}),
            mock.patch.object(DEPLOY, "ensure_code_snippets"),
            mock.patch.object(DEPLOY, "render_bridge", return_value="bridge"),
            mock.patch.object(DEPLOY, "arm_live_mutation_recovery"),
            mock.patch.object(DEPLOY, "create_snippet", return_value=9),
            mock.patch.object(DEPLOY, "remove_bootstrap_snippet", return_value={}),
            mock.patch.object(DEPLOY, "preflight_with_recovery", return_value=preflight),
            mock.patch.object(DEPLOY, "verify_bridge_site_identity", return_value={}),
            mock.patch.object(DEPLOY, "remove_prefixed_snippets", return_value={}),
            mock.patch.object(
                DEPLOY,
                "stage_artifact",
                return_value={"complete": True, "artifact_sha256": metadata["sha256"]},
            ),
            mock.patch.object(DEPLOY, "install_with_recovery", side_effect=run_error),
            mock.patch.object(DEPLOY, "bridge_call", bridge),
            mock.patch.object(DEPLOY, "finalize_deployment", finalize),
            mock.patch.object(DEPLOY, "rollback_with_recovery", rollback),
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
                with self.assertRaises(DEPLOY.HTTPDeployError) as raised:
                    DEPLOY.main()

        self.assertIs(run_error, raised.exception)
        bridge.assert_called_once()
        self.assertEqual("status", bridge.call_args.args[1])
        finalize.assert_called_once()
        rollback.assert_not_called()
        audit = write_audit.call_args.args[1]
        self.assertEqual(
            {"mutation_detected": False, "lock_released": True},
            audit["unstarted_recovery"],
        )

    def test_owned_reserved_stage_failure_is_finalized_as_unstarted(self) -> None:
        reserved = {
            "state_exists": False,
            "lock_owned": True,
            "phase": "reserved",
            "recovery_ready": False,
        }
        self.assertTrue(DEPLOY.can_finalize_unstarted_status(reserved))
        self.assertTrue(
            DEPLOY.can_finalize_unstarted_status(
                {"state_exists": False, "lock_owned": False, "phase": ""}
            )
        )
        self.assertTrue(
            DEPLOY.can_finalize_unstarted_status(
                {
                    "state_exists": False,
                    "lock_owned": True,
                    "phase": "locked",
                    "recovery_ready": True,
                }
            )
        )
        for status in (
            {},
            {"state_exists": True, "lock_owned": True, "phase": "reserved"},
            {"state_exists": False, "lock_owned": 1, "phase": "reserved"},
            {"state_exists": False, "lock_owned": True, "phase": "prepared"},
            {
                "state_exists": False,
                "lock_owned": True,
                "phase": "locked",
                "recovery_ready": False,
            },
        ):
            with self.subTest(status=status):
                self.assertFalse(DEPLOY.can_finalize_unstarted_status(status))

        source = (ROOT / "scripts" / "deploy-wordpress.py").read_text(encoding="utf-8")
        self.assertIn("if can_finalize_unstarted_status(recovery_status):", source)

    def test_artifact_is_staged_in_exact_sequential_bounded_chunks(self) -> None:
        raw = b"a" * (DEPLOY.ARTIFACT_STAGE_CHUNK_BYTES * 2 + 17)
        digest = hashlib.sha256(raw).hexdigest()
        deployment_id = "c99-prod-stage-1234"
        observed: list[dict[str, object]] = []

        def bridge(
            client: object,
            action: str,
            token: str,
            actual_deployment_id: str,
            **fields: object,
        ) -> dict[str, object]:
            self.assertIs(client, fake_client)
            self.assertEqual("stage", action)
            self.assertEqual("temporary-token", token)
            self.assertEqual(deployment_id, actual_deployment_id)
            chunk = base64.b64decode(str(fields["chunk_base64"]), validate=True)
            self.assertEqual(hashlib.sha256(chunk).hexdigest(), fields["chunk_sha256"])
            self.assertEqual(digest, fields["expected_artifact_sha256"])
            self.assertEqual(len(raw), fields["expected_artifact_size"])
            self.assertEqual(
                int(fields["offset"]) + len(chunk) == len(raw),
                fields["final"],
            )
            observed.append(dict(fields))
            return exact_receipt(
                deployment_id,
                int(fields["offset"]),
                len(chunk),
                len(raw),
                digest,
            )

        fake_client = object()
        with mock.patch.object(DEPLOY, "bridge_call", side_effect=bridge):
            receipt = DEPLOY.stage_artifact(
                fake_client,
                "temporary-token",
                deployment_id,
                raw,
                digest,
                len(raw),
            )

        self.assertEqual(3, receipt["chunk_count"])
        self.assertEqual(len(raw), receipt["final_next_offset"])
        self.assertEqual(digest, receipt["artifact_sha256"])
        self.assertEqual(
            [0, DEPLOY.ARTIFACT_STAGE_CHUNK_BYTES, DEPLOY.ARTIFACT_STAGE_CHUNK_BYTES * 2],
            [entry["offset"] for entry in observed],
        )
        self.assertEqual([False, False, True], [entry["final"] for entry in observed])
        self.assertTrue(
            all(
                len(base64.b64decode(str(entry["chunk_base64"]), validate=True))
                <= DEPLOY.ARTIFACT_STAGE_CHUNK_BYTES
                for entry in observed
            )
        )

    def test_ambiguous_transport_loss_retries_only_the_identical_chunk(self) -> None:
        raw = b"immutable-artifact"
        digest = hashlib.sha256(raw).hexdigest()
        deployment_id = "c99-prod-stage-ambiguous"
        calls: list[dict[str, object]] = []

        def bridge(
            client: object,
            action: str,
            token: str,
            actual_deployment_id: str,
            **fields: object,
        ) -> dict[str, object]:
            calls.append(dict(fields))
            if len(calls) == 1:
                raise DEPLOY.NetworkDeployError("ambiguous response loss")
            return exact_receipt(
                deployment_id,
                0,
                len(raw),
                len(raw),
                digest,
            )

        with mock.patch.object(DEPLOY, "bridge_call", side_effect=bridge), mock.patch.object(
            DEPLOY.time,
            "sleep",
        ) as sleep:
            receipt = DEPLOY.stage_artifact(
                object(),
                "temporary-token",
                deployment_id,
                raw,
                digest,
                len(raw),
            )

        self.assertTrue(receipt["complete"])
        self.assertEqual(2, len(calls))
        self.assertEqual(calls[0], calls[1])
        sleep.assert_called_once_with(DEPLOY.PUBLIC_READ_RETRY_DELAYS_SECONDS[0])

    def test_trusted_http_failure_is_never_retried(self) -> None:
        raw = b"immutable-artifact"
        digest = hashlib.sha256(raw).hexdigest()
        for status, code in (
            (400, "c99_deploy_stage_chunk_sha256"),
            (409, "c99_deploy_stage_offset"),
            (422, "c99_deploy_stage_final"),
            (500, "c99_deploy_stage_write"),
        ):
            with self.subTest(status=status, code=code):
                error = DEPLOY.HTTPDeployError(
                    "trusted staging failure",
                    status=status,
                    code=code,
                )
                bridge = mock.Mock(side_effect=error)
                with mock.patch.object(DEPLOY, "bridge_call", bridge), self.assertRaises(
                    DEPLOY.HTTPDeployError
                ):
                    DEPLOY.stage_artifact(
                        object(),
                        "temporary-token",
                        "c99-prod-stage-http",
                        raw,
                        digest,
                        len(raw),
                    )
                self.assertEqual(1, bridge.call_count)

    def test_ambiguous_gateway_and_generic_500_replay_identical_chunk(self) -> None:
        raw = b"immutable-artifact"
        digest = hashlib.sha256(raw).hexdigest()
        deployment_id = "c99-prod-stage-gateway"
        receipt = exact_receipt(
            deployment_id,
            0,
            len(raw),
            len(raw),
            digest,
        )
        for status, code in (
            (502, "http_error"),
            (503, "http_error"),
            (504, "http_error"),
            (500, "http_error"),
            (500, "internal_server_error"),
        ):
            with self.subTest(status=status, code=code):
                error = DEPLOY.HTTPDeployError(
                    "ambiguous upstream response",
                    status=status,
                    code=code,
                )
                bridge = mock.Mock(side_effect=[error, receipt])
                with mock.patch.object(DEPLOY, "bridge_call", bridge), mock.patch.object(
                    DEPLOY.time,
                    "sleep",
                ):
                    result = DEPLOY.stage_artifact(
                        object(),
                        "temporary-token",
                        deployment_id,
                        raw,
                        digest,
                        len(raw),
                    )
                self.assertTrue(result["complete"])
                self.assertEqual(2, bridge.call_count)
                self.assertEqual(
                    bridge.call_args_list[0].kwargs,
                    bridge.call_args_list[1].kwargs,
                )

    def test_invalid_receipt_is_rejected_without_a_second_write(self) -> None:
        raw = b"immutable-artifact"
        digest = hashlib.sha256(raw).hexdigest()
        invalid = exact_receipt(
            "c99-prod-stage-receipt",
            0,
            len(raw),
            len(raw),
            digest,
        )
        invalid["unexpected"] = True
        bridge = mock.Mock(return_value=invalid)
        with mock.patch.object(DEPLOY, "bridge_call", bridge), self.assertRaisesRegex(
            DEPLOY.DeployError,
            "unexpected receipt schema",
        ):
            DEPLOY.stage_artifact(
                object(),
                "temporary-token",
                "c99-prod-stage-receipt",
                raw,
                digest,
                len(raw),
            )
        self.assertEqual(1, bridge.call_count)

    def test_completed_receipt_requires_exact_size_and_digest(self) -> None:
        digest = "a" * 64
        base = exact_receipt("c99-prod-stage-proof", 0, 4, 4, digest)
        for field, value, message in (
            ("total_bytes", 3, "byte count mismatch"),
            ("artifact_sha256", "b" * 64, "digest mismatch"),
            ("complete", False, "completion state mismatch"),
        ):
            invalid = dict(base)
            invalid[field] = value
            with self.subTest(field=field), self.assertRaisesRegex(
                DEPLOY.DeployError,
                message,
            ):
                DEPLOY.verify_stage_receipt(
                    invalid,
                    "c99-prod-stage-proof",
                    digest,
                    4,
                    0,
                    4,
                    True,
                )

    def test_source_integrity_failure_stops_before_any_request(self) -> None:
        bridge = mock.Mock()
        with mock.patch.object(DEPLOY, "bridge_call", bridge), self.assertRaisesRegex(
            DEPLOY.DeployError,
            "source integrity",
        ):
            DEPLOY.stage_artifact(
                object(),
                "temporary-token",
                "c99-prod-stage-source",
                b"artifact",
                "0" * 64,
                len(b"artifact"),
            )
        bridge.assert_not_called()

    def test_run_request_is_small_and_never_contains_package_base64(self) -> None:
        source = (ROOT / "scripts" / "deploy-wordpress.py").read_text(encoding="utf-8")
        main = source.split("def main() -> int:", 1)[1]
        self.assertNotIn('"package_base64"', main)
        self.assertIn('"staged": True', main)
        self.assertIn('audit["artifact_staging"] = stage_artifact(', main)
        self.assertLess(
            main.index('audit["artifact_staging"] = stage_artifact('),
            main.index("result = install_with_recovery("),
        )

    def test_renderer_rejects_incomplete_or_oversized_normal_artifact_identity(self) -> None:
        common = {
            "token": "test-only-token",
            "deployment_id": "c99-prod-stage-render",
            "max_bytes": 1024,
            "local_test": True,
            "target_host": "localhost",
            "allowed_hosts": {"localhost"},
        }
        for fields in (
            {"expected_artifact_sha256": "a" * 64},
            {"expected_artifact_size": 1},
            {
                "expected_artifact_sha256": "a" * 64,
                "expected_artifact_size": 1025,
            },
        ):
            with self.subTest(fields=fields), self.assertRaisesRegex(
                DEPLOY.DeployError,
                "expected artifact identity",
            ):
                DEPLOY.render_bridge(**common, **fields)

        rendered_recovery = DEPLOY.render_bridge(
            **common,
            expected_artifact_sha256="a" * 64,
            interrupted_forward_proof_sha256="b" * 64,
        )
        self.assertIn("'expected_artifact_size'   => 0", rendered_recovery)

    def test_ambiguous_install_recovery_requires_exact_embedded_plugin_digest(self) -> None:
        expected_plugin_sha256 = "b" * 64
        run_fields = {
            "slug": DEPLOY.SLUG,
            "type": "plugin",
            "version": "1.18.1",
            "expected_sha256": "a" * 64,
            "staged": True,
            "activate": True,
        }
        status = {
            "phase": "installed_pending_stabilization",
            "forward_stabilization_candidate": True,
            "expected_sha256": run_fields["expected_sha256"],
            "installed_plugin_sha256": "c" * 64,
            "current_plugin_sha256": "c" * 64,
            "current_version": run_fields["version"],
            "current_database_version": run_fields["version"],
            "current_active": True,
        }
        ambiguous = DEPLOY.NetworkDeployError("lost run response")
        with mock.patch.object(DEPLOY, "bridge_call", side_effect=ambiguous), mock.patch.object(
            DEPLOY,
            "poll_deployment_status",
            return_value=status,
        ), mock.patch.object(DEPLOY, "stabilize_deployment") as stabilize:
            with self.assertRaises(DEPLOY.NetworkDeployError):
                DEPLOY.install_with_recovery(
                    object(),
                    "temporary-token",
                    "c99-prod-stage-install",
                    run_fields,
                    expected_plugin_sha256,
                )
        stabilize.assert_not_called()


if __name__ == "__main__":
    unittest.main()
