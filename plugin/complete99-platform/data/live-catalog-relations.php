<?php
/**
 * Reviewed public relations between the exact 30 products, 12 dish pages and
 * the approved Culinary Science Museum ingredient pages.
 *
 * Relations describe culinary navigation only. They do not assert a supplier,
 * brand, formulation, nutrition, health, allergen or kosher fact.
 *
 * @package Complete99_Platform
 */

defined( 'ABSPATH' ) || exit;

return array(
	'schema'      => 'complete99-live-catalog-relations/v1',
	'reviewed_at' => '2026-07-31',
	'products'    => array(
		'product-tahini-500g'        => array( 'ingredient_code' => 'ingredient-tahini', 'dish_slugs' => array( 'sabich' ) ),
		'product-amba-500g'          => array( 'ingredient_code' => 'ingredient-amba', 'dish_slugs' => array( 'sabich' ) ),
		'product-hot-sauce-60ml'     => array( 'ingredient_code' => 'ingredient-hot-sauce', 'dish_slugs' => array( 'sabich' ) ),
		'product-pita-12x50g'        => array( 'ingredient_code' => 'ingredient-pita', 'dish_slugs' => array( 'sabich', 'schnitzel', 'aja-herb-omelet', 'sabtucha' ) ),
		'product-aubergine-1kg'      => array( 'ingredient_code' => 'ingredient-aubergine', 'dish_slugs' => array( 'sabich', 'sabtucha' ) ),
		'product-eggs-l-12'          => array( 'ingredient_code' => 'ingredient-egg', 'dish_slugs' => array( 'sabich', 'shakshuka', 'aja-herb-omelet', 'sabtucha' ) ),
		'product-potato-white-1kg'   => array( 'ingredient_code' => 'ingredient-potato', 'dish_slugs' => array( 'sabich', 'sabtucha' ) ),
		'product-tomato-1kg'         => array( 'ingredient_code' => 'ingredient-tomato', 'dish_slugs' => array( 'shakshuka', 'homemade-meatballs', 'fish-patties' ) ),
		'product-cucumber-1kg'       => array( 'ingredient_code' => 'ingredient-cucumber', 'dish_slugs' => array( 'sabich' ) ),
		'product-onion-dry-1kg'      => array( 'ingredient_code' => 'ingredient-onion', 'dish_slugs' => array( 'shakshuka', 'homemade-meatballs', 'chicken-liver' ) ),
		'product-parsley-100g'       => array( 'ingredient_code' => 'ingredient-parsley', 'dish_slugs' => array( 'aja-herb-omelet', 'fish-patties' ) ),
		'product-chickpeas-dry-500g' => array( 'ingredient_code' => 'ingredient-chickpea', 'dish_slugs' => array( 'couscous' ) ),
		'product-beetroot-1kg'       => array( 'ingredient_code' => 'ingredient-beet', 'dish_slugs' => array( 'beet-kubbeh' ) ),
		'product-bulgur-fine-500g'   => array( 'ingredient_code' => 'ingredient-bulgur', 'dish_slugs' => array( 'couscous' ) ),
		'product-couscous-1kg'       => array( 'ingredient_code' => 'ingredient-couscous', 'dish_slugs' => array( 'couscous' ) ),
		'product-chicken-breast-1kg' => array( 'ingredient_code' => 'ingredient-chicken-breast', 'dish_slugs' => array( 'schnitzel', 'grilled-chicken' ) ),
		'product-breadcrumbs-500g'   => array( 'ingredient_code' => 'ingredient-breadcrumbs', 'dish_slugs' => array( 'schnitzel' ) ),
		'product-ground-beef-1kg'    => array( 'ingredient_code' => 'ingredient-ground-beef', 'dish_slugs' => array( 'homemade-meatballs' ) ),
		'product-tilapia-fillet-1kg' => array( 'ingredient_code' => 'ingredient-tilapia', 'dish_slugs' => array( 'fish-patties' ) ),
		'product-tomato-sauce-400g'  => array( 'ingredient_code' => 'ingredient-tomato-sauce', 'dish_slugs' => array( 'homemade-meatballs', 'fish-patties' ) ),
		'product-rice-persian-1kg'   => array( 'ingredient_code' => 'ingredient-rice', 'dish_slugs' => array( 'homemade-meatballs' ) ),
		'product-beef-shank-1kg'     => array( 'ingredient_code' => 'ingredient-beef-shank', 'dish_slugs' => array( 'yemenite-beef-soup' ) ),
		'product-hawayej-soup-100g'  => array( 'ingredient_code' => 'ingredient-hawayej-soup', 'dish_slugs' => array( 'yemenite-beef-soup' ) ),
		'product-olive-oil-750ml'    => array( 'ingredient_code' => 'ingredient-olive-oil', 'dish_slugs' => array( 'shakshuka' ) ),
		'product-pickles-brine-320g' => array( 'ingredient_code' => 'ingredient-pickles', 'dish_slugs' => array( 'sabich' ) ),
		'product-chicken-liver-1kg'  => array( 'ingredient_code' => 'ingredient-chicken-liver', 'dish_slugs' => array( 'chicken-liver' ) ),
		'product-rishiri-kombu-100g' => array( 'ingredient_code' => 'ingredient-kombu', 'dish_slugs' => array(), 'science_entity_id' => 'ingredient-kombu', 'related_product_codes' => array( 'product-honkarebushi-200g', 'product-yamaroku-tsurubishio-500ml' ) ),
		'product-honkarebushi-200g'  => array( 'ingredient_code' => 'ingredient-katsuobushi', 'dish_slugs' => array(), 'science_entity_id' => 'ingredient-katsuobushi', 'related_product_codes' => array( 'product-rishiri-kombu-100g', 'product-yamaroku-tsurubishio-500ml' ) ),
		'product-yamaroku-tsurubishio-500ml' => array( 'ingredient_code' => 'ingredient-kioke-shoyu', 'dish_slugs' => array(), 'science_entity_id' => 'ingredient-kioke-shoyu', 'related_product_codes' => array( 'product-kito-yuzu-juice-100ml', 'product-rishiri-kombu-100g', 'product-honkarebushi-200g' ) ),
		'product-kito-yuzu-juice-100ml' => array( 'ingredient_code' => 'ingredient-kito-yuzu', 'dish_slugs' => array(), 'science_entity_id' => 'ingredient-kito-yuzu', 'related_product_codes' => array( 'product-yamaroku-tsurubishio-500ml' ) ),
	),
	'dishes'      => array(
		'sabich'             => array( 'product-tahini-500g', 'product-amba-500g', 'product-hot-sauce-60ml', 'product-pita-12x50g', 'product-aubergine-1kg', 'product-eggs-l-12', 'product-potato-white-1kg', 'product-cucumber-1kg', 'product-pickles-brine-320g' ),
		'beet-kubbeh'        => array( 'product-beetroot-1kg' ),
		'schnitzel'          => array( 'product-pita-12x50g', 'product-chicken-breast-1kg', 'product-breadcrumbs-500g' ),
		'shakshuka'          => array( 'product-eggs-l-12', 'product-tomato-1kg', 'product-onion-dry-1kg', 'product-olive-oil-750ml' ),
		'homemade-meatballs' => array( 'product-tomato-1kg', 'product-onion-dry-1kg', 'product-ground-beef-1kg', 'product-tomato-sauce-400g', 'product-rice-persian-1kg' ),
		'fish-patties'       => array( 'product-tomato-1kg', 'product-parsley-100g', 'product-tilapia-fillet-1kg', 'product-tomato-sauce-400g' ),
		'grilled-chicken'    => array( 'product-chicken-breast-1kg' ),
		'aja-herb-omelet'    => array( 'product-pita-12x50g', 'product-eggs-l-12', 'product-parsley-100g' ),
		'couscous'           => array( 'product-chickpeas-dry-500g', 'product-bulgur-fine-500g', 'product-couscous-1kg' ),
		'yemenite-beef-soup' => array( 'product-beef-shank-1kg', 'product-hawayej-soup-100g' ),
		'sabtucha'           => array( 'product-pita-12x50g', 'product-aubergine-1kg', 'product-eggs-l-12', 'product-potato-white-1kg' ),
		'chicken-liver'      => array( 'product-onion-dry-1kg', 'product-chicken-liver-1kg' ),
	),
);
