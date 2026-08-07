<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public, projection-only renderer for the Culinary Science Museum.
 *
 * The class never reads the private registry. It accepts only page bundles
 * released by Complete99_Culinary_Science and exact-matches their canonical
 * paths before WordPress is allowed to render a museum response.
 */
final class Complete99_Culinary_Museum_Frontend {
	const QUERY_VAR       = 'complete99_culinary_museum';
	const LANGUAGE_VAR    = 'complete99_culinary_museum_lang';
	const TEMPLATE_FILE   = 'templates/culinary-museum.php';
	const STYLESHEET_FILE = 'assets/css/culinary-museum.css';
	const COLLECTION_SCHEMA       = 'complete99-culinary-collection-public/v1';
	const COLLECTION_FILTER_QUERY = 'foundation-group';

	private static $booted      = false;
	private static $bundle      = array();
	private static $request_path = '';

	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'parse_request', array( __CLASS__, 'capture_request' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'prepare_response' ), 0 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 1000 );
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ), 1000 );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'document_title' ), 1000 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'head_metadata' ), 4 );
	}

	public static function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		$vars[] = self::LANGUAGE_VAR;
		return array_values( array_unique( $vars ) );
	}

	/**
	 * Resolve an approved page bundle before the main query chooses a template.
	 * Only GET and HEAD are public page methods.
	 */
	public static function capture_request( $wp ) {
		self::$bundle       = array();
		self::$request_path = '';

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true )
			|| ! class_exists( 'Complete99_Culinary_Science', false )
			|| ! is_callable( array( 'Complete99_Culinary_Science', 'public_page_bundle_for_path' ) ) ) {
			return;
		}

		$paths = self::request_paths();
		if ( empty( $paths ) ) {
			return;
		}

		$bundle = Complete99_Culinary_Science::public_page_bundle_for_path( $paths['lookup'] );
		if ( ! self::is_renderable_bundle( $bundle, $paths['lookup'] ) ) {
			return;
		}

		self::$bundle       = $bundle;
		self::$request_path = $paths['request'];
		if ( is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
			$wp->query_vars[ self::QUERY_VAR ]    = (string) $bundle['entity']['id'];
			$wp->query_vars[ self::LANGUAGE_VAR ] = (string) $bundle['language'];
		}
	}

	public static function is_museum_request() {
		return ! empty( self::$bundle );
	}

	public static function current_bundle() {
		return self::$bundle;
	}

	/**
	 * Canonicalize the one accepted path variant and convert WordPress 404 state
	 * into a normal, cacheable public response.
	 */
	public static function prepare_response() {
		if ( ! self::is_museum_request() ) {
			return;
		}

		remove_action( 'wp_head', 'rel_canonical' );
		if ( self::$request_path !== self::$bundle['canonical_path'] ) {
			wp_safe_redirect( self::$bundle['canonical_url'], 301, 'Complete99' );
			exit;
		}

		global $wp_query;
		if ( is_object( $wp_query ) ) {
			$wp_query->is_404     = false;
			$wp_query->is_page    = false;
			$wp_query->is_singular = false;
		}
		status_header( 200 );
	}

	public static function template_include( $template ) {
		if ( ! self::is_museum_request() ) {
			return $template;
		}

		return COMPLETE99_PLATFORM_DIR . self::TEMPLATE_FILE;
	}

	public static function body_classes( $classes ) {
		if ( ! self::is_museum_request() ) {
			return $classes;
		}

		$lang = self::$bundle['language'];
		if ( 'en' === $lang ) {
			$classes = array_values( array_diff( $classes, array( 'rtl' ) ) );
		}
		$classes[] = 'complete99-public';
		$classes[] = 'c99-consumer-site';
		$classes[] = 'c99-culinary-museum';
		$classes[] = 'complete99-lang-' . $lang;
		$classes[] = 'en' === $lang ? 'complete99-ltr' : 'complete99-rtl';
		$classes[] = 'c99-museum-type-' . sanitize_html_class( (string) self::$bundle['entity']['type'] );
		if ( ! empty( self::approved_collection_projection( self::$bundle ) ) ) {
			$classes[] = 'c99-foundations-lab-page';
		}

		return array_values( array_unique( $classes ) );
	}

	public static function document_title( $title ) {
		if ( ! self::is_museum_request() ) {
			return $title;
		}

		$entity_title = isset( self::$bundle['entity']['seo']['title'] )
			? trim( wp_strip_all_tags( (string) self::$bundle['entity']['seo']['title'] ) )
			: '';
		if ( '' === $entity_title ) {
			$entity_title = trim( wp_strip_all_tags( (string) self::$bundle['entity']['name'] ) );
		}
		if ( false === stripos( $entity_title, 'Complete99' ) ) {
			$entity_title .= ' | Complete99';
		}
		return $entity_title;
	}

	public static function robots( $robots ) {
		if ( ! self::is_museum_request() ) {
			return $robots;
		}

		foreach ( array( 'index', 'noindex', 'follow', 'nofollow', 'max-image-preview', 'max-snippet', 'max-video-preview' ) as $directive ) {
			unset( $robots[ $directive ] );
		}
		if ( true === self::$bundle['indexable'] ) {
			$robots['index']             = true;
			$robots['follow']            = true;
			$robots['max-image-preview'] = 'large';
			$robots['max-snippet']       = -1;
			$robots['max-video-preview'] = -1;
		} else {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}

	public static function enqueue() {
		if ( ! self::is_museum_request() ) {
			return;
		}

		wp_enqueue_style(
			'complete99-public',
			COMPLETE99_PLATFORM_URL . 'assets/css/public.css',
			array(),
			COMPLETE99_PLATFORM_VERSION
		);
		wp_enqueue_style(
			'complete99-consumer',
			COMPLETE99_PLATFORM_URL . 'assets/css/consumer.css',
			array( 'complete99-public' ),
			COMPLETE99_PLATFORM_VERSION
		);
		wp_enqueue_style(
			'complete99-culinary-museum',
			COMPLETE99_PLATFORM_URL . self::STYLESHEET_FILE,
			array( 'complete99-consumer' ),
			COMPLETE99_PLATFORM_VERSION
		);
		wp_enqueue_script(
			'complete99-public',
			COMPLETE99_PLATFORM_URL . 'assets/js/public.js',
			array(),
			COMPLETE99_PLATFORM_VERSION,
			true
		);
	}

	/**
	 * Emit one canonical, complete language alternates, social metadata and
	 * evidence-aware JSON-LD for the public projection.
	 */
	public static function head_metadata() {
		if ( ! self::is_museum_request() ) {
			return;
		}

		$bundle     = self::$bundle;
		$entity     = $bundle['entity'];
		$title      = self::document_title( '' );
		$description = isset( $entity['seo']['meta_description'] ) ? (string) $entity['seo']['meta_description'] : (string) $entity['summary'];
		$image      = self::visual_url( isset( $entity['visual'] ) ? $entity['visual'] : array() );
		$image_alt  = isset( $entity['visual']['alt'] ) ? (string) $entity['visual']['alt'] : (string) $entity['name'];
		$locale     = 'he' === $bundle['language'] ? 'he_IL' : 'en_US';
		$other      = 'he' === $bundle['language'] ? 'en_US' : 'he_IL';

		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $bundle['canonical_url'] ) . '" />' . "\n";
		foreach ( array( 'he', 'en', 'x-default' ) as $language ) {
			echo '<link rel="alternate" hreflang="' . esc_attr( $language ) . '" href="' . esc_url( $bundle['alternates'][ $language ] ) . '" />' . "\n";
		}
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:site_name" content="Complete99" />' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '" />' . "\n";
		echo '<meta property="og:locale:alternate" content="' . esc_attr( $other ) . '" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $bundle['canonical_url'] ) . '" />' . "\n";
		if ( '' !== $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
			echo '<meta property="og:image:alt" content="' . esc_attr( $image_alt ) . '" />' . "\n";
			echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		}

		$schema = self::schema_graph( $bundle );
		$flags  = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, $flags ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render_page( $bundle ) {
		if ( ! self::is_renderable_bundle( $bundle, $bundle['canonical_path'] ) ) {
			return;
		}
		if ( 'museum-culinary-science' === $bundle['entity']['id'] ) {
			self::render_museum_landing( $bundle );
			return;
		}
		if ( 'cuisine-lebanese-regional' === $bundle['entity']['id'] ) {
			self::render_lebanese_landing( $bundle );
			return;
		}
		$collection = self::approved_collection_projection( $bundle );
		if ( ! empty( $collection ) ) {
			self::render_collection_page( $bundle, $collection );
			return;
		}

		$entity = $bundle['entity'];
		$lang   = $bundle['language'];
		$is_he  = 'he' === $lang;
		?>
		<section class="c99-museum-hero">
			<div class="c99-container">
				<?php self::render_breadcrumbs( $entity, $bundle ); ?>
				<div class="c99-museum-hero-grid">
					<div class="c99-museum-hero-copy">
						<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'מוזיאון המדע של הקולינריה' : 'Culinary Science Museum' ); ?></p>
						<h1><?php echo esc_html( isset( $entity['seo']['h1'] ) ? $entity['seo']['h1'] : $entity['name'] ); ?></h1>
						<p class="c99-museum-lede"><?php echo esc_html( isset( $entity['seo']['opening'] ) ? $entity['seo']['opening'] : $entity['summary'] ); ?></p>
						<div class="c99-museum-fact-strip" aria-label="<?php echo esc_attr( $is_he ? 'פרטי העמוד' : 'Page details' ); ?>">
							<span><strong><?php echo esc_html( count( $entity['sources'] ) ); ?></strong> <?php echo esc_html( $is_he ? 'מקורות' : 'sources' ); ?></span>
							<span><strong><?php echo esc_html( self::entity_type_label( $entity['type'], $lang ) ); ?></strong></span>
							<span><strong><?php echo esc_html( $is_he ? 'עודכן' : 'Updated' ); ?></strong> <time datetime="<?php echo esc_attr( $entity['trust']['substantive_updated_at'] ); ?>"><?php echo esc_html( $entity['trust']['substantive_updated_at'] ); ?></time></span>
						</div>
					</div>
					<?php self::render_visual( $entity, true ); ?>
				</div>
			</div>
		</section>

		<div class="c99-container c99-museum-layout">
			<div class="c99-museum-main-column">
				<?php self::render_profiles( $entity, 2 ); ?>
				<?php self::render_facts( $entity, 2 ); ?>
				<?php self::render_sections( $bundle['sections'], $bundle ); ?>
				<?php self::render_connections( $entity, 2 ); ?>
				<?php self::render_sources( $entity, 2 ); ?>
			</div>
			<aside class="c99-museum-side-column" aria-label="<?php echo esc_attr( $is_he ? 'מידע משלים' : 'Supporting information' ); ?>">
				<?php self::render_offer( $entity ); ?>
				<?php self::render_market_context( $entity ); ?>
				<?php self::render_taxonomy( $entity ); ?>
				<?php self::render_safety( $entity ); ?>
				<?php self::render_trust( $entity, $bundle ); ?>
			</aside>
		</div>
		<?php
	}

	/**
	 * Render the museum entrance as a food-first discovery experience.
	 * Research notes remain available at the end without interrupting the visit.
	 */
	private static function render_museum_landing( $bundle ) {
		$entity = $bundle['entity'];
		$is_he  = 'he' === $bundle['language'];
		$paths  = $is_he
			? array(
				'japanese'  => '/museum/japanese-culinary-science/',
				'syrian'    => '/museum/syrian-culinary-science/',
				'lebanese'  => '/museum/lebanese-culinary-science/',
				'dishes'    => '/dishes/',
				'store'     => '/store/',
				'ingredients' => '/ingredients/',
				'traditions' => '/traditions/',
				'knowledge' => '/knowledge/',
				'proposal'  => '/request-proposal/',
			)
			: array(
				'japanese'  => '/en/museum/japanese-culinary-science/',
				'syrian'    => '/en/museum/syrian-culinary-science/',
				'lebanese'  => '/en/museum/lebanese-culinary-science/',
				'dishes'    => '/en/dishes/',
				'store'     => '/en/store/',
				'ingredients' => '/en/ingredients/',
				'traditions' => '/en/traditions/',
				'knowledge' => '/en/knowledge/',
				'proposal'  => '/en/request-proposal/',
			);
		$urls = array();
		foreach ( $paths as $key => $path ) {
			$urls[ $key ] = self::internal_url( $path );
		}

		$cuisines = array(
			array(
				'url'   => $urls['japanese'],
				'eyebrow' => $is_he ? 'יפן' : 'Japan',
				'title' => $is_he ? 'המטבח היפני' : 'Japanese cooking',
				'copy'  => $is_he ? 'אורז, דאשי, סויה, וואסבי וכלי עבודה שמגלים כמה עומק יכול להסתתר בביס נקי ומדויק.' : 'Rice, dashi, shoyu, wasabi and beautiful tools reveal how much depth can live in one precise bite.',
				'cta'   => $is_he ? 'לטייל ביפן דרך האוכל' : 'Taste your way through Japan',
				'image' => 'assets/images/science/c99-science-japanese-washoku-v01',
				'alt'   => $is_he ? 'שולחן יפני עם אורז, דאשי, סויה וכלי הגשה' : 'Japanese table with rice, dashi, shoyu and serving pieces',
			),
			array(
				'url'   => $urls['syrian'],
				'eyebrow' => $is_he ? 'סוריה' : 'Syria',
				'title' => $is_he ? 'המטבח הסורי' : 'Syrian cooking',
				'copy'  => $is_he ? 'מחלב ודמשק מגיעים פלפל, פריקה, רכז רימונים, קובה ושולחנות שמספרים סיפור בכל שכבה.' : 'Aleppo and Damascus bring pepper, freekeh, pomegranate molasses, kibbeh and tables layered with stories.',
				'cta'   => $is_he ? 'לגלות טעמים מסוריה' : 'Discover the flavors of Syria',
				'image' => 'assets/images/science/c99-science-syrian-regional-table-v01',
				'alt'   => $is_he ? 'שולחן אזורי סורי עשיר במנות, דגנים ותבלינים' : 'Regional Syrian table rich with dishes, grains and spices',
			),
			array(
				'url'   => $urls['lebanese'],
				'eyebrow' => $is_he ? 'לבנון' : 'Lebanon',
				'title' => $is_he ? 'המטבח הלבנוני' : 'Lebanese cooking',
				'copy'  => $is_he ? 'מהחוף אל ההרים והבקעה: זעתר, סומאק, קובה, דגים, עשבי תיבול ומזווה שמתחלף עם הנוף.' : 'From the coast to the mountains and the Bekaa: zaatar, sumac, kibbeh, fish, herbs and a pantry shaped by place.',
				'cta'   => $is_he ? 'להיכנס למזווה הלבנוני' : 'Enter the Lebanese pantry',
				'image' => 'assets/images/science/c99-science-lebanese-regional-table-v01',
				'alt'   => $is_he ? 'שולחן לבנוני עם מנאקיש, טאבולה, קובה, דג ותבלינים' : 'Lebanese table with manakish, tabbouleh, kibbeh, fish and spices',
			),
		);

		$shelves = array(
			array(
				'url'   => $urls['dishes'],
				'title' => $is_he ? 'מנות שמתחילות בתיאבון' : 'Dishes that begin with appetite',
				'copy'  => $is_he ? 'סביח, שקשוקה, קובה, מרקים ועוד מנות שאפשר להכיר מקרוב.' : 'Sabich, shakshuka, kibbeh, soups and more dishes to discover up close.',
				'cta'   => $is_he ? 'לכל המנות' : 'Explore all dishes',
				'image' => 'assets/images/original/c99-food-house-spread-hero-2021-wp-v01',
				'alt'   => $is_he ? 'מבחר מנות ביתיות צבעוניות על שולחן' : 'A colorful spread of homestyle dishes on a table',
			),
			array(
				'url'   => $urls['ingredients'],
				'title' => $is_he ? 'חומרי גלם שמבקשים לטעום' : 'Ingredients worth tasting',
				'copy'  => $is_he ? 'מתבלין שלם ועד רוטב מותסס, כל מרכיב פותח דלת למנות ולשיטות שמתאימות לו.' : 'From whole spices to fermented sauces, every ingredient opens a door to the dishes and methods that suit it.',
				'cta'   => $is_he ? 'לגלות חומרי גלם' : 'Discover ingredients',
				'image' => 'assets/images/science/c99-science-japanese-premium-ingredients-v01',
				'alt'   => $is_he ? 'שולחן חומרי גלם יפניים עם קומבו, אורז, יוזו, וואסבי ושויו' : 'Japanese ingredient table with kombu, rice, yuzu, wasabi and shoyu',
			),
			array(
				'url'   => $urls['store'],
				'title' => $is_he ? 'המדף שאפשר לקחת הביתה' : 'The shelf you can take home',
				'copy'  => $is_he ? 'עברו בין מוצרי המזווה והציוד שכבר מוצגים בחנות.' : 'Browse pantry goods and equipment already shown in the store.',
				'cta'   => $is_he ? 'לבקר בחנות' : 'Visit the store',
				'image' => 'assets/images/science/c99-science-museum-store-pantry-v01',
				'alt'   => $is_he ? 'מזווה חנות עשיר עם תבלינים, דגנים, שמנים, ירקות וחומרי גלם יפניים' : 'Rich store pantry with spices, grains, oils, vegetables and Japanese ingredients',
			),
			array(
				'url'   => $urls['traditions'],
				'title' => $is_he ? 'הסיפורים שעוברים סביב השולחן' : 'Stories passed around the table',
				'copy'  => $is_he ? 'מסורות, אזורים, קהילות והרגלי אכילה שמסבירים למה למנה יש טעם של מקום.' : 'Traditions, regions, communities and eating customs explain why a dish tastes of its place.',
				'cta'   => $is_he ? 'לגלות מסורות' : 'Explore traditions',
				'image' => 'assets/images/science/c99-science-culinary-museum-pantry-v02',
				'alt'   => $is_he ? 'מזווה קולינרי רחב עם כלים וחומרי גלם מתרבויות שונות' : 'A broad culinary pantry with tools and ingredients from different cultures',
			),
		);

		$intent_paths = array(
			array( 'url' => $urls['dishes'], 'number' => '01', 'title' => $is_he ? 'בא לי לאכול' : 'I want to eat', 'copy' => $is_he ? 'מתחילים מהמנות.' : 'Start with the dishes.' ),
			array( 'url' => $urls['ingredients'], 'number' => '02', 'title' => $is_he ? 'בא לי לבשל' : 'I want to cook', 'copy' => $is_he ? 'בוחרים חומר גלם וממשיכים ממנו.' : 'Choose an ingredient and follow where it leads.' ),
			array( 'url' => $urls['knowledge'], 'number' => '03', 'title' => $is_he ? 'בא לי להבין' : 'I want to learn', 'copy' => $is_he ? 'נכנסים למדריכים, למתכונים ולשיטות.' : 'Open the guides, recipes and methods.' ),
			array( 'url' => $urls['store'], 'number' => '04', 'title' => $is_he ? 'אני מחפש מוצר' : 'I am looking for a product', 'copy' => $is_he ? 'מדלגים ישר למדפי החנות.' : 'Go straight to the store shelves.' ),
			array( 'url' => $urls['proposal'], 'number' => '05', 'title' => $is_he ? 'אנחנו קבוצה או חברה' : 'We are a group or company', 'copy' => $is_he ? 'מספרים לנו איזו ארוחה אתם מדמיינים.' : 'Tell us about the meal you have in mind.' ),
		);
		?>
		<div class="c99-museum-home" id="c99-museum-home">
			<section class="c99-museum-home-hero" aria-labelledby="c99-museum-home-title">
				<div class="c99-container">
					<?php self::render_breadcrumbs( $entity, $bundle ); ?>
					<div class="c99-museum-home-hero-grid">
						<div class="c99-museum-home-hero-copy">
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'ברוכים הבאים למזווה של העולם' : 'Welcome to the world pantry' ); ?></p>
							<h1 id="c99-museum-home-title"><?php echo esc_html( $entity['seo']['h1'] ); ?></h1>
							<p class="c99-museum-home-intro"><?php echo esc_html( $is_he ? 'בואו רעבים. כאן אפשר לגלות מנה, להרים תבלין מהמדף, לטייל בין מטבחים, ללמוד איך טעם נוצר ולמצוא מה לקחת הביתה.' : 'Come hungry. Discover a dish, pick a spice from the shelf, travel through world kitchens, learn how flavor comes alive and find something to take home.' ); ?></p>
							<div class="c99-museum-home-actions">
								<a class="c99-button c99-museum-home-button-primary" href="<?php echo esc_url( $urls['dishes'] ); ?>"><?php echo esc_html( $is_he ? 'מה בא לי לאכול?' : 'What do I want to eat?' ); ?></a>
								<a class="c99-button c99-museum-home-button-secondary" href="<?php echo esc_url( $urls['store'] ); ?>"><?php echo esc_html( $is_he ? 'להיכנס למזווה' : 'Enter the pantry' ); ?></a>
							</div>
						</div>
						<div class="c99-museum-home-hero-media">
							<?php self::render_visual( $entity, true ); ?>
						</div>
					</div>
				</div>
			</section>

			<section id="c99-museum-cuisines" class="c99-museum-home-section c99-museum-home-cuisines" aria-labelledby="c99-museum-cuisines-title">
				<div class="c99-container">
					<div class="c99-museum-home-heading">
						<div>
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'בוחרים ארץ ומתחילים לטעום' : 'Choose a place and start tasting' ); ?></p>
							<h2 id="c99-museum-cuisines-title"><?php echo esc_html( $is_he ? 'מטבחים שאפשר להיכנס אליהם' : 'Kitchens you can step into' ); ?></h2>
						</div>
						<p><?php echo esc_html( $is_he ? 'כל דלת מובילה למנות, חומרי גלם, שיטות ומסורות. משם פשוט ממשיכים לפי מה שמסקרן או מגרה את התיאבון.' : 'Every doorway leads to dishes, ingredients, methods and traditions. Follow whatever sparks your curiosity or appetite.' ); ?></p>
					</div>
					<div class="c99-museum-home-cuisine-grid">
						<?php foreach ( $cuisines as $cuisine ) : ?>
							<a class="c99-museum-home-cuisine-card" href="<?php echo esc_url( $cuisine['url'] ); ?>">
								<?php self::render_landing_picture( $cuisine['image'], $cuisine['alt'] ); ?>
								<span class="c99-museum-home-card-copy">
									<span class="c99-museum-home-eyebrow"><?php echo esc_html( $cuisine['eyebrow'] ); ?></span>
									<strong class="c99-museum-home-card-title"><?php echo esc_html( $cuisine['title'] ); ?></strong>
									<span class="c99-museum-home-card-description"><?php echo esc_html( $cuisine['copy'] ); ?></span>
									<span class="c99-museum-home-card-cta"><?php echo esc_html( $cuisine['cta'] ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section class="c99-museum-home-section c99-museum-home-shelves" aria-labelledby="c99-museum-shelves-title">
				<div class="c99-container">
					<div class="c99-museum-home-heading">
						<div>
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'מטיילים בין המדפים' : 'Wander the shelves' ); ?></p>
							<h2 id="c99-museum-shelves-title"><?php echo esc_html( $is_he ? 'כל מדף פותח עולם אחר' : 'Every shelf opens another world' ); ?></h2>
						</div>
						<p><?php echo esc_html( $is_he ? 'אפשר להתחיל מצלחת, ממרכיב, מסיפור או ממוצר. הכל מחובר כך שקל לעבור מדבר טעים אחד לדבר הבא.' : 'Begin with a plate, an ingredient, a story or a product. Everything connects, so the next delicious discovery is always close.' ); ?></p>
					</div>
					<div class="c99-museum-home-shelf-grid">
						<?php foreach ( $shelves as $shelf ) : ?>
							<a class="c99-museum-home-shelf-card" href="<?php echo esc_url( $shelf['url'] ); ?>">
								<?php self::render_landing_picture( $shelf['image'], $shelf['alt'], 'shelf' ); ?>
								<span class="c99-museum-home-card-copy">
									<strong class="c99-museum-home-card-title"><?php echo esc_html( $shelf['title'] ); ?></strong>
									<span class="c99-museum-home-card-description"><?php echo esc_html( $shelf['copy'] ); ?></span>
									<span class="c99-museum-home-card-cta"><?php echo esc_html( $shelf['cta'] ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section class="c99-museum-home-section c99-museum-home-intents" aria-labelledby="c99-museum-intents-title">
				<div class="c99-container">
					<div class="c99-museum-home-heading c99-museum-home-heading-centered">
						<div>
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'לא צריך לדעת מאיפה להתחיל' : 'You do not need to know where to begin' ); ?></p>
							<h2 id="c99-museum-intents-title"><?php echo esc_html( $is_he ? 'פשוט בוחרים מה בא לכם עכשיו' : 'Just choose what you feel like doing' ); ?></h2>
						</div>
					</div>
					<nav class="c99-museum-home-intent-grid" aria-label="<?php echo esc_attr( $is_he ? 'בחירה לפי מה שבא לכם' : 'Choose by what you want to do' ); ?>">
						<?php foreach ( $intent_paths as $intent ) : ?>
							<a href="<?php echo esc_url( $intent['url'] ); ?>">
								<span aria-hidden="true"><?php echo esc_html( $intent['number'] ); ?></span>
								<strong><?php echo esc_html( $intent['title'] ); ?></strong>
								<small><?php echo esc_html( $intent['copy'] ); ?></small>
							</a>
						<?php endforeach; ?>
					</nav>
				</div>
			</section>

			<section class="c99-museum-home-section c99-museum-home-neighbors" aria-labelledby="c99-museum-neighbors-title">
				<div class="c99-container c99-museum-home-neighbors-grid">
					<div class="c99-museum-home-neighbors-visuals" aria-hidden="true">
						<?php self::render_landing_picture( 'assets/images/science/c99-science-syrian-regional-table-v01', '', 'neighbor' ); ?>
						<?php self::render_landing_picture( 'assets/images/science/c99-science-lebanese-regional-table-v01', '', 'neighbor' ); ?>
					</div>
					<div class="c99-museum-home-neighbors-copy">
						<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'סיפור טעים עובר בין שכנים' : 'A delicious story travels between neighbors' ); ?></p>
						<h2 id="c99-museum-neighbors-title"><?php echo esc_html( $is_he ? 'מסוריה ממשיכים ללבנון' : 'From Syria, continue into Lebanon' ); ?></h2>
						<p><?php echo esc_html( $is_he ? 'עקבו אחר אותם דגנים, עשבי תיבול, תבלינים ושיטות כשהם משנים אופי מחלב ודמשק ועד ביירות, ההרים והבקעה.' : 'Follow familiar grains, herbs, spices and methods as they change character from Aleppo and Damascus to Beirut, the mountains and the Bekaa.' ); ?></p>
						<div class="c99-museum-home-actions">
							<a class="c99-button c99-museum-home-button-primary" href="<?php echo esc_url( $urls['syrian'] ); ?>"><?php echo esc_html( $is_he ? 'להתחיל בסוריה' : 'Begin in Syria' ); ?></a>
							<a class="c99-button c99-museum-home-button-light" href="<?php echo esc_url( $urls['lebanese'] ); ?>"><?php echo esc_html( $is_he ? 'להמשיך ללבנון' : 'Continue to Lebanon' ); ?></a>
						</div>
					</div>
				</div>
			</section>

			<section class="c99-museum-home-section c99-museum-home-groups" aria-labelledby="c99-museum-groups-title">
				<div class="c99-container c99-museum-home-groups-inner">
					<div>
						<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'מהמדף אל השולחן הגדול' : 'From the shelf to a bigger table' ); ?></p>
						<h2 id="c99-museum-groups-title"><?php echo esc_html( $is_he ? 'מתכננים ארוחה לקבוצה או לחברה?' : 'Planning a meal for a group or company?' ); ?></h2>
						<p><?php echo esc_html( $is_he ? 'בחרו טעמים ומנות שאהבתם וספרו לנו על המקום, מספר האנשים והאווירה שאתם רוצים ליצור.' : 'Choose the flavors and dishes you love, then tell us about the place, the number of guests and the atmosphere you want to create.' ); ?></p>
					</div>
					<a class="c99-button c99-museum-home-button-primary" href="<?php echo esc_url( $urls['proposal'] ); ?>"><?php echo esc_html( $is_he ? 'לבקש הצעה לארוחה' : 'Request a meal proposal' ); ?></a>
				</div>
			</section>

			<?php self::render_museum_landing_sources( $entity, $is_he ); ?>
		</div>
		<?php
	}

	/**
	 * Keep supporting reading available without making it the museum entrance.
	 */
	private static function render_museum_landing_sources( $entity, $is_he ) {
		$sources = isset( $entity['sources'] ) && is_array( $entity['sources'] ) ? $entity['sources'] : array();
		if ( empty( $sources ) ) {
			return;
		}
		?>
		<section class="c99-museum-home-reading">
			<div class="c99-container">
				<details>
					<summary>
						<strong><?php echo esc_html( $is_he ? 'לקריאה נוספת' : 'For curious readers' ); ?></strong>
						<span><?php echo esc_html( $is_he ? 'ספרים, מוסדות ומאמרים שמאפשרים להעמיק עוד' : 'Books, institutions and articles for going deeper' ); ?></span>
					</summary>
					<div class="c99-museum-home-reading-content">
						<h2><?php echo esc_html( $is_he ? 'עוד מקומות טובים להעמיק בהם' : 'More good places to keep exploring' ); ?></h2>
						<ol>
							<?php foreach ( $sources as $source ) : ?>
								<?php $url = self::external_url( isset( $source['url'] ) ? $source['url'] : '' ); ?>
								<li>
									<p><strong><?php echo esc_html( $source['publisher'] ); ?></strong> <?php echo esc_html( $source['title'] ); ?></p>
									<?php if ( '' !== $url ) : ?><a class="c99-museum-source-link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'לפתוח ולקרוא' : 'Open and read' ); ?></a><?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				</details>
			</div>
		</section>
		<?php
	}

	/**
	 * Render one local, responsive landing image from a rights-governed asset.
	 */
	private static function render_landing_picture( $asset_stem, $alt, $layout = 'cuisine' ) {
		$asset_stem  = ltrim( (string) $asset_stem, '/' );
		$avif        = COMPLETE99_PLATFORM_URL . $asset_stem . '.avif';
		$avif_small  = COMPLETE99_PLATFORM_URL . $asset_stem . '-768.avif';
		$webp        = COMPLETE99_PLATFORM_URL . $asset_stem . '.webp';
		$webp_small  = COMPLETE99_PLATFORM_URL . $asset_stem . '-768.webp';
		$source_width = 1536;
		$width        = 1536;
		$height       = 1024;
		$sizes_by_layout = array(
			'cuisine'  => '(max-width: 680px) calc(100vw - 28px), (max-width: 920px) 50vw, (max-width: 1320px) 33vw, 420px',
			'shelf'    => '(max-width: 680px) calc(100vw - 28px), (max-width: 1120px) 50vw, (max-width: 1320px) 25vw, 310px',
			'neighbor' => '(max-width: 920px) 50vw, (max-width: 1320px) 27vw, 340px',
		);
		$sizes = isset( $sizes_by_layout[ $layout ] ) ? $sizes_by_layout[ $layout ] : $sizes_by_layout['cuisine'];

		if ( 'assets/images/original/c99-food-house-spread-hero-2021-wp-v01' === $asset_stem ) {
			$source_width = 1400;
			$width        = 1400;
			$height       = 788;
		}
		?>
		<picture class="c99-museum-home-picture">
			<source srcset="<?php echo esc_url( $avif_small ); ?> 768w, <?php echo esc_url( $avif ); ?> <?php echo esc_attr( $source_width ); ?>w" sizes="<?php echo esc_attr( $sizes ); ?>" type="image/avif" />
			<img src="<?php echo esc_url( $webp ); ?>" srcset="<?php echo esc_url( $webp_small ); ?> 768w, <?php echo esc_url( $webp ); ?> <?php echo esc_attr( $source_width ); ?>w" sizes="<?php echo esc_attr( $sizes ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" loading="lazy" decoding="async" />
		</picture>
		<?php
	}

	/**
	 * Render Lebanon as a public culinary journey, not an editorial dashboard.
	 */
	private static function render_lebanese_landing( $bundle ) {
		$entity = $bundle['entity'];
		$is_he  = 'he' === $bundle['language'];
		$paths  = $is_he
			? array(
				'dishes'     => '/dishes/',
				'ingredients' => '/ingredients/',
				'store'      => '/store/',
				'traditions' => '/traditions/',
				'knowledge'  => '/knowledge/',
				'proposal'   => '/request-proposal/',
				'museum'     => '/museum/',
				'syrian'     => '/museum/syrian-culinary-science/',
			)
			: array(
				'dishes'     => '/en/dishes/',
				'ingredients' => '/en/ingredients/',
				'store'      => '/en/store/',
				'traditions' => '/en/traditions/',
				'knowledge'  => '/en/knowledge/',
				'proposal'   => '/en/request-proposal/',
				'museum'     => '/en/museum/',
				'syrian'     => '/en/museum/syrian-culinary-science/',
			);
		$urls = array();
		foreach ( $paths as $key => $path ) {
			$urls[ $key ] = self::internal_url( $path );
		}

		$places = array(
			array(
				'number' => '01',
				'title'  => $is_he ? 'החוף' : 'The coast',
				'copy'   => $is_he ? 'דג, לימון, טחינה ועשבי תיבול נפגשים ליד הים בצלחות בהירות ורעננות.' : 'Fish, lemon, tahini and herbs meet by the sea in bright, fresh plates.',
			),
			array(
				'number' => '02',
				'title'  => $is_he ? 'ההרים' : 'The mountains',
				'copy'   => $is_he ? 'זעתר, שמן זית, גבינות, לחם חם ומזווה עונתי מספרים סיפור של גובה ואדמה.' : 'Zaatar, olive oil, cheeses, warm bread and a seasonal pantry tell a story of altitude and earth.',
			),
			array(
				'number' => '03',
				'title'  => $is_he ? 'הבקעה' : 'The Bekaa',
				'copy'   => $is_he ? 'דגנים, עדשים, עשבי בר ושימורי בית הופכים את העונות למדף שאפשר לפתוח כל השנה.' : 'Grains, lentils, wild greens and home preserves turn the seasons into a shelf that lasts all year.',
			),
		);

		$doors = array(
			array(
				'url'   => $urls['dishes'],
				'label' => $is_he ? 'רעבים' : 'Hungry',
				'title' => $is_he ? 'מתחילים מהצלחת' : 'Begin with the plate',
				'copy'  => $is_he ? 'מגלים מנות, טעמים ורעיונות לארוחה הבאה.' : 'Discover dishes, flavors and ideas for the next meal.',
			),
			array(
				'url'   => $urls['ingredients'],
				'label' => $is_he ? 'סקרנים' : 'Curious',
				'title' => $is_he ? 'פותחים את המזווה' : 'Open the pantry',
				'copy'  => $is_he ? 'מכירים זעתר, סומאק, דגנים, שמנים וכל מה שנותן למנה את האופי שלה.' : 'Meet zaatar, sumac, grains, oils and everything that gives a dish its character.',
			),
			array(
				'url'   => $urls['store'],
				'label' => $is_he ? 'בוחרים' : 'Choose',
				'title' => $is_he ? 'לוקחים משהו הביתה' : 'Take something home',
				'copy'  => $is_he ? 'עוברים בין מוצרי המזווה והכלים שנמצאים בחנות.' : 'Browse pantry goods and tools available in the store.',
			),
			array(
				'url'   => $urls['traditions'],
				'label' => $is_he ? 'מתארחים' : 'Gather',
				'title' => $is_he ? 'יושבים סביב השולחן' : 'Gather around the table',
				'copy'  => $is_he ? 'מגלים את האזור, הקהילה וההרגלים שנותנים לאוכל משמעות.' : 'Discover the places, communities and customs that give food its meaning.',
			),
			array(
				'url'   => $urls['knowledge'],
				'label' => $is_he ? 'לומדים' : 'Learn',
				'title' => $is_he ? 'נכנסים למדריכים' : 'Open the guides',
				'copy'  => $is_he ? 'קוראים על שיטות, מתכונים והקשרים שעוזרים לבשל ולהבין.' : 'Read about methods, recipes and connections that help you cook and understand.',
			),
			array(
				'url'   => $urls['proposal'],
				'label' => $is_he ? 'ביחד' : 'Together',
				'title' => $is_he ? 'מתכננים שולחן גדול' : 'Plan a bigger table',
				'copy'  => $is_he ? 'בוחרים כיוון לארוחה לקבוצה או לחברה ומספרים לנו מה אתם מדמיינים.' : 'Choose a direction for a group or company meal and tell us what you have in mind.',
			),
			array(
				'url'   => $urls['syrian'],
				'label' => $is_he ? 'ממשיכים לשכן' : 'Visit a neighbor',
				'title' => $is_he ? 'חוצים אל המטבח הסורי' : 'Cross into Syrian cooking',
				'copy'  => $is_he ? 'עוקבים אחרי דגנים, תבלינים ושיטות כשהם מקבלים אופי אחר מעבר לגבול.' : 'Follow grains, spices and methods as they take on a different character across the border.',
			),
			array(
				'url'   => $urls['museum'],
				'label' => $is_he ? 'מפת העולם' : 'World map',
				'title' => $is_he ? 'חוזרים למוזיאון' : 'Return to the museum',
				'copy'  => $is_he ? 'בוחרים מטבח נוסף וממשיכים את המסע הקולינרי.' : 'Choose another kitchen and continue the culinary journey.',
			),
		);
		?>
		<div class="c99-lebanon-home" id="c99-lebanon-home">
			<section class="c99-lebanon-hero" aria-labelledby="c99-lebanon-title">
				<div class="c99-container">
					<?php self::render_breadcrumbs( $entity, $bundle ); ?>
					<div class="c99-lebanon-hero-grid">
						<div class="c99-lebanon-hero-copy">
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'לבנון, מהים אל ההר' : 'Lebanon, from sea to mountain' ); ?></p>
							<h1 id="c99-lebanon-title"><?php echo esc_html( $entity['seo']['h1'] ); ?></h1>
							<p class="c99-lebanon-intro"><?php echo esc_html( $is_he ? 'לחם זעתר חם, טאבולה ירוקה, קובה מתובלת, דג מהחוף וצנצנות ששומרות את העונה. זה מטבח שמזמין לטעום את הדרך מהים, דרך ההרים ועד הבקעה.' : 'Warm zaatar bread, bright tabbouleh, spiced kibbeh, fish from the coast and jars that preserve the season. This is a kitchen that invites you to taste the road from the sea through the mountains to the Bekaa.' ); ?></p>
							<div class="c99-museum-home-actions">
								<a class="c99-button c99-museum-home-button-primary" href="<?php echo esc_url( $urls['dishes'] ); ?>"><?php echo esc_html( $is_he ? 'מה אוכלים בלבנון?' : 'What do people eat in Lebanon?' ); ?></a>
								<a class="c99-button c99-lebanon-button-secondary" href="<?php echo esc_url( $urls['ingredients'] ); ?>"><?php echo esc_html( $is_he ? 'לפתוח את המזווה' : 'Open the pantry' ); ?></a>
							</div>
						</div>
						<div class="c99-lebanon-hero-media">
							<?php self::render_visual( $entity, true ); ?>
						</div>
					</div>
				</div>
			</section>

			<section class="c99-lebanon-section c99-lebanon-places" aria-labelledby="c99-lebanon-places-title">
				<div class="c99-container">
					<div class="c99-lebanon-heading">
						<div>
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'טועמים את הנוף' : 'Taste the landscape' ); ?></p>
							<h2 id="c99-lebanon-places-title"><?php echo esc_html( $is_he ? 'הצלחת משתנה עם הדרך' : 'The plate changes with the road' ); ?></h2>
						</div>
						<p><?php echo esc_html( $is_he ? 'מרחק קצר יכול לשנות את חומרי הגלם, את הריח ואת מה שמניחים במרכז השולחן.' : 'A short journey can change the ingredients, aromas and what takes the center of the table.' ); ?></p>
					</div>
					<div class="c99-lebanon-place-grid">
						<?php foreach ( $places as $place ) : ?>
							<article>
								<span aria-hidden="true"><?php echo esc_html( $place['number'] ); ?></span>
								<h3><?php echo esc_html( $place['title'] ); ?></h3>
								<p><?php echo esc_html( $place['copy'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section class="c99-lebanon-section c99-lebanon-doors" aria-labelledby="c99-lebanon-doors-title">
				<div class="c99-container">
					<div class="c99-lebanon-heading">
						<div>
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'בוחרים איך להמשיך' : 'Choose your next step' ); ?></p>
							<h2 id="c99-lebanon-doors-title"><?php echo esc_html( $is_he ? 'שמונה דלתות מהשולחן' : 'Eight doors from the table' ); ?></h2>
						</div>
						<p><?php echo esc_html( $is_he ? 'אפשר לעבור מהמנה למרכיב, מהמסורת לחנות, או להמשיך ישר אל שולחן אחר.' : 'Move from a dish to an ingredient, from a tradition to the store, or continue straight to another table.' ); ?></p>
					</div>
					<nav class="c99-lebanon-door-grid" aria-label="<?php echo esc_attr( $is_he ? 'דרכים להמשיך מהמטבח הלבנוני' : 'Ways to continue from Lebanese cooking' ); ?>">
						<?php foreach ( $doors as $door ) : ?>
							<a href="<?php echo esc_url( $door['url'] ); ?>">
								<span><?php echo esc_html( $door['label'] ); ?></span>
								<strong><?php echo esc_html( $door['title'] ); ?></strong>
								<small><?php echo esc_html( $door['copy'] ); ?></small>
								<b aria-hidden="true"><?php echo esc_html( $is_he ? '←' : '→' ); ?></b>
							</a>
						<?php endforeach; ?>
					</nav>
				</div>
			</section>

			<section class="c99-lebanon-invitation" aria-labelledby="c99-lebanon-invitation-title">
				<div class="c99-container c99-lebanon-invitation-inner">
					<div>
						<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'השולחן יכול לגדול' : 'The table can grow' ); ?></p>
						<h2 id="c99-lebanon-invitation-title"><?php echo esc_html( $is_he ? 'רוצים ארוחה לקבוצה או לחברה?' : 'Want a meal for a group or company?' ); ?></h2>
						<p><?php echo esc_html( $is_he ? 'ספרו לנו אילו טעמים משכו אתכם, למי הארוחה מיועדת ואיזה שולחן אתם רוצים ליצור.' : 'Tell us which flavors drew you in, who the meal is for and what kind of table you want to create.' ); ?></p>
					</div>
					<a class="c99-button c99-museum-home-button-primary" href="<?php echo esc_url( $urls['proposal'] ); ?>"><?php echo esc_html( $is_he ? 'לבקש הצעה לארוחה' : 'Request a meal proposal' ); ?></a>
				</div>
			</section>

			<?php self::render_museum_landing_sources( $entity, $is_he ); ?>
		</div>
		<?php
	}

	/**
	 * Render the Japanese cooking gateway from its approved public projection.
	 * Member order is presentation order only and never changes entity ownership.
	 */
	private static function render_collection_page( $bundle, $collection ) {
		$entity       = $bundle['entity'];
		$lang         = $bundle['language'];
		$is_he        = 'he' === $lang;
		$member_count = count( $collection['members'] );
		?>
		<section class="c99-museum-hero c99-foundations-hero">
			<div class="c99-container">
				<?php self::render_breadcrumbs( $entity, $bundle ); ?>
				<div class="c99-museum-hero-grid">
					<div class="c99-museum-hero-copy">
						<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'שער לבישול היפני' : 'Explore Japanese cooking' ); ?></p>
						<h1><?php echo esc_html( isset( $entity['seo']['h1'] ) ? $entity['seo']['h1'] : $entity['name'] ); ?></h1>
						<p class="c99-museum-lede"><?php echo esc_html( isset( $entity['seo']['opening'] ) ? $entity['seo']['opening'] : $entity['summary'] ); ?></p>
						<div class="c99-museum-fact-strip" aria-label="<?php echo esc_attr( $is_he ? 'פרטי העמוד' : 'Page details' ); ?>">
							<span><strong><?php echo esc_html( $member_count ); ?></strong> <?php echo esc_html( $is_he ? 'נושאים לבחירה' : 'topics to explore' ); ?></span>
							<span><strong><?php echo esc_html( count( $collection['groups'] ) ); ?></strong> <?php echo esc_html( $is_he ? 'דרכי גילוי' : 'ways to explore' ); ?></span>
							<span><strong><?php echo esc_html( $is_he ? 'עודכן' : 'Updated' ); ?></strong> <time datetime="<?php echo esc_attr( $entity['trust']['substantive_updated_at'] ); ?>"><?php echo esc_html( $entity['trust']['substantive_updated_at'] ); ?></time></span>
						</div>
					</div>
					<?php self::render_visual( $entity, true ); ?>
				</div>
			</div>
		</section>

		<div class="c99-container c99-foundations-layout">
			<div class="c99-foundations-main">
				<section class="c99-foundations-lab" aria-labelledby="c99-foundations-title" data-c99-foundations-filter data-c99-foundations-canonical-url="<?php echo esc_url( $bundle['canonical_url'] ); ?>" data-c99-foundations-query="<?php echo esc_attr( self::COLLECTION_FILTER_QUERY ); ?>">
					<div class="c99-foundations-heading">
						<div>
							<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'בחרו מאיפה להתחיל' : 'Choose where to start' ); ?></p>
							<h2 id="c99-foundations-title"><?php echo esc_html( $is_he ? 'חומרי גלם, טעם, שיטות וכלים' : 'Ingredients, flavor, methods and tools' ); ?></h2>
						</div>
						<p><?php echo esc_html( $is_he ? 'בחרו מה מסקרן אתכם וגלו מה קונים, איך משתמשים בו, למה הוא טעים ואיזה כלי מתאים.' : 'Choose what interests you and discover what to buy, how to use it, why it tastes the way it does and which tool fits.' ); ?></p>
					</div>

					<div class="c99-foundations-filter-shell">
						<div class="c99-foundations-filter-bar" role="group" aria-label="<?php echo esc_attr( $is_he ? 'סינון נושאי יסוד' : 'Filter foundation topics' ); ?>">
							<button type="button" class="c99-foundations-filter is-active" data-c99-foundations-filter-reset data-c99-foundations-filter-value="all" aria-controls="c99-foundations-grid" aria-pressed="true"><?php echo esc_html( $is_he ? 'הכל' : 'All' ); ?></button>
							<?php foreach ( $collection['groups'] as $group ) : ?>
								<button type="button" class="c99-foundations-filter" data-c99-foundations-filter-button="<?php echo esc_attr( $group['id'] ); ?>" data-c99-foundations-filter-value="<?php echo esc_attr( $group['id'] ); ?>" aria-controls="c99-foundations-grid" aria-pressed="false"><?php echo esc_html( $group['label'] ); ?></button>
							<?php endforeach; ?>
						</div>
						<p class="c99-foundations-result-count"><span data-c99-foundations-filter-count aria-live="polite" aria-atomic="true"><?php echo esc_html( self::collection_count_label( $member_count, $lang ) ); ?></span></p>
					</div>

					<div class="c99-foundations-group-guide">
						<?php foreach ( $collection['groups'] as $group ) : ?>
							<article data-c99-foundations-group-guide="<?php echo esc_attr( $group['id'] ); ?>">
								<h3><?php echo esc_html( $group['label'] ); ?></h3>
								<p><?php echo esc_html( $group['description'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>

					<div id="c99-foundations-grid" class="c99-foundations-grid" data-c99-foundations-grid>
						<?php foreach ( $collection['members'] as $member ) : ?>
							<?php $url = self::collection_member_url( $member ); ?>
							<article class="c99-foundations-card" data-c99-foundations-member data-c99-foundations-group="<?php echo esc_attr( $member['group_id'] ); ?>">
								<a href="<?php echo esc_url( $url ); ?>">
									<span class="c99-foundations-card-type"><?php echo esc_html( self::entity_type_label( $member['entity_type'], $lang ) ); ?></span>
									<h3><?php echo esc_html( $member['name'] ); ?></h3>
									<p><?php echo esc_html( $member['summary'] ); ?></p>
									<strong><?php echo esc_html( self::collection_member_cta( $member['entity_type'], $lang ) ); ?></strong>
								</a>
							</article>
						<?php endforeach; ?>
					</div>
					<p class="c99-foundations-empty" data-c99-foundations-filter-empty hidden><?php echo esc_html( $is_he ? 'אין נושאים תואמים בסינון הזה.' : 'No topics match this filter.' ); ?></p>
				</section>

				<?php self::render_profiles( $entity, 2 ); ?>
				<?php self::render_facts( $entity, 2 ); ?>
				<?php self::render_sources( $entity, 2 ); ?>
			</div>
			<aside class="c99-museum-side-column c99-foundations-side" aria-label="<?php echo esc_attr( $is_he ? 'מידע משלים' : 'Supporting information' ); ?>">
				<?php self::render_taxonomy( $entity ); ?>
				<?php self::render_safety( $entity ); ?>
				<?php self::render_trust( $entity, $bundle ); ?>
			</aside>
		</div>
		<?php
	}

	private static function collection_count_label( $count, $lang ) {
		$count = max( 0, absint( $count ) );
		if ( 'he' === $lang ) {
			return 1 === $count ? 'נושא אחד מוצג' : $count . ' נושאים מוצגים';
		}
		return 1 === $count ? '1 topic shown' : $count . ' topics shown';
	}

	private static function collection_member_cta( $entity_type, $lang ) {
		$is_he = 'he' === $lang;
		$labels = array(
			'ingredient' => array( 'להכיר את המרכיב', 'Learn about the ingredient' ),
			'molecule'   => array( 'להבין את המדע', 'Understand the science' ),
			'reaction'   => array( 'להבין את המדע', 'Understand the science' ),
			'guide'      => array( 'להבין את המדע', 'Understand the science' ),
			'preparation' => array( 'ללמוד את השיטה', 'Learn the method' ),
			'technique'  => array( 'ללמוד את השיטה', 'Learn the method' ),
			'equipment'  => array( 'לבחור את הכלי', 'Choose the tool' ),
		);
		$type = strtolower( trim( (string) $entity_type ) );
		if ( ! isset( $labels[ $type ] ) ) {
			return $is_he ? 'לגלות עוד' : 'Explore more';
		}
		return $labels[ $type ][ $is_he ? 0 : 1 ];
	}

	private static function render_offer( $entity ) {
		$offer = isset( $entity['offer'] ) && is_array( $entity['offer'] ) ? $entity['offer'] : array();
		$code  = sanitize_key( (string) ( $offer['product_code'] ?? '' ) );
		if ( '' === $code
			|| ! class_exists( 'Complete99_Commerce' )
			|| ! Complete99_Commerce::catalog_is_ready()
			|| ! function_exists( 'wc_get_product_id_by_sku' )
			|| ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$product_id = absint( wc_get_product_id_by_sku( $code ) );
		$product    = $product_id ? wc_get_product( $product_id ) : false;
		if ( ! $product
			|| 'yes' !== (string) get_post_meta( $product_id, '_complete99_live_catalog_managed', true )
			|| $code !== (string) get_post_meta( $product_id, '_complete99_catalog_product_code', true )
			|| 'yes' !== (string) get_post_meta( $product_id, Complete99_Commerce::PRODUCT_APPROVED, true ) ) {
			return;
		}
		$is_he = 'he' === self::$bundle['language'];
		$name  = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::NAME_HE : Complete99_Commerce::NAME_EN, true );
		$url   = self::internal_url( (string) ( $offer['store_path'] ?? '' ) );
		if ( '' === trim( $name ) || '' === $url ) {
			return;
		}
		?>
		<section class="c99-museum-side-card c99-museum-offer" aria-labelledby="c99-museum-offer-title">
			<p class="c99-museum-card-label"><?php echo esc_html( $is_he ? 'במזווה' : 'In the pantry' ); ?></p>
			<h2 id="c99-museum-offer-title"><?php echo esc_html( $name ); ?></h2>
			<p class="c99-museum-offer-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
			<p><?php echo esc_html( $product->is_in_stock() ? ( $is_he ? 'במלאי' : 'In stock' ) : ( $is_he ? 'אזל זמנית' : 'Temporarily out of stock' ) ); ?></p>
			<a class="c99-button c99-button-primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $offer['label'] ); ?></a>
		</section>
		<?php
	}

	private static function render_breadcrumbs( $entity, $bundle ) {
		$items = isset( $entity['seo']['visible_breadcrumbs'] ) && is_array( $entity['seo']['visible_breadcrumbs'] )
			? $entity['seo']['visible_breadcrumbs']
			: array();
		if ( empty( $items ) ) {
			return;
		}
		$is_he = 'he' === $bundle['language'];
		?>
		<nav class="c99-museum-breadcrumbs" aria-label="<?php echo esc_attr( $is_he ? 'פירורי לחם' : 'Breadcrumbs' ); ?>">
			<ol>
				<?php foreach ( $items as $offset => $item ) : ?>
					<?php
					$path       = isset( $item['path'] ) ? (string) $item['path'] : '';
					$url        = self::internal_url( $path );
					$is_current = $path === $bundle['canonical_path'] || $offset === count( $items ) - 1;
					?>
					<li>
						<?php if ( $is_current || '' === $url ) : ?>
							<span<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
	}

	private static function render_visual( $entity, $priority = false ) {
		$visual = isset( $entity['visual'] ) && is_array( $entity['visual'] ) ? $entity['visual'] : array();
		$url    = self::visual_url( $visual );
		if ( '' === $url ) {
			return;
		}
		$alt     = isset( $visual['alt'] ) ? (string) $visual['alt'] : (string) $entity['name'];
		$width   = isset( $visual['width'] ) ? max( 1, absint( $visual['width'] ) ) : 1536;
		$height  = isset( $visual['height'] ) ? max( 1, absint( $visual['height'] ) ) : 1024;
		$loading = $priority ? 'eager' : 'lazy';
		$avif    = ! empty( $visual['avif_url'] ) ? self::safe_visual_url( $visual['avif_url'] ) : '';
		$small_url  = ! empty( $visual['small_url'] ) ? self::safe_visual_url( $visual['small_url'] ) : '';
		$small_avif = ! empty( $visual['small_avif_url'] ) ? self::safe_visual_url( $visual['small_avif_url'] ) : '';
		$sizes   = $priority
			? '(max-width: 680px) calc(100vw - 40px), (max-width: 920px) calc(100vw - 56px), 52vw'
			: '(max-width: 680px) calc(100vw - 40px), (max-width: 920px) calc(100vw - 56px), 760px';
		?>
		<figure class="c99-museum-visual">
			<picture>
				<?php if ( '' !== $avif ) : ?><source srcset="<?php if ( '' !== $small_avif ) : ?><?php echo esc_url( $small_avif ); ?> 768w, <?php endif; ?><?php echo esc_url( $avif ); ?> <?php echo esc_attr( $width ); ?>w" sizes="<?php echo esc_attr( $sizes ); ?>" type="image/avif" /><?php endif; ?>
				<img src="<?php echo esc_url( $url ); ?>"<?php if ( '' !== $small_url ) : ?> srcset="<?php echo esc_url( $small_url ); ?> 768w, <?php echo esc_url( $url ); ?> <?php echo esc_attr( $width ); ?>w"<?php endif; ?> alt="<?php echo esc_attr( $alt ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" sizes="<?php echo esc_attr( $sizes ); ?>" loading="<?php echo esc_attr( $loading ); ?>" decoding="async"<?php echo $priority ? ' fetchpriority="high"' : ''; ?> />
			</picture>
			<?php if ( ! empty( $visual['caption'] ) ) : ?><figcaption><?php echo esc_html( $visual['caption'] ); ?></figcaption><?php endif; ?>
		</figure>
		<?php
	}

	private static function render_profiles( $entity, $heading_level ) {
		$profiles = isset( $entity['profiles'] ) && is_array( $entity['profiles'] ) ? $entity['profiles'] : array();
		if ( empty( $profiles ) ) {
			return;
		}
		$lang = self::$bundle['language'];
		$tag  = 4 <= $heading_level ? 'h4' : ( 3 === $heading_level ? 'h3' : 'h2' );
		?>
		<section class="c99-museum-section c99-museum-profiles" aria-labelledby="c99-museum-profiles-<?php echo esc_attr( $entity['id'] ); ?>">
			<<?php echo esc_attr( $tag ); ?> id="c99-museum-profiles-<?php echo esc_attr( $entity['id'] ); ?>"><?php echo esc_html( 'he' === $lang ? 'חמש זוויות להבנת הנושא' : 'Five lenses on the subject' ); ?></<?php echo esc_attr( $tag ); ?>>
			<div class="c99-museum-profile-grid">
				<?php foreach ( $profiles as $dimension => $profile ) : ?>
					<article class="c99-museum-profile-card">
						<p class="c99-museum-card-label"><?php echo esc_html( self::dimension_label( $dimension, $lang ) ); ?></p>
						<p><?php echo esc_html( $profile['summary'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_facts( $entity, $heading_level ) {
		$facts = isset( $entity['facts'] ) && is_array( $entity['facts'] ) ? $entity['facts'] : array();
		if ( empty( $facts ) ) {
			return;
		}
		$lang        = self::$bundle['language'];
		$source_map  = self::source_number_map( $entity );
		$heading_tag = 4 <= $heading_level ? 'h4' : ( 3 === $heading_level ? 'h3' : 'h2' );
		?>
		<section class="c99-museum-section c99-museum-evidence" aria-labelledby="c99-museum-facts-<?php echo esc_attr( $entity['id'] ); ?>">
			<<?php echo esc_attr( $heading_tag ); ?> id="c99-museum-facts-<?php echo esc_attr( $entity['id'] ); ?>"><?php echo esc_html( 'he' === $lang ? 'מה ידוע ומה נמדד' : 'What is known and measured' ); ?></<?php echo esc_attr( $heading_tag ); ?>>
			<div class="c99-museum-evidence-list">
				<?php foreach ( $facts as $fact ) : ?>
					<article class="c99-museum-evidence-card">
						<div class="c99-museum-evidence-meta">
							<span><?php echo esc_html( self::dimension_label( $fact['dimension'], $lang ) ); ?></span>
							<span><?php echo esc_html( self::evidence_label( $fact['evidence_class'], $lang ) ); ?></span>
						</div>
						<p><?php echo esc_html( $fact['statement'] ); ?><?php self::render_source_markers( $fact['source_ids'], $source_map, $entity['id'] ); ?></p>
						<?php self::render_scientific_measurements( $fact, $lang ); ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_scientific_measurements( $fact, $lang ) {
		$measurements = isset( $fact['scientific_measurements'] ) && is_array( $fact['scientific_measurements'] ) ? $fact['scientific_measurements'] : array();
		if ( empty( $measurements ) ) {
			return;
		}
		?>
		<div class="c99-museum-measurements" role="group" aria-label="<?php echo esc_attr( 'he' === $lang ? 'מדידות מדעיות' : 'Scientific measurements' ); ?>">
			<?php foreach ( $measurements as $measurement ) : ?>
				<?php
				$value = 'range' === $measurement['kind']
					? self::format_number( $measurement['low'] ) . ' - ' . self::format_number( $measurement['high'] )
					: self::format_number( $measurement['value'] );
				?>
				<dl>
					<div><dt><?php echo esc_html( self::machine_label( $measurement['property'] ) ); ?></dt><dd><?php echo esc_html( trim( $value . ' ' . $measurement['unit'] ) ); ?></dd></div>
					<div><dt><?php echo esc_html( 'he' === $lang ? 'שיטה' : 'Method' ); ?></dt><dd><?php echo esc_html( $measurement['method'] ); ?></dd></div>
					<div><dt><?php echo esc_html( 'he' === $lang ? 'היקף' : 'Scope' ); ?></dt><dd><?php echo esc_html( self::machine_label( $measurement['specimen_scope'] ) ); ?></dd></div>
				</dl>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_sections( $sections, $bundle ) {
		if ( empty( $sections ) ) {
			return;
		}
		$is_he = 'he' === $bundle['language'];
		?>
		<section class="c99-museum-section c99-museum-map" aria-labelledby="c99-museum-map-title">
			<div class="c99-museum-section-heading">
				<p class="c99-museum-kicker"><?php echo esc_html( $is_he ? 'מסלולים להמשך' : 'Ways to explore' ); ?></p>
				<h2 id="c99-museum-map-title"><?php echo esc_html( $is_he ? 'לגלות את הנושא' : 'Explore the topic' ); ?></h2>
			</div>
			<div class="c99-museum-section-stack">
				<?php foreach ( $sections as $section ) : ?>
					<?php $section_id = ! empty( $section['seo']['section_id'] ) ? $section['seo']['section_id'] : $section['slug']; ?>
					<section id="<?php echo esc_attr( $section_id ); ?>" class="c99-museum-subsection">
						<div class="c99-museum-subsection-intro">
							<div><p class="c99-museum-card-label"><?php echo esc_html( self::entity_type_label( $section['type'], $bundle['language'] ) ); ?></p><h3><?php echo esc_html( $section['name'] ); ?></h3></div>
							<p><?php echo esc_html( $section['summary'] ); ?></p>
						</div>
						<?php self::render_visual( $section ); ?>
						<?php self::render_facts( $section, 4 ); ?>
						<?php self::render_connections( $section, 4 ); ?>
						<?php self::render_sources( $section, 4 ); ?>
					</section>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_connections( $entity, $heading_level ) {
		$links     = isset( $entity['internal_links'] ) && is_array( $entity['internal_links'] ) ? $entity['internal_links'] : array();
		$relations = isset( $entity['relations'] ) && is_array( $entity['relations'] ) ? $entity['relations'] : array();
		if ( empty( $links ) && empty( $relations ) ) {
			return;
		}
		$lang        = self::$bundle['language'];
		$tag         = 4 <= $heading_level ? 'h4' : ( 3 === $heading_level ? 'h3' : 'h2' );
		$source_map  = self::source_number_map( $entity );
		$target_urls = array();
		foreach ( $links as $link ) {
			if ( ! empty( $link['target_id'] ) && ! empty( $link['url'] ) ) {
				$target_urls[ $link['target_id'] ] = self::internal_url( $link['url'] );
			}
		}
		?>
		<section class="c99-museum-section c99-museum-connections" aria-labelledby="c99-museum-links-<?php echo esc_attr( $entity['id'] ); ?>">
			<<?php echo esc_attr( $tag ); ?> id="c99-museum-links-<?php echo esc_attr( $entity['id'] ); ?>"><?php echo esc_html( 'he' === $lang ? 'ממשיכים דרך הקשרים' : 'Continue through the connections' ); ?></<?php echo esc_attr( $tag ); ?>>
			<?php if ( ! empty( $links ) ) : ?>
				<div class="c99-museum-link-grid">
					<?php foreach ( $links as $link ) : ?>
						<?php $url = self::internal_url( isset( $link['url'] ) ? $link['url'] : '' ); ?>
						<?php if ( '' === $url ) { continue; } ?>
						<a class="c99-museum-link-card" href="<?php echo esc_url( $url ); ?>">
							<span><?php echo esc_html( self::relationship_label( isset( $link['relationship'] ) ? $link['relationship'] : 'related', $lang ) ); ?></span>
							<strong><?php echo esc_html( isset( $link['anchor'] ) ? $link['anchor'] : $link['context'] ); ?></strong>
							<?php if ( ! empty( $link['context'] ) && $link['context'] !== $link['anchor'] ) : ?><small><?php echo esc_html( $link['context'] ); ?></small><?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $relations ) ) : ?>
				<div class="c99-museum-relation-list">
					<?php foreach ( $relations as $relation ) : ?>
						<article>
							<p class="c99-museum-card-label"><?php echo esc_html( self::relationship_label( $relation['type'], $lang ) ); ?></p>
							<p><?php echo esc_html( $relation['note'] ); ?><?php self::render_source_markers( $relation['source_ids'], $source_map, $entity['id'] ); ?></p>
							<?php if ( ! empty( $target_urls[ $relation['target_id'] ] ) ) : ?><a class="c99-museum-source-link" href="<?php echo esc_url( $target_urls[ $relation['target_id'] ] ); ?>"><?php echo esc_html( 'he' === $lang ? 'לנושא הקשור' : 'Explore the related topic' ); ?></a><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	private static function render_market_context( $entity ) {
		$items = isset( $entity['market_context'] ) && is_array( $entity['market_context'] ) ? $entity['market_context'] : array();
		if ( empty( $items ) ) {
			return;
		}
		$is_he = 'he' === self::$bundle['language'];
		?>
		<section class="c99-museum-side-card c99-museum-market" aria-labelledby="c99-museum-market-title">
			<p class="c99-museum-card-label"><?php echo esc_html( $is_he ? 'תצפית שוק מתוארכת' : 'Dated market observation' ); ?></p>
			<h2 id="c99-museum-market-title"><?php echo esc_html( $is_he ? 'מחיר בשוק המקור' : 'Source-market price' ); ?></h2>
			<div class="c99-museum-market-list">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$amount   = isset( $item['amount'] ) && is_numeric( $item['amount'] ) ? self::format_number( $item['amount'] ) : '';
					$currency = isset( $item['currency'] ) ? (string) $item['currency'] : '';
					$source   = isset( $item['source_url'] ) ? self::external_url( $item['source_url'] ) : '';
					?>
					<article>
						<?php if ( ! empty( $item['label'] ) ) : ?><h3><?php echo esc_html( $item['label'] ); ?></h3><?php endif; ?>
						<?php if ( '' !== $amount && '' !== $currency ) : ?><p class="c99-museum-market-price"><strong><?php echo esc_html( $amount ); ?></strong> <?php echo esc_html( $currency ); ?></p><?php endif; ?>
						<?php if ( isset( $item['normalized_amount'], $item['normalized_unit'] ) && is_numeric( $item['normalized_amount'] ) ) : ?><p><?php echo esc_html( self::format_number( $item['normalized_amount'] ) . ' ' . $currency . ' / ' . $item['normalized_unit'] ); ?></p><?php endif; ?>
						<dl>
							<?php self::render_market_term( $is_he ? 'שוק' : 'Market', isset( $item['market'] ) ? $item['market'] : '' ); ?>
							<?php self::render_market_term( $is_he ? 'מוכר' : 'Seller', isset( $item['seller'] ) ? $item['seller'] : '' ); ?>
							<?php self::render_market_term( $is_he ? 'נצפה בתאריך' : 'Observed', isset( $item['observed_at'] ) ? $item['observed_at'] : '' ); ?>
							<?php self::render_market_term( $is_he ? 'זמינות בעת הבדיקה' : 'Availability at review', isset( $item['availability'] ) ? self::market_value_label( $item['availability'], $is_he ) : '' ); ?>
							<?php self::render_market_term( $is_he ? 'בסיס השוואה' : 'Comparability', isset( $item['comparability'] ) ? self::market_value_label( $item['comparability'], $is_he ) : '' ); ?>
							<?php self::render_market_term( $is_he ? 'מצב מס' : 'Tax state', isset( $item['tax_state'] ) ? self::market_value_label( $item['tax_state'], $is_he ) : '' ); ?>
							<?php self::render_market_term( $is_he ? 'משלוח' : 'Shipping', isset( $item['shipping_state'] ) ? self::market_value_label( $item['shipping_state'], $is_he ) : '' ); ?>
						</dl>
						<?php if ( ! empty( $item['scope_note'] ) ) : ?><p class="c99-museum-market-scope"><?php echo esc_html( $item['scope_note'] ); ?></p><?php endif; ?>
						<?php if ( '' !== $source ) : ?><a class="c99-museum-source-link" href="<?php echo esc_url( $source ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'לרישום המקור' : 'View source listing' ); ?></a><?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_market_term( $label, $value ) {
		if ( '' === trim( (string) $value ) ) {
			return;
		}
		echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
	}

	private static function market_value_label( $value, $is_he ) {
		$labels = array(
			'quantity_selector_visible' => array( 'ניתן היה לבחור כמות', 'Quantity selection available' ),
			'in_stock'                  => array( 'במלאי בעת הבדיקה', 'In stock at review' ),
			'listed_for_sale'           => array( 'הוצע למכירה בעת הבדיקה', 'Listed for sale at review' ),
			'price_listed'              => array( 'מחיר הוצג בעת הבדיקה', 'Price displayed at review' ),
			'out_of_stock'              => array( 'אזל בעת הבדיקה', 'Out of stock at review' ),
			'low_stock'                 => array( 'מלאי נמוך בעת הבדיקה', 'Low stock at review' ),
			'like_for_like'             => array( 'השוואה בין גרסאות מקבילות', 'Like-for-like comparison' ),
			'partially_comparable'      => array( 'השוואה חלקית', 'Partially comparable' ),
			'non_comparable'            => array( 'לא מיועד להשוואת מחיר ישירה', 'Not directly comparable' ),
			'included'                  => array( 'כלול במחיר המקור', 'Included in source price' ),
			'excluded'                  => array( 'לא כלול במחיר המקור', 'Excluded from source price' ),
			'unknown'                   => array( 'לא צוין במקור', 'Not stated by source' ),
		);
		$key = sanitize_key( (string) $value );
		return isset( $labels[ $key ] ) ? $labels[ $key ][ $is_he ? 0 : 1 ] : self::machine_label( $value );
	}

	private static function render_taxonomy( $entity ) {
		$taxonomy = isset( $entity['taxonomy'] ) && is_array( $entity['taxonomy'] ) ? $entity['taxonomy'] : array();
		if ( empty( $taxonomy['category_path'] ) && empty( $taxonomy['attributes'] ) && empty( $taxonomy['tags'] ) ) {
			return;
		}
		$is_he = 'he' === self::$bundle['language'];
		?>
		<section class="c99-museum-side-card c99-museum-taxonomy" aria-labelledby="c99-museum-taxonomy-title">
			<p class="c99-museum-card-label"><?php echo esc_html( $is_he ? 'נושאים ומאפיינים' : 'Topics and characteristics' ); ?></p>
			<h2 id="c99-museum-taxonomy-title"><?php echo esc_html( $is_he ? 'ההקשר הקולינרי במבט אחד' : 'Culinary context at a glance' ); ?></h2>
			<?php if ( ! empty( $taxonomy['category_path'] ) ) : ?><p class="c99-museum-category-path"><?php echo esc_html( implode( ' / ', array_map( array( __CLASS__, 'machine_label' ), $taxonomy['category_path'] ) ) ); ?></p><?php endif; ?>
			<?php if ( ! empty( $taxonomy['attributes'] ) ) : ?>
				<dl><?php foreach ( $taxonomy['attributes'] as $key => $values ) : ?><div><dt><?php echo esc_html( self::machine_label( $key ) ); ?></dt><dd><?php echo esc_html( implode( ', ', array_map( array( __CLASS__, 'machine_label' ), $values ) ) ); ?></dd></div><?php endforeach; ?></dl>
			<?php endif; ?>
			<?php if ( ! empty( $taxonomy['tags'] ) ) : ?><div class="c99-museum-tags"><?php foreach ( $taxonomy['tags'] as $tag ) : ?><span><?php echo esc_html( self::machine_label( $tag ) ); ?></span><?php endforeach; ?></div><?php endif; ?>
		</section>
		<?php
	}

	private static function render_safety( $entity ) {
		$notes = isset( $entity['safety_notes'] ) && is_array( $entity['safety_notes'] ) ? $entity['safety_notes'] : array();
		if ( empty( $notes ) ) {
			return;
		}
		$is_he = 'he' === self::$bundle['language'];
		?>
		<section class="c99-museum-side-card c99-museum-safety" aria-labelledby="c99-museum-safety-title">
			<p class="c99-museum-card-label"><?php echo esc_html( $is_he ? 'שימוש אחראי' : 'Responsible use' ); ?></p>
			<h2 id="c99-museum-safety-title"><?php echo esc_html( $is_he ? 'בטיחות, אחסון ורגולציה' : 'Safety, storage and compliance' ); ?></h2>
			<ul><?php foreach ( $notes as $note ) : ?><li><?php echo esc_html( self::clean_compliance_note( $note ) ); ?></li><?php endforeach; ?></ul>
		</section>
		<?php
	}

	private static function render_trust( $entity, $bundle ) {
		$trust = isset( $entity['trust'] ) && is_array( $entity['trust'] ) ? $entity['trust'] : array();
		if ( empty( $trust ) ) {
			return;
		}
		$is_he          = 'he' === $bundle['language'];
		$correction_url = isset( $trust['correction_path'] ) ? self::internal_url( $trust['correction_path'] ) : '';
		?>
		<section class="c99-museum-side-card c99-museum-trust" aria-labelledby="c99-museum-trust-title">
			<p class="c99-museum-card-label"><?php echo esc_html( $is_he ? 'שיטת מחקר' : 'Research method' ); ?></p>
			<h2 id="c99-museum-trust-title"><?php echo esc_html( $is_he ? 'איך אנחנו בודקים את המידע' : 'How we check the information' ); ?></h2>
			<p><?php echo esc_html( isset( $trust['research_method'] ) ? $trust['research_method'] : '' ); ?></p>
			<?php if ( ! empty( $trust['next_review_trigger'] ) ) : ?><p><?php echo esc_html( $trust['next_review_trigger'] ); ?></p><?php endif; ?>
			<?php if ( '' !== $correction_url ) : ?><a class="c99-museum-source-link" href="<?php echo esc_url( $correction_url ); ?>"><?php echo esc_html( $is_he ? 'שליחת תיקון מבוסס' : 'Submit a sourced correction' ); ?></a><?php endif; ?>
		</section>
		<?php
	}

	private static function render_sources( $entity, $heading_level ) {
		$sources = isset( $entity['sources'] ) && is_array( $entity['sources'] ) ? $entity['sources'] : array();
		if ( empty( $sources ) ) {
			return;
		}
		$lang = self::$bundle['language'];
		$tag  = 4 <= $heading_level ? 'h4' : ( 3 === $heading_level ? 'h3' : 'h2' );
		?>
		<section class="c99-museum-section c99-museum-sources" aria-labelledby="c99-museum-sources-<?php echo esc_attr( $entity['id'] ); ?>">
			<<?php echo esc_attr( $tag ); ?> id="c99-museum-sources-<?php echo esc_attr( $entity['id'] ); ?>"><?php echo esc_html( 'he' === $lang ? 'מקורות וציטוטים' : 'Sources and citations' ); ?></<?php echo esc_attr( $tag ); ?>>
			<ol>
				<?php foreach ( $sources as $source ) : ?>
					<?php $url = self::external_url( isset( $source['url'] ) ? $source['url'] : '' ); ?>
					<li id="c99-source-<?php echo esc_attr( sanitize_html_class( $entity['id'] . '-' . $source['id'] ) ); ?>">
						<p><strong><?php echo esc_html( $source['publisher'] ); ?></strong>, <?php echo esc_html( $source['title'] ); ?>.</p>
						<p class="c99-museum-source-meta"><?php echo esc_html( self::source_type_label( $source['type'], $lang ) ); ?><?php if ( ! empty( $source['published_at'] ) ) : ?> · <?php echo esc_html( $source['published_at'] ); ?><?php endif; ?> · <?php echo esc_html( 'he' === $lang ? 'נבדק' : 'retrieved' ); ?> <?php echo esc_html( $source['retrieved_at'] ); ?></p>
						<?php if ( '' !== $url ) : ?><a class="c99-museum-source-link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( 'he' === $lang ? 'פתיחת המקור' : 'Open source' ); ?></a><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php
	}

	private static function render_source_markers( $source_ids, $source_map, $entity_id ) {
		$source_label = 'he' === self::$bundle['language'] ? 'מקור ' : 'Source ';
		foreach ( $source_ids as $source_id ) {
			if ( ! isset( $source_map[ $source_id ] ) ) {
				continue;
			}
			echo ' <sup class="c99-museum-citation"><a href="#c99-source-' . esc_attr( sanitize_html_class( $entity_id . '-' . $source_id ) ) . '" aria-label="' . esc_attr( $source_label . $source_map[ $source_id ] ) . '">[' . esc_html( $source_map[ $source_id ] ) . ']</a></sup>';
		}
	}

	private static function source_number_map( $entity ) {
		$map = array();
		foreach ( $entity['sources'] as $offset => $source ) {
			$map[ $source['id'] ] = $offset + 1;
		}
		return $map;
	}

	/**
	 * Accept only the compact public collection contract attached to this page.
	 * The renderer never derives members from the private registry.
	 */
	private static function approved_collection_projection( $bundle ) {
		if ( ! is_array( $bundle ) || ! isset( $bundle['collection'] ) || ! is_array( $bundle['collection'] ) ) {
			return array();
		}
		$collection = $bundle['collection'];
		$expected   = array(
			'schema',
			'key',
			'language',
			'translation_group_id',
			'canonical_path',
			'alternate_path',
			'approved_public',
			'groups',
			'members',
			'parity_member_ids',
		);
		if ( ! self::has_exact_keys( $collection, $expected )
			|| self::COLLECTION_SCHEMA !== $collection['schema']
			|| true !== $collection['approved_public']
			|| ! isset( $bundle['language'], $bundle['canonical_path'], $bundle['alternates'] )
			|| $collection['language'] !== $bundle['language']
			|| $collection['canonical_path'] !== $bundle['canonical_path']
			|| ! is_string( $collection['key'] )
			|| ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $collection['key'] )
			|| ! is_string( $collection['translation_group_id'] )
			|| ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $collection['translation_group_id'] )
			|| ! self::is_collection_path( $collection['canonical_path'], $collection['language'] )
			|| ! self::is_collection_path( $collection['alternate_path'], 'he' === $collection['language'] ? 'en' : 'he' )
			|| ! self::is_same_site_url( $bundle['alternates'][ 'he' === $collection['language'] ? 'en' : 'he' ], $collection['alternate_path'] )
			|| ! self::is_list( $collection['groups'] )
			|| ! self::is_list( $collection['members'] )
			|| ! is_array( $collection['parity_member_ids'] ) ) {
			return array();
		}

		$expected_group_ids = array( 'ingredients', 'food_science', 'techniques', 'equipment' );
		$actual_group_ids   = array();
		if ( 4 !== count( $collection['groups'] ) ) {
			return array();
		}
		foreach ( $collection['groups'] as $group ) {
			if ( ! is_array( $group )
				|| ! self::has_exact_keys( $group, array( 'id', 'label', 'description' ) )
				|| ! is_string( $group['id'] )
				|| ! is_string( $group['label'] )
				|| ! is_string( $group['description'] )
				|| '' === trim( $group['label'] )
				|| '' === trim( $group['description'] ) ) {
				return array();
			}
			$actual_group_ids[] = $group['id'];
		}
		if ( $expected_group_ids !== $actual_group_ids ) {
			return array();
		}

		$member_ids       = array();
		$member_targets   = array();
		$populated_groups = array_fill_keys( $expected_group_ids, false );
		$blocked_types    = array(
			'supplier',
			'producer',
			'market_observation',
			'retail_listing',
			'equipment_shop',
			'guide_edition',
			'visual_asset',
			'compliance_rule',
		);
		foreach ( $collection['members'] as $member ) {
			if ( ! is_array( $member )
				|| ! self::has_exact_keys(
					$member,
					array( 'id', 'group_id', 'name', 'summary', 'entity_type', 'canonical_path', 'owner_entity_id', 'route_mode', 'approved_public' ),
					array( 'fragment' )
				)
				|| true !== $member['approved_public']
				|| ! is_string( $member['id'] )
				|| ! is_string( $member['owner_entity_id'] )
				|| ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $member['id'] )
				|| ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $member['owner_entity_id'] )
				|| ! in_array( $member['group_id'], $expected_group_ids, true )
				|| ! is_string( $member['name'] )
				|| ! is_string( $member['summary'] )
				|| '' === trim( $member['name'] )
				|| '' === trim( $member['summary'] )
				|| ! is_string( $member['entity_type'] )
				|| in_array( $member['entity_type'], $blocked_types, true )
				|| ! in_array( $member['route_mode'], array( 'standalone', 'section' ), true )
				|| ! self::is_collection_path( $member['canonical_path'], $collection['language'] ) ) {
				return array();
			}

			$fragment = isset( $member['fragment'] ) ? (string) $member['fragment'] : '';
			if ( ( 'standalone' === $member['route_mode'] && ( $member['id'] !== $member['owner_entity_id'] || '' !== $fragment ) )
				|| ( 'section' === $member['route_mode'] && ( $member['id'] === $member['owner_entity_id'] || ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $fragment ) ) ) ) {
				return array();
			}
			$target = self::collection_member_url( $member );
			if ( '' === $target || isset( $member_ids[ $member['id'] ] ) || isset( $member_targets[ $target ] ) ) {
				return array();
			}
			$member_ids[ $member['id'] ]       = true;
			$member_targets[ $target ]         = true;
			$populated_groups[ $member['group_id'] ] = true;
		}
		if ( empty( $member_ids ) || in_array( false, $populated_groups, true ) ) {
			return array();
		}

		$parity = $collection['parity_member_ids'];
		if ( ! self::has_exact_keys( $parity, array( 'he', 'en' ) )
			|| ! self::is_list( $parity['he'] )
			|| ! self::is_list( $parity['en'] )
			|| $parity['he'] !== $parity['en']
			|| array_keys( $member_ids ) !== $parity[ $collection['language'] ]
			|| count( array_unique( $parity['he'] ) ) !== count( $parity['he'] ) ) {
			return array();
		}

		return $collection;
	}

	private static function has_exact_keys( $value, $required, $optional = array() ) {
		if ( ! is_array( $value ) || self::is_list( $value ) ) {
			return false;
		}
		$actual = array_keys( $value );
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $value ) ) {
				return false;
			}
		}
		return empty( array_diff( $actual, array_merge( $required, $optional ) ) );
	}

	private static function is_collection_path( $path, $language ) {
		if ( ! is_string( $path )
			|| ! preg_match( '#^/(?:[a-z0-9-]+/)+$#', $path )
			|| ( 'en' === $language && 0 !== strpos( $path, '/en/' ) )
			|| ( 'he' === $language && 0 === strpos( $path, '/en/' ) ) ) {
			return false;
		}
		return true;
	}

	private static function collection_member_url( $member ) {
		if ( ! is_array( $member ) || empty( $member['canonical_path'] ) ) {
			return '';
		}
		$path = (string) $member['canonical_path'];
		if ( ! preg_match( '#^/(?:[a-z0-9-]+/)+$#', $path ) ) {
			return '';
		}
		$url      = home_url( $path );
		$fragment = isset( $member['fragment'] ) ? (string) $member['fragment'] : '';
		if ( '' !== $fragment ) {
			if ( ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $fragment ) ) {
				return '';
			}
			$url .= '#' . $fragment;
		}
		return $url;
	}

	private static function schema_graph( $bundle ) {
		$entity      = $bundle['entity'];
		$collection  = self::approved_collection_projection( $bundle );
		$description = isset( $entity['seo']['meta_description'] ) ? (string) $entity['seo']['meta_description'] : (string) $entity['summary'];
		$schema_type = isset( $entity['seo']['schema_type'] ) ? (string) $entity['seo']['schema_type'] : 'WebPage';
		$page_types  = array( 'WebPage', 'CollectionPage', 'Article', 'ProfilePage' );
		$page_type   = ! empty( $collection ) ? 'CollectionPage' : ( in_array( $schema_type, $page_types, true ) ? $schema_type : 'WebPage' );
		$breadcrumbs = array();
		foreach ( $entity['seo']['visible_breadcrumbs'] as $offset => $item ) {
			$breadcrumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $offset + 1,
				'name'     => $item['label'],
				'item'     => self::internal_url( $item['path'] ),
			);
		}
		$citations = array();
		$citation_entities = array_merge( array( $entity ), $bundle['sections'] );
		foreach ( $citation_entities as $citation_entity ) {
			foreach ( $citation_entity['sources'] as $source ) {
				$url = self::external_url( $source['url'] );
				if ( '' !== $url ) {
					$citations[] = $url;
				}
			}
		}
		$citations = array_values( array_unique( $citations ) );
		$organization_id = home_url( '/' ) . '#organization';
		$website_id      = home_url( '/' ) . '#website';
		$page_id         = $bundle['canonical_url'] . '#webpage';
		$language        = 'he' === $bundle['language'] ? 'he-IL' : 'en';
		$page = array(
			'@type'            => $page_type,
			'@id'              => $page_id,
			'url'              => $bundle['canonical_url'],
			'name'             => isset( $entity['seo']['title'] ) ? $entity['seo']['title'] : $entity['name'],
			'headline'         => isset( $entity['seo']['h1'] ) ? $entity['seo']['h1'] : $entity['name'],
			'description'      => $description,
			'inLanguage'       => $language,
			'isAccessibleForFree' => true,
			'isPartOf'         => array( '@id' => $website_id ),
			'breadcrumb'       => array( '@id' => $bundle['canonical_url'] . '#breadcrumb' ),
			'citation'         => $citations,
			'dateModified'     => $entity['trust']['substantive_updated_at'],
		);
		if ( 'Article' === $page_type ) {
			$page['author']    = array( '@id' => $organization_id );
			$page['publisher'] = array( '@id' => $organization_id );
			$published_at      = isset( $entity['reviewed_at'] ) ? (string) $entity['reviewed_at'] : '';
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $published_at ) ) {
				$page['datePublished'] = $published_at;
			}
		}
		$image = self::visual_url( isset( $entity['visual'] ) ? $entity['visual'] : array() );
		if ( '' !== $image ) {
			$page['primaryImageOfPage'] = array( '@type' => 'ImageObject', 'url' => $image );
		}
		if ( 'WebPage' === $page_type && ! in_array( $schema_type, $page_types, true ) && preg_match( '/^[A-Za-z][A-Za-z0-9]*$/', $schema_type ) ) {
			$page['mainEntity'] = array(
				'@type'       => $schema_type,
				'@id'         => $bundle['canonical_url'] . '#entity',
				'name'        => $entity['name'],
				'description' => $entity['summary'],
			);
		}
		$owned_entities = array();
		foreach ( $bundle['sections'] as $section ) {
			$section_schema_type = isset( $section['seo']['schema_type'] ) ? (string) $section['seo']['schema_type'] : '';
			if ( ! in_array( $section_schema_type, array( 'ChemicalSubstance', 'DefinedTerm', 'CollectionPage' ), true ) ) {
				continue;
			}
			$section_fragment = isset( $section['seo']['section_id'] ) ? (string) $section['seo']['section_id'] : (string) $section['slug'];
			$section_fragment = sanitize_html_class( $section_fragment );
			if ( '' === $section_fragment ) {
				continue;
			}
			$section_url       = $bundle['canonical_url'] . '#' . $section_fragment;
			$section_citations = array();
			foreach ( isset( $section['sources'] ) && is_array( $section['sources'] ) ? $section['sources'] : array() as $source ) {
				$source_url = self::external_url( isset( $source['url'] ) ? $source['url'] : '' );
				if ( '' !== $source_url ) {
					$section_citations[] = $source_url;
				}
			}
			$owned_entity = array(
				'@type'       => $section_schema_type,
				'@id'         => $section_url,
				'url'         => $section_url,
				'name'        => $section['name'],
				'description' => $section['summary'],
				'inLanguage'  => $language,
				'isPartOf'    => array( '@id' => $page_id ),
			);
			if ( ! empty( $section_citations ) ) {
				$owned_entity['citation'] = array_values( array_unique( $section_citations ) );
			}
			$owned_entities[] = $owned_entity;
		}
		if ( ! empty( $owned_entities ) ) {
			$owned_entity_references = array_map(
				static function ( $owned_entity ) {
					return array( '@id' => $owned_entity['@id'] );
				},
				$owned_entities
			);
			if ( empty( $page['mainEntity'] ) ) {
				$page['mainEntity'] = $owned_entity_references;
			}
			if ( 'Article' === $page_type ) {
				$page['about'] = $owned_entity_references;
			}
		}

		$collection_list = array();
		if ( ! empty( $collection ) ) {
			$list_id       = $bundle['canonical_url'] . '#foundations-item-list';
			$list_elements = array();
			foreach ( $collection['members'] as $offset => $member ) {
				$member_url = self::collection_member_url( $member );
				$list_elements[] = array(
					'@type'    => 'ListItem',
					'position' => $offset + 1,
					'item'     => array(
						'@id'         => $member_url,
						'url'         => $member_url,
						'name'        => $member['name'],
						'description' => $member['summary'],
					),
				);
			}
			$collection_list = array(
				'@type'           => 'ItemList',
				'@id'             => $list_id,
				'name'            => $entity['name'],
				'numberOfItems'   => count( $list_elements ),
				'itemListElement' => $list_elements,
			);
			$page['mainEntity'] = array( '@id' => $list_id );
		}

		$graph = array(
			array(
				'@type' => 'Organization',
				'@id'   => $organization_id,
				'url'   => home_url( '/' ),
				'name'  => 'Complete99',
			),
			array(
				'@type'       => 'WebSite',
				'@id'         => $website_id,
				'url'         => home_url( '/' ),
				'name'        => 'Complete99',
				'inLanguage'  => array( 'he-IL', 'en' ),
				'publisher'   => array( '@id' => $organization_id ),
			),
			$page,
			array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $bundle['canonical_url'] . '#breadcrumb',
				'itemListElement' => $breadcrumbs,
			),
		);
		$graph = array_merge( $graph, $owned_entities );
		if ( ! empty( $collection_list ) ) {
			$graph[] = $collection_list;
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}

	private static function request_paths() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path || '\\' === substr( $path, 0, 1 )
			|| preg_match( '#(?:\\\\|//|/(?:\.|\.\.)(?:/|$)|%2f|%5c|%00)#i', $path ) ) {
			return array();
		}

		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_base = is_string( $home_path ) ? rtrim( $home_path, '/' ) : '';
		if ( '' !== $home_base ) {
			if ( $path !== $home_base && 0 !== strpos( $path, $home_base . '/' ) ) {
				return array();
			}
			$path = substr( $path, strlen( $home_base ) );
		}
		if ( '' === $path ) {
			$path = '/';
		}
		if ( '/' !== substr( $path, 0, 1 ) ) {
			$path = '/' . $path;
		}

		$lookup = '/' === $path ? '/' : rtrim( $path, '/' ) . '/';
		return array( 'request' => $path, 'lookup' => $lookup );
	}

	private static function is_renderable_bundle( $bundle, $lookup_path ) {
		if ( ! is_array( $bundle ) || self::is_list( $bundle ) ) {
			return false;
		}
		$expected = array( 'schema', 'version', 'language', 'entity', 'sections', 'canonical_path', 'canonical_url', 'alternates', 'indexable' );
		if ( array_key_exists( 'collection', $bundle ) ) {
			$expected[] = 'collection';
		}
		$actual   = array_keys( $bundle );
		sort( $actual, SORT_STRING );
		sort( $expected, SORT_STRING );
		if ( $actual !== $expected
			|| 'complete99-culinary-science-page-bundle/v1' !== $bundle['schema']
			|| ! is_string( $bundle['version'] ) || '' === trim( $bundle['version'] )
			|| ! in_array( $bundle['language'], array( 'he', 'en' ), true )
			|| ! is_array( $bundle['entity'] )
			|| empty( $bundle['entity']['id'] )
			|| empty( $bundle['entity']['seo']['canonical_path'] )
			|| 'standalone' !== $bundle['entity']['seo']['route_mode']
			|| ! self::is_list( $bundle['sections'] )
			|| ! is_bool( $bundle['indexable'] )
			|| $bundle['canonical_path'] !== $lookup_path
			|| $bundle['entity']['seo']['canonical_path'] !== $bundle['canonical_path']
			|| '/' !== substr( $bundle['canonical_path'], -1 )
			|| ! self::is_same_site_url( $bundle['canonical_url'], $bundle['canonical_path'] ) ) {
			return false;
		}
		if ( ( 'en' === $bundle['language'] && 0 !== strpos( $bundle['canonical_path'], '/en/' ) )
			|| ( 'he' === $bundle['language'] && 0 === strpos( $bundle['canonical_path'], '/en/' ) ) ) {
			return false;
		}
		$alternate_keys = is_array( $bundle['alternates'] ) ? array_keys( $bundle['alternates'] ) : array();
		sort( $alternate_keys, SORT_STRING );
		if ( array( 'en', 'he', 'x-default' ) !== $alternate_keys ) {
			return false;
		}
		if ( $bundle['alternates'][ $bundle['language'] ] !== $bundle['canonical_url']
			|| $bundle['alternates']['x-default'] !== $bundle['alternates']['he'] ) {
			return false;
		}
		foreach ( $bundle['alternates'] as $alternate ) {
			if ( ! self::is_same_site_url( $alternate ) ) {
				return false;
			}
		}
		foreach ( $bundle['sections'] as $section ) {
			if ( ! is_array( $section ) || empty( $section['id'] ) || empty( $section['seo'] ) ) {
				return false;
			}
		}
		if ( array_key_exists( 'collection', $bundle ) && empty( self::approved_collection_projection( $bundle ) ) ) {
			return false;
		}
		return true;
	}

	private static function is_same_site_url( $url, $expected_path = '' ) {
		$url_parts  = wp_parse_url( (string) $url );
		$home_parts = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $url_parts ) || ! is_array( $home_parts )
			|| empty( $url_parts['scheme'] ) || ! in_array( strtolower( $url_parts['scheme'] ), array( 'http', 'https' ), true )
			|| empty( $home_parts['scheme'] ) || strtolower( $url_parts['scheme'] ) !== strtolower( $home_parts['scheme'] )
			|| empty( $url_parts['host'] ) || empty( $home_parts['host'] )
			|| strtolower( $url_parts['host'] ) !== strtolower( $home_parts['host'] )
			|| isset( $url_parts['user'] ) || isset( $url_parts['pass'] )
			|| isset( $url_parts['query'] ) || isset( $url_parts['fragment'] ) ) {
			return false;
		}
		if ( '' !== $expected_path ) {
			$expected_parts = wp_parse_url( home_url( $expected_path ) );
			if ( ! is_array( $expected_parts )
				|| ! isset( $url_parts['path'], $expected_parts['path'] )
				|| $url_parts['path'] !== $expected_parts['path'] ) {
				return false;
			}
		}
		return true;
	}

	private static function internal_url( $path ) {
		$path = (string) $path;
		if ( '' === $path ) {
			return '';
		}
		if ( 0 === strpos( $path, '/' ) && 0 !== strpos( $path, '//' ) ) {
			return home_url( $path );
		}
		return self::is_same_site_url( $path ) ? $path : '';
	}

	private static function external_url( $url ) {
		$url = esc_url_raw( (string) $url, array( 'https' ) );
		return 0 === strpos( $url, 'https://' ) ? $url : '';
	}

	private static function visual_url( $visual ) {
		if ( ! is_array( $visual ) ) {
			return '';
		}
		foreach ( array( 'url', 'webp_url', 'src' ) as $key ) {
			if ( ! empty( $visual[ $key ] ) ) {
				return self::safe_visual_url( $visual[ $key ] );
			}
		}
		return '';
	}

	private static function safe_visual_url( $value ) {
		$value = (string) $value;
		if ( 0 === strpos( $value, '/' ) && 0 !== strpos( $value, '//' ) ) {
			return home_url( $value );
		}
		return self::is_same_site_url( $value ) ? $value : '';
	}

	private static function clean_compliance_note( $note ) {
		$note = trim( (string) $note );
		if ( preg_match( '/^\[COMPLIANCE_NOTE:\s*(.+)\]$/u', $note, $match ) ) {
			return trim( $match[1] );
		}
		return $note;
	}

	private static function dimension_label( $dimension, $lang ) {
		$labels = array(
			'scientific'    => array( 'מדעי ומולקולרי', 'Scientific and molecular' ),
			'cultural'      => array( 'תרבות ומורשת', 'Culture and heritage' ),
			'institutional' => array( 'מוסדות וסמכות', 'Institutions and authority' ),
			'economic'      => array( 'כלכלה ושוק', 'Economics and market' ),
			'structural'    => array( 'מבנה וקשרים', 'Structure and relationships' ),
		);
		return isset( $labels[ $dimension ] ) ? $labels[ $dimension ][ 'he' === $lang ? 0 : 1 ] : self::machine_label( $dimension );
	}

	private static function evidence_label( $evidence, $lang ) {
		$labels = array(
			'peer_reviewed'          => array( 'מחקר שעבר ביקורת עמיתים', 'Peer-reviewed study' ),
			'peer_reviewed_paper'    => array( 'מאמר שעבר ביקורת עמיתים', 'Peer-reviewed paper' ),
			'peer_reviewed_context'  => array( 'הקשר מחקרי', 'Peer-reviewed context' ),
			'official_source'         => array( 'מקור רשמי', 'Official source' ),
			'third_party_guide'       => array( 'מדריך צד שלישי', 'Third-party guide' ),
			'conference_context'      => array( 'הקשר מכנס מקצועי', 'Conference context' ),
			'regulatory_standard'     => array( 'תקן רגולטורי', 'Regulatory standard' ),
			'supplier_declaration'    => array( 'הצהרת ספק', 'Supplier declaration' ),
			'lot_coa'                 => array( 'תעודת אנליזה לאצווה', 'Lot certificate of analysis' ),
			'market_observation'      => array( 'תצפית שוק מתוארכת', 'Dated market observation' ),
			'editorial_inference'     => array( 'הסקה מערכתית', 'Editorial inference' ),
			'official_government'     => array( 'מקור ממשלתי רשמי', 'Official government source' ),
			'official_organization'   => array( 'מקור ארגוני רשמי', 'Official organization source' ),
			'official_standard'       => array( 'תקן רשמי', 'Official standard' ),
			'conference_proceeding'   => array( 'פרסום כנס', 'Conference proceeding' ),
			'official_market_listing' => array( 'רישום שוק רשמי', 'Official market listing' ),
			'official_business'       => array( 'מקור עסקי רשמי', 'Official business source' ),
			'regulatory_guidance'     => array( 'הנחיה רגולטורית', 'Regulatory guidance' ),
		);
		return isset( $labels[ $evidence ] ) ? $labels[ $evidence ][ 'he' === $lang ? 0 : 1 ] : self::machine_label( $evidence );
	}

	private static function source_type_label( $type, $lang ) {
		return self::evidence_label( $type, $lang );
	}

	private static function entity_type_label( $type, $lang ) {
		$labels = array(
			'topic_hub'              => array( 'מרכז ידע', 'Knowledge hub' ),
			'cuisine'                => array( 'מטבח ותרבות', 'Cuisine and culture' ),
			'tradition'              => array( 'מסורת', 'Tradition' ),
			'dish'                   => array( 'מנה', 'Dish' ),
			'preparation'            => array( 'הכנה', 'Preparation' ),
			'ingredient'             => array( 'חומר גלם', 'Ingredient' ),
			'molecule'               => array( 'מולקולה', 'Molecule' ),
			'reaction'               => array( 'תגובה כימית', 'Chemical reaction' ),
			'technique'              => array( 'טכניקה', 'Technique' ),
			'guide'                  => array( 'מדריך', 'Guide' ),
			'comparison'             => array( 'השוואה', 'Comparison' ),
			'equipment'              => array( 'ציוד', 'Equipment' ),
			'material_specification' => array( 'מפרט חומר', 'Material specification' ),
			'quality_grade'          => array( 'דרגת איכות', 'Quality grade' ),
			'culinary_institution'   => array( 'מוסד קולינרי', 'Culinary institution' ),
			'institution'            => array( 'מוסד', 'Institution' ),
			'restaurant'             => array( 'מסעדה', 'Restaurant' ),
			'market'                 => array( 'שוק', 'Market' ),
			'equipment_shop'         => array( 'חנות ציוד', 'Equipment shop' ),
			'producer'               => array( 'יצרן', 'Producer' ),
			'supplier'               => array( 'ספק', 'Supplier' ),
			'market_observation'     => array( 'תצפית שוק', 'Market observation' ),
			'retail_listing'         => array( 'רישום קמעונאי', 'Retail listing' ),
			'standard'               => array( 'תקן', 'Standard' ),
			'geographical_indication' => array( 'ציון גאוגרפי', 'Geographical indication' ),
			'guide_edition'          => array( 'מהדורת מדריך', 'Guide edition' ),
			'alternative'            => array( 'חלופה', 'Alternative' ),
			'visual_asset'           => array( 'נכס חזותי', 'Visual asset' ),
			'compliance_rule'        => array( 'כלל ציות', 'Compliance rule' ),
		);
		return isset( $labels[ $type ] )
			? $labels[ $type ][ 'he' === $lang ? 0 : 1 ]
			: ( 'he' === $lang ? 'פריט קולינרי' : self::machine_label( $type ) );
	}

	private static function relationship_label( $value, $lang ) {
		$key = strtolower( trim( str_replace( '_', '-', (string) $value ) ) );
		$labels = array(
			'related'           => array( 'קשר נוסף', 'Related' ),
			'part-of'           => array( 'חלק מתוך', 'Part of' ),
			'contains'          => array( 'מכיל', 'Contains' ),
			'used-in'           => array( 'משמש בתוך', 'Used in' ),
			'requires'          => array( 'דורש', 'Requires' ),
			'produced-by'       => array( 'מיוצר על ידי', 'Produced by' ),
			'sold-by'           => array( 'נמכר על ידי', 'Sold by' ),
			'sourced-from'      => array( 'מסופק ממקור', 'Sourced from' ),
			'observed-at'       => array( 'נצפה אצל', 'Observed at' ),
			'graded-as'         => array( 'מדורג בתור', 'Graded as' ),
			'specified-by'      => array( 'מוגדר על ידי', 'Specified by' ),
			'certified-by'      => array( 'מאושר על ידי', 'Certified by' ),
			'recognized-in'     => array( 'מוכר במסגרת', 'Recognized in' ),
			'recognizes'        => array( 'מכיר ב', 'Recognizes' ),
			'complements'       => array( 'משלים', 'Complements' ),
			'substitutes'       => array( 'חלופה עבור', 'Substitutes' ),
			'upgrades-to'       => array( 'משתדרג אל', 'Upgrades to' ),
			'located-at'        => array( 'נמצא ב', 'Located at' ),
			'supported-by'      => array( 'נתמך על ידי', 'Supported by' ),
			'benchmarks'        => array( 'משמש אמת מידה', 'Benchmarks' ),
			'teaches'           => array( 'מלמד', 'Teaches' ),
			'serves'            => array( 'מגיש', 'Serves' ),
			'references'        => array( 'מפנה אל', 'References' ),
			'parent-context'    => array( 'חזרה לנושא האב', 'Parent topic' ),
			'child-discovery'   => array( 'המשך לתת-נושא', 'Explore subtopic' ),
			'curated-discovery' => array( 'המשך מומלץ', 'Recommended next' ),
			'cross-sell'        => array( 'השלמה קולינרית', 'Culinary pairing' ),
			'up-sell'           => array( 'חלופת פרימיום', 'Premium alternative' ),
		);
		if ( 0 === strpos( $key, 'related-' ) ) {
			$base_key = substr( $key, strlen( 'related-' ) );
			if ( isset( $labels[ $base_key ] ) ) {
				return $labels[ $base_key ][ 'he' === $lang ? 0 : 1 ];
			}
		}
		if ( isset( $labels[ $key ] ) ) {
			return $labels[ $key ][ 'he' === $lang ? 0 : 1 ];
		}
		return 'he' === $lang ? 'קשר נוסף' : ucwords( trim( str_replace( '-', ' ', $key ) ) );
	}

	private static function machine_label( $value ) {
		$key  = strtolower( trim( str_replace( '_', '-', (string) $value ) ) );
		$lang = isset( self::$bundle['language'] ) ? self::$bundle['language'] : 'en';
		$labels = array(
			'pa-origin' => array( 'מקור', 'Origin' ),
			'pa-region' => array( 'אזור', 'Region' ),
			'pa-community' => array( 'מסורת וקהילה', 'Tradition and community' ),
			'pa-species' => array( 'מין', 'Species' ),
			'pa-cultivar' => array( 'זן', 'Cultivar' ),
			'pa-processing-method' => array( 'שיטת עיבוד', 'Processing method' ),
			'pa-fermentation-method' => array( 'שיטת התססה', 'Fermentation method' ),
			'pa-vessel' => array( 'כלי תהליך', 'Process vessel' ),
			'pa-flavor-profile' => array( 'פרופיל טעם', 'Flavor profile' ),
			'pa-allergens' => array( 'אלרגנים', 'Allergens' ),
			'pa-storage-type' => array( 'סוג אחסון', 'Storage type' ),
			'pa-material' => array( 'חומר', 'Material' ),
			'pa-steel' => array( 'סוג פלדה', 'Steel type' ),
			'pa-handedness' => array( 'יד דומיננטית', 'Handedness' ),
			'pa-quality-grade' => array( 'דרגת איכות', 'Quality grade' ),
			'pa-market' => array( 'שוק', 'Market' ),
			'pa-institution-type' => array( 'סוג מוסד', 'Institution type' ),
			'pa-equipment-required' => array( 'ציוד נדרש', 'Equipment required' ),
			'part-of' => array( 'חלק מתוך', 'Part of' ),
			'contains' => array( 'מכיל', 'Contains' ),
			'used-in' => array( 'משמש בתוך', 'Used in' ),
			'requires' => array( 'דורש', 'Requires' ),
			'produced-by' => array( 'מיוצר על ידי', 'Produced by' ),
			'complements' => array( 'משלים', 'Complements' ),
			'supported-by' => array( 'נתמך על ידי', 'Supported by' ),
			'parent-context' => array( 'חזרה לנושא האב', 'Parent topic' ),
			'curated-discovery' => array( 'המשך מומלץ', 'Recommended next' ),
			'world-cuisines' => array( 'מטבחי עולם', 'World cuisines' ),
			'culinary-museum' => array( 'מוזיאון קולינרי', 'Culinary museum' ),
			'cuisines' => array( 'מטבחי עולם', 'World cuisines' ),
			'syrian-culinary-science' => array( 'המטבח הסורי', 'Syrian cuisine' ),
			'syrian-cuisine' => array( 'מטבח סורי', 'Syrian cuisine' ),
			'syria-national' => array( 'סוריה', 'Syria' ),
			'syrian-multi-community' => array( 'מסורות סוריות רב קהילתיות', 'Syrian multi-community traditions' ),
			'culinary-science' => array( 'מדע הקולינריה', 'Culinary science' ),
			'knowledge-graph' => array( 'ידע מקושר', 'Connected knowledge' ),
			'topic-clusters' => array( 'נושאים קשורים', 'Related topics' ),
			'topic-cluster' => array( 'נושא קשור', 'Related topic' ),
			'japanese-cuisine' => array( 'מטבח יפני', 'Japanese cuisine' ),
			'japanese-heritage' => array( 'מורשת יפנית', 'Japanese heritage' ),
			'japanese-culinary-techniques' => array( 'טכניקות קולינריות יפניות', 'Japanese culinary techniques' ),
			'japanese-premium-ingredients' => array( 'חומרי גלם יפניים מובחרים', 'Japanese premium ingredients' ),
			'japanese-professional-equipment' => array( 'ציוד יפני מקצועי', 'Professional Japanese equipment' ),
			'japanese-food-science' => array( 'מדע האוכל היפני', 'Japanese food science' ),
			'japan' => array( 'יפן', 'Japan' ),
			'washoku' => array( 'וואשוקו', 'Washoku' ),
			'seasonality' => array( 'עונתיות', 'Seasonality' ),
			'knowledge' => array( 'מרכז הידע', 'Knowledge' ),
			'food-science' => array( 'מדע המזון', 'Food science' ),
			'taste' => array( 'טעם', 'Taste' ),
			'umami' => array( 'אומאמי', 'Umami' ),
			'dashi' => array( 'דאשי', 'Dashi' ),
			'dashi-ingredients' => array( 'חומרי גלם לדאשי', 'Dashi ingredients' ),
			'first-stock' => array( 'ציר ראשון', 'First stock' ),
			'controlled-water-extraction' => array( 'מיצוי מבוקר במים', 'Controlled water extraction' ),
			'sauces-and-fermentation' => array( 'רטבים והתססות', 'Sauces and fermentation' ),
			'shoyu' => array( 'שויו', 'Shoyu' ),
			'kioke' => array( 'קיוקה', 'Kioke' ),
			'kombu' => array( 'קומבו', 'Kombu' ),
			'katsuobushi' => array( 'קצואובושי', 'Katsuobushi' ),
			'bonito' => array( 'בוניטו', 'Bonito' ),
			'seaweed' => array( 'אצת ים', 'Seaweed' ),
			'smoking' => array( 'עישון', 'Smoking' ),
			'seasonings' => array( 'תיבול', 'Seasonings' ),
			'mirin' => array( 'מירין', 'Mirin' ),
			'hon-mirin' => array( 'הון מירין', 'Hon mirin' ),
			'alcohol' => array( 'אלכוהול', 'Alcohol' ),
			'glaze' => array( 'זיגוג', 'Glaze' ),
			'citrus' => array( 'הדרים', 'Citrus' ),
			'yuzu' => array( 'יוזו', 'Yuzu' ),
			'kito' => array( 'קיטו', 'Kito' ),
			'kito-gi' => array( 'ציון גאוגרפי קיטו', 'Kito geographical indication' ),
			'gi' => array( 'GI', 'GI' ),
			'product-specific' => array( 'לפי המוצר', 'Product-specific' ),
			'product-specific-kito-eligibility-required' => array( 'נדרשת התאמה למפרט קיטו', 'Product-specific Kito eligibility required' ),
			'dried-seaweed-product-specific' => array( 'אצת ים מיובשת לפי המוצר', 'Dried seaweed, product-specific' ),
			'cooked-smoked-dried-product-specific' => array( 'מבושל, מעושן ומיובש לפי המוצר', 'Cooked, smoked and dried, product-specific' ),
			'eutrema-japonicum-product-specific' => array( 'Eutrema japonicum לפי המוצר', 'Eutrema japonicum, product-specific' ),
			'whole-fruit-or-derived-product' => array( 'פרי שלם או מוצר נגזר', 'Whole fruit or derived product' ),
			'koji-fermentation' => array( 'התססת קוג׳י', 'Koji fermentation' ),
			'koji-saccharification-in-alcohol' => array( 'סכריפיקציית קוג׳י באלכוהול', 'Koji saccharification in alcohol' ),
			'kioke-wooden-barrel' => array( 'חבית עץ קיוקה', 'Kioke wooden barrel' ),
			'verify-sku-label' => array( 'יש לבדוק את תווית המוצר', 'Check the product label' ),
			'product-label-required' => array( 'נדרשת תווית מוצר', 'Product label required' ),
			'refrigerated-perishable' => array( 'מתכלה בקירור', 'Refrigerated perishable' ),
			'volatile-pungency' => array( 'חריפות נדיפה', 'Volatile pungency' ),
			'fresh-aromatics' => array( 'ארומטים טריים', 'Fresh aromatics' ),
			'fresh-rhizome' => array( 'קנה שורש טרי', 'Fresh rhizome' ),
			'wasabi' => array( 'וואסבי', 'Wasabi' ),
			'fresh-wasabi' => array( 'וואסבי טרי', 'Fresh wasabi' ),
			'aroma-and-pungency' => array( 'ארומה וחריפות', 'Aroma and pungency' ),
			'isothiocyanates' => array( 'איזותיוציאנטים', 'Isothiocyanates' ),
			'isothiocyanate' => array( 'איזותיוציאנט', 'Isothiocyanate' ),
			'professional-equipment' => array( 'ציוד מקצועי', 'Professional equipment' ),
			'japanese-tools' => array( 'כלים יפניים', 'Japanese tools' ),
			'wasabi-tools' => array( 'כלים לוואסבי', 'Wasabi tools' ),
			'graters' => array( 'מגררות', 'Graters' ),
			'fresh-wasabi-preparation' => array( 'הכנת וואסבי טרי', 'Fresh wasabi preparation' ),
			'wasabi-grater' => array( 'מגררת וואסבי', 'Wasabi grater' ),
			'oroshi' => array( 'אורושי', 'Oroshi' ),
			'hagane-zame' => array( 'הגאנה-זאמה', 'Hagane-zame' ),
			'aromatic-citrus' => array( 'הדרי ארומטי', 'Aromatic citrus' ),
			'umami-synergy' => array( 'סינרגיית אומאמי', 'Umami synergy' ),
			'fermentation' => array( 'התססה', 'Fermentation' ),
			'koji' => array( 'קוג׳י', 'Koji' ),
			'glutamate' => array( 'גלוטמט', 'Glutamate' ),
			'imp' => array( 'IMP', 'IMP' ),
			'aitc' => array( 'AITC', 'AITC' ),
			'myrosinase' => array( 'מירוזינאז', 'Myrosinase' ),
			'limonene' => array( 'לימונן', 'Limonene' ),
			'yuzunone' => array( 'יוזונון', 'Yuzunone' ),
			't1r1-t1r3' => array( 'T1R1/T1R3', 'T1R1/T1R3' ),
			'fish' => array( 'דגים', 'Fish' ),
		);
		if ( isset( $labels[ $key ] ) ) {
			return $labels[ $key ][ 'he' === $lang ? 0 : 1 ];
		}
		return ucwords( trim( str_replace( '-', ' ', $key ) ) );
	}

	private static function format_number( $value ) {
		if ( ! is_numeric( $value ) ) {
			return '';
		}
		$decimals = floor( (float) $value ) === (float) $value ? 0 : 2;
		return number_format_i18n( (float) $value, $decimals );
	}

	private static function is_list( $value ) {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $value );
		}
		return is_array( $value ) && ( empty( $value ) || array_keys( $value ) === range( 0, count( $value ) - 1 ) );
	}
}
