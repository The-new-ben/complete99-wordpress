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
	const BUNDLED_CATALOG_VERSION = 'wordpress-bundle-2026-08-01-v1';
	const BUNDLED_CATALOG_UPDATED_AT = '2026-08-01T00:20:00Z';

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
		$model_integrity_valid = self::read_model_integrity_is_valid( $model );
		$model_digest = $model_integrity_valid ? self::stored_read_model_digest( $model ) : '';
		$science_loaded = class_exists( 'Complete99_Culinary_Science', false );
		$science        = $science_loaded ? Complete99_Culinary_Science::status() : array();
		$commerce_graph_loaded = class_exists( 'Complete99_Culinary_Commerce', false );
		$commerce_graph = $commerce_graph_loaded ? Complete99_Culinary_Commerce::status() : array();
		$commerce_registry_valid = $commerce_graph_loaded && ! empty( $commerce_graph['registry_valid'] );
		$database_version = (string) get_option( 'complete99_platform_version', '' );
		if ( Complete99_Platform::migration_failed()
			|| COMPLETE99_PLATFORM_VERSION !== $database_version ) {
			return new WP_Error(
				'complete99_migration_incomplete',
				'Complete99 is not ready because its database migration is incomplete.',
				array( 'status' => 503 )
			);
		}
		if ( ( $science_loaded && empty( $science['ready'] ) )
			|| ( $commerce_graph_loaded && ! $commerce_registry_valid ) ) {
			return new WP_Error(
				'complete99_culinary_graph_unavailable',
				'Complete99 culinary data is temporarily unavailable.',
				array( 'status' => 503 )
			);
		}
		return rest_ensure_response(
			array(
				'status'          => 'ok',
				'component'       => 'complete99-platform',
				'version'         => COMPLETE99_PLATFORM_VERSION,
				'database_version'=> $database_version,
				'deployment_id'   => (string) get_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID ),
				'content_schema'  => 'complete99-public-read-model/v1',
				'sync_configured' => '' !== (string) get_option( Complete99_Settings::OPTION_SECRET, '' ),
				'read_model'      => array(
					'version'    => $model_integrity_valid && isset( $model['version'] ) ? (string) $model['version'] : '',
					'updated_at' => $model_integrity_valid && isset( $model['generated_at'] ) ? (string) $model['generated_at'] : '',
					'digest'     => $model_digest,
					'fresh'      => $freshness['fresh'],
					'expires_at' => $freshness['expires_at'],
					'ttl_seconds' => self::PUBLIC_MODEL_TTL,
				),
				'culinary_science_ready' => $science_loaded && ! empty( $science['ready'] ),
				'culinary_commerce_registry_valid' => $commerce_registry_valid,
				'culinary_commerce_ready' => $commerce_graph_loaded && ! empty( $commerce_graph['commerce_ready'] ),
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

	/**
	 * Verify a route-bound, consumer-scoped integration signature.
	 *
	 * A derived key prevents one adapter credential from authorizing another
	 * consumer even though WordPress retains one protected root secret.
	 */
	public static function verify_scoped_integration_signature( WP_REST_Request $request, $scope, $consumer_id, $key_id ) {
		$secret = (string) get_option( Complete99_Settings::OPTION_SECRET, '' );
		if ( strlen( $secret ) < 32 ) {
			return new WP_Error( 'complete99_integration_unconfigured', 'Integration authentication is not configured.', array( 'status' => 503 ) );
		}
		foreach ( array( 'scope' => $scope, 'consumer_id' => $consumer_id, 'key_id' => $key_id ) as $label => $value ) {
			if ( ! is_string( $value ) || 1 !== preg_match( '/\A[a-z][a-z0-9._-]{2,99}\z/', $value ) ) {
				return new WP_Error( 'complete99_integration_identity', 'The integration identity is invalid.', array( 'status' => 401, 'field' => $label ) );
			}
		}
		$raw = (string) $request->get_body();
		if ( '' === $raw || strlen( $raw ) > self::MAX_BODY_BYTES ) {
			return new WP_Error( 'complete99_integration_size', 'The integration payload is empty or too large.', array( 'status' => 413 ) );
		}
		$timestamp = (string) $request->get_header( 'x-complete99-timestamp' );
		$nonce = (string) $request->get_header( 'x-complete99-nonce' );
		$signature = strtolower( (string) $request->get_header( 'x-complete99-signature' ) );
		$header_key_id = (string) $request->get_header( 'x-complete99-key-id' );
		if ( ! hash_equals( $key_id, $header_key_id ) ) {
			return new WP_Error( 'complete99_integration_key', 'The integration key identity is invalid.', array( 'status' => 401 ) );
		}
		if ( ! ctype_digit( $timestamp ) || abs( time() - (int) $timestamp ) > self::MAX_CLOCK_SKEW ) {
			return new WP_Error( 'complete99_integration_time', 'The integration timestamp is outside the accepted window.', array( 'status' => 401 ) );
		}
		if ( ! preg_match( '/^[A-Za-z0-9_-]{16,128}$/', $nonce ) || ! preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
			return new WP_Error( 'complete99_integration_headers', 'The integration authentication headers are invalid.', array( 'status' => 401 ) );
		}
		$method = strtoupper( (string) $request->get_method() );
		$route = '/' . ltrim( (string) $request->get_route(), '/' );
		$canonical = implode( "\n", array( 'complete99-integration-signature/v1', $method, $route, $scope, $consumer_id, $key_id, $timestamp, $nonce, hash( 'sha256', $raw ) ) );
		$derived_key = hash_hmac( 'sha256', "complete99-integration-key/v1\n" . $scope . "\n" . $consumer_id . "\n" . $key_id, $secret, true );
		$expected = hash_hmac( 'sha256', $canonical, $derived_key );
		if ( ! hash_equals( $expected, $signature ) ) {
			return new WP_Error( 'complete99_integration_signature', 'The integration signature is invalid.', array( 'status' => 401 ) );
		}
		$nonce_key = 'c99_integration_' . substr( hash_hmac( 'sha256', $consumer_id . '|' . $key_id . '|' . $nonce, wp_salt( 'nonce' ) ), 0, 40 );
		return self::reserve_sync_nonce( $nonce_key );
	}

	private static function reserve_sync_nonce( $nonce_key ) {
		$lock_scope = 'nonce-' . substr( hash( 'sha256', (string) $nonce_key ), 0, 24 );
		if ( ! self::acquire_sync_lock( $lock_scope ) ) {
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
			self::release_sync_lock( $lock_scope );
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
		$required_payload_fields = array( 'schema', 'version', 'generated_at', 'branches', 'menu_sections', 'menu_items', 'campaigns' );
		if ( array() !== array_diff( $required_payload_fields, array_keys( $payload ) ) ) {
			return new WP_Error(
				'complete99_sync_normalization',
				'The public read model must contain the complete normalized transport envelope.',
				array( 'status' => 400 )
			);
		}
		if ( ! is_string( $payload['version'] )
			|| '' === trim( $payload['version'] )
			|| sanitize_text_field( $payload['version'] ) !== $payload['version'] ) {
			return new WP_Error( 'complete99_sync_normalization', 'The public read-model version is not normalized.', array( 'status' => 400 ) );
		}

		$generated = isset( $payload['generated_at'] ) && is_string( $payload['generated_at'] )
			? trim( $payload['generated_at'] )
			: '';
		$generated_at = self::parse_canonical_generation_millis( $generated );
		if ( false === $generated_at || $generated !== $payload['generated_at'] ) {
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
		if ( ! is_array( $branch_records ) || ! empty( $branch_records ) ) {
			return new WP_Error(
				'complete99_sync_private_field',
				'Branches are private and cannot be accepted by the public read model.',
				array( 'status' => 422 )
			);
		}
		$sections = self::clean_records(
			isset( $payload['menu_sections'] ) ? $payload['menu_sections'] : array(),
			self::transport_section_keys()
		);
		if ( is_wp_error( $sections ) ) {
			return $sections;
		}
		$items = self::clean_records(
			isset( $payload['menu_items'] ) ? $payload['menu_items'] : array(),
			self::transport_item_keys()
		);
		if ( is_wp_error( $items ) ) {
			return $items;
		}
		if ( ! is_array( $payload['menu_sections'] )
			|| ! is_array( $payload['menu_items'] )
			|| ! self::records_have_exact_keys( $payload['menu_sections'], self::transport_section_keys() )
			|| ! self::records_have_exact_keys( $payload['menu_items'], self::transport_item_keys() )
			|| ! hash_equals( self::canonical_read_model_value_digest( $sections ), self::canonical_read_model_value_digest( $payload['menu_sections'] ) )
			|| ! hash_equals( self::canonical_read_model_value_digest( $items ), self::canonical_read_model_value_digest( $payload['menu_items'] ) ) ) {
			return new WP_Error(
				'complete99_sync_normalization',
				'The public read-model records must already be normalized.',
				array( 'status' => 400 )
			);
		}
		foreach ( $items as $item ) {
			if ( ! isset( $item['updated_at'] ) || ! hash_equals( $generated, (string) $item['updated_at'] ) ) {
				return new WP_Error(
					'complete99_sync_item_timestamp',
					'Every public menu item must use the read-model generation timestamp.',
					array( 'status' => 400 )
				);
			}
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
		$stored_is_legacy = false;
		if ( ! empty( $stored_model ) && ! self::read_model_integrity_is_valid( $stored_model ) ) {
			if ( self::is_recognized_legacy_read_model( $stored_model ) ) {
				$stored_is_legacy = true;
			} else {
				return new WP_Error( 'complete99_sync_stored_integrity', 'The stored public read model failed its integrity check.', array( 'status' => 500 ) );
			}
		}
		$identity_check = self::validate_item_identities( $items, $stored_model );
		if ( is_wp_error( $identity_check ) ) {
			return $identity_check;
		}

		$clean = $payload;
		$stored_generated = $stored_is_legacy
			? (string) ( $stored_model['generated'] ?? '' )
			: (string) ( $stored_model['generated_at'] ?? '' );
		$stored_generated_at = '' !== $stored_generated
			? ( $stored_is_legacy
				? self::parse_rfc3339_millis( $stored_generated )
				: self::parse_canonical_generation_millis( $stored_generated ) )
			: false;
		$legacy_equal_equivalent = $stored_is_legacy
			&& false !== $stored_generated_at
			&& $generated_at === $stored_generated_at
			&& (string) ( $stored_model['version'] ?? '' ) === (string) $clean['version']
			&& hash_equals(
				self::canonical_read_model_value_digest(
					array(
						'menu_sections' => $clean['menu_sections'],
						'menu_items'    => $clean['menu_items'],
					)
				),
				self::canonical_read_model_value_digest(
					array(
						'menu_sections' => $stored_model['sections'] ?? array(),
						'menu_items'    => $stored_model['items'] ?? array(),
					)
				)
			);
		if ( ! $stored_is_legacy && false !== $stored_generated_at && $generated_at === $stored_generated_at ) {
			$equivalent = hash_equals(
				self::read_model_digest( $clean ),
				self::stored_read_model_digest( $stored_model )
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
						'item_count'    => count( $clean['menu_sections'] ) + count( $clean['menu_items'] ),
						'expires_at'    => $freshness['expires_at'],
						'cache'         => $cache,
					)
				);
			}
		}

		$now_millis = 1000 * time();
		if ( $generated_at < $now_millis - ( 1000 * self::MAX_CLOCK_SKEW ) || $generated_at > $now_millis + ( 1000 * self::MAX_CLOCK_SKEW ) ) {
			return new WP_Error( 'complete99_sync_stale_model', 'The public read model was not generated inside the accepted freshness window.', array( 'status' => 409 ) );
		}
		if ( false !== $stored_generated_at && $generated_at < $stored_generated_at ) {
			return new WP_Error( 'complete99_sync_non_monotonic', 'An older public read model cannot replace the current model.', array( 'status' => 409 ) );
		}
		if ( false !== $stored_generated_at && $generated_at === $stored_generated_at && ! $legacy_equal_equivalent ) {
			return new WP_Error( 'complete99_sync_non_monotonic', 'A different public read model must use a later generation timestamp.', array( 'status' => 409 ) );
		}

		$total = count( $clean['menu_sections'] ) + count( $clean['menu_items'] );
		if ( $total > self::MAX_MODEL_ITEMS ) {
			return new WP_Error( 'complete99_sync_items', 'The public read model contains too many records.', array( 'status' => 413 ) );
		}

		$clean['digest'] = self::read_model_digest( $clean );
		$write_changed = update_option( 'complete99_public_read_model', $clean, false );
		$persisted     = self::read_persisted_read_model();
		if ( is_wp_error( $persisted ) ) {
			return $persisted;
		}
		if ( ! is_array( $persisted )
			|| ! self::read_model_integrity_is_valid( $persisted )
			|| ! hash_equals( $clean['digest'], self::stored_read_model_digest( $persisted ) ) ) {
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
					return new WP_Error(
						'complete99_sync_boolean',
						'Public read-model boolean fields must use JSON booleans.',
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

	private static function transport_section_keys() {
		return array( 'id', 'name_he', 'name_en', 'sort', 'published' );
	}

	private static function transport_item_keys() {
		return array(
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
		);
	}

	private static function records_have_exact_keys( $records, $expected_keys ) {
		if ( ! is_array( $records ) ) {
			return false;
		}
		foreach ( $records as $record ) {
			if ( ! is_array( $record )
				|| array() !== array_diff( $expected_keys, array_keys( $record ) )
				|| array() !== array_diff( array_keys( $record ), $expected_keys ) ) {
				return false;
			}
		}
		return true;
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

	private static function parse_canonical_generation_millis( $value ) {
		$value = is_string( $value ) ? $value : '';
		if ( ! preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z\z/', $value ) ) {
			return false;
		}
		$date = \DateTimeImmutable::createFromFormat(
			'!Y-m-d\TH:i:s.v\Z',
			$value,
			new \DateTimeZone( 'UTC' )
		);
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) ) ) {
			return false;
		}
		return ( 1000 * $date->getTimestamp() ) + (int) $date->format( 'v' );
	}

	private static function parse_rfc3339_millis( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( ! preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.(\d{1,6}))?(?:Z|[+-]\d{2}:\d{2})\z/', $value, $matches ) ) {
			return false;
		}
		$timestamp = self::parse_rfc3339_timestamp( $value );
		if ( false === $timestamp ) {
			return false;
		}
		$fraction = isset( $matches[1] ) ? str_pad( $matches[1], 3, '0' ) : '000';
		return ( 1000 * $timestamp ) + (int) substr( $fraction, 0, 3 );
	}

	private static function canonicalize_read_model_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$is_list = true;
		$offset  = 0;
		foreach ( array_keys( $value ) as $key ) {
			if ( $key !== $offset ) {
				$is_list = false;
				break;
			}
			$offset++;
		}

		$canonical = array();
		foreach ( $value as $key => $item ) {
			$canonical[ $key ] = self::canonicalize_read_model_value( $item );
		}
		if ( ! $is_list ) {
			ksort( $canonical, SORT_STRING );
		}
		return $canonical;
	}

	private static function canonical_read_model_value_digest( $value ) {
		$encoded = wp_json_encode(
			self::canonicalize_read_model_value( $value ),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		return is_string( $encoded ) ? hash( 'sha256', $encoded ) : '';
	}

	private static function read_model_digest( $model ) {
		if ( ! is_array( $model ) ) {
			return '';
		}
		$unsigned = $model;
		unset( $unsigned['digest'] );
		return self::canonical_read_model_value_digest( $unsigned );
	}

	private static function stored_read_model_digest( $model ) {
		$digest = is_array( $model ) && isset( $model['digest'] ) ? (string) $model['digest'] : '';
		return 1 === preg_match( '/\A[a-f0-9]{64}\z/', $digest ) ? $digest : '';
	}

	private static function read_model_integrity_is_valid( $model ) {
		if ( ! self::is_valid_transport_read_model_shape( $model ) ) {
			return false;
		}
		$stored   = self::stored_read_model_digest( $model );
		$expected = self::read_model_digest( $model );
		return '' !== $stored && '' !== $expected && hash_equals( $expected, $stored );
	}

	private static function is_valid_transport_read_model_shape( $model ) {
		$required = array( 'schema', 'version', 'generated_at', 'branches', 'menu_sections', 'menu_items', 'campaigns', 'digest' );
		if ( ! is_array( $model )
			|| array() !== array_diff( $required, array_keys( $model ) )
			|| array() !== array_diff( array_keys( $model ), $required )
			|| 'complete99-public-read-model/v1' !== ( $model['schema'] ?? '' )
			|| '' === trim( (string) ( $model['version'] ?? '' ) )
			|| sanitize_text_field( (string) $model['version'] ) !== (string) $model['version']
			|| false === self::parse_canonical_generation_millis( (string) ( $model['generated_at'] ?? '' ) )
			|| trim( (string) $model['generated_at'] ) !== (string) $model['generated_at']
			|| ! is_array( $model['branches'] )
			|| array() !== $model['branches']
			|| ! is_array( $model['campaigns'] )
			|| array() !== $model['campaigns']
			|| ! is_array( $model['menu_sections'] )
			|| ! is_array( $model['menu_items'] )
			|| count( $model['menu_sections'] ) + count( $model['menu_items'] ) > self::MAX_MODEL_ITEMS ) {
			return false;
		}

		$sections = self::clean_records(
			$model['menu_sections'],
			self::transport_section_keys()
		);
		$items = self::clean_records(
			$model['menu_items'],
			self::transport_item_keys()
		);
		if ( ! is_wp_error( $items ) ) {
			foreach ( $items as $item ) {
				if ( ! isset( $item['updated_at'] ) || ! hash_equals( (string) $model['generated_at'], (string) $item['updated_at'] ) ) {
					return false;
				}
			}
		}
		return ! is_wp_error( $sections )
			&& ! is_wp_error( $items )
			&& self::records_have_exact_keys( $model['menu_sections'], self::transport_section_keys() )
			&& self::records_have_exact_keys( $model['menu_items'], self::transport_item_keys() )
			&& count( $sections ) === count( $model['menu_sections'] )
			&& count( $items ) === count( $model['menu_items'] )
			&& hash_equals( self::canonical_read_model_value_digest( $sections ), self::canonical_read_model_value_digest( $model['menu_sections'] ) )
			&& hash_equals( self::canonical_read_model_value_digest( $items ), self::canonical_read_model_value_digest( $model['menu_items'] ) );
	}

	private static function is_recognized_legacy_read_model( $model ) {
		if ( ! is_array( $model ) ) {
			return false;
		}
		$required = array( 'schema', 'version', 'generated', 'updated_at', 'sections', 'items', 'campaigns' );
		$allowed  = array_merge( $required, array( 'digest' ) );
		if ( array() !== array_diff( $required, array_keys( $model ) )
			|| array() !== array_diff( array_keys( $model ), $allowed )
			|| 'complete99-public-read-model/v1' !== ( $model['schema'] ?? '' )
			|| 1 !== preg_match( '/\Acomplete99-os-v\d+\z/', (string) ( $model['version'] ?? '' ) )
			|| false === self::parse_rfc3339_timestamp( (string) ( $model['generated'] ?? '' ) )
			|| false === self::parse_rfc3339_timestamp( (string) ( $model['updated_at'] ?? '' ) )
			|| ! is_array( $model['sections'] )
			|| ! is_array( $model['items'] )
			|| ! is_array( $model['campaigns'] )
			|| array() !== $model['campaigns']
			|| count( $model['sections'] ) + count( $model['items'] ) > self::MAX_MODEL_ITEMS ) {
			return false;
		}

		$sections = self::clean_records(
			$model['sections'],
			array( 'id', 'name_he', 'name_en', 'sort', 'published' )
		);
		$items = self::clean_records(
			$model['items'],
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
		if ( is_wp_error( $sections ) || is_wp_error( $items )
			|| count( $sections ) !== count( $model['sections'] )
			|| count( $items ) !== count( $model['items'] )
			|| ! hash_equals( self::canonical_read_model_value_digest( $sections ), self::canonical_read_model_value_digest( $model['sections'] ) )
			|| ! hash_equals( self::canonical_read_model_value_digest( $items ), self::canonical_read_model_value_digest( $model['items'] ) ) ) {
			return false;
		}

		if ( ! array_key_exists( 'digest', $model ) ) {
			$bundled = self::bundled_public_indexable_items();
			if ( 12 !== count( $model['items'] ) || count( $bundled ) !== count( $model['items'] ) ) {
				return false;
			}
			foreach ( $model['items'] as $offset => $item ) {
				if ( ! isset( $bundled[ $offset ] )
					|| ! hash_equals( (string) ( $bundled[ $offset ]['id'] ?? '' ), (string) ( $item['id'] ?? '' ) )
					|| ! hash_equals( (string) ( $bundled[ $offset ]['slug'] ?? '' ), sanitize_title( (string) ( $item['slug'] ?? '' ) ) ) ) {
					return false;
				}
			}
			return true;
		}
		$stored = self::stored_read_model_digest( $model );
		if ( '' === $stored ) {
			return false;
		}
		$unsigned = $model;
		unset( $unsigned['digest'] );
		$encoded = wp_json_encode( $unsigned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$expected = is_string( $encoded ) ? hash( 'sha256', $encoded ) : '';
		return '' !== $expected && hash_equals( $expected, $stored );
	}

	private static function model_freshness( $model ) {
		if ( ! self::read_model_integrity_is_valid( $model ) ) {
			return array(
				'fresh'      => false,
				'expires_at' => '',
			);
		}
		$updated_at = is_array( $model ) && isset( $model['generated_at'] ) ? (string) $model['generated_at'] : '';
		$timestamp  = self::parse_canonical_generation_millis( $updated_at );
		if ( false === $timestamp ) {
			return array(
				'fresh'      => false,
				'expires_at' => '',
			);
		}
		$expires = $timestamp + ( 1000 * self::PUBLIC_MODEL_TTL );
		$now_millis = 1000 * time();
		return array(
			'fresh'      => $timestamp <= $now_millis + ( 1000 * self::MAX_CLOCK_SKEW ) && $now_millis <= $expires,
			'expires_at' => gmdate( 'c', (int) floor( $expires / 1000 ) ),
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
		$stored_items = isset( $stored_model['menu_items'] ) && is_array( $stored_model['menu_items'] )
			? $stored_model['menu_items']
			: ( isset( $stored_model['items'] ) && is_array( $stored_model['items'] ) ? $stored_model['items'] : array() );
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
		$bundled = self::bundled_public_indexable_items();
		if ( ! is_array( $model ) || ! self::is_public_model_fresh( $model ) ) {
			return $bundled;
		}
		$records        = isset( $model['menu_items'] ) && is_array( $model['menu_items'] ) ? $model['menu_items'] : array();
		$synced_by_slug = array();
		$synced_order   = array();
		$exact_contract = true;
		foreach ( $records as $item ) {
			$slug = is_array( $item ) ? sanitize_title( (string) ( $item['slug'] ?? '' ) ) : '';
			if ( '' === $slug || isset( $synced_by_slug[ $slug ] ) ) {
				$exact_contract = false;
				continue;
			}
			$synced_by_slug[ $slug ] = $item;
			$synced_order[]           = $slug;
		}
		$bundled_order = array_map(
			static function ( $item ) {
				return sanitize_title( (string) ( $item['slug'] ?? '' ) );
			},
			$bundled
		);
		if ( $synced_order !== $bundled_order ) {
			$exact_contract = false;
		}
		$items = array();
		foreach ( $bundled as $item ) {
			$slug = sanitize_title( (string) ( $item['slug'] ?? '' ) );
			if ( ! isset( $synced_by_slug[ $slug ] ) ) {
				$exact_contract = false;
				continue;
			}
			$synced_item = $synced_by_slug[ $slug ];
			if ( ! self::is_public_item( $synced_item, $model ) ) {
				$exact_contract = false;
				continue;
			}
			if ( ! self::public_catalog_records_match( $synced_item, $item ) ) {
				$exact_contract = false;
			}
			$items[] = $item;
		}
		if ( count( $synced_by_slug ) !== count( $bundled ) || count( $items ) !== count( $bundled ) ) {
			$exact_contract = false;
		}
		$source = $exact_contract ? 'wordpress_bundle_attested_by_synced_model' : 'wordpress_bundle_with_synced_controls';
		foreach ( $items as &$item ) {
			$item['_complete99_source'] = $source;
		}
		unset( $item );
		return $items;
	}

	private static function public_catalog_contract( $records ) {
		$keys     = array(
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
		);
		$contract = array();
		foreach ( is_array( $records ) ? $records : array() as $record ) {
			if ( ! is_array( $record ) ) {
				return array();
			}
			$slug = sanitize_title( (string) ( $record['slug'] ?? '' ) );
			if ( '' === $slug || isset( $contract[ $slug ] ) ) {
				return array();
			}
			$contract[ $slug ] = array();
			foreach ( $keys as $key ) {
				if ( 'image_asset' === $key ) {
					$value = sanitize_file_name( (string) ( $record[ $key ] ?? '' ) );
				} elseif ( 'vegetarian' === $key ) {
					$value = true === ( $record[ $key ] ?? false );
				} else {
					$value = trim( (string) ( $record[ $key ] ?? '' ) );
				}
				$contract[ $slug ][ $key ] = $value;
			}
		}
		ksort( $contract, SORT_STRING );
		return $contract;
	}

	private static function public_catalog_records_match( $synced_item, $bundled_item ) {
		$synced  = self::public_catalog_contract( array( $synced_item ) );
		$bundled = self::public_catalog_contract( array( $bundled_item ) );
		if ( empty( $synced ) || empty( $bundled ) ) {
			return false;
		}
		$synced_digest  = hash( 'sha256', wp_json_encode( $synced, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		$bundled_digest = hash( 'sha256', wp_json_encode( $bundled, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		return hash_equals( $bundled_digest, $synced_digest );
	}

	private static function bundled_public_indexable_items() {
		static $items = null;
		if ( is_array( $items ) ) {
			return $items;
		}
		$records = require COMPLETE99_PLATFORM_DIR . 'data/consumer-menu.php';
		$items   = array();
		foreach ( is_array( $records ) ? $records : array() as $record ) {
			if ( ! is_array( $record ) || true !== ( $record['published'] ?? null ) ) {
				continue;
			}
			$required = array( 'id', 'slug', 'name_he', 'name_en', 'description_he', 'description_en' );
			foreach ( $required as $key ) {
				if ( '' === trim( (string) ( $record[ $key ] ?? '' ) ) ) {
					continue 2;
				}
			}
			$record['slug']                       = sanitize_title( (string) $record['slug'] );
			$record['verification_state']         = 'launch_ready';
			$record['updated_at']                 = self::BUNDLED_CATALOG_UPDATED_AT;
			$record['media_provenance']            = 'business_owned';
			$record['media_rights_state']          = 'approved_public_use';
			$record['vegetarian']                  = in_array( 'vegetarian', (array) ( $record['facets'] ?? array() ), true );
			$record['_complete99_source']          = 'wordpress_bundle';
			$items[]                               = $record;
		}
		return $items;
	}

	public static function public_indexable_item_by_slug( $slug, $model = null ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return array();
		}
		foreach ( self::public_indexable_items( $model ) as $item ) {
			if ( hash_equals( $slug, sanitize_title( (string) ( $item['slug'] ?? '' ) ) ) ) {
				return $item;
			}
		}
		return array();
	}

	public static function is_public_indexable_item( $record, $model = null ) {
		if ( ! is_array( $record ) ) {
			return false;
		}
		$canonical = self::public_indexable_item_by_slug( (string) ( $record['slug'] ?? '' ), $model );
		return ! empty( $canonical )
			&& hash_equals( (string) ( $canonical['id'] ?? '' ), (string) ( $record['id'] ?? '' ) );
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

	private static function public_catalog_sections( $items ) {
		$sections = array();
		$seen     = array();
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$name_he = trim( (string) ( $item['category_he'] ?? '' ) );
			$name_en = trim( (string) ( $item['category_en'] ?? '' ) );
			if ( '' === $name_he || '' === $name_en ) {
				continue;
			}
			$key = hash( 'sha256', $name_he . "\n" . $name_en );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$sections[]   = array(
				'name_he' => $name_he,
				'name_en' => $name_en,
			);
		}
		return $sections;
	}

	public static function public_catalog() {
		$model = get_option( 'complete99_public_read_model', array() );
		if ( ! is_array( $model ) ) {
			$model = array();
		}
		$freshness = self::model_freshness( $model );
		$indexable = self::public_indexable_items( $model );
		$source    = ! empty( $indexable )
			? (string) ( $indexable[0]['_complete99_source'] ?? 'wordpress_bundle' )
			: ( self::is_public_model_fresh( $model ) ? 'wordpress_bundle_with_synced_controls' : 'wordpress_bundle' );
		$attested  = 'wordpress_bundle_attested_by_synced_model' === $source;
		$fallback  = ! $attested;
		$model_is_fresh = self::is_public_model_fresh( $model );
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
			$indexable
		);
		return rest_ensure_response(
			array(
				'schema'     => $model_is_fresh && isset( $model['schema'] ) ? (string) $model['schema'] : 'complete99-public-read-model/v1',
				'version'    => self::BUNDLED_CATALOG_VERSION,
				'updated_at' => self::BUNDLED_CATALOG_UPDATED_AT,
				'source'     => $source,
				'sync'       => array(
					'attested'         => $attested,
					'controls_applied' => $model_is_fresh,
					'version'          => $model_is_fresh ? (string) ( $model['version'] ?? '' ) : '',
					'updated_at'       => $model_is_fresh ? (string) ( $model['generated_at'] ?? '' ) : '',
				),
				'sections'   => self::public_catalog_sections( $indexable ),
				'items'      => array_values( $items ),
				'freshness'  => array_merge( $freshness, array( 'fallback_active' => $fallback ) ),
			)
		);
	}
}
