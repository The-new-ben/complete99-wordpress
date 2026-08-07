<?php
/**
 * Complete99 Iraqi community, institution and compliance research module.
 *
 * This module contains private editorial research only. It creates no public
 * route, supplier, product, price observation, stock record, order, payment or
 * import path. Iraqi Jewish foodways remain an important bounded layer within
 * a plural Iraqi account and never replace other community or regional voices.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $c99_iraqi_build ) || ! is_callable( $c99_iraqi_build ) ) {
	throw new RuntimeException( 'Iraqi community module requires the Iraqi entity builder.' );
}
if ( ! isset( $c99_iraqi_sources ) || ! is_array( $c99_iraqi_sources ) ) {
	throw new RuntimeException( 'Iraqi community module requires the Iraqi source registry.' );
}

$c99_iraqi_community_sources = array(
	'iraqi-community-nli-eli-timan-collection' => array(
		'type' => 'official_organization',
		'publisher' => 'The National Library of Israel',
		'title' => 'Series 03: The Eli Timan Collection',
		'url' => 'https://www.nli.org.il/en/archives/NNL_ARCHIVE_AL997013616981905171/NLI',
		'published_at' => '2006-01-01',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-nli-harisa-sawdayee' => array(
		'type' => 'official_organization',
		'publisher' => 'The National Library of Israel',
		'title' => 'Harisa oral-history item narrated by Eli Sawdayee',
		'url' => 'https://www.nli.org.il/he/archives/NNL_ARCHIVE_AL997013718757605171/NLI',
		'published_at' => '2006-01-01',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-bjhc-center' => array(
		'type' => 'official_organization',
		'publisher' => 'Babylonian Jewry Heritage Center',
		'title' => 'The Center',
		'url' => 'https://www.bjhcenglish.com/the-center',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-nara-archive' => array(
		'type' => 'official_government',
		'publisher' => 'United States National Archives and Records Administration',
		'title' => 'Preserving the Iraqi Jewish Archive',
		'url' => 'https://www.archives.gov/exhibits/ija/home',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-ids-handbook-audio' => array(
		'type' => 'official_organization',
		'publisher' => 'Institute of Development Studies',
		'title' => 'Audio Recording Files for The Handbook of Iraqi People\'s Heritage',
		'url' => 'https://opendocs.ids.ac.uk/articles/media/Audio_Recording_Files_for_i_The_Handbook_of_Iraqi_People_s_Heritage_i_/27925995',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-kashkul-mosul-lives' => array(
		'type' => 'official_organization',
		'publisher' => 'Kashkul, American University of Iraq, Sulaimani',
		'title' => 'Mosul Lives',
		'url' => 'https://www.kashkul.com/projects/mosullives',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-uobaghdad-shorja-2025' => array(
		'type' => 'official_organization',
		'publisher' => 'University of Baghdad',
		'title' => 'Traditional markets in Baghdad and their economic and civilizational role',
		'url' => 'https://nc.uobaghdad.edu.iq/?p=131589',
		'published_at' => '2025-11-13',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-erbil-qaysariyah' => array(
		'type' => 'official_government',
		'publisher' => 'Erbil Governorate',
		'title' => 'Qaysariyah Bazaar',
		'url' => 'https://www.erbil.gov.krd/app/en/node/556',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-erbil-qaysariyah-visit-2025' => array(
		'type' => 'official_government',
		'publisher' => 'Erbil Governorate',
		'title' => 'Erbil Governor and Turkish Deputy Foreign Minister Visit Qaysari Bazaar',
		'url' => 'https://www.erbil.gov.krd/app/node/10706',
		'published_at' => '2025-04-19',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-uobasrah-shrimp-markets-2021' => array(
		'type' => 'peer_reviewed_paper',
		'publisher' => 'Mesopotamian Journal of Marine Sciences, University of Basrah',
		'title' => 'Commercial shrimp landings in the main markets of Basrah Province, Iraq',
		'url' => 'https://mjms.uobasrah.edu.iq/index.php/mms/article/view/18',
		'published_at' => '2021-01-01',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-uobasrah-fish-availability-2026' => array(
		'type' => 'peer_reviewed_paper',
		'publisher' => 'Iraqi Journal of Aquaculture, University of Basrah',
		'title' => 'Fish food security in Basrah governorate, Iraq: An assessment of fish availability and per capita supply in 2024',
		'url' => 'https://ijaqua.uobasrah.edu.iq/index.php/jaqua/article/view/823',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-bestoon-samad-2026' => array(
		'type' => 'official_business',
		'publisher' => 'Bestoon Samad Restaurant',
		'title' => 'Authentic Iraqi food in traditional surroundings',
		'url' => 'https://www.bestoonsamadrestaurant.com/',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'iraqi-community-dubai-det-kabab-erbil-2025' => array(
		'type' => 'official_government',
		'publisher' => 'Dubai Department of Economy and Tourism',
		'title' => 'Old Dubai food tours',
		'url' => 'https://www.dubaidet.gov.ae/en/newsroom/press-releases/cultural-food-tours-2026?print=',
		'published_at' => '2025-10-22',
		'retrieved_at' => '2026-08-07',
	),
);

foreach ( $c99_iraqi_community_sources as $source_id => $source ) {
	if ( isset( $c99_iraqi_sources[ $source_id ] ) ) {
		throw new RuntimeException( 'Duplicate Iraqi community source ID: ' . $source_id );
	}
	$c99_iraqi_sources[ $source_id ] = $source;
}

$c99_iraqi_community_rows = array(
	/* Ten bounded tradition and community records. */
	array(
		'id' => 'tradition-iraqi-jewish-foodways-bounded',
		'type' => 'tradition',
		'slug' => 'iraqi-jewish-foodways-bounded',
		'parent_id' => 'hub-iraqi-jewish-foodways',
		'name_he' => 'מסורות האוכל של יהדות בבל, שכבה תחומה',
		'name_en' => 'Iraqi Jewish Foodways, a Bounded Layer',
		'summary_he' => 'מסגרת לעדויות משפחתיות, שבת, חגים, חיי קהילה והגירה של יהודי עיראק. היא שכבה מרכזית במפה הרב קהילתית, אך אינה תחליף למטבח העיראקי כולו ואינה בעלות על מנות משותפות.',
		'summary_en' => 'A frame for Iraqi Jewish household testimony, Shabbat, festivals, community life and migration. It is a major layer in the plural map, not a substitute for Iraqi cuisine as a whole and not ownership of shared dishes.',
		'region' => 'iraq-diaspora',
		'community' => 'iraqi-jewish',
		'sources' => array( 'iraqi-community-nli-eli-timan-collection', 'iraqi-community-bjhc-center', 'iraq-handbook-peoples-heritage-2024' ),
		'fact_he' => 'אוסף אלי תימן כולל ראיונות היסטוריה שבעל פה עם יהודי עיראק שהוקלטו בערבית יהודית בין 2006 ל-2021 ומתייחסים לבגדאד, בצרה וערים נוספות. הרשומה שומרת כל עדות בהיקף שהמרואיין והארכיון תומכים בו.',
		'fact_en' => 'The Eli Timan Collection contains oral-history interviews with Iraqi Jews recorded in Judaeo-Arabic between 2006 and 2021 and covering Baghdad, Basra and other cities. Each testimony remains within the scope supported by the interviewee and archive.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-baghdad', 'region-iraq-basra-shatt-al-arab' ),
		'compliance' => array(
			array( 'archive-rights', 'אין להעתיק הקלטה, תמונה, תמליל או מסמך ארכיוני ללא בדיקת תנאי שימוש, קרדיט והסכמה החלים על הפריט.', 'Do not copy a recording, image, transcript or archival document without reviewing item-level terms, credit and applicable consent.', array( 'iraqi-community-nli-eli-timan-collection', 'iraqi-community-bjhc-center' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'גבולות ארכיון למסורות האוכל של יהדות בבל',
		'keyword_en' => 'Iraqi Jewish foodways archive boundary',
		'prompt_en' => 'Original editorial still life of an Iraqi Jewish family food archive with a covered rice pot, cooked kubbeh soup, date pastries and blank recipe cards in separate zones, warm documentary light, no people, symbols, logos or copied archival material.',
	),
	array(
		'id' => 'tradition-iraqi-jewish-harisa-sawdayee-oral-history',
		'type' => 'tradition',
		'slug' => 'iraqi-jewish-harisa-sawdayee-oral-history',
		'parent_id' => 'hub-iraqi-jewish-foodways',
		'name_he' => 'הריסה בעדות אלי סוודאיי',
		'name_en' => 'Harisa in Eli Sawdayee Oral History',
		'summary_he' => 'רשומת עדות מזוהה על הריסה במסורת יהודית עיראקית. היא מתעדת זיכרון ושפה של אדם מסוים ואינה קובעת נוסחה אחידה או בעלות יהודית על משפחת ההריסה הרחבה.',
		'summary_en' => 'A named oral-history record of harisa in an Iraqi Jewish context. It documents one person\'s memory and language and does not define one formula or Jewish ownership of the wider harees family.',
		'region' => 'iraq-baghdad-diaspora',
		'community' => 'iraqi-jewish-named-testimony',
		'sources' => array( 'iraqi-community-nli-harisa-sawdayee', 'iraqi-community-nli-eli-timan-collection' ),
		'fact_he' => 'הספרייה הלאומית מקטלגת את הפריט כעדות הריסה מפי אלי סוודאיי במסגרת אוסף אלי תימן. הרשומה אינה מסיקה שכיחות קהילתית או הוראת בישול שלא נמסרו במטא דאטה.',
		'fact_en' => 'The National Library catalogs the item as a harisa account narrated by Eli Sawdayee within the Eli Timan Collection. The record does not infer community prevalence or cooking instructions absent from the metadata.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-baghdad' ),
		'compliance' => array(
			array( 'named-testimony-scope', 'יש לשמור את שם המספר, הקשר האוסף ותנאי השימוש. אין להפוך עדות אישית לטענת מקור או למוצר מסחרי.', 'Retain the narrator, collection context and terms of use. Do not turn a personal account into an origin verdict or commercial product.', array( 'iraqi-community-nli-harisa-sawdayee' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'הריסה יהודית עיראקית אלי סוודאיי',
		'keyword_en' => 'Eli Sawdayee Iraqi Jewish harisa oral history',
		'prompt_en' => 'Original documentary food study of fully cooked wheat harisa in a simple bowl beside an unmarked audio reel and blank catalog card, soft archive-room light, no copied document, portrait, religious symbol or legible text.',
	),
	array(
		'id' => 'tradition-iraqi-assyrian-household-foodways',
		'type' => 'tradition',
		'slug' => 'iraqi-assyrian-household-foodways',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'מסורות אוכל אשוריות בעיראק',
		'name_en' => 'Assyrian Foodways in Iraq',
		'summary_he' => 'מסגרת לעדויות אשוריות מזוהות על פאצ\'ה, קלייצ\'ה, דיח\'ווה, ג\'מיד ולחם פאלו, תוך שמירת ההבדלים בין משפחה, כפר, צום וחג.',
		'summary_en' => 'A frame for named Assyrian accounts of pacha, kleicha, dikhwah, jameed and palo bread while preserving differences among household, village, fast and feast contexts.',
		'region' => 'iraq-ninewa-dohuk-diaspora',
		'community' => 'iraqi-assyrian',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024' ),
		'fact_he' => 'המדריך מתעד עדויות נפרדות של מלקו אודשו, מרגרט אנוויה, כאמל ניסן ותרזה יונאן. הרשומה אינה מאחדת את גרסאותיהם ואינה מגדירה אותן כמטבח נוצרי כללי.',
		'fact_en' => 'The handbook records separate accounts from Malko Odesho, Margaret Enwiya, Kamel Nisan and Teresa Younan. The record neither merges their versions nor labels them as one general Christian cuisine.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-mosul-ninewa', 'region-iraq-kurdistan' ),
		'compliance' => array(
			array( 'community-review', 'מסחור של מאכל חג, צום או זיכרון מחייב בדיקת הקשר וזכויות עם נציגי הקהילה, בנוסף לבדיקת בטיחות מזון.', 'Commercial use of a feast, fast or memory food requires context and rights review with community representatives in addition to food-safety review.', array( 'iraq-handbook-peoples-heritage-2024' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'מסורות אוכל אשוריות בעיראק',
		'keyword_en' => 'Assyrian foodways in Iraq',
		'prompt_en' => 'Original Assyrian Iraqi foodways atlas with fully cooked pacha, kleicha, barley yogurt stew and a separate festival bread on plain ceramic settings, documentary studio light, no people, costumes, symbols or origin claims.',
	),
	array(
		'id' => 'tradition-iraqi-chaldean-household-foodways',
		'type' => 'tradition',
		'slug' => 'iraqi-chaldean-household-foodways',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'מסורות אוכל כלדיות בעיראק',
		'name_en' => 'Chaldean Foodways in Iraq',
		'summary_he' => 'מסגרת לעדויות כלדיות על קוטלה דוכי, ריזה ביקוזי, טביתי, פאצ\'ה וקלייצ\'ה, בלי למזג זהות כלדית עם זהות אשורית או עם כלל נוצרי אחיד.',
		'summary_en' => 'A frame for Chaldean accounts of kotle doki, riza bikoozi, tabiti, pacha and kleicha without merging Chaldean identity into Assyrian identity or one uniform Christian category.',
		'region' => 'iraq-dohuk-baghdad-diaspora',
		'community' => 'iraqi-chaldean',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024' ),
		'fact_he' => 'המדריך מתעד עדויות נפרדות של בסמה אל-ספאר, מריה אישו מתי ועאידה יצחק מיכו. כל מאכל נשאר קשור למספרת ולהקשר שהעדות מספקת.',
		'fact_en' => 'The handbook records separate accounts from Basma Al-Saffar, Maria Isho Meti and Aida Ishaq Mikho. Each food remains attached to the narrator and context supplied by the testimony.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-kurdistan', 'region-iraq-baghdad' ),
		'compliance' => array(
			array( 'offal-safety-boundary', 'תיעוד טביתי ופאצ\'ה אינו הוראת הכנה. שימוש תפעולי דורש חומר גלם מפוקח, שרשרת קירור, ניקוי, בישול מאומת ומניעת זיהום צולב.', 'Documentation of tabiti and pacha is not a preparation instruction. Operational use requires inspected material, cold chain, cleaning, validated cooking and cross-contamination controls.', array( 'iraq-handbook-peoples-heritage-2024' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'מסורות אוכל כלדיות בעיראק',
		'keyword_en' => 'Chaldean foodways in Iraq',
		'prompt_en' => 'Original Chaldean Iraqi household-foodways study with cooked yogurt dumplings, herb rice, a closed stuffed-dish serving vessel and date cookies in separate zones, no people, church symbols, labels or copied heritage images.',
	),
	array(
		'id' => 'tradition-iraqi-mandaean-ritual-foodways',
		'type' => 'tradition',
		'slug' => 'iraqi-mandaean-ritual-foodways',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'מסורות אוכל מנדעיות בעיראק',
		'name_en' => 'Mandaean Foodways in Iraq',
		'summary_he' => 'מסגרת תחומה ללוואני, דק אבו אל-פל, מסמוטה ולחם טבק לפי עדויות מנדעיות מזוהות, עם הפרדה בין תיאור טקסי לבין הוראת מזון לציבור.',
		'summary_en' => 'A bounded frame for luvani, dak Abu al-Fal, masmouta and tabak bread from named Mandaean accounts, separating ritual description from public food instruction.',
		'region' => 'iraq-baghdad-south-diaspora',
		'community' => 'iraqi-mandaean',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024' ),
		'fact_he' => 'איהאב ג\'בורי ג\'שאלי תיאר את לוואני בהקשר דתי, וג\'מילה עלי חאוזי ג\'יאד תיארה מאכלים נוספים. השמות והרכיבים נשמרים לפי העדות בלי להמציא נוסחה אחידה.',
		'fact_en' => 'Ihab Jabbouri Jashali described luvani in a religious context, and Jamila Ali Hawzi Jiyad described additional foods. Names and components remain within the testimony without inventing one uniform formula.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-baghdad', 'region-iraq-marshes-south' ),
		'compliance' => array(
			array( 'ritual-water-not-food-instruction', 'אזכור מי נהר הוא פרט מורשת טקסי בלבד. אין להשתמש במים לא מטופלים בהכנת מזון לציבור.', 'River water is documented only as ritual heritage. Untreated water must not be used in public food preparation.', array( 'iraq-handbook-peoples-heritage-2024' ) ),
			array( 'dried-fish-validation', 'מסמוטה נשארת רשומת מחקר עד לאימות מלח, פעילות מים, חמצון, היסטמין, מיקרוביולוגיה, אריזה ואחסון.', 'Masmouta remains a research record until salt, water activity, oxidation, histamine, microbiology, packaging and storage are validated.', array( 'iraq-handbook-peoples-heritage-2024' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'מסורות אוכל מנדעיות בעיראק',
		'keyword_en' => 'Mandaean foodways in Iraq',
		'prompt_en' => 'Original Mandaean Iraqi foodways still life with pomegranate, quince, dates, walnuts, almonds, cooked fish and rice-flour bread kept in distinct neutral settings, no river-water use, people, ritual objects, symbols or text.',
	),
	array(
		'id' => 'tradition-iraqi-yazidi-festival-foodways',
		'type' => 'tradition',
		'slug' => 'iraqi-yazidi-festival-foodways',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'מסורות אוכל יזידיות בעיראק',
		'name_en' => 'Yazidi Foodways in Iraq',
		'summary_he' => 'מסגרת לעדויות מבעשיקה ובהזאני על קובה סומאק, לחם עג\'ווה לחג, אל-סמאט ולחם סה ואק, תוך שמירת ההבדל בין בית, חתונה, חג ומקדש.',
		'summary_en' => 'A frame for Bashiqa and Bahzani accounts of sumac kibbeh, Ajwa Eid bread, al-samat and seh wak bread while preserving differences among household, wedding, festival and shrine contexts.',
		'region' => 'iraq-ninewa-bashiqa-bahzani',
		'community' => 'iraqi-yazidi',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024' ),
		'fact_he' => 'עדויות פרחה אליאס חאג\'י, רייאת ג\'ומעה בשאר, כרים אליאס קאטו וח\'יירי קאדי מתעדות מאכלים והקשרים שונים. גרסת קובה ללא בשר בעדות אחת אינה כלל לכל היזידים.',
		'fact_en' => 'Accounts from Farha Elias Hajji, Rayat Jumaa Bashar, Karim Elias Katto and Khairy Kadi document different foods and contexts. One meatless kibbeh account is not a rule for all Yazidis.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-mosul-ninewa' ),
		'compliance' => array(
			array( 'sacred-context-review', 'מאכל הקשור לחג, מקדש או חלוקה קהילתית דורש ביקורת הקשר של נציגי הקהילה לפני מסחור או שימוש חזותי.', 'Food linked to a festival, shrine or communal distribution requires context review by community representatives before commercial or visual use.', array( 'iraq-handbook-peoples-heritage-2024' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'מסורות אוכל יזידיות בעיראק',
		'keyword_en' => 'Yazidi foodways in Iraq',
		'prompt_en' => 'Original Yazidi Iraqi festival-food study with fully cooked sumac kibbeh, round Ajwa bread, wheat-and-meat samat and egg bread in separate documentary settings, no people, shrines, religious symbols, costumes or exclusivity cues.',
	),
	array(
		'id' => 'tradition-tal-afar-turkmen-household-foodways',
		'type' => 'tradition',
		'slug' => 'tal-afar-turkmen-household-foodways',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'מסורות האוכל הטורקמניות של תל עפר',
		'name_en' => 'Tal Afar Turkmen Foodways',
		'summary_he' => 'מסגרת לעדויות מתל עפר על הכנת בורגול ביתי, לחמה בארוק או אטלי ג\'וראק, זינקל ודשלכ לצמיחת שן ראשונה.',
		'summary_en' => 'A frame for Tal Afar accounts of household bulgur, lahmeh baaruk or atli jurak, zinkel and dashlak for a first tooth.',
		'region' => 'iraq-ninewa-tal-afar',
		'community' => 'iraqi-turkmen-tal-afar',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024' ),
		'fact_he' => 'זיינב אחמד עבאס תיארה ייצור בורגול ושתי מנות, וסקינה ג\'עפר מוסטפא תיארה דשלכ. הרשומה אינה מכלילה את תל עפר לכל הטורקמנים בעיראק.',
		'fact_en' => 'Zainab Ahmed Abbas described bulgur production and two dishes, while Sakina Jaafar Mustafa described dashlak. The record does not generalize Tal Afar to every Turkmen community in Iraq.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-mosul-ninewa' ),
		'schema_type' => 'Article',
		'keyword_he' => 'מסורות אוכל טורקמניות תל עפר',
		'keyword_en' => 'Tal Afar Turkmen foodways',
		'prompt_en' => 'Original Tal Afar Turkmen household-foodways atlas with coarse bulgur, cooked meat-and-groat bread, fried zinkel and a first-tooth grain bowl arranged separately, no child, people, costumes, flags, labels or copied imagery.',
	),
	array(
		'id' => 'tradition-iraqi-kurdish-household-foodways',
		'type' => 'tradition',
		'slug' => 'iraqi-kurdish-household-foodways',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'מסורות אוכל כורדיות בעיראק',
		'name_en' => 'Kurdish Foodways in Iraq',
		'summary_he' => 'מסגרת לאוכל ביתי, אורז, דולמה, מוצרי חלב, לחמים וידע עונתי בכורדיסטן העיראקית, עם הפרדה בין אזור, ניב, משפחה, קהילה ומחקר.',
		'summary_en' => 'A frame for household food, rice, dolma, dairy, breads and seasonal knowledge in Iraqi Kurdistan, separating region, dialect, household, community and study.',
		'region' => 'iraq-kurdistan',
		'community' => 'iraqi-kurdish',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024', 'krg-official-cuisine' ),
		'fact_he' => 'המקורות תומכים בזהות כורדית אזורית מגוונת ואינם מצדיקים נוסחה כורדית אחת, בעלות בלעדית על דולמה או שיוך כל מזון בצפון לקהילה אחת.',
		'fact_en' => 'The sources support diverse regional Kurdish identities and do not justify one Kurdish formula, exclusive ownership of dolma or assignment of every northern food to one community.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-kurdistan' ),
		'compliance' => array(
			array( 'wild-plant-boundary', 'ידע על צמחי בר הוא תיעוד תרבותי בלבד ואינו היתר לליקוט, זיהוי עצמי או אכילת צמח לא מאומת.', 'Wild-plant knowledge is cultural documentation only and is not permission to forage, self-identify or consume an unverified plant.', array( 'iraq-handbook-peoples-heritage-2024' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'מסורות אוכל כורדיות בעיראק',
		'keyword_en' => 'Kurdish foodways in Iraq',
		'prompt_en' => 'Original Iraqi Kurdish household-foodways study with rice, cooked dolma, cultured dairy and regional bread in separate plain vessels, natural highland daylight, no people, costumes, flags, foraging scene or exclusive-origin claim.',
	),
	array(
		'id' => 'tradition-iraq-arbaeen-hospitality',
		'type' => 'tradition',
		'slug' => 'iraq-arbaeen-hospitality',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'אירוח ושירותי מזון בארבעין בעיראק',
		'name_en' => 'Arbaeen Hospitality and Food Service in Iraq',
		'summary_he' => 'מסורת של שירות ואירוח למבקרים לאורך נתיבי העלייה לרגל. ההקשר כולל רוב שיעי אך אונסקו מתארת השתתפות חוצת השתייכויות, ולכן אין להפוך אותו למטבח שיעי בלעדי.',
		'summary_en' => 'A tradition of service and hospitality for visitors along pilgrimage routes. The context has a Shia majority, while UNESCO describes participation across affiliations, so it is not treated as an exclusive Shia cuisine.',
		'region' => 'iraq-middle-euphrates',
		'community' => 'iraqi-arbaeen-multi-affiliation-shia-majority',
		'sources' => array( 'unesco-arbaeen-hospitality-2019' ),
		'fact_he' => 'אונסקו רשמה בשנת 2019 את מתן השירותים והאירוח במהלך ביקור הארבעין ברשימת המורשת הבלתי מוחשית. הרישום תומך במנהג האירוח, לא בתפריט אחיד ולא בזמינות מסחרית.',
		'fact_en' => 'UNESCO inscribed the provision of services and hospitality during the Arbaeen visitation in 2019. The inscription supports the hospitality practice, not one uniform menu or commercial availability.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-middle-euphrates' ),
		'compliance' => array(
			array( 'mass-feeding-safety', 'כל יישום עתידי של האכלה המונית דורש מים ראויים לשתייה, עקיבות, בקרת זמן וטמפרטורה, אלרגנים, פסולת ותכנית תגובה לאירוע.', 'Any future mass-feeding implementation requires potable water, traceability, time and temperature control, allergens, waste control and an incident-response plan.', array( 'unesco-arbaeen-hospitality-2019' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'אירוח בארבעין בעיראק',
		'keyword_en' => 'Arbaeen hospitality Iraq',
		'prompt_en' => 'Original large-scale hospitality service study with covered cooked-food vessels, potable-water stations, clean ladles and traceability cards in an empty workflow, no crowd, flags, slogans, shrine, religious symbols or operational claim.',
	),
	array(
		'id' => 'tradition-mosul-plural-community-foodways-boundary',
		'type' => 'tradition',
		'slug' => 'mosul-plural-community-foodways-boundary',
		'parent_id' => 'hub-iraqi-community-foodways',
		'name_he' => 'מוסול הרב קהילתית, גבול שיוך למאכלים',
		'name_en' => 'Plural Mosul Foodways, an Attribution Boundary',
		'summary_he' => 'מסגרת המונעת תיוג אוטומטי של קובה, יפרך, דולמה, בורמה או קלייצ\'ה כמאכל סוני, נוצרי, יהודי או טורקמני רק מפני שתועדו במוסול.',
		'summary_en' => 'A frame preventing automatic labeling of kibbeh, yaprakh, dolma, burma or kleicha as Sunni, Christian, Jewish or Turkmen solely because they were documented in Mosul.',
		'region' => 'iraq-mosul-ninewa',
		'community' => 'mosul-plural-communities',
		'sources' => array( 'uomosul-moslawi-food-heritage', 'mosul-heritage-intangible-food', 'iraqi-community-kashkul-mosul-lives' ),
		'fact_he' => 'מקורות מוסוליים מתעדים מנות אזוריות והיסטוריה עירונית רב קולית. שיוך דתי או אתני יתווסף רק כאשר עדות מזוהה תומכת בו במפורש.',
		'fact_en' => 'Mosul sources document regional dishes and a multi-voiced urban history. Religious or ethnic attribution is added only when a named account explicitly supports it.',
		'dimension' => 'structural',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-mosul-ninewa' ),
		'schema_type' => 'Article',
		'keyword_he' => 'מסורות אוכל רב קהילתיות במוסול',
		'keyword_en' => 'plural community foodways of Mosul',
		'prompt_en' => 'Original Mosul foodways archive with fully cooked kibbeh, yaprakh, dolma, burma and kleicha on separate neutral plates at equal scale, no people, labels, religious or ethnic symbols, flags or ownership cues.',
	),

	/* Five institutions and archives. */
	array(
		'id' => 'institution-babylonian-jewry-heritage-center',
		'type' => 'culinary_institution',
		'slug' => 'babylonian-jewry-heritage-center',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'המרכז למורשת יהדות בבל',
		'name_en' => 'Babylonian Jewry Heritage Center',
		'summary_he' => 'מוזיאון, מכון מחקר, ספרייה ואוספים באור יהודה המתעדים את מורשת יהדות בבל. הישות היא מקור מחקר עצמאי, לא שותף, ספק או אישור מסחרי של Complete99.',
		'summary_en' => 'A museum, research institute, library and collections center in Or Yehuda documenting Babylonian Jewry. It is an independent research source, not a Complete99 partner, supplier or commercial endorsement.',
		'region' => 'israel-or-yehuda',
		'community' => 'iraqi-jewish-diaspora',
		'sources' => array( 'iraqi-community-bjhc-center' ),
		'fact_he' => 'האתר הרשמי מציין שהמרכז נוסד בשנת 1973 וכולל מוזיאון, מכון מחקר, ספרייה, עדויות בעל פה, תצלומים, מסמכים ואוספים.',
		'fact_en' => 'The official site states that the center was founded in 1973 and includes a museum, research institute, library, oral testimony, photographs, documents and collections.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'compliance' => array(
			array( 'institution-not-partner', 'מיפוי המוסד אינו יוצר שותפות, הרשאת תמונה, הרשאת ארכיון, ספק או אישור שימוש.', 'Mapping the institution creates no partnership, image permission, archive permission, supplier or use authorization.', array( 'iraqi-community-bjhc-center' ) ),
		),
		'schema_type' => 'Organization',
		'keyword_he' => 'המרכז למורשת יהדות בבל',
		'keyword_en' => 'Babylonian Jewry Heritage Center',
		'prompt_en' => 'Original institutional archive concept with museum shelving, sealed document boxes, an audio station and blank catalog cards, clean neutral lighting, no logos, copied artifacts, people, readable documents or partnership cues.',
	),
	array(
		'id' => 'institution-nli-eli-timan-iraqi-jewish-oral-history',
		'type' => 'culinary_institution',
		'slug' => 'nli-eli-timan-iraqi-jewish-oral-history',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'אוסף אלי תימן בספרייה הלאומית',
		'name_en' => 'Eli Timan Collection at the National Library of Israel',
		'summary_he' => 'סדרת היסטוריה שבעל פה עם יהודי עיראק, בעיקר בערבית יהודית, המאפשרת מחקר של חיי קהילה, שפה, זיכרון והגירה בגבולות כל עדות.',
		'summary_en' => 'An oral-history series with Iraqi Jews, primarily in Judaeo-Arabic, supporting research into community life, language, memory and migration within each testimony\'s limits.',
		'region' => 'israel-national-library',
		'community' => 'iraqi-jewish-diaspora',
		'sources' => array( 'iraqi-community-nli-eli-timan-collection' ),
		'fact_he' => 'מטא דאטה רשמי מתאר ראיונות שנערכו בידי אלי תימן בין 2006 ל-2021 בקנדה, בריטניה וישראל, על תקופת 1914 עד 2003 ועל קהילות בבגדאד, בצרה וערים נוספות.',
		'fact_en' => 'Official metadata describes interviews conducted by Eli Timan from 2006 to 2021 in Canada, Britain and Israel, covering 1914 to 2003 and communities in Baghdad, Basra and other cities.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'compliance' => array(
			array( 'item-level-terms', 'לכל פריט עשויים להיות תנאי גישה ושימוש נפרדים. יש לבדוק קרדיט, זכויות, פרטיות והסכמה לפני העתקה או פרסום.', 'Each item may have separate access and use terms. Review credit, rights, privacy and consent before copying or publishing.', array( 'iraqi-community-nli-eli-timan-collection' ) ),
		),
		'schema_type' => 'Organization',
		'keyword_he' => 'אוסף אלי תימן יהודי עיראק',
		'keyword_en' => 'Eli Timan Iraqi Jewish oral history collection',
		'prompt_en' => 'Original oral-history archive reading room with headphones, unmarked audio carriers, blank bilingual metadata cards and sealed photo sleeves, no copied portrait, document text, logo, personal identifier or public-access claim.',
	),
	array(
		'id' => 'institution-nara-iraqi-jewish-archive',
		'type' => 'culinary_institution',
		'slug' => 'nara-iraqi-jewish-archive',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'הארכיון היהודי העיראקי ב-NARA',
		'name_en' => 'Iraqi Jewish Archive at NARA',
		'summary_he' => 'מיזם שימור, קיטלוג ודיגיטציה של ספרים ומסמכים מחיי יהדות עיראק. הוא מקור להיסטוריה קהילתית, לא בהכרח סמכות למתכון או לזכויות על תמונה.',
		'summary_en' => 'A preservation, cataloging and digitization project for books and documents from Iraqi Jewish life. It is a community-history source, not automatically a recipe authority or image-rights grant.',
		'region' => 'united-states-national-archives',
		'community' => 'iraqi-jewish-archive',
		'sources' => array( 'iraqi-community-nara-archive' ),
		'fact_he' => 'NARA מתארת יותר מ-2,700 ספרים ועשרות אלפי מסמכים שנמצאו בשנת 2003 ושומרו, קוטלגו ונסרקו עם שותפים.',
		'fact_en' => 'NARA describes more than 2,700 books and tens of thousands of documents found in 2003 and preserved, cataloged and digitized with partners.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'compliance' => array(
			array( 'archive-not-recipe-authority', 'מסמך קהילתי אינו מוכיח מתכון, שכיחות מנה או זכות שימוש חזותית. כל טענה וכל נכס דורשים מקור וזכויות ברמת הפריט.', 'A community document does not prove a recipe, dish prevalence or visual-use right. Each claim and asset requires item-level evidence and rights.', array( 'iraqi-community-nara-archive' ) ),
		),
		'schema_type' => 'Organization',
		'keyword_he' => 'הארכיון היהודי העיראקי NARA',
		'keyword_en' => 'NARA Iraqi Jewish Archive',
		'prompt_en' => 'Original conservation-lab concept with water-damaged blank book forms, archival supports, humidity tools and digitization equipment, no copied page, readable text, emblem, people or claim of artifact ownership.',
	),
	array(
		'id' => 'institution-iraqi-peoples-heritage-oral-history-project',
		'type' => 'culinary_institution',
		'slug' => 'iraqi-peoples-heritage-oral-history-project',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'מיזם מורשת עמי עיראק והקלטות העדויות',
		'name_en' => 'Iraqi People\'s Heritage Oral-History Project',
		'summary_he' => 'מיזם אקדמי רב קהילתי של IDS ואוניברסיטת דוהוק המאגד מדריך והקלטות עדות. הוא תומך במיפוי תחום של מספרים וקהילות ואינו רישיון להעתקה מסחרית.',
		'summary_en' => 'A multi-community academic project from IDS and the University of Duhok combining a handbook and testimony recordings. It supports bounded mapping of narrators and communities and is not a commercial-copying license.',
		'region' => 'iraq-multi-region',
		'community' => 'iraqi-plural-communities',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024', 'iraqi-community-ids-handbook-audio' ),
		'fact_he' => 'המיזם מציג עדויות ותכני מורשת של קהילות שונות בעיראק. כל שימוש שומר את שם המספר, הקהילה, מועד הראיון והיקף הטענה, ואינו מאחד גרסאות.',
		'fact_en' => 'The project presents testimony and heritage content from different Iraqi communities. Use retains narrator, community, interview date and claim scope and does not merge versions.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'compliance' => array(
			array( 'noncommercial-source-license', 'אין להעתיק או להתאים טקסט, הקלטה או תמונה לשימוש מסחרי בלי בדיקת רישיון, תנאי פריט והסכמה. יש לכתוב סיכום מקורי וליצור נכס מקורי.', 'Do not copy or adapt text, audio or imagery for commercial use without reviewing the license, item terms and consent. Write original summaries and create original assets.', array( 'iraq-handbook-peoples-heritage-2024', 'iraqi-community-ids-handbook-audio' ) ),
		),
		'schema_type' => 'Organization',
		'keyword_he' => 'מיזם מורשת עמי עיראק היסטוריה שבעל פה',
		'keyword_en' => 'Iraqi Peoples Heritage oral history project',
		'prompt_en' => 'Original multi-community oral-history research desk with nine separated blank folders, unmarked audio waveforms and neutral food-memory objects, no copied images, names, costumes, symbols, logos or commercial-use cue.',
	),
	array(
		'id' => 'institution-mosul-lives-oral-history-project',
		'type' => 'culinary_institution',
		'slug' => 'mosul-lives-oral-history-project',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'Mosul Lives, מיזם היסטוריה שבעל פה',
		'name_en' => 'Mosul Lives Oral-History Project',
		'summary_he' => 'מיזם בהובלת היסטוריונים צעירים מעיראק וממוסול שתיעדו ראיונות חיים עם תושבי מוסול ממגוון שכבות. הוא מקור להקשר עירוני וזיכרון, לא קטלוג מתכונים אוטומטי.',
		'summary_en' => 'A project led by emerging Iraqi and Moslawi oral historians who conducted lifetime interviews across Mosul society. It supports urban and memory context, not an automatic recipe catalog.',
		'region' => 'iraq-mosul-ninewa',
		'community' => 'mosul-plural-communities',
		'sources' => array( 'iraqi-community-kashkul-mosul-lives' ),
		'fact_he' => 'Kashkul באוניברסיטה האמריקאית בעיראק, סולימאניה, מתארת מיזם שהחל בשנת 2016 ושאת הראיונות המקוריים שלו מארחת ספריית UCLA.',
		'fact_en' => 'Kashkul at the American University of Iraq, Sulaimani describes a project initiated in 2016 whose original interviews are hosted by the UCLA Library.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'references' => array( 'region-iraq-mosul-ninewa' ),
		'compliance' => array(
			array( 'oral-history-consent', 'אין לחלץ מאדם, משפחה או קהילה טענת מזון מסחרית בלי תמליל, הקשר, תנאי שימוש והסכמה מתאימים.', 'Do not derive a commercial food claim about a person, family or community without transcript, context, applicable terms and consent.', array( 'iraqi-community-kashkul-mosul-lives' ) ),
		),
		'schema_type' => 'Organization',
		'keyword_he' => 'Mosul Lives היסטוריה שבעל פה',
		'keyword_en' => 'Mosul Lives oral history project',
		'prompt_en' => 'Original Mosul oral-history workspace with blank city-memory cards, headphones, an abstract street-grid relief and sealed interview folders, no portrait, personal data, copied archive material, logo or public-access promise.',
	),

	/* Three private market benchmarks, with no price observations. */
	array(
		'id' => 'market-al-shorja-baghdad-research-benchmark',
		'type' => 'market',
		'slug' => 'al-shorja-baghdad-research-benchmark',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'שוק א-שורג\'ה בבגדאד, Benchmark מחקרי',
		'name_en' => 'Al-Shorja Market in Baghdad, Research Benchmark',
		'summary_he' => 'שוק בגדאדי ותיק המשמש Benchmark פרטי למבנה של תבלינים, עשבים, בשמים וסחורות מסורתיות. הרשומה אינה ספק, מקור רכש או הוכחת מלאי.',
		'summary_en' => 'A long-established Baghdad market retained as a private benchmark for spices, herbs, perfumes and traditional goods. The record is not a supplier, procurement source or proof of stock.',
		'region' => 'iraq-baghdad',
		'community' => 'baghdad-multi-community-market',
		'sources' => array( 'iraqi-community-uobaghdad-shorja-2025' ),
		'fact_he' => 'סדנה של אוניברסיטת בגדאד מ-13 בנובמבר 2025 תיארה את א-שורג\'ה כשוק עממי חיוני עם תבלינים, עשבים, בשמים ומוצרי נוי. זהו Benchmark מתוארך, לא מצב תפעולי בזמן אמת.',
		'fact_en' => 'A University of Baghdad workshop dated 13 November 2025 described Al-Shorja as an active popular market with spices, herbs, perfumes and decorative goods. This is a dated benchmark, not real-time operating status.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'region-iraq-baghdad' ),
		'compliance' => array(
			array( 'market-not-supplier', 'אין לפנות לדוכן, להציג מוכר כספק, לבקש דוגמה, מחיר, משלוח או מסלול תשלום. הרשומה היא Reference בלבד.', 'Do not contact a stall, present a seller as a supplier, request a sample, price, delivery or payment route. The record is reference only.', array( 'iraqi-community-uobaghdad-shorja-2025' ) ),
		),
		'schema_type' => 'LocalBusiness',
		'keyword_he' => 'שוק א-שורג\'ה בגדאד תבלינים',
		'keyword_en' => 'Al-Shorja Baghdad spice market',
		'prompt_en' => 'Original documentary market concept inspired by a dense Baghdad spice and herb bazaar, unbranded sacks and glass jars viewed from an empty aisle, no people, logos, prices, readable signs, supplier cue or copied storefront.',
	),
	array(
		'id' => 'market-qaysariyah-erbil-research-benchmark',
		'type' => 'market',
		'slug' => 'qaysariyah-erbil-research-benchmark',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'בזאר קייסריה בארביל, Benchmark מחקרי',
		'name_en' => 'Qaysariyah Bazaar in Erbil, Research Benchmark',
		'summary_he' => 'בזאר היסטורי בארביל המתועד בידי המחוז, ובו בין השאר מוצרי חלב מקומיים לצד מלאכות ומסחר מגוון. הוא נשמר כ-Benchmark מוסדי בלבד.',
		'summary_en' => 'A historic Erbil bazaar documented by the governorate, including local dairy among a wider mix of crafts and trade. It is retained only as an institutional benchmark.',
		'region' => 'iraq-kurdistan-erbil',
		'community' => 'erbil-multi-community-market',
		'sources' => array( 'iraqi-community-erbil-qaysariyah', 'iraqi-community-erbil-qaysariyah-visit-2025' ),
		'fact_he' => 'מחוז ארביל מתאר חנויות לגבינה כורדית וליוגורט ארביל בבזאר, וביקור רשמי מ-19 באפריל 2025 מספק נקודת זמן נוספת לקיומו. תמהיל החנויות והזמינות דורשים אימות מחדש.',
		'fact_en' => 'Erbil Governorate describes Kurdish cheese and Erbil yogurt shops in the bazaar, while an official visit on 19 April 2025 supplies a second dated existence point. Current tenant mix and availability require re-verification.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'region-iraq-kurdistan' ),
		'compliance' => array(
			array( 'market-not-supplier', 'אין להפוך חנות בבזאר לספק, הצעה, מלאי או יעד רכישה. אין פנייה או עסקה על בסיס הרשומה.', 'Do not turn a bazaar shop into a supplier, offer, stock record or purchasing destination. No outreach or transaction may rely on this record.', array( 'iraqi-community-erbil-qaysariyah', 'iraqi-community-erbil-qaysariyah-visit-2025' ) ),
		),
		'schema_type' => 'LocalBusiness',
		'keyword_he' => 'בזאר קייסריה ארביל מוצרי חלב',
		'keyword_en' => 'Qaysariyah Bazaar Erbil dairy market',
		'prompt_en' => 'Original Erbil bazaar research scene with separate unbranded Kurdish cheese and yogurt counters beside craft stalls in an empty covered passage, no people, logos, prices, readable signage, flags or supplier implication.',
	),
	array(
		'id' => 'market-basra-al-ashar-fish-system-benchmark',
		'type' => 'market',
		'slug' => 'basra-al-ashar-fish-system-benchmark',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'מערכת שווקי הדגים של בצרה ואל-עשאר, Benchmark',
		'name_en' => 'Basra and Al-Ashar Fish-Market System, Benchmark',
		'summary_he' => 'Benchmark מחקרי למערכת שיווק דגים ושרימפס בבצרה, המבוסס על מחקרי אוניברסיטת בצרה. הוא אינו רשימת דוכנים, מחירון, מלאי או אישור בטיחות.',
		'summary_en' => 'A research benchmark for fish and shrimp marketing in Basra based on University of Basrah studies. It is not a stall directory, price list, stock record or safety approval.',
		'region' => 'iraq-basra-shatt-al-arab',
		'community' => 'basra-multi-community-market',
		'sources' => array( 'iraqi-community-uobasrah-shrimp-markets-2021', 'iraqi-community-uobasrah-fish-availability-2026' ),
		'fact_he' => 'מחקר מ-2021 מדד נחיתות מסחריות של שני מיני שרימפס בשווקים מרכזיים בבצרה ובאל-עשאר, ומחקר נוסף בחן נתוני זמינות דגים חודשיים משנת 2024 בעשרה מחוזות משנה. שני המקורות הם חלונות מחקר מתוארכים.',
		'fact_en' => 'A 2021 study measured commercial landings of two shrimp species in main Basra and Al-Ashar markets, while another assessed monthly 2024 fish-availability data across ten districts. Both are dated research windows.',
		'dimension' => 'institutional',
		'evidence' => 'peer_reviewed_context',
		'value_scope' => 'market_snapshot',
		'references' => array( 'region-iraq-basra-shatt-al-arab' ),
		'compliance' => array(
			array( 'fish-market-safety', 'תיעוד שוק אינו מאשר מין, טריות או בטיחות. כל שימוש עתידי דורש זיהוי מין, עקיבות, שרשרת קירור, מזהמים, פתוגנים ותהליך יבוא מאושר.', 'Market documentation does not verify species, freshness or safety. Any future use requires species identity, traceability, cold chain, contaminant, pathogen and authorized import review.', array( 'iraqi-community-uobasrah-shrimp-markets-2021', 'iraqi-community-uobasrah-fish-availability-2026' ) ),
		),
		'schema_type' => 'LocalBusiness',
		'keyword_he' => 'שווקי דגים בצרה אל-עשאר',
		'keyword_en' => 'Basra Al-Ashar fish markets',
		'prompt_en' => 'Original scientific fish-market benchmark with chilled labeled-by-shape fish and shrimp sample trays, insulated boxes and temperature instruments in an empty clean market bay, no prices, brands, people, live animals or safety-certification claim.',
	),

	/* Two dated private restaurant benchmarks. */
	array(
		'id' => 'restaurant-bestoon-samad-baghdad-benchmark',
		'type' => 'restaurant',
		'slug' => 'bestoon-samad-baghdad-benchmark',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'Bestoon Samad בבגדאד, Benchmark פרטי',
		'name_en' => 'Bestoon Samad Baghdad, Private Benchmark',
		'summary_he' => 'Benchmark מתוארך לאופן שבו עסק מציג אוכל עיראקי, תפריט רחב וסביבה מסורתית בבגדאד. זהו תיאור עצמי של העסק, לא דירוג עצמאי, שותפות או אימות איכות.',
		'summary_en' => 'A dated benchmark for how a business presents Iraqi food, a broad menu and traditional surroundings in Baghdad. It is business self-description, not an independent rating, partnership or quality verification.',
		'region' => 'iraq-baghdad',
		'community' => 'iraqi-commercial-benchmark',
		'sources' => array( 'iraqi-community-bestoon-samad-2026' ),
		'fact_he' => 'אתר העסק שנקרא ב-7 באוגוסט 2026 הציג כתובת ברחוב אל-מנסור בבגדאד וקטגוריות תפריט הכוללות בישול עיראקי, קובה עיראקית, גריל, דגים ומתוקים. שעות, תפריט ופעילות עשויים להשתנות.',
		'fact_en' => 'The business site retrieved on 7 August 2026 listed an Al-Mansur Street Baghdad address and menu categories including Iraqi cooking, Iraqi kibbeh, grills, seafood and sweets. Hours, menu and operation may change.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'region-iraq-baghdad' ),
		'compliance' => array(
			array( 'restaurant-benchmark-only', 'אין פנייה, הזמנה, תשלום, שותפות, העתקת תפריט או שימוש בתמונות העסק. הרשומה היא Benchmark פרטי מתוארך בלבד.', 'No outreach, order, payment, partnership, menu copying or use of business imagery is allowed. The record is only a dated private benchmark.', array( 'iraqi-community-bestoon-samad-2026' ) ),
		),
		'schema_type' => 'LocalBusiness',
		'keyword_he' => 'מסעדה עיראקית בגדאד Benchmark',
		'keyword_en' => 'Baghdad Iraqi restaurant benchmark',
		'prompt_en' => 'Original restaurant benchmark board with separate fully cooked Iraqi grill, kibbeh, fish and rice categories in a refined neutral dining setting, no copied plating, logo, menu text, price, people, reservation cue or endorsement.',
	),
	array(
		'id' => 'restaurant-kabab-erbil-dubai-tourism-benchmark',
		'type' => 'restaurant',
		'slug' => 'kabab-erbil-dubai-tourism-benchmark',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'Kabab Erbil בדובאי, Benchmark תיירותי פרטי',
		'name_en' => 'Kabab Erbil Dubai, Private Tourism Benchmark',
		'summary_he' => 'Benchmark מתוארך להצגת מטבח עיראקי בשוק בינלאומי, על בסיס מקור ממשלתי מדובאי. הוא אינו מלמד על מחיר, זמינות, איכות נוכחית או מסלול מסחר לישראל.',
		'summary_en' => 'A dated benchmark for presenting Iraqi cuisine in an international market, based on a Dubai government source. It establishes no price, current availability, quality or trade route to Israel.',
		'region' => 'united-arab-emirates-dubai',
		'community' => 'iraqi-kurdish-diaspora-commercial-benchmark',
		'sources' => array( 'iraqi-community-dubai-det-kabab-erbil-2025' ),
		'fact_he' => 'מחלקת הכלכלה והתיירות של דובאי זיהתה ב-22 באוקטובר 2025 את Kabab Erbil באל-מורקבאת כמסעדה עיראקית והציעה מסגוף כמנה לטעימה. זוהי נקודת Benchmark תיירותית מתוארכת.',
		'fact_en' => 'Dubai Department of Economy and Tourism identified Kabab Erbil in Al Muraqqabat as an Iraqi restaurant on 22 October 2025 and suggested masgouf as a dish to try. This is a dated tourism benchmark.',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'region-iraq-kurdistan' ),
		'compliance' => array(
			array( 'third-country-not-workaround', 'נוכחות עסק במדינה שלישית אינה יוצרת מסלול עוקף למסחר, הזמנה, תשלום או יבוא של טובין שמקורם בעיראק.', 'A business presence in a third country does not create a workaround for trade, ordering, payment or import of Iraq-origin goods.', array( 'iraqi-community-dubai-det-kabab-erbil-2025', 'govil-iraq-trade-2026' ) ),
		),
		'schema_type' => 'LocalBusiness',
		'keyword_he' => 'מסעדה עיראקית דובאי Benchmark',
		'keyword_en' => 'Dubai Iraqi restaurant benchmark',
		'prompt_en' => 'Original international Iraqi restaurant benchmark with cooked masgouf, kebab and rice presented as three neutral comparison plates, polished documentary light, no copied restaurant design, logo, menu, price, people, tourism mark or purchase cue.',
	),

	/* Central controlling trade boundary. */
	array(
		'id' => 'compliance-iraq-trade-israel-2026',
		'type' => 'compliance_rule',
		'slug' => 'iraq-trade-israel-2026',
		'parent_id' => 'cuisine-iraqi-regional',
		'name_he' => 'גבול מסחר בין ישראל לעיראק, אוגוסט 2026',
		'name_en' => 'Israel-Iraq Trade Boundary, August 2026',
		'summary_he' => 'כלל ציות פרטי וכושל סגור: כל ישות עיראקית היא מחקר או Benchmark בלבד. אין סחר ישיר או עקיף, הצעה, מלאי, ספק, הזמנה, תשלום או מסלול דרך מדינה שלישית ללא אישור רשמי כתוב ועדכני.',
		'summary_en' => 'A private fail-closed compliance rule: every Iraqi entity is research or benchmark only. No direct or indirect trade, offer, stock, supplier, order, payment or third-country route is allowed without current written official authorization.',
		'region' => 'israel-iraq-regulatory',
		'community' => 'not-applicable',
		'sources' => array( 'govil-iraq-trade-2026' ),
		'fact_he' => 'הוראת מנכ\'ל 2.4 שאותרה אוסרת סחר ישיר או עקיף עם עיראק. ההקלות הזמניות המוזכרות בנוסח המלא ובתמצית הסתיימו לכל המאוחר ב-30 ביוני 2026, ולא אותרה הארכה רשמית מאוחרת יותר עד 7 באוגוסט 2026. לכן ברירת המחדל היא איסור עד אישור כתוב ועדכני.',
		'fact_en' => 'The located Director-General Instruction 2.4 prohibits direct or indirect trade with Iraq. Temporary treatment mentioned in the full instruction and summary expired no later than 30 June 2026, and no later official extension was located by 7 August 2026. The default therefore remains prohibited until current written authorization exists.',
		'dimension' => 'economic',
		'evidence' => 'regulatory_standard',
		'value_scope' => 'category',
		'compliance' => array(
			array( 'iraq-enemy-state-trade-default', 'אין ליצור קשר עם ספק בעיראק, לבקש דוגמה, לפרסם הצעה, לקבוע מלאי, לבצע הזמנה או תשלום, או לנתב רכישה דרך צד שלישי. כל פעולה מחייבת קודם אישור רשמי כתוב ועדכני ובדיקות יבוא מזון נפרדות.', 'Do not contact an Iraq supplier, request a sample, publish an offer, set stock, place an order or payment, or route a purchase through a third party. Any action first requires current written official authorization and separate food-import review.', array( 'govil-iraq-trade-2026' ) ),
		),
		'schema_type' => 'Article',
		'keyword_he' => 'גבול מסחר ישראל עיראק 2026',
		'keyword_en' => 'Israel Iraq trade boundary 2026',
		'prompt_en' => 'Original private compliance flow graphic with every direct and indirect route between two generic warehouse nodes visibly blocked, including a blocked third-country branch, neutral monochrome design, no flags, maps, logos, products, legal seal or purchase control.',
	),

	/* Two private evidence and benchmark guides. */
	array(
		'id' => 'guide-iraqi-oral-history-source-intake',
		'type' => 'guide',
		'slug' => 'iraqi-oral-history-source-intake',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'מדריך קליטת עדויות ומקורות למסורות אוכל עיראקיות',
		'name_en' => 'Iraqi Foodways Oral-History Source Intake Guide',
		'summary_he' => 'נוהל פרטי לקליטת עדות לפי מספר, קהילה, מקום, תאריך, שפה, אירוע, גרסת מנה, זכויות והיקף טענה, בלי להפוך זיכרון יחיד לכלל קהילתי.',
		'summary_en' => 'A private intake protocol recording narrator, community, place, date, language, occasion, dish version, rights and claim scope without turning one memory into a community-wide rule.',
		'region' => 'iraq-multi-region',
		'community' => 'iraqi-plural-communities',
		'sources' => array( 'iraq-handbook-peoples-heritage-2024', 'iraqi-community-nli-eli-timan-collection', 'iraqi-community-kashkul-mosul-lives', 'iraqi-community-nara-archive' ),
		'fact_he' => 'הארכיונים מדגימים סוגי ראיה שונים: עדות אישית, מטא דאטה, מסמך היסטורי ואוסף רב קהילתי. הנוהל דורש לשמור ביניהם הבחנה, תנאי שימוש ונתיב תיקון.',
		'fact_en' => 'The archives demonstrate different evidence types: personal testimony, metadata, historical document and multi-community collection. The protocol preserves those distinctions, terms of use and a correction path.',
		'dimension' => 'structural',
		'evidence' => 'editorial_inference',
		'references' => array( 'hub-iraqi-community-foodways' ),
		'compliance' => array(
			array( 'oral-history-human-review', 'עדות אינה מאשרת את עצמה לפרסום, מסחור או פעולה. נדרשים ביקורת אנושית, זכויות, פרטיות, תרגום והקשר.', 'Testimony does not self-approve publication, commercialization or action. Human review, rights, privacy, translation and context are required.', array( 'iraq-handbook-peoples-heritage-2024', 'iraqi-community-nli-eli-timan-collection', 'iraqi-community-kashkul-mosul-lives' ) ),
		),
		'schema_type' => 'CollectionPage',
		'keyword_he' => 'קליטת עדויות אוכל עיראקי',
		'keyword_en' => 'Iraqi foodways oral history intake',
		'prompt_en' => 'Original evidence-intake workstation with separate blank fields for narrator, place, date, language, occasion, rights and claim scope, archival headphones and food-memory objects, no names, copied forms, portraits, logos or automated-approval cue.',
	),
	array(
		'id' => 'guide-iraqi-market-restaurant-benchmark-capture',
		'type' => 'guide',
		'slug' => 'iraqi-market-restaurant-benchmark-capture',
		'parent_id' => 'hub-iraqi-institutions-markets',
		'name_he' => 'מדריך תיעוד Benchmarks לשווקים, מסעדות ומחירים עיראקיים',
		'name_en' => 'Iraqi Market, Restaurant and Price Benchmark Capture Guide',
		'summary_he' => 'נוהל פרטי לתיעוד עתידי של שוק, מסעדה או מחיר עם תאריך, מקור, מטבע, יחידת אריזה, מס, מיקום, צילום מסך וגיבוב ראיה. הטרנץ\' הנוכחי אינו יוצר תצפית מחיר.',
		'summary_en' => 'A private protocol for later market, restaurant or price evidence with date, source, currency, pack unit, tax, location, screenshot and evidence digest. This tranche creates no price observation.',
		'region' => 'iraq-and-third-country-benchmarks',
		'community' => 'not-applicable',
		'sources' => array( 'iraqi-community-uobaghdad-shorja-2025', 'iraqi-community-erbil-qaysariyah', 'iraqi-community-uobasrah-fish-availability-2026', 'iraqi-community-bestoon-samad-2026', 'iraqi-community-dubai-det-kabab-erbil-2025', 'govil-iraq-trade-2026' ),
		'fact_he' => 'תצפית עתידית חייבת להיות נקודת זמן הניתנת לשחזור, לא ממוצע שוק, הצעת ספק או מסלול רכש. מחיר ללא צילום מקור, חותמת זמן, מטבע ויחידת אריזה נשאר מחוץ לרישום.',
		'fact_en' => 'A future observation must be a reproducible point in time, not a market average, supplier offer or procurement route. A price lacking source capture, timestamp, currency and pack unit remains unregistered.',
		'dimension' => 'economic',
		'evidence' => 'editorial_inference',
		'references' => array( 'hub-iraqi-institutions-markets' ),
		'compliance' => array(
			array( 'no-price-in-first-tranche', 'אין בטרנץ\' זה תצפית מחיר, הצעה או הרשאת רכישה. גם תיעוד עתידי לא יפעיל מסחר בלי אישור רשמי כתוב ועדכני.', 'This tranche contains no price observation, offer or purchasing authorization. Future documentation also cannot activate trade without current written official authorization.', array( 'govil-iraq-trade-2026' ) ),
		),
		'schema_type' => 'CollectionPage',
		'keyword_he' => 'תיעוד מחירי שוק עיראקי Benchmark',
		'keyword_en' => 'Iraqi market price benchmark capture',
		'prompt_en' => 'Original private benchmark-capture board with blank date, source, currency, pack, tax, location, screenshot-digest and recheck fields beside neutral market and restaurant thumbnails, no real price, brand, offer, cart, payment or supplier path.',
	),
);

$c99_iraqi_community_entities = array();
foreach ( $c99_iraqi_community_rows as $spec ) {
	$entity = $c99_iraqi_build( $spec );
	$entity['surface_class'] = 'editorial_draft';
	$entity['index_policy'] = 'noindex_private';
	$entity['publication']['state'] = 'private_preview';
	$entity['publication']['public_api'] = false;
	$entity['publication']['public_page'] = false;
	$entity['publication']['search_index'] = false;
	$entity['seo']['route_mode'] = 'private';
	$entity['commerce']['state'] = 'reference_only';
	$entity['commerce']['woo_product_code'] = '';
	$entity['commerce']['public_offer_allowed'] = false;
	$entity['commerce']['cross_sell_ids'] = array();
	$entity['commerce']['up_sell_ids'] = array();
	$entity['commerce']['business_model']['pricing_state'] = 'research_required';
	$entity['commerce']['business_model']['observation_entity_ids'] = array();
	$entity['trust']['substantive_updated_at'] = '2026-08-07';
	$entity['review']['reviewed_at'] = '2026-08-07';
	$c99_iraqi_community_entities[] = $entity;
}

$c99_iraqi_community_counts = array_count_values( array_column( $c99_iraqi_community_entities, 'type' ) );
$c99_iraqi_community_expected_counts = array(
	'tradition' => 10,
	'culinary_institution' => 5,
	'market' => 3,
	'restaurant' => 2,
	'compliance_rule' => 1,
	'guide' => 2,
);

if ( 23 !== count( $c99_iraqi_community_entities ) || $c99_iraqi_community_expected_counts !== $c99_iraqi_community_counts ) {
	throw new RuntimeException( 'Iraqi community module must contain exactly 23 entities with the approved type distribution.' );
}

$c99_iraqi_community_ids = array_column( $c99_iraqi_community_entities, 'id' );
if ( count( $c99_iraqi_community_ids ) !== count( array_unique( $c99_iraqi_community_ids ) ) ) {
	throw new RuntimeException( 'Duplicate Iraqi community entity ID.' );
}

$c99_iraqi_community_prompts = array();
foreach ( $c99_iraqi_community_entities as $entity ) {
	if ( empty( $entity['name']['he'] ) || empty( $entity['name']['en'] ) || empty( $entity['facts'] ) || empty( $entity['visual']['prompt_en'] ) ) {
		throw new RuntimeException( 'Incomplete bilingual Iraqi community entity: ' . $entity['id'] );
	}
	if ( isset( $c99_iraqi_community_prompts[ $entity['visual']['prompt_en'] ] ) ) {
		throw new RuntimeException( 'Duplicate Iraqi community image prompt: ' . $entity['id'] );
	}
	$c99_iraqi_community_prompts[ $entity['visual']['prompt_en'] ] = true;
	foreach ( $entity['facts'] as $fact ) {
		if ( empty( $fact['source_ids'] ) ) {
			throw new RuntimeException( 'Unbound fact in Iraqi community entity: ' . $entity['id'] );
		}
		foreach ( $fact['source_ids'] as $source_id ) {
			if ( ! isset( $c99_iraqi_sources[ $source_id ] ) ) {
				throw new RuntimeException( 'Unknown Iraqi community fact source: ' . $source_id );
			}
		}
	}
	if ( 'compliance-iraq-trade-israel-2026' !== $entity['id'] ) {
		$has_trade_rule = false;
		foreach ( $entity['relations'] as $relation ) {
			if ( 'compliance-iraq-trade-israel-2026' === $relation['target_id'] ) {
				$has_trade_rule = true;
				break;
			}
		}
		if ( ! $has_trade_rule ) {
			throw new RuntimeException( 'Iraqi community entity lacks trade control: ' . $entity['id'] );
		}
	}
}

return array(
	'schema' => 'complete99-iraqi-community-institutions-module/v1',
	'version' => 'culinary-science-2026.08.07.v18',
	'sources' => $c99_iraqi_community_sources,
	'entities' => $c99_iraqi_community_entities,
	'private_entity_ids' => $c99_iraqi_community_ids,
	'counts' => array(
		'by_type' => $c99_iraqi_community_counts,
		'total_entities' => count( $c99_iraqi_community_entities ),
	),
);
