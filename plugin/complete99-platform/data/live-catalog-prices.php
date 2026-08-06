<?php
/**
 * Owner-authorized initial public catalog prices.
 *
 * This registry is deliberately separate from product copy and WooCommerce so
 * a researched price refresh can be reviewed, diffed and applied without
 * changing catalog identity or media bindings.
 *
 * @package Complete99_Platform
 */

defined( 'ABSPATH' ) || exit;

return array(
	'schema'      => 'complete99-live-catalog-prices/v1',
	'currency'    => 'ILS',
	'reviewed_at' => '2026-08-06',
	'price_scope' => 'owner_authorized_opening_retail_price_informed_by_market_observation',
	'evidence'    => array(
		'registry'          => 'catalog-product-seeds.php',
		'binding'           => 'product_code',
		'source_url_field'  => 'market_observation.source_url',
		'accessed_at_field' => 'market_observation.checked_at',
		'observed_price_field' => 'market_observation.observed_price_ils',
		'range_low_field'      => 'market_observation.range_low_ils',
		'range_high_field'     => 'market_observation.range_high_ils',
		'selection_rule'       => 'Owner-authorized opening retail price selected after reviewing the bound current market observation; it is not represented as the exact observed third-party price.',
		'represents_exact_third_party_observation' => false,
	),
	'prices'      => array(
		'product-tahini-500g'        => '11.00',
		'product-amba-500g'          => '14.90',
		'product-hot-sauce-60ml'     => '12.90',
		'product-pita-12x50g'        => '14.90',
		'product-aubergine-1kg'      => '6.90',
		'product-eggs-l-12'          => '14.24',
		'product-potato-white-1kg'   => '4.90',
		'product-tomato-1kg'         => '6.90',
		'product-cucumber-1kg'       => '6.90',
		'product-onion-dry-1kg'      => '4.90',
		'product-parsley-100g'       => '5.90',
		'product-chickpeas-dry-500g' => '8.90',
		'product-beetroot-1kg'       => '4.90',
		'product-bulgur-fine-500g'   => '5.90',
		'product-couscous-1kg'       => '11.90',
		'product-chicken-breast-1kg' => '39.90',
		'product-breadcrumbs-500g'   => '8.90',
		'product-ground-beef-1kg'    => '64.90',
		'product-tilapia-fillet-1kg' => '38.90',
		'product-tomato-sauce-400g'  => '9.90',
		'product-rice-persian-1kg'   => '11.90',
		'product-beef-shank-1kg'     => '69.90',
		'product-hawayej-soup-100g'  => '8.90',
		'product-olive-oil-750ml'    => '44.90',
		'product-pickles-brine-320g' => '14.90',
		'product-chicken-liver-1kg'  => '17.90',
		'product-rishiri-kombu-100g' => '89.00',
		'product-honkarebushi-200g'  => '219.00',
		'product-yamaroku-tsurubishio-500ml' => '149.00',
		'product-kito-yuzu-juice-100ml' => '64.00',
		'product-fresh-japanese-wasabi-250g' => '399.00',
		'product-hagane-zame-large' => '699.00',
	),
);
