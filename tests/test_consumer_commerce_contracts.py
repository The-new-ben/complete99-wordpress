from __future__ import annotations

import csv
import json
import re
import shutil
import subprocess
import unittest
from pathlib import Path
from urllib.parse import urlparse


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
CONSUMER = PLUGIN / "includes" / "class-complete99-consumer.php"
COMMERCE = PLUGIN / "includes" / "class-complete99-commerce.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
CONTENT = PLUGIN / "data" / "consumer-content.php"
MENU = PLUGIN / "data" / "consumer-menu.php"
KEYWORDS = PLUGIN / "data" / "keyword-ownership.csv"
SHELL = PLUGIN / "templates" / "public-shell.php"
COMMERCE_SHELL = PLUGIN / "templates" / "commerce-shell.php"
REST = PLUGIN / "includes" / "class-complete99-rest.php"
CSS = PLUGIN / "assets" / "css" / "consumer.css"
SCRIPT = PLUGIN / "assets" / "js" / "public.js"
MEDIA_REGISTER = ROOT / "docs" / "media-rights-register.md"


class Complete99ConsumerCommerceContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.consumer = CONSUMER.read_text(encoding="utf-8")
        cls.commerce = COMMERCE.read_text(encoding="utf-8")
        cls.frontend = FRONTEND.read_text(encoding="utf-8")
        cls.content = CONTENT.read_text(encoding="utf-8")
        cls.menu = MENU.read_text(encoding="utf-8")
        cls.shell = SHELL.read_text(encoding="utf-8")
        cls.commerce_shell = COMMERCE_SHELL.read_text(encoding="utf-8")
        cls.rest = REST.read_text(encoding="utf-8")
        cls.css = CSS.read_text(encoding="utf-8")
        cls.script = SCRIPT.read_text(encoding="utf-8")

    def test_public_shell_and_legacy_entry_points_use_consumer_renderer(self) -> None:
        for marker in (
            "Complete99_Consumer::render_header",
            "Complete99_Consumer::render_current",
            "Complete99_Consumer::render_footer",
        ):
            self.assertIn(marker, self.shell)
            self.assertIn(marker, self.frontend)
        self.assertNotIn("Complete99_Frontend::render_current", self.shell)

    def test_release_keeps_role_definitions_dormant(self) -> None:
        platform = (
            PLUGIN / "includes" / "class-complete99-platform.php"
        ).read_text(encoding="utf-8")
        migration = platform[platform.index("private static function run_migration") :]
        self.assertNotIn("Complete99_Content::install_roles();", migration)
        self.assertIn("Role definitions remain dormant", platform)

    def test_navigation_is_consumer_only_and_every_destination_is_owned(self) -> None:
        header = self.consumer.split("public static function render_header", 1)[1].split(
            "private static function render_language_switch", 1
        )[0]
        expected = {
            "dishes",
            "proposal",
            "traditions",
            "knowledge",
            "store",
            "about",
            "contact",
        }
        for key in expected:
            self.assertIn(f"array( '{key}'", header)
        for private_key in ("services", "industries", "platform", "app"):
            self.assertNotIn(f"array( '{private_key}'", header)
            self.assertNotIn(f"route( '{private_key}'", header)

        with KEYWORDS.open(encoding="utf-8", newline="") as handle:
            rows = list(csv.DictReader(handle))
        owned = {row["translation_key"] for row in rows}
        self.assertTrue(expected.issubset(owned))
        self.assertFalse({"services", "industries", "platform", "app"} & owned)

        self.assertNotIn('href="#"', self.consumer)
        self.assertNotIn("javascript:", self.consumer.lower())
        self.assertIn("'tel:035231810' : 'tel:+97235231810'", self.consumer)
        self.assertIn('class="c99-menu-toggle"', header)
        self.assertIn("aria-controls=\"c99-primary-nav\"", header)
        self.assertIn("querySelector('.c99-menu-toggle')", self.script)
        route = self.consumer.split("private static function route", 1)[1].split(
            "private static function key_for_post", 1
        )[0]
        self.assertNotIn("sanitize_title", route)
        self.assertEqual(2, route.count("home_url("))

    @unittest.skipUnless(shutil.which("php"), "PHP is required for content evaluation")
    def test_culinary_hubs_are_deep_sourced_and_editorially_owned(self) -> None:
        launch = PLUGIN / "data" / "launch-content.php"
        launch_path = launch.as_posix().replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
$records = require '{launch_path}';
$keys = array('ingredients', 'traditions', 'knowledge');
$result = array();
foreach ($records as $record) {{
    if (!in_array($record['key'], $keys, true)) {{
        continue;
    }}
    foreach (array('he', 'en') as $language) {{
        preg_match_all('/<a href="([^"]+)"/', $record['content'][$language], $matches);
        $plain = trim(preg_replace('/\\s+/u', ' ', strip_tags($record['content'][$language])));
        $result[$record['key']][$language] = array(
            'word_count' => count(preg_split('/\\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY)),
            'content' => $record['content'][$language],
            'plain' => $plain,
            'links' => $matches[1],
            'owner' => $record['editorial_owner'][$language],
            'reviewed_at' => $record['reviewed_at'],
            'verification' => $record['verification'],
            'index_eligible' => $record['index_eligible'],
        );
    }}
}}
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=20,
            check=True,
        )
        hubs = json.loads(completed.stdout)
        self.assertEqual({"ingredients", "traditions", "knowledge"}, set(hubs))

        required_hosts = {
            "ingredients": {"www.gov.il", "me.health.gov.il", "www.who.int"},
            "traditions": {
                "ich.unesco.org",
                "foodish.anumuseum.org.il",
                "www.jewishfoodsociety.org",
            },
            "knowledge": {"www.gov.il", "me.health.gov.il", "www.who.int"},
        }
        forbidden_public_terms = (
            "institutional",
            "operations",
            "inventory",
            "workers",
            "procurement",
            "campaign",
            "מוסדי",
            "תפעול",
            "מלאי",
            "עובדים",
            "רכש",
            "קמפיין",
            "בינה מלאכותית",
        )
        for key, locales in hubs.items():
            for language, page in locales.items():
                minimum_words = 575 if language == "he" else 700
                self.assertGreaterEqual(page["word_count"], minimum_words)
                self.assertEqual("קומפלט 99" if language == "he" else "Complete99", page["owner"])
                self.assertEqual("2026-07-29", page["reviewed_at"])
                self.assertEqual("editorial_review", page["verification"])
                self.assertTrue(page["index_eligible"])
                self.assertIn('<time datetime="2026-07-29">', page["content"])
                self.assertEqual(4, len(page["links"]))
                self.assertEqual(
                    required_hosts[key],
                    {urlparse(url).netloc for url in page["links"]},
                )
                for url in page["links"]:
                    self.assertEqual("https", urlparse(url).scheme)
                self.assertEqual(
                    len(page["links"]),
                    page["content"].count(
                        'target="_blank" rel="external noopener noreferrer"'
                    ),
                )
                lowered = page["plain"].lower()
                for forbidden in forbidden_public_terms:
                    self.assertNotIn(forbidden, lowered)
                self.assertNotIn("\u2014", page["content"])

        generic_renderer = self.consumer.split(
            "private static function render_generic_page", 1
        )[1].split("private static function eyebrow", 1)[0]
        for route_key in ("dishes", "ingredients", "traditions", "knowledge"):
            self.assertIn(f"array( '{route_key}'", generic_renderer)
        self.assertIn("if ( $link[0] === $key )", generic_renderer)
        self.assertIn("self::route( $link[0], $lang )", generic_renderer)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for menu evaluation")
    def test_live_menu_replaces_reference_menu_without_mixing_sources(self) -> None:
        consumer_path = CONSUMER.as_posix().replace("'", "\\'")
        plugin_dir = f"{PLUGIN.as_posix()}/".replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_dir}');
$GLOBALS['c99_live_items'] = array();
function sanitize_title($value) {{
    return strtolower(trim((string) $value));
}}
function sanitize_key($value) {{
    return strtolower(trim((string) $value));
}}
class Complete99_REST {{
    public static function public_indexable_items() {{
        return $GLOBALS['c99_live_items'];
    }}
}}
require '{consumer_path}';
$reference = Complete99_Consumer::menu_items();
$GLOBALS['c99_live_items'] = array(
    array(
        'id' => 'dish-live-1',
        'slug' => 'live-dish',
        'sort' => 1,
        'verification_state' => 'launch_ready',
        'published' => true
    )
);
$live = Complete99_Consumer::menu_items();
echo json_encode(
    array(
        'reference_count' => count($reference),
        'reference_sources' => array_values(array_unique(array_column($reference, '_complete99_source'))),
        'reference_states' => array_values(array_unique(array_column($reference, 'verification_state'))),
        'live_count' => count($live),
        'live_id' => $live[0]['id'],
        'live_source' => $live[0]['_complete99_source']
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
            timeout=15,
            check=True,
        )
        result = json.loads(completed.stdout)
        self.assertEqual(12, result["reference_count"])
        self.assertEqual(["reference"], result["reference_sources"])
        self.assertEqual(["menu_reference"], result["reference_states"])
        self.assertEqual(1, result["live_count"])
        self.assertEqual("dish-live-1", result["live_id"])
        self.assertEqual("live", result["live_source"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for menu evaluation")
    def test_reference_menu_has_owned_images_and_no_transaction_fields(self) -> None:
        menu_path = MENU.as_posix().replace("'", "\\'")
        script = (
            "define('ABSPATH', __DIR__);"
            f"$items=require '{menu_path}';"
            "echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);"
        )
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=15,
            check=True,
        )
        items = json.loads(completed.stdout)
        self.assertEqual(12, len(items))
        forbidden_fields = {
            "public_price",
            "currency",
            "stock",
            "stock_quantity",
            "allergens",
            "nutrition",
            "calories",
        }
        for item in items:
            self.assertFalse(forbidden_fields & set(item), item["id"])
            self.assertEqual("provider_check", item["availability"])
            self.assertEqual("menu_reference", item["verification_state"])
            self.assertTrue(item["published"])
            image_stem = Path(item["image_asset"]).stem
            variants = [
                PLUGIN / "assets" / "images" / "original" / f"{image_stem}.avif",
                PLUGIN / "assets" / "images" / "original" / f"{image_stem}.webp",
            ]
            self.assertTrue(all(path.exists() for path in variants), variants)

    def test_reference_dishes_fail_closed_for_search_indexing(self) -> None:
        robots = self.frontend.split("public static function robots", 1)[1].split(
            "public static function head_metadata", 1
        )[0]
        self.assertIn("array( 'verified', 'launch_ready' )", robots)
        self.assertIn("$robots['noindex']  = true", robots)
        self.assertNotIn("menu_reference", robots)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for schema evaluation")
    def test_dish_schema_matches_visible_breadcrumbs_and_gates_menu_items(self) -> None:
        frontend_path = FRONTEND.as_posix().replace("'", "\\'")
        plugin_dir = f"{PLUGIN.as_posix()}/".replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_dir}');
define('COMPLETE99_PLATFORM_URL', 'https://assets.example.test/complete99-platform/');
function sanitize_title($value) {{
    return strtolower(trim((string) $value));
}}
function sanitize_key($value) {{
    return strtolower(trim((string) $value));
}}
function sanitize_file_name($value) {{
    return basename((string) $value);
}}
function user_trailingslashit($value) {{
    return rtrim((string) $value, '/') . '/';
}}
function home_url($path = '/') {{
    return 'https://example.test/' . ltrim((string) $path, '/');
}}
function wp_strip_all_tags($value) {{
    return strip_tags((string) $value);
}}
function esc_url($value) {{
    return (string) $value;
}}
function esc_attr($value) {{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}}
function wp_json_encode($value, $flags = 0) {{
    return json_encode($value, $flags | JSON_THROW_ON_ERROR);
}}
class Complete99_Content {{
    public static function route_url($key, $lang) {{
        $prefix = 'en' === $lang ? '/en' : '';
        return 'home' === $key
            ? 'https://example.test' . $prefix . '/'
            : 'https://example.test' . $prefix . '/' . $key . '/';
    }}
}}
class Complete99_Commerce {{
    public static function order_url($lang) {{
        return 'https://orders.example.test/' . $lang . '/';
    }}
}}
class Complete99_REST {{
    public static function is_public_item($dish) {{
        return true === ($dish['published'] ?? null)
            && 'live' === ($dish['_complete99_source'] ?? '')
            && in_array(($dish['verification_state'] ?? ''), array('verified', 'launch_ready'), true);
    }}
}}
require '{frontend_path}';
function c99_dish_schema($dish) {{
    $method = new ReflectionMethod('Complete99_Frontend', 'live_dish_head_metadata');
    $method->setAccessible(true);
    ob_start();
    $method->invoke(null, $dish, 'en');
    $html = ob_get_clean();
    preg_match('#<script type="application/ld\\+json">(.*?)</script>#s', $html, $match);
    return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
}}
$base = array(
    'slug' => 'beet-kubbeh',
    'name_he' => 'קובה סלק',
    'name_en' => 'Beet Kubbeh',
    'description_he' => 'תיאור',
    'description_en' => 'Description',
    'published' => true,
    'updated_at' => gmdate('c'),
);
$verified = $base;
$verified['verification_state'] = 'verified';
$verified['_complete99_source'] = 'live';
$reference = $base;
$reference['verification_state'] = 'menu_reference';
$reference['_complete99_source'] = 'reference';
echo json_encode(
    array(
        'verified' => c99_dish_schema($verified),
        'reference' => c99_dish_schema($reference),
    ),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=15,
            check=True,
        )
        schemas = json.loads(completed.stdout)
        for schema in schemas.values():
            graph = schema["@graph"]
            breadcrumb = next(node for node in graph if node["@type"] == "BreadcrumbList")
            self.assertEqual(
                ["Home", "Dishes", "Beet Kubbeh"],
                [item["name"] for item in breadcrumb["itemListElement"]],
            )
            self.assertEqual(
                [
                    "https://example.test/en/",
                    "https://example.test/en/dishes/",
                    "https://example.test/en/menu/beet-kubbeh/",
                ],
                [item["item"] for item in breadcrumb["itemListElement"]],
            )
            webpage = next(node for node in graph if node["@type"] == "WebPage")
            self.assertEqual(
                "https://example.test/en/menu/beet-kubbeh/#breadcrumb",
                webpage["breadcrumb"]["@id"],
            )
        verified_types = [node["@type"] for node in schemas["verified"]["@graph"]]
        reference_types = [node["@type"] for node in schemas["reference"]["@graph"]]
        self.assertIn("MenuItem", verified_types)
        self.assertNotIn("MenuItem", reference_types)
        self.assertIn(
            "Complete99_Frontend::live_dish_breadcrumb_items( $dish, $lang )",
            self.consumer,
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required for content evaluation")
    def test_store_is_non_transactional_and_not_index_eligible(self) -> None:
        launch = PLUGIN / "data" / "launch-content.php"
        launch_path = launch.as_posix().replace("'", "\\'")
        script = (
            "define('ABSPATH', __DIR__);"
            f"$records=require '{launch_path}';"
            "$store=[];"
            "foreach($records as $record){if($record['key']==='store'){$store=$record;break;}}"
            "echo json_encode($store, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);"
        )
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=15,
            check=True,
        )
        store = json.loads(completed.stdout)
        self.assertEqual("publish", store["status"])
        self.assertTrue(store["public_route"])
        self.assertFalse(store["index_eligible"])
        self.assertEqual("configuration_required", store["verification"])
        store_renderer = self.consumer.split("private static function render_store_page", 1)[
            1
        ].split("private static function render_live_store_page", 1)[0]
        for forbidden in ("Add to cart", "Buy now", "woocommerce_cart", "<form"):
            self.assertNotIn(forbidden, store_renderer)
        self.assertIn("Start with the dishes", store_renderer)
        self.assertIn("Browse all dishes", store_renderer)
        self.assertNotIn("There is no site cart or checkout yet", store_renderer)
        self.assertNotIn("Store status", store_renderer)
        self.assertIn("Complete99_Commerce::is_ready()", store_renderer)
        live_store = self.consumer.split("private static function render_live_store_page", 1)[1]
        self.assertNotIn("[products ids=", live_store)
        self.assertIn("render_store_product_card", live_store)
        self.assertIn("Complete99_Commerce::INGREDIENTS_HE", live_store)
        self.assertIn("Complete99_Commerce::ALLERGENS_EN", live_store)
        self.assertIn("Complete99_Commerce::transaction_url", live_store)
        self.assertIn("Complete99_Consumer::render_transaction_page", self.commerce_shell)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for commerce evaluation")
    def test_commerce_readiness_fails_closed_without_woocommerce(self) -> None:
        commerce_path = COMMERCE.as_posix().replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
function get_option($name, $default = false) {{ return $default; }}
function rest_ensure_response($value) {{ return $value; }}
require '{commerce_path}';
echo json_encode(
    Complete99_Commerce::public_status(),
    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=15,
            check=True,
        )
        status = json.loads(completed.stdout)
        self.assertFalse(status["checkout_ready"])
        self.assertEqual("external_ordering", status["status"])
        self.assertEqual(0, status["product_count"])
        self.assertEqual(
            "https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete",
            status["current_order_url"],
        )
        self.assertNotIn("missing_requirements", status)

    def test_commerce_operations_are_private_and_pii_free(self) -> None:
        route_permissions = {
            "/store/readiness": "can_manage_commerce",
            "/store/acceptance": "can_govern_commerce",
            "/store/acceptance-preview": "can_govern_commerce",
            "/store/legal-acceptance": "can_govern_commerce",
            "/store/launch": "can_govern_commerce",
            "/store/operations/outbox": "can_operate_commerce_outbox",
            "/store/operations/outbox/replay": "can_operate_commerce_outbox",
            "/store/operations/orders/(?P<id>\\d+)": "can_view_commerce_order_details",
        }
        for route, permission in route_permissions.items():
            block = self.commerce.split(f"'{route}'", 1)[1][:1600]
            self.assertIn(
                f"'permission_callback' => array( __CLASS__, '{permission}' )",
                block,
            )
        self.assertIn("current_user_can( 'manage_woocommerce' )", self.commerce)
        self.assertIn("current_user_can( 'manage_options' )", self.commerce)
        self.assertIn("'woocommerce_after_order_object_save'", self.commerce)
        self.assertIn("'woocommerce_refund_created'", self.commerce)
        self.assertIn("'woocommerce_refund_deleted'", self.commerce)
        self.assertNotIn("'woocommerce_new_order'", self.commerce)
        self.assertIn("public static function capture_order_snapshot", self.commerce)
        self.assertIn("'complete99-commerce-acceptance/v3'", self.commerce)
        self.assertIn("'required_languages' => array( 'he', 'en' )", self.commerce)
        self.assertIn("'checkout_acceptance'", self.commerce)
        self.assertIn("self::PRODUCT_APPROVED", self.commerce)
        self.assertIn("self::STOCK_AUTHORITY", self.commerce)
        self.assertIn("$product->is_purchasable()", self.commerce)
        self.assertIn("$product->managing_stock()", self.commerce)
        self.assertIn("$product->is_in_stock()", self.commerce)
        self.assertIn("$product->get_weight()", self.commerce)
        self.assertIn("$product->get_shipping_class_id()", self.commerce)
        self.assertIn("self::LABEL_REVIEWED", self.commerce)
        self.assertIn("self::RIGHTS_REVIEWED", self.commerce)
        self.assertIn("self::TAX_REVIEWED", self.commerce)
        self.assertIn("gate_product_visibility", self.commerce)
        self.assertIn("gate_product_purchasability", self.commerce)
        self.assertIn("gate_store_api", self.commerce)
        self.assertIn("gate_public_woocommerce_routes", self.commerce)
        self.assertIn("consumer_legal_acceptance", self.commerce)
        self.assertIn("operations_outbox_backpressure", self.commerce)
        self.assertIn("SELECT GET_LOCK", self.commerce)
        self.assertIn("outbox_lock_name() . '-failures'", self.commerce)
        self.assertIn("OPTION_OUTBOX_ERROR_PREFIX", self.commerce)
        self.assertIn("checkout-draft", self.commerce)
        self.assertIn("'worker_assignment_mode' => 'unassigned_infrastructure'", self.commerce)
        self.assertIn("'order_handoff'          => self::OUTBOX_SCHEMA", self.commerce)
        order_payload = self.commerce.split(
            "private static function order_event_payload", 1
        )[1].split("private static function order_event_version", 1)[0]
        outbox_payloads = order_payload + self.commerce.split(
            "private static function capture_order_event", 1
        )[1].split("private static function enqueue_event", 1)[0]
        for pii_field in (
            "billing_email",
            "billing_phone",
            "billing_address",
            "shipping_address",
            "customer_ip_address",
            "customer_user_agent",
        ):
            self.assertNotIn(pii_field, outbox_payloads)
        self.assertIn("const MAX_OUTBOX_EVENTS  = 500", self.commerce)
        self.assertIn("const OUTBOX_SCHEMA       = 'complete99-commerce-outbox/v2'", self.commerce)
        self.assertIn("const OUTBOX_FAILURE_SCHEMA = 'complete99-commerce-outbox-failure/v2'", self.commerce)
        self.assertIn("const OUTBOX_ACK_SCHEMA   = 'complete99-commerce-outbox-ack/v2'", self.commerce)
        self.assertIn("private static function stock_reduction_event_payload", self.commerce)
        self.assertIn("private static function refund_created_event_payload", self.commerce)
        self.assertIn("private static function fulfilment_event_payload", self.commerce)
        self.assertIn("replay_outbox_failures", self.commerce)
        self.assertIn("complete99_outbox_unknown_event", self.commerce)
        self.assertIn("hash_equals", self.commerce)

    def test_acceptance_v3_requires_two_real_language_orders(self) -> None:
        receipt = self.commerce.split(
            "private static function acceptance_receipt", 1
        )[1].split("public static function record_checkout_acceptance", 1)[0]
        recording = self.commerce.split(
            "public static function record_checkout_acceptance", 1
        )[1].split("private static function evidence_time_is_valid", 1)[0]
        contract = self.commerce.split(
            "private static function order_passes_acceptance_contract", 1
        )[1].split("private static function order_uses_approved_products", 1)[0]
        for marker in (
            "'complete99-commerce-acceptance/v3'",
            "$receipt['languages']['he']",
            "$receipt['languages']['en']",
            "absint( $receipt['languages']['he']['order_id'] ?? 0 )",
            "absint( $receipt['languages']['en']['order_id'] ?? 0 )",
            "self::acceptance_language_entry_is_valid",
        ):
            self.assertIn(marker, receipt)
        for marker in (
            "self::order_passes_acceptance_contract( $order, true, true )",
            "'pending_second_language'",
            "'required_languages' => array( 'he', 'en' )",
            "absint( $languages['he']['order_id'] ?? 0 )",
            "absint( $languages['en']['order_id'] ?? 0 )",
            "self::commit_store_hold_state( $store_ids, ! $passed )",
            "'no' !== (string) get_option( self::OPTION_ENABLED, '__missing__' )",
            "$receipt['store_requires_explicit_launch'] = true",
        ):
            self.assertIn(marker, recording)
        self.assertIn("'checkout' !== (string) $order->get_created_via()", contract)
        self.assertNotIn("'checkout', 'store-api'", contract)
        self.assertNotIn("tested_surfaces", recording)

    def test_acceptance_correlates_every_stock_and_fulfilment_line(self) -> None:
        stock_capture = self.commerce.split(
            "public static function capture_order_item_stock_reduction", 1
        )[1].split("private static function order_payment_gateway", 1)[0]
        acceptance = self.commerce.split(
            "private static function order_passes_acceptance_contract", 1
        )[1].split("private static function order_uses_approved_products", 1)[0]
        fulfilment = self.commerce.split(
            "private static function fulfilment_receipt_covers_order", 1
        )[1].split("private static function order_passes_acceptance_contract", 1)[0]

        for marker in (
            "'inventory.order_stock_reduced'",
            "'order'",
            "(string) $order_id",
            "'line_item_id'",
            "'product_id'",
            "'variation_id'",
            "'stock_from'",
            "'stock_to'",
            "'event_id'",
            "abs( $reduced - $quantity ) > 0.00001",
            "'complete99-stock-reduction-evidence/v2'",
        ):
            self.assertIn(marker, stock_capture)
        for marker in (
            "'inventory_order_stock_reduced'",
            "(string) $order->get_id()",
            "$line_item_id",
            "$stock_from - $stock_to",
            "$expected_stock",
            "unset( $expected_stock[ $line_item_id ] )",
            "if ( ! empty( $expected_stock ) )",
        ):
            self.assertIn(marker, acceptance)
        for marker in (
            "'complete99-fulfilment-evidence/v2'",
            "$covered[ $line_item_id ] += $quantity",
            "$covered[ $line_item_id ] - $expected[ $line_item_id ]",
            "abs( $covered[ $line_item_id ] - $quantity )",
            "'fulfilment_changed'",
            "(string) $order->get_id()",
        ):
            self.assertIn(marker, fulfilment)

    def test_gateway_and_configuration_evidence_fail_closed(self) -> None:
        gateway = self.commerce.split(
            "private static function gateway_is_live_mode", 1
        )[1].split("public static function gate_public_woocommerce_routes", 1)[0]
        config = self.commerce.split(
            "private static function commerce_configuration_digest", 1
        )[1].split("public static function capture_order_snapshot", 1)[0]
        entry_validation = self.commerce.split(
            "private static function acceptance_language_entry_is_valid", 1
        )[1].split("private static function acceptance_receipt", 1)[0]

        for marker in (
            "complete99_commerce_gateway_live_mode",
            "'testmode', 'test_mode', 'sandbox', 'sandbox_mode'",
            "'environment', 'mode'",
            "return $recognized && $live_indicator",
            "'complete99-gateway-payment-evidence/v1'",
            "'transaction_id_hash'",
            "'live_mode'",
        ):
            self.assertIn(marker, gateway)
        for marker in (
            "'plugin_version'",
            "'woo_version'",
            "'order_language'",
            "'gateways'",
            "'shipping_zones'",
            "'pages'",
            "'settings_digest'",
            "self::gateway_is_live_mode( $gateway, $order )",
        ):
            self.assertIn(marker, config)
        self.assertIn(
            "self::commerce_configuration_digest( $order )",
            entry_validation,
        )

    def test_outbox_recovery_is_scoped_locked_and_bounded(self) -> None:
        errors = self.commerce.split(
            "private static function record_outbox_error", 1
        )[1].split("private static function acquire_outbox_failures_lock", 1)[0]
        failures = self.commerce.split(
            "private static function acquire_outbox_failures_lock", 1
        )[1].split("private static function outbox_ack_audit", 1)[0]
        acknowledgement = self.commerce.split(
            "public static function acknowledge_outbox", 1
        )[1]

        for marker in (
            "OPTION_OUTBOX_ERROR_PREFIX . $code",
            "'complete99-commerce-outbox-error/v2'",
            "self::outbox_error_codes()",
            "delete_option( self::OPTION_OUTBOX_ERROR_PREFIX . $code )",
        ):
            self.assertIn(marker, errors)
        for marker in (
            "outbox_lock_name() . '-failures'",
            "self::acquire_outbox_failures_lock()",
            "self::release_outbox_failures_lock()",
            "self::MAX_OUTBOX_FAILURES",
            "self::OUTBOX_FAILURE_SCHEMA",
            "failure_capacity",
            "failure_readback",
        ):
            self.assertIn(marker, failures)
        self.assertIn("const MAX_OUTBOX_AUDIT   = 5000", self.commerce)
        for marker in (
            "self::MAX_OUTBOX_AUDIT",
            "self::protected_acceptance_event_ids()",
            "'audit_capacity'",
            "'audit_readback'",
            "self::OUTBOX_ACK_SCHEMA",
            "'payload_digest'",
        ):
            self.assertIn(marker, acknowledgement)
        self.assertLess(
            acknowledgement.index("update_option( self::OPTION_OUTBOX_AUDIT"),
            acknowledgement.index("update_option( self::OPTION_OUTBOX, $remaining"),
        )

    def test_launch_is_staged_and_closes_before_cache_recovery(self) -> None:
        launch = self.commerce.split(
            "public static function set_store_launch_state", 1
        )[1].split("private static function acquire_store_launch_lock", 1)[0]
        self.assertLess(
            launch.index("update_option( self::OPTION_ENABLED, 'no', false )"),
            launch.index("if ( ! self::purge_commerce_caches_with_retry() )"),
        )
        self.assertIn("'store_enabled'               => false", launch)
        self.assertIn("'manual_cache_purge_required' => true", launch)
        staged_readiness = launch.index(
            "$staged_readiness = self::readiness( true, true, true )"
        )
        staged_audit = launch.index(
            "$audit = self::store_launch_audit( true, $staged_readiness )"
        )
        staged_pages = launch.index(
            "self::write_store_page_launch_state( $store_ids, true )"
        )
        enable_commit = launch.index(
            "update_option( self::OPTION_ENABLED, 'yes', false )"
        )
        self.assertLess(staged_readiness, staged_audit)
        self.assertLess(staged_audit, staged_pages)
        self.assertLess(staged_pages, enable_commit)
        postcommit = launch[enable_commit:]
        self.assertIn("self::purge_commerce_caches_with_retry()", postcommit)
        self.assertIn("self::restore_store_launch_snapshot", postcommit)
        self.assertIn("'rollback_verified'", postcommit)
        self.assertIn(
            "'store_enabled'                => 'yes' === (string) get_option",
            postcommit,
        )

    def test_commerce_closes_product_discovery_and_preserves_customer_truth(self) -> None:
        for marker in (
            "#^/wc/store(?:/v\\d+)?(?:/|$)#",
            "#^/wc/store(?:/v\\d+)?/(?:batch|products|shopper-lists)(?:/|$)#",
            "product_variation|product_cat|product_tag|product_brand",
            "$is_core_search",
            "exclude_products_from_rest_search",
            "exclude_products_from_public_search",
        ):
            self.assertIn(marker, self.commerce)
        gate = self.commerce.split("public static function gate_store_api", 1)[
            1
        ].split("public static function exclude_products_from_rest_search", 1)[0]
        self.assertIn(
            "$is_store_products || $is_core_product || $is_core_media || $is_product_search || $is_product_oembed",
            gate,
        )
        self.assertIn("'complete99_commerce_route_held'", gate)
        self.assertNotIn("$request->set_param( 'include'", self.commerce)
        self.assertNotIn("tested_surfaces", self.commerce)
        for hook in (
            "woocommerce_checkout_create_order",
            "woocommerce_checkout_create_order_line_item",
            "woocommerce_store_api_checkout_update_order_meta",
            "woocommerce_thankyou",
            "woocommerce_email_sent",
            "woocommerce_reduce_order_item_stock",
            "woocommerce_reduce_order_stock",
            "woocommerce_update_order_refund",
            "woocommerce_fulfillment_after_fulfill",
            "woocommerce_get_checkout_order_received_url",
            "woocommerce_allow_switching_email_locale",
        ):
            self.assertIn(hook, self.commerce)
        for marker in (
            "ORDER_LANGUAGE",
            "ITEM_NAME_HE",
            "ITEM_NAME_EN",
            "ORDER_RECEIVED_SEEN",
            "ORDER_EMAIL_SENT",
            "ORDER_STOCK_RECEIPT",
            "ORDER_FULFILMENT_RECEIPT",
            "complete99-stock-reduction-evidence/v2",
            "complete99-fulfilment-evidence/v2",
            "get_transaction_id()",
            "'completed' !== (string) $order->get_status()",
            "get_refunded_payment",
            "can_access_customer_continuity",
            "OPTION_EVER_LAUNCHED",
        ):
            self.assertIn(marker, self.commerce)
        self.assertIn("Complete99_Commerce::can_access_customer_continuity()", self.frontend)
        self.assertLess(
            self.commerce_shell.index("switch_to_locale"),
            self.commerce_shell.index("wp_head"),
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required for integrity evaluation")
    def test_outbox_identity_and_language_dominance_execute_fail_closed(self) -> None:
        commerce_path = COMMERCE.as_posix().replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
function absint($value) {{ return abs((int) $value); }}
function sanitize_key($value) {{
    return strtolower(preg_replace('/[^a-z0-9_\\-]/', '', (string) $value));
}}
function sanitize_text_field($value) {{ return trim(strip_tags((string) $value)); }}
function wp_json_encode($value, $flags = 0) {{
    return json_encode($value, $flags | JSON_THROW_ON_ERROR);
}}
require '{commerce_path}';
function c99_call($name, ...$args) {{
    $method = new ReflectionMethod('Complete99_Commerce', $name);
    $method->setAccessible(true);
    return $method->invokeArgs(null, $args);
}}
$first = c99_call(
    'build_outbox_event',
    'order.snapshot',
    'order',
    '91',
    '1700000000|completed',
    array('z' => 1, 'nested' => array('b' => 2, 'a' => 1))
);
$second = c99_call(
    'build_outbox_event',
    'order.snapshot',
    'order',
    '91',
    '1700000000|completed',
    array('nested' => array('a' => 1, 'b' => 2), 'z' => 1)
);
$mutated_payload = $first;
$mutated_payload['payload']['z'] = 2;
$mutated_version = $first;
$mutated_version['event_version'] = 'different';
$he_email = array(
    'subject_hebrew_chars' => 30,
    'subject_latin_chars' => 4,
    'body_hebrew_chars' => 300,
    'body_latin_chars' => 40
);
$wrong_he_email = array(
    'subject_hebrew_chars' => 8,
    'subject_latin_chars' => 30,
    'body_hebrew_chars' => 80,
    'body_latin_chars' => 300
);
echo json_encode(
    array(
        'first_valid' => c99_call('is_valid_outbox_event', $first),
        'stable_id' => $first['id'] === $second['id'],
        'payload_tamper_rejected' => !c99_call('is_valid_outbox_event', $mutated_payload),
        'version_tamper_rejected' => !c99_call('is_valid_outbox_event', $mutated_version),
        'he_copy_matches' => c99_call('text_matches_language', 'הזמנה אמיתית הושלמה ונשלחה אליך עכשיו', 'he', 8, 60),
        'english_copy_rejected_as_he' => !c99_call('text_matches_language', 'Your completed order is ready for pickup today', 'he', 8, 60),
        'he_email_dominant' => c99_call('email_script_counts_match_language', $he_email, 'he'),
        'wrong_he_email_rejected' => !c99_call('email_script_counts_match_language', $wrong_he_email, 'he')
    ),
    JSON_THROW_ON_ERROR
);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=20,
            check=True,
        )
        result = json.loads(completed.stdout)
        self.assertTrue(all(result.values()), result)

    def test_commerce_integrity_covers_cache_launch_config_media_and_acknowledgement(self) -> None:
        for marker in (
            "const OUTBOX_ID_VERSION   = 3",
            "'event_version'  => $version",
            "'occurred_at'    => $occurred_at",
            "'payload_digest' => $payload_digest",
            "self::is_valid_outbox_event( $event )",
            "isset( $seen[ $event_id ] )",
            "'event_corrupt'",
            "'event_version'   => (string) $event['event_version']",
            "self::is_valid_outbox_ack( $entry )",
            "The outbox is corrupt and no event was acknowledged.",
            "The outbox is corrupt and no failed event was replayed.",
        ):
            self.assertIn(marker, self.commerce)

        status = self.commerce.split("public static function public_status", 1)[1].split(
            "public static function private_readiness", 1
        )[0]
        self.assertIn("no-store, no-cache, must-revalidate, max-age=0", status)
        no_cache = self.commerce.split(
            "public static function enforce_commerce_no_cache", 1
        )[1].split("public static function gate_store_api", 1)[0]
        self.assertIn("array( 'home', 'store' )", no_cache)

        for marker in (
            "'complete99-commerce-launch-audit/v3'",
            "'readiness'         => $readiness",
            "'acceptance_digest'",
            "'legal_digest'",
            "self::launch_audit_is_valid()",
            "'launch_audit_integrity'",
        ):
            self.assertIn(marker, self.commerce)

        config = self.commerce.split(
            "private static function commerce_configuration_digest", 1
        )[1].split("public static function capture_order_snapshot", 1)[0]
        for marker in (
            "self::commerce_material_options_snapshot()",
            "self::commerce_attachment_identity",
            "'file_sha256'",
            "'metadata_digest'",
            "'post_excerpt'",
            "'attributes'",
            "'gallery'",
            "sort( $rate[ $location_key ], SORT_STRING )",
        ):
            self.assertIn(marker, self.commerce)
        for marker in (
            "'_product_image_gallery'",
            "'_product_attributes'",
            "'_purchase_note'",
            "'product_cat'",
            "'product_tag'",
        ):
            self.assertIn(marker, self.commerce)
        self.assertIn("'material_options'", config)

        for marker in (
            "const MEDIA_PUBLIC_SAFE",
            "'attachment'",
            "self::MEDIA_PUBLIC_SAFE",
            "#^/wp/v2/media(?:/|$)#",
            "self::attachment_is_approved_product_image",
            "'complete99-order-email-evidence/v4'",
            "script_dominance_verified",
            "self::email_script_counts_match_language",
            "complete99_commerce_legal_language",
            "self::text_matches_language",
        ):
            self.assertIn(marker, self.commerce)

    def test_public_catalog_reuses_strict_static_and_bilingual_contracts(self) -> None:
        approved = self.commerce.split(
            "private static function approved_products", 1
        )[1].split("private static function allowed_product_ids", 1)[0]
        static_contract = self.commerce.split(
            "private static function product_passes_static_acceptance_contract", 1
        )[1].split("private static function commerce_tax_configuration", 1)[0]
        language_contract = self.commerce.split(
            "private static function product_copy_matches_declared_languages", 1
        )[1].split("public static function storefront_product_ids", 1)[0]

        self.assertIn(
            "self::product_passes_static_acceptance_contract( $product_id )",
            approved,
        )
        self.assertIn(
            "self::product_copy_matches_declared_languages( $product_id )",
            static_contract,
        )
        for marker in (
            "array( self::NAME_HE, 'he', 2, 55 )",
            "array( self::NAME_EN, 'en', 2, 55 )",
            "array( self::DESCRIPTION_HE, 'he', 12, 60 )",
            "array( self::DESCRIPTION_EN, 'en', 12, 60 )",
            "array( self::INGREDIENTS_HE, 'he', 2, 60 )",
            "array( self::INGREDIENTS_EN, 'en', 2, 60 )",
            "array( self::ALLERGENS_HE, 'he', 2, 60 )",
            "array( self::ALLERGENS_EN, 'en', 2, 60 )",
            "array( self::STORAGE_HE, 'he', 3, 60 )",
            "array( self::STORAGE_EN, 'en', 3, 60 )",
            "array( self::FULFILMENT_HE, 'he', 3, 60 )",
            "array( self::FULFILMENT_EN, 'en', 3, 60 )",
            "self::text_matches_language",
        ):
            self.assertIn(marker, language_contract)

    def test_configuration_digest_rejects_incomplete_option_and_tax_readback(self) -> None:
        tax = self.commerce.split(
            "private static function commerce_tax_configuration", 1
        )[1].split("private static function commerce_configuration_digest", 1)[0]
        config = self.commerce.split(
            "private static function commerce_configuration_digest", 1
        )[1].split("public static function capture_order_snapshot", 1)[0]

        self.assertIn("return array();", tax)
        self.assertNotIn("'readback_error'", tax)
        for marker in (
            "empty( $material_options['enumeration_complete'] )",
            "$tax_configuration = self::commerce_tax_configuration()",
            "empty( $tax_configuration['readback_complete'] )",
            "'taxes'                  => $tax_configuration",
        ):
            self.assertIn(marker, config)

    def test_concept_asset_is_clearly_restricted_and_registered(self) -> None:
        concept_dir = PLUGIN / "assets" / "images" / "concepts"
        for name in (
            "complete99-pantry-packaging-concept-v1.avif",
            "complete99-pantry-packaging-concept-v1.webp",
        ):
            path = concept_dir / name
            self.assertTrue(path.exists(), path)
            self.assertGreater(path.stat().st_size, 50_000, path)
            self.assertIn(name, MEDIA_REGISTER.read_text(encoding="utf-8"))
        register = MEDIA_REGISTER.read_text(encoding="utf-8").lower()
        self.assertIn("not a product for sale", register)
        self.assertIn("must not be used", register)
        self.assertIn("product structured data", register)
        self.assertIn("photograph from the complete99 food collection", self.consumer.lower())
        self.assertIn("the archive photograph remains illustrative", register)

    def test_menu_hero_keeps_archive_disclosure_for_live_menu_data(self) -> None:
        menu_renderer = self.consumer.split(
            "private static function render_menu_page", 1
        )[1].split("private static function render_store_page", 1)[0]
        caption = "Complete99 archive photograph"
        self.assertIn("c99-food-sabich-pita-gallery-2021-wp-v01", menu_renderer)
        self.assertIn(f'<figcaption class="c99-archive-note">', menu_renderer)
        self.assertIn(caption, menu_renderer)
        caption_line = next(line for line in menu_renderer.splitlines() if caption in line)
        self.assertNotIn("$has_live", caption_line)
        register = MEDIA_REGISTER.read_text(encoding="utf-8").lower()
        self.assertIn("c99-food-sabich-pita-gallery-2021-wp-v01", register)
        self.assertIn("the archive photograph remains illustrative", register)

    def test_consumer_css_supports_keyboard_touch_mobile_and_reduced_motion(self) -> None:
        self.assertIn(":focus-visible", self.css)
        self.assertGreaterEqual(self.css.count("min-height: 44px"), 5)
        self.assertRegex(self.css, r"@media\s*\(max-width:\s*1180px\)")
        self.assertRegex(self.css, r"@media\s*\(max-width:\s*700px\)")
        self.assertIn("@media (prefers-reduced-motion: reduce)", self.css)
        self.assertIn(".c99-consumer-menu-grid", self.css)
        self.assertIn(".c99-consumer-hero-grid", self.css)
        public_css = (PLUGIN / "assets" / "css" / "public.css").read_text(
            encoding="utf-8"
        )
        self.assertIn("outline: 3px solid #fff", public_css)
        self.assertIn("box-shadow: 0 0 0 6px var(--c99-focus)", public_css)
        self.assertIn(".woocommerce a.remove", self.css)
        self.assertIn("width: 44px", self.css)
        for selector in (
            ".c99-consumer-utility",
            ".c99-consumer-nav-link",
            ".c99-consumer-header .c99-nav-cta",
            ".c99-consumer-header .c99-language-switch",
            ".c99-consumer-header .c99-menu-toggle",
            ".c99-consumer-site .c99-button",
            ".c99-breadcrumb.c99-container",
            ".c99-note-links a",
            ".c99-footer-direct-links a",
            ".c99-consumer-footer .c99-footer-cluster a",
        ):
            match = re.search(re.escape(selector) + r"\s*\{([^}]*)\}", self.css)
            self.assertIsNotNone(match, selector)
            self.assertRegex(match.group(1), r"font-size:\s*16px", selector)

    def test_new_public_copy_contains_no_em_dash(self) -> None:
        paths = (
            CONSUMER,
            CONTENT,
            MENU,
            KEYWORDS,
            CSS,
            ROOT / "docs" / "consumer-culinary-ecommerce-benchmark-2026-07-29.md",
        )
        em_dash = "\u2014"
        for path in paths:
            self.assertNotIn(em_dash, path.read_text(encoding="utf-8"), path)

    def test_external_ordering_destination_is_exact_and_protected(self) -> None:
        self.assertEqual(2, self.commerce.lower().count("https://wolt.com"))
        self.assertIn(
            "https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete",
            self.commerce,
        )
        self.assertIn(
            "https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete",
            self.commerce,
        )
        external_links = re.findall(
            r'<a[^>]+href="<\?php echo esc_url\( [^;]+ \); \?>"[^>]*>',
            self.consumer,
        )
        self.assertTrue(external_links)
        for link in external_links:
            if "order_url" in link:
                self.assertIn('target="_blank"', link)
                self.assertIn('rel="noopener noreferrer"', link)


if __name__ == "__main__":
    unittest.main()
