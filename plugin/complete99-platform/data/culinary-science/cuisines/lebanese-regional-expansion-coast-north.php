<?php
/**
 * Complete99 Lebanese coastal and northern regional research expansion.
 *
 * This module contains private, source-bounded editorial records only. It
 * creates no public page, product, price, inventory, commercial relationship
 * or purchasing route. Geographic and household evidence remains within its
 * named scope. Five under-sourced records fail closed until review evidence is
 * added.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $c99_lebanese_entity ) || ! is_callable( $c99_lebanese_entity ) || ! isset( $c99_lebanese_fact ) || ! is_callable( $c99_lebanese_fact ) ) {
	throw new RuntimeException( 'Lebanese coast and north expansion requires the Lebanese entity and fact builders.' );
}
if ( ! isset( $c99_relation ) || ! is_callable( $c99_relation ) || ! isset( $c99_compliance ) || ! is_callable( $c99_compliance ) || ! isset( $c99_text ) || ! is_callable( $c99_text ) ) {
	throw new RuntimeException( 'Lebanese coast and north expansion requires the shared registry builders.' );
}
if ( ! isset( $c99_lebanese_sources ) || ! is_array( $c99_lebanese_sources ) ) {
	throw new RuntimeException( 'Lebanese coast and north expansion requires the Lebanese source registry.' );
}

$c99_lebanese_coast_sources = array(
	'lebanon-ich-register-2024' => array(
		'type' => 'official_organization', 'publisher' => 'Chamber of Commerce, Industry and Agriculture of Beirut and Mount Lebanon',
		'title' => 'Inventory of Intangible Cultural Heritage in Lebanon',
		'url' => 'https://iheritage.ccib.org.lb/wp-content/uploads/2024/02/ICHLEBANONEN.pdf', 'published_at' => '2024-02-01', 'retrieved_at' => '2026-08-07',
	),
	'unesco-tripoli-old-city-2019' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO World Heritage Centre',
		'title' => 'The ancient city of Tripoli',
		'url' => 'https://whc.unesco.org/en/tentativelists/6436/', 'published_at' => '2019-07-12', 'retrieved_at' => '2026-08-07',
	),
	'mot-dakoujeh-beit-chabeb' => array(
		'type' => 'official_government', 'publisher' => 'Lebanon Ministry of Tourism',
		'title' => 'Dakoujeh, Beit Chabeb',
		'url' => 'https://mot.gov.lb/en/dakoujeh-beit-chabeb/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'aub-beirut-cookbook-1885-chapter' => array(
		'type' => 'official_organization', 'publisher' => 'American University of Beirut Press',
		'title' => 'How to Behave: A Beirut cookbook printed in 1885',
		'url' => 'https://www.aub.edu.lb/aubpress/PDF%20Embed%20Files/In%20the%20Steps%20of%20the%20Sultan/13_How%20to%20Behave_Samir%20Seikaly.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'ilo-akkar-potato-leafy-greens' => array(
		'type' => 'official_organization', 'publisher' => 'International Labour Organization',
		'title' => 'Potatoes and leafy green vegetables value chain analysis in Akkar, Lebanon',
		'url' => 'https://www.ilo.org/publications/potatoes-and-leafy-green-vegetables-value-chain-analysis-akkar-lebanon', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'aub-khreibet-el-jundi-agrarian-transition' => array(
		'type' => 'official_organization', 'publisher' => 'American University of Beirut ScholarWorks',
		'title' => 'Agrarian transition in Khreibet El Jundi, Akkar',
		'url' => 'https://scholarworks.aub.edu.lb/items/75d34aca-eb3b-454a-8e50-2e875c0a15c6', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'aub-maingate-marshousheh-minyara' => array(
		'type' => 'official_organization', 'publisher' => 'American University of Beirut MainGate',
		'title' => 'Marshousheh family recipe from Minyara, Akkar',
		'url' => 'https://www.aub.edu.lb/maingate/Documents/mg-spring-17.pdf', 'published_at' => '2017-03-01', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-foul-b-selek' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Fava beans and chard, Foul b Selek',
		'url' => 'https://food-heritage.org/fava-beans-and-chard-foul-b-selek/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-kishk-turnovers-ras-beirut' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Kishk recipes including Ras Beirut turnovers',
		'url' => 'https://food-heritage.org/kishk-recipes/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-wheat-laban-akkar' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Wheat with laban',
		'url' => 'https://food-heritage.org/wheat-with-laban/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-omayshe-chouf' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Omayshe',
		'url' => 'https://food-heritage.org/omayshe/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-kaak-eid-chouf' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Holiday cookies from Chouf, Lebanon',
		'url' => 'https://food-heritage.org/holiday-cookies-chouf-lebanon/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-mansoufeh' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Mansoufeh from West Bekaa and Al Chouf',
		'url' => 'https://food-heritage.org/mansoufeh-a-flavorful-dish-from-west-bekaa-and-al-chouf/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-akkoub-stew' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Akkoub stew with meat',
		'url' => 'https://food-heritage.org/akkoub-stew-with-meat/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-jamaat-al-noor-minyara' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Jamaat Al Noor in Minyara, Akkar',
		'url' => 'https://food-heritage.org/jamaat-al-noor-minyara-akkar/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-akletna-community-kitchens' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Akletna community kitchens',
		'url' => 'https://food-heritage.org/case/akletna-community-kitchens/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'aub-esdu-food-heritage-foundation' => array(
		'type' => 'official_organization', 'publisher' => 'American University of Beirut, ESDU',
		'title' => 'Food Heritage Foundation',
		'url' => 'https://www.aub.edu.lb/fafs/esdu/Pages/Foodheritagefoundation.aspx', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'peck-tripoli-sweets-craft-2022' => array(
		'type' => 'official_organization', 'publisher' => 'Henry Peck',
		'title' => 'Beirut and Tripoli sweets craft research record',
		'url' => 'https://philpapers.org/rec/PECBHT', 'published_at' => '2022-01-01', 'retrieved_at' => '2026-08-07',
	),
	'lebanon-traveler-sweets-2015-2016' => array(
		'type' => 'official_organization', 'publisher' => 'Lebanon Traveler',
		'title' => 'Lebanese sweets and Tripoli craft, December 2015 to February 2016',
		'url' => 'https://crm.visit-lebanon.org/alternatedocroots/1e853a6d-ac73-42c3-84bf-9e580eb0add0-December-February2016.pdf', 'published_at' => '2015-12-01', 'retrieved_at' => '2026-08-07',
	),
	'lebanon-traveler-tripoli-breakfast-2018' => array(
		'type' => 'official_organization', 'publisher' => 'Lebanon Traveler',
		'title' => 'Tripoli breakfast guide',
		'url' => 'https://crm.visit-lebanon.org/alternatedocroots/80bdb484-6aaa-4f75-a0b7-2fe63ccc3b38-LT24.pdf', 'published_at' => '2018-01-01', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-pumpkin-jazarieh-context' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Pumpkin in Lebanese food heritage',
		'url' => 'https://food-heritage.org/pumpkin/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'aub-darb-el-karam-brochure' => array(
		'type' => 'official_organization', 'publisher' => 'American University of Beirut, ESDU',
		'title' => 'Darb El Karam food trail brochure',
		'url' => 'https://www.aub.edu.lb/fafs/esdu/PublishingImages/Pages/ongoingprojects/darb%20el%20karam_eng%20%282%29.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'menhem-trading-batroun-official' => array(
		'type' => 'official_business', 'publisher' => 'Menhem Trading',
		'title' => 'Menhem Trading official site',
		'url' => 'https://www.menhem.com/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'hello-byblos-aal-baher' => array(
		'type' => 'official_business', 'publisher' => 'Hello Byblos',
		'title' => 'Aal Baher directory record',
		'url' => 'https://hellobyblos.com/3al.baher', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'hou-tahini-water-rheology-2017' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Journal of Food Quality',
		'title' => 'Water effects on the rheology of tahini',
		'url' => 'https://doi.org/10.1155/2017/8023610', 'published_at' => '2017-01-01', 'retrieved_at' => '2026-08-07',
	),
	'bread-production-maillard-review-2024' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Maillard reaction in bread production review',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC11241233/', 'published_at' => '2024-01-01', 'retrieved_at' => '2026-08-07',
	),
	'rice-starch-chemistry-review-2025' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Rice starch structure, gelatinization and functionality review',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC11722826/', 'published_at' => '2025-01-01', 'retrieved_at' => '2026-08-07',
	),
	'origanum-syriacum-review-2022' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Origanum syriacum composition and variability review',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC9268277/', 'published_at' => '2022-01-01', 'retrieved_at' => '2026-08-07',
	),
	'labneh-ambaris-microbiota-2022' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Journal of Dairy Science',
		'title' => 'Microbiota of Lebanese Labneh Ambaris',
		'url' => 'https://www.journalofdairyscience.org/article/S0022-0302%2822%2900734-2/fulltext', 'published_at' => '2022-01-01', 'retrieved_at' => '2026-08-07',
	),
	'aub-wild-plant-collection-lebanon' => array(
		'type' => 'official_organization', 'publisher' => 'American University of Beirut ScholarWorks',
		'title' => 'Wild plant collection and sustainability in Lebanon',
		'url' => 'https://scholarworks.aub.edu.lb/server/api/core/bitstreams/ab2f827a-2bd5-42d9-8b18-afe6d39ae297/content', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'fda-seafood-safe-handling' => array(
		'type' => 'regulatory_guidance', 'publisher' => 'United States Food and Drug Administration',
		'title' => 'Selecting and serving fresh and frozen seafood safely',
		'url' => 'https://www.fda.gov/food/buy-store-serve-safe-food/selecting-and-serving-fresh-and-frozen-seafood-safely', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
);

foreach ( $c99_lebanese_coast_sources as $source_id => $source ) {
	if ( isset( $c99_lebanese_sources[ $source_id ] ) ) {
		throw new RuntimeException( 'Duplicate Lebanese coast and north source ID: ' . $source_id );
	}
}

$c99_lebanese_coast_known_sources = array_replace( $c99_lebanese_sources, $c99_lebanese_coast_sources );

$c99_lebanese_coast_boundaries = array(
	'context' => array(
		'המקורות תומכים בהקשר העריכתי המתואר, אך אינם מוכיחים שכיחות ארצית, בעלות בלעדית, מפרט מוצר, פעילות נוכחית או ערך שוק.',
		'The sources support the stated editorial context but do not prove national prevalence, exclusive ownership, a product specification, current activity or market value.',
	),
	'dish' => array(
		'המקור מזהה מנה או רצף הכנה, אך אינו מספק נוסחה מדודה, pH, בריקס, פעילות מים, עקומת חום, הרכב תזונתי או חיי מדף מאומתים.',
		'The source identifies a dish or preparation sequence but supplies no measured formula, pH, Brix, water activity, heat curve, nutritional composition or validated shelf life.',
	),
	'produce' => array(
		'המקור תומך בהקשר חקלאי או קולינרי בלבד; זן, דרגת איכות, שאריות, הרכב, מצב אצווה, מחיר וזמינות דורשים נתונים נפרדים.',
		'The source supports an agricultural or culinary context only; cultivar, grade, residues, composition, lot condition, price and availability require separate data.',
	),
	'molecule' => array(
		'הסקירה מדווחת על התרכובת בהקשר של Origanum syriacum ועל שונות לפי גאוגרפיה, עונה וחומר צמחי; אין להחיל ערך על מוצר בלי בדיקת אצווה ואין להסיק הבטחה רפואית.',
		'The review reports the compound in Origanum syriacum and variation by geography, season and plant material; no value applies to a product without lot testing and no medical promise follows.',
	),
	'reaction' => array(
		'המקור תומך במנגנון המדעי הכללי, אך פרמטרים של נוסחה, גזירה, זמן, טמפרטורה, לחות ותוצאת מרקם דורשים ניסוי במוצר בפועל.',
		'The source supports the general scientific mechanism, while formulation, shear, time, temperature, moisture and texture outcome require testing in the actual product.',
	),
	'equipment' => array(
		'המקור תומך בזהות הכלי או בתפקידו ההיסטורי, אך אינו מהווה אישור מזון, מפרט הנדסי, הוראת הפעלה או אימות בטיחות לציוד מסחרי.',
		'The source supports the tool identity or historical role but is not food-contact approval, an engineering specification, an operating instruction or commercial equipment safety validation.',
	),
	'institution' => array(
		'המקור מתאר מוסד או מסגרת במועד הפרסום או האחזור; אין בכך שותפות, סטטוס נוכחי מאומת, המלצה מסחרית או הרשאה לפנות לאנשים.',
		'The source describes an institution or framework at publication or retrieval; it creates no partnership, verified current status, commercial endorsement or permission to contact people.',
	),
	'market' => array(
		'המקור משמש נקודת ייחוס היסטורית, מוסדית או עסקית בלבד; אין ממנו מלאי, מחיר, מוכר מאומת, זמינות, קשר מסחרי או מסלול רכישה.',
		'The source is a historical, institutional or business benchmark only; it provides no inventory, price, verified seller, availability, commercial relationship or purchasing route.',
	),
	'held' => array(
		'אין במאגר הנוכחי מקור ייעודי או עדכני מספיק לזהות, לנוסחה או לסטטוס; הרשומה מוחזקת סגורה ואין לשחזר מנה, לפרסם עסק או ליצור הצעה.',
		'The current corpus lacks sufficiently dedicated or current evidence for identity, formula or status; the record is held fail closed with no dish reconstruction, business publication or offer.',
	),
);

$c99_lebanese_coast_safety_notes = array(
	'private-source-bounded-reference' => array( 'הרשומה פרטית, תחומה למקור ואינה הוראת הכנה או הצעה.', 'The record is private, source-bounded and is neither a preparation instruction nor an offer.' ),
	'legume-thermal-allergen-validation' => array( 'יש לאמת זהות קטנית, השריה, בישול, קירור, החזקה, אלרגנים ומגע צולב לפני פיתוח.', 'Verify legume identity, soaking, cooking, cooling, holding, allergens and cross-contact before development.' ),
	'gluten-dairy-fermentation-validation' => array( 'יש לאמת גלוטן וחלב, תהליך קישק, מיקרוביולוגיה, פעילות מים, קירור ומגע צולב.', 'Verify gluten and milk, kishk process, microbiology, water activity, refrigeration and cross-contact.' ),
	'rice-dairy-nut-cold-chain-validation' => array( 'יש לאמת אורז, חלב ואגוזים לפי הגרסה, חום, קירור, שרשרת קירור ומגע צולב.', 'Verify rice, milk and version-specific nuts, heat, cooling, cold chain and cross-contact.' ),
	'sesame-legume-allergen-validation' => array( 'יש לאמת שומשום וקטניות, בישול, קירור, החזקה ומגע צולב.', 'Verify sesame and legumes, cooking, cooling, holding and cross-contact.' ),
	'grain-dairy-cold-chain-validation' => array( 'יש לאמת דגן וחלב, בישול, קירור מהיר, שרשרת קירור, החזקה וחיי מדף.', 'Verify grain and milk, cooking, rapid cooling, cold chain, holding and shelf life.' ),
	'gluten-grain-thermal-validation' => array( 'יש לאמת דגן, גלוטן, נוזלים, חום, קירור ומגע צולב לפי הגרסה בפועל.', 'Verify grain, gluten, liquids, heat, cooling and cross-contact for the actual version.' ),
	'gluten-sesame-allergen-validation' => array( 'יש לאמת קמח, גלוטן, שומשום, שומן, תבלינים, חום ומגע צולב.', 'Verify flour, gluten, sesame, fat, spices, heat and cross-contact.' ),
	'wild-plant-identification-legal-collection' => array( 'נדרשים זיהוי בוטני מקצועי, מקור חוקי ובר קיימא, עקיבות וניקוי; אין כאן היתר לליקוט או זיהוי עצמי.', 'Professional botanical identification, lawful sustainable sourcing, traceability and cleaning are required; this is not permission for foraging or self-identification.' ),
	'produce-identity-handling-validation' => array( 'יש לאמת זן, דרגה, אצווה, שאריות, תנאי הובלה, שטיפה, אחסון ואלרגנים לפני שימוש.', 'Verify cultivar, grade, lot, residues, transport, washing, storage and allergens before use.' ),
	'molecule-context-no-health-claim' => array( 'זהו הקשר כימי בלבד; אין לייחס ערך לאצווה או טענה רפואית בלי בדיקה ומקור ייעודי.', 'This is chemical context only; assign no lot value or medical claim without testing and dedicated evidence.' ),
	'sesame-emulsion-allergen-validation' => array( 'יש לאמת מפרט טחינה, אלרגן שומשום, יחס מים, חומציות, גזירה, קירור ומגע צולב.', 'Verify tahini specification, sesame allergen, water ratio, acidity, shear, refrigeration and cross-contact.' ),
	'high-heat-browning-process-validation' => array( 'יש לאמת קמח, גלוטן, זמן, טמפרטורת משטח, אוורור, כוויות ותוצאת השחמה במוצר בפועל.', 'Verify flour, gluten, time, surface temperature, ventilation, burn controls and browning outcome in the actual product.' ),
	'rice-starch-time-temperature-validation' => array( 'יש לאמת זן אורז, עמילוז, יחס נוזל, חום, קירור, החזקה ומרקם לפי הנוסחה.', 'Verify rice cultivar, amylose, liquid ratio, heat, cooling, holding and texture for the formula.' ),
	'dairy-fermentation-process-validation' => array( 'תסיסה ספונטנית אינה תהליך מסחרי מאומת; נדרשים חלב מפוסטר לפי דין, תרבית, מלח, pH, פעילות מים, מיקרוביולוגיה וקירור.', 'Spontaneous fermentation is not a validated commercial process; lawful pasteurized milk, culture, salt, pH, water activity, microbiology and refrigeration controls are required.' ),
	'high-heat-equipment-control' => array( 'ציוד בחום גבוה דורש מפרט יצרן, התקנה מקצועית, אוורור, מיגון אש וכוויות, ניקוי והדרכה.', 'High-heat equipment requires manufacturer specifications, professional installation, ventilation, fire and burn protection, cleaning and training.' ),
	'historical-not-modern-foodsafe' => array( 'הכלי או השיטה היסטוריים ואינם מאושרים לשימוש מזון מודרני בלי בדיקת חומרים, ניקוי, חדירות והגירה.', 'The tool or method is historical and is not approved for modern food use without material, cleaning, porosity and migration testing.' ),
	'seafood-haccp-bone-temperature-control' => array( 'נדרשים זיהוי מין, עקיבות, שרשרת קירור, הפרדה, עצמות, טמפרטורה מאומתת וניקוי לפי תכנית בטיחות.', 'Species identity, traceability, cold chain, separation, bone control, validated temperature and sanitation are required under a safety plan.' ),
	'institution-current-status-recheck' => array( 'יש לאמת סטטוס נוכחי, סמכות, פרטי קשר והרשאת פנייה לפני כל שימוש תפעולי.', 'Verify current status, authority, contact details and permission before any operational use.' ),
	'market-current-status-recheck' => array( 'יש לאמת סטטוס, כתובת, פעילות, מוכרים ורישיונות מחדש; אין כאן תצפית מסחר פעילה.', 'Recheck status, address, activity, vendors and licences; this is not an active market observation.' ),
	'business-benchmark-current-status-recheck' => array( 'האזכור הוא אמת מידה בלבד; יש לאמת פעילות, זהות משפטית, הרשאות וכל קשר בנפרד.', 'The mention is a benchmark only; verify operation, legal identity, permissions and any relationship separately.' ),
	's9-source-fail-closed' => array( 'הישות מוחזקת סגורה בגלל מחסור במקור ייעודי או עדכני; אין לשחזר, לפרסם, להציע או להפעיל.', 'The entity is held fail closed for lack of dedicated or current evidence; do not reconstruct, publish, offer or operationalize it.' ),
	'allergen-gluten' => array( 'יש להצהיר גלוטן לפי מפרט המוצר, למנוע מגע צולב ולאמת ניקוי.', 'Declare gluten from the actual product specification, prevent cross-contact and validate cleaning.' ),
	'allergen-sesame' => array( 'יש להצהיר שומשום לפי מפרט המוצר, למנוע מגע צולב ולאמת ניקוי.', 'Declare sesame from the actual product specification, prevent cross-contact and validate cleaning.' ),
	'allergen-nuts' => array( 'יש להצהיר אגוזים לפי הגרסה והמוצר, להפריד אצוות ולמנוע מגע צולב.', 'Declare nuts for the actual version and product, segregate lots and prevent cross-contact.' ),
	'fish-food-safety' => array( 'יש לאמת מין, עקיבות, קירור, הפרדה, עצמות ובישול במסגרת תכנית בטיחות פירות ים.', 'Verify species, traceability, refrigeration, separation, bones and cooking under a seafood safety plan.' ),
	'cold-chain-control' => array( 'יש לאמת קירור, זמן מחוץ לקירור, החזקה, הובלה וחיי מדף במוצר בפועל.', 'Validate refrigeration, time out of refrigeration, holding, transport and shelf life for the actual product.' ),
	'traditional-dairy-food-safety' => array( 'תיאור מסורתי של חלב מותסס אינו תהליך בטוח מאומת; נדרשים חומר גלם, תהליך ומיקרוביולוגיה מאושרים.', 'A traditional fermented-dairy description is not a validated safe process; approved raw materials, process and microbiology controls are required.' ),
	'open-fire-safety' => array( 'כל מקור חום או להבה דורש התקנה, אוורור, מיגון, הדרכה ובקרת כוויות ואש.', 'Every heat or flame source requires installation, ventilation, guarding, training and burn and fire controls.' ),
	'wild-plant-identification' => array( 'אין לזהות או לאכול צמח בר מן התמונה; נדרשים מומחה בוטני, מקור חוקי ועקיבות.', 'Do not identify or consume a wild plant from an image; botanical expertise, lawful sourcing and traceability are required.' ),
);

$c99_lebanese_coast_standard_safety_codes = array(
	'gluten-dairy-fermentation-validation' => array( 'allergen-gluten', 'traditional-dairy-food-safety', 'cold-chain-control' ),
	'rice-dairy-nut-cold-chain-validation' => array( 'allergen-nuts', 'traditional-dairy-food-safety', 'cold-chain-control' ),
	'sesame-legume-allergen-validation' => array( 'allergen-sesame' ),
	'grain-dairy-cold-chain-validation' => array( 'allergen-gluten', 'traditional-dairy-food-safety', 'cold-chain-control' ),
	'gluten-grain-thermal-validation' => array( 'allergen-gluten' ),
	'gluten-sesame-allergen-validation' => array( 'allergen-gluten', 'allergen-sesame' ),
	'wild-plant-identification-legal-collection' => array( 'wild-plant-identification' ),
	'sesame-emulsion-allergen-validation' => array( 'allergen-sesame', 'cold-chain-control' ),
	'high-heat-browning-process-validation' => array( 'allergen-gluten', 'open-fire-safety' ),
	'rice-starch-time-temperature-validation' => array( 'cold-chain-control' ),
	'dairy-fermentation-process-validation' => array( 'traditional-dairy-food-safety', 'cold-chain-control' ),
	'high-heat-equipment-control' => array( 'open-fire-safety' ),
	'seafood-haccp-bone-temperature-control' => array( 'fish-food-safety', 'cold-chain-control' ),
);

$c99_lebanese_coast_row = static function ( $id, $type, $parent_id, $region, $community, $name_he, $name_en, $sources, $summary_he, $summary_en, $boundary, $safety_code, $intent, $references, $visual_focus, $evidence = 'official_source', $safety_sources = array() ) {
	$evidence_aliases = array(
		'academic_source' => 'official_source',
		'editorial_archive' => 'third_party_guide',
		'named_testimony' => 'official_source',
		'official_business_source' => 'official_source',
		'official_organization' => 'official_source',
		'peer_reviewed' => 'peer_reviewed_context',
		'pending_evidence' => 'editorial_inference',
		'regulatory_guidance' => 'regulatory_standard',
	);
	if ( isset( $evidence_aliases[ $evidence ] ) ) {
		$evidence = $evidence_aliases[ $evidence ];
	}
	return array(
		'id' => $id, 'type' => $type, 'parent_id' => $parent_id, 'region' => $region, 'community' => $community,
		'name_he' => $name_he, 'name_en' => $name_en, 'sources' => $sources, 'summary_he' => $summary_he, 'summary_en' => $summary_en,
		'boundary' => $boundary, 'safety_code' => $safety_code, 'intent' => $intent, 'references' => $references,
		'visual_focus' => $visual_focus, 'evidence' => $evidence, 'safety_sources' => empty( $safety_sources ) ? $sources : $safety_sources,
	);
};

$c99_lebanese_coast_build = static function ( $spec ) use ( $c99_lebanese_entity, $c99_lebanese_fact, $c99_relation, $c99_compliance, $c99_text, $c99_lebanese_coast_boundaries, $c99_lebanese_coast_safety_notes, $c99_lebanese_coast_standard_safety_codes ) {
	if ( ! isset( $c99_lebanese_coast_boundaries[ $spec['boundary'] ] ) || ! isset( $c99_lebanese_coast_safety_notes[ $spec['safety_code'] ] ) ) {
		throw new RuntimeException( 'Unknown Lebanese coast boundary or safety code: ' . $spec['id'] );
	}
	$boundary = $c99_lebanese_coast_boundaries[ $spec['boundary'] ];
	$safety = $c99_lebanese_coast_safety_notes[ $spec['safety_code'] ];
	$relations = array(
		$c99_relation( 'part_of', $spec['parent_id'], 'הישות נשמרת תחת ההקשר האזורי או הנושאי המזוהה שלה בלבד.', 'The entity remains only under its identified regional or thematic context.', false, $spec['sources'], $spec['evidence'] ),
	);
	foreach ( $spec['references'] as $target_id ) {
		$relations[] = $c99_relation( 'references', $target_id, 'הקישור מציג הקשר מחקרי ושומר על זהויות נפרדות בלי לטעון למיזוג או למקור בלעדי.', 'The link records research context and preserves separate identities without claiming a merge or exclusive origin.', false, $spec['sources'], 'editorial_inference' );
	}
	$relations[] = $c99_relation(
		'references',
		'compliance-lebanon-trade-israel-2026',
		'הישות כפופה לגבול הסחר המתועד ואינה יוצרת מוצר, זמינות או מסלול רכישה.',
		'The entity is governed by the documented trade boundary and creates no product, availability or purchasing route.',
		false,
		array( 'israel-enemy-states-trade-2026' ),
		'regulatory_standard'
	);
	$facts = array(
		$c99_lebanese_fact( 'fact-' . $spec['id'] . '-documented', 'cultural', $spec['summary_he'], $spec['summary_en'], $spec['evidence'], 'entity', $spec['sources'] ),
		$c99_lebanese_fact( 'fact-' . $spec['id'] . '-science-boundary', 'scientific', $boundary[0], $boundary[1], 'editorial_inference', 'entity', $spec['sources'] ),
	);
	$compliance = array( $c99_compliance( $spec['safety_code'], $safety[0], $safety[1], $spec['safety_sources'], false ) );
	foreach ( isset( $c99_lebanese_coast_standard_safety_codes[ $spec['safety_code'] ] ) ? $c99_lebanese_coast_standard_safety_codes[ $spec['safety_code'] ] : array() as $standard_code ) {
		$standard_note = $c99_lebanese_coast_safety_notes[ $standard_code ];
		$compliance[] = $c99_compliance( $standard_code, $standard_note[0], $standard_note[1], $spec['safety_sources'], false );
	}
	$entity = $c99_lebanese_entity( array(
		'id' => $spec['id'],
		'type' => $spec['type'],
		'slug' => $spec['id'],
		'parent_id' => $spec['parent_id'],
		'name' => $c99_text( $spec['name_he'], $spec['name_en'] ),
		'summary' => $c99_text( $spec['summary_he'], $spec['summary_en'] ),
		'region' => $spec['region'],
		'community' => $spec['community'],
		'primary_intent' => $c99_text( 'להבין את ' . $spec['name_he'] . ' בתוך ההקשר המתועד והגבולות שלו.', 'Understand ' . $spec['name_en'] . ' within its documented context and boundaries.' ),
		'primary_keyword' => $c99_text( $spec['name_he'], $spec['name_en'] ),
		'intent_classes' => array( 'informational' ),
		'schema_type' => 'topic_hub' === $spec['type'] ? 'CollectionPage' : 'Article',
		'facts' => $facts,
		'relations' => $relations,
		'compliance' => $compliance,
		'attributes' => array(
			'pa_region' => array( $spec['region'] ),
			'pa_community' => array( $spec['community'] ),
		),
		'tags' => array( 'lebanese-regional-research', $spec['region'], $spec['intent'], 'private-reference-only' ),
		'prompt_en' => 'Original private editorial culinary study for ' . $spec['name_en'] . ': ' . $spec['visual_focus'] . '. Controlled studio or documentary lighting, source-neutral styling, no copied source image, no text, no logo, no package, no price, no medical claim, no unsafe service cue.',
	) );
	$entity['seo']['page_role'] = 'topic_hub' === $spec['type'] ? 'category' : 'spoke';
	$entity['seo']['route_mode'] = 'private';
	$entity['seo']['cluster_id'] = 'cluster-lebanese-regional-cuisine';
	$entity['seo']['hub_entity_id'] = 'cuisine-lebanese-regional';
	$entity['seo']['intent_classes'] = array( 'informational' );
	$entity['seo']['semantic_entity_ids'] = $spec['references'];
	$entity['commerce']['state'] = 'reference_only';
	$entity['commerce']['woo_product_code'] = '';
	$entity['commerce']['public_offer_allowed'] = false;
	$entity['commerce']['cross_sell_ids'] = array();
	$entity['commerce']['up_sell_ids'] = array();
	$entity['commerce']['business_model']['pricing_state'] = 'research_required';
	$entity['commerce']['business_model']['market_scope'] = 'global_research';
	$entity['commerce']['business_model']['observation_entity_ids'] = array();
	$entity['trust']['commercial_purpose'] = $c99_text( 'סימוכין פרטיים בלבד. אין מוצר, מחיר, מלאי, קשר מסחרי, הצעה או עמוד ציבורי.', 'Private reference only. There is no product, price, inventory, commercial relationship, offer or public page.' );
	$entity['trust']['substantive_updated_at'] = '2026-08-07';
	$entity['review']['reviewed_at'] = '2026-08-07';
	$entity['visual']['negative_prompt_en'] = 'No text, no logos, no flags, no copied source image, no health claim, no price, no package, no watermark.';
	foreach ( $entity['relations'] as $relation_offset => &$relation ) {
		$relation['id'] = 'edge-' . $entity['id'] . '-' . $relation['type'] . '-' . ( $relation_offset + 1 );
		$relation['valid_from'] = '2026-08-07';
	}
	unset( $relation );
	return $entity;
};

$c99_lebanese_coast_rows = array(
	/* Five navigation hubs. */
	$c99_lebanese_coast_row(
		'hub-beirut-urban-food-history', 'topic_hub', 'region-lebanon-beirut', 'lebanon-beirut', 'beirut-multi-community', 'ביירות: אוכל עירוני וזיכרון מודפס', 'Beirut Urban Food and Print Memory',
		array( 'aub-beirut-cookbook-1885-chapter', 'food-heritage-foul-b-selek', 'food-heritage-kishk-turnovers-ras-beirut' ),
		'שער מחקר המחבר דפוס קולינרי בביירות של 1885 עם מנות עונתיות ומתכון מזוהה מראס ביירות, בלי להפוך דוגמאות נקודתיות לתפריט עירוני אחיד.',
		'A research gateway connecting an 1885 Beirut culinary print record with seasonal dishes and a named Ras Beirut recipe without turning bounded examples into one citywide menu.',
		'context', 'private-source-bounded-reference', 'info_nav', array( 'dish-foul-bi-selek-beirut', 'dish-fatayer-kishk-ras-beirut', 'tradition-beirut-1885-cookbook-print-culture' ),
		'an archival-neutral table with a closed nineteenth-century-style cookbook form, cooked chard and fava beans, and a separate kishk turnover, with no copied page or writing'
	),
	$c99_lebanese_coast_row(
		'hub-tripoli-sweets-breakfast-souks', 'topic_hub', 'region-lebanon-north-akkar-tripoli', 'lebanon-tripoli', 'tripoli-multi-community', 'טריפולי: ממתקים, ארוחות בוקר וסוקים', 'Tripoli Sweets, Breakfast and Souks',
		array( 'unesco-tripoli-old-city-2019', 'peck-tripoli-sweets-craft-2022', 'lebanon-traveler-sweets-2015-2016', 'lebanon-traveler-tripoli-breakfast-2018' ),
		'שער מחקר המפריד בין המרקם העירוני והסוקים של טריפולי, מלאכת הממתקים ותיעוד ארוחות בוקר מתוארך, בלי לטעון שעסק מסוים פעיל כיום.',
		'A research gateway separating Tripoli\'s urban souk fabric, sweets craft and dated breakfast documentation without asserting that any named business is currently operating.',
		'context', 'private-source-bounded-reference', 'info_nav', array( 'dish-halawet-shmayseh-tripoli', 'market-tripoli-old-souks-food-context', 'tradition-tripoli-halwanji-sweets-craft' ),
		'a source-neutral Tripoli study table with a fully cooked rice sweet, separate foul and hummus bowls, spice sacks and abstract stone-arch geometry, no storefront or signage'
	),
	$c99_lebanese_coast_row(
		'hub-akkar-seasonal-grain-greens', 'topic_hub', 'region-lebanon-north-akkar-tripoli', 'lebanon-akkar', 'akkar-multi-community', 'עכאר: דגנים, ירק עונתי ומטבח קהילתי', 'Akkar Seasonal Grain, Greens and Community Kitchens',
		array( 'ilo-akkar-potato-leafy-greens', 'aub-khreibet-el-jundi-agrarian-transition', 'aub-maingate-marshousheh-minyara', 'food-heritage-wheat-laban-akkar', 'food-heritage-jamaat-al-noor-minyara' ),
		'שער מחקר המחבר חקלאות עכאר, דגנים וירק, מנות משפחתיות ומטבחים קהילתיים תוך הפרדה בין עדות, שרשרת ערך ומצב שוק נוכחי.',
		'A research gateway connecting Akkar agriculture, grains and greens, family dishes and community kitchens while separating testimony, value-chain evidence and current market status.',
		'context', 'private-source-bounded-reference', 'info_nav', array( 'dish-wheat-laban-akkar-easter', 'dish-marshousheh-minyara-family', 'institution-jamaat-al-noor-minyara-community-kitchen' ),
		'a northern rural research table with cooked wheat and yogurt, black-eyed peas, Swiss chard, potatoes and citrus kept in distinct evidence zones, no farm ownership claim'
	),
	$c99_lebanese_coast_row(
		'hub-mount-lebanon-shouf-rural-table', 'topic_hub', 'region-lebanon-mount-lebanon-shouf', 'lebanon-mount-lebanon-chouf', 'chouf-multi-community', 'הר הלבנון והשוף: שולחן כפרי ושימור ידע', 'Mount Lebanon and Chouf Rural Table',
		array( 'mot-dakoujeh-beit-chabeb', 'food-heritage-omayshe-chouf', 'food-heritage-kaak-eid-chouf', 'food-heritage-mansoufeh', 'food-heritage-akkoub-stew', 'aub-darb-el-karam-brochure' ),
		'שער מחקר למנות, כלי אחסון היסטורי, צמחי בר ושביל אוכל בשוף ובהר הלבנון, כשכל גרסה נשארת קשורה לכפר, למשפחה או למקור שמזוהים בה.',
		'A research gateway for dishes, a historical storage vessel, wild plants and a food trail in Chouf and Mount Lebanon, with every version bounded to its named village, household or source.',
		'context', 'private-source-bounded-reference', 'info_nav', array( 'dish-omayshe-chouf', 'dish-mansoufeh-chouf-west-bekaa', 'tradition-darb-el-karam-food-trail' ),
		'a Chouf research table with cooked bulgur dishes, holiday cookies, a separated historical clay vessel and a botanically neutral wild-plant specimen area'
	),
	$c99_lebanese_coast_row(
		'hub-north-coast-batroun-jbeil-foodways', 'topic_hub', 'region-lebanon-north-akkar-tripoli', 'lebanon-batroun-jbeil-coast', 'north-coast-multi-community', 'חוף הצפון: בתרון, ג׳בייל ודרכי אוכל', 'North Coast Batroun and Jbeil Foodways',
		array( 'unesco-tripoli-old-city-2019', 'menhem-trading-batroun-official', 'hello-byblos-aal-baher', 'fda-seafood-safe-handling' ),
		'שער פרטי למיפוי נקודות ייחוס של מזווה ושל פירות ים בחוף הצפון, בלי להפוך אתר עסק או מדריך מקומי לקשר מסחרי או להוכחת פעילות.',
		'A private gateway for pantry and seafood benchmarks on the north coast without turning a business site or local directory into a commercial relationship or proof of operation.',
		'context', 'private-source-bounded-reference', 'info_nav', array( 'market-menhem-trading-batroun-benchmark', 'restaurant-aal-baher-byblos-seafood-benchmark', 'equipment-seafood-calibrated-probe-thermometer' ),
		'a neutral coastal pantry and seafood research table with sealed unbranded jars, a chilled whole-fish study tray and a calibrated probe, no restaurant scene'
	),

	/* Twelve dishes. */
	$c99_lebanese_coast_row(
		'dish-foul-bi-selek-beirut', 'dish', 'hub-beirut-urban-food-history', 'lebanon-beirut', 'beirut-source-bounded', 'פול בסלק מביירות', 'Beirut Foul bi Selek',
		array( 'food-heritage-foul-b-selek' ),
		'המקור מציג מתכון ביירותי לסוף החורף המשלב פול עונתי ומנגולד, עם בישול מדורג והגשה קרה בלימון.',
		'The source presents a Beirut recipe for the end of winter combining seasonal fava beans and Swiss chard, with staged cooking and cold service with lemon.',
		'dish', 'legume-thermal-allergen-validation', 'recipe_identity', array( 'ingredient-spring-fava-bean-beirut-context', 'ingredient-swiss-chard-beirut-context', 'technique-foul-bi-selek-staged-cooking' ),
		'a fully cooked shallow bowl of glossy fava beans and finely cut Swiss chard with lemon served separately, documentary daylight and no measured recipe cues'
	),
	$c99_lebanese_coast_row(
		'dish-fatayer-kishk-ras-beirut', 'dish', 'hub-beirut-urban-food-history', 'lebanon-ras-beirut', 'ras-beirut-recipe-source', 'פטאייר קישק מראס ביירות', 'Ras Beirut Kishk Turnovers',
		array( 'food-heritage-kishk-turnovers-ras-beirut', 'food-heritage-kishk-winter' ),
		'המקור מציג מאפה בוקר דמוי סירה מראס ביירות עם מילוי קישק, תוך השארת שונות הבצק והקישק ברמת המתכון המזוהה.',
		'The source presents a boat-shaped Ras Beirut breakfast turnover with kishk filling while keeping dough and kishk variation bounded to the named recipe.',
		'dish', 'gluten-dairy-fermentation-validation', 'recipe_identity', array( 'ingredient-lebanese-kishk', 'technique-kishk-fermentation-drying-lebanon' ),
		'a single fully baked boat-shaped turnover with a restrained dry kishk filling visible beside separate flour and kishk reference dishes, no proportions'
	),
	$c99_lebanese_coast_row(
		'dish-kaak-orchali-beirut-context', 'dish', 'hub-beirut-urban-food-history', 'lebanon-beirut', 'identity-under-review', 'כעכ אורשאלי מביירות, זהות בהחזקה', 'Beirut Kaak Orchali, Identity Held',
		array( 'lebanon-ich-register-2024' ),
		'רשומת זהות פרטית למונח כעכ אורשאלי בהקשר ביירותי; המאגר הנוכחי אינו כולל מקור ייעודי מספיק למרכיבים, לצורה או לשיוך מדויק.',
		'A private identity record for the term Kaak Orchali in a Beirut context; the current corpus lacks a sufficiently dedicated source for ingredients, form or precise attribution.',
		'held', 's9-source-fail-closed', 'recipe_identity', array(),
		'an unresolved evidence board with an empty plate silhouette, sealed blank specimen sleeve and neutral bakery-grid geometry, no reconstructed pastry or ingredient clues', 'pending_evidence'
	),
	$c99_lebanese_coast_row(
		'dish-halawet-shmayseh-tripoli', 'dish', 'hub-tripoli-sweets-breakfast-souks', 'lebanon-tripoli', 'tripoli-sweets-source', 'חלאוות שמייסה מטריפולי', 'Tripoli Halawet Shmayseh',
		array( 'lebanon-traveler-sweets-2015-2016', 'peck-tripoli-sweets-craft-2022' ),
		'מקור מתוארך מתאר מתוק אורז הנוצר בחום נמוך, מעוצב לדסקיות וממולא קרם, כחלק ממלאכת הממתקים של טריפולי.',
		'A dated source describes a rice sweet formed over low heat, shaped into discs and filled with cream within Tripoli sweets craft.',
		'dish', 'rice-dairy-nut-cold-chain-validation', 'recipe_identity', array( 'technique-halawet-shmayseh-low-heat-forming', 'reaction-rice-starch-gelatinization-tripoli-sweets' ),
		'a fully cooked pale rice-sweet disc opened to show a restrained cream center, rose water and nuts shown only as separate version-specific references'
	),
	$c99_lebanese_coast_row(
		'dish-tripoli-foul-hummus-breakfast-pairing', 'dish', 'hub-tripoli-sweets-breakfast-souks', 'lebanon-tripoli', 'tripoli-urban-breakfast-context', 'צמד ארוחת בוקר של פול וחומוס מטריפולי', 'Tripoli Foul and Hummus Breakfast Pairing',
		array( 'lebanon-traveler-tripoli-breakfast-2018' ),
		'מדריך משנת 2018 מציג פול וחומוס כצמד ארוחת בוקר בטריפולי; הרשומה שומרת את ההקשר המתוארך ואינה קובעת עסק פעיל או נוסחה עירונית.',
		'A 2018 guide presents foul and hummus as a Tripoli breakfast pairing; the record preserves the dated context without asserting an active business or citywide formula.',
		'dish', 'sesame-legume-allergen-validation', 'local_discovery', array( 'restaurant-akra-tripoli-breakfast-benchmark', 'market-tripoli-old-souks-food-context' ),
		'two separate fully cooked breakfast bowls, one fava bean and one chickpea-tahini, with bread kept outside the frame and no storefront or brand cues', 'editorial_archive'
	),
	$c99_lebanese_coast_row(
		'dish-jazarieh-tripoli-context', 'dish', 'hub-tripoli-sweets-breakfast-souks', 'lebanon-tripoli', 'identity-under-review', 'ג׳זרייה מטריפולי, זהות בהחזקה', 'Tripoli Jazarieh, Identity Held',
		array( 'food-heritage-pumpkin-jazarieh-context' ),
		'מקור המורשת מספק הקשר לדלעת ולמתוק בשם ג׳זרייה, אך אינו מספיק לבדו לזהות ייצור ייעודית, מפרט מרכיבים או שחזור חזותי של גרסת טריפולי.',
		'The heritage source provides pumpkin and Jazarieh sweet context but is insufficient by itself for a dedicated production identity, ingredient specification or visual reconstruction of a Tripoli version.',
		'held', 's9-source-fail-closed', 'recipe_identity', array(),
		'an unresolved confectionery evidence board with an empty geometric sweet mould, sealed pumpkin-color swatch and blank process cards, no finished sweet', 'pending_evidence'
	),
	$c99_lebanese_coast_row(
		'dish-wheat-laban-akkar-easter', 'dish', 'hub-akkar-seasonal-grain-greens', 'lebanon-akkar', 'akkar-christian-occasion-context', 'חיטה בלבן מעכאר בהקשר הפסחא', 'Akkar Wheat with Laban in Easter Context',
		array( 'food-heritage-wheat-laban-akkar' ),
		'המקור מתעד מנה של חיטה קלופה, חומוס ויוגורט הפופולרית בעכאר ובמקומות נוספים, ומקשר הכנה לשבת שלפני הפסחא.',
		'The source documents peeled wheat, chickpeas and yogurt in a dish popular in Akkar and elsewhere, and links preparation to the Saturday before Easter.',
		'dish', 'grain-dairy-cold-chain-validation', 'recipe_identity', array( 'technique-wheat-laban-cook-cool-akkar', 'tradition-akkar-easter-wheat-laban' ),
		'a chilled finished bowl of cooked peeled wheat, chickpeas and plain yogurt on a neutral table, with a separate covered cold-storage vessel and no religious symbols'
	),
	$c99_lebanese_coast_row(
		'dish-marshousheh-minyara-family', 'dish', 'hub-akkar-seasonal-grain-greens', 'lebanon-minyara-akkar', 'minyara-family-testimony', 'מרשושה ממשפחת מיניארה', 'Minyara Family Marshousheh',
		array( 'aub-maingate-marshousheh-minyara' ),
		'פרסום AUB מתעד מתכון משפחתי ממיניארה עם לוביה, מנגולד, בורגול גס ובצל; זו עדות משפחתית ולא נוסחה לכל עכאר.',
		'An AUB publication documents a Minyara family recipe with black-eyed peas, Swiss chard, coarse bulgur and onions; it is family testimony, not a formula for all Akkar.',
		'dish', 'gluten-grain-thermal-validation', 'recipe_identity', array( 'ingredient-black-eyed-pea-minyara-context', 'technique-marshousheh-minyara-steaming' ),
		'a fully cooked rustic bowl with visible black-eyed peas, chopped Swiss chard, coarse bulgur and softened onions, family-recipe study without people or place claims', 'named_testimony'
	),
	$c99_lebanese_coast_row(
		'dish-omayshe-chouf', 'dish', 'hub-mount-lebanon-shouf-rural-table', 'lebanon-chouf', 'chouf-recipe-source', 'אומיישה מהשוף', 'Chouf Omayshe',
		array( 'food-heritage-omayshe-chouf', 'food-heritage-kishk-winter' ),
		'המקור מתעד אומיישה מן השוף סביב בורגול, קישק ובצל קלוי ומציין גרסאות, ולכן הרשומה אינה מקבעת נוסחה יחידה.',
		'The source documents Chouf Omayshe around bulgur, kishk and roasted onion and notes variants, so the record does not fix one formula.',
		'dish', 'gluten-dairy-fermentation-validation', 'recipe_identity', array( 'ingredient-lebanese-kishk', 'technique-kishk-fermentation-drying-lebanon' ),
		'a fully cooked Chouf-style bulgur and kishk bowl finished with visibly roasted onions, all components source-bounded and no standardized garnish'
	),
	$c99_lebanese_coast_row(
		'dish-mansoufeh-chouf-west-bekaa', 'dish', 'hub-mount-lebanon-shouf-rural-table', 'lebanon-chouf-west-bekaa', 'named-guesthouse-recipe', 'מנסופה מהשוף וממערב הבקאע', 'Mansoufeh from Chouf and West Bekaa',
		array( 'food-heritage-mansoufeh' ),
		'המקור ממקם מנסופה בכפרים בשוף ובמערב הבקאע ומתאר חלקים מבורגול, דלעת וקמח הנחלטים ומושלמים בבצל, שמן זית וחומציות.',
		'The source locates Mansoufeh in Chouf and West Bekaa villages and describes bulgur, pumpkin and flour pieces that are poached and finished with onion, olive oil and acidity.',
		'dish', 'gluten-grain-thermal-validation', 'recipe_identity', array( 'technique-mansoufeh-shape-poach-finish', 'tradition-darb-el-karam-food-trail' ),
		'a finished shallow bowl of flattened cooked pumpkin-bulgur pieces with softened onions and olive oil, acidity represented by a separate unlabeled small vessel'
	),
	$c99_lebanese_coast_row(
		'dish-kaak-eid-chouf', 'dish', 'hub-mount-lebanon-shouf-rural-table', 'lebanon-chouf', 'chouf-family-occasion-source', 'כעכ אלעיד מהשוף', 'Chouf Kaak el Eid',
		array( 'food-heritage-kaak-eid-chouf' ),
		'המקור מתעד מתכון משפחתי מהשוף לכעכ חג בכמויות קהילתיות בהקשר עיד אל-אדחא, עם סולת, קמח, תבלינים ושומשום.',
		'The source documents a Chouf family holiday-cookie recipe made in community-scale batches for Eid al-Adha, with semolina, flour, spices and sesame.',
		'dish', 'gluten-sesame-allergen-validation', 'recipe_identity', array( 'tradition-chouf-adha-kaak-community-baking', 'equipment-manouche-tabouneh-bakery-oven' ),
		'a small group of fully baked ring and stamped holiday cookies showing semolina texture and sesame, neutral communal-baking table with no religious symbols', 'named_testimony'
	),
	$c99_lebanese_coast_row(
		'dish-akkoub-stew-chouf-dahr-el-baydar', 'dish', 'hub-mount-lebanon-shouf-rural-table', 'lebanon-chouf-dahr-el-baydar', 'chouf-wild-plant-context', 'תבשיל עכוב מהשוף ודהר אל-ביידר', 'Akkoub Stew from Chouf and Dahr el Baydar',
		array( 'food-heritage-akkoub-stew', 'aub-wild-plant-collection-lebanon' ),
		'המקור מתעד תבשיל עכוב מן השוף ודהר אל-ביידר לאחר טיפול בקוצים, בעוד מחקר הצמחים מחייב זהירות בזיהוי ובקיימות האיסוף.',
		'The source documents Akkoub stew from Chouf and Dahr el Baydar after thorn handling, while wild-plant research requires caution about identification and collection sustainability.',
		'dish', 'wild-plant-identification-legal-collection', 'recipe_identity', array( 'ingredient-akkoub-gundelia-chouf-context', 'technique-akkoub-cleaning-cooking-chouf' ),
		'a fully cooked stew with professionally identified Gundelia portions and meat shown only as a cooked version component, plus a separate traceability tag without readable text', 'official_source', array( 'food-heritage-akkoub-stew', 'aub-wild-plant-collection-lebanon' )
	),

	/* Eight ingredients and agricultural contexts. */
	$c99_lebanese_coast_row(
		'ingredient-spring-fava-bean-beirut-context', 'ingredient', 'dish-foul-bi-selek-beirut', 'lebanon-beirut', 'beirut-source-bounded', 'פול אביבי בהקשר ביירותי', 'Spring Fava Bean in Beirut Context',
		array( 'food-heritage-foul-b-selek' ), 'המקור מזהה פול עונתי כרכיב מרכזי בפול בסלק הביירותי המתועד.', 'The source identifies seasonal fava beans as a central component of the documented Beirut Foul bi Selek.',
		'produce', 'produce-identity-handling-validation', 'professional_science', array( 'dish-foul-bi-selek-beirut' ), 'fresh fava pods, shelled beans and one cooked bean sample in three separate unbranded specimen zones'
	),
	$c99_lebanese_coast_row(
		'ingredient-swiss-chard-beirut-context', 'ingredient', 'dish-foul-bi-selek-beirut', 'lebanon-beirut', 'beirut-source-bounded', 'מנגולד בהקשר ביירותי', 'Swiss Chard in Beirut Context',
		array( 'food-heritage-foul-b-selek' ), 'המקור מזהה מנגולד כרכיב הירוק בפול בסלק הביירותי ומתאר שילובו לאחר שלב הקטנית.', 'The source identifies Swiss chard as the green component in Beirut Foul bi Selek and describes adding it after the legume stage.',
		'produce', 'produce-identity-handling-validation', 'professional_science', array( 'dish-foul-bi-selek-beirut' ), 'washed Swiss chard leaves, stems and a small fully cooked sample presented separately on a clean produce-study surface'
	),
	$c99_lebanese_coast_row(
		'ingredient-black-eyed-pea-minyara-context', 'ingredient', 'dish-marshousheh-minyara-family', 'lebanon-minyara-akkar', 'minyara-family-testimony', 'לוביה בהקשר מרשושה ממיניארה', 'Black-Eyed Pea in Minyara Marshousheh Context',
		array( 'aub-maingate-marshousheh-minyara' ), 'המתכון המשפחתי ממיניארה מזהה לוביה לצד מנגולד ובורגול גס במרשושה.', 'The Minyara family recipe identifies black-eyed peas beside Swiss chard and coarse bulgur in Marshousheh.',
		'produce', 'legume-thermal-allergen-validation', 'professional_science', array( 'dish-marshousheh-minyara-family' ), 'dry black-eyed peas, soaked specimens and a fully cooked sample in separate bowls, with no implied universal soaking time', 'named_testimony'
	),
	$c99_lebanese_coast_row(
		'ingredient-akkar-potato-value-chain-context', 'ingredient', 'hub-akkar-seasonal-grain-greens', 'lebanon-akkar', 'akkar-agricultural-context', 'תפוח אדמה בשרשרת הערך של עכאר', 'Akkar Potato Value-Chain Context',
		array( 'ilo-akkar-potato-leafy-greens' ), 'ניתוח שרשרת הערך של ILO ממפה תפוחי אדמה בעכאר כענף חקלאי, בלי להפוך את המחקר למפרט זן, מחיר או זמינות.', 'The ILO value-chain analysis maps potatoes as an Akkar agricultural sector without turning the study into a cultivar specification, price or availability claim.',
		'produce', 'produce-identity-handling-validation', 'commercial_benchmark', array( 'hub-akkar-seasonal-grain-greens' ), 'three unbranded potato lots differing visibly in size and skin condition on a neutral grading table, no farm or quality ranking'
	),
	$c99_lebanese_coast_row(
		'ingredient-akkar-leafy-greens-value-chain-context', 'ingredient', 'hub-akkar-seasonal-grain-greens', 'lebanon-akkar', 'akkar-agricultural-context', 'עלים ירוקים בשרשרת הערך של עכאר', 'Akkar Leafy Greens Value-Chain Context',
		array( 'ilo-akkar-potato-leafy-greens' ), 'ניתוח ILO ממפה ירקות עליים בשרשרת הערך של עכאר, בלי לזהות בכל מקרה מין, זן, דרגה או אצווה.', 'The ILO analysis maps leafy greens in the Akkar value chain without identifying species, cultivar, grade or lot in every case.',
		'produce', 'produce-identity-handling-validation', 'commercial_benchmark', array( 'hub-akkar-seasonal-grain-greens' ), 'assorted leafy-green specimens separated by morphology on a clean chilled grading surface, no species labels or market claim'
	),
	$c99_lebanese_coast_row(
		'ingredient-akkar-olive-fruit-context', 'ingredient', 'hub-akkar-seasonal-grain-greens', 'lebanon-akkar', 'akkar-agrarian-history-context', 'פרי זית בהקשר החקלאי של עכאר', 'Olive Fruit in Akkar Agrarian Context',
		array( 'aub-khreibet-el-jundi-agrarian-transition' ), 'מחקר חקלאי מכרייבת אל-ג׳ונדי מתעד זיתים בתוך שינויי החקלאות המקומיים, בלי לספק מפרט זן או שמן.', 'Agrarian research from Khreibet El Jundi documents olives within local agricultural change without supplying a cultivar or oil specification.',
		'produce', 'produce-identity-handling-validation', 'professional_science', array( 'ingredient-lebanese-olive-oil-context' ), 'mixed-ripeness olive fruit on branches beside a sealed blank oil sample, clearly separated to avoid implying cultivar or chemistry'
	),
	$c99_lebanese_coast_row(
		'ingredient-akkar-citrus-context', 'ingredient', 'hub-akkar-seasonal-grain-greens', 'lebanon-akkar', 'akkar-agrarian-history-context', 'הדרים בהקשר החקלאי של עכאר', 'Citrus in Akkar Agrarian Context',
		array( 'aub-khreibet-el-jundi-agrarian-transition' ), 'המחקר החקלאי מתעד הדרים בהקשר המקומי בעכאר, אך אינו מזהה לכל פרי מין, זן, דרגת איכות או הרכב.', 'The agrarian study documents citrus in a local Akkar context but does not identify species, cultivar, grade or composition for each fruit.',
		'produce', 'produce-identity-handling-validation', 'professional_science', array( 'hub-akkar-seasonal-grain-greens' ), 'several whole citrus specimens separated by size and peel morphology on an unbranded agricultural study table, no named cultivar'
	),
	$c99_lebanese_coast_row(
		'ingredient-akkoub-gundelia-chouf-context', 'ingredient', 'dish-akkoub-stew-chouf-dahr-el-baydar', 'lebanon-chouf-dahr-el-baydar', 'chouf-wild-plant-context', 'עכוב, Gundelia, בהקשר השוף', 'Akkoub, Gundelia, in Chouf Context',
		array( 'food-heritage-akkoub-stew', 'aub-wild-plant-collection-lebanon' ), 'מקורות המנה והצמחים מקשרים עכוב בהקשר השוף למין Gundelia ולידע טיפול מסורתי, לצד סיכון זיהוי ואיסוף יתר.', 'Dish and plant sources connect Akkoub in the Chouf context with Gundelia and traditional handling knowledge, alongside identification and overharvesting risks.',
		'produce', 'wild-plant-identification-legal-collection', 'professional_science', array( 'dish-akkoub-stew-chouf-dahr-el-baydar', 'technique-akkoub-cleaning-cooking-chouf' ), 'a professionally identified Gundelia specimen beside a cleaned edible portion and a closed cooked sample, no foraging scene or identification guide', 'academic_source'
	),

	/* Three volatile-compound context entities. */
	$c99_lebanese_coast_row(
		'molecule-carvacrol-origanum-syriacum-context', 'molecule', 'ingredient-lebanese-zaatar-blend', 'lebanon-multi-region', 'origanum-syriacum-research-context', 'קרבקרול בהקשר Origanum syriacum', 'Carvacrol in Origanum syriacum Context',
		array( 'origanum-syriacum-review-2022' ), 'סקירה מדעית מדווחת על קרבקרול כאחת התרכובות הבולטות בדגימות מסוימות של Origanum syriacum, עם שונות משמעותית בין דגימות ותנאים.', 'A scientific review reports carvacrol among prominent compounds in some Origanum syriacum samples, with substantial variation among samples and conditions.',
		'molecule', 'molecule-context-no-health-claim', 'professional_science', array( 'ingredient-lebanese-zaatar-blend', 'dish-al-manouche-lebanon' ), 'a molecular-context composition with fresh Origanum syriacum, a sealed colorless volatile sample and an abstract carvacrol molecular model, no dosage or health cue', 'peer_reviewed'
	),
	$c99_lebanese_coast_row(
		'molecule-thymol-origanum-syriacum-context', 'molecule', 'ingredient-lebanese-zaatar-blend', 'lebanon-multi-region', 'origanum-syriacum-research-context', 'תימול בהקשר Origanum syriacum', 'Thymol in Origanum syriacum Context',
		array( 'origanum-syriacum-review-2022' ), 'סקירה מדעית מדווחת על תימול כחלק מפרופילים נדיפים מסוימים של Origanum syriacum, בלי לקבוע ריכוז אחיד לצמח או לתערובת מסחרית.', 'A scientific review reports thymol within some Origanum syriacum volatile profiles without establishing a uniform concentration for the plant or a commercial blend.',
		'molecule', 'molecule-context-no-health-claim', 'professional_science', array( 'ingredient-lebanese-zaatar-blend', 'dish-al-manouche-lebanon' ), 'an Origanum syriacum leaf and flower study beside a sealed volatile sample and an abstract thymol molecular model, clinical-neutral light and no efficacy cue', 'peer_reviewed'
	),
	$c99_lebanese_coast_row(
		'molecule-p-cymene-origanum-syriacum-context', 'molecule', 'ingredient-lebanese-zaatar-blend', 'lebanon-multi-region', 'origanum-syriacum-research-context', 'פארא-צימן בהקשר Origanum syriacum', 'p-Cymene in Origanum syriacum Context',
		array( 'origanum-syriacum-review-2022' ), 'סקירה מדעית כוללת p-cymene בין התרכובות הנדיפות המדווחות ב-Origanum syriacum, כאשר היחסים הכימיים משתנים לפי מקור ודגימה.', 'A scientific review includes p-cymene among volatile compounds reported in Origanum syriacum, with chemical ratios varying by origin and sample.',
		'molecule', 'molecule-context-no-health-claim', 'professional_science', array( 'ingredient-lebanese-zaatar-blend', 'dish-al-manouche-lebanon' ), 'a source-neutral volatile chemistry study with dried Origanum syriacum, a sealed sample vial and an abstract p-cymene molecular model, no concentration label', 'peer_reviewed'
	),

	/* Four food-science reactions. */
	$c99_lebanese_coast_row(
		'reaction-tahini-water-hydration-phase-transition', 'reaction', 'dish-kibbeh-arnabiyyeh-beirut', 'lebanon-beirut', 'tahini-science-context', 'הידרציית טחינה ושינוי מופע', 'Tahini Hydration and Phase Transition',
		array( 'hou-tahini-water-rheology-2017' ), 'המחקר מראה שהוספת מים משנה את הריאולוגיה של טחינה ויכולה להעביר את המערכת לכיוון אמולסיה של שמן במים, בהתאם להרכב ולכמות המים.', 'The study shows that water addition changes tahini rheology and can move the system toward an oil-in-water emulsion depending on composition and water level.',
		'reaction', 'sesame-emulsion-allergen-validation', 'professional_science', array( 'technique-tahini-citrus-sauce-kibbeh-arnabiyyeh', 'dish-kibbeh-arnabiyyeh-beirut' ), 'three unlabeled tahini hydration states in identical glass dishes showing paste, thickened transition and smooth emulsion, macro rheology texture and no ratios', 'peer_reviewed'
	),
	$c99_lebanese_coast_row(
		'reaction-bread-crust-maillard-manouche', 'reaction', 'dish-al-manouche-lebanon', 'lebanon-national', 'manouche-science-context', 'תגובת מייאר בקרום מנאקיש', 'Maillard Reaction in Manouche Crust',
		array( 'bread-production-maillard-review-2024', 'unesco-al-manouche-2023' ), 'סקירת הלחם קושרת השחמה ואורומות בקרום לתגובות מייאר ולתרכובות נדיפות, בעוד מקור אונסקו מספק את הקשר המנאקיש הלבנוני.', 'The bread review links crust browning and aroma with Maillard reactions and volatile compounds, while the UNESCO source provides the Lebanese manouche context.',
		'reaction', 'high-heat-browning-process-validation', 'professional_science', array( 'dish-al-manouche-lebanon', 'equipment-manouche-convex-saj', 'equipment-manouche-tabouneh-bakery-oven' ), 'a fully baked manouche crust macro showing a controlled gradient from pale dough to browned blistered surface beside a neutral heat-flow diagram without numbers', 'peer_reviewed'
	),
	$c99_lebanese_coast_row(
		'reaction-rice-starch-gelatinization-tripoli-sweets', 'reaction', 'dish-halawet-shmayseh-tripoli', 'lebanon-tripoli', 'tripoli-sweets-science-context', 'ג׳לטיניזציית עמילן אורז בממתקי טריפולי', 'Rice Starch Gelatinization in Tripoli Sweets',
		array( 'rice-starch-chemistry-review-2025', 'lebanon-traveler-sweets-2015-2016' ), 'סקירת עמילן האורז מתארת תפיחת גרגרים וזליגת עמילוז בחום ובמים; מקור הממתקים ממקם מנגנון זה כהקשר מחקרי אפשרי לחלאוות שמייסה, לא כנוסחה.', 'The rice-starch review describes granule swelling and amylose leaching under heat and water; the sweets source places this as a research context for Halawet Shmayseh, not a formula.',
		'reaction', 'rice-starch-time-temperature-validation', 'professional_science', array( 'dish-halawet-shmayseh-tripoli', 'technique-halawet-shmayseh-low-heat-forming' ), 'a four-stage rice-starch microscopy-inspired food study from dry granules to hydrated thickened paste, paired with a finished sweet only as a separate context sample', 'peer_reviewed'
	),
	$c99_lebanese_coast_row(
		'reaction-lactic-fermentation-ambarees-sirdeleh', 'reaction', 'ingredient-labneh-ambarees-shouf', 'lebanon-chouf', 'chouf-dairy-science-context', 'תסיסה לקטית בלבנה אמבריס וסירדלה', 'Lactic Fermentation in Labneh Ambaris and Sirdeleh',
		array( 'labneh-ambaris-microbiota-2022', 'fermented-cheese-safety-2025' ), 'מחקר המיקרוביוטה מתאר תסיסה לקטית ספונטנית בלבנה אמבריס, אך אינו הופך קהילה מיקרוביאלית מתועדת לפרוטוקול ייצור מסחרי.', 'The microbiota study describes spontaneous lactic fermentation in Labneh Ambaris but does not turn a documented microbial community into a commercial production protocol.',
		'reaction', 'dairy-fermentation-process-validation', 'professional_science', array( 'ingredient-labneh-ambarees-shouf', 'technique-labneh-ambarees-sirdele-fermentation' ), 'a closed laboratory-style dairy fermentation study with three sealed clay-vessel samples, abstract microbial dots and a chilled finished labneh sample, no home-fermentation instruction', 'peer_reviewed'
	),

	/* Eight techniques. */
	$c99_lebanese_coast_row(
		'technique-tahini-citrus-sauce-kibbeh-arnabiyyeh', 'technique', 'dish-kibbeh-arnabiyyeh-beirut', 'lebanon-beirut', 'beirut-dish-science-context', 'רוטב טחינה והדרים לקיבּה ארנבייה', 'Tahini and Citrus Sauce for Kibbeh Arnabiyyeh',
		array( 'food-heritage-kibbeh-regions', 'hou-tahini-water-rheology-2017' ), 'רשומת השיטה מחברת את זהות הקיבּה ארנבייה הביירותית להידרציית טחינה; סוג ההדר, החומציות והיחסים אינם נקבעים מן המחקר.', 'The technique record connects Beiruti Kibbeh Arnabiyyeh identity with tahini hydration; citrus identity, acidity and ratios are not established by the rheology study.',
		'reaction', 'sesame-emulsion-allergen-validation', 'professional_science', array( 'reaction-tahini-water-hydration-phase-transition', 'dish-kibbeh-arnabiyyeh-beirut' ), 'a source-neutral sauce study with tahini, water and an unidentified whole citrus kept separate beside a smooth finished emulsion, no ratio marks'
	),
	$c99_lebanese_coast_row(
		'technique-foul-bi-selek-staged-cooking', 'technique', 'dish-foul-bi-selek-beirut', 'lebanon-beirut', 'beirut-source-bounded', 'בישול מדורג של פול בסלק', 'Staged Cooking of Foul bi Selek',
		array( 'food-heritage-foul-b-selek' ), 'המקור מתאר בישול פול ולאחריו עבודה נפרדת עם בצל, שום, מנגולד וכוסברה, אך אינו מספק עקומת זמן וטמפרטורה.', 'The source describes cooking fava beans followed by a separate onion, garlic, chard and coriander stage but supplies no time-temperature curve.',
		'dish', 'legume-thermal-allergen-validation', 'professional_science', array( 'dish-foul-bi-selek-beirut', 'ingredient-spring-fava-bean-beirut-context', 'ingredient-swiss-chard-beirut-context' ), 'three-stage cooked process table with fava beans, softened aromatics and wilted chard in separate pans, no flames, clocks or quantities'
	),
	$c99_lebanese_coast_row(
		'technique-halawet-shmayseh-low-heat-forming', 'technique', 'dish-halawet-shmayseh-tripoli', 'lebanon-tripoli', 'tripoli-sweets-source', 'עיבוד בחום נמוך ועיצוב חלאוות שמייסה', 'Low-Heat Cooking and Forming of Halawet Shmayseh',
		array( 'lebanon-traveler-sweets-2015-2016', 'rice-starch-chemistry-review-2025' ), 'מקור הממתקים מתאר בישול בחום נמוך, יצירת מסה ג׳לטינית, עיצוב דסקיות ומילוי; אין בו נקודות קצה מדודות.', 'The sweets source describes low-heat cooking, formation of a gelatinous mass, disc shaping and filling without measured endpoints.',
		'reaction', 'rice-dairy-nut-cold-chain-validation', 'professional_science', array( 'dish-halawet-shmayseh-tripoli', 'reaction-rice-starch-gelatinization-tripoli-sweets' ), 'a four-step confectionery process study with thickened rice paste, cooling slab, formed discs and a chilled cream-filled finished sample, no temperature values'
	),
	$c99_lebanese_coast_row(
		'technique-marshousheh-minyara-steaming', 'technique', 'dish-marshousheh-minyara-family', 'lebanon-minyara-akkar', 'minyara-family-testimony', 'אידוי מרשושה ממיניארה', 'Steaming Minyara Marshousheh',
		array( 'aub-maingate-marshousheh-minyara' ), 'המתכון המשפחתי מתאר איחוד לוביה, מנגולד ובורגול גס ובישול באדים, בלי להפוך את התיאור לפרוטוקול לכל האזור.', 'The family recipe describes combining black-eyed peas, Swiss chard and coarse bulgur with steam cooking without turning the description into a protocol for the whole region.',
		'dish', 'gluten-grain-thermal-validation', 'professional_science', array( 'dish-marshousheh-minyara-family', 'ingredient-black-eyed-pea-minyara-context' ), 'a covered steaming vessel opened over a fully cooked black-eyed-pea, chard and coarse-bulgur mixture, gentle steam and no universal timing cue', 'named_testimony'
	),
	$c99_lebanese_coast_row(
		'technique-wheat-laban-cook-cool-akkar', 'technique', 'dish-wheat-laban-akkar-easter', 'lebanon-akkar', 'akkar-christian-occasion-context', 'בישול וקירור חיטה בלבן מעכאר', 'Cooking and Cooling Akkar Wheat with Laban',
		array( 'food-heritage-wheat-laban-akkar' ), 'המקור מתאר בישול חיטה וחומוס, שילוב יוגורט והעברה לקירור, אך אינו מאמת קצב קירור או חיי מדף.', 'The source describes cooking wheat and chickpeas, adding yogurt and moving the dish to refrigeration but does not validate cooling rate or shelf life.',
		'dish', 'grain-dairy-cold-chain-validation', 'professional_science', array( 'dish-wheat-laban-akkar-easter', 'tradition-akkar-easter-wheat-laban' ), 'a safe-process study with a covered cooked grain pot, shallow cooling tray and closed refrigerated finished bowl in separate zones, no time values'
	),
	$c99_lebanese_coast_row(
		'technique-mansoufeh-shape-poach-finish', 'technique', 'dish-mansoufeh-chouf-west-bekaa', 'lebanon-chouf-west-bekaa', 'named-guesthouse-recipe', 'עיצוב, חליטה וגימור של מנסופה', 'Shaping, Poaching and Finishing Mansoufeh',
		array( 'food-heritage-mansoufeh' ), 'המקור מתאר יצירת חלקים שטוחים מתערובת בורגול, דלעת וקמח, חליטה וגימור עם בצל, שמן זית וחומציות.', 'The source describes shaping flattened pieces from bulgur, pumpkin and flour, poaching them and finishing with onion, olive oil and acidity.',
		'dish', 'gluten-grain-thermal-validation', 'professional_science', array( 'dish-mansoufeh-chouf-west-bekaa' ), 'a three-stage mansoufeh method board with flattened uncooked pieces, active poaching and a fully cooked onion-finished plate, no dimensions or ratios'
	),
	$c99_lebanese_coast_row(
		'technique-akkoub-cleaning-cooking-chouf', 'technique', 'dish-akkoub-stew-chouf-dahr-el-baydar', 'lebanon-chouf-dahr-el-baydar', 'chouf-wild-plant-context', 'ניקוי ובישול עכוב בשוף', 'Cleaning and Cooking Akkoub in Chouf Context',
		array( 'food-heritage-akkoub-stew', 'aub-wild-plant-collection-lebanon' ), 'מקור המנה מתאר הסרת קוצים והכנה לבישול, אך הרשומה אינה מלמדת זיהוי שטח ואינה מאשרת ליקוט.', 'The dish source describes thorn removal and preparation for cooking, but the record does not teach field identification or authorize collection.',
		'dish', 'wild-plant-identification-legal-collection', 'professional_science', array( 'ingredient-akkoub-gundelia-chouf-context', 'dish-akkoub-stew-chouf-dahr-el-baydar' ), 'a controlled professional prep bench with a botanically verified whole Gundelia specimen isolated from a cleaned portion and fully cooked sample, puncture-safe tools and no foraging'
	),
	$c99_lebanese_coast_row(
		'technique-dakoujeh-historical-pantry-storage', 'technique', 'equipment-dakoujeh-clay-storage-vessel', 'lebanon-beit-chabeb-mount-lebanon', 'beit-chabeb-historical-context', 'אחסון מזווה היסטורי בדכוג׳ה', 'Historical Pantry Storage in a Dakoujeh',
		array( 'mot-dakoujeh-beit-chabeb' ), 'משרד התיירות מתאר דכוג׳ה היסטורית מבית שבאב ככלי נקבובי ששימש דגנים, זיתים, מוצרי חלב וחמוצים לפני קירור מודרני.', 'The Ministry of Tourism describes a historical Beit Chabeb Dakoujeh as a porous vessel used for grains, olives, dairy and pickles before modern refrigeration.',
		'equipment', 'historical-not-modern-foodsafe', 'equipment_research', array( 'equipment-dakoujeh-clay-storage-vessel' ), 'a museum-neutral porous clay vessel shown empty beside separated closed grain, olive, dairy and pickle reference containers, never storing food directly'
	),

	/* Six equipment records. */
	$c99_lebanese_coast_row(
		'equipment-manouche-convex-saj', 'equipment', 'dish-al-manouche-lebanon', 'lebanon-national', 'manouche-practice-context', 'סאג׳ קמור למנאקיש', 'Convex Saj for Manouche',
		array( 'unesco-al-manouche-2023' ), 'תיעוד אונסקו של מנהג המנאקיש כולל משטח סאג׳ קמור כאחת מתצורות האפייה המתועדות.', 'UNESCO documentation of the manouche practice includes a convex saj among documented baking configurations.',
		'equipment', 'high-heat-equipment-control', 'equipment_research', array( 'dish-al-manouche-lebanon', 'reaction-bread-crust-maillard-manouche' ), 'an unlit professional convex saj photographed from three-quarter angle with a separate fully baked flatbread sample, clear heat-zone spacing and no operating flame'
	),
	$c99_lebanese_coast_row(
		'equipment-manouche-flat-saj', 'equipment', 'dish-al-manouche-lebanon', 'lebanon-national', 'manouche-practice-context', 'סאג׳ שטוח למנאקיש', 'Flat Saj for Manouche',
		array( 'unesco-al-manouche-2023' ), 'תיעוד אונסקו מציג גם משטח סאג׳ שטוח בתוך מגוון ציוד האפייה של מנהג המנאקיש.', 'UNESCO documentation also presents a flat saj within the range of baking equipment used in the manouche practice.',
		'equipment', 'high-heat-equipment-control', 'equipment_research', array( 'dish-al-manouche-lebanon', 'reaction-bread-crust-maillard-manouche' ), 'an unlit circular flat saj on a stable professional stand with a separate baked manouche, overhead geometry emphasizing the flat cooking surface and safety clearance'
	),
	$c99_lebanese_coast_row(
		'equipment-manouche-tabouneh-bakery-oven', 'equipment', 'dish-al-manouche-lebanon', 'lebanon-national', 'manouche-practice-context', 'תנור טאבונה ומאפייה למנאקיש', 'Tabouneh and Bakery Oven for Manouche',
		array( 'unesco-al-manouche-2023' ), 'מקור אונסקו מתעד טאבונה ותנורי מאפייה לצד סאג׳ים בהקשר העברת מנהג המנאקיש.', 'The UNESCO source documents tabouneh and bakery ovens beside saj configurations in the transmission of the manouche practice.',
		'equipment', 'high-heat-equipment-control', 'equipment_research', array( 'dish-al-manouche-lebanon', 'reaction-bread-crust-maillard-manouche' ), 'a cold unlit tabouneh-style oven mouth and a separate modern bakery-deck cross-section represented as two distinct equipment references, no fire or installation claim'
	),
	$c99_lebanese_coast_row(
		'equipment-kibbeh-stone-mortar-wooden-pestle', 'equipment', 'hub-lebanese-kibbeh-family', 'lebanon-multi-region', 'lebanese-kibbeh-practice-context', 'מכתש אבן ועלי עץ לקיבּה', 'Stone Mortar and Wooden Pestle for Kibbeh',
		array( 'lebanon-ich-register-2024', 'food-heritage-kibbeh-regions' ), 'רישום המורשת מתעד כתישת קיבּה בכלי אבן ובעזרת עלי עץ, לצד מעבר במקומות רבים למיכון מודרני.', 'The heritage inventory documents kibbeh pounding in stone with a wooden pestle alongside replacement by modern machinery in many settings.',
		'equipment', 'historical-not-modern-foodsafe', 'equipment_research', array( 'technique-kibbeh-pounding-forming-lebanon', 'hub-lebanese-kibbeh-family' ), 'an empty historical stone mortar and wooden pestle displayed on a museum plinth beside a separate sealed cooked kibbeh sample, no raw meat or use demonstration'
	),
	$c99_lebanese_coast_row(
		'equipment-seafood-calibrated-probe-thermometer', 'equipment', 'hub-north-coast-batroun-jbeil-foodways', 'lebanon-north-coast', 'seafood-safety-context', 'מדחום חדירה מכויל לבקרת פירות ים', 'Calibrated Probe Thermometer for Seafood Control',
		array( 'fda-seafood-safe-handling' ), 'הנחיות FDA מדגישות טיפול קר, הפרדה, ניקוי ובדיקת מזון ים מבושל; המדחום כאן הוא כלי בקרת בטיחות ולא מוצר מוצע.', 'FDA guidance emphasizes cold handling, separation, sanitation and checking cooked seafood; the thermometer is a safety-control tool here, not an offered product.',
		'equipment', 'seafood-haccp-bone-temperature-control', 'equipment_research', array( 'hub-north-coast-batroun-jbeil-foodways' ), 'a calibrated probe thermometer beside a closed chilled seafood tray and a sanitized board, no readable temperature, raw service or restaurant branding', 'regulatory_guidance'
	),
	$c99_lebanese_coast_row(
		'equipment-dakoujeh-clay-storage-vessel', 'equipment', 'hub-mount-lebanon-shouf-rural-table', 'lebanon-beit-chabeb-mount-lebanon', 'beit-chabeb-historical-context', 'כלי דכוג׳ה מחרס מבית שבאב', 'Beit Chabeb Dakoujeh Clay Vessel',
		array( 'mot-dakoujeh-beit-chabeb' ), 'משרד התיירות מתאר כלי חרס נקבובי משפחתי משנת 1960 מבית שבאב ששימש היסטורית לאחסון ולצינון מזון.', 'The Ministry of Tourism describes a porous family clay vessel from 1960 in Beit Chabeb historically used for food storage and cooling.',
		'equipment', 'historical-not-modern-foodsafe', 'equipment_research', array( 'technique-dakoujeh-historical-pantry-storage' ), 'an empty porous clay Dakoujeh displayed as a documented material-culture object with a cutaway texture sample, no food contact or copied museum image'
	),

	/* Seven traditions and bounded transmission contexts. */
	$c99_lebanese_coast_row(
		'tradition-beirut-1885-cookbook-print-culture', 'tradition', 'hub-beirut-urban-food-history', 'lebanon-beirut', 'beirut-print-culture-context', 'תרבות ספרי הבישול בביירות בשנת 1885', 'Beirut Cookbook Print Culture in 1885',
		array( 'aub-beirut-cookbook-1885-chapter' ), 'מחקר AUB מתעד ספר בישול שנדפס בביירות בשנת 1885 בשני חלקים למנות מערביות וערביות, כעדות לתרבות הידע הקולינרי המודפס בעיר.', 'AUB research documents an 1885 Beirut-printed cookbook with separate sections for Western and Arab dishes as evidence of the city\'s culinary print culture.',
		'context', 'private-source-bounded-reference', 'info_nav', array( 'hub-beirut-urban-food-history' ), 'a closed source-neutral nineteenth-century cookbook facsimile shape beside blank recipe cards, movable type forms and culinary utensils, no copied text or title'
	),
	$c99_lebanese_coast_row(
		'tradition-beirut-seasonal-foul-bi-selek', 'tradition', 'hub-beirut-urban-food-history', 'lebanon-beirut', 'beirut-source-bounded', 'עונת הפול בסלק בביירות', 'Seasonal Foul bi Selek in Beirut',
		array( 'food-heritage-foul-b-selek' ), 'המקור ממקם את הפול בסלק הביירותי בסוף החורף עם הופעת פול אביבי, כשעונתיות זו נשמרת כפרט של המקור ולא ככלל לכל בית.', 'The source places Beirut Foul bi Selek at the end of winter with the arrival of spring fava beans, retained as a source detail rather than a rule for every household.',
		'context', 'produce-identity-handling-validation', 'info_nav', array( 'dish-foul-bi-selek-beirut', 'ingredient-spring-fava-bean-beirut-context' ), 'a seasonal transition still life with late-winter greens, spring fava pods and the fully cooked dish in separate zones, no calendar text'
	),
	$c99_lebanese_coast_row(
		'tradition-tripoli-halwanji-sweets-craft', 'tradition', 'hub-tripoli-sweets-breakfast-souks', 'lebanon-tripoli', 'tripoli-sweets-craft-context', 'מלאכת החלוונג׳י בטריפולי', 'Tripoli Halwanji Sweets Craft',
		array( 'peck-tripoli-sweets-craft-2022', 'lebanon-traveler-sweets-2015-2016' ), 'מקורות מחקר וארכיון ממקמים בעלי מלאכת ממתקים ותהליכי ייצור בתוך ההיסטוריה העירונית והחברתית של טריפולי.', 'Research and archive sources place sweets makers and production craft within Tripoli\'s urban and social history.',
		'context', 'private-source-bounded-reference', 'info_nav', array( 'dish-halawet-shmayseh-tripoli', 'tradition-tripoli-ramadan-sweets-context' ), 'an empty traditional confectionery worktable with brass trays, rice-sweet forming tools and fully cooked sample pieces, no people, shop name or copied heritage image', 'academic_source'
	),
	$c99_lebanese_coast_row(
		'tradition-tripoli-ramadan-sweets-context', 'tradition', 'hub-tripoli-sweets-breakfast-souks', 'lebanon-tripoli', 'tripoli-ramadan-source-context', 'ממתקי רמדאן בטריפולי, הקשר מתועד', 'Tripoli Ramadan Sweets Context',
		array( 'lebanon-traveler-sweets-2015-2016' ), 'המקור המתוארך קושר חלאוות שמייסה לעונת הקיץ ולרמדאן; זהו הקשר ארכיוני מוגדר ולא טענה שכל משפחה או קונדיטוריה מגישה אותה.', 'The dated source connects Halawet Shmayseh with summer and Ramadan; this is a bounded archive context, not a claim that every household or sweet shop serves it.',
		'context', 'rice-dairy-nut-cold-chain-validation', 'info_nav', array( 'dish-halawet-shmayseh-tripoli' ), 'a restrained evening dessert setting with one chilled rice sweet, water and neutral lantern-like light, no people, religious symbols or shop cues', 'editorial_archive'
	),
	$c99_lebanese_coast_row(
		'tradition-akkar-easter-wheat-laban', 'tradition', 'hub-akkar-seasonal-grain-greens', 'lebanon-akkar', 'akkar-christian-occasion-context', 'חיטה בלבן בעכאר לקראת הפסחא', 'Akkar Wheat with Laban Before Easter',
		array( 'food-heritage-wheat-laban-akkar' ), 'המקור מתעד הכנת חיטה בלבן בשבת שלפני הפסחא בעכאר, תוך ציון שהמנה מוכרת גם במקומות נוספים.', 'The source documents preparing wheat with laban on the Saturday before Easter in Akkar while noting that the dish is also known elsewhere.',
		'context', 'grain-dairy-cold-chain-validation', 'info_nav', array( 'dish-wheat-laban-akkar-easter', 'technique-wheat-laban-cook-cool-akkar' ), 'a covered chilled wheat-and-yogurt bowl beside dry wheat and chickpeas on a quiet seasonal table, no cross, egg, church or universal-community claim'
	),
	$c99_lebanese_coast_row(
		'tradition-chouf-adha-kaak-community-baking', 'tradition', 'hub-mount-lebanon-shouf-rural-table', 'lebanon-chouf', 'chouf-family-occasion-source', 'אפייה משותפת של כעכ אלעיד בשוף', 'Shared Kaak el Eid Baking in Chouf',
		array( 'food-heritage-kaak-eid-chouf' ), 'המקור המשפחתי מתאר הכנה בכמות גדולה ועבודה משותפת סביב כעכ אלעיד בהקשר עיד אל-אדחא בשוף, בלי לטעון לאחידות בכל הכפרים.', 'The family source describes large-batch shared work around Kaak el Eid for Eid al-Adha in Chouf without claiming uniform practice across all villages.',
		'context', 'gluten-sesame-allergen-validation', 'info_nav', array( 'dish-kaak-eid-chouf' ), 'a communal-scale but people-free baking table with divided dough, stamping tools, sesame and fully baked cookies in separate trays, no religious symbols', 'named_testimony'
	),
	$c99_lebanese_coast_row(
		'tradition-darb-el-karam-food-trail', 'tradition', 'hub-mount-lebanon-shouf-rural-table', 'lebanon-chouf-west-bekaa', 'darb-el-karam-village-network-context', 'שביל האוכל דרב אל-כרם', 'Darb El Karam Food Trail',
		array( 'aub-darb-el-karam-brochure', 'aub-esdu-food-heritage-foundation' ), 'מקורות AUB מתארים מסלול אוכל המחבר תשעה כפרים בשוף העליון ובמערב הבקאע סביב מורשת, אירוח וייצור מקומי.', 'AUB sources describe a food trail connecting nine villages in Upper Chouf and West Bekaa around heritage, hospitality and local production.',
		'institution', 'institution-current-status-recheck', 'local_discovery', array( 'dish-mansoufeh-chouf-west-bekaa', 'institution-aub-esdu-food-heritage-research' ), 'an abstract nine-node food-trail table using distinct bowls and path stones with no geographic map, village logos, people or booking cue', 'official_organization'
	),

	/* Three market and pantry benchmarks. */
	$c99_lebanese_coast_row(
		'market-tripoli-old-souks-food-context', 'market', 'hub-tripoli-sweets-breakfast-souks', 'lebanon-tripoli', 'tripoli-urban-market-context', 'הסוקים העתיקים של טריפולי בהקשר מזון', 'Tripoli Old Souks Food Context',
		array( 'unesco-tripoli-old-city-2019' ), 'דף אונסקו מתאר ברובע ההיסטורי של טריפולי סוקים ובהם סוחרי תבלינים וירקות, כחלק מן המרקם העירוני המוצע לרישום.', 'The UNESCO page describes souks in historic Tripoli including spice and vegetable merchants as part of the urban fabric proposed for inscription.',
		'market', 'market-current-status-recheck', 'local_discovery', array( 'hub-tripoli-sweets-breakfast-souks' ), 'an empty source-neutral stone-souk passage with unbranded spice and vegetable stalls represented as closed evidence displays, no seller, price or current-operation cue', 'official_source'
	),
	$c99_lebanese_coast_row(
		'market-halba-produce-system-historical-context', 'market', 'hub-akkar-seasonal-grain-greens', 'lebanon-halba-akkar', 'akkar-historical-market-context', 'מערכת התוצרת של חלבה, הקשר היסטורי בהחזקה', 'Halba Produce System, Historical Context Held',
		array( 'aub-khreibet-el-jundi-agrarian-transition' ), 'המחקר החקלאי מספק הקשר היסטורי לקשרי יישובים, תוצרת ושוק בעכאר, אך אינו מאמת שוק תוצרת פעיל בחלבה כיום.', 'The agrarian study provides historical context for settlement, produce and market relations in Akkar but does not verify a currently operating Halba produce market.',
		'held', 's9-source-fail-closed', 'commercial_benchmark', array(),
		'an unresolved historical market-system board with blank route lines, closed produce crates and an empty status card, no current market depiction or vendor', 'pending_evidence'
	),
	$c99_lebanese_coast_row(
		'market-menhem-trading-batroun-benchmark', 'market', 'hub-north-coast-batroun-jbeil-foodways', 'lebanon-batroun', 'business-benchmark-only', 'Menhem Trading בבתרון, אמת מידה למזווה', 'Menhem Trading Batroun Pantry Benchmark',
		array( 'menhem-trading-batroun-official' ), 'האתר הרשמי שאוחזר מציג עסק בבתרון בהקשר מעדנייה ומזווה; הרשומה משתמשת בו כאמת מידה בלבד ולא כקשר מסחרי.', 'The retrieved official site presents a Batroun business in delicatessen and pantry context; the record uses it only as a benchmark and not as a commercial relationship.',
		'market', 'business-benchmark-current-status-recheck', 'commercial_benchmark', array( 'hub-north-coast-batroun-jbeil-foodways' ), 'a generic premium pantry benchmark shelf with sealed unbranded oil, preserves, grains and spice containers, no copied shop interior, label or ordering cue', 'official_business_source'
	),

	/* Three institutions. */
	$c99_lebanese_coast_row(
		'institution-jamaat-al-noor-minyara-community-kitchen', 'culinary_institution', 'hub-akkar-seasonal-grain-greens', 'lebanon-minyara-akkar', 'minyara-orthodox-womens-cooperative-source', 'מטבח קהילתי ג׳מאעת אל-נור במיניארה', 'Jamaat Al Noor Community Kitchen in Minyara',
		array( 'food-heritage-jamaat-al-noor-minyara' ), 'המקור מתאר קואופרטיב נשים נוצריות אורתודוקסיות במיניארה ומטבח קהילתי, עם מספר משתתפות נכון למועד המקור בלבד.', 'The source describes an Orthodox Christian women\'s cooperative and community kitchen in Minyara, with participant numbers valid only for the source date.',
		'institution', 'institution-current-status-recheck', 'info_nav', array( 'hub-akkar-seasonal-grain-greens', 'institution-akletna-community-kitchen-network' ), 'an empty licensed-kitchen-style workspace with clean stainless tables, covered prepared-food containers and cooperative workflow cards without names or faces', 'official_organization'
	),
	$c99_lebanese_coast_row(
		'institution-akletna-community-kitchen-network', 'culinary_institution', 'cuisine-lebanese-regional', 'lebanon-multi-region', 'community-kitchen-network-context', 'רשת המטבחים הקהילתיים אכלתנא', 'Akletna Community Kitchen Network',
		array( 'food-heritage-akletna-community-kitchens', 'aub-esdu-food-heritage-foundation' ), 'מקורות המוסד מתארים רשת של מטבחים קהילתיים אזוריים המשמרים ידע ומפתחים פעילות כלכלית מקומית, בלי ליצור קשר תפעולי עם Complete99.', 'Institutional sources describe a network of regional community kitchens preserving knowledge and developing local economic activity without creating an operational relationship with Complete99.',
		'institution', 'institution-current-status-recheck', 'info_nav', array( 'institution-jamaat-al-noor-minyara-community-kitchen', 'institution-aub-esdu-food-heritage-research' ), 'an abstract network of separate clean community-kitchen workstations connected by neutral lines, no people, logos, geographic map or contact details', 'official_organization'
	),
	$c99_lebanese_coast_row(
		'institution-aub-esdu-food-heritage-research', 'culinary_institution', 'cuisine-lebanese-regional', 'lebanon-national', 'aub-esdu-institutional-context', 'AUB-ESDU וחקר מורשת המזון', 'AUB ESDU Food Heritage Research',
		array( 'aub-esdu-food-heritage-foundation', 'aub-darb-el-karam-brochure' ), 'דפי AUB מתארים את תפקיד ESDU בהקמת Food Heritage Foundation וביוזמות מחקר, מטבחים קהילתיים ושביל אוכל.', 'AUB pages describe ESDU\'s role in establishing the Food Heritage Foundation and in research, community-kitchen and food-trail initiatives.',
		'institution', 'institution-current-status-recheck', 'info_nav', array( 'institution-akletna-community-kitchen-network', 'tradition-darb-el-karam-food-trail' ), 'a source-neutral university food-heritage research desk with blank field notes, sealed ingredient specimens and a community-kitchen diagram, no institutional logo or copied page', 'official_organization'
	),

	/* Two restaurant benchmarks, both held. */
	$c99_lebanese_coast_row(
		'restaurant-akra-tripoli-breakfast-benchmark', 'restaurant', 'hub-tripoli-sweets-breakfast-souks', 'lebanon-tripoli', 'business-benchmark-only', 'אכרה בטריפולי, אמת מידה לארוחת בוקר בהחזקה', 'Akra Tripoli Breakfast Benchmark Held',
		array( 'lebanon-traveler-tripoli-breakfast-2018' ), 'מדריך משנת 2018 מזכיר את אכרה בהקשר פול וחומוס בטריפולי, אך אין במאגר מקור רשמי עדכני המאמת פעילות נוכחית.', 'A 2018 guide mentions Akra in a Tripoli foul and hummus context, but the corpus contains no current official source verifying present operation.',
		'held', 's9-source-fail-closed', 'commercial_benchmark', array( 'dish-tripoli-foul-hummus-breakfast-pairing' ),
		'an unresolved breakfast-business benchmark board with two closed bowl silhouettes, a blank dated-source card and an empty current-status field, no storefront or branding', 'pending_evidence'
	),
	$c99_lebanese_coast_row(
		'restaurant-aal-baher-byblos-seafood-benchmark', 'restaurant', 'hub-north-coast-batroun-jbeil-foodways', 'lebanon-jbeil-byblos', 'business-benchmark-only', 'עאל באחר בג׳בייל, אמת מידה לפירות ים בהחזקה', 'Aal Baher Byblos Seafood Benchmark Held',
		array( 'hello-byblos-aal-baher' ), 'מדריך מקומי כולל רשומת מסעדת פירות ים בשם עאל באחר בג׳בייל, אך מקור מדריך יחיד אינו מספיק לאימות פעילות, זהות משפטית או תפריט נוכחי.', 'A local directory includes a seafood-restaurant record named Aal Baher in Byblos, but one directory source is insufficient to verify operation, legal identity or a current menu.',
		'held', 's9-source-fail-closed', 'commercial_benchmark', array( 'equipment-seafood-calibrated-probe-thermometer' ),
		'an unresolved seafood-business benchmark board with a closed chilled-fish silhouette, blank legal-identity card and empty menu field, no restaurant scene, logo or dish reconstruction', 'pending_evidence'
	),
);

$c99_lebanese_coast_entities = array();
foreach ( $c99_lebanese_coast_rows as $spec ) {
	$c99_lebanese_coast_entities[] = $c99_lebanese_coast_build( $spec );
}

$c99_lebanese_coast_ids = array_column( $c99_lebanese_coast_entities, 'id' );
if ( count( $c99_lebanese_coast_ids ) !== count( array_unique( $c99_lebanese_coast_ids ) ) ) {
	throw new RuntimeException( 'Duplicate Lebanese coast and north entity ID.' );
}

$c99_lebanese_coast_entity_offsets = array();
foreach ( $c99_lebanese_coast_entities as $entity_offset => $entity ) {
	$c99_lebanese_coast_entity_offsets[ $entity['id'] ] = $entity_offset;
}
foreach ( $c99_lebanese_coast_entities as &$entity ) {
	$breadcrumbs = array( 'cuisine-lebanese-regional' );
	if ( 'cuisine-lebanese-regional' !== $entity['parent_id'] ) {
		$breadcrumbs[] = $entity['parent_id'];
	}
	$breadcrumbs[] = $entity['id'];
	$entity['seo']['breadcrumb_entity_ids'] = array_values( array_unique( $breadcrumbs ) );
	if ( isset( $c99_lebanese_coast_entity_offsets[ $entity['parent_id'] ] ) ) {
		$parent_offset = $c99_lebanese_coast_entity_offsets[ $entity['parent_id'] ];
		$c99_lebanese_coast_entities[ $parent_offset ]['seo']['expected_child_ids'][] = $entity['id'];
	}
}
unset( $entity );

$c99_lebanese_coast_expected_counts = array(
	'topic_hub' => 5,
	'dish' => 12,
	'ingredient' => 8,
	'molecule' => 3,
	'reaction' => 4,
	'technique' => 8,
	'equipment' => 6,
	'tradition' => 7,
	'market' => 3,
	'culinary_institution' => 3,
	'restaurant' => 2,
);
$c99_lebanese_coast_counts = array_count_values( array_column( $c99_lebanese_coast_entities, 'type' ) );
if ( 61 !== count( $c99_lebanese_coast_entities ) || $c99_lebanese_coast_expected_counts !== $c99_lebanese_coast_counts ) {
	throw new RuntimeException( 'Lebanese coast and north expansion must contain exactly 61 entities with the approved type distribution.' );
}
if ( 31 !== count( $c99_lebanese_coast_sources ) ) {
	throw new RuntimeException( 'Lebanese coast and north expansion must contain exactly 31 new sources.' );
}

$c99_lebanese_coast_held_ids = array(
	'dish-kaak-orchali-beirut-context',
	'dish-jazarieh-tripoli-context',
	'market-halba-produce-system-historical-context',
	'restaurant-akra-tripoli-breakfast-benchmark',
	'restaurant-aal-baher-byblos-seafood-benchmark',
);
$c99_lebanese_coast_actual_held_ids = array();
$c99_lebanese_coast_prompts = array();
foreach ( $c99_lebanese_coast_entities as $entity ) {
	if ( isset( $c99_lebanese_coast_prompts[ $entity['visual']['prompt_en'] ] ) ) {
		throw new RuntimeException( 'Duplicate Lebanese coast and north visual prompt: ' . $entity['id'] );
	}
	$c99_lebanese_coast_prompts[ $entity['visual']['prompt_en'] ] = true;
	foreach ( $entity['compliance'] as $note ) {
		if ( 's9-source-fail-closed' === $note['code'] ) {
			$c99_lebanese_coast_actual_held_ids[] = $entity['id'];
		}
	}
	foreach ( array_merge( $entity['facts'], $entity['relations'], $entity['compliance'] ) as $claim ) {
		foreach ( $claim['source_ids'] as $source_id ) {
			if ( ! isset( $c99_lebanese_coast_known_sources[ $source_id ] ) ) {
				throw new RuntimeException( 'Unknown Lebanese coast and north source ID: ' . $source_id );
			}
		}
	}
}
sort( $c99_lebanese_coast_held_ids );
sort( $c99_lebanese_coast_actual_held_ids );
if ( $c99_lebanese_coast_held_ids !== $c99_lebanese_coast_actual_held_ids ) {
	throw new RuntimeException( 'Lebanese coast and north held records do not match the approved five fail-closed IDs.' );
}

return array(
	'schema' => 'complete99-lebanese-regional-expansion-coast-north/v1',
	'version' => 'culinary-science-2026.08.07.v18',
	'sources' => $c99_lebanese_coast_sources,
	'entities' => $c99_lebanese_coast_entities,
	'private_entity_ids' => $c99_lebanese_coast_ids,
	'counts' => array(
		'by_type' => $c99_lebanese_coast_counts,
		'total_entities' => count( $c99_lebanese_coast_entities ),
	),
);
