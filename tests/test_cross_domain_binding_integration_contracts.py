import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
BOOTSTRAP = PLUGIN / "complete99-platform.php"
REST = PLUGIN / "includes" / "class-complete99-rest.php"
REVIEW = PLUGIN / "includes" / "class-complete99-review-lab.php"
BINDINGS = PLUGIN / "includes" / "class-complete99-cross-domain-bindings.php"


def run_php(code: str) -> str:
    completed = subprocess.run(
        ["php", "-r", code],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=30,
    )
    return completed.stdout


class CrossDomainBindingIntegrationContracts(unittest.TestCase):
    def test_bootstrap_loads_bindings_after_science_before_consumers(self) -> None:
        source = BOOTSTRAP.read_text(encoding="utf-8")
        science = "includes/class-complete99-culinary-science.php"
        bindings = "includes/class-complete99-cross-domain-bindings.php"
        commerce = "includes/class-complete99-culinary-commerce.php"

        self.assertEqual(1, source.count(bindings))
        self.assertLess(source.index(science), source.index(bindings))
        self.assertLess(source.index(bindings), source.index(commerce))
        self.assertNotIn("Complete99_Cross_Domain_Bindings::boot", source)

    def test_invalid_bindings_are_non_fatal_and_health_is_bounded(self) -> None:
        rest_path = REST.as_posix().replace("'", "\\'")
        payload = run_php(
            f"""
            define('ABSPATH', __DIR__);
            define('COMPLETE99_PLATFORM_VERSION', '1.20.0');
            define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'binding-health-contract');
            class WP_Error {{
                public function __construct($code, $message, $data = array()) {{}}
            }}
            class Complete99_Settings {{
                const OPTION_SECRET = 'complete99_sync_secret';
            }}
            class Complete99_Platform {{
                public static function migration_failed() {{ return false; }}
            }}
            class Complete99_Cross_Domain_Bindings {{
                public static function status() {{
                    return array(
                        'schema' => 'complete99-cross-domain-binding-registry/v2',
                        'version' => 'complete99-cross-domain-bindings-2026.08.08.v2',
                        'registry_valid' => false,
                        'record_count' => 95,
                        'candidates' => array('private-candidate'),
                        'reviewer_id' => 'private-reviewer',
                        'evidence_refs' => array('private-evidence'),
                        'error_detail' => 'private-validator-path',
                    );
                }}
            }}
            $options = array(
                'complete99_platform_version' => '1.20.0',
                'complete99_last_deployment_id' => 'binding-health-contract',
            );
            function get_option($name, $default = false) {{
                global $options;
                return array_key_exists($name, $options) ? $options[$name] : $default;
            }}
            function rest_ensure_response($value) {{ return $value; }}
            require '{rest_path}';
            echo json_encode(Complete99_REST::health(), JSON_THROW_ON_ERROR);
            """
        )
        health = json.loads(payload)

        self.assertEqual("ok", health["status"])
        self.assertFalse(health["cross_domain_bindings_valid"])
        self.assertEqual(
            "complete99-cross-domain-bindings-2026.08.08.v2",
            health["cross_domain_bindings_version"],
        )
        self.assertEqual(
            {
                "cross_domain_bindings_valid",
                "cross_domain_bindings_version",
            },
            {key for key in health if key.startswith("cross_domain_bindings_")},
        )
        serialized = json.dumps(health, sort_keys=True).lower()
        for private_marker in (
            "private-candidate",
            "private-reviewer",
            "private-evidence",
            "private-validator-path",
            "record_count",
            "candidate",
            "reviewer",
            "evidence",
            "error_detail",
        ):
            self.assertNotIn(private_marker, serialized)

    def test_health_uses_only_guarded_status_api(self) -> None:
        source = REST.read_text(encoding="utf-8")
        health = source.split("public static function health()", 1)[1].split(
            "public static function verify_sync_signature", 1
        )[0]

        self.assertIn(
            "class_exists( 'Complete99_Cross_Domain_Bindings', false )", health
        )
        self.assertIn(
            "is_callable( array( 'Complete99_Cross_Domain_Bindings', 'status' ) )",
            health,
        )
        self.assertIn("Complete99_Cross_Domain_Bindings::status()", health)
        self.assertNotIn("Complete99_Cross_Domain_Bindings::indexes", health)
        self.assertNotIn("editorial_snapshot", health)
        self.assertNotIn("candidate", health)
        self.assertNotIn("reviewer", health)
        self.assertNotIn("evidence", health)
        self.assertNotIn("binding_registry_unavailable", health)

        fatal_gate = health.split("if ( ( $science_loaded", 1)[1].split(
            "return rest_ensure_response", 1
        )[0]
        self.assertNotIn("bindings", fatal_gate)

    def test_review_lab_snapshot_is_private_and_uses_editorial_api(self) -> None:
        review_path = REVIEW.as_posix().replace("'", "\\'")
        plugin_path = (PLUGIN.as_posix() + "/").replace("'", "\\'")
        payload = run_php(
            f"""
            define('ABSPATH', __DIR__);
            define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
            function sanitize_file_name($value) {{
                return preg_replace('/[^A-Za-z0-9._-]/', '', (string) $value);
            }}
            function sanitize_key($value) {{
                return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value));
            }}
            class Complete99_Cross_Domain_Bindings {{
                public static function editorial_snapshot() {{
                    return array(
                        'registry' => array(
                            'version' => 'private-v1',
                            'records' => array(
                                array('candidates' => array_fill(0, 11, array('state' => 'pending_review'))),
                            ),
                        ),
                        'indexes' => array(
                            'public_navigation' => array(),
                            'public_product_navigation' => array(),
                        ),
                        'status' => array(
                            'registry_valid' => true,
                            'record_count' => 95,
                            'dish_subject_count' => 12,
                            'component_subject_count' => 47,
                            'product_subject_count' => 36,
                            'unresolved_count' => 95,
                        ),
                    );
                }}
            }}
            require '{review_path}';
            echo json_encode(Complete99_Review_Lab::snapshot()['cross_domain_bindings'], JSON_THROW_ON_ERROR);
            """
        )
        snapshot = json.loads(payload)

        self.assertTrue(snapshot["status"]["registry_valid"])
        self.assertEqual(95, snapshot["status"]["record_count"])
        self.assertEqual(12, snapshot["status"]["dish_subject_count"])
        self.assertEqual(47, snapshot["status"]["component_subject_count"])
        self.assertEqual(36, snapshot["status"]["product_subject_count"])
        self.assertEqual(95, snapshot["status"]["unresolved_count"])
        self.assertEqual(11, len(snapshot["registry"]["records"][0]["candidates"]))

        source = REVIEW.read_text(encoding="utf-8")
        render = source.split("public static function render_page()", 1)[1]
        self.assertLess(
            render.index("current_user_can( 'manage_options' )"),
            render.index("$snapshot = self::snapshot();"),
        )
        self.assertIn("Complete99_Cross_Domain_Bindings::editorial_snapshot()", source)
        for card in (
            "Binding registry",
            "Binding decisions",
            "Menu dish subjects",
            "Scoped component subjects",
            "Woo product subjects",
            "Unresolved bindings",
            "Explicit candidates",
        ):
            self.assertIn(card, source)

    def test_unauthorized_review_render_never_loads_private_snapshot(self) -> None:
        review_path = REVIEW.as_posix().replace("'", "\\'")
        payload = run_php(
            f"""
            define('ABSPATH', __DIR__);
            class Complete99_Cross_Domain_Bindings {{
                public static $calls = 0;
                public static function editorial_snapshot() {{
                    ++self::$calls;
                    return array();
                }}
            }}
            function current_user_can($capability) {{ return false; }}
            function esc_html__($text, $domain = '') {{ return $text; }}
            function wp_die($message) {{ throw new RuntimeException($message); }}
            require '{review_path}';
            try {{
                Complete99_Review_Lab::render_page();
            }} catch (RuntimeException $error) {{}}
            echo json_encode(array('calls' => Complete99_Cross_Domain_Bindings::$calls), JSON_THROW_ON_ERROR);
            """
        )
        self.assertEqual({"calls": 0}, json.loads(payload))

    def test_seeded_unresolved_registry_has_literal_empty_public_indexes(self) -> None:
        bindings_path = BINDINGS.as_posix().replace("'", "\\'")
        plugin_path = (PLUGIN.as_posix() + "/").replace("'", "\\'")
        payload = run_php(
            f"""
            define('ABSPATH', __DIR__);
            define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
            class WP_Error {{
                private $code;
                private $message;
                private $data;
                public function __construct($code, $message, $data = array()) {{
                    $this->code = $code;
                    $this->message = $message;
                    $this->data = $data;
                }}
            }}
            function is_wp_error($value) {{ return $value instanceof WP_Error; }}
            require '{bindings_path}';
            echo json_encode(
                array(
                    'status' => Complete99_Cross_Domain_Bindings::status(true),
                    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
                ),
                JSON_THROW_ON_ERROR
            );
            """
        )
        result = json.loads(payload)

        self.assertTrue(result["status"]["registry_valid"])
        self.assertEqual(95, result["status"]["record_count"])
        self.assertEqual(95, result["status"]["unresolved_count"])
        self.assertEqual(
            {
                "menu_dish_science_dish",
                "menu_component_science_entity",
                "woo_product_science_entity",
                "public_navigation",
                "public_product_navigation",
            },
            set(result["indexes"]),
        )
        self.assertTrue(all(value == [] for value in result["indexes"].values()))

    def test_integration_does_not_mutate_woo_or_public_surfaces(self) -> None:
        rest = REST.read_text(encoding="utf-8")
        review = REVIEW.read_text(encoding="utf-8")
        bindings = BINDINGS.read_text(encoding="utf-8")
        bootstrap = BOOTSTRAP.read_text(encoding="utf-8")
        health = rest.split("public static function health()", 1)[1].split(
            "public static function verify_sync_signature", 1
        )[0]

        for source in (review, bindings, bootstrap, health):
            for mutation in (
                "wp_insert_post(",
                "wp_update_post(",
                "update_post_meta(",
                "delete_post_meta(",
                "update_option(",
                "WC_Product",
                "wc_get_product(",
                "set_status(",
                "set_catalog_visibility(",
                "set_stock_quantity(",
            ):
                self.assertNotIn(mutation, source)

        public_catalog = rest.split("public static function public_catalog", 1)[1].split(
            "public static function public_indexable_items", 1
        )[0]
        public_indexes = rest.split(
            "public static function public_indexable_items", 1
        )[1].split("private static function bundled_public_indexable_items", 1)[0]
        self.assertNotIn("Cross_Domain_Bindings", public_catalog)
        self.assertNotIn("Cross_Domain_Bindings", public_indexes)


if __name__ == "__main__":
    unittest.main()
