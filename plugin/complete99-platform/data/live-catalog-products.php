<?php
/**
 * Public WooCommerce materialization policy for the exact approved catalog.
 *
 * Product identity, bilingual names and source images continue to come from
 * catalog-product-seeds.php. This file contains only public-sale policy and
 * operational copy that must not mutate the private evaluation registry.
 *
 * @package Complete99_Platform
 */

defined( 'ABSPATH' ) || exit;

$c99_live_product = static function ( $weight_kg, $category, $tags, $shipping_class, $ingredients_he, $ingredients_en, $allergens_he, $allergens_en, $storage_he, $storage_en, $description_he = '', $description_en = '' ) {
	return array(
		'weight_kg'      => (string) $weight_kg,
		'category'       => $category,
		'tags'           => array_values( $tags ),
		'shipping_class' => $shipping_class,
		'ingredients'    => array( 'he' => $ingredients_he, 'en' => $ingredients_en ),
		'allergens'      => array( 'he' => $allergens_he, 'en' => $allergens_en ),
		'storage'        => array( 'he' => $storage_he, 'en' => $storage_en ),
		'description'    => array( 'he' => $description_he, 'en' => $description_en ),
	);
};

$ambient_he = 'יש לשמור באריזה הסגורה במקום קריר ויבש ולפעול לפי הוראות האחסון שעל אריזת הספק.';
$ambient_en = 'Keep the sealed pack in a cool, dry place and follow the supplier storage instructions on the pack.';
$fresh_he   = 'יש לשמור בתנאים המתאימים לתוצרת טרייה, לשטוף לפני שימוש ולפעול לפי הוראות הספק.';
$fresh_en   = 'Keep under conditions suitable for fresh produce, wash before use and follow the supplier instructions.';
$cold_he    = 'יש לשמור בקירור רציף ולפעול לפי הטמפרטורה ותאריך השימוש שעל אריזת הספק.';
$cold_en    = 'Keep continuously refrigerated and follow the temperature and use-by date on the supplier pack.';
$frozen_he  = 'יש לשמור בהקפאה רציפה ולפעול לפי הוראות ההפשרה והבישול שעל אריזת הספק.';
$frozen_en  = 'Keep continuously frozen and follow the defrosting and cooking instructions on the supplier pack.';
$label_he   = 'יש לקרוא את הצהרת האלרגנים המלאה שעל אריזת הספק לפני שימוש.';
$label_en   = 'Read the complete allergen declaration on the supplier pack before use.';
$none_he    = 'לא ידוע על אלרגן מובנה במוצר הגולמי. יש לבדוק את אריזת הספק לפני שימוש.';
$none_en    = 'No inherent allergen is known for the raw item. Check the supplier pack before use.';

return array(
	'schema'             => 'complete99-live-catalog-products/v1',
	'reviewed_at'        => '2026-08-06',
	'catalog_publication_authorized' => true,
	'supplier_label_reviewed'        => false,
	'country_of_origin_reviewed'     => false,
	'checkout_eligible'              => false,
	'initial_stock'      => 1,
	'backorders'         => 'no',
	'tax_status'         => 'taxable',
	'fulfilment'         => array(
		'he' => 'איסוף עצמי מאבן גבירול 99, תל אביב, לפי המלאי בזמן אישור ההזמנה.',
		'en' => 'Pickup from 99 Ibn Gabirol Street, Tel Aviv, subject to stock when the order is confirmed.',
	),
	'categories'         => array(
		'pantry' => array( 'name' => 'מזווה | Pantry', 'slug' => 'complete99-pantry' ),
		'bakery' => array( 'name' => 'מאפים | Bakery', 'slug' => 'complete99-bakery' ),
		'produce' => array( 'name' => 'ירקות ועשבי תיבול | Produce and herbs', 'slug' => 'complete99-produce' ),
		'eggs' => array( 'name' => 'ביצים | Eggs', 'slug' => 'complete99-eggs' ),
		'protein' => array( 'name' => 'בשר, עוף ודגים | Meat, poultry and fish', 'slug' => 'complete99-protein' ),
		'japanese-pantry' => array( 'name' => 'המזווה היפני | Japanese pantry', 'slug' => 'complete99-japanese-pantry' ),
	),
	'tags'               => array(
		'ambient' => array( 'name' => 'מדף | Ambient', 'slug' => 'complete99-ambient' ),
		'fresh' => array( 'name' => 'טרי | Fresh', 'slug' => 'complete99-fresh' ),
		'chilled' => array( 'name' => 'בקירור | Chilled', 'slug' => 'complete99-chilled' ),
		'frozen' => array( 'name' => 'קפוא | Frozen', 'slug' => 'complete99-frozen' ),
		'condiment' => array( 'name' => 'רטבים וממרחים | Condiments', 'slug' => 'complete99-condiment' ),
		'grain' => array( 'name' => 'דגנים וקטניות | Grains and pulses', 'slug' => 'complete99-grain-pulse' ),
		'spice' => array( 'name' => 'תבלינים | Spices', 'slug' => 'complete99-spice' ),
		'produce' => array( 'name' => 'תוצרת טרייה | Fresh produce', 'slug' => 'complete99-fresh-produce' ),
		'protein' => array( 'name' => 'חלבון מן החי | Animal protein', 'slug' => 'complete99-animal-protein' ),
		'japanese' => array( 'name' => 'יפני | Japanese', 'slug' => 'complete99-japanese' ),
		'dashi' => array( 'name' => 'דאשי ואומאמי | Dashi and umami', 'slug' => 'complete99-dashi-umami' ),
		'fish' => array( 'name' => 'דגים | Fish', 'slug' => 'complete99-fish' ),
		'fermentation' => array( 'name' => 'התססה | Fermentation', 'slug' => 'complete99-fermentation' ),
		'shoyu' => array( 'name' => 'שויו | Shoyu', 'slug' => 'complete99-shoyu' ),
		'soy' => array( 'name' => 'סויה | Soy', 'slug' => 'complete99-soy' ),
		'wheat' => array( 'name' => 'חיטה | Wheat', 'slug' => 'complete99-wheat' ),
		'seasoning' => array( 'name' => 'תיבול | Seasoning', 'slug' => 'complete99-seasoning' ),
		'yuzu' => array( 'name' => 'יוזו | Yuzu', 'slug' => 'complete99-yuzu' ),
		'citrus' => array( 'name' => 'הדרים | Citrus', 'slug' => 'complete99-citrus' ),
		'premium' => array( 'name' => 'פרימיום | Premium', 'slug' => 'complete99-premium' ),
	),
	'shipping_classes'   => array(
		'ambient' => array( 'name' => 'משלוח מדף | Ambient delivery', 'slug' => 'complete99-ambient-delivery' ),
		'fresh' => array( 'name' => 'משלוח טרי | Fresh delivery', 'slug' => 'complete99-fresh-delivery' ),
		'chilled' => array( 'name' => 'שרשרת קירור | Chilled delivery', 'slug' => 'complete99-chilled-delivery' ),
		'frozen' => array( 'name' => 'שרשרת הקפאה | Frozen delivery', 'slug' => 'complete99-frozen-delivery' ),
	),
	'products'           => array(
		'product-tahini-500g' => $c99_live_product( '0.500', 'pantry', array( 'ambient', 'condiment' ), 'ambient', 'שומשום טחון.', 'Ground sesame seeds.', 'מכיל שומשום. יש לבדוק את הצהרת האלרגנים שעל האריזה.', 'Contains sesame. Check the allergen declaration on the pack.', $ambient_he, $ambient_en ),
		'product-amba-500g' => $c99_live_product( '0.500', 'pantry', array( 'ambient', 'condiment' ), 'ambient', 'רוטב עמבה על בסיס מנגו. רשימת המרכיבים המלאה מופיעה על אריזת הספק.', 'Mango-based amba sauce. The complete ingredient list appears on the supplier pack.', $label_he, $label_en, $ambient_he, $ambient_en ),
		'product-hot-sauce-60ml' => $c99_live_product( '0.060', 'pantry', array( 'ambient', 'condiment' ), 'ambient', 'רוטב פלפל חריף. רשימת המרכיבים המלאה מופיעה על אריזת הספק.', 'Hot pepper sauce. The complete ingredient list appears on the supplier pack.', $label_he, $label_en, $ambient_he, $ambient_en ),
		'product-pita-12x50g' => $c99_live_product( '0.600', 'bakery', array( 'fresh', 'grain' ), 'fresh', 'פיתות על בסיס קמח חיטה. רשימת המרכיבים המלאה מופיעה על אריזת הספק.', 'Wheat-flour pita bread. The complete ingredient list appears on the supplier pack.', 'מכיל חיטה וגלוטן. יש לבדוק אלרגנים נוספים על האריזה.', 'Contains wheat and gluten. Check the pack for additional allergens.', 'יש לשמור לפי הוראות המאפייה או הספק ולצרוך במסגרת חיי המדף המצוינים על האריזה.', 'Store as directed by the bakery or supplier and use within the shelf life shown on the pack.' ),
		'product-aubergine-1kg' => $c99_live_product( '1.000', 'produce', array( 'fresh', 'produce' ), 'fresh', 'חציל טרי.', 'Fresh aubergine.', $none_he, $none_en, $fresh_he, $fresh_en ),
		'product-eggs-l-12' => $c99_live_product( '0.750', 'eggs', array( 'fresh', 'chilled', 'protein' ), 'chilled', 'ביצי תרנגולת טריות בגודל L.', 'Fresh size L hen eggs.', 'מכיל ביצים.', 'Contains egg.', $cold_he, $cold_en ),
		'product-potato-white-1kg' => $c99_live_product( '1.000', 'produce', array( 'fresh', 'produce' ), 'fresh', 'תפוח אדמה לבן טרי.', 'Fresh white potato.', $none_he, $none_en, 'יש לשמור במקום קריר, חשוך ומאוורר ולפעול לפי הוראות הספק.', 'Keep in a cool, dark, ventilated place and follow the supplier instructions.' ),
		'product-tomato-1kg' => $c99_live_product( '1.000', 'produce', array( 'fresh', 'produce' ), 'fresh', 'עגבנייה טרייה.', 'Fresh tomato.', $none_he, $none_en, $fresh_he, $fresh_en ),
		'product-cucumber-1kg' => $c99_live_product( '1.000', 'produce', array( 'fresh', 'produce' ), 'fresh', 'מלפפון טרי.', 'Fresh cucumber.', $none_he, $none_en, $fresh_he, $fresh_en ),
		'product-onion-dry-1kg' => $c99_live_product( '1.000', 'produce', array( 'fresh', 'produce' ), 'fresh', 'בצל יבש.', 'Dry onion.', $none_he, $none_en, 'יש לשמור במקום קריר, יבש ומאוורר ולפעול לפי הוראות הספק.', 'Keep in a cool, dry, ventilated place and follow the supplier instructions.' ),
		'product-parsley-100g' => $c99_live_product( '0.100', 'produce', array( 'fresh', 'produce' ), 'fresh', 'פטרוזיליה טרייה.', 'Fresh parsley.', $none_he, $none_en, $fresh_he, $fresh_en ),
		'product-chickpeas-dry-500g' => $c99_live_product( '0.500', 'pantry', array( 'ambient', 'grain' ), 'ambient', 'גרגרי חומוס יבשים.', 'Dry chickpeas.', $none_he, $none_en, $ambient_he, $ambient_en ),
		'product-beetroot-1kg' => $c99_live_product( '1.000', 'produce', array( 'fresh', 'produce' ), 'fresh', 'סלק אדום טרי.', 'Fresh red beetroot.', $none_he, $none_en, $fresh_he, $fresh_en ),
		'product-bulgur-fine-500g' => $c99_live_product( '0.500', 'pantry', array( 'ambient', 'grain' ), 'ambient', 'בורגול דק מחיטה.', 'Fine bulgur wheat.', 'מכיל חיטה וגלוטן.', 'Contains wheat and gluten.', $ambient_he, $ambient_en ),
		'product-couscous-1kg' => $c99_live_product( '1.000', 'pantry', array( 'ambient', 'grain' ), 'ambient', 'קוסקוס מסולת חיטה. רשימת המרכיבים המלאה מופיעה על אריזת הספק.', 'Couscous made from wheat semolina. The complete ingredient list appears on the supplier pack.', 'מכיל חיטה וגלוטן. יש לבדוק אלרגנים נוספים על האריזה.', 'Contains wheat and gluten. Check the pack for additional allergens.', $ambient_he, $ambient_en ),
		'product-chicken-breast-1kg' => $c99_live_product( '1.000', 'protein', array( 'fresh', 'chilled', 'protein' ), 'chilled', 'חזה עוף טרי.', 'Fresh chicken breast.', $none_he, $none_en, $cold_he, $cold_en ),
		'product-breadcrumbs-500g' => $c99_live_product( '0.500', 'pantry', array( 'ambient', 'grain' ), 'ambient', 'פירורי לחם על בסיס חיטה. רשימת המרכיבים המלאה מופיעה על אריזת הספק.', 'Wheat-based breadcrumbs. The complete ingredient list appears on the supplier pack.', 'מכיל חיטה וגלוטן. יש לבדוק אלרגנים נוספים על האריזה.', 'Contains wheat and gluten. Check the pack for additional allergens.', $ambient_he, $ambient_en ),
		'product-ground-beef-1kg' => $c99_live_product( '1.000', 'protein', array( 'fresh', 'chilled', 'protein' ), 'chilled', 'בשר בקר טחון טרי.', 'Fresh ground beef.', $none_he, $none_en, $cold_he, $cold_en ),
		'product-tilapia-fillet-1kg' => $c99_live_product( '1.000', 'protein', array( 'frozen', 'protein' ), 'frozen', 'פילה דג אמנון קפוא.', 'Frozen tilapia fish fillet.', 'מכיל דגים.', 'Contains fish.', $frozen_he, $frozen_en ),
		'product-tomato-sauce-400g' => $c99_live_product( '0.400', 'pantry', array( 'ambient', 'condiment' ), 'ambient', 'רוטב עגבניות מרוכז. רשימת המרכיבים המלאה מופיעה על אריזת הספק.', 'Concentrated tomato sauce. The complete ingredient list appears on the supplier pack.', $label_he, $label_en, $ambient_he, $ambient_en ),
		'product-rice-persian-1kg' => $c99_live_product( '1.000', 'pantry', array( 'ambient', 'grain' ), 'ambient', 'אורז בסגנון פרסי.', 'Persian-style rice.', $none_he, $none_en, $ambient_he, $ambient_en ),
		'product-beef-shank-1kg' => $c99_live_product( '1.000', 'protein', array( 'fresh', 'chilled', 'protein' ), 'chilled', 'שריר זרוע בקר טרי.', 'Fresh beef shank.', $none_he, $none_en, $cold_he, $cold_en ),
		'product-hawayej-soup-100g' => $c99_live_product( '0.100', 'pantry', array( 'ambient', 'spice' ), 'ambient', 'תערובת תבליני חוויאג׳ למרק. ההרכב המלא מופיע על אריזת הספק.', 'Hawayej spice blend for soup. The complete blend appears on the supplier pack.', $label_he, $label_en, $ambient_he, $ambient_en ),
		'product-olive-oil-750ml' => $c99_live_product( '0.690', 'pantry', array( 'ambient' ), 'ambient', 'שמן זית כתית מעולה.', 'Extra virgin olive oil.', $none_he, $none_en, $ambient_he, $ambient_en ),
		'product-pickles-brine-320g' => $c99_live_product( '0.320', 'pantry', array( 'ambient', 'condiment' ), 'ambient', 'מלפפונים במי מלח. רשימת המרכיבים המלאה מופיעה על אריזת הספק.', 'Cucumbers in brine. The complete ingredient list appears on the supplier pack.', $label_he, $label_en, $ambient_he, $ambient_en ),
		'product-chicken-liver-1kg' => $c99_live_product( '1.000', 'protein', array( 'fresh', 'chilled', 'protein' ), 'chilled', 'כבד עוף טרי.', 'Fresh chicken liver.', $none_he, $none_en, $cold_he, $cold_en ),
		'product-rishiri-kombu-100g' => $c99_live_product( '0.100', 'japanese-pantry', array( 'ambient', 'japanese', 'dashi' ), 'ambient', 'אצת קומבו מיובשת מסוג רישירי.', 'Dried Rishiri-type kombu seaweed.', 'יש לקרוא את הצהרת האלרגנים והמגע הצולב שעל אריזת הספק. אצות עשויות להכיל יוד בכמות משמעותית.', 'Read the supplier allergen and cross-contact declaration. Seaweed may contain a significant amount of iodine.', $ambient_he, $ambient_en ),
		'product-honkarebushi-200g' => $c99_live_product( '0.200', 'japanese-pantry', array( 'ambient', 'japanese', 'dashi', 'fish' ), 'ambient', 'בלוק דג בוניטו מבושל, מעושן, מיובש ומיושן בתהליך קצואובושי. יש לפעול לפי רשימת המרכיבים שעל אריזת הספק.', 'A cooked, smoked, dried and matured bonito block made by the katsuobushi process. Follow the ingredient list on the supplier pack.', 'מכיל דגים. יש לבדוק מגע צולב ואלרגנים נוספים על אריזת הספק.', 'Contains fish. Check the supplier pack for cross-contact and additional allergens.', $ambient_he, $ambient_en ),
		'product-yamaroku-tsurubishio-500ml' => $c99_live_product(
			'0.570',
			'japanese-pantry',
			array( 'ambient', 'japanese', 'fermentation', 'shoyu', 'soy', 'wheat', 'seasoning', 'premium' ),
			'ambient',
			'סויה, חיטה ומלח, לפי תיאור היצרן. התווית שעל המוצר המסופק היא הקובעת.',
			'Soybeans, wheat and salt, according to the producer description. The supplied product label is authoritative.',
			'מכיל סויה וחיטה, לרבות גלוטן. יש לקרוא את הצהרת האלרגנים שעל האריזה.',
			'Contains soy and wheat, including gluten. Read the allergen declaration on the pack.',
			'יש לשמור במקום קריר ומוצל. לאחר פתיחה יש לפעול לפי הוראות היצרן שעל האריזה.',
			'Keep in a cool, shaded place. After opening, follow the producer instructions on the pack.',
			'שויו סאישיקומי בעל גוף עמוק, המיוצר במחזור יישון נוסף בקיוקה. מתאים לדאשי, רטבי טבילה, זיגוגים ותיבול מדויק שבו נדרשת עוצמת אומאמי.',
			'A deep-bodied saishikomi shoyu made with an additional maturation cycle in kioke. Suited to dashi, dipping sauces, glazes and precise seasoning where concentrated umami is wanted.'
		),
		'product-kito-yuzu-juice-100ml' => $c99_live_product(
			'0.160',
			'japanese-pantry',
			array( 'ambient', 'japanese', 'seasoning', 'yuzu', 'citrus', 'premium' ),
			'ambient',
			'100% מיץ יוזו, לפי תיאור היצרן. התווית שעל המוצר המסופק היא הקובעת.',
			'100% yuzu juice, according to the producer description. The supplied product label is authoritative.',
			'לא ידוע על אלרגן מובנה במיץ הפרי. יש לבדוק את אריזת הספק למגע צולב.',
			'No inherent allergen is known for the fruit juice. Check the supplier pack for cross-contact.',
			'יש לשמור לפי התווית ולקרר לאחר הפתיחה. יש להשתמש במסגרת חיי המדף המצוינים על הבקבוק.',
			'Store as directed on the label and refrigerate after opening. Use within the shelf life shown on the bottle.',
			'מיץ יוזו ראשון בעל חומציות בהירה וארומה הדרית מורכבת. מיועד לרטבים, ויניגרט, דאשי, קינוחים ומשקאות ללא צורך להוסיף ממתיקים למוצר עצמו.',
			'First-press yuzu juice with bright acidity and a layered citrus aroma. Intended for sauces, vinaigrettes, dashi, desserts and drinks without adding sweetener to the product itself.'
		),
	),
);
