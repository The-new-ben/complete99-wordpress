from __future__ import annotations

import hashlib
import json
import re
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BRIDGE_PATH = ROOT / "deploy" / "temporary-bridge.php"
CAMPAIGN_PATH = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-campaigns.php"
)


class DeploymentWorkerFenceContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bridge = BRIDGE_PATH.read_text(encoding="utf-8")
        cls.campaign = CAMPAIGN_PATH.read_text(encoding="utf-8")
        cls.run_section = cls._section(
            "$route_prefix . '/run'", "$route_prefix . '/continue-activation'"
        )
        cls.continuation = cls._section(
            "$route_prefix . '/continue-activation'", "$route_prefix . '/rollback'"
        )
        cls.rollback = cls._section(
            "$route_prefix . '/rollback'",
            "$route_prefix . '/reconcile-orphaned-rollback'",
        )
        cls.orphan = cls._section(
            "$route_prefix . '/reconcile-orphaned-rollback'",
            "$route_prefix . '/finalize'",
        )
        cls.finalize = cls.bridge.split("$route_prefix . '/finalize'", 1)[1]

    @classmethod
    def _section(cls, start: str, end: str) -> str:
        return cls.bridge.split(start, 1)[1].split(end, 1)[0]

    def test_template_lints(self) -> None:
        result = subprocess.run(
            ["php", "-l", str(BRIDGE_PATH)],
            cwd=ROOT,
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)

    def test_bridge_and_campaign_share_one_exact_fence_identity(self) -> None:
        protocol = "complete99-campaign-worker-fence/v1"
        expected_name = "c99_campaign_worker_" + hashlib.sha256(
            protocol.encode("ascii")
        ).hexdigest()[:40]
        self.assertIn(f"WORKER_FENCE_PROTOCOL = '{protocol}'", self.campaign)
        self.assertIn(f"$worker_fence_protocol = '{protocol}'", self.bridge)
        self.assertIn("'c99_campaign_worker_' . substr( hash( 'sha256'", self.bridge)
        self.assertEqual(60, len(expected_name))

    def test_bridge_retains_the_campaign_database_boundary_in_lock_order(self) -> None:
        capacity_name = "c99_campaign_slot_" + hashlib.sha256(
            b"rollback-capacity"
        ).hexdigest()[:40]
        helper = self.bridge.split("$acquire_worker_fence = static function", 1)[1].split(
            "$release_worker_fence = static function", 1
        )[0]
        lifecycle = helper.index("$lifecycle_acquired =")
        worker = helper.index("$acquired =", lifecycle)
        capacity = helper.index("$capacity_acquired =", worker)
        reservation_readback = helper.index("$after = $deploy_reservation_exists();")
        self.assertLess(lifecycle, worker)
        self.assertLess(worker, capacity)
        self.assertLess(capacity, reservation_readback)
        self.assertIn("hash( 'sha256', 'rollback-capacity' )", self.bridge)
        self.assertIn(
            "AUTHORITY_LOCK_ORDER = "
            "'deploy-reservation>lifecycle-role>worker-execution>"
            "authority-source-row>rollback-capacity>cron'",
            self.campaign,
        )
        self.assertIn(
            "$name = 'c99_campaign_slot_' . substr( hash( 'sha256', "
            "'rollback-capacity' ), 0, 40 );",
            self.campaign,
        )
        self.assertEqual(58, len(capacity_name))
        self.assertIn("c99_rollback_capacity_busy", helper)

        release = self.bridge.split("$release_worker_fence = static function", 1)[1].split(
            "$acquire_lock = static function", 1
        )[0]
        capacity_release = release.index("$capacity_released =")
        worker_release = release.index("$released =", capacity_release)
        lifecycle_release = release.index("$lifecycle_released =", worker_release)
        self.assertLess(capacity_release, worker_release)
        self.assertLess(worker_release, lifecycle_release)

    def test_deployment_reservation_precedes_and_survives_fence_acquisition(self) -> None:
        helper = self.bridge.split("$acquire_worker_fence = static function", 1)[1].split(
            "$release_worker_fence = static function", 1
        )[0]
        before = helper.index("$before = $deploy_reservation_exists();")
        acquire = helper.index("SELECT GET_LOCK(%s, %d)")
        after = helper.index("$after = $deploy_reservation_exists();")
        self.assertLess(before, acquire)
        self.assertLess(acquire, after)
        self.assertIn("true !== $before", helper)
        self.assertIn("true !== $after", helper)
        self.assertIn("SELECT RELEASE_LOCK(%s)", helper)
        self.assertIn("c99_worker_fence_reservation_lost", helper)

    def test_run_holds_fence_from_claim_through_all_campaign_and_file_mutation(self) -> None:
        claim = self.run_section.index("$claim_lock(")
        acquire = self.run_section.index("$acquire_worker_fence()")
        baseline = self.run_section.index("$capture_quiescent_database_state()")
        install = self.run_section.index("$upgrader->install(")
        release = self.run_section.rindex("$release_worker_fence(")
        self.assertLess(claim, acquire)
        self.assertLess(acquire, baseline)
        self.assertLess(baseline, install)
        self.assertLess(install, release)
        self.assertIn("$release_lock( $deployment_id, $lock );", self.run_section)

    def test_candidate_activation_is_a_fresh_request_authenticated_handoff(self) -> None:
        self.assertIn("'candidate_activation_pending'", self.run_section)
        self.assertNotIn("activate_plugin( $config['plugin_file'] )", self.run_section)
        continuation = self.continuation
        for token in (
            "$acquire_process_lock()",
            "$acquire_worker_fence()",
            "$read_lock( true )",
            "candidate_activation_pending",
            "installed_plugin_sha256",
            "Complete99_Platform::recover_active_upgrade()",
            "activate_plugin( $config['plugin_file'] )",
            "$core_plugin_active_persisted( $config['plugin_file'] )",
            "$decrypt_database_state( $state['database_journal'] ?? array() )",
            "$campaign_snapshot_coherent( $journal )",
            "$campaign_snapshot_coherent( $snapshot )",
            "candidate_database_fingerprint",
            "candidate_activation_complete",
            "installed_pending_stabilization",
        ):
            self.assertIn(token, continuation)
        self.assertLess(
            continuation.index("$acquire_worker_fence()"),
            continuation.index("$decrypt_database_state( $state['database_journal'] ?? array() )"),
        )
        self.assertLess(
            continuation.index("$decrypt_database_state( $state['database_journal'] ?? array() )"),
            continuation.index("Complete99_Platform::recover_active_upgrade()"),
        )
        self.assertIn("is_wp_error( $snapshot ) || ! is_array( $snapshot )", continuation)
        self.assertIn("hash_equals( (string) $state['candidate_database_fingerprint']", continuation)
        self.assertLess(
            continuation.index("candidate_activation_complete"),
            continuation.index("installed_pending_stabilization"),
        )
        for status_field in (
            "candidate_activation_required",
            "candidate_activation_phase",
            "candidate_activation_completed_at",
            "candidate_database_fingerprint",
            "candidate_requested_active",
            "candidate_prior_active",
        ):
            self.assertIn(f"'{status_field}'", self.bridge)

    def test_deployment_id_and_core_activation_truth_are_exact(self) -> None:
        self.assertIn("/\\A[A-Za-z0-9._-]{8,96}\\z/", self.bridge)
        self.assertIn("SELECT option_id,option_value FROM {$wpdb->options}", self.bridge)
        self.assertIn("ORDER BY option_id ASC LIMIT 2", self.bridge)
        self.assertIn("maybe_unserialize", self.bridge)

    def test_deployment_id_validator_executes_dotted_and_exact_boundaries(self) -> None:
        helper = self.bridge.split("$deployment_id_valid = static function", 1)[1].split("};", 1)[0]
        match = re.search(r"preg_match\(\s*'([^']+)'", helper)
        self.assertIsNotNone(match, helper)
        pattern = match.group(1)
        php = (
            "$p=" + repr(pattern).replace('\\\\', '\\') + ";"
            "$v=['deploy.01',str_repeat('a',96),str_repeat('a',97),\"deploy.01\\n\"];"
            "echo json_encode(array_map(static fn($x)=>1===preg_match($p,$x),$v),JSON_THROW_ON_ERROR);"
        )
        result = subprocess.run(
            ["php", "-r", php], cwd=ROOT, check=True, capture_output=True, text=True
        )
        self.assertEqual([True, True, False, False], json.loads(result.stdout))

    def test_rollback_recovery_and_finalize_hold_fence_across_mutation(self) -> None:
        cases = (
            (self.rollback, "$restore_database_state(", "$purge_caches()"),
            (self.orphan, "$marker_cas_transaction(", "$capture_database_state_consistent()"),
            (self.finalize, "$release_lock( $deployment_id, $lease )", "$purge_caches()"),
        )
        for section, first_mutation, later_mutation in cases:
            with self.subTest(first_mutation=first_mutation):
                claim = section.index("$claim_lock(")
                acquire = section.index("$acquire_worker_fence()")
                mutation = section.index(first_mutation)
                release = section.rindex("$release_worker_fence(")
                self.assertLess(claim, acquire)
                self.assertLess(acquire, mutation)
                self.assertLess(section.index(later_mutation), release)
                self.assertIn(
                    "is_array( $deployment_worker_fence ) ? $release_worker_fence",
                    section,
                )

    def test_release_failure_overrides_success_before_process_lock_release(self) -> None:
        for section in (self.run_section, self.rollback, self.orphan, self.finalize):
            with self.subTest(route=section[:40]):
                finally_block = section.rsplit("} finally {", 1)[1]
                worker_release = finally_block.index("$release_worker_fence(")
                process_release = finally_block.index(
                    "$release_process_lock( $process_lock );"
                )
                release_check = finally_block.index(
                    "is_wp_error( $worker_fence_release )"
                )
                self.assertLess(worker_release, process_release)
                self.assertLess(process_release, release_check)
                self.assertIn("return $worker_fence_release;", finally_block)

    def test_fault_model_never_allows_worker_and_deployment_mutation_together(self) -> None:
        def acquire(
            reservation_before: bool,
            worker_running: bool,
            database_transaction_running: bool,
            reservation_after: bool,
        ):
            if not reservation_before:
                return "missing", worker_running, database_transaction_running
            if worker_running:
                return "worker-busy", worker_running, database_transaction_running
            if database_transaction_running:
                return "database-busy", False, database_transaction_running
            if not reservation_after:
                return "lost", False, False
            return "owned", False, False

        vectors = (
            (False, False, False, False, "missing"),
            (True, True, False, True, "worker-busy"),
            (True, False, True, True, "database-busy"),
            (True, False, False, False, "lost"),
            (True, False, False, True, "owned"),
        )
        for before, worker, database, after, expected in vectors:
            with self.subTest(before=before, worker=worker, database=database, after=after):
                outcome, worker_still_running, database_still_running = acquire(
                    before, worker, database, after
                )
                self.assertEqual(expected, outcome)
                if outcome == "owned":
                    self.assertFalse(worker_still_running)
                    self.assertFalse(database_still_running)


if __name__ == "__main__":
    unittest.main()
