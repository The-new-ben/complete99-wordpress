<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Private market-evaluation catalog materializer.
 *
 * This class turns the reviewed catalog seed registry into private WordPress
 * records. When WooCommerce is available it also creates held draft products
 * that remain hidden and non-purchasable. Loading or booting this class never
 * materializes records. A trusted operator must call materialize() explicitly.
 */
final class Complete99_Evaluation_Catalog {
	const REGISTRY_SCHEMA = 'complete99-catalog-product-seeds/v1';
	const PRODUCT_SCHEMA  = 'complete99-catalog-product-seed/v1';
	const RECEIPT_SCHEMA  = 'complete99-evaluation-catalog-receipt/v1';
	const STATUS_SCHEMA   = 'complete99-evaluation-catalog-status/v1';
	const OPTION_RECEIPT  = 'complete99_evaluation_catalog_receipt';
	const EXPECTED_SEED_COUNT = 30;
	const MODE_AUTO         = 'auto';
	const MODE_PRIVATE_ONLY = 'private_only';

	const META_MANAGED                = '_complete99_evaluation_catalog_managed';
	const META_PRODUCT_CODE           = '_complete99_evaluation_product_code';
	const META_INGREDIENT_CODE        = '_complete99_evaluation_ingredient_code';
	const META_CANONICAL_PRODUCT_CODE = '_complete99_catalog_product_code';
	const META_MARKET_PROVIDER        = '_complete99_evaluation_market_provider';
	const META_MARKET_SOURCE          = '_complete99_evaluation_market_source_url';
	const META_MARKET_CHECKED_AT      = '_complete99_evaluation_market_checked_at';
	const META_SOURCE_UPDATED_AT      = '_complete99_evaluation_source_updated_at';
	const META_PRICE                  = '_complete99_evaluation_price_ils';
	const META_STOCK                  = '_complete99_evaluation_stock';
	const META_PRICE_SCOPE            = '_complete99_evaluation_price_scope';
	const META_STOCK_SCOPE            = '_complete99_evaluation_stock_scope';
	const META_CLASSIFICATION         = '_complete99_evaluation_classification';
	const META_SALE_STATE             = '_complete99_evaluation_sale_state';
	const META_PUBLIC_SALE            = '_complete99_evaluation_public_sale_eligible';
	const META_REGISTRY_DIGEST        = '_complete99_evaluation_registry_digest';

	const PRODUCT_APPROVED  = '_complete99_store_approved';
	const STOCK_AUTHORITY   = '_complete99_stock_authority';
	const LABEL_REVIEWED    = '_complete99_product_label_reviewed';
	const RIGHTS_REVIEWED   = '_complete99_product_rights_reviewed';
	const TAX_REVIEWED      = '_complete99_product_tax_reviewed';
	const MEDIA_PUBLIC_SAFE = '_complete99_media_public_safe';

	const GRAPH_META_MANAGED         = '_complete99_catalog_managed';
	const GRAPH_META_INGREDIENT_CODE = '_complete99_catalog_ingredient_code';
	const GRAPH_META_ENTITY_ID       = '_complete99_catalog_entity_id';

	private static $booted         = false;
	private static $registry_cache = null;

	/**
	 * Register only private metadata.
	 *
	 * No data mutation is attached to init or any other automatic hook.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'init', array( __CLASS__, 'register_meta' ), 9 );
	}

	/**
	 * Register the private evaluation metadata contract.
	 */
	public static function register_meta() {
		$fields = array(
			self::META_MANAGED           => array( 'string', array( __CLASS__, 'sanitize_managed' ) ),
			self::META_PRODUCT_CODE      => array( 'string', array( __CLASS__, 'sanitize_product_code' ) ),
			self::META_INGREDIENT_CODE   => array( 'string', array( __CLASS__, 'sanitize_ingredient_code' ) ),
			self::META_MARKET_PROVIDER   => array( 'string', array( __CLASS__, 'sanitize_identifier' ) ),
			self::META_MARKET_SOURCE     => array( 'string', array( __CLASS__, 'sanitize_https_url' ) ),
			self::META_MARKET_CHECKED_AT => array( 'string', array( __CLASS__, 'sanitize_date' ) ),
			self::META_SOURCE_UPDATED_AT => array( 'string', array( __CLASS__, 'sanitize_date' ) ),
			self::META_PRICE             => array( 'number', array( __CLASS__, 'sanitize_price' ) ),
			self::META_STOCK             => array( 'integer', array( __CLASS__, 'sanitize_stock' ) ),
			self::META_PRICE_SCOPE       => array( 'string', array( __CLASS__, 'sanitize_identifier' ) ),
			self::META_STOCK_SCOPE       => array( 'string', array( __CLASS__, 'sanitize_identifier' ) ),
			self::META_CLASSIFICATION    => array( 'string', array( __CLASS__, 'sanitize_identifier' ) ),
			self::META_SALE_STATE        => array( 'string', array( __CLASS__, 'sanitize_identifier' ) ),
			self::META_PUBLIC_SALE       => array( 'string', array( __CLASS__, 'sanitize_yes_no' ) ),
			self::META_REGISTRY_DIGEST   => array( 'string', array( __CLASS__, 'sanitize_digest' ) ),
		);

		foreach ( array( 'c99_ingredient', 'c99_product_plan', 'product' ) as $post_type ) {
			foreach ( $fields as $meta_key => $definition ) {
				register_post_meta(
					$post_type,
					$meta_key,
					array(
						'type'              => $definition[0],
						'single'            => true,
						'show_in_rest'      => false,
						'sanitize_callback' => $definition[1],
						'auth_callback'     => self::meta_auth_callback( $post_type ),
					)
				);
			}
		}
		foreach ( array( 'c99_product_plan', 'product' ) as $post_type ) {
			register_post_meta(
				$post_type,
				self::META_CANONICAL_PRODUCT_CODE,
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
	 * Return a canonical product code or an empty fail-closed value.
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
	 * Return a canonical ingredient code or an empty fail-closed value.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_ingredient_code( $value ) {
		return is_string( $value )
			&& strlen( $value ) <= 160
			&& 1 === preg_match( '/\Aingredient-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value )
				? $value
				: '';
	}

	/**
	 * Sanitize a bounded registry identifier.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_identifier( $value ) {
		return is_string( $value )
			&& strlen( $value ) <= 160
			&& 1 === preg_match( '/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/', $value )
				? $value
				: '';
	}

	/**
	 * Sanitize a strict ISO calendar date.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_date( $value ) {
		return self::date_is_valid( $value ) ? $value : '';
	}

	/**
	 * Sanitize an HTTPS source URL.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_https_url( $value ) {
		return self::https_url_is_valid( $value ) ? $value : '';
	}

	/**
	 * Sanitize a positive, two-decimal ILS value.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_price( $value ) {
		try {
			return self::price_string( $value, 'price' );
		} catch ( \Throwable $error ) {
			return '';
		}
	}

	/**
	 * Evaluation stock is intentionally fixed to one unit.
	 *
	 * @param mixed $value Candidate value.
	 * @return int
	 */
	public static function sanitize_stock( $value ) {
		return ( 1 === $value || '1' === $value ) ? 1 : 0;
	}

	/**
	 * Sanitize a yes/no state.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_yes_no( $value ) {
		return in_array( $value, array( 'yes', 'no' ), true ) ? $value : '';
	}

	/**
	 * Sanitize the exact private materializer marker.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_managed( $value ) {
		return ( '1' === $value || 1 === $value ) ? '1' : '';
	}

	/**
	 * Sanitize a SHA-256 digest.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitize_digest( $value ) {
		return is_string( $value ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/', $value )
			? $value
			: '';
	}

	/**
	 * Load and validate the private seed registry.
	 *
	 * @param bool $fresh Ignore the request-local cache.
	 * @return array|WP_Error
	 */
	public static function load_registry( $fresh = false ) {
		if ( ! $fresh && is_array( self::$registry_cache ) ) {
			return self::$registry_cache;
		}
		$base = defined( 'COMPLETE99_PLATFORM_DIR' )
			? rtrim( (string) COMPLETE99_PLATFORM_DIR, '/\\' )
			: dirname( __DIR__ );
		$path = $base . '/data/catalog-product-seeds.php';
		if ( ! is_readable( $path ) ) {
			return self::error(
				'complete99_evaluation_registry_missing',
				'The private evaluation catalog registry is not readable.'
			);
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
	 * Strictly validate every registry and product field before any write.
	 *
	 * @param mixed $registry Candidate registry.
	 * @return true|WP_Error
	 */
	public static function validate_registry( $registry ) {
		try {
			self::assert_exact_keys(
				$registry,
				array(
					'schema',
					'registry_reviewed_at',
					'currency',
					'price_scope',
					'stock_policy',
					'market_transparency_sources',
					'classification_rules',
					'products',
				),
				'registry'
			);
			self::assert_same( self::REGISTRY_SCHEMA, $registry['schema'], 'registry.schema' );
			self::assert_date( $registry['registry_reviewed_at'], 'registry.registry_reviewed_at' );
			self::assert_same( 'ILS', $registry['currency'], 'registry.currency' );
			self::assert_same( 'private_market_benchmark', $registry['price_scope'], 'registry.price_scope' );

			self::assert_exact_keys(
				$registry['stock_policy'],
				array( 'evaluation_quantity', 'public_stock_claim', 'public_sale' ),
				'registry.stock_policy'
			);
			self::assert_same( 1, $registry['stock_policy']['evaluation_quantity'], 'registry.stock_policy.evaluation_quantity' );
			self::assert_same( false, $registry['stock_policy']['public_stock_claim'], 'registry.stock_policy.public_stock_claim' );
			self::assert_same( false, $registry['stock_policy']['public_sale'], 'registry.stock_policy.public_sale' );

			self::assert_associative_array(
				$registry['market_transparency_sources'],
				'registry.market_transparency_sources',
				false
			);
			if ( 30 < count( $registry['market_transparency_sources'] ) ) {
				throw new \UnexpectedValueException( 'registry.market_transparency_sources exceeds its bound.' );
			}
			$trusted_domains = array();
			foreach ( $registry['market_transparency_sources'] as $source_id => $url ) {
				self::assert_identifier( $source_id, 'registry.market_transparency_sources key' );
				self::assert_https_url( $url, 'registry.market_transparency_sources.' . $source_id );
				$trusted_domains[] = self::base_domain( (string) parse_url( $url, PHP_URL_HOST ) );
			}
			$trusted_domains = array_values( array_unique( $trusted_domains ) );

			self::assert_associative_array(
				$registry['classification_rules'],
				'registry.classification_rules',
				false
			);
			if ( 50 < count( $registry['classification_rules'] ) ) {
				throw new \UnexpectedValueException( 'registry.classification_rules exceeds its bound.' );
			}
			$classification_rules = array();
			foreach ( $registry['classification_rules'] as $classification => $rule ) {
				self::assert_identifier( $classification, 'registry.classification_rules key' );
				self::assert_exact_keys(
					$rule,
					array( 'resale_candidate', 'temperature_zone', 'required_gates' ),
					'registry.classification_rules.' . $classification
				);
				self::assert_boolean(
					$rule['resale_candidate'],
					'registry.classification_rules.' . $classification . '.resale_candidate'
				);
				self::assert_identifier(
					$rule['temperature_zone'],
					'registry.classification_rules.' . $classification . '.temperature_zone'
				);
				self::assert_identifier_list(
					$rule['required_gates'],
					'registry.classification_rules.' . $classification . '.required_gates',
					false,
					100
				);
				$classification_rules[ $classification ] = $rule;
			}

			self::assert_list( $registry['products'], 'registry.products', false );
			if ( 200 < count( $registry['products'] ) ) {
				throw new \UnexpectedValueException( 'registry.products exceeds its bound.' );
			}
			$product_codes    = array();
			$ingredient_codes = array();
			foreach ( $registry['products'] as $index => $product ) {
				$path = 'registry.products.' . $index;
				self::validate_product(
					$product,
					$path,
					$registry['registry_reviewed_at'],
					$trusted_domains,
					$classification_rules
				);
				if ( isset( $product_codes[ $product['product_code'] ] ) ) {
					throw new \UnexpectedValueException( $path . '.product_code is duplicated.' );
				}
				if ( isset( $ingredient_codes[ $product['ingredient_code'] ] ) ) {
					throw new \UnexpectedValueException( $path . '.ingredient_code is duplicated.' );
				}
				$product_codes[ $product['product_code'] ]       = true;
				$ingredient_codes[ $product['ingredient_code'] ] = true;
			}
			return true;
		} catch ( \Throwable $error ) {
			return self::error(
				'complete99_evaluation_registry_invalid',
				'The private evaluation catalog failed closed: ' . $error->getMessage()
			);
		}
	}

	/**
	 * Compute the canonical registry digest used by records and receipts.
	 *
	 * @param array $registry Validated registry.
	 * @return string
	 */
	public static function registry_digest( $registry ) {
		return self::digest_value( $registry );
	}

	/**
	 * Explicitly materialize all validated evaluation seeds.
	 *
	 * The method performs a complete registry and database preflight before the
	 * first write. Existing records are updated only when they carry this
	 * class's marker or the catalog graph marker, exact canonical bindings,
	 * held exposure and closed acceptance gates.
	 *
	 * @param string $mode Materialization mode. Use private_only during a
	 *                     migration that must not create WooCommerce products.
	 * @return array|WP_Error
	 */
	public static function materialize( $mode = self::MODE_AUTO ) {
		if ( ! in_array( $mode, array( self::MODE_AUTO, self::MODE_PRIVATE_ONLY ), true ) ) {
			return self::error(
				'complete99_evaluation_materialization_mode_invalid',
				'The evaluation catalog materialization mode is invalid.'
			);
		}

		$registry = self::load_registry( true );
		if ( self::is_error( $registry ) ) {
			return $registry;
		}
		if ( ! post_type_exists( 'c99_ingredient' ) || ! post_type_exists( 'c99_product_plan' ) ) {
			return self::error(
				'complete99_evaluation_post_types_unavailable',
				'Private ingredient and product-plan post types must be registered first.'
			);
		}

		$woo_available = self::woocommerce_active();
		$woo_active    = self::MODE_AUTO === $mode && $woo_available;
		if ( $woo_active && ( ! post_type_exists( 'product' ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) ) {
			return self::error(
				'complete99_evaluation_woocommerce_incomplete',
				'WooCommerce is active but its product or SKU APIs are unavailable.'
			);
		}

		$preflight = self::preflight_existing_records( $registry['products'], $woo_active );
		if ( self::is_error( $preflight ) ) {
			return $preflight;
		}

		$registry_digest = self::registry_digest( $registry );
		if ( 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $registry_digest ) ) {
			return self::error(
				'complete99_evaluation_registry_digest_failed',
				'The evaluation registry digest could not be established.'
			);
		}

		$result = array(
			'schema'                   => 'complete99-evaluation-catalog-materialization/v1',
			'mode'                     => $mode,
			'registry_digest'          => $registry_digest,
			'seed_count'               => count( $registry['products'] ),
			'ingredient_posts'         => array(),
			'product_plan_posts'       => array(),
			'woo_products'             => array(),
			'woocommerce_available'    => $woo_available,
			'woocommerce_materialized' => $woo_active,
		);

		foreach ( $registry['products'] as $seed ) {
			$product_code = $seed['product_code'];

			$ingredient_id = self::upsert_private_record(
				'c99_ingredient',
				$seed,
				$preflight['c99_ingredient'][ $product_code ],
				$registry_digest
			);
			if ( self::is_error( $ingredient_id ) ) {
				return $ingredient_id;
			}
			$result['ingredient_posts'][ $product_code ] = $ingredient_id;

			$plan_id = self::upsert_product_plan(
				$seed,
				$preflight['c99_product_plan'][ $product_code ],
				$registry_digest
			);
			if ( self::is_error( $plan_id ) ) {
				return $plan_id;
			}
			$result['product_plan_posts'][ $product_code ] = $plan_id;

			if ( $woo_active ) {
				$product_id = self::upsert_woo_product(
					$seed,
					$preflight['product'][ $product_code ],
					$registry_digest
				);
				if ( self::is_error( $product_id ) ) {
					return $product_id;
				}
				$result['woo_products'][ $product_code ] = $product_id;
			}
		}

		ksort( $result['ingredient_posts'], SORT_STRING );
		ksort( $result['product_plan_posts'], SORT_STRING );
		ksort( $result['woo_products'], SORT_STRING );

		$seed_count = count( $registry['products'] );
		if ( $seed_count !== count( $result['ingredient_posts'] )
			|| $seed_count !== count( $result['product_plan_posts'] )
			|| ( $woo_active && $seed_count !== count( $result['woo_products'] ) )
			|| ( ! $woo_active && ! empty( $result['woo_products'] ) ) ) {
			return self::error(
				'complete99_evaluation_materialization_incomplete',
				'The evaluation catalog did not produce the expected held record counts.'
			);
		}

		$receipt = array(
			'schema'              => self::RECEIPT_SCHEMA,
			'status'              => 'success',
			'mode'                => $mode,
			'registry_digest'     => $registry_digest,
			'registry_reviewed_at'=> $registry['registry_reviewed_at'],
			'seed_count'          => $seed_count,
			'ingredient_count'    => count( $result['ingredient_posts'] ),
			'product_plan_count'  => count( $result['product_plan_posts'] ),
			'woo_product_count'   => count( $result['woo_products'] ),
			'woo_available'       => $woo_available,
			'woo_materialized'    => $woo_active,
			'bindings_digest'     => self::digest_value(
				array(
					'ingredient_posts'   => $result['ingredient_posts'],
					'product_plan_posts' => $result['product_plan_posts'],
					'woo_products'       => $result['woo_products'],
				)
			),
			'materialized_at'     => gmdate( 'c' ),
		);
		$stored  = update_option( self::OPTION_RECEIPT, $receipt, false );
		$readback = get_option( self::OPTION_RECEIPT, null );
		if ( false === $stored && ! self::values_equal( $receipt, $readback ) ) {
			return self::error(
				'complete99_evaluation_receipt_write_failed',
				'The successful evaluation receipt could not be stored.'
			);
		}
		if ( ! self::receipt_is_valid( $readback, $receipt ) ) {
			return self::error(
				'complete99_evaluation_receipt_readback_failed',
				'The successful evaluation receipt failed durable readback.'
			);
		}
		$result['receipt'] = $readback;
		return $result;
	}

	/**
	 * Verify the durable private-only catalog without returning price data.
	 *
	 * The caller may pass a receipt read directly from wp_options so migration
	 * and health checks can bypass an object-cache copy.
	 *
	 * @param mixed $receipt Persisted receipt, or null to use get_option().
	 * @return array
	 */
	public static function persisted_status( $receipt = null ) {
		if ( null === $receipt ) {
			$receipt = get_option( self::OPTION_RECEIPT, null );
		}
		$status = array(
			'schema'       => self::STATUS_SCHEMA,
			'ready'        => false,
			'reason'       => 'invariant_failed',
			'receipt'      => self::safe_receipt_projection( $receipt ),
			'materialized' => array(
				'ingredient_count'   => 0,
				'product_plan_count' => 0,
			),
		);

		try {
			$registry = self::load_registry( true );
			if ( self::is_error( $registry )
				|| self::EXPECTED_SEED_COUNT !== count( $registry['products'] ?? array() ) ) {
				throw new \RuntimeException( 'registry' );
			}
			$registry_digest = self::registry_digest( $registry );
			if ( ! self::persisted_receipt_contract_is_valid( $receipt, $registry, $registry_digest ) ) {
				$status['reason'] = is_array( $receipt ) ? 'receipt_corrupt' : 'receipt_missing';
				return $status;
			}

			$expected = array();
			foreach ( $registry['products'] as $seed ) {
				$expected[ $seed['product_code'] ] = $seed;
			}
			ksort( $expected, SORT_STRING );

			$bindings = array(
				'ingredient_posts'   => array(),
				'product_plan_posts' => array(),
				'woo_products'       => array(),
			);
			foreach (
				array(
					'c99_ingredient'   => 'ingredient_posts',
					'c99_product_plan' => 'product_plan_posts',
				) as $post_type => $binding_key
			) {
				$ids = self::managed_record_ids( $post_type );
				$status['materialized'][ 'c99_ingredient' === $post_type ? 'ingredient_count' : 'product_plan_count' ] = count( $ids );
				if ( self::EXPECTED_SEED_COUNT !== count( $ids ) ) {
					throw new \RuntimeException( 'count' );
				}

				foreach ( $ids as $post_id ) {
					$post = get_post( $post_id );
					if ( ! $post
						|| $post_type !== (string) $post->post_type
						|| 'private' !== (string) $post->post_status ) {
						throw new \RuntimeException( 'exposure' );
					}
					$product_code = (string) get_post_meta( $post_id, self::META_PRODUCT_CODE, true );
					if ( ! isset( $expected[ $product_code ] )
						|| isset( $bindings[ $binding_key ][ $product_code ] ) ) {
						throw new \RuntimeException( 'binding' );
					}
					$seed = $expected[ $product_code ];
					foreach ( self::held_meta( $seed, $registry_digest ) as $meta_key => $expected_value ) {
						if ( ! metadata_exists( 'post', $post_id, $meta_key )
							|| ! self::meta_values_equal(
								$meta_key,
								$expected_value,
								get_post_meta( $post_id, $meta_key, true )
							) ) {
							throw new \RuntimeException( 'held_meta' );
						}
					}
					if ( 'c99_product_plan' === $post_type ) {
						$plan_meta = array(
							self::META_CANONICAL_PRODUCT_CODE   => $product_code,
							'_complete99_product_status'        => 'private_evaluation_held',
							'_complete99_product_sku'           => $product_code,
							'_complete99_product_name_he'       => $seed['name']['he'],
							'_complete99_product_name_en'       => $seed['name']['en'],
							'_complete99_product_unit'          => $seed['package_label']['en'],
							'_complete99_product_price'         => self::price_string( $seed['evaluation_price_ils'], 'evaluation price' ),
							'_complete99_product_currency'      => 'ILS',
							'_complete99_product_stock_source'  => 'evaluation_only',
							'_complete99_product_rights'        => 'pending',
						);
						foreach ( $plan_meta as $meta_key => $expected_value ) {
							if ( ! metadata_exists( 'post', $post_id, $meta_key )
								|| ! self::meta_values_equal(
									$meta_key,
									$expected_value,
									get_post_meta( $post_id, $meta_key, true )
								) ) {
								throw new \RuntimeException( 'plan_meta' );
							}
						}
					}
					$bindings[ $binding_key ][ $product_code ] = absint( $post_id );
				}
				ksort( $bindings[ $binding_key ], SORT_STRING );
				if ( array_keys( $expected ) !== array_keys( $bindings[ $binding_key ] ) ) {
					throw new \RuntimeException( 'coverage' );
				}
			}

			if ( ! hash_equals(
				(string) $receipt['bindings_digest'],
				self::digest_value( $bindings )
			) ) {
				$status['reason'] = 'receipt_binding_mismatch';
				return $status;
			}

			$status['ready']  = true;
			$status['reason'] = '';
			$status['receipt']['valid'] = true;
			return $status;
		} catch ( \Throwable $error ) {
			return $status;
		}
	}

	private static function safe_receipt_projection( $receipt ) {
		$receipt = is_array( $receipt ) ? $receipt : array();
		return array(
			'present'            => ! empty( $receipt ),
			'valid'              => false,
			'status'             => is_string( $receipt['status'] ?? null ) ? $receipt['status'] : '',
			'mode'               => is_string( $receipt['mode'] ?? null ) ? $receipt['mode'] : '',
			'seed_count'         => is_numeric( $receipt['seed_count'] ?? null ) ? (int) $receipt['seed_count'] : 0,
			'ingredient_count'   => is_numeric( $receipt['ingredient_count'] ?? null ) ? (int) $receipt['ingredient_count'] : 0,
			'product_plan_count' => is_numeric( $receipt['product_plan_count'] ?? null ) ? (int) $receipt['product_plan_count'] : 0,
			'woo_product_count'  => is_numeric( $receipt['woo_product_count'] ?? null ) ? (int) $receipt['woo_product_count'] : 0,
			'woo_materialized'   => is_bool( $receipt['woo_materialized'] ?? null ) ? $receipt['woo_materialized'] : false,
		);
	}

	private static function persisted_receipt_contract_is_valid( $receipt, $registry, $registry_digest ) {
		if ( ! is_array( $receipt ) ) {
			return false;
		}
		$expected_keys = array(
			'schema',
			'status',
			'mode',
			'registry_digest',
			'registry_reviewed_at',
			'seed_count',
			'ingredient_count',
			'product_plan_count',
			'woo_product_count',
			'woo_available',
			'woo_materialized',
			'bindings_digest',
			'materialized_at',
		);
		$actual_keys = array_keys( $receipt );
		sort( $expected_keys, SORT_STRING );
		sort( $actual_keys, SORT_STRING );
		return $expected_keys === $actual_keys
			&& self::RECEIPT_SCHEMA === $receipt['schema']
			&& 'success' === $receipt['status']
			&& self::MODE_PRIVATE_ONLY === $receipt['mode']
			&& is_string( $receipt['registry_digest'] )
			&& hash_equals( $registry_digest, $receipt['registry_digest'] )
			&& is_string( $receipt['registry_reviewed_at'] )
			&& $registry['registry_reviewed_at'] === $receipt['registry_reviewed_at']
			&& is_int( $receipt['seed_count'] )
			&& self::EXPECTED_SEED_COUNT === $receipt['seed_count']
			&& is_int( $receipt['ingredient_count'] )
			&& self::EXPECTED_SEED_COUNT === $receipt['ingredient_count']
			&& is_int( $receipt['product_plan_count'] )
			&& self::EXPECTED_SEED_COUNT === $receipt['product_plan_count']
			&& is_int( $receipt['woo_product_count'] )
			&& 0 === $receipt['woo_product_count']
			&& is_bool( $receipt['woo_available'] )
			&& is_bool( $receipt['woo_materialized'] )
			&& false === $receipt['woo_materialized']
			&& is_string( $receipt['bindings_digest'] )
			&& 1 === preg_match( '/\A[a-f0-9]{64}\z/', $receipt['bindings_digest'] )
			&& self::receipt_timestamp_is_valid( $receipt['materialized_at'] );
	}

	private static function receipt_timestamp_is_valid( $value ) {
		if ( ! is_string( $value )
			|| 1 !== preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00\z/', $value ) ) {
			return false;
		}
		$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:sP', $value );
		$errors = \DateTimeImmutable::getLastErrors();
		return false !== $date
			&& ( false === $errors
				|| ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) )
			&& $date->format( 'Y-m-d\TH:i:sP' ) === $value;
	}

	private static function managed_record_ids( $post_type ) {
		$statuses = function_exists( 'get_post_stati' ) ? array_values( get_post_stati() ) : 'any';
		$ids      = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => $statuses,
				'posts_per_page'         => self::EXPECTED_SEED_COUNT + 1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => self::META_MANAGED,
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);
		if ( ! is_array( $ids ) ) {
			throw new \RuntimeException( 'record_query' );
		}
		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	/**
	 * Report whether the minimum WooCommerce object runtime is active.
	 *
	 * @return bool
	 */
	public static function woocommerce_active() {
		return class_exists( 'WooCommerce' )
			&& class_exists( 'WC_Product_Simple' )
			&& function_exists( 'wc_get_product' );
	}

	private static function validate_product( $product, $path, $registry_reviewed_at, $trusted_domains, $rules ) {
		self::assert_exact_keys(
			$product,
			array(
				'schema',
				'product_code',
				'ingredient_code',
				'name',
				'brand_reference',
				'gtin',
				'gtin_status',
				'package_label',
				'classification',
				'temperature_zone',
				'market_observation',
				'normalized_market_price',
				'evaluation_price_ils',
				'evaluation_price_scope',
				'evaluation_stock',
				'evaluation_stock_scope',
				'public_sale_eligible',
				'sale_state',
				'resale_candidate',
				'procurement_cost_ils',
				'target_margin_percent',
				'waste_factor_percent',
				'ingredient_statement',
				'allergen_statement',
				'nutrition_panel',
				'kosher_certificate',
				'image_asset',
				'image_state',
				'regulatory_attention_codes',
				'relations',
				'acceptance_gates',
			),
			$path
		);
		self::assert_same( self::PRODUCT_SCHEMA, $product['schema'], $path . '.schema' );
		self::assert_product_code( $product['product_code'], $path . '.product_code' );
		self::assert_ingredient_code( $product['ingredient_code'], $path . '.ingredient_code' );
		self::assert_bilingual_text( $product['name'], $path . '.name', 300 );
		self::assert_text( $product['brand_reference'], $path . '.brand_reference', false, 300 );
		self::assert_same( '', $product['gtin'], $path . '.gtin' );
		self::assert_same( 'supplier_record_required', $product['gtin_status'], $path . '.gtin_status' );
		self::assert_bilingual_text( $product['package_label'], $path . '.package_label', 500 );

		self::assert_identifier( $product['classification'], $path . '.classification' );
		if ( ! isset( $rules[ $product['classification'] ] ) ) {
			throw new \UnexpectedValueException( $path . '.classification is unknown.' );
		}
		$rule = $rules[ $product['classification'] ];
		self::assert_same( $rule['temperature_zone'], $product['temperature_zone'], $path . '.temperature_zone' );

		$observation = $product['market_observation'];
		self::assert_exact_keys(
			$observation,
			array(
				'source_provider',
				'source_url',
				'checked_at',
				'source_updated_at',
				'observed_price_ils',
				'range_low_ils',
				'range_high_ils',
				'price_scope',
			),
			$path . '.market_observation'
		);
		self::assert_identifier( $observation['source_provider'], $path . '.market_observation.source_provider' );
		self::assert_https_url( $observation['source_url'], $path . '.market_observation.source_url' );
		$source_domain = self::base_domain( (string) parse_url( $observation['source_url'], PHP_URL_HOST ) );
		if ( ! in_array( $source_domain, $trusted_domains, true ) ) {
			throw new \UnexpectedValueException( $path . '.market_observation.source_url is outside the source registry.' );
		}
		self::assert_date( $observation['checked_at'], $path . '.market_observation.checked_at' );
		self::assert_date( $observation['source_updated_at'], $path . '.market_observation.source_updated_at' );
		if ( $observation['source_updated_at'] > $observation['checked_at']
			|| $observation['checked_at'] > $registry_reviewed_at ) {
			throw new \UnexpectedValueException( $path . '.market_observation dates are not chronologically valid.' );
		}
		$observed = self::assert_price( $observation['observed_price_ils'], $path . '.market_observation.observed_price_ils' );
		$low      = self::assert_price( $observation['range_low_ils'], $path . '.market_observation.range_low_ils' );
		$high     = self::assert_price( $observation['range_high_ils'], $path . '.market_observation.range_high_ils' );
		if ( $low > $observed || $observed > $high ) {
			throw new \UnexpectedValueException( $path . '.market_observation observed price is outside its range.' );
		}
		self::assert_same(
			'consumer_retail_observation',
			$observation['price_scope'],
			$path . '.market_observation.price_scope'
		);

		self::assert_exact_keys(
			$product['normalized_market_price'],
			array( 'amount', 'unit' ),
			$path . '.normalized_market_price'
		);
		self::assert_price( $product['normalized_market_price']['amount'], $path . '.normalized_market_price.amount' );
		self::assert_price_unit( $product['normalized_market_price']['unit'], $path . '.normalized_market_price.unit' );
		self::assert_price( $product['evaluation_price_ils'], $path . '.evaluation_price_ils' );
		self::assert_same(
			'private_benchmark_only',
			$product['evaluation_price_scope'],
			$path . '.evaluation_price_scope'
		);
		self::assert_same( 1, $product['evaluation_stock'], $path . '.evaluation_stock' );
		self::assert_same(
			'private_evaluation_only',
			$product['evaluation_stock_scope'],
			$path . '.evaluation_stock_scope'
		);
		self::assert_same( false, $product['public_sale_eligible'], $path . '.public_sale_eligible' );
		self::assert_same( 'held_until_acceptance', $product['sale_state'], $path . '.sale_state' );
		self::assert_same( $rule['resale_candidate'], $product['resale_candidate'], $path . '.resale_candidate' );
		self::assert_same( null, $product['procurement_cost_ils'], $path . '.procurement_cost_ils' );
		self::assert_same( null, $product['target_margin_percent'], $path . '.target_margin_percent' );
		self::assert_same( null, $product['waste_factor_percent'], $path . '.waste_factor_percent' );

		self::assert_pending_status(
			$product['ingredient_statement'],
			'supplier_label_required',
			$path . '.ingredient_statement'
		);
		self::assert_pending_status(
			$product['allergen_statement'],
			'supplier_label_required',
			$path . '.allergen_statement'
		);
		self::assert_pending_status(
			$product['nutrition_panel'],
			'supplier_label_required',
			$path . '.nutrition_panel'
		);
		self::assert_pending_status(
			$product['kosher_certificate'],
			'supplier_document_required',
			$path . '.kosher_certificate'
		);
		if ( '' === $product['image_asset'] ) {
			self::assert_same(
				'evaluation_asset_pending_binding',
				$product['image_state'],
				$path . '.image_state'
			);
		} else {
			if ( ! is_string( $product['image_asset'] )
				|| strlen( $product['image_asset'] ) > 180
				|| 1 !== preg_match( '/\Ac99-[a-z0-9]+(?:-[a-z0-9]+)*\.webp\z/', $product['image_asset'] ) ) {
				throw new \UnexpectedValueException( $path . '.image_asset is not a held evaluation asset basename.' );
			}
			self::assert_same(
				'evaluation_asset_held_for_review',
				$product['image_state'],
				$path . '.image_state'
			);
		}
		self::assert_identifier_list(
			$product['regulatory_attention_codes'],
			$path . '.regulatory_attention_codes',
			true,
			50
		);

		self::assert_exact_keys(
			$product['relations'],
			array(
				'verified_ingredient_codes',
				'verified_dish_ids',
				'candidate_ingredient_codes',
				'candidate_dish_ids',
			),
			$path . '.relations'
		);
		self::assert_same(
			array( $product['ingredient_code'] ),
			$product['relations']['verified_ingredient_codes'],
			$path . '.relations.verified_ingredient_codes'
		);
		self::assert_identifier_list(
			$product['relations']['verified_dish_ids'],
			$path . '.relations.verified_dish_ids',
			true,
			100
		);
		foreach ( $product['relations']['verified_dish_ids'] as $dish_id ) {
			if ( 1 !== preg_match( '/\Amenu-reference-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $dish_id ) ) {
				throw new \UnexpectedValueException( $path . '.relations.verified_dish_ids contains an invalid dish ID.' );
			}
		}
		self::assert_identifier_list(
			$product['relations']['candidate_ingredient_codes'],
			$path . '.relations.candidate_ingredient_codes',
			true,
			100
		);
		foreach ( $product['relations']['candidate_ingredient_codes'] as $ingredient_code ) {
			self::assert_ingredient_code(
				$ingredient_code,
				$path . '.relations.candidate_ingredient_codes'
			);
		}
		self::assert_identifier_list(
			$product['relations']['candidate_dish_ids'],
			$path . '.relations.candidate_dish_ids',
			true,
			100
		);
		foreach ( $product['relations']['candidate_dish_ids'] as $dish_id ) {
			if ( 1 !== preg_match( '/\Amenu-reference-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $dish_id ) ) {
				throw new \UnexpectedValueException( $path . '.relations.candidate_dish_ids contains an invalid dish ID.' );
			}
		}

		self::assert_associative_array( $product['acceptance_gates'], $path . '.acceptance_gates', false );
		$expected_gates = $rule['required_gates'];
		sort( $expected_gates, SORT_STRING );
		$actual_gates = array_keys( $product['acceptance_gates'] );
		sort( $actual_gates, SORT_STRING );
		if ( $expected_gates !== $actual_gates ) {
			throw new \UnexpectedValueException( $path . '.acceptance_gates does not match its classification.' );
		}
		foreach ( $product['acceptance_gates'] as $gate => $complete ) {
			self::assert_identifier( $gate, $path . '.acceptance_gates key' );
			if ( false !== $complete ) {
				throw new \UnexpectedValueException( $path . '.acceptance_gates contains a completed gate.' );
			}
		}
	}

	private static function preflight_existing_records( $seeds, $woo_active ) {
		$existing = array(
			'c99_ingredient'   => array(),
			'c99_product_plan' => array(),
			'product'          => array(),
		);
		foreach ( $seeds as $seed ) {
			$product_code    = $seed['product_code'];
			$ingredient_code = $seed['ingredient_code'];

			foreach ( array( 'c99_ingredient', 'c99_product_plan' ) as $post_type ) {
				$bindings = array();
				$specs    = array(
					array( self::META_PRODUCT_CODE, $product_code ),
					array( self::META_INGREDIENT_CODE, $ingredient_code ),
					array( self::GRAPH_META_INGREDIENT_CODE, $ingredient_code ),
					array( self::GRAPH_META_ENTITY_ID, $ingredient_code ),
				);
				if ( 'c99_product_plan' === $post_type ) {
					$specs[] = array( self::META_CANONICAL_PRODUCT_CODE, $product_code );
					$specs[] = array( '_complete99_product_sku', $product_code );
				}
				foreach ( $specs as $spec ) {
					$binding = self::unique_existing_post_id( $post_type, $spec[0], $spec[1] );
					if ( self::is_error( $binding ) ) {
						return $binding;
					}
					$bindings[] = $binding;
				}
				$post_id = self::reconcile_bindings(
					$post_type,
					$product_code,
					$bindings
				);
				if ( self::is_error( $post_id ) ) {
					return $post_id;
				}
				if ( $post_id ) {
					$managed = self::validate_existing_managed_record(
						$post_type,
						$post_id,
						$seed,
						'private'
					);
					if ( self::is_error( $managed ) ) {
						return $managed;
					}
				}
				$existing[ $post_type ][ $product_code ] = $post_id;
			}

			if ( $woo_active ) {
				$bindings = array();
				foreach (
					array(
						array( self::META_PRODUCT_CODE, $product_code ),
						array( self::META_INGREDIENT_CODE, $ingredient_code ),
						array( self::GRAPH_META_INGREDIENT_CODE, $ingredient_code ),
						array( self::GRAPH_META_ENTITY_ID, $ingredient_code ),
						array( self::META_CANONICAL_PRODUCT_CODE, $product_code ),
						array( '_complete99_product_sku', $product_code ),
						array( '_sku', $product_code ),
					) as $spec
				) {
					$binding = self::unique_existing_post_id( 'product', $spec[0], $spec[1] );
					if ( self::is_error( $binding ) ) {
						return $binding;
					}
					$bindings[] = $binding;
				}
				$woo_sku_binding = absint( wc_get_product_id_by_sku( $product_code ) );
				$bindings[]      = $woo_sku_binding;
				$post_id         = self::reconcile_bindings(
					'product',
					$product_code,
					$bindings
				);
				if ( self::is_error( $post_id ) ) {
					return $post_id;
				}
				if ( $post_id ) {
					$managed = self::validate_existing_managed_record(
						'product',
						$post_id,
						$seed,
						'draft'
					);
					if ( self::is_error( $managed ) ) {
						return $managed;
					}
					$product = wc_get_product( $post_id );
					if ( ! $product || ! $product->is_type( 'simple' ) ) {
						return self::error(
							'complete99_evaluation_product_type_invalid',
							'An evaluation product binding points to a non-simple WooCommerce product.'
						);
					}
				}
				$existing['product'][ $product_code ] = $post_id;
			}
		}
		return $existing;
	}

	private static function unique_existing_post_id( $post_type, $meta_key, $meta_value ) {
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
						'value'   => $meta_value,
						'compare' => '=',
					),
				),
			)
		);
		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
		if ( 1 < count( $ids ) ) {
			return self::error(
				'complete99_evaluation_duplicate_binding',
				'An evaluation code has duplicate ' . $post_type . ' bindings.'
			);
		}
		return empty( $ids ) ? 0 : $ids[0];
	}

	private static function reconcile_bindings( $post_type, $product_code, $bindings ) {
		$bindings = array_values( array_unique( array_filter( array_map( 'absint', $bindings ) ) ) );
		if ( 1 < count( $bindings ) ) {
			return self::error(
				'complete99_evaluation_binding_conflict',
				'Product code ' . $product_code . ' has conflicting ' . $post_type . ' bindings.'
			);
		}
		return empty( $bindings ) ? 0 : $bindings[0];
	}

	private static function validate_existing_managed_record( $post_type, $post_id, $seed, $required_status ) {
		$post = get_post( $post_id );
		if ( ! $post || $post_type !== (string) $post->post_type ) {
			return self::error(
				'complete99_evaluation_existing_record_invalid',
				'An existing evaluation binding does not resolve to its expected post type.'
			);
		}

		$evaluation_managed = '1' === (string) get_post_meta( $post_id, self::META_MANAGED, true );
		$graph_managed      = '1' === (string) get_post_meta( $post_id, self::GRAPH_META_MANAGED, true );
		if ( ! $evaluation_managed && ! $graph_managed ) {
			return self::error(
				'complete99_evaluation_nonmanaged_binding',
				'An existing canonical binding is not managed by an approved catalog owner.'
			);
		}

		if ( $evaluation_managed
			&& ( $seed['product_code'] !== (string) get_post_meta( $post_id, self::META_PRODUCT_CODE, true )
				|| $seed['ingredient_code'] !== (string) get_post_meta( $post_id, self::META_INGREDIENT_CODE, true ) ) ) {
			return self::error(
				'complete99_evaluation_binding_mismatch',
				'An existing evaluation-managed record has mismatched canonical bindings.'
			);
		}
		if ( $graph_managed
			&& ( $seed['ingredient_code'] !== (string) get_post_meta( $post_id, self::GRAPH_META_INGREDIENT_CODE, true )
				|| $seed['ingredient_code'] !== (string) get_post_meta( $post_id, self::GRAPH_META_ENTITY_ID, true ) ) ) {
			return self::error(
				'complete99_evaluation_binding_mismatch',
				'An existing graph-managed record has mismatched canonical bindings.'
			);
		}

		$binding_values = array(
			self::META_PRODUCT_CODE        => $seed['product_code'],
			self::META_INGREDIENT_CODE     => $seed['ingredient_code'],
			self::GRAPH_META_INGREDIENT_CODE => $seed['ingredient_code'],
			self::GRAPH_META_ENTITY_ID       => $seed['ingredient_code'],
		);
		if ( in_array( $post_type, array( 'c99_product_plan', 'product' ), true ) ) {
			$binding_values[ self::META_CANONICAL_PRODUCT_CODE ] = $seed['product_code'];
			$binding_values['_complete99_product_sku']            = $seed['product_code'];
			$binding_values['_sku']                               = $seed['product_code'];
		}
		foreach ( $binding_values as $meta_key => $expected ) {
			$value = (string) get_post_meta( $post_id, $meta_key, true );
			if ( '' !== $value && $expected !== $value ) {
				return self::error(
					'complete99_evaluation_binding_mismatch',
					'An existing managed record has a conflicting canonical binding.'
				);
			}
		}

		if ( $required_status !== (string) $post->post_status ) {
			return self::error(
				'complete99_evaluation_existing_exposure_invalid',
				'An existing evaluation record is not retained in its required held status.'
			);
		}

		$safe_gate_values = array(
			self::PRODUCT_APPROVED              => array( '', 'no' ),
			self::STOCK_AUTHORITY               => array( '', 'pending', 'evaluation_only' ),
			self::LABEL_REVIEWED                 => array( '', 'no' ),
			self::RIGHTS_REVIEWED                => array( '', 'no' ),
			self::TAX_REVIEWED                   => array( '', 'no' ),
			self::MEDIA_PUBLIC_SAFE              => array( '', 'no' ),
			self::META_PUBLIC_SALE               => array( '', 'no' ),
			self::META_SALE_STATE                => array( '', 'held_until_acceptance' ),
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
					'complete99_evaluation_acceptance_gate_completed',
					'An existing evaluation record contains a completed or conflicting acceptance gate.'
				);
			}
		}

		if ( 'product' === $post_type ) {
			return self::validate_existing_woo_product( $post_id, $seed );
		}
		return true;
	}

	private static function validate_existing_woo_product( $post_id, $seed ) {
		$product = wc_get_product( $post_id );
		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			return self::error(
				'complete99_evaluation_product_type_invalid',
				'An evaluation product binding points to a non-simple WooCommerce product.'
			);
		}

		$price          = self::price_string( $seed['evaluation_price_ils'], 'evaluation price' );
		$product_price  = (string) $product->get_price();
		$regular_price  = (string) $product->get_regular_price();
		$sale_price     = (string) $product->get_sale_price();
		$unpriced       = '' === $product_price && '' === $regular_price && '' === $sale_price;
		$evaluation_set = $price === self::normalized_price_readback( $product_price )
			&& $price === self::normalized_price_readback( $regular_price )
			&& '' === $sale_price;
		$unstocked      = ! $product->managing_stock()
			&& null === $product->get_stock_quantity()
			&& 'outofstock' === (string) $product->get_stock_status();
		$evaluation_stock = $product->managing_stock()
			&& 1 === (int) $product->get_stock_quantity()
			&& 'instock' === (string) $product->get_stock_status();
		$sku = (string) $product->get_sku();

		if ( 'hidden' !== (string) $product->get_catalog_visibility()
			|| ( '' !== $sku && $seed['product_code'] !== $sku )
			|| ( ! $unpriced && ! $evaluation_set )
			|| ( ! $unstocked && ! $evaluation_stock )
			|| $product->backorders_allowed()
			|| $product->is_virtual()
			|| $product->is_downloadable()
			|| ( method_exists( $product, 'is_purchasable' ) && $product->is_purchasable() ) ) {
			return self::error(
				'complete99_evaluation_existing_product_hold_invalid',
				'An existing managed WooCommerce binding is not an exact hidden, physical, non-purchasable draft.'
			);
		}
		return true;
	}

	private static function upsert_private_record( $post_type, $seed, $existing_id, $registry_digest ) {
		$post = array(
			'post_type'    => $post_type,
			'post_status'  => 'private',
			'post_title'   => $seed['name']['he'],
			'post_name'    => $seed['ingredient_code'],
			'post_excerpt' => $seed['name']['en'],
			'post_content' => $seed['package_label']['he'] . "\n\n" . $seed['package_label']['en'],
		);
		$post_id = self::store_post( $post, $existing_id, 'private' );
		if ( self::is_error( $post_id ) ) {
			return $post_id;
		}
		$stored = self::store_and_verify_meta(
			$post_id,
			self::held_meta( $seed, $registry_digest )
		);
		if ( self::is_error( $stored ) ) {
			return $stored;
		}
		return $post_id;
	}

	private static function upsert_product_plan( $seed, $existing_id, $registry_digest ) {
		$post = array(
			'post_type'    => 'c99_product_plan',
			'post_status'  => 'private',
			'post_title'   => $seed['name']['he'],
			'post_name'    => $seed['product_code'],
			'post_excerpt' => $seed['name']['en'],
			'post_content' => $seed['package_label']['he'] . "\n\n" . $seed['package_label']['en'],
		);
		$post_id = self::store_post( $post, $existing_id, 'private' );
		if ( self::is_error( $post_id ) ) {
			return $post_id;
		}
		$meta = array_merge(
			self::held_meta( $seed, $registry_digest ),
			array(
				self::META_CANONICAL_PRODUCT_CODE   => $seed['product_code'],
				'_complete99_product_status'       => 'private_evaluation_held',
				'_complete99_product_sku'          => $seed['product_code'],
				'_complete99_product_name_he'      => $seed['name']['he'],
				'_complete99_product_name_en'      => $seed['name']['en'],
				'_complete99_product_unit'         => $seed['package_label']['en'],
				'_complete99_product_price'        => self::price_string( $seed['evaluation_price_ils'], 'evaluation price' ),
				'_complete99_product_currency'     => 'ILS',
				'_complete99_product_stock_source' => 'evaluation_only',
				'_complete99_product_rights'       => 'pending',
			)
		);
		$stored = self::store_and_verify_meta( $post_id, $meta );
		if ( self::is_error( $stored ) ) {
			return $stored;
		}
		return $post_id;
	}

	private static function upsert_woo_product( $seed, $existing_id, $registry_digest ) {
		$price = self::price_string( $seed['evaluation_price_ils'], 'evaluation price' );
		try {
			$product = $existing_id ? wc_get_product( $existing_id ) : new WC_Product_Simple();
			if ( ! $product || ( $existing_id && ! $product->is_type( 'simple' ) ) ) {
				return self::error(
					'complete99_evaluation_product_type_invalid',
					'An evaluation product must be a simple WooCommerce product.'
				);
			}
			$product->set_name( $seed['name']['he'] );
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_description( $seed['package_label']['he'] . "\n\n" . $seed['package_label']['en'] );
			$product->set_short_description( $seed['name']['en'] );
			$product->set_sku( $seed['product_code'] );
			$product->set_regular_price( $price );
			$product->set_price( $price );
			$product->set_sale_price( '' );
			$product->set_manage_stock( true );
			$product->set_stock_quantity( 1 );
			$product->set_stock_status( 'instock' );
			$product->set_backorders( 'no' );
			$product->set_sold_individually( false );
			$product->set_virtual( false );
			$product->set_downloadable( false );
			$product->set_purchase_note( '' );
			$product_id = absint( $product->save() );
		} catch ( \Throwable $error ) {
			return self::error(
				'complete99_evaluation_product_save_failed',
				'The held evaluation product could not be saved: ' . $error->getMessage()
			);
		}
		if ( ! $product_id || ( $existing_id && $product_id !== absint( $existing_id ) ) ) {
			return self::error(
				'complete99_evaluation_product_save_failed',
				'The held evaluation product returned an invalid binding.'
			);
		}

		$stored = self::store_and_verify_meta(
			$product_id,
			array_merge(
				self::held_meta( $seed, $registry_digest ),
				array(
					self::META_CANONICAL_PRODUCT_CODE => $seed['product_code'],
				)
			)
		);
		if ( self::is_error( $stored ) ) {
			return $stored;
		}

		$fresh = wc_get_product( $product_id );
		if ( ! $fresh
			|| ! $fresh->is_type( 'simple' )
			|| 'draft' !== (string) get_post_status( $product_id )
			|| 'hidden' !== (string) $fresh->get_catalog_visibility()
			|| $seed['product_code'] !== (string) $fresh->get_sku()
			|| $price !== self::normalized_price_readback( $fresh->get_price() )
			|| $price !== self::normalized_price_readback( $fresh->get_regular_price() )
			|| '' !== (string) $fresh->get_sale_price()
			|| ! $fresh->managing_stock()
			|| 1 !== (int) $fresh->get_stock_quantity()
			|| 'instock' !== (string) $fresh->get_stock_status()
			|| $fresh->backorders_allowed()
			|| $fresh->is_virtual()
			|| $fresh->is_downloadable()
			|| ( method_exists( $fresh, 'is_purchasable' ) && $fresh->is_purchasable() ) ) {
			return self::error(
				'complete99_evaluation_product_hold_verification_failed',
				'An evaluation product did not remain an exact hidden, physical, non-purchasable draft.'
			);
		}
		return $product_id;
	}

	private static function held_meta( $seed, $registry_digest ) {
		// WordPress stores registered boolean false metadata as an empty string.
		return array(
			self::META_MANAGED           => '1',
			self::META_PRODUCT_CODE      => $seed['product_code'],
			self::META_INGREDIENT_CODE   => $seed['ingredient_code'],
			self::META_MARKET_PROVIDER   => $seed['market_observation']['source_provider'],
			self::META_MARKET_SOURCE     => $seed['market_observation']['source_url'],
			self::META_MARKET_CHECKED_AT => $seed['market_observation']['checked_at'],
			self::META_SOURCE_UPDATED_AT => $seed['market_observation']['source_updated_at'],
			self::META_PRICE             => self::price_string( $seed['evaluation_price_ils'], 'evaluation price' ),
			self::META_STOCK             => 1,
			self::META_PRICE_SCOPE       => 'private_benchmark_only',
			self::META_STOCK_SCOPE       => 'private_evaluation_only',
			self::META_CLASSIFICATION    => $seed['classification'],
			self::META_SALE_STATE        => 'held_until_acceptance',
			self::META_PUBLIC_SALE       => 'no',
			self::META_REGISTRY_DIGEST   => $registry_digest,
			'_complete99_managed'        => '1',
			'_complete99_index_eligible' => '',
			'_complete99_verification_state' => 'evaluation_catalog_held',
			self::PRODUCT_APPROVED       => 'no',
			self::STOCK_AUTHORITY        => 'evaluation_only',
			self::LABEL_REVIEWED         => 'no',
			self::RIGHTS_REVIEWED        => 'no',
			self::TAX_REVIEWED           => 'no',
			self::MEDIA_PUBLIC_SAFE      => 'no',
		);
	}

	private static function store_post( $post, $existing_id, $required_status ) {
		if ( $existing_id ) {
			$post['ID'] = absint( $existing_id );
			$post_id    = wp_update_post( self::slash( $post ), true );
		} else {
			$post_id = wp_insert_post( self::slash( $post ), true );
		}
		if ( self::is_error( $post_id ) || 1 > absint( $post_id ) ) {
			return self::error(
				'complete99_evaluation_post_save_failed',
				'A private evaluation record could not be saved.'
			);
		}
		$post_id = absint( $post_id );
		$stored  = get_post( $post_id );
		if ( ! $stored
			|| $post['post_type'] !== (string) $stored->post_type
			|| $required_status !== (string) $stored->post_status ) {
			return self::error(
				'complete99_evaluation_post_readback_failed',
				'A private evaluation record failed held-status readback.'
			);
		}
		return $post_id;
	}

	private static function store_and_verify_meta( $post_id, $fields ) {
		foreach ( $fields as $key => $value ) {
			$updated = update_post_meta( $post_id, $key, self::slash( $value ) );
			if ( false === $updated
				&& ( ! metadata_exists( 'post', $post_id, $key )
					|| ! self::meta_values_equal( $key, $value, get_post_meta( $post_id, $key, true ) ) ) ) {
				return self::error(
					'complete99_evaluation_meta_write_failed',
					'A private evaluation record could not store its held metadata.'
				);
			}
		}
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $post_id, 'post_meta' );
		}
		foreach ( $fields as $key => $value ) {
			if ( ! metadata_exists( 'post', $post_id, $key )
				|| ! self::meta_values_equal( $key, $value, get_post_meta( $post_id, $key, true ) ) ) {
				return self::error(
					'complete99_evaluation_meta_readback_failed',
					'A private evaluation record failed held metadata readback.'
				);
			}
		}
		return true;
	}

	private static function meta_values_equal( $key, $expected, $actual ) {
		if ( in_array( $key, array( self::META_PRICE, '_complete99_product_price' ), true ) ) {
			return self::normalized_price_readback( $expected ) === self::normalized_price_readback( $actual );
		}
		if ( self::META_STOCK === $key ) {
			return (int) $expected === (int) $actual;
		}
		return self::values_equal( $expected, $actual );
	}

	private static function receipt_is_valid( $receipt, $expected ) {
		if ( ! is_array( $receipt )
			|| self::RECEIPT_SCHEMA !== ( $receipt['schema'] ?? '' )
			|| 'success' !== ( $receipt['status'] ?? '' )
			|| ! in_array( $receipt['mode'] ?? '', array( self::MODE_AUTO, self::MODE_PRIVATE_ONLY ), true )
			|| 1 > (int) ( $receipt['seed_count'] ?? 0 )
			|| (int) ( $receipt['seed_count'] ?? 0 ) !== (int) ( $receipt['ingredient_count'] ?? -1 )
			|| (int) ( $receipt['seed_count'] ?? 0 ) !== (int) ( $receipt['product_plan_count'] ?? -1 )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $receipt['registry_digest'] ?? '' ) )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $receipt['bindings_digest'] ?? '' ) )
			|| ! is_bool( $receipt['woo_available'] ?? null )
			|| ! is_bool( $receipt['woo_materialized'] ?? null )
			|| ! is_string( $receipt['materialized_at'] ?? null )
			|| 1024 < strlen( self::encode( $receipt ) ) ) {
			return false;
		}
		if ( ! empty( $receipt['woo_materialized'] )
			&& (int) $receipt['seed_count'] !== (int) ( $receipt['woo_product_count'] ?? -1 ) ) {
			return false;
		}
		if ( empty( $receipt['woo_materialized'] ) && 0 !== (int) ( $receipt['woo_product_count'] ?? -1 ) ) {
			return false;
		}
		return self::values_equal( $expected, $receipt );
	}

	private static function normalized_price_readback( $value ) {
		try {
			return self::price_string( $value, 'price readback' );
		} catch ( \Throwable $error ) {
			return '';
		}
	}

	private static function price_string( $value, $path ) {
		$number = self::assert_price( $value, $path );
		return number_format( $number, 2, '.', '' );
	}

	private static function assert_price( $value, $path ) {
		if ( ! is_int( $value ) && ! is_float( $value ) && ! is_string( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be a price.' );
		}
		if ( is_string( $value )
			&& 1 !== preg_match( '/\A(?:0|[1-9][0-9]{0,6})(?:\.[0-9]{1,2})?\z/', $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be a two-decimal price.' );
		}
		if ( ! is_numeric( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be numeric.' );
		}
		$number = (float) $value;
		if ( ! is_finite( $number )
			|| $number < 0.01
			|| $number > 1000000
			|| abs( ( $number * 100 ) - round( $number * 100 ) ) > 0.000001 ) {
			throw new \UnexpectedValueException( $path . ' must be a positive price with at most two decimals.' );
		}
		return $number;
	}

	private static function assert_pending_status( $value, $expected, $path ) {
		self::assert_exact_keys( $value, array( 'status' ), $path );
		self::assert_same( $expected, $value['status'], $path . '.status' );
	}

	private static function assert_bilingual_text( $value, $path, $max_length ) {
		self::assert_exact_keys( $value, array( 'he', 'en' ), $path );
		self::assert_text( $value['he'], $path . '.he', false, $max_length );
		self::assert_text( $value['en'], $path . '.en', false, $max_length );
	}

	private static function assert_text( $value, $path, $allow_empty, $max_length ) {
		if ( ! is_string( $value )
			|| ( ! $allow_empty && '' === trim( $value ) )
			|| self::text_length( $value ) > $max_length
			|| false !== strpos( $value, "\0" ) ) {
			throw new \UnexpectedValueException( $path . ' is not bounded plain text.' );
		}
	}

	private static function assert_identifier_list( $value, $path, $allow_empty, $max_count ) {
		self::assert_list( $value, $path, $allow_empty );
		if ( $max_count < count( $value ) ) {
			throw new \UnexpectedValueException( $path . ' exceeds its bound.' );
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

	private static function assert_identifier( $value, $path ) {
		if ( '' === self::sanitize_identifier( $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not a canonical identifier.' );
		}
	}

	private static function assert_price_unit( $value, $path ) {
		if ( ! is_string( $value )
			|| strlen( $value ) > 80
			|| 1 !== preg_match( '/\AILS_per_[a-z0-9]+(?:_[a-z0-9]+)*\z/', $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not a canonical normalized price unit.' );
		}
	}

	private static function assert_product_code( $value, $path ) {
		if ( '' === self::sanitize_product_code( $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not a canonical product code.' );
		}
	}

	private static function assert_ingredient_code( $value, $path ) {
		if ( '' === self::sanitize_ingredient_code( $value ) ) {
			throw new \UnexpectedValueException( $path . ' is not a canonical ingredient code.' );
		}
	}

	private static function assert_https_url( $value, $path ) {
		if ( ! self::https_url_is_valid( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be a valid HTTPS URL.' );
		}
	}

	private static function https_url_is_valid( $value ) {
		if ( ! is_string( $value )
			|| strlen( $value ) > 2048
			|| false === filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		$scheme = strtolower( (string) parse_url( $value, PHP_URL_SCHEME ) );
		$host   = strtolower( (string) parse_url( $value, PHP_URL_HOST ) );
		$user   = parse_url( $value, PHP_URL_USER );
		$pass   = parse_url( $value, PHP_URL_PASS );
		return 'https' === $scheme
			&& '' !== $host
			&& false === strpos( $host, '..' )
			&& ( null === $user || false === $user )
			&& ( null === $pass || false === $pass );
	}

	private static function base_domain( $host ) {
		$host  = strtolower( trim( (string) $host, ". \t\n\r\0\x0B" ) );
		$parts = array_values( array_filter( explode( '.', $host ), 'strlen' ) );
		if ( 2 > count( $parts ) ) {
			return $host;
		}
		$count = 2;
		if ( 3 <= count( $parts ) && 'il' === $parts[ count( $parts ) - 1 ]
			&& in_array( $parts[ count( $parts ) - 2 ], array( 'co', 'org', 'ac', 'net', 'gov', 'muni' ), true ) ) {
			$count = 3;
		}
		return implode( '.', array_slice( $parts, -$count ) );
	}

	private static function assert_date( $value, $path ) {
		if ( ! self::date_is_valid( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be a valid YYYY-MM-DD date.' );
		}
	}

	private static function date_is_valid( $value ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $value ) ) {
			return false;
		}
		$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		$errors = \DateTimeImmutable::getLastErrors();
		return false !== $date
			&& $date->format( 'Y-m-d' ) === $value
			&& ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) );
	}

	private static function assert_exact_keys( $value, $expected, $path ) {
		self::assert_associative_array( $value, $path, true );
		$actual = array_keys( $value );
		sort( $actual, SORT_STRING );
		sort( $expected, SORT_STRING );
		if ( $actual !== $expected ) {
			throw new \UnexpectedValueException( $path . ' has unexpected or missing fields.' );
		}
	}

	private static function assert_associative_array( $value, $path, $allow_empty ) {
		if ( ! is_array( $value )
			|| ( ! $allow_empty && empty( $value ) )
			|| self::is_list( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be an associative array.' );
		}
	}

	private static function assert_list( $value, $path, $allow_empty ) {
		if ( ! is_array( $value )
			|| ( ! $allow_empty && empty( $value ) )
			|| ! self::is_list( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be a list.' );
		}
	}

	private static function is_list( $value ) {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $value );
		}
		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	private static function assert_boolean( $value, $path ) {
		if ( ! is_bool( $value ) ) {
			throw new \UnexpectedValueException( $path . ' must be boolean.' );
		}
	}

	private static function assert_same( $expected, $actual, $path ) {
		if ( $expected !== $actual ) {
			throw new \UnexpectedValueException( $path . ' has an unsafe or unexpected value.' );
		}
	}

	private static function canonicalize( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( self::is_list( $value ) ) {
			return array_map( array( __CLASS__, 'canonicalize' ), $value );
		}
		ksort( $value, SORT_STRING );
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonicalize( $item );
		}
		return $value;
	}

	private static function digest_value( $value ) {
		return hash( 'sha256', self::encode( self::canonicalize( $value ) ) );
	}

	private static function values_equal( $expected, $actual ) {
		return self::digest_value( $expected ) === self::digest_value( $actual );
	}

	private static function encode( $value ) {
		$options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		$json    = function_exists( 'wp_json_encode' )
			? wp_json_encode( $value, $options )
			: json_encode( $value, $options );
		return is_string( $json ) ? $json : '';
	}

	private static function text_length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}

	private static function slash( $value ) {
		return function_exists( 'wp_slash' ) ? wp_slash( $value ) : $value;
	}

	private static function meta_auth_callback( $post_type ) {
		return static function () use ( $post_type ) {
			if ( 'product' === $post_type ) {
				return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
			}
			return current_user_can( 'manage_options' );
		};
	}

	private static function error( $code, $message ) {
		if ( class_exists( 'WP_Error' ) ) {
			return new WP_Error( $code, $message );
		}
		return array(
			'error'   => true,
			'code'    => $code,
			'message' => $message,
		);
	}

	private static function is_error( $value ) {
		if ( function_exists( 'is_wp_error' ) ) {
			return is_wp_error( $value );
		}
		return is_array( $value ) && ! empty( $value['error'] );
	}
}
