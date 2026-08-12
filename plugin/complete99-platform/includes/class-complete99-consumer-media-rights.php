<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exact server-side rights overlay for legacy consumer photography.
 *
 * The frozen consumer-menu payload is an input to the cross-domain registry,
 * so its bytes cannot be relabelled. This separately authenticated overlay
 * records illustrative public use while keeping Campaign/paid use held.
 */
final class Complete99_Consumer_Media_Rights {
	const SCHEMA                 = 'complete99-consumer-media-rights/v1';
	const VERSION                = 'complete99-consumer-media-rights-2026.08.11.v1';
	const DATA_FILE              = 'consumer-media-rights.php';
	const EXPECTED_RECORD_COUNT  = 13;
	const PAYLOAD_SHA256         = '9a0f6ceacea89869b0da896f10b0f6f25f8b082e9a911a377d36bf9afe00527f';

	private static $registry_cache = null;

	/** Load and validate the exact checked-in rights overlay. */
	public static function registry( $fresh = false ) {
		if ( $fresh ) {
			self::$registry_cache = null;
		}
		if ( is_array( self::$registry_cache ) ) {
			return self::$registry_cache;
		}
		if ( ! defined( 'COMPLETE99_PLATFORM_DIR' ) ) {
			throw new \RuntimeException( 'consumer_media_rights.platform_dir' );
		}
		$path = COMPLETE99_PLATFORM_DIR . 'data/' . self::DATA_FILE;
		if ( ! is_readable( $path ) ) {
			throw new \RuntimeException( 'consumer_media_rights.missing' );
		}
		$registry = require $path;
		self::assert_registry_valid( $registry );
		self::$registry_cache = $registry;
		return self::$registry_cache;
	}

	/** Deployment/runtime invariant: always re-read bytes rather than trust cache. */
	public static function assert_invariants() {
		self::registry( true );
		return true;
	}

	/** Offline/test validator that cannot alter request-local registry state. */
	public static function validate_registry( $registry ) {
		try {
			self::assert_registry_valid( $registry );
			return true;
		} catch ( \Throwable $error ) {
			return false;
		}
	}

	/** Return the exact rights record for a file name, URL path, or asset stem. */
	public static function record_for_asset( $asset ) {
		$stem = self::normalize_asset_stem( $asset );
		if ( '' === $stem ) {
			return array();
		}
		foreach ( self::registry()['records'] as $record ) {
			if ( hash_equals( (string) $record['asset_stem'], $stem ) ) {
				return $record;
			}
		}
		return array();
	}

	/** Canonical digest exposed for release/readiness evidence. */
	public static function payload_digest( $registry = null ) {
		if ( null === $registry ) {
			$registry = self::registry();
		}
		$json = json_encode(
			self::canonicalize( $registry ),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
		);
		return hash( 'sha256', $json );
	}

	private static function assert_registry_valid( $registry ) {
		self::assert_exact_keys( $registry, array( 'schema', 'version', 'records' ), 'registry' );
		if ( self::SCHEMA !== $registry['schema'] ) {
			throw new \RuntimeException( 'registry.schema' );
		}
		if ( self::VERSION !== $registry['version'] ) {
			throw new \RuntimeException( 'registry.version' );
		}
		if ( ! is_array( $registry['records'] ) || self::EXPECTED_RECORD_COUNT !== count( $registry['records'] )
			|| array_keys( $registry['records'] ) !== range( 0, self::EXPECTED_RECORD_COUNT - 1 ) ) {
			throw new \RuntimeException( 'registry.records' );
		}
		$expected_stems = self::expected_stems();
		$actual_stems   = array();
		foreach ( $registry['records'] as $offset => $record ) {
			$path = 'registry.records.' . $offset;
			self::assert_exact_keys(
				$record,
				array( 'asset_stem', 'media_provenance', 'media_rights_state', 'illustrative_public_use', 'campaign_use_authorized', 'paid_media_authorized', 'rights_receipt_sha256' ),
				$path
			);
			if ( ! is_string( $record['asset_stem'] ) || ! preg_match( '/\Ac99-food-[a-z0-9-]{12,120}\z/', $record['asset_stem'] ) ) {
				throw new \RuntimeException( $path . '.asset_stem' );
			}
			if ( 'complete99_archive' !== $record['media_provenance']
				|| 'approved_public_use' !== $record['media_rights_state']
				|| true !== $record['illustrative_public_use']
				|| false !== $record['campaign_use_authorized']
				|| false !== $record['paid_media_authorized']
				|| '' !== $record['rights_receipt_sha256'] ) {
				throw new \RuntimeException( $path . '.rights_boundary' );
			}
			$actual_stems[] = $record['asset_stem'];
		}
		if ( $expected_stems !== $actual_stems ) {
			throw new \RuntimeException( 'registry.records.asset_stems' );
		}
		if ( ! hash_equals( self::PAYLOAD_SHA256, self::payload_digest( $registry ) ) ) {
			throw new \RuntimeException( 'registry.payload_sha256' );
		}
	}

	private static function expected_stems() {
		return array(
			'c99-food-beef-meatballs-gravy-gallery-2021-wp-v01',
			'c99-food-chicken-liver-plate-gallery-2021-wp-v01',
			'c99-food-couscous-beef-gallery-2021-wp-v01',
			'c99-food-fish-meatballs-tomato-menu-2021-mishloha-v01',
			'c99-food-grilled-chicken-plate-menu-2021-mishloha-v01',
			'c99-food-herb-omelet-pita-menu-2021-mishloha-v01',
			'c99-food-house-spread-hero-2021-wp-v01',
			'c99-food-kubeh-beet-soup-gallery-2021-wp-v01',
			'c99-food-sabich-pita-gallery-2021-wp-v01',
			'c99-food-sabtucha-pita-gallery-2021-wp-v01',
			'c99-food-schnitzel-pita-menu-2021-mishloha-v01',
			'c99-food-shakshuka-plate-gallery-2021-wp-v01',
			'c99-food-yemenite-beef-soup-menu-2021-mishloha-v01',
		);
	}

	private static function normalize_asset_stem( $asset ) {
		$value = trim( (string) $asset );
		if ( '' === $value ) {
			return '';
		}
		$value    = preg_split( '/[?#]/', str_replace( '\\', '/', $value ), 2 )[0];
		$filename = basename( $value );
		$stem     = pathinfo( $filename, PATHINFO_FILENAME );
		$stem     = preg_replace( '/(?:-[0-9]+x[0-9]+|-768)\z/', '', strtolower( $stem ) );
		return preg_match( '/\Ac99-food-[a-z0-9-]{12,120}\z/', $stem ) ? $stem : '';
	}

	private static function assert_exact_keys( $value, $expected, $path ) {
		if ( ! is_array( $value ) ) {
			throw new \RuntimeException( $path . '.type' );
		}
		$actual = array_keys( $value );
		sort( $actual, SORT_STRING );
		sort( $expected, SORT_STRING );
		if ( $actual !== $expected ) {
			throw new \RuntimeException( $path . '.keys' );
		}
	}

	private static function canonicalize( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );
		if ( ! $is_list ) {
			ksort( $value, SORT_STRING );
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonicalize( $item );
		}
		return $value;
	}
}
