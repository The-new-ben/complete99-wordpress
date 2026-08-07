<?php
/**
 * Japanese cooking foundations collection and its narrow canonical owner.
 *
 * The collection presents existing approved public entities without changing
 * their ontology parents, canonical owners or search intents.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_foundations_groups = array(
	'ingredients' => array(
		'ingredient-kombu',
		'ingredient-katsuobushi',
		'ingredient-kioke-shoyu',
		'ingredient-kome-koji',
		'ingredient-koji-starter-culture',
		'ingredient-koshihikari-rice',
		'ingredient-fresh-wasabi',
		'ingredient-fresh-dutch-wasabi',
		'ingredient-kito-yuzu',
		'ingredient-hon-mirin',
	),
	'food_science' => array(
		'guide-umami-synergy',
		'guide-wasabi-aitc',
		'molecule-allyl-isothiocyanate',
	),
	'techniques' => array(
		'preparation-ichiban-dashi',
	),
	'equipment' => array(
		'equipment-wasabi-grater',
	),
);

$c99_foundations_receipt_tokens = array();
foreach ( $c99_foundations_groups as $c99_foundations_group_id => $c99_foundations_member_ids ) {
	foreach ( $c99_foundations_member_ids as $c99_foundations_member_id ) {
		$c99_foundations_receipt_tokens[] = $c99_foundations_group_id . ':' . $c99_foundations_member_id;
	}
}
$c99_foundations_receipt_basis = 'japanese-foundations-lab|hub-japanese-foundations-lab|' . implode( '|', $c99_foundations_receipt_tokens );

return array(
	'entity' => $c99_entity(
		array(
			'id'          => 'hub-japanese-foundations-lab',
			'type'        => 'topic_hub',
			'slug'        => 'japanese-foundations-lab',
			'parent_id'   => 'cuisine-japanese-washoku',
			'name'        => $c99_text( 'שער לבישול היפני', 'Explore Japanese cooking' ),
			'summary'     => $c99_text(
				'מסלול פשוט אל חומרי הגלם, מדע הטעם, שיטות ההכנה והכלים של המטבח היפני. מתחילים במה שמסקרן אתכם וממשיכים אל המרכיב, ההסבר, השיטה והכלי המתאים.',
				'A clear path into Japanese ingredients, flavor science, cooking methods and tools. Start with what interests you, then continue to the ingredient, explanation, method and matching tool.'
			),
			'surface_class' => 'editorial_draft',
			'index_policy'  => 'noindex_until_longform_review',
			'review_status' => 'research_draft',
			'seo_group'     => 'museum',
			'page_role'     => 'category',
			'primary_intent' => $c99_text(
				'לגלות את יסודות המטבח היפני במסלול שמחבר חומרי גלם, מדע, טכניקות וכלים',
				'Explore Japanese culinary foundations through ingredients, science, techniques and tools'
			),
			'primary_keyword' => $c99_text( 'יסודות המטבח היפני', 'Japanese culinary foundations' ),
			'secondary_keywords' => array(
				'he' => array( 'מפת יסודות המטבח היפני' ),
				'en' => array( 'Japanese culinary foundations map' ),
			),
			'schema_type' => 'CollectionPage',
			'facts'       => array(
				$c99_fact(
					'fact-japanese-foundations-lab-reading-path',
					'structural',
					'ארבעה מסלולים עוזרים להתחיל מחומר גלם, מדע, שיטת הכנה או כלי, ולהמשיך לנושאים הקשורים.',
					'Four paths make it easy to start with an ingredient, science, a cooking method or a tool, then continue to related topics.',
					'official_source',
					'entity',
					array( 'complete99-public-site' )
				),
			),
			'profiles' => $c99_profiles(
				array(
					'scientific' => $c99_profile( 'pending_evidence', 'ההסברים המדעיים נמצאים במדריכים ובדפי המרכיבים המקושרים.', 'Scientific explanations are provided in the linked guides and ingredient pages.' ),
					'cultural' => $c99_profile( 'pending_evidence', 'ההקשר התרבותי נשמר בדפי המטבח והמסורת המתאימים.', 'Cultural context remains in the relevant cuisine and tradition pages.' ),
					'institutional' => $c99_profile( 'not_applicable', 'האוסף מיועד לגילוי קולינרי ואינו מדריך מוסדות.', 'The collection supports culinary discovery and is not an institutional directory.' ),
					'economic' => $c99_profile( 'not_applicable', 'מחיר וזמינות מוצגים רק ליד מוצר מתאים בחנות.', 'Price and availability appear only beside a matching store product.' ),
					'structural' => $c99_profile(
						'source_backed',
						'ארבעה מסלולים קצרים מאפשרים לעבור מחומר הגלם אל ההסבר, שיטת העבודה והכלי המתאים.',
						'Four concise paths make it easy to move from an ingredient to its explanation, method and matching tool.',
						array( 'fact-japanese-foundations-lab-reading-path' )
					),
				)
			),
			'categories' => array( 'culinary-museum', 'japanese-cuisine', 'foundations' ),
			'attributes' => array(),
			'tags'       => array( 'japanese-foundations', 'ingredients', 'food-science', 'techniques', 'equipment' ),
			'prompt_en'  => 'Commercial culinary studio still life of Japanese cooking foundations arranged as four connected zones: kombu and katsuobushi, clear dashi and molecular flavor notes, careful preparation tools, and a professional wasabi grater. Warm natural side light, dark stone and pale cedar surfaces, precise textures, editorial museum composition, no text or logos.',
			'asset_state' => 'spec_ready',
			'rights_method' => 'generated_concept_with_human_review',
		)
	),
	'collection' => array(
		'key'                  => 'japanese-foundations-lab',
		'owner_entity_id'      => 'hub-japanese-foundations-lab',
		'navigation'           => array(
			'parent_entity_id'  => 'cuisine-japanese-washoku',
			'group_order'       => array( 'ingredients', 'food_science', 'techniques', 'equipment' ),
			'member_ids_by_group' => $c99_foundations_groups,
		),
		'translation_group_id' => 'collection-japanese-foundations-lab',
		'route'                => array(
			'mode'           => 'standalone',
			'canonical_path' => $c99_text( '/museum/japanese-culinary-science/foundations/', '/en/museum/japanese-culinary-science/foundations/' ),
		),
		'index_reason'         => $c99_text(
			'העמוד זמין לעיון מודרך ואינו נכלל כעת בתוצאות החיפוש.',
			'The page is available for guided browsing and is not currently included in search results.'
		),
		'receipt'              => array(
			'state'             => 'derived_from_member_publication',
			'recorded_at'       => '2026-08-06',
			'membership_digest' => 'sha256:' . hash( 'sha256', $c99_foundations_receipt_basis ),
		),
		'display'              => array(
			'title'          => $c99_text( 'שער לבישול היפני', 'Explore Japanese cooking' ),
			'description'    => $c99_text(
				'בוחרים חומר גלם, שאלה מדעית, שיטת הכנה או כלי, וממשיכים למידע שעוזר לבחור, לבשל ולהבין.',
				'Choose an ingredient, a science question, a cooking method or a tool, then continue to information that helps you choose, cook and understand.'
			),
			'hero_entity_id' => 'hub-japanese-foundations-lab',
			'groups'         => array(
				array(
					'id'          => 'ingredients',
					'label'       => $c99_text( 'חומרי גלם', 'Ingredients' ),
					'description' => $c99_text( 'קומבו, קצואובושי, שויו, אורז קושיהיקארי, קוג׳י, תרבית קוג׳י, ווסאבי, יוזו והון מירין, עם מקור, טעם, מדע ושימוש.', 'Kombu, katsuobushi, shoyu, Koshihikari rice, koji, starter culture, wasabi, yuzu and hon mirin, with origin, flavor, science and use.' ),
				),
				array(
					'id'          => 'food_science',
					'label'       => $c99_text( 'מדע המזון', 'Food science' ),
					'description' => $c99_text( 'אומאמי, סינרגיה בין גלוטמט ל-IMP, והכימיה של חריפות הווסאבי.', 'Umami, glutamate and IMP synergy, and the chemistry of wasabi pungency.' ),
				),
				array(
					'id'          => 'techniques',
					'label'       => $c99_text( 'טכניקות', 'Techniques' ),
					'description' => $c99_text( 'עקרונות הכנת איצ׳יבאן דאשי והקשר בין חומר, מים, זמן וטמפרטורה.', 'Ichiban dashi principles and the relationship among material, water, time and temperature.' ),
				),
				array(
					'id'          => 'equipment',
					'label'       => $c99_text( 'ציוד', 'Equipment' ),
					'description' => $c99_text( 'כלים שנבחרים לפי הפעולה, החומר, המידה, התחזוקה והתוצאה הרצויה.', 'Tools selected by task, material, size, care and intended result.' ),
				),
			),
		),
		'public_projection'    => array(
			'enabled' => true,
			'schema'  => 'complete99-culinary-collection-public/v1',
		),
	),
);
