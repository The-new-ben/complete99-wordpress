from __future__ import annotations

import hashlib
import json
import re
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
    "hub-japanese-foundations-lab",
    "hub-japanese-equipment",
    "hub-japanese-food-science",
    "hub-japanese-ingredients",
    "hub-japanese-techniques",
    "guide-umami-synergy",
    "guide-wasabi-aitc",
    "ingredient-kombu",
    "ingredient-katsuobushi",
    "ingredient-kioke-shoyu",
    "ingredient-fresh-wasabi",
    "ingredient-kito-yuzu",
    "ingredient-hon-mirin",
    "molecule-allyl-isothiocyanate",
    "preparation-ichiban-dashi",
    "equipment-wasabi-grater",
}
PUBLIC_OFFER_CODES = {
    "ingredient-kombu": "product-rishiri-kombu-100g",
    "ingredient-katsuobushi": "product-honkarebushi-200g",
    "ingredient-kioke-shoyu": "product-yamaroku-tsurubishio-500ml",
    "ingredient-fresh-wasabi": "product-fresh-japanese-wasabi-250g",
    "ingredient-kito-yuzu": "product-kito-yuzu-juice-100ml",
    "equipment-wasabi-grater": "product-hagane-zame-large",
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
LAB_GROUP_MEMBERS = {
    "ingredients": [
        "ingredient-kombu",
        "ingredient-katsuobushi",
        "ingredient-kioke-shoyu",
        "ingredient-fresh-wasabi",
        "ingredient-kito-yuzu",
        "ingredient-hon-mirin",
    ],
    "food_science": [
        "guide-umami-synergy",
        "guide-wasabi-aitc",
        "molecule-allyl-isothiocyanate",
    ],
    "techniques": ["preparation-ichiban-dashi"],
    "equipment": ["equipment-wasabi-grater"],
}
LAB_COLLECTION_KEYS = {
    "schema",
    "key",
    "language",
    "translation_group_id",
    "canonical_path",
    "alternate_path",
    "approved_public",
    "groups",
    "members",
    "parity_member_ids",
}
LAB_MEMBER_KEYS = {
    "id",
    "group_id",
    "name",
    "summary",
    "entity_type",
    "canonical_path",
    "owner_entity_id",
    "route_mode",
    "approved_public",
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
    '/museum/japanese-culinary-science/foundations/',
    '/en/museum/japanese-culinary-science/foundations/',
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
    '/en/knowledge/umami-synergy-glutamate-imp/',
    '/knowledge/wasabi-aitc-pungency/',
    '/en/knowledge/wasabi-aitc-pungency/',
    '/knowledge/wasabi-grater-guide/',
    '/en/knowledge/wasabi-grater-guide/'
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
    assert len(pilot_payload["bundles"]) == 26
    public_standalone = {
        entity["id"]
        for entity in pilot_payload["registry"]["entities"]
        if entity["publication"]["public_page"]
        and entity["seo"]["route_mode"] == "standalone"
    }
    assert len(public_standalone) == 13
    assert len(pilot_payload["bundles"]) == 2 * len(public_standalone)
    for path, bundle in pilot_payload["bundles"].items():
        assert bundle["canonical_path"] == path
        assert bundle["canonical_url"] == "https://complete99.test" + path
        assert bundle["indexable"] is False
        assert bundle["language"] == ("en" if path.startswith("/en/") else "he")
        expected_bundle_keys = {
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
        if bundle["entity"]["id"] == "hub-japanese-foundations-lab":
            expected_bundle_keys.add("collection")
        assert set(bundle) == expected_bundle_keys
        entity = bundle["entity"]
        assert set(entity) == PUBLIC_PROJECTION_KEYS
        assert entity["search_index"] is False


def test_foundations_lab_collection_is_bilingual_presentation_only(
    pilot_payload: dict,
) -> None:
    he_bundle = pilot_payload["bundles"][
        "/museum/japanese-culinary-science/foundations/"
    ]
    en_bundle = pilot_payload["bundles"][
        "/en/museum/japanese-culinary-science/foundations/"
    ]
    expected_member_ids = [
        member_id
        for group_id in ("ingredients", "food_science", "techniques", "equipment")
        for member_id in LAB_GROUP_MEMBERS[group_id]
    ]

    for language, bundle, alternate_path in (
        (
            "he",
            he_bundle,
            "/en/museum/japanese-culinary-science/foundations/",
        ),
        (
            "en",
            en_bundle,
            "/museum/japanese-culinary-science/foundations/",
        ),
    ):
        collection = bundle["collection"]
        assert set(collection) == LAB_COLLECTION_KEYS
        assert collection["schema"] == "complete99-culinary-collection-public/v1"
        assert collection["key"] == "japanese-foundations-lab"
        assert collection["language"] == language
        assert collection["translation_group_id"] == (
            "collection-japanese-foundations-lab"
        )
        assert collection["canonical_path"] == bundle["canonical_path"]
        assert collection["alternate_path"] == alternate_path
        assert collection["approved_public"] is True
        assert [group["id"] for group in collection["groups"]] == [
            "ingredients",
            "food_science",
            "techniques",
            "equipment",
        ]
        assert all(set(group) == {"id", "label", "description"} for group in collection["groups"])
        assert [member["id"] for member in collection["members"]] == (
            expected_member_ids
        )
        assert collection["parity_member_ids"] == {
            "he": expected_member_ids,
            "en": expected_member_ids,
        }
        for member in collection["members"]:
            expected_keys = set(LAB_MEMBER_KEYS)
            if member["route_mode"] == "section":
                expected_keys.add("fragment")
                assert member["fragment"]
                assert member["owner_entity_id"] != member["id"]
            else:
                assert member["route_mode"] == "standalone"
                assert member["owner_entity_id"] == member["id"]
            assert set(member) == expected_keys
            assert member["approved_public"] is True

    for path, bundle in pilot_payload["bundles"].items():
        if "foundations/" not in path:
            assert "collection" not in bundle


def test_foundations_lab_visible_breadcrumbs_include_japanese_cuisine_parent(
    pilot_payload: dict,
) -> None:
    owner = next(
        entity
        for entity in pilot_payload["registry"]["entities"]
        if entity["id"] == "hub-japanese-foundations-lab"
    )
    breadcrumbs = owner["seo"]["visible_breadcrumbs"]
    assert [breadcrumb["key"] for breadcrumb in breadcrumbs] == [
        "home",
        "museum",
        "parent-cuisine-japanese-washoku",
        "current-hub-japanese-foundations-lab",
    ]
    assert breadcrumbs[2]["path"] == {
        "he": "/museum/japanese-culinary-science/",
        "en": "/en/museum/japanese-culinary-science/",
    }


def test_foundations_lab_does_not_reparent_or_take_member_intents(
    pilot_payload: dict,
) -> None:
    registry = pilot_payload["registry"]
    collection = registry["collections"][0]
    by_id = {entity["id"]: entity for entity in registry["entities"]}
    owner_id = collection["owner_entity_id"]
    member_ids = {
        member_id
        for members in collection["navigation"]["member_ids_by_group"].values()
        for member_id in members
    }
    assert owner_id == "hub-japanese-foundations-lab"
    assert by_id[owner_id]["parent_id"] == "cuisine-japanese-washoku"
    assert by_id[owner_id]["seo"]["canonical_path"] == collection["route"][
        "canonical_path"
    ]
    assert by_id[owner_id]["publication"]["search_index"] is False
    for member_id in member_ids:
        member = by_id[member_id]
        assert member["parent_id"] != owner_id
        assert member["seo"]["owner_entity_id"] != owner_id


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


def _public_copy_strings(projection: dict) -> list[str]:
    strings = [projection.get("name", ""), projection.get("summary", "")]
    strings.extend(
        profile.get("summary", "")
        for profile in projection.get("profiles", {}).values()
    )
    strings.extend(fact.get("statement", "") for fact in projection.get("facts", []))
    strings.extend(
        relation.get("note", "") for relation in projection.get("relations", [])
    )
    for link in projection.get("internal_links", []):
        strings.extend((link.get("anchor", ""), link.get("context", "")))
    for observation in projection.get("market_context", []):
        strings.extend(
            (observation.get("label", ""), observation.get("scope_note", ""))
        )
    offer = projection.get("offer", {})
    if isinstance(offer, dict):
        strings.append(offer.get("label", ""))
    strings.extend(projection.get("safety_notes", []))
    trust = projection.get("trust", {})
    strings.extend(
        (trust.get("research_method", ""), trust.get("next_review_trigger", ""))
    )
    return strings


def test_public_copy_does_not_expose_internal_delivery_or_seo_jargon(
    pilot_payload: dict,
) -> None:
    forbidden = (
        "head intent",
        "intent owner",
        "search intent",
        "pilot",
        "project",
        "sku",
        "mapped",
        "taxonomy",
        "knowledge graph",
        "topic cluster",
        "hubs and spokes",
        "\u05e4\u05d9\u05d9\u05dc\u05d5\u05d8",
        "\u05e4\u05e8\u05d5\u05d9\u05e7\u05d8",
        "\u05db\u05d5\u05d5\u05e0\u05ea \u05d7\u05d9\u05e4\u05d5\u05e9",
        "\u05d1\u05e2\u05dc \u05d4\u05db\u05d5\u05d5\u05e0\u05d4",
        "\u05d4\u05d9\u05e9\u05d5\u05ea",
        "\u05d9\u05e9\u05d5\u05d9\u05d5\u05ea",
        "\u05de\u05de\u05d5\u05e4\u05d4",
    )
    for path, bundle in pilot_payload["bundles"].items():
        for projection in (bundle["entity"], *bundle["sections"]):
            public_copy = "\n".join(_public_copy_strings(projection)).casefold()
            for marker in forbidden:
                assert marker.casefold() not in public_copy, (
                    path,
                    projection["id"],
                    marker,
                )
            assert re.search(r"\b(?:entity|entities)\b", public_copy) is None, (
                path,
                projection["id"],
            )


def test_public_projections_expose_only_approved_safe_offer_references(
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


def test_cuisine_owns_the_four_public_pilot_sections(pilot_payload: dict) -> None:
    cuisine = pilot_payload["bundles"]["/museum/japanese-culinary-science/"]
    assert [section["id"] for section in cuisine["sections"]] == [
        "hub-japanese-equipment",
        "hub-japanese-food-science",
        "hub-japanese-ingredients",
        "hub-japanese-techniques",
    ]
    expected_section_ids = {
        "hub-japanese-equipment": "japanese-professional-equipment",
        "hub-japanese-food-science": "japanese-food-science",
        "hub-japanese-ingredients": "japanese-premium-ingredients",
        "hub-japanese-techniques": "japanese-culinary-techniques",
    }
    for section in cuisine["sections"]:
        assert section["seo"]["route_mode"] == "section"
        assert section["seo"]["owner_entity_id"] == "cuisine-japanese-washoku"
        assert section["seo"]["section_id"] == expected_section_ids[section["id"]]


def test_wasabi_guide_owns_the_aitc_molecule_section(pilot_payload: dict) -> None:
    guide = pilot_payload["bundles"]["/knowledge/wasabi-aitc-pungency/"]
    assert [section["id"] for section in guide["sections"]] == [
        "molecule-allyl-isothiocyanate"
    ]
    molecule = guide["sections"][0]
    assert molecule["seo"]["route_mode"] == "section"
    assert molecule["seo"]["owner_entity_id"] == "guide-wasabi-aitc"
    assert molecule["seo"]["section_id"] == "allyl-isothiocyanate"
    assert molecule["seo"]["canonical_path"] == guide["entity"]["seo"][
        "canonical_path"
    ]


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


def test_wasabi_slice_uses_official_sources_and_independently_gated_offers(
    pilot_payload: dict,
) -> None:
    registry = pilot_payload["registry"]
    sources = registry["sources"]
    by_id = {entity["id"]: entity for entity in registry["entities"]}

    yamamoto = sources["yamamoto-haganezame-spec"]
    assert yamamoto["type"] == "official_business"
    assert yamamoto["publisher"] == "Yamamoto Foods Co., Ltd."
    assert yamamoto["url"] == (
        "https://www.yamamotofoods.co.jp/haganezame/jp/spec/"
    )

    wasabi = by_id["ingredient-fresh-wasabi"]
    grater = by_id["equipment-wasabi-grater"]
    assert wasabi["commerce"]["woo_product_code"] == (
        "product-fresh-japanese-wasabi-250g"
    )
    assert grater["commerce"]["woo_product_code"] == "product-hagane-zame-large"
    for entity in (wasabi, grater):
        assert entity["commerce"]["public_offer_allowed"] is True
        assert entity["commerce"]["state"] == "active_offer"
        assert entity["commerce"]["business_model"]["pricing_state"] == (
            "approved_sell_price"
        )

    grater_fact_sources = {
        source_id
        for fact in grater["facts"]
        if fact["public_safe"]
        for source_id in fact["source_ids"]
    }
    assert "yamamoto-haganezame-spec" in grater_fact_sources
    assert any(
        relation["target_id"] == "equipment-wasabi-grater"
        and relation["public_safe"]
        and "yamamoto-haganezame-spec" in relation["source_ids"]
        for relation in wasabi["relations"]
    )
