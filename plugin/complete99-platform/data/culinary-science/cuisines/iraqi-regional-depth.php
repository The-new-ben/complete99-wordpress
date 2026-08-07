<?php
/**
 * Complete99 Iraqi dishes and applied-science depth tranche.
 *
 * Loaded after iraqi-foundations.php. Every record remains private, noindex
 * and reference-only through the shared Iraqi entity builder. The module maps
 * identities and measurement gaps, not public recipes, offers or shelf lives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_iraqi_depth_sources = array(
	'iraqi-depth-nasrallah-delights' => array(
		'type' => 'official_business', 'publisher' => 'Nawal Nasrallah, Delights from the Garden of Eden',
		'title' => 'Delights from the Garden of Eden, official book site and index',
		'url' => 'https://www.iraqicookbook.com/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-tbit-2024' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Tbit, Iraqi Stuffed Chicken with Spiced Rice',
		'url' => 'https://www.jewishfoodsociety.org/recipes/iraqi-tbit-iraqi-stuffed-chicken-with-spiced-rice', 'published_at' => '2024-09-19', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-kubbeh-green-beans-2021' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Iraqi Kubbeh Soup with Green Beans',
		'url' => 'https://www.jewishfoodsociety.org/recipes/iraqi-dumpling-soup-with-green-beans', 'published_at' => '2021-09-01', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-kubbeh-batata-2023' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Kubbeh Batata, Iraqi Potato Fritters Filled with Meat',
		'url' => 'https://www.jewishfoodsociety.org/recipes/kubbeh-batata-potato-kubbeh-filled-with-meat', 'published_at' => '2023-11-29', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-kichree-2026' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Kichree, Iraqi Rice and Red Lentils',
		'url' => 'https://www.jewishfoodsociety.org/recipes/kichree-iraqi-rice-and-red-lentils', 'published_at' => '2026-01-21', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-beet-kubbeh-2022' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Beet Kubbeh Soup',
		'url' => 'https://www.jewishfoodsociety.org/recipes/beet-kubbeh-soup', 'published_at' => '2022-11-02', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-sabich-2018' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Sabich and the Iraqi Shabbat Breakfast Context',
		'url' => 'https://www.jewishfoodsociety.org/recipes/sabich', 'published_at' => '2018-05-02', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-ingriye-2021' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Ingriye, Roasted Eggplant with Tomatoes and Beef',
		'url' => 'https://www.jewishfoodsociety.org/recipes/ingriye-roasted-eggplant-with-tomatoes-and-beef', 'published_at' => '2021-09-01', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-foodish-sambusak-btawa-2025' => array(
		'type' => 'official_organization', 'publisher' => 'FOODISH, ANU Museum of the Jewish People',
		'title' => 'Sambusak Btawa, Pan-Fried Iraqi Sambusak',
		'url' => 'https://foodish.anumuseum.org.il/en/community-recipe/sambusak-btawa/', 'published_at' => '2025-03-11', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-foodish-kubba-mosul-2026' => array(
		'type' => 'official_organization', 'publisher' => 'FOODISH, ANU Museum of the Jewish People',
		'title' => 'Kubba Mosul',
		'url' => 'https://foodish.anumuseum.org.il/en/recipe/iraqi-kubba-mosul/', 'published_at' => '2026-06-30', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-foodish-kubbeh-hamusta-2024' => array(
		'type' => 'official_organization', 'publisher' => 'FOODISH, ANU Museum of the Jewish People',
		'title' => 'Kubbeh Hamusta, Green Kubbeh Soup',
		'url' => 'https://foodish.anumuseum.org.il/en/recipe/green-kubbeh/', 'published_at' => '2024-10-27', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-jfs-duhok-kubbeh-2018' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Preserving a Kurdish Recipe, One Kubbeh at a Time',
		'url' => 'https://www.jewishfoodsociety.org/stories/preserving-a-kurdish-recipe-one-kubbeh-at-a-time', 'published_at' => '2018-06-08', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-ninewa-everyday-peace-2023' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Cooperation and Conflict, SAGE Journals',
		'title' => 'Everyday peace in the Ninewa Plains, Iraq: Culture, rituals, and community interactions',
		'url' => 'https://journals.sagepub.com/doi/10.1177/00108367231177797', 'published_at' => '2023-06-06', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-broad-beans-2020' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Journal of Ethnic Foods',
		'title' => 'Symbolic meaning and use of broad beans in traditional foods of the Mediterranean Basin and the Middle East',
		'url' => 'https://link.springer.com/article/10.1186/s42779-020-00073-1', 'published_at' => '2020-11-10', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-depth-uobasrah-dried-fish-2026' => array(
		'type' => 'official_organization', 'publisher' => 'University of Basrah',
		'title' => 'Cultural lecture on Basrah dried-fish traditions',
		'url' => 'https://uobasrah.edu.iq/news/14304', 'published_at' => '2026-06-02', 'retrieved_at' => '2026-08-07',
	),
);

$c99_iraqi_depth_risk_profiles = array(
	'general' => array(
		'science_he' => 'המקורות תומכים בזהות קולינרית, אך אינם מספקים למנה או למוצר המסוימים מדידת pH, בריקס, פעילות מים, עקומת טמפרטורה, תפוקה או חיי מדף מאומתים.',
		'science_en' => 'The sources support culinary identity but provide no measured pH, Brix, water activity, temperature curve, yield or validated shelf life for the specific dish or product.',
		'sources' => array(), 'compliance' => array(),
	),
	'meat' => array(
		'science_he' => 'מין בעל החיים, נתח, אחוז שומן, גודל חלקיק, טמפרטורת ליבה, קצב קירור וזיהום צולב אינם נקבעים מן הזהות התרבותית.',
		'science_en' => 'Animal species, cut, fat percentage, particle size, core temperature, cooling rate and cross-contamination controls are not established by cultural identity.',
		'sources' => array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ),
		'compliance' => array( 'meat-source-and-thermal-validation', 'יש לאמת מקור מפוקח, טיפול, טמפרטורת ליבה, קירור וזיהום צולב לפני מתכון, ייצור או הצעה.', 'Verify inspected source, handling, core temperature, cooling and cross-contamination controls before any recipe, production or offer.' ),
	),
	'rice' => array(
		'science_he' => 'זן האורז, עמילוז ועמילופקטין, ג׳לטיניזציה, יחס נוזלים, קצב קירור, זמן החזקה וספירת Bacillus cereus אינם מאומתים לרשומה זו.',
		'science_en' => 'Rice cultivar, amylose and amylopectin, gelatinization, liquid ratio, cooling rate, holding time and Bacillus cereus counts are not validated for this record.',
		'sources' => array( 'iraqi-rice-bacillus-2026', 'israel-moh-food-hygiene' ),
		'compliance' => array( 'cooked-rice-time-temperature-control', 'אין לקבוע קירור, החזקה, חימום חוזר או חיי מדף לאורז בלי תהליך זמן וטמפרטורה מאומת.', 'Do not set cooling, holding, reheating or shelf life for rice without a validated time and temperature process.' ),
	),
	'fish' => array(
		'science_he' => 'מין הדג, מקורו, שרשרת הקירור, עצמות, טפילים, היסטמין כאשר רלוונטי וטמפרטורת ליבה אינם מאומתים ברמת אצווה.',
		'science_en' => 'Fish species, source, cold chain, bones, parasites, histamine where relevant and core temperature are not verified at lot level.',
		'sources' => array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ),
		'compliance' => array( 'fish-species-cold-chain-and-thermal-validation', 'יש לזהות מין ואצווה, לאמת שרשרת קירור, עצמות, טפילים וטיפול תרמי לפני מתכון או מוצר.', 'Identify species and lot, then validate cold chain, bones, parasites and thermal treatment before any recipe or product.' ),
	),
	'open_fire' => array(
		'science_he' => 'סוג הדלק, מרחק מן הלהבה, משך חשיפה, טמפרטורת פני שטח וליבה וחשיפה לעשן או לתוצרי בעירה אינם מוגדרים מן השם מסגוף.',
		'science_en' => 'Fuel identity, flame distance, exposure time, surface and core temperatures, and smoke or combustion exposure are not defined by the name masgouf.',
		'sources' => array( 'iraqi-grilling-pah-2025', 'foodsafety-safe-temperatures' ),
		'compliance' => array( 'open-fire-fuel-distance-and-smoke-validation', 'נדרש פרוטוקול אש מאומת הכולל דלק מותר, מרחק, מניעת מגע להבה, טמפרטורות ואוורור.', 'A validated fire protocol is required, including permitted fuel, distance, prevention of flame contact, temperatures and ventilation.' ),
	),
	'dried_fish' => array(
		'science_he' => 'מין הדג, קליטת מלח, זמן וטמפרטורת ייבוש, לחות יחסית, פעילות מים, חמצון, אמינים ביוגניים, טפילים ומיקרוביולוגיה אינם מאומתים למוצר.',
		'science_en' => 'Fish species, salt uptake, drying time and temperature, relative humidity, water activity, oxidation, biogenic amines, parasites and microbiology are not validated for the product.',
		'sources' => array( 'fda-water-activity', 'israel-moh-food-hygiene', 'iraqi-depth-uobasrah-dried-fish-2026' ),
		'compliance' => array( 'dried-fish-process-validation', 'אין לפרסם הוראות ייבוש, חיי מדף או צריכה לפני זיהוי מין, תהליך HACCP ומדידות פעילות מים, אמינים ביוגניים ומיקרוביולוגיה.', 'Do not publish drying instructions, shelf life or consumption guidance before species identification, a HACCP process, and measurements for water activity, biogenic amines and microbiology.' ),
	),
	'dairy' => array(
		'science_he' => 'מין החלב, פסטור או טיפול שקול, תרבית, pH, מלח, פעילות מים, קירור ופתוגנים אינם מאומתים לכל יצרן ואצווה.',
		'science_en' => 'Milk species, pasteurization or equivalent treatment, culture, pH, salt, water activity, chilling and pathogens are not verified for each producer and lot.',
		'sources' => array( 'iraqi-zhazhi-dairy-2023', 'iraqi-basra-raw-milk-safety-2024', 'israel-moh-allergen-survey-2024' ),
		'compliance' => array( 'dairy-batch-and-allergen-validation', 'יש לאמת פסטור, אלרגן חלב, קירור, pH, מלח ומיקרוביולוגיה לכל מוצר ואצווה.', 'Verify pasteurization, milk allergen, chilling, pH, salt and microbiology for every product and lot.' ),
	),
	'fermentation' => array(
		'science_he' => 'המקור אינו קובע אם התהליך הוא תסיסה טבעית או החמצה בחומץ, ואינו מספק מלח, pH בשיווי משקל, פעילות מים, זמן, טמפרטורה או בדיקת אתגר.',
		'science_en' => 'The source does not establish whether the process is natural fermentation or vinegar acidification and supplies no salt, equilibrium pH, water activity, time, temperature or challenge study.',
		'sources' => array( 'iraqi-fermented-foods-2022', 'fda-water-activity', 'israel-moh-food-hygiene' ),
		'compliance' => array( 'fermentation-process-and-shelf-life-validation', 'אין לקבוע תהליך, בטיחות או חיי מדף בלי נוסחה, pH בשיווי משקל, פעילות מים, מיקרוביולוגיה ואריזה מאומתים.', 'Do not set process, safety or shelf life without a validated formula, equilibrium pH, water activity, microbiology and packaging.' ),
	),
	'dates' => array(
		'science_he' => 'זן, שלב הבשלה, בריקס, לחות, פעילות מים, pH, צמיגות, עומס חימום ו-HMF אינם נמדדים למוצר המסוים, ואין להסיק תועלת רפואית מן הקטגוריה.',
		'science_en' => 'Cultivar, ripeness stage, Brix, moisture, water activity, pH, viscosity, heat load and HMF are not measured for the specific product, and no medical benefit follows from the category.',
		'sources' => array( 'fao-iraq-date-palm-ocop', 'unesco-date-palm-2022' ),
		'compliance' => array( 'date-product-measurement-and-claims-boundary', 'דרגת מוצר, חיי מדף וטענה תזונתית דורשים מפרט אצווה ובדיקות, ללא הבטחה רפואית.', 'Product grade, shelf life and nutrition claims require lot specification and testing, with no medical promise.' ),
	),
	'organ_meat' => array(
		'science_he' => 'זהות האיברים, מקור מפוקח, ניקוי, טיפול מקדים, עקומת חימום, קירור וזיהום צולב אינם מאומתים לרשומה.',
		'science_en' => 'Organ identity, inspected source, cleaning, pretreatment, heating curve, cooling and cross-contamination controls are not validated for this record.',
		'sources' => array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ),
		'compliance' => array( 'organ-meat-inspection-and-thermal-validation', 'הרשומה היא זיהוי פרטי בלבד. אין מתכון או המלצת צריכה לפני אימות מקור, ניקוי, תהליך תרמי וקירור.', 'This is a private identity record only. No recipe or consumption recommendation is allowed before source, cleaning, thermal process and cooling are validated.' ),
	),
	'wild_plant' => array(
		'science_he' => 'השם המקומי אינו מספיק לזיהוי בוטני. מין, חלק אכיל, בית גידול, איכות מים ומזהמים אינם מאומתים.',
		'science_en' => 'A local name is insufficient for botanical identification. Species, edible part, habitat, water quality and contaminants are not verified.',
		'sources' => array( 'iraq-wild-food-plants-2019', 'israel-moh-food-hygiene' ),
		'compliance' => array( 'wild-plant-botanical-and-contaminant-validation', 'אין הוראות ליקוט או אכילה לפני זיהוי בוטני מקצועי ובדיקת איכות מים, קרקע ומזהמים.', 'No foraging or consumption instructions are allowed before professional botanical identification and water, soil and contaminant testing.' ),
	),
	'dried_plant' => array(
		'science_he' => 'זהות בוטנית, עקומת ייבוש, לחות, פעילות מים, נדיפים, עובש ומיקוטוקסינים אינם מאומתים לאצווה.',
		'science_en' => 'Botanical identity, drying curve, moisture, water activity, volatiles, mold and mycotoxins are not verified for the lot.',
		'sources' => array( 'fda-water-activity', 'israel-moh-food-hygiene' ),
		'compliance' => array( 'dried-plant-water-activity-and-mold-validation', 'יש לאמת מין, תהליך ייבוש, פעילות מים, עובש, מזהמים ואריזה לפני חיי מדף או מכירה.', 'Verify species, drying process, water activity, mold, contaminants and packaging before shelf life or sale.' ),
	),
	'gluten' => array(
		'science_he' => 'מין הדגן, דרגת גריסה, גלוטן, ספיחה וזיהום צולב אינם אחידים ואינם מאומתים למוצר או למנה.',
		'science_en' => 'Cereal species, particle grade, gluten, absorption and cross-contact are not uniform and are not verified for the product or dish.',
		'sources' => array( 'israel-moh-allergen-survey-2024' ),
		'compliance' => array( 'cereal-identity-and-allergen-validation', 'יש לאמת חיטה וגלוטן, רכיבי תערובת וזיהום צולב. אין לטעון ללא גלוטן לפי שם המנה.', 'Verify wheat and gluten, blend components and cross-contact. Do not make a gluten-free claim from the dish name.' ),
	),
);

$c99_iraqi_depth_rows = array(
	array(
		'dish-masgouf-iraq', 'dish', 'masgouf-iraq', 'hub-iraqi-fish-fire', 'מסגוף עיראקי', 'Iraqi masgouf',
		'Baghdad and the Tigris basin', 'Iraqi river-fish foodways',
		array( 'undp-iraq-united-through-food', 'iraqi-depth-nasrallah-delights', 'iraqi-grilling-pah-2025' ),
		'דג נהר פתוח המזוהה עם צלייה עקיפה ליד אש בבגדאד ובעמק החידקל. הרשומה שומרת על זהות המנה בלי לקבוע מין דג, מרינדה או מרחק אש אחידים.',
		'An opened river fish associated with indirect fire cooking in Baghdad and the Tigris basin. This record preserves the identity without asserting one fish species, marinade or fire distance.',
		array( 'fish', 'open_fire' ), array( 'ingredient-iraqi-freshwater-fish-family', 'technique-masgouf-indirect-fire' ), array(),
		'Commercial culinary documentary photograph of a butterflied whole river fish mounted vertically beside glowing hardwood embers, Baghdad riverside atmosphere, controlled smoke, copper and reed accents, three-quarter view, crisp skin and moist flesh detail'
	),
	array(
		'dish-dolma-iraqi-family', 'dish', 'dolma-iraqi-family', 'hub-iraqi-rice-stews', 'משפחת דולמה עיראקית', 'Iraqi dolma family',
		'Iraq, with regional household variants', 'Iraqi family and communal cooking',
		array( 'undp-iraq-united-through-food', 'iraqi-depth-nasrallah-delights' ),
		'משפחה עיראקית של ירקות ועלים ממולאים, המסודרים בצפיפות ומתבשלים יחד לפני היפוך ההגשה. המילוי, החמיצות וההרכב משתנים בין בתים ואזורים.',
		'An Iraqi family of tightly arranged stuffed vegetables and leaves cooked together and commonly inverted for service. Filling, acidity and composition vary across households and regions.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context', 'technique-iraqi-stuffed-vegetable-cooking' ), array( 'dish-yabraq-yebra' ),
		'Overhead premium food photograph of an inverted Iraqi dolma platter with stuffed onions, vine leaves, eggplant, peppers and tomatoes arranged as a precise mosaic, glossy cooking juices, dark stone table, generous family-service composition'
	),
	array(
		'dish-tepsi-baytinijan-iraq', 'dish', 'tepsi-baytinijan-iraq', 'region-iraq-baghdad', 'טפסי בתינגאן עיראקי', 'Iraqi tepsi baytinijan',
		'Baghdad and central Iraq', 'Iraqi urban home cooking',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'תבנית חצילים עיראקית שכבתית עם ירקות ורוטב עגבניות, ולעיתים בשר. הרשומה מגדירה משפחת מנה ולא נוסחה יחידה או יחס רכיבים מחייב.',
		'An Iraqi layered eggplant tray with vegetables and tomato sauce, sometimes including meat. The record defines a dish family rather than one mandatory formula or ingredient ratio.',
		array( 'meat' ), array(), array(),
		'Editorial studio photograph of Iraqi tepsi baytinijan in a round aged copper tray, overlapping roasted eggplant, tomato, onion and pepper layers with a burnished surface, low side angle, warm window light, visible tender textures'
	),
	array(
		'dish-quzi-iraq', 'dish', 'quzi-iraq', 'hub-iraqi-rice-stews', 'קוזי עיראקי', 'Iraqi quzi',
		'Iraq, banquet and celebration contexts', 'Iraqi celebration foodways',
		array( 'undp-iraq-united-through-food', 'iraqi-depth-nasrallah-delights' ),
		'מנת אירוח חגיגית של אורז ובשר, לעיתים עם אגוזים, צימוקים או שכבת מאפה. זהותה העיראקית נשמרת בנפרד מאוזי דמשקאי ומגרסאות אזוריות אחרות.',
		'A celebratory hospitality dish of rice and meat, sometimes with nuts, raisins or a pastry layer. Its Iraqi identity remains separate from Damascene ouzi and other regional forms.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context' ), array( 'dish-ouzi-damascene' ),
		'Grand banquet photograph of Iraqi quzi, aromatic long-grain rice crowned with slow-cooked lamb, toasted almonds and restrained dried fruit, engraved brass platter, oblique hero angle, rich but natural color and individual rice-grain detail'
	),
	array(
		'dish-tashreeb-iraq', 'dish', 'tashreeb-iraq', 'hub-iraqi-rice-stews', 'תשריב עיראקי', 'Iraqi tashreeb',
		'Iraq, urban and rural household variants', 'Iraqi bread-and-broth foodways',
		array( 'undp-iraq-united-through-food', 'iraqi-depth-nasrallah-delights' ),
		'משפחת מנות שבה לחם סופג רוטב או ציר מתובל ומוגש עם ירקות, קטניות או בשר לפי הגרסה. הגבול נשמר מול תריד סורי וערבי אחר.',
		'A family in which bread absorbs seasoned sauce or broth and is served with vegetables, pulses or meat depending on the version. The boundary is retained against Syrian and other Arab tharid identities.',
		array( 'meat', 'gluten' ), array(), array( 'dish-thareed-raqqa-rural' ),
		'Close editorial photograph of Iraqi tashreeb in a shallow glazed bowl, torn flatbread visibly saturated with saffron-colored broth beneath tender vegetables and meat, spoon-level perspective, steam, tactile bread structure'
	),
	array(
		'dish-timman-bagilla-iraq', 'dish', 'timman-bagilla-iraq', 'hub-iraqi-rice-stews', 'תימן באגילה עיראקי', 'Iraqi timman bagilla',
		'Baghdad and central Iraq', 'Iraqi rice and broad-bean foodways',
		array( 'iraqi-depth-nasrallah-delights', 'iraqi-depth-broad-beans-2020' ),
		'אורז עיראקי עם פול ועשבי תיבול, המזוהה עם שילוב דגן וקטנית ועם גרסאות הגשה ביתיות שונות. הזן, יחס המים ושיטת האורז אינם אחידים.',
		'Iraqi rice with broad beans and herbs, associated with a grain-and-pulse combination and varied household service forms. Cultivar, water ratio and rice method are not universal.',
		array( 'rice' ), array( 'ingredient-iraqi-amber-rice-context' ), array(),
		'Bright studio photograph of Iraqi timman bagilla, separate long rice grains threaded with green dill and tender broad beans, shallow white ceramic platter, daylight from the side, fresh green contrast, overhead three-quarter composition'
	),
	array(
		'dish-biryani-iraqi-family', 'dish', 'biryani-iraqi-family', 'hub-iraqi-rice-stews', 'משפחת ביריאני עיראקית', 'Iraqi biryani family',
		'Iraqi cities and diaspora households', 'Iraqi festive rice foodways',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'משפחה עיראקית של אורז מתובל עם תוספות משתנות כגון בשר, עוף, ירקות, אטריות, אגוזים או פירות יבשים. אין להשליך ממנה על זהויות ביריאני אחרות.',
		'An Iraqi family of seasoned rice with varying additions such as meat, chicken, vegetables, noodles, nuts or dried fruit. It should not be used to collapse other biryani identities.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context' ), array(),
		'Festive Iraqi biryani photographed on a wide turquoise ceramic platter, jewel-like rice with peas, fine noodles, toasted nuts, restrained raisins and browned meat pieces, overhead editorial layout, high ingredient separation'
	),
	array(
		'dish-margat-bamia-iraq', 'dish', 'margat-bamia-iraq', 'hub-iraqi-rice-stews', 'מרגת במיה עיראקית', 'Iraqi margat bamia',
		'Iraq, household stew tradition', 'Iraqi stew and rice service',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'תבשיל במיה עיראקי ברוטב עגבניות חמצמץ, לעיתים עם בשר, המוגש בהקשרים רבים לצד אורז. רשומה זו אינה קובעת סמיכות, חומציות או נתח אחידים.',
		'An Iraqi okra stew in a tangy tomato sauce, often with meat and commonly served beside rice. This record does not prescribe one viscosity, acidity or cut.',
		array( 'meat' ), array(), array(),
		'Moody close-up of Iraqi margat bamia in a deep earthenware bowl, intact green okra and tender meat in a glossy red tomato broth, small mound of rice nearby, soft directional light, honest stew texture'
	),
	array(
		'dish-kahi-geymar-baghdad', 'dish', 'kahi-geymar-baghdad', 'region-iraq-baghdad', 'קאהי וגיימר בגדאדי', 'Baghdadi kahi with geymar',
		'Baghdad', 'Baghdadi breakfast and bakery culture',
		array( 'iraqi-depth-nasrallah-delights', 'iraqi-zhazhi-dairy-2023' ),
		'מאפה קאהי פריך ושכבתי המוגש בבגדאד עם גיימר, ולעיתים עם סילאן או דבש. זהו הקשר תרבותי, לא מפרט שומן או תהליך חלבי מאומת.',
		'Crisp layered kahi pastry served in Baghdad with geymar, sometimes accompanied by date syrup or honey. This is a cultural context, not a verified fat specification or dairy process.',
		array( 'dairy', 'gluten' ), array( 'ingredient-iraqi-geymar-dairy-context' ), array(),
		'Morning bakery photograph of flaky Baghdadi kahi folded into golden layers beside a generous cloud of white geymar and a small pour of dark date syrup, marble cafe table, soft sunrise light, macro pastry flake detail'
	),
	array(
		'dish-pacha-iraq', 'dish', 'pacha-iraq', 'region-iraq-baghdad', 'פאצ\'ה עיראקית', 'Iraqi pacha',
		'Baghdad and wider Iraq', 'Iraqi specialist and communal foodways',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'משפחת מנה עיראקית המבוססת על חלקי פנים וראש או רגליים לפי המסורת המקומית. הרשומה פרטית ומזהה בלבד עד אימות מקור, ניקוי ותהליך תרמי.',
		'An Iraqi dish family based on offal and head or feet according to local practice. This private record is identification-only until source, cleaning and thermal process are validated.',
		array( 'organ_meat' ), array(), array(),
		'Restrained documentary culinary photograph of an Iraqi pacha service in a traditional metal bowl, carefully arranged cooked components in clear aromatic broth, bread alongside, respectful non-graphic presentation, warm restaurant light'
	),
	array(
		'dish-uruq-baghdadi', 'dish', 'uruq-baghdadi', 'region-iraq-baghdad', 'ערוק בגדאדי', 'Baghdadi uruq',
		'Baghdad', 'Baghdadi patty and fritter tradition',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'קציצה או לביבה בגדאדית מתובלת, המופיעה בגרסאות בשר וירק שונות. היא נשמרת כישות עיראקית נפרדת מערוק הג\'זירה הסורי.',
		'A seasoned Baghdadi patty or fritter with varying meat and vegetable versions. It remains a distinct Iraqi entity from the Syrian Jazira al-uruq identity.',
		array( 'meat' ), array(), array( 'dish-al-uruq-jazira' ),
		'Street-food editorial photograph of small Baghdadi uruq patties with crisp dark-golden edges and a herb-flecked interior, arranged on paper over a metal tray, pickles and herbs at the edge, close side light'
	),
	array(
		'dish-kubba-mosul', 'dish', 'kubba-mosul', 'hub-iraqi-kubba-family', 'קובה מוסול', 'Kubba Mosul',
		'Mosul and Ninewa', 'Moslawi culinary heritage',
		array( 'iraqi-depth-foodish-kubba-mosul-2026', 'uomosul-moslawi-food-heritage', 'mosul-heritage-intangible-food' ),
		'קובה מוסול שטוחה ורחבה, עם מעטפת דגן ומילוי מתובל, היא זהות מוסלאווית מובחנת. הרשומה אינה מאחדת אותה עם משפחות קיבה חלביות או לבנוניות.',
		'Flat, broad Kubba Mosul with a cereal shell and seasoned filling is a distinct Moslawi identity. The record does not merge it with Aleppine or Lebanese kibbeh families.',
		array( 'meat', 'gluten' ), array( 'ingredient-iraqi-bulgur-jreesh-context', 'technique-iraqi-kubba-shell-rheology' ), array( 'hub-aleppine-kibbeh-family', 'hub-lebanese-kibbeh-family' ),
		'High-detail studio photograph of a large flat Kubba Mosul cut into a clean wedge, thin bulgur shell enclosing an even spiced meat layer, hammered tray, neutral limestone background, cross-section in sharp focus'
	),
	array(
		'dish-kubba-halab-iraqi-rice-shell', 'dish', 'kubba-halab-iraqi-rice-shell', 'hub-iraqi-kubba-family', 'קובה חלב עיראקית במעטפת אורז', 'Iraqi rice-shell kubba Halab',
		'Iraq, including Baghdadi usage', 'Iraqi kubba nomenclature and practice',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'ישות עיראקית הנקראת קובה חלב ומזוהה עם מעטפת אורז ומילוי. השם אינו מעביר בעלות לחלב. הישות אינה מחליפה את משפחת הקיבה החלבית הסורית.',
		'An Iraqi entity called kubba Halab and associated with a rice shell and filling. The name does not transfer ownership to Aleppo. This entity does not replace the Syrian Aleppine kibbeh family.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context', 'technique-iraqi-kubba-shell-rheology' ), array( 'region-syria-aleppo', 'hub-aleppine-kibbeh-family', 'hub-lebanese-kibbeh-family' ),
		'Clean culinary studio image of oval Iraqi rice-shell kubba Halab, one opened to reveal a dark aromatic meat center inside a pale crisp rice shell, matte black plate, raking light, fine shell granule detail'
	),
	array(
		'dish-lahm-bi-ajin-mosul', 'dish', 'lahm-bi-ajin-mosul', 'region-iraq-mosul-ninewa', 'לחם בעג\'ין מוסלאווי', 'Moslawi lahm bi ajin',
		'Mosul and Ninewa', 'Moslawi bakery and market foodways',
		array( 'uomosul-moslawi-food-heritage', 'mosul-heritage-intangible-food' ),
		'מאפה בשר מוסלאווי בעל הקשר מקומי של בצק, תיבול ואפייה. הוא נשמר כישות נפרדת ממאפי בשר של חלב, לבנון ואזורים אחרים.',
		'A Moslawi meat pastry with a local context of dough, seasoning and baking. It remains separate from meat pastries of Aleppo, Lebanon and other regions.',
		array( 'meat', 'gluten' ), array(), array(),
		'Artisanal bakery photograph of Moslawi lahm bi ajin, thin round dough with finely textured spiced meat topping and lightly blistered rim, stone oven surface, flour dust, overhead angle, no modern branding'
	),
	array(
		'dish-hareesa-ninewa-shared', 'dish', 'hareesa-ninewa-shared', 'region-iraq-mosul-ninewa', 'הריסה של נינווה, מורשת משותפת', 'Ninewa hareesa, shared heritage',
		'Ninewa Plains', 'Shared Ninewa communal and ceremonial foodways',
		array( 'iraqi-depth-ninewa-everyday-peace-2023', 'uomosul-moslawi-food-heritage' ),
		'דייסת דגן ובשר בבישול ממושך המופיעה בהקשרים קהילתיים משותפים במישורי נינווה. אין לייחס אותה לקהילה יחידה או לקבוע מתכון אחיד.',
		'A long-cooked cereal and meat porridge appearing in shared communal contexts of the Ninewa Plains. It should not be assigned to a single community or treated as one universal recipe.',
		array( 'meat', 'gluten' ), array( 'ingredient-iraqi-bulgur-jreesh-context' ), array(),
		'Documentary food photograph of Ninewa hareesa in a broad communal copper pot, creamy grain and meat texture being served with a wooden ladle into simple bowls, many hands at the perimeter, respectful shared-table framing'
	),
	array(
		'dish-turshi-mahshi-ninewa', 'dish', 'turshi-mahshi-ninewa', 'region-iraq-mosul-ninewa', 'טורשי מחשי נינווה', 'Ninewa turshi mahshi',
		'Mosul and Ninewa', 'Moslawi preservation and stuffed-vegetable foodways',
		array( 'uomosul-moslawi-food-heritage', 'iraqi-fermented-foods-2022' ),
		'משפחת ירקות ממולאים ומשומרים המקושרת לנינווה. יש להבחין בין כבישה בחומץ לבין תסיסה טבעית ולא לקבוע חיי מדף לפי השם.',
		'A family of stuffed preserved vegetables associated with Ninewa. Vinegar acidification must be distinguished from natural fermentation, and shelf life cannot be inferred from the name.',
		array( 'fermentation' ), array( 'ingredient-iraqi-turshi-vegetable-family', 'technique-iraqi-turshi-fermentation' ), array(),
		'Premium pantry photograph of Ninewa stuffed pickled vegetables in a clear unbranded glass vessel, small eggplants and peppers revealing a textured filling, measured brine line, dark cool background, macro botanical detail'
	),
	array(
		'dish-mutabbaq-samak-basra', 'dish', 'mutabbaq-samak-basra', 'region-iraq-basra-shatt-al-arab', 'מוטבק סמכ בסראווי', 'Basrawi mutabbaq samak',
		'Basra and Shatt al-Arab', 'Basrawi fish and rice foodways',
		array( 'undp-iraq-united-through-food', 'iraqi-depth-nasrallah-delights' ),
		'מנת דג ואורז בסראווית בנויה בשכבות או בהרכבה הפוכה לפי המסורת הביתית. מין הדג, תיבול האורז ושיטת ההרכבה משתנים.',
		'A Basrawi fish-and-rice dish assembled in layers or inverted according to household practice. Fish species, rice seasoning and assembly method vary.',
		array( 'fish', 'rice' ), array( 'ingredient-iraqi-freshwater-fish-family', 'ingredient-iraqi-amber-rice-context' ), array(),
		'Hero photograph of Basrawi mutabbaq samak on an oval platter, deeply browned fish resting over spiced amber rice with caramelized onion accents, Shatt al-Arab inspired reed backdrop, angled natural light'
	),
	array(
		'dish-masmouta-basra', 'dish', 'masmouta-basra', 'region-iraq-basra-shatt-al-arab', 'מסמוטה בסראווית', 'Basrawi masmouta',
		'Basra and southern Iraq', 'Basrawi dried-fish tradition',
		array( 'iraqi-depth-uobasrah-dried-fish-2026', 'undp-iraq-united-through-food' ),
		'מסמוטה היא הקשר בסראווי לדג מיובש או מומלח ולעיבודו הקולינרי. הרשומה אינה מספקת הוראות ייבוש, בטיחות, צריכה או חיי מדף.',
		'Masmouta is a Basrawi context for dried or salted fish and its culinary handling. This record supplies no drying, safety, consumption or shelf-life instructions.',
		array( 'dried_fish' ), array( 'ingredient-basra-dried-fish-family', 'technique-iraqi-dried-fish-preservation' ), array(),
		'Restrained heritage still life of Basrawi masmouta context, cleaned dried fish presented on woven palm matting with coarse salt and a covered preparation bowl, airy shaded setting, non-graphic documentary styling, visible dried texture'
	),
	array(
		'dish-sayadiyah-basra', 'dish', 'sayadiyah-basra', 'region-iraq-basra-shatt-al-arab', 'סיאדיה בסראווית', 'Basrawi sayadiyah',
		'Basra and southern Iraqi waterways', 'Basrawi fish and rice foodways',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'סיאדיה בסראווית היא זהות מקומית של דג ואורז עם בצל ותיבול משתנים. היא מקושרת להשוואה אך אינה מתמזגת עם סיאדיה לבנונית או סורית חופית.',
		'Basrawi sayadiyah is a local fish-and-rice identity with varying onion and seasoning practices. It is linked for comparison but not merged with Lebanese or Syrian coastal sayadiyah.',
		array( 'fish', 'rice' ), array( 'ingredient-iraqi-freshwater-fish-family', 'ingredient-iraqi-amber-rice-context' ), array( 'dish-sayadiyah-lebanon', 'dish-sayadiyah-syrian-coast' ),
		'Editorial overhead photograph of Basrawi sayadiyah, caramel-brown rice with flaked river fish and dark fried onion strands, shallow brass platter, citrus at the edge, strong grain definition, southern Iraqi table atmosphere'
	),
	array(
		'dish-kharet-marshes', 'dish', 'kharet-marshes', 'region-iraq-marshes-south', 'ח' . "'" . 'ריט של ביצות דרום עיראק', 'Kharet of the southern Iraqi Marshes',
		'Southern Iraqi Marshes', 'Marsh Arab seasonal plant foodways',
		array( 'unesco-ahwar-2016', 'iraq-cbd-marshlands-traditional-knowledge', 'iraq-wild-food-plants-2019' ),
		'רשומת מורשת לצמח מזון עונתי המכונה ח' . "'" . 'ריט בהקשר ביצות דרום עיראק. השם המקומי לבדו אינו זיהוי בוטני ואינו בסיס להוראות ליקוט או אכילה.',
		'A heritage record for a seasonal food plant called kharet in the southern Iraqi Marshes context. The local name alone is not botanical identification and cannot support foraging or consumption guidance.',
		array( 'wild_plant' ), array(), array(),
		'Environmental culinary documentary photograph of a carefully gathered seasonal marsh plant food known as kharet displayed beside reeds and clean water, palm-fiber tray, close botanical detail, no claim of species identification'
	),
	array(
		'dish-qeema-najafiya', 'dish', 'qeema-najafiya', 'region-iraq-middle-euphrates', 'קיימה נג\'פיה', 'Najafi qeema',
		'Najaf and the Middle Euphrates', 'Najafi pilgrimage hospitality',
		array( 'unesco-arbaeen-hospitality-2019', 'iraq-handbook-peoples-heritage-2024' ),
		'תבשיל בשר וקטניות המזוהה עם נג\'ף ועם אירוח המוני בתקופות עלייה לרגל. הרשומה אינה קובעת יחס רכיבים, מנת הגשה או פרוטוקול ייצור המוני.',
		'A meat-and-pulse stew associated with Najaf and large-scale pilgrimage hospitality. The record does not prescribe ingredient ratios, serving size or a mass-production protocol.',
		array( 'meat' ), array(), array(),
		'Documentary hospitality photograph of Najafi qeema, finely textured meat and chickpea stew ladled from a large immaculate steel pot into simple bowls, warm saffron-brown color, organized communal service, dignified human context'
	),
	array(
		'dish-daheen-najaf', 'dish', 'daheen-najaf', 'region-iraq-middle-euphrates', 'דהין נג\'פי', 'Najafi daheen',
		'Najaf', 'Najafi sweet and bakery heritage',
		array( 'iraqi-depth-nasrallah-delights', 'iraq-handbook-peoples-heritage-2024' ),
		'מתוק אפוי המזוהה עם נג\'ף, בעל פני שטח כהים ומרקם עשיר המשתנה בין יצרנים. הרשומה אינה קובעת מתכון, ערך תזונתי או חיי מדף.',
		'A baked sweet associated with Najaf, with a dark surface and rich texture that vary among makers. The record sets no recipe, nutritional value or shelf life.',
		array( 'general' ), array(), array(),
		'Luxury bakery close-up of Najafi daheen cut into neat diamond portions, dark caramelized top scattered with sesame, dense moist crumb visible at one edge, brass tray, low warm light, artisanal texture without packaging'
	),
	array(
		'dish-kleicha-iraq', 'dish', 'kleicha-iraq', 'hub-iraqi-bread-bakery', 'קלייצ\'ה עיראקית', 'Iraqi kleicha',
		'Iraq, with regional and community variants', 'Iraqi holiday and hospitality baking',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'משפחת מאפים עיראקית ממולאת או מתובלת, המזוהה עם חג, אירוח וגרסאות קהילתיות שונות. סוג המילוי, החותמת והבצק אינם אחידים.',
		'An Iraqi family of filled or spiced pastries associated with holidays, hospitality and distinct community variants. Filling, stamp and dough are not uniform.',
		array( 'gluten' ), array(), array(),
		'Festive pastry photograph of assorted Iraqi kleicha, date-filled spirals and stamped rounds arranged on an engraved silver tray, cardamom pods and dates nearby, soft celebratory light, precise crust and filling cross-sections'
	),
	array(
		'dish-samoon-stone-baghdad', 'dish', 'samoon-stone-baghdad', 'hub-iraqi-bread-bakery', 'סמון אבן בגדאדי', 'Baghdadi stone samoon',
		'Baghdad', 'Baghdadi bakery and sandwich culture',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'לחם סמון בגדאדי בעל צורה מאורכת והקשר אפייה על משטח חם או בתנור מאפייה. הקמח, ההידרציה והתסיסה דורשים מפרט יצרן נפרד.',
		'A Baghdadi samoon bread with an elongated form and a bakery context involving a hot surface or oven. Flour, hydration and fermentation require a producer-specific specification.',
		array( 'gluten' ), array(), array(),
		'Baghdad bakery photograph of elongated stone-baked samoon loaves with pointed ends, pale golden blistered crust and one torn airy crumb, stacked in a wicker basket beside a masonry oven, crisp morning light'
	),
	array(
		'dish-kubba-shwandar-iraqi-jewish-family', 'dish', 'kubba-shwandar-iraqi-jewish-family', 'hub-iraqi-jewish-foodways', 'משפחת קובה שוונדר יהודית עיראקית', 'Iraqi Jewish kubba shwandar family',
		'Iraqi Jewish households and diaspora', 'Iraqi Jewish foodways',
		array( 'iraqi-depth-jfs-beet-kubbeh-2022', 'iraq-handbook-peoples-heritage-2024' ),
		'משפחת קובה במרק סלק חמוץ מתוק בהקשר יהודי עיראקי, עם שונות במעטפת, במילוי ובאיזון הטעמים. הקישור למנת הסלק הציבורית הוא הפניה בלבד.',
		'A kubba family in sweet-sour beet broth within Iraqi Jewish foodways, with variation in shell, filling and flavor balance. The public beet-dish link is reference-only.',
		array( 'meat', 'gluten' ), array( 'ingredient-iraqi-semolina-kubba-context', 'technique-iraqi-kubba-shell-rheology' ), array( 'hub-aleppine-kibbeh-family', 'hub-lebanese-kibbeh-family' ),
		'Vivid editorial photograph of Iraqi Jewish kubba shwandar, pale dumplings in a naturally ruby beet broth with beet wedges and celery leaves, white bowl, overhead daylight, one cut dumpling showing filling, no decorative claims'
	),
	array(
		'dish-kubba-hamusta-iraqi-kurdish-jewish-family', 'dish', 'kubba-hamusta-iraqi-kurdish-jewish-family', 'hub-iraqi-jewish-foodways', 'משפחת קובה חמוסטה יהודית כורדית עיראקית', 'Iraqi Kurdish Jewish kubba hamusta family',
		'Iraqi Kurdistan, Duhok and diaspora households', 'Iraqi Kurdish Jewish foodways',
		array( 'iraqi-depth-foodish-kubbeh-hamusta-2024', 'iraqi-depth-jfs-duhok-kubbeh-2018' ),
		'משפחת כיסוני קובה במרק ירוק וחמצמץ בהקשר יהודי כורדי עיראקי. סוג העלים, מקור החמיצות והמעטפת משתנים בין משפחות.',
		'A kubba dumpling family in a green sour broth within Iraqi Kurdish Jewish foodways. Leaf mixture, souring agent and shell vary among families.',
		array( 'meat', 'gluten' ), array( 'ingredient-iraqi-semolina-kubba-context', 'technique-iraqi-kubba-shell-rheology' ), array( 'hub-aleppine-kibbeh-family', 'hub-lebanese-kibbeh-family' ),
		'Fresh culinary photograph of Iraqi Kurdish Jewish kubba hamusta in luminous green herb broth, rounded dumplings with one cross-section, celery and leafy greens visible, deep ceramic bowl, diffused side light, natural acidity cues only'
	),
	array(
		'dish-kubba-batata-iraqi-jewish-family', 'dish', 'kubba-batata-iraqi-jewish-family', 'hub-iraqi-jewish-foodways', 'משפחת קובה בטטה יהודית עיראקית', 'Iraqi Jewish kubba batata family',
		'Iraqi Jewish households and diaspora', 'Iraqi Jewish holiday and home foodways',
		array( 'iraqi-depth-jfs-kubbeh-batata-2023' ),
		'קובה תפוחי אדמה ממולאת בהקשר יהודי עיראקי, לעיתים מטוגנת ומוגשת באירועים ביתיים. סוג תפוח האדמה, המילוי והשומן אינם אחידים.',
		'A filled potato kubba within Iraqi Jewish foodways, sometimes fried and served at home occasions. Potato type, filling and cooking fat are not uniform.',
		array( 'meat', 'gluten' ), array( 'technique-iraqi-kubba-shell-rheology' ), array( 'hub-aleppine-kibbeh-family', 'hub-lebanese-kibbeh-family' ),
		'Studio food photograph of golden Iraqi Jewish kubba batata fritters, one sliced open to show seasoned meat inside a smooth potato shell, parchment-lined tray, gentle top light, crisp crumb and creamy shell contrast'
	),
	array(
		'dish-tbit-iraqi-jewish-family', 'dish', 'tbit-iraqi-jewish-family', 'hub-iraqi-jewish-foodways', 'טבית יהודית עיראקית', 'Iraqi Jewish tbit',
		'Baghdad, Iraqi Jewish households and diaspora', 'Iraqi Jewish Shabbat foodways',
		array( 'iraqi-depth-jfs-tbit-2024', 'iraq-handbook-peoples-heritage-2024' ),
		'מנה יהודית עיראקית של עוף ואורז המתבשלת זמן ממושך בהקשר שבת, עם גרסאות מילוי ותיבול משפחתיות. הרשומה אינה מפרסמת תהליך לילה בטוח.',
		'An Iraqi Jewish chicken-and-rice dish cooked for an extended period in a Shabbat context, with family-specific stuffing and seasoning. This record does not publish a safe overnight process.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context', 'technique-iraqi-rice-cooling-hot-holding' ), array(),
		'Warm editorial photograph of Iraqi Jewish tbit, deeply bronzed whole chicken nestled in mahogany spiced rice with visible separate grains, heavy covered pot opened at table, low amber light, no unsafe holding cues'
	),
	array(
		'dish-sambusak-btawa-iraqi-jewish-family', 'dish', 'sambusak-btawa-iraqi-jewish-family', 'hub-iraqi-jewish-foodways', 'סמבוסק בטאווה יהודי עיראקי', 'Iraqi Jewish sambusak btawa',
		'Iraqi Jewish households and diaspora', 'Iraqi Jewish pan-cooked pastry tradition',
		array( 'iraqi-depth-foodish-sambusak-btawa-2025' ),
		'כיסון בצק יהודי עיראקי המבושל במחבת ומופיע בגרסאות מילוי ביתיות. הרשומה מתעדת זהות והקשר בלי לקבוע עובי בצק, שומן או טמפרטורה.',
		'An Iraqi Jewish dough pocket cooked in a pan and made with varied household fillings. The record documents identity and context without prescribing dough thickness, fat or temperature.',
		array( 'gluten' ), array(), array(),
		'Home-kitchen editorial photograph of Iraqi Jewish sambusak btawa, crescent pastries browning in a seasoned pan, one opened to reveal its savory filling, linen cloth and wooden board, intimate side light, crisp edge detail'
	),
	array(
		'dish-kichree-iraqi-jewish-family', 'dish', 'kichree-iraqi-jewish-family', 'hub-iraqi-jewish-foodways', 'קיצ\'רי יהודי עיראקי', 'Iraqi Jewish kichree',
		'Iraqi Jewish households and diaspora', 'Iraqi Jewish rice and lentil foodways',
		array( 'iraqi-depth-jfs-kichree-2026', 'iraq-handbook-peoples-heritage-2024' ),
		'מנה יהודית עיראקית של אורז ועדשים אדומות, המזוהה עם ארוחה ביתית ומלווה לעיתים ביוגורט או רוטב. היא נשמרת בנפרד ממשפחת המג\'דרה הלבנונית.',
		'An Iraqi Jewish rice-and-red-lentil dish associated with home meals and sometimes served with yogurt or sauce. It remains separate from the Lebanese mujaddara family.',
		array( 'rice', 'dairy' ), array( 'ingredient-iraqi-amber-rice-context' ), array( 'dish-mujaddara-lebanon-family', 'preparation-mujadara-thursday-syrian-jewish' ),
		'Comforting overhead photograph of Iraqi Jewish kichree, creamy orange-red lentils integrated with rice in a shallow bowl, cumin and fried onion accents, a separate small yogurt dish, soft domestic daylight'
	),
	array(
		'dish-ingriyeh-iraqi-jewish-family', 'dish', 'ingriyeh-iraqi-jewish-family', 'hub-iraqi-jewish-foodways', 'אינגרייה יהודית עיראקית', 'Iraqi Jewish ingriyeh',
		'Iraqi Jewish households and diaspora', 'Iraqi Jewish sweet-sour stew foodways',
		array( 'iraqi-depth-jfs-ingriye-2021' ),
		'תבשיל יהודי עיראקי של חציל, עגבניות ובשר באיזון מתוק וחמוץ המשתנה בין משפחות. הרשומה אינה קובעת ריכוז סוכר, חומציות או נתח.',
		'An Iraqi Jewish eggplant, tomato and beef stew with a sweet-sour balance that varies among families. The record does not prescribe sugar concentration, acidity or cut.',
		array( 'meat' ), array(), array(),
		'Rich editorial photograph of Iraqi Jewish ingriyeh, roasted eggplant and tender beef in glossy tomato sauce with a restrained sweet-sour visual profile, shallow cobalt bowl, linen table, close three-quarter framing'
	),
	array(
		'dish-yaprakh-iraqi-kurdistan', 'dish', 'yaprakh-iraqi-kurdistan', 'region-iraq-kurdistan', 'יאפרח של כורדיסטן העיראקית', 'Yaprakh of Iraqi Kurdistan',
		'Iraqi Kurdistan', 'Kurdish Iraqi stuffed-leaf and vegetable foodways',
		array( 'krg-official-cuisine', 'krg-welcome-guide-food-newroz', 'iraqi-depth-jfs-duhok-kubbeh-2018' ),
		'משפחת עלים וירקות ממולאים בכורדיסטן העיראקית, הבנויה לעיתים בשכבות ומיועדת להגשה משותפת. היא מקושרת ליאברק הסורי להשוואה בלבד.',
		'A family of stuffed leaves and vegetables in Iraqi Kurdistan, sometimes layered and intended for communal service. It is linked to Syrian yabraq for comparison only.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context', 'technique-iraqi-stuffed-vegetable-cooking' ), array( 'dish-yabraq-yebra' ),
		'Mountain-table photograph of Iraqi Kurdish yaprakh, stuffed vine leaves, onions and small vegetables packed in concentric layers on a wide platter, fresh herbs and sumac nearby, cool natural daylight, communal abundance'
	),
	array(
		'preparation-masgouf-fire-distance-control', 'preparation', 'masgouf-fire-distance-control', 'hub-iraqi-fish-fire', 'בקרת מרחק אש במסגוף', 'Masgouf fire-distance control',
		'Baghdad and the Tigris basin', 'Iraqi river-fish fire practice',
		array( 'undp-iraq-united-through-food', 'iraqi-grilling-pah-2025' ),
		'הכנת מסגוף דורשת הפרדה בין זהות מסורתית לבין פרוטוקול מדיד של דלק, מרחק, זמן, טמפרטורה ועשן. הרשומה מגדירה את פערי האימות ואינה מתכון.',
		'Masgouf preparation requires separating traditional identity from a measurable protocol for fuel, distance, time, temperature and smoke. This record defines validation gaps and is not a recipe.',
		array( 'fish', 'open_fire' ), array( 'ingredient-iraqi-freshwater-fish-family', 'technique-masgouf-indirect-fire' ), array( 'dish-masgouf-iraq' ),
		'Technical culinary photograph of a masgouf fire station showing a whole butterflied fish held at a clear indirect distance from glowing embers, visible thermometer probe and clean airflow, side elevation, no numeric labels'
	),
	array(
		'preparation-iraqi-dolma-stack-and-inversion', 'preparation', 'iraqi-dolma-stack-and-inversion', 'hub-iraqi-rice-stews', 'סידור והיפוך דולמה עיראקית', 'Iraqi dolma stacking and inversion',
		'Iraq, regional household practice', 'Iraqi family and communal cooking',
		array( 'undp-iraq-united-through-food', 'iraqi-depth-nasrallah-delights' ),
		'הכנה מבנית של ירקות ועלים ממולאים בצפיפות כדי לתמוך בבישול ובהיפוך. גודל, יחס נוזלים, משקל וכיסוי חייבים להימדד לכל נוסחה.',
		'A structural preparation in which stuffed vegetables and leaves are packed tightly to support cooking and inversion. Size, liquid ratio, load and cover require measurement for each formula.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context', 'technique-iraqi-stuffed-vegetable-cooking' ), array( 'dish-dolma-iraqi-family' ),
		'Process-focused overhead photograph of an Iraqi dolma pot before cooking, stuffed onions, vine leaves, peppers and eggplants packed in precise concentric layers, clean stainless work surface, hands arranging the final layer'
	),
	array(
		'preparation-hkaka-iraqi-rice-crust', 'preparation', 'hkaka-iraqi-rice-crust', 'hub-iraqi-rice-stews', 'חקאקה, שכבת האורז העיראקית', 'Hkaka, Iraqi rice crust',
		'Iraq, household rice practice', 'Iraqi rice technique context',
		array( 'iraqi-depth-nasrallah-delights', 'iraqi-rice-bacillus-2026' ),
		'חקאקה היא ההקשר העיראקי לשכבת אורז מושחמת בתחתית הסיר. סוג האורז, השומן, עובי השכבה ועקומת החימום אינם נגזרים מן השם.',
		'Hkaka is the Iraqi context for a browned rice layer at the bottom of the pot. Rice type, fat, layer thickness and heating curve cannot be inferred from the name.',
		array( 'rice' ), array( 'ingredient-iraqi-amber-rice-context' ), array(),
		'Macro studio photograph of a single intact Iraqi hkaka rice crust lifted from a heavy pot, crisp amber underside and distinct white grains above, neutral background, raking light emphasizing brittle and tender layers'
	),
	array(
		'preparation-sabich-iraqi-jewish-breakfast-context', 'preparation', 'sabich-iraqi-jewish-breakfast-context', 'hub-iraqi-jewish-foodways', 'הקשר ארוחת הבוקר היהודית העיראקית של סביח', 'Iraqi Jewish breakfast context of sabich',
		'Iraqi Jewish households and Israeli public food culture', 'Iraqi Jewish Shabbat breakfast foodways',
		array( 'iraqi-depth-jfs-sabich-2018', 'iraq-handbook-peoples-heritage-2024' ),
		'רשומת הקשר הקושרת רכיבי ארוחת בוקר יהודית עיראקית אל סביח הציבורי בלי לשנות את בעלות עמוד המנה. עמבה דורשת זהות תהליך נפרדת ואימות מוצר.',
		'A context record linking Iraqi Jewish breakfast components to the public sabich dish without changing ownership of the dish page. Amba requires a separate process identity and product validation.',
		array( 'fermentation' ), array( 'ingredient-iraqi-amba-process-context' ), array(),
		'Editorial breakfast-table photograph showing eggplant, cooked eggs, tahini, chopped salad, Iraqi-style amba in an unbranded bowl and fresh pita as separate components, bright natural light, no assembled commercial sandwich'
	),
	array(
		'ingredient-iraqi-amber-rice-context', 'ingredient', 'iraqi-amber-rice-context', 'hub-iraqi-rice-stews', 'הקשר אורז ענבר עיראקי', 'Iraqi amber rice context',
		'Iraq, cultivar identity requires lot verification', 'Iraqi rice agriculture and cooking',
		array( 'iraq-handbook-peoples-heritage-2024', 'iraqi-rice-bacillus-2026', 'iraqi-depth-nasrallah-delights' ),
		'ישות הקשר לאורז המכונה ענבר בעיראק ולמקומו במטבח המקומי. שם מסחרי לבדו אינו מאמת זן, מקור, גיל, ארומה, עמילוז או התאמה למנה.',
		'A context entity for rice called amber in Iraq and its place in local cooking. A trade name alone does not verify cultivar, origin, age, aroma, amylose or suitability for a dish.',
		array( 'rice' ), array(), array(),
		'Premium ingredient photograph of loose Iraqi amber-rice context grains in a shallow handmade ceramic bowl, a few translucent grains spread on dark stone for inspection, soft side light, macro grain geometry, no packaging'
	),
	array(
		'ingredient-iraqi-bulgur-jreesh-context', 'ingredient', 'iraqi-bulgur-jreesh-context', 'hub-iraqi-kubba-family', 'הקשר בורגול וג׳ריש עיראקי', 'Iraqi bulgur and jreesh context',
		'Iraq, including Mosul and Ninewa', 'Iraqi cereal processing and kubba practice',
		array( 'uomosul-moslawi-food-heritage', 'iraqi-depth-nasrallah-delights', 'iraq-handbook-peoples-heritage-2024' ),
		'ישות דגן להבחנה בין דרגות בורגול וג׳ריש המשמשות במנות עיראקיות. יש לאמת מין חיטה, גודל חלקיק, טיפול מקדים, ספיחה וגלוטן לכל מוצר.',
		'A cereal entity distinguishing bulgur and jreesh grades used in Iraqi dishes. Wheat species, particle size, pretreatment, absorption and gluten require product-level verification.',
		array( 'gluten' ), array(), array( 'ingredient-syrian-bulgur', 'ingredient-lebanese-bulgur-context' ),
		'Ingredient taxonomy photograph of three Iraqi cereal textures, fine bulgur, coarse bulgur and cracked jreesh, separated in small unlabeled brass bowls, overhead scientific styling, exact particle detail, neutral background'
	),
	array(
		'ingredient-iraqi-semolina-kubba-context', 'ingredient', 'iraqi-semolina-kubba-context', 'hub-iraqi-kubba-family', 'הקשר סולת למעטפת קובה עיראקית', 'Iraqi semolina kubba-shell context',
		'Iraq and Iraqi Jewish diaspora households', 'Iraqi kubba shell practice',
		array( 'iraqi-depth-jfs-kubbeh-green-beans-2021', 'iraqi-depth-jfs-beet-kubbeh-2022', 'iraqi-depth-foodish-kubbeh-hamusta-2024' ),
		'סולת מופיעה בהקשרים של מעטפות קובה עיראקיות ויהודיות עיראקיות. דרגת הטחינה, החלבון, ספיחת המים והערבוב אינם אחידים בין מוצרים.',
		'Semolina appears in Iraqi and Iraqi Jewish kubba-shell contexts. Milling grade, protein, water absorption and mixing behavior are not uniform across products.',
		array( 'gluten' ), array(), array(),
		'Macro commercial photograph of pale semolina granules beside a small hand-shaped kubba shell sample cut open before filling, slate laboratory-style surface, raking light, particle-scale texture, no measurement claims'
	),
	array(
		'ingredient-iraqi-date-cultivars-context', 'ingredient', 'iraqi-date-cultivars-context', 'hub-iraqi-date-palm', 'הקשר זני תמר עיראקיים', 'Iraqi date-cultivar context',
		'Iraqi date-growing regions', 'Iraqi date-palm heritage and agriculture',
		array( 'fao-iraq-date-palm-ocop', 'unesco-date-palm-2022' ),
		'ישות מטרייה לזני תמר עיראקיים ולשלבי הבשלה שונים. כל שם זן, אזור, דרגה, בריקס, לחות ואיכות חייבים להיות מאומתים ברמת ספק ואצווה.',
		'An umbrella entity for Iraqi date cultivars and different ripeness stages. Cultivar name, region, grade, Brix, moisture and quality must be verified at supplier and lot level.',
		array( 'dates' ), array(), array(),
		'Museum-quality ingredient photograph of several visibly different Iraqi date-cultivar contexts arranged in separate unlabeled palm-leaf compartments, colors from amber to deep mahogany, macro skin texture, diffused daylight, no varietal claims'
	),
	array(
		'ingredient-iraqi-date-syrup-dibs', 'ingredient', 'iraqi-date-syrup-dibs', 'hub-iraqi-date-palm', 'דיבס, סילאן תמרים עיראקי', 'Iraqi date syrup, dibs',
		'Iraq, producer and cultivar require verification', 'Iraqi date processing and pantry use',
		array( 'fao-iraq-date-palm-ocop', 'unesco-date-palm-2022', 'iraqi-depth-nasrallah-delights' ),
		'דיבס הוא הקשר עיראקי לסירופ תמרים מרוכז המשמש במזווה ובמנות שונות. מקור התמר, שיטת המיצוי, בריקס, טיפול חום ותוספים דורשים מפרט מוצר.',
		'Dibs is an Iraqi context for concentrated date syrup used across the pantry and dishes. Date source, extraction method, Brix, heat treatment and additives require a product specification.',
		array( 'dates' ), array(), array(),
		'Commercial ingredient photograph of thick Iraqi date syrup dibs falling in a continuous ribbon from a small copper spoon into an unbranded dark-glass bowl, whole dates nearby, macro viscosity detail, controlled warm highlights'
	),
	array(
		'ingredient-noomi-basra-dried-lime', 'ingredient', 'noomi-basra-dried-lime', 'region-iraq-basra-shatt-al-arab', 'נומי בסרה, ליים מיובש', 'Noomi Basra, dried lime',
		'Basra naming context, botanical and production origin require verification', 'Iraqi and Gulf dried-citrus pantry context',
		array( 'iraqi-depth-nasrallah-delights', 'undp-iraq-united-through-food' ),
		'נומי בסרה הוא שם הקשר לליים מיובש במטבח העיראקי. יש לאמת מין בוטני, מקור, ייבוש, לחות, עובש, נדיפים וחומרי טיפול בכל אצווה.',
		'Noomi Basra is a context name for dried lime in Iraqi cooking. Botanical species, origin, drying, moisture, mold, volatiles and processing aids require lot verification.',
		array( 'dried_plant' ), array(), array(),
		'High-resolution pantry photograph of whole noomi Basra dried limes, matte tan to charcoal shells with one carefully split to reveal dry chambers, raw linen and dark ceramic, low side light, botanical texture, no origin label'
	),
	array(
		'ingredient-iraqi-amba-process-context', 'ingredient', 'iraqi-amba-process-context', 'hub-iraqi-fermentation-preservation', 'הקשר תהליך עמבה עיראקית', 'Iraqi amba process context',
		'Iraq and Iraqi Jewish diaspora contexts', 'Iraqi mango condiment foodways',
		array( 'iraqi-depth-jfs-sabich-2018', 'iraqi-fermented-foods-2022', 'iraqi-depth-nasrallah-delights' ),
		'ישות תהליך פרטית לעמבה בהקשר עיראקי, שאינה מחליפה את ישות העמבה הציבורית. מנגו, מלח, חומצה ותבלינים עשויים לעבור מסלולי הכנה שונים, ואין להניח שכל עמבה מותססת או בטוחה לאחסון חדר.',
		'A private process entity for amba in Iraqi contexts that does not replace the public amba entity. Mango, salt, acid and spices may follow different preparation paths, and not every amba is fermented or safe for room-temperature storage.',
		array( 'fermentation' ), array(), array(),
		'Ingredient-process photograph of Iraqi-style amba context, golden mango condiment in an unlabeled glass bowl beside green mango pieces, fenugreek and mustard spices kept visibly separate, clean studio bench, no fermentation bubbles implied'
	),
	array(
		'ingredient-iraqi-turshi-vegetable-family', 'ingredient', 'iraqi-turshi-vegetable-family', 'hub-iraqi-fermentation-preservation', 'משפחת ירקות טורשי עיראקית', 'Iraqi turshi vegetable family',
		'Iraq, with strong regional variation', 'Iraqi preservation and table-condiment foodways',
		array( 'iraqi-fermented-foods-2022', 'uomosul-moslawi-food-heritage', 'iraqi-depth-nasrallah-delights' ),
		'משפחת ירקות משומרים עיראקית הכוללת הרכבים ותהליכים שונים. יש להפריד בין תסיסה, החמצה בחומץ, המלחה ומוצר ממולא ולמדוד כל נוסחה.',
		'An Iraqi family of preserved vegetables with varied compositions and processes. Fermentation, vinegar acidification, salting and stuffed products must be separated and each formula measured.',
		array( 'fermentation' ), array(), array(),
		'Curated pantry photograph of an Iraqi turshi vegetable family displayed as separate small batches of turnip, cucumber, cauliflower, pepper and eggplant in clear unbranded vessels, cool daylight, distinct natural colors, no shelf-life cue'
	),
	array(
		'ingredient-iraqi-freshwater-fish-family', 'ingredient', 'iraqi-freshwater-fish-family', 'hub-iraqi-fish-fire', 'משפחת דגי מים מתוקים עיראקיים', 'Iraqi freshwater-fish family',
		'Tigris, Euphrates and southern marsh-water systems', 'Iraqi river and marsh foodways',
		array( 'unesco-ahwar-2016', 'iraq-cbd-marshlands-traditional-knowledge', 'undp-iraq-united-through-food' ),
		'ישות מטרייה לדגי מים מתוקים המשמשים בהקשרים עיראקיים. שם מנה אינו מזהה מין, מקור, שיטת גידול או דיג, עונתיות, מזהמים או שרשרת קירור.',
		'An umbrella entity for freshwater fish used in Iraqi contexts. A dish name does not identify species, source, capture or farming method, seasonality, contaminants or cold chain.',
		array( 'fish' ), array(), array(),
		'Scientific culinary still life of several whole freshwater-fish silhouettes on crushed ice in a clean unbranded market tray, species intentionally not labeled, silver scale detail, cool directional light, reeds softly blurred behind'
	),
	array(
		'ingredient-basra-dried-fish-family', 'ingredient', 'basra-dried-fish-family', 'hub-iraqi-fermentation-preservation', 'משפחת דגים מיובשים של בסרה', 'Basra dried-fish family',
		'Basra and southern Iraq', 'Basrawi dried-fish preservation heritage',
		array( 'iraqi-depth-uobasrah-dried-fish-2026', 'undp-iraq-united-through-food' ),
		'ישות מורשת לדגים מיובשים או מומלחים בהקשר בסראווי. אין להסיק ממנה מין דג, ריכוז מלח, פעילות מים, בטיחות, הוראות צריכה או חיי מדף.',
		'A heritage entity for dried or salted fish in Basrawi contexts. It does not establish species, salt concentration, water activity, safety, consumption instructions or shelf life.',
		array( 'dried_fish' ), array(), array(),
		'Conservation-style ingredient photograph of a Basra dried-fish family sample resting on a raised palm-fiber rack in open shade, coarse salt crystals and desiccated skin texture visible, clean non-graphic frame, no retail packaging'
	),
	array(
		'ingredient-iraqi-geymar-dairy-context', 'ingredient', 'iraqi-geymar-dairy-context', 'hub-iraqi-community-foodways', 'הקשר גיימר עיראקי', 'Iraqi geymar dairy context',
		'Baghdad and southern Iraqi dairy traditions', 'Iraqi breakfast dairy foodways',
		array( 'iraqi-depth-nasrallah-delights', 'iraqi-zhazhi-dairy-2023', 'iraqi-basra-raw-milk-safety-2024' ),
		'גיימר הוא מוצר שמנת עשיר בהקשר עיראקי, אך מין החלב, אחוז השומן, הפסטור, ריכוז המוצקים והקירור משתנים בין יצרנים ודורשים אימות.',
		'Geymar is a rich cream product in Iraqi contexts, but milk species, fat percentage, pasteurization, solids concentration and chilling vary among producers and require verification.',
		array( 'dairy' ), array(), array(),
		'Premium dairy photograph of Iraqi geymar context with thick white folds in a small chilled ceramic bowl, a clean spoon lifting the dense surface, cool marble, soft diffuse light, no raw-milk or fat-percentage implication'
	),
	array(
		'ingredient-iraqi-aushari-zhazhi-dairy-context', 'ingredient', 'iraqi-aushari-zhazhi-dairy-context', 'region-iraq-kurdistan', 'הקשר מוצרי חלב אושרי וז׳אז׳י עיראקיים', 'Iraqi aushari and zhazhi dairy context',
		'Iraqi Kurdistan, producer identity requires verification', 'Kurdish Iraqi cultured-dairy foodways',
		array( 'iraqi-zhazhi-dairy-2023', 'krg-official-cuisine', 'krg-welcome-guide-food-newroz' ),
		'ישות הקשר למונחי מוצרי חלב אושרי וז׳אז׳י בכורדיסטן העיראקית. יש לאמת מין חלב, תרבית, מליחות, pH, פסטור, קירור וזהות יצרן.',
		'A context entity for aushari and zhazhi dairy terms in Iraqi Kurdistan. Milk species, culture, salt, pH, pasteurization, chilling and producer identity require verification.',
		array( 'dairy', 'fermentation' ), array(), array(),
		'Highland dairy still life of Iraqi Kurdish aushari and zhazhi contexts presented in two separate unbranded earthenware bowls, one spoonable and one firmer, woven wool textile, cool mountain daylight, no process claims'
	),
	array(
		'technique-masgouf-indirect-fire', 'technique', 'masgouf-indirect-fire', 'hub-iraqi-fish-fire', 'צלייה עקיפה של מסגוף', 'Masgouf indirect-fire technique',
		'Baghdad and the Tigris basin', 'Iraqi river-fish fire practice',
		array( 'undp-iraq-united-through-food', 'iraqi-grilling-pah-2025' ),
		'טכניקת זהות של פתיחת דג והצבתו ליד מקור חום ולא כטיגון או אפייה סגורה. תהליך תפעולי דורש זיהוי דלק ומדידת מרחק, זמן, עשן וטמפרטורות.',
		'An identity technique in which an opened fish is positioned beside a heat source rather than fried or enclosed in an oven. An operating process requires fuel identification and measurements of distance, time, smoke and temperatures.',
		array( 'fish', 'open_fire' ), array( 'ingredient-iraqi-freshwater-fish-family' ), array( 'dish-masgouf-iraq' ),
		'Training photograph of an indirect masgouf fire geometry, butterflied fish upright on a clean metal support beside a defined ember bed with air gap clearly visible, side-on composition, thermometer probe, no written annotations'
	),
	array(
		'technique-iraqi-kubba-shell-rheology', 'technique', 'iraqi-kubba-shell-rheology', 'hub-iraqi-kubba-family', 'ראולוגיית מעטפת קובה עיראקית', 'Iraqi kubba-shell rheology',
		'Iraq, with grain-specific regional variants', 'Iraqi kubba craft and food science',
		array( 'iraqi-depth-foodish-kubba-mosul-2026', 'iraqi-depth-jfs-kubbeh-green-beans-2021', 'iraqi-depth-foodish-kubbeh-hamusta-2024' ),
		'מעטפת קובה עיראקית עשויה להתבסס על בורגול, אורז, סולת או תפוח אדמה לפי הישות. גודל חלקיק, ספיחת מים, חלבון, עמילן, לישה ועובי דורשים ניסוי נפרד.',
		'An Iraqi kubba shell may use bulgur, rice, semolina or potato depending on the entity. Particle size, hydration, protein, starch, mixing and thickness require separate trials.',
		array( 'meat', 'gluten' ), array(), array( 'hub-aleppine-kibbeh-family', 'hub-lebanese-kibbeh-family' ),
		'Food-science process photograph of four small unlabeled kubba-shell test samples with visibly different grain matrices and wall thicknesses, one clean cross-section each, neutral steel bench, macro raking light, no numeric performance claims'
	),
	array(
		'technique-iraqi-stuffed-vegetable-cooking', 'technique', 'iraqi-stuffed-vegetable-cooking', 'hub-iraqi-rice-stews', 'בישול ירקות ממולאים עיראקיים', 'Iraqi stuffed-vegetable cooking',
		'Iraq and Iraqi Kurdistan', 'Iraqi dolma and yaprakh practice',
		array( 'undp-iraq-united-through-food', 'iraqi-depth-nasrallah-delights', 'krg-official-cuisine' ),
		'טכניקה לאריזה, סידור ובישול של ירקות ועלים ממולאים. גודל יחידה, יחס אורז ובשר, נפח נוזל, דחיסה, חימום וקירור חייבים להיקבע לכל מוצר.',
		'A technique for filling, arranging and cooking stuffed vegetables and leaves. Unit size, rice-to-meat ratio, liquid volume, compression, heating and cooling must be set for each product.',
		array( 'rice', 'meat' ), array( 'ingredient-iraqi-amber-rice-context' ), array( 'dish-dolma-iraqi-family', 'dish-yaprakh-iraqi-kurdistan' ),
		'Instructional culinary photograph of gloved hands packing assorted Iraqi stuffed vegetables into a pot by size and shape, cutaway vessel revealing layered arrangement and measured liquid space, clean production-kitchen lighting, no text'
	),
	array(
		'technique-iraqi-rice-cooling-hot-holding', 'technique', 'iraqi-rice-cooling-hot-holding', 'hub-iraqi-rice-stews', 'קירור והחזקה חמה של אורז עיראקי', 'Iraqi rice cooling and hot holding',
		'Iraq-facing production operations', 'Applied food-safety control for Iraqi rice dishes',
		array( 'iraqi-rice-bacillus-2026', 'israel-moh-food-hygiene' ),
		'שכבת בקרה החלה על מנות אורז עיראקיות לאחר הבישול. התרבות אינה קובעת גבולות בטיחות, ולכן נדרשים תהליך זמן וטמפרטורה, עומק מגש, רישום אצווה ואימות.',
		'A control layer applied to Iraqi rice dishes after cooking. Culture does not set safety limits, so a time-and-temperature process, tray depth, batch record and validation are required.',
		array( 'rice' ), array( 'ingredient-iraqi-amber-rice-context' ), array( 'dish-tbit-iraqi-jewish-family' ),
		'Food-operations photograph of cooked rice divided into shallow stainless trays beside a calibrated probe and a separate covered hot-holding pan, clean professional kitchen, visible batch workflow without readable numbers or labels'
	),
	array(
		'technique-iraqi-turshi-fermentation', 'technique', 'iraqi-turshi-fermentation', 'hub-iraqi-fermentation-preservation', 'בקרת תסיסה והחמצה של טורשי עיראקי', 'Iraqi turshi fermentation and acidification control',
		'Iraq, formula-specific application', 'Iraqi preservation food science',
		array( 'iraqi-fermented-foods-2022', 'fda-water-activity', 'israel-moh-food-hygiene' ),
		'טכניקה המפרידה בין תסיסה טבעית, החמצה בחומץ והמלחה. לכל נוסחה נדרשים אחוזי מלח לפי משקל, pH בשיווי משקל, זמן, טמפרטורה, פעילות מים ומיקרוביולוגיה.',
		'A technique separating natural fermentation, vinegar acidification and salting. Every formula requires salt by weight, equilibrium pH, time, temperature, water activity and microbiology.',
		array( 'fermentation' ), array( 'ingredient-iraqi-turshi-vegetable-family' ), array(),
		'Controlled fermentation-bench photograph of separate Iraqi turshi test vessels with vegetables fully submerged under weights, clean airlock-style covers, calibrated pH probe and salt scale nearby, cool laboratory light, no readable values'
	),
	array(
		'technique-iraqi-date-syrup-concentration', 'technique', 'iraqi-date-syrup-concentration', 'hub-iraqi-date-palm', 'ריכוז דיבס תמרים עיראקי', 'Iraqi date-syrup concentration',
		'Iraqi date-processing context', 'Iraqi date-palm processing science',
		array( 'fao-iraq-date-palm-ocop', 'unesco-date-palm-2022' ),
		'טכניקת ריכוז לסירופ תמרים שבה עומס חום, סינון ואידוי משפיעים על צבע, צמיגות ותרכובות חימום. אין לקבוע בריקס, HMF או חיי מדף בלי בדיקה.',
		'A date-syrup concentration technique in which heat load, filtration and evaporation affect color, viscosity and heat-derived compounds. Brix, HMF and shelf life cannot be set without testing.',
		array( 'dates' ), array( 'ingredient-iraqi-date-cultivars-context', 'ingredient-iraqi-date-syrup-dibs' ), array(),
		'Process photograph of clarified date extract concentrating in a small steam-jacketed vessel, dark syrup sampled into a glass viscosity cup, filter cloth and whole dates in background, warm controlled light, no endpoint numbers'
	),
	array(
		'technique-iraqi-dried-fish-preservation', 'technique', 'iraqi-dried-fish-preservation', 'hub-iraqi-fermentation-preservation', 'בקרת שימור דגים מיובשים עיראקיים', 'Iraqi dried-fish preservation control',
		'Basra and southern Iraq, process requires facility validation', 'Basrawi preservation heritage and applied food safety',
		array( 'iraqi-depth-uobasrah-dried-fish-2026', 'fda-water-activity', 'israel-moh-food-hygiene' ),
		'שכבת מדע פרטית למיפוי המלחה וייבוש דגים בהקשר דרום עיראק. מין, עובי, קליטת מלח, לחות, פעילות מים, חמצון, אמינים ביוגניים, מזיקים ומיקרוביולוגיה דורשים אימות.',
		'A private science layer for mapping fish salting and drying in southern Iraqi contexts. Species, thickness, salt uptake, humidity, water activity, oxidation, biogenic amines, pests and microbiology require validation.',
		array( 'dried_fish' ), array( 'ingredient-basra-dried-fish-family' ), array( 'dish-masmouta-basra' ),
		'Food-safety documentation photograph of cleaned fish samples on a stainless drying rack inside a controlled cabinet, separate salt sample, humidity and water-activity instruments visible without readable results, sterile neutral lighting'
	),
	array(
		'technique-iraqi-cultured-dairy-control', 'technique', 'iraqi-cultured-dairy-control', 'region-iraq-kurdistan', 'בקרת מוצרי חלב מתורבתים עיראקיים', 'Iraqi cultured-dairy control',
		'Iraqi Kurdistan and Iraqi dairy production', 'Kurdish Iraqi dairy science and safety',
		array( 'iraqi-zhazhi-dairy-2023', 'iraqi-basra-raw-milk-safety-2024', 'israel-moh-allergen-survey-2024' ),
		'טכניקת בקרה למוצרי חלב מתורבתים המחייבת זיהוי חלב ותרבית, טיפול תרמי, pH לאורך זמן, מלח, קירור, אלרגן ומיקרוביולוגיה. שם מורשת אינו תהליך מאומת.',
		'A control technique for cultured dairy requiring milk and culture identity, heat treatment, pH over time, salt, chilling, allergen control and microbiology. A heritage name is not a validated process.',
		array( 'dairy', 'fermentation' ), array( 'ingredient-iraqi-aushari-zhazhi-dairy-context' ), array(),
		'Clean dairy-lab photograph of cultured milk in small stainless vessels with a calibrated probe, culture packet represented as an unbranded sealed sachet, chilled sample jars and sanitation tools, cool high-key light, no numeric claims'
	),
);

$c99_iraqi_depth_entities = array();

foreach ( $c99_iraqi_depth_rows as $row ) {
	$risk_codes = empty( $row[11] ) ? array( 'general' ) : $row[11];
	$science_he = array();
	$science_en = array();
	$science_sources = $row[8];
	$compliance = array();
	foreach ( $risk_codes as $risk_code ) {
		$risk = $c99_iraqi_depth_risk_profiles[ $risk_code ];
		$science_he[] = $risk['science_he'];
		$science_en[] = $risk['science_en'];
		$science_sources = array_merge( $science_sources, $risk['sources'] );
		if ( ! empty( $risk['compliance'] ) ) {
			$compliance[] = array( $risk['compliance'][0], $risk['compliance'][1], $risk['compliance'][2], $risk['sources'] );
		}
	}
	$science_sources = array_values( array_unique( $science_sources ) );
	$c99_iraqi_depth_entities[] = $c99_iraqi_build( array(
		'id' => $row[0],
		'type' => $row[1],
		'slug' => $row[2],
		'parent_id' => $row[3],
		'name_he' => $row[4],
		'name_en' => $row[5],
		'region' => $row[6],
		'community' => $row[7],
		'sources' => $row[8],
		'summary_he' => $row[9],
		'summary_en' => $row[10],
		'fact_he' => 'המקורות הרשומים תומכים בשם ובהקשר המתוארים של ' . $row[4] . ' בלבד. הם אינם מוכיחים מקור בלעדי, נוסחה אחידה, זמינות מסחרית או התאמה למוצר מסוים.',
		'fact_en' => 'The listed sources support only the stated name and context of ' . $row[5] . '. They do not prove exclusive origin, a universal formula, commercial availability or suitability for a specific product.',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'science_he' => implode( ' ', $science_he ),
		'science_en' => implode( ' ', $science_en ),
		'science_evidence' => 'editorial_inference',
		'science_sources' => $science_sources,
		'requires' => $row[12],
		'references' => $row[13],
		'compliance' => $compliance,
		'schema_type' => 'Article',
		'prompt_en' => $row[14] . ' Rights-safe editorial photography, no text, no logos, no flags, no medical symbols, no invented packaging and no certification marks.',
	) );
}

$c99_iraqi_depth_counts = array_count_values( array_column( $c99_iraqi_depth_entities, 'type' ) );
$c99_iraqi_depth_expected_counts = array( 'dish' => 32, 'preparation' => 4, 'ingredient' => 12, 'technique' => 8 );
$c99_iraqi_depth_comparable_counts = $c99_iraqi_depth_counts;
ksort( $c99_iraqi_depth_expected_counts );
ksort( $c99_iraqi_depth_comparable_counts );
if ( $c99_iraqi_depth_expected_counts !== $c99_iraqi_depth_comparable_counts || 56 !== count( $c99_iraqi_depth_entities ) ) {
	throw new RuntimeException( 'Complete99 Iraqi regional depth entity counts do not match the v17 contract.' );
}

return array(
	'schema' => 'complete99-iraqi-regional-depth-module/v1',
	'version' => 'culinary-science-2026.08.07.v17',
	'sources' => $c99_iraqi_depth_sources,
	'entities' => $c99_iraqi_depth_entities,
	'private_entity_ids' => array_column( $c99_iraqi_depth_entities, 'id' ),
	'counts' => array(
		'by_type' => $c99_iraqi_depth_counts,
		'total_entities' => count( $c99_iraqi_depth_entities ),
	),
);
