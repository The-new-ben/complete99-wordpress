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
LEBANESE_MODULE = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "lebanese-foundations.php"
)

EXPECTED_BY_TYPE = {
    "cuisine": {"cuisine-lebanese-regional"},
    "topic_hub": {
        "region-lebanon-beirut",
        "region-lebanon-mount-lebanon-shouf",
        "region-lebanon-north-akkar-tripoli",
        "region-lebanon-bekaa-baalbek-hermel",
        "region-lebanon-south-jabal-amel",
        "hub-lebanese-manouche-practice",
        "hub-lebanese-kibbeh-family",
        "hub-lebanese-mouneh-system",
        "hub-lebanese-community-foodways",
        "hub-lebanese-institutions-markets",
        "hub-lebanese-plant-seafood-table",
        "hub-armenian-lebanese-bourj-hammoud",
        "hub-palestinian-foodways-lebanon",
    },
    "dish": {
        "dish-al-manouche-lebanon",
        "dish-tabbouleh-lebanon",
        "dish-fattoush-lebanon",
        "dish-mujaddara-lebanon-family",
        "dish-kibbeh-zghartawiyeh",
        "dish-kibbeh-summakiyeh-hermel",
        "dish-kibbeh-laqtin-west-bekaa",
        "dish-kibbeh-arnabiyyeh-beirut",
        "dish-kibbeh-samak-lebanon",
        "dish-kibbeh-nayyeh-lebanon",
        "dish-lentil-fennel-kibbeh-andaket",
        "dish-samkeh-harra-tripoli",
        "dish-sayadiyah-lebanon",
        "dish-sfiha-baalbek",
        "dish-moufataka-beirut",
        "dish-loubieh-bi-zayt-lebanon",
        "dish-fennel-tabbouleh-jezzine",
        "dish-moghrabieh-lebanon",
        "dish-mtashtash-akkar",
        "dish-lemon-zenkoul-west-bekaa",
        "dish-moufataka-beirut-assida",
        "dish-meghle-lebanon",
        "dish-kaak-el-abbass-south-lebanon",
        "dish-hamod-lebanese-jewish-family",
        "dish-bazela-lebanese-jewish-family",
        "dish-mahshi-lebanese-jewish-family",
        "dish-karabij-lebanese-jewish-wedding",
    },
    "preparation": {
        "preparation-mujaddara-hamra-rmeish",
        "preparation-mudardara-rice-lebanon",
    },
    "ingredient": {
        "ingredient-lebanese-zaatar-blend",
        "ingredient-lebanese-bulgur-context",
        "ingredient-lebanese-kishk",
        "ingredient-labneh-ambarees-shouf",
        "ingredient-lebanese-qawarma",
        "ingredient-lebanese-pomegranate-molasses",
        "ingredient-lebanese-sumac-context",
        "ingredient-lebanese-olive-oil-context",
    },
    "technique": {
        "technique-manouche-indenting-baking-lebanon",
        "technique-kibbeh-pounding-forming-lebanon",
        "technique-kishk-fermentation-drying-lebanon",
        "technique-labneh-ambarees-sirdele-fermentation",
        "technique-qawarma-preservation-lebanon",
    },
    "tradition": {
        "tradition-al-manouche-sobhiyyeh",
        "tradition-lebanese-regional-kibbeh-adaptation",
        "tradition-lebanese-mouneh-seasonal-cycle",
        "tradition-lebanese-jewish-foodways",
        "tradition-nina-dahan-shabbat-hamod",
        "tradition-sabat-diyafat-lebanese-jewish-wedding",
        "tradition-lebanese-christian-lent-foodways",
        "tradition-south-lebanon-ashura-foodways",
        "tradition-druze-wild-plant-knowledge-chouf-aley",
    },
    "culinary_institution": {
        "institution-food-heritage-foundation",
        "institution-phoenicia-culinary-institute",
        "institution-aub-palestinian-oral-history-archive",
        "institution-soufra-burj-el-barajneh",
        "institution-tawlet-mar-mikhael",
    },
    "market": {"market-souk-el-tayeb", "market-dekenet-mar-mikhael"},
    "restaurant": {
        "restaurant-hallab-1881",
        "restaurant-mayrig-beirut",
        "restaurant-em-sherif-beirut",
    },
    "compliance_rule": {"compliance-lebanon-trade-israel-2026"},
    "retail_listing": {
        "listing-mymoune-pomegranate-molasses-250ml-spinneys-20260807",
        "listing-mymoune-zaatar-200g-spinneys-20260807",
        "listing-terroirs-zaatar-70g-eu-20260807",
        "listing-terroirs-freekeh-500g-eu-20260807",
        "listing-pereg-zaatar-baladi-ils-20260807",
        "listing-nitzat-pomegranate-concentrate-280g-ils-20260807",
    },
}
EXPECTED_IDS = set().union(*EXPECTED_BY_TYPE.values())


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def registry() -> dict:
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
echo json_encode($registry, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
def entities(registry: dict) -> dict[str, dict]:
    return {entity["id"]: entity for entity in registry["entities"]}


@pytest.fixture(scope="module")
def lebanese_entities(registry: dict) -> dict[str, dict]:
    return {
        entity["id"]: entity
        for entity in registry["entities"]
        if entity["id"] in EXPECTED_IDS
    }


def _relation(entity: dict, relation_type: str, target_id: str) -> dict:
    matches = [
        relation
        for relation in entity["relations"]
        if relation["type"] == relation_type
        and relation["target_id"] == target_id
    ]
    assert len(matches) == 1, (entity["id"], relation_type, target_id, matches)
    return matches[0]


def test_module_is_loaded_and_owns_one_gateway_plus_private_foundation(
    registry: dict, lebanese_entities: dict[str, dict]
) -> None:
    assert LEBANESE_MODULE.is_file()
    loader = SCIENCE_DATA.read_text(encoding="utf-8")
    module = LEBANESE_MODULE.read_text(encoding="utf-8")
    assert "lebanese-foundations.php" in loader
    assert "culinary-science-2026.08.08.v20" in module
    assert "'public_gateway_ids' => array( 'cuisine-lebanese-regional' )" in module
    assert "'private_entity_ids' => array_values(" in module
    assert registry["version"] == "culinary-science-2026.08.08.v20"
    assert set(lebanese_entities) == EXPECTED_IDS
    assert len(lebanese_entities) == 82
    assert Counter(entity["type"] for entity in lebanese_entities.values()) == {
        entity_type: len(ids) for entity_type, ids in EXPECTED_BY_TYPE.items()
    }


def test_only_lebanese_gateway_is_public_and_all_other_foundations_remain_private(
    lebanese_entities: dict[str, dict]
) -> None:
    gateway = lebanese_entities["cuisine-lebanese-regional"]
    assert gateway["surface_class"] == "public_discovery"
    assert gateway["index_policy"] == "noindex_until_longform_review"
    assert gateway["publication"] == {
        "state": "approved_public",
        "public_api": True,
        "public_page": True,
        "search_index": False,
        "approved_at": "2026-08-07",
    }
    assert gateway["seo"]["route_mode"] == "standalone"
    assert gateway["commerce"]["state"] == "reference_only"
    assert gateway["commerce"]["public_offer_allowed"] is False
    assert gateway["visual"]["asset_state"] == "approved"
    assert gateway["visual"]["rights_state"] == "cleared_generated"

    for entity_id, entity in lebanese_entities.items():
        if entity_id == "cuisine-lebanese-regional":
            continue
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
        assert entity["taxonomy"]["public_category_path"] == [], entity_id
        assert entity["taxonomy"]["public_attribute_keys"] == [], entity_id
        assert entity["taxonomy"]["public_tags"] == [], entity_id
        assert entity["visual"]["asset_state"] == "rights_review_required", entity_id
        assert entity["visual"]["rights_state"] == "pending", entity_id
        assert entity["visual"]["rights_receipt_digest"] == "", entity_id
        assert entity["visual"]["prompt_en"], entity_id
        assert entity["facts"], entity_id
        assert all(fact["source_ids"] for fact in entity["facts"]), entity_id
        assert all(fact["public_safe"] is False for fact in entity["facts"]), entity_id
        assert all(relation["source_ids"] for relation in entity["relations"]), entity_id
        assert all(relation["public_safe"] is False for relation in entity["relations"]), entity_id


def test_cluster_ancestry_and_canonical_paths_are_isolated(
    entities: dict[str, dict], lebanese_entities: dict[str, dict]
) -> None:
    root = entities["cuisine-lebanese-regional"]
    assert root["parent_id"] == "museum-culinary-science"
    assert root["seo"]["page_role"] == "pillar"
    assert root["seo"]["hub_entity_id"] == root["id"]
    assert root["seo"]["canonical_path"] == {
        "he": "/museum/lebanese-culinary-science/",
        "en": "/en/museum/lebanese-culinary-science/",
    }
    for entity_id, entity in lebanese_entities.items():
        assert entity["seo"]["cluster_id"] == "cluster-lebanese-regional-cuisine"
        assert entity["seo"]["hub_entity_id"] == "cuisine-lebanese-regional"
        assert entity["seo"]["canonical_path"]["he"].startswith(
            "/museum/lebanese-culinary-science/"
        ), entity_id
        assert entity["seo"]["canonical_path"]["en"].startswith(
            "/en/museum/lebanese-culinary-science/"
        ), entity_id


def test_every_entity_references_the_trade_boundary(
    lebanese_entities: dict[str, dict]
) -> None:
    boundary_id = "compliance-lebanon-trade-israel-2026"
    for entity_id, entity in lebanese_entities.items():
        if entity_id == boundary_id:
            notes = entity["compliance"]
            assert {note["code"] for note in notes} == {"enemy-state-trade"}
            assert "direct or indirect trade" in entity["facts"][0]["statement"]["en"]
            continue
        relation = _relation(entity, "references", boundary_id)
        assert relation["evidence_class"] == "regulatory_standard"
        assert relation["source_ids"] == ["israel-enemy-states-trade-2026"]


def test_shared_levantine_families_are_compared_without_merging(
    entities: dict[str, dict]
) -> None:
    comparisons = {
        "dish-sayadiyah-lebanon": "dish-sayadiyah-syrian-coast",
        "dish-samkeh-harra-tripoli": "dish-samaka-harra-baniyas",
        "dish-kibbeh-summakiyeh-hermel": "dish-kibbeh-somakiyya",
        "dish-mujaddara-lebanon-family": "preparation-mujadara-thursday-syrian-jewish",
        "ingredient-lebanese-bulgur-context": "ingredient-syrian-bulgur",
        "ingredient-lebanese-kishk": "ingredient-syrian-kishk",
        "ingredient-lebanese-pomegranate-molasses": "ingredient-syrian-pomegranate-molasses",
        "ingredient-lebanese-sumac-context": "ingredient-syrian-sumac",
        "ingredient-lebanese-olive-oil-context": "ingredient-syrian-olive-oil",
    }
    for subject_id, target_id in comparisons.items():
        relation = _relation(entities[subject_id], "references", target_id)
        text = relation["note"]["en"].lower()
        assert "merge" in text or "without merging" in text
        assert entities[subject_id]["id"] != entities[target_id]["id"]

    family = entities["dish-mujaddara-lebanon-family"]
    assert set(family["seo"]["expected_child_ids"]) == {
        "preparation-mujaddara-hamra-rmeish",
        "preparation-mudardara-rice-lebanon",
    }


def test_raw_meat_fermentation_and_foraging_fail_closed(
    entities: dict[str, dict]
) -> None:
    raw = entities["dish-kibbeh-nayyeh-lebanon"]
    raw_text = json.dumps(raw, ensure_ascii=False).lower()
    assert "no recipe" in raw_text
    assert "no preparation instructions" in raw_text
    assert "consumption recommendation" in raw_text
    assert {note["code"] for note in raw["compliance"]} == {"raw-ground-meat"}

    for entity_id in {
        "ingredient-lebanese-kishk",
        "ingredient-labneh-ambarees-shouf",
        "ingredient-lebanese-qawarma",
        "technique-kishk-fermentation-drying-lebanon",
        "technique-labneh-ambarees-sirdele-fermentation",
        "technique-qawarma-preservation-lebanon",
    }:
        text = json.dumps(entities[entity_id], ensure_ascii=False).lower()
        assert "water activity" in text or "microbiology" in text or "haccp" in text
        assert "validated" in text or "validation" in text or "measured" in text

    foraging = entities["tradition-druze-wild-plant-knowledge-chouf-aley"]
    assert {note["code"] for note in foraging["compliance"]} == {
        "wild-plant-identification"
    }
    assert "not permission for foraging" in json.dumps(
        foraging, ensure_ascii=False
    ).lower()


def test_community_evidence_remains_family_or_occasion_scoped(
    entities: dict[str, dict]
) -> None:
    jewish = entities["tradition-lebanese-jewish-foodways"]
    assert jewish["taxonomy"]["attributes"]["pa_community"] == [
        "lebanese-jewish"
    ]
    assert "without turning shared levantine dishes into jewish inventions" in jewish[
        "summary"
    ]["en"].lower()

    hamod = entities["dish-hamod-lebanese-jewish-family"]
    assert hamod["taxonomy"]["attributes"]["pa_region"] == [
        "lebanese-jewish-family"
    ]
    assert "family-specific" in json.dumps(hamod, ensure_ascii=False).lower()

    palestinian = entities["hub-palestinian-foodways-lebanon"]
    palestinian_text = json.dumps(palestinian, ensure_ascii=False).lower()
    assert "without assigning dishes lebanese origin" in palestinian_text
    assert "commercial lead" in palestinian_text


def test_price_observations_are_dated_references_not_offers(
    entities: dict[str, dict]
) -> None:
    listing_ids = EXPECTED_BY_TYPE["retail_listing"]
    for listing_id in listing_ids:
        listing = entities[listing_id]
        assert listing["commerce"]["business_model"]["pricing_state"] == (
            "source_price_observed"
        )
        assert listing["commerce"]["state"] == "reference_only"
        assert listing["commerce"]["public_offer_allowed"] is False
        fact = listing["facts"][0]
        assert fact["dimension"] == "economic"
        assert fact["evidence_class"] == "market_observation"
        assert fact["observed_at"] == "2026-08-07T12:00:00+03:00"

    concentrate = entities[
        "listing-nitzat-pomegranate-concentrate-280g-ils-20260807"
    ]
    assert concentrate["parent_id"] == "hub-lebanese-mouneh-system"
    _relation(concentrate, "references", "ingredient-pomegranate-concentrate")
    concentrate_text = json.dumps(concentrate, ensure_ascii=False).lower()
    assert "rather than molasses" in concentrate_text
    assert "must not be merged" in concentrate_text


def test_public_science_adds_only_reviewed_lebanese_gateway(registry: dict) -> None:
    public_ids = {
        entity["id"]
        for entity in registry["entities"]
        if entity["publication"]["public_api"]
    }
    assert len(public_ids) == 27
    assert "cuisine-lebanese-regional" in public_ids

    module_text = LEBANESE_MODULE.read_text(encoding="utf-8")
    assert "woo_product_code" not in module_text
    assert "public_offer_allowed' => true" not in module_text
    assert "public_api' => true" not in module_text
    assert "\u2014" not in module_text
