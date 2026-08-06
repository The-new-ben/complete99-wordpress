<?php

/**
 * Complete99 market-benchmark product seeds.
 *
 * Private evaluation data only. These are not supplier offers, launch prices,
 * procurement costs or public stock promises.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_classification_rules = array(
	'packaged_shelf_stable' => array(
		'resale_candidate' => true,
		'temperature_zone' => 'ambient_pending_label',
		'required_gates'   => array( 'supplier', 'gtin', 'label', 'ingredients', 'allergens', 'nutrition_panel', 'lot_expiry', 'storage', 'kosher_document', 'tax', 'media', 'shipping', 'returns', 'procurement_price', 'margin' ),
	),
	'bakery_short_shelf_life' => array(
		'resale_candidate' => false,
		'temperature_zone' => 'bakery_route_pending',
		'required_gates'   => array( 'bakery_supplier', 'production_date', 'shelf_life', 'label', 'ingredients', 'allergens', 'storage', 'delivery_window', 'returns', 'procurement_price', 'margin' ),
	),
	'fresh_variable_weight' => array(
		'resale_candidate' => false,
		'temperature_zone' => 'fresh_route_pending',
		'required_gates'   => array( 'fresh_supplier', 'quality_grade', 'country_of_origin', 'variable_weight', 'packaging', 'label', 'storage', 'waste_factor', 'returns', 'procurement_price', 'margin' ),
	),
	'regulated_category' => array(
		'resale_candidate' => false,
		'temperature_zone' => 'regulated_route_pending',
		'required_gates'   => array( 'approved_supplier', 'regulatory_price_check', 'grade', 'traceability', 'label', 'storage', 'returns', 'procurement_price', 'margin' ),
	),
	'chilled_or_frozen_sensitive' => array(
		'resale_candidate' => false,
		'temperature_zone' => 'cold_chain_pending',
		'required_gates'   => array( 'approved_food_supplier', 'batch_traceability', 'cold_chain', 'temperature_log', 'label', 'allergens', 'kosher_document', 'packaging', 'returns', 'procurement_price', 'waste_factor', 'margin' ),
	),
	'professional_equipment' => array(
		'resale_candidate' => true,
		'temperature_zone' => 'non_food_equipment',
		'required_gates'   => array( 'supplier', 'model', 'material', 'dimensions', 'care', 'safety', 'food_contact', 'tax', 'media', 'shipping', 'returns', 'procurement_price', 'margin' ),
	),
);

$c99_product = static function (
	$product_code,
	$ingredient_code,
	$name_he,
	$name_en,
	$brand,
	$classification,
	$package_he,
	$package_en,
	$observed_price,
	$observed_low,
	$observed_high,
	$normalized_amount,
	$normalized_unit,
	$evaluation_price,
	$source_url,
	$source_provider,
	$source_updated_at,
	$linked_dish_ids = array(),
	$attention_codes = array(),
	$checked_at = '2026-07-31',
	$product_kind = 'food',
	$source_price_evidence = array(),
	$equipment_specification = null
) use ( $c99_classification_rules ) {
	$rules = $c99_classification_rules[ $classification ];
	$product_kind = 'equipment' === $product_kind ? 'equipment' : 'food';
	$market_observation = array(
		'source_provider'    => $source_provider,
		'source_url'         => $source_url,
		'checked_at'         => $checked_at,
		'source_updated_at'  => $source_updated_at,
		'observed_price_ils' => (float) $observed_price,
		'range_low_ils'      => (float) $observed_low,
		'range_high_ils'     => (float) $observed_high,
		'price_scope'        => 'consumer_retail_observation',
		'source_price'       => array(),
		'fx_conversion'      => array(),
	);
	if ( isset( $source_price_evidence['source_price'], $source_price_evidence['fx_conversion'] ) ) {
		$market_observation['source_price']  = $source_price_evidence['source_price'];
		$market_observation['fx_conversion'] = $source_price_evidence['fx_conversion'];
	}

	return array(
		'schema'                     => 'complete99-catalog-product-seed/v1',
		'product_code'               => $product_code,
		'ingredient_code'            => $ingredient_code,
		'product_kind'               => $product_kind,
		'name'                       => array( 'he' => $name_he, 'en' => $name_en ),
		'brand_reference'            => $brand,
		'gtin'                       => '',
		'gtin_status'                => 'supplier_record_required',
		'package_label'              => array( 'he' => $package_he, 'en' => $package_en ),
		'classification'             => $classification,
		'temperature_zone'           => $rules['temperature_zone'],
		'market_observation'         => $market_observation,
		'normalized_market_price'    => array( 'amount' => (float) $normalized_amount, 'unit' => $normalized_unit ),
		'evaluation_price_ils'       => (float) $evaluation_price,
		'evaluation_price_scope'     => 'private_benchmark_only',
		'evaluation_stock'           => 1,
		'evaluation_stock_scope'     => 'private_evaluation_only',
		'public_sale_eligible'       => false,
		'sale_state'                 => 'held_until_acceptance',
		'resale_candidate'           => (bool) $rules['resale_candidate'],
		'procurement_cost_ils'       => null,
		'target_margin_percent'      => null,
		'waste_factor_percent'       => null,
		'ingredient_statement'       => 'food' === $product_kind ? array( 'status' => 'supplier_label_required' ) : null,
		'allergen_statement'         => 'food' === $product_kind ? array( 'status' => 'supplier_label_required' ) : null,
		'nutrition_panel'            => 'food' === $product_kind ? array( 'status' => 'supplier_label_required' ) : null,
		'kosher_certificate'         => 'food' === $product_kind ? array( 'status' => 'supplier_document_required' ) : null,
		'equipment_specification'    => 'equipment' === $product_kind ? $equipment_specification : null,
		'image_asset'                => '',
		'image_state'                => 'evaluation_asset_pending_binding',
		'regulatory_attention_codes' => array_values( $attention_codes ),
		'relations'                  => array(
			'verified_ingredient_codes' => array( $ingredient_code ),
			'verified_dish_ids'         => array_values( $linked_dish_ids ),
			'candidate_dish_ids'        => array(),
		),
		'acceptance_gates'           => array_fill_keys( $rules['required_gates'], false ),
	);
};

$c99_products = array(
	$c99_product(
		'product-tahini-500g', 'ingredient-tahini', 'טחינה גולמית 500 גרם', 'Raw tahini 500 g', 'Achva benchmark',
		'packaged_shelf_stable', '500 גרם', '500 g', 10.90, 10.90, 10.90, 21.80, 'ILS_per_kg', 12.90,
		'https://www.pricez.co.il/Product/5640', 'pricez', '2026-07-31', array( 'menu-reference-sabich' ), array( 'sesame_label_review' )
	),
	$c99_product(
		'product-amba-500g', 'ingredient-amba', 'עמבה 500 גרם', 'Amba 500 g', 'Hanamal benchmark',
		'packaged_shelf_stable', '500 גרם', '500 g', 13.90, 13.90, 13.90, 27.80, 'ILS_per_kg', 14.90,
		'https://www.pricez.co.il/Price/12172/2970621656/1517/%D7%A4%D7%A8%D7%A9-%D7%9E%D7%A8%D7%A7%D7%98-%D7%99%D7%94%D7%95%D7%93', 'pricez', '2026-07-31', array( 'menu-reference-sabich' )
	),
	$c99_product(
		'product-hot-sauce-60ml', 'ingredient-hot-sauce', 'רוטב חריף 60 מ״ל', 'Hot sauce 60 ml', 'Tabasco benchmark',
		'packaged_shelf_stable', '60 מ״ל', '60 ml', 10.80, 10.80, 10.80, 180.00, 'ILS_per_litre', 12.90,
		'https://www.pricez.co.il/Product/46339/%D7%A8%D7%95%D7%98%D7%91-%D7%98%D7%91%D7%A1%D7%A7%D7%95-%D7%97%D7%9C%D7%A4%D7%99%D7%A0%D7%99%D7%95-%D7%99%D7%A8%D7%95%D7%A7-60-%D7%9E%D7%9C', 'pricez', '2026-06-16'
	),
	$c99_product(
		'product-pita-12x50g', 'ingredient-pita', 'פיתות 12 יחידות', 'Pita bread, 12 units', 'Pita Express benchmark',
		'bakery_short_shelf_life', '12 יחידות, 50 גרם ליחידה', '12 units, 50 g each', 12.90, 12.90, 17.90, 21.50, 'ILS_per_kg_low_observed', 14.90,
		'https://prices.pricez.co.il/product-27569-%D7%A4%D7%99%D7%AA%D7%95%D7%AA-%D7%91%D7%99%D7%A1-%D7%A4%D7%99%D7%AA%D7%94-%D7%90%D7%A7%D7%A1%D7%A4%D7%A8%D7%A1-12-50-%D7%92%D7%A8%D7%9D-city-738-%D7%9E%D7%95%D7%93%D7%99%D7%A2%D7%99%D7%9F-%D7%9E%D7%9B%D7%91%D7%99%D7%9D-%D7%A8%D7%A2%D7%95%D7%AA.html', 'pricez', '2026-07-18', array(), array( 'gluten_label_review' )
	),
	$c99_product(
		'product-aubergine-1kg', 'ingredient-aubergine', 'חציל טרי במשקל', 'Fresh aubergine by weight', 'Fresh produce benchmark',
		'fresh_variable_weight', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 3.90, 3.90, 7.90, 6.90, 'ILS_per_kg_evaluation', 6.90,
		'https://prices.pricez.co.il/product-65787-%D7%97%D7%A6%D7%99%D7%9C-%D7%98%D7%A8%D7%99-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-126-%D7%90%D7%A9%D7%A7%D7%9C%D7%95%D7%9F.html', 'pricez', '2026-07-28', array( 'menu-reference-sabich', 'menu-reference-sabtucha' )
	),
	$c99_product(
		'product-eggs-l-12', 'ingredient-egg', 'ביצים טריות L, 12 יחידות', 'Fresh size L eggs, 12 units', 'Retail regulated-category benchmark',
		'regulated_category', '12 יחידות', '12 units', 14.24, 14.24, 14.24, 1.19, 'ILS_per_unit', 14.24,
		'https://chp.co.il/%D7%AA%D7%9C%2B%D7%90%D7%91%D7%99%D7%91/9000/5000/%D7%9E%D7%91%D7%A6%D7%A2%D7%99%D7%9D%2B%D7%91%D7%A8%D7%A9%D7%AA%2Byellow%2B%D7%A9%D7%9C%2B%D7%91%D7%99%D7%A6%D7%99%D7%9D%2B%D7%98%D7%A8%D7%99%D7%95%D7%AA%2B%D7%92%D7%93%D7%95%D7%9C%2C%2B%2B%2B12%2B%D7%99%D7%97%D7%99%D7%93%D7%95%D7%AA%2C%2B%28%D7%99%D7%A6%D7%A8%D7%9F%2F%D7%9E%D7%95%D7%AA%D7%92%3A%2B%D7%9E%D7%9F%2B%D7%94%D7%98%D7%91%D7%A2%2C%2B%D7%91%D7%A8%D7%A7%D7%95%D7%93%3A%2B7290011778118%29%2B%D7%91%D7%90%D7%99%D7%96%D7%95%D7%A8%2B%D7%AA%D7%9C%2B%D7%90%D7%91%D7%99%D7%91/7290027600007_7290011778118', 'chp', '2026-07-31', array( 'menu-reference-sabich', 'menu-reference-shakshuka', 'menu-reference-aja', 'menu-reference-sabtucha' ), array( 'eggs_allergen', 'regulated_price_check' )
	),
	$c99_product(
		'product-potato-white-1kg', 'ingredient-potato', 'תפוח אדמה לבן במשקל', 'White potato by weight', 'Fresh produce benchmark',
		'fresh_variable_weight', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 4.90, 4.90, 4.90, 5.90, 'ILS_per_kg_evaluation', 5.90,
		'https://www.pricez.co.il/Product/279285/%D7%AA%D7%A4%D7%95%D7%97-%D7%90%D7%93%D7%9E%D7%94-%D7%9C%D7%91%D7%9F-%D7%90%D7%A8%D7%95%D7%96-%D7%97%D7%91%D7%9C-%D7%9E%D7%A2%D7%95%D7%9F-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C', 'pricez', '2026-07-26', array( 'menu-reference-sabich', 'menu-reference-sabtucha' )
	),
	$c99_product(
		'product-tomato-1kg', 'ingredient-tomato', 'עגבנייה טרייה במשקל', 'Fresh tomato by weight', 'Fresh produce benchmark',
		'fresh_variable_weight', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 4.90, 4.90, 11.90, 6.90, 'ILS_per_kg_evaluation', 6.90,
		'https://prices.pricez.co.il/product-65717-%D7%A2%D7%92%D7%91%D7%A0%D7%99%D7%95%D7%AA-%D7%98%D7%A8%D7%99%D7%95%D7%AA-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-363-%D7%94%D7%95%D7%93-%D7%94%D7%A9%D7%A8%D7%95%D7%9F.html', 'pricez', '2026-07-27', array( 'menu-reference-shakshuka', 'menu-reference-fish-patties' )
	),
	$c99_product(
		'product-cucumber-1kg', 'ingredient-cucumber', 'מלפפון טרי במשקל', 'Fresh cucumber by weight', 'Fresh produce benchmark',
		'fresh_variable_weight', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 4.90, 4.90, 8.90, 6.90, 'ILS_per_kg_evaluation', 6.90,
		'https://prices.pricez.co.il/product-65716-%D7%9E%D7%9C%D7%A4%D7%A4%D7%95%D7%9F-%D7%98%D7%A8%D7%99-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-1142-%D7%A7%D7%A8%D7%99%D7%AA-%D7%90%D7%AA%D7%90.html', 'pricez', '2026-07-27'
	),
	$c99_product(
		'product-onion-dry-1kg', 'ingredient-onion', 'בצל יבש במשקל', 'Dry onion by weight', 'Fresh produce benchmark',
		'fresh_variable_weight', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 2.90, 2.90, 7.90, 4.90, 'ILS_per_kg_evaluation', 4.90,
		'https://prices.pricez.co.il/product-65726-%D7%91%D7%A6%D7%9C-%D7%99%D7%91%D7%A9-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-622-%D7%9B%D7%A4%D7%A8-%D7%A1%D7%91%D7%90.html', 'pricez', '2026-07-28'
	),
	$c99_product(
		'product-parsley-100g', 'ingredient-parsley', 'פטרוזיליה 100 גרם', 'Parsley 100 g', 'Fresh herb benchmark',
		'fresh_variable_weight', '100 גרם', '100 g', 5.90, 5.90, 5.90, 59.00, 'ILS_per_kg', 6.90,
		'https://chp.co.il/%D7%A4%D7%A8%D7%93%D7%A1%2B%D7%97%D7%A0%D7%94-%D7%9B%D7%A8%D7%9B%D7%95%D7%A8%2B/9000/7800/%D7%9E%D7%91%D7%A6%D7%A2%D7%99%D7%9D%2B%D7%91%D7%A8%D7%A9%D7%AA%2B%D7%A8%D7%9E%D7%99%2B%D7%9C%D7%95%D7%99%2B%D7%A9%D7%9C%2B%D7%A4%D7%98%D7%A8%D7%95%D7%96%D7%99%D7%9C%D7%99%D7%94%2C%2B%2B%2C%2B%28%D7%99%D7%A6%D7%A8%D7%9F%2F%D7%9E%D7%95%D7%AA%D7%92%3A%2B%D7%A9%D7%93%D7%95%D7%AA%2B%D7%91%D7%A2%D7%9E%D7%A7%2C%2B%D7%91%D7%A8%D7%A7%D7%95%D7%93%3A%2B7290017487601%29%2B%D7%91%D7%90%D7%99%D7%96%D7%95%D7%A8%2B%D7%A4%D7%A8%D7%93%D7%A1%2B%D7%97%D7%A0%D7%94-%D7%9B%D7%A8%D7%9B%D7%95%D7%A8%2B/temp_7290017487601', 'chp', '2026-07-31'
	),
	$c99_product(
		'product-chickpeas-dry-500g', 'ingredient-chickpea', 'גרגרי חומוס יבשים 500 גרם', 'Dry chickpeas 500 g', 'Sugart benchmark',
		'packaged_shelf_stable', '500 גרם', '500 g', 6.90, 6.90, 6.90, 13.80, 'ILS_per_kg', 8.90,
		'https://www.pricez.co.il/Product/59678/', 'pricez', '2026-07-18'
	),
	$c99_product(
		'product-beetroot-1kg', 'ingredient-beet', 'סלק אדום טרי במשקל', 'Fresh red beetroot by weight', 'Fresh produce benchmark',
		'fresh_variable_weight', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 2.90, 2.90, 7.90, 4.90, 'ILS_per_kg_evaluation', 4.90,
		'https://prices.pricez.co.il/product-65810-%D7%A1%D7%9C%D7%A7-%D7%90%D7%93%D7%95%D7%9D-%D7%98%D7%A8%D7%99-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-934-%D7%A0%D7%AA%D7%A0%D7%99%D7%94.html', 'pricez', '2026-07-19', array( 'menu-reference-beet-kubbeh' )
	),
	$c99_product(
		'product-bulgur-fine-500g', 'ingredient-bulgur', 'בורגול דק 500 גרם', 'Fine bulgur 500 g', 'Sugart benchmark',
		'packaged_shelf_stable', '500 גרם', '500 g', 4.70, 4.70, 4.70, 9.40, 'ILS_per_kg', 5.90,
		'https://chp.co.il/%D7%90%D7%91%D7%98%D7%99%D7%9F/9000/652/%D7%92%D7%A8%D7%99%D7%A9%D7%94%2B500.00%2B%D7%92%D7%A8%D7%9D%2C%2B%28%D7%A1%D7%A4%D7%A7%3A%2B%D7%A1%D7%95%D7%92%D7%AA%2C%2B%D7%91%D7%A8%D7%A7%D7%95%D7%93%3A%2B7290100701454%29/7290027600007_7290100701454', 'chp', '2026-07-29', array(), array( 'gluten_label_review' )
	),
	$c99_product(
		'product-couscous-1kg', 'ingredient-couscous', 'קוסקוס 1 ק״ג', 'Couscous 1 kg', 'Carrefour category benchmark',
		'packaged_shelf_stable', '1 ק״ג', '1 kg', 11.90, 11.90, 11.90, 11.90, 'ILS_per_kg', 12.90,
		'https://www.carrefour.co.il/categories/79647/products', 'carrefour', '2026-07-27', array(), array( 'gluten_label_review' )
	),
	$c99_product(
		'product-chicken-breast-1kg', 'ingredient-chicken-breast', 'חזה עוף טרי במשקל', 'Fresh chicken breast by weight', 'Shufersal benchmark',
		'chilled_or_frozen_sensitive', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 39.90, 39.90, 39.90, 44.90, 'ILS_per_kg_evaluation', 44.90,
		'https://prices.pricez.co.il/product-937604-%D7%97%D7%96%D7%94-%D7%A2%D7%95%D7%A3-%D7%98%D7%A8%D7%99-%D7%90%D7%A8%D7%95%D7%96-%D7%A9%D7%95%D7%A4%D7%A8%D7%A1%D7%9C-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-1306-%D7%AA%D7%9C-%D7%90%D7%91%D7%99%D7%91-%D7%99%D7%A4%D7%95.html', 'pricez', '2026-07-28', array( 'menu-reference-grilled-chicken' )
	),
	$c99_product(
		'product-breadcrumbs-500g', 'ingredient-breadcrumbs', 'פירורי לחם 500 גרם', 'Breadcrumbs 500 g', 'Hanamal benchmark',
		'packaged_shelf_stable', '500 גרם', '500 g', 6.90, 6.90, 9.90, 13.80, 'ILS_per_kg_low_observed', 8.90,
		'https://prices.pricez.co.il/product-28181-%D7%A4%D7%99%D7%A8%D7%95%D7%A8%D7%99-%D7%9C%D7%97%D7%9D-%D7%94%D7%A0%D7%9E%D7%9C-500-%D7%92%D7%A8%D7%9D-city-1164-%D7%A8%D7%90%D7%A9%D7%95%D7%9F-%D7%9C%D7%A6%D7%99%D7%95%D7%9F.html', 'pricez', '2026-07-03', array(), array( 'gluten_label_review' )
	),
	$c99_product(
		'product-ground-beef-1kg', 'ingredient-ground-beef', 'בקר טחון טרי במשקל', 'Fresh ground beef by weight', 'Fresh meat benchmark',
		'chilled_or_frozen_sensitive', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 40.00, 40.00, 64.90, 64.90, 'ILS_per_kg_evaluation', 64.90,
		'https://prices.pricez.co.il/product-65427-%D7%91%D7%A9%D7%A8-%D7%91%D7%A7%D7%A8-%D7%A2%D7%92%D7%9C-%D7%98%D7%97%D7%95%D7%9F-%D7%98%D7%A8%D7%99-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-238-%D7%91%D7%AA-%D7%99%D7%9D.html', 'pricez', '2026-07-27'
	),
	$c99_product(
		'product-tilapia-fillet-1kg', 'ingredient-tilapia', 'פילה אמנון קפוא 100% דג', 'Frozen tilapia fillet, 100% fish', 'Tnuva benchmark',
		'chilled_or_frozen_sensitive', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 26.90, 26.90, 38.90, 39.90, 'ILS_per_kg_evaluation', 39.90,
		'https://prices.pricez.co.il/product-1039177-%D7%A4%D7%99%D7%9C%D7%94-%D7%93%D7%92-%D7%90%D7%9E%D7%A0%D7%95%D7%9F-%D7%A7%D7%A4%D7%95%D7%90-5-7-100-%D7%90%D7%97%D7%95%D7%96-%D7%93%D7%92-%D7%AA%D7%A0%D7%95%D7%91%D7%94-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-934-%D7%A0%D7%AA%D7%A0%D7%99%D7%94.html', 'pricez', '2026-07-26', array(), array( 'fish_allergen' )
	),
	$c99_product(
		'product-tomato-sauce-400g', 'ingredient-tomato-sauce', 'רוטב עגבניות מרוכז 400 גרם', 'Concentrated tomato sauce 400 g', 'Priniv benchmark',
		'packaged_shelf_stable', '400 גרם', '400 g', 7.80, 7.80, 9.90, 19.50, 'ILS_per_kg_low_observed', 10.90,
		'https://www.pricez.co.il/Product/1546007/%D7%A8%D7%95%D7%98%D7%91-%D7%A2%D7%92%D7%91%D7%A0%D7%99%D7%95%D7%AA-%D7%9E%D7%A8%D7%95%D7%9B%D7%96-%D7%9E%D7%90%D7%95%D7%93-%D7%A4%D7%A8%D7%99%D7%A0%D7%99%D7%A8-400-%D7%92%D7%A8%D7%9D', 'pricez', '2026-07-31'
	),
	$c99_product(
		'product-rice-persian-1kg', 'ingredient-rice', 'אורז פרסי 1 ק״ג', 'Persian-style rice 1 kg', 'Sugart benchmark',
		'packaged_shelf_stable', '1 ק״ג', '1 kg', 11.70, 7.00, 14.90, 11.70, 'ILS_per_kg_observed', 12.90,
		'https://prices.pricez.co.il/product-63843-%D7%90%D7%95%D7%A8%D7%96-%D7%A4%D7%A8%D7%A1%D7%99-%D7%A7%D7%9C%D7%90%D7%A1%D7%99-%D7%A1%D7%95%D7%92%D7%AA-1-%D7%A7%D7%99%D7%9C%D7%95-city-1164-%D7%A8%D7%90%D7%A9%D7%95%D7%9F-%D7%9C%D7%A6%D7%99%D7%95%D7%9F.html', 'pricez', '2026-07-27'
	),
	$c99_product(
		'product-beef-shank-1kg', 'ingredient-beef-shank', 'שריר זרוע בקר טרי במשקל', 'Fresh beef shank by weight', 'Helek benchmark',
		'chilled_or_frozen_sensitive', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 64.90, 64.90, 69.90, 69.90, 'ILS_per_kg_evaluation', 69.90,
		'https://prices.pricez.co.il/product-133304-%D7%91%D7%A9%D7%A8-%D7%A9%D7%A8%D7%99%D7%A8-%D7%94%D7%96%D7%A8%D7%95%D7%A2-%D7%91%D7%A7%D7%A8-%D7%A2%D7%92%D7%9C-%D7%98%D7%A8%D7%99-%D7%97%D7%9C%D7%A7%2B-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-1208-%D7%A8%D7%A2%D7%A0%D7%A0%D7%94.html', 'pricez', '2026-07-26'
	),
	$c99_product(
		'product-hawayej-soup-100g', 'ingredient-hawayej-soup', 'חוואיג׳ למרק 100 גרם', 'Hawayej for soup 100 g', 'Taam Vareach benchmark',
		'packaged_shelf_stable', '100 גרם', '100 g', 6.90, 6.90, 6.90, 69.00, 'ILS_per_kg', 8.90,
		'https://chp.co.il/%D7%99%D7%A8%D7%95%D7%A9%D7%9C%D7%99%D7%9D%2B/9000/3000/%D7%9E%D7%91%D7%A6%D7%A2%D7%99%D7%9D%2B%D7%91%D7%9E%D7%A2%D7%99%D7%99%D7%9F%2B2000%2B%D7%A9%D7%9C%2B%D7%97%D7%95%D7%95%D7%90%D7%99%D7%99%D7%92%27%2B%D7%9C%D7%9E%D7%A8%D7%A7%2C%2B%2B%2B100%2B%D7%92%D7%A8%D7%9D%2C%2B%28%D7%99%D7%A6%D7%A8%D7%9F%2F%D7%9E%D7%95%D7%AA%D7%92%3A%2B%D7%AA%D7%91%D7%9C%D7%99%D7%A0%D7%99%2B%D7%98%D7%A2%D7%9D%2B%D7%95%D7%A8%D7%99%D7%97%2C%2B%D7%91%D7%A8%D7%A7%D7%95%D7%93%3A%2B7290000134970%29%2B%D7%91%D7%90%D7%99%D7%96%D7%95%D7%A8%2B%D7%99%D7%A8%D7%95%D7%A9%D7%9C%D7%99%D7%9D%2B/7290027600007_7290000134970/1', 'chp', '2026-07-31'
	),
	$c99_product(
		'product-olive-oil-750ml', 'ingredient-olive-oil', 'שמן זית כתית מעולה 750 מ״ל', 'Extra virgin olive oil 750 ml', 'Zeta benchmark',
		'packaged_shelf_stable', '750 מ״ל', '750 ml', 44.90, 32.90, 49.90, 59.87, 'ILS_per_litre', 46.90,
		'https://prices.pricez.co.il/product-59734-%D7%A9%D7%9E%D7%9F-%D7%96%D7%99%D7%AA-%D7%9B%D7%AA%D7%99%D7%AA-%D7%9E%D7%A2%D7%95%D7%9C%D7%94-%D7%98%D7%A2%D7%9D-%D7%9E%D7%A2%D7%95%D7%93%D7%9F-%D7%91%D7%9B%D7%91%D7%99%D7%A9%D7%94-%D7%A7%D7%A8%D7%94-%D7%97%D7%9E%D7%99%D7%A6%D7%95%D7%AA-%D7%9E%D7%A8%D7%91%D7%99%D7%AA-0.5-%D7%90%D7%97%D7%95%D7%96-%D7%96%D7%99%D7%AA%D7%90-750-%D7%9E%D7%9C-city-934-%D7%A0%D7%AA%D7%A0%D7%99%D7%94.html', 'pricez', '2026-07-28'
	),
	$c99_product(
		'product-pickles-brine-320g', 'ingredient-pickles', 'מלפפונים קטנים במלח 320 גרם', 'Small brined pickles 320 g', 'Beit Hashita benchmark',
		'packaged_shelf_stable', '320 גרם, משקל ברוטו', '320 g gross weight', 14.50, 10.50, 14.50, 45.31, 'ILS_per_kg_gross', 14.90,
		'https://prices.pricez.co.il/product-1021567-%D7%9E%D7%9C%D7%A4%D7%A4%D7%95%D7%A0%D7%99%D7%9D-%D7%91%D7%9E%D7%9C%D7%97-%D7%A7%D7%98%D7%A0%D7%99%D7%9D-%D7%9E%D7%90%D7%95%D7%93-18-25-%D7%91%D7%99%D7%AA-%D7%94%D7%A9%D7%99%D7%98%D7%94-320-%D7%92%D7%A8%D7%9D-city-271-%D7%92%D7%91%D7%A2%D7%AA%D7%99%D7%99%D7%9D.html', 'pricez', '2026-07-28', array(), array( 'drained_weight_required' )
	),
	$c99_product(
		'product-chicken-liver-1kg', 'ingredient-chicken-liver', 'כבד עוף טרי במשקל', 'Fresh chicken liver by weight', 'Fresh poultry benchmark',
		'chilled_or_frozen_sensitive', '1 ק״ג להערכת מחיר', '1 kg price benchmark', 17.50, 9.90, 24.90, 24.90, 'ILS_per_kg_evaluation', 24.90,
		'https://prices.pricez.co.il/product-65990-%D7%9B%D7%91%D7%93-%D7%A2%D7%95%D7%A3-%D7%98%D7%A8%D7%99-%D7%9B%D7%A9%D7%A8%D7%95%D7%AA-%D7%A8%D7%92%D7%99%D7%9C%D7%94-%D7%91%D7%9E%D7%A9%D7%A7%D7%9C-city-1179-%D7%A8%D7%97%D7%95%D7%91%D7%95%D7%AA.html', 'pricez', '2026-07-26', array( 'menu-reference-chicken-liver' )
	),
	$c99_product(
		'product-rishiri-kombu-100g', 'ingredient-kombu', 'קומבו רישירי טבעי 100 גרם', 'Natural Rishiri kombu 100 g', 'Rishiri direct market benchmark',
		'packaged_shelf_stable', '100 גרם', '100 g', 22.19, 22.19, 119.00, 221.90, 'ILS_per_kg_source_conversion', 89.00,
		'https://www.rishirikonbu.com/items/4808577', 'rishiri_kombu_direct', '2026-08-06', array(), array( 'import_label_review', 'seaweed_iodine_guidance_review' ), '2026-08-06'
	),
	$c99_product(
		'product-honkarebushi-200g', 'ingredient-katsuobushi', 'בלוק קצואובושי הונקרבושי כ-200 גרם', 'Honkarebushi katsuobushi block, approx. 200 g', 'Japanese Taste market benchmark',
		'packaged_shelf_stable', 'כ-200 גרם', 'Approx. 200 g', 99.03, 99.03, 240.00, 495.15, 'ILS_per_kg_source_conversion', 219.00,
		'https://int.japanesetaste.com/products/honkarebushi-whole-japanese-katsuobushi-block-bonito-belly-200g', 'japanese_taste', '2026-08-06', array(), array( 'fish_allergen', 'animal_derived_food_import_review', 'import_label_review' ), '2026-08-06'
	),
	$c99_product(
		'product-yamaroku-tsurubishio-500ml', 'ingredient-kioke-shoyu', 'Yamaroku Tsuru-bishio שויו קיוקה 500 מ״ל', 'Yamaroku Tsuru-bishio kioke shoyu 500 ml', 'Yamaroku producer benchmark',
		'packaged_shelf_stable', '500 מ״ל', '500 ml', 36.27, 36.27, 122.29, 72.54, 'ILS_per_litre_source_conversion', 149.00,
		'https://yama-roku.net/product', 'yamaroku_direct', '2026-08-06', array(), array( 'soy_allergen', 'wheat_allergen', 'import_label_review', 'supplier_pack_photography_required' ), '2026-08-06'
	),
	$c99_product(
		'product-kito-yuzu-juice-100ml', 'ingredient-kito-yuzu', 'Ogon no Mura מיץ Kito Yuzu ראשון 100 מ״ל', 'Ogon no Mura Kito Yuzu first-press juice 100 ml', 'Ogon no Mura producer benchmark',
		'packaged_shelf_stable', '100 מ״ל', '100 ml', 13.69, 13.69, 64.00, 136.90, 'ILS_per_litre_source_conversion', 64.00,
		'https://shop.ogonnomura.jp/view/item/000000000364', 'ogon_no_mura_direct', '2026-08-06', array(), array( 'import_label_review', 'processed_gi_representation_review', 'refrigerate_after_opening' ), '2026-08-06'
	),
	$c99_product(
		'product-fresh-japanese-wasabi-250g', 'ingredient-fresh-wasabi', 'קני שורש וואסבי יפני טרי 250 גרם', 'Fresh Japanese wasabi rhizomes 250 g', 'The Wasabi Company market benchmark',
		'chilled_or_frozen_sensitive', '250 גרם', '250 g', 252.81, 252.81, 252.81, 1011.24, 'ILS_per_kg_source_conversion', 399.00,
		'https://www.thewasabicompany.co.uk/products/fresh-japanese-wasabi-250g', 'the_wasabi_company', '2026-08-06', array(), array( 'cold_chain_review', 'phytosanitary_import_review', 'variable_rhizome_size', 'supplier_availability_not_public_inventory' ), '2026-08-06', 'food',
		array(
			'source_price'  => array(
				'amount'             => '62.50',
				'currency'           => 'GBP',
				'tax_state'          => 'included',
				'availability_state' => 'out_of_stock_at_observation',
			),
			'fx_conversion' => array(
				'rate'                 => '4.0450',
				'basis'                => 'ILS_per_GBP',
				'source_url'           => 'https://www.boi.org.il/roles/markets/exchangerates/',
				'rate_date'            => '2026-08-05',
				'checked_at'           => '2026-08-06',
				'converted_amount_ils' => '252.81',
				'formula'              => 'GBP 62.50 multiplied by ILS 4.0450 per GBP equals ILS 252.8125, rounded to ILS 252.81.',
			),
		)
	),
	$c99_product(
		'product-hagane-zame-large', 'equipment-wasabi-grater', 'מגררת וואסבי Yamamoto Hagane-zame Pro גדולה', 'Yamamoto Hagane-zame Pro large wasabi grater', 'Yamamoto Foods official benchmark',
		'professional_equipment', 'יחידה אחת, דגם Pro גדול', 'One Pro large grater', 324.79, 324.79, 324.79, 324.79, 'ILS_per_item_source_conversion', 699.00,
		'https://www.yamamotofoods.co.jp/haganezame/jp/spec/', 'yamamoto_foods_official', '2026-08-06', array(), array( 'food_contact_material_review', 'sharp_surface_handling', 'official_japan_price', 'domestic_japan_shipping_only' ), '2026-08-06', 'equipment',
		array(
			'source_price'  => array(
				'amount'             => '17050',
				'currency'           => 'JPY',
				'tax_state'          => 'included',
				'availability_state' => 'official_purchase_methods_listed',
			),
			'fx_conversion' => array(
				'rate'                 => '1.9049',
				'basis'                => 'ILS_per_100_JPY',
				'source_url'           => 'https://www.boi.org.il/roles/markets/exchangerates/',
				'rate_date'            => '2026-08-05',
				'checked_at'           => '2026-08-06',
				'converted_amount_ils' => '324.79',
				'formula'              => 'JPY 17,050 divided by 100 and multiplied by ILS 1.9049 per JPY 100 equals ILS 324.78545, rounded to ILS 324.79.',
			),
		),
		array(
			'model'      => array( 'he' => 'Hagane-zame Pro, דגם גדול', 'en' => 'Hagane-zame Pro, large model' ),
			'material'   => array( 'he' => 'פלדת אל-חלד', 'en' => 'Stainless steel' ),
			'dimensions' => array( 'he' => 'גוף 26.0 על 11.0 על 0.1 ס״מ; משטח גירור 16.0 על 11.0 ס״מ; משקל כ-156 גרם.', 'en' => 'Body 26.0 by 11.0 by 0.1 cm; grating surface 16.0 by 11.0 cm; approximately 156 g.' ),
			'care'       => array( 'he' => 'לאחר השימוש לשטוף במים, להבריש בעדינות בכמה כיוונים, לספוג את המים ולייבש לפני אחסון. היצרן מציין התאמה למדיח כלים.', 'en' => 'After use, rinse with water, brush gently in several directions, blot away moisture and dry before storage. The maker states that dishwasher cleaning is supported.' ),
			'safety'     => array( 'he' => 'משטח הגירור חד. יש לאחוז בידית, להרחיק אצבעות מהשיניים ולנקות במברשת במקום ביד חשופה.', 'en' => 'The grating surface is sharp. Hold the handle, keep fingers clear of the teeth and clean with a brush rather than a bare hand.' ),
		)
	),
);

$c99_candidate_relations = array(
	'product-parsley-100g' => array(
		'ingredient_codes' => array( 'ingredient-herbs-unspecified' ),
		'dish_ids'         => array( 'menu-reference-aja' ),
	),
	'product-couscous-1kg' => array(
		'ingredient_codes' => array(),
		'dish_ids'         => array( 'menu-reference-couscous' ),
	),
	'product-breadcrumbs-500g' => array(
		'ingredient_codes' => array(),
		'dish_ids'         => array( 'menu-reference-schnitzel' ),
	),
	'product-ground-beef-1kg' => array(
		'ingredient_codes' => array( 'ingredient-meat-unspecified' ),
		'dish_ids'         => array( 'menu-reference-beet-kubbeh', 'menu-reference-homemade-meatballs' ),
	),
	'product-tilapia-fillet-1kg' => array(
		'ingredient_codes' => array( 'ingredient-fish' ),
		'dish_ids'         => array( 'menu-reference-fish-patties' ),
	),
	'product-tomato-sauce-400g' => array(
		'ingredient_codes' => array(),
		'dish_ids'         => array( 'menu-reference-fish-patties', 'menu-reference-homemade-meatballs' ),
	),
	'product-rice-persian-1kg' => array(
		'ingredient_codes' => array(),
		'dish_ids'         => array( 'menu-reference-homemade-meatballs' ),
	),
	'product-beef-shank-1kg' => array(
		'ingredient_codes' => array( 'ingredient-beef' ),
		'dish_ids'         => array( 'menu-reference-yemenite-soup' ),
	),
	'product-hawayej-soup-100g' => array(
		'ingredient_codes' => array(),
		'dish_ids'         => array( 'menu-reference-yemenite-soup' ),
	),
);

$c99_evaluation_image_bindings = array(
	'product-tahini-500g'        => 'c99-ingredient-tahini-evaluation-v01.webp',
	'product-amba-500g'          => 'c99-ingredient-amba-evaluation-v01.webp',
	'product-hot-sauce-60ml'     => 'c99-ingredient-hot-sauce-evaluation-v01.webp',
	'product-pita-12x50g'        => 'c99-supply-pita-stack-evaluation-v01.webp',
	'product-aubergine-1kg'      => 'c99-ingredient-aubergine-evaluation-v01.webp',
	'product-eggs-l-12'          => 'c99-ingredient-eggs-evaluation-v01.webp',
	'product-potato-white-1kg'   => 'c99-ingredient-potatoes-evaluation-v01.webp',
	'product-tomato-1kg'         => 'c99-ingredient-tomatoes-evaluation-v01.webp',
	'product-cucumber-1kg'       => 'c99-ingredient-cucumbers-evaluation-v01.webp',
	'product-onion-dry-1kg'      => 'c99-ingredient-onions-evaluation-v01.webp',
	'product-parsley-100g'       => 'c99-ingredient-parsley-evaluation-v01.webp',
	'product-chickpeas-dry-500g' => 'c99-ingredient-chickpeas-evaluation-v01.webp',
	'product-beetroot-1kg'       => 'c99-ingredient-beetroot-evaluation-v01.webp',
	'product-bulgur-fine-500g'   => 'c99-ingredient-bulgur-evaluation-v01.webp',
	'product-couscous-1kg'       => 'c99-ingredient-couscous-evaluation-v01.webp',
	'product-breadcrumbs-500g'   => 'c99-supply-breadcrumbs-evaluation-v01.webp',
	'product-tomato-sauce-400g'  => 'c99-supply-tomato-sauce-evaluation-v01.webp',
	'product-rice-persian-1kg'   => 'c99-ingredient-rice-evaluation-v01.webp',
	'product-olive-oil-750ml'    => 'c99-supply-olive-oil-evaluation-v01.webp',
	'product-pickles-brine-320g' => 'c99-supply-salt-pickles-evaluation-v01.webp',
	'product-chicken-breast-1kg' => 'c99-ingredient-chicken-breast-evaluation-v01.webp',
	'product-ground-beef-1kg'    => 'c99-ingredient-ground-beef-evaluation-v01.webp',
	'product-tilapia-fillet-1kg' => 'c99-ingredient-tilapia-evaluation-v01.webp',
	'product-beef-shank-1kg'     => 'c99-ingredient-beef-shank-evaluation-v01.webp',
	'product-hawayej-soup-100g'  => 'c99-ingredient-hawayej-soup-evaluation-v01.webp',
	'product-chicken-liver-1kg'  => 'c99-ingredient-chicken-liver-evaluation-v01.webp',
	'product-rishiri-kombu-100g' => 'c99-ingredient-rishiri-kombu-evaluation-v01.webp',
	'product-honkarebushi-200g'  => 'c99-ingredient-katsuobushi-evaluation-v01.webp',
	'product-yamaroku-tsurubishio-500ml' => 'c99-ingredient-kioke-shoyu-evaluation-v01.webp',
	'product-kito-yuzu-juice-100ml' => 'c99-ingredient-kito-yuzu-juice-evaluation-v01.webp',
	'product-fresh-japanese-wasabi-250g' => 'c99-ingredient-fresh-wasabi-250g-v01.webp',
	'product-hagane-zame-large' => 'c99-equipment-hagane-zame-pro-v01.webp',
);

foreach ( $c99_products as &$c99_seed_product ) {
	$c99_code = $c99_seed_product['product_code'];
	$c99_seed_product['relations']['candidate_ingredient_codes'] = isset( $c99_candidate_relations[ $c99_code ] )
		? $c99_candidate_relations[ $c99_code ]['ingredient_codes']
		: array();
	$c99_seed_product['relations']['candidate_dish_ids'] = isset( $c99_candidate_relations[ $c99_code ] )
		? $c99_candidate_relations[ $c99_code ]['dish_ids']
		: array();
	if ( isset( $c99_evaluation_image_bindings[ $c99_code ] ) ) {
		$c99_seed_product['image_asset'] = $c99_evaluation_image_bindings[ $c99_code ];
		$c99_seed_product['image_state'] = 'evaluation_asset_held_for_review';
	}
}
unset( $c99_seed_product );

return array(
	'schema'                      => 'complete99-catalog-product-seeds/v1',
	'registry_reviewed_at'        => '2026-08-06',
	'currency'                    => 'ILS',
	'price_scope'                 => 'private_market_benchmark',
	'stock_policy'                => array(
		'evaluation_quantity' => 1,
		'public_stock_claim'  => false,
		'public_sale'         => false,
	),
	'market_transparency_sources' => array(
		'pricez'                     => 'https://www.pricez.co.il/',
		'chp'                        => 'https://chp.co.il/',
		'shufersal_transparency'     => 'https://prices.shufersal.co.il/',
		'carrefour_transparency'     => 'https://shilut.carrefour.co.il/',
		'israel_controlled_products' => 'https://www.gov.il/he/Departments/DynamicCollectors/food-price-control-search',
		'israel_maximum_prices'      => 'https://www.gov.il/he/service/maximum_price_for_consumer_goods',
		'bank_of_israel_fx'          => 'https://www.boi.org.il/roles/markets/exchangerates/',
		'israel_food_import'         => 'https://www.gov.il/en/departments/units/import-food-inspection-unit',
		'rishiri_kombu_direct'       => 'https://www.rishirikonbu.com/',
		'yamaroku_direct'             => 'https://yama-roku.net/product',
		'ogon_no_mura_direct'         => 'https://shop.ogonnomura.jp/view/item/000000000364',
		'japanese_taste'             => 'https://int.japanesetaste.com/',
		'the_wasabi_company'          => 'https://www.thewasabicompany.co.uk/',
		'yamamoto_foods_official'     => 'https://www.yamamotofoods.co.jp/haganezame/jp/spec/',
	),
	'classification_rules'         => $c99_classification_rules,
	'products'                     => $c99_products,
);
