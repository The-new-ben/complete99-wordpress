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
    "hub-japanese-food-science",
    "hub-japanese-ingredients",
    "hub-japanese-techniques",
    "guide-umami-synergy",
    "ingredient-kombu",
    "ingredient-katsuobushi",
    "ingredient-kioke-shoyu",
    "ingredient-fresh-wasabi",
    "ingredient-kito-yuzu",
    "ingredient-hon-mirin",
    "preparation-ichiban-dashi",
}
PUBLIC_OFFER_CODES = {
    "ingredient-kombu": "product-rishiri-kombu-100g",
    "ingredient-katsuobushi": "product-honkarebushi-200g",
    "ingredient-kioke-shoyu": "product-yamaroku-tsurubishio-500ml",
    "ingredient-kito-yuzu": "product-kito-yuzu-juice-100ml",
}
PUBLIC_PROJECTION_KEYS = {
    "id",
    "type",
    "slug",
    "parent_id",
    "name",
    "summary",
    "index_policy",
    "search_index",
    "seo",
    "profiles",
    "facts",
    "taxonomy",
    "relations",
    "internal_links",
    "visual",
    "market_context",
    "offer",
    "safety_notes",
    "sources",
    "trust",
    "reviewed_at",
}
PRIVATE_PROJECTION_KEYS = {
    "surface_class",
    "publication",
    "commerce",
    "review",
    "asset_state",
    "prompt_en",
    "negative_prompt_en",
    "shot_list",
    "rights_method",
    "rights_state",
    "rights_receipt_digest",
    "woo_product_code",
    "public_offer_allowed",
    "product_copy",
    "cross_sell_ids",
    "up_sell_ids",
    "business_model",
    "revenue_models",
    "customer_segments",
    "value_proposition",
    "pricing_state",
    "market_scope",
    "observation_entity_ids",
    "margin_scenario",
    "landed_cost_low",
    "landed_cost_high",
    "retail_price_low",
    "retail_price_high",
    "gross_margin_low",
    "gross_margin_high",
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
    '/ingredients/katsuobushi/',
    '/en/ingredients/katsuobushi/',
    '/ingredients/kioke-shoyu/',
    '/en/ingredients/kioke-shoyu/',
    '/ingredients/fresh-wasabi-rhizome/',
    '/en/ingredients/fresh-wasabi-rhizome/',
    '/ingredients/kito-yuzu/',
    '/en/ingredients/kito-yuzu/',
    '/ingredients/hon-mirin/',
    '/en/ingredients/hon-mirin/',
    '/knowledge/ichiban-dashi/',
    '/en/knowledge/ichiban-dashi/',
    '/knowledge/umami-synergy-glutamate-imp/',
    '/en/knowledge/umami-synergy-glutamate-imp/'
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
    assert len(pilot_payload["bundles"]) == 20
    public_standalone = {
        entity["id"]
        for entity in pilot_payload["registry"]["entities"]
        if entity["publication"]["public_page"]
        and entity["seo"]["route_mode"] == "standalone"
    }
    assert len(public_standalone) == 10
    assert len(pilot_payload["bundles"]) == 2 * len(public_standalone)
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
        assert set(entity) == PUBLIC_PROJECTION_KEYS
        assert entity["search_index"] is False


def _mapping_keys(value: object) -> set[str]:
    keys: set[str] = set()
    if isinstance(value, dict):
        keys.update(value)
        for item in value.values():
            keys.update(_mapping_keys(item))
    elif isinstance(value, list):
        for item in value:
            keys.update(_mapping_keys(item))
    return keys


def test_public_projections_expose_only_four_safe_offer_references(
    pilot_payload: dict,
) -> None:
    seen: dict[tuple[str, str], dict] = {}
    for bundle in pilot_payload["bundles"].values():
        projections = [bundle["entity"], *bundle["sections"]]
        for projection in projections:
            assert set(projection) == PUBLIC_PROJECTION_KEYS
            assert not (_mapping_keys(projection) & PRIVATE_PROJECTION_KEYS), (
                projection["id"],
                sorted(_mapping_keys(projection) & PRIVATE_PROJECTION_KEYS),
            )
            seen[(bundle["language"], projection["id"])] = projection

    for (language, entity_id), projection in seen.items():
        expected_code = PUBLIC_OFFER_CODES.get(entity_id)
        if expected_code is None:
            assert not projection["offer"], (language, entity_id)
            continue

        offer = projection["offer"]
        assert set(offer) == {"product_code", "store_path", "label"}
        assert offer["product_code"] == expected_code
        store_prefix = "/en/store/" if language == "en" else "/store/"
        assert offer["store_path"] == (
            f"{store_prefix}#c99-product-code-{expected_code}"
        )
        assert offer["label"].strip()


def test_cuisine_owns_the_three_public_pilot_sections(pilot_payload: dict) -> None:
    cuisine = pilot_payload["bundles"]["/museum/japanese-culinary-science/"]
    assert [section["id"] for section in cuisine["sections"]] == [
        "hub-japanese-food-science",
        "hub-japanese-ingredients",
        "hub-japanese-techniques",
    ]
    expected_section_ids = {
        "hub-japanese-food-science": "japanese-food-science",
        "hub-japanese-ingredients": "japanese-premium-ingredients",
        "hub-japanese-techniques": "japanese-culinary-techniques",
    }
    for section in cuisine["sections"]:
        assert section["seo"]["route_mode"] == "section"
        assert section["seo"]["owner_entity_id"] == "cuisine-japanese-washoku"
        assert section["seo"]["section_id"] == expected_section_ids[section["id"]]


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
