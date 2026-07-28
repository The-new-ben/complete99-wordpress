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

$c99_hub_content = static function ( $language, $intro, $sections, $pathways, $boundary ) {
	$explore_title  = 'he' === $language ? 'מסלולים להמשך' : 'Explore the next layer';
	$boundary_title = 'he' === $language ? 'גבולות אחריות ברורים' : 'Clear responsibility boundaries';
	$html           = '<div class="c99-prose c99-hub-prose"><p class="c99-lead-copy">' . $intro . '</p>';
	foreach ( $sections as $section ) {
		$html .= '<section class="c99-hub-section"><h2>' . $section[0] . '</h2>';
		foreach ( $section[1] as $paragraph ) {
			$html .= '<p>' . $paragraph . '</p>';
		}
		$html .= '</section>';
	}
	$html .= '<section class="c99-hub-pathways"><h2>' . $explore_title . '</h2><ul class="c99-check-list">';
	foreach ( $pathways as $pathway ) {
		$html .= '<li>' . $pathway . '</li>';
	}
	$html .= '</ul></section><section class="c99-content-boundary"><h2>' . $boundary_title . '</h2><p>' . $boundary . '</p></section></div>';
	return $html;
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

$legal_records = array(
	array(
		'key'     => 'privacy',
		'title'   => array( 'he' => 'מדיניות פרטיות', 'en' => 'Privacy policy' ),
		'excerpt' => array(
			'he' => 'מה האתר הציבורי אוסף, למה המידע משמש, מה נשאר מחוץ לטופס וכיצד אפשר לפנות.',
			'en' => 'What the public site collects, why information is used, what stays outside the form and how to ask a question.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'מדיניות זו מתייחסת לאתר הציבורי של קומפלט 99 ולטופס הפנייה שבו. היא אינה מתארת מערכת לקוח פרטית, נתוני עובדים, מצלמות, תשלומים או חיבור לרשת חברתית. אם שירות כזה יופעל בעתיד, השימוש במידע יוגדר במסמך נפרד המתאים למערכת, לצדדים ולמטרה.',
				array(
					array(
						'מידע שנמסר בפנייה',
						array(
							'כאשר שולחים טופס, המערכת שומרת את הפרטים שהוזנו, כגון שם, דרך חזרה, ארגון, נושא ותוכן ההודעה. יש למסור רק מידע הדרוש לשיחה הראשונית. אין לשלוח בטופס הציבורי מידע רפואי, פרטי עובדים, פרטי תשלום, סודות מסחריים או מסמכי מכרז רגישים.',
							'המידע משמש לקבלת הפנייה, הקצאת בעל תפקיד, מענה, שמירת היסטוריית טיפול ומניעת שימוש לרעה. שליחת הטופס אינה מצרפת אדם לרשימת דיוור ואינה יוצרת התקשרות מסחרית.',
						),
					),
					array(
						'מידע טכני וספקי תשתית',
						array(
							'שרתי האתר ומנגנוני האבטחה עשויים לשמור כתובת רשת, זמן, כתובת עמוד, סוג דפדפן ואירועי אבטחה לצורך הפעלה, אבחון תקלה, מניעת תקיפה ועמידה בדרישות דין. ספקי אחסון, אבטחה ותחזוקה רשאים לעבד מידע טכני רק לצורך השירות שהם מספקים ובכפוף להתחייבויות הנדרשות.',
							'אם יתווספו בעתיד כלי מדידה, פרסום או עוגיות שאינן חיוניות, יוצג מידע מתאים ותינתן בחירה כאשר הדין דורש זאת לפני הפעלתם לציבור.',
						),
					),
					array(
						'שמירה, גישה ובקשות',
						array(
							'מידע נשמר רק כל עוד הוא נחוץ לטיפול בפנייה, להמשך קשר שהתבקש, להגנת האתר או לחובה חוקית. הגישה מוגבלת לבעלי תפקיד ולספקים שזקוקים לה. אפשר לבקש עיון, תיקון או מחיקה באמצעות טופס יצירת הקשר; הבקשה תיבדק לפי זהות הפונה, סוג המידע והחובות החלות.',
							'בהתקשרות ממשית, זהות הצד האחראי לעיבוד, מטרות נוספות, תקופות שמירה והעברות נדרשות יופיעו במסמכי ההתקשרות המתאימים.',
						),
					),
				),
				array( 'טופס יצירת קשר — לשאלת פרטיות או בקשת מידע.', 'תנאי שימוש — כללי השימוש באתר.', 'נגישות — התאמות ודיווח על קושי.' ),
				'קומפלט 99 אינה מוכרת מידע שנמסר בטופס הציבורי. מידע יימסר לצד אחר רק לצורך הפעלת האתר והטיפול המבוקש, בהסכמה מתאימה, להגנה על זכויות ובטיחות, או כאשר קיימת חובה חוקית.',
			),
			'en' => $c99_hub_content(
				'en',
				'This policy covers the Complete99 public website and its enquiry form. It does not describe a private client system, employee data, cameras, payments or a social-network connection. If such a service is introduced, its information use will be defined in separate material appropriate to the system, parties and purpose.',
				array(
					array(
						'Information supplied in an enquiry',
						array(
							'When a form is sent, the system stores the fields entered, such as name, return contact, organisation, subject and message. Only information needed for the initial conversation should be supplied. Medical information, employee records, payment details, trade secrets and sensitive tender documents do not belong in the public form.',
							'The information is used to receive the enquiry, assign an owner, respond, retain a handling history and prevent abuse. Sending the form does not add a person to a marketing list and does not create a commercial engagement.',
						),
					),
					array(
						'Technical information and infrastructure providers',
						array(
							'Website servers and security controls may retain network address, time, page address, browser type and security events to operate the service, diagnose faults, prevent attacks and meet legal obligations. Hosting, security and maintenance providers may process technical information only for the service they provide and under appropriate commitments.',
							'If optional measurement, advertising or non-essential cookies are added later, appropriate information and a choice will be provided where required by law before they are enabled for public visitors.',
						),
					),
					array(
						'Retention, access and requests',
						array(
							'Information is retained only while needed to handle an enquiry, continue requested contact, protect the site or meet a legal obligation. Access is limited to responsible roles and providers who need it. A person can request access, correction or deletion through the contact form; the request will be assessed against identity, the information involved and applicable duties.',
							'In an actual engagement, the responsible contracting party, additional purposes, retention periods and necessary transfers will be identified in the appropriate engagement documents.',
						),
					),
				),
				array( 'Contact form — for a privacy question or information request.', 'Terms of use — rules for using the public site.', 'Accessibility — adjustments and reporting a barrier.' ),
				'Complete99 does not sell information submitted through the public form. Information is disclosed only to operate the site and handle the requested matter, with an appropriate instruction or consent, to protect rights and safety, or where a legal obligation applies.',
			),
		),
	),
	array(
		'key'     => 'terms',
		'title'   => array( 'he' => 'תנאי שימוש באתר', 'en' => 'Website terms of use' ),
		'excerpt' => array(
			'he' => 'הכללים לשימוש במידע, בטפסים ובסיור היכולות של האתר הציבורי.',
			'en' => 'Rules for using the information, forms and capability tour on the public website.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'האתר מציג מידע על קומפלט 99, מסגרות אפשריות לשירות מזון, ידע קולינרי ושיטת עבודה תפעולית. השימוש באתר כפוף לתנאים אלה. המשך גלישה או שליחת פנייה מבטאים הסכמה לשימוש אחראי באתר, אך אינם יוצרים חוזה, התחייבות לשירות, יחסי ייעוץ או עסקת רכישה.',
				array(
					array(
						'מידע כללי והצעה נפרדת',
						array(
							'תיאורי שירות, מגזר ופלטפורמה מסבירים גישה ושאלות עבודה. הם אינם הבטחה לקיבולת, זמינות, כשרות, רישוי, כוח אדם, מחיר, חיסכון, תוצאה תזונתית או התאמה לאתר מסוים. כל שירות דורש אפיון, מסמכים והסכם חתום שבו יופיעו זהות הצד המתקשר, היקף, אחריות, תמורה ולוחות זמנים.',
							'שליחת טופס היא בקשה לשיחה בלבד. קומפלט 99 רשאית לבקש מידע נוסף, להחליט שהצורך אינו מתאים למסלול המוצע או להפסיק שיחה שאינה ניתנת לטיפול בטוח וחוקי.',
						),
					),
					array(
						'שימוש מותר בתוכן ובאתר',
						array(
							'אפשר לקרוא, לשתף קישור ולהשתמש במידע לצורך בחינה פנימית סבירה. אין להעתיק מאמר, תמונה, סימן, מאגר או מבנה תוכן בהיקף מסחרי, להסיר ייחוס, להציג את התוכן כעבודה של אחר, לאסוף מידע באופן שמכביד על האתר, לעקוף הרשאות או לנסות לפגוע באבטחה.',
							'חומר שמקורו בצד אחר נשאר כפוף לזכויות ולהרשאות שלו. קישור לאתר חיצוני נועד לנוחות ולהקשר; תוכן, זמינות, פרטיות ותנאים של אותו אתר נמצאים באחריות מפעילו.',
						),
					),
					array(
						'יכולות, חנות ושינויים',
						array(
							'סיור הפלטפורמה מתאר מבנה עבודה ואינו רישיון לתוכנה עצמאית או הבטחה לחיבור חיצוני. עמוד החנות מציג מבנה קטלוג עתידי בלבד; אין בו מוצר לרכישה, מחיר, סל, תשלום, מלאי או משלוח, ולכן תנאים אלה אינם תנאי מכר.',
							'אפשר לעדכן מידע ותנאים כאשר האתר או הדין משתנים. שינוי מהותי יסומן בעמוד. אם סעיף אינו ניתן ליישום, יתר התנאים נשארים בתוקף ככל שהדין מאפשר.',
						),
					),
				),
				array( 'פרטיות — שימוש במידע ופניות.', 'נגישות — יעד, שימוש ודיווח על קושי.', 'יצירת קשר — שאלות על האתר או השירות.' ),
				'אין להסתמך על האתר במקום ייעוץ משפטי, תזונתי, רפואי, בטיחותי, הלכתי או מקצועי אחר. במקרה של סתירה בין האתר להסכם חתום, ההסכם החתום גובר ביחסים שבין הצדדים.',
			),
			'en' => $c99_hub_content(
				'en',
				'This website presents information about Complete99, possible foodservice frameworks, culinary knowledge and an operating method. Use of the site is subject to these terms. Continuing to browse or sending an enquiry represents agreement to use the site responsibly, but does not create a contract, service commitment, advisory relationship or purchase.',
				array(
					array(
						'General information and a separate proposal',
						array(
							'Service, industry and platform descriptions explain an approach and working questions. They do not promise capacity, availability, kosher status, licensing, staffing, price, savings, nutrition outcomes or suitability for a particular site. Every service requires discovery, records and a signed agreement identifying the contracting party, scope, responsibilities, consideration and timing.',
							'Sending a form is only a request for a conversation. Complete99 may request more information, determine that a need does not fit the presented route, or stop a conversation that cannot be handled safely and lawfully.',
						),
					),
					array(
						'Permitted use of content and the site',
						array(
							'A visitor may read, share a link and use information for reasonable internal evaluation. A person may not reproduce an article, image, mark, collection or content structure at commercial scale, remove attribution, present the material as another party’s work, collect information in a way that burdens the site, bypass permissions or attempt to damage security.',
							'Material originating with another party remains subject to that party’s rights and permission. A link to an external website is supplied for convenience and context; its content, availability, privacy and terms remain the responsibility of its operator.',
						),
					),
					array(
						'Capabilities, store and changes',
						array(
							'The platform tour describes a working structure and is not a licence for standalone software or a promise of an external connection. The store page describes a future catalogue structure only; it has no product for purchase, price, basket, payment, stock or delivery, so these terms are not terms of sale.',
							'Information and terms may be updated as the website or law changes. A material revision will be identified on this page. If a provision cannot be applied, the remaining terms continue to the extent permitted by law.',
						),
					),
				),
				array( 'Privacy — information and enquiries.', 'Accessibility — target, use and reporting a barrier.', 'Contact — questions about the site or service.' ),
				'The website is not a substitute for legal, nutrition, medical, safety, religious or other professional advice. Where the website conflicts with a signed agreement, the signed agreement governs the relationship between its parties.',
			),
		),
	),
	array(
		'key'     => 'accessibility',
		'title'   => array( 'he' => 'הצהרת נגישות', 'en' => 'Accessibility statement' ),
		'excerpt' => array(
			'he' => 'יעד הנגישות של האתר, דרכי השימוש, מגבלות ידועות והדרך לדווח על קושי.',
			'en' => 'The site accessibility target, ways of using it, known limitations and how to report a barrier.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'קומפלט 99 מבקשת לאפשר לאנשים עם מוגבלויות להשתמש במידע ובטופס הפנייה באופן עצמאי ומכבד. יעד הפיתוח של האתר הוא רמת AA בתקן הישראלי 5568 ובהנחיות WCAG שעליהן הוא נשען, לצד דרישות הנגישות החלות על השירות. הצהרה זו מתארת יעד ותהליך עבודה ואינה טוענת שנערכה עדיין ביקורת חיצונית מלאה.',
				array(
					array(
						'עקרונות שנכללים במבנה האתר',
						array(
							'האתר נבנה לניווט באמצעות מקלדת, סדר כותרות הגיוני, קישור דילוג לתוכן, סימון מיקוד נראה, ניגודיות, טקסט חלופי לתמונות משמעותיות, תוויות לטפסים והודעות שאינן נשענות על צבע בלבד. העברית מוצגת מימין לשמאל והאנגלית משמאל לימין, בלי לשנות את סדר הקריאה של טכנולוגיית העזר.',
							'כפתורים וקישורים משתמשים גם בטקסט ברור ולא רק באייקון. תפריטים נפתחים צריכים להיות זמינים במקלדת, ותנועה חזותית צריכה לכבד העדפה להפחתת תנועה. הגדלת טקסט ושינוי רוחב מסך אינם אמורים להסתיר פעולה מרכזית.',
						),
					),
					array(
						'מגבלות ועבודה מתמשכת',
						array(
							'תוכן חדש, מסמך שמגיע מצד אחר וחיבור עתידי לשירות חיצוני עלולים ליצור קושי שלא היה באתר הבסיס. לפני פרסום נעשית בדיקה של מבנה, מקלדת, שפה, תמונות וטפסים, אך ייתכנו פערים. אין כיום חנות פעילה, מסמכי הורדה או אזור לקוח ציבורי שיש לתאר בהצהרה זו.',
							'הסדרי נגישות פיזיים אינם מפורסמים כאן משום שלא הוגדר מקום קבלת קהל או אתר שירות מסוים לציבור. כאשר יפורסם מקום כזה, יתווספו פרטי הגישה והשירות המתאימים לו.',
						),
					),
					array(
						'דיווח על קושי ובקשת חלופה',
						array(
							'אם נתקלתם במחסום, השתמשו בטופס יצירת הקשר ובחרו בנושא נגישות. כדאי לציין את כתובת העמוד, הפעולה שניסיתם לבצע, הדפדפן או המכשיר וטכנולוגיית העזר, אם נוח לשתף. אין לשלוח מידע רפואי שאינו נחוץ לטיפול.',
							'הפנייה תועבר לבעל תפקיד לצורך בירור, תיקון או הצעת דרך חלופית לקבלת המידע. פרטי רכז נגישות יפורסמו כאשר ימונה בעל תפקיד ויוגדר ערוץ ישיר; עד אז טופס הקשר הוא הערוץ הציבורי המתועד.',
						),
					),
				),
				array( 'יצירת קשר — דיווח על קושי ובקשת חלופה.', 'פרטיות — כיצד מטפלים בפרטי הפנייה.', 'תנאי שימוש — כללי השימוש באתר.' ),
				'עדכון אחרון: יולי 2026. האתר ייבדק מחדש לאחר שינוי מהותי בתבנית, בטופס, בניווט או בשירות חיצוני. משוב מאנשים המשתמשים בטכנולוגיות עזר הוא חלק חשוב מתהליך השיפור.',
			),
			'en' => $c99_hub_content(
				'en',
				'Complete99 aims to let people with disabilities use its information and enquiry form independently and with dignity. The website development target is AA under Israeli Standard 5568 and the WCAG guidance on which it is based, together with accessibility requirements applicable to the service. This statement describes a target and working process; it does not claim that a complete independent audit has yet taken place.',
				array(
					array(
						'Principles included in the site structure',
						array(
							'The site is built for keyboard navigation, a logical heading order, a skip link, visible focus, contrast, text alternatives for meaningful images, form labels and messages that do not rely on colour alone. Hebrew runs right to left and English left to right without changing the reading order for assistive technology.',
							'Buttons and links use clear text as well as an icon. Expanding menus should be operable with a keyboard, and visual movement should respect a reduced-motion preference. Enlarging text and changing viewport width should not hide a primary action.',
						),
					),
					array(
						'Limitations and continuing work',
						array(
							'New content, a document supplied by another party or a future external-service connection may introduce a barrier that was not present in the foundation. Structure, keyboard operation, language, images and forms are reviewed before publication, but gaps may remain. There is currently no active store, downloadable document collection or public client area to describe in this statement.',
							'Physical accessibility arrangements are not published here because no public reception place or specific public service site has been defined. If such a place is published, the relevant access and service arrangements will be added.',
						),
					),
					array(
						'Report a barrier or request an alternative',
						array(
							'If you encounter a barrier, use the contact form and choose accessibility as the subject. It helps to include the page address, the action attempted, browser or device, and assistive technology if you are comfortable sharing it. Medical information that is not needed to handle the request should not be sent.',
							'The enquiry will be assigned for investigation, correction or an alternative way to receive the information. Details of an accessibility coordinator will be published when a responsible role and direct channel are appointed; until then, the contact form is the documented public route.',
						),
					),
				),
				array( 'Contact — report a barrier and request an alternative.', 'Privacy — handling information in the enquiry.', 'Terms of use — rules for using the site.' ),
				'Last updated: July 2026. The site will be reviewed after a material change to its template, form, navigation or external service. Feedback from people who use assistive technology is an important part of improvement.',
			),
		),
	),
);

foreach ( $legal_records as $legal ) {
	$legal['type']           = 'page';
	$legal['slug']           = array( 'he' => $legal['key'], 'en' => $legal['key'] );
	$legal['verification']   = 'editorial_review';
	$legal['index_eligible'] = true;
	$records[]               = $legal;
}

$hub_records = array(
	array(
		'key'     => 'services',
		'title'   => array( 'he' => 'שירותי מזון ותפעול לארגונים', 'en' => 'Foodservice and operations for organisations' ),
		'excerpt' => array(
			'he' => 'מפת שירותים שמתחילה בדרישות האתר ומחברת תכנון, מטבח, הגשה, בקרה ושיפור.',
			'en' => 'A service map grounded in site requirements, connecting planning, kitchen work, service, control and improvement.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'שירות מזון מוסדי אינו מוצר מדף. הוא מפגש יום־יומי בין אנשים, תשתית, חומרי גלם, לוחות זמנים, חוזים וציפיות של סועדים. מרכז השירותים של קומפלט 99 מסביר כיצד מפרקים את המפגש הזה להחלטות שאפשר לנהל, בלי להבטיח יכולת, מחיר או התאמה לפני שנאספו נתוני האתר.',
				array(
					array(
						'מתחילים בתמונת מצב משותפת',
						array(
							'העבודה נפתחת במיפוי סוג הארגון, מספר אתרי השירות, קהלי הסועדים, שעות הפעילות, מודל המטבח, נקודות הקבלה וההגשה, מגבלות תזונה ידועות וחלוקת האחריות בין הלקוח, המפעיל והספקים. המסמך הראשון אינו פתרון; הוא בסיס מסודר לשאלות ולהחלטות.',
							'לאחר המיפוי אפשר להבחין בין צורך בשירות מזון מלא, ארוחות עובדים, ניהול חדר אוכל, הפעלת מטבח באתר, ייצור והפצה או תכנון תפריט. ההפרדה הזאת מונעת מדף אחד להתחרות בכל הנושאים ומאפשרת לכל מסלול לענות על צורך מוגדר.',
						),
					),
					array(
						'מחזור חיים שאפשר לנהל',
						array(
							'כל מסלול בנוי מאותם עקרונות: אפיון, תכנון, הקמה, הפעלה, טיפול בחריגים ובחינה מחודשת. לכל שלב מוגדרים בעל תפקיד, מסמכים, נקודת החלטה והמשך פעולה. כך מנהלי רכש, משאבי אנוש, תפעול ומזון יכולים לראות אותה תמונה גם כשהאחריות מתחלקת בין כמה צוותים.',
							'מדדי השירות נקבעים רק לאחר שהמטרות ברורות. זמני הגשה, זמינות חלופות, פחת, שביעות רצון או עמידה בתכנית אינם סיסמאות; הם דורשים הגדרה, מקור נתונים, תדירות מדידה ובעלים שמסוגל לפעול כאשר יש פער.',
						),
					),
					array(
						'מידע ציבורי מול מידע תפעולי',
						array(
							'האתר מציג את שיטת העבודה ואת השאלות שארגון צריך לשאול. פרטי עלויות ספק, כמויות ייצור, סידורי עובדים, מסמכי בטיחות פנימיים ונתוני סועדים נשארים במרחב מורשה. ההפרדה מאפשרת שקיפות מסחרית בלי לחשוף מידע תפעולי רגיש.',
						),
					),
				),
				array( 'הסעדה מוסדית — מסגרת השירות הכוללת.', 'ארוחות עובדים — חוויית יום עבודה ותכנון ביקוש.', 'ניהול חדר אוכל והפעלת מטבח — אחריות ותהליכי יום.', 'מטבח מרכזי והפצה — אצוות, מסירה וקבלה.', 'תכנון תפריט ומידע תזונתי — קולינריה לצד ביקורת מקצועית.' ),
				'קיבולת, זמינות, כשרות, רישוי, כוח אדם, מחיר ותוצאות שירות נקבעים רק על בסיס אתר, מסמכים וגורמים מוסמכים. התוכן כאן מסביר את המסגרת ואינו מחליף אפיון, חוזה או ייעוץ מקצועי.',
			),
			'en' => $c99_hub_content(
				'en',
				'Institutional foodservice is not an off-the-shelf product. It is a daily meeting of people, infrastructure, ingredients, schedules, contracts and diner expectations. This services centre explains how Complete99 turns that complexity into governable decisions without promising capacity, price or suitability before the site facts are known.',
				array(
					array(
						'Start with a shared operating picture',
						array(
							'Discovery records the organisation type, service locations, diner groups, operating hours, kitchen model, receiving and service points, known dietary constraints, and the division of responsibility between client, operator and suppliers. The first record is not a proposed solution; it is a disciplined basis for questions and decisions.',
							'That map distinguishes a full foodservice requirement from employee meals, dining-room management, an on-site kitchen, central production and delivery, or menu planning. Clear separation gives each service page one defined job and helps procurement, human resources and operations teams reach the right route.',
						),
					),
					array(
						'A manageable service lifecycle',
						array(
							'Each route follows the same operating logic: discovery, design, mobilisation, daily service, exception handling and review. Every stage has an owner, required records, a decision point and a next action. Several teams can therefore work from one operating picture even when responsibility is distributed.',
							'Service measures are defined only after objectives are agreed. Service times, availability of alternatives, waste, satisfaction and plan adherence each need a definition, data source, review rhythm and accountable owner who can act when performance differs from the plan.',
						),
					),
					array(
						'Public information and operational information',
						array(
							'The public site explains the working method and the questions an organisation should ask. Supplier costs, production quantities, staff arrangements, internal safety records and diner data remain in an authorised workspace. This boundary supports commercial clarity without exposing sensitive operations.',
						),
					),
				),
				array( 'Institutional foodservice — the overall service framework.', 'Employee meals — workday experience and demand planning.', 'Dining-room and on-site kitchen operations — ownership and daily workflows.', 'Central kitchen and delivery — batches, handover and receipt.', 'Menu and nutrition information — culinary planning with qualified review.' ),
				'Capacity, availability, kosher status, licensing, staffing, price and service outcomes can only be stated for a documented site with the relevant professional records. This content describes the framework; it does not replace discovery, contract terms or qualified advice.',
			),
		),
	),
	array(
		'key'     => 'industries',
		'title'   => array( 'he' => 'פתרונות שירות מזון לפי סביבת עבודה', 'en' => 'Foodservice by operating environment' ),
		'excerpt' => array(
			'he' => 'אותו אוכל פוגש צרכים שונים במשרד, במפעל, במשמרת ובמסגרת רגישה.',
			'en' => 'The same meal meets different requirements in an office, plant, shift operation or sensitive setting.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'מגזר אינו רק תווית שיווקית. הוא משנה את שעות השירות, זרימת האנשים, נקודות המסירה, אופי התפריט, בעלי העניין והמסמכים הדרושים. מרכז המגזרים מסדר את ההבדלים האלה כדי שארגון יוכל לזהות את השאלות הנכונות לו לפני בחירת מודל שירות.',
				array(
					array(
						'מתרגמים את סביבת העבודה לדרישות',
						array(
							'בחברות ובמשרדים הדגש עשוי להיות חוויית עובד, גמישות בין ימי עבודה ושילוב עם מתקני האתר. בייצור ובלוגיסטיקה נדרשים לעיתים תכנון משמרות, חלונות הגשה קצרים, פיזור בין אזורים והמשכיות גם בשעות שאינן שגרתיות. המסגרת משתנה, ולכן גם תכנית ההפעלה חייבת להשתנות.',
							'בכל מגזר ממפים מי מחליט, מי משתמש בשירות ומי נושא באחריות המקצועית. מנהלי משאבי אנוש, רכש, מתקנים, בטיחות, כספים ומזון מסתכלים על אותה התקשרות מזוויות שונות; מסמך דרישות משותף מונע הנחות סותרות.',
						),
					),
					array(
						'סביבות רגישות דורשות מסלול נפרד',
						array(
							'שירות במסגרות חינוך, דיור מוגן או רווחה אינו הרחבה אוטומטית של שירות משרדי. גיל, מרקמים, אלרגנים, פרטיות, הסכמה, רגולציה ואחריות קלינית עשויים לשנות את ההחלטות. לכן עמודי המגזר האלה אינם מוצגים לציבור כמענה זמין לפני שיש מסמכים וגורמים מקצועיים מתאימים.',
							'קומפלט 99 אינה מפרסמת הבטחות רפואיות או תזונתיות כלליות. כאשר נדרשת התאמה אישית, היא שייכת לבעל מקצוע מוסמך ולתהליך המאושר של הארגון.',
						),
					),
					array(
						'בונים בסיס שאפשר להרחיב',
						array(
							'גם בארגון רב־אתרי, מתחילים באתר אחד ובתהליך מרכזי אחד. לאחר שהשפה, התפקידים והמדדים ברורים אפשר לתעד אילו רכיבים הם תקן ארגוני ואילו התאמות שייכות למקום, לקהל או לשעות פעילות מסוימות.',
						),
					),
				),
				array( 'חברות ומשרדים — שירות כחלק מחוויית העבודה.', 'ייצור ולוגיסטיקה — משמרות, עומסים ומסירה.', 'חינוך — מסלול שמחייב אחריות ייעודית.', 'דיור מוגן ורווחה — שירות רגיש עם גורמים מקצועיים.' ),
				'דוגמאות המגזר מתארות שאלות ותהליכים, לא רשימת לקוחות או ניסיון שלא פורסם. התאמה, שעות, קיבולת, רגולציה ותוצאות נקבעות לכל ארגון ואתר בנפרד.',
			),
			'en' => $c99_hub_content(
				'en',
				'An industry is more than a marketing label. It changes service hours, movement of people, handover points, menu priorities, stakeholders and required records. This centre organises those differences so an organisation can identify the right questions before choosing a service model.',
				array(
					array(
						'Translate the workplace into requirements',
						array(
							'Companies and offices may prioritise employee experience, flexible attendance patterns and alignment with site facilities. Manufacturing and logistics operations may require shift planning, short service windows, distributed service points and continuity outside conventional hours. Different operating conditions require different service designs.',
							'Every environment also has a distinct decision group. Human resources, procurement, facilities, safety, finance and food professionals view the same engagement from different angles. A shared requirements record prevents conflicting assumptions and makes responsibility visible.',
						),
					),
					array(
						'Sensitive settings need a separate route',
						array(
							'Education, senior living and welfare are not automatic extensions of an office service. Age, textures, allergens, privacy, consent, regulation and clinical responsibility may change the decision. These sector routes are therefore not presented as available public offers until appropriate records and qualified professionals are in place.',
							'Complete99 does not publish general medical or nutrition promises. Where individual suitability is required, the decision belongs to a qualified professional and the organisation’s approved process.',
						),
					),
					array(
						'Create a foundation that can expand',
						array(
							'Even in a multi-site organisation, work begins with one location and one core process. Once language, roles and measures are clear, the organisation can distinguish central standards from controlled local adaptations for a particular location, audience or operating schedule.',
						),
					),
				),
				array( 'Companies and offices — foodservice within the work experience.', 'Manufacturing and logistics — shifts, demand peaks and handover.', 'Education — a route with dedicated accountability.', 'Senior living and welfare — sensitive service with qualified oversight.' ),
				'The sector examples describe questions and processes, not undisclosed clients or experience claims. Suitability, hours, capacity, regulatory obligations and outcomes must be established for each organisation and site.',
			),
		),
	),
	array(
		'key'     => 'platform',
		'title'   => array( 'he' => 'פלטפורמת התפעול של קומפלט 99', 'en' => 'The Complete99 operations platform' ),
		'excerpt' => array(
			'he' => 'מרכז עבודה שמחבר פתיחת יום, מזון, מלאי, צוות, סניפים, מותג ופניות.',
			'en' => 'A working centre connecting daily opening, food, stock, people, locations, brand and enquiries.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'פלטפורמת קומפלט 99 נועדה ללוות את שירות המזון בפעולות היום־יום. היא מחברת בין מה שהארגון הבטיח, מה שהמטבח צריך לבצע ומה שהמנהלים צריכים לראות. המערכת אינה מוצעת כתוכנה עצמאית; מבנה ההרשאות, התהליכים והנתונים נקבע כחלק מהתקשרות תפעולית.',
				array(
					array(
						'מסך קצר לפעולה, תמונה מלאה לניהול',
						array(
							'לעובד בשטח מוצגת פעולה ברורה: מה נדרש, באיזה סניף, עד מתי ומה נחשב השלמה. למנהל מוצגת תמונה רחבה של משימות, חריגים, אישורים ומגמות. אייקון אינו מחליף טקסט, וטקסט אינו מחליף אחריות; שניהם עובדים יחד כדי לצמצם טעויות.',
							'פתיחת יום, קבלת סחורה, הכנה, הגשה, סגירה וטיפול בתקלה נשענים על אותה תבנית של בעלים, זמן יעד, סטטוס והמשך. תמונה, חתימה או מסמך מצטרפים רק כאשר הם באמת תומכים בהחלטה.',
						),
					),
					array(
						'מקור אחד למזון ולשינויים',
						array(
							'גרסת מתכון, תשואה, אלרגנים, מרכיבים ומפרט כמויות מתחברים לתפריט ולתכנון הייצור. עלויות ספקים, מחירי קנייה ונתוני מלאי נשארים פרטיים לפי תפקיד. כאשר מתכון משתנה, השינוי מתועד לפני שהוא משפיע על עבודה בסניף נוסף.',
							'גם נכסי מותג וקמפיינים מתחילים מבריף, קהל, מסר ובעלים. חיבור לרשת חברתית, ספק, מצלמה או שירות חיצוני נעשה רק לאחר הרשאה, בדיקת זרימת מידע וקבלת אישור מהמערכת החיצונית.',
						),
					),
					array(
						'רב־סניפי מהיום הראשון',
						array(
							'כל פריט תפעולי קשור לארגון, סניף, תפקיד וגרסה. המבנה מאפשר להוסיף מקום חדש בלי לשכפל כאוס: תקן מרכזי נשמר פעם אחת, והתאמה מקומית מקבלת בעלים וסיבה מתועדת.',
						),
					),
				),
				array( 'מרכז שליטה — תמונת היום והחריגים.', 'פתיחת יום ורשימות בקרה — תהליך לפי תפקיד וסניף.', 'מתכונים, מפרטי כמויות ועלות מנה — גרסאות ובעלות.', 'מלאי, רכש וספקים — קבלה, פער ואישור.', 'ריבוי סניפים — תקן מרכזי והתאמות מקומיות.', 'מותג וקמפיינים — בריף, נכסים ובקרה.' ),
				'עמודי הפלטפורמה מציגים את שיטת העבודה. הם אינם מציגים חיבור חי, נתוני לקוח, מחיר תוכנה או הבטחה לעבוד עם מערכת חיצונית. כל חיבור דורש הרשאה, תכנון אבטחה ובדיקה בסביבה המתאימה.',
			),
			'en' => $c99_hub_content(
				'en',
				'The Complete99 platform supports the daily work of a foodservice engagement. It connects what the organisation has agreed, what the kitchen must execute and what managers need to see. It is not offered as standalone software; permissions, workflows and data structures are configured as part of an operating relationship.',
				array(
					array(
						'Short actions for teams, a complete picture for managers',
						array(
							'Front-line staff see a clear action: what is required, at which location, by when and what counts as completion. Managers see a wider picture of tasks, exceptions, sign-offs and trends. An icon does not replace language, and language does not replace accountability; both work together to reduce ambiguity.',
							'Opening, receiving, preparation, service, closing and fault handling use a common pattern of owner, due time, status and next action. A photo, signature or document is requested only when it materially supports the decision.',
						),
					),
					array(
						'One governed source for food and change',
						array(
							'Recipe versions, yield, allergens, ingredients and a bill of materials connect to menu and production planning. Supplier costs, purchase prices and stock data remain private by role. A recipe change is recorded before it affects work at another location.',
							'Brand assets and campaign work also begin with a brief, audience, message and owner. A connection to a social network, supplier, camera or external service is enabled only after authorisation, a data-flow review and a receipt from the external system.',
						),
					),
					array(
						'Multi-location by design',
						array(
							'Every operating record belongs to an organisation, location, role and version. A new site can therefore be added without copying disorder: the central standard is maintained once, while each local adaptation has an owner and a documented reason.',
						),
					),
				),
				array( 'Command centre — today’s operating picture and exceptions.', 'Opening workflows — role- and location-aware checklists.', 'Recipes, bill of materials and food cost — versions and ownership.', 'Inventory, procurement and suppliers — receiving, discrepancy and approval.', 'Multiple locations — central standards and local adaptations.', 'Brand and campaigns — brief, assets and review.' ),
				'Platform pages explain the operating method. They do not claim a live connection, expose client data, state a standalone software price or promise compatibility with an external system. Every connection requires authorisation, security design and testing in the relevant environment.',
			),
		),
	),
	array(
		'key'     => 'dishes',
		'title'   => array( 'he' => 'אטלס המנות של קומפלט 99', 'en' => 'The Complete99 dish atlas' ),
		'excerpt' => array(
			'he' => 'סיפור, מקור, מרכיבים, מסורת ומתכון בדוק — לכל מנה יש תיק תוכן עצמאי.',
			'en' => 'Story, origins, ingredients, tradition and a tested recipe—one governed dossier for each dish.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'מנה היא יותר מתמונה ושם. היא יכולה לשאת זיכרון משפחתי, מסלול הגירה, טכניקת בישול, חומרי גלם עונתיים וגרסאות רבות שחיות זו לצד זו. אטלס המנות נבנה כדי לכבד את המורכבות הזאת ולתת לקורא תשובה מסודרת, בלי להציג סיפור אחד כאמת יחידה.',
				array(
					array(
						'תיק תוכן ולא פסקת קידום',
						array(
							'כל מנה עתידית מקבלת גרסה עברית וגרסה אנגלית, שלכל אחת לפחות 5,000 מילים מהותיות לאחר עריכה. התיק כולל הגדרת המנה, שמות מקובלים, ציר זמן זהיר, אזורי מוצא, קהילות שהשפיעו עליה, תפקידה במסורת היהודית כאשר יש לכך בסיס, מרכיבים, טכניקות, וריאציות ושאלות שנותרו פתוחות.',
							'לפחות שמונה מקורות נפרדים מלווים את העבודה, ובהם לפחות שני מקורות ראשוניים או מוסדיים. המקורות אינם קישוט: כל טענה היסטורית משמעותית נבדקת מול המקור המתאים, ומחלוקת בין מקורות מתוארת בגלוי.',
						),
					),
					array(
						'מתכון שעבר מטבח',
						array(
							'המתכון הציבורי כולל תשואה, משקלים, טמפרטורות, זמני הכנה ובישול, הוראות, אלרגנים וגרסה. הוא מתפרסם רק לאחר הכנה מתועדת, ביקורת של איש מטבח, בדיקת אלרגנים ואישור התמונות לשימוש ציבורי. עלויות ספקים ומפרט ייצור פנימי אינם חלק מן העמוד הציבורי.',
							'מידע תזונתי מוצג רק כאשר שיטת החישוב והגורם האחראי ברורים. האתר אינו מייחס למנה ריפוי, מניעה או התאמה רפואית ללא ביקורת מקצועית מתאימה.',
						),
					),
					array(
						'בונים ספרייה בלי כפילויות',
						array(
							'לכל מנה יש עמוד מרכזי אחד. גרסאות אזוריות, סיפורי מרכיבים ומסורות מקבלים קישורים והקשר, אך אינם משכפלים את אותה שאלה בעמודים מתחרים. כך הספרייה יכולה לגדול למאות ואלפי פריטים ועדיין להישאר מובנת.',
						),
					),
				),
				array( 'מנות — תיקי התוכן המרכזיים.', 'מרכיבים — חומרי גלם, תפקיד ושימוש.', 'מסורות — הקשר קהילתי, עונתי וטקסי.', 'מרכז הידע — שיטות מחקר, מטבח ותפעול.' ),
				'ששת תיקי המנות הראשונים עדיין נמצאים בעבודה ואינם מוצגים כמאמרים גמורים. מנה תופיע באטלס רק לאחר שהכתיבה, המקורות, בדיקת המטבח, האלרגנים, התמונות והעריכה בשתי השפות הושלמו.',
			),
			'en' => $c99_hub_content(
				'en',
				'A dish is more than a picture and a name. It may carry family memory, migration, cooking technique, seasonal ingredients and several versions that coexist. The dish atlas is designed to respect that complexity and give readers a structured account without presenting one story as the only truth.',
				array(
					array(
						'A dossier, not a promotional paragraph',
						array(
							'Every future dish receives a Hebrew and an English edition, each containing at least 5,000 substantive words after editorial review. The dossier covers definition, common names, a careful timeline, places of origin, communities that shaped it, its place in Jewish tradition where supported, ingredients, techniques, variations and unresolved questions.',
							'At least eight independent sources support the work, including at least two primary or institutional sources. Sources are not decoration: each material historical statement is checked against the appropriate record, and disagreements between sources are explained.',
						),
					),
					array(
						'A recipe tested in the kitchen',
						array(
							'The public recipe records yield, weights, temperatures, preparation and cooking time, instructions, allergens and version. It is published only after a documented preparation, kitchen review, allergen review and public-image approval. Supplier costs and internal production specifications do not belong on the public page.',
							'Nutrition information appears only when the calculation method and responsible reviewer are clear. The site does not attribute treatment, prevention or medical suitability to a dish without appropriate qualified review.',
						),
					),
					array(
						'A library without competing duplicates',
						array(
							'Each dish has one central page. Regional variants, ingredient stories and traditions provide context through links rather than repeating the same question on competing pages. The collection can therefore grow to hundreds or thousands of records while remaining understandable.',
						),
					),
				),
				array( 'Dishes — the central culinary dossiers.', 'Ingredients — raw materials, roles and uses.', 'Traditions — community, seasonal and ritual context.', 'Knowledge — research, kitchen and operating methods.' ),
				'The first six dish dossiers remain in active editorial work and are not presented as finished articles. A dish enters the atlas only after writing, sources, kitchen work, allergen review, imagery and both language editions are complete.',
			),
		),
	),
	array(
		'key'     => 'ingredients',
		'title'   => array( 'he' => 'ספריית המרכיבים וחומרי הגלם', 'en' => 'Ingredient and raw-material library' ),
		'excerpt' => array(
			'he' => 'מאיפה חומר גלם מגיע, מה הוא עושה במנה, כיצד עובדים איתו ומה חשוב לדעת.',
			'en' => 'Where an ingredient comes from, what it does in a dish, how it is handled and what readers need to know.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'ספריית המרכיבים מפרידה בין חומר גלם לבין מנה. היא עונה על שאלות של מקור, עונה, זנים, טעם, מרקם, אחסון, טכניקה ותפקיד קולינרי, ומקשרת למנות ולמסורות שבהן המרכיב מופיע. המטרה היא ידע שימושי ומבוסס, לא רשימת יתרונות שיווקית.',
				array(
					array(
						'זהות, מסלול ושימוש',
						array(
							'עמוד מרכיב מתחיל בשם המקובל ובשמות חלופיים, במשפחה הבוטנית או בקטגוריית המזון כאשר הדבר רלוונטי, ובתיאור הדרך שבה הגיע למטבחים שונים. אזור גידול אינו בהכרח מקום מוצא, ושימוש מסורתי בקהילה אחת אינו הופך למסורת אוניברסלית.',
							'החלק המעשי מסביר בחירה, אחסון, הכנה, שיטות בישול ושילובים. כאשר קיימים זנים או עיבודים שונים, העמוד מפריד ביניהם כדי שלא לייחס לאחד תכונה של אחר.',
						),
					),
					array(
						'אלרגנים, בטיחות ותזונה',
						array(
							'מידע על אלרגנים והצלבה נבדק לפי חומר, ספק וסביבת עבודה; עמוד כללי אינו יכול לקבוע שמוצר מסוים בטוח. גם ערכים תזונתיים משתנים לפי זן, עיבוד, כמות ושיטת הכנה. כל נתון מספרי דורש מקור, יחידה והקשר.',
							'הספרייה אינה נותנת הוראה רפואית ואינה מסווגת מרכיב כ״בריא״ או ״לא בריא״ ללא הקשר. היא יכולה להסביר הרכב ושימוש, ולהפנות להחלטה מקצועית כאשר נדרשת התאמה אישית.',
						),
					),
					array(
						'קשר למטבח ולרכש',
						array(
							'אותו מזהה מרכיב יכול לשרת את התוכן הציבורי ואת מפרט המתכון, בעוד ספק, מחיר, מלאי ומספר אצווה נשארים במערכת המורשית. כך שינוי שם או מידע ציבורי אינו מוחק את עקבות הרכש והתפעול.',
						),
					),
				),
				array( 'מקור והיסטוריה — בהבחנה בין עובדה, מסורת והשערה.', 'טעם, מרקם וטכניקה — ידע מעשי למטבח.', 'אחסון ובטיחות — הנחיות לפי הקשר.', 'מנות ומסורות קשורות — קישורים במקום שכפול.' ),
				'עמודי מרכיב ייפתחו לציבור רק כאשר יש להם בעל תוכן, מקורות, טקסט מקורי וקשרים ברורים למנות או למסורות. עמודי תגית קצרים ורשימות אוטומטיות אינם תחליף למאמר מרכיב ערוך.',
			),
			'en' => $c99_hub_content(
				'en',
				'The ingredient library separates a raw material from a dish. It answers questions about origin, season, varieties, flavour, texture, storage, technique and culinary role, then connects to dishes and traditions in which the ingredient appears. Its purpose is grounded, useful knowledge rather than a list of marketing benefits.',
				array(
					array(
						'Identity, movement and use',
						array(
							'An ingredient page begins with the common name and alternatives, its botanical family or food category where relevant, and the routes through which it reached different kitchens. A present growing region is not necessarily a place of origin, and use in one community is not a universal tradition.',
							'The practical section covers selection, storage, preparation, cooking methods and pairings. Where varieties or processing methods differ, the page distinguishes them so that a quality of one is not assigned to another.',
						),
					),
					array(
						'Allergens, safety and nutrition',
						array(
							'Allergen and cross-contact information must be checked for the material, supplier and working environment; a general page cannot declare a specific product safe. Nutrition values also vary by variety, processing, quantity and preparation. Every number needs a source, unit and context.',
							'The library does not provide medical instructions or classify an ingredient as simply healthy or unhealthy. It can explain composition and culinary use, then direct individual suitability decisions to an appropriate professional.',
						),
					),
					array(
						'Connections to kitchen and procurement',
						array(
							'One ingredient identity can support public knowledge and recipe specifications while supplier, price, inventory and batch records remain in the authorised system. A public naming change therefore does not erase procurement and operating history.',
						),
					),
				),
				array( 'Origin and history — distinguishing record, tradition and hypothesis.', 'Flavour, texture and technique — practical kitchen knowledge.', 'Storage and safety — contextual guidance.', 'Related dishes and traditions — useful links instead of repetition.' ),
				'Ingredient pages become public only when they have a content owner, sources, original writing and meaningful links to dishes or traditions. Thin tag pages and automated lists are not substitutes for an edited ingredient article.',
			),
		),
	),
	array(
		'key'     => 'traditions',
		'title'   => array( 'he' => 'מסורות אוכל יהודיות וקהילתיות', 'en' => 'Jewish and community food traditions' ),
		'excerpt' => array(
			'he' => 'מועדים, שבת, הגירה וזיכרון משפחתי דרך מנות, מקורות וקולות שונים.',
			'en' => 'Holidays, Shabbat, migration and family memory through dishes, sources and multiple voices.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'אוכל מסורתי משתנה כאשר משפחות נודדות, חומרי גלם מתחלפים ודורות חדשים נותנים משמעות אחרת לאותה מנה. מרכז המסורות מתאר את התנועה הזאת בזהירות. הוא מחבר בין מקורות כתובים, עדויות משפחתיות, מנהגים קהילתיים ומעשה המטבח, בלי לקבוע שגרסה אחת מייצגת את כל היהודים או את כל בני הקהילה.',
				array(
					array(
						'מארגנים לפי הקשר, לא לפי סטריאוטיפ',
						array(
							'עמוד מסורת יכול לעסוק בשבת, במועד, בעונה, באירוח, במנהג של קהילה או במסלול הגירה. הוא מסביר מי נהג כך, באיזו תקופה ובאיזה מקום, ומה השתנה כאשר המנה עברה לסביבה חדשה. שמות קהילות ומקומות נכתבים כפי שהם מופיעים במקורות ובהעדפת המרואיינים.',
							'כאשר מנה משותפת לכמה קהילות, העמוד אינו מחפש בעלות בלעדית. הוא מציג מסלולים מקבילים, השפעות הדדיות והבדלים בטכניקה או בהקשר הטקסי.',
						),
					),
					array(
						'מקור כתוב וקול חי',
						array(
							'ספרי קהילה, ארכיונים, מחקר היסטורי, כתבי עת, תפריטים וחומר משפחתי יכולים להשלים זה את זה. זיכרון אישי מסומן כזיכרון, והסקה היסטורית מסומנת כהסקה. תאריך, שם ונסיבות נשמרים כדי שקורא עתידי יבין מאין הגיע הסיפור.',
							'ראיון או מתכון משפחתי מתפרסם רק בהרשאה מתאימה, ובהפרדה בין מידע שהמשפחה מבקשת לשתף לבין פרטים שנשארים פרטיים.',
						),
					),
					array(
						'מחברים למסלול קריאה',
						array(
							'כל מסורת מפנה למנות, מרכיבים ומדריכים קשורים. עמוד המנה נשאר המקום למתכון המלא; עמוד המסורת מסביר את ההקשר. חלוקת התפקידים הזאת שומרת על עומק ומונעת חזרה של אותו טקסט בכמה כתובות.',
						),
					),
				),
				array( 'שבת ומועדים — הקשר טקסי ועונתי.', 'קהילות ותפוצות — קולות ומסלולי הגירה.', 'מנות קשורות — המתכון והסיפור הקולינרי.', 'מרכיבים — שינויי מקום, עונה וזמינות.' ),
				'מסורת אינה תחליף לפסק הלכה, ייעוץ כשרות או קביעה על זהות. עמודים יבדילו בין מנהג מתועד, זיכרון אישי, פרשנות ומידע מקצועי, ויכבדו בקשות פרטיות וזכויות יוצרים.',
			),
			'en' => $c99_hub_content(
				'en',
				'Traditional food changes as families move, ingredients are replaced and new generations give a dish a different meaning. This traditions centre describes that movement carefully. It connects written records, family accounts, community practice and kitchen work without claiming that one version represents all Jews or every member of a community.',
				array(
					array(
						'Organise by context, not stereotype',
						array(
							'A tradition page may address Shabbat, a holiday, a season, hospitality, a community practice or a migration route. It explains who followed a practice, in which period and place, and what changed as the dish entered a new environment. Community and place names follow the source record and the preferences of participants.',
							'Where several communities share a dish, the page does not seek exclusive ownership. It presents parallel routes, mutual influence and differences in technique or ritual context.',
						),
					),
					array(
						'Written record and living voice',
						array(
							'Community books, archives, historical scholarship, journals, menus and family materials can complement one another. Personal memory is identified as memory, and historical inference as inference. Date, name and circumstances are retained so a future reader can understand where the account came from.',
							'An interview or family recipe is published only with appropriate permission and with a boundary between information the family wishes to share and details that remain private.',
						),
					),
					array(
						'A connected reading path',
						array(
							'Each tradition links to relevant dishes, ingredients and guides. The dish page remains the home of the full recipe; the tradition page explains context. This division creates depth without repeating the same text at several addresses.',
						),
					),
				),
				array( 'Shabbat and holidays — ritual and seasonal context.', 'Communities and diasporas — voices and migration routes.', 'Related dishes — recipe and culinary history.', 'Ingredients — changes in place, season and availability.' ),
				'Tradition is not a substitute for a religious ruling, kosher advice or a conclusion about identity. Pages distinguish documented practice, personal memory, interpretation and professional information while respecting privacy requests and copyright.',
			),
		),
	),
	array(
		'key'     => 'knowledge',
		'title'   => array( 'he' => 'מרכז הידע למזון, תפעול וצמיחה', 'en' => 'Knowledge centre for food, operations and growth' ),
		'excerpt' => array(
			'he' => 'מדריכים ממוקדי החלטה לרכש, משאבי אנוש, תפעול, מטבח, איכות ושיווק.',
			'en' => 'Decision-focused guides for procurement, human resources, operations, kitchen, quality and marketing teams.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'מרכז הידע נועד לעזור לבעלי תפקידים לקבל החלטה או לבצע תהליך, לא לייצר כמות של עמודים קצרים. כל מדריך מתחיל בשאלה אמיתית, מגדיר למי הוא נכתב, מסביר את המושגים הנחוצים ומוביל למסמך, בדיקה או פעולה שאפשר לקחת לעבודה.',
				array(
					array(
						'כוונת חיפוש אחת לכל עמוד',
						array(
							'מדריך על בחירת שירות הסעדה עונה על תהליך הבחירה; עמוד שירות מסביר את השירות; עמוד מגזר מתאר את סביבת העבודה; ועמוד הצעה מרכז פנייה. כאשר לכל כתובת יש תפקיד אחר, הקורא ומנוע החיפוש אינם צריכים לנחש איזה עמוד הוא העיקרי.',
							'לפני כתיבת מדריך חדש נבדקת מפת הבעלות על הנושא. אם עמוד קיים כבר עונה על אותה שאלה, מעדכנים ומעמיקים אותו. אם השאלה חדשה, מגדירים כתובת, קהל, מונח מרכזי, קישורים פנימיים ותנאי פרסום.',
						),
					),
					array(
						'כתיבה עם בעלים ותאריך',
						array(
							'לכל מדריך יש בעל תוכן, מקורות, תאריך בדיקה וקצב עדכון. מידע על רגולציה, בטיחות, אלרגנים, תזונה, חוזים או מערכות חיצוניות דורש גורם מקצועי מתאים. שינוי משמעותי נרשם כדי שקוראים וצוותים לא יסתמכו על גרסה ישנה.',
							'דוגמאות מוצגות כתבניות עבודה ולא כהבטחה לתוצאה. שמות לקוחות, נתוני ביצוע ומסמכים מסחריים יופיעו רק לאחר אישור מפורש.',
						),
					),
					array(
						'קישורים שמלמדים את המבנה',
						array(
							'כל מדריך מקשר למרכז הנושא שלו, לעמודים המשלימים ולפעולה הבאה. פירורי לחם מציגים את המסלול מן הבית אל המרכז ואל המדריך. רשימות תגיות אוטומטיות אינן נכנסות למפת האתר במקום דפי מרכז ערוכים.',
						),
					),
				),
				array( 'רכש והתקשרות — דרישות, מסמכים ובחירה.', 'תפעול וסניפים — תפקידים, בקרה וחריגים.', 'מטבח ומזון — מתכונים, מרכיבים ובטיחות.', 'אנשים ושירות — קליטה, הדרכה ומשוב.', 'מותג וצמיחה — בריף, תוכן, פניות ומדידה.' ),
				'מרכז הידע אינו מייצר עמודים אוטומטיים רק כדי לכסות ביטוי חיפוש. עמוד חדש נכנס לאינדקס רק כאשר הוא מקורי, מהותי, מחובר להיררכיה, עבר עריכה ויש לו בעלים שמתחייב לעדכן אותו.',
			),
			'en' => $c99_hub_content(
				'en',
				'The knowledge centre helps a responsible role make a decision or complete a process; it is not a factory for short pages. Every guide begins with a real question, identifies its audience, explains the required concepts and leads to a record, review or action that can be used at work.',
				array(
					array(
						'One search purpose for each page',
						array(
							'A guide to choosing foodservice owns the selection process; a service page explains the service; an industry page describes the operating setting; and the proposal page handles an enquiry. When every address has a distinct role, readers and search engines can identify the primary answer.',
							'Before a new guide is written, the topic ownership map is checked. If an existing page already answers the question, that page is improved. If the question is distinct, the team defines its address, audience, primary topic, internal links and publication conditions.',
						),
					),
					array(
						'Writing with an owner and review date',
						array(
							'Every guide has a content owner, sources, review date and update rhythm. Regulation, safety, allergens, nutrition, contracts and external systems require the appropriate professional reviewer. Material changes are recorded so readers and teams do not rely on an obsolete edition.',
							'Examples are presented as working templates rather than guaranteed outcomes. Client names, performance data and commercial documents appear only with explicit permission.',
						),
					),
					array(
						'Links that teach the structure',
						array(
							'Every guide links to its subject hub, supporting pages and a useful next action. Breadcrumbs show the route from home to the hub and guide. Automated tag lists do not enter the sitemap in place of edited hubs.',
						),
					),
				),
				array( 'Procurement and engagement — requirements, records and selection.', 'Operations and locations — roles, controls and exceptions.', 'Kitchen and food — recipes, ingredients and safety.', 'People and service — onboarding, training and feedback.', 'Brand and growth — briefs, content, enquiries and measurement.' ),
				'The knowledge centre does not create automated pages merely to cover a query. A new page can enter the index only when it is original, substantive, connected to the hierarchy, edited and assigned to an owner who will maintain it.',
			),
		),
	),
	array(
		'key'     => 'store',
		'title'   => array( 'he' => 'חנות קומפלט 99 — מבנה קטלוג עתידי', 'en' => 'Complete99 store — future catalogue structure' ),
		'excerpt' => array(
			'he' => 'הקטגוריות והאחריות הדרושות לחנות ציוד וחומרי מזון; הזמנות עדיין אינן פתוחות.',
			'en' => 'The categories and responsibilities required for an equipment and pantry store; ordering is not yet open.',
		),
		'content' => array(
			'he' => $c99_hub_content(
				'he',
				'קומפלט 99 מכינה תשתית לקטלוג של ציוד עבודה, כלי מטבח, גאדג׳טים, שמנים, נוזלים ומוצרי מזווה. בשלב זה אין באתר מוצרים לרכישה, סל קניות, מחירון, מלאי זמין או אפשרות תשלום. העמוד מציג את המבנה והאחריות שיידרשו לפני פתיחת מסחר אמיתי.',
				array(
					array(
						'קטגוריה שמובילה להחלטה',
						array(
							'כל קטגוריה עתידית תוגדר לפי משימת משתמש ולא רק לפי שם ספק: הכנה, בישול, אחסון, מדידה, ניקוי, בטיחות, שמנים ומזווה. מדריך בחירה יסביר מידות, חומר, שימוש, תחזוקה והתאמה לסביבת עבודה, בלי להסתיר מגבלות או להמציא השוואות.',
							'לכל מוצר יהיו שם ברור, יצרן, דגם, מזהה, תמונות מורשות, מפרט, יחידת מכירה, מצב מלאי, אחריות ומסמכי שימוש כאשר הם נדרשים. וריאציות לא ישוכפלו כעמודים מתחרים אם אפשר לנהל אותן תחת מוצר אחד.',
						),
					),
					array(
						'מסחר דורש תפעול ומשפט',
						array(
							'לפני פתיחת הזמנות יוגדרו זהות הסוחר, חשבוניות ומע״מ, אמצעי תשלום, אבטחת מידע, משלוחים, אזורי שירות, זמני טיפול, ביטולים, החזרות, אחריות, שירות לקוחות ונגישות. כל הבטחה על זמינות או מועד מסירה תגיע ממערכת מלאי ומשלוחים פעילה.',
							'מוצרי מזון ידרשו מידע על רכיבים, אלרגנים, כשרות כאשר רלוונטי, תנאי אחסון, תוקף וגורם אחראי. האתר לא יציג מוצר מזון עד שהמידע תואם את האריזה והמסמכים של הספק.',
						),
					),
					array(
						'חיבור לתוכן בלי ערבוב כוונות',
						array(
							'עמוד מידע על שמן זית יישאר בספריית המרכיבים; עמוד מסחרי עתידי יטפל ברכישה של מוצר מסוים. מדריכי בחירה יקשרו לקטגוריה, ומוצר יקשר למדריך שימוש, אך כל כתובת תשמור על תפקיד ברור.',
						),
					),
				),
				array( 'ציוד הכנה ובישול.', 'אחסון, מדידה ובטיחות.', 'כלי עבודה וגאדג׳טים.', 'שמנים, נוזלים ומוצרי מזווה.', 'מדריכי בחירה, שימוש ותחזוקה.' ),
				'החנות אינה מקבלת הזמנות ואינה מציגה מוצר, מחיר, מלאי או הבטחת משלוח. היא תיפתח רק לאחר שכל פרטי הסוחר, התשלום, המשלוח, ההחזרה, האחריות והמוצרים נבדקו והוגדרו במערכת פעילה.',
			),
			'en' => $c99_hub_content(
				'en',
				'Complete99 is preparing the foundation for a catalogue of work equipment, kitchen tools, gadgets, oils, liquids and pantry goods. There are currently no products for purchase, basket, price list, available stock or payment facility on this site. This page explains the structure and responsibilities required before real commerce opens.',
				array(
					array(
						'Categories that support a decision',
						array(
							'Each future category will be organised around a user task rather than merely a supplier name: preparation, cooking, storage, measurement, cleaning, safety, oils and pantry. Selection guidance will cover dimensions, material, use, maintenance and working-environment suitability without hiding limitations or inventing comparisons.',
							'Every product will need a clear name, manufacturer, model, identifier, licensed imagery, specification, selling unit, stock state, warranty and use records where required. Variations will not become competing pages when they can be governed under one product.',
						),
					),
					array(
						'Commerce requires operational and legal foundations',
						array(
							'Before orders open, the merchant identity, invoicing and tax handling, payment method, information security, delivery zones, handling times, cancellations, returns, warranties, customer service and accessibility will be defined. Any availability or delivery promise must come from an active inventory and delivery system.',
							'Food products will require ingredients, allergens, kosher information where relevant, storage conditions, expiry handling and an accountable supplier. A food item will not be listed until its page matches the packaging and supplier records.',
						),
					),
					array(
						'Connect commerce to knowledge without mixing purposes',
						array(
							'An educational page about olive oil remains in the ingredient library; a future commercial page would support purchase of a specific item. Selection guides can link to a category and a product can link to use guidance, while each address keeps one clear purpose.',
						),
					),
				),
				array( 'Preparation and cooking equipment.', 'Storage, measurement and safety.', 'Tools and kitchen gadgets.', 'Oils, liquids and pantry goods.', 'Selection, use and maintenance guides.' ),
				'The store does not accept orders or publish a product, price, stock state or delivery promise. It will open only after merchant, payment, delivery, returns, warranty and product information have been reviewed and configured in a functioning system.',
			),
		),
		'status'         => 'publish',
		'verification'   => 'configuration_required',
		'index_eligible' => false,
	),
);

foreach ( $hub_records as $hub ) {
	$hub['type'] = 'page';
	$hub['slug'] = array( 'he' => $hub['key'], 'en' => $hub['key'] );
	if ( ! isset( $hub['verification'] ) ) {
		$hub['verification'] = 'editorial_review';
	}
	if ( ! isset( $hub['index_eligible'] ) ) {
		$hub['index_eligible'] = true;
	}
	$records[] = $hub;
}

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
		'parent_hub'   => 'services',
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
		'parent_hub'   => 'industries',
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
		'parent_hub'   => 'platform',
	);
}

return $records;
