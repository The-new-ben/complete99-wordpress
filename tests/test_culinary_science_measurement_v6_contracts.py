import hashlib
import json
import shutil
import subprocess
import tempfile
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"


def _php_path(path: Path, *, directory: bool = False) -> str:
    value = path.as_posix()
    if directory:
        value = value.rstrip("/") + "/"
    return value.replace("\\", "\\\\").replace("'", "\\'")


def _run_php_json(script: str, *, timeout: int = 120) -> object:
    if not shutil.which("php"):
        pytest.skip("PHP is required for executable Culinary Science contracts")
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=timeout,
    )
    return json.loads(completed.stdout)


def _wp_error_stub() -> str:
    return r"""
class WP_Error {
    private $code;
    private $message;
    private $data;
    public function __construct($code, $message, $data = array()) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
"""


@pytest.fixture(scope="module")
def v6_contract_payload() -> dict:
    script = r"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '__PLUGIN_DIR__');
define('COMPLETE99_PLATFORM_URL', 'https://complete99.test/wp-content/plugins/complete99-platform/');
__WP_ERROR_STUB__
require '__SCIENCE_CLASS__';
$bundled = require '__SCIENCE_DATA__';

function c99_result($candidate) {
    $result = Complete99_Culinary_Science::validate_registry($candidate);
    if (true === $result) {
        return array('valid' => true, 'code' => '', 'path' => '');
    }
    $data = $result->get_error_data();
    return array(
        'valid' => false,
        'code' => $result->get_error_code(),
        'path' => is_array($data) && isset($data['path']) ? $data['path'] : '',
    );
}

function c99_entity_offset($registry, $entity_id) {
    foreach ($registry['entities'] as $offset => $entity) {
        if ($entity_id === $entity['id']) {
            return $offset;
        }
    }
    throw new RuntimeException('missing-test-entity-' . $entity_id);
}

function c99_fact_offset($entity, $fact_id) {
    foreach ($entity['facts'] as $offset => $fact) {
        if ($fact_id === $fact['id']) {
            return $offset;
        }
    }
    throw new RuntimeException('missing-test-fact-' . $fact_id);
}

function c99_v6_base($registry) {
    $registry['schema'] = 'complete99-culinary-science-registry/v6';
    $registry['source_receipts'] = array();
    foreach ($registry['entities'] as &$entity) {
        foreach ($entity['facts'] as &$fact) {
            $fact['scientific_measurements'] = array();
        }
        unset($fact);
    }
    unset($entity);
    return $registry;
}

function c99_receipt($source_id) {
    return array(
        'schema' => 'complete99-source-evidence-receipt/v1',
        'source_id' => $source_id,
        'upstream_url' => 'https://1469.stores.jp/items/5efed301ec8fd331f922d017',
        'upstream_sha256' => str_repeat('a', 64),
        'evidence_repository_path' => 'docs/research-evidence/hishiroku/chouhaku-kin-2026-08-06.html',
        'evidence_sha256' => str_repeat('b', 64),
        'retrieved_at' => '2026-08-06T10:30:00Z',
        'license' => 'Source retained for verification under applicable terms',
        'claim_locators' => array(
            'maker_dose' => 'Product description stating 20 g per 15 kg before washing',
        ),
        'review_state' => 'verified',
    );
}

function c99_measurement($id, $confidence = 'verified', $scope = 'supplier_specification') {
    return array(
        'id' => $id,
        'property' => 'maker_stated_starter_dose',
        'kind' => 'point',
        'low' => null,
        'high' => null,
        'value' => 20,
        'unit' => 'g per 15 kg substrate before washing',
        'method' => 'Maker-stated dosage transcribed from the official product listing; not a laboratory assay.',
        'specimen_scope' => $scope,
        'conditions' => array(
            'substrate_mass' => '15 kg',
            'substrate_state' => 'before washing',
        ),
        'confidence' => $confidence,
        'source_ids' => array('hishiroku-chouhaku-kin-20g-listing-2026'),
        'measured_at' => 'lot_measurement' === $scope ? '2026-08-06T10:30:00Z' : '',
    );
}

$base = c99_v6_base($bundled);
$source_id = 'hishiroku-chouhaku-kin-20g-listing-2026';
$entity_offset = c99_entity_offset($base, 'ingredient-koji-starter-culture');
$fact_offset = c99_fact_offset(
    $base['entities'][$entity_offset],
    'fact-chouhaku-kin-maker-directions-20260806'
);

$positive = $base;
$positive['source_receipts'][$source_id] = c99_receipt($source_id);
$positive['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'] = array(
    c99_measurement('sm-chouhaku-kin-maker-dose-20260806')
);

$cases = array();
$cases['valid_empty_measurement_registry'] = c99_result($base);
$cases['valid_verified_point'] = c99_result($positive);

$valid_range = $positive;
$range = c99_measurement('sm-range-literature');
$range['property'] = 'reported_activity_range';
$range['kind'] = 'range';
$range['low'] = 10;
$range['high'] = 20.5;
$range['value'] = null;
$range['specimen_scope'] = 'literature_context';
$range['conditions'] = array('study_matrix' => 'documented substrate system');
$valid_range['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'] = array($range);
$cases['valid_verified_range'] = c99_result($valid_range);

$valid_lot = $positive;
$valid_lot['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'] = array(
    c99_measurement('sm-valid-lot', 'verified', 'lot_measurement')
);
$cases['valid_lot_with_time'] = c99_result($valid_lot);

$valid_reviewed_recipe = $base;
$recipe = c99_measurement('sm-reviewed-recipe', 'reviewed', 'recipe_batch');
$recipe['conditions'] = array();
$valid_reviewed_recipe['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'] = array($recipe);
$cases['valid_reviewed_recipe_without_receipt'] = c99_result($valid_reviewed_recipe);

$valid_pending_supplier = $base;
$pending = c99_measurement('sm-pending-supplier', 'pending', 'supplier_specification');
$pending['conditions'] = array();
$valid_pending_supplier['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'] = array($pending);
$cases['valid_pending_supplier_without_receipt'] = c99_result($valid_pending_supplier);

$candidate = $positive;
unset($candidate['source_receipts']);
$cases['missing_source_receipts_key'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['unexpected'] = true;
$cases['receipt_extra_key'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'] = array(c99_receipt($source_id));
$cases['receipt_list_instead_of_map'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['source_id'] = 'maff-fermented-foods';
$cases['receipt_key_mismatch'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts']['unknown-source'] = $candidate['source_receipts'][$source_id];
$candidate['source_receipts']['unknown-source']['source_id'] = 'unknown-source';
unset($candidate['source_receipts'][$source_id]);
$cases['receipt_unknown_source'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['schema'] = 'complete99-source-evidence-receipt/v2';
$cases['receipt_bad_schema'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['upstream_url'] = 'http://example.test/source';
$cases['receipt_non_https_url'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['upstream_url'] = 'https://example.test/' . str_repeat('a', 2049);
$cases['receipt_oversize_url'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['upstream_sha256'] = str_repeat('A', 64);
$cases['receipt_uppercase_upstream_sha'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['upstream_sha256'] = str_repeat('a', 64) . "\n";
$cases['receipt_sha_trailing_lf'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['upstream_sha256'] = str_repeat('a', 64) . "\r\n";
$cases['receipt_sha_trailing_crlf'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['evidence_sha256'] = 'sha256:' . str_repeat('b', 64);
$cases['receipt_prefixed_evidence_sha'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['evidence_repository_path'] = 'docs/research-evidence/../private.txt';
$cases['receipt_path_traversal'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['evidence_repository_path'] = 'docs\\research-evidence\\private.txt';
$cases['receipt_path_backslash'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['evidence_repository_path'] = 'private/research-evidence/source.txt';
$cases['receipt_path_outside_root'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['evidence_repository_path'] = "docs/research-evidence/source.txt\n";
$cases['receipt_path_trailing_lf'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['evidence_repository_path'] = "docs/research-evidence/source.txt\r\n";
$cases['receipt_path_trailing_crlf'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['evidence_repository_path'] = 'docs/research-evidence/' . str_repeat('a', 480) . '.txt';
$cases['receipt_oversize_path'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['retrieved_at'] = '2026-08-06';
$cases['receipt_bad_datetime'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['retrieved_at'] = '2026-08-06T10:30:00+99:99';
$cases['receipt_invalid_offset'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['retrieved_at'] = '2026-08-06T10:30:00+14:01';
$cases['receipt_offset_beyond_iso_limit'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['license'] = '';
$cases['receipt_blank_license'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['license'] = str_repeat('a', 201);
$cases['receipt_oversize_license'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['claim_locators'] = array();
$cases['receipt_empty_locators'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['claim_locators'] = array('bad key' => 'description');
$cases['receipt_bad_locator_key'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['claim_locators'] = array('maker_dose' => '');
$cases['receipt_blank_locator'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['claim_locators'] = array(str_repeat('a', 121) => 'description');
$cases['receipt_oversize_locator_key'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['claim_locators'] = array('maker_dose' => str_repeat('a', 501));
$cases['receipt_oversize_locator_value'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'][$source_id]['review_state'] = 'reviewed';
$cases['receipt_not_verified'] = c99_result($candidate);

$candidate = $positive;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['unexpected'] = true;
$cases['measurement_extra_key'] = c99_result($candidate);

$numeric_mutations = array(
    'measurement_numeric_string' => '20',
    'measurement_boolean' => true,
    'measurement_nan' => NAN,
    'measurement_infinite' => INF,
    'measurement_negative' => -1,
);
foreach ($numeric_mutations as $name => $value) {
    $candidate = $positive;
    $candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['value'] = $value;
    $cases[$name] = c99_result($candidate);
}

$candidate = $positive;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['low'] = 1;
$cases['point_with_low'] = c99_result($candidate);

$candidate = $positive;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['high'] = 1;
$cases['point_with_high'] = c99_result($candidate);

$candidate = $positive;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['value'] = null;
$cases['point_without_value'] = c99_result($candidate);

$candidate = $valid_range;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['value'] = 15;
$cases['range_with_value'] = c99_result($candidate);

$candidate = $valid_range;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['low'] = null;
$cases['range_without_low'] = c99_result($candidate);

$candidate = $valid_range;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['low'] = 30;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['high'] = 20;
$cases['range_reversed'] = c99_result($candidate);

$candidate = $positive;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['source_ids'] = array('maff-fermented-foods');
$cases['measurement_source_outside_fact'] = c99_result($candidate);

$candidate = $positive;
$candidate['source_receipts'] = array();
$cases['verified_without_receipt'] = c99_result($candidate);

$candidate = $valid_range;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['conditions'] = array();
$cases['literature_without_conditions'] = c99_result($candidate);

$candidate = $valid_range;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['measured_at'] = '2026-08-06T10:30:00Z';
$cases['literature_with_measured_at'] = c99_result($candidate);

$candidate = $valid_lot;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['measured_at'] = '';
$cases['lot_without_measured_at'] = c99_result($candidate);

$candidate = $valid_lot;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['measured_at'] = '2026-08-06T10:30:00+99:99';
$cases['measurement_invalid_offset'] = c99_result($candidate);

$candidate = $valid_lot;
$candidate['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'][0]['measured_at'] = '2026-08-06T10:30:00+14:01';
$cases['measurement_offset_beyond_iso_limit'] = c99_result($candidate);

$candidate = $positive;
$duplicate_fact = $candidate['entities'][$entity_offset]['facts'][$fact_offset];
$duplicate_fact['id'] = 'fact-duplicate-measurement-host';
$duplicate_fact['public_safe'] = false;
$candidate['entities'][$entity_offset]['facts'][] = $duplicate_fact;
$cases['duplicate_measurement_id_across_facts'] = c99_result($candidate);

$candidate = $positive;
$other_entity_offset = c99_entity_offset($candidate, 'ingredient-kome-koji');
$duplicate_fact = $candidate['entities'][$entity_offset]['facts'][$fact_offset];
$duplicate_fact['id'] = 'fact-cross-entity-duplicate-measurement-host';
$duplicate_fact['public_safe'] = false;
$candidate['entities'][$other_entity_offset]['facts'][] = $duplicate_fact;
$cases['duplicate_measurement_id_across_entities'] = c99_result($candidate);

$projection_registry = $positive;
$verified = c99_measurement('sm-public-verified', 'verified');
$reviewed = c99_measurement('sm-public-reviewed', 'reviewed');
$pending_public = c99_measurement('sm-public-pending', 'pending');
$projection_registry['entities'][$entity_offset]['facts'][$fact_offset]['scientific_measurements'] = array(
    $reviewed,
    $verified,
    $pending_public,
);
$private_fact = $projection_registry['entities'][$entity_offset]['facts'][$fact_offset];
$private_fact['id'] = 'fact-private-verified-measurement';
$private_fact['public_safe'] = false;
$private_fact['scientific_measurements'] = array(
    c99_measurement('sm-private-verified', 'verified')
);
$projection_registry['entities'][$entity_offset]['facts'][] = $private_fact;
$projection_validation = c99_result($projection_registry);
$method = new ReflectionMethod('Complete99_Culinary_Science', 'public_projection');
$method->setAccessible(true);
$projection = $method->invoke(
    null,
    $projection_registry['entities'][$entity_offset],
    $projection_registry,
    'en'
);
$projected_measurement_ids = array();
$projected_measurement_keys = array();
$projected_fact_ids = array();
foreach ($projection['facts'] as $fact) {
    $projected_fact_ids[] = $fact['id'];
    if ('fact-chouhaku-kin-maker-directions-20260806' === $fact['id']) {
        $projected_measurement_keys = array_keys($fact['scientific_measurements']);
    }
    foreach ($fact['scientific_measurements'] as $measurement) {
        $projected_measurement_ids[] = $measurement['id'];
    }
}
$projection_json = json_encode($projection, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$asset_entity_ids = array(
    'ingredient-shoyu-koji',
    'equipment-kioke',
    'guide-koji-hydrolysis',
    'reaction-koji-enzymatic-hydrolysis',
    'standard-jas-shoyu-1703',
);
$asset_projections = array();
foreach ($asset_entity_ids as $asset_entity_id) {
    $asset_entity = $base['entities'][c99_entity_offset($base, $asset_entity_id)];
    $english_projection = $method->invoke(null, $asset_entity, $base, 'en');
    $hebrew_projection = $method->invoke(null, $asset_entity, $base, 'he');
    $asset_projections[$asset_entity_id] = array(
        'en' => $english_projection['visual'],
        'he' => $hebrew_projection['visual'],
    );
}

$bundled_validation = Complete99_Culinary_Science::validate_registry($bundled);
echo json_encode(array(
    'constant_schema' => Complete99_Culinary_Science::REGISTRY_SCHEMA,
    'bundled' => array(
        'schema' => isset($bundled['schema']) ? $bundled['schema'] : '',
        'has_source_receipts' => array_key_exists('source_receipts', $bundled),
        'valid' => true === $bundled_validation,
        'source_receipts' => isset($bundled['source_receipts']) ? $bundled['source_receipts'] : array(),
    ),
    'cases' => $cases,
    'projection' => array(
        'validation' => $projection_validation,
        'measurement_ids' => $projected_measurement_ids,
        'measurement_keys' => $projected_measurement_keys,
        'fact_ids' => $projected_fact_ids,
        'contains_source_receipts' => false !== strpos($projection_json, 'source_receipts'),
        'contains_evidence_path' => false !== strpos($projection_json, 'evidence_repository_path'),
        'contains_receipt_hash' => false !== strpos($projection_json, str_repeat('b', 64)),
    ),
    'asset_projections' => $asset_projections,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
"""
    replacements = {
        "__PLUGIN_DIR__": _php_path(PLUGIN, directory=True),
        "__SCIENCE_CLASS__": _php_path(SCIENCE_CLASS),
        "__SCIENCE_DATA__": _php_path(SCIENCE_DATA),
        "__WP_ERROR_STUB__": _wp_error_stub(),
    }
    for marker, value in replacements.items():
        script = script.replace(marker, value)
    return _run_php_json(script, timeout=180)


def test_bundled_registry_uses_v6_and_valid_source_receipts(
    v6_contract_payload: dict,
) -> None:
    assert v6_contract_payload["constant_schema"] == (
        "complete99-culinary-science-registry/v6"
    )
    bundled = v6_contract_payload["bundled"]
    assert bundled["schema"] == "complete99-culinary-science-registry/v6"
    assert bundled["has_source_receipts"] is True
    assert bundled["valid"] is True
    assert bundled["source_receipts"]


def test_bundled_receipt_evidence_files_match_declared_sha256(
    v6_contract_payload: dict,
) -> None:
    evidence_root = (ROOT / "docs" / "research-evidence").resolve()
    receipts = v6_contract_payload["bundled"]["source_receipts"]
    assert receipts
    for source_id, receipt in receipts.items():
        evidence_path = (ROOT / receipt["evidence_repository_path"]).resolve()
        assert evidence_path.is_relative_to(evidence_root), source_id
        assert evidence_path.is_file(), source_id
        assert hashlib.sha256(evidence_path.read_bytes()).hexdigest() == (
            receipt["evidence_sha256"]
        ), source_id


@pytest.mark.parametrize(
    "case",
    (
        "valid_empty_measurement_registry",
        "valid_verified_point",
        "valid_verified_range",
        "valid_lot_with_time",
        "valid_reviewed_recipe_without_receipt",
        "valid_pending_supplier_without_receipt",
    ),
)
def test_valid_v6_receipt_and_measurement_shapes_are_accepted(
    v6_contract_payload: dict,
    case: str,
) -> None:
    assert v6_contract_payload["cases"][case] == {
        "valid": True,
        "code": "",
        "path": "",
    }


@pytest.mark.parametrize(
    ("case", "path_suffix"),
    (
        ("missing_source_receipts_key", "registry.keys"),
        ("receipt_extra_key", ".keys"),
        ("receipt_list_instead_of_map", "registry.source_receipts"),
        ("receipt_key_mismatch", ".source_id_mismatch"),
        ("receipt_unknown_source", ".unknown_source"),
        ("receipt_bad_schema", ".schema"),
        ("receipt_non_https_url", ".upstream_url"),
        ("receipt_oversize_url", ".upstream_url"),
        ("receipt_uppercase_upstream_sha", ".upstream_sha256"),
        ("receipt_sha_trailing_lf", ".upstream_sha256"),
        ("receipt_sha_trailing_crlf", ".upstream_sha256"),
        ("receipt_prefixed_evidence_sha", ".evidence_sha256"),
        ("receipt_path_traversal", ".evidence_repository_path"),
        ("receipt_path_backslash", ".evidence_repository_path"),
        ("receipt_path_outside_root", ".evidence_repository_path"),
        ("receipt_path_trailing_lf", ".evidence_repository_path"),
        ("receipt_path_trailing_crlf", ".evidence_repository_path"),
        ("receipt_oversize_path", ".evidence_repository_path"),
        ("receipt_bad_datetime", ".retrieved_at"),
        ("receipt_invalid_offset", ".retrieved_at"),
        ("receipt_offset_beyond_iso_limit", ".retrieved_at"),
        ("receipt_blank_license", ".license"),
        ("receipt_oversize_license", ".license"),
        ("receipt_empty_locators", ".claim_locators"),
        ("receipt_bad_locator_key", ".claim_locators.key"),
        ("receipt_blank_locator", ".claim_locators.maker_dose"),
        ("receipt_oversize_locator_key", ".claim_locators.key"),
        ("receipt_oversize_locator_value", ".claim_locators.maker_dose"),
        ("receipt_not_verified", ".review_state"),
    ),
)
def test_source_receipt_tampering_fails_closed(
    v6_contract_payload: dict,
    case: str,
    path_suffix: str,
) -> None:
    result = v6_contract_payload["cases"][case]
    assert result["valid"] is False
    assert result["code"] == "complete99_science_registry_invalid"
    assert result["path"].endswith(path_suffix), result


@pytest.mark.parametrize(
    ("case", "path_suffix"),
    (
        ("measurement_extra_key", ".keys"),
        ("measurement_numeric_string", ".value"),
        ("measurement_boolean", ".value"),
        ("measurement_nan", ".value"),
        ("measurement_infinite", ".value"),
        ("measurement_negative", ".value"),
        ("point_with_low", ".point"),
        ("point_with_high", ".point"),
        ("point_without_value", ".point"),
        ("range_with_value", ".range"),
        ("range_without_low", ".range"),
        ("range_reversed", ".range"),
        ("measurement_source_outside_fact", ".source_outside_fact"),
        ("verified_without_receipt", ".verified_source_receipt"),
        ("literature_without_conditions", ".literature_context"),
        ("literature_with_measured_at", ".literature_context"),
        ("lot_without_measured_at", ".lot_measurement"),
        ("measurement_invalid_offset", ".measured_at"),
        ("measurement_offset_beyond_iso_limit", ".measured_at"),
        ("duplicate_measurement_id_across_facts", ".duplicate"),
        ("duplicate_measurement_id_across_entities", ".duplicate"),
    ),
)
def test_scientific_measurement_tampering_fails_closed(
    v6_contract_payload: dict,
    case: str,
    path_suffix: str,
) -> None:
    result = v6_contract_payload["cases"][case]
    assert result["valid"] is False
    assert result["code"] == "complete99_science_registry_invalid"
    assert result["path"].endswith(path_suffix), result


def test_public_projection_emits_verified_measurements_only(
    v6_contract_payload: dict,
) -> None:
    projection = v6_contract_payload["projection"]
    assert projection["validation"]["valid"] is True
    assert projection["measurement_ids"] == ["sm-public-verified"]
    assert projection["measurement_keys"] == [0]
    assert "fact-private-verified-measurement" not in projection["fact_ids"]
    assert projection["contains_source_receipts"] is False
    assert projection["contains_evidence_path"] is False
    assert projection["contains_receipt_hash"] is False


@pytest.mark.parametrize(
    ("entity_id", "stem", "he_alt", "en_alt"),
    (
        (
            "ingredient-shoyu-koji",
            "c99-science-shoyu-koji-substrate-v01",
            "מגש קוג׳י לשויו מסויה וחיטה עם מעטה קוג׳י בהיר",
            "Tray of soybean and wheat shoyu koji with a pale koji bloom",
        ),
        (
            "equipment-kioke",
            "c99-science-kioke-wooden-barrel-v01",
            "חבית קיוקה מעץ ארז עם חישוקי במבוק בסדנת תסיסה",
            "Cedar kioke barrel with bamboo hoops in a fermentation workshop",
        ),
        (
            "guide-koji-hydrolysis",
            "c99-science-koji-enzymes-hydrolysis-guide-v01",
            "קוג׳י אורז וקוג׳י לשויו לצד המחשה מושגית של פירוק אנזימטי",
            "Rice koji and shoyu koji beside a conceptual enzymatic-breakdown illustration",
        ),
        (
            "reaction-koji-enzymatic-hydrolysis",
            "c99-science-koji-enzymatic-hydrolysis-v01",
            "שלושה רצפים מושגיים של שרשראות מזון המתפרקות מעל מצע קוג׳י לשויו",
            "Three conceptual food-chain breakdown sequences above shoyu koji substrate",
        ),
        (
            "standard-jas-shoyu-1703",
            "c99-science-jas-1703-shoyu-standard-v01",
            "דוגמאות גוון כלליות של רוטב סויה לצד תיק תקן וכלי בדיקה לא מסומנים",
            "Generic soy-sauce color samples beside an unmarked standards folio and test glassware",
        ),
    ),
)
def test_koji_shoyu_public_visuals_emit_full_and_768_variants(
    v6_contract_payload: dict,
    entity_id: str,
    stem: str,
    he_alt: str,
    en_alt: str,
) -> None:
    visuals = v6_contract_payload["asset_projections"][entity_id]
    base = "https://complete99.test/wp-content/plugins/complete99-platform/"
    expected_urls = {
        "url": f"{base}assets/images/science/{stem}.webp",
        "avif_url": f"{base}assets/images/science/{stem}.avif",
        "small_url": f"{base}assets/images/science/{stem}-768.webp",
        "small_avif_url": f"{base}assets/images/science/{stem}-768.avif",
        "width": 1536,
        "height": 1024,
    }
    assert visuals["en"] == {**expected_urls, "alt": en_alt}
    assert visuals["he"] == {**expected_urls, "alt": he_alt}


def _make_platform_fixture(registry_source: str) -> Path:
    temporary = Path(tempfile.mkdtemp(prefix="complete99-science-v6-"))
    data = temporary / "data"
    data.mkdir(parents=True)
    (data / "culinary-science-pilot.php").write_text(
        registry_source,
        encoding="utf-8",
    )
    return temporary


def _registry_bootstrap(platform_dir: Path) -> str:
    return f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{_php_path(platform_dir, directory=True)}');
{_wp_error_stub()}
require '{_php_path(SCIENCE_CLASS)}';
"""


def test_registry_without_platform_constant_is_bounded_and_nonfatal() -> None:
    result = _run_php_json(
        r"""
define('ABSPATH', __DIR__);
__WP_ERROR_STUB__
require '__SCIENCE_CLASS__';
$registry = Complete99_Culinary_Science::registry(true);
echo json_encode(array(
    'code' => is_wp_error($registry) ? $registry->get_error_code() : '',
    'message' => is_wp_error($registry) ? $registry->get_error_message() : '',
    'status' => Complete99_Culinary_Science::status(),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
""".replace("__WP_ERROR_STUB__", _wp_error_stub()).replace(
            "__SCIENCE_CLASS__", _php_path(SCIENCE_CLASS)
        )
    )

    assert result["code"] == "complete99_science_registry_missing"
    assert result["message"] == "The culinary science registry is unavailable."
    assert result["status"] == {
        "ready": False,
        "version": "",
        "entity_count": 0,
        "public_count": 0,
        "cluster_count": 0,
        "digest": "",
    }


@pytest.mark.parametrize(
    "registry_source",
    (
        "<?php\nthis is deliberately malformed PHP",
        "<?php\nthrow new RuntimeException('private-registry-secret');\n",
    ),
    ids=("parse-error", "throwing-registry"),
)
def test_malformed_or_throwing_registry_is_bounded_and_nonfatal(
    registry_source: str,
) -> None:
    fixture = _make_platform_fixture(registry_source)
    try:
        result = _run_php_json(
            _registry_bootstrap(fixture)
            + r"""
$registry = Complete99_Culinary_Science::registry(true);
echo json_encode(array(
    'code' => is_wp_error($registry) ? $registry->get_error_code() : '',
    'message' => is_wp_error($registry) ? $registry->get_error_message() : '',
    'data' => is_wp_error($registry) ? $registry->get_error_data() : array(),
    'status' => Complete99_Culinary_Science::status(),
    'public_bundle' => Complete99_Culinary_Science::public_page_bundle_for_path('/'),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
""",
        )
    finally:
        shutil.rmtree(fixture)

    assert result["code"] == "complete99_science_registry_invalid"
    assert result["message"] == (
        "The culinary science registry failed its schema contract."
    )
    assert result["data"] == {"status": 500}
    assert "private-registry-secret" not in json.dumps(result)
    assert str(fixture) not in json.dumps(result)
    assert result["status"] == {
        "ready": False,
        "version": "",
        "entity_count": 0,
        "public_count": 0,
        "cluster_count": 0,
        "digest": "",
    }
    assert result["public_bundle"] == []


def test_failed_fresh_reload_clears_previously_valid_caches() -> None:
    registry_source = f"""<?php
$registry = require '{_php_path(SCIENCE_DATA)}';
$registry['schema'] = 'complete99-culinary-science-registry/v6';
$registry['source_receipts'] = array();
foreach ($registry['entities'] as &$entity) {{
    foreach ($entity['facts'] as &$fact) {{
        $fact['scientific_measurements'] = array();
    }}
    unset($fact);
}}
unset($entity);
return $registry;
"""
    fixture = _make_platform_fixture(registry_source)
    fixture_data = fixture / "data" / "culinary-science-pilot.php"
    try:
        result = _run_php_json(
            _registry_bootstrap(fixture)
            + f"""
$first = Complete99_Culinary_Science::registry(true);
file_put_contents(
    '{_php_path(fixture_data)}',
    "<?php\\nthrow new RuntimeException('cache-secret');\\n"
);
$fresh_failure = Complete99_Culinary_Science::registry(true);
$cached_retry = Complete99_Culinary_Science::registry();
echo json_encode(array(
    'first_valid' => !is_wp_error($first),
    'fresh_failure' => is_wp_error($fresh_failure),
    'cached_retry_failure' => is_wp_error($cached_retry),
    'fresh_data' => is_wp_error($fresh_failure) ? $fresh_failure->get_error_data() : array(),
    'retry_data' => is_wp_error($cached_retry) ? $cached_retry->get_error_data() : array(),
    'status' => Complete99_Culinary_Science::status(),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
""",
        )
    finally:
        shutil.rmtree(fixture)

    assert result["first_valid"] is True
    assert result["fresh_failure"] is True
    assert result["cached_retry_failure"] is True
    assert result["fresh_data"] == {"status": 500}
    assert result["retry_data"] == {"status": 500}
    assert result["status"]["ready"] is False
    assert "cache-secret" not in json.dumps(result)
