<?php
/**
 * Complete99 Syrian regional cuisine foundations.
 *
 * This module is an editorial research tranche. The main registry may expose a
 * small, explicitly reviewed noindex subset; every other entity remains private
 * and non-commercial until its culinary, language, rights and source review is
 * complete.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_syrian_sources = array(
	'unesco-syrian-ich-survey-2017' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO',
		'title' => 'Survey of intangible cultural heritage elements in Syria',
		'url' => 'https://ich.unesco.org/doc/src/38275-EN.pdf', 'published_at' => '2017-01-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-syria-home' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Agricultural Voices Syria',
		'url' => 'https://agricultural-voices.sussex.ac.uk/', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'avs-heart-to-hearth' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'From Heart to Hearth',
		'url' => 'https://agricultural-voices.sussex.ac.uk/?page_id=1006', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'avs-razan-damascus' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Razan\'s Story, Damascus',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Razans-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-mirvet-aleppo' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Mirvet\'s Story, Aleppo',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Mirvets-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-nariman-homs' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Nariman\'s Story, Homs',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Narimans-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-noor-hama' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Noor\'s Story, Hama',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Noors-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-zainab-coast' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Zainab\'s Story, Syrian coast',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Zainabs-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-rana-raqqa' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Rana\'s Story, Raqqa',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Ranas-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-buthaina-east' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Buthaina\'s Story, eastern Syria',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Buthainas-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-samar-qamishli' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Samar\'s Story, Qamishli family transmission',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Samars-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-ghaimana-suwayda' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Ghaimana\'s Story, Suwayda',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Ghaimanas-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-shahla-hauran' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Shahla\'s Story, Hauran',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Shahlas-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-amani-afrin' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Amani\'s Story, Afrin',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Amanis-Story-3.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'avs-rahma-idlib' => array(
		'type' => 'official_organization', 'publisher' => 'University of Sussex, Agricultural Voices Syria',
		'title' => 'Rahma\'s Story, Idlib',
		'url' => 'https://agricultural-voices.sussex.ac.uk/wp-content/uploads/2025/03/Rahmas-Story.pdf', 'published_at' => '2025-03-01', 'retrieved_at' => '2026-08-06',
	),
	'aleppo-project-cuisine-2017' => array(
		'type' => 'official_organization', 'publisher' => 'The Aleppo Project',
		'title' => 'Aleppo Cuisine',
		'url' => 'https://www.thealeppoproject.com/wp-content/uploads/2017/08/Cuisine-Final.pdf', 'published_at' => '2017-08-01', 'retrieved_at' => '2026-08-06',
	),
	'unesco-ancient-city-aleppo' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO World Heritage Centre',
		'title' => 'Ancient City of Aleppo',
		'url' => 'https://whc.unesco.org/en/list/21/', 'published_at' => '', 'retrieved_at' => '2026-08-08',
	),
	'georgetown-making-levantine-cuisine' => array(
		'type' => 'official_organization', 'publisher' => 'Georgetown University Center for Contemporary Arab Studies',
		'title' => 'On Making Levantine Cuisine',
		'url' => 'https://ccas.georgetown.edu/ccas-newsmagazine/on-making-levantine-cuisine/', 'published_at' => '', 'retrieved_at' => '2026-08-08',
	),
	'simon-schuster-aleppo-cookbook' => array(
		'type' => 'official_business', 'publisher' => 'Simon & Schuster',
		'title' => 'The Aleppo Cookbook by Marlene Matar',
		'url' => 'https://www.simonandschuster.com/books/The-Aleppo-Cookbook/Marlene-Matar/9781566569866', 'published_at' => '', 'retrieved_at' => '2026-08-08',
	),
	'bulgur-hydration-cereal-chemistry' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Cereal Chemistry',
		'title' => 'Effect of Hydration Temperature and Time on Bulgur Quality',
		'url' => 'https://doi.org/10.1002/cche.10427', 'published_at' => '', 'retrieved_at' => '2026-08-08',
	),
	'lal-scents-flavors' => array(
		'type' => 'official_organization', 'publisher' => 'Library of Arabic Literature',
		'title' => 'Scents and Flavors: A Syrian Cookbook',
		'url' => 'https://www.libraryofarabicliterature.org/books/9781479856282/scents-and-flavors/', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'ettijahat-syrian-cuisine-2019' => array(
		'type' => 'official_organization', 'publisher' => 'Ettijahat Independent Culture',
		'title' => 'Syrian Cuisine research project',
		'url' => 'https://www.ettijahat.org/page/1007', 'published_at' => '2019-01-01', 'retrieved_at' => '2026-08-06',
	),
	'international-academy-gastronomy-syria' => array(
		'type' => 'official_organization', 'publisher' => 'International Academy of Gastronomy',
		'title' => 'Syria gastronomy profile',
		'url' => 'https://intergastronom.org/syria/', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'smithsonian-syrian-armenian-foodways' => array(
		'type' => 'official_organization', 'publisher' => 'Smithsonian Center for Folklife and Cultural Heritage',
		'title' => 'Food and Longing in the Armenian Diaspora',
		'url' => 'https://folklife.si.edu/magazine/forklife-food-and-longing-armenian-diaspora', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'anu-syrian-jewish-community' => array(
		'type' => 'official_organization', 'publisher' => 'ANU Museum of the Jewish People',
		'title' => 'The Jewish community of Syria',
		'url' => 'https://dbs.anumuseum.org.il/skn/en/c6/e134370/Place/Syria', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'nli-aleppo-tradition' => array(
		'type' => 'official_organization', 'publisher' => 'National Library of Israel',
		'title' => 'Aleppo Jewish tradition',
		'url' => 'https://www.nli.org.il/he/discover/music/jewish-music/piyut/traditions/heleb-aleppo', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'nli-damascus-tradition' => array(
		'type' => 'official_organization', 'publisher' => 'National Library of Israel',
		'title' => 'Musical tradition of Damascus Jewry',
		'url' => 'https://www.nli.org.il/he/discover/music/jewish-music/piyut/articles/introductions/traditions/musical-tradition-of-damascus-jewry', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'jfs-passover-kibbeh-damascus' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Passover Kibbeh',
		'url' => 'https://www.jewishfoodsociety.org/recipes/passover-kibbeh', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'jfs-kibbeh-hamda' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'The Syrian Passover soup that came to Brooklyn',
		'url' => 'https://www.jewishfoodsociety.org/stories/the-syrian-passover-soup-that-came-to-brooklyn', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'jfs-yebra-apricots' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Yebra stuffed grape leaves with apricots',
		'url' => 'https://www.jewishfoodsociety.org/recipes/yebra-stuffed-grape-leaves-with-apricots', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'jfs-lahm-bajin' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Laham Bajeen flatbread with meat and pomegranate',
		'url' => 'https://www.jewishfoodsociety.org/recipes/laham-bajeen-flatbread-with-meat-and-pomegranate', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'foodish-matzah-kebab' => array(
		'type' => 'official_organization', 'publisher' => 'FOODISH, ANU Museum of the Jewish People',
		'title' => 'Matzah Kebab',
		'url' => 'https://foodish.anumuseum.org.il/en/recipe/matzah-kebab/', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'foodish-dajaj-mashwi' => array(
		'type' => 'official_organization', 'publisher' => 'FOODISH, ANU Museum of the Jewish People',
		'title' => 'Dajaj Mashwi',
		'url' => 'https://foodish.anumuseum.org.il/en/recipe/dajaj-mashwi/', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'bulgur-hydration-2025' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed',
		'title' => 'Bulgur hydration research',
		'url' => 'https://pubmed.ncbi.nlm.nih.gov/41273208/', 'published_at' => '2025-01-01', 'retrieved_at' => '2026-08-06',
	),
	'yogurt-protein-structure-2023' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Yogurt protein structure research',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC10609537/', 'published_at' => '2023-01-01', 'retrieved_at' => '2026-08-06',
	),
	'pomegranate-physicochemical-2020' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Pomegranate physicochemical context',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC7074153/', 'published_at' => '2020-01-01', 'retrieved_at' => '2026-08-06',
	),
	'sour-cherry-organic-acids-2020' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Organic acids in sour cherry',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC7582279/', 'published_at' => '2020-01-01', 'retrieved_at' => '2026-08-06',
	),
	'sumac-organic-acids-2022' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Organic acids in sumac',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC9414570/', 'published_at' => '2022-01-01', 'retrieved_at' => '2026-08-06',
	),
	'fda-water-activity' => array(
		'type' => 'regulatory_guidance', 'publisher' => 'United States Food and Drug Administration',
		'title' => 'Water Activity in Foods',
		'url' => 'https://www.fda.gov/inspections-compliance-enforcement-and-criminal-investigations/inspection-technical-guides/water-activity-aw-foods', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'foodsafety-safe-temperatures' => array(
		'type' => 'official_government', 'publisher' => 'United States Department of Agriculture, Food Safety and Inspection Service',
		'title' => 'Safe Temperature Chart',
		'url' => 'https://www.fsis.usda.gov/food-safety/safe-food-handling-and-preparation/food-safety-basics/safe-temperature-chart', 'published_at' => '', 'retrieved_at' => '2026-08-08',
	),
	'cdc-raw-kibbeh-salmonella-2013' => array(
		'type' => 'official_government', 'publisher' => 'United States Centers for Disease Control and Prevention',
		'title' => 'Salmonella Typhimurium infections linked to raw ground beef kibbeh',
		'url' => 'https://archive.cdc.gov/www_cdc_gov/salmonella/typhimurium-01-13/index.html', 'published_at' => '2013-01-01', 'retrieved_at' => '2026-08-06',
	),
	'israel-moh-food-hygiene' => array(
		'type' => 'official_government', 'publisher' => 'Israel Ministry of Health',
		'title' => 'Food hygiene guidance',
		'url' => 'https://www.gov.il/he/pages/food-hygiene?chapterIndex=2', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'israel-moh-allergen-survey-2024' => array(
		'type' => 'official_government', 'publisher' => 'Israel Ministry of Health',
		'title' => 'Survey of allergens in marketing channels, 2024',
		'url' => 'https://www.gov.il/BlobFolder/reports/summary-report-survey-allergens-marketing-channels-2024/he/files_publications_units_food_control_services_Summary-report-survey-allergens-marketing-channels-2024.pdf', 'published_at' => '2024-01-01', 'retrieved_at' => '2026-08-06',
	),
	'big-dabach-sugat-freekeh-500g-listing-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Big Dabach',
		'title' => 'Sugat freekeh 500 g indexed product page',
		'url' => 'https://www.bigdabach.co.il/?catalogProduct=6279611', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'tamar-hst-keter-harimon-pomegranate-concentrate-250ml-listing-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Tamar HST',
		'title' => 'Keter Harimon pomegranate concentrate 250 ml product page',
		'url' => 'https://www.tamar-hst.co.il/product-details/209856/%D7%A8%D7%9B%D7%96_%D7%A8%D7%99%D7%9E%D7%95%D7%9F', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
	'tamar-bakfar-pure-ground-sumac-100g-indexed-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Tamar Bakfar historical indexed result',
		'title' => 'Pure ground sumac 100 g historical indexed product result',
		'url' => 'https://tamarbakfar.co.il/product/%D7%A1%D7%95%D7%9E%D7%A7-%D7%98%D7%97%D7%95%D7%9F-%D7%98%D7%94%D7%95%D7%A8/', 'published_at' => '', 'retrieved_at' => '2026-08-06',
	),
);

$c99_syrian_fact = static function ( $id, $dimension, $he, $en, $evidence_class, $value_scope, $source_ids ) use ( $c99_fact ) {
	return $c99_fact( $id, $dimension, $he, $en, $evidence_class, $value_scope, $source_ids, false );
};

$c99_syrian_profiles = static function ( $facts ) use ( $c99_profile, $c99_profiles ) {
	$fact_ids_by_dimension = array();
	foreach ( $facts as $fact ) {
		$fact_ids_by_dimension[ $fact['dimension'] ][] = $fact['id'];
	}
	$labels = array(
		'scientific' => array( 'הטענה המדעית תחומה למקור ולהיקף הרשומים.', 'The scientific claim is bounded to its listed source and scope.' ),
		'cultural' => array( 'ההקשר התרבותי נשמר לפי העדות או המוסד המזוהים.', 'The cultural context is retained according to the named testimony or institution.' ),
		'institutional' => array( 'ההקשר המוסדי נשמר לפי מקור מזוהה.', 'The institutional context is retained according to an identified source.' ),
		'economic' => array( 'אין ברשומה מחיר, ספק או טענת זמינות.', 'This record contains no price, supplier or availability claim.' ),
		'structural' => array( 'ההבחנה האונטולוגית מתועדת כמבנה עריכה תחום.', 'The ontological distinction is documented as a bounded editorial structure.' ),
	);
	$overrides = array();
	foreach ( $labels as $dimension => $label ) {
		if ( isset( $fact_ids_by_dimension[ $dimension ] ) ) {
			$overrides[ $dimension ] = $c99_profile( 'source_backed', $label[0], $label[1], $fact_ids_by_dimension[ $dimension ] );
		} elseif ( 'economic' === $dimension ) {
			$overrides[ $dimension ] = $c99_profile( 'pending_evidence', $label[0], $label[1] );
		}
	}
	return $c99_profiles( $overrides );
};

$c99_syrian_entity = static function ( $config ) use ( $c99_entity, $c99_syrian_profiles, $c99_text ) {
	$type = $config['type'];
	$region = isset( $config['region'] ) ? $config['region'] : 'syria-national';
	$community = isset( $config['community'] ) ? $config['community'] : 'syrian-multi-community';
	$culinary_status = in_array( $type, array( 'dish', 'preparation' ), true ) ? 'pending' : 'not_applicable';
	$attributes = array(
		'pa_region' => array( $region ),
		'pa_community' => array( $community ),
	);
	if ( isset( $config['attributes'] ) ) {
		$attributes = array_replace( $attributes, $config['attributes'] );
	}
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
		'next_review_at' => '2027-02-06',
		'culinary_test_status' => $culinary_status,
		'schema_type' => isset( $config['schema_type'] ) ? $config['schema_type'] : 'Article',
		'surface_group' => 'syrian-culinary-science',
		'seo_group' => 'syrian-culinary-science',
		'primary_intent' => $config['primary_intent'],
		'primary_keyword' => $config['primary_keyword'],
		'secondary_keywords' => isset( $config['secondary_keywords'] ) ? $config['secondary_keywords'] : array( 'he' => array(), 'en' => array() ),
		'intent_classes' => array( 'informational' ),
		'facts' => $config['facts'],
		'profiles' => $c99_syrian_profiles( $config['facts'] ),
		'categories' => isset( $config['categories'] ) ? $config['categories'] : array( 'culinary-museum', 'syrian-culinary-science', $type . 's' ),
		'attributes' => $attributes,
		'tags' => isset( $config['tags'] ) ? $config['tags'] : array( 'syrian-cuisine', $region, $community ),
		'public_category_path' => array(),
		'public_attribute_keys' => array(),
		'public_tags' => array(),
		'relations' => isset( $config['relations'] ) ? $config['relations'] : array(),
		'commerce_state' => 'reference_only',
		'public_offer_allowed' => false,
		'cross_sell_ids' => isset( $config['cross_sell_ids'] ) ? $config['cross_sell_ids'] : array(),
		'up_sell_ids' => array(),
		'revenue_models' => array( 'content_to_commerce' ),
		'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'culinary_students', 'research_readers' ),
		'pricing_state' => isset( $config['pricing_state'] ) ? $config['pricing_state'] : 'research_required',
		'market_scope' => isset( $config['market_scope'] ) ? $config['market_scope'] : 'global_research',
		'prompt_en' => $config['prompt_en'],
		'negative_prompt_en' => 'No text, no logos, no flags, no national symbols, no costumes, no copied packaging, no historical props, no unsourced garnish, no health claim, no watermark.',
		'asset_state' => 'rights_review_required',
		'rights_method' => 'generated_concept_with_human_review',
		'rights_state' => 'pending',
		'compliance' => isset( $config['compliance'] ) ? $config['compliance'] : array(),
		'attribution_state' => 'pending_named_review',
		'protected_exclusions' => array(
			'he' => array( 'טענת מקור בלעדית', 'הצעת ספק פעילה', 'הבטחה רפואית' ),
			'en' => array( 'exclusive origin claim', 'active supplier offer', 'medical promise' ),
		),
	) );
	$entity['seo']['route_mode'] = 'private';
	$entity['trust']['substantive_updated_at'] = '2026-08-06';
	$entity['review']['reviewed_at'] = '2026-08-06';
	foreach ( $entity['relations'] as &$relation ) {
		$relation['valid_from'] = '2026-08-06';
	}
	unset( $relation );
	return $entity;
};

$c99_syrian_entities = array();

$c99_syrian_entities[] = $c99_syrian_entity( array(
	'id' => 'cuisine-syrian-regional',
	'type' => 'cuisine',
	'slug' => 'syrian-culinary-science',
	'parent_id' => 'museum-culinary-science',
	'name' => $c99_text( 'המטבח הסורי האזורי', 'Syrian Regional Cuisine' ),
	'summary' => $c99_text(
		'מפת מחקר דו-לשונית המציגה את המטבח הסורי כפסיפס של אזורים, קהילות וטכניקות. היא מפרידה בין חלב, דמשק, חומס, חמה, החוף, ג׳זירה, הפרת והמזרח, א-סווידא וחוראן, וממקמת מסורות יהודיות, ארמניות, כורדיות ודרוזיות בתוך התמונה הרחבה בלי להפוך קהילה אחת לתחליף למטבח כולו.',
		'A bilingual research map that treats Syrian cuisine as a mosaic of regions, communities and techniques. It distinguishes Aleppo, Damascus, Homs, Hama, the coast, Jazira, the Euphrates and east, Suwayda and Hauran, while placing Jewish, Armenian, Kurdish and Druze foodways inside the wider picture without using any one community as a proxy for the whole cuisine.'
	),
	'primary_intent' => $c99_text( 'להבין את ההבדלים האזוריים והקהילתיים במטבח הסורי לפני בחירת מנה או חומר גלם.', 'Understand Syrian regional and community differences before choosing a dish or ingredient.' ),
	'primary_keyword' => $c99_text( 'המטבח הסורי לפי אזורים וקהילות', 'Syrian cuisine by region and community' ),
	'schema_type' => 'CollectionPage',
	'facts' => array(
		$c99_syrian_fact(
			'fact-syrian-cuisine-regional-mosaic', 'cultural',
			'המקורות המוסדיים והעדויות האזוריות מתארים רפרטואר משתנה בין ערים, אזורי חוף, מזרח ודרום. לכן הרשומה אינה מציגה נוסחה סורית אחת.',
			'Institutional sources and regional testimonies describe a repertoire that varies across cities, the coast, the east and the south. The record therefore does not present one uniform Syrian formula.',
			'official_source', 'category', array( 'unesco-syrian-ich-survey-2017', 'avs-heart-to-hearth', 'aleppo-project-cuisine-2017' )
		),
		$c99_syrian_fact(
			'fact-syrian-community-scope-boundary', 'structural',
			'מסורות יהודיות מחלב ומדמשק, לצד מסורות ארמניות, כורדיות ודרוזיות, מתועדות כתתי-מסורות חשובות. הן אינן משמשות תחליף לכלל המטבח הסורי.',
			'Aleppan and Damascene Jewish foodways, together with Armenian, Kurdish and Druze foodways, are documented as important subtraditions. They are not used as substitutes for Syrian cuisine as a whole.',
			'official_source', 'category', array( 'anu-syrian-jewish-community', 'smithsonian-syrian-armenian-foodways', 'avs-amani-afrin', 'avs-ghaimana-suwayda', 'avs-heart-to-hearth' )
		),
		$c99_syrian_fact(
			'fact-raw-kibbeh-historical-only-boundary', 'scientific',
			'קובה מבשר נא נשמרת כאן כהקשר היסטורי בלבד. התפרצות מתועדת שנקשרה לקובה מבשר בקר טחון נא מחייבת שלא לפרסם מתכון, הוראות הכנה או המלצת צריכה לנוסח נא.',
			'Raw-meat kibbeh is retained here only as historical context. A documented outbreak linked to raw ground-beef kibbeh means this tranche must not publish a recipe, preparation instructions or consumption recommendation for a raw version.',
			'official_source', 'category', array( 'aleppo-project-cuisine-2017', 'cdc-raw-kibbeh-salmonella-2013' )
		),
	),
	'relations' => array(
		$c99_relation( 'contains', 'region-syria-aleppo', 'האשכול כולל מרכז אזורי לחלב.', 'The cluster includes an Aleppo regional hub.', false, array( 'avs-mirvet-aleppo' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-damascus', 'האשכול כולל מרכז אזורי לדמשק.', 'The cluster includes a Damascus regional hub.', false, array( 'avs-razan-damascus' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-homs', 'האשכול כולל מרכז אזורי לחומס.', 'The cluster includes a Homs regional hub.', false, array( 'avs-nariman-homs' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-hama', 'האשכול כולל מרכז אזורי לחמה.', 'The cluster includes a Hama regional hub.', false, array( 'avs-noor-hama' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-coast', 'האשכול כולל מרכז אזורי לחוף הסורי.', 'The cluster includes a Syrian coast regional hub.', false, array( 'avs-zainab-coast' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-jazira', 'האשכול כולל מרכז אזורי לג׳זירה.', 'The cluster includes a Jazira regional hub.', false, array( 'avs-samar-qamishli' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-euphrates-east', 'האשכול כולל מרכז לאזור הפרת והמזרח.', 'The cluster includes an Euphrates and eastern Syria hub.', false, array( 'avs-rana-raqqa', 'avs-buthaina-east' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-suwayda', 'האשכול כולל מרכז אזורי לא-סווידא.', 'The cluster includes a Suwayda regional hub.', false, array( 'avs-ghaimana-suwayda' ), 'official_source' ),
		$c99_relation( 'contains', 'region-syria-hauran', 'האשכול כולל מרכז אזורי לחוראן.', 'The cluster includes a Hauran regional hub.', false, array( 'avs-shahla-hauran' ), 'official_source' ),
	),
	'compliance' => array(
		$c99_compliance( 'raw-kibbeh-historical-only', 'אין לפרסם גרסה נאה, הוראות הכנה או המלצת צריכה. בשר טחון יטופל לפי תוכנית היגיינה והנחיית טמפרטורה מאומתת.', 'Do not publish a raw version, preparation instructions or consumption recommendation. Ground meat must follow a hygiene plan and validated temperature guidance.', array( 'cdc-raw-kibbeh-salmonella-2013', 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ), false ),
	),
	'prompt_en' => 'Private editorial atlas still life of Syrian regional foodways, nine distinct unlabeled table zones with representative grains, vegetables, dairy, fruit, fish and cooked dishes, documentary overhead light, no national iconography.',
) );

$c99_syrian_region_specs = array(
	array(
		'id' => 'region-syria-aleppo', 'slug' => 'aleppo', 'region' => 'aleppo',
		'name_he' => 'חלב: עיר של קובה, פלפל ופירות חמוצים', 'name_en' => 'Aleppo: a city of kibbeh, pepper and sour fruit',
		'summary_he' => 'בחלב הדרך אל השולחן עוברת דרך בורגול, פלפל אדום, אגוזים, רימון, חבוש ודובדבן חמוץ. מתחילים במשפחת הקובה החלבית, ממשיכים לחומרי הגלם ומגלים גם את מסורות האוכל של יהודי חלב שנישאו מן העיר אל התפוצות.',
		'summary_en' => 'Aleppo opens onto a table of bulgur, red pepper, nuts, pomegranate, quince and sour cherry. Begin with the Aleppine kibbeh family, continue to its ingredients and discover the Jewish foodways carried from the city into diaspora kitchens.',
		'fact_he' => 'אונסק״ו מתארת את חלב העתיקה כעיר שנבנתה בצומת נתיבי מסחר ושכבות תרבות. זה אינו מוכיח שמנה מסוימת נולדה בחלב, אבל הוא מסביר מדוע נכון לקרוא את מטבח העיר כמפגש מתמשך של חומרי גלם, קהילות ושיטות.',
		'fact_en' => 'UNESCO describes ancient Aleppo as a city shaped at the crossroads of trade routes and cultural layers. That does not prove that a particular dish originated in Aleppo, but it helps explain why the city cuisine is best explored as an evolving meeting of ingredients, communities and methods.',
		'sources' => array( 'unesco-ancient-city-aleppo', 'georgetown-making-levantine-cuisine', 'avs-mirvet-aleppo', 'aleppo-project-cuisine-2017' ),
		'intent_he' => 'לגלות את טעמי חלב דרך קובה, חומרי גלם ומסורות קהילתיות.',
		'intent_en' => 'Discover Aleppo through kibbeh, ingredients and community foodways.',
		'keyword_he' => 'המטבח החַלבי', 'keyword_en' => 'Aleppo cuisine',
		'visual' => 'an abundant Aleppo table with fully cooked kibbeh, separate bowls of fine bulgur and red pepper walnut spread, sour cherries, quince and warm flatbread, inviting natural window light and no labels',
	),
	array(
		'id' => 'region-syria-damascus', 'slug' => 'damascus', 'region' => 'damascus',
		'name_he' => 'דמשק', 'name_en' => 'Damascus',
		'summary_he' => 'מרכז מחקר למנות דמשקאיות של עלי גפן, כיסוני בצק, אורז, קובה ורטבי יוגורט או חמיצות, כולל שכבה נפרדת למסורת יהודי דמשק.',
		'summary_en' => 'A research hub for Damascene grape-leaf dishes, dumplings, rice, kibbeh and yogurt or sour sauces, including a separate layer for Damascene Jewish foodways.',
		'sources' => array( 'avs-razan-damascus', 'nli-damascus-tradition' ),
		'visual' => 'Damascus culinary research table with grape-leaf rolls, dumplings, rice parcel and cooked kibbeh in separate neutral dishes',
	),
	array(
		'id' => 'region-syria-homs', 'slug' => 'homs', 'region' => 'homs',
		'name_he' => 'חומס', 'name_en' => 'Homs',
		'summary_he' => 'מרכז מחקר לעדות אוכל מחומס, ובה קובות מג׳ריש, לבניה, קובה בחמוד, מונֶה ביתית וחלאוות אל-ג׳בן. טענת המוצא של המתוק נשמרת כעדות מקומית לצד עדות חמה ואינה מוצגת כפסק דין היסטורי.',
		'summary_en' => 'A research hub for Homs food testimony, including jreesh kibbeh, labaniyyeh, kibbeh b hamod, household mouneh and halawet al-jibn. The sweet\'s origin claim remains local testimony beside the Hama account rather than a historical verdict.',
		'sources' => array( 'avs-nariman-homs' ),
		'visual' => 'Homs culinary research table with separate fully cooked jreesh kibbeh in yogurt, kibbeh in pomegranate and tomato broth, household mouneh jars and a restrained cheese and semolina sweet study',
	),
	array(
		'id' => 'region-syria-hama', 'slug' => 'hama', 'region' => 'hama',
		'name_he' => 'חמה', 'name_en' => 'Hama',
		'summary_he' => 'מרכז מחקר לעדות אוכל מחמה, ובה באטרש ומסורת מקומית הטוענת לקשר לחלאוות אל-ג׳בן. ההבחנה מחומס נשמרת במפורש.',
		'summary_en' => 'A research hub for Hama food testimony, including batersh and a local account linking the city to halawet al-jibn. The distinction from Homs remains explicit.',
		'sources' => array( 'avs-noor-hama' ),
		'visual' => 'Hama culinary research table with charred eggplant, tahini and a separate cheese and semolina sweet study',
	),
	array(
		'id' => 'region-syria-coast', 'slug' => 'syrian-coast', 'region' => 'syrian-coast',
		'name_he' => 'החוף הסורי', 'name_en' => 'Syrian Coast',
		'summary_he' => 'מרכז מחקר למטבח החוף, עם דגים, בצל מושחם, פלפל חריף, לימון וירק עלים. זהות מין הדג נשארת תלויה במנה, עונה ומקור.',
		'summary_en' => 'A research hub for coastal cooking with fish, browned onion, hot pepper, lemon and leafy greens. Fish species identity remains dish, season and source specific.',
		'sources' => array( 'avs-zainab-coast' ),
		'visual' => 'Syrian coastal culinary research table with fully cooked fish, browned onion rice, red pepper sauce and chard, no identifiable commercial species claim',
	),
	array(
		'id' => 'region-syria-jazira', 'slug' => 'jazira-qamishli', 'region' => 'jazira',
		'name_he' => 'ג׳זירה וקמישלי', 'name_en' => 'Jazira and Qamishli',
		'summary_he' => 'מרכז מחקר למסורות משפחתיות מג׳זירה וקמישלי. עדותה של סמאר מסומנת כמסירה משפחתית מדור שני, משום שהיא מציינת שלא חיה בקמישלי.',
		'summary_en' => 'A research hub for family traditions associated with Jazira and Qamishli. Samar\'s account is marked as second-generation family transmission because she states that she did not live in Qamishli.',
		'sources' => array( 'avs-samar-qamishli' ),
		'visual' => 'Jazira family-transmission research table with small cooked kubaybat and a separate haqt component, presented as an editorial reconstruction target',
	),
	array(
		'id' => 'region-syria-euphrates-east', 'slug' => 'euphrates-and-east', 'region' => 'euphrates-east',
		'name_he' => 'הפרת ומזרח סוריה', 'name_en' => 'Euphrates and Eastern Syria',
		'summary_he' => 'מרכז מחקר לעדויות מרקה, דיר א-זור ומזרח סוריה, כולל תריד ופאורה. הוא אינו מאחד אוטומטית את כל נוסחי האזור.',
		'summary_en' => 'A research hub for testimony from Raqqa, Deir ez-Zor and eastern Syria, including thareed and fawra. It does not automatically merge every local version.',
		'sources' => array( 'avs-rana-raqqa', 'avs-buthaina-east' ),
		'visual' => 'Euphrates and eastern Syrian culinary research table with bread and broth thareed and a separate fawra dish, both fully cooked',
	),
	array(
		'id' => 'region-syria-suwayda', 'slug' => 'suwayda', 'region' => 'suwayda',
		'name_he' => 'א-סווידא', 'name_en' => 'Suwayda',
		'summary_he' => 'מרכז מחקר למסורות א-סווידא והקהילה הדרוזית, ובכללן נוסח מליחי המבוסס בעדות המתועדת על יוגורט טרי ואגוזים.',
		'summary_en' => 'A research hub for Suwayda and Druze foodways, including a documented Mleihi preparation based on fresh yogurt and nuts.',
		'sources' => array( 'avs-ghaimana-suwayda' ),
		'visual' => 'Suwayda culinary research table with a fresh yogurt and nut Mleihi preparation, bread and cooked meat, components clearly separated',
	),
	array(
		'id' => 'region-syria-hauran', 'slug' => 'hauran-daraa', 'region' => 'hauran',
		'name_he' => 'חוראן ודרעא', 'name_en' => 'Hauran and Daraa',
		'summary_he' => 'מרכז מחקר למסורות חוראן ודרעא, ובכללן נוסח מליחי עם יוגורט מיובש או ג׳מיד וקובה מטוגנת. המונחים ג׳מיד והיגט אינם מאוחדים ללא ראיה.',
		'summary_en' => 'A research hub for Hauran and Daraa foodways, including a Mleihi preparation with dried yogurt or jameed and fried kibbeh. The terms jameed and higet are not merged without evidence.',
		'sources' => array( 'avs-shahla-hauran', 'avs-ghaimana-suwayda' ),
		'visual' => 'Hauran culinary research table with dried-yogurt sauce, fully cooked fried kibbeh, bread and cooked meat, each component visible',
	),
);

foreach ( $c99_syrian_region_specs as $region_spec ) {
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $region_spec['id'],
		'type' => 'topic_hub',
		'slug' => $region_spec['slug'],
		'parent_id' => 'cuisine-syrian-regional',
		'name' => $c99_text( $region_spec['name_he'], $region_spec['name_en'] ),
		'summary' => $c99_text( $region_spec['summary_he'], $region_spec['summary_en'] ),
		'region' => $region_spec['region'],
		'primary_intent' => $c99_text(
			isset( $region_spec['intent_he'] ) ? $region_spec['intent_he'] : 'להכיר מנות, חומרי גלם וקהילות של ' . $region_spec['name_he'] . ' בלי למחוק הבדלים מקומיים.',
			isset( $region_spec['intent_en'] ) ? $region_spec['intent_en'] : 'Explore dishes, ingredients and communities of ' . $region_spec['name_en'] . ' without erasing local differences.'
		),
		'primary_keyword' => $c99_text(
			isset( $region_spec['keyword_he'] ) ? $region_spec['keyword_he'] : 'המטבח של ' . $region_spec['name_he'],
			isset( $region_spec['keyword_en'] ) ? $region_spec['keyword_en'] : $region_spec['name_en'] . ' regional cuisine'
		),
		'schema_type' => 'CollectionPage',
		'facts' => array(
			$c99_syrian_fact(
				'fact-' . $region_spec['slug'] . '-regional-testimony-boundary', 'cultural',
				isset( $region_spec['fact_he'] ) ? $region_spec['fact_he'] : 'המרכז האזורי מבוסס על מקור או עדות מזוהים. הוא מתעד נקודת מבט תחומה ואינו טוען שכל משקי הבית באזור מבשלים באותו אופן.',
				isset( $region_spec['fact_en'] ) ? $region_spec['fact_en'] : 'This regional hub is based on identified sources or testimony. It documents a bounded viewpoint and does not claim that every household in the region cooks in the same way.',
				'official_source', 'category', $region_spec['sources']
			),
		),
		'prompt_en' => ( 'region-syria-aleppo' === $region_spec['id'] ? 'Commercial culinary editorial photograph of ' : 'Private editorial atlas plate for ' ) . $region_spec['visual'] . ', accurate food textures and no decorative stereotypes.',
	) );
}

$c99_syrian_dish_specs = array(
	array(
		'id' => 'dish-muhammara-syrian', 'slug' => 'muhammara-syrian', 'parent' => 'cuisine-syrian-regional', 'region' => 'aleppo-and-homs', 'community' => 'syrian-multi-community',
		'name_he' => 'מוחמרה בעדויות מחלב ומחומס', 'name_en' => 'Muhammara in Aleppo and Homs Testimonies',
		'summary_he' => 'מוחמרה מופיעה בעדות מחומס כחלק מן המונֶה הביתית ובביבליוגרפיה של מחקר על המטבח החַלבי. המקורות הרשומים אינם קובעים נוסחת רכיבים משותפת לשתי הערים.',
		'summary_en' => 'Muhammara appears in Homs testimony as part of household mouneh and in the bibliography of an Aleppo cuisine study. The listed sources do not establish one shared ingredient formula for both cities.',
		'fact_he' => 'עדות חומס מתעדת מוחמרה במזווה הביתי, ומחקר המטבח החַלבי מפנה בביבליוגרפיה למאמר נפרד על מוחמרה. אף אחד מן הקשרים האלה אינו משמש כאן כמפרט רכיבים.',
		'fact_en' => 'The Homs testimony records muhammara in the household pantry, while the Aleppo cuisine study points in its bibliography to a separate muhammara article. Neither connection is used here as an ingredient specification.',
		'sources' => array( 'aleppo-project-cuisine-2017', 'avs-nariman-homs' ),
		'ingredients' => array(),
		'visual' => 'a restrained documentary bowl of muhammara with no ingredient callouts, garnish or formula claim',
		'extra_relations' => array(
			$c99_relation( 'references', 'region-syria-aleppo', 'המחקר החַלבי מפנה בביבליוגרפיה למאמר נפרד על מוחמרה בלי לספק כאן נוסחת רכיבים.', 'The Aleppo study points in its bibliography to a separate muhammara article without supplying an ingredient formula here.', false, array( 'aleppo-project-cuisine-2017' ), 'official_source' ),
			$c99_relation( 'references', 'region-syria-homs', 'עדות חומס מתעדת מוחמרה כחלק מן המונֶה הביתית.', 'The Homs testimony documents muhammara as part of household mouneh.', false, array( 'avs-nariman-homs' ), 'official_source' ),
		),
	),
	array(
		'id' => 'dish-kibbeh-meshwiyyeh', 'slug' => 'kibbeh-meshwiyyeh', 'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'syrian-multi-community',
		'name_he' => 'קובה משוויה', 'name_en' => 'Kibbeh Meshwiyyeh',
		'summary_he' => 'קובה חלבית צלויה שבה מעטפת בורגול ובשר ומילוי מתועדים כמבנה נפרד. מודל הבטיחות דורש גרסה מבושלת לחלוטין בלבד.',
		'summary_en' => 'An Aleppan grilled kibbeh whose bulgur-meat shell and filling are documented as separate structures. The safety model permits only a fully cooked version.',
		'fact_he' => 'עדותה של מירוות מתארת קובה משוויה כמבנה ממולא הנצלה; היא אינה סמכות לטמפרטורת מוצר מסחרי או למתכון נא.',
		'fact_en' => 'Mirvet\'s testimony describes kibbeh meshwiyyeh as a filled, grilled form; it is not authority for a commercial-product temperature or a raw recipe.',
		'sources' => array( 'avs-mirvet-aleppo' ),
		'ingredients' => array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-unspecified-nuts' ),
		'ingredient_sources' => array(
			'ingredient-syrian-bulgur' => array( 'avs-mirvet-aleppo' ),
			'ingredient-syrian-red-meat' => array( 'avs-mirvet-aleppo' ),
			'ingredient-syrian-unspecified-nuts' => array( 'avs-mirvet-aleppo' ),
		),
		'visual' => 'fully cooked round grilled kibbeh with a browned exterior and one cut piece showing a distinct cooked filling, no raw center',
	),
	array(
		'id' => 'dish-kibbeh-safarjaliyyeh', 'slug' => 'kibbeh-safarjaliyyeh', 'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'syrian-multi-community',
		'name_he' => 'קובה ספרג׳ליה', 'name_en' => 'Kibbeh Safarjaliyyeh',
		'summary_he' => 'תבשיל קובה חַלבי עם חבוש ורוטב חמוץ-מתוק. החבוש הוא זהות חומר גלם עצמאית ולא כינוי כללי לכל פרי חמוץ.',
		'summary_en' => 'An Aleppan kibbeh stew with quince and a sweet-sour sauce. Quince remains a distinct ingredient identity rather than a generic label for any sour fruit.',
		'fact_he' => 'המקור החַלבי מתאר שילוב של קובה וחבוש; דרגת החמיצות והמתיקות נשארת תלויה בנוסח ובפרי.',
		'fact_en' => 'The Aleppo source describes a combination of kibbeh and quince; sourness and sweetness remain version and fruit dependent.',
		'sources' => array( 'aleppo-project-cuisine-2017' ),
		'ingredients' => array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-quince' ),
		'visual' => 'fully cooked kibbeh pieces in a glossy quince stew with clearly identifiable quince chunks and no unsourced garnish',
	),
	array(
		'id' => 'dish-kebab-bil-karaz', 'slug' => 'kebab-bil-karaz', 'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'syrian-multi-community',
		'name_he' => 'קבב ביל כרז', 'name_en' => 'Kebab Bil Karaz',
		'summary_he' => 'קבב חַלבי מבושל ברוטב דובדבנים חמוצים. הרשומה מבחינה בין זהות הפרי, איזון הרוטב ובישול הבשר.',
		'summary_en' => 'An Aleppan kebab cooked with a sour-cherry sauce. The record separates fruit identity, sauce balance and meat cooking.',
		'fact_he' => 'המקור החַלבי קושר את המנה לקבב ולדובדבן חמוץ; מחקר החומצות מתאר את קטגוריית הפרי ואינו מודד את המנה.',
		'fact_en' => 'The Aleppo source links the dish to kebab and sour cherry; organic-acid research describes the fruit category and does not measure the dish.',
		'sources' => array( 'aleppo-project-cuisine-2017', 'sour-cherry-organic-acids-2020' ),
		'ingredients' => array( 'ingredient-syrian-red-meat', 'ingredient-syrian-sour-cherry' ),
		'ingredient_sources' => array(
			'ingredient-syrian-red-meat' => array( 'aleppo-project-cuisine-2017' ),
			'ingredient-syrian-sour-cherry' => array( 'aleppo-project-cuisine-2017' ),
		),
		'visual' => 'small fully cooked kebab pieces coated in a deep ruby sour-cherry sauce with visible whole pitted cherries and no unsourced garnish',
	),
	array(
		'id' => 'dish-lahm-bi-ajin-syria', 'slug' => 'lahm-bi-ajin-syria', 'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'syrian-multi-community',
		'name_he' => 'לחם בעג׳ין סורי', 'name_en' => 'Syrian Lahm Bi Ajin',
		'summary_he' => 'מאפה שטוח עם בשר מתובל ורכיב רימונים בגרסה משפחתית שמסלולה מתחיל בחלב. המסורת המשפחתית אינה מוגדרת כאן כנוסח היחיד של המנה.',
		'summary_en' => 'A flatbread with seasoned meat and a pomegranate component in a family version whose journey begins in Aleppo. The family tradition is not treated as the dish\'s only form.',
		'fact_he' => 'Jewish Food Society מתעדת לחם בעג׳ין משפחתי עם בשר, בצל, מולסת רימונים ורסק עגבניות במסלול משפחתי שמתחיל בחלב.',
		'fact_en' => 'Jewish Food Society documents a family lahm bi ajin with meat, onion, pomegranate molasses and tomato paste in a family journey beginning in Aleppo.',
		'sources' => array( 'jfs-lahm-bajin' ),
		'ingredients' => array( 'ingredient-syrian-red-meat', 'ingredient-syrian-pomegranate-molasses', 'ingredient-syrian-tomato-paste', 'ingredient-syrian-onion' ),
		'ingredient_sources' => array(
			'ingredient-syrian-red-meat' => array( 'jfs-lahm-bajin' ),
			'ingredient-syrian-pomegranate-molasses' => array( 'jfs-lahm-bajin' ),
			'ingredient-syrian-tomato-paste' => array( 'jfs-lahm-bajin' ),
			'ingredient-syrian-onion' => array( 'jfs-lahm-bajin' ),
		),
		'visual' => 'thin oval flatbread with an even fully cooked minced-meat topping and subtle pomegranate component, crisp rim, no branded paper',
	),
	array(
		'id' => 'dish-kibbeh-labaniyyeh', 'slug' => 'kibbeh-labaniyyeh', 'parent' => 'region-syria-homs', 'region' => 'homs', 'community' => 'syrian-multi-community',
		'name_he' => 'קובה לבניה', 'name_en' => 'Kibbeh Labaniyyeh',
		'summary_he' => 'קובה מבושלת ברוטב יוגורט, עם הבחנה בין יציבות הרוטב, בישול הקובה וזהות מוצר החלב.',
		'summary_en' => 'Cooked kibbeh in yogurt sauce, with separate attention to sauce stability, kibbeh cooking and dairy-product identity.',
		'fact_he' => 'עדות חומס מתארת קובה לבניה מג׳ריש המתבשלת ביוגורט עם מעט אורז למרקם. ספרות מבנה החלבון מספקת הקשר קטגוריה בלבד ואינה מתכון או בדיקת אצווה.',
		'fact_en' => 'The Homs testimony describes jreesh-based kibbeh labaniyyeh cooked in yogurt with a little rice for texture. Protein-structure literature supplies category context only, not a recipe or lot test.',
		'sources' => array( 'avs-nariman-homs', 'yogurt-protein-structure-2023' ),
		'ingredients' => array( 'ingredient-syrian-jreesh', 'ingredient-syrian-red-meat', 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-rice' ),
		'ingredient_sources' => array(
			'ingredient-syrian-jreesh' => array( 'avs-nariman-homs' ),
			'ingredient-syrian-red-meat' => array( 'avs-nariman-homs' ),
			'ingredient-syrian-fresh-yogurt' => array( 'avs-nariman-homs' ),
			'ingredient-syrian-rice' => array( 'avs-nariman-homs' ),
		),
		'visual' => 'fully cooked kibbeh in a smooth pale yogurt sauce, intact pieces, gentle steam and no raw meat cue',
	),
	array(
		'id' => 'dish-kibbeh-b-hamod', 'slug' => 'kibbeh-b-hamod', 'parent' => 'region-syria-homs', 'region' => 'homs', 'community' => 'syrian-multi-community',
		'name_he' => 'קובה בחמוד', 'name_en' => 'Kibbeh B Hamod',
		'summary_he' => 'קובה חמוצה מחומס, המתועדת ברוטב של מים, מולסת רימונים ורסק עגבניות. הריכוז והיחסים דורשים ניסוי נפרד.',
		'summary_en' => 'A sour kibbeh from Homs documented in a broth of water, pomegranate molasses and tomato paste. Concentration and ratios require a separate trial.',
		'fact_he' => 'עדות חומס מתארת קובה בחמוד מג׳ריש ברוטב מים, מולסת רימונים ורסק עגבניות. אין להחליף את מחוללי החמיצות בלימון, תמרהינדי או סומאק בלי מקור לגרסה אחרת.',
		'fact_en' => 'The Homs testimony describes jreesh-based kibbeh b hamod in a broth of water, pomegranate molasses and tomato paste. Lemon, tamarind or sumac must not replace those souring components without a source for another version.',
		'sources' => array( 'avs-nariman-homs' ),
		'ingredients' => array( 'ingredient-syrian-jreesh', 'ingredient-syrian-red-meat', 'ingredient-syrian-pomegranate-molasses', 'ingredient-syrian-tomato-paste' ),
		'visual' => 'fully cooked kibbeh pieces in a tart pomegranate-molasses and tomato broth, no lemon, tamarind or sumac substitution cue',
	),
	array(
		'id' => 'dish-yabraq-yebra', 'slug' => 'yabraq-yebra', 'parent' => 'cuisine-syrian-regional', 'region' => 'damascus-and-aleppo-diaspora', 'community' => 'syrian-multi-community',
		'name_he' => 'יבראק ויברה', 'name_en' => 'Yabraq and Yebra',
		'summary_he' => 'עלי גפן ממולאים בנוסחים דמשקאיים ומשפחתיים. הרשומה שומרת את הכתיבים יבראק ויברה כמונחי חיפוש קשורים בלי להניח שכל המילויים זהים.',
		'summary_en' => 'Stuffed grape leaves in Damascene and family versions. The record keeps Yabraq and Yebra as related search terms without assuming identical fillings.',
		'fact_he' => 'העדות הדמשקאית ומקור משפחתי יהודי-סורי מתעדים עלי גפן ממולאים; מקור המשפחה כולל משמשים ואינו מייצג כל נוסח.',
		'fact_en' => 'Damascene testimony and a Syrian Jewish family source document stuffed grape leaves; the family source includes apricots and does not represent every version.',
		'sources' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ),
		'ingredients' => array( 'ingredient-syrian-grape-leaves', 'ingredient-syrian-rice' ),
		'ingredient_sources' => array(
			'ingredient-syrian-grape-leaves' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ),
			'ingredient-syrian-rice' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ),
		),
		'visual' => 'neatly rolled cooked grape leaves arranged in a single layer, visible rice filling in one cut roll, no generic Mediterranean garnish',
	),
	array(
		'id' => 'dish-yalangi', 'slug' => 'yalangi-syrian', 'parent' => 'region-syria-damascus', 'region' => 'damascus', 'community' => 'syrian-multi-community',
		'name_he' => 'ילנג׳י סורי', 'name_en' => 'Syrian Yalangi',
		'summary_he' => 'עלי גפן ממולאים ללא בשר בנוסח המתועד, עם אורז ורכיבי חמיצות. הרשומה מפרידה ילנג׳י מיבראק במקום לאחד את השמות.',
		'summary_en' => 'Meatless stuffed grape leaves in the documented version, with rice and souring components. The record keeps yalangi distinct from yabraq rather than merging the names.',
		'fact_he' => 'העדות הדמשקאית משתמשת בילנג׳י לזהות מנה נפרדת; ההבחנה נשמרת גם כאשר שתי המנות משתמשות בעלי גפן.',
		'fact_en' => 'The Damascene testimony uses yalangi for a distinct dish identity; that distinction is retained even though both dishes use grape leaves.',
		'sources' => array( 'avs-razan-damascus' ),
		'ingredients' => array( 'ingredient-syrian-grape-leaves', 'ingredient-syrian-rice', 'ingredient-syrian-pomegranate-molasses' ),
		'visual' => 'small meatless cooked grape-leaf rolls with a visible rice filling and glossy sour dressing, presented separately from yabraq',
	),
	array(
		'id' => 'dish-shishbarak', 'slug' => 'shishbarak-damascus', 'parent' => 'region-syria-damascus', 'region' => 'damascus', 'community' => 'syrian-multi-community',
		'name_he' => 'שישברק דמשקאי', 'name_en' => 'Damascene Shishbarak',
		'summary_he' => 'כיסוני בצק ממולאים המבושלים ומוגשים ברוטב יוגורט. מבנה הכיסון, בישול המילוי ויציבות הרוטב נשמרים כשכבות נפרדות.',
		'summary_en' => 'Filled dumplings cooked and served in yogurt sauce. Dumpling structure, filling cook and sauce stability remain separate layers.',
		'fact_he' => 'העדות הדמשקאית מתעדת שישברק כחיבור בין כיסון ממולא לרוטב יוגורט; היא אינה מפרט ייצור מסחרי.',
		'fact_en' => 'The Damascene testimony documents shishbarak as a pairing of filled dumplings and yogurt sauce; it is not a commercial production specification.',
		'sources' => array( 'avs-razan-damascus' ),
		'ingredients' => array( 'ingredient-syrian-red-meat', 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-samn' ),
		'visual' => 'small fully cooked folded dumplings in a smooth yogurt sauce, one dumpling opened to show a cooked filling',
	),
	array(
		'id' => 'dish-ouzi-damascene', 'slug' => 'ouzi-damascene', 'parent' => 'region-syria-damascus', 'region' => 'damascus', 'community' => 'syrian-multi-community',
		'name_he' => 'אוזי דמשקאי', 'name_en' => 'Damascene Ouzi',
		'summary_he' => 'מנת אורז דמשקאית בנוסח ארוז או חגיגי, עם בשר ואפונה ירוקה לפי העדות. צורת המעטפת והמילוי נשארת תלויה בגרסה.',
		'summary_en' => 'A Damascene rice dish in parcel or celebratory forms, with meat and green peas in the cited account. Wrapper and filling remain version dependent.',
		'fact_he' => 'העדות הדמשקאית מתעדת אוזי כחלק מרפרטואר אורז ואירוח; אין להסיק ממנה משקל מנה או נוסחת מילוי אחידה.',
		'fact_en' => 'The Damascene testimony documents ouzi within a rice and hospitality repertoire; it does not establish a universal portion weight or filling formula.',
		'sources' => array( 'avs-razan-damascus' ),
		'ingredients' => array( 'ingredient-syrian-rice', 'ingredient-syrian-red-meat', 'ingredient-syrian-green-peas' ),
		'visual' => 'a fully cooked Damascene ouzi rice parcel opened to show separate rice, meat and green pea components, no banquet excess',
	),
	array(
		'id' => 'dish-matzah-kebab-damascene-jewish', 'slug' => 'matzah-kebab-damascene-jewish', 'parent' => 'region-syria-damascus', 'region' => 'damascus', 'community' => 'damascene-jewish',
		'name_he' => 'קבב מצה של יהודי דמשק', 'name_en' => 'Damascene Jewish Matzah Kebab',
		'summary_he' => 'מנה משפחתית יהודית-דמשקאית המתועדת במקור קהילתי. היא מוצגת כחלק מהמטבח הסורי הרב-קהילתי ולא כמייצגת שלו לבדה.',
		'summary_en' => 'A Damascene Jewish family dish documented by a community source. It appears as one part of multi-community Syrian cuisine, not as its sole representative.',
		'fact_he' => 'FOODISH מייחס את קבב המצה למסורת משפחתית של יהודי דמשק; היקף הטענה הוא משפחתי וקהילתי.',
		'fact_en' => 'FOODISH attributes matzah kebab to a Damascene Jewish family tradition; the claim scope is family and community specific.',
		'sources' => array( 'foodish-matzah-kebab' ),
		'ingredients' => array( 'ingredient-syrian-matzah', 'ingredient-syrian-red-meat', 'ingredient-syrian-lemon' ),
		'ingredient_sources' => array(
			'ingredient-syrian-matzah' => array( 'foodish-matzah-kebab' ),
			'ingredient-syrian-red-meat' => array( 'foodish-matzah-kebab' ),
			'ingredient-syrian-lemon' => array( 'foodish-matzah-kebab' ),
		),
		'visual' => 'fully cooked compact matzah kebab portions from a Damascene Jewish family context, simple serving plate and no ritual props',
	),
	array(
		'id' => 'dish-batersh-hama', 'slug' => 'batersh-hama', 'parent' => 'region-syria-hama', 'region' => 'hama', 'community' => 'syrian-multi-community',
		'name_he' => 'באטרש מחמה', 'name_en' => 'Hama Batersh',
		'summary_he' => 'מנה מחמה הבנויה מחציל קלוי, טחינה ורכיב בשרי מתועד. קליית החציל והרכבת השכבות נבחנות בנפרד.',
		'summary_en' => 'A Hama dish built from charred eggplant, tahini and a documented meat component. Eggplant charring and layer assembly are considered separately.',
		'fact_he' => 'העדות מחמה מציגה באטרש כמנה מקומית בעלת בסיס חציל וטחינה ורכיב בשרי; היחסים אינם מפרט אחיד.',
		'fact_en' => 'The Hama testimony presents batersh as a local dish with an eggplant-tahini base and a meat component; ratios are not a universal specification.',
		'sources' => array( 'avs-noor-hama' ),
		'ingredients' => array( 'ingredient-syrian-eggplant', 'ingredient-syrian-tahini', 'ingredient-syrian-red-meat' ),
		'visual' => 'a layered Hama batersh plate with smoky eggplant and tahini base under a fully cooked minced-meat component, layers clearly visible',
	),
	array(
		'id' => 'dish-halawet-al-jibn', 'slug' => 'halawet-al-jibn', 'parent' => 'cuisine-syrian-regional', 'region' => 'homs-hama-contested', 'community' => 'syrian-multi-community',
		'name_he' => 'חלאוות אל-ג׳בן', 'name_en' => 'Halawet Al Jibn',
		'summary_he' => 'ממתק גבינה וסולת המקושר בעדויות בעל פה גם לחומס וגם לחמה. הרשומה מציגה את המחלוקת ואינה פוסקת עיר מוצא.',
		'summary_en' => 'A cheese and semolina sweet linked by oral accounts to both Homs and Hama. The record presents the dispute and does not declare a birthplace.',
		'fact_he' => 'נרימאן מייחסת את המנה לחומס, ואילו נור מייחסת אותה לחמה. שתי העדויות נשמרות זו לצד זו ללא הכרעת מוצא.',
		'fact_en' => 'Nariman attributes the dish to Homs, while Noor attributes it to Hama. Both accounts remain side by side without an origin verdict.',
		'sources' => array( 'avs-nariman-homs', 'avs-noor-hama' ),
		'ingredients' => array(),
		'visual' => 'a split research composition keeping the Homs cheese, semolina, cream and syrup account separate from the Hama qeshta, delicate dough and pistachio account, with no origin verdict',
		'extra_relations' => array(
			$c99_relation( 'references', 'region-syria-homs', 'עדות חומס נשמרת לצד עדות חמה ללא הכרעה.', 'The Homs account remains alongside the Hama account without a verdict.', false, array( 'avs-nariman-homs' ), 'official_source' ),
			$c99_relation( 'references', 'region-syria-hama', 'עדות חמה נשמרת לצד עדות חומס ללא הכרעה.', 'The Hama account remains alongside the Homs account without a verdict.', false, array( 'avs-noor-hama' ), 'official_source' ),
		),
	),
	array(
		'id' => 'dish-sayadiyah-syrian-coast', 'slug' => 'sayadiyah-syrian-coast', 'parent' => 'region-syria-coast', 'region' => 'syrian-coast', 'community' => 'syrian-multi-community',
		'name_he' => 'סיאדיה מהחוף הסורי', 'name_en' => 'Syrian Coastal Sayadiyah',
		'summary_he' => 'מנת דג ואורז מהחוף שבה השחמת בצל היא שכבת טעם ומבנה. מין הדג דורש זיהוי נפרד בכל גרסה או מוצר.',
		'summary_en' => 'A coastal fish-and-rice dish in which browned onion is a flavor and structure layer. Fish species requires separate identification for every version or product.',
		'fact_he' => 'העדות מהחוף מתעדת סיאדיה עם דג, אורז ובצל; היא אינה מאשרת שכל מין דג מתאים או זהה.',
		'fact_en' => 'The coastal testimony documents sayadiyah with fish, rice and onion; it does not establish that every fish species is suitable or equivalent.',
		'sources' => array( 'avs-zainab-coast' ),
		'ingredients' => array( 'ingredient-syrian-coastal-fish', 'ingredient-syrian-rice', 'ingredient-syrian-olive-oil', 'ingredient-syrian-onion' ),
		'visual' => 'fully cooked fish on deeply browned onion rice, fish species left visually non-specific, no raw flesh and no lemon garnish unless separately sourced',
	),
	array(
		'id' => 'dish-samaka-harra-baniyas', 'slug' => 'samaka-harra-baniyas', 'parent' => 'region-syria-coast', 'region' => 'baniyas-coast', 'community' => 'syrian-multi-community',
		'name_he' => 'סמכה חרה מבניאס', 'name_en' => 'Baniyas Samaka Harra',
		'summary_he' => 'דג חריף מהקשר בניאס והחוף, עם פלפל ורכיבי חמיצות לפי העדות. חריפות, מין דג ורוטב נשארים תלויי גרסה.',
		'summary_en' => 'A hot fish dish associated with Baniyas and the coast, with pepper and sour components in the testimony. Heat level, fish species and sauce remain version dependent.',
		'fact_he' => 'העדות מהחוף קושרת סמכה חרה לבניאס ולבישול דג חריף; אין להסיק ממנה זן פלפל או מין דג קבוע.',
		'fact_en' => 'The coastal testimony links samaka harra with Baniyas and hot fish cookery; it does not establish one pepper variety or fish species.',
		'sources' => array( 'avs-zainab-coast' ),
		'ingredients' => array( 'ingredient-syrian-coastal-fish', 'ingredient-syrian-red-pepper-paste', 'ingredient-syrian-lemon' ),
		'visual' => 'fully cooked whole or portioned fish under a red pepper and lemon sauce, coastal Syrian context, species visually non-specific',
	),
	array(
		'id' => 'dish-kibbat-al-silik', 'slug' => 'kibbat-al-silik', 'parent' => 'region-syria-coast', 'region' => 'syrian-coast', 'community' => 'syrian-multi-community',
		'name_he' => 'קובת א-סלק', 'name_en' => 'Kibbat Al Silik',
		'summary_he' => 'קובה עם סלק עלים מן העדות החופית. הרשומה מזהה את הירק כעלי מנגולד ולא כסלק שורש.',
		'summary_en' => 'A kibbeh preparation with chard from the coastal testimony. The vegetable is modeled as leafy Swiss chard rather than beetroot.',
		'fact_he' => 'העדות החופית מתעדת קובת א-סלק עם ירק עלים; המונח נשמר כישות מנגולד נפרדת מסלק שורש.',
		'fact_en' => 'The coastal testimony documents kibbat al silik with leafy greens; the term is retained as a Swiss-chard entity distinct from beetroot.',
		'sources' => array( 'avs-zainab-coast' ),
		'ingredients' => array( 'ingredient-syrian-swiss-chard', 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat' ),
		'visual' => 'fully cooked kibbeh with abundant dark green Swiss chard leaves visible in the dish, no beetroot color cue',
	),
	array(
		'id' => 'dish-thareed-raqqa-rural', 'slug' => 'thareed-raqqa-rural', 'parent' => 'region-syria-euphrates-east', 'region' => 'raqqa-countryside', 'community' => 'syrian-multi-community',
		'name_he' => 'תריד כפרי מרקה', 'name_en' => 'Rural Raqqa Thareed',
		'summary_he' => 'כיכרות לחם תנור שלמות הסופגות ציר כבש בעדות מן הכפרים של רקה. העדות מבחינה בין הנוסח הכפרי ללא אורז לבין נוסח עירוני שמוסיף אורז.',
		'summary_en' => 'Whole tannour loaves absorbing lamb broth in testimony from rural Raqqa. The account distinguishes a rural version without rice from a city version that adds rice.',
		'fact_he' => 'העדות הכפרית מרקה מתארת לחם תנור, כבש וציר, ולעיתים רוטב אדום מרסק עגבניות. היא אינה ראיה לנוסח של דיר א-זור או של כל אזור הפרת.',
		'fact_en' => 'The rural Raqqa testimony documents tannour bread, lamb and broth, sometimes with a red tomato-paste sauce. It is not evidence for a Deir ez-Zor version or for the entire Euphrates region.',
		'sources' => array( 'avs-rana-raqqa' ),
		'ingredients' => array( 'ingredient-raqqa-tannour-bread', 'ingredient-syrian-red-meat' ),
		'extra_relations' => array(
			$c99_relation( 'references', 'ingredient-syrian-tomato-paste', 'עדות רקה מציינת רסק עגבניות כתוספת אפשרית בחלק מן ההכנות, לא כרכיב חובה בתריד הכפרי.', 'The Raqqa account mentions tomato paste as an optional addition in some preparations, not as a required ingredient in rural thareed.', false, array( 'avs-rana-raqqa' ), 'official_source' ),
		),
		'visual' => 'whole thin tannour loaves visibly absorbing fully cooked lamb broth in a large rural Raqqa serving dish, no rice, chickpeas or decorative bread tower',
	),
	array(
		'id' => 'dish-fawra-deir', 'slug' => 'fawra-deir-ez-zor', 'parent' => 'region-syria-euphrates-east', 'region' => 'deir-ez-zor-east', 'community' => 'syrian-multi-community',
		'name_he' => 'פאורה מדיר א-זור', 'name_en' => 'Deir ez-Zor Fawra',
		'summary_he' => 'מנה ממזרח סוריה המתועדת בעדות של בות׳יינה. הרשומה שומרת את השם וההקשר בלי להרחיב מעבר לפרטים שבמקור.',
		'summary_en' => 'An eastern Syrian dish documented in Buthaina\'s account. The record preserves its name and context without extending beyond source details.',
		'fact_he' => 'פאורה נשמרת כמנה הקשורה לעדות מדיר א-זור ומזרח סוריה; פרטים שאינם במקור נשארים במצב מחקר.',
		'fact_en' => 'Fawra is retained as a dish associated with testimony from Deir ez-Zor and eastern Syria; details absent from the source remain under research.',
		'sources' => array( 'avs-buthaina-east' ),
		'ingredients' => array( 'ingredient-syrian-black-eyed-peas', 'ingredient-syrian-kishk', 'ingredient-syrian-onion', 'ingredient-syrian-samn' ),
		'visual' => 'fully cooked Deir ez-Zor fawra with visible black-eyed peas, softened kishk and finely chopped onions fried in clarified butter, no meat component',
	),
	array(
		'id' => 'dish-kubaybat-haqt-qamishli', 'slug' => 'kubaybat-haqt-qamishli', 'parent' => 'region-syria-jazira', 'region' => 'qamishli-jazira', 'community' => 'family-transmitted-qamishli',
		'name_he' => 'קובייבאת והקט מקמישלי', 'name_en' => 'Qamishli Kubaybat and Haqt',
		'summary_he' => 'מנה ומרכיב המתועדים במסירה משפחתית הקשורה לקמישלי. המקור מציין שהמספרת לא חיה בעיר, ולכן הייחוס מסומן כזיכרון משפחתי מדור שני.',
		'summary_en' => 'A dish and component documented through family transmission associated with Qamishli. The narrator states that she did not live there, so the attribution is marked as second-generation family memory.',
		'fact_he' => 'סמאר מתארת קובייבאת והקט דרך משפחתה ומציינת שלא חיה בקמישלי; הרשומה אינה הופכת את העדות לתצפית ישירה בעיר.',
		'fact_en' => 'Samar describes kubaybat and haqt through her family and states that she did not live in Qamishli; the record does not turn that account into direct observation in the city.',
		'sources' => array( 'avs-samar-qamishli' ),
		'ingredients' => array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-haqt' ),
		'visual' => 'small fully cooked kubaybat beside a separate haqt component, labeled only in metadata as a family-transmitted Qamishli reconstruction target',
	),
	array(
		'id' => 'dish-mansaf-mleihi', 'slug' => 'mansaf-mleihi', 'parent' => 'region-syria-hauran', 'region' => 'southern-syria', 'community' => 'syrian-multi-community',
		'name_he' => 'מנסף מליחי', 'name_en' => 'Mansaf Mleihi',
		'summary_he' => 'מנה דרום-סורית בעלת שתי הכנות מתועדות ונפרדות: בא-סווידא עם יוגורט טרי ואגוזים, ובחוראן עם יוגורט מיובש או ג׳מיד וקובה מטוגנת.',
		'summary_en' => 'A southern Syrian dish with two separately documented preparations: a Suwayda form with fresh yogurt and nuts, and a Hauran form with dried yogurt or jameed and fried kibbeh.',
		'fact_he' => 'עדות א-סווידא ועדות חוראן מציגות שתי הכנות שונות למליחי. הן נשמרות תחת מנה קנונית אחת אך אינן ממוזגות למתכון אחד.',
		'fact_en' => 'Suwayda and Hauran testimonies present two different Mleihi preparations. They remain under one canonical dish but are not merged into one recipe.',
		'sources' => array( 'avs-ghaimana-suwayda', 'avs-shahla-hauran' ),
		'ingredients' => array(),
		'visual' => 'a split research composition showing the Suwayda fresh-yogurt and nut preparation beside the Hauran dried-yogurt and fried-kibbeh preparation, no blending of components',
		'extra_facts' => array(
			$c99_syrian_fact( 'fact-mleihi-dairy-terms-not-aliases', 'structural', 'גהימאנה משתמשת בהיגט לתיאור מוצר יוגורט מיובש מדרעא, ושאהלה משתמשת בג׳מיד. המונחים נשמרים כישויות נפרדות עד להוכחת זהות.', 'Ghaimana uses higet for a dried-yogurt product from Daraa, while Shahla uses jameed. The terms remain separate entities until identity is demonstrated.', 'official_source', 'category', array( 'avs-ghaimana-suwayda', 'avs-shahla-hauran' ) ),
		),
		'extra_relations' => array(
			$c99_relation( 'references', 'region-syria-suwayda', 'הכנת א-סווידא נשמרת כווריאנט נפרד.', 'The Suwayda preparation remains a separate variant.', false, array( 'avs-ghaimana-suwayda' ), 'official_source' ),
		),
	),
	array(
		'id' => 'dish-kibbeh-hamda-aleppan-jewish', 'slug' => 'kibbeh-hamda-aleppan-jewish', 'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'aleppan-jewish',
		'name_he' => 'קובה חמודה של יהודי חלב', 'name_en' => 'Aleppan Jewish Kibbeh Hamda',
		'summary_he' => 'מרק קובה חמוץ ממסורת יהודי חלב, המתועד כסיפור משפחתי וקהילתי. הוא חלק מן הפסיפס הסורי ואינו מוצג כנוסח הכללי של חלב.',
		'summary_en' => 'A sour kibbeh soup from Aleppan Jewish foodways, documented through a family and community story. It is part of the Syrian mosaic, not the general version for all Aleppo.',
		'fact_he' => 'Jewish Food Society מתעדת קובה חמודה במסורת משפחתית של יהודי חלב ובהקשר של פסח והגירה לברוקלין.',
		'fact_en' => 'Jewish Food Society documents kibbeh hamda in an Aleppan Jewish family tradition, with Passover and migration-to-Brooklyn context.',
		'sources' => array( 'jfs-kibbeh-hamda' ),
		'ingredients' => array( 'ingredient-syrian-rice', 'ingredient-syrian-red-meat', 'ingredient-syrian-lemon' ),
		'ingredient_sources' => array(
			'ingredient-syrian-rice' => array( 'jfs-kibbeh-hamda' ),
			'ingredient-syrian-red-meat' => array( 'jfs-kibbeh-hamda' ),
			'ingredient-syrian-lemon' => array( 'jfs-kibbeh-hamda' ),
		),
		'visual' => 'fully cooked kibbeh in a clear sour soup from an Aleppan Jewish family context, simple bowl and no holiday props',
	),
	array(
		'id' => 'dish-dajaj-mashwi-aleppan-jewish', 'slug' => 'dajaj-mashwi-aleppan-jewish', 'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'aleppan-jewish',
		'name_he' => 'דג׳אג׳ משווי של יהודי חלב', 'name_en' => 'Aleppan Jewish Dajaj Mashwi',
		'summary_he' => 'עוף ממולא המתבשל באיטיות ממסורת משפחתית יהודית-חלבית. הרשומה מפרידה בין שכבת המסורת המשפחתית לבין התאמות ישראליות עכשוויות המופיעות במתכון שפורסם.',
		'summary_en' => 'Slow-cooked stuffed chicken from an Aleppan Jewish family tradition. The record separates the family-tradition layer from contemporary Israeli adaptations in the published recipe.',
		'fact_he' => 'מקור FOODISH משמר ייחוס משפחתי חַלבי אך כולל גם התאמות ישראליות עכשוויות. ההתאמות אינן מוצגות כראיה למנה היסטורית בחלב.',
		'fact_en' => 'The FOODISH source retains an Aleppan family attribution but also includes contemporary Israeli adaptations. Those adaptations are not treated as evidence for a historical Aleppo dish.',
		'sources' => array( 'foodish-dajaj-mashwi' ),
		'ingredients' => array( 'ingredient-syrian-whole-chicken', 'ingredient-syrian-rice', 'ingredient-syrian-red-meat', 'ingredient-syrian-olive-oil' ),
		'ingredient_sources' => array(
			'ingredient-syrian-whole-chicken' => array( 'foodish-dajaj-mashwi' ),
			'ingredient-syrian-rice' => array( 'foodish-dajaj-mashwi' ),
			'ingredient-syrian-red-meat' => array( 'foodish-dajaj-mashwi' ),
			'ingredient-syrian-olive-oil' => array( 'foodish-dajaj-mashwi' ),
		),
		'visual' => 'fully cooked roast chicken from an Aleppan Jewish family context, browned skin, rice and the source-documented accompaniments, no lemon, sumac, national symbols or unsourced garnish',
	),
	array(
		'id' => 'dish-kitawiyeh-afrin-kurdish', 'slug' => 'kitawiyeh-afrin-kurdish', 'parent' => 'region-syria-aleppo', 'region' => 'afrin', 'community' => 'kurdish-afrin',
		'name_he' => 'קיטאוויה כורדית מאפרין', 'name_en' => 'Kurdish Afrin Kitawiyeh',
		'summary_he' => 'מנה כורדית מאפרין כפי שתועדה בעדותה של אמאני. הרשומה מציגה מקור ישיר ומוגבל ואינה מרחיבה אותו לכל המטבח הכורדי.',
		'summary_en' => 'A Kurdish dish from Afrin as documented in Amani\'s testimony. The record presents a direct but bounded source and does not generalize it to all Kurdish cuisine.',
		'fact_he' => 'אמאני מתעדת קיטאוויה בהקשר משפחתי וכורדי מאפרין; פרטים מחוץ לעדות נשארים במחקר.',
		'fact_en' => 'Amani documents kitawiyeh in a Kurdish Afrin family context; details outside her account remain under research.',
		'sources' => array( 'avs-amani-afrin' ),
		'ingredients' => array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat', 'ingredient-syrian-samn' ),
		'visual' => 'private reconstruction study of fully cooked Kurdish Afrin kitawiyeh using only source-confirmed components, neutral family table and no costume cues',
	),
);

foreach ( $c99_syrian_dish_specs as $dish_spec ) {
	$facts = array(
		$c99_syrian_fact(
			'fact-' . $dish_spec['slug'] . '-documented-scope', 'cultural',
			$dish_spec['fact_he'], $dish_spec['fact_en'], 'official_source', 'entity', $dish_spec['sources']
		),
	);
	if ( isset( $dish_spec['extra_facts'] ) ) {
		$facts = array_merge( $facts, $dish_spec['extra_facts'] );
	}
	$c99_syrian_relations = array();
	foreach ( $dish_spec['ingredients'] as $ingredient_id ) {
		$ingredient_source_ids = isset( $dish_spec['ingredient_sources'][ $ingredient_id ] )
			? $dish_spec['ingredient_sources'][ $ingredient_id ]
			: $dish_spec['sources'];
		$c99_syrian_relations[] = $c99_relation(
			'requires', $ingredient_id,
			'חומר הגלם מקושר למבנה המתועד של המנה, אך זהות מוצר וכמות דורשות אימות נפרד.',
			'The ingredient is linked to the documented dish structure, while product identity and quantity require separate verification.',
			false, $ingredient_source_ids, 'official_source'
		);
	}
	if ( isset( $dish_spec['extra_relations'] ) ) {
		$c99_syrian_relations = array_merge( $c99_syrian_relations, $dish_spec['extra_relations'] );
	}
	$compliance = array();
	if ( in_array( 'ingredient-syrian-red-meat', $dish_spec['ingredients'], true ) ) {
		$compliance[] = $c99_compliance(
			'fully-cooked-ground-meat',
			'כל רכיב בשר טחון יוצג ויפותח בגרסה מבושלת לחלוטין לפי תוכנית היגיינה והנחיית טמפרטורה מאומתת. אין הוראות לגרסה נאה.',
			'Any ground-meat component must be presented and developed only as fully cooked under a hygiene plan and validated temperature guidance. No raw-version instructions are permitted.',
			array( 'foodsafety-safe-temperatures', 'cdc-raw-kibbeh-salmonella-2013', 'israel-moh-food-hygiene' ), false
		);
	}
	if ( in_array( 'ingredient-syrian-coastal-fish', $dish_spec['ingredients'], true ) ) {
		$compliance[] = $c99_compliance(
			'fully-cooked-fish-and-species-review',
			'יש לזהות מין דג, לבדוק אלרגן דגים ולפתח גרסה מבושלת לפי תוכנית בטיחות מזון.',
			'Identify the fish species, review the fish allergen and develop a cooked version under a food-safety plan.',
			array( 'foodsafety-safe-temperatures', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ), false
		);
	}
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $dish_spec['id'],
		'type' => 'dish',
		'slug' => $dish_spec['slug'],
		'parent_id' => $dish_spec['parent'],
		'name' => $c99_text( $dish_spec['name_he'], $dish_spec['name_en'] ),
		'summary' => $c99_text( $dish_spec['summary_he'], $dish_spec['summary_en'] ),
		'region' => $dish_spec['region'],
		'community' => $dish_spec['community'],
		'primary_intent' => $c99_text( 'להכיר את ' . $dish_spec['name_he'] . ', את ההקשר האזורי ואת חומרי הגלם המקושרים לפני ניסיון במטבח.', 'Understand ' . $dish_spec['name_en'] . ', its regional context and linked ingredients before a kitchen trial.' ),
		'primary_keyword' => $c99_text( $dish_spec['name_he'] . ' מהמטבח הסורי', $dish_spec['name_en'] . ' Syrian dish guide' ),
		'schema_type' => 'Article',
		'facts' => $facts,
		'relations' => $c99_syrian_relations,
		'cross_sell_ids' => $dish_spec['ingredients'],
		'compliance' => $compliance,
		'prompt_en' => 'Private editorial food study of ' . $dish_spec['visual'] . ', accurate portion scale, natural side light and a neutral tabletop.',
	) );
}

$c99_syrian_ingredient_specs = array(
	array( 'id' => 'ingredient-syrian-bulgur', 'slug' => 'syrian-bulgur', 'he' => 'בורגול לקובה ולמטבח הסורי', 'en' => 'Bulgur for Kibbeh and Syrian Cooking', 'region' => 'syria-national', 'summary_he' => 'בורגול הוא הגרגר שמעניק למעטפת הקובה את הגוף, האחיזה והמרקם. גודל הגרגר, משך ההשריה וכמות המים משנים את התוצאה, ולכן מתחילים בבורגול המתאים למנה ולא ביחס מים אחד שמתיימר להתאים לכולם.', 'summary_en' => 'Bulgur gives a kibbeh shell its body, cohesion and texture. Grain size, soaking time and water uptake change the result, so the journey begins with bulgur suited to the preparation rather than one ratio claimed to fit every grain.', 'fact_he' => 'בורגול מופיע במקורות החלביים כחומר גלם מרכזי במשפחת הקובה. הוא נשאר נפרד מג׳ריש ומפריקה, שנבדלים ממנו בשם, במבנה ובשימושים המתועדים.', 'fact_en' => 'Aleppine sources place bulgur at the heart of the kibbeh family. It remains distinct from jreesh and freekeh, which carry their own documented names, structures and uses.', 'sources' => array( 'avs-mirvet-aleppo', 'aleppo-project-cuisine-2017', 'simon-schuster-aleppo-cookbook' ), 'used_in' => 'dish-kibbeh-meshwiyyeh', 'used_in_sources' => array( 'avs-mirvet-aleppo' ), 'intent_he' => 'לבחור בורגול מתאים לקובה ולהבין כיצד גודל גרגר והשריה משפיעים על המרקם.', 'intent_en' => 'Choose bulgur for kibbeh and understand how grain size and hydration shape texture.', 'keyword_he' => 'בורגול לקובה', 'keyword_en' => 'bulgur for kibbeh', 'visual' => 'dry fine and medium bulgur grains in separate warm ceramic bowls, tactile grain detail, no hydrated grain and no packaging' ),
	array( 'id' => 'ingredient-syrian-jreesh', 'slug' => 'syrian-jreesh', 'he' => 'ג׳ריש במטבח הסורי', 'en' => 'Jreesh in Syrian Cuisine', 'region' => 'homs-and-southern-syria', 'fact_he' => 'ג׳ריש נשמר כחומר גלם בשם נפרד בעדויות מחומס ומדרום סוריה. המערכת אינה מחליפה אותו אוטומטית בבורגול.', 'fact_en' => 'Jreesh is retained as a separately named ingredient in testimony from Homs and southern Syria. The system does not automatically substitute bulgur for it.', 'sources' => array( 'avs-nariman-homs', 'avs-ghaimana-suwayda', 'avs-shahla-hauran' ), 'visual' => 'coarse jreesh grains in one plain bowl beside an empty comparison space, no bulgur label inside the image' ),
	array( 'id' => 'ingredient-syrian-freekeh', 'slug' => 'syrian-freekeh', 'he' => 'פריקה במטבח הסורי', 'en' => 'Freekeh in Syrian Cuisine', 'region' => 'northern-and-eastern-syria', 'fact_he' => 'פריקה מתועדת כחומר גלם גרעיני בשם עצמאי. היא נשמרת בנפרד מבורגול ומג׳ריש, וכל טענת עיבוד או מקור דורשת מוצר ומקור מתאימים.', 'fact_en' => 'Freekeh is documented as a separately named grain ingredient. It remains distinct from bulgur and jreesh, and every processing or origin claim requires appropriate product evidence.', 'sources' => array( 'avs-rahma-idlib', 'avs-heart-to-hearth' ), 'visual' => 'whole and cracked freekeh grains in unbranded bowls with visible green-brown variation, no origin seal' ),
	array( 'id' => 'ingredient-syrian-rice', 'slug' => 'syrian-rice', 'he' => 'אורז במטבח הסורי', 'en' => 'Rice in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'אורז מתועד במנות דמשקאיות, מזרחיות ודרומיות. זן, אורך גרגר ויחס מים אינם נקבעים מן הקטגוריה.', 'fact_en' => 'Rice is documented in Damascene, eastern and southern dishes. Cultivar, grain length and water ratio are not established by the category.', 'sources' => array( 'avs-razan-damascus', 'avs-buthaina-east', 'avs-ghaimana-suwayda' ), 'used_in' => 'dish-ouzi-damascene', 'used_in_sources' => array( 'avs-razan-damascus' ), 'visual' => 'uncooked white rice grains and a separate bowl of fully cooked rice, no cultivar claim' ),
	array( 'id' => 'ingredient-syrian-chickpeas', 'slug' => 'syrian-chickpeas', 'he' => 'חומוס יבש במטבח הסורי', 'en' => 'Dry Chickpeas in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'גרגרי חומוס מתועדים כרכיב במנות ובמזווה הסורי. זמן השריה ובישול תלוי במוצר ובשימוש.', 'fact_en' => 'Chickpeas are documented as a component of Syrian dishes and pantry practice. Soaking and cooking time depend on product and use.', 'sources' => array( 'avs-heart-to-hearth' ), 'visual' => 'dry chickpeas and fully cooked chickpeas in separate plain bowls, accurate size and texture' ),
	array( 'id' => 'ingredient-syrian-green-peas', 'slug' => 'damascene-ouzi-green-peas', 'he' => 'אפונה ירוקה באוזי דמשקאי', 'en' => 'Green Peas in Damascene Ouzi', 'region' => 'damascus', 'fact_he' => 'עדותה של רזאן מציינת אפונה ירוקה באוזי הדמשקאי. הרשומה אינה מחליפה אותה בחומוס ואינה קובעת אם המוצר טרי או קפוא.', 'fact_en' => 'Razan\'s testimony names green peas in Damascene ouzi. The record does not replace them with chickpeas or determine whether the product is fresh or frozen.', 'sources' => array( 'avs-razan-damascus' ), 'used_in' => 'dish-ouzi-damascene', 'used_in_sources' => array( 'avs-razan-damascus' ), 'visual' => 'plain green peas beside a fully cooked sample, no chickpeas and no package-form claim' ),
	array( 'id' => 'ingredient-syrian-lentils', 'slug' => 'syrian-lentils', 'he' => 'עדשים במטבח הסורי', 'en' => 'Lentils in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'עדשים מופיעות בעדויות אוכל סוריות כחומר גלם קטנית. סוג העדשה וצורת השימוש דורשים תיעוד ברמת מנה.', 'fact_en' => 'Lentils appear in Syrian food testimony as a legume ingredient. Lentil type and use require dish-level documentation.', 'sources' => array( 'avs-heart-to-hearth', 'unesco-syrian-ich-survey-2017' ), 'visual' => 'several Syrian-use lentil color categories separated in unbranded bowls, no claim that they are interchangeable' ),
	array( 'id' => 'ingredient-syrian-black-eyed-peas', 'slug' => 'syrian-black-eyed-peas', 'he' => 'לוביה יבשה במטבח הסורי', 'en' => 'Black-Eyed Peas in Syrian Cuisine', 'region' => 'deir-ez-zor-east', 'fact_he' => 'לוביה יבשה נשמרת כזהות קטנית נפרדת בהקשר פאורה ממזרח סוריה, ולא ככינוי לחומוס או לעדשים.', 'fact_en' => 'Black-eyed peas remain a distinct legume identity in the eastern Syrian fawra context, not a synonym for chickpeas or lentils.', 'sources' => array( 'avs-buthaina-east' ), 'used_in' => 'dish-fawra-deir', 'used_in_sources' => array( 'avs-buthaina-east' ), 'visual' => 'dry black-eyed peas and a separate fully cooked sample, plain bowls and no bean substitution cue' ),
	array( 'id' => 'ingredient-syrian-grape-leaves', 'slug' => 'syrian-grape-leaves', 'he' => 'עלי גפן במטבח הסורי', 'en' => 'Grape Leaves in Syrian Cuisine', 'region' => 'damascus-and-aleppo-diaspora', 'fact_he' => 'עלי גפן מתועדים כמעטפת ליבראק, יברה וילנג׳י בנוסחים דמשקאיים ומשפחתיים חלביים-יהודיים. זהות המילוי נשמרת בנפרד מזהות העלה.', 'fact_en' => 'Grape leaves are documented as wrappers for yabraq, yebra and yalangi in Damascene and Aleppan Jewish family versions. Filling identity remains separate from leaf identity.', 'sources' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ), 'used_in' => 'dish-yabraq-yebra', 'used_in_sources' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ), 'visual' => 'rinsed grape leaves laid flat beside one correctly rolled cooked leaf, no branded jar' ),
	array( 'id' => 'ingredient-raqqa-tannour-bread', 'slug' => 'raqqa-tannour-bread', 'he' => 'לחם תנור לתריד הכפרי של רקה', 'en' => 'Tannour Bread for Rural Raqqa Thareed', 'region' => 'raqqa-countryside', 'fact_he' => 'עדותה של רנא מתארת כיכרות דקות הנאפות על דופן תנור חרס ומשמשות בשלמותן כבסיס לתריד הכפרי. הרשומה אינה מכלילה את סוג הלחם לכל אזור הפרת.', 'fact_en' => 'Rana\'s account describes thin loaves baked against a clay tannour wall and used whole as the base of rural thareed. The record does not generalize this bread to the entire Euphrates region.', 'sources' => array( 'avs-rana-raqqa' ), 'used_in' => 'dish-thareed-raqqa-rural', 'used_in_sources' => array( 'avs-rana-raqqa' ), 'visual' => 'one fully baked wide thin tannour loaf with a blistered surface beside a torn section showing flexible crumb, no saj or factory loaf' ),
	array( 'id' => 'ingredient-syrian-matzah', 'slug' => 'damascene-jewish-matzah', 'he' => 'מצה בקבב מצה דמשקאי-יהודי', 'en' => 'Matzah in Damascene Jewish Matzah Kebab', 'region' => 'damascus', 'fact_he' => 'מתכון FOODISH המשפחתי משתמש במצה בקבב המצה המיוחס ליהודי דמשק. סוג המצה וסטטוס האלרגן דורשים אימות מוצר.', 'fact_en' => 'The FOODISH family recipe uses matzah in the matzah kebab attributed to Damascene Jews. Matzah type and allergen status require product verification.', 'sources' => array( 'foodish-matzah-kebab' ), 'used_in' => 'dish-matzah-kebab-damascene-jewish', 'used_in_sources' => array( 'foodish-matzah-kebab' ), 'visual' => 'plain unbranded matzah sheets and measured broken matzah pieces, no ritual props' ),
	array( 'id' => 'ingredient-syrian-onion', 'slug' => 'syrian-onion', 'he' => 'בצל במנות הסוריות המתועדות', 'en' => 'Onion in Documented Syrian Dishes', 'region' => 'syria-national', 'fact_he' => 'בצל מתועד בשכבת ההגשה של פאורה מדיר א-זור ובמנות נוספות. בפאורה הוא נקצץ דק ומטוגן בסמנה ערבית לפני יציקה על הסיר.', 'fact_en' => 'Onion is documented in the finishing layer of Deir ez-Zor fawra and in other dishes. For fawra it is finely chopped and fried in clarified butter before being poured over the pot.', 'sources' => array( 'avs-buthaina-east', 'avs-zainab-coast' ), 'used_in' => 'dish-fawra-deir', 'used_in_sources' => array( 'avs-buthaina-east' ), 'visual' => 'whole onion, finely chopped onion and a separate fully browned sample in plain dishes, no raw and cooked equivalence cue' ),
	array( 'id' => 'ingredient-syrian-eggplant', 'slug' => 'syrian-eggplant', 'he' => 'חציל במטבח הסורי', 'en' => 'Eggplant in Syrian Cuisine', 'region' => 'hama', 'fact_he' => 'חציל מתועד כבסיס לבאטרש מחמה. זן, גודל ורמת קלייה דורשים בחינה נפרדת.', 'fact_en' => 'Eggplant is documented as a base for Hama batersh. Variety, size and charring level require separate review.', 'sources' => array( 'avs-noor-hama' ), 'used_in' => 'dish-batersh-hama', 'used_in_sources' => array( 'avs-noor-hama' ), 'visual' => 'one whole eggplant beside split fully charred eggplant flesh, no unrelated vegetables' ),
	array( 'id' => 'ingredient-syrian-swiss-chard', 'slug' => 'syrian-swiss-chard', 'he' => 'מנגולד במטבח הסורי', 'en' => 'Swiss Chard in Syrian Cuisine', 'region' => 'syrian-coast', 'fact_he' => 'עלי מנגולד מתועדים בקובת א-סלק מן החוף. הישות נפרדת מסלק שורש.', 'fact_en' => 'Swiss chard leaves are documented in coastal kibbat al silik. The entity is distinct from beetroot.', 'sources' => array( 'avs-zainab-coast' ), 'used_in' => 'dish-kibbat-al-silik', 'used_in_sources' => array( 'avs-zainab-coast' ), 'visual' => 'fresh Swiss chard leaves with broad green blades and pale ribs beside a cooked chopped sample, no beetroot' ),
	array( 'id' => 'ingredient-syrian-red-pepper-paste', 'slug' => 'syrian-red-pepper-paste', 'he' => 'מחית פלפל אדום סורית', 'en' => 'Syrian Red Pepper Paste', 'region' => 'syrian-coast', 'fact_he' => 'מחית פלפל אדום מתועדת במנות מן החוף הסורי. זן פלפל, מלח, חריפות ותהליך הם שדות מוצר נפרדים.', 'fact_en' => 'Red pepper paste is documented in dishes from the Syrian coast. Pepper variety, salt, heat and process are separate product fields.', 'sources' => array( 'avs-zainab-coast' ), 'visual' => 'thick deep-red pepper paste in an unbranded ceramic dish with visible pepper texture, no package' ),
	array( 'id' => 'ingredient-syrian-dried-red-pepper', 'slug' => 'syrian-dried-red-pepper', 'he' => 'פלפל אדום מיובש סורי', 'en' => 'Syrian Dried Red Pepper', 'region' => 'aleppo', 'fact_he' => 'פלפל אדום מיובש או פלפל בסגנון חַלבי דורש זיהוי זן, מקור, דרגת טחינה וחריפות. השם לבדו אינו אישור מקור חַלבי.', 'fact_en' => 'Dried red pepper or Aleppo-style pepper requires variety, origin, grind and heat identification. The name alone does not certify Aleppo origin.', 'sources' => array( 'avs-mirvet-aleppo', 'aleppo-project-cuisine-2017' ), 'visual' => 'whole dried red peppers and coarse red pepper flakes in separate plain dishes, no Aleppo origin badge' ),
	array( 'id' => 'ingredient-syrian-tomato-paste', 'slug' => 'syrian-tomato-paste', 'he' => 'רסק עגבניות במטבח הסורי', 'en' => 'Tomato Paste in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'רסק עגבניות מופיע בעדויות אזוריות כרכיב תבשיל. ריכוז, מלח ותוספים דורשים תווית מוצר.', 'fact_en' => 'Tomato paste appears in regional testimony as a stew component. Concentration, salt and additives require a product label.', 'sources' => array( 'avs-heart-to-hearth', 'avs-buthaina-east' ), 'visual' => 'dense tomato paste in an unbranded dish with a cut tomato for identity only, no concentration claim' ),
	array( 'id' => 'ingredient-syrian-pomegranate-molasses', 'slug' => 'syrian-pomegranate-molasses', 'he' => 'מולסת רימונים במטבח הסורי', 'en' => 'Pomegranate Molasses in Syrian Cuisine', 'region' => 'aleppo-and-homs', 'fact_he' => 'מולסת רימונים מתועדת בלהם בעג׳ין משפחתי מחלב וברוטב קובה בחמוד מחומס. מתיקות, חומציות וריכוז הם תכונות מוצר ואצווה.', 'fact_en' => 'Pomegranate molasses is documented in a family Aleppan lahm bi ajin and in the Homs kibbeh b hamod broth. Sweetness, acidity and concentration are product and lot properties.', 'sources' => array( 'jfs-lahm-bajin', 'avs-nariman-homs' ), 'visual' => 'dark ruby pomegranate molasses flowing slowly from an unbranded spoon into a plain dish, no health symbolism' ),
	array( 'id' => 'ingredient-syrian-lemon', 'slug' => 'syrian-lemon', 'he' => 'לימון במטבח הסורי', 'en' => 'Lemon in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'לימון מופיע כמחולל חמיצות במספר עדויות, אך אינו תחליף אוטומטי לתמרהינדי, סומאק, דובדבן חמוץ או רימון.', 'fact_en' => 'Lemon appears as an acid source in several accounts, but it is not an automatic substitute for tamarind, sumac, sour cherry or pomegranate.', 'sources' => array( 'avs-razan-damascus', 'avs-zainab-coast' ), 'visual' => 'whole and cut lemons with measured juice in a plain vessel, no wellness imagery' ),
	array( 'id' => 'ingredient-syrian-tamarind', 'slug' => 'syrian-tamarind', 'he' => 'תמרהינדי במטבח הסורי', 'en' => 'Tamarind in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'תמרהינדי נשמר כמחולל חמיצות עצמאי במפת המטבח הסורי. צורת מוצר וריכוז דורשים אימות.', 'fact_en' => 'Tamarind remains an independent souring ingredient in the Syrian cuisine map. Product form and concentration require verification.', 'sources' => array( 'avs-heart-to-hearth', 'lal-scents-flavors' ), 'visual' => 'tamarind pods, pulp and a strained dark liquid shown separately, no product packaging' ),
	array( 'id' => 'ingredient-syrian-sumac', 'slug' => 'syrian-sumac', 'he' => 'סומאק במטבח הסורי', 'en' => 'Sumac in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'סומאק מתועד כרכיב תיבול וחמיצות. מין בוטני, מקור, מלח ותכולת לחות דורשים מפרט מוצר.', 'fact_en' => 'Sumac is documented as a seasoning and souring ingredient. Botanical species, origin, salt and moisture require product specification.', 'sources' => array( 'avs-heart-to-hearth' ), 'visual' => 'coarse burgundy ground sumac and intact clusters shown as identity references, no medical or antioxidant cue' ),
	array( 'id' => 'ingredient-syrian-sour-cherry', 'slug' => 'syrian-sour-cherry', 'he' => 'דובדבן חמוץ במטבח הסורי', 'en' => 'Sour Cherry in Syrian Cuisine', 'region' => 'aleppo', 'fact_he' => 'דובדבן חמוץ הוא חומר גלם מזוהה בקבב ביל כרז. זן, עונה וצורת שימור משפיעים על הרוטב ודורשים תיעוד.', 'fact_en' => 'Sour cherry is an identified ingredient in kebab bil karaz. Cultivar, season and preservation form affect the sauce and require documentation.', 'sources' => array( 'aleppo-project-cuisine-2017' ), 'used_in' => 'dish-kebab-bil-karaz', 'used_in_sources' => array( 'aleppo-project-cuisine-2017' ), 'visual' => 'fresh and preserved sour cherries shown in separate plain bowls, deep ruby color and no sweet-cherry substitution cue' ),
	array( 'id' => 'ingredient-syrian-quince', 'slug' => 'syrian-quince', 'he' => 'חבוש במטבח הסורי', 'en' => 'Quince in Syrian Cuisine', 'region' => 'aleppo', 'fact_he' => 'חבוש מתועד בקובה ספרג׳ליה. דרגת הבשלה, עפיצות ובישול אינם נלמדים משם המנה בלבד.', 'fact_en' => 'Quince is documented in kibbeh safarjaliyyeh. Ripeness, astringency and cooking behavior are not established by the dish name alone.', 'sources' => array( 'aleppo-project-cuisine-2017' ), 'used_in' => 'dish-kibbeh-safarjaliyyeh', 'used_in_sources' => array( 'aleppo-project-cuisine-2017' ), 'visual' => 'whole golden quince, cut pale flesh and fully cooked quince pieces shown in sequence, no apple substitution' ),
	array( 'id' => 'ingredient-syrian-tahini', 'slug' => 'syrian-tahini', 'he' => 'טחינה במטבח הסורי', 'en' => 'Tahini in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'טחינה מתועדת בבאטרש ובמנות נוספות. זהות שומשום, קלייה, צמיגות ואלרגן דורשים מוצר מסוים.', 'fact_en' => 'Tahini is documented in batersh and other dishes. Sesame identity, roasting, viscosity and allergen status require a specific product.', 'sources' => array( 'avs-noor-hama', 'avs-heart-to-hearth' ), 'used_in' => 'dish-batersh-hama', 'used_in_sources' => array( 'avs-noor-hama' ), 'visual' => 'smooth tahini in a plain bowl with sesame seeds shown only for ingredient identity, no brand or health claim' ),
	array( 'id' => 'ingredient-syrian-walnuts', 'slug' => 'syrian-walnuts', 'he' => 'אגוזי מלך במטבח הסורי', 'en' => 'Walnuts in Syrian Cuisine', 'region' => 'homs', 'fact_he' => 'עדות חומס מתעדת אגוזי מלך בהכנת מוחמרה ביתית. טריות, קלייה וגודל טחינה הם שדות מוצר ותהליך.', 'fact_en' => 'The Homs testimony documents walnuts in household muhammara preparation. Freshness, roasting and grind size are product and process fields.', 'sources' => array( 'avs-nariman-homs' ), 'visual' => 'shelled walnuts shown whole, chopped and finely ground in separate unbranded dishes' ),
	array( 'id' => 'ingredient-syrian-unspecified-nuts', 'slug' => 'syrian-source-unspecified-nuts', 'he' => 'אגוזים מסוג שלא צוין במקור', 'en' => 'Source-Unspecified Nuts in Syrian Preparations', 'region' => 'aleppo-and-suwayda', 'fact_he' => 'העדויות של מירוות וגהימאנה מציינות אגוזים בלי לזהות סוג. הישות נשמרת כללית ואינה מומרת לאגוז מלך, פיסטוק או סוג אחר.', 'fact_en' => 'Mirvet\'s and Ghaimana\'s testimonies name nuts without identifying a type. The entity remains generic and is not converted to walnut, pistachio or another nut.', 'sources' => array( 'avs-mirvet-aleppo', 'avs-ghaimana-suwayda' ), 'used_in' => 'dish-kibbeh-meshwiyyeh', 'used_in_sources' => array( 'avs-mirvet-aleppo' ), 'extra_used_in' => array( array( 'target' => 'preparation-mleihi-suwayda-fresh-yogurt', 'sources' => array( 'avs-ghaimana-suwayda' ) ) ), 'visual' => 'a neutral mixed-nut placeholder shown without any single nut type being asserted as the source identity' ),
	array( 'id' => 'ingredient-syrian-pistachios', 'slug' => 'syrian-pistachios', 'he' => 'פיסטוקים במטבח הסורי', 'en' => 'Pistachios in Syrian Cuisine', 'region' => 'hama', 'fact_he' => 'פיסטוקים מתועדים בהגשת חלאוות אל-ג׳בן בעדות מחמה. מקור, קלייה, מלח ואלרגן דורשים אימות מוצר.', 'fact_en' => 'Pistachios are documented in the Hama testimony about serving halawet al-jibn. Origin, roasting, salt and allergen status require product verification.', 'sources' => array( 'avs-noor-hama' ), 'used_in' => 'preparation-halawet-hama-qeshta-pistachio', 'used_in_sources' => array( 'avs-noor-hama' ), 'visual' => 'shelled green pistachios shown whole and finely chopped beside a plain dish, no origin claim' ),
	array( 'id' => 'ingredient-syrian-cheese', 'slug' => 'homs-halawet-cheese', 'he' => 'גבינה בחלאוות אל-ג׳בן מחומס', 'en' => 'Cheese in the Homs Halawet Al Jibn Account', 'region' => 'homs', 'fact_he' => 'עדותה של נרימאן מתעדת גבינה בבצק חלאוות אל-ג׳בן מחומס. סוג הגבינה, המליחות והלחות אינם נקבעים מן העדות.', 'fact_en' => 'Nariman\'s testimony documents cheese in the Homs halawet al-jibn dough. Cheese type, salinity and moisture are not established by the testimony.', 'sources' => array( 'avs-nariman-homs' ), 'used_in' => 'preparation-halawet-homs-cheese-semolina', 'used_in_sources' => array( 'avs-nariman-homs' ), 'visual' => 'a neutral white cheese sample with no variety, brand or origin claim' ),
	array( 'id' => 'ingredient-syrian-semolina', 'slug' => 'homs-halawet-semolina', 'he' => 'סולת בחלאוות אל-ג׳בן מחומס', 'en' => 'Semolina in the Homs Halawet Al Jibn Account', 'region' => 'homs', 'fact_he' => 'עדותה של נרימאן מתעדת סולת בבצק חלאוות אל-ג׳בן מחומס. גודל הגרגר, סוג החיטה והיחס לגבינה דורשים ניסוי נפרד.', 'fact_en' => 'Nariman\'s testimony documents semolina in the Homs halawet al-jibn dough. Grain size, wheat type and the cheese ratio require separate testing.', 'sources' => array( 'avs-nariman-homs' ), 'used_in' => 'preparation-halawet-homs-cheese-semolina', 'used_in_sources' => array( 'avs-nariman-homs' ), 'visual' => 'plain semolina grains in an unbranded bowl, no flour or grain-size equivalence cue' ),
	array( 'id' => 'ingredient-syrian-qeshta-cream', 'slug' => 'halawet-qeshta-cream', 'he' => 'קשטה או קרם בחלאוות אל-ג׳בן', 'en' => 'Qeshta or Cream in Halawet Al Jibn Accounts', 'region' => 'homs-and-hama', 'fact_he' => 'עדויות חומס וחמה מתעדות מילוי קרם או קשטה בחלאוות אל-ג׳בן. הרכב הקרם ואחוזי השומן אינם נקבעים מן העדויות.', 'fact_en' => 'The Homs and Hama testimonies document a cream or qeshta filling in halawet al-jibn. Cream composition and fat percentage are not established by the accounts.', 'sources' => array( 'avs-nariman-homs', 'avs-noor-hama' ), 'used_in' => 'preparation-halawet-homs-cheese-semolina', 'used_in_sources' => array( 'avs-nariman-homs' ), 'extra_used_in' => array( array( 'target' => 'preparation-halawet-hama-qeshta-pistachio', 'sources' => array( 'avs-noor-hama' ) ) ), 'visual' => 'plain thick white cream in an unbranded dish, no fat percentage or commercial dairy identity' ),
	array( 'id' => 'ingredient-syrian-sugar-syrup', 'slug' => 'homs-halawet-sugar-syrup', 'he' => 'סירופ בחלאוות אל-ג׳בן מחומס', 'en' => 'Syrup in the Homs Halawet Al Jibn Account', 'region' => 'homs', 'fact_he' => 'עדותה של נרימאן מתעדת סירופ בהגשת חלאוות אל-ג׳בן מחומס. ריכוז הסוכר, ארומה וכמות אינם נקבעים מן העדות.', 'fact_en' => 'Nariman\'s testimony documents syrup in the Homs serving of halawet al-jibn. Sugar concentration, aroma and quantity are not established by the testimony.', 'sources' => array( 'avs-nariman-homs' ), 'used_in' => 'preparation-halawet-homs-cheese-semolina', 'used_in_sources' => array( 'avs-nariman-homs' ), 'visual' => 'clear pale syrup in an unbranded glass and spoon, no concentration or flavor claim' ),
	array( 'id' => 'ingredient-syrian-fresh-yogurt', 'slug' => 'syrian-fresh-yogurt', 'he' => 'יוגורט טרי במטבח הסורי', 'en' => 'Fresh Yogurt in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'יוגורט טרי מתועד ברטבים מחומס ומדמשק ובהכנת מליחי מא-סווידא. הוא נשמר בנפרד מקישק, ג׳מיד והיגט.', 'fact_en' => 'Fresh yogurt is documented in Homs and Damascus sauces and in the Suwayda Mleihi preparation. It remains distinct from kishk, jameed and higet.', 'sources' => array( 'avs-nariman-homs', 'avs-razan-damascus', 'avs-ghaimana-suwayda' ), 'used_in' => 'dish-kibbeh-labaniyyeh', 'used_in_sources' => array( 'avs-nariman-homs' ), 'visual' => 'plain fresh yogurt with visible smooth spoon trail in an unbranded bowl, no dried dairy nearby' ),
	array( 'id' => 'ingredient-syrian-kishk', 'slug' => 'syrian-kishk', 'he' => 'קישק במטבח הסורי', 'en' => 'Kishk in Syrian Cuisine', 'region' => 'northern-and-eastern-syria', 'fact_he' => 'קישק נשמר כזהות מזון מותסס ומיובש לפי מקורות מקומיים. הוא אינו כינוי חלופי לג׳מיד, היגט או הקט.', 'fact_en' => 'Kishk remains a fermented and dried food identity according to local sources. It is not an alternate name for jameed, higet or haqt.', 'sources' => array( 'avs-rahma-idlib', 'avs-buthaina-east' ), 'visual' => 'dry kishk form and a separately reconstituted sample, plain bowls and no probiotic claim' ),
	array( 'id' => 'ingredient-syrian-jameed', 'slug' => 'syrian-jameed', 'he' => 'ג׳מיד בהקשר חוראן', 'en' => 'Jameed in Hauran Context', 'region' => 'hauran', 'fact_he' => 'שאהלה משתמשת במונח ג׳מיד בהכנת מליחי מחוראן. הרשומה אינה מאחדת אותו עם היגט ללא מקור זהות.', 'fact_en' => 'Shahla uses the term jameed in the Hauran Mleihi preparation. The record does not merge it with higet without identity evidence.', 'sources' => array( 'avs-shahla-hauran' ), 'used_in' => 'preparation-mleihi-hauran-jameed', 'used_in_sources' => array( 'avs-shahla-hauran' ), 'visual' => 'a dried yogurt product form identified only as the Hauran jameed research target beside a reconstituted sauce, no origin seal' ),
	array( 'id' => 'ingredient-syrian-higet', 'slug' => 'syrian-higet', 'he' => 'היגט בהקשר דרעא', 'en' => 'Higet in Daraa Context', 'region' => 'daraa', 'fact_he' => 'גהימאנה משתמשת במונח היגט למוצר יוגורט מיובש מדרעא. הישות נשמרת בנפרד מג׳מיד עד לבדיקת זהות לשונית וחומרית.', 'fact_en' => 'Ghaimana uses the term higet for a dried-yogurt product from Daraa. The entity remains separate from jameed pending linguistic and material identity review.', 'sources' => array( 'avs-ghaimana-suwayda' ), 'visual' => 'a dried yogurt product form identified only as the Daraa higet research target, plain surface and no jameed label' ),
	array( 'id' => 'ingredient-syrian-haqt', 'slug' => 'syrian-haqt', 'he' => 'הקט בהקשר קמישלי', 'en' => 'Haqt in Qamishli Context', 'region' => 'qamishli-jazira', 'fact_he' => 'הקט מתועד בעדות משפחתית הקשורה לקמישלי. זהותו נשמרת לפי שם המשפחה ואינה מאוחדת עם קישק או מוצרי יוגורט דרומיים.', 'fact_en' => 'Haqt is documented in family testimony associated with Qamishli. Its identity follows the family term and is not merged with kishk or southern dried-yogurt products.', 'sources' => array( 'avs-samar-qamishli' ), 'used_in' => 'dish-kubaybat-haqt-qamishli', 'used_in_sources' => array( 'avs-samar-qamishli' ), 'visual' => 'a neutral ingredient study labeled only in metadata as the family-transmitted haqt target, no speculative components' ),
	array( 'id' => 'ingredient-syrian-olive-oil', 'slug' => 'syrian-olive-oil', 'he' => 'שמן זית במטבח הסורי', 'en' => 'Olive Oil in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'שמן זית מתועד בבישול ובשימור מזון סורי. זן, מסיק, חומציות ודרגת מוצר דורשים תווית או בדיקה מתאימה.', 'fact_en' => 'Olive oil is documented in Syrian cooking and preservation. Cultivar, harvest, acidity and grade require an appropriate label or test.', 'sources' => array( 'avs-zainab-coast', 'avs-amani-afrin', 'unesco-syrian-ich-survey-2017' ), 'used_in' => 'dish-sayadiyah-syrian-coast', 'used_in_sources' => array( 'avs-zainab-coast' ), 'visual' => 'golden olive oil in an unbranded glass cruet and a measured plain dish, no purity or wellness symbols' ),
	array( 'id' => 'ingredient-syrian-samn', 'slug' => 'syrian-samn', 'he' => 'סמנה במטבח הסורי', 'en' => 'Samn in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'סמנה או שומן מזוכך מופיעים בעדויות אזוריות. מקור החלב או השומן, אלרגן ותהליך דורשים זיהוי מוצר.', 'fact_en' => 'Samn or clarified cooking fat appears in regional testimony. Dairy or fat source, allergen status and process require product identification.', 'sources' => array( 'avs-razan-damascus', 'avs-ghaimana-suwayda', 'lal-scents-flavors' ), 'visual' => 'clarified cooking fat in an unbranded jar and spoon, pale golden texture and no butter-origin claim' ),
	array( 'id' => 'ingredient-syrian-red-meat', 'slug' => 'syrian-lamb-beef-family', 'he' => 'כבש ובקר בקובה ובמטבח הסורי', 'en' => 'Lamb and Beef in Kibbeh and Syrian Cooking', 'region' => 'syria-national', 'summary_he' => 'כשמנה כוללת בשר, חשוב לשאול איזה נתח או טחינה, מה אחוז השומן ואיך מבשלים אותו. כבש ובקר מוצגים כאן זה לצד זה כדי להבין את האפשרויות, לא כהחלפה אוטומטית בכל קובה או תבשיל.', 'summary_en' => 'When a dish includes meat, the useful questions are the cut or grind, fat level and cooking method. Lamb and beef sit side by side here as choices to understand, not as automatic substitutes in every kibbeh or stew.', 'fact_he' => 'מקורות מחלב מתעדים בשר במגוון צורות של קובה ומנות נוספות. סוג הבשר המדויק נשאר חלק מזהות המנה והגרסה המשפחתית, ולכן אין להחליף בין כבש לבקר בלי להבין את המתכון.', 'fact_en' => 'Aleppine sources document meat across multiple kibbeh forms and other dishes. The exact meat remains part of the dish and family version, so lamb and beef should not be exchanged without understanding the preparation.', 'sources' => array( 'avs-mirvet-aleppo', 'aleppo-project-cuisine-2017', 'simon-schuster-aleppo-cookbook' ), 'used_in' => 'dish-kibbeh-meshwiyyeh', 'used_in_sources' => array( 'avs-mirvet-aleppo' ), 'intent_he' => 'להבין את ההבדלים בין כבש לבקר בקובה ובבישול הסורי.', 'intent_en' => 'Understand the role of lamb and beef in kibbeh and Syrian cooking.', 'keyword_he' => 'בשר לקובה', 'keyword_en' => 'meat for kibbeh', 'visual' => 'fully cooked lamb and beef examples in separate neutral dishes, warm natural light, no raw mince and no equivalence cue' ),
	array( 'id' => 'ingredient-syrian-whole-chicken', 'slug' => 'aleppan-jewish-whole-chicken', 'he' => 'עוף שלם בדג׳אג׳ משווי משפחתי', 'en' => 'Whole Chicken in Family Aleppan Jewish Dajaj Mashwi', 'region' => 'aleppo-diaspora', 'fact_he' => 'מתכון FOODISH המשפחתי מתעד עוף שלם ממולא בדג׳אג׳ משווי. משקל העוף, מקורו וזמן הבישול דורשים אימות וניסוי בטיחות.', 'fact_en' => 'The FOODISH family recipe documents a stuffed whole chicken in dajaj mashwi. Chicken weight, origin and cooking time require verification and a safety trial.', 'sources' => array( 'foodish-dajaj-mashwi' ), 'used_in' => 'dish-dajaj-mashwi-aleppan-jewish', 'used_in_sources' => array( 'foodish-dajaj-mashwi' ), 'visual' => 'one fully cooked whole stuffed chicken with no pink meat, no raw poultry and no garnish claims' ),
	array( 'id' => 'ingredient-syrian-coastal-fish', 'slug' => 'syrian-coastal-fish-family', 'he' => 'דגי החוף הסורי', 'en' => 'Syrian Coastal Fish Family', 'region' => 'syrian-coast', 'fact_he' => 'העדות החופית מתעדת דגים במנות מקומיות, אך אינה הופכת מין אחד לברירת מחדל. כל מוצר דורש זיהוי מין, מקור וקירור.', 'fact_en' => 'Coastal testimony documents fish in local dishes but does not make one species the default. Every product requires species, origin and cold-chain identification.', 'sources' => array( 'avs-zainab-coast' ), 'used_in' => 'dish-sayadiyah-syrian-coast', 'used_in_sources' => array( 'avs-zainab-coast' ), 'visual' => 'fully cooked generic coastal fish portions and a separate species-verification card area, no raw fish and no invented species' ),
	array( 'id' => 'ingredient-syrian-garlic', 'slug' => 'syrian-garlic', 'he' => 'שום במטבח הסורי', 'en' => 'Garlic in Syrian Cuisine', 'region' => 'syria-national', 'fact_he' => 'שום מתועד בהכנת יבראק דמשקאית וביברה משפחתית של יהודי חלב. זן, מצב טרי או מיובש וכמות דורשים בדיקה ברמת ההכנה והמוצר.', 'fact_en' => 'Garlic is documented in a Damascene yabraq preparation and an Aleppan Jewish family yebra. Variety, fresh or dried state and quantity require preparation-level and product-level review.', 'sources' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ), 'used_in' => 'dish-yabraq-yebra', 'used_in_sources' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ), 'visual' => 'whole garlic bulb and separated fresh cloves in a plain unbranded dish, no powdered-garlic equivalence cue' ),
	array( 'id' => 'ingredient-syrian-dried-apricot', 'slug' => 'aleppan-jewish-yebra-dried-apricot', 'he' => 'משמש מיובש ביברה משפחתית חלבית-יהודית', 'en' => 'Dried Apricot in Aleppan Jewish Family Yebra', 'region' => 'aleppo-diaspora', 'fact_he' => 'מקור המשפחה של Charles Dabbah מתעד משמשים מיובשים בהכנת יברה שמסלול המשפחה שלה מתחיל בחלב. הרכיב אינו מוצג כחובה בכל יברה סורית.', 'fact_en' => 'The Charles Dabbah family source documents dried apricots in a yebra preparation whose family journey begins in Aleppo. The ingredient is not presented as mandatory in every Syrian yebra.', 'sources' => array( 'jfs-yebra-apricots' ), 'used_in' => 'preparation-yebra-aleppan-jewish-apricot', 'used_in_sources' => array( 'jfs-yebra-apricots' ), 'visual' => 'plain dried apricot halves in an unbranded bowl beside one rehydrated example, no origin or variety claim' ),
	array( 'id' => 'ingredient-syrian-allspice', 'slug' => 'aleppan-jewish-yebra-allspice', 'he' => 'פלפל אנגלי ביברה משפחתית חלבית-יהודית', 'en' => 'Allspice in Aleppan Jewish Family Yebra', 'region' => 'aleppo-diaspora', 'fact_he' => 'מקור המשפחה של Charles Dabbah מציין פלפל אנגלי טחון במילוי הבשר והאורז. הרשומה אינה מסיקה מכך שכל מילוי עלי גפן סורי משתמש באותו תבלין.', 'fact_en' => 'The Charles Dabbah family source lists ground allspice in the meat and rice filling. The record does not infer that every Syrian grape-leaf filling uses the same spice.', 'sources' => array( 'jfs-yebra-apricots' ), 'used_in' => 'preparation-yebra-aleppan-jewish-apricot', 'used_in_sources' => array( 'jfs-yebra-apricots' ), 'visual' => 'whole allspice berries and a separate small sample of freshly ground allspice, no mixed-spice substitution' ),
	array( 'id' => 'ingredient-syrian-dried-mint', 'slug' => 'aleppan-jewish-yebra-dried-mint', 'he' => 'נענע מיובשת ביברה משפחתית חלבית-יהודית', 'en' => 'Dried Mint in Aleppan Jewish Family Yebra', 'region' => 'aleppo-diaspora', 'fact_he' => 'מקור המשפחה של Charles Dabbah מציין נענע מיובשת בין שכבות היברה. נענע טרייה ומיובשת אינן מאוחדות אוטומטית במפרט מוצר.', 'fact_en' => 'The Charles Dabbah family source lists dried mint between the yebra layers. Fresh and dried mint are not automatically merged in a product specification.', 'sources' => array( 'jfs-yebra-apricots' ), 'used_in' => 'preparation-yebra-aleppan-jewish-apricot', 'used_in_sources' => array( 'jfs-yebra-apricots' ), 'visual' => 'crumbled dried mint leaves in a plain dish beside a small intact dried sprig, no fresh-mint equivalence cue' ),
	array( 'id' => 'ingredient-aleppan-ou-souring-concentrate', 'slug' => 'aleppan-ou-souring-concentrate-source-term', 'he' => 'אוּ׳, תרכיז חמוץ במונח המקור המשפחתי', 'en' => 'Ou Sour Concentrate, Family Source Term', 'region' => 'aleppo-diaspora', 'fact_he' => 'דף היברה של Jewish Food Society משתמש במונח אוּ׳ לנוזל חמוץ-מתוק אך אינו מגדיר בדף את הרכבו. לכן הישות נשמרת כמונח מקור נפרד ואינה מאוחדת עם תמרהינדי, משמש או רימון ללא מקור זהות נוסף.', 'fact_en' => 'The Jewish Food Society yebra page uses the term ou for a sweet-sour liquid but does not define its composition on that page. The entity therefore remains a separate source term and is not merged with tamarind, apricot or pomegranate without additional identity evidence.', 'sources' => array( 'jfs-yebra-apricots' ), 'used_in' => 'preparation-yebra-aleppan-jewish-apricot', 'used_in_sources' => array( 'jfs-yebra-apricots' ), 'visual' => 'an unbranded dark souring liquid in a neutral glass identified only in metadata as the source term ou, no tamarind, apricot or pomegranate shown and no ingredient equivalence claim' ),
	array( 'id' => 'ingredient-pomegranate-concentrate', 'slug' => 'pomegranate-concentrate-product-identity', 'he' => 'רכז רימונים, זהות מוצר נפרדת', 'en' => 'Pomegranate Concentrate, Separate Product Identity', 'region' => 'market-specific-unverified-origin', 'fact_he' => 'דף המוצר המתועד משתמש בשם רכז רימונים. השם אינו מוכיח שזהות המוצר זהה לדיבס רומאן או למולסת רימונים מסורתית, ולכן הישות נשמרת בנפרד עד בדיקת תווית, רכיבים וריכוז.', 'fact_en' => 'The observed product page uses the name pomegranate concentrate. That name does not prove identity with traditional dibs rumman or pomegranate molasses, so the entity remains separate until label, ingredient and concentration review.', 'sources' => array( 'tamar-hst-keter-harimon-pomegranate-concentrate-250ml-listing-2026' ), 'visual' => 'unbranded pomegranate concentrate in a plain measured vessel beside an empty label-review card, no pomegranate-molasses equivalence cue' ),
);

foreach ( $c99_syrian_ingredient_specs as $ingredient_spec ) {
	$facts = array(
		$c99_syrian_fact(
			'fact-' . $ingredient_spec['slug'] . '-culinary-identity', 'cultural',
			$ingredient_spec['fact_he'], $ingredient_spec['fact_en'], 'official_source', 'category', $ingredient_spec['sources']
		),
	);
	if ( 'ingredient-syrian-bulgur' === $ingredient_spec['id'] ) {
		$facts[] = $c99_syrian_fact( 'fact-syrian-bulgur-hydration-category-context', 'scientific', 'מחקרי הידרציה מראים שספיחת המים והמרקם משתנים עם הזמן, הטמפרטורה ומאפייני הגרגר. הם עוזרים להבין מה למדוד, אך אינם מספקים יחס מים אוניברסלי לכל בורגול או לכל קובה.', 'Hydration studies show that water uptake and texture change with time, temperature and grain characteristics. They clarify what to observe but do not provide one universal water ratio for every bulgur or kibbeh.', 'peer_reviewed_context', 'category', array( 'bulgur-hydration-2025', 'bulgur-hydration-cereal-chemistry' ) );
	}
	if ( 'ingredient-syrian-fresh-yogurt' === $ingredient_spec['id'] ) {
		$facts[] = $c99_syrian_fact( 'fact-syrian-yogurt-structure-category-context', 'scientific', 'מחקר מבנה חלבון ביוגורט מספק הקשר ליציבות רוטב בלבד; הוא אינו מודד את היוגורט או המנה ברשומה.', 'Yogurt protein-structure research supplies sauce-stability context only; it does not measure the yogurt or dish in this record.', 'peer_reviewed_context', 'category', array( 'yogurt-protein-structure-2023' ) );
	}
	if ( 'ingredient-syrian-pomegranate-molasses' === $ingredient_spec['id'] ) {
		$facts[] = $c99_syrian_fact( 'fact-pomegranate-molasses-category-science-boundary', 'scientific', 'ספרות פיזיקוכימית על רימון היא הקשר קטגוריה ואינה מאשרת ריכוז, חומציות, מתיקות או הרכב של מולסה מסחרית מסוימת.', 'Physicochemical pomegranate literature is category context and does not verify concentration, acidity, sweetness or composition for a specific commercial molasses.', 'peer_reviewed_context', 'category', array( 'pomegranate-physicochemical-2020' ) );
	}
	if ( 'ingredient-syrian-sour-cherry' === $ingredient_spec['id'] ) {
		$facts[] = $c99_syrian_fact( 'fact-sour-cherry-acids-category-boundary', 'scientific', 'מחקר חומצות אורגניות בדובדבן חמוץ מתאר שונות בפרי ואינו מדידת רוטב קבב ביל כרז.', 'Organic-acid research on sour cherry describes fruit variation and is not a measurement of kebab bil karaz sauce.', 'peer_reviewed_context', 'category', array( 'sour-cherry-organic-acids-2020' ) );
	}
	if ( 'ingredient-syrian-sumac' === $ingredient_spec['id'] ) {
		$facts[] = $c99_syrian_fact( 'fact-sumac-acids-category-boundary', 'scientific', 'מחקר חומצות אורגניות בסומאק מספק הקשר לקטגוריה בלבד. אין להסיק ממנו יתרון רפואי, ריכוז או אישור למוצר מסוים.', 'Organic-acid research on sumac provides category context only. It does not establish a medical benefit, concentration or approval for a specific product.', 'peer_reviewed_context', 'category', array( 'sumac-organic-acids-2022' ) );
	}
	if ( in_array( $ingredient_spec['id'], array( 'ingredient-syrian-kishk', 'ingredient-syrian-jameed', 'ingredient-syrian-higet', 'ingredient-syrian-haqt' ), true ) ) {
		$facts[] = $c99_syrian_fact( 'fact-' . $ingredient_spec['slug'] . '-water-activity-boundary', 'scientific', 'הנחיית פעילות מים מספקת מסגרת לבדיקת יציבות של מזון מיובש. היא אינה מוכיחה פעילות מים, חיי מדף או בטיחות של מוצר שלא נבדק.', 'Water-activity guidance supplies a framework for evaluating dried-food stability. It does not prove water activity, shelf life or safety for an untested product.', 'regulatory_standard', 'category', array( 'fda-water-activity' ) );
	}
	$c99_syrian_relations = array();
	if ( isset( $ingredient_spec['used_in'] ) ) {
		$used_in_source_ids = isset( $ingredient_spec['used_in_sources'] ) ? $ingredient_spec['used_in_sources'] : $ingredient_spec['sources'];
		$c99_syrian_relations[] = $c99_relation( 'used_in', $ingredient_spec['used_in'], 'המקור קושר את חומר הגלם למנה, בלי לקבוע SKU או כמות.', 'The source links the ingredient to the dish without establishing a SKU or quantity.', false, $used_in_source_ids, 'official_source' );
	}
	if ( isset( $ingredient_spec['extra_used_in'] ) ) {
		foreach ( $ingredient_spec['extra_used_in'] as $extra_used_in ) {
			$c99_syrian_relations[] = $c99_relation( 'used_in', $extra_used_in['target'], 'המקור קושר את חומר הגלם להכנה נוספת בלי לקבוע SKU או כמות.', 'The source links the ingredient to an additional preparation without establishing a SKU or quantity.', false, $extra_used_in['sources'], 'official_source' );
		}
	}
	$compliance = array();
	if ( in_array( $ingredient_spec['id'], array( 'ingredient-syrian-bulgur', 'ingredient-syrian-jreesh', 'ingredient-syrian-freekeh', 'ingredient-syrian-tahini', 'ingredient-syrian-walnuts', 'ingredient-syrian-unspecified-nuts', 'ingredient-syrian-pistachios', 'ingredient-syrian-matzah', 'ingredient-syrian-cheese', 'ingredient-syrian-semolina', 'ingredient-syrian-qeshta-cream', 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-kishk', 'ingredient-syrian-jameed', 'ingredient-syrian-higet', 'ingredient-syrian-samn', 'ingredient-syrian-coastal-fish' ), true ) ) {
		$allergen_note_he = 'ingredient-syrian-bulgur' === $ingredient_spec['id']
			? 'בורגול הוא מוצר חיטה. יש לבדוק את תווית האריזה המדויקת ואת סיכוני המגע הצולב בסביבת העבודה כאשר חיטה או גלוטן הם שיקול.'
			: 'יש לבדוק את תווית המוצר ואת סביבת העבודה כאשר קיים אלרגן רלוונטי.';
		$allergen_note_en = 'ingredient-syrian-bulgur' === $ingredient_spec['id']
			? 'Bulgur is a wheat product. Check the exact pack label and cross-contact risks in the preparation environment when wheat or gluten is a concern.'
			: 'Check the product label and preparation environment when a relevant allergen is a concern.';
		$allergen_source_ids = 'ingredient-syrian-bulgur' === $ingredient_spec['id']
			? array( 'israel-moh-food-allergen-labeling-2026', 'israel-moh-allergen-survey-2024' )
			: array( 'israel-moh-allergen-survey-2024' );
		$compliance[] = $c99_compliance( 'product-allergen-verification', $allergen_note_he, $allergen_note_en, $allergen_source_ids, false );
	}
	if ( 'ingredient-pomegranate-concentrate' === $ingredient_spec['id'] ) {
		$compliance[] = $c99_compliance( 'pomegranate-concentrate-identity-review', 'יש לאמת את רשימת הרכיבים, תוספת הסוכר, הריכוז, האלרגנים, חיי המדף והאחסון של המוצר המדויק לפני הפעלה.', 'Verify ingredients, added sugar, concentration, allergens, shelf life and storage for the exact product before activation.', array( 'tamar-hst-keter-harimon-pomegranate-concentrate-250ml-listing-2026' ), false );
	}
	if ( 'ingredient-syrian-red-meat' === $ingredient_spec['id'] ) {
		$compliance[] = $c99_compliance( 'no-raw-ground-meat-guidance', 'אין כאן המלצה לאכול בשר טחון נא. בבשר טחון מכבש או בקר יש להגיע ל-71.1°C במרכז ולבדוק במדחום מזון, לא לפי הצבע בלבד.', 'This page does not recommend eating raw ground meat. Ground lamb or beef should reach 71.1°C at the center and be checked with a food thermometer rather than color alone.', array( 'cdc-raw-kibbeh-salmonella-2013', 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ), false );
	}
	if ( 'ingredient-syrian-whole-chicken' === $ingredient_spec['id'] ) {
		$compliance[] = $c99_compliance( 'fully-cooked-whole-poultry', 'יש לבשל עוף שלם ומילויו לפי תוכנית בטיחות והנחיית טמפרטורה מאומתת, בלי לפרסם זמן אוניברסלי.', 'Cook whole poultry and its stuffing under a safety plan and validated temperature guidance without publishing a universal time.', array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ), false );
	}
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $ingredient_spec['id'],
		'type' => 'ingredient',
		'slug' => $ingredient_spec['slug'],
		'parent_id' => 'cuisine-syrian-regional',
		'name' => $c99_text( $ingredient_spec['he'], $ingredient_spec['en'] ),
		'summary' => $c99_text(
			isset( $ingredient_spec['summary_he'] ) ? $ingredient_spec['summary_he'] : $ingredient_spec['fact_he'] . ' הרשומה מיועדת לבחירת חומר גלם מושכלת ואינה הצעת מוצר.',
			isset( $ingredient_spec['summary_en'] ) ? $ingredient_spec['summary_en'] : $ingredient_spec['fact_en'] . ' The record supports informed ingredient selection and is not a product offer.'
		),
		'region' => $ingredient_spec['region'],
		'primary_intent' => $c99_text(
			isset( $ingredient_spec['intent_he'] ) ? $ingredient_spec['intent_he'] : 'להבין את הזהות, התפקיד והבדיקות הנדרשות עבור ' . $ingredient_spec['he'] . '.',
			isset( $ingredient_spec['intent_en'] ) ? $ingredient_spec['intent_en'] : 'Understand the identity, role and required checks for ' . $ingredient_spec['en'] . '.'
		),
		'primary_keyword' => $c99_text(
			isset( $ingredient_spec['keyword_he'] ) ? $ingredient_spec['keyword_he'] : 'מדריך ' . $ingredient_spec['he'],
			isset( $ingredient_spec['keyword_en'] ) ? $ingredient_spec['keyword_en'] : $ingredient_spec['en'] . ' ingredient guide'
		),
		'schema_type' => 'Article',
		'facts' => $facts,
		'relations' => $c99_syrian_relations,
		'compliance' => $compliance,
		'prompt_en' => ( in_array( $ingredient_spec['id'], array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat' ), true ) ? 'Commercial culinary editorial photograph of ' : 'Private editorial ingredient plate of ' ) . $ingredient_spec['visual'] . ', accurate scale, neutral daylight and food-safe styling.',
	) );
}

$c99_syrian_technique_specs = array(
	array(
		'id' => 'technique-syrian-bulgur-hydration', 'slug' => 'syrian-bulgur-hydration', 'he' => 'איך מרטיבים בורגול לקובה', 'en' => 'How to Hydrate Bulgur for Kibbeh', 'region' => 'syria-national',
		'summary_he' => 'בורגול יבש, בורגול רטוב ובורגול שנח אינם אותו חומר ביד. גודל הגרגר, טמפרטורת המים, זמן המנוחה והסחיטה משנים את היכולת לעצב מעטפת קובה. המדריך מלמד מה לראות ולהרגיש, בלי להבטיח יחס קסם שמתאים לכל שקית.',
		'summary_en' => 'Dry bulgur, freshly moistened bulgur and rested bulgur behave differently in the hand. Grain size, water temperature, resting time and draining shape the ability to form a kibbeh shell. This guide explains what to observe without promising one magic ratio for every pack.',
		'fact_he' => 'מחקרי הידרציה של בורגול מודדים ספיחת מים לאורך זמן ובתנאים שונים. הם תומכים בעבודה מדודה עם זמן, טמפרטורה ומרקם, אך אינם קובעים יחס אוניברסלי לקובה חלבית.',
		'fact_en' => 'Bulgur hydration studies measure water uptake over time and under different conditions. They support deliberate attention to time, temperature and texture but do not establish one universal ratio for Aleppine kibbeh.',
		'dimension' => 'scientific', 'evidence' => 'peer_reviewed_context', 'sources' => array( 'bulgur-hydration-2025', 'bulgur-hydration-cereal-chemistry', 'avs-mirvet-aleppo' ),
		'target' => 'dish-kibbeh-meshwiyyeh', 'ingredients' => array( 'ingredient-syrian-bulgur' ),
		'target_sources' => array( 'avs-mirvet-aleppo' ),
		'ingredient_sources' => array( 'ingredient-syrian-bulgur' => array( 'avs-mirvet-aleppo' ) ),
		'intent_he' => 'להבין כיצד להרטיב בורגול לקובה לפי מרקם ולא לפי יחס אוניברסלי.',
		'intent_en' => 'Learn how to hydrate bulgur for kibbeh by texture rather than a universal ratio.',
		'keyword_he' => 'איך משרים בורגול לקובה', 'keyword_en' => 'how to hydrate bulgur for kibbeh',
		'visual' => 'a tactile four-stage bulgur hydration sequence showing dry grain, measured moistening, rested grain and cohesive final texture in identical handmade bowls, no ratios or text',
	),
	array(
		'id' => 'technique-syrian-kibbeh-shell-shaping', 'slug' => 'syrian-kibbeh-shell-shaping', 'he' => 'עיצוב מעטפת קובה', 'en' => 'Kibbeh Shell Shaping', 'region' => 'syria-national',
		'summary_he' => 'שיטה לעיצוב מעטפת ומילוי כיחידות נפרדות, עם תשומת לב לעובי, סגירה ואחידות. הרשומה אינה מפרסמת נוסח בשר נא לאכילה.',
		'summary_en' => 'A method for treating shell and filling as separate structures, with attention to thickness, closure and consistency. The record does not publish a raw-meat eating version.',
		'fact_he' => 'המקורות מתעדים צורות קובה ממולאות שונות; עובי וסגירה הם משתני תהליך המחייבים ניסוי קולינרי לפני פרסום.',
		'fact_en' => 'The sources document different filled kibbeh forms; thickness and closure are process variables requiring culinary testing before publication.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'avs-mirvet-aleppo' ),
		'target' => 'dish-kibbeh-meshwiyyeh', 'ingredients' => array( 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat' ),
		'target_sources' => array( 'avs-mirvet-aleppo' ),
		'ingredient_sources' => array(
			'ingredient-syrian-bulgur' => array( 'avs-mirvet-aleppo' ),
			'ingredient-syrian-red-meat' => array( 'avs-mirvet-aleppo' ),
		),
		'visual' => 'a fully cooked kibbeh cross-section study that makes shell thickness, seam and filling cavity visible, no raw meat handling scene',
	),
	array(
		'id' => 'technique-syrian-kibbeh-cooking', 'slug' => 'syrian-kibbeh-cooking', 'he' => 'איך מבשלים קובה בבטחה', 'en' => 'How to Cook Kibbeh Safely', 'region' => 'syria-national',
		'summary_he' => 'קובה יכולה להיכנס לשמן, לגריל, לסיר מים או לרוטב, וכל שיטה יוצרת מעטפת ומרכז שונים. המטרה המשותפת היא קובה מבושלת היטב, עסיסית ובעלת מרקם ברור, עם בדיקת טמפרטורה במקום ניחוש לפי צבע.',
		'summary_en' => 'Kibbeh may meet hot oil, a grill, simmering water or sauce, and each path creates a different shell and center. The shared goal is a fully cooked, juicy kibbeh with a clear texture, checked by temperature rather than guessed from color.',
		'fact_he' => 'לפי הנחיות USDA, בשר טחון מכבש או בקר צריך להגיע ל-71.1°C במרכז ולהיבדק במדחום מזון. תיעוד התפרצות שנקשרה לקובה מבשר טחון נא מסביר מדוע המסלול הזה עוסק רק בקובה מבושלת.',
		'fact_en' => 'USDA guidance calls for ground lamb or beef to reach 71.1°C at the center and be checked with a food thermometer. A documented outbreak linked to raw ground-meat kibbeh is why this pathway covers cooked kibbeh only.',
		'dimension' => 'scientific', 'evidence' => 'regulatory_standard', 'sources' => array( 'foodsafety-safe-temperatures', 'cdc-raw-kibbeh-salmonella-2013', 'israel-moh-food-hygiene', 'avs-mirvet-aleppo' ),
		'target' => 'dish-kibbeh-meshwiyyeh', 'ingredients' => array( 'ingredient-syrian-red-meat', 'ingredient-syrian-bulgur' ),
		'target_sources' => array( 'avs-mirvet-aleppo' ),
		'ingredient_sources' => array(
			'ingredient-syrian-red-meat' => array( 'avs-mirvet-aleppo' ),
			'ingredient-syrian-bulgur' => array( 'avs-mirvet-aleppo' ),
		),
		'intent_he' => 'להכיר שיטות בישול לקובה ולבדוק שהיא מבושלת בבטחה.',
		'intent_en' => 'Explore kibbeh cooking methods and verify a safely cooked center.',
		'keyword_he' => 'איך מבשלים קובה', 'keyword_en' => 'how to cook kibbeh safely',
		'visual' => 'four appetizing fully cooked kibbeh outcomes for simmering, grilling, frying and sauce cooking, each cut open with no pink center and no time or temperature text',
		'compliance' => array( $c99_compliance( 'raw-kibbeh-prohibited', 'המסלול אינו ממליץ על אכילת קובה מבשר נא. בקובה עם בשר טחון מכבש או בקר יש להגיע ל-71.1°C במרכז ולבדוק במדחום מזון.', 'This pathway does not recommend eating raw-meat kibbeh. Kibbeh containing ground lamb or beef should reach 71.1°C at the center and be checked with a food thermometer.', array( 'cdc-raw-kibbeh-salmonella-2013', 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ), false ) ),
	),
	array(
		'id' => 'technique-syrian-yogurt-sauce-stability', 'slug' => 'syrian-yogurt-sauce-stability', 'he' => 'יציבות רוטב יוגורט', 'en' => 'Yogurt Sauce Stability', 'region' => 'homs',
		'summary_he' => 'מפת תהליך לרוטב יוגורט חם: ערבוב, קצב חימום, תנועה ושילוב המנה. כל פרמטר ייבדק ביוגורט המסוים.',
		'summary_en' => 'A process map for hot yogurt sauce: mixing, heating rate, movement and dish integration. Every parameter must be tested with the specific yogurt.',
		'fact_he' => 'ספרות מבנה החלבון מספקת הקשר להבנת יציבות, אך אינה קובעת טמפרטורה או תוספת מייצב למנות הסוריות ברשומה.',
		'fact_en' => 'Protein-structure literature provides stability context but does not establish a temperature or stabilizer addition for the Syrian dishes in this record.',
		'dimension' => 'scientific', 'evidence' => 'peer_reviewed_context', 'sources' => array( 'yogurt-protein-structure-2023', 'avs-nariman-homs' ),
		'target' => 'dish-kibbeh-labaniyyeh', 'ingredients' => array( 'ingredient-syrian-fresh-yogurt' ),
		'target_sources' => array( 'avs-nariman-homs' ),
		'ingredient_sources' => array( 'ingredient-syrian-fresh-yogurt' => array( 'avs-nariman-homs' ) ),
		'visual' => 'a controlled hot-yogurt sauce study showing smooth, beginning-to-separate and separated textures in labeled metadata only, no embedded text',
	),
	array(
		'id' => 'technique-syrian-stuffing-grape-leaves', 'slug' => 'syrian-stuffing-grape-leaves', 'he' => 'מילוי וגלגול עלי גפן', 'en' => 'Stuffing and Rolling Grape Leaves', 'region' => 'damascus-and-aleppo-diaspora',
		'summary_he' => 'שיטה לבחירת עלה, חלוקת מילוי, גלגול וסידור לסיר, תוך שמירת ההבדל בין יבראק, יברה וילנג׳י.',
		'summary_en' => 'A method for selecting leaves, portioning filling, rolling and arranging the pot while preserving differences among yabraq, yebra and yalangi.',
		'fact_he' => 'העדות הדמשקאית ומקור משפחתי מתעדים גלגול עלי גפן בנוסחים שונים; צורת גלגול אחת אינה מוכיחה מילוי זהה.',
		'fact_en' => 'Damascene testimony and a family source document rolled grape leaves in different versions; one rolling shape does not prove an identical filling.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'avs-razan-damascus', 'jfs-yebra-apricots' ),
		'target' => 'dish-yabraq-yebra', 'ingredients' => array( 'ingredient-syrian-grape-leaves', 'ingredient-syrian-rice' ),
		'visual' => 'a precise sequence of flat grape leaf, modest rice filling, folded sides, tight roll and packed pot, no hands or text',
	),
	array(
		'id' => 'technique-syrian-sour-fruit-braising', 'slug' => 'syrian-sour-fruit-braising', 'he' => 'בישול ברוטב פרי חמוץ', 'en' => 'Sour Fruit Braising', 'region' => 'aleppo',
		'summary_he' => 'שיטה לבישול בשר או קובה עם דובדבן חמוץ או חבוש תוך הפרדת זהות הפרי, ריכוז הרוטב וזמן הבישול.',
		'summary_en' => 'A method for cooking meat or kibbeh with sour cherry or quince while separating fruit identity, sauce concentration and cooking time.',
		'fact_he' => 'מקורות חלביים מתעדים מנות פרי חמוץ, ומחקרי פרי מספקים הקשר לשונות בחומצות. המחקרים אינם מדידת המנה.',
		'fact_en' => 'Aleppo sources document sour-fruit dishes, while fruit studies provide context for acid variation. The studies are not measurements of the dish.',
		'dimension' => 'scientific', 'evidence' => 'peer_reviewed_context', 'sources' => array( 'aleppo-project-cuisine-2017', 'sour-cherry-organic-acids-2020' ),
		'ingredients' => array(),
		'relations' => array(
			$c99_relation( 'used_in', 'dish-kebab-bil-karaz', 'ענף הדובדבן של מפת התהליך קשור לקבב ביל כרז בלבד.', 'The sour-cherry branch of the process map is linked only to kebab bil karaz.', false, array( 'aleppo-project-cuisine-2017' ), 'official_source' ),
			$c99_relation( 'references', 'ingredient-syrian-sour-cherry', 'הדובדבן החמוץ מתועד עם קבב ביל כרז ואינו מוצג כרכיב בקובה ספרג׳ליה.', 'Sour cherry is documented with kebab bil karaz and is not presented as an ingredient in kibbeh safarjaliyyeh.', false, array( 'aleppo-project-cuisine-2017' ), 'official_source' ),
			$c99_relation( 'used_in', 'dish-kibbeh-safarjaliyyeh', 'ענף החבוש של מפת התהליך קשור לקובה ספרג׳ליה בלבד.', 'The quince branch of the process map is linked only to kibbeh safarjaliyyeh.', false, array( 'aleppo-project-cuisine-2017' ), 'official_source' ),
			$c99_relation( 'references', 'ingredient-syrian-quince', 'החבוש מתועד עם קובה ספרג׳ליה ואינו מוצג כרכיב בקבב ביל כרז.', 'Quince is documented with kibbeh safarjaliyyeh and is not presented as an ingredient in kebab bil karaz.', false, array( 'aleppo-project-cuisine-2017' ), 'official_source' ),
		),
		'cross_sell_ids' => array( 'ingredient-syrian-sour-cherry', 'ingredient-syrian-quince' ),
		'visual' => 'two separate braising studies using sour cherry and quince, identical cooked-meat scale and no suggestion that the fruits are interchangeable',
	),
	array(
		'id' => 'technique-syrian-charred-eggplant', 'slug' => 'syrian-charred-eggplant', 'he' => 'קליית חציל', 'en' => 'Charred Eggplant', 'region' => 'hama',
		'summary_he' => 'שיטה לבניית ארומת קלייה ומרקם חציל לבאטרש, עם הפרדה בין חריכה חיצונית, ריכוך פנימי וניקוז.',
		'summary_en' => 'A method for building char aroma and eggplant texture for batersh, separating exterior charring, interior softening and draining.',
		'fact_he' => 'העדות מחמה קושרת חציל לבאטרש. עוצמת הקלייה וזמן הניקוז הם משתני ניסוי, לא עובדות קבועות מן המקור.',
		'fact_en' => 'The Hama testimony links eggplant to batersh. Charring intensity and draining time are test variables, not fixed facts from the source.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'avs-noor-hama' ),
		'target' => 'dish-batersh-hama', 'ingredients' => array( 'ingredient-syrian-eggplant', 'ingredient-syrian-tahini' ),
		'visual' => 'a charred eggplant process study with blackened intact skin, collapsed cooked flesh and a separately drained pulp sample',
	),
	array(
		'id' => 'technique-syrian-onion-browning-sayadiyah', 'slug' => 'syrian-onion-browning-sayadiyah', 'he' => 'השחמת בצל לסיאדיה', 'en' => 'Onion Browning for Sayadiyah', 'region' => 'syrian-coast',
		'summary_he' => 'שיטת השחמה לבניית צבע וטעם בשכבת האורז של סיאדיה, עם בקרת חום ונקודת עצירה לפי ניסוי.',
		'summary_en' => 'A browning method for building color and flavor in sayadiyah rice, with heat control and a tested stop point.',
		'fact_he' => 'העדות החופית קושרת בצל לסיאדיה; דרגת ההשחמה המדויקת נשארת משתנה קולינרי לבדיקה.',
		'fact_en' => 'The coastal testimony links onion to sayadiyah; the exact browning level remains a culinary variable for testing.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'avs-zainab-coast' ),
		'target' => 'dish-sayadiyah-syrian-coast', 'ingredients' => array( 'ingredient-syrian-onion', 'ingredient-syrian-rice', 'ingredient-syrian-olive-oil' ),
		'target_sources' => array( 'avs-zainab-coast' ),
		'ingredient_sources' => array(
			'ingredient-syrian-onion' => array( 'avs-zainab-coast' ),
			'ingredient-syrian-rice' => array( 'avs-zainab-coast' ),
			'ingredient-syrian-olive-oil' => array( 'avs-zainab-coast' ),
		),
		'visual' => 'four stages of sliced onion browning from translucent to deep brown, no burned black stage and no embedded labels',
	),
	array(
		'id' => 'technique-syrian-saj-bread', 'slug' => 'syrian-saj-bread', 'he' => 'אפיית לחם סאג׳', 'en' => 'Syrian Saj Bread', 'region' => 'northern-southern-and-eastern-syria',
		'summary_he' => 'מיפוי של מתיחת בצק דק ואפייה על משטח קמור. סוג קמח, עובי, חום וזמן דורשים ניסוי וציוד מתאים.',
		'summary_en' => 'A map of stretching thin dough and baking it on a convex surface. Flour type, thickness, heat and time require testing and suitable equipment.',
		'fact_he' => 'עדויות אזוריות וסקר מורשת מתעדים מסורות לחם ומתקני אפייה; הם אינם מפרט בטיחות או ביצועים למכשיר מסוים.',
		'fact_en' => 'Regional testimonies and a heritage survey document bread traditions and baking installations; they are not a safety or performance specification for a particular appliance.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'avs-amani-afrin', 'avs-ghaimana-suwayda', 'avs-buthaina-east', 'unesco-syrian-ich-survey-2017' ),
		'target' => 'region-syria-euphrates-east', 'ingredients' => array(),
		'target_sources' => array( 'avs-buthaina-east' ),
		'visual' => 'thin flatbread baking across a convex saj surface in a controlled professional setup, no open public flame or costume scene',
	),
	array(
		'id' => 'technique-syrian-tannour-bread', 'slug' => 'syrian-tannour-bread', 'he' => 'אפיית לחם תנור', 'en' => 'Syrian Tannour Bread', 'region' => 'euphrates-east',
		'summary_he' => 'מפת תהליך ללחם הנאפה בדופן תנור, עם דגש על עובי, הדבקה, חום והוצאה בטוחה. אין בה הוראות לבניית תנור.',
		'summary_en' => 'A process map for bread baked against an oven wall, focusing on thickness, adhesion, heat and safe removal. It contains no oven-construction instructions.',
		'fact_he' => 'עדויות ממזרח סוריה וסקר מורשת מתעדים לחם ותנור כחלק ממסורת מקומית; פרטי ציוד ובטיחות דורשים מומחה.',
		'fact_en' => 'Eastern Syrian testimony and a heritage survey document bread and oven as local tradition; equipment and safety details require an expert.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'avs-rana-raqqa', 'avs-buthaina-east', 'unesco-syrian-ich-survey-2017' ),
		'target' => 'dish-thareed-raqqa-rural', 'ingredients' => array( 'ingredient-raqqa-tannour-bread' ),
		'target_sources' => array( 'avs-rana-raqqa' ),
		'ingredient_sources' => array( 'ingredient-raqqa-tannour-bread' => array( 'avs-rana-raqqa' ) ),
		'visual' => 'a professional tannour bread process seen from a safe distance with fully baked flatbread being removed by a proper tool, no child or bare-hand contact',
	),
	array(
		'id' => 'technique-syrian-mouneh', 'slug' => 'syrian-mouneh', 'he' => 'מונֶה ושימור עונתי', 'en' => 'Syrian Mouneh', 'region' => 'syria-national',
		'summary_he' => 'מסגרת מחקר לשימור עונתי של תוצרת. כל מוצר דורש תהליך, pH, פעילות מים, אריזה וחיי מדף משלו לפני פרסום או מכירה.',
		'summary_en' => 'A research framework for seasonal preservation. Every product requires its own process, pH, water activity, packaging and shelf-life evidence before publication or sale.',
		'fact_he' => 'מקורות מורשת ועדויות מתעדים מונֶה כפרקטיקה עונתית ומשפחתית.',
		'fact_en' => 'Heritage sources and testimony document mouneh as a seasonal and family practice.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'unesco-syrian-ich-survey-2017', 'avs-nariman-homs' ),
		'extra_facts' => array(
			$c99_syrian_fact( 'fact-syrian-mouneh-water-activity-boundary', 'scientific', 'הנחיית FDA לפעילות מים היא מסגרת לבדיקת מוצר ואינה אישור לחיי מדף של מוצר מונֶה כלשהו.', 'FDA water-activity guidance is a product-evaluation framework and does not approve shelf life for any mouneh product.', 'regulatory_standard', 'technique_context', array( 'fda-water-activity' ) ),
		),
		'target' => 'cuisine-syrian-regional', 'ingredients' => array(),
		'target_sources' => array( 'unesco-syrian-ich-survey-2017', 'avs-nariman-homs' ),
		'visual' => 'seasonal mouneh research shelf with plain sealed jars, visible batch cards kept outside image and no shelf-stable or safety seal',
		'compliance' => array( $c99_compliance( 'mouneh-process-validation', 'אין לטעון לחיי מדף או יציבות ללא תהליך מאומת, מדידות מוצר, אריזה ותנאי אחסון.', 'Do not claim shelf life or stability without a validated process, product measurements, packaging and storage conditions.', array( 'fda-water-activity', 'israel-moh-food-hygiene' ), false ) ),
	),
	array(
		'id' => 'technique-syrian-cultured-dried-dairy', 'slug' => 'syrian-cultured-dried-dairy', 'he' => 'מוצרי חלב מותססים ומיובשים', 'en' => 'Cultured and Dried Dairy', 'region' => 'southern-and-eastern-syria',
		'summary_he' => 'מפת הבחנה בין קישק, ג׳מיד, היגט והקט. שמות מקומיים, חומרי בסיס, תסיסה, ייבוש ושימוש נשמרים כשדות נפרדים.',
		'summary_en' => 'A distinction map for kishk, jameed, higet and haqt. Local names, substrates, fermentation, drying and use remain separate fields.',
		'fact_he' => 'עדויות מקומיות משתמשות במונחים שונים למוצרים שונים או שטרם הוכחה זהותם.',
		'fact_en' => 'Local accounts use different terms for products that differ or have not been shown identical.',
		'dimension' => 'cultural', 'evidence' => 'official_source', 'sources' => array( 'avs-rahma-idlib', 'avs-samar-qamishli', 'avs-ghaimana-suwayda', 'avs-shahla-hauran' ),
		'extra_facts' => array(
			$c99_syrian_fact( 'fact-syrian-cultured-dried-dairy-water-activity-boundary', 'scientific', 'פעילות מים וחיי מדף דורשים מדידה של כל מוצר בפועל. הנחיית FDA אינה מוכיחה זהות, תהליך או חיי מדף של קישק, ג׳מיד, היגט או הקט.', 'Water activity and shelf life require measurement of each actual product. FDA guidance does not establish the identity, process or shelf life of kishk, jameed, higet or haqt.', 'regulatory_standard', 'technique_context', array( 'fda-water-activity' ) ),
		),
		'target' => 'dish-mansaf-mleihi', 'ingredients' => array( 'ingredient-syrian-kishk', 'ingredient-syrian-jameed', 'ingredient-syrian-higet', 'ingredient-syrian-haqt' ),
		'target_sources' => array( 'avs-ghaimana-suwayda', 'avs-shahla-hauran' ),
		'ingredient_sources' => array(
			'ingredient-syrian-kishk' => array( 'avs-rahma-idlib' ),
			'ingredient-syrian-jameed' => array( 'avs-shahla-hauran' ),
			'ingredient-syrian-higet' => array( 'avs-ghaimana-suwayda' ),
			'ingredient-syrian-haqt' => array( 'avs-samar-qamishli' ),
		),
		'visual' => 'four separate neutral research samples for kishk, jameed, higet and haqt with distinct vessels and no visual claim that any pair is identical',
		'compliance' => array( $c99_compliance( 'dried-dairy-product-validation', 'יש לאמת אלרגני חלב, פעילות מים, תהליך, אריזה ואחסון לכל מוצר בנפרד.', 'Verify milk allergens, water activity, process, packaging and storage for each product separately.', array( 'fda-water-activity', 'israel-moh-allergen-survey-2024', 'israel-moh-food-hygiene' ), false ) ),
	),
);

foreach ( $c99_syrian_technique_specs as $technique_spec ) {
	if ( isset( $technique_spec['relations'] ) ) {
		$c99_syrian_relations = $technique_spec['relations'];
	} else {
		$target_source_ids = isset( $technique_spec['target_sources'] ) ? $technique_spec['target_sources'] : $technique_spec['sources'];
		$target_evidence_class = isset( $technique_spec['target_evidence'] )
			? $technique_spec['target_evidence']
			: ( isset( $technique_spec['target_sources'] ) ? 'official_source' : $technique_spec['evidence'] );
		$c99_syrian_relations = array(
			$c99_relation( 'used_in', $technique_spec['target'], 'השיטה מקושרת למנה או למרכז המחקר לפי המקורות הרשומים.', 'The technique is linked to the dish or research hub according to the listed sources.', false, $target_source_ids, $target_evidence_class ),
		);
		foreach ( $technique_spec['ingredients'] as $ingredient_id ) {
			$ingredient_source_ids = isset( $technique_spec['ingredient_sources'][ $ingredient_id ] )
				? $technique_spec['ingredient_sources'][ $ingredient_id ]
				: $technique_spec['sources'];
			$ingredient_evidence_class = isset( $technique_spec['ingredient_evidence'][ $ingredient_id ] )
				? $technique_spec['ingredient_evidence'][ $ingredient_id ]
				: ( isset( $technique_spec['ingredient_sources'][ $ingredient_id ] ) ? 'official_source' : $technique_spec['evidence'] );
			$c99_syrian_relations[] = $c99_relation( 'requires', $ingredient_id, 'חומר הגלם הוא יעד בדיקה לתהליך, לא מפרט מוצר מאושר.', 'The ingredient is a process test target, not an approved product specification.', false, $ingredient_source_ids, $ingredient_evidence_class );
		}
	}
	$c99_syrian_facts = array(
		$c99_syrian_fact( 'fact-' . $technique_spec['slug'] . '-bounded-method', $technique_spec['dimension'], $technique_spec['fact_he'], $technique_spec['fact_en'], $technique_spec['evidence'], 'technique_context', $technique_spec['sources'] ),
	);
	if ( isset( $technique_spec['extra_facts'] ) ) {
		$c99_syrian_facts = array_merge( $c99_syrian_facts, $technique_spec['extra_facts'] );
	}
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $technique_spec['id'],
		'type' => 'technique',
		'slug' => $technique_spec['slug'],
		'parent_id' => 'cuisine-syrian-regional',
		'name' => $c99_text( $technique_spec['he'], $technique_spec['en'] ),
		'summary' => $c99_text( $technique_spec['summary_he'], $technique_spec['summary_en'] ),
		'region' => $technique_spec['region'],
		'primary_intent' => $c99_text(
			isset( $technique_spec['intent_he'] ) ? $technique_spec['intent_he'] : 'להבין מה השיטה עושה, אילו משתנים יש לבדוק ומה אסור להסיק לפני ניסוי.',
			isset( $technique_spec['intent_en'] ) ? $technique_spec['intent_en'] : 'Understand what the technique does, which variables need testing and what cannot be inferred before a trial.'
		),
		'primary_keyword' => $c99_text(
			isset( $technique_spec['keyword_he'] ) ? $technique_spec['keyword_he'] : 'טכניקת ' . $technique_spec['he'] . ' במטבח הסורי',
			isset( $technique_spec['keyword_en'] ) ? $technique_spec['keyword_en'] : $technique_spec['en'] . ' in Syrian cooking'
		),
		'schema_type' => 'Article',
		'facts' => $c99_syrian_facts,
		'relations' => $c99_syrian_relations,
		'cross_sell_ids' => isset( $technique_spec['cross_sell_ids'] ) ? $technique_spec['cross_sell_ids'] : $technique_spec['ingredients'],
		'compliance' => isset( $technique_spec['compliance'] ) ? $technique_spec['compliance'] : array(),
		'prompt_en' => ( in_array( $technique_spec['id'], array( 'technique-syrian-bulgur-hydration', 'technique-syrian-kibbeh-cooking' ), true ) ? 'Commercial culinary editorial photograph of ' : 'Private editorial process plate of ' ) . $technique_spec['visual'] . ', accurate food texture, controlled workspace and no embedded instruction text.',
	) );
}

$c99_syrian_tradition_specs = array(
	array(
		'id' => 'tradition-syrian-hospitality-sharing', 'slug' => 'syrian-hospitality-and-sharing', 'he' => 'אירוח ושיתוף במטבח הסורי', 'en' => 'Hospitality and Sharing in Syrian Cuisine',
		'parent' => 'cuisine-syrian-regional', 'region' => 'syria-national', 'community' => 'syrian-multi-community',
		'summary_he' => 'מסורת של הגשה משותפת, אירוח והעברת ידע סביב שולחן ומשפחה כפי שהיא עולה ממקורות המורשת והעדויות. דפוס אחד אינו מיוחס לכל משק בית.',
		'summary_en' => 'A tradition of shared serving, hospitality and knowledge transmission around table and family as reflected in heritage sources and testimonies. One pattern is not attributed to every household.',
		'fact_he' => 'מקורות המורשת והעדויות מציגים אוכל כמרחב של אירוח, זיכרון והעברה בין-דורית; היקף הטענה הוא תיאורי ולא כלל לאומי מוחלט.',
		'fact_en' => 'Heritage sources and testimonies present food as a space of hospitality, memory and intergenerational transmission; the claim is descriptive rather than an absolute national rule.',
		'sources' => array( 'unesco-syrian-ich-survey-2017', 'avs-heart-to-hearth' ), 'target' => 'cuisine-syrian-regional',
		'visual' => 'a shared Syrian family table with several regionally distinct fully cooked dishes and open serving space, no people, costumes or ceremonial props',
	),
	array(
		'id' => 'tradition-syrian-mouneh', 'slug' => 'syrian-mouneh-tradition', 'he' => 'מסורת המונֶה הסורית', 'en' => 'Syrian Mouneh Tradition',
		'parent' => 'cuisine-syrian-regional', 'region' => 'syria-national', 'community' => 'syrian-multi-community',
		'summary_he' => 'מסורת שימור עונתי של תוצרת, עבודה משפחתית ותכנון מזווה. הרשומה התרבותית נפרדת מאישור תהליך בטיחות או חיי מדף.',
		'summary_en' => 'A tradition of seasonal preservation, family work and pantry planning. The cultural record is separate from process-safety or shelf-life approval.',
		'fact_he' => 'סקר המורשת ועדויות הפרויקט מתעדים מונֶה כפרקטיקה עונתית ומשפחתית; כל יישום מוצר דורש ולידציה נפרדת.',
		'fact_en' => 'The heritage survey and project testimonies document mouneh as seasonal and family practice; every product implementation requires separate validation.',
		'sources' => array( 'unesco-syrian-ich-survey-2017', 'avs-heart-to-hearth' ), 'target' => 'technique-syrian-mouneh',
		'visual' => 'a seasonal preparation table with peppers, tomatoes, grains and plain preservation jars in distinct work zones, no shelf-life badge',
	),
	array(
		'id' => 'tradition-syrian-ramadan-eid-foodways', 'slug' => 'syrian-ramadan-and-eid-foodways', 'he' => 'מאכלי רמדאן ועיד בסוריה', 'en' => 'Syrian Ramadan and Eid Foodways',
		'parent' => 'cuisine-syrian-regional', 'region' => 'syria-national', 'community' => 'syrian-multi-community',
		'summary_he' => 'מפת מחקר למאכלי עונה, צום וחג בעדויות סוריות. היא אינה מניחה שכל קהילה, עיר או משפחה מציינת מועדים באותו תפריט.',
		'summary_en' => 'A research map for seasonal, fasting and festival foods in Syrian testimony. It does not assume that every community, city or family observes occasions with the same menu.',
		'fact_he' => 'מקורות קולינריים ועדויות מתעדים קשרים בין מנות, אירוח ומועדים מוסלמיים; ההיקף נשאר מקומי ומשפחתי לפי המקור.',
		'fact_en' => 'Culinary sources and testimonies document links among dishes, hospitality and Muslim occasions; scope remains local and family specific according to each source.',
		'sources' => array( 'avs-heart-to-hearth', 'lal-scents-flavors' ), 'target' => 'tradition-syrian-hospitality-sharing',
		'visual' => 'a quiet evening food table with dates kept outside the composition unless source-confirmed, several fully cooked Syrian dishes and no religious symbols',
	),
	array(
		'id' => 'tradition-aleppan-jewish-foodways', 'slug' => 'aleppan-jewish-foodways', 'he' => 'מסורות האוכל של יהודי חלב', 'en' => 'Aleppan Jewish Foodways',
		'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'aleppan-jewish',
		'summary_he' => 'מתכונים של משפחות יהודיות מחלב ממשיכים לחיות בתפוצות, בארוחות שבת וחג וגם במטבח היומיומי. כאן פוגשים קובה חמודה, דג׳אג׳ משווי ויברה עם משמשים מיובשים דרך סיפורים משפחתיים, בלי להפוך משפחה אחת לקול של קהילה שלמה.',
		'summary_en' => 'Recipes from Aleppan Jewish families continue in diaspora kitchens, at Shabbat and holiday tables and in everyday cooking. Meet kibbeh hamda, dajaj mashwi and yebra with dried apricots through family stories, without making one family the voice of an entire community.',
		'fact_he' => 'אנו, מוזיאון העם היהודי, והספרייה הלאומית מתעדים את קהילת יהודי סוריה ואת מסורת חלב. ארכיוני אוכל משפחתיים מוסיפים מנות וקולות אישיים, ובהם קובה חמודה, דג׳אג׳ משווי ויברה. יחד הם מציגים המשכיות עשירה, לא תפריט אחיד לכל יהודי חלב.',
		'fact_en' => 'ANU, Museum of the Jewish People, and the National Library of Israel document Syrian Jewry and Aleppan tradition. Family food archives add individual dishes and voices, including kibbeh hamda, dajaj mashwi and yebra. Together they show rich continuity rather than one uniform menu for all Aleppan Jews.',
		'sources' => array( 'anu-syrian-jewish-community', 'nli-aleppo-tradition', 'jfs-kibbeh-hamda', 'foodish-dajaj-mashwi', 'jfs-yebra-apricots' ), 'target' => 'dish-kibbeh-hamda-aleppan-jewish', 'target_sources' => array( 'jfs-kibbeh-hamda' ),
		'intent_he' => 'לגלות את מסורות האוכל של יהודי חלב דרך מנות וסיפורים משפחתיים.',
		'intent_en' => 'Discover Aleppan Jewish foodways through dishes and family stories.',
		'keyword_he' => 'מאכלי יהודי חלב', 'keyword_en' => 'Aleppan Jewish foodways',
		'visual' => 'an inviting Aleppan Jewish family table with fully cooked kibbeh hamda in its broth, stuffed roast chicken and yebra with visible dried apricots in separate dishes, no synagogue, ritual or holiday props',
	),
	array(
		'id' => 'tradition-damascene-jewish-foodways', 'slug' => 'damascene-jewish-foodways', 'he' => 'מסורות האוכל של יהודי דמשק', 'en' => 'Damascene Jewish Foodways',
		'parent' => 'region-syria-damascus', 'region' => 'damascus', 'community' => 'damascene-jewish',
		'summary_he' => 'מסורת קהילתית ומשפחתית מדמשק הכוללת גרסאות חג ומאכלי בית. היא נשמרת כשכבה סורית מובחנת לצד מסורות אחרות בעיר.',
		'summary_en' => 'A Damascene community and family tradition that includes festival versions and household dishes. It remains a distinct Syrian layer alongside other traditions in the city.',
		'fact_he' => 'מקורות הקהילה והמזון מתעדים יהדות דמשק וקבב מצה במסורת משפחתית דמשקאית; אין להסיק מהם תפריט אחיד לכל הקהילה.',
		'fact_en' => 'Community and food sources document Damascene Jewry and matzah kebab in a Damascene family tradition; they do not establish one menu for the entire community.',
		'sources' => array( 'anu-syrian-jewish-community', 'nli-damascus-tradition', 'foodish-matzah-kebab' ), 'target' => 'dish-matzah-kebab-damascene-jewish', 'target_sources' => array( 'foodish-matzah-kebab' ),
		'visual' => 'a Damascene Jewish family-food research table with fully cooked matzah kebab and source-confirmed accompaniments, no ritual objects',
	),
	array(
		'id' => 'tradition-syrian-armenian-aleppo', 'slug' => 'syrian-armenian-aleppo-foodways', 'he' => 'מסורות סוריות-ארמניות בחלב', 'en' => 'Syrian Armenian Foodways in Aleppo',
		'parent' => 'region-syria-aleppo', 'region' => 'aleppo', 'community' => 'syrian-armenian',
		'summary_he' => 'מסורות אוכל ארמניות-סוריות הקשורות לחלב, לעקירה ולזיכרון. הרשומה נשענת על מקור אוכל ייעודי ואינה מסיקה תפריט מעצם נוכחות קהילה.',
		'summary_en' => 'Armenian Syrian foodways connected to Aleppo, displacement and memory. The record relies on a food-specific source and does not infer a menu merely from community presence.',
		'fact_he' => 'מקור Smithsonian מתעד אוכל וזיכרון בתפוצה הארמנית הסורית; ההקשר הקולינרי נשמר לפי הסיפורים המתועדים ולא מורחב לכל משפחה.',
		'fact_en' => 'The Smithsonian source documents food and memory in the Syrian Armenian diaspora; culinary context follows the documented stories and is not generalized to every family.',
		'sources' => array( 'smithsonian-syrian-armenian-foodways' ), 'target' => 'region-syria-aleppo',
		'visual' => 'a Syrian Armenian Aleppo memory-food research table based only on source-confirmed food forms, neutral domestic setting and no ethnic costume',
	),
	array(
		'id' => 'tradition-kurdish-afrin', 'slug' => 'kurdish-afrin-foodways', 'he' => 'מסורות אוכל כורדיות מאפרין', 'en' => 'Kurdish Afrin Foodways',
		'parent' => 'region-syria-aleppo', 'region' => 'afrin', 'community' => 'kurdish-afrin',
		'summary_he' => 'מסורת אוכל כורדית מאפרין לפי עדותה הישירה של אמאני. היא מציגה נקודת מבט מקומית ואינה מוגדרת כמטבח הכורדי כולו.',
		'summary_en' => 'Kurdish Afrin foodways according to Amani\'s direct testimony. It presents a local viewpoint and is not defined as all Kurdish cuisine.',
		'fact_he' => 'עדותה של אמאני מספקת מקור אוכל ישיר מאפרין, ובכללו קיטאוויה; היקף הטענה נשאר משפחתי ומקומי.',
		'fact_en' => 'Amani\'s testimony supplies a direct Afrin food source, including kitawiyeh; claim scope remains family and local.',
		'sources' => array( 'avs-amani-afrin' ), 'target' => 'dish-kitawiyeh-afrin-kurdish',
		'visual' => 'a Kurdish Afrin family-food research table centered on fully cooked kitawiyeh and only source-confirmed accompaniments, no flags or costume',
	),
	array(
		'id' => 'tradition-druze-suwayda', 'slug' => 'druze-suwayda-foodways', 'he' => 'מסורות אוכל דרוזיות מא-סווידא', 'en' => 'Druze Suwayda Foodways',
		'parent' => 'region-syria-suwayda', 'region' => 'suwayda', 'community' => 'druze-suwayda',
		'summary_he' => 'מסורת אוכל דרוזית מא-סווידא לפי עדותה של גהימאנה, כולל הכנת מליחי מובחנת. המקור אינו מייצג כל בית דרוזי.',
		'summary_en' => 'Druze Suwayda foodways according to Ghaimana\'s testimony, including a distinct Mleihi preparation. The source does not represent every Druze household.',
		'fact_he' => 'גהימאנה מתעדת מסורת משפחתית ודרוזית מא-סווידא ובה מליחי עם יוגורט טרי ואגוזים; היקף הטענה הוא העדות המתועדת.',
		'fact_en' => 'Ghaimana documents a Suwayda Druze family tradition including Mleihi with fresh yogurt and nuts; claim scope is the documented testimony.',
		'sources' => array( 'avs-ghaimana-suwayda' ), 'target' => 'preparation-mleihi-suwayda-fresh-yogurt',
		'visual' => 'a Suwayda Druze family-food research table centered on the fresh-yogurt and nut Mleihi preparation, no religious symbols or costume',
	),
);

foreach ( $c99_syrian_tradition_specs as $tradition_spec ) {
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $tradition_spec['id'],
		'type' => 'tradition',
		'slug' => $tradition_spec['slug'],
		'parent_id' => $tradition_spec['parent'],
		'name' => $c99_text( $tradition_spec['he'], $tradition_spec['en'] ),
		'summary' => $c99_text( $tradition_spec['summary_he'], $tradition_spec['summary_en'] ),
		'region' => $tradition_spec['region'],
		'community' => $tradition_spec['community'],
		'primary_intent' => $c99_text(
			isset( $tradition_spec['intent_he'] ) ? $tradition_spec['intent_he'] : 'להכיר את המסורת, את היקף העדות ואת הקשריה למנות בלי להכליל מעבר למקור.',
			isset( $tradition_spec['intent_en'] ) ? $tradition_spec['intent_en'] : 'Understand the tradition, evidence scope and dish connections without generalizing beyond the source.'
		),
		'primary_keyword' => $c99_text(
			isset( $tradition_spec['keyword_he'] ) ? $tradition_spec['keyword_he'] : $tradition_spec['he'] . ' מחקר',
			isset( $tradition_spec['keyword_en'] ) ? $tradition_spec['keyword_en'] : $tradition_spec['en'] . ' research guide'
		),
		'schema_type' => 'Article',
		'facts' => array(
			$c99_syrian_fact( 'fact-' . $tradition_spec['slug'] . '-community-scope', 'cultural', $tradition_spec['fact_he'], $tradition_spec['fact_en'], 'official_source', 'category', $tradition_spec['sources'] ),
		),
		'relations' => array(
			$c99_relation( 'references', $tradition_spec['target'], 'המסורת מקושרת ליעד תוכן תחום ומבוסס מקור.', 'The tradition links to a bounded, source-backed content target.', false, isset( $tradition_spec['target_sources'] ) ? $tradition_spec['target_sources'] : $tradition_spec['sources'], 'official_source' ),
		),
		'prompt_en' => ( 'tradition-aleppan-jewish-foodways' === $tradition_spec['id'] ? 'Commercial culinary editorial photograph of ' : 'Private editorial tradition plate of ' ) . $tradition_spec['visual'] . ', documentary natural light and no decorative stereotypes.',
	) );
}

$c99_syrian_mleihi_preparations = array(
	array(
		'id' => 'preparation-mleihi-suwayda-fresh-yogurt',
		'slug' => 'mleihi-suwayda-fresh-yogurt',
		'region' => 'suwayda',
		'community' => 'druze-suwayda',
		'name_he' => 'הכנת מליחי מא-סווידא עם יוגורט טרי',
		'name_en' => 'Suwayda Mleihi with Fresh Yogurt',
		'summary_he' => 'הכנת מליחי מא-סווידא המבוססת בעדותה של גהימאנה על יוגורט טרי ואגוזים. היא נשמרת בנפרד מהכנת חוראן המבוססת על מוצר חלב מיובש.',
		'summary_en' => 'A Suwayda Mleihi preparation based in Ghaimana\'s testimony on fresh yogurt and nuts. It remains separate from the Hauran preparation based on dried dairy.',
		'fact_he' => 'גהימאנה מתארת הכנת מליחי עם יוגורט טרי ואגוזים בהקשר א-סווידא והמשפחה הדרוזית שלה; הרשומה אינה מרחיבה את הנוסח לכל האזור.',
		'fact_en' => 'Ghaimana describes a Mleihi preparation with fresh yogurt and nuts in her Suwayda Druze family context; the record does not generalize the version to the entire region.',
		'sources' => array( 'avs-ghaimana-suwayda' ),
		'ingredients' => array( 'ingredient-syrian-fresh-yogurt', 'ingredient-syrian-unspecified-nuts', 'ingredient-syrian-red-meat' ),
		'visual' => 'the Suwayda Mleihi preparation with a fresh smooth yogurt sauce, cooked meat, bread and a visible generic nut component, no claim about nut type, no dried-yogurt balls or fried kibbeh',
	),
	array(
		'id' => 'preparation-mleihi-hauran-jameed',
		'slug' => 'mleihi-hauran-jameed',
		'region' => 'hauran',
		'community' => 'hauran-family-tradition',
		'name_he' => 'הכנת מליחי מחוראן עם ג׳מיד',
		'name_en' => 'Hauran Mleihi with Jameed',
		'summary_he' => 'הכנת מליחי מחוראן המתועדת בעדותה של שאהלה עם מוצר יוגורט מיובש או ג׳מיד וקובה מטוגנת. היא אינה מתמזגת עם נוסח היוגורט הטרי מא-סווידא.',
		'summary_en' => 'A Hauran Mleihi preparation documented in Shahla\'s testimony with dried yogurt or jameed and fried kibbeh. It is not merged with the fresh-yogurt Suwayda version.',
		'fact_he' => 'שאהלה מתארת בהקשר חוראן מליחי עם ג׳מיד וקובה מטוגנת; המונח וההכנה נשמרים לפי עדותה ואינם מוכיחים זהות עם היגט.',
		'fact_en' => 'Shahla describes Hauran Mleihi with jameed and fried kibbeh; the term and preparation follow her testimony and do not prove identity with higet.',
		'sources' => array( 'avs-shahla-hauran' ),
		'ingredients' => array( 'ingredient-syrian-jameed', 'ingredient-syrian-bulgur', 'ingredient-syrian-red-meat' ),
		'visual' => 'the Hauran Mleihi preparation with a dried-yogurt sauce, fully cooked fried kibbeh, cooked meat and bread, no fresh-yogurt and walnut presentation',
	),
);

foreach ( $c99_syrian_mleihi_preparations as $preparation_spec ) {
	$c99_syrian_relations = array(
		$c99_relation( 'part_of', 'dish-mansaf-mleihi', 'ההכנה היא וריאנט מתועד של המנה הקנונית מליחי.', 'The preparation is a documented variant of the canonical Mleihi dish.', false, $preparation_spec['sources'], 'official_source' ),
	);
	foreach ( $preparation_spec['ingredients'] as $ingredient_id ) {
		$c99_syrian_relations[] = $c99_relation( 'requires', $ingredient_id, 'חומר הגלם מתועד בהכנה זו ודורש אימות מוצר וכמות לפני ניסוי.', 'The ingredient is documented in this preparation and requires product and quantity verification before testing.', false, $preparation_spec['sources'], 'official_source' );
	}
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $preparation_spec['id'],
		'type' => 'preparation',
		'slug' => $preparation_spec['slug'],
		'parent_id' => 'dish-mansaf-mleihi',
		'name' => $c99_text( $preparation_spec['name_he'], $preparation_spec['name_en'] ),
		'summary' => $c99_text( $preparation_spec['summary_he'], $preparation_spec['summary_en'] ),
		'region' => $preparation_spec['region'],
		'community' => $preparation_spec['community'],
		'primary_intent' => $c99_text( 'להבין במה הכנת המליחי האזורית שונה מהווריאנט הדרום-סורי האחר לפני ניסוי קולינרי.', 'Understand how this regional Mleihi preparation differs from the other southern Syrian variant before a culinary trial.' ),
		'primary_keyword' => $c99_text( $preparation_spec['name_he'] . ' הבדלים', $preparation_spec['name_en'] . ' preparation differences' ),
		'schema_type' => 'Article',
		'facts' => array(
			$c99_syrian_fact( 'fact-' . $preparation_spec['slug'] . '-separate-preparation', 'cultural', $preparation_spec['fact_he'], $preparation_spec['fact_en'], 'official_source', 'entity', $preparation_spec['sources'] ),
		),
		'relations' => $c99_syrian_relations,
		'cross_sell_ids' => $preparation_spec['ingredients'],
		'compliance' => array(
			$c99_compliance( 'mleihi-allergen-and-cook-review', 'יש לאמת אלרגני חלב ואגוזים לפי הווריאנט, ולבשל בשר וקובה במלואם לפי תוכנית בטיחות.', 'Verify dairy and nut allergens by variant, and fully cook meat and kibbeh under a safety plan.', array( 'israel-moh-allergen-survey-2024', 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ), false ),
		),
		'prompt_en' => 'Private editorial food study of ' . $preparation_spec['visual'] . ', component boundaries clearly visible, natural side light and neutral tableware.',
	) );
}

$c99_syrian_halawet_preparations = array(
	array(
		'id' => 'preparation-halawet-homs-cheese-semolina',
		'slug' => 'halawet-al-jibn-homs-cheese-semolina',
		'region' => 'homs',
		'community' => 'syrian-multi-community',
		'name_he' => 'הכנת חלאוות אל-ג׳בן מחומס עם גבינה וסולת',
		'name_en' => 'Homs Halawet Al Jibn with Cheese and Semolina',
		'summary_he' => 'הכנת חומס לפי עדותה של נרימאן, עם בצק גבינה וסולת, מילוי קרם וסירופ בהגשה. היא נשמרת בנפרד מעדות חמה ואינה משמשת להכרעת עיר המקור.',
		'summary_en' => 'A Homs preparation from Nariman\'s testimony, with a cheese and semolina dough, cream filling and syrup for serving. It remains separate from the Hama account and does not decide the city of origin.',
		'fact_he' => 'נרימאן מייחסת את המנה לחומס ומתארת גבינה, סולת, קרם וסירופ. העדות אינה קובעת יחסים, זני מוצר או תהליך מסחרי מאומת.',
		'fact_en' => 'Nariman attributes the dish to Homs and describes cheese, semolina, cream and syrup. The testimony does not establish ratios, product varieties or a validated commercial process.',
		'sources' => array( 'avs-nariman-homs' ),
		'ingredients' => array( 'ingredient-syrian-cheese', 'ingredient-syrian-semolina', 'ingredient-syrian-qeshta-cream', 'ingredient-syrian-sugar-syrup' ),
		'visual' => 'the Homs account as fully prepared cheese and semolina rolls with a plain cream filling and separate syrup, no pistachio claim and no origin badge',
	),
	array(
		'id' => 'preparation-halawet-hama-qeshta-pistachio',
		'slug' => 'halawet-al-jibn-hama-qeshta-pistachio',
		'region' => 'hama',
		'community' => 'syrian-multi-community',
		'name_he' => 'הכנת חלאוות אל-ג׳בן מחמה עם קשטה ופיסטוקים',
		'name_en' => 'Hama Halawet Al Jibn with Qeshta and Pistachios',
		'summary_he' => 'הכנת חמה לפי עדותה של נור, המדגישה בצק עדין, קשטה איכותית ופיסטוקים. היא נשמרת בנפרד מעדות חומס ואינה משמשת להכרעת עיר המקור.',
		'summary_en' => 'A Hama preparation from Noor\'s testimony emphasizing delicate dough, high-quality qeshta and pistachios. It remains separate from the Homs account and does not decide the city of origin.',
		'fact_he' => 'נור מייחסת את המנה לחמה ומדגישה קשטה ופיסטוקים. העדות אינה מספקת הרכב בצק, יחסים או תהליך מסחרי מאומת.',
		'fact_en' => 'Noor attributes the dish to Hama and emphasizes qeshta and pistachios. The testimony does not provide dough composition, ratios or a validated commercial process.',
		'sources' => array( 'avs-noor-hama' ),
		'ingredients' => array( 'ingredient-syrian-qeshta-cream', 'ingredient-syrian-pistachios' ),
		'visual' => 'the Hama account as delicate filled rolls with a plain qeshta center and chopped pistachios, no cheese or semolina formula claim and no origin badge',
	),
);

foreach ( $c99_syrian_halawet_preparations as $preparation_spec ) {
	$c99_syrian_relations = array(
		$c99_relation( 'part_of', 'dish-halawet-al-jibn', 'ההכנה היא עדות אזורית נפרדת תחת ישות המנה שאינה מכריעה את מחלוקת המקור.', 'The preparation is a separate regional account under the dish entity that does not decide the origin dispute.', false, $preparation_spec['sources'], 'official_source' ),
	);
	foreach ( $preparation_spec['ingredients'] as $ingredient_id ) {
		$c99_syrian_relations[] = $c99_relation( 'requires', $ingredient_id, 'חומר הגלם מתועד בעדות האזורית הזאת ודורש אימות מוצר וכמות לפני ניסוי.', 'The ingredient is documented in this regional account and requires product and quantity verification before testing.', false, $preparation_spec['sources'], 'official_source' );
	}
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $preparation_spec['id'],
		'type' => 'preparation',
		'slug' => $preparation_spec['slug'],
		'parent_id' => 'dish-halawet-al-jibn',
		'name' => $c99_text( $preparation_spec['name_he'], $preparation_spec['name_en'] ),
		'summary' => $c99_text( $preparation_spec['summary_he'], $preparation_spec['summary_en'] ),
		'region' => $preparation_spec['region'],
		'community' => $preparation_spec['community'],
		'primary_intent' => $c99_text( 'להבין את עדות ההכנה האזורית בלי למזג אותה עם העיר האחרת ובלי להכריע מקור.', 'Understand the regional preparation account without merging it with the other city or deciding origin.' ),
		'primary_keyword' => $c99_text( $preparation_spec['name_he'] . ' מחקר', $preparation_spec['name_en'] . ' research' ),
		'schema_type' => 'Article',
		'facts' => array(
			$c99_syrian_fact( 'fact-' . $preparation_spec['slug'] . '-regional-account', 'cultural', $preparation_spec['fact_he'], $preparation_spec['fact_en'], 'official_source', 'entity', $preparation_spec['sources'] ),
		),
		'relations' => $c99_syrian_relations,
		'cross_sell_ids' => $preparation_spec['ingredients'],
		'compliance' => array(
			$c99_compliance( 'halawet-allergen-review', 'יש לאמת אלרגני חלב, חיטה ואגוזים לפי ההכנה ומוצרי הגלם שנבחרו.', 'Verify dairy, wheat and nut allergens according to the preparation and selected ingredients.', array( 'israel-moh-allergen-survey-2024' ), false ),
		),
		'prompt_en' => 'Private editorial food study of ' . $preparation_spec['visual'] . ', source-bounded composition, natural side light and neutral tableware.',
	) );
}

$c99_syrian_grape_leaf_preparations = array(
	array(
		'id' => 'preparation-yabraq-damascene',
		'slug' => 'yabraq-damascene-meat-rice',
		'region' => 'damascus',
		'community' => 'syrian-multi-community',
		'name_he' => 'הכנת יבראק דמשקאית עם בשר ואורז',
		'name_en' => 'Damascene Yabraq with Meat and Rice',
		'summary_he' => 'הכנת יבראק דמשקאית המתועדת בעדותה של Razan עם עלי גפן, אורז, בשר טחון, תבלינים, מעט שומן, לימון, שום ובשר על העצם לבישול איטי. זו עדות אזורית, לא מפרט ייצור מאומת.',
		'summary_en' => 'A Damascene yabraq preparation documented in Razan\'s testimony with grape leaves, rice, minced meat, spices, a little fat, lemon, garlic and bone-in meat for slow cooking. It is a regional account, not a validated production specification.',
		'fact_he' => 'העדות הדמשקאית מפרידה בין יבראק בשרי לבין ילנג׳י צמחוני ומתארת את היבראק כמנת אירוח וחג. הכמות, הזן, הטמפרטורה והתשואה עדיין דורשים ניסוי מטבח.',
		'fact_en' => 'The Damascene account separates meat-filled yabraq from meatless yalangi and describes yabraq as a celebration and gathering dish. Quantity, variety, temperature and yield still require a kitchen trial.',
		'sources' => array( 'avs-razan-damascus' ),
		'ingredients' => array( 'ingredient-syrian-grape-leaves', 'ingredient-syrian-rice', 'ingredient-syrian-red-meat', 'ingredient-syrian-lemon', 'ingredient-syrian-garlic' ),
		'visual' => 'fully cooked narrow Damascene grape-leaf rolls with one cut roll showing rice and cooked meat, a separate bone-in cooked meat component, lemon and garlic cues only, no raw filling',
	),
	array(
		'id' => 'preparation-yebra-aleppan-jewish-apricot',
		'slug' => 'yebra-aleppan-jewish-apricot',
		'region' => 'aleppo',
		'community' => 'aleppan-jewish',
		'name_he' => 'הכנת יברה משפחתית חלבית-יהודית עם משמשים',
		'name_en' => 'Aleppan Jewish Family Yebra with Apricots',
		'summary_he' => 'הכנת יברה מתועדת ממשפחת Charles Dabbah, שמסלול משפחתה מתחיל בחלב, עם בשר בקר, אורז, פלפל אנגלי, עלי גפן, משמשים מיובשים, שום, נענע מיובשת ונוזל חמוץ בשם אוּ׳. היא אינה מוצגת כנוסח של כל יהודי חלב.',
		'summary_en' => 'A yebra preparation documented by the Charles Dabbah family, whose family journey begins in Aleppo, with beef, rice, allspice, grape leaves, dried apricots, garlic, dried mint and a sour liquid named ou. It is not presented as the version of every Aleppan Jewish family.',
		'fact_he' => 'Jewish Food Society מפרט את רכיבי ההכנה, תהליך הגלגול, קירור קצר לפני בישול ובישול איטי. הנתונים נשמרים כעדות משפחתית ועדיין אינם בדיקת מתכון, תשואה או בטיחות של Complete99.',
		'fact_en' => 'Jewish Food Society lists the ingredients, rolling process, a brief freeze before cooking and slow cooking. The data remain a family preparation record and are not yet a Complete99 recipe, yield or safety test.',
		'sources' => array( 'jfs-yebra-apricots' ),
		'ingredients' => array( 'ingredient-syrian-grape-leaves', 'ingredient-syrian-rice', 'ingredient-syrian-red-meat', 'ingredient-syrian-dried-apricot', 'ingredient-syrian-garlic', 'ingredient-syrian-allspice', 'ingredient-syrian-dried-mint', 'ingredient-aleppan-ou-souring-concentrate' ),
		'visual' => 'fully cooked Aleppan Jewish family yebra in compact layers with visible dried apricots and garlic cloves, one cut roll showing cooked beef and rice, restrained dark sour liquid and no ritual props',
		'ou_identity_note' => true,
	),
);

foreach ( $c99_syrian_grape_leaf_preparations as $preparation_spec ) {
	$facts = array(
		$c99_syrian_fact( 'fact-' . $preparation_spec['slug'] . '-separate-preparation', 'cultural', $preparation_spec['fact_he'], $preparation_spec['fact_en'], 'official_source', 'entity', $preparation_spec['sources'] ),
	);
	if ( ! empty( $preparation_spec['ou_identity_note'] ) ) {
		$facts[] = $c99_syrian_fact(
			'fact-yebra-ou-source-term-identity-held',
			'structural',
			'דף המקור מציין אוּ׳ אך אינו מגדיר את הרכבו. לכן הקשר לתמרהינדי או לכל תרכיז אחר נשאר חסום עד מקור זהות נוסף.',
			'The source page names ou but does not define its composition. A link to tamarind or any other concentrate therefore remains held pending additional identity evidence.',
			'official_source',
			'entity',
			$preparation_spec['sources']
		);
	}
	$c99_syrian_relations = array(
		$c99_relation( 'part_of', 'dish-yabraq-yebra', 'ההכנה היא וריאנט אזורי או משפחתי מתועד של ישות המנה יבראק ויברה.', 'The preparation is a documented regional or family variant of the Yabraq and Yebra dish entity.', false, $preparation_spec['sources'], 'official_source' ),
	);
	foreach ( $preparation_spec['ingredients'] as $ingredient_id ) {
		$c99_syrian_relations[] = $c99_relation( 'requires', $ingredient_id, 'חומר הגלם מתועד בהכנה זו ודורש אימות מוצר וכמות לפני ניסוי.', 'The ingredient is documented in this preparation and requires product and quantity verification before testing.', false, $preparation_spec['sources'], 'official_source' );
	}
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $preparation_spec['id'],
		'type' => 'preparation',
		'slug' => $preparation_spec['slug'],
		'parent_id' => 'dish-yabraq-yebra',
		'name' => $c99_text( $preparation_spec['name_he'], $preparation_spec['name_en'] ),
		'summary' => $c99_text( $preparation_spec['summary_he'], $preparation_spec['summary_en'] ),
		'region' => $preparation_spec['region'],
		'community' => $preparation_spec['community'],
		'primary_intent' => $c99_text( 'להבין במה הכנת עלי הגפן הזאת שונה מן הווריאנט המתועד האחר לפני ניסוי קולינרי.', 'Understand how this grape-leaf preparation differs from the other documented variant before a culinary trial.' ),
		'primary_keyword' => $c99_text( $preparation_spec['name_he'] . ' הבדלים', $preparation_spec['name_en'] . ' differences' ),
		'schema_type' => 'Article',
		'facts' => $facts,
		'relations' => $c99_syrian_relations,
		'cross_sell_ids' => $preparation_spec['ingredients'],
		'compliance' => array(
			$c99_compliance( 'stuffed-grape-leaf-cook-and-label-review', 'יש לאמת תוויות של עלי הגפן וחומרי הטעם, ולבשל בשר טחון ומילוי במלואם לפי תוכנית בטיחות לפני פרסום מתכון.', 'Verify labels for grape leaves and flavoring ingredients, and fully cook ground meat and filling under a safety plan before publishing a recipe.', array( 'foodsafety-safe-temperatures', 'israel-moh-food-hygiene' ), false ),
		),
		'prompt_en' => 'Private editorial food study of ' . $preparation_spec['visual'] . ', component boundaries clearly visible, natural side light and neutral tableware.',
	) );
}

$c99_syrian_market_specs = array(
	array(
		'id' => 'listing-sugat-freekeh-500g-big-dabach-20260806',
		'type' => 'retail_listing',
		'slug' => 'sugat-freekeh-500g-big-dabach-20260806',
		'parent' => 'ingredient-syrian-freekeh',
		'source_id' => 'big-dabach-sugat-freekeh-500g-listing-2026',
		'source_url' => 'https://www.bigdabach.co.il/?catalogProduct=6279611',
		'name_he' => 'פריקה סוגת 500 גרם בביג דבאח, תצפית 6.8.2026',
		'name_en' => 'Sugat Freekeh 500 g at Big Dabach, observed August 6 2026',
		'summary_he' => 'מצביע פרטי לדף מוצר מדויק שהופיע באינדקס החיפוש עם מחיר מבצע 10.90 ש״ח ומחיר מוצג קודם 14.90 ש״ח. שליפה ישירה נתקלה באתגר Cloudflare, ולכן אין צילום מצב שמור או הבטחת זמינות.',
		'summary_en' => 'A private pointer to an exact product page returned in the search index with a sale price of ILS 10.90 and a displayed list price of ILS 14.90. Direct automated retrieval met a Cloudflare challenge, so there is no retained snapshot or availability guarantee.',
		'fact_he' => 'דף מדויק לפריקה סוגת 500 גרם הוחזר באינדקס החיפוש עם מחיר מבצע 10.90 ש״ח, מחיר קודם 14.90 ש״ח ו-2.18 ש״ח ל-100 גרם. אתגר Cloudflare מנע צילום מצב ישיר, ולכן הרשומה אינה טענת מלאי.',
		'fact_en' => 'Exact Sugat freekeh 500 g page was returned in the current search index with a sale price of ILS 10.90, list price ILS 14.90 and ILS 2.18 per 100 g. Direct automated fetch met a Cloudflare challenge, so the record is a source-page pointer, not a retained snapshot or availability guarantee.',
		'value' => 10.90, 'unit' => 'one exact 500 g pack',
		'basis' => 'Indexed sale price for one exact 500 g pack; displayed list price ILS 14.90; direct fetch met a Cloudflare challenge.',
		'availability' => 'indexed_price_no_live_availability',
		'capture_method' => 'search_index_manual_review_cloudflare_challenge',
		'attributes' => array( 'net_content' => '500 g', 'brand_claim' => 'Sugat in indexed result', 'list_price' => 'ILS 14.90 displayed', 'normalized_price' => 'ILS 2.18 per 100 g displayed' ),
		'visual' => 'an unbranded plain 500 g freekeh pack beside weighed grains and an empty price-card area, no copied Sugat or Big Dabach branding',
	),
	array(
		'id' => 'listing-keter-harimon-pomegranate-concentrate-250ml-tamar-hst-20260806',
		'type' => 'retail_listing',
		'slug' => 'keter-harimon-pomegranate-concentrate-250ml-tamar-hst-20260806',
		'parent' => 'ingredient-pomegranate-concentrate',
		'comparison_target' => 'ingredient-syrian-pomegranate-molasses',
		'source_id' => 'tamar-hst-keter-harimon-pomegranate-concentrate-250ml-listing-2026',
		'source_url' => 'https://www.tamar-hst.co.il/product-details/209856/%D7%A8%D7%9B%D7%96_%D7%A8%D7%99%D7%9E%D7%95%D7%9F',
		'name_he' => 'רכז רימון כתר הרימון 250 מ״ל בתמר HST, תצפית 6.8.2026',
		'name_en' => 'Keter Harimon Pomegranate Concentrate 250 ml at Tamar HST, observed August 6 2026',
		'summary_he' => 'תצפית פרטית על דף מוצר חי שהציג רכז רימון 250 מ״ל במחיר 29.90 ש״ח ופעולת הוספה לסל. הכינוי רכז רימון אינו מוכיח שזהות המוצר זהה לדיבס רומאן או למולסת רימונים מסורתית.',
		'summary_en' => 'A private observation of a live page displaying pomegranate concentrate 250 ml at ILS 29.90 with add-to-cart visible. The pomegranate-concentrate label does not establish identity with traditional dibs rumman or pomegranate molasses.',
		'fact_he' => 'דף המוצר החי הציג רכז רימון כתר הרימון 250 מ״ל במחיר 29.90 ש״ח, 11.96 ש״ח ל-100 מ״ל ופעולת הוספה לסל. זו תצפית קמעונאית מתוארכת, לא מחיר מכירה של Complete99 או הבטחת מלאי.',
		'fact_en' => 'Live product page displayed Keter Harimon pomegranate concentrate 250 ml at ILS 29.90 per unit, ILS 11.96 per 100 ml, with add-to-cart visible. This is a dated retail observation, not a Complete99 sale price or stock guarantee.',
		'value' => 29.90, 'unit' => 'one exact 250 ml bottle',
		'basis' => 'One exact 250 ml retail listing with ILS 11.96 per 100 ml displayed; concentrate identity is not automatically traditional dibs rumman.',
		'availability' => 'add_to_cart_visible_at_observation',
		'capture_method' => 'live_retail_page_manual_review_no_snapshot',
		'attributes' => array( 'net_content' => '250 ml', 'product_name' => 'Keter Harimon pomegranate concentrate', 'normalized_price' => 'ILS 11.96 per 100 ml displayed', 'identity_boundary' => 'not automatically traditional dibs rumman' ),
		'visual' => 'an unbranded plain 250 ml bottle of pomegranate concentrate beside a measured dark-red liquid sample and empty price-card area, no traditional-origin claim',
	),
	array(
		'id' => 'listing-tamar-bakfar-pure-ground-sumac-100g-indexed-20260806',
		'type' => 'market_observation',
		'slug' => 'tamar-bakfar-pure-ground-sumac-100g-indexed-20260806',
		'parent' => 'ingredient-syrian-sumac',
		'source_id' => 'tamar-bakfar-pure-ground-sumac-100g-indexed-2026',
		'source_url' => 'https://tamarbakfar.co.il/product/%D7%A1%D7%95%D7%9E%D7%A7-%D7%98%D7%97%D7%95%D7%9F-%D7%98%D7%94%D7%95%D7%A8/',
		'name_he' => 'סומאק טחון טהור 100 גרם, תצפית אינדקס היסטורית 6.8.2026',
		'name_en' => 'Pure Ground Sumac 100 g, historical index observation August 6 2026',
		'summary_he' => 'תוצאת חיפוש היסטורית הציגה סומאק טחון טהור 100 גרם ב-11.00 ש״ח. ב-6 באוגוסט 2026 כתובת המוצר הפנתה לדף מכירת דומיין חונה, ולכן אין טענה למחיר נוכחי, זמינות נוכחית או הוספה לסל.',
		'summary_en' => 'A historical indexed result displayed pure ground sumac 100 g at ILS 11.00. On August 6 2026 the product URL redirected to a parked-domain sale page, so no current price, current availability or add-to-cart claim is made.',
		'fact_he' => 'תוצאת המוצר ההיסטורית באינדקס הציגה סומאק טחון טהור 100 גרם ב-11.00 ש״ח. בשל ההפניה לדומיין חונה בעת הבדיקה, הערך נשמר כמחיר היסטורי בלבד ללא טענת מוכר פעיל.',
		'fact_en' => 'The indexed historical product result displayed pure ground sumac 100 g at ILS 11.00. On retrieval the original URL redirected to a parked-domain sale page, so no current seller availability, current price or add-to-cart claim is made.',
		'value' => 11.00, 'unit' => 'one historical indexed 100 g pack',
		'basis' => 'Historical search-index price only; original product URL redirected to a parked-domain sale page on 2026-08-06.',
		'availability' => 'historical_index_only_domain_inactive',
		'comparability' => 'non_comparable',
		'capture_method' => 'historical_index_review_redirect_observed',
		'attributes' => array( 'net_content' => '100 g in historical index', 'price_state' => 'historical only', 'live_domain_state' => 'redirected to parked-domain sale page', 'current_availability' => 'not claimed' ),
		'visual' => 'an unbranded plain 100 g sumac pack beside coarse burgundy powder and an empty historical-price card area, no seller identity or availability cue',
	),
);

foreach ( $c99_syrian_market_specs as $market_spec ) {
	$observed_at = '2026-08-06T22:45:00+03:00';
	$measurement = array(
		'kind' => 'point',
		'low' => null,
		'high' => null,
		'value' => $market_spec['value'],
		'currency' => 'ILS',
		'unit' => $market_spec['unit'],
		'basis' => $market_spec['basis'],
		'tax_status' => 'unknown',
		'shipping_status' => 'unknown',
		'observed_at' => $observed_at,
		'source_url' => $market_spec['source_url'],
		'sample_size' => 1,
		'comparability' => isset( $market_spec['comparability'] ) ? $market_spec['comparability'] : 'like_for_like',
		'capture_method' => $market_spec['capture_method'],
		'snapshot_digest' => '',
		'line_items' => array(
			array(
				'name' => $market_spec['name_en'],
				'price' => $market_spec['value'],
				'currency' => 'ILS',
				'tax_status' => 'unknown',
				'availability' => $market_spec['availability'],
				'source_url' => $market_spec['source_url'],
				'attributes' => $market_spec['attributes'],
			),
		),
	);
	$fact_id = 'fact-' . $market_spec['slug'] . '-price-scope';
	$market_relations = array(
		$c99_relation( 'references', $market_spec['parent'], 'הרשומה מצביעה לישות ההקשר בלי להפוך אותה למוצר פעיל או להצעה.', 'The record points to its context entity without turning it into an active product or offer.', false, array( $market_spec['source_id'] ), 'market_observation' ),
	);
	if ( isset( $market_spec['comparison_target'] ) ) {
		$market_relations[] = $c99_relation(
			'references',
			$market_spec['comparison_target'],
			'רכז הרימונים מקושר למולסת רימונים לצורך השוואה בלבד. שם המוצר אינו מוכיח זהות או שקילות.',
			'The pomegranate concentrate links to pomegranate molasses for comparison only. The product name does not prove identity or equivalence.',
			false,
			array( $market_spec['source_id'] ),
			'market_observation'
		);
	}
	$c99_syrian_entities[] = $c99_syrian_entity( array(
		'id' => $market_spec['id'],
		'type' => $market_spec['type'],
		'slug' => $market_spec['slug'],
		'parent_id' => $market_spec['parent'],
		'name' => $c99_text( $market_spec['name_he'], $market_spec['name_en'] ),
		'summary' => $c99_text( $market_spec['summary_he'], $market_spec['summary_en'] ),
		'region' => 'israel-retail-observation',
		'community' => 'not-applicable',
		'primary_intent' => $c99_text( 'תיעוד עריכה פרטי של מחיר, זהות מוצר ומגבלות הראיה.', 'Private editorial documentation of price, product identity and evidence limitations.' ),
		'primary_keyword' => $c99_text( $market_spec['name_he'] . ' מחיר מתועד', $market_spec['name_en'] . ' documented price' ),
		'schema_type' => 'Dataset',
		'facts' => array(
			$c99_fact( $fact_id, 'economic', $market_spec['fact_he'], $market_spec['fact_en'], 'market_observation', 'market_snapshot', array( $market_spec['source_id'] ), false, $measurement, $observed_at ),
		),
		'relations' => $market_relations,
		'categories' => array( 'market-intelligence', 'syrian-research', 'dated-observations' ),
		'attributes' => array( 'pa_market' => array( 'israel-online-retail' ) ),
		'tags' => array( 'private-market-evidence', 'ils', 'observed-2026-08-06' ),
		'pricing_state' => 'source_price_observed',
		'market_scope' => 'market_specific',
		'prompt_en' => 'Private editorial evidence cutout of ' . $market_spec['visual'] . ', neutral background and no copied packaging or embedded text.',
	) );
}

$c99_syrian_observation_links = array(
	'ingredient-pomegranate-concentrate' => array(
		'observation_id' => 'listing-keter-harimon-pomegranate-concentrate-250ml-tamar-hst-20260806',
		'source_id' => 'tamar-hst-keter-harimon-pomegranate-concentrate-250ml-listing-2026',
	),
	'ingredient-syrian-freekeh' => array(
		'observation_id' => 'listing-sugat-freekeh-500g-big-dabach-20260806',
		'source_id' => 'big-dabach-sugat-freekeh-500g-listing-2026',
	),
	'ingredient-syrian-sumac' => array(
		'observation_id' => 'listing-tamar-bakfar-pure-ground-sumac-100g-indexed-20260806',
		'source_id' => 'tamar-bakfar-pure-ground-sumac-100g-indexed-2026',
	),
);
foreach ( $c99_syrian_entities as &$syrian_entity ) {
	if ( ! isset( $c99_syrian_observation_links[ $syrian_entity['id'] ] ) ) {
		continue;
	}
	$observation_link = $c99_syrian_observation_links[ $syrian_entity['id'] ];
	$syrian_entity['commerce']['business_model']['observation_entity_ids'][] = $observation_link['observation_id'];
	$syrian_entity['relations'][] = $c99_relation(
		'references',
		$observation_link['observation_id'],
		'ישות חומר הגלם מפנה לתצפית המחיר הפרטית כראיה מתוארכת בלבד, ללא הצעה או הבטחת זמינות.',
		'The ingredient points to the private price observation only as dated evidence, without an offer or availability guarantee.',
		false,
		array( $observation_link['source_id'] ),
		'market_observation'
	);
	$syrian_entity['relations'][ count( $syrian_entity['relations'] ) - 1 ]['valid_from'] = '2026-08-06';
}
unset( $syrian_entity );

$c99_syrian_private_entity_ids = array_column( $c99_syrian_entities, 'id' );

return array(
	'schema' => 'complete99-syrian-foundations-module/v1',
	'version' => 'culinary-science-2026.08.08.v20',
	'sources' => $c99_syrian_sources,
	'entities' => $c99_syrian_entities,
	'private_entity_ids' => $c99_syrian_private_entity_ids,
	'cluster_root_id' => 'cuisine-syrian-regional',
	'cluster_id' => 'cluster-syrian-regional-cuisine',
	'canonical_overrides' => array(
		'cuisine-syrian-regional' => $c99_text( '/museum/syrian-culinary-science/', '/en/museum/syrian-culinary-science/' ),
	),
	'counts' => array(
		'cuisine_hubs' => 1,
		'regional_hubs' => 9,
		'dishes' => 24,
		'ingredients' => 47,
		'techniques' => 12,
		'traditions' => 8,
		'preparations' => 6,
		'market_evidence' => 3,
		'total_entities' => 110,
	),
);
