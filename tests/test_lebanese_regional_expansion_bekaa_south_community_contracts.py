from __future__ import annotations

import json
import subprocess
from collections import Counter
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"
MODULE = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "lebanese-regional-expansion-bekaa-south-community.php"
)

EXPECTED_BY_TYPE = {
    "topic_hub": {
        "region-lebanon-zahle-central-bekaa",
        "region-lebanon-baalbek-northern-bekaa",
        "region-lebanon-hermel-aarsal",
        "region-lebanon-west-bekaa-rashaya",
        "region-lebanon-nabatieh-jabal-amel",
        "region-lebanon-jezzine-sidon-tyre",
        "hub-lebanese-ritual-foodways-comparative",
    },
    "dish": {
        "dish-kebbit-el-arous-baalbek-held",
        "dish-yakhne-helwe-aarsal",
        "dish-mafroukeh-b-laban-dar-el-wasaa",
        "dish-saff-rashaya",
        "dish-pumpkin-jam-west-bekaa-held",
        "dish-kaak-kherbet-qanafar-west-bekaa",
        "dish-akras-mashghara-west-bekaa",
        "dish-meshtah-el-jreesh-south-lebanon",
        "dish-potato-fennel-fritters-zawtar",
        "dish-tomato-kammouneh-south-lebanon",
        "dish-frakeh-south-lebanon-held",
        "dish-mjaddaret-fassolia-shouf-south",
        "dish-armenian-vegan-chard-kibbeh-lebanon",
        "dish-khebbeyze-hommous-wild-mallow-lebanon",
        "dish-poha-musakhan-halima-al-afifi-record",
        "dish-poha-maqluba-nawal-mustafa-record",
        "dish-poha-maamoul-malak-abu-shheir-record",
        "dish-poha-falafel-ghaliya-kaddoura-record",
        "dish-poha-kubbeh-labaniyye-zahriyah-record",
    },
    "ingredient": {
        "ingredient-bekaa-grape-context",
        "ingredient-zahle-arak-context",
        "ingredient-aarsal-chickpea-context",
        "ingredient-west-bekaa-pumpkin-context",
        "ingredient-green-kammouneh-south-lebanon",
        "ingredient-jreesh-south-lebanon-context",
        "ingredient-trout-anjar-hermel-context",
    },
    "technique": {
        "technique-zahle-arak-distillation-held",
        "technique-west-bekaa-pumpkin-lime-firming-held",
        "technique-baalbek-kebbeh-forming-raw-held",
        "technique-meshtah-jreesh-soaking-baking",
        "technique-green-kammouneh-grinding-south-lebanon",
        "technique-kherbet-qanafar-wood-fired-kaak-baking",
    },
    "tradition": {
        "tradition-zahle-grape-wine-arak-culture",
        "tradition-zahle-vine-festival-context",
        "tradition-baalbek-brides-kebbeh-henna",
        "tradition-aarsal-wedding-meal-triad",
        "tradition-west-bekaa-holiday-kaak-family",
        "tradition-mashghara-winter-fitr-adha-akras",
        "tradition-south-lebanon-meshtah-ramadan",
        "tradition-bourj-hammoud-armenian-lebanese-food-market-2015",
        "tradition-lebanon-hrisseh-shia-christian-comparative",
        "tradition-central-bekaa-melkite-family-feast",
        "tradition-hsoun-maronite-shia-feast-coexistence",
    },
    "guide": {"guide-lebanese-shared-levantine-identity-boundaries"},
    "culinary_institution": {
        "institution-zahle-unesco-creative-city-gastronomy",
        "institution-zahle-gastronomy-association",
        "institution-nusroto-zahle-community-kitchen",
        "institution-khayrat-bekaena-coop",
        "institution-arcenciel-taanayel-food-social-enterprise",
        "institution-aub-healthy-kitchen-project",
        "institution-darb-el-karam-west-bekaa-food-trail",
    },
    "market": {"market-souk-aal-souk-bourj-hammoud-2015"},
    "restaurant": {"restaurant-khan-al-makssoud-taanayel"},
}
EXPECTED_IDS = set().union(*EXPECTED_BY_TYPE.values())
HELD_IDS = {
    "dish-kebbit-el-arous-baalbek-held",
    "dish-pumpkin-jam-west-bekaa-held",
    "dish-frakeh-south-lebanon-held",
    "ingredient-zahle-arak-context",
    "technique-zahle-arak-distillation-held",
    "technique-west-bekaa-pumpkin-lime-firming-held",
    "technique-baalbek-kebbeh-forming-raw-held",
}
STANDARD_SAFETY_CODES = {
    "alcohol-age-gate",
    "allergen-gluten",
    "allergen-sesame",
    "allergen-nuts",
    "raw-meat-food-safety",
    "traditional-dairy-food-safety",
    "food-grade-calcium-oxide",
    "distillation-fire-safety",
    "open-fire-safety",
    "wild-plant-identification",
    "cold-chain-control",
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
    'registry' => $registry,
    'module' => $c99_lebanese_bekaa_south_community_module,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8-sig",
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


def test_exact_version_membership_types_sources_and_holds(
    module: dict, entities: dict[str, dict]
) -> None:
    assert MODULE.is_file()
    assert module["schema"] == (
        "complete99-lebanese-regional-expansion-bekaa-south-community/v1"
    )
    assert module["version"] == "culinary-science-2026.08.07.v17"
    assert len(module["sources"]) == 46
    assert set(entities) == EXPECTED_IDS
    assert set(module["private_entity_ids"]) == EXPECTED_IDS
    assert set(module["held_entity_ids"]) == HELD_IDS
    assert len(entities) == 60
    assert Counter(entity["type"] for entity in entities.values()) == {
        entity_type: len(entity_ids)
        for entity_type, entity_ids in EXPECTED_BY_TYPE.items()
    }
    assert module["counts"] == {
        "by_type": {
            entity_type: len(entity_ids)
            for entity_type, entity_ids in EXPECTED_BY_TYPE.items()
        },
        "total_entities": 60,
    }
    assert "dish-mansoufeh-chouf-west-bekaa" not in entities
    assert "technique-mansoufeh-shape-poach-finish" not in entities
    assert "dish-khebbeyze-hommous-wild-mallow-lebanon" in entities
    assert "technique-kherbet-qanafar-wood-fired-kaak-baking" in entities


def test_every_entity_is_private_reference_only_trade_gated_and_unpriced(
    payload: dict, entities: dict[str, dict]
) -> None:
    registry_ids = {
        entity["id"] for entity in payload["registry"]["entities"]
    }
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
        assert entity["commerce"]["state"] == "reference_only", entity_id
        assert entity["commerce"]["woo_product_code"] == "", entity_id
        assert entity["commerce"]["public_offer_allowed"] is False, entity_id
        assert entity["commerce"]["cross_sell_ids"] == [], entity_id
        assert entity["commerce"]["up_sell_ids"] == [], entity_id
        model = entity["commerce"]["business_model"]
        assert model["pricing_state"] == "research_required", entity_id
        assert model["observation_entity_ids"] == [], entity_id
        margin = model["margin_scenario"]
        for key in {
            "landed_cost_low",
            "landed_cost_high",
            "retail_price_low",
            "retail_price_high",
            "gross_margin_low",
            "gross_margin_high",
        }:
            assert margin[key] is None, (entity_id, key)
        assert entity["parent_id"] in registry_ids, entity_id
        trade = [
            relation
            for relation in entity["relations"]
            if relation["target_id"]
            == "compliance-lebanon-trade-israel-2026"
        ]
        assert len(trade) == 1, entity_id
        assert trade[0]["type"] == "references", entity_id
        assert trade[0]["source_ids"] == [
            "israel-enemy-states-trade-2026"
        ], entity_id


def test_all_facts_relations_and_compliance_are_source_bound_and_resolve(
    payload: dict, module: dict, entities: dict[str, dict]
) -> None:
    registry_ids = {
        entity["id"] for entity in payload["registry"]["entities"]
    }
    source_ids = set(payload["registry"]["sources"])
    used_new_sources: set[str] = set()
    fact_ids: list[str] = []
    relation_ids: list[str] = []
    for entity_id, entity in entities.items():
        assert entity["facts"], entity_id
        assert entity["relations"], entity_id
        assert entity["compliance"], entity_id
        for fact in entity["facts"]:
            fact_ids.append(fact["id"])
            assert fact["source_ids"], entity_id
            assert set(fact["source_ids"]) <= source_ids, entity_id
            assert fact["public_safe"] is False, entity_id
            used_new_sources.update(
                set(fact["source_ids"]) & set(module["sources"])
            )
        for relation in entity["relations"]:
            relation_ids.append(relation["id"])
            assert relation["target_id"] in registry_ids, (
                entity_id,
                relation["target_id"],
            )
            assert relation["target_id"] != entity_id, entity_id
            assert relation["source_ids"], entity_id
            assert set(relation["source_ids"]) <= source_ids, entity_id
            assert relation["public_safe"] is False, entity_id
            used_new_sources.update(
                set(relation["source_ids"]) & set(module["sources"])
            )
        for note in entity["compliance"]:
            assert note["source_ids"], entity_id
            assert set(note["source_ids"]) <= source_ids, entity_id
            assert note["public_safe"] is False, entity_id
            used_new_sources.update(
                set(note["source_ids"]) & set(module["sources"])
            )
    assert len(fact_ids) == len(set(fact_ids))
    assert len(relation_ids) == len(set(relation_ids))
    assert used_new_sources == set(module["sources"])


def test_exact_held_detection_and_standard_machine_safety_codes(
    entities: dict[str, dict]
) -> None:
    observed_held: set[str] = set()
    safety_codes: set[str] = set()
    for entity_id, entity in entities.items():
        safety_codes.update(note["code"] for note in entity["compliance"])
        searchable = " ".join(
            [
                entity["summary"]["en"],
                entity["trust"]["commercial_purpose"]["en"],
                entity["trust"]["next_review_trigger"]["en"],
                *(note["note"]["en"] for note in entity["compliance"]),
            ]
        ).lower()
        if "held" in searchable or "fail-closed" in searchable:
            observed_held.add(entity_id)
    assert observed_held == HELD_IDS
    assert STANDARD_SAFETY_CODES <= safety_codes
    for entity_id in HELD_IDS:
        assert entities[entity_id]["review"]["status"] == "research_draft"
        assert entities[entity_id]["publication"]["public_page"] is False
        assert entities[entity_id]["commerce"]["public_offer_allowed"] is False


def test_bilingual_content_unique_prompts_identity_boundaries_and_no_em_dash(
    entities: dict[str, dict]
) -> None:
    prompts: set[str] = set()
    for entity_id, entity in entities.items():
        for field in ("name", "summary"):
            assert entity[field]["he"].strip(), (entity_id, field, "he")
            assert entity[field]["en"].strip(), (entity_id, field, "en")
        assert entity["seo"]["primary_intent"]["he"].strip(), entity_id
        assert entity["seo"]["primary_intent"]["en"].strip(), entity_id
        prompt = entity["visual"]["prompt_en"]
        assert prompt.startswith("Original "), entity_id
        assert prompt not in prompts, entity_id
        prompts.add(prompt)
        assert entity["visual"]["asset_state"] == "rights_review_required"
        assert entity["visual"]["rights_method"] == (
            "generated_concept_with_human_review"
        )
        assert entity["visual"]["rights_state"] == "pending"
    assert len(prompts) == 60

    guide = json.dumps(
        entities["guide-lebanese-shared-levantine-identity-boundaries"],
        ensure_ascii=False,
    ).lower()
    assert "shared levantine" in guide
    assert "origin verdicts without evidence" in guide
    palestinian = json.dumps(
        entities["dish-poha-musakhan-halima-al-afifi-record"],
        ensure_ascii=False,
    ).lower()
    assert "named palestinian archive record" in palestinian
    assert "not a lebanese-origin claim" in palestinian
    assert "\u2014" not in MODULE.read_text(encoding="utf-8")


def test_source_registry_is_exact_allowed_and_url_unique(module: dict) -> None:
    allowed_types = {
        "official_government",
        "official_organization",
        "peer_reviewed_paper",
        "conference_proceeding",
        "official_standard",
        "official_business",
        "official_market_listing",
        "regulatory_guidance",
    }
    assert len(module["sources"]) == 46
    urls = [source["url"] for source in module["sources"].values()]
    assert len(set(urls)) == 46
    assert all(source["type"] in allowed_types for source in module["sources"].values())
    assert all(source["publisher"].strip() for source in module["sources"].values())
    assert all(source["title"].strip() for source in module["sources"].values())
    assert all(source["retrieved_at"] == "2026-08-07" for source in module["sources"].values())
