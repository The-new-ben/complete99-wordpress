<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Content {
	const SEED_VERSION = '2026-07-28.1';
	const DISH_MIN_WORDS_PER_LANGUAGE = 5000;
	const DISH_MIN_SOURCES            = 8;
	const DISH_MIN_AUTHORITATIVE      = 2;

	private static $post_types = array(
		'c99_service'          => array( 'service', 'services', 'שירותים', 'Services', 'services' ),
		'c99_industry'         => array( 'industry', 'industries', 'מגזרים', 'Industries', 'industries' ),
		'c99_platform_feature' => array( 'platform_feature', 'platform_features', 'יכולות מערכת', 'Platform capabilities', 'platform' ),
		'c99_dish'             => array( 'dish', 'dishes', 'מנות', 'Dishes', 'dishes' ),
		'c99_ingredient'       => array( 'ingredient', 'ingredients', 'מרכיבים', 'Ingredients', 'ingredients' ),
		'c99_location'         => array( 'location', 'locations', 'סניפים', 'Locations', 'locations' ),
		'c99_guide'            => array( 'guide', 'guides', 'מרכז ידע', 'Knowledge', 'knowledge' ),
		'c99_case_study'       => array( 'case_study', 'case_studies', 'מקרי בוחן', 'Case studies', 'case-studies' ),
		'c99_team_member'      => array( 'team_member', 'team_members', 'צוות', 'Team', 'team' ),
	);

	private static $taxonomies = array(
		'c99_service_family' => array( 'משפחת שירות', 'Service family', array( 'c99_service', 'c99_case_study' ) ),
		'c99_sector'         => array( 'מגזר', 'Sector', array( 'c99_service', 'c99_industry', 'c99_case_study' ) ),
		'c99_ops_domain'     => array( 'תחום תפעולי', 'Operations domain', array( 'c99_platform_feature', 'c99_guide' ) ),
		'c99_dish_course'    => array( 'סוג מנה', 'Dish course', array( 'c99_dish' ) ),
		'c99_food_tradition' => array( 'מסורת קולינרית', 'Food tradition', array( 'c99_dish', 'c99_ingredient', 'c99_guide' ) ),
		'c99_dietary_note'   => array( 'הערת תזונה', 'Dietary note', array( 'c99_dish' ) ),
		'c99_region'         => array( 'אזור', 'Region', array( 'c99_location', 'c99_case_study' ) ),
	);

	public static function register() {
		foreach ( self::$post_types as $post_type => $definition ) {
			list( $singular_cap, $plural_cap, $label_he, $label_en ) = $definition;
			register_post_type(
				$post_type,
				array(
					'labels' => array(
						'name'          => $label_he,
						'singular_name' => $label_he,
						'add_new_item'  => sprintf( 'הוספת %s', $label_he ),
						'edit_item'     => sprintf( 'עריכת %s', $label_he ),
					),
					'public'              => true,
					'show_in_rest'        => true,
					'has_archive'         => false,
					'hierarchical'        => false,
					'rewrite'             => false,
					'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'custom-fields' ),
					'menu_icon'           => self::menu_icon( $post_type ),
					'capability_type'     => array( 'c99_' . $singular_cap, 'c99_' . $plural_cap ),
					'capabilities'        => self::capabilities( $singular_cap, $plural_cap ),
					'map_meta_cap'        => true,
					'delete_with_user'    => false,
					'show_in_nav_menus'   => true,
					'exclude_from_search' => false,
				)
			);
		}

		foreach ( self::$taxonomies as $taxonomy => $definition ) {
			register_taxonomy(
				$taxonomy,
				$definition[2],
				array(
					'labels'            => array(
						'name'          => $definition[0],
						'singular_name' => $definition[0],
					),
					'public'            => true,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'hierarchical'      => true,
					'rewrite'           => false,
				)
			);
		}

		self::register_meta();
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'enforce_dish_publication_gate' ), 20, 4 );
		add_action( 'add_meta_boxes_c99_dish', array( __CLASS__, 'add_dish_gate_meta_box' ) );
		add_action( 'save_post_c99_dish', array( __CLASS__, 'save_dish_editorial_meta' ), 20, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'dish_gate_notice' ) );
		add_filter( 'redirect_post_location', array( __CLASS__, 'dish_gate_redirect' ), 10, 2 );
	}

	private static function register_meta() {
		$public_types = array_merge( array( 'page' ), array_keys( self::$post_types ) );
		foreach ( $public_types as $post_type ) {
			register_post_meta(
				$post_type,
				'_complete99_language',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => array( __CLASS__, 'sanitize_language' ),
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
			register_post_meta(
				$post_type,
				'_complete99_verification_state',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_key',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}

		register_post_meta(
			'c99_dish',
			'_complete99_recipe',
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_recipe' ),
				'auth_callback'     => static function () {
					return current_user_can( 'edit_c99_dishes' );
				},
			)
		);
		register_post_meta(
			'c99_dish',
			'_complete99_kitchen_reviewed',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_c99_dishes' );
				},
			)
		);
		register_post_meta(
			'c99_dish',
			'_complete99_kitchen_reviewer',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_c99_dishes' );
				},
			)
		);
		register_post_meta(
			'c99_dish',
			'_complete99_kitchen_reviewed_at',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_c99_dishes' );
				},
			)
		);
		foreach ( array( '_complete99_allergen_reviewed', '_complete99_image_approved', '_complete99_originality_reviewed' ) as $boolean_key ) {
			register_post_meta(
				'c99_dish',
				$boolean_key,
				array(
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_c99_dishes' );
					},
				)
			);
		}
		foreach ( array( '_complete99_allergen_reviewer', '_complete99_allergen_reviewed_at', '_complete99_he_editor', '_complete99_en_editor', '_complete99_editorial_reviewed_at' ) as $text_key ) {
			register_post_meta(
				'c99_dish',
				$text_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_c99_dishes' );
					},
				)
			);
		}
	}

	public static function sanitize_language( $value ) {
		return in_array( $value, array( 'he', 'en' ), true ) ? $value : 'he';
	}

	public static function sanitize_recipe( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$allowed = array(
			'yield',
			'prep_minutes',
			'cook_minutes',
			'ingredients',
			'instructions',
			'sources',
			'authoritative_sources',
			'source_notes',
			'allergens',
			'weights',
			'temperatures',
			'test_date',
			'kitchen_test_id',
			'recipe_version',
			'image_approved',
			'he_editor',
			'en_editor',
			'editorial_reviewed_at',
			'originality_reviewed',
			'nutrition_reviewed',
			'health_claims_present',
		);
		$clean   = array();
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}
			if ( is_array( $value[ $key ] ) ) {
				$clean[ $key ] = array_map( 'sanitize_text_field', array_slice( $value[ $key ], 0, 100 ) );
			} elseif ( is_bool( $value[ $key ] ) ) {
				$clean[ $key ] = $value[ $key ];
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $value[ $key ] );
			}
		}
		return $clean;
	}

	public static function register_rewrites() {
		foreach ( self::$post_types as $post_type => $definition ) {
			$base = $definition[4];
			add_rewrite_rule(
				'^' . preg_quote( $base, '/' ) . '/([^/]+)/?$',
				'index.php?post_type=' . $post_type . '&name=$matches[1]',
				'top'
			);
			add_rewrite_rule(
				'^en/' . preg_quote( $base, '/' ) . '/([^/]+)/?$',
				'index.php?post_type=' . $post_type . '&name=en-$matches[1]',
				'top'
			);
		}
	}

	public static function filter_post_type_link( $url, $post ) {
		if ( ! isset( self::$post_types[ $post->post_type ] ) ) {
			return $url;
		}
		$lang = (string) get_post_meta( $post->ID, '_complete99_language', true );
		$slug = (string) $post->post_name;
		if ( 'en' === $lang && 0 === strpos( $slug, 'en-' ) ) {
			$slug = substr( $slug, 3 );
		}
		$prefix = 'en' === $lang ? 'en/' : '';
		return home_url( user_trailingslashit( $prefix . self::$post_types[ $post->post_type ][4] . '/' . $slug ) );
	}

	public static function install_roles() {
		$all_caps       = array();
		$food_caps      = array();
		$marketing_caps = array();
		$location_caps  = array();

		foreach ( self::$post_types as $post_type => $definition ) {
			$caps     = array_values( self::capabilities( $definition[0], $definition[1] ) );
			$all_caps = array_merge( $all_caps, $caps );
			if ( in_array( $post_type, array( 'c99_dish', 'c99_ingredient', 'c99_guide' ), true ) ) {
				$food_caps = array_merge( $food_caps, $caps );
			}
			if ( in_array( $post_type, array( 'c99_service', 'c99_industry', 'c99_platform_feature', 'c99_guide', 'c99_case_study', 'c99_team_member' ), true ) ) {
				$marketing_caps = array_merge( $marketing_caps, $caps );
			}
			if ( 'c99_location' === $post_type ) {
				$location_caps = array_merge( $location_caps, $caps );
			}
		}

		$page_caps = array( 'edit_pages', 'edit_others_pages', 'edit_published_pages', 'publish_pages', 'read_private_pages', 'delete_pages', 'delete_published_pages' );
		self::upsert_role( 'complete99_content_editor', 'Complete99 Content Editor', array_merge( $all_caps, $page_caps, array( 'read', 'upload_files' ) ) );
		self::upsert_role( 'complete99_food_editor', 'Complete99 Food Editor', array_merge( $food_caps, array( 'read', 'upload_files' ) ) );
		self::upsert_role( 'complete99_marketing_editor', 'Complete99 Marketing Editor', array_merge( $marketing_caps, $page_caps, array( 'read', 'upload_files' ) ) );
		self::upsert_role( 'complete99_location_manager', 'Complete99 Location Manager', array_merge( $location_caps, array( 'read', 'upload_files' ) ) );

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( array_unique( array_merge( $all_caps, array( 'read_c99_lead', 'read_private_c99_leads', 'edit_c99_lead', 'edit_c99_leads', 'edit_others_c99_leads', 'delete_c99_lead', 'delete_c99_leads', 'delete_others_c99_leads' ) ) ) as $cap ) {
				$administrator->add_cap( $cap );
			}
		}
	}

	private static function upsert_role( $slug, $label, $caps ) {
		$role = get_role( $slug );
		if ( ! $role ) {
			$role = add_role( $slug, $label, array( 'read' => true ) );
		}
		if ( ! $role ) {
			return;
		}
		foreach ( array_unique( $caps ) as $cap ) {
			$role->add_cap( $cap );
		}
	}

	private static function capabilities( $singular, $plural ) {
		$singular = 'c99_' . $singular;
		$plural   = 'c99_' . $plural;
		return array(
			'edit_post'              => 'edit_' . $singular,
			'read_post'              => 'read_' . $singular,
			'delete_post'            => 'delete_' . $singular,
			'edit_posts'             => 'edit_' . $plural,
			'edit_others_posts'      => 'edit_others_' . $plural,
			'publish_posts'          => 'publish_' . $plural,
			'read_private_posts'     => 'read_private_' . $plural,
			'delete_posts'           => 'delete_' . $plural,
			'delete_private_posts'   => 'delete_private_' . $plural,
			'delete_published_posts' => 'delete_published_' . $plural,
			'delete_others_posts'    => 'delete_others_' . $plural,
			'edit_private_posts'     => 'edit_private_' . $plural,
			'edit_published_posts'   => 'edit_published_' . $plural,
			'create_posts'           => 'edit_' . $plural,
		);
	}

	private static function menu_icon( $post_type ) {
		$icons = array(
			'c99_service'          => 'dashicons-store',
			'c99_industry'         => 'dashicons-building',
			'c99_platform_feature' => 'dashicons-dashboard',
			'c99_dish'             => 'dashicons-food',
			'c99_ingredient'       => 'dashicons-carrot',
			'c99_location'         => 'dashicons-location-alt',
			'c99_guide'            => 'dashicons-book-alt',
			'c99_case_study'       => 'dashicons-chart-line',
			'c99_team_member'      => 'dashicons-groups',
		);
		return isset( $icons[ $post_type ] ) ? $icons[ $post_type ] : 'dashicons-admin-post';
	}

	public static function seed_launch_content() {
		$launch = require COMPLETE99_PLATFORM_DIR . 'data/launch-content.php';
		$dishes = require COMPLETE99_PLATFORM_DIR . 'data/dish-seeds.php';

		$english_home = 0;
		foreach ( array( 'he', 'en' ) as $language ) {
			foreach ( $launch as $blueprint ) {
				if ( 'home' !== $blueprint['key'] ) {
					continue;
				}
				$id = self::upsert_seed( $blueprint, $language, 0 );
				if ( ! $id ) {
					throw new \RuntimeException( 'Complete99 home seed failed.' );
				}
				if ( 'en' === $language ) {
					$english_home = $id;
				} elseif ( $id && ( ! get_option( 'page_on_front' ) || get_post_meta( (int) get_option( 'page_on_front' ), '_complete99_seed_key', true ) ) ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', $id );
				}
			}
		}

		foreach ( array( 'he', 'en' ) as $language ) {
			foreach ( $launch as $blueprint ) {
				if ( 'home' === $blueprint['key'] ) {
					continue;
				}
				$parent = ( 'en' === $language && 'page' === $blueprint['type'] ) ? $english_home : 0;
				if ( ! self::upsert_seed( $blueprint, $language, $parent ) ) {
					throw new \RuntimeException( 'Complete99 launch seed failed.' );
				}
			}
		}

		foreach ( $dishes as $blueprint ) {
			foreach ( array( 'he', 'en' ) as $language ) {
				if ( ! self::upsert_seed( $blueprint, $language, 0 ) ) {
					throw new \RuntimeException( 'Complete99 dish seed failed.' );
				}
			}
		}
	}

	private static function upsert_seed( $blueprint, $language, $parent ) {
		$seed_key = $blueprint['key'] . ':' . $language;
		$existing = self::find_seed_post_id( $seed_key );
		$title    = $blueprint['title'][ $language ];
		$excerpt  = $blueprint['excerpt'][ $language ];
		$content  = $blueprint['content'][ $language ];
		$slug     = $blueprint['slug'][ $language ];
		$status   = isset( $blueprint['status'] ) ? $blueprint['status'] : 'publish';
		$post     = array(
			'post_type'    => $blueprint['type'],
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
			'post_status'  => $status,
			'post_parent'  => $parent,
		);
		$new_hash = self::content_hash( $post );

		if ( $existing ) {
			$current = get_post( $existing );
			if ( ! $current ) {
				return 0;
			}
			$stored_hash  = (string) get_post_meta( $existing, '_complete99_seed_hash', true );
			$current_hash = self::content_hash(
				array(
					'post_title'   => $current->post_title,
					'post_excerpt' => $current->post_excerpt,
					'post_content' => $current->post_content,
				)
			);
			if ( '' !== $stored_hash && hash_equals( $stored_hash, $current_hash ) ) {
				$post['ID'] = $existing;
				$result     = wp_update_post( wp_slash( $post ), true );
				if ( is_wp_error( $result ) ) {
					return 0;
				}
				update_post_meta( $existing, '_complete99_seed_hash', $new_hash );
			}
			$id = $existing;
		} else {
			$id = wp_insert_post( wp_slash( $post ), true );
			if ( is_wp_error( $id ) ) {
				return 0;
			}
			update_post_meta( $id, '_complete99_seed_hash', $new_hash );
		}

		update_post_meta( $id, '_complete99_seed_key', $seed_key );
		update_post_meta( $id, '_complete99_translation_key', $blueprint['key'] );
		update_post_meta( $id, '_complete99_language', $language );
		update_post_meta( $id, '_complete99_seed_version', self::SEED_VERSION );
		update_post_meta( $id, '_complete99_verification_state', isset( $blueprint['verification'] ) ? $blueprint['verification'] : 'editorial_review' );
		if ( ! empty( $blueprint['image'] ) ) {
			update_post_meta( $id, '_complete99_image_asset', sanitize_file_name( $blueprint['image'] ) );
		}
		if ( ! empty( $blueprint['recipe'] ) ) {
			update_post_meta( $id, '_complete99_recipe', $blueprint['recipe'] );
		}
		return (int) $id;
	}

	private static function content_hash( $post ) {
		return hash( 'sha256', (string) $post['post_title'] . "\n" . (string) $post['post_excerpt'] . "\n" . (string) $post['post_content'] );
	}

	public static function find_seed_post_id( $seed_key ) {
		$ids = get_posts(
			array(
				'post_type'              => array_merge( array( 'page' ), array_keys( self::$post_types ) ),
				'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future', 'trash' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'meta_key'               => '_complete99_seed_key',
				'meta_value'             => sanitize_text_field( $seed_key ),
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	public static function route_url( $translation_key, $language ) {
		$id = self::find_seed_post_id( $translation_key . ':' . $language );
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	public static function language_for_post( $post_id ) {
		$lang = (string) get_post_meta( $post_id, '_complete99_language', true );
		return in_array( $lang, array( 'he', 'en' ), true ) ? $lang : 'he';
	}

	public static function dish_gate_status( $post_id, $candidate_content = null ) {
		$post_id = (int) $post_id;
		$key     = (string) get_post_meta( $post_id, '_complete99_translation_key', true );
		$lang    = self::language_for_post( $post_id );
		$other   = 'he' === $lang ? 'en' : 'he';
		$pair_id = $key ? self::find_seed_post_id( $key . ':' . $other ) : 0;
		$current = null !== $candidate_content ? (string) $candidate_content : (string) get_post_field( 'post_content', $post_id );
		$paired  = $pair_id ? (string) get_post_field( 'post_content', $pair_id ) : '';
		$counts  = array(
			$lang  => self::word_count( $current ),
			$other => self::word_count( $paired ),
		);
		$reasons = array();
		if ( $counts['he'] < self::DISH_MIN_WORDS_PER_LANGUAGE ) {
			$reasons[] = sprintf( 'Hebrew content requires at least %d words (%d now).', self::DISH_MIN_WORDS_PER_LANGUAGE, $counts['he'] );
		}
		if ( $counts['en'] < self::DISH_MIN_WORDS_PER_LANGUAGE ) {
			$reasons[] = sprintf( 'English content requires at least %d words (%d now).', self::DISH_MIN_WORDS_PER_LANGUAGE, $counts['en'] );
		}
		foreach ( array_filter( array( $post_id, $pair_id ) ) as $dish_id ) {
			$recipe        = get_post_meta( $dish_id, '_complete99_recipe', true );
			$sources       = is_array( $recipe ) && isset( $recipe['sources'] ) && is_array( $recipe['sources'] ) ? array_filter( $recipe['sources'], 'esc_url_raw' ) : array();
			$authoritative = is_array( $recipe ) && isset( $recipe['authoritative_sources'] ) && is_array( $recipe['authoritative_sources'] ) ? array_filter( $recipe['authoritative_sources'], 'esc_url_raw' ) : array();
			if ( count( $sources ) < self::DISH_MIN_SOURCES ) {
				$reasons[] = sprintf( 'Dish %d requires at least %d source URLs.', $dish_id, self::DISH_MIN_SOURCES );
			}
			if ( count( $authoritative ) < self::DISH_MIN_AUTHORITATIVE ) {
				$reasons[] = sprintf( 'Dish %d requires at least %d authoritative/primary source URLs.', $dish_id, self::DISH_MIN_AUTHORITATIVE );
			}
			if ( ! rest_sanitize_boolean( get_post_meta( $dish_id, '_complete99_kitchen_reviewed', true ) ) ) {
				$reasons[] = sprintf( 'Dish %d requires kitchen review.', $dish_id );
			}
			if ( '' === trim( (string) get_post_meta( $dish_id, '_complete99_kitchen_reviewer', true ) ) || '' === trim( (string) get_post_meta( $dish_id, '_complete99_kitchen_reviewed_at', true ) ) ) {
				$reasons[] = sprintf( 'Dish %d requires reviewer name and review date.', $dish_id );
			}
			foreach ( array( 'yield', 'prep_minutes', 'cook_minutes', 'ingredients', 'instructions', 'weights', 'temperatures', 'test_date', 'kitchen_test_id', 'recipe_version' ) as $required_recipe_key ) {
				if ( ! is_array( $recipe ) || empty( $recipe[ $required_recipe_key ] ) ) {
					$reasons[] = sprintf( 'Dish %d requires recipe field %s.', $dish_id, $required_recipe_key );
				}
			}
			if ( ! rest_sanitize_boolean( get_post_meta( $dish_id, '_complete99_allergen_reviewed', true ) )
				|| '' === trim( (string) get_post_meta( $dish_id, '_complete99_allergen_reviewer', true ) )
				|| '' === trim( (string) get_post_meta( $dish_id, '_complete99_allergen_reviewed_at', true ) ) ) {
				$reasons[] = sprintf( 'Dish %d requires named/date-stamped allergen review.', $dish_id );
			}
			if ( ! rest_sanitize_boolean( get_post_meta( $dish_id, '_complete99_image_approved', true ) ) ) {
				$reasons[] = sprintf( 'Dish %d requires public image approval.', $dish_id );
			}
			if ( ! rest_sanitize_boolean( get_post_meta( $dish_id, '_complete99_originality_reviewed', true ) )
				|| '' === trim( (string) get_post_meta( $dish_id, '_complete99_he_editor', true ) )
				|| '' === trim( (string) get_post_meta( $dish_id, '_complete99_en_editor', true ) )
				|| '' === trim( (string) get_post_meta( $dish_id, '_complete99_editorial_reviewed_at', true ) ) ) {
				$reasons[] = sprintf( 'Dish %d requires originality and bilingual editorial review metadata.', $dish_id );
			}
			if ( is_array( $recipe ) && ! empty( $recipe['health_claims_present'] ) && empty( $recipe['nutrition_reviewed'] ) ) {
				$reasons[] = sprintf( 'Dish %d has health language and requires qualified nutrition review.', $dish_id );
			}
		}
		if ( ! $pair_id ) {
			$reasons[] = 'A paired Hebrew/English dish record is required.';
		}
		return array(
			'passed'  => empty( $reasons ),
			'counts'  => $counts,
			'pair_id' => $pair_id,
			'reasons' => array_values( array_unique( $reasons ) ),
		);
	}

	private static function word_count( $content ) {
		$text   = html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $content ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$tokens = preg_split( '/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		return is_array( $tokens ) ? count( $tokens ) : 0;
	}

	public static function enforce_dish_publication_gate( $data, $postarr, $unsanitized_postarr, $update ) {
		if ( 'c99_dish' !== ( isset( $data['post_type'] ) ? $data['post_type'] : '' ) || ! in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
			return $data;
		}
		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		if ( ! $post_id ) {
			$data['post_status'] = 'draft';
			return $data;
		}
		$status = self::dish_gate_status( $post_id, isset( $data['post_content'] ) ? $data['post_content'] : null );
		if ( ! $status['passed'] ) {
			$data['post_status'] = 'draft';
			set_transient( 'complete99_dish_gate_' . get_current_user_id(), 1, MINUTE_IN_SECONDS );
		}
		return $data;
	}

	public static function add_dish_gate_meta_box() {
		add_meta_box(
			'complete99-dish-gate',
			__( 'Complete99 publication gate', 'complete99-platform' ),
			array( __CLASS__, 'render_dish_gate_meta_box' ),
			'c99_dish',
			'side',
			'high'
		);
		add_meta_box(
			'complete99-dish-editorial',
			__( 'Complete99 dish editorial record', 'complete99-platform' ),
			array( __CLASS__, 'render_dish_editorial_meta_box' ),
			'c99_dish',
			'normal',
			'high'
		);
	}

	public static function render_dish_gate_meta_box( $post ) {
		$status = self::dish_gate_status( $post->ID );
		echo '<p><strong>' . esc_html( $status['passed'] ? 'Ready for publication and indexation' : 'Publication blocked' ) . '</strong></p>';
		echo '<p>' . esc_html( sprintf( 'Hebrew: %d / %d words', $status['counts']['he'], self::DISH_MIN_WORDS_PER_LANGUAGE ) ) . '<br>';
		echo esc_html( sprintf( 'English: %d / %d words', $status['counts']['en'], self::DISH_MIN_WORDS_PER_LANGUAGE ) ) . '</p>';
		if ( $status['reasons'] ) {
			echo '<ul style="list-style:disc;padding-inline-start:18px">';
			foreach ( $status['reasons'] as $reason ) {
				echo '<li>' . esc_html( $reason ) . '</li>';
			}
			echo '</ul>';
		}
	}

	public static function render_dish_editorial_meta_box( $post ) {
		$recipe = get_post_meta( $post->ID, '_complete99_recipe', true );
		$recipe = is_array( $recipe ) ? $recipe : array();
		wp_nonce_field( 'complete99_dish_editorial', 'complete99_dish_editorial_nonce' );
		$text_field = static function ( $name, $label, $value, $type = 'text' ) {
			echo '<label style="display:block;margin:0 0 12px"><strong>' . esc_html( $label ) . '</strong><br>';
			echo '<input class="widefat" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"></label>';
		};
		$lines_field = static function ( $name, $label, $values, $rows = 4 ) {
			$value = is_array( $values ) ? implode( "\n", $values ) : (string) $values;
			echo '<label style="display:block;margin:0 0 12px"><strong>' . esc_html( $label ) . '</strong><br>';
			echo '<textarea class="widefat code" rows="' . esc_attr( $rows ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea></label>';
		};
		$checkbox = static function ( $name, $label, $checked ) {
			echo '<label style="display:block;margin:0 0 12px"><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( (bool) $checked, true, false ) . '> <strong>' . esc_html( $label ) . '</strong></label>';
		};

		echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">';
		echo '<div>';
		$lines_field( 'c99_sources', 'Credible source URLs — one per line (minimum 8)', isset( $recipe['sources'] ) ? $recipe['sources'] : array(), 8 );
		$lines_field( 'c99_authoritative_sources', 'Authoritative/primary source URLs — one per line (minimum 2)', isset( $recipe['authoritative_sources'] ) ? $recipe['authoritative_sources'] : array(), 4 );
		$lines_field( 'c99_source_notes', 'Claim/source and dispute notes — one per line', isset( $recipe['source_notes'] ) ? $recipe['source_notes'] : array(), 6 );
		$lines_field( 'c99_ingredients', 'Tested public ingredients — one per line', isset( $recipe['ingredients'] ) ? $recipe['ingredients'] : array(), 8 );
		$lines_field( 'c99_instructions', 'Tested public instructions — one step per line', isset( $recipe['instructions'] ) ? $recipe['instructions'] : array(), 8 );
		$lines_field( 'c99_allergens', 'Allergen record — one item per line; use “none identified” only after review', isset( $recipe['allergens'] ) ? $recipe['allergens'] : array(), 4 );
		echo '</div><div>';
		$text_field( 'c99_yield', 'Recipe yield', isset( $recipe['yield'] ) ? $recipe['yield'] : '' );
		$text_field( 'c99_prep_minutes', 'Preparation minutes', isset( $recipe['prep_minutes'] ) ? $recipe['prep_minutes'] : '', 'number' );
		$text_field( 'c99_cook_minutes', 'Cooking minutes', isset( $recipe['cook_minutes'] ) ? $recipe['cook_minutes'] : '', 'number' );
		$lines_field( 'c99_weights', 'Tested weights — one per line', isset( $recipe['weights'] ) ? $recipe['weights'] : array(), 4 );
		$lines_field( 'c99_temperatures', 'Tested temperatures — one per line', isset( $recipe['temperatures'] ) ? $recipe['temperatures'] : array(), 4 );
		$text_field( 'c99_test_date', 'Kitchen test date', isset( $recipe['test_date'] ) ? $recipe['test_date'] : '', 'date' );
		$text_field( 'c99_kitchen_test_id', 'Kitchen test ID', isset( $recipe['kitchen_test_id'] ) ? $recipe['kitchen_test_id'] : '' );
		$text_field( 'c99_recipe_version', 'Public recipe version', isset( $recipe['recipe_version'] ) ? $recipe['recipe_version'] : '' );
		$checkbox( 'c99_kitchen_reviewed', 'Kitchen review complete', get_post_meta( $post->ID, '_complete99_kitchen_reviewed', true ) );
		$text_field( 'c99_kitchen_reviewer', 'Kitchen reviewer', get_post_meta( $post->ID, '_complete99_kitchen_reviewer', true ) );
		$text_field( 'c99_kitchen_reviewed_at', 'Kitchen review date', get_post_meta( $post->ID, '_complete99_kitchen_reviewed_at', true ), 'date' );
		$checkbox( 'c99_allergen_reviewed', 'Allergen review complete', get_post_meta( $post->ID, '_complete99_allergen_reviewed', true ) );
		$text_field( 'c99_allergen_reviewer', 'Allergen reviewer', get_post_meta( $post->ID, '_complete99_allergen_reviewer', true ) );
		$text_field( 'c99_allergen_reviewed_at', 'Allergen review date', get_post_meta( $post->ID, '_complete99_allergen_reviewed_at', true ), 'date' );
		$checkbox( 'c99_image_approved', 'Original image approved for public use', get_post_meta( $post->ID, '_complete99_image_approved', true ) );
		$checkbox( 'c99_originality_reviewed', 'Originality/repetition review complete', get_post_meta( $post->ID, '_complete99_originality_reviewed', true ) );
		$text_field( 'c99_he_editor', 'Hebrew editor', get_post_meta( $post->ID, '_complete99_he_editor', true ) );
		$text_field( 'c99_en_editor', 'English editor', get_post_meta( $post->ID, '_complete99_en_editor', true ) );
		$text_field( 'c99_editorial_reviewed_at', 'Bilingual editorial review date', get_post_meta( $post->ID, '_complete99_editorial_reviewed_at', true ), 'date' );
		$checkbox( 'c99_health_claims_present', 'Public copy contains health/nutrition claims', isset( $recipe['health_claims_present'] ) ? $recipe['health_claims_present'] : false );
		$checkbox( 'c99_nutrition_reviewed', 'Qualified nutrition review complete', isset( $recipe['nutrition_reviewed'] ) ? $recipe['nutrition_reviewed'] : false );
		echo '</div></div>';
	}

	public static function save_dish_editorial_meta( $post_id, $post ) {
		$nonce = isset( $_POST['complete99_dish_editorial_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['complete99_dish_editorial_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'complete99_dish_editorial' ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$lines = static function ( $key, $urls = false ) {
			$raw   = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$items = preg_split( '/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY );
			$items = is_array( $items ) ? array_map( 'trim', $items ) : array();
			if ( $urls ) {
				$items = array_filter( array_map( 'esc_url_raw', $items ) );
			} else {
				$items = array_filter( array_map( 'sanitize_text_field', $items ) );
			}
			return array_values( array_unique( $items ) );
		};
		$value = static function ( $key ) {
			return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		};
		$recipe = get_post_meta( $post_id, '_complete99_recipe', true );
		$recipe = is_array( $recipe ) ? $recipe : array();
		$recipe = array_merge(
			$recipe,
			array(
				'sources'               => $lines( 'c99_sources', true ),
				'authoritative_sources' => $lines( 'c99_authoritative_sources', true ),
				'source_notes'          => $lines( 'c99_source_notes' ),
				'ingredients'           => $lines( 'c99_ingredients' ),
				'instructions'          => $lines( 'c99_instructions' ),
				'allergens'             => $lines( 'c99_allergens' ),
				'weights'               => $lines( 'c99_weights' ),
				'temperatures'          => $lines( 'c99_temperatures' ),
				'yield'                 => $value( 'c99_yield' ),
				'prep_minutes'          => absint( $value( 'c99_prep_minutes' ) ),
				'cook_minutes'          => absint( $value( 'c99_cook_minutes' ) ),
				'test_date'             => $value( 'c99_test_date' ),
				'kitchen_test_id'       => $value( 'c99_kitchen_test_id' ),
				'recipe_version'        => $value( 'c99_recipe_version' ),
				'health_claims_present' => isset( $_POST['c99_health_claims_present'] ),
				'nutrition_reviewed'    => isset( $_POST['c99_nutrition_reviewed'] ),
			)
		);
		update_post_meta( $post_id, '_complete99_recipe', self::sanitize_recipe( $recipe ) );
		$boolean_meta = array(
			'_complete99_kitchen_reviewed'    => 'c99_kitchen_reviewed',
			'_complete99_allergen_reviewed'   => 'c99_allergen_reviewed',
			'_complete99_image_approved'      => 'c99_image_approved',
			'_complete99_originality_reviewed'=> 'c99_originality_reviewed',
		);
		foreach ( $boolean_meta as $meta_key => $field ) {
			update_post_meta( $post_id, $meta_key, isset( $_POST[ $field ] ) );
		}
		$text_meta = array(
			'_complete99_kitchen_reviewer'     => 'c99_kitchen_reviewer',
			'_complete99_kitchen_reviewed_at'  => 'c99_kitchen_reviewed_at',
			'_complete99_allergen_reviewer'    => 'c99_allergen_reviewer',
			'_complete99_allergen_reviewed_at' => 'c99_allergen_reviewed_at',
			'_complete99_he_editor'            => 'c99_he_editor',
			'_complete99_en_editor'            => 'c99_en_editor',
			'_complete99_editorial_reviewed_at'=> 'c99_editorial_reviewed_at',
		);
		foreach ( $text_meta as $meta_key => $field ) {
			update_post_meta( $post_id, $meta_key, $value( $field ) );
		}
	}

	public static function dish_gate_redirect( $location, $post_id ) {
		if ( 'c99_dish' === get_post_type( $post_id ) && get_transient( 'complete99_dish_gate_' . get_current_user_id() ) ) {
			delete_transient( 'complete99_dish_gate_' . get_current_user_id() );
			return add_query_arg( 'complete99_dish_gate', 'blocked', $location );
		}
		return $location;
	}

	public static function dish_gate_notice() {
		if ( ! isset( $_GET['complete99_dish_gate'] ) || 'blocked' !== sanitize_key( wp_unslash( $_GET['complete99_dish_gate'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'This dish remains private. Both language versions need 5,000 substantive words, eight credible sources (two authoritative/primary), complete kitchen-test fields, allergen review, image approval, and bilingual editorial review.', 'complete99-platform' ) . '</p></div>';
	}

	public static function is_complete99_post( $post_id ) {
		return '' !== (string) get_post_meta( $post_id, '_complete99_seed_key', true );
	}

	public static function post_types() {
		return self::$post_types;
	}
}
