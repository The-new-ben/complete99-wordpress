<?php
/**
 * Complete99 Culinary Science Museum, Japanese pilot registry.
 *
 * This registry contains sourced knowledge and held commerce architecture. It
 * does not create a supplier relationship, a live product, a current offer or
 * permission to use a third party logo or photograph.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_text = static function ( $he, $en ) {
	return array(
		'he' => $he,
		'en' => $en,
	);
};

$c99_profile = static function ( $state, $he, $en, $fact_ids = array() ) use ( $c99_text ) {
	return array(
		'state'    => $state,
		'summary'  => $c99_text( $he, $en ),
		'fact_ids' => $fact_ids,
	);
};

$c99_profiles = static function ( $overrides = array() ) use ( $c99_profile ) {
	$profiles = array(
		'scientific'    => $c99_profile( 'pending_evidence', 'הפרופיל המדעי ממתין למקור מתאים לסוג הישות.', 'The scientific profile awaits evidence appropriate to this entity type.' ),
		'cultural'      => $c99_profile( 'pending_evidence', 'ההקשר התרבותי ייכתב רק ממקורות מזוהים.', 'Cultural context will be written only from identified sources.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'הקשרים למוסדות ולבעלי סמכות עדיין נאספים.', 'Institutional and authority relationships are still being collected.' ),
		'economic'      => $c99_profile( 'pending_evidence', 'הערך הכלכלי דורש תצפית שוק מתוארכת ובסיס השוואה ברור.', 'Economic value requires a dated market observation and a clear comparison basis.' ),
		'structural'    => $c99_profile( 'pending_evidence', 'מיקום הישות בגרף יושלם לאחר אימות הקשרים.', 'The entity position in the graph will be completed after relation review.' ),
	);
	foreach ( $overrides as $dimension => $profile ) {
		$profiles[ $dimension ] = $profile;
	}
	return $profiles;
};

$c99_fact = static function ( $id, $dimension, $he, $en, $evidence_class, $value_scope, $source_ids, $public_safe = true, $measurement = array(), $observed_at = '', $scientific_measurements = array() ) use ( $c99_text ) {
	return array(
		'id'             => $id,
		'dimension'      => $dimension,
		'statement'      => $c99_text( $he, $en ),
		'evidence_class' => $evidence_class,
		'value_scope'     => $value_scope,
		'source_ids'     => $source_ids,
		'verified_at'    => '' !== $observed_at ? substr( $observed_at, 0, 10 ) : '2026-08-06',
		'observed_at'    => $observed_at,
		'public_safe'    => $public_safe,
		'measurement'    => $measurement,
		'scientific_measurements' => $scientific_measurements,
	);
};

$c99_relation = static function ( $type, $target_id, $he, $en, $public_safe = false, $source_ids = array(), $evidence_class = 'editorial_inference' ) use ( $c99_text ) {
	return array(
		'id'          => 'edge-pending',
		'type'        => $type,
		'target_id'   => $target_id,
		'public_safe' => $public_safe && ! empty( $source_ids ),
		'note'        => $c99_text( $he, $en ),
		'evidence_class' => $evidence_class,
		'source_ids'  => $source_ids,
		'valid_from'  => '2026-08-05',
		'valid_to'    => '',
		'confidence'  => empty( $source_ids ) ? 'pending' : 'reviewed',
	);
};

$c99_compliance = static function ( $code, $he, $en, $source_ids = array(), $public_safe = true ) use ( $c99_text ) {
	return array(
		'code'        => $code,
		'note'        => $c99_text( '[COMPLIANCE_NOTE: ' . $he . ']', '[COMPLIANCE_NOTE: ' . $en . ']' ),
		'public_safe' => $public_safe && ! empty( $source_ids ),
		'source_ids'  => $source_ids,
	);
};

$c99_entity = static function ( $config ) use ( $c99_text, $c99_profiles ) {
	$type       = $config['type'];
	$slug       = $config['slug'];
	$name       = $config['name'];
	$group      = isset( $config['seo_group'] ) ? $config['seo_group'] : 'entities';
	$summary    = $config['summary'];
	$primary    = $config['primary_keyword'];
	$secondary  = isset( $config['secondary_keywords'] ) ? $config['secondary_keywords'] : array( 'he' => array(), 'en' => array() );
	$attributes = isset( $config['attributes'] ) ? $config['attributes'] : array();
	$tags       = isset( $config['tags'] ) ? $config['tags'] : array();
	$categories = isset( $config['categories'] ) ? $config['categories'] : array( 'culinary-museum', $group );
	$relations  = isset( $config['relations'] ) ? $config['relations'] : array();
	$cross      = isset( $config['cross_sell_ids'] ) ? $config['cross_sell_ids'] : array();
	$upsell     = isset( $config['up_sell_ids'] ) ? $config['up_sell_ids'] : array();
	$compliance = isset( $config['compliance'] ) ? $config['compliance'] : array();
	$surface    = isset( $config['surface_class'] ) ? $config['surface_class'] : 'public_discovery';
	$review     = isset( $config['review_status'] ) ? $config['review_status'] : ( 'public_discovery' === $surface ? 'source_reviewed' : 'research_draft' );
	$next_review = isset( $config['next_review_at'] ) ? $config['next_review_at'] : '2027-08-05';
	$page_role  = isset( $config['page_role'] ) ? $config['page_role'] : 'spoke';
	$intents    = isset( $config['intent_classes'] ) ? $config['intent_classes'] : array( 'informational' );
	if ( in_array( $type, array( 'ingredient', 'equipment', 'quality_grade', 'market', 'equipment_shop', 'producer', 'supplier' ), true ) ) {
		$intents[] = 'commercial';
	}
	if ( in_array( $type, array( 'restaurant', 'market', 'equipment_shop', 'producer', 'culinary_institution' ), true ) ) {
		$intents[] = 'navigational';
	}
	$intents = array_values( array_unique( $intents ) );
	$revenue_models = isset( $config['revenue_models'] ) ? $config['revenue_models'] : array( 'content_to_commerce' );
	$customer_segments = isset( $config['customer_segments'] ) ? $config['customer_segments'] : array( 'culinary_consumers' );
	if ( in_array( $type, array( 'ingredient', 'equipment', 'material_specification', 'retail_listing' ), true ) ) {
		$revenue_models = isset( $config['revenue_models'] ) ? $config['revenue_models'] : array( 'retail_product', 'curated_bundle', 'content_to_commerce' );
		$customer_segments = isset( $config['customer_segments'] ) ? $config['customer_segments'] : array( 'culinary_consumers', 'professional_chefs', 'foodservice_buyers' );
	}
	$margin_scenario = isset( $config['margin_scenario'] ) ? $config['margin_scenario'] : array(
		'currency'         => '',
		'landed_cost_low'  => null,
		'landed_cost_high' => null,
		'retail_price_low' => null,
		'retail_price_high'=> null,
		'gross_margin_low' => null,
		'gross_margin_high'=> null,
		'basis'            => $c99_text( 'טרם נבנה תרחיש מרווח מאומת. יש להשלים מחיר מקור, שילוח, מכס, מע״מ, פחת, אריזה ועלות טיפול לפני אישור רווחיות.', 'No verified margin scenario exists yet. Source price, freight, customs, VAT, shrinkage, packaging and handling must be completed before profitability is approved.' ),
		'confidence'       => 'pending',
		'reviewed_at'      => '',
	);
	$query_variants = array(
		'he' => array_values( array_unique( array_merge( array( $primary['he'] ), $secondary['he'] ) ) ),
		'en' => array_values( array_unique( array_merge( array( $primary['en'] ), $secondary['en'] ) ) ),
	);
	$he_terms = array_values( array_unique( array_merge( array( $name['he'] ), $secondary['he'] ) ) );
	$en_terms = array_values( array_unique( array_merge( array( $name['en'] ), $secondary['en'] ) ) );
	$protected = isset( $config['protected_exclusions'] ) ? $config['protected_exclusions'] : array(
		'he' => array( 'הצעת ספק פעילה', 'הוראות תפעול פנימיות' ),
		'en' => array( 'active supplier offer', 'private operating instructions' ),
	);

	return array(
		'id'            => $config['id'],
		'type'          => $type,
		'slug'          => $slug,
		'parent_id'     => isset( $config['parent_id'] ) ? $config['parent_id'] : '',
		'name'          => $config['name'],
		'summary'       => $summary,
		'surface_class' => $surface,
		'index_policy'  => isset( $config['index_policy'] ) ? $config['index_policy'] : 'noindex_until_longform_review',
		'publication'   => array(
			'state'        => isset( $config['publication_state'] ) ? $config['publication_state'] : 'private_preview',
			'public_api'   => isset( $config['public_api'] ) ? (bool) $config['public_api'] : false,
			'public_page'  => isset( $config['public_page'] ) ? (bool) $config['public_page'] : false,
			'search_index' => isset( $config['search_index'] ) ? (bool) $config['search_index'] : false,
			'approved_at'  => isset( $config['approved_at'] ) ? $config['approved_at'] : '',
		),
		'seo'           => array(
			'page_role'          => $page_role,
			'route_mode'         => 'standalone',
			'owner_entity_id'    => $config['id'],
			'section_id'         => '',
			'cluster_id'         => 'cluster-unassigned',
			'hub_entity_id'      => $config['id'],
			'intent_classes'     => $intents,
			'primary_intent'     => $config['primary_intent'],
			'primary_keyword'    => $primary,
			'query_variants'     => $query_variants,
			'term_variants'      => array( 'he' => $he_terms, 'en' => $en_terms ),
			'semantic_entity_ids'=> array(),
			'protected_exclusions'=> $protected,
			'protected_owner_ids'=> isset( $config['protected_owner_ids'] ) ? $config['protected_owner_ids'] : array(),
			'canonical_path'     => $c99_text( '/museum/' . $group . '/' . $slug . '/', '/en/museum/' . $group . '/' . $slug . '/' ),
			'title'              => $c99_text( $primary['he'] . ' | Complete99', $primary['en'] . ' | Complete99' ),
			'h1'                 => $name,
			'meta_description'   => $c99_text( $name['he'] . ': מדע, מורשת, איכות וקשרים קולינריים במוזיאון המדע של הקולינריה.', $name['en'] . ': science, heritage, quality and culinary connections in the Culinary Science Museum.' ),
			'opening'            => $summary,
			'schema_type'        => $config['schema_type'],
			'breadcrumb_entity_ids' => array( $config['id'] ),
			'visible_breadcrumbs' => array(
				array( 'key' => 'home', 'label' => $c99_text( 'בית', 'Home' ), 'path' => $c99_text( '/', '/en/' ) ),
			),
			'expected_child_ids' => array(),
			'link_plan'          => array(),
		),
		'profiles'      => isset( $config['profiles'] ) ? $config['profiles'] : $c99_profiles(),
		'facts'         => $config['facts'],
		'taxonomy'      => array(
			'category_path' => $categories,
			'attributes'    => $attributes,
			'tags'          => $tags,
			'public_category_path' => isset( $config['public_category_path'] ) ? $config['public_category_path'] : array(),
			'public_attribute_keys' => isset( $config['public_attribute_keys'] ) ? $config['public_attribute_keys'] : array(),
			'public_tags'          => isset( $config['public_tags'] ) ? $config['public_tags'] : array(),
		),
		'relations'     => $relations,
		'commerce'      => array(
			'state'                => isset( $config['commerce_state'] ) ? $config['commerce_state'] : 'reference_only',
			'woo_product_code'     => isset( $config['woo_product_code'] ) ? $config['woo_product_code'] : '',
			'public_offer_allowed' => isset( $config['public_offer_allowed'] ) ? (bool) $config['public_offer_allowed'] : false,
			'product_copy'         => isset( $config['product_copy'] ) ? $config['product_copy'] : $summary,
			'cross_sell_ids'       => $cross,
			'up_sell_ids'          => $upsell,
			'business_model'        => array(
				'revenue_models'        => $revenue_models,
				'customer_segments'     => $customer_segments,
				'value_proposition'      => isset( $config['value_proposition'] ) ? $config['value_proposition'] : $summary,
				'pricing_state'         => isset( $config['pricing_state'] ) ? $config['pricing_state'] : 'research_required',
				'market_scope'          => isset( $config['market_scope'] ) ? $config['market_scope'] : 'israel_launch',
				'observation_entity_ids'=> isset( $config['observation_entity_ids'] ) ? $config['observation_entity_ids'] : array(),
				'margin_scenario'       => $margin_scenario,
			),
		),
		'visual'        => array(
			'asset_state'       => isset( $config['asset_state'] ) ? $config['asset_state'] : 'spec_ready',
			'prompt_en'         => $config['prompt_en'],
			'negative_prompt_en'=> isset( $config['negative_prompt_en'] ) ? $config['negative_prompt_en'] : 'No text, no logos, no certification seals, no false brand packaging, no watermarks, no distorted utensils or hands.',
			'ratios'            => array( '1:1', '4:5', '4:3', '16:9' ),
			'shot_list'         => isset( $config['shot_list'] ) ? $config['shot_list'] : array( 'hero', 'macro texture', 'process detail', 'transparent cutout' ),
			'rights_method'     => isset( $config['rights_method'] ) ? $config['rights_method'] : 'generated_concept_with_human_review',
			'rights_state'      => isset( $config['rights_state'] ) ? $config['rights_state'] : 'pending',
			'rights_receipt_digest' => isset( $config['rights_receipt_digest'] ) ? $config['rights_receipt_digest'] : '',
		),
		'compliance'    => $compliance,
		'trust'         => array(
			'attribution_state'    => isset( $config['attribution_state'] ) ? $config['attribution_state'] : 'pending_named_review',
			'research_method'      => $c99_text( 'כל עובדה נשמרת בנפרד עם סוג ראיה, מקור, תאריך והיקף. ערך ספרותי אינו מוצג כמדידת מוצר או אצווה.', 'Each fact is stored with an evidence class, source, date and scope. A literature value is not presented as a product or lot measurement.' ),
			'user_purpose'         => $config['primary_intent'],
			'commercial_purpose'   => $c99_text( 'העמוד מסביר את הנושא ויכול לקשר להצעה מסחרית רק לאחר אימות מוצר, ספק, מחיר וזמינות.', 'The page explains the topic and may link to a commercial offer only after product, supplier, price and availability verification.' ),
			'correction_path'      => $c99_text( '/contact/', '/en/contact/' ),
			'substantive_updated_at' => '2026-08-05',
			'next_review_trigger'  => $c99_text( 'עדכון מקור, שינוי תקן, שינוי סטטוס מוסדי, תצפית מחיר חדשה או תיקון מבוסס מפעילים בדיקה מחודשת.', 'A source update, standard change, institutional status change, new price observation or substantiated correction triggers review.' ),
		),
		'review'        => array(
			'status'               => $review,
			'reviewed_at'          => '2026-08-05',
			'next_review_at'       => $next_review,
			'language_status'      => 'draft_bilingual',
			'culinary_test_status' => isset( $config['culinary_test_status'] ) ? $config['culinary_test_status'] : 'not_applicable',
		),
	);
};

$sources = array(
	'complete99-public-site' => array(
		'type'         => 'official_business',
		'publisher'    => 'Complete99',
		'title'        => 'Complete99 public culinary website',
		'url'          => 'https://complete99.co.il/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'unesco-washoku' => array(
		'type'         => 'official_organization',
		'publisher'    => 'UNESCO',
		'title'        => 'Washoku, traditional dietary cultures of the Japanese',
		'url'          => 'https://ich.unesco.org/en/RL/washoku-traditional-dietary-cultures-of-the-japanese-notably-for-the-celebration-of-new-year-00869',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'maff-edomae' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'Edomae cuisine',
		'url'          => 'https://www.maff.go.jp/e/policies/market/k_ryouri/areastory/1272/index.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'maff-fermented-foods' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'An Introduction to Japanese Fermented Foods',
		'url'          => 'https://www.maff.go.jp/j/keikaku/syokubunka/traditional-foods/files/user/pdf/an_Introduction_to_Japanese_fermented_foods.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'jas-shoyu-1703' => array(
		'type'         => 'official_standard',
		'publisher'    => 'Food and Agricultural Materials Inspection Center',
		'title'        => 'JAS 1703 Soy Sauce',
		'url'          => 'https://www.famic.go.jp/english/jas/_doc/jas1703.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'umami-receptor-2009' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Proceedings of the National Academy of Sciences',
		'title'        => 'Molecular mechanism for the umami taste synergism',
		'url'          => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC2606899/',
		'published_at' => '2009-01-06',
		'retrieved_at' => '2026-08-05',
	),
	'koshihikari-genome-2018' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'PubMed',
		'title'        => 'Genome-wide association study of eating and cooking quality in rice',
		'url'          => 'https://pubmed.ncbi.nlm.nih.gov/29629486/',
		'published_at' => '2018-04-09',
		'retrieved_at' => '2026-08-05',
	),
	'fda-sushi-rice-2022' => array(
		'type'         => 'regulatory_guidance',
		'publisher'    => 'United States Food and Drug Administration',
		'title'        => 'Supplement to the 2022 Food Code, sushi rice acidification guidance context',
		'url'          => 'https://www.fda.gov/media/183271/download',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'fda-fish-hazards-2022' => array(
		'type'         => 'regulatory_guidance',
		'publisher'    => 'United States Food and Drug Administration',
		'title'        => 'Fish and Fishery Products Hazards and Controls Guidance, June 2022',
		'url'          => 'https://www.fda.gov/regulatory-information/search-fda-guidance-documents/guidance-industry-fish-and-fishery-products-hazards-and-controls',
		'published_at' => '2022-06-01',
		'retrieved_at' => '2026-08-06',
	),
	'israel-moh-fish-safety' => array(
		'type'         => 'official_government',
		'publisher'    => 'Israel Ministry of Health',
		'title'        => 'Safe meat, poultry and fish',
		'url'          => 'https://www.gov.il/en/pages/meat-chicken-and-fish?chapterindex=4',
		'published_at' => '2022-04-03',
		'retrieved_at' => '2026-08-06',
	),
	'wasabi-itc-2023' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Breeding Science',
		'title'        => 'Genetic and seasonal variation of isothiocyanates in wasabi',
		'url'          => 'https://www.jstage.jst.go.jp/article/jsbbs/73/3/73_22080/_html/-char/en',
		'published_at' => '2023-10-17',
		'retrieved_at' => '2026-08-05',
	),
	'yamamoto-haganezame-spec' => array(
		'type'         => 'official_business',
		'publisher'    => 'Yamamoto Foods Co., Ltd.',
		'title'        => 'Hagane-zame wasabi grater specifications',
		'url'          => 'https://www.yamamotofoods.co.jp/haganezame/jp/spec/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'yuzu-aroma-2009' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Journal of Agricultural and Food Chemistry',
		'title'        => 'Novel Character Impact Compounds in Yuzu (Citrus junos Sieb. ex Tanaka) Peel Oil',
		'url'          => 'https://pubs.acs.org/doi/10.1021/jf803257x',
		'published_at' => '2009-02-09',
		'retrieved_at' => '2026-08-05',
	),
	'yuzu-volatiles-2017' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Phytochemical Analysis',
		'title'        => 'Determination of Volatile Flavour Profiles of Citrus Species Fruits',
		'url'          => 'https://pubmed.ncbi.nlm.nih.gov/28444796/',
		'published_at' => '2017-04-25',
		'retrieved_at' => '2026-08-06',
	),
	'kito-yuzu-gi' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'Kito Yuzu Geographical Indication registration 42',
		'url'          => 'https://www.maff.go.jp/e/policies/intel/gi_act/register/s42.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'nori-taste-study' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Japan Society for Food Engineering',
		'title'        => 'Taste-active compounds in dried nori',
		'url'          => 'https://www.jstage.jst.go.jp/article/jsfe/19/2/19_18515/_article',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'maff-hon-mirin' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'An Introduction to Japanese Fermented Foods, part 2',
		'url'          => 'https://www.maff.go.jp/j/keikaku/syokubunka/traditional-foods/files/user/pdf/an_Introduction_to_Japanese_fermented_foods_part2.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'maff-dashi-umami' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'Dashi and Umami, Washoku World Challenge archive',
		'url'          => 'https://www.maff.go.jp/e/policies/market/washoku-world-challenge/en/learning_03.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'dashi-combination-palatability-2008' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Journal of Cookery Science of Japan',
		'title'        => 'Relationship Between the Taste Components and the Taste Palatability of Japanese Soup Stock Made from Combination Ingredients',
		'url'          => 'https://www.jstage.jst.go.jp/article/cookeryscience1995/41/5/41_304/_article/-char/en',
		'published_at' => '2008-01-01',
		'retrieved_at' => '2026-08-06',
	),
	'kombu-water-extraction-conference-2024' => array(
		'type'         => 'conference_proceeding',
		'publisher'    => 'The Japan Society of Cookery Science',
		'title'        => 'Multiband spectroscopic analysis on kombu soup stock extracted using different types of water',
		'url'          => 'https://www.jstage.jst.go.jp/article/ajscs/35/0/35_17/_article/-char/en',
		'published_at' => '2024-09-06',
		'retrieved_at' => '2026-08-06',
	),
	'katsuobushi-mold-dashi-1986' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Journal of Cookery Science of Japan',
		'title'        => 'A Study on Katsuobushi-dashi, Effect of moldy process',
		'url'          => 'https://www.jstage.jst.go.jp/article/cookeryscience1968/19/4/19_285/_article',
		'published_at' => '1986-01-01',
		'retrieved_at' => '2026-08-06',
	),
	'toyosu-tmg' => array(
		'type'         => 'official_government',
		'publisher'    => 'Tokyo Metropolitan Government',
		'title'        => 'Information about Toyosu Market',
		'url'          => 'https://www.english.metro.tokyo.lg.jp/w/016-101-003992',
		'published_at' => '2026-07-01',
		'retrieved_at' => '2026-08-05',
	),
	'tsukiji-official' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Tsukiji Outer Market',
		'title'        => 'Welcome to Tsukiji',
		'url'          => 'https://www.tsukiji.or.jp/english/',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'kappabashi-official' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Kappabashi Dougu Street Promotion Association',
		'title'        => 'Kappabashi Dougu Street overview',
		'url'          => 'https://www.kappabashi.or.jp/en/overview/',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'kappabashi-map-2026' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Kappabashi Dougu Street Promotion Association',
		'title'        => 'Kappabashi English shop map',
		'url'          => 'https://www.kappabashi.or.jp/assets/pdf/kappabashimap_en.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'tsubaya-yanagiba-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Tsubaya',
		'title'        => 'Yanagiba collection',
		'url'          => 'https://tsubaya.jp/en/collections/yanagiba',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'tsubaya-molybdenum-no-bolster-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Tsubaya',
		'title'        => 'Molybdenum Steel Yanagiba, no bolster',
		'url'          => 'https://tsubaya.jp/en/products/ms-yanagiba-tsubanashi',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'tsubaya-white2-collection-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Tsubaya',
		'title'        => 'White 2 steel knife collection',
		'url'          => 'https://tsubaya.jp/en/collections/white2',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'tsubaya-blue1-suminagashi-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Tsubaya',
		'title'        => 'Blue 1 Carbon Steel Suminagashi Yanagiba Karin Burl Octagonal Handle',
		'url'          => 'https://tsubaya.jp/en/products/blue1-suminagashi-yanagiba-karinkobu',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'rishiri-kombu-100g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Rishiri Kombu direct shop',
		'title'        => 'Natural Rishiri kombu 100 g',
		'url'          => 'https://www.rishirikonbu.com/items/4808577',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'honkarebushi-200g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Honkarebushi whole Japanese katsuobushi block, bonito belly, 200 g',
		'url'          => 'https://int.japanesetaste.com/products/honkarebushi-whole-japanese-katsuobushi-block-bonito-belly-200g',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'fukumitsuya-hon-mirin-3y-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Fukumitsuya Junmai Hon Mirin, three years, 720 ml',
		'url'          => 'https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-3-years-traditionally-aged-sweet-rice-wine-720ml',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'fukumitsuya-hon-mirin-10y-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Fukumitsuya Junmai Hon Mirin, ten years, 720 ml',
		'url'          => 'https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-10-year-aged-sweet-rice-seasoning-720ml',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'yamaroku-product-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Yamaroku Soy Sauce',
		'title'        => 'Yamaroku product list',
		'url'          => 'https://yama-roku.net/product',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'fresh-japanese-wasabi-250g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'The Wasabi Company',
		'title'        => 'Fresh Japanese wasabi 250 g',
		'url'          => 'https://www.thewasabicompany.co.uk/products/fresh-japanese-wasabi-250g',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'dutch-wasabi-koshihikari-2kg-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Dutch Wasabi',
		'title'        => 'Koshihikari rice from Uozu, Toyama, 2 kg',
		'url'          => 'https://www.dutchwasabi.nl/en/koshihikari-rice-from-uozu-toyama-2-kg-2/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'hishiroku-dried-rice-koji-500g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Hishiroku Moyashi official online store',
		'title'        => 'Dried rice koji 500 g',
		'url'          => 'https://1469.stores.jp/items/601b735cc19c453eef5d6a72',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'hishiroku-chouhaku-kin-20g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Hishiroku Moyashi official online store',
		'title'        => 'Chouhaku-kin powdered koji starter 20 g',
		'url'          => 'https://1469.stores.jp/items/5efed301ec8fd331f922d017',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'dutch-wasabi-fresh-rhizome-50-60g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Dutch Wasabi',
		'title'        => 'Dutch-grown fresh wasabi rhizome, 50 to 60 g',
		'url'          => 'https://www.dutchwasabi.nl/en/fresh-wasabirhi-hem-1-piece-50-60-grams-2-4-servings/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'bank-israel-exchange-rates-20260806' => array(
		'type'         => 'official_government',
		'publisher'    => 'Bank of Israel',
		'title'        => 'Representative exchange rates, August 6 2026',
		'url'          => 'https://www.boi.org.il/roles/markets/exchangerates/',
		'published_at' => '2026-08-06',
		'retrieved_at' => '2026-08-06',
	),
	'kito-yuzu-juice-100ml-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Ogon no Mura',
		'title'        => 'Kito yuzu first juice, 100 ml',
		'url'          => 'https://shop.ogonnomura.jp/view/item/000000000364',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'kito-yuzu-juice-720ml-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Ogon no Mura',
		'title'        => 'Kito yuzu first juice collection, 720 ml',
		'url'          => 'https://shop.ogonnomura.jp/view/item/000000000199?category_page_id=ichiban',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'umezawa-hangiri-36cm-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Umezawa sawara cypress hangiri, 36 cm',
		'url'          => 'https://japanesetaste.com/products/umezawa-sawara-cypress-hangiri-wooden-sushi-oke-bowl-36cm',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'hagane-zame-large-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'The Wasabi Company',
		'title'        => 'Yamamoto Foods Hagane-zame wasabi grater, large',
		'url'          => 'https://www.thewasabicompany.co.uk/products/hagane-zame-wasabi-grater?variant=49446664601881',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'yamaroku-about' => array(
		'type'         => 'official_business',
		'publisher'    => 'Yamaroku Soy Sauce',
		'title'        => 'About Yamaroku',
		'url'          => 'https://yama-roku.net/en/about',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'michelin-tokyo-2026' => array(
		'type'         => 'official_organization',
		'publisher'    => 'The Michelin Guide',
		'title'        => 'Michelin Guide Tokyo 2026 stars reveal',
		'url'          => 'https://guide.michelin.com/en/article/michelin-guide-ceremony/michelin-guide-tokyo-2026-stars-reveal',
		'published_at' => '2025-09-25',
		'retrieved_at' => '2026-08-05',
	),
	'japanese-culinary-academy' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Japanese Culinary Academy',
		'title'        => 'The Japanese Culinary Academy',
		'url'          => 'https://culinary-academy.jp/english',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'danon-official' => array(
		'type'         => 'official_business',
		'publisher'    => 'Danon Culinary School',
		'title'        => 'Danon School of Culinary Professions',
		'url'          => 'https://www.danon.org.il/',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'bishulim-official' => array(
		'type'         => 'official_business',
		'publisher'    => 'Bishulim Culinary School',
		'title'        => 'About Bishulim',
		'url'          => 'https://www.bishulim-school.com/about-en/',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'cia-official' => array(
		'type'         => 'official_organization',
		'publisher'    => 'The Culinary Institute of America',
		'title'        => 'About the Culinary Institute of America',
		'url'          => 'https://www.ciachef.edu/about-the-culinary-institute-of-america/',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'basque-culinary-center-official' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Basque Culinary Center',
		'title'        => 'About Basque Culinary Center',
		'url'          => 'https://www.bculinary.com/en/sobrebcc',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'israel-food-import' => array(
		'type'         => 'official_government',
		'publisher'    => 'Israel Ministry of Health',
		'title'        => 'National Food Service imported food inspection unit',
		'url'          => 'https://www.gov.il/he/departments/units/import-food-inspection-unit',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
	'israel-alcohol-license' => array(
		'type'         => 'regulatory_guidance',
		'publisher'    => 'State of Israel',
		'title'        => 'Business licensing specification for alcohol sales',
		'url'          => 'https://www.gov.il/BlobFolder/generalpage/regulation-group4-food/he/publications_business_license_4_Article%204.8.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-05',
	),
);

$entities = array();

$entities[] = $c99_entity(
	array(
		'id' => 'museum-culinary-science', 'type' => 'topic_hub', 'slug' => 'culinary-science-museum',
		'name' => $c99_text( 'מוזיאון המדע של הקולינריה', 'Culinary Science Museum' ),
		'summary' => $c99_text( 'מקום לגלות איך תרבות, חומרי גלם, מולקולות טעם, טכניקות וכלי עבודה נפגשים באוכל. מתחילים במטבח, ממשיכים למרכיב, ורואים ליד כל טענה את המקור שעליו היא נשענת.', 'A place to discover how culture, ingredients, flavor molecules, techniques and tools meet in food. Start with a cuisine, continue to an ingredient, and see the source supporting each claim.' ),
		'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_until_longform_review', 'review_status' => 'research_draft',
		'seo_group' => 'museum', 'page_role' => 'pillar', 'primary_intent' => $c99_text( 'לחקור את מדע הקולינריה דרך מטבחים ומוצרים', 'Explore culinary science through cuisines and products' ),
		'primary_keyword' => $c99_text( 'מוזיאון המדע של הקולינריה', 'Culinary Science Museum' ),
		'secondary_keywords' => array( 'he' => array( 'אנציקלופדיה קולינרית מדעית' ), 'en' => array( 'culinary science encyclopedia' ) ), 'schema_type' => 'CollectionPage',
		'facts' => array( $c99_fact( 'fact-museum-graph-role', 'structural', 'Complete99 מפרסם בשער הזה פרופילים קולינריים דו-לשוניים שבהם עובדות, הקשר מדעי ומקורות מוצגים יחד.', 'Complete99 publishes bilingual culinary profiles in this gateway, presenting facts, scientific context and sources together.', 'official_source', 'entity', array( 'complete99-public-site' ) ) ),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'pending_evidence', 'המרכז המדעי נבנה מעובדות ומדידות הקשורות לחומר גלם, מנה, כלי או תהליך מסוימים.', 'The science layer is built from facts and measurements tied to a specific ingredient, dish, tool or process.' ),
			'cultural' => $c99_profile( 'pending_evidence', 'כל תרבות נשמרת כאשכול עצמאי בתוך אותו שער.', 'Each culture remains an independent cluster within the same gateway.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'לכל מוסד, מדריך ותקן יש תיאור ומקורות משלו.', 'Each institution, guide and standard has its own description and sources.' ),
			'economic' => $c99_profile( 'pending_evidence', 'כאשר מוצר מוצע למכירה, מחיר החנות הנוכחי נשמר בנפרד מדוגמאות מחיר כלליות מן השוק.', 'When a product is offered for sale, its current store price remains separate from general market-price examples.' ),
			'structural' => $c99_profile( 'source_backed', 'מסלולי העיון מחברים בין מטבח, חומר גלם, מחקר ומקור כדי שקל להמשיך מן השאלה אל התשובה הבאה.', 'Discovery paths connect cuisine, ingredient, research and source so readers can move naturally from one question to the next.', array( 'fact-museum-graph-role' ) ),
		) ),
		'categories' => array( 'culinary-museum' ), 'attributes' => array(), 'tags' => array( 'culinary-science', 'knowledge-graph', 'topic-clusters' ),
		'revenue_models' => array( 'content_to_commerce', 'education', 'lead_generation' ),
		'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'culinary_students', 'research_readers' ),
		'prompt_en' => 'Premium editorial hero for a culinary science museum, a precise circular composition connecting a plated dish, raw ingredients, molecular glassware, traditional tools and a world map, sophisticated natural materials, museum lighting, no text, no logos, no futuristic holograms.',
	)
);

$entities[] = $c99_entity(
	array(
		'id'      => 'cuisine-japanese-washoku',
		'type'    => 'cuisine',
		'slug'    => 'japanese-washoku',
		'name'    => $c99_text( 'וואשוקו, תרבות האוכל היפנית', 'Washoku, Japanese dietary culture' ),
		'summary' => $c99_text( 'וואשוקו הוא מסגרת תרבותית של ידע, מיומנויות ומסורות הקשורות לייצור, עיבוד, הכנה וצריכת מזון ביפן. מכאן אפשר להמשיך לחומרי גלם, טכניקות, מנות ומקומות, תוך שמירה על ההבדלים שבין אזורים ומסורות.', 'Washoku is a cultural framework of knowledge, skills and traditions connected to producing, processing, preparing and consuming food in Japan. From here, readers can continue to ingredients, techniques, dishes and places while preserving regional and traditional differences.' ),
		'seo_group' => 'cuisines',
		'primary_intent' => $c99_text( 'להבין את תרבות האוכל היפנית ואת המבנה שלה', 'Understand Japanese dietary culture and its structure' ),
		'primary_keyword' => $c99_text( 'תרבות האוכל היפנית וואשוקו', 'Japanese washoku food culture' ),
		'secondary_keywords' => array( 'he' => array( 'מטבח יפני מסורתי', 'מורשת קולינרית יפנית' ), 'en' => array( 'traditional Japanese cuisine', 'Japanese culinary heritage' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-washoku-unesco-framework', 'cultural', 'אונסקו מתאר את וואשוקו כמכלול של מיומנויות, ידע, מנהגים ומסורות הקשורים למזון.', 'UNESCO describes washoku as a set of skills, knowledge, practices and traditions related to food.', 'official_source', 'category', array( 'unesco-washoku' ) ),
			$c99_fact( 'fact-washoku-graph-root', 'structural', 'בגרף Complete99, וואשוקו הוא מטבח על שממנו מסתעפים אזורים, מנות, טכניקות, מוסדות ושווקים.', 'In the Complete99 graph, washoku is a parent cuisine from which regions, dishes, techniques, institutions and markets branch.', 'editorial_inference', 'entity', array( 'unesco-washoku' ), false ),
		),
		'profiles' => $c99_profiles(
			array(
				'scientific' => $c99_profile( 'pending_evidence', 'המדע נמצא בישויות המנה, החומר, הטכניקה והמולקולה, ולא מיוחס למטבח כולו.', 'Science belongs to dish, ingredient, technique and molecule entities rather than being generalized to the whole cuisine.' ),
				'cultural' => $c99_profile( 'source_backed', 'המסגרת התרבותית מבוססת על תיאור אונסקו ומחייבת פירוק להקשרים אזוריים.', 'The cultural framework is grounded in UNESCO and must be decomposed into regional contexts.', array( 'fact-washoku-unesco-framework' ) ),
				'institutional' => $c99_profile( 'pending_evidence', 'מוסדות יפניים ובינלאומיים מקושרים כישויות נפרדות עם תאריך בדיקה.', 'Japanese and international institutions are linked as separate entities with review dates.' ),
				'economic' => $c99_profile( 'not_applicable', 'אין מחיר אחד למטבח; הכלכלה נמדדת ברמת חומר, מנה, ציוד ושוק.', 'A cuisine has no single price; economics is measured at ingredient, dish, equipment and market level.' ),
				'structural' => $c99_profile( 'source_backed', 'נקודת פתיחה המחברת מסורות, מנות, חומרי גלם, שיטות, כלים ומקומות ברחבי המטבח היפני.', 'A starting point connecting traditions, dishes, ingredients, methods, tools and places across Japanese cuisine.', array( 'fact-washoku-graph-root' ) ),
			)
		),
		'categories' => array( 'world-cuisines', 'japan', 'washoku' ),
		'attributes' => array( 'pa_origin' => array( 'japan' ) ),
		'tags' => array( 'washoku', 'japanese-heritage', 'seasonality' ),
		'relations' => array(
			$c99_relation( 'contains', 'dish-edomae-nigiri', 'מסורת אדומאה היא אחד מענפי המיפוי.', 'Edomae tradition is one mapped branch.' ),
			$c99_relation( 'supported_by', 'institution-japanese-culinary-academy', 'האקדמיה היפנית לקולינריה נשמרת כישות מוסדית נפרדת.', 'The Japanese Culinary Academy is retained as a separate institutional entity.' ),
		),
		'prompt_en' => 'Museum-grade editorial still life expressing washoku through seasonal vegetables, polished rice, clear dashi, handcrafted lacquerware and natural fibers, quiet Japanese daylight, precise material textures, no people, no logos, no calligraphy, documentary restraint.',
		'shot_list' => array( 'seasonal table hero', 'ingredient detail', 'craft material detail', 'heritage timeline background' ),
	)
);

/* Query-owning hubs and navigational category nodes. */
$c99_topic_hub = static function ( $id, $slug, $name, $summary, $keyword, $source_ids, $parent_id = 'cuisine-japanese-washoku', $page_role = 'category', $intent_classes = array( 'informational' ) ) use ( $c99_entity, $c99_text, $c99_fact, $c99_profiles, $c99_profile ) {
	$fact_id = 'fact-' . $slug . '-architecture';
	return $c99_entity( array(
		'id' => $id,
		'type' => 'topic_hub',
		'slug' => $slug,
		'parent_id' => $parent_id,
		'name' => $name,
		'summary' => $summary,
		'seo_group' => 'topics',
		'page_role' => $page_role,
		'intent_classes' => $intent_classes,
		'primary_intent' => $c99_text( 'למצוא ולהבין את ' . $name['he'] . ' לפי נושאים וישויות', 'Explore and understand ' . $name['en'] . ' by topic and entity' ),
		'primary_keyword' => $keyword,
		'secondary_keywords' => array( 'he' => array( $name['he'] ), 'en' => array( $name['en'] ) ),
		'schema_type' => 'CollectionPage',
		'facts' => array(
			$c99_fact( $fact_id, 'structural', 'המדור מרכז נושאים קשורים ומוביל ביניהם באמצעות הקשר קולינרי ברור.', 'This section groups related subjects and connects them through clear culinary context.', 'editorial_inference', 'entity', $source_ids ),
		),
		'profiles' => $c99_profiles( array(
			'structural' => $c99_profile( 'source_backed', 'המדור עוזר לעבור מן התמונה הרחבה אל מנות, חומרי גלם, כלים ושיטות קשורים, תוך שמירה על ההקשר של כל נושא.', 'This section helps readers move from the broader picture to related dishes, ingredients, tools and methods while preserving each topic context.', array( $fact_id ) ),
		) ),
		'categories' => array( 'culinary-museum', 'japanese-cuisine', $slug ),
		'attributes' => array( 'pa_origin' => array( 'japan' ) ),
		'tags' => array( 'topic-cluster', $slug ),
		'prompt_en' => 'Museum-grade commercial editorial composition representing ' . $name['en'] . ', clear visual hierarchy, authentic culinary materials, restrained natural light, no logos, no embedded text, no invented certification marks.',
		'shot_list' => array( 'cluster hero', 'category index visual', 'material detail', 'contextual link card crop' ),
	) );
};

$entities[] = $c99_topic_hub(
	'hub-japanese-dishes',
	'japanese-dishes',
	$c99_text( 'מנות יפניות ומסורות הגשה', 'Japanese dishes and service traditions' ),
	$c99_text( 'מרכז המנות מפריד בין מסורות אזוריות, מנות מוגשות, מבני ארוחה וטכניקות הגשה. כל מנה נפתחת למרכיבים, תהליכים, ציוד, בטיחות ומקורות.', 'The dish hub separates regional traditions, plated dishes, meal structures and service techniques. Every dish opens into ingredients, processes, equipment, safety and sources.' ),
	$c99_text( 'מנות יפניות מסורתיות', 'traditional Japanese dishes' ),
	array( 'maff-edomae', 'unesco-washoku' )
);
$entities[] = $c99_topic_hub(
	'hub-japanese-techniques',
	'japanese-culinary-techniques',
	$c99_text( 'טכניקות בישול יפניות', 'Japanese culinary techniques' ),
	$c99_text( 'מרכז הטכניקות ממפה הכנה, חיתוך, מיצוי, כבישה, בישול והתססה לפי חומר, זמן, טמפרטורה, כלי ותוצאה רצויה.', 'The technique hub maps preparation, cutting, extraction, curing, cooking and fermentation by material, time, temperature, tool and intended result.' ),
	$c99_text( 'טכניקות בישול יפניות', 'Japanese cooking techniques' ),
	array( 'maff-edomae', 'maff-fermented-foods' )
);
$entities[] = $c99_topic_hub(
	'hub-japanese-ingredients',
	'japanese-premium-ingredients',
	$c99_text( 'חומרי גלם יפניים פרימיום', 'Premium Japanese ingredients' ),
	$c99_text( 'כאן משווים חומרי גלם לפי מקור, זן, עיבוד, טעם, אלרגנים, דרגות איכות ושימושים. כאשר קיים מחיר מקור מתועד, הוא מוצג בנפרד ממחיר מכירה בישראל.', 'Compare ingredients by origin, cultivar, processing, flavor, allergens, quality grade and use. When a documented source-market price exists, it is shown separately from any Israeli sell price.' ),
	$c99_text( 'חומרי גלם יפניים פרימיום', 'premium Japanese ingredients' ),
	array( 'maff-fermented-foods', 'unesco-washoku' ),
	'cuisine-japanese-washoku',
	'category',
	array( 'informational', 'commercial' )
);
$entities[] = $c99_topic_hub(
	'hub-japanese-food-science',
	'japanese-food-science',
	$c99_text( 'מדע המזון היפני', 'Japanese food science' ),
	$c99_text( 'מרכז המדע מחבר מולקולות טעם, אנזימים, תגובות, מבנה חומר ומדידות להקשר קולינרי מוגדר, בלי להפוך ממצא ספרותי למפרט מוצר.', 'The science hub connects flavor molecules, enzymes, reactions, material structure and measurements to a defined culinary context without turning literature into a product specification.' ),
	$c99_text( 'מדע המזון היפני', 'Japanese food science' ),
	array( 'umami-receptor-2009', 'wasabi-itc-2023' )
);
$entities[] = $c99_topic_hub(
	'hub-japanese-equipment',
	'japanese-professional-equipment',
	$c99_text( 'כלי מטבח יפניים להכנה מדויקת', 'Japanese culinary tools for precise preparation' ),
	$c99_text( 'מרכז הכלים עוזר לבחור כלי יפני לפי הפעולה הקולינרית, החומר, המידה, התחזוקה ומפרט הדגם, ומחבר כל כלי לחומרי הגלם ולטכניקות המתאימים לו.', 'The tools hub helps culinary consumers choose a Japanese tool by preparation task, material, size, care and model specification, then connects each tool to suitable ingredients and techniques.' ),
	$c99_text( 'כלי מטבח יפניים', 'Japanese culinary tools' ),
	array( 'kappabashi-official', 'yamamoto-haganezame-spec' ),
	'cuisine-japanese-washoku',
	'category',
	array( 'informational', 'commercial' )
);
$entities[] = $c99_topic_hub(
	'hub-japanese-sourcing',
	'japanese-culinary-markets-and-sourcing',
	$c99_text( 'שווקים ומקורות אספקה קולינריים ביפן', 'Japanese culinary markets and sourcing' ),
	$c99_text( 'מרכז האספקה מפריד בין שוק, יצרן, חנות, ספק מאושר ותצפית מחיר. זהות ציבורית אינה מעידה על קשר מסחרי, מלאי או יכולת שילוח.', 'The sourcing hub separates markets, producers, shops, approved suppliers and price observations. Public identity does not imply a commercial relationship, inventory or shipping capability.' ),
	$c99_text( 'אשכול מקורות אספקה קולינריים ביפן', 'Japanese culinary sourcing topic cluster' ),
	array( 'toyosu-tmg', 'tsukiji-official', 'kappabashi-official' ),
	'cuisine-japanese-washoku',
	'category',
	array( 'informational', 'commercial', 'navigational' )
);
$entities[] = $c99_topic_hub(
	'hub-japanese-restaurants',
	'japanese-benchmark-restaurants',
	$c99_text( 'מסעדות יפניות נבחרות ונקודות ייחוס', 'Selected Japanese restaurants and benchmarks' ),
	$c99_text( 'מרכז המסעדות שומר כל מסעדה כישות מתוארכת עם מקור, מטבח, מיקום וסטטוס נבדק. אזכור מקצועי אינו המלצה מסחרית ואינו נשמר כתואר קבוע.', 'The restaurant hub stores each restaurant as a dated entity with source, cuisine, location and reviewed status. Professional recognition is not a commercial endorsement and is not treated as permanent.' ),
	$c99_text( 'מסעדות יפניות מובילות', 'leading Japanese restaurants' ),
	array( 'michelin-tokyo-2026' ),
	'cuisine-japanese-washoku',
	'category',
	array( 'informational', 'navigational' )
);
$entities[] = $c99_topic_hub(
	'hub-global-culinary-institutions',
	'global-culinary-institutions',
	$c99_text( 'מוסדות קולינריים מובילים בישראל ובעולם', 'Leading culinary institutions in Israel and worldwide' ),
	$c99_text( 'מרכז המוסדות ממפה בתי ספר, אקדמיות ומרכזי מחקר בישראל ובעולם לפי זהות רשמית, תחומי פעילות, תוכניות, מחקר וקשר למטבחים ולטכניקות.', 'The institution hub maps schools, academies and research centers in Israel and worldwide by official identity, activity, programs, research and links to cuisines and techniques.' ),
	$c99_text( 'מוסדות קולינריים מובילים בישראל ובעולם', 'leading culinary institutions worldwide' ),
	array( 'japanese-culinary-academy', 'danon-official', 'bishulim-official', 'cia-official', 'basque-culinary-center-official' ),
	'',
	'pillar',
	array( 'informational', 'navigational' )
);

$entities[] = $c99_entity(
	array(
		'id' => 'dish-edomae-nigiri', 'type' => 'dish', 'slug' => 'edomae-nigiri', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'ניגירי אדומאה', 'Edomae nigiri' ),
		'summary' => $c99_text( 'ניגירי אדומאה מחבר שארי מתובל בחומץ עם דג שעבר טיפול המתאים למין ולעונה, כגון המלחה, כבישה, אידוי, בישול, השריית סויה או עטיפת קומבו. המנה ממופה כמערכת של אורז, דג, תיבול, מיומנות סכין, בטיחות וקצב הגשה.', 'Edomae nigiri joins vinegar-seasoned shari with fish treated for species and season through methods such as salting, curing, steaming, cooking, soy marination or kombu wrapping. The dish is mapped as a system of rice, fish, seasoning, knife skill, safety and service rhythm.' ),
		'seo_group' => 'dishes', 'primary_intent' => $c99_text( 'להבין ניגירי אדומאה, מרכיבים וטכניקה', 'Understand Edomae nigiri, ingredients and technique' ),
		'primary_keyword' => $c99_text( 'ניגירי אדומאה', 'Edomae nigiri' ),
		'secondary_keywords' => array( 'he' => array( 'סושי אדומאה', 'אורז סושי אדום' ), 'en' => array( 'Edomae sushi', 'red vinegar sushi rice' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-edomae-methods-maff', 'cultural', 'MAFF מתעד שיטות אדומאה הכוללות המלחה, כבישה, אידוי, בישול, מרינדת סויה ועטיפת קומבו.', 'MAFF documents Edomae methods including salting, curing, steaming, cooking, soy marination and kombu wrapping.', 'official_source', 'technique_context', array( 'maff-edomae' ) ),
			$c99_fact( 'fact-edomae-system-structure', 'structural', 'המנה מקשרת שארי, דג, שויו, וואסבי, נורי, סכין יאנאגיבה ובקרות בטיחות.', 'The dish links shari, fish, shoyu, wasabi, nori, a yanagiba and safety controls.', 'editorial_inference', 'entity', array( 'maff-edomae' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'pending_evidence', 'המדע מפוצל לחומציות השארי, עמילן האורז, חלבון הדג וסינרגיית אומאמי.', 'Science is decomposed into shari acidity, rice starch, fish protein and umami synergy.' ),
			'cultural' => $c99_profile( 'source_backed', 'שיטות הטיפול נרשמות לפי תיעוד MAFF ולא כמסורת אחידה לכל דג.', 'Treatment methods follow MAFF documentation and are not generalized to every fish.', array( 'fact-edomae-methods-maff' ) ),
			'institutional' => $c99_profile( 'pending_evidence', 'מסעדות ואקדמיות מקושרות כנקודות ייחוס מתוארכות, לא כהמלצה מסחרית.', 'Restaurants and academies are linked as dated references, not commercial endorsements.' ),
			'economic' => $c99_profile( 'pending_evidence', 'השוואת ערך תפריד בין דג, אורז, עבודה, עונה ורמת שירות.', 'Value comparison will separate fish, rice, labor, season and service level.' ),
			'structural' => $c99_profile( 'source_backed', 'מפת המנה מחברת רכיבים, ציוד ובקרות.', 'The dish map connects ingredients, equipment and controls.', array( 'fact-edomae-system-structure' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'sushi', 'edomae' ),
		'attributes' => array( 'pa_origin' => array( 'tokyo-japan' ), 'pa_processing_method' => array( 'species-specific-edomae-treatment' ), 'pa_allergens' => array( 'fish', 'soybeans', 'cereals-containing-gluten' ) ),
		'tags' => array( 'edomae', 'nigiri', 'shari', 'umami' ),
		'relations' => array(
			$c99_relation( 'requires', 'preparation-sushi-shari', 'השארי הוא בסיס מבני של המנה.', 'Shari is a structural base of the dish.' ),
			$c99_relation( 'complements', 'ingredient-kioke-shoyu', 'שויו הוא רכיב תיבול אפשרי בהתאם לשיטת ההגשה.', 'Shoyu is a possible seasoning depending on service method.' ),
			$c99_relation( 'complements', 'ingredient-fresh-wasabi', 'וואסבי טרי הוא רכיב נלווה אפשרי.', 'Fresh wasabi is a possible accompaniment.' ),
			$c99_relation( 'requires', 'equipment-yanagiba', 'יאנאגיבה מייצגת את שכבת מיומנות החיתוך.', 'Yanagiba represents the slicing-skill layer.' ),
		),
		'cross_sell_ids' => array( 'ingredient-kioke-shoyu', 'ingredient-fresh-wasabi', 'ingredient-yakinori', 'equipment-hangiri', 'equipment-yanagiba' ),
		'prompt_en' => 'Commercial culinary studio photograph of an Edomae nigiri progression on a charcoal lacquer counter, akami zuke, kohada, steamed prawn and glazed anago over warm vinegar-seasoned shari, exact knife work, restrained seasonal garnish, soft directional window light, 45 degree angle, ultra-real food texture.',
		'culinary_test_status' => 'pending',
		'compliance' => array(
			$c99_compliance( 'raw-fish-control', 'דג המיועד לצריכה נאה דורש בקרת מקור, קירור וסיכוני טפילים לפי המין. הנחיית FDA משמשת הקשר מקצועי זר בלבד, והיישום חייב להיבדק מול הדין ותוכנית בטיחות המזון בישראל.', 'Fish intended for raw consumption requires origin, refrigeration and species-specific parasite controls. FDA guidance is foreign professional context only; implementation must be checked against Israeli law and the local food-safety plan.', array( 'fda-fish-hazards-2022', 'israel-moh-fish-safety' ) ),
		),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'preparation-sushi-shari', 'type' => 'preparation', 'slug' => 'sushi-shari', 'parent_id' => 'dish-edomae-nigiri',
		'name' => $c99_text( 'שארי לסושי', 'Sushi shari' ),
		'summary' => $c99_text( 'שארי הוא אורז יפוניקה מבושל ומתובל בחומץ, מלח ולעיתים סוכר. המרקם אינו מספר קבוע: זן, עונת קציר, יחס מים, פרופיל עמילן, טמפרטורה וקצב קירור משנים את קשיות הגרגיר והדביקות.', 'Shari is cooked japonica rice seasoned with vinegar, salt and sometimes sugar. Its texture is not a fixed number: cultivar, harvest, water ratio, starch profile, temperature and cooling rate affect grain firmness and adhesion.' ),
		'seo_group' => 'techniques', 'primary_intent' => $c99_text( 'להבין אורז סושי ותהליכי שארי', 'Understand sushi rice and shari preparation' ),
		'primary_keyword' => $c99_text( 'שארי אורז לסושי', 'sushi shari rice' ),
		'secondary_keywords' => array( 'he' => array( 'אורז יפוניקה לסושי', 'חומץ אורז לסושי' ), 'en' => array( 'japonica sushi rice', 'sushi vinegar rice' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-shari-cultivar-context', 'scientific', 'קושיהיקארי משמש במחקרי איכות אורז כזן ייחוס חשוב, אך נתוני מרקם חייבים להיקשר לזן ולאצווה בפועל.', 'Koshihikari is an important reference cultivar in rice-quality research, but texture data must be tied to the actual cultivar and lot.', 'peer_reviewed_context', 'category', array( 'koshihikari-genome-2018' ) ),
			$c99_fact( 'fact-shari-lot-values', 'structural', 'pH, יחס מים ונתוני עמילן נשמרים כמפרט מתכון או מדידת אצווה ולא כתגית WooCommerce כללית.', 'pH, water ratio and starch data are stored as recipe specifications or lot measurements, not generic WooCommerce tags.', 'editorial_inference', 'entity', array( 'fda-sushi-rice-2022' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'מרקם האורז תלוי זן ותהליך; אין להעתיק ערך עמילוז או pH מקטגוריה למוצר.', 'Rice texture depends on cultivar and process; amylose or pH values must not be copied from a category to a product.', array( 'fact-shari-cultivar-context' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'סוג החומץ ופרופיל התיבול יתועדו לפי מסורת ושיטה ספציפית.', 'Vinegar type and seasoning profile will be documented for a specific tradition and method.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'שיטות לימוד ומסעדות ייקשרו עם מקור ותאריך.', 'Teaching methods and restaurants will be linked with a source and date.' ),
			'economic' => $c99_profile( 'pending_evidence', 'השוואת אורז תכלול אריזה, יבול, מקור ומחיר מנורמל לקילוגרם.', 'Rice comparison will include pack, harvest, origin and normalized price per kilogram.' ),
			'structural' => $c99_profile( 'source_backed', 'השארי מפריד בין נתוני ספרות, מפרט מתכון ומדידת אצווה.', 'Shari separates literature context, recipe specification and lot measurement.', array( 'fact-shari-lot-values' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'sushi', 'rice-and-shari' ),
		'attributes' => array( 'pa_origin' => array( 'japan' ), 'pa_cultivar' => array( 'cultivar-specific' ), 'pa_processing_method' => array( 'vinegar-seasoned-cooked-rice' ) ),
		'tags' => array( 'shari', 'japonica-rice', 'starch-gelatinization', 'acetic-acid' ),
		'relations' => array( $c99_relation( 'requires', 'equipment-hangiri', 'האנגירי משמש לערבוב ולפיזור חום ולחות.', 'A hangiri is used while mixing and managing heat and moisture.' ) ),
		'cross_sell_ids' => array( 'equipment-hangiri', 'ingredient-kioke-shoyu', 'ingredient-yakinori' ),
		'prompt_en' => 'Macro commercial food photograph of glossy individual Japanese sushi-rice grains being folded in a cedar hangiri with a wooden shamoji, faint steam, restrained vinegar sheen, warm neutral studio light, high micro-texture and accurate grain scale.',
		'culinary_test_status' => 'pending',
		'compliance' => array( $c99_compliance( 'acidified-rice-process', 'ערך pH מספרות או מהנחיה זרה אינו מחליף תהליך HACCP ומתכון מאומת לפי הדין המקומי.', 'A pH value from literature or foreign guidance does not replace a locally validated recipe and HACCP process.', array( 'fda-sushi-rice-2022' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'preparation-ichiban-dashi', 'type' => 'preparation', 'slug' => 'ichiban-dashi', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'איצ׳יבאן דאשי', 'Ichiban dashi' ),
		'summary' => $c99_text( 'איצ׳יבאן דאשי הוא ציר ראשון המבוסס בדרך כלל על קומבו וקצואובושי. המערכת מפרידה בין זהות חומרי הגלם, תנאי המיצוי והקשר המחקרי של גלוטמט ו-IMP, כדי לא להפוך יחס ניסויי אחד למתכון אוניברסלי.', 'Ichiban dashi is a first stock commonly based on kombu and katsuobushi. The system separates ingredient identity, extraction conditions and the research context of glutamate and IMP so one experimental ratio is not treated as a universal recipe.' ),
		'seo_group' => 'techniques', 'primary_intent' => $c99_text( 'להבין דאשי, אומאמי ומיצוי', 'Understand dashi, umami and extraction' ),
		'primary_keyword' => $c99_text( 'איצ׳יבאן דאשי', 'ichiban dashi' ),
		'secondary_keywords' => array( 'he' => array( 'קומבו וקצואובושי', 'סינרגיית אומאמי' ), 'en' => array( 'kombu katsuobushi stock', 'umami synergy' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-dashi-umami-synergy', 'scientific', 'מחקר מולקולרי תומך במודל שבו גלוטמט ו-5-prime נוקלאוטידים כגון IMP יכולים ליצור סינרגיית אומאמי דרך הרצפטור T1R1/T1R3.', 'Molecular research supports a model in which glutamate and 5-prime nucleotides such as IMP can create umami synergy through the T1R1/T1R3 receptor.', 'peer_reviewed_context', 'technique_context', array( 'umami-receptor-2009', 'dashi-combination-palatability-2008' ) ),
			$c99_fact( 'fact-dashi-graph', 'structural', 'הציר מקשר קומבו, קצואובושי, מולקולות טעם, סוג מים, טמפרטורה, זמן וסינון כישויות ושדות נפרדים.', 'The stock links kombu, katsuobushi, taste molecules, water type, temperature, time and filtration as separate entities and fields.', 'editorial_inference', 'entity', array( 'maff-dashi-umami', 'kombu-water-extraction-conference-2024' ), false ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'הבסיס המדעי הוא סינרגיה רצפטורית בין גלוטמט לנוקלאוטידים, לא סיסמה כללית של אומאמי.', 'The scientific base is receptor-level synergy between glutamate and nucleotides, not a generic umami slogan.', array( 'fact-dashi-umami-synergy' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'סוג הדאשי ושימושו יתועדו לפי אזור, עונה ומנה.', 'Dashi type and use will be documented by region, season and dish.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'מסעדות ושיטות הוראה יקושרו רק כמקורות מתוארכים.', 'Restaurants and teaching methods will be linked only as dated sources.' ),
			'economic' => $c99_profile( 'pending_evidence', 'השוואה תפריד בין זן קומבו, דרגת קצואובושי, תפוקה ומחיר לליטר ציר.', 'Comparison will separate kombu type, katsuobushi grade, yield and stock cost per liter.' ),
			'structural' => $c99_profile( 'source_backed', 'ישות ההכנה מחברת חומר, מולקולה ותהליך.', 'The preparation entity connects material, molecule and process.', array( 'fact-dashi-graph' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'dashi', 'first-stock' ),
		'attributes' => array( 'pa_origin' => array( 'japan' ), 'pa_flavor_profile' => array( 'umami' ), 'pa_processing_method' => array( 'controlled-water-extraction' ), 'pa_allergens' => array( 'fish' ) ),
		'tags' => array( 'dashi', 'glutamate', 'imp', 'umami-synergy' ),
		'relations' => array(
			$c99_relation( 'requires', 'ingredient-kombu', 'מפת הבסיס משתמשת בקומבו כחומר גלם מזוהה.', 'The base map uses kombu as an identified ingredient.', true, array( 'maff-dashi-umami' ), 'official_source' ),
			$c99_relation( 'requires', 'ingredient-katsuobushi', 'מפת הבסיס משתמשת בקצואובושי כחומר גלם מזוהה.', 'The base map uses katsuobushi as an identified ingredient.', true, array( 'maff-dashi-umami' ), 'official_source' ),
			$c99_relation( 'requires', 'technique-dashi-extraction', 'זמן, טמפרטורה, מים וסינון נשמרים במפרט התהליך.', 'Time, temperature, water and filtration remain in the process specification.', true, array( 'kombu-water-extraction-conference-2024' ), 'conference_context' ),
			$c99_relation( 'contains', 'molecule-l-glutamate', 'קומבו הוא מקור הקשרי לגלוטמט.', 'Kombu is a contextual source of glutamate.', true, array( 'maff-dashi-umami', 'umami-receptor-2009' ), 'peer_reviewed_context' ),
			$c99_relation( 'contains', 'molecule-inosine-monophosphate', 'קצואובושי הוא מקור הקשרי ל-IMP.', 'Katsuobushi is a contextual source of IMP.', true, array( 'maff-dashi-umami', 'umami-receptor-2009' ), 'peer_reviewed_context' ),
		),
		'cross_sell_ids' => array( 'ingredient-kombu', 'ingredient-katsuobushi', 'technique-dashi-extraction' ),
		'prompt_en' => 'Translucent golden ichiban dashi in a clear laboratory-style culinary vessel, one kombu sheet and paper-thin katsuobushi shavings beside it, backlight revealing clarity and gentle steam, restrained Japanese studio composition, scientifically precise food texture.',
		'culinary_test_status' => 'not_applicable',
		'compliance' => array( $c99_compliance( 'fish-allergen', 'קצואובושי הוא מוצר דג ויש לסמן אלרגן דגים ולבדוק מגע צולב במוצר בפועל.', 'Katsuobushi is a fish product; fish allergen labeling and actual-product cross-contact review are required.' ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-kombu', 'type' => 'ingredient', 'slug' => 'kombu', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'קומבו לדאשי', 'Kombu for dashi' ),
		'summary' => $c99_text( 'קומבו הוא אצת מאכל חומה המשמשת בסיס מרכזי לסוגי דאשי. זן, אזור, עובי, יישון, ניקוי, איכות המים ותנאי המיצוי משפיעים על התוצאה, ולכן ערכי גלוטמט, לחות או מחיר חייבים להיות קשורים למוצר, אצווה או ניסוי מוגדרים.', 'Kombu is an edible brown seaweed central to many dashi styles. Species, region, thickness, maturation, cleaning, water quality and extraction conditions affect the result, so glutamate, moisture or price values must be tied to a defined product, lot or experiment.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'לבחור קומבו לדאשי ולהבין מיצוי', 'Choose kombu for dashi and understand extraction' ),
		'primary_keyword' => $c99_text( 'קומבו לדאשי', 'kombu for dashi' ),
		'secondary_keywords' => array( 'he' => array( 'אצת קומבו', 'קומבו אומאמי' ), 'en' => array( 'dashi kelp', 'kombu umami' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-kombu-dashi-role', 'scientific', 'מקור MAFF מתאר קומבו כחומר גלם לדאשי וכמקור לגלוטמט, ומחקר הרצפטור מסביר כיצד גלוטמט משתתף בתפיסת אומאמי. ערך כמותי חייב להישאר קשור לדגימה ולתנאי הבדיקה.', 'MAFF describes kombu as a dashi ingredient and glutamate source, while receptor research explains how glutamate participates in umami perception. Any quantitative value must remain tied to the tested sample and conditions.', 'peer_reviewed_context', 'category', array( 'maff-dashi-umami', 'umami-receptor-2009' ) ),
			$c99_fact( 'fact-kombu-lot-boundary', 'structural', 'מין, אזור, שנת קציר, דרגה, לחות ו-COA שייכים ל-SKU ולאצווה ולא לישות הקטגוריה.', 'Species, region, harvest year, grade, moisture and COA belong to a SKU and lot, not to the category entity.', 'editorial_inference', 'entity', array( 'kombu-water-extraction-conference-2024' ), false ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'כדי להשוות קומבו בצורה הוגנת, שומרים יחד את זהות האצה, סוג המים ותנאי המיצוי.', 'A fair kombu comparison keeps seaweed identity, water type and extraction conditions together.', array( 'fact-kombu-dashi-role' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'הקשר אזורי והיסטורי ייכתב ממקורות תרבותיים ייעודיים.', 'Regional and historical context will be written from dedicated cultural sources.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'מפיקים, אזורי קציר ותקנים יתווספו כישויות נפרדות.', 'Producers, harvest regions and standards will be separate entities.' ),
			'economic' => $c99_profile( 'pending_evidence', 'מחיר ינורמל ל-100 גרם ולליטר דאשי שימושי, עם תפוקה ופחת.', 'Price will be normalized per 100 grams and useful liter of dashi, including yield and shrinkage.' ),
			'structural' => $c99_profile( 'source_backed', 'קטגוריה, SKU ואצווה מופרדים כדי למנוע העתקת נתונים.', 'Category, SKU and lot are separated to prevent copied values.', array( 'fact-kombu-lot-boundary' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'dashi-ingredients', 'kombu' ),
		'attributes' => array( 'pa_species' => array( 'product-specific' ), 'pa_origin' => array( 'product-specific' ), 'pa_processing_method' => array( 'dried-seaweed-product-specific' ), 'pa_flavor_profile' => array( 'umami' ) ),
		'tags' => array( 'kombu', 'dashi', 'glutamate', 'seaweed', 'umami' ),
		'relations' => array(
			$c99_relation( 'used_in', 'preparation-ichiban-dashi', 'קומבו הוא רכיב יסוד במפת האיצ׳יבאן דאשי.', 'Kombu is a foundational ingredient in the ichiban dashi map.', true, array( 'maff-dashi-umami' ), 'official_source' ),
			$c99_relation( 'requires', 'technique-dashi-extraction', 'תוצאה שימושית דורשת מפרט מים, זמן וטמפרטורה.', 'A useful result requires water, time and temperature specifications.', true, array( 'kombu-water-extraction-conference-2024' ), 'conference_context' ),
		),
		'commerce_state' => 'active_offer',
		'woo_product_code' => 'product-rishiri-kombu-100g', 'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-rishiri-kombu-100g-20260806' ),
		'cross_sell_ids' => array( 'ingredient-katsuobushi', 'technique-dashi-extraction' ),
		'prompt_en' => 'Commercial culinary studio photograph of premium unbranded kombu sheets for dashi, natural olive-brown surface bloom and realistic thickness, one cut edge beside clear golden stock, soft raking side light, macro texture, neutral stone background, no packaging or text.',
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-katsuobushi', 'type' => 'ingredient', 'slug' => 'katsuobushi', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'קצואובושי', 'Katsuobushi' ),
		'summary' => $c99_text( 'קצואובושי הוא מוצר דג בוניטו מבושל, מעושן ומיובש, ולעיתים עובר מחזורי עובש וייבוש נוספים. דרגת העיבוד, אזור הייצור, צורת הגוש או השבבים ומועד הגילוח משפיעים על הארומה ועל מיצוי הדאשי.', 'Katsuobushi is a cooked, smoked and dried bonito product that may undergo additional mold and drying cycles. Processing grade, production region, block or shaving format and shaving time influence aroma and dashi extraction.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין קצואובושי ולבחור אותו לדאשי', 'Understand and choose katsuobushi for dashi' ),
		'primary_keyword' => $c99_text( 'קצואובושי', 'katsuobushi' ),
		'secondary_keywords' => array( 'he' => array( 'שבבי בוניטו', 'קצואובושי לדאשי' ), 'en' => array( 'bonito flakes', 'katsuobushi dashi' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-katsuobushi-dashi-processing', 'scientific', 'מחקר על דאשי מקצואובושי בחן את השפעת תהליך העובש, ולכן סוג העיבוד חייב להישמר כמשתנה ולא ככותרת איכות כללית.', 'Research on katsuobushi dashi examined the effect of the mold process, so processing type must remain a variable rather than a generic quality label.', 'peer_reviewed_context', 'category', array( 'katsuobushi-mold-dashi-1986' ) ),
			$c99_fact( 'fact-katsuobushi-fish-identity', 'structural', 'קצואובושי הוא ישות חומר גלם מדג; מין, מפעל, מספר אצווה ואלרגנים מאומתים שייכים למוצר בפועל.', 'Katsuobushi is a fish ingredient entity; species, facility, lot number and verified allergens belong to the actual product.', 'editorial_inference', 'entity', array( 'maff-dashi-umami' ), false ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'העיבוד נשמר כמשתנה מחקרי ומסחרי הניתן להשוואה.', 'Processing is retained as a research and commercial comparison variable.', array( 'fact-katsuobushi-dashi-processing' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'אזורי ייצור, מלאכה והיסטוריה יתווספו ממקורות תרבותיים ייעודיים.', 'Production regions, craft and history will be added from dedicated cultural sources.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'יצרנים ואזורים יתווספו רק ממקורות מזוהים.', 'Producers and regions will be added only from identified sources.' ),
			'economic' => $c99_profile( 'pending_evidence', 'מחיר ינורמל ל-100 גרם ולתפוקת ציר, תוך הפרדת גוש משבבים.', 'Price will be normalized per 100 grams and stock yield, separating blocks from shavings.' ),
			'structural' => $c99_profile( 'source_backed', 'הישות מקשרת חומר גלם, עיבוד, אלרגן, דאשי ו-IMP בלי להעתיק ערכי אצווה.', 'The entity links ingredient, processing, allergen, dashi and IMP without copying lot values.', array( 'fact-katsuobushi-fish-identity' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'dashi-ingredients', 'katsuobushi' ),
		'attributes' => array( 'pa_species' => array( 'product-specific' ), 'pa_origin' => array( 'product-specific' ), 'pa_processing_method' => array( 'cooked-smoked-dried-product-specific' ), 'pa_allergens' => array( 'fish' ) ),
		'tags' => array( 'katsuobushi', 'bonito', 'dashi', 'imp', 'smoking' ),
		'relations' => array(
			$c99_relation( 'used_in', 'preparation-ichiban-dashi', 'קצואובושי הוא רכיב יסוד במפת האיצ׳יבאן דאשי.', 'Katsuobushi is a foundational ingredient in the ichiban dashi map.', true, array( 'maff-dashi-umami' ), 'official_source' ),
			$c99_relation( 'complements', 'ingredient-kombu', 'השילוב נלמד בהקשר של טעם וקבילות דאשי.', 'The combination is studied in dashi taste and palatability context.', true, array( 'dashi-combination-palatability-2008' ), 'peer_reviewed_context' ),
		),
		'commerce_state' => 'active_offer',
		'woo_product_code' => 'product-honkarebushi-200g', 'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-honkarebushi-belly-200g-20260806' ),
		'cross_sell_ids' => array( 'ingredient-kombu', 'technique-dashi-extraction' ),
		'prompt_en' => 'Commercial culinary studio photograph of freshly shaved katsuobushi in paper-thin amber rose curls beside an unbranded whole smoked bonito block and clear dashi, precise fibrous texture, controlled warm side light, Japanese pantry styling, no packaging or text.',
		'compliance' => array( $c99_compliance( 'fish-allergen', 'יש לסמן אלרגן דגים ולבדוק את המין, היצרן, האצווה ותנאי האחסון של המוצר בפועל.', 'Fish allergen labeling and verification of species, producer, lot and storage conditions are required for the actual product.', array( 'israel-food-import' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'technique-dashi-extraction', 'type' => 'technique', 'slug' => 'dashi-extraction', 'parent_id' => 'preparation-ichiban-dashi',
		'name' => $c99_text( 'מיצוי דאשי מבוקר', 'Controlled dashi extraction' ),
		'summary' => $c99_text( 'מיצוי דאשי הוא תהליך מדיד של חומר גלם, מים, טמפרטורה, זמן וסינון. המודל שומר כל מתכון או ניסוי כמפרט נפרד כדי שניתן יהיה להשוות בהירות, תפוקה, טעם ועלות לליטר בלי להפוך תנאי מחקר יחידים לכלל מוחלט.', 'Dashi extraction is a measurable process of ingredient, water, temperature, time and filtration. The model stores each recipe or experiment as a separate specification so clarity, yield, taste and cost per liter can be compared without turning one research condition into a universal rule.' ),
		'seo_group' => 'techniques', 'primary_intent' => $c99_text( 'ללמוד מיצוי דאשי מדויק', 'Learn precise dashi extraction' ),
		'primary_keyword' => $c99_text( 'מיצוי דאשי', 'dashi extraction' ),
		'secondary_keywords' => array( 'he' => array( 'טמפרטורת דאשי', 'מיצוי קומבו' ), 'en' => array( 'dashi temperature', 'kombu extraction' ) ),
		'schema_type' => 'HowTo',
		'facts' => array(
			$c99_fact( 'fact-dashi-extraction-variables', 'scientific', 'תקציר כנס על ציר קומבו במים שונים תומך בשמירת סוג המים ותנאי המיצוי כחלק בלתי נפרד מן המדידה, כממצא ראשוני התלוי בתנאי הניסוי.', 'A conference proceeding on kombu stock extracted with different water types supports storing water type and extraction conditions with the measurement as preliminary, experiment-bound evidence.', 'conference_context', 'technique_context', array( 'kombu-water-extraction-conference-2024' ) ),
			$c99_fact( 'fact-dashi-process-entity', 'structural', 'כל גרסת דאשי תוכל לקשר BOM, ציוד, שלבי תהליך, בדיקות אצווה ועלות מנורמלת.', 'Each dashi version can link a BOM, equipment, process steps, batch tests and normalized cost.', 'editorial_inference', 'entity', array( 'maff-dashi-umami' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'סוג מים ותנאי מיצוי נשמרים לצד כל תוצאה.', 'Water type and extraction conditions stay attached to every result.', array( 'fact-dashi-extraction-variables' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'הבדלים אזוריים ומסורתיים בשיטות דאשי יתווספו ממקורות ייעודיים.', 'Regional and traditional differences in dashi methods will be added from dedicated sources.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'פרוטוקולים של בתי ספר ושפים יישמרו כגרסאות מיוחסות.', 'School and chef protocols will be stored as attributed versions.' ),
			'economic' => $c99_profile( 'pending_evidence', 'המודל יחשב עלות חומר, אנרגיה, זמן עבודה, תפוקה ופחת לליטר.', 'The model will calculate ingredient, energy, labor, yield and shrinkage cost per liter.' ),
			'structural' => $c99_profile( 'source_backed', 'התהליך הוא ישות נפרדת מן המנה ומחומרי הגלם.', 'The process is an entity separate from the dish and ingredients.', array( 'fact-dashi-process-entity' ) ),
		) ),
		'categories' => array( 'culinary-techniques', 'japan', 'dashi', 'extraction' ),
		'attributes' => array( 'pa_processing_method' => array( 'controlled-water-extraction' ), 'pa_equipment_required' => array( 'temperature-and-time-control' ) ),
		'tags' => array( 'dashi-extraction', 'kombu', 'katsuobushi', 'temperature', 'yield' ),
		'relations' => array(
			$c99_relation( 'part_of', 'preparation-ichiban-dashi', 'התהליך הוא שכבת ביצוע בתוך הכנת איצ׳יבאן דאשי.', 'The process is an execution layer within ichiban dashi preparation.', true, array( 'maff-dashi-umami' ), 'official_source' ),
			$c99_relation( 'requires', 'ingredient-kombu', 'גרסת קומבו דורשת חומר גלם ומפרט אצווה.', 'A kombu version requires an ingredient and lot specification.', true, array( 'kombu-water-extraction-conference-2024' ), 'conference_context' ),
		),
		'revenue_models' => array( 'education', 'content_to_commerce', 'curated_bundle' ),
		'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'culinary_students' ),
		'prompt_en' => 'Scientific culinary process photograph of kombu extraction in a clear temperature-controlled vessel, accurate water line, probe thermometer, timer and filter arranged cleanly, pale golden stock, restrained Japanese laboratory kitchen, no labels or logos.',
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-shoyu-koji', 'type' => 'ingredient', 'slug' => 'shoyu-koji-substrate', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'קוג׳י לשויו', 'Shoyu koji substrate' ),
		'summary' => $c99_text( 'קוג׳י לשויו הוא מצע קוג׳י המיועד לייצור רוטב סויה, בדרך כלל מסויה וחיטה שעברו הכנה מתאימה וגידול עובש קוג׳י מבוקר. הוא ישות נפרדת מקומה קוג׳י המבוסס על אורז, כדי לשמור על חומרי גלם, אלרגנים, אנזימים ותהליך נכונים.', 'Shoyu koji is a koji substrate intended for soy sauce production, commonly prepared from soybeans and wheat under controlled koji mold cultivation. It is separate from rice-based kome koji so ingredients, allergens, enzymes and process remain accurate.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין קוג׳י לשויו והבדלו מקומה קוג׳י', 'Understand shoyu koji and how it differs from kome koji' ),
		'primary_keyword' => $c99_text( 'קוג׳י לשויו', 'shoyu koji' ),
		'secondary_keywords' => array( 'he' => array( 'מצע קוג׳י סויה וחיטה' ), 'en' => array( 'soy wheat koji substrate' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-shoyu-koji-distinction', 'scientific', 'מקור MAFF מתאר את שלב הקוג׳י בייצור שויו; המודל מפריד אותו מקומה קוג׳י כדי לא לייחס מצע אורז לתהליך סויה וחיטה.', 'The MAFF source describes the koji stage in shoyu production; the model separates it from kome koji so a rice substrate is not assigned to a soybean and wheat process.', 'official_source', 'technique_context', array( 'maff-fermented-foods' ) ),
			$c99_fact( 'fact-shoyu-koji-allergen-scope', 'structural', 'סויה, חיטה, זן תרבית ותנאי גידול חייבים להיקשר למפרט תהליך או אצווה.', 'Soybean, wheat, culture strain and cultivation conditions must attach to a process or lot specification.', 'editorial_inference', 'entity', array( 'maff-fermented-foods', 'jas-shoyu-1703' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'הישות מונעת ערבוב בין מצעי קוג׳י שונים.', 'The entity prevents conflation of different koji substrates.', array( 'fact-shoyu-koji-distinction' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'ההקשר ההיסטורי של קוג׳י לשויו ייכתב ממקור תרבותי ייעודי.', 'The historical context of shoyu koji will be written from a dedicated cultural source.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'יצרני תרביות וגופי תקינה יתווספו בנפרד.', 'Culture producers and standards bodies will be added separately.' ),
			'economic' => $c99_profile( 'pending_evidence', 'הכדאיות תימדד לפי תפוקת מורומי, זמן, אובדן וחומרי גלם.', 'Economics will be measured by moromi yield, time, loss and inputs.' ),
			'structural' => $c99_profile( 'source_backed', 'אלרגנים ותנאי גידול שייכים למפרט ולא לשם הקטגוריה בלבד.', 'Allergens and cultivation conditions belong to a specification, not only a category label.', array( 'fact-shoyu-koji-allergen-scope' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'fermentation', 'shoyu-koji' ),
		'attributes' => array( 'pa_processing_method' => array( 'controlled-koji-cultivation' ), 'pa_allergens' => array( 'verify-soy-and-wheat-substrate' ), 'pa_fermentation_method' => array( 'shoyu-koji' ) ),
		'tags' => array( 'shoyu-koji', 'aspergillus-oryzae', 'soybean', 'wheat', 'enzymes' ),
		'relations' => array(
			$c99_relation( 'used_in', 'ingredient-kioke-shoyu', 'המצע הוא שלב חומרי בתהליך שויו.', 'The substrate is a material stage in shoyu production.', true, array( 'maff-fermented-foods' ), 'official_source' ),
			$c99_relation( 'complements', 'reaction-koji-enzymatic-hydrolysis', 'המצע מספק את ההקשר לפעילות האנזימטית.', 'The substrate supplies the context for enzymatic activity.', true, array( 'maff-fermented-foods' ), 'official_source' ),
		),
		'commerce_state' => 'reference_only',
		'prompt_en' => 'Macro scientific culinary photograph of shoyu koji substrate with prepared soybeans and cracked roasted wheat covered in a delicate ivory koji bloom, controlled humidity chamber context, precise natural texture, no mold exaggeration, no labels or packaging.',
		'compliance' => array( $c99_compliance( 'fermentation-control', 'ייצור מסחרי דורש תרבית מזוהה, בקרת טמפרטורה ולחות, תוכנית היגיינה ואימות אלרגנים.', 'Commercial production requires an identified culture, temperature and humidity control, a hygiene plan and allergen verification.', array( 'maff-fermented-foods', 'israel-food-import' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'equipment-kioke', 'type' => 'equipment', 'slug' => 'kioke-wooden-barrel', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'חבית עץ קיוקה', 'Kioke wooden barrel' ),
		'summary' => $c99_text( 'קיוקה היא חבית עץ מסורתית המשמשת יצרנים מסוימים להתססה ולהבשלה של שויו ומוצרים נוספים. חומר העץ, נפח, גיל, תחזוקה ומצב החבית הם נתוני ציוד ספציפיים, ולא הבטחה אוטומטית לאיכות או לזמן יישון.', 'A kioke is a traditional wooden barrel used by some producers for fermentation and maturation of shoyu and other products. Wood, capacity, age, maintenance and barrel condition are equipment-specific data, not automatic proof of quality or aging time.' ),
		'seo_group' => 'equipment', 'primary_intent' => $c99_text( 'להבין חביות קיוקה ותפקידן בשויו', 'Understand kioke barrels and their role in shoyu' ),
		'primary_keyword' => $c99_text( 'חבית קיוקה', 'kioke barrel' ),
		'secondary_keywords' => array( 'he' => array( 'חבית עץ לשויו' ), 'en' => array( 'wooden soy sauce barrel' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-kioke-documented-use', 'cultural', 'המקור הרשמי של Yamaroku מתעד שימוש ושימור של חביות קיוקה כחלק מייצור שויו.', 'Yamaroku official source documents the use and preservation of kioke barrels in shoyu production.', 'official_source', 'entity', array( 'yamaroku-about' ) ),
			$c99_fact( 'fact-kioke-equipment-boundary', 'structural', 'החבית היא ישות ציוד; יצרן, מוצר, תהליך ואצווה נשמרים כישויות נפרדות.', 'The barrel is an equipment entity; producer, product, process and lot remain separate entities.', 'editorial_inference', 'entity', array( 'yamaroku-about' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'pending_evidence', 'מיקרוביוטה, מעבר חמצן ותרכובות עץ דורשים מחקר או בדיקת חבית ספציפית.', 'Microbiota, oxygen transfer and wood compounds require research or a barrel-specific test.' ),
			'cultural' => $c99_profile( 'source_backed', 'השימוש והשימור מתועדים בדוגמת יצרן מזוהה.', 'Use and preservation are documented through an identified producer example.', array( 'fact-kioke-documented-use' ) ),
			'institutional' => $c99_profile( 'pending_evidence', 'בוני חביות וגופי מלאכה יישמרו כישויות נפרדות.', 'Barrel makers and craft bodies will be separate entities.' ),
			'economic' => $c99_profile( 'pending_evidence', 'המודל יפריד עלות רכישה, תחזוקה, נפח, חיי שירות ואובדן ייצור.', 'The model will separate acquisition, maintenance, capacity, service life and production loss.' ),
			'structural' => $c99_profile( 'source_backed', 'ציוד, יצרן, תהליך ואצווה אינם מתאחדים לישות אחת.', 'Equipment, producer, process and lot do not collapse into one entity.', array( 'fact-kioke-equipment-boundary' ) ),
		) ),
		'categories' => array( 'professional-equipment', 'fermentation', 'wooden-vessels', 'kioke' ),
		'attributes' => array( 'pa_material' => array( 'wood-product-specific' ), 'pa_equipment_required' => array( 'fermentation-facility-specific' ) ),
		'tags' => array( 'kioke', 'wooden-barrel', 'shoyu', 'fermentation-vessel' ),
		'relations' => array(
			$c99_relation( 'used_in', 'ingredient-kioke-shoyu', 'קיוקה היא כלי תהליך אפשרי בייצור שויו מסוג זה.', 'Kioke is a possible process vessel for this shoyu type.', true, array( 'yamaroku-about' ), 'official_source' ),
			$c99_relation( 'produced_by', 'producer-yamaroku-shoyu', 'המקור מתעד את החביות אצל Yamaroku כדוגמת יצרן.', 'The source documents the barrels at Yamaroku as a producer example.', true, array( 'yamaroku-about' ), 'official_source' ),
		),
		'revenue_models' => array( 'lead_generation', 'content_to_commerce' ),
		'customer_segments' => array( 'professional_chefs', 'foodservice_buyers', 'institutional_buyers' ),
		'prompt_en' => 'Museum-grade studio photograph of an authentic unbranded Japanese kioke wooden fermentation barrel, visible staves and bamboo hoops, clean maintained interior suggested without staging food unsafely, dramatic raking light, precise aged wood texture, neutral workshop background.',
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-kioke-shoyu', 'type' => 'ingredient', 'slug' => 'kioke-shoyu', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'שויו מותסס בקיוקה', 'Kioke-fermented shoyu' ),
		'summary' => $c99_text( 'שויו בקיוקה הוא רוטב סויה שתהליך התסיסה או היישון שלו כולל חבית עץ מסורתית. המונח קיוקה אינו מוכיח לבדו גיל, pH, מליחות, מקור או דרגת איכות; כל נתון מסחרי חייב להגיע מתווית, מפרט ספק או COA של המוצר.', 'Kioke shoyu is soy sauce whose fermentation or maturation includes a traditional wooden barrel. The word kioke alone does not prove age, pH, salinity, origin or grade; every commercial value must come from the product label, supplier specification or COA.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין שויו בקיוקה, התססה ואיכות', 'Understand kioke shoyu, fermentation and quality' ),
		'primary_keyword' => $c99_text( 'שויו קיוקה', 'kioke shoyu' ),
		'secondary_keywords' => array( 'he' => array( 'סויה בחבית עץ', 'שויו הונג׳וזו' ), 'en' => array( 'wood barrel soy sauce', 'honjozo shoyu' ) ),
		'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-shoyu-jas-category', 'structural', 'JAS 1703 מספק מסגרת תקנית לסיווג שויו, אך אינו הופך תיאור קטגוריה למפרט של מוצר מסוים.', 'JAS 1703 provides a classification framework for shoyu but does not turn a category description into a specification for a particular product.', 'regulatory_standard', 'category', array( 'jas-shoyu-1703' ) ),
			$c99_fact( 'fact-shoyu-fermentation', 'scientific', 'בתהליך מסורתי, אנזימי קוג׳י מפרקים חלבונים ועמילנים, ומיקרואורגניזמים תורמים חומצות ותרכובות ארומה.', 'In traditional production, koji enzymes break down proteins and starches, while microorganisms contribute acids and aroma compounds.', 'official_source', 'technique_context', array( 'maff-fermented-foods' ) ),
			$c99_fact( 'fact-shoyu-institutional-basis', 'institutional', 'מסגרת JAS והדוגמה המתועדת של יצרן קיוקה מוצגות בנפרד, בלי לטעון שהן מאשרות מוצר מסוים המוצע למכירה.', 'The JAS framework and the documented kioke producer example are presented separately, without claiming that either certifies a particular product offered for sale.', 'official_source', 'entity', array( 'jas-shoyu-1703', 'yamaroku-about' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'הפרופיל המדעי מתאר אנזימים והתססה; מספרים שייכים למוצר ולאצווה.', 'The scientific profile describes enzymes and fermentation; numbers belong to a product and lot.', array( 'fact-shoyu-fermentation' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'היסטוריית החבית תישען על יצרן ומקור מתועדים.', 'Barrel history will rely on documented producer and source evidence.' ),
			'institutional' => $c99_profile( 'source_backed', 'ההסבר מקשר לתקן JAS וליצרן דוגמה נפרד, בלי לטעון לקשר מסחרי.', 'The explanation links to the JAS standard and a separate example producer without claiming a commercial relationship.', array( 'fact-shoyu-institutional-basis' ) ),
			'economic' => $c99_profile( 'pending_evidence', 'השוואת מחיר תכלול נפח, חנקן כולל, זמן, כלי, יבוא והובלה.', 'Price comparison will include volume, total nitrogen, time, vessel, import and shipping.' ),
			'structural' => $c99_profile( 'source_backed', 'סיווג JAS עוזר להבין את סוג השויו, אך פרטי גיל, מליחות ואלרגנים נבדקים בתווית של המוצר שנבחר.', 'JAS classification helps explain the shoyu type, while age, salinity and allergens are checked on the selected product label.', array( 'fact-shoyu-jas-category' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'sauces-and-fermentation', 'shoyu', 'kioke' ),
		'attributes' => array( 'pa_origin' => array( 'product-specific' ), 'pa_fermentation_method' => array( 'koji-fermentation' ), 'pa_vessel' => array( 'kioke-wooden-barrel' ), 'pa_allergens' => array( 'verify-sku-label' ) ),
		'tags' => array( 'shoyu', 'kioke', 'koji', 'fermentation', 'umami' ),
		'relations' => array(
			$c99_relation( 'produced_by', 'producer-yamaroku-shoyu', 'ימרוקו נשמר כיצרן מתועד לדוגמה, לא כספק של Complete99.', 'Yamaroku is retained as a documented example producer, not a Complete99 supplier.', true, array( 'yamaroku-about' ), 'official_source' ),
			$c99_relation( 'complements', 'dish-edomae-nigiri', 'שויו יכול להשתלב במפת ניגירי אדומאה.', 'Shoyu can participate in the Edomae nigiri map.' ),
			$c99_relation( 'requires', 'ingredient-shoyu-koji', 'קוג׳י לשויו הוא מצע התהליך המתאים, בנפרד מקומה קוג׳י.', 'Shoyu koji is the appropriate process substrate, separate from kome koji.', true, array( 'maff-fermented-foods' ), 'official_source' ),
			$c99_relation( 'requires', 'equipment-kioke', 'ישות הקיוקה מתעדת את כלי העץ בנפרד מן הרוטב.', 'The kioke entity documents the wooden vessel separately from the sauce.', true, array( 'yamaroku-about' ), 'official_source' ),
			$c99_relation( 'requires', 'reaction-koji-enzymatic-hydrolysis', 'פירוק אנזימטי הוא שכבת מדע מרכזית בתהליך.', 'Enzymatic hydrolysis is a central science layer in the process.', true, array( 'maff-fermented-foods' ), 'official_source' ),
		),
		'commerce_state' => 'active_offer',
		'woo_product_code' => 'product-yamaroku-tsurubishio-500ml',
		'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-yamaroku-tsurubishio-500ml-20260806' ),
		'cross_sell_ids' => array( 'ingredient-fresh-wasabi', 'ingredient-yakinori', 'preparation-sushi-shari' ),
		'prompt_en' => 'Artisanal Japanese soy sauce flowing into dark stoneware, deep ruby-brown highlights, authentic cedar kioke softly out of focus, whole soybeans and roasted wheat presented as source materials, dramatic side light, macro commercial detail, unbranded vessel.',
		'compliance' => array(
			$c99_compliance( 'shoyu-label-and-lot', 'סויה, חיטה, מלח, גיל, pH ודרגת איכות יוצגו רק לפי תווית המוצר ומסמכי האצווה בפועל.', 'Soy, wheat, salt, age, pH and grade are displayed only from the actual product label and lot documentation.', array( 'jas-shoyu-1703', 'israel-food-import' ) ),
		),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-kome-koji', 'type' => 'ingredient', 'slug' => 'kome-koji', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'קומה קוג׳י', 'Kome koji' ),
		'summary' => $c99_text( 'קומה קוג׳י הוא אורז מאודה שעליו גדל Aspergillus oryzae בתהליך מבוקר. הוא משמש בין היתר במיסו, סאקה ומירין. אין לבלבל אותו עם קוג׳י לשויו, שבדרך כלל מבוסס על מצע סויה וחיטה ויש להתייחס אליו בנפרד. זן התרבית, הטמפרטורה, הלחות והמצע חייבים להיות מתועדים.', 'Kome koji is steamed rice cultivated with Aspergillus oryzae under controlled conditions. It is used in products including miso, sake and mirin. It must not be conflated with shoyu koji, which typically uses a soybean and wheat substrate and must be treated separately. Culture strain, temperature, humidity and substrate must be documented.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין קוג׳י, אנזימים והתססה', 'Understand koji, enzymes and fermentation' ),
		'primary_keyword' => $c99_text( 'קומה קוג׳י', 'kome koji' ), 'secondary_keywords' => array( 'he' => array( 'אורז קוג׳י', 'קומה קוג׳י להתססה' ), 'en' => array( 'rice koji', 'kome koji fermentation' ) ), 'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-koji-fermented-food-role', 'scientific', 'MAFF מתאר קוג׳י כבסיס לתהליכי מזון מותסס יפניים, כאשר אנזימים מפרקים עמילנים וחלבונים.', 'MAFF describes koji as a foundation of Japanese fermented foods in which enzymes break down starches and proteins.', 'official_source', 'technique_context', array( 'maff-fermented-foods' ) ),
			$c99_fact( 'fact-koji-process-node', 'structural', 'בגרף, קוג׳י מקשר חומר גלם, תרבית, אנזים, תהליך ומוצר מותסס.', 'In the graph, koji connects substrate, culture, enzyme, process and fermented product.', 'editorial_inference', 'entity', array( 'maff-fermented-foods' ), false ),
			$c99_fact( 'fact-hishiroku-dried-koji-500g-market-20260806', 'economic', 'בדף החנות הרשמי נצפתה אריזת קוג׳י אורז מיובש 500 גרם במחיר 1,050 ין כולל מס, עם אפשרות הוספה לסל. הדף מציין תרבית SR-108, תפוקה מקבילה לכ-660 גרם קוג׳י טרי, קירור ושימוש בתוך כארבעה חודשים מן הרכישה.', 'The official shop page showed a 500 g pack of dried rice koji at JPY 1,050 including tax with add-to-cart available. The page identifies SR-108 culture, an equivalent yield of about 660 g fresh koji, refrigeration and use within about four months of purchase.', 'official_source', 'supplier_specification', array( 'hishiroku-dried-rice-koji-500g-listing-2026' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'עמילאזות ופרוטאזות הן המפתח, עם תנאי תהליך שנשמרים כמפרט נפרד.', 'Amylases and proteases are central, with process conditions stored separately.', array( 'fact-koji-fermented-food-role' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'השימושים יתועדו לפי מוצר מותסס ומסורת.', 'Uses will be documented by fermented product and tradition.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'יצרנים ומעבדות ייקשרו רק עם תיעוד תרבית ומוצר.', 'Producers and laboratories will link only with culture and product documentation.' ),
			'economic' => $c99_profile( 'source_backed', 'מחיר המקור נשמר עבור אריזת 500 גרם המדויקת, בנפרד ממחיר המכירה בישראל ומעלות נוחתת.', 'The source-market price is retained for the exact 500 g pack, separate from the Israeli sell price and landed cost.', array( 'fact-hishiroku-dried-koji-500g-market-20260806' ) ),
			'structural' => $c99_profile( 'source_backed', 'נקודת חיבור בין המדע למוצרים מותססים.', 'A junction between science and fermented products.', array( 'fact-koji-process-node' ) ),
		) ),
		'categories' => array( 'scientific-pantry', 'fermentation', 'koji', 'rice-koji' ),
		'attributes' => array( 'pa_origin' => array( 'sku-or-lot-specific' ), 'pa_species' => array( 'aspergillus-oryzae' ), 'pa_processing_method' => array( 'solid-state-cultivation' ), 'pa_storage_type' => array( 'label-specific-fresh-dried-frozen-or-shelf-stable' ) ),
		'tags' => array( 'koji', 'amylase', 'protease', 'fermentation' ),
		'relations' => array(
			$c99_relation( 'requires', 'reaction-koji-enzymatic-hydrolysis', 'האנזימים מניעים פירוק עמילנים וחלבונים.', 'The enzymes drive starch and protein hydrolysis.', true, array( 'maff-fermented-foods' ), 'official_source' ),
			$c99_relation( 'complements', 'ingredient-koji-starter-culture', 'תרבית קוג׳י אבקתית היא קלט נפרד לגידול קוג׳י חדש ואינה תוספת שנדרשת לקוג׳י מוכן.', 'Powdered koji starter is a separate input for cultivating new koji and is not an additive required for prepared koji.', true, array( 'hishiroku-chouhaku-kin-20g-listing-2026', 'hishiroku-dried-rice-koji-500g-listing-2026' ), 'official_source' ),
		),
		'commerce_state' => 'active_offer',
		'woo_product_code' => 'product-hishiroku-dried-rice-koji-500g',
		'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price',
		'observation_entity_ids' => array( 'listing-hishiroku-dried-rice-koji-500g-20260806' ),
		'value_proposition' => $c99_text( 'קוג׳י אורז מיובש באריזה מדויקת, עם הוראות אחסון ושימוש מתועדות, המחובר למתכוני אמאזאקה, שיו קוג׳י והתססות ולתרבית המקצועית הנפרדת.', 'Exact-pack dried rice koji with documented storage and use directions, linked to amazake, shio-koji and fermentation paths and to the separate professional starter culture.' ),
		'margin_scenario' => array(
			'currency' => 'ILS', 'landed_cost_low' => null, 'landed_cost_high' => null, 'retail_price_low' => 119, 'retail_price_high' => 119, 'gross_margin_low' => null, 'gross_margin_high' => null,
			'basis' => $c99_text( 'מחיר המכירה המאושר הוא 119 ש״ח. מחיר המקור שנצפה הוא 1,050 ין, כ-20.04 ש״ח לפי שער בנק ישראל מ-6.8.2026. זה אינו מחיר ספק או עלות נוחתת, ולכן רווחיות ושיעור רווח נשארים לא מחושבים עד לקבלת חשבונית, שילוח, מסים, קירור, פחת וטיפול.', 'The approved sell price is ILS 119. The observed source-market price is JPY 1,050, about ILS 20.04 at the Bank of Israel rate on August 6 2026. This is not a supplier quote or landed cost, so profitability and gross margin remain uncalculated until invoice, freight, tax, refrigeration, shrinkage and handling are known.' ),
			'confidence' => 'pending', 'reviewed_at' => '2026-08-06',
		),
		'cross_sell_ids' => array( 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-kioke-shoyu' ),
		'prompt_en' => 'Extreme macro of white Aspergillus oryzae growth covering distinct steamed rice grains, paired with a clean fermentation tray and controlled humidity cues, scientific yet appetizing culinary studio photography, accurate mycelial texture, neutral white balance.',
		'compliance' => array( $c99_compliance( 'koji-culture-provenance', 'מוצר קוג׳י למכירה דורש זיהוי יצרן, תרבית, תנאי אחסון ותווית תקינה; זיהוי המין בספרות אינו אישור לאצווה.', 'A sellable koji product requires producer, culture, storage and compliant label evidence; species identity in literature is not lot approval.', array( 'israel-food-import' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-koji-starter-culture', 'type' => 'ingredient', 'slug' => 'koji-starter-culture', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'תרבית קוג׳י Chouhaku-kin אבקתית', 'Chouhaku-kin powdered koji starter' ),
		'summary' => $c99_text( 'Chouhaku-kin היא תרבית tane-koji אבקתית בעלת נבגים לבנים, המיועדת לפי דף היצרן לגידול קוג׳י עבור שיו קוג׳י ומיסו. אריזת 20 גרם מיועדת לכמות חומר גלם מוגדרת בדף המוצר, נשמרת בקירור ודורשת עבודה לפי הוראות היצרן. זהו קלט תהליכי מרוכז, לא קוג׳י מוכן לאכילה.', 'Chouhaku-kin is a powdered white-spore tane-koji starter intended by the maker listing for cultivating koji for shio-koji and miso. The 20 g sachet is specified for a stated substrate quantity, requires refrigeration and must be used according to maker directions. It is a concentrated process input, not ready-to-eat koji.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין ולבחור תרבית קוג׳י מקצועית', 'Understand and choose a professional koji starter culture' ),
		'primary_keyword' => $c99_text( 'תרבית קוג׳י אבקתית', 'powdered koji starter culture' ), 'secondary_keywords' => array( 'he' => array( 'tane-koji', 'Chouhaku-kin', 'נבגי קוג׳י למיסו' ), 'en' => array( 'tane-koji', 'Chouhaku-kin', 'koji spores for miso' ) ), 'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-chouhaku-kin-maker-directions-20260806', 'scientific', 'דף החנות הרשמי מתאר תרבית קוג׳י אבקתית בעלת נבגים לבנים לשיו קוג׳י ולמיסו. הוא מציין 20 גרם עבור 15 ק״ג חומר גלם לפני שטיפה, המלצה להגדיל מינון בעבודה בכמות קטנה, חיי שימוש של שישה חודשים וקירור.', 'The official shop page describes a powdered white-spore koji starter for shio-koji and miso. It states 20 g for 15 kg of substrate before washing, recommends a higher dose for small batches, gives a six-month use period and requires refrigeration.', 'official_source', 'supplier_specification', array( 'hishiroku-chouhaku-kin-20g-listing-2026' ) ),
			$c99_fact( 'fact-chouhaku-kin-price-20260806', 'economic', 'ב-6.8.2026 נצפה מחיר של 630 ין כולל מס לשקית 20 גרם, עם אפשרות הוספה לסל ומשלוח נפרד.', 'On August 6 2026, JPY 630 including tax was observed for a 20 g sachet, with add-to-cart available and shipping separate.', 'official_source', 'market_snapshot', array( 'hishiroku-chouhaku-kin-20g-listing-2026' ) ),
			$c99_fact( 'fact-chouhaku-kin-official-listing-identity', 'institutional', 'שם המוצר, צורת האבקה והוראות השימוש מיוחסים לחנות המקוונת הרשמית של Hishiroku Moyashi שנבדקה.', 'Product name, powdered form and use directions are attributed to the reviewed Hishiroku Moyashi official online store.', 'official_source', 'entity', array( 'hishiroku-chouhaku-kin-20g-listing-2026' ) ),
			$c99_fact( 'fact-koji-starter-entity-boundary', 'structural', 'תרבית Chouhaku-kin, קוג׳י אורז מיובש והתוצר המותסס הם חומרים ושלבים שונים המחוברים בתהליך.', 'Chouhaku-kin starter, dried rice koji and the fermented output are different materials and stages connected by the process.', 'official_source', 'entity', array( 'hishiroku-chouhaku-kin-20g-listing-2026', 'hishiroku-dried-rice-koji-500g-listing-2026' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'המינון, המצע, הטמפרטורה, הלחות והזמן נשמרים כהוראות תהליך ולא כהבטחה כללית.', 'Dose, substrate, temperature, humidity and time remain process directions rather than a general promise.', array( 'fact-chouhaku-kin-maker-directions-20260806' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'היסטוריית ייצור tane-koji דורשת מקורות תרבותיים נפרדים.', 'The history of tane-koji production requires separate cultural sources.' ),
			'institutional' => $c99_profile( 'source_backed', 'זהות המוצר וההוראות מיוחסות לדף החנות הרשמי שנבדק.', 'Product identity and directions are attributed to the reviewed official shop page.', array( 'fact-chouhaku-kin-official-listing-identity' ) ),
			'economic' => $c99_profile( 'source_backed', 'מחיר המקור מתועד לשקית 20 גרם המדויקת ובנפרד ממחיר Complete99.', 'The source-market price is documented for the exact 20 g sachet and kept separate from the Complete99 price.', array( 'fact-chouhaku-kin-price-20260806' ) ),
			'structural' => $c99_profile( 'source_backed', 'תרבית, קוג׳י מוכן, מצע ותוצר מותסס הם חומרים ושלבים שונים המקושרים בתהליך.', 'Starter culture, prepared koji, substrate and fermented output are distinct materials and stages linked by process.', array( 'fact-koji-starter-entity-boundary' ) ),
		) ),
		'categories' => array( 'scientific-pantry', 'fermentation', 'koji', 'starter-cultures' ),
		'attributes' => array( 'pa_origin' => array( 'maker-and-lot-specific' ), 'pa_processing_method' => array( 'powdered-tane-koji' ), 'pa_storage_type' => array( 'refrigerated' ), 'pa_equipment_required' => array( 'controlled-koji-cultivation' ) ),
		'tags' => array( 'koji-starter', 'tane-koji', 'chouhaku-kin', 'miso', 'shio-koji' ),
		'relations' => array(
			$c99_relation( 'used_in', 'ingredient-kome-koji', 'התרבית יכולה לשמש כקלט לגידול קוג׳י אורז חדש לפי הוראות היצרן.', 'The starter can serve as an input for cultivating new rice koji according to maker directions.', true, array( 'hishiroku-chouhaku-kin-20g-listing-2026', 'maff-fermented-foods' ), 'official_source' ),
			$c99_relation( 'requires', 'reaction-koji-enzymatic-hydrolysis', 'הקוג׳י שגדל מפעיל מערכות אנזימטיות בהמשך התהליך.', 'The cultivated koji contributes enzymatic systems in the subsequent process.', true, array( 'maff-fermented-foods' ), 'official_source' ),
		),
		'commerce_state' => 'active_offer', 'woo_product_code' => 'product-hishiroku-chouhaku-kin-20g', 'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-hishiroku-chouhaku-kin-20g-20260806' ),
		'value_proposition' => $c99_text( 'תרבית מקצועית מרוכזת באריזה קטנה, עם הוראות יצרן מתועדות וקישורים ישירים לקוג׳י מוכן, מצעים, תהליכי התססה וציוד בקרה.', 'A concentrated professional culture in a small pack, with documented maker directions and direct links to prepared koji, substrates, fermentation processes and control equipment.' ),
		'margin_scenario' => array(
			'currency' => 'ILS', 'landed_cost_low' => null, 'landed_cost_high' => null, 'retail_price_low' => 109, 'retail_price_high' => 109, 'gross_margin_low' => null, 'gross_margin_high' => null,
			'basis' => $c99_text( 'מחיר המכירה המאושר הוא 109 ש״ח. מחיר המקור שנצפה הוא 630 ין, כ-12.03 ש״ח לפי שער בנק ישראל מ-6.8.2026. זה אינו מחיר ספק או עלות נוחתת, ולכן רווחיות ושיעור רווח נשארים לא מחושבים עד לקבלת חשבונית, שילוח, מסים, קירור, פחת וטיפול.', 'The approved sell price is ILS 109. The observed source-market price is JPY 630, about ILS 12.03 at the Bank of Israel rate on August 6 2026. This is not a supplier quote or landed cost, so profitability and gross margin remain uncalculated until invoice, freight, tax, refrigeration, shrinkage and handling are known.' ),
			'confidence' => 'pending', 'reviewed_at' => '2026-08-06',
		),
		'cross_sell_ids' => array( 'ingredient-kome-koji', 'ingredient-koshihikari-rice', 'ingredient-kioke-shoyu' ),
		'prompt_en' => 'Commercial culinary studio photograph of a plain cream 20 g sachet of powdered koji starter beside a small ceramic dish holding fine pale culture powder, a covered cedar koji tray and a digital thermometer softly out of focus, clean neutral background, precise scale, no bottle, no logos, no text, no certification marks.',
		'compliance' => array( $c99_compliance( 'koji-starter-maker-directions', 'יש לשמור בקירור, להגן מלחות ולהשתמש רק לפי מינון, מצע, זמן, טמפרטורה ולחות שעל אריזת היצרן. אין לצרוך תרבית מרוכזת כמזון מוכן.', 'Keep refrigerated, protect from moisture and use only according to the dose, substrate, time, temperature and humidity on the maker pack. Do not consume concentrated starter as a ready food.', array( 'hishiroku-chouhaku-kin-20g-listing-2026', 'israel-food-import' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-fresh-wasabi', 'type' => 'ingredient', 'slug' => 'fresh-wasabi-rhizome', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'קנה שורש וואסבי טרי', 'Fresh wasabi rhizome' ),
		'summary' => $c99_text( 'וואסבי טרי מזוהה ברמת המין, הזן, המקור והאצווה. גרירה שוברת תאים ומפגישה גלוקוזינולטים עם Myrosinase ליצירת איזותיוציאנטים נדיפים; העוצמה וההרכב משתנים לפי גנטיקה, חלק הצמח, עונה וסביבה.', 'Fresh wasabi is identified by species, cultivar, origin and lot. Grating ruptures cells and brings glucosinolates together with myrosinase to form volatile isothiocyanates; intensity and composition vary with genetics, plant part, season and environment.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין וואסבי טרי, חריפות ואיכות', 'Understand fresh wasabi, pungency and quality' ),
		'primary_keyword' => $c99_text( 'שורש וואסבי טרי', 'fresh wasabi rhizome' ), 'secondary_keywords' => array( 'he' => array( 'וואסבי יפני אמיתי', 'איזותיוציאנטים בוואסבי' ), 'en' => array( 'real Japanese wasabi', 'wasabi isothiocyanates' ) ), 'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-wasabi-itc-variation', 'scientific', 'ריכוזי AITC ו-6-MSITC השתנו בין הגישות הגנטיות שנדגמו. בניסוי העונתי שדווח בקני שורש, 6-MSITC היה נמוך יותר באביב, ואילו AITC לא השתנה באופן מובהק בין העונות שנבדקו.', 'AITC and 6-MSITC concentrations varied among sampled accessions. In the reported seasonal rhizome trial, 6-MSITC was lower in spring, while AITC did not differ significantly among tested seasons.', 'peer_reviewed_context', 'category', array( 'wasabi-itc-2023' ) ),
			$c99_fact( 'fact-wasabi-lot-identity', 'structural', 'מוצר יישא מין, זן, מקור, תאריך קציר ותנאי קירור כאשר הנתונים זמינים.', 'A product will carry species, cultivar, origin, harvest date and cold-chain fields when available.', 'editorial_inference', 'entity', array( 'wasabi-itc-2023' ), false ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'איזותיוציאנטים מתועדים כהקשר מחקרי; mg/g לא יוצג ללא בדיקת המוצר.', 'Isothiocyanates are documented as research context; mg/g is not displayed without product testing.', array( 'fact-wasabi-itc-variation' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'אזורי גידול ושיטות מים יתועדו בנפרד.', 'Growing regions and water-cultivation methods will be documented separately.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'מגדלים ושווקים ייקשרו רק לאחר אימות מקור.', 'Growers and markets will link only after origin verification.' ),
			'economic' => $c99_profile( 'pending_evidence', 'השוואת ערך תבדיל בין קנה שורש שלם, משחה, אבקה ותערובת חזרת.', 'Value comparison will distinguish whole rhizome, paste, powder and horseradish blend.' ),
			'structural' => $c99_profile( 'source_backed', 'הישות מחברת מין, מולקולה, כלי גרירה, קירור ומנות.', 'The entity connects species, molecule, grater, cold chain and dishes.', array( 'fact-wasabi-lot-identity' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'fresh-aromatics', 'wasabi' ),
		'attributes' => array( 'pa_origin' => array( 'product-specific' ), 'pa_species' => array( 'eutrema-japonicum-product-specific' ), 'pa_storage_type' => array( 'refrigerated-perishable' ), 'pa_flavor_profile' => array( 'volatile-pungency' ) ),
		'tags' => array( 'wasabi', 'aitc', 'myrosinase', 'fresh-rhizome' ),
		'relations' => array(
			$c99_relation( 'contains', 'molecule-allyl-isothiocyanate', 'AITC הוא חלק מהקשר החריפות לאחר גרירה.', 'AITC is part of the pungency context after grating.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
			$c99_relation( 'references', 'guide-wasabi-aitc', 'מדריך AITC מסביר את המדע שמאחורי חריפות הוואסבי.', 'The AITC guide explains the science behind wasabi pungency.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
			$c99_relation( 'complements', 'equipment-wasabi-grater', 'מגררת ייעודית מסייעת להכנת קנה שורש טרי, ויש לבדוק את החומר והמידות של כל דגם.', 'A dedicated grater helps prepare a fresh rhizome, and each model should be checked for its material and dimensions.', true, array( 'yamamoto-haganezame-spec' ), 'official_source' ),
			$c99_relation( 'complements', 'dish-edomae-nigiri', 'וואסבי טרי יכול להשתלב במפת ניגירי.', 'Fresh wasabi can participate in a nigiri map.' ),
			$c99_relation( 'references', 'ingredient-fresh-dutch-wasabi', 'מוצר 50 עד 60 גרם בגידול הולנדי נשמר כגרסת מוצר נפרדת כדי שהמקור, המשקל והמחיר לא יתערבבו עם אריזת הוואסבי היפני.', 'The Dutch-grown 50 to 60 g offer is kept as a separate product version so origin, weight and price are not conflated with the Japanese wasabi pack.', true, array( 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026' ), 'official_source' ),
		),
		'commerce_state' => 'active_offer',
		'woo_product_code' => 'product-fresh-japanese-wasabi-250g',
		'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-fresh-japanese-wasabi-250g-20260806' ),
		'cross_sell_ids' => array( 'equipment-wasabi-grater', 'ingredient-kioke-shoyu' ),
		'prompt_en' => 'Fresh whole Eutrema japonicum rhizome with stems attached, freshly grated pale-green wasabi on a fine unbranded grater, crisp fibrous cut surface, cool dark stone background, controlled macro commercial lighting, accurate scale and natural moisture.',
		'compliance' => array( $c99_compliance( 'wasabi-no-safety-substitute', 'חריפות וואסבי אינה תחליף לקירור, בקרת טפילים או היגיינה של דג נא, ואין להציג אותה כהבטחה רפואית.', 'Wasabi pungency is not a substitute for refrigeration, parasite control or raw-fish hygiene and must not be presented as a medical promise.', array( 'wasabi-itc-2023' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-fresh-dutch-wasabi', 'type' => 'ingredient', 'slug' => 'dutch-grown-fresh-wasabi', 'parent_id' => 'ingredient-fresh-wasabi',
		'name' => $c99_text( 'וואסבי טרי בגידול הולנדי, 50 עד 60 גרם', 'Dutch-grown fresh wasabi, 50 to 60 g' ),
		'summary' => $c99_text( 'קנה שורש וואסבי טרי שגדל בחממה בהולנד ונמכר כיחידה במשקל 50 עד 60 גרם. דף המגדל מתאר גידול של 18 עד 24 חודשים בחממות בהולנד וקטיף ידני. מקור הולנדי, משקל יחידה ותנאי קירור נשמרים ברמת המוצר ואינם מוצגים כוואסבי יפני.', 'A fresh wasabi rhizome grown in a Dutch greenhouse and sold as one 50 to 60 g piece. The grower page describes 18 to 24 months in its Netherlands greenhouses and hand harvesting. Dutch origin, piece weight and refrigeration remain product-level facts and are not presented as Japanese wasabi.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'לבחור וואסבי טרי הולנדי ביחידה קטנה', 'Choose a small Dutch-grown fresh wasabi rhizome' ),
		'primary_keyword' => $c99_text( 'וואסבי טרי בגידול הולנדי', 'Dutch-grown fresh wasabi' ), 'secondary_keywords' => array( 'he' => array( 'קנה שורש וואסבי 50 גרם', 'וואסבי טרי 50 עד 60 גרם' ), 'en' => array( 'fresh wasabi 50 g', '50 to 60 g wasabi rhizome' ) ), 'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-dutch-wasabi-greenhouse-origin-20260806', 'institutional', 'דף המגדל מתאר קנה שורש מן החממות שלו בהולנד, גידול של 18 עד 24 חודשים וקטיף ידני. זו הצהרת מגדל למוצר, לא בדיקת מקור עצמאית של האצווה.', 'The grower page describes a rhizome from its Netherlands greenhouses, cultivated for 18 to 24 months and harvested by hand. This is a grower statement for the product, not independent lot-origin testing.', 'official_source', 'supplier_specification', array( 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026' ) ),
			$c99_fact( 'fact-dutch-wasabi-price-20260806', 'economic', 'ב-6.8.2026 נצפה מחיר של 17.51 אירו כולל מע״מ ליחידה במשקל 50 עד 60 גרם, כאשר המוצר סומן במלאי.', 'On August 6 2026, EUR 17.51 including VAT was observed for one 50 to 60 g piece marked in stock.', 'official_source', 'market_snapshot', array( 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026' ) ),
			$c99_fact( 'fact-dutch-wasabi-science-boundary', 'scientific', 'מחקר הוואסבי מתעד שונות באיזותיוציאנטים בין גנטיקה, חלקי צמח ועונות. אין לייחס ערך AITC או 6-MSITC לפריט ההולנדי בלי בדיקת אצווה.', 'Wasabi research documents isothiocyanate variation across genetics, plant parts and seasons. No AITC or 6-MSITC value is assigned to the Dutch item without lot testing.', 'peer_reviewed_context', 'category', array( 'wasabi-itc-2023' ) ),
			$c99_fact( 'fact-dutch-wasabi-product-subtype', 'structural', 'מקור הולנדי, טווח משקל של 50 עד 60 גרם ומחיר ליחידה נשמרים בנפרד מאריזת הוואסבי היפני 250 גרם.', 'Dutch origin, the 50 to 60 g weight range and per-piece price are kept distinct from the 250 g Japanese wasabi pack.', 'official_source', 'entity', array( 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026', 'fresh-japanese-wasabi-250g-listing-2026' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'המדע מוצג כהקשר קטגוריה ומופרד ממדידת אצווה.', 'Science is presented as category context and separated from lot measurement.', array( 'fact-dutch-wasabi-science-boundary' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'ההקשר התרבותי של גידול וואסבי מחוץ ליפן דורש מקורות ייעודיים.', 'The cultural context of cultivating wasabi outside Japan requires dedicated sources.' ),
			'institutional' => $c99_profile( 'source_backed', 'מקור הגידול מיוחס לדף המגדל ומסומן כהצהרת מקור, לא כהסמכה.', 'Cultivation origin is attributed to the grower page and marked as a source statement rather than certification.', array( 'fact-dutch-wasabi-greenhouse-origin-20260806' ) ),
			'economic' => $c99_profile( 'source_backed', 'המחיר נשמר ליחידה בטווח משקל מוגדר ובנפרד ממחיר לפי קילוגרם או אריזה יפנית.', 'Price is retained for one defined weight-range piece, separate from per-kilogram pricing or a Japanese pack.', array( 'fact-dutch-wasabi-price-20260806' ) ),
			'structural' => $c99_profile( 'source_backed', 'המוצר נשמר בנפרד מן ההסבר הכללי על וואסבי ומאריזת הוואסבי היפני 250 גרם.', 'This product remains distinct from the general wasabi guidance and the 250 g Japanese pack.', array( 'fact-dutch-wasabi-product-subtype' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'fresh-aromatics', 'wasabi', 'dutch-grown' ),
		'attributes' => array( 'pa_origin' => array( 'netherlands-grower-statement' ), 'pa_species' => array( 'eutrema-japonicum-grower-description' ), 'pa_storage_type' => array( 'refrigerated-perishable' ), 'pa_flavor_profile' => array( 'volatile-pungency' ) ),
		'tags' => array( 'wasabi', 'fresh-rhizome', 'dutch-grown', 'weight-50-60g', 'aitc' ),
		'relations' => array(
			$c99_relation( 'part_of', 'ingredient-fresh-wasabi', 'הפריט הוא תת-ישות מקור ומשקל של וואסבי טרי.', 'The item is an origin-and-weight subtype of fresh wasabi.', true, array( 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026' ), 'official_source' ),
			$c99_relation( 'complements', 'equipment-wasabi-grater', 'דף המוצר ממליץ על מגררת וואסבי ייעודית ולא על מגררת מטבח רגילה.', 'The product page recommends a dedicated wasabi grater rather than a standard kitchen grater.', true, array( 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026', 'yamamoto-haganezame-spec' ), 'official_source' ),
			$c99_relation( 'references', 'guide-wasabi-aitc', 'מדריך AITC מסביר את הקשר המדעי של חריפות לאחר גרירה.', 'The AITC guide explains the scientific context of pungency after grating.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
		),
		'commerce_state' => 'active_offer', 'woo_product_code' => 'product-fresh-wasabi-50-60g', 'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-dutch-wasabi-50-60g-20260806' ),
		'value_proposition' => $c99_text( 'יחידה קטנה ונגישה של וואסבי טרי עם מקור ומשקל שקופים, המחוברת למגררת המתאימה, למדעי החריפות ולמוצרי שויו וסושי משלימים.', 'An accessible small piece of fresh wasabi with transparent origin and weight, linked to the right grater, pungency science and complementary shoyu and sushi products.' ),
		'margin_scenario' => array(
			'currency' => 'ILS', 'landed_cost_low' => null, 'landed_cost_high' => null, 'retail_price_low' => 119, 'retail_price_high' => 119, 'gross_margin_low' => null, 'gross_margin_high' => null,
			'basis' => $c99_text( 'מחיר המכירה המאושר הוא 119 ש״ח. מחיר המקור שנצפה הוא 17.51 אירו, כ-60.89 ש״ח לפי שער בנק ישראל מ-6.8.2026. זה אינו מחיר ספק או עלות נוחתת, ולכן רווחיות ושיעור רווח נשארים לא מחושבים עד לקבלת חשבונית, שילוח בקירור, מסים, פחת וטיפול.', 'The approved sell price is ILS 119. The observed source-market price is EUR 17.51, about ILS 60.89 at the Bank of Israel rate on August 6 2026. This is not a supplier quote or landed cost, so profitability and gross margin remain uncalculated until invoice, refrigerated freight, tax, shrinkage and handling are known.' ),
			'confidence' => 'pending', 'reviewed_at' => '2026-08-06',
		),
		'cross_sell_ids' => array( 'equipment-wasabi-grater', 'ingredient-kioke-shoyu' ),
		'up_sell_ids' => array( 'ingredient-fresh-wasabi' ),
		'prompt_en' => 'Commercial culinary studio photograph of one fresh Dutch-grown wasabi rhizome, natural 50 to 60 g scale, pale green fibrous cut surface and fine grated wasabi beside it, cool stone background, gentle greenhouse daylight, accurate moisture and texture, no packaging, no logos, no Japanese origin symbols, no text.',
		'compliance' => array( $c99_compliance( 'dutch-wasabi-origin-and-cold-chain', 'יש להציג מקור הולנדי וטווח משקל של 50 עד 60 גרם, לשמור בקירור ולפעול לפי תווית האצווה. אין להציג את הפריט כמוצר שגדל ביפן.', 'Display Dutch origin and the 50 to 60 g weight range, keep refrigerated and follow the lot label. Do not present the item as grown in Japan.', array( 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026', 'israel-food-import' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-kito-yuzu', 'type' => 'ingredient', 'slug' => 'kito-yuzu', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'יוזו קיטו', 'Kito yuzu' ),
		'summary' => $c99_text( 'יוזו קיטו הוא כינוי גאוגרפי מוגן למוצר מוסמך מאזור קיטו בטוקושימה. קליפה, מיץ ושמן קליפה הם צורות מוצר שונות; פרופיל הארומה כולל תרכובות כגון לימונן, gamma-terpinene, לינלול ו-Yuzunone, אך היחסים תלויים בפרי ובתהליך.', 'Kito yuzu is a protected geographical indication for certified product from Kito in Tokushima. Peel, juice and peel oil are different product forms; the aroma profile includes compounds such as limonene, gamma-terpinene, linalool and Yuzunone, but their proportions depend on fruit and process.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין יוזו קיטו, מקור וארומה', 'Understand Kito yuzu, origin and aroma' ),
		'primary_keyword' => $c99_text( 'יוזו קיטו', 'Kito yuzu' ), 'secondary_keywords' => array( 'he' => array( 'יוזו יפני', 'שמן קליפת יוזו' ), 'en' => array( 'Japanese yuzu', 'yuzu peel oil' ) ), 'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-yuzu-major-volatiles', 'scientific', 'במחקר GC-MS השוואתי על מיני הדר, לימונן, gamma-terpinene ולינלול זוהו כתרכובות עיקריות בדגימות היוזו שנבדקו.', 'In a comparative GC-MS study of citrus species, limonene, gamma-terpinene and linalool were identified as major compounds in the tested yuzu samples.', 'peer_reviewed_context', 'category', array( 'yuzu-volatiles-2017' ) ),
			$c99_fact( 'fact-yuzu-yuzunone-impact', 'scientific', 'מחקר נפרד זיהה את Yuzunone כתורמת חשובה לאופי הארומטי של יוזו; זהו הקשר מחקרי ולא מפרט אצווה.', 'A separate study identified Yuzunone as an important contributor to yuzu aroma character; this is research context, not a lot specification.', 'peer_reviewed_context', 'category', array( 'yuzu-aroma-2009' ) ),
			$c99_fact( 'fact-kito-yuzu-gi', 'institutional', 'Kito Yuzu רשום ביפן כאינדיקציה גאוגרפית מספר 42.', 'Kito Yuzu is registered in Japan as Geographical Indication number 42.', 'official_source', 'entity', array( 'kito-yuzu-gi' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'פרופיל הארומה מפוצל בין מחקר התרכובות העיקריות למחקר Yuzunone, ואינו מוצג כ-COA של פרי בודד.', 'The aroma profile separates the major-volatiles study from the Yuzunone study and is not presented as a COA for one fruit.', array( 'fact-yuzu-major-volatiles', 'fact-yuzu-yuzunone-impact' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'השימושים האזוריים והעונתיים ייכתבו ממקורות יפניים.', 'Regional and seasonal uses will be written from Japanese sources.' ),
			'institutional' => $c99_profile( 'source_backed', 'המקור הגאוגרפי מוגן ברישום GI.', 'Geographical origin is protected through GI registration.', array( 'fact-kito-yuzu-gi' ) ),
			'economic' => $c99_profile( 'pending_evidence', 'יש להשוות פרי, מיץ, מחית ושמן לפי אחוז פרי, משקל, יבול וקירור.', 'Fruit, juice, puree and oil must be compared by fruit percentage, weight, harvest and cold chain.' ),
			'structural' => $c99_profile( 'pending_evidence', 'SKU יופרד לפי צורת מוצר והסמכת GI.', 'SKUs will be separated by product form and GI certification.' ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'citrus', 'yuzu', 'kito-gi' ),
		'attributes' => array( 'pa_origin' => array( 'product-specific-kito-eligibility-required' ), 'pa_processing_method' => array( 'whole-fruit-or-derived-product' ), 'pa_flavor_profile' => array( 'aromatic-citrus' ) ),
		'tags' => array( 'yuzu', 'kito', 'gi', 'limonene', 'yuzunone' ),
		'relations' => array( $c99_relation( 'supported_by', 'market-toyosu', 'שוק סיטונאי יכול להיות נקודת מחקר לאספקת הדרים עונתיים, לא הוכחת מלאי.', 'A wholesale market can be a research point for seasonal citrus supply, not proof of stock.' ) ),
		'commerce_state' => 'active_offer',
		'woo_product_code' => 'product-kito-yuzu-juice-100ml',
		'public_offer_allowed' => true,
		'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-kito-yuzu-juice-100ml-20260806', 'listing-kito-yuzu-juice-720ml-20260806' ),
		'prompt_en' => 'Premium Kito-origin yuzu study, one whole fruit and one freshly cut half with thick aromatic rind and visible juice vesicles, suspended zest oils caught in side light, refined commercial food photography, accurate natural color, unbranded presentation.',
		'compliance' => array( $c99_compliance( 'gi-use', 'השם Kito Yuzu וסימון GI יוצגו רק למוצר בעל הסמכה ורישום תואמים; מוצר בסגנון קיטו אינו מוצר GI.', 'The Kito Yuzu name and GI mark are displayed only for a product with matching certification and registration; Kito-style is not GI product.', array( 'kito-yuzu-gi' ) ) ),
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-yakinori', 'type' => 'ingredient', 'slug' => 'premium-yakinori', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'יאקינורי פרימיום', 'Premium yakinori' ),
		'summary' => $c99_text( 'יאקינורי הוא נורי שעבר קלייה. איכות מסחרית דורשת זיהוי מין אצה, אזור וגידול, מועד קציר, דרגת גיליון, קלייה, לחות ואריזה; תוצאות מחקר על דגימות נורי אינן מפרט של כל חבילה.', 'Yakinori is roasted nori. Commercial quality requires seaweed species, growing area, harvest, sheet grade, roast, moisture and packaging; research findings from nori samples are not specifications for every pack.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין נורי קלוי ודרגות איכות', 'Understand roasted nori and quality grades' ),
		'primary_keyword' => $c99_text( 'יאקינורי פרימיום', 'premium yakinori' ), 'secondary_keywords' => array( 'he' => array( 'נורי לסושי', 'דרגות נורי' ), 'en' => array( 'sushi nori sheets', 'nori grades' ) ), 'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-nori-taste-context', 'scientific', 'מחקר על דגימות נורי מיובש זיהה חומצות אמינו חופשיות ונוקלאוטידים תורמי טעם; אין להכליל את הכמויות לכל מוצר.', 'Research on dried nori samples identified taste-active free amino acids and nucleotides; amounts must not be generalized to every product.', 'peer_reviewed_context', 'category', array( 'nori-taste-study' ) ),
			$c99_fact( 'fact-nori-quality-fields', 'structural', 'הישות דורשת מין, אזור, קציר, דרגת גיליון, קלייה, לחות ואריזה ברמת SKU.', 'The entity requires species, region, harvest, sheet grade, roast, moisture and packaging at SKU level.', 'editorial_inference', 'entity', array( 'nori-taste-study' ) ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'הרכב הטעם הוא הקשר מחקרי, לא טענה כמותית למוצר.', 'Taste composition is research context, not a quantitative product claim.', array( 'fact-nori-taste-context' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'אזורי גידול ומסורות עיבוד יתועדו לפי מקור.', 'Growing regions and processing traditions will be documented by source.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'קואופרטיבים ושווקים יקושרו לאחר אימות מקור.', 'Cooperatives and markets will link after origin verification.' ),
			'economic' => $c99_profile( 'pending_evidence', 'מחיר ינורמל לפי מספר גיליונות, משקל, דרגה וקציר.', 'Price will be normalized by sheet count, weight, grade and harvest.' ),
			'structural' => $c99_profile( 'source_backed', 'הישות מפרידה חומר גלם, דרגה, קלייה ו-SKU.', 'The entity separates ingredient, grade, roast and SKU.', array( 'fact-nori-quality-fields' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'seaweed', 'nori', 'yakinori' ),
		'attributes' => array( 'pa_origin' => array( 'japan' ), 'pa_processing_method' => array( 'roasted-sheet' ), 'pa_storage_type' => array( 'moisture-protected' ), 'pa_flavor_profile' => array( 'marine-umami' ) ),
		'tags' => array( 'nori', 'yakinori', 'seaweed', 'sushi' ),
		'relations' => array( $c99_relation( 'complements', 'dish-edomae-nigiri', 'נורי יכול להשתלב לפי סוג הסושי.', 'Nori can participate depending on sushi type.' ) ),
		'commerce_state' => 'supplier_onboarding',
		'prompt_en' => 'Premium roasted nori sheets in a controlled culinary studio, deep green-black sheen, delicate crisp edges and one clean break revealing fine sheet structure, low side light, moisture-free styling, accurate scale, no packaging.',
	)
);

$entities[] = $c99_entity(
	array(
		'id' => 'ingredient-hon-mirin', 'type' => 'ingredient', 'slug' => 'hon-mirin', 'parent_id' => 'cuisine-japanese-washoku',
		'name' => $c99_text( 'הון מירין', 'Hon mirin' ),
		'summary' => $c99_text( 'הון מירין הוא תיבול אלכוהולי מתוק המיוצר מאורז דביק, קוג׳י ואלכוהול באמצעות סכריפיקציה והבשלה. אין לתאר את התהליך כתסיסה אלכוהולית כללית. סוכר ו-ABV יגיעו מתווית המוצר או מתעודת אנליזה של האצווה.', 'Hon mirin is a sweet alcoholic seasoning made from glutinous rice, koji and alcohol through saccharification and maturation. The process should not be described as generic alcoholic fermentation. Sugar and ABV must come from the product label or the lot certificate of analysis.' ),
		'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין הון מירין, תהליך ושימוש', 'Understand hon mirin, process and use' ),
		'primary_keyword' => $c99_text( 'הון מירין יפני', 'Japanese hon mirin' ), 'secondary_keywords' => array( 'he' => array( 'מירין אמיתי', 'מירין עם קוג׳י' ), 'en' => array( 'real mirin', 'koji mirin' ) ), 'schema_type' => 'DefinedTerm',
		'facts' => array(
			$c99_fact( 'fact-hon-mirin-process', 'scientific', 'MAFF מתאר תהליך שבו אנזימי קוג׳י פועלים על אורז בנוכחות אלכוהול ומייצרים סוכרים וחומצות אמינו.', 'MAFF describes a process in which koji enzymes act on rice in the presence of alcohol, producing sugars and amino acids.', 'official_source', 'technique_context', array( 'maff-hon-mirin' ) ),
			$c99_fact( 'fact-hon-mirin-sku-values', 'structural', 'ABV, סוכר, נפח וסיווג אלכוהולי הם שדות SKU ומסמכים, לא תגיות כלליות.', 'ABV, sugar, volume and alcohol classification are SKU and documentation fields, not generic tags.', 'editorial_inference', 'entity', array( 'maff-hon-mirin', 'israel-alcohol-license' ), false ),
		),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'source_backed', 'סכריפיקציה ופירוק חלבון מתועדים כתהליך; ערכי מוצר דורשים תווית.', 'Saccharification and protein breakdown describe process; product values require a label.', array( 'fact-hon-mirin-process' ) ),
			'cultural' => $c99_profile( 'pending_evidence', 'שימושים מסורתיים ייקשרו למנה ושיטה.', 'Traditional uses will link to a dish and method.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'יצרן, סיווג ורישוי ייקשרו ל-SKU.', 'Producer, classification and licensing will bind to the SKU.' ),
			'economic' => $c99_profile( 'pending_evidence', 'השוואה תבדיל הון מירין מתיבול בסגנון מירין לפי ABV, סוכר ונפח.', 'Comparison will distinguish hon mirin from mirin-style seasoning by ABV, sugar and volume.' ),
			'structural' => $c99_profile( 'source_backed', 'הישות מחברת קוג׳י, אלכוהול, בישול ומסלול ציות.', 'The entity connects koji, alcohol, cooking and compliance.', array( 'fact-hon-mirin-sku-values' ) ),
		) ),
		'categories' => array( 'world-cuisines', 'japan', 'seasonings', 'mirin' ),
		'attributes' => array( 'pa_origin' => array( 'product-specific' ), 'pa_fermentation_method' => array( 'koji-saccharification-in-alcohol' ), 'pa_allergens' => array( 'product-label-required' ) ),
		'tags' => array( 'hon-mirin', 'koji', 'alcohol', 'glaze' ),
		'relations' => array( $c99_relation( 'requires', 'ingredient-kome-koji', 'קוג׳י הוא רכיב תהליכי מרכזי.', 'Koji is a central process ingredient.' ) ),
		'commerce_state' => 'supplier_onboarding',
		'pricing_state' => 'source_price_observed', 'observation_entity_ids' => array( 'listing-fukumitsuya-hon-mirin-3y-720ml-20260806', 'listing-fukumitsuya-hon-mirin-10y-720ml-20260806' ),
		'prompt_en' => 'Amber hon mirin in a clear unbranded glass vessel beside glutinous rice and rice koji, soft backlight revealing viscosity and warm color, precise Japanese culinary studio styling, no labels, no bottle branding.',
		'compliance' => array( $c99_compliance( 'alcohol-age-and-license', 'סיווג המוצר, ABV, אימות גיל 18+, רישוי ושעות מכירה ייבדקו לפי המוצר והדין בישראל לפני הפעלת מכירה.', 'Product classification, ABV, age 18 verification, licensing and permitted sale hours must be checked for the actual product under Israeli law before sale activation.', array( 'israel-alcohol-license' ) ) ),
	)
);

/* Molecular and process entities. */
$entities[] = $c99_entity( array(
	'id' => 'molecule-l-glutamate', 'type' => 'molecule', 'slug' => 'l-glutamate', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'L-גלוטמט', 'L-glutamate' ),
	'summary' => $c99_text( 'L-גלוטמט הוא אניון של חומצה גלוטמית המשתתף בתפיסת אומאמי. במערכת הוא מקושר למקורות מזון ולרצפטור טעם כהקשר מדעי, לא כהבטחת עוצמת טעם לכל מוצר.', 'L-glutamate is the anion of glutamic acid involved in umami perception. In the system it links food sources to taste-receptor context, not to a promised flavor intensity for every product.' ),
	'seo_group' => 'science', 'primary_intent' => $c99_text( 'להבין גלוטמט ואומאמי במזון', 'Understand glutamate and umami in food' ), 'primary_keyword' => $c99_text( 'גלוטמט אומאמי', 'glutamate umami' ),
	'secondary_keywords' => array( 'he' => array( 'קולטן אומאמי', 'חומצה גלוטמית במזון' ), 'en' => array( 'umami receptor', 'glutamic acid in food' ) ), 'schema_type' => 'ChemicalSubstance',
	'facts' => array( $c99_fact( 'fact-glutamate-receptor', 'scientific', 'גלוטמט נקשר לרצפטור T1R1/T1R3, ונוקלאוטידים יכולים לחזק את התגובה באופן אלוסטרי.', 'Glutamate binds the T1R1/T1R3 receptor, and nucleotides can enhance the response allosterically.', 'peer_reviewed_context', 'category', array( 'umami-receptor-2009' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'מנגנון הרצפטור מגובה במחקר מולקולרי.', 'The receptor mechanism is supported by molecular research.', array( 'fact-glutamate-receptor' ) ),
		'cultural' => $c99_profile( 'not_applicable', 'התרכובת עצמה אינה תרבות; ההקשר התרבותי נמצא במנות ובטכניקות.', 'The compound itself is not a culture; cultural context belongs to dishes and techniques.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'מקורות מחקר ותקנים ייקשרו בנפרד.', 'Research and standard authorities will be linked separately.' ),
		'economic' => $c99_profile( 'not_applicable', 'אין מחיר שימושי למולקולה בתוך מיפוי מנה; נמדדים חומרי הגלם.', 'A molecule price is not useful in a dish map; ingredients are measured instead.' ),
		'structural' => $c99_profile( 'pending_evidence', 'הישות מקשרת דאשי, שויו ורצפטור טעם.', 'The entity links dashi, shoyu and taste receptor context.' ),
	) ),
	'categories' => array( 'culinary-science', 'taste-molecules', 'umami' ), 'attributes' => array( 'pa_flavor_profile' => array( 'umami' ) ), 'tags' => array( 'glutamate', 'umami', 't1r1-t1r3' ),
	'relations' => array( $c99_relation( 'complements', 'molecule-inosine-monophosphate', 'IMP יכול להגביר תפיסת אומאמי בנוכחות גלוטמט.', 'IMP can enhance umami perception in the presence of glutamate.' ) ),
	'prompt_en' => 'Scientific editorial visualization of L-glutamate as a clean molecular model beside kombu and clear dashi, accurate atom color convention, dark neutral background, culinary science museum style, no medical symbolism, no labels embedded in the image.'
) );

$entities[] = $c99_entity( array(
	'id' => 'molecule-inosine-monophosphate', 'type' => 'molecule', 'slug' => 'inosine-monophosphate', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'אינוזין מונופוספט, IMP', 'Inosine monophosphate, IMP' ),
	'summary' => $c99_text( 'IMP הוא נוקלאוטיד טעם שיכול להגביר תפיסת אומאמי בנוכחות גלוטמט. המערכת שומרת את המנגנון כהקשר מחקרי ואינה מייחסת ריכוז קבוע לקצואובושי, דג או נורי ללא בדיקה.', 'IMP is a taste-active nucleotide that can enhance umami perception in the presence of glutamate. The system stores the mechanism as research context and does not assign a fixed concentration to katsuobushi, fish or nori without testing.' ),
	'seo_group' => 'science', 'primary_intent' => $c99_text( 'להבין IMP וסינרגיית אומאמי', 'Understand IMP and umami synergy' ), 'primary_keyword' => $c99_text( 'IMP אומאמי', 'IMP umami' ),
	'secondary_keywords' => array( 'he' => array( 'נוקלאוטיד טעם', 'סינרגיית גלוטמט' ), 'en' => array( 'taste nucleotide', 'glutamate synergy' ) ), 'schema_type' => 'ChemicalSubstance',
	'facts' => array( $c99_fact( 'fact-imp-allosteric-synergy', 'scientific', 'IMP תורם לסינרגיית אומאמי עם גלוטמט דרך מנגנון אלוסטרי ברצפטור T1R1/T1R3.', 'IMP contributes to glutamate umami synergy through an allosteric mechanism at the T1R1/T1R3 receptor.', 'peer_reviewed_context', 'category', array( 'umami-receptor-2009' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'הקשר הרצפטורי מתועד במחקר.', 'The receptor relationship is documented by research.', array( 'fact-imp-allosteric-synergy' ) ),
		'cultural' => $c99_profile( 'not_applicable', 'התרכובת מקבלת הקשר תרבותי דרך מנות.', 'The compound receives cultural context through dishes.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'מקורות מחקר מקושרים כרשומות סמכות.', 'Research sources are linked as authority records.' ),
		'economic' => $c99_profile( 'not_applicable', 'הערך הכלכלי נמדד בחומרי המקור.', 'Economic value is measured in source ingredients.' ),
		'structural' => $c99_profile( 'pending_evidence', 'הישות מקשרת קצואובושי, נורי, דג ודאשי.', 'The entity links katsuobushi, nori, fish and dashi.' ),
	) ),
	'categories' => array( 'culinary-science', 'taste-molecules', 'umami' ), 'attributes' => array( 'pa_flavor_profile' => array( 'umami-synergy' ) ), 'tags' => array( 'imp', 'nucleotide', 'umami' ),
	'relations' => array( $c99_relation( 'complements', 'molecule-l-glutamate', 'הסינרגיה נבחנת בנוכחות גלוטמט.', 'Synergy is considered in the presence of glutamate.' ) ),
	'prompt_en' => 'Scientific editorial visualization of inosine monophosphate as a precise molecular model beside katsuobushi shavings and clear dashi, accurate atom colors, museum-grade dark backdrop, no health claims, no embedded labels.'
) );

$entities[] = $c99_entity( array(
	'id' => 'molecule-allyl-isothiocyanate', 'type' => 'molecule', 'slug' => 'allyl-isothiocyanate', 'parent_id' => 'ingredient-fresh-wasabi',
	'name' => $c99_text( 'אליל איזותיוציאנט, AITC', 'Allyl isothiocyanate, AITC' ),
	'summary' => $c99_text( 'AITC הוא תרכובת נדיפה התורמת לחריפות האופיינית לאחר פגיעה ברקמת וואסבי. הריכוזים משתנים, ולכן ההסבר מתמקד במנגנון ואינו מציג מספר גנרי לכל מוצר.', 'AITC is a volatile compound contributing to characteristic pungency after wasabi tissue is disrupted. Concentrations vary, so the explanation focuses on the mechanism rather than assigning one generic number to every product.' ),
	'seo_group' => 'science', 'primary_intent' => $c99_text( 'להבין AITC וחריפות וואסבי', 'Understand AITC and wasabi pungency' ), 'primary_keyword' => $c99_text( 'AITC בוואסבי', 'AITC in wasabi' ),
	'secondary_keywords' => array( 'he' => array( 'איזותיוציאנטים', 'חריפות נדיפה' ), 'en' => array( 'isothiocyanates', 'volatile pungency' ) ), 'schema_type' => 'ChemicalSubstance',
	'facts' => array( $c99_fact( 'fact-aitc-wasabi-variation', 'scientific', 'AITC ותרכובות קשורות משתנים בין גנוטיפים, איברי צמח ותנאי עונה.', 'AITC and related compounds vary among genotypes, plant organs and seasonal conditions.', 'peer_reviewed_context', 'category', array( 'wasabi-itc-2023' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'השונות הביולוגית מונעת מספר גנרי למוצר.', 'Biological variation prevents a generic product number.', array( 'fact-aitc-wasabi-variation' ) ),
		'cultural' => $c99_profile( 'not_applicable', 'התרכובת מקבלת הקשר דרך שימוש בוואסבי.', 'The compound receives context through wasabi use.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'שיטות בדיקה ומעבדות ייקשרו בעת הצורך.', 'Test methods and laboratories will link when needed.' ),
		'economic' => $c99_profile( 'not_applicable', 'מחיר נמדד בקנה השורש או במוצר, לא במולקולה.', 'Price is measured in rhizome or product, not molecule.' ),
		'structural' => $c99_profile( 'pending_evidence', 'ההסבר מחבר בין וואסבי, גרירה ותפיסת החריפות.', 'The explanation connects wasabi, grating and perceived pungency.' ),
	) ),
	'categories' => array( 'culinary-science', 'aroma-and-pungency', 'isothiocyanates' ), 'attributes' => array( 'pa_flavor_profile' => array( 'volatile-pungency' ) ), 'tags' => array( 'aitc', 'wasabi', 'isothiocyanate' ),
	'relations' => array(
		$c99_relation( 'part_of', 'ingredient-fresh-wasabi', 'AITC מוסבר בהקשר של וואסבי טרי.', 'AITC is explained in the context of fresh wasabi.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
		$c99_relation( 'part_of', 'guide-wasabi-aitc', 'AITC מוסבר במדריך על חריפות וואסבי לצד חומר הגלם ותהליך הגרירה.', 'AITC is explained in the wasabi pungency guide alongside the ingredient and grating process.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
	),
	'prompt_en' => 'Culinary science visualization of allyl isothiocyanate emerging from freshly grated wasabi, precise molecular model, cool vapor-like motion, dark stone and pale green botanical texture, no medical iconography, no text.'
) );

$entities[] = $c99_entity( array(
	'id' => 'reaction-koji-enzymatic-hydrolysis', 'type' => 'reaction', 'slug' => 'koji-enzymatic-hydrolysis', 'parent_id' => 'ingredient-kome-koji',
	'name' => $c99_text( 'הידרוליזה אנזימטית בקוג׳י', 'Koji enzymatic hydrolysis' ),
	'summary' => $c99_text( 'עמילאזות ופרוטאזות שמקורן בקוג׳י מפרקות עמילנים וחלבונים לסוכרים, פפטידים וחומצות אמינו. הטמפרטורה, המים, המלח, המצע והזמן קובעים את מסלול התהליך בפועל.', 'Koji-derived amylases and proteases break starches and proteins into sugars, peptides and amino acids. Temperature, water, salt, substrate and time determine the actual process path.' ),
	'seo_group' => 'science', 'primary_intent' => $c99_text( 'להבין את תגובת ההידרוליזה האנזימטית בקוג׳י', 'Understand the koji enzymatic hydrolysis reaction' ), 'primary_keyword' => $c99_text( 'תגובת הידרוליזה אנזימטית בקוג׳י', 'koji enzymatic hydrolysis reaction' ),
	'secondary_keywords' => array( 'he' => array(), 'en' => array() ), 'schema_type' => 'DefinedTerm',
	'facts' => array( $c99_fact( 'fact-koji-hydrolysis-process', 'scientific', 'מקורות MAFF מתארים פירוק עמילן וחלבון כחלק מרכזי במזונות מותססים מבוססי קוג׳י.', 'MAFF sources describe starch and protein breakdown as a central part of koji-based fermented foods.', 'official_source', 'technique_context', array( 'maff-fermented-foods' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'התגובה נשמרת כמנגנון תהליכי עם תנאי מערכת.', 'The reaction is stored as a process mechanism with system conditions.', array( 'fact-koji-hydrolysis-process' ) ),
		'cultural' => $c99_profile( 'pending_evidence', 'ההקשר התרבותי מגיע מהמוצר המותסס.', 'Cultural context comes from the fermented product.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'שיטות מעבדה ותקנים ייקשרו בנפרד.', 'Laboratory methods and standards will link separately.' ),
		'economic' => $c99_profile( 'pending_evidence', 'תפוקה, זמן ואנרגיה ישויכו לתהליך מקצועי פרטי.', 'Yield, time and energy belong to a private professional process.' ),
		'structural' => $c99_profile( 'pending_evidence', 'התגובה מקשרת קוג׳י לשויו, מיסו ומירין.', 'The reaction links koji to shoyu, miso and mirin.' ),
	) ),
	'categories' => array( 'culinary-science', 'reactions', 'enzymatic-hydrolysis' ), 'attributes' => array( 'pa_processing_method' => array( 'enzymatic-hydrolysis' ) ), 'tags' => array( 'amylase', 'protease', 'koji', 'hydrolysis' ),
	'relations' => array( $c99_relation( 'used_in', 'ingredient-kioke-shoyu', 'המנגנון הוא חלק מתהליך השויו.', 'The mechanism participates in shoyu processing.' ) ),
	'prompt_en' => 'Split-screen culinary science diagram showing intact steamed rice and soy proteins transforming into sugars, peptides and amino acids under koji enzyme action, precise microstructure, warm fermentation palette, no equations or embedded text.'
) );

/* Equipment and quality entities. */
$entities[] = $c99_entity( array(
	'id' => 'equipment-hangiri', 'type' => 'equipment', 'slug' => 'hangiri', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'האנגירי', 'Hangiri' ),
	'summary' => $c99_text( 'האנגירי הוא כלי עץ רחב ונמוך המשמש לערבוב שארי ולניהול פיזור חום ולחות. סוג העץ, קוטר, גימור, חישוק, תחזוקה וסניטציה הם שדות איכות נפרדים.', 'A hangiri is a wide, shallow wooden vessel used to mix shari and manage heat and moisture release. Wood species, diameter, finish, hoop, care and sanitation are separate quality fields.' ),
	'seo_group' => 'equipment', 'primary_intent' => $c99_text( 'להבין ולבחור האנגירי לסושי', 'Understand and choose a sushi hangiri' ), 'primary_keyword' => $c99_text( 'האנגירי לסושי', 'sushi hangiri' ),
	'secondary_keywords' => array( 'he' => array( 'קערת עץ לאורז סושי', 'תחזוקת האנגירי' ), 'en' => array( 'wooden sushi rice tub', 'hangiri care' ) ), 'schema_type' => 'DefinedTerm',
	'facts' => array( $c99_fact( 'fact-hangiri-process-role', 'structural', 'האנגירי מקושר לשארי ככלי תהליך, לא כתנאי יחיד לאיכות אורז.', 'The hangiri links to shari as process equipment, not as the sole condition for rice quality.', 'editorial_inference', 'entity', array( 'maff-edomae' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'יש למדוד השפעת חומר, שטח פנים ולחות בנפרד.', 'Material, surface area and moisture effects require separate measurement.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'מסורות ייצור עץ יתועדו לפי יצרן ואזור.', 'Woodcraft traditions will be documented by producer and region.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'יצרנים וחנויות ציוד ייקשרו עם מפרט.', 'Producers and equipment shops will link with specifications.' ),
		'economic' => $c99_profile( 'pending_evidence', 'מחיר יושווה לפי קוטר, עץ, בנייה, אחריות ומשלוח.', 'Price will be compared by diameter, wood, construction, warranty and shipping.' ),
		'structural' => $c99_profile( 'source_backed', 'כלי מקושר להכנת שארי ולערכות סושי.', 'Equipment linked to shari preparation and sushi kits.', array( 'fact-hangiri-process-role' ) ),
	) ),
	'categories' => array( 'professional-equipment', 'japanese-woodware', 'sushi-tools', 'hangiri' ), 'attributes' => array( 'pa_origin' => array( 'product-specific' ), 'pa_material' => array( 'wood-product-specific' ), 'pa_equipment_required' => array( 'sushi-rice-preparation' ) ), 'tags' => array( 'hangiri', 'shari', 'woodware' ),
	'relations' => array( $c99_relation( 'used_in', 'preparation-sushi-shari', 'הכלי משמש בתהליך ערבוב השארי.', 'The vessel is used while mixing shari.' ) ),
	'commerce_state' => 'supplier_onboarding',
	'pricing_state' => 'source_price_observed', 'observation_entity_ids' => array( 'listing-umezawa-hangiri-36cm-20260806' ),
	'prompt_en' => 'Commercial studio product photograph of a handcrafted shallow Japanese hangiri with clean wood grain, fitted hoops and a wooden shamoji, three-quarter top view, soft side light revealing joinery and surface texture, neutral background, exact scale reference without text.',
	'compliance' => array( $c99_compliance( 'wood-contact-care', 'נדרשות הוראות ניקוי, ייבוש ואחסון המתאימות לעץ ולגימור של המוצר בפועל.', 'Cleaning, drying and storage instructions must match the actual wood species and finish.' ) ),
) );

$entities[] = $c99_entity( array(
	'id' => 'equipment-yanagiba', 'type' => 'equipment', 'slug' => 'yanagiba', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'סכין יאנאגיבה', 'Yanagiba knife' ),
	'summary' => $c99_text( 'יאנאגיבה היא משפחת סכיני פריסה יפניים בעלות להב ארוך, בדרך כלל חד-צדדי. פלדה, חיסום, קשיות, אורך, גאומטריה, יד דומיננטית, ידית, ליטוש ושירות השחזה מסבירים את פערי האיכות והמחיר.', 'Yanagiba is a family of long Japanese slicing knives, commonly single bevel. Steel, heat treatment, hardness, length, geometry, handedness, handle, finish and sharpening service explain quality and price differences.' ),
	'seo_group' => 'equipment', 'primary_intent' => $c99_text( 'להבין ולבחור סכין יאנאגיבה', 'Understand and choose a yanagiba knife' ), 'primary_keyword' => $c99_text( 'סכין יאנאגיבה', 'yanagiba knife' ),
	'secondary_keywords' => array( 'he' => array( 'סכין סשימי יפנית', 'פלדת יאנאגיבה' ), 'en' => array( 'Japanese sashimi knife', 'yanagiba steel grades' ) ), 'schema_type' => 'DefinedTerm',
	'facts' => array(
		$c99_fact( 'fact-yanagiba-quality-model', 'structural', 'הערכת איכות דורשת שדות נפרדים לפלדה, חיסום, גאומטריה, אורך, יד, ידית וליטוש.', 'Quality assessment requires separate fields for steel, heat treatment, geometry, length, handedness, handle and finish.', 'editorial_inference', 'entity', array( 'tsubaya-yanagiba-2026' ) ),
	),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'מטאלורגיה, קשיות ושימור חוד ידרשו מפרט ובדיקת מוצר.', 'Metallurgy, hardness and edge retention require product specification and testing.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'מסורות חישול והשחזה ייכתבו לפי יצרן ואזור.', 'Forging and sharpening traditions will be written by producer and region.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'חנויות ויצרנים מקושרים כישויות עם תאריך בדיקה.', 'Shops and makers are linked as entities with review dates.' ),
		'economic' => $c99_profile( 'pending_evidence', 'תצפיות מחיר נשמרות בישות שוק מתוארכת וב-SKU מדויק, לא במשפחת הכלים.', 'Price observations are stored on a dated market entity and exact SKU, not on the equipment family.' ),
		'structural' => $c99_profile( 'source_backed', 'הישות מחברת כלי, מפרטי חומר, חנות ותצפית מחיר בלי להציגם כמדרג איכות יחיד.', 'The entity connects equipment, material specifications, shop and price observation without presenting them as one quality ladder.', array( 'fact-yanagiba-quality-model' ) ),
	) ),
	'categories' => array( 'professional-equipment', 'japanese-knives', 'sushi-knives', 'yanagiba' ),
	'attributes' => array( 'pa_origin' => array( 'product-specific' ), 'pa_material' => array( 'steel-product-specific' ), 'pa_handedness' => array( 'right-or-left-product-specific' ), 'pa_quality_grade' => array( 'specification-required' ) ),
	'tags' => array( 'yanagiba', 'sashimi-knife', 'single-bevel', 'knife-geometry' ),
	'relations' => array(
		$c99_relation( 'sold_by', 'equipment-shop-tsubaya', 'Tsubaya משמשת מקור מחיר ציבורי לדוגמה, לא ספק מאושר של Complete99.', 'Tsubaya is an example public price source, not an approved Complete99 supplier.' ),
		$c99_relation( 'specified_by', 'material-yanagiba-white2', 'White 2 הוא מפרט חומר אפשרי, לא ציון איכות.', 'White 2 is a possible material specification, not a quality score.', true, array( 'tsubaya-white2-collection-2026' ), 'official_source' ),
		$c99_relation( 'specified_by', 'material-yanagiba-blue1-suminagashi', 'Blue 1 ו-suminagashi מתארים פלדה ובנייה בדגם מסוים.', 'Blue 1 and suminagashi describe steel and construction on a particular model.', true, array( 'tsubaya-blue1-suminagashi-2026' ), 'official_source' ),
		$c99_relation( 'observed_at', 'market-observation-tsubaya-yanagiba-2026-08-06', 'תצפית המחיר נשמרת כישות מתוארכת.', 'The price observation is stored as a dated entity.' ),
	),
	'commerce_state' => 'supplier_onboarding',
	'pricing_state' => 'source_price_observed',
	'observation_entity_ids' => array( 'market-observation-tsubaya-yanagiba-2026-08-06' ),
	'prompt_en' => 'Commercial studio product photograph of a traditional long single-bevel yanagiba knife, blade face and ura geometry visible in separate angles, restrained dark wood and neutral paper background, precise edge reflections, no maker mark, no logo, no unsafe hand pose.',
	'compliance' => array( $c99_compliance( 'sharp-knife', 'סכין מקצועית חדה דורשת אריזה מגינה, הוראות טיפול, אחסון בטוח ואזהרה מפני שימוש בידי ילדים ללא השגחה.', 'A sharp professional knife requires protective packaging, care instructions, safe storage and a warning against unsupervised child use.' ) ),
) );

$entities[] = $c99_entity( array(
	'id' => 'material-yanagiba-white2', 'type' => 'material_specification', 'slug' => 'yanagiba-white-2', 'parent_id' => 'equipment-yanagiba',
	'name' => $c99_text( 'יאנאגיבה מפלדת White 2', 'White 2 steel yanagiba' ),
	'summary' => $c99_text( 'White 2 הוא מפרט פלדה אפשרי, לא ציון איכות מלא. חיסום, קשיות, גאומטריה, ליטוש, אורך, ידית ויצרן יכולים לשנות ביצועים ומחיר גם כאשר שם הפלדה זהה.', 'White 2 is a possible steel specification, not a complete quality score. Heat treatment, hardness, geometry, finish, length, handle and maker can change performance and price even when the steel name is identical.' ),
	'seo_group' => 'quality', 'primary_intent' => $c99_text( 'להשוות יאנאגיבה White 2', 'Compare White 2 yanagiba knives' ), 'primary_keyword' => $c99_text( 'יאנאגיבה White 2', 'White 2 yanagiba' ),
	'secondary_keywords' => array( 'he' => array( 'פלדה לבנה 2', 'סכין סשימי פחמנית' ), 'en' => array( 'Shirogami 2 yanagiba', 'carbon steel sashimi knife' ) ), 'schema_type' => 'DefinedTerm',
	'facts' => array( $c99_fact( 'fact-white2-material-context', 'structural', 'White 2 מופיע כמפרט פלדה ברשומות מסחריות, אך שם הפלדה לבדו אינו מתאר חיסום, גאומטריה, אורך, ידית, יצרן או ביצועים.', 'White 2 appears as a steel specification in commercial listings, but the steel name alone does not describe heat treatment, geometry, length, handle, maker or performance.', 'official_source', 'category', array( 'tsubaya-white2-collection-2026' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'הרכב פלדה וחיסום דורשים מפרט יצרן.', 'Steel composition and heat treatment require maker specification.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'מסורת הייצור תיוחס ליצרן, לא לשם הפלדה בלבד.', 'Craft tradition is attributed to the maker, not the steel name alone.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'יצרנים וחנויות ייבדקו בנפרד.', 'Makers and shops will be reviewed separately.' ),
		'economic' => $c99_profile( 'pending_evidence', 'מחיר שייך לדגם, אורך ו-SKU ולא למונח White 2 לבדו.', 'Price belongs to a model, length and SKU, not to the White 2 term alone.' ),
		'structural' => $c99_profile( 'source_backed', 'המפרט מקושר ליאנאגיבה אך אינו מחליף מפרט SKU.', 'The material specification links to yanagiba but does not replace a SKU specification.', array( 'fact-white2-material-context' ) ),
	) ),
	'categories' => array( 'professional-equipment', 'japanese-knives', 'material-specifications', 'white-2' ), 'attributes' => array( 'pa_steel' => array( 'white-2' ) ), 'tags' => array( 'white-2', 'shirogami-2', 'yanagiba' ),
	'relations' => array( $c99_relation( 'part_of', 'equipment-yanagiba', 'מפרט החומר הוא אפשרות בתוך משפחת יאנאגיבה.', 'The material specification is an option within the yanagiba family.', true, array( 'tsubaya-white2-collection-2026' ), 'official_source' ) ),
	'commerce_state' => 'reference_only',
	'prompt_en' => 'Technical studio comparison plate of an unbranded White 2 carbon steel yanagiba, blade face, spine, heel and single-bevel geometry shown with neutral scale blocks, precise reflections, archival catalog lighting, no logos or text.'
) );

$entities[] = $c99_entity( array(
	'id' => 'material-yanagiba-blue1-suminagashi', 'type' => 'material_specification', 'slug' => 'yanagiba-blue-1-suminagashi', 'parent_id' => 'equipment-yanagiba',
	'name' => $c99_text( 'יאנאגיבה Blue 1 Suminagashi', 'Blue 1 suminagashi yanagiba' ),
	'summary' => $c99_text( 'Blue 1 עם מבנה suminagashi מתאר פלדה ומראה שכבות, אך אינו מבטיח לבדו חיסום, גאומטריה או ביצועים. בדגם עילית יש לבחון גם אורך, ידית, ליטוש, יצרן ושירות.', 'Blue 1 with suminagashi construction describes steel and layered appearance but does not by itself guarantee heat treatment, geometry or performance. A high-end model also requires review of length, handle, finish, maker and service.' ),
	'seo_group' => 'quality', 'primary_intent' => $c99_text( 'להשוות יאנאגיבה Blue 1 Suminagashi', 'Compare Blue 1 suminagashi yanagiba knives' ), 'primary_keyword' => $c99_text( 'יאנאגיבה Blue 1 Suminagashi', 'Blue 1 suminagashi yanagiba' ),
	'secondary_keywords' => array( 'he' => array( 'פלדת Aogami 1', 'סכין יאנאגיבה עילית' ), 'en' => array( 'Aogami 1 yanagiba', 'high end yanagiba' ) ), 'schema_type' => 'DefinedTerm',
	'facts' => array( $c99_fact( 'fact-blue1-suminagashi-material-context', 'structural', 'ברשומה מדויקת של Tsubaya, Blue 1 מתאר את הפלדה ו-suminagashi את מבנה השכבות; אלה מפרטי חומר ובנייה של דגם, לא דירוג איכות אוניברסלי.', 'In an exact Tsubaya listing, Blue 1 identifies the steel and suminagashi the layered construction; these are model material and construction specifications, not a universal quality grade.', 'official_source', 'supplier_specification', array( 'tsubaya-blue1-suminagashi-2026' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'פלדה, שכבות וחיסום דורשים מפרט יצרן.', 'Steel, layers and heat treatment require maker specification.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'מלאכת השכבות תיוחס ליצרן ומסורת מתועדים.', 'Layered craft will be attributed to a documented maker and tradition.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'היצרן והחנות אינם אותו תפקיד בגרף.', 'Maker and retailer are distinct graph roles.' ),
		'economic' => $c99_profile( 'pending_evidence', 'המחיר שייך לרשומת המוצר המדויקת ולא לצירוף המונחים Blue 1 ו-suminagashi.', 'The price belongs to the exact product listing, not to the Blue 1 and suminagashi terms.' ),
		'structural' => $c99_profile( 'source_backed', 'פלדה ומבנה שכבות נשמרים כשדות נפרדים ב-SKU.', 'Steel and layered construction remain separate SKU fields.', array( 'fact-blue1-suminagashi-material-context' ) ),
	) ),
	'categories' => array( 'professional-equipment', 'japanese-knives', 'material-specifications', 'blue-1-suminagashi' ), 'attributes' => array( 'pa_steel' => array( 'blue-1' ), 'pa_material' => array( 'suminagashi-layered-construction' ) ), 'tags' => array( 'blue-1', 'aogami-1', 'suminagashi', 'yanagiba' ),
	'relations' => array( $c99_relation( 'part_of', 'equipment-yanagiba', 'מפרט החומר והבנייה הוא מסלול בתוך משפחת יאנאגיבה.', 'The material and construction specification is a path within the yanagiba family.', true, array( 'tsubaya-blue1-suminagashi-2026' ), 'official_source' ) ),
	'commerce_state' => 'reference_only',
	'prompt_en' => 'High-detail technical studio photograph of an unbranded Blue 1 suminagashi yanagiba, layered blade pattern visible without exaggeration, burl-style handle shown separately, controlled raking light, neutral archival background, no logos or text.'
) );

$entities[] = $c99_entity( array(
	'id' => 'ingredient-koshihikari-rice', 'type' => 'ingredient', 'slug' => 'koshihikari-rice', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'אורז קושיהיקארי', 'Koshihikari rice' ),
	'summary' => $c99_text( 'קושיהיקארי הוא זן אורז יפוניקה בעל חשיבות מסחרית ומחקרית. שם הזן אינו מוכיח אזור, שנת קציר, שיטת גידול, טחינה, גיל, עמילוז או איכות בישול; כל נתון כזה חייב להיות קשור למוצר ולאצווה המסוימים.', 'Koshihikari is a japonica rice cultivar of commercial and research importance. The cultivar name does not prove region, harvest year, cultivation method, milling, age, amylose or cooking quality; each value must attach to the specific product and lot.' ),
	'seo_group' => 'ingredients', 'primary_intent' => $c99_text( 'להבין ולבחור אורז קושיהיקארי', 'Understand and choose Koshihikari rice' ), 'primary_keyword' => $c99_text( 'אורז קושיהיקארי', 'Koshihikari rice' ),
	'secondary_keywords' => array( 'he' => array( 'קושיהיקארי לסושי', 'אורז יפוניקה' ), 'en' => array( 'Koshihikari sushi rice', 'japonica rice cultivar' ) ), 'schema_type' => 'DefinedTerm',
	'facts' => array(
		$c99_fact( 'fact-koshihikari-research-context', 'scientific', 'מחקר גנומי על איכות אכילה ובישול באורז כולל את קושיהיקארי כהקשר זני, אך אינו מספק מפרט אוטומטי לכל שקית מסחרית.', 'Genomic research on rice eating and cooking quality includes Koshihikari as cultivar context but does not provide an automatic specification for every commercial bag.', 'peer_reviewed_context', 'category', array( 'koshihikari-genome-2018' ) ),
		$c99_fact( 'fact-koshihikari-lot-boundary', 'structural', 'מקור, קציר, טחינה, לחות, אחסון ו-COA נשמרים ברמת SKU ואצווה.', 'Origin, harvest, milling, moisture, storage and COA are stored at SKU and lot level.', 'editorial_inference', 'entity', array( 'koshihikari-genome-2018' ), false ),
		$c99_fact( 'fact-koshihikari-uozu-2kg-market-20260806', 'economic', 'ב-6.8.2026 נצפתה בדף Dutch Wasabi אריזת 2 ק״ג קושיהיקארי מאוזו שבטויאמה במחיר 16.95 אירו כולל מע״מ, כאשר המוצר סומן במלאי.', 'On August 6 2026, the Dutch Wasabi page showed a 2 kg pack of Koshihikari from Uozu in Toyama at EUR 16.95 including VAT, marked in stock.', 'official_source', 'market_snapshot', array( 'dutch-wasabi-koshihikari-2kg-listing-2026' ) ),
	),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'איכות אורז היא שילוב של זן, סביבה ותהליך, לא תכונה קבועה של שם מסחרי.', 'Rice quality combines cultivar, environment and process rather than being fixed by a commercial name.', array( 'fact-koshihikari-research-context' ) ),
		'cultural' => $c99_profile( 'pending_evidence', 'אזורי גידול והיסטוריה ייכתבו ממקורות ייעודיים.', 'Growing regions and history will be written from dedicated sources.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'מגדלים, קואופרטיבים והסמכות יישמרו כישויות נפרדות.', 'Growers, cooperatives and certifications will be separate entities.' ),
		'economic' => $c99_profile( 'source_backed', 'תצפית המחיר קשורה לאריזת 2 ק״ג מאוזו שבטויאמה ואינה הופכת למחיר כללי לכל אורז קושיהיקארי.', 'The price observation belongs to the 2 kg Uozu, Toyama pack and is not generalized to all Koshihikari rice.', array( 'fact-koshihikari-uozu-2kg-market-20260806' ) ),
		'structural' => $c99_profile( 'source_backed', 'ההסבר מפריד בין זן, מוצר ואצווה.', 'The guidance separates cultivar, product and lot.', array( 'fact-koshihikari-lot-boundary' ) ),
	) ),
	'categories' => array( 'world-cuisines', 'japan', 'rice', 'koshihikari' ), 'attributes' => array( 'pa_cultivar' => array( 'koshihikari' ), 'pa_origin' => array( 'product-specific' ), 'pa_processing_method' => array( 'milling-product-specific' ) ), 'tags' => array( 'koshihikari', 'japonica', 'sushi-rice', 'harvest-year' ),
	'relations' => array(
		$c99_relation( 'used_in', 'preparation-sushi-shari', 'הזן הוא אפשרות חומר גלם לשארי, בכפוף למוצר ולמתכון בפועל.', 'The cultivar is a possible shari ingredient subject to the actual product and recipe.', true, array( 'koshihikari-genome-2018' ), 'peer_reviewed_context' ),
		$c99_relation( 'complements', 'ingredient-kioke-shoyu', 'אורז ושויו הם רכיבים משלימים במסלולי סושי ומזווה יפני, כאשר כל מוצר נשמר בנפרד.', 'Rice and shoyu are complementary in sushi and Japanese pantry paths, with each product retained separately.', true, array( 'maff-edomae', 'jas-shoyu-1703' ), 'official_source' ),
	),
	'commerce_state' => 'active_offer', 'woo_product_code' => 'product-koshihikari-uozu-2kg', 'public_offer_allowed' => true,
	'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-koshihikari-uozu-2kg-20260806' ),
	'value_proposition' => $c99_text( 'אורז קושיהיקארי מאוזו באריזת 2 ק״ג עם מקור מתועד, קישור ישיר לשארי, האנגירי, שויו, וואסבי וקוג׳י, ומחיר מקור שמופרד ממחיר Complete99.', 'Uozu Koshihikari in a 2 kg pack with documented origin, direct links to shari, hangiri, shoyu, wasabi and koji, and a source-market price kept separate from the Complete99 price.' ),
	'margin_scenario' => array(
		'currency' => 'ILS', 'landed_cost_low' => null, 'landed_cost_high' => null, 'retail_price_low' => 149, 'retail_price_high' => 149, 'gross_margin_low' => null, 'gross_margin_high' => null,
		'basis' => $c99_text( 'מחיר המכירה המאושר הוא 149 ש״ח. מחיר המקור שנצפה הוא 16.95 אירו, כ-58.95 ש״ח לפי שער בנק ישראל מ-6.8.2026. זה אינו מחיר ספק או עלות נוחתת, ולכן רווחיות ושיעור רווח נשארים לא מחושבים עד לקבלת חשבונית, שילוח, מסים, אחסון, פחת וטיפול.', 'The approved sell price is ILS 149. The observed source-market price is EUR 16.95, about ILS 58.95 at the Bank of Israel rate on August 6 2026. This is not a supplier quote or landed cost, so profitability and gross margin remain uncalculated until invoice, freight, tax, storage, shrinkage and handling are known.' ),
		'confidence' => 'pending', 'reviewed_at' => '2026-08-06',
	),
	'cross_sell_ids' => array( 'equipment-hangiri', 'ingredient-kioke-shoyu', 'ingredient-fresh-dutch-wasabi', 'ingredient-kome-koji', 'ingredient-koji-starter-culture' ),
	'prompt_en' => 'Commercial culinary studio photograph of premium unbranded Koshihikari rice, translucent short grains in a shallow cedar measure beside precisely cooked glossy rice, soft daylight, macro grain detail, neutral Japanese pantry setting, no packaging or geographic claims.',
) );

$entities[] = $c99_entity( array(
	'id' => 'equipment-wasabi-grater', 'type' => 'equipment', 'slug' => 'wasabi-grater', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'מגררת וואסבי', 'Wasabi grater' ),
	'summary' => $c99_text( 'מגררת וואסבי היא כלי ליצירת משחה דקה מקנה שורש טרי. חומר המשטח, צפיפות השיניים, גודל, קשיחות, תחזוקה ומברשת איסוף הם שדות מפרט נפרדים. כלי פלדת Hagane-zame אינו עור כריש ויש לתארו לפי חומר היצרן בפועל.', 'A wasabi grater produces a fine paste from a fresh rhizome. Surface material, tooth density, size, rigidity, care and collection brush are separate specifications. A steel Hagane-zame tool is not sharkskin and must be described by its actual maker material.' ),
	'seo_group' => 'equipment', 'primary_intent' => $c99_text( 'לבחור מגררת וואסבי טרי', 'Choose a fresh wasabi grater' ), 'primary_keyword' => $c99_text( 'מגררת וואסבי', 'wasabi grater' ),
	'secondary_keywords' => array( 'he' => array( 'אורושי לוואסבי', 'Hagane-zame' ), 'en' => array( 'oroshi wasabi tool', 'Hagane-zame grater' ) ), 'schema_type' => 'DefinedTerm',
	'facts' => array(
		$c99_fact( 'fact-wasabi-grater-material-boundary', 'structural', 'דף המפרט הרשמי של Yamamoto מזהה את Hagane-zame כמגררת וואסבי מפלדת אל-חלד ומפריד בין מפרטי הדגמים. חומר ומידה שייכים לדגם המדויק.', 'Yamamoto Foods identifies Hagane-zame as a stainless-steel wasabi grater and separates specifications by model. Material and dimensions belong to the exact model.', 'official_source', 'supplier_specification', array( 'yamamoto-haganezame-spec' ) ),
	),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'השפעת משטח וזמן גרירה על מרקם ונדיפי חריפות דורשת מבחן מוצר.', 'The effect of surface and grating time on texture and pungent volatiles requires product testing.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'מסורת כלי הגרירה תיוחס ליצרן וחומר מתועדים.', 'Grating-tool tradition will be attributed to a documented maker and material.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'יצרן, חנות ושף מדגים יישמרו כישויות נפרדות.', 'Maker, shop and demonstrating chef remain separate entities.' ),
		'economic' => $c99_profile( 'pending_evidence', 'השוואת מחיר תפריד חומר, גודל, מלאי, מס ומשלוח.', 'Price comparison separates material, size, stock, tax and shipping.' ),
		'structural' => $c99_profile( 'source_backed', 'הכלי מקושר לווריאנט מדויק ולא לתווית חומר שגויה.', 'The tool links to an exact variant rather than an incorrect material label.', array( 'fact-wasabi-grater-material-boundary' ) ),
	) ),
	'categories' => array( 'professional-equipment', 'japanese-tools', 'wasabi-tools', 'graters' ), 'attributes' => array( 'pa_material' => array( 'product-specific' ), 'pa_equipment_required' => array( 'fresh-wasabi-preparation' ) ), 'tags' => array( 'wasabi-grater', 'oroshi', 'hagane-zame', 'fresh-wasabi' ),
	'relations' => array(
		$c99_relation( 'used_in', 'ingredient-fresh-wasabi', 'הכלי משלים הכנה של קנה שורש טרי.', 'The tool supports preparation of a fresh rhizome.', true, array( 'yamamoto-haganezame-spec', 'wasabi-itc-2023' ), 'official_source' ),
		$c99_relation( 'references', 'guide-wasabi-aitc', 'מדריך החריפות מספק את ההקשר המדעי להכנת וואסבי טרי.', 'The pungency guide provides the scientific context for preparing fresh wasabi.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
	),
	'commerce_state' => 'active_offer', 'pricing_state' => 'approved_sell_price', 'observation_entity_ids' => array( 'listing-hagane-zame-large-20260806' ),
	'woo_product_code' => 'product-hagane-zame-large',
	'public_offer_allowed' => true,
	'cross_sell_ids' => array( 'ingredient-fresh-wasabi' ),
	'prompt_en' => 'Commercial studio product photograph of an unbranded stainless steel wasabi grater with fine textured surface, fresh wasabi rhizome and bamboo brush arranged safely beside it, macro raking light, accurate metal texture, neutral background, no sharkskin implication, no text.',
) );

$entities[] = $c99_entity( array(
	'id' => 'standard-jas-shoyu-1703', 'type' => 'standard', 'slug' => 'jas-1703-shoyu-standard', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'תקן JAS 1703 לשויו', 'JAS 1703 shoyu standard' ),
	'summary' => $c99_text( 'JAS 1703 הוא מקור תקני לסיווג רוטב סויה. ישות התקן נפרדת מן היצרן ומן ה-SKU, כדי שהמערכת תוכל לשמור גרסה, סעיף, טענה ותעודת התאמה בלי להציג קטגוריה כמוצר מוסמך.', 'JAS 1703 is a standards source for soy sauce classification. The standard entity remains separate from producer and SKU so version, clause, claim and conformity evidence can be stored without presenting a category as a certified product.' ),
	'seo_group' => 'standards', 'primary_intent' => $c99_text( 'להבין את תקן JAS לשויו', 'Understand the JAS standard for shoyu' ), 'primary_keyword' => $c99_text( 'תקן JAS לשויו', 'JAS shoyu standard' ),
	'secondary_keywords' => array( 'he' => array( 'JAS 1703', 'סיווג רוטב סויה' ), 'en' => array( 'JAS 1703', 'soy sauce classification standard' ) ), 'schema_type' => 'Legislation',
	'facts' => array( $c99_fact( 'fact-jas-shoyu-standard-identity', 'institutional', 'המסמך הרשמי JAS 1703 מספק מסגרת לסיווג שויו; התאמת מוצר דורשת מסמך או סימון של ה-SKU בפועל.', 'The official JAS 1703 document provides a shoyu classification framework; product conformity requires evidence or labeling for the actual SKU.', 'regulatory_standard', 'entity', array( 'jas-shoyu-1703' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'דרישות אנליטיות יפורקו לסעיפים רק לאחר קריאת גרסה מלאה ובקרת מומחה.', 'Analytical requirements will be decomposed by clause only after full-version review and expert control.' ),
		'cultural' => $c99_profile( 'not_applicable', 'זהו מסמך תקינה ולא מקור היסטורי.', 'This is a standards document, not a historical source.' ),
		'institutional' => $c99_profile( 'source_backed', 'התקן נשמר כישות סמכות נפרדת.', 'The standard remains a separate authority entity.', array( 'fact-jas-shoyu-standard-identity' ) ),
		'economic' => $c99_profile( 'pending_evidence', 'השפעת התאמה לתקן על תמחור תיבחן ברמת SKU.', 'The pricing effect of conformity will be assessed at SKU level.' ),
		'structural' => $c99_profile( 'pending_evidence', 'גרסה, סעיף ותעודת התאמה יקושרו בנפרד.', 'Version, clause and conformity evidence will be linked separately.' ),
	) ),
	'categories' => array( 'culinary-science', 'standards', 'japan', 'shoyu' ), 'attributes' => array(), 'tags' => array( 'jas-1703', 'shoyu-standard', 'classification' ),
	'relations' => array( $c99_relation( 'supported_by', 'ingredient-kioke-shoyu', 'התקן מספק הקשר סיווג לישות השויו, לא אישור מוצר.', 'The standard provides classification context for the shoyu entity, not product approval.', true, array( 'jas-shoyu-1703' ), 'regulatory_standard' ) ),
	'revenue_models' => array( 'content_to_commerce', 'education' ), 'customer_segments' => array( 'professional_chefs', 'foodservice_buyers', 'research_readers' ),
	'prompt_en' => 'Editorial museum graphic representing a Japanese food standard as an abstract unbranded specification document beside soybeans, wheat and a dark shoyu sample, clean archival lighting, blank document areas, no copied seals, logos or readable text.',
) );

$entities[] = $c99_entity( array(
	'id' => 'geographical-indication-kito-yuzu', 'type' => 'geographical_indication', 'slug' => 'kito-yuzu-geographical-indication', 'parent_id' => 'ingredient-kito-yuzu',
	'name' => $c99_text( 'ציון גאוגרפי קיטו יוזו', 'Kito Yuzu Geographical Indication' ),
	'summary' => $c99_text( 'קיטו יוזו רשום במערכת הציונים הגאוגרפיים של יפן. ישות ה-GI נפרדת מן הפרי, המיץ וה-SKU, כך שהשם והסימון יופעלו מסחרית רק כאשר קיימת הוכחת זכאות למוצר ולאצווה הספציפיים.', 'Kito Yuzu is registered in Japan geographical indication system. The GI entity remains separate from fruit, juice and SKU so the name and mark are used commercially only when eligibility is proven for the specific product and lot.' ),
	'seo_group' => 'standards', 'primary_intent' => $c99_text( 'להבין את ה-GI של קיטו יוזו', 'Understand the Kito Yuzu GI' ), 'primary_keyword' => $c99_text( 'ציון גאוגרפי קיטו יוזו', 'Kito Yuzu GI' ),
	'secondary_keywords' => array( 'he' => array( 'GI קיטו יוזו', 'יוזו טוקושימה' ), 'en' => array( 'Kito Yuzu geographical indication', 'Tokushima yuzu GI' ) ), 'schema_type' => 'DefinedTerm',
	'facts' => array( $c99_fact( 'fact-kito-yuzu-gi-registration', 'institutional', 'MAFF מציג את Kito Yuzu כרישום GI מספר 42; הטענה אינה עוברת אוטומטית לכל מוצר שמזכיר את אזור קיטו.', 'MAFF lists Kito Yuzu as GI registration number 42; the claim does not automatically transfer to every product mentioning Kito origin.', 'official_source', 'entity', array( 'kito-yuzu-gi' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'not_applicable', 'רישום GI אינו מדידת ארומה או הרכב כימי.', 'A GI registration is not an aroma or chemistry measurement.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'הקשר מקום ומסורת ייכתב ממקורות ייעודיים.', 'Place and tradition context will be written from dedicated sources.' ),
		'institutional' => $c99_profile( 'source_backed', 'הרישום נשמר כישות סמכות עצמאית.', 'The registration remains an independent authority entity.', array( 'fact-kito-yuzu-gi-registration' ) ),
		'economic' => $c99_profile( 'pending_evidence', 'פרמיית מחיר תימדד רק בין מוצרים בני השוואה עם הוכחת זכאות.', 'Price premium will be measured only between comparable products with eligibility evidence.' ),
		'structural' => $c99_profile( 'pending_evidence', 'ה-GI יקושר למסמך זכאות של SKU ואצווה.', 'The GI will link to SKU and lot eligibility evidence.' ),
	) ),
	'categories' => array( 'culinary-science', 'geographical-indications', 'japan', 'kito-yuzu' ), 'attributes' => array( 'pa_origin' => array( 'kito-tokushima-japan' ) ), 'tags' => array( 'kito-yuzu', 'geographical-indication', 'gi-42', 'tokushima' ),
	'relations' => array( $c99_relation( 'part_of', 'ingredient-kito-yuzu', 'הרישום מספק שכבת מקור וזכאות נפרדת לישות הפרי.', 'The registration provides a separate origin and eligibility layer for the fruit entity.', true, array( 'kito-yuzu-gi' ), 'official_source' ) ),
	'revenue_models' => array( 'content_to_commerce', 'market_intelligence' ), 'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'foodservice_buyers' ),
	'prompt_en' => 'Editorial provenance photograph of fresh yuzu fruit in a mountainous Tokushima orchard context, accurate peel texture and natural foliage, soft misty daylight, museum documentation style, no GI seal, no brand, no text or certification mark.',
	'compliance' => array( $c99_compliance( 'gi-eligibility', 'שם וסימון GI יופעלו רק לאחר הוכחת זכאות של המוצר והאצווה הספציפיים.', 'The GI name and mark are used only after eligibility is proven for the specific product and lot.', array( 'kito-yuzu-gi' ) ) ),
) );

$entities[] = $c99_entity( array(
	'id' => 'tradition-washoku', 'type' => 'tradition', 'slug' => 'washoku', 'parent_id' => 'cuisine-japanese-washoku',
	'name' => $c99_text( 'וואשוקו ותרבות האוכל היפנית', 'Washoku and Japanese food culture' ),
	'summary' => $c99_text( 'וואשוקו מתואר ב-UNESCO כפרקטיקה חברתית המבוססת על ידע, מיומנויות ומסורות הקשורות לייצור, עיבוד, הכנה וצריכת מזון, עם דגש עונתי וחברתי. עמוד המסורת נפרד מעמוד המדע היפני כדי לתת מענה מדויק לכוונת חיפוש תרבותית.', 'UNESCO describes washoku as a social practice based on knowledge, skills and traditions related to producing, processing, preparing and consuming food, with seasonal and social emphasis. The tradition owner is separate from the Japanese science pillar to serve cultural search intent precisely.' ),
	'seo_group' => 'traditions', 'primary_intent' => $c99_text( 'להכיר את וואשוקו כתרבות ומסורת מזון', 'Understand washoku as food culture and tradition' ), 'primary_keyword' => $c99_text( 'וואשוקו ותרבות האוכל היפנית', 'Washoku Japanese food culture' ),
	'secondary_keywords' => array( 'he' => array( 'מסורת אוכל יפנית', 'וואשוקו UNESCO' ), 'en' => array( 'Japanese dietary culture', 'UNESCO washoku' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-washoku-unesco-context', 'cultural', 'UNESCO רשם את וואשוקו ברשימת המורשת התרבותית הבלתי מוחשית בשנת 2013 ומתאר אותו כפרקטיקה חברתית של ידע ומסורות מזון.', 'UNESCO inscribed washoku on the Representative List of Intangible Cultural Heritage in 2013 and describes it as a social practice of food knowledge and traditions.', 'official_source', 'entity', array( 'unesco-washoku' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'היבטים מדעיים יופיעו בעמודי המנות, החומרים והתהליכים ולא יוחלפו בטענה תרבותית.', 'Scientific aspects belong on dish, ingredient and process owners rather than being replaced by a cultural claim.' ),
		'cultural' => $c99_profile( 'source_backed', 'המסורת מבוססת על תיאור UNESCO ומתוחמת לעובדות המקור.', 'The tradition profile is grounded in the UNESCO description and bounded to source facts.', array( 'fact-washoku-unesco-context' ) ),
		'institutional' => $c99_profile( 'pending_evidence', 'גופי שימור והוראה יישמרו כישויות נפרדות.', 'Preservation and teaching bodies remain separate entities.' ),
		'economic' => $c99_profile( 'pending_evidence', 'חיבור למסחר ייעשה דרך מוצרים ומנות רלוונטיים, לא דרך מכירת המורשת.', 'Commerce connects through relevant products and dishes, not by selling heritage.' ),
		'structural' => $c99_profile( 'pending_evidence', 'עמוד המסורת הוא בעל כוונה נפרד מן ה-Hub המדעי.', 'The tradition page is a distinct intent owner from the science hub.' ),
	) ),
	'categories' => array( 'traditions', 'japan', 'washoku' ), 'attributes' => array( 'pa_origin' => array( 'japan' ) ), 'tags' => array( 'washoku', 'japanese-culture', 'seasonality', 'unesco' ),
	'relations' => array( $c99_relation( 'part_of', 'cuisine-japanese-washoku', 'וואשוקו הוא ציר תרבותי בתוך אשכול המטבח היפני.', 'Washoku is the cultural axis within the Japanese cuisine cluster.', true, array( 'unesco-washoku' ), 'official_source' ) ),
	'revenue_models' => array( 'content_to_commerce', 'education' ), 'customer_segments' => array( 'culinary_consumers', 'culinary_students', 'research_readers' ),
	'prompt_en' => 'Museum editorial still life about washoku culture, seasonal vegetables, rice, clear dashi, lacquerware and natural fibers in a restrained Japanese setting, documentary daylight, accurate materials, no people, text, logos or sacred-symbol imitation.',
) );

$entities[] = $c99_entity( array(
	'id' => 'guide-umami-synergy', 'type' => 'guide', 'slug' => 'umami-synergy-glutamate-imp', 'parent_id' => 'hub-japanese-food-science',
	'name' => $c99_text( 'סינרגיית אומאמי, גלוטמט ו-IMP', 'Glutamate and IMP umami synergy' ),
	'summary' => $c99_text( 'מדריך המדע מרכז את הקשר בין גלוטמט, IMP והרצפטור T1R1/T1R3. הוא מבחין בין מנגנון הנתמך במחקר, תפיסת טעם ותכולה שנמדדה במוצר או באצווה.', 'This science guide connects glutamate, IMP and the T1R1/T1R3 receptor. It separates a research-supported mechanism, perceived taste and content measured in a product or lot.' ),
	'seo_group' => 'knowledge', 'primary_intent' => $c99_text( 'להבין כיצד גלוטמט ו-IMP יוצרים סינרגיית אומאמי', 'Understand how glutamate and IMP create umami synergy' ), 'primary_keyword' => $c99_text( 'סינרגיית אומאמי גלוטמט IMP', 'glutamate IMP umami synergy' ),
	'secondary_keywords' => array( 'he' => array( 'רצפטור T1R1 T1R3', 'מדע האומאמי' ), 'en' => array( 'T1R1 T1R3 receptor', 'umami science' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-umami-guide-mechanism', 'scientific', 'המחקר מציע מנגנון שבו נוקלאוטידים מייצבים מצב פעיל של הרצפטור ומגבירים תגובה לגלוטמט; זו תמיכה במודל מולקולרי ולא מדידת טעם של מוצר או אצווה מסוימים.', 'The study proposes a mechanism in which nucleotides stabilize an active receptor state and enhance response to glutamate; this supports a molecular model and is not a taste measurement for a particular product or lot.', 'peer_reviewed_context', 'category', array( 'umami-receptor-2009' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'המנגנון מנוסח כתמיכה מחקרית ולא כוודאות מוחלטת לכל מזון.', 'The mechanism is phrased as research support rather than absolute certainty for every food.', array( 'fact-umami-guide-mechanism' ) ),
		'cultural' => $c99_profile( 'pending_evidence', 'היסטוריית המונח תטופל בנפרד מן המנגנון.', 'The history of the term will be handled separately from the mechanism.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'חוקרים ומוסדות יתווספו כרשומות ייחוס.', 'Researchers and institutions will be added as reference records.' ),
		'economic' => $c99_profile( 'pending_evidence', 'יישום מסחרי יתחבר להרכב מוצר בדוק ולשימוש קולינרי.', 'Commercial use will connect to tested product composition and culinary application.' ),
		'structural' => $c99_profile( 'pending_evidence', 'המדריך מרכז את הקשר בין גלוטמט, IMP, תפיסת אומאמי והמחקר התומך.', 'The guide brings together glutamate, IMP, perceived umami and the supporting research.' ),
	) ),
	'categories' => array( 'knowledge', 'food-science', 'taste', 'umami' ), 'attributes' => array( 'pa_flavor_profile' => array( 'umami' ) ), 'tags' => array( 'umami', 'glutamate', 'imp', 't1r1-t1r3' ),
	'relations' => array( $c99_relation( 'contains', 'molecule-l-glutamate', 'גלוטמט הוא ישות משנה במדריך.', 'Glutamate is a subject entity in the guide.', true, array( 'umami-receptor-2009' ), 'peer_reviewed_context' ), $c99_relation( 'contains', 'molecule-inosine-monophosphate', 'IMP הוא ישות משנה במדריך.', 'IMP is a subject entity in the guide.', true, array( 'umami-receptor-2009' ), 'peer_reviewed_context' ) ),
	'revenue_models' => array( 'education', 'content_to_commerce' ), 'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'culinary_students', 'research_readers' ),
	'prompt_en' => 'Editorial culinary science visualization of glutamate and IMP molecular models beside kombu, katsuobushi and clear dashi, accurate atom conventions, museum-grade dark background, no health claims, text or labels.',
) );

$entities[] = $c99_entity( array(
	'id' => 'guide-wasabi-aitc', 'type' => 'guide', 'slug' => 'wasabi-aitc-pungency', 'parent_id' => 'hub-japanese-food-science',
	'name' => $c99_text( 'AITC וחריפות וואסבי', 'AITC and wasabi pungency' ),
	'summary' => $c99_text( 'המדריך מסביר כיצד גרירת קנה השורש והמערכת האנזימטית קשורות ליצירת איזותיוציאנטים נדיפים, ובפרט AITC. הוא אינו הופך חריפות להבטחת בריאות או לבקרת בטיחות מזון.', 'The guide explains how rhizome grating and the enzyme system relate to volatile isothiocyanates, particularly AITC. It does not turn pungency into a health promise or food-safety control.' ),
	'seo_group' => 'knowledge', 'primary_intent' => $c99_text( 'להבין AITC וחריפות של וואסבי טרי', 'Understand AITC and fresh wasabi pungency' ), 'primary_keyword' => $c99_text( 'AITC וחריפות וואסבי', 'AITC wasabi pungency' ),
	'secondary_keywords' => array( 'he' => array( 'אליל איזותיוציאנט', 'מדע וואסבי' ), 'en' => array( 'allyl isothiocyanate', 'wasabi pungency science' ) ), 'schema_type' => 'Article',
	'facts' => array(
		$c99_fact( 'fact-wasabi-guide-itc', 'scientific', 'המחקר מראה שונות גנטית ועונתית בהרכב איזותיוציאנטים בוואסבי, ולכן אין לייחס ריכוז AITC קבוע לקנה שורש ללא מדידה.', 'Research shows genetic and seasonal variation in wasabi isothiocyanate composition, so a fixed AITC concentration must not be assigned to a rhizome without measurement.', 'peer_reviewed_context', 'category', array( 'wasabi-itc-2023' ) ),
		$c99_fact( 'fact-wasabi-guide-enzyme-system', 'scientific', 'כאשר רקמת וואסבי נפגעת, המערכת האנזימטית ממירה גלוקוזינולטים לאיזותיוציאנטים. זהו הסבר מנגנוני, לא מדידת ריכוז של מוצר או אצווה.', 'When wasabi tissue is disrupted, its enzyme system converts glucosinolates into isothiocyanates. This is a mechanism explanation, not a concentration measurement for a product or lot.', 'peer_reviewed_context', 'technique_context', array( 'wasabi-itc-2023' ) ),
	),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'השונות היא חלק מרכזי מן ההסבר ולא הערת שוליים.', 'Variation is central to the explanation rather than a footnote.', array( 'fact-wasabi-guide-itc' ) ),
		'cultural' => $c99_profile( 'pending_evidence', 'שיטות גרירה מסורתיות ייוחסו למקורות נפרדים.', 'Traditional grating methods will be attributed to separate sources.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'מגדלים וחוקרים יישמרו כישויות נפרדות.', 'Growers and researchers remain separate entities.' ),
		'economic' => $c99_profile( 'pending_evidence', 'ערך מסחרי יתחבר לטריות, מקור, קירור ותפוקה.', 'Commercial value will connect to freshness, origin, refrigeration and yield.' ),
		'structural' => $c99_profile( 'pending_evidence', 'המדריך מחבר מולקולה, חומר גלם, כלי ותהליך.', 'The guide connects molecule, ingredient, tool and process.' ),
	) ),
	'categories' => array( 'knowledge', 'food-science', 'aroma-and-pungency', 'wasabi' ), 'attributes' => array( 'pa_flavor_profile' => array( 'volatile-pungency' ) ), 'tags' => array( 'aitc', 'wasabi', 'isothiocyanates', 'myrosinase' ),
	'relations' => array(
		$c99_relation( 'contains', 'molecule-allyl-isothiocyanate', 'AITC הוא איזותיוציאנט נדיף שנבחן בהקשר של חריפות וואסבי.', 'AITC is a volatile isothiocyanate examined in the context of wasabi pungency.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
		$c99_relation( 'references', 'ingredient-fresh-wasabi', 'החומר מחובר להקשר מין, עונה ואצווה.', 'The ingredient connects to species, season and lot context.', true, array( 'wasabi-itc-2023' ), 'peer_reviewed_context' ),
		$c99_relation( 'references', 'equipment-wasabi-grater', 'מדריך הכלי מחבר את מנגנון החריפות לפעולת גרירת קנה השורש.', 'The tool guide connects the pungency mechanism to grating the rhizome.', true, array( 'yamamoto-haganezame-spec', 'wasabi-itc-2023' ), 'official_source' ),
	),
	'revenue_models' => array( 'education', 'content_to_commerce' ), 'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'research_readers' ),
	'prompt_en' => 'Culinary science photograph of a freshly grated wasabi rhizome beside a clean molecular visualization of AITC, cool macro lighting, visible fibrous texture and natural moisture, no medical symbols, labels or claims.',
) );

$entities[] = $c99_entity( array(
	'id' => 'guide-koji-hydrolysis', 'type' => 'guide', 'slug' => 'koji-enzymes-hydrolysis-guide', 'parent_id' => 'hub-japanese-food-science',
	'name' => $c99_text( 'הידרוליזה אנזימטית בקוג׳י', 'Koji enzymatic hydrolysis' ),
	'summary' => $c99_text( 'המדריך מפריד בין מצע הקוג׳י, התרבית, אנזימים, תנאי גידול ופירוק של עמילנים וחלבונים. כך אפשר לקשר קומה קוג׳י, קוג׳י לשויו ומוצר מותסס בלי לטעון שכל התהליכים זהים.', 'The guide separates koji substrate, culture, enzymes, cultivation conditions and starch and protein breakdown. This links kome koji, shoyu koji and fermented products without claiming every process is identical.' ),
	'seo_group' => 'knowledge', 'primary_intent' => $c99_text( 'להבין אנזימי קוג׳י והידרוליזה', 'Understand koji enzymes and hydrolysis' ), 'primary_keyword' => $c99_text( 'אנזימי קוג׳י והידרוליזה', 'koji enzymes and hydrolysis' ),
	'secondary_keywords' => array( 'he' => array( 'עמילאז קוג׳י', 'פרוטאז קוג׳י' ), 'en' => array( 'koji amylase', 'koji protease' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-koji-guide-process', 'scientific', 'MAFF מתאר קוג׳י כמקור אנזימים המפרקים עמילנים וחלבונים בתהליכי מזון יפניים; זהות המצע והתהליך נשמרת לכל מוצר.', 'MAFF describes koji as a source of enzymes that break down starches and proteins in Japanese food processes; substrate and process identity remain product-specific.', 'official_source', 'technique_context', array( 'maff-fermented-foods', 'maff-hon-mirin' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'ההסבר שומר הפרדה בין אנזים, מצע ומוצר.', 'The explanation preserves the distinction between enzyme, substrate and product.', array( 'fact-koji-guide-process' ) ),
		'cultural' => $c99_profile( 'pending_evidence', 'היסטוריית השימושים תיכתב לפי מוצר ומסורת.', 'Use history will be written by product and tradition.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'יצרני תרביות ומוסדות מחקר יתווספו בנפרד.', 'Culture producers and research institutions will be added separately.' ),
		'economic' => $c99_profile( 'pending_evidence', 'תפוקה, זמן, הפסד ואיכות יתחברו למפרט תהליך.', 'Yield, time, loss and quality will connect to a process specification.' ),
		'structural' => $c99_profile( 'pending_evidence', 'המדריך הוא בעל הכוונה למונחי אנזימי קוג׳י.', 'The guide owns koji-enzyme intent.' ),
	) ),
	'categories' => array( 'knowledge', 'food-science', 'fermentation', 'koji-enzymes' ), 'attributes' => array( 'pa_fermentation_method' => array( 'koji-cultivation' ) ), 'tags' => array( 'koji-enzymes', 'hydrolysis', 'amylase', 'protease' ),
	'relations' => array( $c99_relation( 'contains', 'reaction-koji-enzymatic-hydrolysis', 'תגובת ההידרוליזה היא ישות משנה במדריך.', 'The hydrolysis reaction is a subject entity in the guide.', true, array( 'maff-fermented-foods' ), 'official_source' ), $c99_relation( 'references', 'ingredient-kome-koji', 'קומה קוג׳י הוא מצע אורז נפרד.', 'Kome koji is a separate rice substrate.', true, array( 'maff-fermented-foods' ), 'official_source' ), $c99_relation( 'references', 'ingredient-shoyu-koji', 'קוג׳י לשויו הוא מצע סויה וחיטה נפרד.', 'Shoyu koji is a separate soybean and wheat substrate.', true, array( 'maff-fermented-foods' ), 'official_source' ) ),
	'revenue_models' => array( 'education', 'content_to_commerce' ), 'customer_segments' => array( 'professional_chefs', 'culinary_students', 'research_readers' ),
	'prompt_en' => 'Scientific culinary visualization of rice koji and shoyu koji as distinct substrates beside simplified starch and protein chains being enzymatically cleaved, accurate food textures, museum lighting, no labels or futuristic effects.',
) );

$entities[] = $c99_entity( array(
	'id' => 'comparison-yanagiba-steels', 'type' => 'comparison', 'slug' => 'yanagiba-white-2-vs-blue-1', 'parent_id' => 'equipment-yanagiba',
	'name' => $c99_text( 'White 2 מול Blue 1 ביאנאגיבה', 'White 2 vs Blue 1 in yanagiba knives' ),
	'summary' => $c99_text( 'עמוד ההשוואה מפריד פלדה, מבנה שכבות, חיסום, גאומטריה, אורך, ידית, יצרן ושירות. White 2 ו-Blue 1 אינם מדרגות מחיר או איכות אוניברסליות, ו-suminagashi הוא תיאור בנייה ומראה.', 'The comparison separates steel, layered construction, heat treatment, geometry, length, handle, maker and service. White 2 and Blue 1 are not universal price or quality grades, and suminagashi describes construction and appearance.' ),
	'seo_group' => 'knowledge', 'primary_intent' => $c99_text( 'להשוות White 2 ו-Blue 1 ביאנאגיבה', 'Compare White 2 and Blue 1 in yanagiba knives' ), 'primary_keyword' => $c99_text( 'White 2 מול Blue 1 יאנאגיבה', 'White 2 vs Blue 1 yanagiba' ),
	'secondary_keywords' => array( 'he' => array( 'פלדות יאנאגיבה', 'שירוגאמי מול אאוגאמי' ), 'en' => array( 'yanagiba steel comparison', 'Shirogami vs Aogami' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-yanagiba-comparison-boundary', 'structural', 'רשומות Tsubaya מציגות פלדה, מבנה, אורך וידית ברמת דגם; תצפיות המחיר אינן השוואת ביצועים בתנאים שווים.', 'Tsubaya listings expose steel, construction, length and handle at model level; price observations are not controlled performance comparisons.', 'official_source', 'category', array( 'tsubaya-white2-collection-2026', 'tsubaya-blue1-suminagashi-2026' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'ביצועים ידרשו מפרט חיסום ומבחן שימוש מבוקר.', 'Performance requires heat-treatment specifications and controlled use testing.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'מלאכת היצרן אינה נגזרת משם הפלדה.', 'Maker craft is not inferred from the steel name.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'יצרנים וקמעונאים נשמרים בנפרד.', 'Makers and retailers remain separate.' ),
		'economic' => $c99_profile( 'pending_evidence', 'המחירים מושווים רק בין SKU בני השוואה.', 'Prices are compared only between like-for-like SKUs.' ),
		'structural' => $c99_profile( 'source_backed', 'העמוד מאחד כוונת השוואה ומונע שני עמודי חומר מתחרים.', 'The page consolidates comparison intent and prevents two competing material pages.', array( 'fact-yanagiba-comparison-boundary' ) ),
	) ),
	'categories' => array( 'knowledge', 'equipment-guides', 'japanese-knives', 'comparisons' ), 'attributes' => array( 'pa_steel' => array( 'white-2', 'blue-1' ) ), 'tags' => array( 'yanagiba-comparison', 'white-2', 'blue-1', 'suminagashi' ),
	'relations' => array( $c99_relation( 'contains', 'material-yanagiba-white2', 'White 2 הוא מפרט חומר להשוואה.', 'White 2 is a material specification in the comparison.', true, array( 'tsubaya-white2-collection-2026' ), 'official_source' ), $c99_relation( 'contains', 'material-yanagiba-blue1-suminagashi', 'Blue 1 ו-suminagashi הם מפרטי חומר ובנייה להשוואה.', 'Blue 1 and suminagashi are material and construction specifications in the comparison.', true, array( 'tsubaya-blue1-suminagashi-2026' ), 'official_source' ) ),
	'revenue_models' => array( 'content_to_commerce', 'lead_generation' ), 'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'foodservice_buyers' ),
	'prompt_en' => 'Neutral split studio comparison of two unbranded yanagiba knives, one plain White 2 reference and one Blue 1 layered reference, matched length and camera angle, controlled raking light, no superiority cues, labels or logos.',
) );

$entities[] = $c99_entity( array(
	'id' => 'guide-japanese-markets', 'type' => 'guide', 'slug' => 'japanese-culinary-markets', 'parent_id' => 'hub-japanese-sourcing',
	'name' => $c99_text( 'שווקי אוכל וציוד קולינרי ביפן', 'Japanese culinary food and equipment markets' ),
	'summary' => $c99_text( 'המדריך מרכז את Toyosu, השוק החיצוני של Tsukiji ורחוב Kappabashi לפי תפקיד: סיטונאות, קמעונאות, תרבות אוכל וציוד. הוא אינו מציג נוכחות בשוק כהוכחת מלאי או כהסכם אספקה.', 'The guide organizes Toyosu, Tsukiji Outer Market and Kappabashi by role: wholesale, retail, food culture and equipment. Market presence is not presented as proof of stock or a supply agreement.' ),
	'seo_group' => 'knowledge', 'primary_intent' => $c99_text( 'להכיר שווקי חומרי גלם וציוד קולינרי ביפן', 'Understand Japanese ingredient and culinary equipment markets' ), 'primary_keyword' => $c99_text( 'שווקי אוכל וציוד ביפן', 'Japanese culinary markets' ),
	'secondary_keywords' => array( 'he' => array( 'Toyosu Tsukiji Kappabashi', 'שווקי קולינריה בטוקיו' ), 'en' => array( 'Toyosu Tsukiji Kappabashi guide', 'Tokyo culinary markets' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-japanese-markets-roles', 'institutional', 'מקורות רשמיים נפרדים מתארים את Toyosu, Tsukiji Outer Market ו-Kappabashi; כל מקום נשמר כישות עצמאית תחת מדריך אחד.', 'Separate official sources describe Toyosu, Tsukiji Outer Market and Kappabashi; each place remains an independent entity under one guide.', 'official_source', 'entity', array( 'toyosu-tmg', 'tsukiji-official', 'kappabashi-official' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'not_applicable', 'זהו מדריך מוסדי וגאוגרפי, לא בדיקת מוצר.', 'This is an institutional and geographic guide, not a product test.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'היסטוריה ותרבות שוק ייכתבו ממקורות ייעודיים.', 'Market history and culture will be written from dedicated sources.' ),
		'institutional' => $c99_profile( 'source_backed', 'תפקיד כל שוק נלקח מן המקור הרשמי שלו.', 'Each market role comes from its official source.', array( 'fact-japanese-markets-roles' ) ),
		'economic' => $c99_profile( 'pending_evidence', 'ספקים ומחירים דורשים רשומות מתוארכות נפרדות.', 'Suppliers and prices require separate dated records.' ),
		'structural' => $c99_profile( 'pending_evidence', 'המדריך הוא בעל כוונת החיפוש; פרופילי השוק הם ישויות משנה.', 'The guide owns search intent while market profiles are subject entities.' ),
	) ),
	'categories' => array( 'knowledge', 'sourcing', 'japan', 'markets' ), 'attributes' => array( 'pa_market' => array( 'japan' ) ), 'tags' => array( 'toyosu', 'tsukiji', 'kappabashi', 'japanese-markets' ),
	'relations' => array( $c99_relation( 'contains', 'market-toyosu', 'Toyosu הוא פרופיל משנה במדריך.', 'Toyosu is a subject profile in the guide.', true, array( 'toyosu-tmg' ), 'official_source' ), $c99_relation( 'contains', 'market-tsukiji-outer', 'Tsukiji Outer Market הוא פרופיל משנה במדריך.', 'Tsukiji Outer Market is a subject profile in the guide.', true, array( 'tsukiji-official' ), 'official_source' ), $c99_relation( 'contains', 'market-kappabashi-dougu', 'Kappabashi הוא פרופיל משנה במדריך.', 'Kappabashi is a subject profile in the guide.', true, array( 'kappabashi-official' ), 'official_source' ) ),
	'revenue_models' => array( 'content_to_commerce', 'lead_generation', 'market_intelligence' ), 'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'foodservice_buyers' ),
	'prompt_en' => 'Editorial triptych of Tokyo culinary sourcing, a clean wholesale seafood market aisle, an outer food market storefront texture and a professional kitchenware street, documentary realism, no copied signs, faces, logos or text.',
) );

/* Markets, producers, shops, restaurants and institutions. */
$institution_like = static function ( $id, $type, $slug, $name, $summary, $group, $keyword, $source_id, $schema_type, $prompt, $next_review = '2026-11-05' ) use ( $c99_entity, $c99_text, $c99_fact, $c99_profiles, $c99_profile ) {
	$is_restaurant = 'restaurant' === $type;
	$fact_id = 'fact-' . $slug . ( $is_restaurant ? '-guide-recognition' : '-official-identity' );
	return $c99_entity( array(
		'id' => $id, 'type' => $type, 'slug' => $slug, 'name' => $name, 'summary' => $summary,
		'seo_group' => $group, 'primary_intent' => $c99_text( 'להכיר את ' . $name['he'] . ' בהקשר הקולינרי', 'Understand ' . $name['en'] . ' in culinary context' ), 'primary_keyword' => $keyword,
		'secondary_keywords' => array( 'he' => array( $name['he'] ), 'en' => array( $name['en'] ) ), 'schema_type' => $schema_type,
		'facts' => array( $c99_fact( $fact_id, 'institutional', $summary['he'], $summary['en'], $is_restaurant ? 'third_party_guide' : 'official_source', 'entity', array( $source_id ) ) ),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'pending_evidence', 'תכניות מחקר, הוראה או מפרטים מדעיים יירשמו כישויות נפרדות.', 'Research, teaching programs or scientific specifications will be separate entities.' ),
			'cultural' => $c99_profile( 'pending_evidence', 'היסטוריה והשפעה ייכתבו רק ממקורות מזוהים.', 'History and influence will be written only from identified sources.' ),
			'institutional' => $c99_profile(
				'source_backed',
				$is_restaurant ? 'ההכרה נשמרת כרשומת מדריך צד שלישי מתוארכת, בנפרד מזהות המסעדה.' : 'הזהות והייעוד מבוססים על המקור הרשמי של הגוף.',
				$is_restaurant ? 'Recognition is stored as a dated third-party guide record, separate from restaurant identity.' : 'Identity and purpose are grounded in the entity official source.',
				array( $fact_id )
			),
			'economic' => $c99_profile( 'pending_evidence', 'מחירים ציבוריים יישמרו כתצפיות מתוארכות; תנאים מסחריים יישארו פרטיים.', 'Public prices will be dated observations; commercial terms remain private.' ),
			'structural' => $c99_profile( 'pending_evidence', 'הישות מקושרת לגרף כנקודת סמכות, לימוד, שוק או אספקה בהתאם לסוגה.', 'The entity joins the graph as an authority, education, market or supply node according to type.' ),
		) ),
		'categories' => array( 'culinary-network', $group ), 'attributes' => array( 'pa_origin' => array( 'entity-specific' ) ), 'tags' => array( $type, $slug ),
		'prompt_en' => $prompt, 'asset_state' => 'original_photography_required', 'rights_method' => 'licensed_or_original_documentary_only',
		'shot_list' => array( 'rights-cleared exterior', 'rights-cleared interior detail', 'public wayfinding context', 'editorial map' ),
		'next_review_at' => $next_review,
	) );
};

$entities[] = $institution_like(
	'market-toyosu', 'market', 'toyosu-market', $c99_text( 'שוק טויוסו', 'Toyosu Market' ),
	$c99_text( 'שוק טויוסו הוא שוק סיטונאי מרכזי המופעל על ידי ממשלת מטרופולין טוקיו. הוא נשמר כישות שוק ומקור מחקר לאספקת דגים ותוצרת, אך כניסה לאזורי מסחר והשתתפות בעסקאות כפופות להרשאה.', 'Toyosu Market is a major wholesale market operated by the Tokyo Metropolitan Government. It is stored as a market and supply-research entity, while access to trading areas and participation in transactions are permission-controlled.' ),
	'markets', $c99_text( 'שוק טויוסו', 'Toyosu Market' ), 'toyosu-tmg', 'Place',
	'Rights-cleared documentary photograph of the actual Toyosu Market public observation areas and logistics architecture, photographed from a permitted visitor position, clean morning light, no restricted trading activity, no identifiable workers without releases.'
);
$entities[] = $institution_like(
	'market-tsukiji-outer', 'market', 'tsukiji-outer-market', $c99_text( 'השוק החיצוני של צוקיג׳י', 'Tsukiji Outer Market' ),
	$c99_text( 'השוק החיצוני של צוקיג׳י מציג קמעונאות, מסעדות ומזון יפני מסורתי לקהל. הוא אינו זהה לשוק הסיטונאי שעבר לטויוסו, ולכן שתי הישויות נשמרות בנפרד.', 'Tsukiji Outer Market presents retail, restaurants and traditional Japanese foods to the public. It is not the wholesale market relocated to Toyosu, so the two entities remain separate.' ),
	'markets', $c99_text( 'השוק החיצוני צוקיג׳י', 'Tsukiji Outer Market' ), 'tsukiji-official', 'Place',
	'Rights-cleared documentary street photograph of the actual Tsukiji Outer Market during normal public hours, ingredient storefront rhythm, human-scale wayfinding and food culture, no staged vendor claims, no visible private customer data.'
);
$entities[] = $institution_like(
	'market-kappabashi-dougu', 'market', 'kappabashi-dougu-street', $c99_text( 'רחוב כלי המטבח קפאבשי', 'Kappabashi Dougu Street' ),
	$c99_text( 'קפאבשי הוא אזור מסחרי בטוקיו המתמחה בציוד למסעדות, כלי מטבח, כלי הגשה, אריזה, ריהוט ושירותי הקמה. האתר הרשמי מתאר רחוב באורך כ-800 מטר עם יותר מ-170 חנויות.', 'Kappabashi is a Tokyo commercial district specializing in restaurant equipment, kitchenware, tableware, packaging, furniture and shop services. Its official site describes an approximately 800-meter street with more than 170 shops.' ),
	'markets', $c99_text( 'קפאבשי ציוד מטבח', 'Kappabashi kitchen equipment' ), 'kappabashi-official', 'Place',
	'Rights-cleared documentary photograph of the actual Kappabashi Dougu Street from the public sidewalk, layered professional kitchenware storefronts, restrained daylight, accurate urban scale, no copied shop logos as the main subject.'
);
$entities[] = $institution_like(
	'equipment-shop-tsubaya', 'equipment_shop', 'tsubaya', $c99_text( 'צובאיה', 'Tsubaya' ),
	$c99_text( 'Tsubaya היא חנות סכינים המופיעה במפת החנויות הרשמית של קפאבשי ומפעילה קטלוג מקוון. היא נשמרת כמקור קמעונאי ציבורי לתצפיות מוצר ומחיר, לא כספק מאושר של Complete99.', 'Tsubaya is a knife shop listed on the official Kappabashi map and operates an online catalog. It is stored as a public retail source for product and price observations, not as an approved Complete99 supplier.' ),
	'equipment-shops', $c99_text( 'צובאיה סכינים יפניות', 'Tsubaya Japanese knives' ), 'kappabashi-map-2026', 'Store',
	'Rights-cleared documentary photograph of the actual Tsubaya storefront or interior with written permission, emphasizing knife display organization and service context, no generated branding, no false inventory, no identifiable customers.'
);
$entities[] = $institution_like(
	'producer-yamaroku-shoyu', 'producer', 'yamaroku-shoyu', $c99_text( 'ימרוקו שויו', 'Yamaroku Soy Sauce' ),
	$c99_text( 'ימרוקו מייצרת את רטבי הסויה שלה בקיוקה בנפח 3,000 עד 6,000 ליטר ומתארת תסיסה המבוססת על שמרים וחיידקי חומצת חלב. הישות היא דוגמת יצרן מתועדת ואינה מעידה על קשר אספקה ל-Complete99.', 'Yamaroku states that it produces its soy sauces in 3,000 to 6,000-liter kioke and describes fermentation involving yeast and lactic acid bacteria. The entity is a documented producer example and does not indicate a Complete99 supply relationship.' ),
	'producers', $c99_text( 'ימרוקו שויו קיוקה', 'Yamaroku kioke shoyu' ), 'yamaroku-about', 'Organization',
	'Rights-cleared original documentary photograph inside the actual Yamaroku brewery with written permission, showing cedar kioke scale and natural fermentation environment, no recreated logo, no false product label, no unverified worker identity.'
);

$entities[] = $institution_like(
	'restaurant-myojaku', 'restaurant', 'myojaku', $c99_text( 'מיוג׳אקו', 'Myojaku' ),
	$c99_text( 'Myojaku מופיעה במדריך מישלן טוקיו 2026 כמסעדה יפנית בעלת שלושה כוכבים, נכון למהדורה שהוכרזה ב-25 בספטמבר 2025. ההכרה נשמרת כעובדה מתוארכת הדורשת בדיקה מחדש, לא כתווית קבועה.', 'Myojaku appears in the Michelin Guide Tokyo 2026 as a three-star Japanese restaurant, based on the edition announced on September 25, 2025. The distinction is stored as a dated fact requiring revalidation, not a permanent label.' ),
	'restaurants', $c99_text( 'מיוג׳אקו מסעדה יפנית', 'Myojaku Japanese restaurant' ), 'michelin-tokyo-2026', 'Restaurant',
	'Rights-cleared documentary photograph supplied or approved by the actual restaurant, showing one verified seasonal preparation or the dining space, captioned with date and rights record, no generated imitation of the restaurant cuisine.', '2026-10-01'
);
$entities[] = $institution_like(
	'restaurant-nishiazabu-sushi-shin', 'restaurant', 'nishiazabu-sushi-shin', $c99_text( 'נישיאזבו סושי שין', 'Nishiazabu Sushi Shin' ),
	$c99_text( 'Nishiazabu Sushi Shin מופיעה במדריך מישלן טוקיו 2026 כמסעדת סושי בעלת שני כוכבים, נכון למהדורה שהוכרזה ב-25 בספטמבר 2025. המדריך מדגיש מיומנות חיתוך ועיצוב כל יחידה, והסטטוס דורש בדיקה תקופתית.', 'Nishiazabu Sushi Shin appears in the Michelin Guide Tokyo 2026 as a two-star sushi restaurant in the edition announced on September 25, 2025. The guide highlights slicing and shaping skill, and the status requires periodic revalidation.' ),
	'restaurants', $c99_text( 'נישיאזבו סושי שין', 'Nishiazabu Sushi Shin' ), 'michelin-tokyo-2026', 'Restaurant',
	'Rights-cleared documentary photograph supplied or approved by the actual restaurant, focused on verified knife-work context or dining counter details, with date and rights record, no generated imitation of signature dishes.', '2026-10-01'
);

$entities[] = $c99_entity( array(
	'id' => 'guide-edition-michelin-tokyo-2026', 'type' => 'guide_edition', 'slug' => 'michelin-guide-tokyo-2026', 'parent_id' => 'hub-japanese-restaurants',
	'name' => $c99_text( 'מדריך מישלן טוקיו 2026', 'Michelin Guide Tokyo 2026' ),
	'summary' => $c99_text( 'מהדורת טוקיו 2026 של מדריך מישלן נשמרת כישות מתוארכת ונפרדת מן המסעדות. כך ניתן לעדכן הכרה בכל מהדורה בלי להפוך כוכב לתכונה קבועה של מסעדה.', 'The Michelin Guide Tokyo 2026 edition is a dated entity separate from restaurants. This allows recognition to change by edition without turning a star into a permanent restaurant attribute.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'source_reviewed', 'next_review_at' => '2026-10-01',
	'seo_group' => 'references', 'primary_intent' => $c99_text( 'לתעד הכרות במהדורת מישלן טוקיו 2026', 'Document recognition in the Michelin Guide Tokyo 2026 edition' ), 'primary_keyword' => $c99_text( 'רשומת מדריך מישלן טוקיו 2026', 'Michelin Guide Tokyo 2026 reference record' ),
	'secondary_keywords' => array( 'he' => array(), 'en' => array() ), 'schema_type' => 'CreativeWork',
	'facts' => array( $c99_fact( 'fact-michelin-tokyo-2026-edition', 'institutional', 'המקור הרשמי של המדריך הודיע על בחירת טוקיו 2026 ב-25 בספטמבר 2025; הכרות במסעדות נשמרות מול מהדורה זו.', 'The guide official source announced the Tokyo 2026 selection on September 25 2025; restaurant recognitions are stored against this edition.', 'third_party_guide', 'entity', array( 'michelin-tokyo-2026' ), false ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'not_applicable', 'דירוג מדריך אינו מחקר מדעי.', 'A guide rating is not scientific research.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'השפעה תרבותית דורשת מקורות נפרדים.', 'Cultural influence requires separate sources.' ),
		'institutional' => $c99_profile( 'source_backed', 'המהדורה והתאריך נשמרים כישות ייחוס.', 'The edition and date remain a reference entity.', array( 'fact-michelin-tokyo-2026-edition' ) ),
		'economic' => $c99_profile( 'pending_evidence', 'אין להסיק מחיר או כדאיות עסקית מן ההכרה בלבד.', 'Price or commercial viability is not inferred from recognition alone.' ),
		'structural' => $c99_profile( 'pending_evidence', 'כל הכרה מקשרת מסעדה, מהדורה, תאריך ומקור.', 'Each recognition links restaurant, edition, date and source.' ),
	) ),
	'categories' => array( 'references', 'restaurant-guides', 'tokyo', 'edition-2026' ), 'attributes' => array(), 'tags' => array( 'michelin-guide', 'tokyo-2026', 'dated-recognition' ),
	'relations' => array(
		$c99_relation( 'recognizes', 'restaurant-myojaku', 'המהדורה מציגה את Myojaku בשלושה כוכבים.', 'The edition lists Myojaku with three stars.', false, array( 'michelin-tokyo-2026' ), 'third_party_guide' ),
		$c99_relation( 'recognizes', 'restaurant-nishiazabu-sushi-shin', 'המהדורה מציגה את Nishiazabu Sushi Shin בשני כוכבים.', 'The edition lists Nishiazabu Sushi Shin with two stars.', false, array( 'michelin-tokyo-2026' ), 'third_party_guide' ),
	),
	'revenue_models' => array( 'market_intelligence', 'content_to_commerce' ), 'customer_segments' => array( 'professional_chefs', 'foodservice_buyers', 'research_readers' ),
	'prompt_en' => 'Private editorial timeline card for a restaurant guide edition, restrained Tokyo map geometry and two anonymous dining silhouettes, empty fields for separately typeset date and recognition, no Michelin logo, stars, copied trade dress or text.',
) );

$entities[] = $institution_like(
	'institution-japanese-culinary-academy', 'culinary_institution', 'japanese-culinary-academy', $c99_text( 'האקדמיה היפנית לקולינריה', 'Japanese Culinary Academy' ),
	$c99_text( 'האקדמיה היפנית לקולינריה נוסדה בשנת 2004 במטרה לקדם הבנה עולמית של המטבח היפני ולתרום לדור הבא של אנשי המקצוע. היא נשמרת כישות מוסדית ומקור סמכות, לא כהסמכה של Complete99.', 'The Japanese Culinary Academy was established in 2004 to promote global understanding of Japanese cuisine and contribute to the next generation of professionals. It is stored as an institutional authority entity, not as a Complete99 certification.' ),
	'institutions', $c99_text( 'האקדמיה היפנית לקולינריה', 'Japanese Culinary Academy' ), 'japanese-culinary-academy', 'Organization',
	'Rights-cleared documentary photograph of an actual Japanese Culinary Academy public event or approved educational material, with written permission and caption, no generated logo, no implied Complete99 affiliation.'
);
$entities[] = $institution_like(
	'institution-danon', 'culinary_institution', 'danon-culinary-school', $c99_text( 'דנון, בית הספר למקצועות הקולינריה', 'Danon Culinary School' ),
	$c99_text( 'דנון הוא בית ספר ישראלי ללימודי בישול וקונדיטוריה המציע מסלולים לאנשי מקצוע ולחובבים. הישות מאפשרת למפות הכשרה קולינרית בישראל בלי לטעון לשותפות או להעדפה.', 'Danon is an Israeli culinary school offering cooking and pastry studies for professionals and enthusiasts. The entity supports mapping culinary education in Israel without claiming partnership or preference.' ),
	'institutions', $c99_text( 'דנון בית ספר לקולינריה', 'Danon culinary school' ), 'danon-official', 'EducationalOrganization',
	'Rights-cleared documentary photograph of the actual Danon culinary school learning environment with institutional permission, showing equipment and teaching context without identifiable students unless released, no generated logo.'
);
$entities[] = $institution_like(
	'institution-bishulim', 'culinary_institution', 'bishulim-culinary-school', $c99_text( 'בית הספר בישולים', 'Bishulim Culinary School' ),
	$c99_text( 'בישולים הוא בית ספר ישראלי להכשרה מקצועית בבישול ובקונדיטוריה. האתר הרשמי מציג פעילות של יותר מ-27 שנים ויותר מ-16,000 בוגרים; המספרים נשמרים כטענת המוסד ומתעדכנים בבדיקה חוזרת.', 'Bishulim is an Israeli school for professional cooking and pastry training. Its official site states more than 27 years of activity and more than 16,000 graduates; these figures are stored as institution claims and require rechecking.' ),
	'institutions', $c99_text( 'בית ספר בישולים', 'Bishulim culinary school' ), 'bishulim-official', 'EducationalOrganization',
	'Rights-cleared documentary photograph of the actual Bishulim training kitchens with institutional permission, showing professional instruction context, no identifiable students without releases, no generated logo.'
);
$entities[] = $institution_like(
	'institution-culinary-institute-america', 'culinary_institution', 'culinary-institute-of-america', $c99_text( 'המכון הקולינרי של אמריקה', 'The Culinary Institute of America' ),
	$c99_text( 'המכון הקולינרי של אמריקה נוסד בשנת 1946 ומתאר את ייעודו כחינוך, פרקטיקה ומחקר בתחומי מזון ועסקי מזון. הוא נשמר כישות ייחוס עולמית להכשרה ומחקר.', 'The Culinary Institute of America was founded in 1946 and describes its mission through education, practice and scholarship across food and food enterprise. It is stored as a global education and research reference entity.' ),
	'institutions', $c99_text( 'המכון הקולינרי של אמריקה', 'Culinary Institute of America' ), 'cia-official', 'EducationalOrganization',
	'Rights-cleared documentary photograph of an actual Culinary Institute of America campus learning or research space with permission, factual caption and date, no generated logo, no implied affiliation.'
);
$entities[] = $institution_like(
	'institution-basque-culinary-center', 'culinary_institution', 'basque-culinary-center', $c99_text( 'מרכז הקולינריה הבאסקי', 'Basque Culinary Center' ),
	$c99_text( 'מרכז הקולינריה הבאסקי משלב פקולטה למדעי הגסטרונומיה, תכניות מתקדמות, מערכת חדשנות ומרכזי מחקר. הוא נשמר כישות ייחוס לחיבור בין קולינריה, מדע, קיימות ויזמות.', 'Basque Culinary Center combines a Faculty of Gastronomic Sciences, advanced programs, an innovation ecosystem and research centers. It is stored as a reference entity connecting gastronomy, science, sustainability and entrepreneurship.' ),
	'institutions', $c99_text( 'מרכז הקולינריה הבאסקי', 'Basque Culinary Center' ), 'basque-culinary-center-official', 'EducationalOrganization',
	'Rights-cleared documentary photograph of an actual Basque Culinary Center research or teaching environment with permission, emphasizing food science and prototyping, no generated branding, no implied affiliation.'
);

/* Exact, dated retail listing observations. They are private evidence, not live offers. */
$retail_listing = static function ( $config ) use ( $c99_entity, $c99_text, $c99_fact, $c99_profiles, $c99_profile, $c99_relation ) {
	$observed_at = '2026-08-06T00:50:31+03:00';
	$measurement = array(
		'kind'            => 'point',
		'low'             => null,
		'high'            => null,
		'value'           => $config['price'],
		'currency'        => $config['currency'],
		'unit'            => $config['unit'],
		'basis'           => $config['basis'],
		'tax_status'      => $config['tax_status'],
		'shipping_status' => $config['shipping_status'],
		'observed_at'     => $observed_at,
		'source_url'      => $config['source_url'],
		'sample_size'     => 1,
		'comparability'   => $config['comparability'],
		'capture_method'  => 'live_retail_page_manual_review_no_snapshot',
		'snapshot_digest' => '',
		'line_items'      => array(
			array(
				'name'         => $config['name']['en'],
				'price'        => $config['price'],
				'currency'     => $config['currency'],
				'tax_status'   => $config['tax_status'],
				'availability' => $config['availability'],
				'source_url'   => $config['source_url'],
				'attributes'   => $config['listing_attributes'],
			),
		),
	);
	$fact_id = 'fact-' . $config['slug'] . '-price-20260806';
	return $c99_entity( array(
		'id' => $config['id'], 'type' => 'retail_listing', 'slug' => $config['slug'], 'parent_id' => $config['subject_id'],
		'name' => $config['name'],
		'summary' => $c99_text(
			'רשומת מחיר מתוארכת למוצר ולווריאנט מדויקים. הנתון הוא תצפית קמעונאית בשוק המקור ואינו הצעת ספק, עלות נוחתת או מחיר מכירה בישראל.',
			'A dated price record for an exact product and variant. The value is a source-market retail observation, not a supplier quote, landed cost or Israeli sell price.'
		),
		'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'research_draft', 'next_review_at' => '2026-09-06',
		'seo_group' => 'observations', 'primary_intent' => $c99_text( 'תיעוד פנימי של מחיר וריאנט', 'Internal variant price evidence' ),
		'primary_keyword' => $config['keyword'],
		'secondary_keywords' => array( 'he' => array(), 'en' => array() ), 'schema_type' => 'Dataset',
		'facts' => array( $c99_fact( $fact_id, 'economic', $config['statement']['he'], $config['statement']['en'], 'market_observation', 'market_snapshot', array( $config['source_id'] ), false, $measurement, $observed_at ) ),
		'profiles' => $c99_profiles( array(
			'scientific' => $c99_profile( 'not_applicable', 'רשומת המחיר אינה מדידה מדעית של המוצר.', 'The price record is not a scientific product measurement.' ),
			'cultural' => $c99_profile( 'not_applicable', 'מחיר קמעונאי אינו ראיה למורשת או לאיכות.', 'A retail price is not evidence of heritage or quality.' ),
			'institutional' => $c99_profile( 'pending_evidence', 'המוכר והיצרן יישמרו כישויות נפרדות כאשר זהותם מאומתת.', 'Seller and maker remain separate entities when identity is verified.' ),
			'economic' => $c99_profile( 'source_backed', 'המחיר נשמר עם מטבע, יחידה, מס, משלוח, מלאי, זמן ומקור.', 'Price is stored with currency, unit, tax, shipping, availability, time and source.', array( $fact_id ) ),
			'structural' => $c99_profile( 'pending_evidence', 'רשומת המחיר מקושרת לישות הנושא ולווריאנט המדויק.', 'The price record links to its subject entity and exact variant.' ),
		) ),
		'categories' => array( 'market-intelligence', 'retail-listings', strtolower( $config['currency'] ) ),
		'attributes' => array( 'pa_market' => array( $config['market'] ) ), 'tags' => array( 'retail-listing', 'price-observation', strtolower( $config['currency'] ) ),
		'relations' => array( $c99_relation( 'references', $config['subject_id'], 'הרשומה מתייחסת לישות הנושא בלי להפוך אותה ל-SKU.', 'The listing references the subject entity without turning it into a SKU.', false, array( $config['source_id'] ), 'market_observation' ) ),
		'commerce_state' => 'reference_only', 'pricing_state' => 'source_price_observed', 'market_scope' => $config['market_scope'],
		'up_sell_ids' => isset( $config['up_sell_ids'] ) ? $config['up_sell_ids'] : array(),
		'cross_sell_ids' => isset( $config['cross_sell_ids'] ) ? $config['cross_sell_ids'] : array(),
		'prompt_en' => $config['prompt_en'],
	) );
};

$entities[] = $retail_listing( array(
	'id' => 'listing-rishiri-kombu-100g-20260806', 'slug' => 'rishiri-kombu-100g-20260806', 'subject_id' => 'ingredient-kombu',
	'name' => $c99_text( 'קומבו רישירי טבעי 100 גרם, תצפית 6.8.2026', 'Natural Rishiri kombu 100 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר קומבו רישירי 100 גרם', 'Rishiri kombu 100 g price observation' ),
	'price' => 1165, 'currency' => 'JPY', 'unit' => 'one 100 g pack', 'basis' => 'one direct-shop 100 g natural Rishiri kombu listing; normalized arithmetic is JPY 11,650 per kg', 'tax_status' => 'included', 'shipping_status' => 'excluded', 'comparability' => 'partially_comparable', 'availability' => 'quantity_selector_visible',
	'source_id' => 'rishiri-kombu-100g-listing-2026', 'source_url' => 'https://www.rishirikonbu.com/items/4808577', 'market' => 'japan-direct-retail', 'market_scope' => 'japan_source_market',
	'listing_attributes' => array( 'net_content' => '100 g', 'product_form' => 'natural Rishiri kombu', 'normalized_price' => 'JPY 11,650 per kg, arithmetic only', 'seller' => 'Rishiri Kombu direct shop' ),
	'statement' => $c99_text( 'נצפו 1,165 ין כולל מס לאריזת 100 גרם; משלוח בתוך יפן נפרד.', 'JPY 1,165 including tax was observed for a 100 g pack; domestic Japanese shipping was separate.' ),
	'cross_sell_ids' => array( 'ingredient-katsuobushi', 'technique-dashi-extraction' ), 'prompt_en' => 'Private editorial cutout specification for an unbranded 100 gram pack of whole Rishiri kombu sheets beside a calibrated scale card, neutral daylight, no copied packaging or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-honkarebushi-belly-200g-20260806', 'slug' => 'honkarebushi-belly-200g-20260806', 'subject_id' => 'ingredient-katsuobushi',
	'name' => $c99_text( 'בלוק Honkarebushi בטן 200 גרם, תצפית 6.8.2026', 'Honkarebushi belly block 200 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Honkarebushi 200 גרם', 'Honkarebushi 200 g price observation' ),
	'price' => 33, 'currency' => 'USD', 'unit' => 'one approximately 200 g block', 'basis' => 'one US-market listing for a whole Kagoshima honkarebushi belly block; normalized arithmetic is approximately USD 165 per kg', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'partially_comparable', 'availability' => 'in_stock',
	'source_id' => 'honkarebushi-200g-listing-2026', 'source_url' => 'https://int.japanesetaste.com/products/honkarebushi-whole-japanese-katsuobushi-block-bonito-belly-200g', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'net_content' => 'approximately 200 g', 'product_form' => 'whole honkarebushi belly block', 'origin_claim' => 'Kagoshima in listing', 'normalized_price' => 'approximately USD 165 per kg, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 33 דולר לבלוק בטן שלם במשקל כ-200 גרם, מסומן במלאי; מס ומשלוח סופי לא היו גלויים.', 'USD 33 was observed for an approximately 200 g whole belly block marked in stock; final tax and shipping were not visible.' ),
	'cross_sell_ids' => array( 'ingredient-kombu', 'technique-dashi-extraction' ), 'prompt_en' => 'Private editorial cutout specification for one unbranded whole honkarebushi belly block beside a weight marker and freshly shaved curls, neutral background, no copied packaging or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-fukumitsuya-hon-mirin-3y-720ml-20260806', 'slug' => 'fukumitsuya-hon-mirin-3y-720ml-20260806', 'subject_id' => 'ingredient-hon-mirin',
	'name' => $c99_text( 'Fukumitsuya Hon Mirin מיושן 3 שנים 720 מ״ל, תצפית 6.8.2026', 'Fukumitsuya three-year Hon Mirin 720 ml, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר מירין 3 שנים 720 מ״ל', 'three-year hon mirin 720 ml price observation' ),
	'price' => 39.99, 'currency' => 'USD', 'unit' => 'one 720 ml bottle', 'basis' => 'one US-market listing for Fukumitsuya Junmai Hon Mirin aged three years; arithmetic normalization is USD 55.54 per liter', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'in_stock',
	'source_id' => 'fukumitsuya-hon-mirin-3y-listing-2026', 'source_url' => 'https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-3-years-traditionally-aged-sweet-rice-wine-720ml', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'net_content' => '720 ml', 'age_claim' => 'three years', 'abv' => '13.5 to 14.5 percent in listing', 'normalized_price' => 'USD 55.54 per liter, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 39.99 דולר לבקבוק 720 מ״ל המסומן במלאי; מס ומשלוח סופי לא היו גלויים.', 'USD 39.99 was observed for a 720 ml bottle marked in stock; final tax and shipping were not visible.' ),
	'up_sell_ids' => array( 'listing-fukumitsuya-hon-mirin-10y-720ml-20260806' ), 'cross_sell_ids' => array( 'ingredient-kioke-shoyu' ), 'prompt_en' => 'Private editorial bottle silhouette specification for a 720 ml aged hon mirin reference, amber liquid, neutral background, no copied brand label or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-fukumitsuya-hon-mirin-10y-720ml-20260806', 'slug' => 'fukumitsuya-hon-mirin-10y-720ml-20260806', 'subject_id' => 'ingredient-hon-mirin',
	'name' => $c99_text( 'Fukumitsuya Hon Mirin מיושן 10 שנים 720 מ״ל, תצפית 6.8.2026', 'Fukumitsuya ten-year Hon Mirin 720 ml, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר מירין 10 שנים 720 מ״ל', 'ten-year hon mirin 720 ml price observation' ),
	'price' => 54.99, 'currency' => 'USD', 'unit' => 'one 720 ml bottle', 'basis' => 'one US-market listing for Fukumitsuya Junmai Hon Mirin aged ten years, compared only with the same brand and pack size', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'listed_for_sale',
	'source_id' => 'fukumitsuya-hon-mirin-10y-listing-2026', 'source_url' => 'https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-10-year-aged-sweet-rice-seasoning-720ml', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'net_content' => '720 ml', 'age_claim' => 'ten years', 'comparison_basis' => 'same maker and pack size as three-year listing', 'price_delta' => 'USD 15 above three-year listing, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 54.99 דולר לבקבוק 720 מ״ל; זהו מסלול Up-sell נקי יחסית מול גרסת 3 שנים מאותו מותג ובאותו נפח.', 'USD 54.99 was observed for a 720 ml bottle; this is a relatively clean upsell comparison with the same-brand, same-size three-year version.' ),
	'cross_sell_ids' => array( 'ingredient-kioke-shoyu' ), 'prompt_en' => 'Private editorial bottle silhouette specification for a 720 ml long-aged hon mirin reference, deep amber liquid, neutral background, no copied brand label or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-yamaroku-tsurubishio-500ml-20260806', 'slug' => 'yamaroku-tsurubishio-500ml-20260806', 'subject_id' => 'ingredient-kioke-shoyu',
	'name' => $c99_text( 'Yamaroku Tsuru-bishio 500 מ״ל, תצפית 6.8.2026', 'Yamaroku Tsuru-bishio 500 ml, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Tsuru-bishio 500 מ״ל', 'Tsuru-bishio 500 ml price observation' ),
	'price' => 1944, 'currency' => 'JPY', 'unit' => 'one 500 ml bottle', 'basis' => 'one producer price-list entry for the 500 ml Tsuru-bishio variant; arithmetic normalization is JPY 3,888 per liter', 'tax_status' => 'included', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'price_listed',
	'source_id' => 'yamaroku-product-listing-2026', 'source_url' => 'https://yama-roku.net/product', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
	'listing_attributes' => array( 'net_content' => '500 ml', 'process_claim' => 'saishikomi in kioke', 'age_claim' => 'approximately four years total in producer description', 'normalized_price' => 'JPY 3,888 per liter, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 1,944 ין כולל מס לבקבוק 500 מ״ל; מלאי ומשלוח לא הוצגו בדף המחירים.', 'JPY 1,944 including tax was observed for a 500 ml bottle; stock and shipping were not shown on the price page.' ),
	'cross_sell_ids' => array( 'ingredient-koshihikari-rice', 'ingredient-fresh-wasabi', 'ingredient-kito-yuzu' ), 'prompt_en' => 'Private editorial bottle silhouette for a 500 ml kioke shoyu reference, ruby-brown liquid, neutral background, no copied label, logo or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-fresh-japanese-wasabi-250g-20260806', 'slug' => 'fresh-japanese-wasabi-250g-20260806', 'subject_id' => 'ingredient-fresh-wasabi',
	'name' => $c99_text( 'וואסבי יפני טרי 250 גרם, תצפית 6.8.2026', 'Fresh Japanese wasabi 250 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר וואסבי יפני 250 גרם', 'fresh Japanese wasabi 250 g price observation' ),
	'price' => 62.5, 'currency' => 'GBP', 'unit' => 'one 250 g pack', 'basis' => 'one UK listing for fresh Japanese wasabi from Shizuoka; arithmetic normalization is GBP 250 per kg', 'tax_status' => 'included', 'shipping_status' => 'excluded', 'comparability' => 'non_comparable', 'availability' => 'out_of_stock',
	'source_id' => 'fresh-japanese-wasabi-250g-listing-2026', 'source_url' => 'https://www.thewasabicompany.co.uk/products/fresh-japanese-wasabi-250g', 'market' => 'united-kingdom-online-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'net_content' => '250 g', 'origin_claim' => 'Shizuoka, Japan in listing', 'stock_status' => 'out of stock at observation', 'cold_chain' => 'refrigerated handling required' ),
	'statement' => $c99_text( 'נצפו 62.50 ליש״ט כולל VAT ל-250 גרם, אך המוצר היה מחוץ למלאי; זה אינו מחיר רכישת-עכשיו.', 'GBP 62.50 including VAT was observed for 250 g, but the product was out of stock; this is not a currently purchasable price.' ),
	'cross_sell_ids' => array( 'equipment-wasabi-grater', 'ingredient-kioke-shoyu' ), 'prompt_en' => 'Private editorial cutout specification for 250 grams of fresh Japanese wasabi rhizomes in a chilled produce tray, exact natural texture, no copied packaging or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-koshihikari-uozu-2kg-20260806', 'slug' => 'koshihikari-uozu-2kg-20260806', 'subject_id' => 'ingredient-koshihikari-rice',
	'name' => $c99_text( 'אורז קושיהיקארי מאוזו, טויאמה, 2 ק״ג, תצפית 6.8.2026', 'Uozu, Toyama Koshihikari rice 2 kg, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר קושיהיקארי 2 ק״ג', 'Koshihikari rice 2 kg price observation' ),
	'price' => 16.95, 'currency' => 'EUR', 'unit' => 'one 2 kg pack', 'basis' => 'one exact Dutch online-retail listing for Koshihikari rice from Uozu, Toyama; arithmetic normalization is EUR 8.475 per kg', 'tax_status' => 'included', 'shipping_status' => 'excluded', 'comparability' => 'like_for_like', 'availability' => 'in_stock',
	'source_id' => 'dutch-wasabi-koshihikari-2kg-listing-2026', 'source_url' => 'https://www.dutchwasabi.nl/en/koshihikari-rice-from-uozu-toyama-2-kg-2/', 'market' => 'netherlands-online-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'net_content' => '2 kg', 'cultivar' => 'Koshihikari', 'origin_claim' => 'Uozu, Toyama in listing', 'stock_status' => 'in stock at observation', 'normalized_price' => 'EUR 8.475 per kg, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 16.95 אירו כולל מע״מ לאריזת 2 ק״ג מאוזו שבטויאמה, כאשר המוצר סומן במלאי; משלוח אינו כלול.', 'EUR 16.95 including VAT was observed for a 2 kg pack from Uozu, Toyama marked in stock; shipping was excluded.' ),
	'cross_sell_ids' => array( 'equipment-hangiri', 'ingredient-kioke-shoyu', 'ingredient-fresh-dutch-wasabi' ), 'prompt_en' => 'Private editorial cutout specification for one unbranded 2 kg Koshihikari rice pack beside loose translucent grains and cooked rice, neutral background, no copied packaging or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-hishiroku-dried-rice-koji-500g-20260806', 'slug' => 'hishiroku-dried-rice-koji-500g-20260806', 'subject_id' => 'ingredient-kome-koji',
	'name' => $c99_text( 'קוג׳י אורז מיובש Hishiroku 500 גרם, תצפית 6.8.2026', 'Hishiroku dried rice koji 500 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר קוג׳י אורז 500 גרם', 'dried rice koji 500 g price observation' ),
	'price' => 1050, 'currency' => 'JPY', 'unit' => 'one 500 g pack', 'basis' => 'one exact official-shop listing for dried rice koji made with SR-108; arithmetic normalization is JPY 2,100 per kg', 'tax_status' => 'included', 'shipping_status' => 'excluded', 'comparability' => 'like_for_like', 'availability' => 'add_to_cart_available',
	'source_id' => 'hishiroku-dried-rice-koji-500g-listing-2026', 'source_url' => 'https://1469.stores.jp/items/601b735cc19c453eef5d6a72', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
	'listing_attributes' => array( 'net_content' => '500 g', 'culture_claim' => 'SR-108 in listing', 'fresh_equivalent_claim' => 'about 660 g in listing', 'storage' => 'refrigerated', 'use_period' => 'about four months after purchase', 'normalized_price' => 'JPY 2,100 per kg, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 1,050 ין כולל מס לאריזת 500 גרם שניתן היה להוסיף לסל; משלוח נפרד.', 'JPY 1,050 including tax was observed for a 500 g pack that could be added to cart; shipping was separate.' ),
	'cross_sell_ids' => array( 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-kioke-shoyu' ), 'prompt_en' => 'Private editorial cutout specification for one unbranded 500 g pack of dried rice koji beside visible white-coated grains, neutral background, no copied packaging or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-hishiroku-chouhaku-kin-20g-20260806', 'slug' => 'hishiroku-chouhaku-kin-20g-20260806', 'subject_id' => 'ingredient-koji-starter-culture',
	'name' => $c99_text( 'תרבית Chouhaku-kin אבקתית 20 גרם, תצפית 6.8.2026', 'Chouhaku-kin powdered koji starter 20 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר תרבית קוג׳י 20 גרם', 'koji starter 20 g price observation' ),
	'price' => 630, 'currency' => 'JPY', 'unit' => 'one 20 g sachet', 'basis' => 'one exact official-shop listing for powdered Chouhaku-kin starter; arithmetic normalization is JPY 31,500 per kg and is not a culinary dose comparison', 'tax_status' => 'included', 'shipping_status' => 'excluded', 'comparability' => 'like_for_like', 'availability' => 'add_to_cart_available',
	'source_id' => 'hishiroku-chouhaku-kin-20g-listing-2026', 'source_url' => 'https://1469.stores.jp/items/5efed301ec8fd331f922d017', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
	'listing_attributes' => array( 'net_content' => '20 g', 'form' => 'powdered white-spore starter', 'maker_use' => 'shio-koji and miso', 'substrate_quantity_claim' => '20 g for 15 kg before washing', 'storage' => 'refrigerated', 'use_period' => 'six months after purchase' ),
	'statement' => $c99_text( 'נצפו 630 ין כולל מס לשקית 20 גרם שניתן היה להוסיף לסל; משלוח נפרד.', 'JPY 630 including tax was observed for a 20 g sachet that could be added to cart; shipping was separate.' ),
	'cross_sell_ids' => array( 'ingredient-kome-koji', 'ingredient-koshihikari-rice' ), 'prompt_en' => 'Private editorial cutout specification for one plain 20 g sachet of powdered koji starter beside a small dish of pale powder, no bottle, no copied packaging, logos or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-dutch-wasabi-50-60g-20260806', 'slug' => 'dutch-wasabi-50-60g-20260806', 'subject_id' => 'ingredient-fresh-dutch-wasabi',
	'name' => $c99_text( 'וואסבי טרי בגידול הולנדי 50 עד 60 גרם, תצפית 6.8.2026', 'Dutch-grown fresh wasabi 50 to 60 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר וואסבי טרי 50 עד 60 גרם', 'fresh wasabi 50 to 60 g price observation' ),
	'price' => 17.51, 'currency' => 'EUR', 'unit' => 'one 50 to 60 g piece', 'basis' => 'one exact grower-shop listing for a fresh rhizome with a variable 50 to 60 g piece weight; arithmetic midpoint normalization is about EUR 318.36 per kg', 'tax_status' => 'included', 'shipping_status' => 'excluded', 'comparability' => 'like_for_like', 'availability' => 'in_stock',
	'source_id' => 'dutch-wasabi-fresh-rhizome-50-60g-listing-2026', 'source_url' => 'https://www.dutchwasabi.nl/en/fresh-wasabirhi-hem-1-piece-50-60-grams-2-4-servings/', 'market' => 'netherlands-grower-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'net_content' => 'one 50 to 60 g piece', 'origin_claim' => 'own greenhouses in the Netherlands', 'cultivation_period_claim' => '18 to 24 months', 'harvest_claim' => 'hand harvested', 'stock_status' => 'in stock at observation', 'cold_chain' => 'refrigerated handling required' ),
	'statement' => $c99_text( 'נצפו 17.51 אירו כולל מע״מ ליחידה במשקל 50 עד 60 גרם שסומנה במלאי; משלוח אינו כלול.', 'EUR 17.51 including VAT was observed for one 50 to 60 g piece marked in stock; shipping was excluded.' ),
	'up_sell_ids' => array( 'ingredient-fresh-wasabi' ), 'cross_sell_ids' => array( 'equipment-wasabi-grater', 'ingredient-kioke-shoyu' ), 'prompt_en' => 'Private editorial cutout specification for one Dutch-grown 50 to 60 g fresh wasabi rhizome beside a weight marker, natural texture, no copied packaging or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-kito-yuzu-juice-100ml-20260806', 'slug' => 'kito-yuzu-juice-100ml-20260806', 'subject_id' => 'ingredient-kito-yuzu',
	'name' => $c99_text( 'מיץ Kito Yuzu ראשון 100 מ״ל, תצפית 6.8.2026', 'Kito Yuzu first juice 100 ml, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר מיץ קיטו יוזו 100 מ״ל', 'Kito Yuzu juice 100 ml price observation' ),
	'price' => 734, 'currency' => 'JPY', 'unit' => 'one 100 ml bottle', 'basis' => 'one producer-shop 100 ml 100 percent juice listing; arithmetic normalization is JPY 7,340 per liter', 'tax_status' => 'included', 'shipping_status' => 'unknown', 'comparability' => 'partially_comparable', 'availability' => 'add_to_cart_available',
	'source_id' => 'kito-yuzu-juice-100ml-listing-2026', 'source_url' => 'https://shop.ogonnomura.jp/view/item/000000000364', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
	'listing_attributes' => array( 'net_content' => '100 ml', 'product_form' => '100 percent yuzu juice in listing', 'shelf_life_claim' => '12 months in listing', 'normalized_price' => 'JPY 7,340 per liter, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 734 ין כולל מס לבקבוק 100 מ״ל שניתן היה להוסיף לעגלה; משלוח לא היה גלוי.', 'JPY 734 including tax was observed for a 100 ml bottle that could be added to cart; shipping was not visible.' ),
	'up_sell_ids' => array( 'listing-kito-yuzu-juice-720ml-20260806' ), 'cross_sell_ids' => array( 'ingredient-kioke-shoyu', 'ingredient-hon-mirin' ), 'prompt_en' => 'Private editorial bottle silhouette specification for a 100 ml pure yuzu juice reference, pale yellow liquid, neutral background, no copied label, logo or GI seal.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-kito-yuzu-juice-720ml-20260806', 'slug' => 'kito-yuzu-juice-720ml-20260806', 'subject_id' => 'ingredient-kito-yuzu',
	'name' => $c99_text( 'מיץ Kito Yuzu ראשון 720 מ״ל, תצפית 6.8.2026', 'Kito Yuzu first juice 720 ml, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר מיץ קיטו יוזו 720 מ״ל', 'Kito Yuzu juice 720 ml price observation' ),
	'price' => 3780, 'currency' => 'JPY', 'unit' => 'one 720 ml bottle', 'basis' => 'one producer-shop collection entry for a 720 ml juice format; arithmetic normalization is JPY 5,250 per liter', 'tax_status' => 'included', 'shipping_status' => 'unknown', 'comparability' => 'partially_comparable', 'availability' => 'price_listed',
	'source_id' => 'kito-yuzu-juice-720ml-listing-2026', 'source_url' => 'https://shop.ogonnomura.jp/view/item/000000000199?category_page_id=ichiban', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
	'listing_attributes' => array( 'net_content' => '720 ml', 'product_form' => 'yuzu juice collection entry', 'normalized_price' => 'JPY 5,250 per liter, arithmetic only', 'unit_discount' => 'approximately 28.5 percent below 100 ml unit price, arithmetic only' ),
	'statement' => $c99_text( 'נצפו 3,780 ין כולל מס ל-720 מ״ל; המחיר המנורמל נמוך בכ-28.5 אחוז לעומת תצפית ה-100 מ״ל, אך יש לאמת SKU ותנאים לפני הצעה מקצועית.', 'JPY 3,780 including tax was observed for 720 ml; normalized price is about 28.5 percent below the 100 ml observation, but SKU and terms require verification before a professional offer.' ),
	'cross_sell_ids' => array( 'ingredient-kioke-shoyu', 'ingredient-hon-mirin' ), 'prompt_en' => 'Private editorial bottle silhouette specification for a 720 ml professional yuzu juice reference, pale yellow liquid, neutral background, no copied label, logo or GI seal.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-umezawa-hangiri-36cm-20260806', 'slug' => 'umezawa-hangiri-36cm-20260806', 'subject_id' => 'equipment-hangiri',
	'name' => $c99_text( 'Umezawa Hangiri סווארה 36 ס״מ, תצפית 6.8.2026', 'Umezawa sawara hangiri 36 cm, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר האנגירי 36 ס״מ', '36 cm hangiri price observation' ),
	'price' => 129, 'currency' => 'USD', 'unit' => 'one 36 cm bowl without lid', 'basis' => 'one US-market exact listing for a 36 cm Umezawa sawara cypress hangiri without lid', 'tax_status' => 'unknown', 'shipping_status' => 'excluded', 'comparability' => 'like_for_like', 'availability' => 'in_stock',
	'source_id' => 'umezawa-hangiri-36cm-listing-2026', 'source_url' => 'https://japanesetaste.com/products/umezawa-sawara-cypress-hangiri-wooden-sushi-oke-bowl-36cm', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'diameter' => '36 cm', 'wood' => 'sawara cypress in listing', 'hoops' => 'copper in listing', 'included_accessories' => 'lid not included' ),
	'statement' => $c99_text( 'נצפו 129 דולר לקערת 36 ס״מ המסומנת במלאי, ללא מכסה; מס ומשלוח סופי לא היו גלויים.', 'USD 129 was observed for an in-stock 36 cm bowl without lid; final tax and shipping were not visible.' ),
	'cross_sell_ids' => array( 'ingredient-koshihikari-rice', 'preparation-sushi-shari' ), 'prompt_en' => 'Private editorial cutout specification for one unbranded 36 cm sawara hangiri without lid, exact wood and copper hoop detail, neutral background, no copied packaging or text.',
) );

$entities[] = $retail_listing( array(
	'id' => 'listing-hagane-zame-large-20260806', 'slug' => 'hagane-zame-large-20260806', 'subject_id' => 'equipment-wasabi-grater',
	'name' => $c99_text( 'Hagane-zame Large מפלדת אל-חלד, תצפית 6.8.2026', 'Hagane-zame Large stainless grater, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Hagane-zame Large', 'Hagane-zame Large price observation' ),
	'price' => 135, 'currency' => 'GBP', 'unit' => 'one large 26 by 11 cm grater', 'basis' => 'one UK exact variant listing for the large stainless steel Hagane-zame grater', 'tax_status' => 'included', 'shipping_status' => 'excluded', 'comparability' => 'like_for_like', 'availability' => 'last_few_remaining',
	'source_id' => 'hagane-zame-large-listing-2026', 'source_url' => 'https://www.thewasabicompany.co.uk/products/hagane-zame-wasabi-grater?variant=49446664601881', 'market' => 'united-kingdom-online-retail', 'market_scope' => 'market_specific',
	'listing_attributes' => array( 'variant' => 'large', 'dimensions' => '26 by 11 cm', 'material' => 'stainless steel in listing', 'stock_text' => 'last few remaining' ),
	'statement' => $c99_text( 'נצפו 135 ליש״ט כולל VAT לווריאנט Large בגודל 26 על 11 ס״מ, עם סימון שנותרו יחידות מעטות; משלוח בקופה.', 'GBP 135 including VAT was observed for the Large 26 by 11 cm variant marked last few remaining; shipping was calculated at checkout.' ),
	'cross_sell_ids' => array( 'ingredient-fresh-wasabi' ), 'prompt_en' => 'Private editorial cutout specification for one unbranded large stainless steel wasabi grater, 26 by 11 cm, neutral background, no copied packaging, logos or text.',
) );

/* A dated, non-public multi-listing price observation. */
$yanagiba_measurement = array(
	'kind'            => 'range',
	'low'             => 12000,
	'high'            => 135000,
	'value'           => null,
	'currency'        => 'JPY',
	'unit'            => 'one listed knife at displayed starting price',
	'basis'           => 'two exact official retailer listings chosen as visible low and high examples; models, materials, handles and blade lengths are not like-for-like',
	'tax_status'      => 'excluded',
	'shipping_status' => 'excluded',
	'observed_at'     => '2026-08-06T00:34:50+03:00',
	'source_url'      => 'https://tsubaya.jp/en/collections/yanagiba',
	'sample_size'     => 2,
	'comparability'   => 'non_comparable',
	'capture_method'  => 'live_retail_page_manual_review_no_snapshot',
	'snapshot_digest' => '',
	'line_items'      => array(
		array(
			'name'         => 'Molybdenum Steel Yanagiba, no bolster',
			'price'        => 12000,
			'currency'     => 'JPY',
			'tax_status'   => 'excluded',
			'availability' => 'listed_for_sale',
			'source_url'   => 'https://tsubaya.jp/en/products/ms-yanagiba-tsubanashi',
			'attributes'   => array(
				'blade_size_options' => '20 cm and 24 cm',
				'steel_type' => 'stainless steel',
				'blade_structure' => 'monosteel',
				'blade_grind' => 'single bevel',
			),
		),
		array(
			'name'         => 'Blue 1 Carbon Steel Suminagashi Yanagiba Karin Burl Octagonal Handle',
			'price'        => 135000,
			'currency'     => 'JPY',
			'tax_status'   => 'excluded',
			'availability' => 'listed_for_sale',
			'source_url'   => 'https://tsubaya.jp/en/products/blue1-suminagashi-yanagiba-karinkobu',
			'attributes'   => array(
				'blade_size_options' => '27 cm and 30 cm',
				'steel_type' => 'carbon steel, Blue 1',
				'blade_structure' => 'laminated kasumi or awase',
				'blade_grind' => 'single bevel',
			),
		),
	),
);
$entities[] = $c99_entity( array(
	'id' => 'market-observation-tsubaya-yanagiba-2026-08-06', 'type' => 'market_observation', 'slug' => 'tsubaya-yanagiba-prices-2026-08-06',
	'name' => $c99_text( 'תצפית מחירי יאנאגיבה ב-Tsubaya, 6 באוגוסט 2026', 'Tsubaya yanagiba price observation, August 6 2026' ),
	'summary' => $c99_text( 'תצפית מתוארכת על מחירי פתיחה גלויים בקטגוריית יאנאגיבה של חנות אחת. הטווח אינו ממוצע שוק, אינו משווה מוצרים זהים ואינו כולל המרת מטבע, מס או משלוח לישראל.', 'A dated observation of visible starting prices in one retailer yanagiba category. The range is not a market average, does not compare like-for-like products and excludes currency conversion, tax and shipping to Israel.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'research_draft', 'next_review_at' => '2026-09-05',
	'seo_group' => 'observations', 'primary_intent' => $c99_text( 'תיעוד פנימי של מחיר יאנאגיבה', 'Internal yanagiba price evidence' ), 'primary_keyword' => $c99_text( 'תצפית מחיר יאנאגיבה', 'yanagiba price observation' ),
	'secondary_keywords' => array( 'he' => array( 'מחירי סכינים יפניות' ), 'en' => array( 'Japanese knife price sample' ) ), 'schema_type' => 'Dataset',
	'facts' => array( $c99_fact( 'fact-tsubaya-yanagiba-price-range-20260806', 'economic', 'בשתי רשומות מוצר מדויקות נצפו 12,000 ין ליאנאגיבה מוליבדן ללא בולסטר ו-135,000 ין ליאנאגיבה Blue 1 Suminagashi. המחירים אינם כוללים מס או משלוח והדגמים אינם בני השוואה ישירה.', 'Two exact product listings showed JPY 12,000 for a no-bolster molybdenum yanagiba and JPY 135,000 for a Blue 1 suminagashi yanagiba. Prices exclude tax and shipping, and the models are not directly comparable.', 'market_observation', 'market_snapshot', array( 'tsubaya-yanagiba-2026', 'tsubaya-molybdenum-no-bolster-2026', 'tsubaya-blue1-suminagashi-2026' ), false, $yanagiba_measurement, '2026-08-06T00:34:50+03:00' ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'not_applicable', 'התצפית אינה בדיקת ביצועי פלדה.', 'The observation is not a steel performance test.' ),
		'cultural' => $c99_profile( 'not_applicable', 'תצפית מחיר אינה ראיה למסורת ייצור.', 'A price observation is not evidence of craft tradition.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'החנות והמוצר נשמרים כישויות נפרדות.', 'The shop and product are separate entities.' ),
		'economic' => $c99_profile( 'source_backed', 'הטווח הוא צילום מצב של מחירי פתיחה בדף קמעונאי אחד.', 'The range is a snapshot of starting prices on one retail page.', array( 'fact-tsubaya-yanagiba-price-range-20260806' ) ),
		'structural' => $c99_profile( 'pending_evidence', 'התצפית מקשרת נושא, מוכר, מטבע, תאריך ומקור.', 'The observation links subject, seller, currency, date and source.' ),
	) ),
	'categories' => array( 'market-intelligence', 'equipment', 'knives', 'price-observations' ), 'attributes' => array( 'pa_market' => array( 'japan-online-retail' ), 'pa_quality_grade' => array( 'mixed-non-comparable-sample' ) ), 'tags' => array( 'price-observation', 'yanagiba', 'jpy', 'tsubaya' ),
	'relations' => array(
		$c99_relation( 'observed_at', 'equipment-shop-tsubaya', 'המחירים נצפו בדף החנות הרשמי.', 'Prices were observed on the official shop page.', false ),
		$c99_relation( 'references', 'equipment-yanagiba', 'נושא ההשוואה הוא משפחת יאנאגיבה.', 'The comparison subject is the yanagiba family.', false ),
	),
	'prompt_en' => 'Neutral editorial price-comparison graphic for a private review dashboard, three unbranded yanagiba silhouettes at entry, mid and high specification levels, clear empty zones for separately typeset verified prices, no retailer logo, no embedded text.',
	'asset_state' => 'spec_ready', 'rights_method' => 'generated_concept_with_human_review',
) );

$c99_foundations_lab_module = require __DIR__ . '/culinary-science/collections/japanese-foundations-lab.php';
$entities[]                  = $c99_foundations_lab_module['entity'];
$collections                 = array( $c99_foundations_lab_module['collection'] );

/*
 * SEO architecture is computed from one explicit parent map. The same chain
 * drives canonical paths, breadcrumbs, expected child coverage and the public
 * internal-link projection, preventing menu, breadcrumb and schema drift.
 */
$parent_overrides = array(
	'cuisine-japanese-washoku' => 'museum-culinary-science',
	'hub-global-culinary-institutions' => 'museum-culinary-science',
	'dish-edomae-nigiri' => 'hub-japanese-dishes',
	'preparation-sushi-shari' => 'hub-japanese-techniques',
	'preparation-ichiban-dashi' => 'hub-japanese-techniques',
	'ingredient-kioke-shoyu' => 'hub-japanese-ingredients',
	'ingredient-kombu' => 'hub-japanese-ingredients',
	'ingredient-katsuobushi' => 'hub-japanese-ingredients',
	'ingredient-shoyu-koji' => 'hub-japanese-ingredients',
	'ingredient-kome-koji' => 'hub-japanese-ingredients',
	'ingredient-koji-starter-culture' => 'hub-japanese-ingredients',
	'ingredient-fresh-wasabi' => 'hub-japanese-ingredients',
	'ingredient-fresh-dutch-wasabi' => 'ingredient-fresh-wasabi',
	'ingredient-kito-yuzu' => 'hub-japanese-ingredients',
	'ingredient-yakinori' => 'hub-japanese-ingredients',
	'ingredient-hon-mirin' => 'hub-japanese-ingredients',
	'ingredient-koshihikari-rice' => 'hub-japanese-ingredients',
	'molecule-l-glutamate' => 'guide-umami-synergy',
	'molecule-inosine-monophosphate' => 'guide-umami-synergy',
	'molecule-allyl-isothiocyanate' => 'guide-wasabi-aitc',
	'reaction-koji-enzymatic-hydrolysis' => 'guide-koji-hydrolysis',
	'equipment-hangiri' => 'hub-japanese-equipment',
	'equipment-yanagiba' => 'hub-japanese-equipment',
	'equipment-kioke' => 'hub-japanese-equipment',
	'equipment-wasabi-grater' => 'hub-japanese-equipment',
	'standard-jas-shoyu-1703' => 'hub-japanese-food-science',
	'material-yanagiba-white2' => 'comparison-yanagiba-steels',
	'material-yanagiba-blue1-suminagashi' => 'comparison-yanagiba-steels',
	'market-toyosu' => 'guide-japanese-markets',
	'market-tsukiji-outer' => 'guide-japanese-markets',
	'market-kappabashi-dougu' => 'guide-japanese-markets',
	'equipment-shop-tsubaya' => 'market-kappabashi-dougu',
	'producer-yamaroku-shoyu' => 'hub-japanese-sourcing',
	'restaurant-myojaku' => 'hub-japanese-restaurants',
	'restaurant-nishiazabu-sushi-shin' => 'hub-japanese-restaurants',
	'institution-japanese-culinary-academy' => 'hub-global-culinary-institutions',
	'institution-danon' => 'hub-global-culinary-institutions',
	'institution-bishulim' => 'hub-global-culinary-institutions',
	'institution-culinary-institute-america' => 'hub-global-culinary-institutions',
	'institution-basque-culinary-center' => 'hub-global-culinary-institutions',
	'market-observation-tsubaya-yanagiba-2026-08-06' => 'equipment-shop-tsubaya',
);
foreach ( $entities as &$entity ) {
	if ( in_array( $entity['id'], array( 'restaurant-myojaku', 'restaurant-nishiazabu-sushi-shin' ), true ) ) {
		$entity['relations'][] = $c99_relation(
			'recognized_in',
			'guide-edition-michelin-tokyo-2026',
			'ההכרה נשמרת מול מהדורת 2026 המתוארכת, ולא כתכונה קבועה של המסעדה.',
			'Recognition is stored against the dated 2026 edition rather than as a permanent restaurant attribute.',
			false,
			array( 'michelin-tokyo-2026' ),
			'third_party_guide'
		);
	}
}
unset( $entity );
foreach ( $entities as &$entity ) {
	if ( isset( $parent_overrides[ $entity['id'] ] ) ) {
		$entity['parent_id'] = $parent_overrides[ $entity['id'] ];
	}
	foreach ( $entity['relations'] as $relation_offset => &$relation ) {
		$relation['id'] = 'edge-' . $entity['id'] . '-' . $relation['type'] . '-' . ( $relation_offset + 1 );
	}
	unset( $relation );
}
unset( $entity );

$entity_offsets = array();
$children_by_parent = array();
foreach ( $entities as $entity_offset => $entity ) {
	$entity_offsets[ $entity['id'] ] = $entity_offset;
	if ( '' !== $entity['parent_id'] ) {
		if ( ! isset( $children_by_parent[ $entity['parent_id'] ] ) ) {
			$children_by_parent[ $entity['parent_id'] ] = array();
		}
		$children_by_parent[ $entity['parent_id'] ][] = $entity['id'];
	}
}

$clusters_by_root = array(
	'museum-culinary-science' => 'cluster-culinary-science-museum',
	'cuisine-japanese-washoku' => 'cluster-japanese-washoku',
	'hub-global-culinary-institutions' => 'cluster-global-culinary-institutions',
);
$profile_types = array( 'culinary_institution', 'restaurant', 'market', 'equipment_shop', 'producer', 'supplier' );
$reference_types = array( 'molecule', 'reaction', 'market_observation', 'retail_listing', 'standard', 'geographical_indication', 'guide_edition', 'compliance_rule' );

foreach ( $entities as &$entity ) {
	$chain = array();
	$cursor = $entity['id'];
	$guard = 0;
	while ( '' !== $cursor && isset( $entity_offsets[ $cursor ] ) && $guard < 100 ) {
		array_unshift( $chain, $cursor );
		$cursor = $entities[ $entity_offsets[ $cursor ] ]['parent_id'];
		$guard++;
	}
	$root_id = $chain[0];
	$cluster_hub_id = $root_id;
	foreach ( $chain as $chain_id ) {
		if ( isset( $clusters_by_root[ $chain_id ] ) ) {
			$cluster_hub_id = $chain_id;
		}
	}
	$entity['seo']['hub_entity_id'] = $cluster_hub_id;
	$entity['seo']['cluster_id'] = $clusters_by_root[ $cluster_hub_id ];
	$entity['seo']['breadcrumb_entity_ids'] = $chain;
	$entity['seo']['expected_child_ids'] = isset( $children_by_parent[ $entity['id'] ] ) ? $children_by_parent[ $entity['id'] ] : array();

	if ( $entity['id'] === $cluster_hub_id ) {
		$entity['seo']['page_role'] = 'pillar';
	} elseif ( 'topic_hub' === $entity['type'] ) {
		$entity['seo']['page_role'] = 'category';
	} elseif ( in_array( $entity['type'], $profile_types, true ) ) {
		$entity['seo']['page_role'] = 'profile';
	} elseif ( in_array( $entity['type'], $reference_types, true ) ) {
		$entity['seo']['page_role'] = 'reference';
	} else {
		$entity['seo']['page_role'] = 'spoke';
	}

	$path_slugs = array();
	foreach ( $chain as $chain_id ) {
		if ( 'museum-culinary-science' === $chain_id ) {
			continue;
		}
		$path_slugs[] = $entities[ $entity_offsets[ $chain_id ] ]['slug'];
	}
	$relative_path = empty( $path_slugs ) ? '' : implode( '/', $path_slugs ) . '/';
	$entity['seo']['canonical_path'] = $c99_text( '/museum/' . $relative_path, '/en/museum/' . $relative_path );

	$semantic_ids = array_merge(
		'' !== $entity['parent_id'] ? array( $entity['parent_id'] ) : array(),
		$entity['seo']['expected_child_ids'],
		array_column( $entity['relations'], 'target_id' ),
		$entity['commerce']['cross_sell_ids'],
		$entity['commerce']['up_sell_ids']
	);
	$semantic_ids = array_values( array_unique( array_filter( $semantic_ids, static function ( $semantic_id ) use ( $entity ) {
		return $semantic_id !== $entity['id'];
	} ) ) );
	$entity['seo']['semantic_entity_ids'] = $semantic_ids;
}
unset( $entity );

/*
 * Search ownership is separate from ontology membership. Reusable ingredients
 * keep type-first owners, while molecules, material specifications and market
 * profiles can remain first-class entities rendered as sections of one owner.
 */
$canonical_overrides = array(
	'museum-culinary-science' => $c99_text( '/museum/', '/en/museum/' ),
	'cuisine-japanese-washoku' => $c99_text( '/museum/japanese-culinary-science/', '/en/museum/japanese-culinary-science/' ),
	'hub-japanese-foundations-lab' => $c99_text( '/museum/japanese-culinary-science/foundations/', '/en/museum/japanese-culinary-science/foundations/' ),
	'hub-global-culinary-institutions' => $c99_text( '/museum/global-culinary-institutions/', '/en/museum/global-culinary-institutions/' ),
	'tradition-washoku' => $c99_text( '/traditions/washoku/', '/en/traditions/washoku/' ),
	'dish-edomae-nigiri' => $c99_text( '/dishes/edomae-nigiri/', '/en/dishes/edomae-nigiri/' ),
	'preparation-sushi-shari' => $c99_text( '/knowledge/sushi-shari/', '/en/knowledge/sushi-shari/' ),
	'preparation-ichiban-dashi' => $c99_text( '/knowledge/ichiban-dashi/', '/en/knowledge/ichiban-dashi/' ),
	'ingredient-kioke-shoyu' => $c99_text( '/ingredients/kioke-shoyu/', '/en/ingredients/kioke-shoyu/' ),
	'ingredient-kome-koji' => $c99_text( '/ingredients/kome-koji/', '/en/ingredients/kome-koji/' ),
	'ingredient-koji-starter-culture' => $c99_text( '/ingredients/koji-starter-culture/', '/en/ingredients/koji-starter-culture/' ),
	'ingredient-shoyu-koji' => $c99_text( '/ingredients/shoyu-koji/', '/en/ingredients/shoyu-koji/' ),
	'ingredient-fresh-wasabi' => $c99_text( '/ingredients/fresh-wasabi-rhizome/', '/en/ingredients/fresh-wasabi-rhizome/' ),
	'ingredient-fresh-dutch-wasabi' => $c99_text( '/ingredients/dutch-grown-fresh-wasabi/', '/en/ingredients/dutch-grown-fresh-wasabi/' ),
	'ingredient-kito-yuzu' => $c99_text( '/ingredients/kito-yuzu/', '/en/ingredients/kito-yuzu/' ),
	'ingredient-yakinori' => $c99_text( '/ingredients/premium-yakinori/', '/en/ingredients/premium-yakinori/' ),
	'ingredient-hon-mirin' => $c99_text( '/ingredients/hon-mirin/', '/en/ingredients/hon-mirin/' ),
	'ingredient-koshihikari-rice' => $c99_text( '/ingredients/koshihikari-rice/', '/en/ingredients/koshihikari-rice/' ),
	'ingredient-kombu' => $c99_text( '/ingredients/kombu/', '/en/ingredients/kombu/' ),
	'ingredient-katsuobushi' => $c99_text( '/ingredients/katsuobushi/', '/en/ingredients/katsuobushi/' ),
	'guide-umami-synergy' => $c99_text( '/knowledge/umami-synergy-glutamate-imp/', '/en/knowledge/umami-synergy-glutamate-imp/' ),
	'guide-wasabi-aitc' => $c99_text( '/knowledge/wasabi-aitc-pungency/', '/en/knowledge/wasabi-aitc-pungency/' ),
	'guide-koji-hydrolysis' => $c99_text( '/knowledge/koji-enzymatic-hydrolysis/', '/en/knowledge/koji-enzymatic-hydrolysis/' ),
	'equipment-hangiri' => $c99_text( '/knowledge/hangiri-guide/', '/en/knowledge/hangiri-guide/' ),
	'equipment-yanagiba' => $c99_text( '/knowledge/yanagiba-guide/', '/en/knowledge/yanagiba-guide/' ),
	'equipment-wasabi-grater' => $c99_text( '/knowledge/wasabi-grater-guide/', '/en/knowledge/wasabi-grater-guide/' ),
	'equipment-kioke' => $c99_text( '/knowledge/kioke-barrel-guide/', '/en/knowledge/kioke-barrel-guide/' ),
	'comparison-yanagiba-steels' => $c99_text( '/knowledge/yanagiba-white-2-vs-blue-1/', '/en/knowledge/yanagiba-white-2-vs-blue-1/' ),
	'guide-japanese-markets' => $c99_text( '/knowledge/japanese-culinary-markets/', '/en/knowledge/japanese-culinary-markets/' ),
	'standard-jas-shoyu-1703' => $c99_text( '/knowledge/jas-1703-shoyu-standard/', '/en/knowledge/jas-1703-shoyu-standard/' ),
);

$section_owner_map = array(
	'hub-japanese-dishes' => 'cuisine-japanese-washoku',
	'hub-japanese-techniques' => 'cuisine-japanese-washoku',
	'hub-japanese-ingredients' => 'cuisine-japanese-washoku',
	'hub-japanese-food-science' => 'cuisine-japanese-washoku',
	'hub-japanese-equipment' => 'cuisine-japanese-washoku',
	'hub-japanese-sourcing' => 'cuisine-japanese-washoku',
	'hub-japanese-restaurants' => 'cuisine-japanese-washoku',
	'technique-dashi-extraction' => 'preparation-ichiban-dashi',
	'molecule-l-glutamate' => 'guide-umami-synergy',
	'molecule-inosine-monophosphate' => 'guide-umami-synergy',
	'molecule-allyl-isothiocyanate' => 'guide-wasabi-aitc',
	'reaction-koji-enzymatic-hydrolysis' => 'guide-koji-hydrolysis',
	'material-yanagiba-white2' => 'comparison-yanagiba-steels',
	'material-yanagiba-blue1-suminagashi' => 'comparison-yanagiba-steels',
	'geographical-indication-kito-yuzu' => 'ingredient-kito-yuzu',
	'market-toyosu' => 'guide-japanese-markets',
	'market-tsukiji-outer' => 'guide-japanese-markets',
	'market-kappabashi-dougu' => 'guide-japanese-markets',
	'equipment-shop-tsubaya' => 'guide-japanese-markets',
);

$private_route_types = array( 'retail_listing', 'market_observation', 'guide_edition', 'restaurant', 'culinary_institution', 'producer', 'supplier' );
foreach ( $entities as &$entity ) {
	if ( isset( $canonical_overrides[ $entity['id'] ] ) ) {
		$entity['seo']['canonical_path'] = $canonical_overrides[ $entity['id'] ];
	}
	$entity['seo']['route_mode'] = in_array( $entity['type'], $private_route_types, true ) ? 'private' : 'standalone';
	$entity['seo']['owner_entity_id'] = $entity['id'];
	$entity['seo']['section_id'] = '';
	if ( isset( $section_owner_map[ $entity['id'] ] ) ) {
		$entity['seo']['route_mode'] = 'section';
		$entity['seo']['owner_entity_id'] = $section_owner_map[ $entity['id'] ];
		$entity['seo']['section_id'] = $entity['slug'];
	}

	$owner_path = isset( $canonical_overrides[ $entity['seo']['owner_entity_id'] ] )
		? $canonical_overrides[ $entity['seo']['owner_entity_id'] ]['he']
		: $entity['seo']['canonical_path']['he'];
	if ( 0 === strpos( $owner_path, '/dishes/' ) ) {
		$entity['seo']['protected_owner_ids'] = array( 'dishes-hub-owner', 'menu-dish-owner' );
	} elseif ( 0 === strpos( $owner_path, '/ingredients/' ) ) {
		$entity['seo']['protected_owner_ids'] = array( 'ingredients-hub-owner', 'store-sku-owner' );
	} elseif ( 0 === strpos( $owner_path, '/knowledge/' ) ) {
		$entity['seo']['protected_owner_ids'] = array( 'knowledge-hub-owner', 'store-sku-owner' );
	} elseif ( 0 === strpos( $owner_path, '/traditions/' ) ) {
		$entity['seo']['protected_owner_ids'] = array( 'traditions-hub-owner' );
	} else {
		$entity['seo']['protected_owner_ids'] = array( 'museum-root-owner' );
	}
}
unset( $entity );

$breadcrumb_section = static function ( $path ) use ( $c99_text ) {
	if ( 0 === strpos( $path, '/traditions/' ) ) {
		return array( 'traditions', $c99_text( 'מסורות', 'Traditions' ), $c99_text( '/traditions/', '/en/traditions/' ) );
	}
	if ( 0 === strpos( $path, '/dishes/' ) ) {
		return array( 'dishes', $c99_text( 'מנות', 'Dishes' ), $c99_text( '/dishes/', '/en/dishes/' ) );
	}
	if ( 0 === strpos( $path, '/ingredients/' ) ) {
		return array( 'ingredients', $c99_text( 'חומרי גלם', 'Ingredients' ), $c99_text( '/ingredients/', '/en/ingredients/' ) );
	}
	if ( 0 === strpos( $path, '/knowledge/' ) ) {
		return array( 'knowledge', $c99_text( 'מרכז הידע', 'Knowledge Centre' ), $c99_text( '/knowledge/', '/en/knowledge/' ) );
	}
	return array( 'museum', $c99_text( 'מוזיאון המדע של הקולינריה', 'Culinary Science Museum' ), $c99_text( '/museum/', '/en/museum/' ) );
};

foreach ( $entities as &$entity ) {
	if ( 'section' === $entity['seo']['route_mode'] ) {
		$owner = $entities[ $entity_offsets[ $entity['seo']['owner_entity_id'] ] ];
		$entity['seo']['canonical_path'] = $owner['seo']['canonical_path'];
		continue;
	}
	$section = $breadcrumb_section( $entity['seo']['canonical_path']['he'] );
	$breadcrumbs = array(
		array( 'key' => 'home', 'label' => $c99_text( 'בית', 'Home' ), 'path' => $c99_text( '/', '/en/' ) ),
	);
	if ( $entity['seo']['canonical_path']['he'] !== $section[2]['he'] ) {
		$breadcrumbs[] = array( 'key' => $section[0], 'label' => $section[1], 'path' => $section[2] );
	}
	if ( '' !== $entity['parent_id'] && isset( $entity_offsets[ $entity['parent_id'] ] ) ) {
		$parent         = $entities[ $entity_offsets[ $entity['parent_id'] ] ];
		$parent_path_he = $parent['seo']['canonical_path']['he'];
		$section_root   = rtrim( $section[2]['he'], '/' ) . '/';
		if ( 'standalone' === $parent['seo']['route_mode']
			&& $parent_path_he !== $section[2]['he']
			&& $parent_path_he !== $entity['seo']['canonical_path']['he']
			&& 0 === strpos( $parent_path_he, $section_root ) ) {
			$breadcrumbs[] = array(
				'key'   => 'parent-' . $parent['id'],
				'label' => $parent['name'],
				'path'  => $parent['seo']['canonical_path'],
			);
		}
	}
	$breadcrumbs[] = array( 'key' => 'current-' . $entity['id'], 'label' => $entity['name'], 'path' => $entity['seo']['canonical_path'] );
	$entity['seo']['visible_breadcrumbs'] = $breadcrumbs;
}
unset( $entity );

foreach ( $entities as &$entity ) {
	if ( 'section' !== $entity['seo']['route_mode'] ) {
		continue;
	}
	$owner = $entities[ $entity_offsets[ $entity['seo']['owner_entity_id'] ] ];
	$entity['seo']['canonical_path'] = $owner['seo']['canonical_path'];
	$entity['seo']['visible_breadcrumbs'] = $owner['seo']['visible_breadcrumbs'];
	$entity['seo']['protected_owner_ids'] = $owner['seo']['protected_owner_ids'];
}
unset( $entity );

foreach ( $entities as &$entity ) {
	$link_plan = array();
	if ( '' !== $entity['parent_id'] ) {
		$parent = $entities[ $entity_offsets[ $entity['parent_id'] ] ];
		$link_plan[] = array(
			'target_id' => $parent['id'], 'purpose' => 'parent-context', 'anchor' => $parent['name'], 'placement' => 'breadcrumb',
			'required' => true, 'public_safe' => true, 'basis_relation_id' => '', 'evidence_state' => 'verified',
		);
	}
	foreach ( $entity['seo']['expected_child_ids'] as $child_id ) {
		$child = $entities[ $entity_offsets[ $child_id ] ];
		$link_plan[] = array(
			'target_id' => $child_id, 'purpose' => 'child-discovery', 'anchor' => $child['name'], 'placement' => 'body',
			'required' => true, 'public_safe' => true, 'basis_relation_id' => '', 'evidence_state' => 'verified',
		);
	}
	foreach ( $entity['relations'] as $relation ) {
		$target = $entities[ $entity_offsets[ $relation['target_id'] ] ];
		$link_plan[] = array(
			'target_id' => $target['id'], 'purpose' => 'related-' . $relation['type'], 'anchor' => $target['name'], 'placement' => 'related_module',
			'required' => false, 'public_safe' => $relation['public_safe'], 'basis_relation_id' => $relation['id'], 'evidence_state' => $relation['confidence'],
		);
	}
	foreach ( $entity['commerce']['cross_sell_ids'] as $target_id ) {
		$target = $entities[ $entity_offsets[ $target_id ] ];
		$link_plan[] = array(
			'target_id' => $target_id, 'purpose' => 'cross-sell', 'anchor' => $target['name'], 'placement' => 'commerce_module',
			'required' => false, 'public_safe' => false, 'basis_relation_id' => '', 'evidence_state' => 'pending',
		);
	}
	foreach ( $entity['commerce']['up_sell_ids'] as $target_id ) {
		$target = $entities[ $entity_offsets[ $target_id ] ];
		$link_plan[] = array(
			'target_id' => $target_id, 'purpose' => 'up-sell', 'anchor' => $target['name'], 'placement' => 'commerce_module',
			'required' => false, 'public_safe' => false, 'basis_relation_id' => '', 'evidence_state' => 'pending',
		);
	}
	$entity['seo']['link_plan'] = $link_plan;
}
unset( $entity );

/*
 * Public Japanese museum pilot. The complete graph remains intact for private
 * editorial work, while every public semantic edge is explicitly allowlisted.
 * Visibility and search indexing stay separate, so reviewed preview pages can
 * be evaluated before long-form index approval.
 */
$public_pilot_ids = array(
	'museum-culinary-science',
	'cuisine-japanese-washoku',
	'hub-japanese-foundations-lab',
	'hub-japanese-equipment',
	'hub-japanese-ingredients',
	'hub-japanese-techniques',
	'hub-japanese-food-science',
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
	'preparation-ichiban-dashi',
	'guide-umami-synergy',
	'guide-wasabi-aitc',
	'molecule-allyl-isothiocyanate',
	'equipment-wasabi-grater',
);
$public_pilot_lookup = array_fill_keys( $public_pilot_ids, true );
$public_semantic_allowlists = array(
	'museum-culinary-science' => array( 'cuisine-japanese-washoku' ),
	'cuisine-japanese-washoku' => array( 'museum-culinary-science', 'hub-japanese-foundations-lab', 'hub-japanese-equipment', 'hub-japanese-ingredients', 'hub-japanese-techniques', 'hub-japanese-food-science', 'ingredient-kombu', 'ingredient-katsuobushi', 'ingredient-kioke-shoyu', 'ingredient-kome-koji', 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi', 'ingredient-kito-yuzu', 'ingredient-hon-mirin', 'preparation-ichiban-dashi', 'guide-umami-synergy', 'guide-wasabi-aitc', 'equipment-wasabi-grater' ),
	'hub-japanese-foundations-lab' => array( 'cuisine-japanese-washoku', 'ingredient-kombu', 'ingredient-katsuobushi', 'ingredient-kioke-shoyu', 'ingredient-kome-koji', 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi', 'ingredient-kito-yuzu', 'ingredient-hon-mirin', 'guide-umami-synergy', 'guide-wasabi-aitc', 'molecule-allyl-isothiocyanate', 'preparation-ichiban-dashi', 'equipment-wasabi-grater' ),
	'hub-japanese-equipment' => array( 'cuisine-japanese-washoku', 'equipment-wasabi-grater', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi', 'guide-wasabi-aitc' ),
	'hub-japanese-ingredients' => array( 'cuisine-japanese-washoku', 'ingredient-kombu', 'ingredient-katsuobushi', 'ingredient-kioke-shoyu', 'ingredient-kome-koji', 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi', 'ingredient-kito-yuzu', 'ingredient-hon-mirin' ),
	'hub-japanese-techniques' => array( 'cuisine-japanese-washoku', 'preparation-ichiban-dashi', 'hub-japanese-food-science' ),
	'hub-japanese-food-science' => array( 'cuisine-japanese-washoku', 'preparation-ichiban-dashi', 'guide-umami-synergy', 'guide-wasabi-aitc', 'ingredient-kombu', 'ingredient-katsuobushi', 'ingredient-kome-koji', 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi' ),
	'ingredient-kombu' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'hub-japanese-food-science', 'ingredient-katsuobushi', 'ingredient-kioke-shoyu', 'ingredient-fresh-wasabi', 'ingredient-kito-yuzu', 'preparation-ichiban-dashi', 'guide-umami-synergy' ),
	'ingredient-katsuobushi' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'hub-japanese-food-science', 'ingredient-kombu', 'preparation-ichiban-dashi', 'guide-umami-synergy' ),
	'ingredient-kioke-shoyu' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'ingredient-kombu', 'ingredient-kome-koji', 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi', 'ingredient-kito-yuzu', 'ingredient-hon-mirin' ),
	'ingredient-kome-koji' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'hub-japanese-food-science', 'ingredient-koji-starter-culture', 'ingredient-koshihikari-rice', 'ingredient-kioke-shoyu' ),
	'ingredient-koji-starter-culture' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'hub-japanese-food-science', 'ingredient-kome-koji', 'ingredient-koshihikari-rice', 'ingredient-kioke-shoyu' ),
	'ingredient-koshihikari-rice' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'hub-japanese-food-science', 'ingredient-kome-koji', 'ingredient-koji-starter-culture', 'ingredient-kioke-shoyu', 'ingredient-fresh-dutch-wasabi' ),
	'ingredient-fresh-wasabi' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'hub-japanese-food-science', 'hub-japanese-equipment', 'guide-wasabi-aitc', 'molecule-allyl-isothiocyanate', 'equipment-wasabi-grater', 'ingredient-kombu', 'ingredient-kioke-shoyu', 'ingredient-fresh-dutch-wasabi', 'ingredient-kito-yuzu' ),
	'ingredient-fresh-dutch-wasabi' => array( 'cuisine-japanese-washoku', 'ingredient-fresh-wasabi', 'hub-japanese-ingredients', 'hub-japanese-food-science', 'hub-japanese-equipment', 'guide-wasabi-aitc', 'molecule-allyl-isothiocyanate', 'equipment-wasabi-grater', 'ingredient-kioke-shoyu', 'ingredient-koshihikari-rice' ),
	'ingredient-kito-yuzu' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'ingredient-kombu', 'ingredient-kioke-shoyu', 'ingredient-fresh-wasabi', 'ingredient-hon-mirin' ),
	'ingredient-hon-mirin' => array( 'cuisine-japanese-washoku', 'hub-japanese-ingredients', 'ingredient-kioke-shoyu', 'ingredient-kito-yuzu' ),
	'preparation-ichiban-dashi' => array( 'cuisine-japanese-washoku', 'hub-japanese-techniques', 'hub-japanese-food-science', 'ingredient-kombu', 'ingredient-katsuobushi', 'guide-umami-synergy' ),
	'guide-umami-synergy' => array( 'cuisine-japanese-washoku', 'hub-japanese-food-science', 'ingredient-kombu', 'ingredient-katsuobushi', 'preparation-ichiban-dashi' ),
	'guide-wasabi-aitc' => array( 'cuisine-japanese-washoku', 'hub-japanese-food-science', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi', 'molecule-allyl-isothiocyanate', 'equipment-wasabi-grater' ),
	'molecule-allyl-isothiocyanate' => array( 'guide-wasabi-aitc', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi' ),
	'equipment-wasabi-grater' => array( 'hub-japanese-equipment', 'ingredient-fresh-wasabi', 'ingredient-fresh-dutch-wasabi', 'guide-wasabi-aitc' ),
);
$public_asset_receipts = array(
	'museum-culinary-science' => 'sha256:ee2441315d9c03074bbe88bba7408e66e06323a4906d1c5310574028d970f18b',
	'cuisine-japanese-washoku' => 'sha256:98558d16ea7975b78ba7b925ea2a4b3a7dc0f6158a42e94855f30f73f7fa644c',
	'hub-japanese-foundations-lab' => 'sha256:8dcc708e53538ed4a0044d3cd79704f1d9e02ff01142b8f5f486192e3595e180',
	'hub-japanese-ingredients' => 'sha256:76cc7ecfebd4eac9ecb9ed6a670cee097941a99637fbc2446b00eb7692848e10',
	'ingredient-kombu' => 'sha256:046d2ba7f392efa8076afc3acae177604e27cbe77ef3d8c626fc2974abe8ac4e',
	'ingredient-katsuobushi' => 'sha256:a48c8adf8f92b0c425301ff5cfff502301af0babb059cf446aa100c1fdd91b8e',
	'ingredient-kioke-shoyu' => 'sha256:7bbb750f81dac4c2ec8326174f48d2aedba782be68a780c0b63acfcf1ad8b950',
	'ingredient-kome-koji' => 'sha256:49a01347668903b4e6140b27cbd18db5560dccc17216153a71c85a7eaf385e61',
	'ingredient-koji-starter-culture' => 'sha256:58811677efcbc8534e8c3ade288c7de332b06d95e2d1e3e0bedddbd2eb37dea0',
	'ingredient-koshihikari-rice' => 'sha256:e231622d6a84a5c2d2f8023bf1431faee49c7da141a22a944882fc584e744799',
	'ingredient-fresh-wasabi' => 'sha256:740471ec3f8970016f31af46ef6206c9984f07b25b09e00ed5f59a4bfe15d1b1',
	'ingredient-fresh-dutch-wasabi' => 'sha256:720f8b65c3f1f010332664261ef4608e21d9f88d13349d041dabac248d3d0ed8',
	'ingredient-kito-yuzu' => 'sha256:e058ebfece1033d37f2835678a961f4bfbf7fbe988b960036d23f12bf83b2464',
	'hub-japanese-food-science' => 'sha256:41affd1d16f01e9aeb418d05139d0df6aad5bee4c02df88473ea2c33c516c49b',
	'hub-japanese-techniques' => 'sha256:2eda7710abfa5ce35e1634fecdf69a57efdf3875638889c217bba804d44027b4',
	'hub-japanese-equipment' => 'sha256:1c36efbad8d50150c0147bb1064bba40abf60feb7ed036cdca9dcabbb6e80b12',
	'ingredient-hon-mirin' => 'sha256:c8808bebd8f92d7ebfd4b78d3ab3853ebff56fbe00e8d98c3433db75d4de97d0',
	'preparation-ichiban-dashi' => 'sha256:28eb6c05cec30ba9f4fb986c12afc31b8dd9c3cf2c90a3ec2a25400482a847e2',
	'guide-umami-synergy' => 'sha256:cff653805e2e90b3ee4d565cdfdd21c8ac4e13782441860bd81a98516d1c7cd5',
	'guide-wasabi-aitc' => 'sha256:a74f67aaab227256031f2b0bd477bee76562b36ddf072338ccca69d1b894918c',
	'molecule-allyl-isothiocyanate' => 'sha256:87fdf5927fd72ba282e97d72c948d87213f02fbdef2dd4a13ce607f042084ae6',
	'equipment-wasabi-grater' => 'sha256:be0f4f831f58efc4ab6b6c74fa1979aaa4797bf9e4f1be51a19b2afe6d9a1757',
);

$ingredient_hub_offset = $entity_offsets['hub-japanese-ingredients'];
$entities[ $ingredient_hub_offset ]['facts'][0]['statement'] = $c99_text(
	'במדור זה מוצגים חומרי גלם יפניים עם הסבר על מקור, טעם, שימוש, מדע ומחיר מקור מתועד כאשר הוא זמין.',
	'This section presents Japanese ingredients with origin, flavor, use, science and a documented source-market price when available.'
);
$entities[ $ingredient_hub_offset ]['facts'][0]['evidence_class'] = 'official_source';
$entities[ $ingredient_hub_offset ]['facts'][0]['source_ids'] = array( 'complete99-public-site' );
$entities[ $ingredient_hub_offset ]['facts'][0]['public_safe'] = true;

$food_science_hub_offset = $entity_offsets['hub-japanese-food-science'];
$entities[ $food_science_hub_offset ]['facts'][0]['evidence_class'] = 'peer_reviewed_context';
$entities[ $food_science_hub_offset ]['facts'][0]['source_ids'] = array( 'umami-receptor-2009', 'wasabi-itc-2023' );
$entities[ $food_science_hub_offset ]['facts'][0]['public_safe'] = true;

$techniques_hub_offset = $entity_offsets['hub-japanese-techniques'];
$entities[ $techniques_hub_offset ]['facts'][0]['evidence_class'] = 'official_source';
$entities[ $techniques_hub_offset ]['facts'][0]['source_ids'] = array( 'maff-edomae', 'maff-fermented-foods' );
$entities[ $techniques_hub_offset ]['facts'][0]['public_safe'] = true;

$equipment_hub_offset = $entity_offsets['hub-japanese-equipment'];
$entities[ $equipment_hub_offset ]['facts'][0]['statement'] = $c99_text(
	'האתר הרשמי של קפאבשי מתאר אזור המתמחה בכלי מטבח, ודף Yamamoto הרשמי מפריד את מפרטי Hagane-zame לפי דגם. לכן כדאי להשוות את השימוש המיועד ואת הדגם המדויק ולא להתייחס לכל הכלים היפניים כזהים.',
	'The official Kappabashi site describes a district specializing in kitchenware, and Yamamoto Foods separates Hagane-zame specifications by model. Compare intended use and the exact model rather than treating Japanese tools as interchangeable.'
);
$entities[ $equipment_hub_offset ]['facts'][0]['evidence_class'] = 'official_source';
$entities[ $equipment_hub_offset ]['facts'][0]['source_ids'] = array( 'kappabashi-official', 'yamamoto-haganezame-spec' );
$entities[ $equipment_hub_offset ]['facts'][0]['public_safe'] = true;

$public_meta_descriptions = array(
	'hub-japanese-foundations-lab' => $c99_text( 'יסודות המטבח היפני במסלול אחד: חומרי גלם, מדע הטעם, טכניקות הכנה וכלים מקצועיים, עם קישורים ישירים לכל מדריך ודף מרכיב.', 'Japanese culinary foundations in one path: ingredients, flavor science, preparation techniques and professional tools, with direct links to every guide and ingredient page.' ),
	'hub-japanese-food-science' => $c99_text( 'מדע המזון היפני: אומאמי, גלוטמט, IMP, קוג׳י, התססה, דאשי ומדידות, עם מקורות וקשרים למנות ולחומרי גלם.', 'Japanese food science: umami, glutamate, IMP, koji, fermentation, dashi and measurements, with sources and links to ingredients and dishes.' ),
	'hub-japanese-techniques' => $c99_text( 'טכניקות בישול יפניות: דאשי, אורז, חיתוך, קוג׳י והתססה במפת ידע שמפרידה חומר, זמן, טמפרטורה, כלי ותוצאה.', 'Japanese culinary techniques: dashi, rice, cutting, koji and fermentation in a knowledge map separating material, time, temperature, tool and result.' ),
	'hub-japanese-equipment' => $c99_text( 'כלי מטבח יפניים להכנה מדויקת: בחירה לפי פעולה, חומר, מידה, תחזוקה ומפרט דגם, עם קישורים לחומרי גלם ולטכניקות מתאימות.', 'Japanese culinary tools for precise preparation: choose by task, material, size, care and model specification, with links to suitable ingredients and techniques.' ),
	'ingredient-kome-koji' => $c99_text( 'קומה קוג׳י מיובש: תפקיד האנזימים, ההבדל מתרבית tane-koji, אחסון בקירור, שימושים בהתססה, מחיר מקור וקישור למוצר 500 גרם.', 'Dried kome koji: enzyme function, the boundary from tane-koji starter, refrigerated storage, fermentation uses, source-market price and the 500 g product.' ),
	'ingredient-koji-starter-culture' => $c99_text( 'תרבית קוג׳י Chouhaku-kin אבקתית: שימוש למיסו ושיו קוג׳י, מינון יצרן, אחסון בקירור, מדע התהליך וקישור לשקית 20 גרם.', 'Chouhaku-kin powdered koji starter: use for miso and shio-koji, maker dose, refrigeration, process science and the 20 g sachet.' ),
	'ingredient-koshihikari-rice' => $c99_text( 'אורז קושיהיקארי מאוזו, טויאמה: מקור, זן, איכות בישול, גבולות מפרט האצווה, מחיר מקור וקישור לאריזת 2 ק״ג.', 'Uozu, Toyama Koshihikari rice: origin, cultivar, cooking-quality boundaries, lot specification, source-market price and the 2 kg pack.' ),
	'ingredient-fresh-dutch-wasabi' => $c99_text( 'וואסבי טרי בגידול הולנדי, 50 עד 60 גרם: מקור חממה, מדע AITC, אחסון בקירור, כלי גרירה, מחיר מקור וקישור למוצר.', 'Dutch-grown fresh wasabi, 50 to 60 g: greenhouse origin, AITC science, refrigeration, grating tools, source-market price and the product.' ),
	'preparation-ichiban-dashi' => $c99_text( 'איצ׳יבאן דאשי מקומבו וקצואובושי: עקרונות מיצוי, סינרגיית גלוטמט ו-IMP, אלרגן דגים וקישורים לחומרי הגלם.', 'Ichiban dashi from kombu and katsuobushi: extraction principles, glutamate and IMP synergy, fish allergen context and ingredient links.' ),
	'ingredient-hon-mirin' => $c99_text( 'מהו הון מירין יפני: אורז דביק, קוג׳י, סכריפיקציה, הבשלה, אלכוהול וההבדל מתיבול בסגנון מירין, עם מקורות.', 'What Japanese hon mirin is: glutinous rice, koji, saccharification, maturation, alcohol and the boundary from mirin-style seasoning, with sources.' ),
	'guide-umami-synergy' => $c99_text( 'סינרגיית אומאמי בין גלוטמט ל-IMP: מנגנון T1R1/T1R3, ההבדל בין מחקר למדידת מוצר וקשרים לדאשי, קומבו וקצואובושי.', 'Glutamate and IMP umami synergy: the T1R1/T1R3 mechanism, research versus product measurement, and links to dashi, kombu and katsuobushi.' ),
	'guide-wasabi-aitc' => $c99_text( 'AITC וחריפות וואסבי טרי: המערכת האנזימטית, שונות בין זנים ועונות, והקשר בין קנה השורש, המולקולה וכלי הגרירה.', 'AITC and fresh wasabi pungency: the enzyme system, genetic and seasonal variation, and links among the rhizome, molecule and grating tool.' ),
	'equipment-wasabi-grater' => $c99_text( 'מדריך מגררת וואסבי: תפקיד הכלי, חומר, מידות ומפרטי דגם, עם קישור לוואסבי טרי ולמדע החריפות.', 'Wasabi grater guide: tool purpose, material, dimensions and model specifications, linked to fresh wasabi and pungency science.' ),
);
foreach ( $public_meta_descriptions as $public_meta_entity_id => $public_meta_description ) {
	$entities[ $entity_offsets[ $public_meta_entity_id ] ]['seo']['meta_description'] = $public_meta_description;
}

foreach ( $public_pilot_ids as $public_entity_id ) {
	$entity_offset = $entity_offsets[ $public_entity_id ];
	$entities[ $entity_offset ]['surface_class'] = 'public_discovery';
	$entities[ $entity_offset ]['index_policy'] = 'noindex_until_longform_review';
	$entities[ $entity_offset ]['publication'] = array(
		'state' => 'approved_public',
		'public_api' => true,
		'public_page' => true,
		'search_index' => false,
		'approved_at' => '2026-08-06',
	);
	$entities[ $entity_offset ]['review']['status'] = 'source_reviewed';
	$entities[ $entity_offset ]['review']['reviewed_at'] = '2026-08-06';
	$entities[ $entity_offset ]['review']['language_status'] = 'reviewed_bilingual';
	$entities[ $entity_offset ]['trust']['attribution_state'] = 'organization_editorial_process';
	$entities[ $entity_offset ]['trust']['substantive_updated_at'] = '2026-08-06';
	$entities[ $entity_offset ]['visual']['asset_state'] = 'approved';
	$entities[ $entity_offset ]['visual']['rights_method'] = 'generated_for_complete99_with_human_review';
	$entities[ $entity_offset ]['visual']['rights_state'] = 'cleared_generated';
	$entities[ $entity_offset ]['visual']['rights_receipt_digest'] = $public_asset_receipts[ $public_entity_id ];
	$entities[ $entity_offset ]['taxonomy']['public_category_path'] = $entities[ $entity_offset ]['taxonomy']['category_path'];
	$entities[ $entity_offset ]['taxonomy']['public_attribute_keys'] = array_keys( $entities[ $entity_offset ]['taxonomy']['attributes'] );
	$entities[ $entity_offset ]['taxonomy']['public_tags'] = $entities[ $entity_offset ]['taxonomy']['tags'];
	$entities[ $entity_offset ]['seo']['semantic_entity_ids'] = $public_semantic_allowlists[ $public_entity_id ];

	foreach ( $entities[ $entity_offset ]['relations'] as &$public_relation ) {
		$public_relation['public_safe'] = true === $public_relation['public_safe']
			&& isset( $public_pilot_lookup[ $public_relation['target_id'] ] );
	}
	unset( $public_relation );

	foreach ( $entities[ $entity_offset ]['seo']['link_plan'] as &$existing_link ) {
		$existing_link['public_safe'] = false;
	}
	unset( $existing_link );
	foreach ( $public_semantic_allowlists[ $public_entity_id ] as $target_id ) {
		$target = $entities[ $entity_offsets[ $target_id ] ];
		$is_parent = $target_id === $entities[ $entity_offset ]['parent_id'];
		$matched_existing_link = false;
		foreach ( $entities[ $entity_offset ]['seo']['link_plan'] as &$candidate_link ) {
			if ( ! $matched_existing_link && $target_id === $candidate_link['target_id'] ) {
				$candidate_link['public_safe'] = true;
				$matched_existing_link = true;
			}
		}
		unset( $candidate_link );
		if ( $matched_existing_link ) {
			continue;
		}
		$entities[ $entity_offset ]['seo']['link_plan'][] = array(
			'target_id' => $target_id,
			'purpose' => $is_parent ? 'parent-context' : 'curated-discovery',
			'anchor' => $target['name'],
			'placement' => $is_parent ? 'breadcrumb' : 'related_module',
			'required' => $is_parent,
			'public_safe' => true,
			'basis_relation_id' => '',
			'evidence_state' => 'verified',
		);
	}
}

return array(
	'schema'        => 'complete99-culinary-science-registry/v5',
	'version'       => 'japanese-pilot-2026.08.06.v10',
	'generated_at'  => '2026-08-06',
	'locales'       => array( 'he', 'en' ),
	'surface_class' => 'editorial_draft',
	'controlled_vocabulary' => array(
		'entity_types' => array( 'topic_hub', 'cuisine', 'tradition', 'dish', 'preparation', 'ingredient', 'molecule', 'reaction', 'technique', 'guide', 'comparison', 'equipment', 'material_specification', 'quality_grade', 'culinary_institution', 'restaurant', 'market', 'equipment_shop', 'producer', 'supplier', 'market_observation', 'retail_listing', 'standard', 'geographical_indication', 'guide_edition', 'alternative', 'visual_asset', 'compliance_rule' ),
		'surface_classes' => array( 'public_discovery', 'private_operations', 'editorial_draft', 'future_architecture' ),
		'index_policies' => array( 'index', 'noindex_until_longform_review', 'noindex_private', 'noindex_duplicate' ),
		'page_roles' => array( 'home', 'category', 'pillar', 'spoke', 'article', 'location', 'profile', 'comparison', 'tool', 'reference', 'utility' ),
		'intent_classes' => array( 'informational', 'commercial', 'transactional', 'navigational', 'local' ),
		'profile_states' => array( 'source_backed', 'pending_evidence', 'not_applicable' ),
		'dimensions' => array( 'scientific', 'cultural', 'institutional', 'economic', 'structural' ),
		'evidence_classes' => array( 'official_source', 'third_party_guide', 'peer_reviewed_context', 'conference_context', 'regulatory_standard', 'supplier_declaration', 'lot_coa', 'market_observation', 'editorial_inference' ),
		'value_scopes' => array( 'entity', 'category', 'technique_context', 'market_snapshot', 'supplier_specification', 'lot_measurement' ),
		'relation_types' => array( 'part_of', 'contains', 'used_in', 'requires', 'produced_by', 'sold_by', 'sourced_from', 'observed_at', 'graded_as', 'specified_by', 'certified_by', 'recognized_in', 'recognizes', 'complements', 'substitutes', 'upgrades_to', 'located_at', 'supported_by', 'benchmarks', 'teaches', 'serves', 'references' ),
		'commerce_states' => array( 'reference_only', 'supplier_onboarding', 'verified_sku', 'active_offer' ),
		'asset_states' => array( 'spec_ready', 'original_photography_required', 'rights_review_required', 'approved' ),
		'rights_states' => array( 'pending', 'cleared_owned', 'cleared_generated', 'cleared_licensed', 'restricted' ),
		'source_types' => array( 'official_government', 'official_organization', 'peer_reviewed_paper', 'conference_proceeding', 'official_standard', 'official_business', 'official_market_listing', 'regulatory_guidance' ),
		'allowed_attributes' => array( 'pa_origin', 'pa_species', 'pa_cultivar', 'pa_processing_method', 'pa_fermentation_method', 'pa_vessel', 'pa_flavor_profile', 'pa_allergens', 'pa_storage_type', 'pa_material', 'pa_steel', 'pa_handedness', 'pa_quality_grade', 'pa_market', 'pa_institution_type', 'pa_equipment_required' ),
		'attribution_states' => array( 'pending_named_review', 'organization_editorial_process', 'named_expert_reviewed' ),
		'confidence_levels' => array( 'pending', 'reviewed', 'verified' ),
		'revenue_models' => array( 'retail_product', 'prepared_food_sale', 'curated_bundle', 'content_to_commerce', 'education', 'lead_generation', 'market_intelligence' ),
		'pricing_states' => array( 'research_required', 'source_price_observed', 'landed_cost_estimated', 'retail_price_proposed', 'approved_sell_price' ),
		'market_scopes' => array( 'israel_launch', 'japan_source_market', 'global_research', 'market_specific' ),
		'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'foodservice_buyers', 'culinary_students', 'research_readers', 'institutional_buyers' ),
		'publication_states' => array( 'research_draft', 'private_preview', 'approved_public' ),
		'route_modes' => array( 'standalone', 'section', 'private' ),
	),
	'sources'     => $sources,
	'entities'    => $entities,
	'collections' => $collections,
);
