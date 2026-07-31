<?php

/**
 * Complete99 public-safe dish entity trees.
 *
 * Schema contract:
 *
 * - The twelve records mirror the twelve stable slugs in consumer-menu.php.
 * - Identity, component, serving-format and preparation statements are limited
 *   to facts written in that source menu record.
 * - A component name is not a complete recipe. Unknown subcomponents, brands,
 *   quantities, substitutions and production instructions remain unknown.
 * - The allergen vocabulary contains the fourteen EU allergen groups plus a
 *   separate fava bean and G6PD record. Allowed states are:
 *   unknown, contains, may_contain, not_intentionally_used and
 *   verified_free_from.
 * - An allergen is marked contains only when the menu wording itself names the
 *   allergenic food. Culinary knowledge is not used to infer hidden allergens.
 * - Nutrition values, health suitability, price, stock and current dish-level
 *   availability are outside this file and remain unpublished.
 * - Connector relationships do not prove dish-level availability. Wolt is a
 *   restaurant-menu continuation only. Other connectors remain held.
 * - Review owner fields assign accountability functions, not user accounts or
 *   active application roles.
 * - Chicken liver uses Complete99 archive evidence only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_dish_sources = array(
	'wolt-menu-2026-07-31' => array(
		'type'        => 'verified_external_menu',
		'provider'    => 'wolt',
		'url'         => array(
			'he' => 'https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete',
			'en' => 'https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete',
		),
		'source_date' => '2026-07-31',
	),
	'complete99-archive-chicken-liver-2021' => array(
		'type'        => 'complete99_archive_image',
		'provider'    => 'complete99_archive',
		'url'         => array(
			'he' => 'https://a235232-tmp.s1242.upress.link/wp-content/plugins/complete99-platform/assets/images/original/c99-food-chicken-liver-plate-gallery-2021-wp-v01.webp',
			'en' => 'https://a235232-tmp.s1242.upress.link/wp-content/plugins/complete99-platform/assets/images/original/c99-food-chicken-liver-plate-gallery-2021-wp-v01.webp',
		),
		'source_date' => '2021-12-31',
	),
);

$c99_dish_evidence = static function ( $source_id, $statement_he, $statement_en ) use ( $c99_dish_sources ) {
	$source = $c99_dish_sources[ $source_id ];

	return array(
		'source_id'     => $source_id,
		'source_type'   => $source['type'],
		'source_url'    => $source['url'],
		'source_date'   => $source['source_date'],
		'statement'     => array(
			'he' => $statement_he,
			'en' => $statement_en,
		),
		'evidence_mode' => 'complete99_archive_image' === $source['type'] ? 'archive_stated' : 'menu_stated',
	);
};

$c99_dish_component = static function ( $code, $type, $label_he, $label_en, $source_id ) use ( $c99_dish_evidence ) {
	return array(
		'code'                 => $code,
		'type'                 => $type,
		'label'                => array(
			'he' => $label_he,
			'en' => $label_en,
		),
		'quantity_status'      => 'unknown',
		'subcomponents_status' => 'unknown',
		'children'             => array(),
		'evidence'             => $c99_dish_evidence( $source_id, $label_he, $label_en ),
	);
};

$c99_dish_serving_format = static function ( $code, $label_he, $label_en, $source_id ) use ( $c99_dish_evidence ) {
	return array(
		'code'       => $code,
		'label'      => array(
			'he' => $label_he,
			'en' => $label_en,
		),
		'evidence'   => $c99_dish_evidence( $source_id, $label_he, $label_en ),
		'option_gate' => 'provider_check',
	);
};

$c99_allergen_codes = array(
	'cereals_containing_gluten',
	'crustaceans',
	'eggs',
	'fish',
	'peanuts',
	'soybeans',
	'milk',
	'tree_nuts',
	'celery',
	'mustard',
	'sesame',
	'sulphur_dioxide_and_sulphites',
	'lupin',
	'molluscs',
);

$c99_dish_allergen_map = static function ( $explicit_contains, $source_id ) use ( $c99_allergen_codes, $c99_dish_evidence ) {
	$map = array();

	foreach ( $c99_allergen_codes as $allergen_code ) {
		$map[ $allergen_code ] = array(
			'state'                    => 'unknown',
			'evidence_component_codes' => array(),
			'evidence'                 => array(),
			'public_claim_allowed'     => false,
			'review_status'            => 'not_reviewed',
		);
	}

	foreach ( $explicit_contains as $allergen_code => $evidence_record ) {
		$map[ $allergen_code ] = array(
			'state'                    => 'contains',
			'evidence_component_codes' => $evidence_record['component_codes'],
			'evidence'                 => array(
				$c99_dish_evidence(
					$source_id,
					$evidence_record['statement_he'],
					$evidence_record['statement_en']
				),
			),
			'public_claim_allowed'     => false,
			'review_status'            => 'explicit_menu_fact_pending_food_safety_review',
		);
	}

	return $map;
};

$c99_dish_connector_relations = static function ( $source_id ) use ( $c99_dish_sources ) {
	$is_wolt_menu_record = 'verified_external_menu' === $c99_dish_sources[ $source_id ]['type'];

	return array(
		'wolt'     => array(
			'relation'                 => $is_wolt_menu_record ? 'restaurant_menu_reference' : 'dish_relation_not_verified',
			'public_exposure'          => $is_wolt_menu_record,
			'dish_deep_link_verified'  => false,
			'dish_availability_status' => 'provider_check',
		),
		'tenbis'   => array(
			'relation'                 => 'future_connector',
			'public_exposure'          => false,
			'dish_deep_link_verified'  => false,
			'dish_availability_status' => 'unknown',
		),
		'cibus'    => array(
			'relation'                 => 'future_connector',
			'public_exposure'          => false,
			'dish_deep_link_verified'  => false,
			'dish_availability_status' => 'unknown',
		),
		'spareeat' => array(
			'relation'                 => 'future_connector',
			'public_exposure'          => false,
			'dish_deep_link_verified'  => false,
			'dish_availability_status' => 'unknown',
		),
	);
};

$c99_build_dish_tree = static function ( $record ) use (
	$c99_dish_sources,
	$c99_dish_evidence,
	$c99_dish_allergen_map,
	$c99_dish_connector_relations
) {
	$source_id = $record['source_id'];
	$source    = $c99_dish_sources[ $source_id ];

	return array(
		'dish_id'              => $record['dish_id'],
		'entity_version'       => '1.0.0',
		'schema_version'       => 'complete99-dish-entity-tree/v1',
		'source_record_id'     => $record['dish_id'],
		'source_record_slug'   => $record['slug'],
		'source_version'       => 'consumer-menu-2026-07-31',
		'identity'             => array(
			'slug'              => $record['slug'],
			'name'              => $record['name'],
			'category'          => $record['category'],
			'description'       => $record['description'],
			'editorial_tagline' => $record['editorial_tagline'],
			'evidence'          => $c99_dish_evidence(
				$source_id,
				$record['name']['he'],
				$record['name']['en']
			),
		),
		'component_tree'       => array(
			'root_code'             => 'dish:' . $record['slug'],
			'root_type'             => 'dish',
			'children'              => $record['components'],
			'completeness_status'   => 'menu_components_only',
			'unlisted_components'   => 'unknown',
			'quantities_status'     => 'unknown',
			'substitutions_status'  => 'unknown',
			'production_spec_status' => 'not_in_public_record',
		),
		'serving_formats'      => $record['serving_formats'],
		'preparation'          => $record['preparation'],
		'allergen_information' => array(
			'allowed_states' => array(
				'unknown',
				'contains',
				'may_contain',
				'not_intentionally_used',
				'verified_free_from',
			),
			'allergens'     => $c99_dish_allergen_map( $record['explicit_allergens'], $source_id ),
			'fava_g6pd'     => array(
				'state'                => 'unknown',
				'evidence'             => array(),
				'public_claim_allowed' => false,
				'review_status'        => 'not_reviewed',
			),
			'map_status'    => 'incomplete_pending_recipe_supplier_and_cross_contact_review',
		),
		'nutrition'           => array(
			'method'               => 'unknown',
			'basis'                => 'unknown',
			'values'               => array(),
			'portion_weight_status' => 'unknown',
			'review_status'        => 'not_calculated',
			'public_exposure'      => false,
		),
		'claim_gate'          => array(
			'derived_claims_status' => 'held',
			'publish_by_default'    => false,
			'menu_fact_scope'       => array(
				'identity',
				'explicit_menu_components',
				'explicit_serving_formats',
				'explicit_preparation_method',
			),
			'held_claims'           => array(
				'complete_recipe',
				'allergen_free',
				'cross_contact_safety',
				'nutrition_values',
				'nutrition_claims',
				'health_suitability',
				'medical_suitability',
				'certification',
				'price',
				'stock',
				'current_availability',
				'connector_specific_availability',
			),
		),
		'relations'           => array(
			'guide_codes'      => $record['guide_codes'],
			'ingredient_codes' => $record['ingredient_codes'],
			'product_codes'    => array(),
			'product_status'   => 'no_verified_product_relation',
			'connectors'       => $c99_dish_connector_relations( $source_id ),
		),
		'review'              => array(
			'status' => 'source_seeded_pending_multidisciplinary_review',
			'owners' => array(
				'culinary'        => 'unassigned',
				'food_safety'     => 'unassigned',
				'nutrition'       => 'unassigned',
				'israeli_food_law' => 'unassigned',
				'hebrew_editor'   => 'unassigned',
				'english_editor'  => 'unassigned',
			),
			'requirements' => array(
				'recipe_version'        => false,
				'ingredient_specifications' => false,
				'supplier_declarations' => false,
				'cross_contact_review'  => false,
				'nutrition_calculation' => false,
				'food_safety_signoff'   => false,
				'dietitian_signoff'     => false,
				'legal_claim_review'    => false,
				'hebrew_editorial_review' => false,
				'english_editorial_review' => false,
			),
			'last_source_reviewed' => $source['source_date'],
		),
		'exposure'            => array(
			'public'  => array(
				'identity'                 => true,
				'menu_stated_components'   => true,
				'menu_stated_formats'      => true,
				'menu_stated_preparation'  => 'stated' === $record['preparation']['state'],
				'allergen_map'             => false,
				'nutrition'                => false,
				'health_or_medical_claims' => false,
				'price'                    => false,
				'stock'                    => false,
				'current_availability'     => false,
			),
			'private' => array(
				'evidence_records'          => true,
				'review_workflow'           => true,
				'unverified_safety_fields'  => true,
				'operational_recipe'        => false,
				'supplier_data'             => false,
				'cost_data'                 => false,
				'staff_or_customer_data'    => false,
			),
		),
	);
};

$wolt_source_id   = 'wolt-menu-2026-07-31';
$archive_source_id = 'complete99-archive-chicken-liver-2021';

$unknown_preparation = array(
	'state'    => 'unknown',
	'method'   => null,
	'label'    => array(
		'he' => '',
		'en' => '',
	),
	'evidence' => array(),
);

$records = array(
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-sabich',
			'slug'              => 'sabich',
			'name'              => array( 'he' => 'הסביח של 99', 'en' => 'The 99 Sabich' ),
			'category'          => array( 'he' => 'הקלאסיקה', 'en' => 'The classic' ),
			'description'       => array(
				'he' => 'חציל, ביצה, תפוח אדמה, סלט, טחינה, עמבה וחריף, בפיתה או בצלחת.',
				'en' => 'Aubergine, egg, potato, salad, tahini, amba and hot sauce, in a pita or on a plate.',
			),
			'editorial_tagline' => array( 'he' => 'המנה שהתחילה הכול', 'en' => 'Where it all began' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'ingredient-aubergine', 'ingredient', 'חציל', 'Aubergine', $wolt_source_id ),
				$c99_dish_component( 'ingredient-egg', 'ingredient', 'ביצה', 'Egg', $wolt_source_id ),
				$c99_dish_component( 'ingredient-potato', 'ingredient', 'תפוח אדמה', 'Potato', $wolt_source_id ),
				$c99_dish_component( 'component-salad', 'culinary_component', 'סלט', 'Salad', $wolt_source_id ),
				$c99_dish_component( 'ingredient-tahini', 'ingredient_or_sauce', 'טחינה', 'Tahini', $wolt_source_id ),
				$c99_dish_component( 'ingredient-amba', 'condiment', 'עמבה', 'Amba', $wolt_source_id ),
				$c99_dish_component( 'component-hot-sauce', 'sauce', 'חריף', 'Hot sauce', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'pita', 'בפיתה', 'In a pita', $wolt_source_id ),
				$c99_dish_serving_format( 'plate', 'בצלחת', 'On a plate', $wolt_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(
				'eggs' => array(
					'component_codes' => array( 'ingredient-egg' ),
					'statement_he'    => 'הביצה מופיעה במפורש בתיאור המנה.',
					'statement_en'    => 'Egg is explicitly named in the dish description.',
				),
			),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-gluten-free',
				'nutrition-guide-vegan-vegetarian',
				'nutrition-guide-calculation-method',
			),
			'ingredient_codes'  => array(
				'ingredient-aubergine',
				'ingredient-egg',
				'ingredient-potato',
				'ingredient-tahini',
				'ingredient-amba',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-beet-kubbeh',
			'slug'              => 'beet-kubbeh',
			'name'              => array( 'he' => 'קובה סלק', 'en' => 'Beet Kubbeh' ),
			'category'          => array( 'he' => 'מהסירים', 'en' => 'From the pots' ),
			'description'       => array(
				'he' => 'מרק סלק חמוץ ומתוק עם קובה במילוי בשר.',
				'en' => 'Sweet and sour beet soup with meat-filled kubbeh.',
			),
			'editorial_tagline' => array( 'he' => 'אוכל של בית', 'en' => 'Home cooking' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-beet-soup', 'culinary_component', 'מרק סלק חמוץ ומתוק', 'Sweet and sour beet soup', $wolt_source_id ),
				$c99_dish_component( 'component-meat-filled-kubbeh', 'culinary_component', 'קובה במילוי בשר', 'Meat-filled kubbeh', $wolt_source_id ),
			),
			'serving_formats'   => array(),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-calculation-method',
				'nutrition-guide-portions-balance',
			),
			'ingredient_codes'  => array(
				'ingredient-beet',
				'component-kubbeh',
				'ingredient-meat-unspecified',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-schnitzel',
			'slug'              => 'schnitzel',
			'name'              => array( 'he' => 'שניצל', 'en' => 'Israeli Schnitzel' ),
			'category'          => array( 'he' => 'פיתה או צלחת', 'en' => 'Pita or plate' ),
			'description'       => array(
				'he' => 'שניצל עם סלטים ורטבים, בפיתה או כארוחה בצלחת.',
				'en' => 'Schnitzel with salads and sauces, in a pita or as a full plate.',
			),
			'editorial_tagline' => array( 'he' => 'צהריים בלי סיבוך', 'en' => 'An easy lunch' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-schnitzel', 'culinary_component', 'שניצל', 'Schnitzel', $wolt_source_id ),
				$c99_dish_component( 'component-salads', 'culinary_component', 'סלטים', 'Salads', $wolt_source_id ),
				$c99_dish_component( 'component-sauces', 'culinary_component', 'רטבים', 'Sauces', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'pita', 'בפיתה', 'In a pita', $wolt_source_id ),
				$c99_dish_serving_format( 'plate', 'ארוחה בצלחת', 'Full plate', $wolt_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-gluten-free',
				'nutrition-guide-calculation-method',
				'nutrition-guide-portions-balance',
			),
			'ingredient_codes'  => array(
				'component-schnitzel',
				'component-salads',
				'component-sauces',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-shakshuka',
			'slug'              => 'shakshuka',
			'name'              => array( 'he' => 'שקשוקה', 'en' => 'Shakshuka' ),
			'category'          => array( 'he' => 'מהמחבת', 'en' => 'From the pan' ),
			'description'       => array(
				'he' => 'רוטב עגבניות וביצים, בפיתה או בצלחת.',
				'en' => 'Tomato sauce and eggs, in a pita or on a plate.',
			),
			'editorial_tagline' => array( 'he' => 'עגבניות וביצים', 'en' => 'Tomato and eggs' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-tomato-sauce', 'sauce', 'רוטב עגבניות', 'Tomato sauce', $wolt_source_id ),
				$c99_dish_component( 'ingredient-egg', 'ingredient', 'ביצים', 'Eggs', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'pita', 'בפיתה', 'In a pita', $wolt_source_id ),
				$c99_dish_serving_format( 'plate', 'בצלחת', 'On a plate', $wolt_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(
				'eggs' => array(
					'component_codes' => array( 'ingredient-egg' ),
					'statement_he'    => 'ביצים מופיעות במפורש בתיאור המנה.',
					'statement_en'    => 'Eggs are explicitly named in the dish description.',
				),
			),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-gluten-free',
				'nutrition-guide-vegan-vegetarian',
				'nutrition-guide-calculation-method',
			),
			'ingredient_codes'  => array(
				'ingredient-tomato',
				'ingredient-egg',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-homemade-meatballs',
			'slug'              => 'homemade-meatballs',
			'name'              => array( 'he' => 'קציצות ביתיות', 'en' => 'Home-style Meatballs' ),
			'category'          => array( 'he' => 'אוכל ביתי', 'en' => 'Home cooking' ),
			'description'       => array(
				'he' => 'קציצות ברוטב עם תוספת חמה.',
				'en' => 'Meatballs in sauce with a warm side.',
			),
			'editorial_tagline' => array( 'he' => 'מהמטבח הביתי', 'en' => 'From the home kitchen' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-meatballs', 'culinary_component', 'קציצות', 'Meatballs', $wolt_source_id ),
				$c99_dish_component( 'component-sauce-unspecified', 'sauce', 'רוטב', 'Sauce', $wolt_source_id ),
				$c99_dish_component( 'component-warm-side-unspecified', 'side', 'תוספת חמה', 'Warm side', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'plate', 'צלחת', 'Plate', $wolt_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-protein',
				'nutrition-guide-calculation-method',
				'nutrition-guide-portions-balance',
			),
			'ingredient_codes'  => array(
				'component-meatballs',
				'component-sauce-unspecified',
				'component-warm-side-unspecified',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-fish-patties',
			'slug'              => 'fish-patties',
			'name'              => array( 'he' => 'קציצות דגים', 'en' => 'Fish Patties' ),
			'category'          => array( 'he' => 'אוכל ביתי', 'en' => 'Home cooking' ),
			'description'       => array(
				'he' => 'קציצות דגים ברוטב עגבניות.',
				'en' => 'Fish patties in tomato sauce.',
			),
			'editorial_tagline' => array( 'he' => 'רוטב של בית', 'en' => 'A proper house sauce' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-fish-patties', 'culinary_component', 'קציצות דגים', 'Fish patties', $wolt_source_id ),
				$c99_dish_component( 'component-tomato-sauce', 'sauce', 'רוטב עגבניות', 'Tomato sauce', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'plate', 'צלחת', 'Plate', $wolt_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(
				'fish' => array(
					'component_codes' => array( 'component-fish-patties' ),
					'statement_he'    => 'דגים מופיעים במפורש בשם ובתיאור המנה.',
					'statement_en'    => 'Fish is explicitly named in the dish name and description.',
				),
			),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-protein',
				'nutrition-guide-calculation-method',
				'nutrition-guide-portions-balance',
			),
			'ingredient_codes'  => array(
				'ingredient-fish',
				'ingredient-tomato',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-grilled-chicken',
			'slug'              => 'grilled-chicken',
			'name'              => array( 'he' => 'חזה עוף על הפלנצ׳ה', 'en' => 'Griddled Chicken Breast' ),
			'category'          => array( 'he' => 'פיתה או צלחת', 'en' => 'Pita or plate' ),
			'description'       => array(
				'he' => 'חזה עוף על הפלנצ׳ה עם סלטים ותוספות, בפיתה או בצלחת.',
				'en' => 'Griddled chicken breast with salads and sides, in a pita or on a plate.',
			),
			'editorial_tagline' => array( 'he' => 'פיתה או צלחת', 'en' => 'Pita or plate' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'ingredient-chicken-breast', 'ingredient', 'חזה עוף', 'Chicken breast', $wolt_source_id ),
				$c99_dish_component( 'component-salads', 'culinary_component', 'סלטים', 'Salads', $wolt_source_id ),
				$c99_dish_component( 'component-sides-unspecified', 'side', 'תוספות', 'Sides', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'pita', 'בפיתה', 'In a pita', $wolt_source_id ),
				$c99_dish_serving_format( 'plate', 'בצלחת', 'On a plate', $wolt_source_id ),
			),
			'preparation'       => array(
				'state'    => 'stated',
				'method'   => 'griddled_on_plancha',
				'label'    => array( 'he' => 'על הפלנצ׳ה', 'en' => 'Griddled' ),
				'evidence' => array(
					$c99_dish_evidence( $wolt_source_id, 'על הפלנצ׳ה', 'Griddled' ),
				),
			),
			'explicit_allergens' => array(),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-protein',
				'nutrition-guide-calculation-method',
				'nutrition-guide-portions-balance',
			),
			'ingredient_codes'  => array(
				'ingredient-chicken-breast',
				'component-salads',
				'component-sides-unspecified',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-aja',
			'slug'              => 'aja-herb-omelet',
			'name'              => array( 'he' => 'עיג׳ה, חביתת ירק', 'en' => 'Aja Herb Omelette' ),
			'category'          => array( 'he' => 'מסורת בפיתה', 'en' => 'Tradition in a pita' ),
			'description'       => array(
				'he' => 'חביתת ירק עם סלטים ורטבים, בפיתה או בצלחת.',
				'en' => 'A herb omelette with salads and sauces, in a pita or on a plate.',
			),
			'editorial_tagline' => array( 'he' => 'ירק, ביצים וטעם', 'en' => 'Herbs, eggs and flavour' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-herb-omelette', 'culinary_component', 'חביתת ירק', 'Herb omelette', $wolt_source_id ),
				$c99_dish_component( 'component-salads', 'culinary_component', 'סלטים', 'Salads', $wolt_source_id ),
				$c99_dish_component( 'component-sauces', 'culinary_component', 'רטבים', 'Sauces', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'pita', 'בפיתה', 'In a pita', $wolt_source_id ),
				$c99_dish_serving_format( 'plate', 'בצלחת', 'On a plate', $wolt_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(
				'eggs' => array(
					'component_codes' => array( 'component-herb-omelette' ),
					'statement_he'    => 'ביצים מופיעות במפורש בשורת התיאור של המנה.',
					'statement_en'    => 'Eggs are explicitly named in the dish tagline and omelette identity.',
				),
			),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-gluten-free',
				'nutrition-guide-vegan-vegetarian',
				'nutrition-guide-calculation-method',
			),
			'ingredient_codes'  => array(
				'ingredient-egg',
				'ingredient-herbs-unspecified',
				'component-salads',
				'component-sauces',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-couscous',
			'slug'              => 'couscous',
			'name'              => array( 'he' => 'קוסקוס', 'en' => 'Couscous' ),
			'category'          => array( 'he' => 'מהסירים', 'en' => 'From the pots' ),
			'description'       => array(
				'he' => 'קוסקוס, ירקות ותבשיל משתנה.',
				'en' => 'Couscous, vegetables and a changing stew.',
			),
			'editorial_tagline' => array( 'he' => 'יום של קוסקוס', 'en' => 'A couscous kind of day' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-couscous', 'culinary_component', 'קוסקוס', 'Couscous', $wolt_source_id ),
				$c99_dish_component( 'component-vegetables', 'culinary_component', 'ירקות', 'Vegetables', $wolt_source_id ),
				$c99_dish_component( 'component-changing-stew', 'culinary_component', 'תבשיל משתנה', 'Changing stew', $wolt_source_id ),
			),
			'serving_formats'   => array(),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-gluten-free',
				'nutrition-guide-fibre',
				'nutrition-guide-calculation-method',
			),
			'ingredient_codes'  => array(
				'component-couscous',
				'component-vegetables',
				'component-changing-stew',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-yemenite-soup',
			'slug'              => 'yemenite-beef-soup',
			'name'              => array( 'he' => 'מרק בשר תימני', 'en' => 'Yemenite Beef Soup' ),
			'category'          => array( 'he' => 'מרק ביתי', 'en' => 'Home-style soup' ),
			'description'       => array(
				'he' => 'מרק בשר תימני עמוק ומחמם.',
				'en' => 'A deep, warming Yemenite beef soup.',
			),
			'editorial_tagline' => array( 'he' => 'סיר עמוק', 'en' => 'A deep, warming pot' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-yemenite-beef-soup', 'culinary_component', 'מרק בשר תימני', 'Yemenite beef soup', $wolt_source_id ),
			),
			'serving_formats'   => array(),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-protein',
				'nutrition-guide-sodium',
				'nutrition-guide-calculation-method',
			),
			'ingredient_codes'  => array(
				'ingredient-beef',
				'component-soup',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-sabtucha',
			'slug'              => 'sabtucha',
			'name'              => array( 'he' => 'סבטוחה', 'en' => 'Sabtucha' ),
			'category'          => array( 'he' => 'הבית של הסביח', 'en' => 'From the sabich family' ),
			'description'       => array(
				'he' => 'חיבור מקומי בין סביח לביצת שקשוקה, עם חציל, תפוח אדמה וסלטים.',
				'en' => 'A local meeting of sabich and shakshuka egg, with aubergine, potato and salads.',
			),
			'editorial_tagline' => array( 'he' => 'שתי קלאסיקות בפיתה', 'en' => 'Two classics in one pita' ),
			'source_id'         => $wolt_source_id,
			'components'        => array(
				$c99_dish_component( 'component-sabich', 'culinary_component', 'סביח', 'Sabich', $wolt_source_id ),
				$c99_dish_component( 'component-shakshuka-egg', 'culinary_component', 'ביצת שקשוקה', 'Shakshuka egg', $wolt_source_id ),
				$c99_dish_component( 'ingredient-aubergine', 'ingredient', 'חציל', 'Aubergine', $wolt_source_id ),
				$c99_dish_component( 'ingredient-potato', 'ingredient', 'תפוח אדמה', 'Potato', $wolt_source_id ),
				$c99_dish_component( 'component-salads', 'culinary_component', 'סלטים', 'Salads', $wolt_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'pita', 'בפיתה', 'In a pita', $wolt_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(
				'eggs' => array(
					'component_codes' => array( 'component-shakshuka-egg' ),
					'statement_he'    => 'ביצת שקשוקה מופיעה במפורש בתיאור המנה.',
					'statement_en'    => 'Shakshuka egg is explicitly named in the dish description.',
				),
			),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-gluten-free',
				'nutrition-guide-vegan-vegetarian',
				'nutrition-guide-calculation-method',
			),
			'ingredient_codes'  => array(
				'ingredient-egg',
				'ingredient-aubergine',
				'ingredient-potato',
				'component-salads',
			),
		)
	),
	$c99_build_dish_tree(
		array(
			'dish_id'           => 'menu-reference-chicken-liver',
			'slug'              => 'chicken-liver',
			'name'              => array( 'he' => 'כבד עוף', 'en' => 'Chicken Liver' ),
			'category'          => array( 'he' => 'אוכל ביתי', 'en' => 'Home cooking' ),
			'description'       => array(
				'he' => 'מנה ביתית עם תוספת חמה.',
				'en' => 'A home-style plate with a warm side.',
			),
			'editorial_tagline' => array( 'he' => 'טעם עמוק', 'en' => 'Deep, comforting flavour' ),
			'source_id'         => $archive_source_id,
			'components'        => array(
				$c99_dish_component( 'ingredient-chicken-liver', 'archive_named_component', 'כבד עוף', 'Chicken liver', $archive_source_id ),
				$c99_dish_component( 'component-warm-side-unspecified', 'archive_visible_component', 'תוספת חמה', 'Warm side', $archive_source_id ),
			),
			'serving_formats'   => array(
				$c99_dish_serving_format( 'plate', 'צלחת', 'Plate', $archive_source_id ),
			),
			'preparation'       => $unknown_preparation,
			'explicit_allergens' => array(),
			'guide_codes'       => array(
				'nutrition-guide-allergens-cross-contact',
				'nutrition-guide-protein',
				'nutrition-guide-calculation-method',
				'nutrition-guide-portions-balance',
			),
			'ingredient_codes'  => array(
				'ingredient-chicken-liver',
				'component-warm-side-unspecified',
			),
		)
	),
);

return array(
	'schema'               => 'complete99-dish-entity-tree-registry/v1',
	'registry_reviewed_at' => '2026-07-31',
	'allowed_states'       => array(
		'unknown',
		'contains',
		'may_contain',
		'not_intentionally_used',
		'verified_free_from',
	),
	'source_registry'      => $c99_dish_sources,
	'dishes'               => $records,
);
