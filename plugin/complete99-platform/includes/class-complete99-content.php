<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Content {
	const SEED_VERSION = '2026-07-29.1';
	const PUBLIC_AUDIENCE = 'culinary_consumer_v1';
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

	private static $public_post_types = array(
		'c99_dish',
		'c99_ingredient',
		'c99_guide',
	);

	private static $hub_by_post_type = array(
		'c99_service'          => 'services',
		'c99_industry'         => 'industries',
		'c99_platform_feature' => 'platform',
		'c99_dish'             => 'dishes',
		'c99_ingredient'       => 'ingredients',
		'c99_guide'            => 'knowledge',
		'c99_location'         => 'locations',
		'c99_case_study'       => 'case-studies',
		'c99_team_member'      => 'about',
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

	private static $public_taxonomies = array(
		'c99_dish_course',
		'c99_food_tradition',
		'c99_dietary_note',
	);

	public static function boot_governance() {
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'filter_sitemap_provider' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'filter_sitemap_post_types' ) );
		add_filter( 'wp_sitemaps_taxonomies', array( __CLASS__, 'filter_sitemap_taxonomies' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'filter_sitemap_posts_query_args' ), 10, 2 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots_index_gate' ), 20 );
	}

	public static function register() {
		foreach ( self::$post_types as $post_type => $definition ) {
			list( $singular_cap, $plural_cap, $label_he, $label_en ) = $definition;
			$is_public = self::is_public_post_type( $post_type );
			register_post_type(
				$post_type,
				array(
					'labels' => array(
						'name'          => $label_he,
						'singular_name' => $label_he,
						'add_new_item'  => sprintf( 'הוספת %s', $label_he ),
						'edit_item'     => sprintf( 'עריכת %s', $label_he ),
					),
					'public'              => $is_public,
					'publicly_queryable'  => $is_public,
					'show_ui'             => true,
					'show_in_menu'        => true,
					'show_in_rest'        => $is_public,
					'show_in_admin_bar'   => $is_public,
					'has_archive'         => false,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => $is_public,
					'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'custom-fields' ),
					'menu_icon'           => self::menu_icon( $post_type ),
					'capability_type'     => array( 'c99_' . $singular_cap, 'c99_' . $plural_cap ),
					'capabilities'        => self::capabilities( $singular_cap, $plural_cap ),
					'map_meta_cap'        => true,
					'delete_with_user'    => false,
					'show_in_nav_menus'   => $is_public,
					'exclude_from_search' => ! $is_public,
				)
			);
		}

		foreach ( self::$taxonomies as $taxonomy => $definition ) {
			$is_public = self::is_public_taxonomy( $taxonomy );
			register_taxonomy(
				$taxonomy,
				$definition[2],
				array(
					'labels'            => array(
						'name'          => $definition[0],
						'singular_name' => $definition[0],
					),
					'public'            => $is_public,
					'publicly_queryable' => $is_public,
					'show_ui'           => true,
					'show_in_rest'      => $is_public,
					'show_in_nav_menus' => $is_public,
					'show_tagcloud'     => false,
					'show_admin_column' => true,
					'hierarchical'      => true,
					'rewrite'           => false,
					'query_var'         => $is_public,
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

	private static function is_public_post_type( $post_type ) {
		return in_array( (string) $post_type, self::$public_post_types, true );
	}

	private static function is_public_taxonomy( $taxonomy ) {
		return in_array( (string) $taxonomy, self::$public_taxonomies, true );
	}

	private static function register_meta() {
		$managed_types = array_merge( array( 'page' ), array_keys( self::$post_types ) );
		foreach ( $managed_types as $post_type ) {
			$show_in_rest = 'page' === $post_type || self::is_public_post_type( $post_type );
			register_post_meta(
				$post_type,
				'_complete99_managed',
				array(
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
			register_post_meta(
				$post_type,
				'_complete99_translation_group',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => 'sanitize_key',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
			register_post_meta(
				$post_type,
				'_complete99_language',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => array( __CLASS__, 'sanitize_language' ),
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
			register_post_meta(
				$post_type,
				'_complete99_parent_hub',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => 'sanitize_key',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
			register_post_meta(
				$post_type,
				'_complete99_index_eligible',
				array(
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => 'rest_sanitize_boolean',
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
					'show_in_rest'      => $show_in_rest,
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

	public static function filter_sitemap_provider( $provider, $name ) {
		return 'users' === (string) $name ? false : $provider;
	}

	public static function filter_sitemap_post_types( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return array();
		}
		$allowed = array_fill_keys( array_merge( array( 'page' ), self::$public_post_types ), true );
		foreach ( array_keys( $post_types ) as $post_type ) {
			if ( ! isset( $allowed[ $post_type ] ) ) {
				unset( $post_types[ $post_type ] );
			}
		}
		return $post_types;
	}

	public static function filter_sitemap_taxonomies( $taxonomies ) {
		return array();
	}

	public static function filter_sitemap_posts_query_args( $args, $post_type ) {
		$allowed = array_merge( array( 'page' ), self::$public_post_types );
		if ( ! in_array( (string) $post_type, $allowed, true ) ) {
			$args['post__in'] = array( 0 );
			return $args;
		}

		$eligibility = array(
			'relation' => 'AND',
			array(
				'key'     => '_complete99_managed',
				'value'   => '1',
				'compare' => '=',
			),
			array(
				'key'     => '_complete99_index_eligible',
				'value'   => '1',
				'compare' => '=',
			),
			array(
				'key'     => '_complete99_verification_state',
				'value'   => array( 'editorial_review', 'verified', 'product_demo', 'launch_ready' ),
				'compare' => 'IN',
			),
		);
		if ( ! empty( $args['meta_query'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				$args['meta_query'],
				$eligibility,
			);
		} else {
			$args['meta_query'] = $eligibility;
		}
		$args['post_status']  = 'publish';
		$args['has_password'] = false;
		return $args;
	}

	public static function robots_index_gate( $robots ) {
		if ( is_tax( self::$public_taxonomies ) ) {
			unset( $robots['index'] );
			$robots['noindex']  = true;
			$robots['nofollow'] = false;
			return $robots;
		}
		if ( ! is_singular() ) {
			return $robots;
		}
		$post_id = (int) get_queried_object_id();
		if ( ! self::is_complete99_post( $post_id ) || self::is_index_eligible( $post_id ) ) {
			return $robots;
		}
		unset( $robots['index'] );
		$robots['noindex']  = true;
		$robots['nofollow'] = false;
		return $robots;
	}

	public static function is_index_eligible( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post
			|| 'publish' !== (string) $post->post_status
			|| '' !== (string) $post->post_password
			|| ! self::is_complete99_post( $post->ID )
			|| ! rest_sanitize_boolean( get_post_meta( $post->ID, '_complete99_index_eligible', true ) ) ) {
			return false;
		}
		$translation_key = sanitize_key( (string) get_post_meta( $post->ID, '_complete99_translation_key', true ) );
		if ( 'store' === $translation_key
			&& class_exists( 'Complete99_Commerce' )
			&& ! Complete99_Commerce::is_ready() ) {
			return false;
		}
		$verification = (string) get_post_meta( $post->ID, '_complete99_verification_state', true );
		return in_array( $verification, array( 'editorial_review', 'verified', 'product_demo', 'launch_ready' ), true );
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
			if ( ! self::is_public_post_type( $post_type ) ) {
				continue;
			}
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
		add_rewrite_rule(
			'^menu/([^/]+)/?$',
			'index.php?complete99_live_dish=$matches[1]&complete99_live_lang=he',
			'top'
		);
		add_rewrite_rule(
			'^en/menu/([^/]+)/?$',
			'index.php?complete99_live_dish=$matches[1]&complete99_live_lang=en',
			'top'
		);
	}

	public static function filter_post_type_link( $url, $post ) {
		if ( ! isset( self::$post_types[ $post->post_type ] ) || ! self::is_public_post_type( $post->post_type ) ) {
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
		$requirements = self::role_requirements();
		foreach ( $requirements['roles'] as $slug => $spec ) {
			self::upsert_role( $slug, $spec['label'], $spec['caps'] );
		}

		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			throw new \RuntimeException( 'The administrator role is unavailable.' );
		}
		foreach ( $requirements['administrator_caps'] as $cap ) {
			$administrator->add_cap( $cap );
		}

		self::assert_roles_persisted( $requirements );
	}

	private static function role_requirements() {
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
		return array(
			'roles'              => array(
				'complete99_content_editor'   => array(
					'label' => 'Complete99 Content Editor',
					'caps'  => array_values( array_unique( array_merge( $all_caps, $page_caps, array( 'read', 'upload_files' ) ) ) ),
				),
				'complete99_food_editor'      => array(
					'label' => 'Complete99 Food Editor',
					'caps'  => array_values( array_unique( array_merge( $food_caps, array( 'read', 'upload_files' ) ) ) ),
				),
				'complete99_marketing_editor' => array(
					'label' => 'Complete99 Marketing Editor',
					'caps'  => array_values( array_unique( array_merge( $marketing_caps, $page_caps, array( 'read', 'upload_files' ) ) ) ),
				),
				'complete99_location_manager' => array(
					'label' => 'Complete99 Location Manager',
					'caps'  => array_values( array_unique( array_merge( $location_caps, array( 'read', 'upload_files' ) ) ) ),
				),
			),
			'administrator_caps' => array_values( self::administrator_caps( $all_caps ) ),
		);
	}

	private static function upsert_role( $slug, $label, $caps ) {
		$role = get_role( $slug );
		if ( ! $role ) {
			$role = add_role( $slug, $label, array( 'read' => true ) );
		}
		if ( ! $role ) {
			throw new \RuntimeException( 'A required Complete99 role could not be stored.' );
		}
		foreach ( array_unique( $caps ) as $cap ) {
			$role->add_cap( $cap );
		}
	}

	private static function administrator_caps( $all_caps ) {
		return array_unique(
			array_merge(
				$all_caps,
				array(
					'read_c99_lead',
					'read_private_c99_leads',
					'edit_c99_lead',
					'edit_c99_leads',
					'edit_others_c99_leads',
					'delete_c99_lead',
					'delete_c99_leads',
					'delete_others_c99_leads',
				)
			)
		);
	}

	/**
	 * Read the serialized roles option from the current transaction.
	 *
	 * WP_Role mutates its in-memory object before WP_Roles attempts the option
	 * write, and WP_Roles discards update_option() failures. Only the database
	 * row proves that a capability will survive the next request.
	 *
	 * @param array|null $requirements Optional precomputed capability contract.
	 */
	private static function assert_roles_persisted( $requirements = null ) {
		global $wpdb;

		$requirements = is_array( $requirements ) ? $requirements : self::role_requirements();
		$role_key     = $wpdb->get_blog_prefix( get_current_blog_id() ) . 'user_roles';
		$wpdb->last_error = '';
		$raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$role_key
			)
		);
		if ( '' !== (string) $wpdb->last_error || null === $raw ) {
			throw new \RuntimeException( 'The durable WordPress roles option is unavailable.' );
		}
		$stored = maybe_unserialize( $raw );
		if ( ! is_array( $stored ) ) {
			throw new \RuntimeException( 'The durable WordPress roles option is invalid.' );
		}

		foreach ( $requirements['roles'] as $slug => $spec ) {
			if ( ! isset( $stored[ $slug ]['capabilities'] ) || ! is_array( $stored[ $slug ]['capabilities'] ) ) {
				throw new \RuntimeException( 'A required Complete99 role is missing from durable storage.' );
			}
			foreach ( $spec['caps'] as $cap ) {
				if ( true !== ( $stored[ $slug ]['capabilities'][ $cap ] ?? null ) ) {
					throw new \RuntimeException( 'A required Complete99 role capability is missing from durable storage.' );
				}
			}
		}

		if ( ! isset( $stored['administrator']['capabilities'] ) || ! is_array( $stored['administrator']['capabilities'] ) ) {
			throw new \RuntimeException( 'The administrator role is missing from durable storage.' );
		}
		foreach ( $requirements['administrator_caps'] as $cap ) {
			if ( true !== ( $stored['administrator']['capabilities'][ $cap ] ?? null ) ) {
				throw new \RuntimeException( 'A required administrator capability is missing from durable storage.' );
			}
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

		self::ensure_site_identity();

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
					if ( 'page' !== get_option( 'show_on_front' ) || $id !== (int) get_option( 'page_on_front' ) ) {
						throw new \RuntimeException( 'Complete99 front-page options failed readback.' );
					}
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

	/**
	 * Correct only empty, default, or known legacy/misspelled launch identity.
	 *
	 * Later editor-owned identity changes remain untouched on future releases.
	 */
	private static function ensure_site_identity() {
		$current_name = (string) get_option( 'blogname', '' );
		$legacy_name  = false !== strpos( $current_name, 'קומפליט' )
			|| in_array( trim( $current_name ), array( '', 'WordPress', 'Complete99', 'Complete 99' ), true );

		if ( $legacy_name ) {
			update_option( 'blogname', 'קומפלט 99 | Complete99' );
			if ( 'קומפלט 99 | Complete99' !== (string) get_option( 'blogname', '' ) ) {
				throw new \RuntimeException( 'Complete99 site-name correction failed readback.' );
			}
		}

		$current_description = (string) get_option( 'blogdescription', '' );
		$legacy_description  = in_array(
			trim( $current_description ),
			array( '', 'Just another WordPress site', 'אוכל של בית. תפעול של מחר.' ),
			true
		);
		if ( $legacy_description ) {
			update_option( 'blogdescription', 'סביח, קובה ואוכל ביתי בתל אביב.' );
			if ( 'סביח, קובה ואוכל ביתי בתל אביב.' !== (string) get_option( 'blogdescription', '' ) ) {
				throw new \RuntimeException( 'Complete99 site-description correction failed readback.' );
			}
		}
	}

	private static function upsert_seed( $blueprint, $language, $parent ) {
		$seed_key        = $blueprint['key'] . ':' . $language;
		$existing_record = self::unique_seed_record( $seed_key, true );
		$existing        = $existing_record ? (int) $existing_record['ID'] : 0;
		$title           = $blueprint['title'][ $language ];
		$excerpt         = $blueprint['excerpt'][ $language ];
		$content         = $blueprint['content'][ $language ];
		$slug            = $blueprint['slug'][ $language ];
		$status          = self::expected_seed_status( $blueprint );
		$post            = array(
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
			$hash_state   = self::direct_single_meta_state( $existing, '_complete99_seed_hash' );
			$current_hash = self::content_hash(
				array(
					'post_title'   => $existing_record['post_title'],
					'post_excerpt' => $existing_record['post_excerpt'],
					'post_content' => $existing_record['post_content'],
				)
			);
			$current_status  = (string) $existing_record['post_status'];
			$required_status = self::required_seed_status( $blueprint, $current_status );
			$post['post_status']  = $required_status;
			$audience_reset = self::requires_consumer_audience_reset( $blueprint, $existing );

			if ( $audience_reset ) {
				$post['ID'] = $existing;
				$result     = wp_update_post( wp_slash( $post ), true );
				if ( is_wp_error( $result ) || $existing !== (int) $result ) {
					return 0;
				}
				self::store_seed_meta( $existing, '_complete99_seed_hash', $new_hash );
			} elseif ( ! $hash_state['exists'] ) {
				if ( ! hash_equals( $new_hash, $current_hash ) ) {
					throw new \RuntimeException( 'A Complete99 seed is missing provenance for editor-owned content.' );
				}
				self::store_seed_meta( $existing, '_complete99_seed_hash', $new_hash );
			}
			if ( ! $audience_reset && $hash_state['exists'] ) {
				$stored_hash = (string) $hash_state['value'];
				if ( ! self::is_sha256( $stored_hash ) ) {
					throw new \RuntimeException( 'A Complete99 seed provenance hash is invalid.' );
				}
			}

			if ( ! $audience_reset ) {
				$stored_hash = $hash_state['exists'] ? (string) $hash_state['value'] : $new_hash;
				if ( hash_equals( $stored_hash, $current_hash ) ) {
					$post['ID'] = $existing;
					$result     = wp_update_post( wp_slash( $post ), true );
					if ( is_wp_error( $result ) || $existing !== (int) $result ) {
						return 0;
					}
					self::store_seed_meta( $existing, '_complete99_seed_hash', $new_hash );
				} elseif ( $required_status !== $current_status ) {
					$result = wp_update_post(
						array(
							'ID'          => $existing,
							'post_status' => $required_status,
						),
						true
					);
					if ( is_wp_error( $result ) || $existing !== (int) $result ) {
						return 0;
					}
				}
			}
			$id = $existing;
		} else {
			$id = wp_insert_post( wp_slash( $post ), true );
			if ( is_wp_error( $id ) ) {
				return 0;
			}
			self::store_seed_meta( $id, '_complete99_seed_hash', $new_hash );
		}

		self::store_seed_meta( $id, '_complete99_seed_key', $seed_key );
		self::store_seed_meta( $id, '_complete99_translation_key', $blueprint['key'] );
		self::store_seed_meta( $id, '_complete99_translation_group', $blueprint['key'] );
		self::store_seed_meta( $id, '_complete99_language', $language );
		self::store_seed_meta( $id, '_complete99_managed', true );
		self::store_seed_meta( $id, '_complete99_parent_hub', self::parent_hub_for_blueprint( $blueprint ) );
		self::store_seed_meta( $id, '_complete99_index_eligible', self::seed_index_eligible( $blueprint ) );
		self::store_seed_meta( $id, '_complete99_seed_version', self::SEED_VERSION );
		self::store_seed_meta( $id, '_complete99_verification_state', isset( $blueprint['verification'] ) ? $blueprint['verification'] : 'editorial_review' );
		if ( self::is_consumer_public_blueprint( $blueprint ) ) {
			self::store_seed_meta( $id, '_complete99_public_audience', self::PUBLIC_AUDIENCE );
		}
		if ( ! empty( $blueprint['image'] ) ) {
			self::store_seed_meta( $id, '_complete99_image_asset', sanitize_file_name( $blueprint['image'] ) );
		}
		if ( ! empty( $blueprint['recipe'] ) ) {
			self::sync_seed_recipe( $id, $blueprint['recipe'] );
		}
		return (int) $id;
	}

	private static function is_consumer_public_blueprint( $blueprint ) {
		$consumer_keys = array( 'home', 'about', 'contact', 'dishes', 'ingredients', 'traditions', 'knowledge', 'store', 'proposal', 'privacy', 'terms', 'accessibility' );
		return ! empty( $blueprint['public_route'] )
			&& 'page' === (string) ( $blueprint['type'] ?? '' )
			&& in_array( (string) ( $blueprint['key'] ?? '' ), $consumer_keys, true );
	}

	private static function requires_consumer_audience_reset( $blueprint, $post_id ) {
		return self::is_consumer_public_blueprint( $blueprint )
			&& self::PUBLIC_AUDIENCE !== (string) get_post_meta( $post_id, '_complete99_public_audience', true );
	}

	private static function store_seed_meta( $post_id, $key, $value ) {
		$post_type = get_post_type( $post_id );
		$canonical = sanitize_meta( $key, $value, 'post', $post_type ? $post_type : '' );
		update_post_meta( $post_id, $key, wp_slash( $value ) );
		$stored           = self::direct_single_meta_state( $post_id, $key );
		$stored_canonical = $stored['exists'] ? sanitize_meta( $key, $stored['value'], 'post', $post_type ? $post_type : '' ) : null;
		if ( ! $stored['exists'] || maybe_serialize( $stored_canonical ) !== maybe_serialize( $canonical ) ) {
			throw new \RuntimeException( 'Complete99 seed metadata failed readback.' );
		}
	}

	private static function sync_seed_recipe( $post_id, $blueprint_recipe ) {
		$seed_recipe       = self::sanitize_recipe( $blueprint_recipe );
		$recipe            = self::direct_single_meta_state( $post_id, '_complete99_recipe' );
		$provenance        = self::direct_single_meta_state( $post_id, '_complete99_recipe_seed_hash' );
		$stored_provenance = $provenance['exists'] ? (string) $provenance['value'] : '';
		$seed_hash         = self::recipe_hash( $seed_recipe );
		$refresh           = self::should_refresh_seed_recipe( $seed_recipe, $recipe['value'], $stored_provenance, $recipe['exists'] );

		if ( $refresh ) {
			self::store_seed_meta( $post_id, '_complete99_recipe', $seed_recipe );
		}
		$next_provenance = self::recipe_provenance_after_sync( $seed_hash, $stored_provenance, $provenance['exists'], $refresh );
		if ( ! $provenance['exists'] || ! hash_equals( $stored_provenance, $next_provenance ) ) {
			self::store_seed_meta( $post_id, '_complete99_recipe_seed_hash', $next_provenance );
		}
	}

	/**
	 * Seed recipes update only while the durable recipe still matches its prior
	 * seed provenance. Missing provenance adopts an identical seed recipe but
	 * treats any differing recipe as chef-owned, records a non-owning baseline,
	 * and preserves both later edits and the last valid provenance.
	 */
	private static function should_refresh_seed_recipe( $seed_recipe, $stored_recipe, $stored_provenance, $recipe_exists ) {
		if ( ! $recipe_exists ) {
			return true;
		}

		$current_hash = self::recipe_hash( $stored_recipe );
		$seed_hash    = self::recipe_hash( $seed_recipe );
		if ( self::is_sha256( (string) $stored_provenance ) ) {
			return hash_equals( (string) $stored_provenance, $current_hash );
		}
		return hash_equals( $seed_hash, $current_hash );
	}

	private static function recipe_provenance_after_sync( $seed_hash, $stored_provenance, $provenance_exists, $refresh ) {
		if ( $provenance_exists && ! self::is_sha256( (string) $stored_provenance ) ) {
			throw new \RuntimeException( 'A Complete99 seed recipe provenance hash is invalid.' );
		}
		if ( $refresh || ! $provenance_exists ) {
			return (string) $seed_hash;
		}
		return (string) $stored_provenance;
	}

	private static function recipe_hash( $recipe ) {
		return hash( 'sha256', maybe_serialize( self::sanitize_recipe( $recipe ) ) );
	}

	private static function is_sha256( $value ) {
		return is_string( $value ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/', $value );
	}

	private static function parent_hub_for_blueprint( $blueprint ) {
		if ( isset( $blueprint['parent_hub'] ) ) {
			return sanitize_key( (string) $blueprint['parent_hub'] );
		}
		$post_type = isset( $blueprint['type'] ) ? (string) $blueprint['type'] : '';
		return isset( self::$hub_by_post_type[ $post_type ] ) ? self::$hub_by_post_type[ $post_type ] : '';
	}

	private static function seed_index_eligible( $blueprint ) {
		if ( array_key_exists( 'index_eligible', $blueprint ) ) {
			return rest_sanitize_boolean( $blueprint['index_eligible'] );
		}
		if ( 'publish' !== self::expected_seed_status( $blueprint ) ) {
			return false;
		}
		$verification = isset( $blueprint['verification'] ) ? (string) $blueprint['verification'] : 'editorial_review';
		return in_array( $verification, array( 'editorial_review', 'verified', 'product_demo', 'launch_ready' ), true );
	}

	private static function expected_seed_status( $blueprint ) {
		$status = isset( $blueprint['status'] ) ? (string) $blueprint['status'] : 'publish';
		if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private', 'future' ), true ) ) {
			throw new \RuntimeException( 'A Complete99 seed blueprint has an invalid status.' );
		}
		return $status;
	}

	private static function required_seed_status( $blueprint, $current_status ) {
		$expected = self::expected_seed_status( $blueprint );
		$must_be_public = 'publish' === $expected && ! empty( $blueprint['public_route'] );
		if ( $must_be_public ) {
			return 'publish';
		}
		if ( 'private' === $current_status ) {
			return 'private';
		}
		if ( 'draft' === $expected && 'publish' === $current_status ) {
			return 'publish';
		}
		return $expected;
	}

	private static function allowed_seed_statuses( $blueprint ) {
		$expected = self::expected_seed_status( $blueprint );
		$must_be_public = 'publish' === $expected && ! empty( $blueprint['public_route'] );
		if ( $must_be_public ) {
			return array( 'publish' );
		}
		if ( 'private' === $expected ) {
			return array( 'private' );
		}
		if ( 'draft' === $expected ) {
			return array( 'draft', 'private', 'publish' );
		}
		return array( $expected, 'private' );
	}

	private static function direct_single_meta_state( $post_id, $key ) {
		global $wpdb;

		$wpdb->last_error = '';
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id",
				(int) $post_id,
				(string) $key
			)
		);
		if ( '' !== (string) $wpdb->last_error || ! is_array( $rows ) ) {
			throw new \RuntimeException( 'Complete99 seed metadata could not be read from durable storage.' );
		}
		if ( 1 < count( $rows ) ) {
			throw new \RuntimeException( 'Complete99 seed metadata is duplicated.' );
		}
		return array(
			'exists' => 1 === count( $rows ),
			'value'  => 1 === count( $rows ) ? maybe_unserialize( $rows[0] ) : null,
		);
	}

	private static function direct_seed_records( $seed_key ) {
		global $wpdb;

		$wpdb->last_error = '';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_type, p.post_status, p.post_password, p.post_name, p.post_parent, p.post_title, p.post_excerpt, p.post_content, pm.meta_id
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND pm.meta_value = %s
				ORDER BY p.ID, pm.meta_id",
				'_complete99_seed_key',
				sanitize_text_field( $seed_key )
			),
			ARRAY_A
		);
		if ( '' !== (string) $wpdb->last_error || ! is_array( $rows ) ) {
			throw new \RuntimeException( 'Complete99 seed identity could not be read from durable storage.' );
		}
		return $rows;
	}

	private static function unique_seed_record( $seed_key, $allow_missing = false ) {
		$rows = self::direct_seed_records( $seed_key );
		if ( empty( $rows ) && $allow_missing ) {
			return null;
		}
		if ( 1 !== count( $rows ) ) {
			throw new \RuntimeException( 'A Complete99 seed identity must have exactly one post and one key row.' );
		}
		return $rows[0];
	}

	private static function direct_option_value( $name ) {
		global $wpdb;

		$wpdb->last_error = '';
		$raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				(string) $name
			)
		);
		if ( '' !== (string) $wpdb->last_error || null === $raw ) {
			throw new \RuntimeException( 'A required Complete99 option is unavailable in durable storage.' );
		}
		return maybe_unserialize( $raw );
	}

	/**
	 * Prove the complete data model before the migration version is committed.
	 */
	public static function assert_migration_invariants() {
		$launch = require COMPLETE99_PLATFORM_DIR . 'data/launch-content.php';
		$dishes = require COMPLETE99_PLATFORM_DIR . 'data/dish-seeds.php';
		$english_home = self::unique_seed_record( 'home:en' );
		foreach ( array_merge( $launch, $dishes ) as $blueprint ) {
			foreach ( array( 'he', 'en' ) as $language ) {
				$seed_key = $blueprint['key'] . ':' . $language;
				$post     = self::unique_seed_record( $seed_key );
				$post_id  = (int) $post['ID'];
				if ( $blueprint['type'] !== $post['post_type'] ) {
					throw new \RuntimeException( 'A required Complete99 seed post is missing.' );
				}
				if ( ! in_array( (string) $post['post_status'], self::allowed_seed_statuses( $blueprint ), true ) ) {
					throw new \RuntimeException( 'A required Complete99 seed post has an invalid status.' );
				}

				$seed_hash = self::direct_single_meta_state( $post_id, '_complete99_seed_hash' );
				if ( ! $seed_hash['exists'] || ! self::is_sha256( (string) $seed_hash['value'] ) ) {
					throw new \RuntimeException( 'A required Complete99 seed provenance hash is missing.' );
				}
				$expected_content_hash = self::content_hash(
					array(
						'post_title'   => $blueprint['title'][ $language ],
						'post_excerpt' => $blueprint['excerpt'][ $language ],
						'post_content' => $blueprint['content'][ $language ],
					)
				);
				$current_content_hash = self::content_hash( $post );
				if ( hash_equals( (string) $seed_hash['value'], $current_content_hash ) ) {
					$expected_parent = ( 'en' === $language && 'page' === $blueprint['type'] && 'home' !== $blueprint['key'] )
						? (int) $english_home['ID']
						: 0;
					if ( ! hash_equals( $expected_content_hash, $current_content_hash )
						|| (string) $blueprint['slug'][ $language ] !== (string) $post['post_name']
						|| $expected_parent !== (int) $post['post_parent'] ) {
						throw new \RuntimeException( 'An unedited Complete99 seed post does not match its durable provenance.' );
					}
				}

				$expected_meta = array(
					'_complete99_seed_key'          => $seed_key,
					'_complete99_translation_key'   => $blueprint['key'],
					'_complete99_translation_group' => $blueprint['key'],
					'_complete99_language'          => $language,
					'_complete99_managed'           => true,
					'_complete99_parent_hub'        => self::parent_hub_for_blueprint( $blueprint ),
					'_complete99_index_eligible'    => self::seed_index_eligible( $blueprint ),
					'_complete99_seed_version'      => self::SEED_VERSION,
					'_complete99_verification_state'=> isset( $blueprint['verification'] ) ? $blueprint['verification'] : 'editorial_review',
				);
				if ( self::is_consumer_public_blueprint( $blueprint ) ) {
					$expected_meta['_complete99_public_audience'] = self::PUBLIC_AUDIENCE;
				}
				foreach ( $expected_meta as $key => $value ) {
					$stored           = self::direct_single_meta_state( $post_id, $key );
					$expected_value   = sanitize_meta( $key, $value, 'post', (string) $post['post_type'] );
					$stored_canonical = $stored['exists'] ? sanitize_meta( $key, $stored['value'], 'post', (string) $post['post_type'] ) : null;
					if ( ! $stored['exists'] || maybe_serialize( $expected_value ) !== maybe_serialize( $stored_canonical ) ) {
						throw new \RuntimeException( 'Required Complete99 seed metadata is missing.' );
					}
				}
				if ( ! empty( $blueprint['image'] ) ) {
					$image = self::direct_single_meta_state( $post_id, '_complete99_image_asset' );
					if ( ! $image['exists'] || sanitize_file_name( $blueprint['image'] ) !== (string) $image['value'] ) {
						throw new \RuntimeException( 'A Complete99 seed image reference is missing.' );
					}
				}
				if ( ! empty( $blueprint['recipe'] ) ) {
					$recipe              = self::direct_single_meta_state( $post_id, '_complete99_recipe' );
					$provenance          = self::direct_single_meta_state( $post_id, '_complete99_recipe_seed_hash' );
					$recipe_hash         = $recipe['exists'] ? self::recipe_hash( $recipe['value'] ) : '';
					$expected_recipe_hash = self::recipe_hash( $blueprint['recipe'] );
					if ( ! $recipe['exists']
						|| ! is_array( $recipe['value'] )
						|| maybe_serialize( self::sanitize_recipe( $recipe['value'] ) ) !== maybe_serialize( $recipe['value'] )
						|| ! $provenance['exists']
						|| ! self::is_sha256( (string) $provenance['value'] )
						|| ( hash_equals( (string) $provenance['value'], $recipe_hash )
							&& ! hash_equals( $expected_recipe_hash, $recipe_hash ) ) ) {
						throw new \RuntimeException( 'A Complete99 seed recipe or its provenance is incomplete.' );
					}
				}
			}
		}

		$front_page = (int) self::direct_option_value( 'page_on_front' );
		$home       = self::unique_seed_record( 'home:he' );
		if ( 'page' !== self::direct_option_value( 'show_on_front' )
			|| ! $front_page
			|| $front_page !== (int) $home['ID']
			|| 'page' !== (string) $home['post_type']
			|| 'publish' !== (string) $home['post_status']
			|| '' !== (string) $home['post_password'] ) {
			throw new \RuntimeException( 'The Complete99 front page is not configured.' );
		}
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

	public static function translation_group_for_post( $post_id ) {
		$group = (string) get_post_meta( (int) $post_id, '_complete99_translation_group', true );
		if ( '' === $group ) {
			$group = (string) get_post_meta( (int) $post_id, '_complete99_translation_key', true );
		}
		return sanitize_key( $group );
	}

	public static function find_translation_post_id( $translation_group, $language, $public_only = false ) {
		$translation_group = sanitize_key( (string) $translation_group );
		$language          = self::sanitize_language( (string) $language );
		if ( '' === $translation_group ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'              => array_merge( array( 'page' ), array_keys( self::$post_types ) ),
				'post_status'            => $public_only ? array( 'publish' ) : array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'posts_per_page'         => 2,
				'fields'                 => 'ids',
				'meta_query'             => array(
					'relation' => 'AND',
					array(
						'key'     => '_complete99_translation_group',
						'value'   => $translation_group,
						'compare' => '=',
					),
					array(
						'key'     => '_complete99_language',
						'value'   => $language,
						'compare' => '=',
					),
				),
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		if ( 1 === count( $ids ) ) {
			return (int) $ids[0];
		}
		if ( 1 < count( $ids ) ) {
			return 0;
		}

		$legacy = self::find_seed_post_id( $translation_group . ':' . $language );
		if ( ! $legacy ) {
			return 0;
		}
		return ! $public_only || 'publish' === get_post_status( $legacy ) ? $legacy : 0;
	}

	public static function route_url( $translation_key, $language ) {
		$id = self::find_translation_post_id( $translation_key, $language, true );
		return $id ? (string) get_permalink( $id ) : '';
	}

	public static function language_for_post( $post_id ) {
		$lang = (string) get_post_meta( $post_id, '_complete99_language', true );
		return in_array( $lang, array( 'he', 'en' ), true ) ? $lang : 'he';
	}

	public static function breadcrumb_trail( $post_id ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post || ! self::is_complete99_post( $post_id ) ) {
			return array();
		}
		$language = self::language_for_post( $post_id );
		$home_id  = self::find_translation_post_id( 'home', $language, true );
		$trail    = array();
		if ( $home_id ) {
			$trail[] = array(
				'id'      => $home_id,
				'label'   => 'he' === $language ? 'בית' : 'Home',
				'url'     => (string) get_permalink( $home_id ),
				'current' => $home_id === $post_id,
			);
		}
		if ( $home_id === $post_id ) {
			return $trail;
		}

		$ancestor_ids = array();
		$parent_id    = (int) $post->post_parent;
		$guard        = 0;
		while ( $parent_id && $parent_id !== $home_id && $guard < 8 ) {
			$parent = get_post( $parent_id );
			if ( ! $parent || ! self::is_complete99_post( $parent_id ) || self::language_for_post( $parent_id ) !== $language ) {
				break;
			}
			array_unshift( $ancestor_ids, $parent_id );
			$parent_id = (int) $parent->post_parent;
			++$guard;
		}

		if ( empty( $ancestor_ids ) ) {
			$hub_key = (string) get_post_meta( $post_id, '_complete99_parent_hub', true );
			if ( '' === $hub_key && isset( self::$hub_by_post_type[ $post->post_type ] ) ) {
				$hub_key = self::$hub_by_post_type[ $post->post_type ];
			}
			$hub_id = $hub_key ? self::find_translation_post_id( $hub_key, $language, true ) : 0;
			if ( $hub_id && $hub_id !== $post_id && $hub_id !== $home_id ) {
				$ancestor_ids[] = $hub_id;
			}
		}

		foreach ( array_values( array_unique( $ancestor_ids ) ) as $ancestor_id ) {
			$trail[] = array(
				'id'      => (int) $ancestor_id,
				'label'   => (string) get_the_title( $ancestor_id ),
				'url'     => (string) get_permalink( $ancestor_id ),
				'current' => false,
			);
		}
		$trail[] = array(
			'id'      => $post_id,
			'label'   => (string) get_the_title( $post_id ),
			'url'     => (string) get_permalink( $post_id ),
			'current' => true,
		);
		return $trail;
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
		$lines_field( 'c99_sources', 'Credible source URLs - one per line (minimum 8)', isset( $recipe['sources'] ) ? $recipe['sources'] : array(), 8 );
		$lines_field( 'c99_authoritative_sources', 'Authoritative/primary source URLs - one per line (minimum 2)', isset( $recipe['authoritative_sources'] ) ? $recipe['authoritative_sources'] : array(), 4 );
		$lines_field( 'c99_source_notes', 'Claim/source and dispute notes - one per line', isset( $recipe['source_notes'] ) ? $recipe['source_notes'] : array(), 6 );
		$lines_field( 'c99_ingredients', 'Tested public ingredients - one per line', isset( $recipe['ingredients'] ) ? $recipe['ingredients'] : array(), 8 );
		$lines_field( 'c99_instructions', 'Tested public instructions - one step per line', isset( $recipe['instructions'] ) ? $recipe['instructions'] : array(), 8 );
		$lines_field( 'c99_allergens', 'Allergen record - one item per line; use “none identified” only after review', isset( $recipe['allergens'] ) ? $recipe['allergens'] : array(), 4 );
		echo '</div><div>';
		$text_field( 'c99_yield', 'Recipe yield', isset( $recipe['yield'] ) ? $recipe['yield'] : '' );
		$text_field( 'c99_prep_minutes', 'Preparation minutes', isset( $recipe['prep_minutes'] ) ? $recipe['prep_minutes'] : '', 'number' );
		$text_field( 'c99_cook_minutes', 'Cooking minutes', isset( $recipe['cook_minutes'] ) ? $recipe['cook_minutes'] : '', 'number' );
		$lines_field( 'c99_weights', 'Tested weights - one per line', isset( $recipe['weights'] ) ? $recipe['weights'] : array(), 4 );
		$lines_field( 'c99_temperatures', 'Tested temperatures - one per line', isset( $recipe['temperatures'] ) ? $recipe['temperatures'] : array(), 4 );
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
		return rest_sanitize_boolean( get_post_meta( (int) $post_id, '_complete99_managed', true ) )
			|| '' !== (string) get_post_meta( (int) $post_id, '_complete99_seed_key', true );
	}

	public static function post_types() {
		return self::$post_types;
	}
}
