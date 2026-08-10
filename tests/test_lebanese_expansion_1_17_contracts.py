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
MODULES = (
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "lebanese-regional-expansion-coast-north.php",
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "lebanese-regional-expansion-bekaa-south-community.php",
)

EXPECTED_MODULE_COUNTS = (61, 60)
EXPECTED_MODULE_SOURCE_COUNTS = (31, 46)
EXPECTED_NEW_TYPES = {
    "topic_hub": 12,
    "dish": 31,
    "ingredient": 15,
    "molecule": 3,
    "reaction": 4,
    "technique": 14,
    "equipment": 6,
    "tradition": 18,
    "market": 4,
    "culinary_institution": 10,
    "restaurant": 3,
    "guide": 1,
}
EXPECTED_LEBANESE_TYPES = {
    "cuisine": 1,
    "topic_hub": 25,
    "dish": 58,
    "preparation": 2,
    "ingredient": 23,
    "molecule": 3,
    "reaction": 4,
    "technique": 19,
    "equipment": 6,
    "tradition": 27,
    "culinary_institution": 15,
    "market": 6,
    "restaurant": 6,
    "compliance_rule": 1,
    "retail_listing": 6,
    "guide": 1,
}
HELD_IDS = {
    "dish-kaak-orchali-beirut-context",
    "dish-jazarieh-tripoli-context",
    "market-halba-produce-system-historical-context",
    "restaurant-akra-tripoli-breakfast-benchmark",
    "restaurant-aal-baher-byblos-seafood-benchmark",
    "dish-kebbit-el-arous-baalbek-held",
    "dish-pumpkin-jam-west-bekaa-held",
    "dish-frakeh-south-lebanon-held",
    "ingredient-zahle-arak-context",
    "technique-zahle-arak-distillation-held",
    "technique-west-bekaa-pumpkin-lime-firming-held",
    "technique-baalbek-kebbeh-forming-raw-held",
}
EXPECTED_SAFETY_CODES = {
    "alcohol-age-gate",
    "allergen-gluten",
    "allergen-nuts",
    "allergen-sesame",
    "cold-chain-control",
    "distillation-fire-safety",
    "fish-food-safety",
    "food-grade-calcium-oxide",
    "open-fire-safety",
    "raw-meat-food-safety",
    "traditional-dairy-food-safety",
    "wild-plant-identification",
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
    'foundation_sources' => $c99_lebanese_foundations_module['sources'],
    'modules' => array(
        $c99_lebanese_coast_north_module,
        $c99_lebanese_bekaa_south_community_module,
    ),
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


def _new_entities(payload: dict) -> dict[str, dict]:
    new_ids = set().union(
        *(set(module["private_entity_ids"]) for module in payload["modules"])
    )
    return {
        entity["id"]: entity
        for entity in payload["registry"]["entities"]
        if entity["id"] in new_ids
    }


def test_release_registry_module_and_source_counts_are_exact(payload: dict) -> None:
    registry = payload["registry"]
    modules = payload["modules"]
    assert registry["version"] == "culinary-science-2026.08.08.v20"
    assert len(registry["entities"]) == 672
    assert len(registry["sources"]) == 375
    assert tuple(len(module["entities"]) for module in modules) == (
        EXPECTED_MODULE_COUNTS
    )
    assert tuple(len(module["sources"]) for module in modules) == (
        EXPECTED_MODULE_SOURCE_COUNTS
    )
    assert all(
        module["version"] == "culinary-science-2026.08.08.v20"
        for module in modules
    )

    module_id_sets = [set(module["private_entity_ids"]) for module in modules]
    module_slug_sets = [
        {entity["slug"] for entity in module["entities"]} for module in modules
    ]
    assert not (module_id_sets[0] & module_id_sets[1])
    assert not (module_slug_sets[0] & module_slug_sets[1])
    assert len(set().union(*module_id_sets)) == 121
    assert Counter(entity["type"] for entity in _new_entities(payload).values()) == (
        EXPECTED_NEW_TYPES
    )


def test_new_sources_are_disjoint_used_and_url_unique(payload: dict) -> None:
    foundation = payload["foundation_sources"]
    modules = payload["modules"]
    source_sets = [set(module["sources"]) for module in modules]
    assert not (source_sets[0] & source_sets[1])
    assert not (set(foundation) & set().union(*source_sets))

    all_source_rows = [
        source
        for module in modules
        for source in module["sources"].values()
    ]
    urls = [source["url"] for source in all_source_rows]
    assert len(urls) == 77
    assert len(set(urls)) == 77

    used_source_ids: set[str] = set()
    for entity in _new_entities(payload).values():
        for fact in entity["facts"]:
            used_source_ids.update(fact["source_ids"])
        for relation in entity["relations"]:
            used_source_ids.update(relation["source_ids"])
        for note in entity["compliance"]:
            used_source_ids.update(note["source_ids"])
    assert set().union(*source_sets) <= used_source_ids


def test_complete_lebanese_cluster_has_exact_203_entity_shape(payload: dict) -> None:
    lebanese = [
        entity
        for entity in payload["registry"]["entities"]
        if entity["seo"]["cluster_id"] == "cluster-lebanese-regional-cuisine"
    ]
    assert len(lebanese) == 203
    assert Counter(entity["type"] for entity in lebanese) == EXPECTED_LEBANESE_TYPES


def test_all_new_entities_are_private_noncommercial_and_trade_gated(
    payload: dict,
) -> None:
    entities = _new_entities(payload)
    registry_ids = {entity["id"] for entity in payload["registry"]["entities"]}
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
        assert entity["commerce"]["business_model"]["observation_entity_ids"] == (
            []
        ), entity_id
        assert all(fact["public_safe"] is False for fact in entity["facts"])
        assert all(relation["public_safe"] is False for relation in entity["relations"])
        assert entity["parent_id"] in registry_ids, entity_id
        trade_relations = [
            relation
            for relation in entity["relations"]
            if relation["target_id"] == "compliance-lebanon-trade-israel-2026"
        ]
        assert len(trade_relations) == 1, entity_id
        assert trade_relations[0]["type"] == "references", entity_id
        assert trade_relations[0]["source_ids"] == [
            "israel-enemy-states-trade-2026"
        ], entity_id


def test_every_new_parent_chain_resolves_to_the_lebanese_root(payload: dict) -> None:
    entities = {
        entity["id"]: entity for entity in payload["registry"]["entities"]
    }
    for entity_id in _new_entities(payload):
        cursor = entity_id
        seen: set[str] = set()
        while cursor != "cuisine-lebanese-regional":
            assert cursor not in seen, (entity_id, cursor)
            seen.add(cursor)
            parent_id = entities[cursor]["parent_id"]
            assert parent_id in entities, (entity_id, cursor, parent_id)
            cursor = parent_id


def test_exact_held_set_and_safety_controls_are_retained(payload: dict) -> None:
    entities = _new_entities(payload)
    assert HELD_IDS <= set(entities)
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
    assert EXPECTED_SAFETY_CODES <= safety_codes


def test_only_reviewed_lebanese_gateway_is_added_to_public_boundary(payload: dict) -> None:
    registry = payload["registry"]
    public_entities = [
        entity for entity in registry["entities"] if entity["publication"]["public_page"]
    ]
    assert len(public_entities) == 27
    assert len({entity["seo"]["owner_entity_id"] for entity in public_entities}) == 19
    public_ids = {entity["id"] for entity in public_entities}
    assert "cuisine-lebanese-regional" in public_ids
    assert not (set(_new_entities(payload)) & public_ids)

    prompts = [
        entity["visual"]["prompt_en"] for entity in _new_entities(payload).values()
    ]
    assert len(prompts) == 121
    assert len(set(prompts)) == 121
    assert all(prompt.strip() for prompt in prompts)
    for module_path in MODULES:
        assert "\u2014" not in module_path.read_text(encoding="utf-8")
