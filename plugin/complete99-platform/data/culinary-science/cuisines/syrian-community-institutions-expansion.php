<?php
/**
 * Complete99 Syrian community, institution and benchmark expansion.
 *
 * This module is private editorial research. It creates no public route,
 * endorsement, supplier, product, price, inventory, order or payment path.
 * Family and community evidence remains attached to the named source and does
 * not establish exclusive origin or one formula for a city or community.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $c99_syrian_entity ) || ! is_callable( $c99_syrian_entity ) ) {
	throw new RuntimeException( 'Syrian community expansion requires the Syrian entity builder.' );
}
if ( ! isset( $c99_syrian_fact ) || ! is_callable( $c99_syrian_fact ) ) {
	throw new RuntimeException( 'Syrian community expansion requires the Syrian fact builder.' );
}
if ( ! isset( $c99_relation ) || ! is_callable( $c99_relation ) || ! isset( $c99_compliance ) || ! is_callable( $c99_compliance ) || ! isset( $c99_text ) || ! is_callable( $c99_text ) ) {
	throw new RuntimeException( 'Syrian community expansion requires the shared registry builders.' );
}
if ( ! isset( $c99_syrian_sources ) || ! is_array( $c99_syrian_sources ) || ! isset( $c99_syrian_depth_sources ) || ! is_array( $c99_syrian_depth_sources ) ) {
	throw new RuntimeException( 'Syrian community expansion requires the Syrian source registries.' );
}

$c99_syrian_community_sources = array(
	'syrian-community-gastrosyr-academy' => array(
		'type' => 'official_organization',
		'publisher' => 'Syrian Academy of Gastronomy',
		'title' => 'Syrian Academy of Gastronomy official website',
		'url' => 'https://gastrosyr.com/eng/index.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-jfs-recipes-index' => array(
		'type' => 'official_organization',
		'publisher' => 'Jewish Food Society',
		'title' => 'Jewish Food Society recipe archive',
		'url' => 'https://www.jewishfoodsociety.org/recipes',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-foodish-anu-index' => array(
		'type' => 'official_organization',
		'publisher' => 'FOODISH, ANU Museum of the Jewish People',
		'title' => 'FOODISH community recipe archive',
		'url' => 'https://foodish.anumuseum.org.il/en/',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-asif-recipes-index' => array(
		'type' => 'official_organization',
		'publisher' => 'Asif, Culinary Institute of Israel',
		'title' => 'Asif recipe archive',
		'url' => 'https://asif.org/en/recipes/',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-loc-foodways-archive' => array(
		'type' => 'official_government',
		'publisher' => 'Library of Congress',
		'title' => 'Food and Foodways Web Archive',
		'url' => 'https://www.loc.gov/collections/food-and-foodways-web-archive/about-this-collection/',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-imads-official-2026' => array(
		'type' => 'official_business',
		'publisher' => 'Imad\'s Syrian Kitchen',
		'title' => 'About Imad\'s Syrian Kitchen',
		'url' => 'https://www.imadssyriankitchen.co.uk/our-story',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-imads-michelin-2026' => array(
		'type' => 'official_organization',
		'publisher' => 'Michelin Guide',
		'title' => 'Imad\'s Syrian Kitchen, London',
		'url' => 'https://guide.michelin.com/gb/en/greater-london/london/restaurant/imad-s-syrian-kitchen',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-le-petit-alep-official-2026' => array(
		'type' => 'official_business',
		'publisher' => 'Le Petit Alep',
		'title' => 'Le Petit Alep official website',
		'url' => 'https://www.restaurantalep.com/',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-le-petit-alep-michelin-2026' => array(
		'type' => 'official_organization',
		'publisher' => 'Michelin Guide',
		'title' => 'Le Petit Alep, Montreal',
		'url' => 'https://guide.michelin.com/ca/fr/quebec/montreal_2433514/restaurant/le-petit-alep',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-abu-hagop-visit-yerevan-2026' => array(
		'type' => 'official_organization',
		'publisher' => 'Visit Yerevan',
		'title' => 'Abu Hagop Restaurant',
		'url' => 'https://visityerevan.am/restaurants/details/185/en/',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-old-ashtarak-smithsonian' => array(
		'type' => 'official_organization',
		'publisher' => 'Smithsonian My Armenia',
		'title' => 'Learn to Cook Syrian-Armenian Fusion Cuisine',
		'url' => 'https://myarmenia.si.edu/en/guide/experience/learn-cook-syrian-armenian-fusion-cuisine/index.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-jfs-ejjeh' => array(
		'type' => 'official_organization',
		'publisher' => 'Jewish Food Society',
		'title' => 'Ejjeh, Syrian Vegetable Fritters',
		'url' => 'https://www.jewishfoodsociety.org/recipes/ejjeh-syrian-vegetable-fritters',
		'published_at' => '2024-09-12',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-jfs-heitaliyeh' => array(
		'type' => 'official_organization',
		'publisher' => 'Jewish Food Society',
		'title' => 'Heitaliyeh, Chilled Orange Blossom Pudding in Syrup',
		'url' => 'https://www.jewishfoodsociety.org/recipes/heitaliyeh-chilled-orange-blossom-pudding-in-syrup',
		'published_at' => '2026-04-30',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-jfs-string-cheese' => array(
		'type' => 'official_organization',
		'publisher' => 'Jewish Food Society',
		'title' => 'Syrian String Cheese',
		'url' => 'https://www.jewishfoodsociety.org/recipes/syrian-string-cheese',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-jfs-chicken-mehshi-sfeeha' => array(
		'type' => 'official_organization',
		'publisher' => 'Jewish Food Society',
		'title' => 'Chicken with Mehshi Sfeeha',
		'url' => 'https://www.jewishfoodsociety.org/recipes/chicken-with-mehshi-sfeeha-chicken-with-stuffed-eggplants',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-jfs-macaroni-chicken' => array(
		'type' => 'official_organization',
		'publisher' => 'Jewish Food Society',
		'title' => 'Macaroni Chicken, Baked Pasta with Chicken',
		'url' => 'https://www.jewishfoodsociety.org/recipes/macaroni-chicken-baked-pasta-with-chicken',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-loc-cardamom-tea' => array(
		'type' => 'official_government',
		'publisher' => 'Library of Congress',
		'title' => 'Cardamom and Tea archived website record',
		'url' => 'https://www.loc.gov/item/lcwaN0038125/',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'syrian-community-zmo-afrin-olive-oil-2025' => array(
		'type' => 'peer_reviewed_paper',
		'publisher' => 'Leibniz-Zentrum Moderner Orient',
		'title' => 'The Taste of Absence: Kurdish Oil, Loss and Memory among Immigrants from Afrin to Germany',
		'url' => 'https://www.zmo.de/en/publications/publication-search/the-taste-of-absence-1',
		'published_at' => '2025-01-01',
		'retrieved_at' => '2026-08-07',
	),
);

foreach ( $c99_syrian_community_sources as $source_id => $source ) {
	if ( isset( $c99_syrian_sources[ $source_id ] ) || isset( $c99_syrian_depth_sources[ $source_id ] ) ) {
		throw new RuntimeException( 'Duplicate Syrian community source ID: ' . $source_id );
	}
}

$c99_syrian_community_known_sources = array_replace( $c99_syrian_sources, $c99_syrian_depth_sources, $c99_syrian_community_sources );

$c99_syrian_community_build = static function ( $spec ) use ( $c99_syrian_entity, $c99_syrian_fact, $c99_relation, $c99_compliance, $c99_text ) {
	$sources = $spec['sources'];
	$relations = array();
	foreach ( $spec['references'] as $target_id ) {
		$relations[] = $c99_relation(
			'references',
			$target_id,
			'הקשר נשמר כקישור מחקרי תחום למקור, בלי למזג זהויות או להרחיב את הטענה מעבר לראיה.',
			'The connection remains a source-bounded research link without merging identities or extending the claim beyond the evidence.',
			false,
			$sources,
			isset( $spec['evidence'] ) ? $spec['evidence'] : 'official_source'
		);
	}

	$compliance = array(
		$c99_compliance(
			'community-source-no-exclusive-origin',
			'המידע מתאר את הרשומה, המשפחה, המוסד או נקודת הזמן שבמקור בלבד. אין להסיק מקור בלעדי, נוסחה קהילתית אחידה או בעלות על מאכל משותף.',
			'The evidence describes only the record, family, institution or dated point in the source. It does not establish exclusive origin, one community-wide formula or ownership of a shared food.',
			$sources,
			false
		),
		$c99_compliance(
			'original-visual-no-archive-copy',
			'הנכס החזותי ייווצר כקומפוזיציה מקורית בלבד. אין להעתיק צילום, מסמך, תפריט, סימן מסחר או עיצוב מן המקור.',
			'The visual asset must be an original composition. Do not copy a photograph, document, menu, trademark or design from the source.',
			$sources,
			false
		),
	);
	if ( 'culinary_institution' === $spec['type'] ) {
		$compliance[] = $c99_compliance(
			'archive-rights-and-representativeness',
			'יש לבדוק זכויות ברמת פריט. ארכיון או מוסד מספקים תיעוד, אך אינם מייצגים לבדם את כל המטבח או הקהילה.',
			'Check rights at item level. An archive or institution supplies documentation but does not by itself represent an entire cuisine or community.',
			$sources,
			false
		);
	}
	if ( in_array( $spec['type'], array( 'market', 'restaurant' ), true ) ) {
		$compliance[] = $c99_compliance(
			'benchmark-only-no-endorsement',
			'זהו Benchmark פרטי ללא המלצה, שותפות, פנייה, הזמנה, מחיר, מלאי, ספק או הבטחת פעילות נוכחית מעבר למועד האימות הרשום.',
			'This is a private benchmark with no endorsement, partnership, outreach, order, price, inventory, supplier or current-operation claim beyond the recorded verification date.',
			$sources,
			false
		);
	}
	if ( isset( $spec['compliance'] ) && is_array( $spec['compliance'] ) ) {
		foreach ( $spec['compliance'] as $control ) {
			$compliance[] = $c99_compliance(
				$control[0],
				$control[1],
				$control[2],
				$control[3],
				false
			);
		}
	}

	$tags = array( 'syrian-community-research', $spec['region'], $spec['community'], 'private-reference-only' );
	if ( isset( $spec['status_tag'] ) ) {
		$tags[] = $spec['status_tag'];
	}

	$entity = $c99_syrian_entity( array(
		'id' => $spec['id'],
		'type' => $spec['type'],
		'slug' => $spec['slug'],
		'parent_id' => $spec['parent_id'],
		'name' => $c99_text( $spec['name_he'], $spec['name_en'] ),
		'summary' => $c99_text( $spec['summary_he'], $spec['summary_en'] ),
		'region' => $spec['region'],
		'community' => $spec['community'],
		'primary_intent' => $c99_text( $spec['intent_he'], $spec['intent_en'] ),
		'primary_keyword' => $c99_text( $spec['keyword_he'], $spec['keyword_en'] ),
		'schema_type' => $spec['schema_type'],
		'facts' => array(
			$c99_syrian_fact(
				'fact-' . $spec['slug'] . '-documented-boundary',
				$spec['dimension'],
				$spec['fact_he'],
				$spec['fact_en'],
				isset( $spec['evidence'] ) ? $spec['evidence'] : 'official_source',
				isset( $spec['value_scope'] ) ? $spec['value_scope'] : 'entity',
				$sources
			),
		),
		'relations' => $relations,
		'categories' => array( 'culinary-museum', 'syrian-community-institutions', $spec['type'] . 's' ),
		'attributes' => array(
			'pa_region' => array( $spec['region'] ),
			'pa_community' => array( $spec['community'] ),
		),
		'tags' => $tags,
		'cross_sell_ids' => array(),
		'pricing_state' => 'research_required',
		'market_scope' => 'global_research',
		'prompt_en' => $spec['prompt_en'],
		'negative_prompt_en' => 'No text, no logos, no copied archive material, no copied restaurant interior, no menu, no price, no inventory, no supplier, no endorsement, no flags, no costumes, no watermark.',
		'asset_state' => 'rights_review_required',
		'rights_method' => 'generated_concept_with_human_review',
		'rights_state' => 'pending',
		'compliance' => $compliance,
	) );
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
	$entity['commerce']['business_model']['market_scope'] = 'global_research';
	$entity['commerce']['business_model']['observation_entity_ids'] = array();
	$entity['visual']['negative_prompt_en'] = 'No text, no logos, no copied archive material, no copied restaurant interior, no menu, no price, no inventory, no supplier, no endorsement, no flags, no costumes, no watermark.';
	$entity['trust']['substantive_updated_at'] = '2026-08-07';
	$entity['review']['reviewed_at'] = '2026-08-07';
	foreach ( $entity['relations'] as $relation_offset => &$relation ) {
		$relation['id'] = 'edge-' . $entity['id'] . '-' . $relation['type'] . '-' . ( $relation_offset + 1 );
	}
	unset( $relation );
	return $entity;
};

$c99_syrian_community_rows = array(
	/* Nine institutions and archives. */
	array(
		'id' => 'institution-agricultural-voices-syria',
		'type' => 'culinary_institution',
		'slug' => 'agricultural-voices-syria-institution',
		'parent_id' => 'cuisine-syrian-regional',
		'name_he' => 'קולות חקלאיים מסוריה, ארכיון עדויות',
		'name_en' => 'Agricultural Voices Syria, Testimony Archive',
		'summary_he' => 'ישות מוסדית פרטית לפרויקט של אוניברסיטת סאסקס המתעד חקלאות, מטבחים אזוריים, משפחות והעברה בין דורות באמצעות עדויות מזוהות. כל עדות נשארת קשורה למספרת, למקום ולמסמך שלה.',
		'summary_en' => 'A private institutional record for the University of Sussex project documenting agriculture, regional kitchens, families and intergenerational transmission through named testimonies. Every account remains tied to its narrator, place and document.',
		'region' => 'syria-multi-region-diaspora',
		'community' => 'syrian-plural-testimony-archive',
		'sources' => array( 'avs-syria-home', 'avs-heart-to-hearth' ),
		'fact_he' => 'הפרויקט מרכז עדויות אזוריות נפרדות. הוא מספק חלונות משפחתיים ומקומיים ואינו מוכיח שכיחות ארצית, נוסחה אחידה או ייצוג מלא של הקהילות בסוריה.',
		'fact_en' => 'The project assembles separate regional testimonies. It supplies family and local windows but does not establish nationwide prevalence, one formula or complete representation of Syrian communities.',
		'intent_he' => 'להבין מה ארכיון קולות חקלאיים מסוריה מתעד ומה גבולות כל עדות.',
		'intent_en' => 'Understand what Agricultural Voices Syria documents and the limits of each testimony.',
		'keyword_he' => 'ארכיון עדויות אוכל וחקלאות מסוריה',
		'keyword_en' => 'Agricultural Voices Syria foodways archive',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'cuisine-syrian-regional', 'region-syria-aleppo', 'region-syria-damascus' ),
		'prompt_en' => 'Original editorial evidence map with twelve blank testimony cards, regional pantry objects and audio-wave placeholders arranged in separate zones, soft documentary light, no copied pages, names, portraits, logos or maps.',
	),
	array(
		'id' => 'institution-ifpo-syria-recipes-cultures',
		'type' => 'culinary_institution',
		'slug' => 'ifpo-syria-recipes-cultures',
		'parent_id' => 'cuisine-syrian-regional',
		'name_he' => 'מתכונים ותרבויות מסוריה של IFPO',
		'name_en' => 'IFPO Syria Recipes and Cultures',
		'summary_he' => 'רשומת מוסד פרטית לסדרת פרקי מחקר אזוריים של המכון הצרפתי למזרח הקרוב. הפרקים נשמרים כמקורות נפרדים לקמישלי, א-רקה, חוראן ואזורים נוספים ולא מתמזגים לנוסח סורי אחד.',
		'summary_en' => 'A private institutional record for the French Institute for the Near East regional research chapters. Qamishli, Raqqa, Hauran and other chapters remain separate sources rather than one merged Syrian formula.',
		'region' => 'syria-multi-region',
		'community' => 'syrian-plural-research-archive',
		'sources' => array( 'ifrepo-qamishli-assyrian-foodways', 'ifrepo-raqqa-foodways', 'ifrepo-hauran-foodways' ),
		'fact_he' => 'הסדרה מספקת תיאורי מנות, שמות והקשרים אזוריים לפי פרק. מקור מפרק אחד אינו מועבר אוטומטית לעיר, לאזור או לקהילה אחרת.',
		'fact_en' => 'The series supplies dish descriptions, names and regional contexts chapter by chapter. Evidence from one chapter is not automatically transferred to another city, region or community.',
		'intent_he' => 'לנווט בין פרקי IFPO לפי האזור והקהילה שהפרק מתעד.',
		'intent_en' => 'Navigate IFPO chapters by the region and community each chapter documents.',
		'keyword_he' => 'IFPO מתכונים ותרבויות מסוריה',
		'keyword_en' => 'IFPO Syria recipes cultures archive',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'region-syria-qamishli-family-transmission', 'region-syria-raqqa', 'region-syria-hauran' ),
		'prompt_en' => 'Original research-library composition with three distinct regional recipe notebooks beside separate grain, dairy and herb samples, clean museum lighting, no copied pages, legible text, institutional logo or national symbol.',
	),
	array(
		'id' => 'institution-syrian-academy-of-gastronomy',
		'type' => 'culinary_institution',
		'slug' => 'syrian-academy-of-gastronomy',
		'parent_id' => 'cuisine-syrian-regional',
		'name_he' => 'האקדמיה הסורית לגסטרונומיה',
		'name_en' => 'Syrian Academy of Gastronomy',
		'summary_he' => 'רשומת מוסד מחקרית המבוססת על אתר האקדמיה ועל פרופיל האקדמיה הבינלאומית לגסטרונומיה. קיום האתרים מתועד, אך היקף הפעילות הנוכחית, הנהלה, תוכניות ואישורים דורשים אימות ישיר.',
		'summary_en' => 'A research institution record based on the academy website and the International Academy of Gastronomy profile. The websites are documented, while current activity, leadership, programs and approvals require direct verification.',
		'region' => 'syria-national',
		'community' => 'syrian-gastronomy-institution',
		'sources' => array( 'syrian-community-gastrosyr-academy', 'international-academy-gastronomy-syria' ),
		'fact_he' => 'שני המקורות מזהים גוף בשם האקדמיה הסורית לגסטרונומיה. הרשומה אינה טוענת שהגוף פעיל כיום, מכיר ב-Complete99 או מאשר תוכן כלשהו במערכת.',
		'fact_en' => 'Both sources identify a body named the Syrian Academy of Gastronomy. The record does not claim that it currently operates, recognizes Complete99 or approves any content in this system.',
		'intent_he' => 'לתעד את זהות האקדמיה ואת שאלות האימות שנותרו פתוחות.',
		'intent_en' => 'Document the academy identity and the verification questions that remain open.',
		'keyword_he' => 'האקדמיה הסורית לגסטרונומיה',
		'keyword_en' => 'Syrian Academy of Gastronomy',
		'schema_type' => 'Organization',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'cuisine-syrian-regional' ),
		'status_tag' => 'operational-status-unverified',
		'prompt_en' => 'Original neutral institutional research desk with blank program folders, culinary reference books and an empty verification checklist, architectural daylight, no seal, logo, certificate, copied website, staff portrait or active-program cue.',
	),
	array(
		'id' => 'institution-smithsonian-syrian-armenian-foodways',
		'type' => 'culinary_institution',
		'slug' => 'smithsonian-syrian-armenian-foodways-archive',
		'parent_id' => 'tradition-syrian-armenian-aleppo',
		'name_he' => 'תיעוד סורי-ארמני של Smithsonian Folklife',
		'name_en' => 'Smithsonian Syrian Armenian Foodways Record',
		'summary_he' => 'ישות מוסדית פרטית למאמר Smithsonian Folklife המתעד את משפחת קיליסליאן, מסעדה משפחתית בחלב, הגירה והמשכיות קולינרית. זהו תיעוד של משפחה ומסלול חיים מזוהים.',
		'summary_en' => 'A private institutional entity for the Smithsonian Folklife account documenting the Kilislian family, a family restaurant in Aleppo, migration and culinary continuity. It is a record of a named family and life journey.',
		'region' => 'aleppo-armenia-diaspora',
		'community' => 'syrian-armenian-kilislian-family',
		'sources' => array( 'smithsonian-syrian-armenian-foodways' ),
		'fact_he' => 'המקור קושר את סיפור המשפחה לחלב ולפעילות קולינרית בתפוצות. הוא אינו מדגם לכל הארמנים בסוריה ואינו מוכיח בעלות ארמנית או סורית בלעדית על לחמעג׳ון, סוג׳וק או מנות משותפות.',
		'fact_en' => 'The source connects the family story to Aleppo and culinary work in diaspora. It is not a sample of all Armenians in Syria and does not prove exclusive Armenian or Syrian ownership of lahmajoun, sujukh or shared dishes.',
		'intent_he' => 'לקרוא את תיעוד משפחת קיליסליאן בלי להפוך אותו לכלל קהילתי.',
		'intent_en' => 'Read the Kilislian family record without turning it into a community-wide rule.',
		'keyword_he' => 'תיעוד אוכל סורי ארמני Smithsonian',
		'keyword_en' => 'Smithsonian Syrian Armenian foodways archive',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'tradition-syrian-armenian-aleppo' ),
		'prompt_en' => 'Original family-foodways archive concept with an Aleppo restaurant memory represented by blank recipe cards, bread, cheese and cured sausage components in separate zones, no copied photograph, portrait, sign, logo or costume.',
	),
	array(
		'id' => 'institution-jewish-food-society-syrian-family-archive',
		'type' => 'culinary_institution',
		'slug' => 'jewish-food-society-syrian-family-archive',
		'parent_id' => 'tradition-syrian-jewish-foodways-depth',
		'name_he' => 'ארכיון המשפחות הסוריות של Jewish Food Society',
		'name_en' => 'Jewish Food Society Syrian Family Archive',
		'summary_he' => 'רשומת ארכיון פרטית לסיפורי משפחה ומתכונים סוריים-יהודיים שפורסמו ב-Jewish Food Society. כל רשומה נשמרת עם המשפחה, העיר, מסלול ההגירה והאירוע המתועדים בה.',
		'summary_en' => 'A private archive record for Syrian Jewish family stories and recipes published by Jewish Food Society. Each item remains attached to its family, city, migration route and documented occasion.',
		'region' => 'aleppo-damascus-diaspora',
		'community' => 'syrian-jewish-family-archive',
		'sources' => array( 'syrian-community-jfs-recipes-index', 'jfs-passover-kibbeh-damascus', 'jfs-yebra-apricots' ),
		'fact_he' => 'הארכיון מספק עדויות משפחתיות עשירות, אך בחירת משפחות לפרסום אינה מדגם סטטיסטי ואינה מגדירה מתכון אחד לכל יהודי חלב, דמשק או סוריה.',
		'fact_en' => 'The archive supplies rich family testimony, but editorial selection is not a statistical sample and does not define one recipe for all Aleppan, Damascene or Syrian Jews.',
		'intent_he' => 'לאתר סיפורי משפחה סוריים-יהודיים לפי עיר, משפחה ומסלול הגירה.',
		'intent_en' => 'Find Syrian Jewish family stories by city, family and migration route.',
		'keyword_he' => 'ארכיון מתכונים של יהודי סוריה',
		'keyword_en' => 'Syrian Jewish family recipe archive',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'tradition-aleppan-jewish-foodways', 'tradition-damascene-jewish-foodways', 'guide-syrian-jewish-kibbeh-family' ),
		'prompt_en' => 'Original bilingual family-recipe archive table with six blank catalog cards and six distinct cooked food silhouettes, warm documentary light, no copied recipe, handwriting, portrait, family name, logo or religious symbol.',
	),
	array(
		'id' => 'institution-foodish-anu-syrian-community-archive',
		'type' => 'culinary_institution',
		'slug' => 'foodish-anu-syrian-community-archive',
		'parent_id' => 'tradition-syrian-jewish-foodways-depth',
		'name_he' => 'ארכיון הקהילה הסורית של FOODISH ו-ANU',
		'name_en' => 'FOODISH and ANU Syrian Community Archive',
		'summary_he' => 'ישות מוסדית פרטית למתכוני קהילה ולחומרי הקשר של מוזיאון העם היהודי. רשומות חלב ודמשק נשמרות בנפרד ובזיקה למוסר המתכון המזוהה.',
		'summary_en' => 'A private institutional entity for community recipes and contextual material from the Museum of the Jewish People. Aleppan and Damascene records remain separate and tied to the named recipe contributor.',
		'region' => 'israel-syrian-jewish-diaspora',
		'community' => 'syrian-jewish-community-archive',
		'sources' => array( 'syrian-community-foodish-anu-index', 'anu-syrian-jewish-community', 'foodish-matzah-kebab', 'foodish-dajaj-mashwi' ),
		'fact_he' => 'FOODISH ו-ANU מספקים מתכונים ותוכן קהילתי מזוהים. הרשומה אינה מחליפה את ההבחנה בין יהודי חלב ליהודי דמשק ואינה הופכת מתכון קהילתי לפסק דין היסטורי.',
		'fact_en' => 'FOODISH and ANU supply identified recipes and community context. The record does not replace the distinction between Aleppan and Damascene Jews or turn a community recipe into a historical verdict.',
		'intent_he' => 'לנווט בתיעוד FOODISH ו-ANU תוך שמירת ההבחנה בין חלב לדמשק.',
		'intent_en' => 'Navigate FOODISH and ANU records while preserving the Aleppo and Damascus distinction.',
		'keyword_he' => 'FOODISH מתכוני יהודי סוריה',
		'keyword_en' => 'FOODISH Syrian Jewish community recipes',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'tradition-aleppan-jewish-foodways', 'tradition-damascene-jewish-foodways' ),
		'prompt_en' => 'Original museum research table with two clearly separated unlabeled archive zones for Aleppo and Damascus family recipes, neutral catalog lighting, no copied museum interface, recipe text, portrait, logo or city emblem.',
	),
	array(
		'id' => 'institution-asif-syrian-jewish-recipe-archive',
		'type' => 'culinary_institution',
		'slug' => 'asif-syrian-jewish-recipe-archive',
		'parent_id' => 'tradition-syrian-jewish-foodways-depth',
		'name_he' => 'ארכיון המתכונים הסוריים-יהודיים של אסיף',
		'name_en' => 'Asif Syrian Jewish Recipe Archive',
		'summary_he' => 'רשומת מוסד פרטית למתכונים סוריים-יהודיים המתועדים באסיף. בדיקת מתכון במטבח אסיף נשמרת כהצהרת אסיף ואינה הופכת לבדיקה, אישור או מוצר של Complete99.',
		'summary_en' => 'A private institutional record for Syrian Jewish recipes documented by Asif. Testing in the Asif kitchen remains an Asif statement and does not become a Complete99 test, approval or product.',
		'region' => 'israel-syrian-jewish-diaspora',
		'community' => 'syrian-jewish-editorial-archive',
		'sources' => array( 'syrian-community-asif-recipes-index', 'asif-medias-damascene' ),
		'fact_he' => 'הארכיון מספק דף מתכון ומסגרת עריכה מזוהים. הוא אינו מעיד שכל משפחה דמשקאית מכינה מדיאס באותו אופן ואינו מעניק זכויות שימוש בתמונות.',
		'fact_en' => 'The archive supplies an identified recipe page and editorial frame. It does not show that every Damascene family prepares medias the same way and grants no image-use rights.',
		'intent_he' => 'לזהות מתכונים סוריים-יהודיים בארכיון אסיף ואת גבולות הבדיקה והזכויות.',
		'intent_en' => 'Identify Syrian Jewish recipes in the Asif archive and the limits of testing and rights.',
		'keyword_he' => 'אסיף מתכונים סוריים יהודיים',
		'keyword_en' => 'Asif Syrian Jewish recipe archive',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'tradition-damascene-jewish-foodways', 'dish-medias-damascene-jewish' ),
		'prompt_en' => 'Original culinary test-library concept with a neutral stuffed-zucchini study, blank testing card and rights-review folder, bright editorial light, no copied recipe page, photograph, logo, handwriting or approval seal.',
	),
	array(
		'id' => 'institution-national-library-israel-syrian-jewish-context-archive',
		'type' => 'culinary_institution',
		'slug' => 'national-library-israel-syrian-jewish-context-archive',
		'parent_id' => 'tradition-syrian-jewish-foodways-depth',
		'name_he' => 'ארכיון ההקשר של יהודי חלב ודמשק בספרייה הלאומית',
		'name_en' => 'National Library of Israel Aleppo and Damascus Context Archive',
		'summary_he' => 'ישות מוסדית פרטית לשני מקורות תרבותיים נפרדים על מסורות יהודי חלב ויהודי דמשק. המקורות מספקים היסטוריה, קהילה ומסורת מוזיקלית, לא הוכחת רכיבים או הוראות בישול.',
		'summary_en' => 'A private institutional entity for two separate cultural sources on Aleppan and Damascene Jewish traditions. The sources supply history, community and musical tradition, not ingredient proof or cooking instructions.',
		'region' => 'aleppo-damascus-israel-diaspora',
		'community' => 'aleppan-and-damascene-jewish-context',
		'sources' => array( 'nli-aleppo-tradition', 'nli-damascus-tradition' ),
		'fact_he' => 'הספרייה הלאומית מציגה את חלב ואת דמשק כמסורות מובחנות. שימוש במקורות אלה למזון מוגבל להקשר הקהילתי בלבד ואינו תומך במתכון, במקור מנה או בשכיחות משפחתית.',
		'fact_en' => 'The National Library presents Aleppo and Damascus as distinct traditions. Food use is limited to community context and does not support a recipe, dish origin or household prevalence.',
		'intent_he' => 'להבין את ההבדל ההיסטורי בין קהילות חלב ודמשק בלי להפיק מן המקור טענת מתכון.',
		'intent_en' => 'Understand the historical distinction between Aleppo and Damascus without deriving a recipe claim.',
		'keyword_he' => 'מסורות יהודי חלב ודמשק הספרייה הלאומית',
		'keyword_en' => 'National Library Aleppo Damascus Jewish traditions',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'tradition-aleppan-jewish-foodways', 'tradition-damascene-jewish-foodways' ),
		'prompt_en' => 'Original split archive visualization with two separate unlabeled cultural catalog zones, abstract sound-wave lines and blank chronology cards, no copied manuscript, musical notation, portrait, library mark, food formula or city symbol.',
	),
	array(
		'id' => 'institution-library-of-congress-foodways-web-archive',
		'type' => 'culinary_institution',
		'slug' => 'library-of-congress-foodways-web-archive',
		'parent_id' => 'cuisine-syrian-regional',
		'name_he' => 'ארכיון הרשת למזון ודרכי אוכל בספריית הקונגרס',
		'name_en' => 'Library of Congress Food and Foodways Web Archive',
		'summary_he' => 'רשומת מוסד מחקרית לאוסף רשת רחב על מזון ודרכי אוכל. הוא משמש לאיתור מקורות השוואה, ובכללם ארכיון אשורי בתפוצות, אך אינו הופך מקור תפוצות לראיה סורית.',
		'summary_en' => 'A research institution record for a broad web collection on food and foodways. It supports discovery of comparison sources, including an Assyrian diaspora archive, but does not turn diaspora material into Syrian evidence.',
		'region' => 'global-foodways-archive',
		'community' => 'multi-community-comparison-archive',
		'sources' => array( 'syrian-community-loc-foodways-archive', 'syrian-community-loc-cardamom-tea' ),
		'fact_he' => 'תיאור האוסף והמטא דאטה של הפריט מאפשרים לזהות את מקור הרשת ואת הקשרו. זכויות, שלמות הארכוב והיקף הטענה נבדקים ברמת כל פריט.',
		'fact_en' => 'The collection description and item metadata identify the web source and its context. Rights, capture completeness and claim scope require item-level review.',
		'intent_he' => 'להשתמש בארכיון לאיתור מקורות השוואה בלי לייחס חומר תפוצות לסוריה.',
		'intent_en' => 'Use the archive to find comparison sources without attributing diaspora material to Syria.',
		'keyword_he' => 'ארכיון דרכי אוכל ספריית הקונגרס',
		'keyword_en' => 'Library of Congress foodways web archive',
		'schema_type' => 'CollectionPage',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'cuisine-syrian-regional', 'dish-dikhwa-qamishli-assyrian' ),
		'prompt_en' => 'Original web-archive research visualization with empty browser-frame silhouettes, preservation timestamps and separate foodways subject cards, cool museum light, no copied webpage, screenshot, logo, recipe text or archival image.',
	),

	/* One market and four dated or explicitly unverified restaurant benchmarks. */
	array(
		'id' => 'market-al-midan-damascus-sweets-corridor',
		'type' => 'market',
		'slug' => 'al-midan-damascus-sweets-corridor',
		'parent_id' => 'guide-damascene-sweets',
		'name_he' => 'מסדרון ייצור המתוקים באל-מידאן, דמשק',
		'name_en' => 'Al-Midan Damascus Sweets Production Corridor',
		'summary_he' => 'Benchmark מחקרי פרטי לרובע אל-מידאן כמרחב של בתי מלאכה וחנויות מתוקים משפחתיות לפי מאמר אוניברסיטאי. זו אינה רשימת ספקים, חנויות פעילות, מלאי, מחירון או המלצת קנייה.',
		'summary_en' => 'A private research benchmark for Al-Midan as a district of family sweet workshops and shops in a university essay. It is not a supplier directory, current-business list, inventory, price list or purchase recommendation.',
		'region' => 'damascus-al-midan',
		'community' => 'damascene-multi-community-market',
		'sources' => array( 'ritsumeikan-damascus-midan-sweets' ),
		'fact_he' => 'המאמר מתאר זיכרון שטח, ייצור משפחתי וריכוז מסחרי של מתוקים באל-מידאן. פעילות של עסק מסוים, כתובת, שעות, מוצר וזמינות דורשים אימות נפרד ומתוארך.',
		'fact_en' => 'The essay describes field memory, family production and a commercial concentration of sweets in Al-Midan. Any specific business, address, hours, product and availability require separate dated verification.',
		'intent_he' => 'להבין את אל-מידאן כמערכת ייצור מתוקים ולא כרשימת ספקים פעילה.',
		'intent_en' => 'Understand Al-Midan as a sweets production system rather than an active supplier list.',
		'keyword_he' => 'מתוקים אל מידאן דמשק',
		'keyword_en' => 'Al-Midan Damascus sweets corridor',
		'schema_type' => 'LocalBusiness',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'guide-damascene-sweets', 'region-syria-damascus' ),
		'status_tag' => 'district-benchmark-retrieved-2026-08-07',
		'prompt_en' => 'Original empty Damascene sweets-production corridor study with neutral workshop counters, copper trays and unbranded finished sweets viewed from a wide angle, no copied storefront, sign, vendor, customer, logo, price or purchase cue.',
	),
	array(
		'id' => 'restaurant-imads-syrian-kitchen-london',
		'type' => 'restaurant',
		'slug' => 'imads-syrian-kitchen-london-benchmark',
		'parent_id' => 'cuisine-syrian-regional',
		'name_he' => 'Imad\'s Syrian Kitchen בלונדון, Benchmark פרטי',
		'name_en' => 'Imad\'s Syrian Kitchen London, Private Benchmark',
		'summary_he' => 'Benchmark חיצוני מתוארך למסעדה בלונדון שהאתר הרשמי ומדריך Michelin קשרו לשף סורי שניהל מסעדות בדמשק. הרשומה אינה המלצה, שותפות, העתקת תפריט או דירוג מטעם Complete99.',
		'summary_en' => 'A dated external benchmark for a London restaurant whose official site and Michelin connect its chef to prior restaurant work in Damascus. The record is no Complete99 endorsement, partnership, menu copy or rating.',
		'region' => 'united-kingdom-london-syrian-diaspora',
		'community' => 'syrian-diaspora-restaurant-benchmark',
		'sources' => array( 'syrian-community-imads-official-2026', 'syrian-community-imads-michelin-2026' ),
		'fact_he' => 'שני המקורות נבדקו ב-7 באוגוסט 2026 ותיארו מסעדה פעילה בלונדון ואת הרקע הדמשקאי של Imad Alarnab. פעילות, תפריט, הכרה ושעות לאחר מועד זה דורשים בדיקה חדשה.',
		'fact_en' => 'Both sources were checked on 7 August 2026 and described an operating London restaurant and the Damascus background of Imad Alarnab. Operation, menu, recognition and hours after that date require a new check.',
		'intent_he' => 'לנתח הצגה בינלאומית של מסעדה סורית לפי צילום מצב מתוארך.',
		'intent_en' => 'Analyze international presentation of a Syrian restaurant from a dated snapshot.',
		'keyword_he' => 'מסעדה סורית בלונדון Benchmark',
		'keyword_en' => 'London Syrian restaurant benchmark',
		'schema_type' => 'LocalBusiness',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'cuisine-syrian-regional', 'region-syria-damascus' ),
		'status_tag' => 'operational-source-verified-2026-08-07',
		'prompt_en' => 'Original neutral restaurant benchmark with a bright London dining room abstraction and three fully cooked Syrian sharing plates arranged for comparative analysis, no copied interior, chef likeness, logo, menu, Michelin mark, price, reservation cue or endorsement.',
	),
	array(
		'id' => 'restaurant-le-petit-alep-montreal',
		'type' => 'restaurant',
		'slug' => 'le-petit-alep-montreal-benchmark',
		'parent_id' => 'tradition-syrian-armenian-aleppo',
		'name_he' => 'Le Petit Alep במונטריאול, Benchmark סורי-ארמני',
		'name_en' => 'Le Petit Alep Montreal, Syrian Armenian Benchmark',
		'summary_he' => 'Benchmark חיצוני מתוארך למסעדה במונטריאול שהאתר הרשמי ומדריך Michelin מציגים דרך שורשים משפחתיים סוריים וארמניים. הרשומה אינה המלצה, שותפות או הרשאה להעתיק תמונות, עיצוב או תפריט.',
		'summary_en' => 'A dated external benchmark for a Montreal restaurant whose official site and Michelin present Syrian and Armenian family roots. The record is no endorsement, partnership or permission to copy imagery, design or menu.',
		'region' => 'canada-montreal-syrian-armenian-diaspora',
		'community' => 'syrian-armenian-restaurant-benchmark',
		'sources' => array( 'syrian-community-le-petit-alep-official-2026', 'syrian-community-le-petit-alep-michelin-2026' ),
		'fact_he' => 'המקורות נבדקו ב-7 באוגוסט 2026 ותמכו בקיום המסעדה ובהצגה משולבת של מסורות משפחתיות סוריות וארמניות. הם אינם מוכיחים שמנה מסוימת בלעדית לאחת הזהויות.',
		'fact_en' => 'The sources were checked on 7 August 2026 and supported the restaurant identity and a combined presentation of Syrian and Armenian family traditions. They do not prove that a dish belongs exclusively to either identity.',
		'intent_he' => 'לנתח כיצד מסעדת תפוצות מציגה שורשים סוריים וארמניים יחד.',
		'intent_en' => 'Analyze how a diaspora restaurant presents Syrian and Armenian roots together.',
		'keyword_he' => 'מסעדה סורית ארמנית מונטריאול Benchmark',
		'keyword_en' => 'Montreal Syrian Armenian restaurant benchmark',
		'schema_type' => 'LocalBusiness',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'tradition-syrian-armenian-aleppo' ),
		'status_tag' => 'operational-source-verified-2026-08-07',
		'prompt_en' => 'Original Montreal restaurant benchmark with two complementary Syrian Armenian table zones, refined contemporary lighting and unbranded cooked dishes, no copied room, plating, logo, menu, award mark, price, staff, customer or endorsement.',
	),
	array(
		'id' => 'restaurant-abu-hagop-yerevan',
		'type' => 'restaurant',
		'slug' => 'abu-hagop-yerevan-benchmark',
		'parent_id' => 'tradition-syrian-armenian-aleppo',
		'name_he' => 'Abu Hagop בירוואן, Benchmark משפחתי סורי-ארמני',
		'name_en' => 'Abu Hagop Yerevan, Syrian Armenian Family Benchmark',
		'summary_he' => 'Benchmark פרטי המחבר בין תיעוד Smithsonian של משפחת קיליסליאן ובין רישום תיירותי של המסעדה בירוואן. זהו רצף משפחתי מתועד, לא המלצה, שותפות או אישור איכות.',
		'summary_en' => 'A private benchmark connecting Smithsonian documentation of the Kilislian family with a Yerevan tourism listing for the restaurant. It is documented family continuity, not an endorsement, partnership or quality approval.',
		'region' => 'armenia-yerevan-syrian-armenian-diaspora',
		'community' => 'syrian-armenian-kilislian-restaurant-benchmark',
		'sources' => array( 'smithsonian-syrian-armenian-foodways', 'syrian-community-abu-hagop-visit-yerevan-2026' ),
		'fact_he' => 'מקור Smithsonian מתעד את מסלול המשפחה, ורישום Visit Yerevan שנבדק ב-7 באוגוסט 2026 סיפק נקודת קיום עסקית נוספת. תפריט, שעות וזמינות דורשים אימות מחדש לפני כל שימוש.',
		'fact_en' => 'The Smithsonian source documents the family journey, while a Visit Yerevan listing checked on 7 August 2026 supplied another business-existence point. Menu, hours and availability require re-verification before any use.',
		'intent_he' => 'לתעד רצף של יזמות אוכל סורית-ארמנית מחלב לירוואן.',
		'intent_en' => 'Document Syrian Armenian food-enterprise continuity from Aleppo to Yerevan.',
		'keyword_he' => 'Abu Hagop מסעדה סורית ארמנית',
		'keyword_en' => 'Abu Hagop Syrian Armenian restaurant',
		'schema_type' => 'LocalBusiness',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'tradition-syrian-armenian-aleppo' ),
		'status_tag' => 'directory-source-verified-2026-08-07',
		'prompt_en' => 'Original Yerevan family-restaurant benchmark with a neutral open-kitchen silhouette and separate bread, cured sausage and cheese preparation zones, no copied storefront, family portrait, logo, menu, price, reservation control or endorsement.',
	),
	array(
		'id' => 'restaurant-old-ashtarak-syrian-armenian',
		'type' => 'restaurant',
		'slug' => 'old-ashtarak-syrian-armenian-benchmark',
		'parent_id' => 'tradition-syrian-armenian-aleppo',
		'name_he' => 'Old Ashtarak, תיעוד מסעדה סורית-ארמנית',
		'name_en' => 'Old Ashtarak, Documented Syrian Armenian Restaurant',
		'summary_he' => 'Benchmark היסטורי פרטי מתוך תוכנית My Armenia של Smithsonian המתעד מסעדה משפחתית ומפגש בישול סורי-ארמני. מצב הפעילות הנוכחי אינו מאומת ואין לפרסם שעות, הזמנה או זמינות.',
		'summary_en' => 'A private historical benchmark from the Smithsonian My Armenia program documenting a family restaurant and Syrian Armenian cooking experience. Current operation is unverified, so no hours, booking or availability may be published.',
		'region' => 'armenia-ashtarak-syrian-armenian-diaspora',
		'community' => 'syrian-armenian-family-restaurant-record',
		'sources' => array( 'syrian-community-old-ashtarak-smithsonian' ),
		'fact_he' => 'המקור תיעד את המסעדה ואת חוויית הבישול במועד הפרסום שלו. הוא אינו מקור עדכני מספיק להוכחת פעילות ב-2026, ולכן הסטטוס נשאר unverified.',
		'fact_en' => 'The source documented the restaurant and cooking experience at its publication time. It is not current enough to establish operation in 2026, so status remains unverified.',
		'intent_he' => 'לשמר תיעוד של אירוח ובישול סורי-ארמני בלי לטעון לפעילות נוכחית.',
		'intent_en' => 'Preserve documentation of Syrian Armenian hospitality and cooking without claiming current operation.',
		'keyword_he' => 'Old Ashtarak אוכל סורי ארמני',
		'keyword_en' => 'Old Ashtarak Syrian Armenian foodways',
		'schema_type' => 'LocalBusiness',
		'dimension' => 'institutional',
		'evidence' => 'official_source',
		'value_scope' => 'market_snapshot',
		'references' => array( 'tradition-syrian-armenian-aleppo' ),
		'status_tag' => 'operational-status-unverified',
		'prompt_en' => 'Original historical restaurant-research scene with an empty Armenian courtyard abstraction, a neutral cooking-workshop table and separate fully cooked dishes, no copied venue, people, logo, brochure, menu, price, current-operation cue or endorsement.',
	),

	/* Six source-bounded Syrian Jewish family entities. */
	array(
		'id' => 'dish-passover-kibbeh-damascene-jewish',
		'type' => 'dish',
		'slug' => 'passover-kibbeh-damascene-jewish-family',
		'parent_id' => 'tradition-damascene-jewish-holiday-foodways',
		'name_he' => 'קובה לפסח במשפחה יהודית דמשקאית',
		'name_en' => 'Passover Kibbeh in a Damascene Jewish Family',
		'summary_he' => 'רשומת מנה משפחתית מן הארכיון של Jewish Food Society שבה מעטפת לפסח מבוססת על אורז טחון וקמח מצה. היא אינה נוסחה לכל יהודי דמשק ואינה מחליפה משפחות קובה אחרות.',
		'summary_en' => 'A family dish record from Jewish Food Society in which a Passover shell uses ground rice and matzo meal. It is not a formula for all Damascene Jews and does not replace other kibbeh families.',
		'region' => 'damascus-jewish-diaspora',
		'community' => 'damascene-jewish-family',
		'sources' => array( 'jfs-passover-kibbeh-damascus' ),
		'fact_he' => 'המקור מתעד גרסה משפחתית דמשקאית לפסח ומפרט את מבנה המעטפת והמילוי. פרסום מתכון של Complete99 עדיין דורש בדיקת מטבח, תפוקה, אלרגנים ובטיחות מזון.',
		'fact_en' => 'The source documents a Damascene family Passover version and describes its shell and filling structure. A Complete99 recipe still requires kitchen testing, yield, allergen and food-safety review.',
		'intent_he' => 'להכיר גרסת קובה לפסח המתועדת במשפחה יהודית דמשקאית.',
		'intent_en' => 'Understand a Passover kibbeh version documented in a Damascene Jewish family.',
		'keyword_he' => 'קובה לפסח יהודי דמשק',
		'keyword_en' => 'Damascene Jewish Passover kibbeh',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'guide-syrian-jewish-kibbeh-family', 'tradition-damascene-jewish-foodways' ),
		'compliance' => array(
			array( 'ground-meat-source-cold-chain-and-thermal-validation', 'הבשר הטחון דורש מקור ואצווה מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי של המילוי ושל המנה בפועל.', 'Ground meat requires identified source and lot, cold chain, cross-contamination controls and dish-specific thermal validation for both filling and finished dish.', array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ) ),
			array( 'matzo-sesame-allergen-and-frying-validation', 'יש לאמת את מפרט קמח המצה והטחינה, אלרגני גלוטן ושומשום, מגע צולב, שמן הטיגון, חום, קירור והחזקה.', 'Verify matzo-meal and tahini specifications, gluten and sesame allergens, cross-contact, frying oil, heat, cooling and holding.', array( 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene', 'foodsafety-safe-temperatures' ) ),
			array( 'passover-status-requires-authority-review', 'אין להציג התאמה לפסח כמעמד מסחרי או תעודה. כל טענת מוצר עתידית דורשת מפרט מלא ואישור מן הגורם המוסמך שנבחר.', 'Do not present Passover suitability as a commercial status or certification. Any future product claim requires a complete specification and review by the selected qualified authority.', array( 'jfs-passover-kibbeh-damascus' ) ),
		),
		'prompt_en' => 'Original commercial culinary studio study of fully cooked Passover kibbeh from one Damascene Jewish family record, one piece cut to show a rice and matzo-meal shell around cooked filling, neutral plate, soft side light, no ritual prop, family symbol or origin seal.',
	),
	array(
		'id' => 'dish-ejjeh-syrian-jewish-family',
		'type' => 'dish',
		'slug' => 'ejjeh-syrian-jewish-family',
		'parent_id' => 'tradition-syrian-jewish-migration-adaptation',
		'name_he' => 'עג׳ה בעדות משפחתית סורית-יהודית',
		'name_en' => 'Ejjeh in a Syrian Jewish Family Record',
		'summary_he' => 'לביבות ירק מתועדות בסיפור משפחתי שעובר בין ביירות, חלב וארצות הברית. הרשומה שומרת את מסלול המשפחה ואינה מגדירה את המנה כשייכת בלעדית לעיר או לקהילה אחת.',
		'summary_en' => 'Vegetable fritters documented in a family story moving through Beirut, Aleppo and the United States. The record preserves the family route and does not define the dish as exclusive to one city or community.',
		'region' => 'aleppo-beirut-united-states-diaspora',
		'community' => 'syrian-jewish-family-diaspora',
		'sources' => array( 'syrian-community-jfs-ejjeh' ),
		'fact_he' => 'המקור פורסם ב-12 בספטמבר 2024 ומתעד את גרסת המשפחה ואת מסלול ההעברה שלה. הוא אינו מדגם של כל המטבח החלבי או הסורי-יהודי.',
		'fact_en' => 'The source was published on 12 September 2024 and documents the family version and its transmission route. It is not a sample of all Aleppan or Syrian Jewish cooking.',
		'intent_he' => 'להכיר עג׳ה דרך עדות משפחתית ומסלול הגירה מזוהה.',
		'intent_en' => 'Understand ejjeh through a named family record and migration route.',
		'keyword_he' => 'עג׳ה סורית יהודית משפחתית',
		'keyword_en' => 'Syrian Jewish family ejjeh',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'tradition-syrian-jewish-migration-adaptation', 'tradition-aleppan-jewish-foodways' ),
		'compliance' => array(
			array( 'egg-matzo-allergen-and-frying-validation', 'יש לאמת ביצים וקמח מצה, אלרגני ביצה וגלוטן, מגע צולב, שמן טיגון, בישול מלא, קירור והחזקה לפי הגרסה בפועל.', 'Verify eggs and matzo meal, egg and gluten allergens, cross-contact, frying oil, complete cooking, cooling and holding for the actual version.', array( 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Original culinary studio photograph of fully cooked herb-rich vegetable ejjeh fritters from one Syrian Jewish family record, crisp irregular edges and tender green interior, plain ceramic plate, daylight, no copied plating, family object, city emblem or exclusivity cue.',
	),
	array(
		'id' => 'dish-heitaliyeh-aleppan-jewish-panama',
		'type' => 'dish',
		'slug' => 'heitaliyeh-aleppan-jewish-panama-family',
		'parent_id' => 'tradition-syrian-jewish-migration-adaptation',
		'name_he' => 'הייטליה במשפחה יהודית מחלב בפנמה',
		'name_en' => 'Heitaliyeh in an Aleppan Jewish Family in Panama',
		'summary_he' => 'קינוח קר בניחוח מי זהר המתועד במשפחה שמסלולה מחלב לביירות ולפנמה. המקור מבחין בין גרסאות חלביות בסוריה לבין גרסאות פרווה בקהילה הסורית-יהודית ואינו קובע נוסחה אוניברסלית.',
		'summary_en' => 'A chilled orange-blossom dessert documented in a family journey from Aleppo through Beirut to Panama. The source distinguishes dairy versions in Syria from pareve versions in the Syrian Jewish community and does not establish a universal formula.',
		'region' => 'aleppo-beirut-panama-diaspora',
		'community' => 'aleppan-jewish-panama-family',
		'sources' => array( 'syrian-community-jfs-heitaliyeh' ),
		'fact_he' => 'המקור שפורסם ב-30 באפריל 2026 מתעד גרסה משפחתית והקשר קהילתי בפנמה. זהות חומר ההסמכה, יחס הסירופ וגרסת החלב נשארים תלויי נוסח.',
		'fact_en' => 'The source published on 30 April 2026 documents a family version and Panama community context. Thickener identity, syrup ratio and dairy version remain version specific.',
		'intent_he' => 'להכיר הייטליה כקינוח משפחתי שעבר מחלב לפנמה.',
		'intent_en' => 'Understand heitaliyeh as a family dessert transmitted from Aleppo to Panama.',
		'keyword_he' => 'הייטליה יהודי חלב פנמה',
		'keyword_en' => 'Aleppan Jewish heitaliyeh Panama',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'tradition-syrian-jewish-migration-adaptation', 'ingredient-syrian-orange-blossom-water' ),
		'compliance' => array(
			array( 'variant-specific-dairy-allergen-and-cold-chain-validation', 'אין להניח שהגרסה חלבית או פרווה לפי שם המנה. לכל גרסה נדרשים מפרט רכיבים, זיהוי אלרגני חלב, מניעת מגע צולב, קירור ותוכנית זמן-טמפרטורה מאומתת.', 'Do not infer dairy or pareve status from the dish name. Each version requires an ingredient specification, milk-allergen identity, cross-contact control, refrigeration and a validated time-temperature plan.', array( 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Original commercial dessert studio shot of chilled heitaliyeh cubes in clear syrup from one Aleppan Jewish Panama family record, delicate orange-blossom mood conveyed by a single blossom off the food, cool side light, no copied serving ware, family symbol or universal-recipe cue.',
	),
	array(
		'id' => 'ingredient-aleppan-jewish-string-cheese-family',
		'type' => 'ingredient',
		'slug' => 'aleppan-jewish-string-cheese-family',
		'parent_id' => 'tradition-aleppan-jewish-foodways',
		'name_he' => 'גבינת חוטים בעדות משפחתית של יהודי חלב',
		'name_en' => 'String Cheese in an Aleppan Jewish Family Record',
		'summary_he' => 'זהות חומר גלם משפחתית מתוך סיפור העברה מחלב למקסיקו, ברוקלין וניו ג׳רזי. הרשומה אינה SKU, אינה מציגה ספק או מלאי ואינה קובעת שכל גבינת חוטים סורית מיוצרת באותה שיטה.',
		'summary_en' => 'A family ingredient identity from a transmission story linking Aleppo, Mexico, Brooklyn and New Jersey. The record is not a SKU, supplier or inventory item and does not claim that all Syrian string cheese uses one method.',
		'region' => 'aleppo-mexico-united-states-diaspora',
		'community' => 'aleppan-jewish-family',
		'sources' => array( 'syrian-community-jfs-string-cheese' ),
		'fact_he' => 'המקור מתעד את שם הגבינה, תהליך משפחתי והקשר אכילה. מפרט מסחרי עתידי ידרוש סוג חלב, תרבית, מלח, לחות, מיקרוביולוגיה, אלרגנים ושרשרת קירור מאומתים.',
		'fact_en' => 'The source documents the cheese name, a family process and eating context. Any future commercial specification requires verified milk type, culture, salt, moisture, microbiology, allergens and cold chain.',
		'intent_he' => 'להבין גבינת חוטים דרך תיעוד משפחתי חלבי בלי להפוך אותה למוצר.',
		'intent_en' => 'Understand string cheese through an Aleppan family record without turning it into a product.',
		'keyword_he' => 'גבינת חוטים יהודי חלב',
		'keyword_en' => 'Aleppan Jewish family string cheese',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'tradition-aleppan-jewish-foodways', 'ingredient-syrian-cheese' ),
		'compliance' => array(
			array( 'dairy-pasteurization-allergen-and-cold-chain-validation', 'כל שימוש עתידי דורש חלב מפוסטר ומזוהה, תרבית ותהליך מאומתים, הצהרת אלרגן חלב, מניעת מגע צולב ושרשרת קירור מתועדת.', 'Any future use requires identified pasteurized milk, validated culture and process, milk-allergen declaration, cross-contact control and documented cold chain.', array( 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Original ingredient studio study of hand-separated white string cheese strands from one Aleppan Jewish family record in an unbranded chilled ceramic dish, macro texture, cool clean light, no package, label, supplier, price, stock cue or copied family photograph.',
	),
	array(
		'id' => 'dish-chicken-mehshi-sfeeha-aleppan-family',
		'type' => 'dish',
		'slug' => 'chicken-mehshi-sfeeha-aleppan-family',
		'parent_id' => 'tradition-aleppan-jewish-foodways',
		'name_he' => 'עוף עם מחשי ספיחה במשפחה יהודית מחלב',
		'name_en' => 'Chicken with Mehshi Sfeeha in an Aleppan Jewish Family',
		'summary_he' => 'מנה משפחתית המתועדת ב-Jewish Food Society ובה עוף מוגש עם חצילים ממולאים. השם וההרכב נשמרים לפי המשפחה ואין להסיק מהם גרסה אחידה לכל חלב.',
		'summary_en' => 'A family dish documented by Jewish Food Society in which chicken is served with stuffed eggplants. Its name and composition remain attached to the family and do not define one version for all Aleppo.',
		'region' => 'aleppo-jewish-diaspora',
		'community' => 'aleppan-jewish-family',
		'sources' => array( 'syrian-community-jfs-chicken-mehshi-sfeeha' ),
		'fact_he' => 'המקור מתעד את המנה ואת מסלול המשפחה. הרשומה אינה מפרט ייצור, וכל שימוש עתידי דורש בדיקת בישול עוף, מילוי, אלרגנים, קירור ותפוקה.',
		'fact_en' => 'The source documents the dish and family journey. This record is not a production specification, and any future use requires chicken-cooking, filling, allergen, cooling and yield validation.',
		'intent_he' => 'להכיר שילוב משפחתי חלבי של עוף וחצילים ממולאים.',
		'intent_en' => 'Understand an Aleppan family combination of chicken and stuffed eggplants.',
		'keyword_he' => 'עוף מחשי ספיחה יהודי חלב',
		'keyword_en' => 'Aleppan Jewish chicken mehshi sfeeha',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'tradition-aleppan-jewish-foodways' ),
		'compliance' => array(
			array( 'poultry-source-cold-chain-and-thermal-validation', 'העוף דורש מקור ואצווה מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי למנה ולמילוי בפועל.', 'Poultry requires identified source and lot, cold chain, cross-contamination controls and dish-specific thermal validation for both chicken and filling.', array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ) ),
			array( 'stuffed-vegetable-allergen-and-cooling-validation', 'יש לאמת את כל רכיבי המילוי והאלרגנים, את חדירת החום ואת הקירור וההחזקה של העוף והחצילים הממולאים.', 'Validate every filling ingredient and allergen, heat penetration, cooling and holding for the chicken and stuffed eggplants.', array( 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Original culinary studio photograph of fully cooked browned chicken beside small tender stuffed eggplants from one Aleppan Jewish family record, clear separation of components, warm side light, no copied plating, raw meat, family prop, city icon or origin claim.',
	),
	array(
		'id' => 'dish-macaroni-chicken-aleppan-diaspora',
		'type' => 'dish',
		'slug' => 'macaroni-chicken-aleppan-diaspora-family',
		'parent_id' => 'tradition-syrian-jewish-migration-adaptation',
		'name_he' => 'מקרוני ועוף במסלול משפחתי חלבי',
		'name_en' => 'Macaroni Chicken in an Aleppan Diaspora Family',
		'summary_he' => 'תבשיל אפוי של פסטה ועוף המתועד בסיפור משפחתי המחבר חלב, תל אביב ומנילה. זהו תיעוד של הסתגלות והעברה משפחתית ולא טענה שמדובר במנה כללית של יהודי סוריה.',
		'summary_en' => 'A baked pasta and chicken dish documented in a family story linking Aleppo, Tel Aviv and Manila. It records family adaptation and transmission rather than a claim that this is a general Syrian Jewish dish.',
		'region' => 'aleppo-tel-aviv-manila-diaspora',
		'community' => 'aleppan-jewish-diaspora-family',
		'sources' => array( 'syrian-community-jfs-macaroni-chicken' ),
		'fact_he' => 'המקור מתעד את שם המנה, גרסת המשפחה ומסלול ההגירה. הוא אינו מקור לקביעת מוצא היסטורי או שכיחות בחלב.',
		'fact_en' => 'The source documents the dish name, family version and migration route. It is not evidence for historical origin or prevalence in Aleppo.',
		'intent_he' => 'להכיר מנה משפחתית שהתפתחה לאורך מסלול הגירה חלבי.',
		'intent_en' => 'Understand a family dish developed along an Aleppan migration route.',
		'keyword_he' => 'מקרוני עוף משפחה יהודית מחלב',
		'keyword_en' => 'Aleppan Jewish macaroni chicken family',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'tradition-syrian-jewish-migration-adaptation' ),
		'compliance' => array(
			array( 'poultry-source-cold-chain-and-thermal-validation', 'העוף דורש מקור ואצווה מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי למנה בפועל.', 'Poultry requires identified source and lot, cold chain, cross-contamination controls and dish-specific thermal validation.', array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ) ),
			array( 'pasta-gluten-allergen-and-cooling-validation', 'יש לאמת את מפרט הפסטה ואלרגן הגלוטן, את רכיבי התבשיל ואת תוכנית האפייה, הקירור וההחזקה.', 'Verify the pasta specification and gluten allergen, casserole ingredients, and the baking, cooling and holding plan.', array( 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Original culinary studio shot of a fully baked macaroni and chicken family casserole with distinct pasta and cooked chicken visible in one cut portion, understated diaspora-home setting, no copied cookware, family photograph, national symbol or citywide claim.',
	),

	/* Five additional community entities. */
	array(
		'id' => 'dish-doshka-syrian-armenian-family',
		'type' => 'dish',
		'slug' => 'doshka-syrian-armenian-family',
		'parent_id' => 'tradition-syrian-armenian-aleppo',
		'name_he' => 'דושקה בתיעוד משפחה סורית-ארמנית',
		'name_en' => 'Doshka in a Syrian Armenian Family Record',
		'summary_he' => 'כריך חם של לחם, סוג׳וק וגבינה המתועד אצל משפחת קיליסליאן. Smithsonian מייחס במאמר את יצירתו לשפים ארמנים בסוריה, אך הרשומה שומרת זאת כייחוס המקור ולא כפסק דין בלעדי.',
		'summary_en' => 'A hot bread, sujukh and cheese sandwich documented with the Kilislian family. Smithsonian attributes its creation to Armenian chefs in Syria, while this record preserves that as source attribution rather than an exclusive verdict.',
		'region' => 'aleppo-syrian-armenian-diaspora',
		'community' => 'syrian-armenian-kilislian-family',
		'sources' => array( 'smithsonian-syrian-armenian-foodways' ),
		'fact_he' => 'המאמר מתאר שתי שכבות לחם עם סוג׳וק וגבינה ומקשר את המנה למשפחה ולניסיון המקצועי שלה. הוא אינו מפרט גבינה, מפרט סוג׳וק, טמפרטורה או שכיחות מעבר להקשר המתועד.',
		'fact_en' => 'The article describes two bread layers with sujukh and cheese and connects the dish to the family and its professional experience. It supplies no cheese specification, sujukh specification, temperature or prevalence beyond the documented context.',
		'intent_he' => 'להכיר דושקה דרך התיעוד הסורי-ארמני המשפחתי שלה.',
		'intent_en' => 'Understand doshka through its Syrian Armenian family documentation.',
		'keyword_he' => 'דושקה סורית ארמנית',
		'keyword_en' => 'Syrian Armenian doshka',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'entity',
		'references' => array( 'tradition-syrian-armenian-aleppo', 'institution-smithsonian-syrian-armenian-foodways' ),
		'compliance' => array(
			array( 'processed-meat-source-cold-chain-and-thermal-validation', 'הסוג׳וק דורש זיהוי יצרן, אצווה, מין בשר, רכיבים, אחסון, שרשרת קירור ואימות תרמי לפי המוצר המדויק.', 'Sujukh requires identified producer, lot, meat species, ingredients, storage, cold chain and thermal validation for the exact product.', array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ) ),
			array( 'dairy-and-gluten-allergen-product-validation', 'יש לאמת את סוג הגבינה והלחם, אלרגני חלב וגלוטן, מגע צולב ותנאי קירור והחזקה לפני כל שימוש.', 'Verify cheese and bread identity, milk and gluten allergens, cross-contact, refrigeration and holding before any use.', array( 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Original commercial culinary studio photograph of a hot doshka sandwich with two bread layers, cooked sujukh and melted cheese clearly visible in cross section, restrained family-table styling, no copied restaurant plating, logo, flag, costume or exclusive-origin seal.',
	),
	array(
		'id' => 'tradition-syrian-armenian-food-enterprise-diaspora',
		'type' => 'tradition',
		'slug' => 'syrian-armenian-food-enterprise-diaspora',
		'parent_id' => 'tradition-syrian-armenian-aleppo',
		'name_he' => 'יזמות אוכל סורית-ארמנית בתפוצות',
		'name_en' => 'Syrian Armenian Food Enterprise in Diaspora',
		'summary_he' => 'מסגרת פרטית לתיעוד משפחות שהעבירו ידע קולינרי מחלב למיזמי אוכל בארמניה ובתפוצות. היא מתארת רצף משפחתי מזוהה ואינה מייצגת את כל הקהילה הארמנית בסוריה.',
		'summary_en' => 'A private frame for families carrying culinary knowledge from Aleppo into food enterprises in Armenia and diaspora. It documents named family continuity and does not represent the entire Armenian community in Syria.',
		'region' => 'aleppo-armenia-global-diaspora',
		'community' => 'syrian-armenian-family-enterprise',
		'sources' => array( 'smithsonian-syrian-armenian-foodways', 'syrian-community-old-ashtarak-smithsonian', 'syrian-community-abu-hagop-visit-yerevan-2026' ),
		'fact_he' => 'המקורות מתעדים שני הקשרים משפחתיים של בישול ואירוח סורי-ארמני בארמניה. הם אינם מוכיחים רשת עסקית אחת, שותפות, מודל זכיינות או בעלות קהילתית על המנות.',
		'fact_en' => 'The sources document two family contexts of Syrian Armenian cooking and hospitality in Armenia. They do not establish one business network, partnership, franchise model or community ownership of dishes.',
		'intent_he' => 'להבין כיצד ידע אוכל סורי-ארמני עובר דרך משפחות ומיזמי תפוצות.',
		'intent_en' => 'Understand how Syrian Armenian food knowledge travels through families and diaspora enterprises.',
		'keyword_he' => 'יזמות אוכל סורית ארמנית בתפוצות',
		'keyword_en' => 'Syrian Armenian diaspora food enterprise',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'tradition-syrian-armenian-aleppo', 'restaurant-abu-hagop-yerevan', 'restaurant-old-ashtarak-syrian-armenian' ),
		'prompt_en' => 'Original visual timeline of Syrian Armenian family food enterprise using three unbranded kitchen-table stages linked by neutral recipe-card shapes, bread and pantry objects changing across settings, no maps, portraits, business logos, storefront copies, contracts or franchise cue.',
	),
	array(
		'id' => 'guide-assyrian-qamishli-cross-border-foodways-boundary',
		'type' => 'guide',
		'slug' => 'assyrian-qamishli-cross-border-foodways-boundary',
		'parent_id' => 'region-syria-qamishli-family-transmission',
		'name_he' => 'גבול מקורות למטבח אשורי בקמישלי ובתפוצות',
		'name_en' => 'Assyrian Qamishli and Diaspora Source Boundary',
		'summary_he' => 'מדריך פרטי המפריד בין עדות IFPO על דיחווה ואכיתו בקמישלי לבין ארכיון אשורי כללי בתפוצות המתעד מאכלים נוספים. חומר תפוצות אינו משמש הוכחה לגרסה סורית.',
		'summary_en' => 'A private guide separating IFPO testimony about dikhwa and Akitu in Qamishli from a general Assyrian diaspora archive documenting other foods. Diaspora material is not used as proof of a Syrian version.',
		'region' => 'qamishli-assyrian-diaspora-comparison',
		'community' => 'assyrian-qamishli-source-boundary',
		'sources' => array( 'ifrepo-qamishli-assyrian-foodways', 'syrian-community-loc-cardamom-tea' ),
		'fact_he' => 'מקור IFPO מספק עדות מסוריה על דיחווה בהקשר אכיתו. רשומת ספריית הקונגרס מתארת אתר תפוצות אשורי מאוסטרליה, ולכן קדה, בושאלה ועלי גפן מן האתר אינם מקבלים שיוך סורי אוטומטי.',
		'fact_en' => 'The IFPO source supplies Syrian testimony about dikhwa in an Akitu context. The Library of Congress record describes an Assyrian diaspora site from Australia, so its kadeh, booshala and grape leaves receive no automatic Syrian attribution.',
		'intent_he' => 'להבחין בין ראיה אשורית מקמישלי לבין חומר השוואה אשורי בתפוצות.',
		'intent_en' => 'Distinguish Assyrian evidence from Qamishli from Assyrian diaspora comparison material.',
		'keyword_he' => 'מטבח אשורי קמישלי גבולות מקורות',
		'keyword_en' => 'Assyrian Qamishli foodways source boundary',
		'schema_type' => 'CollectionPage',
		'dimension' => 'structural',
		'evidence' => 'official_source',
		'value_scope' => 'category',
		'references' => array( 'dish-dikhwa-qamishli-assyrian', 'institution-library-of-congress-foodways-web-archive' ),
		'prompt_en' => 'Original evidence-boundary graphic with one fully cooked dikhwa bowl in a Qamishli source zone and three abstract diaspora food silhouettes behind a clear divider, no copied archive image, costume, church symbol, map, recipe formula or merged-origin cue.',
	),
	array(
		'id' => 'tradition-afrin-kurdish-olive-oil-memory-diaspora',
		'type' => 'tradition',
		'slug' => 'afrin-kurdish-olive-oil-memory-diaspora',
		'parent_id' => 'tradition-kurdish-afrin',
		'name_he' => 'שמן זית, אובדן וזיכרון כורדי מאפרין',
		'name_en' => 'Afrin Kurdish Olive Oil, Loss and Diaspora Memory',
		'summary_he' => 'מסורת מחקרית פרטית על הקשר בין עצי זית, שמן מאפרין, עקירה, זיכרון וטיפול מרחוק במטעים בקרב מהגרים כורדים בגרמניה. היא אינה מפרט איכות, הצעת ספק או טענת זמינות.',
		'summary_en' => 'A private research tradition on relationships among olive trees, Afrin oil, displacement, memory and remote orchard care among Kurdish migrants in Germany. It is not a quality specification, supplier offer or availability claim.',
		'region' => 'afrin-germany-diaspora',
		'community' => 'kurdish-afrin-diaspora',
		'sources' => array( 'syrian-community-zmo-afrin-olive-oil-2025', 'avs-amani-afrin' ),
		'fact_he' => 'המחקר מ-2025 מתעד כיצד שמן ועצי זית פועלים כחומרי זיכרון וקשר למקום לאחר עקירה. הוא אינו מספק זן, חומציות, פוליפנולים, בדיקת מעבדה או זהות של אצווה מסחרית.',
		'fact_en' => 'The 2025 study documents how oil and olive trees act as media of memory and connection to place after displacement. It supplies no cultivar, acidity, polyphenols, laboratory test or commercial lot identity.',
		'intent_he' => 'להבין את משמעות שמן הזית בזיכרון הכורדי מאפרין בלי להפוך אותו למוצר.',
		'intent_en' => 'Understand the meaning of olive oil in Kurdish Afrin memory without turning it into a product.',
		'keyword_he' => 'שמן זית כורדי אפרין זיכרון',
		'keyword_en' => 'Afrin Kurdish olive oil memory',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'peer_reviewed_context',
		'value_scope' => 'entity',
		'references' => array( 'tradition-kurdish-afrin', 'ingredient-syrian-olive-oil' ),
		'prompt_en' => 'Original documentary still life of an Afrin olive branch, a small unbranded oil vessel and an empty correspondence space suggesting diaspora memory, quiet natural light, no copied landscape, person, map, flag, product label, quality seal, price, stock or supplier cue.',
	),
	array(
		'id' => 'technique-suwayda-qahwa-murra-hospitality-service',
		'type' => 'technique',
		'slug' => 'suwayda-qahwa-murra-hospitality-service',
		'parent_id' => 'institution-southern-syrian-madafa',
		'name_he' => 'שירות קפה מר באירוח של א-סווידא',
		'name_en' => 'Bitter Coffee Hospitality Service in Suwayda',
		'summary_he' => 'טכניקת אירוח פרטית המתמקדת בסדר ההגשה ובתפקיד החברתי של קפה מר במדאפה ובאירוח דרומי. היא אינה מפרט קלייה, טחינה, מינון, טמפרטורה או הוראת תפעול.',
		'summary_en' => 'A private hospitality technique focused on service sequence and the social role of bitter coffee in the madafa and southern hospitality. It is not a roasting, grinding, dose, temperature or operating specification.',
		'region' => 'suwayda-southern-syria',
		'community' => 'druze-and-southern-shared-hospitality',
		'sources' => array( 'mdpi-suwayda-madafa-2025', 'avs-ghaimana-suwayda', 'enab-southern-syrian-heritage-2025' ),
		'fact_he' => 'המקורות תומכים בקפה מר כחלק מטקס אירוח ובמדאפה כמוסד משותף בהקשרים דרומיים. הם אינם מוכיחים שכל מדאפה פעילה כיום, שכל משפחה משתמשת באותו סדר או שהמנהג שייך בלעדית לדרוזים.',
		'fact_en' => 'The sources support bitter coffee as part of hospitality ritual and the madafa as a shared institution in southern contexts. They do not prove that every madafa operates today, every family uses one sequence or the practice belongs exclusively to Druze communities.',
		'intent_he' => 'להבין את תפקיד שירות הקפה המר באירוח של א-סווידא והדרום.',
		'intent_en' => 'Understand the role of bitter coffee service in Suwayda and southern hospitality.',
		'keyword_he' => 'קפה מר אירוח א סווידא',
		'keyword_en' => 'Suwayda bitter coffee hospitality service',
		'schema_type' => 'Article',
		'dimension' => 'cultural',
		'evidence' => 'peer_reviewed_context',
		'value_scope' => 'technique_context',
		'references' => array( 'institution-southern-syrian-madafa', 'tradition-druze-suwayda' ),
		'prompt_en' => 'Original hospitality-service study with a small unbranded bitter-coffee pot, three empty handleless cups and a clear pouring sequence indicated only by spacing, warm madafa-inspired interior light, no people, costume, religious symbol, readable marks, recipe ratio or exclusivity cue.',
	),
);

$c99_syrian_community_entities = array();
foreach ( $c99_syrian_community_rows as $spec ) {
	$c99_syrian_community_entities[] = $c99_syrian_community_build( $spec );
}

$c99_syrian_community_expected_counts = array(
	'culinary_institution' => 9,
	'market' => 1,
	'restaurant' => 4,
	'dish' => 6,
	'ingredient' => 1,
	'tradition' => 2,
	'guide' => 1,
	'technique' => 1,
);
$c99_syrian_community_counts = array_count_values( array_column( $c99_syrian_community_entities, 'type' ) );

if ( 25 !== count( $c99_syrian_community_entities ) || $c99_syrian_community_expected_counts !== $c99_syrian_community_counts ) {
	throw new RuntimeException( 'Syrian community expansion must contain exactly 25 entities with the approved type distribution.' );
}

$c99_syrian_community_ids = array_column( $c99_syrian_community_entities, 'id' );
if ( count( $c99_syrian_community_ids ) !== count( array_unique( $c99_syrian_community_ids ) ) ) {
	throw new RuntimeException( 'Duplicate Syrian community entity ID.' );
}

$c99_syrian_community_prompts = array();
foreach ( $c99_syrian_community_entities as $entity ) {
	if ( empty( $entity['name']['he'] ) || empty( $entity['name']['en'] ) || empty( $entity['summary']['he'] ) || empty( $entity['summary']['en'] ) || empty( $entity['visual']['prompt_en'] ) ) {
		throw new RuntimeException( 'Incomplete bilingual Syrian community entity: ' . $entity['id'] );
	}
	if ( isset( $c99_syrian_community_prompts[ $entity['visual']['prompt_en'] ] ) ) {
		throw new RuntimeException( 'Duplicate Syrian community visual prompt: ' . $entity['id'] );
	}
	$c99_syrian_community_prompts[ $entity['visual']['prompt_en'] ] = true;
	foreach ( $entity['facts'] as $fact ) {
		if ( empty( $fact['source_ids'] ) ) {
			throw new RuntimeException( 'Unbound fact in Syrian community entity: ' . $entity['id'] );
		}
		foreach ( $fact['source_ids'] as $source_id ) {
			if ( ! isset( $c99_syrian_community_known_sources[ $source_id ] ) ) {
				throw new RuntimeException( 'Unknown Syrian community fact source: ' . $source_id );
			}
		}
	}
	foreach ( $entity['relations'] as $relation ) {
		foreach ( $relation['source_ids'] as $source_id ) {
			if ( ! isset( $c99_syrian_community_known_sources[ $source_id ] ) ) {
				throw new RuntimeException( 'Unknown Syrian community relation source: ' . $source_id );
			}
		}
	}
}

return array(
	'schema' => 'complete99-syrian-community-institutions-expansion/v1',
	'version' => 'culinary-science-2026.08.08.v20',
	'sources' => $c99_syrian_community_sources,
	'entities' => $c99_syrian_community_entities,
	'private_entity_ids' => $c99_syrian_community_ids,
	'counts' => array(
		'by_type' => $c99_syrian_community_counts,
		'total_entities' => count( $c99_syrian_community_entities ),
	),
);
