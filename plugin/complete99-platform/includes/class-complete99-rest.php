<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_REST {
	const NAMESPACE       = 'complete99/v1';
	const MAX_BODY_BYTES  = 524288;
	const MAX_CLOCK_SKEW   = 300;
	const NONCE_TTL        = 600;
	const MAX_MODEL_ITEMS  = 500;
	const PUBLIC_MODEL_TTL = 86400;

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'health' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/sync/read-model',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'verify_sync_signature' ),
				'callback'            => array( __CLASS__, 'sync_read_model' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/public-catalog',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'public_catalog' ),
			)
		);
	}

	public static function health() {
		$model = get_option( 'complete99_public_read_model', array() );
		$freshness = self::model_freshness( $model );
		$database_version = (string) get_option( 'complete99_platform_version', '' );
		if ( Complete99_Platform::migration_failed() || COMPLETE99_PLATFORM_VERSION !== $database_version ) {
			return new WP_Error(
				'complete99_migration_incomplete',
				'Complete99 is not ready because its database migration is incomplete.',
				array( 'status' => 503 )
			);
		}
		$evaluation = Complete99_Platform::evaluation_catalog_status();
		if ( true !== ( $evaluation['ready'] ?? false ) ) {
			return new WP_Error(
				'complete99_evaluation_catalog_incomplete',
				'Complete99 is not ready because its private catalog migration is incomplete.',
				array( 'status' => 503 )
			);
		}
		$evaluation_receipt = is_array( $evaluation['receipt'] ?? null )
			? $evaluation['receipt']
			: array();
		$evaluation_materialized = is_array( $evaluation['materialized'] ?? null )
			? $evaluation['materialized']
			: array();
		return rest_ensure_response(
			array(
				'status'          => 'ok',
				'component'       => 'complete99-platform',
				'version'         => COMPLETE99_PLATFORM_VERSION,
				'database_version'=> $database_version,
				'deployment_id'   => (string) get_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID ),
				'content_schema'  => 'complete99-public-read-model/v1',
				'sync_configured' => '' !== (string) get_option( Complete99_Settings::OPTION_SECRET, '' ),
				'evaluation_catalog' => array(
					'ready'                  => true,
					'mode'                   => (string) ( $evaluation_receipt['mode'] ?? '' ),
					'seed_count'             => (int) ( $evaluation_receipt['seed_count'] ?? 0 ),
					'receipt_ingredient_count'=> (int) ( $evaluation_receipt['ingredient_count'] ?? 0 ),
					'receipt_plan_count'     => (int) ( $evaluation_receipt['product_plan_count'] ?? 0 ),
					'materialized_ingredient_count' => (int) ( $evaluation_materialized['ingredient_count'] ?? 0 ),
					'materialized_plan_count'=> (int) ( $evaluation_materialized['product_plan_count'] ?? 0 ),
					'woo_materialized'       => true === ( $evaluation_receipt['woo_materialized'] ?? null ),
				),
				'read_model'      => array(
					'version'    => isset( $model['version'] ) ? (string) $model['version'] : '',
					'updated_at' => isset( $model['updated_at'] ) ? (string) $model['updated_at'] : '',
					'fresh'      => $freshness['fresh'],
					'expires_at' => $freshness['expires_at'],
					'ttl_seconds' => self::PUBLIC_MODEL_TTL,
				),
			)
		);
	}

	public static function verify_sync_signature( WP_REST_Request $request ) {
		$secret = (string) get_option( Complete99_Settings::OPTION_SECRET, '' );
		if ( strlen( $secret ) < 32 ) {
			return new WP_Error( 'complete99_sync_unconfigured', 'Read-model sync is not configured.', array( 'status' => 503 ) );
		}

		$raw = (string) $request->get_body();
		if ( '' === $raw || strlen( $raw ) > self::MAX_BODY_BYTES ) {
			return new WP_Error( 'complete99_sync_size', 'The sync payload is empty or too large.', array( 'status' => 413 ) );
		}

		$timestamp = (string) $request->get_header( 'x-complete99-timestamp' );
		$nonce     = (string) $request->get_header( 'x-complete99-nonce' );
		$signature = strtolower( (string) $request->get_header( 'x-complete99-signature' ) );
		if ( ! ctype_digit( $timestamp ) || abs( time() - (int) $timestamp ) > self::MAX_CLOCK_SKEW ) {
			return new WP_Error( 'complete99_sync_time', 'The sync timestamp is outside the accepted window.', array( 'status' => 401 ) );
		}
		if ( ! preg_match( '/^[A-Za-z0-9_-]{16,128}$/', $nonce ) || ! preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
			return new WP_Error( 'complete99_sync_headers', 'The sync authentication headers are invalid.', array( 'status' => 401 ) );
		}

		$nonce_key = 'c99_sync_' . substr( hash_hmac( 'sha256', $nonce, wp_salt( 'nonce' ) ), 0, 40 );
		$canonical = $timestamp . "\n" . $nonce . "\n" . hash( 'sha256', $raw );
		$expected  = hash_hmac( 'sha256', $canonical, $secret );
		if ( ! hash_equals( $expected, $signature ) ) {
			return new WP_Error( 'complete99_sync_signature', 'The sync signature is invalid.', array( 'status' => 401 ) );
		}

		$reserved = self::reserve_sync_nonce( $nonce_key );
		if ( is_wp_error( $reserved ) ) {
			return $reserved;
		}
		return true;
	}

	private static function reserve_sync_nonce( $nonce_key ) {
		if ( ! self::acquire_sync_lock( 'nonce' ) ) {
			return new WP_Error( 'complete99_sync_lock', 'The sync receiver is busy. Retry with a new nonce.', array( 'status' => 503 ) );
		}
		try {
			if ( false !== get_transient( $nonce_key ) ) {
				return new WP_Error( 'complete99_sync_replay', 'This sync request has already been used.', array( 'status' => 409 ) );
			}
			set_transient( $nonce_key, 1, self::NONCE_TTL );
			if ( 1 !== (int) get_transient( $nonce_key ) ) {
				return new WP_Error( 'complete99_sync_nonce_storage', 'The sync nonce reservation could not be verified.', array( 'status' => 503 ) );
			}
			return true;
		} finally {
			self::release_sync_lock( 'nonce' );
		}
	}

	private static function sync_lock_name( $scope ) {
		$site_id = function_exists( 'get_current_blog_id' ) ? absint( get_current_blog_id() ) : 0;
		$site    = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '';
		return 'c99-' . sanitize_key( (string) $scope ) . '-' . substr( hash( 'sha256', $site_id . '|' . $site ), 0, 32 );
	}

	private static function acquire_sync_lock( $scope ) {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return false;
		}
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', self::sync_lock_name( $scope ), 3 ) );
		return '1' === (string) $locked;
	}

	private static function release_sync_lock( $scope ) {
		global $wpdb;
		if ( is_object( $wpdb ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'get_var' ) ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::sync_lock_name( $scope ) ) );
		}
	}

	public static function sync_read_model( WP_REST_Request $request ) {
		$payload = json_decode( (string) $request->get_body(), true );
		if ( ! is_array( $payload ) || 'complete99-public-read-model/v1' !== ( isset( $payload['schema'] ) ? $payload['schema'] : '' ) ) {
			return new WP_Error( 'complete99_sync_schema', 'Unsupported public read-model schema.', array( 'status' => 400 ) );
		}
		$unknown_payload_fields = array_values(
			array_diff(
				array_keys( $payload ),
				array( 'schema', 'version', 'generated_at', 'branches', 'menu_sections', 'menu_items', 'campaigns' )
			)
		);
		if ( ! empty( $unknown_payload_fields ) ) {
			return new WP_Error(
				'complete99_sync_unknown_field',
				'The public read model contains an unknown top-level field.',
				array( 'status' => 400 )
			);
		}

		$generated = isset( $payload['generated_at'] ) && is_string( $payload['generated_at'] )
			? trim( $payload['generated_at'] )
			: '';
		$generated_at = self::parse_rfc3339_timestamp( $generated );
		if ( false === $generated_at ) {
			return new WP_Error( 'complete99_sync_generated_at', 'The public read-model generation timestamp is invalid.', array( 'status' => 400 ) );
		}
		$campaigns = isset( $payload['campaigns'] ) ? $payload['campaigns'] : array();
		if ( ! is_array( $campaigns ) || ! empty( $campaigns ) ) {
			return new WP_Error(
				'complete99_sync_private_field',
				'Campaigns are private and cannot be accepted by the public read model.',
				array( 'status' => 422 )
			);
		}

		$branch_records = isset( $payload['branches'] ) ? $payload['branches'] : array();
		$branch_count   = is_array( $branch_records )
			? min( count( $branch_records ), self::MAX_MODEL_ITEMS )
			: 0;
		$sections = self::clean_records(
			isset( $payload['menu_sections'] ) ? $payload['menu_sections'] : array(),
			array( 'id', 'name_he', 'name_en', 'sort', 'published' )
		);
		if ( is_wp_error( $sections ) ) {
			return $sections;
		}
		$items = self::clean_records(
			isset( $payload['menu_items'] ) ? $payload['menu_items'] : array(),
			array(
				'id',
				'slug',
				'section_id',
				'name_he',
				'name_en',
				'category_he',
				'category_en',
				'description_he',
				'description_en',
				'tag_he',
				'tag_en',
				'image_asset',
				'media_provenance',
				'media_rights_state',
				'vegetarian',
				'verification_state',
				'published',
				'sort',
				'updated_at',
			)
		);
		if ( is_wp_error( $items ) ) {
			return $items;
		}

		if ( ! self::acquire_sync_lock( 'read-model' ) ) {
			return new WP_Error( 'complete99_sync_lock', 'The public read model is being updated. Retry with a new nonce.', array( 'status' => 503 ) );
		}
		try {
		$stored_model = self::read_persisted_read_model();
		if ( is_wp_error( $stored_model ) ) {
			return $stored_model;
		}
		if ( null === $stored_model ) {
			$stored_model = array();
		}
		if ( ! is_array( $stored_model ) ) {
			return new WP_Error( 'complete99_sync_stored_model', 'The stored public read model is invalid.', array( 'status' => 500 ) );
		}
		$identity_check = self::validate_item_identities( $items, $stored_model );
		if ( is_wp_error( $identity_check ) ) {
			return $identity_check;
		}

		$clean = array(
			'schema'     => 'complete99-public-read-model/v1',
			'version'    => sanitize_text_field( isset( $payload['version'] ) ? (string) $payload['version'] : '' ),
			'generated'  => sanitize_text_field( $generated ),
			'updated_at' => gmdate( 'c' ),
			'sections'   => $sections,
			'items'      => $items,
			'campaigns'  => array(),
		);
		$stored_generated_at = ! empty( $stored_model['generated'] )
			? self::parse_rfc3339_timestamp( (string) $stored_model['generated'] )
			: false;
		if ( false !== $stored_generated_at && $generated_at === $stored_generated_at ) {
			$equivalent = (string) ( $stored_model['generated'] ?? '' ) === (string) $clean['generated']
				&& (string) ( $stored_model['version'] ?? '' ) === (string) $clean['version']
				&& hash_equals(
					hash(
						'sha256',
						wp_json_encode(
							array(
								'sections'  => $clean['sections'],
								'items'     => $clean['items'],
								'campaigns' => $clean['campaigns'],
							),
							JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
						)
					),
					hash(
						'sha256',
						wp_json_encode(
							array(
								'sections'  => $stored_model['sections'] ?? array(),
								'items'     => $stored_model['items'] ?? array(),
								'campaigns' => $stored_model['campaigns'] ?? array(),
							),
							JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
						)
					)
				);
			if ( $equivalent ) {
				$cache = self::purge_public_read_model_caches();
				if ( is_wp_error( $cache ) ) {
					return $cache;
				}
				$freshness = self::model_freshness( $stored_model );
				return rest_ensure_response(
					array(
						'stored'        => true,
						'write_changed' => false,
						'version'       => (string) $stored_model['version'],
						'digest'        => (string) ( $stored_model['digest'] ?? '' ),
						'item_count'    => count( $clean['sections'] ) + count( $clean['items'] ),
						'expires_at'    => $freshness['expires_at'],
						'cache'         => $cache,
					)
				);
			}
		}

		if ( $generated_at < time() - self::MAX_CLOCK_SKEW || $generated_at > time() + self::MAX_CLOCK_SKEW ) {
			return new WP_Error( 'complete99_sync_stale_model', 'The public read model was not generated inside the accepted freshness window.', array( 'status' => 409 ) );
		}
		if ( false !== $stored_generated_at && $generated_at < $stored_generated_at ) {
			return new WP_Error( 'complete99_sync_non_monotonic', 'An older public read model cannot replace the current model.', array( 'status' => 409 ) );
		}
		if ( false !== $stored_generated_at && $generated_at === $stored_generated_at ) {
			return new WP_Error( 'complete99_sync_non_monotonic', 'A different public read model must use a later generation timestamp.', array( 'status' => 409 ) );
		}

		$total = count( $clean['sections'] ) + count( $clean['items'] );
		if ( $branch_count + $total > self::MAX_MODEL_ITEMS ) {
			return new WP_Error( 'complete99_sync_items', 'The public read model contains too many records.', array( 'status' => 413 ) );
		}

		$clean['digest'] = hash( 'sha256', wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		$write_changed = update_option( 'complete99_public_read_model', $clean, false );
		$persisted     = self::read_persisted_read_model();
		if ( is_wp_error( $persisted ) ) {
			return $persisted;
		}
		if ( ! is_array( $persisted ) || ! hash_equals( hash( 'sha256', serialize( $clean ) ), hash( 'sha256', serialize( $persisted ) ) ) ) {
			return new WP_Error( 'complete99_sync_readback', 'The public read model could not be verified after storage.', array( 'status' => 500 ) );
		}

		$cache = self::purge_public_read_model_caches();
		if ( is_wp_error( $cache ) ) {
			return $cache;
		}
		$freshness = self::model_freshness( $persisted );

		return rest_ensure_response(
			array(
				'stored'        => true,
				'write_changed' => (bool) $write_changed,
				'version'       => $clean['version'],
				'digest'        => $clean['digest'],
				'item_count'    => $total,
				'expires_at'    => $freshness['expires_at'],
				'cache'         => $cache,
			)
		);
		} finally {
			self::release_sync_lock( 'read-model' );
		}
	}

	private static function clean_records( $records, $allowed_keys ) {
		if ( ! is_array( $records ) ) {
			return array();
		}
		$clean = array();
		foreach ( array_slice( $records, 0, self::MAX_MODEL_ITEMS ) as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}
			$unknown_record_fields = array_values( array_diff( array_keys( $record ), $allowed_keys ) );
			if ( ! empty( $unknown_record_fields ) ) {
				return new WP_Error(
					'complete99_sync_unknown_field',
					'A public read-model record contains an unknown field.',
					array( 'status' => 400 )
				);
			}
			$item = array();
			foreach ( $allowed_keys as $key ) {
				if ( ! array_key_exists( $key, $record ) ) {
					continue;
				}
				if ( in_array( $key, array( 'published', 'vegetarian' ), true ) ) {
					$value = $record[ $key ];
					if ( is_bool( $value ) ) {
						$item[ $key ] = $value;
						continue;
					}
					if ( 'true' === $value || 'false' === $value ) {
						$item[ $key ] = 'true' === $value;
						continue;
					}
					return new WP_Error(
						'complete99_sync_boolean',
						'Public read-model boolean fields must use JSON booleans or the strings "true" or "false".',
						array( 'status' => 400 )
					);
				} elseif ( 'sort' === $key ) {
					$item[ $key ] = is_numeric( $record[ $key ] ) ? (float) $record[ $key ] : null;
				} elseif ( 'slug' === $key ) {
					$item[ $key ] = sanitize_title( (string) $record[ $key ] );
				} elseif ( in_array( $key, array( 'media_provenance', 'media_rights_state' ), true ) ) {
					$item[ $key ] = sanitize_key( (string) $record[ $key ] );
				} elseif ( 'cta_url' === $key ) {
					$item[ $key ] = esc_url_raw( (string) $record[ $key ], array( 'https' ) );
				} elseif ( 'image_asset' === $key ) {
					$asset = sanitize_file_name( (string) $record[ $key ] );
					$item[ $key ] = ( 0 === strpos( $asset, 'c99-' ) && preg_match( '/\.(?:jpe?g|png|webp|avif)$/i', $asset ) ) ? $asset : '';
				} else {
					$item[ $key ] = sanitize_text_field( (string) $record[ $key ] );
				}
			}
			if ( ! empty( $item['id'] ) ) {
				$clean[] = $item;
			}
		}
		return $clean;
	}

	private static function parse_rfc3339_timestamp( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/', $value ) ) {
			return false;
		}
		$normalised = preg_replace( '/Z$/', '+00:00', $value );
		$format     = false !== strpos( $normalised, '.' ) ? '!Y-m-d\TH:i:s.uP' : '!Y-m-d\TH:i:sP';
		$date       = \DateTimeImmutable::createFromFormat( $format, $normalised );
		$errors     = \DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) ) ) {
			return false;
		}
		return $date->getTimestamp();
	}

	private static function model_freshness( $model ) {
		$updated_at = is_array( $model ) && isset( $model['updated_at'] ) ? (string) $model['updated_at'] : '';
		$timestamp  = self::parse_rfc3339_timestamp( $updated_at );
		if ( false === $timestamp ) {
			return array(
				'fresh'      => false,
				'expires_at' => '',
			);
		}
		$expires = $timestamp + self::PUBLIC_MODEL_TTL;
		return array(
			'fresh'      => $timestamp <= time() + self::MAX_CLOCK_SKEW && time() <= $expires,
			'expires_at' => gmdate( 'c', $expires ),
		);
	}

	public static function is_public_model_fresh( $model = null ) {
		if ( null === $model ) {
			$model = get_option( 'complete99_public_read_model', array() );
		}
		return self::model_freshness( $model )['fresh'];
	}

	private static function read_persisted_read_model() {
		global $wpdb;

		if ( ! is_object( $wpdb ) || ! isset( $wpdb->options ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return new WP_Error( 'complete99_sync_readback_driver', 'The public read-model storage is unavailable.', array( 'status' => 500 ) );
		}

		$can_suppress = method_exists( $wpdb, 'suppress_errors' );
		$previous     = null;
		try {
			if ( $can_suppress ) {
				$previous = $wpdb->suppress_errors( true );
			}
			$wpdb->last_error = '';
			$raw = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
					'complete99_public_read_model'
				)
			);
			$error = isset( $wpdb->last_error ) ? (string) $wpdb->last_error : '';
		} catch ( \Throwable $error ) {
			return new WP_Error( 'complete99_sync_readback_driver', 'The public read-model storage could not be read.', array( 'status' => 500 ) );
		} finally {
			if ( $can_suppress ) {
				$wpdb->suppress_errors( $previous );
			}
		}
		if ( '' !== $error ) {
			return new WP_Error( 'complete99_sync_readback_driver', 'The public read-model storage could not be read.', array( 'status' => 500 ) );
		}
		return null === $raw ? null : maybe_unserialize( $raw );
	}

	private static function validate_item_identities( $items, $stored_model ) {
		$candidate_ids   = array();
		$candidate_slugs = array();
		foreach ( $items as $item ) {
			$id   = isset( $item['id'] ) ? trim( (string) $item['id'] ) : '';
			$slug = isset( $item['slug'] ) ? sanitize_title( (string) $item['slug'] ) : '';
			if ( '' === $id ) {
				continue;
			}
			$id_key = 'id:' . $id;
			if ( isset( $candidate_ids[ $id_key ] ) ) {
				return new WP_Error( 'complete99_sync_duplicate_id', 'The public read model contains duplicate menu-item identities.', array( 'status' => 409 ) );
			}
			$candidate_ids[ $id_key ] = $slug;
			if ( '' === $slug ) {
				continue;
			}
			$slug_key = 'slug:' . $slug;
			if ( isset( $candidate_slugs[ $slug_key ] ) && ! hash_equals( $candidate_slugs[ $slug_key ], $id ) ) {
				return new WP_Error( 'complete99_sync_slug_collision', 'The public read model contains colliding menu-item slugs.', array( 'status' => 409 ) );
			}
			$candidate_slugs[ $slug_key ] = $id;
		}

		$stored_ids   = array();
		$stored_slugs = array();
		$stored_items = isset( $stored_model['items'] ) && is_array( $stored_model['items'] ) ? $stored_model['items'] : array();
		foreach ( $stored_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id   = isset( $item['id'] ) ? trim( (string) $item['id'] ) : '';
			$slug = isset( $item['slug'] ) ? sanitize_title( (string) $item['slug'] ) : '';
			if ( '' === $id ) {
				continue;
			}
			$id_key = 'id:' . $id;
			if ( isset( $stored_ids[ $id_key ] ) ) {
				return new WP_Error( 'complete99_sync_stored_identity', 'The stored public read model contains duplicate menu-item identities.', array( 'status' => 409 ) );
			}
			$stored_ids[ $id_key ] = $slug;
			if ( '' === $slug ) {
				continue;
			}
			$slug_key = 'slug:' . $slug;
			if ( isset( $stored_slugs[ $slug_key ] ) && ! hash_equals( $stored_slugs[ $slug_key ], $id ) ) {
				return new WP_Error( 'complete99_sync_stored_identity', 'The stored public read model contains colliding menu-item slugs.', array( 'status' => 409 ) );
			}
			$stored_slugs[ $slug_key ] = $id;
		}

		foreach ( $candidate_ids as $id_key => $slug ) {
			$id = substr( $id_key, 3 );
			if ( isset( $stored_ids[ $id_key ] ) && '' !== (string) $stored_ids[ $id_key ] && ! hash_equals( (string) $stored_ids[ $id_key ], (string) $slug ) ) {
				return new WP_Error( 'complete99_sync_slug_changed', 'An existing menu-item identity cannot change its canonical slug in one sync.', array( 'status' => 409 ) );
			}
			if ( '' !== $slug ) {
				$slug_key = 'slug:' . $slug;
				if ( isset( $stored_slugs[ $slug_key ] ) && ! hash_equals( (string) $stored_slugs[ $slug_key ], (string) $id ) ) {
					return new WP_Error( 'complete99_sync_slug_collision', 'A canonical menu-item slug is already owned by another stored identity.', array( 'status' => 409 ) );
				}
			}
		}
		return true;
	}

	private static function purge_public_read_model_caches() {
		$report = array(
			'object_cache' => array(
				'method'  => 'wp_cache_flush',
				'flushed' => false,
			),
			'page_cache'   => array(
				'upress'    => array(
					'detected'          => false,
					'request_completed' => false,
				),
				'litespeed' => array(
					'listener_detected' => false,
					'signal_sent'       => false,
				),
			),
		);

		$report['page_cache']['upress']['detected'] = class_exists( '\\Upress\\EzCache\\Cache' );
		if ( $report['page_cache']['upress']['detected'] ) {
			try {
				if ( ! method_exists( '\\Upress\\EzCache\\Cache', 'instance' ) ) {
					throw new \RuntimeException( 'instance' );
				}
				$cache = \Upress\EzCache\Cache::instance();
				if ( ! is_object( $cache ) || ! method_exists( $cache, 'clear_cache' ) ) {
					throw new \RuntimeException( 'clear-cache' );
				}
				$result = $cache->clear_cache();
				if ( false === $result ) {
					throw new \RuntimeException( 'clear-failed' );
				}
				$report['page_cache']['upress']['request_completed'] = true;
			} catch ( \Throwable $error ) {
				return new WP_Error(
					'complete99_sync_upress_cache',
					'The public read model was stored, but the UPress page-cache purge request failed.',
					array(
						'status' => 503,
						'stored' => true,
						'cache'  => $report,
					)
				);
			}
		}

		$listener = has_action( 'litespeed_purge_all' );
		$report['page_cache']['litespeed']['listener_detected'] = false !== $listener;
		try {
			do_action( 'litespeed_purge_all' );
			$report['page_cache']['litespeed']['signal_sent'] = true;
		} catch ( \Throwable $error ) {
			return new WP_Error(
				'complete99_sync_litespeed_cache',
				'The public read model was stored, but the LiteSpeed page-cache purge signal failed.',
				array(
					'status' => 503,
					'stored' => true,
					'cache'  => $report,
				)
			);
		}

		try {
			$report['object_cache']['flushed'] = true === wp_cache_flush();
		} catch ( \Throwable $error ) {
			$report['object_cache']['flushed'] = false;
		}
		if ( ! $report['object_cache']['flushed'] ) {
			return new WP_Error(
				'complete99_sync_object_cache',
				'The public read model was stored, but the WordPress object cache could not be flushed.',
				array(
					'status' => 503,
					'stored' => true,
					'cache'  => $report,
				)
			);
		}
		return $report;
	}

	public static function is_public_item( $record, $model = null ) {
		if ( null === $model ) {
			$model = get_option( 'complete99_public_read_model', array() );
		}
		if ( ! self::is_public_model_fresh( $model ) || ! is_array( $record ) || true !== ( isset( $record['published'] ) ? $record['published'] : null ) ) {
			return false;
		}
		$required = array( 'id', 'slug', 'name_he', 'name_en', 'description_he', 'description_en' );
		foreach ( $required as $key ) {
			if ( '' === trim( isset( $record[ $key ] ) ? (string) $record[ $key ] : '' ) ) {
				return false;
			}
		}
		$updated_at = self::parse_rfc3339_timestamp( (string) ( $record['updated_at'] ?? '' ) );
		if ( false === $updated_at || $updated_at > time() + self::MAX_CLOCK_SKEW || $updated_at < time() - self::PUBLIC_MODEL_TTL ) {
			return false;
		}
		$image_asset = sanitize_file_name( (string) ( $record['image_asset'] ?? '' ) );
		if ( '' !== $image_asset ) {
			$provenance = sanitize_key( (string) ( $record['media_provenance'] ?? '' ) );
			$rights     = sanitize_key( (string) ( $record['media_rights_state'] ?? '' ) );
			if ( ! in_array( $provenance, array( 'complete99_archive', 'business_owned', 'licensed' ), true )
				|| 'approved_public_use' !== $rights ) {
				return false;
			}
		}
		$verification = sanitize_key( isset( $record['verification_state'] ) ? (string) $record['verification_state'] : '' );
		return in_array( $verification, array( 'verified', 'launch_ready' ), true );
	}

	public static function public_indexable_items( $model = null ) {
		if ( null === $model ) {
			$model = get_option( 'complete99_public_read_model', array() );
		}
		if ( ! is_array( $model ) || ! self::is_public_model_fresh( $model ) ) {
			return array();
		}
		$records = isset( $model['items'] ) && is_array( $model['items'] ) ? $model['items'] : array();
		return array_values(
			array_filter(
				$records,
				static function ( $record ) use ( $model ) {
					return self::is_public_item( $record, $model );
				}
			)
		);
	}

	private static function public_record_projection( $record, $allowed_keys ) {
		if ( ! is_array( $record ) ) {
			return array();
		}
		$public = array();
		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $record ) ) {
				$public[ $key ] = $record[ $key ];
			}
		}
		return $public;
	}

	private static function public_published_records( $model, $key, $allowed_keys ) {
		$records = isset( $model[ $key ] ) && is_array( $model[ $key ] ) ? $model[ $key ] : array();
		$public  = array();
		foreach ( $records as $record ) {
			if ( ! is_array( $record ) || true !== ( isset( $record['published'] ) ? $record['published'] : null ) ) {
				continue;
			}
			$public[] = self::public_record_projection( $record, $allowed_keys );
		}
		return $public;
	}

	public static function public_catalog() {
		$model = get_option( 'complete99_public_read_model', array() );
		if ( ! is_array( $model ) ) {
			$model = array();
		}
		$freshness = self::model_freshness( $model );
		if ( ! $freshness['fresh'] ) {
			return new WP_Error(
				'complete99_public_model_stale',
				'The public catalog is unavailable because its read model is missing or stale.',
				array(
					'status'     => 503,
					'expires_at' => $freshness['expires_at'],
				)
			);
		}
		$items = array_map(
			static function ( $record ) {
				return self::public_record_projection(
					$record,
					array(
						'slug',
						'name_he',
						'name_en',
						'category_he',
						'category_en',
						'description_he',
						'description_en',
						'tag_he',
						'tag_en',
						'image_asset',
						'vegetarian',
						'updated_at',
					)
				);
			},
			self::public_indexable_items( $model )
		);
		return rest_ensure_response(
			array(
				'schema'     => isset( $model['schema'] ) ? (string) $model['schema'] : 'complete99-public-read-model/v1',
				'version'    => isset( $model['version'] ) ? (string) $model['version'] : '',
				'updated_at' => isset( $model['updated_at'] ) ? (string) $model['updated_at'] : '',
				'sections'   => self::public_published_records(
					$model,
					'sections',
					array( 'name_he', 'name_en' )
				),
				'items'      => array_values( $items ),
				'freshness'  => $freshness,
			)
		);
	}
}
