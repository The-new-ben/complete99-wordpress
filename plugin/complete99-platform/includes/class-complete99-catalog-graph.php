<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evidence-bound catalog draft materializer.
 *
 * The source registry is deliberately stricter than a normal import. A graph
 * that contains an unknown field, an invalid state, an unresolved evidence
 * source or an unsafe exposure flag is rejected before any WordPress record is
 * written. Materialized records remain private or draft and are never made
 * purchasable by this class.
 */
final class Complete99_Catalog_Graph {
	const REGISTRY_SCHEMA = 'complete99-dish-entity-tree-registry/v1';
	const DISH_SCHEMA     = 'complete99-dish-entity-tree/v1';

	const META_DISH_ID           = '_complete99_catalog_dish_id';
	const META_INGREDIENT_CODE   = '_complete99_catalog_ingredient_code';
	const META_ENTITY_ID         = '_complete99_catalog_entity_id';
	const META_ENTITY_TYPE       = '_complete99_catalog_entity_type';
	const META_GRAPH_VERSION     = '_complete99_catalog_graph_version';
	const META_ENTITY_VERSIONS   = '_complete99_catalog_entity_versions';
	const META_SOURCE_DISH_IDS   = '_complete99_catalog_source_dish_ids';
	const META_SOURCE_RECORD_IDS = '_complete99_catalog_source_record_ids';
	const META_SOURCE_VERSIONS   = '_complete99_catalog_source_versions';
	const META_NAME_HE           = '_complete99_catalog_name_he';
	const META_NAME_EN           = '_complete99_catalog_name_en';
	const META_DESCRIPTION_HE    = '_complete99_catalog_description_he';
	const META_DESCRIPTION_EN    = '_complete99_catalog_description_en';
	const META_ALIASES_HE        = '_complete99_catalog_aliases_he';
	const META_ALIASES_EN        = '_complete99_catalog_aliases_en';
	const META_MANAGED           = '_complete99_catalog_managed';
	const META_PRODUCT_CODE      = '_complete99_catalog_product_code';

	const EVALUATION_META_MANAGED         = '_complete99_evaluation_catalog_managed';
	const EVALUATION_META_PRODUCT_CODE    = '_complete99_evaluation_product_code';
	const EVALUATION_META_INGREDIENT_CODE = '_complete99_evaluation_ingredient_code';
	const EVALUATION_META_PRICE           = '_complete99_evaluation_price_ils';
	const EVALUATION_META_STOCK           = '_complete99_evaluation_stock';
	const EVALUATION_META_PRICE_SCOPE     = '_complete99_evaluation_price_scope';
	const EVALUATION_META_STOCK_SCOPE     = '_complete99_evaluation_stock_scope';
	const EVALUATION_META_SALE_STATE      = '_complete99_evaluation_sale_state';
	const EVALUATION_META_PUBLIC_SALE     = '_complete99_evaluation_public_sale_eligible';

	const PRODUCT_APPROVED  = '_complete99_store_approved';
	const STOCK_AUTHORITY   = '_complete99_stock_authority';
	const LABEL_REVIEWED    = '_complete99_product_label_reviewed';
	const RIGHTS_REVIEWED   = '_complete99_product_rights_reviewed';
	const TAX_REVIEWED      = '_complete99_product_tax_reviewed';
	const MEDIA_PUBLIC_SAFE = '_complete99_media_public_safe';
	const PRODUCT_NAME_HE   = '_complete99_product_name_he';
	const PRODUCT_NAME_EN   = '_complete99_product_name_en';
	const PRODUCT_DESC_HE   = '_complete99_product_description_he';
	const PRODUCT_DESC_EN   = '_complete99_product_description_en';

	private static $booted         = false;
	private static $registry_cache = null;

	/**
	 * Register the approved-dish refresh hook.
	 *
	 * Merely loading this file has no side effects. The owner must explicitly
	 * call boot() from the plugin bootstrap when the integration is approved.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'init', array( __CLASS__, 'register_meta' ), 9 );
		add_action( 'save_post_c99_dish', array( __CLASS__, 'refresh_drafts_for_saved_dish' ), 40, 3 );
	}

	/**
	 * Register graph bindings as private metadata.
	 */
	public static function register_meta() {
		register_post_meta(
			'c99_dish',
			self::META_DISH_ID,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_dish_id' ),
				'auth_callback'     => static function () {
					return current_user_can( 'edit_c99_dishes' );
				},
			)
		);

		$string_fields = array(
			self::META_INGREDIENT_CODE,
			self::META_ENTITY_ID,
			self::META_ENTITY_TYPE,
			self::META_GRAPH_VERSION,
			self::META_NAME_HE,
			self::META_NAME_EN,
			self::META_DESCRIPTION_HE,
			self::META_DESCRIPTION_EN,
			self::META_MANAGED,
		);
		$list_fields = array(
			self::META_ENTITY_VERSIONS,
			self::META_SOURCE_DISH_IDS,
			self::META_SOURCE_RECORD_IDS,
			self::META_SOURCE_VERSIONS,
		);
		$text_list_fields = array( self::META_ALIASES_HE, self::META_ALIASES_EN );

		foreach ( array( 'c99_ingredient', 'c99_product_plan', 'product' ) as $post_type ) {
			foreach ( $string_fields as $meta_key ) {
				$sanitizer = self::META_INGREDIENT_CODE === $meta_key || self::META_ENTITY_ID === $meta_key
					? array( __CLASS__, 'sanitize_ingredient_code' )
					: 'sanitize_text_field';
				register_post_meta(
					$post_type,
					$meta_key,
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => false,
						'sanitize_callback' => $sanitizer,
						'auth_callback'     => self::meta_auth_callback( $post_type ),
					)
				);
			}
			foreach ( $list_fields as $meta_key ) {
				register_post_meta(
					$post_type,
					$meta_key,
					array(
						'type'              => 'array',
						'single'            => true,
						'show_in_rest'      => false,
						'sanitize_callback' => array( __CLASS__, 'sanitize_identifier_list' ),
						'auth_callback'     => self::meta_auth_callback( $post_type ),
					)
				);
			}
			foreach ( $text_list_fields as $meta_key ) {
				register_post_meta(
					$post_type,
					$meta_key,
					array(
						'type'              => 'array',
						'single'            => true,
						'show_in_rest'      => false,
						'sanitize_callback' => array( __CLASS__, 'sanitize_text_list' ),
						'auth_callback'     => self::meta_auth_callback( $post_type ),
					)
				);
			}
		}
		foreach ( array( 'c99_product_plan', 'product' ) as $post_type ) {
			register_post_meta(
				$post_type,
				self::META_PRODUCT_CODE,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => array( __CLASS__, 'sanitize_product_code' ),
					'auth_callback'     => self::meta_auth_callback( $post_type ),
				)
			);
		}
	}

	/**
	 * Sanitize an exact canonical dish ID.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_dish_id( $value ) {
		return is_string( $value )
			&& strlen( $value ) <= 160
			&& 1 === preg_match( '/\Amenu-reference-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value )
				? $value
				: '';
	}

	/**
	 * Sanitize an exact evidence-bound culinary entity code.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_ingredient_code( $value ) {
		return is_string( $value )
			&& strlen( $value ) <= 160
			&& 1 === preg_match( '/\A(?:ingredient|equipment)-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value )
				? $value
				: '';
	}

	/**
	 * Sanitize an exact canonical product code.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_product_code( $value ) {
		return is_string( $value )
			&& strlen( $value ) <= 160
			&& 1 === preg_match( '/\Aproduct-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value )
				? $value
				: '';
	}

	/**
	 * Sanitize a bounded list of canonical identifiers.
	 *
	 * Invalid input fails to an empty list rather than partially accepting it.
	 *
	 * @param mixed $value Candidate list.
	 * @return array
	 */
	public static function sanitize_identifier_list( $value ) {
		if ( ! is_array( $value ) || 256 < count( $value ) || ! self::is_list( $value ) ) {
			return array();
		}
		$clean = array();
		foreach ( $value as $identifier ) {
			if ( ! is_string( $identifier )
				|| strlen( $identifier ) > 160
				|| 1 !== preg_match( '/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/', $identifier ) ) {
				return array();
			}
			$clean[] = $identifier;
		}
		$clean = array_values( array_unique( $clean ) );
		sort( $clean, SORT_STRING );
		return $clean;
	}

	/**
	 * Sanitize a bounded list of plain-text aliases.
	 *
	 * @param mixed $value Candidate list.
	 * @return array
	 */
	public static function sanitize_text_list( $value ) {
		if ( ! is_array( $value ) || 100 < count( $value ) || ! self::is_list( $value ) ) {
			return array();
		}
		$clean = array();
		foreach ( $value as $text ) {
			if ( ! is_string( $text ) || '' === trim( $text ) || self::text_length( $text ) > 500 ) {
				return array();
			}
			$sanitized = sanitize_text_field( $text );
			if ( '' === $sanitized ) {
				return array();
			}
			$clean[] = $sanitized;
		}
		$clean = array_values( array_unique( $clean ) );
		return self::unique_sorted_text( $clean );
	}

	/**
	 * Load and validate the canonical source registry.
	 *
	 * @param bool $fresh Bypass the request-local cache.
	 * @return array|WP_Error
	 */
	public static function load_registry( $fresh = false ) {
		if ( ! $fresh && is_array( self::$registry_cache ) ) {
			return self::$registry_cache;
		}

		$path = dirname( __DIR__ ) . '/data/dish-entity-trees.php';
		if ( ! is_readable( $path ) ) {
			return self::error( 'complete99_catalog_graph_missing', 'The dish entity registry is not readable.' );
		}

		$registry   = require $path;
		$validation = self::validate_registry( $registry );
		if ( self::is_error( $validation ) ) {
			return $validation;
		}

		self::$registry_cache = $registry;
		return self::$registry_cache;
	}

	/**
	 * Strictly validate the complete v1 registry.
	 *
	 * @param mixed $registry Candidate registry.
	 * @return true|WP_Error
	 */
	public static function validate_registry( $registry ) {
		try {
			self::assert_exact_keys(
				$registry,
				array( 'schema', 'registry_reviewed_at', 'allowed_states', 'source_registry', 'dishes' ),
				'registry'
			);
			self::assert_same( self::REGISTRY_SCHEMA, $registry['schema'], 'registry.schema' );
			self::assert_date( $registry['registry_reviewed_at'], 'registry.registry_reviewed_at' );
			self::assert_exact_list(
				$registry['allowed_states'],
				self::allergen_states(),
				'registry.allowed_states'
			);
			self::assert_associative_array( $registry['source_registry'], 'registry.source_registry', false );
			self::assert_list( $registry['dishes'], 'registry.dishes', false );
			if ( 50 < count( $registry['source_registry'] ) || 500 < count( $registry['dishes'] ) ) {
				throw new \UnexpectedValueException( 'registry exceeds the bounded v1 source or dish count.' );
			}

			$sources = array();
			foreach ( $registry['source_registry'] as $source_id => $source ) {
				self::assert_identifier( $source_id, 'registry.source_registry key' );
				self::validate_source( $source_id, $source, 'registry.source_registry.' . $source_id );
				$sources[ $source_id ] = $source;
				if ( $source['source_date'] > $registry['registry_reviewed_at'] ) {
					throw new \UnexpectedValueException( 'registry.registry_reviewed_at predates a registered source.' );
				}
			}

			$dish_ids = array();
			$slugs    = array();
			foreach ( $registry['dishes'] as $index => $dish ) {
				$path = 'registry.dishes.' . $index;
				self::validate_dish( $dish, $path, $sources, $registry['allowed_states'] );
				if ( isset( $dish_ids[ $dish['dish_id'] ] ) ) {
					throw new \UnexpectedValueException( $path . '.dish_id is duplicated.' );
				}
				if ( isset( $slugs[ $dish['source_record_slug'] ] ) ) {
					throw new \UnexpectedValueException( $path . '.source_record_slug is duplicated.' );
				}
				$dish_ids[ $dish['dish_id'] ]             = true;
				$slugs[ $dish['source_record_slug'] ]     = true;
			}
		} catch ( \Throwable $error ) {
			return self::error(
				'complete99_catalog_graph_invalid',
				'The dish entity registry failed closed: ' . $error->getMessage()
			);
		}

		return true;
	}

	/**
	 * Return the canonical bilingual component registry.
	 *
	 * Only evidence-bound component_tree nodes are materialized. Relation tokens
	 * without a bilingual component record remain relationships, not invented
	 * ingredient records.
	 *
	 * @param array $dish_ids Optional canonical dish IDs to select.
	 * @return array|WP_Error
	 */
	public static function entity_registry( $dish_ids = array() ) {
		$registry = self::load_registry();
		if ( self::is_error( $registry ) ) {
			return $registry;
		}

		$selection = self::validated_dish_selection( $dish_ids, $registry['dishes'] );
		if ( self::is_error( $selection ) ) {
			return $selection;
		}

		$entities = array();
		try {
			foreach ( $registry['dishes'] as $dish ) {
				if ( ! isset( $selection[ $dish['dish_id'] ] ) ) {
					continue;
				}
				self::collect_component_entities( $dish['component_tree']['children'], $dish, $entities );
			}

			foreach ( $entities as $entity_id => &$entity ) {
				foreach ( array( 'he', 'en' ) as $language ) {
					$entity['aliases'][ $language ] = self::unique_sorted_text( $entity['aliases'][ $language ] );
					$entity['descriptions'][ $language ] = self::unique_sorted_text( $entity['descriptions'][ $language ] );
					$entity['name'][ $language ] = self::canonical_text( $entity['aliases'][ $language ] );
					$entity['description'][ $language ] = self::canonical_text( $entity['descriptions'][ $language ] );
				}
				foreach ( array( 'entity_types', 'source_dish_ids', 'source_record_ids', 'source_versions', 'entity_versions' ) as $field ) {
					$entity[ $field ] = array_values( array_unique( $entity[ $field ] ) );
					sort( $entity[ $field ], SORT_STRING );
				}
				$entity['entity_type'] = self::canonical_text( self::unique_sorted_text( $entity['entity_types'] ) );
				ksort( $entity['source_evidence'], SORT_STRING );
				if ( '' === $entity['name']['he']
					|| '' === $entity['name']['en']
					|| '' === $entity['description']['he']
					|| '' === $entity['description']['en'] ) {
					return self::error(
						'complete99_catalog_entity_incomplete',
						'Canonical entity ' . $entity_id . ' is missing evidence-bound bilingual copy.'
					);
				}
			}
			unset( $entity );
		} catch ( \Throwable $error ) {
			return self::error(
				'complete99_catalog_entity_registry_invalid',
				'The canonical entity projection failed closed: ' . $error->getMessage()
			);
		}
		ksort( $entities, SORT_STRING );

		return array(
			'schema'        => 'complete99-catalog-entity-registry/v1',
			'graph_version' => $registry['schema'],
			'entities'      => $entities,
		);
	}

	/**
	 * Idempotently create or refresh held catalog records.
	 *
	 * @param array $dish_ids Optional canonical dish IDs to select.
	 * @return array|WP_Error
	 */
	public static function materialize_drafts( $dish_ids = array() ) {
		$entity_registry = self::entity_registry( $dish_ids );
		if ( self::is_error( $entity_registry ) ) {
			return $entity_registry;
		}
		if ( ! post_type_exists( 'c99_ingredient' ) || ! post_type_exists( 'c99_product_plan' ) ) {
			return self::error(
				'complete99_catalog_post_types_unavailable',
				'Catalog drafts require the registered c99_ingredient and c99_product_plan post types.'
			);
		}

		$woo_active = self::woocommerce_active();
		if ( $woo_active && ! post_type_exists( 'product' ) ) {
			return self::error(
				'complete99_catalog_woo_product_type_unavailable',
				'WooCommerce is active but its product post type is unavailable.'
			);
		}

		$ingredient_entities = array_filter(
			$entity_registry['entities'],
			static function ( $entity ) {
				return 0 === strpos( (string) $entity['entity_id'], 'ingredient-' );
			}
		);
		if ( empty( $ingredient_entities ) ) {
			return self::error(
				'complete99_catalog_no_evidence_bound_ingredients',
				'The selected graph contains no bilingual evidence-bound ingredient entities.'
			);
		}

		$existing = self::preflight_existing_records( array_keys( $ingredient_entities ), $woo_active );
		if ( self::is_error( $existing ) ) {
			return $existing;
		}

		$result = array(
			'graph_version'           => $entity_registry['graph_version'],
			'source_entity_count'     => count( $entity_registry['entities'] ),
			'ingredient_entity_count' => count( $ingredient_entities ),
			'ingredient_posts'        => array(),
			'product_plan_posts'      => array(),
			'woo_products'            => array(),
			'woocommerce_materialized'=> $woo_active,
		);

		foreach ( $ingredient_entities as $entity_id => $entity ) {
			$ingredient_id = self::upsert_private_record(
				'c99_ingredient',
				$entity,
				$existing['c99_ingredient'][ $entity_id ]
			);
			if ( self::is_error( $ingredient_id ) ) {
				return $ingredient_id;
			}
			$result['ingredient_posts'][ $entity_id ] = $ingredient_id;

			$plan_id = self::upsert_product_plan(
				$entity,
				$existing['c99_product_plan'][ $entity_id ]
			);
			if ( self::is_error( $plan_id ) ) {
				return $plan_id;
			}
			$result['product_plan_posts'][ $entity_id ] = $plan_id;

			$existing_product_id = $existing['product'][ $entity_id ] ?? 0;
			$evaluation_plan     = self::is_evaluation_managed( $plan_id );
			if ( $woo_active && ( $existing_product_id || ! $evaluation_plan ) ) {
				$product_id = self::upsert_woo_product(
					$entity,
					$existing_product_id
				);
				if ( self::is_error( $product_id ) ) {
					return $product_id;
				}
				$result['woo_products'][ $entity_id ] = $product_id;
			}
		}

		return $result;
	}

	/**
	 * Refresh drafts after an independently approved c99_dish save.
	 *
	 * This hook remains inert unless boot() has been called. Publication alone
	 * is insufficient: the existing Complete99 dish gate must also pass.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Saved post.
	 * @param bool    $update  Whether the save updated an existing post.
	 * @return array|WP_Error|null
	 */
	public static function refresh_drafts_for_saved_dish( $post_id, $post, $update ) {
		unset( $update );
		$post_id = absint( $post_id );
		if ( ! $post_id
			|| ! is_object( $post )
			|| 'c99_dish' !== (string) $post->post_type
			|| 'publish' !== (string) $post->post_status
			|| ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) )
			|| ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) ) {
			return null;
		}
		if ( ! class_exists( 'Complete99_Content' )
			|| ! is_callable( array( 'Complete99_Content', 'dish_gate_status' ) ) ) {
			return self::error(
				'complete99_catalog_dish_gate_unavailable',
				'Catalog drafts were not refreshed because the dish approval gate is unavailable.'
			);
		}

		$gate = Complete99_Content::dish_gate_status( $post_id );
		if ( ! is_array( $gate ) || empty( $gate['passed'] ) ) {
			return null;
		}

		$dish_id = self::dish_id_for_saved_post( $post_id, $post );
		if ( self::is_error( $dish_id ) ) {
			return $dish_id;
		}
		return self::materialize_drafts( array( $dish_id ) );
	}

	/**
	 * Report whether a complete, active WooCommerce runtime is available.
	 *
	 * @return bool
	 */
	public static function woocommerce_active() {
		return class_exists( 'WooCommerce' )
			&& class_exists( 'WC_Product_Simple' )
			&& function_exists( 'wc_get_product' );
	}

	private static function validate_source( $source_id, $source, $path ) {
		self::assert_exact_keys( $source, array( 'type', 'provider', 'url', 'source_date' ), $path );
		self::assert_enum(
			$source['type'],
			array( 'verified_external_menu', 'complete99_archive_image' ),
			$path . '.type'
		);
		self::assert_identifier( $source['provider'], $path . '.provider' );
		self::validate_bilingual_urls( $source['url'], $path . '.url' );
		self::assert_date( $source['source_date'], $path . '.source_date' );
		if ( 'complete99_archive_image' === $source['type'] && 'complete99_archive' !== $source['provider'] ) {
			throw new \UnexpectedValueException( $path . ' has an invalid archive provider.' );
		}
		if ( 'verified_external_menu' === $source['type'] && 'wolt' !== $source['provider'] ) {
			throw new \UnexpectedValueException( $path . ' has an unapproved external menu provider.' );
		}
		self::assert_identifier( $source_id, $path . ' key' );
	}

	private static function validate_dish( $dish, $path, $sources, $allowed_states ) {
		self::assert_exact_keys(
			$dish,
			array(
				'dish_id',
				'entity_version',
				'schema_version',
				'source_record_id',
				'source_record_slug',
				'source_version',
				'identity',
				'component_tree',
				'serving_formats',
				'preparation',
				'allergen_information',
				'nutrition',
				'claim_gate',
				'relations',
				'review',
				'exposure',
			),
			$path
		);
		self::assert_dish_id( $dish['dish_id'], $path . '.dish_id' );
		self::assert_semver( $dish['entity_version'], $path . '.entity_version' );
		self::assert_same( self::DISH_SCHEMA, $dish['schema_version'], $path . '.schema_version' );
		self::assert_same( $dish['dish_id'], $dish['source_record_id'], $path . '.source_record_id' );
		self::assert_slug( $dish['source_record_slug'], $path . '.source_record_slug' );
		self::assert_identifier( $dish['source_version'], $path . '.source_version' );

		$source_id = self::validate_identity( $dish['identity'], $path . '.identity', $sources );
		self::assert_same( $dish['source_record_slug'], $dish['identity']['slug'], $path . '.identity.slug' );
		$component_codes = self::validate_component_tree(
			$dish['component_tree'],
			$path . '.component_tree',
			$sources,
			$source_id,
			$dish['source_record_slug']
		);
		self::validate_serving_formats( $dish['serving_formats'], $path . '.serving_formats', $sources, $source_id );
		self::validate_preparation( $dish['preparation'], $path . '.preparation', $sources, $source_id );
		self::validate_allergen_information(
			$dish['allergen_information'],
			$path . '.allergen_information',
			$sources,
			$source_id,
			$allowed_states,
			$component_codes
		);
		self::validate_nutrition( $dish['nutrition'], $path . '.nutrition' );
		self::validate_claim_gate( $dish['claim_gate'], $path . '.claim_gate' );
		self::validate_relations( $dish['relations'], $path . '.relations' );
		self::validate_review( $dish['review'], $path . '.review' );
		self::assert_same(
			$sources[ $source_id ]['source_date'],
			$dish['review']['last_source_reviewed'],
			$path . '.review.last_source_reviewed'
		);
		self::validate_exposure( $dish['exposure'], $path . '.exposure', $dish['preparation'] );
	}

	private static function validate_identity( $identity, $path, $sources ) {
		self::assert_exact_keys(
			$identity,
			array( 'slug', 'name', 'category', 'description', 'editorial_tagline', 'evidence' ),
			$path
		);
		self::assert_slug( $identity['slug'], $path . '.slug' );
		self::validate_bilingual_text( $identity['name'], $path . '.name', false, 240 );
		self::validate_bilingual_text( $identity['category'], $path . '.category', false, 240 );
		self::validate_bilingual_text( $identity['description'], $path . '.description', false, 2000 );
		self::validate_bilingual_text( $identity['editorial_tagline'], $path . '.editorial_tagline', false, 500 );
		$source_id = self::validate_evidence_record( $identity['evidence'], $path . '.evidence', $sources );
		foreach ( array( 'he', 'en' ) as $language ) {
			self::assert_same(
				$identity['name'][ $language ],
				$identity['evidence']['statement'][ $language ],
				$path . '.evidence.statement.' . $language
			);
		}
		return $source_id;
	}

	private static function validate_component_tree( $tree, $path, $sources, $source_id, $slug ) {
		self::assert_exact_keys(
			$tree,
			array(
				'root_code',
				'root_type',
				'children',
				'completeness_status',
				'unlisted_components',
				'quantities_status',
				'substitutions_status',
				'production_spec_status',
			),
			$path
		);
		self::assert_same( 'dish:' . $slug, $tree['root_code'], $path . '.root_code' );
		self::assert_same( 'dish', $tree['root_type'], $path . '.root_type' );
		self::assert_same( 'menu_components_only', $tree['completeness_status'], $path . '.completeness_status' );
		self::assert_same( 'unknown', $tree['unlisted_components'], $path . '.unlisted_components' );
		self::assert_same( 'unknown', $tree['quantities_status'], $path . '.quantities_status' );
		self::assert_same( 'unknown', $tree['substitutions_status'], $path . '.substitutions_status' );
		self::assert_same( 'not_in_public_record', $tree['production_spec_status'], $path . '.production_spec_status' );
		self::assert_list( $tree['children'], $path . '.children', false );
		if ( 200 < count( $tree['children'] ) ) {
			throw new \UnexpectedValueException( $path . '.children exceeds the bounded v1 component count.' );
		}

		$codes = array();
		foreach ( $tree['children'] as $index => $component ) {
			self::validate_component(
				$component,
				$path . '.children.' . $index,
				$sources,
				$source_id,
				$codes,
				1
			);
		}
		return array_keys( $codes );
	}

	private static function validate_component( $component, $path, $sources, $source_id, &$codes, $depth ) {
		if ( 12 < $depth || 2000 < count( $codes ) ) {
			throw new \UnexpectedValueException( $path . ' exceeds the bounded v1 component depth or count.' );
		}
		self::assert_exact_keys(
			$component,
			array( 'code', 'type', 'label', 'quantity_status', 'subcomponents_status', 'children', 'evidence' ),
			$path
		);
		self::assert_entity_id( $component['code'], $path . '.code' );
		if ( isset( $codes[ $component['code'] ] ) ) {
			throw new \UnexpectedValueException( $path . '.code is duplicated within the dish tree.' );
		}
		$codes[ $component['code'] ] = true;
		self::assert_enum(
			$component['type'],
			array(
				'ingredient',
				'culinary_component',
				'ingredient_or_sauce',
				'condiment',
				'sauce',
				'side',
				'archive_named_component',
				'archive_visible_component',
			),
			$path . '.type'
		);
		self::validate_bilingual_text( $component['label'], $path . '.label', false, 240 );
		self::assert_same( 'unknown', $component['quantity_status'], $path . '.quantity_status' );
		self::assert_same( 'unknown', $component['subcomponents_status'], $path . '.subcomponents_status' );
		self::assert_list( $component['children'], $path . '.children', true );
		self::validate_evidence_record( $component['evidence'], $path . '.evidence', $sources, $source_id );
		foreach ( array( 'he', 'en' ) as $language ) {
			self::assert_same(
				$component['label'][ $language ],
				$component['evidence']['statement'][ $language ],
				$path . '.evidence.statement.' . $language
			);
		}
		foreach ( $component['children'] as $index => $child ) {
			self::validate_component( $child, $path . '.children.' . $index, $sources, $source_id, $codes, $depth + 1 );
		}
	}

	private static function validate_serving_formats( $formats, $path, $sources, $source_id ) {
		self::assert_list( $formats, $path, true );
		if ( 50 < count( $formats ) ) {
			throw new \UnexpectedValueException( $path . ' exceeds the bounded v1 serving-format count.' );
		}
		$codes = array();
		foreach ( $formats as $index => $format ) {
			$item_path = $path . '.' . $index;
			self::assert_exact_keys( $format, array( 'code', 'label', 'evidence', 'option_gate' ), $item_path );
			self::assert_identifier( $format['code'], $item_path . '.code' );
			if ( isset( $codes[ $format['code'] ] ) ) {
				throw new \UnexpectedValueException( $item_path . '.code is duplicated.' );
			}
			$codes[ $format['code'] ] = true;
			self::validate_bilingual_text( $format['label'], $item_path . '.label', false, 240 );
			self::validate_evidence_record( $format['evidence'], $item_path . '.evidence', $sources, $source_id );
			foreach ( array( 'he', 'en' ) as $language ) {
				self::assert_same(
					$format['label'][ $language ],
					$format['evidence']['statement'][ $language ],
					$item_path . '.evidence.statement.' . $language
				);
			}
			self::assert_same( 'provider_check', $format['option_gate'], $item_path . '.option_gate' );
		}
	}

	private static function validate_preparation( $preparation, $path, $sources, $source_id ) {
		self::assert_exact_keys( $preparation, array( 'state', 'method', 'label', 'evidence' ), $path );
		self::assert_enum( $preparation['state'], array( 'unknown', 'stated' ), $path . '.state' );
		self::assert_list( $preparation['evidence'], $path . '.evidence', true );

		if ( 'unknown' === $preparation['state'] ) {
			if ( null !== $preparation['method'] ) {
				throw new \UnexpectedValueException( $path . '.method must be null when preparation is unknown.' );
			}
			self::validate_bilingual_text( $preparation['label'], $path . '.label', true, 240 );
			if ( '' !== $preparation['label']['he'] || '' !== $preparation['label']['en'] || ! empty( $preparation['evidence'] ) ) {
				throw new \UnexpectedValueException( $path . ' exposes unsupported preparation facts.' );
			}
			return;
		}

		self::assert_identifier( $preparation['method'], $path . '.method' );
		self::validate_bilingual_text( $preparation['label'], $path . '.label', false, 240 );
		if ( empty( $preparation['evidence'] ) ) {
			throw new \UnexpectedValueException( $path . '.evidence is required for a stated method.' );
		}
		foreach ( $preparation['evidence'] as $index => $evidence ) {
			self::validate_evidence_record( $evidence, $path . '.evidence.' . $index, $sources, $source_id );
			foreach ( array( 'he', 'en' ) as $language ) {
				self::assert_same(
					$preparation['label'][ $language ],
					$evidence['statement'][ $language ],
					$path . '.evidence.' . $index . '.statement.' . $language
				);
			}
		}
	}

	private static function validate_allergen_information( $information, $path, $sources, $source_id, $allowed_states, $component_codes ) {
		self::assert_exact_keys( $information, array( 'allowed_states', 'allergens', 'fava_g6pd', 'map_status' ), $path );
		self::assert_exact_list( $information['allowed_states'], $allowed_states, $path . '.allowed_states' );
		self::assert_exact_keys( $information['allergens'], self::allergen_codes(), $path . '.allergens' );
		$component_lookup = array_fill_keys( $component_codes, true );

		foreach ( self::allergen_codes() as $allergen_code ) {
			$item_path = $path . '.allergens.' . $allergen_code;
			$record    = $information['allergens'][ $allergen_code ];
			self::assert_exact_keys(
				$record,
				array( 'state', 'evidence_component_codes', 'evidence', 'public_claim_allowed', 'review_status' ),
				$item_path
			);
			self::assert_enum( $record['state'], $allowed_states, $item_path . '.state' );
			self::assert_identifier_list( $record['evidence_component_codes'], $item_path . '.evidence_component_codes' );
			foreach ( $record['evidence_component_codes'] as $component_code ) {
				if ( ! isset( $component_lookup[ $component_code ] ) ) {
					throw new \UnexpectedValueException( $item_path . ' references an unknown component code.' );
				}
			}
			self::assert_list( $record['evidence'], $item_path . '.evidence', true );
			if ( 50 < count( $record['evidence'] ) ) {
				throw new \UnexpectedValueException( $item_path . '.evidence exceeds the bounded v1 count.' );
			}
			foreach ( $record['evidence'] as $evidence_index => $evidence ) {
				self::validate_evidence_record(
					$evidence,
					$item_path . '.evidence.' . $evidence_index,
					$sources,
					$source_id
				);
			}
			self::assert_boolean( $record['public_claim_allowed'], $item_path . '.public_claim_allowed' );
			self::validate_allergen_state_contract( $record, $item_path );
		}

		$fava_path = $path . '.fava_g6pd';
		$fava      = $information['fava_g6pd'];
		self::assert_exact_keys( $fava, array( 'state', 'evidence', 'public_claim_allowed', 'review_status' ), $fava_path );
		self::assert_enum( $fava['state'], $allowed_states, $fava_path . '.state' );
		self::assert_list( $fava['evidence'], $fava_path . '.evidence', true );
		if ( 50 < count( $fava['evidence'] ) ) {
			throw new \UnexpectedValueException( $fava_path . '.evidence exceeds the bounded v1 count.' );
		}
		foreach ( $fava['evidence'] as $evidence_index => $evidence ) {
			self::validate_evidence_record( $evidence, $fava_path . '.evidence.' . $evidence_index, $sources, $source_id );
		}
		self::assert_boolean( $fava['public_claim_allowed'], $fava_path . '.public_claim_allowed' );
		self::validate_allergen_state_contract( $fava, $fava_path );
		self::assert_enum(
			$information['map_status'],
			array( 'incomplete_pending_recipe_supplier_and_cross_contact_review', 'reviewed' ),
			$path . '.map_status'
		);
	}

	private static function validate_allergen_state_contract( $record, $path ) {
		self::assert_enum(
			$record['review_status'],
			array(
				'not_reviewed',
				'explicit_menu_fact_pending_food_safety_review',
				'pending_food_safety_review',
				'food_safety_reviewed',
			),
			$path . '.review_status'
		);
		$codes = isset( $record['evidence_component_codes'] ) ? $record['evidence_component_codes'] : array();
		if ( 'unknown' === $record['state'] ) {
			if ( ! empty( $codes )
				|| ! empty( $record['evidence'] )
				|| $record['public_claim_allowed']
				|| 'not_reviewed' !== $record['review_status'] ) {
				throw new \UnexpectedValueException( $path . ' exposes evidence or a claim for an unknown state.' );
			}
			return;
		}
		if ( empty( $record['evidence'] ) ) {
			throw new \UnexpectedValueException( $path . '.evidence is required for a non-unknown state.' );
		}
		if ( 'contains' === $record['state']
			&& isset( $record['evidence_component_codes'] )
			&& empty( $record['evidence_component_codes'] ) ) {
			throw new \UnexpectedValueException( $path . '.evidence_component_codes is required for contains.' );
		}
		if ( $record['public_claim_allowed'] && 'food_safety_reviewed' !== $record['review_status'] ) {
			throw new \UnexpectedValueException( $path . ' allows a public claim without food-safety review.' );
		}
		if ( 'verified_free_from' === $record['state']
			&& 'food_safety_reviewed' !== $record['review_status'] ) {
			throw new \UnexpectedValueException( $path . ' marks verified free-from without completed food-safety review.' );
		}
		if ( 'contains' === $record['state']
			&& ! in_array(
				$record['review_status'],
				array( 'explicit_menu_fact_pending_food_safety_review', 'food_safety_reviewed' ),
				true
			) ) {
			throw new \UnexpectedValueException( $path . ' has an invalid review state for contains.' );
		}
	}

	private static function validate_nutrition( $nutrition, $path ) {
		self::assert_exact_keys(
			$nutrition,
			array( 'method', 'basis', 'values', 'portion_weight_status', 'review_status', 'public_exposure' ),
			$path
		);
		self::assert_same( 'unknown', $nutrition['method'], $path . '.method' );
		self::assert_same( 'unknown', $nutrition['basis'], $path . '.basis' );
		self::assert_list( $nutrition['values'], $path . '.values', true );
		if ( ! empty( $nutrition['values'] ) ) {
			throw new \UnexpectedValueException( $path . '.values must remain empty in the v1 unknown-nutrition graph.' );
		}
		self::assert_same( 'unknown', $nutrition['portion_weight_status'], $path . '.portion_weight_status' );
		self::assert_same( 'not_calculated', $nutrition['review_status'], $path . '.review_status' );
		self::assert_same( false, $nutrition['public_exposure'], $path . '.public_exposure' );
	}

	private static function validate_claim_gate( $gate, $path ) {
		self::assert_exact_keys(
			$gate,
			array( 'derived_claims_status', 'publish_by_default', 'menu_fact_scope', 'held_claims' ),
			$path
		);
		self::assert_same( 'held', $gate['derived_claims_status'], $path . '.derived_claims_status' );
		self::assert_same( false, $gate['publish_by_default'], $path . '.publish_by_default' );
		self::assert_exact_list(
			$gate['menu_fact_scope'],
			array(
				'identity',
				'explicit_menu_components',
				'explicit_serving_formats',
				'explicit_preparation_method',
			),
			$path . '.menu_fact_scope'
		);
		self::assert_exact_list(
			$gate['held_claims'],
			array(
				'complete_recipe',
				'allergen_free',
				'cross_contact_safety',
				'nutrition_values',
				'nutrition_claims',
				'health_suitability',
				'medical_suitability',
				'certification',
				'price',
				'stock',
				'current_availability',
				'connector_specific_availability',
			),
			$path . '.held_claims'
		);
	}

	private static function validate_relations( $relations, $path ) {
		self::assert_exact_keys(
			$relations,
			array( 'guide_codes', 'ingredient_codes', 'product_codes', 'product_status', 'connectors' ),
			$path
		);
		self::assert_identifier_list( $relations['guide_codes'], $path . '.guide_codes' );
		self::assert_identifier_list( $relations['ingredient_codes'], $path . '.ingredient_codes' );
		self::assert_identifier_list( $relations['product_codes'], $path . '.product_codes' );
		if ( ! empty( $relations['product_codes'] ) ) {
			throw new \UnexpectedValueException( $path . '.product_codes must remain empty until a product relation is verified.' );
		}
		self::assert_same( 'no_verified_product_relation', $relations['product_status'], $path . '.product_status' );
		self::assert_exact_keys( $relations['connectors'], array( 'wolt', 'tenbis', 'cibus', 'spareeat' ), $path . '.connectors' );

		foreach ( $relations['connectors'] as $connector => $relation ) {
			$item_path = $path . '.connectors.' . $connector;
			self::assert_exact_keys(
				$relation,
				array( 'relation', 'public_exposure', 'dish_deep_link_verified', 'dish_availability_status' ),
				$item_path
			);
			self::assert_enum(
				$relation['relation'],
				array( 'restaurant_menu_reference', 'dish_relation_not_verified', 'future_connector' ),
				$item_path . '.relation'
			);
			self::assert_boolean( $relation['public_exposure'], $item_path . '.public_exposure' );
			self::assert_same( false, $relation['dish_deep_link_verified'], $item_path . '.dish_deep_link_verified' );
			self::assert_enum(
				$relation['dish_availability_status'],
				array( 'provider_check', 'unknown' ),
				$item_path . '.dish_availability_status'
			);
			if ( 'future_connector' === $relation['relation']
				&& ( $relation['public_exposure'] || 'unknown' !== $relation['dish_availability_status'] ) ) {
				throw new \UnexpectedValueException( $item_path . ' exposes a future connector.' );
			}
			if ( 'restaurant_menu_reference' === $relation['relation']
				&& ( 'wolt' !== $connector
					|| ! $relation['public_exposure']
					|| 'provider_check' !== $relation['dish_availability_status'] ) ) {
				throw new \UnexpectedValueException( $item_path . ' has an invalid restaurant-menu reference.' );
			}
			if ( 'dish_relation_not_verified' === $relation['relation'] && $relation['public_exposure'] ) {
				throw new \UnexpectedValueException( $item_path . ' exposes an unverified dish relation.' );
			}
		}
	}

	private static function validate_review( $review, $path ) {
		self::assert_exact_keys( $review, array( 'status', 'owners', 'requirements', 'last_source_reviewed' ), $path );
		self::assert_same( 'source_seeded_pending_multidisciplinary_review', $review['status'], $path . '.status' );
		self::assert_exact_keys(
			$review['owners'],
			array( 'culinary', 'food_safety', 'nutrition', 'israeli_food_law', 'hebrew_editor', 'english_editor' ),
			$path . '.owners'
		);
		foreach ( $review['owners'] as $owner => $value ) {
			self::assert_owner( $value, $path . '.owners.' . $owner );
		}
		self::assert_exact_keys(
			$review['requirements'],
			array(
				'recipe_version',
				'ingredient_specifications',
				'supplier_declarations',
				'cross_contact_review',
				'nutrition_calculation',
				'food_safety_signoff',
				'dietitian_signoff',
				'legal_claim_review',
				'hebrew_editorial_review',
				'english_editorial_review',
			),
			$path . '.requirements'
		);
		foreach ( $review['requirements'] as $requirement => $value ) {
			self::assert_boolean( $value, $path . '.requirements.' . $requirement );
		}
		self::assert_date( $review['last_source_reviewed'], $path . '.last_source_reviewed' );
	}

	private static function validate_exposure( $exposure, $path, $preparation ) {
		self::assert_exact_keys( $exposure, array( 'public', 'private' ), $path );
		self::assert_exact_keys(
			$exposure['public'],
			array(
				'identity',
				'menu_stated_components',
				'menu_stated_formats',
				'menu_stated_preparation',
				'allergen_map',
				'nutrition',
				'health_or_medical_claims',
				'price',
				'stock',
				'current_availability',
			),
			$path . '.public'
		);
		foreach ( $exposure['public'] as $key => $value ) {
			self::assert_boolean( $value, $path . '.public.' . $key );
		}
		self::assert_same( true, $exposure['public']['identity'], $path . '.public.identity' );
		self::assert_same( true, $exposure['public']['menu_stated_components'], $path . '.public.menu_stated_components' );
		self::assert_same( true, $exposure['public']['menu_stated_formats'], $path . '.public.menu_stated_formats' );
		self::assert_same(
			'stated' === $preparation['state'],
			$exposure['public']['menu_stated_preparation'],
			$path . '.public.menu_stated_preparation'
		);
		foreach ( array( 'allergen_map', 'nutrition', 'health_or_medical_claims', 'price', 'stock', 'current_availability' ) as $held ) {
			self::assert_same( false, $exposure['public'][ $held ], $path . '.public.' . $held );
		}

		self::assert_exact_keys(
			$exposure['private'],
			array(
				'evidence_records',
				'review_workflow',
				'unverified_safety_fields',
				'operational_recipe',
				'supplier_data',
				'cost_data',
				'staff_or_customer_data',
			),
			$path . '.private'
		);
		foreach ( $exposure['private'] as $key => $value ) {
			self::assert_boolean( $value, $path . '.private.' . $key );
		}
		foreach ( array( 'evidence_records', 'review_workflow', 'unverified_safety_fields' ) as $private_true ) {
			self::assert_same( true, $exposure['private'][ $private_true ], $path . '.private.' . $private_true );
		}
		foreach ( array( 'operational_recipe', 'supplier_data', 'cost_data', 'staff_or_customer_data' ) as $private_false ) {
			self::assert_same( false, $exposure['private'][ $private_false ], $path . '.private.' . $private_false );
		}
	}

	private static function validate_evidence_record( $evidence, $path, $sources, $expected_source_id = '' ) {
		self::assert_exact_keys(
			$evidence,
			array( 'source_id', 'source_type', 'source_url', 'source_date', 'statement', 'evidence_mode' ),
			$path
		);
		self::assert_identifier( $evidence['source_id'], $path . '.source_id' );
		if ( ! isset( $sources[ $evidence['source_id'] ] ) ) {
			throw new \UnexpectedValueException( $path . ' references an unregistered source.' );
		}
		if ( '' !== $expected_source_id ) {
			self::assert_same( $expected_source_id, $evidence['source_id'], $path . '.source_id' );
		}
		$source = $sources[ $evidence['source_id'] ];
		self::assert_same( $source['type'], $evidence['source_type'], $path . '.source_type' );
		self::validate_bilingual_urls( $evidence['source_url'], $path . '.source_url' );
		foreach ( array( 'he', 'en' ) as $language ) {
			self::assert_same(
				$source['url'][ $language ],
				$evidence['source_url'][ $language ],
				$path . '.source_url.' . $language
			);
		}
		self::assert_same( $source['source_date'], $evidence['source_date'], $path . '.source_date' );
		self::validate_bilingual_text( $evidence['statement'], $path . '.statement', false, 2000 );
		$expected_mode = 'complete99_archive_image' === $source['type'] ? 'archive_stated' : 'menu_stated';
		self::assert_same( $expected_mode, $evidence['evidence_mode'], $path . '.evidence_mode' );
		return $evidence['source_id'];
	}

	private static function validated_dish_selection( $dish_ids, $dishes ) {
		if ( ! is_array( $dish_ids ) || 500 < count( $dish_ids ) ) {
			return self::error( 'complete99_catalog_selection_invalid', 'Dish selection must be an array of canonical IDs.' );
		}
		$available = array();
		foreach ( $dishes as $dish ) {
			$available[ $dish['dish_id'] ] = true;
		}
		if ( empty( $dish_ids ) ) {
			return $available;
		}
		$selection = array();
		foreach ( $dish_ids as $dish_id ) {
			if ( ! is_string( $dish_id )
				|| ! preg_match( '/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $dish_id )
				|| ! isset( $available[ $dish_id ] ) ) {
				return self::error(
					'complete99_catalog_selection_unknown',
					'The requested canonical dish ID is invalid or absent from the graph.'
				);
			}
			$selection[ $dish_id ] = true;
		}
		return $selection;
	}

	private static function collect_component_entities( $components, $dish, &$entities ) {
		foreach ( $components as $component ) {
			$entity_id = $component['code'];
			if ( ! isset( $entities[ $entity_id ] ) ) {
				$entities[ $entity_id ] = array(
					'entity_id'         => $entity_id,
					'entity_type'       => $component['type'],
					'entity_types'      => array(),
					'name'              => array( 'he' => '', 'en' => '' ),
					'description'       => array( 'he' => '', 'en' => '' ),
					'aliases'           => array( 'he' => array(), 'en' => array() ),
					'descriptions'      => array( 'he' => array(), 'en' => array() ),
					'source_dish_ids'   => array(),
					'source_record_ids' => array(),
					'source_versions'   => array(),
					'entity_versions'   => array(),
					'source_evidence'   => array(),
				);
			}
			$entities[ $entity_id ]['entity_types'][] = $component['type'];
			foreach ( array( 'he', 'en' ) as $language ) {
				$entities[ $entity_id ]['aliases'][ $language ][]      = $component['label'][ $language ];
				$entities[ $entity_id ]['descriptions'][ $language ][] = $component['evidence']['statement'][ $language ];
			}
			$entities[ $entity_id ]['source_dish_ids'][]   = $dish['dish_id'];
			$entities[ $entity_id ]['source_record_ids'][] = $dish['source_record_id'];
			$entities[ $entity_id ]['source_versions'][]   = $dish['source_version'];
			$entities[ $entity_id ]['entity_versions'][]   = $dish['entity_version'];
			$evidence_key = $dish['dish_id'] . ':' . $component['evidence']['source_id'];
			$entities[ $entity_id ]['source_evidence'][ $evidence_key ] = $component['evidence'];

			if ( ! empty( $component['children'] ) ) {
				self::collect_component_entities( $component['children'], $dish, $entities );
			}
		}
	}

	private static function preflight_existing_records( $entity_ids, $woo_active ) {
		$post_types = array( 'c99_ingredient', 'c99_product_plan' );
		if ( $woo_active ) {
			$post_types[] = 'product';
		}
		$existing = array(
			'c99_ingredient'  => array(),
			'c99_product_plan'=> array(),
			'product'         => array(),
		);
		foreach ( $post_types as $post_type ) {
			foreach ( $entity_ids as $entity_id ) {
				$bindings = array();
				foreach (
					array(
						self::META_INGREDIENT_CODE,
						self::META_ENTITY_ID,
						self::EVALUATION_META_INGREDIENT_CODE,
					) as $meta_key
				) {
					$binding = self::unique_existing_post_id( $post_type, $meta_key, $entity_id );
					if ( self::is_error( $binding ) ) {
						return $binding;
					}
					$bindings[] = $binding;
				}
				$post_id = self::reconcile_existing_bindings( $post_type, $entity_id, $bindings );
				if ( self::is_error( $post_id ) ) {
					return $post_id;
				}
				if ( $post_id ) {
					$validation = self::validate_existing_managed_record(
						$post_type,
						$post_id,
						$entity_id,
						'product' === $post_type ? 'draft' : 'private'
					);
					if ( self::is_error( $validation ) ) {
						return $validation;
					}
				}
				$existing[ $post_type ][ $entity_id ] = $post_id;
			}
		}
		return $existing;
	}

	private static function unique_existing_post_id( $post_type, $meta_key, $entity_id ) {
		$statuses = function_exists( 'get_post_stati' ) ? array_values( get_post_stati() ) : 'any';
		$ids      = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => $statuses,
				'posts_per_page'         => 3,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => $meta_key,
						'value'   => $entity_id,
						'compare' => '=',
					),
				),
			)
		);
		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
		if ( 1 < count( $ids ) ) {
			return self::error(
				'complete99_catalog_duplicate_entity',
				'Canonical entity ' . $entity_id . ' has duplicate ' . $post_type . ' records.'
			);
		}
		return empty( $ids ) ? 0 : $ids[0];
	}

	private static function reconcile_existing_bindings( $post_type, $entity_id, $bindings ) {
		$bindings = array_values( array_unique( array_filter( array_map( 'absint', $bindings ) ) ) );
		if ( 1 < count( $bindings ) ) {
			return self::error(
				'complete99_catalog_entity_binding_conflict',
				'Canonical entity ' . $entity_id . ' has conflicting ' . $post_type . ' bindings.'
			);
		}
		return empty( $bindings ) ? 0 : $bindings[0];
	}

	private static function validate_existing_managed_record( $post_type, $post_id, $entity_id, $required_status ) {
		$post = get_post( $post_id );
		if ( ! $post || $post_type !== (string) $post->post_type ) {
			return self::error(
				'complete99_catalog_existing_record_invalid',
				'An existing catalog binding does not resolve to its expected post type.'
			);
		}

		$graph_managed      = '1' === (string) get_post_meta( $post_id, self::META_MANAGED, true );
		$evaluation_managed = self::is_evaluation_managed( $post_id );
		if ( ! $graph_managed && ! $evaluation_managed ) {
			return self::error(
				'complete99_catalog_nonmanaged_binding',
				'An existing canonical binding is not managed by an approved catalog owner.'
			);
		}
		if ( $graph_managed
			&& ( $entity_id !== (string) get_post_meta( $post_id, self::META_INGREDIENT_CODE, true )
				|| $entity_id !== (string) get_post_meta( $post_id, self::META_ENTITY_ID, true ) ) ) {
			return self::error(
				'complete99_catalog_entity_binding_mismatch',
				'An existing graph-managed record has mismatched canonical bindings.'
			);
		}
		if ( $evaluation_managed
			&& $entity_id !== (string) get_post_meta( $post_id, self::EVALUATION_META_INGREDIENT_CODE, true ) ) {
			return self::error(
				'complete99_catalog_entity_binding_mismatch',
				'An existing evaluation-managed record has a mismatched ingredient binding.'
			);
		}

		foreach (
			array(
				self::META_INGREDIENT_CODE            => $entity_id,
				self::META_ENTITY_ID                  => $entity_id,
				self::EVALUATION_META_INGREDIENT_CODE => $entity_id,
			) as $meta_key => $expected
		) {
			$value = (string) get_post_meta( $post_id, $meta_key, true );
			if ( '' !== $value && $expected !== $value ) {
				return self::error(
					'complete99_catalog_entity_binding_mismatch',
					'An existing managed record has a conflicting ingredient binding.'
				);
			}
		}

		$product_code = (string) get_post_meta( $post_id, self::EVALUATION_META_PRODUCT_CODE, true );
		if ( $evaluation_managed && '' === self::sanitize_product_code( $product_code ) ) {
			return self::error(
				'complete99_catalog_product_binding_invalid',
				'An evaluation-managed record is missing its exact canonical product code.'
			);
		}
		if ( in_array( $post_type, array( 'c99_product_plan', 'product' ), true ) ) {
			foreach ( array( self::META_PRODUCT_CODE, '_complete99_product_sku', '_sku' ) as $meta_key ) {
				$value = (string) get_post_meta( $post_id, $meta_key, true );
				if ( '' !== $value && ( '' === $product_code || $product_code !== $value ) ) {
					return self::error(
						'complete99_catalog_product_binding_conflict',
						'An existing managed record has a conflicting canonical product binding.'
					);
				}
			}
		}

		if ( $required_status !== (string) $post->post_status ) {
			return self::error(
				'complete99_catalog_existing_exposure_invalid',
				'An existing catalog record is not retained in its required held status.'
			);
		}

		$safe_gate_values = array(
			self::PRODUCT_APPROVED              => array( '', 'no' ),
			self::STOCK_AUTHORITY               => array( '', 'pending', 'evaluation_only' ),
			self::LABEL_REVIEWED                 => array( '', 'no' ),
			self::RIGHTS_REVIEWED                => array( '', 'no' ),
			self::TAX_REVIEWED                   => array( '', 'no' ),
			self::MEDIA_PUBLIC_SAFE              => array( '', 'no' ),
			self::EVALUATION_META_PUBLIC_SALE    => array( '', 'no' ),
			self::EVALUATION_META_SALE_STATE     => array( '', 'held_until_acceptance' ),
			'_complete99_product_status'          => array( '', 'draft_not_approved', 'private_evaluation_held' ),
			'_complete99_product_stock_source'    => array( '', 'pending', 'evaluation_only' ),
			'_complete99_product_rights'          => array( '', 'pending' ),
			'_complete99_index_eligible'          => array( '', '0' ),
			'_complete99_verification_state'      => array( '', 'catalog_graph_draft', 'evaluation_catalog_held' ),
		);
		foreach ( $safe_gate_values as $meta_key => $allowed ) {
			$value = (string) get_post_meta( $post_id, $meta_key, true );
			if ( ! in_array( $value, $allowed, true ) ) {
				return self::error(
					'complete99_catalog_acceptance_gate_completed',
					'An existing catalog record contains a completed or conflicting acceptance gate.'
				);
			}
		}

		if ( $evaluation_managed ) {
			$evaluation_price = (string) get_post_meta( $post_id, self::EVALUATION_META_PRICE, true );
			if ( 1 !== preg_match( '/\A(?:0|[1-9][0-9]{0,7})\.[0-9]{2}\z/', $evaluation_price )
				|| 0.0 >= (float) $evaluation_price
				|| 1 !== (int) get_post_meta( $post_id, self::EVALUATION_META_STOCK, true )
				|| 'private_benchmark_only' !== (string) get_post_meta( $post_id, self::EVALUATION_META_PRICE_SCOPE, true )
				|| 'private_evaluation_only' !== (string) get_post_meta( $post_id, self::EVALUATION_META_STOCK_SCOPE, true ) ) {
				return self::error(
					'complete99_catalog_evaluation_hold_invalid',
					'An evaluation-managed record has incomplete or conflicting held evaluation data.'
				);
			}
		}

		if ( 'product' === $post_type ) {
			return self::validate_existing_woo_product( $post_id, $product_code, $evaluation_managed );
		}
		return true;
	}

	private static function validate_existing_woo_product( $post_id, $product_code, $evaluation_managed ) {
		$product = wc_get_product( $post_id );
		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			return self::error(
				'complete99_catalog_product_type_invalid',
				'A generated catalog entity is linked to a non-simple WooCommerce product.'
			);
		}

		$sku            = (string) $product->get_sku();
		$price          = (string) $product->get_price();
		$regular_price  = (string) $product->get_regular_price();
		$sale_price     = (string) $product->get_sale_price();
		$state_is_safe  = 'hidden' === (string) $product->get_catalog_visibility()
			&& ! $product->backorders_allowed()
			&& ! $product->is_virtual()
			&& ! $product->is_downloadable()
			&& ( ! method_exists( $product, 'is_purchasable' ) || ! $product->is_purchasable() );

		if ( $evaluation_managed ) {
			$evaluation_price = (string) get_post_meta( $post_id, self::EVALUATION_META_PRICE, true );
			$state_is_safe     = $state_is_safe
				&& $product_code === $sku
				&& $evaluation_price === $price
				&& $evaluation_price === $regular_price
				&& '' === $sale_price
				&& $product->managing_stock()
				&& 1 === (int) $product->get_stock_quantity()
				&& 'instock' === (string) $product->get_stock_status();
		} else {
			$state_is_safe = $state_is_safe
				&& '' === $sku
				&& '' === $price
				&& '' === $regular_price
				&& '' === $sale_price
				&& ! $product->managing_stock()
				&& null === $product->get_stock_quantity()
				&& 'outofstock' === (string) $product->get_stock_status();
		}

		if ( ! $state_is_safe ) {
			return self::error(
				'complete99_catalog_existing_product_hold_invalid',
				'An existing managed WooCommerce binding is not in its exact private held state.'
			);
		}
		return true;
	}

	private static function is_evaluation_managed( $post_id ) {
		return '1' === (string) get_post_meta( $post_id, self::EVALUATION_META_MANAGED, true );
	}

	private static function upsert_private_record( $post_type, $entity, $existing_id ) {
		$evaluation_managed = $existing_id && self::is_evaluation_managed( $existing_id );
		$post               = array(
			'post_type'   => $post_type,
			'post_status' => 'private',
		);
		if ( ! $evaluation_managed ) {
			$post['post_title']   = $entity['name']['he'];
			$post['post_name']    = $entity['entity_id'];
			$post['post_excerpt'] = $entity['description']['en'];
			$post['post_content'] = $entity['description']['he'] . "\n\n" . $entity['description']['en'];
		}
		$post_id = self::store_post( $post, $existing_id, 'private' );
		if ( self::is_error( $post_id ) ) {
			return $post_id;
		}

		$meta = self::entity_meta( $entity );
		if ( ! $evaluation_managed ) {
			// WordPress stores registered boolean false metadata as an empty string.
			$meta['_complete99_managed']            = '1';
			$meta['_complete99_index_eligible']     = '';
			$meta['_complete99_verification_state'] = 'catalog_graph_draft';
		}
		$stored = self::store_and_verify_meta( $post_id, $meta );
		if ( self::is_error( $stored ) ) {
			return $stored;
		}
		return $post_id;
	}

	private static function upsert_product_plan( $entity, $existing_id ) {
		$evaluation_managed = $existing_id && self::is_evaluation_managed( $existing_id );
		$post_id = self::upsert_private_record( 'c99_product_plan', $entity, $existing_id );
		if ( self::is_error( $post_id ) ) {
			return $post_id;
		}
		if ( $evaluation_managed ) {
			$product_code = (string) get_post_meta( $post_id, self::EVALUATION_META_PRODUCT_CODE, true );
			$stored       = self::store_and_verify_meta(
				$post_id,
				array(
					self::META_PRODUCT_CODE => $product_code,
				)
			);
			return self::is_error( $stored ) ? $stored : $post_id;
		}
		$meta = array(
			'_complete99_product_status'       => 'draft_not_approved',
			self::PRODUCT_NAME_HE              => $entity['name']['he'],
			self::PRODUCT_NAME_EN              => $entity['name']['en'],
			self::PRODUCT_DESC_HE              => $entity['description']['he'],
			self::PRODUCT_DESC_EN              => $entity['description']['en'],
			'_complete99_product_stock_source' => 'pending',
			'_complete99_product_rights'       => 'pending',
			self::PRODUCT_APPROVED             => 'no',
			self::STOCK_AUTHORITY              => 'pending',
			self::LABEL_REVIEWED               => 'no',
			self::RIGHTS_REVIEWED              => 'no',
			self::TAX_REVIEWED                 => 'no',
			self::MEDIA_PUBLIC_SAFE             => 'no',
		);
		$stored = self::store_and_verify_meta( $post_id, $meta );
		if ( self::is_error( $stored ) ) {
			return $stored;
		}
		foreach ( array( '_complete99_product_sku', '_complete99_product_weight', '_complete99_product_price', '_complete99_product_currency' ) as $held_key ) {
			delete_post_meta( $post_id, $held_key );
			if ( metadata_exists( 'post', $post_id, $held_key ) ) {
				return self::error(
					'complete99_catalog_plan_commerce_hold_failed',
					'A generated product plan retained a held commercial field.'
				);
			}
		}
		return $post_id;
	}

	private static function upsert_woo_product( $entity, $existing_id ) {
		if ( $existing_id && self::is_evaluation_managed( $existing_id ) ) {
			$product_code                    = (string) get_post_meta( $existing_id, self::EVALUATION_META_PRODUCT_CODE, true );
			$meta                            = self::entity_meta( $entity );
			$meta[ self::META_PRODUCT_CODE ] = $product_code;
			$stored                          = self::store_and_verify_meta( $existing_id, $meta );
			if ( self::is_error( $stored ) ) {
				return $stored;
			}
			$validation = self::validate_existing_woo_product( $existing_id, $product_code, true );
			return self::is_error( $validation ) ? $validation : absint( $existing_id );
		}

		try {
			$product = $existing_id ? wc_get_product( $existing_id ) : new WC_Product_Simple();
			if ( ! $product || ( $existing_id && ! $product->is_type( 'simple' ) ) ) {
				return self::error(
					'complete99_catalog_product_type_invalid',
					'A generated catalog entity is linked to a non-simple WooCommerce product.'
				);
			}

			$product->set_name( $entity['name']['he'] );
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_description( $entity['description']['he'] );
			$product->set_short_description( '' );
			$product->set_sku( '' );
			$product->set_price( '' );
			$product->set_regular_price( '' );
			$product->set_sale_price( '' );
			$product->set_manage_stock( false );
			$product->set_stock_quantity( null );
			$product->set_stock_status( 'outofstock' );
			$product->set_backorders( 'no' );
			$product->set_sold_individually( false );
			$product->set_virtual( false );
			$product->set_downloadable( false );
			$product->set_purchase_note( '' );
			$product_id = absint( $product->save() );
		} catch ( \Throwable $error ) {
			return self::error(
				'complete99_catalog_product_save_failed',
				'The held WooCommerce product could not be saved: ' . $error->getMessage()
			);
		}
		if ( ! $product_id ) {
			return self::error( 'complete99_catalog_product_save_failed', 'The held WooCommerce product returned no ID.' );
		}

		$meta = self::entity_meta( $entity );
		$meta[ self::PRODUCT_NAME_HE ]   = $entity['name']['he'];
		$meta[ self::PRODUCT_NAME_EN ]   = $entity['name']['en'];
		$meta[ self::PRODUCT_DESC_HE ]   = $entity['description']['he'];
		$meta[ self::PRODUCT_DESC_EN ]   = $entity['description']['en'];
		$meta[ self::PRODUCT_APPROVED ]  = 'no';
		$meta[ self::STOCK_AUTHORITY ]   = 'pending';
		$meta[ self::LABEL_REVIEWED ]    = 'no';
		$meta[ self::RIGHTS_REVIEWED ]   = 'no';
		$meta[ self::TAX_REVIEWED ]      = 'no';
		$meta[ self::MEDIA_PUBLIC_SAFE ] = 'no';
		$stored = self::store_and_verify_meta( $product_id, $meta );
		if ( self::is_error( $stored ) ) {
			return $stored;
		}

		$fresh = wc_get_product( $product_id );
		if ( ! $fresh
			|| 'draft' !== (string) get_post_status( $product_id )
			|| 'hidden' !== (string) $fresh->get_catalog_visibility()
			|| '' !== (string) $fresh->get_price()
			|| '' !== (string) $fresh->get_regular_price()
			|| '' !== (string) $fresh->get_sale_price()
			|| $fresh->managing_stock()
			|| null !== $fresh->get_stock_quantity()
			|| 'outofstock' !== (string) $fresh->get_stock_status()
			|| $fresh->backorders_allowed()
			|| ( method_exists( $fresh, 'is_purchasable' ) && $fresh->is_purchasable() ) ) {
			return self::error(
				'complete99_catalog_product_hold_verification_failed',
				'A generated WooCommerce product did not remain hidden, draft, unpriced and unstocked.'
			);
		}
		return $product_id;
	}

	private static function store_post( $post, $existing_id, $required_status ) {
		if ( $existing_id ) {
			$post['ID'] = absint( $existing_id );
			$post_id    = wp_update_post( self::slash( $post ), true );
		} else {
			$post_id = wp_insert_post( self::slash( $post ), true );
		}
		if ( self::is_error( $post_id ) || 1 > absint( $post_id ) ) {
			return self::error( 'complete99_catalog_post_save_failed', 'A held catalog record could not be saved.' );
		}
		$post_id = absint( $post_id );
		$stored  = get_post( $post_id );
		if ( ! $stored
			|| $post['post_type'] !== (string) $stored->post_type
			|| $required_status !== (string) $stored->post_status ) {
			return self::error(
				'complete99_catalog_post_hold_verification_failed',
				'A generated catalog record did not retain its private or draft status.'
			);
		}
		return $post_id;
	}

	private static function entity_meta( $entity ) {
		return array(
			self::META_INGREDIENT_CODE   => $entity['entity_id'],
			self::META_ENTITY_ID         => $entity['entity_id'],
			self::META_ENTITY_TYPE       => $entity['entity_type'],
			self::META_GRAPH_VERSION     => self::REGISTRY_SCHEMA,
			self::META_ENTITY_VERSIONS   => $entity['entity_versions'],
			self::META_SOURCE_DISH_IDS   => $entity['source_dish_ids'],
			self::META_SOURCE_RECORD_IDS => $entity['source_record_ids'],
			self::META_SOURCE_VERSIONS   => $entity['source_versions'],
			self::META_NAME_HE           => $entity['name']['he'],
			self::META_NAME_EN           => $entity['name']['en'],
			self::META_DESCRIPTION_HE    => $entity['description']['he'],
			self::META_DESCRIPTION_EN    => $entity['description']['en'],
			self::META_ALIASES_HE        => $entity['aliases']['he'],
			self::META_ALIASES_EN        => $entity['aliases']['en'],
			self::META_MANAGED           => '1',
		);
	}

	private static function store_and_verify_meta( $post_id, $fields ) {
		foreach ( $fields as $key => $value ) {
			$updated = update_post_meta( $post_id, $key, self::slash( $value ) );
			if ( false === $updated
				&& ( ! metadata_exists( 'post', $post_id, $key )
					|| ! self::meta_values_equal( $value, get_post_meta( $post_id, $key, true ) ) ) ) {
				return self::error(
					'complete99_catalog_meta_save_failed',
					'A generated catalog record could not store its canonical metadata.'
				);
			}
		}
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $post_id, 'post_meta' );
		}
		foreach ( $fields as $key => $value ) {
			if ( ! metadata_exists( 'post', $post_id, $key )
				|| ! self::meta_values_equal( $value, get_post_meta( $post_id, $key, true ) ) ) {
				return self::error(
					'complete99_catalog_meta_readback_failed',
					'A generated catalog record failed canonical metadata readback.'
				);
			}
		}
		return true;
	}

	private static function dish_id_for_saved_post( $post_id, $post ) {
		unset( $post );
		$registry = self::load_registry();
		if ( self::is_error( $registry ) ) {
			return $registry;
		}
		$canonical_dish_id = (string) get_post_meta( $post_id, self::META_DISH_ID, true );
		if ( '' === self::sanitize_dish_id( $canonical_dish_id ) ) {
			return self::error(
				'complete99_catalog_dish_mapping_missing',
				'The approved dish has no exact canonical graph mapping.'
			);
		}
		foreach ( $registry['dishes'] as $dish ) {
			if ( hash_equals( $dish['dish_id'], $canonical_dish_id ) ) {
				return $dish['dish_id'];
			}
		}
		return self::error(
			'complete99_catalog_dish_mapping_missing',
			'The approved dish has no exact canonical graph mapping.'
		);
	}

	private static function unique_sorted_text( $values ) {
		$values = array_values( array_unique( array_map( 'strval', $values ) ) );
		usort(
			$values,
			static function ( $left, $right ) {
				$length = Complete99_Catalog_Graph::text_length( $left ) <=> Complete99_Catalog_Graph::text_length( $right );
				return 0 !== $length ? $length : strcmp( $left, $right );
			}
		);
		return $values;
	}

	private static function canonical_text( $values ) {
		return empty( $values ) ? '' : (string) $values[0];
	}

	private static function text_length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value, 'UTF-8' ) : strlen( (string) $value );
	}

	private static function meta_values_equal( $expected, $actual ) {
		if ( is_array( $expected ) || is_array( $actual ) ) {
			return self::value_digest( $expected ) === self::value_digest( $actual );
		}
		return (string) $expected === (string) $actual;
	}

	private static function value_digest( $value ) {
		if ( is_array( $value ) ) {
			if ( ! self::is_list( $value ) ) {
				ksort( $value, SORT_STRING );
			}
			foreach ( $value as $key => $item ) {
				$value[ $key ] = is_array( $item ) ? self::canonical_value( $item ) : $item;
			}
		}
		return hash( 'sha256', serialize( $value ) );
	}

	private static function canonical_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( ! self::is_list( $value ) ) {
			ksort( $value, SORT_STRING );
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonical_value( $item );
		}
		return $value;
	}

	private static function slash( $value ) {
		return function_exists( 'wp_slash' ) ? wp_slash( $value ) : $value;
	}

	private static function meta_auth_callback( $post_type ) {
		if ( 'c99_ingredient' === $post_type ) {
			return static function () {
				return current_user_can( 'edit_c99_ingredients' );
			};
		}
		if ( 'product' === $post_type ) {
			return static function () {
				return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
			};
		}
		return static function () {
			return current_user_can( 'manage_options' );
		};
	}

	private static function validate_bilingual_urls( $urls, $path ) {
		self::assert_exact_keys( $urls, array( 'he', 'en' ), $path );
		foreach ( array( 'he', 'en' ) as $language ) {
			self::assert_https_url( $urls[ $language ], $path . '.' . $language );
		}
	}

	private static function validate_bilingual_text( $value, $path, $allow_empty, $maximum ) {
		self::assert_exact_keys( $value, array( 'he', 'en' ), $path );
		foreach ( array( 'he', 'en' ) as $language ) {
			self::assert_text( $value[ $language ], $path . '.' . $language, $allow_empty, $maximum );
		}
	}

	private static function assert_exact_keys( $value, $expected, $path ) {
		self::assert_associative_array( $value, $path, true );
		$actual = array_keys( $value );
		sort( $actual, SORT_STRING );
		$expected_sorted = $expected;
		sort( $expected_sorted, SORT_STRING );
		if ( $actual !== $expected_sorted ) {
			throw new \UnexpectedValueException( $path . ' has missing or unknown fields.' );
		}
	}

	private static function assert_associative_array( $value, $path, $allow_empty ) {
		if ( ! is_array( $value ) || self::is_list( $value ) || ( ! $allow_empty && empty( $value ) ) ) {
			throw new \UnexpectedValueException( $path . ' must be a non-list object.' );
		}
	}

	private static function assert_list( $value, $path, $allow_empty ) {
		if ( ! is_array( $value ) || ! self::is_list( $value ) || ( ! $allow_empty && empty( $value ) ) ) {
			throw new \UnexpectedValueException( $path . ' must be a list.' );
		}
	}

	private static function is_list( $value ) {
		if ( ! is_array( $value ) ) {
			return false;
		}
		if ( array() === $value ) {
			return true;
		}
		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	private static function assert_exact_list( $value, $expected, $path ) {
		self::assert_list( $value, $path, empty( $expected ) );
		if ( $value !== $expected ) {
			throw new \UnexpectedValueException( $path . ' does not match the v1 contract.' );
		}
	}

	private static function assert_identifier_list( $value, $path ) {
		self::assert_list( $value, $path, true );
		if ( 500 < count( $value ) ) {
			throw new \UnexpectedValueException( $path . ' exceeds the bounded v1 identifier count.' );
		}
		$seen = array();
		foreach ( $value as $index => $identifier ) {
			self::assert_identifier( $identifier, $path . '.' . $index );
			if ( isset( $seen[ $identifier ] ) ) {
				throw new \UnexpectedValueException( $path . ' contains a duplicate identifier.' );
			}
			$seen[ $identifier ] = true;
		}
	}

	private static function assert_text( $value, $path, $allow_empty, $maximum ) {
		if ( ! is_string( $value )
			|| ( ! $allow_empty && '' === trim( $value ) )
			|| self::text_length( $value ) > $maximum
			|| preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value )
			|| preg_match( '/<[^>]*>/', $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not safe bounded text.' );
		}
	}

	private static function assert_identifier( $value, $path ) {
		if ( ! is_string( $value )
			|| 1 !== preg_match( '/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/', $value )
			|| strlen( $value ) > 160 ) {
			throw new \UnexpectedValueException( $path . ' is not a canonical identifier.' );
		}
	}

	private static function assert_entity_id( $value, $path ) {
		self::assert_identifier( $value, $path );
		if ( 1 !== preg_match( '/\A(?:ingredient|component)-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not an ingredient or component entity ID.' );
		}
	}

	private static function assert_dish_id( $value, $path ) {
		if ( '' === self::sanitize_dish_id( $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not a canonical menu-reference dish ID.' );
		}
	}

	private static function assert_slug( $value, $path ) {
		if ( ! is_string( $value )
			|| 1 !== preg_match( '/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value )
			|| strlen( $value ) > 120 ) {
			throw new \UnexpectedValueException( $path . ' is not a canonical slug.' );
		}
	}

	private static function assert_owner( $value, $path ) {
		if ( ! is_string( $value )
			|| 1 !== preg_match( '/\A(?:unassigned|[a-z0-9]+(?:[._-][a-z0-9]+)*)\z/', $value )
			|| strlen( $value ) > 80 ) {
			throw new \UnexpectedValueException( $path . ' is not a valid owner state.' );
		}
	}

	private static function assert_semver( $value, $path ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '/\A(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\z/', $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not a semantic version.' );
		}
	}

	private static function assert_date( $value, $path ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '/\A\d{4}-\d{2}-\d{2}\z/', $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not an ISO date.' );
		}
		$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $date
			|| ( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) )
			|| $date->format( 'Y-m-d' ) !== $value ) {
			throw new \UnexpectedValueException( $path . ' is not a real calendar date.' );
		}
	}

	private static function assert_https_url( $value, $path ) {
		if ( ! is_string( $value )
			|| strlen( $value ) > 2048
			|| false === filter_var( $value, FILTER_VALIDATE_URL ) ) {
			throw new \UnexpectedValueException( $path . ' is not a valid URL.' );
		}
		$parts = parse_url( $value );
		if ( ! is_array( $parts )
			|| 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) )
			|| empty( $parts['host'] )
			|| isset( $parts['user'] )
			|| isset( $parts['pass'] )
			|| isset( $parts['fragment'] ) ) {
			throw new \UnexpectedValueException( $path . ' must be a credential-free HTTPS URL.' );
		}
	}

	private static function assert_boolean( $value, $path ) {
		if ( ! is_bool( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be boolean.' );
		}
	}

	private static function assert_enum( $value, $allowed, $path ) {
		if ( ! is_string( $value ) || ! in_array( $value, $allowed, true ) ) {
			throw new \UnexpectedValueException( $path . ' has an unsupported value.' );
		}
	}

	private static function assert_same( $expected, $actual, $path ) {
		if ( $expected !== $actual ) {
			throw new \UnexpectedValueException( $path . ' does not match the fail-closed contract.' );
		}
	}

	private static function allergen_states() {
		return array(
			'unknown',
			'contains',
			'may_contain',
			'not_intentionally_used',
			'verified_free_from',
		);
	}

	private static function allergen_codes() {
		return array(
			'cereals_containing_gluten',
			'crustaceans',
			'eggs',
			'fish',
			'peanuts',
			'soybeans',
			'milk',
			'tree_nuts',
			'celery',
			'mustard',
			'sesame',
			'sulphur_dioxide_and_sulphites',
			'lupin',
			'molluscs',
		);
	}

	private static function is_error( $value ) {
		return function_exists( 'is_wp_error' )
			? is_wp_error( $value )
			: ( class_exists( 'WP_Error' ) && $value instanceof WP_Error );
	}

	private static function error( $code, $message ) {
		if ( class_exists( 'WP_Error' ) ) {
			return new WP_Error( $code, $message );
		}
		throw new \RuntimeException( $message );
	}
}
