from __future__ import annotations

import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CATALOG = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-evaluation-catalog.php"
)
DATA = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "data"
    / "catalog-product-seeds.php"
)
GRAPH = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-catalog-graph.php"
)
REST = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-rest.php"
)


class Complete99EvaluationCatalogContracts(unittest.TestCase):
    maxDiff = None

    def run_php(self, body: str) -> dict:
        catalog = CATALOG.as_posix().replace("'", "\\'")
        data = DATA.as_posix().replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
class WP_Error {{
    public $code;
    public $message;
    public function __construct($code, $message) {{
        $this->code = $code;
        $this->message = $message;
    }}
    public function get_error_code() {{ return $this->code; }}
    public function get_error_message() {{ return $this->message; }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
require '{catalog}';
$c99_catalog_data_path = '{data}';
{body}
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=60,
        )
        return json.loads(completed.stdout)

    def run_shared_php(self, body: str, woo: bool = False) -> dict:
        catalog = CATALOG.as_posix().replace("'", "\\'")
        graph = GRAPH.as_posix().replace("'", "\\'")
        data = DATA.as_posix().replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
class WP_Error {{
    public $code;
    public $message;
    public function __construct($code, $message) {{
        $this->code = $code;
        $this->message = $message;
    }}
    public function get_error_code() {{ return $this->code; }}
    public function get_error_message() {{ return $this->message; }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
require '{catalog}';
require '{graph}';
$c99_catalog_data_path = '{data}';
{self.wordpress_storage_stub(woo=woo)}
{body}
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=60,
        )
        return json.loads(completed.stdout)

    def test_php_lints_and_source_never_auto_materializes_or_publishes(self) -> None:
        lint = subprocess.run(
            ["php", "-l", str(CATALOG)],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=30,
        )
        self.assertIn("No syntax errors detected", lint.stdout)

        source = CATALOG.read_text(encoding="utf-8")
        boot = source.split("public static function boot()", 1)[1].split(
            "public static function register_meta()", 1
        )[0]
        self.assertNotIn("materialize(", boot)
        self.assertNotIn("$product->set_status( 'publish' )", source)
        self.assertIn("$product->set_status( 'draft' )", source)
        self.assertIn("$product->set_catalog_visibility( 'hidden' )", source)
        self.assertIn("$product->set_manage_stock( true )", source)
        self.assertIn("$product->set_stock_quantity( 1 )", source)

    def test_public_health_omits_private_evaluation_catalog_state(
        self,
    ) -> None:
        rest = REST.as_posix().replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_VERSION', '1.3.0');
define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'health-contract');
class WP_Error {{
    public $code;
    public $message;
    public $data;
    public function __construct($code, $message, $data = array()) {{
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }}
    public function get_error_code() {{ return $this->code; }}
    public function get_error_data() {{ return $this->data; }}
}}
class Complete99_Settings {{
    const OPTION_SECRET = 'complete99_sync_secret';
}}
class Complete99_Platform {{
    public static $status = array('ready' => false);
    public static function migration_failed() {{ return false; }}
    public static function evaluation_catalog_status() {{ return self::$status; }}
}}
$options = array(
    'complete99_platform_version' => '1.3.0',
    'complete99_last_deployment_id' => 'health-contract',
);
function get_option($name, $default = false) {{
    global $options;
    return array_key_exists($name, $options) ? $options[$name] : $default;
}}
function rest_ensure_response($value) {{ return $value; }}
require '{rest}';
$public = Complete99_REST::health();
echo json_encode($public, JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=30,
        )
        result = json.loads(completed.stdout)
        self.assertEqual("ok", result["status"])
        serialized = json.dumps(result).lower()
        for private_marker in (
            "evaluation_catalog",
            "product_plan_count",
            "woo_materialized",
            "price",
        ):
            self.assertNotIn(private_marker, serialized)

    def test_current_registry_has_32_strict_private_evaluation_seeds(self) -> None:
        result = self.run_php(
            """
$registry = require $c99_catalog_data_path;
$validation = Complete99_Evaluation_Catalog::validate_registry($registry);
$prices = array();
$stocks = array();
$codes = array();
foreach ($registry['products'] as $seed) {
    $codes[] = $seed['product_code'];
    $prices[$seed['product_code']] = number_format(
        (float) $seed['evaluation_price_ils'],
        2,
        '.',
        ''
    );
    $stocks[$seed['product_code']] = $seed['evaluation_stock'];
}
echo json_encode(array(
    'valid' => true === $validation,
    'count' => count($registry['products']),
    'unique_codes' => count(array_unique($codes)),
    'all_stock_one' => count(array_unique($stocks)) === 1
        && 1 === (int) reset($stocks),
    'prices' => $prices,
    'digest' => Complete99_Evaluation_Catalog::registry_digest($registry),
    'global_public_sale' => $registry['stock_policy']['public_sale'],
    'global_public_stock' => $registry['stock_policy']['public_stock_claim'],
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(result["valid"])
        self.assertEqual(32, result["count"])
        self.assertEqual(32, result["unique_codes"])
        self.assertTrue(result["all_stock_one"])
        self.assertEqual(32, len(result["prices"]))
        self.assertRegex(result["digest"], r"^[a-f0-9]{64}$")
        self.assertFalse(result["global_public_sale"])
        self.assertFalse(result["global_public_stock"])

    def test_registry_rejects_unsafe_prices_stock_sources_dates_and_gates(
        self,
    ) -> None:
        result = self.run_php(
            """
$registry = require $c99_catalog_data_path;
$mutations = array();

$bad = $registry;
$bad['products'][0]['market_observation']['source_url'] = 'http://example.test/item';
$mutations['http_source'] = $bad;

$bad = $registry;
$bad['products'][0]['market_observation']['checked_at'] = '2026-02-30';
$mutations['bad_date'] = $bad;

$bad = $registry;
$bad['products'][0]['evaluation_price_ils'] = -1;
$mutations['bad_price'] = $bad;

$bad = $registry;
$bad['products'][0]['evaluation_stock'] = 2;
$mutations['bad_stock'] = $bad;

$bad = $registry;
$bad['products'][0]['public_sale_eligible'] = true;
$mutations['public_sale'] = $bad;

$bad = $registry;
$gate = array_key_first($bad['products'][0]['acceptance_gates']);
$bad['products'][0]['acceptance_gates'][$gate] = true;
$mutations['completed_gate'] = $bad;

$bad = $registry;
$bad['products'][1]['product_code'] = $bad['products'][0]['product_code'];
$mutations['duplicate_product_code'] = $bad;

$bad = $registry;
$bad['products'][1]['ingredient_code'] = $bad['products'][0]['ingredient_code'];
$bad['products'][1]['relations']['verified_ingredient_codes'] = array(
    $bad['products'][0]['ingredient_code']
);
$mutations['duplicate_ingredient_code'] = $bad;

$bad = $registry;
$bad['products'][30]['market_observation']['fx_conversion']['rate'] = 'not-a-rate';
$mutations['bad_fx_rate'] = $bad;

$bad = $registry;
$bad['products'][30]['market_observation']['fx_conversion']['converted_amount_ils'] = '252.80';
$mutations['bad_fx_result'] = $bad;

$bad = $registry;
$bad['products'][30]['market_observation']['fx_conversion']['rate_date'] = '2026-08-07';
$mutations['future_fx_rate_date'] = $bad;

$bad = $registry;
unset($bad['products'][30]['market_observation']['fx_conversion']['rate_date']);
$mutations['missing_fx_rate_date'] = $bad;

$bad = $registry;
$bad['products'][30]['market_observation']['fx_conversion']['source_url'] = $bad['market_transparency_sources']['the_wasabi_company'];
$mutations['non_boi_fx_source'] = $bad;

$out = array();
foreach ($mutations as $name => $candidate) {
    $validation = Complete99_Evaluation_Catalog::validate_registry($candidate);
    $out[$name] = is_wp_error($validation)
        && 'complete99_evaluation_registry_invalid'
            === $validation->get_error_code();
}
echo json_encode($out, JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual(
            {
                "http_source": True,
                "bad_date": True,
                "bad_price": True,
                "bad_stock": True,
                "public_sale": True,
                "completed_gate": True,
                "duplicate_product_code": True,
                "duplicate_ingredient_code": True,
                "bad_fx_rate": True,
                "bad_fx_result": True,
                "future_fx_rate_date": True,
                "missing_fx_rate_date": True,
                "non_boi_fx_source": True,
            },
            result,
        )

    def test_boot_registers_private_meta_without_mutation_hooks(self) -> None:
        result = self.run_php(
            """
$hooks = array();
$registered = array();
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['hooks'][] = array(
        'hook' => $hook,
        'method' => is_array($callback) ? $callback[1] : '',
        'priority' => $priority,
        'accepted_args' => $accepted_args,
    );
}
function register_post_meta($post_type, $key, $args) {
    $GLOBALS['registered'][$post_type][$key] = array(
        'type' => $args['type'],
        'single' => $args['single'],
        'show_in_rest' => $args['show_in_rest'],
        'sanitizer' => is_array($args['sanitize_callback'])
            ? $args['sanitize_callback'][1]
            : $args['sanitize_callback'],
    );
}
function current_user_can($capability) { return true; }

Complete99_Evaluation_Catalog::boot();
Complete99_Evaluation_Catalog::boot();
Complete99_Evaluation_Catalog::register_meta();

$all_private = true;
foreach ($registered as $post_type => $fields) {
    foreach ($fields as $field) {
        if (false !== $field['show_in_rest']) {
            $all_private = false;
        }
    }
}
echo json_encode(array(
    'hooks' => $hooks,
    'all_private' => $all_private,
    'post_types' => array_keys($registered),
    'required' => array(
        isset($registered['c99_ingredient']['_complete99_evaluation_product_code']),
        isset($registered['c99_product_plan']['_complete99_evaluation_market_source_url']),
        isset($registered['product']['_complete99_evaluation_market_checked_at']),
        $registered['product']['_complete99_evaluation_price_ils']['type'],
        $registered['product']['_complete99_evaluation_stock']['type'],
        isset($registered['product']['_complete99_evaluation_price_scope']),
        isset($registered['product']['_complete99_evaluation_stock_scope']),
        isset($registered['product']['_complete99_evaluation_classification']),
        !isset($registered['c99_ingredient']['_complete99_catalog_product_code']),
        isset($registered['c99_product_plan']['_complete99_catalog_product_code']),
        isset($registered['product']['_complete99_catalog_product_code']),
    ),
    'sanitizers' => array(
        Complete99_Evaluation_Catalog::sanitize_product_code('product-tahini-500g'),
        Complete99_Evaluation_Catalog::sanitize_product_code('Tahini'),
        Complete99_Evaluation_Catalog::sanitize_ingredient_code('ingredient-tahini'),
        Complete99_Evaluation_Catalog::sanitize_ingredient_code('equipment-wasabi-grater'),
        Complete99_Evaluation_Catalog::sanitize_https_url('http://example.test'),
        Complete99_Evaluation_Catalog::sanitize_date('2026-02-30'),
        Complete99_Evaluation_Catalog::sanitize_price('14.249'),
        Complete99_Evaluation_Catalog::sanitize_stock(2),
    ),
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual(
            [
                {
                    "hook": "init",
                    "method": "register_meta",
                    "priority": 9,
                    "accepted_args": 1,
                }
            ],
            result["hooks"],
        )
        self.assertTrue(result["all_private"])
        self.assertEqual(
            ["c99_ingredient", "c99_product_plan", "product"],
            result["post_types"],
        )
        self.assertEqual(
            [
                True,
                True,
                True,
                "number",
                "integer",
                True,
                True,
                True,
                True,
                True,
                True,
            ],
            result["required"],
        )
        self.assertEqual(
            [
                "product-tahini-500g",
                "",
                "ingredient-tahini",
                "equipment-wasabi-grater",
                "",
                "",
                "",
                0,
            ],
            result["sanitizers"],
        )

    def test_no_woocommerce_materialization_is_private_exact_and_idempotent(
        self,
    ) -> None:
        result = self.run_php(
            self.wordpress_storage_stub()
            + """
$registry = require $c99_catalog_data_path;
$expected = array();
foreach ($registry['products'] as $seed) {
    $expected[$seed['product_code']] = array(
        'price' => number_format((float) $seed['evaluation_price_ils'], 2, '.', ''),
        'stock' => $seed['evaluation_stock'],
        'classification' => $seed['classification'],
        'source' => $seed['market_observation']['source_url'],
        'checked_at' => $seed['market_observation']['checked_at'],
    );
}
$first = Complete99_Evaluation_Catalog::materialize();
$second = Complete99_Evaluation_Catalog::materialize();
if (is_wp_error($first) || is_wp_error($second)) {
    $error = is_wp_error($first) ? $first : $second;
    echo json_encode(array(
        'error' => $error->get_error_code() . ':' . $error->get_error_message(),
    ), JSON_THROW_ON_ERROR);
    return;
}
$private = true;
$exact = true;
$held = true;
$canonical = true;
foreach ($posts as $post_id => $post) {
    $private = $private && 'private' === $post->post_status;
    $code = $meta[$post_id]['_complete99_evaluation_product_code'];
    $exact = $exact
        && isset($expected[$code])
        && $expected[$code]['price']
            === (string) $meta[$post_id]['_complete99_evaluation_price_ils']
        && (int) $expected[$code]['stock']
            === (int) $meta[$post_id]['_complete99_evaluation_stock']
        && $expected[$code]['classification']
            === $meta[$post_id]['_complete99_evaluation_classification']
        && $expected[$code]['source']
            === $meta[$post_id]['_complete99_evaluation_market_source_url']
        && $expected[$code]['checked_at']
            === $meta[$post_id]['_complete99_evaluation_market_checked_at'];
    $held = $held
        && 'no' === $meta[$post_id]['_complete99_evaluation_public_sale_eligible']
        && 'held_until_acceptance'
            === $meta[$post_id]['_complete99_evaluation_sale_state']
        && 'no' === $meta[$post_id]['_complete99_store_approved']
        && 'evaluation_only' === $meta[$post_id]['_complete99_stock_authority']
        && 'no' === $meta[$post_id]['_complete99_product_label_reviewed']
        && 'no' === $meta[$post_id]['_complete99_product_rights_reviewed']
        && 'no' === $meta[$post_id]['_complete99_product_tax_reviewed']
        && 'no' === $meta[$post_id]['_complete99_media_public_safe'];
    if ('c99_product_plan' === $post->post_type) {
        $canonical = $canonical
            && $code === $meta[$post_id]['_complete99_catalog_product_code']
            && $code === $meta[$post_id]['_complete99_product_sku'];
    } else {
        $canonical = $canonical
            && !isset($meta[$post_id]['_complete99_catalog_product_code']);
    }
}
$receipt = get_option('complete99_evaluation_catalog_receipt', array());
echo json_encode(array(
    'error' => '',
    'seed_count' => $first['seed_count'],
    'post_count' => count($posts),
    'ingredient_count' => count($first['ingredient_posts']),
    'plan_count' => count($first['product_plan_posts']),
    'woo_count' => count($first['woo_products']),
    'woo_materialized' => $first['woocommerce_materialized'],
    'same_ids' => $first['ingredient_posts'] === $second['ingredient_posts']
        && $first['product_plan_posts'] === $second['product_plan_posts'],
    'private' => $private,
    'exact' => $exact,
    'held' => $held,
    'canonical' => $canonical,
    'receipt_schema' => $receipt['schema'],
    'receipt_status' => $receipt['status'],
    'receipt_counts' => array(
        $receipt['seed_count'],
        $receipt['ingredient_count'],
        $receipt['product_plan_count'],
        $receipt['woo_product_count'],
    ),
    'receipt_digest' => $receipt['registry_digest'],
    'receipt_size' => strlen(json_encode($receipt)),
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual("", result["error"])
        self.assertEqual(32, result["seed_count"])
        self.assertEqual(64, result["post_count"])
        self.assertEqual(32, result["ingredient_count"])
        self.assertEqual(32, result["plan_count"])
        self.assertEqual(0, result["woo_count"])
        self.assertFalse(result["woo_materialized"])
        self.assertTrue(result["same_ids"])
        self.assertTrue(result["private"])
        self.assertTrue(result["exact"])
        self.assertTrue(result["held"])
        self.assertTrue(result["canonical"])
        self.assertEqual(
            "complete99-evaluation-catalog-receipt/v1",
            result["receipt_schema"],
        )
        self.assertEqual("success", result["receipt_status"])
        self.assertEqual([32, 32, 32, 0], result["receipt_counts"])
        self.assertRegex(result["receipt_digest"], r"^[a-f0-9]{64}$")
        self.assertLessEqual(result["receipt_size"], 1024)

    def test_private_only_mode_skips_woocommerce_even_when_available(self) -> None:
        result = self.run_php(
            self.wordpress_storage_stub(woo=True)
            + """
$invalid = Complete99_Evaluation_Catalog::materialize('unsafe-mode');
$invalid_left_clean = is_wp_error($invalid)
    && 'complete99_evaluation_materialization_mode_invalid'
        === $invalid->get_error_code()
    && 0 === count($posts)
    && 0 === count($options);

$first = Complete99_Evaluation_Catalog::materialize(
    Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY
);
$second = Complete99_Evaluation_Catalog::materialize(
    Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY
);
if (is_wp_error($first) || is_wp_error($second)) {
    $error = is_wp_error($first) ? $first : $second;
    echo json_encode(array(
        'error' => $error->get_error_code() . ':' . $error->get_error_message(),
    ), JSON_THROW_ON_ERROR);
    return;
}
$canonical = true;
$no_products = true;
foreach ($posts as $post_id => $post) {
    $no_products = $no_products && 'product' !== $post->post_type;
    if ('c99_product_plan' === $post->post_type) {
        $canonical = $canonical
            && $meta[$post_id]['_complete99_evaluation_product_code']
                === $meta[$post_id]['_complete99_catalog_product_code'];
    }
}
$receipt = get_option('complete99_evaluation_catalog_receipt', array());
echo json_encode(array(
    'error' => '',
    'invalid_left_clean' => $invalid_left_clean,
    'mode' => $first['mode'],
    'woo_available' => $first['woocommerce_available'],
    'woo_materialized' => $first['woocommerce_materialized'],
    'woo_count' => count($first['woo_products']),
    'product_objects' => count($products),
    'post_count' => count($posts),
    'no_products' => $no_products,
    'canonical' => $canonical,
    'same_ids' => $first['ingredient_posts'] === $second['ingredient_posts']
        && $first['product_plan_posts'] === $second['product_plan_posts'],
    'receipt_mode' => $receipt['mode'],
    'receipt_woo_available' => $receipt['woo_available'],
    'receipt_woo_materialized' => $receipt['woo_materialized'],
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual("", result["error"])
        self.assertTrue(result["invalid_left_clean"])
        self.assertEqual("private_only", result["mode"])
        self.assertTrue(result["woo_available"])
        self.assertFalse(result["woo_materialized"])
        self.assertEqual(0, result["woo_count"])
        self.assertEqual(0, result["product_objects"])
        self.assertEqual(64, result["post_count"])
        self.assertTrue(result["no_products"])
        self.assertTrue(result["canonical"])
        self.assertTrue(result["same_ids"])
        self.assertEqual("private_only", result["receipt_mode"])
        self.assertTrue(result["receipt_woo_available"])
        self.assertFalse(result["receipt_woo_materialized"])

    def test_persisted_status_requires_exact_receipt_records_and_hides_prices(
        self,
    ) -> None:
        result = self.run_php(
            self.wordpress_storage_stub()
            + """
$materialized = Complete99_Evaluation_Catalog::materialize(
    Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY
);
if (is_wp_error($materialized)) {
    echo json_encode(array(
        'error' => $materialized->get_error_code(),
    ), JSON_THROW_ON_ERROR);
    return;
}
$ready = Complete99_Evaluation_Catalog::persisted_status();
$plan_id = (int) reset($materialized['product_plan_posts']);
$original_price = $meta[$plan_id]['_complete99_product_price'];
$meta[$plan_id]['_complete99_product_price'] = '999.99';
$record_corrupt = Complete99_Evaluation_Catalog::persisted_status();
$meta[$plan_id]['_complete99_product_price'] = $original_price;
$receipt = $options['complete99_evaluation_catalog_receipt'];
$options['complete99_evaluation_catalog_receipt']['bindings_digest']
    = str_repeat('0', 64);
$receipt_corrupt = Complete99_Evaluation_Catalog::persisted_status();
$options['complete99_evaluation_catalog_receipt'] = $receipt;
$options['complete99_evaluation_catalog_receipt']['seed_count'] = '28';
$receipt_type_corrupt = Complete99_Evaluation_Catalog::persisted_status();
$options['complete99_evaluation_catalog_receipt'] = $receipt;
$options['complete99_evaluation_catalog_receipt']['materialized_at']
    = 'not-a-timestamp';
$receipt_time_corrupt = Complete99_Evaluation_Catalog::persisted_status();
$options['complete99_evaluation_catalog_receipt'] = $receipt;
unset($options['complete99_evaluation_catalog_receipt']);
$receipt_missing = Complete99_Evaluation_Catalog::persisted_status();
echo json_encode(array(
    'error' => '',
    'ready' => $ready,
    'ready_json' => json_encode($ready),
    'record_corrupt' => $record_corrupt,
    'receipt_corrupt' => $receipt_corrupt,
    'receipt_type_corrupt' => $receipt_type_corrupt,
    'receipt_time_corrupt' => $receipt_time_corrupt,
    'receipt_missing' => $receipt_missing,
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual("", result["error"])
        self.assertTrue(result["ready"]["ready"])
        self.assertTrue(result["ready"]["receipt"]["valid"])
        self.assertEqual(
            {"ingredient_count": 32, "product_plan_count": 32},
            result["ready"]["materialized"],
        )
        self.assertNotIn("price", result["ready_json"].lower())
        self.assertFalse(result["record_corrupt"]["ready"])
        self.assertFalse(result["receipt_corrupt"]["ready"])
        self.assertEqual(
            "receipt_binding_mismatch", result["receipt_corrupt"]["reason"]
        )
        self.assertFalse(result["receipt_type_corrupt"]["ready"])
        self.assertEqual(
            "receipt_corrupt", result["receipt_type_corrupt"]["reason"]
        )
        self.assertFalse(result["receipt_time_corrupt"]["ready"])
        self.assertEqual(
            "receipt_corrupt", result["receipt_time_corrupt"]["reason"]
        )
        self.assertFalse(result["receipt_missing"]["ready"])
        self.assertEqual("receipt_missing", result["receipt_missing"]["reason"])

    def test_woocommerce_materialization_is_exact_hidden_draft_and_held(
        self,
    ) -> None:
        result = self.run_php(
            self.wordpress_storage_stub(woo=True)
            + """
$registry = require $c99_catalog_data_path;
$expected = array();
foreach ($registry['products'] as $seed) {
    $expected[$seed['product_code']] = number_format(
        (float) $seed['evaluation_price_ils'],
        2,
        '.',
        ''
    );
}
$first = Complete99_Evaluation_Catalog::materialize();
$second = Complete99_Evaluation_Catalog::materialize();
if (is_wp_error($first) || is_wp_error($second)) {
    $error = is_wp_error($first) ? $first : $second;
    echo json_encode(array(
        'error' => $error->get_error_code() . ':' . $error->get_error_message(),
    ), JSON_THROW_ON_ERROR);
    return;
}
$exact = true;
$held = true;
foreach ($first['woo_products'] as $code => $product_id) {
    $product = wc_get_product($product_id);
    $exact = $exact
        && $code === $product->get_sku()
        && $expected[$code] === $product->get_regular_price()
        && $expected[$code] === $product->get_price()
        && '' === $product->get_sale_price()
        && 1 === (int) $product->get_stock_quantity();
    $held = $held
        && 'draft' === get_post_status($product_id)
        && 'hidden' === $product->get_catalog_visibility()
        && $product->managing_stock()
        && 'instock' === $product->get_stock_status()
        && !$product->backorders_allowed()
        && !$product->is_virtual()
        && !$product->is_downloadable()
        && !$product->is_purchasable()
        && 'no' === $meta[$product_id]['_complete99_store_approved']
        && 'evaluation_only'
            === $meta[$product_id]['_complete99_stock_authority']
        && 'no' === $meta[$product_id]['_complete99_product_label_reviewed']
        && 'no' === $meta[$product_id]['_complete99_product_rights_reviewed']
        && 'no' === $meta[$product_id]['_complete99_product_tax_reviewed']
        && 'no' === $meta[$product_id]['_complete99_media_public_safe']
        && 'no'
            === $meta[$product_id]['_complete99_evaluation_public_sale_eligible']
        && $code
            === $meta[$product_id]['_complete99_catalog_product_code'];
}
$non_product_private = true;
foreach ($posts as $post) {
    if ('product' !== $post->post_type) {
        $non_product_private = $non_product_private
            && 'private' === $post->post_status;
    }
}
$receipt = get_option('complete99_evaluation_catalog_receipt', array());
echo json_encode(array(
    'error' => '',
    'post_count' => count($posts),
    'product_count' => count($first['woo_products']),
    'same_product_ids' => $first['woo_products'] === $second['woo_products'],
    'exact' => $exact,
    'held' => $held,
    'non_product_private' => $non_product_private,
    'receipt_counts' => array(
        $receipt['seed_count'],
        $receipt['ingredient_count'],
        $receipt['product_plan_count'],
        $receipt['woo_product_count'],
    ),
    'receipt_woo' => $receipt['woo_materialized'],
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual("", result["error"])
        self.assertEqual(96, result["post_count"])
        self.assertEqual(32, result["product_count"])
        self.assertTrue(result["same_product_ids"])
        self.assertTrue(result["exact"])
        self.assertTrue(result["held"])
        self.assertTrue(result["non_product_private"])
        self.assertEqual([32, 32, 32, 32], result["receipt_counts"])
        self.assertTrue(result["receipt_woo"])

    def test_duplicate_and_nonmanaged_bindings_fail_before_catalog_writes(
        self,
    ) -> None:
        duplicate = self.run_php(
            self.wordpress_storage_stub()
            + """
$registry = require $c99_catalog_data_path;
$seed = $registry['products'][0];
foreach (array(1, 2) as $index) {
    $id = wp_insert_post(array(
        'post_type' => 'c99_product_plan',
        'post_status' => 'private',
        'post_title' => 'duplicate',
        'post_name' => 'duplicate-' . $index,
        'post_excerpt' => '',
        'post_content' => '',
    ), true);
    update_post_meta($id, '_complete99_evaluation_catalog_managed', '1');
    update_post_meta(
        $id,
        '_complete99_evaluation_product_code',
        $seed['product_code']
    );
    update_post_meta(
        $id,
        '_complete99_evaluation_ingredient_code',
        $seed['ingredient_code']
    );
}
$before = count($posts);
$result = Complete99_Evaluation_Catalog::materialize();
echo json_encode(array(
    'is_error' => is_wp_error($result),
    'code' => is_wp_error($result) ? $result->get_error_code() : '',
    'before' => $before,
    'after' => count($posts),
    'receipt_exists' => isset($options['complete99_evaluation_catalog_receipt']),
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(duplicate["is_error"])
        self.assertEqual(
            "complete99_evaluation_duplicate_binding", duplicate["code"]
        )
        self.assertEqual(2, duplicate["before"])
        self.assertEqual(2, duplicate["after"])
        self.assertFalse(duplicate["receipt_exists"])

        nonmanaged = self.run_php(
            self.wordpress_storage_stub()
            + """
$registry = require $c99_catalog_data_path;
$seed = $registry['products'][0];
$id = wp_insert_post(array(
    'post_type' => 'c99_ingredient',
    'post_status' => 'private',
    'post_title' => 'operator record',
    'post_name' => $seed['ingredient_code'],
    'post_excerpt' => '',
    'post_content' => '',
), true);
update_post_meta(
    $id,
    '_complete99_catalog_ingredient_code',
    $seed['ingredient_code']
);
update_post_meta(
    $id,
    '_complete99_catalog_entity_id',
    $seed['ingredient_code']
);
$snapshot = serialize(array($posts, $meta));
$result = Complete99_Evaluation_Catalog::materialize();
echo json_encode(array(
    'is_error' => is_wp_error($result),
    'code' => is_wp_error($result) ? $result->get_error_code() : '',
    'preserved' => $snapshot === serialize(array($posts, $meta)),
    'post_count' => count($posts),
    'receipt_exists' => isset($options['complete99_evaluation_catalog_receipt']),
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(nonmanaged["is_error"])
        self.assertEqual(
            "complete99_evaluation_nonmanaged_binding", nonmanaged["code"]
        )
        self.assertTrue(nonmanaged["preserved"])
        self.assertEqual(1, nonmanaged["post_count"])
        self.assertFalse(nonmanaged["receipt_exists"])

        canonical_collision = self.run_php(
            self.wordpress_storage_stub()
            + """
$registry = require $c99_catalog_data_path;
$seed = $registry['products'][0];
$id = wp_insert_post(array(
    'post_type' => 'c99_product_plan',
    'post_status' => 'private',
    'post_title' => 'unmanaged canonical collision',
    'post_name' => 'unmanaged-collision',
    'post_excerpt' => '',
    'post_content' => '',
), true);
update_post_meta(
    $id,
    '_complete99_catalog_product_code',
    $seed['product_code']
);
$snapshot = serialize(array($posts, $meta));
$result = Complete99_Evaluation_Catalog::materialize(
    Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY
);
echo json_encode(array(
    'is_error' => is_wp_error($result),
    'code' => is_wp_error($result) ? $result->get_error_code() : '',
    'preserved' => $snapshot === serialize(array($posts, $meta)),
    'post_count' => count($posts),
    'receipt_exists' => isset($options['complete99_evaluation_catalog_receipt']),
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(canonical_collision["is_error"])
        self.assertEqual(
            "complete99_evaluation_nonmanaged_binding",
            canonical_collision["code"],
        )
        self.assertTrue(canonical_collision["preserved"])
        self.assertEqual(1, canonical_collision["post_count"])
        self.assertFalse(canonical_collision["receipt_exists"])

    def test_shared_catalog_evaluation_then_graph_has_one_record_per_overlap(
        self,
    ) -> None:
        result = self.run_shared_php(
            "$sanitize_registered_index_meta = true;\n"
            + self.shared_order_assertion_php(evaluation_first=True)
        )
        self.assert_shared_order_result(result, "evaluation_then_graph")

    def test_shared_catalog_graph_then_evaluation_has_one_record_per_overlap(
        self,
    ) -> None:
        result = self.run_shared_php(
            "$sanitize_registered_index_meta = true;\n"
            + self.shared_order_assertion_php(evaluation_first=False)
        )
        self.assert_shared_order_result(result, "graph_then_evaluation")

    def test_graph_rejects_arbitrary_evaluation_binding_before_writes(self) -> None:
        result = self.run_shared_php(
            """
$registry = require $c99_catalog_data_path;
$seed = $registry['products'][0];
$id = wp_insert_post(array(
    'post_type' => 'c99_ingredient',
    'post_status' => 'private',
    'post_title' => 'operator-owned collision',
    'post_name' => 'operator-owned-collision',
    'post_excerpt' => '',
    'post_content' => '',
), true);
update_post_meta(
    $id,
    '_complete99_evaluation_ingredient_code',
    $seed['ingredient_code']
);
$snapshot = serialize(array($posts, $meta));
$result = Complete99_Catalog_Graph::materialize_drafts();
echo json_encode(array(
    'is_error' => is_wp_error($result),
    'code' => is_wp_error($result) ? $result->get_error_code() : '',
    'preserved' => $snapshot === serialize(array($posts, $meta)),
    'post_count' => count($posts),
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(result["is_error"])
        self.assertEqual("complete99_catalog_nonmanaged_binding", result["code"])
        self.assertTrue(result["preserved"])
        self.assertEqual(1, result["post_count"])

    def test_shared_woocommerce_records_are_adopted_and_preserved_both_orders(
        self,
    ) -> None:
        for evaluation_first, order in (
            (True, "evaluation_then_graph"),
            (False, "graph_then_evaluation"),
        ):
            with self.subTest(order=order):
                result = self.run_shared_php(
                    self.shared_woo_order_assertion_php(evaluation_first),
                    woo=True,
                )
                self.assertEqual("", result["error"])
                self.assertEqual(order, result["order"])
                self.assertEqual(96, result["post_count"])
                self.assertEqual(32, result["product_records"])
                self.assertEqual(0, result["published_records"])
                self.assertEqual(32, result["evaluation_products"])
                self.assertEqual(7, result["shared_products"])
                self.assertTrue(result["shared_bindings_exact"])
                self.assertTrue(result["held_fields_intact"])

    @staticmethod
    def shared_order_assertion_php(evaluation_first: bool) -> str:
        if evaluation_first:
            materialize = """
$evaluation = Complete99_Evaluation_Catalog::materialize(
    Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY
);
$graph = Complete99_Catalog_Graph::materialize_drafts();
$order = 'evaluation_then_graph';
"""
        else:
            materialize = """
$graph = Complete99_Catalog_Graph::materialize_drafts();
$evaluation = Complete99_Evaluation_Catalog::materialize(
    Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY
);
$order = 'graph_then_evaluation';
"""
        return (
            materialize
            + """
if (is_wp_error($evaluation) || is_wp_error($graph)) {
    $error = is_wp_error($evaluation) ? $evaluation : $graph;
    echo json_encode(array(
        'error' => $error->get_error_code() . ':' . $error->get_error_message(),
        'order' => $order,
    ), JSON_THROW_ON_ERROR);
    return;
}

$registry = require $c99_catalog_data_path;
$expected_by_ingredient = array();
$expected_by_product = array();
foreach ($registry['products'] as $seed) {
    $expected_by_ingredient[$seed['ingredient_code']] = $seed;
    $expected_by_product[$seed['product_code']] = $seed;
}

$ingredient_records = 0;
$plan_records = 0;
$product_records = 0;
$published_records = 0;
$evaluation_ingredient_codes = array();
$evaluation_plan_codes = array();
$held_fields_intact = true;
$shared_ingredient_count = 0;
$shared_plan_count = 0;

foreach ($posts as $post_id => $post) {
    if ('c99_ingredient' === $post->post_type) {
        ++$ingredient_records;
    } elseif ('c99_product_plan' === $post->post_type) {
        ++$plan_records;
    } elseif ('product' === $post->post_type) {
        ++$product_records;
    }
    if ('publish' === $post->post_status) {
        ++$published_records;
    }
    if ('1' !== ($meta[$post_id]['_complete99_evaluation_catalog_managed'] ?? '')) {
        continue;
    }

    $product_code = $meta[$post_id]['_complete99_evaluation_product_code'];
    $ingredient_code = $meta[$post_id]['_complete99_evaluation_ingredient_code'];
    $seed = $expected_by_product[$product_code] ?? null;
    $price = $seed
        ? number_format((float) $seed['evaluation_price_ils'], 2, '.', '')
        : '';
    $held_fields_intact = $held_fields_intact
        && null !== $seed
        && $seed['ingredient_code'] === $ingredient_code
        && $price === (string) $meta[$post_id]['_complete99_evaluation_price_ils']
        && 1 === (int) $meta[$post_id]['_complete99_evaluation_stock']
        && 'private_benchmark_only'
            === $meta[$post_id]['_complete99_evaluation_price_scope']
        && 'private_evaluation_only'
            === $meta[$post_id]['_complete99_evaluation_stock_scope']
        && 'held_until_acceptance'
            === $meta[$post_id]['_complete99_evaluation_sale_state']
        && 'no'
            === $meta[$post_id]['_complete99_evaluation_public_sale_eligible']
        && 'no' === $meta[$post_id]['_complete99_store_approved']
        && 'evaluation_only' === $meta[$post_id]['_complete99_stock_authority']
        && 'no' === $meta[$post_id]['_complete99_product_label_reviewed']
        && 'no' === $meta[$post_id]['_complete99_product_rights_reviewed']
        && 'no' === $meta[$post_id]['_complete99_product_tax_reviewed']
        && 'no' === $meta[$post_id]['_complete99_media_public_safe']
        && 'private' === $post->post_status;

    if ('c99_ingredient' === $post->post_type) {
        $evaluation_ingredient_codes[] = $product_code;
        if ('1' === ($meta[$post_id]['_complete99_catalog_managed'] ?? '')) {
            ++$shared_ingredient_count;
        }
    }
    if ('c99_product_plan' === $post->post_type) {
        $evaluation_plan_codes[] = $product_code;
        $held_fields_intact = $held_fields_intact
            && $product_code
                === $meta[$post_id]['_complete99_catalog_product_code']
            && $product_code === $meta[$post_id]['_complete99_product_sku']
            && $price === (string) $meta[$post_id]['_complete99_product_price']
            && 'ILS' === $meta[$post_id]['_complete99_product_currency']
            && 'evaluation_only'
                === $meta[$post_id]['_complete99_product_stock_source']
            && 'private_evaluation_held'
                === $meta[$post_id]['_complete99_product_status']
            && 'pending' === $meta[$post_id]['_complete99_product_rights'];
        if ('1' === ($meta[$post_id]['_complete99_catalog_managed'] ?? '')) {
            ++$shared_plan_count;
        }
    }
}

$shared_bindings_exact = true;
$shared_provenance_exact = true;
foreach ($graph['ingredient_posts'] as $ingredient_code => $ingredient_id) {
    $seed = $expected_by_ingredient[$ingredient_code] ?? null;
    $shared_bindings_exact = $shared_bindings_exact
        && null !== $seed
        && $ingredient_id
            === $evaluation['ingredient_posts'][$seed['product_code']]
        && $graph['product_plan_posts'][$ingredient_code]
            === $evaluation['product_plan_posts'][$seed['product_code']];
    $plan_id = $graph['product_plan_posts'][$ingredient_code];
    $shared_provenance_exact = $shared_provenance_exact
        && $ingredient_code
            === $meta[$ingredient_id]['_complete99_catalog_ingredient_code']
        && $ingredient_code
            === $meta[$ingredient_id]['_complete99_catalog_entity_id']
        && $ingredient_code
            === $meta[$plan_id]['_complete99_catalog_ingredient_code']
        && $ingredient_code === $meta[$plan_id]['_complete99_catalog_entity_id']
        && !empty($meta[$ingredient_id]['_complete99_catalog_source_dish_ids'])
        && !empty($meta[$plan_id]['_complete99_catalog_source_dish_ids']);
}

echo json_encode(array(
    'error' => '',
    'order' => $order,
    'post_count' => count($posts),
    'ingredient_records' => $ingredient_records,
    'plan_records' => $plan_records,
    'product_records' => $product_records,
    'published_records' => $published_records,
    'evaluation_ingredient_bindings' => count(
        array_unique($evaluation_ingredient_codes)
    ),
    'evaluation_plan_bindings' => count(array_unique($evaluation_plan_codes)),
    'graph_overlap_count' => count($graph['ingredient_posts']),
    'shared_ingredient_count' => $shared_ingredient_count,
    'shared_plan_count' => $shared_plan_count,
    'shared_bindings_exact' => $shared_bindings_exact,
    'shared_provenance_exact' => $shared_provenance_exact,
    'held_fields_intact' => $held_fields_intact,
    'woo_products' => count($evaluation['woo_products'])
        + count($graph['woo_products']),
), JSON_THROW_ON_ERROR);
"""
        )

    def assert_shared_order_result(self, result: dict, order: str) -> None:
        self.assertEqual("", result["error"])
        self.assertEqual(order, result["order"])
        self.assertEqual(64, result["post_count"])
        self.assertEqual(32, result["ingredient_records"])
        self.assertEqual(32, result["plan_records"])
        self.assertEqual(0, result["product_records"])
        self.assertEqual(0, result["published_records"])
        self.assertEqual(32, result["evaluation_ingredient_bindings"])
        self.assertEqual(32, result["evaluation_plan_bindings"])
        self.assertEqual(7, result["graph_overlap_count"])
        self.assertEqual(7, result["shared_ingredient_count"])
        self.assertEqual(7, result["shared_plan_count"])
        self.assertTrue(result["shared_bindings_exact"])
        self.assertTrue(result["shared_provenance_exact"])
        self.assertTrue(result["held_fields_intact"])
        self.assertEqual(0, result["woo_products"])

    @staticmethod
    def shared_woo_order_assertion_php(evaluation_first: bool) -> str:
        if evaluation_first:
            materialize = """
$evaluation = Complete99_Evaluation_Catalog::materialize();
$graph = Complete99_Catalog_Graph::materialize_drafts();
$order = 'evaluation_then_graph';
"""
        else:
            materialize = """
$graph = Complete99_Catalog_Graph::materialize_drafts();
$evaluation = Complete99_Evaluation_Catalog::materialize();
$order = 'graph_then_evaluation';
"""
        return (
            materialize
            + """
if (is_wp_error($evaluation) || is_wp_error($graph)) {
    $error = is_wp_error($evaluation) ? $evaluation : $graph;
    echo json_encode(array(
        'error' => $error->get_error_code() . ':' . $error->get_error_message(),
        'order' => $order,
    ), JSON_THROW_ON_ERROR);
    return;
}

$registry = require $c99_catalog_data_path;
$expected_by_ingredient = array();
$expected_by_product = array();
foreach ($registry['products'] as $seed) {
    $expected_by_ingredient[$seed['ingredient_code']] = $seed;
    $expected_by_product[$seed['product_code']] = $seed;
}

$product_records = 0;
$published_records = 0;
$evaluation_products = 0;
$shared_products = 0;
$held_fields_intact = true;
foreach ($posts as $post_id => $post) {
    if ('publish' === $post->post_status) {
        ++$published_records;
    }
    if ('product' !== $post->post_type) {
        continue;
    }
    ++$product_records;
    if ('1' !== ($meta[$post_id]['_complete99_evaluation_catalog_managed'] ?? '')) {
        continue;
    }
    ++$evaluation_products;
    if ('1' === ($meta[$post_id]['_complete99_catalog_managed'] ?? '')) {
        ++$shared_products;
    }
    $product_code = $meta[$post_id]['_complete99_evaluation_product_code'];
    $seed = $expected_by_product[$product_code] ?? null;
    $price = $seed
        ? number_format((float) $seed['evaluation_price_ils'], 2, '.', '')
        : '';
    $product = wc_get_product($post_id);
    $held_fields_intact = $held_fields_intact
        && null !== $seed
        && $product
        && 'draft' === $post->post_status
        && 'hidden' === $product->get_catalog_visibility()
        && $product_code === $product->get_sku()
        && $price === $product->get_price()
        && $price === $product->get_regular_price()
        && '' === $product->get_sale_price()
        && $product->managing_stock()
        && 1 === (int) $product->get_stock_quantity()
        && 'instock' === $product->get_stock_status()
        && !$product->backorders_allowed()
        && !$product->is_virtual()
        && !$product->is_downloadable()
        && !$product->is_purchasable()
        && $product_code === $meta[$post_id]['_complete99_catalog_product_code']
        && $price === (string) $meta[$post_id]['_complete99_evaluation_price_ils']
        && 1 === (int) $meta[$post_id]['_complete99_evaluation_stock']
        && 'private_benchmark_only'
            === $meta[$post_id]['_complete99_evaluation_price_scope']
        && 'private_evaluation_only'
            === $meta[$post_id]['_complete99_evaluation_stock_scope']
        && 'no' === $meta[$post_id]['_complete99_store_approved']
        && 'evaluation_only' === $meta[$post_id]['_complete99_stock_authority']
        && 'no' === $meta[$post_id]['_complete99_product_label_reviewed']
        && 'no' === $meta[$post_id]['_complete99_product_rights_reviewed']
        && 'no' === $meta[$post_id]['_complete99_product_tax_reviewed']
        && 'no' === $meta[$post_id]['_complete99_media_public_safe']
        && array_key_exists('_complete99_index_eligible', $meta[$post_id])
        && '' === $meta[$post_id]['_complete99_index_eligible'];
}

$shared_bindings_exact = true;
foreach ($graph['woo_products'] as $ingredient_code => $product_id) {
    $seed = $expected_by_ingredient[$ingredient_code] ?? null;
    $shared_bindings_exact = $shared_bindings_exact
        && null !== $seed
        && $product_id === $evaluation['woo_products'][$seed['product_code']];
}

echo json_encode(array(
    'error' => '',
    'order' => $order,
    'post_count' => count($posts),
    'product_records' => $product_records,
    'published_records' => $published_records,
    'evaluation_products' => $evaluation_products,
    'shared_products' => $shared_products,
    'shared_bindings_exact' => $shared_bindings_exact,
    'held_fields_intact' => $held_fields_intact,
), JSON_THROW_ON_ERROR);
"""
        )

    @staticmethod
    def wordpress_storage_stub(woo: bool = False) -> str:
        woo_stub = (
            """
class WooCommerce {}
class WC_Product_Simple {
    public $id = 0;
    public $name = '';
    public $status = 'draft';
    public $visibility = 'hidden';
    public $description = '';
    public $short_description = '';
    public $sku = '';
    public $price = '';
    public $regular_price = '';
    public $sale_price = '';
    public $manage_stock = false;
    public $stock_quantity = null;
    public $stock_status = 'outofstock';
    public $backorders = 'no';
    public $sold_individually = false;
    public $virtual = false;
    public $downloadable = false;
    public $purchase_note = '';
    public function is_type($type) { return 'simple' === $type; }
    public function set_name($value) { $this->name = $value; }
    public function set_status($value) { $this->status = $value; }
    public function set_catalog_visibility($value) { $this->visibility = $value; }
    public function set_description($value) { $this->description = $value; }
    public function set_short_description($value) { $this->short_description = $value; }
    public function set_sku($value) { $this->sku = $value; }
    public function set_price($value) { $this->price = $value; }
    public function set_regular_price($value) { $this->regular_price = $value; }
    public function set_sale_price($value) { $this->sale_price = $value; }
    public function set_manage_stock($value) { $this->manage_stock = $value; }
    public function set_stock_quantity($value) { $this->stock_quantity = $value; }
    public function set_stock_status($value) { $this->stock_status = $value; }
    public function set_backorders($value) { $this->backorders = $value; }
    public function set_sold_individually($value) { $this->sold_individually = $value; }
    public function set_virtual($value) { $this->virtual = $value; }
    public function set_downloadable($value) { $this->downloadable = $value; }
    public function set_purchase_note($value) { $this->purchase_note = $value; }
    public function get_catalog_visibility() { return $this->visibility; }
    public function get_sku() { return $this->sku; }
    public function get_price() { return $this->price; }
    public function get_regular_price() { return $this->regular_price; }
    public function get_sale_price() { return $this->sale_price; }
    public function managing_stock() { return $this->manage_stock; }
    public function get_stock_quantity() { return $this->stock_quantity; }
    public function get_stock_status() { return $this->stock_status; }
    public function backorders_allowed() { return 'no' !== $this->backorders; }
    public function is_virtual() { return $this->virtual; }
    public function is_downloadable() { return $this->downloadable; }
    public function is_purchasable() {
        return 'publish' === $this->status && '' !== $this->price;
    }
    public function save() {
        $post = array(
            'post_type' => 'product',
            'post_status' => $this->status,
            'post_title' => $this->name,
            'post_name' => $this->sku,
            'post_excerpt' => $this->short_description,
            'post_content' => $this->description,
        );
        if (!$this->id) {
            $this->id = wp_insert_post($post, true);
        } else {
            $post['ID'] = $this->id;
            wp_update_post($post, true);
        }
        if (!isset($GLOBALS['meta'][$this->id])) {
            $GLOBALS['meta'][$this->id] = array();
        }
        $GLOBALS['meta'][$this->id]['_sku'] = $this->sku;
        $GLOBALS['products'][$this->id] = $this;
        return $this->id;
    }
}
function wc_get_product($product_id) {
    return isset($GLOBALS['products'][$product_id])
        ? $GLOBALS['products'][$product_id]
        : false;
}
function wc_get_product_id_by_sku($sku) {
    foreach ($GLOBALS['products'] as $product_id => $product) {
        if ($sku === $product->get_sku()) {
            return $product_id;
        }
    }
    return 0;
}
"""
            if woo
            else ""
        )
        return (
            """
$posts = array();
$meta = array();
$options = array();
$products = array();
$next_id = 1;
$sanitize_registered_index_meta = false;
function absint($value) { return abs((int) $value); }
function wp_slash($value) { return $value; }
function wp_cache_delete($key, $group = '') { return true; }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function rest_sanitize_boolean($value) {
    if (is_bool($value)) {
        return $value;
    }
    if ('false' === $value) {
        return false;
    }
    return (bool) $value;
}
function post_type_exists($post_type) {
    return in_array(
        $post_type,
        array('c99_ingredient', 'c99_product_plan', 'product'),
        true
    );
}
function get_post_stati() {
    return array('private', 'draft', 'publish', 'trash');
}
function wp_insert_post($post, $wp_error = false) {
    $id = $GLOBALS['next_id']++;
    $object = (object) $post;
    $object->ID = $id;
    $GLOBALS['posts'][$id] = $object;
    return $id;
}
function wp_update_post($post, $wp_error = false) {
    $id = (int) $post['ID'];
    if (!isset($GLOBALS['posts'][$id])) {
        return new WP_Error('missing', 'missing post');
    }
    foreach ($post as $key => $value) {
        if ('ID' !== $key) {
            $GLOBALS['posts'][$id]->{$key} = $value;
        }
    }
    return $id;
}
function get_post($post_id) {
    return isset($GLOBALS['posts'][$post_id])
        ? $GLOBALS['posts'][$post_id]
        : null;
}
function get_post_status($post_id) {
    return isset($GLOBALS['posts'][$post_id])
        ? $GLOBALS['posts'][$post_id]->post_status
        : false;
}
function get_posts($args) {
    $key = $args['meta_query'][0]['key'];
    $value = $args['meta_query'][0]['value'];
    $ids = array();
    foreach ($GLOBALS['posts'] as $post_id => $post) {
        if ($args['post_type'] !== $post->post_type) {
            continue;
        }
        if (isset($GLOBALS['meta'][$post_id])
            && array_key_exists($key, $GLOBALS['meta'][$post_id])
            && $value === $GLOBALS['meta'][$post_id][$key]) {
            $ids[] = $post_id;
        }
    }
    sort($ids, SORT_NUMERIC);
    return array_slice($ids, 0, (int) $args['posts_per_page']);
}
function update_post_meta($post_id, $key, $value) {
    if (!isset($GLOBALS['meta'][$post_id])) {
        $GLOBALS['meta'][$post_id] = array();
    }
    if (!empty($GLOBALS['sanitize_registered_index_meta'])
        && '_complete99_index_eligible' === $key) {
        $value = rest_sanitize_boolean($value) ? '1' : '';
    }
    $same = array_key_exists($key, $GLOBALS['meta'][$post_id])
        && $GLOBALS['meta'][$post_id][$key] === $value;
    $GLOBALS['meta'][$post_id][$key] = $value;
    return !$same;
}
function get_post_meta($post_id, $key, $single = false) {
    return isset($GLOBALS['meta'][$post_id])
        && array_key_exists($key, $GLOBALS['meta'][$post_id])
        ? $GLOBALS['meta'][$post_id][$key]
        : '';
}
function metadata_exists($type, $post_id, $key) {
    return isset($GLOBALS['meta'][$post_id])
        && array_key_exists($key, $GLOBALS['meta'][$post_id]);
}
function delete_post_meta($post_id, $key) {
    if (isset($GLOBALS['meta'][$post_id][$key])) {
        unset($GLOBALS['meta'][$post_id][$key]);
        return true;
    }
    return false;
}
function update_option($name, $value, $autoload = null) {
    $same = array_key_exists($name, $GLOBALS['options'])
        && $GLOBALS['options'][$name] === $value;
    $GLOBALS['options'][$name] = $value;
    return !$same;
}
function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['options'])
        ? $GLOBALS['options'][$name]
        : $default;
}
"""
            + woo_stub
        )


if __name__ == "__main__":
    unittest.main()
