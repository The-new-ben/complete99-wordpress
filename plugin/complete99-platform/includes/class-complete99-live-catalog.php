<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Explicit, owner-only materializer for the public WooCommerce catalog.
 *
 * This class never runs during activation or migration. It keeps the public
 * catalog receipt separate from the private evaluation receipt and refuses to
 * adopt products or attachments that it does not own.
 */
final class Complete99_Live_Catalog {
	const NAMESPACE       = 'complete99/v1';
	const RECEIPT_SCHEMA  = 'complete99-live-catalog-receipt/v1';
	const STATUS_SCHEMA   = 'complete99-live-catalog-status/v1';
	const RECOVERY_SCHEMA = 'complete99-live-catalog-recovery/v2';
	const OPTION_RECEIPT  = 'complete99_live_catalog_receipt';
	const OPTION_RECOVERY = 'complete99_live_catalog_recovery_required';
	const OPTION_PICKUP_INSTANCE = 'complete99_live_catalog_pickup_instance';
	const OPTION_PUBLIC_ADDRESS  = 'complete99_live_catalog_public_address';
	const LOCK_TIMEOUT    = 10;
	const EXPECTED_COUNT  = 26;
	const RECOVERY_STATES = array(
		'materializing',
		'commit_unverified',
		'postcommit_verification_failed',
		'rollback_unverified',
		'rollback_cleanup_failed',
		'transaction_start_cleanup_failed',
	);
	const STRICT_READBACK_CAUSES = array(
		'receipt_missing'              => 'complete99_live_catalog_strict_readback_receipt_missing',
		'registry_invalid'             => 'complete99_live_catalog_strict_readback_registry_invalid',
		'woocommerce_dependency'       => 'complete99_live_catalog_strict_readback_woocommerce_dependency',
		'recovery_required'            => 'complete99_live_catalog_strict_readback_recovery_required',
		'recovery_unknown'             => 'complete99_live_catalog_strict_readback_recovery_unknown',
		'receipt_invalid'              => 'complete99_live_catalog_strict_readback_receipt_invalid',
		'store_configuration_mismatch' => 'complete99_live_catalog_strict_readback_store_configuration_mismatch',
		'product_binding_invalid'      => 'complete99_live_catalog_strict_readback_product_binding_invalid',
		'product_readback_mismatch'    => 'complete99_live_catalog_strict_readback_product_readback_mismatch',
		'product_count_mismatch'       => 'complete99_live_catalog_strict_readback_product_count_mismatch',
		'receipt_identity_mismatch'    => 'complete99_live_catalog_strict_readback_receipt_identity_mismatch',
	);

	const META_MANAGED       = '_complete99_live_catalog_managed';
	const META_PRODUCT_CODE  = '_complete99_catalog_product_code';
	const META_REGISTRY      = '_complete99_live_catalog_registry_digest';
	const META_PRICE_SOURCE  = '_complete99_live_catalog_price_scope';
	const META_ASSET_MANAGED = '_complete99_live_catalog_asset_managed';
	const META_ASSET_CODE    = '_complete99_live_catalog_asset_product_code';
	const META_ASSET_SHA     = '_complete99_live_catalog_asset_source_sha256';
	const META_SYNC_ENABLED  = '_complete99_inventory_sync_enabled';
	const META_STOCK_INITIALIZED = '_complete99_live_catalog_stock_initialized';
	const META_PUBLIC_COPY_REVIEWED = '_complete99_live_catalog_public_copy_reviewed';

	const PRODUCT_CODES = array(
		'product-tahini-500g',
		'product-amba-500g',
		'product-hot-sauce-60ml',
		'product-pita-12x50g',
		'product-aubergine-1kg',
		'product-eggs-l-12',
		'product-potato-white-1kg',
		'product-tomato-1kg',
		'product-cucumber-1kg',
		'product-onion-dry-1kg',
		'product-parsley-100g',
		'product-chickpeas-dry-500g',
		'product-beetroot-1kg',
		'product-bulgur-fine-500g',
		'product-couscous-1kg',
		'product-chicken-breast-1kg',
		'product-breadcrumbs-500g',
		'product-ground-beef-1kg',
		'product-tilapia-fillet-1kg',
		'product-tomato-sauce-400g',
		'product-rice-persian-1kg',
		'product-beef-shank-1kg',
		'product-hawayej-soup-100g',
		'product-olive-oil-750ml',
		'product-pickles-brine-320g',
		'product-chicken-liver-1kg',
	);
	const DISH_SLUGS = array(
		'sabich',
		'beet-kubbeh',
		'schnitzel',
		'shakshuka',
		'homemade-meatballs',
		'fish-patties',
		'grilled-chicken',
		'aja-herb-omelet',
		'couscous',
		'yemenite-beef-soup',
		'sabtucha',
		'chicken-liver',
	);

	private static $status_cache = array();

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'save_post_product', array( __CLASS__, 'invalidate_status_cache' ), 30 );
		add_action( 'edit_attachment', array( __CLASS__, 'invalidate_status_cache' ), 30 );
		add_action( 'delete_attachment', array( __CLASS__, 'invalidate_status_cache' ), 30 );
	}

	public static function invalidate_status_cache() {
		self::$status_cache = array();
	}

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/store/catalog-materialization',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => array( __CLASS__, 'can_materialize' ),
					'callback'            => array( __CLASS__, 'rest_readback' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( __CLASS__, 'can_materialize' ),
					'callback'            => array( __CLASS__, 'rest_materialize' ),
					'args'                => array(
						'mode' => array(
							'default'           => 'dry_run',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => static function ( $value ) {
								return in_array( $value, array( 'dry_run', 'apply' ), true );
							},
						),
						'confirm' => array(
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'deployment_id' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => static function ( $value ) {
								return is_string( $value ) && 1 === preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $value );
							},
						),
					),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/catalog-materialization/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( __CLASS__, 'can_materialize' ),
				'callback'            => array( __CLASS__, 'rest_readback' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/catalog-materialization/dry-run',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_materialize' ),
				'callback'            => array( __CLASS__, 'rest_dry_run' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/catalog-materialization/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_materialize' ),
				'callback'            => array( __CLASS__, 'rest_apply' ),
				'args'                => array(
					'confirm' => array(
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
						'validate_callback' => static function ( $value ) {
							return true === rest_sanitize_boolean( $value );
						},
					),
					'deployment_id' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $value ) {
							return is_string( $value ) && 1 === preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $value );
						},
					),
				),
			)
		);
	}

	public static function can_materialize() {
		return current_user_can( 'manage_options' );
	}

	public static function rest_readback() {
		return rest_ensure_response( self::status( true ) );
	}

	public static function rest_dry_run() {
		$result = self::dry_run();
		return self::is_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function rest_apply( $request ) {
		$confirm = is_object( $request ) && method_exists( $request, 'get_param' )
			? rest_sanitize_boolean( $request->get_param( 'confirm' ) )
			: false;
		if ( ! $confirm ) {
			return self::error( 'complete99_live_catalog_confirmation_required', 'Applying the public catalog requires confirm=true.', 400 );
		}
		$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
		$result = self::materialize( $deployment_id );
		return self::is_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function rest_materialize( $request ) {
		$mode = is_object( $request ) && method_exists( $request, 'get_param' )
			? sanitize_key( (string) $request->get_param( 'mode' ) )
			: 'dry_run';
		if ( 'apply' !== $mode ) {
			$result = self::dry_run();
			return self::is_error( $result ) ? $result : rest_ensure_response( $result );
		}

		$confirm = is_object( $request ) && method_exists( $request, 'get_param' )
			? rest_sanitize_boolean( $request->get_param( 'confirm' ) )
			: false;
		if ( ! $confirm ) {
			return self::error(
				'complete99_live_catalog_confirmation_required',
				'Applying the public catalog requires confirm=true.',
				400
			);
		}
		$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
		$result = self::materialize( $deployment_id );
		return self::is_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Read-only plan. No terms, files, posts, metadata or options are written.
	 */
	public static function dry_run() {
		$bundle = self::load_bundle();
		if ( self::is_error( $bundle ) ) {
			return $bundle;
		}
		$dependency = self::woocommerce_dependency();
		if ( self::is_error( $dependency ) ) {
			return $dependency;
		}
		$preflight = self::preflight( $bundle );
		if ( self::is_error( $preflight ) ) {
			return $preflight;
		}

		$actions = array();
		foreach ( self::PRODUCT_CODES as $code ) {
			$is_new = ! $preflight['products'][ $code ];
			$actions[ $code ] = array(
				'product'    => $is_new ? 'create' : 'update',
				'attachment' => $preflight['attachments'][ $code ] ? 'reuse' : 'import',
				'sku'        => $code,
				'stock_action' => $is_new ? 'initialize' : 'preserve',
				'initial_stock' => $is_new ? 1 : null,
				'backorders' => 'no',
				'price_ils'  => $bundle['products'][ $code ]['price'],
				'asset_sha256' => $bundle['products'][ $code ]['asset']['sha256'],
			);
		}

		return array(
			'schema'          => self::STATUS_SCHEMA,
			'mode'            => 'dry_run',
			'write_performed' => false,
			'product_count'   => count( $actions ),
			'registry_digest' => $bundle['registry_digest'],
			'price_digest'    => $bundle['price_digest'],
			'asset_digest'    => $bundle['asset_digest'],
			'relation_digest' => $bundle['relation_digest'],
			'actions'         => $actions,
		);
	}

	/**
	 * Apply the complete allowlisted catalog and verify durable readback.
	 */
	public static function materialize( $deployment_id = '' ) {
		$deployment_id = sanitize_text_field( (string) $deployment_id );
		if ( 1 !== preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $deployment_id ) ) {
			return self::error( 'complete99_live_catalog_deployment_id', 'A valid deployment ID is required for catalog apply.', 400 );
		}
		$bundle = self::load_bundle();
		if ( self::is_error( $bundle ) ) {
			return $bundle;
		}
		$dependency = self::woocommerce_dependency();
		if ( self::is_error( $dependency ) ) {
			return $dependency;
		}

		$lock = self::acquire_lock();
		if ( self::is_error( $lock ) ) {
			return $lock;
		}

		global $wpdb;
		$new_files = array();
		$started   = false;
		$committed = false;
		$commit_attempted = false;
		$marker_written   = false;
		$transaction_started_once = false;
		$marker = array();
		try {
			if ( ! self::flush_catalog_caches() ) {
				return self::error( 'complete99_live_catalog_recovery_cache', 'Catalog apply could not flush caches after acquiring its advisory lock.', 500 );
			}
			$transactional_storage = self::transactional_storage_preflight();
			if ( self::is_error( $transactional_storage ) ) {
				return $transactional_storage;
			}

			/* The advisory lock must protect every read, validation and repair of the marker. */
			$missing_recovery  = new \stdClass();
			$existing_recovery = get_option( self::OPTION_RECOVERY, $missing_recovery );
			if ( $missing_recovery !== $existing_recovery ) {
				if ( ! is_array( $existing_recovery ) ) {
					return self::error( 'complete99_live_catalog_recovery_unknown', 'The catalog recovery boundary has an unknown shape.', 409 );
				}
				$recovery = self::recover_interrupted_materialization(
					$existing_recovery,
					$bundle,
					$transactional_storage
				);
				if ( self::is_error( $recovery ) ) {
					return $recovery;
				}
				if ( ! empty( $recovery['committed'] )
					&& hash_equals( (string) $existing_recovery['deployment_id'], $deployment_id ) ) {
					$receipt = get_option( self::OPTION_RECEIPT, null );
					return array(
						'schema'          => self::STATUS_SCHEMA,
						'mode'            => 'apply',
						'write_performed' => true,
						'recovered'       => true,
						'ready'           => true,
						'product_count'   => self::EXPECTED_COUNT,
						'product_ids'     => $recovery['status']['product_ids'],
						'receipt'         => $receipt,
						'page_cache_purge' => $recovery['page_cache_purge'],
					);
				}
			}

			$repeat = self::preflight( $bundle );
			if ( self::is_error( $repeat ) ) {
				return $repeat;
			}
			$baseline = self::recovery_database_baseline( $repeat );
			$journal  = self::build_recovery_file_journal();
			if ( self::is_error( $journal ) ) {
				return $journal;
			}
			$marker = self::create_recovery_marker(
				$deployment_id,
				$bundle,
				$transactional_storage,
				$baseline,
				$journal
			);
			if ( self::is_error( $marker ) ) {
				return $marker;
			}
			if ( ! self::write_recovery_marker( $marker ) ) {
				throw new \RuntimeException( 'The durable catalog recovery marker failed readback.' );
			}
			$marker_written = true;
			if ( ! self::transaction_statement( 'START TRANSACTION' ) ) {
				throw new \RuntimeException( 'The catalog database transaction could not start.' );
			}
			$started = true;
			$transaction_started_once = true;
			$configuration = self::ensure_store_configuration();
			if ( self::is_error( $configuration ) ) {
				throw new \RuntimeException( $configuration->get_error_code() . ': ' . $configuration->get_error_message() );
			}

			$terms = self::ensure_terms( $bundle['policy'] );
			if ( self::is_error( $terms ) ) {
				throw new \RuntimeException( $terms->get_error_code() . ': ' . $terms->get_error_message() );
			}

			$bindings = array();
			$digests  = array();
			$initial_stock_receipts = array();
			foreach ( self::PRODUCT_CODES as $code ) {
				$record        = $bundle['products'][ $code ];
				$attachment_id = self::ensure_attachment(
					$record,
					$repeat['attachments'][ $code ],
					$new_files
				);
				if ( self::is_error( $attachment_id ) ) {
					throw new \RuntimeException( $attachment_id->get_error_code() . ': ' . $attachment_id->get_error_message() );
				}
				$product_id = self::ensure_product(
					$record,
					$repeat['products'][ $code ],
					$attachment_id,
					$terms,
					$bundle
				);
				if ( self::is_error( $product_id ) ) {
					throw new \RuntimeException( $product_id->get_error_code() . ': ' . $product_id->get_error_message() );
				}
				$bindings[ $code ] = absint( $product_id );
				$initialized_now   = empty( $repeat['products'][ $code ] );
				$initial_stock_receipts[ $code ] = array(
					'product_id'      => absint( $product_id ),
					'policy_quantity' => 1,
					'initialized'     => 'yes' === (string) get_post_meta( $product_id, self::META_STOCK_INITIALIZED, true ),
					'initialized_now' => $initialized_now,
				);
				if ( $initialized_now ) {
					$stock_readback = wc_get_product( $product_id );
					$initial_stock_receipts[ $code ]['readback'] = array(
						'managing_stock' => $stock_readback ? (bool) $stock_readback->managing_stock() : false,
						'quantity'       => $stock_readback ? (int) $stock_readback->get_stock_quantity() : -1,
						'status'         => $stock_readback ? (string) $stock_readback->get_stock_status() : '',
						'backorders'     => $stock_readback ? (string) $stock_readback->get_backorders() : '',
					);
				}
				$identity          = self::product_identity( $product_id, true );
				if ( self::is_error( $identity ) ) {
					throw new \RuntimeException( $identity->get_error_code() . ': ' . $identity->get_error_message() );
				}
				$digests[ $code ] = self::digest( $identity );
			}
			ksort( $bindings, SORT_STRING );
			ksort( $digests, SORT_STRING );
			ksort( $initial_stock_receipts, SORT_STRING );

			$receipt = array(
				'schema'           => self::RECEIPT_SCHEMA,
				'status'           => 'verified',
				'product_count'    => self::EXPECTED_COUNT,
				'registry_digest'  => $bundle['registry_digest'],
				'price_digest'     => $bundle['price_digest'],
				'asset_digest'     => $bundle['asset_digest'],
				'relation_digest'  => $bundle['relation_digest'],
				'configuration_digest' => self::digest( $configuration ),
				'product_ids'      => $bindings,
				'product_digests'  => $digests,
				'initial_stock_receipts' => $initial_stock_receipts,
				'initial_stock_digest'   => self::digest( $initial_stock_receipts ),
				'bindings_digest'  => self::digest( $bindings ),
				'materialized_at'  => gmdate( 'c' ),
				'materialized_by'  => absint( get_current_user_id() ),
				'deployment_id'    => $deployment_id,
				'mutation_id'      => $marker['mutation_id'],
			);
			update_option( self::OPTION_RECEIPT, $receipt, false );
			if ( ! self::flush_catalog_caches() ) {
				throw new \RuntimeException( 'The public catalog cache could not be flushed before strict transactional readback.' );
			}
			$readback = self::status( true, true );
			if ( ! self::readback_receipt_matches_marker( $readback, $marker ) ) {
				if ( ! empty( $readback['ready'] ) ) {
					$readback['reason'] = 'receipt_identity_mismatch';
				}
				throw new \RuntimeException( self::strict_readback_failure_message( $readback ) );
			}

			$commit_attempted = true;
			if ( ! self::transaction_statement( 'COMMIT' ) ) {
				throw new \RuntimeException( 'The catalog database transaction could not commit.' );
			}
			$started = false;
			$committed = true;
			if ( ! self::flush_catalog_caches() ) {
				throw new \RuntimeException( 'The committed public catalog cache could not be flushed.' );
			}
			$readback = self::status( true, true );
			if ( ! self::readback_receipt_matches_marker( $readback, $marker ) ) {
				if ( ! empty( $readback['ready'] ) ) {
					$readback['reason'] = 'receipt_identity_mismatch';
				}
				throw new \RuntimeException( self::strict_readback_failure_message( $readback ) );
			}
			$boundary_cleared = self::clear_recovery_boundary( $marker );
			if ( self::is_error( $boundary_cleared ) ) {
				throw new \RuntimeException( $boundary_cleared->get_error_code() . ': ' . $boundary_cleared->get_error_message() );
			}
			if ( true !== $boundary_cleared ) {
				throw new \RuntimeException( 'The committed catalog could not clear its recovery boundary.' );
			}
			$readback = self::status( true );
			if ( ! self::readback_receipt_matches_marker( $readback, $marker ) ) {
				if ( ! empty( $readback['ready'] ) ) {
					$readback['reason'] = 'receipt_identity_mismatch';
				}
				throw new \RuntimeException( self::strict_readback_failure_message( $readback ) );
			}
			$page_cache_purge = self::purge_public_page_caches_with_retry();
			if ( self::is_error( $page_cache_purge ) ) {
				throw new \RuntimeException( 'The committed public catalog page cache could not be purged.' );
			}
			return array(
				'schema'          => self::STATUS_SCHEMA,
				'mode'            => 'apply',
				'write_performed' => true,
				'ready'           => true,
				'product_count'   => self::EXPECTED_COUNT,
				'product_ids'     => $bindings,
				'receipt'         => $receipt,
				'page_cache_purge' => $page_cache_purge,
			);
		} catch ( \Throwable $error ) {
			$rollback_verified = false;
			if ( $started ) {
				$rollback_verified = self::transaction_statement( 'ROLLBACK' );
				$started = false;
			}
			$cache_flushed = self::flush_catalog_caches();
			$database_reverted = ! $transaction_started_once || $rollback_verified;
			if ( $marker_written && ! $committed && ! $commit_attempted && $database_reverted && $cache_flushed ) {
				$recovered = self::recover_rolled_back_boundary( $marker, $bundle );
				if ( ! self::is_error( $recovered ) ) {
					return self::error( 'complete99_live_catalog_apply_failed', $error->getMessage(), 500 );
				}
				$failed_state = $transaction_started_once ? 'rollback_cleanup_failed' : 'transaction_start_cleanup_failed';
				if ( ! self::restore_recovery_boundary( $marker, $failed_state ) ) {
					return self::error( 'complete99_live_catalog_recovery_restore_failed', 'The rolled-back catalog recovery boundary could not be restored and verified.', 500 );
				}
				return self::error( 'complete99_live_catalog_recovery_required', $recovered->get_error_message(), 500 );
			}
			if ( ! $marker_written && ! $transaction_started_once ) {
				return self::error( 'complete99_live_catalog_apply_failed', $error->getMessage(), 500 );
			}
			if ( $marker_written ) {
				$failed_state  = $committed ? 'postcommit_verification_failed' : ( $commit_attempted ? 'commit_unverified' : 'rollback_unverified' );
				if ( ! self::restore_recovery_boundary( $marker, $failed_state ) ) {
					return self::error( 'complete99_live_catalog_recovery_restore_failed', 'The catalog recovery boundary could not be restored and verified after a failed mutation check.', 500 );
				}
			}
			return self::error( 'complete99_live_catalog_recovery_required', 'Catalog recovery is required after an unverified mutation boundary: ' . $error->getMessage(), 500 );
		} finally {
			self::release_lock( $lock );
		}
	}

	/**
	 * Return only the exact receipt-bound catalog product IDs.
	 */
	public static function product_ids() {
		$status = self::status( false );
		return ! empty( $status['ready'] ) ? array_values( $status['product_ids'] ) : array();
	}

	public static function is_ready() {
		return true === self::status( false )['ready'];
	}

	public static function relations_for_product_code( $product_code ) {
		$bundle = self::load_bundle();
		if ( self::is_error( $bundle ) || ! isset( $bundle['relations']['products'][ $product_code ] ) ) {
			return array();
		}
		return $bundle['relations']['products'][ $product_code ];
	}

	public static function product_codes_for_dish_slug( $dish_slug ) {
		$bundle = self::load_bundle();
		$dish_slug = sanitize_title( (string) $dish_slug );
		if ( self::is_error( $bundle ) || ! isset( $bundle['relations']['dishes'][ $dish_slug ] ) ) {
			return array();
		}
		return $bundle['relations']['dishes'][ $dish_slug ];
	}

	public static function product_ids_for_dish_slug( $dish_slug ) {
		$status = self::status( false );
		if ( empty( $status['ready'] ) ) {
			return array();
		}
		$ids = array();
		foreach ( self::product_codes_for_dish_slug( $dish_slug ) as $code ) {
			if ( isset( $status['product_ids'][ $code ] ) ) {
				$ids[] = absint( $status['product_ids'][ $code ] );
			}
		}
		return array_values( array_filter( $ids ) );
	}

	/**
	 * Readback may be strict for owner evidence or lightweight for storefronts.
	 */
	public static function status( $strict = false, $ignore_recovery_marker = false ) {
		$cache_key = ( $strict ? 'strict' : 'public' ) . ( $ignore_recovery_marker ? '-recovery' : '' );
		if ( isset( self::$status_cache[ $cache_key ] ) ) {
			return self::$status_cache[ $cache_key ];
		}
		$status = array(
			'schema'        => self::STATUS_SCHEMA,
			'ready'         => false,
			'reason'        => 'receipt_missing',
			'product_count' => 0,
			'product_ids'   => array(),
			'strict'        => (bool) $strict,
		);
		$bundle = self::load_bundle();
		if ( self::is_error( $bundle ) ) {
			$status['reason'] = 'registry_invalid';
			self::$status_cache[ $cache_key ] = $status;
			return $status;
		}
		if ( self::is_error( self::woocommerce_dependency() ) ) {
			$status['reason'] = 'woocommerce_dependency';
			self::$status_cache[ $cache_key ] = $status;
			return $status;
		}
		if ( ! $ignore_recovery_marker ) {
			$missing_recovery = new \stdClass();
			$recovery_marker  = get_option( self::OPTION_RECOVERY, $missing_recovery );
			if ( $missing_recovery !== $recovery_marker ) {
				$status['reason'] = is_array( $recovery_marker ) ? 'recovery_required' : 'recovery_unknown';
				self::$status_cache[ $cache_key ] = $status;
				return $status;
			}
		}

		$receipt = get_option( self::OPTION_RECEIPT, null );
		if ( ! self::receipt_contract_is_valid( $receipt, $bundle ) ) {
			$status['reason'] = is_array( $receipt ) ? 'receipt_invalid' : 'receipt_missing';
			self::$status_cache[ $cache_key ] = $status;
			return $status;
		}
		$configuration = self::store_configuration_snapshot();
		if ( self::is_error( $configuration )
			|| ! hash_equals( (string) $receipt['configuration_digest'], self::digest( $configuration ) ) ) {
			$status['reason'] = 'store_configuration_mismatch';
			self::$status_cache[ $cache_key ] = $status;
			return $status;
		}

		foreach ( self::PRODUCT_CODES as $code ) {
			$product_id = absint( $receipt['product_ids'][ $code ] ?? 0 );
			if ( 1 > $product_id
				|| 'publish' !== (string) get_post_status( $product_id )
				|| 'yes' !== (string) get_post_meta( $product_id, self::META_MANAGED, true )
				|| ! hash_equals( $code, (string) get_post_meta( $product_id, self::META_PRODUCT_CODE, true ) ) ) {
				$status['reason'] = 'product_binding_invalid';
				$status['product_code'] = $code;
				self::$status_cache[ $cache_key ] = $status;
				return $status;
			}
			$identity = self::product_identity( $product_id, $strict );
			if ( self::is_error( $identity )
				|| ! hash_equals( (string) $receipt['product_digests'][ $code ], self::digest( $identity ) ) ) {
				$status['reason'] = 'product_readback_mismatch';
				$status['product_code'] = $code;
				self::$status_cache[ $cache_key ] = $status;
				return $status;
			}
			$status['product_ids'][ $code ] = $product_id;
		}
		ksort( $status['product_ids'], SORT_STRING );
		if ( self::EXPECTED_COUNT !== count( array_unique( $status['product_ids'] ) ) ) {
			$status['reason'] = 'product_count_mismatch';
			self::$status_cache[ $cache_key ] = $status;
			return $status;
		}
		$status['ready']         = true;
		$status['reason']        = '';
		$status['product_count'] = self::EXPECTED_COUNT;
		$status['receipt']       = array(
			'schema'          => $receipt['schema'],
			'status'          => $receipt['status'],
			'materialized_at' => $receipt['materialized_at'],
			'deployment_id'   => $receipt['deployment_id'],
			'mutation_id'     => $receipt['mutation_id'],
			'bindings_digest' => $receipt['bindings_digest'],
			'initial_stock_digest' => $receipt['initial_stock_digest'],
		);
		self::$status_cache[ $cache_key ] = $status;
		return $status;
	}

	private static function strict_readback_failure_message( $readback ) {
		$reason = is_array( $readback ) ? (string) ( $readback['reason'] ?? '' ) : '';
		$cause  = self::STRICT_READBACK_CAUSES[ $reason ] ?? 'complete99_live_catalog_runtime_strict_readback';
		$message = $cause . ': Strict public catalog readback failed.';
		$product_code = is_array( $readback ) ? (string) ( $readback['product_code'] ?? '' ) : '';
		if ( in_array( $product_code, self::PRODUCT_CODES, true ) ) {
			$message .= ' ' . $product_code;
		}
		return $message;
	}

	private static function readback_receipt_matches_marker( $readback, $marker ) {
		return is_array( $readback )
			&& ! empty( $readback['ready'] )
			&& is_array( $marker )
			&& hash_equals( (string) ( $marker['mutation_id'] ?? '' ), (string) ( $readback['receipt']['mutation_id'] ?? '' ) )
			&& hash_equals( (string) ( $marker['deployment_id'] ?? '' ), (string) ( $readback['receipt']['deployment_id'] ?? '' ) );
	}

	private static function woocommerce_dependency() {
		if ( ! class_exists( 'WooCommerce' )
			|| ! class_exists( 'WC_Product_Simple' )
			|| ! class_exists( 'WC_Product_Attribute' )
			|| ! function_exists( 'wc_get_product' )
			|| ! function_exists( 'wc_get_product_id_by_sku' )
			|| ! post_type_exists( 'product' ) ) {
			return self::error( 'complete99_live_catalog_woocommerce_required', 'WooCommerce product APIs are required.', 409 );
		}
		return true;
	}

	private static function load_bundle() {
		try {
			$seed_registry  = require COMPLETE99_PLATFORM_DIR . 'data/catalog-product-seeds.php';
			$price_registry = require COMPLETE99_PLATFORM_DIR . 'data/live-catalog-prices.php';
			$policy         = require COMPLETE99_PLATFORM_DIR . 'data/live-catalog-products.php';
			$relations      = require COMPLETE99_PLATFORM_DIR . 'data/live-catalog-relations.php';
			$asset_manifest = require COMPLETE99_PLATFORM_DIR . 'data/generated-asset-manifest.php';
			if ( ! is_array( $seed_registry )
				|| ! is_array( $price_registry )
				|| ! is_array( $policy )
				|| ! is_array( $relations )
				|| ! is_array( $asset_manifest )
				|| 'complete99-catalog-product-seeds/v1' !== ( $seed_registry['schema'] ?? '' )
				|| 'complete99-live-catalog-prices/v1' !== ( $price_registry['schema'] ?? '' )
				|| 'complete99-live-catalog-products/v1' !== ( $policy['schema'] ?? '' )
				|| 'complete99-live-catalog-relations/v1' !== ( $relations['schema'] ?? '' )
				|| 'complete99-generated-asset-manifest/v1' !== ( $asset_manifest['schema'] ?? '' )
				|| 'ILS' !== ( $price_registry['currency'] ?? '' )
				|| 'owner_authorized_opening_retail_price_informed_by_market_observation' !== ( $price_registry['price_scope'] ?? '' )
				|| ! is_array( $price_registry['evidence'] ?? null )
				|| false !== ( $price_registry['evidence']['represents_exact_third_party_observation'] ?? null )
				|| '' === trim( (string) ( $price_registry['evidence']['selection_rule'] ?? '' ) )
				|| true !== ( $policy['catalog_publication_authorized'] ?? null )
				|| false !== ( $policy['supplier_label_reviewed'] ?? null )
				|| false !== ( $policy['country_of_origin_reviewed'] ?? null )
				|| false !== ( $policy['checkout_eligible'] ?? null )
				|| 1 !== ( $policy['initial_stock'] ?? null )
				|| 'no' !== ( $policy['backorders'] ?? '' ) ) {
				throw new \UnexpectedValueException( 'A live catalog registry contract is invalid.' );
			}

			$seeds = array();
			foreach ( (array) ( $seed_registry['products'] ?? array() ) as $seed ) {
				$code = is_array( $seed ) ? (string) ( $seed['product_code'] ?? '' ) : '';
				if ( '' === $code || isset( $seeds[ $code ] ) ) {
					throw new \UnexpectedValueException( 'The seed registry contains a duplicate or empty product code.' );
				}
				$seeds[ $code ] = $seed;
			}
			$assets = array();
			foreach ( (array) ( $asset_manifest['assets'] ?? array() ) as $asset ) {
				$filename = is_array( $asset ) ? (string) ( $asset['filename'] ?? '' ) : '';
				if ( '' !== $filename ) {
					if ( isset( $assets[ $filename ] ) ) {
						throw new \UnexpectedValueException( 'The asset manifest contains a duplicate filename.' );
					}
					$assets[ $filename ] = $asset;
				}
			}

			$expected = self::PRODUCT_CODES;
			$seed_codes = array_keys( $seeds );
			$price_codes = array_keys( (array) ( $price_registry['prices'] ?? array() ) );
			$policy_codes = array_keys( (array) ( $policy['products'] ?? array() ) );
			$relation_codes = array_keys( (array) ( $relations['products'] ?? array() ) );
			$dish_slugs     = array_keys( (array) ( $relations['dishes'] ?? array() ) );
			$expected_dishes = self::DISH_SLUGS;
			sort( $expected, SORT_STRING );
			sort( $seed_codes, SORT_STRING );
			sort( $price_codes, SORT_STRING );
			sort( $policy_codes, SORT_STRING );
			sort( $relation_codes, SORT_STRING );
			sort( $dish_slugs, SORT_STRING );
			sort( $expected_dishes, SORT_STRING );
			if ( self::EXPECTED_COUNT !== count( $expected )
				|| $expected !== $seed_codes
				|| $expected !== $price_codes
				|| $expected !== $policy_codes
				|| $expected !== $relation_codes
				|| $expected_dishes !== $dish_slugs ) {
				throw new \UnexpectedValueException( 'The live catalog allowlist does not have exact 26-product coverage.' );
			}

			$products      = array();
			$asset_receipt = array();
			foreach ( self::PRODUCT_CODES as $code ) {
				$seed       = $seeds[ $code ];
				$public     = $policy['products'][ $code ];
				$relation   = $relations['products'][ $code ];
				$price      = (string) $price_registry['prices'][ $code ];
				$asset_name = sanitize_file_name( (string) ( $seed['image_asset'] ?? '' ) );
				if ( 1 !== preg_match( '/\A\d+\.\d{2}\z/', $price ) || (float) $price <= 0
					|| ! isset( $assets[ $asset_name ] )
					|| ! is_array( $public )
					|| ! is_array( $relation )
					|| (string) ( $relation['ingredient_code'] ?? '' ) !== (string) $seed['ingredient_code']
					|| ! isset( $relation['dish_slugs'] )
					|| ! is_array( $relation['dish_slugs'] )
					|| empty( $relation['dish_slugs'] )
					|| ! isset( $public['ingredients']['he'], $public['ingredients']['en'], $public['allergens']['he'], $public['allergens']['en'], $public['storage']['he'], $public['storage']['en'] )
					|| ! isset( $policy['categories'][ $public['category'] ], $policy['shipping_classes'][ $public['shipping_class'] ] )
					|| ! is_array( $public['tags'] )
					|| '' === trim( (string) $public['weight_kg'] )
					|| (float) $public['weight_kg'] <= 0 ) {
					throw new \UnexpectedValueException( 'A live product policy record is invalid: ' . $code );
				}
				foreach ( $public['tags'] as $tag ) {
					if ( ! isset( $policy['tags'][ $tag ] ) ) {
						throw new \UnexpectedValueException( 'A live product tag is unknown: ' . $code );
					}
				}
				foreach ( $relation['dish_slugs'] as $dish_slug ) {
					if ( ! in_array( $dish_slug, self::DISH_SLUGS, true )
						|| ! in_array( $code, (array) $relations['dishes'][ $dish_slug ], true ) ) {
						throw new \UnexpectedValueException( 'A live product relation is unknown or not reciprocal: ' . $code );
					}
				}
				$asset = $assets[ $asset_name ];
				$sha   = strtolower( (string) ( $asset['sha256'] ?? '' ) );
				$path  = COMPLETE99_PLATFORM_DIR . (string) ( $asset['relative_path'] ?? '' );
				$related = array_map( 'strval', (array) ( $asset['related_product_codes'] ?? array() ) );
				$source_url  = (string) ( $seed['market_observation']['source_url'] ?? '' );
				$accessed_at = (string) ( $seed['market_observation']['checked_at'] ?? '' );
				$observed_price = $seed['market_observation']['observed_price_ils'] ?? null;
				$range_low      = $seed['market_observation']['range_low_ils'] ?? null;
				$range_high     = $seed['market_observation']['range_high_ils'] ?? null;
				if ( 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $sha )
					|| ! in_array( $code, $related, true )
					|| 'owner_approved' !== (string) ( $asset['review_state'] ?? '' )
					|| 'public' !== (string) ( $asset['usage_state'] ?? '' )
					|| 'public_catalog_illustration' !== (string) ( $asset['presentation_scope'] ?? '' )
					|| '2026-07-31' !== (string) ( $asset['owner_authorized_at'] ?? '' )
					|| ! empty( $asset['visual_caveat']['he'] )
					|| ! empty( $asset['visual_caveat']['en'] )
					|| ! empty( $asset['visual_caveats'] )
					|| 'https' !== strtolower( (string) parse_url( $source_url, PHP_URL_SCHEME ) )
					|| 1 !== preg_match( '/\A\d{4}-\d{2}-\d{2}\z/', $accessed_at )
					|| ! is_numeric( $observed_price )
					|| ! is_numeric( $range_low )
					|| ! is_numeric( $range_high )
					|| 0 >= (float) $observed_price
					|| (float) $range_low > (float) $range_high
					|| (float) $observed_price < (float) $range_low
					|| (float) $observed_price > (float) $range_high
					|| ! is_file( $path )
					|| ! is_readable( $path )
					|| ! hash_equals( $sha, (string) hash_file( 'sha256', $path ) ) ) {
					throw new \UnexpectedValueException( 'A bundled product image failed exact hash validation: ' . $code );
				}
				$products[ $code ] = array(
					'code'          => $code,
					'ingredient'    => (string) $seed['ingredient_code'],
					'name'          => $seed['name'],
					'package'       => $seed['package_label'],
					'classification'=> (string) $seed['classification'],
					'relations'     => $seed['relations'],
					'price'         => $price,
					'price_evidence'=> array(
						'source_url'     => $source_url,
						'accessed_at'    => $accessed_at,
						'observed_price' => number_format( (float) $observed_price, 2, '.', '' ),
						'range_low'      => number_format( (float) $range_low, 2, '.', '' ),
						'range_high'     => number_format( (float) $range_high, 2, '.', '' ),
						'selection_rule' => (string) $price_registry['evidence']['selection_rule'],
					),
					'public'        => $public,
					'relations'     => $relation,
					'asset'         => array(
						'filename' => $asset_name,
						'path'     => $path,
						'sha256'   => $sha,
						'width'    => absint( $asset['width'] ?? 0 ),
						'height'   => absint( $asset['height'] ?? 0 ),
					),
				);
				$asset_receipt[ $code ] = array( 'filename' => $asset_name, 'sha256' => $sha );
			}
			foreach ( self::DISH_SLUGS as $dish_slug ) {
				$codes = (array) $relations['dishes'][ $dish_slug ];
				if ( empty( $codes ) || count( $codes ) !== count( array_unique( $codes ) ) ) {
					throw new \UnexpectedValueException( 'A dish relation list is empty or duplicated: ' . $dish_slug );
				}
				foreach ( $codes as $code ) {
					if ( ! in_array( $code, self::PRODUCT_CODES, true )
						|| ! in_array( $dish_slug, (array) $relations['products'][ $code ]['dish_slugs'], true ) ) {
						throw new \UnexpectedValueException( 'A dish relation is unknown or not reciprocal: ' . $dish_slug );
					}
				}
			}
			ksort( $products, SORT_STRING );
			ksort( $asset_receipt, SORT_STRING );
			return array(
				'products'        => $products,
				'policy'          => $policy,
				'price_registry'  => $price_registry,
				'registry_digest' => self::digest( array( $seed_registry['schema'], $seed_registry['registry_reviewed_at'], $products ) ),
				'price_digest'    => self::digest( $price_registry ),
				'asset_digest'    => self::digest( $asset_receipt ),
				'relation_digest' => self::digest( $relations ),
				'relations'       => $relations,
			);
		} catch ( \Throwable $error ) {
			return self::error( 'complete99_live_catalog_registry_invalid', $error->getMessage(), 500 );
		}
	}

	private static function preflight( $bundle ) {
		$products    = array();
		$attachments = array();
		$managed     = self::query_ids( 'product', self::META_MANAGED, 'yes', self::EXPECTED_COUNT + 1 );
		if ( self::is_error( $managed ) ) {
			return $managed;
		}
		foreach ( $managed as $product_id ) {
			$code = (string) get_post_meta( $product_id, self::META_PRODUCT_CODE, true );
			if ( ! in_array( $code, self::PRODUCT_CODES, true ) ) {
				return self::error( 'complete99_live_catalog_unallowlisted_managed_product', 'A managed product is outside the exact allowlist.', 409 );
			}
		}

		foreach ( self::PRODUCT_CODES as $code ) {
			$ids = array();
			foreach ( array( self::META_PRODUCT_CODE, '_sku' ) as $meta_key ) {
				$found = self::query_ids( 'product', $meta_key, $code, 3 );
				if ( self::is_error( $found ) ) {
					return $found;
				}
				$ids = array_merge( $ids, $found );
			}
			$sku_id = absint( wc_get_product_id_by_sku( $code ) );
			if ( $sku_id ) {
				$ids[] = $sku_id;
			}
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
			if ( 1 < count( $ids ) ) {
				return self::error( 'complete99_live_catalog_product_binding_conflict', 'A product code has conflicting WooCommerce bindings: ' . $code, 409 );
			}
			$product_id = $ids ? $ids[0] : 0;
			if ( $product_id ) {
				$product = wc_get_product( $product_id );
				if ( 'yes' !== (string) get_post_meta( $product_id, self::META_MANAGED, true )
					|| ! $product
					|| ! $product->is_type( 'simple' )
					|| 'yes' !== (string) get_post_meta( $product_id, self::META_STOCK_INITIALIZED, true ) ) {
					return self::error( 'complete99_live_catalog_unowned_product_conflict', 'An existing SKU or product binding is not owned by the live catalog: ' . $code, 409 );
				}
			}
			$products[ $code ] = $product_id;

			$asset_ids = self::query_ids( 'attachment', self::META_ASSET_CODE, $code, 3 );
			if ( self::is_error( $asset_ids ) ) {
				return $asset_ids;
			}
			if ( 1 < count( $asset_ids ) ) {
				return self::error( 'complete99_live_catalog_asset_binding_conflict', 'A product image has conflicting attachment bindings: ' . $code, 409 );
			}
			$attachment_id = $asset_ids ? $asset_ids[0] : 0;
			if ( $attachment_id
				&& ( 'yes' !== (string) get_post_meta( $attachment_id, self::META_ASSET_MANAGED, true )
					|| ! hash_equals( $bundle['products'][ $code ]['asset']['sha256'], (string) get_post_meta( $attachment_id, self::META_ASSET_SHA, true ) ) ) ) {
				return self::error( 'complete99_live_catalog_unowned_asset_conflict', 'An existing attachment binding is not owned or has a different hash: ' . $code, 409 );
			}
			$attachments[ $code ] = $attachment_id;
		}
		return array( 'products' => $products, 'attachments' => $attachments );
	}

	/**
	 * Configure only deterministic, non-gateway WooCommerce prerequisites.
	 */
	private static function ensure_store_configuration() {
		$options = array(
			'woocommerce_currency'                => 'ILS',
			'woocommerce_default_country'         => 'IL',
			'woocommerce_store_address'           => '99 Shlomo Ibn Gabirol Street',
			'woocommerce_store_address_2'         => '',
			'woocommerce_store_city'              => 'Tel Aviv',
			'woocommerce_calc_taxes'              => 'yes',
			'woocommerce_prices_include_tax'      => 'yes',
			'woocommerce_tax_based_on'            => 'base',
			'woocommerce_tax_display_shop'        => 'incl',
			'woocommerce_tax_display_cart'        => 'incl',
			'woocommerce_tax_round_at_subtotal'   => 'no',
			'woocommerce_shipping_tax_class'      => 'inherit',
			'woocommerce_manage_stock'            => 'yes',
			'woocommerce_notify_low_stock'        => 'yes',
			'woocommerce_notify_no_stock'         => 'yes',
			'woocommerce_low_stock_amount'        => '1',
			'woocommerce_out_of_stock_amount'     => '0',
			'woocommerce_weight_unit'             => 'kg',
			'woocommerce_dimension_unit'          => 'cm',
			'woocommerce_coming_soon'             => 'no',
			'woocommerce_store_pages_only'        => 'no',
		);
		foreach ( $options as $name => $value ) {
			update_option( $name, $value, false );
			if ( (string) get_option( $name, null ) !== $value ) {
				return self::error( 'complete99_live_catalog_option_readback_failed', 'A WooCommerce baseline option failed readback: ' . $name, 500 );
			}
		}
		$address = array(
			'he' => 'שלמה אבן גבירול 99, תל אביב',
			'en' => '99 Shlomo Ibn Gabirol Street, Tel Aviv',
		);
		update_option( self::OPTION_PUBLIC_ADDRESS, $address, false );
		if ( self::digest( $address ) !== self::digest( get_option( self::OPTION_PUBLIC_ADDRESS, array() ) ) ) {
			return self::error( 'complete99_live_catalog_address_readback_failed', 'The bilingual public address failed readback.', 500 );
		}

		$pages = self::ensure_woocommerce_pages();
		if ( self::is_error( $pages ) ) {
			return $pages;
		}
		$tax_rate_id = self::ensure_standard_vat_rate();
		if ( self::is_error( $tax_rate_id ) ) {
			return $tax_rate_id;
		}
		$pickup_id = self::ensure_local_pickup();
		if ( self::is_error( $pickup_id ) ) {
			return $pickup_id;
		}
		return self::store_configuration_snapshot();
	}

	private static function ensure_woocommerce_pages() {
		if ( ! function_exists( 'wc_create_pages' ) && defined( 'WC_ABSPATH' ) ) {
			$admin_functions = trailingslashit( WC_ABSPATH ) . 'includes/admin/wc-admin-functions.php';
			if ( is_file( $admin_functions ) ) {
				require_once $admin_functions;
			}
		}
		if ( function_exists( 'wc_create_pages' ) ) {
			wc_create_pages();
		}

		$store_id   = Complete99_Content::find_translation_post_id( 'store', 'he', true );
		$terms_id   = Complete99_Content::find_translation_post_id( 'terms', 'he', true );
		$privacy_id = Complete99_Content::find_translation_post_id( 'privacy', 'he', true );
		foreach ( array( $store_id, $terms_id, $privacy_id ) as $page_id ) {
			if ( 1 > absint( $page_id ) || 'publish' !== (string) get_post_status( $page_id ) ) {
				return self::error( 'complete99_live_catalog_public_page_missing', 'A required Complete99 public store or policy page is missing.', 409 );
			}
		}
		$native_shop_id = absint( get_option( 'woocommerce_shop_page_id', 0 ) );
		if ( $native_shop_id === absint( $store_id ) ) {
			update_option( 'woocommerce_shop_page_id', 0, false );
			if ( ! function_exists( 'wc_create_page' ) ) {
				return self::error( 'complete99_live_catalog_native_shop_api_missing', 'WooCommerce native shop-page creation is unavailable.', 409 );
			}
			wc_create_page( 'shop', 'woocommerce_shop_page_id', 'Shop', '' );
			$native_shop_id = absint( get_option( 'woocommerce_shop_page_id', 0 ) );
		}
		if ( 1 > $native_shop_id
			|| $native_shop_id === absint( $store_id )
			|| 'publish' !== (string) get_post_status( $native_shop_id ) ) {
			return self::error( 'complete99_live_catalog_native_shop_page_invalid', 'The native WooCommerce shop page must be published and distinct from the public Complete99 store.', 409 );
		}
		update_option( 'woocommerce_terms_page_id', absint( $terms_id ), false );
		update_option( 'wp_page_for_privacy_policy', absint( $privacy_id ), false );

		$required = array(
			'shop'     => 'woocommerce_shop_page_id',
			'terms'    => 'woocommerce_terms_page_id',
			'privacy'  => 'wp_page_for_privacy_policy',
			'cart'     => 'woocommerce_cart_page_id',
			'checkout' => 'woocommerce_checkout_page_id',
			'account'  => 'woocommerce_myaccount_page_id',
		);
		$pages = array();
		foreach ( $required as $key => $option ) {
			$page_id = absint( get_option( $option, 0 ) );
			if ( 1 > $page_id || 'publish' !== (string) get_post_status( $page_id ) ) {
				return self::error( 'complete99_live_catalog_woocommerce_page_missing', 'A required WooCommerce page is missing: ' . $key, 409 );
			}
			$pages[ $key ] = $page_id;
		}
		$cart_id = absint( $pages['cart'] );
		$cart_update = wp_update_post(
			array(
				'ID'           => $cart_id,
				'post_content' => '[woocommerce_cart]',
			),
			true
		);
		if ( is_wp_error( $cart_update ) || $cart_id !== absint( $cart_update ) ) {
			return self::error( 'complete99_live_catalog_cart_page_write_failed', 'The classic WooCommerce cart page could not be configured.', 500 );
		}
		update_post_meta( $cart_id, '_complete99_classic_cart_managed', 'yes' );
		if ( '[woocommerce_cart]' !== trim( (string) get_post_field( 'post_content', $cart_id ) )
			|| 'yes' !== (string) get_post_meta( $cart_id, '_complete99_classic_cart_managed', true ) ) {
			return self::error( 'complete99_live_catalog_cart_page_readback_failed', 'The classic WooCommerce cart page failed readback.', 500 );
		}
		return $pages;
	}

	private static function ensure_standard_vat_rate() {
		if ( ! class_exists( 'WC_Tax' )
			|| ! is_callable( array( 'WC_Tax', 'get_rates_for_tax_class' ) )
			|| ! is_callable( array( 'WC_Tax', '_insert_tax_rate' ) )
			|| ! is_callable( array( 'WC_Tax', '_update_tax_rate' ) ) ) {
			return self::error( 'complete99_live_catalog_tax_api_missing', 'WooCommerce tax-rate APIs are unavailable.', 409 );
		}
		$matches = array();
		foreach ( (array) WC_Tax::get_rates_for_tax_class( '' ) as $rate ) {
			$record = is_object( $rate ) ? get_object_vars( $rate ) : (array) $rate;
			if ( 'IL' === strtoupper( (string) ( $record['tax_rate_country'] ?? '' ) )
				&& '' === (string) ( $record['tax_rate_state'] ?? '' )
				&& '' === (string) ( $record['tax_rate_class'] ?? '' )
				&& 1 === absint( $record['tax_rate_priority'] ?? 0 ) ) {
				$matches[] = absint( $record['tax_rate_id'] ?? 0 );
			}
		}
		$matches = array_values( array_unique( array_filter( $matches ) ) );
		if ( 1 < count( $matches ) ) {
			return self::error( 'complete99_live_catalog_tax_rate_conflict', 'Multiple wildcard Israeli standard VAT rates require manual reconciliation.', 409 );
		}
		$definition = array(
			'tax_rate_country'  => 'IL',
			'tax_rate_state'    => '',
			'tax_rate'          => '18.0000',
			'tax_rate_name'     => 'מע״מ | VAT',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 1,
			'tax_rate_class'    => '',
		);
		if ( empty( $matches ) ) {
			$tax_rate_id = absint( WC_Tax::_insert_tax_rate( $definition ) );
		} else {
			$tax_rate_id = $matches[0];
			WC_Tax::_update_tax_rate( $tax_rate_id, $definition );
		}
		$readback = $tax_rate_id ? WC_Tax::_get_tax_rate( $tax_rate_id, ARRAY_A ) : array();
		if ( ! is_array( $readback )
			|| 'IL' !== (string) ( $readback['tax_rate_country'] ?? '' )
			|| 18.0 !== (float) ( $readback['tax_rate'] ?? 0 )
			|| '' !== (string) ( $readback['tax_rate_class'] ?? '' ) ) {
			return self::error( 'complete99_live_catalog_tax_rate_readback_failed', 'The 18 percent Israeli standard VAT rate failed readback.', 500 );
		}
		return $tax_rate_id;
	}

	private static function ensure_local_pickup() {
		if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
			return self::error( 'complete99_live_catalog_shipping_api_missing', 'WooCommerce shipping-zone APIs are unavailable.', 409 );
		}
		$zone       = new WC_Shipping_Zone( 0 );
		$matches    = array();
		$methods    = $zone->get_shipping_methods( false );
		foreach ( is_array( $methods ) ? $methods : array() as $method ) {
			if ( is_object( $method ) && 'local_pickup' === (string) ( $method->id ?? '' ) ) {
				$matches[] = absint( $method->instance_id ?? 0 );
			}
		}
		$stored_id = absint( get_option( self::OPTION_PICKUP_INSTANCE, 0 ) );
		if ( $stored_id && ! in_array( $stored_id, $matches, true ) ) {
			return self::error( 'complete99_live_catalog_pickup_binding_conflict', 'The stored local-pickup instance no longer belongs to the default shipping zone.', 409 );
		}
		if ( $stored_id ) {
			$instance_id = $stored_id;
		} elseif ( 1 === count( $matches ) ) {
			$instance_id = $matches[0];
		} elseif ( empty( $matches ) ) {
			$instance_id = absint( $zone->add_shipping_method( 'local_pickup' ) );
		} else {
			return self::error( 'complete99_live_catalog_pickup_conflict', 'Multiple local-pickup methods require manual reconciliation.', 409 );
		}
		if ( 1 > $instance_id ) {
			return self::error( 'complete99_live_catalog_pickup_write_failed', 'Local pickup could not be added.', 500 );
		}
		global $wpdb;
		$table = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
		$method_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT zone_id, method_id, instance_id, method_order, is_enabled FROM {$table} WHERE instance_id = %d LIMIT 1",
				$instance_id
			),
			ARRAY_A
		);
		if ( ! is_array( $method_row )
			|| 0 !== (int) ( $method_row['zone_id'] ?? -1 )
			|| 'local_pickup' !== (string) ( $method_row['method_id'] ?? '' ) ) {
			return self::error( 'complete99_live_catalog_pickup_binding_invalid', 'The local-pickup instance is not bound to the default shipping zone.', 409 );
		}
		if ( 1 !== (int) ( $method_row['is_enabled'] ?? 0 ) ) {
			$updated = $wpdb->update(
				$table,
				array( 'is_enabled' => 1 ),
				array( 'instance_id' => $instance_id ),
				array( '%d' ),
				array( '%d' )
			);
			if ( false === $updated ) {
				return self::error( 'complete99_live_catalog_pickup_enable_failed', 'Local pickup could not be enabled.', 500 );
			}
		}
		$option   = 'woocommerce_local_pickup_' . $instance_id . '_settings';
		$settings = get_option( $option, array() );
		$settings = is_array( $settings ) ? $settings : array();
		$settings['title']      = 'איסוף עצמי | Local pickup';
		$settings['tax_status'] = 'taxable';
		$settings['cost']       = '0';
		update_option( $option, $settings, false );
		update_option( self::OPTION_PICKUP_INSTANCE, $instance_id, false );
		$readback = get_option( $option, array() );
		$method_readback = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT zone_id, method_id, instance_id, method_order, is_enabled FROM {$table} WHERE instance_id = %d LIMIT 1",
				$instance_id
			),
			ARRAY_A
		);
		if ( ! is_array( $readback )
			|| ! is_array( $method_readback )
			|| 0 !== (int) ( $method_readback['zone_id'] ?? -1 )
			|| 'local_pickup' !== (string) ( $method_readback['method_id'] ?? '' )
			|| 1 !== (int) ( $method_readback['is_enabled'] ?? 0 )
			|| '0' !== (string) ( $readback['cost'] ?? '' ) ) {
			return self::error( 'complete99_live_catalog_pickup_readback_failed', 'Local pickup failed readback.', 500 );
		}
		return $instance_id;
	}

	private static function store_configuration_snapshot() {
		$option_names = array(
			'woocommerce_currency',
			'woocommerce_default_country',
			'woocommerce_store_address',
			'woocommerce_store_address_2',
			'woocommerce_store_city',
			'woocommerce_calc_taxes',
			'woocommerce_prices_include_tax',
			'woocommerce_tax_based_on',
			'woocommerce_tax_display_shop',
			'woocommerce_tax_display_cart',
			'woocommerce_tax_round_at_subtotal',
			'woocommerce_shipping_tax_class',
			'woocommerce_manage_stock',
			'woocommerce_notify_low_stock',
			'woocommerce_notify_no_stock',
			'woocommerce_low_stock_amount',
			'woocommerce_out_of_stock_amount',
			'woocommerce_weight_unit',
			'woocommerce_dimension_unit',
			'woocommerce_coming_soon',
			'woocommerce_store_pages_only',
			'woocommerce_shop_page_id',
			'woocommerce_terms_page_id',
			'wp_page_for_privacy_policy',
			'woocommerce_cart_page_id',
			'woocommerce_checkout_page_id',
			'woocommerce_myaccount_page_id',
		);
		$options = array();
		foreach ( $option_names as $name ) {
			$value = self::normalize_store_option_value( $name, get_option( $name, null ) );
			if ( self::is_error( $value ) ) {
				return $value;
			}
			$options[ $name ] = $value;
		}
		$instance_id = absint( get_option( self::OPTION_PICKUP_INSTANCE, 0 ) );
		if ( 1 > $instance_id || ! class_exists( 'WC_Tax' ) || ! is_callable( array( 'WC_Tax', '_get_tax_rate' ) ) ) {
			return self::error( 'complete99_live_catalog_configuration_readback_failed', 'The store configuration cannot be read back completely.', 500 );
		}
		$tax_rates = array();
		foreach ( (array) WC_Tax::get_rates_for_tax_class( '' ) as $rate ) {
			$record = is_object( $rate ) ? get_object_vars( $rate ) : (array) $rate;
			if ( 'IL' === strtoupper( (string) ( $record['tax_rate_country'] ?? '' ) )
				&& '' === (string) ( $record['tax_rate_state'] ?? '' )
				&& '' === (string) ( $record['tax_rate_class'] ?? '' )
				&& 1 === absint( $record['tax_rate_priority'] ?? 0 ) ) {
				$tax_rates[] = $record;
			}
		}
		if ( 1 !== count( $tax_rates ) || 18.0 !== (float) ( $tax_rates[0]['tax_rate'] ?? 0 ) ) {
			return self::error( 'complete99_live_catalog_tax_rate_readback_failed', 'The standard VAT configuration is not exact.', 500 );
		}
		global $wpdb;
		$pickup_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT zone_id, method_id, instance_id, method_order, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d LIMIT 1",
				$instance_id
			),
			ARRAY_A
		);
		$cart_id = absint( $options['woocommerce_cart_page_id'] ?? 0 );
		if ( ! is_array( $pickup_row )
			|| 0 !== (int) ( $pickup_row['zone_id'] ?? -1 )
			|| 'local_pickup' !== (string) ( $pickup_row['method_id'] ?? '' )
			|| 1 !== (int) ( $pickup_row['is_enabled'] ?? 0 )
			|| 1 > $cart_id
			|| 'publish' !== (string) get_post_status( $cart_id )
			|| '[woocommerce_cart]' !== trim( (string) get_post_field( 'post_content', $cart_id ) )
			|| 'yes' !== (string) get_post_meta( $cart_id, '_complete99_classic_cart_managed', true ) ) {
			return self::error( 'complete99_live_catalog_cart_or_pickup_readback_failed', 'The live cart or local-pickup configuration is not exact.', 500 );
		}
		return array(
			'options'              => $options,
			'public_address'       => get_option( self::OPTION_PUBLIC_ADDRESS, array() ),
			'local_pickup_instance'=> $instance_id,
			'local_pickup_settings'=> get_option( 'woocommerce_local_pickup_' . $instance_id . '_settings', array() ),
			'local_pickup_method'  => $pickup_row,
			'classic_cart'         => array( 'page_id' => $cart_id, 'content' => '[woocommerce_cart]', 'published' => true ),
			'standard_vat_rate'    => $tax_rates[0],
		);
	}

	private static function normalize_store_option_value( $name, $value ) {
		$integer_options = array(
			'woocommerce_shop_page_id',
			'woocommerce_terms_page_id',
			'wp_page_for_privacy_policy',
			'woocommerce_cart_page_id',
			'woocommerce_checkout_page_id',
			'woocommerce_myaccount_page_id',
		);
		if ( in_array( (string) $name, $integer_options, true ) ) {
			if ( ( is_int( $value ) && 0 < $value )
				|| ( is_string( $value ) && 1 === preg_match( '/\A[1-9][0-9]*\z/', $value ) ) ) {
				return absint( $value );
			}
			return self::error( 'complete99_live_catalog_option_type_invalid', 'A WooCommerce page option has an invalid type: ' . $name, 500 );
		}
		if ( ! is_string( $value ) ) {
			return self::error( 'complete99_live_catalog_option_type_invalid', 'A WooCommerce text option has an invalid type: ' . $name, 500 );
		}
		return $value;
	}

	private static function ensure_terms( $policy ) {
		$result = array( 'categories' => array(), 'tags' => array(), 'shipping_classes' => array() );
		$groups = array(
			'categories'       => 'product_cat',
			'tags'             => 'product_tag',
			'shipping_classes' => 'product_shipping_class',
		);
		foreach ( $groups as $group => $taxonomy ) {
			foreach ( $policy[ $group ] as $key => $definition ) {
				$term_id = self::ensure_term( $taxonomy, $definition );
				if ( self::is_error( $term_id ) ) {
					return $term_id;
				}
				$result[ $group ][ $key ] = $term_id;
			}
		}
		return $result;
	}

	private static function ensure_term( $taxonomy, $definition ) {
		$slug     = sanitize_title( (string) $definition['slug'] );
		$existing = term_exists( $slug, $taxonomy );
		$term_id  = is_array( $existing ) ? absint( $existing['term_id'] ?? 0 ) : absint( $existing );
		if ( ! $term_id ) {
			$created = wp_insert_term( (string) $definition['name'], $taxonomy, array( 'slug' => $slug ) );
			if ( is_wp_error( $created ) ) {
				return $created;
			}
			$term_id = absint( $created['term_id'] ?? 0 );
		} elseif ( 'yes' !== (string) get_term_meta( $term_id, self::META_MANAGED, true ) ) {
			return self::error( 'complete99_live_catalog_term_conflict', 'A deterministic taxonomy slug is already owned by another record.', 409 );
		}
		if ( 1 > $term_id ) {
			return self::error( 'complete99_live_catalog_term_write_failed', 'A catalog taxonomy term could not be created.', 500 );
		}
		update_term_meta( $term_id, self::META_MANAGED, 'yes' );
		return $term_id;
	}

	private static function ensure_attachment( $record, $existing_id, &$new_files ) {
		$code  = $record['code'];
		$asset = $record['asset'];
		if ( $existing_id ) {
			$file = (string) get_attached_file( $existing_id, true );
			if ( ! is_file( $file ) || ! is_readable( $file ) || ! hash_equals( $asset['sha256'], (string) hash_file( 'sha256', $file ) ) ) {
				return self::error( 'complete99_live_catalog_asset_readback_failed', 'A managed product image no longer matches its approved hash: ' . $code, 409 );
			}
			self::store_attachment_meta( $existing_id, $record );
			return absint( $existing_id );
		}

		$bytes = file_get_contents( $asset['path'] );
		if ( false === $bytes || ! hash_equals( $asset['sha256'], hash( 'sha256', $bytes ) ) ) {
			return self::error( 'complete99_live_catalog_asset_source_failed', 'The bundled product image could not be read safely: ' . $code, 500 );
		}
		$target   = 'complete99-' . substr( $code, strlen( 'product-' ) ) . '-v1.webp';
		$uploaded = wp_upload_bits( $target, null, $bytes );
		unset( $bytes );
		if ( ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) || empty( $uploaded['url'] ) ) {
			return self::error( 'complete99_live_catalog_asset_upload_failed', 'The approved product image could not be imported: ' . $code, 500 );
		}
		$new_files[] = (string) $uploaded['file'];
		if ( ! hash_equals( $asset['sha256'], (string) hash_file( 'sha256', $uploaded['file'] ) ) ) {
			return self::error( 'complete99_live_catalog_asset_upload_hash_failed', 'The imported product image failed exact hash readback: ' . $code, 500 );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/webp',
				'post_title'     => $record['name']['he'] . ' | ' . $record['name']['en'],
				'post_excerpt'   => $record['package']['he'] . ' | ' . $record['package']['en'],
				'post_status'    => 'inherit',
			),
			$uploaded['file']
		);
		if ( is_wp_error( $attachment_id ) || 1 > absint( $attachment_id ) ) {
			return is_wp_error( $attachment_id ) ? $attachment_id : self::error( 'complete99_live_catalog_attachment_write_failed', 'The product attachment could not be created.', 500 );
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
		if ( is_wp_error( $metadata ) || ! is_array( $metadata ) ) {
			return self::error( 'complete99_live_catalog_attachment_metadata_failed', 'The product image metadata could not be generated.', 500 );
		}
		wp_update_attachment_metadata( $attachment_id, $metadata );
		$upload_directory = dirname( (string) $uploaded['file'] );
		foreach ( (array) ( $metadata['sizes'] ?? array() ) as $size ) {
			if ( is_array( $size ) && ! empty( $size['file'] ) ) {
				$new_files[] = $upload_directory . DIRECTORY_SEPARATOR . basename( (string) $size['file'] );
			}
		}
		if ( ! empty( $metadata['original_image'] ) ) {
			$new_files[] = $upload_directory . DIRECTORY_SEPARATOR . basename( (string) $metadata['original_image'] );
		}
		self::store_attachment_meta( $attachment_id, $record );
		return absint( $attachment_id );
	}

	private static function store_attachment_meta( $attachment_id, $record ) {
		update_post_meta( $attachment_id, self::META_ASSET_MANAGED, 'yes' );
		update_post_meta( $attachment_id, self::META_ASSET_CODE, $record['code'] );
		update_post_meta( $attachment_id, self::META_ASSET_SHA, $record['asset']['sha256'] );
		update_post_meta( $attachment_id, Complete99_Commerce::MEDIA_PUBLIC_SAFE, 'yes' );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $record['name']['he'] . ' | ' . $record['name']['en'] );
	}

	private static function ensure_product( $record, $existing_id, $attachment_id, $terms, $bundle ) {
		try {
			$product = $existing_id ? wc_get_product( $existing_id ) : new WC_Product_Simple();
			if ( ! $product || ( $existing_id && ! $product->is_type( 'simple' ) ) ) {
				return self::error( 'complete99_live_catalog_product_type_failed', 'The catalog product is not a simple WooCommerce product.', 409 );
			}
			$public      = $record['public'];
			$name_he     = trim( (string) $record['name']['he'] );
			$name_en     = trim( (string) $record['name']['en'] );
			$package_he  = trim( (string) $record['package']['he'] );
			$package_en  = trim( (string) $record['package']['en'] );
			$description_he = $name_he . ' באריזת ' . $package_he . '. מוצר למטבח הביתי, עם מחיר ומלאי שמתעדכנים בקטלוג.';
			$description_en = $name_en . ' in a ' . $package_en . ' pack. A home-kitchen product with price and stock maintained in the catalog.';

			$product->set_name( $name_he );
			$product->set_slug( sanitize_title( 'complete99-' . substr( $record['code'], strlen( 'product-' ) ) ) );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_sku( $record['code'] );
			$product->set_description( '<p dir="rtl">' . esc_html( $description_he ) . '</p><p dir="ltr">' . esc_html( $description_en ) . '</p>' );
			$product->set_short_description( esc_html( $name_he . ' | ' . $name_en ) );
			$product->set_regular_price( $record['price'] );
			$product->set_price( $record['price'] );
			$product->set_sale_price( '' );
			$product->set_manage_stock( true );
			$sets_initial_stock = ! $existing_id;
			if ( $sets_initial_stock ) {
				$product->set_stock_quantity( 1 );
				$product->set_stock_status( 'instock' );
			}
			$product->set_backorders( 'no' );
			$product->set_sold_individually( false );
			$product->set_virtual( false );
			$product->set_downloadable( false );
			$product->set_weight( (string) $public['weight_kg'] );
			$product->set_tax_status( 'taxable' );
			$product->set_image_id( absint( $attachment_id ) );
			$product->set_gallery_image_ids( array() );
			$product->set_category_ids( array( absint( $terms['categories'][ $public['category'] ] ) ) );
			$product->set_tag_ids( array_map( 'absint', array_intersect_key( $terms['tags'], array_flip( $public['tags'] ) ) ) );
			$product->set_shipping_class_id( absint( $terms['shipping_classes'][ $public['shipping_class'] ] ) );
			$product->set_attributes( self::product_attributes( $record, $bundle['policy'] ) );
			$product_id = absint( $product->save() );
			if ( 1 > $product_id ) {
				return self::error( 'complete99_live_catalog_product_write_failed', 'WooCommerce did not return a product ID.', 500 );
			}
			if ( $sets_initial_stock ) {
				$initial_stock_readback = wc_get_product( $product_id );
				if ( ! $initial_stock_readback
					|| ! $initial_stock_readback->managing_stock()
					|| 1 !== (int) $initial_stock_readback->get_stock_quantity()
					|| 'instock' !== (string) $initial_stock_readback->get_stock_status()
					|| 'no' !== (string) $initial_stock_readback->get_backorders() ) {
					return self::error( 'complete99_live_catalog_initial_stock_failed', 'The initial stock policy could not be read back exactly.', 500 );
				}
			}

			$meta = array(
				self::META_MANAGED                         => 'yes',
				self::META_PRODUCT_CODE                    => $record['code'],
				self::META_REGISTRY                        => $bundle['registry_digest'],
				self::META_PRICE_SOURCE                    => (string) $bundle['price_registry']['price_scope'],
				self::META_SYNC_ENABLED                    => 'yes',
				Complete99_Commerce::PRODUCT_APPROVED      => 'yes',
				Complete99_Commerce::STOCK_AUTHORITY       => 'woocommerce',
				Complete99_Commerce::LABEL_REVIEWED        => ! empty( $bundle['policy']['supplier_label_reviewed'] ) ? 'yes' : 'no',
				Complete99_Commerce::ORIGIN_REVIEWED       => ! empty( $bundle['policy']['country_of_origin_reviewed'] ) ? 'yes' : 'no',
				Complete99_Commerce::CHECKOUT_ELIGIBLE     => ! empty( $bundle['policy']['checkout_eligible'] ) ? 'yes' : 'no',
				Complete99_Commerce::RIGHTS_REVIEWED       => 'yes',
				Complete99_Commerce::TAX_REVIEWED          => 'yes',
				Complete99_Commerce::MEDIA_PUBLIC_SAFE     => 'yes',
				self::META_PUBLIC_COPY_REVIEWED             => 'yes',
				Complete99_Commerce::NAME_HE               => $name_he,
				Complete99_Commerce::NAME_EN               => $name_en,
				Complete99_Commerce::DESCRIPTION_HE        => $description_he,
				Complete99_Commerce::DESCRIPTION_EN        => $description_en,
				Complete99_Commerce::INGREDIENTS_HE        => (string) $public['ingredients']['he'],
				Complete99_Commerce::INGREDIENTS_EN        => (string) $public['ingredients']['en'],
				Complete99_Commerce::ALLERGENS_HE          => (string) $public['allergens']['he'],
				Complete99_Commerce::ALLERGENS_EN          => (string) $public['allergens']['en'],
				Complete99_Commerce::STORAGE_HE            => (string) $public['storage']['he'],
				Complete99_Commerce::STORAGE_EN            => (string) $public['storage']['en'],
				Complete99_Commerce::FULFILMENT_HE         => (string) $bundle['policy']['fulfilment']['he'],
				Complete99_Commerce::FULFILMENT_EN         => (string) $bundle['policy']['fulfilment']['en'],
				Complete99_Commerce::ORIGIN_HE             => '',
				Complete99_Commerce::ORIGIN_EN             => '',
				'_complete99_catalog_ingredient_code'      => $record['ingredient'],
				'_complete99_live_catalog_asset_sha256'    => $record['asset']['sha256'],
				'_complete99_live_catalog_relation_digest' => self::digest( $record['relations'] ),
				'_complete99_live_catalog_facet'           => self::facet_for_classification( $record['classification'] ),
				'_complete99_live_catalog_package_he'      => $package_he,
				'_complete99_live_catalog_package_en'      => $package_en,
				'_complete99_live_catalog_initial_stock'   => '1',
				self::META_STOCK_INITIALIZED                => 'yes',
				'_complete99_live_catalog_currency'        => 'ILS',
			);
			foreach ( $meta as $key => $value ) {
				update_post_meta( $product_id, $key, $value );
			}
			clean_post_cache( $product_id );
			return $product_id;
		} catch ( \Throwable $error ) {
			return self::error( 'complete99_live_catalog_product_write_failed', $error->getMessage(), 500 );
		}
	}

	private static function product_attributes( $record, $policy ) {
		$definitions = array(
			array( 'אריזה | Package', $record['package']['he'] . ' | ' . $record['package']['en'] ),
			array( 'סוג מוצר | Product type', $policy['categories'][ $record['public']['category'] ]['name'] ),
			array( 'אחסון | Storage', $record['public']['storage']['he'] . ' | ' . $record['public']['storage']['en'] ),
		);
		$attributes = array();
		foreach ( $definitions as $position => $definition ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( 0 );
			$attribute->set_name( $definition[0] );
			$attribute->set_options( array( $definition[1] ) );
			$attribute->set_position( $position );
			$attribute->set_visible( true );
			$attribute->set_variation( false );
			$attributes[] = $attribute;
		}
		return $attributes;
	}

	private static function facet_for_classification( $classification ) {
		$facets = array(
			'packaged_shelf_stable'       => 'pantry',
			'fresh_variable_weight'       => 'fresh-produce',
			'chilled_or_frozen_sensitive' => 'chilled-frozen',
			'bakery_short_shelf_life'     => 'bakery',
			'regulated_category'          => 'regulated',
		);
		return isset( $facets[ $classification ] ) ? $facets[ $classification ] : '';
	}

	private static function product_identity( $product_id, $strict_asset ) {
		$product = wc_get_product( absint( $product_id ) );
		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			return self::error( 'complete99_live_catalog_product_readback_failed', 'A product could not be read back as a simple product.', 500 );
		}
		$image_id = absint( $product->get_image_id() );
		$product_code = (string) get_post_meta( $product_id, self::META_PRODUCT_CODE, true );
		$file     = $image_id ? (string) get_attached_file( $image_id, true ) : '';
		$file_sha = '';
		if ( 1 > $image_id
			|| ( function_exists( 'get_post_type' ) && 'attachment' !== get_post_type( $image_id ) )
			|| 'yes' !== (string) get_post_meta( $image_id, self::META_ASSET_MANAGED, true )
			|| ! hash_equals( $product_code, (string) get_post_meta( $image_id, self::META_ASSET_CODE, true ) )
			|| 'yes' !== (string) get_post_meta( $image_id, Complete99_Commerce::MEDIA_PUBLIC_SAFE, true ) ) {
			return self::error( 'complete99_live_catalog_asset_binding_invalid', 'A product image is not bound to its approved public catalog asset.', 500 );
		}
		if ( $strict_asset && is_file( $file ) && is_readable( $file ) ) {
			$file_sha = (string) hash_file( 'sha256', $file );
		}
		if ( $strict_asset && ( '' === $file_sha || ! hash_equals( (string) get_post_meta( $image_id, self::META_ASSET_SHA, true ), $file_sha ) ) ) {
			return self::error( 'complete99_live_catalog_asset_readback_failed', 'A product image failed exact file readback.', 500 );
		}
		$meta_keys = array(
			self::META_MANAGED,
			self::META_PRODUCT_CODE,
			self::META_REGISTRY,
			self::META_PRICE_SOURCE,
			self::META_SYNC_ENABLED,
			Complete99_Commerce::PRODUCT_APPROVED,
			Complete99_Commerce::STOCK_AUTHORITY,
			Complete99_Commerce::LABEL_REVIEWED,
			Complete99_Commerce::ORIGIN_REVIEWED,
			Complete99_Commerce::CHECKOUT_ELIGIBLE,
			Complete99_Commerce::RIGHTS_REVIEWED,
			Complete99_Commerce::TAX_REVIEWED,
			Complete99_Commerce::MEDIA_PUBLIC_SAFE,
			self::META_PUBLIC_COPY_REVIEWED,
			Complete99_Commerce::NAME_HE,
			Complete99_Commerce::NAME_EN,
			Complete99_Commerce::DESCRIPTION_HE,
			Complete99_Commerce::DESCRIPTION_EN,
			Complete99_Commerce::INGREDIENTS_HE,
			Complete99_Commerce::INGREDIENTS_EN,
			Complete99_Commerce::ALLERGENS_HE,
			Complete99_Commerce::ALLERGENS_EN,
			Complete99_Commerce::STORAGE_HE,
			Complete99_Commerce::STORAGE_EN,
			Complete99_Commerce::FULFILMENT_HE,
			Complete99_Commerce::FULFILMENT_EN,
			Complete99_Commerce::ORIGIN_HE,
			Complete99_Commerce::ORIGIN_EN,
			'_complete99_live_catalog_relation_digest',
			'_complete99_live_catalog_facet',
			'_complete99_live_catalog_package_he',
			'_complete99_live_catalog_package_en',
			'_complete99_live_catalog_initial_stock',
			self::META_STOCK_INITIALIZED,
		);
		$meta = array();
		foreach ( $meta_keys as $key ) {
			$meta[ $key ] = get_post_meta( $product_id, $key, true );
		}
		return array(
			'id'                 => absint( $product_id ),
			'post_status'        => (string) get_post_status( $product_id ),
			'sku'                => (string) $product->get_sku(),
			'name'               => (string) $product->get_name(),
			'price'              => (string) $product->get_price(),
			'regular_price'      => (string) $product->get_regular_price(),
			'sale_price'         => (string) $product->get_sale_price(),
			'weight'             => (string) $product->get_weight(),
			'managing_stock'     => (bool) $product->managing_stock(),
			'backorders'         => (string) $product->get_backorders(),
			'catalog_visibility' => (string) $product->get_catalog_visibility(),
			'virtual'            => (bool) $product->get_virtual(),
			'downloadable'       => (bool) $product->get_downloadable(),
			'tax_status'         => (string) $product->get_tax_status(),
			'image_id'           => $image_id,
			'image_sha256'       => (string) get_post_meta( $image_id, self::META_ASSET_SHA, true ),
			'image_managed'      => (string) get_post_meta( $image_id, self::META_ASSET_MANAGED, true ),
			'image_product_code' => (string) get_post_meta( $image_id, self::META_ASSET_CODE, true ),
			'image_public_safe'  => (string) get_post_meta( $image_id, Complete99_Commerce::MEDIA_PUBLIC_SAFE, true ),
			'category_ids'       => array_map( 'absint', $product->get_category_ids() ),
			'tag_ids'            => array_map( 'absint', $product->get_tag_ids() ),
			'shipping_class_id'  => absint( $product->get_shipping_class_id() ),
			'attributes'         => $product->get_attributes(),
			'meta'               => $meta,
		);
	}

	private static function receipt_contract_is_valid( $receipt, $bundle ) {
		if ( ! is_array( $receipt )
			|| self::RECEIPT_SCHEMA !== ( $receipt['schema'] ?? '' )
			|| 'verified' !== ( $receipt['status'] ?? '' )
			|| self::EXPECTED_COUNT !== ( $receipt['product_count'] ?? null )
			|| 1 !== preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', (string) ( $receipt['deployment_id'] ?? '' ) )
			|| 1 !== preg_match( '/\A[A-Za-z0-9-]{16,64}\z/', (string) ( $receipt['mutation_id'] ?? '' ) )
			|| ! isset( $receipt['product_ids'], $receipt['product_digests'], $receipt['initial_stock_receipts'], $receipt['initial_stock_digest'], $receipt['bindings_digest'], $receipt['configuration_digest'], $receipt['materialized_at'] )
			|| ! is_array( $receipt['product_ids'] )
			|| ! is_array( $receipt['product_digests'] )
			|| ! is_array( $receipt['initial_stock_receipts'] )
			|| ! hash_equals( $bundle['registry_digest'], (string) ( $receipt['registry_digest'] ?? '' ) )
			|| ! hash_equals( $bundle['price_digest'], (string) ( $receipt['price_digest'] ?? '' ) )
			|| ! hash_equals( $bundle['asset_digest'], (string) ( $receipt['asset_digest'] ?? '' ) )
			|| ! hash_equals( $bundle['relation_digest'], (string) ( $receipt['relation_digest'] ?? '' ) )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) $receipt['configuration_digest'] )
			|| ! hash_equals( self::digest( $receipt['initial_stock_receipts'] ), (string) $receipt['initial_stock_digest'] )
			|| ! hash_equals( self::digest( $receipt['product_ids'] ), (string) $receipt['bindings_digest'] ) ) {
			return false;
		}
		$codes = array_keys( $receipt['product_ids'] );
		$digests = array_keys( $receipt['product_digests'] );
		$stock_codes = array_keys( $receipt['initial_stock_receipts'] );
		$expected = self::PRODUCT_CODES;
		sort( $codes, SORT_STRING );
		sort( $digests, SORT_STRING );
		sort( $stock_codes, SORT_STRING );
		sort( $expected, SORT_STRING );
		if ( $expected !== $codes || $expected !== $digests || $expected !== $stock_codes ) {
			return false;
		}
		foreach ( self::PRODUCT_CODES as $code ) {
			$stock = $receipt['initial_stock_receipts'][ $code ];
			if ( ! is_array( $stock )
				|| absint( $receipt['product_ids'][ $code ] ) !== absint( $stock['product_id'] ?? 0 )
				|| 1 !== ( $stock['policy_quantity'] ?? null )
				|| true !== ( $stock['initialized'] ?? null )
				|| ! is_bool( $stock['initialized_now'] ?? null ) ) {
				return false;
			}
			if ( true === $stock['initialized_now'] ) {
				$readback = $stock['readback'] ?? null;
				if ( ! is_array( $readback )
					|| true !== ( $readback['managing_stock'] ?? null )
					|| 1 !== ( $readback['quantity'] ?? null )
					|| 'instock' !== ( $readback['status'] ?? '' )
					|| 'no' !== ( $readback['backorders'] ?? '' ) ) {
					return false;
				}
			}
		}
		return true;
	}

	private static function transactional_storage_preflight() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || true !== $wpdb->is_mysql ) {
			return self::error( 'complete99_live_catalog_transaction_driver', 'The live catalog requires transactional MySQL storage.', 500 );
		}
		$tables = array(
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->terms,
			$wpdb->term_taxonomy,
			$wpdb->term_relationships,
			$wpdb->options,
			$wpdb->prefix . 'wc_product_meta_lookup',
			$wpdb->prefix . 'wc_product_attributes_lookup',
			$wpdb->prefix . 'woocommerce_tax_rates',
			$wpdb->prefix . 'woocommerce_tax_rate_locations',
			$wpdb->prefix . 'woocommerce_shipping_zones',
			$wpdb->prefix . 'woocommerce_shipping_zone_locations',
			$wpdb->prefix . 'woocommerce_shipping_zone_methods',
		);
		if ( isset( $wpdb->termmeta ) && '' !== (string) $wpdb->termmeta ) {
			$tables[] = $wpdb->termmeta;
		}
		$engines = array();
		foreach ( array_values( array_unique( array_filter( array_map( 'strval', $tables ) ) ) ) as $table ) {
			$wpdb->last_error = '';
			$engine = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT ENGINE FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s',
					$table
				)
			);
			if ( '' !== trim( (string) $wpdb->last_error ) || 'innodb' !== strtolower( trim( (string) $engine ) ) ) {
				return self::error( 'complete99_live_catalog_transaction_engine', 'A required catalog table is missing or not transactional: ' . $table, 500 );
			}
			$engines[ $table ] = 'InnoDB';
		}
		ksort( $engines, SORT_STRING );
		return $engines;
	}

	private static function transaction_statement( $statement ) {
		global $wpdb;
		if ( ! in_array( $statement, array( 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ), true ) ) {
			return false;
		}
		$wpdb->last_error = '';
		$result = $wpdb->query( $statement );
		return false !== $result && '' === trim( (string) $wpdb->last_error );
	}

	private static function create_recovery_marker( $deployment_id, $bundle, $storage, $baseline, $journal ) {
		if ( ! is_array( $bundle ) || ! is_array( $storage ) || ! is_array( $baseline ) || ! is_array( $journal ) ) {
			return self::error( 'complete99_live_catalog_recovery_baseline', 'The catalog recovery baseline could not be created.', 500 );
		}
		$mutation_id = function_exists( 'wp_generate_uuid4' ) ? strtolower( (string) wp_generate_uuid4() ) : '';
		if ( 1 !== preg_match( '/\A[A-Za-z0-9-]{16,64}\z/', $mutation_id ) ) {
			try {
				$mutation_id = bin2hex( random_bytes( 16 ) );
			} catch ( \Throwable $error ) {
				$mutation_id = hash( 'sha256', uniqid( 'complete99-', true ) . '|' . microtime( true ) );
			}
		}
		$started_by = absint( get_current_user_id() );
		if ( 1 > $started_by ) {
			return self::error( 'complete99_live_catalog_recovery_owner', 'An authenticated catalog owner is required to seal the recovery marker.', 403 );
		}
		$marker = array(
			'schema'              => self::RECOVERY_SCHEMA,
			'state'               => 'materializing',
			'registry_digest'     => $bundle['registry_digest'],
			'price_digest'        => $bundle['price_digest'],
			'asset_digest'        => $bundle['asset_digest'],
			'relation_digest'     => $bundle['relation_digest'],
			'storage_digest'      => self::digest( $storage ),
			'baseline'            => $baseline,
			'baseline_digest'     => self::digest( $baseline ),
			'file_journal'        => $journal,
			'file_journal_digest' => self::digest( $journal ),
			'started_at'          => gmdate( 'c' ),
			'started_by'          => $started_by,
			'deployment_id'       => $deployment_id,
			'mutation_id'         => $mutation_id,
		);
		$marker = self::seal_recovery_marker( $marker );
		return self::recovery_marker_is_valid( $marker, $bundle, $storage )
			? $marker
			: self::error( 'complete99_live_catalog_recovery_marker', 'The catalog recovery marker failed its sealed contract.', 500 );
	}

	private static function recovery_database_baseline( $preflight ) {
		$products    = array_map( 'absint', (array) ( $preflight['products'] ?? array() ) );
		$attachments = array_map( 'absint', (array) ( $preflight['attachments'] ?? array() ) );
		ksort( $products, SORT_STRING );
		ksort( $attachments, SORT_STRING );
		$receipt = get_option( self::OPTION_RECEIPT, null );
		return array(
			'receipt_state'  => null === $receipt ? 'missing' : 'present',
			'receipt_digest' => self::digest( array( 'type' => gettype( $receipt ), 'value' => $receipt ) ),
			'products'       => $products,
			'attachments'    => $attachments,
		);
	}

	private static function recovery_baseline_contract_is_valid( $baseline ) {
		if ( ! is_array( $baseline )
			|| ! in_array( $baseline['receipt_state'] ?? '', array( 'missing', 'present' ), true )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $baseline['receipt_digest'] ?? '' ) )
			|| ! is_array( $baseline['products'] ?? null )
			|| ! is_array( $baseline['attachments'] ?? null ) ) {
			return false;
		}
		$expected = self::PRODUCT_CODES;
		$product_codes = array_keys( $baseline['products'] );
		$attachment_codes = array_keys( $baseline['attachments'] );
		sort( $expected, SORT_STRING );
		sort( $product_codes, SORT_STRING );
		sort( $attachment_codes, SORT_STRING );
		if ( $expected !== $product_codes || $expected !== $attachment_codes ) {
			return false;
		}
		foreach ( self::PRODUCT_CODES as $code ) {
			if ( ! is_int( $baseline['products'][ $code ] )
				|| 0 > $baseline['products'][ $code ]
				|| ! is_int( $baseline['attachments'][ $code ] )
				|| 0 > $baseline['attachments'][ $code ] ) {
				return false;
			}
		}
		return true;
	}

	private static function seal_recovery_marker( $marker ) {
		if ( ! is_array( $marker ) ) {
			return array();
		}
		unset( $marker['marker_digest'] );
		$marker['marker_digest'] = self::digest( $marker );
		return $marker;
	}

	private static function recovery_marker_seal_is_valid( $marker ) {
		if ( ! is_array( $marker )
			|| self::RECOVERY_SCHEMA !== ( $marker['schema'] ?? '' )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $marker['marker_digest'] ?? '' ) ) ) {
			return false;
		}
		$expected_digest = (string) $marker['marker_digest'];
		unset( $marker['marker_digest'] );
		return hash_equals( $expected_digest, self::digest( $marker ) );
	}

	private static function recovery_marker_is_valid( $marker, $bundle, $storage ) {
		if ( ! self::recovery_marker_seal_is_valid( $marker )
			|| ! in_array( $marker['state'] ?? '', self::RECOVERY_STATES, true )
			|| 1 !== preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', (string) ( $marker['deployment_id'] ?? '' ) )
			|| 1 !== preg_match( '/\A[A-Za-z0-9-]{16,64}\z/', (string) ( $marker['mutation_id'] ?? '' ) )
			|| empty( $marker['started_at'] )
			|| 1 > absint( $marker['started_by'] ?? 0 )
			|| ! self::recovery_baseline_contract_is_valid( $marker['baseline'] ?? null )
			|| ! self::recovery_file_journal_contract_is_valid( $marker['file_journal'] ?? null )
			|| ! hash_equals( self::digest( $marker['baseline'] ), (string) ( $marker['baseline_digest'] ?? '' ) )
			|| ! hash_equals( self::digest( $marker['file_journal'] ), (string) ( $marker['file_journal_digest'] ?? '' ) )
			|| ! hash_equals( self::digest( $storage ), (string) ( $marker['storage_digest'] ?? '' ) ) ) {
			return false;
		}
		foreach ( array( 'registry_digest', 'price_digest', 'asset_digest', 'relation_digest' ) as $field ) {
			if ( 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $marker[ $field ] ?? '' ) )
				|| ! hash_equals( (string) $bundle[ $field ], (string) $marker[ $field ] ) ) {
				return false;
			}
		}
		return true;
	}

	private static function transition_recovery_marker( $marker, $state ) {
		if ( ! self::recovery_marker_seal_is_valid( $marker ) || ! in_array( $state, self::RECOVERY_STATES, true ) ) {
			return array();
		}
		$marker['state']     = $state;
		$marker['failed_at'] = gmdate( 'c' );
		return self::seal_recovery_marker( $marker );
	}

	private static function write_recovery_marker( $marker ) {
		if ( ! self::recovery_marker_seal_is_valid( $marker ) ) {
			return false;
		}
		update_option( self::OPTION_RECOVERY, $marker, false );
		$stored = get_option( self::OPTION_RECOVERY, false );
		return self::recovery_marker_seal_is_valid( $stored )
			&& hash_equals( (string) $marker['marker_digest'], (string) $stored['marker_digest'] );
	}

	private static function restore_recovery_boundary( $marker, $state ) {
		$failed_marker = self::transition_recovery_marker( $marker, $state );
		if ( ! self::recovery_marker_seal_is_valid( $failed_marker ) ) {
			return false;
		}
		for ( $attempt = 0; $attempt < 2; $attempt++ ) {
			$written = self::write_recovery_marker( $failed_marker );
			$flushed = self::flush_catalog_caches();
			if ( $written && $flushed && self::write_recovery_marker( $failed_marker ) ) {
				return true;
			}
		}
		return false;
	}

	private static function clear_recovery_marker() {
		delete_option( self::OPTION_RECOVERY );
		$missing = new \stdClass();
		return $missing === get_option( self::OPTION_RECOVERY, $missing );
	}

	private static function clear_recovery_boundary( $marker ) {
		if ( ! self::recovery_marker_seal_is_valid( $marker ) || ! self::clear_recovery_marker() ) {
			return false;
		}
		if ( self::flush_catalog_caches() ) {
			return true;
		}
		if ( ! self::restore_recovery_boundary( $marker, (string) ( $marker['state'] ?? 'materializing' ) ) ) {
			return self::error( 'complete99_live_catalog_recovery_restore_failed', 'The catalog recovery boundary could not be restored after object-cache flush failure.', 500 );
		}
		return false;
	}

	private static function flush_catalog_caches() {
		self::invalidate_status_cache();
		if ( function_exists( 'wp_cache_flush' ) && false === wp_cache_flush() ) {
			return false;
		}
		return true;
	}

	private static function purge_public_page_caches_with_retry() {
		$first = self::purge_public_page_caches();
		if ( ! self::is_error( $first ) ) {
			$first['attempts'] = 1;
			return $first;
		}
		$second = self::purge_public_page_caches();
		if ( ! self::is_error( $second ) ) {
			$second['attempts'] = 2;
			return $second;
		}
		return $second;
	}

	private static function purge_public_page_caches() {
		$report = array(
			'upress'    => array(
				'detected'          => class_exists( '\\Upress\\EzCache\\Cache' ),
				'request_completed' => false,
			),
			'litespeed' => array(
				'listener_detected' => false !== has_action( 'litespeed_purge_all' ),
				'signal_sent'       => false,
			),
		);
		if ( $report['upress']['detected'] ) {
			try {
				if ( ! method_exists( '\\Upress\\EzCache\\Cache', 'instance' ) ) {
					throw new \RuntimeException( 'instance' );
				}
				$cache = \Upress\EzCache\Cache::instance();
				if ( ! is_object( $cache ) || ! method_exists( $cache, 'clear_cache' ) || false === $cache->clear_cache() ) {
					throw new \RuntimeException( 'clear-cache' );
				}
				$report['upress']['request_completed'] = true;
			} catch ( \Throwable $error ) {
				return self::error( 'complete99_live_catalog_page_cache', 'The committed catalog UPress page-cache purge request failed.', 503 );
			}
		}
		try {
			do_action( 'litespeed_purge_all' );
			$report['litespeed']['signal_sent'] = true;
		} catch ( \Throwable $error ) {
			return self::error( 'complete99_live_catalog_page_cache', 'The committed catalog LiteSpeed page-cache purge signal failed.', 503 );
		}
		return $report;
	}

	private static function recovery_baseline_matches( $marker, $bundle ) {
		$preflight = self::preflight( $bundle );
		if ( self::is_error( $preflight ) ) {
			return self::error( 'complete99_live_catalog_recovery_ambiguous', 'The database no longer matches a recoverable catalog baseline.', 409 );
		}
		$current = self::recovery_database_baseline( $preflight );
		return hash_equals( (string) $marker['baseline_digest'], self::digest( $current ) );
	}

	private static function recover_rolled_back_boundary( $marker, $bundle ) {
		if ( ! self::flush_catalog_caches() ) {
			return self::error( 'complete99_live_catalog_recovery_cache', 'Catalog recovery could not flush caches before baseline verification.', 500 );
		}
		$matches = self::recovery_baseline_matches( $marker, $bundle );
		if ( self::is_error( $matches ) || true !== $matches ) {
			return self::error( 'complete99_live_catalog_recovery_ambiguous', 'The interrupted catalog database state is not the sealed pre-transaction baseline.', 409 );
		}
		$cleanup = self::cleanup_recovery_files( $marker['file_journal'] );
		if ( self::is_error( $cleanup ) ) {
			return $cleanup;
		}
		$boundary_cleared = self::clear_recovery_boundary( $marker );
		if ( self::is_error( $boundary_cleared ) ) {
			return $boundary_cleared;
		}
		if ( true !== $boundary_cleared ) {
			return self::error( 'complete99_live_catalog_recovery_marker', 'The rolled-back catalog recovery boundary could not be cleared durably.', 500 );
		}
		$matches = self::recovery_baseline_matches( $marker, $bundle );
		if ( self::is_error( $matches ) || true !== $matches ) {
			if ( ! self::restore_recovery_boundary( $marker, (string) ( $marker['state'] ?? 'rollback_unverified' ) ) ) {
				return self::error( 'complete99_live_catalog_recovery_restore_failed', 'The catalog baseline recovery boundary could not be restored and verified.', 500 );
			}
			return self::error( 'complete99_live_catalog_recovery_ambiguous', 'The catalog baseline changed while its recovery boundary was being cleared.', 409 );
		}
		return true;
	}

	private static function recover_interrupted_materialization( $marker, $bundle, $storage ) {
		if ( ! self::recovery_marker_is_valid( $marker, $bundle, $storage ) ) {
			return self::error( 'complete99_live_catalog_recovery_unknown', 'The catalog recovery marker has an unknown state or digest.', 409 );
		}
		/* A marker-ignoring strict status is safe only after evicting stale object state. */
		if ( ! self::flush_catalog_caches() ) {
			return self::error( 'complete99_live_catalog_recovery_cache', 'Catalog recovery could not flush caches before committed-state verification.', 500 );
		}
		$recoverable_status = self::status( true, true );
		$committed = self::readback_receipt_matches_marker( $recoverable_status, $marker );
		if ( $committed ) {
			if ( ! in_array( $marker['state'], array( 'materializing', 'commit_unverified', 'postcommit_verification_failed' ), true ) ) {
				return self::error( 'complete99_live_catalog_recovery_ambiguous', 'A committed catalog is paired with an incompatible recovery state.', 409 );
			}
			$boundary_cleared = self::clear_recovery_boundary( $marker );
			if ( self::is_error( $boundary_cleared ) ) {
				return $boundary_cleared;
			}
			if ( true !== $boundary_cleared ) {
				return self::error( 'complete99_live_catalog_recovery_marker', 'The committed catalog recovery boundary could not be cleared durably.', 500 );
			}
			$fresh = self::status( true );
			if ( ! self::readback_receipt_matches_marker( $fresh, $marker ) ) {
				if ( ! self::restore_recovery_boundary( $marker, 'postcommit_verification_failed' ) ) {
					return self::error( 'complete99_live_catalog_recovery_restore_failed', 'The committed catalog recovery boundary could not be restored after fresh verification failed.', 500 );
				}
				return self::error( 'complete99_live_catalog_recovery_ambiguous', 'The committed catalog failed fresh verification after marker clearance.', 409 );
			}
			$page_cache_purge = self::purge_public_page_caches_with_retry();
			if ( self::is_error( $page_cache_purge ) ) {
				if ( ! self::restore_recovery_boundary( $marker, 'postcommit_verification_failed' ) ) {
					return self::error( 'complete99_live_catalog_recovery_restore_failed', 'The committed catalog recovery boundary could not be restored and verified after page-cache failure.', 500 );
				}
				return $page_cache_purge;
			}
			return array( 'committed' => true, 'status' => $fresh, 'page_cache_purge' => $page_cache_purge );
		}

		if ( ! in_array( $marker['state'], array( 'materializing', 'commit_unverified', 'rollback_unverified', 'rollback_cleanup_failed', 'transaction_start_cleanup_failed' ), true ) ) {
			return self::error( 'complete99_live_catalog_recovery_ambiguous', 'The recovery state requires a committed receipt that is not present.', 409 );
		}
		$recovered = self::recover_rolled_back_boundary( $marker, $bundle );
		if ( self::is_error( $recovered ) ) {
			return $recovered;
		}
		return array( 'committed' => false, 'status' => null );
	}

	private static function query_ids( $post_type, $meta_key, $meta_value, $limit ) {
		$statuses = function_exists( 'get_post_stati' ) ? array_values( get_post_stati() ) : 'any';
		$ids = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => $statuses,
				'fields'                 => 'ids',
				'posts_per_page'         => absint( $limit ),
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_key'               => $meta_key,
				'meta_value'             => $meta_value,
			)
		);
		if ( ! is_array( $ids ) ) {
			return self::error( 'complete99_live_catalog_query_failed', 'A catalog binding query failed.', 500 );
		}
		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	private static function acquire_lock() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || true !== $wpdb->is_mysql ) {
			return self::error( 'complete99_live_catalog_lock_driver', 'The production catalog requires a MySQL advisory lock.', 500 );
		}
		$name = 'complete99-live-catalog-' . substr( hash( 'sha256', get_current_blog_id() . '|' . $wpdb->prefix . '|' . home_url( '/' ) ), 0, 32 );
		$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, self::LOCK_TIMEOUT ) );
		if ( 1 !== (int) $acquired ) {
			return self::error( 'complete99_live_catalog_locked', 'Another catalog materialization is still running.', 409 );
		}
		return $name;
	}

	private static function release_lock( $name ) {
		global $wpdb;
		if ( is_string( $name ) && '' !== $name && is_object( $wpdb ) ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
		}
	}

	private static function catalog_upload_stems() {
		$stems = array();
		foreach ( self::PRODUCT_CODES as $code ) {
			$stems[] = 'complete99-' . substr( $code, strlen( 'product-' ) ) . '-v1';
		}
		sort( $stems, SORT_STRING );
		return $stems;
	}

	private static function normalize_directory_path( $path ) {
		return rtrim( wp_normalize_path( (string) $path ), '/' );
	}

	private static function directory_paths_are_equal( $left, $right ) {
		$left  = self::normalize_directory_path( $left );
		$right = self::normalize_directory_path( $right );
		return '\\' === DIRECTORY_SEPARATOR ? 0 === strcasecmp( $left, $right ) : $left === $right;
	}

	private static function path_is_within_directory( $path, $directory ) {
		$path      = self::normalize_directory_path( $path );
		$directory = self::normalize_directory_path( $directory );
		if ( '' === $path || '' === $directory ) {
			return false;
		}
		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$path      = strtolower( $path );
			$directory = strtolower( $directory );
		}
		return $path === $directory || 0 === strpos( $path, $directory . '/' );
	}

	private static function relative_upload_path( $path, $basedir ) {
		$path    = self::normalize_directory_path( $path );
		$basedir = self::normalize_directory_path( $basedir );
		if ( ! self::path_is_within_directory( $path, $basedir ) ) {
			return '';
		}
		return self::directory_paths_are_equal( $path, $basedir ) ? '' : ltrim( substr( $path, strlen( $basedir ) ), '/' );
	}

	private static function scan_recovery_upload_directory( $target_dir ) {
		$target_dir = self::normalize_directory_path( $target_dir );
		if ( ! is_dir( $target_dir ) ) {
			return array();
		}
		$target_real = realpath( $target_dir );
		if ( false === $target_real || ! self::directory_paths_are_equal( $target_real, $target_dir ) ) {
			return self::error( 'complete99_live_catalog_recovery_upload_path', 'The catalog upload journal directory cannot be resolved exactly.', 500 );
		}
		$names = scandir( $target_dir );
		if ( false === $names ) {
			return self::error( 'complete99_live_catalog_recovery_upload_scan', 'The catalog upload journal directory cannot be scanned.', 500 );
		}
		$files = array();
		foreach ( $names as $name ) {
			$name = (string) $name;
			if ( 0 !== strpos( $name, 'complete99-' ) ) {
				continue;
			}
			if ( basename( $name ) !== $name || preg_match( '/[\\\\\/]/', $name ) ) {
				return self::error( 'complete99_live_catalog_recovery_upload_name', 'A catalog upload journal filename is unsafe.', 500 );
			}
			$file = $target_dir . '/' . $name;
			$real = realpath( $file );
			if ( is_link( $file ) || ! is_file( $file ) || ! is_readable( $file ) || false === $real
				|| ! self::directory_paths_are_equal( dirname( $real ), $target_dir ) ) {
				return self::error( 'complete99_live_catalog_recovery_upload_entry', 'A catalog upload journal entry is not a regular file in the exact target directory.', 500 );
			}
			$sha  = hash_file( 'sha256', $file );
			$size = filesize( $file );
			if ( false === $sha || false === $size ) {
				return self::error( 'complete99_live_catalog_recovery_upload_read', 'A catalog upload journal file cannot be read exactly.', 500 );
			}
			$files[ $name ] = array( 'sha256' => (string) $sha, 'size' => (int) $size );
		}
		ksort( $files, SORT_STRING );
		return $files;
	}

	private static function build_recovery_file_journal() {
		$uploads = wp_get_upload_dir();
		if ( ! is_array( $uploads ) || ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['path'] ) ) {
			return self::error( 'complete99_live_catalog_recovery_uploads', 'The WordPress uploads directory is unavailable for catalog recovery.', 500 );
		}
		$basedir = realpath( (string) $uploads['basedir'] );
		if ( false === $basedir || ! is_dir( $basedir ) ) {
			return self::error( 'complete99_live_catalog_recovery_uploads', 'The WordPress uploads base directory cannot be resolved.', 500 );
		}
		$target = (string) $uploads['path'];
		if ( ! is_dir( $target ) && ( ! function_exists( 'wp_mkdir_p' ) || ! wp_mkdir_p( $target ) ) ) {
			return self::error( 'complete99_live_catalog_recovery_uploads', 'The exact catalog upload directory cannot be prepared.', 500 );
		}
		$target_real = realpath( $target );
		$basedir     = self::normalize_directory_path( $basedir );
		$target_real = false === $target_real ? '' : self::normalize_directory_path( $target_real );
		if ( '' === $target_real || ! self::path_is_within_directory( $target_real, $basedir ) ) {
			return self::error( 'complete99_live_catalog_recovery_uploads', 'The catalog upload directory is outside the exact WordPress uploads base.', 500 );
		}
		$baseline_files = self::scan_recovery_upload_directory( $target_real );
		if ( self::is_error( $baseline_files ) ) {
			return $baseline_files;
		}
		$stems = self::catalog_upload_stems();
		return array(
			'uploads_basedir'  => $basedir,
			'target_dir'       => $target_real,
			'target_relative'  => self::relative_upload_path( $target_real, $basedir ),
			'allowed_stems'    => $stems,
			'allowlist_digest' => self::digest( $stems ),
			'baseline_files'   => $baseline_files,
			'baseline_digest'  => self::digest( $baseline_files ),
		);
	}

	private static function recovery_file_journal_contract_is_valid( $journal ) {
		if ( ! is_array( $journal )
			|| empty( $journal['uploads_basedir'] )
			|| empty( $journal['target_dir'] )
			|| ! is_string( $journal['target_relative'] ?? null )
			|| ! is_array( $journal['allowed_stems'] ?? null )
			|| ! is_array( $journal['baseline_files'] ?? null )
			|| ! self::path_is_within_directory( $journal['target_dir'], $journal['uploads_basedir'] )
			|| self::relative_upload_path( $journal['target_dir'], $journal['uploads_basedir'] ) !== $journal['target_relative'] ) {
			return false;
		}
		$stems = self::catalog_upload_stems();
		if ( $stems !== array_values( $journal['allowed_stems'] )
			|| ! hash_equals( self::digest( $stems ), (string) ( $journal['allowlist_digest'] ?? '' ) )
			|| ! hash_equals( self::digest( $journal['baseline_files'] ), (string) ( $journal['baseline_digest'] ?? '' ) ) ) {
			return false;
		}
		foreach ( $journal['baseline_files'] as $name => $identity ) {
			if ( ! is_string( $name ) || basename( $name ) !== $name || 0 !== strpos( $name, 'complete99-' )
				|| ! is_array( $identity )
				|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $identity['sha256'] ?? '' ) )
				|| ! is_int( $identity['size'] ?? null ) || 0 > $identity['size'] ) {
				return false;
			}
		}
		return true;
	}

	private static function catalog_upload_filename_is_allowed( $filename, $stems ) {
		foreach ( $stems as $stem ) {
			$pattern = '/\A' . preg_quote( (string) $stem, '/' ) . '(?:-\d+)?(?:-(?:scaled|rotated))?(?:-\d+x\d+)?\.webp\z/';
			if ( 1 === preg_match( $pattern, (string) $filename ) ) {
				return true;
			}
		}
		return false;
	}

	private static function recovery_file_is_referenced( $file, $journal ) {
		global $wpdb;
		$real = realpath( $file );
		if ( false === $real || ! is_object( $wpdb )
			|| ! self::path_is_within_directory( $real, $journal['target_dir'] )
			|| ! self::directory_paths_are_equal( dirname( $real ), $journal['target_dir'] ) ) {
			return self::error( 'complete99_live_catalog_recovery_file_scope', 'A recovery candidate is outside the sealed upload directory.', 500 );
		}
		$relative = self::relative_upload_path( $real, $journal['uploads_basedir'] );
		$like     = '%' . $wpdb->esc_like( basename( $real ) ) . '%';
		$wpdb->last_error = '';
		$meta_references = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE (meta_key = %s AND meta_value = %s) OR meta_value LIKE %s",
				'_wp_attached_file',
				$relative,
				$like
			)
		);
		if ( '' !== trim( (string) $wpdb->last_error ) ) {
			return self::error( 'complete99_live_catalog_recovery_reference_query', 'Catalog recovery could not verify upload metadata references.', 500 );
		}
		$wpdb->last_error = '';
		$guid_references = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND guid LIKE %s",
				'attachment',
				$like
			)
		);
		if ( '' !== trim( (string) $wpdb->last_error ) ) {
			return self::error( 'complete99_live_catalog_recovery_reference_query', 'Catalog recovery could not verify upload post references.', 500 );
		}
		return 0 < (int) $meta_references || 0 < (int) $guid_references;
	}

	private static function cleanup_recovery_files( $journal ) {
		if ( ! self::recovery_file_journal_contract_is_valid( $journal ) ) {
			return self::error( 'complete99_live_catalog_recovery_journal_unknown', 'The catalog file recovery journal is invalid.', 409 );
		}
		$uploads = wp_get_upload_dir();
		$current_basedir = is_array( $uploads ) && ! empty( $uploads['basedir'] ) ? realpath( (string) $uploads['basedir'] ) : false;
		if ( false === $current_basedir
			|| ! self::directory_paths_are_equal( $current_basedir, $journal['uploads_basedir'] ) ) {
			return self::error( 'complete99_live_catalog_recovery_uploads_changed', 'The WordPress uploads base changed after the catalog journal was sealed.', 409 );
		}
		if ( ! is_dir( $journal['target_dir'] ) ) {
			return empty( $journal['baseline_files'] )
				? array( 'deleted' => array(), 'verified' => true )
				: self::error( 'complete99_live_catalog_recovery_baseline_missing', 'A preexisting catalog upload directory is missing.', 409 );
		}
		$target_real = realpath( $journal['target_dir'] );
		if ( false === $target_real
			|| ! self::directory_paths_are_equal( $target_real, $journal['target_dir'] ) ) {
			return self::error( 'complete99_live_catalog_recovery_uploads_changed', 'The sealed catalog upload target changed after journaling.', 409 );
		}
		$current = self::scan_recovery_upload_directory( $journal['target_dir'] );
		if ( self::is_error( $current ) ) {
			return $current;
		}
		foreach ( $journal['baseline_files'] as $name => $identity ) {
			if ( ! isset( $current[ $name ] )
				|| ! hash_equals( (string) $identity['sha256'], (string) $current[ $name ]['sha256'] )
				|| (int) $identity['size'] !== (int) $current[ $name ]['size'] ) {
				return self::error( 'complete99_live_catalog_recovery_baseline_changed', 'A preexisting Complete99 upload changed after journaling.', 409 );
			}
		}
		$deleted = array();
		foreach ( array_diff( array_keys( $current ), array_keys( $journal['baseline_files'] ) ) as $name ) {
			$file = self::normalize_directory_path( $journal['target_dir'] ) . '/' . $name;
			if ( ! self::catalog_upload_filename_is_allowed( $name, $journal['allowed_stems'] ) ) {
				return self::error( 'complete99_live_catalog_recovery_file_ambiguous', 'A new Complete99 upload is outside the sealed catalog filename allowlist.', 409 );
			}
			$referenced = self::recovery_file_is_referenced( $file, $journal );
			if ( self::is_error( $referenced ) ) {
				return $referenced;
			}
			if ( $referenced ) {
				return self::error( 'complete99_live_catalog_recovery_file_referenced', 'A new Complete99 upload is referenced and was preserved for manual investigation.', 409 );
			}
			if ( ! function_exists( 'wp_delete_file' ) ) {
				return self::error( 'complete99_live_catalog_recovery_delete_unavailable', 'WordPress file deletion is unavailable for exact catalog cleanup.', 500 );
			}
			wp_delete_file( $file );
			if ( file_exists( $file ) ) {
				return self::error( 'complete99_live_catalog_recovery_delete_failed', 'An unreferenced catalog recovery file could not be deleted exactly.', 500 );
			}
			$deleted[] = $name;
		}
		$readback = self::scan_recovery_upload_directory( $journal['target_dir'] );
		if ( self::is_error( $readback )
			|| ! hash_equals( (string) $journal['baseline_digest'], self::digest( $readback ) ) ) {
			return self::error( 'complete99_live_catalog_recovery_cleanup_readback', 'The catalog upload baseline failed exact cleanup readback.', 500 );
		}
		return array( 'deleted' => $deleted, 'verified' => true );
	}

	private static function canonicalize( $value ) {
		if ( is_object( $value ) ) {
			if ( method_exists( $value, 'get_data' ) ) {
				$value = $value->get_data();
			} else {
				$value = get_object_vars( $value );
			}
		}
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$is_list = empty( $value ) || array_keys( $value ) === range( 0, count( $value ) - 1 );
		if ( ! $is_list ) {
			ksort( $value, SORT_STRING );
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonicalize( $item );
		}
		return $value;
	}

	private static function digest( $value ) {
		$json = wp_json_encode( self::canonicalize( $value ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return is_string( $json ) ? hash( 'sha256', $json ) : '';
	}

	private static function error( $code, $message, $status ) {
		return new WP_Error( $code, $message, array( 'status' => absint( $status ) ) );
	}

	private static function is_error( $value ) {
		return function_exists( 'is_wp_error' ) && is_wp_error( $value );
	}
}
