<?php
/**
 * Complete99 Syrian western and central regional research expansion.
 *
 * This module records source-bounded editorial entities only. It creates no
 * public projection, product, price, inventory, supplier or sales offer.
 * Family testimony remains testimony and is never promoted to a citywide rule.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $c99_syrian_entity ) || ! is_callable( $c99_syrian_entity ) || ! isset( $c99_syrian_fact ) || ! is_callable( $c99_syrian_fact ) ) {
	throw new RuntimeException( 'Syrian west expansion requires the Syrian entity and fact builders.' );
}
if ( ! isset( $c99_relation ) || ! is_callable( $c99_relation ) || ! isset( $c99_compliance ) || ! is_callable( $c99_compliance ) || ! isset( $c99_text ) || ! is_callable( $c99_text ) ) {
	throw new RuntimeException( 'Syrian west expansion requires the shared registry builders.' );
}
if ( ! isset( $c99_syrian_sources ) || ! is_array( $c99_syrian_sources ) || ! isset( $c99_syrian_depth_sources ) || ! is_array( $c99_syrian_depth_sources ) ) {
	throw new RuntimeException( 'Syrian west expansion requires both existing Syrian source registries.' );
}

$c99_syrian_west_sources = array(
	'ifrepo-idlib-harem-foodways' => array(
		'type' => 'official_organization',
		'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Idlib and Harem recipes and cultures',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_7.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'ifrepo-qadmus-foodways' => array(
		'type' => 'official_organization',
		'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Qadmus recipes and cultures',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_5.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'ifrepo-kassab-armenian-foodways' => array(
		'type' => 'official_organization',
		'publisher' => 'Institut francais du Proche-Orient, Syria Recipes and Cultures',
		'title' => 'Kassab Armenian recipes and cultures',
		'url' => 'https://create.ifrepo.world/static/ifcollectors/pdf/chapter_9.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'arum-eurasia-review-2025' => array(
		'type' => 'peer_reviewed_paper',
		'publisher' => 'Plants, PubMed Central',
		'title' => 'Arum species in Eurasian ethnobotany and food use review',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC11859539/',
		'published_at' => '2025-01-01',
		'retrieved_at' => '2026-08-07',
	),
	'pubmed-arum-palaestinum-poisoning-2020' => array(
		'type' => 'peer_reviewed_paper',
		'publisher' => 'Clinical Toxicology, PubMed',
		'title' => 'Arum palaestinum poisoning case series',
		'url' => 'https://pubmed.ncbi.nlm.nih.gov/32296984/',
		'published_at' => '2020-10-01',
		'retrieved_at' => '2026-08-07',
	),
	'fda-acidified-low-acid-foods' => array(
		'type' => 'regulatory_guidance',
		'publisher' => 'United States Food and Drug Administration',
		'title' => 'Acidified and low-acid canned foods guidance',
		'url' => 'https://www.fda.gov/food/guidance-documents-regulatory-information-topic-food-and-dietary-supplements/acidified-low-acid-canned-foods-guidance-documents-regulatory-information',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'who-household-air-pollution-2025' => array(
		'type' => 'official_organization',
		'publisher' => 'World Health Organization',
		'title' => 'Household air pollution and health',
		'url' => 'https://www.who.int/en/news-room/fact-sheets/detail/household-air-pollution-and-health',
		'published_at' => '2025-12-16',
		'retrieved_at' => '2026-08-07',
	),
	'usda-no-wash-poultry' => array(
		'type' => 'official_government',
		'publisher' => 'United States Department of Agriculture Food Safety and Inspection Service',
		'title' => 'Should I wash chicken or other poultry before cooking',
		'url' => 'https://ask.fsis.usda.gov/article/Should-I-wash-chicken-or-other-poultry-before-cooking',
		'published_at' => '2024-08-23',
		'retrieved_at' => '2026-08-07',
	),
);

foreach ( $c99_syrian_west_sources as $source_id => $source ) {
	if ( isset( $c99_syrian_sources[ $source_id ] ) || isset( $c99_syrian_depth_sources[ $source_id ] ) ) {
		throw new RuntimeException( 'Duplicate Syrian west source ID: ' . $source_id );
	}
}

$c99_syrian_west_known_sources = array_replace( $c99_syrian_sources, $c99_syrian_depth_sources, $c99_syrian_west_sources );

$c99_syrian_west_build = static function ( $spec ) use ( $c99_syrian_entity, $c99_syrian_fact, $c99_relation, $c99_compliance, $c99_text ) {
	$relations = array(
		$c99_relation(
			'part_of',
			$spec['parent_id'],
			'הקשר נשמר תחת האזור או הישות המתועדים בלבד, בלי להרחיב עדות משפחתית לכלל האזור.',
			'The record stays under its documented region or entity without expanding family testimony into a regional rule.',
			false,
			$spec['sources'],
			'official_source'
		),
	);
	foreach ( isset( $spec['references'] ) ? $spec['references'] : array() as $target_id ) {
		$relations[] = $c99_relation(
			'references',
			$target_id,
			'הקישור שומר על זהויות נפרדות ומציג קרבה מחקרית בלבד.',
			'The link preserves separate identities and records research context only.',
			false,
			$spec['sources'],
			'official_source'
		);
	}

	$compliance = array(
		$c99_compliance(
			isset( $spec['compliance_code'] ) ? $spec['compliance_code'] : 'private-source-bounded-reference',
			$spec['risk_he'],
			$spec['risk_en'],
			$spec['risk_sources'],
			false
		),
	);
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

	$entity = $c99_syrian_entity( array(
		'id' => $spec['id'],
		'type' => $spec['type'],
		'slug' => $spec['slug'],
		'parent_id' => $spec['parent_id'],
		'name' => $c99_text( $spec['name_he'], $spec['name_en'] ),
		'summary' => $c99_text( $spec['summary_he'], $spec['summary_en'] ),
		'region' => $spec['region'],
		'community' => isset( $spec['community'] ) ? $spec['community'] : 'syrian-multi-community',
		'primary_intent' => $c99_text( 'להבין את ' . $spec['name_he'] . ' בתוך ההקשר האזורי והמקור המתועדים.', 'Understand ' . $spec['name_en'] . ' within its documented regional and source context.' ),
		'primary_keyword' => $c99_text( $spec['name_he'] . ' במטבח הסורי', $spec['name_en'] . ' in Syrian cuisine' ),
		'schema_type' => 'topic_hub' === $spec['type'] ? 'CollectionPage' : 'Article',
		'facts' => array(
			$c99_syrian_fact(
				'fact-' . $spec['slug'] . '-documented',
				isset( $spec['dimension'] ) ? $spec['dimension'] : 'cultural',
				$spec['fact_he'],
				$spec['fact_en'],
				'official_source',
				'entity',
				$spec['sources']
			),
			$c99_syrian_fact(
				'fact-' . $spec['slug'] . '-science-risk-boundary',
				'scientific',
				$spec['science_he'],
				$spec['science_en'],
				isset( $spec['science_evidence'] ) ? $spec['science_evidence'] : 'official_source',
				'entity',
				$spec['science_sources']
			),
		),
		'relations' => $relations,
		'categories' => array( 'culinary-museum', 'syrian-regional-research', $spec['type'] . 's' ),
		'attributes' => array(
			'pa_region' => array( $spec['region'] ),
			'pa_community' => array( isset( $spec['community'] ) ? $spec['community'] : 'syrian-multi-community' ),
		),
		'tags' => array( 'syrian-regional-research', $spec['region'], 'private-reference-only' ),
		'cross_sell_ids' => array(),
		'prompt_en' => $spec['prompt_en'],
		'compliance' => $compliance,
	) );

	$entity['surface_class'] = 'editorial_draft';
	$entity['index_policy'] = 'noindex_private';
	$entity['publication']['state'] = 'private_preview';
	$entity['publication']['public_api'] = false;
	$entity['publication']['public_page'] = false;
	$entity['publication']['search_index'] = false;
	$entity['seo']['page_role'] = 'topic_hub' === $spec['type'] ? 'category' : 'spoke';
	$entity['seo']['route_mode'] = 'private';
	$entity['seo']['cluster_id'] = 'cluster-syrian-regional-cuisine';
	$entity['seo']['hub_entity_id'] = 'cuisine-syrian-regional';
	$entity['seo']['intent_classes'] = array( 'informational' );
	$entity['commerce']['state'] = 'reference_only';
	$entity['commerce']['woo_product_code'] = '';
	$entity['commerce']['public_offer_allowed'] = false;
	$entity['commerce']['product_copy'] = $c99_text( 'רשומת מחקר פרטית בלבד. אין כאן מוצר, מחיר, מלאי, ספק או הצעת מכר.', 'Private research record only. It creates no product, price, inventory, supplier or sales offer.' );
	$entity['commerce']['cross_sell_ids'] = array();
	$entity['commerce']['up_sell_ids'] = array();
	$entity['commerce']['business_model']['revenue_models'] = array( 'education' );
	$entity['commerce']['business_model']['customer_segments'] = array( 'culinary_consumers', 'research_readers' );
	$entity['commerce']['business_model']['value_proposition'] = $c99_text( 'ערך הרשומה הוא לימודי ומחקרי בלבד עד השלמת אימות נפרד.', 'The record has educational and research value only until separate verification is complete.' );
	$entity['commerce']['business_model']['pricing_state'] = 'research_required';
	$entity['commerce']['business_model']['market_scope'] = 'global_research';
	$entity['commerce']['business_model']['observation_entity_ids'] = array();
	$entity['commerce']['business_model']['margin_scenario'] = array(
		'currency' => '',
		'landed_cost_low' => null,
		'landed_cost_high' => null,
		'retail_price_low' => null,
		'retail_price_high' => null,
		'gross_margin_low' => null,
		'gross_margin_high' => null,
		'basis' => $c99_text( 'לא נוצר תרחיש מסחרי לרשומת המחקר הפרטית.', 'No commercial scenario is created for this private research record.' ),
		'confidence' => 'pending',
		'reviewed_at' => '',
	);
	$entity['trust']['commercial_purpose'] = $c99_text( 'הרשומה היא סימוכין פרטיים בלבד ואינה יוצרת מוצר, מחיר, מלאי, ספק, הצעה או עמוד ציבורי.', 'This is a private reference only and creates no product, price, inventory, supplier, offer or public page.' );
	$entity['trust']['substantive_updated_at'] = '2026-08-07';
	$entity['review']['reviewed_at'] = '2026-08-07';
	$entity['visual']['negative_prompt_en'] = 'No text, no logos, no flags, no copied source image, no health claim, no price, no package, no supplier, no watermark.';

	foreach ( $entity['relations'] as $relation_index => &$relation ) {
		$relation['id'] = 'edge-' . $entity['id'] . '-' . $relation['type'] . '-' . ( $relation_index + 1 );
		$relation['valid_from'] = '2026-08-07';
	}
	unset( $relation );

	return $entity;
};

$c99_syrian_west_rows = array(
	array(
		'id' => 'guide-aleppo-damascus-mahshi-methods', 'type' => 'guide', 'slug' => 'aleppo-damascus-mahshi-methods', 'parent_id' => 'cuisine-syrian-regional', 'region' => 'aleppo-and-damascus', 'community' => 'razan-family-testimony',
		'name_he' => 'השוואת מחשי חלב ודמשק', 'name_en' => 'Aleppo and Damascus Mahshi Method Comparison', 'sources' => array( 'avs-razan-damascus' ),
		'summary_he' => 'מדריך פרטי המשווה בין שני פרטים בעדותה של רזאן: רסק עגבניות בתוך מילוי המיוחס למשפחתה בחלב, לעומת חריע במילוי ורסק ברוטב הבישול בהקשר הדמשקאי שלה.',
		'summary_en' => 'A private guide comparing two details in Razan\'s testimony: tomato paste inside a filling associated with her Aleppan family account, and safflower in the filling with tomato paste in the cooking sauce in her Damascene account.',
		'fact_he' => 'זהו מקור משפחתי אחד המשמר הבחנה שימושית בין שתי שיטות, ולא כלל מחייב לכל בתי חלב או דמשק.', 'fact_en' => 'This is one family source preserving a useful distinction between two methods, not a binding rule for every Aleppan or Damascene household.',
		'science_he' => 'העדות אינה מספקת כמויות, זני ירק, pH, ריכוז רסק, טמפרטורה או אימות בטיחות למילוי.', 'science_en' => 'The testimony supplies no quantities, vegetable cultivars, pH, paste concentration, temperature or filling safety validation.', 'science_sources' => array( 'avs-razan-damascus', 'foodsafety-safe-temperatures' ),
		'risk_he' => 'המדריך נשאר השוואה פרטית. אין לפרסם ממנו מתכון, יחס או טענה עירונית בלי מקור נוסף ובדיקת בטיחות.', 'risk_en' => 'Keep this as a private comparison. Do not publish a recipe, ratio or citywide claim without added evidence and safety review.', 'risk_sources' => array( 'avs-razan-damascus', 'foodsafety-safe-temperatures' ),
		'references' => array( 'technique-aleppine-mahshi-tomato-paste-in-filling', 'technique-damascene-mahshi-safflower-filling-tomato-sauce' ),
		'prompt_en' => 'Private editorial split-screen mahshi research plate, Aleppan filling study on the left and Damascene filling-and-sauce study on the right, neutral ceramic vessels, no quantities, no labels, controlled soft side light.',
	),
	array(
		'id' => 'technique-aleppine-mahshi-tomato-paste-in-filling', 'type' => 'technique', 'slug' => 'aleppine-mahshi-tomato-paste-filling', 'parent_id' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'razan-family-testimony',
		'name_he' => 'רסק עגבניות במילוי מחשי חַלַבּי בעדות משפחתית', 'name_en' => 'Tomato Paste in an Aleppine Family Mahshi Filling', 'sources' => array( 'avs-razan-damascus' ),
		'summary_he' => 'ישות שיטה המשמרת את פרט העדות שלפיו רסק עגבניות עורבב במילוי מחשי בהקשר המשפחתי החַלַבּי של רזאן.', 'summary_en' => 'A method entity retaining the testimony detail that tomato paste was mixed into mahshi filling in Razan\'s Aleppine family context.',
		'fact_he' => 'המקור מתעד מיקום של רכיב בתוך המילוי, אך אינו מוכיח שזו נוסחה חַלַבּית אוניברסלית.', 'fact_en' => 'The source documents the placement of an ingredient inside the filling but does not establish a universal Aleppine formula.',
		'science_he' => 'אין במקור נתון על מוצקי עגבנייה, מלח, חומציות, פעילות מים או טמפרטורת ליבה.', 'science_en' => 'The source gives no tomato-solids, salt, acidity, water-activity or core-temperature measurement.', 'science_sources' => array( 'avs-razan-damascus', 'foodsafety-safe-temperatures' ),
		'risk_he' => 'אין להמיר את פרט העדות להוראות הכנה או ליחס רכיבים ציבורי לפני ניסוי ואימות.', 'risk_en' => 'Do not convert the testimony detail into public instructions or ingredient ratios before testing and verification.', 'risk_sources' => array( 'avs-razan-damascus', 'foodsafety-safe-temperatures' ),
		'references' => array( 'ingredient-syrian-tomato-paste', 'guide-aleppo-damascus-mahshi-methods' ),
		'prompt_en' => 'Private macro editorial study of a neutral mahshi filling bowl with a visibly separate tomato-paste fold-in stage, no recipe quantities, no hands, pale limestone background, precise overhead light.',
	),
	array(
		'id' => 'technique-damascene-mahshi-safflower-filling-tomato-sauce', 'type' => 'technique', 'slug' => 'damascene-mahshi-safflower-filling-tomato-sauce', 'parent_id' => 'region-syria-damascus', 'region' => 'damascus', 'community' => 'razan-family-testimony',
		'name_he' => 'חריע במילוי ורסק ברוטב מחשי דמשקאי בעדות משפחתית', 'name_en' => 'Safflower Filling and Tomato Sauce in a Damascene Family Mahshi', 'sources' => array( 'avs-razan-damascus' ),
		'summary_he' => 'ישות שיטה פרטית המתעדת חריע בתוך המילוי ורסק עגבניות בנוזל הבישול בנוסח הדמשקאי של עדות רזאן.', 'summary_en' => 'A private method entity documenting safflower inside the filling and tomato paste in the cooking liquid in Razan\'s Damascene account.',
		'fact_he' => 'העדות מבחינה בין מיקום החריע במילוי לבין מיקום רסק העגבניות ברוטב, בלי לקבוע נוסחה עירונית אחת.', 'fact_en' => 'The testimony distinguishes safflower in the filling from tomato paste in the sauce without defining one citywide formula.',
		'science_he' => 'אין מדידות לזהות החריע, לריכוז הרסק, ל-pH, ליחס נוזל או לטמפרטורת ליבה.', 'science_en' => 'There are no measurements for safflower identity, paste concentration, pH, liquid ratio or core temperature.', 'science_sources' => array( 'avs-razan-damascus', 'foodsafety-safe-temperatures' ),
		'risk_he' => 'יש לאמת את חומר הגלם ואת תהליך הבישול לפני כל מתכון או יישום תפעולי.', 'risk_en' => 'Verify ingredient identity and the cooking process before any recipe or operational use.', 'risk_sources' => array( 'avs-razan-damascus', 'foodsafety-safe-temperatures' ),
		'references' => array( 'ingredient-syrian-safflower-damascus-context', 'ingredient-syrian-tomato-paste', 'guide-aleppo-damascus-mahshi-methods' ),
		'prompt_en' => 'Private culinary process still life with a safflower-speckled mahshi filling study beside a separate tomato cooking sauce vessel, no amounts, no embedded instructions, warm controlled studio light.',
	),
	array(
		'id' => 'ingredient-syrian-safflower-damascus-context', 'type' => 'ingredient', 'slug' => 'syrian-safflower-damascus-context', 'parent_id' => 'region-syria-damascus', 'region' => 'damascus', 'community' => 'razan-family-testimony',
		'name_he' => 'חריע בהקשר מילוי דמשקאי', 'name_en' => 'Safflower in a Damascene Filling Context', 'sources' => array( 'avs-razan-damascus' ),
		'summary_he' => 'רשומת זהות פרטית למונח חריע כפי שהוא מופיע במילוי בעדות רזאן, בלי לייחס זן, דרגה או תכונה בריאותית.', 'summary_en' => 'A private identity record for safflower as named in Razan\'s filling account, without assigning a cultivar, grade or health property.',
		'fact_he' => 'המקור תומך בשם חומר הגלם ובהקשר השימוש בלבד.', 'fact_en' => 'The source supports only the ingredient name and its use context.',
		'science_he' => 'זהות בוטנית, חלק הצמח, תרכובות צבע וארומה, מינון ואלרגניות לא נמדדו במקור.', 'science_en' => 'Botanical identity, plant part, color and aroma compounds, dose and allergenicity were not measured in the source.', 'science_sources' => array( 'avs-razan-damascus' ),
		'risk_he' => 'אין להציג זהות מוצר, תועלת בריאותית או מינון לפני אימות בוטני ותווית.', 'risk_en' => 'Do not present a product identity, health benefit or dose before botanical and label verification.', 'risk_sources' => array( 'avs-razan-damascus' ),
		'references' => array( 'technique-damascene-mahshi-safflower-filling-tomato-sauce' ),
		'prompt_en' => 'Private botanical identity plate of loose safflower florets in a plain glass dish beside an empty specimen-verification area, macro texture, neutral daylight, no package, no medicinal symbolism.',
	),
	array(
		'id' => 'ingredient-shanklish-homs-context', 'type' => 'ingredient', 'slug' => 'shanklish-homs-context', 'parent_id' => 'region-syria-homs', 'region' => 'homs', 'community' => 'nariman-homsi-family-testimony',
		'name_he' => 'שנקליש בהקשר חומסי משפחתי', 'name_en' => 'Shanklish in a Homs Family Context', 'sources' => array( 'avs-nariman-homs' ),
		'summary_he' => 'רשומת מחקר לשנקליש בעדות נרימאן מחומס, המקשרת אותו לגבינת קרישה, תיבול, ייבוש והמשך הבשלה בצנצנת.', 'summary_en' => 'A research record for shanklish in Nariman\'s Homs testimony, linking it to curd cheese, seasoning, drying and later maturation in a jar.',
		'fact_he' => 'העדות מתארת כדורים מתובלים המיובשים 15 עד 20 יום ולאחר מכן נשמרים בצנצנת להמשך הבשלה.', 'fact_en' => 'The testimony describes seasoned balls dried for 15 to 20 days and then kept in a jar for further maturation.',
		'science_he' => 'אין במקור מדידות pH, פעילות מים, מלח, תרבית, טמפרטורה, לחות או חיי מדף מאומתים.', 'science_en' => 'The source provides no validated pH, water activity, salt, culture, temperature, humidity or shelf-life measurements.', 'science_sources' => array( 'avs-nariman-homs', 'fda-water-activity' ), 'science_evidence' => 'regulatory_standard',
		'risk_he' => 'אין להפוך את תיאור הייבוש וההבשלה לתהליך ייצור או לשימור בטוח בלי תוכנית מאומתת ומדידות מוצר.', 'risk_en' => 'Do not convert the drying and maturation account into a production or safe-preservation process without a validated plan and product measurements.', 'risk_sources' => array( 'avs-nariman-homs', 'fda-water-activity' ),
		'references' => array( 'ingredient-qareesheh-homs-context', 'technique-homsi-shanklish-drying-and-fermentation' ),
		'compliance' => array(
			array( 'dairy-fermentation-water-activity-and-cold-chain-validation', 'כל שימוש עתידי דורש חלב מפוסטר ומזוהה, אלרגן חלב, תרבית מאומתת, מדידות pH, מלח ופעילות מים, בקרת טמפרטורה ולחות, שרשרת קירור וחיי מדף שנבדקו.', 'Any future use requires identified pasteurized milk, milk-allergen control, validated culture, measured pH, salt and water activity, temperature and humidity control, cold chain and tested shelf life.', array( 'avs-nariman-homs', 'fda-water-activity', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Private dairy research plate with one unbranded seasoned shanklish ball, a separate curd sample and an empty measurement card area, dry neutral studio surface, no shelf-life seal.',
	),
	array(
		'id' => 'ingredient-qareesheh-homs-context', 'type' => 'ingredient', 'slug' => 'qareesheh-homs-context', 'parent_id' => 'region-syria-homs', 'region' => 'homs', 'community' => 'nariman-homsi-family-testimony',
		'name_he' => 'קרישה בהקשר שנקליש חומסי', 'name_en' => 'Qareesheh in a Homs Shanklish Context', 'sources' => array( 'avs-nariman-homs' ),
		'summary_he' => 'ישות פרטית לגבינת הקרישה שממנה מתחיל השנקליש בעדות המשפחתית של נרימאן.', 'summary_en' => 'A private entity for the curd cheese from which shanklish begins in Nariman\'s family testimony.',
		'fact_he' => 'המקור מציין קרישה כחומר מוצא, אך אינו מגדיר סוג חלב, תרבית, אחוזי שומן או לחות.', 'fact_en' => 'The source names qareesheh as a starting material but does not define milk type, culture, fat percentage or moisture.',
		'science_he' => 'זהות מיקרוביאלית, pH, מלח ושרשרת קירור דורשים בדיקה ברמת חומר הגלם בפועל.', 'science_en' => 'Microbial identity, pH, salt and cold-chain conditions require testing of the actual ingredient.', 'science_sources' => array( 'avs-nariman-homs' ),
		'risk_he' => 'אין להסיק מפרט מוצר או יציבות מגבינת המקור ללא תווית, בדיקות ותהליך מאומת.', 'risk_en' => 'Do not infer a product specification or stability from the source cheese without a label, testing and a validated process.', 'risk_sources' => array( 'avs-nariman-homs' ),
		'references' => array( 'ingredient-shanklish-homs-context' ),
		'compliance' => array(
			array( 'dairy-pasteurization-allergen-and-cold-chain-validation', 'גבינת הקרישה דורשת מקור חלב מפוסטר ומזוהה, תרבית ותהליך מאומתים, אלרגן חלב, מפרט מיקרוביולוגי ושרשרת קירור מתועדת.', 'Qareesheh requires identified pasteurized milk, validated culture and process, milk-allergen control, a microbiological specification and documented cold chain.', array( 'avs-nariman-homs', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Private close-up ingredient study of fresh qareesheh curd in a plain white bowl with visible whey separation and an empty laboratory-note zone, cool diffuse light, no dairy brand.',
	),
	array(
		'id' => 'technique-homsi-shanklish-drying-and-fermentation', 'type' => 'technique', 'slug' => 'homsi-shanklish-drying-fermentation', 'parent_id' => 'ingredient-shanklish-homs-context', 'region' => 'homs', 'community' => 'nariman-homsi-family-testimony',
		'name_he' => 'ייבוש והבשלת שנקליש בעדות חומסית', 'name_en' => 'Shanklish Drying and Maturation in a Homs Account', 'sources' => array( 'avs-nariman-homs' ),
		'summary_he' => 'רשומת תהליך המתעדת את רצף הייבוש וההבשלה שבסיפור נרימאן, בלי להפוך אותו לפרוטוקול ייצור.', 'summary_en' => 'A process record documenting the drying and maturation sequence in Nariman\'s account without turning it into a production protocol.',
		'fact_he' => 'העדות מציינת תיבול בפלפל, כוסברה ומלח, עיצוב כדורים, ייבוש של 15 עד 20 יום והעברה לצנצנת.', 'fact_en' => 'The testimony names pepper, coriander and salt, ball shaping, 15 to 20 days of drying and transfer to a jar.',
		'science_he' => 'משך לבדו אינו מאמת בטיחות: יש למדוד פעילות מים, pH, מלח, טמפרטורה, לחות ומיקרוביולוגיה.', 'science_en' => 'Duration alone does not validate safety: water activity, pH, salt, temperature, humidity and microbiology require measurement.', 'science_sources' => array( 'avs-nariman-homs', 'fda-water-activity' ), 'science_evidence' => 'regulatory_standard',
		'risk_he' => 'הישות היא תיעוד תרבותי בלבד ואינה הוראת ייבוש, תסיסה, אריזה או אחסון.', 'risk_en' => 'This entity is cultural documentation only, not a drying, fermentation, packing or storage instruction.', 'risk_sources' => array( 'avs-nariman-homs', 'fda-water-activity' ),
		'references' => array( 'ingredient-qareesheh-homs-context' ),
		'compliance' => array(
			array( 'dairy-fermentation-process-and-shelf-life-validation', 'אין להפעיל את רצף הייבוש וההבשלה ללא חומר גלם חלבי מפוסטר, תוכנית תסיסה מאומתת, מדידות pH, מלח ופעילות מים, בקרת סביבה, מיקרוביולוגיה, אריזה, קירור וחיי מדף.', 'Do not operate the drying and maturation sequence without pasteurized dairy, a validated fermentation plan, measured pH, salt and water activity, environmental control, microbiology, packaging, refrigeration and shelf-life validation.', array( 'avs-nariman-homs', 'fda-water-activity', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Private process storyboard showing three separated non-instructional stages of shanklish research, fresh curd ball, dry exterior study and sealed-jar concept, no timers, no temperatures, no safety badge.',
	),
	array(
		'id' => 'tradition-asrouniyeh-homs', 'type' => 'tradition', 'slug' => 'asrouniyeh-homs', 'parent_id' => 'region-syria-homs', 'region' => 'homs', 'community' => 'nariman-homsi-family-testimony',
		'name_he' => 'עַסְרוּנִיֶּה חומסית בעדות משפחתית', 'name_en' => 'Homs Asrouniyeh in a Family Testimony', 'sources' => array( 'avs-nariman-homs' ),
		'summary_he' => 'עדות נרימאן מתארת ארוחת אחר צהריים מאוחרת ובה שנקליש, מקדוס, זיתים, מוחמרה ועגבניות.', 'summary_en' => 'Nariman\'s testimony describes a late-afternoon meal with shanklish, makdous, olives, muhammara and tomatoes.',
		'fact_he' => 'הארוחה נשמרת כזיכרון משפחתי מחומס ולא כמנהג אחיד של כל תושבי העיר.', 'fact_en' => 'The meal is retained as a Homs family memory rather than a uniform practice of every city resident.',
		'science_he' => 'המקור אינו מספק כמויות, הרכב תזונתי, מפרטי אלרגן, שרשרת קירור או חיי מדף לרכיבי הארוחה.', 'science_en' => 'The source supplies no quantities, nutritional composition, allergen specifications, cold-chain data or shelf life for the meal components.', 'science_sources' => array( 'avs-nariman-homs' ),
		'risk_he' => 'אין להציג את העדות כטענה עירונית, כהמלצה בריאותית או כתפריט מסחרי.', 'risk_en' => 'Do not present the testimony as a citywide claim, health recommendation or commercial menu.', 'risk_sources' => array( 'avs-nariman-homs' ),
		'references' => array( 'ingredient-shanklish-homs-context', 'dish-muhammara-syrian' ),
		'prompt_en' => 'Private late-afternoon family-memory table with separate plain dishes for shanklish, makdous, olives, muhammara and tomatoes, soft window light, no people, no menu styling, no universal-city claim.',
	),
	array(
		'id' => 'technique-hamawi-batersh-smoke-and-tahini-emulsion', 'type' => 'technique', 'slug' => 'hamawi-batersh-smoke-tahini-emulsion', 'parent_id' => 'dish-batersh-hama', 'region' => 'hama',
		'name_he' => 'קליית חציל ואמולסיית טחינה לבאטרש חמאווי', 'name_en' => 'Eggplant Charring and Tahini Emulsion for Hamawi Batersh', 'sources' => array( 'avs-noor-hama' ),
		'summary_he' => 'ישות שיטה פרטית המתעדת אפשרויות קליית חציל בעדות נור ואת חיבורו לבסיס טחינה בבאטרש מחמה.', 'summary_en' => 'A private method entity documenting eggplant charring options in Noor\'s testimony and its combination with a tahini base in Hama batersh.',
		'fact_he' => 'העדות מזכירה קלייה בגז, בתנור, על פחמים או במאפייה מוסקת עץ, אך אינה מודדת פרופיל עשן.', 'fact_en' => 'The testimony mentions gas, oven, charcoal or wood-fired bakery charring but does not measure a smoke profile.',
		'science_he' => 'יציבות האמולסיה תלויה בהרכב הטחינה, מים, חומציות וגזירה שלא נמדדו; עשן ואש דורשים אוורור ובקרת חשיפה.', 'science_en' => 'Emulsion stability depends on unmeasured tahini composition, water, acidity and shear; smoke and flame require ventilation and exposure control.', 'science_sources' => array( 'avs-noor-hama', 'who-household-air-pollution-2025' ),
		'risk_he' => 'יש לציין אלרגן שומשום ולפתח כל שימוש באש או עשן רק בסביבת עבודה מאווררת ומבוקרת.', 'risk_en' => 'Identify sesame allergen risk and develop any flame or smoke use only in a controlled, ventilated workspace.', 'risk_sources' => array( 'avs-noor-hama', 'who-household-air-pollution-2025' ),
		'references' => array( 'ingredient-syrian-eggplant', 'ingredient-syrian-tahini' ),
		'compliance' => array(
			array( 'sesame-allergen-flame-smoke-and-ventilation-validation', 'יש לאמת אלרגן שומשום ומגע צולב. עבודה בגז, פחמים או עץ דורשת ציוד ודלק מתאימים, אוורור, בקרת עשן, אש וכוויות ואימות תהליך מקצועי.', 'Verify sesame allergen and cross-contact. Gas, charcoal or wood use requires suitable equipment and fuel, ventilation, smoke, flame and burn controls, and professional process validation.', array( 'avs-noor-hama', 'who-household-air-pollution-2025', 'israel-moh-allergen-survey-2024' ) ),
		),
		'prompt_en' => 'Private controlled batersh technique plate with charred eggplant flesh, a separate smooth tahini emulsion and a ventilated professional charring setup in soft focus, no open indoor smoke cloud, no recipe text.',
	),
	array(
		'id' => 'dish-jerneh-idlib', 'type' => 'dish', 'slug' => 'jerneh-idlib', 'parent_id' => 'region-syria-idlib-maarrat', 'region' => 'idlib-harem',
		'name_he' => 'ג׳רנה מאידליב וחארם', 'name_en' => 'Jerneh from Idlib and Harem', 'sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'summary_he' => 'מנה מתועדת שבה בצל, עגבנייה, פלפל אדום, מלח ושמן זית נכתשים בג׳רן ומחוברים לחציל קלוי.', 'summary_en' => 'A documented dish in which onion, tomato, red pepper, salt and olive oil are pounded in a jern and combined with grilled eggplant.',
		'fact_he' => 'הרשומה שומרת את שיטת הכתישה ואת החציל הקלוי כמאפיינים של המקור, בלי לקבוע כמויות.', 'fact_en' => 'The record retains pounding and grilled eggplant as source features without setting quantities.',
		'science_he' => 'אין במקור pH, פעילות מים, חריפות, ריכוז מלח, פרופיל עשן או חיי מדף מאומתים.', 'science_en' => 'The source gives no validated pH, water activity, heat level, salt concentration, smoke profile or shelf life.', 'science_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'risk_he' => 'הישות היא תיעוד מנה פרטי, לא מתכון, מפרט ייצור או הצעת מכר.', 'risk_en' => 'This is private dish documentation, not a recipe, production specification or sales offer.', 'risk_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'references' => array( 'technique-jern-pounding-idlib' ),
		'prompt_en' => 'Private editorial Jerneh study with a stone mortar holding visibly pounded onion, tomato and red pepper beside fully grilled eggplant flesh and olive oil, rustic but neutral surface, no quantities, no labels.',
	),
	array(
		'id' => 'dish-chili-dakkah-idlib', 'type' => 'dish', 'slug' => 'chili-dakkah-idlib', 'parent_id' => 'region-syria-idlib-maarrat', 'region' => 'idlib-harem',
		'name_he' => 'דקת צ׳ילי מאידליב וחארם', 'name_en' => 'Chili Dakkah from Idlib and Harem', 'sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'summary_he' => 'תערובת מקור מתועדת של עגבנייה, בצל, פלפל אדום, אגוזי מלך, מלח, שמן וכמון, הנשמרת בנפרד מג׳רנה משום שהמקור אינו כולל בה חציל.', 'summary_en' => 'A documented mixture of tomato, onion, red pepper, walnuts, salt, oil and cumin, kept separate from Jerneh because the source does not include eggplant in it.',
		'fact_he' => 'היעדר חציל בתערובת הוא גבול זהות חשוב מול ג׳רנה.', 'fact_en' => 'The absence of eggplant from the mixture is an important identity boundary against Jerneh.',
		'science_he' => 'זן פלפל, חריפות, מצב האגוז, גודל חלקיקים, pH ויציבות אינם נמדדים במקור.', 'science_en' => 'Pepper cultivar, heat, walnut condition, particle size, pH and stability are not measured in the source.', 'science_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'risk_he' => 'יש לשמור אגוזי מלך כאלרגן ולמנוע הוספת חציל או טענת נוסחה ללא מקור נוסף.', 'risk_en' => 'Retain walnut as an allergen boundary and do not add eggplant or claim a fixed formula without added evidence.', 'risk_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'references' => array( 'ingredient-syrian-walnuts', 'technique-jern-pounding-idlib' ),
		'compliance' => array(
			array( 'walnut-allergen-product-and-cross-contact-validation', 'יש לזהות את מוצר אגוזי המלך והאצווה, להצהיר אלרגן אגוזים ולמנוע מגע צולב לפני כל פיתוח מתכון או הצעה.', 'Identify the walnut product and lot, declare tree-nut allergen and prevent cross-contact before any recipe or offer development.', array( 'ifrepo-idlib-harem-foodways', 'israel-moh-allergen-survey-2024' ) ),
		),
		'prompt_en' => 'Private chili dakkah identity plate with coarsely pounded tomato, onion, red pepper, walnuts and cumin in a shallow stone bowl, explicitly no eggplant visible, crisp natural light, no garnish.',
	),
	array(
		'id' => 'dish-aqras-al-zawbaa-idlib', 'type' => 'dish', 'slug' => 'aqras-al-zawbaa-idlib', 'parent_id' => 'region-syria-idlib-maarrat', 'region' => 'idlib-harem',
		'name_he' => 'אקרַאס אל-זַוְבַּע מאידליב', 'name_en' => 'Aqras al-Zawbaa from Idlib', 'sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'summary_he' => 'מאפה המתועד עם מחית צ׳ילי ועם עשב מקומי המכונה במקור זובעא, כאשר זהות העשב נשארת מונח מקור עד אימות בוטני.', 'summary_en' => 'A pastry documented with chili paste and a local herb called Zawbaa in the source, whose botanical identity remains unresolved pending verification.',
		'fact_he' => 'המקור תומך בשם המאפה, במחית הצ׳ילי ובמונח זובעא, אך לא במפרט בוטני או במתכון מלא.', 'fact_en' => 'The source supports the pastry name, chili paste and the term Zawbaa, but not a botanical specification or complete recipe.',
		'science_he' => 'סוג קמח, הידרציה, התפחה, טמפרטורת אפייה, pH וזהות תרכובות הארומה אינם נמדדים.', 'science_en' => 'Flour type, hydration, fermentation, baking temperature, pH and aroma-compound identity are not measured.', 'science_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'risk_he' => 'אין לזהות את זובעא עם מין מסוים, להציע תחליף או לפרסם נוסחה לפני אימות.', 'risk_en' => 'Do not identify Zawbaa as a particular species, propose a substitute or publish a formula before verification.', 'risk_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'references' => array( 'ingredient-zawbaa-unresolved-herb-idlib' ),
		'prompt_en' => 'Private pastry research still life of small unlabeled flat rounds with a restrained chili-paste surface and a separate unidentified herb specimen zone, no botanical claim, no recipe steps, clean studio daylight.',
	),
	array(
		'id' => 'tradition-zannaneh-olive-press-idlib', 'type' => 'tradition', 'slug' => 'zannaneh-olive-press-idlib', 'parent_id' => 'region-syria-idlib-maarrat', 'region' => 'idlib-harem',
		'name_he' => 'זַנַּאנֶה בבית בד באידליב', 'name_en' => 'Zannaneh at an Idlib Olive Press', 'sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'summary_he' => 'מסורת מסיק ובית בד המתועדת עם לחם סאג׳, שום, רימון, נענע ושמן זית טרי בשפע.', 'summary_en' => 'An olive-harvest and press tradition documented with saj bread, garlic, pomegranate, mint and abundant fresh olive oil.',
		'fact_he' => 'האכילה המשותפת בידיים היא פרט בעדות המקור ואינה הופכת לפרוטוקול הגשה ציבורי.', 'fact_en' => 'Shared eating by hand is a detail in the source testimony and does not become a public serving protocol.',
		'science_he' => 'המקור אינו מספק זן זית, תאריך מסיק, חומציות, פוליפנולים, מצב היגייני או יציבות שמן.', 'science_en' => 'The source supplies no olive cultivar, harvest date, acidity, polyphenol, hygiene or oil-stability measurement.', 'science_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'risk_he' => 'אין להציג דרגת שמן, תועלת בריאותית או הוראת הגשה מן המסורת ללא נתוני אצווה ותוכנית היגיינה.', 'risk_en' => 'Do not present an oil grade, health benefit or serving instruction from the tradition without lot data and a hygiene plan.', 'risk_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'references' => array( 'ingredient-syrian-olive-oil' ),
		'prompt_en' => 'Private documentary olive-press table with saj bread, garlic, pomegranate, mint and a plain bowl of fresh oil, no people, no hand-eating scene, no quality seal, muted mill background.',
	),
	array(
		'id' => 'ingredient-zawbaa-unresolved-herb-idlib', 'type' => 'ingredient', 'slug' => 'zawbaa-unresolved-herb-idlib', 'parent_id' => 'region-syria-idlib-maarrat', 'region' => 'idlib-harem',
		'name_he' => 'זובעא, מונח עשב מקומי מאידליב', 'name_en' => 'Zawbaa, a Local Idlib Herb Term', 'sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'summary_he' => 'ישות זהות שמרנית למונח זובעא שבמקור, בלי לאחדו אוטומטית עם קורנית, זעתר או מין בוטני אחר.', 'summary_en' => 'A conservative identity entity for the source term Zawbaa, without automatically merging it with thyme, zaatar or another botanical species.',
		'fact_he' => 'המקור מקשר את המונח המקומי למאפה אקראס אל-זובעא ואינו מספק שם מדעי.', 'fact_en' => 'The source links the local term to Aqras al-Zawbaa and supplies no scientific name.',
		'science_he' => 'מין, כימוטיפ, שמנים נדיפים, רעילות, אלרגניות ומינון נשארים בלתי מאומתים.', 'science_en' => 'Species, chemotype, volatile oils, toxicity, allergenicity and dose remain unverified.', 'science_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'risk_he' => 'הישות נשארת מונח מקור פרטי. אין למכור, להחליף או להמליץ על צריכה בלי זיהוי בוטני.', 'risk_en' => 'Keep this as a private source term. Do not sell, substitute or recommend consumption without botanical identification.', 'risk_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'references' => array( 'dish-aqras-al-zawbaa-idlib' ),
		'prompt_en' => 'Private herb identity board with one small dried green specimen labeled only in metadata as Zawbaa, botanical comparison space left empty, macro lens, neutral gray background, no thyme equivalence cue.',
	),
	array(
		'id' => 'technique-jern-pounding-idlib', 'type' => 'technique', 'slug' => 'jern-pounding-idlib', 'parent_id' => 'region-syria-idlib-maarrat', 'region' => 'idlib-harem',
		'name_he' => 'כתישה בג׳רן באידליב וחארם', 'name_en' => 'Jern Pounding in Idlib and Harem', 'sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'summary_he' => 'טכניקת כתישה בג׳רן גדול ועלי כפי שהיא מתועדת בהכנת ג׳רנה ודקת צ׳ילי באזור.', 'summary_en' => 'A large-mortar and pestle pounding technique documented for Jerneh and chili Dakkah in the region.',
		'fact_he' => 'הטכניקה מספקת הקשר לכלי ולמרקם, אך המקור אינו מגדיר מידות, חומר כלי או גודל חלקיקים.', 'fact_en' => 'The technique supplies tool and texture context, but the source does not define dimensions, tool material or particle size.',
		'science_he' => 'עוצמת גזירה, זמן כתישה, חמצון ועליית טמפרטורה לא נמדדו.', 'science_en' => 'Shear intensity, pounding time, oxidation and temperature rise were not measured.', 'science_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'risk_he' => 'כלי כבד דורש יציבות, ניקוי והדרכת עבודה; הרשומה אינה הוראת תפעול.', 'risk_en' => 'A heavy tool requires stability, cleaning and work training; this record is not an operating instruction.', 'risk_sources' => array( 'ifrepo-idlib-harem-foodways' ),
		'references' => array( 'dish-jerneh-idlib', 'dish-chili-dakkah-idlib' ),
		'prompt_en' => 'Private equipment-and-texture study of a large stone jern and pestle with three separated vegetable particle-size samples, no hands, no force diagram, no embedded instruction, documentary overhead light.',
	),
	array(
		'id' => 'guide-kibbeh-nayyeh-idlib-hama-historical-only', 'type' => 'guide', 'slug' => 'kibbeh-nayyeh-idlib-hama-historical-only', 'parent_id' => 'cuisine-syrian-regional', 'region' => 'idlib-and-hama',
		'name_he' => 'קובה נאה באידליב וחמה, הקשר היסטורי בלבד', 'name_en' => 'Raw Kibbeh in Idlib and Hama, Historical Context Only', 'sources' => array( 'ifrepo-idlib-harem-foodways', 'avs-noor-hama', 'cdc-raw-kibbeh-salmonella-2013' ),
		'summary_he' => 'מדריך היסטורי פרטי המשמר אזכור של קובה נאה בחארם והבדל עדותי בין כתישה במכתש באידליב לבין שימוש במטחנה בחמה. הוא אינו כולל רכיבים או הוראות.', 'summary_en' => 'A private historical guide retaining a raw-kibbeh mention in Harem and a testimony contrast between mortar pounding in Idlib and grinder use in Hama. It contains no ingredients or instructions.',
		'fact_he' => 'המקורות מתעדים זיכרון ושוני בכלי עבודה בלבד; אין כאן נוסחה, תהליך או המלצת אכילה.', 'fact_en' => 'The sources document memory and a tool contrast only; this is not a formula, process or eating recommendation.',
		'science_he' => 'התפרצות סלמונלה מתועדת שנקשרה לקובה מבשר בקר טחון נא מחייבת גבול fail-closed לכל תוכן הכנה או צריכה.', 'science_en' => 'A documented Salmonella outbreak linked to raw ground-beef kibbeh requires a fail-closed boundary for all preparation or consumption content.', 'science_sources' => array( 'cdc-raw-kibbeh-salmonella-2013' ),
		'compliance_code' => 'raw-kibbeh-historical-only', 'risk_he' => 'הקשר היסטורי בלבד. אין לפרסם רכיבים, מתכון, שלבי הכנה, טעימה או המלצת צריכה לקובה נאה.', 'risk_en' => 'Historical context only. Do not publish ingredients, a recipe, preparation steps, tasting or a consumption recommendation for raw kibbeh.', 'risk_sources' => array( 'cdc-raw-kibbeh-salmonella-2013' ),
		'references' => array( 'region-syria-idlib-maarrat', 'region-syria-hama' ),
		'prompt_en' => 'Private historical-method comparison with an empty stone mortar on the Idlib side and an unplugged empty grinder on the Hama side, no meat, no ingredients, no food preparation, archival-neutral lighting.',
	),
	array(
		'id' => 'region-syria-qadmus-mountains', 'type' => 'topic_hub', 'slug' => 'qadmus-mountain-foodways', 'parent_id' => 'region-syria-coast', 'region' => 'qadmus-mountains',
		'name_he' => 'מטבח הרי קדמוס', 'name_en' => 'Qadmus Mountain Foodways', 'sources' => array( 'ifrepo-qadmus-foodways' ),
		'summary_he' => 'שער מחקר פרטי למסורות עונתיות בהרי קדמוס, ובהן מאפה מילאדי, תאנים מיובשות ושימוש מקומי מתועד בלוף שנשאר חסום.', 'summary_en' => 'A private research gateway to seasonal Qadmus mountain foodways, including Milady pastry, dried figs and a documented local Arum use that remains held.',
		'fact_he' => 'המקור מתאר מסגרת הררית ועונתית, אך אינו הופך פריט אחד לסמל בלעדי של כל תושבי האזור.', 'fact_en' => 'The source describes a mountain and seasonal setting but does not make one item an exclusive symbol of every regional resident.',
		'science_he' => 'המסגרת התרבותית אינה מספקת מדידות אקלים, הרכב מזון, בטיחות שימור או זהות בוטנית לכל הפריטים.', 'science_en' => 'The cultural framework supplies no climate measurements, food composition, preservation validation or botanical identity for every item.', 'science_sources' => array( 'ifrepo-qadmus-foodways' ),
		'risk_he' => 'כל ישות נשארת פרטית ונבדקת בנפרד; לוף אינו עובר למסלול ציבורי או מסחרי.', 'risk_en' => 'Every entity remains private and is reviewed separately; Arum does not enter a public or commercial path.', 'risk_sources' => array( 'ifrepo-qadmus-foodways', 'pubmed-arum-palaestinum-poisoning-2020' ),
		'references' => array( 'dish-milady-pastry-qadmus', 'ingredient-qadmus-dried-fig', 'ingredient-loof-arum-qadmus-held' ),
		'prompt_en' => 'Private Qadmus mountain foodways atlas with separated studies of a plain pastry, dried figs and a sealed empty hazard-review zone for unidentified Arum, misty stone background, no regional stereotypes.',
	),
	array(
		'id' => 'dish-milady-pastry-qadmus', 'type' => 'dish', 'slug' => 'milady-pastry-qadmus', 'parent_id' => 'region-syria-qadmus-mountains', 'region' => 'qadmus-mountains',
		'name_he' => 'מאפה מילאדי מקדמוס', 'name_en' => 'Milady Pastry from Qadmus', 'sources' => array( 'ifrepo-qadmus-foodways' ),
		'summary_he' => 'מאפה המקושר במקור לראש השנה המזרחי וכולל קמח, מים, מלח, סוכר, חמאה, שומשום וקצח, עם שומר או חילבה כאפשרויות מקור.', 'summary_en' => 'A pastry linked in the source to Eastern New Year and described with flour, water, salt, sugar, butter, sesame and black seed, with fennel or fenugreek as source options.',
		'fact_he' => 'רשימת הרכיבים נשמרת כתיאור מקור ואינה הופכת למתכון מדוד או לנוסחה אחידה.', 'fact_en' => 'The component list remains a source description and does not become a measured recipe or uniform formula.',
		'science_he' => 'אין נתונים על סוג קמח, גלוטן, הידרציה, שומן, התפחה, טמפרטורת אפייה או פעילות מים.', 'science_en' => 'There are no data for flour type, gluten, hydration, fat, leavening, baking temperature or water activity.', 'science_sources' => array( 'ifrepo-qadmus-foodways' ),
		'risk_he' => 'יש לאמת אלרגני גלוטן, חלב ושומשום וכל מפרט רכיב לפני פיתוח מתכון.', 'risk_en' => 'Verify gluten, dairy and sesame allergens and every ingredient specification before recipe development.', 'risk_sources' => array( 'ifrepo-qadmus-foodways' ),
		'references' => array( 'tradition-qadmus-eastern-new-year-pastry' ),
		'compliance' => array(
			array( 'gluten-dairy-sesame-allergen-and-baking-validation', 'יש לאמת קמח, חמאה, שומשום וכל תוספת, להצהיר אלרגני גלוטן, חלב ושומשום, למנוע מגע צולב ולאמת אפייה, קירור, אחסון וחיי מדף.', 'Verify flour, butter, sesame and every addition, declare gluten, milk and sesame allergens, prevent cross-contact, and validate baking, cooling, storage and shelf life.', array( 'ifrepo-qadmus-foodways', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Private Milady pastry identity study with a few plain baked forms and separate small dishes of sesame, black seed, fennel and fenugreek, no exact proportions, winter daylight, neutral stone table.',
	),
	array(
		'id' => 'ingredient-qadmus-dried-fig', 'type' => 'ingredient', 'slug' => 'qadmus-dried-fig', 'parent_id' => 'region-syria-qadmus-mountains', 'region' => 'qadmus-mountains',
		'name_he' => 'תאנים מיובשות בהקשר קדמוס', 'name_en' => 'Dried Figs in a Qadmus Context', 'sources' => array( 'ifrepo-qadmus-foodways' ),
		'summary_he' => 'תאנים מיובשות מתועדות במזווה קדמוס ולעיתים נאכלות עם אגוזי מלך, בלי מפרט זן, ייבוש או אחסון.', 'summary_en' => 'Dried figs are documented in the Qadmus pantry and are sometimes paired with walnuts, without a cultivar, drying or storage specification.',
		'fact_he' => 'הקישור לאגוזי מלך מתועד כהתאמה מקומית אפשרית ולא כחובה לכל תאנה מיובשת.', 'fact_en' => 'The walnut pairing is documented as a possible local accompaniment, not a requirement for every dried fig.',
		'science_he' => 'לחות, פעילות מים, סוכרים, עובש, מיקוטוקסינים וחיי מדף דורשים בדיקת מוצר בפועל.', 'science_en' => 'Moisture, water activity, sugars, mold, mycotoxins and shelf life require testing of the actual product.', 'science_sources' => array( 'ifrepo-qadmus-foodways', 'fda-water-activity' ), 'science_evidence' => 'regulatory_standard',
		'risk_he' => 'אין לטעון ליציבות, איכות או תועלת בריאותית ללא בדיקות אצווה ותנאי אחסון.', 'risk_en' => 'Do not claim stability, quality or health benefit without lot testing and storage conditions.', 'risk_sources' => array( 'ifrepo-qadmus-foodways', 'fda-water-activity' ),
		'references' => array( 'ingredient-syrian-walnuts' ),
		'prompt_en' => 'Private Qadmus pantry plate of whole and cut dried figs in one plain dish with walnuts kept in a separate optional-pairing dish, macro texture, no wellness cue, no packaging.',
	),
	array(
		'id' => 'ingredient-loof-arum-qadmus-held', 'type' => 'ingredient', 'slug' => 'loof-arum-qadmus-held', 'parent_id' => 'region-syria-qadmus-mountains', 'region' => 'qadmus-mountains',
		'name_he' => 'לוף או ארום מקדמוס, מוחזק וחסום', 'name_en' => 'Qadmus Loof or Arum, Held and Fail Closed', 'sources' => array( 'ifrepo-qadmus-foodways', 'arum-eurasia-review-2025', 'pubmed-arum-palaestinum-poisoning-2020' ),
		'summary_he' => 'ישות זיהוי מסוכנת הנשמרת במצב held ו-fail-closed. המקור מתעד שימוש מקומי מבושל, אך זהות בוטנית ותהליך הפחתת הסיכון אינם מאומתים.', 'summary_en' => 'A hazardous identity entity held fail-closed. The source documents local cooked use, but botanical identity and risk-reduction process are not validated.',
		'fact_he' => 'השם העממי לוף אינו מספיק לזיהוי מין, חלק צמח או התאמה למאכל.', 'fact_en' => 'The vernacular name Loof is insufficient to identify species, plant part or food suitability.',
		'science_he' => 'ספרות מדעית מתעדת סיכון גירוי והרעלה במיני Arum; אין להניח שבישול לא מוגדר מבטל את הסיכון.', 'science_en' => 'Scientific literature documents irritation and poisoning risk in Arum species; unspecified cooking must not be assumed to remove the hazard.', 'science_sources' => array( 'arum-eurasia-review-2025', 'pubmed-arum-palaestinum-poisoning-2020' ), 'science_evidence' => 'peer_reviewed_context',
		'compliance_code' => 's9-arum-fail-closed', 'risk_he' => 'HELD ו-FAIL CLOSED: אין לפרסם זיהוי, רכיבים, הוראות, שימוש, טעימה, מוצר או מכירה עד זיהוי בוטני ותהליך הפחתת סיכון מאומת.', 'risk_en' => 'HELD AND FAIL CLOSED: publish no identity, ingredients, instructions, use, tasting, product or sale until botanical identification and a validated risk-reduction process exist.', 'risk_sources' => array( 'arum-eurasia-review-2025', 'pubmed-arum-palaestinum-poisoning-2020' ),
		'references' => array( 'technique-qadmus-cooked-arum-preservation-held' ),
		'prompt_en' => 'Private hazard-review board with an empty sealed specimen tray and a generic unidentified Arum silhouette behind frosted glass, no edible presentation, no plant-part claim, red review lighting only, no text.',
	),
	array(
		'id' => 'technique-qadmus-cooked-arum-preservation-held', 'type' => 'technique', 'slug' => 'qadmus-cooked-arum-preservation-held', 'parent_id' => 'ingredient-loof-arum-qadmus-held', 'region' => 'qadmus-mountains',
		'name_he' => 'שימור לוף מבושל מקדמוס, מוחזק וחסום', 'name_en' => 'Qadmus Cooked Arum Preservation, Held and Fail Closed', 'sources' => array( 'ifrepo-qadmus-foodways', 'arum-eurasia-review-2025', 'pubmed-arum-palaestinum-poisoning-2020', 'fda-acidified-low-acid-foods' ),
		'summary_he' => 'תיעוד פרטי בלבד לכך שהמקור מתאר בישול ולאחריו שימור בצנצנת בדומה לחמוצים. אין כאן שלבים, יחסים, pH, זמן, חום או חיי מדף.', 'summary_en' => 'Private documentation only that the source describes cooking followed by jar preservation in a pickle-like context. It supplies no steps, ratios, pH, time, heat or shelf life.',
		'fact_he' => 'הדמיון הלשוני לחמוצים אינו מוכיח החמצה, עיקור, יציבות או בטיחות.', 'fact_en' => 'Linguistic similarity to pickles does not establish acidification, sterilization, stability or safety.',
		'science_he' => 'זהות הצמח, הסרת גורמי הגירוי, pH שיווי משקל, תהליך תרמי, אטימה ואחסון אינם מאומתים.', 'science_en' => 'Plant identity, irritant removal, equilibrium pH, thermal process, closure and storage are unvalidated.', 'science_sources' => array( 'arum-eurasia-review-2025', 'pubmed-arum-palaestinum-poisoning-2020', 'fda-acidified-low-acid-foods' ), 'science_evidence' => 'regulatory_standard',
		'compliance_code' => 's9-arum-fail-closed', 'risk_he' => 'HELD ו-FAIL CLOSED: אין לשחזר, לבדוק במטבח, לפרסם או ליישם את התהליך לפני זיהוי בוטני וולידציה מקצועית מלאה.', 'risk_en' => 'HELD AND FAIL CLOSED: do not reconstruct, kitchen-test, publish or apply this process before botanical identification and complete professional validation.', 'risk_sources' => array( 'arum-eurasia-review-2025', 'pubmed-arum-palaestinum-poisoning-2020', 'fda-acidified-low-acid-foods' ),
		'references' => array(),
		'prompt_en' => 'Private fail-closed preservation review scene with an empty locked glass jar inside a laboratory containment tray, no plant material, no liquid, no recipe cues, stark controlled lighting, no instructional text.',
	),
	array(
		'id' => 'tradition-qadmus-eastern-new-year-pastry', 'type' => 'tradition', 'slug' => 'qadmus-eastern-new-year-pastry', 'parent_id' => 'region-syria-qadmus-mountains', 'region' => 'qadmus-mountains',
		'name_he' => 'מסורת מאפה ראש השנה המזרחי בקדמוס', 'name_en' => 'Qadmus Eastern New Year Pastry Tradition', 'sources' => array( 'ifrepo-qadmus-foodways' ),
		'summary_he' => 'מסורת מתועדת המקשרת את מאפה מילאדי לציון ראש השנה המזרחי בקדמוס.', 'summary_en' => 'A documented tradition linking Milady pastry with Eastern New Year observance in Qadmus.',
		'fact_he' => 'הקישור נשמר כהקשר מקומי במקור ואינו מוצג כמנהג אחיד של כל הקהילות באזור.', 'fact_en' => 'The connection remains a local source context and is not presented as a uniform practice of every community in the region.',
		'science_he' => 'המקור התרבותי אינו מספק הרכב תזונתי, טמפרטורת אפייה, בטיחות מזון או מפרט אלרגנים.', 'science_en' => 'The cultural source supplies no nutritional composition, baking temperature, food-safety validation or allergen specification.', 'science_sources' => array( 'ifrepo-qadmus-foodways' ),
		'risk_he' => 'אין להרחיב את העדות לטענה דתית כוללת או לפרסם נוסחת מאפה ללא מקור ובדיקה.', 'risk_en' => 'Do not expand the evidence into a broad religious claim or publish a pastry formula without evidence and testing.', 'risk_sources' => array( 'ifrepo-qadmus-foodways' ),
		'references' => array( 'dish-milady-pastry-qadmus' ),
		'prompt_en' => 'Private seasonal tradition still life with a small Milady pastry study, winter greenery kept abstract and an empty calendar space, no religious symbols, no people, quiet mountain-window light.',
	),
	array(
		'id' => 'region-syria-kassab-armenian', 'type' => 'topic_hub', 'slug' => 'kassab-armenian-foodways', 'parent_id' => 'region-syria-coast', 'region' => 'kassab', 'community' => 'syrian-armenian',
		'name_he' => 'מטבח כסאב הסורי-ארמני', 'name_en' => 'Syrian Armenian Foodways of Kassab', 'sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'summary_he' => 'שער מחקר פרטי למסורות הסוריות-ארמניות של כסאב, ובו הריסה של חג העלייה, בישול ממושך ומתוק מולסת ענבים.', 'summary_en' => 'A private research gateway to Syrian Armenian Kassab foodways, including Assumption hareesa, long cooking and a grape-molasses sweet.',
		'fact_he' => 'המקור מתעד מסורות קהילתיות ומקומיות ואינו קובע שהן מייצגות את כל הארמנים בסוריה או את כל תושבי כסאב.', 'fact_en' => 'The source documents local community foodways and does not establish that they represent every Armenian in Syria or every Kassab resident.',
		'science_he' => 'המקור התרבותי אינו מספק מדידות הרכב, תשואה, טמפרטורה, אלרגנים או חיי מדף לכל ישות.', 'science_en' => 'The cultural source supplies no composition, yield, temperature, allergen or shelf-life measurements for every entity.', 'science_sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'risk_he' => 'כל ישות נשמרת כעדות מקור פרטית עד אימות קולינרי, בטיחותי ולשוני נפרד.', 'risk_en' => 'Every entity remains a private source record until separate culinary, safety and language verification.', 'risk_sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'references' => array( 'tradition-kassab-assumption-day-hareesa', 'dish-hareesa-kassab-syrian-armenian', 'dish-grape-molasses-sweet-kassab' ),
		'prompt_en' => 'Private Kassab Syrian Armenian culinary atlas with three separated studies for hareesa, a long-cook vessel and a grape-molasses sweet, forested mountain light, no flags, costumes or religious props.',
	),
	array(
		'id' => 'tradition-kassab-assumption-day-hareesa', 'type' => 'tradition', 'slug' => 'kassab-assumption-day-hareesa', 'parent_id' => 'region-syria-kassab-armenian', 'region' => 'kassab', 'community' => 'syrian-armenian',
		'name_he' => 'הריסה בחג העלייה בכסאב', 'name_en' => 'Assumption Day Hareesa in Kassab', 'sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'summary_he' => 'מסורת מקומית מתועדת המקשרת הכנת הריסה לחג העלייה בקהילה הסורית-ארמנית בכסאב.', 'summary_en' => 'A documented local tradition connecting hareesa preparation with Assumption Day in the Syrian Armenian community of Kassab.',
		'fact_he' => 'הקישור החגיגי נשמר כהקשר קהילתי ממקור אחד ואינו טענה לכל הקהילות הארמניות.', 'fact_en' => 'The festive connection remains a community context from one source and is not a claim about every Armenian community.',
		'science_he' => 'המקור אינו מודד יחס חיטה לנוזל, זמן, טמפרטורה, בטיחות עוף או תנאי החזקה.', 'science_en' => 'The source does not measure wheat-to-liquid ratio, time, temperature, poultry safety or holding conditions.', 'science_sources' => array( 'ifrepo-kassab-armenian-foodways', 'foodsafety-safe-temperatures' ),
		'risk_he' => 'אין להציג הוראת הכנה חגיגית או נוהל הגשה בלי ניסוי ובקרת בטיחות נפרדים.', 'risk_en' => 'Do not present festive preparation instructions or a serving procedure without separate testing and safety control.', 'risk_sources' => array( 'ifrepo-kassab-armenian-foodways', 'foodsafety-safe-temperatures' ),
		'references' => array( 'dish-hareesa-kassab-syrian-armenian' ),
		'prompt_en' => 'Private Kassab Assumption tradition table with one covered hareesa vessel and a separate empty communal-serving space, no ceremony, clergy, icons or crowd, gentle late-summer light.',
	),
	array(
		'id' => 'dish-hareesa-kassab-syrian-armenian', 'type' => 'dish', 'slug' => 'hareesa-kassab-syrian-armenian', 'parent_id' => 'region-syria-kassab-armenian', 'region' => 'kassab', 'community' => 'syrian-armenian',
		'name_he' => 'הריסה סורית-ארמנית מכסאב', 'name_en' => 'Syrian Armenian Hareesa from Kassab', 'sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'summary_he' => 'מנה מתועדת של חיטה עם עוף או בשר ושומן מזוכך, המזוהה בהקשר כסאב עם בישול ממושך.', 'summary_en' => 'A documented wheat dish with chicken or meat and clarified fat, associated in the Kassab context with prolonged cooking.',
		'fact_he' => 'המקור תומך במשפחת הרכיבים ובמבנה הבישול הארוך, אך אינו קובע מפרט חומרי גלם או נוסחה מדודה.', 'fact_en' => 'The source supports the ingredient family and long-cook structure but does not establish ingredient specifications or a measured formula.',
		'science_he' => 'ג׳לטיניזציית עמילן ופירוק רקמות תלויים בזמן, חום וגזירה שלא נמדדו; עוף דורש הפרדה ובישול מאומת.', 'science_en' => 'Starch gelatinization and tissue breakdown depend on unmeasured time, heat and shear; poultry requires separation and validated cooking.', 'science_sources' => array( 'ifrepo-kassab-armenian-foodways', 'foodsafety-safe-temperatures', 'usda-no-wash-poultry' ),
		'compliance_code' => 'poultry-separation-no-rinsing', 'risk_he' => 'אין לשטוף עוף נא. יש למנוע זיהום צולב ולבשל לפי הנחיית טמפרטורה מאומתת בלי לפרסם זמן אוניברסלי.', 'risk_en' => 'Do not rinse raw poultry. Prevent cross-contamination and cook to validated temperature guidance without publishing a universal time.', 'risk_sources' => array( 'usda-no-wash-poultry', 'foodsafety-safe-temperatures' ),
		'references' => array( 'technique-kassab-hareesa-long-cook', 'tradition-kassab-assumption-day-hareesa' ),
		'compliance' => array(
			array( 'red-meat-source-cold-chain-and-thermal-validation', 'כאשר הגרסה משתמשת בבשר אדום, נדרשים מין ונתח, מקור ואצווה מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי למנה בפועל.', 'When the version uses red meat, identified species and cut, source and lot, cold chain, cross-contamination controls and dish-specific thermal validation are required.', array( 'ifrepo-kassab-armenian-foodways', 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ) ),
			array( 'wheat-gluten-and-clarified-fat-allergen-validation', 'יש לאמת את מפרט החיטה והשומן המזוכך, להצהיר אלרגן גלוטן וכל אלרגן חלב לפי המוצר, למנוע מגע צולב ולאמת אחסון והחזקה.', 'Verify wheat and clarified-fat specifications, declare gluten and any product-specific milk allergen, prevent cross-contact, and validate storage and holding.', array( 'ifrepo-kassab-armenian-foodways', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Private fully cooked Kassab hareesa identity study in a plain deep bowl with a smooth wheat-and-meat texture, gentle steam, no raw poultry, no rinsing scene, no festive garnish, neutral tableware.',
	),
	array(
		'id' => 'dish-grape-molasses-sweet-kassab', 'type' => 'dish', 'slug' => 'grape-molasses-sweet-kassab', 'parent_id' => 'region-syria-kassab-armenian', 'region' => 'kassab', 'community' => 'syrian-armenian',
		'name_he' => 'מתוק מולסת ענבים מכסאב', 'name_en' => 'Kassab Grape Molasses Sweet', 'sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'summary_he' => 'מתוק קהילתי מתועד המבוסס במקור על קמח, שמן ומולסת ענבים, בלי יחס רכיבים או שם מוצר מסחרי.', 'summary_en' => 'A documented community sweet based in the source on flour, oil and grape molasses, without ingredient ratios or a commercial product identity.',
		'fact_he' => 'המקור תומך בשלושת רכיבי היסוד ובהקשר כסאב בלבד.', 'fact_en' => 'The source supports only the three core components and the Kassab context.',
		'science_he' => 'ריכוז סוכר, Brix, pH, סוג קמח, סוג שמן, טמפרטורה ומרקם סופי אינם נמדדים.', 'science_en' => 'Sugar concentration, Brix, pH, flour type, oil type, temperature and final texture are not measured.', 'science_sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'risk_he' => 'אין להציג הרכב תזונתי, יציבות או נוסחה לפני אפיון חומרי גלם וניסוי.', 'risk_en' => 'Do not present nutritional composition, stability or a formula before ingredient characterization and testing.', 'risk_sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'references' => array(),
		'compliance' => array(
			array( 'flour-gluten-allergen-and-product-validation', 'יש לאמת את סוג הקמח, אלרגן הגלוטן, מגע צולב, זהות השמן והמולסה, תהליך החום, קירור, אחסון וחיי מדף לפני פיתוח מתכון או מוצר.', 'Verify flour type, gluten allergen, cross-contact, oil and molasses identity, heat process, cooling, storage and shelf life before recipe or product development.', array( 'ifrepo-kassab-armenian-foodways', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Private Kassab sweet research plate with one restrained dark grape-molasses confection sample beside separate flour, oil and molasses reference vessels, no proportions, no package, soft forest-window light.',
	),
	array(
		'id' => 'technique-kassab-hareesa-long-cook', 'type' => 'technique', 'slug' => 'kassab-hareesa-long-cook', 'parent_id' => 'dish-hareesa-kassab-syrian-armenian', 'region' => 'kassab', 'community' => 'syrian-armenian',
		'name_he' => 'בישול ממושך של הריסה מכסאב', 'name_en' => 'Long Cooking of Kassab Hareesa', 'sources' => array( 'ifrepo-kassab-armenian-foodways' ),
		'summary_he' => 'רשומת שיטה המתעדת בישול על אש נמוכה, ערבוב בשלב מאוחר והסרת עצמות, בלי להפוך את התיאור לפרוטוקול.', 'summary_en' => 'A method record documenting low-heat cooking, later stirring and bone removal without converting the description into a protocol.',
		'fact_he' => 'המקור מתאר רצף עבודה כללי ואינו מספק ערכי זמן, חום, יחס נוזל או קריטריון סיום.', 'fact_en' => 'The source describes a general work sequence and supplies no time, heat, liquid-ratio or endpoint values.',
		'science_he' => 'מרקם ובטיחות תלויים בחדירת חום, ג׳לטיניזציה, גזירה ופירוק עצמות שאינם מאומתים במספרים.', 'science_en' => 'Texture and safety depend on heat penetration, gelatinization, shear and bone handling that are not numerically validated.', 'science_sources' => array( 'ifrepo-kassab-armenian-foodways', 'foodsafety-safe-temperatures', 'usda-no-wash-poultry' ),
		'compliance_code' => 'poultry-long-cook-validation', 'risk_he' => 'אין לשטוף עוף נא ואין לפרסם זמן בישול קבוע. יש למנוע זיהום צולב ולאמת טמפרטורת ליבה והסרת עצמות.', 'risk_en' => 'Do not rinse raw poultry and do not publish a fixed cooking time. Prevent cross-contamination and validate core temperature and bone removal.', 'risk_sources' => array( 'usda-no-wash-poultry', 'foodsafety-safe-temperatures' ),
		'references' => array(),
		'compliance' => array(
			array( 'red-meat-long-cook-and-bone-removal-validation', 'גרסת בשר אדום דורשת מקור ואצווה מזוהים, שרשרת קירור, חדירת חום, קריטריון סיום, הסרת עצמות והחזקה שנבדקו למנה בפועל.', 'A red-meat version requires identified source and lot, cold chain, heat penetration, endpoint, bone removal and holding validated for the actual dish.', array( 'ifrepo-kassab-armenian-foodways', 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ) ),
		),
		'prompt_en' => 'Private professional long-cook process study with a covered heavy pot, a separate late-stage stirring utensil and a clean bone-removal verification tray, fully cooked food only, no clocks, no temperature numbers.',
	),
	array(
		'id' => 'dish-kaak-bi-haleeb-baniyas', 'type' => 'dish', 'slug' => 'kaak-bi-haleeb-baniyas', 'parent_id' => 'region-syria-baniyas', 'region' => 'baniyas',
		'name_he' => 'כעכ בחלב מבניאס, זהות בבירור', 'name_en' => 'Kaak bi Haleeb from Baniyas, Identity Under Review', 'sources' => array( 'avs-zainab-coast' ),
		'summary_he' => 'שם מנה המופיע בעדות זינב מן החוף. הנוסחה, המרכיבים והצורה אינם מאומתים ולכן הישות נשארת יעד מחקר פרטי.', 'summary_en' => 'A dish name appearing in Zainab\'s coastal testimony. Its formula, components and form are unverified, so the entity remains a private research target.',
		'fact_he' => 'המקור תומך בשם ובהקשר בניאס בלבד; אין להסיק מן השם שהמוצר מכיל חלב מסוים או נראה בצורה מסוימת.', 'fact_en' => 'The source supports only the name and Baniyas context; the name does not establish a particular milk ingredient or visual form.',
		'science_he' => 'אין נתונים על אלרגן חלב, גלוטן, תסיסה, לחות, אפייה, pH או חיי מדף.', 'science_en' => 'There are no data for milk allergen, gluten, fermentation, moisture, baking, pH or shelf life.', 'science_sources' => array( 'avs-zainab-coast' ),
		'compliance_code' => 'identity-unresolved-no-reconstruction', 'risk_he' => 'אין לייצר מתכון, רשימת רכיבים או תמונת מנה משוחזרת עד מקור זהות נוסף.', 'risk_en' => 'Do not create a recipe, ingredient list or reconstructed dish image until added identity evidence exists.', 'risk_sources' => array( 'avs-zainab-coast' ),
		'references' => array(),
		'prompt_en' => 'Private Baniyas identity-research board with an empty neutral plate, one closed plain notebook and an unfilled ingredient grid, coastal daylight, no reconstructed food, no ingredients, no text.',
	),
	array(
		'id' => 'dish-kaak-bi-sumac-jableh', 'type' => 'dish', 'slug' => 'kaak-bi-sumac-jableh', 'parent_id' => 'region-syria-jableh', 'region' => 'jableh',
		'name_he' => 'כעכ בסומאק מג׳בלה, זהות בבירור', 'name_en' => 'Kaak bi Sumac from Jableh, Identity Under Review', 'sources' => array( 'avs-zainab-coast' ),
		'summary_he' => 'שם מנה המופיע בעדות החופית של זינב. מעבר לשם ולהקשר ג׳בלה, אין מקור מספיק לנוסחה או לצורה.', 'summary_en' => 'A dish name appearing in Zainab\'s coastal testimony. Beyond its name and Jableh context, the source is insufficient for a formula or form.',
		'fact_he' => 'השם אינו מוכיח מין סומאק, כמות, מצב טחון, סוג בצק או מבנה מנה.', 'fact_en' => 'The name does not establish sumac species, quantity, ground state, dough type or dish structure.',
		'science_he' => 'אין נתוני חומציות, מלח, אלרגנים, תסיסה, אפייה, פעילות מים או יציבות.', 'science_en' => 'There are no acidity, salt, allergen, fermentation, baking, water-activity or stability data.', 'science_sources' => array( 'avs-zainab-coast' ),
		'compliance_code' => 'identity-unresolved-no-reconstruction', 'risk_he' => 'אין להוסיף רכיבים, מתכון או תמונת מנה מוגמרת על סמך השם בלבד.', 'risk_en' => 'Do not add ingredients, a recipe or a finished-dish image from the name alone.', 'risk_sources' => array( 'avs-zainab-coast' ),
		'references' => array(),
		'prompt_en' => 'Private Jableh culinary identity board with an empty dark ceramic plate, a sealed unlabeled specimen envelope and a blank morphology grid, sea-muted light, no reconstructed dish, no ingredients, no sumac shown.',
	),
	array(
		'id' => 'dish-jazariyeh-jableh', 'type' => 'dish', 'slug' => 'jazariyeh-jableh', 'parent_id' => 'region-syria-jableh', 'region' => 'jableh',
		'name_he' => 'ג׳זרייה מג׳בלה, זהות בבירור', 'name_en' => 'Jazariyeh from Jableh, Identity Under Review', 'sources' => array( 'avs-zainab-coast' ),
		'summary_he' => 'שם מנה נוסף בעדות זינב מג׳בלה, הנשמר כישות עצמאית כדי שלא למזגו עם מנות בעלות שם דומה בלי ראיה.', 'summary_en' => 'Another dish name in Zainab\'s Jableh testimony, retained as an independent entity to avoid merging it with similarly named foods without evidence.',
		'fact_he' => 'המקור מאמת את השם וההקשר המקומי בלבד ואינו מספק פירוק רכיבים או תיאור חזותי.', 'fact_en' => 'The source verifies only the name and local context and supplies no component breakdown or visual description.',
		'science_he' => 'זהות חומרי הגלם, תגובות בישול, pH, Brix, אלרגנים, טמפרטורה וחיי מדף אינם ידועים.', 'science_en' => 'Ingredient identity, cooking reactions, pH, Brix, allergens, temperature and shelf life are unknown.', 'science_sources' => array( 'avs-zainab-coast' ),
		'compliance_code' => 'identity-unresolved-no-reconstruction', 'risk_he' => 'אין לשחזר, להציג או למכור מנה מן השם בלבד; נדרש מקור זהות נוסף.', 'risk_en' => 'Do not reconstruct, depict or sell a dish from the name alone; added identity evidence is required.', 'risk_sources' => array( 'avs-zainab-coast' ),
		'references' => array(),
		'prompt_en' => 'Private Jableh unresolved-entity board with an empty clear-glass plate, three blank evidence cards and a neutral coastal-stone background, no food reconstruction, no ingredient clues, no writing.',
	),
);

$c99_syrian_west_entities = array();
foreach ( $c99_syrian_west_rows as $spec ) {
	$c99_syrian_west_entities[] = $c99_syrian_west_build( $spec );
}

$c99_syrian_west_expected_counts = array(
	'guide' => 2,
	'technique' => 7,
	'ingredient' => 6,
	'tradition' => 4,
	'dish' => 9,
	'topic_hub' => 2,
);
$c99_syrian_west_counts = array_count_values( array_column( $c99_syrian_west_entities, 'type' ) );
if ( 30 !== count( $c99_syrian_west_entities ) || $c99_syrian_west_expected_counts !== $c99_syrian_west_counts ) {
	throw new RuntimeException( 'Syrian west expansion must contain exactly 30 entities with the approved type distribution.' );
}

$c99_syrian_west_ids = array_column( $c99_syrian_west_entities, 'id' );
if ( count( $c99_syrian_west_ids ) !== count( array_unique( $c99_syrian_west_ids ) ) ) {
	throw new RuntimeException( 'Duplicate Syrian west entity ID.' );
}

$c99_syrian_west_prompts = array();
foreach ( $c99_syrian_west_entities as $entity ) {
	if ( empty( $entity['name']['he'] ) || empty( $entity['name']['en'] ) || empty( $entity['summary']['he'] ) || empty( $entity['summary']['en'] ) || empty( $entity['visual']['prompt_en'] ) ) {
		throw new RuntimeException( 'Incomplete bilingual Syrian west entity: ' . $entity['id'] );
	}
	if ( isset( $c99_syrian_west_prompts[ $entity['visual']['prompt_en'] ] ) ) {
		throw new RuntimeException( 'Duplicate Syrian west visual prompt: ' . $entity['id'] );
	}
	$c99_syrian_west_prompts[ $entity['visual']['prompt_en'] ] = true;
	foreach ( array_merge( $entity['facts'], $entity['relations'], $entity['compliance'] ) as $claim ) {
		foreach ( $claim['source_ids'] as $source_id ) {
			if ( ! isset( $c99_syrian_west_known_sources[ $source_id ] ) ) {
				throw new RuntimeException( 'Unknown Syrian west source: ' . $source_id );
			}
		}
	}
}

return array(
	'schema' => 'complete99-syrian-regional-expansion-west/v1',
	'version' => 'culinary-science-2026.08.07.v18',
	'sources' => $c99_syrian_west_sources,
	'entities' => $c99_syrian_west_entities,
	'private_entity_ids' => $c99_syrian_west_ids,
	'counts' => array(
		'by_type' => $c99_syrian_west_counts,
		'total_entities' => count( $c99_syrian_west_entities ),
	),
);
