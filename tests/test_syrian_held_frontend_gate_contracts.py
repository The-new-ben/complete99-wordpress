from __future__ import annotations

import json
import shutil
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
FRONTEND = PLUGIN / "includes" / "class-complete99-culinary-museum-frontend.php"
SCIENCE = PLUGIN / "includes" / "class-complete99-culinary-science.php"

HELD_IDS = (
    "region-syria-aleppo",
    "hub-aleppine-kibbeh-family",
    "ingredient-syrian-bulgur",
    "ingredient-syrian-red-meat",
    "technique-syrian-bulgur-hydration",
    "technique-syrian-kibbeh-cooking",
    "tradition-aleppan-jewish-foodways",
)
HELD_PATHS = (
    "/museum/syrian-culinary-science/aleppo/",
    "/museum/syrian-culinary-science/aleppo/aleppine-kibbeh-family/",
    "/ingredients/syrian-bulgur/",
    "/ingredients/lamb-and-beef-in-syrian-cooking/",
    "/knowledge/how-to-hydrate-bulgur-for-kibbeh/",
    "/knowledge/how-to-cook-kibbeh-safely/",
    "/traditions/aleppan-jewish-foodways/",
)
HELD_STEMS = (
    "c99-science-syrian-aleppo-table-v01",
    "c99-science-aleppine-kibbeh-family-v01",
    "c99-science-syrian-bulgur-v01",
    "c99-science-syrian-lamb-beef-family-v01",
    "c99-science-syrian-bulgur-hydration-v01",
    "c99-science-syrian-kibbeh-cooking-v01",
    "c99-science-aleppan-jewish-foodways-v01",
)


def test_syrian_custom_renderers_require_authoritative_public_bundles() -> None:
    source = FRONTEND.read_text(encoding="utf-8")
    assert "&& self::syrian_public_cohort_is_ready( $bundle )" in source
    assert "self::SYRIAN_PUBLIC_COHORT_IDS" in source
    assert "Complete99_Culinary_Science::public_page_bundle_for_id" in source
    resolver = source.split("private static function syrian_entity_url", 1)[1].split(
        "private static function syrian_countertop_ids", 1
    )[0]
    assert "$target['canonical_url'] === $url" in resolver
    assert "$catalog" not in resolver
    assert "return isset(" not in resolver

    for japanese_id in (
        "ingredient-shoyu-koji",
        "equipment-kioke",
        "guide-koji-hydrolysis",
        "reaction-koji-enzymatic-hydrolysis",
        "standard-jas-shoyu-1703",
    ):
        assert japanese_id not in source


@pytest.mark.skipif(not shutil.which("php"), reason="PHP is required")
def test_real_held_registry_renders_safe_generic_syrian_root_in_both_languages() -> None:
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', {json.dumps(PLUGIN.as_posix() + '/')});
define('COMPLETE99_PLATFORM_URL', 'https://complete99.example/plugin/');

class WP_Error {{
    public function __construct($code = '', $message = '', $data = array()) {{}}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
function home_url($path = '') {{ return 'https://complete99.example/' . ltrim((string) $path, '/'); }}
function wp_parse_url($url, $component = -1) {{ return -1 === $component ? parse_url($url) : parse_url($url, $component); }}
function sanitize_html_class($value) {{ return preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $value)); }}
function sanitize_key($value) {{ return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }}
function absint($value) {{ return abs((int) $value); }}
function esc_attr($value) {{ return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }}
function esc_url($value) {{ return esc_attr($value); }}
function esc_html($value) {{ return esc_attr($value); }}
function esc_url_raw($value, $protocols = null) {{ return (string) $value; }}
function number_format_i18n($value, $decimals = 0) {{ return number_format((float) $value, (int) $decimals, '.', ','); }}

require {json.dumps(SCIENCE.as_posix())};
require {json.dumps(FRONTEND.as_posix())};

function c99_render($bundle) {{
    $property = new ReflectionProperty('Complete99_Culinary_Museum_Frontend', 'bundle');
    $property->setAccessible(true);
    $property->setValue(null, $bundle);
    ob_start();
    Complete99_Culinary_Museum_Frontend::render_page($bundle);
    return ob_get_clean();
}}

$gate = new ReflectionMethod('Complete99_Culinary_Museum_Frontend', 'syrian_public_cohort_is_ready');
$gate->setAccessible(true);
$he = Complete99_Culinary_Science::public_page_bundle_for_id('cuisine-syrian-regional', 'he');
$en = Complete99_Culinary_Science::public_page_bundle_for_id('cuisine-syrian-regional', 'en');
$held = array();
foreach ({json.dumps(list(HELD_IDS))} as $id) {{
    $held[$id] = Complete99_Culinary_Science::public_page_bundle_for_id($id, 'he');
}}
echo json_encode(array(
    'he_gate' => $gate->invoke(null, $he),
    'en_gate' => $gate->invoke(null, $en),
    'he_html' => c99_render($he),
    'en_html' => c99_render($en),
    'held' => $held,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
        timeout=90,
        check=False,
    )
    assert completed.returncode == 0, completed.stderr
    result = json.loads(completed.stdout)

    assert result["he_gate"] is False
    assert result["en_gate"] is False
    assert all(bundle == [] for bundle in result["held"].values())
    for language in ("he", "en"):
        html = result[f"{language}_html"]
        assert "c99-museum-hero" in html
        assert 'id="c99-syria-home"' not in html
        for token in (*HELD_IDS, *HELD_PATHS, *HELD_STEMS):
            assert token not in html
