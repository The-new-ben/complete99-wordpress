<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Signed inventory handoff from Complete99 OS to exact WooCommerce products.
 *
 * Loading this file has no side effects. The plugin owner must explicitly call
 * boot() after the REST and commerce dependencies have loaded.
 */
final class Complete99_Inventory_Bridge {
	const NAMESPACE      = 'complete99/v1';
	const PAYLOAD_SCHEMA = 'complete99-inventory-sync/v1';
	const RECEIPT_SCHEMA = 'complete99-inventory-sync-receipt/v1';

	const OPTION_RECEIPTS = 'complete99_inventory_sync_receipts';
	const OPTION_LOCK     = 'complete99_inventory_sync_lock';

	const META_PRODUCT_CODE        = '_complete99_catalog_product_code';
	const META_SYNC_ENABLED        = '_complete99_inventory_sync_enabled';
	const META_SYNC_VERSION        = '_complete99_inventory_sync_version';
	const META_SYNC_DIGEST         = '_complete99_inventory_sync_digest';
	const META_SYNC_BATCH          = '_complete99_inventory_sync_batch_id';
	const META_SYNC_SOURCE         = '_complete99_inventory_sync_source';
	const META_SYNC_MODE           = '_complete99_inventory_sync_mode';
	const META_SYNC_REASON         = '_complete99_inventory_sync_reason';
	const META_SYNC_UPDATED_AT     = '_complete99_inventory_sync_updated_at';
	const META_EVALUATION_QUANTITY = '_complete99_evaluation_inventory_quantity';

	const EVALUATION_META_MANAGED      = '_complete99_evaluation_catalog_managed';
	const EVALUATION_META_PRODUCT_CODE = '_complete99_evaluation_product_code';
	const EVALUATION_META_PRICE        = '_complete99_evaluation_price_ils';
	const EVALUATION_META_STOCK        = '_complete99_evaluation_stock';
	const EVALUATION_META_PRICE_SCOPE  = '_complete99_evaluation_price_scope';
	const EVALUATION_META_STOCK_SCOPE  = '_complete99_evaluation_stock_scope';
	const EVALUATION_META_SALE_STATE   = '_complete99_evaluation_sale_state';
	const EVALUATION_META_PUBLIC_SALE  = '_complete99_evaluation_public_sale_eligible';
	const EVALUATION_META_REGISTRY_DIGEST = '_complete99_evaluation_registry_digest';

	const PRODUCT_APPROVED  = '_complete99_store_approved';
	const STOCK_AUTHORITY   = '_complete99_stock_authority';
	const LABEL_REVIEWED    = '_complete99_product_label_reviewed';
	const RIGHTS_REVIEWED   = '_complete99_product_rights_reviewed';
	const TAX_REVIEWED      = '_complete99_product_tax_reviewed';
	const MEDIA_PUBLIC_SAFE = '_complete99_media_public_safe';

	const MAX_ITEMS       = 100;
	const MAX_RECEIPTS    = 50;
	const MAX_CLOCK_SKEW  = 300;
	const MAX_VERSION     = 2147483647;
	const MAX_QUANTITY    = 1000000;
	const LOCK_SECONDS    = 30;

	private static $booted = false;

	/**
	 * Register the private metadata and signed endpoint hooks.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'init', array( __CLASS__, 'register_meta' ), 9 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register metadata without making any field public through REST.
	 */
	public static function register_meta() {
		$auth = static function () {
			return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		};

		$fields = array(
			self::META_PRODUCT_CODE => array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_product_code' ),
			),
			self::META_SYNC_ENABLED => array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			),
			self::META_SYNC_VERSION => array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_version' ),
			),
			self::META_SYNC_DIGEST => array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_digest' ),
			),
			self::META_SYNC_BATCH => array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_identifier' ),
			),
			self::META_SYNC_SOURCE => array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_identifier' ),
			),
			self::META_SYNC_MODE => array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_mode' ),
			),
			self::META_SYNC_REASON => array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_identifier' ),
			),
			self::META_SYNC_UPDATED_AT => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			self::META_EVALUATION_QUANTITY => array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_quantity' ),
			),
		);

		foreach ( $fields as $meta_key => $definition ) {
			register_post_meta(
				'product',
				$meta_key,
				array(
					'type'              => $definition['type'],
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => $definition['sanitize_callback'],
					'auth_callback'     => $auth,
				)
			);
		}
	}

	/**
	 * Register a signed private write route.
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/inventory/sync',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( 'Complete99_REST', 'verify_sync_signature' ),
				'callback'            => array( __CLASS__, 'sync_inventory' ),
			)
		);
	}

	/**
	 * Validate, apply and independently read back one inventory batch.
	 */
	public static function sync_inventory( WP_REST_Request $request ) {
		$payload = self::validate_payload( (string) $request->get_body() );
		if ( self::is_error( $payload ) ) {
			return $payload;
		}
		$migration = self::migration_guard();
		if ( self::is_error( $migration ) ) {
			return $migration;
		}

		$lock = self::acquire_lock();
		if ( self::is_error( $lock ) ) {
			return $lock;
		}

		try {
			$receipts = self::read_receipts();
			if ( self::is_error( $receipts ) ) {
				return $receipts;
			}

			$batch_digest = self::value_digest( $payload );
			$existing     = self::find_batch_receipt( $receipts, $payload['batch_id'] );
			if ( self::is_error( $existing ) ) {
				return $existing;
			}
			if ( is_array( $existing ) ) {
				if ( empty( $existing['digest'] )
					|| ! self::is_digest( $existing['digest'] )
					|| ! hash_equals( $existing['digest'], $batch_digest ) ) {
					return self::error(
						'complete99_inventory_batch_conflict',
						'The inventory batch ID is already bound to different content.',
						409
					);
				}
				$receipt = isset( $existing['receipt'] ) && is_array( $existing['receipt'] )
					? $existing['receipt']
					: array();
				if ( self::RECEIPT_SCHEMA !== ( isset( $receipt['schema'] ) ? $receipt['schema'] : '' )
					|| ! isset( $receipt['digest'] )
					|| ! hash_equals( $batch_digest, (string) $receipt['digest'] ) ) {
					return self::error(
						'complete99_inventory_receipt_corrupt',
						'The stored inventory receipt failed integrity validation.',
						500
					);
				}
				$receipt['idempotent'] = true;
				return self::private_response( $receipt );
			}

			$plans = self::preflight( $payload );
			if ( self::is_error( $plans ) ) {
				return $plans;
			}

			$attempted = array();
			$items     = array();
			$writes    = 0;
			foreach ( $plans as $plan ) {
				if ( $plan['write'] ) {
					$attempted[] = $plan;
					$applied = self::apply_plan( $plan, $payload );
					if ( self::is_error( $applied ) ) {
						$rolled_back = self::rollback_plans( array_reverse( $attempted ) );
						if ( ! $rolled_back ) {
							return self::error(
								'complete99_inventory_rollback_failed',
								'The inventory batch failed and its prior state could not be fully restored.',
								500
							);
						}
						return $applied;
					}
					++$writes;
				} else {
					$verified = self::verify_plan_readback( $plan, $payload['mode'] );
					if ( self::is_error( $verified ) ) {
						$rolled_back = self::rollback_plans( array_reverse( $attempted ) );
						if ( ! $rolled_back ) {
							return self::error(
								'complete99_inventory_rollback_failed',
								'The inventory batch failed and its prior state could not be fully restored.',
								500
							);
						}
						return $verified;
					}
				}
				$items[] = array(
					'product_code' => $plan['item']['product_code'],
					'product_id'   => $plan['product_id'],
					'version'      => $plan['item']['version'],
					'quantity'     => $plan['item']['quantity'],
					'state'        => $plan['write'] ? 'applied' : 'unchanged',
				);
			}

			$receipt = array(
				'schema'          => self::RECEIPT_SCHEMA,
				'status'          => 'accepted',
				'batch_id'        => $payload['batch_id'],
				'source'          => $payload['source'],
				'mode'            => $payload['mode'],
				'generated_at'    => $payload['generated_at'],
				'accepted_at'     => gmdate( 'c' ),
				'digest'          => $batch_digest,
				'item_count'      => count( $items ),
				'write_count'     => $writes,
				'unchanged_count' => count( $items ) - $writes,
				'idempotent'      => false,
				'items'           => $items,
			);

			$stored = self::store_receipt( $receipts, $receipt );
			if ( self::is_error( $stored ) ) {
				$receipt_rolled_back = self::restore_receipts( $receipts );
				$rolled_back = self::rollback_plans( array_reverse( $attempted ) );
				if ( ! $receipt_rolled_back || ! $rolled_back ) {
					return self::error(
						'complete99_inventory_rollback_failed',
						'The inventory receipt failed and the prior product state could not be fully restored.',
						500
					);
				}
				return $stored;
			}

			return self::private_response( $receipt );
		} finally {
			self::release_lock( $lock );
		}
	}

	/**
	 * Return an exact, normalized v1 payload or a validation error.
	 */
	private static function validate_payload( $raw ) {
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) || self::is_list( $decoded ) || JSON_ERROR_NONE !== json_last_error() ) {
			return self::error( 'complete99_inventory_json', 'The inventory body must be one JSON object.', 400 );
		}
		if ( ! self::has_exact_keys(
			$decoded,
			array( 'schema', 'batch_id', 'generated_at', 'source', 'mode', 'items' )
		) ) {
			return self::error(
				'complete99_inventory_fields',
				'The inventory payload has missing or unknown top-level fields.',
				400
			);
		}
		if ( self::PAYLOAD_SCHEMA !== $decoded['schema'] ) {
			return self::error( 'complete99_inventory_schema', 'The inventory schema is unsupported.', 400 );
		}
		if ( ! self::is_identifier( $decoded['batch_id'], 128 ) ) {
			return self::error( 'complete99_inventory_batch_id', 'The inventory batch ID is invalid.', 400 );
		}
		if ( ! self::is_identifier( $decoded['source'], 80 ) ) {
			return self::error( 'complete99_inventory_source', 'The inventory source is invalid.', 400 );
		}
		if ( ! is_string( $decoded['mode'] ) || ! in_array( $decoded['mode'], array( 'evaluation', 'commerce' ), true ) ) {
			return self::error( 'complete99_inventory_mode', 'The inventory mode is unsupported.', 400 );
		}

		$generated_at = self::parse_rfc3339( $decoded['generated_at'] );
		if ( false === $generated_at ) {
			return self::error( 'complete99_inventory_generated_at', 'The inventory generation timestamp is invalid.', 400 );
		}
		if ( abs( time() - $generated_at ) > self::MAX_CLOCK_SKEW ) {
			return self::error(
				'complete99_inventory_generated_at_stale',
				'The inventory batch is outside the accepted freshness window.',
				409
			);
		}

		if ( ! is_array( $decoded['items'] )
			|| ! self::is_list( $decoded['items'] )
			|| empty( $decoded['items'] )
			|| self::MAX_ITEMS < count( $decoded['items'] ) ) {
			return self::error(
				'complete99_inventory_items',
				'The inventory batch must contain between 1 and 100 items.',
				400
			);
		}

		$items = array();
		$seen  = array();
		foreach ( $decoded['items'] as $index => $item ) {
			if ( ! is_array( $item )
				|| self::is_list( $item )
				|| ! self::has_exact_keys( $item, array( 'product_code', 'version', 'quantity', 'reason' ) ) ) {
				return self::error(
					'complete99_inventory_item_fields',
					'An inventory item has missing or unknown fields.',
					400
				);
			}
			if ( ! self::is_product_code( $item['product_code'] ) ) {
				return self::error( 'complete99_inventory_product_code', 'An inventory product code is invalid.', 400 );
			}
			if ( isset( $seen[ $item['product_code'] ] ) ) {
				return self::error(
					'complete99_inventory_duplicate_item',
					'An inventory product code appears more than once in the batch.',
					409
				);
			}
			$seen[ $item['product_code'] ] = $index;
			if ( ! is_int( $item['version'] )
				|| 1 > $item['version']
				|| self::MAX_VERSION < $item['version'] ) {
				return self::error( 'complete99_inventory_version', 'An inventory version is invalid.', 400 );
			}
			if ( ! is_int( $item['quantity'] )
				|| 0 > $item['quantity']
				|| self::MAX_QUANTITY < $item['quantity'] ) {
				return self::error( 'complete99_inventory_quantity', 'An inventory quantity is invalid.', 400 );
			}
			if ( ! self::is_identifier( $item['reason'], 80 ) ) {
				return self::error( 'complete99_inventory_reason', 'An inventory reason code is invalid.', 400 );
			}
			$items[] = array(
				'product_code' => $item['product_code'],
				'version'      => $item['version'],
				'quantity'     => $item['quantity'],
				'reason'       => $item['reason'],
			);
		}
		usort(
			$items,
			static function ( $left, $right ) {
				return strcmp( $left['product_code'], $right['product_code'] );
			}
		);

		return array(
			'schema'       => self::PAYLOAD_SCHEMA,
			'batch_id'     => $decoded['batch_id'],
			'generated_at' => $decoded['generated_at'],
			'source'       => $decoded['source'],
			'mode'         => $decoded['mode'],
			'items'        => $items,
		);
	}

	/**
	 * Refuse inventory writes until the current migration and held catalog are exact.
	 */
	private static function migration_guard() {
		if ( ! defined( 'COMPLETE99_PLATFORM_VERSION' )
			|| ! class_exists( 'Complete99_Platform', false )
			|| ! is_callable( array( 'Complete99_Platform', 'migration_failed' ) )
			|| ! is_callable( array( 'Complete99_Platform', 'evaluation_catalog_ready' ) )
			|| Complete99_Platform::migration_failed()
			|| COMPLETE99_PLATFORM_VERSION !== (string) get_option( 'complete99_platform_version', '' )
			|| true !== Complete99_Platform::evaluation_catalog_ready() ) {
			return self::error(
				'complete99_inventory_migration_incomplete',
				'Inventory synchronization is unavailable until the private catalog migration is complete.',
				503
			);
		}
		return true;
	}

	/**
	 * Resolve and validate every target before the first write.
	 */
	private static function preflight( $payload ) {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
			return self::error(
				'complete99_inventory_woocommerce_required',
				'WooCommerce is required for inventory synchronization.',
				503
			);
		}
		if ( 'commerce' === $payload['mode'] ) {
			if ( ! function_exists( 'wc_update_product_stock' )
				|| ! function_exists( 'wc_update_product_stock_status' ) ) {
				return self::error(
					'complete99_inventory_stock_api_required',
					'The WooCommerce managed-stock API is unavailable.',
					503
				);
			}
			if ( ! class_exists( 'Complete99_Commerce' )
				|| ! is_callable( array( 'Complete99_Commerce', 'is_ready' ) )
				|| true !== Complete99_Commerce::is_ready() ) {
				return self::error(
					'complete99_inventory_store_not_ready',
					'Commerce inventory synchronization requires global store readiness.',
					409
				);
			}
		}

		$plans = array();
		foreach ( $payload['items'] as $item ) {
			$product_id = self::resolve_product_id( $item['product_code'] );
			if ( self::is_error( $product_id ) ) {
				return $product_id;
			}
			$product = wc_get_product( $product_id );
			if ( ! is_object( $product )
				|| ! method_exists( $product, 'is_type' )
				|| ! method_exists( $product, 'get_catalog_visibility' )
				|| ! method_exists( $product, 'get_sku' )
				|| ! method_exists( $product, 'get_price' )
				|| ! method_exists( $product, 'get_regular_price' )
				|| ! method_exists( $product, 'get_sale_price' )
				|| ! method_exists( $product, 'managing_stock' )
				|| ! method_exists( $product, 'get_stock_quantity' )
				|| ! method_exists( $product, 'get_stock_status' )
				|| ! method_exists( $product, 'backorders_allowed' )
				|| ! method_exists( $product, 'is_virtual' )
				|| ! method_exists( $product, 'is_downloadable' )
				|| ! method_exists( $product, 'is_purchasable' )
				|| ! method_exists( $product, 'set_stock_quantity' )
				|| ! method_exists( $product, 'set_stock_status' )
				|| ! method_exists( $product, 'save' ) ) {
				return self::error(
					'complete99_inventory_product_invalid',
					'The exact inventory binding is not a supported WooCommerce product.',
					422
				);
			}

			$post_status = (string) get_post_status( $product_id );
			if ( 'evaluation' === $payload['mode'] ) {
				if ( ! self::evaluation_target_is_exactly_held(
					$product_id,
					$product,
					$item['product_code']
				) ) {
					return self::error(
						'complete99_inventory_evaluation_target',
						'Evaluation inventory can target only an exact managed and held WooCommerce draft.',
						422
					);
				}
			} else {
				if ( 'publish' !== $post_status
					|| 'yes' !== (string) get_post_meta( $product_id, self::META_SYNC_ENABLED, true )
					|| 'woocommerce' !== (string) get_post_meta( $product_id, self::STOCK_AUTHORITY, true )
					|| true !== (bool) $product->managing_stock() ) {
					return self::error(
						'complete99_inventory_commerce_target',
						'Commerce inventory requires a published, sync-enabled, WooCommerce-managed product.',
						422
					);
				}
			}

			$stored = self::stored_sync_state( $product_id );
			if ( self::is_error( $stored ) ) {
				return $stored;
			}
			$item_digest = self::item_digest( $payload['source'], $payload['mode'], $item );
			if ( $item['version'] < $stored['version'] ) {
				return self::error(
					'complete99_inventory_stale_version',
					'An inventory item version is older than the stored version.',
					409
				);
			}
			if ( $item['version'] === $stored['version']
				&& ( empty( $stored['digest'] ) || ! hash_equals( $stored['digest'], $item_digest ) ) ) {
				return self::error(
					'complete99_inventory_version_conflict',
					'An inventory item version is already bound to different content.',
					409
				);
			}

			$plans[] = array(
				'product_id' => $product_id,
				'product'    => $product,
				'item'       => $item,
				'item_digest'=> $item_digest,
				'mode'       => $payload['mode'],
				'write'      => $item['version'] > $stored['version'],
				'snapshot'   => self::snapshot_product( $product_id, $product ),
			);
		}
		return $plans;
	}

	/**
	 * Confirm that an evaluation target is the exact hidden draft created by the
	 * evaluation catalog. No inventory value is returned or exposed.
	 */
	private static function evaluation_target_is_exactly_held( $product_id, $product, $product_code ) {
		$receipt = get_option( 'complete99_evaluation_catalog_receipt', null );
		$registry_digest = is_array( $receipt ) && isset( $receipt['registry_digest'] )
			? (string) $receipt['registry_digest']
			: '';
		$evaluation_price = (string) get_post_meta( $product_id, self::EVALUATION_META_PRICE, true );
		$stored_registry_digest = (string) get_post_meta(
			$product_id,
			self::EVALUATION_META_REGISTRY_DIGEST,
			true
		);
		$normalized_evaluation_price = self::normalized_price( $evaluation_price );

		return '' !== $normalized_evaluation_price
			&& 'draft' === (string) get_post_status( $product_id )
			&& $product->is_type( 'simple' )
			&& 'hidden' === (string) $product->get_catalog_visibility()
			&& hash_equals( $product_code, (string) $product->get_sku() )
			&& $normalized_evaluation_price === self::normalized_price( $product->get_price() )
			&& $normalized_evaluation_price === self::normalized_price( $product->get_regular_price() )
			&& '' === (string) $product->get_sale_price()
			&& true === (bool) $product->managing_stock()
			&& 1 === (int) $product->get_stock_quantity()
			&& 'instock' === (string) $product->get_stock_status()
			&& ! $product->backorders_allowed()
			&& ! $product->is_virtual()
			&& ! $product->is_downloadable()
			&& ! $product->is_purchasable()
			&& '1' === (string) get_post_meta( $product_id, self::EVALUATION_META_MANAGED, true )
			&& hash_equals(
				$product_code,
				(string) get_post_meta( $product_id, self::EVALUATION_META_PRODUCT_CODE, true )
			)
			&& 1 === (int) get_post_meta( $product_id, self::EVALUATION_META_STOCK, true )
			&& 'private_benchmark_only' === (string) get_post_meta( $product_id, self::EVALUATION_META_PRICE_SCOPE, true )
			&& 'private_evaluation_only' === (string) get_post_meta( $product_id, self::EVALUATION_META_STOCK_SCOPE, true )
			&& 'held_until_acceptance' === (string) get_post_meta( $product_id, self::EVALUATION_META_SALE_STATE, true )
			&& 'no' === (string) get_post_meta( $product_id, self::EVALUATION_META_PUBLIC_SALE, true )
			&& 'no' === (string) get_post_meta( $product_id, self::PRODUCT_APPROVED, true )
			&& 'evaluation_only' === (string) get_post_meta( $product_id, self::STOCK_AUTHORITY, true )
			&& 'no' === (string) get_post_meta( $product_id, self::LABEL_REVIEWED, true )
			&& 'no' === (string) get_post_meta( $product_id, self::RIGHTS_REVIEWED, true )
			&& 'no' === (string) get_post_meta( $product_id, self::TAX_REVIEWED, true )
			&& 'no' === (string) get_post_meta( $product_id, self::MEDIA_PUBLIC_SAFE, true )
			&& 1 === preg_match( '/\A[a-f0-9]{64}\z/', $registry_digest )
			&& hash_equals( $registry_digest, $stored_registry_digest );
	}

	private static function normalized_price( $value ) {
		$value = is_int( $value ) || is_float( $value ) || is_string( $value )
			? trim( (string) $value )
			: '';
		if ( 1 !== preg_match( '/\A(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?\z/', $value ) ) {
			return '';
		}
		return number_format( (float) $value, 2, '.', '' );
	}

	/**
	 * Bind by the private canonical product code only.
	 */
	private static function resolve_product_id( $product_code ) {
		$statuses = function_exists( 'get_post_stati' ) ? array_values( get_post_stati() ) : 'any';
		$ids      = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => $statuses,
				'posts_per_page'         => 2,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => self::META_PRODUCT_CODE,
						'value'   => $product_code,
						'compare' => '=',
					),
				),
			)
		);
		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
		if ( empty( $ids ) ) {
			return self::error(
				'complete99_inventory_product_binding_missing',
				'No product has the exact private inventory binding.',
				422
			);
		}
		if ( 1 !== count( $ids ) ) {
			return self::error(
				'complete99_inventory_product_binding_duplicate',
				'More than one product has the same private inventory binding.',
				409
			);
		}
		$product_id = $ids[0];
		if ( function_exists( 'get_post_type' ) && 'product' !== get_post_type( $product_id ) ) {
			return self::error(
				'complete99_inventory_product_binding_invalid',
				'The private inventory binding does not point to a product.',
				422
			);
		}
		if ( ! hash_equals( $product_code, (string) get_post_meta( $product_id, self::META_PRODUCT_CODE, true ) ) ) {
			return self::error(
				'complete99_inventory_product_binding_readback',
				'The private inventory binding failed exact readback.',
				500
			);
		}
		return $product_id;
	}

	/**
	 * Read and validate the monotonic state for a product.
	 */
	private static function stored_sync_state( $product_id ) {
		$version_exists = metadata_exists( 'post', $product_id, self::META_SYNC_VERSION );
		$digest_exists  = metadata_exists( 'post', $product_id, self::META_SYNC_DIGEST );
		if ( ! $version_exists ) {
			if ( $digest_exists ) {
				return self::error(
					'complete99_inventory_state_corrupt',
					'The stored inventory version state is incomplete.',
					500
				);
			}
			return array( 'version' => 0, 'digest' => '' );
		}
		$raw_version = get_post_meta( $product_id, self::META_SYNC_VERSION, true );
		$version     = is_int( $raw_version ) ? $raw_version : ( ctype_digit( (string) $raw_version ) ? (int) $raw_version : 0 );
		$digest      = (string) get_post_meta( $product_id, self::META_SYNC_DIGEST, true );
		if ( 1 > $version || self::MAX_VERSION < $version || ! $digest_exists || ! self::is_digest( $digest ) ) {
			return self::error(
				'complete99_inventory_state_corrupt',
				'The stored inventory synchronization state is invalid.',
				500
			);
		}
		return array( 'version' => $version, 'digest' => $digest );
	}

	/**
	 * Apply one already-preflighted plan and verify all persisted values.
	 */
	private static function apply_plan( $plan, $payload ) {
		$product_id = $plan['product_id'];
		$item       = $plan['item'];

		if ( 'commerce' === $payload['mode'] ) {
			try {
				$stored_quantity = wc_update_product_stock( $product_id, $item['quantity'], 'set' );
				$stored_status   = wc_update_product_stock_status(
					$product_id,
					0 < $item['quantity'] ? 'instock' : 'outofstock'
				);
			} catch ( \Throwable $error ) {
				return self::error(
					'complete99_inventory_stock_write',
					'WooCommerce could not store the inventory quantity.',
					500
				);
			}
			if ( false === $stored_quantity
				|| ! is_numeric( $stored_quantity )
				|| $item['quantity'] !== (int) $stored_quantity
				|| false === $stored_status ) {
				return self::error(
					'complete99_inventory_stock_write',
					'WooCommerce did not confirm the expected product after the stock write.',
					500
				);
			}
		}

		$meta = array(
			self::META_SYNC_VERSION    => $item['version'],
			self::META_SYNC_DIGEST     => $plan['item_digest'],
			self::META_SYNC_BATCH      => $payload['batch_id'],
			self::META_SYNC_SOURCE     => $payload['source'],
			self::META_SYNC_MODE       => $payload['mode'],
			self::META_SYNC_REASON     => $item['reason'],
			self::META_SYNC_UPDATED_AT => gmdate( 'c' ),
		);
		if ( 'evaluation' === $payload['mode'] ) {
			$meta[ self::META_EVALUATION_QUANTITY ] = $item['quantity'];
		}
		$stored = self::store_meta( $product_id, $meta );
		if ( self::is_error( $stored ) ) {
			return $stored;
		}
		return self::verify_plan_readback( $plan, $payload['mode'] );
	}

	/**
	 * Verify common monotonic metadata and the mode-specific quantity.
	 */
	private static function verify_plan_readback( $plan, $mode ) {
		$product_id = $plan['product_id'];
		$item       = $plan['item'];
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $product_id, 'post_meta' );
		}
		$stored = self::stored_sync_state( $product_id );
		if ( self::is_error( $stored )
			|| $item['version'] !== ( isset( $stored['version'] ) ? $stored['version'] : 0 )
			|| ! isset( $stored['digest'] )
			|| ! hash_equals( $plan['item_digest'], (string) $stored['digest'] ) ) {
			return self::error(
				'complete99_inventory_meta_readback',
				'The inventory synchronization metadata failed readback.',
				500
			);
		}

		if ( 'evaluation' === $mode ) {
			$quantity = get_post_meta( $product_id, self::META_EVALUATION_QUANTITY, true );
			$product  = wc_get_product( $product_id );
			if ( ! is_numeric( $quantity )
				|| $item['quantity'] !== (int) $quantity
				|| 'draft' !== (string) get_post_status( $product_id )
				|| ! is_object( $product )
				|| 'hidden' !== (string) $product->get_catalog_visibility() ) {
				return self::error(
					'complete99_inventory_evaluation_readback',
					'The private evaluation inventory failed readback.',
					500
				);
			}
			return true;
		}

		$product  = wc_get_product( $product_id );
		$quantity = is_object( $product ) ? $product->get_stock_quantity() : null;
		if ( ! is_object( $product )
			|| 'publish' !== (string) get_post_status( $product_id )
			|| true !== (bool) $product->managing_stock()
			|| ! is_numeric( $quantity )
			|| $item['quantity'] !== (int) $quantity ) {
			return self::error(
				'complete99_inventory_stock_readback',
				'The WooCommerce inventory quantity failed readback.',
				500
			);
		}
		return true;
	}

	/**
	 * Store metadata and read each value back exactly.
	 */
	private static function store_meta( $product_id, $values ) {
		foreach ( $values as $key => $value ) {
			$result = update_post_meta( $product_id, $key, $value );
			if ( false === $result
				&& ( ! metadata_exists( 'post', $product_id, $key )
					|| ! self::values_equal( $value, get_post_meta( $product_id, $key, true ) ) ) ) {
				return self::error(
					'complete99_inventory_meta_write',
					'The inventory synchronization metadata could not be stored.',
					500
				);
			}
		}
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $product_id, 'post_meta' );
		}
		foreach ( $values as $key => $value ) {
			if ( ! metadata_exists( 'post', $product_id, $key )
				|| ! self::values_equal( $value, get_post_meta( $product_id, $key, true ) ) ) {
				return self::error(
					'complete99_inventory_meta_readback',
					'The inventory synchronization metadata failed readback.',
					500
				);
			}
		}
		return true;
	}

	/**
	 * Capture only the fields this bridge can mutate.
	 */
	private static function snapshot_product( $product_id, $product ) {
		$keys = array(
			self::META_SYNC_VERSION,
			self::META_SYNC_DIGEST,
			self::META_SYNC_BATCH,
			self::META_SYNC_SOURCE,
			self::META_SYNC_MODE,
			self::META_SYNC_REASON,
			self::META_SYNC_UPDATED_AT,
			self::META_EVALUATION_QUANTITY,
		);
		$meta = array();
		foreach ( $keys as $key ) {
			$meta[ $key ] = array(
				'exists' => metadata_exists( 'post', $product_id, $key ),
				'value'  => get_post_meta( $product_id, $key, true ),
			);
		}
		return array(
			'meta'         => $meta,
			'manage_stock' => (bool) $product->managing_stock(),
			'quantity'     => $product->get_stock_quantity(),
			'stock_status' => method_exists( $product, 'get_stock_status' ) ? (string) $product->get_stock_status() : '',
		);
	}

	/**
	 * Restore attempted writes in reverse order.
	 */
	private static function rollback_plans( $plans ) {
		$success = true;
		foreach ( $plans as $plan ) {
			$product_id = $plan['product_id'];
			foreach ( $plan['snapshot']['meta'] as $key => $state ) {
				if ( $state['exists'] ) {
					update_post_meta( $product_id, $key, $state['value'] );
				} else {
					delete_post_meta( $product_id, $key );
				}
			}
			if ( function_exists( 'wp_cache_delete' ) ) {
				wp_cache_delete( $product_id, 'post_meta' );
			}
			foreach ( $plan['snapshot']['meta'] as $key => $state ) {
				$exists = metadata_exists( 'post', $product_id, $key );
				if ( (bool) $state['exists'] !== (bool) $exists
					|| ( $state['exists'] && ! self::values_equal( $state['value'], get_post_meta( $product_id, $key, true ) ) ) ) {
					$success = false;
				}
			}

			if ( 'commerce' === ( isset( $plan['mode'] ) ? $plan['mode'] : '' ) ) {
				try {
					$product = wc_get_product( $product_id );
					if ( is_object( $product )
						&& true === (bool) $plan['snapshot']['manage_stock']
						&& is_numeric( $plan['snapshot']['quantity'] )
						&& function_exists( 'wc_update_product_stock' )
						&& function_exists( 'wc_update_product_stock_status' ) ) {
						$restored_quantity = wc_update_product_stock(
							$product_id,
							$plan['snapshot']['quantity'],
							'set'
						);
						$restored_status = wc_update_product_stock_status(
							$product_id,
							$plan['snapshot']['stock_status']
						);
						if ( false === $restored_quantity
							|| ! is_numeric( $restored_quantity )
							|| (int) $plan['snapshot']['quantity'] !== (int) $restored_quantity
							|| false === $restored_status ) {
							$success = false;
						}
						$fresh = wc_get_product( $product_id );
						if ( ! is_object( $fresh )
							|| (bool) $plan['snapshot']['manage_stock'] !== (bool) $fresh->managing_stock()
							|| ! self::values_equal( $plan['snapshot']['quantity'], $fresh->get_stock_quantity() ) ) {
							$success = false;
						}
					} else {
						$success = false;
					}
				} catch ( \Throwable $error ) {
					$success = false;
				}
			}
		}
		return $success;
	}

	/**
	 * Read the bounded receipt ledger.
	 */
	private static function read_receipts() {
		$receipts = get_option( self::OPTION_RECEIPTS, array() );
		if ( ! is_array( $receipts )
			|| ( ! empty( $receipts ) && self::is_list( $receipts ) )
			|| self::MAX_RECEIPTS < count( $receipts ) ) {
			return self::error(
				'complete99_inventory_receipts_corrupt',
				'The inventory receipt ledger is invalid.',
				500
			);
		}
		foreach ( $receipts as $key => $record ) {
			if ( ! is_string( $key )
				|| ! self::is_digest( $key )
				|| ! is_array( $record )
				|| ! self::has_exact_keys( $record, array( 'batch_id', 'digest', 'receipt' ) )
				|| ! isset( $record['batch_id'], $record['digest'] )
				|| ! self::is_identifier( $record['batch_id'], 128 )
				|| ! self::is_digest( $record['digest'] )
				|| ! hash_equals( $key, hash( 'sha256', $record['batch_id'] ) )
				|| ! is_array( $record['receipt'] )
				|| self::RECEIPT_SCHEMA !== ( isset( $record['receipt']['schema'] ) ? $record['receipt']['schema'] : '' )
				|| ! isset( $record['receipt']['batch_id'], $record['receipt']['digest'], $record['receipt']['items'] )
				|| ! hash_equals( $record['batch_id'], (string) $record['receipt']['batch_id'] )
				|| ! hash_equals( $record['digest'], (string) $record['receipt']['digest'] )
				|| ! is_array( $record['receipt']['items'] )
				|| ! self::is_list( $record['receipt']['items'] )
				|| self::MAX_ITEMS < count( $record['receipt']['items'] ) ) {
				return self::error(
					'complete99_inventory_receipts_corrupt',
					'The inventory receipt ledger failed integrity validation.',
					500
				);
			}
		}
		return $receipts;
	}

	private static function find_batch_receipt( $receipts, $batch_id ) {
		$key = hash( 'sha256', $batch_id );
		if ( ! isset( $receipts[ $key ] ) ) {
			return null;
		}
		$record = $receipts[ $key ];
		if ( ! is_array( $record )
			|| ! isset( $record['batch_id'] )
			|| ! is_string( $record['batch_id'] )
			|| ! hash_equals( $record['batch_id'], $batch_id ) ) {
			return self::error(
				'complete99_inventory_receipt_collision',
				'The inventory receipt key failed identity validation.',
				500
			);
		}
		return $record;
	}

	private static function store_receipt( $receipts, $receipt ) {
		$key = hash( 'sha256', $receipt['batch_id'] );
		$receipts[ $key ] = array(
			'batch_id' => $receipt['batch_id'],
			'digest'   => $receipt['digest'],
			'receipt'  => $receipt,
		);
		while ( self::MAX_RECEIPTS < count( $receipts ) ) {
			array_shift( $receipts );
		}
		update_option( self::OPTION_RECEIPTS, $receipts, false );
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( self::OPTION_RECEIPTS, 'options' );
		}
		$stored = get_option( self::OPTION_RECEIPTS, array() );
		if ( ! is_array( $stored )
			|| ! isset( $stored[ $key ] )
			|| self::value_digest( $receipts[ $key ] ) !== self::value_digest( $stored[ $key ] ) ) {
			return self::error(
				'complete99_inventory_receipt_readback',
				'The inventory audit receipt failed readback.',
				500
			);
		}
		return true;
	}

	private static function restore_receipts( $receipts ) {
		update_option( self::OPTION_RECEIPTS, $receipts, false );
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( self::OPTION_RECEIPTS, 'options' );
		}
		$stored = get_option( self::OPTION_RECEIPTS, array() );
		return is_array( $stored )
			&& self::value_digest( $receipts ) === self::value_digest( $stored );
	}

	/**
	 * Use a database advisory lock, with an atomic option fallback for tests or
	 * constrained hosts where advisory locks are unavailable.
	 */
	private static function acquire_lock() {
		global $wpdb;
		$name = self::lock_name();
		if ( is_object( $wpdb )
			&& method_exists( $wpdb, 'prepare' )
			&& method_exists( $wpdb, 'get_var' ) ) {
			try {
				$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, 3 ) );
				if ( '1' === (string) $locked ) {
					return array( 'driver' => 'database', 'name' => $name );
				}
				return self::error(
					'complete99_inventory_lock',
					'Another inventory batch is being processed.',
					503
				);
			} catch ( \Throwable $error ) {
				return self::error(
					'complete99_inventory_lock',
					'The inventory synchronization lock is unavailable.',
					503
				);
			}
		}

		if ( ! function_exists( 'add_option' ) ) {
			return self::error(
				'complete99_inventory_lock',
				'The inventory synchronization lock is unavailable.',
				503
			);
		}
		$token = hash( 'sha256', $name . '|' . microtime( true ) . '|' . wp_rand() );
		$state = array( 'token' => $token, 'expires' => time() + self::LOCK_SECONDS );
		if ( add_option( self::OPTION_LOCK, $state, '', 'no' ) ) {
			return array( 'driver' => 'option', 'token' => $token );
		}
		$current = get_option( self::OPTION_LOCK, array() );
		if ( is_array( $current )
			&& isset( $current['expires'] )
			&& (int) $current['expires'] < time() ) {
			delete_option( self::OPTION_LOCK );
			if ( add_option( self::OPTION_LOCK, $state, '', 'no' ) ) {
				return array( 'driver' => 'option', 'token' => $token );
			}
		}
		return self::error(
			'complete99_inventory_lock',
			'Another inventory batch is being processed.',
			503
		);
	}

	private static function release_lock( $lock ) {
		if ( ! is_array( $lock ) ) {
			return;
		}
		if ( 'database' === ( isset( $lock['driver'] ) ? $lock['driver'] : '' ) ) {
			global $wpdb;
			if ( is_object( $wpdb )
				&& method_exists( $wpdb, 'prepare' )
				&& method_exists( $wpdb, 'get_var' ) ) {
				try {
					$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock['name'] ) );
				} catch ( \Throwable $error ) {
					// The request is ending; there is no safe secondary write here.
				}
			}
			return;
		}
		if ( 'option' === ( isset( $lock['driver'] ) ? $lock['driver'] : '' ) ) {
			$current = get_option( self::OPTION_LOCK, array() );
			if ( is_array( $current )
				&& isset( $current['token'] )
				&& hash_equals( (string) $current['token'], (string) $lock['token'] ) ) {
				delete_option( self::OPTION_LOCK );
			}
		}
	}

	private static function lock_name() {
		$site_id = function_exists( 'get_current_blog_id' ) ? absint( get_current_blog_id() ) : 0;
		$site    = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '';
		return 'c99-inventory-' . substr( hash( 'sha256', $site_id . '|' . $site ), 0, 32 );
	}

	private static function item_digest( $source, $mode, $item ) {
		return self::value_digest(
			array(
				'schema' => self::PAYLOAD_SCHEMA,
				'source' => $source,
				'mode'   => $mode,
				'item'   => $item,
			)
		);
	}

	private static function value_digest( $value ) {
		$canonical = self::canonical_value( $value );
		$json      = function_exists( 'wp_json_encode' )
			? wp_json_encode( $canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			: json_encode( $canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return hash( 'sha256', (string) $json );
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

	private static function values_equal( $expected, $actual ) {
		if ( is_array( $expected ) || is_array( $actual ) ) {
			return is_array( $expected )
				&& is_array( $actual )
				&& self::value_digest( $expected ) === self::value_digest( $actual );
		}
		if ( null === $expected || null === $actual ) {
			return $expected === $actual;
		}
		if ( is_int( $expected ) || is_float( $expected ) || is_int( $actual ) || is_float( $actual ) ) {
			return is_numeric( $expected ) && is_numeric( $actual ) && (float) $expected === (float) $actual;
		}
		return (string) $expected === (string) $actual;
	}

	private static function parse_rfc3339( $value ) {
		if ( ! is_string( $value )
			|| 1 !== preg_match(
				'/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/',
				$value
			) ) {
			return false;
		}
		$normalized = preg_replace( '/Z\z/', '+00:00', $value );
		$format     = false !== strpos( $normalized, '.' ) ? '!Y-m-d\TH:i:s.uP' : '!Y-m-d\TH:i:sP';
		$date       = \DateTimeImmutable::createFromFormat( $format, $normalized );
		$errors     = \DateTimeImmutable::getLastErrors();
		if ( false === $date
			|| ( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) ) ) {
			return false;
		}
		return $date->getTimestamp();
	}

	private static function has_exact_keys( $value, $expected ) {
		$actual = array_keys( $value );
		sort( $actual, SORT_STRING );
		sort( $expected, SORT_STRING );
		return $actual === $expected;
	}

	private static function is_list( $value ) {
		if ( ! is_array( $value ) ) {
			return false;
		}
		return array() === $value || array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	private static function is_product_code( $value ) {
		return is_string( $value )
			&& strlen( $value ) <= 120
			&& 1 === preg_match( '/\Aproduct-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value );
	}

	private static function is_identifier( $value, $maximum ) {
		return is_string( $value )
			&& 0 < strlen( $value )
			&& strlen( $value ) <= $maximum
			&& 1 === preg_match( '/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/', $value );
	}

	private static function is_digest( $value ) {
		return is_string( $value ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/', $value );
	}

	public static function sanitize_product_code( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		return self::is_product_code( $value ) ? $value : '';
	}

	public static function sanitize_identifier( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		return self::is_identifier( $value, 128 ) ? $value : '';
	}

	public static function sanitize_yes_no( $value ) {
		return 'yes' === (string) $value ? 'yes' : 'no';
	}

	public static function sanitize_version( $value ) {
		$normalized = is_int( $value ) ? (string) $value : ( is_string( $value ) ? trim( $value ) : '' );
		if ( 1 !== preg_match( '/\A[1-9]\d*\z/', $normalized ) ) {
			return 0;
		}
		$number = (int) $normalized;
		return 1 <= $number && self::MAX_VERSION >= $number ? $number : 0;
	}

	public static function sanitize_quantity( $value ) {
		$normalized = is_int( $value ) ? (string) $value : ( is_string( $value ) ? trim( $value ) : '' );
		if ( 1 !== preg_match( '/\A(?:0|[1-9]\d*)\z/', $normalized ) ) {
			return 0;
		}
		$number = (int) $normalized;
		return self::MAX_QUANTITY >= $number ? $number : 0;
	}

	public static function sanitize_digest( $value ) {
		$value = strtolower( trim( (string) $value ) );
		return self::is_digest( $value ) ? $value : '';
	}

	public static function sanitize_mode( $value ) {
		$value = (string) $value;
		return in_array( $value, array( 'evaluation', 'commerce' ), true ) ? $value : '';
	}

	private static function is_error( $value ) {
		return function_exists( 'is_wp_error' )
			? is_wp_error( $value )
			: ( class_exists( 'WP_Error' ) && $value instanceof WP_Error );
	}

	private static function error( $code, $message, $status ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	private static function private_response( $value ) {
		$response = rest_ensure_response( $value );
		if ( is_object( $response ) && method_exists( $response, 'header' ) ) {
			$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
			$response->header( 'Pragma', 'no-cache' );
		}
		return $response;
	}
}
