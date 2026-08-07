from __future__ import annotations

import json
import re
import subprocess
from collections.abc import Iterator, Mapping
from datetime import datetime
from decimal import Decimal, ROUND_HALF_UP
from pathlib import Path
from typing import Any

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
BOOTSTRAP = PLUGIN / "complete99-platform.php"
PLATFORM_CLASS = PLUGIN / "includes" / "class-complete99-platform.php"
REST_CLASS = PLUGIN / "includes" / "class-complete99-rest.php"
REVIEW_LAB = PLUGIN / "includes" / "class-complete99-review-lab.php"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
COMMERCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-commerce.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"
COMMERCE_DATA = PLUGIN / "data" / "culinary-commerce-pilot.php"
SYRIAN_COMMERCE_DATA = (
    PLUGIN / "data" / "culinary-commerce" / "syrian-market-tranche.php"
)

EXPECTED_SCHEMA = "complete99-culinary-commerce-registry/v2"
EXPECTED_VERSION = "culinary-commerce-2026.08.06.v6"
EXPECTED_COUNTS = {
    "countries": 5,
    "currencies": 5,
    "locales": 6,
    "tax_zones": 5,
    "markets": 6,
    "channels": 4,
    "sellers": 15,
    "brands": 16,
    "manufacturers": 16,
    "products": 25,
    "variants": 25,
    "skus": 25,
    "supplier_offers": 0,
    "evidence_artifacts": 26,
    "market_observations": 25,
    "channel_offers": 17,
    "landed_cost_scenarios": 0,
    "margin_scenarios": 0,
    "bundles": 8,
    "merchandising_edges": 14,
    "connector_profiles": 2,
    "integration_consumers": 1,
}
POS_ROUTE = "complete99/v1/integrations/pos/catalog"
MONEY_KEY = re.compile(
    r"(?:^|_)(?:price|amount|cost|revenue|contribution|subtotal|total)(?:$|_)"
)
NON_AMOUNT_KEYS = {
    "price_authority",
    "price_tier",
    "cost_lines",
    "variable_cost_lines",
    "landed_cost_scenario_id",
    "margin_scenario_id",
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


def _run_php(script: str) -> str:
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
    )
    return completed.stdout


def _run_php_stdin(script: str) -> str:
    completed = subprocess.run(
        ["php"],
        cwd=ROOT,
        check=True,
        input="<?php\n" + script,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
    )
    return completed.stdout


def _id_index(records: list[dict[str, Any]]) -> dict[str, dict[str, Any]]:
    return {record["id"]: record for record in records}


def _walk(value: Any, path: str = "registry") -> Iterator[tuple[str, str, Any]]:
    if isinstance(value, Mapping):
        for key, item in value.items():
            child = f"{path}.{key}"
            yield child, str(key), item
            yield from _walk(item, child)
    elif isinstance(value, list):
        for offset, item in enumerate(value):
            yield from _walk(item, f"{path}.{offset}")


def _error_code(result: dict[str, Any]) -> str:
    return str(result.get("code", ""))


def _error_path(result: dict[str, Any]) -> str:
    data = result.get("data")
    return str(data.get("path", "")) if isinstance(data, dict) else ""


@pytest.fixture(scope="module")
def commerce_payload() -> dict[str, Any]:
    plugin_path = _php_path(PLUGIN) + "/"
    science_class = _php_path(SCIENCE_CLASS)
    commerce_class = _php_path(COMMERCE_CLASS)
    commerce_data = _php_path(COMMERCE_DATA)
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
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
    public function get_error_message() {{ return $this->message; }}
    public function get_error_data() {{ return $this->data; }}
}}
class WP_REST_Server {{
    const READABLE = 'GET';
    const CREATABLE = 'POST';
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
$GLOBALS['complete99_test_routes'] = array();
function register_rest_route($namespace, $route, $arguments) {{
    $GLOBALS['complete99_test_routes'][$namespace . $route] = $arguments;
    return true;
}}
require '{science_class}';
require '{commerce_class}';
$registry = require '{commerce_data}';
$science = Complete99_Culinary_Science::registry();

function c99_validation_result($candidate) {{
    $result = Complete99_Culinary_Commerce::validate_registry($candidate);
    if (true === $result) {{
        return array('valid' => true, 'code' => '', 'data' => array());
    }}
    return array(
        'valid' => false,
        'code' => $result->get_error_code(),
        'message' => $result->get_error_message(),
        'data' => $result->get_error_data(),
    );
}}

$mutations = array();

$active_offer = $registry;
$seed_sku = $active_offer['skus'][0];
$seed_variant = null;
foreach ($active_offer['variants'] as $variant) {{
    if ($variant['id'] === $seed_sku['variant_id']) {{ $seed_variant = $variant; break; }}
}}
$seed_product = null;
foreach ($active_offer['products'] as $product) {{
    if ($seed_variant && $product['id'] === $seed_variant['product_id']) {{ $seed_product = $product; break; }}
}}
$active_offer['channel_offers'][] = array(
    'id' => 'offer-mutation-missing-gates',
    'sku_id' => $seed_sku['id'],
    'market_id' => 'market-il-launch',
    'channel_id' => 'channel-myshop-pos-il',
    'customer_segment' => $active_offer['controlled_vocabulary']['customer_segments'][0],
    'price_tier' => 'mutation-only',
    'price_basis' => 'gross_tax_inclusive',
    'currency_id' => 'currency-ils',
    'price_minor' => 100,
    'tax_state' => 'unknown',
    'minimum_quantity' => '1',
    'stock_policy' => $seed_sku['inventory_policy'],
    'fulfillment_policy' => 'mutation-only',
    'state' => 'active',
    'approved_at' => '',
    'approved_by' => '',
    'woo_product_code' => '',
    'landed_cost_scenario_id' => '',
    'margin_scenario_id' => '',
    'evidence_artifact_ids' => array(),
    'valid_from' => '',
    'valid_until' => '',
    'kiosk_projection' => array(
        'category' => array('he' => 'בדיקה', 'en' => 'Test'),
        'subcategory' => array('he' => 'בדיקה', 'en' => 'Test'),
        'image_url' => '',
        'food_tags' => array(),
        'allergens' => array(),
        'modifiers' => array(),
        'availability' => 'held',
        'version' => 1,
    ),
);
$mutations['active_offer_without_gates'] = c99_validation_result($active_offer);

$duplicate_code = $registry;
$duplicate_code['skus'][1]['internal_code'] = $duplicate_code['skus'][0]['internal_code'];
$mutations['duplicate_internal_code'] = c99_validation_result($duplicate_code);

$eligible_without_snapshot = $registry;
$eligible_without_snapshot['evidence_artifacts'][0]['offer_approval_eligible'] = true;
$eligible_without_snapshot['evidence_artifacts'][0]['retention_state'] = 'retained';
$eligible_without_snapshot['evidence_artifacts'][0]['snapshot_digest'] = '';
$mutations['eligible_evidence_without_snapshot'] = c99_validation_result($eligible_without_snapshot);

$subject_mismatch = $registry;
$science_by_id = array();
foreach ($science['entities'] as $entity) {{ $science_by_id[$entity['id']] = $entity; }}
$variants_by_id = array();
foreach ($registry['variants'] as $variant) {{ $variants_by_id[$variant['id']] = $variant; }}
$products_by_id = array();
foreach ($registry['products'] as $product) {{ $products_by_id[$product['id']] = $product; }}
$skus_by_id = array();
foreach ($registry['skus'] as $sku) {{ $skus_by_id[$sku['id']] = $sku; }}
$subject_mutation_prepared = false;
foreach ($subject_mismatch['market_observations'] as $offset => $observation) {{
    $sku = $skus_by_id[$observation['sku_id']];
    $variant = $variants_by_id[$sku['variant_id']];
    $product = $products_by_id[$variant['product_id']];
    foreach ($science['entities'] as $candidate_source) {{
        if (!in_array($candidate_source['type'], array('retail_listing', 'market_observation'), true)) {{ continue; }}
        $targets = array($candidate_source['parent_id']);
        foreach ($candidate_source['relations'] as $relation) {{ $targets[] = $relation['target_id']; }}
        if (!in_array($product['knowledge_entity_id'], $targets, true)) {{
            $subject_mismatch['market_observations'][$offset]['source_entity_id'] = $candidate_source['id'];
            $subject_mutation_prepared = true;
            break 2;
        }}
    }}
}}
$mutations['observation_subject_mismatch'] = $subject_mutation_prepared
    ? c99_validation_result($subject_mismatch)
    : array('valid' => true, 'code' => 'mutation_not_prepared', 'data' => array());

Complete99_Culinary_Commerce::register_routes();
$baseline = c99_validation_result($registry);
$status = Complete99_Culinary_Commerce::status();
$route = $GLOBALS['complete99_test_routes']['{POS_ROUTE}'];

echo json_encode(array(
    'baseline' => $baseline,
    'status' => $status,
    'registry' => $registry,
    'science' => $science,
    'mutations' => $mutations,
    'route' => array(
        'methods' => $route['methods'],
        'permission_callback' => $route['permission_callback'],
        'callback' => $route['callback'],
    ),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    return json.loads(_run_php(script))


@pytest.fixture(scope="module")
def approved_chain_payload() -> dict[str, Any]:
    script = r"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '__PLUGIN_PATH__/');

class WP_Error {
    public $code;
    public $message;
    public $data;
    public function __construct($code, $message, $data = array()) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
class WP_REST_Request {
    private $body;
    public function __construct($body) { $this->body = $body; }
    public function get_body() { return $this->body; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function rest_ensure_response($value) { return $value; }

require '__SCIENCE_CLASS__';
require '__COMMERCE_CLASS__';
$registry = require '__COMMERCE_DATA__';

function c99_chain_validation_result($candidate) {
    $result = Complete99_Culinary_Commerce::validate_registry($candidate);
    if (true === $result) {
        return array('valid' => true, 'code' => '', 'data' => array());
    }
    return array(
        'valid' => false,
        'code' => $result->get_error_code(),
        'message' => $result->get_error_message(),
        'data' => $result->get_error_data(),
    );
}

function c99_record_offset($records, $id) {
    foreach ($records as $offset => $record) {
        if ($record['id'] === $id) { return $offset; }
    }
    throw new RuntimeException('test_fixture_missing.' . $id);
}

function c99_set_registry_cache($registry) {
    $property = new ReflectionProperty('Complete99_Culinary_Commerce', 'registry_cache');
    $property->setAccessible(true);
    $property->setValue(null, $registry);
}

function c99_mutation_result($approved, $mutator) {
    $candidate = $approved;
    $mutator($candidate);
    return c99_chain_validation_result($candidate);
}

function c99_build_approved_chain($registry) {
    $product_id = 'product-rishiri-kombu-100g';
    $variant_id = 'variant-rishiri-kombu-100g';
    $sku_id = 'sku-rishiri-kombu-100g';
    $observation_id = 'observation-rishiri-kombu-100g-20260806';
    $artifact_id = 'evidence-rishiri-kombu-100g-20260806';
    $seller_id = 'seller-rishiri-kombu-direct';
    $market_id = 'market-il-launch';
    $source_market_id = 'market-jp-source';
    $tax_zone_id = 'tax-zone-il-research';
    $connector_id = 'connector-myshop-contract-pending';
    $channel_id = 'channel-myshop-pos-il';
    $consumer_id = 'consumer-myshop-pos-adapter';
    $supplier_offer_id = 'supplier-offer-approved-chain';
    $landed_id = 'landed-cost-approved-chain';
    $margin_id = 'margin-approved-chain';
    $offer_id = 'channel-offer-approved-chain';
    $woo_code = 'c99-rishiri-kombu-100g';

    $product_offset = c99_record_offset($registry['products'], $product_id);
    $variant_offset = c99_record_offset($registry['variants'], $variant_id);
    $sku_offset = c99_record_offset($registry['skus'], $sku_id);
    $observation_offset = c99_record_offset($registry['market_observations'], $observation_id);
    $artifact_offset = c99_record_offset($registry['evidence_artifacts'], $artifact_id);
    $seller_offset = c99_record_offset($registry['sellers'], $seller_id);
    $market_offset = c99_record_offset($registry['markets'], $market_id);
    $tax_zone_offset = c99_record_offset($registry['tax_zones'], $tax_zone_id);
    $connector_offset = c99_record_offset($registry['connector_profiles'], $connector_id);
    $channel_offset = c99_record_offset($registry['channels'], $channel_id);
    $consumer_offset = c99_record_offset($registry['integration_consumers'], $consumer_id);

    $registry['products'][$product_offset]['state'] = 'active';
    $registry['variants'][$variant_offset]['state'] = 'active';
    $registry['skus'][$sku_offset]['state'] = 'active';
    $registry['skus'][$sku_offset]['woo_product_code'] = $woo_code;
    $registry['skus'][$sku_offset]['inventory_policy'] = 'woocommerce_managed';
    $registry['skus'][$sku_offset]['compliance_state'] = 'cleared';
    $registry['market_observations'][$observation_offset]['state'] = 'recorded';

    $registry['evidence_artifacts'][$artifact_offset]['snapshot_digest'] = 'sha256:' . str_repeat('a', 64);
    $registry['evidence_artifacts'][$artifact_offset]['snapshot_uri'] = 'test-fixture://retained/source-offer';
    $registry['evidence_artifacts'][$artifact_offset]['verification_state'] = 'snapshot_retained';
    $registry['evidence_artifacts'][$artifact_offset]['retention_state'] = 'retained';
    $registry['evidence_artifacts'][$artifact_offset]['offer_approval_eligible'] = true;

    $registry['sellers'][$seller_offset]['legal_identity_state'] = 'legally_verified';
    $registry['markets'][$market_offset]['seller_of_record_id'] = $seller_id;
    $registry['markets'][$market_offset]['state'] = 'active';
    $registry['tax_zones'][$tax_zone_offset]['state'] = 'approved';
    $registry['connector_profiles'][$connector_offset]['binding_state'] = 'bound';
    $registry['connector_profiles'][$connector_offset]['transport_mode'] = 'api';
    $registry['channels'][$channel_offset]['state'] = 'active';
    $registry['integration_consumers'][$consumer_offset]['state'] = 'active';

    $valid_from = gmdate('Y-m-d', time() - (30 * 86400));
    $valid_until = gmdate('Y-m-d', time() + (365 * 86400));

    $registry['supplier_offers'][] = array(
        'id' => $supplier_offer_id,
        'sku_id' => $sku_id,
        'seller_id' => $seller_id,
        'market_id' => $source_market_id,
        'currency_id' => 'currency-jpy',
        'state' => 'approved',
        'price_minor' => 1165,
        'minimum_quantity' => '1',
        'incoterm' => 'EXW',
        'lead_time_days' => 5,
        'valid_from' => $valid_from,
        'valid_until' => $valid_until,
        'evidence_artifact_ids' => array($artifact_id),
    );

    $registry['landed_cost_scenarios'][] = array(
        'id' => $landed_id,
        'sku_id' => $sku_id,
        'destination_market_id' => $market_id,
        'source_observation_id' => $observation_id,
        'supplier_offer_id' => $supplier_offer_id,
        'scenario_state' => 'approved',
        'incoterm' => 'EXW',
        'order_quantity' => '1',
        'sellable_units' => '1',
        'source_subtotal_minor' => 1165,
        'converted_source_cost_minor' => 2796,
        'fx' => array(
            'pair' => 'JPY/ILS',
            'source_currency_id' => 'currency-jpy',
            'destination_currency_id' => 'currency-ils',
            'direction' => 'source_to_destination',
            'rounding' => 'half_up_minor',
            'rate_decimal' => '0.024000',
            'source_url' => 'https://www.boi.org.il/en/economic-roles/financial-markets/exchange-rates/',
            'observed_at' => '2026-08-06T00:50:31+03:00',
        ),
        'cost_lines' => array(
            array(
                'code' => 'source_goods',
                'amount_minor' => 2796,
                'currency_id' => 'currency-ils',
                'basis' => 'JPY 1,165 converted at 0.024 ILS per JPY.',
                'evidence_artifact_id' => $artifact_id,
                'status' => 'verified',
                'tax_recoverable' => false,
            ),
            array(
                'code' => 'freight',
                'amount_minor' => 1200,
                'currency_id' => 'currency-ils',
                'basis' => 'Fixture freight evidence retained for contract testing.',
                'evidence_artifact_id' => $artifact_id,
                'status' => 'verified',
                'tax_recoverable' => false,
            ),
            array(
                'code' => 'handling',
                'amount_minor' => 500,
                'currency_id' => 'currency-ils',
                'basis' => 'Fixture handling evidence retained for contract testing.',
                'evidence_artifact_id' => $artifact_id,
                'status' => 'verified',
                'tax_recoverable' => false,
            ),
        ),
        'shrinkage_rate_decimal' => '0',
        'landed_cost_minor' => 4496,
        'currency_id' => 'currency-ils',
        'calculation_method' => 'sum_cost_lines_divided_by_sellable_units_ceiling_minor',
        'formula' => '(2,796 + 1,200 + 500) / 1 = 4,496 ILS minor units.',
        'version' => 'approved-chain-v1',
        'review_at' => $valid_until,
    );

    $registry['margin_scenarios'][] = array(
        'id' => $margin_id,
        'channel_offer_id' => $offer_id,
        'landed_cost_scenario_id' => $landed_id,
        'scenario_state' => 'approved',
        'currency_id' => 'currency-ils',
        'net_revenue_minor' => 10000,
        'landed_cost_minor' => 4496,
        'revenue_adjustment_lines' => array(
            array(
                'code' => 'tax',
                'amount_minor_signed' => -1700,
                'currency_id' => 'currency-ils',
                'basis' => 'Gross price 11,700 less a verified 1,700 tax adjustment.',
                'evidence_artifact_id' => $artifact_id,
                'status' => 'verified',
            ),
        ),
        'variable_cost_lines' => array(
            array(
                'code' => 'payment_fee',
                'amount_minor' => 300,
                'currency_id' => 'currency-ils',
                'basis' => 'Verified fixture variable channel cost.',
                'evidence_artifact_id' => $artifact_id,
                'status' => 'verified',
                'tax_recoverable' => false,
            ),
        ),
        'contribution_minor' => 5204,
        'margin_rate_decimal' => '0.520400',
        'break_even_units' => 1,
        'formula' => '(10,000 - 4,496 - 300) / 10,000 = 0.520400.',
        'evidence_artifact_ids' => array($artifact_id),
        'version' => 'approved-chain-v1',
        'review_at' => $valid_until,
    );

    array_unshift($registry['channel_offers'], array(
        'id' => $offer_id,
        'sku_id' => $sku_id,
        'market_id' => $market_id,
        'channel_id' => $channel_id,
        'customer_segment' => 'consumer',
        'price_tier' => 'standard',
        'price_basis' => 'gross_tax_inclusive',
        'currency_id' => 'currency-ils',
        'price_minor' => 11700,
        'tax_state' => 'approved',
        'minimum_quantity' => '1',
        'stock_policy' => 'woocommerce_managed',
        'fulfillment_policy' => 'woocommerce_order_flow',
        'state' => 'active',
        'approved_at' => gmdate('c'),
        'approved_by' => 'approved-chain-fixture',
        'woo_product_code' => $woo_code,
        'landed_cost_scenario_id' => $landed_id,
        'margin_scenario_id' => $margin_id,
        'evidence_artifact_ids' => array($artifact_id),
        'valid_from' => $valid_from,
        'valid_until' => $valid_until,
        'kiosk_projection' => array(
            'category' => array('he' => 'Test category', 'en' => 'Test category'),
            'subcategory' => array('he' => 'Test subcategory', 'en' => 'Test subcategory'),
            'image_url' => '',
            'food_tags' => array('kombu'),
            'allergens' => array(),
            'modifiers' => array(
                array(
                    'code' => 'standard',
                    'name' => array('he' => 'Standard', 'en' => 'Standard'),
                    'price_delta_minor' => 0,
                ),
            ),
            'availability' => 'in_stock',
            'version' => 1,
        ),
    ));
    return $registry;
}

$approved = c99_build_approved_chain($registry);
$approved_validation = c99_chain_validation_result($approved);
c99_set_registry_cache($approved);
$approved_status = Complete99_Culinary_Commerce::status();

$mutations = array();
$mutations['bound_connector_unbound_transport'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $offset = c99_record_offset($candidate['connector_profiles'], 'connector-myshop-contract-pending');
        $candidate['connector_profiles'][$offset]['transport_mode'] = 'unbound';
    }
);
$mutations['invalidated_observation'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $offset = c99_record_offset($candidate['market_observations'], 'observation-rishiri-kombu-100g-20260806');
        $candidate['market_observations'][$offset]['state'] = 'invalidated';
    }
);
$mutations['quote_pending_supplier_offer'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['supplier_offers'][0]['state'] = 'quote_pending'; }
);
$mutations['blank_cross_currency_fx'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        foreach ($candidate['landed_cost_scenarios'][0]['fx'] as $key => $value) {
            $candidate['landed_cost_scenarios'][0]['fx'][$key] = '';
        }
    }
);
$mutations['wrong_cross_currency_fx'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $candidate['landed_cost_scenarios'][0]['fx']['source_currency_id'] = 'currency-ils';
        $candidate['landed_cost_scenarios'][0]['fx']['destination_currency_id'] = 'currency-jpy';
    }
);
$mutations['unverified_cost_line'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['landed_cost_scenarios'][0]['cost_lines'][1]['status'] = 'estimated'; }
);
$mutations['unevidenced_cost_line'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['landed_cost_scenarios'][0]['cost_lines'][1]['evidence_artifact_id'] = ''; }
);
$mutations['landed_cost_machine_mismatch'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['landed_cost_scenarios'][0]['landed_cost_minor']++; }
);
$mutations['margin_landed_amount_mismatch'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['margin_scenarios'][0]['landed_cost_minor']++; }
);
$mutations['margin_currency_mismatch'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['margin_scenarios'][0]['currency_id'] = 'currency-usd'; }
);
$mutations['margin_rate_mismatch'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['margin_scenarios'][0]['margin_rate_decimal'] = '0.500000'; }
);
$mutations['different_landed_scenario_links'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $secondary = $candidate['landed_cost_scenarios'][0];
        $secondary['id'] = 'landed-cost-approved-chain-secondary';
        $candidate['landed_cost_scenarios'][] = $secondary;
        $candidate['margin_scenarios'][0]['landed_cost_scenario_id'] = $secondary['id'];
    }
);
$same_currency_wrong_destination = $approved;
$launch_market_offset = c99_record_offset($same_currency_wrong_destination['markets'], 'market-il-launch');
$wrong_market = $same_currency_wrong_destination['markets'][$launch_market_offset];
$wrong_market['id'] = 'market-il-wrong-destination';
$wrong_market['label'] = array('he' => 'Wrong destination fixture', 'en' => 'Wrong destination fixture');
$same_currency_wrong_destination['markets'][] = $wrong_market;
$same_currency_wrong_destination['landed_cost_scenarios'][0]['destination_market_id'] = $wrong_market['id'];
$mutations['same_currency_wrong_destination'] = c99_chain_validation_result($same_currency_wrong_destination);
$wrong_destination_context = array(
    'offer_market_id' => $same_currency_wrong_destination['channel_offers'][0]['market_id'],
    'landed_destination_market_id' => $same_currency_wrong_destination['landed_cost_scenarios'][0]['destination_market_id'],
    'offer_currency_id' => $same_currency_wrong_destination['channel_offers'][0]['currency_id'],
    'destination_currency_id' => $wrong_market['currency_id'],
);
$mutations['unexplained_gross_to_net_gap'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['margin_scenarios'][0]['revenue_adjustment_lines'][0]['amount_minor_signed'] = -1600; }
);
$mutations['reversed_validity_window'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $candidate['channel_offers'][0]['valid_from'] = gmdate('Y-m-d', time() + 86400);
        $candidate['channel_offers'][0]['valid_until'] = gmdate('Y-m-d', time() - 86400);
    }
);
$mutations['negative_modifier_effective_price'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['channel_offers'][0]['kiosk_projection']['modifiers'][0]['price_delta_minor'] = -11701; }
);
$mutations['inactive_channel_gate'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $offset = c99_record_offset($candidate['channels'], 'channel-myshop-pos-il');
        $candidate['channels'][$offset]['state'] = 'configured_no_offers';
    }
);
$mutations['inactive_market_gate'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $offset = c99_record_offset($candidate['markets'], 'market-il-launch');
        $candidate['markets'][$offset]['state'] = 'launch_preparation';
    }
);
$mutations['inactive_parent_gate'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $product = c99_record_offset($candidate['products'], 'product-rishiri-kombu-100g');
        $variant = c99_record_offset($candidate['variants'], 'variant-rishiri-kombu-100g');
        $sku = c99_record_offset($candidate['skus'], 'sku-rishiri-kombu-100g');
        $candidate['products'][$product]['state'] = 'verified_product';
        $candidate['variants'][$variant]['state'] = 'verified_variant';
        $candidate['skus'][$sku]['state'] = 'verified_sku';
    }
);
$mutations['uncleared_compliance_gate'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $sku = c99_record_offset($candidate['skus'], 'sku-rishiri-kombu-100g');
        $candidate['skus'][$sku]['state'] = 'verified_sku';
        $candidate['skus'][$sku]['compliance_state'] = 'import_label_review_required';
    }
);
$mutations['sku_inventory_gate'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $sku = c99_record_offset($candidate['skus'], 'sku-rishiri-kombu-100g');
        $candidate['skus'][$sku]['inventory_policy'] = 'research_only';
    }
);
$mutations['offer_inventory_gate'] = c99_mutation_result(
    $approved,
    function (&$candidate) { $candidate['channel_offers'][0]['stock_policy'] = 'binary_availability'; }
);
$mutations['market_tax_gate'] = c99_mutation_result(
    $approved,
    function (&$candidate) {
        $offset = c99_record_offset($candidate['tax_zones'], 'tax-zone-il-research');
        $candidate['tax_zones'][$offset]['state'] = 'review_required';
    }
);

$expired = $approved;
$expired['channel_offers'][0]['valid_from'] = gmdate('Y-m-d', time() - (30 * 86400));
$expired['channel_offers'][0]['valid_until'] = gmdate('Y-m-d', time() - 86400);
$expired_validation = c99_chain_validation_result($expired);
c99_set_registry_cache($expired);
$expired_status = Complete99_Culinary_Commerce::status();
$expired_pos = Complete99_Culinary_Commerce::rest_pos_catalog(
    new WP_REST_Request(
        json_encode(
            array(
                'schema' => 'complete99-pos-catalog-request/v1',
                'consumer_id' => 'consumer-myshop-pos-adapter',
                'market_id' => 'market-il-launch',
                'channel_id' => 'channel-myshop-pos-il',
                'locale' => 'locale-he-il',
                'cursor' => '',
                'limit' => 25,
            )
        )
    )
);

echo json_encode(
    array(
        'approved' => $approved_validation,
        'approved_status' => $approved_status,
        'chain' => array(
            'product' => $approved['products'][c99_record_offset($approved['products'], 'product-rishiri-kombu-100g')],
            'variant' => $approved['variants'][c99_record_offset($approved['variants'], 'variant-rishiri-kombu-100g')],
            'sku' => $approved['skus'][c99_record_offset($approved['skus'], 'sku-rishiri-kombu-100g')],
            'supplier_offer' => $approved['supplier_offers'][0],
            'landed_cost' => $approved['landed_cost_scenarios'][0],
            'margin' => $approved['margin_scenarios'][0],
            'channel_offer' => $approved['channel_offers'][0],
            'connector' => $approved['connector_profiles'][c99_record_offset($approved['connector_profiles'], 'connector-myshop-contract-pending')],
            'consumer' => $approved['integration_consumers'][c99_record_offset($approved['integration_consumers'], 'consumer-myshop-pos-adapter')],
        ),
        'mutations' => $mutations,
        'wrong_destination_context' => $wrong_destination_context,
        'expired' => array(
            'validation' => $expired_validation,
            'status' => $expired_status,
            'pos' => $expired_pos,
        ),
    ),
    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
"""
    replacements = {
        "__PLUGIN_PATH__": _php_path(PLUGIN),
        "__SCIENCE_CLASS__": _php_path(SCIENCE_CLASS),
        "__COMMERCE_CLASS__": _php_path(COMMERCE_CLASS),
        "__COMMERCE_DATA__": _php_path(COMMERCE_DATA),
    }
    for marker, value in replacements.items():
        script = script.replace(marker, value)
    return json.loads(_run_php_stdin(script))


@pytest.mark.parametrize(
    "php_file",
    [
        BOOTSTRAP,
        PLATFORM_CLASS,
        REST_CLASS,
        REVIEW_LAB,
        SCIENCE_CLASS,
        COMMERCE_CLASS,
        SCIENCE_DATA,
        COMMERCE_DATA,
        SYRIAN_COMMERCE_DATA,
    ],
)
def test_culinary_commerce_php_files_lint_cleanly(php_file: Path) -> None:
    completed = subprocess.run(
        ["php", "-l", str(php_file)],
        cwd=ROOT,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=30,
    )
    assert completed.returncode == 0, completed.stdout + completed.stderr
    assert "No syntax errors detected" in completed.stdout


def test_registry_validates_against_the_real_science_registry(
    commerce_payload: dict[str, Any],
) -> None:
    assert commerce_payload["baseline"]["valid"], commerce_payload["baseline"]
    registry = commerce_payload["registry"]
    science = commerce_payload["science"]
    assert registry["schema"] == EXPECTED_SCHEMA
    assert registry["version"] == EXPECTED_VERSION
    assert registry["knowledge_registry_version"] == science["version"]
    assert commerce_payload["status"]["ready"] is True


def test_v2_controlled_vocabularies_and_integration_consumers_are_explicit(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    vocabulary = registry["controlled_vocabulary"]
    assert set(vocabulary["channel_states"]) == {
        "configured_no_offers",
        "contract_required",
        "active",
        "disabled",
    }
    assert set(vocabulary["cost_line_states"]) == {
        "estimated",
        "source_observed",
        "supplier_quoted",
        "verified",
    }
    assert "cleared" in vocabulary["sku_compliance_states"]
    assert set(vocabulary["price_bases"]) == {
        "gross_tax_inclusive",
        "tax_exclusive",
        "net_revenue",
    }
    assert set(vocabulary["integration_consumer_states"]) == {
        "contract_required",
        "active",
        "disabled",
    }
    assert len(registry["integration_consumers"]) == 1


def test_pilot_counts_are_stable_and_every_sale_offer_is_closed(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    actual = {key: len(registry[key]) for key in EXPECTED_COUNTS}
    assert actual == EXPECTED_COUNTS
    assert commerce_payload["status"]["products"] == EXPECTED_COUNTS["products"]
    assert commerce_payload["status"]["skus"] == EXPECTED_COUNTS["skus"]
    assert commerce_payload["status"]["observations"] == EXPECTED_COUNTS[
        "market_observations"
    ]
    assert commerce_payload["status"]["active_offers"] == 0
    assert not [
        offer
        for offer in registry["channel_offers"]
        if offer["state"] in {"approved", "active"}
    ]
    assert registry["supplier_offers"] == []
    assert registry["landed_cost_scenarios"] == []
    assert registry["margin_scenarios"] == []


def test_private_draft_prices_cover_every_non_live_commerce_candidate(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    expected = {
        "sku-honkarebushi-belly-200g": 21900,
        "sku-fukumitsuya-hon-mirin-3y-720ml": 24900,
        "sku-fukumitsuya-hon-mirin-10y-720ml": 34900,
        "sku-kito-yuzu-juice-720ml": 19900,
        "sku-umezawa-hangiri-36cm": 64900,
        "sku-maruyama-gokujo-kontobi-nori-5-sheets": 9900,
        "sku-tajima-red-sushi-vinegar-360ml": 11900,
        "sku-minamigura-gin-warabeuta-tamari-200ml": 15900,
        "sku-sugimoto-organic-dried-shiitake-70g": 14900,
        "sku-yubaya-kyoto-dried-yuba-100g": 8900,
        "sku-ohsawa-organic-kudzu-starch-150g": 14900,
        "sku-yawataya-isogoro-sansho-12g": 16900,
        "sku-marukyu-koyamaen-tenju-matcha-20g": 74900,
        "sku-yamaco-bamboo-makisu-27cm": 12900,
        "sku-sakai-takayuki-ginsan-yanagiba-270mm": 179900,
        "sku-nagatanien-kamado-san-3-cup": 119900,
        "sku-kubo-komakichi-kazuho-chasen": 24900,
    }
    offers = {offer["sku_id"]: offer for offer in registry["channel_offers"]}

    assert set(offers) == set(expected)
    for sku_id, price_minor in expected.items():
        offer = offers[sku_id]
        assert offer["price_minor"] == price_minor
        assert offer["state"] == "draft"
        assert offer["market_id"] == "market-il-launch"
        assert offer["channel_id"] == "channel-woo-web-il"
        assert offer["currency_id"] == "currency-ils"
        assert offer["tax_state"] == "review_required"
        assert offer["stock_policy"] == "research_only"
        assert offer["woo_product_code"] == ""
        assert offer["landed_cost_scenario_id"] == ""
        assert offer["margin_scenario_id"] == ""
        assert offer["evidence_artifact_ids"]
        assert offer["kiosk_projection"]["availability"] == "held"


def test_public_market_projection_uses_an_explicit_legacy_allowlist(
    commerce_payload: dict[str, Any],
) -> None:
    variants = _id_index(commerce_payload["registry"]["variants"])
    expected_public = {
        "variant-rishiri-kombu-100g",
        "variant-honkarebushi-belly-200g",
        "variant-fukumitsuya-hon-mirin-3y-720ml",
        "variant-fukumitsuya-hon-mirin-10y-720ml",
        "variant-yamaroku-tsurubishio-500ml",
        "variant-fresh-japanese-wasabi-250g",
        "variant-kito-yuzu-juice-100ml",
        "variant-kito-yuzu-juice-720ml",
        "variant-hagane-zame-large",
    }
    actual_public = {
        variant_id
        for variant_id, variant in variants.items()
        if variant["attributes"].get("public_market_projection") == "public"
    }

    assert actual_public == expected_public
    assert variants["variant-umezawa-hangiri-36cm"]["attributes"][
        "public_market_projection"
    ] == "held"
    for variant in variants.values():
        assert variant["attributes"]["public_market_projection"] in {
            "public",
            "held",
        }


def test_syrian_retail_tranche_is_private_research_without_commercial_wiring(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    products = _id_index(registry["products"])
    variants = _id_index(registry["variants"])
    skus = _id_index(registry["skus"])
    observations = _id_index(registry["market_observations"])
    artifacts = _id_index(registry["evidence_artifacts"])

    expected = {
        "sugat-freekeh-500g": {
            "knowledge_entity_id": "ingredient-syrian-freekeh",
            "amount_minor": 1090,
            "normalized_amount_minor": 2180,
            "normalized_unit_code": "kg",
            "source_url": "https://www.bigdabach.co.il/?catalogProduct=6279611",
        },
        "keter-harimon-pomegranate-concentrate-250ml": {
            "knowledge_entity_id": "ingredient-syrian-pomegranate-molasses",
            "amount_minor": 2990,
            "normalized_amount_minor": 11960,
            "normalized_unit_code": "l",
            "source_url": (
                "https://www.tamar-hst.co.il/product-details/209856/"
                "%D7%A8%D7%9B%D7%96_%D7%A8%D7%99%D7%9E%D7%95%D7%9F"
            ),
        },
        "tamar-bakfar-pure-ground-sumac-100g": {
            "knowledge_entity_id": "ingredient-syrian-sumac",
            "amount_minor": 1100,
            "normalized_amount_minor": 11000,
            "normalized_unit_code": "kg",
            "source_url": (
                "https://tamarbakfar.co.il/product/"
                "%D7%A1%D7%95%D7%9E%D7%A7-%D7%98%D7%97%D7%95%D7%9F-"
                "%D7%98%D7%94%D7%95%D7%A8/"
            ),
        },
    }
    syrian_sku_ids = {f"sku-{key}" for key in expected}
    assert {
        sku_id
        for sku_id, sku in skus.items()
        if sku["internal_code"].startswith("C99-SY-")
    } == syrian_sku_ids

    for key, price in expected.items():
        product = products[f"product-{key}"]
        variant = variants[f"variant-{key}"]
        sku = skus[f"sku-{key}"]
        observation = observations[f"observation-{key}-20260806"]
        artifact = artifacts[f"evidence-{key}-20260806"]

        assert product["state"] == "research_candidate"
        assert product["knowledge_entity_id"] == price["knowledge_entity_id"]
        assert variant["state"] == "research_candidate"
        assert variant["attributes"]["planning_stock_quantity"] == "0"
        assert variant["attributes"]["public_market_projection"] == "held"
        assert sku["state"] == "research_candidate"
        assert sku["woo_product_code"] == ""
        assert sku["inventory_policy"] == "research_only"
        assert sku["internal_code"].startswith("C99-SY-")
        assert observation["currency_id"] == "currency-ils"
        assert observation["amount_minor"] == price["amount_minor"]
        assert observation["normalization"]["normalized_amount_minor"] == price[
            "normalized_amount_minor"
        ]
        assert observation["normalization"]["normalized_unit_code"] == price[
            "normalized_unit_code"
        ]
        assert observation["observed_at"].startswith("2026-08-06T")
        assert artifact["source_url"] == price["source_url"]
        assert artifact["captured_at"].startswith("2026-08-06T")
        assert artifact["verification_state"] == "source_reviewed"
        assert artifact["retention_state"] == "source_pointer_only"
        assert artifact["offer_approval_eligible"] is False

    assert not {
        offer["sku_id"] for offer in registry["channel_offers"]
    } & syrian_sku_ids
    assert not {
        offer["sku_id"] for offer in registry["supplier_offers"]
    } & syrian_sku_ids
    assert not {
        scenario["sku_id"] for scenario in registry["landed_cost_scenarios"]
    } & syrian_sku_ids
    assert not {
        component["sku_id"]
        for bundle in registry["bundles"]
        for component in bundle["components"]
    } & syrian_sku_ids
    assert not {
        sku_id
        for edge in registry["merchandising_edges"]
        for sku_id in (edge["source_sku_id"], edge["target_sku_id"])
    } & syrian_sku_ids

    freekeh_observation = observations[
        "observation-sugat-freekeh-500g-20260806"
    ]
    science_entities = _id_index(commerce_payload["science"]["entities"])
    freekeh_science = science_entities[
        "listing-sugat-freekeh-500g-big-dabach-20260806"
    ]
    science_availability = freekeh_science["facts"][0]["measurement"][
        "line_items"
    ][0]["availability"]
    assert (
        freekeh_observation["availability_state"]
        == science_availability
        == "indexed_price_no_live_availability"
    )


def test_inactive_tamar_bakfar_sumac_evidence_cannot_look_current(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    observations = _id_index(registry["market_observations"])
    artifacts = _id_index(registry["evidence_artifacts"])
    variants = _id_index(registry["variants"])
    sellers = _id_index(registry["sellers"])
    key = "tamar-bakfar-pure-ground-sumac-100g"

    observation = observations[f"observation-{key}-20260806"]
    artifact = artifacts[f"evidence-{key}-20260806"]
    attributes = variants[f"variant-{key}"]["attributes"]
    seller = sellers["seller-tamar-bakfar-historical"]

    assert observation["availability_state"] == (
        "historical_index_only_domain_inactive"
    )
    assert observation["comparability"] == "non_comparable"
    assert observation["availability_state"] not in {
        "in_stock",
        "listed_for_sale",
        "add_to_cart_visible",
        "add_to_cart_available",
    }
    assert attributes["current_availability_claim"] == "none"
    assert attributes["current_price_claim"] == "none"
    assert attributes["public_market_projection"] == "held"
    assert "parked-domain" in attributes["domain_state_at_retrieval"]
    assert seller["status"] == "historical_source_inactive"
    assert artifact["capture_method"] == (
        "historical-search-index-plus-live-redirect-review"
    )
    assert artifact["source_url"] == (
        "https://tamarbakfar.co.il/product/"
        "%D7%A1%D7%95%D7%9E%D7%A7-%D7%98%D7%97%D7%95%D7%9F-"
        "%D7%98%D7%94%D7%95%D7%A8/"
    )
    assert "no current seller availability" in artifact["claim_locator"]
    assert artifact["offer_approval_eligible"] is False


def test_premium_tranche_keeps_stock_zero_gates_and_compliance_in_private_variants(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    variants = _id_index(registry["variants"])
    products = _id_index(registry["products"])
    skus = _id_index(registry["skus"])
    new_product_ids = {
        "product-maruyama-gokujo-kontobi-nori-5-sheets",
        "product-tajima-red-sushi-vinegar-360ml",
        "product-minamigura-gin-warabeuta-tamari-200ml",
        "product-sugimoto-organic-dried-shiitake-70g",
        "product-yubaya-kyoto-dried-yuba-100g",
        "product-ohsawa-organic-kudzu-starch-150g",
        "product-yawataya-isogoro-sansho-12g",
        "product-marukyu-koyamaen-tenju-matcha-20g",
        "product-yamaco-bamboo-makisu-27cm",
        "product-sakai-takayuki-ginsan-yanagiba-270mm",
        "product-nagatanien-kamado-san-3-cup",
        "product-kubo-komakichi-kazuho-chasen",
    }
    expected_fx_arithmetic = {
        "product-maruyama-gokujo-kontobi-nori-5-sheets": "ILS 25.77 source equivalent",
        "product-tajima-red-sushi-vinegar-360ml": "ILS 14.53 source equivalent",
        "product-minamigura-gin-warabeuta-tamari-200ml": "ILS 51.07 source equivalent",
        "product-sugimoto-organic-dried-shiitake-70g": "ILS 43.06 source equivalent",
        "product-yubaya-kyoto-dried-yuba-100g": "ILS 20.62 source equivalent",
        "product-ohsawa-organic-kudzu-starch-150g": "ILS 45.13 source equivalent",
        "product-yawataya-isogoro-sansho-12g": "ILS 66.26 source equivalent",
        "product-marukyu-koyamaen-tenju-matcha-20g": "ILS 412.30 source equivalent",
        "product-yamaco-bamboo-makisu-27cm": "ILS 59.41 source equivalent",
        "product-sakai-takayuki-ginsan-yanagiba-270mm": "ILS 848.61 source equivalent",
        "product-nagatanien-kamado-san-3-cup": "ILS 314.95 source equivalent",
        "product-kubo-komakichi-kazuho-chasen": "ILS 111.28 source equivalent",
    }
    assert new_product_ids <= set(products)

    science_only_sources = {
        "nori-category-science-2024",
        "tamari-category-science-2020",
        "shiitake-category-science-2024",
        "kudzu-category-science-2026",
        "sansho-category-science-2023",
        "matcha-category-science-2022-a",
        "matcha-category-science-2022-b",
        "chasen-foam-science-2012",
    }
    for product_id in new_product_ids:
        product = products[product_id]
        variant = variants["variant-" + product_id.removeprefix("product-")]
        sku = skus["sku-" + product_id.removeprefix("product-")]
        attributes = variant["attributes"]
        assert product["state"] == "research_candidate"
        assert sku["state"] == "research_candidate"
        assert sku["woo_product_code"] == ""
        assert sku["inventory_policy"] == "research_only"
        assert attributes["planning_stock_quantity"] == "0"
        assert attributes["public_market_projection"] == "held"
        assert Decimal(attributes["planning_price_ils"]) > 0
        assert attributes["planning_price_rationale"]
        assert expected_fx_arithmetic[product_id] in attributes["planning_price_rationale"]
        assert attributes["activation_gates"]
        assert attributes["compliance_note"].startswith("[COMPLIANCE_NOTE:")
        assert attributes["compliance_note_he"].startswith("[COMPLIANCE_NOTE:")
        assert attributes["compliance_note_en"].startswith("[COMPLIANCE_NOTE:")
        assert attributes["compliance_note"] == attributes["compliance_note_en"]
        assert attributes["compliance_note_he"] != attributes["compliance_note_en"]
        assert set(product["source_ids"]).isdisjoint(science_only_sources)

    offers = {
        offer["sku_id"]: offer
        for offer in registry["channel_offers"]
        if offer["sku_id"].removeprefix("sku-")
        in {item.removeprefix("product-") for item in new_product_ids}
    }
    assert len(offers) == 12
    for offer in offers.values():
        assert offer["state"] == "draft"
        assert offer["stock_policy"] == "research_only"
        assert offer["kiosk_projection"]["availability"] == "held"
        assert "evidence-boi-fx-20260806" in offer["evidence_artifact_ids"]


def test_premium_tranche_bundles_and_merchandising_are_held_and_evidenced(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    bundle_ids = {
        "bundle-edomae-sushi-lab-draft",
        "bundle-umami-shojin-lab-draft",
        "bundle-matcha-ritual-draft",
        "bundle-pro-sushi-tools-draft",
        "bundle-seasonal-hassun-capsule-draft",
    }
    bundles = _id_index(registry["bundles"])
    assert bundle_ids <= set(bundles)
    for bundle_id in bundle_ids:
        bundle = bundles[bundle_id]
        assert bundle["state"] == "draft"
        assert bundle["channel_offer_id"] == ""
        assert bundle["inventory_policy"] == "component_managed"
        assert bundle["components"]
        assert bundle["evidence_artifact_ids"]

    premium_edges = [
        edge
        for edge in registry["merchandising_edges"]
        if edge["id"].startswith(
            (
                "edge-kontobi-",
                "edge-vinegar-",
                "edge-tamari-",
                "edge-shiitake-",
                "edge-yuba-",
                "edge-kudzu-",
                "edge-matcha-",
                "edge-makisu-",
                "edge-kamadosan-",
            )
        )
    ]
    assert len(premium_edges) == 9
    assert {edge["type"] for edge in premium_edges} == {"cross_sell"}
    assert next(
        edge for edge in premium_edges if edge["id"] == "edge-makisu-to-yanagiba"
    )["type"] == "cross_sell"
    assert all(edge["state"] == "draft" for edge in premium_edges)
    assert all(edge["evidence_artifact_ids"] for edge in premium_edges)


def test_premium_tranche_primary_source_corrections_are_exact_and_bounded(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    products = _id_index(registry["products"])
    variants = _id_index(registry["variants"])
    skus = _id_index(registry["skus"])
    observations = _id_index(registry["market_observations"])
    artifacts = _id_index(registry["evidence_artifacts"])

    fx = artifacts["evidence-boi-fx-20260806"]
    official_update = datetime.fromisoformat("2026-08-06T12:21:04+00:00")
    captured = datetime.fromisoformat(fx["captured_at"].replace("Z", "+00:00"))
    assert captured >= official_update
    assert fx["source_url"] == "https://boi.org.il/PublicApi/GetExchangeRates"
    assert fx["capture_method"] == "official-api-json-review"
    assert "lastUpdate 2026-08-06T12:21:04Z" in fx["claim_locator"]
    assert "retrieved 2026-08-06T18:19:19Z" in fx["claim_locator"]

    tenju_key = "marukyu-koyamaen-tenju-matcha-20g"
    tenju_product = products[f"product-{tenju_key}"]
    tenju_variant = variants[f"variant-{tenju_key}"]
    tenju_observation = observations[f"observation-{tenju_key}-20260806"]
    tenju_artifact = artifacts[f"evidence-{tenju_key}-20260806"]
    assert tenju_observation["amount_minor"] == 21600
    assert tenju_observation["availability_state"] == "sold_out_limited_allocation"
    assert tenju_observation["normalization"]["normalized_amount_minor"] == 1080000
    assert tenju_variant["attributes"]["sku"] == "1111020C1"
    assert tenju_variant["attributes"]["stock_state"] == "sold out"
    assert "ILS 412.30 source equivalent" in tenju_variant["attributes"][
        "planning_price_rationale"
    ]
    assert tenju_artifact["source_url"] == (
        "https://www.marukyu-koyamaen.co.jp/motoan-shop/products/1111020c1/"
    )

    tenju_serialized = json.dumps(
        [tenju_product, tenju_variant, tenju_observation, tenju_artifact],
        ensure_ascii=False,
    ).lower()
    for unsupported in (
        "20,100",
        "20100",
        "price-conflict",
        "price conflict",
        "january 2026 catalog",
    ):
        assert unsupported not in tenju_serialized

    kamado_key = "nagatanien-kamado-san-3-cup"
    kamado_product = products[f"product-{kamado_key}"]
    kamado_variant = variants[f"variant-{kamado_key}"]
    kamado_observation = observations[f"observation-{kamado_key}-20260806"]
    kamado_artifact = artifacts[f"evidence-{kamado_key}-20260806"]
    assert kamado_observation["amount_minor"] == 16500
    assert kamado_observation["availability_state"] == (
        "sequential_shipment_after_late_september"
    )
    assert kamado_variant["attributes"]["model_code"] == "ACT-01"
    assert kamado_variant["attributes"]["capacity"] == "three cups"
    assert "9月下旬以降" in kamado_variant["attributes"]["availability_state"]
    assert kamado_observation["tax_state"] == "included"

    nori_observation = observations[
        "observation-maruyama-gokujo-kontobi-nori-5-sheets-20260806"
    ]
    yuba_observation = observations[
        "observation-yubaya-kyoto-dried-yuba-100g-20260806"
    ]
    assert nori_observation["tax_state"] == "included"
    assert yuba_observation["tax_state"] == "included"

    yanagiba = products["product-sakai-takayuki-ginsan-yanagiba-270mm"]
    manufacturers = _id_index(registry["manufacturers"])
    brands = _id_index(registry["brands"])
    assert yanagiba["brand_id"] == "brand-sakai-takayuki"
    assert yanagiba["manufacturer_id"] == "manufacturer-aoki-hamono"
    assert brands["brand-sakai-takayuki"]["name"]["en"] == "Sakai Takayuki"
    assert manufacturers["manufacturer-aoki-hamono"]["name"]["en"] == (
        "Aoki Hamono"
    )
    assert "manufacturer-sakai-takayuki" not in manufacturers

    kamado_serialized = json.dumps(
        [kamado_product, kamado_variant, kamado_observation, kamado_artifact],
        ensure_ascii=False,
    ).lower()
    for wrong_scope in (
        "mid-september",
        "preorder",
        "restock",
        "four cups",
        "four-cup model",
        "4-cup",
    ):
        assert wrong_scope not in kamado_serialized


def test_product_variant_sku_and_observation_are_separate_identity_layers(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    products = _id_index(registry["products"])
    variants = _id_index(registry["variants"])
    skus = _id_index(registry["skus"])
    observations = _id_index(registry["market_observations"])

    identity_sets = [set(items) for items in (products, variants, skus, observations)]
    for offset, left in enumerate(identity_sets):
        for right in identity_sets[offset + 1 :]:
            assert left.isdisjoint(right)

    for variant in variants.values():
        assert variant["product_id"] in products
        assert "knowledge_entity_id" not in variant
    for sku in skus.values():
        assert sku["variant_id"] in variants
        assert "product_id" not in sku
        assert "knowledge_entity_id" not in sku
    for observation in observations.values():
        assert observation["sku_id"] in skus
        assert "variant_id" not in observation
        assert "product_id" not in observation

    for collection in (registry["products"], registry["variants"], registry["skus"]):
        for record in collection:
            assert not any(key.endswith("_minor") for key in record), record["id"]


def test_money_is_stored_only_as_integer_minor_units(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    money_fields = 0
    for path, key, value in _walk(registry):
        assert not isinstance(value, float), path
        if key.endswith("_minor") or key.endswith("_minor_signed"):
            money_fields += 1
            assert value is None or (
                isinstance(value, int) and not isinstance(value, bool) and value >= 0
            ), path
        elif MONEY_KEY.search(key) and key not in NON_AMOUNT_KEYS:
            assert not isinstance(value, (int, float)), (
                f"{path} is a numeric money-looking field without _minor"
            )
    assert money_fields >= 20


def test_all_cross_collection_references_are_type_safe(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    science = commerce_payload["science"]
    ids = {
        key: set(_id_index(registry[key]))
        for key in (
            "countries",
            "currencies",
            "locales",
            "tax_zones",
            "markets",
            "channels",
            "sellers",
            "brands",
            "manufacturers",
            "products",
            "variants",
            "skus",
            "supplier_offers",
            "evidence_artifacts",
            "market_observations",
            "channel_offers",
            "landed_cost_scenarios",
            "margin_scenarios",
            "bundles",
            "connector_profiles",
            "integration_consumers",
        )
    }
    science_entity_ids = {entity["id"] for entity in science["entities"]}
    science_source_ids = set(science["sources"])

    def optional(value: str, target: set[str]) -> bool:
        return value == "" or value in target

    for locale in registry["locales"]:
        assert locale["country_id"] in ids["countries"]
    for seller in registry["sellers"]:
        assert seller["country_id"] in ids["countries"]
        assert optional(seller["science_entity_id"], science_entity_ids)
        assert set(seller["source_ids"]).issubset(science_source_ids)
    for zone in registry["tax_zones"]:
        assert zone["country_id"] in ids["countries"]
        assert set(zone["evidence_source_ids"]).issubset(science_source_ids)
    for market in registry["markets"]:
        assert market["country_id"] in ids["countries"]
        assert market["currency_id"] in ids["currencies"]
        assert set(market["locale_ids"]).issubset(ids["locales"])
        assert set(market["tax_zone_ids"]).issubset(ids["tax_zones"])
        assert optional(market["seller_of_record_id"], ids["sellers"])
    for channel in registry["channels"]:
        assert set(channel["market_ids"]).issubset(ids["markets"])
        assert channel["connector_profile_id"] in ids["connector_profiles"]
    for connector in registry["connector_profiles"]:
        assert set(connector["channel_ids"]).issubset(ids["channels"])
    for consumer in registry["integration_consumers"]:
        assert consumer["connector_profile_id"] in ids["connector_profiles"]
        assert set(consumer["market_ids"]).issubset(ids["markets"])
        assert set(consumer["channel_ids"]).issubset(ids["channels"])
    for brand in registry["brands"]:
        assert optional(brand["owner_seller_id"], ids["sellers"])
        assert set(brand["source_ids"]).issubset(science_source_ids)
    for manufacturer in registry["manufacturers"]:
        assert optional(manufacturer["seller_id"], ids["sellers"])
        assert manufacturer["country_id"] in ids["countries"]
        assert optional(manufacturer["science_entity_id"], science_entity_ids)
        assert set(manufacturer["source_ids"]).issubset(science_source_ids)
    for product in registry["products"]:
        assert product["knowledge_entity_id"] in science_entity_ids
        assert optional(product["brand_id"], ids["brands"])
        assert optional(product["manufacturer_id"], ids["manufacturers"])
        assert set(product["source_ids"]).issubset(science_source_ids)
    for variant in registry["variants"]:
        assert variant["product_id"] in ids["products"]
        assert set(variant["source_entity_ids"]).issubset(science_entity_ids)
    for sku in registry["skus"]:
        assert sku["variant_id"] in ids["variants"]
    for artifact in registry["evidence_artifacts"]:
        assert artifact["source_id"] in science_source_ids
    for offer in registry["supplier_offers"]:
        assert offer["sku_id"] in ids["skus"]
        assert offer["seller_id"] in ids["sellers"]
        assert offer["market_id"] in ids["markets"]
        assert offer["currency_id"] in ids["currencies"]
        assert set(offer["evidence_artifact_ids"]).issubset(
            ids["evidence_artifacts"]
        )
    for observation in registry["market_observations"]:
        assert observation["sku_id"] in ids["skus"]
        assert observation["seller_id"] in ids["sellers"]
        assert observation["market_id"] in ids["markets"]
        assert observation["currency_id"] in ids["currencies"]
        assert observation["evidence_artifact_id"] in ids["evidence_artifacts"]
        assert observation["source_entity_id"] in science_entity_ids
        assert optional(observation["supersedes_id"], ids["market_observations"])
    for scenario in registry["landed_cost_scenarios"]:
        assert scenario["sku_id"] in ids["skus"]
        assert scenario["destination_market_id"] in ids["markets"]
        assert scenario["currency_id"] in ids["currencies"]
        assert optional(
            scenario["source_observation_id"], ids["market_observations"]
        )
        assert optional(scenario["supplier_offer_id"], ids["supplier_offers"])
    for scenario in registry["margin_scenarios"]:
        assert scenario["channel_offer_id"] in ids["channel_offers"]
        assert scenario["landed_cost_scenario_id"] in ids[
            "landed_cost_scenarios"
        ]
        assert scenario["currency_id"] in ids["currencies"]
    for offer in registry["channel_offers"]:
        assert offer["sku_id"] in ids["skus"]
        assert offer["market_id"] in ids["markets"]
        assert offer["channel_id"] in ids["channels"]
        assert offer["currency_id"] in ids["currencies"]
        assert optional(
            offer["landed_cost_scenario_id"], ids["landed_cost_scenarios"]
        )
        assert optional(offer["margin_scenario_id"], ids["margin_scenarios"])


def test_observations_match_their_scientific_listing_subjects(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    science_entities = _id_index(commerce_payload["science"]["entities"])
    products = _id_index(registry["products"])
    variants = _id_index(registry["variants"])
    skus = _id_index(registry["skus"])

    for observation in registry["market_observations"]:
        sku = skus[observation["sku_id"]]
        variant = variants[sku["variant_id"]]
        product = products[variant["product_id"]]
        source = science_entities[observation["source_entity_id"]]
        assert source["type"] in {"retail_listing", "market_observation"}
        targets = {source["parent_id"]} | {
            relation["target_id"] for relation in source["relations"]
        }
        assert product["knowledge_entity_id"] in targets, observation["id"]


def test_research_skus_have_no_woocommerce_binding(
    commerce_payload: dict[str, Any],
) -> None:
    skus = commerce_payload["registry"]["skus"]
    assert skus
    assert all(sku["state"] == "research_candidate" for sku in skus)
    for sku in skus:
        assert sku["woo_product_code"] == "", sku["id"]
        assert not {
            external_id["provider"].casefold()
            for external_id in sku["external_ids"]
        } & {"woo", "woocommerce", "wc_product"}, sku["id"]


def test_bundles_contain_sku_components_only(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    sku_ids = {sku["id"] for sku in registry["skus"]}
    expected_component_keys = {
        "sku_id",
        "quantity_decimal",
        "unit_code",
        "substitution_group",
        "required",
    }
    assert registry["bundles"]
    for bundle in registry["bundles"]:
        assert bundle["components"]
        for component in bundle["components"]:
            assert set(component) == expected_component_keys
            assert component["sku_id"] in sku_ids
            assert "product_id" not in component
            assert "variant_id" not in component


def test_myshop_is_an_unbound_contract_and_woocommerce_owns_the_catalog(
    commerce_payload: dict[str, Any],
) -> None:
    registry = commerce_payload["registry"]
    connectors = _id_index(registry["connector_profiles"])
    channels = _id_index(registry["channels"])
    myshop = connectors["connector-myshop-contract-pending"]
    assert myshop["vendor"].casefold() == "myshop"
    assert myshop["binding_state"] == "contract_required"
    assert myshop["transport_mode"] == "unbound"
    assert myshop["official_source_urls"]
    assert all(
        url.startswith("https://") for url in myshop["official_source_urls"]
    )
    assert set(myshop["channel_ids"]) == {
        "channel-myshop-kiosk-il",
        "channel-myshop-pos-il",
    }

    for channel_id in myshop["channel_ids"]:
        channel = channels[channel_id]
        assert channel["channel_type"] in {"kiosk", "pos"}
        assert channel["connector_profile_id"] == myshop["id"]
        assert channel["catalog_authority"] == "woocommerce"
        assert channel["price_authority"] == "woocommerce"
        assert channel["inventory_authority"] == "woocommerce"
        assert channel["order_authority"] == "woocommerce"


def test_vendor_neutral_pos_route_is_signed_and_fail_closed(
    commerce_payload: dict[str, Any],
) -> None:
    route = commerce_payload["route"]
    assert "myshop" not in POS_ROUTE.casefold()
    assert route["methods"] == "POST"
    assert route["permission_callback"] == [
        "Complete99_Culinary_Commerce",
        "verify_pos_signature",
    ]
    assert route["callback"] == [
        "Complete99_Culinary_Commerce",
        "rest_pos_catalog",
    ]
    source = COMMERCE_CLASS.read_text(encoding="utf-8")
    assert "complete99-pos-catalog-request/v1" in source
    assert "complete99-pos-catalog-response/v1" in source
    assert "self::offer_is_effective( $offer, $today )" in source
    assert "price_minor" in source
    assert "verify_scoped_integration_signature" in source
    assert "consumer_id" in source


def test_plugin_require_boot_migration_and_review_lab_wiring() -> None:
    bootstrap = BOOTSTRAP.read_text(encoding="utf-8")
    platform = PLATFORM_CLASS.read_text(encoding="utf-8")
    review = REVIEW_LAB.read_text(encoding="utf-8")
    health = REST_CLASS.read_text(encoding="utf-8")

    science_require = "includes/class-complete99-culinary-science.php"
    commerce_require = "includes/class-complete99-culinary-commerce.php"
    platform_require = "includes/class-complete99-platform.php"
    assert science_require in bootstrap
    assert commerce_require in bootstrap
    assert bootstrap.index(science_require) < bootstrap.index(commerce_require)
    assert bootstrap.index(commerce_require) < bootstrap.index(platform_require)

    assert "Complete99_Culinary_Science::boot();" in platform
    assert "Complete99_Culinary_Commerce::boot();" in platform
    assert platform.index("Complete99_Culinary_Science::boot();") < platform.index(
        "Complete99_Culinary_Commerce::boot();"
    )

    migration = platform.split("private static function run_migration", 1)[1]
    science_invariant = migration.index(
        "Complete99_Culinary_Science::assert_invariants();"
    )
    commerce_invariant = migration.index(
        "Complete99_Culinary_Commerce::assert_invariants();"
    )
    version_write = migration.index(
        "update_option( 'complete99_platform_version', COMPLETE99_PLATFORM_VERSION"
    )
    commit = migration.index("$wpdb->query( 'COMMIT' )")
    assert science_invariant < commerce_invariant < version_write < commit

    assert "Complete99_Culinary_Commerce::status()" in health
    assert "'culinary_commerce_ready' =>" in health
    assert "Complete99_Culinary_Commerce::editorial_snapshot()" in review
    assert "'culinary_commerce_graph' => array(" in review
    for collection in (
        "products",
        "variants",
        "skus",
        "observations",
        "channel_offers",
        "bundles",
        "connector_profiles",
    ):
        assert f"'{collection}'" in review


def test_active_offer_without_all_commercial_gates_is_rejected(
    commerce_payload: dict[str, Any],
) -> None:
    result = commerce_payload["mutations"]["active_offer_without_gates"]
    assert result["valid"] is False
    assert _error_code(result) == "complete99_commerce_registry_invalid"
    assert _error_path(result).endswith(".approval_gate")


def test_observation_subject_mismatch_is_rejected(
    commerce_payload: dict[str, Any],
) -> None:
    result = commerce_payload["mutations"]["observation_subject_mismatch"]
    assert result["valid"] is False
    assert _error_code(result) == "complete99_commerce_registry_invalid"
    assert _error_path(result).endswith(".subject_mismatch")


def test_duplicate_internal_sku_code_is_rejected(
    commerce_payload: dict[str, Any],
) -> None:
    result = commerce_payload["mutations"]["duplicate_internal_code"]
    assert result["valid"] is False
    assert _error_code(result) == "complete99_commerce_registry_invalid"
    assert _error_path(result).endswith(".internal_code")


def test_offer_eligible_evidence_without_snapshot_is_rejected(
    commerce_payload: dict[str, Any],
) -> None:
    result = commerce_payload["mutations"]["eligible_evidence_without_snapshot"]
    assert result["valid"] is False
    assert _error_code(result) == "complete99_commerce_registry_invalid"
    assert _error_path(result).endswith(".eligible_without_snapshot")


def test_reusable_approved_chain_is_fully_coherent(
    approved_chain_payload: dict[str, Any],
) -> None:
    assert approved_chain_payload["approved"]["valid"], approved_chain_payload[
        "approved"
    ]
    assert approved_chain_payload["approved_status"]["active_offers"] == 1
    chain = approved_chain_payload["chain"]

    assert chain["product"]["state"] == "active"
    assert chain["variant"]["state"] == "active"
    assert chain["variant"]["product_id"] == chain["product"]["id"]
    assert chain["sku"]["state"] == "active"
    assert chain["sku"]["variant_id"] == chain["variant"]["id"]
    assert chain["sku"]["woo_product_code"]
    assert chain["sku"]["inventory_policy"] == "woocommerce_managed"
    assert chain["sku"]["compliance_state"] == "cleared"

    supplier = chain["supplier_offer"]
    landed = chain["landed_cost"]
    margin = chain["margin"]
    offer = chain["channel_offer"]
    assert supplier["state"] == "approved"
    assert supplier["sku_id"] == chain["sku"]["id"]
    assert landed["supplier_offer_id"] == supplier["id"]
    assert landed["sku_id"] == chain["sku"]["id"]
    assert landed["fx"] == {
        "pair": "JPY/ILS",
        "source_currency_id": "currency-jpy",
        "destination_currency_id": "currency-ils",
        "direction": "source_to_destination",
        "rounding": "half_up_minor",
        "rate_decimal": "0.024000",
        "source_url": (
            "https://www.boi.org.il/en/economic-roles/financial-markets/"
            "exchange-rates/"
        ),
        "observed_at": "2026-08-06T00:50:31+03:00",
    }
    assert landed["calculation_method"] == (
        "sum_cost_lines_divided_by_sellable_units_ceiling_minor"
    )
    assert landed["source_subtotal_minor"] == supplier["price_minor"]
    assert landed["converted_source_cost_minor"] == 2796
    assert landed["landed_cost_minor"] == sum(
        line["amount_minor"] for line in landed["cost_lines"]
    )
    assert all(line["status"] == "verified" for line in landed["cost_lines"])
    assert all(
        line["evidence_artifact_id"] for line in landed["cost_lines"]
    )

    assert margin["landed_cost_scenario_id"] == landed["id"]
    assert margin["landed_cost_minor"] == landed["landed_cost_minor"]
    adjustment_total = sum(
        line["amount_minor_signed"]
        for line in margin["revenue_adjustment_lines"]
    )
    assert offer["price_minor"] + adjustment_total == margin[
        "net_revenue_minor"
    ]
    variable_total = sum(
        line["amount_minor"] for line in margin["variable_cost_lines"]
    )
    assert margin["contribution_minor"] == (
        margin["net_revenue_minor"]
        - margin["landed_cost_minor"]
        - variable_total
    )
    expected_rate = (
        Decimal(margin["contribution_minor"])
        / Decimal(margin["net_revenue_minor"])
    ).quantize(Decimal("0.000001"), rounding=ROUND_HALF_UP)
    assert margin["margin_rate_decimal"] == format(expected_rate, ".6f")
    assert offer["landed_cost_scenario_id"] == landed["id"]
    assert offer["margin_scenario_id"] == margin["id"]
    assert offer["woo_product_code"] == chain["sku"]["woo_product_code"]
    assert offer["price_basis"] == "gross_tax_inclusive"
    assert chain["connector"]["binding_state"] == "bound"
    assert chain["connector"]["transport_mode"] == "api"
    assert chain["consumer"]["state"] == "active"
    assert chain["consumer"]["connector_profile_id"] == chain["connector"][
        "id"
    ]


@pytest.mark.parametrize(
    ("mutation", "expected_suffix"),
    [
        ("bound_connector_unbound_transport", ".bound_transport"),
        ("invalidated_observation", ".observation_state"),
        ("quote_pending_supplier_offer", ".supplier_offer_state"),
        ("blank_cross_currency_fx", ".fx.contract"),
        ("wrong_cross_currency_fx", ".fx.contract"),
        ("unverified_cost_line", ".cost_lines.1.approval"),
        ("unevidenced_cost_line", ".cost_lines.1.approval"),
        ("landed_cost_machine_mismatch", ".landed_cost_formula"),
        ("margin_landed_amount_mismatch", ".landed_cost_contract"),
        ("margin_currency_mismatch", ".landed_cost_contract"),
        ("margin_rate_mismatch", ".margin_rate_decimal.formula"),
        ("different_landed_scenario_links", ".scenario_gate"),
        ("same_currency_wrong_destination", ".destination_market"),
        ("unexplained_gross_to_net_gap", ".net_revenue_bridge"),
        ("reversed_validity_window", ".validity"),
        ("negative_modifier_effective_price", ".modifier_effective_price"),
        ("inactive_channel_gate", ".activation_gate"),
        ("inactive_market_gate", ".activation_gate"),
        ("inactive_parent_gate", ".activation_gate"),
        ("uncleared_compliance_gate", ".approval_gate"),
        ("sku_inventory_gate", ".activation_gate"),
        ("offer_inventory_gate", ".activation_gate"),
        ("market_tax_gate", ".market_tax_gate"),
    ],
)
def test_approved_chain_mutations_fail_closed_at_the_expected_gate(
    approved_chain_payload: dict[str, Any],
    mutation: str,
    expected_suffix: str,
) -> None:
    result = approved_chain_payload["mutations"][mutation]
    assert result["valid"] is False, mutation
    assert _error_code(result) == "complete99_commerce_registry_invalid"
    assert _error_path(result).endswith(expected_suffix), (
        mutation,
        _error_path(result),
    )


def test_expired_active_offer_is_excluded_from_status_and_pos_projection(
    approved_chain_payload: dict[str, Any],
) -> None:
    expired = approved_chain_payload["expired"]
    assert expired["validation"]["valid"], expired["validation"]
    assert expired["status"]["ready"] is True
    assert expired["status"]["active_offers"] == 0
    assert expired["pos"]["schema"] == "complete99-pos-catalog-response/v1"
    assert expired["pos"]["consumer_id"] == "consumer-myshop-pos-adapter"
    assert expired["pos"]["count"] == 0
    assert expired["pos"]["items"] == []
    assert expired["pos"]["next_cursor"] == ""


def test_same_currency_destination_mutation_is_not_a_currency_failure(
    approved_chain_payload: dict[str, Any],
) -> None:
    context = approved_chain_payload["wrong_destination_context"]
    assert context["offer_market_id"] != context["landed_destination_market_id"]
    assert context["offer_currency_id"] == context["destination_currency_id"]
    result = approved_chain_payload["mutations"][
        "same_currency_wrong_destination"
    ]
    assert result["valid"] is False
    assert _error_path(result).endswith(".destination_market")


def test_culinary_commerce_files_contain_no_em_dash_u2014() -> None:
    paths = [
        BOOTSTRAP,
        PLATFORM_CLASS,
        REST_CLASS,
        REVIEW_LAB,
        SCIENCE_CLASS,
        COMMERCE_CLASS,
        SCIENCE_DATA,
        COMMERCE_DATA,
        SYRIAN_COMMERCE_DATA,
        Path(__file__),
    ]
    offenders = [
        path.relative_to(ROOT).as_posix()
        for path in paths
        if "\u2014" in path.read_text(encoding="utf-8")
    ]
    assert offenders == []
