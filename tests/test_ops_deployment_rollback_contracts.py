from __future__ import annotations

import hashlib
import importlib.util
import json
import sys
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
    if version == 2:
        components.append("ops_tables")
    value: dict[str, Any] = {
        "schema": f"complete99-database-snapshot-manifest/v{version}",
        "sync_secret_existed": True,
        "sync_secret_configured": True,
    }
    for index, component in enumerate(components, start=1):
        value[f"{component}_count"] = 7 if component == "ops_tables" else index
        value[f"{component}_sha256"] = format(index, "x") * 64
    canonical = json.dumps(
        value, ensure_ascii=False, separators=(",", ":"), sort_keys=True
    ).encode("utf-8")
    return value, hashlib.sha256(canonical).hexdigest()


class OpsDeploymentRollbackContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bridge = BRIDGE_PATH.read_text(encoding="utf-8")
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
        self.assertIn("'complete99_ops_schema_version'", self.bridge)
        self.assertIn("ORDER BY `id` ASC LIMIT 5001", self.bridge)
        self.assertIn("5000 < count( $rows )", self.bridge)
        self.assertIn("8 * 1024 * 1024 < $total_bytes", self.bridge)
        self.assertIn("'ops_tables'=> $ops_tables", self.bridge)

    def test_manifest_v2_is_exact_and_v1_remains_accepted(self) -> None:
        self.assertIn("complete99-database-snapshot-manifest/v1", self.bridge)
        self.assertIn("complete99-database-snapshot-manifest/v2", self.bridge)
        self.assertIn("$components[] = 'ops_tables'", self.bridge)
        self.assertIn("'ops_tables' === $component && 7 !==", self.bridge)

        for validator in (
            lambda value, digest: RECOVER.validate_database_manifest(
                DEPLOY, value, digest, "test"
            ),
            lambda value, digest: AUDIT.validate_database_manifest(
                value, digest, "test"
            ),
        ):
            for version in (1, 2):
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

    def test_first_install_detaches_before_core_transaction_and_cleans_after_readback(self) -> None:
        detach = self.restore.index("$ops_atomic_rename( $detach_pairs )")
        begin = self.restore.index("START TRANSACTION")
        readback = self.restore.index("$precommit_snapshot = $capture_database_state()")
        commit = self.restore.index("$wpdb->query( 'COMMIT' )")
        self.assertLess(detach, begin)
        self.assertLess(begin, readback)
        self.assertLess(readback, commit)
        self.assertIn("$ops_rejoin_forward(", self.restore)
        self.assertIn("c99_ops_restore_existing_changed", self.bridge)

        baseline_readback = self.rollback.index("$restored_database_snapshot")
        cleanup = self.rollback.index("$ops_cleanup_quarantine(")
        terminal = self.rollback.index("$rolled_back = $set_state_phase(")
        self.assertLess(baseline_readback, cleanup)
        self.assertLess(cleanup, terminal)
        self.assertIn("'ops_quarantine_cleaned'=> true", self.rollback)

    def test_legacy_v1_journal_is_authenticated_before_absent_ops_synthesis(self) -> None:
        legacy_shape = self.rollback.index("$legacy_journal_keys")
        digest_check = self.rollback.index(
            "hash_equals( $baseline_fingerprint, hash( 'sha256', $journal_json ) )"
        )
        synthesis = self.rollback.index(
            "'ops_tables'                => $ops_absent_snapshot()"
        )
        self.assertLess(legacy_shape, digest_check)
        self.assertLess(digest_check, synthesis)
        self.assertIn(
            "$normalized_options['complete99_ops_schema_version'] = null",
            self.rollback,
        )
        self.assertIn(
            "unset( $current_recorded_snapshot['options']['complete99_ops_schema_version'] )",
            self.rollback,
        )
        self.assertIn("c99_ops_legacy_journal_conflict", self.rollback)
        self.assertIn("unset( $current_recorded_snapshot['ops_tables'] )", self.rollback)
        self.assertIn("$restore_database_state( $database_snapshot,", self.rollback)

    def test_retry_proof_and_residue_gates_are_deterministic(self) -> None:
        checkpoint = self.rollback.index("'rollback_forward_ops_sha256'")
        restore = self.rollback.index("$restore_database_state( $database_snapshot,")
        self.assertLess(checkpoint, restore)
        for contract in (
            "$ops_reconstruct_forward(",
            "$ops_rejoin_forward(",
            "c99_ops_rollback_retry_proof",
            "c99_ops_rollback_retry_conflict",
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

    def test_every_runtime_checkpoint_fails_closed_on_ops_or_science_drift(self) -> None:
        self.assertEqual(
            self.bridge.count("class_exists( 'Complete99_Ops', false )"), 5
        )
        self.assertEqual(
            self.bridge.count("method_exists( 'Complete99_Ops', 'assert_invariants' )"),
            5,
        )
        self.assertEqual(self.bridge.count("Complete99_Ops::assert_invariants();"), 5)
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
            5,
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
            self.assertIn("Complete99_Ops::assert_invariants();", segment)
            self.assertIn(
                "class_exists( 'Complete99_Culinary_Science', false )", segment
            )
            self.assertIn(
                "method_exists( 'Complete99_Culinary_Science', 'assert_invariants' )",
                segment,
            )
            self.assertIn(
                "Complete99_Culinary_Science::assert_invariants();", segment
            )

        self.assertGreaterEqual(
            stabilize.count("Complete99_Ops::assert_invariants();"), 2
        )


if __name__ == "__main__":
    unittest.main()
