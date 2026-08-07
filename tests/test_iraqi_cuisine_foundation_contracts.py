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
IRAQI_MODULES = (
    PLUGIN / "data" / "culinary-science" / "cuisines" / "iraqi-foundations.php",
    PLUGIN / "data" / "culinary-science" / "cuisines" / "iraqi-regional-depth.php",
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "iraqi-community-institutions.php",
)

EXPECTED_BY_TYPE = {
    "cuisine": {"cuisine-iraqi-regional"},
    "topic_hub": {
        "region-iraq-baghdad",
        "region-iraq-mosul-ninewa",
        "region-iraq-basra-shatt-al-arab",
        "region-iraq-middle-euphrates",
        "region-iraq-marshes-south",
        "region-iraq-kurdistan",
        "region-iraq-kirkuk-diyala",
        "hub-iraqi-kubba-family",
        "hub-iraqi-rice-stews",
        "hub-iraqi-fish-fire",
        "hub-iraqi-date-palm",
        "hub-iraqi-fermentation-preservation",
        "hub-iraqi-bread-bakery",
        "hub-iraqi-community-foodways",
        "hub-iraqi-institutions-markets",
        "hub-iraqi-jewish-foodways",
    },
    "dish": {
        "dish-masgouf-iraq",
        "dish-dolma-iraqi-family",
        "dish-tepsi-baytinijan-iraq",
        "dish-quzi-iraq",
        "dish-tashreeb-iraq",
        "dish-timman-bagilla-iraq",
        "dish-biryani-iraqi-family",
        "dish-margat-bamia-iraq",
        "dish-kahi-geymar-baghdad",
        "dish-pacha-iraq",
        "dish-uruq-baghdadi",
        "dish-kubba-mosul",
        "dish-kubba-halab-iraqi-rice-shell",
        "dish-lahm-bi-ajin-mosul",
        "dish-hareesa-ninewa-shared",
        "dish-turshi-mahshi-ninewa",
        "dish-mutabbaq-samak-basra",
        "dish-masmouta-basra",
        "dish-sayadiyah-basra",
        "dish-kharet-marshes",
        "dish-qeema-najafiya",
        "dish-daheen-najaf",
        "dish-kleicha-iraq",
        "dish-samoon-stone-baghdad",
        "dish-kubba-shwandar-iraqi-jewish-family",
        "dish-kubba-hamusta-iraqi-kurdish-jewish-family",
        "dish-kubba-batata-iraqi-jewish-family",
        "dish-tbit-iraqi-jewish-family",
        "dish-sambusak-btawa-iraqi-jewish-family",
        "dish-kichree-iraqi-jewish-family",
        "dish-ingriyeh-iraqi-jewish-family",
        "dish-yaprakh-iraqi-kurdistan",
    },
    "preparation": {
        "preparation-masgouf-fire-distance-control",
        "preparation-iraqi-dolma-stack-and-inversion",
        "preparation-hkaka-iraqi-rice-crust",
        "preparation-sabich-iraqi-jewish-breakfast-context",
    },
    "ingredient": {
        "ingredient-iraqi-amber-rice-context",
        "ingredient-iraqi-bulgur-jreesh-context",
        "ingredient-iraqi-semolina-kubba-context",
        "ingredient-iraqi-date-cultivars-context",
        "ingredient-iraqi-date-syrup-dibs",
        "ingredient-noomi-basra-dried-lime",
        "ingredient-iraqi-amba-process-context",
        "ingredient-iraqi-turshi-vegetable-family",
        "ingredient-iraqi-freshwater-fish-family",
        "ingredient-basra-dried-fish-family",
        "ingredient-iraqi-geymar-dairy-context",
        "ingredient-iraqi-aushari-zhazhi-dairy-context",
    },
    "technique": {
        "technique-masgouf-indirect-fire",
        "technique-iraqi-kubba-shell-rheology",
        "technique-iraqi-stuffed-vegetable-cooking",
        "technique-iraqi-rice-cooling-hot-holding",
        "technique-iraqi-turshi-fermentation",
        "technique-iraqi-date-syrup-concentration",
        "technique-iraqi-dried-fish-preservation",
        "technique-iraqi-cultured-dairy-control",
    },
    "tradition": {
        "tradition-iraqi-jewish-foodways-bounded",
        "tradition-iraqi-jewish-harisa-sawdayee-oral-history",
        "tradition-iraqi-assyrian-household-foodways",
        "tradition-iraqi-chaldean-household-foodways",
        "tradition-iraqi-mandaean-ritual-foodways",
        "tradition-iraqi-yazidi-festival-foodways",
        "tradition-tal-afar-turkmen-household-foodways",
        "tradition-iraqi-kurdish-household-foodways",
        "tradition-iraq-arbaeen-hospitality",
        "tradition-mosul-plural-community-foodways-boundary",
    },
    "culinary_institution": {
        "institution-babylonian-jewry-heritage-center",
        "institution-nli-eli-timan-iraqi-jewish-oral-history",
        "institution-nara-iraqi-jewish-archive",
        "institution-iraqi-peoples-heritage-oral-history-project",
        "institution-mosul-lives-oral-history-project",
    },
    "market": {
        "market-al-shorja-baghdad-research-benchmark",
        "market-qaysariyah-erbil-research-benchmark",
        "market-basra-al-ashar-fish-system-benchmark",
    },
    "restaurant": {
        "restaurant-bestoon-samad-baghdad-benchmark",
        "restaurant-kabab-erbil-dubai-tourism-benchmark",
    },
    "compliance_rule": {"compliance-iraq-trade-israel-2026"},
    "guide": {
        "guide-iraqi-oral-history-source-intake",
        "guide-iraqi-market-restaurant-benchmark-capture",
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
def iraqi_entities(registry: dict) -> dict[str, dict]:
    return {
        entity["id"]: entity
        for entity in registry["entities"]
        if entity["seo"]["cluster_id"] == "cluster-iraqi-regional-cuisine"
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


def test_exact_private_iraqi_foundation_membership(
    registry: dict, iraqi_entities: dict[str, dict]
) -> None:
    assert registry["version"] == "culinary-science-2026.08.07.v16"
    assert len(registry["entities"]) == 551
    assert all(module.is_file() for module in IRAQI_MODULES)
    loader = SCIENCE_DATA.read_text(encoding="utf-8")
    assert all(module.name in loader for module in IRAQI_MODULES)
    assert set(iraqi_entities) == EXPECTED_IDS
    assert len(iraqi_entities) == 96
    assert Counter(entity["type"] for entity in iraqi_entities.values()) == {
        entity_type: len(ids) for entity_type, ids in EXPECTED_BY_TYPE.items()
    }


def test_every_iraqi_entity_is_private_noindex_and_noncommercial(
    iraqi_entities: dict[str, dict]
) -> None:
    for entity_id, entity in iraqi_entities.items():
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
        assert entity["commerce"]["business_model"]["observation_entity_ids"] == [], entity_id
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
    entities: dict[str, dict], iraqi_entities: dict[str, dict]
) -> None:
    root = entities["cuisine-iraqi-regional"]
    assert root["parent_id"] == "museum-culinary-science"
    assert root["seo"]["page_role"] == "pillar"
    assert root["seo"]["hub_entity_id"] == root["id"]
    assert root["seo"]["canonical_path"] == {
        "he": "/museum/iraqi-culinary-science/",
        "en": "/en/museum/iraqi-culinary-science/",
    }
    for entity_id, entity in iraqi_entities.items():
        assert entity["seo"]["cluster_id"] == "cluster-iraqi-regional-cuisine"
        assert entity["seo"]["hub_entity_id"] == "cuisine-iraqi-regional"
        assert entity["seo"]["canonical_path"]["he"].startswith(
            "/museum/iraqi-culinary-science/"
        ), entity_id
        assert entity["seo"]["canonical_path"]["en"].startswith(
            "/en/museum/iraqi-culinary-science/"
        ), entity_id


def test_every_entity_references_the_trade_boundary(
    iraqi_entities: dict[str, dict]
) -> None:
    boundary_id = "compliance-iraq-trade-israel-2026"
    for entity_id, entity in iraqi_entities.items():
        if entity_id == boundary_id:
            text = json.dumps(entity, ensure_ascii=False).lower()
            assert "direct or indirect trade" in text
            assert "third party" in text or "third-country" in text
            continue
        relation = _relation(entity, "references", boundary_id)
        assert relation["evidence_class"] == "regulatory_standard"
        assert relation["source_ids"] == ["govil-iraq-trade-2026"]


def test_shared_families_compare_without_merging_or_exclusive_origin(
    entities: dict[str, dict]
) -> None:
    comparisons = (
        ("dish-dolma-iraqi-family", "dish-yabraq-yebra"),
        ("dish-kubba-mosul", "hub-aleppine-kibbeh-family"),
        ("dish-kubba-mosul", "hub-lebanese-kibbeh-family"),
        ("dish-sayadiyah-basra", "dish-sayadiyah-syrian-coast"),
        ("dish-sayadiyah-basra", "dish-sayadiyah-lebanon"),
    )
    for subject_id, target_id in comparisons:
        relation = _relation(entities[subject_id], "references", target_id)
        text = relation["note"]["en"].lower()
        assert "without merging" in text
        assert "exclusive origin" in text

    kubba_halab = json.dumps(
        entities["dish-kubba-halab-iraqi-rice-shell"], ensure_ascii=False
    ).lower()
    assert "does not transfer ownership to aleppo" in kubba_halab
    assert "does not replace the syrian aleppine kibbeh family" in kubba_halab


def test_existing_sabich_amba_and_beet_kubbeh_ownership_is_preserved(
    entities: dict[str, dict]
) -> None:
    sabich = json.dumps(
        entities["preparation-sabich-iraqi-jewish-breakfast-context"],
        ensure_ascii=False,
    ).lower()
    assert "public sabich dish" in sabich
    assert "without changing ownership of the dish page" in sabich

    amba = json.dumps(
        entities["ingredient-iraqi-amba-process-context"], ensure_ascii=False
    ).lower()
    assert "does not replace the public amba entity" in amba

    beet = entities["dish-kubba-shwandar-iraqi-jewish-family"]
    assert beet["seo"]["route_mode"] == "private"
    assert beet["commerce"]["woo_product_code"] == ""


def test_food_safety_controls_fail_closed(entities: dict[str, dict]) -> None:
    expected_codes = {
        "dish-masgouf-iraq": {
            "fish-species-cold-chain-and-thermal-validation",
            "open-fire-fuel-distance-and-smoke-validation",
        },
        "dish-pacha-iraq": {"organ-meat-inspection-and-thermal-validation"},
        "dish-tbit-iraqi-jewish-family": {
            "cooked-rice-time-temperature-control",
            "meat-source-and-thermal-validation",
        },
        "technique-iraqi-rice-cooling-hot-holding": {
            "cooked-rice-time-temperature-control"
        },
        "technique-iraqi-turshi-fermentation": {
            "fermentation-process-and-shelf-life-validation"
        },
        "technique-iraqi-date-syrup-concentration": {
            "date-product-measurement-and-claims-boundary"
        },
        "technique-iraqi-cultured-dairy-control": {
            "dairy-batch-and-allergen-validation",
            "fermentation-process-and-shelf-life-validation",
        },
    }
    for entity_id, codes in expected_codes.items():
        assert {note["code"] for note in entities[entity_id]["compliance"]} == codes

    dried_fish = json.dumps(
        entities["technique-iraqi-dried-fish-preservation"], ensure_ascii=False
    ).lower()
    assert "water activity" in dried_fish
    assert "biogenic" in dried_fish


def test_community_records_are_bounded_and_not_the_whole_iraqi_cuisine(
    entities: dict[str, dict]
) -> None:
    jewish = json.dumps(
        entities["tradition-iraqi-jewish-foodways-bounded"], ensure_ascii=False
    ).lower()
    assert "not a substitute for iraqi cuisine as a whole" in jewish
    assert "not ownership of shared dishes" in jewish

    testimony = json.dumps(
        entities["tradition-iraqi-jewish-harisa-sawdayee-oral-history"],
        ensure_ascii=False,
    ).lower()
    assert "one person's memory" in testimony
    assert "does not define one formula" in testimony

    for entity_id in {
        "tradition-iraqi-assyrian-household-foodways",
        "tradition-iraqi-chaldean-household-foodways",
        "tradition-iraqi-mandaean-ritual-foodways",
        "tradition-iraqi-yazidi-festival-foodways",
        "tradition-tal-afar-turkmen-household-foodways",
        "tradition-iraqi-kurdish-household-foodways",
    }:
        assert entities[entity_id]["taxonomy"]["attributes"]["pa_community"]
        assert entities[entity_id]["publication"]["public_page"] is False


def test_no_iraqi_price_observation_offer_or_public_projection(
    registry: dict, iraqi_entities: dict[str, dict]
) -> None:
    assert not {
        entity_id
        for entity_id, entity in iraqi_entities.items()
        if entity["type"] in {"retail_listing", "market_observation"}
    }
    public_ids = {
        entity["id"]
        for entity in registry["entities"]
        if entity["publication"]["public_api"]
    }
    assert len(public_ids) == 23
    assert not (public_ids & EXPECTED_IDS)

    module_text = "\n".join(
        module.read_text(encoding="utf-8") for module in IRAQI_MODULES
    )
    assert "public_offer_allowed' => true" not in module_text
    assert "public_api' => true" not in module_text
    assert "'type' => 'retail_listing'" not in module_text
    assert "'type' => 'market_observation'" not in module_text
    assert "\u2014" not in module_text
