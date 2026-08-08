from __future__ import annotations

import json
import re
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
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"


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
        self.assertIn("private static function render_offer", frontend)
        self.assertIn("wc_get_product_id_by_sku( $code )", frontend)
        self.assertIn("'_complete99_live_catalog_managed'", frontend)
        self.assertIn("'_complete99_catalog_product_code'", frontend)
        self.assertIn("Complete99_Commerce::PRODUCT_APPROVED", frontend)
        self.assertIn("Complete99_Commerce::catalog_is_ready()", frontend)
        self.assertIn("private static function market_value_label", frontend)
        self.assertIn("self::market_value_label( $item['availability'], $is_he )", frontend)
        self.assertIn("private static function relationship_label", frontend)
        self.assertEqual(2, frontend.count("self::relationship_label("))
        self.assertIn("מסלולים להמשך", frontend)
        self.assertIn("Ways to explore", frontend)
        self.assertNotIn("$is_he ? 'Hubs & Spokes' : 'Hubs & Spokes'", frontend)

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
            "render_offer",
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
        self.assertRegex(
            css,
            r"(?s)\.c99-museum-breadcrumbs a\s*\{[^}]*min-width:\s*44px",
        )
        self.assertRegex(
            css,
            r"(?s)\.c99-museum-citation a\s*\{[^}]*min-width:\s*44px[^}]*min-height:\s*44px",
        )

    def test_trust_copy_addresses_the_reader_not_an_internal_workflow(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        science = SCIENCE_DATA.read_text(encoding="utf-8")

        self.assertIn("How we check the information", frontend)
        self.assertIn("איך אנחנו בודקים את המידע", frontend)
        self.assertNotIn("What triggers a new review", frontend)
        self.assertNotIn("מה מפעיל בדיקה מחדש", frontend)

        self.assertIn("source you can open and read", science)
        self.assertIn("contact page", science)
        self.assertNotIn("Each fact is stored with an evidence class", science)
        self.assertNotIn("A source update, standard change", science)
        self.assertIn("Page details", frontend)
        self.assertIn("פרטי העמוד", frontend)
        self.assertNotIn("Dossier details", frontend)
        self.assertNotIn("פרטי התיק", frontend)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for label evaluation")
    def test_dashi_and_emitted_relationship_labels_are_fully_bilingual(self) -> None:
        frontend_path = json.dumps(FRONTEND.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
require {frontend_path};

$entity_type_label = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'entity_type_label'
);
$entity_type_label->setAccessible(true);
$relationship_label = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'relationship_label'
);
$relationship_label->setAccessible(true);

function c99_label_values($method, $values, $lang) {{
    $labels = array();
    foreach ($values as $value) {{
        $labels[$value] = $method->invoke(null, $value, $lang);
    }}
    return $labels;
}}

$entity_types = array('preparation', 'guide');
$relationships = array(
    'parent-context',
    'child-discovery',
    'curated-discovery',
    'cross-sell',
    'up-sell',
    'related-complements',
    'related-requires',
    'related-used_in',
    'complements',
    'requires',
    'used_in',
    'unknown-machine-purpose'
);

echo json_encode(array(
    'entity_he' => c99_label_values($entity_type_label, $entity_types, 'he'),
    'entity_en' => c99_label_values($entity_type_label, $entity_types, 'en'),
    'relationship_he' => c99_label_values($relationship_label, $relationships, 'he'),
    'relationship_en' => c99_label_values($relationship_label, $relationships, 'en'),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
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
        self.assertEqual(
            {"preparation": "הכנה", "guide": "מדריך"},
            result["entity_he"],
        )
        self.assertEqual(
            {"preparation": "Preparation", "guide": "Guide"},
            result["entity_en"],
        )
        self.assertEqual(
            {
                "parent-context": "חזרה לנושא האב",
                "child-discovery": "המשך לתת-נושא",
                "curated-discovery": "המשך מומלץ",
                "cross-sell": "השלמה קולינרית",
                "up-sell": "חלופת פרימיום",
                "related-complements": "משלים",
                "related-requires": "דורש",
                "related-used_in": "משמש בתוך",
                "complements": "משלים",
                "requires": "דורש",
                "used_in": "משמש בתוך",
                "unknown-machine-purpose": "קשר נוסף",
            },
            result["relationship_he"],
        )
        self.assertEqual(
            {
                "parent-context": "Parent topic",
                "child-discovery": "Explore subtopic",
                "curated-discovery": "Recommended next",
                "cross-sell": "Culinary pairing",
                "up-sell": "Premium alternative",
                "related-complements": "Complements",
                "related-requires": "Requires",
                "related-used_in": "Used in",
                "complements": "Complements",
                "requires": "Requires",
                "used_in": "Used in",
                "unknown-machine-purpose": "Unknown Machine Purpose",
            },
            result["relationship_en"],
        )
        self.assertNotIn("Preparation", result["entity_he"].values())
        self.assertNotIn("RELATED REQUIRES", result["relationship_he"].values())

    def test_every_controlled_entity_and_relation_has_a_bilingual_label(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        registry = SCIENCE_DATA.read_text(encoding="utf-8")
        entity_block = frontend.split(
            "private static function entity_type_label", 1
        )[1].split("private static function relationship_label", 1)[0]
        relation_block = frontend.split(
            "private static function relationship_label", 1
        )[1].split("private static function machine_label", 1)[0]

        entity_vocabulary = re.search(
            r"'entity_types'\s*=>\s*array\((.*?)\)", registry, re.DOTALL
        )
        relation_vocabulary = re.search(
            r"'relation_types'\s*=>\s*array\((.*?)\)", registry, re.DOTALL
        )
        self.assertIsNotNone(entity_vocabulary)
        self.assertIsNotNone(relation_vocabulary)

        entity_types = re.findall(r"'([a-z_]+)'", entity_vocabulary.group(1))
        relation_types = re.findall(r"'([a-z_]+)'", relation_vocabulary.group(1))
        self.assertEqual(28, len(entity_types))
        self.assertEqual(22, len(relation_types))
        for entity_type in entity_types:
            self.assertIn(f"'{entity_type}'", entity_block, entity_type)
        for relation_type in relation_types:
            normalized = relation_type.replace("_", "-")
            self.assertIn(f"'{normalized}'", relation_block, relation_type)
        for purpose in (
            "parent-context",
            "child-discovery",
            "curated-discovery",
            "cross-sell",
            "up-sell",
        ):
            self.assertIn(f"'{purpose}'", relation_block, purpose)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for label evaluation")
    def test_current_public_taxonomy_and_evidence_labels_are_localized(self) -> None:
        frontend_path = json.dumps(FRONTEND.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
require {frontend_path};

$bundle = new ReflectionProperty(
    'Complete99_Culinary_Museum_Frontend',
    'bundle'
);
$bundle->setAccessible(true);
$bundle->setValue(null, array('language' => 'he'));
$machine_label = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'machine_label'
);
$machine_label->setAccessible(true);
$evidence_label = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'evidence_label'
);
$evidence_label->setAccessible(true);

$taxonomy_values = array(
    'pa-region',
    'pa-community',
    'cuisines',
    'syrian-culinary-science',
    'syrian-cuisine',
    'syria-national',
    'syrian-multi-community',
    'japanese-food-science',
    'dashi-ingredients',
    'controlled-water-extraction',
    'fresh-aromatics',
    'refrigerated-perishable',
    'seaweed',
    'seasonality',
    'smoking',
    'imp',
    'aitc',
    't1r1-t1r3'
);
$taxonomy = array();
foreach ($taxonomy_values as $value) {{
    $taxonomy[$value] = $machine_label->invoke(null, $value);
}}
echo json_encode(array(
    'taxonomy' => $taxonomy,
    'regulatory_standard' => $evidence_label->invoke(null, 'regulatory_standard', 'he'),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
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
        self.assertEqual("אזור", result["taxonomy"]["pa-region"])
        self.assertEqual(
            "מסורת וקהילה", result["taxonomy"]["pa-community"]
        )
        self.assertEqual("מטבחי עולם", result["taxonomy"]["cuisines"])
        self.assertEqual(
            "המטבח הסורי", result["taxonomy"]["syrian-culinary-science"]
        )
        self.assertEqual(
            "מטבח סורי", result["taxonomy"]["syrian-cuisine"]
        )
        self.assertEqual("סוריה", result["taxonomy"]["syria-national"])
        self.assertEqual(
            "מסורות סוריות רב קהילתיות",
            result["taxonomy"]["syrian-multi-community"],
        )
        self.assertEqual("מדע האוכל היפני", result["taxonomy"]["japanese-food-science"])
        self.assertEqual("חומרי גלם לדאשי", result["taxonomy"]["dashi-ingredients"])
        self.assertEqual("מיצוי מבוקר במים", result["taxonomy"]["controlled-water-extraction"])
        self.assertEqual("ארומטים טריים", result["taxonomy"]["fresh-aromatics"])
        self.assertEqual("מתכלה בקירור", result["taxonomy"]["refrigerated-perishable"])
        self.assertEqual("אצת ים", result["taxonomy"]["seaweed"])
        self.assertEqual("עונתיות", result["taxonomy"]["seasonality"])
        self.assertEqual("עישון", result["taxonomy"]["smoking"])
        self.assertEqual("IMP", result["taxonomy"]["imp"])
        self.assertEqual("AITC", result["taxonomy"]["aitc"])
        self.assertEqual("T1R1/T1R3", result["taxonomy"]["t1r1-t1r3"])
        self.assertEqual("תקן רגולטורי", result["regulatory_standard"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for offer evaluation")
    def test_offer_renderer_requires_managed_approved_matching_woo_product(self) -> None:
        frontend_path = json.dumps(FRONTEND.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', 'C:/complete99/');
define('COMPLETE99_PLATFORM_URL', 'https://complete99.example/plugin/');
define('COMPLETE99_PLATFORM_VERSION', 'test');

function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function absint($value) {{ return abs((int) $value); }}
function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function esc_attr($value) {{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}}
function esc_url($value) {{ return esc_attr($value); }}
function esc_html($value) {{ return esc_attr($value); }}
function wp_kses_post($value) {{ return (string) $value; }}

class Complete99_Commerce {{
    const PRODUCT_APPROVED = '_complete99_store_approved';
    const NAME_HE = '_complete99_name_he';
    const NAME_EN = '_complete99_name_en';
    public static function catalog_is_ready() {{
        global $c99_offer_mode;
        return 'catalog_not_ready' !== $c99_offer_mode;
    }}
    public static function storefront_product_url($product_code, $lang, $filter = 'all') {{
        return 'https://complete99.example/en/store/?product-page=3#c99-product-code-' . $product_code;
    }}
}}
class C99_Test_Product {{
    public function get_price_html() {{ return '<span>₪89.00</span>'; }}
    public function is_in_stock() {{ return true; }}
}}

$c99_offer_mode = 'valid';
$c99_offer_code = 'product-rishiri-kombu-100g';
function wc_get_product_id_by_sku($sku) {{
    global $c99_offer_mode, $c99_offer_code;
    if ('missing' === $c99_offer_mode || $sku !== $c99_offer_code) {{
        return 0;
    }}
    return 700;
}}
function wc_get_product($product_id) {{
    return 700 === (int) $product_id ? new C99_Test_Product() : false;
}}
function get_post_meta($product_id, $key, $single = false) {{
    global $c99_offer_mode, $c99_offer_code;
    if ('_complete99_live_catalog_managed' === $key) {{
        return 'unmanaged' === $c99_offer_mode ? 'no' : 'yes';
    }}
    if ('_complete99_catalog_product_code' === $key) {{
        return 'mismatch' === $c99_offer_mode ? 'product-other' : $c99_offer_code;
    }}
    if (Complete99_Commerce::PRODUCT_APPROVED === $key) {{
        return 'unapproved' === $c99_offer_mode ? 'no' : 'yes';
    }}
    if (Complete99_Commerce::NAME_HE === $key) {{
        return 'קומבו רישירי טבעי 100 גרם';
    }}
    if (Complete99_Commerce::NAME_EN === $key) {{
        return 'Natural Rishiri kombu 100 g';
    }}
    return '';
}}

require {frontend_path};
$bundle_property = new ReflectionProperty(
    'Complete99_Culinary_Museum_Frontend',
    'bundle'
);
$bundle_property->setAccessible(true);
$bundle_property->setValue(null, array('language' => 'en'));
$render_offer = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'render_offer'
);
$render_offer->setAccessible(true);
$internal_url = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'internal_url'
);
$internal_url->setAccessible(true);

function c99_render_offer_case($method, $mode, $offer) {{
    global $c99_offer_mode;
    $c99_offer_mode = $mode;
    ob_start();
    $method->invoke(null, array('offer' => $offer));
    return ob_get_clean();
}}

$offer = array(
    'product_code' => $c99_offer_code,
    'store_path' => '/en/store/#c99-product-code-' . $c99_offer_code,
    'label' => 'View in the pantry',
);
$preflight = array(
    'code' => sanitize_key($offer['product_code']),
    'product_id' => wc_get_product_id_by_sku($offer['product_code']),
    'managed' => get_post_meta(700, '_complete99_live_catalog_managed', true),
    'catalog_code' => get_post_meta(700, '_complete99_catalog_product_code', true),
    'approved' => get_post_meta(700, Complete99_Commerce::PRODUCT_APPROVED, true),
    'name' => get_post_meta(700, Complete99_Commerce::NAME_EN, true),
    'url' => $internal_url->invoke(null, $offer['store_path']),
);
echo json_encode(array(
    'preflight' => $preflight,
    'valid' => c99_render_offer_case($render_offer, 'valid', $offer),
    'catalog_not_ready' => c99_render_offer_case($render_offer, 'catalog_not_ready', $offer),
    'unmanaged' => c99_render_offer_case($render_offer, 'unmanaged', $offer),
    'mismatch' => c99_render_offer_case($render_offer, 'mismatch', $offer),
    'unapproved' => c99_render_offer_case($render_offer, 'unapproved', $offer),
    'missing' => c99_render_offer_case($render_offer, 'missing', $offer),
    'empty' => c99_render_offer_case($render_offer, 'valid', array()),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
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
        self.assertEqual(
            {
                "code": "product-rishiri-kombu-100g",
                "product_id": 700,
                "managed": "yes",
                "catalog_code": "product-rishiri-kombu-100g",
                "approved": "yes",
                "name": "Natural Rishiri kombu 100 g",
                "url": (
                    "https://complete99.example/en/store/"
                    "#c99-product-code-product-rishiri-kombu-100g"
                ),
            },
            result["preflight"],
        )
        self.assertIn("Natural Rishiri kombu 100 g", result["valid"])
        self.assertIn("₪89.00", result["valid"])
        self.assertIn("In stock", result["valid"])
        self.assertIn(
            "https://complete99.example/en/store/?product-page=3"
            "#c99-product-code-product-rishiri-kombu-100g",
            result["valid"],
        )
        for case in (
            "catalog_not_ready",
            "unmanaged",
            "mismatch",
            "unapproved",
            "missing",
            "empty",
        ):
            self.assertEqual("", result[case], case)

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
