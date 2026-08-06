<?php
/**
 * Private commerce planning records for the Japanese premium-market tranche.
 *
 * Every candidate is held with planning stock zero. Prices are internal ILS
 * planning values, not WooCommerce prices or approved offers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$boi_url = 'https://boi.org.il/PublicApi/GetExchangeRates';
$listing = static function ( $config ) use ( $c99_commerce_text ) {
	$config['attributes']['planning_stock_quantity'] = '0';
	$config['attributes']['public_market_projection'] = 'held';
	$config['attributes']['planning_price_ils'] = number_format( $config['planning_price_minor'] / 100, 2, '.', '' );
	$config['attributes']['planning_price_rationale'] = $config['planning_price_rationale'];
	$config['attributes']['activation_gates'] = implode( '|', $config['activation_gate_ids'] );
	$config['attributes']['compliance_note_he'] = '[COMPLIANCE_NOTE: ' . $config['compliance_note_he'] . ']';
	$config['attributes']['compliance_note_en'] = '[COMPLIANCE_NOTE: ' . $config['compliance_note_en'] . ']';
	$config['attributes']['compliance_note'] = $config['attributes']['compliance_note_en'];
	$config['compliance_state'] = $config['sku_compliance_state'];
	unset( $config['planning_price_rationale'], $config['compliance_note_he'], $config['compliance_note_en'], $config['sku_compliance_state'] );
	return $config;
};

$premium_listings = array(
	$listing( array(
		'key' => 'maruyama-gokujo-kontobi-nori-5-sheets', 'listing_entity_id' => 'listing-maruyama-gokujo-kontobi-nori-5-sheets-20260806', 'knowledge_entity_id' => 'ingredient-yakinori',
		'source_id' => 'maruyama-kontobi-5-listing-2026', 'source_url' => 'https://www.maruyamanori.com/c/kontobi_n/200660-C157', 'seller_id' => 'seller-maruyama-nori-direct', 'market_id' => 'market-jp-source', 'currency_id' => 'currency-jpy',
		'amount_minor' => 1350, 'tax_state' => 'included', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'like_for_like',
		'name' => $c99_commerce_text( 'Maruyama Gokujo Kontobi נורי, 5 דפים', 'Maruyama Gokujo Kontobi nori, 5 sheets' ), 'product_family' => 'premium-yakinori', 'brand_id' => 'brand-maruyama-nori', 'manufacturer_id' => 'manufacturer-maruyama-nori',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'sushi-ingredients', 'premium-nori' ), 'compliance_rule_ids' => array( 'iodine-review', 'heavy-metals-review', 'allergen-review', 'import-label-review', 'moisture-control-review' ),
		'attributes' => array( 'pack_size' => '5 sheets', 'product_identity' => 'Gokujo Kontobi listing', 'category_science_scope' => 'category only, never a SKU or lot lab result' ),
		'quantity_decimal' => '5', 'quantity_unit' => 'sheet', 'normalized_minor' => 270, 'normalized_quantity' => '1', 'normalized_unit' => 'sheet', 'normalization_formula' => 'JPY 1,350 divided by five sheets equals JPY 270 per sheet.',
		'sku_compliance_state' => 'import_label_review_required', 'claim_locator' => 'Exact five-sheet Gokujo Kontobi listing displayed at JPY 1,350 including tax.',
		'planning_price_minor' => 9900, 'planning_price_rationale' => 'JPY 1,350 x ILS 0.019088 = ILS 25.77 source equivalent; ILS 99 is a held planning value before freight, duty, VAT, loss and margin approval.',
		'activation_gate_ids' => array( 'exact-identity', 'supplier-quote', 'iodine', 'heavy-metals', 'allergens', 'import-label', 'moisture', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת יוד ומתכות כבדות ברמת המוצר או האצווה המדויקים, אלרגנים, תווית יבוא ישראלית והגנה מלחות לפני הפעלה.',
		'compliance_note_en' => 'Verify iodine and heavy metals by exact product or lot, allergens, Israeli import label and moisture protection before activation.',
	) ),
	$listing( array(
		'key' => 'tajima-red-sushi-vinegar-360ml', 'listing_entity_id' => 'listing-tajima-red-sushi-vinegar-360ml-20260806', 'knowledge_entity_id' => 'hub-japanese-ingredients',
		'source_id' => 'tajima-red-sushi-vinegar-360ml-listing-2026', 'source_url' => 'https://japanesetaste.jp/products/tajima-jozo-premium-akazu-aged-red-vinegar-for-sushi-360ml', 'seller_id' => 'seller-japanese-taste', 'market_id' => 'market-jp-source', 'currency_id' => 'currency-jpy',
		'amount_minor' => 761, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'non_comparable',
		'name' => $c99_commerce_text( 'Tajima תיבול חומץ אדום לסושי, 360 מ״ל', 'Tajima seasoned red sushi vinegar, 360 ml' ), 'product_family' => 'seasoned-sushi-vinegar', 'brand_id' => 'brand-tajima-jozo', 'manufacturer_id' => 'manufacturer-tajima-jozo',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'sushi-seasonings', 'seasoned-vinegar' ), 'compliance_rule_ids' => array( 'identity-review', 'jan-review', 'formula-review', 'acidity-review', 'sugar-salt-review', 'shelf-life-review', 'import-label-review' ),
		'attributes' => array( 'pack_size' => '360 ml', 'category_boundary' => 'seasoned grain vinegar, not pure Akazu', 'category_science_scope' => 'producer category context, not SKU lab data' ),
		'quantity_decimal' => '360', 'quantity_unit' => 'ml', 'normalized_minor' => 2114, 'normalized_quantity' => '1', 'normalized_unit' => 'l', 'normalization_formula' => 'JPY 761 divided by 0.36 and rounded equals JPY 2,114 per liter.',
		'sku_compliance_state' => 'import_label_review_required', 'claim_locator' => 'Exact 360 ml seasoned red sushi-vinegar listing displayed at JPY 761; stored as seasoned grain vinegar, not pure Akazu.',
		'planning_price_minor' => 11900, 'planning_price_rationale' => 'JPY 761 x ILS 0.019088 = ILS 14.53 source equivalent; ILS 119 is a held plan pending exact identity, formula, import and full unit economics.',
		'activation_gate_ids' => array( 'exact-identity', 'jan', 'formula', 'acidity', 'sugar-salt', 'shelf-life', 'supplier-quote', 'import-label', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש להתייחס למוצר כחומץ דגנים מתובל. יש לאמת JAN, רכיבים, חומציות, סוכר, מלח, חיי מדף וסימון ישראלי לפני הפעלה.',
		'compliance_note_en' => 'Treat as seasoned grain vinegar. Verify JAN, ingredients, acidity, sugar, salt, shelf life and Israeli labeling before activation.',
	) ),
	$listing( array(
		'key' => 'minamigura-gin-warabeuta-tamari-200ml', 'listing_entity_id' => 'listing-minamigura-gin-warabeuta-tamari-200ml-20260806', 'knowledge_entity_id' => 'hub-japanese-ingredients',
		'source_id' => 'minamigura-tamari-200ml-listing-2026', 'source_url' => 'https://japanesetaste.com/products/minamigura-tamari-shoyu-gluten-free-japanese-soy-sauce-gin-warabeuta-200ml', 'seller_id' => 'seller-japanese-taste', 'market_id' => 'market-us-source', 'currency_id' => 'currency-usd',
		'amount_minor' => 1695, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'like_for_like',
		'name' => $c99_commerce_text( 'Minamigura Gin Warabeuta Tamari, 200 מ״ל', 'Minamigura Gin Warabeuta tamari, 200 ml' ), 'product_family' => 'tamari-shoyu', 'brand_id' => 'brand-minamigura', 'manufacturer_id' => 'manufacturer-minamigura',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'fermented-seasonings', 'tamari' ), 'compliance_rule_ids' => array( 'soy-allergen-review', 'gluten-free-certification-review', 'import-label-review' ),
		'attributes' => array( 'pack_size' => '200 ml', 'product_form' => 'tamari shoyu', 'category_science_scope' => 'fermentation category only, not SKU composition' ),
		'quantity_decimal' => '200', 'quantity_unit' => 'ml', 'normalized_minor' => 8475, 'normalized_quantity' => '1', 'normalized_unit' => 'l', 'normalization_formula' => 'USD 16.95 divided by 0.2 equals USD 84.75 per liter.',
		'sku_compliance_state' => 'allergen_import_label_review_required', 'claim_locator' => 'Exact 200 ml Gin Warabeuta tamari listing displayed at USD 16.95; gluten-free claim held for certification review.',
		'planning_price_minor' => 15900, 'planning_price_rationale' => 'USD 16.95 x ILS 3.0130 = ILS 51.07 source equivalent; ILS 159 is a held plan before freight, certification, tax and margin approval.',
		'activation_gate_ids' => array( 'exact-identity', 'supplier-quote', 'soy-allergen', 'gluten-free-certification', 'ingredients', 'import-label', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'המוצר כולל אלרגן סויה. יש לפרסם טענת ללא גלוטן רק לאחר בדיקת הסמכה, תווית ומגע צולב של ה-SKU המדויק.',
		'compliance_note_en' => 'Soy allergen applies. Publish gluten-free only after exact SKU certification, label and cross-contact review.',
	) ),
	$listing( array(
		'key' => 'sugimoto-organic-dried-shiitake-70g', 'listing_entity_id' => 'listing-sugimoto-organic-dried-shiitake-70g-20260806', 'knowledge_entity_id' => 'hub-japanese-ingredients',
		'source_id' => 'sugimoto-shiitake-70g-listing-2026', 'source_url' => 'https://int.japanesetaste.com/products/sugimoto-organic-japanese-dried-shiitake-mushrooms-70g', 'seller_id' => 'seller-japanese-taste', 'market_id' => 'market-us-source', 'currency_id' => 'currency-usd',
		'amount_minor' => 1429, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'like_for_like',
		'name' => $c99_commerce_text( 'Sugimoto שיטאקה יפנית אורגנית מיובשת, 70 גרם', 'Sugimoto organic Japanese dried shiitake, 70 g' ), 'product_family' => 'dried-shiitake', 'brand_id' => 'brand-sugimoto', 'manufacturer_id' => 'manufacturer-sugimoto',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'umami-ingredients', 'dried-mushrooms' ), 'compliance_rule_ids' => array( 'jas-review', 'identity-review', 'traceability-review', 'microbiology-review', 'mycotoxin-review', 'pesticide-review', 'heavy-metals-review', 'import-label-review' ),
		'attributes' => array( 'pack_size' => '70 g', 'product_form' => 'dried shiitake', 'category_science_scope' => 'category literature only, no SKU health claim' ),
		'quantity_decimal' => '70', 'quantity_unit' => 'g', 'normalized_minor' => 20414, 'normalized_quantity' => '1', 'normalized_unit' => 'kg', 'normalization_formula' => 'USD 14.29 divided by 0.07 and rounded equals USD 204.14 per kg.',
		'sku_compliance_state' => 'import_label_review_required', 'claim_locator' => 'Exact 70 g dried-shiitake listing displayed at USD 14.29; organic and origin claims require exact evidence.',
		'planning_price_minor' => 14900, 'planning_price_rationale' => 'USD 14.29 x ILS 3.0130 = ILS 43.06 source equivalent; ILS 149 is a held plan pending traceability, testing, import and margin evidence.',
		'activation_gate_ids' => array( 'exact-identity', 'jas', 'traceability', 'microbiology', 'mycotoxins', 'pesticides', 'heavy-metals', 'supplier-quote', 'import-label', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת תחולת JAS, זהות מדויקת, עקיבות, מיקרוביולוגיה, מיקוטוקסינים, חומרי הדברה, מתכות כבדות וסימון ישראלי לפני הפעלה.',
		'compliance_note_en' => 'Verify JAS scope, exact identity, traceability, microbiology, mycotoxins, pesticides, heavy metals and Israeli labeling before activation.',
	) ),
	$listing( array(
		'key' => 'yubaya-kyoto-dried-yuba-100g', 'listing_entity_id' => 'listing-yubaya-kyoto-dried-yuba-100g-20260806', 'knowledge_entity_id' => 'hub-japanese-ingredients',
		'source_id' => 'yubaya-kyoto-yuba-home-2026', 'source_url' => 'https://www.yubaya.co.jp/', 'seller_id' => 'seller-yubaya-direct', 'market_id' => 'market-jp-source', 'currency_id' => 'currency-jpy',
		'amount_minor' => 1080, 'tax_state' => 'included', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'partially_comparable',
		'name' => $c99_commerce_text( 'Yubaya יובה מיובשת מקיוטו, 100 גרם', 'Yubaya Kyoto dried yuba, 100 g' ), 'product_family' => 'dried-yuba', 'brand_id' => 'brand-yubaya', 'manufacturer_id' => 'manufacturer-yubaya',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'soy-products', 'dried-yuba' ), 'compliance_rule_ids' => array( 'soy-allergen-review', 'ingredients-review', 'shelf-life-review', 'cross-contact-review', 'import-label-review' ),
		'attributes' => array( 'pack_size' => '100 g', 'product_form' => 'dried yuba', 'price_precedence' => 'current homepage price supersedes stale item-page price' ),
		'quantity_decimal' => '100', 'quantity_unit' => 'g', 'normalized_minor' => 10800, 'normalized_quantity' => '1', 'normalized_unit' => 'kg', 'normalization_formula' => 'JPY 1,080 per 100 g multiplied by 10 equals JPY 10,800 per kg.',
		'sku_compliance_state' => 'allergen_import_label_review_required', 'claim_locator' => 'Current producer homepage displayed JPY 1,080 including tax; this supersedes the stale item-page price for planning evidence.',
		'planning_price_minor' => 8900, 'planning_price_rationale' => 'JPY 1,080 x ILS 0.019088 = ILS 20.62 source equivalent; ILS 89 is a held plan pending supplier, allergen, shelf-life and landed-cost evidence.',
		'activation_gate_ids' => array( 'exact-identity', 'supplier-quote', 'soy-allergen', 'ingredients', 'shelf-life', 'cross-contact', 'import-label', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'המוצר כולל אלרגן סויה. יש לאמת רכיבים, חיי מדף, מגע צולב ותווית יבוא ישראלית לפני הפעלה.',
		'compliance_note_en' => 'Soy allergen applies. Verify ingredients, shelf life, cross-contact and Israeli import label before activation.',
	) ),
	$listing( array(
		'key' => 'ohsawa-organic-kudzu-starch-150g', 'listing_entity_id' => 'listing-ohsawa-organic-kudzu-starch-150g-20260806', 'knowledge_entity_id' => 'hub-japanese-ingredients',
		'source_id' => 'ohsawa-kudzu-150g-listing-2026', 'source_url' => 'https://japanesetaste.com/products/ohsawa-organic-kudzu-starch-block-type-thickening-powder-150g', 'seller_id' => 'seller-japanese-taste', 'market_id' => 'market-us-source', 'currency_id' => 'currency-usd',
		'amount_minor' => 1498, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'like_for_like',
		'name' => $c99_commerce_text( 'Ohsawa עמילן קודזו אורגני, 150 גרם', 'Ohsawa organic kudzu starch, 150 g' ), 'product_family' => 'kudzu-starch', 'brand_id' => 'brand-ohsawa', 'manufacturer_id' => 'manufacturer-ohsawa',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'starches-and-thickeners', 'kudzu' ), 'compliance_rule_ids' => array( 'ingredient-identity-review', 'organic-scope-review', 'import-label-review', 'health-claim-hold' ),
		'attributes' => array( 'pack_size' => '150 g', 'product_form' => 'block-type starch', 'category_science_scope' => 'category only, no SKU health claim' ),
		'quantity_decimal' => '150', 'quantity_unit' => 'g', 'normalized_minor' => 9987, 'normalized_quantity' => '1', 'normalized_unit' => 'kg', 'normalization_formula' => 'USD 14.98 divided by 0.15 and rounded equals USD 99.87 per kg.',
		'sku_compliance_state' => 'import_label_review_required', 'claim_locator' => 'Exact 150 g block-type kudzu-starch listing displayed at USD 14.98.',
		'planning_price_minor' => 14900, 'planning_price_rationale' => 'USD 14.98 x ILS 3.0130 = ILS 45.13 source equivalent; ILS 149 is a held plan pending identity, organic, import and margin evidence.',
		'activation_gate_ids' => array( 'exact-identity', '100-percent-kudzu', 'organic-scope', 'supplier-quote', 'import-label', 'no-health-claims', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת 100 אחוז קודזו ואת תחולת האורגני ברמת ה-SKU המדויק. אין לפרסם טענות בריאות ממחקר קטגוריה.',
		'compliance_note_en' => 'Verify 100 percent kudzu and organic scope at exact SKU level. Do not publish health claims from category literature.',
	) ),
	$listing( array(
		'key' => 'yawataya-isogoro-sansho-12g', 'listing_entity_id' => 'listing-yawataya-isogoro-sansho-12g-20260806', 'knowledge_entity_id' => 'hub-japanese-ingredients',
		'source_id' => 'yawataya-sansho-12g-listing-2026', 'source_url' => 'https://japanesetaste.com/products/yawataya-isogoro-sansho-pepper-japanese-pepper-12g', 'seller_id' => 'seller-japanese-taste', 'market_id' => 'market-us-source', 'currency_id' => 'currency-usd',
		'amount_minor' => 2199, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'like_for_like',
		'name' => $c99_commerce_text( 'Yawataya Isogoro Sansho, 12 גרם', 'Yawataya Isogoro sansho, 12 g' ), 'product_family' => 'sansho', 'brand_id' => 'brand-yawataya-isogoro', 'manufacturer_id' => 'manufacturer-yawataya-isogoro',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'spices', 'sansho' ), 'compliance_rule_ids' => array( 'botanical-identity-review', 'origin-review', 'harvest-review', 'light-oxygen-moisture-review', 'import-label-review' ),
		'attributes' => array( 'pack_size' => '12 g', 'product_form' => 'ground sansho', 'category_science_scope' => 'category aroma research only, no SKU concentration' ),
		'quantity_decimal' => '12', 'quantity_unit' => 'g', 'normalized_minor' => 183250, 'normalized_quantity' => '1', 'normalized_unit' => 'kg', 'normalization_formula' => 'USD 21.99 divided by 0.012 equals USD 1,832.50 per kg.',
		'sku_compliance_state' => 'import_label_review_required', 'claim_locator' => 'Exact 12 g sansho listing displayed at USD 21.99.',
		'planning_price_minor' => 16900, 'planning_price_rationale' => 'USD 21.99 x ILS 3.0130 = ILS 66.26 source equivalent; ILS 169 is a held plan pending identity, freshness protection, import and margin evidence.',
		'activation_gate_ids' => array( 'botanical-species', 'origin', 'harvest', 'supplier-quote', 'light-oxygen-moisture', 'import-label', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת מין בוטני, מקור, בציר והגנה מאור, חמצן ולחות לפני הפעלה.',
		'compliance_note_en' => 'Verify botanical species, origin, harvest and light, oxygen and moisture protection before activation.',
	) ),
	$listing( array(
		'key' => 'marukyu-koyamaen-tenju-matcha-20g', 'listing_entity_id' => 'listing-marukyu-koyamaen-tenju-matcha-20g-20260806', 'knowledge_entity_id' => 'hub-japanese-ingredients',
		'source_id' => 'marukyu-tenju-matcha-20g-listing-2026', 'source_url' => 'https://www.marukyu-koyamaen.co.jp/motoan-shop/products/1111020c1/', 'seller_id' => 'seller-marukyu-koyamaen-direct', 'market_id' => 'market-jp-source', 'currency_id' => 'currency-jpy',
		'amount_minor' => 21600, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'sold_out_limited_allocation', 'comparability' => 'non_comparable',
		'name' => $c99_commerce_text( 'Marukyu Koyamaen Tenju Matcha, 20 גרם', 'Marukyu Koyamaen Tenju matcha, 20 g' ), 'product_family' => 'premium-matcha', 'brand_id' => 'brand-marukyu-koyamaen', 'manufacturer_id' => 'manufacturer-marukyu-koyamaen',
		'taxonomy_ids' => array( 'world-cuisines', 'japanese', 'tea', 'matcha' ), 'compliance_rule_ids' => array( 'allocation-review', 'identity-review', 'import-label-review', 'health-claim-hold' ),
		'attributes' => array( 'sku' => '1111020C1', 'pack_size' => '20 g', 'allocation_state' => 'limited', 'stock_state' => 'sold out', 'selling_context' => 'irregular selling or shortage context', 'category_science_scope' => 'category only, no SKU health claim' ),
		'quantity_decimal' => '20', 'quantity_unit' => 'g', 'normalized_minor' => 1080000, 'normalized_quantity' => '1', 'normalized_unit' => 'kg', 'normalization_formula' => 'JPY 21,600 per 20 g multiplied by 50 equals JPY 1,080,000 per kg.',
		'sku_compliance_state' => 'import_label_review_required', 'claim_locator' => 'Exact SKU 1111020C1, 20 g, displayed at JPY 21,600, sold out, with irregular-selling or shortage context.',
		'planning_price_minor' => 74900, 'planning_price_rationale' => 'JPY 21,600 x ILS 0.019088 = ILS 412.30 source equivalent; ILS 749 is a held plan pending stock, allocation, supplier, import and margin evidence.',
		'activation_gate_ids' => array( 'stock', 'allocation', 'exact-identity', 'supplier-quote', 'import-label', 'no-health-claims', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת את SKU 1111020C1 המדויק, מלאי והקצאה נוכחיים, תנאי ספק ותווית יבוא לפני הפעלה. אין לפרסם טענות בריאות.',
		'compliance_note_en' => 'Verify exact SKU 1111020C1, current stock and allocation, supplier terms and import label before activation. No health claims.',
	) ),
	$listing( array(
		'key' => 'yamaco-bamboo-makisu-27cm', 'listing_entity_id' => 'listing-yamaco-bamboo-makisu-27cm-20260806', 'knowledge_entity_id' => 'hub-japanese-equipment',
		'source_id' => 'yamaco-makisu-27cm-listing-2026', 'source_url' => 'https://www.mujostore.com/products/bamboo-sushi-mat', 'seller_id' => 'seller-mujo-australia', 'market_id' => 'market-au-source', 'currency_id' => 'currency-aud',
		'amount_minor' => 2800, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'listed_for_sale', 'comparability' => 'partially_comparable',
		'name' => $c99_commerce_text( 'Yamaco מחצלת במבוק Makisu, 27 ס״מ', 'Yamaco bamboo makisu, 27 cm' ), 'product_family' => 'makisu', 'brand_id' => 'brand-yamaco', 'manufacturer_id' => 'manufacturer-yamaco',
		'taxonomy_ids' => array( 'professional-equipment', 'japanese-tools', 'sushi-tools', 'makisu' ), 'compliance_rule_ids' => array( 'exact-variant-review', 'food-contact-material-review', 'finish-review', 'cleaning-review', 'warranty-review' ),
		'attributes' => array( 'size_claim' => '27 cm requires exact variant verification', 'material_claim' => 'bamboo', 'food_contact_state' => 'finish and care verification required' ),
		'quantity_decimal' => '1', 'quantity_unit' => 'each', 'normalized_minor' => 2800, 'normalized_quantity' => '1', 'normalized_unit' => 'each', 'normalization_formula' => 'AUD 28 for one listed mat equals AUD 28 per item.',
		'sku_compliance_state' => 'food_contact_material_review_required', 'claim_locator' => 'Bamboo sushi-mat listing displayed at AUD 28; exact 27 cm variant requires confirmation.',
		'planning_price_minor' => 12900, 'planning_price_rationale' => 'AUD 28 x ILS 2.1218 = ILS 59.41 source equivalent; ILS 129 is a held plan pending variant, food-contact, freight and margin evidence.',
		'activation_gate_ids' => array( 'exact-27cm-variant', 'food-contact', 'finish', 'cleaning', 'warranty', 'stock', 'supplier-quote', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת וריאנט מדויק של 27 ס״מ, גימור למגע עם מזון, שיטת ניקוי, אחריות ומלאי לפני הפעלה.',
		'compliance_note_en' => 'Verify exact 27 cm variant, food-contact finish, cleaning method, warranty and stock before activation.',
	) ),
	$listing( array(
		'key' => 'sakai-takayuki-ginsan-yanagiba-270mm', 'listing_entity_id' => 'listing-sakai-takayuki-ginsan-yanagiba-270mm-20260806', 'knowledge_entity_id' => 'equipment-yanagiba',
		'source_id' => 'sakai-takayuki-ginsan-yanagiba-270mm-listing-2026', 'source_url' => 'https://www.knivesandstones.com.au/products/sakai-takayuki-ginsan-yanagiba-270mm', 'seller_id' => 'seller-knives-stones-australia', 'market_id' => 'market-au-source', 'currency_id' => 'currency-aud',
		'amount_minor' => 39995, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'conflicting', 'comparability' => 'like_for_like',
		'name' => $c99_commerce_text( 'Sakai Takayuki Ginsan Yanagiba, 270 מ״מ', 'Sakai Takayuki Ginsan yanagiba, 270 mm' ), 'product_family' => 'yanagiba', 'brand_id' => 'brand-sakai-takayuki', 'manufacturer_id' => 'manufacturer-aoki-hamono',
		'taxonomy_ids' => array( 'professional-equipment', 'japanese-tools', 'knives', 'yanagiba' ), 'compliance_rule_ids' => array( 'availability-review', 'handedness-review', 'steel-review', 'hrc-review', 'dimensions-review', 'warranty-review', 'sharp-tool-review' ),
		'attributes' => array( 'blade_length' => '270 mm', 'steel_claim' => 'Ginsan or Silver 3 requires exact SKU verification', 'availability_state' => 'conflict unresolved', 'category_science_scope' => 'steel index is category context only' ),
		'quantity_decimal' => '1', 'quantity_unit' => 'each', 'normalized_minor' => 39995, 'normalized_quantity' => '1', 'normalized_unit' => 'each', 'normalization_formula' => 'AUD 399.95 for one exact 270 mm listing equals AUD 399.95 per item.',
		'sku_compliance_state' => 'food_contact_and_handling_review_required', 'claim_locator' => 'Exact 270 mm listing displayed at AUD 399.95; availability and specifications require reconciliation.',
		'planning_price_minor' => 179900, 'planning_price_rationale' => 'AUD 399.95 x ILS 2.1218 = ILS 848.61 source equivalent; ILS 1,799 is a held plan pending availability, specifications, shipping and margin approval.',
		'activation_gate_ids' => array( 'availability', 'handedness', 'steel', 'hrc', 'dimensions', 'warranty', 'shipping', 'supplier-quote', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת זמינות, התאמה ליד, פלדה מדויקת, HRC, מידות, אחריות, משלוח ומידע לטיפול בטוח לפני הפעלה.',
		'compliance_note_en' => 'Verify availability, handedness, exact steel, HRC, dimensions, warranty, shipping and safe-handling information before activation.',
	) ),
	$listing( array(
		'key' => 'nagatanien-kamado-san-3-cup', 'listing_entity_id' => 'listing-nagatanien-kamado-san-3-cup-20260806', 'knowledge_entity_id' => 'hub-japanese-equipment',
		'source_id' => 'nagatanien-kamado-san-3cup-listing-2026', 'source_url' => 'https://store.igamono.jp/?pid=85075826', 'seller_id' => 'seller-nagatanien-direct', 'market_id' => 'market-jp-source', 'currency_id' => 'currency-jpy',
		'amount_minor' => 16500, 'tax_state' => 'included', 'shipping_state' => 'unknown', 'availability_state' => 'sequential_shipment_after_late_september', 'comparability' => 'like_for_like',
		'name' => $c99_commerce_text( 'Nagatanien Kamado-san, 3 כוסות', 'Nagatanien Kamado-san, three cups' ), 'product_family' => 'donabe-rice-cooker', 'brand_id' => 'brand-nagatanien', 'manufacturer_id' => 'manufacturer-nagatanien',
		'taxonomy_ids' => array( 'professional-equipment', 'japanese-tools', 'rice-tools', 'donabe' ), 'compliance_rule_ids' => array( 'stove-compatibility-review', 'breakage-review', 'thermal-shock-review', 'lead-cadmium-review', 'weight-review', 'availability-review' ),
		'attributes' => array( 'model_code' => 'ACT-01', 'capacity' => 'three cups', 'availability_state' => 'sequential shipment after late September (9月下旬以降)', 'food_contact_state' => 'lead and cadmium documentation required' ),
		'quantity_decimal' => '1', 'quantity_unit' => 'each', 'normalized_minor' => 16500, 'normalized_quantity' => '1', 'normalized_unit' => 'each', 'normalization_formula' => 'JPY 16,500 for one exact three-cup model equals JPY 16,500 per item.',
		'sku_compliance_state' => 'food_contact_material_review_required', 'claim_locator' => 'Exact ACT-01 three-cup model displayed at JPY 16,500 including tax, with sequential shipment after late September (9月下旬以降).',
		'planning_price_minor' => 119900, 'planning_price_rationale' => 'JPY 16,500 x ILS 0.019088 = ILS 314.95 source equivalent; ILS 1,199 is a held plan pending breakage, freight, food-contact and margin evidence.',
		'activation_gate_ids' => array( 'sequential-shipment-availability', 'stove-compatibility', 'breakage', 'thermal-shock', 'lead-cadmium', 'weight', 'supplier-quote', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'יש לאמת זהות ACT-01 לשלוש כוסות, מועד משלוח מדורג, התאמה לכיריים, טיפול בשבר, הלם תרמי, תיעוד עופרת וקדמיום ומשקל לפני הפעלה.',
		'compliance_note_en' => 'Verify ACT-01 three-cup identity, sequential-shipment timing, stove compatibility, breakage handling, thermal shock, lead and cadmium documentation and weight before activation.',
	) ),
	$listing( array(
		'key' => 'kubo-komakichi-kazuho-chasen', 'listing_entity_id' => 'listing-kubo-komakichi-kazuho-chasen-20260806', 'knowledge_entity_id' => 'hub-japanese-equipment',
		'source_id' => 'kubo-komakichi-kazuho-chasen-listing-2026', 'source_url' => 'https://teaosakaya.theshop.jp/items/65610450', 'seller_id' => 'seller-tea-osakaya', 'market_id' => 'market-jp-source', 'currency_id' => 'currency-jpy',
		'amount_minor' => 5830, 'tax_state' => 'unknown', 'shipping_state' => 'unknown', 'availability_state' => 'sold_out', 'comparability' => 'non_comparable',
		'name' => $c99_commerce_text( 'Kubo Komakichi Kazuho Chasen', 'Kubo Komakichi Kazuho chasen' ), 'product_family' => 'chasen', 'brand_id' => 'brand-kubo-komakichi', 'manufacturer_id' => 'manufacturer-kubo-komakichi',
		'taxonomy_ids' => array( 'professional-equipment', 'japanese-tools', 'tea-tools', 'chasen' ), 'compliance_rule_ids' => array( 'maker-identity-review', 'tine-count-review', 'origin-review', 'stock-review', 'food-contact-material-review' ),
		'attributes' => array( 'style_claim' => 'Kazuho', 'maker_claim' => 'Kubo Komakichi', 'tine_claim' => 'approximately 70 requires verification', 'origin_claim' => 'Takayama requires verification', 'stock_state' => 'sold out', 'category_science_scope' => 'foam study is technique context only' ),
		'quantity_decimal' => '1', 'quantity_unit' => 'each', 'normalized_minor' => 5830, 'normalized_quantity' => '1', 'normalized_unit' => 'each', 'normalization_formula' => 'JPY 5,830 for one exact maker-attributed chasen equals JPY 5,830 per item.',
		'sku_compliance_state' => 'food_contact_material_review_required', 'claim_locator' => 'Exact Kubo Komakichi Kazuho listing displayed at JPY 5,830 and sold out; the maker-attributed record remains separate from generic Kazuho records.',
		'planning_price_minor' => 24900, 'planning_price_rationale' => 'JPY 5,830 x ILS 0.019088 = ILS 111.28 source equivalent; ILS 249 is a held plan pending maker, specification, stock and margin evidence.',
		'activation_gate_ids' => array( 'maker-identity', 'approximately-70-tines', 'takayama-origin', 'stock', 'food-contact', 'supplier-quote', 'landed-cost', 'margin', 'woo-acceptance' ),
		'compliance_note_he' => 'אין למזג עם Kazuho גנרי. יש לאמת יצרן, כ-70 שיניים, מקור Takayama, חומר למגע עם מזון ומלאי לפני הפעלה.',
		'compliance_note_en' => 'Do not merge with generic Kazuho. Verify maker, approximately 70 tines, Takayama origin, food-contact material and stock before activation.',
	) ),
);

$offer_configs = array();
foreach ( $premium_listings as $candidate ) {
	$is_tool = in_array( 'professional-equipment', $candidate['taxonomy_ids'], true );
	$offer_configs[] = array(
		'key' => $candidate['key'],
		'sku_id' => 'sku-' . $candidate['key'],
		'price_minor' => $candidate['planning_price_minor'],
		'evidence_artifact_ids' => array( 'evidence-' . $candidate['key'] . '-20260806', 'evidence-boi-fx-20260806' ),
		'category_he' => $is_tool ? 'ציוד מקצועי' : 'המזווה היפני',
		'category_en' => $is_tool ? 'Professional equipment' : 'Japanese pantry',
		'subcategory_he' => $is_tool ? 'כלי מטבח יפניים' : 'חומרי גלם פרימיום',
		'subcategory_en' => $is_tool ? 'Japanese culinary tools' : 'Premium ingredients',
		'food_tags' => array( $candidate['product_family'], 'planning-stock-0', 'held' ),
		'allergens' => false !== strpos( implode( '|', $candidate['compliance_rule_ids'] ), 'soy-allergen' ) ? array( 'soy' ) : array(),
	);
}

$brands = array();
$manufacturers = array();
$maker_records = array(
	array( 'maruyama-nori', 'Maruyama Nori', 'maruyama-nori', 'Maruyama Nori', 'maruyama-kontobi-5-listing-2026', 'seller-maruyama-nori-direct' ),
	array( 'tajima-jozo', 'Tajima Jozo', 'tajima-jozo', 'Tajima Jozo', 'tajima-red-sushi-vinegar-360ml-listing-2026', '' ),
	array( 'minamigura', 'Minamigura', 'minamigura', 'Minamigura', 'minamigura-tamari-200ml-listing-2026', '' ),
	array( 'sugimoto', 'Sugimoto', 'sugimoto', 'Sugimoto', 'sugimoto-shiitake-70g-listing-2026', '' ),
	array( 'yubaya', 'Yubaya', 'yubaya', 'Yubaya', 'yubaya-kyoto-yuba-home-2026', 'seller-yubaya-direct' ),
	array( 'ohsawa', 'Ohsawa', 'ohsawa', 'Ohsawa', 'ohsawa-kudzu-150g-listing-2026', '' ),
	array( 'yawataya-isogoro', 'Yawataya Isogoro', 'yawataya-isogoro', 'Yawataya Isogoro', 'yawataya-sansho-12g-listing-2026', '' ),
	array( 'marukyu-koyamaen', 'Marukyu Koyamaen', 'marukyu-koyamaen', 'Marukyu Koyamaen', 'marukyu-tenju-matcha-20g-listing-2026', 'seller-marukyu-koyamaen-direct' ),
	array( 'yamaco', 'Yamaco', 'yamaco', 'Yamaco', 'yamaco-makisu-27cm-listing-2026', '' ),
	array( 'sakai-takayuki', 'Sakai Takayuki', 'aoki-hamono', 'Aoki Hamono', 'sakai-takayuki-ginsan-yanagiba-270mm-listing-2026', '' ),
	array( 'nagatanien', 'Nagatanien', 'nagatanien', 'Nagatanien', 'nagatanien-kamado-san-3cup-listing-2026', 'seller-nagatanien-direct' ),
	array( 'kubo-komakichi', 'Kubo Komakichi', 'kubo-komakichi', 'Kubo Komakichi', 'kubo-komakichi-kazuho-chasen-listing-2026', '' ),
);
foreach ( $maker_records as $maker ) {
	$brands[] = array( 'id' => 'brand-' . $maker[0], 'name' => $c99_commerce_text( $maker[1], $maker[1] ), 'owner_seller_id' => $maker[5], 'identity_state' => 'listing_named', 'source_ids' => array( $maker[4] ) );
	$manufacturers[] = array( 'id' => 'manufacturer-' . $maker[2], 'name' => $c99_commerce_text( $maker[3], $maker[3] ), 'seller_id' => $maker[5], 'country_id' => 'country-jp', 'science_entity_id' => '', 'identity_state' => 'listing_named', 'source_ids' => array( $maker[4] ) );
}

$bundle = static function ( $id, $he, $en, $sku_ids ) use ( $c99_commerce_text ) {
	$components = array();
	$evidence_ids = array();
	foreach ( $sku_ids as $sku_id ) {
		$components[] = array( 'sku_id' => $sku_id, 'quantity_decimal' => '1', 'unit_code' => 'sku_unit', 'substitution_group' => '', 'required' => true );
		$evidence_ids[] = 'evidence-' . substr( $sku_id, 4 ) . '-20260806';
	}
	return array(
		'id' => $id, 'name' => $c99_commerce_text( $he, $en ), 'state' => 'draft', 'market_ids' => array( 'market-il-launch' ),
		'channel_ids' => array( 'channel-woo-web-il', 'channel-woo-b2b-il' ), 'components' => $components, 'inventory_policy' => 'component_managed',
		'channel_offer_id' => '', 'evidence_artifact_ids' => $evidence_ids, 'review_at' => '2026-09-06',
	);
};

$edge = static function ( $id, $type, $source, $target, $he, $en ) use ( $c99_commerce_text ) {
	return array(
		'id' => $id, 'type' => $type, 'source_sku_id' => $source, 'target_sku_id' => $target,
		'reason' => $c99_commerce_text( $he, $en ),
		'evidence_artifact_ids' => array( 'evidence-' . substr( $source, 4 ) . '-20260806', 'evidence-' . substr( $target, 4 ) . '-20260806' ),
		'state' => 'draft',
	);
};

return array(
	'listings' => $premium_listings,
	'offer_configs' => $offer_configs,
	'countries' => array( array( 'id' => 'country-au', 'iso2' => 'AU', 'name' => $c99_commerce_text( 'אוסטרליה', 'Australia' ) ) ),
	'currencies' => array( array( 'id' => 'currency-aud', 'code' => 'AUD', 'minor_unit_digits' => 2 ) ),
	'locales' => array( array( 'id' => 'locale-en-au', 'bcp47' => 'en-AU', 'language_code' => 'en', 'country_id' => 'country-au', 'label' => $c99_commerce_text( 'אנגלית אוסטרליה', 'English, Australia' ), 'content_state' => 'source_language', 'path_prefix' => '' ) ),
	'tax_zones' => array( array( 'id' => 'tax-zone-au-observation', 'country_id' => 'country-au', 'state' => 'unknown', 'basis' => $c99_commerce_text( 'טיפול המס הסופי לא היה גלוי בתצפיות האוסטרליות ואינו מוכלל לשוק.', 'Final tax treatment was not visible in the Australian observations and is not generalized to the market.' ), 'evidence_source_ids' => array( 'yamaco-makisu-27cm-listing-2026', 'sakai-takayuki-ginsan-yanagiba-270mm-listing-2026' ), 'review_at' => '2026-09-06' ) ),
	'markets' => array( array( 'id' => 'market-au-source', 'label' => $c99_commerce_text( 'שוק מקור אוסטרליה', 'Australia source market' ), 'country_id' => 'country-au', 'currency_id' => 'currency-aud', 'locale_ids' => array( 'locale-en-au' ), 'tax_zone_ids' => array( 'tax-zone-au-observation' ), 'seller_of_record_id' => '', 'fulfillment_region_ids' => array(), 'purpose' => 'source_price_observation', 'state' => 'source_observation' ) ),
	'sellers' => array(
		array( 'id' => 'seller-maruyama-nori-direct', 'name' => $c99_commerce_text( 'החנות הישירה Maruyama Nori', 'Maruyama Nori direct shop' ), 'seller_type' => 'producer_retailer', 'country_id' => 'country-jp', 'science_entity_id' => '', 'legal_identity_state' => 'listing_named', 'source_ids' => array( 'maruyama-kontobi-5-listing-2026' ), 'status' => 'market_source_only' ),
		array( 'id' => 'seller-yubaya-direct', 'name' => $c99_commerce_text( 'החנות הישירה Yubaya', 'Yubaya direct shop' ), 'seller_type' => 'producer_retailer', 'country_id' => 'country-jp', 'science_entity_id' => '', 'legal_identity_state' => 'listing_named', 'source_ids' => array( 'yubaya-kyoto-yuba-home-2026' ), 'status' => 'market_source_only' ),
		array( 'id' => 'seller-marukyu-koyamaen-direct', 'name' => $c99_commerce_text( 'החנות הישירה Marukyu Koyamaen', 'Marukyu Koyamaen direct shop' ), 'seller_type' => 'producer_retailer', 'country_id' => 'country-jp', 'science_entity_id' => '', 'legal_identity_state' => 'listing_named', 'source_ids' => array( 'marukyu-tenju-matcha-20g-listing-2026' ), 'status' => 'market_source_only' ),
		array( 'id' => 'seller-mujo-australia', 'name' => $c99_commerce_text( 'Mujo Store אוסטרליה', 'Mujo Store Australia' ), 'seller_type' => 'retailer', 'country_id' => 'country-au', 'science_entity_id' => '', 'legal_identity_state' => 'listing_named', 'source_ids' => array( 'yamaco-makisu-27cm-listing-2026' ), 'status' => 'market_source_only' ),
		array( 'id' => 'seller-knives-stones-australia', 'name' => $c99_commerce_text( 'Knives and Stones אוסטרליה', 'Knives and Stones Australia' ), 'seller_type' => 'retailer', 'country_id' => 'country-au', 'science_entity_id' => '', 'legal_identity_state' => 'listing_named', 'source_ids' => array( 'sakai-takayuki-ginsan-yanagiba-270mm-listing-2026' ), 'status' => 'market_source_only' ),
		array( 'id' => 'seller-nagatanien-direct', 'name' => $c99_commerce_text( 'החנות הישירה Nagatanien', 'Nagatanien direct shop' ), 'seller_type' => 'producer_retailer', 'country_id' => 'country-jp', 'science_entity_id' => '', 'legal_identity_state' => 'listing_named', 'source_ids' => array( 'nagatanien-kamado-san-3cup-listing-2026' ), 'status' => 'market_source_only' ),
		array( 'id' => 'seller-tea-osakaya', 'name' => $c99_commerce_text( 'Tea Osaka-ya', 'Tea Osaka-ya' ), 'seller_type' => 'retailer', 'country_id' => 'country-jp', 'science_entity_id' => '', 'legal_identity_state' => 'listing_named', 'source_ids' => array( 'kubo-komakichi-kazuho-chasen-listing-2026' ), 'status' => 'market_source_only' ),
	),
	'seller_source_extensions' => array(
		'seller-japanese-taste' => array( 'tajima-red-sushi-vinegar-360ml-listing-2026', 'minamigura-tamari-200ml-listing-2026', 'sugimoto-shiitake-70g-listing-2026', 'ohsawa-kudzu-150g-listing-2026', 'yawataya-sansho-12g-listing-2026' ),
	),
	'brands' => $brands,
	'manufacturers' => $manufacturers,
	'fx_evidence_artifact' => array(
		'id' => 'evidence-boi-fx-20260806', 'source_id' => 'bank-israel-exchange-rates-20260806', 'source_url' => $boi_url,
		'captured_at' => '2026-08-06T18:19:19Z', 'capture_method' => 'official-api-json-review',
		'claim_locator' => 'Official API lastUpdate 2026-08-06T12:21:04Z; retrieved 2026-08-06T18:19:19Z. Internal planning basis: USD 3.0130 ILS, JPY 1.9088 ILS per 100 JPY, AUD 2.1218 ILS.',
		'snapshot_digest' => '', 'snapshot_uri' => '', 'captured_by' => 'complete99-editorial-research', 'verification_state' => 'source_reviewed',
		'retention_state' => 'source_pointer_only', 'offer_approval_eligible' => false,
	),
	'bundles' => array(
		$bundle( 'bundle-edomae-sushi-lab-draft', 'מעבדת סושי אדומאה', 'Edomae Sushi Lab', array( 'sku-maruyama-gokujo-kontobi-nori-5-sheets', 'sku-tajima-red-sushi-vinegar-360ml', 'sku-minamigura-gin-warabeuta-tamari-200ml', 'sku-yamaco-bamboo-makisu-27cm', 'sku-sakai-takayuki-ginsan-yanagiba-270mm' ) ),
		$bundle( 'bundle-umami-shojin-lab-draft', 'מעבדת אומאמי ושוג׳ין', 'Umami and Shojin Lab', array( 'sku-sugimoto-organic-dried-shiitake-70g', 'sku-yubaya-kyoto-dried-yuba-100g', 'sku-ohsawa-organic-kudzu-starch-150g', 'sku-yawataya-isogoro-sansho-12g', 'sku-rishiri-kombu-100g' ) ),
		$bundle( 'bundle-matcha-ritual-draft', 'טקס מאצ׳ה', 'Matcha Ritual', array( 'sku-marukyu-koyamaen-tenju-matcha-20g', 'sku-kubo-komakichi-kazuho-chasen' ) ),
		$bundle( 'bundle-pro-sushi-tools-draft', 'כלי סושי מקצועיים', 'Pro Sushi Tools', array( 'sku-yamaco-bamboo-makisu-27cm', 'sku-sakai-takayuki-ginsan-yanagiba-270mm', 'sku-nagatanien-kamado-san-3-cup', 'sku-umezawa-hangiri-36cm' ) ),
		$bundle( 'bundle-seasonal-hassun-capsule-draft', 'קפסולת האסון עונתית', 'Seasonal Hassun Capsule', array( 'sku-yubaya-kyoto-dried-yuba-100g', 'sku-sugimoto-organic-dried-shiitake-70g', 'sku-yawataya-isogoro-sansho-12g', 'sku-maruyama-gokujo-kontobi-nori-5-sheets' ) ),
	),
	'merchandising_edges' => array(
		$edge( 'edge-kontobi-to-makisu', 'cross_sell', 'sku-maruyama-gokujo-kontobi-nori-5-sheets', 'sku-yamaco-bamboo-makisu-27cm', 'מחצלת גלגול משלימה שימוש בפוטומאקי.', 'A rolling mat complements futomaki use.' ),
		$edge( 'edge-vinegar-to-kontobi', 'cross_sell', 'sku-tajima-red-sushi-vinegar-360ml', 'sku-maruyama-gokujo-kontobi-nori-5-sheets', 'תיבול אורז ונורי מתחברים למסלול סושי, בלי להחליף את מפרט האורז.', 'Rice seasoning and nori connect in a sushi workflow without replacing the rice specification.' ),
		$edge( 'edge-tamari-to-kontobi', 'cross_sell', 'sku-minamigura-gin-warabeuta-tamari-200ml', 'sku-maruyama-gokujo-kontobi-nori-5-sheets', 'תיבול ומעטפת משלימים הגשת סושי.', 'Seasoning and wrapper complement sushi service.' ),
		$edge( 'edge-shiitake-to-kombu', 'cross_sell', 'sku-sugimoto-organic-dried-shiitake-70g', 'sku-rishiri-kombu-100g', 'שני חומרי גלם משלימים לבסיס אומאמי צמחי.', 'Two complementary ingredients for a plant-based umami foundation.' ),
		$edge( 'edge-yuba-to-tamari', 'cross_sell', 'sku-yubaya-kyoto-dried-yuba-100g', 'sku-minamigura-gin-warabeuta-tamari-200ml', 'יובה ותמרי יוצרים מסלול תיבול משלים.', 'Yuba and tamari form a complementary seasoning path.' ),
		$edge( 'edge-kudzu-to-sansho', 'cross_sell', 'sku-ohsawa-organic-kudzu-starch-150g', 'sku-yawataya-isogoro-sansho-12g', 'מסמיך ותבלין משלימים פיתוח מרקם וארומה.', 'A thickener and spice support texture and aroma development.' ),
		$edge( 'edge-matcha-to-chasen', 'cross_sell', 'sku-marukyu-koyamaen-tenju-matcha-20g', 'sku-kubo-komakichi-kazuho-chasen', 'המקצף הוא כלי הכנה משלים למאצ׳ה.', 'The whisk is a complementary preparation tool for matcha.' ),
		$edge( 'edge-makisu-to-yanagiba', 'cross_sell', 'sku-yamaco-bamboo-makisu-27cm', 'sku-sakai-takayuki-ginsan-yanagiba-270mm', 'מחצלת גלגול וכלי חיתוך ייעודי הם כלים משלימים במסלול סושי מקצועי ואינם תחליפים זה לזה.', 'A rolling mat and dedicated slicing tool are complementary in a professional sushi workflow and are not substitutes.' ),
		$edge( 'edge-kamadosan-to-hangiri', 'cross_sell', 'sku-nagatanien-kamado-san-3-cup', 'sku-umezawa-hangiri-36cm', 'כלי בישול וכלי תיבול וקירור משלימים תהליך אורז.', 'Cooking and seasoning and cooling vessels complement a rice workflow.' ),
	),
);
