<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Frontend {
	public static function boot() {
		add_filter( 'post_type_link', array( 'Complete99_Content', 'filter_post_type_link' ), 10, 2 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ) );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'document_title' ) );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_head', array( __CLASS__, 'head_metadata' ), 4 );
		add_action( 'template_redirect', array( __CLASS__, 'remove_core_canonical' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'protect_unready_dishes' ), 1 );
	}

	public static function remove_core_canonical() {
		if ( is_singular() && Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}

	public static function protect_unready_dishes() {
		if ( ! is_singular( 'c99_dish' ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( Complete99_Content::dish_gate_status( $post_id )['passed'] || ( is_preview() && current_user_can( 'edit_post', $post_id ) ) ) {
			return;
		}
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		$template = get_404_template();
		if ( $template ) {
			include $template;
		}
		exit;
	}

	public static function template_include( $template ) {
		if ( is_singular() && Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return COMPLETE99_PLATFORM_DIR . 'templates/public-shell.php';
		}
		return $template;
	}

	public static function body_classes( $classes ) {
		if ( is_singular() && Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			$lang      = Complete99_Content::language_for_post( get_queried_object_id() );
			if ( 'en' === $lang ) {
				$classes = array_values( array_diff( $classes, array( 'rtl' ) ) );
			}
			$classes[] = 'complete99-public';
			$classes[] = 'complete99-lang-' . $lang;
			$classes[] = 'en' === $lang ? 'complete99-ltr' : 'complete99-rtl';
		}
		return $classes;
	}

	public static function enqueue() {
		if ( ! is_singular() || ! Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return;
		}
		wp_enqueue_style(
			'complete99-public',
			COMPLETE99_PLATFORM_URL . 'assets/css/public.css',
			array(),
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

	public static function document_title( $title ) {
		if ( ! is_singular() || ! Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return $title;
		}
		$post = get_queried_object();
		return $post ? wp_strip_all_tags( $post->post_title ) . ' | Complete99' : $title;
	}

	public static function robots( $robots ) {
		if ( ! is_singular() || ! Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return $robots;
		}
		if ( 'c99_dish' === get_post_type( get_queried_object_id() ) && ! Complete99_Content::dish_gate_status( get_queried_object_id() )['passed'] ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = false;
		}
		return $robots;
	}

	public static function head_metadata() {
		if ( ! is_singular() || ! Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return;
		}
		$post      = get_queried_object();
		$lang      = Complete99_Content::language_for_post( $post->ID );
		$key       = (string) get_post_meta( $post->ID, '_complete99_translation_key', true );
		$alternate = Complete99_Content::route_url( $key, 'he' === $lang ? 'en' : 'he' );
		$canonical = get_permalink( $post );
		$image     = self::post_image_url( $post->ID );

		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="' . esc_attr( 'he' ) . '" href="' . esc_url( Complete99_Content::route_url( $key, 'he' ) ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="' . esc_attr( 'en' ) . '" href="' . esc_url( Complete99_Content::route_url( $key, 'en' ) ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( Complete99_Content::route_url( $key, 'he' ) ) . '" />' . "\n";
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( 'he' === $lang ? 'he_IL' : 'en_US' ) . '" />' . "\n";
		echo '<meta property="og:locale:alternate" content="' . esc_attr( 'he' === $lang ? 'en_US' : 'he_IL' ) . '" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_strip_all_tags( $post->post_title ) ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
		echo '<meta name="twitter:card" content="' . esc_attr( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( wp_strip_all_tags( $post->post_title ) ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '" />' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
		}

		$schema = self::schema_graph( $post, $lang, $alternate );
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	private static function schema_graph( $post, $lang, $alternate ) {
		$url     = get_permalink( $post );
		$org_id  = home_url( '/#organization' );
		$page_id = $url . '#webpage';
		$image   = self::post_image_url( $post->ID );
		$key     = (string) get_post_meta( $post->ID, '_complete99_translation_key', true );
		$graph   = array(
			array(
				'@type' => 'Organization',
				'@id'   => $org_id,
				'name'  => 'Complete99',
				'url'   => home_url( '/' ),
			),
			array(
				'@type'       => 'WebPage',
				'@id'         => $page_id,
				'url'         => $url,
				'name'        => wp_strip_all_tags( $post->post_title ),
				'description' => wp_strip_all_tags( $post->post_excerpt ),
				'inLanguage'  => $lang,
				'isPartOf'     => array( '@id' => home_url( '/#website' ) ),
			),
		);

		if ( 'home' !== $key ) {
			$graph[1]['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
			$graph[]                = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => array(
					array(
						'@type'    => 'ListItem',
						'position' => 1,
						'name'     => 'he' === $lang ? 'בית' : 'Home',
						'item'     => Complete99_Content::route_url( 'home', $lang ),
					),
					array(
						'@type'    => 'ListItem',
						'position' => 2,
						'name'     => wp_strip_all_tags( $post->post_title ),
						'item'     => $url,
					),
				),
			);
		}

		$graph[0]['logo'] = Complete99_Settings::owned_asset_url( 'c99-identity-legacy-logo-square-2021-wp-v01.png' );
		$graph[]          = array(
			'@type'       => 'WebSite',
			'@id'         => home_url( '/#website' ),
			'url'         => home_url( '/' ),
			'name'        => 'Complete99',
			'inLanguage'  => array( 'he', 'en' ),
			'publisher'   => array( '@id' => $org_id ),
		);

		if ( 'c99_service' === $post->post_type ) {
			$graph[] = array(
				'@type'       => 'Service',
				'@id'         => $url . '#service',
				'name'        => wp_strip_all_tags( $post->post_title ),
				'description' => wp_strip_all_tags( $post->post_excerpt ),
				'url'         => $url,
				'provider'    => array( '@id' => $org_id ),
			);
		}

		if ( 'app' === $key && Complete99_Settings::app_url( $lang ) ) {
			$graph[] = array(
				'@type'               => 'WebApplication',
				'@id'                 => $url . '#application',
				'name'                => 'Complete99 OS',
				'url'                 => Complete99_Settings::app_url( $lang ),
				'applicationCategory' => 'BusinessApplication',
				'operatingSystem'     => 'Web',
				'description'         => wp_strip_all_tags( $post->post_excerpt ),
			);
		}

		$recipe = self::verified_recipe_schema( $post, $lang, $image );
		if ( $recipe ) {
			$graph[] = $recipe;
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}

	private static function verified_recipe_schema( $post, $lang, $image ) {
		if ( 'c99_dish' !== $post->post_type || 'verified' !== (string) get_post_meta( $post->ID, '_complete99_verification_state', true ) || ! Complete99_Content::dish_gate_status( $post->ID )['passed'] ) {
			return null;
		}
		$recipe = get_post_meta( $post->ID, '_complete99_recipe', true );
		if ( ! is_array( $recipe ) || empty( $recipe['ingredients'] ) || empty( $recipe['instructions'] ) || empty( $recipe['sources'] ) || empty( $recipe['yield'] ) ) {
			return null;
		}
		$steps = array();
		foreach ( $recipe['instructions'] as $step ) {
			$steps[] = array(
				'@type' => 'HowToStep',
				'text'  => sanitize_text_field( $step ),
			);
		}
		$schema = array(
			'@type'             => 'Recipe',
			'@id'               => get_permalink( $post ) . '#recipe',
			'name'              => wp_strip_all_tags( $post->post_title ),
			'description'       => wp_strip_all_tags( $post->post_excerpt ),
			'inLanguage'        => $lang,
			'url'               => get_permalink( $post ),
			'recipeYield'       => sanitize_text_field( $recipe['yield'] ),
			'recipeIngredient'  => array_map( 'sanitize_text_field', $recipe['ingredients'] ),
			'recipeInstructions'=> $steps,
			'citation'          => array_map( 'esc_url_raw', $recipe['sources'] ),
		);
		if ( $image ) {
			$schema['image'] = array( $image );
		}
		if ( ! empty( $recipe['prep_minutes'] ) ) {
			$schema['prepTime'] = 'PT' . absint( $recipe['prep_minutes'] ) . 'M';
		}
		if ( ! empty( $recipe['cook_minutes'] ) ) {
			$schema['cookTime'] = 'PT' . absint( $recipe['cook_minutes'] ) . 'M';
		}
		return $schema;
	}

	public static function render_header( $post_id, $lang ) {
		$is_he      = 'he' === $lang;
		$brand_home = Complete99_Content::route_url( 'home', $lang );
		$items      = array(
			array( 'institutional-catering', $is_he ? 'שירותים' : 'Services' ),
			array( 'companies-offices', $is_he ? 'למי זה מתאים' : 'Industries' ),
			array( 'operations-command-center', $is_he ? 'מרכז השליטה' : 'Command centre' ),
			array( 'marketing-campaigns', $is_he ? 'מותג וקמפיינים' : 'Brand & campaigns' ),
			array( 'about', $is_he ? 'אודות' : 'About' ),
		);
		?>
		<a class="c99-skip-link" href="#c99-main"><?php echo esc_html( $is_he ? 'דילוג לתוכן' : 'Skip to content' ); ?></a>
		<header class="c99-site-header">
			<div class="c99-container c99-header-inner">
				<a class="c99-brand" href="<?php echo esc_url( $brand_home ); ?>" aria-label="<?php echo esc_attr( $is_he ? 'קומפלט 99 — בית' : 'Complete99 — home' ); ?>">
					<span class="c99-brand-mark" aria-hidden="true"><span>9</span><span>9</span></span>
					<span class="c99-brand-copy"><strong><?php echo esc_html( $is_he ? 'קומפלט 99' : 'Complete99' ); ?></strong><small><?php echo esc_html( $is_he ? 'אוכל · תפעול · צמיחה' : 'Food · operations · growth' ); ?></small></span>
				</a>
				<button class="c99-menu-toggle" type="button" aria-expanded="false" aria-controls="c99-primary-nav">
					<span aria-hidden="true">☰</span><span><?php echo esc_html( $is_he ? 'תפריט' : 'Menu' ); ?></span>
				</button>
				<nav id="c99-primary-nav" class="c99-primary-nav" aria-label="<?php echo esc_attr( $is_he ? 'ניווט ראשי' : 'Primary navigation' ); ?>">
					<?php foreach ( $items as $item ) : ?>
						<a href="<?php echo esc_url( Complete99_Content::route_url( $item[0], $lang ) ); ?>"><?php echo esc_html( $item[1] ); ?></a>
					<?php endforeach; ?>
					<a class="c99-nav-cta" href="<?php echo esc_url( Complete99_Content::route_url( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בדיקת התאמה' : 'Fit review' ); ?></a>
				</nav>
				<?php self::render_language_switch( $post_id, $lang ); ?>
			</div>
		</header>
		<?php
	}

	private static function render_language_switch( $post_id, $lang ) {
		$key      = (string) get_post_meta( $post_id, '_complete99_translation_key', true );
		$other    = 'he' === $lang ? 'en' : 'he';
		$label    = 'he' === $lang ? 'EN' : 'עברית';
		$language = 'he' === $other ? 'עברית' : 'English';
		echo '<a class="c99-language-switch" href="' . esc_url( Complete99_Content::route_url( $key, $other ) ) . '" hreflang="' . esc_attr( $other ) . '" lang="' . esc_attr( $other ) . '" aria-label="' . esc_attr( $language ) . '">' . esc_html( $label ) . '</a>';
	}

	public static function render_current( $post ) {
		$lang  = Complete99_Content::language_for_post( $post->ID );
		$key   = (string) get_post_meta( $post->ID, '_complete99_translation_key', true );
		$is_he = 'he' === $lang;
		$image = self::post_image_url( $post->ID );

		if ( 'home' === $key ) {
			self::render_home( $post, $lang, $image );
			return;
		}
		self::render_breadcrumb( $post, $lang );
		?>
		<section class="c99-page-hero">
			<div class="c99-container c99-page-hero-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( self::eyebrow( $post, $lang ) ); ?></p>
					<h1><?php echo esc_html( $post->post_title ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $post->post_excerpt ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( Complete99_Content::route_url( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בדיקת התאמה' : 'Request a fit review' ); ?></a>
						<?php if ( 'app' !== $key ) : ?>
							<a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Content::route_url( 'app', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'סיור במרכז השליטה' : 'Tour the command centre' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( $image ) : ?>
					<figure class="c99-hero-image"><img src="<?php echo esc_url( $image ); ?>" alt="" width="720" height="520" fetchpriority="high" /></figure>
				<?php else : ?>
					<div class="c99-hero-system" aria-label="<?php echo esc_attr( $is_he ? 'סקירת תפעול' : 'Operations overview' ); ?>">
						<?php self::render_system_preview( $lang ); ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<section class="c99-content-section">
			<div class="c99-container c99-content-grid">
				<article class="c99-article"><?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
				<aside class="c99-side-card">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'צעד הבא' : 'Next step' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'בואו נבדוק התאמה לעובדות' : 'Test the fit against real facts' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'נרכז צרכים, בעלים, מסמכים ופערים לפני הצעה.' : 'We document requirements, owners, evidence and gaps before proposing a solution.' ); ?></p>
					<a class="c99-button c99-button-primary" href="<?php echo esc_url( Complete99_Content::route_url( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'פתיחת פנייה' : 'Start an enquiry' ); ?></a>
				</aside>
			</div>
		</section>
		<?php
		if ( 'app' === $key ) {
			self::render_app_tour( $lang );
		}
		if ( in_array( $key, array( 'proposal', 'contact' ), true ) ) {
			self::render_lead_section( $lang, $key );
		}
	}

	private static function render_home( $post, $lang, $image ) {
		$is_he = 'he' === $lang;
		?>
		<section class="c99-home-hero">
			<div class="c99-container c99-home-hero-grid">
				<div class="c99-home-hero-copy">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מערכת לעסק מזון שרוצה לגדול נכון' : 'A system for a food business built to scale responsibly' ); ?></p>
					<h1><?php echo esc_html( $post->post_title ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $post->post_excerpt ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( Complete99_Content::route_url( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בדיקת התאמה למוסד' : 'Institutional fit review' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Content::route_url( 'app', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'פתיחת סיור במערכת' : 'Open the system tour' ); ?></a>
					</div>
					<ul class="c99-proof-strip" aria-label="<?php echo esc_attr( $is_he ? 'עקרונות עבודה' : 'Operating principles' ); ?>">
						<li><?php echo esc_html( $is_he ? 'עברית + English' : 'Hebrew + English' ); ?></li>
						<li><?php echo esc_html( $is_he ? 'רב־סניפי' : 'Multi-location' ); ?></li>
						<li><?php echo esc_html( $is_he ? 'תהליך אחד מקצה לקצה' : 'One end-to-end process' ); ?></li>
					</ul>
				</div>
				<figure class="c99-home-image">
					<?php if ( $image ) : ?><img src="<?php echo esc_url( $image ); ?>" alt="" width="840" height="640" fetchpriority="high" /><?php endif; ?>
					<figcaption><?php echo esc_html( $is_he ? 'צילום מקורי בבעלות העסק' : 'Original business-owned image' ); ?></figcaption>
				</figure>
			</div>
		</section>
		<section class="c99-war-room" aria-labelledby="c99-war-room-title">
			<div class="c99-container">
				<div class="c99-section-heading">
					<div><p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'ליבת העסק, לא קישור בתחתית' : 'The business core, not a link in the footer' ); ?></p><h2 id="c99-war-room-title"><?php echo esc_html( $is_he ? 'מרכז שליטה לפתיחת יום, מזון, צוות וצמיחה' : 'A command centre for opening, food, people and growth' ); ?></h2></div>
					<a class="c99-text-link" href="<?php echo esc_url( Complete99_Content::route_url( 'app', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לסיור המלא ←' : 'See the full tour →' ); ?></a>
				</div>
				<div class="c99-dashboard-grid">
					<div class="c99-dashboard-main"><?php self::render_system_preview( $lang ); ?></div>
					<div class="c99-dashboard-stack">
						<article><span aria-hidden="true">◉</span><strong><?php echo esc_html( $is_he ? 'פתיחת סניף' : 'Location opening' ); ?></strong><small><?php echo esc_html( $is_he ? 'משימות, תמונות ואישורים' : 'Tasks, photos and sign-offs' ); ?></small></article>
						<article><span aria-hidden="true">▦</span><strong><?php echo esc_html( $is_he ? 'מתכונים ו-BOM' : 'Recipes and BOM' ); ?></strong><small><?php echo esc_html( $is_he ? 'גרסה, תשואה, עלות ואלרגנים' : 'Version, yield, cost and allergens' ); ?></small></article>
						<article><span aria-hidden="true">↗</span><strong><?php echo esc_html( $is_he ? 'מותג וקמפיינים' : 'Brand and campaigns' ); ?></strong><small><?php echo esc_html( $is_he ? 'בריף, נכס, בקרה ומדידה' : 'Brief, asset, review and measurement' ); ?></small></article>
					</div>
				</div>
			</div>
		</section>
		<section class="c99-campaign-section">
			<div class="c99-container c99-campaign-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'סטודיו צמיחה מובנה' : 'Built-in growth studio' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'האתר, הנכסים והקמפיינים עובדים מאותו מקור' : 'Site, assets and campaigns from one governed source' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'כל מהלך מתחיל בקהל, מטרה ומסר ברורים. נכסי המותג, דפי האתר ותכנית המדידה נשארים מחוברים לאותו בריף.' : 'Every activity starts with a clear audience, objective and message. Brand assets, site pages and measurement stay connected to the same brief.' ); ?></p>
					<a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Content::route_url( 'marketing-campaigns', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'איך הסטודיו עובד' : 'How the studio works' ); ?></a>
				</div>
				<div class="c99-campaign-board" aria-label="<?php echo esc_attr( $is_he ? 'לוח תכנון קמפיין' : 'Campaign planning board' ); ?>">
					<span class="c99-status c99-status-draft"><?php echo esc_html( $is_he ? 'תהליך קמפיין' : 'Campaign workflow' ); ?></span>
					<h3><?php echo esc_html( $is_he ? 'שבוע אוכל מהמסורת המשפחתית' : 'Family food traditions week' ); ?></h3>
					<div class="c99-campaign-steps"><span><?php echo esc_html( $is_he ? 'בריף' : 'Brief' ); ?></span><span><?php echo esc_html( $is_he ? 'קהל' : 'Audience' ); ?></span><span><?php echo esc_html( $is_he ? 'מסר' : 'Message' ); ?></span><span><?php echo esc_html( $is_he ? 'מדידה' : 'Measurement' ); ?></span></div>
					<p><?php echo esc_html( $is_he ? 'מסר עקבי מהבריף ועד מדידת התוצאה' : 'A consistent message from brief to measured outcome' ); ?></p>
				</div>
			</div>
		</section>
		<section class="c99-audience-section">
			<div class="c99-container c99-audience-grid">
				<div><p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'עבור מקבלי ההחלטות' : 'For decision-makers' ); ?></p><h2><?php echo esc_html( $is_he ? 'רכש, תפעול, משאבי אנוש ורווחת עובדים' : 'Procurement, operations, human resources and workplace teams' ); ?></h2><p><?php echo esc_html( $is_he ? 'המסלול המרכזי מיועד לארגונים שמבקשים לתכנן שירות מזון שוטף, לנהל אחריות ולשפר את חוויית העובדים. לצד זה האתר משרת סועדים שמבקשים מידע ברור וקוראי מורשת קולינרית.' : 'The primary pathway serves organisations planning ongoing foodservice, accountable operations and a better employee experience. The site also serves current diners seeking clear information and readers of culinary heritage.' ); ?></p></div>
				<div class="c99-scope-card"><h3><?php echo esc_html( $is_he ? 'מיקוד השירות הנוכחי' : 'Current service focus' ); ?></h3><p><?php echo esc_html( $is_he ? 'השירות המוצג אינו כולל אירועים חד־פעמיים, זכיינות, הצעות השקעה, שירות רפואי או שירות ביטחוני.' : 'The current offer excludes one-off event catering, franchising, investment offers, medical services and security services.' ); ?></p></div>
			</div>
		</section>
		<section class="c99-services-section">
			<div class="c99-container">
				<div class="c99-section-heading"><div><p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'שירותים למוסדות' : 'Institutional services' ); ?></p><h2><?php echo esc_html( $is_he ? 'מתכננים את השירות סביב האתר והאנשים' : 'Design service around the site and its people' ); ?></h2></div></div>
				<div class="c99-card-grid">
					<?php
					$cards = array(
						array( 'institutional-catering', '◎', $is_he ? 'הסעדה מוסדית' : 'Institutional foodservice', $is_he ? 'דרישות, אחריות ומדדי שירות.' : 'Requirements, ownership and service measures.' ),
						array( 'employee-meals', '◌', $is_he ? 'ארוחות לעובדים' : 'Employee meals', $is_he ? 'תפריט, חוויה, ביקוש ותקציב.' : 'Menu, experience, demand and budget.' ),
						array( 'dining-room-management', '▤', $is_he ? 'ניהול חדר אוכל' : 'Dining-room management', $is_he ? 'פתיחה, הגשה, חריגים וסגירה.' : 'Opening, service, exceptions and close.' ),
						array( 'central-kitchen-delivery', '↹', $is_he ? 'מטבח מרכזי והפצה' : 'Central kitchen & delivery', $is_he ? 'אצווה, אריזה, מסירה וקבלה.' : 'Batch, pack, dispatch and receipt.' ),
						array( 'menu-nutrition-planning', '◇', $is_he ? 'תפריט ומידע תזונתי' : 'Menu & nutrition information', $is_he ? 'הפרדה בין תכנון לאישור מקצועי.' : 'Culinary planning separated from qualified review.' ),
						array( 'multi-location', '⌂', $is_he ? 'צמיחה לריבוי סניפים' : 'Multi-location growth', $is_he ? 'סטנדרט מרכזי והתאמות מבוקרות.' : 'Central standards and controlled local overrides.' ),
					);
					foreach ( $cards as $card ) :
						?>
						<a class="c99-service-card" href="<?php echo esc_url( Complete99_Content::route_url( $card[0], $lang ) ); ?>"><span class="c99-icon" aria-hidden="true"><?php echo esc_html( $card[1] ); ?></span><h3><?php echo esc_html( $card[2] ); ?></h3><p><?php echo esc_html( $card[3] ); ?></p><span class="c99-card-arrow" aria-hidden="true">←</span></a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<section class="c99-editorial-section">
			<div class="c99-container c99-content-grid">
				<article class="c99-article"><?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
				<aside class="c99-side-card">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'ניהול ברור' : 'Clear ownership' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'לכל החלטה יש בעלים, זמן יעד והמשך פעולה' : 'Every decision has an owner, due time and next action' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'כך מחברים בין דרישות ההתקשרות, העבודה במטבח וחוויית הסועדים.' : 'This connects contractual requirements, kitchen work and the diner experience.' ); ?></p>
				</aside>
			</div>
		</section>
		<?php self::render_lead_section( $lang, 'institutional-service' ); ?>
		<?php
	}

	private static function render_system_preview( $lang ) {
		$is_he = 'he' === $lang;
		?>
		<div class="c99-system-top"><span class="c99-live-dot" aria-hidden="true"></span><strong><?php echo esc_html( $is_he ? 'היום · תפעול לפי סניף' : 'Today · location operations' ); ?></strong><span><?php echo esc_html( $is_he ? 'סקירה' : 'Overview' ); ?></span></div>
		<div class="c99-kpi-row"><div><small><?php echo esc_html( $is_he ? 'פתיחת היום' : 'Opening' ); ?></small><strong>✓</strong></div><div><small><?php echo esc_html( $is_he ? 'משימות צוות' : 'Team tasks' ); ?></small><strong>↗</strong></div><div><small><?php echo esc_html( $is_he ? 'חריגים לטיפול' : 'Exceptions' ); ?></small><strong>!</strong></div></div>
		<div class="c99-task-list">
			<div><span class="c99-task-icon">✓</span><p><strong><?php echo esc_html( $is_he ? 'פתיחת מטבח' : 'Kitchen opening' ); ?></strong><small><?php echo esc_html( $is_he ? 'משימות לפי תפקיד' : 'Role-based actions' ); ?></small></p></div>
			<div><span class="c99-task-icon c99-task-warn">!</span><p><strong><?php echo esc_html( $is_he ? 'מחסור בפריט' : 'Item shortage' ); ?></strong><small><?php echo esc_html( $is_he ? 'בעלים והמשך פעולה' : 'Owner and next action' ); ?></small></p></div>
			<div><span class="c99-task-icon">↗</span><p><strong><?php echo esc_html( $is_he ? 'מהלך שיווקי' : 'Marketing activity' ); ?></strong><small><?php echo esc_html( $is_he ? 'בריף, בקרה ומדידה' : 'Brief, review and measurement' ); ?></small></p></div>
		</div>
		<?php
	}

	private static function render_breadcrumb( $post, $lang ) {
		$is_he = 'he' === $lang;
		echo '<nav class="c99-breadcrumb c99-container" aria-label="' . esc_attr( $is_he ? 'פירורי לחם' : 'Breadcrumb' ) . '"><a href="' . esc_url( Complete99_Content::route_url( 'home', $lang ) ) . '">' . esc_html( $is_he ? 'בית' : 'Home' ) . '</a><span aria-hidden="true">/</span><span aria-current="page">' . esc_html( $post->post_title ) . '</span></nav>';
	}

	private static function render_app_tour( $lang ) {
		$is_he = 'he' === $lang;
		$url   = Complete99_Settings::app_url( $lang );
		?>
		<section class="c99-app-launch">
			<div class="c99-container c99-app-launch-inner">
				<div><p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מערכת תפעול מחוברת' : 'Connected operating system' ); ?></p><h2><?php echo esc_html( $is_he ? 'פתחו את מרכז השליטה של Complete99' : 'Open the Complete99 command centre' ); ?></h2><p><?php echo esc_html( $is_he ? 'מרכז השליטה מלווה את עבודת השירות והתפעול ואינו מוצע כמוצר תוכנה עצמאי.' : 'The command centre supports the foodservice and operating engagement; it is not offered as standalone software.' ); ?></p></div>
				<?php if ( $url ) : ?><a class="c99-button c99-button-primary c99-button-large" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'סקירת יכולות המערכת' : 'Explore the platform' ); ?></a><?php endif; ?>
			</div>
		</section>
		<?php
	}

	private static function render_lead_section( $lang, $interest ) {
		$is_he = 'he' === $lang;
		?>
		<section class="c99-lead-section" id="request">
			<div class="c99-container c99-lead-grid">
				<div><p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מתחילים בעובדות' : 'Start with facts' ); ?></p><h2><?php echo esc_html( $is_he ? 'ספרו לנו מה צריך לעבוד' : 'Tell us what needs to work' ); ?></h2><p><?php echo esc_html( $is_he ? 'נמפה את היקף השירות, האתר, בעלי התפקידים, המסמכים והפערים שדורשים מענה.' : 'We map service scope, site conditions, responsible roles, documents and gaps that need an answer.' ); ?></p></div>
				<div><?php Complete99_Leads::render_form( $lang, $interest ); ?></div>
			</div>
		</section>
		<?php
	}

	public static function render_footer( $lang ) {
		$is_he = 'he' === $lang;
		$links = array(
			array( 'institutional-catering', $is_he ? 'הסעדה מוסדית' : 'Institutional foodservice' ),
			array( 'dining-room-management', $is_he ? 'ניהול חדרי אוכל' : 'Dining-room management' ),
			array( 'operations-command-center', $is_he ? 'מרכז שליטה' : 'Command centre' ),
			array( 'recipes-bom-food-cost', $is_he ? 'מתכונים ו-BOM' : 'Recipes and BOM' ),
			array( 'marketing-campaigns', $is_he ? 'מותג וקמפיינים' : 'Brand and campaigns' ),
			array( 'tender-pack', $is_he ? 'מרכז מכרזים' : 'Tender pack' ),
			array( 'about', $is_he ? 'אודות' : 'About' ),
			array( 'contact', $is_he ? 'יצירת קשר' : 'Contact' ),
		);
		?>
		<footer class="c99-site-footer">
			<div class="c99-container c99-footer-top">
				<div class="c99-footer-brand"><div class="c99-brand-mark" aria-hidden="true"><span>9</span><span>9</span></div><h2><?php echo esc_html( $is_he ? 'קומפלט 99' : 'Complete99' ); ?></h2><p><?php echo esc_html( $is_he ? 'שירותי מזון שוטפים לארגונים, עם תפעול, ידע ותקשורת באותה שפה.' : 'Ongoing organisational foodservice with operations, knowledge and communication working together.' ); ?></p></div>
				<nav class="c99-footer-links" aria-label="<?php echo esc_attr( $is_he ? 'קישורים נוספים' : 'Additional links' ); ?>"><?php foreach ( $links as $link ) : ?><a href="<?php echo esc_url( Complete99_Content::route_url( $link[0], $lang ) ); ?>"><?php echo esc_html( $link[1] ); ?></a><?php endforeach; ?></nav>
			</div>
			<div class="c99-container c99-footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Complete99</span><span><?php echo esc_html( $is_he ? 'שירותי מזון שוטפים לארגונים, עם תפעול ותוכן מאותה תשתית.' : 'Ongoing organisational foodservice, operations and content on one foundation.' ); ?></span></div>
		</footer>
		<?php
	}

	private static function post_image_url( $post_id ) {
		$asset = (string) get_post_meta( $post_id, '_complete99_image_asset', true );
		return $asset ? Complete99_Settings::owned_asset_url( $asset ) : '';
	}

	private static function eyebrow( $post, $lang ) {
		$is_he = 'he' === $lang;
		$labels = array(
			'c99_service'          => $is_he ? 'שירות למוסדות' : 'Institutional service',
			'c99_industry'         => $is_he ? 'פתרון לפי מגזר' : 'Sector pathway',
			'c99_platform_feature' => $is_he ? 'יכולת מערכת' : 'Platform capability',
			'c99_dish'             => $is_he ? 'ספריית מנות ומסורת' : 'Dish and tradition library',
		);
		return isset( $labels[ $post->post_type ] ) ? $labels[ $post->post_type ] : ( $is_he ? 'קומפלט 99' : 'Complete99' );
	}
}
