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
    / "syrian-regional-expansion-east-south.php"
)

EXPECTED_IDS = [
    "region-syria-qamishli-assyrian-foodways",
    "tradition-assyrian-akitu-qamishli",
    "ingredient-peeled-barley-dikhwa",
    "technique-dikhwa-yogurt-stabilization",
    "region-syria-al-bukamal",
    "tradition-al-bukamal-bedouin-urban-foodways",
    "technique-deir-bukamal-home-saj-tannour",
    "ingredient-deir-ez-zor-okra-context",
    "technique-deir-okra-fresh-dried-seasonality",
    "dish-mshahmiyya-deir-ez-zor",
    "preparation-muhammara-saj-deir-ez-zor",
    "dish-kileija-deir-ez-zor",
    "tradition-deir-kileija-patterning-al-qashoush",
    "ingredient-deir-ez-zor-molokhia-context",
    "dish-hannaniyya-palmyra",
    "guide-al-manzala-palmyra-identity-held",
    "ingredient-palmyra-date-palm-system",
    "technique-palmyra-burma-clay-coated-qidriya",
    "tradition-palmyra-communal-burma",
    "ingredient-kamaa-source-term-palmyra",
    "dish-kamaa-with-saj-palmyra",
    "tradition-palmyra-kamaa-seasonal-foraging",
    "dish-bulgur-chickpeas-palmyra",
    "dish-bulgur-vermicelli-palmyra",
    "tradition-palmyra-first-tooth-boiled-wheat",
    "ingredient-palmyra-white-wheat-burma-context",
    "technique-palmyra-wheat-pounding-winnowing",
    "guide-halqoum-haurani-identity-held",
    "guide-mleihi-mansaf-hauran-jordan-boundary",
    "ingredient-suwayda-purslane-context",
    "guide-syrian-qawarma-regional-forms-and-safety",
]

EXPECTED_TYPES = {
    "topic_hub": 2,
    "tradition": 6,
    "ingredient": 7,
    "technique": 5,
    "dish": 6,
    "preparation": 1,
    "guide": 4,
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def payload() -> dict:
    class_path = _php_path(SCIENCE_CLASS)
    data_path = _php_path(SCIENCE_DATA)
    module_path = _php_path(MODULE)
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
    'registry_entity_ids' => array_column($registry['entities'], 'id'),
    'registry_source_ids' => array_keys($registry['sources']),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8-sig",
        timeout=90,
    )
    assert completed.returncode == 0, completed.stderr
    return json.loads(completed.stdout)


@pytest.fixture(scope="module")
def module(payload: dict) -> dict:
    return payload["module"]


@pytest.fixture(scope="module")
def entities(module: dict) -> dict[str, dict]:
    return {entity["id"]: entity for entity in module["entities"]}


def _relation(entity: dict, relation_type: str, target_id: str) -> dict:
    matches = [
        relation
        for relation in entity["relations"]
        if relation["type"] == relation_type
        and relation["target_id"] == target_id
    ]
    assert len(matches) == 1, (entity["id"], relation_type, target_id, matches)
    return matches[0]


def _codes(entity: dict) -> set[str]:
    return {record["code"] for record in entity["compliance"]}


def test_exact_module_membership_count_order_and_types(
    module: dict, entities: dict[str, dict]
) -> None:
    assert MODULE.is_file()
    assert module["schema"] == (
        "complete99-syrian-regional-expansion-east-south-module/v1"
    )
    assert module["version"] == "culinary-science-2026.08.07.v18"
    assert module["private_entity_ids"] == EXPECTED_IDS
    assert [entity["id"] for entity in module["entities"]] == EXPECTED_IDS
    assert len(entities) == 31
    assert Counter(entity["type"] for entity in entities.values()) == EXPECTED_TYPES
    assert module["counts"] == {
        "by_type": EXPECTED_TYPES,
        "total_entities": 31,
    }


def test_every_entity_is_bilingual_private_noindex_and_reference_only(
    entities: dict[str, dict]
) -> None:
    for entity_id, entity in entities.items():
        for field in ("name", "summary"):
            assert entity[field]["he"], (entity_id, field)
            assert entity[field]["en"], (entity_id, field)
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
        business = entity["commerce"]["business_model"]
        assert business["pricing_state"] == "research_required", entity_id
        assert business["observation_entity_ids"] == [], entity_id
        margin = business["margin_scenario"]
        assert margin["currency"] == "", entity_id
        for field in (
            "landed_cost_low",
            "landed_cost_high",
            "retail_price_low",
            "retail_price_high",
            "gross_margin_low",
            "gross_margin_high",
        ):
            assert margin[field] is None, (entity_id, field)
        assert margin["basis"]["he"]
        assert margin["basis"]["en"]
        assert margin["confidence"] == "pending"
        assert margin["reviewed_at"] == ""
        taxonomy = entity["taxonomy"]
        assert taxonomy["public_category_path"] == [], entity_id
        assert taxonomy["public_attribute_keys"] == [], entity_id
        assert taxonomy["public_tags"] == [], entity_id
        assert entity["visual"]["asset_state"] == "rights_review_required"
        assert entity["visual"]["rights_state"] == "pending"
        assert entity["visual"]["rights_receipt_digest"] == ""
        assert entity["visual"]["prompt_en"]
        assert len(entity["facts"]) >= 2
        assert {fact["dimension"] for fact in entity["facts"]} >= {
            "cultural",
            "scientific",
        }
        assert all(fact["source_ids"] for fact in entity["facts"])
        assert all(fact["public_safe"] is False for fact in entity["facts"])
        assert entity["compliance"], entity_id
        assert all(note["source_ids"] for note in entity["compliance"])
        assert all(note["public_safe"] is False for note in entity["compliance"])


def test_source_records_and_all_fact_relation_links_resolve(
    payload: dict, module: dict, entities: dict[str, dict]
) -> None:
    assert set(module["sources"]) == {
        "avs-nabiha-palmyra",
        "fda-food-code-2022",
        "who-five-keys-safer-food",
        "who-natural-toxins-food",
        "who-complementary-feeding-2023",
    }
    for source_id, source in module["sources"].items():
        assert set(source) == {
            "type",
            "publisher",
            "title",
            "url",
            "published_at",
            "retrieved_at",
        }, source_id
        assert source["url"].startswith("https://"), source_id
        assert source["type"] not in {
            "official_market_listing",
            "market_observation",
        }

    known_sources = set(payload["registry_source_ids"]) | set(module["sources"])
    known_entities = set(payload["registry_entity_ids"]) | set(entities)
    for entity_id, entity in entities.items():
        assert entity["parent_id"] in known_entities, entity_id
        relation_ids = [relation["id"] for relation in entity["relations"]]
        assert len(relation_ids) == len(set(relation_ids)), entity_id
        for fact in entity["facts"]:
            assert set(fact["source_ids"]) <= known_sources, (entity_id, fact)
        for relation in entity["relations"]:
            assert relation["target_id"] in known_entities, (
                entity_id,
                relation,
            )
            assert relation["source_ids"], (entity_id, relation)
            assert set(relation["source_ids"]) <= known_sources
            assert relation["public_safe"] is False
        for note in entity["compliance"]:
            assert set(note["source_ids"]) <= known_sources, (entity_id, note)


def test_two_identity_guides_are_explicitly_held_fail_closed(
    entities: dict[str, dict]
) -> None:
    held_ids = {
        entity_id for entity_id in entities if entity_id.endswith("identity-held")
    }
    assert held_ids == {
        "guide-al-manzala-palmyra-identity-held",
        "guide-halqoum-haurani-identity-held",
    }
    for entity_id in held_ids:
        entity = entities[entity_id]
        assert entity["type"] == "guide"
        assert "identity-held-fail-closed" in _codes(entity)
        assert "fail-closed" in entity["summary"]["en"].lower()
        commercial = entity["trust"]["commercial_purpose"]["en"].lower()
        trigger = entity["trust"]["next_review_trigger"]["en"].lower()
        assert "held fail-closed" in commercial
        assert "no recipe" in commercial
        assert "second independent source" in trigger
        prompt = entity["visual"]["prompt_en"].lower()
        assert "no finished" in prompt
        assert entity["publication"]["public_page"] is False
        assert entity["commerce"]["public_offer_allowed"] is False


def test_meat_dairy_fire_wild_food_and_infant_controls_fail_closed(
    entities: dict[str, dict]
) -> None:
    meat_ids = {
        "technique-dikhwa-yogurt-stabilization",
        "dish-mshahmiyya-deir-ez-zor",
        "technique-palmyra-burma-clay-coated-qidriya",
        "tradition-palmyra-communal-burma",
        "dish-kamaa-with-saj-palmyra",
        "guide-mleihi-mansaf-hauran-jordan-boundary",
        "guide-syrian-qawarma-regional-forms-and-safety",
    }
    for entity_id in meat_ids:
        assert "meat-source-cold-chain-and-thermal-validation" in _codes(
            entities[entity_id]
        ), entity_id

    dairy_ids = {
        "technique-dikhwa-yogurt-stabilization",
        "guide-mleihi-mansaf-hauran-jordan-boundary",
    }
    for entity_id in dairy_ids:
        assert "dairy-pasteurization-allergen-and-time-temperature" in _codes(
            entities[entity_id]
        ), entity_id

    fire_ids = {
        "technique-deir-bukamal-home-saj-tannour",
        "dish-mshahmiyya-deir-ez-zor",
        "preparation-muhammara-saj-deir-ez-zor",
        "technique-palmyra-burma-clay-coated-qidriya",
    }
    for entity_id in fire_ids:
        assert "open-fire-fuel-ventilation-burn-and-thermal-control" in _codes(
            entities[entity_id]
        ), entity_id

    wild_food_ids = {
        "ingredient-kamaa-source-term-palmyra",
        "dish-kamaa-with-saj-palmyra",
        "tradition-palmyra-kamaa-seasonal-foraging",
        "ingredient-suwayda-purslane-context",
    }
    for entity_id in wild_food_ids:
        assert "wild-food-identity-and-source-control" in _codes(
            entities[entity_id]
        ), entity_id

    first_tooth = entities["tradition-palmyra-first-tooth-boiled-wheat"]
    assert "infant-feeding-choking-allergen-and-clinical-review" in _codes(
        first_tooth
    )
    assert "not feeding advice" in first_tooth["summary"]["en"].lower()


def test_high_risk_text_does_not_publish_preservation_or_foraging_steps(
    entities: dict[str, dict]
) -> None:
    qawarma = entities["guide-syrian-qawarma-regional-forms-and-safety"]
    qawarma_text = json.dumps(qawarma, ensure_ascii=False).lower()
    for required in (
        "does not establish preservation safety",
        "water activity",
        "no process details",
        "no unrefrigerated-storage claim",
    ):
        assert required in qawarma_text

    for entity_id in {
        "ingredient-kamaa-source-term-palmyra",
        "tradition-palmyra-kamaa-seasonal-foraging",
        "ingredient-suwayda-purslane-context",
    }:
        text = json.dumps(entities[entity_id], ensure_ascii=False).lower()
        assert "do not" in text
        assert "identify" in text or "identity" in text

    source = MODULE.read_text(encoding="utf-8").lower()
    for unsafe_detail in (
        "pack into jars while still hot",
        "flip them to release",
        "stayed good for over a year",
        "soak the barley in hot water for a day",
        "every two or three hours",
        "start cooking at five in the morning",
    ):
        assert unsafe_detail not in source


def test_black_spice_seed_is_not_guessed_and_origin_is_not_exclusive(
    entities: dict[str, dict]
) -> None:
    dikhwa = json.dumps(
        entities["technique-dikhwa-yogurt-stabilization"],
        ensure_ascii=False,
    ).lower()
    assert "black spice seeds remains unidentified" in dikhwa
    for guessed_identity in ("nigella", "black cumin", "black caraway"):
        assert guessed_identity not in dikhwa

    mleihi = json.dumps(
        entities["guide-mleihi-mansaf-hauran-jordan-boundary"],
        ensure_ascii=False,
    ).lower()
    assert "neither exclusive origin nor historical priority" in mleihi
    assert "do not present any version as the exclusive origin" in mleihi

    module_text = MODULE.read_text(encoding="utf-8").lower()
    for prohibited_claim in (
        "the original version",
        "originated in",
        "invented in",
        "exclusive to",
    ):
        assert prohibited_claim not in module_text


def test_visual_prompts_are_unique_specific_and_english(
    entities: dict[str, dict]
) -> None:
    prompts = [entity["visual"]["prompt_en"] for entity in entities.values()]
    assert len(prompts) == 31
    assert len(set(prompts)) == 31
    assert all(len(prompt) >= 80 for prompt in prompts)
    assert all(not any("\u0590" <= char <= "\u05ff" for char in prompt) for prompt in prompts)


def test_required_graph_edges_preserve_regional_and_identity_boundaries(
    entities: dict[str, dict]
) -> None:
    _relation(
        entities["ingredient-peeled-barley-dikhwa"],
        "used_in",
        "dish-dikhwa-qamishli-assyrian",
    )
    _relation(
        entities["technique-dikhwa-yogurt-stabilization"],
        "requires",
        "ingredient-syrian-fresh-yogurt",
    )
    home_bread = entities["technique-deir-bukamal-home-saj-tannour"]
    _relation(home_bread, "references", "technique-syrian-saj-bread")
    _relation(home_bread, "references", "technique-syrian-tannour-bread")
    _relation(
        entities["ingredient-deir-ez-zor-okra-context"],
        "used_in",
        "dish-thurud-bamiya-deir-ez-zor",
    )
    _relation(
        entities["tradition-deir-kileija-patterning-al-qashoush"],
        "references",
        "dish-kileija-deir-ez-zor",
    )
    _relation(
        entities["guide-al-manzala-palmyra-identity-held"],
        "references",
        "dish-qaren-yaruq-deir-ez-zor",
    )
    _relation(
        entities["technique-palmyra-burma-clay-coated-qidriya"],
        "used_in",
        "dish-burma-palmyra",
    )
    _relation(
        entities["ingredient-kamaa-source-term-palmyra"],
        "used_in",
        "dish-kamaa-with-saj-palmyra",
    )
    mleihi = entities["guide-mleihi-mansaf-hauran-jordan-boundary"]
    for target_id in {
        "dish-mansaf-mleihi",
        "preparation-mleihi-suwayda-fresh-yogurt",
        "preparation-mleihi-hauran-jameed",
    }:
        _relation(mleihi, "references", target_id)
    qawarma = entities["guide-syrian-qawarma-regional-forms-and-safety"]
    _relation(qawarma, "references", "technique-dermale-qawarma-jazira")
    _relation(qawarma, "references", "technique-syrian-mouneh")


def test_new_files_have_no_em_dash() -> None:
    for path in (MODULE, Path(__file__)):
        assert "\N{EM DASH}" not in path.read_text(encoding="utf-8"), path
