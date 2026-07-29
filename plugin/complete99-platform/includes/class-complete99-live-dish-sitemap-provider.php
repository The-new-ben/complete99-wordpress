<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Expose exact, fresh live-dish canonicals through the WordPress sitemap index.
 */
final class Complete99_Live_Dish_Sitemap_Provider extends WP_Sitemaps_Provider {
	// Core's provider rewrite accepts lowercase ASCII letters only.
	const PROVIDER_NAME = 'completedishes';

	public function __construct() {
		$this->name        = self::PROVIDER_NAME;
		$this->object_type = 'complete99-live-dish';
	}

	public static function boot() {
		add_action( 'wp_sitemaps_init', array( __CLASS__, 'register' ) );
	}

	public static function register( $sitemaps ) {
		if (
			! is_object( $sitemaps )
			|| ! isset( $sitemaps->registry )
			|| ! is_object( $sitemaps->registry )
			|| ! method_exists( $sitemaps->registry, 'add_provider' )
		) {
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
		$items = Complete99_REST::public_indexable_items();
		$urls  = array();
		foreach ( $items as $item ) {
			$slug = sanitize_title( isset( $item['slug'] ) ? (string) $item['slug'] : '' );
			if ( '' === $slug ) {
				continue;
			}
			$last_modified = isset( $item['updated_at'] ) ? strtotime( (string) $item['updated_at'] ) : false;
			foreach ( array( 'he', 'en' ) as $language ) {
				$entry = array(
					'loc' => Complete99_Frontend::live_dish_url( $slug, $language ),
				);
				if ( false !== $last_modified ) {
					$entry['lastmod'] = gmdate( 'c', $last_modified );
				}
				$urls[] = $entry;
			}
		}

		return $urls;
	}
}
