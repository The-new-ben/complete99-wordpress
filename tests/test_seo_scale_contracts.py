from __future__ import annotations

import csv
import json
import re
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
LAUNCH = PLUGIN / "data" / "launch-content.php"
KEYWORDS = PLUGIN / "data" / "keyword-ownership.csv"
CONTENT = PLUGIN / "includes" / "class-complete99-content.php"
REGISTRY = PLUGIN / "includes" / "class-complete99-seo-registry.php"
PLATFORM = PLUGIN / "includes" / "class-complete99-platform.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
SITEMAP = PLUGIN / "includes" / "class-complete99-live-dish-sitemap-provider.php"
MAIN = PLUGIN / "complete99-platform.php"
HUBS = {
    "home": "/",
    "about": "/about/",
    "contact": "/contact/",
    "dishes": "/dishes/",
    "ingredients": "/ingredients/",
    "traditions": "/traditions/",
    "knowledge": "/knowledge/",
    "store": "/store/",
}
LEGAL = {
    "privacy": "/privacy/",
    "terms": "/terms/",
    "accessibility": "/accessibility/",
}
PUBLIC_FOUNDATIONS = HUBS | LEGAL


def word_count(html: str) -> int:
    text = re.sub(r"<[^>]+>", " ", html)
    return len(re.findall(r"[^\W_]+", text, flags=re.UNICODE))


class Complete99SEOScaleContracts(unittest.TestCase):
    @unittest.skipUnless(shutil.which("php"), "PHP is required to evaluate launch records")
    def test_bilingual_hubs_and_legal_foundations_are_substantive(self) -> None:
        launch_path = json.dumps(LAUNCH.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
$records = require {launch_path};
$selected = array();
foreach ($records as $record) {{
    if (in_array($record['key'], {json.dumps(sorted(PUBLIC_FOUNDATIONS))}, true)) {{
        $selected[$record['key']] = $record;
    }}
}}
echo json_encode($selected, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        records = json.loads(completed.stdout)
        self.assertEqual(set(PUBLIC_FOUNDATIONS), set(records))
        for key, record in records.items():
            self.assertEqual("page", record["type"])
            if "home" == key:
                self.assertEqual("complete99-home", record["slug"]["he"])
                self.assertEqual("en", record["slug"]["en"])
            else:
                self.assertEqual(key, record["slug"]["he"])
                self.assertEqual(key, record["slug"]["en"])
            for language in ("he", "en"):
                self.assertGreaterEqual(
                    word_count(record["content"][language]),
                    50,
                    f"{key}:{language} is too thin for a launch hub",
                )
                self.assertGreaterEqual(
                    record["content"][language].count("<h2>"),
                    1,
                    f"{key}:{language} lacks an intentional information structure",
                )
        self.assertFalse(records["store"]["index_eligible"])
        self.assertEqual("configuration_required", records["store"]["verification"])
        store_public = " ".join(
            (
                records["store"]["excerpt"]["he"],
                records["store"]["excerpt"]["en"],
                records["store"]["content"]["he"],
                records["store"]["content"]["en"],
            )
        ).lower()
        for marker in (
            "כרגע אין מוצרים לרכישה באתר",
            "there are currently no products for sale on this site",
            "before a purchase button appears",
        ):
            self.assertIn(marker, store_public)
        for key in set(HUBS) - {"store"}:
            self.assertTrue(records[key]["index_eligible"])
            self.assertEqual("editorial_review", records[key]["verification"])
        for key in LEGAL:
            self.assertTrue(records[key]["index_eligible"])
            self.assertEqual("editorial_review", records[key]["verification"])

    def test_keyword_registry_has_unique_bilingual_hub_owners(self) -> None:
        with KEYWORDS.open(encoding="utf-8", newline="") as handle:
            rows = list(csv.DictReader(handle))
        paths = [row["canonical_path"].lower().rstrip("/") or "/" for row in rows]
        self.assertEqual(len(paths), len(set(paths)))
        self.assertFalse(
            any(marker in path for path in paths for marker in ("*", "{", "}")),
            "Canonical ownership must use exact paths rather than wildcard placeholders",
        )

        grouped: dict[str, dict[str, dict[str, str]]] = {}
        for row in rows:
            grouped.setdefault(row["translation_key"], {})[row["language"]] = row
        for translation_key, locales in grouped.items():
            self.assertEqual(
                {"he", "en"},
                set(locales),
                f"{translation_key} must have one Hebrew and one English owner",
            )
        for key, hebrew_path in PUBLIC_FOUNDATIONS.items():
            self.assertEqual(hebrew_path, grouped[key]["he"]["canonical_path"])
            self.assertEqual(
                f"/en{hebrew_path}",
                grouped[key]["en"]["canonical_path"],
            )

    def test_managed_identity_translation_and_sitemap_gates_are_wired(self) -> None:
        content = CONTENT.read_text(encoding="utf-8")
        platform = PLATFORM.read_text(encoding="utf-8")
        frontend = FRONTEND.read_text(encoding="utf-8")
        for marker in (
            "_complete99_managed",
            "_complete99_translation_group",
            "_complete99_parent_hub",
            "_complete99_index_eligible",
            "public static function find_translation_post_id",
            "public static function translation_group_for_post",
            "public static function breadcrumb_trail",
            "public static function is_index_eligible",
            "wp_sitemaps_add_provider",
            "wp_sitemaps_post_types",
            "wp_sitemaps_taxonomies",
            "wp_sitemaps_posts_query_args",
            "public static function robots_index_gate",
            "is_tax( self::$public_taxonomies )",
            "array_merge( array( 'page' ), self::$public_post_types )",
        ):
            self.assertIn(marker, content)
        self.assertIn("Complete99_Content::boot_governance();", platform)
        self.assertGreaterEqual(
            frontend.count("Complete99_Content::translation_group_for_post("),
            5,
        )
        self.assertNotRegex(
            frontend,
            r"get_post_meta\([^\n]+_complete99_translation_key",
        )
        route = content.split("public static function route_url", 1)[1].split(
            "public static function language_for_post", 1
        )[0]
        self.assertIn("find_translation_post_id", route)
        self.assertIn("return $id ? (string) get_permalink( $id ) : '';", route)
        self.assertNotIn("home_url", route)

        query_gate = content.split(
            "public static function filter_sitemap_posts_query_args", 1
        )[1].split("public static function robots_index_gate", 1)[0]
        self.assertIn("'_complete99_managed'", query_gate)
        self.assertIn("'_complete99_index_eligible'", query_gate)
        self.assertIn("'_complete99_verification_state'", query_gate)
        self.assertIn("'compare' => 'IN'", query_gate)
        self.assertIn("$args['post_status']  = 'publish';", query_gate)
        self.assertIn("$args['has_password'] = false;", query_gate)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for sitemap evaluation")
    def test_live_dish_sitemap_registers_exact_fresh_bilingual_urls(self) -> None:
        sitemap_path = json.dumps(SITEMAP.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
abstract class WP_Sitemaps_Provider {{
    protected $name = '';
    protected $object_type = '';
    abstract public function get_url_list($page_num, $object_subtype = '');
    abstract public function get_max_num_pages($object_subtype = '');
}}
class Complete99_REST {{
    public static function public_indexable_items($model = null) {{
        return array(array(
            'id' => 'dish-7',
            'slug' => 'runtime-sabich',
            'updated_at' => '2026-07-29T00:30:00+00:00',
        ));
    }}
}}
class Complete99_Frontend {{
    public static function live_dish_url($slug, $language) {{
        return 'https://complete99.example/'
            . ('en' === $language ? 'en/' : '')
            . 'menu/' . $slug . '/';
    }}
}}
class RegistryStub {{
    public $name = '';
    public $provider = null;
    public function add_provider($name, $provider) {{
        $this->name = $name;
        $this->provider = $provider;
        return true;
    }}
}}
function sanitize_title($value) {{
    return trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower((string) $value)), '-');
}}
function wp_sitemaps_get_max_urls($object_type) {{ return 100; }}
require {sitemap_path};
$provider = new Complete99_Live_Dish_Sitemap_Provider();
$server = (object) array('registry' => new RegistryStub());
$registered = Complete99_Live_Dish_Sitemap_Provider::register($server);
echo json_encode(array(
    'registered' => $registered,
    'provider_name' => $server->registry->name,
    'pages' => $provider->get_max_num_pages(),
    'urls' => $provider->get_url_list(1),
    'page_two' => $provider->get_url_list(2),
), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertTrue(result["registered"])
        self.assertEqual("completedishes", result["provider_name"])
        self.assertRegex(result["provider_name"], r"^[a-z]+$")
        self.assertEqual(1, result["pages"])
        self.assertEqual([], result["page_two"])
        self.assertEqual(
            [
                "https://complete99.example/menu/runtime-sabich/",
                "https://complete99.example/en/menu/runtime-sabich/",
            ],
            [entry["loc"] for entry in result["urls"]],
        )
        self.assertEqual(
            {"2026-07-29T00:30:00+00:00"},
            {entry["lastmod"] for entry in result["urls"]},
        )

        main = MAIN.read_text(encoding="utf-8")
        platform = PLATFORM.read_text(encoding="utf-8")
        self.assertIn(
            "class-complete99-live-dish-sitemap-provider.php",
            main,
        )
        self.assertIn(
            "Complete99_Live_Dish_Sitemap_Provider::boot();",
            platform,
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required for registry evaluation")
    def test_live_dish_registry_uses_exact_bilingual_entity_owners(self) -> None:
        registry_path = json.dumps(REGISTRY.as_posix())
        plugin_dir = json.dumps(f"{PLUGIN.as_posix()}/")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', {plugin_dir});
class Complete99_REST {{
    public static function public_indexable_items($model = null) {{
        return array(array(
            'id' => 'dish-7',
            'slug' => 'runtime-sabich',
            'name_he' => 'Sabich Hebrew',
            'name_en' => 'Sabich English',
            'category_he' => 'Street food',
            'category_en' => 'Street food',
            'tag_he' => 'Vegetarian',
            'tag_en' => 'Vegetarian',
        ));
    }}
}}
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function sanitize_title($value) {{
    return trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower((string) $value)), '-');
}}
function untrailingslashit($value) {{
    return rtrim((string) $value, '/\\\\');
}}
function trailingslashit($value) {{
    return untrailingslashit($value) . '/';
}}
require {registry_path};
$rows = Complete99_SEO_Registry::live_dish_records();
echo json_encode(array(
    'rows' => $rows,
    'errors' => Complete99_SEO_Registry::validation_errors($rows),
), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertEqual([], result["errors"])
        self.assertEqual(2, len(result["rows"]))
        self.assertEqual(
            {"/menu/runtime-sabich/", "/en/menu/runtime-sabich/"},
            {row["canonical_path"] for row in result["rows"]},
        )
        self.assertEqual(
            1,
            len({row["translation_key"] for row in result["rows"]}),
        )
        for row in result["rows"]:
            self.assertFalse(
                any(marker in row["canonical_path"] for marker in ("*", "{", "}"))
            )

    @unittest.skipUnless(shutil.which("php"), "PHP is required for registry validation")
    def test_registry_runtime_detects_path_collision_and_missing_locale(self) -> None:
        registry_path = json.dumps(REGISTRY.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function untrailingslashit($value) {{
    return rtrim((string) $value, '/\\\\');
}}
function trailingslashit($value) {{
    return untrailingslashit($value) . '/';
}}
require {registry_path};
$base = array(
    'secondary_queries' => 'secondary',
    'prohibited_competing_pages' => 'competitor',
    'evidence_gate' => 'gate',
    'publication_status' => 'launch',
);
$rows = array(
    array_merge($base, array(
        'language' => 'he',
        'translation_key' => 'alpha',
        'primary_intent' => 'alpha he',
        'canonical_path' => '/alpha/',
    )),
    array_merge($base, array(
        'language' => 'en',
        'translation_key' => 'alpha',
        'primary_intent' => 'alpha en',
        'canonical_path' => '/en/alpha/',
    )),
    array_merge($base, array(
        'language' => 'he',
        'translation_key' => 'beta',
        'primary_intent' => 'beta he',
        'canonical_path' => '/alpha',
    )),
);
echo json_encode(
    Complete99_SEO_Registry::validation_errors($rows),
    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        errors = " ".join(json.loads(completed.stdout)).lower()
        self.assertIn("duplicate canonical path", errors)
        self.assertIn("translation group beta", errors)
        self.assertIn("exactly one en owner", errors)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for registry validation")
    def test_checked_in_registry_passes_runtime_validation(self) -> None:
        registry_path = json.dumps(REGISTRY.as_posix())
        plugin_dir = json.dumps(f"{PLUGIN.as_posix()}/")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', {plugin_dir});
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function untrailingslashit($value) {{
    return rtrim((string) $value, '/\\\\');
}}
function trailingslashit($value) {{
    return untrailingslashit($value) . '/';
}}
require {registry_path};
$records = Complete99_SEO_Registry::records();
echo json_encode(
    array(
        'count' => count($records),
        'errors' => Complete99_SEO_Registry::validation_errors($records),
    ),
    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertEqual(24, result["count"])
        self.assertEqual([], result["errors"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for seed gate evaluation")
    def test_seed_index_policy_allows_curated_content_and_blocks_gates(self) -> None:
        content_path = json.dumps(CONTENT.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
function rest_sanitize_boolean($value) {{ return filter_var($value, FILTER_VALIDATE_BOOLEAN); }}
require {content_path};
$method = new ReflectionMethod('Complete99_Content', 'seed_index_eligible');
$method->setAccessible(true);
$cases = array(
    'curated' => array('status' => 'publish', 'verification' => 'editorial_review'),
    'labelled_platform' => array('status' => 'publish', 'verification' => 'product_demo'),
    'proof_gated' => array('status' => 'publish', 'verification' => 'proof_gated'),
    'configuration_required' => array('status' => 'publish', 'verification' => 'configuration_required'),
    'draft' => array('status' => 'draft', 'verification' => 'editorial_review'),
    'explicit_false' => array('status' => 'publish', 'verification' => 'editorial_review', 'index_eligible' => false),
);
$result = array();
foreach ($cases as $name => $case) {{
    $result[$name] = $method->invoke(null, $case);
}}
echo json_encode($result, JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertTrue(result["curated"])
        self.assertTrue(result["labelled_platform"])
        self.assertFalse(result["proof_gated"])
        self.assertFalse(result["configuration_required"])
        self.assertFalse(result["draft"])
        self.assertFalse(result["explicit_false"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for metadata readback evaluation")
    def test_boolean_seed_metadata_is_compared_in_registered_canonical_type(self) -> None:
        content_path = json.dumps(CONTENT.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
function rest_sanitize_boolean($value) {{
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}}
function sanitize_meta($key, $value, $object_type, $object_subtype = '') {{
    if (in_array($key, array('_complete99_managed', '_complete99_index_eligible'), true)) {{
        return rest_sanitize_boolean($value);
    }}
    return $value;
}}
function get_post_type($post_id) {{ return 'page'; }}
function wp_slash($value) {{ return $value; }}
function maybe_serialize($value) {{
    return is_array($value) || is_object($value) ? serialize($value) : $value;
}}
function maybe_unserialize($value) {{ return $value; }}
function update_post_meta($post_id, $key, $value) {{
    global $wpdb;
    $wpdb->stored = $value ? '1' : '';
    return true;
}}
class MetadataWpdbStub {{
    public $postmeta = 'wp_postmeta';
    public $last_error = '';
    public $stored = null;
    public function prepare($query, ...$args) {{ return $query; }}
    public function get_col($query) {{
        return null === $this->stored ? array() : array($this->stored);
    }}
}}
$wpdb = new MetadataWpdbStub();
require {content_path};
$method = new ReflectionMethod('Complete99_Content', 'store_seed_meta');
$method->setAccessible(true);
$method->invoke(null, 7, '_complete99_managed', true);
$method->invoke(null, 7, '_complete99_index_eligible', false);
echo json_encode(array('passed' => true), JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        self.assertTrue(json.loads(completed.stdout)["passed"])


if __name__ == "__main__":
    unittest.main()
