<?php
/**
 * Private Japanese premium-market research tranche.
 *
 * The module adds source-bounded knowledge records and exact retail listing
 * observations. It does not create public routes, supplier relationships,
 * endorsements, WooCommerce offers or SKU-level laboratory claims.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tranche_sources = array(
	'toyosu-market-official-2026' => array(
		'type'         => 'official_government',
		'publisher'    => 'Tokyo Metropolitan Government',
		'title'        => 'Toyosu Market information',
		'url'          => 'https://www.shijou.metro.tokyo.lg.jp/info/0/toyosu',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'toyosu-market-overview-2026' => array(
		'type'         => 'official_government',
		'publisher'    => 'Tokyo Metropolitan Government',
		'title'        => 'Overview of Toyosu Market',
		'url'          => 'https://www.shijou.metro.tokyo.lg.jp/documents/d/shijou/r8-overview-of-toyosu-market_japanese-pdf-1',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'kappabashi-official-en-2026' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Tokyo Kappabashi Dougu Street Promotion Union',
		'title'        => 'Kappabashi Dougu Street',
		'url'          => 'https://www.kappabashi.or.jp/en/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'jca-official-en-2026' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Japanese Culinary Academy',
		'title'        => 'Japanese Culinary Academy',
		'url'          => 'https://culinary-academy.jp/english',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'jca-corpus-2026' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Japanese Culinary Academy',
		'title'        => 'Japanese cuisine terminology corpus',
		'url'          => 'https://culinary-academy.jp/corpus',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'jca-taizen-digital-book-2026' => array(
		'type'         => 'official_organization',
		'publisher'    => 'Japanese Culinary Academy',
		'title'        => 'Complete Japanese Cuisine digital book',
		'url'          => 'https://culinary-academy.jp/taizen_digital_book',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'ginza-kyubey-official-2026' => array(
		'type'         => 'official_business',
		'publisher'    => 'Ginza Kyubey',
		'title'        => 'Ginza Kyubey official website',
		'url'          => 'https://www.kyubey.jp/en/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'maff-kombujime-2026' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'Kombujime regional cuisine reference',
		'url'          => 'https://www.maff.go.jp/e/policies/market/k_ryouri/search_menu/3639/index.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'kombujime-science-2025' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'ScienceDirect',
		'title'        => 'Peer-reviewed kombujime process research',
		'url'          => 'https://www.sciencedirect.com/science/article/pii/S1878450X25000253',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'maff-futomaki-2026' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'Futomaki-zushi regional cuisine reference',
		'url'          => 'https://www.maff.go.jp/e/policies/market/japan-cuisine/japan/2/index.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'maff-kaiseki-hassun-english' => array(
		'type'         => 'official_government',
		'publisher'    => 'Ministry of Agriculture, Forestry and Fisheries of Japan',
		'title'        => 'Japanese cuisine and kaiseki English reference',
		'url'          => 'https://www.maff.go.jp/j/shokusan/gaisyoku/pamphlet/pdf/14-25_english.pdf',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'maruyama-kontobi-5-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Maruyama Nori',
		'title'        => 'Gokujo Kontobi nori, five sheets',
		'url'          => 'https://www.maruyamanori.com/c/kontobi_n/200660-C157',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'nori-category-science-2024' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'PubMed',
		'title'        => 'Peer-reviewed nori category research',
		'url'          => 'https://pubmed.ncbi.nlm.nih.gov/39053276/',
		'published_at' => '2024-07-15',
		'retrieved_at' => '2026-08-06',
	),
	'tajima-red-sushi-vinegar-360ml-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Tajima Jozo premium seasoned red vinegar for sushi, 360 ml',
		'url'          => 'https://japanesetaste.jp/products/tajima-jozo-premium-akazu-aged-red-vinegar-for-sushi-360ml',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'tajima-seasoned-vinegar-producer-2026' => array(
		'type'         => 'official_business',
		'publisher'    => 'Tajima Jozo',
		'title'        => 'Seasoned vinegar category',
		'url'          => 'https://tajimajozo.co.jp/en/category/seasoned_vinegar/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'minamigura-tamari-200ml-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Minamigura Gin Warabeuta tamari, 200 ml',
		'url'          => 'https://japanesetaste.com/products/minamigura-tamari-shoyu-gluten-free-japanese-soy-sauce-gin-warabeuta-200ml',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'minamigura-method-2026' => array(
		'type'         => 'official_business',
		'publisher'    => 'Minamigura',
		'title'        => 'Gin Warabeuta method and product reference',
		'url'          => 'https://minamigura.com/gin-warabeuta/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'tamari-category-science-2020' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'PubMed Central',
		'title'        => 'Peer-reviewed soy sauce category research',
		'url'          => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC7581291/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'sugimoto-shiitake-70g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Sugimoto organic Japanese dried shiitake, 70 g',
		'url'          => 'https://int.japanesetaste.com/products/sugimoto-organic-japanese-dried-shiitake-mushrooms-70g',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'shiitake-category-science-2024' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'PubMed',
		'title'        => 'Peer-reviewed dried shiitake category research',
		'url'          => 'https://pubmed.ncbi.nlm.nih.gov/39517140/',
		'published_at' => '2024-10-23',
		'retrieved_at' => '2026-08-06',
	),
	'yubaya-kyoto-yuba-home-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Yubaya',
		'title'        => 'Yubaya current shop price reference',
		'url'          => 'https://www.yubaya.co.jp/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'yubaya-kyoto-yuba-item-2026' => array(
		'type'         => 'official_business',
		'publisher'    => 'Yubaya',
		'title'        => 'Kyoto dried yuba item reference',
		'url'          => 'https://www.yubaya.co.jp/products/item30.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'ohsawa-kudzu-150g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Ohsawa organic kudzu starch, 150 g',
		'url'          => 'https://japanesetaste.com/products/ohsawa-organic-kudzu-starch-block-type-thickening-powder-150g',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'kudzu-category-science-2026' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'PubMed',
		'title'        => 'Peer-reviewed kudzu category research',
		'url'          => 'https://pubmed.ncbi.nlm.nih.gov/41519333/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'yawataya-sansho-12g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Japanese Taste',
		'title'        => 'Yawataya Isogoro sansho pepper, 12 g',
		'url'          => 'https://japanesetaste.com/products/yawataya-isogoro-sansho-pepper-japanese-pepper-12g',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'sansho-category-science-2023' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Foods',
		'title'        => 'Peer-reviewed sansho category research',
		'url'          => 'https://www.mdpi.com/2304-8158/12/19/3589',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'marukyu-tenju-matcha-20g-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Marukyu Koyamaen',
		'title'        => 'Tenju matcha, 20 g',
		'url'          => 'https://www.marukyu-koyamaen.co.jp/motoan-shop/products/1111020c1/',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'matcha-category-science-2022-a' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'PubMed',
		'title'        => 'Peer-reviewed matcha category research, study one',
		'url'          => 'https://pubmed.ncbi.nlm.nih.gov/36234707/',
		'published_at' => '2022-09-20',
		'retrieved_at' => '2026-08-06',
	),
	'matcha-category-science-2022-b' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'PubMed',
		'title'        => 'Peer-reviewed matcha category research, study two',
		'url'          => 'https://pubmed.ncbi.nlm.nih.gov/35624753/',
		'published_at' => '2022-04-30',
		'retrieved_at' => '2026-08-06',
	),
	'yamaco-makisu-27cm-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Mujo Store',
		'title'        => 'Yamaco bamboo sushi mat, 27 cm',
		'url'          => 'https://www.mujostore.com/products/bamboo-sushi-mat',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'sakai-takayuki-ginsan-yanagiba-270mm-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Knives and Stones Australia',
		'title'        => 'Sakai Takayuki Ginsan yanagiba, 270 mm',
		'url'          => 'https://www.knivesandstones.com.au/products/sakai-takayuki-ginsan-yanagiba-270mm',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'ginsan-steel-index-2026' => array(
		'type'         => 'official_business',
		'publisher'    => 'Knives and Stones Australia',
		'title'        => 'Ginsan Silver 3 steel index',
		'url'          => 'https://www.knivesandstones.com.au/pages/steel/ginsan-silver-3',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'nagatanien-kamado-san-3cup-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Nagatanien Igamono',
		'title'        => 'Kamado-san three-cup donabe',
		'url'          => 'https://store.igamono.jp/?pid=85075826',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'nagatanien-kamado-san-yahoo-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Nagatanien Igamono Yahoo store',
		'title'        => 'Kamado-san three-cup current availability reference',
		'url'          => 'https://store.shopping.yahoo.co.jp/igamono-nagatanien/1196168994.html',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'kubo-komakichi-kazuho-chasen-listing-2026' => array(
		'type'         => 'official_market_listing',
		'publisher'    => 'Tea Osaka-ya',
		'title'        => 'Kubo Komakichi Kazuho chasen',
		'url'          => 'https://teaosakaya.theshop.jp/items/65610450',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
	'chasen-foam-science-2012' => array(
		'type'         => 'peer_reviewed_paper',
		'publisher'    => 'Journal of the Japanese Society for Food Science and Technology',
		'title'        => 'Matcha foam preparation research',
		'url'          => 'https://www.jstage.jst.go.jp/article/nskkk/59/3/59_109/_article',
		'published_at' => '',
		'retrieved_at' => '2026-08-06',
	),
);

$tranche_entities = array();

$tranche_entities[] = $c99_entity( array(
	'id' => 'supplier-district-kappabashi', 'type' => 'supplier', 'slug' => 'kappabashi-supplier-district', 'parent_id' => 'hub-japanese-sourcing',
	'name' => $c99_text( 'רובע הספקים קפאבשי', 'Kappabashi supplier district' ),
	'summary' => $c99_text( 'ישות מחקר פרטית המתארת את קפאבשי כרשת מסחרית של חנויות ציוד ומומחיות. היא אינה ספק מאושר, אינה רשימת ספקים פעילה ואינה מעידה על קשר מסחרי עם Complete99.', 'A private research entity describing Kappabashi as a commercial network of specialist equipment shops. It is not an approved supplier, an active supplier list or evidence of a Complete99 commercial relationship.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'source_reviewed',
	'seo_group' => 'references', 'primary_intent' => $c99_text( 'למפות את קפאבשי כרשת מחקר לאספקת ציוד', 'Map Kappabashi as an equipment-sourcing research network' ), 'primary_keyword' => $c99_text( 'רשת ספקי ציוד קפאבשי', 'Kappabashi equipment supplier district' ),
	'secondary_keywords' => array( 'he' => array( 'חנויות ציוד בטוקיו' ), 'en' => array( 'Tokyo kitchen equipment district' ) ), 'schema_type' => 'Organization',
	'facts' => array( $c99_fact( 'fact-kappabashi-supplier-district-boundary', 'institutional', 'האתר הרשמי מתאר אזור מסחרי לציוד וכלי מטבח. המיפוי משמש לאיתור מקורות אפשריים בלבד ואינו יוצר אישור או המלצה על חנות מסוימת.', 'The official site describes a commercial district for kitchen equipment and tableware. The map supports source discovery only and does not approve or endorse any particular shop.', 'official_source', 'entity', array( 'kappabashi-official-en-2026' ), false ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'not_applicable', 'זהו פרופיל רשת מסחרית ולא בדיקת מוצר.', 'This is a commercial-network profile, not a product test.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'היסטוריית הרובע תדרוש מקור ייעודי נוסף.', 'District history requires an additional dedicated source.' ),
		'institutional' => $c99_profile( 'source_backed', 'תפקיד הרובע מבוסס על האתר הרשמי ונשמר ללא טענת קשר.', 'The district role is grounded in its official site and stored without an affiliation claim.', array( 'fact-kappabashi-supplier-district-boundary' ) ),
		'economic' => $c99_profile( 'pending_evidence', 'מחיר, MOQ, שילוח ותנאי ספק דורשים הצעה פרטנית ומתוארכת.', 'Price, MOQ, shipping and supplier terms require an exact dated offer.' ),
		'structural' => $c99_profile( 'pending_evidence', 'הישות מפרידה מחקר ספקים מפרופיל השוק הציבורי.', 'The entity separates supplier research from the public market profile.' ),
	) ),
	'categories' => array( 'culinary-network', 'sourcing', 'japan', 'equipment' ), 'attributes' => array( 'pa_market' => array( 'tokyo' ) ), 'tags' => array( 'kappabashi', 'supplier-research', 'kitchen-equipment' ),
	'relations' => array( $c99_relation( 'references', 'market-kappabashi-dougu', 'פרופיל הספקים נשען על אותה גאוגרפיה אך נשמר כעדשת מחקר פרטית.', 'The supplier profile shares the geography but remains a private research lens.', false, array( 'kappabashi-official-en-2026' ), 'official_source' ) ),
	'commerce_state' => 'supplier_onboarding', 'pricing_state' => 'research_required',
	'prompt_en' => 'Rights-cleared documentary overview of Kappabashi kitchenware district from a public sidewalk, diverse specialist equipment storefronts, no single shop endorsement, no copied logos as the main subject, no text overlays.',
) );

$tranche_entities[] = $c99_entity( array(
	'id' => 'restaurant-ginza-kyubey', 'type' => 'restaurant', 'slug' => 'ginza-kyubey', 'parent_id' => 'hub-japanese-restaurants',
	'name' => $c99_text( 'גינזה קיוביי', 'Ginza Kyubey' ),
	'summary' => $c99_text( 'פרופיל ייחוס פרטי למסעדת Ginza Kyubey על בסיס האתר הרשמי שלה. הישות משמשת לחקר מוסדות סושי ואינה מעידה על שותפות, אישור, אספקה או חיקוי של מנות חתימה.', 'A private reference profile for Ginza Kyubey grounded in its official website. The entity supports sushi-institution research and does not imply partnership, endorsement, supply or imitation of signature dishes.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'source_reviewed',
	'seo_group' => 'references', 'primary_intent' => $c99_text( 'לתעד את Ginza Kyubey כנקודת ייחוס מוסדית', 'Document Ginza Kyubey as an institutional reference' ), 'primary_keyword' => $c99_text( 'גינזה קיוביי פרופיל ייחוס', 'Ginza Kyubey reference profile' ),
	'secondary_keywords' => array( 'he' => array( 'מסעדת סושי גינזה' ), 'en' => array( 'Ginza sushi institution' ) ), 'schema_type' => 'Restaurant',
	'facts' => array( $c99_fact( 'fact-ginza-kyubey-official-identity', 'institutional', 'האתר הרשמי תומך בזהות המסעדה ובפרטי הנוכחות שלה. אין להסיק ממנו דירוג, מחיר, שיטת עבודה או קשר ל-Complete99 שלא נאמרו במקור.', 'The official site supports the restaurant identity and its own presence details. It does not support an unstated rating, price, method or Complete99 relationship.', 'official_source', 'entity', array( 'ginza-kyubey-official-2026' ), false ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'not_applicable', 'זהות מסעדה אינה מדידת מזון.', 'Restaurant identity is not a food measurement.' ),
		'cultural' => $c99_profile( 'pending_evidence', 'השפעה היסטורית דורשת מקורות עצמאיים.', 'Historical influence requires independent sources.' ),
		'institutional' => $c99_profile( 'source_backed', 'הזהות נשמרת מן המקור הרשמי בלבד.', 'Identity is retained from the official source only.', array( 'fact-ginza-kyubey-official-identity' ) ),
		'economic' => $c99_profile( 'pending_evidence', 'אין להסיק תמחור או רווחיות מפרופיל המסעדה.', 'Pricing or profitability is not inferred from the restaurant profile.' ),
		'structural' => $c99_profile( 'pending_evidence', 'המסעדה היא ישות ייחוס נפרדת מן המנות והטכניקות.', 'The restaurant is a reference entity separate from dishes and techniques.' ),
	) ),
	'categories' => array( 'culinary-network', 'restaurants', 'japan', 'sushi' ), 'attributes' => array( 'pa_origin' => array( 'tokyo-japan' ) ), 'tags' => array( 'ginza-kyubey', 'sushi-institution', 'reference-only' ),
	'relations' => array( $c99_relation( 'references', 'dish-edomae-nigiri', 'הקישור תומך במסלול לימוד סושי ואינו מייחס למסעדה מתכון או שיטה שלא תועדו.', 'The link supports a sushi learning path and does not attribute an undocumented recipe or method to the restaurant.', false, array( 'ginza-kyubey-official-2026' ), 'editorial_inference' ) ),
	'prompt_en' => 'Rights-cleared documentary photograph supplied or approved by the actual restaurant, exterior or counter context only, dated rights record, no generated signature dish, no false award marks, no logos recreated.',
) );

$tranche_entities[] = $c99_entity( array(
	'id' => 'technique-edomae-shari-control', 'type' => 'technique', 'slug' => 'edomae-shari-control', 'parent_id' => 'hub-japanese-techniques',
	'name' => $c99_text( 'בקרת שארי בסגנון אדומאה', 'Edomae shari control' ),
	'summary' => $c99_text( 'ישות תהליך פרטית המפרידה זהות אורז, יחס תיבול, קירור, זמן, טמפרטורה ובקרת בטיחות. ערך pH מהנחיית FDA נשמר כהקשר רגולטורי אמריקאי ואינו מדידת אצווה או דין ישראלי אוטומטי.', 'A private process entity separating rice identity, seasoning ratio, cooling, time, temperature and safety control. A pH value from FDA guidance is retained as United States regulatory context, not a batch measurement or automatically applicable Israeli law.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'research_draft', 'culinary_test_status' => 'pending',
	'seo_group' => 'knowledge', 'primary_intent' => $c99_text( 'להבין את משתני הבקרה בשארי אדומאה', 'Understand Edomae shari control variables' ), 'primary_keyword' => $c99_text( 'בקרת שארי אדומאה', 'Edomae shari control' ),
	'secondary_keywords' => array( 'he' => array( 'אורז סושי חומציות ובטיחות' ), 'en' => array( 'sushi rice acidification control' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-edomae-shari-fda-context-boundary', 'scientific', 'הנחיית FDA משתמשת ב-pH שאינו עולה על 4.2 בהקשר מסוים של אורז סושי מוחמץ. זהו גבול בהנחיה האמריקאית, לא ערך של מתכון, מוצר או אצווה ולא קביעה אוטומטית לדין בישראל.', 'FDA guidance uses pH not exceeding 4.2 in a specific acidified sushi-rice context. This is a United States guidance boundary, not a recipe, product or lot value and not an automatic statement of Israeli law.', 'regulatory_standard', 'technique_context', array( 'fda-sushi-rice-2022' ), false ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'הסף מתועד כהקשר רגולטורי בלבד וכל אימות מקומי ואצוותי נשאר שער נפרד.', 'The threshold is documented only as regulatory context, with local and lot verification retained as separate gates.', array( 'fact-edomae-shari-fda-context-boundary' ) ),
		'cultural' => $c99_profile( 'pending_evidence', 'סגנון אדומאה דורש מקורות היסטוריים נפרדים.', 'Edomae style requires separate historical sources.' ),
		'institutional' => $c99_profile( 'pending_evidence', 'בקרת מטבח והדרכה יישמרו בפרטי התפעול המאומתים.', 'Kitchen control and training remain in verified private operations.' ),
		'economic' => $c99_profile( 'pending_evidence', 'תפוקה, פחת וזמן עבודה דורשים מבחן מטבח.', 'Yield, waste and labor time require a kitchen test.' ),
		'structural' => $c99_profile( 'pending_evidence', 'הטכניקה מקשרת שארי, ניגירי, ציוד אורז ובקרת בטיחות בלי לטעון למתכון מאושר.', 'The technique links shari, nigiri, rice tools and safety control without claiming an approved recipe.' ),
	) ),
	'categories' => array( 'knowledge', 'techniques', 'sushi', 'rice-control' ), 'attributes' => array( 'pa_equipment_required' => array( 'rice-cooker', 'hangiri', 'calibrated-ph-meter' ) ), 'tags' => array( 'edomae', 'shari', 'rice-control', 'regulatory-context' ),
	'relations' => array(
		$c99_relation( 'references', 'preparation-sushi-shari', 'הטכניקה מפרקת את משתני הבקרה של הכנת השארי.', 'The technique decomposes the control variables of shari preparation.', false, array( 'fda-sushi-rice-2022' ), 'regulatory_standard' ),
		$c99_relation( 'used_in', 'dish-edomae-nigiri', 'שארי הוא רכיב מרכזי בניגירי, בלי לטעון למפרט מסעדה.', 'Shari is central to nigiri without asserting a restaurant specification.', false, array( 'maff-edomae' ), 'official_source' ),
	),
	'compliance' => array( $c99_compliance( 'jurisdiction-and-batch-ph-review', 'ערך pH 4.2 הוא הקשר FDA בלבד. לפני שימוש תפעולי נדרשים אימות דין ישראלי, תהליך מאושר, מדידה מכוילת ותיעוד אצווה.', 'The pH 4.2 value is FDA context only. Operational use requires Israeli-law review, an approved process, calibrated measurement and lot records.', array( 'fda-sushi-rice-2022' ), false ) ),
	'prompt_en' => 'Private culinary process board showing cooked sushi rice, measured seasoning, cooling fan, clean hangiri and a calibrated pH meter as separate control points, no displayed reading, no restaurant branding, no claim of approval.',
) );

$tranche_entities[] = $c99_entity( array(
	'id' => 'technique-kombujime', 'type' => 'technique', 'slug' => 'kombujime', 'parent_id' => 'hub-japanese-techniques',
	'name' => $c99_text( 'טכניקת קומבוג׳ימה', 'Kombujime technique' ),
	'summary' => $c99_text( 'קומבוג׳ימה היא טכניקת הכנה שבה חומר גלם נעטף או נלחץ בין שכבות קומבו. הישות מפרידה תיאור מסורתי, מנגנון מחקרי, סוג דג, זמן, טמפרטורה ובטיחות, ואינה ממציאה ריכוזי טעם למנה או לאצווה.', 'Kombujime is a preparation technique in which an ingredient is wrapped or pressed between kombu. The entity separates traditional description, research mechanism, fish type, time, temperature and safety, and does not invent flavor concentrations for a dish or lot.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'research_draft', 'culinary_test_status' => 'pending',
	'seo_group' => 'knowledge', 'primary_intent' => $c99_text( 'להבין את טכניקת קומבוג׳ימה', 'Understand the kombujime technique' ), 'primary_keyword' => $c99_text( 'טכניקת קומבוג׳ימה', 'kombujime technique' ),
	'secondary_keywords' => array( 'he' => array( 'כבישת דג בקומבו' ), 'en' => array( 'kombu cured fish method' ) ), 'schema_type' => 'Article',
	'facts' => array(
		$c99_fact( 'fact-kombujime-maff-identity', 'cultural', 'MAFF מתארת קומבוג׳ימה כמנה או שיטה אזורית המבוססת על שילוב דג וקומבו. פרטי ביצוע ביתיים או מסחריים דורשים מפרט בדוק.', 'MAFF describes kombujime as a regional preparation based on combining fish and kombu. Home or commercial execution details require a tested specification.', 'official_source', 'technique_context', array( 'maff-kombujime-2026' ) ),
		$c99_fact( 'fact-kombujime-science-category-boundary', 'scientific', 'המחקר מספק הקשר לתהליך בקטגוריית קומבוג׳ימה. הוא אינו מספק ערך מולקולרי, pH או תוצאת בטיחות ל-SKU, לדג או לאצווה של Complete99.', 'The research provides process context for the kombujime category. It does not provide a molecular value, pH or safety result for a Complete99 SKU, fish or lot.', 'peer_reviewed_context', 'technique_context', array( 'kombujime-science-2025' ), false ),
	),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'source_backed', 'המחקר נשמר ברמת קטגוריית התהליך ולא כבדיקת מוצר.', 'Research remains at process-category scope and not as a product test.', array( 'fact-kombujime-science-category-boundary' ) ),
		'cultural' => $c99_profile( 'source_backed', 'זהות השיטה מבוססת על מקור MAFF.', 'Technique identity is grounded in the MAFF source.', array( 'fact-kombujime-maff-identity' ) ),
		'institutional' => $c99_profile( 'pending_evidence', 'ייחוס לשף או מסעדה דורש מקור ישיר.', 'Chef or restaurant attribution requires a direct source.' ),
		'economic' => $c99_profile( 'pending_evidence', 'תפוקה, זמן, פחת וחיי מדף דורשים מבחן מטבח.', 'Yield, time, waste and shelf life require a kitchen test.' ),
		'structural' => $c99_profile( 'pending_evidence', 'הטכניקה מחברת קומבו, דג, בטיחות ומנות רלוונטיות.', 'The technique connects kombu, fish, safety and relevant dishes.' ),
	) ),
	'categories' => array( 'knowledge', 'techniques', 'japanese', 'kombu' ), 'attributes' => array( 'pa_processing_method' => array( 'kombujime' ), 'pa_allergens' => array( 'fish' ) ), 'tags' => array( 'kombujime', 'kombu', 'fish', 'technique' ),
	'relations' => array( $c99_relation( 'requires', 'ingredient-kombu', 'קומבו הוא חומר גלם מרכזי בשיטה.', 'Kombu is a central ingredient in the technique.', true, array( 'maff-kombujime-2026' ), 'official_source' ) ),
	'compliance' => array( $c99_compliance( 'fish-time-temperature-review', 'נדרשים אימות מין הדג, שרשרת קירור, אלרגנים, זמן, טמפרטורה וחיי מדף לפני הוראת עבודה.', 'Fish species, cold chain, allergens, time, temperature and shelf life require verification before an operating instruction.', array( 'fda-fish-hazards-2022' ), false ) ),
	'prompt_en' => 'Commercial culinary studio process sequence of an unbranded fish fillet placed between clean kombu sheets, chilled stainless work surface, precise texture, no raw-fish safety claim, no text, logos or invented molecular readouts.',
) );

$tranche_entities[] = $c99_entity( array(
	'id' => 'dish-futomaki-sushi', 'type' => 'dish', 'slug' => 'futomaki-sushi', 'parent_id' => 'hub-japanese-dishes',
	'name' => $c99_text( 'פוטומאקי סושי', 'Futomaki sushi' ),
	'summary' => $c99_text( 'ישות מנה פרטית לפוטומאקי, גליל סושי עבה שניתן למפות לפי אורז, נורי, מילויים, חיתוך והקשר תרבותי. אין כאן מתכון מאושר, ערכי תזונה, הבטחת כשרות או זמינות מסחרית.', 'A private dish entity for futomaki, a thick sushi roll that can be mapped by rice, nori, fillings, cutting and cultural context. It is not an approved recipe, nutrition profile, kosher claim or commercial availability statement.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'research_draft', 'culinary_test_status' => 'pending',
	'seo_group' => 'dishes', 'primary_intent' => $c99_text( 'להכיר את מבנה הפוטומאקי', 'Understand the structure of futomaki sushi' ), 'primary_keyword' => $c99_text( 'פוטומאקי סושי', 'futomaki sushi' ),
	'secondary_keywords' => array( 'he' => array( 'גליל סושי עבה' ), 'en' => array( 'thick sushi roll' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-futomaki-maff-identity', 'cultural', 'MAFF מציגה פוטומאקי בהקשר של מטבח אזורי יפני. מקור זה תומך בזהות ובהקשר, לא במתכון Complete99 בדוק.', 'MAFF presents futomaki in the context of Japanese regional cuisine. The source supports identity and context, not a tested Complete99 recipe.', 'official_source', 'entity', array( 'maff-futomaki-2026' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'מדע האורז, הנורי והמרקם נשמר בישויות החומרים והטכניקות.', 'Rice, nori and texture science remain in ingredient and technique entities.' ),
		'cultural' => $c99_profile( 'source_backed', 'הזהות וההקשר מבוססים על MAFF.', 'Identity and context are grounded in MAFF.', array( 'fact-futomaki-maff-identity' ) ),
		'institutional' => $c99_profile( 'pending_evidence', 'ייחוס לשף או מסעדה דורש מקור נפרד.', 'Chef or restaurant attribution requires a separate source.' ),
		'economic' => $c99_profile( 'pending_evidence', 'עלות מנה, תפוקה ותמחור דורשים BOM ומבחן מטבח.', 'Dish cost, yield and price require a BOM and kitchen test.' ),
		'structural' => $c99_profile( 'pending_evidence', 'המנה מחברת נורי, שארי, מחצלת גלגול וסינון אלרגנים.', 'The dish connects nori, shari, a rolling mat and allergen filtering.' ),
	) ),
	'categories' => array( 'dishes', 'japanese', 'sushi', 'rolls' ), 'attributes' => array( 'pa_equipment_required' => array( 'makisu', 'sharp-knife' ) ), 'tags' => array( 'futomaki', 'sushi', 'nori', 'shari' ),
	'relations' => array(
		$c99_relation( 'requires', 'ingredient-yakinori', 'נורי הוא מעטפת מרכזית במבנה הפוטומאקי.', 'Nori is a central wrapper in futomaki structure.', true, array( 'maff-futomaki-2026' ), 'official_source' ),
		$c99_relation( 'references', 'preparation-sushi-shari', 'השארי נשמר כבעל ידע נפרד.', 'Shari remains a separate knowledge owner.', false, array( 'maff-futomaki-2026' ), 'official_source' ),
	),
	'prompt_en' => 'Commercial culinary studio photograph of a precisely cut unbranded futomaki roll showing balanced rice, nori and colorful vegetable fillings, neutral cedar and stone styling, no restaurant imitation, text or logos.',
) );

$tranche_entities[] = $c99_entity( array(
	'id' => 'dish-kaiseki-hassun', 'type' => 'dish', 'slug' => 'kaiseki-hassun', 'parent_id' => 'hub-japanese-dishes',
	'name' => $c99_text( 'האסון בקאיסקי', 'Kaiseki hassun' ),
	'summary' => $c99_text( 'ישות מנה פרטית המתארת האסון כשלב עונתי במבנה קאיסקי, ולא כמגש קבוע או מתכון אחיד. כל רכיב, עונה, מקור חומר ואלרגן דורשים מפרט עצמאי לפני פרסום או מכירה.', 'A private dish entity describing hassun as a seasonal course within kaiseki rather than a fixed platter or uniform recipe. Every component, season, ingredient source and allergen requires its own specification before publication or sale.' ),
	'surface_class' => 'editorial_draft', 'index_policy' => 'noindex_private', 'review_status' => 'research_draft', 'culinary_test_status' => 'pending',
	'seo_group' => 'dishes', 'primary_intent' => $c99_text( 'להבין את תפקיד ההאסון בקאיסקי', 'Understand the role of hassun in kaiseki' ), 'primary_keyword' => $c99_text( 'האסון בקאיסקי', 'kaiseki hassun' ),
	'secondary_keywords' => array( 'he' => array( 'מנה עונתית בקאיסקי' ), 'en' => array( 'seasonal kaiseki course' ) ), 'schema_type' => 'Article',
	'facts' => array( $c99_fact( 'fact-kaiseki-hassun-maff-context', 'cultural', 'מקור MAFF מציג את מבנה הקאיסקי וההקשר העונתי. הישות אינה הופכת את ההדגמה למתכון, מפרט הגשה או טענה על מסעדה מסוימת.', 'The MAFF source presents kaiseki structure and seasonal context. The entity does not turn that illustration into a recipe, service specification or claim about a particular restaurant.', 'official_source', 'entity', array( 'maff-kaiseki-hassun-english' ) ) ),
	'profiles' => $c99_profiles( array(
		'scientific' => $c99_profile( 'pending_evidence', 'כל רכיב יתחבר לישות מדעית משלו.', 'Each component will connect to its own science entity.' ),
		'cultural' => $c99_profile( 'source_backed', 'התפקיד העונתי נשמר בגבולות מקור MAFF.', 'The seasonal role remains within the MAFF source boundary.', array( 'fact-kaiseki-hassun-maff-context' ) ),
		'institutional' => $c99_profile( 'pending_evidence', 'שיטות בית ספר או מסעדה דורשות מקור והרשאה.', 'School or restaurant methods require a source and permission.' ),
		'economic' => $c99_profile( 'pending_evidence', 'מודל קפסולה עונתית דורש BOM, זמינות ומחיר רכיבים.', 'A seasonal capsule model requires a BOM, component availability and pricing.' ),
		'structural' => $c99_profile( 'pending_evidence', 'הישות יכולה לקשר עונה, טכניקות, חומרי גלם ומארז מסחרי מוחזק.', 'The entity can connect season, techniques, ingredients and a held commercial capsule.' ),
	) ),
	'categories' => array( 'dishes', 'japanese', 'kaiseki', 'seasonal-course' ), 'attributes' => array( 'pa_origin' => array( 'japan' ) ), 'tags' => array( 'kaiseki', 'hassun', 'seasonality', 'course-structure' ),
	'relations' => array( $c99_relation( 'part_of', 'cuisine-japanese-washoku', 'האסון נשמר בתוך הקשר המטבח היפני ולא כתבנית אוניברסלית.', 'Hassun remains within Japanese cuisine context rather than a universal template.', true, array( 'maff-kaiseki-hassun-english' ), 'official_source' ) ),
	'prompt_en' => 'Museum-grade culinary studio photograph of a seasonal hassun-inspired arrangement with several restrained small preparations on natural Japanese serving ware, no restaurant signature imitation, no logos, no text, exact food textures.',
) );

$retail_specs = array(
	array(
		'id' => 'listing-maruyama-gokujo-kontobi-nori-5-sheets-20260806', 'slug' => 'maruyama-gokujo-kontobi-nori-5-sheets-20260806', 'subject_id' => 'ingredient-yakinori',
		'name' => $c99_text( 'Maruyama Gokujo Kontobi נורי, 5 דפים, תצפית 6.8.2026', 'Maruyama Gokujo Kontobi nori, 5 sheets, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Kontobi נורי 5 דפים', 'Kontobi nori 5 sheets price observation' ),
		'price' => 1350, 'currency' => 'JPY', 'unit' => 'one five-sheet pack', 'basis' => 'one exact maker-shop five-sheet listing', 'tax_status' => 'included', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'listed_for_sale',
		'source_id' => 'maruyama-kontobi-5-listing-2026', 'source_url' => 'https://www.maruyamanori.com/c/kontobi_n/200660-C157', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
		'listing_attributes' => array( 'net_content' => 'five sheets', 'product_identity' => 'Gokujo Kontobi listing', 'sku_claim_scope' => 'listing identity and price only' ),
		'statement' => $c99_text( 'נצפו 1,350 ין כולל מס לאריזת חמישה דפי Gokujo Kontobi. משלוח ומפרט אצווה דורשים אימות.', 'JPY 1,350 including tax was observed for a five-sheet Gokujo Kontobi pack. Shipping and lot specification require verification.' ),
		'cross_sell_ids' => array( 'dish-futomaki-sushi', 'preparation-sushi-shari' ), 'prompt_en' => 'Private editorial cutout of five unbranded premium toasted nori sheets with crisp edges and deep green-black sheen, neutral humidity-controlled setup, no copied packaging, logos or text.',
		'category_source_ids' => array( 'nori-category-science-2024' ),
	),
	array(
		'id' => 'listing-tajima-red-sushi-vinegar-360ml-20260806', 'slug' => 'tajima-red-sushi-vinegar-360ml-20260806', 'subject_id' => 'hub-japanese-ingredients',
		'name' => $c99_text( 'Tajima תיבול חומץ אדום לסושי 360 מ״ל, תצפית 6.8.2026', 'Tajima seasoned red sushi vinegar 360 ml, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר חומץ סושי אדום 360 מ״ל', 'seasoned red sushi vinegar 360 ml price observation' ),
		'price' => 761, 'currency' => 'JPY', 'unit' => 'one 360 ml bottle', 'basis' => 'one exact listing for seasoned grain vinegar; not treated as pure akazu', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'non_comparable', 'availability' => 'listed_for_sale',
		'source_id' => 'tajima-red-sushi-vinegar-360ml-listing-2026', 'source_url' => 'https://japanesetaste.jp/products/tajima-jozo-premium-akazu-aged-red-vinegar-for-sushi-360ml', 'market' => 'japan-online-retail', 'market_scope' => 'japan_source_market',
		'listing_attributes' => array( 'net_content' => '360 ml', 'category_boundary' => 'seasoned grain vinegar, not pure akazu', 'identity_state' => 'JAN and formula verification required' ),
		'statement' => $c99_text( 'נצפו 761 ין לבקבוק 360 מ״ל. המוצר נשמר כתיבול חומץ דגנים לסושי ולא כחומץ Akazu טהור.', 'JPY 761 was observed for a 360 ml bottle. The item is stored as seasoned grain vinegar for sushi, not pure Akazu.' ),
		'cross_sell_ids' => array( 'preparation-sushi-shari', 'dish-futomaki-sushi' ), 'prompt_en' => 'Private editorial silhouette of an unbranded 360 ml bottle of amber seasoned sushi vinegar beside measured rice, no copied label, no pure-akazu claim, no text or logos.',
		'category_source_ids' => array( 'tajima-seasoned-vinegar-producer-2026' ),
	),
	array(
		'id' => 'listing-minamigura-gin-warabeuta-tamari-200ml-20260806', 'slug' => 'minamigura-gin-warabeuta-tamari-200ml-20260806', 'subject_id' => 'hub-japanese-ingredients',
		'name' => $c99_text( 'Minamigura Gin Warabeuta Tamari 200 מ״ל, תצפית 6.8.2026', 'Minamigura Gin Warabeuta tamari 200 ml, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Gin Warabeuta 200 מ״ל', 'Gin Warabeuta tamari 200 ml price observation' ),
		'price' => 16.95, 'currency' => 'USD', 'unit' => 'one 200 ml bottle', 'basis' => 'one exact US-market 200 ml listing', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'listed_for_sale',
		'source_id' => 'minamigura-tamari-200ml-listing-2026', 'source_url' => 'https://japanesetaste.com/products/minamigura-tamari-shoyu-gluten-free-japanese-soy-sauce-gin-warabeuta-200ml', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
		'listing_attributes' => array( 'net_content' => '200 ml', 'product_form' => 'tamari shoyu', 'claim_boundary' => 'gluten-free claim requires certification and label verification' ),
		'statement' => $c99_text( 'נצפו 16.95 דולר לבקבוק 200 מ״ל. טענת ללא גלוטן נשארת מוחזקת עד אימות תווית והסמכה.', 'USD 16.95 was observed for a 200 ml bottle. The gluten-free claim remains held until label and certification verification.' ),
		'cross_sell_ids' => array( 'ingredient-yakinori', 'ingredient-fresh-wasabi' ), 'prompt_en' => 'Private editorial silhouette of an unbranded 200 ml bottle of dark tamari beside a small ceramic dish, no gluten-free badge, no copied label, logos or text.',
		'category_source_ids' => array( 'minamigura-method-2026', 'tamari-category-science-2020' ),
	),
	array(
		'id' => 'listing-sugimoto-organic-dried-shiitake-70g-20260806', 'slug' => 'sugimoto-organic-dried-shiitake-70g-20260806', 'subject_id' => 'hub-japanese-ingredients',
		'name' => $c99_text( 'Sugimoto שיטאקה יפנית אורגנית מיובשת 70 גרם, תצפית 6.8.2026', 'Sugimoto organic Japanese dried shiitake 70 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר שיטאקה יפנית 70 גרם', 'Japanese dried shiitake 70 g price observation' ),
		'price' => 14.29, 'currency' => 'USD', 'unit' => 'one 70 g pack', 'basis' => 'one exact international retail 70 g listing', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'listed_for_sale',
		'source_id' => 'sugimoto-shiitake-70g-listing-2026', 'source_url' => 'https://int.japanesetaste.com/products/sugimoto-organic-japanese-dried-shiitake-mushrooms-70g', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
		'listing_attributes' => array( 'net_content' => '70 g', 'product_form' => 'dried shiitake', 'organic_claim_state' => 'JAS and traceability verification required' ),
		'statement' => $c99_text( 'נצפו 14.29 דולר לאריזת 70 גרם. זהות, JAS, עקיבות ובדיקות מזהמים נשארות שערי הפעלה.', 'USD 14.29 was observed for a 70 g pack. Identity, JAS, traceability and contaminant testing remain activation gates.' ),
		'cross_sell_ids' => array( 'ingredient-kombu', 'technique-kombujime' ), 'prompt_en' => 'Private editorial cutout of unbranded whole dried Japanese shiitake in a plain 70 g pouch beside several caps, accurate wrinkled texture, no organic seal, label, logo or text.',
		'category_source_ids' => array( 'shiitake-category-science-2024' ),
	),
	array(
		'id' => 'listing-yubaya-kyoto-dried-yuba-100g-20260806', 'slug' => 'yubaya-kyoto-dried-yuba-100g-20260806', 'subject_id' => 'hub-japanese-ingredients',
		'name' => $c99_text( 'Yubaya יובה מיובשת מקיוטו 100 גרם, תצפית 6.8.2026', 'Yubaya Kyoto dried yuba 100 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר יובה מיובשת 100 גרם', 'Kyoto dried yuba 100 g price observation' ),
		'price' => 1080, 'currency' => 'JPY', 'unit' => 'one 100 g pack', 'basis' => 'current producer homepage price supersedes the stale item-page price', 'tax_status' => 'included', 'shipping_status' => 'unknown', 'comparability' => 'partially_comparable', 'availability' => 'listed_for_sale',
		'source_id' => 'yubaya-kyoto-yuba-home-2026', 'source_url' => 'https://www.yubaya.co.jp/', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
		'listing_attributes' => array( 'net_content' => '100 g', 'product_form' => 'dried yuba', 'price_precedence' => 'current homepage JPY 1,080 supersedes stale item-page price' ),
		'statement' => $c99_text( 'נצפו 1,080 ין כולל מס במחיר העדכני בדף הבית. מחיר ישן בדף הפריט אינו משמש כסמכות נוכחית.', 'JPY 1,080 including tax was observed as the current homepage price. A stale item-page price is not used as current authority.' ),
		'cross_sell_ids' => array( 'ingredient-kioke-shoyu', 'dish-kaiseki-hassun' ), 'prompt_en' => 'Private editorial cutout of unbranded dried yuba sheets in a plain 100 g pack, delicate layered soybean-skin texture, no copied packaging, logo or text.',
		'category_source_ids' => array( 'yubaya-kyoto-yuba-item-2026' ),
	),
	array(
		'id' => 'listing-ohsawa-organic-kudzu-starch-150g-20260806', 'slug' => 'ohsawa-organic-kudzu-starch-150g-20260806', 'subject_id' => 'hub-japanese-ingredients',
		'name' => $c99_text( 'Ohsawa עמילן קודזו אורגני 150 גרם, תצפית 6.8.2026', 'Ohsawa organic kudzu starch 150 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר עמילן קודזו 150 גרם', 'kudzu starch 150 g price observation' ),
		'price' => 14.98, 'currency' => 'USD', 'unit' => 'one 150 g pack', 'basis' => 'one exact US-market 150 g block-type listing', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'listed_for_sale',
		'source_id' => 'ohsawa-kudzu-150g-listing-2026', 'source_url' => 'https://japanesetaste.com/products/ohsawa-organic-kudzu-starch-block-type-thickening-powder-150g', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
		'listing_attributes' => array( 'net_content' => '150 g', 'product_form' => 'block-type starch', 'identity_state' => '100 percent kudzu and organic scope require verification' ),
		'statement' => $c99_text( 'נצפו 14.98 דולר לאריזת 150 גרם. טענות 100 אחוז קודזו ואורגני אינן מאושרות ללא תווית ותעודה.', 'USD 14.98 was observed for a 150 g pack. The 100 percent kudzu and organic claims are not approved without a label and certificate.' ),
		'cross_sell_ids' => array( 'dish-kaiseki-hassun' ), 'prompt_en' => 'Private editorial cutout of unbranded pale kudzu starch blocks in a plain 150 g pack and a small ceramic dish, no organic seal, health symbol, copied label or text.',
		'category_source_ids' => array( 'kudzu-category-science-2026' ),
	),
	array(
		'id' => 'listing-yawataya-isogoro-sansho-12g-20260806', 'slug' => 'yawataya-isogoro-sansho-12g-20260806', 'subject_id' => 'hub-japanese-ingredients',
		'name' => $c99_text( 'Yawataya Isogoro Sansho 12 גרם, תצפית 6.8.2026', 'Yawataya Isogoro sansho 12 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר סאנשו 12 גרם', 'sansho pepper 12 g price observation' ),
		'price' => 21.99, 'currency' => 'USD', 'unit' => 'one 12 g pack', 'basis' => 'one exact US-market 12 g listing', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'listed_for_sale',
		'source_id' => 'yawataya-sansho-12g-listing-2026', 'source_url' => 'https://japanesetaste.com/products/yawataya-isogoro-sansho-pepper-japanese-pepper-12g', 'market' => 'united-states-online-retail', 'market_scope' => 'market_specific',
		'listing_attributes' => array( 'net_content' => '12 g', 'product_form' => 'ground sansho', 'identity_state' => 'botanical species, origin and harvest require verification' ),
		'statement' => $c99_text( 'נצפו 21.99 דולר לאריזת 12 גרם. המין הבוטני, המקור, הבציר והגנת האור והלחות דורשים אימות.', 'USD 21.99 was observed for a 12 g pack. Botanical species, origin, harvest and light and moisture protection require verification.' ),
		'cross_sell_ids' => array( 'dish-kaiseki-hassun', 'ingredient-kioke-shoyu' ), 'prompt_en' => 'Private editorial macro cutout of unbranded ground sansho and a few husks beside a plain 12 g container, controlled low-oxygen styling, no copied label, health claim, logo or text.',
		'category_source_ids' => array( 'sansho-category-science-2023' ),
	),
	array(
		'id' => 'listing-marukyu-koyamaen-tenju-matcha-20g-20260806', 'slug' => 'marukyu-koyamaen-tenju-matcha-20g-20260806', 'subject_id' => 'hub-japanese-ingredients',
		'name' => $c99_text( 'Marukyu Koyamaen Tenju Matcha 20 גרם, תצפית 6.8.2026', 'Marukyu Koyamaen Tenju matcha 20 g, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Tenju Matcha 20 גרם', 'Tenju matcha 20 g price observation' ),
		'price' => 21600, 'currency' => 'JPY', 'unit' => 'one 20 g tin', 'basis' => 'one exact maker listing for SKU 1111020C1 with sold-out and irregular-selling or shortage context', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'non_comparable', 'availability' => 'sold_out_limited_allocation',
		'source_id' => 'marukyu-tenju-matcha-20g-listing-2026', 'source_url' => 'https://www.marukyu-koyamaen.co.jp/motoan-shop/products/1111020c1/', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
		'listing_attributes' => array( 'sku' => '1111020C1', 'net_content' => '20 g', 'allocation_state' => 'limited', 'stock_state' => 'sold out', 'selling_context' => 'irregular selling or shortage context' ),
		'statement' => $c99_text( 'בדף הרשמי של SKU 1111020C1 נצפו 21,600 ין ל-20 גרם, מצב אזל מהמלאי והקשר של מכירה לא סדירה או מחסור.', 'The official page for SKU 1111020C1 displayed JPY 21,600 for 20 g, sold-out status and irregular-selling or shortage context.' ),
		'cross_sell_ids' => array( 'listing-kubo-komakichi-kazuho-chasen-20260806' ), 'prompt_en' => 'Private editorial macro of vivid unbranded matcha powder in a plain 20 g tin beside a tea bowl, no copied packaging, ceremonial-grade claim, health symbol, logo or text.',
		'category_source_ids' => array( 'matcha-category-science-2022-a', 'matcha-category-science-2022-b' ),
	),
	array(
		'id' => 'listing-yamaco-bamboo-makisu-27cm-20260806', 'slug' => 'yamaco-bamboo-makisu-27cm-20260806', 'subject_id' => 'hub-japanese-equipment',
		'name' => $c99_text( 'Yamaco מחצלת במבוק Makisu 27 ס״מ, תצפית 6.8.2026', 'Yamaco bamboo makisu 27 cm, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Makisu 27 ס״מ', 'Yamaco makisu 27 cm price observation' ),
		'price' => 28, 'currency' => 'AUD', 'unit' => 'one 27 cm mat', 'basis' => 'one Australian listing whose exact 27 cm variant requires confirmation', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'partially_comparable', 'availability' => 'listed_for_sale',
		'source_id' => 'yamaco-makisu-27cm-listing-2026', 'source_url' => 'https://www.mujostore.com/products/bamboo-sushi-mat', 'market' => 'australia-online-retail', 'market_scope' => 'market_specific',
		'listing_attributes' => array( 'size_claim' => '27 cm variant requires confirmation', 'material_claim' => 'bamboo', 'food_contact_state' => 'finish and cleaning instructions require verification' ),
		'statement' => $c99_text( 'נצפו 28 דולר אוסטרלי. יש לאמת שהמחיר שייך לווריאנט 27 ס״מ ואת חומר המגע, הגימור, הניקוי, האחריות והמלאי.', 'AUD 28 was observed. Exact 27 cm variant, food-contact material, finish, cleaning, warranty and stock require verification.' ),
		'cross_sell_ids' => array( 'ingredient-yakinori', 'dish-futomaki-sushi' ), 'prompt_en' => 'Private editorial cutout of one unbranded 27 cm bamboo makisu on a neutral food-safe surface, accurate slat and cord detail, no copied packaging, logo or text.',
		'category_source_ids' => array(),
	),
	array(
		'id' => 'listing-sakai-takayuki-ginsan-yanagiba-270mm-20260806', 'slug' => 'sakai-takayuki-ginsan-yanagiba-270mm-20260806', 'subject_id' => 'equipment-yanagiba',
		'name' => $c99_text( 'Sakai Takayuki Ginsan Yanagiba 270 מ״מ, תצפית 6.8.2026', 'Sakai Takayuki Ginsan yanagiba 270 mm, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Ginsan Yanagiba 270 מ״מ', 'Ginsan yanagiba 270 mm price observation' ),
		'price' => 399.95, 'currency' => 'AUD', 'unit' => 'one 270 mm knife', 'basis' => 'one exact Australian 270 mm listing with availability conflict', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'conflicting',
		'source_id' => 'sakai-takayuki-ginsan-yanagiba-270mm-listing-2026', 'source_url' => 'https://www.knivesandstones.com.au/products/sakai-takayuki-ginsan-yanagiba-270mm', 'market' => 'australia-online-retail', 'market_scope' => 'market_specific',
		'listing_attributes' => array( 'brand_claim' => 'Sakai Takayuki', 'manufacturer_claim' => 'Aoki Hamono', 'blade_length' => '270 mm', 'steel_claim' => 'Ginsan or Silver 3 requires exact listing verification', 'availability_state' => 'conflicting', 'handedness_state' => 'verification required' ),
		'statement' => $c99_text( 'נצפו 399.95 דולר אוסטרלי לדגם 270 מ״מ. זמינות, ידיות, פלדה, HRC, מידות, אחריות ומשלוח דורשים אימות.', 'AUD 399.95 was observed for the 270 mm model. Availability, handedness, steel, HRC, dimensions, warranty and shipping require verification.' ),
		'cross_sell_ids' => array( 'dish-edomae-nigiri', 'technique-kombujime' ), 'prompt_en' => 'Private editorial cutout of one unbranded single-bevel 270 mm yanagiba with neutral handle and safe sheath, matched scale card, no copied maker marks, HRC claim, logo or text.',
		'category_source_ids' => array( 'ginsan-steel-index-2026' ),
	),
	array(
		'id' => 'listing-nagatanien-kamado-san-3-cup-20260806', 'slug' => 'nagatanien-kamado-san-3-cup-20260806', 'subject_id' => 'hub-japanese-equipment',
		'name' => $c99_text( 'Nagatanien Kamado-san ל-3 כוסות, תצפית 6.8.2026', 'Nagatanien Kamado-san three-cup donabe, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Kamado-san 3 כוסות', 'Kamado-san three-cup price observation' ),
		'price' => 16500, 'currency' => 'JPY', 'unit' => 'one ACT-01 three-cup donabe', 'basis' => 'one exact producer-store listing scheduled for sequential shipment after late September', 'tax_status' => 'included', 'shipping_status' => 'unknown', 'comparability' => 'like_for_like', 'availability' => 'sequential_shipment_after_late_september',
		'source_id' => 'nagatanien-kamado-san-3cup-listing-2026', 'source_url' => 'https://store.igamono.jp/?pid=85075826', 'market' => 'japan-producer-retail', 'market_scope' => 'japan_source_market',
		'listing_attributes' => array( 'model_code' => 'ACT-01', 'capacity' => 'three cups', 'availability_state' => 'sequential shipment after late September (9月下旬以降)', 'safety_state' => 'stove compatibility, thermal shock and lead and cadmium documentation required' ),
		'statement' => $c99_text( 'נצפו 16,500 ין כולל מס לדגם ACT-01 של 3 כוסות, המתוכנן למשלוח מדורג לאחר סוף ספטמבר.', 'JPY 16,500 including tax was observed for the ACT-01 three-cup model, scheduled for sequential shipment after late September (9月下旬以降).' ),
		'cross_sell_ids' => array( 'ingredient-koshihikari-rice', 'preparation-sushi-shari' ), 'prompt_en' => 'Private editorial cutout of an unbranded three-cup Japanese donabe with lid on a cold neutral surface, exact ceramic texture, no flame, copied packaging, safety seal, logo or text.',
		'category_source_ids' => array( 'nagatanien-kamado-san-yahoo-2026' ),
	),
	array(
		'id' => 'listing-kubo-komakichi-kazuho-chasen-20260806', 'slug' => 'kubo-komakichi-kazuho-chasen-20260806', 'subject_id' => 'hub-japanese-equipment',
		'name' => $c99_text( 'Kubo Komakichi Kazuho Chasen, תצפית 6.8.2026', 'Kubo Komakichi Kazuho chasen, observed August 6 2026' ), 'keyword' => $c99_text( 'תצפית מחיר Kazuho Chasen', 'Kubo Komakichi Kazuho chasen price observation' ),
		'price' => 5830, 'currency' => 'JPY', 'unit' => 'one chasen', 'basis' => 'one exact maker-attributed listing observed sold out; kept distinct from generic Kazuho records', 'tax_status' => 'unknown', 'shipping_status' => 'unknown', 'comparability' => 'non_comparable', 'availability' => 'sold_out',
		'source_id' => 'kubo-komakichi-kazuho-chasen-listing-2026', 'source_url' => 'https://teaosakaya.theshop.jp/items/65610450', 'market' => 'japan-online-retail', 'market_scope' => 'japan_source_market',
		'listing_attributes' => array( 'maker_claim' => 'Kubo Komakichi', 'style_claim' => 'Kazuho', 'tine_claim' => 'approximately 70 requires verification', 'origin_claim' => 'Takayama requires verification', 'availability' => 'sold out' ),
		'statement' => $c99_text( 'נצפו 5,830 ין והמוצר סומן אזל מהמלאי. רשומת היצרן נשמרת בנפרד מרשומות Kazuho גנריות.', 'JPY 5,830 was observed and the item was marked sold out. The maker-attributed record remains separate from generic Kazuho records.' ),
		'cross_sell_ids' => array( 'listing-marukyu-koyamaen-tenju-matcha-20g-20260806' ), 'prompt_en' => 'Private editorial cutout of one unbranded handmade bamboo chasen with approximately seventy fine tines as a verification target, neutral tea setting, no copied maker mark, logo or text.',
		'category_source_ids' => array( 'chasen-foam-science-2012' ),
	),
);

foreach ( $retail_specs as $retail_spec ) {
	$category_source_ids = $retail_spec['category_source_ids'];
	unset( $retail_spec['category_source_ids'] );
	$listing_entity = $retail_listing( $retail_spec );
	if ( ! empty( $category_source_ids ) ) {
		$category_source_groups = array();
		foreach ( $category_source_ids as $category_source_id ) {
			$category_source_type = $tranche_sources[ $category_source_id ]['type'];
			if ( 'peer_reviewed_paper' === $category_source_type ) {
				$category_evidence_class = 'peer_reviewed_context';
			} elseif ( in_array( $category_source_type, array( 'official_business', 'official_market_listing', 'official_government', 'official_organization' ), true ) ) {
				$category_evidence_class = 'official_source';
			} else {
				throw new RuntimeException( 'Unsupported premium tranche category-source type: ' . $category_source_type );
			}
			$category_source_groups[ $category_evidence_class ][] = $category_source_id;
		}
		$category_fact_ids = array();
		foreach ( $category_source_groups as $category_evidence_class => $grouped_source_ids ) {
			$category_fact_id = 'fact-' . $retail_spec['slug'] . '-category-context-boundary-' . str_replace( '_', '-', $category_evidence_class );
			$listing_entity['facts'][] = $c99_fact(
				$category_fact_id,
				'scientific',
				'המקורות הנלווים מתארים קטגוריה, חומר או תהליך. הם אינם בדיקת מעבדה של ה-SKU, היצרן או האצווה שבתצפית זו, ולכן אין להסיק מהם pH, ריכוז תרכובת, יתרון בריאותי או אישור מוצר.',
				'The supporting sources describe a category, material or process. They are not laboratory tests of this observed SKU, maker or lot, so they do not establish pH, compound concentration, a health benefit or product approval.',
				$category_evidence_class,
				'category',
				$grouped_source_ids,
				false
			);
			$category_fact_ids[] = $category_fact_id;
		}
		$listing_entity['profiles']['scientific'] = $c99_profile(
			'source_backed',
			'המחקר נשמר כהקשר קטגוריה בלבד ומופרד מטענות SKU ואצווה.',
			'Research is retained only as category context and separated from SKU and lot claims.',
			$category_fact_ids
		);
	}
	$tranche_entities[] = $listing_entity;
}

return array(
	'sources' => $tranche_sources,
	'entities' => $tranche_entities,
	'enrichments' => array(
		'market-toyosu' => array(
			'facts' => array(
				$c99_fact( 'fact-toyosu-official-market-scope-2026', 'institutional', 'מקורות ממשלת מטרופולין טוקיו מתארים את טויוסו כשוק סיטונאי בעל מערך מתקנים ותפקידים מוגדרים. המידע תומך במיפוי מוסדי ואינו יוצר גישת מסחר, אספקה או מלאי ל-Complete99.', 'Tokyo Metropolitan Government sources describe Toyosu as a wholesale market with defined facilities and roles. This supports institutional mapping and does not create trading access, supply or inventory for Complete99.', 'official_source', 'entity', array( 'toyosu-market-official-2026', 'toyosu-market-overview-2026' ), false ),
			),
			'relations' => array(
				$c99_relation( 'references', 'hub-japanese-sourcing', 'טויוסו משמש נקודת מחקר למבנה שרשרת אספקה, לא ספק מאושר.', 'Toyosu is a research node for supply-chain structure, not an approved supplier.', false, array( 'toyosu-market-official-2026' ), 'official_source' ),
			),
		),
		'institution-japanese-culinary-academy' => array(
			'facts' => array(
				$c99_fact( 'fact-jca-corpus-and-taizen-scope-2026', 'institutional', 'האתר הרשמי של האקדמיה מפנה לקורפוס מונחים ולספר דיגיטלי. נכסים אלה נשמרים כמקורות ייחוס, בלי לטעון לשותפות, אישור או אימוץ של תוכן Complete99.', 'The academy official site links to a terminology corpus and digital book. These assets remain reference sources without claiming partnership, endorsement or adoption of Complete99 content.', 'official_source', 'entity', array( 'jca-official-en-2026', 'jca-corpus-2026', 'jca-taizen-digital-book-2026' ), false ),
			),
			'relations' => array(
				$c99_relation( 'references', 'cuisine-japanese-washoku', 'הקורפוס והספר הדיגיטלי תומכים במחקר מונחים והקשר, לא בהסמכת המערכת.', 'The corpus and digital book support terminology and context research, not system accreditation.', false, array( 'jca-corpus-2026', 'jca-taizen-digital-book-2026' ), 'official_source' ),
			),
		),
	),
	'parent_overrides' => array(
		'supplier-district-kappabashi' => 'hub-japanese-sourcing',
		'restaurant-ginza-kyubey' => 'hub-japanese-restaurants',
		'technique-edomae-shari-control' => 'hub-japanese-techniques',
		'technique-kombujime' => 'hub-japanese-techniques',
		'dish-futomaki-sushi' => 'hub-japanese-dishes',
		'dish-kaiseki-hassun' => 'hub-japanese-dishes',
	),
	'section_owner_map' => array(
		'technique-edomae-shari-control' => 'cuisine-japanese-washoku',
		'technique-kombujime' => 'cuisine-japanese-washoku',
		'dish-futomaki-sushi' => 'cuisine-japanese-washoku',
		'dish-kaiseki-hassun' => 'cuisine-japanese-washoku',
	),
);
