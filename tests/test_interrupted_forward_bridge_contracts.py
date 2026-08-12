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
        cls.candidate_repair = cls.bridge.split(
            "$route_prefix . '/repair-candidate-activation'", 1
        )[1].split("$route_prefix . '/continue-activation'", 1)[0]
        cls.rollback = cls.bridge.split("$route_prefix . '/rollback'", 1)[1].split(
            "$route_prefix . '/reconcile-orphaned-rollback'", 1
        )[0]
        cls.finalize = cls.bridge.split("$route_prefix . '/finalize'", 1)[1]

    def test_single_use_reviewed_identity_markers_exist_exactly_once(self) -> None:
        markers = (
            "__C99_EXPECTED_ARTIFACT_SHA256__",
            "__C99_EXPECTED_PLUGIN_SHA256__",
            "__C99_EXPECTED_VERSION__",
            "__C99_INTERRUPTED_FORWARD_ADOPTION_SCHEMA__",
            "__C99_INTERRUPTED_FORWARD_PROOF_SHA256__",
            "__C99_INTERRUPTED_FORWARD_FINALIZED_ATTESTATION__",
            "__C99_INTERRUPTED_FORWARD_TARGET_DEPLOYMENT_ID__",
            "__C99_REVIEWED_DATABASE_FINGERPRINT__",
            "__C99_REVIEWED_DATABASE_MANIFEST_BASE64__",
            "__C99_REVIEWED_DATABASE_MANIFEST_SHA256__",
            "__C99_REVIEWED_DATABASE_STORAGE_BASE64__",
            "__C99_REVIEWED_SAFE_STATUS_BASE64__",
            "__C99_REVIEWED_SAFE_STATUS_SHA256__",
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
            "$interrupted_source_phase = $interrupted_forward_pending ? 'installed_pending_stabilization' : 'installing'",
            "array( $interrupted_source_phase )",
            "$interrupted_source_phase,\n\t\t\t\t\t\t\tfalse,\n\t\t\t\t\t\t\ttrue",
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
            "'stabilized_from_phase'                    => $interrupted_source_phase",
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

    def test_pending_stabilization_repair_is_exactly_reviewed_and_rechecked(self) -> None:
        for marker in (
            "'complete99-interrupted-forward-adoption/v4'",
            "$reviewed_safe_status_sha256",
            "hash( 'sha256', $reviewed_safe_json )",
            "new WP_REST_Request( 'POST', '/complete99-deploy/v1/' . $deployment_id . '/status' )",
            "$canonicalize_json_value( $live_safe_status ) !== $canonicalize_json_value( $reviewed_safe_status )",
            "$interrupted_forward_pending = $pending_repair_request && 'installed_pending_stabilization' === $phase",
            "true === ( $state['temp_removed'] ?? null )",
            "true === ( $state['forward_ready'] ?? null )",
            "true === ( $state['installed_active'] ?? null )",
            "'complete' === (string) ( $state['candidate_activation_phase'] ?? '' )",
            "$interrupted_config['reviewed_database_fingerprint'], (string) ( $state['candidate_database_fingerprint'] ?? '' )",
            "'stabilized_from_phase'                    => $interrupted_source_phase",
        ):
            self.assertIn(marker, self.stabilize)
        self.assertLess(
            self.stabilize.index("rest_do_request( $status_request )"),
            self.stabilize.index("$process_lock = $acquire_process_lock()"),
        )
        self.assertLess(
            self.stabilize.index("$post_claim_fingerprint"),
            self.stabilize.index("'adopted_forward_no_rollback'              => true"),
        )

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
            "'campaign_capacity_diagnostic'",
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
        self.assertIn("new \\ReflectionMethod( 'Complete99_Campaigns'", self.status)
        self.assertIn("'lifecycle_capacity_reservation'", self.status)
        self.assertIn("'public_quarantine_capacity_reservation'", self.status)
        self.assertIn("'stored_lifecycle_receipt'", self.status)
        self.assertIn("'fresh_install_empty'", self.status)
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

    def test_candidate_repair_is_irreversible_exact_and_read_back(self) -> None:
        durable_marker = "'candidate_repair_started' => true"
        source_patch = "$replacements = array("
        ddl = 'ALTER TABLE {$table_identifier} MODIFY COLUMN `external_state`'
        receipt = "'candidate_repair_receipt' => $receipt"
        self.assertIn(durable_marker, self.candidate_repair)
        self.assertIn(source_patch, self.candidate_repair)
        self.assertIn(ddl, self.candidate_repair)
        self.assertIn(receipt, self.candidate_repair)
        self.assertLess(
            self.candidate_repair.index("$acquire_worker_fence()"),
            self.candidate_repair.index(durable_marker),
        )
        self.assertLess(
            self.candidate_repair.index(durable_marker),
            self.candidate_repair.index(source_patch),
        )
        self.assertLess(
            self.candidate_repair.index(source_patch),
            self.candidate_repair.index(ddl),
        )
        self.assertLess(
            self.candidate_repair.index(ddl),
            self.candidate_repair.index(receipt),
        )
        self.assertLess(
            self.candidate_repair.index(receipt),
            self.candidate_repair.rindex("$release_worker_fence("),
        )
        self.assertIn(
            "CHARACTER SET {$column_charset} COLLATE {$column_collation} NOT NULL",
            self.candidate_repair,
        )
        self.assertIn(
            "'c99_candidate_repair_review_changed_' . $review_changed_field",
            self.candidate_repair,
        )
        self.assertIn(
            "array( 'status' => 409, 'field' => $review_changed_field )",
            self.candidate_repair,
        )
        self.assertIn(
            "$status_request->set_param( 'projected_deployment_id', $deployment_id )",
            self.candidate_repair,
        )
        self.assertLess(
            self.candidate_repair.index(
                "$status_request->set_param( 'projected_deployment_id', $deployment_id )"
            ),
            self.candidate_repair.index("$status_response = rest_do_request("),
        )
        self.assertIn("c99_rollback_candidate_repair", self.rollback)
        self.assertIn("candidate_repair_started", self.rollback)
        self.assertIn(
            "$candidate_repair_receipt_valid( $current_state, true )",
            self.finalize,
        )

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
        pending_envelope = json.loads(
            (
                ROOT
                / "docs"
                / "recovery-proofs"
                / "c99-prod-31598196288-1-v2.json"
            ).read_text(encoding="utf-8")
        )
        pending_adoption = pending_envelope["proof"]["forward_adoption"]
        pending_audit = json.loads(
            (ROOT / pending_adoption["observation_audit_path"]).read_text(
                encoding="utf-8"
            )
        )
        pending_observation = pending_audit["interrupted_forward_observation"]
        candidate_envelope = json.loads(
            (
                ROOT
                / "docs"
                / "recovery-proofs"
                / "c99-prod-31620203121-1-v4.json"
            ).read_text(encoding="utf-8")
        )
        candidate_proof = candidate_envelope["proof"]
        candidate_adoption = candidate_proof["forward_adoption"]
        candidate_repair = candidate_adoption["candidate_repair"]
        candidate_audit = json.loads(
            (ROOT / candidate_adoption["observation_audit_path"]).read_text(
                encoding="utf-8"
            )
        )
        candidate_observation = candidate_audit[
            "interrupted_forward_observation"
        ]
        candidate_baseline = candidate_proof["recovered_baseline"]
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
            "pending-stabilization-v4": {
                "expected_artifact_sha256": pending_adoption[
                    "target_artifact_sha256"
                ],
                "expected_plugin_sha256": pending_adoption[
                    "target_installed_plugin_sha256"
                ],
                "expected_version": pending_adoption["observed_version"],
                "interrupted_forward_adoption_schema": pending_adoption[
                    "schema"
                ],
                "interrupted_forward_proof_sha256": pending_envelope[
                    "proof_sha256"
                ],
                "prior_database_fingerprint": pending_envelope["proof"][
                    "prior_run"
                ]["database_fingerprint"],
                "prior_deployment_id": pending_envelope["proof"]["prior_run"][
                    "deployment_id"
                ],
                "prior_plugin_sha256": pending_envelope["proof"]["prior_run"][
                    "plugin_sha256"
                ],
                "prior_robots_sha256": pending_envelope["proof"]["prior_run"][
                    "robots_sha256"
                ],
                "prior_version": pending_envelope["proof"]["prior_run"][
                    "version"
                ],
                "reviewed_database_fingerprint": pending_adoption[
                    "observed_database_fingerprint"
                ],
                "reviewed_database_manifest": pending_adoption[
                    "observed_database_manifest"
                ],
                "reviewed_database_manifest_sha256": pending_adoption[
                    "observed_database_manifest_sha256"
                ],
                "reviewed_database_storage": pending_adoption[
                    "observed_database_storage"
                ],
                "reviewed_safe_status": pending_observation["safe_status"],
                "reviewed_safe_status_sha256": pending_observation[
                    "safe_status_sha256"
                ],
            },
            "candidate-repair-v5": {
                "candidate_plugin_after_sha256": candidate_repair[
                    "plugin_after_sha256"
                ],
                "candidate_plugin_before_sha256": candidate_repair[
                    "plugin_before_sha256"
                ],
                "candidate_repair_schema": candidate_repair["schema"],
                "candidate_source_after_sha256": candidate_repair[
                    "source_after_sha256"
                ],
                "candidate_source_before_sha256": candidate_repair[
                    "source_before_sha256"
                ],
                "expected_artifact_sha256": candidate_proof["failed_run"][
                    "artifact_sha256"
                ],
                "expected_plugin_sha256": candidate_repair[
                    "plugin_after_sha256"
                ],
                "expected_version": candidate_proof["failed_run"]["version"],
                "interrupted_forward_adoption_schema": candidate_adoption[
                    "schema"
                ],
                "interrupted_forward_proof_sha256": candidate_envelope[
                    "proof_sha256"
                ],
                "prior_database_fingerprint": candidate_baseline[
                    "database_fingerprint"
                ],
                "prior_deployment_id": candidate_baseline["deployment_id"],
                "prior_plugin_sha256": candidate_baseline["plugin_sha256"],
                "prior_robots_sha256": candidate_baseline["robots_sha256"],
                "prior_version": candidate_baseline["version"],
                "reviewed_database_fingerprint": candidate_adoption[
                    "observed_database_fingerprint"
                ],
                "reviewed_database_manifest": candidate_adoption[
                    "observed_database_manifest"
                ],
                "reviewed_database_manifest_sha256": candidate_adoption[
                    "observed_database_manifest_sha256"
                ],
                "reviewed_database_storage": candidate_adoption[
                    "observed_database_storage"
                ],
                "reviewed_safe_status": candidate_observation["safe_status"],
                "reviewed_safe_status_sha256": candidate_observation[
                    "safe_status_sha256"
                ],
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
                    if label == "candidate-repair-v5":
                        self.assertIn("/repair-candidate-activation", code)
                        self.assertIn("c99_rollback_candidate_repair", code)
                        self.assertNotIn("__C99_CANDIDATE_REPAIR_SCHEMA__", code)


if __name__ == "__main__":
    unittest.main()
