from __future__ import annotations

import hashlib
import json
import re
import shutil
import subprocess
import unittest
from datetime import date
from pathlib import Path
from urllib.parse import urlparse


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
MAIN = PLUGIN / "complete99-platform.php"
LIVE_CATALOG = PLUGIN / "includes" / "class-complete99-live-catalog.php"
COMMERCE = PLUGIN / "includes" / "class-complete99-commerce.php"
CONSUMER = PLUGIN / "includes" / "class-complete99-consumer.php"
CONTENT = PLUGIN / "includes" / "class-complete99-content.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
REST = PLUGIN / "includes" / "class-complete99-rest.php"
LIVE_DISH_SITEMAP = PLUGIN / "includes" / "class-complete99-live-dish-sitemap-provider.php"
CONSUMER_CONTENT = PLUGIN / "data" / "consumer-content.php"
PUBLIC_SCRIPT = PLUGIN / "assets" / "js" / "public.js"
CONSUMER_CSS = PLUGIN / "assets" / "css" / "consumer.css"
MATERIALIZER = ROOT / "scripts" / "materialize-woocommerce.py"


EXPECTED_PRICES = {
    "product-tahini-500g": "11.00",
    "product-amba-500g": "14.90",
    "product-hot-sauce-60ml": "12.90",
    "product-pita-12x50g": "14.90",
    "product-aubergine-1kg": "6.90",
    "product-eggs-l-12": "14.24",
    "product-potato-white-1kg": "4.90",
    "product-tomato-1kg": "6.90",
    "product-cucumber-1kg": "6.90",
    "product-onion-dry-1kg": "4.90",
    "product-parsley-100g": "5.90",
    "product-chickpeas-dry-500g": "8.90",
    "product-beetroot-1kg": "4.90",
    "product-bulgur-fine-500g": "5.90",
    "product-couscous-1kg": "11.90",
    "product-chicken-breast-1kg": "39.90",
    "product-breadcrumbs-500g": "8.90",
    "product-ground-beef-1kg": "64.90",
    "product-tilapia-fillet-1kg": "38.90",
    "product-tomato-sauce-400g": "9.90",
    "product-rice-persian-1kg": "11.90",
    "product-beef-shank-1kg": "69.90",
    "product-hawayej-soup-100g": "8.90",
    "product-olive-oil-750ml": "44.90",
    "product-pickles-brine-320g": "14.90",
    "product-chicken-liver-1kg": "17.90",
    "product-rishiri-kombu-100g": "89.00",
    "product-honkarebushi-200g": "219.00",
    "product-yamaroku-tsurubishio-500ml": "149.00",
    "product-kito-yuzu-juice-100ml": "64.00",
    "product-fresh-japanese-wasabi-250g": "399.00",
    "product-hagane-zame-large": "699.00",
    "product-koshihikari-uozu-2kg": "149.00",
    "product-hishiroku-dried-rice-koji-500g": "119.00",
    "product-hishiroku-chouhaku-kin-20g": "109.00",
    "product-fresh-wasabi-50-60g": "119.00",
}

EXPECTED_DISHES = {
    "sabich",
    "beet-kubbeh",
    "schnitzel",
    "shakshuka",
    "homemade-meatballs",
    "fish-patties",
    "grilled-chicken",
    "aja-herb-omelet",
    "couscous",
    "yemenite-beef-soup",
    "sabtucha",
    "chicken-liver",
}

SAFE_STORE_FILTERS = {
    "all",
    "pantry",
    "fresh-produce",
    "chilled-frozen",
    "bakery",
    "regulated",
    "japanese-pantry",
    "equipment",
}


def php_string(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@unittest.skipUnless(shutil.which("php"), "PHP is required for catalog evaluation")
class Complete99LiveCatalogRuntimeContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        plugin_dir = php_string(PLUGIN) + "/"
        live_catalog = php_string(LIVE_CATALOG)
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_dir}');
function sanitize_file_name($value) {{ return basename((string) $value); }}
function sanitize_key($value) {{
    return strtolower((string) preg_replace('/[^a-z0-9_\\-]/i', '', (string) $value));
}}
function sanitize_title($value) {{
    return trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $value)), '-');
}}
function absint($value) {{ return abs((int) $value); }}
function wp_json_encode($value, $flags = 0) {{
    return json_encode($value, $flags | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}}
$c99_runtime_options = array();
$c99_option_write_failures = 0;
$c99_cache_flush_failures = 0;
function update_option($name, $value, $autoload = null) {{
    global $c99_runtime_options, $c99_option_write_failures;
    if ($c99_option_write_failures > 0) {{
        $c99_option_write_failures--;
        return false;
    }}
    $c99_runtime_options[$name] = $value;
    return true;
}}
function delete_option($name) {{
    global $c99_runtime_options;
    unset($c99_runtime_options[$name]);
    return true;
}}
function get_option($name, $default = false) {{
    global $c99_runtime_options;
    return array_key_exists($name, $c99_runtime_options)
        ? $c99_runtime_options[$name]
        : $default;
}}
function wp_cache_flush() {{
    global $c99_cache_flush_failures;
    if ($c99_cache_flush_failures > 0) {{
        $c99_cache_flush_failures--;
        return false;
    }}
    return true;
}}
class WP_Error {{
    private $code;
    private $message;
    public function __construct($code, $message, $data = array()) {{
        $this->code = $code;
        $this->message = $message;
    }}
    public function get_error_code() {{ return $this->code; }}
    public function get_error_message() {{ return $this->message; }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
class Complete99_Commerce {{
    const PRODUCT_APPROVED = '_complete99_store_approved';
    const PRODUCT_KIND = '_complete99_product_kind';
    const STOCK_AUTHORITY = '_complete99_stock_authority';
    const LABEL_REVIEWED = '_complete99_product_label_reviewed';
    const ORIGIN_REVIEWED = '_complete99_product_origin_reviewed';
    const CHECKOUT_ELIGIBLE = '_complete99_product_checkout_eligible';
    const RIGHTS_REVIEWED = '_complete99_product_rights_reviewed';
    const TAX_REVIEWED = '_complete99_product_tax_reviewed';
    const MEDIA_PUBLIC_SAFE = '_complete99_media_public_safe';
    const NAME_HE = '_complete99_product_name_he';
    const NAME_EN = '_complete99_product_name_en';
    const DESCRIPTION_HE = '_complete99_product_description_he';
    const DESCRIPTION_EN = '_complete99_product_description_en';
    const INGREDIENTS_HE = '_complete99_product_ingredients_he';
    const INGREDIENTS_EN = '_complete99_product_ingredients_en';
    const ALLERGENS_HE = '_complete99_product_allergens_he';
    const ALLERGENS_EN = '_complete99_product_allergens_en';
    const STORAGE_HE = '_complete99_product_storage_he';
    const STORAGE_EN = '_complete99_product_storage_en';
    const FULFILMENT_HE = '_complete99_product_fulfilment_he';
    const FULFILMENT_EN = '_complete99_product_fulfilment_en';
    const ORIGIN_HE = '_complete99_product_origin_he';
    const ORIGIN_EN = '_complete99_product_origin_en';
    const MODEL_HE = '_complete99_product_model_he';
    const MODEL_EN = '_complete99_product_model_en';
    const MATERIAL_HE = '_complete99_product_material_he';
    const MATERIAL_EN = '_complete99_product_material_en';
    const DIMENSIONS_HE = '_complete99_product_dimensions_he';
    const DIMENSIONS_EN = '_complete99_product_dimensions_en';
    const CARE_HE = '_complete99_product_care_he';
    const CARE_EN = '_complete99_product_care_en';
    const SAFETY_HE = '_complete99_product_safety_he';
    const SAFETY_EN = '_complete99_product_safety_en';
}}
$c99_fake_language = 'he';
$c99_fake_getter_contexts = array();
class C99_Fake_Product {{
    private function record($getter, $context) {{
        global $c99_fake_getter_contexts;
        $c99_fake_getter_contexts[$getter][] = $context;
    }}
    private function localized($context, $raw) {{
        global $c99_fake_language;
        return 'edit' === $context ? $raw : $raw . '-' . $c99_fake_language;
    }}
    public function is_type($type) {{ return 'simple' === $type; }}
    public function get_image_id($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? 77 : 0; }}
    public function get_sku($context = 'view') {{ $this->record(__FUNCTION__, $context); return $this->localized($context, 'product-amba-500g'); }}
    public function get_name($context = 'view') {{ $this->record(__FUNCTION__, $context); return $this->localized($context, 'עמבה 500 גרם | Amba 500 g'); }}
    public function get_price($context = 'view') {{ $this->record(__FUNCTION__, $context); return $this->localized($context, '14.90'); }}
    public function get_regular_price($context = 'view') {{ $this->record(__FUNCTION__, $context); return $this->localized($context, '14.90'); }}
    public function get_sale_price($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? '' : '1.00'; }}
    public function get_weight($context = 'view') {{ $this->record(__FUNCTION__, $context); return $this->localized($context, '0.500'); }}
    public function get_manage_stock($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context; }}
    public function get_backorders($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? 'no' : 'yes'; }}
    public function get_catalog_visibility($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? 'visible' : 'hidden'; }}
    public function get_virtual($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' !== $context; }}
    public function get_downloadable($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' !== $context; }}
    public function get_tax_status($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? 'taxable' : 'none'; }}
    public function get_category_ids($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? array(5) : array(6); }}
    public function get_tag_ids($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? array(8) : array(9); }}
    public function get_shipping_class_id($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? 10 : 11; }}
    public function get_attributes($context = 'view') {{ $this->record(__FUNCTION__, $context); return 'edit' === $context ? array('raw') : array('localized'); }}
}}
function wc_get_product($product_id) {{ return 123 === (int) $product_id ? new C99_Fake_Product() : false; }}
function get_post_type($post_id) {{ return 77 === (int) $post_id ? 'attachment' : 'product'; }}
function get_attached_file($post_id, $unfiltered = false) {{ return __FILE__; }}
function get_post_status($post_id) {{ return 'publish'; }}
function get_post_meta($post_id, $key, $single = false) {{
    if (77 === (int) $post_id) {{
        $asset = array(
            '_complete99_live_catalog_asset_managed' => 'yes',
            '_complete99_live_catalog_asset_product_code' => 'product-amba-500g',
            '_complete99_live_catalog_asset_source_sha256' => str_repeat('a', 64),
            '_complete99_media_public_safe' => 'yes',
        );
        return $asset[$key] ?? '';
    }}
    if (123 === (int) $post_id && '_complete99_catalog_product_code' === $key) {{
        return 'product-amba-500g';
    }}
    return 'fixture-' . $key;
}}
require '{live_catalog}';
$method = new ReflectionMethod('Complete99_Live_Catalog', 'load_bundle');
$method->setAccessible(true);
$bundle = $method->invoke(null);
if (is_wp_error($bundle)) {{
    throw new RuntimeException($bundle->get_error_code() . ': ' . $bundle->get_error_message());
}}
$seed_registry = require COMPLETE99_PLATFORM_DIR . 'data/catalog-product-seeds.php';
$consumer_menu = require COMPLETE99_PLATFORM_DIR . 'data/consumer-menu.php';
$seed_sources = array();
foreach ($seed_registry['products'] as $seed) {{
    $seed_sources[$seed['product_code']] = array(
        'provider' => $seed['market_observation']['source_provider'],
        'source_updated_at' => $seed['market_observation']['source_updated_at'],
    );
}}
$products = array();
foreach ($bundle['products'] as $code => $record) {{
    $products[$code] = array(
        'price' => $record['price'],
        'ingredient' => $record['ingredient'],
        'product_kind' => $record['product_kind'],
        'classification' => $record['classification'],
        'name' => $record['name'],
        'package' => $record['package'],
        'price_evidence' => array_merge($record['price_evidence'], $seed_sources[$code]),
        'public' => $record['public'],
        'relations' => $record['relations'],
        'asset' => array(
            'filename' => $record['asset']['filename'],
            'relative_path' => str_replace(COMPLETE99_PLATFORM_DIR, '', $record['asset']['path']),
            'sha256' => $record['asset']['sha256'],
            'width' => $record['asset']['width'],
            'height' => $record['asset']['height'],
            'alt' => $record['asset']['alt'],
        ),
    );
}}
$normalize_option = new ReflectionMethod('Complete99_Live_Catalog', 'normalize_store_option_value');
$normalize_option->setAccessible(true);
$digest_value = new ReflectionMethod('Complete99_Live_Catalog', 'digest');
$digest_value->setAccessible(true);
$product_identity = new ReflectionMethod('Complete99_Live_Catalog', 'product_identity');
$product_identity->setAccessible(true);
$c99_fake_language = 'he';
$identity_he = $product_identity->invoke(null, 123, false);
$c99_fake_language = 'en';
$identity_en = $product_identity->invoke(null, 123, false);
$identity_contexts = array();
foreach ($c99_fake_getter_contexts as $getter => $contexts) {{
    $identity_contexts[$getter] = array_values(array_unique($contexts));
}}
ksort($identity_contexts, SORT_STRING);
$page_option_names = array(
    'woocommerce_shop_page_id',
    'woocommerce_terms_page_id',
    'wp_page_for_privacy_policy',
    'woocommerce_cart_page_id',
    'woocommerce_checkout_page_id',
    'woocommerce_myaccount_page_id',
);
$cached_option_values = array();
$database_option_values = array();
foreach ($page_option_names as $position => $option_name) {{
    $page_id = 700 + $position;
    $cached_option_values[$option_name] = $normalize_option->invoke(null, $option_name, $page_id);
    $database_option_values[$option_name] = $normalize_option->invoke(null, $option_name, (string) $page_id);
}}
$changed_option_values = $database_option_values;
$changed_option_values['woocommerce_cart_page_id'] = 999;
$invalid_page_option = $normalize_option->invoke(null, 'woocommerce_cart_page_id', '12x');
$missing_page_option = $normalize_option->invoke(null, 'woocommerce_cart_page_id', null);
$invalid_text_option = $normalize_option->invoke(null, 'woocommerce_currency', 123);
$strict_failure = new ReflectionMethod('Complete99_Live_Catalog', 'strict_readback_failure_message');
$strict_failure->setAccessible(true);
$receipt_matches = new ReflectionMethod('Complete99_Live_Catalog', 'readback_receipt_matches_marker');
$receipt_matches->setAccessible(true);
$seal_marker = new ReflectionMethod('Complete99_Live_Catalog', 'seal_recovery_marker');
$seal_marker->setAccessible(true);
$restore_marker = new ReflectionMethod('Complete99_Live_Catalog', 'restore_recovery_boundary');
$restore_marker->setAccessible(true);
$clear_boundary = new ReflectionMethod('Complete99_Live_Catalog', 'clear_recovery_boundary');
$clear_boundary->setAccessible(true);
$test_marker = $seal_marker->invoke(null, array(
    'schema' => 'complete99-live-catalog-recovery/v2',
    'state' => 'materializing',
));
$c99_option_write_failures = 1;
$restored_after_retry = $restore_marker->invoke(
    null,
    $test_marker,
    'postcommit_verification_failed'
);
$restored_marker = get_option('complete99_live_catalog_recovery_required', array());
$c99_runtime_options = array();
$c99_option_write_failures = 20;
$persistent_restore_failure = $restore_marker->invoke(
    null,
    $test_marker,
    'postcommit_verification_failed'
);
$c99_runtime_options = array(
    'complete99_live_catalog_recovery_required' => $test_marker,
);
$c99_option_write_failures = 20;
$c99_cache_flush_failures = 1;
$clear_restore_failure = $clear_boundary->invoke(null, $test_marker);
$matching_marker = array('mutation_id' => 'mutation-12345678', 'deployment_id' => 'deployment-12345678');
$matching_readback = array(
    'ready' => true,
    'receipt' => array('mutation_id' => 'mutation-12345678', 'deployment_id' => 'deployment-12345678'),
);
echo wp_json_encode(array(
    'products' => $products,
    'policy' => $bundle['policy'],
    'prices' => $bundle['price_registry'],
    'relations' => $bundle['relations'],
    'consumer_menu' => $consumer_menu,
    'product_identity' => array(
        'equal_across_languages' => $digest_value->invoke(null, $identity_he) === $digest_value->invoke(null, $identity_en),
        'raw_name' => $identity_en['name'] ?? '',
        'contexts' => $identity_contexts,
    ),
    'normalization' => array(
        'page_int' => $normalize_option->invoke(null, 'woocommerce_cart_page_id', 123),
        'page_string' => $normalize_option->invoke(null, 'woocommerce_cart_page_id', '123'),
        'text' => $normalize_option->invoke(null, 'woocommerce_currency', 'ILS'),
        'cached_digest' => $digest_value->invoke(null, $cached_option_values),
        'database_digest' => $digest_value->invoke(null, $database_option_values),
        'changed_digest' => $digest_value->invoke(null, $changed_option_values),
        'invalid_page_code' => is_wp_error($invalid_page_option) ? $invalid_page_option->get_error_code() : '',
        'missing_page_code' => is_wp_error($missing_page_option) ? $missing_page_option->get_error_code() : '',
        'invalid_text_code' => is_wp_error($invalid_text_option) ? $invalid_text_option->get_error_code() : '',
    ),
    'strict_failures' => array(
        'configuration' => $strict_failure->invoke(null, array('reason' => 'store_configuration_mismatch')),
        'product' => $strict_failure->invoke(null, array(
            'reason' => 'product_readback_mismatch',
            'product_code' => 'product-tahini-500g',
        )),
        'untrusted_product' => $strict_failure->invoke(null, array(
            'reason' => 'product_readback_mismatch',
            'product_code' => 'product-secretvalue',
        )),
        'receipt_identity' => $strict_failure->invoke(null, array('reason' => 'receipt_identity_mismatch')),
    ),
    'receipt_identity' => array(
        'matching' => $receipt_matches->invoke(null, $matching_readback, $matching_marker),
        'wrong_mutation' => $receipt_matches->invoke(null, array_replace_recursive(
            $matching_readback,
            array('receipt' => array('mutation_id' => 'mutation-87654321'))
        ), $matching_marker),
        'wrong_deployment' => $receipt_matches->invoke(null, array_replace_recursive(
            $matching_readback,
            array('receipt' => array('deployment_id' => 'deployment-87654321'))
        ), $matching_marker),
    ),
    'recovery_restore' => array(
        'retry_succeeded' => $restored_after_retry,
        'stored_state' => $restored_marker['state'] ?? '',
        'persistent_failure_rejected' => !$persistent_restore_failure,
        'clear_failure_code' => is_wp_error($clear_restore_failure)
            ? $clear_restore_failure->get_error_code()
            : '',
    ),
));
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=30,
            check=True,
        )
        cls.bundle = json.loads(completed.stdout)
        cls.live_catalog = LIVE_CATALOG.read_text(encoding="utf-8")
        cls.commerce = COMMERCE.read_text(encoding="utf-8")
        cls.consumer = CONSUMER.read_text(encoding="utf-8")
        cls.content = CONTENT.read_text(encoding="utf-8")
        cls.frontend = FRONTEND.read_text(encoding="utf-8")
        cls.rest = REST.read_text(encoding="utf-8")
        cls.live_dish_sitemap = LIVE_DISH_SITEMAP.read_text(encoding="utf-8")
        cls.consumer_content = CONSUMER_CONTENT.read_text(encoding="utf-8")
        cls.script = PUBLIC_SCRIPT.read_text(encoding="utf-8")
        cls.css = CONSUMER_CSS.read_text(encoding="utf-8")
        cls.materializer = MATERIALIZER.read_text(encoding="utf-8")

    def test_release_version_is_exact_1_15_0(self) -> None:
        source = MAIN.read_text(encoding="utf-8")
        self.assertRegex(source, r"(?m)^ \* Version:\s+1\.15\.0$")
        self.assertIn("define( 'COMPLETE99_PLATFORM_VERSION', '1.15.0' );", source)
        self.assertIn("define( 'COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'c99-wp-1.15.0' );", source)

    def test_product_receipt_identity_uses_unfiltered_edit_context(self) -> None:
        identity = self.live_catalog.split(
            "private static function product_identity", 1
        )[1].split("private static function receipt_contract_is_valid", 1)[0]
        for getter in (
            "get_image_id",
            "get_sku",
            "get_name",
            "get_price",
            "get_regular_price",
            "get_sale_price",
            "get_weight",
            "get_manage_stock",
            "get_backorders",
            "get_catalog_visibility",
            "get_virtual",
            "get_downloadable",
            "get_tax_status",
            "get_category_ids",
            "get_tag_ids",
            "get_shipping_class_id",
            "get_attributes",
        ):
            self.assertIn(f"{getter}( 'edit' )", identity)
        self.assertNotIn("managing_stock()", identity)
        self.assertNotIn("get_name()", identity)
        runtime = self.bundle["product_identity"]
        self.assertTrue(runtime["equal_across_languages"])
        self.assertEqual("עמבה 500 גרם | Amba 500 g", runtime["raw_name"])
        self.assertEqual(
            {
                getter: ["edit"]
                for getter in (
                    "get_attributes",
                    "get_backorders",
                    "get_catalog_visibility",
                    "get_category_ids",
                    "get_downloadable",
                    "get_image_id",
                    "get_manage_stock",
                    "get_name",
                    "get_price",
                    "get_regular_price",
                    "get_sale_price",
                    "get_shipping_class_id",
                    "get_sku",
                    "get_tag_ids",
                    "get_tax_status",
                    "get_virtual",
                    "get_weight",
                )
            },
            runtime["contexts"],
        )

    def test_initial_stock_receipts_use_unfiltered_edit_context(self) -> None:
        for old_call in (
            "managing_stock()",
            "get_stock_quantity()",
            "get_stock_status()",
            "get_backorders()",
        ):
            self.assertNotIn(old_call, self.live_catalog)
        for raw_call in (
            "get_manage_stock( 'edit' )",
            "get_stock_quantity( 'edit' )",
            "get_stock_status( 'edit' )",
            "get_backorders( 'edit' )",
        ):
            self.assertGreaterEqual(self.live_catalog.count(raw_call), 2)

    def test_runtime_bundle_has_exact_allowlist_and_price_map(self) -> None:
        products = self.bundle["products"]
        price_registry = self.bundle["prices"]
        self.assertEqual(36, len(products))
        self.assertEqual(set(EXPECTED_PRICES), set(products))
        self.assertEqual(EXPECTED_PRICES, {code: row["price"] for code, row in products.items()})
        self.assertEqual("complete99-live-catalog-prices/v1", price_registry["schema"])
        self.assertEqual("ILS", price_registry["currency"])
        self.assertEqual("2026-08-06", price_registry["reviewed_at"])
        self.assertEqual(
            "owner_authorized_opening_retail_price_informed_by_market_observation",
            price_registry["price_scope"],
        )
        selection_rule = price_registry["evidence"]["selection_rule"].lower()
        for required_phrase in (
            "owner-authorized opening retail price",
            "market observation",
            "not represented as the exact observed third-party price",
        ):
            self.assertIn(required_phrase, selection_rule)
        self.assertEqual(EXPECTED_PRICES, price_registry["prices"])

    def test_store_configuration_snapshot_normalizes_option_types_fail_closed(self) -> None:
        normalization = self.bundle["normalization"]
        self.assertEqual(123, normalization["page_int"])
        self.assertEqual(123, normalization["page_string"])
        self.assertEqual("ILS", normalization["text"])
        self.assertEqual(
            normalization["cached_digest"],
            normalization["database_digest"],
        )
        self.assertNotEqual(
            normalization["database_digest"],
            normalization["changed_digest"],
        )
        self.assertEqual(
            "complete99_live_catalog_option_type_invalid",
            normalization["invalid_page_code"],
        )
        self.assertEqual(
            "complete99_live_catalog_option_type_invalid",
            normalization["missing_page_code"],
        )
        self.assertEqual(
            "complete99_live_catalog_option_type_invalid",
            normalization["invalid_text_code"],
        )

    def test_strict_readback_failure_uses_closed_reason_and_product_codes(self) -> None:
        failures = self.bundle["strict_failures"]
        self.assertTrue(
            failures["configuration"].startswith(
                "complete99_live_catalog_strict_readback_store_configuration_mismatch:"
            )
        )
        self.assertIn("product-tahini-500g", failures["product"])
        self.assertNotIn("product-secretvalue", failures["untrusted_product"])
        self.assertTrue(
            failures["receipt_identity"].startswith(
                "complete99_live_catalog_strict_readback_receipt_identity_mismatch:"
            )
        )

    def test_strict_readback_receipt_identity_matches_both_marker_ids(self) -> None:
        identity = self.bundle["receipt_identity"]
        self.assertTrue(identity["matching"])
        self.assertFalse(identity["wrong_mutation"])
        self.assertFalse(identity["wrong_deployment"])

    def test_every_price_has_a_bound_https_source_and_dates(self) -> None:
        expected_provider_hosts = {
            "pricez": {"www.pricez.co.il", "prices.pricez.co.il"},
            "chp": {"chp.co.il"},
            "carrefour": {"www.carrefour.co.il"},
            "rishiri_kombu_direct": {"www.rishirikonbu.com"},
            "japanese_taste": {"int.japanesetaste.com"},
            "yamaroku_direct": {"yama-roku.net"},
            "ogon_no_mura_direct": {"shop.ogonnomura.jp"},
            "the_wasabi_company": {"www.thewasabicompany.co.uk"},
            "yamamoto_foods_official": {"www.yamamotofoods.co.jp"},
            "dutch_wasabi": {"www.dutchwasabi.nl"},
            "hishiroku_moyashi": {"1469.stores.jp"},
        }
        source_urls = set()
        for code, product in self.bundle["products"].items():
            evidence = product["price_evidence"]
            parsed = urlparse(evidence["source_url"])
            self.assertEqual("https", parsed.scheme, code)
            self.assertTrue(parsed.hostname, code)
            self.assertIn(evidence["provider"], expected_provider_hosts, code)
            self.assertIn(
                parsed.hostname,
                expected_provider_hosts[evidence["provider"]],
                code,
            )
            checked = date.fromisoformat(evidence["accessed_at"])
            updated = date.fromisoformat(evidence["source_updated_at"])
            self.assertIn(checked, {date(2026, 7, 31), date(2026, 8, 6)}, code)
            self.assertLessEqual(updated, checked, code)
            source_urls.add(evidence["source_url"])
        self.assertEqual(36, len(source_urls))

    def test_import_source_price_and_fx_evidence_remains_distinct_from_public_price(self) -> None:
        cases = {
            "product-fresh-japanese-wasabi-250g": (
                "399.00",
                "252.81",
                "62.50",
                "GBP",
                "4.0450",
                "ILS_per_GBP",
                "2026-08-05",
            ),
            "product-hagane-zame-large": (
                "699.00",
                "324.79",
                "17050",
                "JPY",
                "1.9049",
                "ILS_per_100_JPY",
                "2026-08-05",
            ),
            "product-koshihikari-uozu-2kg": (
                "149.00",
                "58.95",
                "16.95",
                "EUR",
                "3.4776",
                "ILS_per_EUR",
                "2026-08-06",
            ),
            "product-hishiroku-dried-rice-koji-500g": (
                "119.00",
                "20.04",
                "1050",
                "JPY",
                "1.9088",
                "ILS_per_100_JPY",
                "2026-08-06",
            ),
            "product-hishiroku-chouhaku-kin-20g": (
                "109.00",
                "12.03",
                "630",
                "JPY",
                "1.9088",
                "ILS_per_100_JPY",
                "2026-08-06",
            ),
            "product-fresh-wasabi-50-60g": (
                "119.00",
                "60.89",
                "17.51",
                "EUR",
                "3.4776",
                "ILS_per_EUR",
                "2026-08-06",
            ),
        }
        for code, expected in cases.items():
            with self.subTest(product=code):
                public_price, observed, amount, currency, rate, basis, rate_date = expected
                product = self.bundle["products"][code]
                evidence = product["price_evidence"]
                self.assertEqual(public_price, product["price"])
                self.assertEqual(observed, evidence["observed_price"])
                self.assertNotEqual(public_price, observed)
                self.assertEqual(amount, evidence["source_price"]["amount"])
                self.assertEqual(currency, evidence["source_price"]["currency"])
                self.assertEqual(rate, evidence["fx_conversion"]["rate"])
                self.assertEqual(basis, evidence["fx_conversion"]["basis"])
                self.assertEqual(rate_date, evidence["fx_conversion"]["rate_date"])
                self.assertEqual(observed, evidence["fx_conversion"]["converted_amount_ils"])
                self.assertEqual(
                    "https://www.boi.org.il/roles/markets/exchangerates/",
                    evidence["fx_conversion"]["source_url"],
                )

    def test_relations_are_complete_reciprocal_and_have_unique_anchors(self) -> None:
        product_relations = self.bundle["relations"]["products"]
        dish_relations = self.bundle["relations"]["dishes"]
        self.assertEqual(set(EXPECTED_PRICES), set(product_relations))
        self.assertEqual(EXPECTED_DISHES, set(dish_relations))

        ingredient_codes = []
        for code, relation in product_relations.items():
            ingredient = relation["ingredient_code"]
            dishes = relation["dish_slugs"]
            science_entity_id = relation.get("science_entity_id", "")
            related_product_codes = relation.get("related_product_codes", [])
            self.assertRegex(ingredient, r"^(?:ingredient|equipment)-[a-z0-9-]+$", code)
            self.assertTrue(dishes or science_entity_id, code)
            if science_entity_id:
                self.assertRegex(
                    science_entity_id,
                    r"^[a-z0-9]+(?:-[a-z0-9]+)*$",
                    code,
                )
            self.assertEqual(len(dishes), len(set(dishes)), code)
            self.assertTrue(set(dishes).issubset(EXPECTED_DISHES), code)
            self.assertEqual(
                len(related_product_codes), len(set(related_product_codes)), code
            )
            self.assertNotIn(code, related_product_codes)
            self.assertTrue(
                set(related_product_codes).issubset(EXPECTED_PRICES), code
            )
            ingredient_codes.append(ingredient)
            for dish in dishes:
                self.assertIn(code, dish_relations[dish], f"{code} -> {dish}")

        self.assertEqual(36, len(set(ingredient_codes)))
        self.assertEqual(
            {
                "product-kito-yuzu-juice-100ml",
                "product-rishiri-kombu-100g",
                "product-honkarebushi-200g",
                "product-fresh-japanese-wasabi-250g",
                "product-koshihikari-uozu-2kg",
                "product-fresh-wasabi-50-60g",
            },
            set(
                product_relations["product-yamaroku-tsurubishio-500ml"][
                    "related_product_codes"
                ]
            ),
        )
        self.assertEqual(
            ["product-yamaroku-tsurubishio-500ml"],
            product_relations["product-kito-yuzu-juice-100ml"][
                "related_product_codes"
            ],
        )
        for dish, codes in dish_relations.items():
            self.assertTrue(codes, dish)
            self.assertEqual(len(codes), len(set(codes)), dish)
            for code in codes:
                self.assertIn(dish, product_relations[code]["dish_slugs"], f"{dish} -> {code}")

        ingredient_index = self.consumer.split(
            "private static function render_live_ingredient_index", 1
        )[1].split("private static function render_group_order_page", 1)[0]
        product_card = self.consumer.split(
            "private static function render_store_product_card", 1
        )[1].split("private static function render_related_store_products", 1)[0]
        self.assertIn('id="<?php echo esc_attr( $ingredient ); ?>"', ingredient_index)
        self.assertIn("sanitize_html_class", ingredient_index)
        self.assertIn("self::route( 'store', $lang ) . '#c99-product-code-'", ingredient_index)
        self.assertIn("self::route( 'ingredients', $lang ) . '#'", product_card)
        self.assertIn(
            'id="c99-product-code-<?php echo esc_attr( sanitize_html_class( $product_code ) ); ?>"',
            product_card,
        )
        self.assertIn('data-c99-product-id="<?php echo esc_attr( $product_id ); ?>"', product_card)
        self.assertIn('tabindex="-1"', product_card)
        self.assertIn(".c99-store-product-card:target", self.css)
        self.assertNotIn("c99-store-product-stable-anchor", product_card)
        self.assertNotIn("'#c99-product-' . absint( $product_id )", self.consumer)
        self.assertNotIn("'#c99-product-' . absint( $product_id )", self.frontend)
        self.assertIn("render_live_ingredient_index( $lang )", self.consumer)

        relation_validation = self.live_catalog.split(
            "if ( '' !== $science_entity_id )", 1
        )[1].split("foreach ( $public['tags'] as $tag )", 1)[0]
        for marker in (
            "$science_entities[ $science_entity_id ]",
            "'public_discovery'",
            "'approved_public'",
            "'public_api'",
            "'public_page'",
            "'canonical_path'",
            "'active_offer'",
            "'public_offer_allowed'",
            "'woo_product_code'",
        ):
            self.assertIn(marker, relation_validation)

    def test_product_assets_are_unique_webp_files_with_exact_hashes(self) -> None:
        hashes = set()
        filenames = set()
        for code, product in self.bundle["products"].items():
            asset = product["asset"]
            path = PLUGIN / asset["relative_path"]
            self.assertEqual(".webp", path.suffix.lower(), code)
            self.assertEqual(path.name, asset["filename"], code)
            self.assertTrue(path.is_file(), path)
            self.assertTrue(re.fullmatch(r"[a-f0-9]{64}", asset["sha256"]), code)
            self.assertEqual(asset["sha256"], hashlib.sha256(path.read_bytes()).hexdigest(), code)
            self.assertGreaterEqual(asset["width"], 1000, code)
            self.assertGreaterEqual(asset["height"], 700, code)
            self.assertGreater(path.stat().st_size, 50_000, code)
            self.assertRegex(asset["alt"]["he"], r"[\u0590-\u05ff]", code)
            self.assertTrue(asset["alt"]["en"].strip(), code)
            hashes.add(asset["sha256"])
            filenames.add(asset["filename"])
        self.assertEqual(36, len(hashes))
        self.assertEqual(36, len(filenames))

    def test_product_policy_is_bilingual_and_taxonomy_bounded(self) -> None:
        policy = self.bundle["policy"]
        self.assertEqual("complete99-live-catalog-products/v1", policy["schema"])
        self.assertEqual("2026-08-06", policy["reviewed_at"])
        self.assertIs(policy["catalog_publication_authorized"], True)
        self.assertIs(policy["supplier_label_reviewed"], False)
        self.assertIs(policy["country_of_origin_reviewed"], False)
        self.assertIs(policy["checkout_eligible"], False)
        self.assertEqual(1, policy["initial_stock"])
        self.assertEqual("no", policy["backorders"])
        self.assertEqual("taxable", policy["tax_status"])
        self.assertEqual(set(EXPECTED_PRICES), set(policy["products"]))
        self.assertEqual(
            {
                "pantry",
                "bakery",
                "produce",
                "eggs",
                "protein",
                "japanese-pantry",
                "japanese-equipment",
            },
            set(policy["categories"]),
        )
        self.assertEqual(
            {"ambient", "fresh", "chilled", "frozen", "equipment"},
            set(policy["shipping_classes"]),
        )
        self.assertEqual(
            {
                "ambient",
                "fresh",
                "chilled",
                "frozen",
                "condiment",
                "grain",
                "spice",
                "produce",
                "protein",
                "japanese",
                "dashi",
                "fish",
                "fermentation",
                "shoyu",
                "soy",
                "wheat",
                "seasoning",
                "yuzu",
                "citrus",
                "premium",
                "wasabi",
                "rice",
                "koji",
                "starter-culture",
                "equipment",
            },
            set(policy["tags"]),
        )

        term_slugs = []
        for group in ("categories", "tags", "shipping_classes"):
            for definition in policy[group].values():
                self.assertRegex(definition["slug"], r"^complete99-[a-z0-9-]+$")
                self.assertIn(" | ", definition["name"])
                term_slugs.append(definition["slug"])
        self.assertEqual(len(term_slugs), len(set(term_slugs)))

        for code, product in policy["products"].items():
            self.assertGreater(float(product["weight_kg"]), 0, code)
            self.assertIn(product["category"], policy["categories"], code)
            self.assertIn(product["shipping_class"], policy["shipping_classes"], code)
            self.assertTrue(product["tags"], code)
            self.assertTrue(set(product["tags"]).issubset(policy["tags"]), code)
            self.assertIn(product["product_kind"], {"food", "equipment"}, code)
            if product["product_kind"] == "food":
                for field in ("ingredients", "allergens", "storage"):
                    self.assertTrue(product[field]["he"].strip(), f"{code}:{field}:he")
                    self.assertTrue(product[field]["en"].strip(), f"{code}:{field}:en")
                for field in ("model", "material", "dimensions", "care", "safety"):
                    self.assertEqual("", product[field]["he"], f"{code}:{field}:he")
                    self.assertEqual("", product[field]["en"], f"{code}:{field}:en")
            else:
                for field in ("ingredients", "allergens", "storage"):
                    self.assertEqual("", product[field]["he"], f"{code}:{field}:he")
                    self.assertEqual("", product[field]["en"], f"{code}:{field}:en")
                for field in ("model", "material", "dimensions", "care", "safety"):
                    self.assertTrue(product[field]["he"].strip(), f"{code}:{field}:he")
                    self.assertTrue(product[field]["en"].strip(), f"{code}:{field}:en")
            self.assertTrue(self.bundle["products"][code]["name"]["he"].strip(), code)
            self.assertTrue(self.bundle["products"][code]["name"]["en"].strip(), code)
            self.assertTrue(self.bundle["products"][code]["package"]["he"].strip(), code)
            self.assertTrue(self.bundle["products"][code]["package"]["en"].strip(), code)

        wasabi = policy["products"]["product-fresh-japanese-wasabi-250g"]
        self.assertIn("\u05ea\u05d5\u05d5\u05d9\u05ea \u05d4\u05de\u05d5\u05e6\u05e8", wasabi["allergens"]["he"])
        self.assertIn("supplied product label", wasabi["allergens"]["en"])
        self.assertNotIn("\u05dc\u05d0 \u05d9\u05d3\u05d5\u05e2 \u05e2\u05dc \u05d0\u05dc\u05e8\u05d2\u05df", wasabi["allergens"]["he"])
        self.assertNotIn("No inherent allergen", wasabi["allergens"]["en"])

        for marker in (
            "Complete99_Commerce::PRODUCT_APPROVED      => 'yes'",
            "Complete99_Commerce::PRODUCT_KIND          => $record['product_kind']",
            "Complete99_Commerce::LABEL_REVIEWED        => ! empty( $bundle['policy']['supplier_label_reviewed'] ) ? 'yes' : 'no'",
            "Complete99_Commerce::ORIGIN_REVIEWED       => ! empty( $bundle['policy']['country_of_origin_reviewed'] ) ? 'yes' : 'no'",
            "Complete99_Commerce::CHECKOUT_ELIGIBLE     => ! empty( $bundle['policy']['checkout_eligible'] ) ? 'yes' : 'no'",
        ):
            self.assertIn(marker, self.live_catalog)

    def test_typed_product_meta_is_registered_saved_materialized_and_signed(self) -> None:
        exact_meta = {
            "PRODUCT_KIND": "_complete99_product_kind",
            "MODEL_HE": "_complete99_product_model_he",
            "MODEL_EN": "_complete99_product_model_en",
            "MATERIAL_HE": "_complete99_product_material_he",
            "MATERIAL_EN": "_complete99_product_material_en",
            "DIMENSIONS_HE": "_complete99_product_dimensions_he",
            "DIMENSIONS_EN": "_complete99_product_dimensions_en",
            "CARE_HE": "_complete99_product_care_he",
            "CARE_EN": "_complete99_product_care_en",
            "SAFETY_HE": "_complete99_product_safety_he",
            "SAFETY_EN": "_complete99_product_safety_en",
        }
        for constant, meta_key in exact_meta.items():
            with self.subTest(constant=constant):
                self.assertRegex(
                    self.commerce,
                    rf"const\s+{constant}\s*=\s*'{re.escape(meta_key)}';",
                )
                self.assertIn(f"Complete99_Commerce::{constant}", self.live_catalog)

        readiness_fields = self.commerce.split(
            "public static function render_product_readiness_fields", 1
        )[1].split("public static function save_product_readiness_fields", 1)[0]
        readiness_save = self.commerce.split(
            "public static function save_product_readiness_fields", 1
        )[1].split("private static function mark_commerce_configuration_dirty", 1)[0]
        copy_contract = self.commerce.split(
            "private static function product_copy_matches_declared_languages", 1
        )[1].split("public static function storefront_product_ids", 1)[0]
        for constant in exact_meta:
            self.assertIn(f"self::{constant}", readiness_fields)
            self.assertIn(f"self::{constant}", readiness_save)
        self.assertIn("array( 'food', 'equipment' )", copy_contract)
        self.assertIn("if ( 'food' === $product_kind )", copy_contract)
        for constant in (
            "INGREDIENTS_HE",
            "INGREDIENTS_EN",
            "ALLERGENS_HE",
            "ALLERGENS_EN",
            "STORAGE_HE",
            "STORAGE_EN",
            "MODEL_HE",
            "MODEL_EN",
            "MATERIAL_HE",
            "MATERIAL_EN",
            "DIMENSIONS_HE",
            "DIMENSIONS_EN",
            "CARE_HE",
            "CARE_EN",
            "SAFETY_HE",
            "SAFETY_EN",
        ):
            self.assertIn(f"self::{constant}", copy_contract)
        self.assertIn("'professional_equipment'      => 'equipment'", self.live_catalog)

    def test_public_store_is_never_the_native_woocommerce_shop_page(self) -> None:
        pages = self.live_catalog.split("private static function ensure_woocommerce_pages", 1)[
            1
        ].split("private static function ensure_standard_vat_rate", 1)[0]
        self.assertIn(
            "$store_id   = Complete99_Content::find_translation_post_id( 'store', 'he', true );",
            pages,
        )
        self.assertIn("$native_shop_id = absint( get_option( 'woocommerce_shop_page_id', 0 ) );", pages)
        self.assertIn("$native_shop_id === absint( $store_id )", pages)
        self.assertIn("wc_create_page( 'shop', 'woocommerce_shop_page_id', 'Shop', '' );", pages)
        self.assertIn("'publish' !== (string) get_post_status( $native_shop_id )", pages)
        self.assertIn("must be published and distinct from the public Complete99 store", pages)
        self.assertNotRegex(
            pages,
            r"update_option\(\s*['\"]woocommerce_shop_page_id['\"]\s*,\s*absint\(\s*\$store_id",
        )

    def test_materializer_initializes_stock_only_for_new_products_and_reads_marker(self) -> None:
        dry_run = self.live_catalog.split("public static function dry_run", 1)[1].split(
            "public static function materialize", 1
        )[0]
        ensure_product = self.live_catalog.split("private static function ensure_product", 1)[
            1
        ].split("private static function product_attributes", 1)[0]
        for marker in (
            "'stock_action' => $is_new ? 'initialize' : 'preserve'",
            "'initial_stock' => $is_new ? 1 : null",
            "'backorders' => 'no'",
            "'product_count'   => count( $actions )",
        ):
            self.assertIn(marker, dry_run)
        for marker in (
            "$product->set_status( 'publish' );",
            "$product->set_catalog_visibility( 'visible' );",
            "$product->set_manage_stock( true );",
            "$product->set_backorders( 'no' );",
            "'_complete99_live_catalog_initial_stock'   => '1'",
            "'_complete99_live_catalog_currency'        => 'ILS'",
        ):
            self.assertIn(marker, ensure_product)

        self.assertIn(
            "const META_STOCK_INITIALIZED = '_complete99_live_catalog_stock_initialized';",
            self.live_catalog,
        )
        self.assertIn("$sets_initial_stock = ! $existing_id;", ensure_product)
        initial_branch = ensure_product.split("if ( $sets_initial_stock )", 1)[1].split(
            "$product->set_backorders( 'no' );", 1
        )[0]
        self.assertIn("$product->set_stock_quantity( 1 );", initial_branch)
        self.assertIn("$product->set_stock_status( 'instock' );", initial_branch)
        self.assertEqual(1, ensure_product.count("set_stock_quantity"))
        self.assertEqual(1, ensure_product.count("set_stock_status"))
        self.assertNotIn("metadata_exists", ensure_product)
        readback = ensure_product.split("$initial_stock_readback = wc_get_product", 1)[1].split(
            "$meta = array(", 1
        )[0]
        for marker in (
            "$initial_stock_readback->get_manage_stock( 'edit' )",
            "$initial_stock_readback->get_stock_quantity( 'edit' )",
            "$initial_stock_readback->get_stock_status( 'edit' )",
            "$initial_stock_readback->get_backorders( 'edit' )",
        ):
            self.assertIn(marker, readback)
        self.assertIn("self::META_STOCK_INITIALIZED                => 'yes'", ensure_product)

        preflight = self.live_catalog.split("private static function preflight", 1)[1].split(
            "private static function ensure_store_configuration", 1
        )[0]
        identity = self.live_catalog.split("private static function product_identity", 1)[1].split(
            "private static function receipt_contract_is_valid", 1
        )[0]
        self.assertIn("get_post_meta( $product_id, self::META_STOCK_INITIALIZED, true )", preflight)
        self.assertIn("self::META_STOCK_INITIALIZED", identity)

        materialize = self.live_catalog.split("public static function materialize", 1)[1].split(
            "public static function product_ids", 1
        )[0]
        receipt_validation = self.live_catalog.split(
            "private static function receipt_contract_is_valid", 1
        )[1].split("private static function transactional_storage_preflight", 1)[0]
        for marker in (
            "$initial_stock_receipts",
            "'initialized_now' => $initialized_now",
            "'initial_stock_receipts' => $initial_stock_receipts",
            "'initial_stock_digest'   => self::digest( $initial_stock_receipts )",
        ):
            self.assertIn(marker, materialize)
        self.assertIn("self::digest( $receipt['initial_stock_receipts'] )", receipt_validation)
        self.assertIn("true === $stock['initialized_now']", receipt_validation)

    def test_materialization_has_checked_transaction_recovery_and_cache_contract(self) -> None:
        materialize = self.live_catalog.split("public static function materialize", 1)[1].split(
            "public static function product_ids", 1
        )[0]
        transaction_helper = self.live_catalog.split("private static function transaction_statement", 1)[
            1
        ].split("private static function create_recovery_marker", 1)[0]
        recovery_helpers = self.live_catalog.split("private static function create_recovery_marker", 1)[
            1
        ].split("private static function query_ids", 1)[0]
        page_cache_helper = self.live_catalog.split(
            "private static function purge_public_page_caches_with_retry", 1
        )[1].split("private static function recovery_baseline_matches", 1)[0]
        file_recovery = self.live_catalog.split("private static function catalog_upload_stems", 1)[
            1
        ].split("private static function canonicalize", 1)[0]
        status = self.live_catalog.split("public static function status", 1)[1].split(
            "private static function woocommerce_dependency", 1
        )[0]
        storage = self.live_catalog.split("private static function transactional_storage_preflight", 1)[
            1
        ].split("private static function transaction_statement", 1)[0]

        self.assertIn(
            "const OPTION_RECOVERY = 'complete99_live_catalog_recovery_required';",
            self.live_catalog,
        )
        self.assertIn(
            "const RECOVERY_SCHEMA = 'complete99-live-catalog-recovery/v2';",
            self.live_catalog,
        )
        self.assertIn("self::transactional_storage_preflight()", materialize)
        self.assertIn("self::write_recovery_marker( $marker )", materialize)
        self.assertIn("if ( ! self::transaction_statement( 'START TRANSACTION' ) )", materialize)
        self.assertIn("if ( ! self::transaction_statement( 'COMMIT' ) )", materialize)
        self.assertIn("$rollback_verified = self::transaction_statement( 'ROLLBACK' );", materialize)
        self.assertIn("$commit_attempted", materialize)
        self.assertIn("$rollback_verified", materialize)
        self.assertIn("$committed", materialize)
        for failure_state in (
            "postcommit_verification_failed",
            "commit_unverified",
            "rollback_unverified",
        ):
            self.assertIn(failure_state, materialize)
        self.assertIn("complete99_live_catalog_recovery_required", materialize)

        lock_position = materialize.index("$lock = self::acquire_lock();")
        marker_read_position = materialize.index(
            "$existing_recovery = get_option( self::OPTION_RECOVERY, $missing_recovery );"
        )
        baseline_position = materialize.index(
            "$baseline = self::recovery_database_baseline( $repeat );"
        )
        journal_position = materialize.index(
            "$journal  = self::build_recovery_file_journal();"
        )
        start_position = materialize.index("self::transaction_statement( 'START TRANSACTION' )")
        self.assertLess(lock_position, marker_read_position)
        post_lock_flush = materialize.index("self::flush_catalog_caches()", lock_position)
        self.assertLess(lock_position, post_lock_flush)
        self.assertLess(post_lock_flush, marker_read_position)
        self.assertLess(baseline_position, start_position)
        self.assertLess(journal_position, start_position)
        strict_position = materialize.index("$readback = self::status( true, true );")
        flush_position = materialize.rfind("self::flush_catalog_caches()", 0, strict_position)
        self.assertGreater(flush_position, start_position)
        self.assertLess(flush_position, strict_position)
        self.assertIn("'mutation_id'      => $marker['mutation_id']", materialize)
        commit_position = materialize.index("self::transaction_statement( 'COMMIT' )")
        page_purge_position = materialize.index(
            "$page_cache_purge = self::purge_public_page_caches_with_retry();"
        )
        clear_boundary_position = materialize.index(
            "self::clear_recovery_boundary( $marker )"
        )
        final_readback_position = materialize.index(
            "$readback = self::status( true );", clear_boundary_position
        )
        self.assertLess(commit_position, clear_boundary_position)
        self.assertLess(clear_boundary_position, final_readback_position)
        self.assertLess(final_readback_position, page_purge_position)
        self.assertIn("'page_cache_purge' => $page_cache_purge", materialize)

        self.assertIn("array( 'START TRANSACTION', 'COMMIT', 'ROLLBACK' )", transaction_helper)
        self.assertIn("$result = $wpdb->query( $statement );", transaction_helper)
        self.assertIn("false !== $result", transaction_helper)
        self.assertIn("$wpdb->last_error", transaction_helper)
        for marker in (
            "'baseline_digest'     => self::digest( $baseline )",
            "'file_journal_digest' => self::digest( $journal )",
            "'storage_digest'      => self::digest( $storage )",
            "'mutation_id'         => $mutation_id",
            "self::seal_recovery_marker( $marker )",
            "self::recovery_marker_is_valid( $marker, $bundle, $storage )",
            "self::recover_rolled_back_boundary( $marker, $bundle )",
            "self::status( true, true )",
            "self::clear_recovery_boundary( $marker )",
        ):
            self.assertIn(marker, recovery_helpers)
        recovery_strict = recovery_helpers.index("$recoverable_status = self::status( true, true );")
        recovery_flush = recovery_helpers.rfind("self::flush_catalog_caches()", 0, recovery_strict)
        self.assertGreaterEqual(recovery_flush, 0)
        self.assertLess(recovery_flush, recovery_strict)
        self.assertIn("update_option( self::OPTION_RECOVERY, $marker, false )", recovery_helpers)
        self.assertIn("get_option( self::OPTION_RECOVERY, false )", recovery_helpers)
        self.assertIn("delete_option( self::OPTION_RECOVERY )", recovery_helpers)
        self.assertIn("wp_cache_flush()", recovery_helpers)
        self.assertIn("self::purge_public_page_caches_with_retry()", recovery_helpers)
        for marker in (
            "\\Upress\\EzCache\\Cache::instance()",
            "$cache->clear_cache()",
            "has_action( 'litespeed_purge_all' )",
            "do_action( 'litespeed_purge_all' )",
            "complete99_live_catalog_page_cache",
            "'attempts'",
        ):
            self.assertIn(marker, page_cache_helper)
        self.assertEqual(
            2,
            recovery_helpers.count("self::readback_receipt_matches_marker("),
        )
        self.assertIn("'recovery_required'", status)
        self.assertIn("$ignore_recovery_marker", status)

        for marker in (
            "'uploads_basedir'",
            "'target_dir'",
            "'allowed_stems'",
            "'baseline_files'",
            "self::recovery_file_is_referenced( $file, $journal )",
            "self::catalog_upload_filename_is_allowed( $name, $journal['allowed_stems'] )",
            "wp_delete_file( $file )",
            "A new Complete99 upload is referenced and was preserved",
            "A preexisting Complete99 upload changed after journaling",
        ):
            self.assertIn(marker, file_recovery)
        self.assertNotIn("cleanup_new_files", self.live_catalog)
        self.assertNotIn("glob(", file_recovery)
        self.assertNotIn("unlink(", file_recovery)

        for table in (
            "$wpdb->posts",
            "$wpdb->postmeta",
            "$wpdb->terms",
            "$wpdb->term_taxonomy",
            "$wpdb->term_relationships",
            "$wpdb->options",
            "wc_product_meta_lookup",
            "woocommerce_tax_rates",
            "woocommerce_shipping_zone_methods",
        ):
            self.assertIn(table, storage)
        self.assertIn("'innodb' !== strtolower", storage)

    def test_recovery_boundary_restore_retries_and_rejects_failed_readback(self) -> None:
        self.assertEqual(
            {
                "retry_succeeded": True,
                "stored_state": "postcommit_verification_failed",
                "persistent_failure_rejected": True,
                "clear_failure_code": "complete99_live_catalog_recovery_restore_failed",
            },
            self.bundle["recovery_restore"],
        )
        self.assertIn(
            "complete99_live_catalog_recovery_restore_failed",
            self.live_catalog,
        )
        restore = self.live_catalog.split(
            "private static function restore_recovery_boundary", 1
        )[1].split("private static function clear_recovery_marker", 1)[0]
        self.assertIn("for ( $attempt = 0; $attempt < 2; $attempt++ )", restore)
        self.assertIn("self::write_recovery_marker( $failed_marker )", restore)
        self.assertIn("self::flush_catalog_caches()", restore)

    def test_store_filters_badges_and_controls_are_accessible_and_safe(self) -> None:
        filters = self.consumer.split("private static function render_store_filters", 1)[1].split(
            "private static function render_store_product_card", 1
        )[0]
        product_card = self.consumer.split("private static function render_store_product_card", 1)[
            1
        ].split("private static function render_related_store_products", 1)[0]
        facet_map = self.live_catalog.split("private static function facet_for_classification", 1)[
            1
        ].split("private static function product_identity", 1)[0]
        filter_codes = set(re.findall(r"^\s*'([a-z][a-z-]+)'\s*=>", filters, re.MULTILINE))
        mapped_facets = set(
            re.findall(r"(?:=>|\[\]\s*=)\s*'([a-z][a-z-]+)'", facet_map)
        )
        self.assertEqual(SAFE_STORE_FILTERS, filter_codes)
        self.assertEqual(SAFE_STORE_FILTERS - {"all"}, mapped_facets)
        for marker in (
            'role="group"',
            'aria-live="polite"',
            'aria-atomic="true"',
            'aria-pressed="<?php echo',
            "data-c99-product-filter-button",
        ):
            self.assertIn(marker, filters)
        self.assertIn('data-c99-product-facets="<?php echo esc_attr( $facet ); ?>"', product_card)
        badge_block = product_card.split('<div class="c99-store-product-badges">', 1)[1].split(
            "</div>", 1
        )[0]
        self.assertIn("$facet_labels[ $display_facet ]", badge_block)
        self.assertIn("$package", badge_block)
        for unsupported_badge in ("vegan", "vegetarian", "gluten-free", "kosher", "healthy", "medical"):
            self.assertNotIn(unsupported_badge, badge_block.lower())

        store_script = self.script.split("[data-c99-product-filter]", 1)[1].split(
            "[data-c99-dish-filter]", 1
        )[0]
        self.assertIn("button.setAttribute('aria-pressed', selected ? 'true' : 'false')", store_script)
        self.assertIn("card.hidden = !visibleCard", store_script)
        self.assertIn("count.textContent", store_script)
        filter_button_css = re.search(r"\.c99-product-filter-buttons button\s*\{([^}]*)\}", self.css)
        self.assertIsNotNone(filter_button_css)
        self.assertRegex(filter_button_css.group(1), r"min-height:\s*44px")
        cart_feedback_css = re.search(r"\.c99-store-cart-feedback a\s*\{([^}]*)\}", self.css)
        self.assertIsNotNone(cart_feedback_css)
        self.assertRegex(cart_feedback_css.group(1), r"min-width:\s*44px")
        self.assertRegex(cart_feedback_css.group(1), r"min-height:\s*44px")
        hidden_card_css = re.search(r"\.c99-store-product-card\[hidden\]\s*\{([^}]*)\}", self.css)
        self.assertIsNotNone(hidden_card_css)
        self.assertRegex(hidden_card_css.group(1), r"display:\s*none\s*!important")

    def test_public_images_render_normally_with_zero_archive_caption_copy(self) -> None:
        public_renderers = "\n".join((self.consumer, self.frontend))
        for forbidden in (
            "c99-archive-note",
            "Complete99 archive photograph",
            "archive photograph remains illustrative",
            "archive image notice",
            "צילום מארכיון",
            "תמונת ארכיון",
        ):
            self.assertNotIn(forbidden.lower(), public_renderers.lower())

        menu_grid = self.consumer.split("private static function render_menu_grid", 1)[1].split(
            "private static function render_menu_page", 1
        )[0]
        dish_page = self.consumer.split("public static function render_live_dish_page", 1)[1].split(
            "private static function render_dish_component_tree", 1
        )[0]
        product_card = self.consumer.split("private static function render_store_product_card", 1)[
            1
        ].split("private static function render_related_store_products", 1)[0]
        self.assertIn("<figure><img", menu_grid)
        self.assertIn("fetchpriority=\"high\"", dish_page)
        self.assertIn("wp_get_attachment_image", product_card)
        self.assertNotIn("<figcaption", menu_grid)
        self.assertNotIn("<figcaption", dish_page)
        self.assertNotIn("<figcaption", product_card)

    def test_catalog_and_cart_readiness_are_separate_from_payment_checkout(self) -> None:
        public_status = self.commerce.split("public static function public_status", 1)[1].split(
            "public static function private_readiness", 1
        )[0]
        catalog_contract = self.commerce.split("public static function is_ready", 1)[1].split(
            "private static function text_script_counts", 1
        )[0]
        allowed_product = self.commerce.split("private static function is_allowed_product_id", 1)[1].split(
            "public static function gate_product_visibility", 1
        )[0]
        live_store = self.consumer.split("private static function render_live_store_page", 1)[1].split(
            "private static function render_store_filters", 1
        )[0]
        product_card = self.consumer.split("private static function render_store_product_card", 1)[
            1
        ].split("private static function render_related_store_products", 1)[0]

        for marker in (
            "'catalog_ready'       => $catalog_ready",
            "'cart_ready'          => $cart_ready",
            "'checkout_ready'      => $readiness['ready']",
            "'catalog_ready' : 'external_ordering'",
        ):
            self.assertIn(marker, public_status)
        self.assertIn("Complete99_Live_Catalog::is_ready()", catalog_contract)
        for marker in (
            "public static function cart_is_ready()",
            "self::catalog_is_ready()",
            "get_option( 'woocommerce_cart_page_id', 0 )",
            "'publish' !== (string) get_post_status( $cart_id )",
            "return '[woocommerce_cart]' === $content",
        ):
            self.assertIn(marker, catalog_contract)
        self.assertIn("! self::catalog_is_ready() && ! self::is_ready()", allowed_product)
        self.assertIn("$cart_url", live_store)
        self.assertIn("$cart_ready     = Complete99_Commerce::cart_is_ready();", live_store)
        self.assertIn("if ( $cart_ready )", live_store)
        self.assertIn("self::render_store_cart_feedback( $lang, $cart_url )", live_store)
        self.assertIn("if ( $checkout_ready )", live_store)
        self.assertIn("'add-to-cart' => absint( $product_id )", product_card)
        self.assertIn("Complete99_Commerce::cart_is_ready()", product_card)
        self.assertIn("Add to cart", product_card)
        self.assertNotIn("$checkout_ready", product_card)

        cart_feedback = self.consumer.split("private static function render_store_cart_feedback", 1)[
            1
        ].split("private static function render_store_filters", 1)[0]
        for marker in (
            'role="status" aria-live="polite" aria-atomic="true"',
            "woocommerce_output_all_notices()",
            "wc_print_notices()",
            "data-c99-cart-count",
            "$cart_url",
        ):
            self.assertIn(marker, cart_feedback)

        purchasability = self.commerce.split("public static function gate_product_purchasability", 1)[
            1
        ].split("public static function disable_woocommerce_auto_update", 1)[0]
        self.assertIn("self::cart_is_ready()", purchasability)
        transaction_shell = self.frontend.split("private static function is_consumer_transaction_request", 1)[
            1
        ].split("private static function render_canonical_link", 1)[0]
        self.assertIn("is_cart() && Complete99_Commerce::cart_is_ready()", transaction_shell)

        route_gate = self.commerce.split("public static function gate_public_woocommerce_routes", 1)[
            1
        ].split("public static function configure_catalog_cart_continuation", 1)[0]
        self.assertIn("$cart_or_preview = self::cart_is_ready() || $ready_or_preview", route_gate)
        self.assertIn("if ( $is_cart && $cart_or_preview )", route_gate)
        continuation = self.commerce.split(
            "public static function configure_catalog_cart_continuation", 1
        )[1].split("public static function exclude_products_from_public_search", 1)[0]
        self.assertIn("if ( ! self::cart_is_ready()", continuation)
        self.assertIn("remove_action( 'woocommerce_proceed_to_checkout'", continuation)
        self.assertIn("render_catalog_cart_continuation", continuation)
        self.assertIn("tel:035231810", continuation)

        readiness = self.commerce.split("private static function readiness", 1)[1].split(
            "private static function approved_products", 1
        )[0]
        self.assertIn("$missing[] = 'payment_gateway';", readiness)

    def test_gateway_id_and_enabled_snapshot_is_identical_before_and_after_apply(self) -> None:
        reader = self.materializer.split("def read_gateway_snapshot", 1)[1].split(
            "def install_and_verify_woocommerce", 1
        )[0]
        for marker in (
            'client.request("GET", WOOCOMMERCE_GATEWAYS_PATH)',
            "gateway_id = gateway.get(\"id\")",
            "enabled = gateway.get(\"enabled\")",
            "type(enabled) is not bool",
            "gateway_id in seen",
            'snapshot.append({"id": gateway_id, "enabled": enabled})',
            'return sorted(snapshot, key=lambda row: row["id"])',
        ):
            self.assertIn(marker, reader)

        installation = self.materializer.split("def install_and_verify_woocommerce", 1)[1].split(
            "def verify_catalog_dry_run", 1
        )[0]
        self.assertIn("gateway_snapshot = read_gateway_snapshot(client, require_all_disabled=True)", installation)
        self.assertIn('"snapshot": gateway_snapshot', installation)
        self.assertIn('"inspected_ids": [row["id"] for row in gateway_snapshot]', installation)

        apply_gate = self.materializer.split('gate = "woocommerce-install-and-runtime"', 1)[1].split(
            'audit["result"] = "verified"', 1
        )[0]
        install_position = apply_gate.index("install_and_verify_woocommerce(")
        catalog_position = apply_gate.index("materialize_catalog(client, deployment_id)")
        readback_position = apply_gate.index('gate = "gateway-post-apply-readback"')
        self.assertLess(install_position, catalog_position)
        self.assertLess(catalog_position, readback_position)
        for marker in (
            'gateway_before = audit["woocommerce"]["gateway_configuration"]["snapshot"]',
            "gateway_after = read_gateway_snapshot(client, require_all_disabled=True)",
            "if gateway_after != gateway_before:",
            "WooCommerce payment gateway enablement changed during catalog materialization",
            '"unchanged": True',
            '"read_only_verification": True',
            '"snapshot": gateway_after',
        ):
            self.assertIn(marker, apply_gate)

    def test_public_store_index_and_sitemap_depend_on_catalog_not_payment(self) -> None:
        sitemap = self.content.split("public static function filter_sitemap_posts_query_args", 1)[
            1
        ].split("public static function robots_index_gate", 1)[0]
        index_gate = self.content.split("public static function is_index_eligible", 1)[1].split(
            "public static function sanitize_language", 1
        )[0]
        for source in (sitemap, index_gate):
            self.assertIn("Complete99_Commerce::catalog_is_ready()", source)
            self.assertNotIn("Complete99_Commerce::is_ready()", source)
        self.assertIn("'_complete99_translation_key'", sitemap)
        self.assertIn("'store'", sitemap)
        self.assertIn("self::PUBLIC_AUDIENCE", sitemap)
        self.assertIn("'store' === $translation_key", index_gate)

    def test_unavailable_product_never_renders_an_add_to_cart_link(self) -> None:
        product_card = self.consumer.split("private static function render_store_product_card", 1)[
            1
        ].split("private static function render_related_store_products", 1)[0]
        self.assertIn(
            "$can_purchase = Complete99_Commerce::cart_is_ready() && $product->is_in_stock() && $product->is_purchasable();",
            product_card,
        )
        purchase = product_card.split('<div class="c99-store-product-purchase">', 1)[1]
        available = purchase.split("<?php if ( $can_purchase ) : ?>", 1)[1].split(
            "<?php else : ?>", 1
        )[0]
        unavailable = purchase.split("<?php else : ?>", 1)[1].split("<?php endif; ?>", 1)[0]
        self.assertIn("$action_url", available)
        self.assertIn("Add to cart", available)
        self.assertNotIn("$action_url", unavailable)
        self.assertNotIn("Add to cart", unavailable)
        self.assertIn('aria-disabled="true"', unavailable)

    def test_public_health_exposes_no_private_catalog_or_evaluation_details(self) -> None:
        health = self.rest.split("public static function health", 1)[1].split(
            "public static function verify_sync_signature", 1
        )[0]
        for forbidden in (
            "evaluation_catalog",
            "catalog_graph",
            "evaluation_catalog_ready",
            "complete99_evaluation_catalog_receipt",
            "product_ids",
            "product_digests",
        ):
            self.assertNotIn(forbidden, health)
        for public_field in (
            "'status'",
            "'component'",
            "'version'",
            "'content_schema'",
            "'read_model'",
            "'digest'",
            "'fresh'",
            "'ttl_seconds'",
        ):
            self.assertIn(public_field, health)

    def test_bundled_twelve_dish_source_is_shared_after_read_model_expiry(self) -> None:
        menu = self.bundle["consumer_menu"]
        self.assertEqual(12, len(menu))
        self.assertEqual(EXPECTED_DISHES, {row["slug"] for row in menu})
        self.assertTrue(all(row["published"] is True for row in menu))

        resolver = self.rest.split("public static function public_indexable_items", 1)[1].split(
            "private static function public_catalog_contract", 1
        )[0]
        bundled = self.rest.split("private static function bundled_public_indexable_items", 1)[
            1
        ].split("public static function public_indexable_item_by_slug", 1)[0]
        self.assertIn("! self::is_public_model_fresh( $model )", resolver)
        self.assertIn("return $bundled;", resolver)
        self.assertIn("self::public_catalog_records_match( $synced_item, $item )", resolver)
        self.assertIn("wordpress_bundle_attested_by_synced_model", resolver)
        self.assertIn("wordpress_bundle_with_synced_controls", resolver)
        self.assertIn("data/consumer-menu.php", bundled)
        self.assertIn("'launch_ready'", bundled)
        self.assertIn("'business_owned'", bundled)
        self.assertIn("'approved_public_use'", bundled)
        self.assertIn("'_complete99_source'", bundled)

        consumer_menu = self.consumer.split("public static function menu_items", 1)[1].split(
            "public static function image_url", 1
        )[0]
        frontend_menu = self.frontend.split("private static function public_model_items", 1)[1].split(
            "public static function live_dish_by_slug", 1
        )[0]
        robots = self.frontend.split("public static function robots", 1)[1].split(
            "public static function head_metadata", 1
        )[0]
        for source in (consumer_menu, frontend_menu, self.live_dish_sitemap):
            self.assertIn("Complete99_REST::public_indexable_items()", source)
        self.assertIn("Complete99_REST::public_indexable_item_by_slug", robots)

    def test_consumer_copy_describes_live_catalog_cart_and_phone_confirmation(self) -> None:
        for stale in (
            "there are currently no products for sale",
            "there are currently no products for purchase",
            "orders are currently completed on an external website",
            "this site has no cart, payment or sale transaction",
            "future direction only",
            "ordering button opens an external service",
        ):
            self.assertNotIn(stale, self.consumer_content.lower())
        for current in (
            "The pantry presents culinary products",
            "Products can be added to the cart",
            "Build your pantry basket on the site and complete confirmation by phone",
            "the Complete99 team confirms stock, the fulfilment method and the final amount by phone",
            "המזווה מציג מוצרי קולינריה",
            "אפשר להוסיף מוצרים לסל",
            "אפשר להכין סל באתר ולסיים את האישור בשיחה",
            "צוות קומפלט 99 מאשר בשיחה את המלאי, אופן הקבלה והסכום הסופי",
        ):
            self.assertIn(current, self.consumer_content)
        for construction_status in (
            "payment provider is connected",
            "payment provider is being connected",
            "ספק הסליקה",
            "30 מוצרי מזווה",
            "shop 30 pantry goods",
            "36 מוצרי קולינריה",
            "36 culinary products",
        ):
            self.assertNotIn(construction_status, self.consumer_content.lower())

    def test_live_materializer_never_mutates_a_payment_gateway(self) -> None:
        self.assertNotIn("payment_gateways(", self.live_catalog)
        self.assertNotIn("WC_Payment_Gateway", self.live_catalog)
        self.assertNotRegex(
            self.live_catalog,
            r"update_option\(\s*['\"]woocommerce_(?:bacs|cod|cheque|paypal|stripe)",
        )
        self.assertNotRegex(self.live_catalog, r"woocommerce_[a-z0-9_-]*gateway")
        ensure_configuration = self.live_catalog.split(
            "private static function ensure_store_configuration", 1
        )[1].split("private static function ensure_woocommerce_pages", 1)[0]
        self.assertNotIn("payment", ensure_configuration.lower())
        self.assertNotIn("gateway", ensure_configuration.lower())

    def test_live_materializer_sets_native_woocommerce_visibility_live(self) -> None:
        ensure_configuration = self.live_catalog.split(
            "private static function ensure_store_configuration", 1
        )[1].split("private static function ensure_woocommerce_pages", 1)[0]
        snapshot = self.live_catalog.split(
            "private static function store_configuration_snapshot", 1
        )[1].split("private static function normalize_store_option_value", 1)[0]
        for option in (
            "woocommerce_coming_soon",
            "woocommerce_store_pages_only",
        ):
            self.assertIn(f"'{option}'", ensure_configuration)
            self.assertIn(f"'{option}'", snapshot)
        self.assertIn("'woocommerce_coming_soon'             => 'no'", ensure_configuration)
        self.assertIn("'woocommerce_store_pages_only'        => 'no'", ensure_configuration)


if __name__ == "__main__":
    unittest.main()
