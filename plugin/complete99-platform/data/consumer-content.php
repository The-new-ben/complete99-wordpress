<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_consumer_page = static function ( $intro, $sections, $editorial = array() ) {
	$html = '<div class="c99-prose c99-consumer-prose"><p class="c99-lead-copy">' . $intro . '</p>';
	foreach ( $sections as $section ) {
		$html .= '<section><h2>' . $section[0] . '</h2>';
		foreach ( $section[1] as $paragraph ) {
			$html .= '<p>' . $paragraph . '</p>';
		}
		$html .= '</section>';
	}
	if ( ! empty( $editorial ) ) {
		$html .= '<footer class="c99-consumer-editorial-meta"><h2>' . $editorial['heading'] . '</h2>'
			. '<dl class="c99-consumer-editorial-facts"><div><dt>' . $editorial['owner_label'] . '</dt><dd>' . $editorial['owner'] . '</dd></div>'
			. '<div><dt>' . $editorial['review_label'] . '</dt><dd><time datetime="' . $editorial['reviewed_at'] . '">' . $editorial['review_date'] . '</time></dd></div></dl>'
			. '<h3>' . $editorial['sources_heading'] . '</h3><ul class="c99-consumer-source-list">';
		foreach ( $editorial['sources'] as $source ) {
			$html .= '<li><a href="' . $source['url'] . '" target="_blank" rel="external noopener noreferrer">' . $source['label'] . '</a>'
				. '<span>' . $source['note'] . '</span></li>';
		}
		$html .= '</ul></footer>';
	}
	return $html . '</div>';
};

$c99_consumer_editorial = array(
	'ingredients' => array(
		'reviewed_at' => '2026-07-29',
		'he'          => array(
			'heading'         => 'אחריות עריכה ומקורות',
			'owner_label'     => 'אחריות עריכה',
			'owner'           => 'קומפלט 99',
			'review_label'    => 'תאריך בדיקת העמוד',
			'reviewed_at'     => '2026-07-29',
			'review_date'     => '29 ביולי 2026',
			'sources_heading' => 'מקורות והמשך קריאה',
			'sources'         => array(
				array(
					'label' => 'משרד הבריאות: בטיחות והיגיינה במזון',
					'url'   => 'https://www.gov.il/he/pages/food-hygiene?chapterIndex=2',
					'note'  => 'הנחיות לצרכנים על קירור, הפרדה ואחסון מזון בבית.',
				),
				array(
					'label' => 'משרד הבריאות: תווית מזון וסימון תזונתי',
					'url'   => 'https://www.gov.il/he/departments/guides/food-labeling?chapterIndex=5',
					'note'  => 'מקור רשמי לקריאת תוויות ורשימות רכיבים במזון ארוז מראש.',
				),
				array(
					'label' => 'משרד הבריאות: מידע על אלרגיות',
					'url'   => 'https://me.health.gov.il/parenting/raising-children/common-childhood-diseases-and-symptoms/medical-conditions/common-allergies/',
					'note'  => 'רקע רפואי רשמי על אלרגיה ועל הצורך בבירור מקצועי.',
				),
				array(
					'label' => 'ארגון הבריאות העולמי: חמשת המפתחות למזון בטוח',
					'url'   => 'https://www.who.int/activities/promoting-safe-food-handling/five-key-to-safer-food/',
					'note'  => 'עקרונות כלליים לניקיון, הפרדה, בישול ושמירה בטמפרטורה בטוחה.',
				),
			),
		),
		'en'          => array(
			'heading'         => 'Editorial responsibility and sources',
			'owner_label'     => 'Editorial owner',
			'owner'           => 'Complete99',
			'review_label'    => 'Page review date',
			'reviewed_at'     => '2026-07-29',
			'review_date'     => '29 July 2026',
			'sources_heading' => 'Sources and further reading',
			'sources'         => array(
				array(
					'label' => 'Israel Ministry of Health: food hygiene and handling',
					'url'   => 'https://www.gov.il/he/pages/food-hygiene?chapterIndex=2',
					'note'  => 'Official consumer guidance in Hebrew on refrigeration, separation and home storage.',
				),
				array(
					'label' => 'Israel Ministry of Health: food and nutrition labelling',
					'url'   => 'https://www.gov.il/en/pages/food-labeling?chapterIndex=4',
					'note'  => 'Official guidance for reading labels and ingredient lists on prepacked food.',
				),
				array(
					'label' => 'Israel Ministry of Health: allergy information',
					'url'   => 'https://me.health.gov.il/parenting/raising-children/common-childhood-diseases-and-symptoms/medical-conditions/common-allergies/',
					'note'  => 'Official Hebrew medical background on allergies and professional assessment.',
				),
				array(
					'label' => 'World Health Organization: Five Keys to Safer Food',
					'url'   => 'https://www.who.int/activities/promoting-safe-food-handling/five-key-to-safer-food/',
					'note'  => 'General principles covering cleanliness, separation, cooking and safe temperatures.',
				),
			),
		),
	),
	'traditions'  => array(
		'reviewed_at' => '2026-07-29',
		'he'          => array(
			'heading'         => 'אחריות עריכה ומקורות',
			'owner_label'     => 'אחריות עריכה',
			'owner'           => 'קומפלט 99',
			'review_label'    => 'תאריך בדיקת העמוד',
			'reviewed_at'     => '2026-07-29',
			'review_date'     => '29 ביולי 2026',
			'sources_heading' => 'מקורות והמשך קריאה',
			'sources'         => array(
				array(
					'label' => 'אונסק"ו: הידע והמנהגים של הכנת קוסקוס ואכילתו',
					'url'   => 'https://ich.unesco.org/en/RL/knowledge-know-how-and-practices-pertaining-to-the-production-and-consumption-of-couscous-01602?lang=en',
					'note'  => 'רישום מורשת רשמי המבוסס על הגשה משותפת של אלג׳יריה, מאוריטניה, מרוקו ותוניסיה.',
				),
				array(
					'label' => 'FOODISH של מוזיאון העם היהודי: הסיפור של הקוסקוס',
					'url'   => 'https://foodish.anumuseum.org.il/the-couscous-story/',
					'note'  => 'הקשר היסטורי ותרבותי לקוסקוס בקהילות המגרב ובישראל.',
				),
				array(
					'label' => 'Jewish Food Society: סיפור משפחתי על סביח',
					'url'   => 'https://www.jewishfoodsociety.org/stories/when-your-father-shares-a-name-with-a-national-dish',
					'note'  => 'עדות משפחתית והקשר למסורת ארוחת השבת של יהודי עיראק.',
				),
				array(
					'label' => 'Jewish Food Society: קובה במרק סלק',
					'url'   => 'https://www.jewishfoodsociety.org/recipes/beet-kubbeh-soup',
					'note'  => 'מתכון מתועד וסיפור העברה משפחתי שמדגימים גרסה אחת מתוך משפחת הקובה.',
				),
			),
		),
		'en'          => array(
			'heading'         => 'Editorial responsibility and sources',
			'owner_label'     => 'Editorial owner',
			'owner'           => 'Complete99',
			'review_label'    => 'Page review date',
			'reviewed_at'     => '2026-07-29',
			'review_date'     => '29 July 2026',
			'sources_heading' => 'Sources and further reading',
			'sources'         => array(
				array(
					'label' => 'UNESCO: knowledge and practices of producing and eating couscous',
					'url'   => 'https://ich.unesco.org/en/RL/knowledge-know-how-and-practices-pertaining-to-the-production-and-consumption-of-couscous-01602?lang=en',
					'note'  => 'The official heritage entry from a joint submission by Algeria, Mauritania, Morocco and Tunisia.',
				),
				array(
					'label' => 'FOODISH at ANU Museum: The Story of Couscous',
					'url'   => 'https://foodish.anumuseum.org.il/en/the-story-of-couscous/',
					'note'  => 'Historical and cultural context for couscous in Maghrebi Jewish communities and Israel.',
				),
				array(
					'label' => 'Jewish Food Society: When Your Father Shares a Name with a National Dish',
					'url'   => 'https://www.jewishfoodsociety.org/stories/when-your-father-shares-a-name-with-a-national-dish',
					'note'  => 'A family account connecting sabich with Iraqi Jewish Shabbat breakfast.',
				),
				array(
					'label' => 'Jewish Food Society: Beet Kubbeh Soup',
					'url'   => 'https://www.jewishfoodsociety.org/recipes/beet-kubbeh-soup',
					'note'  => 'A documented recipe and family transmission story showing one version within the kubbeh family.',
				),
			),
		),
	),
	'knowledge'   => array(
		'reviewed_at' => '2026-07-29',
		'he'          => array(
			'heading'         => 'אחריות עריכה ומקורות',
			'owner_label'     => 'אחריות עריכה',
			'owner'           => 'קומפלט 99',
			'review_label'    => 'תאריך בדיקת העמוד',
			'reviewed_at'     => '2026-07-29',
			'review_date'     => '29 ביולי 2026',
			'sources_heading' => 'מקורות והמשך קריאה',
			'sources'         => array(
				array(
					'label' => 'משרד הבריאות: בטיחות והיגיינה במזון',
					'url'   => 'https://www.gov.il/he/pages/food-hygiene?chapterIndex=2',
					'note'  => 'הנחיות רשמיות לקירור, הפרדה ואחסון מזון בבית.',
				),
				array(
					'label' => 'משרד הבריאות: מידע על אלרגיות',
					'url'   => 'https://me.health.gov.il/parenting/raising-children/common-childhood-diseases-and-symptoms/medical-conditions/common-allergies/',
					'note'  => 'מידע רפואי רשמי על תגובות אלרגיות ועל פנייה לבירור מקצועי.',
				),
				array(
					'label' => 'משרד הבריאות: תווית מזון וסימון תזונתי',
					'url'   => 'https://www.gov.il/he/departments/guides/food-labeling?chapterIndex=5',
					'note'  => 'כללים לקריאת רשימת רכיבים וסימון אלרגנים במזון ארוז מראש.',
				),
				array(
					'label' => 'ארגון הבריאות העולמי: חמשת המפתחות למזון בטוח',
					'url'   => 'https://www.who.int/activities/promoting-safe-food-handling/five-key-to-safer-food/',
					'note'  => 'מסגרת כללית וברורה לטיפול בטוח במזון בבית.',
				),
			),
		),
		'en'          => array(
			'heading'         => 'Editorial responsibility and sources',
			'owner_label'     => 'Editorial owner',
			'owner'           => 'Complete99',
			'review_label'    => 'Page review date',
			'reviewed_at'     => '2026-07-29',
			'review_date'     => '29 July 2026',
			'sources_heading' => 'Sources and further reading',
			'sources'         => array(
				array(
					'label' => 'Israel Ministry of Health: food hygiene and handling',
					'url'   => 'https://www.gov.il/he/pages/food-hygiene?chapterIndex=2',
					'note'  => 'Official Hebrew guidance for refrigeration, separation and home storage.',
				),
				array(
					'label' => 'Israel Ministry of Health: allergy information',
					'url'   => 'https://me.health.gov.il/parenting/raising-children/common-childhood-diseases-and-symptoms/medical-conditions/common-allergies/',
					'note'  => 'Official Hebrew medical information on allergic reactions and professional assessment.',
				),
				array(
					'label' => 'Israel Ministry of Health: food and nutrition labelling',
					'url'   => 'https://www.gov.il/en/pages/food-labeling?chapterIndex=4',
					'note'  => 'Guidance for ingredient lists and allergen declarations on prepacked foods.',
				),
				array(
					'label' => 'World Health Organization: Five Keys to Safer Food',
					'url'   => 'https://www.who.int/activities/promoting-safe-food-handling/five-key-to-safer-food/',
					'note'  => 'A clear general framework for safer food handling at home.',
				),
			),
		),
	),
);

return array(
	'home' => array(
		'title'   => array(
			'he' => 'סביח ואוכל ביתי באבן גבירול 99',
			'en' => 'Sabich and home cooking at 99 Ibn Gabirol',
		),
		'excerpt' => array(
			'he' => 'סביח, קובה סלק, קוסקוס, מרקים ומנות מהמטבח הביתי באבן גבירול 99, תל אביב.',
			'en' => 'Sabich, beet kubbeh, couscous, soups and home-style plates at 99 Ibn Gabirol, Tel Aviv.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'באבן גבירול 99 אפשר לבחור בין סביח בפיתה, ארוחה בצלחת ואוכל חם מהסירים. התפריט מחבר חציל, טחינה ועמבה עם קובה סלק, קוסקוס, קציצות, מרקים ומנות צהריים מוכרות.',
				array(
					array(
						'מה בא לכם לאכול היום',
						array(
							'לארוחה בפיתה אפשר להתחיל מהסביח של 99, משניצל, מחזה עוף על הפלנצ׳ה או מעיג׳ה, חביתת ירק. הסביח מוגש גם בצלחת למי שמעדיף לאכול את המרכיבים בנפרד.',
							'לארוחה מהסירים חפשו קובה סלק, קוסקוס, קציצות ביתיות, קציצות דגים או מרק בשר תימני. המנות מהסירים עשויות להשתנות, לכן כדאי לבדוק מה מופיע בתפריט ההזמנות באותו יום.',
							'לארוחה שמתחילה בביצים ועגבניות יש שקשוקה. הסבטוחה מחברת בין ביצת שקשוקה לבין חציל, תפוח אדמה וסלטים ממשפחת הסביח.',
						)
					),
					array(
						'מתחילים מהסביח',
						array(
							'הסביח של 99 מחבר חציל, ביצה, תפוח אדמה, סלט, טחינה, עמבה וחריף. יש בו מרקמים רכים ורעננים, טחינה עשירה והחמיצות המתובלת של העמבה.',
							'אפשר לבחור בפיתה לארוחה שאוכלים ביד, או בצלחת כדי לפגוש כל מרכיב בנפרד. אם חריפות או רוטב מסוים חשובים לכם, שאלו לפני ההזמנה מה כלול בגרסה הנוכחית.',
						)
					),
					array(
						'מגיעים או מזמינים',
						array(
							'קומפלט 99 נמצאת ברחוב שלמה אבן גבירול 99, תל אביב. לבדיקת שעות, זמינות ומחירים אפשר לעבור לתפריט ההזמנות הנוכחי או להתקשר ל-03-523-1810.',
							'לשאלה על רכיבים, אלרגנים או התאמה תזונתית, דברו עם הצוות לפני ההזמנה. שם של מנה ותמונה אינם מספיקים כדי לקבוע התאמה אישית.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'At 99 Ibn Gabirol, lunch can be sabich in a pita, a full plate or something warm from the pots. The menu brings aubergine, tahini and amba together with beet kubbeh, couscous, meatballs, soups and familiar home-style dishes.',
				array(
					array(
						'What do you feel like eating today?',
						array(
							'For a pita lunch, start with the 99 Sabich, schnitzel, griddled chicken breast or aja, a herb omelette. Sabich is also served on a plate when you would rather eat its components separately.',
							'For food from the pots, look for beet kubbeh, couscous, home-style meatballs, fish patties or Yemenite beef soup. Pot dishes can change, so check the ordering menu to see what is offered that day.',
							'For a tomato and egg lunch, choose shakshuka. Sabtucha brings a shakshuka egg together with aubergine, potato and salads from the sabich family.',
						)
					),
					array(
						'Start with the sabich',
						array(
							'The 99 Sabich combines aubergine, egg, potato, salad, tahini, amba and hot sauce. It moves between soft and fresh textures, rich sesame tahini and the sharp, spiced character of amba.',
							'Choose a pita for a meal you can hold, or a plate when you want to meet each component separately. If heat level or a particular sauce matters to you, ask what is included in the current version before ordering.',
						)
					),
					array(
						'Visit or order',
						array(
							'Complete99 is at 99 Shlomo Ibn Gabirol Street, Tel Aviv. Check the current ordering menu for hours, availability and prices, or call 03-523-1810.',
							'For questions about ingredients, allergens or dietary suitability, speak with the team before ordering. A dish name and photograph are not enough to determine personal suitability.',
						)
					),
				)
			),
		),
	),
	'about' => array(
		'title'   => array( 'he' => 'הסיפור של קומפלט 99', 'en' => 'The Complete99 story' ),
		'excerpt' => array(
			'he' => 'מטבח תל אביבי שבו סביח, קובה סלק, קוסקוס, מרקים ומנות ביתיות חיים באותו תפריט.',
			'en' => 'A Tel Aviv kitchen where sabich, beet kubbeh, couscous, soups and home-style dishes share one menu.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'קומפלט 99 היא מטבח תל אביבי באבן גבירול 99. הסביח נמצא במרכז, ולצדו אוכל מהסירים, מנות בפיתה וצלחות שמרגישות כמו ארוחת צהריים מוכרת.',
				array(
					array(
						'הסביח שבמרכז',
						array(
							'הסביח של 99 מוגש בפיתה או בצלחת עם חציל, ביצה, תפוח אדמה, סלט, טחינה, עמבה וחריף. זו מנה שבה כל ביס יכול להיות מעט שונה לפי היחס בין הרוטב, הירקות והמרכיבים החמים.',
							'הסבטוחה ממשיכה את אותה משפחת טעמים ומוסיפה ביצת שקשוקה. היא מתאימה למי שמתלבט בין סביח לבין עגבניות וביצים.',
						)
					),
					array(
						'פיתה פוגשת סיר',
						array(
							'התפריט אינו נעצר בסביח. קובה סלק, קוסקוס, מרק בשר תימני, קציצות ביתיות וקציצות דגים מביאים אל שולחן הצהריים את האופי של אוכל מהסירים.',
							'שניצל, חזה עוף על הפלנצ׳ה ועיג׳ה נותנים בחירה בין פיתה לצלחת. שקשוקה מוסיפה מסלול של עגבניות וביצים, עם אפשרויות שמופיעות בתפריט ההזמנות.',
						)
					),
					array(
						'שולחן תל אביבי אחד',
						array(
							'המנות נושאות שמות וזיכרונות ממטבחים שונים, אבל נפגשות כאן באותה ארוחת צהריים תל אביבית. אפשר לבוא בשביל הקלאסיקה, לנסות סיר של אותו יום או לבחור מנה פשוטה ומוכרת.',
							'הכתובת היא שלמה אבן גבירול 99, תל אביב. לפני הגעה מיוחדת מומלץ לבדוק מה זמין באותו יום או להתקשר ל-03-523-1810.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'Complete99 is a Tel Aviv kitchen at 99 Ibn Gabirol. Sabich sits at the centre, joined by food from the pots, pita dishes and full plates that feel like a familiar lunch.',
				array(
					array(
						'Sabich at the centre',
						array(
							'The 99 Sabich is served in a pita or on a plate with aubergine, egg, potato, salad, tahini, amba and hot sauce. Each bite can feel a little different as the balance shifts between sauce, vegetables and warm components.',
							'Sabtucha stays in the same family of flavours and adds a shakshuka egg. It suits anyone choosing between sabich and a tomato and egg lunch.',
						)
					),
					array(
						'Pita meets the cooking pot',
						array(
							'The menu does not stop at sabich. Beet kubbeh, couscous, Yemenite beef soup, home-style meatballs and fish patties bring the character of pot cooking to the lunch table.',
							'Schnitzel, griddled chicken breast and aja offer a choice between pita and plate. Shakshuka adds a tomato and egg route, with current options shown in the ordering menu.',
						)
					),
					array(
						'One Tel Aviv table',
						array(
							'The dishes carry names and memories from different home kitchens, but meet here in one Tel Aviv lunch. Come for the classic, try the pot dish of the day or choose something simple and familiar.',
							'The address is 99 Shlomo Ibn Gabirol Street, Tel Aviv. Before making a special trip, check what is available that day or call 03-523-1810.',
						)
					),
				)
			),
		),
	),
	'contact' => array(
		'title'   => array( 'he' => 'מגיעים, מתקשרים או מזמינים', 'en' => 'Visit, call or order' ),
		'excerpt' => array(
			'he' => 'אבן גבירול 99, תל אביב. להזמנה ולבדיקת זמינות עוברים לתפריט ההזמנות הנוכחי.',
			'en' => '99 Ibn Gabirol, Tel Aviv. Use the current ordering menu for availability and orders.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'כתובת: שלמה אבן גבירול 99, תל אביב. טלפון: 03-523-1810.',
				array(
					array(
						'לפני שיוצאים',
						array(
							'שעות, מנות ומלאי יכולים להשתנות. מומלץ לבדוק את תפריט ההזמנות או להתקשר לפני הגעה מיוחדת.',
							'לשאלה על רכיבים או אלרגנים יש לדבר עם הצוות לפני ההזמנה. אין להסיק התאמה תזונתית מתמונה או משם המנה.',
							'האתר משאיר את הכתובת, מספר הטלפון וכפתור ההזמנה במקום קבוע כדי שאפשר יהיה להמשיך בלי לחפש אותם מחדש.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'Address: 99 Shlomo Ibn Gabirol, Tel Aviv. Telephone: 03-523-1810.',
				array(
					array(
						'Before a special trip',
						array(
							'Hours, dishes and stock can change. Check the ordering menu or call before making a special journey.',
							'For ingredient or allergen questions, speak with the team before ordering. Do not infer dietary suitability from a photograph or dish name.',
							'The address, telephone number and ordering button stay in predictable places so visitors can continue without searching for them again.',
						)
					),
				)
			),
		),
	),
	'proposal' => array(
		'title'          => array(
			'he' => 'ארוחות לקבוצות ולמקומות עבודה',
			'en' => 'Meals for groups and workplaces',
		),
		'excerpt'        => array(
			'he' => 'ספרו לנו לכמה אנשים, לאיזה תאריך ואיך תרצו לקבל את הארוחה. נחזור אליכם כדי לבדוק תפריט, זמינות ומחיר.',
			'en' => 'Tell us how many people, the requested date and how you would like to receive the meal. We will contact you to check menu, availability and price.',
		),
		'content'        => array(
			'he' => $c99_consumer_page(
				'מתכננים ארוחת צוות, ישיבה, אירוע משפחתי או הזמנה לקבוצה? שלחו בקשה אחת עם מספר הסועדים, התאריך והדרך הנוחה לכם לקבל את האוכל.',
				array(
					array(
						'מתחילים מהפרטים החשובים',
						array(
							'הטופס מבקש את גודל הקבוצה, התאריך הרצוי, חלון הארוחה והעדפה לאיסוף או למשלוח. אפשר גם לציין אריזה משותפת, מנות אישיות או שילוב ביניהן.',
							'אם יש תקציב משוער לאדם, אפשר לציין אותו. לאחר קבלת הבקשה נבדוק מה אפשר להכין לאותו מועד ונחזור עם תשובה ברורה.',
						)
					),
					array(
						'אוכל שמתאים לאופי הארוחה',
						array(
							'אפשר לבקש פיתות, צלחות, מנות מהסירים, מגשים או שילוב של כמה סוגי הגשה. התפריט המדויק נקבע לפי מספר האנשים, שעת הארוחה והזמינות באותו יום.',
							'מחיר, כמויות, תוספות, אריזה ואופן המסירה נקבעים רק לאחר בדיקת הבקשה. שליחת הטופס אינה הזמנה מאושרת ואינה מחייבת תשלום.',
						)
					),
					array(
						'העדפות של הקבוצה בלבד',
						array(
							'אפשר למסור סיכום כללי, למשל מספר מנות צמחוניות או העדפה לאוכל פחות חריף. אין לכתוב שמות של סועדים, אבחנות רפואיות, מידע אישי או מסמכים.',
							'לשאלה על רכיבים, אלרגנים או התאמה אישית יש לדבר איתנו לפני אישור ההזמנה. סיכום קבוצתי אינו מחליף בירור אישי כאשר הוא נדרש.',
						)
					),
					array(
						'מה קורה אחרי השליחה',
						array(
							'הפרטים נשמרים כדי שנוכל לחזור אליכם בקשר לבקשה. נבדוק יחד את התפריט, הכמויות, המחיר, האיסוף או המשלוח והאריזה לפני שמאשרים הזמנה.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'Planning a team lunch, meeting, family occasion or another group meal? Send one request with the number of diners, the date and the most convenient way to receive the food.',
				array(
					array(
						'Start with the useful details',
						array(
							'The form asks for group size, requested date, meal window and a pickup or delivery preference. You can also choose shared packaging, individual meals or a mixture of both.',
							'If you have an estimated budget per person, you may include it. After receiving the request, we will check what can be prepared for that date and contact you with a clear answer.',
						)
					),
					array(
						'Food that fits the occasion',
						array(
							'You may ask about pitas, plates, food from the pots, sharing trays or a combination of serving styles. The exact menu depends on group size, meal time and availability on the requested day.',
							'Price, quantities, extras, packaging and fulfilment are confirmed only after the request is checked. Sending the form does not create a confirmed order or a payment obligation.',
						)
					),
					array(
						'Group preferences only',
						array(
							'You may provide an aggregate summary, such as the number of vegetarian meals or a preference for milder food. Do not include diner names, medical diagnoses, personal information or documents.',
							'Speak with us before confirming the order about ingredients, allergens or individual suitability. An aggregate summary does not replace an individual check when one is needed.',
						)
					),
					array(
						'What happens after you send it',
						array(
							'The details are stored so we can contact you about the request. We will review the menu, quantities, price, pickup or delivery and packaging with you before an order is confirmed.',
						)
					),
				)
			),
		),
		'status'         => 'publish',
		'public_route'   => true,
		'verification'   => 'editorial_review',
		'index_eligible' => true,
	),
	'dishes' => array(
		'title'   => array( 'he' => 'התפריט והמנות של קומפלט 99', 'en' => 'Complete99 menu and dishes' ),
		'excerpt' => array(
			'he' => 'סביח ופיתות, ארוחות בצלחת, מרקים ואוכל מהסירים. למחיר וזמינות בודקים את תפריט ההזמנות.',
			'en' => 'Sabich and pita dishes, full plates, soups and food from the pots. Check the ordering menu for current price and availability.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'בחרו לפי צורת הארוחה שמתאימה לכם: פיתה שאוכלים ביד, צלחת מלאה או מנה חמה מהסיר. התפריט נע בין הסביח של 99 לבין קובה סלק, קוסקוס, קציצות, שקשוקה ומנות צהריים מוכרות.',
				array(
					array(
						'בפיתה או בצלחת',
						array(
							'הסביח של 99 כולל חציל, ביצה, תפוח אדמה, סלט, טחינה, עמבה וחריף. בפיתה הטעמים נפגשים בכל ביס, ובצלחת אפשר לבחור בכל פעם יחס אחר בין המרכיבים.',
							'שניצל, חזה עוף על הפלנצ׳ה ועיג׳ה, חביתת ירק, מופיעים עם סלטים ורטבים בפיתה או כארוחה בצלחת. הבחירה בצלחת מתאימה במיוחד למי שרוצה להפריד בין המנה, הסלטים והתוספות.',
						)
					),
					array(
						'מהסירים',
						array(
							'קובה סלק היא מרק סלק חמוץ ומתוק עם קובה במילוי בשר. זו בחירה למי שמחפש קערה שבה המרק והמילוי הם חלק מאותה מנה.',
							'קוסקוס מגיע עם ירקות ותבשיל משתנה. קציצות ביתיות מוגשות ברוטב עם תוספת חמה, וקציצות דגים מגיעות ברוטב עגבניות. מרק הבשר התימני מקבל מקום משלו בין מנות הסיר.',
							'מנות מהסירים יכולות להשתנות במהלך היום. בדקו את תפריט ההזמנות לפני שבוחרים דווקא מנה מסוימת.',
						)
					),
					array(
						'עגבניות, ביצים ועשבים',
						array(
							'שקשוקה מחברת רוטב עגבניות וביצים ומוגשת בפיתה או בצלחת. עיג׳ה היא חביתת ירק עם סלטים ורטבים, גם היא בפיתה או בצלחת.',
							'סבטוחה היא חיבור מקומי בין סביח לביצת שקשוקה, עם חציל, תפוח אדמה וסלטים. היא שומרת על המבנה המלא של פיתת סביח ומוסיפה את האופי של השקשוקה.',
						)
					),
					array(
						'לפני שבוחרים',
						array(
							'מחיר, תוספות וזמינות מופיעים בתפריט ההזמנות הנוכחי. לרמת חריפות, רכיבים, אלרגנים או צורך תזונתי, שאלו את הצוות לפני ההזמנה.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'Choose the shape of meal that suits you: a pita to eat by hand, a full plate or something warm from the pot. The menu moves from the 99 Sabich to beet kubbeh, couscous, meatballs, shakshuka and familiar lunch dishes.',
				array(
					array(
						'In a pita or on a plate',
						array(
							'The 99 Sabich includes aubergine, egg, potato, salad, tahini, amba and hot sauce. In a pita the flavours meet in every bite, while a plate lets you choose a different balance of components each time.',
							'Schnitzel, griddled chicken breast and aja, a herb omelette, appear with salads and sauces in a pita or as a full plate. A plate is especially useful when you want to keep the main dish, salads and sides separate.',
						)
					),
					array(
						'From the pots',
						array(
							'Beet kubbeh is a sweet and sour beet soup with meat-filled kubbeh. It suits anyone looking for a bowl in which the broth and filling belong to the same dish.',
							'Couscous comes with vegetables and a changing stew. Home-style meatballs are served in sauce with a warm side, while fish patties come in tomato sauce. Yemenite beef soup has its own place among the pot dishes.',
							'Food from the pots can change during the day. Check the ordering menu before setting your heart on one particular dish.',
						)
					),
					array(
						'Tomatoes, eggs and herbs',
						array(
							'Shakshuka brings tomato sauce and eggs together and is served in a pita or on a plate. Aja is a herb omelette with salads and sauces, also offered in pita or plate form.',
							'Sabtucha is a local meeting of sabich and a shakshuka egg, with aubergine, potato and salads. It keeps the full structure of a sabich pita and adds the character of shakshuka.',
						)
					),
					array(
						'Before choosing',
						array(
							'Current price, options and availability appear in the ordering menu. Ask the team before ordering about heat level, ingredients, allergens or a dietary need.',
						)
					),
				)
			),
		),
	),
	'ingredients' => array(
		'title'   => array( 'he' => 'מרכיבים במטבח הישראלי, מחציל ועד עמבה', 'en' => 'Israeli kitchen ingredients, from aubergine to amba' ),
		'excerpt' => array(
			'he' => 'מדריך לחציל, טחינה, עמבה, סלק, ביצים ועשבים, עם שאלות בטיחות חשובות לפני הזמנה.',
			'en' => 'A guide to aubergine, tahini, amba, beetroot, eggs and herbs, with important safety questions before ordering.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'מרכיבים במטבח הישראלי מקבלים משמעות דרך המנה שבה הם נפגשים. מרכיב יכול לתת רכות, חמיצות, צבע, חריפות, רעננות או מרקם. בסביח, בקובה ובמנות הביצים של קומפלט 99, החיבור בין המרכיבים חשוב לא פחות מכל מרכיב בנפרד.',
				array(
					array(
						'מפת הטעמים והמרקמים של הסביח',
						array(
							'חציל, ביצה ותפוח אדמה נותנים לסביח בסיס רך ומלא. הסלט מוסיף מרכיב רענן, הטחינה מוסיפה טעם שומשום, והעמבה מביאה חמיצות מתובלת. חריף מוסיף חום, אבל מידת החריפות והכמות בפועל יכולות להשתנות לפי ההזמנה.',
							'בפיתה, המרכיבים, הרטבים והלחם נפגשים כמעט בכל ביס. בצלחת אפשר להשאיר כל רכיב ברור יותר ולשנות את היחס ביניהם. זו אינה שאלה של גרסה נכונה, אלא של הדרך שבה אתם רוצים לפגוש את אותם טעמים.',
						)
					),
					array(
						'טחינה, עמבה וחריף אינם קישוט',
						array(
							'טחינה היא ממרח שומשום. עמבה היא רוטב מנגו מתובל בעל אופי חד וחמצמץ. אלה שני טעמים שונים מאוד, והיחס ביניהם יכול להפוך את אותו בסיס של חציל, ביצה ותפוח אדמה לעדין יותר, חמצמץ יותר או מודגש יותר.',
							'רוטב יכול להכיל רכיבים שאינם נראים בתצלום ואינם נלמדים משם המנה. אם אתם רוצים פחות חריף, רוטב בצד או מידע על מרכיב מסוים, שאלו לפני ההזמנה מה כלול בגרסה שמוצעת עכשיו.',
						)
					),
					array(
						'סלק, מעטפת ומילוי בקובה',
						array(
							'בקובה סלק, המרק מקבל מהסלק צבע עמוק וטעם שנע בין מתוק לחמוץ. הקובה שבתפריט מתוארת כממולאת בבשר. כדי להבין את המנה צריך לקרוא יחד את המעטפת, המילוי והמרק, ולא להתייחס למילה קובה כאילו היא מתכון אחד קבוע.',
							'קובה היא משפחה רחבה של צורות, מעטפות, מילויים ומרקים. התיאור בעמוד הזה מתייחס למנה של קומפלט 99 בלבד. הוא אינו קובע מה חייב להופיע בכל קובה במטבח אחר או בגרסה משפחתית אחרת.',
						)
					),
					array(
						'עגבניות, ביצים ועשבים מובילים לכיוונים שונים',
						array(
							'בשקשוקה, רוטב העגבניות והביצים הם מרכז המנה. בעיג׳ה, הביצים פוגשות עשבים וירק. בסבטוחה, ביצת שקשוקה מצטרפת לחציל, תפוח אדמה וסלטים ממשפחת הסביח.',
							'ההשוואה הזאת עוזרת לבחור לפי הטעם הרצוי: עגבניות ורוטב, עשבים וירק, או מפגש צפוף יותר עם מרכיבי סביח. היא אינה רשימת רכיבים מלאה, ואינה מחליפה תשובה מהצוות על המנה שמכינים באותו זמן.',
						)
					),
					array(
						'מה שם המנה אינו יכול לספר על אלרגנים',
						array(
							'טחינה עשויה משומשום, וביצה מופיעה בשם או בתיאור של סביח, שקשוקה ועיג׳ה. עדיין אי אפשר להסיק מכך שכל שאר הרכיבים ידועים. שם מנה גם אינו מסביר אם היה במטבח מגע אפשרי עם רכיבים אחרים.',
							'לשאלה על אלרגן, רגישות או התאמה תזונתית, דברו עם הצוות לפני ההזמנה. אל תסתמכו רק על תמונה, על גרסה ביתית מוכרת או על תיאור כללי. אם מדובר באלרגיה מאובחנת, פעלו לפי ההנחיה הרפואית האישית שקיבלתם.',
							'תווית של מוצר ארוז ורשימת רכיבים במסעדה הן שני מצבים שונים. משרד הבריאות מפרסם כללים רשמיים לסימון מזון ארוז מראש. בעמודי המנות שלנו אנו נמנעים מלהציג תיאור קצר כאילו היה תווית מלאה.',
						)
					),
					array(
						'אם לוקחים אוכל הביתה',
						array(
							'הנחיות משרד הבריאות לטיפול במזון בבית ממליצות לשמור מקרר בטמפרטורה של 4 מעלות ולקרר מזון מבושל בתוך פרק זמן קצר, כך שיגיע לטמפרטורת המקרר בתוך שעתיים. ההנחיות גם מדגישות שמראה וריח תקינים אינם הוכחה שמזון שאוחסן עדיין בטוח.',
							'אם קיבלתם הוראת אחסון נקודתית עם המנה, פעלו לפיה. כאשר אין הוראה כזאת ואתם שומרים שאריות, עברו למדריך הרשמי המקושר למטה. מידע כללי בעמוד הזה אינו מחליף הנחיית בטיחות שניתנה למזון מסוים.',
						)
					),
					array(
						'איך אנחנו בונים עמוד מרכיב',
						array(
							'אנחנו מפרידים בין שלושה דברים: מה שמתואר בתפריט הנוכחי של קומפלט 99, ידע כללי ממקור חיצוני, ותיאור חושי שנועד לעזור לבחור. כאשר מקור מספר סיפור תרבותי או משפחתי, הוא מסומן כסיפור ולא כהוכחה לכל גרסה של המנה.',
							'לא נפרסם ערך תזונתי, התאמה רפואית, הבטחה על אלרגן או רשימת רכיבים מלאה בלי מידע מתאים למנה בפועל ובדיקה אחראית. מחיר, תוספות וזמינות נשארים בתפריט ההזמנות הנוכחי.',
						)
					),
				),
				$c99_consumer_editorial['ingredients']['he']
			),
			'en' => $c99_consumer_page(
				'Israeli kitchen ingredients take on meaning through the dish in which they meet. An ingredient can bring softness, acidity, colour, heat, freshness or texture. In Complete99 sabich, kubbeh and egg dishes, the connection between ingredients matters as much as each component on its own.',
				array(
					array(
						'A flavour and texture map of sabich',
						array(
							'Aubergine, egg and potato give sabich a soft, substantial base. Salad adds a fresh component, tahini contributes sesame flavour, and amba brings spiced acidity. Hot sauce adds heat, though the level and amount can change with the order.',
							'In a pita, the components, sauces and bread meet in almost every bite. A plate can keep each component clearer and lets you change the balance as you eat. Neither format is the correct one. They are two ways to encounter the same family of flavours.',
						)
					),
					array(
						'Tahini, amba and hot sauce are not decoration',
						array(
							'Tahini is sesame paste. Amba is a sharp, tangy, spiced mango condiment. They are very different flavours, and their balance can make the same base of aubergine, egg and potato feel milder, more acidic or more pronounced.',
							'A sauce may contain ingredients that cannot be seen in a photograph or learned from a dish name. If you prefer less heat, sauce on the side, or need information about a particular ingredient, ask what is included in the version offered now.',
						)
					),
					array(
						'Beetroot, shell and filling in kubbeh',
						array(
							'In beet kubbeh, beetroot gives the broth a deep colour and a flavour that moves between sweet and sour. The menu describes the kubbeh as meat-filled. Understanding the dish means reading shell, filling and broth together, not treating the word kubbeh as one fixed recipe.',
							'Kubbeh is a broad family of shapes, shells, fillings and broths. The description on this page applies only to the Complete99 dish. It does not define what must appear in kubbeh from another kitchen or family.',
						)
					),
					array(
						'Tomatoes, eggs and herbs lead in different directions',
						array(
							'In shakshuka, tomato sauce and eggs form the centre of the dish. In aja, eggs meet herbs and greens. In sabtucha, a shakshuka egg joins aubergine, potato and salads from the sabich family.',
							'That comparison helps you choose between tomato and sauce, herbs and greens, or a closer meeting with sabich components. It is not a complete ingredient list and does not replace an answer from the team about the dish being prepared now.',
						)
					),
					array(
						'What a dish name cannot tell you about allergens',
						array(
							'Tahini is made from sesame, and egg appears in the name or description of sabich, shakshuka and aja. That still does not mean every other ingredient is known. A dish name also cannot explain possible contact with other ingredients in the kitchen.',
							'For an allergen, sensitivity or dietary question, speak with the team before ordering. Do not rely only on a photograph, a familiar home version or a general description. If you have a diagnosed allergy, follow the personal medical guidance you have received.',
							'A packaged-food label and a restaurant ingredient description are different things. The Israel Ministry of Health publishes official rules for prepacked food labelling. On our dish pages, we do not present a short description as if it were a complete label.',
						)
					),
					array(
						'If you take food home',
						array(
							'Israel Ministry of Health guidance for handling food at home recommends keeping the refrigerator at 4°C and cooling cooked food promptly so that it reaches refrigerator temperature within two hours. It also warns that normal appearance and smell do not prove that stored food is still safe.',
							'Follow any specific storage instruction supplied with your food. When there is no specific instruction and you are saving leftovers, use the official guide linked below. General information on this page does not replace a safety instruction for a particular food.',
						)
					),
					array(
						'How we build an ingredient page',
						array(
							'We separate three things: what the current Complete99 menu describes, general knowledge from an external source, and sensory language that helps with choosing. When a source presents a cultural or family story, we identify it as a story rather than proof for every version of a dish.',
							'We will not publish a nutrition value, medical suitability statement, allergen guarantee or complete ingredient list without information for the actual dish and responsible review. Current price, options and availability remain in the ordering menu.',
						)
					),
				),
				$c99_consumer_editorial['ingredients']['en']
			),
		),
		'editorial_owner' => array( 'he' => 'קומפלט 99', 'en' => 'Complete99' ),
		'reviewed_at'     => $c99_consumer_editorial['ingredients']['reviewed_at'],
	),
	'traditions' => array(
		'title'   => array( 'he' => 'מסורות אוכל יהודיות שעוברות בין בתים', 'en' => 'Jewish food traditions carried between homes' ),
		'excerpt' => array(
			'he' => 'סביח, קובה וקוסקוס דרך מקורות תרבותיים, זיכרונות משפחתיים והצלחת שמוגשת היום.',
			'en' => 'Sabich, kubbeh and couscous through cultural sources, family memory and the plate served today.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'מסורות אוכל יהודיות חיות בתוך מסורות אזוריות רחבות ואינן מתכון יחיד או סיפור מקור קצר שאפשר להדביק לכל צלחת. סביח, קובה, קוסקוס ומרקים מקבלים צורה שונה בין אזורים, קהילות, משפחות ומטבחים. כאן אנחנו מחברים הקשר מתועד, זיכרון משפחתי ותיאור מדויק של המנה שמוגשת היום, בלי לערבב ביניהם.',
				array(
					array(
						'שלוש שכבות לסיפור אוכל אחראי',
						array(
							'ההקשר המתועד מגיע ממוסדות תרבות, ארכיונים ומקורות ערוכים. זיכרון משפחתי מספר איך אדם מסוים למד, אכל או העביר מנה. התיאור של קומפלט 99 אומר מה נמצא בגרסה שלנו ובאיזו צורה היא מוצעת. כל שכבה עונה על שאלה אחרת.',
							'מקור משפחתי חשוב מפני שהוא שומר קול, מנהג ופרטים שלא תמיד נכנסו לספרי היסטוריה. הוא אינו מוכיח שכל בני הקהילה בישלו באותה דרך. גם מקור תרבותי ערוך אינו מחליף בדיקה של המנה הנוכחית, במיוחד כאשר רכיב, אלרגן או התאמה אישית חשובים לכם.',
						)
					),
					array(
						'סביח בין ארוחת שבת למנת רחוב',
						array(
							'סיפור משפחתי שפורסם ב-Jewish Food Society מחבר את מרכיבי הסביח לארוחת בוקר של שבת אצל יהודי עיראק: חציל, ביצים, טחינה, סלט, עמבה ולחם, שהוגשו כך שכל אחד יכול להרכיב לעצמו. המקור מתאר כיצד המנה המשפחתית קיבלה בישראל גם צורה של כריך רחוב.',
							'יש יותר מסיפור אחד על מקור השם ועל נקודת המכירה שהפיצה את הכריך. לכן איננו מציגים אגדה אחת כעובדה סופית. בטוח יותר לומר שהסביח הישראלי נושא קשר חזק למסורת האוכל של יהודי עיראק ושצורתו המשיכה להשתנות בישראל.',
							'הסביח של קומפלט 99 מחבר חציל, ביצה, תפוח אדמה, סלט, טחינה, עמבה וחריף, בפיתה או בצלחת. זהו תיאור של המנה שלנו, לא טענה שכל סביח חייב לכלול בדיוק את אותה רשימה או אותו יחס.',
						)
					),
					array(
						'קובה היא משפחה, לא צורה אחת',
						array(
							'קובה יכולה להיות מטוגנת, אפויה או מבושלת במרק, והעטיפה, המילוי והנוזל משתנים בין גרסאות. המתכון המשפחתי המקושר למטה מתעד קובה במעטפת סולת, מילוי בקר ובצל ומרק סלק. הוא מראה היטב כיצד מתכון אחד נלמד מאם ומסבתא ונכתב כדי לא לאבד את התהליך.',
							'הגרסה בתפריט קומפלט 99 מתוארת כקובה במילוי בשר בתוך מרק סלק חמוץ ומתוק. אנחנו מציגים אותה כגרסה אחת בתוך עולם רחב יותר. העובדה שמקור אחר משתמש באורז, בורגול, תפוח אדמה או מרק אחר אינה סתירה, אלא עדות לכך שהשם מחזיק כמה מסורות.',
						)
					),
					array(
						'קוסקוס כידע משותף וכמנה מקומית',
						array(
							'אונסק"ו רשם את הידע, המיומנויות והמנהגים הקשורים לייצור ולאכילה של קוסקוס בעקבות הגשה משותפת של אלג׳יריה, מאוריטניה, מרוקו ותוניסיה. הדגש הוא לא רק על גרגר או מתכון, אלא גם על דרכי הכנה, כלים, נסיבות אכילה והעברה בין דורות.',
							'FOODISH של מוזיאון העם היהודי מוסיף את ההקשר של קהילות יהודי המגרב ואת קליטת הקוסקוס במטבח הישראלי. הקריאה בשני המקורות יחד מזכירה שמורשת רחבה יכולה להיכנס לבתים יהודיים שונים ולקבל שם מועדים, תוספות וזיכרונות מקומיים.',
							'בקומפלט 99 הקוסקוס מופיע עם ירקות ותבשיל משתנה. זהו מידע שימושי לבחירת ארוחה היום, אך הוא אינו מתיימר לייצג את כל גרסאות הקוסקוס בצפון אפריקה, בקהילות יהודיות או בישראל.',
						)
					),
					array(
						'גם שם מוכר צריך תיאור נוכחי',
						array(
							'עיג׳ה מתוארת כאן כחביתת ירק עם סלטים ורטבים. שקשוקה בנויה סביב רוטב עגבניות וביצים. מרק הבשר התימני מופיע כקערה חמה מן הסירים. אלה תיאורי תפריט, לא הגדרות היסטוריות מלאות של כל מנה ושל כל קהילה.',
							'כאשר שם מוכר מעורר אצלכם ציפייה לטעם או למרכיב מסוים, קראו את תיאור המנה הנוכחית ושאלו. מסורת יכולה ליצור תחושת היכרות, אבל היא אינה תווית רכיבים ואינה הבטחה שגרסה ממטבח אחד זהה לגרסה ממטבח אחר.',
						)
					),
					array(
						'איך לקרוא מחלוקת על מקור',
						array(
							'בסיפורי אוכל יש לעיתים כמה טענות על שם, מקום או אדם ראשון. במקום לבחור את הסיפור המושך ביותר, אנחנו בודקים מי פרסם אותו, האם מדובר במסמך, מחקר, זיכרון אישי או מסורת בעל פה, והאם מקור נוסף תומך בו.',
							'כאשר אין די ראיות, אנחנו משאירים את חוסר הוודאות גלוי. ניסוח כמו "סיפור משפחתי מספר" מדויק יותר מהכרזה שכל הקהילה נהגה כך. ניסוח כמו "הגרסה שלנו" מכבד מסורת בלי לנכס לעצמו סמכות על כל הגרסאות.',
						)
					),
					array(
						'מסורת חיה מפני שהיא ממשיכה להשתנות',
						array(
							'אוכל ביתי עובר בין אנשים דרך ידיים, טעם, זיכרון וחזרה. לפעמים נשמרת טכניקה, לפעמים נשמר שילוב של מרכיבים, ולפעמים דווקא השם ממשיך בזמן שהמנה משתנה. ההבדלים אינם תקלה שצריך למחוק.',
							'הגישה שלנו היא לתת לכל סיפור מקום מדויק: מקור תרבותי ליד טענה תרבותית, עדות משפחתית ליד זיכרון, ותפריט עדכני ליד המנה שאפשר לבחור עכשיו. כך אפשר ליהנות מן הסיפור בלי להפוך אותו לגרסה יחידה ונוקשה.',
						)
					),
				),
				$c99_consumer_editorial['traditions']['he']
			),
			'en' => $c99_consumer_page(
				'Jewish food traditions live within wider regional traditions. They are not one fixed recipe or a short origin story that can be pasted onto every plate. Sabich, kubbeh, couscous and soups take different forms across regions, communities, families and kitchens. Here we connect documented context, family memory and an accurate description of the dish served today without blending them together.',
				array(
					array(
						'Three layers of a responsible food story',
						array(
							'Documented context comes from cultural institutions, curated collections and edited sources. Family memory explains how a particular person learned, ate or passed on a dish. The Complete99 description states what is in our version and how it is offered. Each layer answers a different question.',
							'A family source matters because it preserves voice, custom and details that may never have entered a history book. It does not prove that every member of a community cooked the same way. An edited cultural source also cannot replace checking the current dish when an ingredient, allergen or personal need matters.',
						)
					),
					array(
						'Sabich between Shabbat breakfast and street food',
						array(
							'A family story published by Jewish Food Society connects sabich components with Iraqi Jewish Shabbat breakfast: aubergine, eggs, tahini, salad, amba and bread set out so each person could assemble a plate. The source describes how that family meal also took on the form of Israeli street food.',
							'There is more than one account of the name and of the stall that spread the sandwich. We therefore do not present one popular tale as settled fact. It is more accurate to say that Israeli sabich has a strong connection with Iraqi Jewish food tradition and that its form continued to change in Israel.',
							'The Complete99 sabich combines aubergine, egg, potato, salad, tahini, amba and hot sauce in a pita or on a plate. That describes our dish. It does not claim that every sabich must use exactly the same list or balance.',
						)
					),
					array(
						'Kubbeh is a family, not one shape',
						array(
							'Kubbeh may be fried, baked or cooked in broth, while the shell, filling and liquid change across versions. The family recipe linked below documents a semolina shell, a beef and onion filling and a beet broth. It also shows how one recipe was learned from a mother and grandmother, then written down so the process would not be lost.',
							'The Complete99 menu describes meat-filled kubbeh in a sweet and sour beet soup. We present it as one version within a much larger world. Another source using rice, bulgur, potato or a different broth is not a contradiction. It is evidence that the name holds several traditions.',
						)
					),
					array(
						'Couscous as shared knowledge and a local plate',
						array(
							'UNESCO inscribed the knowledge, skills and practices related to the production and consumption of couscous after a joint submission by Algeria, Mauritania, Morocco and Tunisia. The focus is not just a grain or recipe. It includes preparation methods, tools, eating occasions and transmission between generations.',
							'FOODISH at ANU Museum adds the context of Maghrebi Jewish communities and the adoption of couscous in Israeli cooking. Reading the two sources together shows how a wide regional heritage can enter different Jewish homes and acquire local occasions, accompaniments and memories.',
							'At Complete99, couscous is offered with vegetables and a changing stew. That is useful information for choosing lunch today, but it does not claim to represent every couscous in North Africa, Jewish communities or Israel.',
						)
					),
					array(
						'A familiar name still needs a current description',
						array(
							'Aja is described here as a herb omelette with salads and sauces. Shakshuka is built around tomato sauce and eggs. Yemenite beef soup appears as a warm bowl from the pots. These are menu descriptions, not complete historical definitions of every dish or community.',
							'When a familiar name creates an expectation of a particular flavour or ingredient, read the current description and ask. Tradition can create recognition, but it is not an ingredient label and does not guarantee that one kitchen matches another.',
						)
					),
					array(
						'How to read a disputed origin',
						array(
							'Food stories often contain several claims about a name, place or first maker. Rather than selecting the most attractive tale, we ask who published it, whether it is a document, research, personal memory or oral tradition, and whether another source supports it.',
							'When evidence is limited, we keep the uncertainty visible. "A family story recalls" is more accurate than declaring that an entire community acted one way. "Our version" respects a tradition without claiming authority over every version.',
						)
					),
					array(
						'Tradition lives because it continues to change',
						array(
							'Home cooking moves between people through hands, taste, memory and repetition. Sometimes a method remains, sometimes a combination of ingredients, and sometimes the name continues while the dish changes. Those differences are not defects to erase.',
							'Our approach gives each story a precise place: a cultural source beside a cultural claim, family testimony beside a memory, and the current menu beside the food you can choose now. That makes room for pleasure and context without forcing one rigid version.',
						)
					),
				),
				$c99_consumer_editorial['traditions']['en']
			),
		),
		'editorial_owner' => array( 'he' => 'קומפלט 99', 'en' => 'Complete99' ),
		'reviewed_at'     => $c99_consumer_editorial['traditions']['reviewed_at'],
	),
	'knowledge' => array(
		'title'   => array( 'he' => 'מדריכים לאוכל ביתי, מסביח עד ארוחה מהסירים', 'en' => 'Home cooking guides, from sabich to food from the pots' ),
		'excerpt' => array(
			'he' => 'מדריך מעשי לפיתה או צלחת, טעמים, מנות מהסירים, אלרגנים ושמירת אוכל בבית.',
			'en' => 'A practical guide to pita or plate, flavours, food from the pots, allergens and storing food at home.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'אם אתם יודעים שמתחשק לכם אוכל ביתי אבל עוד לא בחרתם מנה, אל תתחילו מרשימה ארוכה. התחילו בשלוש החלטות פשוטות: איך אתם רוצים לאכול, לאיזה כיוון טעם אתם נמשכים, ואיזה מידע חשוב לכם לקבל לפני ההזמנה.',
				array(
					array(
						'מסלול בחירה של חצי דקה',
						array(
							'ראשית בוחרים צורה: פיתה שאוכלים ביד, צלחת שבה המרכיבים נשארים ברורים יותר, או קערה חמה מהסירים. אחר כך בוחרים כיוון: חציל, טחינה ועמבה; עגבניות וביצים; עשבים וירק; או מרק ותבשיל.',
							'לבסוף בודקים את הפרטים המשתנים. מחיר, תוספות וזמינות שייכים לתפריט ההזמנות הנוכחי. רכיב, רמת חריפות, אלרגן או צורך תזונתי מצריכים שאלה ישירה לצוות לפני ההזמנה.',
						)
					),
					array(
						'מה באמת משתנה בין פיתה לצלחת',
						array(
							'פיתה מחברת את כל המרכיבים לביס אחד ומתאימה לארוחה שאוכלים ביד. צלחת מאפשרת לשמור את המנה, הסלטים, הרטבים והתוספות בנפרד ולשנות את היחס ביניהם בכל ביס.',
							'סביח, שניצל, חזה עוף על הפלנצ׳ה ועיג׳ה מופיעים בפיתה או בצלחת לפי התיאור הנוכחי. אם אתם מתלבטים, שאלו את עצמכם אם אתם רוצים שילוב צפוף של טעמים או שליטה גדולה יותר ברוטב, בסלט ובכל מרכיב.',
							'צלחת אינה מבטיחה מנה גדולה יותר, ופיתה אינה אומרת שכל תוספת כלולה. אלה פרטים שצריך לבדוק בתפריט ההזמנות. הצורה מסבירה את חוויית האכילה, לא את המחיר או את הכמות.',
						)
					),
					array(
						'איך לבחור לפי כיוון הטעם',
						array(
							'חציל, ביצה ותפוח אדמה נותנים את הבסיס הרך והמלא. סלט מוסיף רעננות, טחינה מוסיפה טעם שומשום עשיר, עמבה מביאה חמיצות מתובלת וחריף מוסיף את רמת החום.',
							'אם אתם מחפשים עגבניות וביצה, התחילו משקשוקה. אם אתם מעדיפים ביצה עם עשבים וירק, עיג׳ה הולכת לכיוון אחר. סבטוחה מחברת ביצת שקשוקה עם חציל, תפוח אדמה וסלטים ממשפחת הסביח.',
							'לכיוון של מרק חמוץ ומתוק יש קובה סלק. לקערה עם ירקות ותבשיל משתנה יש קוסקוס. מנות מהסירים עשויות להשתנות במהלך היום, לכן בדקו מה באמת מוצע לפני שמחליטים.',
						)
					),
					array(
						'מילון קצר לתפריט',
						array(
							'עמבה היא רוטב מנגו מתובל, חד וחמצמץ. טחינה היא ממרח שומשום. עיג׳ה היא חביתת ירק שמגיעה עם סלטים ורטבים. קובה סלק היא קובה במילוי בשר בתוך מרק סלק חמוץ ומתוק.',
							'סבטוחה היא מנה מקומית שמחברת בין משפחת הסביח לבין ביצת שקשוקה. אוכל מהסירים הוא שם שימושי למנות חמות כמו קובה, קוסקוס, קציצות ומרק, ולא הבטחה שכל המנות האלה זמינות בכל שעה.',
							'המילון עוזר להתמצא, אבל הוא אינו רשימת רכיבים מלאה. כאשר מילה בתפריט אינה ברורה, בקשו הסבר למנה שמכינים עכשיו במקום להניח שהגרסה מוכרת לכם ממקום אחר.',
						)
					),
					array(
						'איזה מידע שייך לתפריט ההזמנות',
						array(
							'העמודים באתר נועדו להסביר מנות, טעמים והקשרים. התפריט החיצוני הפעיל הוא המקום לבדוק מחיר נוכחי, אפשרויות בחירה וזמינות. אם יש הבדל בין סיפור אוכל לבין אפשרות ההזמנה, התפריט הנוכחי הוא הקובע לגבי מה שאפשר להזמין עכשיו.',
							'קראו את תיאור המנה העדכני ובדקו את אפשרויות הבחירה, ההגשה והתוספות לפני התשלום.',
						)
					),
					array(
						'שאלות שכדאי לשאול לפני ההזמנה',
						array(
							'אם רמת החריפות חשובה, שאלו על החריף ועל העמבה. אם אתם מעדיפים פיתה או צלחת, בדקו באיזו צורה המנה מוצעת. אם רוטב או תוספת מסוימים חשובים, בדקו מה כלול ומה אפשר לקבל בצד.',
							'לשאלה על אלרגנים, רכיבים או התאמה תזונתית, דברו עם הצוות לפני ההזמנה. מתכון מוכר מהבית אינו מבטיח שהמנה במטבח אחר מכילה בדיוק את אותם רכיבים, ושם המנה אינו מסביר מגע אפשרי עם רכיבים אחרים במטבח.',
							'אם יש לכם אלרגיה מאובחנת, פעלו לפי ההנחיה הרפואית האישית שקיבלתם. העמוד הזה אינו כלי לאבחון ואינו מבטיח התאמה אישית.',
						)
					),
					array(
						'שומרים אוכל בבית בצורה אחראית',
						array(
							'משרד הבריאות ממליץ לשמור מקרר ביתי בטמפרטורה של 4 מעלות ולקרר מזון מבושל כך שיגיע לטמפרטורת המקרר בתוך שעתיים. הוא גם מציין שמזון עלול להיראות ולהריח תקין גם כאשר התפתחו בו חיידקים מחוללי מחלה.',
							'אם אתם לוקחים אוכל הביתה, אל תשאירו אותו זמן ממושך ברכב או על השיש. פעלו לפי הוראת אחסון נקודתית שקיבלתם, הפרידו מזון מוכן ממזון לא מבושל במקרר, והשתמשו במדריך הרשמי המקושר למטה כאשר אתם שומרים שאריות.',
							'העקרונות של ארגון הבריאות העולמי מוסיפים מסגרת פשוטה: ניקיון, הפרדה בין נא למבושל, בישול מלא, טמפרטורות בטוחות ומים וחומרי גלם בטוחים. אלה עקרונות כלליים, לא הוראה למנה מסוימת.',
						)
					),
					array(
						'איך להשתמש במקורות שבעמוד',
						array(
							'מקור ממשלתי או רפואי משמש כאן לשאלת בטיחות, אלרגיה או סימון. מקור תרבותי משמש לסיפור של מנה או קהילה. תיאור התפריט משמש כדי להבין מה קומפלט 99 מציעה. מקור אחד אינו ממלא את התפקיד של האחר.',
							'תאריך הבדיקה והאחריות העריכתית מופיעים בסוף העמוד. אם מקור רשמי מעדכן הנחיה, הוא גובר על הסיכום שלנו. אם פרט בתפריט משתנה, בודקים אותו במסלול ההזמנות לפני הבחירה.',
						)
					),
				),
				$c99_consumer_editorial['knowledge']['he']
			),
			'en' => $c99_consumer_page(
				'If you know you want home-style food but have not chosen a dish, do not begin with a long list. Start with three simple decisions: how you want to eat, which flavour direction appeals to you, and what information you need before ordering.',
				array(
					array(
						'A thirty-second route to a decision',
						array(
							'First choose a format: a pita eaten by hand, a plate that keeps components clearer, or a warm bowl from the pots. Then choose a direction: aubergine, tahini and amba; tomato and egg; herbs and greens; or soup and stew.',
							'Finally, check the details that change. Current price, options and availability belong in the ordering menu. An ingredient, heat level, allergen or dietary need calls for a direct question to the team before ordering.',
						)
					),
					array(
						'What really changes between pita and plate',
						array(
							'A pita brings every component into one bite and suits a meal eaten by hand. A plate keeps the main dish, salads, sauces and sides separate, letting you change their balance from one bite to the next.',
							'Sabich, schnitzel, griddled chicken breast and aja are described in pita or plate form. If you are undecided, consider whether you want a close mixture of flavours or more control over sauce, salad and each component.',
							'A plate does not promise a larger serving, and a pita does not mean every side is included. Check those details in the ordering menu. The format describes the eating experience, not the price or quantity.',
						)
					),
					array(
						'How to choose by flavour direction',
						array(
							'Aubergine, egg and potato provide the soft, substantial base. Salad adds freshness, tahini brings rich sesame flavour, amba adds spiced acidity and hot sauce sets the heat level.',
							'If you want tomato and egg, begin with shakshuka. If you prefer egg with herbs and greens, aja heads in another direction. Sabtucha brings a shakshuka egg together with aubergine, potato and salads from the sabich family.',
							'For a sweet and sour soup direction, look at beet kubbeh. For a bowl with vegetables and a changing stew, look at couscous. Food from the pots can change during the day, so check what is actually offered before deciding.',
						)
					),
					array(
						'A short menu glossary',
						array(
							'Amba is a sharp, tangy, spiced mango condiment. Tahini is sesame paste. Aja is a herb omelette served with salads and sauces. Beet kubbeh is meat-filled kubbeh in a sweet and sour beet soup.',
							'Sabtucha is a local dish connecting the sabich family with a shakshuka egg. Food from the pots is a useful name for hot dishes such as kubbeh, couscous, meatballs and soup, not a promise that all of them are available at every hour.',
							'The glossary helps with orientation, but it is not a complete ingredient list. When a menu word is unclear, ask about the dish being prepared now rather than assuming it matches a version from elsewhere.',
						)
					),
					array(
						'What belongs in the ordering menu',
						array(
							'Site pages explain dishes, flavours and context. The active external menu is the place to check current price, choices and availability. If a food story and an ordering option differ, the current menu determines what can be ordered now.',
							'Read the current dish description and review the available presentation, accompaniments and choices before payment.',
						)
					),
					array(
						'Questions worth asking before ordering',
						array(
							'If heat matters, ask about the hot sauce and amba. If you prefer pita or plate, check which form is offered. If a particular sauce or side matters, confirm what is included and what can be served separately.',
							'For allergens, ingredients or dietary suitability, speak with the team before ordering. A familiar home recipe does not mean a dish from another kitchen contains exactly the same ingredients, and a dish name cannot explain possible contact with other ingredients in the kitchen.',
							'If you have a diagnosed allergy, follow the personal medical guidance you have received. This page is not a diagnostic tool and does not guarantee personal suitability.',
						)
					),
					array(
						'Store food responsibly at home',
						array(
							'Israel Ministry of Health guidance recommends keeping a home refrigerator at 4°C and cooling cooked food so that it reaches refrigerator temperature within two hours. It also explains that food can look and smell normal even when disease-causing bacteria have grown.',
							'If you take food home, do not leave it for an extended time in a car or on a counter. Follow any specific storage instruction you receive, keep ready-to-eat food separate from uncooked food in the refrigerator, and use the official guide linked below when saving leftovers.',
							'World Health Organization principles add a simple framework: keep clean, separate raw and cooked food, cook thoroughly, keep food at safe temperatures, and use safe water and raw materials. These are general principles, not instructions for a particular dish.',
						)
					),
					array(
						'How to use the sources on this page',
						array(
							'A government or medical source supports a safety, allergy or labelling question. A cultural source supports a dish or community story. The menu description explains what Complete99 offers. One source cannot perform the job of another.',
							'The review date and editorial owner appear at the end of the page. If an official source updates its guidance, the official source takes priority over our summary. If a menu detail changes, check it in the ordering route before choosing.',
						)
					),
				),
				$c99_consumer_editorial['knowledge']['en']
			),
		),
		'editorial_owner' => array( 'he' => 'קומפלט 99', 'en' => 'Complete99' ),
		'reviewed_at'     => $c99_consumer_editorial['knowledge']['reviewed_at'],
	),
	'store' => array(
		'title'   => array( 'he' => 'המזווה של קומפלט 99', 'en' => 'The Complete99 pantry' ),
		'excerpt' => array(
			'he' => '30 מוצרי מזווה וחומרי גלם עם תמונה, מחיר, כמות נטו, רכיבים, אלרגנים, מלאי והוספה לסל.',
			'en' => 'Shop 30 pantry goods and ingredients with an image, price, net quantity, ingredients, allergens, stock and add-to-cart.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'המזווה מחבר בין חומרי הגלם שעל המדף לבין המנות של קומפלט 99. אפשר לעבור ממוצר למנה קשורה, לבדוק את פרטי המוצר ולהוסיף אותו לסל.',
				array(
					array(
						'מה מוצג לכל מוצר',
						array(
							'שם בעברית ובאנגלית, תמונה, גודל אריזה, מחיר בשקלים, רכיבים, אלרגנים, אחסון ומצב מלאי.',
							'קישורים למנות שבהן משתמשים בחומר הגלם ולמדריך המרכיבים, כדי שאפשר יהיה להמשיך מהמדף אל האוכל.',
						)
					),
					array(
						'איך הסל עובד',
						array(
							'אפשר להוסיף מוצרים, לשנות כמויות ולהסיר פריטים. המחיר והמלאי נבדקים שוב לפני אישור ההזמנה.',
							'סליקה אלקטרונית תיפתח לאחר חיבור ספק הסליקה. עד אז ממשיכים מהסל לשיחה עם קומפלט 99 לבדיקת זמינות ולאישור ההזמנה.',
						)
					),
					array(
						'מנות מוכנות',
						array(
							'סביח, קובה, מרקים ומנות מוכנות מזמינים במסלול ההזמנות של Wolt. המזווה באתר מיועד למוצרים ולחומרי גלם לקחת הביתה.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'The pantry connects shelf ingredients with Complete99 dishes. You can move from a product to related dishes, review its details and add it to the cart.',
				array(
					array(
						'What every product shows',
						array(
							'Hebrew and English names, image, pack size, price in shekels, ingredients, allergens, storage and current stock.',
							'Links to dishes that use the ingredient and to the ingredient guide, so the journey can continue from the shelf to the food.',
						)
					),
					array(
						'How the cart works',
						array(
							'Add products, change quantities and remove items. Price and stock are checked again before the order is confirmed.',
							'Electronic payment will open after the payment provider is connected. Until then, continue from the cart to a Complete99 call for availability and order confirmation.',
						)
					),
					array(
						'Prepared dishes',
						array(
							'Sabich, kubbeh, soups and other prepared dishes are ordered through the Wolt ordering route. The on-site pantry is for goods and ingredients to take home.',
						)
					),
				)
			),
		),
		'verification'   => 'configuration_required',
		'index_eligible' => false,
	),
	'privacy' => array(
		'title'   => array( 'he' => 'מדיניות פרטיות', 'en' => 'Privacy policy' ),
		'excerpt' => array(
			'he' => 'מה עשוי להיאסף בזמן ביקור באתר הציבורי של קומפלט 99 וכיצד אפשר לפנות בשאלה. ',
			'en' => 'What may be collected when visiting the Complete99 public website and how to ask a question.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'המדיניות הזאת מתייחסת לאתר הציבורי של קומפלט 99, לרבות קטלוג המזווה, סל הקניות וטופסי יצירת הקשר. שירות Wolt הוא שירות חיצוני עם מדיניות נפרדת.',
				array(
					array(
						'מידע שנוצר בזמן גלישה',
						array(
							'ספקי האחסון והאבטחה עשויים לעבד כתובת רשת, זמן ביקור, העמוד שנפתח, סוג דפדפן ואירועי אבטחה. המידע משמש להפעלת האתר, לאבחון תקלה ולהגנה מפני שימוש לרעה.',
							'סל המזווה עשוי לשמור מזהי מוצר, כמויות, שפה ומידע טכני הדרוש לשמירת הסל בין עמודים. בשלב חיבור ספק הסליקה לא נאספים בסל פרטי כרטיס תשלום.',
							'אין לשלוח דרך ערוץ ציבורי מידע רפואי, צילום תעודה, סיסמה או פרטים שאינם נחוצים לשאלה או להזמנה.',
						)
					),
					array(
						'יצירת קשר',
						array(
							'כאשר מתקשרים או מוסרים פרטים כדי לקבל מענה, משתמשים בהם לצורך השיחה והמשך הטיפול שהתבקש. משך השמירה תלוי בצורך המעשי ובחובות החלות על העסק.',
							'אפשר לבקש עיון, תיקון או מחיקה של מידע שמסרתם. ייתכן שנצטרך לוודא את זהות הפונה ולשמור מידע שנדרש לפי דין או לצורך הגנה על זכויות.',
						)
					),
					array(
						'סל המזווה ושירות Wolt',
						array(
							'מוצרי המזווה מתווספים לסל באתר. עד לחיבור ספק הסליקה, אישור ההזמנה נעשה בשיחה עם קומפלט 99 והסל משמש להכנת רשימת המוצרים והכמויות.',
							'מנות מוכנות מזמינים דרך Wolt. לאחר מעבר ל-Wolt, החשבון, התשלום, המשלוח והפרטיות כפופים לתנאים ולמדיניות של Wolt. קישור חיצוני אינו מעביר אלינו אוטומטית את פרטי החשבון או התשלום שלכם.',
						)
					),
					array(
						'אבטחה ושינויים',
						array(
							'נעשה שימוש באמצעי תשתית ואבטחה סבירים, אך אין מערכת מקוונת חסינה לחלוטין. במקרה של שינוי מהותי באתר או בדרך השימוש במידע, נוסח המדיניות יעודכן.',
						)
					),
					array(
						'שאלות על פרטיות',
						array(
							'אפשר לפנות דרך עמוד יצירת הקשר או להתקשר למספר שמופיע באתר. כדאי לציין שמדובר בשאלת פרטיות כדי שהפנייה תגיע לטיפול המתאים.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'This policy covers the Complete99 public website, including the pantry catalog, shopping cart and contact forms. Wolt is an external service with its own policy.',
				array(
					array(
						'Information created during a visit',
						array(
							'Hosting and security providers may process a network address, visit time, requested page, browser type and security events. This information is used to operate the site, diagnose faults and protect it from misuse.',
							'The pantry cart may store product identifiers, quantities, language and technical data needed to keep the cart between pages. Card payment details are not collected in the cart while the payment provider is being connected.',
							'Do not send medical information, an identity document, a password or information that is not needed for your question or order through a public channel.',
						)
					),
					array(
						'Contacting Complete99',
						array(
							'When you call or provide details so we can respond, those details are used for the conversation and the follow-up you requested. Retention depends on the practical need and obligations that apply to the business.',
							'You may ask to access, correct or delete information you supplied. We may need to verify the requester and retain information required by law or needed to protect legal rights.',
						)
					),
					array(
						'The pantry cart and Wolt',
						array(
							'Pantry goods are added to the cart on this site. Until the payment provider is connected, order confirmation takes place in a call with Complete99 and the cart prepares the product and quantity list.',
							'Prepared dishes are ordered through Wolt. After you continue to Wolt, account data, payment, delivery and privacy are governed by Wolt terms and policy. Opening that external link does not automatically give Complete99 your account or payment details.',
						)
					),
					array(
						'Security and changes',
						array(
							'Reasonable hosting and security controls are used, but no online system is completely immune from risk. The policy will be revised when a material change affects the site or its use of information.',
						)
					),
					array(
						'Privacy questions',
						array(
							'Use the contact page or call the telephone number shown on the site. Say that the request concerns privacy so it can be directed appropriately.',
						)
					),
				)
			),
		),
	),
	'terms' => array(
		'title'   => array( 'he' => 'תנאי שימוש', 'en' => 'Terms of use' ),
		'excerpt' => array(
			'he' => 'הכללים לשימוש באתר הציבורי, בתוכן הקולינרי ובקישורים להזמנה.',
			'en' => 'Rules for using the public website, culinary content and ordering links.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'האתר נועד להציג את האוכל של קומפלט 99, לספר על מנות ומרכיבים ולעזור להמשיך לערוץ הזמנה פעיל. הגלישה באתר כפופה לתנאים אלה.',
				array(
					array(
						'מידע על מנות וזמינות',
						array(
							'תיאורי המנות והתמונות מספקים היכרות כללית. המחיר, האפשרויות והזמינות בזמן ההזמנה נקבעים בתפריט ההזמנות הפעיל ויכולים להשתנות במהלך היום.',
							'אין להסתמך על תמונה או שם מנה כדי לקבוע התאמה תזונתית. לשאלה על רכיבים, אלרגנים או צורך תזונתי יש לדבר עם הצוות לפני ההזמנה.',
						)
					),
					array(
						'שני מסלולי הזמנה',
						array(
							'מוצרי מזווה וחומרי גלם נבחרים באתר ונוספים לסל מקומי. המחיר, המלאי והאיסוף העצמי נבדקים שוב לפני אישור ההזמנה.',
							'מנות מוכנות מזמינים דרך Wolt. החשבון, התשלום, המשלוח, הביטול וההחזר במסלול זה כפופים למידע ולתנאים שמוצגים ב-Wolt בזמן הפעולה.',
						)
					),
					array(
						'תוכן וזכויות',
						array(
							'מותר לקרוא, לשמור קישור ולשתף את כתובת העמוד. אין להעתיק תמונות, מאמרים, סימנים או חלק מהותי מן האתר לצורך מסחרי בלי רשות מתאימה.',
							'סיפורי אוכל ומסורות יכולים לכלול מקורות, זיכרונות וגרסאות שונות. הם אינם מוצגים כייעוץ רפואי, תזונתי, משפטי או דתי.',
						)
					),
					array(
						'חנות המזווה',
						array(
							'המזווה מציג 32 מוצרי קולינריה עם תמונה, מחיר, מפרט שמתאים לסוג המוצר ומלאי. אפשר להוסיף מוצרים לסל, לשנות כמויות ולהסיר פריטים.',
							'לאחר הכנת הסל, האישור הסופי נעשה בשיחה עם קומפלט 99 לאחר בדיקת המלאי ותנאי הקבלה.',
						)
					),
					array(
						'שימוש הוגן באתר',
						array(
							'אין לנסות לעקוף הגנות, להכביד על האתר, לאסוף מידע באופן אוטומטי שמפריע לפעילותו או להציג תוכן של קומפלט 99 כאילו נוצר בידי גורם אחר. אפשר לעדכן את האתר ואת התנאים כאשר הפעילות או הדין משתנים.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'This site presents Complete99 food, tells the stories of dishes and ingredients, and helps visitors continue to an active ordering route. Use of the site is subject to these terms.',
				array(
					array(
						'Dish information and availability',
						array(
							'Dish descriptions and photographs provide a general introduction. Price, options and availability at the time of an order are set by the active ordering menu and can change during the day.',
							'Do not rely on a photograph or dish name to decide dietary suitability. Speak with the team before ordering about ingredients, allergens or a dietary need.',
						)
					),
					array(
						'Two ordering routes',
						array(
							'Culinary pantry goods and preparation equipment are selected on this site and added to its cart. Price, stock and pickup are checked again before the order is confirmed.',
							'Prepared dishes are ordered through Wolt. Account data, payment, delivery, cancellation and refund on that route are governed by the information and terms shown by Wolt during the transaction.',
						)
					),
					array(
						'Content and rights',
						array(
							'You may read the site, save a link and share a page address. Do not reproduce photographs, articles, marks or a substantial part of the site for commercial use without appropriate permission.',
							'Food stories and traditions may include published sources, memories and different versions. They are not medical, nutrition, legal or religious advice.',
						)
					),
					array(
						'The pantry shop',
						array(
							'The pantry presents 32 culinary products with an image, price, type-specific details and stock. Products can be added to the cart, quantities changed and items removed.',
							'After the cart is prepared, final confirmation takes place in a call with Complete99 after stock and fulfilment are checked.',
						)
					),
					array(
						'Fair use of the site',
						array(
							'Do not bypass protections, overload the site, collect information automatically in a way that disrupts it, or present Complete99 content as another party’s work. The site and these terms may be updated when the activity or applicable rules change.',
						)
					),
				)
			),
		),
	),
	'accessibility' => array(
		'title'   => array( 'he' => 'נגישות', 'en' => 'Accessibility' ),
		'excerpt' => array(
			'he' => 'איך האתר בנוי לשימוש נוח וכיצד אפשר לדווח על קושי.',
			'en' => 'How the site is designed for practical use and how to report a barrier.',
		),
		'content' => array(
			'he' => $c99_consumer_page(
				'המטרה היא לאפשר לאנשים למצוא מנה, לקרוא מידע ולהגיע לערוץ ההזמנה בעזרת מקלדת, קורא מסך, הגדלת תצוגה או מסך מגע.',
				array(
					array(
						'מה בנוי באתר',
						array(
							'העמודים משתמשים בכותרות מסודרות, קישורים בעלי שם ברור, סימון מיקוד נראה לעין, אזורי לחיצה נוחים וכיוון כתיבה מתאים לעברית ולאנגלית.',
							'לתמונות תוכן יש טקסט חלופי. תנועה מצטמצמת כאשר הגדרת המכשיר מבקשת זאת, והניווט בנייד נשאר זמין דרך כפתור מסומן.',
						)
					),
					array(
						'שימוש במקלדת ובהגדלה',
						array(
							'אפשר לעבור בין קישורים וכפתורים באמצעות מקש Tab ולהפעיל אותם באמצעות המקלדת. קישור דילוג מוביל לתוכן הראשי.',
							'הפריסה נועדה להישאר קריאה גם במסכים צרים ובהגדלה. אם רכיב מסתיר תוכן או אינו ניתן להפעלה, חשוב לדווח עליו עם שם העמוד והמכשיר.',
						)
					),
					array(
						'שירות ההזמנה החיצוני',
						array(
							'כפתור ההזמנה פותח אתר שאינו מופעל בתוך האתר הזה. רמת הנגישות, החשבון ותהליך התשלום שם נמצאים באחריות מפעיל השירות החיצוני.',
							'אם המסלול החיצוני אינו נגיש עבורכם, אפשר להתקשר למספר שמופיע באתר ולברר מהו ערוץ ההזמנה הזמין.',
						)
					),
					array(
						'דיווח על קושי',
						array(
							'אפשר להשתמש בעמוד יצירת הקשר או להתקשר ל-03-523-1810. כדאי לציין את כתובת העמוד, הפעולה שניסיתם לבצע, הדפדפן או המכשיר וההתאמה שבה השתמשתם.',
						)
					),
					array(
						'בדיקה מתמשכת',
						array(
							'האתר נבדק במסגרת תהליך הפרסום והתחזוקה, אך אין כאן טענה לביקורת חיצונית מלאה. משוב מעשי עוזר לאתר מחסומים שלא התגלו בבדיקה רגילה.',
						)
					),
				)
			),
			'en' => $c99_consumer_page(
				'The aim is to let people find a dish, read information and reach the ordering route with a keyboard, screen reader, browser zoom or touch screen.',
				array(
					array(
						'What the site provides',
						array(
							'Pages use ordered headings, clearly named links, visible keyboard focus, practical target sizes and the correct reading direction for Hebrew and English.',
							'Content images have alternative text. Motion is reduced when the device requests it, and mobile navigation remains available through a labelled control.',
						)
					),
					array(
						'Keyboard and zoom',
						array(
							'Links and buttons can be reached with the Tab key and activated from the keyboard. A skip link leads directly to the main content.',
							'The layout is designed to remain readable on narrow screens and under zoom. If a control hides content or cannot be operated, report it with the page name and device.',
						)
					),
					array(
						'External ordering service',
						array(
							'The ordering button opens a website that is not operated inside this site. Accessibility, account access and payment on that service are controlled by its operator.',
							'If the external route is not accessible to you, call the telephone number shown on this site and ask which ordering route is available.',
						)
					),
					array(
						'Report a barrier',
						array(
							'Use the contact page or call 03-523-1810. Include the page address, the action you tried, the browser or device and the assistive setting or technology you used.',
						)
					),
					array(
						'Ongoing review',
						array(
							'The site is checked during publication and maintenance, but this is not a claim of a complete external audit. Practical feedback helps uncover barriers that routine checks may miss.',
						)
					),
				)
			),
		),
	),
);
