<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_REST {
	const NAMESPACE       = 'complete99/v1';
	const MAX_BODY_BYTES  = 524288;
	const MAX_CLOCK_SKEW  = 300;
	const NONCE_TTL       = 600;
	const MAX_MODEL_ITEMS = 500;

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
		$database_version = (string) get_option( 'complete99_platform_version', '' );
		if ( Complete99_Platform::migration_failed() || COMPLETE99_PLATFORM_VERSION !== $database_version ) {
			return new WP_Error(
				'complete99_migration_incomplete',
				'Complete99 is not ready because its database migration is incomplete.',
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
					'version'    => isset( $model['version'] ) ? (string) $model['version'] : '',
					'updated_at' => isset( $model['updated_at'] ) ? (string) $model['updated_at'] : '',
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
		if ( false !== get_transient( $nonce_key ) ) {
			return new WP_Error( 'complete99_sync_replay', 'This sync request has already been used.', array( 'status' => 409 ) );
		}

		$canonical = $timestamp . "\n" . $nonce . "\n" . hash( 'sha256', $raw );
		$expected  = hash_hmac( 'sha256', $canonical, $secret );
		if ( ! hash_equals( $expected, $signature ) ) {
			return new WP_Error( 'complete99_sync_signature', 'The sync signature is invalid.', array( 'status' => 401 ) );
		}

		set_transient( $nonce_key, 1, self::NONCE_TTL );
		return true;
	}

	public static function sync_read_model( WP_REST_Request $request ) {
		$payload = json_decode( (string) $request->get_body(), true );
		if ( ! is_array( $payload ) || 'complete99-public-read-model/v1' !== ( isset( $payload['schema'] ) ? $payload['schema'] : '' ) ) {
			return new WP_Error( 'complete99_sync_schema', 'Unsupported public read-model schema.', array( 'status' => 400 ) );
		}

		$clean = array(
			'schema'     => 'complete99-public-read-model/v1',
			'version'    => sanitize_text_field( isset( $payload['version'] ) ? (string) $payload['version'] : '' ),
			'generated'  => sanitize_text_field( isset( $payload['generated_at'] ) ? (string) $payload['generated_at'] : '' ),
			'updated_at' => gmdate( 'c' ),
			'branches'   => self::clean_records( isset( $payload['branches'] ) ? $payload['branches'] : array(), array( 'id', 'name_he', 'name_en', 'slug', 'city_he', 'city_en', 'status', 'published' ) ),
			'sections'   => self::clean_records( isset( $payload['menu_sections'] ) ? $payload['menu_sections'] : array(), array( 'id', 'name_he', 'name_en', 'sort', 'published' ) ),
			'items'      => self::clean_records( isset( $payload['menu_items'] ) ? $payload['menu_items'] : array(), array( 'id', 'section_id', 'name_he', 'name_en', 'description_he', 'description_en', 'image_asset', 'public_price', 'currency', 'verification_state', 'published', 'sort' ) ),
			'campaigns'  => self::clean_records( isset( $payload['campaigns'] ) ? $payload['campaigns'] : array(), array( 'id', 'title_he', 'title_en', 'summary_he', 'summary_en', 'cta_label_he', 'cta_label_en', 'cta_url', 'published' ) ),
		);

		$total = count( $clean['branches'] ) + count( $clean['sections'] ) + count( $clean['items'] ) + count( $clean['campaigns'] );
		if ( $total > self::MAX_MODEL_ITEMS ) {
			return new WP_Error( 'complete99_sync_items', 'The public read model contains too many records.', array( 'status' => 413 ) );
		}

		$clean['digest'] = hash( 'sha256', wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_option( 'complete99_public_read_model', $clean, false );

		return rest_ensure_response(
			array(
				'stored'     => true,
				'version'    => $clean['version'],
				'digest'     => $clean['digest'],
				'item_count' => $total,
			)
		);
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
			$item = array();
			foreach ( $allowed_keys as $key ) {
				if ( ! array_key_exists( $key, $record ) ) {
					continue;
				}
				if ( 'published' === $key ) {
					$item[ $key ] = (bool) $record[ $key ];
				} elseif ( in_array( $key, array( 'sort', 'public_price' ), true ) ) {
					$item[ $key ] = is_numeric( $record[ $key ] ) ? (float) $record[ $key ] : null;
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

	public static function public_catalog() {
		$model = get_option( 'complete99_public_read_model', array() );
		if ( ! is_array( $model ) ) {
			$model = array();
		}
		foreach ( array( 'branches', 'sections', 'items', 'campaigns' ) as $key ) {
			$records       = isset( $model[ $key ] ) && is_array( $model[ $key ] ) ? $model[ $key ] : array();
			$model[ $key ] = array_values(
				array_filter(
					$records,
					static function ( $record ) {
						return is_array( $record ) && ! empty( $record['published'] );
					}
				)
			);
		}
		unset( $model['generated'] );
		return rest_ensure_response( $model );
	}
}
