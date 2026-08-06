from __future__ import annotations

import json
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
COMMERCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-commerce.php"

SAFE_ROW_KEYS = {
    "label",
    "amount",
    "currency",
    "normalized_amount",
    "normalized_unit",
    "market",
    "seller",
    "observed_at",
    "availability",
    "comparability",
    "tax_state",
    "shipping_state",
    "source_url",
    "scope_note",
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def projection() -> dict[str, list[dict[str, object]]]:
    plugin_path = _php_path(PLUGIN) + "/"
    science_class = _php_path(SCIENCE_CLASS)
    commerce_class = _php_path(COMMERCE_CLASS)
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
class WP_Error {{}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
require '{science_class}';
require '{commerce_class}';

function c99_projection_for_variant_policy($mode) {{
    $registry = Complete99_Culinary_Commerce::registry(true);
    foreach ($registry['variants'] as &$variant) {{
        if ('variant-rishiri-kombu-100g' !== $variant['id']) {{ continue; }}
        if ('missing' === $mode) {{
            unset($variant['attributes']['public_market_projection']);
        }} else {{
            $variant['attributes']['public_market_projection'] = $mode;
        }}
    }}
    unset($variant);
    $cache = new ReflectionProperty(Complete99_Culinary_Commerce::class, 'registry_cache');
    $cache->setAccessible(true);
    $cache->setValue(null, $registry);
    $rows = Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-kombu', 'en');
    $cache->setValue(null, null);
    return $rows;
}}

$result = array(
    'policy_explicit_public' => c99_projection_for_variant_policy('public'),
    'policy_missing' => c99_projection_for_variant_policy('missing'),
    'policy_invalid' => c99_projection_for_variant_policy('unexpected'),
    'policy_held' => c99_projection_for_variant_policy('held'),
    'kombu_he' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-kombu', 'he'),
    'kombu_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-kombu', 'en'),
    'shoyu_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-kioke-shoyu', 'en'),
    'wasabi_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-fresh-wasabi', 'en'),
    'yuzu_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-kito-yuzu', 'en'),
    'yakinori_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-yakinori', 'en'),
    'hub_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('hub-japanese-ingredients', 'en'),
    'missing_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('ingredient-not-present', 'en'),
    'invalid_en' => Complete99_Culinary_Commerce::public_market_context_for_science_entity('../private', 'en'),
);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
    )
    return json.loads(completed.stdout)


def test_projection_returns_only_exact_public_safe_fields(projection):
    rows = [
        row
        for result in projection.values()
        for row in result
    ]
    assert rows
    for row in rows:
        assert set(row) == SAFE_ROW_KEYS
        assert row["source_url"].startswith("https://")
        assert "T" in row["observed_at"]
        assert row["scope_note"]

    serialized = json.dumps(rows, ensure_ascii=False).lower()
    for forbidden in (
        "cost",
        "margin",
        "supplier_offer",
        "supplier_terms",
        "approved_by",
        "captured_by",
        "connector",
        "woo_product_code",
        "internal_code",
        "channel_offer",
        "israeli_price",
        "active_offer",
    ):
        assert forbidden not in serialized
    assert chr(0x2014) not in serialized


def test_projection_localizes_labels_and_states_scope(projection):
    assert len(projection["kombu_he"]) == 1
    assert len(projection["kombu_en"]) == 1
    hebrew = projection["kombu_he"][0]
    english = projection["kombu_en"][0]
    assert hebrew["label"] != english["label"]
    assert hebrew["market"] != english["market"]
    assert "מוצגים בחנות" in hebrew["scope_note"]
    assert "presented in the store" in english["scope_note"]


def test_projection_formats_observed_and_normalized_minor_units(projection):
    kombu = projection["kombu_en"][0]
    assert kombu["amount"] == "1165"
    assert kombu["currency"] == "JPY"
    assert kombu["normalized_amount"] == "11650"
    assert kombu["normalized_unit"] == "kg"

    shoyu = projection["shoyu_en"][0]
    assert shoyu["amount"] == "1944"
    assert shoyu["normalized_amount"] == "3888"
    assert shoyu["normalized_unit"] == "l"

    wasabi = projection["wasabi_en"][0]
    assert wasabi["amount"] == "62.50"
    assert wasabi["normalized_amount"] == "250.00"
    assert wasabi["currency"] == "GBP"
    assert wasabi["availability"] == "out_of_stock"


def test_projection_returns_all_dated_variants_for_linked_entity(projection):
    yuzu = projection["yuzu_en"]
    assert len(yuzu) == 2
    assert {row["amount"] for row in yuzu} == {"734", "3780"}
    assert {row["normalized_amount"] for row in yuzu} == {"7340", "5250"}
    assert {row["currency"] for row in yuzu} == {"JPY"}
    assert {row["normalized_unit"] for row in yuzu} == {"l"}


def test_projection_policy_is_explicit_and_fail_closed(projection):
    assert projection["policy_explicit_public"] == projection["kombu_en"]
    assert len(projection["policy_explicit_public"]) == 1
    assert projection["policy_missing"] == []
    assert projection["policy_invalid"] == []
    assert projection["policy_held"] == []


def test_projection_fails_closed_for_unlinked_or_invalid_entity(projection):
    assert projection["yakinori_en"] == []
    assert projection["hub_en"] == []
    assert projection["missing_en"] == []
    assert projection["invalid_en"] == []
