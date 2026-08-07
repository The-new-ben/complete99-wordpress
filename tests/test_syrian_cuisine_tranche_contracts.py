from __future__ import annotations

import json
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"
SYRIAN_LEAF = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "syrian-foundations.php"
)
SYRIAN_DEPTH_LEAF = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "syrian-regional-depth.php"
)

EXPECTED_REGIONS = {
    "region-syria-aleppo",
    "region-syria-damascus",
    "region-syria-homs",
    "region-syria-hama",
    "region-syria-coast",
    "region-syria-jazira",
    "region-syria-euphrates-east",
    "region-syria-suwayda",
    "region-syria-hauran",
}
EXPECTED_DISHES = {
    "dish-muhammara-syrian",
    "dish-kibbeh-meshwiyyeh",
    "dish-kibbeh-safarjaliyyeh",
    "dish-kebab-bil-karaz",
    "dish-lahm-bi-ajin-syria",
    "dish-kibbeh-labaniyyeh",
    "dish-kibbeh-b-hamod",
    "dish-yabraq-yebra",
    "dish-yalangi",
    "dish-shishbarak",
    "dish-ouzi-damascene",
    "dish-matzah-kebab-damascene-jewish",
    "dish-batersh-hama",
    "dish-halawet-al-jibn",
    "dish-sayadiyah-syrian-coast",
    "dish-samaka-harra-baniyas",
    "dish-kibbat-al-silik",
    "dish-thareed-raqqa-rural",
    "dish-fawra-deir",
    "dish-kubaybat-haqt-qamishli",
    "dish-mansaf-mleihi",
    "dish-kibbeh-hamda-aleppan-jewish",
    "dish-dajaj-mashwi-aleppan-jewish",
    "dish-kitawiyeh-afrin-kurdish",
}
EXPECTED_INGREDIENTS = {
    "ingredient-pomegranate-concentrate",
    "ingredient-syrian-bulgur",
    "ingredient-syrian-jreesh",
    "ingredient-syrian-freekeh",
    "ingredient-syrian-rice",
    "ingredient-syrian-chickpeas",
    "ingredient-syrian-green-peas",
    "ingredient-syrian-lentils",
    "ingredient-syrian-black-eyed-peas",
    "ingredient-syrian-grape-leaves",
    "ingredient-raqqa-tannour-bread",
    "ingredient-syrian-matzah",
    "ingredient-syrian-onion",
    "ingredient-syrian-eggplant",
    "ingredient-syrian-swiss-chard",
    "ingredient-syrian-red-pepper-paste",
    "ingredient-syrian-dried-red-pepper",
    "ingredient-syrian-tomato-paste",
    "ingredient-syrian-pomegranate-molasses",
    "ingredient-syrian-lemon",
    "ingredient-syrian-tamarind",
    "ingredient-syrian-sumac",
    "ingredient-syrian-sour-cherry",
    "ingredient-syrian-quince",
    "ingredient-syrian-tahini",
    "ingredient-syrian-walnuts",
    "ingredient-syrian-unspecified-nuts",
    "ingredient-syrian-pistachios",
    "ingredient-syrian-cheese",
    "ingredient-syrian-semolina",
    "ingredient-syrian-qeshta-cream",
    "ingredient-syrian-sugar-syrup",
    "ingredient-syrian-fresh-yogurt",
    "ingredient-syrian-kishk",
    "ingredient-syrian-jameed",
    "ingredient-syrian-higet",
    "ingredient-syrian-haqt",
    "ingredient-syrian-olive-oil",
    "ingredient-syrian-samn",
    "ingredient-syrian-red-meat",
    "ingredient-syrian-whole-chicken",
    "ingredient-syrian-coastal-fish",
    "ingredient-syrian-garlic",
    "ingredient-syrian-dried-apricot",
    "ingredient-syrian-allspice",
    "ingredient-syrian-dried-mint",
    "ingredient-aleppan-ou-souring-concentrate",
}
EXPECTED_TECHNIQUES = {
    "technique-syrian-bulgur-hydration",
    "technique-syrian-kibbeh-shell-shaping",
    "technique-syrian-kibbeh-cooking",
    "technique-syrian-yogurt-sauce-stability",
    "technique-syrian-stuffing-grape-leaves",
    "technique-syrian-sour-fruit-braising",
    "technique-syrian-charred-eggplant",
    "technique-syrian-onion-browning-sayadiyah",
    "technique-syrian-saj-bread",
    "technique-syrian-tannour-bread",
    "technique-syrian-mouneh",
    "technique-syrian-cultured-dried-dairy",
}
EXPECTED_TRADITIONS = {
    "tradition-syrian-hospitality-sharing",
    "tradition-syrian-mouneh",
    "tradition-syrian-ramadan-eid-foodways",
    "tradition-aleppan-jewish-foodways",
    "tradition-damascene-jewish-foodways",
    "tradition-syrian-armenian-aleppo",
    "tradition-kurdish-afrin",
    "tradition-druze-suwayda",
}
EXPECTED_PREPARATIONS = {
    "preparation-mleihi-suwayda-fresh-yogurt",
    "preparation-mleihi-hauran-jameed",
    "preparation-halawet-homs-cheese-semolina",
    "preparation-halawet-hama-qeshta-pistachio",
    "preparation-yabraq-damascene",
    "preparation-yebra-aleppan-jewish-apricot",
}
EXPECTED_MARKET_EVIDENCE = {
    "listing-sugat-freekeh-500g-big-dabach-20260806",
    "listing-keter-harimon-pomegranate-concentrate-250ml-tamar-hst-20260806",
    "listing-tamar-bakfar-pure-ground-sumac-100g-indexed-20260806",
}
EXPECTED_DEPTH_BY_TYPE = {
    "culinary_institution": {
        "institution-hasel-al-door-suwayda",
        "institution-southern-syrian-madafa",
    },
    "dish": {
        "dish-adjwe-date-crescents-aleppan-jewish",
        "dish-al-khubziyyeh-homs",
        "dish-al-mir-jazira",
        "dish-al-mughtuta-homs",
        "dish-al-uruq-jazira",
        "dish-arman-idlib",
        "dish-basmeshqat",
        "dish-burma-palmyra",
        "dish-chika-raqqa",
        "dish-damascene-booza",
        "dish-dikhwa-qamishli-assyrian",
        "dish-fatteh-shamiyya",
        "dish-harraq-isbao",
        "dish-juwayqat-qamishli-family",
        "dish-kawaj-idlib",
        "dish-kibbeh-al-mhabaleh-homs",
        "dish-kibbeh-charola-aleppan-jewish",
        "dish-kibbeh-mabroumeh",
        "dish-kibbeh-somakiyya",
        "dish-kishk-mhabbash-palmyra",
        "dish-lazzaqiyyat-suwayda-hauran",
        "dish-maarouk-damascus",
        "dish-mansaf-qamishli-family",
        "dish-maoudeh-damascene-jewish",
        "dish-medias-damascene-jewish",
        "dish-merge-hamees-jazira",
        "dish-qaren-yaruq-deir-ez-zor",
        "dish-sakhtoura-hama",
        "dish-shakriyeh-hama",
        "dish-siyayil-raqqa-deir",
        "dish-stuffed-carrots-homs",
        "dish-thurud-bamiya-deir-ez-zor",
    },
    "guide": {
        "guide-boraniyeh-kulki-afrin-held",
        "guide-damascene-sweets",
        "guide-syrian-jewish-kibbeh-family",
    },
    "ingredient": {
        "ingredient-aleppo-pepper",
        "ingredient-damascene-rose",
        "ingredient-hauran-dried-dairy-system",
        "ingredient-qamar-al-din",
        "ingredient-suwayda-grape-molasses",
        "ingredient-syrian-baharat",
        "ingredient-syrian-coast-citrus-system",
        "ingredient-syrian-orange-blossom-water",
    },
    "market": {
        "market-souq-al-attarine-aleppo",
        "market-souq-al-buzuriyah",
        "market-souq-al-saqatiyya",
        "market-syrian-coast-fish-landing-network",
    },
    "preparation": {
        "preparation-kibbeh-fried-forms-aleppo",
        "preparation-lahm-bi-ajin-maarrat",
        "preparation-latakia-kibbeh-pomegranate-sauce",
        "preparation-latakia-kibbeh-tahini-sauce",
        "preparation-meatless-bulgur-balls-maarrat",
        "preparation-mujadara-thursday-syrian-jewish",
        "preparation-qamar-al-din-drink",
        "preparation-saj-kibbeh-aleppo",
        "preparation-waraq-enab-hamawi",
    },
    "restaurant": {"restaurant-bakdash", "restaurant-haj-abdo"},
    "technique": {
        "technique-aleppine-sour-fruit-cookery",
        "technique-dermale-qawarma-jazira",
        "technique-hauran-grain-shrak-tannour",
        "technique-home-shairiyya-euphrates",
        "technique-jazira-wheat-to-bulgur",
    },
    "topic_hub": {
        "hub-aleppine-kibbeh-family",
        "hub-homsi-kibbeh-liquid-methods",
        "hub-kutilk-shamburak-jazira",
        "region-syria-afrin-depth",
        "region-syria-baniyas",
        "region-syria-deir-ez-zor",
        "region-syria-idlib-maarrat",
        "region-syria-jableh",
        "region-syria-latakia",
        "region-syria-palmyra",
        "region-syria-qamishli-family-transmission",
        "region-syria-raqqa",
    },
    "tradition": {
        "tradition-al-mrah-rose-craft",
        "tradition-aleppan-jewish-shabbat-bread-hamine",
        "tradition-damascene-jewish-holiday-foodways",
        "tradition-homs-sweet-thursday",
        "tradition-qamishli-eid-kleija-maamoul",
        "tradition-qamishli-first-ramadan-white-dish",
        "tradition-syrian-coast-eid-bulgur",
        "tradition-syrian-jewish-foodways-depth",
        "tradition-syrian-jewish-migration-adaptation",
    },
}
EXPECTED_DEPTH_IDS = set().union(*EXPECTED_DEPTH_BY_TYPE.values())
EXPECTED_PUBLIC_JAPANESE = {
    "museum-culinary-science",
    "cuisine-japanese-washoku",
    "hub-japanese-foundations-lab",
    "hub-japanese-equipment",
    "hub-japanese-ingredients",
    "hub-japanese-techniques",
    "hub-japanese-food-science",
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
    "preparation-ichiban-dashi",
    "guide-umami-synergy",
    "guide-wasabi-aitc",
    "molecule-allyl-isothiocyanate",
    "equipment-wasabi-grater",
}
EXPECTED_PUBLIC = EXPECTED_PUBLIC_JAPANESE | {"cuisine-syrian-regional"}


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
        timeout=60,
    )
    assert completed.returncode == 0, completed.stderr
    return json.loads(completed.stdout)


@pytest.fixture(scope="module")
def entities(registry: dict) -> dict[str, dict]:
    return {entity["id"]: entity for entity in registry["entities"]}


@pytest.fixture(scope="module")
def syrian_entities(registry: dict) -> dict[str, dict]:
    return {
        entity["id"]: entity
        for entity in registry["entities"]
        if entity["seo"]["cluster_id"] == "cluster-syrian-regional-cuisine"
    }


def _one_relation(entity: dict, relation_type: str, target_id: str) -> dict:
    matches = [
        relation
        for relation in entity["relations"]
        if relation["type"] == relation_type
        and relation["target_id"] == target_id
    ]
    assert len(matches) == 1, (entity["id"], relation_type, target_id, matches)
    return matches[0]


def test_full_syrian_foundation_inventory_is_exact(
    registry: dict, syrian_entities: dict[str, dict]
) -> None:
    assert registry["version"] == "culinary-science-2026.08.07.v15"
    assert len(syrian_entities) == 196
    by_type: dict[str, set[str]] = {}
    for entity in syrian_entities.values():
        by_type.setdefault(entity["type"], set()).add(entity["id"])

    assert by_type["cuisine"] == {"cuisine-syrian-regional"}
    assert by_type["topic_hub"] == (
        EXPECTED_REGIONS | EXPECTED_DEPTH_BY_TYPE["topic_hub"]
    )
    assert by_type["dish"] == EXPECTED_DISHES | EXPECTED_DEPTH_BY_TYPE["dish"]
    assert by_type["ingredient"] == (
        EXPECTED_INGREDIENTS | EXPECTED_DEPTH_BY_TYPE["ingredient"]
    )
    assert by_type["technique"] == (
        EXPECTED_TECHNIQUES | EXPECTED_DEPTH_BY_TYPE["technique"]
    )
    assert by_type["tradition"] == (
        EXPECTED_TRADITIONS | EXPECTED_DEPTH_BY_TYPE["tradition"]
    )
    assert by_type["preparation"] == (
        EXPECTED_PREPARATIONS | EXPECTED_DEPTH_BY_TYPE["preparation"]
    )
    assert by_type["guide"] == EXPECTED_DEPTH_BY_TYPE["guide"]
    assert by_type["market"] == EXPECTED_DEPTH_BY_TYPE["market"]
    assert by_type["restaurant"] == EXPECTED_DEPTH_BY_TYPE["restaurant"]
    assert by_type["culinary_institution"] == EXPECTED_DEPTH_BY_TYPE[
        "culinary_institution"
    ]
    assert by_type["retail_listing"] == {
        "listing-sugat-freekeh-500g-big-dabach-20260806",
        "listing-keter-harimon-pomegranate-concentrate-250ml-tamar-hst-20260806",
    }
    assert by_type["market_observation"] == {
        "listing-tamar-bakfar-pure-ground-sumac-100g-indexed-20260806"
    }
    assert set(syrian_entities) == (
        {"cuisine-syrian-regional"}
        | EXPECTED_REGIONS
        | EXPECTED_DISHES
        | EXPECTED_INGREDIENTS
        | EXPECTED_TECHNIQUES
        | EXPECTED_TRADITIONS
        | EXPECTED_PREPARATIONS
        | EXPECTED_MARKET_EVIDENCE
        | EXPECTED_DEPTH_IDS
    )


def test_regional_depth_module_is_modular_source_bound_and_fail_closed(
    entities: dict[str, dict]
) -> None:
    loader = SCIENCE_DATA.read_text(encoding="utf-8")
    assert "syrian-regional-depth.php" in loader
    assert SYRIAN_DEPTH_LEAF.is_file()

    for entity_id in EXPECTED_DEPTH_IDS:
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
        assert entity["commerce"]["state"] == "reference_only"
        assert entity["commerce"]["public_offer_allowed"] is False
        assert entity["commerce"]["woo_product_code"] == ""
        assert entity["visual"]["asset_state"] == "rights_review_required"
        assert entity["visual"]["rights_state"] == "pending"
        assert entity["visual"]["prompt_en"]
        assert all(fact["public_safe"] is False for fact in entity["facts"])

        if entity["type"] in {"dish", "preparation", "ingredient", "technique"}:
            science_facts = [
                fact for fact in entity["facts"] if fact["dimension"] == "scientific"
            ]
            assert len(science_facts) == 1, entity_id
            science_text = science_facts[0]["statement"]["en"].lower()
            assert "no measured" in science_text or "supplies no" in science_text


def test_regional_depth_keeps_techniques_out_of_commerce_cross_sells(
    entities: dict[str, dict]
) -> None:
    allowed_types = {"ingredient", "equipment", "material_specification"}
    for entity_id in EXPECTED_DEPTH_IDS:
        entity = entities[entity_id]
        for target_id in entity["commerce"]["cross_sell_ids"]:
            assert entities[target_id]["type"] in allowed_types, (
                entity_id,
                target_id,
                entities[target_id]["type"],
            )

    for entity_id, technique_id in {
        "dish-arman-idlib": "technique-syrian-yogurt-sauce-stability",
        "dish-merge-hamees-jazira": "technique-syrian-saj-bread",
        "dish-shakriyeh-hama": "technique-syrian-yogurt-sauce-stability",
        "preparation-saj-kibbeh-aleppo": "technique-syrian-saj-bread",
    }.items():
        _one_relation(entities[entity_id], "requires", technique_id)
        assert technique_id not in entities[entity_id]["commerce"]["cross_sell_ids"]


def test_regional_depth_preserves_family_and_community_boundaries(
    entities: dict[str, dict]
) -> None:
    qamishli = entities["region-syria-qamishli-family-transmission"]
    qamishli_text = json.dumps(qamishli, ensure_ascii=False).lower()
    assert "never lived in qamishli" in qamishli_text
    assert "not presented as direct observation" in qamishli_text

    jewish = entities["tradition-syrian-jewish-foodways-depth"]
    assert jewish["parent_id"] == "cuisine-syrian-regional"
    jewish_text = json.dumps(jewish, ensure_ascii=False).lower()
    assert "without replacing syrian cuisine" in jewish_text
    assert "not one uniform syrian jewish formula" in jewish_text

    raw_hazard = entities["technique-dermale-qawarma-jazira"]
    raw_text = json.dumps(raw_hazard, ensure_ascii=False).lower()
    assert "no public instructions" in raw_text
    assert "food-safety expert" in raw_text


def test_every_syrian_entity_is_fail_closed_and_non_commercial(
    syrian_entities: dict[str, dict]
) -> None:
    for entity_id, entity in syrian_entities.items():
        is_public_root = entity_id == "cuisine-syrian-regional"
        if is_public_root:
            assert entity["surface_class"] == "public_discovery"
            assert entity["index_policy"] == "noindex_until_longform_review"
            assert entity["seo"]["route_mode"] == "standalone"
            assert entity["publication"] == {
                "state": "approved_public",
                "public_api": True,
                "public_page": True,
                "search_index": False,
                "approved_at": "2026-08-06",
            }
            assert entity["review"]["status"] == "source_reviewed"
            assert entity["review"]["language_status"] == "reviewed_bilingual"
        else:
            assert entity["surface_class"] == "editorial_draft", entity_id
            assert entity["index_policy"] == "noindex_private", entity_id
            assert entity["seo"]["route_mode"] == "private", entity_id
            assert entity["publication"] == {
                "state": "private_preview",
                "public_api": False,
                "public_page": False,
                "search_index": False,
                "approved_at": "",
            }, entity_id
            assert entity["review"]["status"] == "research_draft", entity_id
            assert entity["review"]["language_status"] == "draft_bilingual", entity_id
        assert entity["commerce"]["state"] == "reference_only", entity_id
        assert entity["commerce"]["woo_product_code"] == "", entity_id
        assert entity["commerce"]["public_offer_allowed"] is False, entity_id
        if is_public_root:
            assert entity["taxonomy"]["public_category_path"]
            assert entity["taxonomy"]["public_attribute_keys"] == [
                "pa_region",
                "pa_community",
            ]
            assert entity["taxonomy"]["public_tags"]
            assert entity["visual"]["asset_state"] == "approved"
            assert entity["visual"]["rights_state"] == "cleared_generated"
            assert entity["visual"]["rights_receipt_digest"].startswith("sha256:")
        else:
            assert entity["taxonomy"]["public_category_path"] == [], entity_id
            assert entity["taxonomy"]["public_attribute_keys"] == [], entity_id
            assert entity["taxonomy"]["public_tags"] == [], entity_id
            assert entity["visual"]["asset_state"] == "rights_review_required", entity_id
            assert entity["visual"]["rights_state"] == "pending", entity_id
            assert entity["visual"]["rights_receipt_digest"] == "", entity_id
        assert "pa_region" in entity["taxonomy"]["attributes"], entity_id
        assert "pa_community" in entity["taxonomy"]["attributes"], entity_id
        assert entity["taxonomy"]["attributes"]["pa_region"], entity_id
        assert entity["taxonomy"]["attributes"]["pa_community"], entity_id
        assert entity["facts"], entity_id
        for fact in entity["facts"]:
            assert fact["source_ids"], (entity_id, fact["id"])
            if is_public_root and fact["id"] == "fact-syrian-cuisine-regional-mosaic":
                assert fact["public_safe"] is True
            else:
                assert fact["public_safe"] is False, (entity_id, fact["id"])
        for relation in entity["relations"]:
            assert relation["source_ids"], (entity_id, relation["id"])
            assert relation["public_safe"] is False, (entity_id, relation["id"])
        for note in entity["compliance"]:
            assert note["source_ids"], (entity_id, note["code"])
            assert note["public_safe"] is False, (entity_id, note["code"])


def test_canonical_cluster_and_parent_hierarchy(
    entities: dict[str, dict], syrian_entities: dict[str, dict]
) -> None:
    cuisine = entities["cuisine-syrian-regional"]
    assert cuisine["parent_id"] == "museum-culinary-science"
    assert cuisine["seo"]["page_role"] == "pillar"
    assert cuisine["seo"]["hub_entity_id"] == cuisine["id"]
    assert cuisine["seo"]["canonical_path"] == {
        "he": "/museum/syrian-culinary-science/",
        "en": "/en/museum/syrian-culinary-science/",
    }

    for region_id in EXPECTED_REGIONS:
        region = entities[region_id]
        assert region["parent_id"] == cuisine["id"]
        assert region["seo"]["breadcrumb_entity_ids"][:2] == [
            "museum-culinary-science",
            cuisine["id"],
        ]
        assert region["seo"]["canonical_path"]["he"].startswith(
            "/museum/syrian-culinary-science/"
        )

    for entity_id, entity in syrian_entities.items():
        assert entity["seo"]["cluster_id"] == "cluster-syrian-regional-cuisine"
        assert entity["seo"]["hub_entity_id"] == "cuisine-syrian-regional"
        assert entity["seo"]["canonical_path"]["he"].startswith(
            "/museum/syrian-culinary-science/"
        ), entity_id
        assert entity["seo"]["canonical_path"]["en"].startswith(
            "/en/museum/syrian-culinary-science/"
        ), entity_id


def test_cross_sell_links_resolve_without_creating_offers(
    entities: dict[str, dict]
) -> None:
    for entity_id in EXPECTED_DISHES | EXPECTED_PREPARATIONS:
        entity = entities[entity_id]
        if entity_id in {
            "dish-mansaf-mleihi",
            "dish-muhammara-syrian",
            "dish-halawet-al-jibn",
        }:
            assert entity["commerce"]["cross_sell_ids"] == []
            continue
        assert entity["commerce"]["cross_sell_ids"], entity_id
        for target_id in entity["commerce"]["cross_sell_ids"]:
            assert target_id in EXPECTED_INGREDIENTS, (entity_id, target_id)
            assert entities[target_id]["publication"]["public_page"] is False
            assert entities[target_id]["commerce"]["public_offer_allowed"] is False

    linked_techniques = 0
    for entity_id in EXPECTED_TECHNIQUES:
        targets = entities[entity_id]["commerce"]["cross_sell_ids"]
        if targets:
            linked_techniques += 1
        for target_id in targets:
            assert target_id in EXPECTED_INGREDIENTS, (entity_id, target_id)
            assert entities[target_id]["commerce"]["public_offer_allowed"] is False
    assert linked_techniques == 10
    assert entities["technique-syrian-mouneh"]["commerce"][
        "cross_sell_ids"
    ] == []


def test_regional_and_community_boundaries_remain_explicit(
    entities: dict[str, dict]
) -> None:
    halawet = entities["dish-halawet-al-jibn"]
    halawet_text = json.dumps(halawet["facts"], ensure_ascii=False).lower()
    assert "avs-nariman-homs" in halawet_text
    assert "avs-noor-hama" in halawet_text
    assert "without an origin verdict" in halawet_text

    qamishli = entities["dish-kubaybat-haqt-qamishli"]
    qamishli_text = json.dumps(qamishli, ensure_ascii=False).lower()
    assert "did not live in qamishli" in qamishli_text
    assert "second-generation family memory" in qamishli_text

    chicken = entities["dish-dajaj-mashwi-aleppan-jewish"]
    chicken_text = json.dumps(chicken["facts"], ensure_ascii=False).lower()
    assert "contemporary israeli adaptations" in chicken_text
    assert "not treated as evidence for a historical aleppo dish" in chicken_text

    cuisine_text = json.dumps(
        entities["cuisine-syrian-regional"]["facts"], ensure_ascii=False
    ).lower()
    assert "none is presented as a substitute for syrian cuisine as a whole" in cuisine_text

    jewish_dishes = {
        entity_id
        for entity_id in EXPECTED_DISHES
        if entities[entity_id]["taxonomy"]["attributes"]["pa_community"][0]
        in {"aleppan-jewish", "damascene-jewish"}
    }
    assert jewish_dishes == {
        "dish-matzah-kebab-damascene-jewish",
        "dish-kibbeh-hamda-aleppan-jewish",
        "dish-dajaj-mashwi-aleppan-jewish",
    }


def test_regional_source_scope_matches_homs_raqqa_deir_and_contested_owners(
    entities: dict[str, dict]
) -> None:
    labaniyyeh = entities["dish-kibbeh-labaniyyeh"]
    b_hamod = entities["dish-kibbeh-b-hamod"]
    for dish in (labaniyyeh, b_hamod):
        assert dish["parent_id"] == "region-syria-homs"
        assert dish["taxonomy"]["attributes"]["pa_region"] == ["homs"]
        assert "avs-nariman-homs" in dish["facts"][0]["source_ids"]
        assert "avs-razan-damascus" not in dish["facts"][0]["source_ids"]
    assert {
        "ingredient-syrian-jreesh",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-fresh-yogurt",
        "ingredient-syrian-rice",
    } == set(labaniyyeh["commerce"]["cross_sell_ids"])
    assert {
        "ingredient-syrian-jreesh",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-pomegranate-molasses",
        "ingredient-syrian-tomato-paste",
    } == set(b_hamod["commerce"]["cross_sell_ids"])

    thareed = entities["dish-thareed-raqqa-rural"]
    assert thareed["taxonomy"]["attributes"]["pa_region"] == [
        "raqqa-countryside"
    ]
    assert thareed["facts"][0]["source_ids"] == ["avs-rana-raqqa"]
    assert set(thareed["commerce"]["cross_sell_ids"]) == {
        "ingredient-raqqa-tannour-bread",
        "ingredient-syrian-red-meat",
    }
    optional_tomato = _one_relation(
        thareed, "references", "ingredient-syrian-tomato-paste"
    )
    assert optional_tomato["source_ids"] == ["avs-rana-raqqa"]
    assert "optional addition" in optional_tomato["note"]["en"]
    thareed_text = json.dumps(thareed, ensure_ascii=False).lower()
    assert "not evidence for a deir ez-zor version" in thareed_text
    assert "ingredient-syrian-chickpeas" not in thareed_text

    tannour = entities["technique-syrian-tannour-bread"]
    saj = entities["technique-syrian-saj-bread"]
    assert any(
        relation["target_id"] == "dish-thareed-raqqa-rural"
        for relation in tannour["relations"]
    )
    assert any(
        relation["target_id"] == "region-syria-euphrates-east"
        for relation in saj["relations"]
    )
    assert not any(
        relation["target_id"] == "dish-thareed-raqqa-rural"
        for relation in saj["relations"]
    )

    fawra = entities["dish-fawra-deir"]
    assert set(fawra["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-black-eyed-peas",
        "ingredient-syrian-kishk",
        "ingredient-syrian-onion",
        "ingredient-syrian-samn",
    }
    assert "ingredient-syrian-red-meat" not in fawra["commerce"]["cross_sell_ids"]

    halawet = entities["dish-halawet-al-jibn"]
    assert halawet["parent_id"] == "cuisine-syrian-regional"
    assert {
        relation["target_id"]
        for relation in halawet["relations"]
        if relation["type"] == "references"
    } == {"region-syria-homs", "region-syria-hama"}


def test_dish_requirement_sources_do_not_fan_out(
    entities: dict[str, dict]
) -> None:
    muhammara = entities["dish-muhammara-syrian"]
    assert muhammara["commerce"]["cross_sell_ids"] == []
    assert not any(
        relation["type"] == "requires" for relation in muhammara["relations"]
    )
    assert muhammara["facts"][0]["source_ids"] == [
        "aleppo-project-cuisine-2017",
        "avs-nariman-homs",
    ]
    assert _one_relation(
        muhammara, "references", "region-syria-aleppo"
    )["source_ids"] == ["aleppo-project-cuisine-2017"]
    assert _one_relation(
        muhammara, "references", "region-syria-homs"
    )["source_ids"] == ["avs-nariman-homs"]

    meshwiyyeh = entities["dish-kibbeh-meshwiyyeh"]
    assert meshwiyyeh["facts"][0]["source_ids"] == ["avs-mirvet-aleppo"]
    assert set(meshwiyyeh["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-bulgur",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-unspecified-nuts",
    }
    for ingredient_id in meshwiyyeh["commerce"]["cross_sell_ids"]:
        assert _one_relation(
            meshwiyyeh, "requires", ingredient_id
        )["source_ids"] == ["avs-mirvet-aleppo"]

    lahm = entities["dish-lahm-bi-ajin-syria"]
    assert lahm["facts"][0]["source_ids"] == ["jfs-lahm-bajin"]
    for ingredient_id in lahm["commerce"]["cross_sell_ids"]:
        assert _one_relation(lahm, "requires", ingredient_id)[
            "source_ids"
        ] == ["jfs-lahm-bajin"]

    labaniyyeh = entities["dish-kibbeh-labaniyyeh"]
    for ingredient_id in labaniyyeh["commerce"]["cross_sell_ids"]:
        relation = _one_relation(labaniyyeh, "requires", ingredient_id)
        assert relation["source_ids"] == ["avs-nariman-homs"]
        assert "yogurt-protein-structure-2023" not in relation["source_ids"]

    kebab = entities["dish-kebab-bil-karaz"]
    assert set(kebab["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-red-meat",
        "ingredient-syrian-sour-cherry",
    }
    assert "ingredient-syrian-samn" not in kebab["commerce"]["cross_sell_ids"]
    for ingredient_id in kebab["commerce"]["cross_sell_ids"]:
        relation = _one_relation(kebab, "requires", ingredient_id)
        assert relation["source_ids"] == ["aleppo-project-cuisine-2017"]
        assert "sour-cherry-organic-acids-2020" not in relation["source_ids"]

    yabra = entities["dish-yabraq-yebra"]
    assert set(yabra["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-grape-leaves",
        "ingredient-syrian-rice",
    }
    assert not any(
        relation["type"] == "requires"
        and relation["target_id"] == "ingredient-syrian-lemon"
        for relation in yabra["relations"]
    )
    for ingredient_id in yabra["commerce"]["cross_sell_ids"]:
        assert _one_relation(yabra, "requires", ingredient_id)["source_ids"] == [
            "avs-razan-damascus",
            "jfs-yebra-apricots",
        ]

    halawet = entities["dish-halawet-al-jibn"]
    assert halawet["commerce"]["cross_sell_ids"] == []
    assert not any(
        relation["type"] == "requires" for relation in halawet["relations"]
    )

    homs_halawet = entities["preparation-halawet-homs-cheese-semolina"]
    assert set(homs_halawet["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-cheese",
        "ingredient-syrian-semolina",
        "ingredient-syrian-qeshta-cream",
        "ingredient-syrian-sugar-syrup",
    }
    for ingredient_id in homs_halawet["commerce"]["cross_sell_ids"]:
        assert _one_relation(
            homs_halawet, "requires", ingredient_id
        )["source_ids"] == ["avs-nariman-homs"]

    hama_halawet = entities["preparation-halawet-hama-qeshta-pistachio"]
    assert set(hama_halawet["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-qeshta-cream",
        "ingredient-syrian-pistachios",
    }
    for ingredient_id in hama_halawet["commerce"]["cross_sell_ids"]:
        assert _one_relation(
            hama_halawet, "requires", ingredient_id
        )["source_ids"] == ["avs-noor-hama"]


def test_reverse_ingredient_edges_keep_regional_source_scope(
    entities: dict[str, dict]
) -> None:
    assert entities["ingredient-syrian-jreesh"]["taxonomy"]["attributes"][
        "pa_region"
    ] == ["homs-and-southern-syria"]
    jreesh_sources = entities["ingredient-syrian-jreesh"]["facts"][0][
        "source_ids"
    ]
    assert "avs-nariman-homs" in jreesh_sources

    yogurt_edge = _one_relation(
        entities["ingredient-syrian-fresh-yogurt"],
        "used_in",
        "dish-kibbeh-labaniyyeh",
    )
    assert yogurt_edge["source_ids"] == ["avs-nariman-homs"]

    for ingredient_id in {
        "ingredient-syrian-red-pepper-paste",
        "ingredient-syrian-pomegranate-molasses",
        "ingredient-syrian-walnuts",
    }:
        assert not any(
            relation["type"] == "used_in"
            and relation["target_id"] == "dish-muhammara-syrian"
            for relation in entities[ingredient_id]["relations"]
        )

    for ingredient_id in {
        "ingredient-syrian-bulgur",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-unspecified-nuts",
    }:
        edge = _one_relation(
            entities[ingredient_id], "used_in", "dish-kibbeh-meshwiyyeh"
        )
        assert edge["source_ids"] == ["avs-mirvet-aleppo"]

    green_peas = _one_relation(
        entities["ingredient-syrian-green-peas"],
        "used_in",
        "dish-ouzi-damascene",
    )
    assert green_peas["source_ids"] == ["avs-razan-damascus"]
    assert not any(
        relation["type"] == "used_in"
        and relation["target_id"] == "dish-ouzi-damascene"
        for relation in entities["ingredient-syrian-chickpeas"]["relations"]
    )

    generic_nuts_mleihi = _one_relation(
        entities["ingredient-syrian-unspecified-nuts"],
        "used_in",
        "preparation-mleihi-suwayda-fresh-yogurt",
    )
    assert generic_nuts_mleihi["source_ids"] == ["avs-ghaimana-suwayda"]

    pistachio_halawet = _one_relation(
        entities["ingredient-syrian-pistachios"],
        "used_in",
        "preparation-halawet-hama-qeshta-pistachio",
    )
    assert pistachio_halawet["source_ids"] == ["avs-noor-hama"]


def test_technique_edges_use_the_regional_source_that_supports_the_link(
    entities: dict[str, dict]
) -> None:
    for technique_id in {
        "technique-syrian-bulgur-hydration",
        "technique-syrian-kibbeh-shell-shaping",
        "technique-syrian-kibbeh-cooking",
    }:
        technique = entities[technique_id]
        assert _one_relation(
            technique, "used_in", "dish-kibbeh-meshwiyyeh"
        )["source_ids"] == ["avs-mirvet-aleppo"]
        for ingredient_id in technique["commerce"]["cross_sell_ids"]:
            relation = _one_relation(technique, "requires", ingredient_id)
            assert relation["source_ids"] == ["avs-mirvet-aleppo"]
            assert relation["evidence_class"] == "official_source"

    yogurt = entities["technique-syrian-yogurt-sauce-stability"]
    assert yogurt["taxonomy"]["attributes"]["pa_region"] == ["homs"]
    yogurt_target = _one_relation(
        yogurt, "used_in", "dish-kibbeh-labaniyyeh"
    )
    yogurt_ingredient = _one_relation(
        yogurt, "requires", "ingredient-syrian-fresh-yogurt"
    )
    for relation in (yogurt_target, yogurt_ingredient):
        assert relation["source_ids"] == ["avs-nariman-homs"]
        assert relation["evidence_class"] == "official_source"

    saj = _one_relation(
        entities["technique-syrian-saj-bread"],
        "used_in",
        "region-syria-euphrates-east",
    )
    assert saj["source_ids"] == ["avs-buthaina-east"]
    assert saj["evidence_class"] == "official_source"

    tannour_entity = entities["technique-syrian-tannour-bread"]
    for relation_type, target_id in (
        ("used_in", "dish-thareed-raqqa-rural"),
        ("requires", "ingredient-raqqa-tannour-bread"),
    ):
        relation = _one_relation(tannour_entity, relation_type, target_id)
        assert relation["source_ids"] == ["avs-rana-raqqa"]
        assert relation["evidence_class"] == "official_source"

    sour_fruit = entities["technique-syrian-sour-fruit-braising"]
    assert set(sour_fruit["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-sour-cherry",
        "ingredient-syrian-quince",
    }
    assert not any(
        relation["type"] == "requires" for relation in sour_fruit["relations"]
    )
    for dish_id, ingredient_id in {
        "dish-kebab-bil-karaz": "ingredient-syrian-sour-cherry",
        "dish-kibbeh-safarjaliyyeh": "ingredient-syrian-quince",
    }.items():
        assert _one_relation(sour_fruit, "used_in", dish_id)["source_ids"] == [
            "aleppo-project-cuisine-2017"
        ]
        assert _one_relation(
            sour_fruit, "references", ingredient_id
        )["source_ids"] == ["aleppo-project-cuisine-2017"]
    relation_targets = {
        relation["target_id"] for relation in sour_fruit["relations"]
    }
    assert "ingredient-syrian-pomegranate-molasses" not in relation_targets
    assert "ingredient-syrian-quince" in relation_targets
    assert "dish-kibbeh-safarjaliyyeh" in relation_targets

    mouneh_entity = entities["technique-syrian-mouneh"]
    assert mouneh_entity["facts"][0]["dimension"] == "cultural"
    assert mouneh_entity["facts"][0]["evidence_class"] == "official_source"
    assert "fda-water-activity" not in mouneh_entity["facts"][0]["source_ids"]
    assert mouneh_entity["facts"][1]["source_ids"] == ["fda-water-activity"]
    assert mouneh_entity["facts"][1]["evidence_class"] == "regulatory_standard"

    dried_dairy = entities["technique-syrian-cultured-dried-dairy"]
    assert dried_dairy["facts"][0]["dimension"] == "cultural"
    assert dried_dairy["facts"][0]["evidence_class"] == "official_source"
    assert "fda-water-activity" not in dried_dairy["facts"][0]["source_ids"]
    assert dried_dairy["facts"][1]["source_ids"] == ["fda-water-activity"]
    assert dried_dairy["facts"][1]["evidence_class"] == "regulatory_standard"

    onion = entities["technique-syrian-onion-browning-sayadiyah"]
    assert set(onion["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-onion",
        "ingredient-syrian-rice",
        "ingredient-syrian-olive-oil",
    }
    for ingredient_id in onion["commerce"]["cross_sell_ids"]:
        assert _one_relation(onion, "requires", ingredient_id)[
            "source_ids"
        ] == ["avs-zainab-coast"]

    mouneh = _one_relation(
        entities["technique-syrian-mouneh"],
        "used_in",
        "cuisine-syrian-regional",
    )
    assert mouneh["source_ids"] == [
        "unesco-syrian-ich-survey-2017",
        "avs-nariman-homs",
    ]
    assert "fda-water-activity" not in mouneh["source_ids"]


def test_community_dishes_keep_only_source_documented_requirements(
    entities: dict[str, dict]
) -> None:
    lahm = entities["dish-lahm-bi-ajin-syria"]
    assert "ingredient-syrian-samn" not in lahm["commerce"]["cross_sell_ids"]
    assert lahm["facts"][0]["source_ids"] == ["jfs-lahm-bajin"]
    matzah = entities["dish-matzah-kebab-damascene-jewish"]
    assert set(matzah["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-matzah",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-lemon",
    }
    assert matzah["facts"][0]["source_ids"] == ["foodish-matzah-kebab"]
    hamda = entities["dish-kibbeh-hamda-aleppan-jewish"]
    assert set(hamda["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-rice",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-lemon",
    }
    assert "ingredient-syrian-bulgur" not in hamda["commerce"]["cross_sell_ids"]
    assert hamda["facts"][0]["source_ids"] == ["jfs-kibbeh-hamda"]
    dajaj = entities["dish-dajaj-mashwi-aleppan-jewish"]
    assert set(dajaj["commerce"]["cross_sell_ids"]) == {
        "ingredient-syrian-whole-chicken",
        "ingredient-syrian-rice",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-olive-oil",
    }
    assert dajaj["facts"][0]["source_ids"] == ["foodish-dajaj-mashwi"]
    dajaj_prompt = dajaj["visual"]["prompt_en"].lower()
    assert "no lemon" in dajaj_prompt
    assert "sumac" in dajaj_prompt


def test_shared_yabraq_owner_does_not_structurally_choose_one_community(
    entities: dict[str, dict]
) -> None:
    dish = entities["dish-yabraq-yebra"]
    grape_leaves = entities["ingredient-syrian-grape-leaves"]
    technique = entities["technique-syrian-stuffing-grape-leaves"]
    aleppan_tradition = entities["tradition-aleppan-jewish-foodways"]
    damascene_tradition = entities["tradition-damascene-jewish-foodways"]

    assert dish["parent_id"] == "cuisine-syrian-regional"
    assert dish["taxonomy"]["attributes"]["pa_region"] == [
        "damascus-and-aleppo-diaspora"
    ]
    assert grape_leaves["taxonomy"]["attributes"]["pa_region"] == [
        "damascus-and-aleppo-diaspora"
    ]
    assert technique["taxonomy"]["attributes"]["pa_region"] == [
        "damascus-and-aleppo-diaspora"
    ]
    assert "jfs-yebra-apricots" in aleppan_tradition["facts"][0]["source_ids"]
    assert "jfs-yebra-apricots" not in damascene_tradition["facts"][0]["source_ids"]
    assert _one_relation(
        aleppan_tradition,
        "references",
        "dish-kibbeh-hamda-aleppan-jewish",
    )["source_ids"] == ["jfs-kibbeh-hamda"]
    assert _one_relation(
        damascene_tradition,
        "references",
        "dish-matzah-kebab-damascene-jewish",
    )["source_ids"] == ["foodish-matzah-kebab"]


def test_mleihi_is_one_dish_with_two_non_merged_preparations(
    entities: dict[str, dict]
) -> None:
    dish = entities["dish-mansaf-mleihi"]
    suwayda = entities["preparation-mleihi-suwayda-fresh-yogurt"]
    hauran = entities["preparation-mleihi-hauran-jameed"]
    assert suwayda["parent_id"] == dish["id"]
    assert hauran["parent_id"] == dish["id"]
    assert suwayda["taxonomy"]["attributes"]["pa_region"] == ["suwayda"]
    assert hauran["taxonomy"]["attributes"]["pa_region"] == ["hauran"]
    assert "ingredient-syrian-fresh-yogurt" in suwayda["commerce"]["cross_sell_ids"]
    assert "ingredient-syrian-unspecified-nuts" in suwayda["commerce"]["cross_sell_ids"]
    assert "ingredient-syrian-walnuts" not in suwayda["commerce"]["cross_sell_ids"]
    assert _one_relation(
        suwayda, "requires", "ingredient-syrian-unspecified-nuts"
    )["source_ids"] == ["avs-ghaimana-suwayda"]
    assert "ingredient-syrian-jameed" in hauran["commerce"]["cross_sell_ids"]
    assert "ingredient-syrian-bulgur" in hauran["commerce"]["cross_sell_ids"]
    assert suwayda["seo"]["schema_type"] == "Article"
    assert hauran["seo"]["schema_type"] == "Article"
    assert suwayda["review"]["culinary_test_status"] == "pending"
    assert hauran["review"]["culinary_test_status"] == "pending"
    assert {
        "ingredient-syrian-kishk",
        "ingredient-syrian-jameed",
        "ingredient-syrian-higet",
        "ingredient-syrian-haqt",
    }.issubset(entities)


def test_yabraq_and_yebra_are_one_dish_with_two_non_merged_preparations(
    entities: dict[str, dict]
) -> None:
    dish = entities["dish-yabraq-yebra"]
    damascene = entities["preparation-yabraq-damascene"]
    aleppan_jewish = entities["preparation-yebra-aleppan-jewish-apricot"]

    assert damascene["parent_id"] == dish["id"]
    assert aleppan_jewish["parent_id"] == dish["id"]
    assert damascene["taxonomy"]["attributes"]["pa_region"] == ["damascus"]
    assert damascene["taxonomy"]["attributes"]["pa_community"] == [
        "syrian-multi-community"
    ]
    assert aleppan_jewish["taxonomy"]["attributes"]["pa_region"] == ["aleppo"]
    assert aleppan_jewish["taxonomy"]["attributes"]["pa_community"] == [
        "aleppan-jewish"
    ]

    assert {
        "ingredient-syrian-grape-leaves",
        "ingredient-syrian-rice",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-lemon",
        "ingredient-syrian-garlic",
    }.issubset(damascene["commerce"]["cross_sell_ids"])
    assert {
        "ingredient-syrian-grape-leaves",
        "ingredient-syrian-rice",
        "ingredient-syrian-red-meat",
        "ingredient-syrian-dried-apricot",
        "ingredient-syrian-garlic",
        "ingredient-syrian-allspice",
        "ingredient-syrian-dried-mint",
        "ingredient-aleppan-ou-souring-concentrate",
    }.issubset(aleppan_jewish["commerce"]["cross_sell_ids"])

    ou = entities["ingredient-aleppan-ou-souring-concentrate"]
    assert ou["id"] != "ingredient-syrian-tamarind"
    assert ou["publication"]["public_page"] is False
    assert ou["commerce"]["public_offer_allowed"] is False
    ou_text = json.dumps(aleppan_jewish["facts"], ensure_ascii=False).lower()
    assert "does not define its composition" in ou_text
    assert "remains held pending additional identity evidence" in ou_text

    for preparation in (damascene, aleppan_jewish):
        assert preparation["seo"]["schema_type"] == "Article"
        assert preparation["review"]["culinary_test_status"] == "pending"
        assert preparation["publication"]["public_page"] is False
        assert preparation["commerce"]["public_offer_allowed"] is False


def test_raw_kibbeh_is_historical_only_and_no_syrian_recipe_is_exposed(
    entities: dict[str, dict], syrian_entities: dict[str, dict]
) -> None:
    cuisine = entities["cuisine-syrian-regional"]
    raw_fact = next(
        fact
        for fact in cuisine["facts"]
        if fact["id"] == "fact-raw-kibbeh-historical-only-boundary"
    )
    assert raw_fact["source_ids"] == [
        "aleppo-project-cuisine-2017",
        "cdc-raw-kibbeh-salmonella-2013",
    ]
    assert raw_fact["public_safe"] is False
    assert "must not publish a recipe" in raw_fact["statement"]["en"].lower()
    technique = entities["technique-syrian-kibbeh-cooking"]
    assert any(note["code"] == "raw-kibbeh-prohibited" for note in technique["compliance"])
    for entity in syrian_entities.values():
        if entity["type"] in {"dish", "preparation"}:
            assert entity["seo"]["schema_type"] != "Recipe", entity["id"]
            assert entity["publication"]["public_page"] is False, entity["id"]


def test_market_evidence_is_dated_scoped_and_linked(
    entities: dict[str, dict]
) -> None:
    cases = {
        "listing-sugat-freekeh-500g-big-dabach-20260806": (
            "ingredient-syrian-freekeh",
            "big-dabach-sugat-freekeh-500g-listing-2026",
            10.9,
        ),
        "listing-keter-harimon-pomegranate-concentrate-250ml-tamar-hst-20260806": (
            "ingredient-pomegranate-concentrate",
            "tamar-hst-keter-harimon-pomegranate-concentrate-250ml-listing-2026",
            29.9,
        ),
        "listing-tamar-bakfar-pure-ground-sumac-100g-indexed-20260806": (
            "ingredient-syrian-sumac",
            "tamar-bakfar-pure-ground-sumac-100g-indexed-2026",
            11.0,
        ),
    }
    for observation_id, (ingredient_id, source_id, price) in cases.items():
        observation = entities[observation_id]
        assert observation["parent_id"] == ingredient_id
        assert observation["facts"][0]["source_ids"] == [source_id]
        assert observation["facts"][0]["evidence_class"] == "market_observation"
        assert observation["facts"][0]["public_safe"] is False
        measurement = observation["facts"][0]["measurement"]
        assert measurement["value"] == price
        assert measurement["currency"] == "ILS"
        assert measurement["observed_at"] == "2026-08-06T22:45:00+03:00"
        assert observation_id in entities[ingredient_id]["commerce"][
            "business_model"
        ]["observation_entity_ids"]
        assert observation["commerce"]["public_offer_allowed"] is False

    pomegranate = entities[
        "listing-keter-harimon-pomegranate-concentrate-250ml-tamar-hst-20260806"
    ]
    assert "not establish identity with traditional dibs rumman" in pomegranate[
        "summary"
    ]["en"]
    comparison = _one_relation(
        pomegranate, "references", "ingredient-syrian-pomegranate-molasses"
    )
    assert "comparison only" in comparison["note"]["en"]
    assert pomegranate["id"] not in entities[
        "ingredient-syrian-pomegranate-molasses"
    ]["commerce"]["business_model"]["observation_entity_ids"]
    assert pomegranate["id"] in entities["ingredient-pomegranate-concentrate"][
        "commerce"
    ]["business_model"]["observation_entity_ids"]

    sumac = entities[
        "listing-tamar-bakfar-pure-ground-sumac-100g-indexed-20260806"
    ]
    assert sumac["type"] == "market_observation"
    assert sumac["facts"][0]["measurement"]["comparability"] == "non_comparable"
    sumac_text = json.dumps(sumac, ensure_ascii=False).lower()
    assert "historical indexed" in sumac_text
    assert "parked-domain sale page" in sumac_text
    assert "no current seller availability" in sumac_text


def test_exact_source_ledger_urls_are_retained(registry: dict) -> None:
    expected = {
        "unesco-syrian-ich-survey-2017": "https://ich.unesco.org/doc/src/38275-EN.pdf",
        "avs-heart-to-hearth": "https://agricultural-voices.sussex.ac.uk/?page_id=1006",
        "avs-razan-damascus": "https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Razans-Story.pdf",
        "avs-mirvet-aleppo": "https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Mirvets-Story.pdf",
        "avs-nariman-homs": "https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Narimans-Story.pdf",
        "avs-noor-hama": "https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Noors-Story.pdf",
        "avs-rana-raqqa": "https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Ranas-Story.pdf",
        "avs-buthaina-east": "https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Buthainas-Story.pdf",
        "aleppo-project-cuisine-2017": "https://www.thealeppoproject.com/wp-content/uploads/2017/08/Cuisine-Final.pdf",
        "jfs-kibbeh-hamda": "https://www.jewishfoodsociety.org/stories/the-syrian-passover-soup-that-came-to-brooklyn",
        "jfs-yebra-apricots": "https://www.jewishfoodsociety.org/recipes/yebra-stuffed-grape-leaves-with-apricots",
        "foodish-matzah-kebab": "https://foodish.anumuseum.org.il/en/recipe/matzah-kebab/",
        "bulgur-hydration-2025": "https://pubmed.ncbi.nlm.nih.gov/41273208/",
        "cdc-raw-kibbeh-salmonella-2013": "https://archive.cdc.gov/www_cdc_gov/salmonella/typhimurium-01-13/index.html",
        "big-dabach-sugat-freekeh-500g-listing-2026": "https://www.bigdabach.co.il/?catalogProduct=6279611",
        "tamar-hst-keter-harimon-pomegranate-concentrate-250ml-listing-2026": "https://www.tamar-hst.co.il/product-details/209856/%D7%A8%D7%9B%D7%96_%D7%A8%D7%99%D7%9E%D7%95%D7%9F",
        "tamar-bakfar-pure-ground-sumac-100g-indexed-2026": "https://tamarbakfar.co.il/product/%D7%A1%D7%95%D7%9E%D7%A7-%D7%98%D7%97%D7%95%D7%9F-%D7%98%D7%94%D7%95%D7%A8/",
    }
    for source_id, url in expected.items():
        assert registry["sources"][source_id]["url"] == url


def test_one_syrian_gate_is_public_and_japanese_public_set_is_unchanged(
    registry: dict,
) -> None:
    public_ids = {
        entity["id"]
        for entity in registry["entities"]
        if entity["publication"]["public_api"]
        or entity["publication"]["public_page"]
    }
    assert public_ids == EXPECTED_PUBLIC
    assert public_ids - {"cuisine-syrian-regional"} == EXPECTED_PUBLIC_JAPANESE
    assert len(registry["collections"]) == 1
    assert registry["collections"][0]["key"] == "japanese-foundations-lab"
    assert registry["collections"][0]["public_projection"]["enabled"] is True


def test_public_syrian_trust_copy_is_written_for_readers(
    entities: dict[str, dict]
) -> None:
    trust = entities["cuisine-syrian-regional"]["trust"]
    public_copy = json.dumps(trust, ensure_ascii=False).lower()
    for internal_phrase in (
        "evidence class",
        "product or lot measurement",
        "triggers review",
        "סוג ראיה",
        "מדידת מוצר או אצווה",
        "מפעילים בדיקה מחודשת",
    ):
        assert internal_phrase not in public_copy
    assert "source you can open and read" in trust["research_method"]["en"]
    assert "contact page" in trust["next_review_trigger"]["en"]


def test_syrian_files_have_no_em_dash() -> None:
    for path in (SYRIAN_LEAF, SYRIAN_DEPTH_LEAF, SCIENCE_DATA, Path(__file__)):
        assert "\N{EM DASH}" not in path.read_text(encoding="utf-8"), path
