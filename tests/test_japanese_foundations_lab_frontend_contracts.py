from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
FRONTEND = PLUGIN / "includes" / "class-complete99-culinary-museum-frontend.php"
SCIENCE = PLUGIN / "includes" / "class-complete99-culinary-science.php"
CSS = PLUGIN / "assets" / "css" / "culinary-museum.css"
JS = PLUGIN / "assets" / "js" / "public.js"


class JapaneseFoundationsLabFrontendContracts(unittest.TestCase):
    def test_renderer_is_projection_only_and_member_cards_are_server_rendered(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        renderer = frontend.split(
            "private static function render_collection_page", 1
        )[1].split("private static function collection_count_label", 1)[0]
        validator = frontend.split(
            "private static function approved_collection_projection", 1
        )[1].split("private static function schema_graph", 1)[0]

        self.assertIn("complete99-culinary-collection-public/v1", frontend)
        self.assertIn("$bundle['collection']", frontend)
        self.assertIn("data-c99-foundations-member", renderer)
        self.assertIn("self::collection_member_url( $member )", renderer)
        self.assertIn("data-c99-foundations-filter-count", renderer)
        self.assertEqual(1, renderer.count("data-c99-foundations-filter-reset"))
        self.assertEqual(1, renderer.count("data-c99-foundations-filter-button"))
        self.assertNotIn("render_offer", renderer)
        self.assertNotIn("render_market_context", renderer)
        self.assertNotIn("render_connections", renderer)
        self.assertNotIn("Complete99_Culinary_Science", renderer)
        self.assertIn("'approved_public'", validator)
        self.assertIn("'owner_entity_id'", validator)
        self.assertIn("'route_mode'", validator)
        self.assertIn("'parity_member_ids'", validator)
        self.assertIn("'supplier'", validator)
        self.assertIn("'market_observation'", validator)
        self.assertIn("'guide_edition'", validator)
        self.assertIn("'visual_asset'", validator)
        self.assertIn("'compliance_rule'", validator)

    def test_filters_have_target_size_keyboard_and_clean_address_contracts(self) -> None:
        css = CSS.read_text(encoding="utf-8")
        js = JS.read_text(encoding="utf-8")

        self.assertIn("[data-c99-foundations-filter]", js)
        self.assertIn("foundation-group", js)
        self.assertIn("data-c99-foundations-canonical-url", js)
        self.assertIn("window.history.replaceState", js)
        self.assertIn("url.pathname + url.search + url.hash", js)
        self.assertIn("setAttribute('aria-pressed'", js)
        self.assertIn("var isRtl", js)
        self.assertIn("isRtl ? -1 : 1", js)
        self.assertIn("isRtl ? 1 : -1", js)
        for key in (
            "ArrowRight",
            "ArrowDown",
            "ArrowLeft",
            "ArrowUp",
            "Home",
            "End",
        ):
            self.assertIn(key, js)

        filter_rule = css.split(".c99-foundations-filter {", 1)[1].split("}", 1)[0]
        self.assertIn("min-width: 44px", filter_rule)
        self.assertIn("min-height: 44px", filter_rule)
        self.assertIn(".c99-foundations-filter:focus-visible", css)
        filter_focus = css.split(".c99-foundations-filter:focus-visible {", 1)[1].split("}", 1)[0]
        card_focus = css.split(".c99-foundations-card a:focus-visible {", 1)[1].split("}", 1)[0]
        self.assertIn("var(--c99-museum-rust)", filter_focus)
        self.assertIn("var(--c99-museum-rust)", card_focus)
        self.assertIn(".c99-foundations-card[hidden]", css)
        self.assertIn("@media (prefers-reduced-motion: reduce)", css)

    def test_changed_frontend_files_have_no_em_dash(self) -> None:
        for path in (FRONTEND, CSS, JS, Path(__file__)):
            self.assertNotIn("\N{EM DASH}", path.read_text(encoding="utf-8"), path)

    def test_lab_hero_has_detailed_bilingual_alt_text(self) -> None:
        science = SCIENCE.read_text(encoding="utf-8")
        self.assertIn("'hub-japanese-foundations-lab'", science)
        self.assertIn("Japanese culinary foundations table with rice in a hangiri", science)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for renderer evaluation")
    def test_bilingual_lab_html_schema_and_fail_closed_projection(self) -> None:
        frontend_path = json.dumps(FRONTEND.as_posix())
        script = f"""
define('ABSPATH', __DIR__);

function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function wp_parse_url($url, $component = -1) {{
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}}
function sanitize_html_class($value) {{
    return preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $value));
}}
function absint($value) {{ return abs((int) $value); }}
function esc_attr($value) {{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}}
function esc_url($value) {{ return esc_attr($value); }}
function esc_html($value) {{ return esc_attr($value); }}
function esc_url_raw($value, $protocols = null) {{ return (string) $value; }}
function number_format_i18n($value, $decimals = 0) {{
    return number_format((float) $value, (int) $decimals, '.', ',');
}}

require {frontend_path};

function c99_groups($lang) {{
    if ('he' === $lang) {{
        return array(
            array('id' => 'ingredients', 'label' => 'חומרי גלם', 'description' => 'חומרי הגלם שמניעים את הטעם.'),
            array('id' => 'food_science', 'label' => 'מדע המזון', 'description' => 'המולקולות והתגובות שמאחורי המנה.'),
            array('id' => 'techniques', 'label' => 'טכניקות', 'description' => 'שיטות עבודה מדויקות במטבח.'),
            array('id' => 'equipment', 'label' => 'ציוד', 'description' => 'כלים מקצועיים להכנה מדויקת.'),
        );
    }}
    return array(
        array('id' => 'ingredients', 'label' => 'Ingredients', 'description' => 'Ingredients that shape flavor.'),
        array('id' => 'food_science', 'label' => 'Food science', 'description' => 'Molecules and reactions behind the dish.'),
        array('id' => 'techniques', 'label' => 'Techniques', 'description' => 'Precise culinary methods.'),
        array('id' => 'equipment', 'label' => 'Equipment', 'description' => 'Professional tools for precise preparation.'),
    );
}}

function c99_members($lang) {{
    $prefix = 'en' === $lang ? '/en' : '';
    $names = 'he' === $lang
        ? array('קומבו', 'סינרגיית אומאמי', 'מיצוי דאשי', 'מגררת ווסאבי')
        : array('Kombu', 'Umami synergy', 'Dashi extraction', 'Wasabi grater');
    return array(
        array(
            'id' => 'ingredient-kombu',
            'group_id' => 'ingredients',
            'name' => $names[0],
            'summary' => 'A public culinary summary for kombu.',
            'entity_type' => 'ingredient',
            'canonical_path' => $prefix . '/ingredients/kombu/',
            'owner_entity_id' => 'ingredient-kombu',
            'route_mode' => 'standalone',
            'approved_public' => true,
        ),
        array(
            'id' => 'reaction-umami-synergy',
            'group_id' => 'food_science',
            'name' => $names[1],
            'summary' => 'A public culinary summary for umami synergy.',
            'entity_type' => 'reaction',
            'canonical_path' => $prefix . '/museum/japanese-culinary-science/food-science/',
            'owner_entity_id' => 'hub-japanese-food-science',
            'route_mode' => 'section',
            'approved_public' => true,
            'fragment' => 'umami-synergy',
        ),
        array(
            'id' => 'technique-dashi-extraction',
            'group_id' => 'techniques',
            'name' => $names[2],
            'summary' => 'A public culinary summary for dashi extraction.',
            'entity_type' => 'technique',
            'canonical_path' => $prefix . '/techniques/dashi-extraction/',
            'owner_entity_id' => 'technique-dashi-extraction',
            'route_mode' => 'standalone',
            'approved_public' => true,
        ),
        array(
            'id' => 'equipment-wasabi-grater',
            'group_id' => 'equipment',
            'name' => $names[3],
            'summary' => 'A public culinary summary for a wasabi grater.',
            'entity_type' => 'equipment',
            'canonical_path' => $prefix . '/equipment/wasabi-grater/',
            'owner_entity_id' => 'equipment-wasabi-grater',
            'route_mode' => 'standalone',
            'approved_public' => true,
        ),
    );
}}

function c99_bundle($lang) {{
    $he_path = '/museum/japanese-culinary-science/foundations/';
    $en_path = '/en/museum/japanese-culinary-science/foundations/';
    $path = 'he' === $lang ? $he_path : $en_path;
    $other = 'he' === $lang ? $en_path : $he_path;
    $ids = array(
        'ingredient-kombu',
        'reaction-umami-synergy',
        'technique-dashi-extraction',
        'equipment-wasabi-grater',
    );
    return array(
        'schema' => 'complete99-culinary-science-page-bundle/v1',
        'version' => 'test',
        'language' => $lang,
        'entity' => array(
            'id' => 'hub-japanese-foundations-lab',
            'type' => 'topic_hub',
            'name' => 'he' === $lang ? 'מעבדת יסודות המטבח היפני' : 'Japanese Foundations Lab',
            'summary' => 'A connected public collection.',
            'seo' => array(
                'route_mode' => 'standalone',
                'canonical_path' => $path,
                'title' => 'Japanese Foundations Lab | Complete99',
                'h1' => 'he' === $lang ? 'יסודות המטבח היפני' : 'Japanese culinary foundations',
                'opening' => 'A connected guide to ingredients, science, techniques and equipment.',
                'meta_description' => 'A connected public collection of Japanese culinary foundations.',
                'schema_type' => 'CollectionPage',
                'visible_breadcrumbs' => array(
                    array('key' => 'home', 'label' => 'Home', 'path' => 'he' === $lang ? '/' : '/en/'),
                    array('key' => 'lab', 'label' => 'Foundations', 'path' => $path),
                ),
            ),
            'profiles' => array(),
            'facts' => array(),
            'sources' => array(),
            'relations' => array(),
            'internal_links' => array(),
            'market_context' => array(),
            'offer' => array(),
            'taxonomy' => array(),
            'safety_notes' => array(),
            'trust' => array(
                'substantive_updated_at' => '2026-08-06',
                'research_method' => 'Reviewed public sources.',
                'next_review_trigger' => '',
                'correction_path' => '',
            ),
            'visual' => array(),
            'reviewed_at' => '2026-08-06',
        ),
        'sections' => array(),
        'canonical_path' => $path,
        'canonical_url' => home_url($path),
        'alternates' => array(
            'he' => home_url($he_path),
            'en' => home_url($en_path),
            'x-default' => home_url($he_path),
        ),
        'indexable' => false,
        'collection' => array(
            'schema' => 'complete99-culinary-collection-public/v1',
            'key' => 'japanese-foundations-lab',
            'language' => $lang,
            'translation_group_id' => 'japanese-foundations-lab',
            'canonical_path' => $path,
            'alternate_path' => $other,
            'approved_public' => true,
            'groups' => c99_groups($lang),
            'members' => c99_members($lang),
            'parity_member_ids' => array('he' => $ids, 'en' => $ids),
        ),
    );
}}

$validate = new ReflectionMethod('Complete99_Culinary_Museum_Frontend', 'is_renderable_bundle');
$validate->setAccessible(true);
$schema = new ReflectionMethod('Complete99_Culinary_Museum_Frontend', 'schema_graph');
$schema->setAccessible(true);
$projection = new ReflectionMethod('Complete99_Culinary_Museum_Frontend', 'approved_collection_projection');
$projection->setAccessible(true);

function c99_render($bundle) {{
    ob_start();
    Complete99_Culinary_Museum_Frontend::render_page($bundle);
    return ob_get_clean();
}}

$he = c99_bundle('he');
$en = c99_bundle('en');
$ordinary = $en;
unset($ordinary['collection']);
$cost_canary = $en;
$cost_canary['collection']['members'][0]['cost'] = 'PRIVATE-COST-CANARY';
$supplier_canary = $en;
$supplier_canary['collection']['members'][0]['entity_type'] = 'supplier';
$parity_canary = $en;
$parity_canary['collection']['parity_member_ids']['en'] = array('ingredient-kombu');

echo json_encode(array(
    'he_valid' => $validate->invoke(null, $he, $he['canonical_path']),
    'en_valid' => $validate->invoke(null, $en, $en['canonical_path']),
    'ordinary_valid' => $validate->invoke(null, $ordinary, $ordinary['canonical_path']),
    'he_html' => c99_render($he),
    'en_html' => c99_render($en),
    'en_schema' => $schema->invoke(null, $en),
    'cost_valid' => $validate->invoke(null, $cost_canary, $cost_canary['canonical_path']),
    'cost_projection' => $projection->invoke(null, $cost_canary),
    'supplier_valid' => $validate->invoke(null, $supplier_canary, $supplier_canary['canonical_path']),
    'parity_valid' => $validate->invoke(null, $parity_canary, $parity_canary['canonical_path']),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=30,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)

        self.assertTrue(result["he_valid"])
        self.assertTrue(result["en_valid"])
        self.assertTrue(result["ordinary_valid"])
        self.assertFalse(result["cost_valid"])
        self.assertEqual([], result["cost_projection"])
        self.assertFalse(result["supplier_valid"])
        self.assertFalse(result["parity_valid"])

        for language, html in (("he", result["he_html"]), ("en", result["en_html"])):
            self.assertEqual(4, html.count("data-c99-foundations-filter-button="), language)
            self.assertEqual(4, html.count("data-c99-foundations-member"), language)
            self.assertEqual(1, html.count("data-c99-foundations-filter-reset"), language)
            self.assertEqual(5, html.count('aria-pressed="'), language)
            self.assertIn("foundation", html.lower(), language)
            self.assertNotIn("PRIVATE-COST-CANARY", html, language)
            self.assertNotIn("ChatGPT", html, language)

        self.assertIn("4 נושאים מוצגים", result["he_html"])
        self.assertIn("4 topics shown", result["en_html"])
        self.assertIn(
            "https://complete99.example/en/museum/japanese-culinary-science/food-science/#umami-synergy",
            result["en_html"],
        )

        graph = result["en_schema"]["@graph"]
        page = next(node for node in graph if node.get("@type") == "CollectionPage")
        item_list = next(node for node in graph if node.get("@type") == "ItemList")
        self.assertEqual(4, item_list["numberOfItems"])
        self.assertEqual(
            {"@id": item_list["@id"]},
            page["mainEntity"],
        )
        urls = [entry["item"]["url"] for entry in item_list["itemListElement"]]
        self.assertEqual(
            [
                "https://complete99.example/en/ingredients/kombu/",
                "https://complete99.example/en/museum/japanese-culinary-science/food-science/#umami-synergy",
                "https://complete99.example/en/techniques/dashi-extraction/",
                "https://complete99.example/en/equipment/wasabi-grater/",
            ],
            urls,
        )
        self.assertTrue(all("?" not in url for url in urls))
        self.assertNotIn("PRIVATE-COST-CANARY", json.dumps(graph))

    @unittest.skipUnless(shutil.which("php"), "PHP is required for integration evaluation")
    def test_real_registry_bundle_activates_the_lab_in_hebrew_and_english(self) -> None:
        frontend_path = json.dumps(FRONTEND.as_posix())
        science_path = json.dumps(SCIENCE.as_posix())
        plugin_dir = json.dumps(f"{PLUGIN.as_posix()}/")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', {plugin_dir});
define('COMPLETE99_PLATFORM_URL', 'https://complete99.example/plugin/');

class WP_Error {{
    private $code;
    private $message;
    private $data;
    public function __construct($code, $message, $data = array()) {{
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }}
    public function get_error_code() {{ return $this->code; }}
    public function get_error_message() {{ return $this->message; }}
    public function get_error_data() {{ return $this->data; }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function wp_parse_url($url, $component = -1) {{
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}}
function sanitize_html_class($value) {{
    return preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $value));
}}
function absint($value) {{ return abs((int) $value); }}
function esc_attr($value) {{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}}
function esc_url($value) {{ return esc_attr($value); }}
function esc_html($value) {{ return esc_attr($value); }}
function esc_url_raw($value, $protocols = null) {{ return (string) $value; }}
function number_format_i18n($value, $decimals = 0) {{
    return number_format((float) $value, (int) $decimals, '.', ',');
}}

require {science_path};
require {frontend_path};

$he = Complete99_Culinary_Science::public_page_bundle_for_path(
    '/museum/japanese-culinary-science/foundations/'
);
$en = Complete99_Culinary_Science::public_page_bundle_for_path(
    '/en/museum/japanese-culinary-science/foundations/'
);
$projection = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'approved_collection_projection'
);
$projection->setAccessible(true);
$schema = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'schema_graph'
);
$schema->setAccessible(true);

function c99_real_render($bundle) {{
    ob_start();
    Complete99_Culinary_Museum_Frontend::render_page($bundle);
    return ob_get_clean();
}}

echo json_encode(array(
    'he' => $he,
    'en' => $en,
    'he_projection' => $projection->invoke(null, $he),
    'en_projection' => $projection->invoke(null, $en),
    'he_html' => c99_real_render($he),
    'en_html' => c99_real_render($en),
    'en_schema' => $schema->invoke(null, $en),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=45,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)

        for language in ("he", "en"):
            bundle = result[language]
            projection = result[f"{language}_projection"]
            html = result[f"{language}_html"]
            self.assertEqual("japanese-foundations-lab", projection["key"])
            self.assertTrue(projection["approved_public"])
            self.assertEqual(15, len(projection["members"]))
            self.assertEqual(
                ["ingredients", "food_science", "techniques", "equipment"],
                [group["id"] for group in projection["groups"]],
            )
            self.assertEqual(
                projection["parity_member_ids"]["he"],
                projection["parity_member_ids"]["en"],
            )
            self.assertEqual(15, html.count("data-c99-foundations-member"))
            self.assertEqual(4, html.count("data-c99-foundations-filter-button="))
            self.assertIn("c99-science-japanese-foundations-lab-v01.webp", html)
            public_json = json.dumps(bundle, ensure_ascii=False).lower()
            for forbidden in (
                "prompt_en",
                "negative_prompt_en",
                "landed_cost",
                "margin_scenario",
                "supplier_record",
            ):
                self.assertNotIn(forbidden, public_json)

        self.assertTrue(any("א" <= char <= "ת" for char in result["he_html"]))
        self.assertIn("Japanese Foundations Lab", result["en_html"])
        graph = result["en_schema"]["@graph"]
        item_list = next(node for node in graph if node.get("@type") == "ItemList")
        self.assertEqual(15, item_list["numberOfItems"])
        self.assertTrue(
            all(
                entry["item"]["url"].startswith("https://complete99.example/en/")
                for entry in item_list["itemListElement"]
            )
        )


if __name__ == "__main__":
    unittest.main()
