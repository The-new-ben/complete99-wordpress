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
    / "syrian-community-institutions-expansion.php"
)

EXPECTED_BY_TYPE = {
    "culinary_institution": {
        "institution-agricultural-voices-syria",
        "institution-ifpo-syria-recipes-cultures",
        "institution-syrian-academy-of-gastronomy",
        "institution-smithsonian-syrian-armenian-foodways",
        "institution-jewish-food-society-syrian-family-archive",
        "institution-foodish-anu-syrian-community-archive",
        "institution-asif-syrian-jewish-recipe-archive",
        "institution-national-library-israel-syrian-jewish-context-archive",
        "institution-library-of-congress-foodways-web-archive",
    },
    "market": {"market-al-midan-damascus-sweets-corridor"},
    "restaurant": {
        "restaurant-imads-syrian-kitchen-london",
        "restaurant-le-petit-alep-montreal",
        "restaurant-abu-hagop-yerevan",
        "restaurant-old-ashtarak-syrian-armenian",
    },
    "dish": {
        "dish-passover-kibbeh-damascene-jewish",
        "dish-ejjeh-syrian-jewish-family",
        "dish-heitaliyeh-aleppan-jewish-panama",
        "dish-chicken-mehshi-sfeeha-aleppan-family",
        "dish-macaroni-chicken-aleppan-diaspora",
        "dish-doshka-syrian-armenian-family",
    },
    "ingredient": {"ingredient-aleppan-jewish-string-cheese-family"},
    "tradition": {
        "tradition-syrian-armenian-food-enterprise-diaspora",
        "tradition-afrin-kurdish-olive-oil-memory-diaspora",
    },
    "guide": {"guide-assyrian-qamishli-cross-border-foodways-boundary"},
    "technique": {"technique-suwayda-qahwa-murra-hospitality-service"},
}
EXPECTED_IDS = set().union(*EXPECTED_BY_TYPE.values())

JEWISH_FAMILY_IDS = {
    "dish-passover-kibbeh-damascene-jewish",
    "dish-ejjeh-syrian-jewish-family",
    "dish-heitaliyeh-aleppan-jewish-panama",
    "ingredient-aleppan-jewish-string-cheese-family",
    "dish-chicken-mehshi-sfeeha-aleppan-family",
    "dish-macaroni-chicken-aleppan-diaspora",
}

ARCHIVE_IDS = {
    "institution-agricultural-voices-syria",
    "institution-ifpo-syria-recipes-cultures",
    "institution-smithsonian-syrian-armenian-foodways",
    "institution-jewish-food-society-syrian-family-archive",
    "institution-foodish-anu-syrian-community-archive",
    "institution-asif-syrian-jewish-recipe-archive",
    "institution-national-library-israel-syrian-jewish-context-archive",
    "institution-library-of-congress-foodways-web-archive",
}

RESTAURANT_STATUS_TAGS = {
    "restaurant-imads-syrian-kitchen-london": (
        "operational-source-verified-2026-08-07"
    ),
    "restaurant-le-petit-alep-montreal": (
        "operational-source-verified-2026-08-07"
    ),
    "restaurant-abu-hagop-yerevan": "directory-source-verified-2026-08-07",
    "restaurant-old-ashtarak-syrian-armenian": "operational-status-unverified",
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
    'base_entity_ids' => array_values(array_diff(
        array_column($registry['entities'], 'id'),
        $module['private_entity_ids']
    )),
    'base_source_ids' => array_values(array_diff(
        array_keys($registry['sources']),
        array_keys($module['sources'])
    )),
    'module' => $module,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
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


def _text(entity: dict) -> str:
    parts = [
        entity["name"]["he"],
        entity["name"]["en"],
        entity["summary"]["he"],
        entity["summary"]["en"],
    ]
    for fact in entity["facts"]:
        parts.extend(fact["statement"].values())
    for rule in entity["compliance"]:
        parts.extend(rule["note"].values())
    return " ".join(parts).lower()


def _relation_targets(entity: dict) -> set[str]:
    return {relation["target_id"] for relation in entity["relations"]}


def test_module_has_exact_approved_membership_and_standard_return_shape(
    module: dict, entities: dict[str, dict]
) -> None:
    assert MODULE.is_file()
    assert module["schema"] == (
        "complete99-syrian-community-institutions-expansion/v1"
    )
    assert module["version"] == "culinary-science-2026.08.08.v20"
    assert "sources" in module
    assert "source_records" not in module
    assert set(entities) == EXPECTED_IDS
    assert set(module["private_entity_ids"]) == EXPECTED_IDS
    assert len(entities) == 25
    assert len(module["sources"]) == 18
    assert Counter(entity["type"] for entity in entities.values()) == {
        entity_type: len(entity_ids)
        for entity_type, entity_ids in EXPECTED_BY_TYPE.items()
    }
    assert module["counts"] == {
        "by_type": {
            entity_type: len(entity_ids)
            for entity_type, entity_ids in EXPECTED_BY_TYPE.items()
        },
        "total_entities": 25,
    }
    assert "tradition-assyrian-qamishli-akitu-dikhwa" not in entities


def test_every_entity_is_private_noindex_reference_only_and_noncommercial(
    entities: dict[str, dict]
) -> None:
    forbidden_relation_types = {
        "sold_by",
        "sourced_from",
        "observed_at",
        "produced_by",
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
        assert entity["commerce"]["business_model"]["pricing_state"] == (
            "research_required"
        ), entity_id
        assert entity["commerce"]["business_model"][
            "observation_entity_ids"
        ] == [], entity_id
        margin = entity["commerce"]["business_model"]["margin_scenario"]
        for key in {
            "landed_cost_low",
            "landed_cost_high",
            "retail_price_low",
            "retail_price_high",
            "gross_margin_low",
            "gross_margin_high",
        }:
            assert margin[key] is None, (entity_id, key)
        assert not (
            forbidden_relation_types
            & {relation["type"] for relation in entity["relations"]}
        ), entity_id
        assert set(entity["taxonomy"]["attributes"]) == {
            "pa_region",
            "pa_community",
        }, entity_id
        assert all(fact["public_safe"] is False for fact in entity["facts"])
        assert all(rule["public_safe"] is False for rule in entity["compliance"])


def test_bilingual_content_visuals_and_rights_are_complete_and_unique(
    entities: dict[str, dict]
) -> None:
    prompts: set[str] = set()
    for entity_id, entity in entities.items():
        for field in ("name", "summary"):
            assert entity[field]["he"].strip(), (entity_id, field, "he")
            assert entity[field]["en"].strip(), (entity_id, field, "en")
        for field in ("primary_intent", "primary_keyword"):
            assert entity["seo"][field]["he"].strip(), (entity_id, field, "he")
            assert entity["seo"][field]["en"].strip(), (entity_id, field, "en")
        for fact in entity["facts"]:
            assert fact["statement"]["he"].strip(), entity_id
            assert fact["statement"]["en"].strip(), entity_id
            assert fact["source_ids"], entity_id
        prompt = entity["visual"]["prompt_en"]
        assert prompt.startswith("Original "), entity_id
        assert prompt not in prompts, entity_id
        prompts.add(prompt)
        assert entity["visual"]["asset_state"] == "rights_review_required"
        assert entity["visual"]["rights_method"] == (
            "generated_concept_with_human_review"
        )
        assert entity["visual"]["rights_state"] == "pending"
        assert "no copied archive material" in entity["visual"][
            "negative_prompt_en"
        ].lower()
    assert len(prompts) == 25
    assert "\u2014" not in MODULE.read_text(encoding="utf-8")


def test_all_ids_sources_parents_and_relations_resolve_without_collisions(
    payload: dict, module: dict, entities: dict[str, dict]
) -> None:
    base_entity_ids = set(payload["base_entity_ids"])
    base_source_ids = set(payload["base_source_ids"])
    module_source_ids = set(module["sources"])
    all_entity_ids = base_entity_ids | set(entities)
    all_source_ids = base_source_ids | module_source_ids

    assert not (base_entity_ids & set(entities))
    assert not (base_source_ids & module_source_ids)
    assert len({entity["slug"] for entity in entities.values()}) == 25

    fact_ids: list[str] = []
    relation_ids: list[str] = []
    for entity_id, entity in entities.items():
        assert entity["parent_id"] in all_entity_ids, entity_id
        for fact in entity["facts"]:
            fact_ids.append(fact["id"])
            assert set(fact["source_ids"]) <= all_source_ids, entity_id
        for relation in entity["relations"]:
            relation_ids.append(relation["id"])
            assert relation["target_id"] in all_entity_ids, (
                entity_id,
                relation["target_id"],
            )
            assert set(relation["source_ids"]) <= all_source_ids, entity_id
        for rule in entity["compliance"]:
            assert set(rule["source_ids"]) <= all_source_ids, entity_id

    assert len(fact_ids) == len(set(fact_ids))
    assert len(relation_ids) == len(set(relation_ids))


def test_aleppan_damascene_and_family_archive_boundaries_are_explicit(
    entities: dict[str, dict]
) -> None:
    assert set(entities) >= JEWISH_FAMILY_IDS
    assert entities["dish-passover-kibbeh-damascene-jewish"]["parent_id"] == (
        "tradition-damascene-jewish-holiday-foodways"
    )
    assert entities["dish-passover-kibbeh-damascene-jewish"]["taxonomy"][
        "attributes"
    ]["pa_community"] == ["damascene-jewish-family"]

    for entity_id in {
        "ingredient-aleppan-jewish-string-cheese-family",
        "dish-chicken-mehshi-sfeeha-aleppan-family",
    }:
        assert entities[entity_id]["parent_id"] == (
            "tradition-aleppan-jewish-foodways"
        )
        assert "damascene" not in entities[entity_id]["taxonomy"]["attributes"][
            "pa_community"
        ][0]

    for entity_id in {
        "dish-ejjeh-syrian-jewish-family",
        "dish-heitaliyeh-aleppan-jewish-panama",
        "dish-macaroni-chicken-aleppan-diaspora",
    }:
        assert entities[entity_id]["parent_id"] == (
            "tradition-syrian-jewish-migration-adaptation"
        )

    for entity_id in JEWISH_FAMILY_IDS:
        entity = entities[entity_id]
        family_content = " ".join(
            [
                *entity["name"].values(),
                *entity["summary"].values(),
                *entity["seo"]["primary_intent"].values(),
                *(
                    statement
                    for fact in entity["facts"]
                    for statement in fact["statement"].values()
                ),
            ]
        ).lower()
        assert "family" in family_content, entity_id
        codes = {rule["code"] for rule in entity["compliance"]}
        assert "community-source-no-exclusive-origin" in codes, entity_id

    nli = entities[
        "institution-national-library-israel-syrian-jewish-context-archive"
    ]
    assert set(nli["facts"][0]["source_ids"]) == {
        "nli-aleppo-tradition",
        "nli-damascus-tradition",
    }
    assert {
        "tradition-aleppan-jewish-foodways",
        "tradition-damascene-jewish-foodways",
    } <= _relation_targets(nli)


def test_archives_are_documentation_not_representative_or_copyable(
    entities: dict[str, dict]
) -> None:
    for entity_id in ARCHIVE_IDS:
        entity = entities[entity_id]
        codes = {rule["code"] for rule in entity["compliance"]}
        assert "archive-rights-and-representativeness" in codes, entity_id
        assert "original-visual-no-archive-copy" in codes, entity_id
        text = _text(entity)
        assert "archive" in text or "institution" in text, entity_id
        assert "represent" in text or "מייצג" in text, entity_id


def test_food_entities_have_machine_enforced_meat_dairy_and_allergen_controls(
    entities: dict[str, dict]
) -> None:
    expected_codes = {
        "dish-passover-kibbeh-damascene-jewish": {
            "ground-meat-source-cold-chain-and-thermal-validation",
            "matzo-sesame-allergen-and-frying-validation",
            "passover-status-requires-authority-review",
        },
        "dish-ejjeh-syrian-jewish-family": {
            "egg-matzo-allergen-and-frying-validation",
        },
        "dish-heitaliyeh-aleppan-jewish-panama": {
            "variant-specific-dairy-allergen-and-cold-chain-validation",
        },
        "ingredient-aleppan-jewish-string-cheese-family": {
            "dairy-pasteurization-allergen-and-cold-chain-validation",
        },
        "dish-chicken-mehshi-sfeeha-aleppan-family": {
            "poultry-source-cold-chain-and-thermal-validation",
            "stuffed-vegetable-allergen-and-cooling-validation",
        },
        "dish-macaroni-chicken-aleppan-diaspora": {
            "poultry-source-cold-chain-and-thermal-validation",
            "pasta-gluten-allergen-and-cooling-validation",
        },
        "dish-doshka-syrian-armenian-family": {
            "processed-meat-source-cold-chain-and-thermal-validation",
            "dairy-and-gluten-allergen-product-validation",
        },
    }
    safety_sources = {
        "foodsafety-safe-temperatures",
        "israel-moh-allergen-survey-2024",
        "israel-moh-food-hygiene",
    }

    for entity_id, required_codes in expected_codes.items():
        controls = {
            rule["code"]: rule for rule in entities[entity_id]["compliance"]
        }
        assert required_codes <= set(controls), entity_id
        for code in required_codes:
            assert controls[code]["public_safe"] is False, (entity_id, code)
            if code == "passover-status-requires-authority-review":
                assert set(controls[code]["source_ids"]) == {
                    "jfs-passover-kibbeh-damascus"
                }
            else:
                assert set(controls[code]["source_ids"]) & safety_sources, (
                    entity_id,
                    code,
                )


def test_restaurants_have_dated_or_unverified_status_and_zero_endorsement(
    entities: dict[str, dict]
) -> None:
    for entity_id, status_tag in RESTAURANT_STATUS_TAGS.items():
        entity = entities[entity_id]
        assert status_tag in entity["taxonomy"]["tags"], entity_id
        assert "benchmark-only-no-endorsement" in {
            rule["code"] for rule in entity["compliance"]
        }
        text = _text(entity)
        assert "no endorsement" in text, entity_id
        assert "partnership" in text, entity_id
        assert entity["commerce"]["public_offer_allowed"] is False

    old_ashtarak = entities["restaurant-old-ashtarak-syrian-armenian"]
    assert "current operation is unverified" in _text(old_ashtarak)


def test_non_jewish_community_entities_keep_exact_scope_and_links(
    entities: dict[str, dict]
) -> None:
    doshka = entities["dish-doshka-syrian-armenian-family"]
    assert doshka["parent_id"] == "tradition-syrian-armenian-aleppo"
    assert "source attribution rather than an exclusive verdict" in _text(doshka)

    enterprise = entities["tradition-syrian-armenian-food-enterprise-diaspora"]
    assert {
        "restaurant-abu-hagop-yerevan",
        "restaurant-old-ashtarak-syrian-armenian",
    } <= _relation_targets(enterprise)

    assyrian = entities[
        "guide-assyrian-qamishli-cross-border-foodways-boundary"
    ]
    assert assyrian["parent_id"] == "region-syria-qamishli-family-transmission"
    assert {
        "dish-dikhwa-qamishli-assyrian",
        "institution-library-of-congress-foodways-web-archive",
    } <= _relation_targets(assyrian)
    assert "no automatic syrian attribution" in _text(assyrian)

    afrin = entities["tradition-afrin-kurdish-olive-oil-memory-diaspora"]
    assert afrin["parent_id"] == "tradition-kurdish-afrin"
    assert "ingredient-syrian-olive-oil" in _relation_targets(afrin)
    assert "supplies no cultivar, acidity, polyphenols" in _text(afrin)

    coffee = entities["technique-suwayda-qahwa-murra-hospitality-service"]
    assert coffee["parent_id"] == "institution-southern-syrian-madafa"
    assert {
        "institution-southern-syrian-madafa",
        "tradition-druze-suwayda",
    } <= _relation_targets(coffee)
    assert "not a roasting, grinding, dose, temperature" in _text(coffee)
