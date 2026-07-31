<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_dish_content = static function ( $language, $dish_name ) {
	if ( 'he' === $language ) {
		return '<div class="c99-prose c99-dish-draft">'
			. '<aside class="c99-proof-note"><strong>מצב עריכה:</strong> נדרש אימות מקורות, בדיקת שף, בדיקת אלרגנים ואישור פרסום. אין להשתמש בעמוד כמתכון ייצור.</aside>'
			. '<h2>זהות המנה</h2><p>תיק המחקר של ' . $dish_name . ' נפתח כדי להפריד בין זיכרון משפחתי, מסורת מתועדת, גרסה ביתית וגרסת הייצור של קומפלט 99. לפני פרסום יירשמו שם המראיין, תאריך השיחה ומסמך או מקור חיצוני שניתן לבדוק.</p>'
			. '<h2>מקור ומסורת יהודית</h2><p><strong>נדרש מקור:</strong> יש לתעד באילו קהילות, אזורים ותקופות מופיעה המנה, אילו שמות חלופיים קיימים ומהו גבול הוודאות. לא תפורסם טענת מקור יחיד כאשר המקורות מציגים מסורות מקבילות.</p>'
			. '<h2>מרכיבים וטכניקה</h2><p><strong>נדרשת בדיקת מטבח:</strong> רשימת המרכיבים, כמויות, תשואה, איבוד, טמפרטורה, זמני הכנה, קירור והגשה יוזנו בגרסה מבוקרת. אלרגנים, כשרות והחלפות מרכיב יעברו בדיקה נפרדת.</p>'
			. '<h2>מתכון ציבורי לעומת BOM</h2><p>המתכון הציבורי ייכתב לאחר בדיקה חוזרת במטבח ביתי. מפרט הייצור, הספקים, העלות, מספרי האצווה והבקרות נשארים במערכת הפרטית ואינם נחשפים באתר.</p>'
			. '<h2>בריאות ותזונה</h2><p>לא תופיע הבטחה שהמנה ״בריאה״ או מתאימה למצב רפואי. אפשר לפרסם רכיבים ושיטת הכנה מאומתים; ניתוח תזונתי או המלצה דורשים חישוב ובדיקה של בעל מקצוע מוסמך.</p>'
			. '<h2>רשימת מקורות לפרסום</h2><ul><li>מקור היסטורי או מחקר קולינרי - חסר.</li><li>ראיון משפחתי מתועד - חסר.</li><li>פרוטוקול בדיקת מתכון ותשואה - חסר.</li><li>בדיקת אלרגנים ותזונה - חסרה.</li></ul>'
			. '</div>';
	}
	return '<div class="c99-prose c99-dish-draft">'
		. '<aside class="c99-proof-note"><strong>Editorial status:</strong> sources, chef test, allergen review and publication approval are required. Do not use this page as a production recipe.</aside>'
		. '<h2>Dish identity</h2><p>The research file for ' . $dish_name . ' separates family memory, documented tradition, a home version and the Complete99 production version. Before publication it will record the interviewer, interview date and a traceable external source.</p>'
		. '<h2>Origin and Jewish tradition</h2><p><strong>Source required:</strong> document the communities, regions and periods in which the dish appears, its alternative names and the limit of certainty. A single-origin claim will not be made when evidence shows parallel traditions.</p>'
		. '<h2>Ingredients and technique</h2><p><strong>Kitchen test required:</strong> ingredients, quantities, yield, loss, temperatures, preparation, cooling and service times belong in a controlled version. Allergens, kosher status and substitutions require separate review.</p>'
		. '<h2>Public recipe and private BOM</h2><p>A public recipe can follow a repeatable home-kitchen test. Production specifications, suppliers, costs, batch identifiers and controls remain in the private operating system.</p>'
		. '<h2>Health and nutrition</h2><p>No claim will describe the dish as “healthy” or suitable for a medical condition. Verified ingredients and methods may be published; nutrition analysis or advice requires calculation and review by a qualified professional.</p>'
		. '<h2>Publication source register</h2><ul><li>Historical or culinary research source - missing.</li><li>Documented family interview - missing.</li><li>Recipe and yield test record - missing.</li><li>Allergen and nutrition review - missing.</li></ul>'
		. '</div>';
};

$dishes = array(
	array( 'kubeh-beet-soup', 'מרק קובה סלק', 'Beet kubeh soup', 'c99-food-kubeh-beet-soup-gallery-2021-wp-v01.jpg' ),
	array( 'couscous-beef', 'קוסקוס עם בקר וירקות', 'Couscous with beef and vegetables', 'c99-food-couscous-beef-gallery-2021-wp-v01.jpg' ),
	array( 'sabich-plate', 'צלחת סביח', 'Sabich plate', 'c99-food-sabich-pita-gallery-2021-wp-v01.jpg' ),
	array( 'shakshuka', 'שקשוקה', 'Shakshuka', 'c99-food-shakshuka-plate-gallery-2021-wp-v01.jpg' ),
	array( 'yemenite-beef-soup', 'מרק בקר תימני', 'Yemenite beef soup', 'c99-food-yemenite-beef-soup-menu-2021-mishloha-v01.jpg' ),
	array( 'beef-meatballs-gravy', 'קציצות בקר ברוטב', 'Beef meatballs in sauce', 'c99-food-beef-meatballs-gravy-gallery-2021-wp-v01.jpg' ),
);

$records = array();
foreach ( $dishes as $dish ) {
	$records[] = array(
		'key'          => 'dish-' . $dish[0],
		'type'         => 'c99_dish',
		'slug'         => array( 'he' => $dish[0], 'en' => 'en-' . $dish[0] ),
		'title'        => array( 'he' => $dish[1], 'en' => $dish[2] ),
		'excerpt'      => array(
			'he' => 'תיק תוכן ומחקר בבדיקה: מסורת, מקורות, מתכון, ייצור, אלרגנים ותזונה.',
			'en' => 'An editorial research file in review: tradition, sources, recipe, production, allergens and nutrition.',
		),
		'content'      => array(
			'he' => $c99_dish_content( 'he', $dish[1] ),
			'en' => $c99_dish_content( 'en', $dish[2] ),
		),
		'image'        => $dish[3],
		'status'       => 'draft',
		'verification' => 'verification_required',
		'recipe'       => array(
			'yield'              => '',
			'prep_minutes'       => '',
			'cook_minutes'       => '',
			'ingredients'        => array(),
			'instructions'       => array(),
			'sources'            => array(),
			'authoritative_sources' => array(),
			'source_notes'       => array(),
			'allergens'          => array(),
			'weights'            => array(),
			'temperatures'       => array(),
			'test_date'          => '',
			'kitchen_test_id'    => '',
			'recipe_version'     => '',
			'image_approved'     => false,
			'he_editor'          => '',
			'en_editor'          => '',
			'editorial_reviewed_at' => '',
			'originality_reviewed' => false,
			'nutrition_reviewed' => false,
			'health_claims_present' => false,
		),
	);
}

return $records;
