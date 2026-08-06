from __future__ import annotations

import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
GRAPH = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-catalog-graph.php"
)
DATA = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "data"
    / "dish-entity-trees.php"
)


class Complete99CatalogGraphContracts(unittest.TestCase):
    maxDiff = None

    def run_php(self, body: str) -> dict:
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
require '{graph}';
$c99_data_path = '{data}';
{body}
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
        return json.loads(completed.stdout)

    def test_current_registry_strictly_validates_and_builds_bilingual_entities(
        self,
    ) -> None:
        result = self.run_php(
            """
$registry = require $c99_data_path;
$validation = Complete99_Catalog_Graph::validate_registry($registry);
$entities = Complete99_Catalog_Graph::entity_registry();
$ingredient_count = 0;
$bilingual = true;
if (!is_wp_error($entities)) {
    foreach ($entities['entities'] as $entity_id => $entity) {
        if (0 === strpos($entity_id, 'ingredient-')) {
            ++$ingredient_count;
        }
        foreach (array('he', 'en') as $language) {
            if ('' === trim($entity['name'][$language])
                || '' === trim($entity['description'][$language])) {
                $bilingual = false;
            }
        }
    }
}
echo json_encode(array(
    'valid' => true === $validation,
    'dish_count' => count($registry['dishes']),
    'entity_error' => is_wp_error($entities),
    'entity_count' => is_wp_error($entities) ? 0 : count($entities['entities']),
    'ingredient_count' => $ingredient_count,
    'bilingual' => $bilingual,
    'schema' => is_wp_error($entities) ? '' : $entities['graph_version'],
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(result["valid"])
        self.assertEqual(12, result["dish_count"])
        self.assertFalse(result["entity_error"])
        self.assertGreater(result["entity_count"], result["ingredient_count"])
        self.assertGreater(result["ingredient_count"], 0)
        self.assertTrue(result["bilingual"])
        self.assertEqual(
            "complete99-dish-entity-tree-registry/v1", result["schema"]
        )

    def test_mutated_graphs_fail_closed_across_every_sensitive_section(
        self,
    ) -> None:
        result = self.run_php(
            """
$registry = require $c99_data_path;
$mutations = array();

$bad = $registry;
$bad['schema'] = 'complete99-dish-entity-tree-registry/v2';
$mutations['schema'] = $bad;

$bad = $registry;
$bad['dishes'][1]['dish_id'] = $bad['dishes'][0]['dish_id'];
$bad['dishes'][1]['source_record_id'] = $bad['dishes'][0]['dish_id'];
$mutations['duplicate_dish'] = $bad;

$bad = $registry;
$bad['dishes'][0]['component_tree']['children'][0]['unexpected'] = true;
$mutations['component_field'] = $bad;

$bad = $registry;
$bad['dishes'][0]['component_tree']['children'][0]['evidence']['source_id'] = 'missing-source';
$mutations['component_source'] = $bad;

$bad = $registry;
$bad['dishes'][0]['allergen_information']['allergens']['eggs']['state'] = 'free';
$mutations['allergen_state'] = $bad;

$bad = $registry;
$bad['dishes'][0]['nutrition']['values'] = array('energy_kcal' => 100);
$mutations['nutrition'] = $bad;

$bad = $registry;
$bad['dishes'][0]['review']['owners']['nutrition'] = 'Named Person';
$mutations['review'] = $bad;

$bad = $registry;
$bad['dishes'][0]['relations']['product_codes'] = array('product-unreviewed');
$mutations['product_relation'] = $bad;

$out = array();
foreach ($mutations as $name => $candidate) {
    $validation = Complete99_Catalog_Graph::validate_registry($candidate);
    $out[$name] = is_wp_error($validation)
        && 'complete99_catalog_graph_invalid' === $validation->get_error_code();
}
echo json_encode($out, JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(result)
        self.assertTrue(all(result.values()), result)

    def test_boot_is_explicit_idempotent_and_registers_private_meta(self) -> None:
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
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }

Complete99_Catalog_Graph::boot();
Complete99_Catalog_Graph::boot();
Complete99_Catalog_Graph::register_meta();

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
    'dish_mapping_registered' => isset(
        $registered['c99_dish']['_complete99_catalog_dish_id']
    ),
    'ingredient_binding_types' => array(
        $registered['c99_ingredient']['_complete99_catalog_ingredient_code']['type'],
        $registered['c99_product_plan']['_complete99_catalog_ingredient_code']['type'],
        $registered['product']['_complete99_catalog_ingredient_code']['type'],
    ),
    'dish_good' => Complete99_Catalog_Graph::sanitize_dish_id(
        'menu-reference-sabich'
    ),
    'dish_bad' => Complete99_Catalog_Graph::sanitize_dish_id('Sabich'),
    'ingredient_good' => Complete99_Catalog_Graph::sanitize_ingredient_code(
        'ingredient-aubergine'
    ),
    'equipment_good' => Complete99_Catalog_Graph::sanitize_ingredient_code(
        'equipment-wasabi-grater'
    ),
    'ingredient_bad' => Complete99_Catalog_Graph::sanitize_ingredient_code(
        'component-aubergine'
    ),
    'list_bad' => Complete99_Catalog_Graph::sanitize_identifier_list(
        array('valid-code', 'Bad Code')
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
                },
                {
                    "hook": "save_post_c99_dish",
                    "method": "refresh_drafts_for_saved_dish",
                    "priority": 40,
                    "accepted_args": 3,
                },
            ],
            result["hooks"],
        )
        self.assertTrue(result["all_private"])
        self.assertTrue(result["dish_mapping_registered"])
        self.assertEqual(["string", "string", "string"], result["ingredient_binding_types"])
        self.assertEqual("menu-reference-sabich", result["dish_good"])
        self.assertEqual("", result["dish_bad"])
        self.assertEqual("ingredient-aubergine", result["ingredient_good"])
        self.assertEqual("equipment-wasabi-grater", result["equipment_good"])
        self.assertEqual("", result["ingredient_bad"])
        self.assertEqual([], result["list_bad"])

    def test_materialization_without_woocommerce_is_private_and_idempotent(
        self,
    ) -> None:
        result = self.run_php(
            self.wordpress_storage_stub()
            + """
$first = Complete99_Catalog_Graph::materialize_drafts(
    array('menu-reference-sabich')
);
$second = Complete99_Catalog_Graph::materialize_drafts(
    array('menu-reference-sabich')
);
if (is_wp_error($first) || is_wp_error($second)) {
    $error = is_wp_error($first) ? $first : $second;
    echo json_encode(array('error' => $error->get_error_message()), JSON_THROW_ON_ERROR);
    return;
}
$private = true;
$component_written = false;
$gates_closed = true;
$commercial_plan_meta_absent = true;
$provenance_persisted = true;
foreach ($posts as $post_id => $post) {
    if ('private' !== $post->post_status) {
        $private = false;
    }
    $code = isset($meta[$post_id]['_complete99_catalog_ingredient_code'])
        ? $meta[$post_id]['_complete99_catalog_ingredient_code']
        : '';
    if (0 === strpos((string) $code, 'component-')) {
        $component_written = true;
    }
    $provenance_persisted = $provenance_persisted
        && 0 === strpos((string) $code, 'ingredient-')
        && $code === $meta[$post_id]['_complete99_catalog_entity_id']
        && 'complete99-dish-entity-tree-registry/v1'
            === $meta[$post_id]['_complete99_catalog_graph_version']
        && in_array(
            'menu-reference-sabich',
            $meta[$post_id]['_complete99_catalog_source_dish_ids'],
            true
        )
        && '' !== trim($meta[$post_id]['_complete99_catalog_name_he'])
        && '' !== trim($meta[$post_id]['_complete99_catalog_name_en'])
        && '' !== trim($meta[$post_id]['_complete99_catalog_description_he'])
        && '' !== trim($meta[$post_id]['_complete99_catalog_description_en']);
    if ('c99_product_plan' === $post->post_type) {
        $gates_closed = $gates_closed
            && 'no' === $meta[$post_id]['_complete99_store_approved']
            && 'pending' === $meta[$post_id]['_complete99_stock_authority']
            && 'no' === $meta[$post_id]['_complete99_product_label_reviewed']
            && 'no' === $meta[$post_id]['_complete99_product_rights_reviewed']
            && 'no' === $meta[$post_id]['_complete99_product_tax_reviewed']
            && 'no' === $meta[$post_id]['_complete99_media_public_safe'];
        foreach (array(
            '_complete99_product_sku',
            '_complete99_product_weight',
            '_complete99_product_price',
            '_complete99_product_currency'
        ) as $held_key) {
            if (isset($meta[$post_id][$held_key])) {
                $commercial_plan_meta_absent = false;
            }
        }
    }
}
echo json_encode(array(
    'error' => '',
    'same_ids' => $first['ingredient_posts'] === $second['ingredient_posts']
        && $first['product_plan_posts'] === $second['product_plan_posts'],
    'post_count' => count($posts),
    'ingredient_count' => $first['ingredient_entity_count'],
    'ingredient_posts' => count($first['ingredient_posts']),
    'plan_posts' => count($first['product_plan_posts']),
    'woo_products' => count($first['woo_products']),
    'woo_materialized' => $first['woocommerce_materialized'],
    'private' => $private,
    'component_written' => $component_written,
    'gates_closed' => $gates_closed,
    'commercial_plan_meta_absent' => $commercial_plan_meta_absent,
    'provenance_persisted' => $provenance_persisted,
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual("", result["error"])
        self.assertTrue(result["same_ids"])
        self.assertEqual(
            result["ingredient_count"] * 2,
            result["post_count"],
        )
        self.assertEqual(result["ingredient_count"], result["ingredient_posts"])
        self.assertEqual(result["ingredient_count"], result["plan_posts"])
        self.assertEqual(0, result["woo_products"])
        self.assertFalse(result["woo_materialized"])
        self.assertTrue(result["private"])
        self.assertFalse(result["component_written"])
        self.assertTrue(result["gates_closed"])
        self.assertTrue(result["commercial_plan_meta_absent"])
        self.assertTrue(result["provenance_persisted"])

    def test_woocommerce_products_are_hidden_draft_unpriced_and_unstocked(
        self,
    ) -> None:
        result = self.run_php(
            self.wordpress_storage_stub(woo=True)
            + """
$first = Complete99_Catalog_Graph::materialize_drafts(
    array('menu-reference-shakshuka')
);
$second = Complete99_Catalog_Graph::materialize_drafts(
    array('menu-reference-shakshuka')
);
if (is_wp_error($first) || is_wp_error($second)) {
    $error = is_wp_error($first) ? $first : $second;
    echo json_encode(array('error' => $error->get_error_message()), JSON_THROW_ON_ERROR);
    return;
}
$product_id = (int) reset($first['woo_products']);
$product = wc_get_product($product_id);
$product_meta = $meta[$product_id];
echo json_encode(array(
    'error' => '',
    'same_product_ids' => $first['woo_products'] === $second['woo_products'],
    'product_count' => count($first['woo_products']),
    'post_count' => count($posts),
    'status' => get_post_status($product_id),
    'visibility' => $product->get_catalog_visibility(),
    'price' => $product->get_price(),
    'regular_price' => $product->get_regular_price(),
    'sale_price' => $product->get_sale_price(),
    'managing_stock' => $product->managing_stock(),
    'stock_quantity' => $product->get_stock_quantity(),
    'stock_status' => $product->get_stock_status(),
    'purchasable' => $product->is_purchasable(),
    'approved' => $product_meta['_complete99_store_approved'],
    'stock_authority' => $product_meta['_complete99_stock_authority'],
    'label_reviewed' => $product_meta['_complete99_product_label_reviewed'],
    'rights_reviewed' => $product_meta['_complete99_product_rights_reviewed'],
    'tax_reviewed' => $product_meta['_complete99_product_tax_reviewed'],
    'media_safe' => $product_meta['_complete99_media_public_safe'],
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertEqual("", result["error"])
        self.assertTrue(result["same_product_ids"])
        self.assertEqual(1, result["product_count"])
        self.assertEqual(3, result["post_count"])
        self.assertEqual("draft", result["status"])
        self.assertEqual("hidden", result["visibility"])
        self.assertEqual("", result["price"])
        self.assertEqual("", result["regular_price"])
        self.assertEqual("", result["sale_price"])
        self.assertFalse(result["managing_stock"])
        self.assertIsNone(result["stock_quantity"])
        self.assertEqual("outofstock", result["stock_status"])
        self.assertFalse(result["purchasable"])
        self.assertEqual("no", result["approved"])
        self.assertEqual("pending", result["stock_authority"])
        for key in (
            "label_reviewed",
            "rights_reviewed",
            "tax_reviewed",
            "media_safe",
        ):
            self.assertEqual("no", result[key])

    def test_invalid_selection_fails_before_any_write(self) -> None:
        result = self.run_php(
            self.wordpress_storage_stub()
            + """
$result = Complete99_Catalog_Graph::materialize_drafts(
    array('menu-reference-not-real')
);
echo json_encode(array(
    'is_error' => is_wp_error($result),
    'code' => is_wp_error($result) ? $result->get_error_code() : '',
    'post_count' => count($posts),
    'meta_count' => count($meta),
), JSON_THROW_ON_ERROR);
"""
        )
        self.assertTrue(result["is_error"])
        self.assertEqual("complete99_catalog_selection_unknown", result["code"])
        self.assertEqual(0, result["post_count"])
        self.assertEqual(0, result["meta_count"])

    def test_saved_dish_mapping_uses_only_explicit_canonical_metadata(self) -> None:
        source = GRAPH.read_text(encoding="utf-8")
        method = source.split(
            "private static function dish_id_for_saved_post", 1
        )[1].split("private static function unique_sorted_text", 1)[0]
        self.assertIn("self::META_DISH_ID", method)
        for forbidden in (
            "_complete99_seed_key",
            "_complete99_translation_key",
            "post_name",
            "sanitize_title",
            "sku",
        ):
            self.assertNotIn(forbidden, method)
        self.assertNotIn("Complete99_Catalog_Graph::boot();", source)
        self.assertIn("$product->set_status( 'draft' )", source)
        self.assertIn("$product->set_catalog_visibility( 'hidden' )", source)
        self.assertNotIn("$product->set_status( 'publish' )", source)

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
    public function is_purchasable() { return false; }
    public function save() {
        if (!$this->id) {
            $this->id = wp_insert_post(array(
                'post_type' => 'product',
                'post_status' => $this->status,
                'post_title' => $this->name,
                'post_name' => '',
                'post_excerpt' => '',
                'post_content' => $this->description,
            ), true);
        } else {
            wp_update_post(array(
                'ID' => $this->id,
                'post_type' => 'product',
                'post_status' => $this->status,
                'post_title' => $this->name,
                'post_name' => '',
                'post_excerpt' => '',
                'post_content' => $this->description,
            ), true);
        }
        $GLOBALS['products'][$this->id] = $this;
        return $this->id;
    }
}
function wc_get_product($product_id) {
    return isset($GLOBALS['products'][$product_id])
        ? $GLOBALS['products'][$product_id]
        : false;
}
"""
            if woo
            else ""
        )
        return (
            """
$posts = array();
$meta = array();
$products = array();
$next_id = 1;
function absint($value) { return abs((int) $value); }
function wp_slash($value) { return $value; }
function wp_cache_delete($key, $group = '') { return true; }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
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
        if (isset($GLOBALS['meta'][$post_id][$key])
            && $value === $GLOBALS['meta'][$post_id][$key]) {
            $ids[] = $post_id;
        }
    }
    sort($ids, SORT_NUMERIC);
    return array_slice($ids, 0, 2);
}
function update_post_meta($post_id, $key, $value) {
    if (!isset($GLOBALS['meta'][$post_id])) {
        $GLOBALS['meta'][$post_id] = array();
    }
    $GLOBALS['meta'][$post_id][$key] = $value;
    return true;
}
function get_post_meta($post_id, $key, $single = false) {
    return isset($GLOBALS['meta'][$post_id][$key])
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
"""
            + woo_stub
        )


if __name__ == "__main__":
    unittest.main()
