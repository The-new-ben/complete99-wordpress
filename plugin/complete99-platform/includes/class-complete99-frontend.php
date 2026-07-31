<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Frontend {
	public static function boot() {
		add_filter( 'post_type_link', array( 'Complete99_Content', 'filter_post_type_link' ), 10, 2 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ) );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'document_title' ) );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_head', array( __CLASS__, 'head_metadata' ), 4 );
		add_action( 'template_redirect', array( __CLASS__, 'remove_core_canonical' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_unready_store' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'protect_unready_dishes' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_live_dish' ), 2 );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'complete99_live_dish';
		$vars[] = 'complete99_live_lang';
		return $vars;
	}

	public static function remove_core_canonical() {
		if ( is_404()
			|| self::is_live_dish_request()
			|| self::is_consumer_transaction_request()
			|| ( is_singular() && Complete99_Content::is_complete99_post( get_queried_object_id() ) ) ) {
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

	public static function maybe_redirect_unready_store() {
		if (
			! is_singular()
			|| Complete99_Commerce::is_ready()
			|| Complete99_Commerce::can_preview_commerce()
		) {
			return;
		}

		$post_id = get_queried_object_id();
		if (
			! $post_id
			|| ! Complete99_Content::is_complete99_post( $post_id )
			|| 'store' !== Complete99_Content::translation_group_for_post( $post_id )
		) {
			return;
		}

		$lang        = Complete99_Content::language_for_post( $post_id );
		$destination = Complete99_Content::route_url( 'dishes', $lang );
		if ( ! $destination ) {
			$destination = home_url( 'en' === $lang ? '/en/' : '/' );
		}
		wp_safe_redirect( $destination, 302, 'Complete99' );
		exit;
	}

	public static function template_include( $template ) {
		if ( is_404() ) {
			return COMPLETE99_PLATFORM_DIR . 'templates/not-found.php';
		}
		if ( self::is_consumer_transaction_request() ) {
			return COMPLETE99_PLATFORM_DIR . 'templates/commerce-shell.php';
		}
		if ( is_singular() && Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return COMPLETE99_PLATFORM_DIR . 'templates/public-shell.php';
		}
		return $template;
	}

	public static function body_classes( $classes ) {
		if ( is_404() ) {
			$lang      = self::not_found_language();
			$classes[] = 'complete99-public';
			$classes[] = 'c99-consumer-site';
			$classes[] = 'complete99-not-found';
			$classes[] = 'complete99-lang-' . $lang;
			$classes[] = 'en' === $lang ? 'complete99-ltr' : 'complete99-rtl';
			if ( self::is_live_dish_request() ) {
				$classes[] = 'complete99-live-dish';
			}
			if ( 'en' === $lang ) {
				$classes = array_values( array_diff( $classes, array( 'rtl' ) ) );
			}
			return array_values( array_unique( $classes ) );
		}
		if ( self::is_consumer_transaction_request() ) {
			$lang      = Complete99_Commerce::transaction_language();
			$classes[] = 'complete99-public';
			$classes[] = 'c99-consumer-site';
			$classes[] = 'c99-consumer-commerce';
			$classes[] = 'complete99-lang-' . $lang;
			$classes[] = 'en' === $lang ? 'complete99-ltr' : 'complete99-rtl';
			if ( 'en' === $lang ) {
				$classes = array_values( array_diff( $classes, array( 'rtl' ) ) );
			}
			return array_values( array_unique( $classes ) );
		}
		if ( self::is_live_dish_request() ) {
			$lang      = self::live_request_language();
			$classes[] = 'complete99-public';
			$classes[] = 'c99-consumer-site';
			$classes[] = 'complete99-live-dish';
			$classes[] = 'complete99-lang-' . $lang;
			$classes[] = 'en' === $lang ? 'complete99-ltr' : 'complete99-rtl';
			if ( 'en' === $lang ) {
				$classes = array_values( array_diff( $classes, array( 'rtl' ) ) );
			}
			return array_values( array_unique( $classes ) );
		}
		if ( is_singular() && Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			$lang      = Complete99_Content::language_for_post( get_queried_object_id() );
			if ( 'en' === $lang ) {
				$classes = array_values( array_diff( $classes, array( 'rtl' ) ) );
			}
			$classes[] = 'complete99-public';
			$classes[] = 'c99-consumer-site';
			$classes[] = 'complete99-lang-' . $lang;
			$classes[] = 'en' === $lang ? 'complete99-ltr' : 'complete99-rtl';
		}
		return $classes;
	}

	public static function enqueue() {
		if ( ! is_404()
			&& ! self::is_live_dish_request()
			&& ! self::is_consumer_transaction_request()
			&& ( ! is_singular() || ! Complete99_Content::is_complete99_post( get_queried_object_id() ) ) ) {
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
		wp_enqueue_script(
			'complete99-public',
			COMPLETE99_PLATFORM_URL . 'assets/js/public.js',
			array(),
			COMPLETE99_PLATFORM_VERSION,
			true
		);
	}

	public static function document_title( $title ) {
		if ( self::is_consumer_transaction_request() ) {
			$lang = Complete99_Commerce::transaction_language();
			$type = Complete99_Commerce::transaction_page_type();
			$labels = array(
				'cart'     => 'he' === $lang ? 'סל קניות' : 'Shopping cart',
				'checkout' => 'he' === $lang ? 'תשלום' : 'Checkout',
				'account'  => 'he' === $lang ? 'החשבון שלי' : 'My account',
			);
			return $labels[ $type ] . ' | Complete99';
		}
		if ( self::is_live_dish_request() ) {
			$dish = self::live_dish_by_slug( self::live_request_slug() );
			$lang = self::live_request_language();
			if ( $dish ) {
				$name = 'en' === $lang ? $dish['name_en'] : $dish['name_he'];
				return wp_strip_all_tags( $name ) . ' | Complete99';
			}
			return 'en' === $lang ? 'Dish not found | Complete99' : 'המנה לא נמצאה | קומפלט 99';
		}
		if ( is_404() ) {
			return 'en' === self::not_found_language() ? 'Page not found | Complete99' : 'העמוד לא נמצא | קומפלט 99';
		}
		if ( ! is_singular() || ! Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return $title;
		}
		$post = get_queried_object();
		return $post ? wp_strip_all_tags( $post->post_title ) . ' | Complete99' : $title;
	}

	public static function render_document_head() {
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );
		$title = trim( (string) wp_get_document_title() );
		if ( '' === $title ) {
			$title = 'Complete99';
		}

		ob_start();
		wp_head();
		$head = (string) ob_get_clean();
		$head = self::strip_document_head_duplicates( $head );

		echo '<title>' . esc_html( $title ) . '</title>' . "\n";
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
		echo (string) $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted wp_head output is preserved after structural de-duplication.
	}

	private static function strip_document_head_duplicates( $head ) {
		$output          = '';
		$cursor          = 0;
		$length          = strlen( $head );
		$protected_depth = 0;
		$protected_tags  = array( 'math', 'svg', 'template' );
		$raw_text_tags   = array( 'iframe', 'noembed', 'noframes', 'noscript', 'plaintext', 'script', 'style', 'textarea', 'xmp' );

		while ( $cursor < $length ) {
			$tag_start = strpos( $head, '<', $cursor );
			if ( false === $tag_start ) {
				$output .= substr( $head, $cursor );
				break;
			}

			$output .= substr( $head, $cursor, $tag_start - $cursor );

			if ( 0 === substr_compare( $head, '<!--', $tag_start, 4 ) ) {
				$comment_end = strpos( $head, '-->', $tag_start + 4 );
				if ( false === $comment_end ) {
					$output .= substr( $head, $tag_start );
					break;
				}
				$output .= substr( $head, $tag_start, $comment_end + 3 - $tag_start );
				$cursor  = $comment_end + 3;
				continue;
			}

			$tag_end = self::find_html_tag_end( $head, $tag_start );
			if ( false === $tag_end ) {
				$output .= substr( $head, $tag_start );
				break;
			}

			$tag_markup = substr( $head, $tag_start, $tag_end + 1 - $tag_start );
			if ( ! preg_match( '#^<(/?)([a-z][a-z0-9:-]*)(?=[\x20\t\r\n\f/>])#i', $tag_markup, $tag_match ) ) {
				$output .= $tag_markup;
				$cursor  = $tag_end + 1;
				continue;
			}

			$is_closer = '/' === $tag_match[1];
			$tag_name  = strtolower( $tag_match[2] );
			$is_protected_self_closer = ! $is_closer
				&& in_array( $tag_name, array( 'math', 'svg' ), true )
				&& 1 === preg_match( '#/\s*>$#', $tag_markup );

			if ( $is_closer && in_array( $tag_name, $protected_tags, true ) ) {
				$protected_depth = max( 0, $protected_depth - 1 );
				$output         .= $tag_markup;
				$cursor          = $tag_end + 1;
				continue;
			}

			if ( ! $is_closer && in_array( $tag_name, $raw_text_tags, true ) ) {
				if ( 'plaintext' === $tag_name ) {
					$output .= substr( $head, $tag_start );
					break;
				}

				$closing_end = self::find_html_raw_text_end( $head, $tag_name, $tag_end + 1 );
				if ( false !== $closing_end ) {
					$output     .= substr( $head, $tag_start, $closing_end - $tag_start );
					$cursor      = $closing_end;
					continue;
				}

				$output .= substr( $head, $tag_start );
				break;
			}

			if ( ! $is_closer && 0 === $protected_depth && 'title' === $tag_name ) {
				$closing_end = self::find_html_raw_text_end( $head, 'title', $tag_end + 1 );
				if ( false !== $closing_end ) {
					$cursor = $closing_end;
				} else {
					// An unclosed document title makes the remaining fragment title text, so discard that malformed tail.
					$cursor = $length;
				}
				continue;
			}

			if ( ! $is_closer
				&& 0 === $protected_depth
				&& 'meta' === $tag_name
				&& 'viewport' === strtolower( trim( (string) self::html_tag_attribute( $tag_markup, 'name' ) ) ) ) {
				$cursor = $tag_end + 1;
				continue;
			}

			$output .= $tag_markup;
			$cursor  = $tag_end + 1;
			if ( ! $is_closer && ! $is_protected_self_closer && in_array( $tag_name, $protected_tags, true ) ) {
				++$protected_depth;
			}
		}

		return $output;
	}

	private static function find_html_raw_text_end( $html, $tag_name, $offset ) {
		$needle = '</' . strtolower( $tag_name );
		$length = strlen( $html );

		while ( $offset < $length ) {
			$closing_start = stripos( $html, $needle, $offset );
			if ( false === $closing_start ) {
				return false;
			}

			$delimiter_position = $closing_start + strlen( $needle );
			if ( $delimiter_position >= $length
				|| 1 !== preg_match( '#[\x20\t\r\n\f/>]#', $html[ $delimiter_position ] ) ) {
				$offset = $delimiter_position;
				continue;
			}

			$closing_end = self::find_html_tag_end( $html, $closing_start );
			if ( false === $closing_end ) {
				return false;
			}

			return $closing_end + 1;
		}

		return false;
	}

	private static function find_html_tag_end( $html, $tag_start ) {
		$quote  = '';
		$length = strlen( $html );
		for ( $index = $tag_start + 1; $index < $length; ++$index ) {
			$character = $html[ $index ];
			if ( '' !== $quote ) {
				if ( $character === $quote ) {
					$quote = '';
				}
				continue;
			}
			if ( '"' === $character || "'" === $character ) {
				$quote = $character;
			} elseif ( '>' === $character ) {
				return $index;
			}
		}
		return false;
	}

	private static function html_tag_attribute( $tag_markup, $wanted_name ) {
		if ( ! preg_match( '#^</?[a-z][a-z0-9:-]*(?=[\x20\t\r\n\f/>])#i', $tag_markup, $tag_match ) ) {
			return null;
		}

		$cursor = strlen( $tag_match[0] );
		$length = strlen( $tag_markup );
		while ( $cursor < $length ) {
			while ( $cursor < $length && preg_match( '/\s/', $tag_markup[ $cursor ] ) ) {
				++$cursor;
			}
			if ( $cursor >= $length || '>' === $tag_markup[ $cursor ] ) {
				break;
			}
			if ( '/' === $tag_markup[ $cursor ] ) {
				++$cursor;
				continue;
			}

			$name_start = $cursor;
			while ( $cursor < $length && ! preg_match( '#[\s=/>]#', $tag_markup[ $cursor ] ) ) {
				++$cursor;
			}
			if ( $cursor === $name_start ) {
				++$cursor;
				continue;
			}
			$name = substr( $tag_markup, $name_start, $cursor - $name_start );

			while ( $cursor < $length && preg_match( '/\s/', $tag_markup[ $cursor ] ) ) {
				++$cursor;
			}
			$value = true;
			if ( $cursor < $length && '=' === $tag_markup[ $cursor ] ) {
				++$cursor;
				while ( $cursor < $length && preg_match( '/\s/', $tag_markup[ $cursor ] ) ) {
					++$cursor;
				}
				if ( $cursor < $length && ( '"' === $tag_markup[ $cursor ] || "'" === $tag_markup[ $cursor ] ) ) {
					$quote       = $tag_markup[ $cursor ];
					$value_start = ++$cursor;
					while ( $cursor < $length && $quote !== $tag_markup[ $cursor ] ) {
						++$cursor;
					}
					$value = substr( $tag_markup, $value_start, $cursor - $value_start );
					if ( $cursor < $length ) {
						++$cursor;
					}
				} else {
					$value_start = $cursor;
					while ( $cursor < $length && ! preg_match( '#[\s>]#', $tag_markup[ $cursor ] ) ) {
						++$cursor;
					}
					$value = substr( $tag_markup, $value_start, $cursor - $value_start );
				}
			}

			if ( 0 === strcasecmp( $name, $wanted_name ) ) {
				return is_string( $value )
					? html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' )
					: $value;
			}
		}

		return null;
	}

	public static function robots( $robots ) {
		if ( is_404() ) {
			unset( $robots['index'], $robots['nofollow'] );
			$robots['noindex'] = true;
			$robots['follow']  = true;
			return $robots;
		}
		if ( self::is_live_dish_request() ) {
			$dish         = self::live_dish_by_slug( self::live_request_slug() );
			$verification = $dish && isset( $dish['verification_state'] ) ? (string) $dish['verification_state'] : '';
			if ( ! in_array( $verification, array( 'verified', 'launch_ready' ), true ) ) {
				unset( $robots['index'] );
				$robots['noindex']  = true;
				$robots['nofollow'] = false;
			}
			return $robots;
		}
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
		if ( self::is_consumer_transaction_request() ) {
			$lang        = Complete99_Commerce::transaction_language();
			$type        = Complete99_Commerce::transaction_page_type();
			$canonical   = Complete99_Commerce::transaction_url( $type, $lang );
			$description = 'he' === $lang
				? 'סל, תשלום ופרטי הזמנה מאובטחים של המזווה של קומפלט 99.'
				: 'Secure cart, checkout and order details for the Complete99 pantry.';
			self::render_canonical_link( $canonical );
			echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
			return;
		}
		if ( self::is_live_dish_request() ) {
			$dish = self::live_dish_by_slug( self::live_request_slug() );
			if ( $dish ) {
				self::live_dish_head_metadata( $dish, self::live_request_language() );
			}
			return;
		}
		if ( ! is_singular() || ! Complete99_Content::is_complete99_post( get_queried_object_id() ) ) {
			return;
		}
		$post      = get_queried_object();
		$lang      = Complete99_Content::language_for_post( $post->ID );
		$key       = Complete99_Content::translation_group_for_post( $post->ID );
		$he_url     = Complete99_Content::route_url( $key, 'he' );
		$en_url     = Complete99_Content::route_url( $key, 'en' );
		$alternate = 'he' === $lang ? $en_url : $he_url;
		$canonical = get_permalink( $post );
		$image     = self::post_image_url( $post->ID );
		$brand_mark = COMPLETE99_PLATFORM_URL . 'assets/images/complete99-mark.svg';
		$description = wp_strip_all_tags( $post->post_excerpt );
		if ( 'store' === $key && Complete99_Commerce::is_ready() ) {
			$description = 'he' === $lang
				? 'מוצרי המזווה של קומפלט 99 עם מחיר, משקל, רכיבים, אלרגנים, מלאי ותנאי איסוף או משלוח.'
				: 'Complete99 pantry goods with price, weight, ingredients, allergens, stock and pickup or delivery terms.';
			$product_ids = Complete99_Commerce::storefront_product_ids();
			$product     = ! empty( $product_ids ) && function_exists( 'wc_get_product' ) ? wc_get_product( $product_ids[0] ) : false;
			if ( $product ) {
				$product_image = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
				$image         = $product_image ? $product_image : $image;
			}
		}

		echo '<link rel="icon" href="' . esc_url( $brand_mark ) . '" type="image/svg+xml" sizes="any" />' . "\n";
		self::render_canonical_link( $canonical );
		if ( $he_url ) {
			echo '<link rel="alternate" hreflang="' . esc_attr( 'he' ) . '" href="' . esc_url( $he_url ) . '" />' . "\n";
			echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $he_url ) . '" />' . "\n";
		}
		if ( $en_url ) {
			echo '<link rel="alternate" hreflang="' . esc_attr( 'en' ) . '" href="' . esc_url( $en_url ) . '" />' . "\n";
		}
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( 'he' === $lang ? 'he_IL' : 'en_US' ) . '" />' . "\n";
		echo '<meta property="og:locale:alternate" content="' . esc_attr( 'he' === $lang ? 'en_US' : 'he_IL' ) . '" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_strip_all_tags( $post->post_title ) ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
		echo '<meta name="twitter:card" content="' . esc_attr( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( wp_strip_all_tags( $post->post_title ) ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
		}

		$schema = self::schema_graph( $post, $lang, $alternate );
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	private static function is_live_dish_request() {
		return '' !== self::live_request_slug();
	}

	private static function is_consumer_transaction_request() {
		return class_exists( 'Complete99_Commerce' )
			&& Complete99_Commerce::is_transaction_page()
			&& ( Complete99_Commerce::is_ready()
				|| Complete99_Commerce::can_preview_commerce()
				|| Complete99_Commerce::can_access_customer_continuity() );
	}

	private static function render_canonical_link( $url ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
	}

	private static function live_request_slug() {
		return sanitize_title( (string) get_query_var( 'complete99_live_dish', '' ) );
	}

	private static function live_request_language() {
		$lang = sanitize_key( (string) get_query_var( 'complete99_live_lang', 'he' ) );
		return 'en' === $lang ? 'en' : 'he';
	}

	public static function not_found_language() {
		$lang = sanitize_key( (string) get_query_var( 'complete99_live_lang', '' ) );
		if ( in_array( $lang, array( 'he', 'en' ), true ) ) {
			return $lang;
		}

		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
		$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );
		$home_path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$relative     = is_string( $request_path ) ? trim( $request_path, '/' ) : '';
		$home_path    = is_string( $home_path ) ? trim( $home_path, '/' ) : '';

		if ( '' !== $home_path ) {
			if ( $relative === $home_path ) {
				$relative = '';
			} elseif ( 0 === strpos( $relative, $home_path . '/' ) ) {
				$relative = substr( $relative, strlen( $home_path ) + 1 );
			}
		}

		$segments = array_values( array_filter( explode( '/', $relative ), 'strlen' ) );
		$prefix   = isset( $segments[0] ) ? sanitize_key( rawurldecode( $segments[0] ) ) : '';
		return 'en' === $prefix ? 'en' : 'he';
	}

	private static function public_model_items() {
		$items = class_exists( 'Complete99_Consumer' )
			? Complete99_Consumer::menu_items()
			: Complete99_REST::public_indexable_items();
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

	public static function live_dish_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return array();
		}
		foreach ( self::public_model_items() as $item ) {
			if ( hash_equals( (string) $item['slug'], $slug ) ) {
				return $item;
			}
		}
		return array();
	}

	public static function live_dish_url( $slug, $lang ) {
		$prefix = 'en' === $lang ? 'en/' : '';
		return home_url( user_trailingslashit( $prefix . 'menu/' . sanitize_title( (string) $slug ) ) );
	}

	private static function live_image_url( $item ) {
		if ( class_exists( 'Complete99_Consumer' ) ) {
			return Complete99_Consumer::image_url( $item );
		}
		$asset = sanitize_file_name( isset( $item['image_asset'] ) ? (string) $item['image_asset'] : '' );
		if ( '' === $asset || 0 !== strpos( $asset, 'c99-' ) ) {
			return '';
		}
		$stem       = pathinfo( $asset, PATHINFO_FILENAME );
		$candidates = array_values( array_unique( array( $asset, $stem . '.webp', $stem . '.avif' ) ) );
		foreach ( $candidates as $candidate ) {
			if ( ! preg_match( '/\.(?:jpe?g|png|webp|avif)$/i', $candidate ) ) {
				continue;
			}
			$path = COMPLETE99_PLATFORM_DIR . 'assets/images/original/' . $candidate;
			if ( is_file( $path ) ) {
				return COMPLETE99_PLATFORM_URL . 'assets/images/original/' . rawurlencode( $candidate );
			}
		}
		return '';
	}

	public static function maybe_render_live_dish() {
		if ( ! self::is_live_dish_request() ) {
			return;
		}
		$dish = self::live_dish_by_slug( self::live_request_slug() );
		if ( ! $dish ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
		$complete99_live_dish = $dish;
		$complete99_live_lang = self::live_request_language();
		include COMPLETE99_PLATFORM_DIR . 'templates/live-dish.php';
		exit;
	}

	public static function live_dish_breadcrumb_items( $dish, $lang ) {
		$is_he = 'he' === $lang;
		$name  = $is_he ? (string) ( $dish['name_he'] ?? '' ) : (string) ( $dish['name_en'] ?? '' );
		return array(
			array(
				'label' => $is_he ? 'בית' : 'Home',
				'url'   => self::navigation_url( 'home', $lang ),
			),
			array(
				'label' => $is_he ? 'מנות' : 'Dishes',
				'url'   => self::navigation_url( 'dishes', $lang ),
			),
			array(
				'label' => wp_strip_all_tags( $name ),
				'url'   => self::live_dish_url( (string) ( $dish['slug'] ?? '' ), $lang ),
			),
		);
	}

	private static function is_verified_current_dish_record( $dish ) {
		if ( ! is_array( $dish )
			|| true !== ( $dish['published'] ?? null )
			|| ! in_array( sanitize_key( (string) ( $dish['verification_state'] ?? '' ) ), array( 'verified', 'launch_ready' ), true ) ) {
			return false;
		}
		$source = sanitize_key( (string) ( $dish['_complete99_source'] ?? '' ) );
		if ( '' !== $source && 'live' !== $source ) {
			return false;
		}
		return class_exists( 'Complete99_REST' )
			&& method_exists( 'Complete99_REST', 'is_public_item' )
			&& Complete99_REST::is_public_item( $dish );
	}

	private static function live_dish_head_metadata( $dish, $lang ) {
		$is_he       = 'he' === $lang;
		$name        = $is_he ? $dish['name_he'] : $dish['name_en'];
		$description = $is_he ? $dish['description_he'] : $dish['description_en'];
		$canonical   = self::live_dish_url( $dish['slug'], $lang );
		$he_url      = self::live_dish_url( $dish['slug'], 'he' );
		$en_url      = self::live_dish_url( $dish['slug'], 'en' );
		$image       = self::live_image_url( $dish );
		$brand_mark  = COMPLETE99_PLATFORM_URL . 'assets/images/complete99-mark.svg';

		echo '<link rel="icon" href="' . esc_url( $brand_mark ) . '" type="image/svg+xml" sizes="any" />' . "\n";
		self::render_canonical_link( $canonical );
		echo '<link rel="alternate" hreflang="he" href="' . esc_url( $he_url ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="en" href="' . esc_url( $en_url ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $he_url ) . '" />' . "\n";
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $description ) ) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( $is_he ? 'he_IL' : 'en_US' ) . '" />' . "\n";
		echo '<meta property="og:locale:alternate" content="' . esc_attr( $is_he ? 'en_US' : 'he_IL' ) . '" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_strip_all_tags( $name ) ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( $description ) ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
		echo '<meta name="twitter:card" content="' . esc_attr( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
		}

		$breadcrumb_items = array();
		foreach ( self::live_dish_breadcrumb_items( $dish, $lang ) as $position => $breadcrumb ) {
			$breadcrumb_items[] = array(
				'@type'    => 'ListItem',
				'position' => $position + 1,
				'name'     => $breadcrumb['label'],
				'item'     => $breadcrumb['url'],
			);
		}
		$updated_at = isset( $dish['updated_at'] ) ? strtotime( (string) $dish['updated_at'] ) : false;
		$web_page   = array(
			'@type'       => 'WebPage',
			'@id'         => $canonical . '#webpage',
			'url'         => $canonical,
			'name'        => wp_strip_all_tags( $name ),
			'description' => wp_strip_all_tags( $description ),
			'inLanguage'  => $lang,
			'breadcrumb'  => array( '@id' => $canonical . '#breadcrumb' ),
		);
		if ( $updated_at ) {
			$web_page['dateModified'] = gmdate( 'c', $updated_at );
		}
		$graph = array(
			self::food_business_schema( $lang ),
			$web_page,
			array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $canonical . '#breadcrumb',
				'itemListElement' => $breadcrumb_items,
			),
		);
		if ( self::is_verified_current_dish_record( $dish ) ) {
			$menu_item = array(
				'@type'       => 'MenuItem',
				'@id'         => $canonical . '#menu-item',
				'name'        => wp_strip_all_tags( $name ),
				'description' => wp_strip_all_tags( $description ),
				'url'         => $canonical,
				'inLanguage'  => $lang,
			);
			if ( $image ) {
				$menu_item['image'] = $image;
			}
			$graph[1]['mainEntity'] = array( '@id' => $canonical . '#menu-item' );
			$graph[]                = $menu_item;
		}
		$schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	private static function food_business_schema( $lang ) {
		return array(
			'@type'     => 'Restaurant',
			'@id'       => home_url( '/#organization' ),
			'name'      => 'Complete99',
			'url'       => home_url( '/' ),
			'telephone' => '+972-3-523-1810',
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => '99 Shlomo Ibn Gabirol',
				'addressLocality' => 'Tel Aviv',
				'addressCountry'  => 'IL',
			),
			'hasMenu'   => Complete99_Content::route_url( 'dishes', $lang ),
			'sameAs'    => array( Complete99_Commerce::order_url( $lang ) ),
		);
	}

	private static function schema_graph( $post, $lang, $alternate ) {
		$url     = get_permalink( $post );
		$org_id  = home_url( '/#organization' );
		$page_id = $url . '#webpage';
		$image   = self::post_image_url( $post->ID );
		$key     = Complete99_Content::translation_group_for_post( $post->ID );
		$page_description = wp_strip_all_tags( $post->post_excerpt );
		if ( 'store' === $key && Complete99_Commerce::is_ready() ) {
			$page_description = 'he' === $lang
				? 'מוצרי המזווה של קומפלט 99 עם מידע מלא והמשך מאובטח לסל ולתשלום.'
				: 'Complete99 pantry goods with full product information and a secure cart and checkout.';
		}
		$graph   = array(
			self::food_business_schema( $lang ),
			array(
				'@type'       => 'WebPage',
				'@id'         => $page_id,
				'url'         => $url,
				'name'        => wp_strip_all_tags( $post->post_title ),
				'description' => $page_description,
				'inLanguage'  => $lang,
				'isPartOf'     => array( '@id' => home_url( '/#website' ) ),
			),
		);

		if ( 'home' !== $key ) {
			$breadcrumb_items = array();
			foreach ( self::breadcrumb_items( $post, $lang ) as $position => $breadcrumb ) {
				$breadcrumb_items[] = array(
					'@type'    => 'ListItem',
					'position' => $position + 1,
					'name'     => $breadcrumb['label'],
					'item'     => $breadcrumb['url'],
				);
			}
			$graph[1]['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
			$graph[]                = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => $breadcrumb_items,
			);
		}

		$graph[0]['logo'] = COMPLETE99_PLATFORM_URL . 'assets/images/complete99-mark.svg';
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
		if ( 'store' === $key && Complete99_Commerce::is_ready() ) {
			foreach ( Complete99_Commerce::storefront_product_ids() as $product_id ) {
				$product_schema = self::store_product_schema( $product_id, $lang, $url );
				if ( $product_schema ) {
					$graph[] = $product_schema;
				}
			}
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}

	private static function store_product_schema( $product_id, $lang, $store_url ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		$product = wc_get_product( absint( $product_id ) );
		if ( ! $product ) {
			return null;
		}
		$is_he       = 'he' === $lang;
		$name        = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::NAME_HE : Complete99_Commerce::NAME_EN, true );
		$description = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::DESCRIPTION_HE : Complete99_Commerce::DESCRIPTION_EN, true );
		$ingredients = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::INGREDIENTS_HE : Complete99_Commerce::INGREDIENTS_EN, true );
		$allergens   = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::ALLERGENS_HE : Complete99_Commerce::ALLERGENS_EN, true );
		$storage     = (string) get_post_meta( $product_id, $is_he ? Complete99_Commerce::STORAGE_HE : Complete99_Commerce::STORAGE_EN, true );
		$image       = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
		$unit_codes  = array( 'kg' => 'KGM', 'g' => 'GRM', 'lbs' => 'LBR', 'oz' => 'ONZ' );
		$weight_unit = (string) get_option( 'woocommerce_weight_unit', 'kg' );
		if ( '' === trim( $name ) || '' === trim( $description ) || ! $image ) {
			return null;
		}
		return array(
			'@type'              => 'Product',
			'@id'                => $store_url . '#c99-product-' . absint( $product_id ),
			'name'               => $name,
			'description'        => $description,
			'inLanguage'         => $lang,
			'image'              => array( $image ),
			'sku'                => (string) $product->get_sku(),
			'weight'             => array(
				'@type'    => 'QuantitativeValue',
				'value'    => (float) $product->get_weight(),
				'unitCode' => $unit_codes[ $weight_unit ] ?? strtoupper( $weight_unit ),
			),
			'additionalProperty' => array(
				array( '@type' => 'PropertyValue', 'name' => $is_he ? 'רכיבים' : 'Ingredients', 'value' => $ingredients ),
				array( '@type' => 'PropertyValue', 'name' => $is_he ? 'אלרגנים' : 'Allergens', 'value' => $allergens ),
				array( '@type' => 'PropertyValue', 'name' => $is_he ? 'אחסון' : 'Storage', 'value' => $storage ),
			),
			'offers'             => array(
				'@type'         => 'Offer',
				'url'           => $store_url . '#c99-product-' . absint( $product_id ),
				'priceCurrency' => (string) get_woocommerce_currency(),
				'price'         => (string) $product->get_price(),
				'availability'  => 'https://schema.org/InStock',
				'itemCondition' => 'https://schema.org/NewCondition',
				'seller'        => array( '@id' => home_url( '/#organization' ) ),
			),
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

	private static function navigation_groups( $lang ) {
		$is_he = 'he' === $lang;
		return array(
			array(
				'key'      => 'services',
				'label'    => $is_he ? 'שירותים' : 'Services',
				'summary'  => $is_he ? 'מסלולי שירות למזון ארגוני שוטף' : 'Ongoing organisational foodservice pathways',
				'children' => array(
					array( 'institutional-catering', $is_he ? 'הסעדה מוסדית' : 'Institutional foodservice' ),
					array( 'employee-meals', $is_he ? 'ארוחות לעובדים' : 'Employee meals' ),
					array( 'dining-room-management', $is_he ? 'ניהול חדרי אוכל' : 'Dining-room management' ),
					array( 'central-kitchen-delivery', $is_he ? 'מטבח מרכזי והפצה' : 'Central kitchen & delivery' ),
				),
			),
			array(
				'key'      => 'industries',
				'label'    => $is_he ? 'למי זה מתאים' : 'Industries',
				'summary'  => $is_he ? 'התאמה לסביבת העבודה, לשעות ולקהל' : 'Designed around each workplace, schedule and audience',
				'children' => array(
					array( 'companies-offices', $is_he ? 'חברות ומשרדים' : 'Companies & offices' ),
					array( 'manufacturing-logistics', $is_he ? 'ייצור ולוגיסטיקה' : 'Manufacturing & logistics' ),
					array( 'proposal', $is_he ? 'בדיקת התאמה לארגון' : 'Organisational fit review' ),
				),
			),
			array(
				'key'      => 'dishes',
				'label'    => $is_he ? 'מנות וידע' : 'Food & knowledge',
				'summary'  => $is_he ? 'מנות, מרכיבים, מסורות ומדריכים' : 'Dishes, ingredients, traditions and practical guides',
				'children' => array(
					array( 'dishes', $is_he ? 'ספריית המנות' : 'Dish library' ),
					array( 'ingredients', $is_he ? 'מרכיבים' : 'Ingredients' ),
					array( 'traditions', $is_he ? 'מסורות קולינריות' : 'Culinary traditions' ),
					array( 'knowledge', $is_he ? 'מרכז הידע' : 'Knowledge centre' ),
				),
			),
			array(
				'key'      => 'platform',
				'label'    => $is_he ? 'המערכת' : 'Platform',
				'summary'  => $is_he ? 'עבודה יומית ברורה לצוות ולמנהלים' : 'Clear daily work for teams and managers',
				'children' => array(
					array( 'operations-command-center', $is_he ? 'מרכז שליטה תפעולי' : 'Operations command centre' ),
					array( 'opening-workflows', $is_he ? 'פתיחת יום ורשימות בקרה' : 'Opening workflows' ),
					array( 'inventory-procurement', $is_he ? 'מלאי ורכש' : 'Inventory & procurement' ),
					array( 'multi-location', $is_he ? 'ניהול רב־סניפי' : 'Multi-location management' ),
				),
			),
			array(
				'key'      => 'store',
				'label'    => $is_he ? 'חנות' : 'Store',
				'summary'  => '',
				'children' => array(),
			),
			array(
				'key'      => 'about',
				'label'    => $is_he ? 'אודות' : 'About',
				'summary'  => $is_he ? 'הגישה, התהליך והדרך להתחיל' : 'Our approach, process and how to begin',
				'children' => array(
					array( 'about', $is_he ? 'על קומפלט 99' : 'About Complete99' ),
					array( 'tender-pack', $is_he ? 'מידע למכרזים' : 'Tender information' ),
					array( 'contact', $is_he ? 'יצירת קשר' : 'Contact' ),
				),
			),
		);
	}

	private static function navigation_url( $key, $lang ) {
		$url = Complete99_Content::route_url( $key, $lang );
		if ( $url ) {
			return $url;
		}
		$prefix = 'en' === $lang ? 'en/' : '';
		return home_url( '/' . $prefix . sanitize_title( $key ) . '/' );
	}

	private static function navigation_group_is_current( $group, $key ) {
		if ( $group['key'] === $key ) {
			return true;
		}
		foreach ( $group['children'] as $child ) {
			if ( $child[0] === $key ) {
				return true;
			}
		}
		return false;
	}

	public static function render_header( $post_id, $lang, $live_slug = '' ) {
		if ( class_exists( 'Complete99_Consumer' ) ) {
			Complete99_Consumer::render_header( $post_id, $lang, $live_slug );
			return;
		}
		$is_he       = 'he' === $lang;
		$brand_home  = self::navigation_url( 'home', $lang );
		$current_key = Complete99_Content::translation_group_for_post( $post_id );
		$groups      = self::navigation_groups( $lang );
		?>
		<a class="c99-skip-link" href="#c99-main"><?php echo esc_html( $is_he ? 'דילוג לתוכן' : 'Skip to content' ); ?></a>
		<header class="c99-site-header">
			<div class="c99-container c99-header-inner">
				<a class="c99-brand" href="<?php echo esc_url( $brand_home ); ?>" aria-label="<?php echo esc_attr( $is_he ? 'קומפלט 99 - בית' : 'Complete99 - home' ); ?>">
					<span class="c99-brand-mark" aria-hidden="true"><span>9</span><span>9</span></span>
					<span class="c99-brand-copy"><strong><?php echo esc_html( $is_he ? 'קומפלט 99' : 'Complete99' ); ?></strong><small><?php echo esc_html( $is_he ? 'אוכל · תפעול · צמיחה' : 'Food · operations · growth' ); ?></small></span>
				</a>
				<button class="c99-menu-toggle" type="button" aria-expanded="false" aria-controls="c99-primary-nav">
					<span class="c99-menu-icon" aria-hidden="true"><i></i><i></i><i></i></span><span><?php echo esc_html( $is_he ? 'תפריט' : 'Menu' ); ?></span>
				</button>
				<nav id="c99-primary-nav" class="c99-primary-nav" aria-label="<?php echo esc_attr( $is_he ? 'ניווט ראשי' : 'Primary navigation' ); ?>">
					<div class="c99-nav-groups">
					<?php foreach ( $groups as $index => $group ) : ?>
						<?php
						$is_current = self::navigation_group_is_current( $group, $current_key );
						$panel_id   = 'c99-mega-panel-' . absint( $index );
						?>
						<div class="c99-nav-group<?php echo $is_current ? ' is-current' : ''; ?>">
							<a class="c99-nav-hub" href="<?php echo esc_url( self::navigation_url( $group['key'], $lang ) ); ?>"<?php echo $group['key'] === $current_key ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $group['label'] ); ?></a>
							<?php if ( $group['children'] ) : ?>
								<button class="c99-mega-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-haspopup="true" aria-label="<?php echo esc_attr( sprintf( $is_he ? 'פתיחת תפריט %s' : 'Open %s menu', $group['label'] ) ); ?>"><span aria-hidden="true">⌄</span></button>
								<div id="<?php echo esc_attr( $panel_id ); ?>" class="c99-mega-panel" hidden>
									<div class="c99-mega-intro">
										<span class="c99-mega-kicker"><?php echo esc_html( $group['label'] ); ?></span>
										<strong><?php echo esc_html( $group['summary'] ); ?></strong>
										<a href="<?php echo esc_url( self::navigation_url( $group['key'], $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל הנושאים' : 'View the full hub' ); ?><span aria-hidden="true"> ←</span></a>
									</div>
									<ul>
										<?php foreach ( $group['children'] as $child ) : ?>
											<li><a href="<?php echo esc_url( self::navigation_url( $child[0], $lang ) ); ?>"<?php echo $child[0] === $current_key ? ' aria-current="page"' : ''; ?>><span><?php echo esc_html( $child[1] ); ?></span><span aria-hidden="true">←</span></a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					</div>
					<div class="c99-nav-actions">
						<a class="c99-nav-dishes" href="<?php echo esc_url( self::navigation_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לספריית המנות' : 'Explore dishes' ); ?></a>
						<a class="c99-nav-cta" href="<?php echo esc_url( self::navigation_url( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בדיקת התאמה למוסד' : 'Institutional fit review' ); ?></a>
					</div>
				</nav>
				<?php
				if ( $live_slug ) {
					self::render_live_language_switch( $live_slug, $lang );
				} else {
					self::render_language_switch( $post_id, $lang );
				}
				?>
			</div>
		</header>
		<?php
	}

	private static function render_live_language_switch( $slug, $lang ) {
		$other    = 'he' === $lang ? 'en' : 'he';
		$label    = 'he' === $lang ? 'EN' : 'עברית';
		$language = 'he' === $other ? 'עברית' : 'English';
		echo '<a class="c99-language-switch" href="' . esc_url( self::live_dish_url( $slug, $other ) ) . '" hreflang="' . esc_attr( $other ) . '" lang="' . esc_attr( $other ) . '" aria-label="' . esc_attr( $language ) . '">' . esc_html( $label ) . '</a>';
	}

	private static function render_language_switch( $post_id, $lang ) {
		$key      = Complete99_Content::translation_group_for_post( $post_id );
		$other    = 'he' === $lang ? 'en' : 'he';
		$label    = 'he' === $lang ? 'EN' : 'עברית';
		$language = 'he' === $other ? 'עברית' : 'English';
		$url      = Complete99_Content::route_url( $key, $other );
		if ( $url ) {
			echo '<a class="c99-language-switch" href="' . esc_url( $url ) . '" hreflang="' . esc_attr( $other ) . '" lang="' . esc_attr( $other ) . '" aria-label="' . esc_attr( $language ) . '">' . esc_html( $label ) . '</a>';
		}
	}

	public static function render_current( $post ) {
		if ( class_exists( 'Complete99_Consumer' ) ) {
			Complete99_Consumer::render_current( $post );
			return;
		}
		$lang  = Complete99_Content::language_for_post( $post->ID );
		$key   = Complete99_Content::translation_group_for_post( $post->ID );
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
		<?php if ( self::is_hub_key( $key ) ) : ?>
			<?php self::render_hub_experience( $key, $lang ); ?>
		<?php endif; ?>
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
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'האוכל בחזית, מערכת אחת מאחוריו' : 'Food at the front, one system behind it' ); ?></p>
					<h1><?php echo esc_html( $post->post_title ); ?></h1>
					<p class="c99-hero-summary"><?php echo esc_html( $post->post_excerpt ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( Complete99_Content::route_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למנות ולאוכל' : 'Explore food and dishes' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( Complete99_Content::route_url( 'platform', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'איך העסק עובד' : 'How the business works' ); ?></a>
					</div>
					<ul class="c99-proof-strip" aria-label="<?php echo esc_attr( $is_he ? 'עקרונות עבודה' : 'Operating principles' ); ?>">
						<li><?php echo esc_html( $is_he ? 'עברית + English' : 'Hebrew + English' ); ?></li>
						<li><?php echo esc_html( $is_he ? 'רב־סניפי' : 'Multi-location' ); ?></li>
						<li><?php echo esc_html( $is_he ? 'תהליך אחד מקצה לקצה' : 'One end-to-end process' ); ?></li>
					</ul>
				</div>
				<figure class="c99-home-image">
					<picture>
						<source srcset="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/original/c99-food-house-spread-hero-2021-wp-v01.avif' ); ?>" type="image/avif" />
						<img src="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/original/c99-food-house-spread-hero-2021-wp-v01.webp' ); ?>" alt="<?php echo esc_attr( $is_he ? 'מבט מלמעלה על קובה סלק, קוסקוס, קציצות, סלט ומנות נוספות' : 'Overhead spread of beet kubeh, couscous, meatballs, salad and additional dishes' ); ?>" width="1400" height="788" decoding="async" fetchpriority="high" />
					</picture>
					<figcaption><?php echo esc_html( $is_he ? 'צילום אוכל מארכיון קומפלט 99' : 'Complete99 archive food photograph' ); ?></figcaption>
				</figure>
			</div>
		</section>
		<?php self::render_live_menu( $lang ); ?>
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
		<?php self::render_connected_table( $lang ); ?>
		<?php self::render_food_archive( $lang ); ?>
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

	private static function live_status_label( $availability, $lang ) {
		$is_he = 'he' === $lang;
		$labels = array(
			'available' => $is_he ? 'זמין' : 'Available',
			'low'       => $is_he ? 'זמינות מוגבלת' : 'Limited availability',
			'sold_out'  => $is_he ? 'אזל כרגע' : 'Currently sold out',
		);
		return isset( $labels[ $availability ] ) ? $labels[ $availability ] : '';
	}

	private static function live_price_label( $item ) {
		$price = isset( $item['public_price'] ) && is_numeric( $item['public_price'] ) ? (float) $item['public_price'] : 0;
		if ( 0 >= $price ) {
			return '';
		}
		$currency = strtoupper( isset( $item['currency'] ) ? (string) $item['currency'] : '' );
		$symbol   = 'ILS' === $currency ? '₪' : $currency;
		return trim( $symbol . number_format_i18n( $price, 2 ) );
	}

	private static function render_live_menu( $lang ) {
		$is_he = 'he' === $lang;
		$items = self::public_model_items();
		$model = get_option( 'complete99_public_read_model', array() );
		?>
		<section class="c99-live-menu" aria-labelledby="c99-live-menu-title">
			<div class="c99-container">
				<div class="c99-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מהמערכת אל האתר' : 'From the operating system to the site' ); ?></p>
						<h2 id="c99-live-menu-title"><?php echo esc_html( $is_he ? 'המנות שפורסמו ממקור התפריט המוסמך' : 'Dishes published from the authoritative menu source' ); ?></h2>
					</div>
					<a class="c99-text-link" href="<?php echo esc_url( self::navigation_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לספריית המנות ←' : 'Open the dish library →' ); ?></a>
				</div>
				<?php if ( $items ) : ?>
					<div class="c99-live-menu-grid">
						<?php foreach ( $items as $item ) : ?>
							<?php
							$name         = $is_he ? $item['name_he'] : $item['name_en'];
							$description  = $is_he ? $item['description_he'] : $item['description_en'];
							$category_key = $is_he ? 'category_he' : 'category_en';
							$tag_key      = $is_he ? 'tag_he' : 'tag_en';
							$image        = self::live_image_url( $item );
							$price        = self::live_price_label( $item );
							?>
							<a class="c99-live-menu-card" href="<?php echo esc_url( self::live_dish_url( $item['slug'], $lang ) ); ?>">
								<?php if ( $image ) : ?>
									<figure><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="720" height="520" loading="lazy" decoding="async" /></figure>
								<?php else : ?>
									<div class="c99-live-menu-placeholder" aria-hidden="true"><span>99</span></div>
								<?php endif; ?>
								<div class="c99-live-menu-card-copy">
									<div class="c99-live-menu-meta">
										<span><?php echo esc_html( isset( $item[ $category_key ] ) ? $item[ $category_key ] : '' ); ?></span>
										<span class="c99-menu-availability c99-menu-availability-<?php echo esc_attr( $item['availability'] ); ?>"><?php echo esc_html( self::live_status_label( $item['availability'], $lang ) ); ?></span>
									</div>
									<h3><?php echo esc_html( $name ); ?></h3>
									<p><?php echo esc_html( $description ); ?></p>
									<div class="c99-live-menu-card-footer">
										<span><?php echo esc_html( isset( $item[ $tag_key ] ) ? $item[ $tag_key ] : '' ); ?></span>
										<?php if ( $price ) : ?><strong><?php echo esc_html( $price ); ?></strong><?php endif; ?>
									</div>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="c99-live-menu-proof">
						<?php
						echo esc_html(
							sprintf(
								$is_he ? 'מקור: Complete99 OS · גרסת פרסום %s' : 'Source: Complete99 OS · publication version %s',
								isset( $model['version'] ) && '' !== (string) $model['version'] ? (string) $model['version'] : '-'
							)
						);
						?>
					</p>
				<?php else : ?>
					<div class="c99-live-menu-empty">
						<strong><?php echo esc_html( $is_he ? 'עדיין לא פורסמו מנות ממקור התפריט המוסמך.' : 'No dishes have yet been published from the authoritative menu source.' ); ?></strong>
						<p><?php echo esc_html( $is_he ? 'לכן איננו מציגים תפריט משוער או מחזירים תוכן ישן. אפשר להמשיך לספריית האוכל והידע.' : 'We therefore do not show an estimated menu or resurrect stale content. You can continue to the food and knowledge library.' ); ?></p>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::navigation_url( 'knowledge', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למרכז הידע' : 'Open the knowledge centre' ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	public static function render_live_dish_page( $dish, $lang ) {
		if ( class_exists( 'Complete99_Consumer' ) ) {
			Complete99_Consumer::render_live_dish_page( $dish, $lang );
			return;
		}
		$is_he       = 'he' === $lang;
		$name        = $is_he ? $dish['name_he'] : $dish['name_en'];
		$description = $is_he ? $dish['description_he'] : $dish['description_en'];
		$category    = $is_he ? ( isset( $dish['category_he'] ) ? $dish['category_he'] : '' ) : ( isset( $dish['category_en'] ) ? $dish['category_en'] : '' );
		$tag         = $is_he ? ( isset( $dish['tag_he'] ) ? $dish['tag_he'] : '' ) : ( isset( $dish['tag_en'] ) ? $dish['tag_en'] : '' );
		$image       = self::live_image_url( $dish );
		$price       = self::live_price_label( $dish );
		$hub_id      = Complete99_Content::find_translation_post_id( 'dishes', $lang, true );
		?>
		<?php self::render_header( $hub_id, $lang, $dish['slug'] ); ?>
		<main id="c99-main" tabindex="-1">
			<nav class="c99-breadcrumb c99-container" aria-label="<?php echo esc_attr( $is_he ? 'פירורי לחם' : 'Breadcrumb' ); ?>">
				<a href="<?php echo esc_url( self::navigation_url( 'home', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בית' : 'Home' ); ?></a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( self::navigation_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'מנות' : 'Dishes' ); ?></a>
				<span aria-hidden="true">/</span>
				<span aria-current="page"><?php echo esc_html( $name ); ?></span>
			</nav>
			<article class="c99-live-dish">
				<div class="c99-container c99-live-dish-grid">
					<div class="c99-live-dish-copy">
						<div class="c99-live-menu-meta">
							<span><?php echo esc_html( $category ); ?></span>
							<span class="c99-menu-availability c99-menu-availability-<?php echo esc_attr( $dish['availability'] ); ?>"><?php echo esc_html( self::live_status_label( $dish['availability'], $lang ) ); ?></span>
						</div>
						<h1><?php echo esc_html( $name ); ?></h1>
						<p class="c99-hero-summary"><?php echo esc_html( $description ); ?></p>
						<?php if ( $tag || $price ) : ?>
							<div class="c99-live-dish-facts">
								<?php if ( $tag ) : ?><span><?php echo esc_html( $tag ); ?></span><?php endif; ?>
								<?php if ( $price ) : ?><strong><?php echo esc_html( $price ); ?></strong><?php endif; ?>
							</div>
						<?php endif; ?>
						<div class="c99-hero-actions">
							<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::navigation_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'View all dishes' ); ?></a>
							<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::navigation_url( 'contact', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'שאלה על המנה' : 'Ask about this dish' ); ?></a>
						</div>
						<p class="c99-live-menu-proof"><?php echo esc_html( $is_he ? 'העמוד נוצר ממודל הפרסום הנוכחי של Complete99 OS; אין כאן טענת זמינות מעבר לסטטוס המוצג.' : 'This page is generated from the current Complete99 OS publication model; no availability claim is made beyond the status shown.' ); ?></p>
					</div>
					<?php if ( $image ) : ?>
						<figure class="c99-live-dish-image"><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="1000" height="760" fetchpriority="high" /></figure>
					<?php else : ?>
						<div class="c99-live-menu-placeholder c99-live-dish-image" aria-hidden="true"><span>99</span></div>
					<?php endif; ?>
				</div>
			</article>
			<?php self::render_connected_table( $lang ); ?>
		</main>
		<?php self::render_footer( $lang ); ?>
		<?php
	}

	public static function render_not_found_page( $lang ) {
		$lang = 'en' === sanitize_key( (string) $lang ) ? 'en' : 'he';
		if ( self::is_live_dish_request() ) {
			self::render_live_dish_not_found_page( $lang );
			return;
		}
		if ( class_exists( 'Complete99_Consumer' ) ) {
			Complete99_Consumer::render_site_not_found_page( $lang );
			return;
		}

		$is_he   = 'he' === $lang;
		$home_id = Complete99_Content::find_translation_post_id( 'home', $lang, true );
		?>
		<?php self::render_header( $home_id, $lang ); ?>
		<main id="c99-main" tabindex="-1">
			<section class="c99-page-hero">
				<div class="c99-container c99-page-hero-grid">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? '404 · העמוד לא נמצא' : '404 · Page not found' ); ?></p>
						<h1><?php echo esc_html( $is_he ? 'העמוד שחיפשתם לא נמצא' : 'The page you were looking for was not found' ); ?></h1>
						<p class="c99-hero-summary"><?php echo esc_html( $is_he ? 'הכתובת שביקשתם אינה זמינה. אפשר לחזור לעמוד הבית או לפתוח את תפריט המנות.' : 'The address you requested is unavailable. Return home or open the dish menu.' ); ?></p>
						<div class="c99-hero-actions">
							<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::navigation_url( 'home', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לעמוד הבית' : 'Return home' ); ?></a>
							<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::navigation_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לכל המנות' : 'View all dishes' ); ?></a>
						</div>
					</div>
					<div class="c99-hero-system c99-live-menu-placeholder" aria-hidden="true"><span>404</span></div>
				</div>
			</section>
		</main>
		<?php self::render_footer( $lang ); ?>
		<?php
	}

	public static function render_live_dish_not_found_page( $lang ) {
		if ( class_exists( 'Complete99_Consumer' ) ) {
			Complete99_Consumer::render_not_found_page( $lang );
			return;
		}
		$is_he  = 'he' === $lang;
		$hub_id = Complete99_Content::find_translation_post_id( 'dishes', $lang, true );
		?>
		<?php self::render_header( $hub_id, $lang ); ?>
		<main id="c99-main" tabindex="-1">
			<section class="c99-page-hero">
				<div class="c99-container c99-page-hero-grid">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? '404 · קישור למנה' : '404 · Dish link' ); ?></p>
						<h1><?php echo esc_html( $is_he ? 'המנה שחיפשתם לא נמצאה' : 'The dish you were looking for was not found' ); ?></h1>
						<p class="c99-hero-summary"><?php echo esc_html( $is_he ? 'לא קיימת כרגע מנה ציבורית בכתובת הזו. ייתכן שהקישור השתנה, או שהמנה עדיין לא אושרה לפרסום.' : 'There is currently no public dish at this address. The link may have changed, or the dish may not yet be approved for publication.' ); ?></p>
						<div class="c99-hero-actions">
							<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::navigation_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לספריית המנות' : 'Open the dish library' ); ?></a>
							<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::navigation_url( 'home', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לעמוד הבית' : 'Return home' ); ?></a>
						</div>
					</div>
					<div class="c99-hero-system c99-live-menu-placeholder" aria-hidden="true"><span>404</span></div>
				</div>
			</section>
		</main>
		<?php self::render_footer( $lang ); ?>
		<?php
	}

	private static function is_hub_key( $key ) {
		return in_array( $key, array( 'services', 'industries', 'platform', 'dishes', 'ingredients', 'traditions', 'knowledge', 'store' ), true );
	}

	private static function hub_experience_data( $key, $lang ) {
		$is_he = 'he' === $lang;
		$hubs  = array(
			'services' => array(
				'eyebrow' => $is_he ? 'שירות שנבנה סביב האתר' : 'Service designed around the site',
				'title'    => $is_he ? 'מהתכנון ועד יום השירות' : 'From service design to the daily meal',
				'summary'  => $is_he ? 'כל מסלול מתחיל בקהל, בשעות, בתשתית ובחלוקת האחריות. משם מחברים תפריט, ייצור, הגשה ושיפור שוטף.' : 'Each pathway begins with audience, hours, infrastructure and responsibilities, then connects menu, production, service and ongoing improvement.',
				'cards'    => array(
					array( 'institutional-catering', '01', $is_he ? 'הסעדה מוסדית' : 'Institutional foodservice', $is_he ? 'מסגרת שירות, אחריות ומדדים לארגון.' : 'Service scope, accountability and measures for an organisation.' ),
					array( 'employee-meals', '02', $is_he ? 'ארוחות לעובדים' : 'Employee meals', $is_he ? 'חוויה יום־יומית שמחברת קהל, תפריט ותקציב.' : 'A daily experience connecting audience, menu and budget.' ),
					array( 'dining-room-management', '03', $is_he ? 'ניהול חדרי אוכל' : 'Dining-room management', $is_he ? 'פתיחה, קבלה, הגשה, חריגים וסגירה.' : 'Opening, receiving, service, exceptions and close.' ),
					array( 'onsite-kitchen-operations', '04', $is_he ? 'מטבח באתר הלקוח' : 'On-site kitchen operations', $is_he ? 'מודל עבודה שמתאים לתשתית ולזרימת האתר.' : 'An operating model matched to site infrastructure and flow.' ),
					array( 'central-kitchen-delivery', '05', $is_he ? 'מטבח מרכזי והפצה' : 'Central kitchen & delivery', $is_he ? 'חיבור בין ייצור, אריזה, מסירה וקבלה.' : 'Connecting production, packing, dispatch and receipt.' ),
					array( 'menu-nutrition-planning', '06', $is_he ? 'תפריט ומידע תזונתי' : 'Menu & nutrition information', $is_he ? 'תכנון קולינרי ברור לצד בדיקה מקצועית נדרשת.' : 'Clear culinary planning alongside the qualified review required.' ),
				),
			),
			'industries' => array(
				'eyebrow' => $is_he ? 'הקשר לפני פתרון' : 'Context before solution',
				'title'    => $is_he ? 'סביבת העבודה משנה את כללי השירות' : 'The workplace changes how foodservice should work',
				'summary'  => $is_he ? 'משמרות, שעות שיא, נקודות שירות, תשתיות וקהל יוצרים צרכים שונים. המסלול מתחיל בהיכרות עם המציאות באתר.' : 'Shifts, peaks, service points, infrastructure and audience create distinct needs. The pathway starts with the reality on site.',
				'cards'    => array(
					array( 'companies-offices', '01', $is_he ? 'חברות ומשרדים' : 'Companies & offices', $is_he ? 'שירות שמתחבר לחוויית העובד וליום העבודה.' : 'Foodservice aligned with employee experience and the working day.' ),
					array( 'manufacturing-logistics', '02', $is_he ? 'ייצור ולוגיסטיקה' : 'Manufacturing & logistics', $is_he ? 'תכנון למשמרות, עומסים ואזורי שירות שונים.' : 'Planning for shifts, peak demand and multiple service zones.' ),
					array( 'proposal', '03', $is_he ? 'מיפוי התאמה לארגון' : 'Organisational fit mapping', $is_he ? 'שיחה ממוקדת על קהל, אתר, תשתית ודרישות.' : 'A focused conversation about audience, site, infrastructure and requirements.' ),
				),
			),
			'platform' => array(
				'eyebrow' => $is_he ? 'פחות חיפוש, יותר פעולה' : 'Less searching, clearer action',
				'title'    => $is_he ? 'מערכת עבודה שמחברת תפקיד, סניף והמשך פעולה' : 'A work system connecting role, location and next action',
				'summary'  => $is_he ? 'המערכת מלווה את התקשרות המזון ומארגנת את הפעולות הקצרות של היום סביב אחריות, זמן ומידע ברור.' : 'The platform supports the foodservice engagement and organises short daily actions around ownership, timing and clear information.',
				'cards'    => array(
					array( 'operations-command-center', '01', $is_he ? 'מרכז שליטה' : 'Command centre', $is_he ? 'תמונה אחת של משימות, חריגים וסניפים.' : 'One view of actions, exceptions and locations.' ),
					array( 'opening-workflows', '02', $is_he ? 'פתיחת יום' : 'Opening workflows', $is_he ? 'רשימות קצרות לפי תפקיד וסניף.' : 'Short role- and location-aware checklists.' ),
					array( 'recipes-bom-food-cost', '03', $is_he ? 'מתכונים ועלויות' : 'Recipes & food cost', $is_he ? 'גרסאות, תשואה, מרכיבים ועלות מנה.' : 'Versions, yield, ingredients and dish cost.' ),
					array( 'inventory-procurement', '04', $is_he ? 'מלאי ורכש' : 'Inventory & procurement', $is_he ? 'מקבלה ומחסור ועד הזמנה וטיפול בפער.' : 'From receiving and shortage to ordering and discrepancy handling.' ),
					array( 'multi-location', '05', $is_he ? 'ריבוי סניפים' : 'Multi-location', $is_he ? 'סטנדרט מרכזי עם התאמות מקומיות מבוקרות.' : 'Central standards with controlled local adaptation.' ),
					array( 'marketing-campaigns', '06', $is_he ? 'מותג וקמפיינים' : 'Brand & campaigns', $is_he ? 'בריף, נכסים, תקשורת ומדידה באותו הקשר.' : 'Brief, assets, communication and measurement in one context.' ),
				),
			),
			'dishes' => array(
				'eyebrow' => $is_he ? 'מנה היא יותר מרשימת מרכיבים' : 'A dish is more than an ingredient list',
				'title'    => $is_he ? 'ספריית אוכל שמחברת טעם, מקור ועבודה במטבח' : 'A food library connecting flavour, origin and kitchen practice',
				'summary'  => $is_he ? 'כל מנה מיועדת לקבל מקום מסודר לסיפור, למרכיבים, לשיטות הכנה, למסורות ולמידע שימושי - רק כשהחומר שלם ואחראי.' : 'Each dish is designed to have a structured place for its story, ingredients, preparation, traditions and useful information once the material is complete and responsible.',
				'cards'    => array(
					array( 'ingredients', '01', $is_he ? 'מרכיבים' : 'Ingredients', $is_he ? 'מה נכנס למנה, מה תפקידו ואיך עובדים איתו.' : 'What goes into a dish, why it is there and how it is handled.' ),
					array( 'traditions', '02', $is_he ? 'מסורות קולינריות' : 'Culinary traditions', $is_he ? 'הקשרים משפחתיים, אזוריים ויהודיים בלי לקצר את הסיפור.' : 'Family, regional and Jewish contexts without flattening the story.' ),
					array( 'knowledge', '03', $is_he ? 'מרכז הידע' : 'Knowledge centre', $is_he ? 'שיטות, מדריכים ושאלות שימושיות סביב האוכל.' : 'Methods, guides and practical questions around food.' ),
					array( 'menu-nutrition-planning', '04', $is_he ? 'תכנון תפריט' : 'Menu planning', $is_he ? 'חיבור בין קהל, עונה, שירות ומידע מקצועי.' : 'Connecting audience, season, service and qualified information.' ),
				),
			),
			'ingredients' => array(
				'eyebrow' => $is_he ? 'להכיר את חומרי הגלם' : 'Know the ingredients',
				'title'    => $is_he ? 'מרכיבים, שימושים והקשרים קולינריים' : 'Ingredients, uses and culinary context',
				'summary'  => $is_he ? 'המרכז מארגן מידע על חומרי גלם לפי שימוש במנה, מסורת, עונה וטכניקת הכנה, בשפה ברורה לקוראים ולצוותים.' : 'The centre organises ingredient information by dish use, tradition, season and preparation method in clear language for readers and teams.',
				'cards'    => array(
					array( 'dishes', '01', $is_he ? 'מהמרכיב אל המנה' : 'From ingredient to dish', $is_he ? 'לראות איך חומרי גלם מתחברים למנות שלמות.' : 'See how ingredients come together in complete dishes.' ),
					array( 'traditions', '02', $is_he ? 'מרכיב בתוך מסורת' : 'Ingredients in tradition', $is_he ? 'להבין הקשרים אזוריים ומשפחתיים.' : 'Understand regional and family contexts.' ),
					array( 'knowledge', '03', $is_he ? 'שיטות עבודה' : 'Methods & guides', $is_he ? 'אחסון, הכנה, טכניקה ושאלות נפוצות.' : 'Storage, preparation, technique and common questions.' ),
				),
			),
			'traditions' => array(
				'eyebrow' => $is_he ? 'אוכל נושא זיכרון' : 'Food carries memory',
				'title'    => $is_he ? 'מסורות קולינריות דרך אנשים, מקומות ומנות' : 'Culinary traditions through people, places and dishes',
				'summary'  => $is_he ? 'המרכז נועד לתת הקשר למסורות יהודיות, משפחתיות ואזוריות, להציג הבדלים בכבוד ולחבר אותן לעבודה קולינרית עכשווית.' : 'The centre gives context to Jewish, family and regional traditions, respects their differences and connects them to contemporary culinary practice.',
				'cards'    => array(
					array( 'dishes', '01', $is_he ? 'מנות וסיפורים' : 'Dishes & stories', $is_he ? 'מנות שמובילות אל מקורות, וריאציות וזיכרונות.' : 'Dishes leading to origins, variations and memories.' ),
					array( 'ingredients', '02', $is_he ? 'חומרי גלם' : 'Ingredients', $is_he ? 'מרכיבים שחוזרים בין קהילות ומקבלים משמעות אחרת.' : 'Ingredients shared across communities and interpreted differently.' ),
					array( 'knowledge', '03', $is_he ? 'מדריכים והקשרים' : 'Guides & context', $is_he ? 'קריאה מעמיקה יותר סביב טכניקות ומנהגים.' : 'Deeper reading around methods and customs.' ),
				),
			),
			'knowledge' => array(
				'eyebrow' => $is_he ? 'ידע שאפשר לנווט בו' : 'Knowledge designed to be explored',
				'title'    => $is_he ? 'מרכז ידע לאוכל, שירות ותפעול' : 'A knowledge centre for food, service and operations',
				'summary'  => $is_he ? 'המרכז מחבר מדריכי עומק עם ספריות מנות, מרכיבים ומסורות, ומוביל כל קורא למסלול ברור במקום לערבב כוונות שונות בעמוד אחד.' : 'The centre connects in-depth guides with dish, ingredient and tradition libraries, giving each reader a clear path instead of mixing different needs on one page.',
				'cards'    => array(
					array( 'dishes', '01', $is_he ? 'ספריית מנות' : 'Dish library', $is_he ? 'הסיפור, המרכיבים והעשייה של כל מנה.' : 'The story, ingredients and practice behind each dish.' ),
					array( 'ingredients', '02', $is_he ? 'ספריית מרכיבים' : 'Ingredient library', $is_he ? 'חומרי גלם לפי שימוש והקשר.' : 'Ingredients organised by use and context.' ),
					array( 'traditions', '03', $is_he ? 'מסורות קולינריות' : 'Culinary traditions', $is_he ? 'אוכל דרך משפחות, קהילות ומקומות.' : 'Food through families, communities and places.' ),
					array( 'tender-pack', '04', $is_he ? 'מידע למקבלי החלטות' : 'Decision-maker information', $is_he ? 'מבנה שירות, שאלות ומסמכים לתהליך מסודר.' : 'Service structure, questions and documents for a clear process.' ),
				),
			),
			'store' => array(
				'eyebrow' => $is_he ? 'קטלוג בתכנון אחראי' : 'A catalogue being planned responsibly',
				'title'    => $is_he ? 'ציוד, כלי עבודה ומוצרי מזווה - כשהמסחר יהיה מוכן' : 'Equipment, working tools and pantry goods - when commerce is ready',
				'summary'  => $is_he ? 'החנות נבנית סביב מוצרים שימושיים למטבח ולשירות. מכירה, תשלום ומשלוח יוצגו רק לאחר השלמת פרטי הסוחר, המחירים, המלאי, האספקה וההחזרות.' : 'The store is being shaped around useful kitchen and service products. Sales, payment and delivery will appear only after merchant, pricing, stock, fulfilment and returns details are complete.',
				'cards'    => array(
					array( '', '01', $is_he ? 'ציוד וכלי מטבח' : 'Kitchen equipment & tools', $is_he ? 'כלים לעבודה יום־יומית, הכנה, הגשה וארגון.' : 'Tools for daily work, preparation, service and organisation.' ),
					array( '', '02', $is_he ? 'מזווה ושמנים' : 'Pantry & oils', $is_he ? 'מוצרים עם מידע ברור על שימוש, מקור ואחסון.' : 'Goods with clear information about use, origin and storage.' ),
					array( '', '03', $is_he ? 'כלים לתפעול' : 'Operating essentials', $is_he ? 'אביזרים שתומכים בסדר, סימון ותהליכי שירות.' : 'Accessories supporting order, labelling and service routines.' ),
				),
			),
		);
		return isset( $hubs[ $key ] ) ? $hubs[ $key ] : array();
	}

	private static function render_hub_experience( $key, $lang ) {
		$data = self::hub_experience_data( $key, $lang );
		if ( ! $data ) {
			return;
		}
		$is_he = 'he' === $lang;
		?>
		<section class="c99-hub-overview" aria-labelledby="c99-hub-overview-title">
			<div class="c99-container">
				<div class="c99-hub-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $data['eyebrow'] ); ?></p>
						<h2 id="c99-hub-overview-title"><?php echo esc_html( $data['title'] ); ?></h2>
					</div>
					<p><?php echo esc_html( $data['summary'] ); ?></p>
				</div>
				<div class="c99-hub-card-grid">
					<?php foreach ( $data['cards'] as $card ) : ?>
						<?php if ( $card[0] ) : ?>
							<a class="c99-hub-card" href="<?php echo esc_url( self::navigation_url( $card[0], $lang ) ); ?>">
						<?php else : ?>
							<article class="c99-hub-card c99-hub-card-static">
						<?php endif; ?>
								<span class="c99-hub-card-number" aria-hidden="true"><?php echo esc_html( $card[1] ); ?></span>
								<h3><?php echo esc_html( $card[2] ); ?></h3>
								<p><?php echo esc_html( $card[3] ); ?></p>
								<?php if ( $card[0] ) : ?><span class="c99-hub-card-action"><?php echo esc_html( $is_he ? 'לפרטים' : 'Explore' ); ?><span aria-hidden="true"> ←</span></span><?php endif; ?>
						<?php if ( $card[0] ) : ?>
							</a>
						<?php else : ?>
							</article>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		if ( in_array( $key, array( 'dishes', 'ingredients', 'traditions', 'knowledge' ), true ) ) {
			self::render_connected_table( $lang );
		}
		if ( 'dishes' === $key ) {
			self::render_live_menu( $lang );
			self::render_food_archive( $lang );
		}
	}

	private static function render_connected_table( $lang ) {
		$is_he = 'he' === $lang;
		?>
		<section class="c99-connected-table" aria-labelledby="c99-connected-table-title">
			<div class="c99-container c99-connected-table-frame">
				<picture class="c99-connected-table-art">
					<source srcset="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/complete99-connected-table-editorial-v1.avif' ); ?>" type="image/avif" />
					<img src="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/complete99-connected-table-editorial-v1.webp' ); ?>" width="1536" height="1024" loading="lazy" decoding="async" alt="<?php echo esc_attr( $is_he ? 'איור מערכתי של שולחן אוכל, מרכיבים, רשימת משימות וחיבור בין אתרים' : 'Editorial illustration of a shared food table, ingredients, an action list and connected locations' ); ?>" />
				</picture>
				<div class="c99-connected-table-copy">
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'שולחן אחד, מערכת הקשרים שלמה' : 'One table, a connected system' ); ?></p>
					<h2 id="c99-connected-table-title"><?php echo esc_html( $is_he ? 'מחברים אוכל, ידע ותפעול בלי לאבד את הסיפור' : 'Connect food, knowledge and operations without losing the story' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'מנות ומרכיבים מקבלים הקשר; צוותים וסניפים מקבלים דרך עבודה; ומקבלי החלטות מקבלים מסלול ברור מהשאלה ועד הפעולה.' : 'Dishes and ingredients gain context, teams and locations gain a way of working, and decision-makers gain a clear path from question to action.' ); ?></p>
					<div class="c99-hero-actions">
						<a class="c99-button c99-button-primary" href="<?php echo esc_url( self::navigation_url( 'knowledge', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'כניסה למרכז הידע' : 'Enter the knowledge centre' ); ?></a>
						<a class="c99-button c99-button-secondary" href="<?php echo esc_url( self::navigation_url( 'platform', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'היכרות עם המערכת' : 'Explore the platform' ); ?></a>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	private static function render_food_archive( $lang ) {
		$is_he = 'he' === $lang;
		$images = array(
			array(
				'c99-food-sabich-pita-gallery-2021-wp-v01',
				1000,
				700,
				$is_he ? 'פיתה עם חציל, ביצה, ירקות ורטבים' : 'Pita filled with aubergine, egg, vegetables and sauces',
			),
			array(
				'c99-food-kubeh-beet-soup-gallery-2021-wp-v01',
				1000,
				700,
				$is_he ? 'קובה במרק סלק אדום' : 'Kubeh dumplings in red beet soup',
			),
			array(
				'c99-food-couscous-beef-gallery-2021-wp-v01',
				1000,
				700,
				$is_he ? 'קוסקוס עם ירקות ובשר' : 'Couscous with vegetables and beef',
			),
			array(
				'c99-food-shakshuka-plate-gallery-2021-wp-v01',
				1000,
				700,
				$is_he ? 'שקשוקה עגבניות וביצים בצלחת' : 'Tomato and egg shakshuka served on a plate',
			),
		);
		?>
		<section class="c99-food-archive" aria-labelledby="c99-food-archive-title">
			<div class="c99-container">
				<div class="c99-section-heading">
					<div>
						<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מארכיון האוכל של קומפלט 99' : 'From the Complete99 food archive' ); ?></p>
						<h2 id="c99-food-archive-title"><?php echo esc_html( $is_he ? 'אוכל שנראה כמו אוכל, עם מקום לסיפור שמאחוריו' : 'Food shown as food, with room for the story behind it' ); ?></h2>
					</div>
					<p class="c99-food-archive-note"><?php echo esc_html( $is_he ? 'הצילומים מציגים נושאים קולינריים מארכיון המותג ואינם מציגים תפריט זמין או הצעת מכירה.' : 'These archive photographs illustrate culinary subjects; they do not represent a currently available menu or sales offer.' ); ?></p>
				</div>
				<div class="c99-food-mosaic">
					<?php foreach ( $images as $index => $archive_image ) : ?>
						<figure class="<?php echo 0 === $index ? 'c99-food-mosaic-featured' : ''; ?>">
							<picture>
								<source srcset="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/original/' . $archive_image[0] . '.avif' ); ?>" type="image/avif" />
								<img src="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/original/' . $archive_image[0] . '.webp' ); ?>" width="<?php echo esc_attr( $archive_image[1] ); ?>" height="<?php echo esc_attr( $archive_image[2] ); ?>" loading="lazy" decoding="async" alt="<?php echo esc_attr( $archive_image[3] ); ?>" />
							</picture>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}

	private static function render_system_preview( $lang ) {
		$is_he = 'he' === $lang;
		?>
		<div class="c99-system-top"><span class="c99-preview-dot" aria-hidden="true">99</span><strong><?php echo esc_html( $is_he ? 'מבנה מייצג של לוח היום' : 'Representative Today layout' ); ?></strong><span><?php echo esc_html( $is_he ? 'תצוגת יכולות' : 'Capability preview' ); ?></span></div>
		<div class="c99-kpi-row"><div><small><?php echo esc_html( $is_he ? 'פתיחת היום' : 'Opening' ); ?></small><strong>✓</strong></div><div><small><?php echo esc_html( $is_he ? 'משימות צוות' : 'Team tasks' ); ?></small><strong>↗</strong></div><div><small><?php echo esc_html( $is_he ? 'חריגים לטיפול' : 'Exceptions' ); ?></small><strong>!</strong></div></div>
		<div class="c99-task-list">
			<div><span class="c99-task-icon">✓</span><p><strong><?php echo esc_html( $is_he ? 'פתיחת מטבח' : 'Kitchen opening' ); ?></strong><small><?php echo esc_html( $is_he ? 'משימות לפי תפקיד' : 'Role-based actions' ); ?></small></p></div>
			<div><span class="c99-task-icon c99-task-warn">!</span><p><strong><?php echo esc_html( $is_he ? 'מחסור בפריט' : 'Item shortage' ); ?></strong><small><?php echo esc_html( $is_he ? 'בעלים והמשך פעולה' : 'Owner and next action' ); ?></small></p></div>
			<div><span class="c99-task-icon">↗</span><p><strong><?php echo esc_html( $is_he ? 'מהלך שיווקי' : 'Marketing activity' ); ?></strong><small><?php echo esc_html( $is_he ? 'בריף, בקרה ומדידה' : 'Brief, review and measurement' ); ?></small></p></div>
		</div>
		<p class="c99-preview-disclaimer"><?php echo esc_html( $is_he ? 'זהו מבנה המחשה בלבד. לא מוצגים נתוני אמת של סניפים, ספקים, מצלמות או קמפיינים.' : 'Illustrative layout only. No live location, supplier, camera or campaign data is shown.' ); ?></p>
		<?php
	}

	private static function breadcrumb_items( $post, $lang ) {
		$items = array();
		if ( method_exists( 'Complete99_Content', 'breadcrumb_trail' ) ) {
			$trail = Complete99_Content::breadcrumb_trail( $post->ID );
			if ( is_array( $trail ) ) {
				foreach ( $trail as $item ) {
					if ( empty( $item['label'] ) ) {
						continue;
					}
					$items[] = array(
						'label'   => wp_strip_all_tags( (string) $item['label'] ),
						'url'     => ! empty( $item['url'] ) ? (string) $item['url'] : get_permalink( $post ),
						'current' => ! empty( $item['current'] ),
					);
				}
			}
		}
		if ( count( $items ) >= 2 ) {
			return $items;
		}
		return array(
			array(
				'label'   => 'he' === $lang ? 'בית' : 'Home',
				'url'     => self::navigation_url( 'home', $lang ),
				'current' => false,
			),
			array(
				'label'   => wp_strip_all_tags( $post->post_title ),
				'url'     => get_permalink( $post ),
				'current' => true,
			),
		);
	}

	private static function render_breadcrumb( $post, $lang ) {
		$is_he = 'he' === $lang;
		$items = self::breadcrumb_items( $post, $lang );
		?>
		<nav class="c99-breadcrumb c99-container" aria-label="<?php echo esc_attr( $is_he ? 'פירורי לחם' : 'Breadcrumb' ); ?>">
			<ol>
				<?php foreach ( $items as $index => $item ) : ?>
					<li>
						<?php if ( $item['current'] ) : ?>
							<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
						<?php endif; ?>
						<?php if ( $index < count( $items ) - 1 ) : ?><span class="c99-breadcrumb-separator" aria-hidden="true">›</span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
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
		if ( class_exists( 'Complete99_Consumer' ) ) {
			Complete99_Consumer::render_footer( $lang );
			return;
		}
		$is_he = 'he' === $lang;
		$clusters = array(
			array(
				$is_he ? 'שירותים' : 'Services',
				array(
					array( 'services', $is_he ? 'כל השירותים' : 'All services' ),
					array( 'institutional-catering', $is_he ? 'הסעדה מוסדית' : 'Institutional foodservice' ),
					array( 'employee-meals', $is_he ? 'ארוחות לעובדים' : 'Employee meals' ),
					array( 'dining-room-management', $is_he ? 'ניהול חדרי אוכל' : 'Dining-room management' ),
				),
			),
			array(
				$is_he ? 'מנות וידע' : 'Dishes & knowledge',
				array(
					array( 'dishes', $is_he ? 'ספריית המנות' : 'Dish library' ),
					array( 'ingredients', $is_he ? 'מרכיבים' : 'Ingredients' ),
					array( 'traditions', $is_he ? 'מסורות קולינריות' : 'Culinary traditions' ),
					array( 'knowledge', $is_he ? 'מרכז הידע' : 'Knowledge centre' ),
				),
			),
			array(
				$is_he ? 'המערכת' : 'Platform',
				array(
					array( 'platform', $is_he ? 'כל יכולות המערכת' : 'Platform overview' ),
					array( 'operations-command-center', $is_he ? 'מרכז שליטה' : 'Command centre' ),
					array( 'opening-workflows', $is_he ? 'פתיחת יום' : 'Opening workflows' ),
					array( 'inventory-procurement', $is_he ? 'מלאי ורכש' : 'Inventory & procurement' ),
				),
			),
			array(
				$is_he ? 'חנות' : 'Store',
				array(
					array( 'store', $is_he ? 'תכנון הקטלוג' : 'Catalogue planning' ),
				),
			),
			array(
				$is_he ? 'החברה' : 'Company',
				array(
					array( 'about', $is_he ? 'אודות' : 'About' ),
					array( 'proposal', $is_he ? 'בדיקת התאמה' : 'Fit review' ),
					array( 'tender-pack', $is_he ? 'מידע למכרזים' : 'Tender information' ),
					array( 'contact', $is_he ? 'יצירת קשר' : 'Contact' ),
				),
			),
			array(
				$is_he ? 'מידע משפטי ונגישות' : 'Legal & accessibility',
				array(
					array( 'privacy', $is_he ? 'פרטיות' : 'Privacy' ),
					array( 'terms', $is_he ? 'תנאי שימוש' : 'Terms of use' ),
					array( 'accessibility', $is_he ? 'נגישות' : 'Accessibility' ),
				),
			),
		);
		?>
		<footer class="c99-site-footer">
			<div class="c99-container c99-footer-callout">
				<div>
					<p class="c99-eyebrow"><?php echo esc_html( $is_he ? 'מתכננים שירות מזון לארגון?' : 'Planning organisational foodservice?' ); ?></p>
					<h2><?php echo esc_html( $is_he ? 'מתחילים מהאתר, מהאנשים ומהיום שצריך לעבוד' : 'Start with the site, the people and the day that needs to work' ); ?></h2>
				</div>
				<div class="c99-footer-callout-actions">
					<a class="c99-button c99-button-light" href="<?php echo esc_url( self::navigation_url( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'בדיקת התאמה למוסד' : 'Institutional fit review' ); ?></a>
					<a class="c99-button c99-button-ghost" href="<?php echo esc_url( self::navigation_url( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לספריית המנות' : 'Explore dishes' ); ?></a>
				</div>
			</div>
			<div class="c99-container c99-footer-main">
				<div class="c99-footer-brand">
					<a class="c99-footer-brand-link" href="<?php echo esc_url( self::navigation_url( 'home', $lang ) ); ?>">
						<span class="c99-brand-mark" aria-hidden="true"><span>9</span><span>9</span></span>
						<span><strong><?php echo esc_html( $is_he ? 'קומפלט 99' : 'Complete99' ); ?></strong><small><?php echo esc_html( $is_he ? 'אוכל · תפעול · צמיחה' : 'Food · operations · growth' ); ?></small></span>
					</a>
					<p><?php echo esc_html( $is_he ? 'שירותי מזון שוטפים לארגונים, עם תפעול, ידע ותקשורת באותה שפה.' : 'Ongoing organisational foodservice with operations, knowledge and communication working together.' ); ?></p>
					<p class="c99-footer-store-note"><?php echo esc_html( $is_he ? 'קטלוג החנות נמצא בתכנון. מכירה ומשלוח יופעלו רק לאחר השלמת כל פרטי המסחר והשירות.' : 'The store catalogue is being planned. Sales and delivery will open only after all commerce and service details are complete.' ); ?></p>
				</div>
				<nav class="c99-footer-nav" aria-label="<?php echo esc_attr( $is_he ? 'מפת האתר' : 'Site map' ); ?>">
					<?php foreach ( $clusters as $cluster ) : ?>
						<div class="c99-footer-cluster">
							<h2><?php echo esc_html( $cluster[0] ); ?></h2>
							<ul>
								<?php foreach ( $cluster[1] as $link ) : ?>
									<li><a href="<?php echo esc_url( self::navigation_url( $link[0], $lang ) ); ?>"><?php echo esc_html( $link[1] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</nav>
			</div>
			<div class="c99-container c99-footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Complete99</span><span><?php echo esc_html( $is_he ? 'שירות מזון, ידע וכלי עבודה המחוברים סביב אותה חוויה.' : 'Foodservice, knowledge and working tools connected around one experience.' ); ?></span></div>
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
