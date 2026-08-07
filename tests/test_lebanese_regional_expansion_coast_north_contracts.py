from __future__ import annotations

import json
import re
import subprocess
from collections import Counter
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"
COAST_MODULE = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "lebanese-regional-expansion-coast-north.php"
)

EXPECTED_BY_TYPE = {
    "topic_hub": {
        "hub-beirut-urban-food-history",
        "hub-tripoli-sweets-breakfast-souks",
        "hub-akkar-seasonal-grain-greens",
        "hub-mount-lebanon-shouf-rural-table",
        "hub-north-coast-batroun-jbeil-foodways",
    },
    "dish": {
        "dish-foul-bi-selek-beirut",
        "dish-fatayer-kishk-ras-beirut",
        "dish-kaak-orchali-beirut-context",
        "dish-halawet-shmayseh-tripoli",
        "dish-tripoli-foul-hummus-breakfast-pairing",
        "dish-jazarieh-tripoli-context",
        "dish-wheat-laban-akkar-easter",
        "dish-marshousheh-minyara-family",
        "dish-omayshe-chouf",
        "dish-mansoufeh-chouf-west-bekaa",
        "dish-kaak-eid-chouf",
        "dish-akkoub-stew-chouf-dahr-el-baydar",
    },
    "ingredient": {
        "ingredient-spring-fava-bean-beirut-context",
        "ingredient-swiss-chard-beirut-context",
        "ingredient-black-eyed-pea-minyara-context",
        "ingredient-akkar-potato-value-chain-context",
        "ingredient-akkar-leafy-greens-value-chain-context",
        "ingredient-akkar-olive-fruit-context",
        "ingredient-akkar-citrus-context",
        "ingredient-akkoub-gundelia-chouf-context",
    },
    "molecule": {
        "molecule-carvacrol-origanum-syriacum-context",
        "molecule-thymol-origanum-syriacum-context",
        "molecule-p-cymene-origanum-syriacum-context",
    },
    "reaction": {
        "reaction-tahini-water-hydration-phase-transition",
        "reaction-bread-crust-maillard-manouche",
        "reaction-rice-starch-gelatinization-tripoli-sweets",
        "reaction-lactic-fermentation-ambarees-sirdeleh",
    },
    "technique": {
        "technique-tahini-citrus-sauce-kibbeh-arnabiyyeh",
        "technique-foul-bi-selek-staged-cooking",
        "technique-halawet-shmayseh-low-heat-forming",
        "technique-marshousheh-minyara-steaming",
        "technique-wheat-laban-cook-cool-akkar",
        "technique-mansoufeh-shape-poach-finish",
        "technique-akkoub-cleaning-cooking-chouf",
        "technique-dakoujeh-historical-pantry-storage",
    },
    "equipment": {
        "equipment-manouche-convex-saj",
        "equipment-manouche-flat-saj",
        "equipment-manouche-tabouneh-bakery-oven",
        "equipment-kibbeh-stone-mortar-wooden-pestle",
        "equipment-seafood-calibrated-probe-thermometer",
        "equipment-dakoujeh-clay-storage-vessel",
    },
    "tradition": {
        "tradition-beirut-1885-cookbook-print-culture",
        "tradition-beirut-seasonal-foul-bi-selek",
        "tradition-tripoli-halwanji-sweets-craft",
        "tradition-tripoli-ramadan-sweets-context",
        "tradition-akkar-easter-wheat-laban",
        "tradition-chouf-adha-kaak-community-baking",
        "tradition-darb-el-karam-food-trail",
    },
    "market": {
        "market-tripoli-old-souks-food-context",
        "market-halba-produce-system-historical-context",
        "market-menhem-trading-batroun-benchmark",
    },
    "culinary_institution": {
        "institution-jamaat-al-noor-minyara-community-kitchen",
        "institution-akletna-community-kitchen-network",
        "institution-aub-esdu-food-heritage-research",
    },
    "restaurant": {
        "restaurant-akra-tripoli-breakfast-benchmark",
        "restaurant-aal-baher-byblos-seafood-benchmark",
    },
}
EXPECTED_IDS = set().union(*EXPECTED_BY_TYPE.values())
EXPECTED_COUNTS = {kind: len(ids) for kind, ids in EXPECTED_BY_TYPE.items()}

EXPECTED_SOURCES = {
    "lebanon-ich-register-2024",
    "unesco-tripoli-old-city-2019",
    "mot-dakoujeh-beit-chabeb",
    "aub-beirut-cookbook-1885-chapter",
    "ilo-akkar-potato-leafy-greens",
    "aub-khreibet-el-jundi-agrarian-transition",
    "aub-maingate-marshousheh-minyara",
    "food-heritage-foul-b-selek",
    "food-heritage-kishk-turnovers-ras-beirut",
    "food-heritage-wheat-laban-akkar",
    "food-heritage-omayshe-chouf",
    "food-heritage-kaak-eid-chouf",
    "food-heritage-mansoufeh",
    "food-heritage-akkoub-stew",
    "food-heritage-jamaat-al-noor-minyara",
    "food-heritage-akletna-community-kitchens",
    "aub-esdu-food-heritage-foundation",
    "peck-tripoli-sweets-craft-2022",
    "lebanon-traveler-sweets-2015-2016",
    "lebanon-traveler-tripoli-breakfast-2018",
    "food-heritage-pumpkin-jazarieh-context",
    "aub-darb-el-karam-brochure",
    "menhem-trading-batroun-official",
    "hello-byblos-aal-baher",
    "hou-tahini-water-rheology-2017",
    "bread-production-maillard-review-2024",
    "rice-starch-chemistry-review-2025",
    "origanum-syriacum-review-2022",
    "labneh-ambaris-microbiota-2022",
    "aub-wild-plant-collection-lebanon",
    "fda-seafood-safe-handling",
}

HELD_IDS = {
    "dish-kaak-orchali-beirut-context",
    "dish-jazarieh-tripoli-context",
    "market-halba-produce-system-historical-context",
    "restaurant-akra-tripoli-breakfast-benchmark",
    "restaurant-aal-baher-byblos-seafood-benchmark",
}

STANDARD_SAFETY_EXPECTATIONS = {
    "dish-fatayer-kishk-ras-beirut": {
        "allergen-gluten",
        "traditional-dairy-food-safety",
        "cold-chain-control",
    },
    "dish-halawet-shmayseh-tripoli": {
        "allergen-nuts",
        "traditional-dairy-food-safety",
        "cold-chain-control",
    },
    "dish-tripoli-foul-hummus-breakfast-pairing": {"allergen-sesame"},
    "dish-wheat-laban-akkar-easter": {
        "allergen-gluten",
        "traditional-dairy-food-safety",
        "cold-chain-control",
    },
    "dish-kaak-eid-chouf": {"allergen-gluten", "allergen-sesame"},
    "dish-akkoub-stew-chouf-dahr-el-baydar": {"wild-plant-identification"},
    "reaction-tahini-water-hydration-phase-transition": {
        "allergen-sesame",
        "cold-chain-control",
    },
    "reaction-bread-crust-maillard-manouche": {
        "allergen-gluten",
        "open-fire-safety",
    },
    "reaction-lactic-fermentation-ambarees-sirdeleh": {
        "traditional-dairy-food-safety",
        "cold-chain-control",
    },
    "equipment-manouche-convex-saj": {"open-fire-safety"},
    "equipment-manouche-flat-saj": {"open-fire-safety"},
    "equipment-manouche-tabouneh-bakery-oven": {"open-fire-safety"},
    "equipment-seafood-calibrated-probe-thermometer": {
        "fish-food-safety",
        "cold-chain-control",
    },
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def payload() -> dict:
    class_path = _php_path(SCIENCE_CLASS)
    data_path = _php_path(SCIENCE_DATA)
    plugin_path = _php_path(PLUGIN) + "/"
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
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
require '{class_path}';
$registry = require '{data_path}';
$module = $c99_lebanese_coast_north_module;
$validation = Complete99_Culinary_Science::validate_registry($registry);
if (true !== $validation) {{
    fwrite(STDERR, json_encode(array(
        'code' => $validation->get_error_code(),
        'message' => $validation->get_error_message(),
        'data' => $validation->get_error_data(),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    exit(2);
}}
echo json_encode(array(
    'module' => $module,
    'registry_version' => $registry['version'],
    'registry_entity_ids' => array_column($registry['entities'], 'id'),
    'registry_source_ids' => array_keys($registry['sources']),
    'foundation_source_ids' => array_keys($c99_lebanese_sources),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=120,
    )
    assert completed.returncode == 0, completed.stderr
    return json.loads(completed.stdout)


@pytest.fixture(scope="module")
def module(payload: dict) -> dict:
    return payload["module"]


@pytest.fixture(scope="module")
def entities(module: dict) -> dict[str, dict]:
    return {entity["id"]: entity for entity in module["entities"]}


def _bilingual(value: dict) -> None:
    assert set(value) == {"he", "en"}
    assert value["he"].strip()
    assert value["en"].strip()


def test_v17_module_contract_is_exact(payload: dict, module: dict) -> None:
    assert COAST_MODULE.is_file()
    assert set(module) == {
        "schema",
        "version",
        "sources",
        "entities",
        "private_entity_ids",
        "counts",
    }
    assert module["schema"] == (
        "complete99-lebanese-regional-expansion-coast-north/v1"
    )
    assert module["version"] == "culinary-science-2026.08.07.v17"
    assert payload["registry_version"] == "culinary-science-2026.08.07.v17"


def test_exact_entity_inventory_and_type_distribution(
    module: dict, entities: dict[str, dict]
) -> None:
    assert set(entities) == EXPECTED_IDS
    assert len(entities) == 61
    assert set(module["private_entity_ids"]) == EXPECTED_IDS
    assert len(module["private_entity_ids"]) == 61
    actual = Counter(entity["type"] for entity in entities.values())
    assert dict(actual) == EXPECTED_COUNTS
    assert module["counts"] == {
        "by_type": EXPECTED_COUNTS,
        "total_entities": 61,
    }


def test_exact_new_source_registry_is_https_and_disjoint_from_foundation(
    payload: dict, module: dict
) -> None:
    assert set(module["sources"]) == EXPECTED_SOURCES
    assert len(module["sources"]) == 31
    assert EXPECTED_SOURCES.isdisjoint(payload["foundation_source_ids"])
    for source in module["sources"].values():
        assert set(source) == {
            "type",
            "publisher",
            "title",
            "url",
            "published_at",
            "retrieved_at",
        }
        assert source["publisher"].strip()
        assert source["title"].strip()
        assert source["url"].startswith("https://")
        assert source["retrieved_at"] == "2026-08-07"


def test_every_entity_is_private_noindex_and_reference_only(
    entities: dict[str, dict]
) -> None:
    for entity_id, entity in entities.items():
        assert entity["surface_class"] == "editorial_draft", entity_id
        assert entity["index_policy"] == "noindex_private", entity_id
        assert entity["publication"] == {
            "state": "private_preview",
            "public_api": False,
            "public_page": False,
            "search_index": False,
            "approved_at": "",
        }, entity_id
        assert entity["seo"]["route_mode"] == "private", entity_id
        assert entity["seo"]["cluster_id"] == (
            "cluster-lebanese-regional-cuisine"
        ), entity_id
        assert entity["seo"]["hub_entity_id"] == "cuisine-lebanese-regional"
        assert entity["seo"]["intent_classes"] == ["informational"]
        assert entity["commerce"]["state"] == "reference_only", entity_id
        assert entity["commerce"]["woo_product_code"] == "", entity_id
        assert entity["commerce"]["public_offer_allowed"] is False, entity_id
        assert entity["commerce"]["cross_sell_ids"] == [], entity_id
        assert entity["commerce"]["up_sell_ids"] == [], entity_id
        model = entity["commerce"]["business_model"]
        assert model["pricing_state"] == "research_required", entity_id
        assert model["market_scope"] == "global_research", entity_id
        assert model["observation_entity_ids"] == [], entity_id
        margin = model["margin_scenario"]
        assert margin["currency"] == "", entity_id
        for key in (
            "landed_cost_low",
            "landed_cost_high",
            "retail_price_low",
            "retail_price_high",
            "gross_margin_low",
            "gross_margin_high",
        ):
            assert margin[key] is None, (entity_id, key)
        assert entity["taxonomy"]["public_category_path"] == [], entity_id
        assert entity["taxonomy"]["public_attribute_keys"] == [], entity_id
        assert entity["taxonomy"]["public_tags"] == [], entity_id
        assert entity["visual"]["asset_state"] == "rights_review_required", entity_id
        assert entity["visual"]["rights_state"] == "pending", entity_id


def test_bilingual_source_bound_claims_and_unique_visual_prompts(
    payload: dict, entities: dict[str, dict]
) -> None:
    all_sources = set(payload["registry_source_ids"])
    prompts: set[str] = set()
    for entity_id, entity in entities.items():
        for field in (
            entity["name"],
            entity["summary"],
            entity["seo"]["primary_intent"],
            entity["seo"]["primary_keyword"],
            entity["trust"]["commercial_purpose"],
        ):
            _bilingual(field)
        assert len(entity["facts"]) == 2, entity_id
        assert entity["facts"][0]["id"].endswith("-documented"), entity_id
        assert entity["facts"][1]["id"].endswith("-science-boundary"), entity_id
        assert entity["facts"][1]["dimension"] == "scientific", entity_id
        for claim in entity["facts"] + entity["relations"] + entity["compliance"]:
            value = claim["statement"] if "statement" in claim else (
                claim["note"]
            )
            _bilingual(value)
            assert claim["source_ids"], (entity_id, claim)
            assert set(claim["source_ids"]) <= all_sources, (entity_id, claim)
            assert claim["public_safe"] is False, (entity_id, claim)
        prompt = entity["visual"]["prompt_en"]
        assert prompt.strip(), entity_id
        assert prompt not in prompts, entity_id
        prompts.add(prompt)
    assert len(prompts) == 61


def test_parent_relation_targets_and_hub_children_resolve(
    payload: dict, entities: dict[str, dict]
) -> None:
    all_ids = set(payload["registry_entity_ids"])
    relation_ids: list[str] = []
    for entity_id, entity in entities.items():
        assert entity["parent_id"] in all_ids, entity_id
        part_of = [r for r in entity["relations"] if r["type"] == "part_of"]
        assert len(part_of) == 1, entity_id
        assert part_of[0]["target_id"] == entity["parent_id"], entity_id
        assert entity["seo"]["breadcrumb_entity_ids"][0] == (
            "cuisine-lebanese-regional"
        )
        assert entity["seo"]["breadcrumb_entity_ids"][-1] == entity_id
        for relation in entity["relations"]:
            assert relation["target_id"] in all_ids, (entity_id, relation)
            assert relation["target_id"] != entity_id, (entity_id, relation)
            assert relation["id"].startswith(f"edge-{entity_id}-")
            relation_ids.append(relation["id"])
    assert len(relation_ids) == len(set(relation_ids))
    for hub_id in EXPECTED_BY_TYPE["topic_hub"]:
        hub = entities[hub_id]
        assert hub["seo"]["page_role"] == "category"
        assert hub["seo"]["expected_child_ids"]
        assert set(hub["seo"]["expected_child_ids"]) <= EXPECTED_IDS


def test_every_entity_has_exactly_one_trade_gate(entities: dict[str, dict]) -> None:
    for entity_id, entity in entities.items():
        gates = [
            relation
            for relation in entity["relations"]
            if relation["target_id"] == "compliance-lebanon-trade-israel-2026"
        ]
        assert len(gates) == 1, entity_id
        assert gates[0]["type"] == "references", entity_id
        assert gates[0]["evidence_class"] == "regulatory_standard", entity_id
        assert gates[0]["source_ids"] == ["israel-enemy-states-trade-2026"]


def test_only_the_approved_five_records_fail_closed(
    entities: dict[str, dict]
) -> None:
    actual = {
        entity_id
        for entity_id, entity in entities.items()
        if any(
            note["code"] == "s9-source-fail-closed"
            for note in entity["compliance"]
        )
    }
    assert actual == HELD_IDS
    for entity_id in HELD_IDS:
        entity = entities[entity_id]
        text = json.dumps(entity, ensure_ascii=False).lower()
        assert "held" in text
        assert "fail closed" in text
        assert "no product" in text or "no dish reconstruction" in text
        assert entity["review"]["status"] == "research_draft"
        assert entity["publication"]["public_page"] is False
        assert entity["commerce"]["public_offer_allowed"] is False


def test_safety_codes_are_machine_identifiable_and_standardized(
    entities: dict[str, dict]
) -> None:
    for entity_id, entity in entities.items():
        codes = {note["code"] for note in entity["compliance"]}
        assert codes, entity_id
        assert all(re.fullmatch(r"[a-z0-9]+(?:-[a-z0-9]+)*", code) for code in codes)
        attributes = entity["taxonomy"]["attributes"]
        assert set(attributes) == {"pa_region", "pa_community"}, entity_id
        assert attributes["pa_region"], entity_id
        assert attributes["pa_community"], entity_id
    for entity_id, expected_codes in STANDARD_SAFETY_EXPECTATIONS.items():
        actual_codes = {note["code"] for note in entities[entity_id]["compliance"]}
        assert expected_codes <= actual_codes, entity_id


def test_science_entities_keep_measurement_and_health_boundaries(
    entities: dict[str, dict]
) -> None:
    molecule_text = " ".join(
        json.dumps(entities[entity_id], ensure_ascii=False).lower()
        for entity_id in EXPECTED_BY_TYPE["molecule"]
    )
    assert "geography, season and plant material" in molecule_text
    assert "no medical promise" in molecule_text
    assert "lot testing" in molecule_text

    reaction_text = " ".join(
        json.dumps(entities[entity_id], ensure_ascii=False).lower()
        for entity_id in EXPECTED_BY_TYPE["reaction"]
    )
    for term in (
        "oil-in-water emulsion",
        "maillard",
        "amylose leaching",
        "spontaneous lactic fermentation",
    ):
        assert term in reaction_text
    assert "require testing in the actual product" in reaction_text


def test_regional_and_community_scope_is_explicit(entities: dict[str, dict]) -> None:
    regions = {
        region
        for entity in entities.values()
        for region in entity["taxonomy"]["attributes"]["pa_region"]
    }
    assert {
        "lebanon-beirut",
        "lebanon-tripoli",
        "lebanon-akkar",
        "lebanon-minyara-akkar",
        "lebanon-chouf",
        "lebanon-beit-chabeb-mount-lebanon",
        "lebanon-batroun",
        "lebanon-jbeil-byblos",
    } <= regions
    family = json.dumps(
        entities["dish-marshousheh-minyara-family"], ensure_ascii=False
    ).lower()
    assert "family testimony" in family
    assert "not a formula for all akkar" in family
    tripoli = json.dumps(
        entities["hub-tripoli-sweets-breakfast-souks"], ensure_ascii=False
    ).lower()
    assert "without asserting that any named business is currently operating" in tripoli


def test_module_contains_no_em_dash_or_live_commerce_projection() -> None:
    text = COAST_MODULE.read_text(encoding="utf-8")
    assert "\u2014" not in text
    assert "public_api' => true" not in text
    assert "public_page' => true" not in text
    assert "public_offer_allowed' => true" not in text
    assert "woo_product_code' =>" not in text
