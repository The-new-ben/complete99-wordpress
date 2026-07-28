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
        self.assertLess(invariants, version)
        self.assertLess(version, commit)
        self.assertGreater(finally_release, commit)
        self.assertIn("version-readback", migration)
        self.assertIn("SELECT option_value FROM {$wpdb->options}", source)
        self.assertIn("self::persisted_option( 'complete99_platform_version' )", migration)

    def test_seed_meta_roles_settings_and_front_page_fail_closed(self):
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
