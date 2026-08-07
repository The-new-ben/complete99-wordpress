<?php
/**
 * Complete99 Lebanese regional cuisine foundation.
 *
 * Every record in this tranche is private, noindex and reference-only. The
 * module separates Lebanese regional variants from shared Levantine dish
 * families, keeps community testimony within its named scope, and blocks
 * Lebanon-origin commerce until a lawful, documented route exists.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c99_lebanese_sources = array(
	'unesco-al-manouche-2023' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO Intangible Cultural Heritage',
		'title' => 'Al-Manouche, an emblematic culinary practice in Lebanon',
		'url' => 'https://ich.unesco.org/en/RL/al-man-ouche-an-emblematic-culinary-practice-in-lebanon-02000', 'published_at' => '2023-12-06', 'retrieved_at' => '2026-08-07',
	),
	'unesco-zahle-gastronomy' => array(
		'type' => 'official_organization', 'publisher' => 'UNESCO Creative Cities Network',
		'title' => 'Zahle, Creative City of Gastronomy',
		'url' => 'https://www.unesco.org/en/creative-cities/zahle', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-about' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'About the Food Heritage Foundation',
		'url' => 'https://food-heritage.org/about-us/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-breakfast-kings' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Breakfast of Kings',
		'url' => 'https://food-heritage.org/breakfast-of-kings/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-kibbeh-regions' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Lebanese Kebbeh in all its shapes and tastes',
		'url' => 'https://food-heritage.org/lebanese-kebbeh-in-all-its-shapes-and-tastes/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-kebbit-el-arous' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Kebbit El Arous, Brides Kebbeh',
		'url' => 'https://food-heritage.org/kebbit-el-arous-brides-kebbeh/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-pumpkin-kibbeh' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Baked Pumpkin Kebbeh',
		'url' => 'https://food-heritage.org/baked-pumpkin-kebbeh-kebbit-lakteen-bil-siniyeh/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-samkeh-harra' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Samke Harra, Spiced Fish',
		'url' => 'https://food-heritage.org/samke-harra-spiced-fish/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-sfeeha' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Sfeeha, Lebanese Meat Pies',
		'url' => 'https://food-heritage.org/sfeeha-or-lebanese-meat-pies/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-mujaddara-hamra' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Mujaddara Hamra',
		'url' => 'https://food-heritage.org/mujaddara-hamra/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-mujaddara-lent' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Mujaddara on Ash Monday',
		'url' => 'https://food-heritage.org/mujaddara-on-ash-monday/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-kishk-winter' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Kishk, the warmth of Lebanese winter',
		'url' => 'https://food-heritage.org/kishk-the-warmth-of-lebanese-winter/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-kishk-preparation' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Preparing Kishk for Winter',
		'url' => 'https://food-heritage.org/preparing-kishk-for-winter/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-mouneh' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'The Importance of Mouneh Provisioning in Lebanon',
		'url' => 'https://food-heritage.org/the-importance-of-mouneh-provisioning-in-lebanon/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-goat-dairy' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Goat milk tastes and flavors',
		'url' => 'https://food-heritage.org/goats-milk-tasteflavors/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-olive-oil' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Olives and olive oil in Lebanon',
		'url' => 'https://food-heritage.org/olives-and-olive-oil-history-and-health-benefits/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-beiruti-assida' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Beiruti Assida',
		'url' => 'https://food-heritage.org/beiruti-assida/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-meghle' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Meghle',
		'url' => 'https://food-heritage.org/meghle/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-mtashtash' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Mtashtash, Tabbouleh Kezzebeh',
		'url' => 'https://food-heritage.org/mtashtash-tabbouleh-kezzebeh/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-lemon-zenkoul' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Lemon Zenkoul',
		'url' => 'https://food-heritage.org/lemon-zenkoul/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-kaak-abbass' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Holiday cookies, Kaak el-Abbass',
		'url' => 'https://food-heritage.org/holiday-cookies-kaak-el-abbass/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'food-heritage-food-roots' => array(
		'type' => 'official_organization', 'publisher' => 'Food Heritage Foundation',
		'title' => 'Food and Roots retail brand',
		'url' => 'https://food-heritage.org/food-and-roots-a-new-lebanese-retail-brand-empowering-rural-communities-and-offering-unique-food-products/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'lebanon-olive-cultivars-2019' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Oil content, fatty acid and phenolic profiles of Lebanese olive varieties',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC6621921/', 'published_at' => '2019-01-01', 'retrieved_at' => '2026-08-07',
	),
	'lebanon-olive-cultivars-2023' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Lebanese olive oil cultivar and quality context',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC10386562/', 'published_at' => '2023-01-01', 'retrieved_at' => '2026-08-07',
	),
	'lebanese-kishk-rheology-2016' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Powder Technology',
		'title' => 'Physical and flow properties of Lebanese kishk powder',
		'url' => 'https://www.sciencedirect.com/science/article/abs/pii/S0032591016300390', 'published_at' => '2016-01-01', 'retrieved_at' => '2026-08-07',
	),
	'fermented-cheese-safety-2025' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Microbiological quality and safety of traditional fermented cheese',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC11735057/', 'published_at' => '2025-01-01', 'retrieved_at' => '2026-08-07',
	),
	'souk-el-tayeb-story' => array(
		'type' => 'official_business', 'publisher' => 'Souk El Tayeb',
		'title' => 'Souk El Tayeb story',
		'url' => 'https://www.soukeltayeb.com/story', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'souk-el-tayeb-organization' => array(
		'type' => 'official_business', 'publisher' => 'Souk El Tayeb',
		'title' => 'Souk El Tayeb organization',
		'url' => 'https://www.soukeltayeb.com/organization', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'souk-el-tayeb-quality' => array(
		'type' => 'official_business', 'publisher' => 'Souk El Tayeb',
		'title' => 'Souk El Tayeb quality assurance',
		'url' => 'https://www.soukeltayeb.com/qa', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'tawlet-official' => array(
		'type' => 'official_business', 'publisher' => 'Souk El Tayeb',
		'title' => 'Tawlet Mar Mikhael',
		'url' => 'https://www.soukeltayeb.com/tawlet/1/tawlet-mar-mikhael', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'tawlet-classes-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Souk El Tayeb',
		'title' => 'Tawlet cooking classes',
		'url' => 'https://www.soukeltayeb.com/tawlet/classes', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'dekenet-official' => array(
		'type' => 'official_business', 'publisher' => 'Souk El Tayeb',
		'title' => 'Dekenet',
		'url' => 'https://www.soukeltayeb.com/dekenet', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-lebanese-hamod-story' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'In this Lebanese family it is not Shabbat without hamod',
		'url' => 'https://www.jewishfoodsociety.org/stories/in-this-lebanese-family-its-not-shabbat-without-hamod', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-lebanese-hamod-recipe' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Hamod, Lebanese lemon and potato soup',
		'url' => 'https://www.jewishfoodsociety.org/recipes/hamod-lebanese-lemon-and-potato-soup', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-lebanese-bazela' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Bazela, peas with allspice',
		'url' => 'https://www.jewishfoodsociety.org/recipes/bazela-peas-with-allspice', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-lebanese-mahshi-zucchini' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Zucchini mahshi with beef and rice',
		'url' => 'https://www.jewishfoodsociety.org/recipes/zucchini-mahshi-stuffed-zucchini-with-beef-and-rice', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'jfs-lebanese-mahshi-onion' => array(
		'type' => 'official_organization', 'publisher' => 'Jewish Food Society',
		'title' => 'Onion mahshi with beef and rice',
		'url' => 'https://www.jewishfoodsociety.org/recipes/onion-mahshi-stuffed-onions-with-beef-and-rice', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'foodish-lebanese-karabij' => array(
		'type' => 'official_organization', 'publisher' => 'FOODISH at ANU Museum of the Jewish People',
		'title' => 'Karabij, pistachio maamoul with meringue',
		'url' => 'https://foodish.anumuseum.org.il/en/recipe/karabij-pistachio-maamoul-with-meringue/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'druze-wild-plants-2024' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'Peter Lang',
		'title' => 'Wild edible plants in Druze communities of Aley and Chouf',
		'url' => 'https://doi.org/10.48611/isbn.978-2-406-17650-3.p.0177', 'published_at' => '2024-01-01', 'retrieved_at' => '2026-08-07',
	),
	'aub-palestinian-oral-history' => array(
		'type' => 'official_organization', 'publisher' => 'American University of Beirut Libraries',
		'title' => 'Palestinian Oral History Archive',
		'url' => 'https://libraries.aub.edu.lb/poha/Content/about', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'healthy-kitchens-2019' => array(
		'type' => 'peer_reviewed_paper', 'publisher' => 'PubMed Central',
		'title' => 'Healthy Kitchens, Healthy Children in Palestinian refugee schools',
		'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC6883597/', 'published_at' => '2019-01-01', 'retrieved_at' => '2026-08-07',
	),
	'soufra-alfanar' => array(
		'type' => 'official_organization', 'publisher' => 'Alfanar',
		'title' => 'Soufra social enterprise',
		'url' => 'https://www.alfanar.org/portfolio/soufra', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'mayrig-official' => array(
		'type' => 'official_business', 'publisher' => 'Mayrig',
		'title' => 'Mayrig restaurant',
		'url' => 'https://www.mayrigrestaurant.com/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'em-sherif-official' => array(
		'type' => 'official_business', 'publisher' => 'Em Sherif',
		'title' => 'Em Sherif restaurants',
		'url' => 'https://emsherifrestaurant.com/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'hallab-1881-profile' => array(
		'type' => 'official_business', 'publisher' => 'Lebanon Industry',
		'title' => 'Hallab 1881 company profile',
		'url' => 'https://lebanon-industry.com/en/industry/details/1264/www.hallab.com.lb', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'phoenicia-culinary-institute' => array(
		'type' => 'official_organization', 'publisher' => 'Phoenicia Culinary Institute',
		'title' => 'About the Phoenicia Culinary Institute',
		'url' => 'https://www.phoenicia-culinary.com/about-the-institute', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'israel-enemy-states-trade-2026' => array(
		'type' => 'official_government', 'publisher' => 'Israel Ministry of Economy and Industry',
		'title' => 'Director-General Instruction 2.4, imports from countries without diplomatic relations or subject to import restrictions',
		'url' => 'https://www.gov.il/BlobFolder/policy/economy_dgi_instructions_02_04/he/instructions_2-04_080326_2-4-08-03-26.pdf', 'published_at' => '2026-03-08', 'retrieved_at' => '2026-08-07',
	),
	'israel-food-importer-registration' => array(
		'type' => 'official_government', 'publisher' => 'Israel Ministry of Health',
		'title' => 'Registration of a new non-animal food importer',
		'url' => 'https://www.gov.il/en/service/non-animal-derived-food-importer-registration', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'foodsafety-safe-temperatures-lebanon' => array(
		'type' => 'official_government', 'publisher' => 'FoodSafety.gov',
		'title' => 'Safe Minimum Internal Temperatures',
		'url' => 'https://www.foodsafety.gov/food-safety-charts/safe-minimum-internal-temperatures', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'cdc-raw-kibbeh-lebanon' => array(
		'type' => 'official_government', 'publisher' => 'United States Centers for Disease Control and Prevention',
		'title' => 'Salmonella Typhimurium infections linked to raw ground beef kibbeh',
		'url' => 'https://archive.cdc.gov/www_cdc_gov/salmonella/typhimurium-01-13/index.html', 'published_at' => '2013-01-01', 'retrieved_at' => '2026-08-07',
	),
	'spinneys-mymoune-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Spinneys Lebanon',
		'title' => 'Mymoune brand listings',
		'url' => 'https://www.spinneyslebanon.com/brands/mymoune', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'terroirs-eu-store-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Terroirs du Liban Europe',
		'title' => 'Terroirs du Liban European store',
		'url' => 'https://europe.terroirsduliban.com/', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'pereg-zaatar-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Pereg',
		'title' => 'Zaatar Baladi listing',
		'url' => 'https://www.tavlineypereg.co.il/ProductInfo.asp?ProdId=179', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
	'nitzat-pomegranate-2026' => array(
		'type' => 'official_market_listing', 'publisher' => 'Nitzat Haduvdevan',
		'title' => 'Organic pomegranate concentrate 280 g listing',
		'url' => 'https://www.nizat.com/%D7%A8%D7%9B%D7%96-%D7%A8%D7%99%D7%9E%D7%95%D7%A0%D7%99%D7%9D-%D7%90%D7%95%D7%A8%D7%92%D7%A0%D7%99-%D7%9C%D7%9C%D7%90-%D7%AA%D7%95%D7%A1%D7%A4%D7%AA-%D7%A1%D7%95%D7%9B%D7%A8-280-%D7%9E%D7%9C%60-%D7%92%D7%95%D7%A8%D7%92%D7%90%D7%A1-%D7%A0%D7%98%D7%95%D7%A8%D7%9C-i25172', 'published_at' => '', 'retrieved_at' => '2026-08-07',
	),
);

$c99_lebanese_fact = static function ( $id, $dimension, $he, $en, $evidence_class, $value_scope, $source_ids, $observed_at = '', $measurement = array() ) use ( $c99_fact ) {
	return $c99_fact( $id, $dimension, $he, $en, $evidence_class, $value_scope, $source_ids, false, $measurement, $observed_at );
};

$c99_lebanese_profiles = static function ( $facts ) use ( $c99_profile, $c99_profiles ) {
	$by_dimension = array();
	foreach ( $facts as $fact ) {
		$by_dimension[ $fact['dimension'] ][] = $fact['id'];
	}
	$labels = array(
		'scientific' => array( 'הטענה המדעית תחומה למקור ולהיקף הרשומים.', 'The scientific claim is bounded to its listed source and scope.' ),
		'cultural' => array( 'ההקשר התרבותי נשמר לפי האזור, הקהילה או העדות המזוהים.', 'The cultural context is retained according to the named region, community or testimony.' ),
		'institutional' => array( 'ההקשר המוסדי נשמר לפי מקור רשמי מזוהה.', 'The institutional context is retained according to an identified official source.' ),
		'economic' => array( 'מחירים נשמרים כתצפיות מתוארכות בלבד, ללא הצעה או זמינות.', 'Prices are retained only as dated observations, without an offer or availability claim.' ),
		'structural' => array( 'ההבחנה האונטולוגית היא מבנה עריכה תחום שאינו פסק מקור בלעדי.', 'The ontological distinction is a bounded editorial structure, not an exclusive-origin verdict.' ),
	);
	$overrides = array();
	foreach ( $labels as $dimension => $label ) {
		if ( isset( $by_dimension[ $dimension ] ) ) {
			$overrides[ $dimension ] = $c99_profile( 'source_backed', $label[0], $label[1], $by_dimension[ $dimension ] );
		} elseif ( 'economic' === $dimension ) {
			$overrides[ $dimension ] = $c99_profile( 'pending_evidence', 'אין ברשומה הצעת מחיר פעילה.', 'This record contains no active price offer.' );
		}
	}
	return $c99_profiles( $overrides );
};

$c99_lebanese_entity = static function ( $config ) use ( $c99_entity, $c99_lebanese_profiles, $c99_text ) {
	$type = $config['type'];
	$region = isset( $config['region'] ) ? $config['region'] : 'lebanon-national';
	$community = isset( $config['community'] ) ? $config['community'] : 'lebanese-multi-community';
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
		'next_review_at' => '2027-02-07',
		'culinary_test_status' => in_array( $type, array( 'dish', 'preparation' ), true ) ? 'pending' : 'not_applicable',
		'schema_type' => isset( $config['schema_type'] ) ? $config['schema_type'] : 'Article',
		'surface_group' => 'lebanese-culinary-science',
		'seo_group' => 'lebanese-culinary-science',
		'primary_intent' => $config['primary_intent'],
		'primary_keyword' => $config['primary_keyword'],
		'secondary_keywords' => isset( $config['secondary_keywords'] ) ? $config['secondary_keywords'] : array( 'he' => array(), 'en' => array() ),
		'intent_classes' => isset( $config['intent_classes'] ) ? $config['intent_classes'] : array( 'informational' ),
		'facts' => $config['facts'],
		'profiles' => $c99_lebanese_profiles( $config['facts'] ),
		'categories' => isset( $config['categories'] ) ? $config['categories'] : array( 'culinary-museum', 'lebanese-culinary-science', $type . 's' ),
		'attributes' => $attributes,
		'tags' => isset( $config['tags'] ) ? $config['tags'] : array( 'lebanese-cuisine', $region, $community ),
		'public_category_path' => array(),
		'public_attribute_keys' => array(),
		'public_tags' => array(),
		'relations' => isset( $config['relations'] ) ? $config['relations'] : array(),
		'commerce_state' => 'reference_only',
		'public_offer_allowed' => false,
		'cross_sell_ids' => array(),
		'up_sell_ids' => array(),
		'revenue_models' => array( 'content_to_commerce' ),
		'customer_segments' => array( 'culinary_consumers', 'professional_chefs', 'culinary_students', 'research_readers' ),
		'pricing_state' => isset( $config['pricing_state'] ) ? $config['pricing_state'] : 'research_required',
		'market_scope' => isset( $config['market_scope'] ) ? $config['market_scope'] : 'global_research',
		'observation_entity_ids' => array(),
		'prompt_en' => $config['prompt_en'],
		'negative_prompt_en' => 'No text, no logos, no flags, no national symbols, no costumes, no copied packaging, no historical props, no unsourced garnish, no health claim, no watermark, no distorted hands, no raw-meat serving suggestion.',
		'asset_state' => 'rights_review_required',
		'rights_method' => 'generated_concept_with_human_review',
		'rights_state' => 'pending',
		'compliance' => isset( $config['compliance'] ) ? $config['compliance'] : array(),
		'attribution_state' => 'pending_named_review',
		'protected_exclusions' => array(
			'he' => array( 'טענת מקור בלעדית', 'הצעת ספק מלבנון', 'הבטחה רפואית', 'הוראות בטיחות לא מאומתות' ),
			'en' => array( 'exclusive origin claim', 'Lebanon-origin supplier offer', 'medical promise', 'unverified safety instructions' ),
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

$c99_lebanese_build = static function ( $spec ) use ( $c99_lebanese_entity, $c99_lebanese_fact, $c99_relation, $c99_compliance, $c99_text ) {
	$sources = $spec['sources'];
	$facts = array(
		$c99_lebanese_fact(
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
		$facts[] = $c99_lebanese_fact(
			'fact-' . $spec['slug'] . '-science-boundary',
			'scientific',
			isset( $spec['science_he'] ) ? $spec['science_he'] : 'המקור מזהה מנה, רכיב או שיטת הכנה, אך אינו מספק מדידת pH, בריקס, פעילות מים, עקומת טמפרטורה או חיי מדף מאומתים לרשומה זו.',
			isset( $spec['science_en'] ) ? $spec['science_en'] : 'The source identifies a dish, component or method but supplies no measured pH, Brix, water activity, temperature curve or validated shelf life for this record.',
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
		$relations[] = $c99_relation( 'requires', $target_id, 'הקשר הרכיב מתועד, אך זהות מוצר, כמות וטיפול דורשים אימות לפני מתכון או הצעה.', 'The component relationship is documented, while product identity, quantity and handling require verification before a recipe or offer.', false, $sources, 'official_source' );
	}
	foreach ( isset( $spec['references'] ) ? $spec['references'] : array() as $target_id ) {
		$relations[] = $c99_relation( 'references', $target_id, 'הקישור שומר חפיפה או השוואה בלי למזג זהויות ובלי לקבוע מקור בלעדי.', 'The link preserves an overlap or comparison without merging identities or declaring exclusive origin.', false, $sources, 'official_source' );
	}
	foreach ( isset( $spec['extra_relations'] ) ? $spec['extra_relations'] : array() as $extra_relation ) {
		$relations[] = $c99_relation( $extra_relation[0], $extra_relation[1], $extra_relation[2], $extra_relation[3], false, isset( $extra_relation[4] ) ? $extra_relation[4] : $sources, isset( $extra_relation[5] ) ? $extra_relation[5] : 'official_source' );
	}
	$compliance = array();
	foreach ( isset( $spec['compliance'] ) ? $spec['compliance'] : array() as $note ) {
		$compliance[] = $c99_compliance( $note[0], $note[1], $note[2], isset( $note[3] ) ? $note[3] : $sources, false );
	}
	$schema_type = isset( $spec['schema_type'] ) ? $spec['schema_type'] : 'Article';
	return $c99_lebanese_entity( array(
		'id' => $spec['id'], 'type' => $spec['type'], 'slug' => $spec['slug'], 'parent_id' => isset( $spec['parent_id'] ) ? $spec['parent_id'] : '',
		'name' => $c99_text( $spec['name_he'], $spec['name_en'] ),
		'summary' => $c99_text( $spec['summary_he'], $spec['summary_en'] ),
		'region' => isset( $spec['region'] ) ? $spec['region'] : 'lebanon-national',
		'community' => isset( $spec['community'] ) ? $spec['community'] : 'lebanese-multi-community',
		'primary_intent' => $c99_text( isset( $spec['intent_he'] ) ? $spec['intent_he'] : 'להבין את הזהות, ההקשר והגבולות של ' . $spec['name_he'] . '.', isset( $spec['intent_en'] ) ? $spec['intent_en'] : 'Understand the identity, context and boundaries of ' . $spec['name_en'] . '.' ),
		'primary_keyword' => $c99_text( isset( $spec['keyword_he'] ) ? $spec['keyword_he'] : $spec['name_he'], isset( $spec['keyword_en'] ) ? $spec['keyword_en'] : $spec['name_en'] ),
		'secondary_keywords' => isset( $spec['secondary_keywords'] ) ? $spec['secondary_keywords'] : array( 'he' => array(), 'en' => array() ),
		'schema_type' => $schema_type,
		'facts' => $facts,
		'relations' => $relations,
		'compliance' => $compliance,
		'attributes' => isset( $spec['attributes'] ) ? $spec['attributes'] : array(),
		'tags' => isset( $spec['tags'] ) ? $spec['tags'] : array( 'lebanese-cuisine', isset( $spec['region'] ) ? $spec['region'] : 'lebanon-national' ),
		'pricing_state' => isset( $spec['pricing_state'] ) ? $spec['pricing_state'] : 'research_required',
		'market_scope' => isset( $spec['market_scope'] ) ? $spec['market_scope'] : 'global_research',
		'prompt_en' => $spec['prompt_en'],
	) );
};

$c99_lebanese_rows = array();

/* Root and regional or thematic hubs. */
$c99_lebanese_rows[] = array(
	'id' => 'cuisine-lebanese-regional', 'type' => 'cuisine', 'slug' => 'lebanese-culinary-science', 'parent_id' => 'museum-culinary-science',
	'name_he' => 'המטבח הלבנוני לפי אזורים וקהילות', 'name_en' => 'Lebanese Cuisine by Region and Community',
	'region' => 'lebanon-national', 'sources' => array( 'unesco-al-manouche-2023', 'food-heritage-kibbeh-regions', 'food-heritage-mouneh' ),
	'summary_he' => 'מפת מחקר דו־לשונית של מנות, מזווה, אזורים, קהילות, מוסדות ושווקים בלבנון. היא מפרידה בין וריאנטים אזוריים לבין משפחות אוכל משותפות ללבנט, ואינה הופכת נוכחות מקומית לפסק מקור בלעדי.',
	'summary_en' => 'A bilingual research map of dishes, pantry systems, regions, communities, institutions and markets in Lebanon. It separates regional variants from shared Levantine food families and never turns local presence into an exclusive-origin verdict.',
	'fact_he' => 'המקורות מתעדים שונות אזורית במנאקיש, קובה ומונה, לצד מנהגים חוצי קהילות. לכן המטבח ממופה כרשת ולא כנוסחה לאומית אחידה.',
	'fact_en' => 'The sources document regional variation in manouche, kibbeh and mouneh alongside practices shared across communities. The cuisine is therefore mapped as a network rather than one uniform national formula.',
	'dimension' => 'structural', 'schema_type' => 'CollectionPage',
	'prompt_en' => 'Editorial overhead atlas of Lebanese foodways arranged in five separated unlabeled zones for Beirut, Mount Lebanon and Chouf, North and Tripoli, Bekaa and Baalbek-Hermel, and South Lebanon, fully cooked dishes, grains, herbs, dairy and preserves on a neutral limestone table, soft documentary daylight.'
);

$c99_lebanese_hubs = array(
	array( 'region-lebanon-beirut', 'beirut-foodways', 'ביירות: עיר, שווקים ושולחנות', 'Beirut: City, Markets and Tables', 'lebanon-beirut', array( 'souk-el-tayeb-story', 'food-heritage-beiruti-assida' ), 'המרחב הביירותי מחבר מסורות ביתיות, שווקים, מאפיות ואירוח עכשווי. הוא נשמר כהקשר עירוני ולא כבעלים של כל מנה המוגשת בו.', 'The Beirut layer connects home traditions, markets, bakeries and contemporary hospitality. It remains an urban context rather than the owner of every dish served there.', 'המקורות תומכים במוסדות ובמנות מזוהות בביירות, אך אינם מצדיקים שיוך של כלל המטבח הלבנוני לעיר.', 'The sources support named Beirut institutions and dishes but do not justify assigning all Lebanese cuisine to the city.', 'Beirut culinary context table with separate bakery, market produce and home dessert zones, documentary daylight, no skyline or tourism symbols.' ),
	array( 'region-lebanon-mount-lebanon-shouf', 'mount-lebanon-chouf-foodways', 'הר הלבנון, שוף ואליי', 'Mount Lebanon, Chouf and Aley', 'lebanon-mount-chouf-aley', array( 'food-heritage-breakfast-kings', 'druze-wild-plants-2024' ), 'ציר אזורי של מאפים, צמחי בר, מטעים ומזווה כפרי, עם הבחנה בין עדות מחקרית לבין מתכון או הנחיית ליקוט.', 'A regional axis of baked foods, wild plants, orchards and rural pantry practices, separating research evidence from recipes or foraging advice.', 'המקורות מתעדים וריאנטים של מנאקיש וידע צמחי בר בקהילות מזוהות, בלי להפוך אותם לכלל אזורי אחיד.', 'The sources document manouche variants and wild-plant knowledge in named communities without turning them into one uniform regional rule.', 'Mount Lebanon foodways study with flatbread, orchard fruit, separate labeled-by-shape herb specimens and pantry jars, no foraging invitation.' ),
	array( 'region-lebanon-north-akkar-tripoli', 'north-akkar-tripoli-foodways', 'צפון לבנון, עכאר וטריפולי', 'North Lebanon, Akkar and Tripoli', 'lebanon-north-akkar-tripoli', array( 'food-heritage-breakfast-kings', 'food-heritage-samkeh-harra', 'food-heritage-kibbeh-regions' ), 'מפה נפרדת של טריפולי החופית, עכאר הכפרית וצפון ההר, הכוללת דגים חריפים, גבינות מקומיות וצורות קובה.', 'A separate map of coastal Tripoli, rural Akkar and the northern highlands, including spiced fish, local dairy and kibbeh forms.', 'שלושת המרחבים נשמרים באותו Hub לצורכי ניווט בלבד, ולא כטענה שהם מטבח אזורי יחיד.', 'The three spaces share a navigation hub only and are not asserted to form one uniform regional cuisine.', 'Northern Lebanon culinary map with Tripoli spiced fish, Akkar dairy flatbread and Zgharta kibbeh in separate plates, neutral studio atlas.' ),
	array( 'region-lebanon-bekaa-baalbek-hermel', 'bekaa-baalbek-hermel-foodways', 'בקעת הלבנון, זחלה, בעלבכ והרמל', 'Bekaa, Zahle, Baalbek and Hermel', 'lebanon-bekaa-baalbek-hermel', array( 'unesco-zahle-gastronomy', 'food-heritage-kibbeh-regions', 'food-heritage-sfeeha' ), 'ציר עמק והר המבדיל בין זחלה, בעלבכ, מערב הבקעה והרמל, עם סְפִיחָה, קובה אזורית, קישק, ענבים ותזקיקים בהקשרם.', 'A valley and mountain axis distinguishing Zahle, Baalbek, West Bekaa and Hermel, with sfiha, regional kibbeh, kishk, grapes and distillates in context.', 'המקורות מתעדים זהויות מקומיות שונות בתוך הבקעה, ולכן אין לאחד אותן לגרסה אחת של מנה או חומר גלם.', 'The sources document distinct local identities within the Bekaa, so they are not collapsed into one dish or ingredient formula.', 'Bekaa atlas with Baalbek sfiha, Hermel sumac kibbeh, West Bekaa pumpkin kibbeh, kishk and grapes in separate zones, no alcohol branding.' ),
	array( 'region-lebanon-south-jabal-amel', 'south-lebanon-jabal-amel-foodways', 'דרום לבנון וג׳בל עאמל', 'South Lebanon and Jabal Amel', 'lebanon-south-jabal-amel', array( 'food-heritage-kibbeh-regions', 'food-heritage-mujaddara-hamra', 'food-heritage-kaak-abbass' ), 'מרחב של קממונה ירוקה, דגנים, שמן זית, מנות עדשים ומזונות טקסיים, עם שיוך נקודתי ליישובים ולאירועים.', 'A region of green kammouneh, grains, olive oil, lentil dishes and ritual foods, with precise attribution to localities and occasions.', 'תיעוד דרום לבנון כולל וריאנטים מקומיים וקהילתיים, אך אינו מוכיח בלעדיות אזורית לכל מרכיב או מנה.', 'South Lebanon documentation includes local and community variants but does not prove regional exclusivity for every ingredient or dish.', 'South Lebanon culinary table with green kammouneh, red lentil dish, olive oil and ritual cookies separated, fully cooked, no religious symbols.' ),
	array( 'hub-lebanese-manouche-practice', 'lebanese-manouche-practice', 'מנאקיש, מאפייה וארוחת בוקר', 'Manouche, Bakery and Breakfast Practice', 'lebanon-national', array( 'unesco-al-manouche-2023', 'food-heritage-breakfast-kings' ), 'Hub למנה, לטכניקת הטבעת האצבעות, לתוספות אזוריות ולמפגש הבוקר סובחייה.', 'A hub for the dish, fingertip-indenting technique, regional toppings and the morning social practice known as sobhiyyeh.', 'אונסקו מתארת את אל־מנאקיש כמנהג קולינרי לבנוני חוצה קהילות, עם הכנה בבית ובמאפיות ייעודיות.', 'UNESCO describes al-manouche as a Lebanese culinary practice shared across communities and prepared at home and in specialized bakeries.', 'Lebanese manouche practice board with dimpled dough, zaatar blend, bakery stone and morning tea separated, no embedded text.' ),
	array( 'hub-lebanese-kibbeh-family', 'lebanese-kibbeh-regional-family', 'משפחת הקובה הלבנונית', 'Lebanese Regional Kibbeh Family', 'lebanon-national', array( 'food-heritage-kibbeh-regions' ), 'Hub המשווה צורות קובה מתועדות בצפון, בבקעה, בדרום ובחוף בלי למחוק את שייכות הקובה למרחב המזרח הקרוב הרחב.', 'A hub comparing documented kibbeh forms in the north, Bekaa, south and coast without erasing kibbeh as a wider Near Eastern family.', 'המקור עצמו מציג קובה כמנה עתיקה ומייצגת ברחבי המזרח הקרוב, ואז מתאר התאמות אזוריות בתוך לבנון.', 'The source itself presents kibbeh as an old representative dish across the Near East and then documents regional adaptations within Lebanon.', 'Comparative top-down study of seven fully cooked Lebanese regional kibbeh forms, each separated on neutral ceramic plates at consistent scale.' ),
	array( 'hub-lebanese-mouneh-system', 'lebanese-mouneh-system', 'מערכת המונה והמזווה הלבנוני', 'Lebanese Mouneh and Pantry System', 'lebanon-national', array( 'food-heritage-mouneh', 'food-heritage-kishk-preparation', 'food-heritage-goat-dairy' ), 'Hub לעונתיות, שימור, קישק, מוצרי חלב, שמנים ומולסות, עם שערי בטיחות לפני כל טענת חיי מדף או מוצר.', 'A hub for seasonality, preservation, kishk, dairy, oils and molasses, with safety gates before any shelf-life or product claim.', 'המקורות מציגים מונה כמערכת ביתית וכפרית מגוונת, ולא כמפרט ייצור אחיד או כהוכחת בטיחות מסחרית.', 'The sources present mouneh as a diverse household and rural system, not a uniform production specification or proof of commercial safety.', 'Seasonal Lebanese mouneh pantry with separate unbranded vessels of kishk, dairy, pomegranate molasses, dried herbs and oil, clean documentary lighting.' ),
	array( 'hub-lebanese-community-foodways', 'lebanese-community-foodways', 'קהילות, משפחות ומועדים בלבנון', 'Communities, Families and Occasions in Lebanon', 'lebanon-national', array( 'jfs-lebanese-hamod-story', 'foodish-lebanese-karabij', 'druze-wild-plants-2024', 'aub-palestinian-oral-history' ), 'Hub למסורות משפחתיות, יהודיות, דרוזיות, ארמניות, פלסטיניות, נוצריות ומוסלמיות, שכל אחת נשמרת בתוך גבול המקור שלה.', 'A hub for family, Jewish, Druze, Armenian, Palestinian, Christian and Muslim contexts, each retained within the boundary of its source.', 'דפי הקהילה אינם קטלוג של מטבח סגור ואינם מייחסים מנות משותפות לקהילה אחת ללא עדות מפורשת.', 'Community pages are not closed-cuisine catalogs and do not assign shared dishes to one community without explicit evidence.', 'Respectful community foodways atlas made only of dishes, utensils and archival recipe cards with blank text zones, no people, costumes or religious symbols.' ),
	array( 'hub-lebanese-institutions-markets', 'lebanese-culinary-institutions-markets', 'מוסדות, שווקים ומסעדות בלבנון', 'Lebanese Culinary Institutions, Markets and Restaurants', 'lebanon-national', array( 'food-heritage-about', 'souk-el-tayeb-organization', 'phoenicia-culinary-institute' ), 'מדריך לישויות מוסדיות עצמאיות: מחקר, הכשרה, שווקים, מסעדות ומיזמים חברתיים. הוא אינו ספר ספקים פעיל.', 'A guide to independent institutional entities for research, training, markets, restaurants and social enterprises. It is not an active supplier directory.', 'כל מוסד נשמר עם מקור רשמי ותפקיד נוכחי שניתן לאימות, בלי להסיק זמינות מסחרית או התקשרות עם Complete99.', 'Each institution is retained with an official source and verifiable current role, without implying availability or a Complete99 relationship.', 'Editorial institutional map with market stall, culinary classroom, restaurant table and archive box as separate neutral icons, no logos or signage.' ),
	array( 'hub-lebanese-plant-seafood-table', 'lebanese-plant-seafood-table', 'צמחי בר, שמן זית ודגי החוף', 'Wild Plants, Olive Oil and Coastal Fish', 'lebanon-coast-mountain', array( 'druze-wild-plants-2024', 'lebanon-olive-cultivars-2019', 'food-heritage-samkeh-harra' ), 'Hub מדעי להבחנה בין זיהוי בוטני, שונות זנית של שמן זית, דגים טריים וטכניקות בישול, בלי הבטחות רפואיות או עצות ליקוט.', 'A scientific hub separating botanical identification, olive-oil cultivar variation, fresh fish and cooking techniques without medical promises or foraging advice.', 'המקורות תומכים בשונות ובידע מקומי, אך מוצר, זן, דג או צמח בפועל דורשים זיהוי ובדיקה משלהם.', 'The sources support variation and local knowledge, while each actual product, cultivar, fish or plant requires its own identification and testing.', 'Scientific culinary still life with separated wild-green specimens, olive cultivar samples and fully cooked coastal fish, neutral labels left blank.' ),
	array( 'hub-armenian-lebanese-bourj-hammoud', 'armenian-lebanese-bourj-hammoud', 'המטבח הארמני־לבנוני בבורג׳ חמוד', 'Armenian-Lebanese Foodways in Bourj Hammoud', 'lebanon-bourj-hammoud', array( 'mayrig-official' ), 'מסגרת דיאספורית המגינה על הזהות הארמנית של מנות וממקמת את ההתפתחות הלבנונית שלהן בלי לבלוע אותן לתוך מקור לבנוני כללי.', 'A diaspora frame that protects the Armenian identity of dishes while locating their Lebanese development without absorbing them into generic Lebanese origin.', 'המקור העסקי מתאר זיכרון ארמני ואינטראקציה ים־תיכונית, אך נדרש מחקר מוסדי נוסף לפני מיפוי מנות פרטניות.', 'The business source describes Armenian memory and Mediterranean interaction, while further institutional research is required before mapping individual dishes.', 'Armenian-Lebanese pantry study in Bourj Hammoud context with separate breads, spice pastes and pastries, no storefront logo or costume.' ),
	array( 'hub-palestinian-foodways-lebanon', 'palestinian-foodways-in-lebanon', 'מסורות אוכל פלסטיניות בלבנון', 'Palestinian Foodways in Lebanon', 'lebanon-palestinian-diaspora', array( 'aub-palestinian-oral-history', 'healthy-kitchens-2019', 'soufra-alfanar' ), 'מסגרת נפרדת לעקירה, זיכרון, מטבחי נשים ומוסדות קהילתיים פלסטיניים בלבנון, ללא שיוך המנות למקור לבנוני וללא הפיכת קהילה פגיעה לליד מסחרי.', 'A separate frame for displacement, memory, women-led kitchens and Palestinian community institutions in Lebanon, without assigning dishes Lebanese origin or turning a vulnerable community into a commercial lead.', 'המקורות תומכים בארכיון עדויות ובמטבחים קהילתיים, אך אינם מהווים הסכמה לגיוס ספקים או לפרסום מידע אישי.', 'The sources support an oral-history archive and community kitchens but do not constitute consent for supplier recruitment or publication of personal data.', 'Documentary archive and community-kitchen still life with recipe notebooks, fully cooked shared dishes and empty place cards, no people or camp identifiers.' ),
);
foreach ( $c99_lebanese_hubs as $hub ) {
	$c99_lebanese_rows[] = array(
		'id' => $hub[0], 'type' => 'topic_hub', 'slug' => $hub[1], 'parent_id' => 'cuisine-lebanese-regional',
		'name_he' => $hub[2], 'name_en' => $hub[3], 'region' => $hub[4], 'sources' => $hub[5],
		'summary_he' => $hub[6], 'summary_en' => $hub[7], 'fact_he' => $hub[8], 'fact_en' => $hub[9],
		'dimension' => 'structural', 'schema_type' => 'CollectionPage', 'prompt_en' => $hub[10],
	);
}

/* Dishes and preparations. */
$c99_lebanese_food_rows = array(
	array( 'dish-al-manouche-lebanon', 'dish', 'al-manouche-lebanon', 'hub-lebanese-manouche-practice', 'אל־מנאקיש בלבנון', 'Al-Manouche in Lebanon', 'lebanon-national', array( 'unesco-al-manouche-2023' ), 'לחם שטוח לארוחת בוקר, מחורץ בקצות האצבעות ונאפה עם תערובת זעתר ושמן זית, לצד תוספות אפשריות המשתנות בין בתים ומאפיות.', 'A breakfast flatbread indented with fingertips and baked with zaatar and olive oil, with optional additions varying among homes and bakeries.', 'אונסקו מתעדת הכנה בבית ובמאפיות ייעודיות, את פעולת החריצה ואת מעמד המנה כמנהג חוצה קהילות.', 'UNESCO documents home and specialized-bakery preparation, fingertip indenting and the practice across communities.', array( 'ingredient-lebanese-zaatar-blend', 'ingredient-lebanese-olive-oil-context' ), array(), 'Freshly baked al-manouche with fingertip dimples and zaatar topping, optional labneh, tomato, cucumber, olive and mint held in separate dishes, bakery-stone surface.' ),
	array( 'dish-tabbouleh-lebanon', 'dish', 'tabbouleh-lebanon-context', 'cuisine-lebanese-regional', 'טאבולה בהקשר הלבנוני', 'Tabbouleh in Lebanese Context', 'lebanon-national', array( 'food-heritage-mtashtash' ), 'רשומת הקשר למנת עשבים ובורגול מוכרת ברחבי הלבנט. היא אינה קובעת מקור בלעדי ואינה מחליפה וריאנטים אזוריים מתועדים.', 'A context record for an herb-and-bulgur dish known across the Levant. It makes no exclusive-origin claim and does not replace documented regional variants.', 'המקור משמש להשוואה בין טאבולה לבין גרסת מטשטש מעכאר, לא לפסק בעלות לאומית.', 'The source supports comparison between tabbouleh and an Akkar mtashtash variant, not a national ownership verdict.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Lebanese-context tabbouleh study with parsley, tomato and fine bulgur visibly separated at one edge, neutral serving bowl, no origin symbols.' ),
	array( 'dish-fattoush-lebanon', 'dish', 'fattoush-lebanon-context', 'cuisine-lebanese-regional', 'פתוש בהקשר הלבנוני', 'Fattoush in Lebanese Context', 'lebanon-national', array( 'food-heritage-breakfast-kings' ), 'רשומת הקשר לסלט לחם ועשבים ממשפחה לבנטינית רחבה. נוסחת ירקות, רוטב ולחם תידרש למקור מתכון ייעודי.', 'A context record for a bread-and-herb salad within a wider Levantine family. A precise vegetable, dressing and bread formula requires a dedicated recipe source.', 'המקור הנוכחי אינו מספק BOM מלא או הכרעת מקור, ולכן הדף נשאר ישות הקשר פרטית.', 'The current source supplies neither a complete BOM nor an origin verdict, so the page remains a private context entity.', array(), array(), 'Fully assembled fattoush context plate with toasted bread and mixed vegetables, ingredients recognizable but no invented formula or national styling.' ),
	array( 'dish-mujaddara-lebanon-family', 'dish', 'mujaddara-lebanon-family', 'cuisine-lebanese-regional', 'משפחת המג׳דרה בלבנון', 'Mujaddara Family in Lebanon', 'lebanon-national', array( 'food-heritage-mujaddara-lent', 'food-heritage-mujaddara-hamra' ), 'בעלים קנוני למשפחת עדשים ודגן הכוללת מג׳דרה, מודרדרה ומג׳דרה חמרה, בלי למזג את ההכנות או לייחס את המשפחה ללבנון בלבד.', 'A canonical owner for a lentil-and-grain family including mujaddara, mudardara and mujaddara hamra, without merging preparations or assigning the family exclusively to Lebanon.', 'המקורות מתעדים הבדלי מרקם, דגן, צבע והקשר עונתי, ולכן כל הכנה נשמרת בנפרד.', 'The sources document differences in texture, grain, color and seasonal context, so each preparation remains separate.', array( 'ingredient-lebanese-bulgur-context' ), array( 'preparation-mujadara-thursday-syrian-jewish' ), 'Comparative culinary study of three Lebanese-context lentil-and-grain preparations in separate bowls, consistent scale, fully cooked.' ),
	array( 'preparation-mujaddara-hamra-rmeish', 'preparation', 'mujaddara-hamra-rmeish', 'dish-mujaddara-lebanon-family', 'מג׳דרה חמרה מרמיש', 'Mujaddara Hamra from Rmeish', 'lebanon-south-rmeish', array( 'food-heritage-mujaddara-hamra' ), 'הכנה דרום־לבנונית שבה הבצל מקבל השחמה עמוקה וצובע את מנת העדשים והבורגול בגוון אדמדם־חום.', 'A South Lebanese preparation in which deeply browned onion colors the lentil-and-bulgur dish reddish brown.', 'המקור מתעד את רמיש ואת מבנה המנה, אך דרגת ההשחמה והיחסים דורשים מבחן מטבח לפני פרסום מתכון.', 'The source documents Rmeish and the dish structure, while browning degree and ratios require kitchen testing before a recipe is published.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Rmeish mujaddara hamra with clearly caramelized onion and reddish-brown lentil-bulgur texture, fully cooked, top-down studio shot.' ),
	array( 'preparation-mudardara-rice-lebanon', 'preparation', 'mudardara-rice-lebanon', 'dish-mujaddara-lebanon-family', 'מודרדרה עם אורז בלבנון', 'Lebanese Rice Mudardara', 'lebanon-national', array( 'food-heritage-mujaddara-lent' ), 'הכנת עדשים ואורז שבה הגרגרים נשמרים מובחנים ומוגשים עם בצל מטוגן, בנפרד ממחית מג׳דרה ומגרסת הבורגול האדומה.', 'A lentil-and-rice preparation with distinct grains and fried onion, kept separate from mashed mujaddara and the red bulgur variant.', 'המקור תומך בהבחנת המרקם והאורז, אך אינו מספק פרוטוקול טמפרטורה או בטיחות לשמן הטיגון.', 'The source supports the rice and texture distinction but supplies no frying-oil temperature or safety protocol.', array(), array(), 'Lebanese rice mudardara with distinct lentils and rice grains, crisp fried onions piled separately, clean neutral bowl.' ),
	array( 'dish-kibbeh-zghartawiyeh', 'dish', 'kibbeh-zghartawiyeh', 'region-lebanon-north-akkar-tripoli', 'קובה זע׳רתאוויה', 'Kibbeh Zghartawiyeh', 'lebanon-north-zgharta', array( 'food-heritage-kibbeh-regions' ), 'קובה בשרית מזע׳רתא המעוצבת בתבנית זכוכית לצורה אליפטית, ממולאת בשומן כבש ונאפית על פחמים לפי המקור.', 'A Zgharta meat kibbeh shaped in a glass bowl into an oval, filled with sheep fat and cooked over charcoal according to the source.', 'המקור מזהה צורה, מילוי ושיטת חום, אך אינו מספק משקל, טמפרטורת ליבה או זמן בטוח.', 'The source identifies shape, filling and heat method but supplies no weight, safe core temperature or validated time.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Fully cooked Zgharta kibbeh oval with charcoal browning and a cut section showing the sheep-fat filling, no raw interior.' ),
	array( 'dish-kibbeh-summakiyeh-hermel', 'dish', 'kibbeh-summakiyeh-hermel', 'region-lebanon-bekaa-baalbek-hermel', 'קובה סומאקייה מהרמל', 'Hermel Kibbeh Summakiyeh', 'lebanon-hermel', array( 'food-heritage-kibbeh-regions' ), 'כדורי קובה מקמח ובורגול, ממולאים בתפוח אדמה, בצל ותבלינים ומבושלים במי סומאק, לפי וריאנט צפוני של הבקעה.', 'Flour-and-bulgur kibbeh balls filled with potato, onion and spices and boiled in sumac water in a northern Bekaa variant.', 'הצבע הוורוד נובע ממי הסומאק לפי המקור, אך חומציות, זן סומאק ובטיחות מילוי דורשים מדידה.', 'The source attributes the pink color to sumac water, while acidity, sumac identity and filling safety require measurement.', array( 'ingredient-lebanese-bulgur-context', 'ingredient-lebanese-sumac-context' ), array( 'dish-kibbeh-somakiyya' ), 'Hermel kibbeh summakiyeh with pale balls turning pink in clear purple sumac broth, one cut ball showing potato and onion filling.' ),
	array( 'dish-kibbeh-laqtin-west-bekaa', 'dish', 'kibbeh-laqtin-west-bekaa', 'region-lebanon-bekaa-baalbek-hermel', 'קובה לקטין ממערב הבקעה', 'West Bekaa Pumpkin Kibbeh', 'lebanon-west-bekaa', array( 'food-heritage-kibbeh-regions', 'food-heritage-pumpkin-kibbeh' ), 'משפחת קובה מדלעת ובורגול המתועדת כמנת צום צמחונית וגם בגרסאות ממולאות לבנה וקווארמה או מבושלות במרק קישק.', 'A pumpkin-and-bulgur kibbeh family documented as a vegetarian Lent dish and in variants filled with labneh and qawarma or cooked in kishk soup.', 'הווריאנטים נשמרים כהכנות שונות. לא ניתן להציג גרסה צמחונית כברירת מחדל כאשר מילוי חלב או בשרי אפשרי.', 'The variants remain separate preparations. A vegetarian default cannot be assumed where dairy or meat fillings are possible.', array( 'ingredient-lebanese-bulgur-context', 'ingredient-lebanese-kishk' ), array(), 'Comparative West Bekaa pumpkin kibbeh study with one baked vegetarian piece and one separate kishk-soup variant, clearly separated, fully cooked.' ),
	array( 'dish-kibbeh-arnabiyyeh-beirut', 'dish', 'kibbeh-arnabiyyeh-lebanese-coast', 'region-lebanon-beirut', 'קובה ארנבייה בחוף הלבנוני', 'Kibbeh Arnabiyyeh on the Lebanese Coast', 'lebanon-coast-beirut', array( 'food-heritage-kibbeh-regions' ), 'כדורי קובה בשריים ברוטב טחינה ומיצי הדרים מרובים, המתועדים גם בשם קובה בטחינה בהקשר חופי.', 'Meat kibbeh balls in a tahini sauce with multiple citrus juices, also documented as kibbeh bil tahini in a coastal context.', 'סוגי ההדרים ורמת החומציות משתנים, ולכן אין להעתיק מספר קבוע או pH ללא בדיקת נוסחה בפועל.', 'Citrus composition and acidity vary, so no fixed count or pH is asserted without testing the actual formula.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Fully cooked kibbeh arnabiyeh in pale tahini-citrus sauce with separate citrus segments indicating variety, no raw meat.' ),
	array( 'dish-kibbeh-samak-lebanon', 'dish', 'kibbeh-samak-lebanon', 'hub-lebanese-plant-seafood-table', 'קובה דג בחוף הלבנוני', 'Lebanese Coastal Fish Kibbeh', 'lebanon-coast', array( 'food-heritage-kibbeh-regions' ), 'דג מצונן, מנוקה מעור ועצמות, מעורבב עם בורגול דק, כוסברה, קליפת תפוז ותבלינים ונאפה או מטוגן עם בצל ואגוזים לפי המקור.', 'Well-chilled skinned and deboned fish mixed with fine bulgur, coriander, orange rind and spices, then baked or fried with onion and nuts according to the source.', 'זהות הדג, קירור, עצמות, אלרגנים וטמפרטורת ליבה הם שערי בטיחות הכרחיים לפני מתכון או מוצר.', 'Fish identity, chilling, bones, allergens and core temperature are mandatory safety gates before any recipe or product.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Fully cooked Lebanese coastal fish kibbeh with orange rind, coriander and onion-nut filling visible in a clean cut section, no raw fish.' ),
	array( 'dish-kibbeh-nayyeh-lebanon', 'dish', 'kibbeh-nayyeh-lebanon-safety-record', 'hub-lebanese-kibbeh-family', 'קובה נייה, רשומת בטיחות', 'Kibbeh Nayyeh, Safety Record', 'lebanon-national', array( 'food-heritage-kibbeh-regions', 'cdc-raw-kibbeh-lebanon' ), 'רשומת זיהוי לא indexable של מנת בשר נא ובורגול. היא אינה כוללת מתכון, המלצת צריכה, זמן החזקה או הצגת הגשה מפתה.', 'A non-indexable identity record for a raw meat and bulgur dish. It contains no recipe, consumption recommendation, holding time or promotional serving image.', 'התפרצות סלמונלה שנקשרה לקובה מבקר נא מדגימה מדוע אין לפרסם הוראות הכנה או צריכה ללא מומחה בטיחות ותהליך מאומת.', 'A Salmonella outbreak linked to raw beef kibbeh demonstrates why no preparation or consumption instructions are published without a food-safety expert and validated process.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Non-promotional food-safety still life with a sealed chilled raw-meat sample physically separated from bulgur and herbs, neutral clinical lighting, blank warning-card area, no serving suggestion.', 'raw-meat-safety' ),
	array( 'dish-lentil-fennel-kibbeh-andaket', 'dish', 'lentil-fennel-kibbeh-andaket', 'region-lebanon-north-akkar-tripoli', 'קובה עדשים ושומר מעַנְדַקֶת', 'Andaket Lentil and Fennel Kibbeh', 'lebanon-akkar-andaket', array( 'food-heritage-mtashtash' ), 'זהות מועמדת מעכאר המבוססת על קשר מתועד בין בורגול, עדשים ושומר. היא נשמרת לבדיקת מקור נוסף לפני ניסוח מתכון.', 'A candidate Akkar identity based on a documented link among bulgur, lentils and fennel. It is held for a second source before a recipe is written.', 'המקור אינו מספק די פרטי יחס וטכניקה, ולכן הרשומה היא מפת זהות ולא נוסחת ייצור.', 'The source supplies insufficient ratio and technique detail, so this is an identity map rather than a production formula.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Ingredient-boundary study for Andaket lentil and fennel kibbeh with cooked lentils, fennel fronds and bulgur separated, no invented final form.' ),
	array( 'dish-samkeh-harra-tripoli', 'dish', 'samkeh-harra-tripoli', 'region-lebanon-north-akkar-tripoli', 'סמקה חררה טריפוליטאית', 'Tripoli Samkeh Harra', 'lebanon-tripoli', array( 'food-heritage-samkeh-harra' ), 'דג אפוי או מבושל בהקשר טריפוליטאי עם רוטב חריף, טחינה, אגוזים ועשבים לפי המקור, הנשמר בנפרד מווריאנטים בחוף הסורי.', 'Fish in a Tripoli context with a spicy sauce, tahini, nuts and herbs according to the source, retained separately from Syrian coastal variants.', 'סוג הדג, האלרגנים, עוצמת החריפות וטמפרטורת הליבה דורשים אימות למנה בפועל.', 'Fish identity, allergens, heat level and core temperature require verification for the actual dish.', array(), array( 'dish-samaka-harra-baniyas' ), 'Fully cooked Tripoli samkeh harra with tahini, nuts and herb-spice sauce, fish flakes opaque and moist, no raw center.' ),
	array( 'dish-sayadiyah-lebanon', 'dish', 'sayadiyah-lebanon-context', 'hub-lebanese-plant-seafood-table', 'סיאדייה בהקשר הלבנוני', 'Sayadiyah in Lebanese Context', 'lebanon-coast', array( 'food-heritage-samkeh-harra' ), 'רשומת הקשר למשפחת דג, אורז ובצל חופית. היא נשמרת נפרדת מן הגרסה הסורית עד מקור לבנוני מפורט ומתכון שנבדק.', 'A context record for a coastal fish, rice and onion family. It remains separate from the Syrian version until a detailed Lebanese source and tested recipe exist.', 'המקור הנוכחי אינו מספק BOM לבנוני מלא, ולכן אין מיזוג עם ישות סורית או יצירת מוצר.', 'The current source does not provide a complete Lebanese BOM, so no merge with a Syrian entity and no product is created.', array(), array( 'dish-sayadiyah-syrian-coast' ), 'Lebanese-context sayadiyah research plate with fully cooked fish, brown onion rice and a separate ingredient key zone, no origin claim.' ),
	array( 'dish-sfiha-baalbek', 'dish', 'sfiha-baalbek', 'region-lebanon-bekaa-baalbek-hermel', 'ספיחה בעלבכית', 'Baalbek Sfiha', 'lebanon-baalbek', array( 'food-heritage-sfeeha' ), 'מאפה בשר קטן המזוהה עם בעלבכ במקור, עם בצק, בשר ותיבול הדורשים אימות נוסחה, אלרגנים וטמפרטורת ליבה.', 'A small meat pastry identified with Baalbek in the source, with dough, meat and seasoning requiring formula, allergen and core-temperature verification.', 'המקור תומך בזהות האזורית ובמבנה המאפה אך אינו מספיק למפרט מסחרי או להבטחת בטיחות.', 'The source supports the regional identity and pastry structure but is insufficient for a commercial specification or safety guarantee.', array(), array(), 'Baalbek sfiha as small fully baked open meat pastries, even browning, one cut edge showing cooked filling, neutral stone surface.' ),
	array( 'dish-moufataka-beirut', 'dish', 'moufataka-beirut', 'region-lebanon-beirut', 'מופתקה ביירותית', 'Beirut Moufataka', 'lebanon-beirut', array( 'food-heritage-beiruti-assida' ), 'זהות קינוח ביירותית הנשמרת כנקודת מחקר נפרדת מאסידה, עד מקור ייעודי שיאמת רכיבים, שיטה ושם בערבית.', 'A Beirut dessert identity retained separately from assida until a dedicated source verifies ingredients, method and Arabic naming.', 'אין מספיק מידע ליצירת נוסחה או תמונת מנה סופית מדויקת, ולכן הנכס החזותי מתמקד ברכיבי מחקר בלבד.', 'There is insufficient evidence for a formula or exact finished-dish image, so the visual specification uses research components only.', array(), array(), 'Abstract Beirut dessert ingredient study with rice, tahini, turmeric-toned mixture and nuts in separate bowls, no invented final plating.' ),
	array( 'dish-loubieh-bi-zayt-lebanon', 'dish', 'loubieh-bi-zayt-lebanon', 'cuisine-lebanese-regional', 'לוביה בשמן בהקשר הלבנוני', 'Loubieh bi Zayt in Lebanese Context', 'lebanon-national', array( 'food-heritage-mouneh' ), 'תבשיל צמחי של שעועית ושמן זית בהקשר לבנוני, הנשמר כישות משותפת־אזורית בלי טענת מקור בלעדי.', 'A plant-based bean and olive-oil dish in Lebanese context, retained as a shared regional identity without an exclusive-origin claim.', 'זן השעועית, הבשלות, השמן והחומציות אינם נמדדים במקור הנוכחי.', 'Bean variety, maturity, oil and acidity are not measured in the current source.', array( 'ingredient-lebanese-olive-oil-context' ), array(), 'Fully cooked loubieh bi zayt with tender green beans, tomato and olive oil sheen, neutral family-style bowl, no health claim.' ),
	array( 'dish-fennel-tabbouleh-jezzine', 'dish', 'fennel-tabbouleh-jezzine', 'region-lebanon-south-jabal-amel', 'טאבולה שומר מג׳זין', 'Jezzine Fennel Tabbouleh', 'lebanon-south-jezzine', array( 'food-heritage-mtashtash' ), 'וריאנט מועמד מדרום לבנון שבו שומר מקבל תפקיד מרכזי. הוא דורש מקור שני לפני קביעה של נוסחה ושיוך יישובי.', 'A candidate South Lebanese variant in which fennel has a central role. It requires a second source before a formula and locality claim are finalized.', 'הזהות נשמרת כטיוטת מחקר, ללא יחס עשבים, בורגול או חומצה.', 'The identity remains a research draft without an asserted herb, bulgur or acid ratio.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Jezzine fennel tabbouleh research plate with fennel fronds visually dominant and other components separated at the rim, no invented ratio.' ),
	array( 'dish-moghrabieh-lebanon', 'dish', 'moghrabieh-lebanon-context', 'cuisine-lebanese-regional', 'מוגרבייה בהקשר הלבנוני', 'Moghrabieh in Lebanese Context', 'lebanon-national', array( 'food-heritage-mouneh' ), 'זהות מנה של פתיתי סולת גדולים, קטניות ובצל בהקשר לבנוני. נדרש מקור מתכון ייעודי לפני BOM מלא.', 'A dish identity of large semolina pearls, legumes and onion in Lebanese context. A dedicated recipe source is required before a full BOM.', 'הרשומה אינה קובעת סוג קטנית, בשר, תבלין או יחס נוזלים ללא מקור מתאים.', 'The record does not assert legume, meat, spice or liquid ratios without an appropriate source.', array(), array(), 'Moghrabieh context bowl with large semolina pearls, chickpeas and pearl onions visible, fully cooked, neutral research styling.' ),
	array( 'dish-mtashtash-akkar', 'dish', 'mtashtash-akkar', 'region-lebanon-north-akkar-tripoli', 'מטשטש מעכאר', 'Akkar Mtashtash', 'lebanon-akkar', array( 'food-heritage-mtashtash' ), 'מנה עכארית המתוארת כטאבולה כוזבת, עם בורגול, בצל ותבלינים ובלא שפע העשבים של טאבולה רגילה.', 'An Akkar dish described as false tabbouleh, using bulgur, onion and seasonings without the abundant herbs of standard tabbouleh.', 'השם וההבחנה מתועדים, אך יחסי ההשריה והתיבול דורשים מבחן מטבח.', 'The name and distinction are documented, while soaking and seasoning ratios require kitchen testing.', array( 'ingredient-lebanese-bulgur-context' ), array(), 'Akkar mtashtash with bulgur and onion clearly visible and minimal herbs, documentary overhead plate, no comparison text embedded.' ),
	array( 'dish-lemon-zenkoul-west-bekaa', 'dish', 'lemon-zenkoul-west-bekaa', 'region-lebanon-bekaa-baalbek-hermel', 'זנקול לימון ממערב הבקעה', 'West Bekaa Lemon Zenkoul', 'lebanon-west-bekaa', array( 'food-heritage-lemon-zenkoul' ), 'מנה אזורית של כדורי בצק ברוטב לימוני המתועדת בהקשר עונתי ונוצרי במערב הבקעה, בלי לייחס אותה לכל משקי הבית.', 'A regional dish of dough pieces in a lemon sauce documented in a seasonal Christian context in West Bekaa, without assigning it to every household.', 'המקור תומך בשם ובהקשר אך אינו מספק pH, עובי בצק או פרוטוקול אחסון.', 'The source supports the name and context but supplies no pH, dough thickness or storage protocol.', array(), array(), 'West Bekaa lemon zenkoul with fully cooked small dough pieces in a bright lemon sauce, neutral bowl and no religious symbols.' ),
	array( 'dish-moufataka-beirut-assida', 'dish', 'beiruti-assida', 'region-lebanon-beirut', 'אסידה ביירותית', 'Beiruti Assida', 'lebanon-beirut', array( 'food-heritage-beiruti-assida' ), 'דייסה מתוקה המזוהה במקור עם ביירות ומוגשת בהקשרים משפחתיים, הנשמרת בנפרד ממופתקה ומקינוחים דומים.', 'A sweet porridge identified with Beirut in the source and served in family contexts, retained separately from moufataka and similar desserts.', 'המקור אינו מספק מדידות עמילן, טמפרטורת ג׳לטיניזציה או יציבות לאחר קירור.', 'The source supplies no starch measurements, gelatinization temperature or post-cooling stability data.', array(), array(), 'Beiruti assida in a shallow bowl with smooth porridge texture and separate nut garnish, soft side light, no event symbols.' ),
	array( 'dish-meghle-lebanon', 'dish', 'meghle-lebanon', 'cuisine-lebanese-regional', 'מגהלי בלבנון', 'Meghle in Lebanon', 'lebanon-national', array( 'food-heritage-meghle' ), 'פודינג אורז מתובל המוגש סביב לידה לפי המקור, עם אגוזים וקוקוס כתוספות אפשריות ועם אלרגנים שיש לסמן.', 'A spiced rice pudding served around childbirth according to the source, with nuts and coconut as possible toppings and allergens requiring declaration.', 'זהות התבלינים, סמיכות, סוג האורז והאלרגנים דורשים מתכון ומפרט נפרדים.', 'Spice identity, viscosity, rice type and allergens require a separate recipe and specification.', array(), array(), 'Lebanese meghle with smooth cinnamon-toned rice pudding and nuts and coconut in separate garnish bands, fully prepared, no birth symbolism.' ),
	array( 'dish-kaak-el-abbass-south-lebanon', 'dish', 'kaak-el-abbass-south-lebanon', 'region-lebanon-south-jabal-amel', 'כעכ אל־עבאס בדרום לבנון', 'Kaak el-Abbass in South Lebanon', 'lebanon-south', array( 'food-heritage-kaak-abbass' ), 'עוגיות חלוקה המתועדות בהקשר עאשורא בדרום לבנון ובהכנה קהילתית, עם מעבר חלקי לרכישה ממאפיות.', 'Distribution cookies documented in an Ashura context in South Lebanon and communal preparation, with some transition to bakery purchase.', 'טענות מסורתיות על חיי מדף אינן מועברות למפרט מוצר בלי בדיקת פעילות מים, אריזה ומיקרוביולוגיה.', 'Traditional shelf-life claims are not transferred to a product specification without water-activity, packaging and microbiology testing.', array(), array(), 'South Lebanon kaak el-Abbass cookies arranged for communal distribution in plain paper packets, no religious symbols, labels or shelf-life cues.' ),
	array( 'dish-hamod-lebanese-jewish-family', 'dish', 'hamod-lebanese-jewish-family', 'hub-lebanese-community-foodways', 'חמוד במשפחת דהן', 'Hamod in the Dahan Family', 'lebanese-jewish-family', array( 'jfs-lebanese-hamod-story', 'jfs-lebanese-hamod-recipe' ), 'מרק לימוני של תפוחי אדמה, סלרי ושום המוגש על אורז בעדות משפחת דהן, ומוצג כמיזוג של גרסאות שתי סבתות וכמנה לשבת.', 'A lemony potato, celery and garlic soup served over rice in the Dahan family account, described as a synthesis of two grandmothers’ versions and a Shabbat dish.', 'העדות היא משפחתית ואינה מוצגת כנוסחה של כל יהודי לבנון או של המטבח הלבנוני כולו.', 'The evidence is family-specific and is not presented as a formula for all Lebanese Jews or Lebanese cuisine as a whole.', array(), array(), 'Dahan-family hamod with lemony broth, potato and celery over a separate mound of rice, warm home-table light, no religious props.' ),
	array( 'dish-bazela-lebanese-jewish-family', 'dish', 'bazela-lebanese-jewish-family', 'hub-lebanese-community-foodways', 'בזלה במשפחת דהן', 'Bazela in the Dahan Family', 'lebanese-jewish-family', array( 'jfs-lebanese-bazela', 'jfs-lebanese-hamod-story' ), 'מנת אפונה מתובלת בפלפל אנגלי בעדות משפחת דהן, המקושרת להגשת חמוד ואורז ולא לכלל קהילת יהודי לבנון.', 'An allspice-seasoned pea dish in the Dahan family account, linked to serving hamod and rice rather than to all Lebanese Jewish households.', 'המקור מספק נוסחת משפחה, אך היא דורשת מבחן מטבח ותיעוד משקל לפני פרסום.', 'The source supplies a family formula, while kitchen testing and weight documentation are required before publication.', array(), array(), 'Fully cooked bazela with green peas and warm allspice tones in a small bowl beside hamod and rice, no universal-community cue.' ),
	array( 'dish-mahshi-lebanese-jewish-family', 'dish', 'mahshi-lebanese-jewish-family', 'hub-lebanese-community-foodways', 'מחשי במשפחת דהן', 'Mahshi in the Dahan Family', 'lebanese-jewish-family', array( 'jfs-lebanese-mahshi-zucchini', 'jfs-lebanese-mahshi-onion' ), 'שתי הכנות משפחתיות של קישוא ובצל ממולאים בבקר ואורז עם לימון, מולסת רימון, רסק עגבניות וסוכר, המתועדות סביב חגים.', 'Two family preparations of zucchini and onion stuffed with beef and rice plus lemon, pomegranate molasses, tomato paste and sugar, documented around holidays.', 'קישוא ובצל נשמרים כווריאנטים של ישות משפחתית אחת בשלב זה, בלי להציגם כמתכון קהילתי אחיד.', 'Zucchini and onion remain variants of one family entity at this stage and are not presented as one uniform community recipe.', array( 'ingredient-lebanese-pomegranate-molasses' ), array(), 'Two separate fully cooked Dahan-family mahshi variants, zucchini and onion, each cut to show cooked beef and rice filling, no holiday symbols.' ),
	array( 'dish-karabij-lebanese-jewish-wedding', 'dish', 'karabij-lebanese-jewish-wedding', 'hub-lebanese-community-foodways', 'קרביג׳ בחתונות יהודי לבנון', 'Karabij in Lebanese Jewish Weddings', 'lebanese-jewish', array( 'foodish-lebanese-karabij' ), 'מאפי סולת וקמח במילוי פיסטוק המוגשים עם מרנג מי ורדים, מתועדים כאחד משבעה כיבודי חתונה בעדות נינה דבש.', 'Semolina-and-flour pastries filled with pistachio and served with rose-scented meringue, documented as one of seven wedding refreshments in Nina Dabash’s account.', 'המקור מדגיש שונות בין משקי בית וכמעט היעלמות של המנהג בישראל, ולכן אין להציג גרסה אחת כתקן הקהילה.', 'The source emphasizes household variation and the practice’s near disappearance in Israel, so no single version is presented as a community standard.', array(), array(), 'Lebanese Jewish wedding karabij with pistachio-filled semolina pastries and rose-scented white meringue in a separate bowl, no wedding symbols.' ),
);

foreach ( $c99_lebanese_food_rows as $row ) {
	$spec = array(
		'id' => $row[0], 'type' => $row[1], 'slug' => $row[2], 'parent_id' => $row[3],
		'name_he' => $row[4], 'name_en' => $row[5], 'region' => $row[6], 'sources' => $row[7],
		'summary_he' => $row[8], 'summary_en' => $row[9], 'fact_he' => $row[10], 'fact_en' => $row[11],
		'requires' => $row[12], 'references' => $row[13], 'prompt_en' => $row[14],
		'schema_type' => 'Article',
	);
	if ( isset( $row[15] ) ) {
		$spec['community'] = $row[15];
	}
	if ( 'raw-meat-safety' === ( isset( $row[15] ) ? $row[15] : '' ) ) {
		$spec['community'] = 'lebanese-multi-community';
		$spec['compliance'] = array(
			array( 'raw-ground-meat', 'בשר טחון נא עלול לשאת פתוגנים. אין ברשומה הוראות הכנה או המלצת צריכה; נדרשים מומחה בטיחות מזון ותהליך מאומת.', 'Raw ground meat can carry pathogens. This record provides no preparation instructions or consumption recommendation; a food-safety expert and validated process are required.', array( 'cdc-raw-kibbeh-lebanon' ) ),
		);
	}
	$c99_lebanese_rows[] = $spec;
}

/* Ingredients and techniques. */
$c99_lebanese_ingredient_rows = array(
	array( 'ingredient-lebanese-zaatar-blend', 'lebanese-zaatar-blend', 'תערובת זעתר לבנונית, הקשר', 'Lebanese Zaatar Blend Context', 'hub-lebanese-manouche-practice', 'lebanon-national', array( 'unesco-al-manouche-2023', 'food-heritage-breakfast-kings' ), 'תערובת המתוארת סביב טימין או זעתר, סומאק, שומשום קלוי, מלח ושמן זית בשימוש למנאקיש, עם שונות בין יצרנים ומשקי בית.', 'A blend described around thyme or zaatar, sumac, toasted sesame and salt for manouche use, with variation among producers and households.', 'הרכב הזן, אחוזי השומשום והמלח, אלרגנים וזהות בוטנית דורשים מפרט לכל מוצר בפועל.', 'Botanical identity, sesame and salt percentages, allergens and formulation require a specification for each actual product.', 'Commercial ingredient studio shot of an unbranded Lebanese-context zaatar blend with whole sumac, toasted sesame and thyme specimens separated around it.' ),
	array( 'ingredient-lebanese-bulgur-context', 'lebanese-bulgur-context', 'בורגול במטבח הלבנוני', 'Bulgur in Lebanese Cuisine', 'cuisine-lebanese-regional', 'lebanon-national', array( 'food-heritage-kibbeh-regions', 'food-heritage-mujaddara-hamra' ), 'ישות הקשר לגרגרי חיטה מבושלים, מיובשים וסדוקים בדרגות שונות המשמשים קובה, טאבולה ומג׳דרה.', 'A context entity for parboiled, dried and cracked wheat in different grades used for kibbeh, tabbouleh and mujaddara.', 'דרגת גריסה, זן חיטה, לחות, ספיגה וגלוטן אינם אחידים ודורשים מפרט SKU.', 'Particle grade, wheat variety, moisture, absorption and gluten status are not uniform and require an SKU specification.', 'Studio ingredient taxonomy of coarse, medium and fine bulgur in separate unbranded bowls, consistent scale and visible particle differences.' ),
	array( 'ingredient-lebanese-kishk', 'lebanese-kishk', 'קישק לבנוני, מערכת אזורית', 'Lebanese Kishk, Regional System', 'hub-lebanese-mouneh-system', 'lebanon-national', array( 'food-heritage-kishk-winter', 'food-heritage-kishk-preparation', 'lebanese-kishk-rheology-2016' ), 'מוצר מותסס ומיובש ממשפחת דגן וחלב עם וריאנטים אזוריים, לרבות הקשרים בעַרְסָאל, ח׳רבת קנפר ומעאסר א־שוף.', 'A fermented and dried grain-and-dairy product family with regional variants, including contexts in Aarsal, Kherbet Qanafar and Maasser el Chouf.', 'מחקר על דגימות אומנותיות מצביע על שונות במטריצה ובזרימה. הוא אינו מאשר pH, פעילות מים, תרבית או חיי מדף של מוצר מסוים.', 'Research on artisanal samples indicates matrix and flow variability. It does not verify pH, water activity, culture or shelf life for a specific product.', 'Comparative Lebanese kishk powder study with three regional textures in separate bowls, grain and dairy inputs nearby, no shelf-life cue.' ),
	array( 'ingredient-labneh-ambarees-shouf', 'labneh-ambarees-context', 'אמבריס ולבנה מסורתית', 'Ambarees and Traditional Labneh Context', 'hub-lebanese-mouneh-system', 'lebanon-bekaa-chouf', array( 'food-heritage-goat-dairy', 'fermented-cheese-safety-2025' ), 'ישות הבחנה למוצרי חלב מותססים ומרוכזים המכונים אמבריס או שמות מקומיים, בלי לאחד תהליכים שונים למוצר אחד.', 'A distinction entity for fermented and concentrated dairy products called ambarees or local names, without merging different processes into one product.', 'תרבית, חלב מקור, מלח, pH, פעילות מים ומיקרוביולוגיה חייבים להימדד לכל יצרן ואצווה.', 'Culture, source milk, salt, pH, water activity and microbiology must be measured for each producer and batch.', 'Traditional ambarees and labneh texture comparison in separate unbranded ceramic vessels, chilled studio setting, no shelf-life or health claim.' ),
	array( 'ingredient-lebanese-qawarma', 'lebanese-qawarma-context', 'קווארמה לבנונית, הקשר שימור', 'Lebanese Qawarma Preservation Context', 'hub-lebanese-mouneh-system', 'lebanon-national', array( 'food-heritage-mouneh' ), 'בשר משומר בשומן בהקשר של מונה, הנשמר כרשומת ידע פרטית עד תהליך חום, מלח, אריזה ואחסון מאומתים.', 'Meat preserved in fat within a mouneh context, retained as a private knowledge record until heat, salt, packaging and storage are validated.', 'אין להסיק בטיחות או יציבות מדברי מסורת. המוצר דורש תהליך HACCP, עקיבות ובדיקת מעבדה.', 'Traditional practice does not establish safety or stability. The product requires a HACCP process, traceability and laboratory validation.', 'Non-promotional qawarma safety study with a sealed jar sample, cooked meat and rendered fat layers visible, refrigeration context, no serving suggestion.' ),
	array( 'ingredient-lebanese-pomegranate-molasses', 'lebanese-pomegranate-molasses-context', 'מולסת רימון בהקשר הלבנוני', 'Pomegranate Molasses in Lebanese Context', 'hub-lebanese-mouneh-system', 'lebanon-national', array( 'food-heritage-mouneh', 'spinneys-mymoune-2026' ), 'רכז רימונים סמיך המשמש בבישול ובמזווה, הנשמר בנפרד מרכז רימון, מיץ או מוצר סורי עד אימות זהות ותווית.', 'A thick pomegranate reduction used in cooking and pantry practice, retained separately from concentrate, juice or a Syrian product until identity and label are verified.', 'בריקס, pH, סוכר, תוספים וזני פרי אינם נלמדים מהשם בלבד ודורשים בדיקת מוצר.', 'Brix, pH, sugar, additives and fruit varieties cannot be inferred from the name and require product testing.', 'Dark pomegranate molasses in an unbranded glass vessel with fresh pomegranate and a viscosity ribbon, no package or health claim.' ),
	array( 'ingredient-lebanese-sumac-context', 'lebanese-sumac-context', 'סומאק במטבח הלבנוני', 'Sumac in Lebanese Cuisine', 'cuisine-lebanese-regional', 'lebanon-national', array( 'food-heritage-kibbeh-regions', 'unesco-al-manouche-2023' ), 'ישות הקשר לתבלין חמצמץ במנאקיש, קובה ומנות אחרות, בלי להניח זן, טחינה, מלח או חומציות של מוצר.', 'A context entity for a sour spice used in manouche, kibbeh and other dishes, without assuming cultivar, grind, salt or acidity for a product.', 'השם סומאק אינו מפרט מדעי. נדרש זיהוי בוטני, טוהר, אלרגנים אפשריים וניתוח מזהמים לכל SKU.', 'The name sumac is not a scientific specification. Botanical identification, purity, possible allergens and contaminant analysis are required for each SKU.', 'Ingredient studio shot of whole and ground sumac in separate bowls with botanical fruit cluster specimen, neutral background, no origin seal.' ),
	array( 'ingredient-lebanese-olive-oil-context', 'lebanese-olive-oil-context', 'שמן זית לבנוני, שונות זנית', 'Lebanese Olive Oil, Cultivar Variation', 'hub-lebanese-plant-seafood-table', 'lebanon-national', array( 'lebanon-olive-cultivars-2019', 'lebanon-olive-cultivars-2023' ), 'ישות מדעית לשונות בין זני זית, דרגת הבשלה, פרופיל חומצות שומן ופנולים, בלי להשליך תוצאה אחת על כל שמן מלבנון.', 'A scientific entity for variation among olive cultivars, ripening stage, fatty-acid profile and phenolics without projecting one result onto every oil from Lebanon.', 'מחקרי הזנים מראים שונות משמעותית, ולכן כל טענת חומציות או פוליפנולים חייבת להיות אנליזה של אצווה מסוימת.', 'Cultivar studies show substantial variation, so every acidity or polyphenol claim must come from analysis of a specific batch.', 'Scientific olive-oil cultivar comparison with eleven small oil samples from pale gold to green, separate olive specimens, no health badges.' ),
);
foreach ( $c99_lebanese_ingredient_rows as $row ) {
	$c99_lebanese_rows[] = array(
		'id' => $row[0], 'type' => 'ingredient', 'slug' => $row[1], 'parent_id' => $row[4],
		'name_he' => $row[2], 'name_en' => $row[3], 'region' => $row[5], 'sources' => $row[6],
		'summary_he' => $row[7], 'summary_en' => $row[8], 'fact_he' => $row[9], 'fact_en' => $row[10],
		'dimension' => 'scientific', 'prompt_en' => $row[11],
	);
}

$c99_lebanese_technique_rows = array(
	array( 'technique-manouche-indenting-baking-lebanon', 'manouche-indenting-baking-lebanon', 'חריצה ואפיית מנאקיש', 'Manouche Indenting and Baking', 'hub-lebanese-manouche-practice', 'lebanon-national', array( 'unesco-al-manouche-2023' ), 'טכניקה של לחיצה בקצות האצבעות לפני פיזור התערובת ואפייה, המתועדת כחלק מן המנהג.', 'A fingertip-indenting technique before applying the topping and baking, documented as part of the practice.', 'המקור אינו מספק הידרציה, טמפרטורת אבן, עובי או זמן אפייה אחידים.', 'The source supplies no uniform hydration, stone temperature, thickness or baking time.', 'Close process study of fingertips making evenly spaced dimples in manouche dough before topping, hands cropped and anatomically correct.' ),
	array( 'technique-kibbeh-pounding-forming-lebanon', 'kibbeh-pounding-forming-lebanon', 'כתישה ועיצוב קובה בלבנון', 'Lebanese Kibbeh Pounding and Forming', 'hub-lebanese-kibbeh-family', 'lebanon-national', array( 'food-heritage-kibbeh-regions' ), 'ציר טכני של עיבוד בורגול ורכיב חלבון או ירק לצורות שונות, עם כלים ושיטות המשתנים לפי הווריאנט.', 'A technical axis for working bulgur with a protein or vegetable component into different forms, with tools and methods varying by variant.', 'גודל חלקיק, טמפרטורה, אנרגיית ערבול וסיום תרמי אינם אחידים ודורשים ניסוי נפרד לכל מנה.', 'Particle size, temperature, mixing energy and thermal finish are not uniform and require separate testing for each dish.', 'Kibbeh forming process board with mortar, smooth cooked mixture and three distinct fully cooked shapes, no raw-meat preparation.' ),
	array( 'technique-kishk-fermentation-drying-lebanon', 'kishk-fermentation-drying-lebanon', 'התססת וייבוש קישק', 'Lebanese Kishk Fermentation and Drying', 'hub-lebanese-mouneh-system', 'lebanon-national', array( 'food-heritage-kishk-preparation', 'lebanese-kishk-rheology-2016' ), 'שרשרת של ערבוב דגן וחלב מותסס, הבשלה, ייבוש ופירור, עם שונות בין אזורים ומשקי בית.', 'A chain of combining grain and fermented dairy, maturation, drying and crumbling, with variation among regions and households.', 'תיאור מסורתי אינו מאמת pH, פעילות מים או חיסול פתוגנים. כל ייצור מסחרי דורש תהליך מדוד ומאושר.', 'A traditional description does not verify pH, water activity or pathogen control. Commercial production requires a measured and validated process.', 'Four-stage kishk process study showing grain-dairy mixture, maturation vessel, hygienic drying and powder, no outdoor contamination cues.' ),
	array( 'technique-labneh-ambarees-sirdele-fermentation', 'labneh-ambarees-fermentation', 'תסיסת אמבריס ולבנה מסורתית', 'Ambarees and Traditional Labneh Fermentation', 'hub-lebanese-mouneh-system', 'lebanon-bekaa-chouf', array( 'food-heritage-goat-dairy', 'fermented-cheese-safety-2025' ), 'טכניקת חלב מותסס ומסונן בכלים מסורתיים, הנשמרת כמפת תהליך ולא כהוראות ביתיות.', 'A fermented and strained dairy technique using traditional vessels, retained as a process map rather than home instructions.', 'נדרשים חלב מפוסטר או שליטה מקבילה, תרבית מזוהה, טמפרטורה, pH, מלח, קירור ומיקרוביולוגיה.', 'Pasteurized milk or equivalent control, identified culture, temperature, pH, salt, refrigeration and microbiology are required.', 'Controlled dairy process study with traditional vessel beside modern pH meter and chilled sample, no unsafe home-instruction framing.' ),
	array( 'technique-qawarma-preservation-lebanon', 'qawarma-preservation-lebanon', 'שימור קווארמה, שער בטיחות', 'Qawarma Preservation Safety Gate', 'hub-lebanese-mouneh-system', 'lebanon-national', array( 'food-heritage-mouneh', 'foodsafety-safe-temperatures-lebanon' ), 'מפת תהליך פרטית לבשר מבושל בשומן, ללא זמנים, טמפרטורות אחסון או הוראות שימור לציבור.', 'A private process map for meat cooked in fat, without public holding times, storage temperatures or preservation instructions.', 'חימום בטוח לבדו אינו מוכיח יציבות מדף. נדרשים תהליך, אריזה, קירור, עקיבות ובדיקות לכל אצווה.', 'Safe cooking alone does not prove shelf stability. Process, packaging, refrigeration, traceability and batch testing are required.', 'Food-safety process diagram photographed as sealed cooked-meat sample, calibrated thermometer and chilled storage tray, no recipe cues.' ),
);
foreach ( $c99_lebanese_technique_rows as $row ) {
	$c99_lebanese_rows[] = array(
		'id' => $row[0], 'type' => 'technique', 'slug' => $row[1], 'parent_id' => $row[4],
		'name_he' => $row[2], 'name_en' => $row[3], 'region' => $row[5], 'sources' => $row[6],
		'summary_he' => $row[7], 'summary_en' => $row[8], 'fact_he' => $row[9], 'fact_en' => $row[10],
		'dimension' => 'scientific', 'prompt_en' => $row[11],
	);
}

/* Traditions and community scopes. */
$c99_lebanese_tradition_rows = array(
	array( 'tradition-al-manouche-sobhiyyeh', 'al-manouche-sobhiyyeh', 'סובחייה סביב מנאקיש', 'Sobhiyyeh Around Al-Manouche', 'hub-lebanese-manouche-practice', 'lebanon-national', 'lebanese-multi-community', array( 'unesco-al-manouche-2023' ), 'מפגש בוקר חברתי סביב הכנה ואכילת מנאקיש, המתועד כחלק מהעברת המנהג בין דורות.', 'A morning social gathering around preparing and eating al-manouche, documented as part of intergenerational transmission.', 'אונסקו מתארת את המנהג כחוצה קהילות וקשור למשפחה ולשכונה, בלי לקבוע שכל בית מקיים אותו באותה צורה.', 'UNESCO describes the practice across communities and linked to family and neighborhood without asserting that every household observes it identically.', 'Morning manouche table with tea, shared flatbread and empty seats, warm daylight, no people or social-class cues.' ),
	array( 'tradition-lebanese-regional-kibbeh-adaptation', 'lebanese-regional-kibbeh-adaptation', 'התאמות אזוריות של קובה בלבנון', 'Regional Kibbeh Adaptation in Lebanon', 'hub-lebanese-kibbeh-family', 'lebanon-national', 'lebanese-multi-community', array( 'food-heritage-kibbeh-regions' ), 'מסורת של שינוי חומרי גלם, רוטב, צורה ושיטת חום לפי אזור ועונה, בתוך משפחת קובה רחבה יותר.', 'A tradition of adapting ingredients, sauce, form and heat method by region and season within a wider kibbeh family.', 'המקור תומך בהבדלים בתוך לבנון וגם באופי המזרח־קרוב של המשפחה, ולכן אין טענת המצאה לאומית.', 'The source supports differences within Lebanon and the wider Near Eastern character of the family, so no national invention claim is made.', 'Regional kibbeh comparison archive with seven fully cooked variants and ingredient swatches, no map flags or origin labels.' ),
	array( 'tradition-lebanese-mouneh-seasonal-cycle', 'lebanese-mouneh-seasonal-cycle', 'מחזור העונות של המונה', 'Lebanese Mouneh Seasonal Cycle', 'hub-lebanese-mouneh-system', 'lebanon-national', 'lebanese-multi-community', array( 'food-heritage-mouneh' ), 'מערכת עונתית של עיבוד תוצרת ושימורה לצריכה מאוחרת, עם שונות מקומית וכלכלית.', 'A seasonal system of processing harvests for later use, with local and economic variation.', 'המשמעות התרבותית אינה אישור בטיחות. כל מוצר עתידי חייב לעבור אימות תהליך, תווית, עקיבות ואחסון.', 'Cultural significance is not a safety approval. Every future product must pass process, label, traceability and storage validation.', 'Four-season Lebanese mouneh cycle with fresh harvest, drying, sealed jars and winter table in separated quadrants, no shelf-life text.' ),
	array( 'tradition-lebanese-jewish-foodways', 'lebanese-jewish-foodways', 'יהודי לבנון: מטבחי משפחה, שבת וחג', 'Lebanese Jewish Foodways: Family, Shabbat and Holiday Tables', 'hub-lebanese-community-foodways', 'lebanon-jewish-diaspora', 'lebanese-jewish', array( 'jfs-lebanese-hamod-story', 'foodish-lebanese-karabij' ), 'מסגרת לעדויות משפחתיות ולמועדים יהודיים בלבנון ובתפוצה, בלי להפוך מנות לבנטיניות משותפות להמצאה יהודית.', 'A frame for family testimony and Jewish occasions in Lebanon and the diaspora without turning shared Levantine dishes into Jewish inventions.', 'חמוד וקרביג׳ נשמרים עם המשפחה והאירוע שהמקור מזהה, ולא כנוסחה אחידה של כל יהודי לבנון.', 'Hamod and karabij remain attached to the family and occasion identified by the source rather than a uniform formula for all Lebanese Jews.', 'Lebanese Jewish family foodways archive with hamod, mahshi and karabij in separate documentary settings, no religious symbols or people.' ),
	array( 'tradition-nina-dahan-shabbat-hamod', 'nina-dahan-shabbat-hamod', 'חמוד לשבת במשפחת דהן', 'Shabbat Hamod in the Dahan Family', 'tradition-lebanese-jewish-foodways', 'lebanon-jewish-diaspora', 'lebanese-jewish-family', array( 'jfs-lebanese-hamod-story' ), 'עדות משפחתית שבה חמוד נמשך כמנת שבת ונוסחת המשפחה משלבת שתי סבתות.', 'A family testimony in which hamod continues as a Shabbat dish and the family version combines two grandmothers.', 'הסיפור הוא ראיה להעברה משפחתית, לא לסולם חשיבות או לשכיחות בכלל הקהילה.', 'The story is evidence of family transmission, not of prevalence or importance across the entire community.', 'Family recipe archive still life for hamod with two handwritten blank recipe cards converging on one soup bowl, no legible text.' ),
	array( 'tradition-sabat-diyafat-lebanese-jewish-wedding', 'sabat-diyafat-lebanese-jewish-wedding', 'שבעת כיבודי החתונה בעדות נינה דבש', 'Seven Wedding Refreshments in Nina Dabash’s Account', 'tradition-lebanese-jewish-foodways', 'lebanon-jewish-diaspora', 'lebanese-jewish', array( 'foodish-lebanese-karabij' ), 'עדות על שבעה כיבודים שהוכנו בידי נשות משפחת החתן, ובהם קרביג׳, עם שונות בין בתים וכמעט היעלמות בישראל.', 'An account of seven refreshments prepared by women of the groom’s family, including karabij, with household variation and near disappearance in Israel.', 'המספר והמנהג נשמרים כעדות מזוהה ולא ככלל מחייב לכל חתונה יהודית בלבנון.', 'The number and practice are retained as a named account rather than a rule for every Jewish wedding in Lebanon.', 'Seven small empty serving settings with karabij occupying one position, archival neutral styling, no wedding or religious symbols.' ),
	array( 'tradition-lebanese-christian-lent-foodways', 'lebanese-christian-lent-foodways', 'מזונות צום נוצריים בלבנון', 'Christian Lent Foodways in Lebanon', 'hub-lebanese-community-foodways', 'lebanon-national', 'lebanese-christian-context', array( 'food-heritage-mujaddara-lent', 'food-heritage-pumpkin-kibbeh', 'food-heritage-lemon-zenkoul' ), 'מסגרת עונתית למג׳דרה, קובה דלעת וזנקול לימון בהקשרים נוצריים מתועדים, בלי להגדיר מטבח נוצרי אחיד.', 'A seasonal frame for mujaddara, pumpkin kibbeh and lemon zenkoul in documented Christian contexts without defining one uniform Christian cuisine.', 'המנות נאכלות גם מחוץ לצום ובקהילות נוספות, ולכן האירוע אינו בעלים בלעדי שלהן.', 'The dishes are also eaten outside Lent and by other communities, so the occasion is not their exclusive owner.', 'Lebanese Lent foodways table with lentil dish, pumpkin kibbeh and lemon zenkoul separated, entirely food-focused, no religious symbols.' ),
	array( 'tradition-south-lebanon-ashura-foodways', 'south-lebanon-ashura-foodways', 'מזונות עאשורא בדרום לבנון', 'Ashura Foodways in South Lebanon', 'hub-lebanese-community-foodways', 'lebanon-south', 'lebanese-shia-context', array( 'food-heritage-kaak-abbass' ), 'מסגרת למזונות חלוקה קהילתיים סביב עאשורא, ובהם כעכ אל־עבאס, ללא בניית קטלוג מטבח שיעי כללי.', 'A frame for communal distribution foods around Ashura, including kaak el-Abbass, without constructing a general Shia cuisine catalog.', 'המקור מתעד הכנה וחלוקה בדרום לבנון ואת המעבר החלקי לרכישה ממאפייה, אך אינו מוכיח אחידות בין קהילות.', 'The source documents preparation and distribution in South Lebanon and some transition to bakery purchase but does not prove uniformity among communities.', 'Communal cookie distribution table with plain packets and serving trays, no people, religious symbols, slogans or banners.' ),
	array( 'tradition-druze-wild-plant-knowledge-chouf-aley', 'druze-wild-plant-knowledge-chouf-aley', 'ידע צמחי בר בקהילות דרוזיות באליי ובשוף', 'Druze Wild-Plant Knowledge in Aley and Chouf', 'region-lebanon-mount-lebanon-shouf', 'lebanon-chouf-aley', 'lebanese-druze-context', array( 'druze-wild-plants-2024' ), 'מחקר תחום המבוסס על 50 ראיונות ומתעד ידע על 68 טקסונים של צמחי בר, שינויים כלכליים וחשש מקטיף יתר.', 'A bounded study based on 50 interviews documenting knowledge of 68 wild plant taxa, economic change and concern about overharvesting.', 'זהו מחקר קהילות מסוימות ולא הדיאטה הדרוזית. אין בו היתר לליקוט, זיהוי עצמי או אכילת צמח לא מאומת.', 'This is a study of specific communities, not the Druze diet. It is not permission for foraging, self-identification or consuming an unverified plant.', 'Botanical documentation board of selected wild edible plants from Aley and Chouf as separated specimens, no consumption or foraging scene.' ),
);
foreach ( $c99_lebanese_tradition_rows as $row ) {
	$spec = array(
		'id' => $row[0], 'type' => 'tradition', 'slug' => $row[1], 'parent_id' => $row[4],
		'name_he' => $row[2], 'name_en' => $row[3], 'region' => $row[5], 'community' => $row[6], 'sources' => $row[7],
		'summary_he' => $row[8], 'summary_en' => $row[9], 'fact_he' => $row[10], 'fact_en' => $row[11],
		'dimension' => 'cultural', 'prompt_en' => $row[12],
	);
	if ( 'tradition-druze-wild-plant-knowledge-chouf-aley' === $row[0] ) {
		$spec['compliance'] = array(
			array( 'wild-plant-identification', 'אין ללקט או לאכול צמח על סמך תמונה או טקסט. נדרשים זיהוי בוטני מקומי, בדיקת רעילות, זכויות גישה ושמירת טבע.', 'Do not forage or consume a plant based on an image or text. Local botanical identification, toxicity review, access rights and conservation controls are required.', array( 'druze-wild-plants-2024' ) ),
		);
	}
	$c99_lebanese_rows[] = $spec;
}

/* Institutions, markets, restaurants, compliance and dated retail references. */
$c99_lebanese_profile_rows = array(
	array( 'institution-food-heritage-foundation', 'culinary_institution', 'food-heritage-foundation-lebanon', 'hub-lebanese-institutions-markets', 'Food Heritage Foundation', 'Food Heritage Foundation', 'lebanon-beirut', array( 'food-heritage-about' ), 'עמותה לבנונית שנוסדה ב־2013 מתוך יחידת ESDU באוניברסיטה האמריקאית בביירות ומתעדת מורשת מזון ותומכת בפרנסה כפרית.', 'A Lebanese nonprofit founded in 2013 through AUB’s ESDU that documents food heritage and supports rural livelihoods.', 'הישות היא מקור מחקר ומוסד עצמאי, לא שותף או ספק של Complete99.', 'The entity is an independent research source and institution, not a Complete99 partner or supplier.', 'Editorial portrait of a culinary heritage research desk with field notes, grain samples and archive folders, no logos or people.' ),
	array( 'institution-phoenicia-culinary-institute', 'culinary_institution', 'phoenicia-culinary-institute', 'hub-lebanese-institutions-markets', 'המכון הקולינרי פניציה', 'Phoenicia Culinary Institute', 'lebanon-beirut', array( 'phoenicia-culinary-institute' ), 'מוסד הכשרה קולינרית מקצועית וחובבנית בביירות לפי המקור הרשמי.', 'A professional and amateur culinary training institution in Beirut according to its official source.', 'הפרופיל מתאר תפקיד מוסדי בלבד ואינו מאמת קורס, מחיר, הסמכה או מקום פנוי במועד עתידי.', 'The profile describes an institutional role only and does not verify a future course, price, accreditation or availability.', 'Neutral culinary classroom with stainless workstations and teaching mise en place, no institution logo or students.' ),
	array( 'institution-aub-palestinian-oral-history-archive', 'culinary_institution', 'aub-palestinian-oral-history-archive', 'hub-palestinian-foodways-lebanon', 'ארכיון ההיסטוריה שבעל פה הפלסטיני ב־AUB', 'AUB Palestinian Oral History Archive', 'lebanon-beirut', array( 'aub-palestinian-oral-history' ), 'ארכיון של כאלף שעות עדות, הכולל קולות דור ראשון וקהילות פלסטיניות בלבנון, ומספק הקשר לזיכרון, הגירה ומזון.', 'An archive of roughly one thousand hours of testimony, including first-generation voices and Palestinian communities in Lebanon, providing context for memory, displacement and food.', 'הארכיון הוא מוסד מחקר. אין להעתיק עדות אישית או להפיק ממנה ליד מסחרי ללא תנאי שימוש והסכמה.', 'The archive is a research institution. Personal testimony must not be copied or turned into a commercial lead without terms-of-use and consent review.', 'Oral-history archive box with audio reel, blank transcript pages and a small food-memory still life, no names or personal identifiers.' ),
	array( 'institution-soufra-burj-el-barajneh', 'culinary_institution', 'soufra-burj-el-barajneh', 'hub-palestinian-foodways-lebanon', 'Soufra בבורג׳ אל־בראג׳נה', 'Soufra in Burj el-Barajneh', 'lebanon-palestinian-diaspora', array( 'soufra-alfanar' ), 'מיזם חברתי בהובלת נשים פלסטיניות המפעיל קייטרינג ומשאית מזון לפי הפרופיל המוסדי.', 'A Palestinian women-led social enterprise operating catering and a food truck according to the institutional profile.', 'הישות מתועדת כמוסד קהילתי בלבד. אין פנייה, גיוס ספקים או פרסום פרטים אישיים ללא הסכמה ובדיקה משפטית וביטחונית.', 'The entity is documented only as a community institution. No outreach, supplier recruitment or personal-data publication is allowed without consent and legal and security review.', 'Respectful social-enterprise kitchen still life with sealed catering trays and a generic food-truck silhouette, no camp location or personal data.' ),
	array( 'institution-tawlet-mar-mikhael', 'culinary_institution', 'tawlet-mar-mikhael', 'hub-lebanese-institutions-markets', 'Tawlet מר מיכאל', 'Tawlet Mar Mikhael', 'lebanon-beirut', array( 'tawlet-official', 'souk-el-tayeb-organization' ), 'מסגרת אירוח עם טבחים מתחלפים ותפריטים אזוריים בתוך מערכת Souk El Tayeb לפי המקורות הרשמיים.', 'A hospitality format with rotating cooks and regional menus within the Souk El Tayeb system according to official sources.', 'זהו Benchmark מוסדי ואירוחי, לא טענה לשותפות, הזמנה או זמינות.', 'This is an institutional and hospitality benchmark, not a claim of partnership, booking or availability.', 'Rotating regional buffet concept with multiple fully cooked dishes and blank chef cards, no people or brand marks.' ),
	array( 'market-souk-el-tayeb', 'market', 'souk-el-tayeb', 'hub-lebanese-institutions-markets', 'Souk El Tayeb', 'Souk El Tayeb', 'lebanon-beirut', array( 'souk-el-tayeb-story', 'souk-el-tayeb-quality' ), 'שוק יצרנים ומערכת איכות המתועדים באתר הרשמי, המשמשים Benchmark למפגש בין חקלאים, מעבדים וצרכנים.', 'A producer market and quality system documented on the official site, used as a benchmark for connecting growers, processors and consumers.', 'כל שעות, מיקום ורשימת יצרנים הם מידע משתנה ודורשים בדיקה מחדש לפני פרסום ציבורי.', 'Hours, location and producer lists are changeable and require rechecking before public publication.', 'Producer-market benchmark with separate farm produce, preserves and quality-check clipboard, no logos, prices or identifiable people.' ),
	array( 'market-dekenet-mar-mikhael', 'market', 'dekenet-mar-mikhael', 'hub-lebanese-institutions-markets', 'Dekenet מר מיכאל', 'Dekenet Mar Mikhael', 'lebanon-beirut', array( 'dekenet-official' ), 'חנות קמעונאית של מונה, שמנים, מי פרחים, שימורים ומזון קפוא לפי המקור הרשמי.', 'A retail shop for mouneh, oils, flower waters, preserves and frozen foods according to the official source.', 'הפרופיל אינו ספר ספקים, אינו הוכחת מלאי ואינו מאפשר רכישה לישראל.', 'The profile is not a supplier directory, does not prove stock and does not enable purchasing into Israel.', 'Lebanese pantry retail benchmark with unbranded jars, oils and flower waters on clean shelves, no copied packaging or price tags.' ),
	array( 'restaurant-hallab-1881', 'restaurant', 'hallab-1881-tripoli', 'region-lebanon-north-akkar-tripoli', 'Hallab 1881', 'Hallab 1881', 'lebanon-tripoli', array( 'hallab-1881-profile' ), 'מוסד ממתקים מטריפולי המשמש Benchmark לקטגוריית ממתקים ואירוח פרימיום.', 'A Tripoli sweets institution used as a benchmark for premium sweets and hospitality.', 'הפרופיל אינו מאמת תפריט, מחיר, כשרות, משלוח או קשר מסחרי נוכחי.', 'The profile does not verify menu, price, kosher status, shipping or a current commercial relationship.', 'Premium Tripoli sweets display with baklava and maamoul in museum-like cases, no logo, packaging or copied storefront.' ),
	array( 'restaurant-mayrig-beirut', 'restaurant', 'mayrig-beirut', 'hub-armenian-lebanese-bourj-hammoud', 'Mayrig', 'Mayrig', 'lebanon-beirut', array( 'mayrig-official' ), 'מסעדה הממסגרת את בישולה דרך זיכרון ארמני ודיאספורי ואינטראקציה ים־תיכונית לפי האתר הרשמי.', 'A restaurant framing its cooking through Armenian and diaspora memory and Mediterranean interaction according to its official site.', 'הישות היא Benchmark של סיפור ותפריט, לא מקור להעתקת מתכונים או עיצוב.', 'The entity is a storytelling and menu benchmark, not a source for copying recipes or design.', 'Armenian-Lebanese fine-dining benchmark table with separate mezze and pastry forms, no restaurant logo or copied plating.' ),
	array( 'restaurant-em-sherif-beirut', 'restaurant', 'em-sherif-beirut', 'region-lebanon-beirut', 'Em Sherif', 'Em Sherif', 'lebanon-beirut', array( 'em-sherif-official' ), 'Benchmark עכשווי לאירוח לבנוני יוקרתי ולמערכת מותג בינלאומית לפי האתר הרשמי.', 'A contemporary benchmark for luxury Lebanese hospitality and an international brand system according to its official site.', 'אין להסיק כוכבי מדריך, מחיר, זמינות או שותפות מעבר למה שמופיע במקור המתוארך.', 'No guide stars, price, availability or partnership is inferred beyond the dated source.', 'Luxury Lebanese hospitality benchmark with elegant shared table, brass accents and fully cooked dishes, no logo or copied interior.' ),
);
$c99_lebanese_hebrew_profile_names = array(
	'institution-food-heritage-foundation' => 'הקרן למורשת המזון',
	'market-souk-el-tayeb' => 'שוק א־טייב',
	'restaurant-hallab-1881' => 'חלאב 1881',
	'restaurant-mayrig-beirut' => 'מאיריג',
	'restaurant-em-sherif-beirut' => 'אם שריף',
);
foreach ( $c99_lebanese_profile_rows as $row ) {
	$schema = in_array( $row[1], array( 'market', 'restaurant' ), true ) ? 'LocalBusiness' : 'Organization';
	$c99_lebanese_rows[] = array(
		'id' => $row[0], 'type' => $row[1], 'slug' => $row[2], 'parent_id' => $row[3],
		'name_he' => isset( $c99_lebanese_hebrew_profile_names[ $row[0] ] ) ? $c99_lebanese_hebrew_profile_names[ $row[0] ] : $row[4], 'name_en' => $row[5], 'region' => $row[6], 'sources' => $row[7],
		'summary_he' => $row[8], 'summary_en' => $row[9], 'fact_he' => $row[10], 'fact_en' => $row[11],
		'dimension' => 'institutional', 'schema_type' => $schema, 'prompt_en' => $row[12],
	);
}

$c99_lebanese_rows[] = array(
	'id' => 'compliance-lebanon-trade-israel-2026', 'type' => 'compliance_rule', 'slug' => 'lebanon-trade-israel-2026', 'parent_id' => 'cuisine-lebanese-regional',
	'name_he' => 'גבול מסחר בין ישראל ללבנון, 2026', 'name_en' => 'Israel-Lebanon Trade Boundary, 2026',
	'region' => 'israel-lebanon-regulatory', 'community' => 'not-applicable', 'sources' => array( 'israel-enemy-states-trade-2026', 'israel-food-importer-registration' ),
	'summary_he' => 'רשומת ציות פרטית הקובעת שכל ישות לבנונית היא מקור עריכתי או Benchmark בלבד ואינה ספק, הצעה, הזמנה או מסלול יבוא.',
	'summary_en' => 'A private compliance record establishing that every Lebanese entity is an editorial source or benchmark only, never a supplier, offer, order or import route.',
	'fact_he' => 'הוראת מנכ״ל 2.4 ממרץ 2026 קובעת איסור גורף על סחר ישיר או עקיף עם מדינות אויב ומונה את לבנון ברשימה. כל חריג מחייב סמכות ואישור מתאימים לפני פעולה.',
	'fact_en' => 'Director-General Instruction 2.4 of March 2026 states a broad prohibition on direct or indirect trade with enemy states and lists Lebanon. Any exception requires the appropriate authority and approval before action.',
	'dimension' => 'economic', 'evidence' => 'regulatory_standard', 'schema_type' => 'Article',
	'compliance' => array(
		array( 'enemy-state-trade', 'אין לפנות לספק לבנוני, להזמין דוגמה, לבצע תשלום, לנתב רכישה דרך צד שלישי או להציג משלוח מלבנון ללא אישור משפטי ורשמי כתוב.', 'Do not contact a Lebanese supplier, order a sample, make payment, route a purchase through a third party or represent delivery from Lebanon without written legal and official authorization.', array( 'israel-enemy-states-trade-2026' ) ),
	),
	'prompt_en' => 'Neutral private compliance dashboard graphic showing blocked direct and indirect trade paths between two generic warehouse nodes, no flags, maps, logos or legal-advice claim.'
);

$c99_lebanese_listing_rows = array(
	array( 'listing-mymoune-pomegranate-molasses-250ml-spinneys-20260807', 'mymoune-pomegranate-molasses-250ml-spinneys-20260807', 'Mymouné מולסת רימון 250 מ״ל, תצפית', 'Mymouné Pomegranate Molasses 250 ml, Observation', 'ingredient-lebanese-pomegranate-molasses', 'spinneys-mymoune-2026', 'נרשם מחיר גלוי של 11.49 דולר אמריקאי בעמוד מותג של Spinneys Lebanon ב־7 באוגוסט 2026. זוהי תצפית מקומית שאינה כוללת שילוח, מס, יבוא או זמינות בישראל.', 'A visible price of USD 11.49 was recorded on the Spinneys Lebanon brand page on 7 August 2026. This is a local observation excluding shipping, tax, import and availability in Israel.', 'unbranded dark pomegranate molasses bottle silhouette beside a blank USD observation card' ),
	array( 'listing-mymoune-zaatar-200g-spinneys-20260807', 'mymoune-zaatar-200g-spinneys-20260807', 'Mymouné זעתר 200 גרם, תצפית', 'Mymouné Zaatar 200 g, Observation', 'ingredient-lebanese-zaatar-blend', 'spinneys-mymoune-2026', 'נרשם מחיר גלוי של 8.49 דולר אמריקאי בעמוד מותג של Spinneys Lebanon ב־7 באוגוסט 2026. אין להסיק ממנו מחיר שוק ממוצע או עלות נוחתת בישראל.', 'A visible price of USD 8.49 was recorded on the Spinneys Lebanon brand page on 7 August 2026. It is not a market average or an Israeli landed-cost estimate.', 'unbranded zaatar pouch silhouette beside a blank USD observation card' ),
	array( 'listing-terroirs-zaatar-70g-eu-20260807', 'terroirs-zaatar-70g-eu-20260807', 'Terroirs du Liban זעתר 70 גרם, תצפית אירופה', 'Terroirs du Liban Zaatar 70 g, EU Observation', 'ingredient-lebanese-zaatar-blend', 'terroirs-eu-store-2026', 'נרשם מחיר גלוי של 7.82 אירו בחנות האירופית ב־7 באוגוסט 2026. התצפית אינה מסלול אספקה לישראל ואינה מוכיחה מלאי עתידי.', 'A visible price of EUR 7.82 was recorded in the European store on 7 August 2026. The observation is not an Israeli supply route and does not prove future stock.', 'unbranded small zaatar jar silhouette beside a blank EUR observation card' ),
	array( 'listing-terroirs-freekeh-500g-eu-20260807', 'terroirs-freekeh-500g-eu-20260807', 'Terroirs du Liban פריקה 500 גרם, תצפית אירופה', 'Terroirs du Liban Freekeh 500 g, EU Observation', 'ingredient-lebanese-bulgur-context', 'terroirs-eu-store-2026', 'נרשם מחיר גלוי של 15.20 אירו בחנות האירופית ב־7 באוגוסט 2026. פריקה ובורגול נשמרים כמוצרים שונים ואין לקשור את המחיר ל־SKU בורגול.', 'A visible price of EUR 15.20 was recorded in the European store on 7 August 2026. Freekeh and bulgur remain different products, and the price is not assigned to a bulgur SKU.', 'unbranded freekeh pouch silhouette beside a blank EUR observation card' ),
	array( 'listing-pereg-zaatar-baladi-ils-20260807', 'pereg-zaatar-baladi-ils-20260807', 'זעתר בלאדי בישראל, תצפית השוואה', 'Zaatar Baladi in Israel, Comparison Observation', 'ingredient-lebanese-zaatar-blend', 'pereg-zaatar-2026', 'נרשם מחיר של 88 ש״ח לקילוגרם בעמוד Pereg ב־7 באוגוסט 2026. המוצר אינו מתואר כלבנוני ללא תווית מקור תומכת.', 'A price of ILS 88 per kilogram was recorded on the Pereg page on 7 August 2026. The product is not described as Lebanese without a supporting origin label.', 'unbranded Israeli retail zaatar comparison bowl beside a blank ILS per kilogram card' ),
	array( 'listing-nitzat-pomegranate-concentrate-280g-ils-20260807', 'nitzat-pomegranate-concentrate-280g-ils-20260807', 'רכז רימון אורגני 280 גרם בישראל, תצפית השוואה', 'Organic Pomegranate Concentrate 280 g in Israel, Comparison Observation', 'ingredient-pomegranate-concentrate', 'nitzat-pomegranate-2026', 'נרשם מחיר של 25.90 ש״ח למוצר 280 גרם ב־7 באוגוסט 2026. זהו רכז רימון ולא מולסת רימון, ואין למזג את הזהויות או להשוות מחיר ליחידה בלי נרמול.', 'A price of ILS 25.90 for a 280 g product was recorded on 7 August 2026. This is pomegranate concentrate rather than molasses, and the identities or prices must not be merged without normalization.', 'unbranded pomegranate concentrate jar silhouette beside a blank ILS observation card, visibly separate from a molasses sample' ),
);
$c99_lebanese_listing_prices = array(
	'listing-mymoune-pomegranate-molasses-250ml-spinneys-20260807' => array( 11.49, 'USD', 'one 250 ml bottle', 'one exact Mymoune 250 ml pomegranate molasses listing on the Spinneys Lebanon brand page', 'listed_on_brand_page', '250 ml' ),
	'listing-mymoune-zaatar-200g-spinneys-20260807' => array( 8.49, 'USD', 'one 200 g pack', 'one exact Mymoune 200 g thyme or zaatar listing on the Spinneys Lebanon brand page', 'listed_on_brand_page', '200 g' ),
	'listing-terroirs-zaatar-70g-eu-20260807' => array( 7.82, 'EUR', 'one 70 g pack', 'one exact premium zaatar 70 g listing in the Terroirs du Liban European store', 'listed_for_sale', '70 g' ),
	'listing-terroirs-freekeh-500g-eu-20260807' => array( 15.20, 'EUR', 'one 500 g pack', 'one exact freekeh 500 g listing in the Terroirs du Liban European store', 'listed_for_sale', '500 g' ),
	'listing-pereg-zaatar-baladi-ils-20260807' => array( 88.00, 'ILS', 'one kilogram price basis', 'one Israeli retail page displaying an ILS 88 per kilogram price basis for zaatar baladi', 'listed_for_sale', 'price per kilogram' ),
	'listing-nitzat-pomegranate-concentrate-280g-ils-20260807' => array( 25.90, 'ILS', 'one 280 g container', 'one exact Israeli retail listing for organic pomegranate concentrate 280 g', 'listed_for_sale', '280 g' ),
);
foreach ( $c99_lebanese_listing_rows as $row ) {
	$price = $c99_lebanese_listing_prices[ $row[0] ];
	$source_url = $c99_lebanese_sources[ $row[5] ]['url'];
	$observed_at = '2026-08-07T12:00:00+03:00';
	$measurement = array(
		'kind' => 'point',
		'low' => null,
		'high' => null,
		'value' => $price[0],
		'currency' => $price[1],
		'unit' => $price[2],
		'basis' => $price[3],
		'tax_status' => 'unknown',
		'shipping_status' => 'unknown',
		'observed_at' => $observed_at,
		'source_url' => $source_url,
		'sample_size' => 1,
		'comparability' => 'non_comparable',
		'capture_method' => 'live_retail_page_manual_review_no_snapshot',
		'snapshot_digest' => '',
		'line_items' => array(
			array(
				'name' => $row[3],
				'price' => $price[0],
				'currency' => $price[1],
				'tax_status' => 'unknown',
				'availability' => $price[4],
				'source_url' => $source_url,
				'attributes' => array(
					'net_content' => $price[5],
					'origin_claim' => 'not transferred to Complete99',
					'commercial_scope' => 'dated external benchmark only',
				),
			),
		),
	);
	$listing_spec = array(
		'id' => $row[0], 'type' => 'retail_listing', 'slug' => $row[1], 'parent_id' => $row[4],
		'name_he' => $row[2], 'name_en' => $row[3], 'region' => 'dated-market-observation', 'community' => 'not-applicable',
		'sources' => array( $row[5] ), 'summary_he' => $row[6], 'summary_en' => $row[7],
		'fact_he' => $row[6], 'fact_en' => $row[7], 'dimension' => 'economic', 'evidence' => 'market_observation', 'value_scope' => 'market_snapshot',
		'observed_at' => $observed_at,
		'measurement' => $measurement,
		'schema_type' => 'Dataset', 'pricing_state' => 'source_price_observed', 'market_scope' => 'market_specific',
		'prompt_en' => 'Private editorial price evidence composition with ' . $row[8] . ', neutral background, no copied packaging, logos or embedded text.',
	);
	if ( 'listing-nitzat-pomegranate-concentrate-280g-ils-20260807' === $row[0] ) {
		$listing_spec['parent_id'] = 'hub-lebanese-mouneh-system';
		$listing_spec['references'] = array( 'ingredient-pomegranate-concentrate' );
	}
	$c99_lebanese_rows[] = $listing_spec;
}

$c99_lebanese_entities = array();
foreach ( $c99_lebanese_rows as $spec ) {
	$c99_lebanese_entities[] = $c99_lebanese_build( $spec );
}

/* Every dated retail listing is owned by a private comparison subject. */
$c99_lebanese_entity_offsets = array();
foreach ( $c99_lebanese_entities as $offset => $lebanese_entity ) {
	$c99_lebanese_entity_offsets[ $lebanese_entity['id'] ] = $offset;
}
foreach ( $c99_lebanese_entities as $lebanese_entity ) {
	if ( 'retail_listing' !== $lebanese_entity['type'] ) {
		continue;
	}
	$parent_id = $lebanese_entity['parent_id'];
	if ( ! isset( $c99_lebanese_entity_offsets[ $parent_id ] ) ) {
		throw new RuntimeException( 'Lebanese retail listing without a private comparison owner: ' . $lebanese_entity['id'] );
	}
	$parent_offset = $c99_lebanese_entity_offsets[ $parent_id ];
	$c99_lebanese_entities[ $parent_offset ]['commerce']['business_model']['observation_entity_ids'][] = $lebanese_entity['id'];
	$c99_lebanese_entities[ $parent_offset ]['relations'][] = $c99_relation(
		'references',
		$lebanese_entity['id'],
		'הישות מפנה לתצפית מחיר מתוארכת בלבד, ללא הצעה, מלאי, מסלול יבוא או הבטחת זמינות.',
		'The entity points to a dated price observation only, without an offer, stock, import route or availability promise.',
		false,
		$lebanese_entity['facts'][0]['source_ids'],
		'market_observation'
	);
	$c99_lebanese_entities[ $parent_offset ]['relations'][ count( $c99_lebanese_entities[ $parent_offset ]['relations'] ) - 1 ]['valid_from'] = '2026-08-07';
}

/* Cross-border comparisons preserve national and regional identities. */
$c99_lebanese_cross_border_references = array(
	'ingredient-lebanese-bulgur-context' => array( 'ingredient-syrian-bulgur', 'food-heritage-kibbeh-regions' ),
	'ingredient-lebanese-kishk' => array( 'ingredient-syrian-kishk', 'food-heritage-kishk-winter' ),
	'ingredient-lebanese-pomegranate-molasses' => array( 'ingredient-syrian-pomegranate-molasses', 'food-heritage-mouneh' ),
	'ingredient-lebanese-sumac-context' => array( 'ingredient-syrian-sumac', 'food-heritage-kibbeh-regions' ),
	'ingredient-lebanese-olive-oil-context' => array( 'ingredient-syrian-olive-oil', 'food-heritage-olive-oil' ),
);
foreach ( $c99_lebanese_entities as &$lebanese_entity ) {
	if ( ! isset( $c99_lebanese_cross_border_references[ $lebanese_entity['id'] ] ) ) {
		continue;
	}
	$comparison = $c99_lebanese_cross_border_references[ $lebanese_entity['id'] ];
	$lebanese_entity['relations'][] = $c99_relation(
		'references',
		$comparison[0],
		'הקישור יוצר גבול השוואה מול הישות הסורית המקבילה ואינו מיזוג, תחליף, זהות מוצר או הכרעת מקור.',
		'The link creates a comparison boundary with the corresponding Syrian entity and is not a merge, substitute, product identity or origin verdict.',
		false,
		array( $comparison[1] ),
		'editorial_inference'
	);
	$lebanese_entity['relations'][ count( $lebanese_entity['relations'] ) - 1 ]['valid_from'] = '2026-08-07';
}
unset( $lebanese_entity );

/* Every Lebanese entity is explicitly governed by the current trade boundary. */
foreach ( $c99_lebanese_entities as &$lebanese_entity ) {
	if ( 'compliance-lebanon-trade-israel-2026' !== $lebanese_entity['id'] ) {
		$lebanese_entity['relations'][] = $c99_relation(
			'references',
			'compliance-lebanon-trade-israel-2026',
			'הישות כפופה לגבול המסחר המתועד ואינה יוצרת ספק, הצעה, מלאי או מסלול רכישה.',
			'The entity is governed by the documented trade boundary and creates no supplier, offer, stock or purchasing route.',
			false,
			array( 'israel-enemy-states-trade-2026' ),
			'regulatory_standard'
		);
		$lebanese_entity['relations'][ count( $lebanese_entity['relations'] ) - 1 ]['valid_from'] = '2026-08-07';
	}
}
unset( $lebanese_entity );

$c99_lebanese_counts = array_count_values( array_column( $c99_lebanese_entities, 'type' ) );

return array(
	'schema' => 'complete99-lebanese-foundations-module/v1',
	'version' => 'culinary-science-2026.08.07.v14',
	'sources' => $c99_lebanese_sources,
	'entities' => $c99_lebanese_entities,
	'private_entity_ids' => array_column( $c99_lebanese_entities, 'id' ),
	'cluster_root_id' => 'cuisine-lebanese-regional',
	'cluster_id' => 'cluster-lebanese-regional-cuisine',
	'counts' => array(
		'by_type' => $c99_lebanese_counts,
		'total_entities' => count( $c99_lebanese_entities ),
	),
);
