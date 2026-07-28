<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_page_content = static function ( $language, $intro, $bullets, $decision, $editorial_gate ) {
	$what_title     = 'he' === $language ? 'מה מקבלים' : 'What the engagement covers';
	$decision_title = 'he' === $language ? 'איך מתקדמים נכון' : 'A responsible next step';
	$list           = '';
	foreach ( $bullets as $bullet ) {
		$list .= '<li>' . $bullet . '</li>';
	}
	return '<div class="c99-prose">'
		. '<p class="c99-lead-copy">' . $intro . '</p>'
		. '<h2>' . $what_title . '</h2><ul class="c99-check-list">' . $list . '</ul>'
		. '<h2>' . $decision_title . '</h2><p>' . $decision . '</p>'
		. '</div>';
};

$records = array();
$records[] = array(
	'key'          => 'home',
	'type'         => 'page',
	'slug'         => array( 'he' => 'complete99-home', 'en' => 'en' ),
	'title'        => array( 'he' => 'קומפלט 99 — אוכל, תפעול וצמיחה במקום אחד', 'en' => 'Complete99 — food, operations and growth in one system' ),
	'excerpt'      => array(
		'he' => 'תשתית אחת לשירותי הסעדה מוסדיים, ידע קולינרי ומרכז שליטה רב־סניפי.',
		'en' => 'One foundation for institutional foodservice, culinary knowledge and multi-location operations.',
	),
	'content'      => array(
		'he' => $c99_page_content(
			'he',
			'קומפלט 99 מחברת את האנשים שמבשלים, מנהלים, רוכשים, משווקים ומבקרים איכות. האתר הציבורי מסביר את השירותים ואת דרך העבודה; מערכת ההפעלה מרכזת פתיחת יום, מתכונים, מלאי, משימות וקמפיינים.',
			array(
				'מסלול מסודר לבדיקת התאמה עבור חברות, אתרים תפעוליים ומוסדות.',
				'ספריית מנות ומרכיבים שמחברת בין סיפור קולינרי, מתכון ושיטת הכנה.',
				'מרכז שליטה שמרכז סניפים, תפקידים וספקים בתמונת עבודה אחת.',
				'שפה שיווקית ועיצובית אחת, עם עברית ואנגלית מאותו מודל תוכן.',
			),
			'מתחילים באפיון האתר, מספר הסועדים, שעות השירות, מגבלות תזונה, תנאי המטבח ואחריות הצדדים. התוצאה היא תמונת עבודה משותפת והצעה שמחלקת את האחריות לשלבים ברורים.',
			'',
		),
		'en' => $c99_page_content(
			'en',
			'Complete99 connects the people who cook, manage, procure, market and review quality. The public site explains services and working methods; the operating system coordinates opening, recipes, inventory, tasks and campaigns.',
			array(
				'A structured fit review for companies, operational sites and institutions.',
				'A dish and ingredient library connecting culinary story, recipe and preparation method.',
				'A command centre bringing locations, roles and suppliers into one operating picture.',
				'One brand and content model in Hebrew and English.',
			),
			'The first step documents the site, expected diners, service hours, dietary constraints, kitchen conditions and division of responsibility. This produces a shared operating picture and a proposal with clear stages.',
			'',
		),
	),
	'image'        => 'c99-food-house-spread-hero-2021-wp-v01.jpg',
	'verification' => 'editorial_review',
);

$records[] = array(
	'key'     => 'about',
	'type'    => 'page',
	'slug'    => array( 'he' => 'about', 'en' => 'about' ),
	'title'   => array( 'he' => 'על קומפלט 99', 'en' => 'About Complete99' ),
	'excerpt' => array(
		'he' => 'חזון של עסק מזון שמנוהל כמערכת: אנשים, מתכונים, בקרה וצמיחה.',
		'en' => 'A vision for running a food business as one system: people, recipes, control and growth.',
	),
	'content' => array(
		'he' => $c99_page_content(
			'he',
			'קומפלט 99 מחברת בין המטבח לבין חדר הניהול. המטרה היא להפוך ידע שנמצא אצל אנשים, בקבצים ובשיחות לתהליכים ברורים שאפשר ללמד, למדוד ולשפר — בלי למחוק את האופי של האוכל ואת האחריות המקצועית.',
			array( 'בעלות ברורה על כל תהליך ותוכן.', 'תיעוד מקורות, שינויים ואישורים.', 'עבודה נוחה גם לעובדים שאינם יושבים מול מחשב.', 'הפרדה בין מידע ציבורי לבין מידע תפעולי רגיש.' ),
			'בכל שיתוף פעולה מגדירים יחד את המצב הקיים, את בעלי התפקידים, את מטרות השירות ואת סדר ההתקדמות. כך נשמר חיבור ברור בין העבודה במטבח לבין החלטות ההנהלה.',
			'',
		),
		'en' => $c99_page_content(
			'en',
			'Complete99 connects the kitchen with the management room. Its purpose is to turn knowledge held by people, files and conversations into clear processes that can be taught, measured and improved—without erasing food identity or professional accountability.',
			array( 'A clear owner for every process and content item.', 'Sources, changes and approvals recorded.', 'Usable workflows for teams away from a desk.', 'A firm boundary between public and sensitive operational information.' ),
			'Every engagement defines the current state, responsible roles, service objectives and order of work. This keeps kitchen execution connected to management decisions.',
			'',
		),
	),
);

$records[] = array(
	'key'     => 'proposal',
	'type'    => 'page',
	'slug'    => array( 'he' => 'request-proposal', 'en' => 'request-proposal' ),
	'title'   => array( 'he' => 'בקשת התאמה והצעה', 'en' => 'Request a fit review and proposal' ),
	'excerpt' => array(
		'he' => 'מסלול קצר ומכבד לבדיקת צרכים לפני שמתחייבים לפתרון.',
		'en' => 'A concise, responsible fit review before any solution is promised.',
	),
	'content' => array(
		'he' => $c99_page_content(
			'he',
			'הצעה טובה מתחילה בתמונה מדויקת. הטופס מרכז את הפנייה ומעביר אותה לטיפול מתועד של בעל תפקיד.',
			array( 'סוג הארגון ומספר אתרי השירות.', 'היקף מוערך ושעות פעילות.', 'מודל מטבח, הגשה ומשלוחים קיים.', 'דרישות חוזיות, תזונתיות ותפעוליות שידועות בשלב זה.' ),
			'לאחר קבלת הפנייה נקבע מי מטפל בה, אילו מסמכים נדרשים ומה היקף הסיור או בדיקת ההיתכנות. המחיר ולוח הזמנים נגזרים ממסמך דרישות מוסכם.',
			'',
		),
		'en' => $c99_page_content(
			'en',
			'A sound proposal starts with an accurate picture. The form gathers the enquiry for documented handling by an assigned owner.',
			array( 'Organisation type and number of service locations.', 'Estimated scale and operating hours.', 'Current kitchen, service and delivery model.', 'Known contractual, dietary and operational requirements.' ),
			'After receipt, an owner is assigned, missing documents are identified, and any site visit or feasibility review is scoped. Price and timing follow an agreed requirements record.',
			'',
		),
	),
);

$records[] = array(
	'key'     => 'tender-pack',
	'type'    => 'page',
	'slug'    => array( 'he' => 'tender-pack', 'en' => 'tender-pack' ),
	'title'   => array( 'he' => 'מרכז מסמכים למכרז ולהתקשרות', 'en' => 'Tender and procurement pack' ),
	'excerpt' => array(
		'he' => 'מקום מסודר למסמכי יכולת, אחריות, בטיחות ושירות עבור תהליך הרכש.',
		'en' => 'A governed place for capability, responsibility, safety and service documents in procurement.',
	),
	'content' => array(
		'he' => $c99_page_content(
			'he',
			'ארגונים צריכים לקבל החלטה על בסיס מסמכים ולא על בסיס סיסמאות. מרכז המכרזים מיועד לרכז גרסאות מאושרות של פרופיל החברה, מתודולוגיית השירות, מטריצת אחריות, תהליך חריגים ומסמכים נדרשים.',
			array( 'רשימת מסמכים עם בעלים ותאריך עדכון.', 'גרסה נפרדת לצפייה ציבורית ולחדר מידע מורשה.', 'קישור ברור בין דרישה למסמך התומך בה.', 'יומן עדכונים כדי למנוע שימוש בגרסה ישנה.' ),
			'צוות הרכש מקבל אינדקס מסמכים מסודר, חלוקת אחריות ותהליך לשאלות והשלמות. חדר המידע המורשה נשאר נפרד מן האתר הציבורי.',
			'',
		),
		'en' => $c99_page_content(
			'en',
			'Institutions need to decide from documents, not slogans. The tender centre is designed to hold approved versions of the company profile, service method, responsibility matrix, exception process and required evidence.',
			array( 'A document register with owner and update date.', 'Separate public and authorised data-room views.', 'A clear link between each requirement and its supporting document.', 'Change history that prevents use of an obsolete version.' ),
			'Procurement teams receive an organised document index, ownership map and process for questions and completion. The authorised data room remains separate from the public site.',
			'',
		),
	),
);

$records[] = array(
	'key'     => 'contact',
	'type'    => 'page',
	'slug'    => array( 'he' => 'contact', 'en' => 'contact' ),
	'title'   => array( 'he' => 'יצירת קשר', 'en' => 'Contact Complete99' ),
	'excerpt' => array(
		'he' => 'ספרו לנו מה אתם מנסים להפעיל, לשפר או להרחיב.',
		'en' => 'Tell us what you need to operate, improve or expand.',
	),
	'content' => array(
		'he' => $c99_page_content( 'he', 'אפשר להתחיל בשאלה קצרה או בתיאור מלא של האתר. טופס הפנייה מרכז את המידע הראשוני ומעביר אותו לטיפול של בעל תפקיד.', array( 'פנייה לשירות מוסדי.', 'פנייה על שותפות או ספק.', 'שאלה על מנות ומקורות.', 'בקשת סקירה של מערכת ההפעלה.' ), 'ציינו מידע שאפשר לשתף בבטחה. מסמכים רגישים, מידע רפואי ופרטי עובדים אינם נשלחים בטופס הציבורי.', '' ),
		'en' => $c99_page_content( 'en', 'Start with a short question or a fuller description of the site. The enquiry form gathers the initial information for an assigned owner.', array( 'Institutional service enquiry.', 'Partner or supplier enquiry.', 'Question about dishes and sources.', 'Request an operating-system review.' ), 'Share only information that can be handled safely. Sensitive documents, medical information and employee records do not belong in the public form.', '' ),
	),
);

$records[] = array(
	'key'     => 'app',
	'type'    => 'page',
	'slug'    => array( 'he' => 'app', 'en' => 'app' ),
	'title'   => array( 'he' => 'מרכז השליטה של קומפלט 99', 'en' => 'The Complete99 command centre' ),
	'excerpt' => array(
		'he' => 'סקירה של המערכת שמחברת פתיחת יום, מזון, צוות וצמיחה.',
		'en' => 'A tour of the system connecting opening, food, people and growth.',
	),
	'content' => array(
		'he' => $c99_page_content( 'he', 'מרכז השליטה מחבר בין האתר הציבורי לבין עבודת השירות היומית. הוא מרכז תהליכים לפי סניף ותפקיד ומלווה את התקשרות המזון; הוא אינו מוצע כמוצר תוכנה עצמאי.', array( 'לוח ״היום״ לפי תפקיד וסניף.', 'פתיחת מסעדה, משימות, תמונות ואישורים.', 'מתכונים, BOM, מלאי, רכש ובזבוז.', 'נכסי מותג, קמפיינים ומעקב אחר פניות.' ), 'הסיור מציג את מבנה העבודה: משימה, בעלים, זמן יעד והמשך פעולה. הרחבה לסניפים נוספים נשענת על אותו מודל.', '' ),
		'en' => $c99_page_content( 'en', 'The command centre connects the public site with daily service work. It coordinates processes by location and role as part of the foodservice engagement; it is not offered as standalone software.', array( 'A role- and location-specific Today view.', 'Restaurant opening, tasks, photos and approvals.', 'Recipes, BOM, inventory, procurement and waste.', 'Brand assets, campaigns and enquiry tracking.' ), 'The tour presents the operating structure: action, owner, due time and next step. Additional locations use the same model.', '' ),
	),
);

$service_data = array(
	array(
		'institutional-catering',
		'הסעדה מוסדית',
		'Institutional foodservice',
		'תכנון שירות מזון שמתחיל בדרישות האתר, באחריות ובמדדי שירות.',
		'Foodservice design grounded in site requirements, accountability and measurable service.',
		array( 'מיפוי קהלי סועדים ונקודות שירות.', 'חלוקת אחריות בין הלקוח, המטבח והספקים.', 'תכנון תפריט, ייצור, הובלה והגשה.', 'בקרת חריגים, תלונות ושיפור מתועד.' ),
		array( 'Map diners and service points.', 'Define client, kitchen and supplier responsibilities.', 'Plan menu, production, transport and service.', 'Record exceptions, feedback and improvements.' ),
	),
	array(
		'employee-meals',
		'ארוחות לעובדים',
		'Employee meals',
		'מסגרת לארוחות יום־יומיות שמאזנת חוויית עובד, תפעול ותקציב.',
		'A daily meal framework balancing employee experience, operations and budget.',
		array( 'מחזור תפריטים לפי אוכלוסייה ועונה.', 'תכנון חלופות לפי דרישות השירות.', 'משוב עובדים שאפשר לתרגם לפעולה.', 'מדידת ביקוש ותכנון עודפים.' ),
		array( 'Menu cycles matched to audience and season.', 'Alternatives aligned with service requirements.', 'Employee feedback converted into accountable actions.', 'Demand measurement and surplus planning.' ),
	),
	array(
		'dining-room-management',
		'ניהול חדרי אוכל',
		'Dining-room management',
		'תפעול עקבי של קבלת סחורה, הכנה, הגשה, סגירה והעברת משמרת.',
		'Consistent receiving, preparation, service, closing and handover workflows.',
		array( 'פתיחת יום לפי אתר ותפקיד.', 'נקודות בקרה עם תמונה, זמן וחתימה.', 'טיפול במחסור או חריגה בזמן אמת.', 'סיכום יום שמזין את התכנון הבא.' ),
		array( 'Opening by location and role.', 'Evidence points with photo, time and sign-off.', 'Shortage and exception handling during service.', 'A daily close feeding the next plan.' ),
	),
	array(
		'onsite-kitchen-operations',
		'הפעלת מטבח באתר הלקוח',
		'On-site kitchen operations',
		'מודל הפעלה שמכבד את תנאי האתר ואת גבולות האחריות.',
		'An operating model built around actual site conditions and responsibility boundaries.',
		array( 'סיור תשתיות וזרימת עבודה.', 'הגדרת ציוד, אחסון ונקודות מסירה.', 'תכנית כוח אדם והכשרה לפי היקף השירות.', 'תהליך מסודר לתקלות והשבתת ציוד.' ),
		array( 'Infrastructure and workflow review.', 'Equipment, storage and handover definition.', 'Staffing and training plan based on service scope.', 'A governed response to faults and downtime.' ),
	),
	array(
		'central-kitchen-delivery',
		'מטבח מרכזי והפצה',
		'Central kitchen and delivery',
		'חיבור בין תכנון ייצור, אצוות, אריזה, מסירה וקבלה בסניף.',
		'Connecting production plans, batches, packing, dispatch and location receipt.',
		array( 'תכנון אצווה לפי הזמנה ותחזית.', 'זיהוי אצווה ושרשרת מסירה מתועדת.', 'התאמת אריזה ותיוג לדרישה מאושרת.', 'אישור קבלה וטיפול בפערים.' ),
		array( 'Batch plans tied to orders and forecasts.', 'Batch identity and documented chain of custody.', 'Packaging and labelling against approved requirements.', 'Receipt confirmation and discrepancy handling.' ),
	),
	array(
		'menu-nutrition-planning',
		'תכנון תפריט ומידע תזונתי',
		'Menu and nutrition planning',
		'הפרדה בין תכנון קולינרי, מידע על מרכיבים ובדיקה מקצועית של טענות תזונה.',
		'Clear separation between culinary planning, ingredient data and qualified nutrition review.',
		array( 'מטרות תפריט לפי קהל ושירות.', 'גרסאות מתכון עם תשואה ואלרגנים.', 'חלוקת אחריות בין הצוות הקולינרי לבין בעלי המקצוע.', 'עדכון תפריט ומידע מאותו מקור.' ),
		array( 'Menu objectives by audience and service.', 'Recipe versions with yield and allergen fields.', 'Clear responsibility between culinary and qualified professionals.', 'Menu and information updated from one source.' ),
	),
);
foreach ( $service_data as $item ) {
	$records[] = array(
		'key'          => $item[0],
		'type'         => 'c99_service',
		'slug'         => array( 'he' => $item[0], 'en' => 'en-' . $item[0] ),
		'title'        => array( 'he' => $item[1], 'en' => $item[2] ),
		'excerpt'      => array( 'he' => $item[3], 'en' => $item[4] ),
		'content'      => array(
			'he' => $c99_page_content( 'he', $item[3] . ' התהליך נבנה סביב נתוני האתר ולא סביב חבילה אחידה מראש.', $item[5], 'פגישת האפיון מתעדת קהל, שעות, תשתית, היקף, בעלי תפקידים ודרישות חוזיות. לאחר מכן נוצרת מטריצת פערים והצעת שלבים.', '' ),
			'en' => $c99_page_content( 'en', $item[4] . ' The operating design follows site requirements rather than a one-size-fits-all package.', $item[6], 'Discovery records the audience, hours, infrastructure, scope, roles and contractual requirements. A gap register and staged proposal then follow.', '' ),
		),
		'status'       => in_array( $item[0], array( 'education', 'senior-living-welfare' ), true ) ? 'draft' : 'publish',
		'verification' => 'proof_gated',
	);
}

$industry_data = array(
	array( 'companies-offices', 'חברות ומשרדים', 'Companies and offices', 'שירות שמתחבר לחוויית העובד, לשעות העבודה ולמגבלות האתר.', 'Service aligned with employee experience, working hours and site constraints.' ),
	array( 'manufacturing-logistics', 'ייצור ולוגיסטיקה', 'Manufacturing and logistics', 'תכנון לאתרים עם משמרות, עומסים, אזורי שירות ודרישות מסירה שונות.', 'Planning for shifts, peak loads, service zones and distinct handover requirements.' ),
	array( 'education', 'מוסדות חינוך', 'Education', 'מסגרת הוכחה מחמירה לשירות עבור קטינים וצוותי חינוך.', 'A strict evidence framework for serving minors and education teams.' ),
	array( 'senior-living-welfare', 'דיור מוגן ורווחה', 'Senior living and welfare', 'שירות רגיש שמחייב גורמים מקצועיים, תיעוד ואחריות ברורה.', 'A sensitive service requiring qualified review, documentation and clear accountability.' ),
);
foreach ( $industry_data as $item ) {
	$records[] = array(
		'key'          => $item[0],
		'type'         => 'c99_industry',
		'slug'         => array( 'he' => $item[0], 'en' => 'en-' . $item[0] ),
		'title'        => array( 'he' => $item[1], 'en' => $item[2] ),
		'excerpt'      => array( 'he' => $item[3], 'en' => $item[4] ),
		'content'      => array(
			'he' => $c99_page_content( 'he', $item[3], array( 'מיפוי בעלי עניין והחלטות.', 'תיעוד שעות, עומסים וממשקי אתר.', 'הגדרת מדדי שירות ברורים.', 'מסלול חריגים והסלמה מוסכם.' ), 'העבודה מתחילה במסמך דרישות משותף. לכל החלטה יש בעל תפקיד, וכל שינוי נשמר ביומן גרסאות.', '' ),
			'en' => $c99_page_content( 'en', $item[4], array( 'Stakeholder and decision mapping.', 'Hours, demand and site-interface documentation.', 'Clear service measures.', 'An agreed exception and escalation route.' ), 'Work begins with a shared requirements record. Every decision has an owner, and every change is versioned.', '' ),
		),
		'status'       => in_array( $item[0], array( 'education', 'senior-living-welfare' ), true ) ? 'draft' : 'publish',
		'verification' => 'proof_gated',
	);
}

$platform_data = array(
	array( 'operations-command-center', 'מרכז שליטה תפעולי', 'Operations command centre', 'תמונת מצב אחת שמחברת סניפים, תפקידים, משימות וחריגים.', 'One operating picture across locations, roles, tasks and exceptions.' ),
	array( 'opening-workflows', 'פתיחת יום ורשימות בקרה', 'Opening workflows and checklists', 'מסלול קצר וברור שמראה מה נעשה, מי אישר ומה עדיין חסר.', 'A clear route showing what is done, who approved it and what remains.' ),
	array( 'recipes-bom-food-cost', 'מתכונים, BOM ועלות מנה', 'Recipes, BOM and food cost', 'ניהול גרסאות מתכון, תשואה ואחידות הכנה בין צוותים וסניפים.', 'Recipe versions, yield and consistent preparation across teams and locations.' ),
	array( 'inventory-procurement', 'מלאי, רכש וספקים', 'Inventory, procurement and suppliers', 'זרימה מקבלה ומלאי דרך התראה, הזמנה ואישור פער.', 'A flow from receiving and stock through alert, order and discrepancy approval.' ),
	array( 'multi-location', 'ניהול רב־סניפי', 'Multi-location management', 'סטנדרט ארגוני עם התאמות מקומיות, בלי לאבד בעלות ובקרה.', 'Organisation-wide standards with controlled local overrides.' ),
	array( 'marketing-campaigns', 'מותג, קמפיינים ופניות', 'Brand, campaigns and enquiries', 'בריפים, נכסים, בקרה ותוצאות באותו מרחב עבודה.', 'Briefs, assets, review and results in one governed workspace.' ),
);
foreach ( $platform_data as $item ) {
	$records[] = array(
		'key'          => $item[0],
		'type'         => 'c99_platform_feature',
		'slug'         => array( 'he' => $item[0], 'en' => 'en-' . $item[0] ),
		'title'        => array( 'he' => $item[1], 'en' => $item[2] ),
		'excerpt'      => array( 'he' => $item[3], 'en' => $item[4] ),
		'content'      => array(
			'he' => $c99_page_content( 'he', $item[3] . ' הממשק מיועד למסך מגע ולפעולות קצרות, עם אייקון וטקסט ברור.', array( 'תצוגת ״היום״ לפי תפקיד.', 'בעלות, זמן יעד וסטטוס לכל פעולה.', 'צילום, חתימה או מסמך כחלק מהתהליך.', 'יומן שינויים שאינו תלוי בזיכרון של אדם אחד.' ), 'מתחילים בתהליך מרכזי אחד, מגדירים תפקידים ומרחיבים בהדרגה לסניפים נוספים.', '' ),
			'en' => $c99_page_content( 'en', $item[4] . ' The interface is designed for touch and short actions, using an icon and clear label.', array( 'A role-aware Today view.', 'Owner, due time and status for every action.', 'Photo, signature or document within the workflow.', 'Change history that does not depend on one person’s memory.' ), 'Start with one core workflow, define roles and expand gradually to more locations.', '' ),
		),
		'verification' => 'product_demo',
	);
}

return $records;
