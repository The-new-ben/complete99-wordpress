from __future__ import annotations

import ast
import base64
import hashlib
import importlib.util
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
import zipfile
from pathlib import Path
from typing import Any
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]
BRIDGE_PATH = ROOT / "deploy" / "temporary-bridge.php"
DEPLOY_PATH = ROOT / "scripts" / "deploy-wordpress.py"
RECOVER_PATH = ROOT / "scripts" / "recover-wordpress.py"


def load_module(name: str, path: Path) -> Any:
    spec = importlib.util.spec_from_file_location(name, path)
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


DEPLOY = load_module("complete99_chunked_staging_deployer", DEPLOY_PATH)


def receipt(
    *,
    deployment_id: str,
    offset: int,
    chunk_size: int,
    total_size: int,
    artifact_sha256: str,
    final: bool,
) -> dict[str, Any]:
    next_offset = offset + chunk_size
    return {
        "deployment_id": deployment_id,
        "accepted_offset": offset,
        "next_offset": next_offset,
        "total_bytes": next_offset,
        "complete": final,
        "artifact_sha256": artifact_sha256 if final else "",
    }


class ChunkedArtifactPythonSecurityTests(unittest.TestCase):
    deployment_id = "c99-prod-chunked-security-1"
    token = "test-only-stage-token"

    def test_stage_receipt_accepts_only_the_exact_schema_and_types(self) -> None:
        raw = b"last-chunk"
        digest = hashlib.sha256(raw).hexdigest()
        expected = receipt(
            deployment_id=self.deployment_id,
            offset=0,
            chunk_size=len(raw),
            total_size=len(raw),
            artifact_sha256=digest,
            final=True,
        )
        self.assertEqual(
            expected,
            DEPLOY.verify_stage_receipt(
                expected,
                self.deployment_id,
                digest,
                len(raw),
                0,
                len(raw),
                True,
            ),
        )

        mutations: list[tuple[str, Any]] = [
            ("deployment_id", "c99-prod-wrong-identity"),
            ("accepted_offset", 1),
            ("next_offset", len(raw) - 1),
            ("total_bytes", len(raw) - 1),
            ("complete", False),
            ("artifact_sha256", "0" * 64),
            ("accepted_offset", True),
            ("next_offset", str(len(raw))),
            ("total_bytes", float(len(raw))),
            ("complete", 1),
            ("artifact_sha256", None),
        ]
        for field, value in mutations:
            with self.subTest(field=field, value=value):
                candidate = dict(expected)
                candidate[field] = value
                with self.assertRaises(DEPLOY.DeployError):
                    DEPLOY.verify_stage_receipt(
                        candidate,
                        self.deployment_id,
                        digest,
                        len(raw),
                        0,
                        len(raw),
                        True,
                    )

        for candidate in (
            {**expected, "extra": "not-allowed"},
            {key: value for key, value in expected.items() if key != "complete"},
            [],
            None,
        ):
            with self.subTest(schema=candidate):
                with self.assertRaises(DEPLOY.DeployError):
                    DEPLOY.verify_stage_receipt(
                        candidate,  # type: ignore[arg-type]
                        self.deployment_id,
                        digest,
                        len(raw),
                        0,
                        len(raw),
                        True,
                    )

    def test_incomplete_receipt_never_claims_a_digest_or_full_size(self) -> None:
        digest = "a" * 64
        response = receipt(
            deployment_id=self.deployment_id,
            offset=0,
            chunk_size=4,
            total_size=8,
            artifact_sha256=digest,
            final=False,
        )
        DEPLOY.verify_stage_receipt(
            response,
            self.deployment_id,
            digest,
            8,
            0,
            4,
            False,
        )
        for mutation in (
            {**response, "artifact_sha256": digest},
            {**response, "next_offset": 8, "total_bytes": 8},
            {**response, "complete": True},
        ):
            with self.subTest(mutation=mutation):
                with self.assertRaises(DEPLOY.DeployError):
                    DEPLOY.verify_stage_receipt(
                        mutation,
                        self.deployment_id,
                        digest,
                        8,
                        0,
                        4,
                        False,
                    )

    def test_stage_chunk_sends_exact_identity_offset_hash_and_bytes(self) -> None:
        chunk = b"chunk-bytes"
        digest = hashlib.sha256(chunk).hexdigest()
        response = receipt(
            deployment_id=self.deployment_id,
            offset=0,
            chunk_size=len(chunk),
            total_size=len(chunk),
            artifact_sha256=digest,
            final=True,
        )
        with mock.patch.object(DEPLOY, "bridge_call", return_value=response) as call:
            result = DEPLOY.stage_artifact_chunk(
                object(),
                self.token,
                self.deployment_id,
                digest,
                len(chunk),
                0,
                chunk,
                True,
            )
        self.assertEqual(response, result)
        call.assert_called_once_with(
            mock.ANY,
            "stage",
            self.token,
            self.deployment_id,
            expected_artifact_sha256=digest,
            expected_artifact_size=len(chunk),
            offset=0,
            chunk_sha256=digest,
            chunk_base64=base64.b64encode(chunk).decode("ascii"),
            final=True,
        )

    def test_stage_chunk_rejects_gap_overlap_size_and_final_flag_inputs(self) -> None:
        digest = "a" * 64
        valid = {
            "client": object(),
            "token": self.token,
            "deployment_id": self.deployment_id,
            "expected_artifact_sha256": digest,
            "expected_artifact_size": 4,
            "offset": 0,
            "chunk": b"data",
            "final": True,
        }
        invalid: tuple[dict[str, Any], ...] = (
            {"expected_artifact_sha256": "g" * 64},
            {"expected_artifact_size": True},
            {"expected_artifact_size": 0},
            {"offset": True},
            {"offset": -1},
            {"offset": 1},
            {"chunk": bytearray(b"data")},
            {"chunk": b""},
            {"chunk": b"data", "expected_artifact_size": 5, "final": True},
            {"chunk": b"data", "expected_artifact_size": 4, "final": False},
            {
                "chunk": b"x" * (DEPLOY.ARTIFACT_STAGE_CHUNK_BYTES + 1),
                "expected_artifact_size": DEPLOY.ARTIFACT_STAGE_CHUNK_BYTES + 1,
            },
        )
        with mock.patch.object(DEPLOY, "bridge_call") as call:
            for changes in invalid:
                with self.subTest(changes=changes):
                    candidate = dict(valid)
                    candidate.update(changes)
                    with self.assertRaises(DEPLOY.DeployError):
                        DEPLOY.stage_artifact_chunk(**candidate)
            call.assert_not_called()

    def test_lost_response_retries_only_the_identical_chunk(self) -> None:
        chunk = b"ambiguous-write"
        digest = hashlib.sha256(chunk).hexdigest()
        response = receipt(
            deployment_id=self.deployment_id,
            offset=0,
            chunk_size=len(chunk),
            total_size=len(chunk),
            artifact_sha256=digest,
            final=True,
        )
        network_error = DEPLOY.NetworkDeployError("lost response")
        with (
            mock.patch.object(
                DEPLOY,
                "bridge_call",
                side_effect=[network_error, response],
            ) as call,
            mock.patch.object(DEPLOY.time, "sleep") as sleep,
        ):
            DEPLOY.stage_artifact_chunk(
                object(),
                self.token,
                self.deployment_id,
                digest,
                len(chunk),
                0,
                chunk,
                True,
            )
        self.assertEqual(2, call.call_count)
        self.assertEqual(call.call_args_list[0], call.call_args_list[1])
        sleep.assert_called_once()

        http_error = DEPLOY.HTTPDeployError(
            "trusted server rejection",
            status=500,
            code="c99_stage_offset",
            data={"status": 500},
        )
        with mock.patch.object(DEPLOY, "bridge_call", side_effect=http_error) as call:
            with self.assertRaises(DEPLOY.HTTPDeployError):
                DEPLOY.stage_artifact_chunk(
                    object(),
                    self.token,
                    self.deployment_id,
                    digest,
                    len(chunk),
                    0,
                    chunk,
                    True,
                )
        call.assert_called_once()

        generic_proxy_error = DEPLOY.HTTPDeployError(
            "ambiguous proxy response",
            status=500,
            code="internal_server_error",
            data={"status": 500},
        )
        with (
            mock.patch.object(
                DEPLOY,
                "bridge_call",
                side_effect=[generic_proxy_error, response],
            ) as call,
            mock.patch.object(DEPLOY.time, "sleep"),
        ):
            DEPLOY.stage_artifact_chunk(
                object(),
                self.token,
                self.deployment_id,
                digest,
                len(chunk),
                0,
                chunk,
                True,
            )
        self.assertEqual(2, call.call_count)
        self.assertEqual(call.call_args_list[0], call.call_args_list[1])

    def test_full_staging_is_sequential_and_exactly_bounded(self) -> None:
        raw = b"abcdefghij"
        digest = hashlib.sha256(raw).hexdigest()
        calls: list[tuple[int, bytes, bool]] = []

        def accept(
            _client: object,
            _token: str,
            deployment_id: str,
            expected_sha256: str,
            expected_size: int,
            offset: int,
            chunk: bytes,
            final: bool,
        ) -> dict[str, Any]:
            self.assertEqual(self.deployment_id, deployment_id)
            self.assertEqual(digest, expected_sha256)
            self.assertEqual(len(raw), expected_size)
            calls.append((offset, chunk, final))
            return receipt(
                deployment_id=deployment_id,
                offset=offset,
                chunk_size=len(chunk),
                total_size=expected_size,
                artifact_sha256=expected_sha256,
                final=final,
            )

        with (
            mock.patch.object(DEPLOY, "ARTIFACT_STAGE_CHUNK_BYTES", 4),
            mock.patch.object(DEPLOY, "stage_artifact_chunk", side_effect=accept),
        ):
            result = DEPLOY.stage_artifact(
                object(),
                self.token,
                self.deployment_id,
                raw,
                digest,
                len(raw),
            )
        self.assertEqual(
            [(0, b"abcd", False), (4, b"efgh", False), (8, b"ij", True)],
            calls,
        )
        self.assertEqual(
            {
                "artifact_sha256": digest,
                "artifact_size": len(raw),
                "chunk_bytes": 4,
                "chunk_count": 3,
                "complete": True,
                "final_next_offset": len(raw),
            },
            result,
        )
        serialized_summary = json.dumps(result, sort_keys=True)
        self.assertNotIn(self.token, serialized_summary)
        self.assertNotIn("chunk_base64", serialized_summary)
        self.assertNotIn(base64.b64encode(raw).decode("ascii"), serialized_summary)

    def test_full_staging_rejects_wrong_source_size_or_digest_before_network(self) -> None:
        raw = b"source"
        digest = hashlib.sha256(raw).hexdigest()
        cases = (
            (b"", digest, 0),
            (raw, digest, len(raw) + 1),
            (raw, "0" * 64, len(raw)),
            (bytearray(raw), digest, len(raw)),
            (raw, digest, True),
        )
        with mock.patch.object(DEPLOY, "stage_artifact_chunk") as call:
            for candidate, candidate_digest, candidate_size in cases:
                with self.subTest(
                    raw=candidate,
                    digest=candidate_digest,
                    size=candidate_size,
                ):
                    with self.assertRaises(DEPLOY.DeployError):
                        DEPLOY.stage_artifact(
                            object(),
                            self.token,
                            self.deployment_id,
                            candidate,  # type: ignore[arg-type]
                            candidate_digest,
                            candidate_size,
                        )
            call.assert_not_called()

    def test_production_run_uses_staging_and_never_embeds_package_base64(self) -> None:
        source = DEPLOY_PATH.read_text(encoding="utf-8")
        module = ast.parse(source)
        main = next(
            node
            for node in module.body
            if isinstance(node, ast.FunctionDef) and node.name == "main"
        )
        run_field_dicts: list[ast.Dict] = []
        stage_calls: list[ast.Call] = []
        install_calls: list[ast.Call] = []
        for node in ast.walk(main):
            if isinstance(node, ast.Assign) and any(
                isinstance(target, ast.Name) and target.id == "run_fields"
                for target in node.targets
            ):
                self.assertIsInstance(node.value, ast.Dict)
                run_field_dicts.append(node.value)
            if isinstance(node, ast.Call) and isinstance(node.func, ast.Name):
                if node.func.id == "stage_artifact":
                    stage_calls.append(node)
                if node.func.id == "install_with_recovery":
                    install_calls.append(node)
        self.assertEqual(1, len(run_field_dicts))
        keys = {
            key.value
            for key in run_field_dicts[0].keys
            if isinstance(key, ast.Constant) and isinstance(key.value, str)
        }
        self.assertIn("staged", keys)
        self.assertNotIn("package_base64", keys)
        self.assertNotIn("package_base64", ast.get_source_segment(source, main) or "")
        self.assertEqual(2, len(stage_calls))
        self.assertEqual(2, len(install_calls))
        for stage_call, install_call in zip(stage_calls, install_calls, strict=True):
            self.assertLess(stage_call.lineno, install_call.lineno)


class ChunkedArtifactBridgeSecurityContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bridge = BRIDGE_PATH.read_text(encoding="utf-8")

    @staticmethod
    def section(source: str, start: str, end: str) -> str:
        return source.split(start, 1)[1].split(end, 1)[0]

    def test_bridge_binds_exact_artifact_size_and_digest(self) -> None:
        self.assertEqual(1, self.bridge.count("__C99_EXPECTED_ARTIFACT_SIZE__"))
        self.assertEqual(1, self.bridge.count("__C99_EXPECTED_ARTIFACT_SHA256__"))
        rendered = DEPLOY.render_bridge(
            "test-only-token",
            self.deployment_id,
            8 * 1024 * 1024,
            True,
            target_host="localhost",
            allowed_hosts={"localhost"},
            expected_artifact_sha256="a" * 64,
            expected_artifact_size=1024,
        )
        self.assertNotIn("__C99_EXPECTED_ARTIFACT_SIZE__", rendered)
        self.assertIn("'expected_artifact_size'", rendered)
        self.assertIn("1024", rendered)
        for size in (0, -1, True, 1.5, 8 * 1024 * 1024 + 1):
            with self.subTest(size=size):
                with self.assertRaises(DEPLOY.DeployError):
                    DEPLOY.render_bridge(
                        "test-only-token",
                        self.deployment_id,
                        8 * 1024 * 1024,
                        True,
                        target_host="localhost",
                        allowed_hosts={"localhost"},
                        expected_artifact_sha256="a" * 64,
                        expected_artifact_size=size,  # type: ignore[arg-type]
                    )
        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.render_bridge(
                "test-only-token",
                self.deployment_id,
                8 * 1024 * 1024,
                True,
                target_host="localhost",
                allowed_hosts={"localhost"},
                expected_artifact_size=1024,
            )

        reviewed_recovery = DEPLOY.render_bridge(
            "test-only-token",
            self.deployment_id,
            8 * 1024 * 1024,
            True,
            target_host="localhost",
            allowed_hosts={"localhost"},
            expected_artifact_sha256="a" * 64,
            expected_artifact_size=0,
            interrupted_forward_proof_sha256="b" * 64,
        )
        self.assertIn("'expected_artifact_size'", reviewed_recovery)

    deployment_id = "c99-prod-chunked-security-1"

    def test_stage_route_uses_the_existing_capability_and_secret_guard(self) -> None:
        permission = self.section(
            self.bridge,
            "$permission = static function",
            "$canonicalize_json_value",
        )
        stage = self.section(
            self.bridge,
            "$route_prefix . '/stage'",
            "$route_prefix . '/run'",
        )
        for marker in (
            "current_user_can( 'update_plugins' )",
            "hash_equals( $config['token'], $token )",
            "'status' => 403",
            "'status' => 401",
        ):
            self.assertIn(marker, permission)
        self.assertIn("'methods'             => 'POST'", stage)
        self.assertIn("'permission_callback' => $permission", stage)
        self.assertIn("$config['deployment_id'] !== $deployment_id", stage)
        self.assertIn("preg_match( '/^[A-Za-z0-9._-]{8,96}$/', $deployment_id )", stage)

    def test_stage_is_bound_to_the_reserved_lock_owner_and_process_lock(self) -> None:
        stage = self.section(
            self.bridge,
            "$route_prefix . '/stage'",
            "$route_prefix . '/run'",
        )
        for marker in (
            "$acquire_process_lock()",
            "$release_process_lock( $process_lock )",
            "$read_lock( true )",
            "$lock_owner",
            "'reserved'",
            "deployment_id",
            "owner_id",
        ):
            self.assertIn(marker, stage)
        self.assertIn("finally", stage)

    def test_stage_rejects_wrong_identity_size_chunk_hash_and_encoding(self) -> None:
        stage = self.section(
            self.bridge,
            "$route_prefix . '/stage'",
            "$route_prefix . '/run'",
        )
        for field in (
            "expected_artifact_sha256",
            "expected_artifact_size",
            "offset",
            "chunk_sha256",
            "chunk_base64",
            "final",
        ):
            self.assertIn("get_param( '" + field + "' )", stage)
        for marker in (
            "$config['expected_artifact_sha256']",
            "$config['expected_artifact_size']",
            "$config['stage_chunk_max_bytes']",
            "$encoded_length > $max_encoded_bytes",
            "0 !== ( $encoded_length % 4 )",
            "base64_decode( $encoded, true )",
            "hash_equals( $encoded, base64_encode( $chunk ) )",
            "hash( 'sha256', $chunk )",
            "hash_equals( $chunk_sha",
        ):
            self.assertIn(marker, stage)
        self.assertNotIn("preg_match( '/^(?:[A-Za-z0-9+\\/]", stage)
        self.assertRegex(stage, r"413|422")

    def test_exact_offset_blocks_gap_overlap_and_mismatched_replay(self) -> None:
        stage = self.section(
            self.bridge,
            "$route_prefix . '/stage'",
            "$route_prefix . '/run'",
        )
        for marker in (
            "received_bytes",
            "last_offset",
            "last_size",
            "last_sha256",
            "last_final",
            "c99_stage_gap",
            "c99_stage_overlap",
            "c99_stage_replay_changed",
        ):
            self.assertIn(marker, stage)
        self.assertIn("hash_equals", stage)
        self.assertIn("accepted_offset", stage)
        self.assertIn("next_offset", stage)
        self.assertIn("total_bytes", stage)

    def test_filesystem_paths_and_archive_entries_fail_closed(self) -> None:
        helpers = self.section(
            self.bridge,
            "$validate_embedded_artifact_identity",
            "$protect_recovery_evidence_root",
        )
        for marker in (
            "is_link( $staging_root )",
            "is_link( $stage_dir )",
            "realpath( $staging_root )",
            "realpath( $stage_dir )",
            "wp_normalize_path( dirname( $resolved_dir ) )",
            "is_link( $artifact_path )",
            "lstat( $artifact_path )",
            "ZipArchive::CHECKCONS",
            "str_contains( $name, '\\\\' )",
            "in_array( '..', $segments, true )",
            "getExternalAttributesIndex",
            "0120000 === $file_type",
            "$total_uncompressed > (int) $config['max_bytes'] * 32",
            "$config['plugin_file'] === $name",
        ):
            self.assertIn(marker, helpers)

    def test_incomplete_or_changed_staging_is_rejected_before_consumption(self) -> None:
        inspect = self.section(
            self.bridge,
            "$inspect_staged_artifact",
            "$validate_staged_archive",
        )
        for marker in (
            "true !== $metadata['complete']",
            "$config['expected_artifact_size'] !== $metadata['received_bytes']",
            "$config['expected_artifact_sha256']",
            "filesize( $artifact_path )",
            "hash_file( 'sha256', $artifact_path )",
            "$before['ino']",
            "$after['ino']",
            "$before['dev']",
            "$after['dev']",
            "c99_stage_incomplete",
            "c99_stage_artifact_integrity",
        ):
            self.assertIn(marker, inspect)

    def test_stale_cleanup_is_allowlisted_and_refuses_links_or_unknown_files(self) -> None:
        cleanup = self.section(
            self.bridge,
            "$cleanup_staging",
            "$read_stage_metadata",
        )
        for marker in (
            "'artifact.zip' !== $entry",
            "'stage.json' !== $entry",
            "stage\\.json\\.tmp-",
            "c99_stage_cleanup_unexpected",
            "is_link( $entry_path )",
            "is_dir( $entry_path )",
            "is_file( $entry_path )",
            "@unlink( $entry_path )",
            "@rmdir( $stage_dir )",
        ):
            self.assertIn(marker, cleanup)

    def test_run_uses_only_a_complete_post_claim_rehashed_artifact(self) -> None:
        run = self.section(
            self.bridge,
            "$route_prefix . '/run'",
            "$route_prefix . '/rollback'",
        )
        self.assertIn("get_param( 'staged' )", run)
        self.assertIn("array_key_exists( 'package_base64', $json_params )", run)
        self.assertIn("null !== $request->get_param( 'package_base64' )", run)
        self.assertNotIn("base64_decode( $encoded", run)
        self.assertIn("$consume_staged_artifact(", run)
        self.assertIn("$validate_staged_archive(", run)
        claim = run.index("$claim_lock(")
        inspect_positions: list[int] = []
        cursor = 0
        while True:
            position = run.find("$inspect_staged_artifact(", cursor)
            if position < 0:
                break
            inspect_positions.append(position)
            cursor = position + 1
        self.assertGreaterEqual(len(inspect_positions), 2)
        pre_claim_inspect, post_claim_inspect = inspect_positions[:2]
        consume = run.index("$consume_staged_artifact(")
        install = run.index("$upgrader->install(")
        self.assertLess(pre_claim_inspect, claim)
        self.assertLess(claim, post_claim_inspect)
        self.assertLess(post_claim_inspect, consume)
        self.assertLess(consume, install)

    def test_staging_is_cleaned_by_rollback_finalize_and_failed_install_paths(self) -> None:
        run = self.section(
            self.bridge,
            "$route_prefix . '/run'",
            "$route_prefix . '/rollback'",
        )
        rollback = self.section(
            self.bridge,
            "$route_prefix . '/rollback'",
            "$route_prefix . '/reconcile-orphaned-rollback'",
        )
        finalize = self.bridge.split("$route_prefix . '/finalize'", 1)[1]
        self.assertIn("$cleanup_staging(", run)
        self.assertIn("$cleanup_staging(", rollback)
        self.assertIn("$cleanup_staging(", finalize)
        for section in (rollback, finalize):
            self.assertIn("is_wp_error", section)

        recovery = RECOVER_PATH.read_text(encoding="utf-8")
        self.assertIn('phase == "reserved"', recovery)
        self.assertIn("deployer.finalize_deployment(", recovery)

    def test_stage_receipt_schema_is_exact_and_non_secret(self) -> None:
        stage = self.section(
            self.bridge,
            "$route_prefix . '/stage'",
            "$route_prefix . '/run'",
        )
        for field in (
            "deployment_id",
            "accepted_offset",
            "next_offset",
            "total_bytes",
            "complete",
            "artifact_sha256",
        ):
            self.assertIn("'" + field + "'", stage)
        response = stage.split("return array(", 1)[-1]
        self.assertNotIn("chunk_base64", response)
        self.assertNotIn("token", response)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for staging runtime")
    def test_real_stage_callback_replay_digest_and_reserved_finalize_cleanup(self) -> None:
        with tempfile.TemporaryDirectory(prefix="c99-stage-runtime-") as temp:
            runtime_root = Path(temp)
            document_root = runtime_root / "wordpress"
            content_root = document_root / "wp-content"
            plugin_root = content_root / "plugins"
            temp_root = runtime_root / "temp"
            wp_admin_includes = document_root / "wp-admin" / "includes"
            for directory in (
                document_root,
                content_root,
                plugin_root,
                temp_root,
                wp_admin_includes,
            ):
                directory.mkdir(parents=True, exist_ok=True)
            (wp_admin_includes / "file.php").write_text("<?php\n", encoding="utf-8")

            artifact_path = runtime_root / "artifact.zip"
            with zipfile.ZipFile(
                artifact_path,
                "w",
                compression=zipfile.ZIP_DEFLATED,
            ) as archive:
                archive.writestr(
                    "complete99-platform/complete99-platform.php",
                    "<?php\n/* Plugin Name: Complete99 Platform */\n",
                )
                archive.writestr(
                    "complete99-platform/assets/runtime.txt",
                    "chunked staging runtime contract\n",
                )
                archive.writestr(
                    "complete99-platform/assets/exact-one-mib-stage.bin",
                    b"C" * (1024 * 1024 + 4096),
                    compress_type=zipfile.ZIP_STORED,
                )
            raw = artifact_path.read_bytes()
            artifact_sha256 = hashlib.sha256(raw).hexdigest()
            split = 1024 * 1024
            self.assertGreater(len(raw), split)
            self.assertLess(split, len(raw))
            self.assertLessEqual(len(raw), 2 * 1024 * 1024)

            rendered = DEPLOY.render_bridge(
                "runtime-stage-token",
                self.deployment_id,
                2 * 1024 * 1024,
                True,
                target_host="localhost",
                allowed_hosts={"localhost"},
                expected_artifact_sha256=artifact_sha256,
                expected_artifact_size=len(raw),
                expected_plugin_sha256="b" * 64,
                expected_version="1.18.1",
            )
            php_path = runtime_root / "runtime.php"
            php_prefix = f"""<?php
define('ABSPATH', {json.dumps(document_root.as_posix() + '/')});
define('WP_CONTENT_DIR', {json.dumps(content_root.as_posix())});
define('WP_PLUGIN_DIR', {json.dumps(plugin_root.as_posix())});
define('FS_CHMOD_FILE', 0644);
define('FS_CHMOD_DIR', 0755);

$GLOBALS['c99_actions'] = array();
$GLOBALS['c99_routes'] = array();
$GLOBALS['c99_options'] = array();

class WP_Error {{
    private string $code;
    private string $message;
    private mixed $data;
    public function __construct($code = '', $message = '', $data = array()) {{
        $this->code = (string) $code;
        $this->message = (string) $message;
        $this->data = $data;
    }}
    public function get_error_code() {{ return $this->code; }}
    public function get_error_message() {{ return $this->message; }}
    public function get_error_data() {{ return $this->data; }}
    public function add_data($data) {{ $this->data = $data; }}
}}

class WP_REST_Request {{
    private array $params;
    public function __construct(array $params) {{ $this->params = $params; }}
    public function get_param($key) {{
        return array_key_exists($key, $this->params) ? $this->params[$key] : null;
    }}
    public function get_json_params() {{ return $this->params; }}
}}

class C99_Test_Filesystem {{
    public function is_dir($path) {{ return is_dir($path); }}
    public function mkdir($path, $mode = 0755) {{ return @mkdir($path, $mode, true) || is_dir($path); }}
    public function put_contents($path, $contents, $mode = 0644) {{
        $written = @file_put_contents($path, $contents, LOCK_EX);
        if (false !== $written) {{ @chmod($path, $mode); }}
        return false !== $written;
    }}
    public function get_contents($path) {{ return @file_get_contents($path); }}
    public function exists($path) {{ return file_exists($path) || is_link($path); }}
    public function delete($path, $recursive = false) {{
        if (is_link($path) || is_file($path)) {{ return @unlink($path); }}
        if (!file_exists($path)) {{ return true; }}
        if (!$recursive || !is_dir($path)) {{ return @rmdir($path); }}
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {{
            $entry_path = $entry->getPathname();
            if ($entry->isLink() || $entry->isFile()) {{
                if (!@unlink($entry_path)) {{ return false; }}
            }} elseif (!@rmdir($entry_path)) {{
                return false;
            }}
        }}
        return @rmdir($path);
    }}
}}

class C99_Test_Wpdb {{
    public bool $is_mysql = true;
    public string $options = 'wp_options';
    public function prepare($query, ...$args) {{ return array('query' => $query, 'args' => $args); }}
    public function query($prepared) {{
        $query = (string) $prepared['query'];
        $args = $prepared['args'];
        if (str_starts_with($query, 'UPDATE ')) {{
            $replacement = $args[0];
            $name = (string) $args[1];
            $expected = $args[2];
            $current = $GLOBALS['c99_options'][$name] ?? null;
            if (maybe_serialize($current) !== $expected) {{ return 0; }}
            $GLOBALS['c99_options'][$name] = maybe_unserialize($replacement);
            return 1;
        }}
        if (str_starts_with($query, 'DELETE ')) {{
            $name = (string) $args[0];
            $expected = $args[1];
            $current = $GLOBALS['c99_options'][$name] ?? null;
            if (maybe_serialize($current) !== $expected) {{ return 0; }}
            unset($GLOBALS['c99_options'][$name]);
            return 1;
        }}
        throw new RuntimeException('Unexpected database query');
    }}
}}

$wp_filesystem = new C99_Test_Filesystem();
$wpdb = new C99_Test_Wpdb();

function add_action($name, $callback) {{ $GLOBALS['c99_actions'][$name] = $callback; }}
function register_rest_route($namespace, $route, $definition) {{ $GLOBALS['c99_routes'][$route] = $definition; }}
function current_user_can($capability) {{ return 'update_plugins' === $capability; }}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function trailingslashit($value) {{ return rtrim((string) $value, "/\\\\") . '/'; }}
function wp_normalize_path($value) {{ return str_replace('\\\\', '/', (string) $value); }}
function sanitize_key($value) {{ return preg_replace('/[^a-z0-9_\\-]/', '', strtolower((string) $value)); }}
function sanitize_text_field($value) {{ return (string) $value; }}
function rest_sanitize_boolean($value) {{ return filter_var($value, FILTER_VALIDATE_BOOLEAN); }}
function wp_json_encode($value) {{ return json_encode($value, JSON_UNESCAPED_SLASHES); }}
function wp_parse_url($url, $component = -1) {{ return parse_url($url, $component); }}
function home_url($path = '/') {{ return 'http://localhost' . $path; }}
function site_url($path = '/') {{ return 'http://localhost' . $path; }}
function rest_url() {{ return 'http://localhost/wp-json/'; }}
function get_filesystem_method() {{ return 'direct'; }}
function WP_Filesystem() {{ return true; }}
function get_option($name, $default = false) {{ return $GLOBALS['c99_options'][$name] ?? $default; }}
function add_option($name, $value, $deprecated = '', $autoload = false) {{
    if (array_key_exists($name, $GLOBALS['c99_options'])) {{ return false; }}
    $GLOBALS['c99_options'][$name] = $value;
    return true;
}}
function delete_option($name) {{ unset($GLOBALS['c99_options'][$name]); return true; }}
function maybe_serialize($value) {{ return serialize($value); }}
function maybe_unserialize($value) {{ return is_string($value) ? unserialize($value) : $value; }}
function wp_cache_delete($key, $group = '') {{ return true; }}
function wp_cache_flush() {{ return true; }}
function get_temp_dir() {{ return {json.dumps(temp_root.as_posix() + '/')}; }}
"""
            php_suffix = f"""

($GLOBALS['c99_actions']['rest_api_init'])();

function c99_call_route($suffix, $params) {{
    $route = '/' . {json.dumps(self.deployment_id)} . $suffix;
    if (!isset($GLOBALS['c99_routes'][$route])) {{ throw new RuntimeException('Missing route ' . $route); }}
    $definition = $GLOBALS['c99_routes'][$route];
    $request = new WP_REST_Request($params);
    $allowed = $definition['permission_callback']($request);
    if (is_wp_error($allowed)) {{ return $allowed; }}
    return $definition['callback']($request);
}}

function c99_expect($condition, $message) {{
    if (!$condition) {{ throw new RuntimeException($message); }}
}}

function c99_reserve() {{
    $deployment_id = {json.dumps(self.deployment_id)};
    $token = 'runtime-stage-token';
    $GLOBALS['c99_options']['complete99_deploy_lock'] = array(
        'deployment_id' => $deployment_id,
        'owner_id' => hash_hmac('sha256', $deployment_id, $token),
        'fence' => 1,
        'phase' => 'reserved',
        'started_at' => time(),
        'updated_at' => time(),
        'heartbeat_seq' => 1,
    );
}}

$raw = base64_decode({json.dumps(base64.b64encode(raw).decode('ascii'))}, true);
$expected_sha = {json.dumps(artifact_sha256)};
$expected_size = strlen($raw);
$split = {split};
$first = substr($raw, 0, $split);
$last = substr($raw, $split);
$base = array(
    'token' => 'runtime-stage-token',
    'deployment_id' => {json.dumps(self.deployment_id)},
    'expected_artifact_sha256' => $expected_sha,
    'expected_artifact_size' => $expected_size,
);
$payload_first = $base + array(
    'offset' => 0,
    'chunk_sha256' => hash('sha256', $first),
    'chunk_base64' => base64_encode($first),
    'final' => false,
);
$stage_dir = trailingslashit(WP_CONTENT_DIR) . '.complete99-deploy-staging/' . {json.dumps(self.deployment_id)};

$malformed_payload = $base + array(
    'offset' => 0,
    'chunk_sha256' => hash('sha256', 'A'),
    'chunk_base64' => 'Q!==',
    'final' => false,
);
$malformed_result = c99_call_route('/stage', $malformed_payload);
c99_expect(is_wp_error($malformed_result), 'malformed base64 was accepted');
c99_expect('c99_stage_chunk_encoding' === $malformed_result->get_error_code(), 'malformed base64 error mismatch');

$noncanonical_payload = $base + array(
    'offset' => 0,
    'chunk_sha256' => hash('sha256', 'A'),
    'chunk_base64' => 'QR==',
    'final' => false,
);
$noncanonical_result = c99_call_route('/stage', $noncanonical_payload);
c99_expect(is_wp_error($noncanonical_result), 'noncanonical base64 was accepted');
c99_expect('c99_stage_chunk_encoding' === $noncanonical_result->get_error_code(), 'noncanonical base64 error mismatch');

$bad_length_payload = $base + array(
    'offset' => 0,
    'chunk_sha256' => hash('sha256', 'A'),
    'chunk_base64' => 'QQ=',
    'final' => false,
);
$bad_length_result = c99_call_route('/stage', $bad_length_payload);
c99_expect(is_wp_error($bad_length_result), 'non-modulo base64 was accepted');
c99_expect('c99_stage_chunk_encoding' === $bad_length_result->get_error_code(), 'non-modulo base64 error mismatch');
c99_expect(!file_exists($stage_dir) && !is_link($stage_dir), 'rejected encoding created staging residue');

c99_reserve();
$first_receipt = c99_call_route('/stage', $payload_first);
c99_expect(!is_wp_error($first_receipt), 'first chunk failed');
c99_expect(1048576 === strlen($first), 'first chunk was not exactly one MiB');
c99_expect($split === $first_receipt['next_offset'], 'first offset mismatch');
$replay_receipt = c99_call_route('/stage', $payload_first);
c99_expect($first_receipt === $replay_receipt, 'identical replay receipt changed');

$changed = str_repeat('X', strlen($first));
if (hash_equals($changed, $first)) {{ $changed = str_repeat('Y', strlen($first)); }}
$changed_payload = $base + array(
    'offset' => 0,
    'chunk_sha256' => hash('sha256', $changed),
    'chunk_base64' => base64_encode($changed),
    'final' => false,
);
$changed_result = c99_call_route('/stage', $changed_payload);
c99_expect(is_wp_error($changed_result), 'changed replay was accepted');
c99_expect('c99_stage_replay_changed' === $changed_result->get_error_code(), 'changed replay error mismatch');

c99_expect(is_dir($stage_dir), 'partial staging directory missing');
$finalize = c99_call_route('/finalize', array(
    'token' => 'runtime-stage-token',
    'deployment_id' => {json.dumps(self.deployment_id)},
));
c99_expect(!is_wp_error($finalize), 'reserved finalize failed');
c99_expect(true === $finalize['finalized'], 'reserved finalize not confirmed');
c99_expect(!file_exists($stage_dir) && !is_link($stage_dir), 'partial stage residue remained');
c99_expect(!isset($GLOBALS['c99_options']['complete99_deploy_lock']), 'reserved lock remained');

c99_reserve();
$first_again = c99_call_route('/stage', $payload_first);
c99_expect(!is_wp_error($first_again), 'restaged first chunk failed');
$payload_last = $base + array(
    'offset' => $split,
    'chunk_sha256' => hash('sha256', $last),
    'chunk_base64' => base64_encode($last),
    'final' => true,
);
$final_receipt = c99_call_route('/stage', $payload_last);
c99_expect(!is_wp_error($final_receipt), 'final chunk failed');
c99_expect(true === $final_receipt['complete'], 'final receipt incomplete');
c99_expect($expected_size === $final_receipt['total_bytes'], 'final size mismatch');
c99_expect(hash_equals($expected_sha, $final_receipt['artifact_sha256']), 'final digest mismatch');
$complete_finalize = c99_call_route('/finalize', array(
    'token' => 'runtime-stage-token',
    'deployment_id' => {json.dumps(self.deployment_id)},
));
c99_expect(!is_wp_error($complete_finalize), 'complete reserved finalize failed');
c99_expect(true === $complete_finalize['finalized'], 'complete reserved finalize not confirmed');
c99_expect(!file_exists($stage_dir) && !is_link($stage_dir), 'complete stage residue remained');
c99_expect(!isset($GLOBALS['c99_options']['complete99_deploy_lock']), 'complete reserved lock remained');

echo json_encode(array(
    'malformed_code' => $malformed_result->get_error_code(),
    'noncanonical_code' => $noncanonical_result->get_error_code(),
    'bad_length_code' => $bad_length_result->get_error_code(),
    'first_receipt' => $first_receipt,
    'replay_receipt' => $replay_receipt,
    'changed_replay_code' => $changed_result->get_error_code(),
    'finalize' => $finalize,
    'final_receipt' => $final_receipt,
    'complete_finalize' => $complete_finalize,
), JSON_UNESCAPED_SLASHES);
"""
            php_path.write_text(
                php_prefix + "\n" + rendered + "\n" + php_suffix,
                encoding="utf-8",
            )
            result = subprocess.run(
                ["php", str(php_path)],
                cwd=runtime_root,
                capture_output=True,
                text=True,
                check=False,
            )
            self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
            evidence = json.loads(result.stdout)
            self.assertEqual(evidence["first_receipt"], evidence["replay_receipt"])
            self.assertEqual(
                "c99_stage_chunk_encoding",
                evidence["malformed_code"],
            )
            self.assertEqual(
                "c99_stage_chunk_encoding",
                evidence["noncanonical_code"],
            )
            self.assertEqual(
                "c99_stage_chunk_encoding",
                evidence["bad_length_code"],
            )
            self.assertEqual(
                "c99_stage_replay_changed",
                evidence["changed_replay_code"],
            )
            self.assertTrue(evidence["finalize"]["finalized"])
            self.assertTrue(evidence["finalize"]["lock_released"])
            self.assertTrue(evidence["finalize"]["state_removed"])
            self.assertTrue(evidence["final_receipt"]["complete"])
            self.assertEqual(len(raw), evidence["final_receipt"]["total_bytes"])
            self.assertEqual(
                artifact_sha256,
                evidence["final_receipt"]["artifact_sha256"],
            )
            self.assertTrue(evidence["complete_finalize"]["finalized"])
            self.assertTrue(evidence["complete_finalize"]["lock_released"])
            self.assertTrue(evidence["complete_finalize"]["state_removed"])
            serialized_evidence = json.dumps(evidence, sort_keys=True)
            self.assertNotIn("runtime-stage-token", serialized_evidence)
            self.assertNotIn("chunk_base64", serialized_evidence)
            self.assertNotIn(base64.b64encode(raw).decode("ascii"), serialized_evidence)
            self.assertNotIn(base64.b64encode(raw[:split]).decode("ascii"), serialized_evidence)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for bridge lint")
    def test_template_and_rendered_staging_bridge_are_valid_php(self) -> None:
        template = subprocess.run(
            ["php", "-l", str(BRIDGE_PATH)],
            cwd=ROOT,
            capture_output=True,
            text=True,
            check=False,
        )
        self.assertEqual(template.returncode, 0, template.stdout + template.stderr)

        rendered = DEPLOY.render_bridge(
            "test-only-token",
            self.deployment_id,
            8 * 1024 * 1024,
            True,
            target_host="localhost",
            allowed_hosts={"localhost"},
            expected_artifact_sha256="a" * 64,
            expected_artifact_size=1024,
            expected_plugin_sha256="b" * 64,
            expected_version="1.18.1",
        )
        with tempfile.TemporaryDirectory(prefix="c99-stage-bridge-lint-") as temp:
            rendered_path = Path(temp) / "bridge.php"
            rendered_path.write_text("<?php\n" + rendered, encoding="utf-8")
            result = subprocess.run(
                ["php", "-l", str(rendered_path)],
                cwd=ROOT,
                capture_output=True,
                text=True,
                check=False,
            )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)


if __name__ == "__main__":
    unittest.main()
