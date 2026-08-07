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
    / "syrian-regional-expansion-west.php",
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "syrian-regional-expansion-east-south.php",
    PLUGIN
    / "data"
    / "culinary-science"
    / "cuisines"
    / "syrian-community-institutions-expansion.php",
)

EXPECTED_MODULE_COUNTS = (30, 31, 25)
EXPECTED_NEW_TYPES = {
    "topic_hub": 4,
    "dish": 21,
    "ingredient": 14,
    "technique": 13,
    "tradition": 12,
    "guide": 7,
    "preparation": 1,
    "culinary_institution": 9,
    "market": 1,
    "restaurant": 4,
}
EXPECTED_SYRIAN_TYPES = {
    "cuisine": 1,
    "topic_hub": 25,
    "dish": 77,
    "ingredient": 69,
    "technique": 30,
    "tradition": 29,
    "preparation": 16,
    "guide": 10,
    "culinary_institution": 11,
    "market": 5,
    "restaurant": 6,
    "retail_listing": 2,
    "market_observation": 1,
}
HELD_IDS = {
    "ingredient-loof-arum-qadmus-held",
    "technique-qadmus-cooked-arum-preservation-held",
    "guide-al-manzala-palmyra-identity-held",
    "guide-halqoum-haurani-identity-held",
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def payload() -> dict:
    class_path = _php_path(SCIENCE_CLASS)
    data_path = _php_path(SCIENCE_DATA)
    plugin_path = _php_path(PLUGIN) + "/"
    module_paths = ",\n".join(
        f"    require '{_php_path(module)}'" for module in MODULES
    )
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
$modules = array(
{module_paths}
);
echo json_encode(array(
    'registry' => $registry,
    'modules' => $modules,
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


def test_release_registry_and_module_membership_are_exact(payload: dict) -> None:
    registry = payload["registry"]
    modules = payload["modules"]
    assert registry["version"] == "culinary-science-2026.08.07.v18"
    assert len(registry["entities"]) == 672
    assert tuple(len(module["entities"]) for module in modules) == (
        EXPECTED_MODULE_COUNTS
    )

    module_sets = [set(module["private_entity_ids"]) for module in modules]
    assert all(
        len(entity_ids) == expected
        for entity_ids, expected in zip(
            module_sets, EXPECTED_MODULE_COUNTS, strict=True
        )
    )
    assert not (module_sets[0] & module_sets[1])
    assert not (module_sets[0] & module_sets[2])
    assert not (module_sets[1] & module_sets[2])

    new_ids = set().union(*module_sets)
    registry_ids = {entity["id"] for entity in registry["entities"]}
    assert len(new_ids) == 86
    assert new_ids <= registry_ids
    new_entities = {
        entity["id"]: entity
        for entity in registry["entities"]
        if entity["id"] in new_ids
    }
    assert Counter(entity["type"] for entity in new_entities.values()) == (
        EXPECTED_NEW_TYPES
    )


def test_complete_syrian_cluster_has_exact_282_entity_shape(payload: dict) -> None:
    syrian = [
        entity
        for entity in payload["registry"]["entities"]
        if entity["seo"]["cluster_id"] == "cluster-syrian-regional-cuisine"
    ]
    assert len(syrian) == 282
    assert Counter(entity["type"] for entity in syrian) == EXPECTED_SYRIAN_TYPES


def test_all_86_new_entities_are_private_noindex_and_noncommercial(
    payload: dict,
) -> None:
    modules = payload["modules"]
    new_ids = set().union(
        *(set(module["private_entity_ids"]) for module in modules)
    )
    entities = {
        entity["id"]: entity
        for entity in payload["registry"]["entities"]
        if entity["id"] in new_ids
    }
    for entity_id, entity in entities.items():
        assert entity["surface_class"] == "editorial_draft", entity_id
        assert entity["index_policy"] == "noindex_private", entity_id
        assert entity["publication"]["public_api"] is False, entity_id
        assert entity["publication"]["public_page"] is False, entity_id
        assert entity["publication"]["search_index"] is False, entity_id
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
        assert all(fact["public_safe"] is False for fact in entity["facts"])


def test_four_unresolved_identites_remain_held_fail_closed(payload: dict) -> None:
    entities = {
        entity["id"]: entity for entity in payload["registry"]["entities"]
    }
    assert HELD_IDS <= set(entities)
    for entity_id in HELD_IDS:
        entity = entities[entity_id]
        text = " ".join(
            [
                entity["summary"]["en"],
                entity["trust"]["commercial_purpose"]["en"],
                entity["trust"]["next_review_trigger"]["en"],
                *(
                    rule["note"]["en"]
                    for rule in entity["compliance"]
                ),
            ]
        ).lower()
        assert "held" in text or "fail-closed" in text, entity_id
        assert entity["commerce"]["public_offer_allowed"] is False
        assert entity["publication"]["public_page"] is False


def test_release_preserves_public_science_and_visual_prompt_boundaries(
    payload: dict,
) -> None:
    registry = payload["registry"]
    public_entities = [
        entity for entity in registry["entities"] if entity["publication"]["public_page"]
    ]
    public_owners = {
        entity["seo"]["owner_entity_id"] for entity in public_entities
    }
    assert len(public_entities) == 27
    assert len(public_owners) == 19

    modules = payload["modules"]
    new_ids = set().union(
        *(set(module["private_entity_ids"]) for module in modules)
    )
    new_entities = [
        entity for entity in registry["entities"] if entity["id"] in new_ids
    ]
    prompts = [entity["visual"]["prompt_en"] for entity in new_entities]
    assert len(prompts) == 86
    assert len(set(prompts)) == 86
    assert all(prompt.strip() for prompt in prompts)
    for module_path in MODULES:
        assert "\u2014" not in module_path.read_text(encoding="utf-8")
