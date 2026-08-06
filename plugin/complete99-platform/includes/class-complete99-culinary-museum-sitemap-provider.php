<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add only search-indexable culinary museum projections to WordPress sitemaps.
 */
final class Complete99_Culinary_Museum_Sitemap_Provider extends WP_Sitemaps_Provider {
	// WordPress core provider rewrites accept lowercase ASCII letters only.
	const PROVIDER_NAME = 'completemuseum';

	public function __construct() {
		$this->name        = self::PROVIDER_NAME;
		$this->object_type = 'complete99-culinary-museum';
	}

	public static function boot() {
		add_action( 'wp_sitemaps_init', array( __CLASS__, 'register' ) );
	}

	public static function register( $sitemaps ) {
		if ( ! is_object( $sitemaps )
			|| ! isset( $sitemaps->registry )
			|| ! is_object( $sitemaps->registry )
			|| ! method_exists( $sitemaps->registry, 'add_provider' ) ) {
			return false;
		}

		return $sitemaps->registry->add_provider( self::PROVIDER_NAME, new self() );
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		$page_num = max( 1, (int) $page_num );
		$per_page = max( 1, (int) wp_sitemaps_get_max_urls( $this->object_type ) );
		$offset   = ( $page_num - 1 ) * $per_page;
		return array_slice( $this->url_entries(), $offset, $per_page );
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		$count = count( $this->url_entries() );
		if ( 0 === $count ) {
			return 0;
		}
		$per_page = max( 1, (int) wp_sitemaps_get_max_urls( $this->object_type ) );
		return (int) ceil( $count / $per_page );
	}

	private function url_entries() {
		if ( ! class_exists( 'Complete99_Culinary_Science', false )
			|| ! is_callable( array( 'Complete99_Culinary_Science', 'public_indexable_page_projections' ) ) ) {
			return array();
		}

		$projections = Complete99_Culinary_Science::public_indexable_page_projections();
		if ( ! is_array( $projections ) ) {
			return array();
		}

		$entries = array();
		$seen    = array();
		foreach ( $projections as $projection ) {
			if ( ! is_array( $projection ) || ( isset( $projection['indexable'] ) && true !== $projection['indexable'] ) ) {
				continue;
			}
			$loc = $this->projection_url( $projection );
			if ( '' === $loc || isset( $seen[ $loc ] ) ) {
				continue;
			}
			$seen[ $loc ] = true;
			$entry        = array( 'loc' => $loc );
			$lastmod      = $this->projection_lastmod( $projection );
			if ( '' !== $lastmod ) {
				$entry['lastmod'] = $lastmod;
			}
			$entries[] = $entry;
		}
		usort(
			$entries,
			static function ( $left, $right ) {
				return strcmp( $left['loc'], $right['loc'] );
			}
		);
		return $entries;
	}

	private function projection_url( $projection ) {
		if ( ! empty( $projection['canonical_url'] ) ) {
			$url = (string) $projection['canonical_url'];
		} elseif ( ! empty( $projection['canonical_path'] ) ) {
			$url = home_url( (string) $projection['canonical_path'] );
		} elseif ( ! empty( $projection['seo']['canonical_path'] ) ) {
			$url = home_url( (string) $projection['seo']['canonical_path'] );
		} elseif ( ! empty( $projection['entity']['seo']['canonical_path'] ) ) {
			$url = home_url( (string) $projection['entity']['seo']['canonical_path'] );
		} else {
			return '';
		}

		$url_parts  = wp_parse_url( $url );
		$home_parts = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $url_parts ) || ! is_array( $home_parts )
			|| empty( $url_parts['scheme'] ) || ! in_array( strtolower( $url_parts['scheme'] ), array( 'http', 'https' ), true )
			|| empty( $url_parts['host'] ) || empty( $home_parts['host'] )
			|| strtolower( $url_parts['host'] ) !== strtolower( $home_parts['host'] )
			|| empty( $url_parts['path'] ) || '/' !== substr( $url_parts['path'], -1 )
			|| isset( $url_parts['query'] ) || isset( $url_parts['fragment'] ) ) {
			return '';
		}
		return $url;
	}

	private function projection_lastmod( $projection ) {
		$candidates = array(
			isset( $projection['lastmod'] ) ? $projection['lastmod'] : '',
			isset( $projection['trust']['substantive_updated_at'] ) ? $projection['trust']['substantive_updated_at'] : '',
			isset( $projection['entity']['trust']['substantive_updated_at'] ) ? $projection['entity']['trust']['substantive_updated_at'] : '',
			isset( $projection['reviewed_at'] ) ? $projection['reviewed_at'] : '',
			isset( $projection['entity']['reviewed_at'] ) ? $projection['entity']['reviewed_at'] : '',
		);
		foreach ( $candidates as $candidate ) {
			$timestamp = '' !== (string) $candidate ? strtotime( (string) $candidate ) : false;
			if ( false !== $timestamp ) {
				return gmdate( 'c', $timestamp );
			}
		}
		return '';
	}
}
