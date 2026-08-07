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
WEST_MODULE = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "syrian-regional-expansion-west.php"
)

EXPECTED_BY_TYPE = {
    "guide": {
        "guide-aleppo-damascus-mahshi-methods",
        "guide-kibbeh-nayyeh-idlib-hama-historical-only",
    },
    "technique": {
        "technique-aleppine-mahshi-tomato-paste-in-filling",
        "technique-damascene-mahshi-safflower-filling-tomato-sauce",
        "technique-homsi-shanklish-drying-and-fermentation",
        "technique-hamawi-batersh-smoke-and-tahini-emulsion",
        "technique-jern-pounding-idlib",
        "technique-qadmus-cooked-arum-preservation-held",
        "technique-kassab-hareesa-long-cook",
    },
    "ingredient": {
        "ingredient-syrian-safflower-damascus-context",
        "ingredient-shanklish-homs-context",
        "ingredient-qareesheh-homs-context",
        "ingredient-zawbaa-unresolved-herb-idlib",
        "ingredient-qadmus-dried-fig",
        "ingredient-loof-arum-qadmus-held",
    },
    "tradition": {
        "tradition-asrouniyeh-homs",
        "tradition-zannaneh-olive-press-idlib",
        "tradition-qadmus-eastern-new-year-pastry",
        "tradition-kassab-assumption-day-hareesa",
    },
    "dish": {
        "dish-jerneh-idlib",
        "dish-chili-dakkah-idlib",
        "dish-aqras-al-zawbaa-idlib",
        "dish-milady-pastry-qadmus",
        "dish-hareesa-kassab-syrian-armenian",
        "dish-grape-molasses-sweet-kassab",
        "dish-kaak-bi-haleeb-baniyas",
        "dish-kaak-bi-sumac-jableh",
        "dish-jazariyeh-jableh",
    },
    "topic_hub": {
        "region-syria-qadmus-mountains",
        "region-syria-kassab-armenian",
    },
}
EXPECTED_IDS = set().union(*EXPECTED_BY_TYPE.values())
EXPECTED_COUNTS = {entity_type: len(ids) for entity_type, ids in EXPECTED_BY_TYPE.items()}

EXPECTED_NEW_SOURCES = {
    "ifrepo-idlib-harem-foodways",
    "ifrepo-qadmus-foodways",
    "ifrepo-kassab-armenian-foodways",
    "arum-eurasia-review-2025",
    "pubmed-arum-palaestinum-poisoning-2020",
    "fda-acidified-low-acid-foods",
    "who-household-air-pollution-2025",
    "usda-no-wash-poultry",
}

REGIONAL_GROUPS = {
    "aleppo_damascus": {
        "guide-aleppo-damascus-mahshi-methods",
        "technique-aleppine-mahshi-tomato-paste-in-filling",
        "technique-damascene-mahshi-safflower-filling-tomato-sauce",
        "ingredient-syrian-safflower-damascus-context",
    },
    "homs": {
        "ingredient-shanklish-homs-context",
        "ingredient-qareesheh-homs-context",
        "technique-homsi-shanklish-drying-and-fermentation",
        "tradition-asrouniyeh-homs",
    },
    "hama": {"technique-hamawi-batersh-smoke-and-tahini-emulsion"},
    "idlib_harem": {
        "dish-jerneh-idlib",
        "dish-chili-dakkah-idlib",
        "dish-aqras-al-zawbaa-idlib",
        "tradition-zannaneh-olive-press-idlib",
        "ingredient-zawbaa-unresolved-herb-idlib",
        "technique-jern-pounding-idlib",
        "guide-kibbeh-nayyeh-idlib-hama-historical-only",
    },
    "qadmus": {
        "region-syria-qadmus-mountains",
        "dish-milady-pastry-qadmus",
        "ingredient-qadmus-dried-fig",
        "ingredient-loof-arum-qadmus-held",
        "technique-qadmus-cooked-arum-preservation-held",
        "tradition-qadmus-eastern-new-year-pastry",
    },
    "kassab": {
        "region-syria-kassab-armenian",
        "tradition-kassab-assumption-day-hareesa",
        "dish-hareesa-kassab-syrian-armenian",
        "dish-grape-molasses-sweet-kassab",
        "technique-kassab-hareesa-long-cook",
    },
    "baniyas_jableh": {
        "dish-kaak-bi-haleeb-baniyas",
        "dish-kaak-bi-sumac-jableh",
        "dish-jazariyeh-jableh",
    },
}

EXPECTED_PARENTS = {
    "guide-aleppo-damascus-mahshi-methods": "cuisine-syrian-regional",
    "technique-aleppine-mahshi-tomato-paste-in-filling": "region-syria-aleppo",
    "technique-damascene-mahshi-safflower-filling-tomato-sauce": "region-syria-damascus",
    "ingredient-syrian-safflower-damascus-context": "region-syria-damascus",
    "ingredient-shanklish-homs-context": "region-syria-homs",
    "ingredient-qareesheh-homs-context": "region-syria-homs",
    "technique-homsi-shanklish-drying-and-fermentation": "ingredient-shanklish-homs-context",
    "tradition-asrouniyeh-homs": "region-syria-homs",
    "technique-hamawi-batersh-smoke-and-tahini-emulsion": "dish-batersh-hama",
    "dish-jerneh-idlib": "region-syria-idlib-maarrat",
    "dish-chili-dakkah-idlib": "region-syria-idlib-maarrat",
    "dish-aqras-al-zawbaa-idlib": "region-syria-idlib-maarrat",
    "tradition-zannaneh-olive-press-idlib": "region-syria-idlib-maarrat",
    "ingredient-zawbaa-unresolved-herb-idlib": "region-syria-idlib-maarrat",
    "technique-jern-pounding-idlib": "region-syria-idlib-maarrat",
    "guide-kibbeh-nayyeh-idlib-hama-historical-only": "cuisine-syrian-regional",
    "region-syria-qadmus-mountains": "region-syria-coast",
    "dish-milady-pastry-qadmus": "region-syria-qadmus-mountains",
    "ingredient-qadmus-dried-fig": "region-syria-qadmus-mountains",
    "ingredient-loof-arum-qadmus-held": "region-syria-qadmus-mountains",
    "technique-qadmus-cooked-arum-preservation-held": "ingredient-loof-arum-qadmus-held",
    "tradition-qadmus-eastern-new-year-pastry": "region-syria-qadmus-mountains",
    "region-syria-kassab-armenian": "region-syria-coast",
    "tradition-kassab-assumption-day-hareesa": "region-syria-kassab-armenian",
    "dish-hareesa-kassab-syrian-armenian": "region-syria-kassab-armenian",
    "dish-grape-molasses-sweet-kassab": "region-syria-kassab-armenian",
    "technique-kassab-hareesa-long-cook": "dish-hareesa-kassab-syrian-armenian",
    "dish-kaak-bi-haleeb-baniyas": "region-syria-baniyas",
    "dish-kaak-bi-sumac-jableh": "region-syria-jableh",
    "dish-jazariyeh-jableh": "region-syria-jableh",
}

HELD_IDS = {
    "ingredient-loof-arum-qadmus-held",
    "technique-qadmus-cooked-arum-preservation-held",
}
UNRESOLVED_COAST_IDS = {
    "dish-kaak-bi-haleeb-baniyas",
    "dish-kaak-bi-sumac-jableh",
    "dish-jazariyeh-jableh",
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def payload() -> dict:
    class_path = _php_path(SCIENCE_CLASS)
    data_path = _php_path(SCIENCE_DATA)
    module_path = _php_path(WEST_MODULE)
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
$module = require '{module_path}';
echo json_encode(array(
    'module' => $module,
    'existing_entity_ids' => array_values(array_diff(
        array_column($registry['entities'], 'id'),
        $module['private_entity_ids']
    )),
    'existing_source_ids' => array_values(array_diff(
        array_keys($registry['sources']),
        array_keys($module['sources'])
    )),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
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


def test_module_contract_uses_sources_key_and_target_version(module: dict) -> None:
    assert set(module) == {
        "schema",
        "version",
        "sources",
        "entities",
        "private_entity_ids",
        "counts",
    }
    assert "source_records" not in module
    assert module["schema"] == "complete99-syrian-regional-expansion-west/v1"
    assert module["version"] == "culinary-science-2026.08.07.v16"


def test_inventory_and_type_distribution_are_exact(module: dict, entities: dict) -> None:
    assert set(entities) == EXPECTED_IDS
    assert len(entities) == 30
    assert set(module["private_entity_ids"]) == EXPECTED_IDS
    assert len(module["private_entity_ids"]) == 30
    actual = Counter(entity["type"] for entity in entities.values())
    assert dict(actual) == EXPECTED_COUNTS
    assert module["counts"] == {"by_type": EXPECTED_COUNTS, "total_entities": 30}
    assert {group: len(ids) for group, ids in REGIONAL_GROUPS.items()} == {
        "aleppo_damascus": 4,
        "homs": 4,
        "hama": 1,
        "idlib_harem": 7,
        "qadmus": 6,
        "kassab": 5,
        "baniyas_jableh": 3,
    }
    assert set().union(*REGIONAL_GROUPS.values()) == EXPECTED_IDS


def test_every_entity_is_private_noindex_and_reference_only(entities: dict) -> None:
    for entity in entities.values():
        assert entity["surface_class"] == "editorial_draft"
        assert entity["index_policy"] == "noindex_private"
        assert entity["publication"]["state"] == "private_preview"
        assert entity["publication"]["public_api"] is False
        assert entity["publication"]["public_page"] is False
        assert entity["publication"]["search_index"] is False
        assert entity["seo"]["route_mode"] == "private"
        assert entity["seo"]["intent_classes"] == ["informational"]
        assert entity["commerce"]["state"] == "reference_only"
        assert entity["commerce"]["woo_product_code"] == ""
        assert entity["commerce"]["public_offer_allowed"] is False
        assert entity["commerce"]["cross_sell_ids"] == []
        assert entity["commerce"]["up_sell_ids"] == []
        model = entity["commerce"]["business_model"]
        assert model["revenue_models"] == ["education"]
        assert model["customer_segments"] == [
            "culinary_consumers",
            "research_readers",
        ]
        assert model["pricing_state"] == "research_required"
        assert model["market_scope"] == "global_research"
        assert model["observation_entity_ids"] == []
        margin = model["margin_scenario"]
        assert margin["currency"] == ""
        assert margin["reviewed_at"] == ""
        assert margin["confidence"] == "pending"
        for key in (
            "landed_cost_low",
            "landed_cost_high",
            "retail_price_low",
            "retail_price_high",
            "gross_margin_low",
            "gross_margin_high",
        ):
            assert margin[key] is None
        assert entity["taxonomy"]["public_category_path"] == []
        assert entity["taxonomy"]["public_attribute_keys"] == []
        assert entity["taxonomy"]["public_tags"] == []


def test_bilingual_content_sources_and_science_boundaries_are_complete(
    entities: dict,
) -> None:
    for entity in entities.values():
        for value in (
            entity["name"],
            entity["summary"],
            entity["seo"]["primary_intent"],
            entity["seo"]["primary_keyword"],
            entity["commerce"]["product_copy"],
            entity["commerce"]["business_model"]["value_proposition"],
            entity["trust"]["commercial_purpose"],
        ):
            _bilingual(value)
        assert len(entity["facts"]) == 2
        assert entity["facts"][0]["id"].endswith("-documented")
        assert entity["facts"][1]["id"].endswith("-science-risk-boundary")
        assert entity["facts"][1]["dimension"] == "scientific"
        for fact in entity["facts"]:
            _bilingual(fact["statement"])
            assert fact["source_ids"]
            assert fact["public_safe"] is False
        assert entity["relations"]
        for relation in entity["relations"]:
            _bilingual(relation["note"])
            assert relation["source_ids"]
            assert relation["public_safe"] is False
        assert entity["compliance"]
        for note in entity["compliance"]:
            _bilingual(note["note"])
            assert note["source_ids"]
            assert note["public_safe"] is False


def test_sources_are_new_https_records_and_all_claims_resolve(
    payload: dict, module: dict, entities: dict
) -> None:
    assert set(module["sources"]) == EXPECTED_NEW_SOURCES
    assert EXPECTED_NEW_SOURCES.isdisjoint(payload["existing_source_ids"])
    all_source_ids = set(payload["existing_source_ids"]) | EXPECTED_NEW_SOURCES
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
    for entity in entities.values():
        for claim in entity["facts"] + entity["relations"] + entity["compliance"]:
            assert set(claim["source_ids"]) <= all_source_ids


def test_parent_and_relation_graph_has_no_missing_target(
    payload: dict, entities: dict
) -> None:
    assert {entity_id: entity["parent_id"] for entity_id, entity in entities.items()} == (
        EXPECTED_PARENTS
    )
    all_ids = set(payload["existing_entity_ids"]) | EXPECTED_IDS
    relation_ids: list[str] = []
    for entity in entities.values():
        assert entity["parent_id"] in all_ids
        part_of = [r for r in entity["relations"] if r["type"] == "part_of"]
        assert len(part_of) == 1
        assert part_of[0]["target_id"] == entity["parent_id"]
        for relation in entity["relations"]:
            assert relation["target_id"] in all_ids
            assert relation["target_id"] != entity["id"]
            assert relation["id"].startswith(f"edge-{entity['id']}-")
            relation_ids.append(relation["id"])
    assert len(relation_ids) == len(set(relation_ids))


def test_family_testimony_stays_one_testimony_not_a_regional_rule(
    entities: dict,
) -> None:
    family_ids = {
        "guide-aleppo-damascus-mahshi-methods",
        "technique-aleppine-mahshi-tomato-paste-in-filling",
        "technique-damascene-mahshi-safflower-filling-tomato-sauce",
        "tradition-asrouniyeh-homs",
    }
    combined = " ".join(
        json.dumps(entities[entity_id], ensure_ascii=False).lower()
        for entity_id in family_ids
    )
    assert "one family" in combined
    assert "not a binding rule" in combined or "not a uniform practice" in combined
    assert "every aleppan or damascene household" in combined
    assert "universal aleppine formula" in combined

    for entity_id in {
        "guide-aleppo-damascus-mahshi-methods",
        "technique-aleppine-mahshi-tomato-paste-in-filling",
        "technique-damascene-mahshi-safflower-filling-tomato-sauce",
        "ingredient-syrian-safflower-damascus-context",
    }:
        assert entities[entity_id]["taxonomy"]["attributes"]["pa_community"] == [
            "razan-family-testimony"
        ]

    for entity_id in {
        "ingredient-shanklish-homs-context",
        "ingredient-qareesheh-homs-context",
        "technique-homsi-shanklish-drying-and-fermentation",
        "tradition-asrouniyeh-homs",
    }:
        assert entities[entity_id]["taxonomy"]["attributes"]["pa_community"] == [
            "nariman-homsi-family-testimony"
        ]


def test_zawbaa_identity_remains_botanically_unresolved(entities: dict) -> None:
    entity = entities["ingredient-zawbaa-unresolved-herb-idlib"]
    assert entity["id"] == "ingredient-zawbaa-unresolved-herb-idlib"
    assert entity["slug"] == "zawbaa-unresolved-herb-idlib"
    assert "thyme" not in entity["id"]
    assert "thyme" not in entity["slug"]
    text = json.dumps(entity, ensure_ascii=False).lower()
    assert "botanical identification" in text
    assert "do not sell, substitute or recommend consumption" in text


def test_food_hazards_have_machine_identifiable_compliance_codes(
    entities: dict,
) -> None:
    expected_codes = {
        "ingredient-shanklish-homs-context": {
            "dairy-fermentation-water-activity-and-cold-chain-validation",
        },
        "ingredient-qareesheh-homs-context": {
            "dairy-pasteurization-allergen-and-cold-chain-validation",
        },
        "technique-homsi-shanklish-drying-and-fermentation": {
            "dairy-fermentation-process-and-shelf-life-validation",
        },
        "technique-hamawi-batersh-smoke-and-tahini-emulsion": {
            "sesame-allergen-flame-smoke-and-ventilation-validation",
        },
        "dish-chili-dakkah-idlib": {
            "walnut-allergen-product-and-cross-contact-validation",
        },
        "dish-milady-pastry-qadmus": {
            "gluten-dairy-sesame-allergen-and-baking-validation",
        },
        "dish-hareesa-kassab-syrian-armenian": {
            "poultry-separation-no-rinsing",
            "red-meat-source-cold-chain-and-thermal-validation",
            "wheat-gluten-and-clarified-fat-allergen-validation",
        },
        "dish-grape-molasses-sweet-kassab": {
            "flour-gluten-allergen-and-product-validation",
        },
        "technique-kassab-hareesa-long-cook": {
            "poultry-long-cook-validation",
            "red-meat-long-cook-and-bone-removal-validation",
        },
    }

    for entity_id, required_codes in expected_codes.items():
        controls = {rule["code"]: rule for rule in entities[entity_id]["compliance"]}
        assert required_codes <= set(controls), entity_id
        for code in required_codes:
            assert controls[code]["public_safe"] is False, (entity_id, code)
            assert controls[code]["source_ids"], (entity_id, code)


def test_loof_entities_are_the_only_s9_held_fail_closed_records(
    entities: dict,
) -> None:
    actual_held = {
        entity_id
        for entity_id, entity in entities.items()
        if any(note["code"] == "s9-arum-fail-closed" for note in entity["compliance"])
    }
    assert actual_held == HELD_IDS
    for entity_id in HELD_IDS:
        entity = entities[entity_id]
        text = json.dumps(entity, ensure_ascii=False).lower()
        assert "held" in text
        assert "fail closed" in text or "fail-closed" in text
        assert "botanical" in text
        assert "validated" in text
        assert entity["review"]["status"] == "research_draft"
        assert entity["publication"]["public_page"] is False
        assert entity["commerce"]["public_offer_allowed"] is False
        assert not re.search(r"\b(?:minutes?|hours?|grams?|ml|°c)\b", text)


def test_raw_kibbeh_is_historical_only_and_contains_no_recipe(
    entities: dict,
) -> None:
    entity = entities["guide-kibbeh-nayyeh-idlib-hama-historical-only"]
    text = json.dumps(entity, ensure_ascii=False).lower()
    assert "historical" in text
    assert "cdc-raw-kibbeh-salmonella-2013" in text
    assert "contains no ingredients or instructions" in text
    assert "not a formula, process or eating recommendation" in text
    assert "do not publish ingredients" in text
    assert entity["seo"]["schema_type"] == "Article"


def test_no_medical_claim_and_poultry_safety_boundary_is_clean(
    entities: dict,
) -> None:
    text = json.dumps(list(entities.values()), ensure_ascii=False).lower()
    assert "covid" not in text
    assert "immunity" not in text
    assert "immune system" not in text
    assert "cures" not in text
    assert "treats disease" not in text
    kassab_ids = REGIONAL_GROUPS["kassab"]
    kassab_text = " ".join(
        json.dumps(entities[entity_id], ensure_ascii=False).lower()
        for entity_id in kassab_ids
    )
    assert "do not rinse raw poultry" in kassab_text
    assert "prevent cross-contamination" in kassab_text
    assert "usda-no-wash-poultry" in kassab_text
    assert "wash the chicken" not in kassab_text
    assert "wash poultry before cooking" not in kassab_text


def test_visual_prompts_are_unique_and_unresolved_dishes_are_not_reconstructed(
    entities: dict,
) -> None:
    prompts = [entity["visual"]["prompt_en"] for entity in entities.values()]
    assert len(prompts) == 30
    assert len(set(prompts)) == 30
    for prompt in prompts:
        assert len(prompt) >= 80
        assert re.search(r"[A-Za-z]", prompt)
    for entity_id in UNRESOLVED_COAST_IDS:
        prompt = entities[entity_id]["visual"]["prompt_en"].lower()
        assert "empty" in prompt
        assert "no reconstructed" in prompt or "no food reconstruction" in prompt
        assert "no ingredient" in prompt


def test_module_and_contract_test_have_no_em_dash() -> None:
    for path in (WEST_MODULE, Path(__file__)):
        assert "\N{EM DASH}" not in path.read_text(encoding="utf-8"), path
