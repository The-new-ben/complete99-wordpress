from __future__ import annotations

import json
import shutil
import subprocess
import tempfile
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
BINDING_CLASS = (
    PLUGIN / "includes" / "class-complete99-cross-domain-bindings.php"
)
BINDING_DATA = PLUGIN / "data" / "cross-domain-bindings.php"
DECISION_DATA = PLUGIN / "data" / "cross-domain-binding-decisions.php"

INPUT_FILES = (
    "consumer-menu.php",
    "dish-entity-trees.php",
    "catalog-product-seeds.php",
    "culinary-science-pilot.php",
    "live-catalog-products.php",
    "live-catalog-relations.php",
)

EXPECTED_DIGESTS = {
    "consumer_menu": "134da0d6cefe66790dc4551e4aa95453bfa58b80667c68749ec3d7791bca869f",
    "dish_entity_trees": "4d7a19fba4e0cb4b17b86542bb0229341830bc79debf8ac13cb545ec2329c264",
    "catalog_product_seeds": "6049f5d6d951df273481f6200dca6c1ba895817c0345e1b74a5424be2fb1b132",
    "culinary_science": "677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54",
    "live_catalog_products": "56a8fbddade21570f874e19a2dc7f8562edf0ab6b11f9d14b79a95116391339f",
    "live_catalog_relations": "debdd5785e539c55ab9b0ab53c911ae3d7f842dc3ede9f077d59d4ab96c9faf5",
}


def _php_path(path: Path, *, directory: bool = False) -> str:
    value = path.as_posix()
    if directory:
        value = value.rstrip("/") + "/"
    return value.replace("\\", "\\\\").replace("'", "\\'")


def _run_php_json(script: str, *, timeout: int = 120) -> object:
    if not shutil.which("php"):
        pytest.skip("PHP is required for executable binding contract checks")
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
    return """
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
"""


def _base_bootstrap(platform_dir: Path = PLUGIN) -> str:
    return f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{_php_path(platform_dir, directory=True)}');
{_wp_error_stub()}
require '{_php_path(BINDING_CLASS)}';
"""


def _make_platform_fixture(
    registry_body: str = "", *, overlay_body: str = ""
) -> Path:
    temporary = Path(tempfile.mkdtemp(prefix="complete99-bindings-"))
    data = temporary / "data"
    data.mkdir(parents=True)
    for filename in INPUT_FILES:
        shutil.copy2(PLUGIN / "data" / filename, data / filename)
    shutil.copytree(
        PLUGIN / "data" / "culinary-science",
        data / "culinary-science",
    )
    (data / "cross-domain-bindings.php").write_text(
        "<?php\n"
        "defined( 'ABSPATH' ) || exit;\n"
        f"$registry = require '{_php_path(BINDING_DATA)}';\n"
        f"{registry_body}\n"
        "return $registry;\n",
        encoding="utf-8",
    )
    if overlay_body:
        (data / "cross-domain-binding-decisions.php").write_text(
            "<?php\n"
            "defined( 'ABSPATH' ) || exit;\n"
            f"$overlay = require '{_php_path(DECISION_DATA)}';\n"
            f"{overlay_body}\n"
            "return $overlay;\n",
            encoding="utf-8",
        )
    else:
        shutil.copy2(
            DECISION_DATA,
            data / "cross-domain-binding-decisions.php",
        )
    return temporary


def test_checked_in_registry_is_strictly_valid_and_nonprojecting() -> None:
    result = _run_php_json(
        _base_bootstrap()
        + f"""
$registry = require '{_php_path(BINDING_DATA)}';
$overlay = require '{_php_path(DECISION_DATA)}';
$validation = Complete99_Cross_Domain_Bindings::validate_registry($registry);
$overlay_validation = Complete99_Cross_Domain_Bindings::validate_decision_overlay($overlay, $registry);
$kind_counts = array_count_values(array_column($registry['records'], 'kind'));
$resolution_counts = array_count_values(array_column($registry['records'], 'resolution_state'));
$candidate_count = 0;
$component_scopes = array();
$component_codes = array();
$candidate_kinds = array();
foreach ($registry['records'] as $record) {{
    $candidate_count += count($record['candidates']);
    if (!empty($record['candidates'])) {{ $candidate_kinds[$record['kind']] = true; }}
    if ('menu_component_science_entity' === $record['kind']) {{
        $component_scopes[$record['subject']['scope_entity_id'] . "\\0" . $record['subject']['entity_id']] = true;
        $component_codes[$record['subject']['entity_id']] = true;
    }}
}}
$ids = array_column($registry['records'], 'id');
$sorted_ids = $ids;
sort($sorted_ids, SORT_STRING);
echo json_encode(array(
    'valid' => true === $validation,
    'overlay_valid' => true === $overlay_validation,
    'kind_counts' => $kind_counts,
    'resolution_counts' => $resolution_counts,
    'candidate_count' => $candidate_count,
    'candidate_kinds' => array_keys($candidate_kinds),
    'component_scope_count' => count($component_scopes),
    'global_component_code_count' => count($component_codes),
    'records_sorted' => $ids === $sorted_ids,
    'status' => Complete99_Cross_Domain_Bindings::status(true),
    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
    'editorial' => Complete99_Cross_Domain_Bindings::editorial_snapshot(),
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
"""
    )

    assert result["valid"] is True
    assert result["overlay_valid"] is True
    assert result["kind_counts"] == {
        "menu_dish_science_dish": 12,
        "menu_component_science_entity": 47,
        "woo_product_science_entity": 36,
    }
    assert result["resolution_counts"] == {"unresolved": 95}
    assert result["candidate_count"] == 11
    assert result["candidate_kinds"] == ["woo_product_science_entity"]
    assert result["component_scope_count"] == 47
    assert result["global_component_code_count"] == 35
    assert result["records_sorted"] is True
    assert result["status"] == {
        "schema": "complete99-cross-domain-binding-registry/v3",
        "version": "complete99-cross-domain-bindings-2026.08.08.v3",
        "registry_valid": True,
        "record_count": 95,
        "dish_subject_count": 12,
        "component_subject_count": 47,
        "product_subject_count": 36,
        "linked_count": 0,
        "no_match_count": 0,
        "unresolved_count": 95,
        "decision_count": 0,
        "recognized_reviewer_authority_count": 0,
        "public_navigation_count": 0,
        "public_product_navigation_count": 0,
    }
    assert result["indexes"] == {
        "menu_dish_science_dish": [],
        "menu_component_science_entity": [],
        "woo_product_science_entity": [],
        "public_navigation": [],
        "public_product_navigation": [],
    }
    assert result["editorial"]["decision_overlay"] == {
        "schema": "complete99-cross-domain-binding-decision-overlay/v1",
        "version": "complete99-cross-domain-binding-decisions-2026.08.08.v1",
        "valid": True,
        "decision_count": 0,
        "recognized_reviewer_authority_count": 0,
    }


def test_input_contracts_use_canonical_logical_payload_digests() -> None:
    source_paths = {
        "consumer_menu": "consumer-menu.php",
        "dish_entity_trees": "dish-entity-trees.php",
        "catalog_product_seeds": "catalog-product-seeds.php",
        "culinary_science": "culinary-science-pilot.php",
        "live_catalog_products": "live-catalog-products.php",
        "live_catalog_relations": "live-catalog-relations.php",
    }
    php_sources = ",\n".join(
        f"'{key}' => require '{_php_path(PLUGIN / 'data' / filename)}'"
        for key, filename in source_paths.items()
    )
    result = _run_php_json(
        _base_bootstrap()
        + f"""
$method = new ReflectionMethod('Complete99_Cross_Domain_Bindings', 'canonical_payload_digest');
$method->setAccessible(true);
$payloads = array({php_sources});
$digests = array();
foreach ($payloads as $key => $payload) {{
    $digests[$key] = $method->invoke(null, $payload);
}}
$registry = require '{_php_path(BINDING_DATA)}';
$declared = array();
foreach ($registry['input_contracts'] as $key => $contract) {{
    $declared[$key] = $contract['payload_sha256'];
}}
echo json_encode(array('computed' => $digests, 'declared' => $declared), JSON_UNESCAPED_SLASHES);
""",
        timeout=180,
    )

    assert result["computed"] == EXPECTED_DIGESTS
    assert result["declared"] == EXPECTED_DIGESTS
    source = BINDING_CLASS.read_text(encoding="utf-8")
    assert "JSON_PRESERVE_ZERO_FRACTION" not in source
    assert "JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE" in source


def test_v3_rejects_science_payload_and_declared_digest_drifting_together() -> None:
    fixture = _make_platform_fixture(
        r"""
$science = require __DIR__ . '/culinary-science-pilot.php';
$digest_method = new ReflectionMethod(
    'Complete99_Cross_Domain_Bindings',
    'canonical_payload_digest'
);
$digest_method->setAccessible(true);
$registry['input_contracts']['culinary_science']['payload_sha256'] =
    $digest_method->invoke(null, $science);
"""
    )
    fixture_science = fixture / "data" / "culinary-science-pilot.php"
    fixture_science.write_text(
        "<?php\n"
        f"$registry = require '{_php_path(PLUGIN / 'data' / 'culinary-science-pilot.php')}';\n"
        "$registry['generated_at'] = '2026-08-07';\n"
        "return $registry;\n",
        encoding="utf-8",
    )
    try:
        result = _run_php_json(
            _base_bootstrap(fixture)
            + r"""
$registry = Complete99_Cross_Domain_Bindings::registry(true);
echo json_encode(array(
    'error_code' => is_wp_error($registry) ? $registry->get_error_code() : '',
    'status' => Complete99_Cross_Domain_Bindings::status(),
    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
""",
            timeout=180,
        )
    finally:
        shutil.rmtree(fixture)

    assert result["error_code"] == "complete99_cross_domain_seed_invalid"
    assert result["status"]["registry_valid"] is False
    assert all(index == [] for index in result["indexes"].values())


def test_validator_rejects_shape_coverage_state_evidence_and_edge_drift() -> None:
    result = _run_php_json(
        _base_bootstrap()
        + f"""
$base = require '{_php_path(BINDING_DATA)}';
function binding_outcome($candidate) {{
    $result = Complete99_Cross_Domain_Bindings::validate_registry($candidate);
    if (true === $result) {{ return 'valid'; }}
    $data = is_wp_error($result) ? $result->get_error_data() : array();
    return isset($data['path']) ? $data['path'] : 'invalid';
}}
function record_offset($records, $id) {{
    foreach ($records as $offset => $record) {{ if ($id === $record['id']) {{ return $offset; }} }}
    throw new RuntimeException('record-not-found');
}}
function promote_bulgur(&$candidate, $projection_scope = 'private_only', $review_state = 'verified') {{
    $offset = record_offset($candidate['records'], 'woo-product--product-bulgur-fine-500g');
    $pending = $candidate['records'][$offset]['candidates'][0];
    $candidate['records'][$offset]['resolution_state'] = 'linked';
    $candidate['records'][$offset]['targets'] = array(array(
        'registry' => 'culinary_science',
        'entity_type' => $pending['entity_type'],
        'entity_id' => $pending['entity_id'],
        'relation' => 'retail_instance_of',
        'projection_scope' => $projection_scope,
        'evidence_refs' => $candidate['records'][$offset]['decision_evidence_refs'],
    ));
    $candidate['records'][$offset]['candidates'] = array();
    $candidate['records'][$offset]['decision_note'] = array(
        'he' => 'החלטה מפורשת ומתועדת.',
        'en' => 'An explicit, documented decision.',
    );
    $candidate['records'][$offset]['review'] = array(
        'state' => $review_state,
        'reviewer_id' => 'reviewer-binding',
        'reviewed_at' => '2026-08-08',
        'next_review_at' => '2027-08-08',
    );
    $candidate['records'][$offset]['valid_from'] = '2026-08-08';
    return $offset;
}}
$cases = array();

$candidate = $base;
$candidate['unexpected'] = true;
$cases['top_level_key'] = binding_outcome($candidate);

$candidate = $base;
$candidate['schema'] = 'complete99-cross-domain-binding-registry/v1';
$cases['superseded_registry_schema'] = binding_outcome($candidate);

$candidate = $base;
$candidate['version'] = 'complete99-cross-domain-bindings-2026.08.08.v1';
$cases['superseded_registry_version'] = binding_outcome($candidate);

$candidate = $base;
$candidate['controlled_vocabulary']['binding_kinds'] = array_reverse($candidate['controlled_vocabulary']['binding_kinds']);
$cases['vocabulary_order'] = binding_outcome($candidate);

$candidate = $base;
$candidate['input_contracts']['culinary_science']['source_schema'] = 'complete99-culinary-science-registry/v5';
$cases['superseded_science_schema'] = binding_outcome($candidate);

$candidate = $base;
$candidate['input_contracts']['culinary_science']['source_version'] = 'culinary-science-2026.08.08.v19';
$cases['superseded_science_version'] = binding_outcome($candidate);

$candidate = $base;
$candidate['input_contracts']['culinary_science']['payload_sha256'] = str_repeat('0', 64);
$cases['source_digest'] = binding_outcome($candidate);

$candidate = $base;
$candidate['records'][0]['unexpected'] = true;
$cases['record_key'] = binding_outcome($candidate);

$candidate = $base;
array_pop($candidate['records']);
$cases['coverage_missing'] = binding_outcome($candidate);

$candidate = $base;
$swap = $candidate['records'][0];
$candidate['records'][0] = $candidate['records'][1];
$candidate['records'][1] = $swap;
$cases['record_order'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'menu-component--menu-reference-sabich--ingredient-egg');
$candidate['records'][$offset]['subject']['scope_entity_id'] = 'menu-reference-sabtucha';
$cases['component_scope'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'woo-product--product-bulgur-fine-500g');
$candidate['records'][$offset]['candidates'][0]['entity_id'] = 'ingredient-kombu';
$cases['nonreciprocal_candidate'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'woo-product--product-bulgur-fine-500g');
$candidate['records'][$offset]['candidates'][0]['entity_type'] = 'equipment';
$cases['candidate_type'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'menu-dish--menu-reference-sabich');
$candidate['records'][$offset]['candidates'][] = array(
    'registry' => 'culinary_science', 'entity_type' => 'dish',
    'entity_id' => 'dish-edomae-nigiri', 'state' => 'pending_review',
    'reason_code' => 'insufficient_evidence',
);
$cases['dish_candidate_inference'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'woo-product--product-bulgur-fine-500g');
$candidate['records'][$offset]['decision_evidence_refs'][1] = $candidate['records'][$offset]['decision_evidence_refs'][0];
$cases['duplicate_evidence'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'woo-product--product-bulgur-fine-500g');
$candidate['records'][$offset]['decision_evidence_refs'] = array();
$cases['candidate_without_evidence'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'woo-product--product-bulgur-fine-500g');
$candidate['records'][$offset]['review']['reviewer_id'] = 'reviewer-binding';
$cases['unreviewed_with_reviewer'] = binding_outcome($candidate);

$candidate = $base;
$offset = promote_bulgur($candidate);
$candidate['records'][$offset]['targets'][0]['entity_id'] = 'ingredient-does-not-exist';
$cases['target_missing'] = binding_outcome($candidate);

$candidate = $base;
$offset = promote_bulgur($candidate);
$candidate['records'][$offset]['targets'][0]['entity_type'] = 'equipment';
$cases['target_type'] = binding_outcome($candidate);

$candidate = $base;
$offset = promote_bulgur($candidate);
$candidate['records'][$offset]['targets'][] = $candidate['records'][$offset]['targets'][0];
$cases['duplicate_conflicting_targets'] = binding_outcome($candidate);

$candidate = $base;
$offset = promote_bulgur($candidate, 'public_product_navigation', 'source_reviewed');
$cases['public_without_verified_review'] = binding_outcome($candidate);

$candidate = $base;
$offset = promote_bulgur($candidate);
$candidate['records'][$offset]['decision_evidence_refs'] = array_slice($candidate['records'][$offset]['decision_evidence_refs'], 1);
$cases['linked_without_science_evidence'] = binding_outcome($candidate);

$candidate = $base;
$offset = promote_bulgur($candidate);
$candidate['records'][$offset]['candidates'][] = array(
    'registry' => 'culinary_science', 'entity_type' => 'ingredient',
    'entity_id' => 'ingredient-syrian-bulgur', 'state' => 'rejected',
    'reason_code' => 'duplicate_conflict',
);
$cases['target_candidate_conflict'] = binding_outcome($candidate);

$candidate = $base;
$offset = record_offset($candidate['records'], 'woo-product--product-bulgur-fine-500g');
$candidate['records'][$offset]['resolution_state'] = 'no_match';
$candidate['records'][$offset]['review'] = array('state'=>'source_reviewed','reviewer_id'=>'reviewer-binding','reviewed_at'=>'2026-08-08','next_review_at'=>'2027-08-08');
$candidate['records'][$offset]['decision_note'] = array('he'=>'אין התאמה מאושרת.','en'=>'No match is approved.');
$candidate['records'][$offset]['valid_from'] = '2026-08-08';
$cases['no_match_pending_candidate'] = binding_outcome($candidate);

echo json_encode($cases, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
""",
        timeout=180,
    )

    assert result
    assert set(result) == {
        "top_level_key",
        "superseded_registry_schema",
        "superseded_registry_version",
        "vocabulary_order",
        "superseded_science_schema",
        "superseded_science_version",
        "source_digest",
        "record_key",
        "coverage_missing",
        "record_order",
        "component_scope",
        "nonreciprocal_candidate",
        "candidate_type",
        "dish_candidate_inference",
        "duplicate_evidence",
        "candidate_without_evidence",
        "unreviewed_with_reviewer",
        "target_missing",
        "target_type",
        "duplicate_conflicting_targets",
        "public_without_verified_review",
        "linked_without_science_evidence",
        "target_candidate_conflict",
        "no_match_pending_candidate",
    }
    assert all(outcome != "valid" for outcome in result.values()), result


def test_valid_shaped_decision_without_recognized_reviewer_cannot_merge() -> None:
    result = _run_php_json(
        _base_bootstrap()
        + f"""
$seed = require '{_php_path(BINDING_DATA)}';
$overlay = require '{_php_path(DECISION_DATA)}';
$digest = new ReflectionMethod('Complete99_Cross_Domain_Bindings', 'canonical_payload_digest');
$digest->setAccessible(true);
foreach ($seed['records'] as $record) {{
    if ('woo-product--product-bulgur-fine-500g' !== $record['id']) {{ continue; }}
    $candidate = $record['candidates'][0];
    $decision = array(
        'record_id' => $record['id'],
        'seed_record_sha256' => $digest->invoke(null, $record),
        'resolution_state' => 'linked',
        'targets' => array(array(
            'registry' => 'culinary_science',
            'entity_type' => $candidate['entity_type'],
            'entity_id' => $candidate['entity_id'],
            'relation' => 'reference_only',
            'projection_scope' => 'private_only',
            'evidence_refs' => $record['decision_evidence_refs'],
        )),
        'candidates' => array(),
        'decision_evidence_refs' => $record['decision_evidence_refs'],
        'decision_note' => array(
            'he' => 'החלטת בדיקה מפורשת שאינה אישור פרסום.',
            'en' => 'An explicit review decision that is not publication approval.',
        ),
        'review' => array(
            'state' => 'verified',
            'reviewer_id' => 'person:unrecognized-reviewer',
            'reviewed_at' => '2026-08-08',
            'next_review_at' => '2027-08-08',
        ),
        'valid_from' => '2026-08-08',
        'valid_to' => '',
    );
    break;
}}
$overlay['decision_count'] = 1;
$overlay['decisions'] = array($decision);
$overlay['decisions_sha256'] = $digest->invoke(null, $overlay['decisions']);
$validation = Complete99_Cross_Domain_Bindings::validate_decision_overlay($overlay, $seed);
$data = is_wp_error($validation) ? $validation->get_error_data() : array();
$pinned = Complete99_Cross_Domain_Bindings::validate_and_merge_overlay($seed, $overlay, true);
$pinned_data = is_wp_error($pinned) ? $pinned->get_error_data() : array();
echo json_encode(array(
    'valid' => true === $validation,
    'path' => $data['path'] ?? '',
    'pinned_path' => $pinned_data['path'] ?? '',
    'status' => Complete99_Cross_Domain_Bindings::status(true),
    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
    'targets' => Complete99_Cross_Domain_Bindings::public_targets_for_subject(
        'woo_product_science_entity', 'product-bulgur-fine-500g'
    ),
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
""",
        timeout=180,
    )

    assert result["valid"] is False
    assert result["path"].endswith(".review.reviewer_authority")
    assert result["pinned_path"] == "decision_overlay.payload_sha256"
    assert result["status"]["registry_valid"] is True
    assert result["status"]["decision_count"] == 0
    assert result["status"]["recognized_reviewer_authority_count"] == 0
    assert result["status"]["unresolved_count"] == 95
    assert all(index == [] for index in result["indexes"].values())
    assert result["targets"] == []


def test_overlay_rejects_tamper_extra_missing_conflict_and_candidate_drift() -> None:
    result = _run_php_json(
        _base_bootstrap()
        + f"""
$seed = require '{_php_path(BINDING_DATA)}';
$base = require '{_php_path(DECISION_DATA)}';
$digest = new ReflectionMethod('Complete99_Cross_Domain_Bindings', 'canonical_payload_digest');
$digest->setAccessible(true);
$outcome = static function ($overlay) use ($seed) {{
    $validation = Complete99_Cross_Domain_Bindings::validate_decision_overlay($overlay, $seed);
    if (true === $validation) {{ return 'valid'; }}
    $data = is_wp_error($validation) ? $validation->get_error_data() : array();
    return $data['path'] ?? 'invalid';
}};
$with_decisions = static function ($overlay, $decisions) use ($digest) {{
    $overlay['decision_count'] = count($decisions);
    $overlay['decisions'] = $decisions;
    $overlay['decisions_sha256'] = $digest->invoke(null, $decisions);
    return $overlay;
}};
foreach ($seed['records'] as $record) {{
    if ('woo-product--product-bulgur-fine-500g' !== $record['id']) {{ continue; }}
    $candidate = $record['candidates'][0];
    $decision = array(
        'record_id' => $record['id'],
        'seed_record_sha256' => $digest->invoke(null, $record),
        'resolution_state' => 'linked',
        'targets' => array(array(
            'registry' => 'culinary_science',
            'entity_type' => $candidate['entity_type'],
            'entity_id' => $candidate['entity_id'],
            'relation' => 'reference_only',
            'projection_scope' => 'private_only',
            'evidence_refs' => $record['decision_evidence_refs'],
        )),
        'candidates' => array(),
        'decision_evidence_refs' => $record['decision_evidence_refs'],
        'decision_note' => array(
            'he' => 'החלטת בדיקה מפורשת שאינה אישור פרסום.',
            'en' => 'An explicit review decision that is not publication approval.',
        ),
        'review' => array(
            'state' => 'verified',
            'reviewer_id' => 'person:unrecognized-reviewer',
            'reviewed_at' => '2026-08-08',
            'next_review_at' => '2027-08-08',
        ),
        'valid_from' => '2026-08-08',
        'valid_to' => '',
    );
    break;
}}
$cases = array();

$candidate_overlay = $base;
$candidate_overlay['unexpected'] = true;
$cases['extra_envelope_key'] = $outcome($candidate_overlay);

$candidate_overlay = $base;
unset($candidate_overlay['input_contracts_sha256']);
$cases['missing_envelope_key'] = $outcome($candidate_overlay);

$candidate_overlay = $base;
$candidate_overlay['seed_contract']['payload_sha256'] = str_repeat('0', 64);
$cases['seed_payload_digest'] = $outcome($candidate_overlay);

$candidate_overlay = $base;
$candidate_overlay['seed_contract']['record_ids_sha256'] = str_repeat('0', 64);
$cases['record_ids_digest'] = $outcome($candidate_overlay);

$candidate_overlay = $base;
$candidate_overlay['input_contracts_sha256'] = str_repeat('0', 64);
$cases['input_contracts_digest'] = $outcome($candidate_overlay);

$candidate_overlay = $base;
$candidate_overlay['reviewer_authorities_sha256'] = str_repeat('0', 64);
$cases['reviewer_authority_digest'] = $outcome($candidate_overlay);

$candidate_overlay = $base;
$candidate_overlay['decision_count'] = 1;
$cases['decision_count'] = $outcome($candidate_overlay);

$candidate_overlay = $base;
$candidate_overlay['decisions_sha256'] = str_repeat('0', 64);
$cases['decisions_digest'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['record_id'] = 'woo-product--does-not-exist';
$candidate_overlay = $with_decisions($base, array($changed));
$cases['unknown_record'] = $outcome($candidate_overlay);

$changed = $decision;
unset($changed['valid_to']);
$candidate_overlay = $with_decisions($base, array($changed));
$cases['missing_decision_field'] = $outcome($candidate_overlay);

$candidate_overlay = $with_decisions($base, array($decision, $decision));
$cases['duplicate_conflict'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['seed_record_sha256'] = str_repeat('0', 64);
$candidate_overlay = $with_decisions($base, array($changed));
$cases['seed_record_digest'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['targets'] = array();
$candidate_overlay = $with_decisions($base, array($changed));
$cases['candidate_disposition_missing'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['targets'][0]['relation'] = 'retail_instance_of';
$candidate_overlay = $with_decisions($base, array($changed));
$cases['bulgur_scope_mismatch'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['review']['reviewer_id'] = '';
$candidate_overlay = $with_decisions($base, array($changed));
$cases['missing_reviewer'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['decision_evidence_refs'] = array();
$candidate_overlay = $with_decisions($base, array($changed));
$cases['missing_evidence'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['decision_note'] = array('he' => '', 'en' => '');
$candidate_overlay = $with_decisions($base, array($changed));
$cases['missing_bilingual_notes'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['review']['reviewed_at'] = '';
$candidate_overlay = $with_decisions($base, array($changed));
$cases['missing_review_date'] = $outcome($candidate_overlay);

$changed = $decision;
$changed['valid_from'] = '';
$candidate_overlay = $with_decisions($base, array($changed));
$cases['missing_valid_from'] = $outcome($candidate_overlay);

$candidate_overlay = $with_decisions($base, array($decision));
$cases['unrecognized_reviewer'] = $outcome($candidate_overlay);

echo json_encode($cases, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
""",
        timeout=180,
    )

    assert set(result) == {
        "extra_envelope_key",
        "missing_envelope_key",
        "seed_payload_digest",
        "record_ids_digest",
        "input_contracts_digest",
        "reviewer_authority_digest",
        "decision_count",
        "decisions_digest",
        "unknown_record",
        "missing_decision_field",
        "duplicate_conflict",
        "seed_record_digest",
        "candidate_disposition_missing",
        "bulgur_scope_mismatch",
        "missing_reviewer",
        "missing_evidence",
        "missing_bilingual_notes",
        "missing_review_date",
        "missing_valid_from",
        "unrecognized_reviewer",
    }
    assert all(outcome != "valid" for outcome in result.values()), result
    assert result["duplicate_conflict"].endswith(".record_id.conflict")
    assert result["seed_record_digest"].endswith(".seed_record_sha256")
    assert result["candidate_disposition_missing"].endswith(
        ".candidate_dispositions"
    )
    assert result["bulgur_scope_mismatch"].endswith(".scope_mismatch")
    assert result["unrecognized_reviewer"].endswith(
        ".review.reviewer_authority"
    )


def test_invalid_registry_returns_only_empty_public_safe_state() -> None:
    fixture = _make_platform_fixture("$registry['records'][0]['unexpected'] = true;")
    try:
        result = _run_php_json(
            _base_bootstrap(fixture)
            + """
$registry = Complete99_Cross_Domain_Bindings::registry(true);
echo json_encode(array(
    'error_code' => is_wp_error($registry) ? $registry->get_error_code() : '',
    'status' => Complete99_Cross_Domain_Bindings::status(),
    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
    'editorial' => Complete99_Cross_Domain_Bindings::editorial_snapshot(),
    'targets' => Complete99_Cross_Domain_Bindings::public_targets_for_subject(
        'woo_product_science_entity', 'product-bulgur-fine-500g'
    ),
), JSON_UNESCAPED_SLASHES);
""",
            timeout=180,
        )
    finally:
        shutil.rmtree(fixture)

    assert result["error_code"] == "complete99_cross_domain_seed_invalid"
    assert result["status"] == {
        "schema": "complete99-cross-domain-binding-registry/v3",
        "version": "complete99-cross-domain-bindings-2026.08.08.v3",
        "registry_valid": False,
        "record_count": 0,
        "dish_subject_count": 0,
        "component_subject_count": 0,
        "product_subject_count": 0,
        "linked_count": 0,
        "no_match_count": 0,
        "unresolved_count": 0,
        "decision_count": 0,
        "recognized_reviewer_authority_count": 0,
        "public_navigation_count": 0,
        "public_product_navigation_count": 0,
    }
    assert result["indexes"] == {
        "menu_dish_science_dish": [],
        "menu_component_science_entity": [],
        "woo_product_science_entity": [],
        "public_navigation": [],
        "public_product_navigation": [],
    }
    assert result["editorial"] == []
    assert result["targets"] == []


@pytest.mark.parametrize(
    "registry_source",
    (
        "<?php\nthis is deliberately malformed PHP",
        "<?php\nthrow new RuntimeException('private-registry-secret');\n",
    ),
    ids=("parse-error", "throwing-registry"),
)
def test_malformed_or_throwing_registry_is_nonfatal_and_fail_closed(
    registry_source: str,
) -> None:
    fixture = _make_platform_fixture("")
    (fixture / "data" / "cross-domain-bindings.php").write_text(
        registry_source,
        encoding="utf-8",
    )
    try:
        result = _run_php_json(
            _base_bootstrap(fixture)
            + """
$registry = Complete99_Cross_Domain_Bindings::registry(true);
echo json_encode(array(
    'error_code' => is_wp_error($registry) ? $registry->get_error_code() : '',
    'error_message' => is_wp_error($registry) ? $registry->get_error_message() : '',
    'error_data' => is_wp_error($registry) ? $registry->get_error_data() : array(),
    'status' => Complete99_Cross_Domain_Bindings::status(),
    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
    'editorial' => Complete99_Cross_Domain_Bindings::editorial_snapshot(),
), JSON_UNESCAPED_SLASHES);
""",
            timeout=180,
        )
    finally:
        shutil.rmtree(fixture)

    assert result["error_code"] == "complete99_cross_domain_registry_invalid"
    assert result["error_message"] == (
        "The cross-domain binding registry failed validation."
    )
    assert result["error_data"] == {"status": 500}
    assert result["status"]["registry_valid"] is False
    assert all(
        value == 0
        for key, value in result["status"].items()
        if key.endswith("_count")
    )
    assert result["indexes"] == {
        "menu_dish_science_dish": [],
        "menu_component_science_entity": [],
        "woo_product_science_entity": [],
        "public_navigation": [],
        "public_product_navigation": [],
    }
    assert result["editorial"] == []
    assert "private-registry-secret" not in json.dumps(result)


@pytest.mark.parametrize(
    "overlay_source",
    (
        "<?php\nthis is deliberately malformed PHP",
        "<?php\nthrow new RuntimeException('private-overlay-secret');\n",
    ),
    ids=("parse-error", "throwing-overlay"),
)
def test_malformed_or_throwing_overlay_is_nonfatal_and_fail_closed(
    overlay_source: str,
) -> None:
    fixture = _make_platform_fixture()
    (fixture / "data" / "cross-domain-binding-decisions.php").write_text(
        overlay_source,
        encoding="utf-8",
    )
    try:
        result = _run_php_json(
            _base_bootstrap(fixture)
            + """
$registry = Complete99_Cross_Domain_Bindings::registry(true);
echo json_encode(array(
    'error_code' => is_wp_error($registry) ? $registry->get_error_code() : '',
    'error_message' => is_wp_error($registry) ? $registry->get_error_message() : '',
    'status' => Complete99_Cross_Domain_Bindings::status(),
    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
    'editorial' => Complete99_Cross_Domain_Bindings::editorial_snapshot(),
), JSON_UNESCAPED_SLASHES);
""",
            timeout=180,
        )
    finally:
        shutil.rmtree(fixture)

    assert result["error_code"] == "complete99_cross_domain_registry_invalid"
    assert result["error_message"] == (
        "The cross-domain binding registry failed validation."
    )
    assert result["status"]["registry_valid"] is False
    assert all(
        value == 0
        for key, value in result["status"].items()
        if key.endswith("_count")
    )
    assert all(index == [] for index in result["indexes"].values())
    assert result["editorial"] == []
    assert "private-overlay-secret" not in json.dumps(result)


def test_missing_overlay_is_nonfatal_and_fail_closed() -> None:
    fixture = _make_platform_fixture()
    (fixture / "data" / "cross-domain-binding-decisions.php").unlink()
    try:
        result = _run_php_json(
            _base_bootstrap(fixture)
            + """
$registry = Complete99_Cross_Domain_Bindings::registry(true);
echo json_encode(array(
    'error_code' => is_wp_error($registry) ? $registry->get_error_code() : '',
    'status' => Complete99_Cross_Domain_Bindings::status(),
    'indexes' => Complete99_Cross_Domain_Bindings::indexes(),
), JSON_UNESCAPED_SLASHES);
""",
            timeout=180,
        )
    finally:
        shutil.rmtree(fixture)

    assert result["error_code"] == "complete99_cross_domain_registry_invalid"
    assert result["status"]["registry_valid"] is False
    assert all(index == [] for index in result["indexes"].values())


def test_implementation_contains_no_similarity_or_transitive_inference_path() -> None:
    source = BINDING_CLASS.read_text(encoding="utf-8").casefold()
    for forbidden in (
        "levenshtein",
        "similar_text",
        "soundex",
        "metaphone",
        "fuzzy",
        "transitive",
        "array_walk_recursive",
    ):
        assert forbidden not in source
    assert "reciprocal_woo_edges" in source
    assert "subject_key" in source
    assert "strlen( $part ) . ':' . $part" in source
