from __future__ import annotations

import json
import re
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
COMMERCE = PLUGIN / "includes" / "class-complete99-commerce.php"
CONSUMER = PLUGIN / "includes" / "class-complete99-consumer.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
SCIENCE = PLUGIN / "includes" / "class-complete99-culinary-science.php"
MUSEUM = PLUGIN / "includes" / "class-complete99-culinary-museum-frontend.php"
SCRIPT = PLUGIN / "assets" / "js" / "public.js"
CSS = PLUGIN / "assets" / "css" / "consumer.css"


SAFE_FILTERS = {
    "all",
    "pantry",
    "japanese-pantry",
    "fresh-produce",
    "chilled-frozen",
    "bakery",
    "equipment",
    "regulated",
}


def method_block(source: str, name: str, next_name: str) -> str:
    return source.split(f"function {name}", 1)[1].split(f"function {next_name}", 1)[0]


class Complete99ScalableStoreShelfContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.commerce = COMMERCE.read_text(encoding="utf-8")
        cls.consumer = CONSUMER.read_text(encoding="utf-8")
        cls.frontend = FRONTEND.read_text(encoding="utf-8")
        cls.science = SCIENCE.read_text(encoding="utf-8")
        cls.museum = MUSEUM.read_text(encoding="utf-8")
        cls.script = SCRIPT.read_text(encoding="utf-8")
        cls.css = CSS.read_text(encoding="utf-8")

    def test_storefront_state_is_allowlisted_bounded_and_twelve_per_page(self) -> None:
        self.assertRegex(
            self.commerce,
            r"const\s+STOREFRONT_PAGE_SIZE\s*=\s*12\s*;",
        )
        filters = method_block(
            self.commerce, "storefront_filter_options", "storefront_listing_state"
        )
        self.assertEqual(
            SAFE_FILTERS,
            set(re.findall(r"^\s*'([a-z][a-z-]+)'\s*=>", filters, re.MULTILINE)),
        )

        state = method_block(
            self.commerce, "storefront_listing_state", "storefront_listing"
        )
        for marker in (
            "$_GET['product-type']",
            "$_GET['product-page']",
            "sanitize_key",
            "wp_unslash",
            "self::storefront_filter_options()",
            "0 < $page ? $page : 1",
            "'product_type'",
            "'product_page'",
        ):
            self.assertIn(marker, state)

        listing = method_block(
            self.commerce, "storefront_listing", "storefront_url"
        )
        self.assertIn("self::STOREFRONT_PAGE_SIZE", listing)
        self.assertIn("array_slice( $matching_ids, $offset, self::STOREFRONT_PAGE_SIZE )", listing)
        for key in (
            "product_type",
            "product_page",
            "per_page",
            "total_products",
            "total_pages",
            "product_ids",
            "all_product_ids",
        ):
            self.assertIn(f"'{key}'", listing)

    def test_filters_and_pagination_are_real_links_without_hidden_card_filtering(self) -> None:
        filters = method_block(
            self.consumer, "render_store_filters", "render_store_pagination"
        )
        self.assertIn("Complete99_Commerce::storefront_filter_options()", filters)
        self.assertIn("Complete99_Commerce::storefront_url(", filters)
        self.assertIn("data-c99-product-filter-link", filters)
        self.assertIn("<a ", filters)
        self.assertNotIn("<button", filters)
        self.assertNotIn("data-c99-product-filter-button", filters)
        self.assertNotIn("aria-pressed", filters)

        pagination = method_block(
            self.consumer, "render_store_pagination", "render_store_product_card"
        )
        self.assertIn('<nav class="c99-store-pagination"', pagination)
        self.assertIn("Complete99_Commerce::storefront_url(", pagination)
        self.assertIn("$listing['total_pages']", pagination)
        self.assertIn("$listing['product_page']", pagination)
        self.assertIn("c99-store-pagination-current", pagination)

        self.assertNotIn("data-c99-product-filter-button", self.script)
        store_script = self.script.split("[data-c99-store-product-map]", 1)[0]
        self.assertNotIn("card.hidden", store_script)
        self.assertNotIn("data-c99-product-filter-empty", self.script)

        filter_rule = re.search(
            r"\.c99-product-filter-buttons a\s*\{([^}]*)\}", self.css
        )
        self.assertIsNotNone(filter_rule)
        self.assertRegex(filter_rule.group(1), r"min-height:\s*44px")
        pagination_rule = re.search(
            r"\.c99-store-pagination\s*>\s*a,[^{]*"
            r"\.c99-store-pagination-current\s*\{([^}]*)\}",
            self.css,
        )
        self.assertIsNotNone(pagination_rule)
        self.assertRegex(pagination_rule.group(1), r"min-height:\s*44px")

    def test_cards_are_compact_details_and_use_one_priority_image(self) -> None:
        store = method_block(
            self.consumer, "render_live_store_page", "render_store_cart_feedback"
        )
        card = method_block(
            self.consumer, "render_store_product_card", "render_related_store_products"
        )
        self.assertIn("Complete99_Commerce::storefront_listing()", store)
        self.assertIn("$listing['product_ids']", store)
        self.assertIn("$product_index", store)
        self.assertIn(
            "render_store_product_card( $product_id, $lang, $product_index",
            store,
        )
        self.assertIn("<details", card)
        self.assertIn("<summary", card)
        self.assertIn("c99-store-product-details", card)
        self.assertIn("'loading'", card)
        self.assertIn("'eager'", card)
        self.assertIn("'lazy'", card)
        self.assertIn("'fetchpriority'", card)
        self.assertIn("'high'", card)

        details_rule = re.search(
            r"\.c99-store-product-details(?:\s*>\s*|\s+)summary\s*\{([^}]*)\}",
            self.css,
        )
        self.assertIsNotNone(details_rule)
        self.assertRegex(details_rule.group(1), r"min-height:\s*44px")

    def test_one_page_aware_product_url_owner_is_used_across_public_surfaces(self) -> None:
        product_url = method_block(
            self.commerce, "storefront_product_url", "page_contains_commerce_surface"
        )
        self.assertIn("self::storefront_listing(", product_url)
        self.assertIn("self::STOREFRONT_PAGE_SIZE", product_url)
        self.assertIn("'c99-product-code-'", product_url)

        for path, source in (
            (CONSUMER, self.consumer),
            (FRONTEND, self.frontend),
            (SCIENCE, self.science),
            (MUSEUM, self.museum),
        ):
            self.assertIn("Complete99_Commerce::storefront_product_url(", source, path)

        public_sources = "\n".join(
            (self.consumer, self.frontend, self.science, self.museum)
        )
        self.assertNotRegex(
            public_sources,
            r"(?:route_url|route)\([^\n;]*store[^\n;]*\)\s*\.\s*'#c99-product-code-'",
        )

    def test_schema_and_metadata_use_the_current_listing_state(self) -> None:
        head = method_block(self.frontend, "head_metadata", "is_live_dish_request")
        schema = method_block(self.frontend, "schema_graph", "store_product_schema")
        product_schema = method_block(
            self.frontend, "store_product_schema", "verified_recipe_schema"
        )
        title = method_block(self.frontend, "document_title", "render_document_head")
        robots = method_block(self.frontend, "robots", "head_metadata")

        for marker in (
            "Complete99_Commerce::storefront_listing_state()",
            "Complete99_Commerce::storefront_url(",
        ):
            self.assertIn(marker, head)
        for marker in ("$canonical", "$he_url", "$en_url", "x-default", "og:url"):
            self.assertIn(marker, head)

        self.assertIn("Complete99_Commerce::storefront_listing(", schema)
        self.assertIn("$listing['product_ids']", schema)
        self.assertIn("Complete99_Commerce::storefront_product_url(", product_schema)
        self.assertNotIn("Complete99_Commerce::storefront_product_ids()", schema)

        self.assertIn("Complete99_Commerce::storefront_listing_state()", title)
        self.assertIn("Page %d", title)
        self.assertIn("$product_page", title)

        self.assertIn("Complete99_Commerce::storefront_listing()", robots)
        self.assertIn("'all' !== (string) ( $listing['product_type'] ?? 'all' )", robots)
        self.assertIn("empty( $listing['query_is_canonical'] )", robots)
        self.assertIn("$robots['noindex']", robots)
        self.assertIn("$robots['follow']", robots)
        self.assertNotIn("$robots['nofollow'] = true", robots)

    def test_language_switch_and_cart_action_retain_validated_store_state(self) -> None:
        language_switch = method_block(
            self.consumer, "render_language_switch", "render_current"
        )
        card = method_block(
            self.consumer, "render_store_product_card", "render_related_store_products"
        )
        self.assertIn("'store' === $key", language_switch)
        self.assertIn("Complete99_Commerce::storefront_listing", language_switch)
        self.assertIn("$listing['product_type']", language_switch)
        self.assertIn("$listing['product_page']", language_switch)
        self.assertIn("Complete99_Commerce::storefront_url(", language_switch)

        self.assertIn("Complete99_Commerce::storefront_url(", card)
        self.assertIn("$product_type", card)
        self.assertIn("$product_page", card)
        self.assertIn("'add-to-cart' => absint( $product_id )", card)
        self.assertIn("'lang'        => $lang", card)
        self.assertIn("'c99-product-code-'", card)

    def test_legacy_fragment_resolver_is_catalog_bounded_and_navigation_only(self) -> None:
        self.assertRegex(
            self.commerce,
            r"const\s+STOREFRONT_LEGACY_MAP_LIMIT\s*=\s*100\s*;",
        )
        legacy_map = method_block(
            self.consumer,
            "render_store_legacy_product_map",
            "render_store_product_card",
        )
        self.assertIn(
            "Complete99_Commerce::STOREFRONT_LEGACY_MAP_LIMIT", legacy_map
        )
        self.assertIn("data-c99-store-product-map", legacy_map)
        self.assertIn("wp_json_encode", legacy_map)

        resolver = self.script.split("[data-c99-store-product-map]", 1)[1]
        self.assertIn("window.location.hash", resolver)
        self.assertIn("window.location.replace", resolver)
        self.assertNotIn("fetch(", resolver)
        self.assertNotIn("innerHTML", resolver)
        self.assertNotIn("localStorage", resolver)

    def test_shelf_read_path_has_no_payment_product_or_stock_mutation(self) -> None:
        shelf = self.commerce.split("public static function storefront_filter_options", 1)[
            1
        ].split("private static function page_contains_commerce_surface", 1)[0]
        for forbidden in (
            "update_option(",
            "update_post_meta(",
            "delete_post_meta(",
            "wp_insert_post(",
            "wp_update_post(",
            "wc_update_product_stock(",
            "set_stock_quantity(",
            "set_price(",
            "set_regular_price(",
            "set_status(",
            "payment_gateway",
        ):
            self.assertNotIn(forbidden, shelf)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for storefront evaluation")
    def test_storefront_runtime_clamps_state_and_returns_three_unique_pages(self) -> None:
        commerce_path = json.dumps(COMMERCE.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function sanitize_html_class($value) {{
    return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value);
}}
function wp_unslash($value) {{ return $value; }}
function absint($value) {{ return abs((int) $value); }}
function add_query_arg($args, $url) {{
    return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($args);
}}
function get_post_meta($id, $key, $single = false) {{
    if ('_complete99_live_catalog_facet' === $key) {{
        return $id <= 9 ? 'pantry japanese-pantry' : 'pantry';
    }}
    if ('_complete99_catalog_product_code' === $key) {{
        return 'product-' . $id;
    }}
    return '';
}}
class Complete99_Content {{
    public static function route_url($key, $lang) {{
        return 'https://complete99.test/' . ('en' === $lang ? 'en/' : '') . 'store/';
    }}
}}
class Complete99_Live_Catalog {{
    public static function is_ready() {{ return true; }}
    public static function product_ids() {{ return range(1, 36); }}
}}
require {commerce_path};

$_GET = array('product-type' => '../unsafe', 'product-page' => '-9');
$unsafe = Complete99_Commerce::storefront_listing();
$_GET = array('product-type' => 'all', 'product-page' => '999');
$clamped = Complete99_Commerce::storefront_listing();
$_GET = array('product-type' => array('pantry'), 'product-page' => array('2'));
$array_input = Complete99_Commerce::storefront_listing();
$_GET = array();
$clean = Complete99_Commerce::storefront_listing();
$_GET = array('product-page' => '1');
$redundant_page = Complete99_Commerce::storefront_listing();
$_GET = array('product-type' => 'japanese-pantry');
$canonical_filter = Complete99_Commerce::storefront_listing();
$_GET = array('add-to-cart' => '25', 'lang' => 'he', 'product-page' => '3');
$transaction = Complete99_Commerce::storefront_listing();
$pages = array();
foreach (range(1, 3) as $page) {{
    $pages[] = Complete99_Commerce::storefront_listing('all', $page)['product_ids'];
}}
echo json_encode(array(
    'filters' => array_keys(Complete99_Commerce::storefront_filter_options()),
    'unsafe' => $unsafe,
    'clamped' => $clamped,
    'array_input' => $array_input,
    'clean' => $clean,
    'redundant_page' => $redundant_page,
    'canonical_filter' => $canonical_filter,
    'transaction' => $transaction,
    'pages' => $pages,
    'japanese' => Complete99_Commerce::storefront_listing('japanese-pantry', 1),
    'en_url' => Complete99_Commerce::storefront_url('en', 'japanese-pantry', 2),
    'he_product' => Complete99_Commerce::storefront_product_url('product-25', 'he'),
), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)

        self.assertEqual(SAFE_FILTERS, set(result["filters"]))
        self.assertEqual("all", result["unsafe"]["product_type"])
        self.assertEqual(1, result["unsafe"]["product_page"])
        self.assertEqual(3, result["clamped"]["product_page"])
        self.assertEqual("all", result["array_input"]["product_type"])
        self.assertEqual(1, result["array_input"]["product_page"])
        self.assertTrue(result["clean"]["query_is_canonical"])
        self.assertFalse(result["redundant_page"]["query_is_canonical"])
        self.assertTrue(result["canonical_filter"]["query_is_canonical"])
        self.assertFalse(result["transaction"]["query_is_canonical"])
        self.assertFalse(result["unsafe"]["query_is_canonical"])
        self.assertFalse(result["clamped"]["query_is_canonical"])
        self.assertEqual([12, 12, 12], [len(page) for page in result["pages"]])
        flattened = [product_id for page in result["pages"] for product_id in page]
        self.assertEqual(list(range(1, 37)), flattened)
        self.assertEqual(9, result["japanese"]["total_products"])
        self.assertEqual(
            "https://complete99.test/en/store/?product-type=japanese-pantry&product-page=2",
            result["en_url"],
        )
        self.assertEqual(
            "https://complete99.test/store/?product-page=3#c99-product-code-product-25",
            result["he_product"],
        )


if __name__ == "__main__":
    unittest.main()
