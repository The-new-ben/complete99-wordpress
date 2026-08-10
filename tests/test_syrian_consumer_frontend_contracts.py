from __future__ import annotations

import json
import re
import shutil
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
FRONTEND = PLUGIN / "includes" / "class-complete99-culinary-museum-frontend.php"
SCIENCE = PLUGIN / "includes" / "class-complete99-culinary-science.php"
CSS = PLUGIN / "assets" / "css" / "culinary-museum.css"

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
HELD_ASSET_STEMS = (
    "c99-science-syrian-aleppo-table-v01",
    "c99-science-aleppine-kibbeh-family-v01",
    "c99-science-syrian-bulgur-v01",
    "c99-science-syrian-lamb-beef-family-v01",
    "c99-science-syrian-bulgur-hydration-v01",
    "c99-science-syrian-kibbeh-cooking-v01",
    "c99-science-aleppan-jewish-foodways-v01",
)


def _text(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def _method(source: str, name: str, next_name: str) -> str:
    start = source.index(f"private static function {name}")
    end = source.index(f"private static function {next_name}", start)
    return source[start:end]


def test_syrian_custom_renderers_require_authoritative_public_bundles() -> None:
    source = _text(FRONTEND)
    collection = source.index("$collection = self::approved_collection_projection")
    dispatch = source[source.index("public static function render_page") : collection]
    assert "self::syrian_public_cohort_is_ready( $bundle )" in dispatch
    assert "self::render_syrian_landing( $bundle );" in dispatch
    assert "self::SYRIAN_PUBLIC_COHORT_IDS" in dispatch
    assert "self::authoritative_public_bundle_matches(" in dispatch
    assert dispatch.index("self::syrian_public_cohort_is_ready") < (
        dispatch.index("self::render_syrian_landing")
    )
    for entity_id in HELD_IDS:
        assert f"'{entity_id}'" in source
    assert "self::render_syrian_consumer_page( $bundle );" in dispatch
    assert "dish-kibbeh-meshwiyyeh" not in dispatch


def test_gated_syrian_landing_template_keeps_the_food_first_visit_order() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_syrian_landing", "render_syrian_consumer_page")

    markers = (
        "c99-syria-hero",
        "c99-syria-aleppo",
        "c99-syria-kibbeh",
        "c99-syria-countertop",
        "c99-syria-methods",
        "c99-syria-tradition",
        "c99-syria-continue",
        "render_syrian_source_drawer",
    )
    offsets = [landing.index(marker) for marker in markers]
    assert offsets == sorted(offsets)

    pairs = (
        ("להתחיל בחלב", "Begin in Aleppo"),
        ("מה פוגשים בצלחת", "What you find on the plate"),
        ("מה לשים על השיש", "What to gather"),
        ("מה משפיע על הטעם והמרקם", "What shapes flavor and texture"),
        ("ממשיכים לגלות", "Keep exploring"),
    )
    for hebrew, english in pairs:
        assert hebrew in landing
        assert english in landing

    assert "self::render_visual( $entity, true )" in landing
    assert "render_profiles" not in landing
    assert "render_taxonomy" not in landing
    assert "render_trust" not in landing
    assert "substantive_updated_at" not in landing
    assert "dish-kibbeh-meshwiyyeh" not in landing


def test_held_syrian_catalog_preserves_draft_routes_assets_and_alts() -> None:
    source = _text(FRONTEND)
    science = _text(SCIENCE)
    catalog = _method(source, "syrian_entity_catalog", "format_number")

    for path in HELD_PATHS:
        assert f"$prefix . '{path}'" in catalog
    for stem in HELD_ASSET_STEMS:
        assert stem in catalog
        assert stem in science

    assert catalog.count("'alt'") == 7
    assert "raw meat" not in catalog.lower()
    assert "research" not in catalog.lower()


def test_gated_syrian_entity_template_is_consumer_first_with_collapsed_sources() -> None:
    source = _text(FRONTEND)
    page = _method(source, "render_syrian_consumer_page", "render_syrian_measurements")
    drawer = _method(source, "render_syrian_source_drawer", "render_syrian_entity_card")

    for hebrew, english in (
        ("מה פוגשים בצלחת", "What you find on the plate"),
        ("מה משפיע על הטעם והמרקם", "What shapes flavor and texture"),
        ("מה לשים על השיש", "What to gather"),
        ("לפני שמתחילים", "Before you begin"),
        ("ממשיכים לגלות", "Keep exploring"),
    ):
        assert hebrew in page
        assert english in page

    for forbidden in (
        "render_profiles",
        "render_facts",
        "render_connections",
        "render_market_context",
        "render_taxonomy",
        "render_trust",
        "entity_type_label",
        "evidence_label",
        "source_number_map",
    ):
        assert forbidden not in page

    assert "self::render_offer( $entity )" in page
    assert "<details>" in drawer
    assert "<summary>" in drawer
    assert " open" not in drawer
    assert "למי שרוצה להעמיק" in drawer
    assert "For readers who want to go deeper" in drawer
    assert "Sources and citations" not in drawer
    assert "מקורות וציטוטים" not in drawer


def test_held_syrian_draft_continuations_stay_inside_the_candidate_graph() -> None:
    source = _text(FRONTEND)
    page = _method(source, "render_syrian_consumer_page", "render_syrian_measurements")
    countertop = _method(source, "syrian_countertop_ids", "syrian_related_ids")
    related = _method(source, "syrian_related_ids", "syrian_profile_label")

    expected_countertop = {
        "region-syria-aleppo": ("ingredient-syrian-bulgur", "ingredient-syrian-red-meat"),
        "hub-aleppine-kibbeh-family": ("ingredient-syrian-bulgur", "ingredient-syrian-red-meat"),
        "ingredient-syrian-bulgur": (),
        "ingredient-syrian-red-meat": (),
        "technique-syrian-bulgur-hydration": ("ingredient-syrian-bulgur",),
        "technique-syrian-kibbeh-cooking": ("ingredient-syrian-bulgur", "ingredient-syrian-red-meat"),
        "tradition-aleppan-jewish-foodways": (),
    }
    expected_related = {
        "region-syria-aleppo": (
            "hub-aleppine-kibbeh-family",
            "technique-syrian-bulgur-hydration",
            "technique-syrian-kibbeh-cooking",
            "tradition-aleppan-jewish-foodways",
        ),
        "hub-aleppine-kibbeh-family": (
            "technique-syrian-bulgur-hydration",
            "technique-syrian-kibbeh-cooking",
            "region-syria-aleppo",
        ),
        "ingredient-syrian-bulgur": (
            "technique-syrian-bulgur-hydration",
            "hub-aleppine-kibbeh-family",
            "technique-syrian-kibbeh-cooking",
            "region-syria-aleppo",
        ),
        "ingredient-syrian-red-meat": (
            "hub-aleppine-kibbeh-family",
            "technique-syrian-kibbeh-cooking",
            "region-syria-aleppo",
        ),
        "technique-syrian-bulgur-hydration": (
            "hub-aleppine-kibbeh-family",
            "technique-syrian-kibbeh-cooking",
            "region-syria-aleppo",
        ),
        "technique-syrian-kibbeh-cooking": (
            "hub-aleppine-kibbeh-family",
            "technique-syrian-bulgur-hydration",
            "region-syria-aleppo",
        ),
        "tradition-aleppan-jewish-foodways": ("region-syria-aleppo",),
    }

    def php_map_row(method: str, entity_id: str) -> tuple[str, ...]:
        row = next(
            line for line in method.splitlines()
            if line.strip().startswith(f"'{entity_id}'")
        )
        return tuple(re.findall(r"'([^']+)'", row.split("=>", 1)[1]))

    for entity_id, expected in expected_countertop.items():
        assert php_map_row(countertop, entity_id) == expected
    for entity_id, expected in expected_related.items():
        assert php_map_row(related, entity_id) == expected

    assert "$urls['lebanon']" not in page
    assert "$urls['museum']" not in page


def test_syrian_styles_cover_focus_targets_direction_and_mobile() -> None:
    css = _text(CSS)

    assert ".c99-syria-entity-card:focus-visible" in css
    assert ".c99-syria-reading summary:focus-visible" in css
    assert "outline: 3px solid var(--c99-syria-saffron);" in css
    assert ".c99-syria-button-primary" in css
    assert "min-height: 44px;" in css
    assert "[dir=\"rtl\"] .c99-syria-hero h1" in css
    assert ".c99-syria-entity-card .c99-museum-home-picture img" in css
    assert "object-fit: cover;" in css
    assert "@media (max-width: 920px)" in css
    assert "@media (max-width: 680px)" in css
    assert "@media (prefers-reduced-motion: reduce)" in css


@pytest.mark.skipif(not shutil.which("php"), reason="PHP is required")
def test_rendered_syrian_landing_excludes_every_held_candidate_token() -> None:
    frontend_path = json.dumps(FRONTEND.as_posix())
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_URL', 'https://complete99.example/plugin/');

function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function wp_parse_url($url, $component = -1) {{
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}}
function sanitize_html_class($value) {{
    return preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $value));
}}
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function absint($value) {{ return abs((int) $value); }}
function esc_attr($value) {{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}}
function esc_url($value) {{ return esc_attr($value); }}
function esc_html($value) {{ return esc_attr($value); }}
function esc_url_raw($value, $protocols = null) {{ return (string) $value; }}
function number_format_i18n($value, $decimals = 0) {{
    return number_format((float) $value, (int) $decimals, '.', ',');
}}

require {frontend_path};

function c99_test_entity($id, $lang, $path) {{
    return array(
        'id' => $id,
        'type' => 'cuisine-syrian-regional' === $id ? 'cuisine' : 'ingredient',
        'name' => 'he' === $lang ? 'טעם סורי' : 'Syrian flavor',
        'summary' => 'he' === $lang ? 'טעם, מרקם ומסורת נפגשים כאן.' : 'Flavor, texture and tradition meet here.',
        'seo' => array(
            'route_mode' => 'standalone',
            'canonical_path' => $path,
            'h1' => 'he' === $lang ? 'המטבח הסורי' : 'Bulgur in Syrian cooking',
            'opening' => 'he' === $lang ? 'נכנסים דרך הצלחת.' : 'Begin with the ingredient and follow its texture.',
            'visible_breadcrumbs' => array(
                array('key' => 'home', 'label' => 'Home', 'path' => 'he' === $lang ? '/' : '/en/'),
                array('key' => 'entity', 'label' => 'Syrian', 'path' => $path),
            ),
        ),
        'profiles' => array(
            'scientific' => array('summary' => 'Water uptake changes texture.'),
        ),
        'facts' => array(
            array('statement' => 'Grain size and water uptake work together.', 'scientific_measurements' => array()),
        ),
        'internal_links' => array(),
        'visual' => array(),
        'offer' => array(),
        'safety_notes' => array('Keep ingredient handling clean and separate.'),
        'sources' => array(
            array(
                'publisher' => 'Culinary Archive',
                'title' => 'Aleppo foodways',
                'url' => 'https://archive.example/aleppo',
            ),
        ),
    );
}}

function c99_test_bundle($id, $lang, $path, $other) {{
    $entity = c99_test_entity($id, $lang, $path);
    $he_url = home_url('he' === $lang ? $path : $other);
    $en_url = home_url('en' === $lang ? $path : $other);
    return array(
        'schema' => 'complete99-culinary-science-page-bundle/v1',
        'version' => 'test',
        'language' => $lang,
        'entity' => $entity,
        'sections' => array(),
        'canonical_path' => $path,
        'canonical_url' => home_url($path),
        'alternates' => array('he' => $he_url, 'en' => $en_url, 'x-default' => $he_url),
        'indexable' => false,
    );
}}

function c99_test_render($bundle) {{
    $property = new ReflectionProperty('Complete99_Culinary_Museum_Frontend', 'bundle');
    $property->setAccessible(true);
    $property->setValue(null, $bundle);
    ob_start();
    Complete99_Culinary_Museum_Frontend::render_page($bundle);
    return ob_get_clean();
}}

$he = c99_test_bundle(
    'cuisine-syrian-regional',
    'he',
    '/museum/syrian-culinary-science/',
    '/en/museum/syrian-culinary-science/'
);
$en = c99_test_bundle(
    'ingredient-syrian-bulgur',
    'en',
    '/en/ingredients/syrian-bulgur/',
    '/ingredients/syrian-bulgur/'
);

echo json_encode(array(
    'he' => c99_test_render($he),
    'en' => c99_test_render($en),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
        timeout=30,
        check=False,
    )
    assert completed.returncode == 0, completed.stderr
    result = json.loads(completed.stdout)

    assert 'class="c99-museum-hero"' in result["he"]
    assert 'id="c99-syria-home"' not in result["he"]
    for token in (*HELD_IDS, *HELD_PATHS, *HELD_ASSET_STEMS):
        assert token not in result["he"]
    assert result["en"] == ""


def test_changed_frontend_files_have_no_em_dash() -> None:
    for path in (FRONTEND, SCIENCE, CSS, Path(__file__)):
        assert "\N{EM DASH}" not in _text(path), path
