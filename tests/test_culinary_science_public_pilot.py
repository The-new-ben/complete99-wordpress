from __future__ import annotations

import hashlib
import json
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"
ASSET_DIR = PLUGIN / "assets" / "images" / "science"
BUILDER = ROOT / "scripts" / "build-plugin-zip.py"

PUBLIC_IDS = {
    "museum-culinary-science",
    "cuisine-japanese-washoku",
    "hub-japanese-ingredients",
    "ingredient-kombu",
    "ingredient-kioke-shoyu",
    "ingredient-fresh-wasabi",
    "ingredient-kito-yuzu",
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def pilot_payload() -> dict:
    plugin_path = _php_path(PLUGIN) + "/"
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
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
    public function get_error_data() {{ return $this->data; }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
function home_url($path = '') {{ return 'https://complete99.test' . $path; }}
require '{_php_path(SCIENCE_CLASS)}';
$registry = require '{_php_path(SCIENCE_DATA)}';
$paths = array(
    '/museum/',
    '/en/museum/',
    '/museum/japanese-culinary-science/',
    '/en/museum/japanese-culinary-science/',
    '/ingredients/kombu/',
    '/en/ingredients/kombu/',
    '/ingredients/kioke-shoyu/',
    '/en/ingredients/kioke-shoyu/',
    '/ingredients/fresh-wasabi-rhizome/',
    '/en/ingredients/fresh-wasabi-rhizome/',
    '/ingredients/kito-yuzu/',
    '/en/ingredients/kito-yuzu/'
);
$bundles = array();
foreach ($paths as $path) {{
    $bundles[$path] = Complete99_Culinary_Science::public_page_bundle_for_path($path);
}}
echo json_encode(array(
    'registry' => $registry,
    'status' => Complete99_Culinary_Science::status(),
    'bundles' => $bundles,
    'invalid' => Complete99_Culinary_Science::public_page_bundle_for_path('/ingredients/not-approved/'),
    'indexable' => Complete99_Culinary_Science::public_indexable_page_projections(),
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


def test_exact_reviewed_public_cohort_and_noindex_boundary(pilot_payload: dict) -> None:
    registry = pilot_payload["registry"]
    public = {
        entity["id"]
        for entity in registry["entities"]
        if entity["publication"]["public_page"]
    }
    assert public == PUBLIC_IDS
    assert pilot_payload["status"]["public_count"] == len(PUBLIC_IDS)
    assert pilot_payload["indexable"] == []
    for entity in registry["entities"]:
        if entity["id"] in PUBLIC_IDS:
            assert entity["publication"]["search_index"] is False
            assert entity["index_policy"] == "noindex_until_longform_review"


def test_exact_bilingual_routes_and_projection_only_bundles(pilot_payload: dict) -> None:
    assert pilot_payload["invalid"] == []
    assert len(pilot_payload["bundles"]) == 12
    for path, bundle in pilot_payload["bundles"].items():
        assert bundle["canonical_path"] == path
        assert bundle["canonical_url"] == "https://complete99.test" + path
        assert bundle["indexable"] is False
        assert bundle["language"] == ("en" if path.startswith("/en/") else "he")
        assert set(bundle) == {
            "schema",
            "version",
            "language",
            "entity",
            "sections",
            "canonical_path",
            "canonical_url",
            "alternates",
            "indexable",
        }
        entity = bundle["entity"]
        assert "commerce" not in entity
        assert "publication" not in entity
        assert "prompt_en" not in entity["visual"]
        assert "rights_receipt_digest" not in entity["visual"]
        assert entity["search_index"] is False


def test_cuisine_owns_the_public_ingredient_section(pilot_payload: dict) -> None:
    cuisine = pilot_payload["bundles"]["/museum/japanese-culinary-science/"]
    assert [section["id"] for section in cuisine["sections"]] == [
        "hub-japanese-ingredients"
    ]
    section = cuisine["sections"][0]
    assert section["seo"]["route_mode"] == "section"
    assert section["seo"]["owner_entity_id"] == "cuisine-japanese-washoku"
    assert section["seo"]["section_id"] == "japanese-premium-ingredients"


def test_every_public_projection_has_a_digest_matched_generated_asset(
    pilot_payload: dict,
) -> None:
    by_id = {entity["id"]: entity for entity in pilot_payload["registry"]["entities"]}
    for entity_id in PUBLIC_IDS:
        entity = by_id[entity_id]
        filename = f"c99-science-{entity['slug']}-v01.webp"
        asset = ASSET_DIR / filename
        assert asset.is_file(), (entity_id, asset)
        digest = hashlib.sha256(asset.read_bytes()).hexdigest()
        assert entity["visual"]["rights_receipt_digest"] == f"sha256:{digest}"
        assert entity["visual"]["rights_state"] == "cleared_generated"


def test_release_builder_keeps_science_png_sources_out_of_delivery_zip() -> None:
    builder = BUILDER.read_text(encoding="utf-8")
    assert 'SCIENCE_SOURCE_ROOT = PurePath("assets/images/science")' in builder
    assert "SOURCE_ONLY_PNG_ROOTS" in builder


def test_public_claims_are_cited_and_source_corrections_are_retained(
    pilot_payload: dict,
) -> None:
    registry = pilot_payload["registry"]
    sources = registry["sources"]
    by_id = {entity["id"]: entity for entity in registry["entities"]}
    for entity_id in PUBLIC_IDS:
        facts = [fact for fact in by_id[entity_id]["facts"] if fact["public_safe"]]
        assert facts
        for fact in facts:
            assert fact["evidence_class"] != "editorial_inference"
            assert fact["source_ids"]
            assert set(fact["source_ids"]).issubset(sources)

    conference = sources["kombu-water-extraction-conference-2024"]
    assert conference["type"] == "conference_proceeding"
    assert conference["published_at"] == "2024-09-06"
    assert sources["yuzu-aroma-2009"]["title"].startswith(
        "Novel Character Impact Compounds"
    )
    assert sources["kito-yuzu-juice-720ml-listing-2026"]["url"].endswith(
        "000000000199?category_page_id=ichiban"
    )
