from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
FRONTEND = PLUGIN / "includes" / "class-complete99-culinary-museum-frontend.php"
SITEMAP = (
    PLUGIN
    / "includes"
    / "class-complete99-culinary-museum-sitemap-provider.php"
)
TEMPLATE = PLUGIN / "templates" / "culinary-museum.php"
CSS = PLUGIN / "assets" / "css" / "culinary-museum.css"
CONSUMER = PLUGIN / "includes" / "class-complete99-consumer.php"


class CulinaryMuseumFrontendContracts(unittest.TestCase):
    def test_module_is_projection_only_and_exact_path_gated(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        expected_keys = (
            "'schema', 'version', 'language', 'entity', 'sections', "
            "'canonical_path', 'canonical_url', 'alternates', 'indexable'"
        )

        self.assertIn(expected_keys, frontend)
        self.assertIn("public_page_bundle_for_path", frontend)
        self.assertIn("array( 'GET', 'HEAD' )", frontend)
        self.assertIn("$bundle['canonical_path'] !== $lookup_path", frontend)
        self.assertIn("'standalone' !== $bundle['entity']['seo']['route_mode']", frontend)
        self.assertIn("wp_safe_redirect( self::$bundle['canonical_url'], 301", frontend)
        self.assertNotIn("Complete99_Culinary_Science::registry", frontend)
        self.assertNotIn("editorial_snapshot", frontend)
        self.assertNotIn("woo_product_code", frontend)
        self.assertNotIn("margin_scenario", frontend)
        self.assertNotIn("landed_cost", frontend)

    def test_bilingual_seo_evidence_and_accessibility_are_explicit(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        template = TEMPLATE.read_text(encoding="utf-8")
        css = CSS.read_text(encoding="utf-8")

        for marker in (
            'rel="canonical"',
            'rel="alternate" hreflang=',
            "'x-default'",
            'property="og:title"',
            'type="application/ld+json"',
            "'BreadcrumbList'",
            "'citation'",
            "'noindex'",
            "'max-image-preview'",
            "render_source_markers",
            "render_market_context",
        ):
            self.assertIn(marker, frontend)

        self.assertIn('dir="<?php echo esc_attr( $complete99_museum_dir ); ?>"', template)
        self.assertIn('id="c99-main" tabindex="-1"', template)
        self.assertIn("Complete99_Frontend::render_document_head();", template)
        self.assertIn("Complete99_Consumer::render_header(", template)
        self.assertIn("$complete99_museum_bundle['alternates']", template)
        self.assertIn("'museum'", template)
        self.assertIn("@media (max-width: 920px)", css)
        self.assertIn("@media (max-width: 680px)", css)
        self.assertIn("@media (prefers-reduced-motion: reduce)", css)
        self.assertGreaterEqual(css.count("min-height: 44px"), 3)

    def test_new_public_files_contain_no_em_dash(self) -> None:
        for path in (FRONTEND, SITEMAP, TEMPLATE, CSS):
            self.assertNotIn("\N{EM DASH}", path.read_text(encoding="utf-8"), path)

    def test_consumer_chrome_links_to_the_bilingual_museum_projection(self) -> None:
        consumer = CONSUMER.read_text(encoding="utf-8")
        self.assertGreaterEqual(
            consumer.count("public_museum_root_projection( $lang )"), 2
        )
        self.assertIn("'museum', $is_he ? 'מוזיאון הקולינריה'", consumer)
        self.assertIn("isset( $link[2] ) ? $link[2]", consumer)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for route evaluation")
    def test_route_resolution_and_metadata_fail_closed(self) -> None:
        frontend_path = json.dumps(FRONTEND.as_posix())
        plugin_dir = json.dumps(f"{PLUGIN.as_posix()}/")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', {plugin_dir});
define('COMPLETE99_PLATFORM_URL', 'https://complete99.example/plugin/');
define('COMPLETE99_PLATFORM_VERSION', 'test');

function add_filter($hook, $callback, $priority = 10) {{}}
function add_action($hook, $callback, $priority = 10) {{}}
function wp_unslash($value) {{ return $value; }}
function wp_parse_url($url, $component = -1) {{
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}}
function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function wp_strip_all_tags($value) {{ return strip_tags((string) $value); }}
function sanitize_html_class($value) {{
    return preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $value));
}}
function remove_action($hook, $callback, $priority = 10) {{}}
function esc_attr($value) {{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}}
function esc_url($value) {{ return esc_attr($value); }}
function esc_url_raw($value, $protocols = null) {{ return (string) $value; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
function status_header($status) {{}}

class Complete99_Culinary_Science {{
    public static function public_page_bundle_for_path($path) {{
        if ('/ingredients/kombu/' !== $path) {{
            return array();
        }}
        return array(
            'schema' => 'complete99-culinary-science-page-bundle/v1',
            'version' => 'test',
            'language' => 'he',
            'entity' => array(
                'id' => 'ingredient-kombu',
                'type' => 'ingredient',
                'name' => 'Kombu',
                'summary' => 'Reviewed kombu context.',
                'seo' => array(
                    'route_mode' => 'standalone',
                    'canonical_path' => '/ingredients/kombu/',
                    'title' => 'Kombu | Complete99',
                    'h1' => 'Kombu',
                    'meta_description' => 'Reviewed kombu evidence.',
                    'schema_type' => 'DefinedTerm',
                    'visible_breadcrumbs' => array(
                        array('key' => 'home', 'label' => 'Home', 'path' => '/en/'),
                        array('key' => 'kombu', 'label' => 'Kombu', 'path' => '/ingredients/kombu/'),
                    ),
                ),
                'sources' => array(array(
                    'id' => 'source-one',
                    'type' => 'peer_reviewed',
                    'publisher' => 'Journal',
                    'title' => 'Study',
                    'url' => 'https://journal.example/study',
                    'published_at' => '2024-01-01',
                    'retrieved_at' => '2026-08-06',
                )),
                'trust' => array('substantive_updated_at' => '2026-08-06'),
                'visual' => array(),
            ),
            'sections' => array(),
            'canonical_path' => '/ingredients/kombu/',
            'canonical_url' => 'https://complete99.example/ingredients/kombu/',
            'alternates' => array(
                'he' => 'https://complete99.example/ingredients/kombu/',
                'en' => 'https://complete99.example/en/ingredients/kombu/',
                'x-default' => 'https://complete99.example/ingredients/kombu/',
            ),
            'indexable' => false,
        );
    }}
}}

require {frontend_path};

function resolve_museum($method, $uri) {{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $wp = (object) array('query_vars' => array());
    Complete99_Culinary_Museum_Frontend::capture_request($wp);
    return array(
        'active' => Complete99_Culinary_Museum_Frontend::is_museum_request(),
        'query' => $wp->query_vars,
    );
}}

$exact = resolve_museum('GET', '/ingredients/kombu/');
$title = Complete99_Culinary_Museum_Frontend::document_title('fallback');
$robots = Complete99_Culinary_Museum_Frontend::robots(array('index' => true));
ob_start();
Complete99_Culinary_Museum_Frontend::head_metadata();
$head = ob_get_clean();
$post = resolve_museum('POST', '/ingredients/kombu/');
$case = resolve_museum('GET', '/Ingredients/kombu/');
$double = resolve_museum('GET', '/ingredients//kombu/');
$headless = resolve_museum('HEAD', '/ingredients/kombu/');

echo json_encode(array(
    'exact' => $exact,
    'title' => $title,
    'robots' => $robots,
    'head' => $head,
    'post' => $post,
    'case' => $case,
    'double' => $double,
    'headless' => $headless,
), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
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
        result = json.loads(completed.stdout)
        self.assertTrue(result["exact"]["active"])
        self.assertEqual("ingredient-kombu", result["exact"]["query"]["complete99_culinary_museum"])
        self.assertEqual("Kombu | Complete99", result["title"])
        self.assertTrue(result["robots"]["noindex"])
        self.assertTrue(result["robots"]["follow"])
        self.assertNotIn("index", result["robots"])
        self.assertIn('rel="canonical"', result["head"])
        self.assertEqual(3, result["head"].count('rel="alternate"'))
        self.assertIn('type="application/ld+json"', result["head"])
        self.assertFalse(result["post"]["active"])
        self.assertFalse(result["case"]["active"])
        self.assertFalse(result["double"]["active"])
        self.assertTrue(result["headless"]["active"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for sitemap evaluation")
    def test_sitemap_includes_only_exact_indexable_same_site_urls(self) -> None:
        sitemap_path = json.dumps(SITEMAP.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
abstract class WP_Sitemaps_Provider {{
    protected $name = '';
    protected $object_type = '';
    abstract public function get_url_list($page_num, $object_subtype = '');
    abstract public function get_max_num_pages($object_subtype = '');
}}
class Complete99_Culinary_Science {{
    public static function public_indexable_page_projections() {{
        return array(
            array(
                'canonical_url' => 'https://complete99.example/ingredients/kombu/',
                'indexable' => true,
                'trust' => array('substantive_updated_at' => '2026-08-06'),
            ),
            array(
                'seo' => array('canonical_path' => '/en/ingredients/kombu/'),
                'indexable' => true,
                'reviewed_at' => '2026-08-05',
            ),
            array(
                'canonical_url' => 'https://complete99.example/ingredients/kombu/',
                'indexable' => true,
            ),
            array(
                'canonical_url' => 'https://complete99.example/ingredients/private/',
                'indexable' => false,
            ),
            array(
                'canonical_url' => 'https://offsite.example/ingredient/',
                'indexable' => true,
            ),
        );
    }}
}}
class RegistryStub {{
    public $name = '';
    public function add_provider($name, $provider) {{
        $this->name = $name;
        return true;
    }}
}}
function add_action($hook, $callback) {{}}
function home_url($path = '') {{ return 'https://complete99.example/' . ltrim((string) $path, '/'); }}
function wp_parse_url($url, $component = -1) {{
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}}
function wp_sitemaps_get_max_urls($object_type) {{ return 100; }}
require {sitemap_path};
$provider = new Complete99_Culinary_Museum_Sitemap_Provider();
$server = (object) array('registry' => new RegistryStub());
$registered = Complete99_Culinary_Museum_Sitemap_Provider::register($server);
echo json_encode(array(
    'registered' => $registered,
    'name' => $server->registry->name,
    'pages' => $provider->get_max_num_pages(),
    'urls' => $provider->get_url_list(1),
), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
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
        result = json.loads(completed.stdout)
        self.assertTrue(result["registered"])
        self.assertEqual("completemuseum", result["name"])
        self.assertEqual(1, result["pages"])
        self.assertEqual(
            [
                "https://complete99.example/en/ingredients/kombu/",
                "https://complete99.example/ingredients/kombu/",
            ],
            [entry["loc"] for entry in result["urls"]],
        )
        self.assertTrue(all("lastmod" in entry for entry in result["urls"]))


if __name__ == "__main__":
    unittest.main()
