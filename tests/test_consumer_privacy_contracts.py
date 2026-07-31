from __future__ import annotations

import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONTENT = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-content.php"
)


class Complete99ConsumerPrivacyContracts(unittest.TestCase):
    def run_content_registry(self) -> dict:
        content_path = CONTENT.as_posix().replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
$c99_post_types = array();
$c99_taxonomies = array();
$c99_rewrites = array();
$c99_post_meta = array();
function register_post_type($name, $args) {{
    $GLOBALS['c99_post_types'][$name] = $args;
}}
function register_taxonomy($name, $objects, $args) {{
    $GLOBALS['c99_taxonomies'][$name] = array(
        'objects' => $objects,
        'args' => $args,
    );
}}
function register_post_meta($post_type, $key, $args) {{
    if (!isset($GLOBALS['c99_post_meta'][$post_type])) {{
        $GLOBALS['c99_post_meta'][$post_type] = array();
    }}
    $GLOBALS['c99_post_meta'][$post_type][$key] = $args;
}}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {{}}
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{}}
function add_rewrite_rule($regex, $query, $position = 'bottom') {{
    $GLOBALS['c99_rewrites'][] = array(
        'regex' => $regex,
        'query' => $query,
        'position' => $position,
    );
}}
require '{content_path}';
Complete99_Content::register();
Complete99_Content::register_rewrites();
$sitemap = Complete99_Content::filter_sitemap_post_types(array(
    'page' => array(),
    'post' => array(),
    'c99_service' => array(),
    'c99_industry' => array(),
    'c99_platform_feature' => array(),
    'c99_dish' => array(),
    'c99_ingredient' => array(),
    'c99_location' => array(),
    'c99_guide' => array(),
    'c99_case_study' => array(),
    'c99_team_member' => array(),
));
echo json_encode(array(
    'post_types' => $c99_post_types,
    'post_meta' => $c99_post_meta,
    'taxonomies' => $c99_taxonomies,
    'rewrites' => $c99_rewrites,
    'sitemap_types' => array_keys($sitemap),
), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=20,
        )
        return json.loads(completed.stdout)

    def test_only_approved_culinary_types_have_public_surfaces(self) -> None:
        result = self.run_content_registry()
        post_types = result["post_types"]
        post_meta = result["post_meta"]
        public_types = {"c99_dish", "c99_ingredient", "c99_guide"}
        private_types = {
            "c99_service",
            "c99_industry",
            "c99_platform_feature",
            "c99_location",
            "c99_case_study",
            "c99_team_member",
        }
        self.assertEqual(public_types | private_types, set(post_types))

        for post_type in public_types:
            args = post_types[post_type]
            self.assertTrue(args["public"])
            self.assertTrue(args["publicly_queryable"])
            self.assertTrue(args["show_in_rest"])
            self.assertTrue(args["show_in_nav_menus"])
            self.assertTrue(args["query_var"])
            self.assertFalse(args["exclude_from_search"])
            self.assertTrue(
                all(meta["show_in_rest"] for meta in post_meta[post_type].values())
            )

        for post_type in private_types:
            args = post_types[post_type]
            self.assertFalse(args["public"])
            self.assertFalse(args["publicly_queryable"])
            self.assertFalse(args["show_in_rest"])
            self.assertFalse(args["show_in_nav_menus"])
            self.assertFalse(args["query_var"])
            self.assertTrue(args["exclude_from_search"])
            self.assertTrue(args["show_ui"])
            self.assertTrue(args["show_in_menu"])
            self.assertTrue(
                all(
                    meta["show_in_rest"] is False
                    for meta in post_meta[post_type].values()
                )
            )

        self.assertEqual(
            {"page", "c99_dish", "c99_ingredient", "c99_guide"},
            set(result["sitemap_types"]),
        )
        rewrite_queries = {
            rewrite["query"] for rewrite in result["rewrites"]
        }
        for post_type in public_types:
            self.assertTrue(
                any(f"post_type={post_type}" in query for query in rewrite_queries)
            )
        for post_type in private_types:
            self.assertFalse(
                any(f"post_type={post_type}" in query for query in rewrite_queries)
            )

    def test_only_culinary_taxonomies_have_public_surfaces(self) -> None:
        taxonomies = self.run_content_registry()["taxonomies"]
        public_taxonomies = {
            "c99_dish_course",
            "c99_food_tradition",
            "c99_dietary_note",
        }
        private_taxonomies = {
            "c99_service_family",
            "c99_sector",
            "c99_ops_domain",
            "c99_region",
        }
        self.assertEqual(public_taxonomies | private_taxonomies, set(taxonomies))

        for taxonomy in public_taxonomies:
            args = taxonomies[taxonomy]["args"]
            self.assertTrue(args["public"])
            self.assertTrue(args["publicly_queryable"])
            self.assertTrue(args["show_in_rest"])
            self.assertTrue(args["show_in_nav_menus"])

        for taxonomy in private_taxonomies:
            args = taxonomies[taxonomy]["args"]
            self.assertFalse(args["public"])
            self.assertFalse(args["publicly_queryable"])
            self.assertFalse(args["show_in_rest"])
            self.assertFalse(args["show_in_nav_menus"])
            self.assertFalse(args["query_var"])
            self.assertTrue(args["show_ui"])
            self.assertTrue(args["show_admin_column"])


if __name__ == "__main__":
    unittest.main()
