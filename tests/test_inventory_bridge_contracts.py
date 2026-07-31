import json
import shutil
import subprocess
import textwrap
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SOURCE = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-inventory-bridge.php"
)


class InventoryBridgeSourceContracts(unittest.TestCase):
    def setUp(self):
        self.source = SOURCE.read_text(encoding="utf-8")

    def test_route_is_signed_and_never_public(self):
        self.assertIn("'/inventory/sync'", self.source)
        self.assertIn(
            "'permission_callback' => array( 'Complete99_REST', 'verify_sync_signature' )",
            self.source,
        )
        self.assertNotIn("'permission_callback' => '__return_true'", self.source)

    def test_contract_is_strict_and_bounded(self):
        for marker in (
            "complete99-inventory-sync/v1",
            "array( 'schema', 'batch_id', 'generated_at', 'source', 'mode', 'items' )",
            "array( 'product_code', 'version', 'quantity', 'reason' )",
            "const MAX_ITEMS       = 100;",
            "const MAX_CLOCK_SKEW  = 300;",
            "const MAX_VERSION     = 2147483647;",
            "const MAX_QUANTITY    = 1000000;",
            r"/\Aproduct-[a-z0-9]+(?:-[a-z0-9]+)*\z/",
        ):
            self.assertIn(marker, self.source)

    def test_binding_is_private_exact_and_has_no_creation_or_heuristic(self):
        self.assertIn(
            "const META_PRODUCT_CODE        = '_complete99_catalog_product_code';",
            self.source,
        )
        self.assertIn("'meta_query'", self.source)
        self.assertIn("'value'   => $product_code", self.source)
        for forbidden in (
            "wp_insert_post(",
            "wp_update_post(",
            "new WC_Product",
            "set_sku(",
            "'post_title'",
        ):
            self.assertNotIn(forbidden, self.source)
        self.assertIn("evaluation_target_is_exactly_held", self.source)
        self.assertIn(
            "hash_equals( $product_code, (string) $product->get_sku() )",
            self.source,
        )

    def test_registered_meta_is_private(self):
        self.assertIn("public static function register_meta()", self.source)
        self.assertIn("'show_in_rest'      => false", self.source)
        self.assertIn("_complete99_inventory_sync_enabled", self.source)
        self.assertIn("_complete99_evaluation_inventory_quantity", self.source)

    def test_evaluation_and_commerce_gates_are_explicit(self):
        for marker in (
            "'draft' === (string) get_post_status( $product_id )",
            "'hidden' === (string) $product->get_catalog_visibility()",
            "self::EVALUATION_META_MANAGED",
            "self::EVALUATION_META_REGISTRY_DIGEST",
            "'private_benchmark_only'",
            "'private_evaluation_only'",
            "'held_until_acceptance'",
            "'publish' !== $post_status",
            "'yes' !== (string) get_post_meta( $product_id, self::META_SYNC_ENABLED, true )",
            "'woocommerce' !== (string) get_post_meta( $product_id, self::STOCK_AUTHORITY, true )",
            "true !== Complete99_Commerce::catalog_is_ready()",
            "wc_update_product_stock( $product_id, $item['quantity'], 'set' )",
        ):
            self.assertIn(marker, self.source)

    def test_migration_guard_requires_exact_durable_catalog(self):
        for marker in (
            "private static function migration_guard( $mode )",
            "Complete99_Platform::migration_failed()",
            "Complete99_Platform::evaluation_catalog_ready()",
            "COMPLETE99_PLATFORM_VERSION !== (string) get_option",
            "complete99_inventory_migration_incomplete",
        ):
            self.assertIn(marker, self.source)


@unittest.skipUnless(shutil.which("php"), "PHP is required for bridge behavior tests")
class InventoryBridgeBehaviorContracts(unittest.TestCase):
    maxDiff = None

    def run_harness(self):
        class_path = str(SOURCE).replace("\\", "/").replace("'", "\\'")
        php = (
            textwrap.dedent(
                r"""
                <?php
                define( 'ABSPATH', __DIR__ . '/' );
                define( 'COMPLETE99_PLATFORM_VERSION', '1.3.0' );

                class WP_Error {
                    public $code;
                    public $message;
                    public $data;
                    public function __construct( $code, $message, $data = array() ) {
                        $this->code = $code;
                        $this->message = $message;
                        $this->data = $data;
                    }
                    public function get_error_code() { return $this->code; }
                    public function get_error_data() { return $this->data; }
                }
                function is_wp_error( $value ) { return $value instanceof WP_Error; }

                class WP_REST_Request {
                    private $body;
                    public function __construct( $body ) { $this->body = $body; }
                    public function get_body() { return $this->body; }
                }
                class WP_REST_Server { const CREATABLE = 'POST'; }

                $GLOBALS['actions'] = array();
                $GLOBALS['routes'] = array();
                $GLOBALS['registered_meta'] = array();
                $GLOBALS['options'] = array();
                $GLOBALS['post_meta'] = array();
                $GLOBALS['products'] = array();

                function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
                    $GLOBALS['actions'][ $hook ][] = $callback;
                }
                function register_rest_route( $namespace, $route, $args ) {
                    $GLOBALS['routes'][ $namespace . $route ] = $args;
                }
                function register_post_meta( $type, $key, $args ) {
                    $GLOBALS['registered_meta'][ $type ][ $key ] = $args;
                }
                function current_user_can( $capability ) { return true; }
                function sanitize_text_field( $value ) { return trim( (string) $value ); }
                function absint( $value ) { return abs( (int) $value ); }
                function rest_ensure_response( $value ) { return $value; }
                function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
                function wp_cache_delete( $key, $group = '' ) { return true; }
                function get_current_blog_id() { return 1; }
                function home_url( $path = '/' ) { return 'https://example.test' . $path; }
                function wp_rand() { return 123456; }

                function get_option( $key, $default = false ) {
                    return array_key_exists( $key, $GLOBALS['options'] )
                        ? $GLOBALS['options'][ $key ]
                        : $default;
                }
                function update_option( $key, $value, $autoload = null ) {
                    $changed = ! array_key_exists( $key, $GLOBALS['options'] )
                        || serialize( $GLOBALS['options'][ $key ] ) !== serialize( $value );
                    $GLOBALS['options'][ $key ] = $value;
                    return $changed;
                }
                function add_option( $key, $value, $deprecated = '', $autoload = 'yes' ) {
                    if ( array_key_exists( $key, $GLOBALS['options'] ) ) { return false; }
                    $GLOBALS['options'][ $key ] = $value;
                    return true;
                }
                function delete_option( $key ) {
                    if ( ! array_key_exists( $key, $GLOBALS['options'] ) ) { return false; }
                    unset( $GLOBALS['options'][ $key ] );
                    return true;
                }

                function metadata_exists( $type, $id, $key ) {
                    return isset( $GLOBALS['post_meta'][ $id ] )
                        && array_key_exists( $key, $GLOBALS['post_meta'][ $id ] );
                }
                function get_post_meta( $id, $key, $single = false ) {
                    return metadata_exists( 'post', $id, $key )
                        ? $GLOBALS['post_meta'][ $id ][ $key ]
                        : '';
                }
                function update_post_meta( $id, $key, $value ) {
                    $changed = ! metadata_exists( 'post', $id, $key )
                        || serialize( $GLOBALS['post_meta'][ $id ][ $key ] ) !== serialize( $value );
                    $GLOBALS['post_meta'][ $id ][ $key ] = $value;
                    return $changed ? 1 : false;
                }
                function delete_post_meta( $id, $key ) {
                    if ( ! metadata_exists( 'post', $id, $key ) ) { return false; }
                    unset( $GLOBALS['post_meta'][ $id ][ $key ] );
                    return true;
                }
                function get_post_stati() {
                    return array( 'publish' => 'publish', 'draft' => 'draft', 'private' => 'private', 'trash' => 'trash' );
                }
                function get_post_status( $id ) {
                    return isset( $GLOBALS['products'][ $id ] ) ? $GLOBALS['products'][ $id ]->status : false;
                }
                function get_post_type( $id ) {
                    return isset( $GLOBALS['products'][ $id ] ) ? 'product' : false;
                }
                function get_posts( $args ) {
                    $query = $args['meta_query'][0];
                    $matches = array();
                    foreach ( $GLOBALS['products'] as $id => $product ) {
                        if ( metadata_exists( 'post', $id, $query['key'] )
                            && (string) get_post_meta( $id, $query['key'], true ) === (string) $query['value'] ) {
                            $matches[] = $id;
                        }
                    }
                    sort( $matches, SORT_NUMERIC );
                    return array_slice( $matches, 0, (int) $args['posts_per_page'] );
                }

                class FakeProduct {
                    public $id;
                    public $status;
                    public $visibility;
                    public $manage_stock;
                    public $quantity;
                    public $stock_status;
                    public $sku;
                    public $price;
                    public $regular_price;
                    public $sale_price = '';
                    public $save_count = 0;
                    public function __construct(
                        $id,
                        $status,
                        $visibility,
                        $manage_stock,
                        $quantity,
                        $sku = '',
                        $price = '10.00'
                    ) {
                        $this->id = $id;
                        $this->status = $status;
                        $this->visibility = $visibility;
                        $this->manage_stock = $manage_stock;
                        $this->quantity = $quantity;
                        $this->stock_status = is_numeric( $quantity ) && 0 < $quantity ? 'instock' : 'outofstock';
                        $this->sku = $sku;
                        $this->price = $price;
                        $this->regular_price = $price;
                    }
                    public function is_type( $type ) { return 'simple' === $type; }
                    public function get_catalog_visibility() { return $this->visibility; }
                    public function get_sku() { return $this->sku; }
                    public function get_price() { return $this->price; }
                    public function get_regular_price() { return $this->regular_price; }
                    public function get_sale_price() { return $this->sale_price; }
                    public function managing_stock() { return $this->manage_stock; }
                    public function get_stock_quantity() { return $this->quantity; }
                    public function set_manage_stock( $value ) { $this->manage_stock = (bool) $value; }
                    public function set_stock_quantity( $value ) { $this->quantity = $value; }
                    public function get_stock_status() { return $this->stock_status; }
                    public function set_stock_status( $value ) { $this->stock_status = $value; }
                    public function backorders_allowed() { return false; }
                    public function is_virtual() { return false; }
                    public function is_downloadable() { return false; }
                    public function is_purchasable() { return false; }
                    public function save() { ++$this->save_count; return $this->id; }
                }
                function wc_get_product( $id ) {
                    return isset( $GLOBALS['products'][ $id ] ) ? $GLOBALS['products'][ $id ] : false;
                }
                function wc_update_product_stock( $id, $quantity, $operation = 'set' ) {
                    $product = wc_get_product( $id );
                    if ( ! $product ) { return false; }
                    $product->set_stock_quantity( $quantity );
                    $product->save();
                    return $quantity;
                }
                function wc_update_product_stock_status( $id, $status ) {
                    $product = wc_get_product( $id );
                    if ( ! $product ) { return false; }
                    $product->set_stock_status( $status );
                    $product->save();
                }
                class WooCommerce {}
                class Complete99_REST {
                    public static function verify_sync_signature( $request ) { return true; }
                }
                class Complete99_Platform {
                    public static $migration_failed = false;
                    public static $evaluation_ready = true;
                    public static function migration_failed() {
                        return self::$migration_failed;
                    }
                    public static function evaluation_catalog_ready() {
                        return self::$evaluation_ready;
                    }
                }
                class Complete99_Commerce {
                    public static $ready = false;
                    public static function is_ready() { return self::$ready; }
                    public static function catalog_is_ready() { return self::$ready; }
                }
                class FakeWpdb {
                    public function prepare( $query, ...$args ) { return array( $query, $args ); }
                    public function get_var( $prepared ) {
                        return false !== strpos( $prepared[0], 'GET_LOCK' ) ? '1' : '1';
                    }
                }
                $GLOBALS['wpdb'] = new FakeWpdb();

                require '__CLASS_PATH__';

                function result_value( $value ) {
                    if ( $value instanceof WP_Error ) {
                        return array(
                            'error' => $value->get_error_code(),
                            'status' => isset( $value->get_error_data()['status'] )
                                ? $value->get_error_data()['status']
                                : 0,
                        );
                    }
                    return $value;
                }
                function run_payload( $payload ) {
                    return result_value(
                        Complete99_Inventory_Bridge::sync_inventory(
                            new WP_REST_Request(
                                json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
                            )
                        )
                    );
                }
                function payload_for( $batch, $mode, $items ) {
                    return array(
                        'schema' => 'complete99-inventory-sync/v1',
                        'batch_id' => $batch,
                        'generated_at' => gmdate( 'c' ),
                        'source' => 'complete99-os',
                        'mode' => $mode,
                        'items' => $items,
                    );
                }
                function item_for( $code, $version, $quantity, $reason = 'operator-count' ) {
                    return array(
                        'product_code' => $code,
                        'version' => $version,
                        'quantity' => $quantity,
                        'reason' => $reason,
                    );
                }

                Complete99_Inventory_Bridge::boot();
                foreach ( $GLOBALS['actions']['init'] as $callback ) { call_user_func( $callback ); }
                foreach ( $GLOBALS['actions']['rest_api_init'] as $callback ) { call_user_func( $callback ); }

                $code_meta = '_complete99_catalog_product_code';
                $enable_meta = '_complete99_inventory_sync_enabled';
                $authority_meta = '_complete99_stock_authority';
                $evaluation_meta = '_complete99_evaluation_inventory_quantity';

                $GLOBALS['options']['complete99_platform_version'] = '1.3.0';
                $GLOBALS['options']['complete99_evaluation_catalog_receipt'] = array(
                    'registry_digest' => str_repeat( 'a', 64 ),
                );

                Complete99_Platform::$evaluation_ready = false;
                $migration_blocked = run_payload(
                    payload_for(
                        'batch-migration-blocked',
                        'evaluation',
                        array( item_for( 'product-tahini-500g', 1, 1, 'evaluation-seed' ) )
                    )
                );
                Complete99_Platform::$evaluation_ready = true;

                $GLOBALS['products'][10] = new FakeProduct(
                    10,
                    'draft',
                    'hidden',
                    true,
                    1,
                    'product-tahini-500g',
                    '14.90'
                );
                $GLOBALS['post_meta'][10][$code_meta] = 'product-tahini-500g';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_catalog_managed'] = '1';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_product_code'] = 'product-tahini-500g';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_price_ils'] = '14.90';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_stock'] = 1;
                $GLOBALS['post_meta'][10]['_complete99_evaluation_price_scope'] = 'private_benchmark_only';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_stock_scope'] = 'private_evaluation_only';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_sale_state'] = 'held_until_acceptance';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_public_sale_eligible'] = 'no';
                $GLOBALS['post_meta'][10]['_complete99_evaluation_registry_digest'] = str_repeat( 'a', 64 );
                $GLOBALS['post_meta'][10]['_complete99_store_approved'] = 'no';
                $GLOBALS['post_meta'][10][$authority_meta] = 'evaluation_only';
                $GLOBALS['post_meta'][10]['_complete99_product_label_reviewed'] = 'no';
                $GLOBALS['post_meta'][10]['_complete99_product_rights_reviewed'] = 'no';
                $GLOBALS['post_meta'][10]['_complete99_product_tax_reviewed'] = 'no';
                $GLOBALS['post_meta'][10]['_complete99_media_public_safe'] = 'no';

                $eval = payload_for(
                    'batch-evaluation-001',
                    'evaluation',
                    array( item_for( 'product-tahini-500g', 1, 1, 'evaluation-seed' ) )
                );
                $eval_first = run_payload( $eval );
                $eval_replay = run_payload( $eval );
                $eval_conflict = run_payload(
                    payload_for(
                        'batch-evaluation-002',
                        'evaluation',
                        array( item_for( 'product-tahini-500g', 1, 2, 'evaluation-seed' ) )
                    )
                );
                $GLOBALS['post_meta'][10]['_complete99_store_approved'] = 'yes';
                $evaluation_target_closed = run_payload(
                    payload_for(
                        'batch-evaluation-held-gate',
                        'evaluation',
                        array( item_for( 'product-tahini-500g', 2, 1, 'evaluation-seed' ) )
                    )
                );
                $GLOBALS['post_meta'][10]['_complete99_store_approved'] = 'no';

                $unknown = $eval;
                $unknown['unknown'] = 'field';
                $unknown_result = run_payload( $unknown );
                $string_quantity = payload_for(
                    'batch-invalid-quantity',
                    'evaluation',
                    array(
                        array(
                            'product_code' => 'product-tahini-500g',
                            'version' => 2,
                            'quantity' => '2',
                            'reason' => 'operator-count',
                        ),
                    )
                );
                $string_quantity_result = run_payload( $string_quantity );
                $stale_time = payload_for(
                    'batch-stale-time',
                    'evaluation',
                    array( item_for( 'product-tahini-500g', 2, 2 ) )
                );
                $stale_time['generated_at'] = gmdate( 'c', time() - 301 );
                $stale_time_result = run_payload( $stale_time );

                $GLOBALS['products'][20] = new FakeProduct( 20, 'publish', 'visible', true, 5 );
                $GLOBALS['post_meta'][20][$code_meta] = 'product-olive-oil-750ml';
                $GLOBALS['post_meta'][20][$authority_meta] = 'woocommerce';
                $GLOBALS['post_meta'][20][$enable_meta] = 'yes';
                $commerce_one = payload_for(
                    'batch-commerce-001',
                    'commerce',
                    array( item_for( 'product-olive-oil-750ml', 1, 7, 'operator-count' ) )
                );
                $commerce_not_ready = run_payload( $commerce_one );
                Complete99_Commerce::$ready = true;
                $GLOBALS['post_meta'][20][$enable_meta] = 'no';
                $commerce_not_enabled = run_payload( $commerce_one );
                $GLOBALS['post_meta'][20][$enable_meta] = 'yes';
                $commerce_first = run_payload( $commerce_one );
                $commerce_replay = run_payload( $commerce_one );
                $commerce_second = run_payload(
                    payload_for(
                        'batch-commerce-002',
                        'commerce',
                        array( item_for( 'product-olive-oil-750ml', 2, 8, 'operator-count' ) )
                    )
                );
                $commerce_stale = run_payload(
                    payload_for(
                        'batch-commerce-003',
                        'commerce',
                        array( item_for( 'product-olive-oil-750ml', 1, 7, 'operator-count' ) )
                    )
                );

                $GLOBALS['products'][30] = new FakeProduct( 30, 'draft', 'hidden', false, null );
                $GLOBALS['products'][31] = new FakeProduct( 31, 'draft', 'hidden', false, null );
                $GLOBALS['post_meta'][30][$code_meta] = 'product-duplicate-1kg';
                $GLOBALS['post_meta'][31][$code_meta] = 'product-duplicate-1kg';
                $duplicate_binding = run_payload(
                    payload_for(
                        'batch-duplicate-binding',
                        'evaluation',
                        array( item_for( 'product-duplicate-1kg', 1, 1 ) )
                    )
                );
                $missing_binding = run_payload(
                    payload_for(
                        'batch-missing-binding',
                        'evaluation',
                        array( item_for( 'product-missing-1kg', 1, 1 ) )
                    )
                );

                $route = $GLOBALS['routes']['complete99/v1/inventory/sync'];
                $meta_private = true;
                foreach ( $GLOBALS['registered_meta']['product'] as $definition ) {
                    if ( false !== $definition['show_in_rest'] ) { $meta_private = false; }
                }

                echo json_encode(
                    array(
                        'route_permission' => $route['permission_callback'],
                        'meta_private' => $meta_private,
                        'registered_meta_count' => count( $GLOBALS['registered_meta']['product'] ),
                        'migration_blocked' => $migration_blocked,
                        'eval_first' => $eval_first,
                        'eval_replay' => $eval_replay,
                        'eval_conflict' => $eval_conflict,
                        'evaluation_target_closed' => $evaluation_target_closed,
                        'eval_quantity' => get_post_meta( 10, $evaluation_meta, true ),
                        'eval_save_count' => $GLOBALS['products'][10]->save_count,
                        'unknown_result' => $unknown_result,
                        'string_quantity_result' => $string_quantity_result,
                        'stale_time_result' => $stale_time_result,
                        'commerce_not_ready' => $commerce_not_ready,
                        'commerce_not_enabled' => $commerce_not_enabled,
                        'commerce_first' => $commerce_first,
                        'commerce_replay' => $commerce_replay,
                        'commerce_second' => $commerce_second,
                        'commerce_stale' => $commerce_stale,
                        'commerce_quantity' => $GLOBALS['products'][20]->quantity,
                        'commerce_save_count' => $GLOBALS['products'][20]->save_count,
                        'duplicate_binding' => $duplicate_binding,
                        'missing_binding' => $missing_binding,
                        'receipt_count' => count( get_option( 'complete99_inventory_sync_receipts', array() ) ),
                    ),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                """
            )
            .replace("__CLASS_PATH__", class_path)
            .strip()
        )
        completed = subprocess.run(
            [shutil.which("php")],
            input=php,
            text=True,
            capture_output=True,
            check=False,
            cwd=ROOT,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        return json.loads(completed.stdout)

    def test_signed_route_and_private_meta_registration(self):
        result = self.run_harness()
        self.assertEqual(
            ["Complete99_REST", "verify_sync_signature"],
            result["route_permission"],
        )
        self.assertTrue(result["meta_private"])
        self.assertGreaterEqual(result["registered_meta_count"], 10)

    def test_evaluation_updates_only_private_meta_and_is_idempotent(self):
        result = self.run_harness()
        self.assertEqual("accepted", result["eval_first"]["status"])
        self.assertEqual(1, result["eval_first"]["write_count"])
        self.assertFalse(result["eval_first"]["idempotent"])
        self.assertTrue(result["eval_replay"]["idempotent"])
        self.assertEqual(1, result["eval_quantity"])
        self.assertEqual(0, result["eval_save_count"])
        self.assertEqual(
            "complete99_inventory_version_conflict",
            result["eval_conflict"]["error"],
        )
        self.assertEqual(
            "complete99_inventory_evaluation_target",
            result["evaluation_target_closed"]["error"],
        )

    def test_migration_guard_fails_closed_before_any_inventory_write(self):
        result = self.run_harness()
        self.assertEqual(
            "complete99_inventory_migration_incomplete",
            result["migration_blocked"]["error"],
        )
        self.assertEqual(503, result["migration_blocked"]["status"])

    def test_schema_timestamp_and_json_integer_contracts_fail_closed(self):
        result = self.run_harness()
        self.assertEqual(
            "complete99_inventory_fields", result["unknown_result"]["error"]
        )
        self.assertEqual(
            "complete99_inventory_quantity",
            result["string_quantity_result"]["error"],
        )
        self.assertEqual(
            "complete99_inventory_generated_at_stale",
            result["stale_time_result"]["error"],
        )

    def test_commerce_requires_catalog_readiness_enablement_and_monotonic_versions(self):
        result = self.run_harness()
        self.assertEqual(
            "complete99_inventory_store_not_ready",
            result["commerce_not_ready"]["error"],
        )
        self.assertEqual(
            "complete99_inventory_commerce_target",
            result["commerce_not_enabled"]["error"],
        )
        self.assertEqual(1, result["commerce_first"]["write_count"])
        self.assertTrue(result["commerce_replay"]["idempotent"])
        self.assertEqual(1, result["commerce_second"]["write_count"])
        self.assertEqual(
            "complete99_inventory_stale_version",
            result["commerce_stale"]["error"],
        )
        self.assertEqual(8, result["commerce_quantity"])
        self.assertEqual(4, result["commerce_save_count"])

    def test_missing_and_duplicate_private_bindings_are_rejected(self):
        result = self.run_harness()
        self.assertEqual(
            "complete99_inventory_product_binding_duplicate",
            result["duplicate_binding"]["error"],
        )
        self.assertEqual(
            "complete99_inventory_product_binding_missing",
            result["missing_binding"]["error"],
        )
        self.assertEqual(3, result["receipt_count"])


if __name__ == "__main__":
    unittest.main()
