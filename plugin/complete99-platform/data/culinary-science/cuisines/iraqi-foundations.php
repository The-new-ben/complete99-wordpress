<?php
/**
 * Complete99 Iraqi regional cuisine foundation.
 *
 * This module contains a private, source-bound research root and topic hubs.
 * It creates no public page, supplier, product, offer, stock or import route.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_iraqi_sources = array(
	'undp-iraq-united-through-food' => array(
		'type' => 'official_organization', 'publisher' => 'United Nations Development Programme Iraq',
		'title' => 'United Through Food',
		'url' => 'https://www.undp.org/iraq/publications/united-through-food', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'unesco-ahwar-2016' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO World Heritage Centre',
		'title' => 'The Ahwar of Southern Iraq',
		'url' => 'https://whc.unesco.org/en/list/1481/', 'published_at' => '2016-07-17', 'retrieved_at' => '2026-08-07',
	),
	'iraq-cbd-marshlands-traditional-knowledge' => array(
		'type' => 'official_government', 'publisher' => 'Republic of Iraq via the Convention on Biological Diversity',
		'title' => 'Types of Traditional Knowledge in the Marshlands in Southern Iraq',
		'url' => 'https://www.cbd.int/doc/c/d67e/d1a7/5efb5bee50b9bb280aa6f307/sbi-02-inf-10-submisson-iraq-en.pdf', 'published_at' => '2018-06-11', 'retrieved_at' => '2026-08-07',
	),
	'uomosul-moslawi-food-heritage' => array(
		'type' => 'official_organization', 'publisher' => 'University of Mosul, Mosul Studies Center',
		'title' => 'Cooking in Moslawi Folk Heritage',
		'url' => 'https://uomosul.edu.iq/mosulstudiescenter/%D9%85%D8%AD%D8%A7%D8%B6%D8%B1%D8%A9-%D8%A7%D9%81%D8%AA%D8%B1%D8%A7%D8%B6%D9%8A%D8%A9/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'mosul-heritage-intangible-food' => array(
		'type' => 'official_organization', 'publisher' => 'Mosul Heritage',
		'title' => 'Intangible Heritage: Food',
		'url' => 'https://mosul-heritage.com/intangible-food', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'krg-official-cuisine' => array(
		'type' => 'official_government', 'publisher' => 'Kurdistan Regional Government',
		'title' => 'Kurdish Cuisine',
		'url' => 'https://austria.gov.krd/en/kurdische-kuche-2/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'krg-welcome-guide-food-newroz' => array(
		'type' => 'official_government', 'publisher' => 'Kurdistan Regional Government Board of Investment',
		'title' => 'Welcome to the Kurdistan Region of Iraq',
		'url' => 'https://cdn.gov.krd/OtherEntities/Board%20of%20Investment/English/Publication/Downloads/8.%20Welcome%20to%20KRI.pdf', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'iraq-wild-food-plants-2019' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Journal of Ethnobiology and Ethnomedicine',
		'title' => 'Traditional uses of wild food plants in southern Iraqi Kurdistan',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC6882212/', 'published_at' => '2019-11-28', 'retrieved_at' => '2026-08-07',
	),
	'iraq-acorn-bread-2022' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Foods',
		'title' => 'Traditional Acorn Breads in Iraqi Kurdistan',
		'url' => 'https://www.mdpi.com/2304-8158/11/23/3898', 'published_at' => '2022-12-02', 'retrieved_at' => '2026-08-07',
	),
	'fao-iraq-date-palm-ocop' => array(
		'type' => 'official_organization', 'publisher' => 'Food and Agriculture Organization of the United Nations',
		'title' => 'Iraq: Date Palm',
		'url' => 'https://www.fao.org/one-country-one-priority-product/near-east-and-north-africa/iraq/en', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'unesco-date-palm-2022' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO Intangible Cultural Heritage',
		'title' => 'Date palm, knowledge, skills, traditions and practices',
		'url' => 'https://ich.unesco.org/en/RL/date-palm-knowledge-skills-traditions-and-practices-01902', 'published_at' => '2022-11-30', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-fermented-foods-2022' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Journal of Ethnic Foods',
		'title' => 'Traditional fermented foods and beverages in Iraq and their potential for large-scale commercialization',
		'url' => 'https://link.springer.com/article/10.1186/s42779-022-00133-8', 'published_at' => '2022-06-13', 'retrieved_at' => '2026-08-07',
	),
	'unesco-arbaeen-hospitality-2019' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO Intangible Cultural Heritage',
		'title' => 'Provision of services and hospitality during the Arbaeen visitation',
		'url' => 'https://ich.unesco.org/en/RL/provision-of-services-and-hospitality-during-the-arba-in-visitation-01474', 'published_at' => '2019-12-11', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-rice-bacillus-2026' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'FAO AGRIS',
		'title' => 'Bacillus cereus context in cooked rice from Iraqi restaurants',
		'url' => 'https://agris.fao.org/search/en/providers/122436/records/6970c547bd789e57df2d27ad', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-grilling-pah-2025' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Polycyclic aromatic hydrocarbon context for grilled foods in the Kurdistan Region of Iraq',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC12394643/', 'published_at' => '2025-01-01', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-zhazhi-dairy-2023' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed',
		'title' => 'Microbial and physicochemical context of traditional Zhazhi dairy',
		'url' => 'https://pubmed.ncbi.nlm.nih.gov/37807331/', 'published_at' => '2023-01-01', 'retrieved_at' => '2026-08-07',
	),
	'iraqi-basra-raw-milk-safety-2024' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Microbiological safety context for raw milk in Basra',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC11052614/', 'published_at' => '2024-01-01', 'retrieved_at' => '2026-08-07',
	),
	'iraq-handbook-peoples-heritage-2024' => array(
		'type' => 'official_organization', 'publisher' => 'Institute of Development Studies',
		'title' => 'The Handbook of Iraqi People\'s Heritage',
		'url' => 'https://opendocs.ids.ac.uk/articles/book/The_Handbook_of_Iraqi_People_s_Heritage/28303469', 'published_at' => '2024-01-01', 'retrieved_at' => '2026-08-07',
	),
	'govil-iraq-trade-2026' => array(
		'type' => 'official_government', 'publisher' => 'Israel Ministry of Economy and Industry',
		'title' => 'Director-General Instruction 2.4, imports from countries without diplomatic relations or subject to import restrictions',
		'url' => 'https://www.gov.il/BlobFolder/policy/economy_dgi_instructions_02_04/he/instructions_2-04_080326_2-4-08-03-26.pdf', 'published_at' => '2026-03-08', 'retrieved_at' => '2026-08-07',
	),
);

$c99_iraqi_negative_prompt = 'No embedded text, no logos, no certification seals, no medical symbols, no national flags, no invented packaging, no raw serving suggestion, no unsafe direct flame contact, no stereotypical costumes.';

$c99_iraqi_fact = static function ( $id, $dimension, $he, $en, $evidence_class, $value_scope, $source_ids, $observed_at = '', $measurement = array() ) use ( $c99_fact ) {
	return $c99_fact( $id, $dimension, $he, $en, $evidence_class, $value_scope, $source_ids, false, $measurement, $observed_at );
};

$c99_iraqi_profiles = static function ( $facts ) use ( $c99_profile, $c99_profiles ) {
	$by_dimension = array();
	foreach ( $facts as $fact ) {
		$by_dimension[ $fact['dimension'] ][] = $fact['id'];
	}
	$labels = array(
		'scientific' => array( 'הטענה המדעית תחומה למקור ולהיקף הרשומים.', 'The scientific claim is bounded to its listed source and scope.' ),
		'cultural' => array( 'ההקשר התרבותי נשמר לפי האזור, הקהילה או העדות המתועדים.', 'The cultural context is retained according to the documented region, community or testimony.' ),
		'institutional' => array( 'ההקשר המוסדי נשמר לפי מקור רשמי מזוהה.', 'The institutional context is retained according to an identified official source.' ),
		'economic' => array( 'הרשומה אינה כוללת הצעת מחיר, מלאי או מסלול רכישה.', 'The record contains no price offer, stock or purchasing route.' ),
		'structural' => array( 'המבנה האזורי והנושאי הוא מסגרת עריכה תחומה, לא טענת מקור בלעדי.', 'The regional and thematic structure is a bounded editorial framework, not an exclusive-origin claim.' ),
	);
	$overrides = array();
	foreach ( $labels as $dimension => $label ) {
		if ( isset( $by_dimension[ $dimension ] ) ) {
			$overrides[ $dimension ] = $c99_profile( 'source_backed', $label[0], $label[1], $by_dimension[ $dimension ] );
		} elseif ( 'economic' === $dimension ) {
			$overrides[ $dimension ] = $c99_profile( 'pending_evidence', $label[0], $label[1] );
		}
	}
	return $c99_profiles( $overrides );
};

$c99_iraqi_entity = static function ( $config ) use ( $c99_entity, $c99_iraqi_profiles, $c99_iraqi_negative_prompt ) {
	$type = $config['type'];
	$region = isset( $config['region'] ) ? $config['region'] : 'iraq-national';
	$community = isset( $config['community'] ) ? $config['community'] : 'iraqi-multi-community';
	$normalize_term = static function ( $value, $fallback ) {
		$value = strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', (string) $value ), '-' ) );
		return '' !== $value ? $value : $fallback;
	};
	$region = $normalize_term( $region, 'iraq-national' );
	$community = $normalize_term( $community, 'iraqi-multi-community' );
	$attributes = array(
		'pa_region' => array( $region ),
		'pa_community' => array( $community ),
	);
	if ( isset( $config['attributes'] ) ) {
		$attributes = array_replace( $attributes, $config['attributes'] );
	}
	$tags = isset( $config['tags'] ) ? $config['tags'] : array( 'iraqi-cuisine', $region, $community );
	$tags = array_values( array_unique( array_filter( array_map(
		static function ( $tag ) use ( $normalize_term ) {
			return $normalize_term( $tag, '' );
		},
		$tags
	) ) ) );
	$entity = $c99_entity( array(
		'id' => $config['id'],
		'type' => $type,
		'slug' => $config['slug'],
		'parent_id' => isset( $config['parent_id'] ) ? $config['parent_id'] : '',
		'name' => $config['name'],
		'summary' => $config['summary'],
		'surface_class' => 'editorial_draft',
		'index_policy' => 'noindex_private',
		'publication_state' => 'private_preview',
		'public_api' => false,
		'public_page' => false,
		'search_index' => false,
		'review_status' => 'research_draft',
		'next_review_at' => '2027-02-07',
		'culinary_test_status' => in_array( $type, array( 'dish', 'preparation' ), true ) ? 'pending' : 'not_applicable',
		'schema_type' => isset( $config['schema_type'] ) ? $config['schema_type'] : 'Article',
		'page_role' => isset( $config['page_role'] ) ? $config['page_role'] : 'hub',
		'surface_group' => 'iraqi-culinary-science',
		'seo_group' => 'iraqi-culinary-science',
		'primary_intent' => $config['primary_intent'],
		'primary_keyword' => $config['primary_keyword'],
		'secondary_keywords' => isset( $config['secondary_keywords'] ) ? $config['secondary_keywords'] : array( 'he' => array(), 'en' => array() ),
		'intent_classes' => isset( $config['intent_classes'] ) ? $config['intent_classes'] : array( 'informational' ),
		'facts' => $config['facts'],
		'profiles' => $c99_iraqi_profiles( $config['facts'] ),
		'categories' => isset( $config['categories'] ) ? $config['categories'] : array( 'culinary-museum', 'iraqi-culinary-science', $type . 's' ),
		'attributes' => $attributes,
		'tags' => $tags,
		'public_category_path' => array(),
		'public_attribute_keys' => array(),
		'public_tags' => array(),
		'relations' => isset( $config['relations'] ) ? $config['relations'] : array(),
		'commerce_state' => 'reference_only',
		'woo_product_code' => '',
		'public_offer_allowed' => false,
		'cross_sell_ids' => array(),
		'up_sell_ids' => array(),
		'revenue_models' => array( 'content_to_commerce' ),
		'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'research_readers' ),
		'pricing_state' => isset( $config['pricing_state'] ) ? $config['pricing_state'] : 'research_required',
		'market_scope' => isset( $config['market_scope'] ) ? $config['market_scope'] : 'global_research',
		'observation_entity_ids' => array(),
		'prompt_en' => $config['prompt_en'],
		'negative_prompt_en' => $c99_iraqi_negative_prompt,
		'asset_state' => 'rights_review_required',
		'rights_method' => 'generated_concept_with_human_review',
		'rights_state' => 'pending',
		'compliance' => isset( $config['compliance'] ) ? $config['compliance'] : array(),
		'attribution_state' => 'pending_named_review',
		'protected_exclusions' => array(
			'he' => array( 'טענת מקור בלעדית', 'הצעת ספק מעיראק', 'טענה רפואית', 'הוראות בטיחות שלא אומתו' ),
			'en' => array( 'exclusive origin claim', 'Iraq-origin supplier offer', 'medical promise', 'unverified safety instructions' ),
		),
	) );
	$entity['seo']['route_mode'] = 'private';
	$entity['trust']['substantive_updated_at'] = '2026-08-07';
	$entity['review']['reviewed_at'] = '2026-08-07';
	foreach ( $entity['relations'] as &$relation ) {
		$relation['valid_from'] = '2026-08-07';
	}
	unset( $relation );
	return $entity;
};

$c99_iraqi_build = static function ( $spec ) use ( $c99_iraqi_entity, $c99_iraqi_fact, $c99_relation, $c99_compliance, $c99_text ) {
	$sources = $spec['sources'];
	$facts = array(
		$c99_iraqi_fact(
			'fact-' . $spec['slug'] . '-documented-scope',
			isset( $spec['dimension'] ) ? $spec['dimension'] : 'cultural',
			$spec['fact_he'],
			$spec['fact_en'],
			isset( $spec['evidence'] ) ? $spec['evidence'] : 'official_source',
			isset( $spec['value_scope'] ) ? $spec['value_scope'] : 'entity',
			$sources,
			isset( $spec['observed_at'] ) ? $spec['observed_at'] : '',
			isset( $spec['measurement'] ) ? $spec['measurement'] : array()
		),
	);
	if ( in_array( $spec['type'], array( 'dish', 'preparation', 'ingredient', 'technique' ), true ) ) {
		$facts[] = $c99_iraqi_fact(
			'fact-' . $spec['slug'] . '-science-boundary',
			'scientific',
			isset( $spec['science_he'] ) ? $spec['science_he'] : 'המקור מזהה מנה, רכיב או שיטת הכנה, אך אינו מספק מדידות מאומתות של pH, בריקס, פעילות מים, עקומת טמפרטורה או חיי מדף לרשומה זו.',
			isset( $spec['science_en'] ) ? $spec['science_en'] : 'The source identifies a dish, component or method but supplies no validated pH, Brix, water activity, temperature curve or shelf life for this record.',
			isset( $spec['science_evidence'] ) ? $spec['science_evidence'] : 'official_source',
			isset( $spec['science_scope'] ) ? $spec['science_scope'] : 'entity',
			isset( $spec['science_sources'] ) ? $spec['science_sources'] : $sources
		);
	}
	$relations = array();
	if ( ! empty( $spec['parent_id'] ) ) {
		$relations[] = $c99_relation( 'part_of', $spec['parent_id'], 'הישות נשמרת תחת ההקשר האזורי, הנושאי או הקהילתי המתועד שלה.', 'The entity remains under its documented regional, thematic or community context.', false, $sources, 'official_source' );
	}
	foreach ( isset( $spec['requires'] ) ? $spec['requires'] : array() as $target_id ) {
		$relations[] = $c99_relation( 'requires', $target_id, 'הקשר הרכיב מתועד, אך זהות המוצר, הכמות והטיפול דורשים אימות לפני מתכון או הצעה.', 'The component relationship is documented, while product identity, quantity and handling require verification before a recipe or offer.', false, $sources, 'official_source' );
	}
	foreach ( isset( $spec['references'] ) ? $spec['references'] : array() as $target_id ) {
		$relations[] = $c99_relation( 'references', $target_id, 'הקישור שומר חפיפה או השוואה בלי למזג זהויות ובלי לקבוע מקור בלעדי.', 'The link preserves an overlap or comparison without merging identities or declaring exclusive origin.', false, $sources, 'official_source' );
	}
	foreach ( isset( $spec['extra_relations'] ) ? $spec['extra_relations'] : array() as $extra_relation ) {
		$relations[] = $c99_relation( $extra_relation[0], $extra_relation[1], $extra_relation[2], $extra_relation[3], false, isset( $extra_relation[4] ) ? $extra_relation[4] : $sources, isset( $extra_relation[5] ) ? $extra_relation[5] : 'official_source' );
	}
	if ( 'compliance-iraq-trade-israel-2026' !== $spec['id'] ) {
		$relations[] = $c99_relation(
			'references',
			'compliance-iraq-trade-israel-2026',
			'הישות כפופה לגבול הסחר המתועד ואינה יוצרת ספק, הצעה, מלאי, תשלום או מסלול רכישה.',
			'The entity is governed by the documented trade boundary and creates no supplier, offer, stock, payment or purchasing route.',
			false,
			array( 'govil-iraq-trade-2026' ),
			'regulatory_standard'
		);
	}
	$compliance = array();
	foreach ( isset( $spec['compliance'] ) ? $spec['compliance'] : array() as $note ) {
		$compliance[] = $c99_compliance( $note[0], $note[1], $note[2], isset( $note[3] ) ? $note[3] : $sources, false );
	}
	return $c99_iraqi_entity( array(
		'id' => $spec['id'], 'type' => $spec['type'], 'slug' => $spec['slug'], 'parent_id' => isset( $spec['parent_id'] ) ? $spec['parent_id'] : '',
		'name' => $c99_text( $spec['name_he'], $spec['name_en'] ),
		'summary' => $c99_text( $spec['summary_he'], $spec['summary_en'] ),
		'region' => isset( $spec['region'] ) ? $spec['region'] : 'iraq-national',
		'community' => isset( $spec['community'] ) ? $spec['community'] : 'iraqi-multi-community',
		'primary_intent' => $c99_text( isset( $spec['intent_he'] ) ? $spec['intent_he'] : 'להבין את הזהות, ההקשר והגבולות של ' . $spec['name_he'] . '.', isset( $spec['intent_en'] ) ? $spec['intent_en'] : 'Understand the identity, context and boundaries of ' . $spec['name_en'] . '.' ),
		'primary_keyword' => $c99_text( isset( $spec['keyword_he'] ) ? $spec['keyword_he'] : $spec['name_he'], isset( $spec['keyword_en'] ) ? $spec['keyword_en'] : $spec['name_en'] ),
		'secondary_keywords' => isset( $spec['secondary_keywords'] ) ? $spec['secondary_keywords'] : array( 'he' => array(), 'en' => array() ),
		'schema_type' => isset( $spec['schema_type'] ) ? $spec['schema_type'] : 'Article',
		'page_role' => isset( $spec['page_role'] ) ? $spec['page_role'] : 'hub',
		'facts' => $facts,
		'relations' => $relations,
		'compliance' => $compliance,
		'attributes' => isset( $spec['attributes'] ) ? $spec['attributes'] : array(),
		'tags' => isset( $spec['tags'] ) ? $spec['tags'] : array( 'iraqi-cuisine', isset( $spec['region'] ) ? $spec['region'] : 'iraq-national' ),
		'pricing_state' => isset( $spec['pricing_state'] ) ? $spec['pricing_state'] : 'research_required',
		'market_scope' => isset( $spec['market_scope'] ) ? $spec['market_scope'] : 'global_research',
		'prompt_en' => $spec['prompt_en'],
	) );
};

$c99_iraqi_rows = array();

$c99_iraqi_rows[] = array(
	'id' => 'cuisine-iraqi-regional', 'type' => 'cuisine', 'slug' => 'iraqi-culinary-science', 'parent_id' => 'museum-culinary-science',
	'name_he' => 'המטבח העיראקי לפי אזורים וקהילות', 'name_en' => 'Iraqi Cuisine by Region and Community',
	'region' => 'iraq-national', 'sources' => array( 'undp-iraq-united-through-food', 'iraq-handbook-peoples-heritage-2024', 'unesco-ahwar-2016' ),
	'summary_he' => 'מפת מחקר דו-לשונית של אזורים, מנות, חומרי גלם, טכניקות, מסורות וקהילות בעיראק. היא שומרת הבדלים מקומיים בלי להפוך מאכל משותף לטענת מקור בלעדית.',
	'summary_en' => 'A bilingual research map of Iraqi regions, dishes, ingredients, techniques, traditions and communities. It preserves local distinctions without turning a shared food into an exclusive-origin claim.',
	'fact_he' => 'מקורות לאומיים, אזוריים וקהילתיים מתעדים שונות רחבה בין בגדאד, מוסול, בצרה, הביצות, הפרת התיכון, כורדיסטן ואזורים רב-קהילתיים.',
	'fact_en' => 'National, regional and community sources document substantial variation across Baghdad, Mosul, Basra, the marshes, the Middle Euphrates, Kurdistan and multi-community regions.',
	'dimension' => 'structural', 'evidence' => 'official_source', 'schema_type' => 'CollectionPage', 'page_role' => 'hub',
	'prompt_en' => 'Documentary overhead culinary atlas of Iraq arranged as distinct unlabeled regional tables with cooked river fish, rice, bulgur, dates, breads, pickles and dairy, warm natural daylight, restrained museum styling.'
);

$c99_iraqi_hubs = array(
	array( 'region-iraq-baghdad', 'baghdad-foodways', 'בגדאד ותרבות האוכל העירונית', 'Baghdad Foodways', 'iraq-baghdad', array( 'undp-iraq-united-through-food', 'iraq-handbook-peoples-heritage-2024' ), 'בגדאד מתועדת כמרחב עירוני רב-קהילתי המחבר אורז, תבשילים, לחמים, מסגוף, שווקים ומסורות ביתיות.', 'Baghdad is documented as a multi-community urban setting connecting rice, stews, breads, masgouf, markets and household traditions.', 'השיוך לבגדאד מתאר הקשר עירוני מתועד ואינו מקנה לעיר בעלות בלעדית על כל מנה הנאכלת בה.', 'Assignment to Baghdad describes a documented urban context and does not give the city exclusive ownership of every dish eaten there.', 'Documentary Baghdad culinary table beside an abstract river edge with cooked fish, rice, stew bowls, bread and dates, no skyline, map, flag or signage.' ),
	array( 'region-iraq-mosul-ninewa', 'mosul-ninewa-foodways', 'מוסול ונינווה', 'Mosul and Ninewa Foodways', 'iraq-mosul-ninewa', array( 'uomosul-moslawi-food-heritage', 'mosul-heritage-intangible-food' ), 'מוסול ונינווה מתועדות דרך קובה מבורגול, דולמה, בורמה, קלייצ\'ה, שימור עונתי ומסורות קהילתיות מגוונות.', 'Mosul and Ninewa are documented through bulgur kubba, dolma, burma, kleicha, seasonal preservation and diverse community traditions.', 'המקורות תומכים בהקשר מוסולי ואזורי, אך אינם מצדיקים תיוג דתי בלעדי או טענת המצאה למנות משותפות.', 'The sources support a Moslawi and regional context but do not justify an exclusive religious label or invention claim for shared dishes.', 'Moslawi research table with a flat cooked bulgur kubba, wheat and cowpea stew, preserved pantry jars and baked pastries, neutral stone background.' ),
	array( 'region-iraq-basra-shatt-al-arab', 'basra-shatt-al-arab-foodways', 'בצרה ושאט אל-ערב', 'Basra and Shatt al-Arab Foodways', 'iraq-basra-shatt-al-arab', array( 'undp-iraq-united-through-food', 'iraq-cbd-marshlands-traditional-knowledge' ), 'בצרה ושאט אל-ערב מחברים מסורות דגים, אורז, תמרים, ליים מיובש, נתיבי סחר ומוצרי חלב דרומיים.', 'Basra and Shatt al-Arab connect fish, rice, dates, dried lime, trade-route and southern dairy traditions.', 'הקשר הנמל מסביר תנועה והשפעה קולינרית, אך אינו מוכיח מקור בלעדי של ביריאני, ליים מיובש או מאכלי דג משותפים.', 'The port context explains culinary movement and influence but does not prove exclusive origin for biryani, dried lime or shared fish dishes.', 'Basra culinary still life with fully cooked fish, amber rice, dried limes, dates and a closed dairy vessel beside an abstract waterway, documentary daylight.' ),
	array( 'region-iraq-middle-euphrates', 'middle-euphrates-najaf-karbala-babil-foodways', 'הפרת התיכון: נג\'ף, כרבלא ובבל', 'Middle Euphrates: Najaf, Karbala and Babil Foodways', 'iraq-middle-euphrates', array( 'undp-iraq-united-through-food', 'unesco-arbaeen-hospitality-2019' ), 'אזור הפרת התיכון כולל מסורות ביתיות ועירוניות לצד מערכי אירוח קהילתיים רחבי היקף בנג\'ף ובכרבלא.', 'The Middle Euphrates includes household and urban traditions alongside large-scale community hospitality in Najaf and Karbala.', 'אונסקו מתעדת את מנהג האירוח והשירות בארבעין, לא מתכון דתי אחיד ולא בעלות של קהילה אחת על מנה מסוימת.', 'UNESCO documents Arbaeen hospitality and service, not one uniform religious recipe or ownership of a dish by one community.', 'Middle Euphrates hospitality table with cooked chickpea and meat stew, rice, bread, water vessels and orderly shared serving stations, no shrine depiction or religious symbols.' ),
	array( 'region-iraq-marshes-south', 'southern-marshes-foodways', 'מסורות המזון של ביצות דרום עיראק', 'Southern Iraqi Marsh Foodways', 'iraq-marshes-south', array( 'unesco-ahwar-2016', 'iraq-cbd-marshlands-traditional-knowledge' ), 'מסורות הביצות קושרות אורז, דגים, חלב תאו, לחמי סאג\' ודיסקת חמר עם סביבת המים, הקנים והפרנסה המקומית.', 'Marsh foodways connect rice, fish, buffalo dairy, saj breads and clay-disc baking with the local water, reed and livelihood environment.', 'מסמך הידע המסורתי משמש לתיעוד זהות ושיטה בלבד. טענות רפואיות עממיות אינן מועברות לתוכן ציבורי.', 'The traditional-knowledge document is used only to document identity and method. Folk medical claims are not transferred into public content.', 'Southern marsh culinary scene with cooked river fish, thick rice bread on a clay disc, thin saj bread, pasteurized buffalo cream and reeds in soft background focus.' ),
	array( 'region-iraq-kurdistan', 'iraqi-kurdistan-foodways', 'כורדיסטן העיראקית', 'Iraqi Kurdistan Foodways', 'iraq-kurdistan', array( 'krg-official-cuisine', 'krg-welcome-guide-food-newroz' ), 'כורדיסטן העיראקית מתועדת דרך אורז, יאפרח, מרקים, לחמים, מוצרי חלב, אגוזים, פירות וידע בצמחי בר.', 'Iraqi Kurdistan is documented through rice, yaprakh, soups, breads, dairy, nuts, fruits and wild-plant knowledge.', 'הישות עוסקת בביטויים מכורדיסטן העיראקית בלבד ואינה מאחדת את כל המטבחים הכורדיים בעיראק, איראן, טורקיה וסוריה.', 'The entity covers Iraqi Kurdistan expressions only and does not merge all Kurdish cuisines across Iraq, Iran, Turkey and Syria.', 'Iraqi Kurdistan foodways table with cooked yaprakh, lentil soup, flatbread, yogurt, walnuts, pomegranate and safely identified herbs, mountain light without costumes or flags.' ),
	array( 'region-iraq-kirkuk-diyala', 'kirkuk-diyala-multi-community-foodways', 'כרכוכ ודיאלא: מסורות רב-קהילתיות', 'Kirkuk and Diyala Multi-Community Foodways', 'iraq-kirkuk-diyala', array( 'undp-iraq-united-through-food', 'iraq-handbook-peoples-heritage-2024' ), 'כרכוכ ודיאלא נשמרות כמרחב מחקר רב-קהילתי שבו יש לתעד בנפרד הקשרים ערביים, כורדיים, טורקמניים, אשוריים, כלדיים ואחרים.', 'Kirkuk and Diyala remain a multi-community research space where Arab, Kurdish, Turkmen, Assyrian, Chaldean and other contexts must be documented separately.', 'נוכחות של מנה באזור אינה מספיקה כדי להקצות לה זהות אתנית, דתית או לאומית בלעדית.', 'The presence of a dish in the region is insufficient to assign it an exclusive ethnic, religious or national identity.', 'Multi-community northern Iraqi research table with separate cooked rice, bread, stuffed vegetables, dairy and tea settings, visually distinct zones without ethnic costumes or symbols.' ),
	array( 'hub-iraqi-kubba-family', 'iraqi-kubba-family', 'משפחת הקובה העיראקית', 'Iraqi Kubba Family', 'iraq-national', array( 'undp-iraq-united-through-food', 'mosul-heritage-intangible-food', 'iraq-handbook-peoples-heritage-2024' ), 'משפחת הקובה העיראקית כוללת מעטפות מבורגול, אורז, תפוחי אדמה וסולת, לצד צורות, מרקים והקשרים אזוריים וקהילתיים שונים.', 'The Iraqi kubba family includes bulgur, rice, potato and semolina shells alongside different forms, soups, regions and community contexts.', 'קובה עיראקית אינה מתמזגת אוטומטית עם קיבֶּה סורית או לבנונית. הקשר ביניהן הוא משפחת מאכלים משותפת.', 'Iraqi kubba is not automatically merged with Syrian or Lebanese kibbeh. Their relationship is a shared food family.', 'Comparative studio arrangement of four fully cooked Iraqi kubba shell styles in clean cross-section, bulgur, rice, potato and semolina kept visibly separate.' ),
	array( 'hub-iraqi-rice-stews', 'iraqi-rice-stews-and-crust', 'אורז, תבשילים ותחתית פריכה בעיראק', 'Iraqi Rice, Stews and Crust', 'iraq-national', array( 'undp-iraq-united-through-food' ), 'ה-Hub מחבר זני אורז, תבשילי מרק, ביריאני, תימן, בגילה ותחתית פריכה תוך הפרדה בין זהות קולינרית לבטיחות החזקה וקירור.', 'This hub connects rice varieties, stews, biryani, timman, bagilla and crisp crust while separating culinary identity from hot-holding and cooling safety.', 'מקור המזון הלאומי מתעד מגוון מנות אורז ותבשילים, אך אינו מחליף אימות נפרד של זן, תהליך, החזקה חמה, קירור וחימום חוזר.', 'The national food source documents varied rice dishes and stews but does not replace separate validation of cultivar, process, hot holding, cooling and reheating.', 'Scientific culinary studio table with separate cooked Iraqi rice grains, crisp pot crust, stew bowl and covered hot-holding vessel, no unsafe room-temperature storage.' ),
	array( 'hub-iraqi-fish-fire', 'iraqi-fish-fire-and-river-foodways', 'דגים, אש ונהרות במטבח העיראקי', 'Iraqi Fish, Fire and River Foodways', 'iraq-national', array( 'iraq-cbd-marshlands-traditional-knowledge', 'unesco-ahwar-2016' ), 'ה-Hub מחבר מסגוף, דגי נהר, דגים מיובשים, שיטות אש והקשרים מבגדאד, בצרה והביצות.', 'This hub connects masgouf, river fish, dried fish, fire techniques and contexts from Baghdad, Basra and the marshes.', 'המקורות מתעדים דגים, מסגוף ושיטות אש בביצות, אך אינם מספקים לבדם מפרט מאומת של מין, שרשרת קירור, טמפרטורת ליבה, טפילים או חשיפה לעשן.', 'The sources document fish, masgouf and fire methods in the marshes but do not by themselves supply validated species, cold-chain, core-temperature, parasite or smoke-exposure specifications.', 'Side-view culinary science scene of a fully cooked split river fish held at a controlled distance from a clean fire, visible thermometer and ventilation, no direct flame contact.' ),
	array( 'hub-iraqi-date-palm', 'iraqi-date-palm-and-dibs', 'התמר, הדקל והדיבס בעיראק', 'Iraqi Date Palm and Dibs', 'iraq-national', array( 'fao-iraq-date-palm-ocop', 'unesco-date-palm-2022' ), 'ה-Hub מתעד זני תמר, שלבי הבשלה, דיבס, שימושים קולינריים וידע תרבותי הקשור לדקל בעיראק.', 'This hub documents date cultivars, ripening stages, dibs, culinary uses and cultural knowledge connected with the date palm in Iraq.', 'טענה על בריקס, פעילות מים, פוליפנולים או השפעה בריאותית מחייבת מדידה או מקור מתאים למוצר ולזן המסוימים.', 'Any Brix, water-activity, polyphenol or health-effect claim requires measurement or evidence appropriate to the specific product and cultivar.', 'Museum-style date palm ingredient study with distinct ripe date samples, a small bowl of dark dibs and neutral measuring glassware, no medicinal badges or packaging.' ),
	array( 'hub-iraqi-fermentation-preservation', 'iraqi-fermentation-pickles-and-preservation', 'התססה, חמוצים ושימור בעיראק', 'Iraqi Fermentation, Pickles and Preservation', 'iraq-national', array( 'iraqi-fermented-foods-2022', 'iraqi-zhazhi-dairy-2023', 'iraqi-basra-raw-milk-safety-2024' ), 'ה-Hub כולל טורשי, לחם חמוץ, מוצרי חלב, בשר משומר ומשקאות מסורתיים, תוך הפרדה בין זהות מסורתית לתהליך בטוח ומאומת.', 'This hub includes turshi, sour bread, dairy, preserved meat and traditional beverages while separating heritage identity from a validated safe process.', 'סקירת התסיסות ממפה מסורות ופערי מחקר. היא אינה קובעת לבדה pH יעד, חיי מדף, בטיחות או יתרון פרוביוטי למוצר מסוים.', 'The fermentation review maps traditions and research gaps. It does not by itself establish target pH, shelf life, safety or a probiotic benefit for a specific product.', 'Controlled fermentation research bench with sealed pickle and dairy vessels, visible blank pH meter and temperature probe, clean labels without readable text.' ),
	array( 'hub-iraqi-bread-bakery', 'iraqi-bread-bakery-and-sweets', 'לחמים, מאפים ומתוקים בעיראק', 'Iraqi Bread, Bakery and Sweets', 'iraq-national', array( 'undp-iraq-united-through-food', 'iraq-cbd-marshlands-traditional-knowledge', 'iraq-handbook-peoples-heritage-2024' ), 'ה-Hub מחבר סמון, לחמי תנור וסאג\', לחמי אורז, קלייצ\'ה, קאהי ומתוקים אזוריים וקהילתיים.', 'This hub connects samoon, tannour and saj breads, rice breads, kleicha, kahi and regional or community sweets.', 'לחם תנור, סאג\', קלייצ\'ה ומאפים ממולאים שייכים למשפחות רחבות. יש לתעד כל וריאנט בלי לטעון להמשכיות עתיקה או מקור בלעדי ללא ראיות.', 'Tannour bread, saj bread, kleicha and filled pastries belong to broad families. Each variant must be documented without unsupported ancient-continuity or exclusive-origin claims.', 'Iraqi bakery research table with samoon, tannour flatbread, thin saj bread, rice bread and filled date cookies, evenly baked and clearly separated.' ),
	array( 'hub-iraqi-community-foodways', 'iraqi-community-foodways', 'מסורות המזון של קהילות עיראק', 'Iraqi Community Foodways', 'iraq-national', array( 'iraq-handbook-peoples-heritage-2024', 'undp-iraq-united-through-food' ), 'ה-Hub שומר שכבות נפרדות למסורות יהודיות-בבליות, אשוריות, כלדיות, מנדעיות, יזידיות, טורקמניות, כורדיות, ערביות ואחרות.', 'This hub preserves separate layers for Babylonian Jewish, Assyrian, Chaldean, Mandaean, Yazidi, Turkmen, Kurdish, Arab and other traditions.', 'עדות משפחתית או קהילתית מתועדת נשמרת בהיקפה ואינה הופכת לבעלות של קהילה אחת על מאכל משותף.', 'A documented family or community testimony remains within its scope and does not become ownership of a shared food by one community.', 'Respectful archival food table with separate home-cooked dishes, breads and serving vessels representing multiple Iraqi community testimonies, no people, costumes or religious props.' ),
	array( 'hub-iraqi-institutions-markets', 'iraqi-culinary-institutions-markets', 'מוסדות, ארכיונים ושווקים קולינריים בעיראק', 'Iraqi Culinary Institutions, Archives and Markets', 'iraq-national', array( 'undp-iraq-united-through-food', 'uomosul-moslawi-food-heritage', 'iraq-handbook-peoples-heritage-2024' ), 'ה-Hub מרכז מוסדות מחקר, ארכיונים, שווקים ואתרי תיעוד שיכולים לתמוך בהבנת מורשת המזון העיראקית.', 'This hub gathers research institutions, archives, markets and documentation sites that can support understanding of Iraqi food heritage.', 'אזכור מוסד או שוק הוא הפניה עריכתית בלבד. הוא אינו יוצר שותפות, המלצה, ספק, מחיר, מלאי או הרשאת שימוש בתמונה.', 'Naming an institution or market is an editorial reference only. It creates no partnership, endorsement, supplier, price, stock or image-use permission.', 'Neutral culinary research desk with archive folders, market produce samples, notebooks and museum-safe photography cards, no institution logos or copied documents.' ),
	array( 'hub-iraqi-jewish-foodways', 'iraqi-jewish-foodways', 'מסורות האוכל של יהודי עיראק', 'Iraqi Jewish Foodways', 'iraq-national', array( 'iraq-handbook-peoples-heritage-2024' ), 'ה-Hub מתעד מסורות משפחתיות וקהילתיות של יהודי בבל, בגדאד וכורדיסטן העיראקית בתוך הפסיפס הרחב של מטבחי עיראק.', 'This hub documents Babylonian, Baghdadi and Iraqi Kurdish Jewish family and community traditions within the wider mosaic of Iraqi cuisines.', 'הישות אינה מגדירה את כל המטבח העיראקי כיהודי ואינה נותנת בעלות בלעדית על קובה, קלייצ\'ה, דולמה, פאצ\'ה, עמבה או אורז.', 'The entity does not define all Iraqi cuisine as Jewish and does not grant exclusive ownership of kubba, kleicha, dolma, pacha, amba or rice.', 'Archival-style Iraqi Jewish family food table with fully cooked rice, stuffed vegetables, kubba soup, date pastries and Sabbath serving context, no people, text or religious symbols.' ),
);

foreach ( $c99_iraqi_hubs as $hub ) {
	$hub_uses_peer_reviewed_evidence = 'hub-iraqi-fermentation-preservation' === $hub[0];
	$c99_iraqi_rows[] = array(
		'id' => $hub[0], 'type' => 'topic_hub', 'slug' => $hub[1],
		'parent_id' => 'hub-iraqi-jewish-foodways' === $hub[0] ? 'hub-iraqi-community-foodways' : 'cuisine-iraqi-regional',
		'name_he' => $hub[2], 'name_en' => $hub[3], 'region' => $hub[4], 'sources' => $hub[5],
		'summary_he' => $hub[6], 'summary_en' => $hub[7], 'fact_he' => $hub[8], 'fact_en' => $hub[9],
		'dimension' => $hub_uses_peer_reviewed_evidence ? 'scientific' : 'structural',
		'evidence' => $hub_uses_peer_reviewed_evidence ? 'peer_reviewed_context' : 'official_source',
		'schema_type' => 'CollectionPage', 'page_role' => 'hub',
		'prompt_en' => $hub[10],
	);
}

$c99_iraqi_entities = array();
foreach ( $c99_iraqi_rows as $spec ) {
	$c99_iraqi_entities[] = $c99_iraqi_build( $spec );
}

$c99_iraqi_counts = array_count_values( array_column( $c99_iraqi_entities, 'type' ) );

return array(
	'schema' => 'complete99-iraqi-foundations-module/v1',
	'version' => 'culinary-science-2026.08.07.v18',
	'sources' => $c99_iraqi_sources,
	'entities' => $c99_iraqi_entities,
	'private_entity_ids' => array_column( $c99_iraqi_entities, 'id' ),
	'cluster_root_id' => 'cuisine-iraqi-regional',
	'cluster_id' => 'cluster-iraqi-regional-cuisine',
	'counts' => array(
		'by_type' => $c99_iraqi_counts,
		'total_entities' => count( $c99_iraqi_entities ),
	),
);
