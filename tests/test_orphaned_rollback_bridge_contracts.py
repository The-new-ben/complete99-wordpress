from __future__ import annotations

import hashlib
import json
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BRIDGE_PATH = ROOT / "deploy" / "temporary-bridge.php"
PROOF_PATH = ROOT / "docs" / "recovery-proofs" / "c99-prod-31171940371-1.json"


class OrphanedRollbackBridgeContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bridge = BRIDGE_PATH.read_text(encoding="utf-8")
        cls.status = cls.bridge.split("$route_prefix . '/status'", 1)[1].split(
            "$route_prefix . '/stabilize'", 1
        )[0]
        cls.reconcile = cls.bridge.split(
            "$route_prefix . '/reconcile-orphaned-rollback'", 1
        )[1].split("$route_prefix . '/finalize'", 1)[0]
        cls.finalize = cls.bridge.split("$route_prefix . '/finalize'", 1)[1]

    def test_reviewed_proof_is_canonicalized_hashed_and_bound(self) -> None:
        self.assertIn("$request->get_param( 'reviewed_proof' )", self.reconcile)
        self.assertIn("ksort( $value, SORT_STRING )", self.bridge)
        self.assertIn(
            "JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE",
            self.reconcile,
        )
        self.assertIn("$has_exact_keys( $reviewed_proof", self.reconcile)
        self.assertIn("! is_int( $failed_proof['run_id']", self.reconcile)
        self.assertIn("true !== ( $prior_proof['active']", self.reconcile)
        self.assertIn(
            "$deployment_id !== (string) ( $failed_proof['deployment_id']",
            self.reconcile,
        )
        self.assertIn(
            "$prior_deployment !== (string) ( $prior_proof['deployment_id']",
            self.reconcile,
        )
        self.assertIn(
            "$failed_proof['run_id'] ?? 0 ) === (int) ( $prior_proof['run_id']",
            self.reconcile,
        )
        self.assertIn(
            "str_contains( (string) ( $failed_proof['deployment_id']",
            self.reconcile,
        )
        self.assertIn(
            "$failed_proof['candidate_version'] ?? '' ) === (string) ( $prior_proof['version']",
            self.reconcile,
        )
        self.assertIn(
            "$failed_proof['candidate_plugin_sha256'] ?? '' ) === (string) ( $prior_proof['plugin_sha256']",
            self.reconcile,
        )
        self.assertIn(
            "$failed_proof['candidate_database_fingerprint'] ?? '' ) === (string) ( $prior_proof['database_fingerprint']",
            self.reconcile,
        )
        self.assertIn(
            "hash_equals( $proof_sha256, $canonical_proof_sha256 )",
            self.reconcile,
        )

        envelope = json.loads(PROOF_PATH.read_text(encoding="utf-8"))
        canonical = json.dumps(
            envelope["proof"],
            ensure_ascii=False,
            separators=(",", ":"),
            sort_keys=True,
        ).encode("utf-8")
        self.assertEqual(
            envelope["proof_sha256"], hashlib.sha256(canonical).hexdigest()
        )

    def test_marker_cas_is_checked_and_driver_aware(self) -> None:
        for marker in (
            "BEGIN IMMEDIATE TRANSACTION",
            "START TRANSACTION",
            "LIMIT 1",
            "FOR UPDATE",
            "BINARY option_value = BINARY %s",
            "option_value = %s COLLATE BINARY",
            "1 !== (int) $updated",
            "false === $committed",
            "rollback_confirmed",
            "failed authoritative readback",
        ):
            self.assertIn(marker, self.reconcile)
        self.assertIn("$database_is_sqlite", self.reconcile)
        self.assertIn("$marker_cas_transaction", self.reconcile)
        self.assertIn(
            "$marker_cas_transaction( $prior_deployment, $observed_deployment, 'compensation' )",
            self.reconcile,
        )

    def test_fence_is_refreshed_immediately_before_mutations(self) -> None:
        marker_heartbeat = self.reconcile.index("$lease = $heartbeat_lock(")
        marker_cas = self.reconcile.index(
            "$marker_cas_transaction( $observed_deployment, $prior_deployment, 'marker' )"
        )
        self.assertLess(marker_heartbeat, marker_cas)

        compensation_cas = self.reconcile.index(
            "$marker_cas_transaction( $prior_deployment, $observed_deployment, 'compensation' )"
        )
        compensation_heartbeat = self.reconcile.rfind(
            "$lease = $heartbeat_lock(", marker_cas, compensation_cas
        )
        self.assertGreater(compensation_heartbeat, marker_cas)

        receipt_write = self.reconcile.index(
            "$write_state_file( $receipt_file, $receipt )"
        )
        receipt_heartbeat = self.reconcile.rfind(
            "$lease = $heartbeat_lock(", compensation_cas, receipt_write
        )
        self.assertGreater(receipt_heartbeat, compensation_cas)
        self.assertLess(receipt_heartbeat, receipt_write)

    def test_finalize_detects_any_orphan_marker_and_fails_closed(self) -> None:
        self.assertIn("$orphaned_marker_present = false", self.finalize)
        self.assertIn(
            "array_key_exists( $orphaned_marker_key, $lock )",
            self.finalize,
        )
        self.assertIn("array( 'committed', 'cleanup_failed' )", self.finalize)
        self.assertIn("'c99_finalize_orphaned_marker_state'", self.finalize)
        self.assertIn("$orphaned_recovery && $owner_changed", self.finalize)
        self.assertIn("if ( $orphaned_recovery )", self.finalize)
        self.assertIn("true === ( $lock['committed_expected_active']", self.finalize)
        self.assertIn("false === ( $lock['committed_expected_absent']", self.finalize)

    def test_symlinks_are_rejected_for_every_recovery_identity(self) -> None:
        for value in (
            "is_link( $state_dir )",
            "is_link( $robots_path )",
            "is_link( $receipt_root )",
            "is_link( $receipt_dir )",
            "is_link( $receipt_file )",
        ):
            self.assertIn(value, self.reconcile)
            self.assertIn(value, self.finalize)
        self.assertIn("is_link( $target_dir )", self.finalize)
        self.assertIn("is_link( $plugin_path )", self.finalize)

    def test_recovery_identity_uses_authoritative_snapshot_rows(self) -> None:
        for segment in (self.status, self.reconcile):
            self.assertIn("['options']['active_plugins']", segment)
            self.assertIn("maybe_unserialize", segment)
        strict_finalize = self.finalize.split("if ( $orphaned_recovery )", 1)[1].split(
            "$preserve_orphaned_evidence = true", 1
        )[0]
        self.assertIn("['options']['active_plugins']", strict_finalize)
        self.assertIn("$current_database_version", strict_finalize)
        self.assertIn("$current_deployment", strict_finalize)
        self.assertNotIn("is_plugin_active(", strict_finalize)
        self.assertNotIn("get_option(", strict_finalize)

    def test_database_observation_is_consistent_redacted_and_marker_neutral(self) -> None:
        manifest_helper = self.bridge.split(
            "$database_snapshot_manifest = static function", 1
        )[1].split("$encrypt_database_state = static function", 1)[0]
        self.assertIn(
            "unset( $options_without_deployment_marker['complete99_last_deployment_id'] )",
            manifest_helper,
        )
        for component in (
            "options_without_deployment_marker",
            "posts",
            "postmeta",
            "seed_ids",
            "evaluation_ids",
        ):
            self.assertIn(component, manifest_helper)
        self.assertIn("$canonicalize_json_value( $component )", manifest_helper)
        self.assertIn("'manifest_sha256' => hash( 'sha256'", manifest_helper)
        self.assertNotIn("option_value' =>", manifest_helper)
        self.assertNotIn("post_content' =>", manifest_helper)
        self.assertNotIn("meta_value' =>", manifest_helper)

        consistent_capture = self.bridge.split(
            "$capture_database_state_consistent = static function", 1
        )[1].split("$database_snapshot_manifest = static function", 1)[0]
        self.assertIn(
            "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ",
            consistent_capture,
        )
        self.assertIn("START TRANSACTION WITH CONSISTENT SNAPSHOT", consistent_capture)
        self.assertIn("$capture_database_state()", consistent_capture)
        self.assertIn("ROLLBACK", consistent_capture)
        self.assertIn("COMMIT", consistent_capture)

        self.assertIn("projected_deployment_id", self.status)
        self.assertIn("projected_database_fingerprint", self.status)
        self.assertIn("database_manifest_sha256", self.status)
        self.assertIn("$verify_transactional_storage()", self.status)
        self.assertIn("'database_storage'=> $database_storage", self.status)
        self.assertLess(
            self.status.index("$verify_transactional_storage()"),
            self.status.index("$capture_database_state_consistent()"),
        )
        self.assertIn("$capture_database_state_consistent()", self.status)
        self.assertNotIn("UPDATE ", self.status)
        self.assertIn("$capture_database_state_consistent()", self.reconcile)

    def test_receipt_is_parsed_and_evidence_is_preserved(self) -> None:
        self.assertIn("json_decode( $receipt_contents, true )", self.finalize)
        self.assertIn("$receipt_keys === $expected_receipt_keys", self.finalize)
        self.assertIn("$receipt_identity_valid", self.finalize)
        self.assertIn("$preserve_orphaned_evidence = true", self.finalize)
        self.assertIn(
            "$preserve_orphaned_evidence\n\t\t\t\t\t\t? ! $wp_filesystem->exists( $state_file )",
            self.finalize,
        )

    def test_recovery_evidence_root_is_web_denied_and_read_back(self) -> None:
        helper = self.bridge.split(
            "$protect_recovery_evidence_root = static function", 1
        )[1].split("$heartbeat_state = static function", 1)[0]
        for guard in ("index.php", ".htaccess", "web.config"):
            self.assertIn(guard, helper)
        for marker in (
            "is_link( $root )",
            "$wp_filesystem->put_contents( $guard_path",
            "$wp_filesystem->get_contents( $guard_path )",
            "hash_equals( hash( 'sha256', $guard_contents )",
            "'c99_orphaned_receipt_guard_readback'",
        ):
            self.assertIn(marker, helper)
        self.assertIn(
            "$protect_recovery_evidence_root( $receipt_root )",
            self.reconcile,
        )
        self.assertIn(
            "$protect_recovery_evidence_root( $receipt_root )",
            self.finalize,
        )

    def test_normal_finalize_state_machine_remains_present(self) -> None:
        for marker in (
            "array( 'installed', 'rolled_back', 'commit_failed', 'committing' )",
            "'c99_finalize_unstabilized'",
            "'c99_finalize_database_checkpoint'",
            "'c99_finalize_robots_forward'",
            "'c99_finalize_robots_rollback'",
            "$set_state_phase( $state_dir, $deployment_id, 'committing'",
            "$set_state_phase( $state_dir, $deployment_id, 'committed'",
        ):
            self.assertIn(marker, self.finalize)


if __name__ == "__main__":
    unittest.main()
