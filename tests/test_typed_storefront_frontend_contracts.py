from __future__ import annotations

import json
import shutil
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
CONSUMER = PLUGIN / "includes" / "class-complete99-consumer.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
MUSEUM = PLUGIN / "includes" / "class-complete99-culinary-museum-frontend.php"
PUBLIC_CSS = PLUGIN / "assets" / "css" / "public.css"


def _read(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def _between(source: str, start: str, end: str) -> str:
    return source.split(start, 1)[1].split(end, 1)[0]


def test_store_card_uses_a_typed_food_or_equipment_contract() -> None:
    consumer = _read(CONSUMER)
    card = _between(
        consumer,
        "private static function render_store_product_card",
        "private static function render_related_store_products",
    )

    for meta_key in (
        "_complete99_product_kind",
        "_complete99_product_model_he",
        "_complete99_product_model_en",
        "_complete99_product_material_he",
        "_complete99_product_material_en",
        "_complete99_product_dimensions_he",
        "_complete99_product_dimensions_en",
        "_complete99_product_care_he",
        "_complete99_product_care_en",
        "_complete99_product_safety_he",
        "_complete99_product_safety_en",
    ):
        assert meta_key in card

    assert "'equipment' === $product_kind ? 'equipment' : 'food'" in card
    assert "<?php if ( $is_equipment ) : ?>" in card
    for label in (
        "Product kind",
        "Model or format",
        "Material",
        "Dimensions",
        "Care",
        "Safety",
        "Fulfilment",
    ):
        assert label in card
    assert "Equipment guide" in card or "Open equipment guide" in card

    food_branch = card.split("<?php else : ?>", 1)[1]
    for existing_food_label in (
        "Net quantity",
        "Ingredients",
        "Allergens",
        "Storage",
        "Pickup",
    ):
        assert existing_food_label in food_branch


def test_store_copy_and_filters_cover_food_and_equipment() -> None:
    consumer = _read(CONSUMER)
    frontend = _read(FRONTEND)

    assert "Food ingredients and kitchen equipment" in consumer
    assert "חומרי גלם וציוד למטבח" in consumer
    assert "'equipment'       => $is_he ? 'ציוד למטבח'" in consumer
    assert "Complete99 food ingredients and kitchen equipment" in frontend


def test_product_schema_emits_kind_appropriate_properties() -> None:
    frontend = _read(FRONTEND)
    schema = _between(
        frontend,
        "private static function store_product_schema",
        "private static function verified_recipe_schema",
    )

    assert "'equipment' === $product_kind ? 'equipment' : 'food'" in schema
    assert "$property_rows = $is_equipment" in schema
    assert "'additionalProperty' => $additional_properties" in schema
    assert "'' === trim( (string) $property_row[1] )" in schema
    for label in (
        "Product kind",
        "Model or format",
        "Material",
        "Dimensions",
        "Care",
        "Safety",
        "Fulfilment",
        "Ingredients",
        "Allergens",
        "Storage",
        "Pickup",
    ):
        assert label in schema


@pytest.mark.skipif(not shutil.which("php"), reason="PHP is required")
def test_product_schema_runtime_separates_food_and_equipment_facts() -> None:
    frontend_path = json.dumps(FRONTEND.as_posix())
    script = f"""
define('ABSPATH', __DIR__);
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function sanitize_html_class($value) {{ return sanitize_key($value); }}
function absint($value) {{ return abs((int) $value); }}
function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function get_option($key, $default = false) {{ return 'kg'; }}
function get_woocommerce_currency() {{ return 'ILS'; }}
function wp_get_attachment_image_url($image_id, $size) {{
    return 'https://complete99.example/images/product.webp';
}}

class Complete99_Commerce {{
    const NAME_HE = '_complete99_product_name_he';
    const NAME_EN = '_complete99_product_name_en';
    const DESCRIPTION_HE = '_complete99_product_description_he';
    const DESCRIPTION_EN = '_complete99_product_description_en';
    const INGREDIENTS_HE = '_complete99_product_ingredients_he';
    const INGREDIENTS_EN = '_complete99_product_ingredients_en';
    const ALLERGENS_HE = '_complete99_product_allergens_he';
    const ALLERGENS_EN = '_complete99_product_allergens_en';
    const STORAGE_HE = '_complete99_product_storage_he';
    const STORAGE_EN = '_complete99_product_storage_en';
    const FULFILMENT_HE = '_complete99_product_fulfilment_he';
    const FULFILMENT_EN = '_complete99_product_fulfilment_en';
    public static function storefront_product_url($product_code, $lang, $filter = 'all') {{
        return 'https://complete99.example/en/store/?product-page=3#c99-product-code-' . $product_code;
    }}
}}
class Complete99_Live_Catalog {{
    const META_WEIGHT_MIN_KG = '_complete99_live_catalog_weight_min_kg';
    const META_WEIGHT_MAX_KG = '_complete99_live_catalog_weight_max_kg';
    public static function relations_for_product_code($product_code) {{ return array(); }}
}}
class C99_Product {{
    public function get_image_id() {{ return 50; }}
    public function get_sku() {{ return 'product-test'; }}
    public function get_weight() {{ return '0.250'; }}
    public function get_price() {{ return '89.00'; }}
    public function is_in_stock() {{ return true; }}
}}
function wc_get_product($product_id) {{ return new C99_Product(); }}

$c99_kind = 'food';
function get_post_meta($product_id, $key, $single = true) {{
    global $c99_kind;
    $common = array(
        '_complete99_product_name_en' => 'Test product',
        '_complete99_product_description_en' => 'Verified product description.',
        '_complete99_catalog_product_code' => 'product-test',
        '_complete99_product_fulfilment_en' => 'Pickup in Tel Aviv.',
        '_complete99_live_catalog_package_en' => 'One item',
    );
    if (isset($common[$key])) {{ return $common[$key]; }}
    if ('_complete99_product_kind' === $key) {{ return $c99_kind; }}
    if ('food' === $c99_kind && '_complete99_live_catalog_weight_min_kg' === $key) {{ return '0.050'; }}
    if ('food' === $c99_kind && '_complete99_live_catalog_weight_max_kg' === $key) {{ return '0.060'; }}
    if ('equipment' === $c99_kind) {{
        $equipment = array(
            '_complete99_product_model_en' => 'Pro large',
            '_complete99_product_material_en' => 'Stainless steel',
            '_complete99_product_dimensions_en' => '26 x 11 cm',
            '_complete99_product_care_en' => 'Hand wash and dry.',
            '_complete99_product_safety_en' => 'Handle the sharp surface carefully.',
        );
        return isset($equipment[$key]) ? $equipment[$key] : '';
    }}
    $food = array(
        '_complete99_product_ingredients_en' => 'Fresh wasabi rhizome.',
        '_complete99_product_allergens_en' => 'Check the supplier label.',
        '_complete99_product_storage_en' => 'Keep refrigerated.',
    );
    return isset($food[$key]) ? $food[$key] : '';
}}

require {frontend_path};
$method = new ReflectionMethod('Complete99_Frontend', 'store_product_schema');
$method->setAccessible(true);
$food = $method->invoke(null, 1, 'en', 'all');
$c99_kind = 'equipment';
$equipment = $method->invoke(null, 2, 'en', 'all');
echo json_encode(
    array('food' => $food, 'equipment' => $equipment),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=20,
        check=True,
    )
    result = json.loads(completed.stdout)
    food_names = {
        row["name"] for row in result["food"]["additionalProperty"]
    }
    equipment_names = {
        row["name"] for row in result["equipment"]["additionalProperty"]
    }
    assert food_names == {"Ingredients", "Allergens", "Storage", "Pickup"}
    assert equipment_names == {
        "Product kind",
        "Model or format",
        "Material",
        "Dimensions",
        "Care",
        "Safety",
        "Fulfilment",
    }
    assert result["food"]["weight"] == {
        "@type": "QuantitativeValue",
        "minValue": 0.05,
        "maxValue": 0.06,
        "unitCode": "KGM",
    }
    assert result["equipment"]["weight"] == {
        "@type": "QuantitativeValue",
        "value": 0.25,
        "unitCode": "KGM",
    }
    expected_url = (
        "https://complete99.example/en/store/?product-page=3"
        "#c99-product-code-product-test"
    )
    assert result["food"]["@id"] == expected_url
    assert result["food"]["offers"]["url"] == expected_url
    assert result["equipment"]["@id"] == expected_url
    assert result["equipment"]["offers"]["url"] == expected_url


def test_museum_accessibility_and_localization_contracts_are_explicit() -> None:
    museum = _read(MUSEUM)
    css = _read(PUBLIC_CSS)

    assert "self::render_facts( $section, 4 )" in museum
    assert "self::render_connections( $section, 4 )" in museum
    assert "self::render_sources( $section, 4 )" in museum
    assert "4 <= $heading_level ? 'h4'" in museum
    assert "'he' === self::$bundle['language'] ? 'מקור ' : 'Source '" in museum
    assert 'sizes="<?php echo esc_attr( $sizes ); ?>"' in museum

    for internal_label in (
        "Hubs and spokes",
        "Open connected entity",
        "Taxonomy",
        "How this entity is organized",
        "Knowledge graph",
        "Topic clusters",
        "Verify SKU label",
        "ישות קולינרית",
        "מכיר בישות",
    ):
        assert internal_label not in museum

    for label_key in (
        "aroma-and-pungency",
        "isothiocyanates",
        "isothiocyanate",
        "professional-equipment",
        "japanese-professional-equipment",
        "japanese-tools",
        "wasabi-tools",
        "graters",
        "fresh-wasabi-preparation",
        "wasabi-grater",
        "oroshi",
        "hagane-zame",
        "fresh-wasabi",
    ):
        assert f"'{label_key}' => array(" in museum

    assert ".c99-culinary-museum a:focus-visible" in css
    assert "outline: 3px solid #0a2f22" in css
    assert ".c99-culinary-museum .c99-museum-section > h4" in css


def test_museum_article_and_owned_entity_schema_are_connected() -> None:
    museum = _read(MUSEUM)
    schema = _between(
        museum,
        "private static function schema_graph",
        "private static function request_paths",
    )

    assert "if ( 'Article' === $page_type )" in schema
    assert "$page['author']" in schema
    assert "$page['publisher']" in schema
    assert "$page['datePublished']" in schema
    assert "'@type' => 'Organization'" in schema
    assert "array( 'ChemicalSubstance', 'DefinedTerm', 'CollectionPage' )" in schema
    assert "$page['mainEntity'] = $owned_entity_references" in schema
    assert "$page['about'] = $owned_entity_references" in schema


@pytest.mark.skipif(not shutil.which("php"), reason="PHP is required")
def test_museum_schema_runtime_emits_article_and_owned_section_nodes() -> None:
    museum_path = json.dumps(MUSEUM.as_posix())
    script = f"""
define('ABSPATH', __DIR__);
function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function sanitize_html_class($value) {{
    return preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $value));
}}
function esc_url_raw($value, $protocols = null) {{ return (string) $value; }}
require {museum_path};
$bundle = array(
    'language' => 'en',
    'canonical_url' => 'https://complete99.example/en/knowledge/wasabi-aitc-pungency/',
    'entity' => array(
        'name' => 'Wasabi pungency and AITC',
        'summary' => 'A sourced culinary science guide.',
        'seo' => array(
            'title' => 'Wasabi pungency and AITC',
            'h1' => 'Wasabi pungency and AITC',
            'meta_description' => 'A sourced culinary science guide.',
            'schema_type' => 'Article',
            'visible_breadcrumbs' => array(
                array('label' => 'Home', 'path' => '/en/'),
                array('label' => 'Guide', 'path' => '/en/knowledge/wasabi-aitc-pungency/'),
            ),
        ),
        'sources' => array(
            array('url' => 'https://example.org/article'),
        ),
        'visual' => array(),
        'trust' => array('substantive_updated_at' => '2026-08-06'),
        'reviewed_at' => '2026-08-05',
    ),
    'sections' => array(
        array(
            'name' => 'Allyl isothiocyanate',
            'summary' => 'The principal volatile pungency molecule in this guide.',
            'slug' => 'allyl-isothiocyanate',
            'seo' => array(
                'schema_type' => 'ChemicalSubstance',
                'section_id' => 'allyl-isothiocyanate',
            ),
            'sources' => array(
                array('url' => 'https://example.org/article'),
            ),
        ),
		array(
			'name' => 'Professional Japanese equipment',
			'summary' => 'Tools organized by preparation task and material.',
			'slug' => 'japanese-professional-equipment',
			'seo' => array(
				'schema_type' => 'CollectionPage',
				'section_id' => 'japanese-professional-equipment',
			),
			'sources' => array(
				array('url' => 'https://example.org/equipment'),
			),
		),
    ),
);
$method = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'schema_graph'
);
$method->setAccessible(true);
echo json_encode(
    $method->invoke(null, $bundle),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=20,
        check=True,
    )
    schema = json.loads(completed.stdout)
    graph = schema["@graph"]
    article = next(node for node in graph if node["@type"] == "Article")
    molecule = next(
        node for node in graph if node["@type"] == "ChemicalSubstance"
    )
    equipment = next(
        node for node in graph if node["@type"] == "CollectionPage"
    )
    assert article["author"] == {
        "@id": "https://complete99.example/#organization"
    }
    assert article["publisher"] == article["author"]
    assert article["datePublished"] == "2026-08-05"
    assert article["dateModified"] == "2026-08-06"
    assert article["mainEntity"] == [
        {"@id": molecule["@id"]},
        {"@id": equipment["@id"]},
    ]
    assert article["about"] == article["mainEntity"]
    assert molecule["@id"].endswith("#allyl-isothiocyanate")
    assert molecule["isPartOf"] == {"@id": article["@id"]}
    assert equipment["@id"].endswith("#japanese-professional-equipment")
    assert equipment["isPartOf"] == {"@id": article["@id"]}
