from __future__ import annotations

import hashlib
import json
import re
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE = PLUGIN / "data" / "culinary-science-pilot.php"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
COMMERCE = PLUGIN / "data" / "culinary-commerce-pilot.php"
LIVE_PRODUCTS = PLUGIN / "data" / "live-catalog-products.php"
LIVE_RELATIONS = PLUGIN / "data" / "live-catalog-relations.php"
LIVE_PRICES = PLUGIN / "data" / "live-catalog-prices.php"
SEEDS = PLUGIN / "data" / "catalog-product-seeds.php"
ASSET_MANIFEST = PLUGIN / "data" / "generated-asset-manifest.php"
SCIENCE_ASSET_DIR = PLUGIN / "assets" / "images" / "science"
EVIDENCE_DIR = ROOT / "docs" / "research-evidence"
CUISINE_DIR = PLUGIN / "data" / "culinary-science" / "cuisines"

SCIENCE_SCHEMA = "complete99-culinary-science-registry/v6"
SCIENCE_VERSION = "culinary-science-2026.08.08.v20"
COMMERCE_VERSION = "culinary-commerce-2026.08.08.v14"

HELD_IDS = (
    "ingredient-shoyu-koji",
    "equipment-kioke",
    "guide-koji-hydrolysis",
    "reaction-koji-enzymatic-hydrolysis",
    "standard-jas-shoyu-1703",
)
PROPOSED_STANDALONE_IDS = set(HELD_IDS) - {"reaction-koji-enzymatic-hydrolysis"}

CAPTURE_IDS = set(HELD_IDS) | {
    "cuisine-japanese-washoku",
    "hub-japanese-foundations-lab",
    "hub-japanese-ingredients",
    "hub-japanese-equipment",
    "hub-japanese-food-science",
    "ingredient-kioke-shoyu",
    "ingredient-kome-koji",
    "ingredient-koji-starter-culture",
    "producer-yamaroku-shoyu",
    "listing-yamaroku-tsurubishio-500ml-20260806",
}

CANONICALS = {
    "ingredient-shoyu-koji": {
        "he": "/ingredients/shoyu-koji/",
        "en": "/en/ingredients/shoyu-koji/",
    },
    "equipment-kioke": {
        "he": "/knowledge/kioke-barrel-guide/",
        "en": "/en/knowledge/kioke-barrel-guide/",
    },
    "guide-koji-hydrolysis": {
        "he": "/knowledge/koji-enzymatic-hydrolysis/",
        "en": "/en/knowledge/koji-enzymatic-hydrolysis/",
    },
    "reaction-koji-enzymatic-hydrolysis": {
        "he": "/knowledge/koji-enzymatic-hydrolysis/",
        "en": "/en/knowledge/koji-enzymatic-hydrolysis/",
    },
    "standard-jas-shoyu-1703": {
        "he": "/knowledge/jas-1703-shoyu-standard/",
        "en": "/en/knowledge/jas-1703-shoyu-standard/",
    },
}

SEMANTIC_EXACT = {
    "ingredient-shoyu-koji": [
        "cuisine-japanese-washoku",
        "hub-japanese-ingredients",
        "hub-japanese-food-science",
        "guide-koji-hydrolysis",
        "reaction-koji-enzymatic-hydrolysis",
        "ingredient-kioke-shoyu",
    ],
    "equipment-kioke": [
        "cuisine-japanese-washoku",
        "hub-japanese-equipment",
        "ingredient-kioke-shoyu",
    ],
    "guide-koji-hydrolysis": [
        "cuisine-japanese-washoku",
        "hub-japanese-food-science",
        "ingredient-kome-koji",
        "ingredient-koji-starter-culture",
        "ingredient-shoyu-koji",
        "reaction-koji-enzymatic-hydrolysis",
        "ingredient-kioke-shoyu",
    ],
    "reaction-koji-enzymatic-hydrolysis": [
        "guide-koji-hydrolysis",
        "ingredient-kome-koji",
        "ingredient-koji-starter-culture",
        "ingredient-shoyu-koji",
    ],
    "standard-jas-shoyu-1703": [
        "cuisine-japanese-washoku",
        "hub-japanese-food-science",
        "ingredient-kioke-shoyu",
    ],
}

SEMANTIC_SUFFIXES = {
    "cuisine-japanese-washoku": [
        "ingredient-shoyu-koji",
        "guide-koji-hydrolysis",
        "equipment-kioke",
        "standard-jas-shoyu-1703",
    ],
    "hub-japanese-foundations-lab": [
        "ingredient-shoyu-koji",
        "guide-koji-hydrolysis",
        "reaction-koji-enzymatic-hydrolysis",
        "equipment-kioke",
        "standard-jas-shoyu-1703",
    ],
    "hub-japanese-ingredients": ["ingredient-shoyu-koji"],
    "hub-japanese-equipment": ["equipment-kioke", "ingredient-kioke-shoyu"],
    "hub-japanese-food-science": [
        "ingredient-shoyu-koji",
        "guide-koji-hydrolysis",
        "reaction-koji-enzymatic-hydrolysis",
        "equipment-kioke",
        "standard-jas-shoyu-1703",
        "ingredient-kioke-shoyu",
    ],
    "ingredient-kioke-shoyu": [
        "ingredient-shoyu-koji",
        "guide-koji-hydrolysis",
        "reaction-koji-enzymatic-hydrolysis",
        "equipment-kioke",
        "standard-jas-shoyu-1703",
    ],
    "ingredient-kome-koji": [
        "ingredient-shoyu-koji",
        "guide-koji-hydrolysis",
        "reaction-koji-enzymatic-hydrolysis",
    ],
    "ingredient-koji-starter-culture": [
        "ingredient-shoyu-koji",
        "guide-koji-hydrolysis",
        "reaction-koji-enzymatic-hydrolysis",
    ],
}

PUBLIC_FACT_IDS = {
    "ingredient-shoyu-koji": {"fact-shoyu-koji-distinction"},
    "equipment-kioke": {"fact-kioke-documented-use"},
    "guide-koji-hydrolysis": {"fact-koji-guide-process"},
    "reaction-koji-enzymatic-hydrolysis": {
        "fact-koji-hydrolysis-process",
        "fact-koji-industrial-protease-activity-ranges",
    },
    "standard-jas-shoyu-1703": {
        "fact-jas-shoyu-standard-identity",
        "fact-jas-saishikomi-category-thresholds",
    },
}

PUBLIC_RELATION_IDS = {
    "ingredient-shoyu-koji": {
        "edge-ingredient-shoyu-koji-used_in-1",
        "edge-ingredient-shoyu-koji-complements-2",
    },
    "equipment-kioke": {"edge-equipment-kioke-used_in-1"},
    "guide-koji-hydrolysis": {
        "edge-guide-koji-hydrolysis-contains-1",
        "edge-guide-koji-hydrolysis-references-2",
        "edge-guide-koji-hydrolysis-references-3",
    },
    "reaction-koji-enzymatic-hydrolysis": set(),
    "standard-jas-shoyu-1703": {
        "edge-standard-jas-shoyu-1703-supported_by-1"
    },
}

QUERY_VARIANTS = {
    "ingredient-shoyu-koji": {
        "he": ["קוג׳י לשויו", "מצע קוג׳י סויה וחיטה"],
        "en": ["shoyu koji", "soy wheat koji substrate"],
    },
    "equipment-kioke": {
        "he": ["חבית קיוקה", "חבית עץ לשויו"],
        "en": ["kioke barrel", "wooden soy sauce barrel"],
    },
    "guide-koji-hydrolysis": {
        "he": ["אנזימי קוג׳י והידרוליזה", "עמילאז קוג׳י", "פרוטאז קוג׳י"],
        "en": ["koji enzymes and hydrolysis", "koji amylase", "koji protease"],
    },
    "reaction-koji-enzymatic-hydrolysis": {
        "he": ["תגובת הידרוליזה אנזימטית בקוג׳י"],
        "en": ["koji enzymatic hydrolysis reaction"],
    },
    "standard-jas-shoyu-1703": {
        "he": ["תקן JAS לשויו", "JAS 1703", "סיווג רוטב סויה"],
        "en": ["JAS shoyu standard", "JAS 1703", "soy sauce classification standard"],
    },
}

ASSETS = {
    "ingredient-shoyu-koji": (
        "c99-science-shoyu-koji-substrate-v01",
        "1213fee79dbd9dfe3d597aaddb0011e57ed0bc014fdd13a83a23ccdf478f1319",
    ),
    "equipment-kioke": (
        "c99-science-kioke-wooden-barrel-v01",
        "bafac22402602dbda38f512754a701a4388b262e89dda7e7cac68d6dd2616a23",
    ),
    "guide-koji-hydrolysis": (
        "c99-science-koji-enzymes-hydrolysis-guide-v01",
        "6bb6f6becd75c7e4fdeed3e76f70e616ed6fc8713b94163475f585c4ac0d1a77",
    ),
    "reaction-koji-enzymatic-hydrolysis": (
        "c99-science-koji-enzymatic-hydrolysis-v01",
        "5978859d2161cbb6a41daddf52cc7402952a63068b9a0c0f684166237eee66a5",
    ),
    "standard-jas-shoyu-1703": (
        "c99-science-jas-1703-shoyu-standard-v01",
        "5a099802acabf8e704a03e5b254844519d2e32df0d8358d277b8594889e16ebf",
    ),
}

MODULE_FILES = (
    "iraqi-community-institutions.php",
    "iraqi-foundations.php",
    "iraqi-regional-depth.php",
    "lebanese-foundations.php",
    "lebanese-regional-expansion-bekaa-south-community.php",
    "lebanese-regional-expansion-coast-north.php",
    "syrian-community-institutions-expansion.php",
    "syrian-foundations.php",
    "syrian-regional-depth.php",
    "syrian-regional-expansion-east-south.php",
    "syrian-regional-expansion-west.php",
)


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
        timeout=90,
    )
    return completed.stdout


@pytest.fixture(scope="module")
def cohort_payload() -> dict:
    script = r"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '__PLUGIN__/');
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
require '__SCIENCE_CLASS__';

$science = require '__SCIENCE__';
$commerce = require '__COMMERCE__';
$live_products = require '__LIVE_PRODUCTS__';
$live_relations = require '__LIVE_RELATIONS__';
$live_prices = require '__LIVE_PRICES__';
$seeds = require '__SEEDS__';
$asset_manifest = require '__ASSET_MANIFEST__';

$validation = Complete99_Culinary_Science::validate_registry($science);
$capture_ids = __CAPTURE_IDS__;
$held_ids = __HELD_IDS__;
$entities = array();
$public_ids = array();
$owner_ids = array();
$route_count = 0;
$indexable_count = 0;
$public_lookup = array();
foreach ($science['entities'] as $entity) {
    $is_public = !empty($entity['publication']['public_api'])
        && !empty($entity['publication']['public_page']);
    if ($is_public) {
        $public_ids[] = $entity['id'];
        $public_lookup[$entity['id']] = true;
        if ('standalone' === $entity['seo']['route_mode']) {
            $owner_ids[] = $entity['id'];
            foreach ($entity['seo']['canonical_path'] as $path) {
                if (is_string($path) && '' !== $path) {
                    ++$route_count;
                }
            }
        }
        if (!empty($entity['publication']['search_index'])) {
            ++$indexable_count;
        }
    }
    if (in_array($entity['id'], $capture_ids, true)) {
        $entities[$entity['id']] = $entity;
    }
}

$unsupported_public_edges = array();
foreach ($science['entities'] as $entity) {
    if (!isset($public_lookup[$entity['id']])) {
        continue;
    }
    foreach ($entity['relations'] as $relation) {
        if (!empty($relation['public_safe'])
            && (empty($relation['source_ids'])
                || !isset($public_lookup[$relation['target_id']]))) {
            $unsupported_public_edges[] = $relation['id'];
        }
    }
}

$seed = array();
foreach ($seeds['products'] as $candidate) {
    if ('product-yamaroku-tsurubishio-500ml' === $candidate['product_code']) {
        $seed = $candidate;
    }
}

function c99_find_record($records, $field, $value) {
    foreach ($records as $record) {
        if (isset($record[$field]) && $value === $record[$field]) {
            return $record;
        }
    }
    return array();
}

$assets = array();
foreach ($asset_manifest['science_assets'] as $asset) {
    if (in_array($asset['related_entity_code'], $held_ids, true)) {
        $assets[$asset['related_entity_code']] = $asset;
    }
}

$commerce_bindings = array();
foreach ($commerce['products'] as $product) {
    if (in_array($product['knowledge_entity_id'], $held_ids, true)) {
        $commerce_bindings[] = $product['knowledge_entity_id'];
    }
}

sort($public_ids);
sort($owner_ids);
sort($unsupported_public_edges);

echo json_encode(array(
    'valid' => true === $validation,
    'validation_error' => is_wp_error($validation)
        ? array(
            'code' => $validation->get_error_code(),
            'message' => $validation->get_error_message(),
            'data' => $validation->get_error_data(),
        )
        : null,
    'schema' => $science['schema'],
    'version' => $science['version'],
    'generated_at' => $science['generated_at'],
    'source_count' => count($science['sources']),
    'source' => $science['sources']['zhang-industrial-koji-proteases-2023'],
    'source_receipts' => $science['source_receipts'],
    'entity_count' => count($science['entities']),
    'public_ids' => $public_ids,
    'owner_ids' => $owner_ids,
    'route_count' => $route_count,
    'indexable_count' => $indexable_count,
    'entities' => $entities,
    'unsupported_public_edges' => $unsupported_public_edges,
    'commerce_version' => $commerce['version'],
    'commerce_knowledge_version' => $commerce['knowledge_registry_version'],
    'commerce_bindings_to_new_ids' => $commerce_bindings,
    'commerce_product' => c99_find_record(
        $commerce['products'],
        'id',
        'product-yamaroku-tsurubishio-500ml'
    ),
    'commerce_variant' => c99_find_record(
        $commerce['variants'],
        'id',
        'variant-yamaroku-tsurubishio-500ml'
    ),
    'commerce_sku' => c99_find_record(
        $commerce['skus'],
        'id',
        'sku-yamaroku-tsurubishio-500ml'
    ),
    'live_readiness' => array(
        'catalog_publication_authorized' => $live_products['catalog_publication_authorized'],
        'supplier_label_reviewed' => $live_products['supplier_label_reviewed'],
        'country_of_origin_reviewed' => $live_products['country_of_origin_reviewed'],
        'checkout_eligible' => $live_products['checkout_eligible'],
        'initial_stock' => $live_products['initial_stock'],
        'backorders' => $live_products['backorders'],
        'tax_status' => $live_products['tax_status'],
    ),
    'live_product' => $live_products['products']['product-yamaroku-tsurubishio-500ml'],
    'live_relation' => $live_relations['products']['product-yamaroku-tsurubishio-500ml'],
    'live_price' => $live_prices['prices']['product-yamaroku-tsurubishio-500ml'],
    'seed' => $seed,
    'assets' => $assets,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    replacements = {
        "__PLUGIN__": _php_path(PLUGIN),
        "__SCIENCE_CLASS__": _php_path(SCIENCE_CLASS),
        "__SCIENCE__": _php_path(SCIENCE),
        "__COMMERCE__": _php_path(COMMERCE),
        "__LIVE_PRODUCTS__": _php_path(LIVE_PRODUCTS),
        "__LIVE_RELATIONS__": _php_path(LIVE_RELATIONS),
        "__LIVE_PRICES__": _php_path(LIVE_PRICES),
        "__SEEDS__": _php_path(SEEDS),
        "__ASSET_MANIFEST__": _php_path(ASSET_MANIFEST),
        "__CAPTURE_IDS__": "array(" + ",".join(
            f"'{item}'" for item in sorted(CAPTURE_IDS)
        ) + ")",
        "__HELD_IDS__": "array(" + ",".join(
            f"'{item}'" for item in HELD_IDS
        ) + ")",
    }
    for marker, value in replacements.items():
        script = script.replace(marker, value)
    return json.loads(_run_php(script))


def _public_fact_ids(entity: dict) -> set[str]:
    return {fact["id"] for fact in entity["facts"] if fact["public_safe"]}


def _public_relation_ids(entity: dict) -> set[str]:
    return {
        relation["id"]
        for relation in entity["relations"]
        if relation["public_safe"]
    }


def _fact(entity: dict, fact_id: str) -> dict:
    return next(fact for fact in entity["facts"] if fact["id"] == fact_id)


def _sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def test_v20_registry_and_bound_commerce_versions_are_valid(
    cohort_payload: dict,
) -> None:
    assert cohort_payload["valid"], cohort_payload["validation_error"]
    assert cohort_payload["schema"] == SCIENCE_SCHEMA
    assert cohort_payload["version"] == SCIENCE_VERSION
    assert cohort_payload["generated_at"] == "2026-08-08"
    assert cohort_payload["commerce_version"] == COMMERCE_VERSION
    assert cohort_payload["commerce_knowledge_version"] == SCIENCE_VERSION

    version_pattern = re.compile(
        r"'version'\s*=>\s*'culinary-science-2026\.08\.08\.v20'"
    )
    for filename in MODULE_FILES:
        text = (CUISINE_DIR / filename).read_text(encoding="utf-8")
        assert version_pattern.search(text), filename


def test_exact_public_counts_and_held_candidate_modes(cohort_payload: dict) -> None:
    assert cohort_payload["source_count"] == 375
    assert len(cohort_payload["public_ids"]) == 27
    assert len(cohort_payload["owner_ids"]) == 19
    assert cohort_payload["route_count"] == 38
    assert cohort_payload["indexable_count"] == 0

    entities = cohort_payload["entities"]
    for entity_id in HELD_IDS:
        entity = entities[entity_id]
        assert entity["surface_class"] == "editorial_draft"
        assert entity["index_policy"] == "noindex_private"
        assert entity["publication"] == {
            "state": "private_preview",
            "public_api": False,
            "public_page": False,
            "search_index": False,
            "approved_at": "",
        }
        assert entity["seo"]["route_mode"] == "private"
        assert not any(link["public_safe"] for link in entity["seo"]["link_plan"])
        assert entity_id not in cohort_payload["public_ids"]


def test_public_semantic_navigation_excludes_every_held_candidate(
    cohort_payload: dict,
) -> None:
    entities = cohort_payload["entities"]
    held = set(HELD_IDS)
    for entity in entities.values():
        targets = entity["seo"]["semantic_entity_ids"]
        assert len(targets) == len(set(targets))
        if entity["publication"]["public_page"]:
            assert not (set(targets) & held)


def test_candidate_facts_relations_and_private_boundaries_are_exact(
    cohort_payload: dict,
) -> None:
    entities = cohort_payload["entities"]
    for entity_id in HELD_IDS:
        assert _public_fact_ids(entities[entity_id]) == set()
        assert _public_relation_ids(entities[entity_id]) == set()

    equipment_edges = {
        edge["id"]: edge for edge in entities["equipment-kioke"]["relations"]
    }
    assert not equipment_edges[
        "edge-equipment-kioke-produced_by-2"
    ]["public_safe"]
    assert equipment_edges[
        "edge-equipment-kioke-produced_by-2"
    ]["target_id"] == "producer-yamaroku-shoyu"

    reaction_edge = entities["reaction-koji-enzymatic-hydrolysis"]["relations"][0]
    assert reaction_edge["source_ids"] == []
    assert not reaction_edge["public_safe"]
    assert reaction_edge["target_id"] == "ingredient-kioke-shoyu"

    shoyu_edges = {
        edge["id"]: edge for edge in entities["ingredient-kioke-shoyu"]["relations"]
    }
    assert not shoyu_edges[
        "edge-ingredient-kioke-shoyu-complements-2"
    ]["public_safe"]
    assert shoyu_edges[
        "edge-ingredient-kioke-shoyu-complements-2"
    ]["source_ids"] == []
    assert not shoyu_edges[
        "edge-ingredient-kioke-shoyu-produced_by-1"
    ]["public_safe"]
    assert cohort_payload["unsupported_public_edges"] == []


def test_verified_literature_measurements_preserve_assay_and_scope(
    cohort_payload: dict,
) -> None:
    reaction = cohort_payload["entities"]["reaction-koji-enzymatic-hydrolysis"]
    fact = _fact(reaction, "fact-koji-industrial-protease-activity-ranges")
    assert fact["evidence_class"] == "peer_reviewed_context"
    assert fact["value_scope"] == "technique_context"
    assert fact["source_ids"] == ["zhang-industrial-koji-proteases-2023"]
    assert fact["verified_at"] == "2026-08-08"
    assert fact["measurement"] == []

    expected = {
        "neutral-protease-activity": (
            500,
            700,
            "Crude extract from 5 g koji plus 45 mL liquid, stirred at 40°C for 1 h. Neutral protease used pH 7.2 phosphate buffer; 1 U was the color equivalent of 1 μg tyrosine released per mL per min at 40°C.",
        ),
        "acidic-protease-activity": (
            50,
            150,
            "Crude extract from 5 g koji plus 45 mL liquid, stirred at 40°C for 1 h. Acidic protease used pH 3.0 lactic acid buffer; 1 U was the color equivalent of 1 μg tyrosine released per mL per min at 40°C.",
        ),
        "leucine-aminopeptidase-activity": (
            50,
            250,
            "Crude extract from 5 g koji plus 45 mL liquid, stirred at 40°C for 1 h. Leucine aminopeptidase used leucine p-nitroaniline; 1 U was 1 μg p-nitroaniline released per min at 40°C.",
        ),
    }
    measurements = fact["scientific_measurements"]
    assert len(measurements) == 3
    assert {item["property"] for item in measurements} == set(expected)
    for measurement in measurements:
        low, high, method = expected[measurement["property"]]
        assert measurement["kind"] == "range"
        assert (measurement["low"], measurement["high"]) == (low, high)
        assert measurement["value"] is None
        assert measurement["unit"] == "U/g"
        assert measurement["method"] == method
        assert measurement["specimen_scope"] == "literature_context"
        assert measurement["confidence"] == "verified"
        assert measurement["source_ids"] == [
            "zhang-industrial-koji-proteases-2023"
        ]
        assert measurement["measured_at"] == ""
        assert measurement["conditions"] == {
            "cohort": "Three industrial Aspergillus oryzae strains used in Chinese and Japanese soy sauce koji.",
            "fermentation-time": "Activities reported during a 46 h koji fermentation.",
            "extraction": "Crude extract from 5 g koji plus 45 mL liquid, stirred at 40°C for 1 h.",
            "scope-boundary": "Literature context only; not a product, lot, universal range, recipe, supplier claim or operating guarantee.",
        }


def test_jas_saishikomi_fact_is_bounded_category_text_not_measurement(
    cohort_payload: dict,
) -> None:
    standard = cohort_payload["entities"]["standard-jas-shoyu-1703"]
    fact = _fact(standard, "fact-jas-saishikomi-category-thresholds")
    assert fact["dimension"] == "institutional"
    assert fact["evidence_class"] == "regulatory_standard"
    assert fact["value_scope"] == "category"
    assert fact["source_ids"] == ["jas-shoyu-1703"]
    assert fact["verified_at"] == "2026-08-08"
    assert fact["measurement"] == []
    assert fact["scientific_measurements"] == []
    statement = fact["statement"]["en"]
    for token in (
        "tentative English translation",
        "1.65, 1.50 and 1.40 g/100 mL",
        "21 and 18 g/100 mL",
        "category thresholds only",
        "not product certification",
        "Japanese original controls",
    ):
        assert token in statement


def test_sources_receipts_and_retained_evidence_are_exact(
    cohort_payload: dict,
) -> None:
    assert cohort_payload["source"] == {
        "type": "peer_reviewed_paper",
        "publisher": "Microbiology Spectrum",
        "title": "Phenotypic, Genomic, and Transcriptomic Comparison of Industrial Aspergillus oryzae Used in Chinese and Japanese Soy Sauce: Analysis of Key Proteolytic Enzymes Produced by Koji Molds",
        "url": "https://pmc.ncbi.nlm.nih.gov/articles/PMC10100866/",
        "published_at": "2023-02-06",
        "retrieved_at": "2026-08-08",
    }
    receipts = cohort_payload["source_receipts"]
    assert set(receipts) == {
        "zhang-industrial-koji-proteases-2023",
        "jas-shoyu-1703",
    }
    assert list(receipts["zhang-industrial-koji-proteases-2023"]) == [
        "schema",
        "source_id",
        "upstream_url",
        "upstream_sha256",
        "evidence_repository_path",
        "evidence_sha256",
        "retrieved_at",
        "license",
        "claim_locators",
        "review_state",
    ]

    expected = {
        "zhang-industrial-koji-proteases-2023": {
            "upstream_url": "https://www.ncbi.nlm.nih.gov/research/bionlp/RESTful/pmcoa.cgi/BioC_json/PMC10100866/unicode",
            "upstream_sha256": "fd57c0cdf14beb447ad47a0561cfc8c6fac1d356ce9cde64b10a2dbd2e1266c3",
            "evidence_repository_path": "docs/research-evidence/pmc10100866-koji-protease-evidence.json",
            "evidence_sha256": "44752ca77d881e2ccc71d7dc2fb4c2d9051c2207b7e67553b81abdc0206c4de5",
            "license": "CC-BY-4.0",
            "claim_locators": {
                "industrial-koji-protease-activity-ranges": "Results, Phenotypic comparison of three industrial Aspergillus oryzae strains, paragraph discussing Figure 2B",
                "industrial-koji-protease-assay-context": "Materials and Methods, Enzyme activity assays",
            },
        },
        "jas-shoyu-1703": {
            "upstream_url": "https://www.famic.go.jp/english/jas/_doc/jas1703.pdf",
            "upstream_sha256": "9dbbf59b5fb4f5fbb557ce6edf3835056d649490f6287bf0ac25b2614ff766d4",
            "evidence_repository_path": "docs/research-evidence/jas1703-saishikomi-evidence.json",
            "evidence_sha256": "5b7d44ac614256d86d7d547021146dbe66875045eaaca836637f0e9ea0be9357",
            "license": "official-standard-tentative-english-translation",
            "claim_locators": {
                "shoyu-koji-definition": "Section 3.2, page 4",
                "saishikomi-definition": "Section 3.12, page 5",
                "saishikomi-quality-thresholds": "Section 4.4, Table 4, page 10",
            },
        },
    }
    for source_id, expected_fields in expected.items():
        receipt = receipts[source_id]
        assert receipt["schema"] == "complete99-source-evidence-receipt/v1"
        assert receipt["source_id"] == source_id
        assert receipt["retrieved_at"] == "2026-08-08T11:34:30+03:00"
        assert receipt["review_state"] == "verified"
        for key, value in expected_fields.items():
            assert receipt[key] == value
        evidence_path = ROOT / receipt["evidence_repository_path"]
        assert evidence_path.is_file()
        assert _sha256(evidence_path) == receipt["evidence_sha256"]
        evidence = json.loads(evidence_path.read_text(encoding="utf-8"))
        assert evidence["source_id"] == source_id
        assert evidence["upstream"]["sha256"] == receipt["upstream_sha256"]


def test_assets_are_hash_bound_responsive_and_held_from_publication(
    cohort_payload: dict,
) -> None:
    entities = cohort_payload["entities"]
    asset_records = cohort_payload["assets"]
    assert set(asset_records) == set(HELD_IDS)
    for entity_id, (stem, webp_sha) in ASSETS.items():
        entity_visual = entities[entity_id]["visual"]
        assert entity_visual["asset_state"] == "rights_review_required"
        assert entity_visual["rights_method"] == "generated_concept_with_human_review"
        assert entity_visual["rights_state"] == "pending"
        assert entity_visual["rights_receipt_digest"] == ""

        asset = asset_records[entity_id]
        assert asset["slug"] == stem
        assert asset["sha256"] == webp_sha
        assert asset["review_state"] == "evaluation"
        assert asset["usage_state"] == "held"
        assert asset["presentation_scope"] == "illustrative_evaluation_only"
        assert asset["actual_product_presentation"] is False
        assert asset["rights_receipt_digest"] == ""
        assert asset["publication_approval_state"] == (
            "held_pending_owner_approval"
        )
        assert asset["publication_approval_receipt_id"] == ""
        assert set(asset["files"]) == {
            "png",
            "webp",
            "avif",
            "webp_768",
            "avif_768",
        }
        for variant, record in asset["files"].items():
            path = PLUGIN / record["relative_path"]
            assert path.is_file(), path
            assert path.stat().st_size == record["bytes"]
            assert _sha256(path) == record["sha256"]
            expected_size = (768, 512) if variant.endswith("_768") else (1536, 1024)
            assert (record["width"], record["height"]) == expected_size


def test_queries_are_exact_and_candidate_copy_has_no_internal_jargon(
    cohort_payload: dict,
) -> None:
    entities = cohort_payload["entities"]
    for entity_id, expected_queries in QUERY_VARIANTS.items():
        entity = entities[entity_id]
        assert entity["seo"]["query_variants"] == expected_queries
        for language in ("he", "en"):
            assert entity["seo"]["primary_keyword"][language] == (
                expected_queries[language][0]
            )

    jargon = re.compile(r"\b(?:entity|sku|registry|test|pilot)\b", re.IGNORECASE)
    hebrew_jargon = re.compile(r"(?:ישות|פיילוט|רג׳יסטרי|מק״ט)")
    copy_scope_ids = set(HELD_IDS) | {
        "ingredient-kioke-shoyu",
        "ingredient-kome-koji",
        "ingredient-koji-starter-culture",
    }
    for entity_id in copy_scope_ids:
        entity = entities[entity_id]
        strings: list[str] = []
        for field in ("name", "summary"):
            strings.extend(entity[field].values())
        for field in ("title", "h1", "meta_description", "opening"):
            strings.extend(entity["seo"][field].values())
        public_fact_ids = _public_fact_ids(entity)
        for fact in entity["facts"]:
            if fact["id"] in public_fact_ids:
                strings.extend(fact["statement"].values())
        for relation in entity["relations"]:
            if relation["public_safe"]:
                strings.extend(relation["note"].values())
        for profile in entity["profiles"].values():
            if (
                profile["state"] == "source_backed"
                and set(profile["fact_ids"]) & public_fact_ids
            ):
                strings.extend(profile["summary"].values())
        for compliance in entity["compliance"]:
            if compliance["public_safe"]:
                strings.extend(compliance["note"].values())
        for text in strings:
            assert not jargon.search(text), (entity_id, text)
            assert not hebrew_jargon.search(text), (entity_id, text)


def test_woo_binding_price_and_readiness_remain_exactly_bounded(
    cohort_payload: dict,
) -> None:
    entities = cohort_payload["entities"]
    for entity_id in HELD_IDS:
        commerce = entities[entity_id]["commerce"]
        assert commerce["woo_product_code"] == ""
        assert not commerce["public_offer_allowed"]
    assert cohort_payload["commerce_bindings_to_new_ids"] == []

    shoyu = entities["ingredient-kioke-shoyu"]
    assert shoyu["commerce"]["state"] == "active_offer"
    assert shoyu["commerce"]["woo_product_code"] == (
        "product-yamaroku-tsurubishio-500ml"
    )
    assert shoyu["commerce"]["public_offer_allowed"] is True
    assert shoyu["commerce"]["business_model"]["pricing_state"] == (
        "approved_sell_price"
    )

    relation = cohort_payload["live_relation"]
    assert relation["ingredient_code"] == "ingredient-kioke-shoyu"
    assert relation["science_entity_id"] == "ingredient-kioke-shoyu"
    assert relation["dish_slugs"] == []
    assert cohort_payload["live_price"] == "149.00"
    assert cohort_payload["live_product"]["category"] == "japanese-pantry"
    assert {"soy", "wheat", "shoyu", "fermentation"} <= set(
        cohort_payload["live_product"]["tags"]
    )
    assert cohort_payload["live_readiness"] == {
        "catalog_publication_authorized": True,
        "supplier_label_reviewed": False,
        "country_of_origin_reviewed": False,
        "checkout_eligible": False,
        "initial_stock": 1,
        "backorders": "no",
        "tax_status": "taxable",
    }

    seed = cohort_payload["seed"]
    assert seed["public_sale_eligible"] is False
    assert seed["sale_state"] == "held_until_acceptance"
    assert seed["evaluation_price_ils"] == 149
    assert seed["acceptance_gates"]
    assert not any(seed["acceptance_gates"].values())

    assert cohort_payload["commerce_product"]["state"] == "research_candidate"
    assert cohort_payload["commerce_product"]["knowledge_entity_id"] == (
        "ingredient-kioke-shoyu"
    )
    assert cohort_payload["commerce_variant"]["state"] == "research_candidate"
    assert cohort_payload["commerce_sku"]["state"] == "research_candidate"
    assert cohort_payload["commerce_sku"]["woo_product_code"] == ""
    assert cohort_payload["commerce_sku"]["inventory_policy"] == "research_only"


def test_private_producer_and_listing_remain_private(cohort_payload: dict) -> None:
    entities = cohort_payload["entities"]
    for entity_id in (
        "producer-yamaroku-shoyu",
        "listing-yamaroku-tsurubishio-500ml-20260806",
    ):
        entity = entities[entity_id]
        assert entity["seo"]["route_mode"] == "private"
        assert entity["publication"]["state"] != "approved_public"
        assert not entity["publication"]["public_api"]
        assert not entity["publication"]["public_page"]
        assert not entity["publication"]["search_index"]


@pytest.mark.parametrize(
    "php_file",
    [SCIENCE, COMMERCE, *(CUISINE_DIR / name for name in MODULE_FILES)],
)
def test_owned_php_files_lint_cleanly(php_file: Path) -> None:
    completed = subprocess.run(
        ["php", "-l", str(php_file)],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=30,
    )
    assert completed.returncode == 0, completed.stdout + completed.stderr
