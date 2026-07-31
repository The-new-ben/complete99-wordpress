<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Public culinary facets are deliberately limited to observable menu format
 * and food-family facts. They are not nutrition, allergen or medical claims.
 * Sensitive claims can be added only through an approved public dish snapshot.
 */
return array(
	'filters' => array(
		'all'        => array( 'he' => 'כל המנות', 'en' => 'All dishes' ),
		'pita'       => array( 'he' => 'בפיתה', 'en' => 'In a pita' ),
		'plate'      => array( 'he' => 'בצלחת', 'en' => 'On a plate' ),
		'pots'       => array( 'he' => 'מהסירים', 'en' => 'From the pots' ),
		'vegetarian' => array( 'he' => 'בתפריט הצמחוני', 'en' => 'Vegetarian menu' ),
		'meat'       => array( 'he' => 'בשרי', 'en' => 'Meat' ),
		'fish'       => array( 'he' => 'דגים', 'en' => 'Fish' ),
	),
	'badges' => array(
		'pita'       => array( 'he' => 'בפיתה', 'en' => 'Pita' ),
		'plate'      => array( 'he' => 'בצלחת', 'en' => 'Plate' ),
		'pots'       => array( 'he' => 'מהסירים', 'en' => 'From the pots' ),
		'pan'        => array( 'he' => 'מהמחבת', 'en' => 'From the pan' ),
		'griddled'   => array( 'he' => 'על הפלנצ׳ה', 'en' => 'Griddled' ),
		'vegetarian' => array( 'he' => 'בתפריט הצמחוני', 'en' => 'Vegetarian menu' ),
		'meat'       => array( 'he' => 'בשרי', 'en' => 'Meat' ),
		'fish'       => array( 'he' => 'דגים', 'en' => 'Fish' ),
		'picante'    => array( 'he' => 'פיקנטי', 'en' => 'Picante' ),
	),
	'allergen_states' => array(
		'unknown',
		'contains',
		'may_contain',
		'not_intentionally_used',
		'verified_free_from',
	),
	'nutrition_methods' => array(
		'unknown',
		'recipe_estimate',
		'recipe_calculated_with_yield',
		'laboratory_verified',
	),
	'claim_review_states' => array(
		'not_requested',
		'verification_required',
		'dietitian_review_required',
		'legal_review_required',
		'approved',
		'expired',
	),
);
