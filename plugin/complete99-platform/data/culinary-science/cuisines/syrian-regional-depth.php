<?php
/**
 * Complete99 Syrian regional cuisine, high-resolution depth tranche.
 *
 * This module extends the Syrian graph with source-bounded city, subregion,
 * community, dish, ingredient, institution and technique entities. The main
 * registry may expose an explicitly reviewed noindex subset; every other
 * identity remains private and reference-only. Family testimony is never
 * promoted to a citywide rule.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_syrian_depth_sources = array(
	'unesco-aleppo-silk-roads' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO Silk Roads Programme',
		'title' => 'Aleppo on the Silk Roads',
		'url' => 'https://en.unesco.org/silkroad/content/aleppo', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'unesco-aleppo-souqs-2025' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO World Heritage Centre',
		'title' => 'Ancient City of Aleppo state of conservation report',
		'url' => 'https://whc.unesco.org/document/224509', 'published_at' => '2025-01-01', 'retrieved_at' => '2026-08-07',
	),
	'unesco-damascene-rose-al-mrah' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO Intangible Cultural Heritage',
		'title' => 'Practices and craftsmanship associated with the Damascene rose in Al-Mrah',
		'url' => 'https://ich.unesco.org/en/RL/practices-and-craftsmanship-associated-with-the-damascene-rose-in-al-mrah-01369', 'published_at' => '2019-01-01', 'retrieved_at' => '2026-08-07',
	),
	'fao-eastern-ghouta-2026' => array(
		'type' => 'official_organization', 'publisher' => 'Food and Agriculture Organization of the United Nations',
		'title' => 'Reviving agriculture in Eastern Ghouta',
		'url' => 'https://www.fao.org/neareast/news/stories/details/reviving-agriculture-in-eastern-ghouta/en', 'published_at' => '2026-05-01', 'retrieved_at' => '2026-08-07',
	),
	'hsa-aleppo-pepper-profile' => array(
		'type' => 'official_organization', 'publisher' => 'Herb Society of America',
		'title' => 'Aleppo Pepper Profile',
		'url' => 'https://www.herbsociety.org/file_download/inline/29f66c3c-d921-4372-adce-8667d3aa21bb', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'haj-abdo-official-menu' => array(
		'type' => 'official_business', 'publisher' => 'Haj Abdo',
		'title' => 'Haj Abdo official site and menu',
		'url' => 'https://www.hajabdo.com/en/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'bakdash-official' => array(
		'type' => 'official_business', 'publisher' => 'Bakdash',
		'title' => 'Bakdash official site',
		'url' => 'https://bakdashgroup.com/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'ritsumeikan-damascus-midan-sweets' => array(
		'type' => 'official_organization', 'publisher' => 'Ritsumeikan University Asia-Japan Research Institute',
		'title' => 'Damascus al-Midan sweets research essay',
		'url' => 'https://www.ritsumei.ac.jp/research/aji/publication/essay1/vol3/number03/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'sana-qamar-al-din-production-2026' => array(
		'type' => 'official_organization', 'publisher' => 'Syrian Arab News Agency',
		'title' => 'Qamar al-Din production report',
		'url' => 'https://sana.sy/en/miscellaneous/2326374/', 'published_at' => '2026-01-01', 'retrieved_at' => '2026-08-07',
	),
	'sana-buzuriyah-market-2026' => array(
		'type' => 'official_organization', 'publisher' => 'Syrian Arab News Agency',
		'title' => 'Souq al-Buzuriyah spice market report',
		'url' => 'https://sana.sy/en/culture-and-arts/2301650/', 'published_at' => '2026-03-01', 'retrieved_at' => '2026-08-07',
	),
	'jfs-kibbeh-charola' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Kibbeh Charola, Savory Bulgur Pie',
		'url' => 'https://www.jewishfoodsociety.org/recipes/kibbeh-charola-savory-bulgur-pie', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-sigi-mantel-archive' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'The Syrian grandmother who taught Sigi Mantel',
		'url' => 'https://www.jewishfoodsociety.org/stories/the-syrian-grandmother-who-taught-sigi-mantel-where', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-mujadara-thursday' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Mujadara with rice, lentils and fried onions',
		'url' => 'https://www.jewishfoodsociety.org/recipes/mujadara-rice-with-lentils-and-fried-onions', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-adjwe-date-crescents' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Adjwe date-filled semolina cookies',
		'url' => 'https://www.jewishfoodsociety.org/recipes/adjwe-date-filled-semolina-cookies', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'forward-syrian-shabbat-bread' => array(
		'type' => 'official_organization', 'publisher' => 'The Forward',
		'title' => 'Syrian flatbread and Shabbat meals',
		'url' => 'https://forward.com/food/140505/shabbat-meals-syrian-flatbread-transcends-generat/', 'published_at' => '2011-07-01', 'retrieved_at' => '2026-08-07',
	),
	'misham-damascus-community' => array(
		'type' => 'official_organization', 'publisher' => 'Organization of Jews from Damascus in Israel',
		'title' => 'Damascene Jewish community archive',
		'url' => 'https://misham.org.il/index817d.html?page_id=2183', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'asif-medias-damascene' => array(
		'type' => 'official_organization', 'publisher' => 'Asif, Culinary Institute of Israel',
		'title' => 'Medias, beef and eggplants',
		'url' => 'https://asif.org/en/recipes/medias-beef-eggplants/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'ifrepo-syria-coast-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Syrian coast recipes and cultures',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_4.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'fao-syria-coastal-fish-marketing' => array(
		'type' => 'official_organization', 'publisher' => 'Food and Agriculture Organization of the United Nations',
		'title' => 'Syrian coastal fish marketing',
		'url' => 'https://www.fao.org/4/w5690e/w5690e03.htm', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'fao-syria-coastal-citrus' => array(
		'type' => 'official_organization', 'publisher' => 'Food and Agriculture Organization of the United Nations',
		'title' => 'Syrian coastal citrus systems',
		'url' => 'https://www.fao.org/4/Y4890E/y4890e0k.htm', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'ifrepo-qamishli-assyrian-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Assyrian and Qamishli recipes and cultures',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_2.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'northpress-jazira-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'North Press Agency',
		'title' => 'Jazira culinary traditions',
		'url' => 'https://npasyria.com/182383/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'ifrepo-deir-ez-zor-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Deir ez-Zor and Al-Bukamal recipes and cultures',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_6.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'ifrepo-raqqa-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Raqqa recipes and techniques',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_3.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'ifrepo-hauran-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Daraa and Hauran recipes and cultures',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_10.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'enab-southern-syrian-heritage-2025' => array(
		'type' => 'official_organization', 'publisher' => 'Enab Baladi',
		'title' => 'Southern Syrian heritage and shared traditions',
		'url' => 'https://english.enabbaladi.net/archives/2025/08/southern-syrian-heritage-a-history-of-coexistence-and-shared-traditions-beyond-crises/', 'published_at' => '2025-08-01', 'retrieved_at' => '2026-08-07',
	),
	'mdpi-suwayda-madafa-2025' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Heritage, MDPI',
		'title' => 'Suwayda madafah and Hasel al-Door heritage study',
		'url' => 'https://www.mdpi.com/2571-9408/8/11/487', 'published_at' => '2025-11-01', 'retrieved_at' => '2026-08-07',
	),
	'enab-suwayda-grape-molasses' => array(
		'type' => 'official_organization', 'publisher' => 'Enab Baladi',
		'title' => 'Grape molasses in Suwayda',
		'url' => 'https://english.enabbaladi.net/archives/2018/10/grape-molasses-the-most-famous-traditional-industry-in-sweida-governorate/', 'published_at' => '2018-10-01', 'retrieved_at' => '2026-08-07',
	),
	'daraa24-hauran-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'Daraa 24',
		'title' => 'Popular foods of Hauran',
		'url' => 'https://daraa24.org/%D8%A7%D9%84%D9%85%D8%A3%D9%83%D9%88%D9%84%D8%A7%D8%AA-%D8%A7%D9%84%D8%B4%D8%B9%D8%A8%D9%8A%D8%A9-%D9%81%D9%8A-%D8%AD%D9%88%D8%B1%D8%A7%D9%86/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
);

$c99_syrian_depth_entities = array();

$c99_syrian_depth_build = static function ( $spec ) use ( $c99_syrian_entity, $c99_syrian_fact, $c99_relation, $c99_text ) {
	$sources = $spec['sources'];
	$facts = array(
		$c99_syrian_fact(
			'fact-' . $spec['slug'] . '-documented-scope',
			isset( $spec['dimension'] ) ? $spec['dimension'] : 'cultural',
			$spec['fact_he'],
			$spec['fact_en'],
			isset( $spec['evidence'] ) ? $spec['evidence'] : 'official_source',
			isset( $spec['value_scope'] ) ? $spec['value_scope'] : 'entity',
			$sources
		),
	);
	$science_he = isset( $spec['science_he'] ) ? $spec['science_he'] : '';
	$science_en = isset( $spec['science_en'] ) ? $spec['science_en'] : '';
	if ( '' === $science_he && in_array( $spec['type'], array( 'dish', 'preparation', 'ingredient', 'technique' ), true ) ) {
		$science_he = 'המקור מזהה רכיבים או שיטת הכנה, אך אינו מספק מדידת pH, בריקס, פעילות מים, עקומת טמפרטורה או חיי מדף מאומתים למנה או למוצר.';
		$science_en = 'The source identifies components or a preparation method but supplies no measured pH, Brix, water activity, temperature curve or validated shelf life for the dish or product.';
	}
	if ( '' !== $science_he && '' !== $science_en ) {
		$facts[] = $c99_syrian_fact(
			'fact-' . $spec['slug'] . '-science-boundary',
			'scientific',
			$science_he,
			$science_en,
			'official_source',
			'entity',
			$sources
		);
	}
	$relations = array();
	if ( ! empty( $spec['parent_id'] ) ) {
		$relations[] = $c99_relation(
			'part_of',
			$spec['parent_id'],
			'הישות נשמרת תחת ההקשר האזורי או הקהילתי המתועד שלה.',
			'The entity remains under its documented regional or community context.',
			false,
			$sources,
			'official_source'
		);
	}
	foreach ( isset( $spec['requires'] ) ? $spec['requires'] : array() as $target_id ) {
		$relations[] = $c99_relation(
			'requires',
			$target_id,
			'הרכיב מתועד בהקשר זה, אך סוג המוצר, הכמות והטיפול דורשים אימות לפני מתכון או הצעה מסחרית.',
			'The component is documented in this context, but product identity, quantity and handling require verification before a recipe or offer.',
			false,
			$sources,
			'official_source'
		);
	}
	foreach ( isset( $spec['references'] ) ? $spec['references'] : array() as $target_id ) {
		$relations[] = $c99_relation(
			'references',
			$target_id,
			'הקשר זה מקושר לישות נפרדת כדי לשמור על הבחנה בין שמות, גרסאות ואזורים.',
			'This context links to a separate entity so names, variants and regions remain distinct.',
			false,
			$sources,
			'official_source'
		);
	}
	return $c99_syrian_entity( array(
		'id' => $spec['id'],
		'type' => $spec['type'],
		'slug' => $spec['slug'],
		'parent_id' => $spec['parent_id'],
		'name' => $c99_text( $spec['name_he'], $spec['name_en'] ),
		'summary' => $c99_text( $spec['summary_he'], $spec['summary_en'] ),
		'region' => $spec['region'],
		'community' => isset( $spec['community'] ) ? $spec['community'] : 'syrian-multi-community',
		'primary_intent' => $c99_text(
			isset( $spec['intent_he'] ) ? $spec['intent_he'] : 'לגלות את ' . $spec['name_he'] . ' בתוך ההקשר האזורי, המשפחתי והקולינרי המתועד.',
			isset( $spec['intent_en'] ) ? $spec['intent_en'] : 'Explore ' . $spec['name_en'] . ' within its documented regional, family and culinary context.'
		),
		'primary_keyword' => $c99_text(
			isset( $spec['keyword_he'] ) ? $spec['keyword_he'] : $spec['name_he'] . ' במטבח הסורי',
			isset( $spec['keyword_en'] ) ? $spec['keyword_en'] : $spec['name_en'] . ' Syrian cuisine guide'
		),
		'schema_type' => isset( $spec['schema_type'] ) ? $spec['schema_type'] : 'Article',
		'facts' => $facts,
		'relations' => $relations,
		'cross_sell_ids' => isset( $spec['cross_sell_ids'] ) ? $spec['cross_sell_ids'] : array(),
		'prompt_en' => $spec['prompt_en'],
		'compliance' => isset( $spec['compliance'] ) ? $spec['compliance'] : array(),
	) );
};

/*
 * Compact row format:
 * id, type, slug, parent, Hebrew name, English name, region, source IDs,
 * Hebrew summary, English summary, Hebrew fact, English fact, required entity
 * IDs, visual prompt, optional community.
 */
$c99_syrian_depth_rows = array(
	/* Regional and subregional owners. */
	array(
		'region-syria-idlib-maarrat', 'topic_hub', 'idlib-maarrat-cuisine', 'cuisine-syrian-regional',
		'מטבח אידליב ומערת א-נועמאן', 'Idlib and Maarrat al-Numan Cuisine', 'syria-idlib-maarrat', array( 'avs-rahma-idlib' ),
		'שער אזורי למנות ולשיטות שתועדו בעדות משפחתית מאידליב ומערת א-נועמאן, ובהן ארמן, בצק בשר מקומי, כדורי בורגול ללא בשר וקוואג׳.',
		'A regional gateway for dishes and methods recorded in an Idlib and Maarrat al-Numan family testimony, including arman, a local meat flatbread, meatless bulgur balls and kawaj.',
		'העדות מתארת מטבח אזורי ומשפחתי ואינה מוכיחה שכל משפחה באידליב מכינה את המנות באותה דרך.',
		'The testimony documents a regional family repertoire and does not establish one method for every Idlib household.',
		array(), 'Editorial culinary atlas of Idlib and Maarrat al-Numan, four clearly separated food studies on a limestone table, neutral documentary styling.'
	),
	array(
		'region-syria-afrin-depth', 'topic_hub', 'afrin-cuisine-depth', 'region-syria-aleppo',
		'מטבח עפרין לעומק', 'Afrin Cuisine in Depth', 'syria-afrin', array( 'avs-amani-afrin' ),
		'שער משנה המשמר את עפרין כמסגרת כורדית ואזורית מובחנת בתוך מפת צפון סוריה, עם שמן זית, דגנים, קטניות, מוצרי חלב ושמות מנות שעדיין דורשים בירור זהות.',
		'A subregional gateway retaining Afrin as a distinct Kurdish and northern Syrian context, with olive oil, grains, legumes, dairy and dish names that still require identity review.',
		'המקור תומך בסל המזווה ובהקשר המשפחתי, אך אינו מספיק כדי לאחד את בוראנייה וקולקי עם מנות מוכרות מאזורים אחרים.',
		'The source supports the pantry and family context but is insufficient to merge boraniyeh or kulki with similarly named dishes elsewhere.',
		array( 'ingredient-syrian-olive-oil', 'ingredient-syrian-chickpeas', 'ingredient-syrian-lentils', 'ingredient-syrian-bulgur' ),
		'Editorial pantry portrait from Afrin with olive oil, chickpeas, lentils, bulgur and cultured dairy in unbranded vessels, no costume or flag.', 'kurdish-afrin'
	),
	array(
		'region-syria-raqqa', 'topic_hub', 'raqqa-cuisine', 'region-syria-euphrates-east',
		'מטבח א-רקה', 'Raqqa Cuisine', 'syria-raqqa', array( 'avs-rana-raqqa' ),
		'שער למטבח הכפרי שתועד סביב א-רקה, עם תנור, לחם, ת׳ריד, קלייג׳ה, סיאייל, תה חזק, ירקות מיובשים ומנהגי שיתוף.',
		'A gateway to the rural Raqqa repertoire documented around bread ovens, thareed, kleija, siyayil, strong tea, dried vegetables and food sharing.',
		'המקור מבדיל בין ת׳ריד כפרי ללא אורז לבין גרסה עירונית הכוללת אורז, ולכן שתי הזהויות אינן מתערבבות.',
		'The source distinguishes a rural thareed without rice from a city version with rice, so the identities remain separate.',
		array( 'ingredient-raqqa-tannour-bread' ), 'Rural Raqqa culinary study with tannour bread, tea glass, dried okra and a small syruped bread sweet, neutral natural light.'
	),
	array(
		'region-syria-deir-ez-zor', 'topic_hub', 'deir-ez-zor-cuisine', 'region-syria-euphrates-east',
		'מטבח דיר א-זור', 'Deir ez-Zor Cuisine', 'syria-deir-ez-zor', array( 'avs-buthaina-east' ),
		'שער למנות שתועדו בדיר א-זור, ובהן קלייג׳ה, משחמייה, מוחמרה על בצק סאג׳, פאורה וקרן ירוק, בלי לבלוע לתוכו את תדמור.',
		'A gateway for dishes recorded in Deir ez-Zor, including kleija, mshahmiyya, muhammara on saj dough, fawra and qaren yaruq, while keeping Palmyra separate.',
		'עדות אחת מספקת מפת זהויות שימושית, אך כל נוסחת רכיבים דורשת אימות נוסף וניסוי מטבח.',
		'One testimony provides a useful identity map, while each ingredient formula still requires corroboration and kitchen testing.',
		array(), 'Deir ez-Zor culinary atlas with saj dough, stuffed eggplant, kleija and legume stew shown as separate labeled-free studies.'
	),
	array(
		'region-syria-palmyra', 'topic_hub', 'palmyra-cuisine', 'region-syria-euphrates-east',
		'מטבח תדמור', 'Palmyra Cuisine', 'syria-palmyra', array( 'avs-buthaina-east', 'avs-mirvet-aleppo' ),
		'שער נפרד לבורמה ולקישק מחבש של תדמור. הוא מונע ערבוב בין תיאור חגיגי מדגן מלא ובשר לבין מנה חלבית או קובה בעלת שם דומה במקור אחר.',
		'A separate gateway for Palmyrene burma and kishk mhabbash, preventing a whole-grain meat feast account from being merged with a dairy or kibbeh dish carrying a similar name elsewhere.',
		'שני המקורות משתמשים בשם בורמה לזהויות שונות, ולכן השם לבדו אינו בסיס לאיחוד מתכונים.',
		'Two sources use the name burma for different identities, so the name alone cannot justify recipe merging.',
		array(), 'Palmyra food-identity comparison with a clay-coated communal grain pot and a separate kishk-legume bowl, no archaeological props.'
	),
	array(
		'region-syria-qamishli-family-transmission', 'topic_hub', 'qamishli-family-transmission', 'region-syria-jazira',
		'מטבח קמישלי בעדות משפחתית', 'Qamishli Cuisine in Family Transmission', 'syria-qamishli', array( 'avs-samar-qamishli' ),
		'שער לעדות משפחתית על מנסף, קובייבאת או הקט, ג׳וייקאת, קרמה ומנה לבנה לפתיחת רמדאן. העדה עצמה מציינת שלא חיה בקמישלי.',
		'A gateway to family-transmitted accounts of mansaf, kubaybat or haqt, juwayqat, karmah and a white first-Ramadan dish. The narrator explicitly states that she never lived in Qamishli.',
		'המידע תקף לתמסורת המשפחתית המתועדת ואינו מוצג כתצפית ישירה על כלל העיר.',
		'The information is valid for the documented family transmission and is not presented as direct observation of the city as a whole.',
		array(), 'Family-transmission food archive from Qamishli with separate platters and a handwritten-free provenance card area, no claim of citywide uniformity.'
	),
	array(
		'region-syria-latakia', 'topic_hub', 'latakia-cuisine', 'region-syria-coast',
		'מטבח לטקיה', 'Latakia Cuisine', 'syria-latakia', array( 'avs-zainab-coast' ),
		'שער חופי המבחין בין סיאדייה, תוספת אפשרית של ממרח פלפל לאורז, וקובה מבושלת המוגשת עם שני רטבים נפרדים.',
		'A coastal gateway distinguishing sayadiyah, an optional red-pepper-paste rice variation, and boiled kibbeh served with two separate sauces.',
		'תוספת ממרח הפלפל מתועדת כאפשרות בעדות אחת ולא ככלל מחייב לכל סיאדייה לטקיינית.',
		'The red pepper paste is documented as an option in one testimony, not a mandatory rule for all Latakia sayadiyah.',
		array( 'ingredient-syrian-coastal-fish', 'ingredient-syrian-red-pepper-paste' ), 'Latakia coastal food study with fish and rice beside two separate kibbeh sauces, blue-grey ceramic, no resort imagery.'
	),
	array(
		'region-syria-baniyas', 'topic_hub', 'baniyas-cuisine', 'region-syria-coast',
		'מטבח בניאס', 'Baniyas Cuisine', 'syria-baniyas', array( 'avs-zainab-coast' ),
		'שער חופי לבניאס המשמר את סמקה חרה ואת כעכ בחלב כזהויות מקומיות נפרדות.',
		'A Baniyas coastal gateway retaining samaka harra and kaak bi haleeb as separate local identities.',
		'השיוך לבניאס נשמר בגבולות עדות המקור ואינו טענת בלעדיות גאוגרפית.',
		'The Baniyas attribution remains bounded to the source testimony and is not a claim of geographic exclusivity.',
		array( 'ingredient-syrian-coastal-fish' ), 'Baniyas coastal pairing of spicy fish and milk bread on separate plates, restrained culinary documentary photography.'
	),
	array(
		'region-syria-jableh', 'topic_hub', 'jableh-cuisine', 'region-syria-coast',
		'מטבח ג׳בלה', 'Jableh Cuisine', 'syria-jableh', array( 'avs-zainab-coast' ),
		'שער לג׳בלה עם מעמול, כעכ בסומאק וג׳זרייה, תוך הפרדה בין שמות מנות, חומרי גלם וגרסאות משפחתיות.',
		'A Jableh gateway for maamoul, kaak bi sumac and jazariyeh, keeping dish names, ingredients and family versions distinct.',
		'המקור תומך בקיום המנות בעדות אחת ואינו מספק נוסחאות ייצור או תפוצה עירונית מלאה.',
		'The source supports the dishes in one testimony but provides neither production formulas nor full citywide prevalence.',
		array( 'ingredient-syrian-sumac' ), 'Jableh confectionery study with carrot sweet, sumac biscuit and maamoul separated on a pale stone surface.'
	),

	/* Aleppo depth. */
	array(
		'hub-aleppine-kibbeh-family', 'topic_hub', 'aleppine-kibbeh-family', 'region-syria-aleppo',
		'משפחת הקובה החלבית', 'Aleppine Kibbeh Family', 'syria-aleppo', array( 'avs-mirvet-aleppo', 'simon-schuster-aleppo-cookbook', 'georgetown-making-levantine-cuisine' ),
		'קובה חלבית אינה מנה אחת אלא משפחה של צורות וטכניקות: צלויה, מטוגנת, מגולגלת או מבושלת ברוטב, ולעיתים פוגשת סומאק, רימון, חבוש או דובדבן חמוץ. בחרו דרך אחת והמשיכו אל הבורגול, הבשר ושיטת הבישול שמעצבים אותה.',
		'Aleppine kibbeh is not one dish but a family of forms and techniques: grilled, fried, rolled or cooked in sauce, sometimes meeting sumac, pomegranate, quince or sour cherry. Choose one path and continue to the bulgur, meat and cooking method that shape it.',
		'עמוד המו״ל של ספרה של מרלן מטר מציג עשרים מתכוני קובה. זהו תיעוד של ספר בישול שממחיש את רוחב המשפחה, לא מפקד רשמי של כל צורות הקובה בחלב.',
		'The publisher page for Marlene Matar\'s cookbook presents twenty kibbeh recipes. That cookbook documentation illustrates the breadth of the family; it is not an official census of every Aleppine kibbeh form.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat' ), 'Commercial culinary studio comparison of four fully cooked Aleppine kibbeh forms: grilled, fried, rolled and sauce-cooked, each clearly separate, inviting top view, no labels and no claim of a definitive taxonomy.'
	),
	array(
		'technique-aleppine-sour-fruit-cookery', 'technique', 'aleppine-sour-fruit-cookery', 'region-syria-aleppo',
		'בישול חלבי עם פירות חמוצים', 'Aleppine Sour-Fruit Cookery', 'syria-aleppo', array( 'avs-mirvet-aleppo', 'international-academy-gastronomy-syria' ),
		'ציר טכני המקשר דובדבן חמוץ, חבוש, סומאק ורימון למנות בשר וקובה, תוך שמירה על כל מנה כבעלת זהות נפרדת.',
		'A technical axis linking sour cherry, quince, sumac and pomegranate to meat and kibbeh dishes while keeping every dish as a separate identity.',
		'המקורות מתעדים צירוף של בשר, קובה ופירות חמוצים, אך אינם מספקים יחס חומצה, סוכר או זמן בישול אחיד.',
		'The sources document meat, kibbeh and sour-fruit combinations but provide no universal acid, sugar or cooking-time ratio.',
		array( 'ingredient-syrian-sour-cherry', 'ingredient-syrian-quince', 'ingredient-syrian-sumac', 'ingredient-syrian-pomegranate-molasses' ),
		'Culinary technique study of sour cherry, quince, sumac and pomegranate around separate cooked meat preparations, macro texture and natural color.'
	),
	array(
		'dish-kibbeh-somakiyya', 'dish', 'kibbeh-somakiyya', 'hub-aleppine-kibbeh-family',
		'קובה סומאקייה', 'Kibbeh Somakiyya', 'syria-aleppo', array( 'avs-mirvet-aleppo' ),
		'קובה ברוטב חמצמץ המבוסס בעדות של מירוות על סומאק, רכז רימונים ובשר. הרשומה נשמרת כגרסה משפחתית חלבית עד להצלבה נוספת.',
		'A sour kibbeh preparation described by Mirvet with sumac, pomegranate molasses and meat. It remains an Aleppine household version pending further corroboration.',
		'העדות מזהה סומאק, רכז רימונים ובשר כרכיבים מרכזיים, אך אינה בסיס למתכון ציבורי בדוק.',
		'The testimony identifies sumac, pomegranate molasses and meat as central components but is not a tested public recipe.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-sumac', 'ingredient-syrian-pomegranate-molasses' ),
		'Commercial culinary studio photograph of kibbeh somakiyya in a deep burgundy sumac and pomegranate sauce, component boundaries visible, no garnish invention.'
	),
	array(
		'preparation-kibbeh-fried-forms-aleppo', 'preparation', 'aleppine-fried-kibbeh-forms', 'hub-aleppine-kibbeh-family',
		'צורות קובה מטוגנות מחלב', 'Aleppine Fried Kibbeh Forms', 'syria-aleppo', array( 'avs-mirvet-aleppo' ),
		'רשומת השוואה אחת לקובה מקלייה, דרוויש ומוזאת. השמות מתעדים צורות שונות בעדות משפחתית ואינם מפוצלים לשלושה מתכונים עירוניים.',
		'A single comparison record for maqliyya, darawish and mozat. The names describe different forms in a family testimony and are not split into three citywide recipes.',
		'דרוויש מתוארת כמוארכת ופריכה, ומוזאת כצורת בננה עם בשר רזה ואגוזים, בגבולות העדות בלבד.',
		'Darawish is described as elongated and crisp, while mozat is banana-shaped with lean meat and nuts, within the testimony scope only.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-unspecified-nuts' ),
		'Three Aleppine fried kibbeh forms aligned for shape comparison, crisp shell detail, neutral plate, no text and no raw interior.'
	),
	array(
		'dish-kibbeh-mabroumeh', 'dish', 'kibbeh-mabroumeh', 'hub-aleppine-kibbeh-family',
		'קובה מברומה', 'Kibbeh Mabroumeh', 'syria-aleppo', array( 'avs-mirvet-aleppo' ),
		'קובה מגולגלת במילוי פיסטוק כפי שתועדה בעדות חלבית אחת. השם והמבנה נשמרים בנפרד מקובה מטוגנת ומקובה אפויה.',
		'A rolled pistachio-filled kibbeh documented in one Aleppine testimony. Its name and form remain separate from fried and baked kibbeh.',
		'העדות מזהה מבנה מגולגל ופיסטוק, אך אינה מספקת מפרט גרגר, אחוז שומן או טמפרטורת ליבה.',
		'The testimony identifies a rolled form and pistachio but supplies no grain specification, fat percentage or core temperature.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-pistachios' ),
		'Rolled Aleppine kibbeh mabroumeh cut to reveal pistachio filling, controlled studio side light, no decorative claims.'
	),
	array(
		'preparation-saj-kibbeh-aleppo', 'preparation', 'saj-kibbeh-aleppo', 'hub-aleppine-kibbeh-family',
		'קובה סאג׳ חלבית', 'Aleppine Saj Kibbeh', 'syria-aleppo', array( 'avs-mirvet-aleppo' ),
		'צורת קובה המקושרת בעדות החלבית לבישול על סאג׳. היא נשמרת כהכנה נפרדת מן הלחם הדק וממנות בשר אחרות על סאג׳.',
		'A kibbeh form linked in the Aleppine testimony to saj cooking. It remains separate from thin bread and other saj-cooked meat preparations.',
		'המקור מזהה את משטח הסאג׳ אך אינו מספק עובי, טמפרטורת משטח או זמן מגע שנבדקו.',
		'The source identifies the saj surface but supplies no tested thickness, surface temperature or contact time.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'technique-syrian-saj-bread' ),
		'Aleppine saj kibbeh cooking on a clean convex griddle, close side angle, visible browning and no flames touching food.'
	),
	array(
		'ingredient-aleppo-pepper', 'ingredient', 'aleppo-pepper', 'region-syria-aleppo',
		'פלפל חלבי', 'Aleppo Pepper', 'syria-aleppo', array( 'hsa-aleppo-pepper-profile' ),
		'מדריך לזהות המסחרית והקולינרית של פלפל חלבי. שם הסגנון אינו מוכיח שמוצר מסוים גודל בסוריה, ולכן זן, ארץ מקור, עיבוד ומנת ספק חייבים להישמר בנפרד.',
		'A guide to the culinary and market identity of Aleppo pepper. The style name does not prove that a specific product was grown in Syria, so cultivar, country of origin, processing and supplier lot must remain separate.',
		'המקור מתאר פרופיל תבלין, אך אין ברשומה ערך חריפות אוניברסלי או אישור מקור למוצר מסוים.',
		'The source describes a spice profile, while the record makes no universal heat-value or product-origin claim.',
		array(), 'Macro commercial ingredient photograph of coarse deep-red Aleppo-style pepper flakes in an unbranded ceramic dish, visible oil sheen, no origin seal.'
	),
	array(
		'market-souq-al-saqatiyya', 'market', 'souq-al-saqatiyya-aleppo', 'region-syria-aleppo',
		'שוק א-סקטייה בחלב', 'Souq al-Saqatiyya in Aleppo', 'syria-aleppo', array( 'unesco-aleppo-souqs-2025' ),
		'ישות מקום לשוק משנה בעיר העתיקה, עם עדויות שימור מתוארכות. היא אינה משמשת כהוכחה שכל השוק העתיק פעיל או שכל חנות זמינה כעת.',
		'A place entity for a named old-city sub-souq with dated conservation evidence. It does not prove that the entire old market is active or that every shop is currently available.',
		'דוחות שימור מתייחסים למקטעים ולתאריכים מוגדרים, ולכן סטטוס המקום חייב להישמר כתצפית מתוארכת.',
		'Conservation reports concern defined sections and dates, so place status must remain a dated observation.',
		array(), 'Architectural food-market study of a restored Aleppo sub-souq aisle with unbranded nuts and sweets stalls, no crowds or signage text.'
	),
	array(
		'market-souq-al-attarine-aleppo', 'market', 'souq-al-attarine-aleppo', 'region-syria-aleppo',
		'שוק אל-עטארין בחלב', 'Souq al-Attarine in Aleppo', 'syria-aleppo', array( 'unesco-aleppo-souqs-2025' ),
		'ישות מקום לשוק התבלינים והארומטים של חלב, הנשמרת כמקור ניווט ומחקר ולא כספק מאומת של Complete99.',
		'A place entity for Aleppo spice and aromatics trade, retained for navigation and research rather than as a verified Complete99 supplier.',
		'הקשר השוק אינו מוכיח מקור, איכות, מחיר או מלאי של מוצר מסוים.',
		'The market context does not verify origin, quality, price or stock for any specific product.',
		array(), 'Aleppo spice-market still life with whole spices and dried aromatics in open unbranded bins, natural market light, no merchant identity.'
	),
	array(
		'restaurant-haj-abdo', 'restaurant', 'haj-abdo-aleppo', 'region-syria-aleppo',
		'חאג׳ עבדו בחלב', 'Haj Abdo in Aleppo', 'syria-aleppo', array( 'haj-abdo-official-menu' ),
		'ישות מסעדה חיצונית המתעדת זהות, כתובת ותפריט גלוי באתר הרשמי. טענות היסטוריה עצמית נשמרות בנפרד מביקורת עצמאית.',
		'An external restaurant entity documenting identity, address and a visible official menu. Self-published history remains separate from independent evaluation.',
		'האתר הרשמי תומך בפריטי תפריט ובהפעלה כפי שנצפו, לא בדירוג איכות עצמאי או בזמינות עתידית.',
		'The official site supports observed menu and operating identity, not independent quality ranking or future availability.',
		array(), 'Neutral editorial restaurant-profile image of ful and fatteh breakfast dishes on a simple Aleppo table, no logo and no copied interior.'
	),

	/* Damascus depth. */
	array(
		'guide-damascene-sweets', 'guide', 'damascene-sweets-guide', 'region-syria-damascus',
		'מדריך ממתקי דמשק', 'Damascene Sweets Guide', 'syria-damascus', array( 'ritsumeikan-damascus-midan-sweets', 'bakdash-official' ),
		'מפת ניווט לממתקים, מאפים, בוזה ושכונות מסחר בדמשק. כל צורה, בית עסק ושכונה נשמרים כישויות נפרדות לפי מקור.',
		'A navigation map for sweets, pastries, booza and commercial neighborhoods in Damascus. Each form, business and district remains a separate source-bound entity.',
		'המקורות תומכים בריבוי מסורות ומקומות, אך אינם מספקים טקסונומיה אחת שמחייבת את כל קונדיטוריות דמשק.',
		'The sources support multiple traditions and places but do not provide one taxonomy governing every Damascus confectioner.',
		array( 'ingredient-syrian-pistachios', 'ingredient-syrian-semolina', 'ingredient-syrian-sugar-syrup' ),
		'Damascene confectionery taxonomy spread with pounded ice cream, semolina pastry and pistachio forms separated on a refined studio table.'
	),
	array(
		'dish-harraq-isbao', 'dish', 'harraq-isbao', 'region-syria-damascus',
		'חראק אצבעו', 'Harraq Isbao', 'syria-damascus', array( 'international-academy-gastronomy-syria' ),
		'מנה דמשקאית המבוססת על עדשים, בצק או לחם, בצל מטוגן ורכיב חמוץ. המקור מאפשר רכז רימונים או לימון ולכן החומצה אינה ננעלת לנוסחה אחת.',
		'A Damascene dish built around lentils, dough or bread, fried onion and acidity. The source allows pomegranate syrup or lemon, so the acid component is not locked to one formula.',
		'המקור מתאר אפשרויות חומצה שונות ואינו מספק pH יעד או יחס אחיד בין עדשים ללחם.',
		'The source describes alternative acid components and supplies no target pH or universal lentil-to-bread ratio.',
		array( 'ingredient-syrian-lentils', 'ingredient-syrian-onion', 'ingredient-syrian-pomegranate-molasses', 'ingredient-syrian-lemon' ),
		'Commercial culinary photograph of harraq isbao with lentils, small dough pieces, fried onions and restrained pomegranate accent, natural dark bowl.'
	),
	array(
		'dish-fatteh-shamiyya', 'dish', 'fatteh-shamiyya', 'region-syria-damascus',
		'פתה שאמייה', 'Damascene Fatteh', 'syria-damascus', array( 'international-academy-gastronomy-syria' ),
		'משפחת מנה דמשקאית המבוססת על שכבות לחם ורוטב, עם גרסת חומוס כמסלול אפשרי. כל גרסה תישמר כהכנה נפרדת לאחר בדיקה.',
		'A Damascene layered-bread dish family with a chickpea version as one possible path. Each version will remain a separate preparation after testing.',
		'המקור תומך בזהות המשפחה אך אינו קובע יחס טחינה, יוגורט, לחם וחומוס לכל גרסה.',
		'The source supports the dish family but does not establish one tahini, yogurt, bread and chickpea ratio for every version.',
		array( 'ingredient-syrian-chickpeas', 'ingredient-syrian-tahini', 'ingredient-syrian-fresh-yogurt' ),
		'Damascene chickpea fatteh in a shallow ceramic bowl, crisp bread and creamy layers clearly visible, no oversized garnish.'
	),
	array(
		'dish-basmeshqat', 'dish', 'basmeshqat', 'region-syria-damascus',
		'בסמשקאט', 'Basmeshqat', 'syria-damascus', array( 'international-academy-gastronomy-syria' ),
		'זהות דמשקאית להכנת בשר ממולא ששמה ותיאורי הגרסאות דורשים הצלבה לשונית וקולינרית לפני פרסום.',
		'A Damascene stuffed-meat identity whose spelling and variant descriptions require linguistic and culinary corroboration before publication.',
		'המקור תומך בשם ובהקשר כללי בלבד, ולכן צורה, מילוי וטכניקת בישול נשארים פתוחים.',
		'The source supports only the name and broad context, leaving shape, filling and cooking method unresolved.',
		array( 'ingredient-syrian-red-meat', 'ingredient-syrian-rice' ),
		'Private editorial study of a Damascene stuffed meat preparation, cut section visible but deliberately neutral where form is unresolved.'
	),
	array(
		'dish-damascene-booza', 'dish', 'damascene-booza', 'guide-damascene-sweets',
		'בוזה דמשקאית', 'Damascene Booza', 'syria-damascus', array( 'bakdash-official' ),
		'גלידה דמשקאית הנבחנת לפי טכניקת כתישה ומתיחה ולפי זהות חומרי ההסמכה. בקדאש נשמר כדוגמת בית עסק ולא כהוכחה שכל בוזה מיוצרת באותה נוסחה.',
		'A Damascene ice cream studied through pounding, elasticity and thickener identity. Bakdash remains a business example rather than proof that every booza uses one formula.',
		'המקור העסקי תומך בזהות ובהכנה גלויה, אך אין ממנו מפרט סחלב, מסטיקה, מוצקי חלב, טמפרטורה או אלרגנים.',
		'The business source supports identity and visible preparation but supplies no sahlab, mastic, milk-solids, temperature or allergen specification.',
		array( 'ingredient-syrian-pistachios' ),
		'Commercial studio photograph of elastic Damascene booza being folded with a long paddle, pistachio edge, cool stone counter, no brand.'
	),
	array(
		'ingredient-qamar-al-din', 'ingredient', 'qamar-al-din-apricot-leather', 'region-syria-damascus',
		'קמר א-דין, יריעת משמש', 'Qamar al-Din Apricot Leather', 'syria-rural-damascus', array( 'sana-qamar-al-din-production-2026', 'fao-eastern-ghouta-2026' ),
		'יריעת משמש מרוכזת המשמשת להכנת משקה ומאכלים. דיווח הייצור הוא תצפית מתוארכת ואינו מוכיח מקור בלעדי, מפרט סוכר או SKU של Complete99.',
		'A concentrated apricot sheet used for drinks and foods. The production report is a dated observation and does not prove exclusive origin, sugar specification or a Complete99 SKU.',
		'המקור מתאר ייצור וחיתוך למנות מסחריות, אך אין ברשומה מדידת בריקס, פעילות מים או זהות תוספים.',
		'The source describes production and portioning but the record has no measured Brix, water activity or additive identity.',
		array( 'ingredient-syrian-dried-apricot' ),
		'Premium studio ingredient shot of translucent Qamar al-Din apricot leather folded beside soaked amber pieces, no packaging or text.'
	),
	array(
		'preparation-qamar-al-din-drink', 'preparation', 'qamar-al-din-drink', 'ingredient-qamar-al-din',
		'משקה קמר א-דין', 'Qamar al-Din Drink', 'syria-damascus', array( 'sana-qamar-al-din-production-2026' ),
		'משקה רמדאן המבוסס על השריה או המסה של יריעת משמש. סיפורי מקור והעדפות מתיקות נשמרים מחוץ לנוסחה עד לבדיקה.',
		'A Ramadan drink based on soaking or dissolving apricot leather. Origin stories and sweetness preferences remain outside the formula pending testing.',
		'המקור תומך בקשר לרמדאן וליריעת המשמש, אך אינו מספק יחס מים, סוכר, זמן או טמפרטורה מאומתים.',
		'The source supports the Ramadan and apricot-leather connection but supplies no verified water, sugar, time or temperature ratio.',
		array( 'ingredient-qamar-al-din' ),
		'Amber Qamar al-Din drink in a clear glass beside soaking apricot leather, soft Ramadan evening light without symbols or text.'
	),
	array(
		'ingredient-damascene-rose', 'ingredient', 'damascene-rose-culinary', 'region-syria-damascus',
		'ורד דמשקאי במטבח', 'Damascene Rose in Cooking', 'syria-al-mrah', array( 'unesco-damascene-rose-al-mrah' ),
		'ישות חומר גלם המקשרת עלי כותרת וניצנים לשיטות ייבוש, זיקוק, סירופ, ריבה ומאפים שתועדו באל-מראח. היא אינה מעניקה מקור מאומת לכל מוצר ורדים מסחרי.',
		'An ingredient entity linking petals and buds to drying, distillation, syrup, jam and pastry practices documented in Al-Mrah. It does not grant verified origin to every commercial rose product.',
		'המקור מתעד שימושים ותהליכים קהילתיים, אך אין ברשומה אנליזה נדיפה, ריכוז שמן, מי ורדים או טענה רפואית.',
		'The source documents community uses and processes, while the record contains no volatile analysis, oil concentration, rose-water specification or medical claim.',
		array(), 'Macro culinary studio photograph of Damascene rose petals and dried buds beside unbranded rose water and syrup samples, no wellness cues.'
	),
	array(
		'tradition-al-mrah-rose-craft', 'tradition', 'al-mrah-rose-craft-festival', 'ingredient-damascene-rose',
		'מלאכת ופסטיבל הוורד באל-מראח', 'Al-Mrah Rose Craft and Festival', 'syria-al-mrah', array( 'unesco-damascene-rose-al-mrah' ),
		'מסורת קהילתית ממוקדת באל-מראח הכוללת קטיף, ייבוש, זיקוק, הכנת מזון ופסטיבל שנתי. היא אינה מוצגת כמנהג אוניברסלי של דמשק.',
		'A community practice specifically scoped to Al-Mrah, including harvesting, drying, distillation, food preparation and an annual festival. It is not presented as universal Damascus practice.',
		'רשומת אונסקו תומכת במקום, בקהילה ובמלאכות המזוהות, לא בבעלות מסחרית או באיכות של מוצר קמעונאי מסוים.',
		'The UNESCO record supports the place, community and named crafts, not commercial ownership or quality of a particular retail product.',
		array( 'ingredient-damascene-rose' ), 'Documentary culinary scene of Al-Mrah rose sorting, drying trays and copper distillation equipment, hands only, no identifiable faces or costumes.'
	),
	array(
		'market-souq-al-buzuriyah', 'market', 'souq-al-buzuriyah-damascus', 'region-syria-damascus',
		'שוק אל-בזורייה בדמשק', 'Souq al-Buzuriyah in Damascus', 'syria-damascus', array( 'sana-buzuriyah-market-2026', 'unesco-damascene-rose-al-mrah' ),
		'ישות מקום לשוק התבלינים והבשמים בדמשק, כולל קשר מתועד לניצני ורד מיובשים. תצפיות מחיר ואיכות נשמרות עם תאריך ואינן הופכות למחיר חנות.',
		'A place entity for the Damascus spice and aromatics market, including a documented link to dried rose buds. Price and quality observations remain dated and never become store prices automatically.',
		'הדיווח המתוארך מציין לחצי מטבע, יבוא וכוח קנייה, אך אלה אינם מאפיינים קבועים של השוק.',
		'The dated report notes currency, import and purchasing-power pressure, but these are not permanent market characteristics.',
		array( 'ingredient-damascene-rose' ), 'Damascus spice-market study with rose buds, whole spices and dried fruit in unbranded bins, realistic documentary lighting.'
	),
	array(
		'restaurant-bakdash', 'restaurant', 'bakdash-damascus', 'guide-damascene-sweets',
		'בקדאש בדמשק', 'Bakdash in Damascus', 'syria-damascus', array( 'bakdash-official' ),
		'ישות מסעדה חיצונית התומכת בשם, במקום ובהצגת בוזה כתושה. היא אינה משמשת כדירוג עצמאי או כהוכחה למפרט מלא של כל חומר גלם.',
		'An external restaurant entity supporting the name, place and presentation of pounded booza. It is not an independent ranking or a complete ingredient specification.',
		'האתר הרשמי תומך בזהות העסקית בזמן הבדיקה בלבד, לא בהבטחת שעות, מלאי או נוסחה עתידית.',
		'The official site supports business identity at review time only, not future hours, stock or formula.',
		array(), 'Neutral editorial image of pounded booza service in a Damascus ice-cream shop setting, no logo, no copied interior and no identifiable staff.'
	),
	array(
		'dish-maarouk-damascus', 'dish', 'maarouk-damascus', 'guide-damascene-sweets',
		'מערוק דמשקאי', 'Damascene Maarouk', 'syria-damascus', array( 'avs-razan-damascus' ),
		'לחם או מאפה רמדאן שתועד בעדות משפחתית דמשקאית. מילויים, צורות ומתיקות נשמרים כווריאנטים עד הצלבה וניסוי.',
		'A Ramadan bread or pastry documented in a Damascene family testimony. Fillings, shapes and sweetness remain variants pending corroboration and testing.',
		'העדות תומכת בשם ובהקשר רמדאן, אך אינה מספקת מפרט קמח, התפחה, מילוי או אפייה.',
		'The testimony supports the name and Ramadan context but supplies no flour, fermentation, filling or baking specification.',
		array(), 'Damascene maarouk bread on a dark baking tray with one cut section, subtle glaze, no invented filling or holiday symbols.'
	),

	/* Syrian Jewish foodways, always nested inside the wider Syrian context. */
	array(
		'tradition-syrian-jewish-foodways-depth', 'tradition', 'syrian-jewish-foodways-depth', 'cuisine-syrian-regional',
		'מסורות האוכל של יהודי סוריה', 'Syrian Jewish Foodways', 'syria-diaspora', array( 'anu-syrian-jewish-community', 'jfs-sigi-mantel-archive', 'jfs-kibbeh-hamda' ),
		'שער המחבר מסורות אוכל של יהודי חלב ודמשק אל ההקשר הסורי הרחב, בלי להחליף את המטבח הסורי ובלי לאחד שתי קהילות היסטוריות שונות.',
		'A gateway connecting Aleppan and Damascene Jewish foodways to the wider Syrian context without replacing Syrian cuisine or merging two historically distinct communities.',
		'המקורות תומכים בקיום מסורות חלביות ודמשקאיות נפרדות ובגלגוליהן בתפוצות, אך אינם תומכים בנוסחה יהודית סורית אחידה.',
		'The sources support separate Aleppan and Damascene traditions and their diaspora continuities, but not one uniform Syrian Jewish formula.',
		array(), 'Bilingual-free editorial foodways map with separate Aleppan and Damascene household tables connected to a wider Syrian culinary context, no religious symbols.', 'syrian-jewish'
	),
	array(
		'tradition-syrian-jewish-migration-adaptation', 'tradition', 'syrian-jewish-migration-recipe-adaptation', 'tradition-syrian-jewish-foodways-depth',
		'הגירת יהודי סוריה וגלגולי מתכונים', 'Syrian Jewish Migration and Recipe Adaptation', 'syria-diaspora', array( 'anu-syrian-jewish-community', 'jfs-sigi-mantel-archive' ),
		'מפת המשכיות ושינוי בין סוריה, ישראל, ברוקלין וקהילות נוספות. החלפת חומר גלם או התאמה לחג נשמרת כגרסה מתועדת ולא כאובדן של מקוריות.',
		'A map of continuity and change across Syria, Israel, Brooklyn and other communities. Ingredient substitutions and holiday adaptations remain documented variants rather than narratives of lost authenticity.',
		'סיפור משפחתי יכול לתעד בית אחד או מסלול הגירה, אך אינו מוכיח מנהג של קהילה שלמה ללא מקור נוסף.',
		'A family story may document one household or migration path but cannot establish a community-wide practice without additional evidence.',
		array(), 'Editorial migration foodways study with the same dish shown in three household adaptations, neutral tables and no maps, flags or identity labels.', 'syrian-jewish'
	),
	array(
		'guide-syrian-jewish-kibbeh-family', 'guide', 'syrian-jewish-kibbeh-family', 'tradition-syrian-jewish-foodways-depth',
		'משפחת הקובה במסורות יהודי סוריה', 'Kibbeh Family in Syrian Jewish Foodways', 'syria-diaspora', array( 'jfs-kibbeh-hamda', 'jfs-passover-kibbeh-damascus', 'foodish-matzah-kebab' ),
		'מדריך המבחין בין קובה חמדה חלבית, קובה לפסח בנוסח משפחתי דמשקאי וקבב מצות. כל מנה נשמרת עם חומר המעטפת, החג והמשפחה שתיעדה אותה.',
		'A guide distinguishing Aleppan kibbeh hamdah, a Damascene family Passover kibbeh and matzo kebab. Each dish retains its documented shell material, holiday and family scope.',
		'אורז, מצה ובורגול אינם תחליפים אוטומטיים, והכשר לפסח תלוי במוצר, במטבח ובמנהג המשפחה.',
		'Rice, matzo and bulgur are not automatic substitutes, and Passover suitability depends on the product, kitchen and family custom.',
		array( 'ingredient-syrian-rice', 'ingredient-syrian-matzah', 'ingredient-syrian-bulgur' ),
		'Comparative culinary study of three cooked Syrian Jewish kibbeh forms with rice, matzo and bulgur shells separated, no raw meat and no religious props.', 'syrian-jewish'
	),
	array(
		'dish-kibbeh-charola-aleppan-jewish', 'dish', 'kibbeh-charola-aleppan-jewish', 'tradition-aleppan-jewish-foodways',
		'קובה צ׳רולה חלבית-יהודית', 'Aleppan Jewish Kibbeh Charola', 'syria-aleppo-diaspora', array( 'jfs-kibbeh-charola' ),
		'קובה אפויה כשכבות בורגול ובשר המתועדת כגרסה משפחתית בתפוצה החלבית. היא אינה שם נרדף לכל קובה אפויה בסוריה.',
		'A baked layered bulgur-and-meat kibbeh documented as an Aleppan diaspora family version. It is not a synonym for every baked kibbeh in Syria.',
		'המקור מספק גרסה משפחתית, אך פרסום כמתכון Complete99 דורש ניסוי, תשואה, אלרגנים ותמונות תואמות.',
		'The source supplies a family version, while Complete99 recipe publication requires testing, yield, allergens and matching images.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-onion' ),
		'Baked kibbeh charola cut into neat squares, visible bulgur shell and cooked meat layer, warm studio light, no family or religious props.', 'aleppan-jewish'
	),
	array(
		'dish-medias-damascene-jewish', 'dish', 'medias-damascene-jewish', 'tradition-damascene-jewish-foodways',
		'מדיאס חצילים דמשקאי-יהודי', 'Damascene Jewish Eggplant Medias', 'syria-damascus-diaspora', array( 'asif-medias-damascene', 'jfs-sigi-mantel-archive' ),
		'מנת חצילים ובשר המתועדת ברפרטואר משפחתי דמשקאי, עם גרסה חלבית נפרדת במקור נוסף. האטימולוגיה והקשר בין הגרסאות אינם נקבעים ללא מקור לשוני.',
		'An eggplant-and-meat dish documented in a Damascene family repertoire, with a separate dairy version in another source. Etymology and variant relationships remain unresolved without linguistic evidence.',
		'הגרסה הבשרית והגרסה החלבית אינן מתמזגות, והן דורשות בדיקות אלרגנים ומטבח נפרדות.',
		'The meat and dairy versions are not merged and require separate kitchen and allergen testing.',
		array( 'ingredient-syrian-eggplant', 'ingredient-syrian-red-meat' ),
		'Damascene eggplant medias with cooked meat filling, cross-section visible, separate empty composition space for a future dairy variant, no text.', 'damascene-jewish'
	),
	array(
		'dish-maoudeh-damascene-jewish', 'dish', 'maoudeh-damascene-jewish', 'tradition-damascene-jewish-foodways',
		'מעודה, עוף ותפוחי אדמה', 'Maoudeh with Chicken and Potatoes', 'syria-damascus-diaspora', array( 'jfs-sigi-mantel-archive', 'misham-damascus-community' ),
		'מנת עוף ותפוחי אדמה המופיעה ברפרטואר משפחתי וקהילתי דמשקאי, כולל הקשרים של ערב יום כיפור בעדויות מסוימות.',
		'A chicken-and-potato dish appearing in Damascene family and community repertoires, including pre-Yom-Kippur context in some records.',
		'הקשר החג תחום לעדויות המזוהות ואינו הופך את המנה לחובה דתית או למנהג של כל יהודי דמשק.',
		'The holiday context is bounded to the identified records and does not make the dish a religious requirement or a practice of every Damascene Jew.',
		array( 'ingredient-syrian-whole-chicken' ),
		'Home-style cooked chicken and potato maoudeh in a shallow casserole, restrained browning, family-table atmosphere without ritual objects.', 'damascene-jewish'
	),
	array(
		'preparation-mujadara-thursday-syrian-jewish', 'preparation', 'mujadara-thursday-syrian-jewish', 'tradition-syrian-jewish-foodways-depth',
		'מג׳דרה של יום חמישי במסורת משפחתית', 'Thursday Mujadara in Syrian Jewish Family Practice', 'syria-diaspora', array( 'jfs-mujadara-thursday' ),
		'גרסת מג׳דרה המתועדת בקהילה הסורית-יהודית בברוקלין כמנת יום חמישי שפינתה זמן להכנות שבת. המנה עצמה משותפת למרחב רחב יותר.',
		'A mujadara version documented in Brooklyn Syrian Jewish practice as a Thursday staple that left time for Shabbat preparation. The dish itself belongs to a much wider regional repertoire.',
		'הקשר של יום חמישי הוא מנהג מתועד ולא כלל מחייב, ואין בו טענת המצאה או בלעדיות.',
		'The Thursday context is a documented practice rather than a rule, with no invention or exclusivity claim.',
		array( 'ingredient-syrian-lentils', 'ingredient-syrian-rice', 'ingredient-syrian-onion' ),
		'Cooked rice-and-lentil mujadara with deeply browned onions in a simple weekday bowl, natural household light, no Shabbat symbols.', 'syrian-jewish'
	),
	array(
		'tradition-aleppan-jewish-shabbat-bread-hamine', 'tradition', 'aleppan-jewish-shabbat-bread-hamine', 'tradition-aleppan-jewish-foodways',
		'לחם שבת וחמין בתנור הקהילתי במסורת חלבית', 'Aleppan Shabbat Bread and Communal-Oven Hamine', 'syria-aleppo-diaspora', array( 'forward-syrian-shabbat-bread' ),
		'עדות קהילתית מתארת ח׳ובז עאדי וחמין שהתבשל במשך הלילה בתנור משותף. המונחים נשמרים כפי שתועדו ואינם מוחלפים אוטומטית בצ׳ולנט או דפינה.',
		'A community account describes khubz adi and hamine cooked overnight in a communal oven. The source terms remain intact and are not automatically replaced by cholent or dafina.',
		'מספר הכיכרות וצורת הבישול שייכים לעדות המתועדת ואינם נוסחה לכל משפחה חלבית.',
		'The loaf count and cooking pattern belong to the documented account and are not a formula for every Aleppan family.',
		array(), 'Aleppan Shabbat bread loaves beside a sealed communal-oven stew pot, documentary kitchen light and no ceremonial objects.', 'aleppan-jewish'
	),
	array(
		'dish-adjwe-date-crescents-aleppan-jewish', 'dish', 'adjwe-date-crescents-aleppan-jewish', 'tradition-aleppan-jewish-foodways',
		'סהרוני עג׳ווה במילוי תמרים', 'Adjwe Date-Filled Semolina Crescents', 'syria-aleppo-diaspora', array( 'jfs-adjwe-date-crescents' ),
		'עוגיות סהרוני סולת ותמרים המתועדות במשפחה חלבית בתפוצה, כולל זיכרון של הגשה ביום שישי. הזיכרון נשאר משפחתי ולא הופך למנהג עירוני.',
		'Semolina-and-date crescent cookies documented in an Aleppan diaspora family, including a Friday serving memory. The memory remains family-scoped rather than a citywide custom.',
		'המקור מזהה סולת ותמרים, אך מתכון ציבורי דורש אימות שומן, נוזלים, גלוטן, זמן ואפייה.',
		'The source identifies semolina and dates, while a public recipe requires verification of fat, liquids, gluten, time and baking.',
		array( 'ingredient-syrian-semolina' ),
		'Commercial pastry photograph of crescent-shaped semolina cookies with visible date filling, soft coffee-side light, no holiday decoration.', 'aleppan-jewish'
	),
	array(
		'tradition-damascene-jewish-holiday-foodways', 'tradition', 'damascene-jewish-holiday-foodways', 'tradition-damascene-jewish-foodways',
		'מאכלי החגים של יהודי דמשק', 'Damascene Jewish Holiday Foodways', 'syria-damascus-diaspora', array( 'misham-damascus-community', 'jfs-sigi-mantel-archive' ),
		'שער לחומר קהילתי ומשפחתי על פסח, ראש השנה, יום כיפור ושבועות, עם הפרדה בין חג, משפחה, מוצר והסמכת כשרות עדכנית.',
		'A gateway to community and family records for Passover, Rosh Hashanah, Yom Kippur and Shavuot, separating holiday, household, product and current kosher certification.',
		'אורז, קטניות, בוטנים ומוצרי חלב משתנים לפי מנהג משפחה וסמכות, ולכן אין ברשומה כלל כשרות גורף.',
		'Rice, legumes, peanuts and dairy vary by family custom and authority, so the record contains no blanket kashrut rule.',
		array(), 'Seasonal Damascene Jewish foodways study with four separate household dishes, no religious symbols, no certification marks and no text.', 'damascene-jewish'
	),
	array(
		'ingredient-syrian-baharat', 'ingredient', 'syrian-baharat', 'cuisine-syrian-regional',
		'בהרט סורי ותערובות ביתיות', 'Syrian Baharat and Household Blends', 'syria-multi-region', array( 'jfs-sigi-mantel-archive', 'avs-razan-damascus', 'avs-mirvet-aleppo' ),
		'ישות לתערובות תבלינים ששמן והרכבן משתנים בין בתים, ערים ומנות. כל מוצר מסחרי חייב להציג רשימת רכיבים, אלרגנים ומקור במקום להסתפק בשם בהרט.',
		'An entity for spice blends whose name and composition vary by household, city and dish. Every commercial product must expose ingredients, allergens and origin rather than relying on the word baharat alone.',
		'המקורות תומכים בשימוש בתערובות, אך אין מהם נוסחה סורית אוניברסלית או ריכוז מולקולות טעם קבוע.',
		'The sources support blend use but provide neither a universal Syrian formula nor a fixed flavor-molecule concentration.',
		array(), 'Macro studio photograph of whole and ground Syrian household spice components in separate unbranded bowls, no fixed recipe implied.'
	),
	array(
		'ingredient-syrian-orange-blossom-water', 'ingredient', 'syrian-orange-blossom-water', 'cuisine-syrian-regional',
		'מי זהר במטבח הסורי', 'Orange Blossom Water in Syrian Cooking', 'syria-multi-region', array( 'jfs-adjwe-date-crescents', 'misham-damascus-community' ),
		'חומר ארומטי לקינוחים ומאפים, הנשמר בנפרד ממי ורדים. מוצר מסחרי דורש מקור, שיטת הפקה, רכיבים וריכוז, והכמות במתכון דורשת ניסוי.',
		'An aromatic ingredient for sweets and pastries, retained separately from rose water. A commercial product requires origin, extraction method, ingredients and concentration, while recipe dosage requires testing.',
		'המקורות תומכים בשימוש קולינרי אך אינם מספקים ניתוח נדיפים או ריכוז אחיד בין מותגים.',
		'The sources support culinary use but supply no volatile analysis or uniform concentration across brands.',
		array(), 'Clear unbranded orange blossom water vial beside blossoms and a measured pastry dropper, bright studio light, no wellness claims.'
	),

	/* Homs and Hama. */
	array(
		'hub-homsi-kibbeh-liquid-methods', 'topic_hub', 'homsi-kibbeh-liquid-methods', 'region-syria-homs',
		'משפחת הקובה החומסית ברטבים ובישול', 'Homsi Kibbeh in Sauces and Boiling Methods', 'syria-homs', array( 'avs-nariman-homs' ),
		'שער לקובה לבנייה, קובה בחמוץ וקובה אל-מהבאלה, עם הפרדה בין רוטב יוגורט, רוטב חמוץ ובישול במים עם גמר שמן חם.',
		'A gateway for kibbeh labaniyyeh, kibbeh b-hamod and kibbeh al-mhabaleh, separating yogurt sauce, sour sauce and water boiling followed by hot-oil finishing.',
		'עדות חומסית אחת תומכת בשלוש הזהויות, אך נדרשים מקורות נוספים ושלושה ניסויי מטבח נפרדים לפני פרסום.',
		'One Homsi testimony supports the three identities, while additional sources and three separate kitchen trials are required before publication.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-pomegranate-molasses' ),
		'Three Homsi cooked kibbeh preparations shown separately in yogurt, sour broth and hot-oil finish, neutral comparative studio layout.'
	),
	array(
		'dish-kibbeh-al-mhabaleh-homs', 'dish', 'kibbeh-al-mhabaleh-homs', 'hub-homsi-kibbeh-liquid-methods',
		'קובה אל-מהבאלה מחומס', 'Homsi Kibbeh al-Mhabaleh', 'syria-homs', array( 'avs-nariman-homs' ),
		'קובה המבושלת במים, מסוננת, מתובלת במלח ופלפל ומקבלת גמר שמן חם לפי עדות חומסית.',
		'A kibbeh boiled in water, drained, seasoned with salt and pepper and finished with hot oil in a Homsi testimony.',
		'המקור מתאר רצף פעולות אך אינו מספק יחס מים, עובי מעטפת, טמפרטורת שמן או זמן בטיחות.',
		'The source describes a process sequence but supplies no water ratio, shell thickness, oil temperature or safety time.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat' ),
		'Cooked Homsi kibbeh al-mhabaleh drained and lightly glossed with hot oil, pepper visible, no raw center and no decorative sauce.'
	),
	array(
		'dish-stuffed-carrots-homs', 'dish', 'stuffed-carrots-homs', 'region-syria-homs',
		'גזר ממולא חומסי', 'Homsi Stuffed Carrots', 'syria-homs', array( 'avs-nariman-homs' ),
		'גזרים מרוקנים וממולאים המוגשים ברוטב טחינה, מים, רכז רימונים, כוסברה ושום לפי עדות חומסית מפורטת.',
		'Hollowed stuffed carrots served with a tahini, water, pomegranate molasses, coriander and garlic sauce in a detailed Homsi testimony.',
		'זהות המילוי, יחס הרוטב וזמן הבישול דורשים אימות לפני שהמנה תסומן כמתכון בדוק.',
		'Filling identity, sauce ratio and cooking time require verification before the dish can be marked as a tested recipe.',
		array( 'ingredient-syrian-tahini', 'ingredient-syrian-pomegranate-molasses', 'ingredient-syrian-garlic' ),
		'Commercial culinary photograph of Homsi stuffed carrots in a pale tahini and pomegranate sauce, clean cross-section and restrained herbs.'
	),
	array(
		'dish-al-mughtuta-homs', 'dish', 'al-mughtuta-homs', 'region-syria-homs',
		'אל-מוגטוטה מחומס', 'Al-Mughtuta from Homs', 'syria-homs', array( 'avs-nariman-homs' ),
		'ארוחת בוקר המבוססת על שכבת שמנת חלבית הנספגת בלחם תנור קטן, נהפכת וממותקת, כפי שתועדה בחומס.',
		'A breakfast in which a milk-cream layer is absorbed by small tannour bread, flipped and sweetened, as documented in Homs.',
		'העדות מתארת השריה לילית, אך בטיחות חלב, קירור, זמן וחיי מדף אינם מאומתים ולכן אין הוראות ציבוריות.',
		'The testimony describes overnight absorption, while dairy safety, refrigeration, time and shelf life remain unverified, so no public instructions are provided.',
		array( 'ingredient-syrian-qeshta-cream', 'ingredient-syrian-sugar-syrup' ),
		'Homsi al-mughtuta breakfast study with a cream-soaked small bread flipped on a plate, morning side light, no shop branding.'
	),
	array(
		'dish-al-khubziyyeh-homs', 'dish', 'al-khubziyyeh-homs', 'region-syria-homs',
		'אל-ח׳ובזייה מחומס', 'Al-Khubziyyeh from Homs', 'syria-homs', array( 'avs-nariman-homs' ),
		'יריעות פריכות מטוגנות ומושרות בסירופ, עם זיכרון של גרסאות אדומות ולבנות בהקשר חגיגי בחומס.',
		'Crisp fried sheets soaked in syrup, with a memory of red and white festive variants in Homs.',
		'הצבעים והגרסאות אינם מקבלים נוסחה או חומר צבע עד לזיהוי מקור נוסף וניסוי.',
		'The colors and variants receive no formula or coloring ingredient until another source and a kitchen test establish them.',
		array( 'ingredient-syrian-sugar-syrup' ),
		'Crisp syrup-soaked Homsi khubziyyeh sheets in natural pale and restrained red-tinted variants, no artificial-color implication.'
	),
	array(
		'tradition-homs-sweet-thursday', 'tradition', 'homs-sweet-thursday', 'region-syria-homs',
		'חמיס אל-חלאווה בחומס', 'Sweet Thursday in Homs', 'syria-homs', array( 'avs-nariman-homs' ),
		'מסורת עירונית לפני יום ראשון של הדקלים הכוללת ביקורי קברים וחלוקת ממתקים בעדויות מחומס. היא נשמרת כמסורת חוצת קהילות ולא משויכת בלעדית לדת אחת.',
		'A city tradition before Palm Sunday involving grave visits and sweet distribution in Homsi records. It remains cross-community rather than exclusively assigned to one religion.',
		'המקור המשפחתי תומך בזיכרון המסורת, אך רשימת הממתקים ותפוצתה העכשווית דורשות אימות מקומי נוסף.',
		'The family source supports the remembered tradition, while the sweet roster and current prevalence require further local verification.',
		array(), 'Homs sweet-distribution table with several traditional pastries in separate plates, documentary daylight, no religious symbols or crowds.'
	),
	array(
		'dish-sakhtoura-hama', 'dish', 'sakhtoura-hama', 'region-syria-hama',
		'סחטורה מחמה', 'Sakhtoura from Hama', 'syria-hama', array( 'avs-noor-hama' ),
		'מנה של קיבה או כרס ממולאת שתועדה בעדות משפחתית מחמה. חומר הגלם מן החי, הניקוי, המילוי והבישול דורשים מפרט בטיחות נפרד.',
		'A stuffed tripe preparation documented in a Hama family testimony. The animal ingredient, cleaning, filling and cooking require a separate safety specification.',
		'המקור תומך בזהות המנה בלבד ואינו מספק תהליך ניקוי מאומת או טמפרטורת ליבה.',
		'The source supports the dish identity only and supplies no validated cleaning process or core temperature.',
		array( 'ingredient-syrian-rice', 'ingredient-syrian-red-meat' ),
		'Private editorial study of fully cooked stuffed tripe from Hama, clean cut section, clinical food-safe styling without raw preparation.'
	),
	array(
		'preparation-waraq-enab-hamawi', 'preparation', 'waraq-enab-hamawi', 'dish-yabraq-yebra',
		'עלי גפן ממולאים בנוסח משפחתי מחמה', 'Hama Family Stuffed Vine Leaves', 'syria-hama', array( 'avs-noor-hama' ),
		'גרסת עלי גפן עם מילוי בשר ואורז ובשר מתחת לסיר, כפי שתועדה במשפחה מחמה. היא נשמרת לצד יברק דמשקאי ויברא חלבית בלי איחוד.',
		'A vine-leaf version with meat-and-rice filling and meat beneath the pot, documented in a Hama family. It remains beside Damascene yabraq and Aleppan yebra without merging.',
		'המקור מתאר מבנה סיר אך אינו מספק משקל, צפיפות גלגול או זמן בישול מאומת.',
		'The source describes pot structure but supplies no verified weight, rolling density or cooking time.',
		array( 'ingredient-syrian-grape-leaves', 'ingredient-syrian-rice', 'ingredient-syrian-red-meat' ),
		'Hama family stuffed vine leaves arranged over cooked meat in a cutaway pot study, fully cooked, no universal recipe claim.'
	),
	array(
		'dish-shakriyeh-hama', 'dish', 'shakriyeh-hama', 'region-syria-hama',
		'שכרייה מחמה', 'Shakriyeh from Hama', 'syria-hama', array( 'avs-noor-hama' ),
		'מנת בשר ברוטב יוגורט שתועדה בחמה. היא נשמרת בנפרד מארמן האידליבית ומקובה לבנייה אף ששלושתן משתמשות בחלב מותסס.',
		'A meat dish in yogurt sauce documented in Hama. It remains separate from Idlib arman and kibbeh labaniyyeh even though all use cultured dairy.',
		'שם חומר החלב אינו מוכיח חומציות, אחוז שומן או יציבות חימום זהים בין המנות.',
		'The dairy name does not establish identical acidity, fat percentage or heat stability across the dishes.',
		array( 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-red-meat', 'technique-syrian-yogurt-sauce-stability' ),
		'Hama shakriyeh with fully cooked meat in smooth white yogurt sauce, minimal rice side, no garnish invention.'
	),

	/* Coast and its food systems. */
	array(
		'preparation-latakia-kibbeh-pomegranate-sauce', 'preparation', 'latakia-kibbeh-pomegranate-sauce', 'region-syria-latakia',
		'כדורי קובה לטקיים ברוטב רימונים', 'Latakia Kibbeh Balls with Pomegranate Sauce', 'syria-latakia', array( 'avs-zainab-coast' ),
		'כדורי קובה מבושלים המוגשים ברוטב רכז רימונים, פטרוזיליה, בצל ותבלינים לפי עדות מן החוף.',
		'Boiled kibbeh balls served with pomegranate molasses, parsley, onion and spices in a coastal testimony.',
		'הרוטב נשמר כהכנה נפרדת מרוטב הטחינה ואינו מוכיח שם מנה עירוני מוסכם.',
		'The sauce remains a separate preparation from the tahini sauce and does not establish an agreed citywide dish name.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-pomegranate-molasses', 'ingredient-syrian-onion' ),
		'Boiled Latakia kibbeh balls with a glossy pomegranate, parsley and onion sauce, coastal ceramic plate, no invented local name.'
	),
	array(
		'preparation-latakia-kibbeh-tahini-sauce', 'preparation', 'latakia-kibbeh-tahini-sauce', 'region-syria-latakia',
		'כדורי קובה לטקיים ברוטב טחינה', 'Latakia Kibbeh Balls with Tahini Sauce', 'syria-latakia', array( 'avs-zainab-coast' ),
		'אותם כדורי קובה מבושלים עם רוטב נפרד של טחינה, שום ולימון לפי העדות החופית.',
		'The same boiled kibbeh-ball identity with a separate tahini, garlic and lemon sauce in the coastal testimony.',
		'זהות הכדור וזהות הרוטב נשמרות בנפרד כדי לאפשר בדיקה והשוואה בלי למזג שתי גרסאות.',
		'The ball and sauce identities remain separate to support testing and comparison without merging two versions.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-tahini', 'ingredient-syrian-garlic', 'ingredient-syrian-lemon' ),
		'Boiled Latakia kibbeh balls in pale tahini, garlic and lemon sauce, sauce texture clearly visible, no pomegranate garnish.'
	),
	array(
		'tradition-syrian-coast-eid-bulgur', 'tradition', 'syrian-coast-eid-bulgur', 'region-syria-coast',
		'בורגול חגיגי בכפרי החוף', 'Festive Bulgur in Rural Coastal Syria', 'syria-coast-rural', array( 'ifrepo-syria-coast-foodways' ),
		'עדות ערוכה מתארת בורגול וחומוס המתבשלים על עצים עם בשר או עוף לאירועים ואזכרות בהקשר כפרי עלווי. הכותרת הערבית המקומית עדיין בבדיקה.',
		'An edited account describes bulgur and chickpeas cooked over wood with meat or chicken for occasions and memorials in a rural Alawite context. The local Arabic title remains under review.',
		'השיוך הקהילתי נתמך במקור אחד ולכן נשאר פרטי עד ביקורת קהילה ומקור אזורי נוסף.',
		'The community attribution is supported by one source and remains private pending community review and another regional source.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-chickpeas', 'ingredient-syrian-red-meat', 'ingredient-syrian-whole-chicken' ),
		'Rural coastal communal bulgur and chickpea pot over controlled wood heat, cooked meat variation beside it, no religious or ethnic symbolism.', 'rural-alawite-source-context'
	),
	array(
		'market-syrian-coast-fish-landing-network', 'market', 'syrian-coast-fish-landing-network', 'region-syria-coast',
		'רשת נמלי הדיג ושיווק הדגים בחוף הסורי', 'Syrian Coastal Fish Landing and Market Network', 'syria-coast', array( 'fao-syria-coastal-fish-marketing' ),
		'ישות מערכתית המקשרת נקודות נחיתה ונתיבי שיווק היסטוריים בלטקיה, ג׳בלה, בניאס וטרטוס. פעילות, מסלולים ומוסדות דורשים אימות עדכני לפני פרסום.',
		'A system entity linking historical landing points and marketing routes in Latakia, Jableh, Baniyas and Tartus. Operations, routes and institutions require current verification before publication.',
		'המקור תומך במבנה היסטורי של שרשרת הדגים ואינו הוכחת מלאי, קירור או ספק פעיל כיום.',
		'The source supports a historical fish-chain structure and is not proof of current stock, cold chain or active supplier status.',
		array( 'ingredient-syrian-coastal-fish' ),
		'Editorial supply-chain still life of coastal fish landing crates, ice and market transfer points, no vessel names, logos or current-operation claim.'
	),
	array(
		'ingredient-syrian-coast-citrus-system', 'ingredient', 'syrian-coast-citrus-system', 'region-syria-coast',
		'מערכת ההדרים של לטקיה וטרטוס', 'Latakia and Tartus Citrus System', 'syria-coast', array( 'fao-syria-coastal-citrus' ),
		'ישות אזורית לתפוזים, מנדרינות, לימונים ואשכוליות בחקלאות החוף, עם קשרים עתידיים לדגים, סירופים ושימורים.',
		'A regional entity for oranges, mandarins, lemons and grapefruit in coastal farming, with future links to fish, syrups and preserves.',
		'המקור תומך במערכת גידול אזורית ואינו מספק זן, בריקס, חומציות או זמינות של אצווה מסוימת.',
		'The source supports a regional growing system but supplies no cultivar, Brix, acidity or availability for a specific lot.',
		array( 'ingredient-syrian-lemon' ),
		'Coastal Syrian citrus study with lemon, orange, mandarin and grapefruit separated by species, orchard light, no origin seal or health claim.'
	),

	/* Idlib and Maarrat al-Numan. */
	array(
		'dish-arman-idlib', 'dish', 'arman-idlib', 'region-syria-idlib-maarrat',
		'ארמן מאידליב', 'Arman from Idlib', 'syria-idlib-maarrat', array( 'avs-rahma-idlib' ),
		'מנה של יוגורט, בשר עם עצם וחריע שתועדה במשפחה מאידליב, ונשמרת בנפרד משכרייה לפי העדות.',
		'A yogurt, bone-in meat and safflower dish documented in an Idlib family and kept separate from shakriyeh according to the testimony.',
		'העדות תומכת בהבחנה בשם וברכיבים, אך אינה מספקת זן חריע, חומציות יוגורט או פרוטוקול ייצוב.',
		'The testimony supports the distinct name and components but supplies no safflower specification, yogurt acidity or stabilization protocol.',
		array( 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-red-meat', 'technique-syrian-yogurt-sauce-stability' ),
		'Idlib arman with bone-in cooked meat in safflower-tinted yogurt sauce, studio bowl, no shakriyeh comparison text.'
	),
	array(
		'preparation-lahm-bi-ajin-maarrat', 'preparation', 'lahm-bi-ajin-maarrat', 'dish-lahm-bi-ajin-syria',
		'לחם בעג׳ין בנוסח מערת א-נועמאן', 'Maarrat al-Numan Lahm bi Ajin Preparation', 'syria-idlib-maarrat', array( 'avs-rahma-idlib' ),
		'גרסה משפחתית עם בשר, בצל, יוגורט ורכז רימונים. היא אינה מתמזגת עם לחמג׳ון טורקי או עם גרסאות חלביות ללא השוואת מבנה.',
		'A family version with meat, onion, yogurt and pomegranate molasses. It is not merged with Turkish lahmacun or Aleppine versions without structural comparison.',
		'המקור מזהה רכיבים אך אינו מספק עובי בצק, הידרציה, טמפרטורת תנור או יחס תערובת.',
		'The source identifies components but supplies no dough thickness, hydration, oven temperature or mixture ratio.',
		array( 'ingredient-syrian-red-meat', 'ingredient-syrian-onion', 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-pomegranate-molasses' ),
		'Thin Maarrat al-Numan meat flatbread with onion, yogurt and pomegranate elements integrated in the topping, neutral stone bake surface.'
	),
	array(
		'preparation-meatless-bulgur-balls-maarrat', 'preparation', 'meatless-bulgur-balls-maarrat', 'region-syria-idlib-maarrat',
		'כדורי בורגול ללא בשר ממערת א-נועמאן', 'Maarrat al-Numan Meatless Bulgur Balls', 'syria-idlib-maarrat', array( 'avs-rahma-idlib' ),
		'גרסה ללא בשר של כדורי בורגול עם שמן זית, עגבנייה, בצל, כמון ונענע, שתועדה בהקשר משפחתי וכלכלי.',
		'A meatless bulgur-ball version with olive oil, tomato, onion, cumin and mint, documented in a family and economic context.',
		'המנה אינה קובה נייה מבשר ואינה מוצגת כתחליף רפואי; זהותה נשמרת כהתאמה משפחתית מתועדת.',
		'The dish is not raw-meat kibbeh and is not presented as a medical substitute; its identity remains a documented family adaptation.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-olive-oil', 'ingredient-syrian-onion', 'ingredient-syrian-dried-mint' ),
		'Meatless Maarrat bulgur balls with tomato, onion, cumin and mint, matte texture close-up, no raw meat cues.'
	),
	array(
		'dish-kawaj-idlib', 'dish', 'kawaj-idlib', 'region-syria-idlib-maarrat',
		'קוואג׳ מאידליב', 'Kawaj from Idlib', 'syria-idlib-maarrat', array( 'avs-rahma-idlib' ),
		'תבשיל המכונה גם שבע מדינות בעדות המשפחתית, המתאר זהות של שבעה ירקות והחלפות מודרניות לפי זמינות.',
		'A stew also called seven countries in the family testimony, describing a seven-vegetable identity and modern availability-based substitutions.',
		'מספר הירקות נשמר כחלק מן הזהות הסיפורית, אך רשימת הירקות והתחליפים דורשת הצלבה לפני נוסחה.',
		'The vegetable count remains part of the narrative identity, while the vegetable list and substitutions require corroboration before a formula.',
		array( 'ingredient-syrian-eggplant', 'ingredient-syrian-onion' ),
		'Idlib kawaj vegetable stew arranged to show seven distinct vegetable textures, rustic casserole, no labels and no meat assumption.'
	),

	/* Jazira and Qamishli. */
	array(
		'dish-dikhwa-qamishli-assyrian', 'dish', 'dikhwa-qamishli-assyrian', 'region-syria-qamishli-family-transmission',
		'דיח׳ווה אשורית מקמישלי', 'Assyrian Dikhwa from Qamishli', 'syria-qamishli', array( 'ifrepo-qamishli-assyrian-foodways' ),
		'מנה אשורית עם שעורה קלופה, בשר קצוץ ויוגורט חמוץ, המתועדת בהקשר קמישלי ובחגיגות אקיטו ואירועים נוספים.',
		'An Assyrian dish with peeled barley, chopped meat and sour yogurt, documented in a Qamishli context and associated with Akitu and other occasions.',
		'השיוך הקהילתי נתמך, אך איות סורי, תבלינים, יחס עמילן ובטיחות החלב דורשים ביקורת מומחה קהילה ומטבח.',
		'The community attribution is supported, while Syriac spelling, spices, starch ratio and dairy safety require community and kitchen expert review.',
		array( 'ingredient-syrian-red-meat', 'ingredient-syrian-fresh-yogurt' ),
		'Assyrian dikhwa from Qamishli with peeled barley, cooked meat and sour yogurt, documentary bowl portrait, no festival or religious symbols.', 'assyrian-syriac-qamishli'
	),
	array(
		'dish-al-mir-jazira', 'dish', 'al-mir-jazira', 'region-syria-jazira',
		'אל-מיר מהג׳זירה', 'Al-Mir from Jazira', 'syria-jazira', array( 'northpress-jazira-foodways' ),
		'מרק חיטה קלופה ויוגורט כבשים שתועד בדיווח אזורי מן הג׳זירה. שיוך כורדי, ערבי או רחב יותר נשאר פתוח.',
		'A peeled-wheat and sheep-yogurt soup documented in a regional Jazira report. Kurdish, Arab or broader ownership remains open.',
		'מקור אחד תומך ברכיבים הכלליים אך לא במפרט חיטה, תרבית יוגורט, מלח או חימום.',
		'One source supports the broad components but not the wheat, yogurt culture, salt or heating specification.',
		array( 'ingredient-syrian-fresh-yogurt' ),
		'Jazira al-mir soup with peeled wheat in sheep-yogurt base, simple earthen bowl, no community attribution cues.'
	),
	array(
		'hub-kutilk-shamburak-jazira', 'topic_hub', 'kutilk-shamburak-jazira', 'region-syria-jazira',
		'קוטילק ושאמבורק בג׳זירה', 'Kutilk and Shamburak in Jazira', 'syria-jazira', array( 'northpress-jazira-foodways' ),
		'רשומת זהות לשמות מקומיים שמוסברים כמשפחות קובה וסמבוסק, אך בצקים, מילויים, איות ובעלות קהילתית עדיין אינם פתורים.',
		'An identity record for local names glossed as kibbeh and sambousek families, while doughs, fillings, spellings and community ownership remain unresolved.',
		'השם לבדו אינו מספיק ליצירת מתכון, דף ציבורי או איחוד עם מנה ממדינה שכנה.',
		'The name alone is insufficient for a recipe, public page or merge with a dish from a neighboring country.',
		array(), 'Private comparative silhouette study of unresolved Jazira stuffed-dough forms, neutral fillings hidden, no invented recipe details.'
	),
	array(
		'dish-al-uruq-jazira', 'dish', 'al-uruq-jazira', 'region-syria-jazira',
		'אל-עורוק מהג׳זירה', 'Al-Uruq from Jazira', 'syria-jazira', array( 'northpress-jazira-foodways' ),
		'תערובת בורגול, סולת, בשר, בצל ואגוזים המעוצבת ומטוגנת, לפי דיווח אזורי מן הג׳זירה.',
		'A bulgur, semolina, meat, onion and nut mixture that is shaped and fried, according to a regional Jazira report.',
		'מקור אחד תומך בזהות וברכיבים, אך צורה, אלרגן האגוז, שמן וטמפרטורת טיגון דורשים אימות.',
		'One source supports the identity and components, while shape, nut allergen, oil and frying temperature require verification.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-semolina', 'ingredient-syrian-red-meat', 'ingredient-syrian-onion', 'ingredient-syrian-unspecified-nuts' ),
		'Fried Jazira al-uruq with bulgur and semolina texture visible, one cut piece showing fully cooked meat and nuts, no garnish.'
	),
	array(
		'dish-merge-hamees-jazira', 'dish', 'merge-hamees-jazira', 'region-syria-jazira',
		'מרגה ואל-חמיס מהג׳זירה', 'Merge and al-Hamees from Jazira', 'syria-jazira', array( 'northpress-jazira-foodways' ),
		'בשר אדום המבושל על עצים, עם ציר המוגש על לחם סאג׳ בהקשרי אירוח. השמות הכורדי והערבי נשמרים כחפיפה אפשרית ולא כזהות מוכחת.',
		'Red meat cooked over wood with broth served over saj bread in hospitality contexts. Kurdish and Arabic labels remain a possible overlap rather than a proven identity.',
		'המקור אינו מוכיח שהשמות מציינים מתכון זהה או מספק בקרת אש, חיתוך בשר וזמן בטיחות.',
		'The source does not prove that the labels mean one identical recipe or supply fire control, meat-cut or safety-time specifications.',
		array( 'ingredient-syrian-red-meat', 'technique-syrian-saj-bread' ),
		'Jazira wood-cooked meat and broth served over saj bread, controlled fire in background, no tribal or ethnic symbols.'
	),
	array(
		'technique-dermale-qawarma-jazira', 'technique', 'dermale-qawarma-jazira', 'region-syria-jazira',
		'דרמאלה, קלי וקאוורמה בג׳זירה', 'Dermale, Qali and Qawarma in Jazira', 'syria-jazira', array( 'northpress-jazira-foodways' ),
		'משפחת שימור בשר בשומן מן החי ומלח כפי שתועדה בג׳זירה. השמות והיחסים ביניהם נשמרים פתוחים.',
		'A family of meat preservation in animal fat and salt documented in Jazira. The names and relationships among them remain open.',
		'אין הוראות ציבוריות עד אימות תרמי, פעילות מים, מלח, אריזה, קירור וחיי מדף על ידי מומחה בטיחות מזון.',
		'No public instructions are allowed until thermal process, water activity, salt, packaging, refrigeration and shelf life are validated by a food-safety expert.',
		array( 'ingredient-syrian-red-meat' ),
		'Private food-safety study of cooked meat preserved under fat in sealed laboratory-neutral jars, no serving suggestion and no shelf-life text.'
	),
	array(
		'technique-jazira-wheat-to-bulgur', 'technique', 'jazira-wheat-to-bulgur', 'region-syria-jazira',
		'שרשרת החיטה והבורגול של הג׳זירה', 'Jazira Wheat-to-Bulgur Chain', 'syria-jazira', array( 'northpress-jazira-foodways' ),
		'ציר עיבוד המחבר חיטה לבורגול, ג׳ריש, סולת ובורגול דק, ומשם לטחנות, מאפיות ומנות אזוריות.',
		'A processing axis connecting wheat to bulgur, jreesh, semolina and fine bulgur, then to mills, bakeries and regional dishes.',
		'המקור תומך בשרשרת מושגית, אך אין ברשומה זן חיטה, גודל חלקיק, לחות, תשואה או ספק פעיל.',
		'The source supports a conceptual chain, while the record has no wheat variety, particle size, moisture, yield or active supplier.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-jreesh', 'ingredient-syrian-semolina' ),
		'Jazira wheat transformation study from whole grain to jreesh, coarse bulgur, fine bulgur and semolina in separate unbranded trays.'
	),
	array(
		'tradition-qamishli-eid-kleija-maamoul', 'tradition', 'qamishli-eid-kleija-maamoul', 'region-syria-qamishli-family-transmission',
		'קלייג׳ה ומעמול בחגי קמישלי', 'Qamishli Eid Kleija and Maamoul', 'syria-qamishli', array( 'ifrepo-qamishli-assyrian-foodways' ),
		'מסורת הכנת עוגיות ביתיות סביב עיד בקמישלי, הנשמרת כריבוי קהילתי ולא כמנהג של כל תושבי העיר.',
		'A home-cookie preparation tradition around Eid in Qamishli, retained as multicultural rather than a practice of every city resident.',
		'המקור תומך בהקשר העירוני והחגיגי, אך מילויים, חותמות, תבלינים וקהילה דורשים פירוק נוסף.',
		'The source supports city and holiday context, while fillings, molds, spices and community attribution require further decomposition.',
		array( 'ingredient-syrian-semolina' ),
		'Qamishli Eid cookie table with kleija and maamoul forms separated, home baking light, no religious symbols and no claim of one community.'
	),
	array(
		'dish-mansaf-qamishli-family', 'dish', 'mansaf-qamishli-family', 'region-syria-qamishli-family-transmission',
		'מנסף קמישלי בעדות משפחתית', 'Qamishli Mansaf in Family Transmission', 'syria-qamishli', array( 'avs-samar-qamishli' ),
		'מנסף שתועד במסירה משפחתית הקשורה לקמישלי, בידי מספרת שלא חיה בעיר. הרשומה אינה בעלות עירונית ואינה מתמזגת עם מליחי חוראני.',
		'A mansaf transmitted in a family linked to Qamishli by a narrator who never lived in the city. The record is not citywide ownership and does not merge with Haurani mlihi.',
		'המידע תקף לגרסת המשפחה בלבד ואינו מספק מדידה או תצפית ישירה על המטבח העירוני.',
		'The information applies only to the family version and supplies neither measurement nor direct observation of city foodways.',
		array( 'ingredient-syrian-rice', 'ingredient-syrian-red-meat' ),
		'Private family-transmission mansaf platter from Qamishli context, provenance-focused studio shot, no citywide or ethnic visual claim.'
	),
	array(
		'dish-juwayqat-qamishli-family', 'dish', 'juwayqat-qamishli-family', 'region-syria-qamishli-family-transmission',
		'ג׳וייקאת בעדות משפחתית מקמישלי', 'Juwayqat in Qamishli Family Transmission', 'syria-qamishli', array( 'avs-samar-qamishli' ),
		'מנה עם חלקי פנים ממולאים שתועדה בהקשר עיד במסירה משפחתית מקמישלי. זהות האיברים והניקוי דורשים מפרט בטיחות.',
		'A stuffed-offal dish documented in an Eid context through Qamishli family transmission. Organ identity and cleaning require a safety specification.',
		'המקור תומך בשם ובהקשר משפחתי בלבד, ללא הוראות ניקוי, מילוי, קירור או טמפרטורת ליבה.',
		'The source supports only the name and family context, with no cleaning, filling, refrigeration or core-temperature instructions.',
		array( 'ingredient-syrian-rice', 'ingredient-syrian-red-meat' ),
		'Private fully cooked stuffed-offal identity study, clean cross-section, no raw handling and no festive symbols.'
	),
	array(
		'tradition-qamishli-first-ramadan-white-dish', 'tradition', 'qamishli-first-ramadan-white-dish', 'region-syria-qamishli-family-transmission',
		'המנה הלבנה לפתיחת רמדאן בעדות משפחתית', 'First-Ramadan White Dish in Family Transmission', 'syria-qamishli', array( 'avs-samar-qamishli' ),
		'מנהג משפחתי של פתיחת רמדאן במנה לבנה, שנשמר כעדות ביתית ולא ככלל של קמישלי או של קהילה שלמה.',
		'A family practice of opening Ramadan with a white dish, retained as household testimony rather than a rule for Qamishli or an entire community.',
		'זהות המנה והסמליות דורשות מקור נוסף לפני פירוק למתכון או מסורת ציבורית.',
		'The dish identity and symbolism require another source before decomposition into a recipe or public tradition.',
		array(), 'Private family table with one neutral pale dish at the start of an evening meal, no religious symbols, no invented dish composition.'
	),

	/* Euphrates and Palmyra. */
	array(
		'dish-qaren-yaruq-deir-ez-zor', 'dish', 'qaren-yaruq-deir-ez-zor', 'region-syria-deir-ez-zor',
		'קארן יאריק בנוסח דיר א-זור', 'Deir ez-Zor Qaren Yaruq', 'syria-deir-ez-zor', array( 'avs-buthaina-east', 'ifrepo-deir-ez-zor-foodways' ),
		'חצילים מפוספסים או מטוגנים, פתוחים וממולאים בבשר, בצל ותבלינים, עם עגבנייה וציר. זו גרסה מקומית בתוך משפחת קרניאריק רחבה יותר.',
		'Striped or fried eggplants opened and filled with meat, onion and spices, finished with tomato and broth. This is a local version within the wider karniyarik family.',
		'המקורות תומכים בזהות אזורית אך אינם מוכיחים המצאה בדיר א-זור או נוסחה זהה למנה הטורקית.',
		'The sources support a regional identity but prove neither Deir ez-Zor invention nor identity with the Turkish dish.',
		array( 'ingredient-syrian-eggplant', 'ingredient-syrian-red-meat', 'ingredient-syrian-onion', 'ingredient-syrian-tomato-paste' ),
		'Deir ez-Zor qaren yaruq with striped eggplant filled with cooked meat in tomato broth, one clean cut section, no origin claim.'
	),
	array(
		'dish-thurud-bamiya-deir-ez-zor', 'dish', 'thurud-bamiya-deir-ez-zor', 'region-syria-deir-ez-zor',
		'ת׳רוד במיה מדיר א-זור', 'Deir ez-Zor Okra Thareed', 'syria-deir-ez-zor', array( 'ifrepo-deir-ez-zor-foodways' ),
		'תבשיל במיה המוגש במבנה של ת׳רוד עם לחם תנור או סאג׳, הנשמר בנפרד מן הת׳ריד הכפרי של א-רקה.',
		'An okra stew served structurally as thareed with tannour or saj bread, retained separately from rural Raqqa thareed.',
		'המקור תומך בבמיה ובלחם כמרכיבי המבנה אך אינו מספק זן במיה, חומציות או יחס ציר.',
		'The source supports okra and bread as structural components but supplies no okra variety, acidity or broth ratio.',
		array( 'ingredient-raqqa-tannour-bread' ),
		'Deir ez-Zor okra thareed with tender okra stew soaking torn tannour bread, layered texture visible, no Raqqa variant cues.'
	),
	array(
		'dish-chika-raqqa', 'dish', 'chika-raqqa', 'region-syria-raqqa',
		'צ׳יקה מא-רקה', 'Chika from Raqqa', 'syria-raqqa', array( 'ifrepo-raqqa-foodways' ),
		'בורגול דק עם גהי, פלפל או ממרח עגבניות, פטרוזיליה, בצל ותבלינים, מושרה ונלוש לצורות אצבע.',
		'Fine bulgur with ghee, pepper or tomato paste, parsley, onion and spices, steeped and kneaded into finger shapes.',
		'המקור מספק תיאור מתכון, אך האיות הערבי המקומי, מידת הבורגול וזמן ההשריה דורשים אימות.',
		'The source provides a recipe description, while local Arabic spelling, bulgur grade and steeping time require verification.',
		array( 'ingredient-syrian-bulgur', 'ingredient-syrian-samn', 'ingredient-syrian-red-pepper-paste', 'ingredient-syrian-tomato-paste', 'ingredient-syrian-onion' ),
		'Raqqa chika finger shapes with fine bulgur, herbs and red paste visible, matte close-up, no raw meat resemblance.'
	),
	array(
		'dish-siyayil-raqqa-deir', 'dish', 'siyayil-raqqa-deir', 'region-syria-raqqa',
		'סיאייל מא-רקה והפרת', 'Siyayil from Raqqa and the Euphrates', 'syria-euphrates', array( 'avs-rana-raqqa', 'ifrepo-raqqa-foodways' ),
		'שכבות לחם סאג׳ או תנור דק עם גהי, אגוזים, קינמון וסירופ, סוכר או חלב. א-רקה היא בעלת התיעוד החזק ביותר, עם חפיפה מתועדת בדיר א-זור.',
		'Layers of thin saj or tannour bread with ghee, nuts, cinnamon and syrup, sugar or milk. Raqqa has the strongest evidence, with documented Deir ez-Zor overlap.',
		'המקורות תומכים במשפחת המנה ובהכנה נשית משותפת, אך תוספות ויחסים משתנים בין עדויות.',
		'The sources support the dish family and collective preparation, while additions and ratios vary among accounts.',
		array( 'ingredient-raqqa-tannour-bread', 'ingredient-syrian-samn', 'ingredient-syrian-walnuts', 'ingredient-syrian-sugar-syrup' ),
		'Layered Euphrates siyayil with paper-thin bread, ghee, walnuts and cinnamon, syrup catching side light, communal platter without people.'
	),
	array(
		'technique-home-shairiyya-euphrates', 'technique', 'home-shairiyya-euphrates', 'region-syria-euphrates-east',
		'שעירייה ביתית באזור הפרת', 'Homemade Vermicelli in the Euphrates Region', 'syria-euphrates', array( 'ifrepo-raqqa-foodways' ),
		'מסורת חורף של ייצור אטריות דקות בבית ובקהילה בכפרי א-רקה ודיר א-זור, המתוארת כמנהג הולך ונחלש.',
		'A winter tradition of making fine noodles at home and collectively in rural Raqqa and Deir ez-Zor, described as declining.',
		'איות השם, כלי העבודה, הבצק והעוסקות הפעילות כיום דורשים תיעוד שדה נוסף.',
		'Spelling, tools, dough and currently active practitioners require further field documentation.',
		array(), 'Documentary food-process shot of handmade fine vermicelli drying in parallel strands, rural indoor winter light, hands only and no costume.'
	),
	array(
		'dish-burma-palmyra', 'dish', 'burma-palmyra', 'region-syria-palmyra',
		'בורמה תדמורית', 'Palmyrene Burma', 'syria-palmyra', array( 'avs-buthaina-east' ),
		'מנת חג של חיטה מלאה ובשר בבישול קהילתי ארוך בכלי קדרייה מצופה חמר, עם גהי, לפי עדות מתדמור.',
		'A feast dish of whole wheat and meat cooked communally for a long time in a clay-coated qidriya pot with ghee, according to a Palmyra testimony.',
		'הזהות נשמרת בנפרד מקובה בשם בורמה שתועדה במקור חלבי, ואין הוראת זמן או טמפרטורה ציבורית.',
		'The identity remains separate from a kibbeh called burma in an Aleppine source, and no public time or temperature instruction is provided.',
		array( 'ingredient-syrian-red-meat', 'ingredient-syrian-samn' ),
		'Palmyrene burma feast pot with whole wheat and fully cooked meat in a clay-coated vessel, communal scale implied without people.'
	),
	array(
		'dish-kishk-mhabbash-palmyra', 'dish', 'kishk-mhabbash-palmyra', 'region-syria-palmyra',
		'קישק מחבש תדמורי', 'Palmyrene Kishk Mhabbash', 'syria-palmyra', array( 'avs-buthaina-east' ),
		'מנה מורכבת מקישק, קטניות, בורגול, כרוב, בצל ושום שתועדה בתדמור, עם זהות נפרדת מקישק כחומר גלם.',
		'A dish combining kishk, legumes, bulgur, cabbage, onion and garlic documented in Palmyra, distinct from kishk as an ingredient.',
		'המקור תומך בסל רכיבים רחב אך אינו מספק סוג קטנית, ריכוז קישק, מלח, חומציות או חיי מדף.',
		'The source supports a broad component set but supplies no legume type, kishk concentration, salt, acidity or shelf life.',
		array( 'ingredient-syrian-kishk', 'ingredient-syrian-bulgur', 'ingredient-syrian-onion', 'ingredient-syrian-garlic' ),
		'Palmyrene kishk mhabbash with distinct legumes, bulgur, cabbage and onion in a thick cultured base, neutral warm bowl.'
	),

	/* Suwayda, Hauran and the south. */
	array(
		'dish-lazzaqiyyat-suwayda-hauran', 'dish', 'lazzaqiyyat-suwayda-hauran', 'region-syria-hauran',
		'לזאקייאת מסווידא וחוראן', 'Lazzaqiyyat from Suwayda and Hauran', 'syria-south', array( 'avs-ghaimana-suwayda', 'daraa24-hauran-foodways' ),
		'שכבות דקות מאוד של לחם סאג׳ או תנור עם סוכר וגהי או חמאה, ולעיתים שמן זית, פיסטוק, קוקוס או אגוזים. המנה אינה בלעדית לקהילה אחת.',
		'Very thin saj or tannour layers with sugar and ghee or butter, sometimes olive oil, pistachio, coconut or nuts. The dish is not exclusive to one community.',
		'המקורות תומכים בזהות דרומית משותפת, אך מספר השכבות והתוספות נשמרים כגרסאות ולא כנוסחה אחת.',
		'The sources support a shared southern identity, while layer count and additions remain variants rather than one formula.',
		array( 'ingredient-syrian-samn', 'ingredient-syrian-sugar-syrup', 'ingredient-syrian-pistachios', 'ingredient-syrian-unspecified-nuts' ),
		'Southern Syrian lazzaqiyyat with seven visible paper-thin layers, ghee sheen and optional pistachio kept to one side, no exclusivity cue.'
	),
	array(
		'institution-southern-syrian-madafa', 'culinary_institution', 'southern-syrian-madafa', 'region-syria-suwayda',
		'אל-מדאפה בדרום סוריה', 'The Madafa in Southern Syria', 'syria-south', array( 'mdpi-suwayda-madafa-2025', 'enab-southern-syrian-heritage-2025' ),
		'מוסד אירוח לקפה, מזון, קבלת אורחים, שיחה קהילתית ולעיתים פתרון סכסוכים. הוא משותף להקשרים דרומיים ואינו מוגדר רק כמבנה דרוזי.',
		'A hospitality institution for coffee, food, receiving guests, community deliberation and sometimes conflict resolution. It spans southern contexts and is not defined only as a Druze building.',
		'המקורות תומכים בתפקידים חברתיים והיסטוריים, אך אינם מוכיחים שכל מדאפה פעילה כיום או מציעה שירות מסחרי.',
		'The sources support social and historical roles but do not prove that every madafa operates today or offers a commercial service.',
		array(), 'Southern Syrian madafa interior study with Arabic coffee service, communal seating and food tray, no people, weapons, flags or religious symbols.'
	),
	array(
		'institution-hasel-al-door-suwayda', 'culinary_institution', 'hasel-al-door-suwayda', 'institution-southern-syrian-madafa',
		'חסל א-דור בג׳בל אל-ערב', 'Hasel al-Door in Jabal al-Arab', 'syria-suwayda', array( 'mdpi-suwayda-madafa-2025' ),
		'מחסן תבואה שיתופי כפרי, בעיקר לשעורה, שתמך באירוח ובהזנת בעלי החיים שהגיעו עם אורחים לפי מחקר מורשת.',
		'A cooperative village grain store, primarily for barley, that supported hospitality and animals arriving with guests according to a heritage study.',
		'איות השם בערבית והמשך קיום המוסד כיום דורשים ביקורת קהילה ותיעוד שדה.',
		'Arabic spelling and present-day survival of the institution require community review and field documentation.',
		array(), 'Heritage study of a cooperative barley store beside a madafa supply area, clean architectural documentation and no active-operation claim.'
	),
	array(
		'ingredient-suwayda-grape-molasses', 'ingredient', 'suwayda-grape-molasses', 'region-syria-suwayda',
		'דיבס ענבים מסווידא', 'Suwayda Grape Molasses', 'syria-suwayda', array( 'enab-suwayda-grape-molasses', 'avs-ghaimana-suwayda' ),
		'מוצר מונה עונתי מענבים המקושר לתרבות המטעים של סווידא, כולל הגשה עם טחינה בעדות אחת.',
		'A seasonal grape-molasses mouneh product linked to Suwayda orchard culture, including serving with tahini in one testimony.',
		'המקורות אינם מספקים זן ענב, בריקס, pH, פעילות מים או חיי מדף, ולכן אין הוראות ריכוז או טענת בריאות.',
		'The sources supply no grape variety, Brix, pH, water activity or shelf life, so no concentration instructions or health claims are made.',
		array( 'ingredient-syrian-tahini' ),
		'Dark Suwayda grape molasses in an unbranded glass jar beside grapes and a small tahini pairing bowl, no health or origin seal.'
	),
	array(
		'ingredient-hauran-dried-dairy-system', 'ingredient', 'hauran-dried-dairy-system', 'region-syria-hauran',
		'ג׳מיד, כת׳י והקט בחוראן', 'Jameed, Kathi and Haqt in Hauran', 'syria-hauran', array( 'avs-shahla-hauran', 'daraa24-hauran-foodways', 'ifrepo-hauran-foodways' ),
		'מערכת שמות למוצרי חלב מלוחים ומיובשים המשמשים למליחי ולמנות דרומיות. המונחים נשמרים בנפרד עד להוכחת זהות טכנולוגית.',
		'A naming system for salted dried dairy used in mlihi and southern dishes. The terms remain separate until technological identity is demonstrated.',
		'המקורות תומכים בשימוש אזורי אך אינם מספקים תרבית, מלח, לחות, פעילות מים, מיקרוביולוגיה או מפרט אחסון.',
		'The sources support regional use but supply no culture, salt, moisture, water activity, microbiology or storage specification.',
		array( 'ingredient-syrian-jameed', 'ingredient-syrian-haqt' ),
		'Comparative ingredient study of three dried-dairy forms from Hauran in separate unbranded bowls, texture-focused and no shelf-life cue.'
	),
	array(
		'technique-hauran-grain-shrak-tannour', 'technique', 'hauran-grain-shrak-tannour', 'region-syria-hauran',
		'מערכת הדגן והלחם של חוראן', 'Hauran Grain and Bread System', 'syria-hauran', array( 'ifrepo-hauran-foodways', 'avs-shahla-hauran' ),
		'ציר המחבר חיטה ושעורה, בורגול גס, לחם שרק ותנור למליחי ולארוחות משותפות בדרום.',
		'An axis connecting wheat and barley, coarse bulgur, shrak and tannour bread to mlihi and communal meals in the south.',
		'המקורות תומכים במבנה הקולינרי, אך אין ברשומה זן דגן, הידרציה, גודל חלקיק או פרוטוקול אפייה.',
		'The sources support the culinary structure, while the record contains no grain variety, hydration, particle size or baking protocol.',
		array( 'ingredient-syrian-bulgur', 'technique-syrian-tannour-bread', 'technique-syrian-saj-bread' ),
		'Hauran grain-and-bread study with barley, coarse bulgur, shrak and tannour bread separated around a communal meal base.'
	),

	/* Afrin identity held pending a second formula source. */
	array(
		'guide-boraniyeh-kulki-afrin-held', 'guide', 'boraniyeh-kulki-afrin-held', 'region-syria-afrin-depth',
		'בוראנייה וקולקי בעפרין, בירור זהות', 'Boraniyeh and Kulki in Afrin, Identity Review', 'syria-afrin', array( 'avs-amani-afrin' ),
		'רשומת בירור לשני שמות שנזכרו בעדות מעפרין בלי די פירוט להכריע אם הם מנות נפרדות, גרסאות או שמות משפחתיים.',
		'An identity-review record for two names mentioned in an Afrin testimony without enough detail to determine whether they are separate dishes, variants or family terms.',
		'לא נוצרת נוסחת רכיבים, תמונת מנה סופית או דף ציבורי עד מקור נוסף וביקורת דובר מקומי.',
		'No component formula, final-dish image or public page is created until another source and local-speaker review are available.',
		array(), 'Abstract ingredient-boundary study for two unresolved Afrin dish names, pantry components only, no invented finished dish.', 'kurdish-afrin'
	),
);

foreach ( $c99_syrian_depth_rows as $row ) {
	$type = $row[1];
	$schema_type = 'Article';
	if ( 'topic_hub' === $type || 'guide' === $type ) {
		$schema_type = 'CollectionPage';
	} elseif ( 'restaurant' === $type || 'market' === $type ) {
		$schema_type = 'LocalBusiness';
	}
	$c99_syrian_depth_entities[] = $c99_syrian_depth_build( array(
		'id' => $row[0],
		'type' => $type,
		'slug' => $row[2],
		'parent_id' => $row[3],
		'name_he' => $row[4],
		'name_en' => $row[5],
		'region' => $row[6],
		'sources' => $row[7],
		'summary_he' => $row[8],
		'summary_en' => $row[9],
		'fact_he' => $row[10],
		'fact_en' => $row[11],
		'requires' => $row[12],
		'cross_sell_ids' => array_values(
			array_filter(
				$row[12],
				static function ( $target_id ) {
					return 0 === strpos( $target_id, 'ingredient-' )
						|| 0 === strpos( $target_id, 'equipment-' )
						|| 0 === strpos( $target_id, 'material-' );
				}
			)
		),
		'prompt_en' => $row[13],
		'community' => isset( $row[14] ) ? $row[14] : 'syrian-multi-community',
		'schema_type' => $schema_type,
	) );
}

$c99_syrian_depth_counts = array_count_values( array_column( $c99_syrian_depth_entities, 'type' ) );

return array(
	'schema' => 'complete99-syrian-regional-depth-module/v1',
	'version' => 'culinary-science-2026.08.08.v20',
	'sources' => $c99_syrian_depth_sources,
	'entities' => $c99_syrian_depth_entities,
	'private_entity_ids' => array_column( $c99_syrian_depth_entities, 'id' ),
	'counts' => array(
		'by_type' => $c99_syrian_depth_counts,
		'total_entities' => count( $c99_syrian_depth_entities ),
	),
);
