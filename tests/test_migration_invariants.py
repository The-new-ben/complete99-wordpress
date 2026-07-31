from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class MigrationInvariantContractTests(unittest.TestCase):
    def test_migration_asserts_readback_before_version_and_commit(self):
        source = (
            ROOT
            / "plugin"
            / "complete99-platform"
            / "includes"
            / "class-complete99-platform.php"
        ).read_text(encoding="utf-8")

        migration = source[source.index("private static function run_migration") :]
        lock = migration.index("$lock = self::acquire_migration_lock();")
        reread = migration.index(
            "$current = self::persisted_option( 'complete99_platform_version' )"
        )
        transaction = migration.index("START TRANSACTION")
        invariants = migration.index(
            "Complete99_Content::assert_migration_invariants();"
        )
        evaluation_materialize = migration.index(
            "Complete99_Evaluation_Catalog::materialize("
        )
        commerce_type_registration = migration.index(
            "Complete99_Commerce::register_product_planning_type();"
        )
        graph_meta_registration = migration.index(
            "Complete99_Catalog_Graph::register_meta();"
        )
        evaluation_meta_registration = migration.index(
            "Complete99_Evaluation_Catalog::register_meta();"
        )
        inventory_meta_registration = migration.index(
            "Complete99_Inventory_Bridge::register_meta();"
        )
        evaluation_invariants = migration.index(
            "self::assert_evaluation_catalog_invariants();"
        )
        version = migration.index(
            "update_option( 'complete99_platform_version', COMPLETE99_PLATFORM_VERSION"
        )
        commit = migration.index("$wpdb->query( 'COMMIT' )")
        finally_release = migration.index(
            "self::release_migration_lock( $lock );",
            migration.index("finally"),
        )
        self.assertLess(lock, reread)
        self.assertLess(reread, transaction)
        self.assertLess(transaction, evaluation_materialize)
        self.assertLess(commerce_type_registration, evaluation_materialize)
        self.assertLess(graph_meta_registration, evaluation_materialize)
        self.assertLess(evaluation_meta_registration, evaluation_materialize)
        self.assertLess(inventory_meta_registration, evaluation_materialize)
        self.assertLess(invariants, version)
        self.assertLess(evaluation_materialize, evaluation_invariants)
        self.assertLess(evaluation_invariants, version)
        self.assertLess(version, commit)
        self.assertGreater(finally_release, commit)
        self.assertIn("version-readback", migration)
        self.assertIn("SELECT option_value FROM {$wpdb->options}", source)
        self.assertIn("self::persisted_option( 'complete99_platform_version' )", migration)
        self.assertIn(
            "Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY", migration
        )
        self.assertNotIn(
            "Complete99_Evaluation_Catalog::MODE_AUTO", migration
        )
        self.assertIn(
            "Complete99_Commerce::register_product_planning_type();",
            migration,
        )
        self.assertIn("Complete99_Catalog_Graph::register_meta();", migration)
        self.assertIn("Complete99_Evaluation_Catalog::register_meta();", migration)
        self.assertIn("Complete99_Inventory_Bridge::register_meta();", migration)

    def test_evaluation_inventory_boot_health_and_deploy_journal_are_wired(self):
        plugin = ROOT / "plugin" / "complete99-platform"
        bootstrap = (plugin / "complete99-platform.php").read_text(encoding="utf-8")
        platform = (
            plugin / "includes" / "class-complete99-platform.php"
        ).read_text(encoding="utf-8")
        health = (plugin / "includes" / "class-complete99-rest.php").read_text(
            encoding="utf-8"
        )
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(
            encoding="utf-8"
        )

        evaluation_require = bootstrap.index(
            "includes/class-complete99-evaluation-catalog.php"
        )
        inventory_require = bootstrap.index(
            "includes/class-complete99-inventory-bridge.php"
        )
        self.assertLess(evaluation_require, inventory_require)
        evaluation_boot = platform.index("Complete99_Evaluation_Catalog::boot();")
        inventory_boot = platform.index("Complete99_Inventory_Bridge::boot();")
        self.assertLess(evaluation_boot, inventory_boot)

        self.assertIn("'complete99_evaluation_catalog_incomplete'", health)
        self.assertIn("array( 'status' => 503 )", health)
        health_block = health.split("public static function health()", 1)[1].split(
            "public static function verify_sync_signature", 1
        )[0]
        self.assertNotIn("_complete99_evaluation_price_ils", health_block)
        self.assertNotIn("evaluation_price", health_block)

        self.assertIn("'complete99_evaluation_catalog_receipt'", bridge)
        self.assertIn("'_complete99_evaluation_catalog_managed'", bridge)
        self.assertIn("'evaluation_ids'=> $evaluation_ids", bridge)
        self.assertIn(
            "Complete99_Platform::assert_evaluation_catalog_invariants();",
            bridge,
        )

    def test_seed_meta_dormant_roles_settings_and_front_page_fail_closed(self):
        platform = (
            ROOT
            / "plugin"
            / "complete99-platform"
            / "includes"
            / "class-complete99-platform.php"
        ).read_text(encoding="utf-8")
        content = (
            ROOT
            / "plugin"
            / "complete99-platform"
            / "includes"
            / "class-complete99-content.php"
        ).read_text(encoding="utf-8")
        settings = (
            ROOT
            / "plugin"
            / "complete99-platform"
            / "includes"
            / "class-complete99-settings.php"
        ).read_text(encoding="utf-8")

        self.assertIn("private static function store_seed_meta", content)
        self.assertIn("private static function direct_single_meta_state", content)
        self.assertIn("SELECT meta_value FROM {$wpdb->postmeta}", content)
        self.assertIn("private static function unique_seed_record", content)
        self.assertIn("must have exactly one post and one key row", content)
        self.assertIn("public static function assert_migration_invariants", content)
        self.assertIn("private static function assert_roles_persisted", content)
        migration = platform[platform.index("private static function run_migration") :]
        invariants = content[
            content.index("public static function assert_migration_invariants") :
        ]
        self.assertNotIn("Complete99_Content::install_roles();", migration)
        self.assertNotIn("self::assert_roles_persisted();", invariants)
        self.assertIn("Role definitions remain dormant", platform)
        self.assertIn("$wpdb->get_blog_prefix( get_current_blog_id() )", content)
        self.assertIn("SELECT option_value FROM {$wpdb->options}", content)
        self.assertNotIn("$role->has_cap( $cap )", content)
        self.assertIn("_complete99_recipe_seed_hash", content)
        self.assertIn("private static function should_refresh_seed_recipe", content)
        self.assertIn("private static function expected_seed_status", content)
        self.assertIn("private static function required_seed_status", content)
        self.assertIn("array( $expected, 'private' )", content)
        self.assertIn("'home:he'", content)
        self.assertIn("'publish' !== (string) $home['post_status']", content)
        self.assertIn("'' !== (string) $home['post_password']", content)
        self.assertIn("self::ensure_site_identity();", content)
        self.assertIn("'קומפלט 99 | Complete99'", content)
        self.assertIn("'קומפליט'", content)
        self.assertIn("site-name correction failed readback", content)
        self.assertIn("public static function assert_defaults", settings)
        self.assertIn("private static function read_persisted_option", settings)
        self.assertIn("private static function canonical_https_url", settings)


if __name__ == "__main__":
    unittest.main()
