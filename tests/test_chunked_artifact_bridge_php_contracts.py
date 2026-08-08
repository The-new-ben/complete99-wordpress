from __future__ import annotations

import re
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BRIDGE_PATH = ROOT / "deploy" / "temporary-bridge.php"


class ChunkedArtifactBridgePHPContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.source = BRIDGE_PATH.read_text(encoding="utf-8")
        cls.stage_section = cls._section(
            "$route_prefix . '/stage'", "$route_prefix . '/run'"
        )
        cls.stabilize_section = cls._section(
            "$route_prefix . '/stabilize'", "$route_prefix . '/configure-sync'"
        )
        cls.run_section = cls._section(
            "$route_prefix . '/run'", "$route_prefix . '/rollback'"
        )
        cls.rollback_section = cls._section(
            "$route_prefix . '/rollback'",
            "$route_prefix . '/reconcile-orphaned-rollback'",
        )
        cls.finalize_section = cls.source.split("$route_prefix . '/finalize'", 1)[1]

    @classmethod
    def _section(cls, start: str, end: str) -> str:
        return cls.source.split(start, 1)[1].split(end, 1)[0]

    def test_php_template_lints(self) -> None:
        result = subprocess.run(
            ["php", "-l", str(BRIDGE_PATH)],
            cwd=ROOT,
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertIn("No syntax errors detected", result.stdout)

    def test_embedded_release_identity_is_singular_and_bounded(self) -> None:
        self.assertEqual(1, self.source.count("__C99_EXPECTED_ARTIFACT_SIZE__"))
        self.assertEqual(1, self.source.count("__C99_EXPECTED_ARTIFACT_SHA256__"))
        self.assertEqual(1, self.source.count("__C99_EXPECTED_PLUGIN_SHA256__"))
        self.assertEqual(1, self.source.count("__C99_EXPECTED_VERSION__"))
        for marker in (
            "'stage_chunk_max_bytes'    => 1048576",
            "$config['expected_artifact_size'] > (int) $config['max_bytes']",
            "$config['expected_plugin_sha256']",
            "$config['expected_version']",
        ):
            self.assertIn(marker, self.source)

    def test_stage_requires_exact_json_fields_and_owned_reservation(self) -> None:
        expected_fields = {
            "chunk_base64",
            "chunk_sha256",
            "deployment_id",
            "expected_artifact_sha256",
            "expected_artifact_size",
            "final",
            "offset",
            "token",
        }
        request_shape = self.stage_section.split("$expected_request_keys = array(", 1)[1].split(
            ");", 1
        )[0]
        self.assertEqual(
            expected_fields,
            set(re.findall(r"'([a-z0-9_]+)'", request_shape)),
        )
        for marker in (
            "$lock_owner !== (string) ( $lock['owner_id'] ?? '' )",
            "'reserved' !== (string) ( $lock['phase'] ?? '' )",
            "$lock_age >= (int) $config['recovery_lease_seconds']",
            "$heartbeat_lock( $deployment_id, $lock_owner",
            "$acquire_process_lock()",
        ):
            self.assertIn(marker, self.stage_section)

    def test_stage_is_sequential_and_only_last_identical_chunk_replays(self) -> None:
        for marker in (
            "$offset < $received",
            "$offset > $received",
            "$offset === (int) $metadata['last_offset']",
            "$next_offset === $received",
            "hash_equals( $chunk, $existing )",
            "c99_stage_replay_changed",
            "c99_stage_overlap",
            "c99_stage_gap",
            "$final !== ( $next_offset === $expected_size )",
        ):
            self.assertIn(marker, self.stage_section)

    def test_final_chunk_proves_whole_archive_and_rejects_links(self) -> None:
        helpers = self._section(
            "$validate_staged_archive = static function",
            "$consume_staged_artifact = static function",
        )
        for marker in (
            "hash_file( 'sha256', $artifact_path )",
            "ZipArchive::CHECKCONS",
            "in_array( '..', $segments, true )",
            "str_contains( $name, '\\\\' )",
            "0120000 === $file_type",
            "$config['plugin_file'] === $name",
        ):
            self.assertIn(marker, self.stage_section + helpers)

    def test_run_never_decodes_a_package_and_rehashes_around_claim(self) -> None:
        self.assertNotIn("base64_decode( $encoded", self.run_section)
        self.assertNotIn("put_contents( $temp, $bytes", self.run_section)
        self.assertIn("array_key_exists( 'package_base64', $json_params )", self.run_section)
        self.assertIn("true !== $staged", self.run_section)
        first_inspect = self.run_section.index("$inspect_staged_artifact( $deployment_id )")
        claim = self.run_section.index("$claim_lock(")
        second_inspect = self.run_section.index(
            "$inspect_staged_artifact( $deployment_id )", first_inspect + 1
        )
        consume = self.run_section.index("$consume_staged_artifact( $deployment_id, $temp )")
        install = self.run_section.index("$upgrader->install(")
        self.assertLess(first_inspect, claim)
        self.assertLess(claim, second_inspect)
        self.assertLess(second_inspect, consume)
        self.assertLess(consume, install)
        self.assertIn(
            "hash_equals( (string) $config['expected_plugin_sha256']",
            self.run_section,
        )

    def test_terminal_and_recovery_paths_remove_exact_stage_residue(self) -> None:
        self.assertIn("$stage_cleanup_on_exit", self.run_section)
        self.assertIn("$cleanup_staging( $deployment_id )", self.run_section)
        self.assertIn("$cleanup_staging( $deployment_id )", self.stabilize_section)
        self.assertIn("$cleanup_staging( $deployment_id )", self.rollback_section)
        self.assertIn("$cleanup_staging( $deployment_id )", self.finalize_section)
        cleanup = self._section(
            "$cleanup_staging = static function",
            "$read_stage_metadata = static function",
        )
        for marker in (
            "'artifact.zip' !== $entry",
            "'stage.json' !== $entry",
            "c99_stage_cleanup_unexpected",
            "is_link( $entry_path )",
            "@unlink( $entry_path )",
            "@rmdir( $stage_dir )",
        ):
            self.assertIn(marker, cleanup)

    def test_run_cleanup_error_overrides_the_pending_response_after_unlock(self) -> None:
        finally_block = self.run_section.split("} finally {", 1)[1]
        cleanup = finally_block.index(
            "$stage_cleanup_result = $cleanup_staging( $deployment_id );"
        )
        unlock = finally_block.index("$release_process_lock( $process_lock );")
        check = finally_block.index("is_wp_error( $stage_cleanup_result )")
        returned = finally_block.index("return $stage_cleanup_result;")
        self.assertLess(cleanup, unlock)
        self.assertLess(unlock, check)
        self.assertLess(check, returned)


if __name__ == "__main__":
    unittest.main()
