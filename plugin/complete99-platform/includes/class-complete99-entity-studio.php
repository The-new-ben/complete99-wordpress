<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Private WordPress authoring surface for modular entity economics.
 *
 * WooCommerce remains the authority for public price, stock and orders. This
 * studio records reviewed commercial decisions and their WordPress revisions.
 * It never changes a WooCommerce product or emits a public projection.
 */
final class Complete99_Entity_Studio {
	const PAGE_SLUG          = 'complete99-entity-studio';
	const POST_TYPE          = 'c99_entity_dossier';
	const RECORD_SCHEMA      = 'complete99-entity-studio-economic-dossier/v1';
	const REST_NAMESPACE     = 'complete99/v1';
	const REST_ROUTE         = '/editorial/entity-studio';
	const REST_PAGE_SIZE     = 50;
	const REST_MAX_PAGE_SIZE = 100;
	const RECORD_PAGE_SIZE   = 250;
	const MAX_RELATIONS      = 30;
	const MAX_OBSERVATIONS   = 50;
	const MAX_HISTORY        = 5000;
	const LOCK_TIMEOUT       = 3;

	private static $booted = false;
	private static $subject_cache = null;
	private static $reference_cache = null;
	private static $file_lock_handle = null;

	/**
	 * Register private infrastructure without creating or assigning roles.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_action( 'init', array( __CLASS__, 'register_post_type' ), 7 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_post_complete99_entity_studio_save', array( __CLASS__, 'handle_admin_save' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'wp_revisions_to_keep', array( __CLASS__, 'retain_revisions' ), 10, 2 );
		add_filter( 'pre_delete_post', array( __CLASS__, 'prevent_deletion' ), 10, 3 );
		add_filter( 'pre_trash_post', array( __CLASS__, 'prevent_trash' ), 10, 3 );
	}

	/**
	 * Store one private WordPress record per stable graph subject.
	 */
	public static function register_post_type() {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}
		$capabilities = array(
			'edit_post'              => 'manage_options',
			'read_post'              => 'manage_options',
			'delete_post'            => 'do_not_allow',
			'edit_posts'             => 'manage_options',
			'edit_others_posts'      => 'manage_options',
			'publish_posts'          => 'manage_options',
			'read_private_posts'     => 'manage_options',
			'delete_posts'           => 'do_not_allow',
			'delete_private_posts'   => 'do_not_allow',
			'delete_published_posts' => 'do_not_allow',
			'delete_others_posts'    => 'do_not_allow',
			'edit_private_posts'     => 'manage_options',
			'edit_published_posts'   => 'manage_options',
			'create_posts'           => 'manage_options',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Complete99 Entity Studio',
					'singular_name' => 'Complete99 entity dossier',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'map_meta_cap'        => false,
				'capabilities'        => $capabilities,
				'supports'            => array( 'title', 'editor', 'revisions' ),
			)
		);
	}

	public static function retain_revisions( $number, $post ) {
		return $post instanceof WP_Post && self::POST_TYPE === $post->post_type ? -1 : $number;
	}

	private static function is_studio_post_or_revision( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
		if ( self::POST_TYPE === $post->post_type ) {
			return true;
		}
		return 'revision' === $post->post_type && self::POST_TYPE === get_post_type( (int) $post->post_parent );
	}

	public static function prevent_deletion( $delete, $post, $force_delete ) {
		return self::is_studio_post_or_revision( $post ) ? false : $delete;
	}

	public static function prevent_trash( $trash, $post, $previous_status ) {
		return self::is_studio_post_or_revision( $post ) ? false : $trash;
	}

	public static function assert_invariants() {
		self::register_post_type();
		$object = get_post_type_object( self::POST_TYPE );
		if ( ! $object
			|| $object->public
			|| $object->publicly_queryable
			|| ! $object->exclude_from_search
			|| $object->show_in_rest
			|| false !== $object->rewrite
			|| 'do_not_allow' !== (string) ( $object->cap->delete_post ?? '' ) ) {
			throw new RuntimeException( 'entity-studio-private-invariants' );
		}
		return true;
	}

	public static function admin_menu() {
		add_management_page(
			'Complete99 Entity Studio',
			'Complete99 Entity Studio',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function can_manage() {
		return current_user_can( 'manage_options' );
	}

	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => array( __CLASS__, 'can_manage' ),
					'callback'            => array( __CLASS__, 'rest_snapshot' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( __CLASS__, 'can_manage' ),
					'callback'            => array( __CLASS__, 'rest_save' ),
				),
			)
		);
	}

	private static function add_observation_reference( &$references, $id, $reference ) {
		if ( isset( $references['observations'][ $id ] ) ) {
			throw new RuntimeException( 'entity-studio-observation-identity-collision.' . $id );
		}
		$references['observations'][ $id ] = $reference;
	}

	private static function load_data_file( $filename ) {
		$filename = sanitize_file_name( (string) $filename );
		if ( '' === $filename || false === strpos( $filename, '.php' ) ) {
			return array();
		}

		$path = COMPLETE99_PLATFORM_DIR . 'data/' . $filename;
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$value = require $path;
		return is_array( $value ) ? $value : array();
	}

	private static function local_text( $value, $language = '' ) {
		if ( '' === $language ) {
			$language = 0 === strpos( strtolower( (string) get_user_locale() ), 'he' ) ? 'he' : 'en';
		}
		if ( is_array( $value ) ) {
			$value = isset( $value[ $language ] ) ? $value[ $language ] : ( isset( $value['en'] ) ? $value['en'] : '' );
		}
		return sanitize_text_field( (string) $value );
	}

	private static function price_to_minor( $value ) {
		return self::amount_to_minor( $value, 2 );
	}

	private static function amount_to_minor( $value, $minor_unit_digits ) {
		$value = trim( (string) $value );
		$minor_unit_digits = (int) $minor_unit_digits;
		if ( $minor_unit_digits < 0 || $minor_unit_digits > 3 ) {
			return null;
		}
		$pattern = 0 === $minor_unit_digits
			? '/\A(0|[1-9][0-9]{0,9})\z/'
			: '/\A(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,' . $minor_unit_digits . '}))?\z/';
		if ( 1 !== preg_match( $pattern, $value, $match ) ) {
			return null;
		}
		$whole = (int) $match[1];
		$factor = 10 ** $minor_unit_digits;
		$decimals = 0 === $minor_unit_digits ? 0 : (int) str_pad( $match[2] ?? '', $minor_unit_digits, '0' );
		return ( $whole * $factor ) + $decimals;
	}

	private static function minor_to_price( $value ) {
		return self::minor_to_amount( $value, 2 );
	}

	private static function minor_to_amount( $value, $minor_unit_digits ) {
		$value = max( 0, (int) $value );
		$minor_unit_digits = min( 3, max( 0, (int) $minor_unit_digits ) );
		$factor = 10 ** $minor_unit_digits;
		return number_format( $value / $factor, $minor_unit_digits, '.', '' );
	}

	private static function currency_digits( $currency_code, $references = null ) {
		$references = is_array( $references ) ? $references : self::reference_index();
		$currency_code = strtoupper( sanitize_text_field( (string) $currency_code ) );
		$currency = $references['currencies'][ $currency_code ] ?? array();
		return isset( $currency['minor_unit_digits'] ) ? (int) $currency['minor_unit_digits'] : 2;
	}

	private static function catalog_observation_id( $product ) {
		$product_code = sanitize_key( (string) ( $product['product_code'] ?? '' ) );
		$observation = isset( $product['market_observation'] ) && is_array( $product['market_observation'] ) ? $product['market_observation'] : array();
		$checked_at = preg_replace( '/[^0-9]/', '', (string) ( $observation['checked_at'] ?? '' ) );
		return '' === $product_code ? '' : 'catalog-observation-' . $product_code . ( '' !== $checked_at ? '-' . $checked_at : '' );
	}

	private static function scoped_base_registry( $schema, $version, $record_kind, $stable_id, $payload ) {
		return array(
			'schema'  => sanitize_text_field( (string) $schema ),
			'version' => sanitize_text_field( (string) $version ),
			'digest'  => self::canonical_digest(
				array(
					'record_kind' => sanitize_key( (string) $record_kind ),
					'stable_id'   => sanitize_key( (string) $stable_id ),
					'payload'     => $payload,
				)
			),
		);
	}

	private static function combined_base_registry( $bases ) {
		$bases = array_values( array_filter( $bases, 'is_array' ) );
		if ( 1 === count( $bases ) ) {
			return $bases[0];
		}
		usort( $bases, static function ( $left, $right ) { return strcmp( (string) $left['schema'], (string) $right['schema'] ); } );
		return array(
			'schema'  => 'complete99-entity-studio-combined-base/v1',
			'version' => implode( '|', array_map( static function ( $base ) { return (string) $base['version']; }, $bases ) ),
			'digest'  => self::canonical_digest( $bases ),
		);
	}

	/**
	 * Merge the science graph, private product graph and live catalog identity.
	 */
	public static function subject_index( $fresh = false ) {
		if ( ! $fresh && is_array( self::$subject_cache ) ) {
			return self::$subject_cache;
		}

		$subjects = array();
		$price_bundle = self::load_data_file( 'live-catalog-prices.php' );
		$prices = isset( $price_bundle['prices'] ) && is_array( $price_bundle['prices'] ) ? $price_bundle['prices'] : array();

		$science = class_exists( 'Complete99_Culinary_Science', false )
			? Complete99_Culinary_Science::registry( $fresh )
			: array();
		$science_schema = is_array( $science ) ? (string) ( $science['schema'] ?? '' ) : '';
		$science_version = is_array( $science ) ? (string) ( $science['version'] ?? '' ) : '';
		if ( ! is_wp_error( $science ) && isset( $science['entities'] ) && is_array( $science['entities'] ) ) {
			foreach ( $science['entities'] as $entity ) {
				$id = isset( $entity['id'] ) ? sanitize_key( (string) $entity['id'] ) : '';
				if ( '' === $id ) {
					continue;
				}
				$commerce = isset( $entity['commerce'] ) && is_array( $entity['commerce'] ) ? $entity['commerce'] : array();
				$product_code = isset( $commerce['woo_product_code'] ) ? sanitize_key( (string) $commerce['woo_product_code'] ) : '';
				$current_price_minor = isset( $prices[ $product_code ] ) ? self::price_to_minor( $prices[ $product_code ] ) : null;
				$subjects[ $id ] = array(
					'id'                    => $id,
					'workspace_identity'    => 'science:entity:' . $id,
					'subject_type'          => 'entity',
					'domain'                => 'science',
					'record_kind'           => 'entity',
					'base_registry'         => self::scoped_base_registry( $science_schema, $science_version, 'entity', $id, $entity ),
					'label'                 => isset( $entity['name'] ) ? $entity['name'] : array( 'he' => $id, 'en' => $id ),
					'entity_type'           => isset( $entity['type'] ) ? sanitize_key( (string) $entity['type'] ) : '',
					'related_entity_id'     => $id,
					'woo_product_code'      => $product_code,
					'current_price_minor'   => $current_price_minor,
					'current_price_scope'   => null === $current_price_minor ? '' : (string) ( $price_bundle['price_scope'] ?? '' ),
					'planning_price_minor'  => null,
					'planning_currency_code'=> '',
					'planning_offer_ids'    => array(),
					'commerce_state'        => isset( $commerce['state'] ) ? sanitize_key( (string) $commerce['state'] ) : '',
					'knowledge_entity_id'   => '',
					'offer_type_hint'        => 'none',
					'market_observation_ids'=> isset( $commerce['observation_entity_ids'] ) && is_array( $commerce['observation_entity_ids'] ) ? array_values( array_map( 'sanitize_key', $commerce['observation_entity_ids'] ) ) : array(),
				);
			}
		}

		$product_bundle = self::load_data_file( 'catalog-product-seeds.php' );
		$catalog_schema = (string) ( $product_bundle['schema'] ?? '' );
		$catalog_version = (string) ( $product_bundle['registry_reviewed_at'] ?? '' );
		$catalog_products = isset( $product_bundle['products'] ) && is_array( $product_bundle['products'] ) ? $product_bundle['products'] : array();
		foreach ( $catalog_products as $product ) {
			$id = isset( $product['product_code'] ) ? sanitize_key( (string) $product['product_code'] ) : '';
			if ( '' === $id ) {
				continue;
			}
			if ( isset( $subjects[ $id ] ) ) {
				throw new RuntimeException( 'entity-studio-subject-identity-collision.' . $id );
			}
			$current_price_minor = isset( $prices[ $id ] ) ? self::price_to_minor( $prices[ $id ] ) : null;
			$catalog_observation_id = self::catalog_observation_id( $product );
			$subjects[ $id ] = array(
				'id'                    => $id,
				'workspace_identity'    => 'catalog:product:' . $id,
				'subject_type'          => 'product',
				'domain'                => 'catalog',
				'record_kind'           => 'product',
				'base_registry'         => self::scoped_base_registry(
					$catalog_schema,
					$catalog_version,
					'product',
					$id,
					array(
						'product'             => $product,
						'authorized_price'    => $prices[ $id ] ?? null,
						'authorized_price_scope' => (string) ( $price_bundle['price_scope'] ?? '' ),
					)
				),
				'label'                 => isset( $product['name'] ) ? $product['name'] : array( 'he' => $id, 'en' => $id ),
				'entity_type'           => isset( $product['product_kind'] ) ? sanitize_key( (string) $product['product_kind'] ) : 'product',
				'related_entity_id'     => isset( $product['ingredient_code'] ) ? sanitize_key( (string) $product['ingredient_code'] ) : '',
				'woo_product_code'      => $id,
				'current_price_minor'   => $current_price_minor,
				'current_price_scope'   => null === $current_price_minor ? '' : (string) ( $price_bundle['price_scope'] ?? '' ),
				'planning_price_minor'  => null,
				'planning_currency_code'=> '',
				'planning_offer_ids'    => array(),
				'commerce_state'        => isset( $product['sale_state'] ) ? sanitize_key( (string) $product['sale_state'] ) : '',
				'knowledge_entity_id'   => isset( $product['ingredient_code'] ) ? sanitize_key( (string) $product['ingredient_code'] ) : '',
				'offer_type_hint'        => 'equipment' === (string) ( $product['product_kind'] ?? '' ) ? 'equipment' : 'ingredient',
				'market_observation_ids'=> '' === $catalog_observation_id ? array() : array( $catalog_observation_id ),
			);
		}

		$commerce = class_exists( 'Complete99_Culinary_Commerce', false )
			? Complete99_Culinary_Commerce::registry( $fresh )
			: array();
		$commerce_schema = is_array( $commerce ) ? (string) ( $commerce['schema'] ?? '' ) : '';
		$commerce_version = is_array( $commerce ) ? (string) ( $commerce['version'] ?? '' ) : '';
		if ( ! is_wp_error( $commerce ) && isset( $commerce['products'] ) && is_array( $commerce['products'] ) ) {
			$currency_codes_by_id = array();
			foreach ( (array) ( $commerce['currencies'] ?? array() ) as $currency ) {
				$currency_id = sanitize_key( (string) ( $currency['id'] ?? '' ) );
				$currency_code = strtoupper( sanitize_text_field( (string) ( $currency['code'] ?? '' ) ) );
				if ( '' !== $currency_id && '' !== $currency_code ) {
					$currency_codes_by_id[ $currency_id ] = $currency_code;
				}
			}
			$offers_by_sku = array();
			foreach ( (array) ( $commerce['channel_offers'] ?? array() ) as $offer ) {
				$sku_id = sanitize_key( (string) ( $offer['sku_id'] ?? '' ) );
				if ( '' !== $sku_id ) {
					$offers_by_sku[ $sku_id ][] = $offer;
				}
			}
			$observations_by_sku = array();
			$observation_records_by_sku = array();
			foreach ( (array) ( $commerce['market_observations'] ?? array() ) as $observation ) {
				$sku_id = isset( $observation['sku_id'] ) ? sanitize_key( (string) $observation['sku_id'] ) : '';
				if ( '' !== $sku_id && isset( $observation['id'] ) ) {
					$observations_by_sku[ $sku_id ][] = sanitize_key( (string) $observation['id'] );
					$observation_records_by_sku[ $sku_id ][] = $observation;
				}
			}
			$skus_by_product = array();
			$variants_by_product = array();
			$variant_product = array();
			foreach ( (array) ( $commerce['variants'] ?? array() ) as $variant ) {
				$product_id = (string) ( $variant['product_id'] ?? '' );
				$variant_product[ (string) ( $variant['id'] ?? '' ) ] = $product_id;
				if ( '' !== $product_id ) {
					$variants_by_product[ $product_id ][] = $variant;
				}
			}
			foreach ( (array) ( $commerce['skus'] ?? array() ) as $sku ) {
				$product_id = isset( $variant_product[ (string) ( $sku['variant_id'] ?? '' ) ] ) ? $variant_product[ (string) $sku['variant_id'] ] : '';
				if ( '' !== $product_id ) {
					$skus_by_product[ $product_id ][] = $sku;
				}
			}

			foreach ( $commerce['products'] as $product ) {
				$id = isset( $product['id'] ) ? sanitize_key( (string) $product['id'] ) : '';
				if ( '' === $id ) {
					continue;
				}
				$knowledge_entity_id = isset( $product['knowledge_entity_id'] ) ? sanitize_key( (string) $product['knowledge_entity_id'] ) : '';
				$taxonomy_ids = array_values( array_map( 'sanitize_key', (array) ( $product['taxonomy_ids'] ?? array() ) ) );
				$offer_type_hint = 0 === strpos( $knowledge_entity_id, 'equipment-' ) || in_array( 'professional-equipment', $taxonomy_ids, true ) ? 'equipment' : 'ingredient';
				$observation_ids = array();
				$observation_records = array();
				$product_offer_records = array();
				$planning_offer_ids = array();
				$planning_price_minor = null;
				$planning_currency_code = '';
				$woo_product_code = '';
				foreach ( (array) ( $skus_by_product[ $id ] ?? array() ) as $sku ) {
					$sku_id = sanitize_key( (string) ( $sku['id'] ?? '' ) );
					$observation_ids = array_merge( $observation_ids, (array) ( $observations_by_sku[ $sku_id ] ?? array() ) );
					$observation_records = array_merge( $observation_records, (array) ( $observation_records_by_sku[ $sku_id ] ?? array() ) );
					$product_offer_records = array_merge( $product_offer_records, (array) ( $offers_by_sku[ $sku_id ] ?? array() ) );
					foreach ( (array) ( $offers_by_sku[ $sku_id ] ?? array() ) as $offer ) {
						if ( 'draft' !== (string) ( $offer['state'] ?? '' )
							|| 'market-il-launch' !== (string) ( $offer['market_id'] ?? '' )
							|| 'channel-woo-web-il' !== (string) ( $offer['channel_id'] ?? '' )
							|| ! is_int( $offer['price_minor'] ?? null )
							|| $offer['price_minor'] <= 0 ) {
							continue;
						}
						$offer_currency_code = (string) ( $currency_codes_by_id[ (string) ( $offer['currency_id'] ?? '' ) ] ?? '' );
						if ( null !== $planning_price_minor && ( $planning_price_minor !== $offer['price_minor'] || $planning_currency_code !== $offer_currency_code ) ) {
							throw new RuntimeException( 'entity-studio-ambiguous-planning-price.' . $id );
						}
						$planning_price_minor = (int) $offer['price_minor'];
						$planning_currency_code = $offer_currency_code;
						$planning_offer_ids[] = sanitize_key( (string) ( $offer['id'] ?? '' ) );
					}
					if ( '' === $woo_product_code && ! empty( $sku['woo_product_code'] ) ) {
						$woo_product_code = sanitize_key( (string) $sku['woo_product_code'] );
					}
				}
				$commerce_product_base = self::scoped_base_registry(
					$commerce_schema,
					$commerce_version,
					'product',
					$id,
					array(
						'product'      => $product,
						'variants'     => array_values( (array) ( $variants_by_product[ $id ] ?? array() ) ),
						'skus'         => array_values( (array) ( $skus_by_product[ $id ] ?? array() ) ),
						'observations' => array_values( $observation_records ),
						'channel_offers' => array_values( $product_offer_records ),
					)
				);

				if ( isset( $subjects[ $id ] ) ) {
					if ( 'product' !== (string) $subjects[ $id ]['subject_type'] ) {
						throw new RuntimeException( 'entity-studio-subject-identity-collision.' . $id );
					}
					$subjects[ $id ]['workspace_identity'] = (string) $subjects[ $id ]['workspace_identity'] . '|commerce:product:' . $id;
					$subjects[ $id ]['base_registry'] = self::combined_base_registry( array( $subjects[ $id ]['base_registry'], $commerce_product_base ) );
					$subjects[ $id ]['commerce_state'] = sanitize_key( (string) ( $product['state'] ?? '' ) );
					$subjects[ $id ]['offer_type_hint'] = $offer_type_hint;
					$subjects[ $id ]['market_observation_ids'] = array_values( array_unique( $observation_ids ) );
					$subjects[ $id ]['planning_price_minor'] = $planning_price_minor;
					$subjects[ $id ]['planning_currency_code'] = $planning_currency_code;
					$subjects[ $id ]['planning_offer_ids'] = array_values( array_unique( array_filter( $planning_offer_ids ) ) );
					continue;
				}
				$current_price_minor = isset( $prices[ $woo_product_code ] ) ? self::price_to_minor( $prices[ $woo_product_code ] ) : null;
				$subjects[ $id ] = array(
					'id'                    => $id,
					'workspace_identity'    => 'commerce:product:' . $id,
					'subject_type'          => 'product',
					'domain'                => 'commerce',
					'record_kind'           => 'product',
					'base_registry'         => $commerce_product_base,
					'label'                 => isset( $product['name'] ) ? $product['name'] : array( 'he' => $id, 'en' => $id ),
					'entity_type'           => 'commerce_product',
					'related_entity_id'     => $knowledge_entity_id,
					'woo_product_code'      => $woo_product_code,
					'current_price_minor'   => $current_price_minor,
					'current_price_scope'   => null === $current_price_minor ? '' : (string) ( $price_bundle['price_scope'] ?? '' ),
					'planning_price_minor'  => $planning_price_minor,
					'planning_currency_code'=> $planning_currency_code,
					'planning_offer_ids'    => array_values( array_unique( array_filter( $planning_offer_ids ) ) ),
					'commerce_state'        => isset( $product['state'] ) ? sanitize_key( (string) $product['state'] ) : '',
					'knowledge_entity_id'   => $knowledge_entity_id,
					'offer_type_hint'        => $offer_type_hint,
					'market_observation_ids'=> array_values( array_unique( $observation_ids ) ),
				);
			}
		}

		$woo_ids = array();
		if ( class_exists( 'Complete99_Live_Catalog', false ) ) {
			$status = Complete99_Live_Catalog::status( false );
			if ( is_array( $status ) && ! empty( $status['ready'] ) && isset( $status['product_ids'] ) && is_array( $status['product_ids'] ) ) {
				$woo_ids = $status['product_ids'];
			}
		}
		foreach ( $subjects as &$subject ) {
			$code = (string) $subject['woo_product_code'];
			$subject['woo_product_id'] = isset( $woo_ids[ $code ] ) ? absint( $woo_ids[ $code ] ) : 0;
			$subject['woo_price_minor'] = null;
			$subject['woo_stock_status'] = '';
			$subject['woo_purchasable'] = false;
			if ( $subject['woo_product_id'] && function_exists( 'wc_get_product' ) ) {
				$woo_product = wc_get_product( $subject['woo_product_id'] );
				if ( $woo_product ) {
					$subject['woo_price_minor'] = self::price_to_minor( $woo_product->get_price() );
					$subject['woo_stock_status'] = sanitize_key( (string) $woo_product->get_stock_status() );
					$subject['woo_purchasable'] = (bool) $woo_product->is_purchasable();
				}
			}
			$subject['price_authority_state'] = 'not_priceable';
			if ( 'product' === $subject['subject_type'] ) {
				$subject['price_authority_state'] = null === $subject['current_price_minor']
					? ( null === $subject['planning_price_minor'] ? 'price_not_recorded' : 'private_draft_plan' )
					: 'owner_authorized_registry';
				if ( null !== $subject['woo_price_minor'] ) {
					$subject['price_authority_state'] = $subject['woo_price_minor'] === $subject['current_price_minor'] ? 'woocommerce_matched' : 'woocommerce_mismatch';
				}
			}
		}
		unset( $subject );

		uasort(
			$subjects,
			static function ( $left, $right ) {
				$type = strcmp( (string) $left['subject_type'], (string) $right['subject_type'] );
				return 0 !== $type ? $type : strcmp( (string) $left['id'], (string) $right['id'] );
			}
		);

		self::$subject_cache = $subjects;
		return self::$subject_cache;
	}

	/**
	 * Return allowed market, channel, currency and observation identities.
	 */
	public static function reference_index( $fresh = false ) {
		if ( ! $fresh && is_array( self::$reference_cache ) ) {
			return self::$reference_cache;
		}

		$commerce = class_exists( 'Complete99_Culinary_Commerce', false )
			? Complete99_Culinary_Commerce::registry( $fresh )
			: array();
		$references = array(
			'markets'      => array(),
			'channels'     => array(),
			'currencies'   => array(),
			'observations' => array(),
		);
		if ( ! is_wp_error( $commerce ) && is_array( $commerce ) ) {
			$variant_products = array();
			foreach ( (array) ( $commerce['variants'] ?? array() ) as $variant ) {
				$variant_id = sanitize_key( (string) ( $variant['id'] ?? '' ) );
				$product_id = sanitize_key( (string) ( $variant['product_id'] ?? '' ) );
				if ( '' !== $variant_id && '' !== $product_id ) {
					$variant_products[ $variant_id ] = $product_id;
				}
			}
			$sku_products = array();
			foreach ( (array) ( $commerce['skus'] ?? array() ) as $sku ) {
				$sku_id = sanitize_key( (string) ( $sku['id'] ?? '' ) );
				$variant_id = sanitize_key( (string) ( $sku['variant_id'] ?? '' ) );
				if ( '' !== $sku_id && isset( $variant_products[ $variant_id ] ) ) {
					$sku_products[ $sku_id ] = $variant_products[ $variant_id ];
				}
			}
			foreach ( (array) ( $commerce['markets'] ?? array() ) as $record ) {
				$id = sanitize_key( (string) ( $record['id'] ?? '' ) );
				if ( '' !== $id ) {
					$references['markets'][ $id ] = isset( $record['label'] ) ? $record['label'] : array( 'he' => $id, 'en' => $id );
				}
			}
			foreach ( (array) ( $commerce['channels'] ?? array() ) as $record ) {
				$id = sanitize_key( (string) ( $record['id'] ?? '' ) );
				if ( '' !== $id ) {
					$references['channels'][ $id ] = isset( $record['label'] ) ? $record['label'] : array( 'he' => $id, 'en' => $id );
				}
			}
			foreach ( (array) ( $commerce['currencies'] ?? array() ) as $record ) {
				$code = strtoupper( sanitize_text_field( (string) ( $record['code'] ?? '' ) ) );
				if ( 1 === preg_match( '/\A[A-Z]{3}\z/', $code ) ) {
					$references['currencies'][ $code ] = array(
						'code'              => $code,
						'minor_unit_digits' => isset( $record['minor_unit_digits'] ) ? (int) $record['minor_unit_digits'] : 2,
					);
				}
			}
			foreach ( (array) ( $commerce['market_observations'] ?? array() ) as $record ) {
				$id = sanitize_key( (string) ( $record['id'] ?? '' ) );
				if ( '' !== $id ) {
					$sku_id = sanitize_key( (string) ( $record['sku_id'] ?? '' ) );
					self::add_observation_reference( $references, $id, array(
						'id'          => $id,
						'source_type' => 'commerce_market_observation',
						'subject_ids' => isset( $sku_products[ $sku_id ] ) ? array( $sku_products[ $sku_id ] ) : array(),
						'market_id'   => sanitize_key( (string) ( $record['market_id'] ?? '' ) ),
						'currency_id' => sanitize_key( (string) ( $record['currency_id'] ?? '' ) ),
						'state'       => sanitize_key( (string) ( $record['state'] ?? '' ) ),
						'record'      => $record,
					) );
				}
			}
		}
		$product_bundle = self::load_data_file( 'catalog-product-seeds.php' );
		foreach ( (array) ( $product_bundle['products'] ?? array() ) as $product ) {
			$id = self::catalog_observation_id( $product );
			if ( '' !== $id ) {
				self::add_observation_reference( $references, $id, array(
					'id'          => $id,
					'source_type' => 'catalog_market_observation',
					'subject_ids' => array( sanitize_key( (string) ( $product['product_code'] ?? '' ) ) ),
					'market_id'   => 'market-il-launch',
					'currency_id' => 'currency-ils',
					'state'       => 'recorded',
					'record'      => isset( $product['market_observation'] ) && is_array( $product['market_observation'] ) ? $product['market_observation'] : array(),
				) );
			}
		}
		$science = class_exists( 'Complete99_Culinary_Science', false )
			? Complete99_Culinary_Science::registry( $fresh )
			: array();
		if ( ! is_wp_error( $science ) && isset( $science['entities'] ) && is_array( $science['entities'] ) ) {
			foreach ( $science['entities'] as $entity ) {
				$type = sanitize_key( (string) ( $entity['type'] ?? '' ) );
				$id = sanitize_key( (string) ( $entity['id'] ?? '' ) );
				if ( '' !== $id && in_array( $type, array( 'retail_listing', 'market_observation' ), true ) ) {
					self::add_observation_reference( $references, $id, array(
						'id'          => $id,
						'source_type' => 'science_observation_entity',
						'subject_ids' => array(),
						'market_id'   => '',
						'currency_id' => '',
						'state'       => sanitize_key( (string) ( $entity['review']['evidence_state'] ?? 'source_reviewed' ) ),
						'record'      => $entity,
					) );
				}
			}
		}

		if ( empty( $references['currencies'] ) ) {
			$references['currencies']['ILS'] = array( 'code' => 'ILS', 'minor_unit_digits' => 2 );
		}
		self::$reference_cache = $references;
		return self::$reference_cache;
	}

	private static function error( $code, $message, $status = 400 ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	private static function clean_text( $value, $maximum ) {
		$value = trim( wp_strip_all_tags( (string) $value, true ) );
		if ( false !== strpos( $value, "\xE2\x80\x94" ) ) {
			return null;
		}
		if ( function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) > $maximum : strlen( $value ) > $maximum ) {
			return null;
		}
		return $value;
	}

	private static function clean_id_list( $value, $maximum ) {
		if ( ! is_array( $value ) || count( $value ) > $maximum ) {
			return null;
		}
		$clean = array();
		foreach ( $value as $item ) {
			$item = sanitize_key( (string) $item );
			if ( '' === $item || isset( $clean[ $item ] ) ) {
				return null;
			}
			$clean[ $item ] = $item;
		}
		return array_values( $clean );
	}

	/**
	 * Normalize and validate only fields an administrator or API client edits.
	 *
	 * @return array|WP_Error
	 */
	public static function normalize_editable_record( $payload, $subjects = null, $references = null ) {
		if ( ! is_array( $payload ) ) {
			return self::error( 'complete99_entity_studio_invalid_payload', 'The dossier payload must be an object.' );
		}
		$allowed_keys = array(
			'subject_id',
			'workflow_state',
			'pricing_applicability',
			'commercial_role',
			'offer_type',
			'market_id',
			'channel_id',
			'currency_code',
			'planned_price_minor',
			'pricing_state',
			'quality_tier',
			'source_observation_ids',
			'cross_sell_subject_ids',
			'upsell_subject_ids',
			'value_proposition',
			'price_rationale',
			'private_note',
		);
		$payload_keys = array_keys( $payload );
		$expected_keys = $allowed_keys;
		sort( $payload_keys, SORT_STRING );
		sort( $expected_keys, SORT_STRING );
		if ( $payload_keys !== $expected_keys ) {
			return self::error( 'complete99_entity_studio_unknown_fields', 'The dossier fields do not match the versioned contract.' );
		}

		$subjects = is_array( $subjects ) ? $subjects : self::subject_index();
		$references = is_array( $references ) ? $references : self::reference_index();
		$subject_id = sanitize_key( (string) $payload['subject_id'] );
		if ( '' === $subject_id || ! isset( $subjects[ $subject_id ] ) ) {
			return self::error( 'complete99_entity_studio_unknown_subject', 'The dossier subject is not present in the Complete99 graph.' );
		}

		$enums = array(
			'workflow_state'       => array( 'draft', 'in_review', 'approved' ),
			'pricing_applicability'=> array( 'not_priceable', 'priceable' ),
			'commercial_role'      => array( 'knowledge', 'discovery', 'conversion', 'replenishment', 'bundle', 'experience' ),
			'offer_type'           => array( 'none', 'ingredient', 'equipment', 'bundle', 'service' ),
			'pricing_state'        => array( 'not_applicable', 'research', 'market_benchmarked', 'owner_authorized_planned' ),
			'quality_tier'         => array( 'not_applicable', 'standard', 'premium', 'reserve', 'professional' ),
		);
		$clean = array( 'subject_id' => $subject_id );
		foreach ( $enums as $key => $allowed ) {
			$value = sanitize_key( (string) $payload[ $key ] );
			if ( ! in_array( $value, $allowed, true ) ) {
				return self::error( 'complete99_entity_studio_invalid_' . $key, 'The dossier contains an unsupported controlled value.' );
			}
			$clean[ $key ] = $value;
		}

		$clean['market_id'] = sanitize_key( (string) $payload['market_id'] );
		$clean['channel_id'] = sanitize_key( (string) $payload['channel_id'] );
		$clean['currency_code'] = strtoupper( sanitize_text_field( (string) $payload['currency_code'] ) );
		$clean['planned_price_minor'] = is_int( $payload['planned_price_minor'] ) ? $payload['planned_price_minor'] : ( ctype_digit( (string) $payload['planned_price_minor'] ) ? (int) $payload['planned_price_minor'] : -1 );
		if ( $clean['planned_price_minor'] < 0 || $clean['planned_price_minor'] > 1000000000 ) {
			return self::error( 'complete99_entity_studio_invalid_price', 'The planned price must use non-negative integer minor units.' );
		}

		$clean['source_observation_ids'] = self::clean_id_list( $payload['source_observation_ids'], self::MAX_OBSERVATIONS );
		$clean['cross_sell_subject_ids'] = self::clean_id_list( $payload['cross_sell_subject_ids'], self::MAX_RELATIONS );
		$clean['upsell_subject_ids'] = self::clean_id_list( $payload['upsell_subject_ids'], self::MAX_RELATIONS );
		if ( null === $clean['source_observation_ids'] || null === $clean['cross_sell_subject_ids'] || null === $clean['upsell_subject_ids'] ) {
			return self::error( 'complete99_entity_studio_invalid_relations', 'Dossier references must be unique bounded identifiers.' );
		}
		foreach ( $clean['source_observation_ids'] as $id ) {
			if ( ! isset( $references['observations'][ $id ] ) ) {
				return self::error( 'complete99_entity_studio_unknown_observation', 'A source observation does not exist in the commerce graph.' );
			}
			$observation = $references['observations'][ $id ];
			if ( in_array( (string) ( $observation['state'] ?? '' ), array( 'invalidated', 'superseded' ), true ) ) {
				return self::error( 'complete99_entity_studio_inactive_observation', 'An invalidated or superseded observation cannot support a price plan.' );
			}
			if ( 'product' === $subjects[ $subject_id ]['subject_type'] && ! in_array( $subject_id, (array) ( $observation['subject_ids'] ?? array() ), true ) ) {
				return self::error( 'complete99_entity_studio_observation_subject_mismatch', 'A product price plan may cite only an observation bound to that product identity.' );
			}
		}
		foreach ( array( 'cross_sell_subject_ids', 'upsell_subject_ids' ) as $key ) {
			foreach ( $clean[ $key ] as $target_id ) {
				if ( $target_id === $subject_id || ! isset( $subjects[ $target_id ] ) ) {
					return self::error( 'complete99_entity_studio_invalid_target', 'A commercial relation target is missing or self-referential.' );
				}
			}
		}
		if ( array_intersect( $clean['cross_sell_subject_ids'], $clean['upsell_subject_ids'] ) ) {
			return self::error( 'complete99_entity_studio_ambiguous_relation', 'One target cannot be both a cross-sell and an up-sell in the same dossier revision.' );
		}

		foreach ( array( 'value_proposition', 'price_rationale' ) as $key ) {
			$localized_keys = is_array( $payload[ $key ] ) ? array_keys( $payload[ $key ] ) : array();
			sort( $localized_keys, SORT_STRING );
			if ( ! is_array( $payload[ $key ] ) || $localized_keys !== array( 'en', 'he' ) ) {
				return self::error( 'complete99_entity_studio_invalid_bilingual_field', 'Bilingual dossier fields require exact Hebrew and English values.' );
			}
			$he = self::clean_text( $payload[ $key ]['he'], 1200 );
			$en = self::clean_text( $payload[ $key ]['en'], 1200 );
			if ( null === $he || null === $en ) {
				return self::error( 'complete99_entity_studio_invalid_text', 'Dossier text is too long or contains a forbidden em dash.' );
			}
			$clean[ $key ] = array( 'he' => $he, 'en' => $en );
		}
		$clean['private_note'] = self::clean_text( $payload['private_note'], 3000 );
		if ( null === $clean['private_note'] ) {
			return self::error( 'complete99_entity_studio_invalid_note', 'The private note is too long or contains a forbidden em dash.' );
		}

		if ( 'not_priceable' === $clean['pricing_applicability'] ) {
			if ( 0 !== $clean['planned_price_minor'] || 'not_applicable' !== $clean['pricing_state'] || 'none' !== $clean['offer_type'] || '' !== $clean['market_id'] || '' !== $clean['channel_id'] || '' !== $clean['currency_code'] ) {
				return self::error( 'complete99_entity_studio_nonpriceable_conflict', 'A non-priceable entity cannot carry an offer or planned price.' );
			}
		} else {
			if ( 'product' !== $subjects[ $subject_id ]['subject_type'] ) {
				return self::error( 'complete99_entity_studio_product_required', 'Only a product subject may carry a planned sell price. Link a knowledge entity to a product instead.' );
			}
			if ( 'none' === $clean['offer_type'] || ! isset( $references['markets'][ $clean['market_id'] ] ) || ! isset( $references['channels'][ $clean['channel_id'] ] ) || ! isset( $references['currencies'][ $clean['currency_code'] ] ) ) {
				return self::error( 'complete99_entity_studio_offer_context_required', 'A priceable product requires a known market, channel, currency and offer type.' );
			}
			if ( in_array( $clean['pricing_state'], array( 'market_benchmarked', 'owner_authorized_planned' ), true ) && empty( $clean['source_observation_ids'] ) ) {
				return self::error( 'complete99_entity_studio_price_evidence_required', 'A benchmarked or owner-authorized plan requires at least one dated market observation.' );
			}
			if ( 'owner_authorized_planned' === $clean['pricing_state'] && 0 === $clean['planned_price_minor'] ) {
				return self::error( 'complete99_entity_studio_price_required', 'An owner-authorized price plan requires a positive planned price.' );
			}
			if ( 'not_applicable' === $clean['pricing_state'] ) {
				return self::error( 'complete99_entity_studio_price_state_conflict', 'A priceable product cannot use the not-applicable pricing state.' );
			}
		}

		if ( 'approved' === $clean['workflow_state'] ) {
			if ( '' === $clean['value_proposition']['he'] || '' === $clean['value_proposition']['en'] ) {
				return self::error( 'complete99_entity_studio_value_required', 'Approval requires a bilingual value proposition.' );
			}
			if ( 'priceable' === $clean['pricing_applicability'] && ( 'owner_authorized_planned' !== $clean['pricing_state'] || 0 === $clean['planned_price_minor'] || '' === $clean['price_rationale']['he'] || '' === $clean['price_rationale']['en'] ) ) {
				return self::error( 'complete99_entity_studio_approval_price_required', 'Approval of a priceable dossier requires an owner-authorized plan and bilingual price rationale.' );
			}
		}

		return $clean;
	}

	private static function default_editable_record( $subject ) {
		$is_product = 'product' === (string) $subject['subject_type'];
		$planned_price_minor = $is_product
			? ( null !== $subject['current_price_minor'] ? (int) $subject['current_price_minor'] : (int) ( $subject['planning_price_minor'] ?? 0 ) )
			: 0;
		$has_authorized_plan = $is_product && $planned_price_minor > 0;
		return array(
			'subject_id'              => (string) $subject['id'],
			'workflow_state'          => 'draft',
			'pricing_applicability'   => $is_product ? 'priceable' : 'not_priceable',
			'commercial_role'         => $is_product ? 'conversion' : 'knowledge',
			'offer_type'              => $is_product ? (string) ( $subject['offer_type_hint'] ?? 'ingredient' ) : 'none',
			'market_id'               => $is_product ? 'market-il-launch' : '',
			'channel_id'              => $is_product ? 'channel-woo-web-il' : '',
			'currency_code'           => $is_product ? ( (string) ( $subject['planning_currency_code'] ?? '' ) ?: 'ILS' ) : '',
			'planned_price_minor'     => $planned_price_minor,
			'pricing_state'           => $is_product ? ( $has_authorized_plan ? 'owner_authorized_planned' : 'research' ) : 'not_applicable',
			'quality_tier'            => $is_product ? 'standard' : 'not_applicable',
			'source_observation_ids'  => array_values( (array) $subject['market_observation_ids'] ),
			'cross_sell_subject_ids'  => array(),
			'upsell_subject_ids'      => array(),
			'value_proposition'       => array( 'he' => '', 'en' => '' ),
			'price_rationale'         => array( 'he' => '', 'en' => '' ),
			'private_note'            => '',
		);
	}

	private static function record_post( $subject_id ) {
		$post = get_page_by_path( sanitize_title( (string) $subject_id ), OBJECT, self::POST_TYPE );
		return $post instanceof WP_Post ? $post : null;
	}

	private static function is_digest( $value, $allow_empty = false ) {
		$value = (string) $value;
		return ( $allow_empty && '' === $value ) || 1 === preg_match( '/\A[a-f0-9]{64}\z/', $value );
	}

	private static function workflow_transition_allowed( $from, $to ) {
		$matrix = array(
			''          => array( 'draft' ),
			'draft'     => array( 'draft', 'in_review' ),
			'in_review' => array( 'draft', 'in_review', 'approved' ),
			'approved'  => array( 'draft', 'approved' ),
		);
		return isset( $matrix[ $from ] ) && in_array( $to, $matrix[ $from ], true );
	}

	private static function changed_field_paths( $before, $after, $prefix = '' ) {
		if ( ! is_array( $before ) || ! is_array( $after ) ) {
			return $before === $after ? array() : array( '' === $prefix ? '/' : $prefix );
		}
		$keys = array_values( array_unique( array_merge( array_keys( $before ), array_keys( $after ) ) ) );
		usort( $keys, static function ( $left, $right ) { return strcmp( (string) $left, (string) $right ); } );
		$paths = array();
		foreach ( $keys as $key ) {
			$path = $prefix . '/' . str_replace( array( '~', '/' ), array( '~0', '~1' ), (string) $key );
			if ( ! array_key_exists( $key, $before ) || ! array_key_exists( $key, $after ) ) {
				$paths[] = $path;
				continue;
			}
			$paths = array_merge( $paths, self::changed_field_paths( $before[ $key ], $after[ $key ], $path ) );
		}
		return $paths;
	}

	private static function validated_record_array( $record ) {
		$record_keys = array( 'schema', 'domain', 'record_kind', 'stable_id', 'base_registry', 'workflow', 'payload', 'payload_digest', 'updated_at', 'updated_by', 'event' );
		$actual_keys = is_array( $record ) ? array_keys( $record ) : array();
		sort( $record_keys, SORT_STRING );
		sort( $actual_keys, SORT_STRING );
		if ( ! is_array( $record )
			|| $record_keys !== $actual_keys
			|| self::RECORD_SCHEMA !== ( $record['schema'] ?? '' )
			|| '' === (string) ( $record['stable_id'] ?? '' )
			|| '' === (string) ( $record['domain'] ?? '' )
			|| '' === (string) ( $record['record_kind'] ?? '' )
			|| sanitize_key( (string) ( $record['stable_id'] ?? '' ) ) !== ( $record['stable_id'] ?? '' )
			|| sanitize_key( (string) ( $record['domain'] ?? '' ) ) !== ( $record['domain'] ?? '' )
			|| sanitize_key( (string) ( $record['record_kind'] ?? '' ) ) !== ( $record['record_kind'] ?? '' )
			|| ! is_array( $record['payload'] ?? null )
			|| ! is_array( $record['base_registry'] ?? null )
			|| ! is_array( $record['workflow'] ?? null )
			|| ! is_array( $record['event'] ?? null ) ) {
			return array();
		}
		$base_keys = array_keys( $record['base_registry'] );
		$expected_base_keys = array( 'schema', 'version', 'digest' );
		sort( $base_keys, SORT_STRING );
		sort( $expected_base_keys, SORT_STRING );
		$workflow_keys = array_keys( $record['workflow'] );
		$expected_workflow_keys = array( 'state', 'revision' );
		sort( $workflow_keys, SORT_STRING );
		sort( $expected_workflow_keys, SORT_STRING );
		if ( $base_keys !== $expected_base_keys
			|| $workflow_keys !== $expected_workflow_keys
			|| '' === trim( (string) ( $record['base_registry']['schema'] ?? '' ) )
			|| '' === trim( (string) ( $record['base_registry']['version'] ?? '' ) )
			|| ! self::is_digest( $record['base_registry']['digest'] ?? '' )
			|| ! in_array( $record['workflow']['state'] ?? '', array( 'draft', 'in_review', 'approved' ), true )
			|| ! is_int( $record['workflow']['revision'] ?? null )
			|| ( $record['workflow']['revision'] ?? 0 ) < 1
			|| ! is_int( $record['updated_by'] ?? null )
			|| ( $record['updated_by'] ?? -1 ) < 0
			|| 1 !== preg_match( '/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/', (string) ( $record['updated_at'] ?? '' ) )
			|| ! self::is_digest( $record['payload_digest'] ?? '' )
			|| ! hash_equals( (string) $record['payload_digest'], self::canonical_digest( $record['payload'] ) ) ) {
			return array();
		}
		$event_keys = array_keys( $record['event'] );
		$expected_event_keys = array( 'event_id', 'source', 'change_reason', 'correlation_id', 'actor_id', 'actor_type', 'occurred_at', 'prior_revision', 'new_revision', 'workflow_transition', 'changed_field_paths', 'prior_record_digest', 'prior_event_digest', 'resulting_payload_digest', 'event_digest' );
		sort( $event_keys, SORT_STRING );
		sort( $expected_event_keys, SORT_STRING );
		$event = $record['event'];
		$event_digest = (string) ( $event['event_digest'] ?? '' );
		unset( $event['event_digest'] );
		if ( $event_keys !== $expected_event_keys
			|| ! self::is_digest( $event_digest )
			|| ! hash_equals( $event_digest, self::canonical_digest( $event ) )
			|| ! self::is_digest( $event['prior_record_digest'] ?? '', true )
			|| ! self::is_digest( $event['prior_event_digest'] ?? '', true )
			|| ! hash_equals( (string) ( $event['resulting_payload_digest'] ?? '' ), (string) $record['payload_digest'] )
			|| (int) ( $event['actor_id'] ?? -1 ) !== (int) $record['updated_by']
			|| 'wordpress_user' !== (string) ( $event['actor_type'] ?? '' )
			|| (string) ( $event['occurred_at'] ?? '' ) !== (string) $record['updated_at']
			|| ! is_int( $event['prior_revision'] ?? null )
			|| ! is_int( $event['new_revision'] ?? null )
			|| (int) ( $event['new_revision'] ?? 0 ) !== (int) $record['workflow']['revision']
			|| (int) ( $event['prior_revision'] ?? -1 ) + 1 !== (int) $record['workflow']['revision']
			|| ! is_array( $event['workflow_transition'] ?? null )
			|| array_keys( $event['workflow_transition'] ) !== array( 'from', 'to' )
			|| (string) ( $event['workflow_transition']['to'] ?? '' ) !== (string) $record['workflow']['state']
			|| ! self::workflow_transition_allowed( (string) ( $event['workflow_transition']['from'] ?? '' ), (string) ( $event['workflow_transition']['to'] ?? '' ) )
			|| ! is_array( $event['changed_field_paths'] ?? null ) ) {
			return array();
		}
		return $record;
	}

	private static function persisted_payload_is_valid( $record ) {
		$stable_id = (string) $record['stable_id'];
		$subject_type = 'product' === (string) $record['record_kind'] ? 'product' : 'entity';
		$subjects = array(
			$stable_id => array( 'subject_type' => $subject_type ),
		);
		foreach ( array_merge( (array) ( $record['payload']['cross_sell_subject_ids'] ?? array() ), (array) ( $record['payload']['upsell_subject_ids'] ?? array() ) ) as $target_id ) {
			$subjects[ (string) $target_id ] = array( 'subject_type' => 'product' );
		}
		$references = array(
			'markets'      => array(),
			'channels'     => array(),
			'currencies'   => array(),
			'observations' => array(),
		);
		foreach ( array( 'market_id' => 'markets', 'channel_id' => 'channels' ) as $payload_key => $reference_key ) {
			$id = (string) ( $record['payload'][ $payload_key ] ?? '' );
			if ( '' !== $id ) {
				$references[ $reference_key ][ $id ] = true;
			}
		}
		$currency_code = (string) ( $record['payload']['currency_code'] ?? '' );
		if ( '' !== $currency_code ) {
			$references['currencies'][ $currency_code ] = array( 'code' => $currency_code, 'minor_unit_digits' => 2 );
		}
		foreach ( (array) ( $record['payload']['source_observation_ids'] ?? array() ) as $observation_id ) {
			$references['observations'][ (string) $observation_id ] = array( 'subject_ids' => array( $stable_id ), 'state' => 'recorded' );
		}
		$editable = array_merge(
			array(
				'subject_id'     => $stable_id,
				'workflow_state' => $record['workflow']['state'],
			),
			$record['payload']
		);
		$editable = self::normalize_editable_record( $editable, $subjects, $references );
		if ( is_wp_error( $editable ) ) {
			return false;
		}
		unset( $editable['subject_id'], $editable['workflow_state'] );
		return hash_equals( (string) $record['payload_digest'], self::canonical_digest( $editable ) );
	}

	private static function record_from_post( $post, $expected_subject_id = '' ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return array();
		}
		$record = self::validated_record_array( json_decode( (string) $post->post_content, true ) );
		if ( ! $record ) {
			return array();
		}
		$expected_subject_id = sanitize_key( (string) $expected_subject_id );
		$record_digest = self::canonical_digest( $record );
		if ( ( '' !== $expected_subject_id && $record['stable_id'] !== $expected_subject_id )
			|| sanitize_title( $record['stable_id'] ) !== (string) $post->post_name
			|| (string) get_post_meta( $post->ID, '_complete99_entity_subject_id', true ) !== (string) $record['stable_id']
			|| (int) get_post_meta( $post->ID, '_complete99_entity_revision', true ) !== (int) $record['workflow']['revision']
			|| ! hash_equals( $record_digest, (string) get_post_meta( $post->ID, '_complete99_entity_digest', true ) ) ) {
			return array();
		}
		$subjects = self::subject_index();
		if ( isset( $subjects[ $record['stable_id'] ] )
			&& (string) $subjects[ $record['stable_id'] ]['record_kind'] !== (string) $record['record_kind'] ) {
			return array();
		}
		if ( ! self::persisted_payload_is_valid( $record ) ) {
			return array();
		}
		$record['post_id'] = (int) $post->ID;
		return $record;
	}

	public static function record( $subject_id ) {
		if ( ! self::can_manage() ) {
			return array();
		}
		return self::record_from_post( self::record_post( $subject_id ), $subject_id );
	}

	private static function all_records() {
		$records = array();
		$page = 1;
		do {
			$posts = get_posts(
				array(
					'post_type'              => self::POST_TYPE,
					'post_status'            => 'private',
					'posts_per_page'         => self::RECORD_PAGE_SIZE,
					'paged'                  => $page,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'suppress_filters'       => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => false,
				)
			);
			foreach ( $posts as $post ) {
				$record = self::record_from_post( $post );
				if ( ! empty( $record['stable_id'] ) && ! isset( $records[ $record['stable_id'] ] ) ) {
					$records[ $record['stable_id'] ] = $record;
				}
			}
			$page++;
		} while ( count( $posts ) === self::RECORD_PAGE_SIZE );
		return $records;
	}

	private static function lock_name() {
		return 'c99-entity-studio-' . substr( hash( 'sha256', get_current_blog_id() . '|' . home_url( '/' ) ), 0, 36 );
	}

	private static function is_sqlite_database() {
		global $wpdb;
		$driver = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
		if ( 'sqlite' === $driver ) {
			return true;
		}
		$driver = defined( 'DATABASE_TYPE' ) ? strtolower( (string) DATABASE_TYPE ) : '';
		return 'sqlite' === $driver || ( is_object( $wpdb ) && false !== strpos( strtolower( get_class( $wpdb ) ), 'sqlite' ) );
	}

	private static function acquire_lock() {
		global $wpdb;
		if ( self::is_sqlite_database() ) {
			$path = trailingslashit( WP_CONTENT_DIR ) . '.complete99-entity-studio.lock';
			$handle = @fopen( $path, 'c+' );
			if ( false === $handle ) {
				return self::error( 'complete99_entity_studio_lock_unavailable', 'The Entity Studio write lock is unavailable.', 503 );
			}
			$deadline = microtime( true ) + self::LOCK_TIMEOUT;
			do {
				if ( @flock( $handle, LOCK_EX | LOCK_NB ) ) {
					self::$file_lock_handle = $handle;
					return true;
				}
				usleep( 100000 );
			} while ( microtime( true ) < $deadline );
			@fclose( $handle );
			return self::error( 'complete99_entity_studio_locked', 'Another Entity Studio write is in progress.', 409 );
		}
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return self::error( 'complete99_entity_studio_lock_unavailable', 'The Entity Studio write lock is unavailable.', 503 );
		}
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', self::lock_name(), self::LOCK_TIMEOUT ) );
		return 1 === (int) $locked ? true : self::error( 'complete99_entity_studio_locked', 'Another Entity Studio write is in progress.', 409 );
	}

	private static function release_lock() {
		global $wpdb;
		if ( is_resource( self::$file_lock_handle ) ) {
			@flock( self::$file_lock_handle, LOCK_UN );
			@fclose( self::$file_lock_handle );
			self::$file_lock_handle = null;
			return;
		}
		if ( is_object( $wpdb ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'get_var' ) ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lock_name() ) );
		}
	}

	private static function begin_transaction() {
		global $wpdb;
		return is_object( $wpdb ) && method_exists( $wpdb, 'query' ) && false !== $wpdb->query( 'START TRANSACTION' );
	}

	private static function commit_transaction() {
		global $wpdb;
		return is_object( $wpdb ) && method_exists( $wpdb, 'query' ) && false !== $wpdb->query( 'COMMIT' );
	}

	private static function rollback_transaction() {
		global $wpdb;
		if ( is_object( $wpdb ) && method_exists( $wpdb, 'query' ) ) {
			$wpdb->query( 'ROLLBACK' );
		}
	}

	private static function invalidate_write_cache( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}
		clean_post_cache( $post_id );
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $post_id, 'post_meta' );
		}
	}

	private static function canonical_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$is_list = empty( $value ) || array_keys( $value ) === range( 0, count( $value ) - 1 );
		if ( ! $is_list ) {
			ksort( $value, SORT_STRING );
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonical_value( $item );
		}
		return $value;
	}

	private static function canonical_digest( $record ) {
		return hash( 'sha256', wp_json_encode( self::canonical_value( $record ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Save one revision with optimistic concurrency and a MySQL advisory lock.
	 */
	public static function save_record( $payload, $expected_revision, $change_reason, $source = 'wordpress-admin', $correlation_id = '', $allow_rebase = false, $expected_base_digest = '' ) {
		if ( ! self::can_manage() ) {
			return self::error( 'complete99_entity_studio_forbidden', 'Entity Studio access is denied.', 403 );
		}
		$change_reason = self::clean_text( $change_reason, 500 );
		if ( null === $change_reason || '' === $change_reason ) {
			return self::error( 'complete99_entity_studio_reason_required', 'A concise change reason is required.' );
		}
		$source = sanitize_key( (string) $source );
		if ( '' === $source || strlen( $source ) > 50 ) {
			return self::error( 'complete99_entity_studio_invalid_source', 'A bounded event source is required.' );
		}
		$correlation_id = sanitize_key( (string) $correlation_id );
		if ( strlen( $correlation_id ) > 100 ) {
			return self::error( 'complete99_entity_studio_invalid_correlation', 'The correlation identifier is too long.' );
		}
		if ( '' === $correlation_id ) {
			$correlation_id = 'c99-es-' . wp_generate_uuid4();
		}

		$subjects = self::subject_index( true );
		$references = self::reference_index( true );
		$editable = self::normalize_editable_record( $payload, $subjects, $references );
		if ( is_wp_error( $editable ) ) {
			return $editable;
		}
		$expected_base_digest = strtolower( trim( (string) $expected_base_digest ) );
		$current_base_digest = (string) ( $subjects[ $editable['subject_id'] ]['base_registry']['digest'] ?? '' );
		if ( ! self::is_digest( $expected_base_digest ) || ! hash_equals( $current_base_digest, $expected_base_digest ) ) {
			return self::error( 'complete99_entity_studio_base_conflict', 'The source record changed after the dossier was loaded. Refresh before saving.', 409 );
		}
		if ( ! is_int( $expected_revision ) && ! ctype_digit( (string) $expected_revision ) ) {
			return self::error( 'complete99_entity_studio_invalid_expected_revision', 'The expected revision must be a non-negative integer.' );
		}
		$expected_revision = (int) $expected_revision;
		$locked = self::acquire_lock();
		if ( is_wp_error( $locked ) ) {
			return $locked;
		}

		$transaction_started = false;
		$affected_post_id = 0;
		try {
			$post = self::record_post( $editable['subject_id'] );
			$affected_post_id = $post instanceof WP_Post ? (int) $post->ID : 0;
			$prior = self::record_from_post( $post );
			if ( $post && ! $prior ) {
				return self::error( 'complete99_entity_studio_corrupt_prior', 'The existing dossier failed integrity checks and cannot be overwritten.', 409 );
			}
			$current_revision = isset( $prior['workflow']['revision'] ) ? absint( $prior['workflow']['revision'] ) : 0;
			if ( $current_revision !== $expected_revision ) {
				return self::error( 'complete99_entity_studio_revision_conflict', 'The dossier changed after it was loaded. Refresh before saving.', 409 );
			}

			$prior_for_digest = $prior;
			unset( $prior_for_digest['post_id'] );
			$prior_digest = empty( $prior_for_digest ) ? '' : self::canonical_digest( $prior_for_digest );
			$subject = $subjects[ $editable['subject_id'] ];
			$workflow_state = $editable['workflow_state'];
			unset( $editable['subject_id'], $editable['workflow_state'] );
			$payload_digest = self::canonical_digest( $editable );
			$prior_workflow_state = (string) ( $prior['workflow']['state'] ?? '' );
			if ( ! self::workflow_transition_allowed( $prior_workflow_state, $workflow_state ) ) {
				return self::error( 'complete99_entity_studio_invalid_transition', 'The workflow transition is not allowed. Submit a draft for review before approval.', 409 );
			}
			$base_is_stale = $prior && ! hash_equals( (string) ( $prior['base_registry']['digest'] ?? '' ), (string) ( $subject['base_registry']['digest'] ?? '' ) );
			if ( $base_is_stale && ! $allow_rebase ) {
				return self::error( 'complete99_entity_studio_stale_base', 'The source record changed. Explicitly rebase the dossier as a draft before saving another revision.', 409 );
			}
			if ( $base_is_stale && 'draft' !== $workflow_state ) {
				return self::error( 'complete99_entity_studio_rebase_requires_draft', 'A stale dossier may be rebased only into the draft workflow state.', 409 );
			}
			if ( 'approved' === $prior_workflow_state && 'approved' === $workflow_state && ! hash_equals( (string) ( $prior['payload_digest'] ?? '' ), $payload_digest ) ) {
				return self::error( 'complete99_entity_studio_approved_edit_requires_draft', 'Change an approved dossier back to draft before editing its payload.', 409 );
			}
			$occurred_at = gmdate( 'Y-m-d\TH:i:s\Z' );
			$actor_id = (int) get_current_user_id();
			$changed_field_paths = self::changed_field_paths( (array) ( $prior['payload'] ?? array() ), $editable, '/payload' );
			if ( $prior_workflow_state !== $workflow_state ) {
				$changed_field_paths[] = '/workflow/state';
			}
			if ( $base_is_stale ) {
				$changed_field_paths[] = '/base_registry';
			}
			$changed_field_paths = array_values( array_unique( $changed_field_paths ) );
			sort( $changed_field_paths, SORT_STRING );
			$event = array(
				'event_id'                => 'event-' . wp_generate_uuid4(),
				'source'                  => $source,
				'change_reason'           => $change_reason,
				'correlation_id'          => $correlation_id,
				'actor_id'                => $actor_id,
				'actor_type'              => 'wordpress_user',
				'occurred_at'             => $occurred_at,
				'prior_revision'          => $current_revision,
				'new_revision'            => $current_revision + 1,
				'workflow_transition'     => array(
					'from' => $prior_workflow_state,
					'to'   => $workflow_state,
				),
				'changed_field_paths'     => $changed_field_paths,
				'prior_record_digest'     => $prior_digest,
				'prior_event_digest'      => (string) ( $prior['event']['event_digest'] ?? '' ),
				'resulting_payload_digest'=> $payload_digest,
			);
			$event['event_digest'] = self::canonical_digest( $event );
			$record = array(
				'schema'         => self::RECORD_SCHEMA,
				'domain'         => (string) $subject['domain'],
				'record_kind'    => (string) $subject['record_kind'],
				'stable_id'      => (string) $subject['id'],
				'base_registry'  => $subject['base_registry'],
				'workflow'       => array(
					'state'    => $workflow_state,
					'revision' => $current_revision + 1,
				),
				'payload'        => $editable,
				'payload_digest' => $payload_digest,
				'updated_at'     => $occurred_at,
				'updated_by'     => $actor_id,
				'event'          => $event,
			);
			$encoded = wp_json_encode( $record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( false === $encoded ) {
				return self::error( 'complete99_entity_studio_encode_failed', 'The dossier could not be encoded.', 500 );
			}

			$label = self::local_text( $subject['label'] );
			$postarr = array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'private',
				'post_title'   => $label . ' [' . $subject['id'] . ']',
				'post_name'    => $subject['id'],
				'post_content' => wp_slash( $encoded ),
			);
			if ( ! self::begin_transaction() ) {
				return self::error( 'complete99_entity_studio_transaction_unavailable', 'A safe Entity Studio transaction could not be started.', 503 );
			}
			$transaction_started = true;
			if ( $post ) {
				$postarr['ID'] = (int) $post->ID;
				$post_id = wp_update_post( $postarr, true );
			} else {
				$post_id = wp_insert_post( $postarr, true );
			}
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				throw new RuntimeException( 'entity-studio-post-write' );
			}
			$affected_post_id = (int) $post_id;

			$digest = self::canonical_digest( $record );
			update_post_meta( $post_id, '_complete99_entity_subject_id', $subject['id'] );
			update_post_meta( $post_id, '_complete99_entity_revision', (int) $record['workflow']['revision'] );
			update_post_meta( $post_id, '_complete99_entity_digest', $digest );
			clean_post_cache( $post_id );
			if ( (string) get_post_meta( $post_id, '_complete99_entity_subject_id', true ) !== (string) $subject['id']
				|| (int) get_post_meta( $post_id, '_complete99_entity_revision', true ) !== (int) $record['workflow']['revision']
				|| ! hash_equals( $digest, (string) get_post_meta( $post_id, '_complete99_entity_digest', true ) ) ) {
				throw new RuntimeException( 'entity-studio-meta-readback' );
			}

			$readback = self::record_from_post( get_post( $post_id ) );
			$readback_for_digest = $readback;
			unset( $readback_for_digest['post_id'] );
			if ( (int) ( $readback['workflow']['revision'] ?? 0 ) !== (int) $record['workflow']['revision'] || ! hash_equals( $digest, self::canonical_digest( $readback_for_digest ) ) ) {
				throw new RuntimeException( 'entity-studio-record-readback' );
			}
			if ( ! self::commit_transaction() ) {
				throw new RuntimeException( 'entity-studio-commit' );
			}
			$transaction_started = false;
			return $readback;
		} catch ( Throwable $error ) {
			if ( $transaction_started ) {
				self::rollback_transaction();
				$transaction_started = false;
				self::invalidate_write_cache( $affected_post_id );
			}
			return self::error( 'complete99_entity_studio_write_rolled_back', 'The dossier write failed verification and was rolled back.', 500 );
		} finally {
			if ( $transaction_started ) {
				self::rollback_transaction();
				self::invalidate_write_cache( $affected_post_id );
			}
			self::release_lock();
		}
	}

	private static function history_bundle( $subject_id ) {
		$post = self::record_post( $subject_id );
		if ( ! $post ) {
			return array( 'state' => 'empty', 'records' => array(), 'reason' => '' );
		}
		$current = self::record_from_post( $post, $subject_id );
		if ( ! $current ) {
			return array( 'state' => 'corrupt', 'records' => array(), 'reason' => 'current_record' );
		}
		$records_by_revision = array( (int) $current['workflow']['revision'] => $current );
		$state = 'verified';
		$reason = '';
		$revisions = wp_get_post_revisions(
			$post->ID,
			array(
				'posts_per_page' => self::MAX_HISTORY,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);
		foreach ( $revisions as $revision ) {
			$record = self::validated_record_array( json_decode( (string) $revision->post_content, true ) );
			if ( ! $record
				|| ! self::persisted_payload_is_valid( $record )
				|| (string) $record['stable_id'] !== (string) $current['stable_id']
				|| (string) $record['record_kind'] !== (string) $current['record_kind'] ) {
				$state = 'corrupt';
				$reason = 'revision_record';
				continue;
			}
			$revision_number = (int) $record['workflow']['revision'];
			if ( isset( $records_by_revision[ $revision_number ] ) ) {
				$existing = $records_by_revision[ $revision_number ];
				unset( $existing['post_id'] );
				if ( ! hash_equals( self::canonical_digest( $existing ), self::canonical_digest( $record ) ) ) {
					$state = 'corrupt';
					$reason = 'revision_conflict';
				}
				continue;
			}
			$records_by_revision[ $revision_number ] = $record;
		}
		krsort( $records_by_revision, SORT_NUMERIC );
		foreach ( $records_by_revision as $revision_number => $record ) {
			$prior_revision = (int) $record['event']['prior_revision'];
			if ( 1 === $revision_number ) {
				if ( 0 !== $prior_revision || '' !== $record['event']['prior_record_digest'] || '' !== $record['event']['prior_event_digest'] ) {
					$state = 'corrupt';
					$reason = 'root_link';
				}
				continue;
			}
			if ( ! isset( $records_by_revision[ $revision_number - 1 ] ) ) {
				if ( 'corrupt' !== $state ) {
					$state = count( $revisions ) >= self::MAX_HISTORY ? 'truncated' : 'incomplete';
					$reason = 'missing_revision';
				}
				continue;
			}
			$prior = $records_by_revision[ $revision_number - 1 ];
			$prior_for_digest = $prior;
			unset( $prior_for_digest['post_id'] );
			if ( $prior_revision !== $revision_number - 1
				|| ! hash_equals( (string) $record['event']['prior_record_digest'], self::canonical_digest( $prior_for_digest ) )
				|| ! hash_equals( (string) $record['event']['prior_event_digest'], (string) $prior['event']['event_digest'] )
				|| (string) $record['event']['workflow_transition']['from'] !== (string) $prior['workflow']['state'] ) {
				$state = 'corrupt';
				$reason = 'chain_link';
			}
		}
		return array( 'state' => $state, 'records' => array_values( $records_by_revision ), 'reason' => $reason );
	}

	private static function history( $subject_id ) {
		$bundle = self::history_bundle( $subject_id );
		return $bundle['records'];
	}

	private static function scoped_snapshot_references( $references, $subjects, $records ) {
		$observation_ids = array();
		foreach ( $subjects as $subject ) {
			$observation_ids = array_merge( $observation_ids, (array) ( $subject['market_observation_ids'] ?? array() ) );
		}
		foreach ( $records as $record ) {
			$observation_ids = array_merge( $observation_ids, (array) ( $record['payload']['source_observation_ids'] ?? array() ) );
		}
		$observation_ids = array_values( array_unique( array_filter( array_map( 'sanitize_key', $observation_ids ) ) ) );
		$references['observations'] = array_intersect_key( $references['observations'], array_fill_keys( $observation_ids, true ) );
		return $references;
	}

	public static function snapshot( $subject_id = '', $page = 1, $per_page = self::REST_PAGE_SIZE ) {
		if ( ! self::can_manage() ) {
			return self::error( 'complete99_entity_studio_forbidden', 'Entity Studio access is denied.', 403 );
		}
		$subjects = self::subject_index();
		$references = self::reference_index();
		$subject_id = sanitize_key( (string) $subject_id );
		$page = max( 1, absint( $page ) );
		$per_page = absint( $per_page );
		if ( $per_page < 1 || $per_page > self::REST_MAX_PAGE_SIZE ) {
			return self::error( 'complete99_entity_studio_invalid_page_size', 'The collection page size must be between 1 and 100.' );
		}
		$total_subjects = count( $subjects );
		$records = array();
		$orphaned = false;
		if ( '' !== $subject_id ) {
			if ( ! isset( $subjects[ $subject_id ] ) ) {
				$orphan = self::record_from_post( self::record_post( $subject_id ), $subject_id );
				if ( ! $orphan ) {
					return self::error( 'complete99_entity_studio_unknown_subject', 'The requested subject or preserved dossier does not exist.', 404 );
				}
				$orphan['base_registry_state'] = 'subject_missing';
				$records = array( $subject_id => $orphan );
				$subjects = array();
				$total_subjects = 0;
				$orphaned = true;
			} else {
				$subjects = array( $subject_id => $subjects[ $subject_id ] );
				$record = self::record_from_post( self::record_post( $subject_id ), $subject_id );
				if ( $record ) {
					$record['base_registry_state'] = hash_equals( (string) $record['base_registry']['digest'], (string) $subjects[ $subject_id ]['base_registry']['digest'] ) ? 'current' : 'stale';
					$records[ $subject_id ] = $record;
				}
				$total_subjects = 1;
			}
			$page = 1;
			$per_page = 1;
		} else {
			$offset = ( $page - 1 ) * $per_page;
			$subjects = array_slice( $subjects, $offset, $per_page, true );
			foreach ( $subjects as $id => $subject ) {
				$record = self::record_from_post( self::record_post( $id ), $id );
				if ( $record ) {
					$record['base_registry_state'] = hash_equals( (string) $record['base_registry']['digest'], (string) $subject['base_registry']['digest'] ) ? 'current' : 'stale';
					$records[ $id ] = $record;
				}
			}
		}
		$references = self::scoped_snapshot_references( $references, $subjects, $records );
		$total_pages = max( 1, (int) ceil( $total_subjects / $per_page ) );
		return array(
			'schema'     => 'complete99-entity-studio-snapshot/v1',
			'generated_at'=> gmdate( 'Y-m-d\TH:i:s\Z' ),
			'authority'  => array(
				'entity_identity' => 'wordpress',
				'public_price'    => 'woocommerce',
				'public_inventory'=> 'woocommerce',
				'planning_record' => 'wordpress_private_revision',
			),
			'subjects'   => array_values( $subjects ),
			'records'    => array_values( $records ),
			'references' => $references,
			'pagination' => array(
				'page'           => $page,
				'per_page'       => $per_page,
				'total_subjects' => $total_subjects,
				'total_pages'    => $total_pages,
				'next_page'      => $page < $total_pages ? $page + 1 : null,
			),
			'orphaned'   => $orphaned,
		);
	}

	public static function rest_snapshot( WP_REST_Request $request ) {
		$result = self::snapshot( $request->get_param( 'subject_id' ), $request->get_param( 'page' ), $request->get_param( 'per_page' ) ?: self::REST_PAGE_SIZE );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function rest_save( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		$body_keys = is_array( $body ) ? array_keys( $body ) : array();
		$expected_keys = array( 'record', 'expected_revision', 'expected_base_digest', 'change_reason', 'correlation_id', 'rebase' );
		sort( $body_keys, SORT_STRING );
		sort( $expected_keys, SORT_STRING );
		if ( ! is_array( $body ) || $body_keys !== $expected_keys ) {
			return self::error( 'complete99_entity_studio_invalid_request', 'The write request does not match the versioned contract.' );
		}
		if ( ! is_bool( $body['rebase'] ) ) {
			return self::error( 'complete99_entity_studio_invalid_rebase', 'The rebase flag must be a boolean.' );
		}
		$result = self::save_record(
			$body['record'],
			$body['expected_revision'],
			$body['change_reason'],
			'wordpress-rest',
			$body['correlation_id'],
			true === $body['rebase'],
			$body['expected_base_digest']
		);
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	private static function parse_admin_list( $value ) {
		$parts = preg_split( '/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		return array_values( array_map( 'sanitize_key', is_array( $parts ) ? $parts : array() ) );
	}

	public static function handle_admin_save() {
		if ( ! self::can_manage() ) {
			wp_die( 'Entity Studio access is denied.' );
		}
		check_admin_referer( 'complete99_entity_studio_save' );

		$currency_code = isset( $_POST['currency_code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['currency_code'] ) ) ) : '';
		$references = self::reference_index();
		$price = isset( $_POST['planned_price'] ) ? self::amount_to_minor( wp_unslash( $_POST['planned_price'] ), self::currency_digits( $currency_code, $references ) ) : 0;
		if ( null === $price ) {
			$price = -1;
		}
		$payload = array(
			'subject_id'              => isset( $_POST['subject_id'] ) ? sanitize_key( wp_unslash( $_POST['subject_id'] ) ) : '',
			'workflow_state'          => isset( $_POST['workflow_state'] ) ? sanitize_key( wp_unslash( $_POST['workflow_state'] ) ) : '',
			'pricing_applicability'   => isset( $_POST['pricing_applicability'] ) ? sanitize_key( wp_unslash( $_POST['pricing_applicability'] ) ) : '',
			'commercial_role'         => isset( $_POST['commercial_role'] ) ? sanitize_key( wp_unslash( $_POST['commercial_role'] ) ) : '',
			'offer_type'              => isset( $_POST['offer_type'] ) ? sanitize_key( wp_unslash( $_POST['offer_type'] ) ) : '',
			'market_id'               => isset( $_POST['market_id'] ) ? sanitize_key( wp_unslash( $_POST['market_id'] ) ) : '',
			'channel_id'              => isset( $_POST['channel_id'] ) ? sanitize_key( wp_unslash( $_POST['channel_id'] ) ) : '',
			'currency_code'           => $currency_code,
			'planned_price_minor'     => $price,
			'pricing_state'           => isset( $_POST['pricing_state'] ) ? sanitize_key( wp_unslash( $_POST['pricing_state'] ) ) : '',
			'quality_tier'            => isset( $_POST['quality_tier'] ) ? sanitize_key( wp_unslash( $_POST['quality_tier'] ) ) : '',
			'source_observation_ids'  => self::parse_admin_list( isset( $_POST['source_observation_ids'] ) ? wp_unslash( $_POST['source_observation_ids'] ) : '' ),
			'cross_sell_subject_ids'  => self::parse_admin_list( isset( $_POST['cross_sell_subject_ids'] ) ? wp_unslash( $_POST['cross_sell_subject_ids'] ) : '' ),
			'upsell_subject_ids'      => self::parse_admin_list( isset( $_POST['upsell_subject_ids'] ) ? wp_unslash( $_POST['upsell_subject_ids'] ) : '' ),
			'value_proposition'       => array(
				'he' => isset( $_POST['value_proposition_he'] ) ? wp_unslash( $_POST['value_proposition_he'] ) : '',
				'en' => isset( $_POST['value_proposition_en'] ) ? wp_unslash( $_POST['value_proposition_en'] ) : '',
			),
			'price_rationale'         => array(
				'he' => isset( $_POST['price_rationale_he'] ) ? wp_unslash( $_POST['price_rationale_he'] ) : '',
				'en' => isset( $_POST['price_rationale_en'] ) ? wp_unslash( $_POST['price_rationale_en'] ) : '',
			),
			'private_note'            => isset( $_POST['private_note'] ) ? wp_unslash( $_POST['private_note'] ) : '',
		);
		$expected_revision = isset( $_POST['expected_revision'] ) ? absint( $_POST['expected_revision'] ) : 0;
		$expected_base_digest = isset( $_POST['expected_base_digest'] ) ? sanitize_text_field( wp_unslash( $_POST['expected_base_digest'] ) ) : '';
		$reason = isset( $_POST['change_reason'] ) ? wp_unslash( $_POST['change_reason'] ) : '';
		$result = self::save_record( $payload, $expected_revision, $reason, 'wordpress-admin', '', ! empty( $_POST['rebase'] ), $expected_base_digest );

		$args = array(
			'page'       => self::PAGE_SLUG,
			'subject_id' => $payload['subject_id'],
		);
		if ( is_wp_error( $result ) ) {
			$args['c99_error'] = sanitize_key( $result->get_error_code() );
		} else {
			$args['c99_saved'] = 1;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'tools.php' ) ) );
		exit;
	}

	private static function select_field( $name, $value, $options ) {
		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
		foreach ( $options as $option_value => $label ) {
			echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	public static function render_page() {
		if ( ! self::can_manage() ) {
			wp_die( 'Entity Studio access is denied.' );
		}
		$subjects = self::subject_index();
		$references = self::reference_index();
		$records = self::all_records();
		$orphan_records = array_diff_key( $records, $subjects );
		$subject_id = isset( $_GET['subject_id'] ) ? sanitize_key( wp_unslash( $_GET['subject_id'] ) ) : '';
		if ( '' === $subject_id || ! isset( $subjects[ $subject_id ] ) ) {
			$subject_id = (string) array_key_first( $subjects );
		}
		$subject = isset( $subjects[ $subject_id ] ) ? $subjects[ $subject_id ] : array();
		$stored = isset( $records[ $subject_id ] ) ? $records[ $subject_id ] : array();
		if ( $stored ) {
			$editable = array_merge(
				array(
					'subject_id'     => $stored['stable_id'],
					'workflow_state' => $stored['workflow']['state'],
				),
				$stored['payload']
			);
		} else {
			$editable = self::default_editable_record( $subject );
		}
		$history_bundle = self::history_bundle( $subject_id );
		$history = array_slice( $history_bundle['records'], 0, 50 );
		$base_registry_state = ! $stored
			? 'not_checked_out'
			: ( hash_equals( (string) ( $stored['base_registry']['digest'] ?? '' ), (string) ( $subject['base_registry']['digest'] ?? '' ) ) ? 'current' : 'stale' );
		$product_count = count( array_filter( $subjects, static function ( $item ) { return 'product' === $item['subject_type']; } ) );
		$live_price_count = count( array_filter( $subjects, static function ( $item ) { return 'product' === $item['subject_type'] && null !== $item['current_price_minor']; } ) );
		$planning_price_count = count( array_filter( $subjects, static function ( $item ) { return 'product' === $item['subject_type'] && null === $item['current_price_minor'] && null !== $item['planning_price_minor']; } ) );
		$price_coverage_count = count( array_filter( $subjects, static function ( $item ) { return 'product' === $item['subject_type'] && ( null !== $item['current_price_minor'] || null !== $item['planning_price_minor'] ); } ) );
		$error_code = isset( $_GET['c99_error'] ) ? sanitize_key( wp_unslash( $_GET['c99_error'] ) ) : '';
		$currency_options = array( '' => 'לא חל' );
		foreach ( $references['currencies'] as $currency_code => $currency ) {
			$currency_options[ $currency_code ] = $currency_code . ' (' . (int) ( $currency['minor_unit_digits'] ?? 2 ) . ')';
		}
		?>
		<div class="wrap c99-entity-studio" dir="rtl">
			<h1>Complete99 Entity Studio</h1>
			<p class="description">מרכז פרטי לניהול הערך העסקי של ישויות ומוצרים. מחיר ומלאי ציבוריים נשארים בסמכות WooCommerce.</p>
			<?php if ( ! empty( $_GET['c99_saved'] ) ) : ?>
				<div class="notice notice-success"><p>הגרסה נשמרה ונקראה מחדש בהצלחה.</p></div>
			<?php endif; ?>
			<?php if ( '' !== $error_code ) : ?>
				<div class="notice notice-error"><p>השמירה נעצרה בבטחה. קוד: <code><?php echo esc_html( $error_code ); ?></code></p></div>
			<?php endif; ?>
			<div class="c99-es-metrics">
				<div><strong><?php echo esc_html( count( $subjects ) ); ?></strong><span>ישויות ומוצרים</span></div>
				<div><strong><?php echo esc_html( $product_count ); ?></strong><span>מוצרים מסחריים</span></div>
				<div><strong><?php echo esc_html( $live_price_count ); ?></strong><span>מחירים מורשים ברישום</span></div>
				<div><strong><?php echo esc_html( $planning_price_count ); ?></strong><span>מחירי תכנון פרטיים</span></div>
				<div><strong><?php echo esc_html( $price_coverage_count . '/' . $product_count ); ?></strong><span>כיסוי תמחור</span></div>
				<div><strong><?php echo esc_html( count( $records ) ); ?></strong><span>תיקים עם גרסה שמורה</span></div>
				<div><strong><?php echo esc_html( count( $orphan_records ) ); ?></strong><span>תיקים שמקורם הוסר</span></div>
			</div>
			<?php if ( $orphan_records ) : ?>
				<section class="c99-es-authority">
					<h2>תיקי ביקורת שמקורם הוסר</h2>
					<p>התיקים נשמרים לקריאה ולביקורת ואינם ניתנים לעריכה עד שזהות המקור תחזור לרישום.</p>
					<table class="widefat striped"><thead><tr><th>מזהה יציב</th><th>תחום</th><th>סוג רשומה</th><th>מצב</th><th>גרסה</th></tr></thead><tbody>
					<?php foreach ( $orphan_records as $orphan_record ) : ?>
						<tr>
							<td><code><?php echo esc_html( $orphan_record['stable_id'] ); ?></code></td>
							<td><?php echo esc_html( $orphan_record['domain'] ); ?></td>
							<td><?php echo esc_html( $orphan_record['record_kind'] ); ?></td>
							<td><?php echo esc_html( $orphan_record['workflow']['state'] ); ?></td>
							<td><?php echo esc_html( (int) $orphan_record['workflow']['revision'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody></table>
				</section>
			<?php endif; ?>

			<form method="get" action="<?php echo esc_url( admin_url( 'tools.php' ) ); ?>" class="c99-es-picker">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<label for="subject_id_picker"><strong>בחירת ישות או מוצר</strong></label>
				<select name="subject_id" id="subject_id_picker">
					<?php foreach ( $subjects as $candidate ) : ?>
						<option value="<?php echo esc_attr( $candidate['id'] ); ?>" <?php selected( $subject_id, $candidate['id'] ); ?>>
							<?php echo esc_html( self::local_text( $candidate['label'] ) . ' | ' . $candidate['id'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="button">פתח תיק</button>
			</form>

			<?php if ( $subject ) : ?>
				<section class="c99-es-authority">
					<h2><?php echo esc_html( self::local_text( $subject['label'] ) ); ?></h2>
					<code><?php echo esc_html( $subject_id ); ?></code>
					<ul>
						<li>סוג: <strong><?php echo esc_html( $subject['subject_type'] . ' / ' . $subject['entity_type'] ); ?></strong></li>
						<li>ישות ידע מקושרת: <strong><?php echo esc_html( $subject['related_entity_id'] ?: 'לא הוגדרה' ); ?></strong></li>
						<li>קוד מוצר WooCommerce: <strong><?php echo esc_html( $subject['woo_product_code'] ?: 'לא חל' ); ?></strong></li>
						<li>מחיר מורשה ברישום: <strong><?php echo null === $subject['current_price_minor'] ? 'לא נקבע' : esc_html( '₪' . self::minor_to_price( $subject['current_price_minor'] ) ); ?></strong></li>
						<li>מחיר תכנון פרטי: <strong><?php echo null === $subject['planning_price_minor'] ? 'לא נקבע' : esc_html( (string) $subject['planning_currency_code'] . ' ' . self::minor_to_amount( $subject['planning_price_minor'], self::currency_digits( $subject['planning_currency_code'], $references ) ) ); ?></strong></li>
						<li>מזהי הצעת תכנון: <strong><?php echo esc_html( $subject['planning_offer_ids'] ? implode( ', ', $subject['planning_offer_ids'] ) : 'לא חל' ); ?></strong></li>
						<li>מחיר שנקרא מ-WooCommerce: <strong><?php echo null === $subject['woo_price_minor'] ? 'לא נקרא' : esc_html( '₪' . self::minor_to_price( $subject['woo_price_minor'] ) ); ?></strong></li>
						<li>מצב סמכות: <strong><?php echo esc_html( $subject['price_authority_state'] ); ?></strong></li>
						<li>בסיס התיק: <strong><?php echo esc_html( $subject['domain'] . ' / ' . $subject['base_registry']['version'] . ' / ' . $base_registry_state ); ?></strong></li>
						<li>שרשרת גרסאות: <strong><?php echo esc_html( $history_bundle['state'] . ( '' !== $history_bundle['reason'] ? ' / ' . $history_bundle['reason'] : '' ) ); ?></strong></li>
					</ul>
				</section>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="c99-es-form">
					<input type="hidden" name="action" value="complete99_entity_studio_save">
					<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>">
					<input type="hidden" name="expected_revision" value="<?php echo esc_attr( (int) ( $stored['workflow']['revision'] ?? 0 ) ); ?>">
					<input type="hidden" name="expected_base_digest" value="<?php echo esc_attr( (string) $subject['base_registry']['digest'] ); ?>">
					<?php wp_nonce_field( 'complete99_entity_studio_save' ); ?>
					<div class="c99-es-grid">
						<label>מצב עבודה<?php self::select_field( 'workflow_state', $editable['workflow_state'], array( 'draft' => 'טיוטה', 'in_review' => 'בבדיקה', 'approved' => 'מאושר' ) ); ?></label>
						<label>אפשרות תמחור<?php self::select_field( 'pricing_applicability', $editable['pricing_applicability'], array( 'not_priceable' => 'לא ניתן לתמחור', 'priceable' => 'ניתן לתמחור' ) ); ?></label>
						<label>תפקיד מסחרי<?php self::select_field( 'commercial_role', $editable['commercial_role'], array( 'knowledge' => 'ידע', 'discovery' => 'גילוי', 'conversion' => 'המרה', 'replenishment' => 'חידוש מלאי', 'bundle' => 'מארז', 'experience' => 'חוויה' ) ); ?></label>
						<label>סוג הצעה<?php self::select_field( 'offer_type', $editable['offer_type'], array( 'none' => 'לא חל', 'ingredient' => 'חומר גלם', 'equipment' => 'ציוד', 'bundle' => 'מארז', 'service' => 'שירות' ) ); ?></label>
						<label>שוק<?php self::select_field( 'market_id', $editable['market_id'], array( '' => 'לא חל' ) + array_map( array( __CLASS__, 'local_text' ), $references['markets'] ) ); ?></label>
						<label>ערוץ<?php self::select_field( 'channel_id', $editable['channel_id'], array( '' => 'לא חל' ) + array_map( array( __CLASS__, 'local_text' ), $references['channels'] ) ); ?></label>
						<label>מטבע וספרות משנה<?php self::select_field( 'currency_code', $editable['currency_code'], $currency_options ); ?></label>
						<label>מחיר מתוכנן<input type="text" inputmode="decimal" name="planned_price" value="<?php echo esc_attr( self::minor_to_amount( $editable['planned_price_minor'], self::currency_digits( $editable['currency_code'], $references ) ) ); ?>"></label>
						<label>מצב מחיר<?php self::select_field( 'pricing_state', $editable['pricing_state'], array( 'not_applicable' => 'לא חל', 'research' => 'מחקר', 'market_benchmarked' => 'נבדק מול שוק', 'owner_authorized_planned' => 'מחיר תכנון מורשה' ) ); ?></label>
						<label>דרגת איכות<?php self::select_field( 'quality_tier', $editable['quality_tier'], array( 'not_applicable' => 'לא חל', 'standard' => 'רגיל', 'premium' => 'פרימיום', 'reserve' => 'רזרב', 'professional' => 'מקצועי' ) ); ?></label>
					</div>
					<label>מזהי תצפיות שוק<textarea name="source_observation_ids" rows="3"><?php echo esc_textarea( implode( "\n", $editable['source_observation_ids'] ) ); ?></textarea></label>
					<label>מוצרי Cross-sell<textarea name="cross_sell_subject_ids" rows="3"><?php echo esc_textarea( implode( "\n", $editable['cross_sell_subject_ids'] ) ); ?></textarea></label>
					<label>מוצרי Up-sell<textarea name="upsell_subject_ids" rows="3"><?php echo esc_textarea( implode( "\n", $editable['upsell_subject_ids'] ) ); ?></textarea></label>
					<div class="c99-es-two">
						<label>הצעת ערך בעברית<textarea name="value_proposition_he" rows="4"><?php echo esc_textarea( $editable['value_proposition']['he'] ); ?></textarea></label>
						<label>Value proposition in English<textarea name="value_proposition_en" rows="4" dir="ltr"><?php echo esc_textarea( $editable['value_proposition']['en'] ); ?></textarea></label>
						<label>היגיון מחיר בעברית<textarea name="price_rationale_he" rows="4"><?php echo esc_textarea( $editable['price_rationale']['he'] ); ?></textarea></label>
						<label>Price rationale in English<textarea name="price_rationale_en" rows="4" dir="ltr"><?php echo esc_textarea( $editable['price_rationale']['en'] ); ?></textarea></label>
					</div>
					<label>הערה עסקית פרטית<textarea name="private_note" rows="4"><?php echo esc_textarea( $editable['private_note'] ); ?></textarea></label>
					<?php if ( 'stale' === $base_registry_state ) : ?>
						<label><input type="checkbox" name="rebase" value="1"> קראתי את שינוי המקור ואני מבקש לשמור גרסת טיוטה על בסיס הרשומה החדשה</label>
					<?php endif; ?>
					<label>סיבת השינוי<input type="text" name="change_reason" required maxlength="500" value=""></label>
					<p><button type="submit" class="button button-primary">שמור גרסה פרטית</button> <span>גרסה נוכחית: <?php echo esc_html( (int) ( $stored['workflow']['revision'] ?? 0 ) ); ?></span></p>
				</form>

				<section>
					<h2>היסטוריית גרסאות</h2>
					<?php if ( empty( $history ) ) : ?>
						<p>עדיין לא נשמרה גרסה לתיק הזה.</p>
					<?php else : ?>
						<table class="widefat striped"><thead><tr><th>גרסה</th><th>מצב</th><th>מחיר תכנון</th><th>זמן</th><th>סיבה</th></tr></thead><tbody>
						<?php foreach ( $history as $version ) : ?>
							<tr>
								<td><?php echo esc_html( (int) $version['workflow']['revision'] ); ?></td>
								<td><?php echo esc_html( $version['workflow']['state'] ); ?></td>
								<td><?php $history_currency = (string) $version['payload']['currency_code']; echo esc_html( $history_currency . ' ' . self::minor_to_amount( $version['payload']['planned_price_minor'], self::currency_digits( $history_currency, $references ) ) ); ?></td>
								<td><?php echo esc_html( $version['updated_at'] ); ?></td>
								<td><?php echo esc_html( $version['event']['change_reason'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody></table>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</div>
		<style>
			.c99-entity-studio{max-width:1320px}.c99-es-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:20px 0}.c99-es-metrics div,.c99-es-authority,.c99-es-form{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}.c99-es-metrics strong{display:block;font-size:28px}.c99-es-metrics span{display:block;margin-top:4px}.c99-es-picker{display:flex;align-items:end;gap:10px;margin:20px 0}.c99-es-picker label{display:grid;gap:6px}.c99-es-picker select{min-width:520px;max-width:70vw}.c99-es-authority ul{columns:2}.c99-es-form{display:grid;gap:16px;margin:20px 0}.c99-es-form label{display:grid;gap:6px;font-weight:600}.c99-es-form textarea,.c99-es-form input[type=text],.c99-es-form select{width:100%;max-width:none}.c99-es-grid,.c99-es-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}@media(max-width:782px){.c99-es-picker{align-items:stretch;flex-direction:column}.c99-es-picker select{min-width:0;max-width:100%;width:100%}.c99-es-authority ul{columns:1}.c99-es-grid,.c99-es-two{grid-template-columns:1fr}}
		</style>
		<?php
	}
}
