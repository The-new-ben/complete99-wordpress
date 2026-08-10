from __future__ import annotations

import json
import shutil
import subprocess
from collections import Counter
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
DATA = PLUGIN / "data" / "cross-domain-bindings.php"
DECISIONS = PLUGIN / "data" / "cross-domain-binding-decisions.php"
BINDING_CLASS = (
    PLUGIN / "includes" / "class-complete99-cross-domain-bindings.php"
)
GENERATOR = ROOT / "scripts" / "generate-cross-domain-binding-seed.php"

EXPECTED_SEED_SHA256 = (
    "1ab6190df1443ca3e7f31103ce9c9ecd07cf25e5000c216bbebe9ccf909a4928"
)
EXPECTED_RECORD_IDS_SHA256 = (
    "8d1c7300a4f4aa0b5f74d4a77d78bb812ac2656ef534137a5f26d87a14f78f1e"
)
EXPECTED_INPUT_CONTRACTS_SHA256 = (
    "02db123c7e62b1ab616793508a1c6d1941e8015ee2111fa2b3df14b8042025ce"
)
EXPECTED_DECISION_OVERLAY_SHA256 = (
    "bf42940d9ef0104c1bbc67c6b61871f55b77b901ba4445d332778c59c7cea73c"
)
EMPTY_LIST_SHA256 = (
    "4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945"
)

RESOLUTION_READY_RECIPROCAL_PAIRS = {
    "product-fresh-japanese-wasabi-250g": "ingredient-fresh-wasabi",
    "product-fresh-wasabi-50-60g": "ingredient-fresh-dutch-wasabi",
    "product-hagane-zame-large": "equipment-wasabi-grater",
    "product-hishiroku-chouhaku-kin-20g": "ingredient-koji-starter-culture",
    "product-hishiroku-dried-rice-koji-500g": "ingredient-kome-koji",
    "product-honkarebushi-200g": "ingredient-katsuobushi",
    "product-kito-yuzu-juice-100ml": "ingredient-kito-yuzu",
    "product-koshihikari-uozu-2kg": "ingredient-koshihikari-rice",
    "product-rishiri-kombu-100g": "ingredient-kombu",
    "product-yamaroku-tsurubishio-500ml": "ingredient-kioke-shoyu",
}


EXPECTED_INPUTS = {
    "consumer_menu": {
        "source_path": "data/consumer-menu.php",
        "source_schema": "complete99-consumer-menu-array/v1",
        "source_version": "unversioned",
        "payload_sha256": "134da0d6cefe66790dc4551e4aa95453bfa58b80667c68749ec3d7791bca869f",
    },
    "dish_entity_trees": {
        "source_path": "data/dish-entity-trees.php",
        "source_schema": "complete99-dish-entity-tree-registry/v1",
        "source_version": "registry-reviewed-2026-07-31",
        "payload_sha256": "4d7a19fba4e0cb4b17b86542bb0229341830bc79debf8ac13cb545ec2329c264",
    },
    "catalog_product_seeds": {
        "source_path": "data/catalog-product-seeds.php",
        "source_schema": "complete99-catalog-product-seeds/v1",
        "source_version": "reviewed-2026-08-06",
        "payload_sha256": "6049f5d6d951df273481f6200dca6c1ba895817c0345e1b74a5424be2fb1b132",
    },
    "culinary_science": {
        "source_path": "data/culinary-science-pilot.php",
        "source_schema": "complete99-culinary-science-registry/v6",
        "source_version": "culinary-science-2026.08.08.v20",
        "payload_sha256": "677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54",
    },
    "live_catalog_products": {
        "source_path": "data/live-catalog-products.php",
        "source_schema": "complete99-live-catalog-products/v1",
        "source_version": "reviewed-2026-08-06",
        "payload_sha256": "56a8fbddade21570f874e19a2dc7f8562edf0ab6b11f9d14b79a95116391339f",
    },
    "live_catalog_relations": {
        "source_path": "data/live-catalog-relations.php",
        "source_schema": "complete99-live-catalog-relations/v1",
        "source_version": "reviewed-2026-08-06",
        "payload_sha256": "debdd5785e539c55ab9b0ab53c911ae3d7f842dc3ede9f077d59d4ab96c9faf5",
    },
}


EXPECTED_VOCABULARY = {
    "binding_kinds": [
        "menu_dish_science_dish",
        "menu_component_science_entity",
        "woo_product_science_entity",
    ],
    "resolution_states": ["linked", "no_match", "unresolved"],
    "registries": [
        "consumer_menu",
        "dish_entity_trees",
        "culinary_science",
        "woocommerce",
    ],
    "entity_types": [
        "dish",
        "component",
        "ingredient",
        "preparation",
        "equipment",
        "product",
    ],
    "relations": [
        "same_dish_identity",
        "house_expression_of",
        "reference_only",
        "same_ingredient_identity",
        "same_preparation_identity",
        "retail_instance_of",
    ],
    "projection_scopes": [
        "private_only",
        "public_navigation",
        "public_product_navigation",
    ],
    "review_states": ["unreviewed", "source_reviewed", "verified"],
    "candidate_states": ["pending_review", "rejected"],
    "candidate_reason_codes": [
        "legacy_explicit_relation_requires_review",
        "insufficient_evidence",
        "scope_mismatch",
        "different_variant",
        "component_is_composite",
        "product_identity_unverified",
        "target_type_mismatch",
        "duplicate_conflict",
    ],
    "evidence_registries": [
        "dish_source_registry",
        "culinary_science_sources",
        "culinary_science_registry",
        "catalog_product_seeds",
        "live_catalog_products",
        "live_catalog_relations",
    ],
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


def _run_php_json(script: str, *, timeout: int = 60) -> object:
    if not shutil.which("php"):
        pytest.skip("PHP is required for executable binding registry checks")
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


@pytest.fixture(scope="module")
def registry() -> dict:
    payload = _run_php_json(
        "define('ABSPATH', __DIR__); "
        f"$value = require '{_php_path(DATA)}'; "
        "echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);"
    )
    assert isinstance(payload, dict)
    return payload


@pytest.fixture(scope="module")
def decision_overlay() -> dict:
    payload = _run_php_json(
        "define('ABSPATH', __DIR__); "
        f"$value = require '{_php_path(DECISIONS)}'; "
        "echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);"
    )
    assert isinstance(payload, dict)
    return payload


@pytest.fixture(scope="module")
def binding_digests() -> dict:
    script = rf"""
define('ABSPATH', __DIR__);
$seed = require '{_php_path(DATA)}';
$overlay = require '{_php_path(DECISIONS)}';
$canonicalize = static function ($value) use (&$canonicalize) {{
    if (!is_array($value)) {{ return $value; }}
    $is_list = empty($value) || array_keys($value) === range(0, count($value) - 1);
    if ($is_list) {{ return array_map($canonicalize, $value); }}
    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) {{ $value[$key] = $canonicalize($item); }}
    return $value;
}};
$digest = static function ($value) use ($canonicalize) {{
    return hash('sha256', json_encode(
        $canonicalize($value),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ));
}};
echo json_encode(array(
    'seed' => $digest($seed),
    'record_ids' => $digest(array_column($seed['records'], 'id')),
    'input_contracts' => $digest($seed['input_contracts']),
    'overlay' => $digest($overlay),
    'decisions' => $digest($overlay['decisions']),
    'reviewer_authorities' => $digest(array()),
), JSON_UNESCAPED_SLASHES);
"""
    payload = _run_php_json(script)
    assert isinstance(payload, dict)
    return payload


@pytest.fixture(scope="module")
def source_census() -> dict:
    script = rf"""
define('ABSPATH', __DIR__);
$base = '{_php_path(PLUGIN / 'data')}/';
$menu = require $base . 'consumer-menu.php';
$trees = require $base . 'dish-entity-trees.php';
$seeds = require $base . 'catalog-product-seeds.php';
$science = require $base . 'culinary-science-pilot.php';
$products = require $base . 'live-catalog-products.php';
$relations = require $base . 'live-catalog-relations.php';

$canonicalize = static function ($value) use (&$canonicalize) {{
    if (!is_array($value)) {{
        return $value;
    }}
    $is_list = empty($value) || array_keys($value) === range(0, count($value) - 1);
    if ($is_list) {{
        return array_map($canonicalize, $value);
    }}
    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) {{
        $value[$key] = $canonicalize($item);
    }}
    return $value;
}};
$science_canonical_json = json_encode(
    $canonicalize($science),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);

$walk = static function ($children, &$seen, &$codes) use (&$walk) {{
    foreach ($children as $child) {{
        if (!isset($seen[$child['code']])) {{
            $seen[$child['code']] = true;
            $codes[] = $child['code'];
        }}
        $walk($child['children'], $seen, $codes);
    }}
}};

$menu_map = array();
foreach ($menu as $dish) {{
    $menu_map[$dish['id']] = $dish['slug'];
}}
$components = array();
foreach ($trees['dishes'] as $dish) {{
    $seen = array();
    $codes = array();
    $walk($dish['component_tree']['children'], $seen, $codes);
    foreach ($dish['relations']['ingredient_codes'] as $code) {{
        if (!isset($seen[$code])) {{
            $seen[$code] = true;
            $codes[] = $code;
        }}
    }}
    foreach ($codes as $code) {{
        $components[] = array(
            'dish_id' => $dish['dish_id'],
            'code' => $code,
            'entity_type' => 0 === strpos($code, 'ingredient-') ? 'ingredient' : 'component',
        );
    }}
}}

$seed_codes = array();
foreach ($seeds['products'] as $product) {{
    $seed_codes[] = $product['product_code'];
}}
$science_by_product = array();
foreach ($science['entities'] as $entity) {{
    $product_code = $entity['commerce']['woo_product_code'] ?? '';
    if ('' !== $product_code) {{
        $science_by_product[$product_code] = array($entity['id'], $entity['type']);
    }}
}}
$reciprocal = array();
foreach ($relations['products'] as $product_code => $relation) {{
    $entity_id = $relation['science_entity_id'] ?? '';
    if ('' !== $entity_id
        && isset($science_by_product[$product_code])
        && $science_by_product[$product_code][0] === $entity_id) {{
        $reciprocal[$product_code] = $science_by_product[$product_code];
    }}
}}
ksort($reciprocal, SORT_STRING);

echo json_encode(array(
    'menu' => $menu_map,
    'components' => $components,
    'seed_products' => $seed_codes,
    'live_products' => array_keys($products['products']),
    'relation_products' => array_keys($relations['products']),
    'reciprocal' => $reciprocal,
    'science_schema' => $science['schema'],
    'science_version' => $science['version'],
    'science_canonical_bytes' => strlen($science_canonical_json),
    'science_canonical_sha256' => hash('sha256', $science_canonical_json),
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
"""
    payload = _run_php_json(script)
    assert isinstance(payload, dict)
    return payload


def test_registry_envelope_inputs_and_vocabulary_are_exact(registry: dict) -> None:
    assert list(registry) == [
        "schema",
        "version",
        "generated_at",
        "input_contracts",
        "controlled_vocabulary",
        "records",
    ]
    assert registry["schema"] == "complete99-cross-domain-binding-registry/v3"
    assert registry["version"] == "complete99-cross-domain-bindings-2026.08.08.v3"
    assert registry["generated_at"] == "2026-08-08"
    assert registry["input_contracts"] == EXPECTED_INPUTS
    assert registry["controlled_vocabulary"] == EXPECTED_VOCABULARY


def test_zero_decision_overlay_is_exactly_bound_to_seed_inputs_and_authorities(
    registry: dict, decision_overlay: dict, binding_digests: dict
) -> None:
    assert list(decision_overlay) == [
        "schema",
        "version",
        "generated_at",
        "seed_contract",
        "input_contracts_sha256",
        "reviewer_authorities_sha256",
        "decision_count",
        "decisions_sha256",
        "decisions",
    ]
    assert decision_overlay["schema"] == (
        "complete99-cross-domain-binding-decision-overlay/v1"
    )
    assert decision_overlay["version"] == (
        "complete99-cross-domain-binding-decisions-2026.08.08.v1"
    )
    assert decision_overlay["generated_at"] == "2026-08-08"
    assert decision_overlay["seed_contract"] == {
        "schema": registry["schema"],
        "version": registry["version"],
        "payload_sha256": EXPECTED_SEED_SHA256,
        "record_count": 95,
        "record_ids_sha256": EXPECTED_RECORD_IDS_SHA256,
    }
    assert decision_overlay["input_contracts_sha256"] == (
        EXPECTED_INPUT_CONTRACTS_SHA256
    )
    assert decision_overlay["reviewer_authorities_sha256"] == EMPTY_LIST_SHA256
    assert decision_overlay["decision_count"] == 0
    assert decision_overlay["decisions_sha256"] == EMPTY_LIST_SHA256
    assert decision_overlay["decisions"] == []
    assert binding_digests == {
        "seed": EXPECTED_SEED_SHA256,
        "record_ids": EXPECTED_RECORD_IDS_SHA256,
        "input_contracts": EXPECTED_INPUT_CONTRACTS_SHA256,
        "overlay": EXPECTED_DECISION_OVERLAY_SHA256,
        "decisions": EMPTY_LIST_SHA256,
        "reviewer_authorities": EMPTY_LIST_SHA256,
    }

    source = BINDING_CLASS.read_text(encoding="utf-8")
    assert f"const SEED_PAYLOAD_SHA256 = '{EXPECTED_SEED_SHA256}';" in source
    assert (
        "const DECISION_OVERLAY_PAYLOAD_SHA256 = "
        f"'{EXPECTED_DECISION_OVERLAY_SHA256}';"
    ) in source
    assert "return array();" in source.split(
        "private static function recognized_reviewer_authorities()", 1
    )[1].split("private static function", 1)[0]


def test_v3_byte_binds_the_exact_v20_science_payload(source_census: dict) -> None:
    assert source_census["science_schema"] == (
        "complete99-culinary-science-registry/v6"
    )
    assert source_census["science_version"] == "culinary-science-2026.08.08.v20"
    assert source_census["science_canonical_bytes"] == 9_820_452
    assert source_census["science_canonical_sha256"] == (
        "677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54"
    )


def test_records_cover_exact_current_subject_sets(
    registry: dict, source_census: dict
) -> None:
    records = registry["records"]
    assert len(records) == 95
    assert [record["id"] for record in records] == sorted(
        record["id"] for record in records
    )
    assert len({record["id"] for record in records}) == 95
    assert Counter(record["kind"] for record in records) == {
        "menu_dish_science_dish": 12,
        "menu_component_science_entity": 47,
        "woo_product_science_entity": 36,
    }

    dish_subjects = {
        record["subject"]["entity_id"]
        for record in records
        if record["kind"] == "menu_dish_science_dish"
    }
    assert dish_subjects == set(source_census["menu"])

    component_subjects = {
        (
            record["subject"]["scope_entity_id"],
            record["subject"]["entity_id"],
            record["subject"]["entity_type"],
        )
        for record in records
        if record["kind"] == "menu_component_science_entity"
    }
    assert component_subjects == {
        (item["dish_id"], item["code"], item["entity_type"])
        for item in source_census["components"]
    }
    assert len(component_subjects) == 47
    assert len({item[1] for item in component_subjects}) == 35

    product_subjects = {
        record["subject"]["entity_id"]
        for record in records
        if record["kind"] == "woo_product_science_entity"
    }
    assert product_subjects == set(source_census["seed_products"])
    assert product_subjects == set(source_census["live_products"])
    assert product_subjects == set(source_census["relation_products"])


def test_every_seed_record_is_unresolved_unreviewed_and_non_projecting(
    registry: dict,
) -> None:
    expected_record_keys = [
        "id",
        "kind",
        "subject",
        "resolution_state",
        "targets",
        "candidates",
        "decision_evidence_refs",
        "decision_note",
        "review",
        "valid_from",
        "valid_to",
    ]
    for record in registry["records"]:
        assert list(record) == expected_record_keys
        assert list(record["subject"]) == [
            "registry",
            "entity_type",
            "entity_id",
            "scope_entity_id",
        ]
        assert record["resolution_state"] == "unresolved"
        assert record["targets"] == []
        assert record["decision_note"] == {"he": "", "en": ""}
        assert record["review"] == {
            "state": "unreviewed",
            "reviewer_id": "",
            "reviewed_at": "",
            "next_review_at": "",
        }
        assert record["valid_from"] == ""
        assert record["valid_to"] == ""
        if record["kind"] == "menu_component_science_entity":
            assert record["subject"]["scope_entity_id"]
        else:
            assert record["subject"]["scope_entity_id"] == ""


def test_only_reciprocal_machine_structured_product_edges_become_candidates(
    registry: dict, source_census: dict
) -> None:
    records_with_candidates = [
        record for record in registry["records"] if record["candidates"]
    ]
    assert len(records_with_candidates) == 11
    assert all(
        record["kind"] == "woo_product_science_entity"
        for record in records_with_candidates
    )
    assert all(
        record["candidates"] == []
        for record in registry["records"]
        if record["kind"] != "woo_product_science_entity"
    )

    actual = {}
    for record in records_with_candidates:
        product_code = record["subject"]["entity_id"]
        candidate = record["candidates"][0]
        assert list(candidate) == [
            "registry",
            "entity_type",
            "entity_id",
            "state",
            "reason_code",
        ]
        assert candidate["registry"] == "culinary_science"
        assert candidate["state"] == "pending_review"
        expected_reason = (
            "scope_mismatch"
            if product_code == "product-bulgur-fine-500g"
            else "legacy_explicit_relation_requires_review"
        )
        assert candidate["reason_code"] == expected_reason
        assert record["decision_evidence_refs"] == [
            {
                "registry": "culinary_science_registry",
                "record_id": candidate["entity_id"],
            },
            {"registry": "live_catalog_products", "record_id": product_code},
            {"registry": "live_catalog_relations", "record_id": product_code},
        ]
        actual[product_code] = [candidate["entity_id"], candidate["entity_type"]]

    assert actual == source_census["reciprocal"]
    assert len(actual) == 11
    assert Counter(target_type for _, target_type in actual.values()) == {
        "ingredient": 10,
        "equipment": 1,
    }

    assert {
        product_code: entity_id
        for product_code, (entity_id, _entity_type) in actual.items()
        if product_code != "product-bulgur-fine-500g"
    } == RESOLUTION_READY_RECIPROCAL_PAIRS
    assert actual["product-bulgur-fine-500g"] == [
        "ingredient-syrian-bulgur",
        "ingredient",
    ]

    for record in registry["records"]:
        if not record["candidates"]:
            assert record["decision_evidence_refs"] == []


def test_generator_reproduces_checked_in_logical_payload(registry: dict) -> None:
    if not shutil.which("php"):
        pytest.skip("PHP is required for seed generation checks")
    checked = subprocess.run(
        ["php", str(GENERATOR), "--check"],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
    )
    assert checked.stdout.strip() == (
        "cross-domain binding v3 verified: 95 unresolved records, "
        "11 pending candidates, 0 decisions, 0 reviewer authorities"
    )
    generated = subprocess.run(
        ["php", str(GENERATOR), "--json"],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
    )
    assert json.loads(generated.stdout) == registry


def test_generator_has_no_lexical_fuzzy_or_transitive_candidate_path() -> None:
    source = GENERATOR.read_text(encoding="utf-8")
    for forbidden in (
        "levenshtein",
        "similar_text",
        "soundex",
        "metaphone",
        "strtolower",
        "mb_strtolower",
        "dish_slugs",
        "related_product_codes",
    ):
        assert forbidden not in source
    assert "commerce']['woo_product_code" in source
    assert "['science_entity_id']" in source
    assert "references are not reciprocal" in source


def test_documented_candidate_triage_preserves_the_approval_boundary() -> None:
    documentation = (
        ROOT / "docs" / "cross-domain-binding-registry.md"
    ).read_text(encoding="utf-8")
    for product_code, entity_id in RESOLUTION_READY_RECIPROCAL_PAIRS.items():
        assert product_code in documentation
        assert entity_id in documentation
    for marker in (
        "Ten reciprocal pairs are technically ready",
        "product-bulgur-fine-500g",
        "ingredient-syrian-bulgur",
        "scope_mismatch",
        "reference_only",
        "All 12 dishes and all 47 scoped components remain unresolved",
        "zero decisions",
        "zero recognized reviewer authorities",
        "A self-digest is not identity authentication",
    ):
        assert marker in documentation
