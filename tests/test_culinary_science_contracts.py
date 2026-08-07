from __future__ import annotations

import json
import re
import subprocess
from datetime import datetime
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
BOOTSTRAP = PLUGIN / "complete99-platform.php"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"
SCIENCE_FOUNDATIONS_COLLECTION = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "collections"
    / "japanese-foundations-lab.php"
)
PLATFORM_CLASS = PLUGIN / "includes" / "class-complete99-platform.php"
REST_CLASS = PLUGIN / "includes" / "class-complete99-rest.php"
REVIEW_LAB = PLUGIN / "includes" / "class-complete99-review-lab.php"
SEO_REGISTRY = PLUGIN / "includes" / "class-complete99-seo-registry.php"

EXPECTED_SCHEMA = "complete99-culinary-science-registry/v5"
EXPECTED_VERSION = "culinary-science-2026.08.07.v15"
EXPECTED_PUBLIC_PILOT = {
    "museum-culinary-science",
    "cuisine-japanese-washoku",
    "cuisine-syrian-regional",
    "hub-japanese-foundations-lab",
    "hub-japanese-equipment",
    "hub-japanese-food-science",
    "hub-japanese-ingredients",
    "hub-japanese-techniques",
    "guide-umami-synergy",
    "guide-wasabi-aitc",
    "ingredient-kombu",
    "ingredient-katsuobushi",
    "ingredient-kioke-shoyu",
    "ingredient-kome-koji",
    "ingredient-koji-starter-culture",
    "ingredient-koshihikari-rice",
    "ingredient-fresh-wasabi",
    "ingredient-fresh-dutch-wasabi",
    "ingredient-kito-yuzu",
    "ingredient-hon-mirin",
    "molecule-allyl-isothiocyanate",
    "preparation-ichiban-dashi",
    "equipment-wasabi-grater",
}
EXPECTED_PUBLIC_OFFER_CODES = {
    "ingredient-kombu": "product-rishiri-kombu-100g",
    "ingredient-katsuobushi": "product-honkarebushi-200g",
    "ingredient-kioke-shoyu": "product-yamaroku-tsurubishio-500ml",
    "ingredient-kome-koji": "product-hishiroku-dried-rice-koji-500g",
    "ingredient-koji-starter-culture": "product-hishiroku-chouhaku-kin-20g",
    "ingredient-koshihikari-rice": "product-koshihikari-uozu-2kg",
    "ingredient-fresh-wasabi": "product-fresh-japanese-wasabi-250g",
    "ingredient-fresh-dutch-wasabi": "product-fresh-wasabi-50-60g",
    "ingredient-kito-yuzu": "product-kito-yuzu-juice-100ml",
    "equipment-wasabi-grater": "product-hagane-zame-large",
}
EXPECTED_CLUSTERS = {
    "cluster-culinary-science-museum",
    "cluster-global-culinary-institutions",
    "cluster-japanese-washoku",
    "cluster-lebanese-regional-cuisine",
    "cluster-iraqi-regional-cuisine",
    "cluster-syrian-regional-cuisine",
}
PROFILE_DIMENSIONS = {
    "scientific",
    "cultural",
    "institutional",
    "economic",
    "structural",
}
BUSINESS_MODEL_FIELDS = {
    "revenue_models",
    "customer_segments",
    "value_proposition",
    "pricing_state",
    "market_scope",
    "observation_entity_ids",
    "margin_scenario",
}
MARGIN_SCENARIO_FIELDS = {
    "currency",
    "landed_cost_low",
    "landed_cost_high",
    "retail_price_low",
    "retail_price_high",
    "gross_margin_low",
    "gross_margin_high",
    "basis",
    "confidence",
    "reviewed_at",
}
SCIENTIFIC_MEASUREMENT_FIELDS = {
    "id",
    "property",
    "kind",
    "low",
    "high",
    "value",
    "unit",
    "method",
    "specimen_scope",
    "conditions",
    "confidence",
    "source_ids",
    "measured_at",
}
VISUAL_FIELDS = {
    "asset_state",
    "prompt_en",
    "negative_prompt_en",
    "ratios",
    "shot_list",
    "rights_method",
    "rights_state",
    "rights_receipt_digest",
}
TAXONOMY_FIELDS = {
    "category_path",
    "attributes",
    "tags",
    "public_category_path",
    "public_attribute_keys",
    "public_tags",
}

# These are the deliberate type-first standalone owners for the first pilot.
# English is the exact mirrored path below /en/.
EXPECTED_CANONICAL_OWNERS = {
    "museum-culinary-science": ("topic_hub", "/museum/"),
    "cuisine-japanese-washoku": (
        "cuisine",
        "/museum/japanese-culinary-science/",
    ),
    "cuisine-syrian-regional": (
        "cuisine",
        "/museum/syrian-culinary-science/",
    ),
    "hub-japanese-foundations-lab": (
        "topic_hub",
        "/museum/japanese-culinary-science/foundations/",
    ),
    "hub-global-culinary-institutions": (
        "topic_hub",
        "/museum/global-culinary-institutions/",
    ),
    "dish-edomae-nigiri": ("dish", "/dishes/edomae-nigiri/"),
    "preparation-sushi-shari": ("preparation", "/knowledge/sushi-shari/"),
    "preparation-ichiban-dashi": (
        "preparation",
        "/knowledge/ichiban-dashi/",
    ),
    "ingredient-kombu": ("ingredient", "/ingredients/kombu/"),
    "ingredient-katsuobushi": ("ingredient", "/ingredients/katsuobushi/"),
    "ingredient-shoyu-koji": ("ingredient", "/ingredients/shoyu-koji/"),
    "equipment-kioke": ("equipment", "/knowledge/kioke-barrel-guide/"),
    "ingredient-kioke-shoyu": ("ingredient", "/ingredients/kioke-shoyu/"),
    "ingredient-kome-koji": ("ingredient", "/ingredients/kome-koji/"),
    "ingredient-koji-starter-culture": (
        "ingredient",
        "/ingredients/koji-starter-culture/",
    ),
    "ingredient-fresh-wasabi": (
        "ingredient",
        "/ingredients/fresh-wasabi-rhizome/",
    ),
    "ingredient-fresh-dutch-wasabi": (
        "ingredient",
        "/ingredients/dutch-grown-fresh-wasabi/",
    ),
    "ingredient-kito-yuzu": ("ingredient", "/ingredients/kito-yuzu/"),
    "ingredient-yakinori": (
        "ingredient",
        "/ingredients/premium-yakinori/",
    ),
    "ingredient-hon-mirin": ("ingredient", "/ingredients/hon-mirin/"),
    "equipment-hangiri": ("equipment", "/knowledge/hangiri-guide/"),
    "equipment-yanagiba": ("equipment", "/knowledge/yanagiba-guide/"),
    "ingredient-koshihikari-rice": (
        "ingredient",
        "/ingredients/koshihikari-rice/",
    ),
    "equipment-wasabi-grater": (
        "equipment",
        "/knowledge/wasabi-grater-guide/",
    ),
    "standard-jas-shoyu-1703": (
        "standard",
        "/knowledge/jas-1703-shoyu-standard/",
    ),
    "tradition-washoku": ("tradition", "/traditions/washoku/"),
    "guide-umami-synergy": (
        "guide",
        "/knowledge/umami-synergy-glutamate-imp/",
    ),
    "guide-wasabi-aitc": ("guide", "/knowledge/wasabi-aitc-pungency/"),
    "guide-koji-hydrolysis": (
        "guide",
        "/knowledge/koji-enzymatic-hydrolysis/",
    ),
    "comparison-yanagiba-steels": (
        "comparison",
        "/knowledge/yanagiba-white-2-vs-blue-1/",
    ),
    "guide-japanese-markets": (
        "guide",
        "/knowledge/japanese-culinary-markets/",
    ),
}

HEBREW_RE = re.compile(r"[\u0590-\u05ff]")
LATIN_RE = re.compile(r"[A-Za-z]")


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
        timeout=45,
    )
    return completed.stdout


@pytest.fixture(scope="module")
def science_payload() -> dict:
    plugin_path = _php_path(PLUGIN) + "/"
    class_path = _php_path(SCIENCE_CLASS)
    data_path = _php_path(SCIENCE_DATA)
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
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
require '{class_path}';
$registry = require '{data_path}';
$validation = Complete99_Culinary_Science::validate_registry($registry);
$status = Complete99_Culinary_Science::status();
echo json_encode(array(
    'valid' => true === $validation,
    'validation_error' => is_wp_error($validation)
        ? array(
            'code' => $validation->get_error_code(),
            'message' => $validation->get_error_message(),
            'data' => $validation->data,
        )
        : null,
    'status' => $status,
    'registry' => $registry,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    return json.loads(_run_php(script))


@pytest.fixture(scope="module")
def science_v5_contract_payload() -> dict:
    plugin_path = _php_path(PLUGIN) + "/"
    class_path = _php_path(SCIENCE_CLASS)
    data_path = _php_path(SCIENCE_DATA)
    script = (
        f"define('ABSPATH', __DIR__);\n"
        f"define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');\n"
        + r"""
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
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
"""
        + f"require '{class_path}';\n"
        + f"$registry = require '{data_path}';\n"
        + r"""
function c99_validation_result($candidate) {
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

function c99_public_entity($entity) {
    $entity['surface_class'] = 'public_discovery';
    $entity['publication'] = array(
        'state' => 'approved_public',
        'public_api' => true,
        'public_page' => true,
        'search_index' => false,
        'approved_at' => '2026-08-06',
    );
    $entity['review']['status'] = 'source_reviewed';
    $entity['review']['language_status'] = 'reviewed_bilingual';
    if ('dish' === $entity['type']
        || ('preparation' === $entity['type']
            && 'Recipe' === $entity['seo']['schema_type'])) {
        $entity['review']['culinary_test_status'] = 'tested';
    } elseif ('preparation' === $entity['type']) {
        $entity['review']['culinary_test_status'] = 'not_applicable';
    }
    $entity['visual']['asset_state'] = 'approved';
    $entity['visual']['rights_state'] = 'cleared_generated';
    $entity['visual']['rights_receipt_digest'] = 'sha256:' . str_repeat('a', 64);
    $entity['trust']['attribution_state'] = 'organization_editorial_process';

    $has_public_fact = false;
    foreach ($entity['facts'] as &$fact) {
        if ('editorial_inference' === $fact['evidence_class']) {
            $fact['public_safe'] = false;
        }
        if ($fact['public_safe']) {
            $has_public_fact = true;
        }
    }
    unset($fact);
    if (!$has_public_fact && !empty($entity['facts'])) {
        $entity['facts'][0]['public_safe'] = true;
        if ('editorial_inference' === $entity['facts'][0]['evidence_class']) {
            $entity['facts'][0]['evidence_class'] = 'official_source';
        }
    }
    return $entity;
}

function c99_private_entity($entity) {
    $entity['publication']['state'] = 'private_preview';
    $entity['publication']['public_api'] = false;
    $entity['publication']['public_page'] = false;
    $entity['publication']['search_index'] = false;
    $entity['publication']['approved_at'] = '';
    return $entity;
}

function c99_public_index($registry) {
    $index = array();
    foreach ($registry['entities'] as $entity) {
        $entity = c99_public_entity($entity);
        $index[$entity['id']] = $entity;
    }
    return $index;
}

function c99_children_by_parent($entities_by_id) {
    $children = array();
    foreach ($entities_by_id as $entity) {
        $parent_id = $entity['parent_id'];
        if ('' !== $parent_id) {
            if (!isset($children[$parent_id])) {
                $children[$parent_id] = array();
            }
            $children[$parent_id][] = $entity['id'];
        }
    }
    return $children;
}

function c99_graph_result($entities_by_id, $source_id, $vocabulary) {
    $method = new ReflectionMethod(
        'Complete99_Culinary_Science',
        'validate_entity_graph'
    );
    $method->setAccessible(true);
    try {
        $method->invoke(
            null,
            $entities_by_id[$source_id],
            $entities_by_id,
            c99_children_by_parent($entities_by_id),
            $vocabulary
        );
        return '';
    } catch (Throwable $error) {
        return $error->getMessage();
    }
}

function c99_projection($entity, $registry) {
    $method = new ReflectionMethod(
        'Complete99_Culinary_Science',
        'public_projection'
    );
    $method->setAccessible(true);
    return $method->invoke(null, $entity, $registry, 'en');
}

$mutations = array();

    $visual_mutation_offset = 2;
    $approved_without_rights = $registry;
    $approved_without_rights['entities'][$visual_mutation_offset]['visual']['asset_state'] = 'approved';
$mutations['approved_without_cleared_rights'] = c99_validation_result(
    $approved_without_rights
);

$cleared_without_receipt = $registry;
    $cleared_without_receipt['entities'][$visual_mutation_offset]['visual']['rights_state'] =
    'cleared_generated';
$mutations['cleared_without_receipt'] = c99_validation_result(
    $cleared_without_receipt
);

$malformed_receipt = $registry;
    $malformed_receipt['entities'][$visual_mutation_offset]['visual']['rights_receipt_digest'] =
    'sha256:not-a-valid-receipt';
$mutations['malformed_rights_receipt'] = c99_validation_result(
    $malformed_receipt
);

$approved_with_receipt = $registry;
    $approved_with_receipt['entities'][$visual_mutation_offset]['visual']['asset_state'] = 'approved';
    $approved_with_receipt['entities'][$visual_mutation_offset]['visual']['rights_state'] =
    'cleared_generated';
    $approved_with_receipt['entities'][$visual_mutation_offset]['visual']['rights_receipt_digest'] =
    'sha256:' . str_repeat('b', 64);
$mutations['approved_with_receipt'] = c99_validation_result(
    $approved_with_receipt
);

$compliance_without_source = $registry;
$compliance_mutated = false;
foreach ($compliance_without_source['entities'] as &$entity) {
    if (empty($entity['compliance'])) {
        continue;
    }
    $entity['compliance'][0]['public_safe'] = true;
    $entity['compliance'][0]['source_ids'] = array();
    $compliance_mutated = true;
    break;
}
unset($entity);
$mutations['public_compliance_without_source'] = $compliance_mutated
    ? c99_validation_result($compliance_without_source)
    : array('valid' => true, 'code' => 'mutation_not_prepared', 'path' => '');

$invalid_taxonomy_allowlist = $registry;
$invalid_taxonomy_allowlist['entities'][0]['taxonomy']['public_tags'][] =
    'not-in-private-taxonomy';
$mutations['invalid_taxonomy_allowlist'] = c99_validation_result(
    $invalid_taxonomy_allowlist
);

$graph_errors = array();
$graph_source_id = 'ingredient-kombu';

$parent_case = c99_public_index($registry);
$parent_id = $parent_case[$graph_source_id]['parent_id'];
$parent_case[$parent_id] = c99_private_entity($parent_case[$parent_id]);
$graph_errors['private_parent'] = c99_graph_result(
    $parent_case,
    $graph_source_id,
    $registry['controlled_vocabulary']
);

$relation_case = c99_public_index($registry);
$relation_target_id = '';
    foreach ($relation_case[$graph_source_id]['relations'] as $relation) {
        $relation_target_id = $relation['target_id'];
        break;
    }
    $relation_case[$graph_source_id]['relations'][0]['public_safe'] = true;
$relation_case[$relation_target_id] = c99_private_entity(
    $relation_case[$relation_target_id]
);
$graph_errors['private_relation_target'] = c99_graph_result(
    $relation_case,
    $graph_source_id,
    $registry['controlled_vocabulary']
);

$link_case = c99_public_index($registry);
$link_target_id = 'institution-bishulim';
$link_case[$graph_source_id]['seo']['link_plan'][] = array(
    'target_id' => $link_target_id,
    'purpose' => 'contract-test',
    'anchor' => array('he' => 'בדיקה', 'en' => 'Test'),
    'placement' => 'related_module',
    'required' => false,
    'public_safe' => true,
    'basis_relation_id' => '',
    'evidence_state' => 'verified',
);
$link_case[$link_target_id] = c99_private_entity($link_case[$link_target_id]);
$graph_errors['private_internal_link_target'] = c99_graph_result(
    $link_case,
    $graph_source_id,
    $registry['controlled_vocabulary']
);

$semantic_case = c99_public_index($registry);
$semantic_target_id = 'institution-danon';
$semantic_case[$graph_source_id]['seo']['semantic_entity_ids'][] =
    $semantic_target_id;
$semantic_case[$semantic_target_id] = c99_private_entity(
    $semantic_case[$semantic_target_id]
);
$graph_errors['private_semantic_target'] = c99_graph_result(
    $semantic_case,
    $graph_source_id,
    $registry['controlled_vocabulary']
);

$breadcrumb_case = c99_public_index($registry);
    $breadcrumb_source_id = 'ingredient-kombu';
$breadcrumb_case['museum-culinary-science'] = c99_private_entity(
    $breadcrumb_case['museum-culinary-science']
);
$graph_errors['private_breadcrumb_target'] = c99_graph_result(
    $breadcrumb_case,
    $breadcrumb_source_id,
    $registry['controlled_vocabulary']
);

$profile_registry = $registry;
$profile_entity_offset = null;
foreach ($profile_registry['entities'] as $offset => &$entity) {
    if ('cuisine-japanese-washoku' !== $entity['id']) {
        continue;
    }
    $profile_entity_offset = $offset;
    $private_fact_id = $entity['profiles']['structural']['fact_ids'][0];
    foreach ($entity['facts'] as &$fact) {
        if ($private_fact_id === $fact['id']) {
            $fact['public_safe'] = false;
        }
    }
    unset($fact);
    break;
}
unset($entity);
$profile_projection = c99_projection(
    $profile_registry['entities'][$profile_entity_offset],
    $profile_registry
);

$taxonomy_registry = $registry;
$taxonomy_entity_offset = null;
foreach ($taxonomy_registry['entities'] as $offset => &$entity) {
    if ('ingredient-kombu' !== $entity['id']) {
        continue;
    }
    $taxonomy_entity_offset = $offset;
    $entity['taxonomy']['public_category_path'] = array(
        'world-cuisines',
        'japan',
    );
    $entity['taxonomy']['public_attribute_keys'] = array('pa_flavor_profile');
    $entity['taxonomy']['public_tags'] = array('kombu', 'umami');
    break;
}
unset($entity);
$taxonomy_projection = c99_projection(
    $taxonomy_registry['entities'][$taxonomy_entity_offset],
    $taxonomy_registry
);

$culinary_gate_cases = array();

$dish_without_test = $registry;
foreach ($dish_without_test['entities'] as &$entity) {
    if ('dish-edomae-nigiri' !== $entity['id']) {
        continue;
    }
    $entity = c99_public_entity($entity);
    $entity['review']['culinary_test_status'] = 'pending';
    break;
}
unset($entity);
$culinary_gate_cases['dish_without_test'] = c99_validation_result(
    $dish_without_test
);

$recipe_without_test = $registry;
foreach ($recipe_without_test['entities'] as &$entity) {
    if ('preparation-ichiban-dashi' !== $entity['id']) {
        continue;
    }
    $entity['seo']['schema_type'] = 'Recipe';
    $entity['review']['culinary_test_status'] = 'pending';
    break;
}
unset($entity);
$culinary_gate_cases['recipe_without_test'] = c99_validation_result(
    $recipe_without_test
);

$recipe_with_test = $registry;
foreach ($recipe_with_test['entities'] as &$entity) {
    if ('preparation-ichiban-dashi' !== $entity['id']) {
        continue;
    }
    $entity['seo']['schema_type'] = 'Recipe';
    $entity['review']['culinary_test_status'] = 'tested';
    break;
}
unset($entity);
$culinary_gate_cases['recipe_with_test'] = c99_validation_result(
    $recipe_with_test
);

$indexed_nonrecipe_without_test = $registry;
foreach ($indexed_nonrecipe_without_test['entities'] as &$entity) {
    if ('preparation-ichiban-dashi' !== $entity['id']) {
        continue;
    }
    $entity['publication']['search_index'] = true;
    break;
}
unset($entity);
$culinary_gate_cases['indexed_nonrecipe_without_test'] = c99_validation_result(
    $indexed_nonrecipe_without_test
);

echo json_encode(array(
    'mutations' => $mutations,
    'graph_errors' => $graph_errors,
    'profile_candidate' => c99_validation_result($profile_registry),
    'profile_projection' => $profile_projection,
    'taxonomy_candidate' => c99_validation_result($taxonomy_registry),
    'taxonomy_projection' => $taxonomy_projection,
    'culinary_gate_cases' => $culinary_gate_cases,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    )
    return json.loads(_run_php(script))


@pytest.mark.parametrize(
    "php_file",
    [
        BOOTSTRAP,
        SCIENCE_CLASS,
        SCIENCE_DATA,
        SCIENCE_FOUNDATIONS_COLLECTION,
        PLATFORM_CLASS,
        REST_CLASS,
        REVIEW_LAB,
        SEO_REGISTRY,
    ],
)
def test_culinary_science_php_files_lint_cleanly(php_file: Path) -> None:
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


def test_php_80_list_fallback_accepts_an_empty_list() -> None:
    source = SCIENCE_CLASS.read_text(encoding="utf-8")
    assert (
        "return is_array( $value ) && ( empty( $value ) || "
        "array_keys( $value ) === range( 0, count( $value ) - 1 ) );"
    ) in source


def test_registry_v5_directly_validates_with_minimum_complete_pilot(
    science_payload: dict,
) -> None:
    assert science_payload["valid"], science_payload["validation_error"]
    registry = science_payload["registry"]
    assert registry["schema"] == EXPECTED_SCHEMA
    assert registry["version"] == EXPECTED_VERSION
    assert len(registry["entities"]) >= 66

    clusters = {entity["seo"]["cluster_id"] for entity in registry["entities"]}
    assert clusters == EXPECTED_CLUSTERS
    assert science_payload["status"]["ready"] is True
    assert science_payload["status"]["entity_count"] == len(registry["entities"])
    assert science_payload["status"]["cluster_count"] == 6


def test_single_museum_root_is_the_exact_bilingual_owner(
    science_payload: dict,
) -> None:
    entities = science_payload["registry"]["entities"]
    roots = [entity for entity in entities if entity["parent_id"] == ""]
    assert len(roots) == 1
    root = roots[0]
    assert root["id"] == "museum-culinary-science"
    assert root["type"] == "topic_hub"
    assert root["seo"]["page_role"] == "pillar"
    assert root["seo"]["route_mode"] == "standalone"
    assert root["seo"]["owner_entity_id"] == root["id"]
    assert root["seo"]["canonical_path"] == {
        "he": "/museum/",
        "en": "/en/museum/",
    }
    assert root["seo"]["breadcrumb_entity_ids"] == [root["id"]]
    assert [item["key"] for item in root["seo"]["visible_breadcrumbs"]] == [
        "home",
        "current-museum-culinary-science",
    ]


def test_reviewed_public_pilot_has_only_approved_offers_and_no_indexing(
    science_payload: dict,
) -> None:
    assert science_payload["status"]["public_count"] == len(EXPECTED_PUBLIC_PILOT)
    for entity in science_payload["registry"]["entities"]:
        publication = entity["publication"]
        expected_public = entity["id"] in EXPECTED_PUBLIC_PILOT
        assert publication["public_api"] is expected_public, entity["id"]
        assert publication["public_page"] is expected_public, entity["id"]
        assert (publication["state"] == "approved_public") is expected_public
        assert publication["search_index"] is False, entity["id"]
        expected_product_code = EXPECTED_PUBLIC_OFFER_CODES.get(entity["id"], "")
        commerce = entity["commerce"]
        assert commerce["public_offer_allowed"] is bool(expected_product_code), (
            entity["id"]
        )
        assert (commerce["state"] == "active_offer") is bool(
            expected_product_code
        ), entity["id"]
        assert commerce["woo_product_code"] == expected_product_code, entity["id"]
        if expected_product_code:
            assert entity["id"] in EXPECTED_PUBLIC_PILOT
            assert commerce["business_model"]["pricing_state"] == (
                "approved_sell_price"
            )


def test_wasabi_public_slice_has_owned_routes_sources_and_independent_offer_boundary(
    science_payload: dict,
) -> None:
    registry = science_payload["registry"]
    by_id = {entity["id"]: entity for entity in registry["entities"]}
    sources = registry["sources"]

    equipment_hub = by_id["hub-japanese-equipment"]
    guide = by_id["guide-wasabi-aitc"]
    molecule = by_id["molecule-allyl-isothiocyanate"]
    grater = by_id["equipment-wasabi-grater"]
    wasabi = by_id["ingredient-fresh-wasabi"]

    assert equipment_hub["seo"]["route_mode"] == "section"
    assert equipment_hub["seo"]["owner_entity_id"] == "cuisine-japanese-washoku"
    assert guide["seo"]["route_mode"] == "standalone"
    assert guide["seo"]["canonical_path"] == {
        "he": "/knowledge/wasabi-aitc-pungency/",
        "en": "/en/knowledge/wasabi-aitc-pungency/",
    }
    assert molecule["seo"]["route_mode"] == "section"
    assert molecule["seo"]["owner_entity_id"] == "guide-wasabi-aitc"
    assert grater["seo"]["route_mode"] == "standalone"

    for entity in (equipment_hub, guide, molecule, grater):
        assert entity["publication"]["public_page"] is True
        assert entity["publication"]["search_index"] is False
        assert entity["index_policy"] == "noindex_until_longform_review"

    yamamoto = sources["yamamoto-haganezame-spec"]
    assert yamamoto["type"] == "official_business"
    assert yamamoto["url"] == (
        "https://www.yamamotofoods.co.jp/haganezame/jp/spec/"
    )
    assert any(
        "yamamoto-haganezame-spec" in fact["source_ids"]
        for fact in grater["facts"]
        if fact["public_safe"]
    )
    assert any(
        relation["target_id"] == "equipment-wasabi-grater"
        and relation["public_safe"]
        and "yamamoto-haganezame-spec" in relation["source_ids"]
        for relation in wasabi["relations"]
    )

    assert wasabi["commerce"]["woo_product_code"] == (
        "product-fresh-japanese-wasabi-250g"
    )
    assert grater["commerce"]["woo_product_code"] == "product-hagane-zame-large"
    for entity in (wasabi, grater):
        assert entity["commerce"]["state"] == "active_offer"
        assert entity["commerce"]["public_offer_allowed"] is True
        assert entity["commerce"]["business_model"]["pricing_state"] == (
            "approved_sell_price"
        )


def test_public_culinary_test_gates_distinguish_dishes_recipes_and_noindex_guides(
    science_payload: dict,
    science_v5_contract_payload: dict,
) -> None:
    public_by_id = {
        entity["id"]: entity
        for entity in science_payload["registry"]["entities"]
        if entity["publication"]["public_page"]
    }
    ichiban = public_by_id["preparation-ichiban-dashi"]
    assert ichiban["type"] == "preparation"
    assert ichiban["seo"]["schema_type"] == "DefinedTerm"
    assert ichiban["review"]["culinary_test_status"] == "not_applicable"
    assert ichiban["publication"]["search_index"] is False
    assert ichiban["index_policy"] == "noindex_until_longform_review"

    for entity in public_by_id.values():
        requires_test = entity["type"] == "dish" or (
            entity["type"] == "preparation"
            and entity["seo"]["schema_type"] == "Recipe"
        )
        if requires_test:
            assert entity["review"]["culinary_test_status"] == "tested"

    cases = science_v5_contract_payload["culinary_gate_cases"]
    assert cases["dish_without_test"] == {
        "valid": False,
        "code": "complete99_science_registry_invalid",
        "path": "registry.entities.10.publication.culinary_test_gate",
    }
    assert cases["recipe_without_test"] == {
        "valid": False,
        "code": "complete99_science_registry_invalid",
        "path": "registry.entities.12.publication.culinary_test_gate",
    }
    assert cases["recipe_with_test"] == {
        "valid": True,
        "code": "",
        "path": "",
    }
    assert cases["indexed_nonrecipe_without_test"] == {
        "valid": False,
        "code": "complete99_science_registry_invalid",
        "path": "registry.entities.12.publication.untested_preparation_scope",
    }


def test_v5_media_rights_and_taxonomy_shapes_are_complete(
    science_payload: dict,
) -> None:
    registry = science_payload["registry"]
    cleared_states = {
        "cleared_owned",
        "cleared_generated",
        "cleared_licensed",
    }
    receipt_pattern = re.compile(r"^sha256:[a-f0-9]{64}$")

    for entity in registry["entities"]:
        visual = entity["visual"]
        taxonomy = entity["taxonomy"]
        assert set(visual) == VISUAL_FIELDS, entity["id"]
        assert set(taxonomy) == TAXONOMY_FIELDS, entity["id"]
        assert set(taxonomy["public_category_path"]).issubset(
            taxonomy["category_path"]
        ), entity["id"]
        assert set(taxonomy["public_attribute_keys"]).issubset(
            taxonomy["attributes"]
        ), entity["id"]
        assert set(taxonomy["public_tags"]).issubset(
            taxonomy["tags"]
        ), entity["id"]
        if visual["rights_state"] in cleared_states:
            assert receipt_pattern.fullmatch(visual["rights_receipt_digest"]), (
                entity["id"],
                visual,
            )
        if visual["asset_state"] == "approved":
            assert visual["rights_state"] in cleared_states, entity["id"]
            assert receipt_pattern.fullmatch(visual["rights_receipt_digest"]), (
                entity["id"],
                visual,
            )

        for note in entity["compliance"]:
            if note["public_safe"]:
                assert note["source_ids"], (entity["id"], note["code"])


def test_v5_rights_compliance_and_taxonomy_mutations_fail_closed(
    science_v5_contract_payload: dict,
) -> None:
    mutations = science_v5_contract_payload["mutations"]

    assert mutations["approved_without_cleared_rights"]["path"].endswith(
        ".visual.approved_without_cleared_rights"
    )
    assert mutations["cleared_without_receipt"]["path"].endswith(
        ".visual.cleared_without_receipt"
    )
    assert mutations["malformed_rights_receipt"]["path"].endswith(
        ".visual.rights_receipt_digest"
    )
    assert mutations["approved_with_receipt"]["valid"] is True
    assert mutations["public_compliance_without_source"]["path"].endswith(
        ".compliance.0.public_without_source"
    )
    assert mutations["invalid_taxonomy_allowlist"]["path"].endswith(
        ".taxonomy.public_allowlist"
    )


def test_public_graph_rejects_every_private_reference_class(
    science_v5_contract_payload: dict,
) -> None:
    assert science_v5_contract_payload["graph_errors"] == {
        "private_parent": (
            "registry.entities.ingredient-kombu.public_parent_private"
        ),
        "private_relation_target": (
            "registry.entities.ingredient-kombu."
            "public_relation_private_target"
        ),
        "private_internal_link_target": (
            "registry.entities.ingredient-kombu."
            "public_link_private_target"
        ),
        "private_semantic_target": (
            "registry.entities.ingredient-kombu.public_semantic_private"
        ),
        "private_breadcrumb_target": (
            "registry.entities.ingredient-kombu.public_breadcrumb_private"
        ),
    }


def test_public_projection_omits_private_profiles_and_private_taxonomy(
    science_v5_contract_payload: dict,
) -> None:
    assert science_v5_contract_payload["profile_candidate"]["valid"] is True
    profile_projection = science_v5_contract_payload["profile_projection"]
    assert "cultural" in profile_projection["profiles"]
    assert "structural" not in profile_projection["profiles"]
    assert profile_projection["profiles"]["cultural"]["fact_ids"] == [
        "fact-washoku-unesco-framework"
    ]
    public_fact_ids = {fact["id"] for fact in profile_projection["facts"]}
    for profile in profile_projection["profiles"].values():
        assert set(profile["fact_ids"]).issubset(public_fact_ids)

    assert science_v5_contract_payload["taxonomy_candidate"]["valid"] is True
    taxonomy = science_v5_contract_payload["taxonomy_projection"]["taxonomy"]
    assert taxonomy == {
        "category_path": ["world-cuisines", "japan"],
        "attributes": {"pa_flavor_profile": ["umami"]},
        "tags": ["kombu", "umami"],
    }
    assert set(taxonomy) == {"category_path", "attributes", "tags"}


def test_exact_bilingual_type_first_canonical_owners(
    science_payload: dict,
) -> None:
    entities = science_payload["registry"]["entities"]
    standalone = {
        entity["id"]: entity
        for entity in entities
        if entity["seo"]["route_mode"] == "standalone"
    }
    assert set(standalone) == set(EXPECTED_CANONICAL_OWNERS)

    for entity_id, (expected_type, expected_he_path) in (
        EXPECTED_CANONICAL_OWNERS.items()
    ):
        entity = standalone[entity_id]
        assert entity["type"] == expected_type
        assert entity["seo"]["owner_entity_id"] == entity_id
        assert entity["seo"]["section_id"] == ""
        assert entity["seo"]["canonical_path"] == {
            "he": expected_he_path,
            "en": "/en" + expected_he_path,
        }

    type_prefixes = {
        "dish": "/dishes/",
        "ingredient": "/ingredients/",
        "tradition": "/traditions/",
        "preparation": "/knowledge/",
        "equipment": "/knowledge/",
        "guide": "/knowledge/",
        "comparison": "/knowledge/",
        "standard": "/knowledge/",
    }
    for entity in standalone.values():
        if entity["type"] in type_prefixes:
            assert entity["seo"]["canonical_path"]["he"].startswith(
                type_prefixes[entity["type"]]
            )


def test_sections_equal_their_standalone_owner_canonical_and_breadcrumbs(
    science_payload: dict,
) -> None:
    entities = science_payload["registry"]["entities"]
    by_id = {entity["id"]: entity for entity in entities}
    sections = [
        entity for entity in entities if entity["seo"]["route_mode"] == "section"
    ]
    assert len(sections) >= 19

    for section in sections:
        seo = section["seo"]
        owner = by_id[seo["owner_entity_id"]]
        assert owner["id"] != section["id"]
        assert owner["seo"]["route_mode"] == "standalone"
        assert seo["section_id"]
        assert seo["canonical_path"] == owner["seo"]["canonical_path"]
        assert seo["visible_breadcrumbs"] == owner["seo"]["visible_breadcrumbs"]
        assert seo["protected_owner_ids"] == owner["seo"]["protected_owner_ids"]


def test_locale_term_lists_are_separate_and_language_clean(
    science_payload: dict,
) -> None:
    for entity in science_payload["registry"]["entities"]:
        terms = entity["seo"]["term_variants"]
        assert terms["he"] != terms["en"], entity["id"]
        assert terms["he"] and terms["en"], entity["id"]
        assert any(HEBREW_RE.search(term) for term in terms["he"]), entity["id"]
        assert any(LATIN_RE.search(term) for term in terms["en"]), entity["id"]
        for term in terms["en"]:
            assert LATIN_RE.search(term), f"{entity['id']}: English term {term!r}"
            assert not HEBREW_RE.search(term), (
                f"{entity['id']}: mixed English term {term!r}"
            )


def _normalise_query(value: str) -> str:
    return " ".join(value.strip().casefold().split())


def test_every_query_variant_has_one_owner_per_locale(
    science_payload: dict,
) -> None:
    query_owners: dict[tuple[str, str], set[str]] = {}
    entities = science_payload["registry"]["entities"]
    entity_ids = {entity["id"] for entity in entities}

    for entity in entities:
        seo = entity["seo"]
        owner_id = seo["owner_entity_id"]
        assert owner_id in entity_ids
        for locale in ("he", "en"):
            variants = seo["query_variants"][locale]
            assert variants
            assert _normalise_query(variants[0]) == _normalise_query(
                seo["primary_keyword"][locale]
            )
            for variant in variants:
                key = (locale, _normalise_query(variant))
                query_owners.setdefault(key, set()).add(owner_id)

    collisions = {
        key: sorted(owners)
        for key, owners in query_owners.items()
        if len(owners) != 1
    }
    assert collisions == {}


def test_every_entity_has_five_profiles_and_complete_business_model(
    science_payload: dict,
) -> None:
    for entity in science_payload["registry"]["entities"]:
        assert set(entity["profiles"]) == PROFILE_DIMENSIONS, entity["id"]
        for dimension, profile in entity["profiles"].items():
            assert set(profile) == {"state", "summary", "fact_ids"}, (
                entity["id"],
                dimension,
            )
            assert set(profile["summary"]) == {"he", "en"}

        model = entity["commerce"]["business_model"]
        assert set(model) == BUSINESS_MODEL_FIELDS, entity["id"]
        assert model["revenue_models"], entity["id"]
        assert model["customer_segments"], entity["id"]
        assert set(model["value_proposition"]) == {"he", "en"}
        assert all(model["value_proposition"].values()), entity["id"]
        assert model["pricing_state"], entity["id"]
        assert model["market_scope"], entity["id"]
        assert isinstance(model["observation_entity_ids"], list)
        assert set(model["margin_scenario"]) == MARGIN_SCENARIO_FIELDS


def test_approved_sell_prices_do_not_imply_unverified_profitability(
    science_payload: dict,
) -> None:
    for entity in science_payload["registry"]["entities"]:
        model = entity["commerce"]["business_model"]
        if model["pricing_state"] != "approved_sell_price":
            continue
        scenario = model["margin_scenario"]
        if scenario["confidence"] != "pending":
            continue
        basis_he = scenario["basis"]["he"]
        basis_en = scenario["basis"]["en"]
        assert "\u05e8\u05d5\u05d5\u05d7\u05d9\u05d5\u05ea" in basis_he, entity["id"]
        assert "profitability" in basis_en, entity["id"]
        assert "\u05dc\u05e4\u05e0\u05d9 \u05d0\u05d9\u05e9\u05d5\u05e8 \u05de\u05d7\u05d9\u05e8 \u05de\u05db\u05d9\u05e8\u05d4" not in basis_he, entity["id"]
        assert "before a sell price is approved" not in basis_en, entity["id"]


def test_every_fact_has_scientific_measurements_contract(
    science_payload: dict,
) -> None:
    source_ids = set(science_payload["registry"]["sources"])
    fact_count = 0
    for entity in science_payload["registry"]["entities"]:
        for fact in entity["facts"]:
            fact_count += 1
            assert "scientific_measurements" in fact, (entity["id"], fact["id"])
            assert isinstance(fact["scientific_measurements"], list)
            if fact["scientific_measurements"]:
                assert fact["dimension"] == "scientific"
            for measurement in fact["scientific_measurements"]:
                assert set(measurement) == SCIENTIFIC_MEASUREMENT_FIELDS
                assert measurement["unit"]
                assert measurement["method"]
                assert measurement["source_ids"]
                assert set(measurement["source_ids"]).issubset(source_ids)
    assert fact_count >= 66


def test_price_records_have_scoped_measurements_and_observation_links(
    science_payload: dict,
) -> None:
    entities = science_payload["registry"]["entities"]
    by_id = {entity["id"]: entity for entity in entities}
    price_records = {
        entity["id"]: entity
        for entity in entities
        if entity["type"] in {"retail_listing", "market_observation"}
    }
    retail_listing_ids = {
        entity_id
        for entity_id, entity in price_records.items()
        if entity["type"] == "retail_listing"
    }
    assert len(retail_listing_ids) >= 10

    linked_observations: set[str] = set()
    for entity in entities:
        for observation_id in entity["commerce"]["business_model"][
            "observation_entity_ids"
        ]:
            assert observation_id in by_id, (entity["id"], observation_id)
            assert by_id[observation_id]["type"] in {
                "retail_listing",
                "market_observation",
            }
            linked_observations.add(observation_id)

    assert retail_listing_ids.issubset(linked_observations)
    assert "market-observation-tsubaya-yanagiba-2026-08-06" in linked_observations

    for entity_id, entity in price_records.items():
        price_facts = [
            fact
            for fact in entity["facts"]
            if fact["evidence_class"] == "market_observation"
            and fact["measurement"]
        ]
        assert price_facts, entity_id
        assert any(
            relation["type"] == "references" for relation in entity["relations"]
        ), entity_id

        for fact in price_facts:
            measurement = fact["measurement"]
            assert fact["observed_at"], (entity_id, fact["id"])
            assert fact["source_ids"], (entity_id, fact["id"])
            assert measurement["observed_at"] == fact["observed_at"]
            datetime.fromisoformat(measurement["observed_at"])
            assert measurement["unit"].strip()
            assert measurement["tax_status"] in {"included", "excluded", "unknown"}
            assert measurement["shipping_status"] in {
                "included",
                "excluded",
                "unknown",
            }
            assert measurement["source_url"].startswith("https://")
            assert measurement["sample_size"] == len(measurement["line_items"])
            assert measurement["line_items"]
            for item in measurement["line_items"]:
                assert item["source_url"].startswith("https://")
                assert item["currency"] == measurement["currency"]
                assert item["tax_status"] in {"included", "excluded", "unknown"}


def test_japanese_premium_tranche_is_private_bilingual_and_source_bounded(
    science_payload: dict,
) -> None:
    registry = science_payload["registry"]
    by_id = {entity["id"]: entity for entity in registry["entities"]}
    knowledge_ids = {
        "market-toyosu",
        "supplier-district-kappabashi",
        "institution-japanese-culinary-academy",
        "restaurant-ginza-kyubey",
        "technique-edomae-shari-control",
        "technique-kombujime",
        "dish-futomaki-sushi",
        "dish-kaiseki-hassun",
    }
    listing_ids = {
        "listing-maruyama-gokujo-kontobi-nori-5-sheets-20260806",
        "listing-tajima-red-sushi-vinegar-360ml-20260806",
        "listing-minamigura-gin-warabeuta-tamari-200ml-20260806",
        "listing-sugimoto-organic-dried-shiitake-70g-20260806",
        "listing-yubaya-kyoto-dried-yuba-100g-20260806",
        "listing-ohsawa-organic-kudzu-starch-150g-20260806",
        "listing-yawataya-isogoro-sansho-12g-20260806",
        "listing-marukyu-koyamaen-tenju-matcha-20g-20260806",
        "listing-yamaco-bamboo-makisu-27cm-20260806",
        "listing-sakai-takayuki-ginsan-yanagiba-270mm-20260806",
        "listing-nagatanien-kamado-san-3-cup-20260806",
        "listing-kubo-komakichi-kazuho-chasen-20260806",
    }

    assert knowledge_ids | listing_ids <= set(by_id)
    for entity_id in knowledge_ids | listing_ids:
        entity = by_id[entity_id]
        assert entity["name"]["he"]
        assert entity["name"]["en"]
        assert entity["publication"]["public_api"] is False
        assert entity["publication"]["public_page"] is False
        assert entity["publication"]["search_index"] is False
        if entity_id in {
            "market-toyosu",
            "institution-japanese-culinary-academy",
        }:
            assert entity["index_policy"].startswith("noindex_")
        else:
            assert entity["index_policy"] == "noindex_private"
        assert entity["seo"]["route_mode"] in {"private", "section"}

    toyosu_source_ids = {
        source_id
        for fact in by_id["market-toyosu"]["facts"]
        for source_id in fact["source_ids"]
    }
    assert {
        "toyosu-market-official-2026",
        "toyosu-market-overview-2026",
    } <= toyosu_source_ids
    academy_source_ids = {
        source_id
        for fact in by_id["institution-japanese-culinary-academy"]["facts"]
        for source_id in fact["source_ids"]
    }
    assert {"jca-corpus-2026", "jca-taizen-digital-book-2026"} <= academy_source_ids


def test_japanese_premium_tranche_august_6_chronology_and_source_provenance(
    science_payload: dict,
) -> None:
    registry = science_payload["registry"]
    by_id = {entity["id"]: entity for entity in registry["entities"]}
    source_types = {
        source_id: source["type"]
        for source_id, source in registry["sources"].items()
    }
    new_entity_ids = {
        "supplier-district-kappabashi",
        "restaurant-ginza-kyubey",
        "technique-edomae-shari-control",
        "technique-kombujime",
        "dish-futomaki-sushi",
        "dish-kaiseki-hassun",
        "listing-maruyama-gokujo-kontobi-nori-5-sheets-20260806",
        "listing-tajima-red-sushi-vinegar-360ml-20260806",
        "listing-minamigura-gin-warabeuta-tamari-200ml-20260806",
        "listing-sugimoto-organic-dried-shiitake-70g-20260806",
        "listing-yubaya-kyoto-dried-yuba-100g-20260806",
        "listing-ohsawa-organic-kudzu-starch-150g-20260806",
        "listing-yawataya-isogoro-sansho-12g-20260806",
        "listing-marukyu-koyamaen-tenju-matcha-20g-20260806",
        "listing-yamaco-bamboo-makisu-27cm-20260806",
        "listing-sakai-takayuki-ginsan-yanagiba-270mm-20260806",
        "listing-nagatanien-kamado-san-3-cup-20260806",
        "listing-kubo-komakichi-kazuho-chasen-20260806",
    }
    enriched_entity_ids = {
        "market-toyosu",
        "institution-japanese-culinary-academy",
    }

    for entity_id in new_entity_ids:
        entity = by_id[entity_id]
        assert entity["trust"]["substantive_updated_at"] == "2026-08-06"
        assert entity["review"]["reviewed_at"] == "2026-08-06"
        assert all(
            relation["valid_from"] == "2026-08-06"
            for relation in entity["relations"]
        ), entity_id

    enrichment_source_ids = {
        "market-toyosu": {
            "toyosu-market-official-2026",
            "toyosu-market-overview-2026",
        },
        "institution-japanese-culinary-academy": {
            "jca-official-en-2026",
            "jca-corpus-2026",
            "jca-taizen-digital-book-2026",
        },
    }
    for entity_id in enriched_entity_ids:
        entity = by_id[entity_id]
        assert entity["trust"]["substantive_updated_at"] == "2026-08-06"
        assert entity["review"]["reviewed_at"] == "2026-08-06"
        new_relations = [
            relation
            for relation in entity["relations"]
            if set(relation["source_ids"]) & enrichment_source_ids[entity_id]
        ]
        assert new_relations, entity_id
        assert all(
            relation["valid_from"] == "2026-08-06"
            for relation in new_relations
        ), entity_id

    category_facts = [
        (entity["id"], fact)
        for entity in registry["entities"]
        for fact in entity["facts"]
        if entity["id"].startswith("listing-")
        and "category-context-boundary" in fact["id"]
    ]
    assert category_facts
    for entity_id, fact in category_facts:
        assert fact["value_scope"] == "category"
        assert fact["public_safe"] is False
        observed_source_types = {
            source_types[source_id] for source_id in fact["source_ids"]
        }
        if fact["evidence_class"] == "peer_reviewed_context":
            assert observed_source_types == {"peer_reviewed_paper"}, entity_id
        elif fact["evidence_class"] == "official_source":
            assert observed_source_types <= {
                "official_business",
                "official_market_listing",
                "official_government",
                "official_organization",
            }, entity_id
            assert observed_source_types, entity_id
        else:
            raise AssertionError(
                f"Unexpected category evidence class for {entity_id}: "
                f"{fact['evidence_class']}"
            )

    minamigura_facts = [
        fact
        for entity_id, fact in category_facts
        if entity_id
        == "listing-minamigura-gin-warabeuta-tamari-200ml-20260806"
    ]
    assert len(minamigura_facts) == 2
    assert {fact["evidence_class"] for fact in minamigura_facts} == {
        "official_source",
        "peer_reviewed_context",
    }

    assert registry["sources"]["nori-category-science-2024"]["published_at"] == (
        "2024-07-15"
    )
    assert registry["sources"]["shiitake-category-science-2024"][
        "published_at"
    ] == "2024-10-23"
    assert registry["sources"]["matcha-category-science-2022-a"][
        "published_at"
    ] == "2022-09-20"
    assert registry["sources"]["matcha-category-science-2022-b"][
        "published_at"
    ] == "2022-04-30"


def test_japanese_premium_tranche_primary_source_corrections_are_exact(
    science_payload: dict,
) -> None:
    by_id = {
        entity["id"]: entity
        for entity in science_payload["registry"]["entities"]
    }

    def listing_measurement(entity_id: str) -> dict:
        matches = [
            fact["measurement"]
            for fact in by_id[entity_id]["facts"]
            if fact["evidence_class"] == "market_observation"
        ]
        assert len(matches) == 1
        return matches[0]

    tenju = listing_measurement(
        "listing-marukyu-koyamaen-tenju-matcha-20g-20260806"
    )
    assert tenju["value"] == 21600
    assert tenju["currency"] == "JPY"
    assert tenju["source_url"] == (
        "https://www.marukyu-koyamaen.co.jp/motoan-shop/products/1111020c1/"
    )
    assert tenju["line_items"][0]["availability"] == (
        "sold_out_limited_allocation"
    )
    assert tenju["line_items"][0]["attributes"] == {
        "sku": "1111020C1",
        "net_content": "20 g",
        "allocation_state": "limited",
        "stock_state": "sold out",
        "selling_context": "irregular selling or shortage context",
    }
    tenju_copy = json.dumps(
        by_id["listing-marukyu-koyamaen-tenju-matcha-20g-20260806"],
        ensure_ascii=False,
    ).lower()
    for stale_claim in ("20,100", "price-conflict", "january 2026 catalog"):
        assert stale_claim not in tenju_copy

    kamado = listing_measurement(
        "listing-nagatanien-kamado-san-3-cup-20260806"
    )
    assert kamado["value"] == 16500
    assert kamado["currency"] == "JPY"
    assert kamado["tax_status"] == "included"
    kamado_item = kamado["line_items"][0]
    assert kamado_item["availability"] == (
        "sequential_shipment_after_late_september"
    )
    assert kamado_item["tax_status"] == "included"
    assert kamado_item["attributes"]["model_code"] == "ACT-01"
    assert kamado_item["attributes"]["capacity"] == "three cups"
    assert "9月下旬以降" in kamado_item["attributes"]["availability_state"]
    kamado_copy = json.dumps(
        by_id["listing-nagatanien-kamado-san-3-cup-20260806"],
        ensure_ascii=False,
    ).lower()
    for stale_claim in (
        "mid-september",
        "preorder",
        "restock",
        "four cups",
        "four-cup",
        "4-cup",
    ):
        assert stale_claim not in kamado_copy

    for entity_id in (
        "listing-maruyama-gokujo-kontobi-nori-5-sheets-20260806",
        "listing-yubaya-kyoto-dried-yuba-100g-20260806",
    ):
        measurement = listing_measurement(entity_id)
        assert measurement["tax_status"] == "included"
        assert measurement["line_items"][0]["tax_status"] == "included"

    yanagiba = listing_measurement(
        "listing-sakai-takayuki-ginsan-yanagiba-270mm-20260806"
    )
    yanagiba_attributes = yanagiba["line_items"][0]["attributes"]
    assert yanagiba_attributes["brand_claim"] == "Sakai Takayuki"
    assert yanagiba_attributes["manufacturer_claim"] == "Aoki Hamono"


def test_japanese_premium_tranche_preserves_regulatory_and_endorsement_boundaries(
    science_payload: dict,
) -> None:
    by_id = {
        entity["id"]: entity
        for entity in science_payload["registry"]["entities"]
    }
    shari = by_id["technique-edomae-shari-control"]
    shari_copy = json.dumps(shari, ensure_ascii=False).lower()
    assert "4.2" in shari_copy
    assert "not an automatic statement of israeli law" in shari_copy
    assert "jurisdiction-and-batch-ph-review" in shari_copy

    for entity_id in (
        "supplier-district-kappabashi",
        "restaurant-ginza-kyubey",
        "institution-japanese-culinary-academy",
    ):
        copy = json.dumps(by_id[entity_id], ensure_ascii=False).lower()
        assert "endorsement" in copy or "partnership" in copy or "affiliation" in copy


def test_plugin_require_boot_migration_health_and_review_lab_are_wired() -> None:
    bootstrap = BOOTSTRAP.read_text(encoding="utf-8")
    platform = PLATFORM_CLASS.read_text(encoding="utf-8")
    health = REST_CLASS.read_text(encoding="utf-8")
    review = REVIEW_LAB.read_text(encoding="utf-8")
    seo = SEO_REGISTRY.read_text(encoding="utf-8")

    science_require = "includes/class-complete99-culinary-science.php"
    assert science_require in bootstrap
    assert bootstrap.index(science_require) < bootstrap.index(
        "includes/class-complete99-platform.php"
    )
    assert "Complete99_Culinary_Science::boot();" in platform

    migration = platform.split("private static function run_migration", 1)[1]
    invariant = migration.index("Complete99_Culinary_Science::assert_invariants();")
    version_write = migration.index(
        "update_option( 'complete99_platform_version', COMPLETE99_PLATFORM_VERSION"
    )
    commit = migration.index("$wpdb->query( 'COMMIT' )")
    assert invariant < version_write < commit

    health_method = health.split("public static function health()", 1)[1].split(
        "public static function verify_sync_signature", 1
    )[0]
    assert "Complete99_Culinary_Science::status()" in health_method
    assert "empty( $science['ready'] )" in health_method
    assert "'culinary_science_ready' =>" in health_method
    assert "'culinary_science' => $science" not in health_method
    assert "'entity_count'" not in health_method
    assert "'read_model'      => array(" in health_method
    assert "'digest'     => $model_digest" in health_method
    assert "'culinary_science_digest'" not in health_method

    snapshot = review.split("public static function snapshot()", 1)[1].split(
        "private static function evaluation_catalog_status", 1
    )[0]
    assert "Complete99_Culinary_Science::editorial_snapshot()" in snapshot
    assert "'culinary_science' => array(" in snapshot
    assert "'entities' =>" in snapshot
    assert "Complete99_Culinary_Science::seo_owner_records()" in seo


def test_plugin_php_contains_no_em_dash_u2014() -> None:
    offenders = []
    for path in PLUGIN.rglob("*.php"):
        if "\u2014" in path.read_text(encoding="utf-8"):
            offenders.append(path.relative_to(ROOT).as_posix())
    assert offenders == []


def test_exact_v7_registry_counts_are_preserved() -> None:
    dish_path = _php_path(PLUGIN / "data" / "dish-entity-trees.php")
    product_path = _php_path(PLUGIN / "data" / "catalog-product-seeds.php")
    asset_path = _php_path(PLUGIN / "data" / "generated-asset-manifest.php")
    payload = json.loads(
        _run_php(
            f"""
define('ABSPATH', __DIR__);
$dish_registry = require '{dish_path}';
$products = require '{product_path}';
$assets = require '{asset_path}';
echo json_encode(array(
    'dish_schema' => $dish_registry['schema'],
    'dish_count' => count($dish_registry['dishes']),
    'product_schema' => $products['schema'],
    'product_count' => count($products['products']),
    'asset_schema' => $assets['schema'],
    'asset_count' => count($assets['assets']),
), JSON_THROW_ON_ERROR);
"""
        )
    )
    assert payload == {
        "dish_schema": "complete99-dish-entity-tree-registry/v1",
        "dish_count": 12,
        "product_schema": "complete99-catalog-product-seeds/v1",
        "product_count": 36,
        "asset_schema": "complete99-generated-asset-manifest/v1",
        "asset_count": 60,
    }
