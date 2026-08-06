from __future__ import annotations

import json
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def v5_payload() -> dict:
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{_php_path(PLUGIN)}/');
define('COMPLETE99_PLATFORM_URL', 'https://complete99.test/wp-content/plugins/complete99-platform/');
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
    public function get_error_data() {{ return $this->data; }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
function home_url($path = '') {{ return 'https://complete99.test' . $path; }}
require '{_php_path(SCIENCE_CLASS)}';
$registry = require '{_php_path(SCIENCE_DATA)}';

function c99_result($candidate) {{
    $result = Complete99_Culinary_Science::validate_registry($candidate);
    if (true === $result) {{
        return array('valid' => true, 'path' => '');
    }}
    $data = $result->get_error_data();
    return array(
        'valid' => false,
        'path' => is_array($data) && isset($data['path']) ? $data['path'] : '',
    );
}}

function c99_entity_offset($registry, $entity_id) {{
    foreach ($registry['entities'] as $offset => $entity) {{
        if ($entity_id === $entity['id']) {{
            return $offset;
        }}
    }}
    throw new RuntimeException('missing-test-entity');
}}

function c99_refresh_membership_receipt(&$candidate) {{
    $collection = &$candidate['collections'][0];
    $tokens = array();
    foreach ($collection['navigation']['group_order'] as $group_id) {{
        foreach ($collection['navigation']['member_ids_by_group'][$group_id] as $member_id) {{
            $tokens[] = $group_id . ':' . $member_id;
        }}
    }}
    $basis = $collection['key'] . '|' . $collection['owner_entity_id'] . '|' . implode('|', $tokens);
    $collection['receipt']['membership_digest'] = 'sha256:' . hash('sha256', $basis);
}}

$deny_cases = array();
$kombu_offset = c99_entity_offset($registry, 'ingredient-kombu');
foreach (array('supplier', 'retail_listing', 'market_observation', 'guide_edition', 'visual_asset') as $type) {{
    $candidate = $registry;
    $candidate['entities'][$kombu_offset]['type'] = $type;
    $deny_cases[$type] = c99_result($candidate);
}}
$private_compliance = $registry;
$private_compliance['entities'][$kombu_offset]['type'] = 'compliance_rule';
$private_compliance['entities'][$kombu_offset]['surface_class'] = 'editorial_draft';
$deny_cases['private_compliance'] = c99_result($private_compliance);

$collection_cases = array();
$private_member = $registry;
$private_member['collections'][0]['navigation']['member_ids_by_group']['ingredients'][0] = 'ingredient-yakinori';
c99_refresh_membership_receipt($private_member);
$collection_cases['private_member'] = c99_result($private_member);

$wrong_type = $registry;
array_pop($wrong_type['collections'][0]['navigation']['member_ids_by_group']['ingredients']);
$wrong_type['collections'][0]['navigation']['member_ids_by_group']['equipment'] = array('ingredient-hon-mirin');
c99_refresh_membership_receipt($wrong_type);
$collection_cases['wrong_group_type'] = c99_result($wrong_type);

$duplicate_member = $registry;
$duplicate_member['collections'][0]['navigation']['member_ids_by_group']['food_science'][] = 'ingredient-kombu';
c99_refresh_membership_receipt($duplicate_member);
$collection_cases['duplicate_member'] = c99_result($duplicate_member);

$receipt_drift = $registry;
$receipt_drift['collections'][0]['receipt']['membership_digest'] = 'sha256:' . str_repeat('0', 64);
$collection_cases['receipt_drift'] = c99_result($receipt_drift);

$route_drift = $registry;
$route_drift['collections'][0]['route']['canonical_path']['en'] = '/en/museum/japanese-culinary-science/other-foundations/';
$collection_cases['route_drift'] = c99_result($route_drift);

$duplicate_owner = $registry;
$duplicate_owner['collections'][] = $duplicate_owner['collections'][0];
$duplicate_owner['collections'][1]['key'] = 'japanese-foundations-lab-copy';
$duplicate_owner['collections'][1]['translation_group_id'] = 'collection-japanese-foundations-lab-copy';
$collection_cases['duplicate_owner'] = c99_result($duplicate_owner);

$index_drift = $registry;
$owner_offset = c99_entity_offset($index_drift, 'hub-japanese-foundations-lab');
$index_drift['entities'][$owner_offset]['index_policy'] = 'index';
$collection_cases['index_drift'] = c99_result($index_drift);

echo json_encode(array(
    'registry' => $registry,
    'deny_cases' => $deny_cases,
    'collection_cases' => $collection_cases,
    'he_bundle' => Complete99_Culinary_Science::public_page_bundle_for_id('hub-japanese-foundations-lab', 'he'),
    'en_bundle' => Complete99_Culinary_Science::public_page_bundle_for_id('hub-japanese-foundations-lab', 'en'),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=45,
    )
    return json.loads(completed.stdout)


def test_v5_registry_has_one_exact_modular_collection(v5_payload: dict) -> None:
    registry = v5_payload["registry"]
    assert registry["schema"] == "complete99-culinary-science-registry/v5"
    assert registry["version"] == "japanese-pilot-2026.08.06.v9"
    assert len(registry["collections"]) == 1
    collection = registry["collections"][0]
    assert set(collection) == {
        "key",
        "owner_entity_id",
        "navigation",
        "translation_group_id",
        "route",
        "index_reason",
        "receipt",
        "display",
        "public_projection",
    }
    assert collection["key"] == "japanese-foundations-lab"
    assert collection["owner_entity_id"] == "hub-japanese-foundations-lab"
    assert collection["navigation"]["group_order"] == [
        "ingredients",
        "food_science",
        "techniques",
        "equipment",
    ]
    assert collection["public_projection"] == {
        "enabled": True,
        "schema": "complete99-culinary-collection-public/v1",
    }


def test_validator_denies_private_record_types_from_public_exposure(
    v5_payload: dict,
) -> None:
    cases = v5_payload["deny_cases"]
    for entity_type in (
        "supplier",
        "retail_listing",
        "market_observation",
        "guide_edition",
        "visual_asset",
    ):
        assert cases[entity_type]["valid"] is False
        assert cases[entity_type]["path"].endswith(
            f".publication.public_exposure_denied.entity_type_{entity_type}"
        )
    assert cases["private_compliance"]["valid"] is False
    assert cases["private_compliance"]["path"].endswith(
        ".publication.public_exposure_denied.private_compliance"
    )


def test_collection_validator_fails_closed_on_membership_and_owner_drift(
    v5_payload: dict,
) -> None:
    cases = v5_payload["collection_cases"]
    assert cases["private_member"]["path"].endswith(
        ".navigation.member_not_public"
    )
    assert cases["wrong_group_type"]["path"].endswith(
        ".navigation.member_type"
    )
    assert cases["duplicate_member"]["path"].endswith(
        ".navigation.duplicate_member"
    )
    assert cases["receipt_drift"]["path"].endswith(
        ".receipt.membership_mismatch"
    )
    assert cases["route_drift"]["path"].endswith(".owner_contract")
    assert cases["duplicate_owner"]["path"].endswith(".duplicate_owner")
    assert cases["index_drift"]["path"].endswith(".public_owner_contract")
    assert all(case["valid"] is False for case in cases.values())


def test_collection_projection_is_locale_paired_and_private_wrapper_free(
    v5_payload: dict,
) -> None:
    private_wrapper_fields = {
        "navigation",
        "index_reason",
        "receipt",
        "display",
        "public_projection",
    }
    he = v5_payload["he_bundle"]["collection"]
    en = v5_payload["en_bundle"]["collection"]
    assert he["language"] == "he"
    assert en["language"] == "en"
    assert he["canonical_path"] == en["alternate_path"]
    assert en["canonical_path"] == he["alternate_path"]
    assert he["parity_member_ids"] == en["parity_member_ids"]
    assert not (private_wrapper_fields & set(he))
    assert not (private_wrapper_fields & set(en))
    assert len(he["members"]) == len(en["members"]) == 11


def test_foundations_lab_hero_alt_is_localized_in_the_public_projection(
    v5_payload: dict,
) -> None:
    assert v5_payload["he_bundle"]["entity"]["visual"]["alt"] == (
        "שולחן יסודות המטבח היפני עם אורז בהאנגירי, קומבו, קצואובושי, "
        "שויו, יוזו, וואסבי, קוג׳י, נורי, כלי מדידה וסכין יפנית"
    )
    assert v5_payload["en_bundle"]["entity"]["visual"]["alt"] == (
        "Japanese culinary foundations table with rice in a hangiri, kombu, "
        "katsuobushi, shoyu, yuzu, wasabi, koji, nori, measurement tools and "
        "a Japanese knife"
    )
