<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verified public ordering destinations and private-ready connector slots.
 */
final class Complete99_Order_Connectors {
	private static $registry = null;

	public static function registry() {
		if ( null === self::$registry ) {
			$registry = require COMPLETE99_PLATFORM_DIR . 'data/order-connectors.php';
			self::$registry = is_array( $registry ) ? $registry : array();
		}
		return self::$registry;
	}

	public static function public_connectors( $language = 'he' ) {
		$language = 'en' === strtolower( trim( (string) $language ) ) ? 'en' : 'he';
		$active   = array();
		foreach ( self::registry() as $key => $connector ) {
			if ( ! self::is_public_ready( $connector, $language ) ) {
				continue;
			}
			$active[] = array(
				'key'   => sanitize_key( (string) $key ),
				'label' => sanitize_text_field( (string) $connector['label'] ),
				'url'   => esc_url_raw( (string) $connector[ 'url_' . $language ] ),
			);
		}
		return $active;
	}

	public static function primary_url( $language = 'he' ) {
		$active = self::public_connectors( $language );
		return ! empty( $active ) ? (string) $active[0]['url'] : '';
	}

	public static function primary_label( $language = 'he' ) {
		$active = self::public_connectors( $language );
		return ! empty( $active ) ? (string) $active[0]['label'] : '';
	}

	private static function is_public_ready( $connector, $language ) {
		if ( ! is_array( $connector )
			|| empty( $connector['public_enabled'] )
			|| empty( $connector['merchant_verified'] )
			|| empty( $connector['availability_check'] )
			|| empty( $connector['acceptance_receipt'] ) ) {
			return false;
		}
		$url   = (string) ( $connector[ 'url_' . $language ] ?? '' );
		$parts = wp_parse_url( $url );
		return is_array( $parts )
			&& 'https' === strtolower( (string) ( $parts['scheme'] ?? '' ) )
			&& '' !== (string) ( $parts['host'] ?? '' )
			&& empty( $parts['user'] )
			&& empty( $parts['pass'] );
	}
}
