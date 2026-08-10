<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Source-bound culinary science, culture and market knowledge graph.
 *
 * The bundled registry is the reviewed WordPress-side source of truth for the
 * first museum vertical slice. Public REST responses are strict projections.
 * Supplier terms, costs, prompts, compliance workflow and commerce plans stay
 * in the administrator-only editorial snapshot.
 */
final class Complete99_Culinary_Science {
	const REGISTRY_SCHEMA = 'complete99-culinary-science-registry/v6';
	const REST_NAMESPACE  = 'complete99/v1';
	const DATA_FILE       = 'culinary-science-pilot.php';

	private static $booted         = false;
	private static $registry_cache = null;
	private static $public_index_cache = array();

	/**
	 * Register REST routes without creating roles or public posts.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register public read projections and a private editorial snapshot.
	 */
	public static function register_routes() {
		$lang_args = array(
			'lang' => array(
				'default'           => 'he',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static function ( $value ) {
					return in_array( (string) $value, array( 'he', 'en' ), true );
				},
			),
		);
		$collection_args = array_merge(
			$lang_args,
			array(
				'cursor' => array(
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => static function ( $value ) {
						return '' === (string) $value || 1 === preg_match( '/\A[a-z][a-z0-9-]{2,79}\z/', (string) $value );
					},
				),
				'limit' => array(
					'default'           => 50,
					'sanitize_callback' => 'absint',
					'validate_callback' => static function ( $value ) {
						return is_numeric( $value ) && (int) $value >= 1 && (int) $value <= 100;
					},
				),
				'type' => array(
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
				'cluster_id' => array(
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/culinary-science',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_public_collection' ),
				'args'                => $collection_args,
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/culinary-science/(?P<entity_id>[a-z][a-z0-9-]{2,79})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_public_entity' ),
				'args'                => array_merge(
					$lang_args,
					array(
						'entity_id' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
					)
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/editorial/culinary-science',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( __CLASS__, 'can_view_editorial_snapshot' ),
				'callback'            => array( __CLASS__, 'rest_editorial_snapshot' ),
			)
		);
	}

	public static function can_view_editorial_snapshot() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Load and validate the canonical bundled registry.
	 *
	 * @param bool $fresh Bypass the per-request cache.
	 * @return array|WP_Error
	 */
	public static function registry( $fresh = false ) {
		if ( ! $fresh && is_array( self::$registry_cache ) ) {
			return self::$registry_cache;
		}

		if ( ! defined( 'COMPLETE99_PLATFORM_DIR' ) ) {
			self::clear_caches();
			return new WP_Error( 'complete99_science_registry_missing', 'The culinary science registry is unavailable.' );
		}
		$path = COMPLETE99_PLATFORM_DIR . 'data/' . self::DATA_FILE;
		if ( ! is_readable( $path ) ) {
			self::clear_caches();
			return new WP_Error( 'complete99_science_registry_missing', 'The culinary science registry is unavailable.' );
		}

		try {
			$registry = require $path;
			$valid    = self::validate_registry( $registry );
			if ( is_wp_error( $valid ) ) {
				self::clear_caches();
				return self::invalid_registry_error();
			}
		} catch ( Throwable $error ) {
			self::clear_caches();
			return self::invalid_registry_error();
		}

		self::$registry_cache = $registry;
		if ( $fresh ) {
			self::$public_index_cache = array();
		}
		return self::$registry_cache;
	}

	private static function clear_caches() {
		self::$registry_cache     = null;
		self::$public_index_cache = array();
	}

	private static function invalid_registry_error() {
		return new WP_Error(
			'complete99_science_registry_invalid',
			'The culinary science registry failed its schema contract.',
			array( 'status' => 500 )
		);
	}

	/**
	 * Strictly validate the ontology, evidence and exposure boundaries.
	 *
	 * @param mixed $registry Candidate registry.
	 * @return true|WP_Error
	 */
	public static function validate_registry( $registry ) {
		try {
			self::assert_exact_keys(
				$registry,
				array( 'schema', 'version', 'generated_at', 'locales', 'surface_class', 'controlled_vocabulary', 'sources', 'source_receipts', 'entities', 'collections' ),
				'registry'
			);
			if ( self::REGISTRY_SCHEMA !== $registry['schema'] ) {
				throw new RuntimeException( 'registry.schema' );
			}
			self::assert_identifier( $registry['version'], 'registry.version', 100 );
			self::assert_date( $registry['generated_at'], 'registry.generated_at' );
			self::assert_exact_list( $registry['locales'], array( 'he', 'en' ), 'registry.locales' );
			self::assert_identifier( $registry['surface_class'], 'registry.surface_class', 80 );
			self::assert_no_em_dash( $registry, 'registry' );

			$vocabulary_keys = array(
				'entity_types',
				'surface_classes',
				'index_policies',
				'page_roles',
				'intent_classes',
				'profile_states',
				'dimensions',
				'evidence_classes',
				'value_scopes',
				'relation_types',
				'commerce_states',
				'asset_states',
				'rights_states',
				'source_types',
				'allowed_attributes',
				'attribution_states',
				'confidence_levels',
				'revenue_models',
				'pricing_states',
				'market_scopes',
				'customer_segments',
				'publication_states',
				'route_modes',
			);
			self::assert_exact_keys( $registry['controlled_vocabulary'], $vocabulary_keys, 'registry.controlled_vocabulary' );
			foreach ( $vocabulary_keys as $key ) {
				self::assert_identifier_list( $registry['controlled_vocabulary'][ $key ], 'registry.controlled_vocabulary.' . $key, false );
			}
			if ( ! in_array( $registry['surface_class'], $registry['controlled_vocabulary']['surface_classes'], true ) ) {
				throw new RuntimeException( 'registry.surface_class' );
			}
			self::assert_exact_list(
				$registry['controlled_vocabulary']['dimensions'],
				array( 'scientific', 'cultural', 'institutional', 'economic', 'structural' ),
				'registry.controlled_vocabulary.dimensions'
			);

			self::assert_associative_array( $registry['sources'], 'registry.sources', false );
			foreach ( $registry['sources'] as $source_id => $source ) {
				self::assert_identifier( $source_id, 'registry.sources.key', 100 );
				self::validate_source( $source_id, $source, $registry['controlled_vocabulary'] );
			}
			self::assert_associative_array( $registry['source_receipts'], 'registry.source_receipts', true );
			foreach ( $registry['source_receipts'] as $source_id => $receipt ) {
				self::assert_identifier( $source_id, 'registry.source_receipts.key', 100 );
				self::validate_source_receipt( $source_id, $receipt, $registry['sources'] );
			}

			self::assert_list( $registry['entities'], 'registry.entities', false );
			$entities_by_id = array();
			$slugs          = array();
			$canonical      = array();
			$fact_ids       = array();
			$relation_ids   = array();
			$scientific_measurement_ids = array();
			$children_by_parent = array();
			$query_owners   = array(
				'he' => array(),
				'en' => array(),
			);
			foreach ( $registry['entities'] as $offset => $entity ) {
				$path = 'registry.entities.' . $offset;
				self::validate_entity_shape(
					$entity,
					$path,
					$registry['controlled_vocabulary'],
					$registry['sources'],
					$registry['source_receipts'],
					$scientific_measurement_ids
				);
				$id   = $entity['id'];
				$slug = $entity['slug'];
				if ( isset( $entities_by_id[ $id ] ) || isset( $slugs[ $slug ] ) ) {
					throw new RuntimeException( $path . '.duplicate_identity' );
				}
				$entities_by_id[ $id ] = $entity;
				$slugs[ $slug ]        = true;
				if ( '' !== $entity['parent_id'] ) {
					if ( ! isset( $children_by_parent[ $entity['parent_id'] ] ) ) {
						$children_by_parent[ $entity['parent_id'] ] = array();
					}
					$children_by_parent[ $entity['parent_id'] ][] = $id;
				}
				foreach ( array( 'he', 'en' ) as $language ) {
					$url = $entity['seo']['canonical_path'][ $language ];
					$canonical_owner = $entity['seo']['owner_entity_id'];
					if ( isset( $canonical[ $url ] ) && $canonical[ $url ] !== $canonical_owner ) {
						throw new RuntimeException( $path . '.duplicate_canonical' );
					}
					$canonical[ $url ] = $canonical_owner;
					foreach ( $entity['seo']['query_variants'][ $language ] as $query_variant ) {
						$query_key = self::normalize_query( $query_variant );
						if ( isset( $query_owners[ $language ][ $query_key ] ) && $query_owners[ $language ][ $query_key ] !== $canonical_owner ) {
							throw new RuntimeException( $path . '.duplicate_query_owner.' . $language . '.' . $query_key );
						}
						$query_owners[ $language ][ $query_key ] = $canonical_owner;
					}
				}
				foreach ( $entity['facts'] as $fact ) {
					if ( isset( $fact_ids[ $fact['id'] ] ) ) {
						throw new RuntimeException( $path . '.duplicate_fact_id' );
					}
					$fact_ids[ $fact['id'] ] = true;
				}
				foreach ( $entity['relations'] as $relation ) {
					if ( isset( $relation_ids[ $relation['id'] ] ) ) {
						throw new RuntimeException( $path . '.duplicate_relation_id' );
					}
					$relation_ids[ $relation['id'] ] = true;
				}
			}

			foreach ( $entities_by_id as $entity_id => $entity ) {
				self::validate_entity_graph( $entity, $entities_by_id, $children_by_parent, $registry['controlled_vocabulary'] );
				self::assert_parent_chain_is_acyclic( $entity_id, $entities_by_id );
			}
			self::validate_collections( $registry['collections'], $entities_by_id );
		} catch ( Throwable $error ) {
			return new WP_Error(
				'complete99_science_registry_invalid',
				'The culinary science registry failed its schema contract.',
				array( 'path' => $error->getMessage() )
			);
		}

		return true;
	}

	private static function validate_source( $source_id, $source, $vocabulary ) {
		$path = 'registry.sources.' . $source_id;
		self::assert_exact_keys( $source, array( 'type', 'publisher', 'title', 'url', 'published_at', 'retrieved_at' ), $path );
		if ( ! in_array( $source['type'], $vocabulary['source_types'], true ) ) {
			throw new RuntimeException( $path . '.type' );
		}
		self::assert_text( $source['publisher'], $path . '.publisher', 200 );
		self::assert_text( $source['title'], $path . '.title', 300 );
		if ( ! filter_var( $source['url'], FILTER_VALIDATE_URL ) || 0 !== strpos( $source['url'], 'https://' ) ) {
			throw new RuntimeException( $path . '.url' );
		}
		self::assert_date( $source['published_at'], $path . '.published_at', true );
		self::assert_date( $source['retrieved_at'], $path . '.retrieved_at' );
	}

	private static function validate_source_receipt( $source_id, $receipt, $sources ) {
		$path = 'registry.source_receipts.' . $source_id;
		self::assert_exact_keys(
			$receipt,
			array( 'schema', 'source_id', 'upstream_url', 'upstream_sha256', 'evidence_repository_path', 'evidence_sha256', 'retrieved_at', 'license', 'claim_locators', 'review_state' ),
			$path
		);
		if ( 'complete99-source-evidence-receipt/v1' !== $receipt['schema'] ) {
			throw new RuntimeException( $path . '.schema' );
		}
		self::assert_identifier( $receipt['source_id'], $path . '.source_id', 100 );
		if ( $source_id !== $receipt['source_id'] ) {
			throw new RuntimeException( $path . '.source_id_mismatch' );
		}
		if ( ! isset( $sources[ $source_id ] ) ) {
			throw new RuntimeException( $path . '.unknown_source' );
		}
		if ( ! is_string( $receipt['upstream_url'] )
			|| strlen( $receipt['upstream_url'] ) > 2048
			|| ! filter_var( $receipt['upstream_url'], FILTER_VALIDATE_URL )
			|| 0 !== strpos( $receipt['upstream_url'], 'https://' ) ) {
			throw new RuntimeException( $path . '.upstream_url' );
		}
		self::assert_sha256( $receipt['upstream_sha256'], $path . '.upstream_sha256' );
		self::assert_evidence_repository_path( $receipt['evidence_repository_path'], $path . '.evidence_repository_path' );
		self::assert_sha256( $receipt['evidence_sha256'], $path . '.evidence_sha256' );
		self::assert_datetime( $receipt['retrieved_at'], $path . '.retrieved_at' );
		self::assert_text( $receipt['license'], $path . '.license', 200 );
		self::assert_associative_array( $receipt['claim_locators'], $path . '.claim_locators', false );
		foreach ( $receipt['claim_locators'] as $locator => $description ) {
			self::assert_identifier( $locator, $path . '.claim_locators.key', 120 );
			self::assert_text( $description, $path . '.claim_locators.' . $locator, 500 );
		}
		if ( 'verified' !== $receipt['review_state'] ) {
			throw new RuntimeException( $path . '.review_state' );
		}
	}

	private static function validate_entity_shape( $entity, $path, $vocabulary, $sources, $source_receipts, &$scientific_measurement_ids ) {
		self::assert_exact_keys(
			$entity,
			array( 'id', 'type', 'slug', 'parent_id', 'name', 'summary', 'surface_class', 'index_policy', 'publication', 'seo', 'profiles', 'facts', 'taxonomy', 'relations', 'commerce', 'visual', 'compliance', 'trust', 'review' ),
			$path
		);
		self::assert_entity_id( $entity['id'], $path . '.id' );
		if ( ! in_array( $entity['type'], $vocabulary['entity_types'], true ) ) {
			throw new RuntimeException( $path . '.type' );
		}
		self::assert_slug( $entity['slug'], $path . '.slug' );
		if ( '' !== $entity['parent_id'] ) {
			self::assert_entity_id( $entity['parent_id'], $path . '.parent_id' );
		}
		self::assert_translation( $entity['name'], $path . '.name', 200 );
		self::assert_translation( $entity['summary'], $path . '.summary', 1500 );
		if ( ! in_array( $entity['surface_class'], $vocabulary['surface_classes'], true ) ) {
			throw new RuntimeException( $path . '.surface_class' );
		}
		if ( ! in_array( $entity['index_policy'], $vocabulary['index_policies'], true ) ) {
			throw new RuntimeException( $path . '.index_policy' );
		}
		self::validate_publication( $entity['publication'], $entity, $path . '.publication', $vocabulary );

		self::validate_seo( $entity['seo'], $path . '.seo', $vocabulary );
		self::assert_exact_keys( $entity['profiles'], $vocabulary['dimensions'], $path . '.profiles' );
		self::assert_list( $entity['facts'], $path . '.facts', true );
		$facts_by_id = array();
		foreach ( $entity['facts'] as $offset => $fact ) {
			self::validate_fact(
				$fact,
				$path . '.facts.' . $offset,
				$vocabulary,
				$sources,
				$source_receipts,
				$scientific_measurement_ids
			);
			if ( isset( $facts_by_id[ $fact['id'] ] ) ) {
				throw new RuntimeException( $path . '.facts.duplicate' );
			}
			$facts_by_id[ $fact['id'] ] = $fact;
		}
		foreach ( $vocabulary['dimensions'] as $dimension ) {
			$profile      = $entity['profiles'][ $dimension ];
			$profile_path = $path . '.profiles.' . $dimension;
			self::assert_exact_keys( $profile, array( 'state', 'summary', 'fact_ids' ), $profile_path );
			if ( ! in_array( $profile['state'], $vocabulary['profile_states'], true ) ) {
				throw new RuntimeException( $profile_path . '.state' );
			}
			self::assert_translation( $profile['summary'], $profile_path . '.summary', 1500 );
			self::assert_identifier_list( $profile['fact_ids'], $profile_path . '.fact_ids', true );
			foreach ( $profile['fact_ids'] as $fact_id ) {
				if ( ! isset( $facts_by_id[ $fact_id ] ) || $dimension !== $facts_by_id[ $fact_id ]['dimension'] ) {
					throw new RuntimeException( $profile_path . '.fact_reference' );
				}
			}
			if ( 'source_backed' === $profile['state'] && empty( $profile['fact_ids'] ) ) {
				throw new RuntimeException( $profile_path . '.source_backed_without_fact' );
			}
		}

		self::validate_taxonomy( $entity['taxonomy'], $path . '.taxonomy', $vocabulary );
		self::validate_relations( $entity['relations'], $path . '.relations', $vocabulary, $sources );
		self::validate_commerce( $entity['commerce'], $path . '.commerce', $vocabulary );
		self::validate_visual( $entity['visual'], $path . '.visual', $vocabulary );
		self::validate_compliance( $entity['compliance'], $path . '.compliance', $sources );
		self::validate_trust( $entity['trust'], $path . '.trust', $vocabulary );
		self::validate_review( $entity['review'], $path . '.review' );

		if ( 'public_discovery' === $entity['surface_class'] ) {
			$public_facts = array_filter(
				$entity['facts'],
				static function ( $fact ) {
					return true === $fact['public_safe'];
				}
			);
			if ( empty( $public_facts ) || ! in_array( $entity['review']['status'], array( 'source_reviewed', 'verified' ), true ) ) {
				throw new RuntimeException( $path . '.public_without_reviewed_evidence' );
			}
		}
	}

	private static function validate_publication( $publication, $entity, $path, $vocabulary ) {
		self::assert_exact_keys( $publication, array( 'state', 'public_api', 'public_page', 'search_index', 'approved_at' ), $path );
		if ( ! in_array( $publication['state'], $vocabulary['publication_states'], true ) ) {
			throw new RuntimeException( $path . '.state' );
		}
		foreach ( array( 'public_api', 'public_page', 'search_index' ) as $flag ) {
			if ( ! is_bool( $publication[ $flag ] ) ) {
				throw new RuntimeException( $path . '.' . $flag );
			}
		}
		self::assert_date( $publication['approved_at'], $path . '.approved_at', true );
		$exposure_requested = 'approved_public' === $publication['state']
			|| $publication['public_api']
			|| $publication['public_page']
			|| $publication['search_index'];
		$denial_reason = self::public_exposure_denial_reason( $entity );
		if ( $exposure_requested && '' !== $denial_reason ) {
			throw new RuntimeException( $path . '.public_exposure_denied.' . $denial_reason );
		}
		if ( $publication['public_api'] || $publication['public_page'] || $publication['search_index'] ) {
			if ( 'approved_public' !== $publication['state'] || '' === $publication['approved_at'] ) {
				throw new RuntimeException( $path . '.exposure_without_approval' );
			}
		}
		if ( 'approved_public' === $publication['state']
			&& ( 'public_discovery' !== $entity['surface_class']
				|| 'private' === $entity['seo']['route_mode']
				|| 'reviewed_bilingual' !== $entity['review']['language_status']
				|| ! in_array( $entity['review']['status'], array( 'source_reviewed', 'verified' ), true )
				|| 'approved' !== $entity['visual']['asset_state']
				|| ! in_array( $entity['visual']['rights_state'], array( 'cleared_owned', 'cleared_generated', 'cleared_licensed' ), true )
				|| '' === $entity['visual']['rights_receipt_digest']
				|| 'pending_named_review' === $entity['trust']['attribution_state'] ) ) {
			throw new RuntimeException( $path . '.approval_contract' );
		}
		$requires_culinary_test = 'dish' === $entity['type']
			|| ( 'preparation' === $entity['type'] && 'Recipe' === $entity['seo']['schema_type'] );
		if ( 'approved_public' === $publication['state'] && $requires_culinary_test && 'tested' !== $entity['review']['culinary_test_status'] ) {
			throw new RuntimeException( $path . '.culinary_test_gate' );
		}
		if ( 'approved_public' === $publication['state']
			&& 'preparation' === $entity['type']
			&& 'not_applicable' === $entity['review']['culinary_test_status']
			&& ( 'Recipe' === $entity['seo']['schema_type'] || true === $publication['search_index'] ) ) {
			throw new RuntimeException( $path . '.untested_preparation_scope' );
		}
		if ( 'approved_public' === $publication['state'] ) {
			foreach ( $entity['facts'] as $fact ) {
				if ( $fact['public_safe'] && 'editorial_inference' === $fact['evidence_class'] ) {
					throw new RuntimeException( $path . '.public_inference_gate' );
				}
			}
		}
		if ( $publication['search_index'] && 'index' !== $entity['index_policy'] ) {
			throw new RuntimeException( $path . '.index_contract' );
		}
		if ( $publication['search_index'] && 'standalone' !== $entity['seo']['route_mode'] ) {
			throw new RuntimeException( $path . '.index_route_contract' );
		}
		if ( $publication['search_index'] && ! $publication['public_page'] ) {
			throw new RuntimeException( $path . '.index_without_page' );
		}
	}

	private static function validate_seo( $seo, $path, $vocabulary ) {
		self::assert_exact_keys(
			$seo,
			array(
				'page_role',
				'route_mode',
				'owner_entity_id',
				'section_id',
				'cluster_id',
				'hub_entity_id',
				'intent_classes',
				'primary_intent',
				'primary_keyword',
				'query_variants',
				'term_variants',
				'semantic_entity_ids',
				'protected_exclusions',
				'protected_owner_ids',
				'canonical_path',
				'title',
				'h1',
				'meta_description',
				'opening',
				'schema_type',
				'breadcrumb_entity_ids',
				'visible_breadcrumbs',
				'expected_child_ids',
				'link_plan',
			),
			$path
		);
		if ( ! in_array( $seo['page_role'], $vocabulary['page_roles'], true ) ) {
			throw new RuntimeException( $path . '.page_role' );
		}
		if ( ! in_array( $seo['route_mode'], $vocabulary['route_modes'], true ) ) {
			throw new RuntimeException( $path . '.route_mode' );
		}
		self::assert_entity_id( $seo['owner_entity_id'], $path . '.owner_entity_id' );
		if ( '' !== $seo['section_id'] ) {
			self::assert_identifier( $seo['section_id'], $path . '.section_id', 100 );
		}
		if ( 'section' === $seo['route_mode'] && '' === $seo['section_id'] ) {
			throw new RuntimeException( $path . '.section_id' );
		}
		if ( 'section' !== $seo['route_mode'] && '' !== $seo['section_id'] ) {
			throw new RuntimeException( $path . '.unexpected_section_id' );
		}
		self::assert_identifier( $seo['cluster_id'], $path . '.cluster_id', 100 );
		self::assert_entity_id( $seo['hub_entity_id'], $path . '.hub_entity_id' );
		self::assert_identifier_list( $seo['intent_classes'], $path . '.intent_classes', false );
		foreach ( $seo['intent_classes'] as $intent_class ) {
			if ( ! in_array( $intent_class, $vocabulary['intent_classes'], true ) ) {
				throw new RuntimeException( $path . '.intent_classes.value' );
			}
		}
		self::assert_translation( $seo['primary_intent'], $path . '.primary_intent', 300 );
		self::assert_translation( $seo['primary_keyword'], $path . '.primary_keyword', 160 );
		self::assert_locale_lists( $seo['query_variants'], $path . '.query_variants', 30 );
		if ( empty( $seo['query_variants']['he'] ) || empty( $seo['query_variants']['en'] ) ) {
			throw new RuntimeException( $path . '.query_variants.empty' );
		}
		foreach ( array( 'he', 'en' ) as $language ) {
			if ( self::normalize_query( $seo['query_variants'][ $language ][0] ) !== self::normalize_query( $seo['primary_keyword'][ $language ] ) ) {
				throw new RuntimeException( $path . '.query_variants.primary_first.' . $language );
			}
		}
		self::assert_locale_lists( $seo['term_variants'], $path . '.term_variants', 60 );
		self::assert_identifier_list( $seo['semantic_entity_ids'], $path . '.semantic_entity_ids', true );
		self::assert_locale_lists( $seo['protected_exclusions'], $path . '.protected_exclusions', 20 );
		self::assert_identifier_list( $seo['protected_owner_ids'], $path . '.protected_owner_ids', true );
		self::assert_translation( $seo['canonical_path'], $path . '.canonical_path', 300 );
		if ( ! preg_match( '#^/(museum|traditions|dishes|ingredients|knowledge|store)/#', $seo['canonical_path']['he'] )
			|| ! preg_match( '#^/en/(museum|traditions|dishes|ingredients|knowledge|store)/#', $seo['canonical_path']['en'] ) ) {
			throw new RuntimeException( $path . '.canonical_path' );
		}
		self::assert_translation( $seo['title'], $path . '.title', 200 );
		self::assert_translation( $seo['h1'], $path . '.h1', 200 );
		self::assert_translation( $seo['meta_description'], $path . '.meta_description', 320 );
		self::assert_translation( $seo['opening'], $path . '.opening', 1500 );
		self::assert_identifier( $seo['schema_type'], $path . '.schema_type', 80 );
		self::assert_identifier_list( $seo['breadcrumb_entity_ids'], $path . '.breadcrumb_entity_ids', false );
		self::validate_visible_breadcrumbs( $seo['visible_breadcrumbs'], $path . '.visible_breadcrumbs' );
		self::assert_identifier_list( $seo['expected_child_ids'], $path . '.expected_child_ids', true );
		self::validate_link_plan( $seo['link_plan'], $path . '.link_plan', $vocabulary );
	}

	private static function validate_visible_breadcrumbs( $breadcrumbs, $path ) {
		self::assert_list( $breadcrumbs, $path, false );
		$keys = array();
		foreach ( $breadcrumbs as $offset => $breadcrumb ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $breadcrumb, array( 'key', 'label', 'path' ), $item_path );
			self::assert_identifier( $breadcrumb['key'], $item_path . '.key', 100 );
			if ( isset( $keys[ $breadcrumb['key'] ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$keys[ $breadcrumb['key'] ] = true;
			self::assert_translation( $breadcrumb['label'], $item_path . '.label', 200 );
			self::assert_translation( $breadcrumb['path'], $item_path . '.path', 300 );
			if ( 0 !== strpos( $breadcrumb['path']['he'], '/' ) || 0 !== strpos( $breadcrumb['path']['en'], '/en/' ) ) {
				throw new RuntimeException( $item_path . '.path' );
			}
		}
	}

	private static function validate_link_plan( $links, $path, $vocabulary ) {
		self::assert_list( $links, $path, true );
		$seen = array();
		foreach ( $links as $offset => $link ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $link, array( 'target_id', 'purpose', 'anchor', 'placement', 'required', 'public_safe', 'basis_relation_id', 'evidence_state' ), $item_path );
			self::assert_entity_id( $link['target_id'], $item_path . '.target_id' );
			self::assert_identifier( $link['purpose'], $item_path . '.purpose', 80 );
			self::assert_translation( $link['anchor'], $item_path . '.anchor', 200 );
			if ( ! in_array( $link['placement'], array( 'breadcrumb', 'body', 'related_module', 'commerce_module' ), true ) ) {
				throw new RuntimeException( $item_path . '.placement' );
			}
			if ( ! is_bool( $link['required'] ) || ! is_bool( $link['public_safe'] ) ) {
				throw new RuntimeException( $item_path . '.flags' );
			}
			if ( '' !== $link['basis_relation_id'] ) {
				self::assert_identifier( $link['basis_relation_id'], $item_path . '.basis_relation_id', 120 );
			}
			if ( ! in_array( $link['evidence_state'], $vocabulary['confidence_levels'], true ) ) {
				throw new RuntimeException( $item_path . '.evidence_state' );
			}
			$key = $link['placement'] . '|' . $link['purpose'] . '|' . $link['target_id'];
			if ( isset( $seen[ $key ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$seen[ $key ] = true;
		}
	}

	private static function validate_fact( $fact, $path, $vocabulary, $sources, $source_receipts, &$scientific_measurement_ids ) {
		$allowed = array( 'id', 'dimension', 'statement', 'evidence_class', 'value_scope', 'source_ids', 'verified_at', 'observed_at', 'public_safe', 'measurement', 'scientific_measurements' );
		self::assert_exact_keys( $fact, $allowed, $path );
		self::assert_identifier( $fact['id'], $path . '.id', 120 );
		if ( ! in_array( $fact['dimension'], $vocabulary['dimensions'], true ) ) {
			throw new RuntimeException( $path . '.dimension' );
		}
		self::assert_translation( $fact['statement'], $path . '.statement', 1600 );
		if ( ! in_array( $fact['evidence_class'], $vocabulary['evidence_classes'], true ) ) {
			throw new RuntimeException( $path . '.evidence_class' );
		}
		if ( ! in_array( $fact['value_scope'], $vocabulary['value_scopes'], true ) ) {
			throw new RuntimeException( $path . '.value_scope' );
		}
		self::assert_identifier_list( $fact['source_ids'], $path . '.source_ids', false );
		foreach ( $fact['source_ids'] as $source_id ) {
			if ( ! isset( $sources[ $source_id ] ) ) {
				throw new RuntimeException( $path . '.unknown_source' );
			}
		}
		self::assert_date( $fact['verified_at'], $path . '.verified_at' );
		self::assert_datetime( $fact['observed_at'], $path . '.observed_at', true );
		if ( '' !== $fact['observed_at'] && $fact['verified_at'] < substr( $fact['observed_at'], 0, 10 ) ) {
			throw new RuntimeException( $path . '.verified_before_observation' );
		}
		if ( ! is_bool( $fact['public_safe'] ) ) {
			throw new RuntimeException( $path . '.public_safe' );
		}
		if ( 'market_observation' === $fact['evidence_class'] && '' === $fact['observed_at'] ) {
			throw new RuntimeException( $path . '.market_observation_without_time' );
		}
		if ( ! is_array( $fact['measurement'] ) ) {
			throw new RuntimeException( $path . '.measurement' );
		}
		if ( ! empty( $fact['measurement'] ) ) {
			self::validate_measurement( $fact['measurement'], $path . '.measurement' );
			if ( 'economic' !== $fact['dimension'] || 'market_observation' !== $fact['evidence_class'] ) {
				throw new RuntimeException( $path . '.measurement_scope' );
			}
			if ( '' === $fact['observed_at'] || $fact['measurement']['observed_at'] !== $fact['observed_at'] ) {
				throw new RuntimeException( $path . '.measurement_observation_time' );
			}
		}
		self::validate_scientific_measurements(
			$fact['scientific_measurements'],
			$path . '.scientific_measurements',
			$vocabulary,
			$sources,
			$source_receipts,
			$fact['source_ids'],
			$scientific_measurement_ids
		);
		if ( ! empty( $fact['scientific_measurements'] ) && 'scientific' !== $fact['dimension'] ) {
			throw new RuntimeException( $path . '.scientific_measurement_scope' );
		}
	}

	private static function validate_scientific_measurements( $measurements, $path, $vocabulary, $sources, $source_receipts, $fact_source_ids, &$scientific_measurement_ids ) {
		self::assert_list( $measurements, $path, true );
		$fact_sources = array_fill_keys( $fact_source_ids, true );
		foreach ( $measurements as $offset => $measurement ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys(
				$measurement,
				array( 'id', 'property', 'kind', 'low', 'high', 'value', 'unit', 'method', 'specimen_scope', 'conditions', 'confidence', 'source_ids', 'measured_at' ),
				$item_path
			);
			self::assert_identifier( $measurement['id'], $item_path . '.id', 120 );
			if ( isset( $scientific_measurement_ids[ $measurement['id'] ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$scientific_measurement_ids[ $measurement['id'] ] = true;
			self::assert_identifier( $measurement['property'], $item_path . '.property', 100 );
			if ( ! in_array( $measurement['kind'], array( 'point', 'range' ), true ) ) {
				throw new RuntimeException( $item_path . '.kind' );
			}
			foreach ( array( 'low', 'high', 'value' ) as $numeric_key ) {
				$numeric_value = $measurement[ $numeric_key ];
				if ( null !== $numeric_value
					&& ( ( ! is_int( $numeric_value ) && ! is_float( $numeric_value ) )
						|| ! is_finite( (float) $numeric_value )
						|| $numeric_value < 0 ) ) {
					throw new RuntimeException( $item_path . '.' . $numeric_key );
				}
			}
			if ( 'point' === $measurement['kind']
				&& ( null === $measurement['value'] || null !== $measurement['low'] || null !== $measurement['high'] ) ) {
				throw new RuntimeException( $item_path . '.point' );
			}
			if ( 'range' === $measurement['kind']
				&& ( null === $measurement['low']
					|| null === $measurement['high']
					|| null !== $measurement['value']
					|| $measurement['low'] > $measurement['high'] ) ) {
				throw new RuntimeException( $item_path . '.range' );
			}
			self::assert_text( $measurement['unit'], $item_path . '.unit', 80 );
			self::assert_text( $measurement['method'], $item_path . '.method', 500 );
			if ( ! in_array( $measurement['specimen_scope'], array( 'literature_context', 'recipe_batch', 'supplier_specification', 'lot_measurement' ), true ) ) {
				throw new RuntimeException( $item_path . '.specimen_scope' );
			}
			self::assert_associative_array( $measurement['conditions'], $item_path . '.conditions', true );
			foreach ( $measurement['conditions'] as $condition => $condition_value ) {
				self::assert_identifier( $condition, $item_path . '.conditions.key', 80 );
				self::assert_text( $condition_value, $item_path . '.conditions.' . $condition, 200 );
			}
			if ( ! in_array( $measurement['confidence'], $vocabulary['confidence_levels'], true ) ) {
				throw new RuntimeException( $item_path . '.confidence' );
			}
			self::assert_identifier_list( $measurement['source_ids'], $item_path . '.source_ids', false );
			foreach ( $measurement['source_ids'] as $source_id ) {
				if ( ! isset( $sources[ $source_id ] ) ) {
					throw new RuntimeException( $item_path . '.unknown_source' );
				}
				if ( ! isset( $fact_sources[ $source_id ] ) ) {
					throw new RuntimeException( $item_path . '.source_outside_fact' );
				}
				if ( 'verified' === $measurement['confidence']
					&& ( ! isset( $source_receipts[ $source_id ] )
						|| 'verified' !== $source_receipts[ $source_id ]['review_state'] ) ) {
					throw new RuntimeException( $item_path . '.verified_source_receipt' );
				}
			}
			self::assert_datetime( $measurement['measured_at'], $item_path . '.measured_at', true );
			if ( 'literature_context' === $measurement['specimen_scope']
				&& ( empty( $measurement['conditions'] ) || '' !== $measurement['measured_at'] ) ) {
				throw new RuntimeException( $item_path . '.literature_context' );
			}
			if ( 'lot_measurement' === $measurement['specimen_scope'] && '' === $measurement['measured_at'] ) {
				throw new RuntimeException( $item_path . '.lot_measurement' );
			}
		}
	}

	private static function validate_measurement( $measurement, $path ) {
		self::assert_exact_keys(
			$measurement,
			array( 'kind', 'low', 'high', 'value', 'currency', 'unit', 'basis', 'tax_status', 'shipping_status', 'observed_at', 'source_url', 'sample_size', 'comparability', 'capture_method', 'snapshot_digest', 'line_items' ),
			$path
		);
		if ( ! in_array( $measurement['kind'], array( 'point', 'range' ), true ) ) {
			throw new RuntimeException( $path . '.kind' );
		}
		foreach ( array( 'low', 'high', 'value' ) as $key ) {
			if ( null !== $measurement[ $key ] && ( ! is_numeric( $measurement[ $key ] ) || (float) $measurement[ $key ] < 0 ) ) {
				throw new RuntimeException( $path . '.' . $key );
			}
		}
		if ( 'range' === $measurement['kind']
			&& ( null === $measurement['low'] || null === $measurement['high'] || (float) $measurement['low'] > (float) $measurement['high'] ) ) {
			throw new RuntimeException( $path . '.range' );
		}
		if ( 'point' === $measurement['kind'] && null === $measurement['value'] ) {
			throw new RuntimeException( $path . '.point' );
		}
		if ( ! preg_match( '/^[A-Z]{3}$/', $measurement['currency'] ) ) {
			throw new RuntimeException( $path . '.currency' );
		}
		self::assert_text( $measurement['unit'], $path . '.unit', 100 );
		self::assert_text( $measurement['basis'], $path . '.basis', 300 );
		if ( ! in_array( $measurement['tax_status'], array( 'included', 'excluded', 'unknown' ), true )
			|| ! in_array( $measurement['shipping_status'], array( 'included', 'excluded', 'unknown' ), true ) ) {
			throw new RuntimeException( $path . '.commercial_scope' );
		}
		self::assert_datetime( $measurement['observed_at'], $path . '.observed_at' );
		if ( ! filter_var( $measurement['source_url'], FILTER_VALIDATE_URL ) || 0 !== strpos( $measurement['source_url'], 'https://' ) ) {
			throw new RuntimeException( $path . '.source_url' );
		}
		if ( ! is_int( $measurement['sample_size'] ) || $measurement['sample_size'] < 1 ) {
			throw new RuntimeException( $path . '.sample_size' );
		}
		if ( ! in_array( $measurement['comparability'], array( 'like_for_like', 'partially_comparable', 'non_comparable' ), true ) ) {
			throw new RuntimeException( $path . '.comparability' );
		}
		self::assert_identifier( $measurement['capture_method'], $path . '.capture_method', 100 );
		if ( '' !== $measurement['snapshot_digest'] && ! preg_match( '/^[a-f0-9]{64}$/', $measurement['snapshot_digest'] ) ) {
			throw new RuntimeException( $path . '.snapshot_digest' );
		}
		self::assert_list( $measurement['line_items'], $path . '.line_items', false );
		if ( count( $measurement['line_items'] ) !== $measurement['sample_size'] ) {
			throw new RuntimeException( $path . '.sample_size_mismatch' );
		}
		foreach ( $measurement['line_items'] as $offset => $line_item ) {
			$line_path = $path . '.line_items.' . $offset;
			self::assert_exact_keys( $line_item, array( 'name', 'price', 'currency', 'tax_status', 'availability', 'source_url', 'attributes' ), $line_path );
			self::assert_text( $line_item['name'], $line_path . '.name', 300 );
			if ( ! is_numeric( $line_item['price'] ) || (float) $line_item['price'] < 0 ) {
				throw new RuntimeException( $line_path . '.price' );
			}
			if ( $line_item['currency'] !== $measurement['currency'] ) {
				throw new RuntimeException( $line_path . '.currency' );
			}
			if ( ! in_array( $line_item['tax_status'], array( 'included', 'excluded', 'unknown' ), true ) ) {
				throw new RuntimeException( $line_path . '.tax_status' );
			}
			self::assert_identifier( $line_item['availability'], $line_path . '.availability', 80 );
			if ( ! filter_var( $line_item['source_url'], FILTER_VALIDATE_URL ) || 0 !== strpos( $line_item['source_url'], 'https://' ) ) {
				throw new RuntimeException( $line_path . '.source_url' );
			}
			self::assert_associative_array( $line_item['attributes'], $line_path . '.attributes', false );
			foreach ( $line_item['attributes'] as $attribute => $attribute_value ) {
				self::assert_identifier( $attribute, $line_path . '.attributes.key', 80 );
				self::assert_text( $attribute_value, $line_path . '.attributes.' . $attribute, 200 );
			}
		}
	}

	private static function validate_taxonomy( $taxonomy, $path, $vocabulary ) {
		self::assert_exact_keys( $taxonomy, array( 'category_path', 'attributes', 'tags', 'public_category_path', 'public_attribute_keys', 'public_tags' ), $path );
		self::assert_identifier_list( $taxonomy['category_path'], $path . '.category_path', false );
		self::assert_associative_array( $taxonomy['attributes'], $path . '.attributes', true );
		foreach ( $taxonomy['attributes'] as $attribute => $values ) {
			if ( ! in_array( $attribute, $vocabulary['allowed_attributes'], true ) ) {
				throw new RuntimeException( $path . '.unknown_attribute' );
			}
			self::assert_identifier_list( $values, $path . '.attributes.' . $attribute, false );
		}
		self::assert_identifier_list( $taxonomy['tags'], $path . '.tags', true );
		self::assert_identifier_list( $taxonomy['public_category_path'], $path . '.public_category_path', true );
		self::assert_identifier_list( $taxonomy['public_attribute_keys'], $path . '.public_attribute_keys', true );
		self::assert_identifier_list( $taxonomy['public_tags'], $path . '.public_tags', true );
		if ( ! empty( array_diff( $taxonomy['public_category_path'], $taxonomy['category_path'] ) )
			|| ! empty( array_diff( $taxonomy['public_attribute_keys'], array_keys( $taxonomy['attributes'] ) ) )
			|| ! empty( array_diff( $taxonomy['public_tags'], $taxonomy['tags'] ) ) ) {
			throw new RuntimeException( $path . '.public_allowlist' );
		}
	}

	private static function validate_relations( $relations, $path, $vocabulary, $sources ) {
		self::assert_list( $relations, $path, true );
		$seen = array();
		foreach ( $relations as $offset => $relation ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $relation, array( 'id', 'type', 'target_id', 'public_safe', 'note', 'evidence_class', 'source_ids', 'valid_from', 'valid_to', 'confidence' ), $item_path );
			self::assert_identifier( $relation['id'], $item_path . '.id', 120 );
			if ( ! in_array( $relation['type'], $vocabulary['relation_types'], true ) ) {
				throw new RuntimeException( $item_path . '.type' );
			}
			self::assert_entity_id( $relation['target_id'], $item_path . '.target_id' );
			if ( ! is_bool( $relation['public_safe'] ) ) {
				throw new RuntimeException( $item_path . '.public_safe' );
			}
			self::assert_translation( $relation['note'], $item_path . '.note', 500 );
			if ( ! in_array( $relation['evidence_class'], $vocabulary['evidence_classes'], true ) ) {
				throw new RuntimeException( $item_path . '.evidence_class' );
			}
			self::assert_identifier_list( $relation['source_ids'], $item_path . '.source_ids', true );
			foreach ( $relation['source_ids'] as $source_id ) {
				if ( ! isset( $sources[ $source_id ] ) ) {
					throw new RuntimeException( $item_path . '.unknown_source' );
				}
			}
			self::assert_date( $relation['valid_from'], $item_path . '.valid_from' );
			self::assert_date( $relation['valid_to'], $item_path . '.valid_to', true );
			if ( '' !== $relation['valid_to'] && $relation['valid_to'] < $relation['valid_from'] ) {
				throw new RuntimeException( $item_path . '.validity_window' );
			}
			if ( ! in_array( $relation['confidence'], $vocabulary['confidence_levels'], true ) ) {
				throw new RuntimeException( $item_path . '.confidence' );
			}
			if ( $relation['public_safe'] && ( empty( $relation['source_ids'] ) || 'pending' === $relation['confidence'] ) ) {
				throw new RuntimeException( $item_path . '.public_without_evidence' );
			}
			$key = $relation['type'] . '|' . $relation['target_id'];
			if ( isset( $seen[ $key ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$seen[ $key ] = true;
		}
	}

	private static function validate_commerce( $commerce, $path, $vocabulary ) {
		self::assert_exact_keys( $commerce, array( 'state', 'woo_product_code', 'public_offer_allowed', 'product_copy', 'cross_sell_ids', 'up_sell_ids', 'business_model' ), $path );
		if ( ! in_array( $commerce['state'], $vocabulary['commerce_states'], true ) ) {
			throw new RuntimeException( $path . '.state' );
		}
		if ( '' !== $commerce['woo_product_code'] ) {
			self::assert_identifier( $commerce['woo_product_code'], $path . '.woo_product_code', 100 );
		}
		if ( ! is_bool( $commerce['public_offer_allowed'] ) ) {
			throw new RuntimeException( $path . '.public_offer_allowed' );
		}
		self::assert_translation( $commerce['product_copy'], $path . '.product_copy', 3000 );
		self::assert_identifier_list( $commerce['cross_sell_ids'], $path . '.cross_sell_ids', true );
		self::assert_identifier_list( $commerce['up_sell_ids'], $path . '.up_sell_ids', true );
		self::validate_business_model( $commerce['business_model'], $path . '.business_model', $vocabulary );
		if ( $commerce['public_offer_allowed'] && ( 'active_offer' !== $commerce['state'] || '' === $commerce['woo_product_code'] ) ) {
			throw new RuntimeException( $path . '.offer_without_verified_sku' );
		}
		if ( in_array( $commerce['state'], array( 'verified_sku', 'active_offer' ), true ) && '' === $commerce['woo_product_code'] ) {
			throw new RuntimeException( $path . '.sku_without_product_code' );
		}
		if ( ! in_array( $commerce['state'], array( 'verified_sku', 'active_offer' ), true ) && '' !== $commerce['woo_product_code'] ) {
			throw new RuntimeException( $path . '.held_with_product_code' );
		}
		if ( 'active_offer' === $commerce['state'] && 'approved_sell_price' !== $commerce['business_model']['pricing_state'] ) {
			throw new RuntimeException( $path . '.active_without_approved_price' );
		}
	}

	private static function validate_business_model( $model, $path, $vocabulary ) {
		self::assert_exact_keys(
			$model,
			array( 'revenue_models', 'customer_segments', 'value_proposition', 'pricing_state', 'market_scope', 'observation_entity_ids', 'margin_scenario' ),
			$path
		);
		self::assert_identifier_list( $model['revenue_models'], $path . '.revenue_models', false );
		foreach ( $model['revenue_models'] as $revenue_model ) {
			if ( ! in_array( $revenue_model, $vocabulary['revenue_models'], true ) ) {
				throw new RuntimeException( $path . '.revenue_models.value' );
			}
		}
		self::assert_identifier_list( $model['customer_segments'], $path . '.customer_segments', false );
		foreach ( $model['customer_segments'] as $segment ) {
			if ( ! in_array( $segment, $vocabulary['customer_segments'], true ) ) {
				throw new RuntimeException( $path . '.customer_segments.value' );
			}
		}
		self::assert_translation( $model['value_proposition'], $path . '.value_proposition', 1200 );
		if ( ! in_array( $model['pricing_state'], $vocabulary['pricing_states'], true ) ) {
			throw new RuntimeException( $path . '.pricing_state' );
		}
		if ( ! in_array( $model['market_scope'], $vocabulary['market_scopes'], true ) ) {
			throw new RuntimeException( $path . '.market_scope' );
		}
		self::assert_identifier_list( $model['observation_entity_ids'], $path . '.observation_entity_ids', true );
		self::validate_margin_scenario( $model['margin_scenario'], $path . '.margin_scenario', $vocabulary );
	}

	private static function validate_margin_scenario( $scenario, $path, $vocabulary ) {
		self::assert_exact_keys(
			$scenario,
			array( 'currency', 'landed_cost_low', 'landed_cost_high', 'retail_price_low', 'retail_price_high', 'gross_margin_low', 'gross_margin_high', 'basis', 'confidence', 'reviewed_at' ),
			$path
		);
		if ( '' !== $scenario['currency'] && ! preg_match( '/^[A-Z]{3}$/', $scenario['currency'] ) ) {
			throw new RuntimeException( $path . '.currency' );
		}
		foreach ( array( 'landed_cost_low', 'landed_cost_high', 'retail_price_low', 'retail_price_high', 'gross_margin_low', 'gross_margin_high' ) as $key ) {
			if ( null !== $scenario[ $key ] && ( ! is_numeric( $scenario[ $key ] ) || (float) $scenario[ $key ] < 0 ) ) {
				throw new RuntimeException( $path . '.' . $key );
			}
		}
		if ( null !== $scenario['landed_cost_low'] && null !== $scenario['landed_cost_high'] && (float) $scenario['landed_cost_low'] > (float) $scenario['landed_cost_high'] ) {
			throw new RuntimeException( $path . '.landed_cost_range' );
		}
		if ( null !== $scenario['retail_price_low'] && null !== $scenario['retail_price_high'] && (float) $scenario['retail_price_low'] > (float) $scenario['retail_price_high'] ) {
			throw new RuntimeException( $path . '.retail_price_range' );
		}
		foreach ( array( 'gross_margin_low', 'gross_margin_high' ) as $key ) {
			if ( null !== $scenario[ $key ] && (float) $scenario[ $key ] > 1 ) {
				throw new RuntimeException( $path . '.' . $key );
			}
		}
		if ( null !== $scenario['gross_margin_low'] && null !== $scenario['gross_margin_high'] && (float) $scenario['gross_margin_low'] > (float) $scenario['gross_margin_high'] ) {
			throw new RuntimeException( $path . '.gross_margin_range' );
		}
		self::assert_translation( $scenario['basis'], $path . '.basis', 1200 );
		if ( ! in_array( $scenario['confidence'], $vocabulary['confidence_levels'], true ) ) {
			throw new RuntimeException( $path . '.confidence' );
		}
		self::assert_date( $scenario['reviewed_at'], $path . '.reviewed_at', true );
	}

	private static function validate_visual( $visual, $path, $vocabulary ) {
		self::assert_exact_keys( $visual, array( 'asset_state', 'prompt_en', 'negative_prompt_en', 'ratios', 'shot_list', 'rights_method', 'rights_state', 'rights_receipt_digest' ), $path );
		if ( ! in_array( $visual['asset_state'], $vocabulary['asset_states'], true ) ) {
			throw new RuntimeException( $path . '.asset_state' );
		}
		self::assert_text( $visual['prompt_en'], $path . '.prompt_en', 3000, 40 );
		self::assert_text( $visual['negative_prompt_en'], $path . '.negative_prompt_en', 1000, 10 );
		self::assert_exact_list( $visual['ratios'], array( '1:1', '4:5', '4:3', '16:9' ), $path . '.ratios' );
		self::assert_text_list( $visual['shot_list'], $path . '.shot_list', false, 20 );
		self::assert_identifier( $visual['rights_method'], $path . '.rights_method', 100 );
		if ( ! in_array( $visual['rights_state'], $vocabulary['rights_states'], true ) ) {
			throw new RuntimeException( $path . '.rights_state' );
		}
		if ( '' !== $visual['rights_receipt_digest'] && 1 !== preg_match( '/\Asha256:[a-f0-9]{64}\z/', $visual['rights_receipt_digest'] ) ) {
			throw new RuntimeException( $path . '.rights_receipt_digest' );
		}
		if ( in_array( $visual['rights_state'], array( 'cleared_owned', 'cleared_generated', 'cleared_licensed' ), true ) && '' === $visual['rights_receipt_digest'] ) {
			throw new RuntimeException( $path . '.cleared_without_receipt' );
		}
		if ( 'approved' === $visual['asset_state']
			&& ! in_array( $visual['rights_state'], array( 'cleared_owned', 'cleared_generated', 'cleared_licensed' ), true ) ) {
			throw new RuntimeException( $path . '.approved_without_cleared_rights' );
		}
	}

	private static function validate_compliance( $notes, $path, $sources ) {
		self::assert_list( $notes, $path, true );
		foreach ( $notes as $offset => $record ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $record, array( 'code', 'note', 'public_safe', 'source_ids' ), $item_path );
			self::assert_identifier( $record['code'], $item_path . '.code', 100 );
			self::assert_translation( $record['note'], $item_path . '.note', 1200 );
			foreach ( array( 'he', 'en' ) as $language ) {
				if ( 0 !== strpos( $record['note'][ $language ], '[COMPLIANCE_NOTE:' )
					|| ']' !== substr( $record['note'][ $language ], -1 ) ) {
					throw new RuntimeException( $item_path . '.note_format' );
				}
			}
			if ( ! is_bool( $record['public_safe'] ) ) {
				throw new RuntimeException( $item_path . '.public_safe' );
			}
			self::assert_identifier_list( $record['source_ids'], $item_path . '.source_ids', true );
			if ( $record['public_safe'] && empty( $record['source_ids'] ) ) {
				throw new RuntimeException( $item_path . '.public_without_source' );
			}
			foreach ( $record['source_ids'] as $source_id ) {
				if ( ! isset( $sources[ $source_id ] ) ) {
					throw new RuntimeException( $item_path . '.unknown_source' );
				}
			}
		}
	}

	private static function validate_trust( $trust, $path, $vocabulary ) {
		self::assert_exact_keys(
			$trust,
			array( 'attribution_state', 'research_method', 'user_purpose', 'commercial_purpose', 'correction_path', 'substantive_updated_at', 'next_review_trigger' ),
			$path
		);
		if ( ! in_array( $trust['attribution_state'], $vocabulary['attribution_states'], true ) ) {
			throw new RuntimeException( $path . '.attribution_state' );
		}
		self::assert_translation( $trust['research_method'], $path . '.research_method', 900 );
		self::assert_translation( $trust['user_purpose'], $path . '.user_purpose', 500 );
		self::assert_translation( $trust['commercial_purpose'], $path . '.commercial_purpose', 500 );
		self::assert_translation( $trust['correction_path'], $path . '.correction_path', 200 );
		if ( '/contact/' !== $trust['correction_path']['he'] || '/en/contact/' !== $trust['correction_path']['en'] ) {
			throw new RuntimeException( $path . '.correction_path' );
		}
		self::assert_date( $trust['substantive_updated_at'], $path . '.substantive_updated_at' );
		self::assert_translation( $trust['next_review_trigger'], $path . '.next_review_trigger', 700 );
	}

	private static function validate_review( $review, $path ) {
		self::assert_exact_keys( $review, array( 'status', 'reviewed_at', 'next_review_at', 'language_status', 'culinary_test_status' ), $path );
		if ( ! in_array( $review['status'], array( 'research_draft', 'source_reviewed', 'verified' ), true ) ) {
			throw new RuntimeException( $path . '.status' );
		}
		self::assert_date( $review['reviewed_at'], $path . '.reviewed_at' );
		self::assert_date( $review['next_review_at'], $path . '.next_review_at' );
		if ( $review['next_review_at'] < $review['reviewed_at'] ) {
			throw new RuntimeException( $path . '.review_window' );
		}
		if ( ! in_array( $review['language_status'], array( 'draft_bilingual', 'reviewed_bilingual' ), true )
			|| ! in_array( $review['culinary_test_status'], array( 'not_applicable', 'pending', 'tested' ), true ) ) {
			throw new RuntimeException( $path . '.review_state' );
		}
	}

	private static function validate_entity_graph( $entity, $entities_by_id, $children_by_parent, $vocabulary ) {
		$path = 'registry.entities.' . $entity['id'];
		$public_entity = self::is_public_entity( $entity );
		if ( '' !== $entity['parent_id'] && ! isset( $entities_by_id[ $entity['parent_id'] ] ) ) {
			throw new RuntimeException( $path . '.unknown_parent' );
		}
		if ( $public_entity && '' !== $entity['parent_id'] && ! self::is_public_entity( $entities_by_id[ $entity['parent_id'] ] ) ) {
			throw new RuntimeException( $path . '.public_parent_private' );
		}
		$references = array();
		foreach ( $entity['relations'] as $relation ) {
			$references[] = $relation['target_id'];
			if ( $public_entity && true === $relation['public_safe'] ) {
				$target = isset( $entities_by_id[ $relation['target_id'] ] ) ? $entities_by_id[ $relation['target_id'] ] : null;
				if ( ! is_array( $target ) || ! self::is_public_entity( $target ) ) {
					throw new RuntimeException( $path . '.public_relation_private_target' );
				}
			}
		}
		$references = array_merge(
			$references,
			$entity['commerce']['cross_sell_ids'],
			$entity['commerce']['up_sell_ids'],
			$entity['commerce']['business_model']['observation_entity_ids']
		);
		foreach ( $references as $reference ) {
			if ( $reference === $entity['id'] || ! isset( $entities_by_id[ $reference ] ) ) {
				throw new RuntimeException( $path . '.unresolved_relation' );
			}
		}
		foreach ( $entity['commerce']['business_model']['observation_entity_ids'] as $observation_id ) {
			$observation = $entities_by_id[ $observation_id ];
			if ( ! in_array( $observation['type'], array( 'retail_listing', 'market_observation' ), true ) ) {
				throw new RuntimeException( $path . '.observation_type' );
			}
			$subjects = array( $observation['parent_id'] );
			foreach ( $observation['relations'] as $relation ) {
				$subjects[] = $relation['target_id'];
			}
			if ( ! in_array( $entity['id'], $subjects, true ) ) {
				throw new RuntimeException( $path . '.observation_subject' );
			}
		}

		$hub_id = $entity['seo']['hub_entity_id'];
		if ( ! isset( $entities_by_id[ $hub_id ] )
			|| 'pillar' !== $entities_by_id[ $hub_id ]['seo']['page_role']
			|| $entity['seo']['cluster_id'] !== $entities_by_id[ $hub_id ]['seo']['cluster_id'] ) {
			throw new RuntimeException( $path . '.hub_contract' );
		}
		if ( $public_entity && ! self::is_public_entity( $entities_by_id[ $hub_id ] ) ) {
			throw new RuntimeException( $path . '.public_hub_private' );
		}
		if ( 'pillar' === $entity['seo']['page_role'] && $entity['id'] !== $hub_id ) {
			throw new RuntimeException( $path . '.pillar_identity' );
		}

		$owner_id = $entity['seo']['owner_entity_id'];
		if ( ! isset( $entities_by_id[ $owner_id ] ) ) {
			throw new RuntimeException( $path . '.unknown_seo_owner' );
		}
		if ( $public_entity && ! self::is_public_entity( $entities_by_id[ $owner_id ] ) ) {
			throw new RuntimeException( $path . '.public_owner_private' );
		}
		if ( 'section' === $entity['seo']['route_mode'] ) {
			if ( $owner_id === $entity['id'] || 'standalone' !== $entities_by_id[ $owner_id ]['seo']['route_mode']
				|| $entity['seo']['canonical_path'] !== $entities_by_id[ $owner_id ]['seo']['canonical_path']
				|| $entity['seo']['visible_breadcrumbs'] !== $entities_by_id[ $owner_id ]['seo']['visible_breadcrumbs'] ) {
				throw new RuntimeException( $path . '.section_owner_contract' );
			}
		} elseif ( $owner_id !== $entity['id'] ) {
			throw new RuntimeException( $path . '.standalone_owner_contract' );
		}

		$breadcrumb = self::entity_parent_chain( $entity['id'], $entities_by_id );
		if ( $breadcrumb !== array_values( $entity['seo']['breadcrumb_entity_ids'] ) ) {
			throw new RuntimeException( $path . '.breadcrumb_contract' );
		}
		if ( $public_entity ) {
			foreach ( $breadcrumb as $breadcrumb_id ) {
				if ( ! self::is_public_entity( $entities_by_id[ $breadcrumb_id ] ) ) {
					throw new RuntimeException( $path . '.public_breadcrumb_private' );
				}
			}
		}

		$children = isset( $children_by_parent[ $entity['id'] ] ) ? $children_by_parent[ $entity['id'] ] : array();
		if ( $children !== array_values( $entity['seo']['expected_child_ids'] ) ) {
			throw new RuntimeException( $path . '.expected_children_contract' );
		}

		foreach ( $entity['seo']['semantic_entity_ids'] as $semantic_id ) {
			if ( $semantic_id === $entity['id'] || ! isset( $entities_by_id[ $semantic_id ] ) ) {
				throw new RuntimeException( $path . '.semantic_entity_reference' );
			}
			if ( $public_entity && ! self::is_public_entity( $entities_by_id[ $semantic_id ] ) ) {
				throw new RuntimeException( $path . '.public_semantic_private' );
			}
		}
		$relation_ids = array();
		foreach ( $entity['relations'] as $relation ) {
			$relation_ids[ $relation['id'] ] = true;
		}
		foreach ( $entity['seo']['link_plan'] as $link ) {
			if ( ! isset( $entities_by_id[ $link['target_id'] ] ) || $link['target_id'] === $entity['id'] ) {
				throw new RuntimeException( $path . '.link_plan_target' );
			}
			if ( '' !== $link['basis_relation_id'] && ! isset( $relation_ids[ $link['basis_relation_id'] ] ) ) {
				throw new RuntimeException( $path . '.link_plan_relation' );
			}
			if ( $public_entity && $link['public_safe'] ) {
				$target = $entities_by_id[ $link['target_id'] ];
				if ( ! self::is_public_entity( $target ) ) {
					throw new RuntimeException( $path . '.public_link_private_target' );
				}
			}
		}
		if ( 'pillar' !== $entity['seo']['page_role'] && '' === $entity['parent_id'] ) {
			throw new RuntimeException( $path . '.orphan_owner' );
		}

		if ( 'market_observation' === $entity['type'] ) {
			$measurements = array_filter(
				$entity['facts'],
				static function ( $fact ) {
					return 'economic' === $fact['dimension'] && ! empty( $fact['measurement'] );
				}
			);
			if ( empty( $measurements ) || 'editorial_draft' !== $entity['surface_class'] ) {
				throw new RuntimeException( $path . '.market_observation_contract' );
			}
		}
	}

	private static function validate_collections( $collections, $entities_by_id ) {
		self::assert_list( $collections, 'registry.collections', false );
		$collection_keys = array();
		$translation_groups = array();
		$owner_ids = array();
		foreach ( $collections as $offset => $collection ) {
			$path = 'registry.collections.' . $offset;
			self::assert_exact_keys(
				$collection,
				array( 'key', 'owner_entity_id', 'navigation', 'translation_group_id', 'route', 'index_reason', 'receipt', 'display', 'public_projection' ),
				$path
			);
			self::assert_identifier( $collection['key'], $path . '.key', 100 );
			self::assert_entity_id( $collection['owner_entity_id'], $path . '.owner_entity_id' );
			self::assert_identifier( $collection['translation_group_id'], $path . '.translation_group_id', 120 );
			if ( isset( $collection_keys[ $collection['key'] ] ) ) {
				throw new RuntimeException( $path . '.duplicate_key' );
			}
			if ( isset( $translation_groups[ $collection['translation_group_id'] ] ) ) {
				throw new RuntimeException( $path . '.duplicate_translation_group' );
			}
			if ( isset( $owner_ids[ $collection['owner_entity_id'] ] ) ) {
				throw new RuntimeException( $path . '.duplicate_owner' );
			}
			$collection_keys[ $collection['key'] ] = true;
			$translation_groups[ $collection['translation_group_id'] ] = true;
			$owner_ids[ $collection['owner_entity_id'] ] = true;

			self::assert_exact_keys( $collection['navigation'], array( 'parent_entity_id', 'group_order', 'member_ids_by_group' ), $path . '.navigation' );
			self::assert_entity_id( $collection['navigation']['parent_entity_id'], $path . '.navigation.parent_entity_id' );
			self::assert_identifier_list( $collection['navigation']['group_order'], $path . '.navigation.group_order', false );
			self::assert_exact_keys(
				$collection['navigation']['member_ids_by_group'],
				$collection['navigation']['group_order'],
				$path . '.navigation.member_ids_by_group'
			);

			self::assert_exact_keys( $collection['route'], array( 'mode', 'canonical_path' ), $path . '.route' );
			if ( 'standalone' !== $collection['route']['mode'] ) {
				throw new RuntimeException( $path . '.route.mode' );
			}
			self::assert_translation( $collection['route']['canonical_path'], $path . '.route.canonical_path', 300 );
			if ( ! preg_match( '#^/museum/(?:[a-z0-9-]+/)+$#', $collection['route']['canonical_path']['he'] )
				|| ! preg_match( '#^/en/museum/(?:[a-z0-9-]+/)+$#', $collection['route']['canonical_path']['en'] ) ) {
				throw new RuntimeException( $path . '.route.canonical_path' );
			}
			self::assert_translation( $collection['index_reason'], $path . '.index_reason', 500 );

			self::assert_exact_keys( $collection['receipt'], array( 'state', 'recorded_at', 'membership_digest' ), $path . '.receipt' );
			self::assert_identifier( $collection['receipt']['state'], $path . '.receipt.state', 100 );
			self::assert_date( $collection['receipt']['recorded_at'], $path . '.receipt.recorded_at' );
			if ( 1 !== preg_match( '/\Asha256:[a-f0-9]{64}\z/', $collection['receipt']['membership_digest'] ) ) {
				throw new RuntimeException( $path . '.receipt.membership_digest' );
			}

			self::assert_exact_keys( $collection['display'], array( 'title', 'description', 'hero_entity_id', 'groups' ), $path . '.display' );
			self::assert_translation( $collection['display']['title'], $path . '.display.title', 200 );
			self::assert_translation( $collection['display']['description'], $path . '.display.description', 900 );
			self::assert_entity_id( $collection['display']['hero_entity_id'], $path . '.display.hero_entity_id' );
			self::assert_list( $collection['display']['groups'], $path . '.display.groups', false );
			$display_group_ids = array();
			foreach ( $collection['display']['groups'] as $group_offset => $group ) {
				$group_path = $path . '.display.groups.' . $group_offset;
				self::assert_exact_keys( $group, array( 'id', 'label', 'description' ), $group_path );
				self::assert_identifier( $group['id'], $group_path . '.id', 80 );
				self::assert_translation( $group['label'], $group_path . '.label', 120 );
				self::assert_translation( $group['description'], $group_path . '.description', 500 );
				$display_group_ids[] = $group['id'];
			}
			if ( $display_group_ids !== array_values( $collection['navigation']['group_order'] ) ) {
				throw new RuntimeException( $path . '.display.group_order' );
			}

			self::assert_exact_keys( $collection['public_projection'], array( 'enabled', 'schema' ), $path . '.public_projection' );
			if ( ! is_bool( $collection['public_projection']['enabled'] )
				|| 'complete99-culinary-collection-public/v1' !== $collection['public_projection']['schema'] ) {
				throw new RuntimeException( $path . '.public_projection' );
			}

			$owner_id = $collection['owner_entity_id'];
			if ( ! isset( $entities_by_id[ $owner_id ] ) ) {
				throw new RuntimeException( $path . '.unknown_owner' );
			}
			$owner = $entities_by_id[ $owner_id ];
			if ( 'topic_hub' !== $owner['type']
				|| 'standalone' !== $owner['seo']['route_mode']
				|| $owner_id !== $owner['seo']['owner_entity_id']
				|| $collection['route']['canonical_path'] !== $owner['seo']['canonical_path']
				|| $collection['navigation']['parent_entity_id'] !== $owner['parent_id']
				|| $collection['display']['title'] !== $owner['name'] ) {
				throw new RuntimeException( $path . '.owner_contract' );
			}

			$hero_id = $collection['display']['hero_entity_id'];
			if ( ! isset( $entities_by_id[ $hero_id ] ) || ! self::is_public_entity( $entities_by_id[ $hero_id ] ) ) {
				throw new RuntimeException( $path . '.display.hero_not_public' );
			}
			if ( $collection['public_projection']['enabled']
				&& ( ! self::is_public_entity( $owner )
					|| 'noindex_until_longform_review' !== $owner['index_policy']
					|| true === $owner['publication']['search_index'] ) ) {
				throw new RuntimeException( $path . '.public_owner_contract' );
			}

			if ( 'japanese-foundations-lab' === $collection['key'] ) {
				self::assert_exact_list(
					$collection['navigation']['group_order'],
					array( 'ingredients', 'food_science', 'techniques', 'equipment' ),
					$path . '.navigation.group_order'
				);
			}
			$allowed_group_types = array(
				'ingredients'  => array( 'ingredient' ),
				'food_science' => array( 'guide', 'molecule', 'reaction', 'standard' ),
				'techniques'   => array( 'preparation', 'technique' ),
				'equipment'    => array( 'equipment' ),
			);
			$member_ids = array();
			foreach ( $collection['navigation']['group_order'] as $group_id ) {
				if ( ! isset( $allowed_group_types[ $group_id ] ) ) {
					throw new RuntimeException( $path . '.navigation.unknown_group' );
				}
				$group_members = $collection['navigation']['member_ids_by_group'][ $group_id ];
				self::assert_identifier_list( $group_members, $path . '.navigation.member_ids_by_group.' . $group_id, false );
				foreach ( $group_members as $member_id ) {
					if ( isset( $member_ids[ $member_id ] ) ) {
						throw new RuntimeException( $path . '.navigation.duplicate_member' );
					}
					if ( ! isset( $entities_by_id[ $member_id ] ) ) {
						throw new RuntimeException( $path . '.navigation.unknown_member' );
					}
					$member = $entities_by_id[ $member_id ];
					if ( ! in_array( $member['type'], $allowed_group_types[ $group_id ], true ) ) {
						throw new RuntimeException( $path . '.navigation.member_type' );
					}
					if ( '' !== self::public_exposure_denial_reason( $member )
						|| ! self::is_public_entity( $member ) ) {
						throw new RuntimeException( $path . '.navigation.member_not_public' );
					}
					if ( $member_id === $owner_id
						|| $member['parent_id'] === $owner_id
						|| $member['seo']['owner_entity_id'] === $owner_id
						|| ! in_array( $member['seo']['route_mode'], array( 'standalone', 'section' ), true ) ) {
						throw new RuntimeException( $path . '.navigation.presentation_only' );
					}
					$member_ids[ $member_id ] = true;
				}
			}
			if ( $collection['receipt']['membership_digest'] !== self::collection_membership_digest( $collection ) ) {
				throw new RuntimeException( $path . '.receipt.membership_mismatch' );
			}
		}
	}

	private static function collection_membership_digest( $collection ) {
		$tokens = array();
		foreach ( $collection['navigation']['group_order'] as $group_id ) {
			foreach ( $collection['navigation']['member_ids_by_group'][ $group_id ] as $member_id ) {
				$tokens[] = $group_id . ':' . $member_id;
			}
		}
		$basis = $collection['key'] . '|' . $collection['owner_entity_id'] . '|' . implode( '|', $tokens );
		return 'sha256:' . hash( 'sha256', $basis );
	}

	private static function public_exposure_denial_reason( $entity ) {
		if ( in_array( $entity['type'], array( 'supplier', 'retail_listing', 'market_observation', 'guide_edition', 'visual_asset' ), true ) ) {
			return 'entity_type_' . $entity['type'];
		}
		if ( 'compliance_rule' === $entity['type'] && 'public_discovery' !== $entity['surface_class'] ) {
			return 'private_compliance';
		}
		return '';
	}

	private static function entity_parent_chain( $entity_id, $entities_by_id ) {
		$chain  = array();
		$cursor = $entity_id;
		while ( '' !== $cursor ) {
			array_unshift( $chain, $cursor );
			$cursor = isset( $entities_by_id[ $cursor ] ) ? $entities_by_id[ $cursor ]['parent_id'] : '';
		}
		return $chain;
	}

	private static function assert_parent_chain_is_acyclic( $entity_id, $entities_by_id ) {
		$seen   = array();
		$cursor = $entity_id;
		while ( '' !== $cursor ) {
			if ( isset( $seen[ $cursor ] ) ) {
				throw new RuntimeException( 'registry.parent_cycle.' . $entity_id );
			}
			$seen[ $cursor ] = true;
			$cursor          = isset( $entities_by_id[ $cursor ] ) ? $entities_by_id[ $cursor ]['parent_id'] : '';
		}
	}

	/**
	 * Fail a migration checkpoint if the bundled ontology drifts.
	 */
	public static function assert_invariants() {
		$registry = self::registry( true );
		if ( is_wp_error( $registry ) ) {
			throw new RuntimeException( 'culinary-science-registry-invariants' );
		}
		return true;
	}

	/**
	 * Return a bounded health summary with no editorial details.
	 */
	public static function status() {
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return array(
				'ready'         => false,
				'version'       => '',
				'entity_count'  => 0,
				'public_count'  => 0,
				'cluster_count' => 0,
				'digest'        => '',
			);
		}
		$public = self::public_entities( $registry );
		$clusters = array();
		foreach ( $registry['entities'] as $entity ) {
			$clusters[ $entity['seo']['cluster_id'] ] = true;
		}
		return array(
			'ready'         => true,
			'version'       => $registry['version'],
			'entity_count'  => count( $registry['entities'] ),
			'public_count'  => count( $public ),
			'cluster_count' => count( $clusters ),
			'digest'        => self::registry_digest( $registry ),
		);
	}

	public static function rest_public_collection( WP_REST_Request $request ) {
		$lang     = self::request_language( $request );
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return $registry;
		}
		$type = sanitize_key( (string) $request->get_param( 'type' ) );
		$cluster_id = sanitize_key( (string) $request->get_param( 'cluster_id' ) );
		$cursor = sanitize_key( (string) $request->get_param( 'cursor' ) );
		$requested_limit = $request->get_param( 'limit' );
		$limit = null === $requested_limit || '' === $requested_limit
			? 50
			: max( 1, min( 100, absint( $requested_limit ) ) );
		$matching = array_values(
			array_filter(
				self::public_entities( $registry ),
				static function ( $entity ) use ( $type, $cluster_id, $cursor ) {
					return ( '' === $type || $type === $entity['type'] )
						&& ( '' === $cluster_id || $cluster_id === $entity['seo']['cluster_id'] )
						&& ( '' === $cursor || strcmp( $entity['id'], $cursor ) > 0 );
				}
			)
		);
		usort(
			$matching,
			static function ( $left, $right ) {
				return strcmp( $left['id'], $right['id'] );
			}
		);
		$page = array_slice( $matching, 0, $limit );
		$entities = array();
		foreach ( $page as $entity ) {
			$entities[] = self::public_projection( $entity, $registry, $lang );
		}
		$next_cursor = count( $matching ) > count( $page ) && ! empty( $page )
			? $page[ count( $page ) - 1 ]['id']
			: '';
		return rest_ensure_response(
			array(
				'schema'       => 'complete99-culinary-science-public/v3',
				'version'      => $registry['version'],
				'generated_at' => $registry['generated_at'],
				'language'     => $lang,
				'count'        => count( $entities ),
				'next_cursor'  => $next_cursor,
				'filters'      => array( 'type' => $type, 'cluster_id' => $cluster_id ),
				'entities'     => $entities,
			)
		);
	}

	public static function rest_public_entity( WP_REST_Request $request ) {
		$lang      = self::request_language( $request );
		$entity_id = (string) $request->get_param( 'entity_id' );
		$registry  = self::registry();
		if ( is_wp_error( $registry ) ) {
			return $registry;
		}
		foreach ( self::public_entities( $registry ) as $entity ) {
			if ( hash_equals( $entity['id'], $entity_id ) ) {
				return rest_ensure_response(
					array(
						'schema'   => 'complete99-culinary-science-entity/v3',
						'version'  => $registry['version'],
						'language' => $lang,
						'entity'   => self::public_projection( $entity, $registry, $lang ),
					)
				);
			}
		}
		return new WP_Error( 'complete99_science_entity_not_found', 'The requested public culinary entity was not found.', array( 'status' => 404 ) );
	}

	public static function rest_editorial_snapshot() {
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return $registry;
		}
		return rest_ensure_response(
			array(
				'schema'   => 'complete99-culinary-science-editorial/v2',
				'digest'   => self::registry_digest( $registry ),
				'registry' => $registry,
			)
		);
	}

	/**
	 * Full private registry for the capability-gated WordPress review lab.
	 */
	public static function editorial_snapshot() {
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return array();
		}
		return array(
			'digest'   => self::registry_digest( $registry ),
			'registry' => $registry,
		);
	}

	/**
	 * Export standalone planned and approved owners into the global SEO ledger.
	 * Section entities remain query concepts of their owning page and do not
	 * create duplicate canonical rows.
	 */
	public static function seo_owner_records() {
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return array();
		}
		$records = array();
		foreach ( $registry['entities'] as $entity ) {
			if ( 'standalone' !== $entity['seo']['route_mode'] ) {
				continue;
			}
			foreach ( array( 'he', 'en' ) as $language ) {
				$secondary = array_slice( $entity['seo']['query_variants'][ $language ], 1 );
				$records[] = array(
					'language'                   => $language,
					'translation_key'            => 'science-' . $entity['id'],
					'primary_intent'             => $entity['seo']['primary_keyword'][ $language ],
					'canonical_path'             => $entity['seo']['canonical_path'][ $language ],
					'secondary_queries'          => empty( $secondary ) ? $entity['seo']['primary_keyword'][ $language ] : implode( '; ', $secondary ),
					'prohibited_competing_pages' => implode( '; ', $entity['seo']['protected_exclusions'][ $language ] ),
					'evidence_gate'              => 'reviewed_bilingual; approved_public; public_page; publication evidence and rights gates',
					'publication_status'         => 'approved_public' === $entity['publication']['state'] ? 'approved' : 'planned-private',
				);
			}
		}
		return $records;
	}

	private static function public_entities( $registry ) {
		return array_values( self::public_entity_index( $registry ) );
	}

	private static function public_entity_index( $registry ) {
		$key = isset( $registry['version'] ) ? (string) $registry['version'] : 'unknown';
		if ( isset( self::$public_index_cache[ $key ] ) ) {
			return self::$public_index_cache[ $key ];
		}
		$index = array();
		foreach ( $registry['entities'] as $entity ) {
			if ( ! self::is_public_entity( $entity ) ) {
				continue;
			}
			$index[ $entity['id'] ] = $entity;
		}
		self::$public_index_cache[ $key ] = $index;
		return $index;
	}

	private static function is_public_entity( $entity ) {
		if ( '' !== self::public_exposure_denial_reason( $entity )
			|| 'public_discovery' !== $entity['surface_class']
			|| 'approved_public' !== $entity['publication']['state']
			|| true !== $entity['publication']['public_api']
			|| true !== $entity['publication']['public_page']
			|| 'private' === $entity['seo']['route_mode']
			|| 'reviewed_bilingual' !== $entity['review']['language_status']
			|| ! in_array( $entity['review']['status'], array( 'source_reviewed', 'verified' ), true )
			|| 'approved' !== $entity['visual']['asset_state']
			|| ! in_array( $entity['visual']['rights_state'], array( 'cleared_owned', 'cleared_generated', 'cleared_licensed' ), true )
			|| '' === $entity['visual']['rights_receipt_digest']
			|| 'pending_named_review' === $entity['trust']['attribution_state']
			|| ( 'dish' === $entity['type'] && 'tested' !== $entity['review']['culinary_test_status'] )
			|| ( 'preparation' === $entity['type'] && 'Recipe' === $entity['seo']['schema_type'] && 'tested' !== $entity['review']['culinary_test_status'] )
			|| ( 'preparation' === $entity['type'] && 'not_applicable' === $entity['review']['culinary_test_status'] && ( 'Recipe' === $entity['seo']['schema_type'] || true === $entity['publication']['search_index'] ) ) ) {
			return false;
		}
		if ( true === $entity['publication']['search_index']
			&& ( 'index' !== $entity['index_policy'] || 'standalone' !== $entity['seo']['route_mode'] ) ) {
			return false;
		}
		$has_public_fact = false;
		foreach ( $entity['facts'] as $fact ) {
			if ( ! $fact['public_safe'] ) {
				continue;
			}
			$has_public_fact = true;
			if ( 'editorial_inference' === $fact['evidence_class'] ) {
				return false;
			}
		}
		return $has_public_fact;
	}

	/**
	 * Resolve an exact approved canonical path to a projection-only page bundle.
	 *
	 * @param string $path Absolute-path portion of the request URI.
	 * @return array
	 */
	public static function public_page_bundle_for_path( $path ) {
		$path = (string) $path;
		if ( ! preg_match( '#^/(?:[a-z0-9-]+/)*$#', $path ) ) {
			return array();
		}
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return array();
		}
		foreach ( self::public_entity_index( $registry ) as $entity ) {
			if ( 'standalone' !== $entity['seo']['route_mode'] ) {
				continue;
			}
			foreach ( array( 'he', 'en' ) as $lang ) {
				if ( hash_equals( $entity['seo']['canonical_path'][ $lang ], $path ) ) {
					return self::build_public_page_bundle( $entity, $registry, $lang );
				}
			}
		}
		return array();
	}

	/**
	 * Resolve an approved entity or owned section to its standalone page bundle.
	 *
	 * @param string $entity_id Entity identifier.
	 * @param string $lang      he or en.
	 * @return array
	 */
	public static function public_page_bundle_for_id( $entity_id, $lang = 'he' ) {
		$lang = in_array( $lang, array( 'he', 'en' ), true ) ? $lang : 'he';
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return array();
		}
		$public = self::public_entity_index( $registry );
		if ( ! isset( $public[ $entity_id ] ) ) {
			return array();
		}
		$owner_id = $public[ $entity_id ]['seo']['owner_entity_id'];
		if ( ! isset( $public[ $owner_id ] ) || 'standalone' !== $public[ $owner_id ]['seo']['route_mode'] ) {
			return array();
		}
		return self::build_public_page_bundle( $public[ $owner_id ], $registry, $lang );
	}

	/**
	 * Return sitemap-safe standalone projections only.
	 *
	 * @param string $lang Optional he or en filter.
	 * @return array
	 */
	public static function public_indexable_page_projections( $lang = '' ) {
		$languages = in_array( $lang, array( 'he', 'en' ), true ) ? array( $lang ) : array( 'he', 'en' );
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return array();
		}
		$records = array();
		foreach ( self::public_entity_index( $registry ) as $entity ) {
			if ( 'standalone' !== $entity['seo']['route_mode']
				|| true !== $entity['publication']['search_index']
				|| 'index' !== $entity['index_policy'] ) {
				continue;
			}
			foreach ( $languages as $language ) {
				$projection = self::public_projection( $entity, $registry, $language );
				$records[] = array(
					'language'      => $language,
					'canonical_url' => home_url( $entity['seo']['canonical_path'][ $language ] ),
					'lastmod'       => $entity['trust']['substantive_updated_at'],
					'entity'        => $projection,
				);
			}
		}
		return $records;
	}

	/**
	 * Return the public museum root projection when its gates are open.
	 *
	 * @param string $lang he or en.
	 * @return array
	 */
	public static function public_museum_root_projection( $lang = 'he' ) {
		$lang = in_array( $lang, array( 'he', 'en' ), true ) ? $lang : 'he';
		$registry = self::registry();
		if ( is_wp_error( $registry ) ) {
			return array();
		}
		$public = self::public_entity_index( $registry );
		return isset( $public['museum-culinary-science'] )
			? self::public_projection( $public['museum-culinary-science'], $registry, $lang )
			: array();
	}

	private static function build_public_page_bundle( $owner, $registry, $lang ) {
		$sections = array();
		foreach ( self::public_entity_index( $registry ) as $candidate ) {
			if ( 'section' === $candidate['seo']['route_mode']
				&& $owner['id'] === $candidate['seo']['owner_entity_id'] ) {
				$sections[] = self::public_projection( $candidate, $registry, $lang );
			}
		}
		usort(
			$sections,
			static function ( $left, $right ) {
				return strcmp( $left['id'], $right['id'] );
			}
		);
		$he_url = home_url( $owner['seo']['canonical_path']['he'] );
		$en_url = home_url( $owner['seo']['canonical_path']['en'] );
		$bundle = array(
			'schema'         => 'complete99-culinary-science-page-bundle/v1',
			'version'        => $registry['version'],
			'language'       => $lang,
			'entity'         => self::public_projection( $owner, $registry, $lang ),
			'sections'       => $sections,
			'canonical_path' => $owner['seo']['canonical_path'][ $lang ],
			'canonical_url'  => 'he' === $lang ? $he_url : $en_url,
			'alternates'     => array( 'he' => $he_url, 'en' => $en_url, 'x-default' => $he_url ),
			'indexable'      => true === $owner['publication']['search_index'] && 'index' === $owner['index_policy'],
		);
		$collection = self::public_collection_projection_for_owner( $owner['id'], $registry, $lang );
		if ( ! empty( $collection ) ) {
			$bundle['collection'] = $collection;
		}
		return $bundle;
	}

	private static function public_collection_projection_for_owner( $owner_id, $registry, $lang ) {
		$collection = null;
		foreach ( $registry['collections'] as $candidate ) {
			if ( $owner_id === $candidate['owner_entity_id'] && true === $candidate['public_projection']['enabled'] ) {
				$collection = $candidate;
				break;
			}
		}
		if ( ! is_array( $collection ) ) {
			return array();
		}
		$public_by_id = self::public_entity_index( $registry );
		if ( ! isset( $public_by_id[ $owner_id ] ) ) {
			return array();
		}

		$display_groups = array();
		foreach ( $collection['display']['groups'] as $group ) {
			$display_groups[ $group['id'] ] = $group;
		}
		$groups = array();
		$members = array();
		$member_ids = array();
		foreach ( $collection['navigation']['group_order'] as $group_id ) {
			$group = $display_groups[ $group_id ];
			$groups[] = array(
				'id'          => $group_id,
				'label'       => $group['label'][ $lang ],
				'description' => $group['description'][ $lang ],
			);
			foreach ( $collection['navigation']['member_ids_by_group'][ $group_id ] as $member_id ) {
				if ( ! isset( $public_by_id[ $member_id ] ) ) {
					return array();
				}
				$member = $public_by_id[ $member_id ];
				$record = array(
					'id'              => $member_id,
					'group_id'        => $group_id,
					'name'            => $member['name'][ $lang ],
					'summary'         => $member['summary'][ $lang ],
					'entity_type'     => $member['type'],
					'canonical_path'  => $member['seo']['canonical_path'][ $lang ],
					'owner_entity_id' => $member['seo']['owner_entity_id'],
					'route_mode'      => $member['seo']['route_mode'],
					'approved_public' => true,
				);
				if ( 'section' === $member['seo']['route_mode'] ) {
					$record['fragment'] = $member['seo']['section_id'];
				}
				$members[] = $record;
				$member_ids[] = $member_id;
			}
		}
		$alternate_lang = 'he' === $lang ? 'en' : 'he';
		return array(
			'schema'               => $collection['public_projection']['schema'],
			'key'                  => $collection['key'],
			'language'             => $lang,
			'translation_group_id' => $collection['translation_group_id'],
			'canonical_path'       => $collection['route']['canonical_path'][ $lang ],
			'alternate_path'       => $collection['route']['canonical_path'][ $alternate_lang ],
			'approved_public'      => true,
			'groups'               => $groups,
			'members'              => $members,
			'parity_member_ids'    => array( 'he' => $member_ids, 'en' => $member_ids ),
		);
	}

	private static function public_projection( $entity, $registry, $lang ) {
		$public_facts = array();
		$public_fact_ids = array();
		$source_ids   = array();
		foreach ( $entity['facts'] as $fact ) {
			if ( true !== $fact['public_safe'] ) {
				continue;
			}
			$public_fact_ids[] = $fact['id'];
			$public_scientific_measurements = array_values(
				array_filter(
					$fact['scientific_measurements'],
					static function ( $measurement ) {
						return 'verified' === $measurement['confidence'];
					}
				)
			);
			$public_facts[] = array(
				'id'             => $fact['id'],
				'dimension'      => $fact['dimension'],
				'statement'      => $fact['statement'][ $lang ],
				'evidence_class' => $fact['evidence_class'],
				'value_scope'     => $fact['value_scope'],
				'source_ids'     => $fact['source_ids'],
				'verified_at'    => $fact['verified_at'],
				'observed_at'    => $fact['observed_at'],
				'measurement'    => $fact['measurement'],
				'scientific_measurements' => $public_scientific_measurements,
			);
			$source_ids = array_merge( $source_ids, $fact['source_ids'] );
		}
		$relations = array();
		foreach ( $entity['relations'] as $relation ) {
			if ( true !== $relation['public_safe'] ) {
				continue;
			}
			$relations[] = array(
				'id'             => $relation['id'],
				'type'           => $relation['type'],
				'target_id'      => $relation['target_id'],
				'note'           => $relation['note'][ $lang ],
				'evidence_class' => $relation['evidence_class'],
				'source_ids'     => $relation['source_ids'],
				'valid_from'     => $relation['valid_from'],
				'valid_to'       => $relation['valid_to'],
				'confidence'     => $relation['confidence'],
			);
			$source_ids = array_merge( $source_ids, $relation['source_ids'] );
		}
		$safety = array();
		foreach ( $entity['compliance'] as $record ) {
			if ( true !== $record['public_safe'] ) {
				continue;
			}
			$safety[]  = $record['note'][ $lang ];
			$source_ids = array_merge( $source_ids, $record['source_ids'] );
		}
		$profiles = array();
		foreach ( $entity['profiles'] as $dimension => $profile ) {
			$profile_public_fact_ids = array_values( array_intersect( $profile['fact_ids'], $public_fact_ids ) );
			if ( 'source_backed' !== $profile['state'] || empty( $profile_public_fact_ids ) ) {
				continue;
			}
			$profiles[ $dimension ] = array(
				'state'    => $profile['state'],
				'summary'  => $profile['summary'][ $lang ],
				'fact_ids' => $profile_public_fact_ids,
			);
		}
		$public_attributes = array();
		foreach ( $entity['taxonomy']['public_attribute_keys'] as $attribute_key ) {
			$public_attributes[ $attribute_key ] = $entity['taxonomy']['attributes'][ $attribute_key ];
		}
		$public_taxonomy = array(
			'category_path' => $entity['taxonomy']['public_category_path'],
			'attributes'    => $public_attributes,
			'tags'          => $entity['taxonomy']['public_tags'],
		);
		$sources = array();
		foreach ( array_values( array_unique( $source_ids ) ) as $source_id ) {
			if ( ! isset( $registry['sources'][ $source_id ] ) ) {
				continue;
			}
			$source = $registry['sources'][ $source_id ];
			$sources[] = array(
				'id'           => $source_id,
				'type'         => $source['type'],
				'publisher'    => $source['publisher'],
				'title'        => $source['title'],
				'url'          => $source['url'],
				'published_at' => $source['published_at'],
				'retrieved_at' => $source['retrieved_at'],
			);
		}
		$internal_links = self::public_internal_links( $entity, $registry, $lang );

		$asset_slug = preg_replace( '/[^a-z0-9-]/', '', (string) $entity['slug'] );
		$asset_stems = array(
			'museum-culinary-science' => 'c99-science-culinary-museum-pantry-v02',
			'hub-japanese-foundations-lab' => 'c99-science-japanese-foundations-lab-v01',
			'cuisine-syrian-regional' => 'c99-science-syrian-regional-table-v01',
			'region-syria-aleppo' => 'c99-science-syrian-aleppo-table-v01',
			'hub-aleppine-kibbeh-family' => 'c99-science-aleppine-kibbeh-family-v01',
			'ingredient-syrian-bulgur' => 'c99-science-syrian-bulgur-v01',
			'ingredient-syrian-red-meat' => 'c99-science-syrian-lamb-beef-family-v01',
			'technique-syrian-bulgur-hydration' => 'c99-science-syrian-bulgur-hydration-v01',
			'technique-syrian-kibbeh-cooking' => 'c99-science-syrian-kibbeh-cooking-v01',
			'tradition-aleppan-jewish-foodways' => 'c99-science-aleppan-jewish-foodways-v01',
			'ingredient-shoyu-koji' => 'c99-science-shoyu-koji-substrate-v01',
			'equipment-kioke' => 'c99-science-kioke-wooden-barrel-v01',
			'guide-koji-hydrolysis' => 'c99-science-koji-enzymes-hydrolysis-guide-v01',
			'reaction-koji-enzymatic-hydrolysis' => 'c99-science-koji-enzymatic-hydrolysis-v01',
			'standard-jas-shoyu-1703' => 'c99-science-jas-1703-shoyu-standard-v01',
			'cuisine-lebanese-regional' => 'c99-science-lebanese-regional-table-v01',
		);
		$asset_stem = isset( $asset_stems[ $entity['id'] ] )
			? $asset_stems[ $entity['id'] ]
			: 'c99-science-' . $asset_slug . '-v01';
		$asset_base = defined( 'COMPLETE99_PLATFORM_URL' ) ? COMPLETE99_PLATFORM_URL : '';
		$visual_alts = array(
			'museum-culinary-science' => array( 'he' => 'מזווה קולינרי עולמי מואר עם מדפי תבלינים, דגנים, קטניות, שמנים, כלי קרמיקה ושולחן מלא במנות צבעוניות', 'en' => 'Sunlit international culinary pantry with shelves of spices, grains, legumes, oils and ceramics beside a table of colorful dishes' ),
			'hub-japanese-foundations-lab' => array( 'he' => 'שולחן יסודות המטבח היפני עם אורז בהאנגירי, קומבו, קצואובושי, שויו, יוזו, וואסבי, קוג׳י, נורי, כלי מדידה וסכין יפנית', 'en' => 'Japanese culinary foundations table with rice in a hangiri, kombu, katsuobushi, shoyu, yuzu, wasabi, koji, nori, measurement tools and a Japanese knife' ),
			'hub-japanese-techniques' => array( 'he' => 'האנגירי עם אורז, דאשי, קומבו, קצואובושי, קוג׳י, סכין וכלי מדידה', 'en' => 'Hangiri with rice, dashi, kombu, katsuobushi, koji, knife and measurement tools' ),
			'hub-japanese-food-science' => array( 'he' => 'דאשי, קומבו, קצואובושי, שויו, יוזו, אורז וקוג׳י לצד כלי מדידה', 'en' => 'Dashi, kombu, katsuobushi, shoyu, yuzu, rice and koji beside measurement tools' ),
			'preparation-ichiban-dashi' => array( 'he' => 'איצ׳יבאן דאשי צלול לצד קומבו ושבבי קצואובושי', 'en' => 'Clear ichiban dashi beside kombu and katsuobushi shavings' ),
			'technique-dashi-extraction' => array( 'he' => 'כלי זכוכית עם דאשי זהוב, קומבו, מדחום ומסננת בתהליך מיצוי מבוקר', 'en' => 'Glass vessel with golden dashi, kombu, thermometer and strainer in a controlled extraction setup' ),
			'molecule-l-glutamate' => array( 'he' => 'קומבו ודאשי צלול לצד פסל זכוכית מופשט הממחיש הקשר מדעי לגלוטמט', 'en' => 'Kombu and clear dashi beside an abstract glass sculpture suggesting glutamate science' ),
			'molecule-inosine-monophosphate' => array( 'he' => 'שבבי קצואובושי ודאשי צלול לצד פסל זכוכית מופשט הממחיש הקשר מדעי ל-IMP', 'en' => 'Katsuobushi shavings and clear dashi beside an abstract glass sculpture suggesting IMP science' ),
			'ingredient-kioke-shoyu' => array( 'he' => 'שויו כהה נמזג לקערת קרמיקה לצד חבית קיוקה, פולי סויה וחיטה', 'en' => 'Dark shoyu poured into a ceramic bowl beside a kioke barrel, soybeans and wheat' ),
			'ingredient-kome-koji' => array( 'he' => 'גרגרי קוג׳י אורז לבנים מיובשים בקערת קרמיקה ולצדם מגש תסיסה', 'en' => 'White dried rice-koji grains in a ceramic bowl beside a fermentation tray' ),
			'ingredient-koji-starter-culture' => array( 'he' => 'שקית בהירה ללא מותג של תרבית קוג׳י אבקתית לצד צלוחית אבקה ומגש קוג׳י', 'en' => 'Plain unbranded sachet of powdered koji starter beside a powder dish and koji tray' ),
			'ingredient-koshihikari-rice' => array( 'he' => 'גרגרי אורז קושיהיקארי קצרים נשפכים לכלי עץ לצד קערת אורז', 'en' => 'Short Koshihikari rice grains pouring into a wooden measure beside a rice bowl' ),
			'ingredient-fresh-dutch-wasabi' => array( 'he' => 'קנה שורש וואסבי טרי בגידול הולנדי לצד וואסבי מגורר על משטח אבן', 'en' => 'Dutch-grown fresh wasabi rhizome beside grated wasabi on a stone surface' ),
			'ingredient-kito-yuzu' => array( 'he' => 'יוזו קיטו שלם וחצוי, עם קליפה עבה ופלחי פרי מוארים', 'en' => 'Whole and halved Kito yuzu showing thick rind and illuminated juice vesicles' ),
			'ingredient-hon-mirin' => array( 'he' => 'הון מירין ענברי בכלי זכוכית לצד אורז דביק וקוג׳י אורז', 'en' => 'Amber hon mirin in a glass vessel beside glutinous rice and rice koji' ),
			'guide-umami-synergy' => array( 'he' => 'דאשי, קומבו וקצואובושי לצד המחשה מולקולרית של גלוטמט ו-IMP', 'en' => 'Dashi, kombu and katsuobushi beside a molecular illustration of glutamate and IMP' ),
			'cuisine-syrian-regional' => array( 'he' => 'שולחן מנות סוריות עם מוחמרה, עלי גפן, קובה מבושלת, תריד ודג עם אורז ובצל שחום', 'en' => 'Syrian tasting table with muhammara, grape leaves, cooked kibbeh, thareed, and fish with rice and browned onion' ),
			'region-syria-aleppo' => array( 'he' => 'שולחן חלבי עם קובה מבושלת, פלפל אדום, דובדבנים חמוצים, חבוש ובורגול', 'en' => 'Aleppine table with cooked kibbeh, red pepper, sour cherries, quince and bulgur' ),
			'hub-aleppine-kibbeh-family' => array( 'he' => 'מבחר צורות קובה חלבית מבושלות, צלויות ומטוגנות על שולחן אבן', 'en' => 'A selection of cooked, grilled and fried Aleppine kibbeh forms on a stone table' ),
			'ingredient-syrian-bulgur' => array( 'he' => 'גרגרי בורגול דק ובינוני בשתי קערות קרמיקה ללא אריזה', 'en' => 'Fine and medium bulgur grains in two unbranded ceramic bowls' ),
			'ingredient-syrian-red-meat' => array( 'he' => 'דוגמאות מבושלות ונפרדות של כבש ובקר בכלי קרמיקה ניטרליים', 'en' => 'Separate fully cooked lamb and beef examples in neutral ceramic dishes' ),
			'technique-syrian-bulgur-hydration' => array( 'he' => 'ארבע קערות המציגות בורגול יבש, ספיחת מים, מנוחה ומרקם מוכן', 'en' => 'Four bowls showing dry bulgur, water uptake, resting and a workable final texture' ),
			'technique-syrian-kibbeh-cooking' => array( 'he' => 'ארבע תוצאות קובה מבושלות לחלוטין בצלייה, טיגון, מים ורוטב', 'en' => 'Four fully cooked kibbeh results from grilling, frying, simmering and sauce cooking' ),
			'tradition-aleppan-jewish-foodways' => array( 'he' => 'שולחן משפחתי יהודי חלבי עם קובה מבושלת, עוף צלוי ועלי גפן ממולאים', 'en' => 'Aleppan Jewish family table with cooked kibbeh, roast chicken and stuffed grape leaves' ),
			'ingredient-shoyu-koji' => array( 'he' => 'מגש קוג׳י לשויו מסויה וחיטה עם מעטה קוג׳י בהיר', 'en' => 'Tray of soybean and wheat shoyu koji with a pale koji bloom' ),
			'equipment-kioke' => array( 'he' => 'חבית קיוקה מעץ ארז עם חישוקי במבוק בסדנת תסיסה', 'en' => 'Cedar kioke barrel with bamboo hoops in a fermentation workshop' ),
			'guide-koji-hydrolysis' => array( 'he' => 'קוג׳י אורז וקוג׳י לשויו לצד המחשה מושגית של פירוק אנזימטי', 'en' => 'Rice koji and shoyu koji beside a conceptual enzymatic-breakdown illustration' ),
			'reaction-koji-enzymatic-hydrolysis' => array( 'he' => 'שלושה רצפים מושגיים של שרשראות מזון המתפרקות מעל מצע קוג׳י לשויו', 'en' => 'Three conceptual food-chain breakdown sequences above shoyu koji substrate' ),
			'standard-jas-shoyu-1703' => array( 'he' => 'דוגמאות גוון כלליות של רוטב סויה לצד תיק תקן וכלי בדיקה לא מסומנים', 'en' => 'Generic soy-sauce color samples beside an unmarked standards folio and test glassware' ),
			'cuisine-lebanese-regional' => array( 'he' => 'שולחן לבנוני עם מנאקיש זעתר, טאבולה, קובה אפויה, דג מבושל, בורגול ועדשים, טחינה וסומאק', 'en' => 'Lebanese table with zaatar manouche, tabbouleh, baked kibbeh, cooked fish, bulgur and lentils, tahini and sumac' ),
		);
		$visual = array(
			'url'      => $asset_base . 'assets/images/science/' . rawurlencode( $asset_stem . '.webp' ),
			'avif_url' => $asset_base . 'assets/images/science/' . rawurlencode( $asset_stem . '.avif' ),
			'alt'      => isset( $visual_alts[ $entity['id'] ][ $lang ] ) ? $visual_alts[ $entity['id'] ][ $lang ] : $entity['name'][ $lang ],
			'width'    => 1536,
			'height'   => 1024,
		);
		if ( isset( $asset_stems[ $entity['id'] ] ) ) {
			$visual['small_url']      = $asset_base . 'assets/images/science/' . rawurlencode( $asset_stem . '-768.webp' );
			$visual['small_avif_url'] = $asset_base . 'assets/images/science/' . rawurlencode( $asset_stem . '-768.avif' );
		}
		$market_context = array();
		if ( class_exists( 'Complete99_Culinary_Commerce' )
			&& method_exists( 'Complete99_Culinary_Commerce', 'public_market_context_for_science_entity' ) ) {
			$market_context = Complete99_Culinary_Commerce::public_market_context_for_science_entity( $entity['id'], $lang );
		}
		$offer = array();
		if ( true === $entity['commerce']['public_offer_allowed'] && '' !== $entity['commerce']['woo_product_code'] ) {
			$product_code = $entity['commerce']['woo_product_code'];
			$store_path   = 'en' === $lang ? '/en/store/' : '/store/';
			if ( class_exists( 'Complete99_Commerce' ) && method_exists( 'Complete99_Commerce', 'storefront_product_url' ) ) {
				$store_path = Complete99_Commerce::storefront_product_url( $product_code, $lang, 'all' );
			} else {
				$store_path .= '#c99-product-code-' . preg_replace( '/[^a-z0-9-]/', '', $product_code );
			}
			$offer = array(
				'product_code' => $product_code,
				'store_path'   => $store_path,
				'label'        => 'he' === $lang ? 'למוצר במזווה' : 'View in the pantry',
			);
		}

		return array(
			'id'           => $entity['id'],
			'type'         => $entity['type'],
			'slug'         => $entity['slug'],
			'parent_id'    => $entity['parent_id'],
			'name'         => $entity['name'][ $lang ],
			'summary'      => $entity['summary'][ $lang ],
			'index_policy' => $entity['index_policy'],
			'search_index' => true === $entity['publication']['search_index'],
			'seo'          => array(
				'page_role'         => $entity['seo']['page_role'],
				'route_mode'        => $entity['seo']['route_mode'],
				'owner_entity_id'   => $entity['seo']['owner_entity_id'],
				'section_id'        => $entity['seo']['section_id'],
				'cluster_id'        => $entity['seo']['cluster_id'],
				'hub_entity_id'     => $entity['seo']['hub_entity_id'],
				'intent_classes'    => $entity['seo']['intent_classes'],
				'primary_intent'     => $entity['seo']['primary_intent'][ $lang ],
				'primary_keyword'    => $entity['seo']['primary_keyword'][ $lang ],
				'query_variants'     => $entity['seo']['query_variants'][ $lang ],
				'term_variants'      => $entity['seo']['term_variants'][ $lang ],
				'semantic_entity_ids'=> $entity['seo']['semantic_entity_ids'],
				'canonical_path'     => $entity['seo']['canonical_path'][ $lang ],
				'title'              => $entity['seo']['title'][ $lang ],
				'h1'                 => $entity['seo']['h1'][ $lang ],
				'meta_description'   => $entity['seo']['meta_description'][ $lang ],
				'opening'            => $entity['seo']['opening'][ $lang ],
				'schema_type'        => $entity['seo']['schema_type'],
				'breadcrumb_entity_ids' => $entity['seo']['breadcrumb_entity_ids'],
				'visible_breadcrumbs' => array_map(
					static function ( $breadcrumb ) use ( $lang ) {
						return array( 'key' => $breadcrumb['key'], 'label' => $breadcrumb['label'][ $lang ], 'path' => $breadcrumb['path'][ $lang ] );
					},
					$entity['seo']['visible_breadcrumbs']
				),
			),
			'profiles'     => $profiles,
			'facts'        => $public_facts,
			'taxonomy'     => $public_taxonomy,
			'relations'    => $relations,
			'internal_links' => $internal_links,
			'visual'       => $visual,
			'market_context' => is_array( $market_context ) ? $market_context : array(),
			'offer'        => $offer,
			'safety_notes' => $safety,
			'sources'      => $sources,
			'trust'        => array(
				'research_method'       => $entity['trust']['research_method'][ $lang ],
				'correction_path'       => $entity['trust']['correction_path'][ $lang ],
				'substantive_updated_at'=> $entity['trust']['substantive_updated_at'],
				'next_review_trigger'   => $entity['trust']['next_review_trigger'][ $lang ],
			),
			'reviewed_at'  => $entity['review']['reviewed_at'],
		);
	}

	private static function public_internal_links( $entity, $registry, $lang ) {
		$public_by_id = self::public_entity_index( $registry );
		$links = array();
		$seen  = array();
		$add   = static function ( $target_id, $location, $relationship, $context = '' ) use ( &$links, &$seen, $public_by_id, $lang ) {
			if ( ! isset( $public_by_id[ $target_id ] ) ) {
				return;
			}
			$key = $location . '|' . $relationship . '|' . $target_id;
			if ( isset( $seen[ $key ] ) ) {
				return;
			}
			$target = $public_by_id[ $target_id ];
			$url = $target['seo']['canonical_path'][ $lang ];
			if ( 'section' === $target['seo']['route_mode'] && '' !== $target['seo']['section_id'] ) {
				$url .= '#' . rawurlencode( $target['seo']['section_id'] );
			}
			$seen[ $key ] = true;
			$links[] = array(
				'target_id'    => $target_id,
				'url'          => $url,
				'anchor'       => '' !== $context ? $context : $target['name'][ $lang ],
				'location'     => $location,
				'relationship' => $relationship,
				'context'      => $context,
			);
		};

		foreach ( $entity['seo']['link_plan'] as $link ) {
			if ( true === $link['public_safe'] ) {
				$add( $link['target_id'], $link['placement'], $link['purpose'], $link['anchor'][ $lang ] );
			}
		}
		return $links;
	}

	private static function request_language( $request ) {
		$lang = sanitize_key( (string) $request->get_param( 'lang' ) );
		return in_array( $lang, array( 'he', 'en' ), true ) ? $lang : 'he';
	}

	private static function registry_digest( $registry ) {
		$canonical = self::canonical_value( $registry );
		$flags     = defined( 'JSON_UNESCAPED_UNICODE' ) ? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES : 0;
		return hash( 'sha256', wp_json_encode( $canonical, $flags ) );
	}

	private static function canonical_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( self::is_list( $value ) ) {
			return array_map( array( __CLASS__, 'canonical_value' ), $value );
		}
		ksort( $value, SORT_STRING );
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonical_value( $item );
		}
		return $value;
	}

	private static function normalize_query( $value ) {
		$value = trim( preg_replace( '/\s+/u', ' ', (string) $value ) );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
	}

	private static function assert_no_em_dash( $value, $path ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				self::assert_no_em_dash( $item, $path . '.' . $key );
			}
			return;
		}
		if ( is_string( $value ) && false !== strpos( $value, "\xE2\x80\x94" ) ) {
			throw new RuntimeException( $path . '.em_dash' );
		}
	}

	private static function assert_sha256( $value, $path ) {
		if ( ! is_string( $value ) || 64 !== strlen( $value ) || ! preg_match( '/\A[a-f0-9]{64}\z/', $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_evidence_repository_path( $value, $path ) {
		if ( ! is_string( $value )
			|| strlen( $value ) > 500
			|| 0 !== strpos( $value, 'docs/research-evidence/' )
			|| false !== strpos( $value, '\\' )
			|| '/' === substr( $value, -1 )
			|| ! preg_match( '#\Adocs/research-evidence/[A-Za-z0-9][A-Za-z0-9._/-]*\z#', $value ) ) {
			throw new RuntimeException( $path );
		}
		foreach ( explode( '/', $value ) as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				throw new RuntimeException( $path );
			}
		}
	}

	private static function assert_translation( $value, $path, $maximum ) {
		self::assert_exact_keys( $value, array( 'he', 'en' ), $path );
		self::assert_text( $value['he'], $path . '.he', $maximum );
		self::assert_text( $value['en'], $path . '.en', $maximum );
	}

	private static function assert_locale_lists( $value, $path, $maximum_items ) {
		self::assert_exact_keys( $value, array( 'he', 'en' ), $path );
		foreach ( array( 'he', 'en' ) as $language ) {
			self::assert_text_list( $value[ $language ], $path . '.' . $language, true, $maximum_items );
		}
	}

	private static function assert_text_list( $value, $path, $allow_empty, $maximum_items ) {
		self::assert_list( $value, $path, $allow_empty );
		if ( count( $value ) > $maximum_items ) {
			throw new RuntimeException( $path . '.too_many' );
		}
		foreach ( $value as $offset => $item ) {
			self::assert_text( $item, $path . '.' . $offset, 300 );
		}
	}

	private static function assert_identifier_list( $value, $path, $allow_empty ) {
		self::assert_list( $value, $path, $allow_empty );
		$seen = array();
		foreach ( $value as $offset => $item ) {
			self::assert_identifier( $item, $path . '.' . $offset, 120 );
			if ( isset( $seen[ $item ] ) ) {
				throw new RuntimeException( $path . '.duplicate' );
			}
			$seen[ $item ] = true;
		}
	}

	private static function assert_entity_id( $value, $path ) {
		if ( ! is_string( $value ) || ! preg_match( '/^[a-z][a-z0-9-]{2,79}$/', $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_slug( $value, $path ) {
		if ( ! is_string( $value ) || ! preg_match( '/^[a-z][a-z0-9-]{1,79}$/', $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_identifier( $value, $path, $maximum ) {
		if ( ! is_string( $value ) || strlen( $value ) > $maximum || ! preg_match( '/\A[a-zA-Z][a-zA-Z0-9_.:-]*\z/', $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_text( $value, $path, $maximum, $minimum = 1 ) {
		if ( ! is_string( $value ) ) {
			throw new RuntimeException( $path );
		}
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( trim( $value ), 'UTF-8' ) : strlen( trim( $value ) );
		if ( $length < $minimum || $length > $maximum ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_date( $value, $path, $allow_empty = false ) {
		if ( $allow_empty && '' === $value ) {
			return;
		}
		$date = is_string( $value ) ? DateTimeImmutable::createFromFormat( '!Y-m-d', $value, new DateTimeZone( 'UTC' ) ) : false;
		$errors = DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $date->format( 'Y-m-d' ) !== $value ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_datetime( $value, $path, $allow_empty = false ) {
		if ( $allow_empty && '' === $value ) {
			return;
		}
		if ( ! is_string( $value )
			|| ! preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-](?:(?:0\d|1[0-3]):[0-5]\d|14:00))\z/', $value ) ) {
			throw new RuntimeException( $path );
		}
		$date = DateTimeImmutable::createFromFormat( DateTimeInterface::ATOM, $value );
		$errors = DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_exact_keys( $value, $expected, $path ) {
		self::assert_associative_array( $value, $path, false );
		$actual = array_keys( $value );
		sort( $actual, SORT_STRING );
		$expected = array_values( $expected );
		sort( $expected, SORT_STRING );
		if ( $actual !== $expected ) {
			throw new RuntimeException( $path . '.keys' );
		}
	}

	private static function assert_exact_list( $value, $expected, $path ) {
		self::assert_list( $value, $path, false );
		if ( array_values( $value ) !== array_values( $expected ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_associative_array( $value, $path, $allow_empty ) {
		if ( ! is_array( $value ) || ( ! $allow_empty && empty( $value ) ) || ( ! empty( $value ) && self::is_list( $value ) ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_list( $value, $path, $allow_empty ) {
		if ( ! is_array( $value ) || ( ! $allow_empty && empty( $value ) ) || ! self::is_list( $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function is_list( $value ) {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $value );
		}
		return is_array( $value ) && ( empty( $value ) || array_keys( $value ) === range( 0, count( $value ) - 1 ) );
	}
}
