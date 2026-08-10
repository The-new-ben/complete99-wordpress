<?php
/**
 * Complete99 Syrian eastern and southern regional research expansion.
 *
 * The records in this module are private editorial references. They do not
 * create recipes, offers, availability claims, preservation instructions or
 * public projections. Named testimony stays bounded to the named narrator or
 * source, and shared regional dishes never receive an exclusive-origin claim.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( array( 'c99_syrian_entity', 'c99_syrian_fact', 'c99_relation', 'c99_text', 'c99_compliance' ) as $c99_required_helper ) {
	if ( ! isset( ${$c99_required_helper} ) || ! is_callable( ${$c99_required_helper} ) ) {
		throw new RuntimeException( 'Syrian east and south expansion requires the existing Syrian entity helpers.' );
	}
}
if ( ! isset( $c99_syrian_sources ) || ! is_array( $c99_syrian_sources ) || ! isset( $c99_syrian_depth_sources ) || ! is_array( $c99_syrian_depth_sources ) ) {
	throw new RuntimeException( 'Syrian east and south expansion requires the foundation and regional-depth source ledgers.' );
}

$c99_syrian_east_south_sources = array(
	'avs-nabiha-palmyra' => array(
		'type' => 'official_organization',
		'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Nabiha\'s Story, Palmyra',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Nabihas-Story.pdf',
		'published_at' => '2025-03-01',
		'retrieved_at' => '2026-08-07',
	),
	'fda-food-code-2022' => array(
		'type' => 'regulatory_guidance',
		'publisher' => 'United States Food and Drug Administration',
		'title' => 'Food Code 2022',
		'url' => 'https://www.fda.gov/food/fda-food-code/food-code-2022',
		'published_at' => '2022-12-28',
		'retrieved_at' => '2026-08-07',
	),
	'who-five-keys-safer-food' => array(
		'type' => 'official_organization',
		'publisher' => 'World Health Organization',
		'title' => 'Five Keys to Safer Food Manual',
		'url' => 'https://www.who.int/publications/i/item/9789241594639',
		'published_at' => '2006-05-15',
		'retrieved_at' => '2026-08-07',
	),
	'who-natural-toxins-food' => array(
		'type' => 'official_organization',
		'publisher' => 'World Health Organization',
		'title' => 'Natural toxins in food',
		'url' => 'https://www.who.int/news-room/fact-sheets/detail/natural-toxins-in-food',
		'published_at' => '',
		'retrieved_at' => '2026-08-07',
	),
	'who-complementary-feeding-2023' => array(
		'type' => 'official_organization',
		'publisher' => 'World Health Organization',
		'title' => 'WHO guideline for complementary feeding of infants and young children 6-23 months of age',
		'url' => 'https://www.who.int/publications/i/item/9789240081864',
		'published_at' => '2023-10-16',
		'retrieved_at' => '2026-08-07',
	),
);

$c99_syrian_east_south_source_ledger = array_merge(
	$c99_syrian_sources,
	$c99_syrian_depth_sources,
	$c99_syrian_east_south_sources
);

$c99_syrian_east_south_build = static function ( $spec ) use ( $c99_syrian_entity, $c99_syrian_fact, $c99_relation, $c99_text, $c99_compliance ) {
	$facts = array(
		$c99_syrian_fact(
			'fact-' . $spec['slug'] . '-documented-scope',
			isset( $spec['dimension'] ) ? $spec['dimension'] : 'cultural',
			$spec['fact_he'],
			$spec['fact_en'],
			'official_source',
			'entity',
			$spec['sources']
		),
		$c99_syrian_fact(
			'fact-' . $spec['slug'] . '-science-safety-boundary',
			'scientific',
			$spec['boundary_he'],
			$spec['boundary_en'],
			'regulatory_standard',
			'technique_context',
			$spec['boundary_sources']
		),
	);

	$relations = array(
		$c99_relation(
			'part_of',
			$spec['parent_id'],
			'הישות נשמרת בתוך ההקשר האזורי או הקהילתי המתועד שלה.',
			'The entity remains within its documented regional or community context.',
			false,
			$spec['sources'],
			'official_source'
		),
	);
	foreach ( isset( $spec['relations'] ) ? $spec['relations'] : array() as $relation_spec ) {
		$relations[] = $c99_relation(
			$relation_spec[0],
			$relation_spec[1],
			$relation_spec[2],
			$relation_spec[3],
			false,
			isset( $relation_spec[4] ) ? $relation_spec[4] : $spec['sources'],
			isset( $relation_spec[5] ) ? $relation_spec[5] : 'official_source'
		);
	}

	$compliance = array();
	foreach ( $spec['compliance'] as $compliance_spec ) {
		$compliance[] = $c99_compliance(
			$compliance_spec[0],
			$compliance_spec[1],
			$compliance_spec[2],
			$compliance_spec[3],
			false
		);
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
		'primary_intent' => $c99_text(
			'ללמוד את ' . $spec['name_he'] . ' כרשומת מחקר תחומה לפני כל שימוש קולינרי.',
			'Learn about ' . $spec['name_en'] . ' as a bounded research record before any culinary use.'
		),
		'primary_keyword' => $c99_text( $spec['name_he'] . ' מחקר קולינרי', $spec['name_en'] . ' culinary research' ),
		'schema_type' => 'guide' === $spec['type'] || 'topic_hub' === $spec['type'] ? 'CollectionPage' : 'Article',
		'facts' => $facts,
		'relations' => $relations,
		'cross_sell_ids' => array(),
		'prompt_en' => $spec['prompt_en'],
		'compliance' => $compliance,
	) );

	foreach ( $entity['relations'] as $relation_offset => &$relation ) {
		$relation['id'] = 'edge-' . $entity['id'] . '-' . $relation['type'] . '-' . ( $relation_offset + 1 );
	}
	unset( $relation );

	if ( ! empty( $spec['held'] ) ) {
		$entity['trust']['commercial_purpose'] = $c99_text(
			'זהות זו מוחזקת במצב fail-closed. אין מתכון, תמונת מנה סופית, מיפוי מוצר או שימוש מסחרי עד השלמת ראיות זהות בלתי תלויות.',
			'This identity is held fail-closed. No recipe, finished-dish image, product mapping or commercial use is allowed until independent identity evidence is complete.'
		);
		$entity['trust']['next_review_trigger'] = $c99_text(
			'נדרשים מקור בלתי תלוי נוסף וביקורת דובר מקומי לפני שינוי מצב ההחזקה.',
			'A second independent source and local-speaker review are required before the held state can change.'
		);
	}

	return $entity;
};

$c99_syrian_east_south_specs = array(
	/* Qamishli and Jazira. */
	array(
		'id' => 'region-syria-qamishli-assyrian-foodways',
		'type' => 'topic_hub',
		'slug' => 'qamishli-assyrian-foodways',
		'parent_id' => 'region-syria-jazira',
		'name_he' => 'מסורות אוכל אשוריות בקמישלי ובג׳זירה',
		'name_en' => 'Assyrian Foodways in Qamishli and Jazira',
		'summary_he' => 'שער מחקר תחום לעדות אשורית על דיח׳ווה, אקיטו, שעורה קלופה ויוגורט בקמישלי. הוא אינו מייצג את כל תושבי העיר או את כל הקהילות האשוריות.',
		'summary_en' => 'A bounded research gateway for an Assyrian account of dikhwa, Akitu, peeled barley and yogurt in Qamishli. It does not represent every city resident or every Assyrian community.',
		'region' => 'syria-qamishli-jazira',
		'community' => 'assyrian-syriac-qamishli',
		'sources' => array( 'ifrepo-qamishli-assyrian-foodways' ),
		'fact_he' => 'פרק 2 של IFPO קושר מארחת אשורית מקמישלי, את חג אקיטו ואת מנת הדיח׳ווה בתוך עדות מזוהה אחת.',
		'fact_en' => 'IFPO chapter 2 links an Assyrian hostess from Qamishli, the Akitu celebration and dikhwa within one identified account.',
		'boundary_he' => 'העדות אינה מספקת מדגם עירוני, מפרט חומרי גלם או תוכנית בטיחות למנה. כל שימוש מעשי דורש אימות קהילה, מוצר ותהליך.',
		'boundary_en' => 'The account supplies neither a citywide sample nor an ingredient specification or food-safety plan. Any practical use requires community, product and process validation.',
		'boundary_sources' => array( 'ifrepo-qamishli-assyrian-foodways', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'contains', 'tradition-assyrian-akitu-qamishli', 'השער כולל את הקשר אקיטו התחום.', 'The gateway contains the bounded Akitu context.' ),
			array( 'contains', 'ingredient-peeled-barley-dikhwa', 'השער כולל רשומת שעורה קלופה נפרדת.', 'The gateway contains a separate peeled-barley record.' ),
			array( 'contains', 'technique-dikhwa-yogurt-stabilization', 'השער כולל גבול טכני לשלב היוגורט.', 'The gateway contains a technical boundary for the yogurt phase.' ),
		),
		'compliance' => array(
			array( 'qamishli-community-scope-boundary', 'אין להכליל מעדות מארחת אחת לכלל קמישלי או לכלל האשורים.', 'Do not generalize one hostess account to all of Qamishli or all Assyrians.', array( 'ifrepo-qamishli-assyrian-foodways' ) ),
		),
		'prompt_en' => 'Original editorial atlas of Qamishli Assyrian foodways with peeled barley, a cultured-dairy bowl and a fully cooked meat-barley study in three separated zones, no people, symbols, flags or citywide claim.',
	),
	array(
		'id' => 'tradition-assyrian-akitu-qamishli',
		'type' => 'tradition',
		'slug' => 'assyrian-akitu-qamishli',
		'parent_id' => 'region-syria-qamishli-assyrian-foodways',
		'name_he' => 'דיח׳ווה ואקיטו בעדות אשורית מקמישלי',
		'name_en' => 'Dikhwa and Akitu in a Qamishli Assyrian Account',
		'summary_he' => 'רשומת מסורת המתעדת הגשת דיח׳ווה באקיטו ובאירועים נוספים לפי מארחת אשורית אחת מקמישלי, בלי להפוך את העדות לכלל קהילתי אחיד.',
		'summary_en' => 'A tradition record documenting dikhwa at Akitu and other occasions according to one Assyrian hostess from Qamishli, without turning the account into a uniform community rule.',
		'region' => 'syria-qamishli-jazira',
		'community' => 'assyrian-syriac-qamishli',
		'sources' => array( 'ifrepo-qamishli-assyrian-foodways' ),
		'fact_he' => 'המקור מתאר את אקיטו כראש השנה האשורי ומקשר את הדיח׳ווה לאירוח החג של המארחת המתועדת.',
		'fact_en' => 'The source describes Akitu as the Assyrian New Year and connects dikhwa to the documented hostess account of the celebration.',
		'boundary_he' => 'הקשר חגיגי אינו מאשר מתכון, תפריט ציבורי או שימוש בסמל דתי. זהות קהילתית וזכויות ייצוג דורשות ביקורת.',
		'boundary_en' => 'Festival context does not authorize a recipe, public menu or religious symbolism. Community identity and representation rights require review.',
		'boundary_sources' => array( 'ifrepo-qamishli-assyrian-foodways', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'references', 'dish-dikhwa-qamishli-assyrian', 'המסורת מפנה לישות המנה הקיימת בלי לשכפל אותה.', 'The tradition references the existing dish entity without duplicating it.' ),
			array( 'references', 'ingredient-peeled-barley-dikhwa', 'השעורה מתועדת כרכיב הקשרי ולא כמפרט מוצר.', 'Barley is documented as a contextual component, not a product specification.' ),
			array( 'references', 'technique-dikhwa-yogurt-stabilization', 'שלב היוגורט נשמר כרשומת גבול טכנית נפרדת.', 'The yogurt phase remains a separate technical boundary record.' ),
		),
		'compliance' => array(
			array( 'akitu-community-representation-review', 'אין ליצור דימוי חג, נוסח דתי או טענת מנהג כלל-קהילתי ללא ביקורת נציגי קהילה.', 'Do not create festival imagery, religious wording or a community-wide practice claim without community review.', array( 'ifrepo-qamishli-assyrian-foodways' ) ),
		),
		'prompt_en' => 'Original documentary still life for an Akitu food-memory record with one covered dikhwa bowl, peeled barley and a blank provenance card area, no people, costumes, religious objects, lettering or invented ritual scene.',
	),
	array(
		'id' => 'ingredient-peeled-barley-dikhwa',
		'type' => 'ingredient',
		'slug' => 'peeled-barley-dikhwa',
		'parent_id' => 'region-syria-qamishli-assyrian-foodways',
		'name_he' => 'שעורה קלופה בהקשר דיח׳ווה',
		'name_en' => 'Peeled Barley in Dikhwa Context',
		'summary_he' => 'רשומת חומר גלם לשעורה קלופה הנזכרת בדיח׳ווה האשורית מקמישלי. היא אינה מזהה זן, דרגת קילוף, לחות, אצווה או מוצר מסחרי.',
		'summary_en' => 'An ingredient record for peeled barley named in Qamishli Assyrian dikhwa. It identifies no cultivar, pearling grade, moisture, lot or commercial product.',
		'region' => 'syria-qamishli-jazira',
		'community' => 'assyrian-syriac-qamishli',
		'sources' => array( 'ifrepo-qamishli-assyrian-foodways' ),
		'fact_he' => 'פרק IFPO מונה שעורה קלופה כרכיב בדיח׳ווה של המארחת האשורית מקמישלי.',
		'fact_en' => 'The IFPO chapter lists peeled barley as a component of the Qamishli Assyrian hostess account of dikhwa.',
		'boundary_he' => 'המקור אינו מספק זן, גודל גרגר, לחות, ניקיון, תנאי אחסון או אימות בישול. אין להמיר את האזכור למפרט רכש או להוראת השריה.',
		'boundary_en' => 'The source supplies no cultivar, grain size, moisture, cleanliness, storage condition or cooking validation. The mention must not become a purchase specification or soaking instruction.',
		'boundary_sources' => array( 'ifrepo-qamishli-assyrian-foodways', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'used_in', 'dish-dikhwa-qamishli-assyrian', 'השעורה מקושרת למנת הדיח׳ווה הקיימת.', 'The barley is linked to the existing dikhwa dish.' ),
			array( 'used_in', 'technique-dikhwa-yogurt-stabilization', 'השעורה היא חלק ממטריצת המנה אך אינה הוראת יחס.', 'The barley is part of the dish matrix but not a ratio instruction.' ),
		),
		'compliance' => array(
			array( 'peeled-barley-lot-and-process-validation', 'כל שימוש דורש זיהוי מוצר ואצווה, ניקיון, אלרגנים, אחסון ותהליך בישול מאומת.', 'Any use requires product and lot identity, cleanliness, allergen, storage and validated cooking controls.', array( 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Macro editorial ingredient study of clean peeled barley kernels in an unbranded shallow tray beside an empty specification card, neutral daylight, no package, measurement, commercial provenance or soaking cue.',
	),
	array(
		'id' => 'technique-dikhwa-yogurt-stabilization',
		'type' => 'technique',
		'slug' => 'dikhwa-yogurt-stabilization',
		'parent_id' => 'region-syria-qamishli-assyrian-foodways',
		'name_he' => 'גבול ייצוב היוגורט בדיח׳ווה',
		'name_en' => 'Dikhwa Yogurt Stabilization Boundary',
		'summary_he' => 'מפת תהליך פרטית לשלב יוגורט חמוץ ועמילן המתואר בדיח׳ווה. היא אינה מפרסמת יחס, זמן, טמפרטורה או הוראת שירות.',
		'summary_en' => 'A private process map for the sour-yogurt and starch phase described for dikhwa. It publishes no ratio, time, temperature or service instruction.',
		'region' => 'syria-qamishli-jazira',
		'community' => 'assyrian-syriac-qamishli',
		'sources' => array( 'ifrepo-qamishli-assyrian-foodways' ),
		'fact_he' => 'המקור מתאר שלב יוגורט עם עמילן וערבוב בתוך מנת שעורה ובשר, אך הרשומה שומרת רק את עקרון הזהות.',
		'fact_en' => 'The source describes a yogurt-and-starch phase in a barley and meat dish, while this record retains only the identity-level principle.',
		'boundary_he' => 'אין עקומת חום, pH, זהות תרבית, פסטור, זמן החזקה או חיי מדף מאומתים. מונח המקור black spice seeds נשאר בלתי מזוהה ואין לייחס לו זהות בוטנית או אלרגנית.',
		'boundary_en' => 'No validated heat curve, pH, culture identity, pasteurization, holding time or shelf life is available. The source term black spice seeds remains unidentified and receives no botanical or allergen identity.',
		'boundary_sources' => array( 'ifrepo-qamishli-assyrian-foodways', 'fda-food-code-2022', 'foodsafety-safe-temperatures' ),
		'relations' => array(
			array( 'used_in', 'dish-dikhwa-qamishli-assyrian', 'הטכניקה מקושרת לדיח׳ווה בלי להפוך לרצפטורה.', 'The technique is linked to dikhwa without becoming a formula.' ),
			array( 'requires', 'ingredient-peeled-barley-dikhwa', 'השעורה היא חלק מן ההקשר המתועד, ללא יחס תפעולי.', 'Barley is part of the documented context without an operating ratio.' ),
			array( 'requires', 'ingredient-syrian-fresh-yogurt', 'זהות היוגורט בפועל דורשת מוצר מאומת ופסטור.', 'Actual yogurt identity requires a verified product and pasteurization.' ),
		),
		'compliance' => array(
			array( 'meat-source-cold-chain-and-thermal-validation', 'הבשר במטריצת הדיח׳ווה דורש מקור ואצווה מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי למנה בפועל.', 'Meat in the dikhwa matrix requires identified source and lot, cold chain, cross-contamination controls and dish-specific thermal validation.', array( 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ) ),
			array( 'dairy-pasteurization-allergen-and-time-temperature', 'אין להפעיל את השלב ללא מוצר חלב מפוסטר ומאומת, בקרת אלרגנים ותוכנית זמן-טמפרטורה שנבדקה.', 'Do not operate the phase without verified pasteurized dairy, allergen controls and a validated time-temperature plan.', array( 'fda-food-code-2022', 'foodsafety-safe-temperatures' ) ),
			array( 'unidentified-black-spice-seed-term', 'אין לזהות את מונח הזרעים השחורים לפי השערה, תמונה או שם דומה.', 'Do not identify the black seed term by guesswork, an image or a similar name.', array( 'ifrepo-qamishli-assyrian-foodways' ) ),
		),
		'prompt_en' => 'Private culinary-science scene of a smooth cultured-dairy phase beside peeled barley and a covered fully cooked meat component, calibrated tools without readable numbers, no spice-seed depiction and no recipe layout.',
	),

	/* Al-Bukamal and Deir ez-Zor. */
	array(
		'id' => 'region-syria-al-bukamal',
		'type' => 'topic_hub',
		'slug' => 'al-bukamal-foodways',
		'parent_id' => 'region-syria-deir-ez-zor',
		'name_he' => 'מטבח אל-בוכמאל',
		'name_en' => 'Al-Bukamal Foodways',
		'summary_he' => 'שער מחקר לעיר אל-בוכמאל שעל הפרת ולעדות של אום כמאל, עם שילוב מקומי בין הקשרים בדואיים ועירוניים, לחמי בית וירקות אזוריים.',
		'summary_en' => 'A research gateway for the Euphrates city of Al-Bukamal and Om Kamal\'s account, with a local combination of Bedouin and urban contexts, home breads and regional vegetables.',
		'region' => 'syria-al-bukamal-deir-ez-zor',
		'sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east' ),
		'fact_he' => 'פרק 6 של IFPO ממקם את אל-בוכמאל לאורך הפרת ומתאר את מטבח האזור כתערובת של רכיבים והקשרים בדואיים ועירוניים.',
		'fact_en' => 'IFPO chapter 6 places Al-Bukamal along the Euphrates and describes the regional cuisine as combining Bedouin and urban ingredients and contexts.',
		'boundary_he' => 'המקורות הם עדויות מחקריות תחומות ואינם מוכיחים נוסחה עירונית אחידה, מקור בלעדי או תנאי תפעול בטוחים.',
		'boundary_en' => 'The sources are bounded research accounts and establish neither one citywide formula, exclusive origin nor safe operating conditions.',
		'boundary_sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'contains', 'tradition-al-bukamal-bedouin-urban-foodways', 'השער כולל את מסגרת השילוב הבדואי-עירוני.', 'The gateway contains the Bedouin-urban context.' ),
			array( 'contains', 'technique-deir-bukamal-home-saj-tannour', 'השער כולל את הקשר אפיית הלחם הביתית.', 'The gateway contains the home-bread context.' ),
		),
		'compliance' => array(
			array( 'al-bukamal-testimony-scope-boundary', 'אין להפוך את עדות אום כמאל לכלל של העיר או לטענת מוצא בלעדית.', 'Do not turn Om Kamal\'s account into a citywide rule or exclusive-origin claim.', array( 'ifrepo-deir-ez-zor-foodways' ) ),
		),
		'prompt_en' => 'Original Al-Bukamal culinary atlas with home saj bread, okra, eggplant and wheat shown as separate research specimens beside an Euphrates-toned ceramic surface, no map, tribe cue, flag or origin claim.',
	),
	array(
		'id' => 'tradition-al-bukamal-bedouin-urban-foodways',
		'type' => 'tradition',
		'slug' => 'al-bukamal-bedouin-urban-foodways',
		'parent_id' => 'region-syria-al-bukamal',
		'name_he' => 'השילוב הבדואי-עירוני במטבח אל-בוכמאל',
		'name_en' => 'Bedouin-Urban Foodways in Al-Bukamal',
		'summary_he' => 'מסגרת תרבותית לשילוב המתועד בין משאבי הפרת, מסורות כפריות ובדואיות ומרחב עירוני באל-בוכמאל ובדיר א-זור.',
		'summary_en' => 'A cultural frame for the documented combination of Euphrates resources, rural and Bedouin traditions, and urban life in Al-Bukamal and Deir ez-Zor.',
		'region' => 'syria-al-bukamal-deir-ez-zor',
		'sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east' ),
		'fact_he' => 'IFPO מתאר תערובת אזורית בין תרבות בדואית לעירונית, ועדות Agricultural Voices מפרידה בין זיכרונות דיר א-זור ותדמור של מספרת אחת.',
		'fact_en' => 'IFPO describes a regional Bedouin-urban mixture, while Agricultural Voices distinguishes one narrator\'s Deir ez-Zor and Palmyra memories.',
		'boundary_he' => 'המסגרת אינה טיפוס אתני קשיח ואינה קובעת שכל משק בית משתמש באותם מרכיבים, לחם או שיטת אש.',
		'boundary_en' => 'The frame is not a fixed ethnic typology and does not claim that every household uses the same ingredients, bread or fire method.',
		'boundary_sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'references', 'region-syria-deir-ez-zor', 'המסורת נשמרת בתוך מרחב דיר א-זור הרחב.', 'The tradition remains inside the wider Deir ez-Zor context.' ),
			array( 'references', 'technique-deir-bukamal-home-saj-tannour', 'לחם הבית הוא קשר מתועד ולא כלל מגדרי או עירוני.', 'Home bread is a documented link, not a gender or citywide rule.' ),
			array( 'references', 'ingredient-deir-ez-zor-okra-context', 'במיה נשמרת כרכיב אזורי נפרד.', 'Okra remains a separate regional ingredient record.' ),
		),
		'compliance' => array(
			array( 'bedouin-urban-nonexclusive-context', 'אין להשתמש במסגרת כדי לטעון בעלות קהילתית, מוצא בלעדי או אחידות של כל משקי הבית.', 'Do not use the frame to claim community ownership, exclusive origin or household uniformity.', array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east' ) ),
		),
		'prompt_en' => 'Original editorial comparison of an Al-Bukamal home bread station, local wheat and market vegetables arranged in balanced rural and urban zones, no people, tents, costumes, tribal signs or binary stereotype.',
	),
	array(
		'id' => 'technique-deir-bukamal-home-saj-tannour',
		'type' => 'technique',
		'slug' => 'deir-bukamal-home-saj-tannour',
		'parent_id' => 'region-syria-al-bukamal',
		'name_he' => 'אפיית סאג׳ ותנור ביתית בדיר א-זור ואל-בוכמאל',
		'name_en' => 'Home Saj and Tannour Bread in Deir ez-Zor and Al-Bukamal',
		'summary_he' => 'רשומת טכניקה המתעדת את חשיבות לחמי הסאג׳ והתנור הביתיים בעדות אל-בוכמאל ודיר א-זור, בלי הוראות בנייה, הדלקה או אפייה.',
		'summary_en' => 'A technique record documenting the importance of home saj and tannour breads in Al-Bukamal and Deir ez-Zor accounts, without construction, ignition or baking instructions.',
		'region' => 'syria-al-bukamal-deir-ez-zor',
		'sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east' ),
		'fact_he' => 'IFPO מבדיל בין תנור חמר לסאג׳ מתכתי ומתעד אפיית לחם ביתית מרכזית בעדות אום כמאל.',
		'fact_en' => 'IFPO distinguishes a clay tannour from a metal saj and documents home breadmaking as central in Om Kamal\'s account.',
		'boundary_he' => 'אין ברשומה מפרט קמח, הידרציה, דלק, אוורור, מרחק אש, טמפרטורת משטח או אימות עשן. אין לפרסם הוראות אש.',
		'boundary_en' => 'The record has no flour, hydration, fuel, ventilation, fire-distance, surface-temperature or smoke validation. No fire instructions may be published.',
		'boundary_sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east', 'who-five-keys-safer-food', 'foodsafety-safe-temperatures' ),
		'relations' => array(
			array( 'references', 'technique-syrian-saj-bread', 'הטכניקה מקשרת לישות הסאג׳ הסורית בלי לקבוע זהות תהליך מלאה.', 'The technique links to the Syrian saj entity without asserting full process identity.' ),
			array( 'references', 'technique-syrian-tannour-bread', 'הטכניקה מקשרת לישות התנור הסורית בלי הוראות הפעלה.', 'The technique links to the Syrian tannour entity without operating instructions.' ),
		),
		'compliance' => array(
			array( 'open-fire-fuel-ventilation-burn-and-thermal-control', 'כל עבודה עתידית דורשת ציוד מאושר, דלק מתאים, אוורור, מניעת כוויות ועשן ואימות תרמי מקצועי.', 'Any future operation requires approved equipment, suitable fuel, ventilation, burn and smoke controls, and professional thermal validation.', array( 'who-five-keys-safer-food', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Technical editorial still life of an unlit metal saj and an unlit clay tannour bread station with flour kept sealed and a visible ventilation hood, no flame, fuel, hands, dimensions or operating sequence.',
	),
	array(
		'id' => 'ingredient-deir-ez-zor-okra-context',
		'type' => 'ingredient',
		'slug' => 'deir-ez-zor-okra-context',
		'parent_id' => 'region-syria-deir-ez-zor',
		'name_he' => 'במיה בהקשר דיר א-זור',
		'name_en' => 'Okra in Deir ez-Zor Context',
		'summary_he' => 'רשומת חומר גלם לבמיה הגדלה באזור דיר א-זור ומשמשת טרייה בקיץ או מיובשת בחורף לפי עדות אחת, בלי מפרט זן או מוצר.',
		'summary_en' => 'An ingredient record for okra grown around Deir ez-Zor and used fresh in summer or dried in winter according to one account, without a cultivar or product specification.',
		'region' => 'syria-deir-ez-zor',
		'sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east' ),
		'fact_he' => 'IFPO מונה במיה בין הירקות הבולטים במחוז, ועדות בות׳יינה מבדילה בין במיה קיצית טרייה לבמיה מיובשת בחורף.',
		'fact_en' => 'IFPO lists okra among prominent provincial vegetables, and Buthaina\'s account distinguishes fresh summer okra from winter dried okra.',
		'boundary_he' => 'המקורות אינם מספקים זן, שאריות הדברה, איכות מים, לחות ייבוש, פעילות מים, אחסון או חיי מדף.',
		'boundary_en' => 'The sources supply no cultivar, pesticide-residue, irrigation-water, drying-moisture, water-activity, storage or shelf-life specification.',
		'boundary_sources' => array( 'ifrepo-deir-ez-zor-foodways', 'avs-buthaina-east', 'who-five-keys-safer-food', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'used_in', 'dish-thurud-bamiya-deir-ez-zor', 'הבמיה מקושרת לישות ת׳רוד הבמיה הקיימת.', 'The okra is linked to the existing okra-thareed entity.' ),
			array( 'references', 'technique-deir-okra-fresh-dried-seasonality', 'העונתיות נשמרת ברשומת טכניקה נפרדת.', 'Seasonality remains in a separate technique record.' ),
		),
		'compliance' => array(
			array( 'okra-produce-lot-and-drying-validation', 'שימוש דורש זיהוי אצווה, היגיינת תוצרת ובדיקה נפרדת לכל מוצר מיובש; עדות עונתית אינה חיי מדף.', 'Use requires lot identity, produce hygiene and separate validation for any dried product; seasonal testimony is not shelf life.', array( 'who-five-keys-safer-food', 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Original seasonal ingredient comparison with fresh green okra in one clean tray and fully dried okra in a separate sealed research tray, tomatoes held apart, no storage duration, package, brand or health cue.',
	),
	array(
		'id' => 'technique-deir-okra-fresh-dried-seasonality',
		'type' => 'technique',
		'slug' => 'deir-okra-fresh-dried-seasonality',
		'parent_id' => 'ingredient-deir-ez-zor-okra-context',
		'name_he' => 'עונתיות במיה טרייה ומיובשת בדיר א-זור',
		'name_en' => 'Fresh and Dried Okra Seasonality in Deir ez-Zor',
		'summary_he' => 'מפת עונתיות המשמרת את ההבחנה העדותית בין במיה טרייה ועגבניות קיץ לבין במיה מיובשת וממרח עגבניות בחורף, ללא פרוטוקול ייבוש.',
		'summary_en' => 'A seasonality map retaining the account\'s distinction between fresh okra and summer tomatoes versus dried okra and tomato paste in winter, without a drying protocol.',
		'region' => 'syria-deir-ez-zor',
		'sources' => array( 'avs-buthaina-east' ),
		'fact_he' => 'עדות בות׳יינה מתארת העדפה לבמיה טרייה בקיץ ושימוש בגרסה מיובשת בחורף, עם הבדל בטעם ובהקשר העגבנייה.',
		'fact_en' => 'Buthaina\'s account describes a preference for fresh summer okra and use of a dried winter form, with a different flavor and tomato context.',
		'boundary_he' => 'העדפה עונתית אינה הוראת ייבוש או אחסון. אין נתוני aw, לחות, מיקרוביולוגיה, חרקים, אריזה או תוקף.',
		'boundary_en' => 'Seasonal preference is not a drying or storage instruction. No water activity, moisture, microbiology, insect, packaging or durability data are available.',
		'boundary_sources' => array( 'avs-buthaina-east', 'fda-water-activity', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'used_in', 'dish-thurud-bamiya-deir-ez-zor', 'העונתיות מקושרת למנה בלי לשנות את זהותה.', 'Seasonality links to the dish without changing its identity.' ),
			array( 'references', 'ingredient-deir-ez-zor-okra-context', 'הרשומה מפנה לחומר הגלם התחום.', 'The record references the bounded ingredient.' ),
		),
		'compliance' => array(
			array( 'dried-okra-water-activity-and-shelf-life-gate', 'אין לפרסם שיטת ייבוש, אחסון או חיי מדף עד אימות פעילות מים, לחות, אריזה ומיקרוביולוגיה.', 'Publish no drying, storage or shelf-life method until water activity, moisture, packaging and microbiology are validated.', array( 'fda-water-activity', 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Private food-science comparison of fresh and dried Deir ez-Zor okra under separate humidity-control domes with blank instrument displays, no sun-drying scene, timeline, shelf claim or recipe text.',
	),
	array(
		'id' => 'dish-mshahmiyya-deir-ez-zor',
		'type' => 'dish',
		'slug' => 'mshahmiyya-deir-ez-zor',
		'parent_id' => 'region-syria-deir-ez-zor',
		'name_he' => 'משחמייה מדיר א-זור',
		'name_en' => 'Mshahmiyya from Deir ez-Zor',
		'summary_he' => 'זהות מנה מתוך עדות דיר א-זור המתארת קמח ובשר טחון המשולבים בבצק ונאפים על סאג׳. הרשומה אינה נוסחה או הוראת אש.',
		'summary_en' => 'A dish identity from a Deir ez-Zor account describing flour and minced meat combined in a dough and cooked on a saj. The record is neither a formula nor a fire instruction.',
		'region' => 'syria-deir-ez-zor',
		'sources' => array( 'avs-buthaina-east' ),
		'fact_he' => 'בות׳יינה מציגה משחמייה כמנה מובחנת מדיר א-זור ומקשרת אותה לבצק, בשר טחון וסאג׳ המוסק בעץ.',
		'fact_en' => 'Buthaina identifies mshahmiyya as a distinct Deir ez-Zor dish and links it to dough, minced meat and a wood-fired saj.',
		'boundary_he' => 'אין יחס בשר-קמח, עובי, זיהוי מין בשר, שרשרת קירור, בקרת אש או טמפרטורת ליבה מאומתת.',
		'boundary_en' => 'No meat-to-flour ratio, thickness, meat-species identity, cold chain, fire control or validated core temperature is available.',
		'boundary_sources' => array( 'avs-buthaina-east', 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'requires', 'ingredient-syrian-red-meat', 'העדות מציינת בשר טחון ללא מין או מפרט אצווה.', 'The account names minced meat without species or lot specification.' ),
			array( 'references', 'technique-deir-bukamal-home-saj-tannour', 'הסאג׳ נשמר כטכניקת אש נפרדת.', 'The saj remains a separate fire-technique record.' ),
		),
		'compliance' => array(
			array( 'meat-source-cold-chain-and-thermal-validation', 'נדרשים מין ונתח מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי למוצר המדויק.', 'Identified species and cut, cold chain, cross-contamination controls and thermal validation are required for the exact product.', array( 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ) ),
			array( 'open-fire-fuel-ventilation-burn-and-thermal-control', 'אין להפעיל סאג׳ מוסק בעץ ללא ציוד, דלק, אוורור, עשן וכוויות תחת בקרה מקצועית.', 'Do not operate a wood-fired saj without professional equipment, fuel, ventilation, smoke and burn controls.', array( 'who-five-keys-safer-food', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Original fully cooked mshahmiyya research sample on a cool unlit saj, one cut edge showing a uniform cooked meat-and-dough matrix, thermometer nearby without readable numbers, no fire, raw meat or serving claim.',
	),
	array(
		'id' => 'preparation-muhammara-saj-deir-ez-zor',
		'type' => 'preparation',
		'slug' => 'muhammara-saj-deir-ez-zor',
		'parent_id' => 'region-syria-deir-ez-zor',
		'name_he' => 'מוחמרה על בצק סאג׳ בדיר א-זור',
		'name_en' => 'Muhammara on Saj Dough in Deir ez-Zor',
		'summary_he' => 'הכנה אזורית מתועדת של ממרח פלפל מתובל על בצק דק בסאג׳, הנשמרת בנפרד ממוחמרה כממרח ומגרסאות מאזורים אחרים.',
		'summary_en' => 'A documented regional preparation of spiced red-pepper spread on thin saj dough, kept separate from muhammara as a dip and from versions in other regions.',
		'region' => 'syria-deir-ez-zor',
		'sources' => array( 'avs-buthaina-east' ),
		'fact_he' => 'עדות בות׳יינה מתארת מוחמרה בדיר א-זור כממרח פלפל מתובל על בצק דק המבושל על סאג׳.',
		'fact_en' => 'Buthaina\'s account describes Deir ez-Zor muhammara as a spiced red-pepper spread on thin dough cooked on a saj.',
		'boundary_he' => 'המקור אינו מספק נוסחת ממרח, אלרגנים, עובי בצק, דלק, טמפרטורת משטח או זמן אפייה.',
		'boundary_en' => 'The source supplies no spread formula, allergen profile, dough thickness, fuel, surface temperature or baking time.',
		'boundary_sources' => array( 'avs-buthaina-east', 'fda-food-code-2022', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'requires', 'ingredient-syrian-red-pepper-paste', 'ממרח הפלפל הוא הקשר זהות בלבד, לא מפרט מוצר.', 'Red-pepper paste is an identity context, not a product specification.' ),
			array( 'references', 'dish-muhammara-syrian', 'הקישור משווה למשפחת המוחמרה בלי למזג את ההכנות.', 'The link compares with the muhammara family without merging preparations.' ),
			array( 'references', 'technique-deir-bukamal-home-saj-tannour', 'הסאג׳ נשמר ברשומת בטיחות נפרדת.', 'The saj remains in a separate safety record.' ),
		),
		'compliance' => array(
			array( 'muhammara-allergen-and-product-identity', 'אין להניח אגוזים, גלוטן או הרכב ממרח ללא תווית ומפרט בפועל.', 'Do not assume nuts, gluten or spread composition without the actual label and specification.', array( 'fda-food-code-2022' ) ),
			array( 'open-fire-fuel-ventilation-burn-and-thermal-control', 'כל שימוש בסאג׳ דורש בקרת ציוד, דלק, אוורור, כוויות ואימות אפייה.', 'Any saj use requires equipment, fuel, ventilation, burn controls and baking validation.', array( 'who-five-keys-safer-food', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Original Deir ez-Zor thin saj-dough study with a restrained red-pepper spread layer on a fully cooked sample, unlit metal surface and separated allergen-review markers without text, no flame or dip-bowl merge.',
	),
	array(
		'id' => 'dish-kileija-deir-ez-zor',
		'type' => 'dish',
		'slug' => 'kileija-deir-ez-zor',
		'parent_id' => 'region-syria-deir-ez-zor',
		'name_he' => 'קלייג׳ה מדיר א-זור',
		'name_en' => 'Deir ez-Zor Kileija',
		'summary_he' => 'עוגיית חג עבה וחמאתית בעדות דיר א-זור, לעיתים במילוי תמרים או אגוזים מתובלים ובדגמים ידניים המשתנים בין משפחות.',
		'summary_en' => 'A thick buttery festival biscuit in a Deir ez-Zor account, sometimes filled with dates or spiced nuts and marked by hand in patterns that vary among families.',
		'region' => 'syria-deir-ez-zor',
		'sources' => array( 'avs-buthaina-east' ),
		'fact_he' => 'העדות קושרת קלייג׳ה לעיד, להכנה משותפת ולדגמים משפחתיים, ומציינת מילויי תמרים או אגוזים כאפשרויות.',
		'fact_en' => 'The account links kileija to Eid, collective preparation and family patterns, and names date or nut fillings as possibilities.',
		'boundary_he' => 'אין נוסחת בצק, זהות שומן, אגוז, תבלין, מילוי, טמפרטורת אפייה או חיי מדף מאומתים.',
		'boundary_en' => 'No dough formula, fat, nut, spice or filling identity, baking validation or shelf life is available.',
		'boundary_sources' => array( 'avs-buthaina-east', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'references', 'tradition-deir-kileija-patterning-al-qashoush', 'הדגמים והכנת הלילה נשמרים כמסורת נפרדת.', 'Patterning and the night gathering remain a separate tradition.' ),
			array( 'references', 'ingredient-palmyra-date-palm-system', 'תמרים הם מילוי אפשרי בלבד והקישור אינו טענת מקור תדמורי.', 'Dates are only a possible filling and the link is not a Palmyrene-origin claim.' ),
		),
		'compliance' => array(
			array( 'kileija-allergen-and-baking-validation', 'כל גרסה דורשת נוסחה ותווית אלרגנים, זיהוי אגוז ומילוי ואימות אפייה ואחסון.', 'Each version requires a formula and allergen label, nut and filling identity, and validated baking and storage.', array( 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Original Deir ez-Zor kileija study with three fully baked biscuits carrying visibly different hand-tooled family patterns, one date-filled and one nut-filled sample kept apart, no mold, logo or universal pattern claim.',
	),
	array(
		'id' => 'tradition-deir-kileija-patterning-al-qashoush',
		'type' => 'tradition',
		'slug' => 'deir-kileija-patterning-al-qashoush',
		'parent_id' => 'dish-kileija-deir-ez-zor',
		'name_he' => 'דיגום קלייג׳ה ואל-קשוש בדיר א-זור',
		'name_en' => 'Kileija Patterning and Al-Qashoush in Deir ez-Zor',
		'summary_he' => 'מסורת משפחתית של דיגום ידני בכלים זמינים ושל התכנסות נשים בליל הכנת הקלייג׳ה, עם שירה ועידוד המכונים בעדות אל-קשוש.',
		'summary_en' => 'A family tradition of hand patterning with available tools and a women\'s night gathering for kileija preparation, with singing and encouragement called al-qashoush in the account.',
		'region' => 'syria-deir-ez-zor',
		'sources' => array( 'avs-buthaina-east' ),
		'fact_he' => 'בות׳יינה מתארת דגמים המאפשרים לה לזהות משקי בית ואת תפקיד החבובה בעידוד אל-קשוש בזמן ההכנה המשותפת.',
		'fact_en' => 'Buthaina describes household-recognizable patterns and the habbouba\'s encouraging role in al-qashoush during collective preparation.',
		'boundary_he' => 'עדות משפחתית אינה מעניקה תבנית עיצוב אוניברסלית, זכויות לשיר או רשות לשחזר אנשים, לבוש או טקס בתמונה.',
		'boundary_en' => 'Family testimony supplies no universal design template, song rights or permission to reconstruct people, clothing or a ritual scene in imagery.',
		'boundary_sources' => array( 'avs-buthaina-east', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'references', 'dish-kileija-deir-ez-zor', 'המסורת מקושרת לישות העוגייה בלי להכתיב נוסחה.', 'The tradition links to the biscuit entity without dictating a formula.' ),
		),
		'compliance' => array(
			array( 'al-qashoush-rights-and-family-scope', 'אין להעתיק שירים או לייחס דגם למשפחה בלי הסכמה וביקורת זכויות.', 'Do not copy songs or attribute a pattern to a family without consent and rights review.', array( 'avs-buthaina-east' ) ),
		),
		'prompt_en' => 'Original overhead archive study of varied hand-tooled kileija patterns beside a fork, spoon and plain round cap, empty lyric-card area and no people, readable song text, family name, costume or staged ritual.',
	),
	array(
		'id' => 'ingredient-deir-ez-zor-molokhia-context',
		'type' => 'ingredient',
		'slug' => 'deir-ez-zor-molokhia-context',
		'parent_id' => 'region-syria-deir-ez-zor',
		'name_he' => 'מולוח׳יה בהקשר דיר א-זור',
		'name_en' => 'Molokhia in Deir ez-Zor Context',
		'summary_he' => 'רשומת הקשר למולוח׳יה שהעדות קושרת לקרקע הפורייה לאורך הפרת. התרגום במקור אינו מפרט בוטני, חקלאי או מסחרי.',
		'summary_en' => 'A context record for molokhia linked by the account to fertile Euphrates soils. The source gloss is not a botanical, agricultural or commercial specification.',
		'region' => 'syria-deir-ez-zor',
		'sources' => array( 'avs-buthaina-east' ),
		'fact_he' => 'עדות בות׳יינה מציינת מולוח׳יה בין הירקות המזוהים עם שפע התוצרת הטרייה באזור דיר א-זור.',
		'fact_en' => 'Buthaina\'s account names molokhia among vegetables associated with abundant fresh produce around Deir ez-Zor.',
		'boundary_he' => 'אין לזהות מין, זן, חלק צמח, מצב טרי או מיובש, מקור גידול, שאריות, שטיפה או חיי מדף לפי השם בלבד.',
		'boundary_en' => 'Name alone cannot establish species, cultivar, plant part, fresh or dried state, growing source, residues, washing or shelf life.',
		'boundary_sources' => array( 'avs-buthaina-east', 'who-five-keys-safer-food', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'references', 'region-syria-al-bukamal', 'הרכיב נשמר בתוך הקשר הפרת הרחב בלי טענת בלעדיות לעיר.', 'The ingredient remains in the wider Euphrates context without a city-exclusivity claim.' ),
		),
		'compliance' => array(
			array( 'molokhia-botanical-lot-and-produce-hygiene', 'שימוש מחייב זיהוי בוטני ומוצר, אצווה, מקור גידול והיגיינת תוצרת; שם מסורתי אינו אישור.', 'Use requires botanical and product identity, lot, growing source and produce hygiene; a traditional name is not approval.', array( 'who-five-keys-safer-food', 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Original molokhia identity-control study with intact cultivated leafy stems in a clean tray, a separate dried sample sealed for comparison and an empty botanical-verification card, no field-foraging or health claim.',
	),
	/* Palmyra. */
	array(
		'id' => 'dish-hannaniyya-palmyra',
		'type' => 'dish',
		'slug' => 'hannaniyya-palmyra',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'חנאנייה מתדמור',
		'name_en' => 'Palmyrene Hannaniyya',
		'summary_he' => 'מנת תמרים וגהי המתועדת בעדות נביהא מתדמור כחטיף אחר הצהריים של עובדי שדה, המוגש עם לחם תנור. הרשומה אינה הוראת הכנה.',
		'summary_en' => 'A date-and-ghee dish documented in Nabiha\'s Palmyra account as a late-afternoon field-work snack served with tannour bread. The record is not a preparation instruction.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra' ),
		'fact_he' => 'נביהא מתארת חנאנייה מתמרים ללא גלעינים עם סאמנה ערבית, בהקשר של מנוחת חקלאים ותמרים מן המטע.',
		'fact_en' => 'Nabiha describes hannaniyya with pitted dates and samin arabi in the context of farmers\' rest and orchard dates.',
		'boundary_he' => 'המקור אינו מספק זן תמר, פעילות מים, זהות שומן, יחס, אימות חום, אלרגנים, גודל מנה או חיי מדף.',
		'boundary_en' => 'The source supplies no date cultivar, water activity, fat identity, ratio, heat validation, allergen profile, portion size or shelf life.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'fda-food-code-2022', 'fda-water-activity' ),
		'relations' => array(
			array( 'requires', 'ingredient-palmyra-date-palm-system', 'התמרים מקושרים למערכת הדקל התדמורית בלי מפרט זן או מוצר.', 'Dates link to the Palmyrene palm system without a cultivar or product specification.' ),
			array( 'requires', 'ingredient-syrian-samn', 'הגהי נזכר בעדות אך דורש זהות מוצר ואלרגן בפועל.', 'Ghee is named in the account but requires actual product and allergen identity.' ),
		),
		'compliance' => array(
			array( 'hannaniyya-date-ghee-product-and-process-validation', 'כל שימוש דורש זיהוי תמר וגהי, אצווה, אלרגנים, תהליך חום, אחסון וחיי מדף מאומתים.', 'Any use requires date and ghee identity, lot, allergens, heat process, storage and validated shelf life.', array( 'fda-food-code-2022', 'fda-water-activity' ) ),
		),
		'prompt_en' => 'Original Palmyrene hannaniyya study with glossy pitted dates and clarified butter in a small earthen bowl beside a separate piece of tannour bread, orchard-work light, no recipe, prayer scene or shelf-life cue.',
	),
	array(
		'id' => 'guide-al-manzala-palmyra-identity-held',
		'type' => 'guide',
		'slug' => 'al-manzala-palmyra-identity-held',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'אל-מנזלה מתדמור, בירור זהות מוחזק',
		'name_en' => 'Palmyrene Al-Manzala, Held Identity Review',
		'summary_he' => 'רשומת זהות fail-closed לשם אל-מנזלה שנזכר כמנה תדמורית דומה לקרן יארוק, ללא רכיבים או תיאור מספק להכרעה.',
		'summary_en' => 'A fail-closed identity record for al-manzala, named as a Palmyrene dish similar to qaren yaruq but without enough components or description to resolve its identity.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-buthaina-east' ),
		'fact_he' => 'בות׳יינה מספרת שמשפחת בעלה הכירה מנה בשם אל-מנזלה והבחינה בינה לבין קרן יארוק שהביאה מדיר א-זור.',
		'fact_en' => 'Buthaina says her husband\'s family knew a dish called al-manzala and distinguished it from qaren yaruq she brought from Deir ez-Zor.',
		'boundary_he' => 'אין נוסחת רכיבים, צורה, טכניקה, איות מקומי נוסף או מקור בלתי תלוי. הזהות נשארת מוחזקת ואין ליצור מתכון או תמונת מנה סופית.',
		'boundary_en' => 'No component formula, form, technique, second local spelling or independent source is available. Identity remains held, and no recipe or finished-dish image may be created.',
		'boundary_sources' => array( 'avs-buthaina-east', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'references', 'dish-qaren-yaruq-deir-ez-zor', 'הקישור משמר את ההשוואה בעדות ואינו ממזג את שתי הזהויות.', 'The link retains the testimony\'s comparison and does not merge the two identities.' ),
		),
		'compliance' => array(
			array( 'identity-held-fail-closed', 'אין מתכון, דימוי מנה סופית, מיפוי מוצר, הצעת מזון או שינוי שם עד מקור נוסף וביקורת דובר מקומי.', 'No recipe, finished-dish depiction, product mapping, food offer or rename is allowed until another source and local-speaker review are complete.', array( 'avs-buthaina-east' ) ),
		),
		'prompt_en' => 'Abstract identity-review board for the unresolved Palmyrene name al-manzala with two empty dish silhouettes, a neutral comparison line and blank evidence slots, no finished food, ingredients, text or archaeological styling.',
		'held' => true,
	),
	array(
		'id' => 'ingredient-palmyra-date-palm-system',
		'type' => 'ingredient',
		'slug' => 'palmyra-date-palm-system',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'מערכת דקל התמר בתדמור',
		'name_en' => 'Palmyrene Date-Palm System',
		'summary_he' => 'רשומת מערכת המחברת תמרים מן המטע, שימושי מזון וכלים ארוגים מכפות דקל בעדות נביהא, בלי להציג זן, היקף עירוני או מוצר למכירה.',
		'summary_en' => 'A system record linking orchard dates, food uses and woven palm-frond tools in Nabiha\'s account, without presenting a cultivar, citywide prevalence or product for sale.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east' ),
		'fact_he' => 'נביהא מתארת תמרים, כפות דקל לסלים ולמחצלות ומטעים משפחתיים; בות׳יינה קושרת גם היא זיכרונות עבודה לדקלים בתדמור.',
		'fact_en' => 'Nabiha describes dates, palm fronds for baskets and mats, and family orchards; Buthaina also links Palmyra work memories to date palms.',
		'boundary_he' => 'עדויות אישיות אינן מדידה של מספר עצים, זן, יבול, חומר מגע-מזון, ניקיון, מזיקים או בטיחות שימוש בכף דקל.',
		'boundary_en' => 'Personal accounts are not measurements of tree count, cultivar, yield, food-contact material, sanitation, pests or safe use of a palm frond.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'used_in', 'dish-hannaniyya-palmyra', 'התמרים מקושרים לחנאנייה ללא מפרט זן.', 'Dates link to hannaniyya without a cultivar specification.' ),
			array( 'references', 'technique-palmyra-burma-clay-coated-qidriya', 'כף דקל נזכרת בעדות בורמה אך אינה מאושרת ככלי מגע-מזון.', 'A palm frond is mentioned in the burma account but is not approved as a food-contact tool.' ),
		),
		'compliance' => array(
			array( 'date-palm-food-and-material-identity', 'תמר או כלי דקל דורשים זיהוי זן וחומר, אצווה, ניקיון, התאמה למגע מזון וביקורת מזיקים.', 'A date or palm tool requires cultivar and material identity, lot, sanitation, food-contact suitability and pest review.', array( 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Original Palmyrene date-palm system still life with fresh dates, a woven bread basket and a palm-frond mat shown in separated evidence zones, no tree-count claim, commercial package, person or ancient-ruins backdrop.',
	),
	array(
		'id' => 'technique-palmyra-burma-clay-coated-qidriya',
		'type' => 'technique',
		'slug' => 'palmyra-burma-clay-coated-qidriya',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'קדרייה מצופת חמר לבורמה תדמורית',
		'name_en' => 'Clay-Coated Qidriya for Palmyrene Burma',
		'summary_he' => 'מפת טכניקה פרטית לכלי קדרייה גדול המצופה חמר ולבישול בורמה מחיטה ובשר באש, ללא הוראות ציפוי, הדלקה, זמן או טמפרטורה.',
		'summary_en' => 'A private technique map for a large clay-coated qidriya used to cook wheat-and-meat burma over fire, without coating, ignition, time or temperature instructions.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east' ),
		'fact_he' => 'שתי עדויות תדמוריות מתארות כלי גדול, חיטה ובשר; בות׳יינה מתארת ציפוי חיצוני בחמר והכנה קהילתית סביב אש.',
		'fact_en' => 'Two Palmyra accounts describe a large vessel, wheat and meat; Buthaina describes an exterior clay coating and communal preparation around fire.',
		'boundary_he' => 'אין מפרט כלי או חמר למגע מזון, פיזור חום, דלק, אוורור, עשן, עומס, טמפרטורת ליבה או קירור. אין לפרסם פרוטוקול אש.',
		'boundary_en' => 'No vessel or food-contact clay specification, heat distribution, fuel, ventilation, smoke, load, core temperature or cooling validation exists. No fire protocol may be published.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east', 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'used_in', 'dish-burma-palmyra', 'הטכניקה מקושרת לישות הבורמה הקיימת בלי לשכפל מתכון.', 'The technique links to the existing burma entity without duplicating a recipe.' ),
			array( 'requires', 'ingredient-palmyra-white-wheat-burma-context', 'החיטה הלבנה נשמרת כהקשר עדותי ולא כמפרט זן.', 'White wheat remains a testimony context, not a cultivar specification.' ),
			array( 'requires', 'ingredient-syrian-red-meat', 'הבשר דורש זהות, אצווה ושרשרת קירור בפועל.', 'Meat requires actual identity, lot and cold chain.' ),
		),
		'compliance' => array(
			array( 'meat-source-cold-chain-and-thermal-validation', 'אין שימוש בבשר ללא מקור ואצווה מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי.', 'No meat use without identified source and lot, cold chain, cross-contamination controls and thermal validation.', array( 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ) ),
			array( 'open-fire-fuel-ventilation-burn-and-thermal-control', 'כלי גדול ואש פתוחה דורשים הנדסת ציוד, חומר, יציבות, דלק, אוורור, עשן וכוויות בידי מומחים.', 'A large vessel and open fire require expert equipment, material, stability, fuel, ventilation, smoke and burn controls.', array( 'who-five-keys-safer-food', 'foodsafety-safe-temperatures' ) ),
		),
		'prompt_en' => 'Technical Palmyrene burma vessel study showing an empty large qidriya with a dry exterior clay layer on a stable cold hearth, separate wheat and cooked-meat reference samples, no flame, people, timing or operating diagram.',
	),
	array(
		'id' => 'tradition-palmyra-communal-burma',
		'type' => 'tradition',
		'slug' => 'palmyra-communal-burma',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'הכנת בורמה קהילתית בתדמור',
		'name_en' => 'Communal Burma Preparation in Palmyra',
		'summary_he' => 'מסורת עדותית של הכנת בורמה לחתונות, עיד, אורחים והתכנסויות, עם חלוקת עבודה, שירה ושיתוף בין משפחה ושכנים.',
		'summary_en' => 'A testimony-bounded tradition of preparing burma for weddings, Eid, guests and gatherings, with shared labor, singing, family and neighbors.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east' ),
		'fact_he' => 'נביהא ובות׳יינה מתארות בורמה כמנה לאירוח ולאירועים גדולים שהכנתה מאורגנת במשותף ולא כמנת יחיד.',
		'fact_en' => 'Nabiha and Buthaina describe burma as a hospitality and large-occasion dish prepared collectively rather than as an individual serving.',
		'boundary_he' => 'העדויות אינן רשות להעתיק שירים, לשחזר אנשים או לבוש, או להפעיל כלי ואש ללא תוכנית בטיחות ונגישות.',
		'boundary_en' => 'The accounts do not authorize copying songs, reconstructing people or clothing, or operating the vessel and fire without a safety and accessibility plan.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east', 'who-five-keys-safer-food', 'foodsafety-safe-temperatures' ),
		'relations' => array(
			array( 'references', 'dish-burma-palmyra', 'המסורת מפנה לישות המנה הקיימת.', 'The tradition references the existing dish entity.' ),
			array( 'references', 'technique-palmyra-burma-clay-coated-qidriya', 'הכלי והאש נשמרים בגבול טכני נפרד.', 'The vessel and fire remain in a separate technical boundary.' ),
			array( 'references', 'technique-palmyra-wheat-pounding-winnowing', 'עיבוד החיטה נשמר כטכניקה נפרדת.', 'Wheat processing remains a separate technique.' ),
		),
		'compliance' => array(
			array( 'communal-event-rights-and-safety-review', 'כל תיעוד ציבורי דורש הסכמה וזכויות; כל הפעלה דורשת תוכנית קהל, ארגונומיה, אש ובטיחות מזון.', 'Any public documentation requires consent and rights review; any operation requires crowd, ergonomic, fire and food-safety planning.', array( 'avs-nabiha-palmyra', 'avs-buthaina-east', 'who-five-keys-safer-food' ) ),
			array( 'meat-source-cold-chain-and-thermal-validation', 'הקשר קהילתי אינו מחליף מקור בשר, קירור ואימות תרמי.', 'Communal context does not replace meat sourcing, cold chain and thermal validation.', array( 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ) ),
		),
		'prompt_en' => 'Original communal-burma editorial scene with a covered cold qidriya, several empty stirring positions and separated wheat-preparation tools suggesting shared labor, no people, songs, costumes, flames or crowd reconstruction.',
	),
	array(
		'id' => 'ingredient-kamaa-source-term-palmyra',
		'type' => 'ingredient',
		'slug' => 'kamaa-source-term-palmyra',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'כמאא כמונח מקור מתדמור',
		'name_en' => 'Kamaa as a Palmyra Source Term',
		'summary_he' => 'רשומת זהות לשם כמאא שהמקור מתרגם ככמהין מדברי ומתאר כחומר עונתי מתדמור. התרגום והצבע אינם זיהוי טקסונומי.',
		'summary_en' => 'An identity record for kamaa, translated by the source as desert truffles and described as a seasonal Palmyra food. The translation and color are not taxonomic identification.',
		'region' => 'syria-palmyra-al-hammad',
		'sources' => array( 'avs-nabiha-palmyra' ),
		'fact_he' => 'נביהא משתמשת בשם כמאא, קושרת אותו לאל-חמאד ומתארת הקשר עונתי ואירוח; הרשומה שומרת את שם המקור.',
		'fact_en' => 'Nabiha uses the name kamaa, links it to Al-Hammad and describes seasonal and hospitality contexts; this record preserves the source term.',
		'boundary_he' => 'אין שם מדעי, בדיקת מומחה, דגימה, כימיה או שלילת דמויי-מין רעילים. אין לזהות לפי צבע, סדקי קרקע, עונה, תמונה או שם.',
		'boundary_en' => 'No scientific name, expert examination, specimen, chemistry or toxic look-alike exclusion is available. Do not identify by color, soil cracks, season, image or name.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'who-natural-toxins-food' ),
		'relations' => array(
			array( 'used_in', 'dish-kamaa-with-saj-palmyra', 'מונח המקור מקושר למנה אך אינו אישור אכילות.', 'The source term links to the dish but is not edibility approval.' ),
			array( 'references', 'tradition-palmyra-kamaa-seasonal-foraging', 'ההקשר העונתי נשמר ברשומת מסורת נפרדת.', 'Seasonal context remains in a separate tradition record.' ),
		),
		'compliance' => array(
			array( 'wild-food-identity-and-source-control', 'אין ללקט, לרכוש, להגיש או לצלם כמזון ללא זיהוי טקסונומי ואישור מקור בידי מומחה מוסמך.', 'Do not forage, purchase, serve or depict as food without taxonomic identity and source approval by a qualified expert.', array( 'who-natural-toxins-food' ) ),
		),
		'prompt_en' => 'Abstract kamaa identity-control study with one pale subterranean fungal specimen obscured under a clear quarantine dome beside an empty taxonomy card, no edible plating, cut surface, soil-search cue or species claim.',
	),
	array(
		'id' => 'dish-kamaa-with-saj-palmyra',
		'type' => 'dish',
		'slug' => 'kamaa-with-saj-palmyra',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'כמאא עם לחם סאג׳ בתדמור',
		'name_en' => 'Kamaa with Saj Bread in Palmyra',
		'summary_he' => 'זהות מנה בעדות נביהא שבה כמאא מבושלת עם בשר ומוגשת על לחם טרי לאורחים חשובים. אין ברשומה אישור זיהוי או מתכון.',
		'summary_en' => 'A dish identity in Nabiha\'s account in which kamaa is cooked with meat and served over fresh bread for important guests. The record supplies neither identification approval nor a recipe.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra' ),
		'fact_he' => 'העדות קושרת כמאא, בשר, לחם והכנסת אורחים בתדמור, תוך שמירת הסיפור במסגרת משפחתית מזוהה.',
		'fact_en' => 'The account links kamaa, meat, bread and hospitality in Palmyra while remaining within an identified family testimony.',
		'boundary_he' => 'אין זיהוי טקסונומי לכמאא, מין בשר, אצווה, שרשרת קירור, שיטת בישול או טמפרטורת ליבה מאומתת.',
		'boundary_en' => 'No taxonomic identity for kamaa, meat species, lot, cold chain, cooking method or validated core temperature is available.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'who-natural-toxins-food', 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'requires', 'ingredient-kamaa-source-term-palmyra', 'המונח נדרש לזהות המנה אך אינו אישור אכילות.', 'The term is required for dish identity but is not edibility approval.' ),
			array( 'requires', 'ingredient-syrian-red-meat', 'הבשר נזכר בעדות בלי מין או מפרט אצווה.', 'Meat is named in the account without species or lot specification.' ),
			array( 'references', 'technique-syrian-saj-bread', 'הלחם מקושר כהקשר הגשה בלי לקבוע זהות טכנית מלאה.', 'Bread links as a serving context without asserting full technical identity.' ),
		),
		'compliance' => array(
			array( 'wild-food-identity-and-source-control', 'אין להכין או להגיש עד זיהוי כמאא בידי מומחה, אישור מקור, אצווה ועקיבות.', 'Do not prepare or serve until kamaa has expert identity, approved source, lot and traceability.', array( 'who-natural-toxins-food' ) ),
			array( 'meat-source-cold-chain-and-thermal-validation', 'הבשר דורש זהות, שרשרת קירור, היגיינה ואימות תרמי למנה בפועל.', 'Meat requires identity, cold chain, hygiene and dish-specific thermal validation.', array( 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ) ),
		),
		'prompt_en' => 'Private Palmyrene kamaa-and-saj concept with a covered fully cooked meat portion, bread kept separate and the unresolved kamaa component hidden under a labeled-free review dome, no serving-ready plate or foraging cue.',
	),
	array(
		'id' => 'tradition-palmyra-kamaa-seasonal-foraging',
		'type' => 'tradition',
		'slug' => 'palmyra-kamaa-seasonal-foraging',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'עונת הכמאא והליקוט בתדמור',
		'name_en' => 'Kamaa Season and Gathering in Palmyra',
		'summary_he' => 'רשומת מסורת לעונה קצרה של איסוף כמאא באל-חמאד ולהגשתה לאורחים לפי עדות נביהא. היא אינה מדריך איתור, זיהוי או ליקוט.',
		'summary_en' => 'A tradition record for a short kamaa-gathering season in Al-Hammad and hospitality use according to Nabiha. It is not a locating, identification or foraging guide.',
		'region' => 'syria-palmyra-al-hammad',
		'sources' => array( 'avs-nabiha-palmyra' ),
		'fact_he' => 'העדות מתארת עונת כמאא מסוף החורף לתחילת האביב ואת האיסוף כזיכרון משפחתי הקשור לשדה ולאירוח.',
		'fact_en' => 'The account describes a late-winter to early-spring kamaa season and gathering as a family memory connected to fields and hospitality.',
		'boundary_he' => 'עונה, מקום, צבע או סימני קרקע אינם זיהוי אכילות. הרשומה משמיטה במכוון הוראות חיפוש ואיסוף ודורשת מומחה ומקור מורשה.',
		'boundary_en' => 'Season, place, color or soil signs do not establish edibility. The record intentionally omits search and collection instructions and requires an expert and approved source.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'who-natural-toxins-food' ),
		'relations' => array(
			array( 'references', 'ingredient-kamaa-source-term-palmyra', 'המסורת מפנה למונח מקור בלתי מזוהה טקסונומית.', 'The tradition references a source term without taxonomic identity.' ),
			array( 'references', 'dish-kamaa-with-saj-palmyra', 'הקשר האירוח נשמר בלי לאשר הכנה.', 'Hospitality context is retained without approving preparation.' ),
		),
		'compliance' => array(
			array( 'wild-food-identity-and-source-control', 'אין להפוך את זיכרון הליקוט להוראת שטח; רק מומחה מוסמך ומקור מאושר יכולים לאמת חומר למזון.', 'Do not turn the gathering memory into field instructions; only a qualified expert and approved source can validate food material.', array( 'who-natural-toxins-food' ) ),
		),
		'prompt_en' => 'Original seasonal-memory abstraction for Palmyra kamaa with an empty late-winter calendar arc, a sealed soil sample and a closed evidence box, no specimen, cracked-ground cue, digging tool, edible plate or foraging route.',
	),
	array(
		'id' => 'dish-bulgur-chickpeas-palmyra',
		'type' => 'dish',
		'slug' => 'bulgur-chickpeas-palmyra',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'בורגול עם חומוס בתדמור',
		'name_en' => 'Bulgur with Chickpeas in Palmyra',
		'summary_he' => 'זהות מנה מבוססת-שם מתוך עדות נביהא המונה בורגול עם חומוס בין מנות החיטה של תדמור, ללא נוסחת רכיבים או טכניקה.',
		'summary_en' => 'A name-level dish identity from Nabiha\'s account, which lists bulgur with chickpeas among Palmyra wheat dishes without a component formula or technique.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra' ),
		'fact_he' => 'נביהא מונה בורגול עם חומוס לצד מנות חיטה תדמוריות אחרות; זהו היקף הראיה לזהות המנה.',
		'fact_en' => 'Nabiha lists bulgur with chickpeas beside other Palmyra wheat dishes; that is the evidence limit for dish identity.',
		'boundary_he' => 'אין סוג בורגול או חומוס, יחס, שומן, תבלין, השריה, זמן, טמפרטורה, אלרגן או צורת הגשה מאומתים.',
		'boundary_en' => 'No bulgur or chickpea grade, ratio, fat, spice, hydration, time, temperature, allergen or serving form is verified.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'fda-food-code-2022', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'requires', 'ingredient-syrian-bulgur', 'הבורגול נדרש ברמת השם בלבד.', 'Bulgur is required only at name level.' ),
			array( 'requires', 'ingredient-syrian-chickpeas', 'החומוס נדרש ברמת השם בלבד.', 'Chickpeas are required only at name level.' ),
		),
		'compliance' => array(
			array( 'bulgur-chickpea-formula-and-process-held', 'אין לפרסם מתכון עד מקור נוסחה נוסף, ניסוי מטבח, אלרגנים ואימות בישול ואחסון.', 'Publish no recipe until another formula source, kitchen testing, allergens, cooking and storage validation are complete.', array( 'avs-nabiha-palmyra', 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Original ingredient-boundary study for Palmyrene bulgur with chickpeas, cooked bulgur and cooked chickpeas held in separate plain bowls with an empty center, no invented finished dish, garnish or ratio cue.',
	),
	array(
		'id' => 'dish-bulgur-vermicelli-palmyra',
		'type' => 'dish',
		'slug' => 'bulgur-vermicelli-palmyra',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'בורגול עם שעירייה בתדמור',
		'name_en' => 'Bulgur with Vermicelli in Palmyra',
		'summary_he' => 'זהות מנה מבוססת-שם מתוך עדות נביהא המונה בורגול עם שעירייה ומתעדת בנפרד הכנת שעירייה ביתית, בלי להוכיח נוסחה משולבת.',
		'summary_en' => 'A name-level dish identity from Nabiha\'s account, which lists bulgur with vermicelli and separately documents home vermicelli, without establishing a combined formula.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra' ),
		'fact_he' => 'העדות מונה בורגול עם שעירייה בין מנות תדמור ומתארת עשיית אטריות דקות בבית בהקשר נפרד.',
		'fact_en' => 'The account lists bulgur with vermicelli among Palmyra dishes and describes making fine noodles at home in a separate context.',
		'boundary_he' => 'אין נוסחת בצק, זהות קמח, אלרגן גלוטן, עובי אטרייה, ייבוש, יחס לבורגול, קלייה, זמן או טמפרטורה.',
		'boundary_en' => 'No dough formula, flour identity, gluten allergen, noodle thickness, drying, bulgur ratio, toasting, time or temperature is available.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'fda-food-code-2022', 'fda-water-activity' ),
		'relations' => array(
			array( 'requires', 'ingredient-syrian-bulgur', 'הבורגול מתועד בשם המנה ללא דרגת מוצר.', 'Bulgur is documented in the dish name without product grade.' ),
			array( 'references', 'technique-home-shairiyya-euphrates', 'הקישור משווה למסורת שעירייה אזורית ואינו מוכיח אותה טכניקה.', 'The link compares with a regional vermicelli tradition and does not prove the same technique.' ),
		),
		'compliance' => array(
			array( 'vermicelli-gluten-drying-and-formula-validation', 'אין להכין או לייבש שעירייה לפי האזכור; נדרשים נוסחה, אלרגנים, תהליך ייבוש, פעילות מים ואחסון.', 'Do not make or dry vermicelli from the mention; formula, allergens, drying process, water activity and storage require validation.', array( 'fda-food-code-2022', 'fda-water-activity' ) ),
		),
		'prompt_en' => 'Original Palmyrene bulgur-and-vermicelli identity study with cooked bulgur and dry handmade noodle strands in separate evidence trays, empty combined-dish area, no drying timeline, flame or serving reconstruction.',
	),
	array(
		'id' => 'tradition-palmyra-first-tooth-boiled-wheat',
		'type' => 'tradition',
		'slug' => 'palmyra-first-tooth-boiled-wheat',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'חיטה מבושלת לציון שן ראשונה בתדמור',
		'name_en' => 'Boiled Wheat for a First Tooth in Palmyra',
		'summary_he' => 'מסורת משפחתית בעדות נביהא של בישול חיטה שלמה ושיתופה עם שכנים וקרובים כאשר הופיעה שן ראשונה לתינוק. זו אינה המלצת האכלה.',
		'summary_en' => 'A family tradition in Nabiha\'s account of cooking whole wheat and sharing it with neighbors and relatives when a baby\'s first tooth appeared. It is not feeding advice.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra' ),
		'fact_he' => 'נביהא מתארת חיטה מן האדמה המשפחתית שחולקה בקהילה לציון אבן הדרך של שן ראשונה.',
		'fact_en' => 'Nabiha describes wheat from family land shared in the community to mark the first-tooth milestone.',
		'boundary_he' => 'הרשומה אינה מאשרת מתן גרגרי חיטה לתינוק, גיל, מרקם, גודל מנה או התאמה רפואית. יש להפריד תיעוד מסורת מהנחיות הזנה.',
		'boundary_en' => 'The record does not approve giving wheat kernels to an infant or establish age, texture, portion or medical suitability. Tradition documentation must remain separate from feeding guidance.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'who-complementary-feeding-2023', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'references', 'ingredient-palmyra-white-wheat-burma-context', 'הקישור משמר הקשר חיטה תדמורי ואינו קובע שזה אותו זן.', 'The link preserves a Palmyra wheat context and does not assert the same cultivar.' ),
		),
		'compliance' => array(
			array( 'infant-feeding-choking-allergen-and-clinical-review', 'אין לפרסם הוראת האכלה או להציג תינוק אוכל; נדרשים התאמת גיל ומרקם, אלרגנים והנחיה קלינית מוסמכת.', 'Publish no feeding instruction or image of an infant eating; age and texture suitability, allergens and qualified clinical guidance are required.', array( 'who-complementary-feeding-2023' ) ),
		),
		'prompt_en' => 'Original milestone-memory still life with a covered bowl of fully cooked whole wheat placed beside a blank family-sharing card and a small tooth-shaped abstract marker, no infant, spoon-feeding, portion or medical claim.',
	),
	array(
		'id' => 'ingredient-palmyra-white-wheat-burma-context',
		'type' => 'ingredient',
		'slug' => 'palmyra-white-wheat-burma-context',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'חיטה לבנה בהקשר בורמה תדמורית',
		'name_en' => 'White Wheat in Palmyrene Burma Context',
		'summary_he' => 'רשומת חומר גלם למונח חיטה לבנה שבעדות נביהא יועד לבורמה, בעוד חיטות אחרות עובדו לבורגול, ג׳ריש, סולת וקמח. אין זיהוי זן.',
		'summary_en' => 'An ingredient record for the white-wheat term assigned to burma in Nabiha\'s account, while other wheat was processed into bulgur, jreesh, semolina and flour. No cultivar is identified.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east' ),
		'fact_he' => 'נביהא מבחינה בעדותה בין חיטה לבנה לבורמה לבין שימושים אחרים של חיטה; בות׳יינה מתארת בורמה מגרגרי חיטה מלאה.',
		'fact_en' => 'Nabiha distinguishes white wheat for burma from other wheat uses in her account; Buthaina describes burma made with whole wheat grains.',
		'boundary_he' => 'צבע עממי אינו זן או מפרט. אין שם בוטני, מקור זרעים, חלבון, לחות, פסולת, טיפול, עקיבות או התאמת עיבוד.',
		'boundary_en' => 'A vernacular color is not a cultivar or specification. No botanical name, seed source, protein, moisture, foreign material, treatment, traceability or processing suitability is available.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'used_in', 'dish-burma-palmyra', 'החיטה מקושרת לבורמה ברמת העדות בלבד.', 'The wheat links to burma only at testimony level.' ),
			array( 'used_in', 'technique-palmyra-burma-clay-coated-qidriya', 'החיטה היא קלט הקשרי ללא מפרט רכש.', 'The wheat is a contextual input without a purchase specification.' ),
			array( 'used_in', 'technique-palmyra-wheat-pounding-winnowing', 'החיטה מקושרת לטיפול המסורתי בלי הוראות הפעלה.', 'The wheat links to traditional handling without operating instructions.' ),
		),
		'compliance' => array(
			array( 'white-wheat-cultivar-lot-and-foreign-material-validation', 'נדרש זיהוי חיטה ואצווה, גלוטן, ניקיון, פסולת זרה, אחסון והתאמת עיבוד לפני כל שימוש.', 'Wheat and lot identity, gluten, cleanliness, foreign material, storage and processing suitability are required before use.', array( 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Macro comparison of pale whole wheat kernels for Palmyra burma beside reddish wheat in a separate tray, both unbranded with empty cultivar cards, no seed-source, purity, commercial provenance or performance claim.',
	),
	array(
		'id' => 'technique-palmyra-wheat-pounding-winnowing',
		'type' => 'technique',
		'slug' => 'palmyra-wheat-pounding-winnowing',
		'parent_id' => 'region-syria-palmyra',
		'name_he' => 'כתישה, שפשוף וזריית חיטה בתדמור',
		'name_en' => 'Palmyrene Wheat Pounding and Winnowing',
		'summary_he' => 'רשומת טכניקה לעיבוד חיטה לבורמה במכתש אבן, הכוללת בהעדויות כתישה, הפרדה וניקוי משותף. אין בה רצף תפעולי לציבור.',
		'summary_en' => 'A technique record for processing burma wheat in a stone mortar, with pounding, separation and communal cleaning in the accounts. It contains no public operating sequence.',
		'region' => 'syria-palmyra',
		'sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east' ),
		'fact_he' => 'נביהא מתארת מכתש אבן, עבודה מחזורית וחלוקת תפקידים; בות׳יינה מתארת כתישה והפרדת קליפה לפני בישול הבורמה.',
		'fact_en' => 'Nabiha describes a stone mortar, rhythmic work and divided roles; Buthaina describes pounding and husk separation before burma cooking.',
		'boundary_he' => 'אין מפרט אבן, ניקוי, שברי מינרל, אבק, ארגונומיה, גודל חלקיק, לחות, סינון או בקרת גוף זר.',
		'boundary_en' => 'No stone, sanitation, mineral-fragment, dust, ergonomic, particle-size, moisture, screening or foreign-material specification is available.',
		'boundary_sources' => array( 'avs-nabiha-palmyra', 'avs-buthaina-east', 'fda-food-code-2022', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'requires', 'ingredient-palmyra-white-wheat-burma-context', 'הטכניקה מקושרת למונח החיטה התחום.', 'The technique links to the bounded wheat term.' ),
			array( 'used_in', 'dish-burma-palmyra', 'הטכניקה מקושרת לבורמה בלי לפרסם שלבי עבודה.', 'The technique links to burma without publishing work steps.' ),
		),
		'compliance' => array(
			array( 'stone-tool-dust-ergonomic-and-foreign-material-control', 'אין להפעיל מכתש ללא אימות חומר מגע-מזון, ניקוי, מיגון אבק, ארגונומיה ובקרת שברי אבן וגופים זרים.', 'Do not operate a mortar without food-contact material validation, sanitation, dust protection, ergonomics, and stone-fragment and foreign-material controls.', array( 'fda-food-code-2022', 'who-five-keys-safer-food' ) ),
		),
		'prompt_en' => 'Technical heritage-tool study of a clean empty stone mortar, pestle, sieve and winnowing tray beside sealed wheat, dust-control hood visible, no people, motion sequence, quantities or active pounding.',
	),

	/* Suwayda, Hauran and the south. */
	array(
		'id' => 'guide-halqoum-haurani-identity-held',
		'type' => 'guide',
		'slug' => 'halqoum-haurani-identity-held',
		'parent_id' => 'region-syria-hauran',
		'name_he' => 'חלקום חוראני, בירור זהות מוחזק',
		'name_en' => 'Haurani Halqoum, Held Identity Review',
		'summary_he' => 'רשומת זהות fail-closed לשם קינוח שהמקור מכנה Halqoum Delights או Hauranian Delights ומקשר לחוראן ולירדן, ללא נוסחה מספקת.',
		'summary_en' => 'A fail-closed identity record for a sweet named by the source as Halqoum Delights or Hauranian Delights and linked to Hauran and Jordan, without a sufficient formula.',
		'region' => 'syria-hauran-jordan-borderlands',
		'sources' => array( 'ifrepo-hauran-foodways' ),
		'fact_he' => 'פרק 10 של IFPO מונה חלקום בין המתוקים המוכרים בחוראן ובירדן אך אינו מפרט את זהותו בעמודי המקור הזמינים.',
		'fact_en' => 'IFPO chapter 10 lists halqoum among sweets known in Hauran and Jordan but does not resolve its identity in the available source pages.',
		'boundary_he' => 'אין נוסחת סוכר או עמילן, מרקם, תיבול, אלרגן, שיטת חימום, חיי מדף, הבחנה מלוקום אחר או מקור בלתי תלוי.',
		'boundary_en' => 'No sugar or starch formula, texture, flavoring, allergen, heat method, shelf life, distinction from other lokum or independent source is available.',
		'boundary_sources' => array( 'ifrepo-hauran-foodways', 'fda-food-code-2022', 'fda-water-activity' ),
		'relations' => array(
			array( 'references', 'region-syria-hauran', 'השם נשמר בהקשר חוראני בלי טענת בלעדיות.', 'The name remains in Haurani context without an exclusivity claim.' ),
		),
		'compliance' => array(
			array( 'identity-held-fail-closed', 'אין מתכון, תמונת ממתק סופית, מיזוג ללוקום אחר, מוצר או הצעה עד מקור נוסף וביקורת לשונית מקומית.', 'No recipe, finished-sweet image, merge with another lokum, product or offer is allowed until another source and local linguistic review are complete.', array( 'ifrepo-hauran-foodways' ) ),
		),
		'prompt_en' => 'Abstract held-identity board for Haurani halqoum with an empty confection silhouette, two blank regional evidence columns and a closed formula folder, no finished sweet, ingredients, powder, packaging or origin seal.',
		'held' => true,
	),
	array(
		'id' => 'guide-mleihi-mansaf-hauran-jordan-boundary',
		'type' => 'guide',
		'slug' => 'mleihi-mansaf-hauran-jordan-boundary',
		'parent_id' => 'region-syria-hauran',
		'name_he' => 'מליחי ומנסף בגבול חוראן-ירדן',
		'name_en' => 'Mleihi and Mansaf across the Hauran-Jordan Boundary',
		'summary_he' => 'מדריך השוואה פרטי לגרסאות מליחי ומנסף בחוראן, דרעא, א-סווידא וירדן, השומר בורגול, אורז, יוגורט טרי ומוצרי חלב מיובשים כהבדלים מתועדים ולא כהוכחת מוצא.',
		'summary_en' => 'A private comparison guide for mleihi and mansaf versions in Hauran, Daraa, Suwayda and Jordan, retaining bulgur, rice, fresh yogurt and dried dairy as documented differences rather than proof of origin.',
		'region' => 'syria-hauran-jordan-borderlands',
		'sources' => array( 'ifrepo-hauran-foodways', 'avs-ghaimana-suwayda', 'avs-shahla-hauran' ),
		'fact_he' => 'המקורות מתעדים גרסה חוראנית עם בורגול ומוצר חלב מיובש, גרסת א-סווידא עם יוגורט טרי ואגוזים ושינויים במרכיבים לאחר עקירה.',
		'fact_en' => 'The sources document a Haurani version with bulgur and dried dairy, a Suwayda version with fresh yogurt and nuts, and ingredient changes after displacement.',
		'boundary_he' => 'הבדלי דגן וחלב אינם מוכיחים מקור בלעדי או קדימות היסטורית. אין נוסחה מאוחדת, וזהות ג׳מיד, היגט ויוגורט נשמרת נפרדת.',
		'boundary_en' => 'Grain and dairy differences establish neither exclusive origin nor historical priority. There is no merged formula, and jameed, higet and fresh yogurt remain separate identities.',
		'boundary_sources' => array( 'ifrepo-hauran-foodways', 'avs-ghaimana-suwayda', 'avs-shahla-hauran', 'fda-food-code-2022', 'foodsafety-safe-temperatures' ),
		'relations' => array(
			array( 'references', 'dish-mansaf-mleihi', 'המדריך מפנה לישות המליחי המשותפת בלי להעביר בעלות לאזור אחד.', 'The guide references the shared mleihi entity without transferring ownership to one region.' ),
			array( 'references', 'preparation-mleihi-suwayda-fresh-yogurt', 'גרסת א-סווידא נשמרת עם יוגורט טרי ואגוזים לא מזוהים.', 'The Suwayda version remains with fresh yogurt and source-unspecified nuts.' ),
			array( 'references', 'preparation-mleihi-hauran-jameed', 'גרסת חוראן נשמרת עם מוצר חלב מיובש ובורגול.', 'The Hauran version remains with dried dairy and bulgur.' ),
		),
		'compliance' => array(
			array( 'mleihi-no-exclusive-origin-or-formula-merge', 'אין להציג אחת הגרסאות כמקור הבלעדי ואין להחליף בין מוצרי החלב ללא אימות זהות ותהליך.', 'Do not present any version as the exclusive origin, and do not substitute dairy products without identity and process validation.', array( 'ifrepo-hauran-foodways', 'avs-ghaimana-suwayda', 'avs-shahla-hauran' ) ),
			array( 'dairy-pasteurization-allergen-and-time-temperature', 'כל גרסה דורשת מוצר חלב מפוסטר ומזוהה, אלרגנים, קירור ותוכנית זמן-טמפרטורה מאומתת.', 'Every version requires identified pasteurized dairy, allergens, refrigeration and a validated time-temperature plan.', array( 'fda-food-code-2022', 'foodsafety-safe-temperatures' ) ),
			array( 'meat-source-cold-chain-and-thermal-validation', 'בשר או עוף דורשים זהות, אצווה, שרשרת קירור ואימות תרמי נפרד.', 'Meat or poultry requires identity, lot, cold chain and separate thermal validation.', array( 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ) ),
		),
		'prompt_en' => 'Original comparative atlas of three mleihi-mansaf contexts with bulgur and dried dairy, bulgur and fresh yogurt, and rice-based elements in clearly separated neutral trays, no national flags, winner cue or origin badge.',
	),
	array(
		'id' => 'ingredient-suwayda-purslane-context',
		'type' => 'ingredient',
		'slug' => 'suwayda-purslane-context',
		'parent_id' => 'region-syria-suwayda',
		'name_he' => 'ריג׳לה בהקשר א-סווידא',
		'name_en' => 'Purslane in Suwayda Context',
		'summary_he' => 'רשומת הקשר לריג׳לה שהעדות מא-סווידא מונה בין הירוקים שגודלו לצד עגבניות ותפוחים. הרשומה אינה מניחה ליקוט בר או זיהוי לפי תמונה.',
		'summary_en' => 'A context record for purslane listed in a Suwayda account among cultivated greens beside tomatoes and apples. The record does not assume wild foraging or image-based identification.',
		'region' => 'syria-suwayda',
		'community' => 'druze-suwayda',
		'sources' => array( 'avs-ghaimana-suwayda' ),
		'fact_he' => 'גהימאנה מציינת ריג׳לה בין גידולי המשפחה בתקופת מחסור; העדות תומכת בהקשר גידול ולא בטענת צמח בר.',
		'fact_en' => 'Ghaimana names purslane among family crops during scarcity; the account supports a cultivation context, not a wild-plant claim.',
		'boundary_he' => 'שם מתורגם ותמונה אינם זיהוי בוטני. אין זן, מקור זרעים, מי השקיה, שאריות, אצווה, חלק אכיל או הוראת ליקוט.',
		'boundary_en' => 'A translated name and image are not botanical identification. No cultivar, seed source, irrigation water, residues, lot, edible part or foraging instruction is available.',
		'boundary_sources' => array( 'avs-ghaimana-suwayda', 'who-natural-toxins-food', 'who-five-keys-safer-food', 'fda-food-code-2022' ),
		'relations' => array(
			array( 'references', 'tradition-druze-suwayda', 'הרכיב נשמר בתוך עדות דרוזית מזוהה בלי להכליל לקהילה כולה.', 'The ingredient remains in an identified Druze account without generalizing to the whole community.' ),
		),
		'compliance' => array(
			array( 'wild-food-identity-and-source-control', 'אין ללקט או להגיש צמח לפי שם עממי או תמונה; נדרשים זיהוי בוטני ומקור גידול מאושר.', 'Do not forage or serve a plant from a vernacular name or image; botanical identity and an approved cultivated source are required.', array( 'who-natural-toxins-food' ) ),
			array( 'purslane-produce-water-residue-and-lot-hygiene', 'שימוש בתוצרת דורש אצווה, מקור גידול, איכות מי השקיה, שאריות והיגיינת תוצרת.', 'Produce use requires lot, growing source, irrigation-water quality, residue and produce-hygiene controls.', array( 'who-five-keys-safer-food', 'fda-food-code-2022' ) ),
		),
		'prompt_en' => 'Original cultivated-purslane verification study from Suwayda with intact stems in a clean greenhouse-style tray, sealed soil and water samples kept apart, no wild landscape, picking hand, recipe or medicinal cue.',
	),
	array(
		'id' => 'guide-syrian-qawarma-regional-forms-and-safety',
		'type' => 'guide',
		'slug' => 'syrian-qawarma-regional-forms-and-safety',
		'parent_id' => 'cuisine-syrian-regional',
		'name_he' => 'צורות קווארמה סוריות וגבול בטיחות',
		'name_en' => 'Syrian Qawarma Regional Forms and Safety Boundary',
		'summary_he' => 'מדריך מחקר פרטי לעדויות שונות על בשר בשומן ובצנצנות בג׳זירה, א-סווידא ובמסע עקירה של משפחה מתדמור ודיר א-זור. הוא משמיט כל הוראת שימור.',
		'summary_en' => 'A private research guide to differing accounts of meat in fat and jars in Jazira, Suwayda and a Palmyra-Deir ez-Zor family displacement journey. It omits all preservation instructions.',
		'region' => 'syria-multi-regional',
		'sources' => array( 'northpress-jazira-foodways', 'avs-ghaimana-suwayda', 'avs-buthaina-east' ),
		'fact_he' => 'המקורות מתעדים שמות ושיטות ביתיות שונות לשימור בשר, אך אינם מוכיחים שזהותן הטכנולוגית אחת או שהן יציבות מדף.',
		'fact_en' => 'The sources document different household names and practices for preserving meat, but do not establish one technological identity or shelf stability.',
		'boundary_he' => 'בישול בשר אינו מוכיח בטיחות שימור. אין נתוני pH, פעילות מים, מלח, תהליך תרמי, אריזה, קירור, מיקרוביולוגיה, עקיבות או חיי מדף.',
		'boundary_en' => 'Cooking meat does not establish preservation safety. No pH, water activity, salt, thermal process, packaging, refrigeration, microbiology, traceability or shelf-life validation is available.',
		'boundary_sources' => array( 'northpress-jazira-foodways', 'avs-ghaimana-suwayda', 'avs-buthaina-east', 'fda-water-activity', 'fda-food-code-2022', 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ),
		'relations' => array(
			array( 'references', 'technique-dermale-qawarma-jazira', 'המדריך משמר את שמות ג׳זירה כמשפחה פתוחה ולא כזהות מאוחדת.', 'The guide retains Jazira names as an open family rather than one merged identity.' ),
			array( 'references', 'technique-syrian-mouneh', 'הקשר המונה נשמר כהקשר תרבותי בלבד ולא כאישור יציבות.', 'The mouneh link remains cultural context only, not stability approval.' ),
		),
		'compliance' => array(
			array( 'qawarma-preservation-fail-closed', 'אין לפרסם פרטי תהליך או לטעון אחסון ללא קירור. נדרשים מומחה תהליך, HACCP, pH, פעילות מים, אריזה, קירור, מעבדה ועקיבות.', 'Publish no process details and make no unrefrigerated-storage claim. A process authority, HACCP, pH, water activity, packaging, refrigeration, laboratory work and traceability are required.', array( 'fda-water-activity', 'fda-food-code-2022', 'who-five-keys-safer-food' ) ),
			array( 'meat-source-cold-chain-and-thermal-validation', 'כל בשר דורש מקור ואצווה מזוהים, שרשרת קירור, מניעת זיהום צולב ואימות תרמי.', 'All meat requires identified source and lot, cold chain, cross-contamination controls and thermal validation.', array( 'foodsafety-safe-temperatures', 'who-five-keys-safer-food' ) ),
		),
		'prompt_en' => 'Private qawarma safety review with three sealed cooked-meat-in-fat reference samples from different regional accounts held in a chilled laboratory tray beside blank pH and water-activity fields, no serving, pantry or shelf-stable cue.',
	),
);

$c99_syrian_east_south_entities = array();
foreach ( $c99_syrian_east_south_specs as $spec ) {
	$c99_syrian_east_south_entities[] = $c99_syrian_east_south_build( $spec );
}

$c99_syrian_east_south_expected_ids = array(
	'region-syria-qamishli-assyrian-foodways',
	'tradition-assyrian-akitu-qamishli',
	'ingredient-peeled-barley-dikhwa',
	'technique-dikhwa-yogurt-stabilization',
	'region-syria-al-bukamal',
	'tradition-al-bukamal-bedouin-urban-foodways',
	'technique-deir-bukamal-home-saj-tannour',
	'ingredient-deir-ez-zor-okra-context',
	'technique-deir-okra-fresh-dried-seasonality',
	'dish-mshahmiyya-deir-ez-zor',
	'preparation-muhammara-saj-deir-ez-zor',
	'dish-kileija-deir-ez-zor',
	'tradition-deir-kileija-patterning-al-qashoush',
	'ingredient-deir-ez-zor-molokhia-context',
	'dish-hannaniyya-palmyra',
	'guide-al-manzala-palmyra-identity-held',
	'ingredient-palmyra-date-palm-system',
	'technique-palmyra-burma-clay-coated-qidriya',
	'tradition-palmyra-communal-burma',
	'ingredient-kamaa-source-term-palmyra',
	'dish-kamaa-with-saj-palmyra',
	'tradition-palmyra-kamaa-seasonal-foraging',
	'dish-bulgur-chickpeas-palmyra',
	'dish-bulgur-vermicelli-palmyra',
	'tradition-palmyra-first-tooth-boiled-wheat',
	'ingredient-palmyra-white-wheat-burma-context',
	'technique-palmyra-wheat-pounding-winnowing',
	'guide-halqoum-haurani-identity-held',
	'guide-mleihi-mansaf-hauran-jordan-boundary',
	'ingredient-suwayda-purslane-context',
	'guide-syrian-qawarma-regional-forms-and-safety',
);

$c99_syrian_east_south_ids = array_column( $c99_syrian_east_south_entities, 'id' );
if ( $c99_syrian_east_south_expected_ids !== $c99_syrian_east_south_ids ) {
	throw new RuntimeException( 'Syrian east and south expansion must contain the exact approved 31 entity IDs in order.' );
}
if ( count( $c99_syrian_east_south_ids ) !== count( array_unique( $c99_syrian_east_south_ids ) ) ) {
	throw new RuntimeException( 'Duplicate Syrian east and south entity ID.' );
}

$c99_syrian_east_south_prompts = array();
foreach ( $c99_syrian_east_south_entities as $entity ) {
	if ( empty( $entity['name']['he'] ) || empty( $entity['name']['en'] ) || empty( $entity['summary']['he'] ) || empty( $entity['summary']['en'] ) ) {
		throw new RuntimeException( 'Incomplete bilingual Syrian east and south entity: ' . $entity['id'] );
	}
	if ( isset( $c99_syrian_east_south_prompts[ $entity['visual']['prompt_en'] ] ) ) {
		throw new RuntimeException( 'Duplicate Syrian east and south visual prompt: ' . $entity['id'] );
	}
	$c99_syrian_east_south_prompts[ $entity['visual']['prompt_en'] ] = true;
	foreach ( $entity['facts'] as $fact ) {
		foreach ( $fact['source_ids'] as $source_id ) {
			if ( ! isset( $c99_syrian_east_south_source_ledger[ $source_id ] ) ) {
				throw new RuntimeException( 'Unknown Syrian east and south fact source: ' . $source_id );
			}
		}
	}
	foreach ( $entity['relations'] as $relation ) {
		foreach ( $relation['source_ids'] as $source_id ) {
			if ( ! isset( $c99_syrian_east_south_source_ledger[ $source_id ] ) ) {
				throw new RuntimeException( 'Unknown Syrian east and south relation source: ' . $source_id );
			}
		}
	}
}

$c99_syrian_east_south_counts = array_count_values( array_column( $c99_syrian_east_south_entities, 'type' ) );

return array(
	'schema' => 'complete99-syrian-regional-expansion-east-south-module/v1',
	'version' => 'culinary-science-2026.08.08.v20',
	'sources' => $c99_syrian_east_south_sources,
	'entities' => $c99_syrian_east_south_entities,
	'private_entity_ids' => $c99_syrian_east_south_ids,
	'counts' => array(
		'by_type' => $c99_syrian_east_south_counts,
		'total_entities' => count( $c99_syrian_east_south_entities ),
	),
);
