from __future__ import annotations

import hashlib
import importlib.util
import json
import shutil
import subprocess
import sys
import unittest
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
BRIDGE_PATH = ROOT / "deploy" / "temporary-bridge.php"
CAMPAIGNS_PATH = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-campaigns.php"
)
PLATFORM_PATH = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-platform.php"
)


def load_module(name: str, path: Path) -> Any:
    spec = importlib.util.spec_from_file_location(name, path)
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


DEPLOY = load_module("complete99_ops_rollback_deployer", ROOT / "scripts" / "deploy-wordpress.py")
RECOVER = load_module("complete99_ops_rollback_recover", ROOT / "scripts" / "recover-wordpress.py")
AUDIT = load_module(
    "complete99_ops_rollback_audit", ROOT / "scripts" / "validate-recovery-audit.py"
)


def manifest(version: int) -> tuple[dict[str, Any], str]:
    components = [
        "options_without_deployment_marker",
        "posts",
        "postmeta",
        "seed_ids",
        "evaluation_ids",
    ]
    if version >= 2:
        components.append("ops_tables")
    if version >= 3:
        components.append("campaign_tables")
    value: dict[str, Any] = {
        "schema": f"complete99-database-snapshot-manifest/v{version}",
        "sync_secret_existed": True,
        "sync_secret_configured": True,
    }
    for index, component in enumerate(components, start=1):
        value[f"{component}_count"] = (
            7 if component in {"ops_tables", "campaign_tables"} else index
        )
        value[f"{component}_sha256"] = format(index, "x") * 64
    canonical = json.dumps(
        value, ensure_ascii=False, separators=(",", ":"), sort_keys=True
    ).encode("utf-8")
    return value, hashlib.sha256(canonical).hexdigest()


class OpsDeploymentRollbackContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bridge = BRIDGE_PATH.read_text(encoding="utf-8")
        cls.campaigns = CAMPAIGNS_PATH.read_text(encoding="utf-8")
        cls.platform = PLATFORM_PATH.read_text(encoding="utf-8")
        cls.restore = cls.bridge.split(
            "$restore_database_state = static function", 1
        )[1].split("$auto_update_enabled = static function", 1)[0]
        cls.rollback = cls.bridge.split("$route_prefix . '/rollback'", 1)[1].split(
            "$route_prefix . '/reconcile-orphaned-rollback'", 1
        )[0]
        cls.finalize = cls.bridge.split("$route_prefix . '/finalize'", 1)[1]

    def test_snapshot_scope_is_exact_bounded_and_includes_marker(self) -> None:
        for suffix in (
            "locations",
            "memberships",
            "tasks",
            "issues",
            "commands",
            "mutation_receipts",
            "audit_events",
        ):
            self.assertIn(f"c99_ops_{suffix}", self.bridge)
        for suffix in (
            "campaigns",
            "campaign_revisions",
            "campaign_packages",
            "campaign_provider_receipts",
            "campaign_results",
            "campaign_placements",
            "campaign_event_aggregates",
        ):
            self.assertIn(f"c99_{suffix}", self.bridge)
        self.assertIn("'complete99_ops_schema_version'", self.bridge)
        self.assertIn("'complete99_campaign_schema_version'", self.bridge)
        self.assertIn("'complete99-campaign-schema/v1'", self.bridge)
        self.assertIn("ORDER BY `id` ASC LIMIT 5001", self.bridge)
        self.assertIn("5000 < count( $rows )", self.bridge)
        self.assertIn("8 * 1024 * 1024 < $total_bytes", self.bridge)
        self.assertIn("'ops_tables'=> $ops_tables", self.bridge)

    def test_manifest_v3_is_exact_and_v1_v2_remain_accepted(self) -> None:
        self.assertIn("complete99-database-snapshot-manifest/v1", self.bridge)
        self.assertIn("complete99-database-snapshot-manifest/v2", self.bridge)
        self.assertIn("complete99-database-snapshot-manifest/v3", self.bridge)
        self.assertIn("$components[] = 'ops_tables'", self.bridge)
        self.assertIn("$components[] = 'campaign_tables'", self.bridge)
        self.assertIn(
            "in_array( $component, array( 'ops_tables', 'campaign_tables' ), true )",
            self.bridge,
        )
        for script in (
            ROOT / "scripts" / "deploy-wordpress.py",
            ROOT / "scripts" / "recover-wordpress.py",
            ROOT / "scripts" / "validate-recovery-audit.py",
        ):
            source = script.read_text(encoding="utf-8")
            self.assertIn("complete99-database-snapshot-manifest/v3", source)
            self.assertIn('"campaign_tables"', source)
            self.assertIn('{"ops_tables", "campaign_tables"}', source)

        for validator in (
            lambda value, digest: RECOVER.validate_database_manifest(
                DEPLOY, value, digest, "test"
            ),
            lambda value, digest: AUDIT.validate_database_manifest(
                value, digest, "test"
            ),
        ):
            for version in (1, 2, 3):
                value, digest = manifest(version)
                validator(value, digest)

            hybrid, _ = manifest(1)
            hybrid["ops_tables_count"] = 7
            hybrid["ops_tables_sha256"] = "f" * 64
            hybrid_digest = hashlib.sha256(
                json.dumps(
                    hybrid,
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()
            with self.assertRaises(Exception):
                validator(hybrid, hybrid_digest)

            wrong_count, _ = manifest(2)
            wrong_count["ops_tables_count"] = 6
            wrong_digest = hashlib.sha256(
                json.dumps(
                    wrong_count,
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()
            with self.assertRaises(Exception):
                validator(wrong_count, wrong_digest)

            wrong_campaign_count, _ = manifest(3)
            wrong_campaign_count["campaign_tables_count"] = 6
            wrong_campaign_digest = hashlib.sha256(
                json.dumps(
                    wrong_campaign_count,
                    ensure_ascii=False,
                    separators=(",", ":"),
                    sort_keys=True,
                ).encode("utf-8")
            ).hexdigest()
            with self.assertRaises(Exception):
                validator(wrong_campaign_count, wrong_campaign_digest)

    def test_python_manifest_readers_enforce_int64_counts_and_storage_type(self) -> None:
        base_components = (
            "options_without_deployment_marker",
            "posts",
            "postmeta",
            "seed_ids",
            "evaluation_ids",
        )

        def digest(value: dict[str, Any]) -> str:
            canonical = json.dumps(
                value,
                ensure_ascii=False,
                separators=(",", ":"),
                sort_keys=True,
            ).encode("utf-8")
            return hashlib.sha256(canonical).hexdigest()

        validators = (
            lambda value, value_digest: RECOVER.validate_database_manifest(
                DEPLOY, value, value_digest, "test"
            ),
            lambda value, value_digest: AUDIT.validate_database_manifest(
                value, value_digest, "test"
            ),
        )
        for validator in validators:
            for version in (1, 2, 3):
                for component in base_components:
                    overflow, _ = manifest(version)
                    overflow[f"{component}_count"] = 9223372036854775808
                    with self.assertRaises(Exception):
                        validator(overflow, digest(overflow))

        failed = {"candidate_database_fingerprint": "c" * 64}
        prior = {
            "deployment_id": "c99-prior-release",
            "database_fingerprint": "d" * 64,
        }

        def observe(
            version: int,
            *,
            overflow_component: str | None = None,
            storage_tables: Any = 3,
        ) -> dict[str, Any]:
            value, value_digest = manifest(version)
            if overflow_component is not None:
                value[f"{overflow_component}_count"] = 9223372036854775808
                value_digest = digest(value)
            status = {
                "database_manifest": value,
                "database_manifest_sha256": value_digest,
                "database_storage": {"engine": "INNODB", "tables": storage_tables},
                "database_fingerprint": "a" * 64,
                "projected_database_fingerprint": "b" * 64,
                "projected_deployment_id": prior["deployment_id"],
            }
            return DEPLOY.observe_orphaned_rollback(
                "c99-failed-release",
                status,
                {},
                "e" * 64,
            )

        original_validator = DEPLOY.validate_orphaned_rollback_live_state
        DEPLOY.validate_orphaned_rollback_live_state = lambda *_args: (failed, prior)
        try:
            for version in (1, 2, 3):
                self.assertEqual(
                    observe(version)["database_manifest"]["schema"],
                    f"complete99-database-snapshot-manifest/v{version}",
                )
                for component in base_components:
                    with self.assertRaises(DEPLOY.DeployError):
                        observe(version, overflow_component=component)
            with self.assertRaises(DEPLOY.DeployError):
                observe(3, storage_tables=3.0)
        finally:
            DEPLOY.validate_orphaned_rollback_live_state = original_validator

        float_storage = {"engine": "INNODB", "tables": 3.0}
        with self.assertRaises(DEPLOY.DeployError):
            RECOVER.validate_transactional_storage(DEPLOY, float_storage, "test")
        with self.assertRaises(AUDIT.AuditValidationError):
            AUDIT.validate_database_storage(float_storage, "test")

    def test_first_install_detaches_before_core_transaction_and_cleans_after_readback(self) -> None:
        detach = self.restore.index("$ops_atomic_rename( $detach_pairs )")
        begin = self.restore.index("START TRANSACTION")
        readback = self.restore.index("$precommit_snapshot   = $capture_database_state()")
        commit = self.restore.index("$wpdb->query( 'COMMIT' )")
        self.assertLess(detach, begin)
        self.assertLess(begin, readback)
        self.assertLess(readback, commit)
        self.assertIn("$protected_rejoin_forward(", self.restore)
        self.assertIn("c99_ops_restore_existing_changed", self.bridge)
        self.assertIn("c99_campaign_restore_existing_changed", self.bridge)

        baseline_readback = self.rollback.index("$restored_database_snapshot")
        cleanup = self.rollback.index("$protected_cleanup_quarantine(")
        terminal = self.rollback.index("$rolled_back = $set_state_phase(")
        self.assertLess(baseline_readback, cleanup)
        self.assertLess(cleanup, terminal)
        self.assertIn("'protected_quarantine_cleaned'=> true", self.rollback)

    def test_historical_journals_authenticate_before_projection(self) -> None:
        legacy_shape = self.rollback.index("$v1_journal_keys")
        digest_check = self.rollback.index(
            "hash_equals( $baseline_fingerprint, hash( 'sha256', $journal_json ) )"
        )
        synthesis = self.rollback.index("$normalize_database_snapshot(")
        self.assertLess(legacy_shape, digest_check)
        self.assertLess(digest_check, synthesis)
        self.assertIn("$v2_journal_keys", self.rollback)
        self.assertIn("$v3_journal_keys", self.rollback)
        self.assertIn("$project_database_snapshot( $current_normalized_snapshot, $journal_generation )", self.rollback)
        self.assertIn("$historical_campaign_projection", self.rollback)
        self.assertIn(
            "array( 'rollback_forward_campaign_sha256' => $reconstructed_forward_campaign_sha256 )",
            self.rollback,
        )
        self.assertIn("c99_protected_legacy_journal_conflict", self.rollback)
        self.assertIn("$restore_database_state( $database_snapshot,", self.rollback)

    def test_retry_proof_and_residue_gates_are_deterministic(self) -> None:
        checkpoint = self.rollback.index("'rollback_forward_ops_sha256'")
        restore = self.rollback.index("$restore_database_state( $database_snapshot,")
        self.assertLess(checkpoint, restore)
        for contract in (
            "$ops_reconstruct_forward(",
            "$campaign_reconstruct_forward(",
            "$protected_rejoin_forward(",
            "c99_protected_rollback_retry_proof",
            "c99_protected_rollback_retry_conflict",
            "c99_deploy_ops_rollback_residue",
            "'ops_rollback_residue_present'",
            "'ops_rollback_residue_count'",
        ):
            self.assertIn(contract, self.bridge)
        self.assertIn("c99_finalize_ops_rollback_residue", self.finalize)
        self.assertLess(
            self.finalize.index("$ops_quarantine_residue()"),
            self.finalize.index("$removed = $preserve_orphaned_evidence"),
        )

    def test_campaign_preflight_and_combined_quarantine_fail_closed(self) -> None:
        self.assertEqual(self.bridge.count("c99_deploy_campaign_schema_drift"), 2)
        self.assertIn("$campaign_snapshot_coherent( $database_snapshot )", self.bridge)
        self.assertIn("$index  = 7", self.bridge)
        protected_rejoin = self.bridge.split(
            "$protected_rejoin_forward = static function", 1
        )[1].split("$protected_cleanup_quarantine = static function", 1)[0]
        self.assertEqual(protected_rejoin.count("$ops_atomic_rename( $pairs )"), 1)
        self.assertIn("$campaign_reconstruct_forward(", protected_rejoin)
        self.assertIn("$expected_campaign_sha256", protected_rejoin)
        self.assertIn("$campaign_snapshot_has_tables( $campaign_quarantine_after )", protected_rejoin)
        self.assertIn("$ops_atomic_rename( $detach_pairs )", self.restore)
        self.assertIn("$campaign_quarantined_count", self.restore)

    def test_cron_is_derived_not_journaled_and_reconciles_after_redeploy(self) -> None:
        capture = self.bridge.split(
            "$capture_database_state = static function", 1
        )[1].split("$database_snapshot_generation = static function", 1)[0]
        self.assertNotIn("'cron'", capture)

        prepare = self.campaigns.split("public static function prepare_schema", 1)[
            1
        ].split("public static function install", 1)[0]
        install = self.campaigns.split("public static function install", 1)[1].split(
            "public static function assert_invariants", 1
        )[0]
        for migration_section in (prepare, install):
            self.assertNotIn("wp_schedule_single_event", migration_section)
            self.assertNotIn("wp_unschedule_event", migration_section)
            self.assertNotIn("wp_clear_scheduled_hook", migration_section)

        self.assertIn(
            "add_action( 'init', array( __CLASS__, 'ensure_reconcile_trigger' ), 45 )",
            self.campaigns,
        )
        self.assertIn("Complete99_Campaigns::begin_deactivation_suspension();", self.platform)
        self.assertIn("Complete99_Campaigns::complete_deactivation_suspension( $token );", self.platform)
        reconcile = self.campaigns.split(
            "public static function reconcile_schedules", 1
        )[1].split("private static function reconcile_campaign_schedule", 1)[0]
        bounded_projection = self.campaigns.split(
            "private static function bounded_actionable_campaign_rows", 1
        )[1].split(
            "private static function ensure_public_quarantine_zero_membership_receipt",
            1,
        )[0]
        self.assertIn("$cursor_id = 0", bounded_projection)
        self.assertIn("c.id>%d", bounded_projection)
        scheduler = self.campaigns.split(
            "private static function schedule_next_reconcile_trigger", 1
        )[1].split("private static function enqueue_reconcile_trigger", 1)[0]
        for projection_kind in (
            "cleanup_due",
            "cleanup_future",
            "schedule_due",
            "schedule_future",
        ):
            self.assertIn(
                f"bounded_actionable_campaign_rows( '{projection_kind}'",
                scheduler,
            )
        for projection_kind in ("cleanup_due", "schedule_due"):
            self.assertIn(
                f"bounded_actionable_campaign_rows( '{projection_kind}'",
                reconcile,
            )
        self.assertIn("RECONCILE_MAX_BATCHES", reconcile)
        self.assertIn("RECONCILE_TIME_BUDGET", reconcile)
        self.assertIn("schedule_next_reconcile_trigger", reconcile)
        self.assertIn(
            "array( (string) $state['campaignId'], (int) ( $state['runtime']['scheduledVersion'] ?? 0 ), (string) ( $state['runtime']['scheduleDigest'] ?? '' ) )",
            self.campaigns,
        )
        self.assertIn(
            "wp_next_scheduled( 'complete99_campaign_activate', $args )",
            self.campaigns,
        )
        self.assertIn(
            "wp_next_scheduled( 'complete99_campaign_expire', $args )",
            self.campaigns,
        )

    def test_every_runtime_checkpoint_fails_closed_on_ops_campaign_or_science_drift(self) -> None:
        self.assertEqual(
            self.bridge.count("class_exists( 'Complete99_Ops', false )"), 5
        )
        self.assertEqual(
            self.bridge.count("method_exists( 'Complete99_Ops', 'assert_invariants' )"),
            5,
        )
        self.assertEqual(self.bridge.count("Complete99_Ops::assert_invariants();"), 4)
        self.assertEqual(
            self.bridge.count("array( 'Complete99_Ops', 'assert_invariants' )"), 1
        )
        self.assertEqual(
            self.bridge.count("class_exists( 'Complete99_Campaigns', false )"), 6
        )
        self.assertEqual(
            self.bridge.count(
                "method_exists( 'Complete99_Campaigns', 'assert_invariants' )"
            ),
            6,
        )
        self.assertEqual(
            self.bridge.count("Complete99_Campaigns::assert_invariants();"), 6
        )
        self.assertEqual(
            self.bridge.count("array( 'Complete99_Campaigns', 'assert_invariants' )"),
            1,
        )
        self.assertEqual(
            self.bridge.count("class_exists( 'Complete99_Culinary_Science', false )"),
            5,
        )
        self.assertEqual(
            self.bridge.count(
                "method_exists( 'Complete99_Culinary_Science', 'assert_invariants' )"
            ),
            5,
        )
        self.assertEqual(
            self.bridge.count("Complete99_Culinary_Science::assert_invariants();"),
            4,
        )
        self.assertEqual(
            self.bridge.count(
                "array( 'Complete99_Culinary_Science', 'assert_invariants' )"
            ),
            1,
        )

        status = self.bridge.split("$route_prefix . '/status'", 1)[1].split(
            "$route_prefix . '/attest-interrupted-finalized'", 1
        )[0]
        attestation = self.bridge.split(
            "$route_prefix . '/attest-interrupted-finalized'", 1
        )[1].split("$route_prefix . '/stabilize'", 1)[0]
        stabilize = self.bridge.split("$route_prefix . '/stabilize'", 1)[1].split(
            "$route_prefix . '/configure-sync'", 1
        )[0]
        for segment in (status, attestation, stabilize, self.finalize):
            self.assertIn("class_exists( 'Complete99_Ops', false )", segment)
            self.assertIn(
                "method_exists( 'Complete99_Ops', 'assert_invariants' )", segment
            )
            self.assertIn("class_exists( 'Complete99_Campaigns', false )", segment)
            self.assertIn(
                "method_exists( 'Complete99_Campaigns', 'assert_invariants' )",
                segment,
            )
            self.assertIn(
                "class_exists( 'Complete99_Culinary_Science', false )", segment
            )
            self.assertIn(
                "method_exists( 'Complete99_Culinary_Science', 'assert_invariants' )",
                segment,
            )
        self.assertIn("array( 'Complete99_Ops', 'assert_invariants' )", status)
        self.assertIn("array( 'Complete99_Campaigns', 'assert_invariants' )", status)
        self.assertIn(
            "array( 'Complete99_Culinary_Science', 'assert_invariants' )", status
        )
        self.assertIn("call_user_func( $callback );", status)
        for segment in (attestation, stabilize, self.finalize):
            self.assertIn("Complete99_Ops::assert_invariants();", segment)
            self.assertIn("Complete99_Campaigns::assert_invariants();", segment)
            self.assertIn(
                "Complete99_Culinary_Science::assert_invariants();", segment
            )

        self.assertGreaterEqual(
            stabilize.count("Complete99_Ops::assert_invariants();"), 2
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_lifecycle_reservation_is_authenticated_only_in_v3_and_round_trips_exactly(self) -> None:
        validator = self.bridge.split(
            "$campaign_lifecycle_reservation_valid = static function", 1
        )[1].split("$campaign_snapshot_coherent = static function", 1)[0]
        validator = "$validate = static function" + validator
        script = r"""
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
__VALIDATOR__
function row_for($state,$generation=7,$autoload='no'){$value=array('changedAt'=>'2026-08-12T00:00:00Z','generation'=>$generation,'schemaVersion'=>'complete99-campaign-lifecycle-reservation/v1','state'=>$state);return array('option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_LINE_TERMINATORS),'autoload'=>$autoload);}
$out=array();foreach(array('active','suspending','inactive')as$state){$out[$state]=$validate(row_for($state));}$out['missing']=$validate(null);$out['badState']=$validate(row_for('bogus'));$out['badGeneration']=$validate(row_for('active',0));$out['autoload']=$validate(row_for('active',7,'yes'));$row=row_for('active');$row['option_value'].=' ';$out['noncanonical']=$validate($row);$row=row_for('active');$decoded=json_decode($row['option_value'],true);$row['option_value']=json_encode(array_reverse($decoded,true));$out['order']=$validate($row);echo json_encode($out,JSON_THROW_ON_ERROR);
""".replace("__VALIDATOR__", validator)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["active"], result)
        self.assertTrue(result["suspending"], result)
        self.assertTrue(result["inactive"], result)
        for key in ("missing", "badState", "badGeneration", "autoload", "noncanonical", "order"):
            self.assertFalse(result[key], (key, result))

        capture = self.bridge.split("$capture_database_state = static function", 1)[1].split(
            "/**\n\t\t * Drain any mutation", 1
        )[0]
        self.assertIn("LIMIT 2", capture)
        self.assertIn("campaign_lifecycle_reservation_cardinality", capture)
        self.assertIn("|| is_array( $campaign_lifecycle_reservation )", capture)
        self.assertIn("$option_names[] = 'complete99_campaign_lifecycle_reservation_v1';", capture)
        coherent = self.bridge.split("$campaign_snapshot_coherent = static function", 1)[1].split(
            "$normalize_database_snapshot = static function", 1
        )[0]
        self.assertIn("3 !== $generation", coherent)
        self.assertIn("$campaign_lifecycle_reservation_valid( $lifecycle_reservation )", coherent)
        self.assertIn("! array_key_exists( 'complete99_campaign_lifecycle_reservation_v1'", coherent)
        normalized = self.bridge.split("$normalize_database_snapshot = static function", 1)[1].split(
            "$project_database_snapshot = static function", 1
        )[0]
        projected = self.bridge.split("$project_database_snapshot = static function", 1)[1].split(
            "$database_snapshot_manifest = static function", 1
        )[0]
        self.assertGreaterEqual(normalized.count("'complete99_campaign_lifecycle_reservation_v1'] = null"), 2)
        self.assertIn("$projected['options']['complete99_campaign_lifecycle_reservation_v1']", projected)
        rollback = self.rollback
        self.assertIn("array_splice( $v3_option_keys, 6, 0, array( 'complete99_campaign_lifecycle_reservation_v1' ) )", rollback)
        self.assertNotIn("complete99_campaign_lifecycle_reservation_v1", rollback.split("$v1_option_keys = array(", 1)[1].split("$v2_option_keys =", 1)[0])
        self.assertIn("complete99_campaign_lifecycle_reservation_v1", rollback.split("$v3_option_keys =", 1)[1])
        lock_order = self.bridge.split("$lifecycle_role_protocol", 1)[1].split("$acquire_lock =", 1)[0]
        self.assertLess(lock_order.index("$lifecycle_acquired"), lock_order.index("$acquired ="))
        release = lock_order.split("$release_worker_fence = static function", 1)[1]
        self.assertLess(release.index("$released ="), release.index("$lifecycle_released ="))


if __name__ == "__main__":
    unittest.main()
