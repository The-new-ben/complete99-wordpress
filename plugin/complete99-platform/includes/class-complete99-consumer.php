<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consumer-only public presentation.
 *
 * Private operations, worker, supplier, cost, campaign and platform language
 * never enters this renderer.
 */
final class Complete99_Consumer {
	private static $culinary_facets = null;
	private static $dish_entities   = null;

	private static function culinary_facets() {
		if ( null === self::$culinary_facets ) {
			$facets = require COMPLETE99_PLATFORM_DIR . 'data/culinary-facets.php';
			self::$culinary_facets = is_array( $facets ) ? $facets : array();
		}
		return self::$culinary_facets;
	}

	private static function dish_entity_tree( $slug ) {
		if ( null === self::$dish_entities ) {
			$records = require COMPLETE99_PLATFORM_DIR . 'data/dish-entity-trees.php';
			self::$dish_entities = array();
			foreach ( is_array( $records ) ? $records : array() as $record ) {
				$record_slug = sanitize_title( (string) ( $record['identity']['slug'] ?? '' ) );
				if ( '' !== $record_slug ) {
					self::$dish_entities[ $record_slug ] = $record;
				}
			}
		}
		$slug = sanitize_title( (string) $slug );
		return isset( self::$dish_entities[ $slug ] ) ? self::$dish_entities[ $slug ] : array();
	}

	private static function item_facet_codes( $item ) {
		$registry = self::culinary_facets();
		$allowed  = array_keys( (array) ( $registry['filters'] ?? array() ) );
		$codes    = isset( $item['facets'] ) && is_array( $item['facets'] ) ? $item['facets'] : array();
		if ( ! empty( $item['vegetarian'] ) ) {
			$codes[] = 'vegetarian';
		}
		return array_values(
			array_unique(
				array_filter(
					array_map( 'sanitize_key', $codes ),
					static function ( $code ) use ( $allowed ) {
						return 'all' !== $code && in_array( $code, $allowed, true );
					}
				)
			)
		);
	}

	private static function item_badge_codes( $item ) {
		$registry = self::culinary_facets();
		$allowed  = array_keys( (array) ( $registry['badges'] ?? array() ) );
		$codes    = isset( $item['badge_codes'] ) && is_array( $item['badge_codes'] )
			? $item['badge_codes']
			: self::item_facet_codes( $item );
		return array_slice(
			array_values(
				array_unique(
					array_filter(
						array_map( 'sanitize_key', $codes ),
						static function ( $code ) use ( $allowed ) {
							return in_array( $code, $allowed, true );
						}
					)
				)
			),
			0,
			2
		);
	}

	public static function menu_items() {
		$items = Complete99_REST::public_indexable_items();
		foreach ( $items as &$item ) {
			$item['_complete99_source']           = sanitize_key( (string) ( $item['_complete99_source'] ?? 'synced_read_model' ) );
			$item['_complete99_media_provenance'] = sanitize_key( (string) ( $item['media_provenance'] ?? 'business_owned' ) );
			if ( 'complete99_archive' === $item['_complete99_media_provenance'] ) {
				$item['_complete99_media_provenance'] = 'business_owned';
			}
			$item['_complete99_media_rights'] = sanitize_key( (string) ( $item['media_rights_state'] ?? 'approved_public_use' ) );
		}
		unset( $item );
		usort(
			$items,
			static function ( $left, $right ) {
				$sort = (float) ( isset( $left['sort'] ) ? $left['sort'] : 0 ) <=> (float) ( isset( $right['sort'] ) ? $right['sort'] : 0 );
				if ( 0 !== $sort ) {
					return $sort;
				}
				return strcmp( (string) $left['id'], (string) $right['id'] );
			}
		);
		return $items;
	}

	public static function dish_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return array();
		}
		foreach ( self::menu_items() as $item ) {
			if ( isset( $item['slug'] ) && hash_equals( sanitize_title( (string) $item['slug'] ), $slug ) ) {
				return $item;
			}
		}
		return array();
	}

	public static function image_url( $item ) {
		$asset = sanitize_file_name( isset( $item['image_asset'] ) ? (string) $item['image_asset'] : '' );
		if ( '' === $asset || 0 !== strpos( $asset, 'c99-' ) ) {
			return '';
		}
		$stem = pathinfo( $asset, PATHINFO_FILENAME );
		foreach ( array_values( array_unique( array( $stem . '.avif', $stem . '.webp', $asset ) ) ) as $candidate ) {
			if ( ! preg_match( '/\.(?:jpe?g|png|webp|avif)$/i', $candidate ) ) {
				continue;
			}
			if ( is_file( COMPLETE99_PLATFORM_DIR . 'assets/images/original/' . $candidate ) ) {
				return COMPLETE99_PLATFORM_URL . 'assets/images/original/' . rawurlencode( $candidate );
			}
		}
		return '';
	}

	private static function route( $key, $lang ) {
		$url = Complete99_Content::route_url( $key, $lang );
		if ( $url ) {
			return $url;
		}
		if ( 'home' === $key ) {
			return home_url( 'en' === $lang ? '/en/' : '/' );
		}
		return home_url( 'en' === $lang ? '/en/' : '/' );
	}

	private static function key_for_post( $post_id ) {
		return Complete99_Content::translation_group_for_post( $post_id );
	}

	private static function brand_picture( $stem, $alt, $width = 1000, $height = 700, $priority = false ) {
		$loading = $priority ? '' : ' loading="lazy"';
		$fetch   = $priority ? ' fetchpriority="high"' : '';
		?>
		<picture>
			<source srcset="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/original/' . $stem . '.avif' ); ?>" type="image/avif" />
			<img src="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/original/' . $stem . '.webp' ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" decoding="async"<?php echo $loading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $fetch; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
		</picture>
		<?php
	}

	public static function render_header( $post_id, $lang, $live_slug = '', $alternate_url = '', $current_override = '' ) {
		$is_he      = 'he' === $lang;
		$current    = '' !== $current_override ? $current_override : ( $post_id ? self::key_for_post( $post_id ) : 'dishes' );
		$home       = self::route( 'home', $lang );
		$order_url  = Complete99_Commerce::order_url( $lang );
		$default_nav = array(
			array( 'dishes', $is_he ? 'תפריט' : 'Menu' ),
			array( 'proposal', $is_he ? 'לקבוצות ולמשרדים' : 'Groups and workplaces' ),
			array( 'traditions', $is_he ? 'סיפורי אוכל' : 'Food stories' ),
			array( 'knowledge', $is_he ? 'מדריכים' : 'Guides' ),
			array( 'about', $is_he ? 'הסיפור שלנו' : 'Our story' ),
			array( 'contact', $is_he ? 'מגיעים אלינו' : 'Visit' ),
		);
		$museum = class_exists( 'Complete99_Culinary_Science' )
			? Complete99_Culinary_Science::public_museum_root_projection( $lang )
			: array();
		$nav = $default_nav;
		if ( ! empty( $museum['seo']['canonical_path'] ) ) {
			$nav = array(
				array( 'dishes', $is_he ? 'תפריט' : 'Menu' ),
				array( 'proposal', $is_he ? 'לקבוצות ולמשרדים' : 'Groups and workplaces' ),
				array( 'museum', $is_he ? 'מוזיאון הקולינריה' : 'Culinary museum', home_url( $museum['seo']['canonical_path'] ) ),
				array( 'about', $is_he ? 'הסיפור שלנו' : 'Our story' ),
				array( 'contact', $is_he ? 'מגיעים אלינו' : 'Visit' ),
			);
		}
		if ( Complete99_Commerce::catalog_is_ready() || Complete99_Commerce::can_preview_commerce() ) {
			array_splice( $nav, 4, 0, array( array( 'store', $is_he ? 'המזווה' : 'Pantry shop' ) ) );
		}
		?>
		<a class="c99-skip-link" href="#c99-main"><?php echo esc_html( $is_he ? 'דילוג לתוכן' : 'Skip to content' ); ?></a>
		<div class="c99-consumer-utility">
			<div class="c99-container c99-consumer-utility-inner">
				<span><?php echo esc_html( $is_he ? 'אבן גבירול 99, תל אביב' : '99 Ibn Gabirol, Tel Aviv' ); ?></span>
				<a href="<?php echo esc_url( $order_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'למחיר ולזמינות בתפריט ההזמנות' : 'Current price and availability' ); ?></a>
			</div>
		</div>
		<header class="c99-site-header c99-consumer-header">
			<div class="c99-container c99-header-inner">
				<a class="c99-brand" href="<?php echo esc_url( $home ); ?>" aria-label="<?php echo esc_attr( $is_he ? 'קומפלט 99, דף הבית' : 'Complete99 home' ); ?>">
					<span class="c99-brand-mark" aria-hidden="true"><span>9</span><span>9</span></span>
					<span class="c99-brand-copy"><strong><?php echo esc_html( $is_he ? 'קומפלט 99' : 'Complete99' ); ?></strong><small><?php echo esc_html( $is_he ? 'סביח ואוכל של בית' : 'Sabich and home cooking' ); ?></small></span>
				</a>
				<button class="c99-menu-toggle" type="button" aria-expanded="false" aria-controls="c99-primary-nav">
					<span class="c99-menu-icon" aria-hidden="true"><i></i><i></i><i></i></span>
					<span><?php echo esc_html( $is_he ? 'תפריט' : 'Menu' ); ?></span>
				</button>
				<nav id="c99-primary-nav" class="c99-primary-nav c99-consumer-nav" aria-label="<?php echo esc_attr( $is_he ? 'ניווט ראשי' : 'Primary navigation' ); ?>">
					<div class="c99-nav-groups">
						<?php foreach ( $nav as $link ) : ?>
							<a class="c99-consumer-nav-link<?php echo $current === $link[0] ? ' is-current' : ''; ?>" href="<?php echo esc_url( isset( $link[2] ) ? $link[2] : self::route( $link[0], $lang ) ); ?>"<?php echo $current === $link[0] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $link[1] ); ?></a>
						<?php endforeach; ?>
					</div>
					<div class="c99-nav-actions">
						<a class="c99-nav-cta" href="<?php echo esc_url( $order_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'להזמנה ב-Wolt' : 'Order on Wolt' ); ?></a>
					</div>
				</nav>
				<?php self::render_language_switch( $post_id, $lang, $live_slug, $alternate_url ); ?>
			</div>
		</header>
		<?php
	}

	private static function render_language_switch( $post_id, $lang, $live_slug, $alternate_url = '' ) {
		$other = 'he' === $lang ? 'en' : 'he';
		$label = 'he' === $lang ? 'EN' : 'עברית';
		$aria  = 'he' === $lang ? 'מעבר לאנגלית' : 'Switch to Hebrew';
		if ( '' !== $alternate_url ) {
			$url = $alternate_url;
		} elseif ( class_exists( 'Complete99_Commerce' ) && Complete99_Commerce::is_transaction_page() ) {
			$url = Complete99_Commerce::transaction_url( Complete99_Commerce::transaction_page_type(), $other );
		} elseif ( $live_slug ) {
			$url = Complete99_Frontend::live_dish_url( $live_slug, $other );
		} else {
			$key = $post_id ? self::key_for_post( $post_id ) : 'home';
			$url = self::route( $key, $other );
		}
		echo '<a class="c99-language-switch" href="' . esc_url( $url ) . '" hreflang="' . esc_attr( $other ) . '" lang="' . esc_attr( $other ) . '" aria-label="' . esc_attr( $aria ) . '">' . esc_html( $label ) . '</a>';
	}

	public static function render_current( $post ) {
		$lang  = Complete99_Content::language_for_post( $post->ID );
		$key   = self::key_for_post( $post->ID );
		if ( 'home' === $key ) {
			self::render_home( $post, $lang );
			return;
		}
		if ( 'dishes' === $key ) {
			self::render_menu_page( $post, $lang );
			return;
		}
		if ( 'store' === $key ) {
			self::render_store_page( $post, $lang );
			return;
		}
		if ( 'proposal' === $key ) {
			self::render_group_order_page( $post, $lang );
			return;
		}
		self::render_generic_page( $post, $lang, $key );
	}

	private static function render_home( $post, $lang ) {
		$is_he     = 'he' === $lang;
		$order_url = Complete99_Commerce::order_url( $lang );
		?>
		<section class="c99-consumer-hero">
			<div class="c99-container c99-consumer-hero-grid">
				<div class="c99-consumer-hero-copy">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'סביח, קובה, מרקים ואוכל מהסירים' : 'Sabich, kubbeh, soups and food from the pots' ); ?></p>
					<h1><?php echo esc_html( $is_he ? 'האוכל של הבית, באמצע תל אביב' : 'Home cooking, right in the middle of Tel Aviv' ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $post->post_excerpt ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( $order_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'לתפריט ולהזמנה' : 'See menu and order' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'Explore the dishes' ); ?></a>
					</div>
					<div class="c99-consumer-facts">
						<span><?php echo esc_html( $is_he ? 'אבן גבירול 99' : '99 Ibn Gabirol' ); ?></span>
						<span><?php echo esc_html( $is_he ? 'מחיר וזמינות מתעדכנים במסלול ההזמנה' : 'Price and availability live in the ordering menu' ); ?></span>
					</div>
				</div>
				<figure class="c99-consumer-hero-media">
					<?php self::brand_picture( 'c99-food-house-spread-hero-2021-wp-v01', $is_he ? 'מבט מלמעלה על קובה סלק, קוסקוס, קציצות, סלט ומנות נוספות' : 'Overhead spread of beet kubbeh, couscous, meatballs, salad and additional dishes', 1400, 788, true ); ?>
				</figure>
			</div>
		</section>
		<?php self::render_menu_preview( $lang, 6 ); ?>
		<?php self::render_group_order_teaser( $lang ); ?>
		<section class="c99-consumer-story">
			<div class="c99-container c99-consumer-story-grid">
				<div class="c99-consumer-story-copy">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'הרבה יותר מסביח' : 'Far more than sabich' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'פיתה אחת, סירים שלמים וסיפור תל אביבי' : 'One pita, full cooking pots and a Tel Aviv story' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'הסביח נמצא בלב המטבח. לידו חיים קובה סלק, קוסקוס, קציצות, מרק תימני, שניצל ושקשוקה. כל מנה מקבלת מקום משלה, בלי לנפח הבטחות ובלי להסתיר שהסיר של היום יכול להשתנות.' : 'Sabich sits at the heart of the kitchen. Alongside it are beet kubbeh, couscous, meatballs, Yemenite soup, schnitzel and shakshuka. Each dish has its own place, without inflated promises and without hiding that today’s pots can change.' ); ?></p>
					<div class="c99-inline-links">
						<a href="<?php echo esc_url( self::route( 'about', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'הסיפור שלנו' : 'Our story' ); ?></a>
						<a href="<?php echo esc_url( self::route( 'traditions', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'מסורות וסיפורי אוכל' : 'Traditions and food stories' ); ?></a>
					</div>
				</div>
				<div class="c99-story-mosaic">
					<figure><?php self::brand_picture( 'c99-food-sabich-pita-gallery-2021-wp-v01', $is_he ? 'סביח בפיתה' : 'Sabich in a pita' ); ?></figure>
					<figure><?php self::brand_picture( 'c99-food-kubeh-beet-soup-gallery-2021-wp-v01', $is_he ? 'קובה במרק סלק' : 'Kubbeh in beet soup' ); ?></figure>
					<figure><?php self::brand_picture( 'c99-food-couscous-beef-gallery-2021-wp-v01', $is_he ? 'קוסקוס, ירקות ובשר' : 'Couscous, vegetables and beef' ); ?></figure>
				</div>
			</div>
		</section>
		<section class="c99-consumer-paths">
			<div class="c99-container">
				<div class="c99-consumer-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'נכנסים דרך מה שמעניין אתכם' : 'Start with what interests you' ); ?></p>
						<h2><?php echo esc_html( $is_he ? 'מנה, מרכיב או סיפור' : 'A dish, an ingredient or a story' ); ?></h2>
					</div>
				</div>
				<div class="c99-consumer-path-grid">
					<a href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><span>01</span><h3><?php echo esc_html( $is_he ? 'מה אוכלים' : 'What to eat' ); ?></h3><p><?php echo esc_html( $is_he ? 'תמונות, תיאורים והמשך לתפריט ההזמנות.' : 'Photographs, descriptions and the current ordering menu.' ); ?></p></a>
					<a href="<?php echo esc_url( self::route( 'ingredients', $lang ) ); ?>"><span>02</span><h3><?php echo esc_html( $is_he ? 'מה יש בפנים' : 'What goes into it' ); ?></h3><p><?php echo esc_html( $is_he ? 'מרכיבים בתוך ההקשר של המנה והמסורת.' : 'Ingredients in the context of dishes and traditions.' ); ?></p></a>
					<a href="<?php echo esc_url( self::route( 'knowledge', $lang ) ); ?>"><span>03</span><h3><?php echo esc_html( $is_he ? 'איך מכינים ומספרים' : 'How it is cooked and told' ); ?></h3><p><?php echo esc_html( $is_he ? 'מדריכים שמתפרסמים רק אחרי בדיקה ומקורות.' : 'Guides published after testing and source review.' ); ?></p></a>
				</div>
			</div>
		</section>
		<?php self::render_pantry_teaser( $lang ); ?>
		<?php self::render_order_band( $lang ); ?>
		<?php
	}

	private static function render_menu_preview( $lang, $limit ) {
		$is_he    = 'he' === $lang;
		$items    = self::menu_items();
		?>
		<section class="c99-consumer-menu-section" aria-labelledby="c99-menu-preview-title">
			<div class="c99-container">
				<div class="c99-consumer-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מתחילים במה שבא לאכול' : 'Start with what you want to eat' ); ?></p>
						<h2 id="c99-menu-preview-title"><?php echo esc_html( $is_he ? 'המנות של קומפלט 99' : 'Complete99 dishes' ); ?></h2>
					</div>
					<a class="c99-text-link" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'View all dishes' ); ?></a>
				</div>
				<?php self::render_menu_grid( $lang, $limit ); ?>
			</div>
		</section>
		<?php
	}

	private static function render_menu_filters( $lang ) {
		$is_he    = 'he' === $lang;
		$registry = self::culinary_facets();
		$filters  = (array) ( $registry['filters'] ?? array() );
		?>
		<div class="c99-dish-filter-shell" data-c99-dish-filter>
			<div class="c99-dish-filter-heading">
				<div>
					<h3><?php echo esc_html( $is_he ? 'איך בא לכם לאכול?' : 'How would you like it?' ); ?></h3>
					<p><?php echo esc_html( $is_he ? 'הסינון מתאר את סגנון המנה בתפריט. מידע על אלרגנים בודקים בנפרד לפני הזמנה.' : 'Filters describe the menu format. Check allergen information separately before ordering.' ); ?></p>
				</div>
				<p class="c99-dish-result-count" aria-live="polite" data-c99-filter-count></p>
			</div>
			<div class="c99-dish-filter-buttons" role="group" aria-label="<?php echo esc_attr( $is_he ? 'סינון מנות' : 'Filter dishes' ); ?>">
				<?php foreach ( $filters as $code => $labels ) : ?>
					<button type="button" class="c99-dish-filter-button<?php echo 'all' === $code ? ' is-active' : ''; ?>" data-c99-filter="<?php echo esc_attr( $code ); ?>" aria-pressed="<?php echo 'all' === $code ? 'true' : 'false'; ?>"><?php echo esc_html( (string) $labels[ $lang ] ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	private static function render_dish_badges( $item, $lang ) {
		$registry = self::culinary_facets();
		$badges   = (array) ( $registry['badges'] ?? array() );
		$codes    = self::item_badge_codes( $item );
		if ( empty( $codes ) ) {
			return;
		}
		?>
		<ul class="c99-dish-badges" aria-label="<?php echo esc_attr( 'he' === $lang ? 'מאפייני המנה' : 'Dish traits' ); ?>">
			<?php foreach ( $codes as $code ) : ?>
				<li data-badge="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( (string) $badges[ $code ][ $lang ] ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	private static function render_dish_component_tree( $dish, $lang ) {
		$entity     = self::dish_entity_tree( $dish['slug'] ?? '' );
		$components = (array) ( $entity['component_tree']['children'] ?? array() );
		if ( empty( $components )
			|| empty( $entity['exposure']['public']['menu_stated_components'] ) ) {
			return;
		}
		$is_he      = 'he' === $lang;
		$source_url = (string) ( $entity['identity']['evidence']['source_url'][ $lang ] ?? '' );
		?>
		<section class="c99-dish-components" aria-labelledby="c99-dish-components-title">
			<div class="c99-container c99-dish-components-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'עץ המנה' : 'Dish tree' ); ?></p>
					<h2 id="c99-dish-components-title"><?php echo esc_html( $is_he ? 'מה פוגשים במנה' : 'What you meet in the dish' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'הרשימה מחברת את המנה למרכיבים ולרטבים שמופיעים בתיאור התפריט. מכל מרכיב אפשר להמשיך לספריית המרכיבים ולמדריכים הקולינריים.' : 'This list connects the dish with ingredients and sauces named in the menu description. Continue from each ingredient to the ingredient library and culinary guides.' ); ?></p>
					<div class="c99-inline-links">
						<a href="<?php echo esc_url( self::route( 'ingredients', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לספריית המרכיבים' : 'Ingredient library' ); ?></a>
						<a href="<?php echo esc_url( self::route( 'knowledge', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למדריכים ולמקורות' : 'Guides and sources' ); ?></a>
					</div>
				</div>
				<div>
					<ul class="c99-dish-component-list">
						<?php foreach ( $components as $component ) : ?>
							<?php
							$code  = sanitize_key( (string) ( $component['code'] ?? '' ) );
							$label = (string) ( $component['label'][ $lang ] ?? '' );
							if ( '' === $code || '' === $label ) {
								continue;
							}
							?>
							<li id="<?php echo esc_attr( $code ); ?>">
								<span aria-hidden="true"></span>
								<strong><?php echo esc_html( $label ); ?></strong>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="c99-dish-component-note"><?php echo esc_html( $is_he ? 'לשאלות על אלרגנים או החלפות מרכיב מדברים עם הצוות לפני ההזמנה.' : 'Speak with the team before ordering about allergens or ingredient substitutions.' ); ?>
					<?php if ( wp_http_validate_url( $source_url ) ) : ?>
						<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="external noopener noreferrer"><?php echo esc_html( $is_he ? 'מקור תיאור המנה' : 'Dish description source' ); ?></a>
					<?php endif; ?>
					</p>
				</div>
			</div>
		</section>
		<?php
	}

	private static function render_menu_grid( $lang, $limit = 0, $filterable = false ) {
		$is_he = 'he' === $lang;
		$items = self::menu_items();
		if ( 0 < $limit ) {
			$items = array_slice( $items, 0, $limit );
		}
		?>
		<div class="c99-consumer-menu-grid" data-c99-dish-grid>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$name        = $is_he ? $item['name_he'] : $item['name_en'];
				$description = $is_he ? $item['description_he'] : $item['description_en'];
				$category    = $is_he ? ( isset( $item['category_he'] ) ? $item['category_he'] : '' ) : ( isset( $item['category_en'] ) ? $item['category_en'] : '' );
				$tag         = $is_he ? ( isset( $item['tag_he'] ) ? $item['tag_he'] : '' ) : ( isset( $item['tag_en'] ) ? $item['tag_en'] : '' );
				$image       = self::image_url( $item );
				$facet_codes = self::item_facet_codes( $item );
				?>
				<a class="c99-consumer-menu-card" href="<?php echo esc_url( Complete99_Frontend::live_dish_url( $item['slug'], $lang ) ); ?>"<?php echo $filterable ? ' data-c99-dish-card data-c99-facets="' . esc_attr( implode( ' ', $facet_codes ) ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php if ( $image ) : ?>
						<figure><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="1000" height="700" loading="lazy" decoding="async" /><?php self::render_dish_badges( $item, $lang ); ?></figure>
					<?php endif; ?>
					<div class="c99-consumer-menu-card-copy">
						<div class="c99-consumer-menu-meta">
							<span><?php echo esc_html( $category ); ?></span>
							<span><?php echo esc_html( $is_he ? 'לפרטים ולהזמנה' : 'Details and ordering' ); ?></span>
						</div>
						<h3><?php echo esc_html( $name ); ?></h3>
						<p><?php echo esc_html( $description ); ?></p>
						<?php if ( $tag ) : ?><div class="c99-consumer-menu-card-footer"><span><?php echo esc_html( $tag ); ?></span></div><?php endif; ?>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_menu_page( $post, $lang ) {
		$is_he    = 'he' === $lang;
		$items    = self::menu_items();
		self::render_simple_breadcrumb( $post, $lang );
		?>
		<section class="c99-consumer-page-hero c99-consumer-menu-hero">
			<div class="c99-container c99-consumer-page-hero-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'סביח, פיתות, צלחות וסירים' : 'Sabich, pitas, plates and pots' ); ?></p>
					<h1><?php echo esc_html( $post->post_title ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $post->post_excerpt ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( Complete99_Commerce::order_url( $lang ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'למחיר, זמינות והזמנה' : 'Price, availability and order' ); ?></a>
						<a class="c99-button c99-button-secondary" href="#c99-all-dishes"><?php echo esc_html( $is_he ? 'למנות' : 'Browse dishes' ); ?></a>
					</div>
				</div>
				<figure>
					<?php self::brand_picture( 'c99-food-sabich-pita-gallery-2021-wp-v01', $is_he ? 'סביח בפיתה עם חציל, ביצה, סלטים ורטבים' : 'Sabich in a pita with aubergine, egg, salads and sauces', 1000, 700, true ); ?>
				</figure>
			</div>
		</section>
		<section id="c99-all-dishes" class="c99-consumer-menu-section" aria-labelledby="c99-all-dishes-title">
			<div class="c99-container">
				<div class="c99-consumer-section-heading">
					<div><p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'המנות שלנו' : 'Our dishes' ); ?></p><h2 id="c99-all-dishes-title"><?php echo esc_html( $is_he ? 'פיתה, צלחת או משהו מהסיר' : 'A pita, a plate or something from the pot' ); ?></h2></div>
				</div>
				<?php self::render_menu_filters( $lang ); ?>
				<?php self::render_menu_grid( $lang, 0, true ); ?>
				<p class="c99-dish-no-results" data-c99-filter-empty hidden><?php echo esc_html( $is_he ? 'לא נמצאה מנה בסינון הזה. אפשר לבחור סינון אחר או לפתוח את תפריט ההזמנות.' : 'No dish matched this filter. Choose another filter or open the ordering menu.' ); ?></p>
			</div>
		</section>
		<section class="c99-consumer-editorial">
			<div class="c99-container c99-consumer-editorial-grid">
				<article class="c99-article"><?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
				<aside class="c99-consumer-note">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'חשוב לפני הזמנה' : 'Before ordering' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'הסיר של היום קובע' : 'Today’s kitchen decides' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'מחיר, תוספות וזמינות נבדקים בתפריט ההזמנות. לשאלת רכיבים או אלרגנים מדברים עם הצוות לפני ההזמנה.' : 'Check the ordering menu for current price, options and availability. Speak with the team about ingredients or allergens before ordering.' ); ?></p>
				</aside>
			</div>
		</section>
		<?php self::render_order_band( $lang ); ?>
		<?php
	}

	private static function render_live_ingredient_index( $lang ) {
		if ( ! Complete99_Commerce::catalog_is_ready() || ! class_exists( 'Complete99_Live_Catalog' ) ) {
			return;
		}
		$is_he       = 'he' === $lang;
		$product_ids = Complete99_Commerce::storefront_product_ids();
		?>
		<section class="c99-ingredient-index" aria-labelledby="c99-ingredient-index-title">
			<div class="c99-container">
				<div class="c99-consumer-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מרכיב, מוצר ומנה' : 'Ingredient, product and dish' ); ?></p>
						<h2 id="c99-ingredient-index-title"><?php echo esc_html( $is_he ? 'מדריך המרכיבים של המזווה' : 'The pantry ingredient guide' ); ?></h2>
					</div>
					<a class="c99-text-link" href="<?php echo esc_url( self::route( 'knowledge', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למרכז הידע' : 'Knowledge centre' ); ?></a>
				</div>
				<div class="c99-ingredient-index-grid">
					<?php foreach ( $product_ids as $product_id ) : ?>
						<?php
						$product      = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
						$product_code = (string) get_post_meta( $product_id, '_complete99_catalog_product_code', true );
						$relations    = Complete99_Live_Catalog::relations_for_product_code( $product_code );
						$ingredient   = sanitize_html_class( (string) ( $relations['ingredient_code'] ?? '' ) );
						$name         = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::NAME_HE : Complete99_Commerce::NAME_EN, true );
						$summary      = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::INGREDIENTS_HE : Complete99_Commerce::INGREDIENTS_EN, true );
						if ( ! $product || '' === $ingredient || '' === trim( $name ) ) {
							continue;
						}
						?>
						<article id="<?php echo esc_attr( $ingredient ); ?>" class="c99-ingredient-index-card">
							<?php echo wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'alt' => $name, 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<div>
								<h3><?php echo esc_html( $name ); ?></h3>
								<p><?php echo esc_html( $summary ); ?></p>
								<div class="c99-ingredient-index-links">
									<a href="<?php echo esc_url( self::route( 'store', $lang ) . '#c99-product-code-' . sanitize_html_class( $product_code ) ); ?>"><?php echo esc_html( $is_he ? 'למוצר במזווה' : 'View pantry product' ); ?></a>
									<a href="<?php echo esc_url( self::route( 'knowledge', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למדריכים ומתכונים' : 'Guides and recipes' ); ?></a>
									<?php foreach ( (array) ( $relations['dish_slugs'] ?? array() ) as $dish_slug ) : ?>
										<?php $dish = self::dish_by_slug( $dish_slug ); ?>
										<?php if ( ! empty( $dish ) ) : ?><a href="<?php echo esc_url( Complete99_Frontend::live_dish_url( $dish_slug, $lang ) ); ?>"><?php echo esc_html( $is_he ? $dish['name_he'] : $dish['name_en'] ); ?></a><?php endif; ?>
									<?php endforeach; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}

	private static function render_group_order_teaser( $lang ) {
		$is_he = 'he' === $lang;
		?>
		<section class="c99-group-order-teaser" aria-labelledby="c99-group-order-teaser-title">
			<div class="c99-container c99-group-order-teaser-grid">
				<div class="c99-group-order-teaser-copy">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'אוכלים יחד' : 'Eating together' ); ?></p>
					<h2 id="c99-group-order-teaser-title"><?php echo esc_html( $is_he ? 'מנות למשרד, לצוות ולמפגש' : 'Meals for workplaces, teams and gatherings' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'מספרים לנו כמה אנשים אוכלים, מתי, מה התקציב ואילו העדפות אוכל יש בקבוצה. כך אפשר לבנות בקשה ברורה סביב פיתות, צלחות, סירים ואריזה מתאימה.' : 'Tell us how many people are eating, when, the budget and the group’s food preferences. This creates a clear request around pitas, plates, shared pots and suitable packaging.' ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::route( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'מתכננים ארוחה לקבוצה' : 'Plan a group meal' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בוחרים מנות' : 'Choose dishes' ); ?></a>
					</div>
				</div>
				<ul class="c99-group-order-facts" aria-label="<?php echo esc_attr( $is_he ? 'פרטים שעוזרים לתכנן את הארוחה' : 'Details that help plan the meal' ); ?>">
					<li><strong><?php echo esc_html( $is_he ? 'כמה' : 'How many' ); ?></strong><span><?php echo esc_html( $is_he ? 'מספר הסועדים' : 'Number of diners' ); ?></span></li>
					<li><strong><?php echo esc_html( $is_he ? 'מתי' : 'When' ); ?></strong><span><?php echo esc_html( $is_he ? 'תאריך ושעת הגשה' : 'Date and serving time' ); ?></span></li>
					<li><strong><?php echo esc_html( $is_he ? 'איך' : 'How' ); ?></strong><span><?php echo esc_html( $is_he ? 'איסוף, משלוח ואריזה' : 'Pickup, delivery and packaging' ); ?></span></li>
					<li><strong><?php echo esc_html( $is_he ? 'מה' : 'What' ); ?></strong><span><?php echo esc_html( $is_he ? 'מנות והעדפות קבוצתיות' : 'Dishes and group preferences' ); ?></span></li>
				</ul>
			</div>
		</section>
		<?php
	}

	private static function render_group_order_page( $post, $lang ) {
		$is_he = 'he' === $lang;
		self::render_simple_breadcrumb( $post, $lang );
		?>
		<section class="c99-consumer-page-hero c99-group-order-hero">
			<div class="c99-container c99-consumer-page-hero-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'ארוחה טובה לקבוצה מתחילה בפרטים הנכונים' : 'A good group meal starts with the right details' ); ?></p>
					<h1><?php echo esc_html( $post->post_title ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $post->post_excerpt ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="#c99-group-order-form"><?php echo esc_html( $is_he ? 'שליחת בקשה' : 'Send a request' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לצפייה במנות' : 'Browse dishes' ); ?></a>
					</div>
				</div>
				<figure>
					<?php self::brand_picture( 'c99-food-house-spread-hero-2021-wp-v01', $is_he ? 'מבחר מנות של קומפלט 99 במרכז שולחן משותף' : 'A selection of Complete99 dishes arranged for a shared table', 1400, 788, true ); ?>
					<figcaption><?php echo esc_html( $is_he ? 'מנות מהפיתה, מהצלחת ומהסירים' : 'Food from the pita, the plate and the cooking pots' ); ?></figcaption>
				</figure>
			</div>
		</section>
		<section class="c99-group-order-details" aria-labelledby="c99-group-order-details-title">
			<div class="c99-container">
				<div class="c99-consumer-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מתכננים לפי האנשים והאירוע' : 'Planned around the people and occasion' ); ?></p>
						<h2 id="c99-group-order-details-title"><?php echo esc_html( $is_he ? 'מה כדאי להכין לפני ששולחים בקשה' : 'What to prepare before sending a request' ); ?></h2>
					</div>
				</div>
				<ol class="c99-group-order-steps">
					<li><span>01</span><h3><?php echo esc_html( $is_he ? 'כמות ומועד' : 'Headcount and timing' ); ?></h3><p><?php echo esc_html( $is_he ? 'מספר הסועדים, תאריך ושעת ההגשה הרצויה.' : 'Number of diners, date and preferred serving time.' ); ?></p></li>
					<li><span>02</span><h3><?php echo esc_html( $is_he ? 'סגנון הארוחה' : 'Meal style' ); ?></h3><p><?php echo esc_html( $is_he ? 'פיתות אישיות, צלחות, סירים למרכז השולחן או שילוב ביניהם.' : 'Individual pitas, plates, shared pots or a combination.' ); ?></p></li>
					<li><span>03</span><h3><?php echo esc_html( $is_he ? 'תקציב ואריזה' : 'Budget and packaging' ); ?></h3><p><?php echo esc_html( $is_he ? 'טווח תקציב לאדם והאם נדרשת אריזה אישית או משותפת.' : 'Budget range per person and whether individual or shared packaging is preferred.' ); ?></p></li>
					<li><span>04</span><h3><?php echo esc_html( $is_he ? 'העדפות ובטיחות' : 'Preferences and safety' ); ?></h3><p><?php echo esc_html( $is_he ? 'כמויות קבוצתיות של צמחוני או סגנונות אחרים. שאלות אלרגנים נבדקות בשיחה נפרדת, בלי לכתוב מידע רפואי בטופס.' : 'Group totals for vegetarian or other preferences. Allergen questions are reviewed separately, without entering medical information in the form.' ); ?></p></li>
				</ol>
			</div>
		</section>
		<section class="c99-group-order-content">
			<div class="c99-container c99-consumer-editorial-grid">
				<article class="c99-article"><?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
				<aside class="c99-consumer-note">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מתחילים מהמנות' : 'Start with the food' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'סביח, קובה, קוסקוס, מרקים ועוד' : 'Sabich, kubbeh, couscous, soups and more' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'אפשר לפתוח את ספריית המנות לפני מילוי הבקשה ולציין בטופס מה מתאים לקבוצה.' : 'Browse the dish library before filling in the request and tell us what suits the group.' ); ?></p>
					<a class="c99-text-link" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'View all dishes' ); ?></a>
				</aside>
			</div>
		</section>
		<section id="c99-group-order-form" class="c99-group-order-form-section" aria-labelledby="c99-group-order-form-title">
			<div class="c99-container c99-group-order-form-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'הפרטים לארוחה' : 'Meal details' ); ?></p>
					<h2 id="c99-group-order-form-title"><?php echo esc_html( $is_he ? 'ספרו לנו מה תרצו לארגן' : 'Tell us what you would like to arrange' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'הבקשה מגיעה לצוות קומפלט 99. לאחר בדיקת הפרטים ניצור קשר כדי לעבור יחד על המנות, הכמויות וההגשה.' : 'Your request reaches the Complete99 team. After reviewing it, we will contact you to go through dishes, quantities and service.' ); ?></p>
				</div>
				<div class="c99-group-order-form-card">
					<?php Complete99_Leads::render_form( $lang, 'group-order' ); ?>
				</div>
			</div>
		</section>
		<?php self::render_order_band( $lang ); ?>
		<?php
	}

	private static function render_store_page( $post, $lang ) {
		$is_he = 'he' === $lang;
		if ( Complete99_Commerce::catalog_is_ready() || Complete99_Commerce::can_preview_commerce() ) {
			self::render_live_store_page( $post, $lang );
			return;
		}
		self::render_simple_breadcrumb( $post, $lang );
		?>
		<section class="c99-consumer-page-hero c99-pantry-hero">
			<div class="c99-container c99-consumer-page-hero-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מה בא לכם לאכול?' : 'What would you like to eat?' ); ?></p>
					<h1><?php echo esc_html( $is_he ? 'מתחילים מהמנות' : 'Start with the dishes' ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $is_he ? 'סביח בפיתה, צלחת חמה, קובה, קוסקוס, מרק ומנות מהסירים.' : 'Sabich in a pita, a warm plate, kubbeh, couscous, soup and food from the pots.' ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'Browse all dishes' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Commerce::order_url( $lang ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'לתפריט ולהזמנה' : 'Open menu and order' ); ?></a>
					</div>
				</div>
				<figure><?php self::brand_picture( 'c99-food-sabich-pita-gallery-2021-wp-v01', $is_he ? 'סביח בפיתה עם חציל, ביצה, סלטים ורטבים' : 'Sabich in a pita with aubergine, egg, salads and sauces', 1000, 700, true ); ?></figure>
			</div>
		</section>
		<?php self::render_order_band( $lang ); ?>
		<?php
	}

	private static function render_live_store_page( $post, $lang ) {
		$is_he          = 'he' === $lang;
		$product_ids    = Complete99_Commerce::storefront_product_ids();
		$catalog_ready  = Complete99_Commerce::catalog_is_ready();
		$cart_ready     = Complete99_Commerce::cart_is_ready();
		$checkout_ready = Complete99_Commerce::is_ready();
		$cart_url       = Complete99_Commerce::transaction_url( 'cart', $lang );
		$checkout_url   = Complete99_Commerce::transaction_url( 'checkout', $lang );
		self::render_simple_breadcrumb( $post, $lang );
		?>
		<section class="c99-consumer-page-hero c99-live-store-hero">
			<div class="c99-container">
				<?php if ( Complete99_Commerce::can_preview_commerce() && ! $catalog_ready ) : ?>
					<p class="c99-commerce-preview-note"><?php echo esc_html( $is_he ? 'תצוגת קבלה פרטית. החנות עדיין סגורה לציבור.' : 'Private acceptance preview. The store is still closed to the public.' ); ?></p>
				<?php endif; ?>
				<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'המזווה פתוח' : 'The pantry is open' ); ?></p>
				<h1><?php echo esc_html( $post->post_title ); ?></h1>
				<p class="c99-hero-summary"><?php echo esc_html( $is_he ? 'מוצרי מזווה עם מחיר, משקל, רכיבים, מלאי ותנאי קבלה עדכניים.' : 'Pantry goods with current price, weight, ingredients, stock and fulfilment details.' ); ?></p>
				<div class="c99-hero-actions">
					<?php if ( $cart_ready ) : ?>
					<a class="c99-button c99-button-primary" href="<?php echo esc_url( $cart_url ); ?>"><?php echo esc_html( $is_he ? 'לסל' : 'View cart' ); ?></a>
					<?php else : ?>
						<a class="c99-button c99-button-primary" href="tel:035231810"><?php echo esc_html( $is_he ? 'הזמנה בטלפון' : 'Order by phone' ); ?></a>
					<?php endif; ?>
					<?php if ( $checkout_ready ) : ?>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( $checkout_url ); ?>"><?php echo esc_html( $is_he ? 'לתשלום' : 'Checkout' ); ?></a>
					<?php else : ?>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למנות המוכנות' : 'Prepared dishes' ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( $cart_ready ) : ?>
					<?php self::render_store_cart_feedback( $lang, $cart_url ); ?>
				<?php endif; ?>
			</div>
		</section>
		<section class="c99-live-store-products" aria-labelledby="c99-live-store-title">
			<div class="c99-container">
				<div class="c99-consumer-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מה אפשר לקחת הביתה' : 'What you can take home' ); ?></p>
						<h2 id="c99-live-store-title"><?php echo esc_html( $is_he ? 'מוצרי המזווה' : 'Pantry goods' ); ?></h2>
					</div>
					<a class="c99-text-link" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'חזרה למנות' : 'Back to the dishes' ); ?></a>
				</div>
				<?php self::render_store_filters( $lang, count( $product_ids ) ); ?>
				<div class="c99-live-store-grid" data-c99-product-grid>
					<?php foreach ( $product_ids as $product_id ) : ?>
						<?php self::render_store_product_card( $product_id, $lang ); ?>
					<?php endforeach; ?>
				</div>
				<p class="c99-product-filter-empty" data-c99-product-filter-empty hidden><?php echo esc_html( $is_he ? 'לא נמצאו מוצרים בסינון הזה.' : 'No products matched this filter.' ); ?></p>
			</div>
		</section>
		<?php
	}

	private static function render_store_cart_feedback( $lang, $cart_url ) {
		$is_he       = 'he' === $lang;
		$woocommerce = function_exists( 'WC' ) ? WC() : null;
		$cart_count  = is_object( $woocommerce )
			&& isset( $woocommerce->cart )
			&& is_object( $woocommerce->cart )
			&& method_exists( $woocommerce->cart, 'get_cart_contents_count' )
			? absint( $woocommerce->cart->get_cart_contents_count() )
			: 0;
		?>
		<div class="c99-store-cart-feedback">
			<?php
			if ( function_exists( 'woocommerce_output_all_notices' ) ) {
				woocommerce_output_all_notices();
			} elseif ( function_exists( 'wc_print_notices' ) ) {
				wc_print_notices();
			}
			?>
			<p role="status" aria-live="polite" aria-atomic="true" data-c99-cart-count>
				<a href="<?php echo esc_url( $cart_url ); ?>">
					<?php echo esc_html( $is_he ? sprintf( 'עכשיו יש בסל %d פריטים', $cart_count ) : sprintf( '%d items are now in your cart', $cart_count ) ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	private static function render_store_filters( $lang, $count ) {
		$is_he = 'he' === $lang;
		$filters = array(
			'all'             => $is_he ? 'הכול' : 'All',
			'pantry'          => $is_he ? 'מזווה' : 'Pantry',
			'fresh-produce'   => $is_he ? 'תוצרת טרייה' : 'Fresh produce',
			'chilled-frozen'  => $is_he ? 'קירור והקפאה' : 'Chilled and frozen',
			'bakery'          => $is_he ? 'מאפים' : 'Bakery',
			'regulated'       => $is_he ? 'בפיקוח' : 'Regulated',
		);
		?>
		<div class="c99-product-filter" data-c99-product-filter>
			<div class="c99-product-filter-heading">
				<strong><?php echo esc_html( $is_he ? 'סינון מוצרים' : 'Filter products' ); ?></strong>
				<span aria-live="polite" aria-atomic="true" data-c99-product-filter-count><?php echo esc_html( $is_he ? $count . ' מוצרים' : $count . ' products' ); ?></span>
			</div>
			<div class="c99-product-filter-buttons" role="group" aria-label="<?php echo esc_attr( $is_he ? 'סינון לפי סוג מוצר' : 'Filter by product type' ); ?>">
				<?php foreach ( $filters as $code => $label ) : ?>
					<button type="button" data-c99-product-filter-button="<?php echo esc_attr( $code ); ?>" aria-pressed="<?php echo 'all' === $code ? 'true' : 'false'; ?>" class="<?php echo 'all' === $code ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	private static function render_store_product_card( $product_id, $lang ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( absint( $product_id ) ) : false;
		if ( ! $product ) {
			return;
		}
		$is_he       = 'he' === $lang;
		$name        = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::NAME_HE : Complete99_Commerce::NAME_EN, true );
		$description = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::DESCRIPTION_HE : Complete99_Commerce::DESCRIPTION_EN, true );
		$ingredients = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::INGREDIENTS_HE : Complete99_Commerce::INGREDIENTS_EN, true );
		$allergens   = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::ALLERGENS_HE : Complete99_Commerce::ALLERGENS_EN, true );
		$storage     = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::STORAGE_HE : Complete99_Commerce::STORAGE_EN, true );
		$fulfilment  = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::FULFILMENT_HE : Complete99_Commerce::FULFILMENT_EN, true );
		$facet       = sanitize_key( (string) get_post_meta( $product_id, '_complete99_live_catalog_facet', true ) );
		$package     = (string) get_post_meta( $product_id, $is_he ? '_complete99_live_catalog_package_he' : '_complete99_live_catalog_package_en', true );
		$product_code= (string) get_post_meta( $product_id, '_complete99_catalog_product_code', true );
		$relations   = class_exists( 'Complete99_Live_Catalog' ) ? Complete99_Live_Catalog::relations_for_product_code( $product_code ) : array();
		$guide_url   = self::route( 'ingredients', $lang ) . '#' . sanitize_html_class( (string) ( $relations['ingredient_code'] ?? '' ) );
		$science_entity_id = sanitize_key( (string) ( $relations['science_entity_id'] ?? '' ) );
		if ( '' !== $science_entity_id && class_exists( 'Complete99_Culinary_Science' ) ) {
			$science_bundle = Complete99_Culinary_Science::public_page_bundle_for_id( $science_entity_id, $lang );
			if ( ! empty( $science_bundle['canonical_path'] ) ) {
				$guide_url = home_url( $science_bundle['canonical_path'] );
			}
		}
		$can_purchase = Complete99_Commerce::cart_is_ready() && $product->is_in_stock() && $product->is_purchasable();
		$facet_labels = array(
			'pantry'         => $is_he ? 'מזווה' : 'Pantry',
			'fresh-produce'  => $is_he ? 'תוצרת טרייה' : 'Fresh produce',
			'chilled-frozen' => $is_he ? 'קירור והקפאה' : 'Chilled and frozen',
			'bakery'         => $is_he ? 'מאפים' : 'Bakery',
			'regulated'      => $is_he ? 'בפיקוח' : 'Regulated',
		);
		$weight_unit = (string) get_option( 'woocommerce_weight_unit', 'kg' );
		$action_url  = add_query_arg(
			array(
				'add-to-cart' => absint( $product_id ),
				'lang'        => $lang,
			),
			self::route( 'store', $lang )
		);
		?>
		<article id="c99-product-code-<?php echo esc_attr( sanitize_html_class( $product_code ) ); ?>" class="c99-store-product-card" data-c99-product-card data-c99-product-facets="<?php echo esc_attr( $facet ); ?>" data-c99-product-id="<?php echo esc_attr( $product_id ); ?>" tabindex="-1">
			<figure><?php echo wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'alt' => $name, 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
			<div class="c99-store-product-copy">
				<p class="c99-store-product-status"><?php echo esc_html( $product->is_in_stock() ? ( $is_he ? 'במלאי' : 'In stock' ) : ( $is_he ? 'אזל זמנית' : 'Temporarily out of stock' ) ); ?></p>
				<div class="c99-store-product-badges">
					<?php if ( isset( $facet_labels[ $facet ] ) ) : ?><span><?php echo esc_html( $facet_labels[ $facet ] ); ?></span><?php endif; ?>
					<?php if ( '' !== trim( $package ) ) : ?><span><?php echo esc_html( $package ); ?></span><?php endif; ?>
				</div>
				<h3><?php echo esc_html( $name ); ?></h3>
				<p><?php echo esc_html( $description ); ?></p>
				<dl class="c99-store-product-facts">
					<div><dt><?php echo esc_html( $is_he ? 'משקל' : 'Weight' ); ?></dt><dd><?php echo esc_html( wc_format_localized_decimal( $product->get_weight() ) . ' ' . $weight_unit ); ?></dd></div>
					<div><dt><?php echo esc_html( $is_he ? 'רכיבים' : 'Ingredients' ); ?></dt><dd><?php echo esc_html( $ingredients ); ?></dd></div>
					<div><dt><?php echo esc_html( $is_he ? 'אלרגנים' : 'Allergens' ); ?></dt><dd><?php echo esc_html( $allergens ); ?></dd></div>
					<div><dt><?php echo esc_html( $is_he ? 'אחסון' : 'Storage' ); ?></dt><dd><?php echo esc_html( $storage ); ?></dd></div>
					<div><dt><?php echo esc_html( $is_he ? 'איסוף עצמי' : 'Pickup' ); ?></dt><dd><?php echo esc_html( $fulfilment ); ?></dd></div>
				</dl>
				<div class="c99-store-product-relations" aria-label="<?php echo esc_attr( $is_he ? 'קשרים קולינריים' : 'Culinary connections' ); ?>">
					<a href="<?php echo esc_url( $guide_url ); ?>"><?php echo esc_html( $is_he ? 'מדריך המרכיב' : 'Ingredient guide' ); ?></a>
					<?php foreach ( (array) ( $relations['dish_slugs'] ?? array() ) as $dish_slug ) : ?>
						<?php $related_dish = self::dish_by_slug( $dish_slug ); ?>
						<?php if ( ! empty( $related_dish ) ) : ?><a href="<?php echo esc_url( Complete99_Frontend::live_dish_url( $dish_slug, $lang ) ); ?>"><?php echo esc_html( $is_he ? $related_dish['name_he'] : $related_dish['name_en'] ); ?></a><?php endif; ?>
					<?php endforeach; ?>
				</div>
				<div class="c99-store-product-purchase">
					<strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong>
					<?php if ( $can_purchase ) : ?>
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( $action_url ); ?>"><?php echo esc_html( $is_he ? 'הוספה לסל' : 'Add to cart' ); ?></a>
					<?php else : ?>
						<span class="c99-button c99-button-secondary" aria-disabled="true"><?php echo esc_html( $product->is_in_stock() ? ( $is_he ? 'לא זמין לרכישה' : 'Unavailable for purchase' ) : ( $is_he ? 'אזל זמנית' : 'Temporarily out of stock' ) ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php
	}

	private static function render_related_store_products( $dish, $lang ) {
		if ( ! Complete99_Commerce::catalog_is_ready() || ! class_exists( 'Complete99_Live_Catalog' ) ) {
			return;
		}
		$product_ids = Complete99_Live_Catalog::product_ids_for_dish_slug( (string) ( $dish['slug'] ?? '' ) );
		if ( empty( $product_ids ) ) {
			return;
		}
		$is_he = 'he' === $lang;
		?>
		<section class="c99-related-store-products" aria-labelledby="c99-related-store-products-title">
			<div class="c99-container">
				<div class="c99-consumer-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'קשרים קולינריים' : 'Culinary connections' ); ?></p>
						<h2 id="c99-related-store-products-title"><?php echo esc_html( $is_he ? 'מוצרים קשורים במזווה' : 'Related pantry products' ); ?></h2>
					</div>
					<a class="c99-text-link" href="<?php echo esc_url( self::route( 'store', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המוצרים' : 'All products' ); ?></a>
				</div>
				<div class="c99-related-store-grid">
					<?php foreach ( $product_ids as $product_id ) : ?>
						<?php $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false; ?>
						<?php if ( ! $product ) : ?><?php continue; ?><?php endif; ?>
						<?php $name = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::NAME_HE : Complete99_Commerce::NAME_EN, true ); ?>
						<?php $product_code = (string) get_post_meta( $product_id, '_complete99_catalog_product_code', true ); ?>
						<?php if ( '' === $product_code ) : ?><?php continue; ?><?php endif; ?>
						<a href="<?php echo esc_url( self::route( 'store', $lang ) . '#c99-product-code-' . sanitize_html_class( $product_code ) ); ?>">
							<?php echo wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'alt' => $name, 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<strong><?php echo esc_html( $name ); ?></strong>
							<span><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}

	public static function render_transaction_page( $surface, $lang ) {
		$is_he = 'he' === $lang;
		$labels = array(
			'cart' => array(
				'he'       => 'סל הקניות',
				'en'       => 'Shopping cart',
				'shortcode'=> '[woocommerce_cart]',
			),
			'checkout' => array(
				'he'       => 'תשלום מאובטח',
				'en'       => 'Secure checkout',
				'shortcode'=> '[woocommerce_checkout]',
			),
			'account' => array(
				'he'       => 'החשבון שלי',
				'en'       => 'My account',
				'shortcode'=> '[woocommerce_my_account]',
			),
		);
		$surface = isset( $labels[ $surface ] ) ? $surface : 'cart';
		$label   = $labels[ $surface ];
		?>
		<section class="c99-consumer-page-hero c99-transaction-hero">
			<div class="c99-container">
				<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'המזווה של קומפלט 99' : 'The Complete99 pantry' ); ?></p>
				<h1><?php echo esc_html( $is_he ? $label['he'] : $label['en'] ); ?></h1>
				<p class="c99-hero-summary"><?php echo esc_html( $is_he ? 'המחיר, המלאי והאיסוף העצמי נבדקים שוב לפני אישור ההזמנה.' : 'Price, stock and pickup are checked again before the order is confirmed.' ); ?></p>
				<div class="c99-hero-actions">
					<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::route( 'store', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'חזרה למזווה' : 'Back to the pantry' ); ?></a>
					<?php if ( 'cart' !== $surface ) : ?><a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Commerce::transaction_url( 'cart', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לסל' : 'View cart' ); ?></a><?php endif; ?>
				</div>
			</div>
		</section>
		<section class="c99-transaction-surface">
			<div class="c99-container">
				<?php echo do_shortcode( $label['shortcode'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
	}

	private static function render_pantry_teaser( $lang ) {
		$is_he = 'he' === $lang;
		if ( Complete99_Commerce::catalog_is_ready() || Complete99_Commerce::can_preview_commerce() ) {
			$product_ids = Complete99_Commerce::storefront_product_ids();
			$product_id  = ! empty( $product_ids ) ? absint( $product_ids[0] ) : 0;
			$product     = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
			$name        = $product_id
				? (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::NAME_HE : Complete99_Commerce::NAME_EN, true )
				: '';
			?>
			<section class="c99-home-pantry c99-home-pantry-live">
				<div class="c99-container c99-home-pantry-grid">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'המזווה פתוח' : 'The pantry is open' ); ?></p>
						<h2><?php echo esc_html( $is_he ? 'מוצרים אמיתיים לקחת הביתה' : 'Real pantry goods to take home' ); ?></h2>
						<p><?php echo esc_html( $is_he ? 'לכל מוצר מוצגים מחיר, משקל, רכיבים, אלרגנים, אחסון ותנאי איסוף עצמי.' : 'Every product shows its price, weight, ingredients, allergens, storage and pickup terms.' ); ?></p>
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::route( 'store', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למוצרי המזווה' : 'Shop the pantry' ); ?></a>
					</div>
					<?php if ( $product ) : ?><figure><?php echo wp_get_attachment_image( $product->get_image_id(), 'large', false, array( 'alt' => $name, 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><figcaption><?php echo esc_html( $name ); ?></figcaption></figure><?php endif; ?>
				</div>
			</section>
			<?php
			return;
		}
		return;
	}

	private static function render_generic_page( $post, $lang, $key ) {
		$is_he = 'he' === $lang;
		$media = array(
			'about'       => array( 'c99-food-house-spread-hero-2021-wp-v01', $is_he ? 'מבחר מנות של קומפלט 99' : 'A selection of Complete99 dishes' ),
			'contact'     => array( 'c99-food-sabich-pita-gallery-2021-wp-v01', $is_he ? 'סביח בפיתה' : 'Sabich in a pita' ),
			'ingredients' => array( 'c99-food-shakshuka-plate-gallery-2021-wp-v01', $is_he ? 'שקשוקה בצלחת' : 'Shakshuka on a plate' ),
			'traditions'  => array( 'c99-food-couscous-beef-gallery-2021-wp-v01', $is_he ? 'קוסקוס עם ירקות ובשר' : 'Couscous with vegetables and beef' ),
			'knowledge'   => array( 'c99-food-kubeh-beet-soup-gallery-2021-wp-v01', $is_he ? 'קובה במרק סלק' : 'Kubbeh in beet soup' ),
		);
		$selected = isset( $media[ $key ] ) ? $media[ $key ] : $media['about'];
		$continuations = array(
			array( 'dishes', $is_he ? 'כל המנות' : 'All dishes' ),
			array( 'ingredients', $is_he ? 'מרכיבים' : 'Ingredients' ),
			array( 'traditions', $is_he ? 'סיפורי אוכל' : 'Food stories' ),
			array( 'knowledge', $is_he ? 'מדריכים' : 'Guides' ),
		);
		self::render_simple_breadcrumb( $post, $lang );
		?>
		<section class="c99-consumer-page-hero">
			<div class="c99-container c99-consumer-page-hero-grid">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( self::eyebrow( $key, $lang ) ); ?></p>
					<h1><?php echo esc_html( $post->post_title ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $post->post_excerpt ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למנות' : 'Explore dishes' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Commerce::order_url( $lang ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'לתפריט ההזמנות' : 'Open ordering menu' ); ?></a>
					</div>
				</div>
				<figure><?php self::brand_picture( $selected[0], $selected[1], 1000, 700, true ); ?></figure>
			</div>
		</section>
		<?php if ( 'ingredients' === $key ) : ?><?php self::render_live_ingredient_index( $lang ); ?><?php endif; ?>
		<section class="c99-consumer-editorial">
			<div class="c99-container c99-consumer-editorial-grid">
				<article class="c99-article"><?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
				<aside class="c99-consumer-note">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'ממשיכים מכאן' : 'Continue from here' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'האוכל נשאר במרכז' : 'Keep the food at the centre' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'עברו לספריית המנות או למרכז ידע קולינרי נוסף. למחיר וזמינות משתמשים בתפריט ההזמנות הנוכחי.' : 'Continue to the dish library or another culinary knowledge hub. Use the current ordering menu for price and availability.' ); ?></p>
					<div class="c99-note-links">
						<?php foreach ( $continuations as $link ) : ?>
							<?php if ( $link[0] === $key ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<a href="<?php echo esc_url( self::route( $link[0], $lang ) ); ?>"><?php echo esc_html( $link[1] ); ?></a>
						<?php endforeach; ?>
					</div>
				</aside>
			</div>
		</section>
		<?php self::render_order_band( $lang ); ?>
		<?php
	}

	private static function eyebrow( $key, $lang ) {
		$is_he  = 'he' === $lang;
		$labels = array(
			'about'         => $is_he ? 'מי אנחנו ומה מבשלים' : 'Who we are and what we cook',
			'contact'       => $is_he ? 'אבן גבירול 99, תל אביב' : '99 Ibn Gabirol, Tel Aviv',
			'ingredients'   => $is_he ? 'להבין את הטעם' : 'Understand the flavour',
			'traditions'    => $is_he ? 'אוכל נושא זיכרון' : 'Food carries memory',
			'knowledge'     => $is_he ? 'קוראים, מבשלים ומבינים' : 'Read, cook and understand',
			'privacy'       => $is_he ? 'פרטיות' : 'Privacy',
			'terms'         => $is_he ? 'תנאי שימוש' : 'Terms of use',
			'accessibility' => $is_he ? 'נגישות' : 'Accessibility',
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : ( $is_he ? 'קומפלט 99' : 'Complete99' );
	}

	private static function render_simple_breadcrumb( $post, $lang ) {
		$is_he = 'he' === $lang;
		?>
		<nav class="c99-breadcrumb c99-container" aria-label="<?php echo esc_attr( $is_he ? 'פירורי לחם' : 'Breadcrumb' ); ?>">
			<a href="<?php echo esc_url( self::route( 'home', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בית' : 'Home' ); ?></a>
			<span aria-hidden="true">/</span>
			<span aria-current="page"><?php echo esc_html( $post->post_title ); ?></span>
		</nav>
		<?php
	}

	public static function render_live_dish_page( $dish, $lang ) {
		$is_he       = 'he' === $lang;
		$name        = $is_he ? $dish['name_he'] : $dish['name_en'];
		$description = $is_he ? $dish['description_he'] : $dish['description_en'];
		$category    = $is_he ? ( isset( $dish['category_he'] ) ? $dish['category_he'] : '' ) : ( isset( $dish['category_en'] ) ? $dish['category_en'] : '' );
		$tag         = $is_he ? ( isset( $dish['tag_he'] ) ? $dish['tag_he'] : '' ) : ( isset( $dish['tag_en'] ) ? $dish['tag_en'] : '' );
		$image       = self::image_url( $dish );
		if ( ! $image ) {
			$visual_copy = $is_he
				? 'כאן מרוכזים תיאור המנה, סגנון ההגשה והדרך להמשיך להזמנה.'
				: 'This page brings together the dish description, serving style and the way to continue to ordering.';
		} else {
			$visual_copy = $is_he
				? 'הצילום והתיאור מציגים יחד את האופי, המרקם וסגנון ההגשה של המנה.'
				: 'The photograph and description present the character, texture and serving style of the dish together.';
		}
		$hub_id      = Complete99_Content::find_translation_post_id( 'dishes', $lang, true );
		self::render_header( $hub_id, $lang, $dish['slug'] );
		?>
		<main id="c99-main" tabindex="-1">
			<nav class="c99-breadcrumb c99-container" aria-label="<?php echo esc_attr( $is_he ? 'פירורי לחם' : 'Breadcrumb' ); ?>">
				<?php foreach ( Complete99_Frontend::live_dish_breadcrumb_items( $dish, $lang ) as $position => $breadcrumb ) : ?>
					<?php if ( $position ) : ?><span aria-hidden="true">/</span><?php endif; ?>
					<?php if ( $position < 2 ) : ?>
						<a href="<?php echo esc_url( $breadcrumb['url'] ); ?>"><?php echo esc_html( $breadcrumb['label'] ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $breadcrumb['label'] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
			<article class="c99-consumer-dish">
				<div class="c99-container c99-consumer-dish-grid">
					<div class="c99-consumer-dish-copy">
						<div class="c99-consumer-menu-meta"><span><?php echo esc_html( $category ); ?></span><span><?php echo esc_html( $is_he ? 'מחיר וזמינות בתפריט ההזמנות' : 'Price and availability in the ordering menu' ); ?></span></div>
						<h1><?php echo esc_html( $name ); ?></h1>
						<p class="c99-hero-summary"><?php echo esc_html( $description ); ?></p>
						<?php self::render_dish_badges( $dish, $lang ); ?>
						<?php if ( $tag ) : ?><div class="c99-consumer-dish-facts"><span><?php echo esc_html( $tag ); ?></span></div><?php endif; ?>
						<div class="c99-hero-actions">
							<a class="c99-button c99-button-primary" href="<?php echo esc_url( Complete99_Commerce::order_url( $lang ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'למחיר, זמינות והזמנה' : 'Price, availability and order' ); ?></a>
							<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'All dishes' ); ?></a>
						</div>
					</div>
					<?php if ( $image ) : ?><figure><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="1000" height="760" decoding="async" fetchpriority="high" /></figure><?php endif; ?>
				</div>
				<?php self::render_dish_component_tree( $dish, $lang ); ?>
				<?php self::render_related_store_products( $dish, $lang ); ?>
				<div class="c99-container c99-dish-clarity-grid">
					<section><span>01</span><h2><?php echo esc_html( $is_he ? 'מה רואים כאן' : 'What this page shows' ); ?></h2><p><?php echo esc_html( $visual_copy ); ?></p></section>
					<section><span>02</span><h2><?php echo esc_html( $is_he ? 'ממשיכים להזמנה' : 'Continue to ordering' ); ?></h2><p><?php echo esc_html( $is_he ? 'בתפריט ההזמנות בוחרים גודל, תוספות ואפשרויות זמינות ורואים את המחיר.' : 'Use the ordering menu to choose size, sides and available options and see the price.' ); ?></p></section>
					<section><span>03</span><h2><?php echo esc_html( $is_he ? 'רכיבים ואלרגנים' : 'Ingredients and allergens' ); ?></h2><p><?php echo esc_html( $is_he ? 'לשאלה תזונתית או על אלרגן מדברים עם הצוות לפני ההזמנה.' : 'Speak with the team before ordering about dietary needs or allergens.' ); ?></p></section>
				</div>
			</article>
			<?php self::render_order_band( $lang ); ?>
		</main>
		<?php self::render_footer( $lang ); ?>
		<?php
	}

	public static function render_site_not_found_page( $lang ) {
		$is_he   = 'he' === $lang;
		$home_id = Complete99_Content::find_translation_post_id( 'home', $lang, true );
		self::render_header( $home_id, $lang );
		?>
		<main id="c99-main" tabindex="-1">
			<section class="c99-consumer-not-found">
				<div class="c99-container">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? '404 · העמוד לא נמצא' : '404 · Page not found' ); ?></p>
					<h1><?php echo esc_html( $is_he ? 'העמוד שחיפשתם לא נמצא' : 'The page you were looking for was not found' ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $is_he ? 'הכתובת שביקשתם אינה זמינה. אפשר לחזור לעמוד הבית או לפתוח את תפריט המנות.' : 'The address you requested is unavailable. Return home or open the dish menu.' ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::route( 'home', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לעמוד הבית' : 'Return home' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'View all dishes' ); ?></a>
					</div>
				</div>
			</section>
		</main>
		<?php
		self::render_footer( $lang );
	}

	public static function render_not_found_page( $lang ) {
		$is_he  = 'he' === $lang;
		$hub_id = Complete99_Content::find_translation_post_id( 'dishes', $lang, true );
		self::render_header( $hub_id, $lang );
		?>
		<main id="c99-main" tabindex="-1">
			<section class="c99-consumer-not-found">
				<div class="c99-container">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'המנה לא נמצאה' : 'Dish not found' ); ?></p>
					<h1><?php echo esc_html( $is_he ? 'כנראה שהכתובת השתנתה' : 'That dish address may have changed' ); ?></h1>
					<p><?php echo esc_html( $is_he ? 'אפשר לחזור לכל המנות או לפתוח את תפריט ההזמנות הנוכחי.' : 'Return to all dishes or open the current ordering menu.' ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'All dishes' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Commerce::order_url( $lang ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'לתפריט ההזמנות' : 'Ordering menu' ); ?></a>
					</div>
				</div>
			</section>
		</main>
		<?php self::render_footer( $lang ); ?>
		<?php
	}

	private static function render_order_band( $lang ) {
		$is_he = 'he' === $lang;
		?>
		<section class="c99-consumer-order-band">
			<div class="c99-container c99-consumer-order-band-inner">
				<div><p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'רעבים עכשיו' : 'Hungry now' ); ?></p><h2><?php echo esc_html( $is_he ? 'בודקים מה זמין ומזמינים' : 'Check what is available and order' ); ?></h2><p><?php echo esc_html( $is_he ? 'המחיר, התוספות והזמינות נקבעים בתפריט ההזמנות הנוכחי.' : 'Current price, options and availability are shown in the ordering menu.' ); ?></p></div>
				<div class="c99-consumer-order-actions">
					<a class="c99-button c99-button-light" href="<?php echo esc_url( Complete99_Commerce::order_url( $lang ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'להזמנה ב-Wolt' : 'Order on Wolt' ); ?></a>
					<a class="c99-button c99-button-ghost" href="<?php echo esc_attr( $is_he ? 'tel:035231810' : 'tel:+97235231810' ); ?>"><?php echo esc_html( $is_he ? '03-523-1810' : '+972 3 523 1810' ); ?></a>
				</div>
			</div>
		</section>
		<?php
	}

	public static function render_footer( $lang ) {
		$is_he       = 'he' === $lang;
		$museum      = class_exists( 'Complete99_Culinary_Science' )
			? Complete99_Culinary_Science::public_museum_root_projection( $lang )
			: array();
		$food_nav    = array(
			array( 'dishes', $is_he ? 'כל המנות' : 'All dishes' ),
			array( 'ingredients', $is_he ? 'מרכיבים' : 'Ingredients' ),
			array( 'traditions', $is_he ? 'סיפורי אוכל' : 'Food stories' ),
			array( 'knowledge', $is_he ? 'מדריכים' : 'Guides' ),
		);
		if ( ! empty( $museum['seo']['canonical_path'] ) ) {
			array_splice(
				$food_nav,
				2,
				0,
				array( array( 'museum', $is_he ? 'מוזיאון הקולינריה' : 'Culinary museum', home_url( $museum['seo']['canonical_path'] ) ) )
			);
		}
		$company_nav = array(
			array( 'about', $is_he ? 'הסיפור שלנו' : 'Our story' ),
			array( 'proposal', $is_he ? 'לקבוצות ולמשרדים' : 'Groups and workplaces' ),
			array( 'contact', $is_he ? 'מגיעים אלינו' : 'Visit and contact' ),
		);
		if ( Complete99_Commerce::catalog_is_ready() || Complete99_Commerce::can_preview_commerce() ) {
			array_splice( $company_nav, 2, 0, array( array( 'store', $is_he ? 'המזווה' : 'Pantry shop' ) ) );
		}
		$clusters = array(
			array(
				$is_he ? 'אוכל' : 'Food',
				$food_nav,
			),
			array(
				$is_he ? 'קומפלט 99' : 'Complete99',
				$company_nav,
			),
			array(
				$is_he ? 'מידע' : 'Information',
				array(
					array( 'privacy', $is_he ? 'פרטיות' : 'Privacy' ),
					array( 'terms', $is_he ? 'תנאי שימוש' : 'Terms of use' ),
					array( 'accessibility', $is_he ? 'נגישות' : 'Accessibility' ),
				),
			),
		);
		?>
		<footer class="c99-site-footer c99-consumer-footer">
			<div class="c99-container c99-consumer-footer-top">
				<div class="c99-footer-brand">
					<a class="c99-footer-brand-link" href="<?php echo esc_url( self::route( 'home', $lang ) ); ?>">
						<span class="c99-brand-mark" aria-hidden="true"><span>9</span><span>9</span></span>
						<span><strong><?php echo esc_html( $is_he ? 'קומפלט 99' : 'Complete99' ); ?></strong><small><?php echo esc_html( $is_he ? 'סביח ואוכל של בית' : 'Sabich and home cooking' ); ?></small></span>
					</a>
					<p><?php echo esc_html( $is_he ? 'אבן גבירול 99, תל אביב. מנות מהפיתה, מהצלחת ומהסירים.' : '99 Ibn Gabirol, Tel Aviv. Food from the pita, the plate and the cooking pots.' ); ?></p>
					<div class="c99-footer-direct-links"><a href="<?php echo esc_attr( $is_he ? 'tel:035231810' : 'tel:+97235231810' ); ?>"><?php echo esc_html( $is_he ? '03-523-1810' : '+972 3 523 1810' ); ?></a><a href="<?php echo esc_url( Complete99_Commerce::order_url( $lang ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'הזמנה ב-Wolt' : 'Order on Wolt' ); ?></a></div>
				</div>
				<nav class="c99-footer-nav" aria-label="<?php echo esc_attr( $is_he ? 'מפת האתר' : 'Site map' ); ?>">
					<?php foreach ( $clusters as $cluster ) : ?>
						<div class="c99-footer-cluster">
							<h2><?php echo esc_html( $cluster[0] ); ?></h2>
							<ul><?php foreach ( $cluster[1] as $link ) : ?><li><a href="<?php echo esc_url( isset( $link[2] ) ? $link[2] : self::route( $link[0], $lang ) ); ?>"><?php echo esc_html( $link[1] ); ?></a></li><?php endforeach; ?></ul>
						</div>
					<?php endforeach; ?>
				</nav>
			</div>
			<div class="c99-container c99-footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Complete99</span><span><?php echo esc_html( $is_he ? 'מחיר וזמינות נבדקים לפני הזמנה.' : 'Check price and availability before ordering.' ); ?></span></div>
		</footer>
		<?php
	}
}
