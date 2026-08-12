from __future__ import annotations

import importlib.util
import hashlib
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
BRIDGE_PATH = ROOT / "deploy" / "temporary-bridge.php"


def load_module(name: str, path: Path) -> Any:
    spec = importlib.util.spec_from_file_location(name, path)
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


DEPLOY = load_module(
    "complete99_interrupted_forward_bridge_deployer",
    ROOT / "scripts" / "deploy-wordpress.py",
)


class InterruptedForwardBridgeContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bridge = BRIDGE_PATH.read_text(encoding="utf-8")
        cls.status = cls.bridge.split("$route_prefix . '/status'", 1)[1].split(
            "$route_prefix . '/attest-interrupted-finalized'", 1
        )[0]
        cls.attestation = cls.bridge.split(
            "$route_prefix . '/attest-interrupted-finalized'", 1
        )[1].split("$route_prefix . '/stabilize'", 1)[0]
        cls.stabilize = cls.bridge.split("$route_prefix . '/stabilize'", 1)[1].split(
            "$route_prefix . '/configure-sync'", 1
        )[0]
        cls.rollback = cls.bridge.split("$route_prefix . '/rollback'", 1)[1].split(
            "$route_prefix . '/reconcile-orphaned-rollback'", 1
        )[0]
        cls.finalize = cls.bridge.split("$route_prefix . '/finalize'", 1)[1]

    def test_single_use_reviewed_identity_markers_exist_exactly_once(self) -> None:
        markers = (
            "__C99_EXPECTED_ARTIFACT_SHA256__",
            "__C99_EXPECTED_PLUGIN_SHA256__",
            "__C99_EXPECTED_VERSION__",
            "__C99_INTERRUPTED_FORWARD_PROOF_SHA256__",
            "__C99_INTERRUPTED_FORWARD_FINALIZED_ATTESTATION__",
            "__C99_INTERRUPTED_FORWARD_TARGET_DEPLOYMENT_ID__",
            "__C99_REVIEWED_DATABASE_FINGERPRINT__",
            "__C99_REVIEWED_DATABASE_MANIFEST_BASE64__",
            "__C99_REVIEWED_DATABASE_MANIFEST_SHA256__",
            "__C99_REVIEWED_DATABASE_STORAGE_BASE64__",
            "__C99_PRIOR_DATABASE_FINGERPRINT__",
            "__C99_PRIOR_PLUGIN_SHA256__",
            "__C99_PRIOR_DEPLOYMENT_ID__",
            "__C99_PRIOR_VERSION__",
            "__C99_PRIOR_ROBOTS_SHA256__",
        )
        for marker in markers:
            with self.subTest(marker=marker):
                self.assertEqual(self.bridge.count(marker), 1)

    def test_finalized_attestation_is_fresh_probe_proof_only_and_exact(self) -> None:
        for marker in (
            "array( 'deployment_id', 'interrupted_forward_proof_sha256', 'token' )",
            "true === ( $interrupted['finalized_attestation_enabled'] ?? null )",
            "$probe_id === (string) ( $probe_lock['deployment_id'] ?? '' )",
            "'reserved' === (string) ( $probe_lock['phase'] ?? '' )",
            "max( 0, time() - $probe_updated_at ) < (int) $config['recovery_lease_seconds']",
            "$capture_database_state_consistent()",
            "$canonicalize_json_value( $expected_manifest )",
            "$verify_transactional_storage()",
            "$managed_robots_contents()",
            "$post_probe_lock !== $probe_lock",
            "'complete99-interrupted-forward-finalized-attestation/v1'",
            "'target_state_absent'",
            "'target_artifacts_absent'",
        ):
            self.assertIn(marker, self.attestation)
        self.assertNotIn("$claim_lock(", self.attestation)
        self.assertNotIn("$set_state_phase(", self.attestation)

    def test_terminal_cleanup_residue_is_allowlisted_and_twice_attested(self) -> None:
        for marker in (
            "$adopted_forward_cleanup_residue",
            "( ! $state_exists || ! $state_backup_intact )",
            "if ( $state_exists && ! $adopted_forward_cleanup_residue )",
            "$state_exists = false",
            "'post_cleanup_residue_lease_adoption'",
            "'pre_cleanup_residue_removal'",
            "array( 'state.json', 'plugin', 'robots.prior-live' )",
            "new RecursiveDirectoryIterator",
            "! $residue_entry->isLink()",
            "$cleanup_residue",
            "$heartbeat_lock(",
            "'cleanup_failed'",
        ):
            self.assertIn(marker, self.finalize)
        self.assertLess(
            self.finalize.index("'pre_cleanup_residue_removal'"),
            self.finalize.index("$removed = $preserve_orphaned_evidence"),
        )

    def test_installing_adoption_is_proof_gated_stale_and_phase_exact(self) -> None:
        for marker in (
            "$request->get_param( 'interrupted_forward_proof_sha256' )",
            "$interrupted_forward = 'installing' === $phase",
            "'c99_stabilize_interrupted_proof'",
            "'c99_stabilize_interrupted_lease'",
            "$lock_age < (int) $config['recovery_lease_seconds']",
            "array( 'installing' )",
            "'installing',\n\t\t\t\t\t\t\tfalse,\n\t\t\t\t\t\t\ttrue",
        ):
            self.assertIn(marker, self.stabilize)
        self.assertIn(
            "array( 'installed', 'installed_pending_stabilization', 'installed_pending_cleanup' )",
            self.stabilize,
        )

    def test_baseline_and_current_proofs_fail_closed(self) -> None:
        for marker in (
            "$decrypt_database_state( $state['database_journal'] ?? array() )",
            "$interrupted_baseline_snapshot['sync_secret_existed']",
            "$interrupted_baseline_snapshot['sync_secret_configured']",
            "$capture_database_state_consistent()",
            "$database_snapshot_manifest_valid(",
            "$verify_transactional_storage()",
            "$current_database_snapshot['sync_secret_existed']",
            "$current_database_snapshot['sync_secret_configured']",
            "Complete99_Content::assert_migration_invariants()",
            "Complete99_Settings::assert_defaults()",
            "Complete99_Platform::assert_evaluation_catalog_invariants()",
            "Complete99_Platform::migration_failed()",
            "$directory_sha256( $target_dir )",
            "$directory_sha256( $backup_dir )",
            "hash( 'sha256', $managed_robots_contents() )",
            "hash_file( 'sha256', $interrupted_temp_path )",
            "'c99_stabilize_interrupted_post_claim_database'",
            "'c99_stabilize_interrupted_post_claim_plugin'",
            "'c99_stabilize_interrupted_post_claim_robots'",
            "$interrupted_config['reviewed_database_storage']['engine']",
            "$interrupted_config['reviewed_database_storage']['tables']",
        ):
            self.assertIn(marker, self.stabilize)

    def test_prior_live_robots_is_optional_but_strict_when_present(self) -> None:
        self.assertIn("$prior_live_robots", self.stabilize)
        self.assertIn("file_exists( $prior_live_robots )", self.stabilize)
        self.assertIn("is_link( $prior_live_robots )", self.stabilize)
        self.assertIn("hash_file( 'sha256', $prior_live_robots )", self.stabilize)
        self.assertNotIn(
            "array( 'robots.forward', 'robots.rollback-prior', 'robots.prior-live' )",
            self.stabilize,
        )

    def test_adoption_checkpoint_and_response_are_auditable(self) -> None:
        for marker in (
            "'adopted_forward_no_rollback'              => true",
            "'stabilized_from_phase'                    => 'installing'",
            "'post_install_database_fingerprint'        => $post_claim_fingerprint",
            "'installed_plugin_sha256'                  => $installed_plugin_sha256",
            "'interrupted_forward_proof_sha256'         => $interrupted_config['proof_sha256']",
            "'interrupted_forward_database_manifest_sha256'=> $post_claim_manifest_sha256",
            "'database_manifest_sha256'         => $post_claim_manifest_sha256",
            "'database_manifest'                => $post_claim_manifest",
            "'database_storage'                 => $post_claim_storage",
            "array( 'deferred_to_finalize' => true )",
            "'c99_stabilize_interrupted_idempotency_proof'",
        ):
            self.assertIn(marker, self.stabilize)

    def test_rollback_categorically_refuses_adopted_forward(self) -> None:
        refusal = "if ( ! empty( $state['adopted_forward_no_rollback'] ) )"
        self.assertIn(refusal, self.rollback)
        self.assertIn("'c99_rollback_adopted_forward'", self.rollback)
        self.assertLess(
            self.rollback.index(refusal),
            self.rollback.index("$interrupted_phase = in_array("),
        )
        self.assertLess(
            self.rollback.index(refusal),
            self.rollback.index("$lease = $claim_lock("),
        )

    def test_status_exposes_safe_pre_adoption_observation(self) -> None:
        for field in (
            "'runtime_loaded'",
            "'runtime_version'",
            "'migration_failed'",
            "'migration_invariant_checks'",
            "'migration_invariants_valid'",
            "'campaign_operational'",
            "'campaign_lifecycle'",
            "'baseline_database_journal_valid'",
            "'baseline_sync_secret_existed'",
            "'baseline_sync_configured'",
            "'no_rollback_artifacts'",
            "'interrupted_forward_candidate'",
            "'adopted_forward_no_rollback'",
            "'interrupted_forward_proof_sha256'",
            "'interrupted_forward_database_manifest_sha256'",
        ):
            self.assertIn(field, self.status)
        self.assertIn("$consistent_database_status = '' !== $projected_deployment_id", self.status)
        self.assertIn("|| $interrupted_installing_status", self.status)
        self.assertIn("|| $interrupted_adopted_status", self.status)
        self.assertIn("Complete99_Ops::status_snapshot()", self.status)
        self.assertIn("$campaign_lifecycle_reservation_valid( $lifecycle_row )", self.status)
        self.assertIn("array_fill_keys( array_keys( $migration_invariant_callbacks ), false )", self.status)
        self.assertIn(
            "in_array( $phase, array( 'installed', 'committing', 'commit_failed', 'committed', 'cleanup_failed' ), true )",
            self.status,
        )

    def test_finalize_re_attests_adopted_state_and_lock_before_cleanup(self) -> None:
        for marker in (
            "$validate_adopted_forward_finalize = static function",
            "'post_lease_adoption'",
            "'post_lock_lease_adoption'",
            "'pre_cleanup'",
            "'pre_lock_release'",
            "'c99_finalize_interrupted_forward_attestation'",
            "$capture_database_state_consistent()",
            "$database_snapshot_manifest_valid(",
            "$verify_transactional_storage()",
            "$directory_sha256( $target_dir )",
            "Complete99_Platform::migration_failed()",
            "Complete99_Content::assert_migration_invariants()",
            "$current_state['interrupted_forward_database_manifest']",
            "$current_state['interrupted_forward_database_storage']",
        ):
            self.assertIn(marker, self.finalize)
        self.assertLess(
            self.finalize.rindex("'pre_cleanup'"),
            self.finalize.index("$removed = $preserve_orphaned_evidence"),
        )
        self.assertLess(
            self.finalize.rindex("'pre_lock_release'"),
            self.finalize.index("$release_lock( $deployment_id, $lease )"),
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required for bridge lint")
    def test_bridge_template_is_valid_php(self) -> None:
        result = subprocess.run(
            ["php", "-l", str(BRIDGE_PATH)],
            cwd=ROOT,
            capture_output=True,
            text=True,
            check=False,
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for rendered bridge lint")
    def test_default_v1_and_v2_rendered_bridges_are_valid_php(self) -> None:
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
        manifest_sha256 = hashlib.sha256(
            json.dumps(
                manifest,
                ensure_ascii=False,
                separators=(",", ":"),
                sort_keys=True,
            ).encode("utf-8")
        ).hexdigest()
        identities = {
            "expected_artifact_sha256": "1" * 64,
            "expected_plugin_sha256": "2" * 64,
            "expected_version": "1.18.0",
            "interrupted_forward_proof_sha256": "3" * 64,
            "reviewed_database_fingerprint": "4" * 64,
            "reviewed_database_manifest_sha256": "5" * 64,
            "prior_database_fingerprint": "6" * 64,
            "prior_plugin_sha256": "7" * 64,
            "prior_deployment_id": "c99-prod-prior-1",
            "prior_version": "1.17.0",
            "prior_robots_sha256": "8" * 64,
        }
        cases = {
            "default": {},
            "v1": identities,
            "v2": {
                **identities,
                "reviewed_database_storage": {
                    "engine": "INNODB",
                    "tables": 3,
                },
            },
            "finalized-attestation": {
                **identities,
                "interrupted_forward_finalized_attestation": True,
                "interrupted_forward_target_deployment_id": "c99-prod-failed-1",
                "reviewed_database_manifest": manifest,
                "reviewed_database_manifest_sha256": manifest_sha256,
                "reviewed_database_storage": {
                    "engine": "INNODB",
                    "tables": 3,
                },
            },
        }
        with tempfile.TemporaryDirectory() as temporary:
            for label, fields in cases.items():
                with self.subTest(label=label):
                    code = DEPLOY.render_bridge(
                        "test-token",
                        "c99-test-deployment-1",
                        8 * 1024 * 1024,
                        True,
                        target_host="localhost",
                        allowed_hosts={"localhost"},
                        **fields,
                    )
                    rendered = Path(temporary) / f"{label}.php"
                    rendered.write_text("<?php\n" + code, encoding="utf-8")
                    result = subprocess.run(
                        ["php", "-l", str(rendered)],
                        cwd=ROOT,
                        capture_output=True,
                        text=True,
                        check=False,
                    )
                    self.assertEqual(
                        result.returncode,
                        0,
                        result.stdout + result.stderr,
                    )


if __name__ == "__main__":
    unittest.main()
