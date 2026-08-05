import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
REVIEW = PLUGIN / "includes" / "class-complete99-review-lab.php"


def run_php(code: str):
    completed = subprocess.run(
        ["php", "-r", code],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
    )
    return completed.stdout


class ReviewLabContracts(unittest.TestCase):
    def test_review_lab_is_private_read_only_admin_surface(self):
        source = REVIEW.read_text(encoding="utf-8")
        self.assertIn("add_management_page(", source)
        self.assertGreaterEqual(source.count("'manage_options'"), 2)
        self.assertIn("current_user_can( 'manage_options' )", source)
        self.assertNotIn("register_rest_route(", source)
        self.assertNotIn("wp_insert_post(", source)
        self.assertNotIn("wp_update_post(", source)
        self.assertNotIn("update_option(", source)
        self.assertNotIn("update_post_meta(", source)

    def test_snapshot_loads_bounded_review_registries(self):
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
            class Complete99_Platform {{
                public static function evaluation_catalog_status() {{
                    return array(
                        'schema' => 'complete99-evaluation-catalog-status/v1',
                        'ready' => true,
                        'reason' => 'ready',
                        'receipt' => array(
                            'present' => true,
                            'valid' => true,
                            'status' => 'accepted',
                            'mode' => 'private_only',
                            'seed_count' => 26,
                            'ingredient_count' => 26,
                            'product_plan_count' => 26,
                            'woo_product_count' => 0,
                            'woo_materialized' => false,
                        ),
                        'materialized' => array(
                            'ingredient_count' => 26,
                            'product_plan_count' => 26,
                        ),
                    );
                }}
            }}
            require '{review_path}';
            echo json_encode(Complete99_Review_Lab::snapshot(), JSON_UNESCAPED_SLASHES);
            """
        )
        snapshot = json.loads(payload)
        self.assertEqual("complete99-review-lab/v1", snapshot["schema"])
        self.assertEqual(12, len(snapshot["dishes"]))
        self.assertEqual(26, len(snapshot["products"]))
        self.assertEqual(50, len(snapshot["assets"]))
        self.assertEqual(11, len(snapshot["guides"]))
        self.assertGreaterEqual(len(snapshot["ingredient_codes"]), 10)
        self.assertLessEqual(len(snapshot["dishes"]), 100)
        self.assertLessEqual(len(snapshot["products"]), 500)
        self.assertLessEqual(len(snapshot["assets"]), 500)
        self.assertIn("wolt", snapshot["connectors"])
        self.assertFalse(snapshot["commerce"]["ready"])
        self.assertTrue(snapshot["evaluation_catalog"]["ready"])
        self.assertTrue(snapshot["evaluation_catalog"]["receipt"]["valid"])
        self.assertEqual(
            26,
            snapshot["evaluation_catalog"]["materialized"]["ingredient_count"],
        )
        self.assertEqual(
            26,
            snapshot["evaluation_catalog"]["materialized"]["product_plan_count"],
        )
        self.assertNotIn(
            "price",
            json.dumps(snapshot["evaluation_catalog"], sort_keys=True).lower(),
        )

    def test_bootstrap_requires_and_boots_review_lab(self):
        bootstrap = (PLUGIN / "complete99-platform.php").read_text(encoding="utf-8")
        platform = (
            PLUGIN / "includes" / "class-complete99-platform.php"
        ).read_text(encoding="utf-8")
        self.assertIn(
            "includes/class-complete99-review-lab.php",
            bootstrap,
        )
        self.assertIn("Complete99_Review_Lab::boot();", platform)

    def test_review_copy_keeps_evaluation_separate_from_public_sale(self):
        source = REVIEW.read_text(encoding="utf-8")
        self.assertIn("אינו מפרסם דבר באופן אוטומטי", source)
        self.assertIn("פרטי לבדיקה", source)
        self.assertIn("בדיקות הקבלה", source)
        self.assertNotIn("\u2014", source)

    def test_product_commerce_rendering_uses_precomputed_indexes(self):
        source = REVIEW.read_text(encoding="utf-8")
        product_loop_marker = "<?php foreach ( $graph_products as $graph_product ) : ?>"
        product_loop = source.split(product_loop_marker, 1)[1].split(
            "<?php endforeach; ?>", 1
        )[0]

        self.assertLess(
            source.index("$variants_by_product = array();"),
            source.index(product_loop_marker),
        )
        self.assertIn(
            "$variants_by_product[ $variant_product_id ][] = $variant;", source
        )
        self.assertIn("$skus_by_variant[ $sku_variant_id ][] = $sku;", source)
        self.assertIn(
            "$observations_by_sku[ $observation_sku_id ][] = $observation;",
            source,
        )
        self.assertIn("$variants_by_product[ $product_id ]", product_loop)
        self.assertIn("$skus_by_variant[ $variant_id ]", product_loop)
        self.assertIn("$observations_by_sku[ $sku_id ]", product_loop)
        self.assertNotIn("array_filter(", product_loop)
        self.assertNotIn("in_array(", product_loop)


if __name__ == "__main__":
    unittest.main()
