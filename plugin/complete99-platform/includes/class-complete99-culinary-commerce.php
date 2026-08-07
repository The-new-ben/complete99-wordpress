<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Private, evidence-bound commerce graph for culinary knowledge entities.
 *
 * Knowledge, catalog identity, observed prices and sellable offers are kept as
 * separate records. Only approved channel offers may enter a POS projection.
 */
final class Complete99_Culinary_Commerce {
	const REGISTRY_SCHEMA    = 'complete99-culinary-commerce-registry/v2';
	const REST_NAMESPACE     = 'complete99/v1';
	const DATA_FILE          = 'culinary-commerce-pilot.php';
	const POS_REQUEST_SCHEMA = 'complete99-pos-catalog-request/v1';
	const POS_RESPONSE_SCHEMA = 'complete99-pos-catalog-response/v1';
	const MAX_PAGE_SIZE      = 250;

	private static $booted = false;
	private static $registry_cache = null;

	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/editorial/culinary-commerce',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( __CLASS__, 'can_view_editorial_snapshot' ),
				'callback'            => array( __CLASS__, 'rest_editorial_snapshot' ),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/integrations/pos/catalog',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'verify_pos_signature' ),
				'callback'            => array( __CLASS__, 'rest_pos_catalog' ),
			)
		);
	}

	public static function can_view_editorial_snapshot() {
		return current_user_can( 'manage_options' );
	}

	public static function verify_pos_signature( WP_REST_Request $request ) {
		$payload = json_decode( (string) $request->get_body(), true );
		if ( ! is_array( $payload ) ) {
			return self::error( 'complete99_pos_auth_payload', 'The POS authentication payload is invalid.', 401 );
		}
		$consumer_id = isset( $payload['consumer_id'] ) && is_string( $payload['consumer_id'] ) ? $payload['consumer_id'] : '';
		$market_id = isset( $payload['market_id'] ) && is_string( $payload['market_id'] ) ? $payload['market_id'] : '';
		$channel_id = isset( $payload['channel_id'] ) && is_string( $payload['channel_id'] ) ? $payload['channel_id'] : '';
		$registry = self::registry();
		if ( self::is_error( $registry ) ) {
			return $registry;
		}
		$consumer = self::find_record( $registry['integration_consumers'], $consumer_id );
		if ( ! is_array( $consumer ) || 'active' !== $consumer['state']
			|| ! in_array( $market_id, $consumer['market_ids'], true )
			|| ! in_array( $channel_id, $consumer['channel_ids'], true ) ) {
			return self::error( 'complete99_pos_consumer_scope', 'The POS consumer is not authorized for this scope.', 403 );
		}
		return Complete99_REST::verify_scoped_integration_signature( $request, 'pos_catalog', $consumer['id'], $consumer['key_id'] );
	}

	/**
	 * Load the immutable seed used by migrations and the private review lab.
	 *
	 * @param bool $fresh Bypass the request cache.
	 * @return array|WP_Error
	 */
	public static function registry( $fresh = false ) {
		if ( ! $fresh && is_array( self::$registry_cache ) ) {
			return self::$registry_cache;
		}
		$path = COMPLETE99_PLATFORM_DIR . 'data/' . self::DATA_FILE;
		if ( ! is_readable( $path ) ) {
			return self::error( 'complete99_commerce_registry_missing', 'The culinary commerce registry is unavailable.', 500 );
		}
		$registry = require $path;
		$valid = self::validate_registry( $registry, $fresh );
		if ( self::is_error( $valid ) ) {
			return $valid;
		}
		self::$registry_cache = $registry;
		return self::$registry_cache;
	}

	/**
	 * Validate relational identity, evidence, state machines and money fields.
	 *
	 * @param mixed $registry Candidate registry.
	 * @param bool  $fresh    Refresh the linked culinary-science registry too.
	 * @return true|WP_Error
	 */
	public static function validate_registry( $registry, $fresh = false ) {
		try {
			self::assert_exact_keys(
				$registry,
				array(
					'schema', 'version', 'generated_at', 'knowledge_registry_version', 'controlled_vocabulary',
					'countries', 'currencies', 'locales', 'tax_zones', 'markets', 'channels', 'sellers',
					'brands', 'manufacturers', 'products', 'variants', 'skus', 'supplier_offers',
					'evidence_artifacts', 'market_observations', 'channel_offers', 'landed_cost_scenarios',
					'margin_scenarios', 'bundles', 'merchandising_edges', 'connector_profiles', 'integration_consumers',
				),
				'registry'
			);
			if ( self::REGISTRY_SCHEMA !== $registry['schema'] ) {
				throw new RuntimeException( 'registry.schema' );
			}
			self::assert_identifier( $registry['version'], 'registry.version', 120 );
			self::assert_date( $registry['generated_at'], 'registry.generated_at' );
			self::assert_identifier( $registry['knowledge_registry_version'], 'registry.knowledge_registry_version', 120 );
			self::assert_no_em_dash( $registry, 'registry' );

			$vocabulary_keys = array(
				'product_states', 'variant_states', 'sku_states', 'supplier_offer_states', 'observation_states',
				'channel_offer_states', 'scenario_states', 'bundle_states', 'edge_states', 'edge_types',
				'seller_types', 'channel_types', 'connector_binding_states', 'transport_modes',
				'inventory_policies', 'evidence_states', 'retention_states', 'tax_states', 'market_states',
				'identity_states', 'content_states', 'comparability_states', 'customer_segments',
				'channel_states', 'cost_line_states', 'sku_compliance_states', 'price_bases',
				'integration_consumer_states',
			);
			self::assert_exact_keys( $registry['controlled_vocabulary'], $vocabulary_keys, 'registry.controlled_vocabulary' );
			foreach ( $vocabulary_keys as $key ) {
				self::assert_identifier_list( $registry['controlled_vocabulary'][ $key ], 'registry.controlled_vocabulary.' . $key, false, 50 );
			}

			$science = self::science_registry( $fresh );
			if ( self::is_error( $science ) ) {
				throw new RuntimeException( 'registry.knowledge_registry_unavailable' );
			}
			if ( $registry['knowledge_registry_version'] !== $science['version'] ) {
				throw new RuntimeException( 'registry.knowledge_registry_version' );
			}
			$science_entities = self::index_records( $science['entities'], 'id', 'knowledge.entities' );
			$science_sources  = $science['sources'];

			$countries = self::validate_countries( $registry['countries'] );
			$currencies = self::validate_currencies( $registry['currencies'] );
			$locales = self::validate_locales( $registry['locales'], $countries, $registry['controlled_vocabulary'] );
			$sellers = self::validate_sellers( $registry['sellers'], $countries, $science_entities, $science_sources, $registry['controlled_vocabulary'] );
			$tax_zones = self::validate_tax_zones( $registry['tax_zones'], $countries, $science_sources, $registry['controlled_vocabulary'] );
			$markets = self::validate_markets( $registry['markets'], $countries, $currencies, $locales, $tax_zones, $sellers, $registry['controlled_vocabulary'] );
			$connectors = self::validate_connectors( $registry['connector_profiles'], $registry['controlled_vocabulary'] );
			$channels = self::validate_channels( $registry['channels'], $markets, $connectors, $registry['controlled_vocabulary'] );
			$brands = self::validate_brands( $registry['brands'], $sellers, $science_sources, $registry['controlled_vocabulary'] );
			$manufacturers = self::validate_manufacturers( $registry['manufacturers'], $sellers, $countries, $science_entities, $science_sources, $registry['controlled_vocabulary'] );
			$products = self::validate_products( $registry['products'], $science_entities, $brands, $manufacturers, $science_sources, $registry['controlled_vocabulary'] );
			$variants = self::validate_variants( $registry['variants'], $products, $science_entities, $registry['controlled_vocabulary'] );
			$skus = self::validate_skus( $registry['skus'], $variants, $products, $registry['controlled_vocabulary'] );
			$artifacts = self::validate_evidence_artifacts( $registry['evidence_artifacts'], $science_sources, $registry['controlled_vocabulary'] );
			$supplier_offers = self::validate_supplier_offers( $registry['supplier_offers'], $skus, $sellers, $markets, $currencies, $artifacts, $registry['controlled_vocabulary'] );
			$observations = self::validate_observations( $registry['market_observations'], $skus, $variants, $products, $sellers, $markets, $currencies, $artifacts, $science_entities, $registry['controlled_vocabulary'] );
			$cost_scenarios = self::validate_landed_costs( $registry['landed_cost_scenarios'], $skus, $markets, $currencies, $observations, $supplier_offers, $artifacts, $registry['controlled_vocabulary'] );
			$margin_scenarios = self::validate_margin_scenarios( $registry['margin_scenarios'], $currencies, $cost_scenarios, $artifacts, $registry['controlled_vocabulary'] );
			$offers = self::validate_channel_offers( $registry['channel_offers'], $skus, $variants, $products, $markets, $tax_zones, $sellers, $channels, $connectors, $currencies, $supplier_offers, $cost_scenarios, $margin_scenarios, $artifacts, $registry['controlled_vocabulary'] );
			self::validate_margin_offer_links( $margin_scenarios, $offers );
			self::validate_bundles( $registry['bundles'], $skus, $markets, $channels, $offers, $artifacts, $registry['controlled_vocabulary'] );
			self::validate_edges( $registry['merchandising_edges'], $skus, $artifacts, $registry['controlled_vocabulary'] );
			self::validate_connector_channel_links( $connectors, $channels );
			self::validate_integration_consumers( $registry['integration_consumers'], $connectors, $markets, $channels, $registry['controlled_vocabulary'] );
			return true;
		} catch ( Throwable $error ) {
			return self::error(
				'complete99_commerce_registry_invalid',
				'The culinary commerce registry failed validation.',
				500,
				array( 'path' => $error->getMessage() )
			);
		}
	}

	private static function validate_countries( $records ) {
		$index = self::index_records( $records, 'id', 'countries' );
		foreach ( $records as $offset => $record ) {
			$path = 'countries.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'iso2', 'name' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 80 );
			if ( 1 !== preg_match( '/\A[A-Z]{2}\z/', $record['iso2'] ) ) {
				throw new RuntimeException( $path . '.iso2' );
			}
			self::assert_translation( $record['name'], $path . '.name', 120 );
		}
		return $index;
	}

	private static function validate_currencies( $records ) {
		$index = self::index_records( $records, 'id', 'currencies' );
		foreach ( $records as $offset => $record ) {
			$path = 'currencies.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'code', 'minor_unit_digits' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 80 );
			if ( 1 !== preg_match( '/\A[A-Z]{3}\z/', $record['code'] ) || ! is_int( $record['minor_unit_digits'] ) || $record['minor_unit_digits'] < 0 || $record['minor_unit_digits'] > 3 ) {
				throw new RuntimeException( $path . '.currency' );
			}
		}
		return $index;
	}

	private static function validate_locales( $records, $countries, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'locales' );
		foreach ( $records as $offset => $record ) {
			$path = 'locales.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'bcp47', 'language_code', 'country_id', 'label', 'content_state', 'path_prefix' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 80 );
			if ( 1 !== preg_match( '/\A[a-z]{2,3}(?:-[A-Z]{2})?\z/', $record['bcp47'] ) || 1 !== preg_match( '/\A[a-z]{2,3}\z/', $record['language_code'] ) ) {
				throw new RuntimeException( $path . '.locale' );
			}
			self::assert_reference( $record['country_id'], $countries, $path . '.country_id' );
			self::assert_translation( $record['label'], $path . '.label', 120 );
			self::assert_enum( $record['content_state'], $vocabulary['content_states'], $path . '.content_state' );
			if ( '' !== $record['path_prefix'] && 1 !== preg_match( '#\A/[a-z]{2,3}/\z#', $record['path_prefix'] ) ) {
				throw new RuntimeException( $path . '.path_prefix' );
			}
		}
		return $index;
	}

	private static function validate_sellers( $records, $countries, $science_entities, $science_sources, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'sellers' );
		foreach ( $records as $offset => $record ) {
			$path = 'sellers.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'name', 'seller_type', 'country_id', 'science_entity_id', 'legal_identity_state', 'source_ids', 'status' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_translation( $record['name'], $path . '.name', 160 );
			self::assert_enum( $record['seller_type'], $vocabulary['seller_types'], $path . '.seller_type' );
			self::assert_reference( $record['country_id'], $countries, $path . '.country_id' );
			self::assert_optional_reference( $record['science_entity_id'], $science_entities, $path . '.science_entity_id' );
			self::assert_enum( $record['legal_identity_state'], $vocabulary['identity_states'], $path . '.legal_identity_state' );
			self::assert_source_ids( $record['source_ids'], $science_sources, $path . '.source_ids', true );
			self::assert_identifier( $record['status'], $path . '.status', 80 );
		}
		return $index;
	}

	private static function validate_tax_zones( $records, $countries, $science_sources, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'tax_zones' );
		foreach ( $records as $offset => $record ) {
			$path = 'tax_zones.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'country_id', 'state', 'basis', 'evidence_source_ids', 'review_at' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_reference( $record['country_id'], $countries, $path . '.country_id' );
			self::assert_enum( $record['state'], $vocabulary['tax_states'], $path . '.state' );
			self::assert_translation( $record['basis'], $path . '.basis', 500 );
			self::assert_source_ids( $record['evidence_source_ids'], $science_sources, $path . '.evidence_source_ids', true );
			self::assert_date( $record['review_at'], $path . '.review_at' );
		}
		return $index;
	}

	private static function validate_markets( $records, $countries, $currencies, $locales, $tax_zones, $sellers, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'markets' );
		foreach ( $records as $offset => $record ) {
			$path = 'markets.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'label', 'country_id', 'currency_id', 'locale_ids', 'tax_zone_ids', 'seller_of_record_id', 'fulfillment_region_ids', 'purpose', 'state' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_translation( $record['label'], $path . '.label', 160 );
			self::assert_reference( $record['country_id'], $countries, $path . '.country_id' );
			self::assert_reference( $record['currency_id'], $currencies, $path . '.currency_id' );
			self::assert_reference_list( $record['locale_ids'], $locales, $path . '.locale_ids', false );
			self::assert_reference_list( $record['tax_zone_ids'], $tax_zones, $path . '.tax_zone_ids', true );
			self::assert_optional_reference( $record['seller_of_record_id'], $sellers, $path . '.seller_of_record_id' );
			self::assert_identifier_list( $record['fulfillment_region_ids'], $path . '.fulfillment_region_ids', true, 30 );
			self::assert_identifier( $record['purpose'], $path . '.purpose', 80 );
			self::assert_enum( $record['state'], $vocabulary['market_states'], $path . '.state' );
		}
		return $index;
	}

	private static function validate_connectors( $records, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'connector_profiles' );
		foreach ( $records as $offset => $record ) {
			$path = 'connector_profiles.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'vendor', 'channel_ids', 'binding_state', 'transport_mode', 'contract_schema', 'capabilities', 'official_source_urls', 'credential_scope', 'notes' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_text( $record['vendor'], $path . '.vendor', 160 );
			self::assert_identifier_list( $record['channel_ids'], $path . '.channel_ids', false, 20 );
			self::assert_enum( $record['binding_state'], $vocabulary['connector_binding_states'], $path . '.binding_state' );
			self::assert_enum( $record['transport_mode'], $vocabulary['transport_modes'], $path . '.transport_mode' );
			self::assert_identifier( $record['contract_schema'], $path . '.contract_schema', 120 );
			self::assert_identifier_list( $record['capabilities'], $path . '.capabilities', false, 30 );
			self::assert_url_list( $record['official_source_urls'], $path . '.official_source_urls', true );
			self::assert_text( $record['credential_scope'], $path . '.credential_scope', 500 );
			self::assert_translation( $record['notes'], $path . '.notes', 1000 );
			if ( 'contract_required' === $record['binding_state'] && 'unbound' !== $record['transport_mode'] ) {
				throw new RuntimeException( $path . '.unverified_transport' );
			}
			if ( 'internal_bound' === $record['binding_state'] && 'internal' !== $record['transport_mode'] ) {
				throw new RuntimeException( $path . '.internal_transport' );
			}
			if ( 'bound' === $record['binding_state'] && ! in_array( $record['transport_mode'], array( 'api', 'webhook', 'polling', 'batch' ), true ) ) {
				throw new RuntimeException( $path . '.bound_transport' );
			}
			if ( 'unbound' === $record['transport_mode'] && ! in_array( $record['binding_state'], array( 'contract_required', 'disabled' ), true ) ) {
				throw new RuntimeException( $path . '.unbound_state' );
			}
		}
		return $index;
	}

	private static function validate_channels( $records, $markets, $connectors, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'channels' );
		foreach ( $records as $offset => $record ) {
			$path = 'channels.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'label', 'channel_type', 'market_ids', 'catalog_authority', 'price_authority', 'inventory_authority', 'order_authority', 'hierarchy_depth', 'connector_profile_id', 'state' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_translation( $record['label'], $path . '.label', 160 );
			self::assert_enum( $record['channel_type'], $vocabulary['channel_types'], $path . '.channel_type' );
			self::assert_reference_list( $record['market_ids'], $markets, $path . '.market_ids', false );
			foreach ( array( 'catalog_authority', 'price_authority', 'inventory_authority', 'order_authority' ) as $authority ) {
				self::assert_identifier( $record[ $authority ], $path . '.' . $authority, 100 );
			}
			if ( ! is_int( $record['hierarchy_depth'] ) || $record['hierarchy_depth'] < 1 || $record['hierarchy_depth'] > 8 ) {
				throw new RuntimeException( $path . '.hierarchy_depth' );
			}
			self::assert_reference( $record['connector_profile_id'], $connectors, $path . '.connector_profile_id' );
			self::assert_enum( $record['state'], $vocabulary['channel_states'], $path . '.state' );
			if ( in_array( $record['channel_type'], array( 'kiosk', 'pos' ), true ) && 'woocommerce' !== $record['catalog_authority'] ) {
				throw new RuntimeException( $path . '.catalog_authority' );
			}
		}
		return $index;
	}

	private static function validate_brands( $records, $sellers, $science_sources, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'brands' );
		foreach ( $records as $offset => $record ) {
			$path = 'brands.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'name', 'owner_seller_id', 'identity_state', 'source_ids' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_translation( $record['name'], $path . '.name', 160 );
			self::assert_optional_reference( $record['owner_seller_id'], $sellers, $path . '.owner_seller_id' );
			self::assert_enum( $record['identity_state'], $vocabulary['identity_states'], $path . '.identity_state' );
			self::assert_source_ids( $record['source_ids'], $science_sources, $path . '.source_ids', false );
		}
		return $index;
	}

	private static function validate_manufacturers( $records, $sellers, $countries, $science_entities, $science_sources, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'manufacturers' );
		foreach ( $records as $offset => $record ) {
			$path = 'manufacturers.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'name', 'seller_id', 'country_id', 'science_entity_id', 'identity_state', 'source_ids' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_translation( $record['name'], $path . '.name', 160 );
			self::assert_optional_reference( $record['seller_id'], $sellers, $path . '.seller_id' );
			self::assert_reference( $record['country_id'], $countries, $path . '.country_id' );
			self::assert_optional_reference( $record['science_entity_id'], $science_entities, $path . '.science_entity_id' );
			self::assert_enum( $record['identity_state'], $vocabulary['identity_states'], $path . '.identity_state' );
			self::assert_source_ids( $record['source_ids'], $science_sources, $path . '.source_ids', false );
		}
		return $index;
	}

	private static function validate_products( $records, $science_entities, $brands, $manufacturers, $science_sources, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'products' );
		foreach ( $records as $offset => $record ) {
			$path = 'products.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'knowledge_entity_id', 'name', 'brand_id', 'manufacturer_id', 'product_family', 'state', 'taxonomy_ids', 'compliance_rule_ids', 'source_ids', 'created_at' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_reference( $record['knowledge_entity_id'], $science_entities, $path . '.knowledge_entity_id' );
			self::assert_translation( $record['name'], $path . '.name', 200 );
			self::assert_optional_reference( $record['brand_id'], $brands, $path . '.brand_id' );
			self::assert_optional_reference( $record['manufacturer_id'], $manufacturers, $path . '.manufacturer_id' );
			self::assert_identifier( $record['product_family'], $path . '.product_family', 100 );
			self::assert_enum( $record['state'], $vocabulary['product_states'], $path . '.state' );
			self::assert_identifier_list( $record['taxonomy_ids'], $path . '.taxonomy_ids', false, 40 );
			self::assert_identifier_list( $record['compliance_rule_ids'], $path . '.compliance_rule_ids', true, 30 );
			self::assert_source_ids( $record['source_ids'], $science_sources, $path . '.source_ids', false );
			self::assert_date( $record['created_at'], $path . '.created_at' );
		}
		return $index;
	}

	private static function validate_variants( $records, $products, $science_entities, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'variants' );
		foreach ( $records as $offset => $record ) {
			$path = 'variants.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'product_id', 'name', 'attributes', 'net_quantity', 'state', 'source_entity_ids' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 110 );
			self::assert_reference( $record['product_id'], $products, $path . '.product_id' );
			self::assert_translation( $record['name'], $path . '.name', 240 );
			self::assert_scalar_map( $record['attributes'], $path . '.attributes', 40 );
			self::assert_quantity( $record['net_quantity'], $path . '.net_quantity' );
			self::assert_enum( $record['state'], $vocabulary['variant_states'], $path . '.state' );
			self::assert_reference_list( $record['source_entity_ids'], $science_entities, $path . '.source_entity_ids', false );
		}
		return $index;
	}

	private static function validate_skus( $records, $variants, $products, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'skus' );
		$codes = array();
		$woo_codes = array();
		$barcodes = array();
		$external_ids = array();
		foreach ( $records as $offset => $record ) {
			$path = 'skus.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'variant_id', 'internal_code', 'woo_product_code', 'external_ids', 'state', 'barcode', 'inventory_policy', 'compliance_state' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			self::assert_reference( $record['variant_id'], $variants, $path . '.variant_id' );
			if ( 1 !== preg_match( '/\A[A-Z0-9][A-Z0-9._-]{5,79}\z/', $record['internal_code'] ) || isset( $codes[ $record['internal_code'] ] ) ) {
				throw new RuntimeException( $path . '.internal_code' );
			}
			$codes[ $record['internal_code'] ] = true;
			self::assert_identifier( $record['state'], $path . '.state', 80 );
			self::assert_enum( $record['state'], $vocabulary['sku_states'], $path . '.state' );
			if ( '' !== $record['woo_product_code'] ) {
				if ( 1 !== preg_match( '/\A[a-z0-9][a-z0-9._-]{5,79}\z/', $record['woo_product_code'] ) || isset( $woo_codes[ $record['woo_product_code'] ] ) ) {
					throw new RuntimeException( $path . '.woo_product_code' );
				}
				$woo_codes[ $record['woo_product_code'] ] = true;
			}
			if ( in_array( $record['state'], array( 'verified_sku', 'active' ), true ) && '' === $record['woo_product_code'] ) {
				throw new RuntimeException( $path . '.verified_without_woo_code' );
			}
			if ( 'research_candidate' === $record['state'] && '' !== $record['woo_product_code'] ) {
				throw new RuntimeException( $path . '.premature_woo_code' );
			}
			self::assert_external_ids( $record['external_ids'], $path . '.external_ids' );
			foreach ( $record['external_ids'] as $external_id ) {
				$key = strtolower( $external_id['provider'] ) . '|' . $external_id['value'];
				if ( isset( $external_ids[ $key ] ) ) {
					throw new RuntimeException( $path . '.external_id_collision' );
				}
				$external_ids[ $key ] = true;
			}
			self::assert_text( $record['barcode'], $path . '.barcode', 80, true );
			if ( '' !== $record['barcode'] ) {
				if ( isset( $barcodes[ $record['barcode'] ] ) ) {
					throw new RuntimeException( $path . '.barcode_collision' );
				}
				$barcodes[ $record['barcode'] ] = true;
			}
			self::assert_enum( $record['inventory_policy'], $vocabulary['inventory_policies'], $path . '.inventory_policy' );
			self::assert_enum( $record['compliance_state'], $vocabulary['sku_compliance_states'], $path . '.compliance_state' );
			$variant = $variants[ $record['variant_id'] ];
			$product = $products[ $variant['product_id'] ];
			if ( 'verified_sku' === $record['state']
				&& ( ! in_array( $variant['state'], array( 'verified_variant', 'active' ), true )
					|| ! in_array( $product['state'], array( 'verified_product', 'active' ), true ) ) ) {
				throw new RuntimeException( $path . '.verified_parent_state' );
			}
			if ( 'active' === $record['state']
				&& ( 'active' !== $variant['state'] || 'active' !== $product['state'] || 'cleared' !== $record['compliance_state'] ) ) {
				throw new RuntimeException( $path . '.active_parent_or_compliance' );
			}
		}
		return $index;
	}

	private static function validate_evidence_artifacts( $records, $science_sources, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'evidence_artifacts' );
		foreach ( $records as $offset => $record ) {
			$path = 'evidence_artifacts.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'source_id', 'source_url', 'captured_at', 'capture_method', 'claim_locator', 'snapshot_digest', 'snapshot_uri', 'captured_by', 'verification_state', 'retention_state', 'offer_approval_eligible' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			if ( ! isset( $science_sources[ $record['source_id'] ] ) ) {
				throw new RuntimeException( $path . '.source_id' );
			}
			self::assert_https_url( $record['source_url'], $path . '.source_url' );
			if ( $record['source_url'] !== $science_sources[ $record['source_id'] ]['url'] ) {
				throw new RuntimeException( $path . '.source_url_mismatch' );
			}
			self::assert_timestamp( $record['captured_at'], $path . '.captured_at' );
			self::assert_identifier( $record['capture_method'], $path . '.capture_method', 100 );
			self::assert_text( $record['claim_locator'], $path . '.claim_locator', 500 );
			if ( '' !== $record['snapshot_digest'] && 1 !== preg_match( '/\Asha256:[a-f0-9]{64}\z/', $record['snapshot_digest'] ) ) {
				throw new RuntimeException( $path . '.snapshot_digest' );
			}
			if ( '' !== $record['snapshot_uri'] ) {
				self::assert_text( $record['snapshot_uri'], $path . '.snapshot_uri', 500 );
			}
			self::assert_identifier( $record['captured_by'], $path . '.captured_by', 100 );
			self::assert_enum( $record['verification_state'], $vocabulary['evidence_states'], $path . '.verification_state' );
			self::assert_enum( $record['retention_state'], $vocabulary['retention_states'], $path . '.retention_state' );
			if ( ! is_bool( $record['offer_approval_eligible'] ) ) {
				throw new RuntimeException( $path . '.offer_approval_eligible' );
			}
			if ( $record['offer_approval_eligible'] && ( '' === $record['snapshot_digest'] || 'retained' !== $record['retention_state'] ) ) {
				throw new RuntimeException( $path . '.eligible_without_snapshot' );
			}
		}
		return $index;
	}

	private static function validate_supplier_offers( $records, $skus, $sellers, $markets, $currencies, $artifacts, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'supplier_offers' );
		foreach ( $records as $offset => $record ) {
			$path = 'supplier_offers.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'sku_id', 'seller_id', 'market_id', 'currency_id', 'state', 'price_minor', 'minimum_quantity', 'incoterm', 'lead_time_days', 'valid_from', 'valid_until', 'evidence_artifact_ids' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			self::assert_reference( $record['sku_id'], $skus, $path . '.sku_id' );
			self::assert_reference( $record['seller_id'], $sellers, $path . '.seller_id' );
			self::assert_reference( $record['market_id'], $markets, $path . '.market_id' );
			self::assert_reference( $record['currency_id'], $currencies, $path . '.currency_id' );
			if ( $markets[ $record['market_id'] ]['currency_id'] !== $record['currency_id'] ) {
				throw new RuntimeException( $path . '.market_currency' );
			}
			self::assert_enum( $record['state'], $vocabulary['supplier_offer_states'], $path . '.state' );
			self::assert_nullable_minor_amount( $record['price_minor'], $path . '.price_minor' );
			self::assert_decimal( $record['minimum_quantity'], $path . '.minimum_quantity', false );
			self::assert_text( $record['incoterm'], $path . '.incoterm', 80, true );
			if ( ! is_int( $record['lead_time_days'] ) || $record['lead_time_days'] < 0 ) {
				throw new RuntimeException( $path . '.lead_time_days' );
			}
			self::assert_optional_date( $record['valid_from'], $path . '.valid_from' );
			self::assert_optional_date( $record['valid_until'], $path . '.valid_until' );
			self::assert_date_window( $record['valid_from'], $record['valid_until'], $path . '.validity' );
			self::assert_reference_list( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids', false );
			if ( in_array( $record['state'], array( 'approved', 'active' ), true ) ) {
				if ( null === $record['price_minor'] || $record['price_minor'] <= 0
					|| ! in_array( $skus[ $record['sku_id'] ]['state'], array( 'verified_sku', 'active' ), true )
					|| '' === $record['incoterm'] || '' === $record['valid_from'] || '' === $record['valid_until'] ) {
					throw new RuntimeException( $path . '.approval_gate' );
				}
				self::assert_approval_evidence( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids' );
				foreach ( $record['evidence_artifact_ids'] as $artifact_id ) {
					if ( ! in_array( $artifacts[ $artifact_id ]['source_id'], $sellers[ $record['seller_id'] ]['source_ids'], true ) ) {
						throw new RuntimeException( $path . '.seller_evidence' );
					}
				}
			}
		}
		return $index;
	}

	private static function validate_observations( $records, $skus, $variants, $products, $sellers, $markets, $currencies, $artifacts, $science_entities, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'market_observations' );
		foreach ( $records as $offset => $record ) {
			$path = 'market_observations.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'sku_id', 'seller_id', 'market_id', 'currency_id', 'amount_minor', 'tax_state', 'shipping_state', 'availability_state', 'observed_at', 'evidence_artifact_id', 'source_entity_id', 'normalization', 'comparability', 'state', 'supersedes_id' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 130 );
			self::assert_reference( $record['sku_id'], $skus, $path . '.sku_id' );
			self::assert_reference( $record['seller_id'], $sellers, $path . '.seller_id' );
			self::assert_reference( $record['market_id'], $markets, $path . '.market_id' );
			self::assert_reference( $record['currency_id'], $currencies, $path . '.currency_id' );
			self::assert_minor_amount( $record['amount_minor'], $path . '.amount_minor' );
			self::assert_enum( $record['tax_state'], $vocabulary['tax_states'], $path . '.tax_state' );
			self::assert_identifier( $record['shipping_state'], $path . '.shipping_state', 80 );
			self::assert_identifier( $record['availability_state'], $path . '.availability_state', 80 );
			self::assert_timestamp( $record['observed_at'], $path . '.observed_at' );
			self::assert_reference( $record['evidence_artifact_id'], $artifacts, $path . '.evidence_artifact_id' );
			self::assert_reference( $record['source_entity_id'], $science_entities, $path . '.source_entity_id' );
			if ( ! in_array( $science_entities[ $record['source_entity_id'] ]['type'], array( 'retail_listing', 'market_observation' ), true ) ) {
				throw new RuntimeException( $path . '.source_entity_type' );
			}
			self::assert_normalization( $record['normalization'], $path . '.normalization' );
			self::assert_enum( $record['comparability'], $vocabulary['comparability_states'], $path . '.comparability' );
			self::assert_enum( $record['state'], $vocabulary['observation_states'], $path . '.state' );
			self::assert_optional_reference( $record['supersedes_id'], $index, $path . '.supersedes_id' );

			$sku = $skus[ $record['sku_id'] ];
			$variant = $variants[ $sku['variant_id'] ];
			$product = $products[ $variant['product_id'] ];
			$source_entity = $science_entities[ $record['source_entity_id'] ];
			$targets = array( $source_entity['id'], $source_entity['parent_id'] );
			$source_ids = array();
			foreach ( $source_entity['facts'] as $fact ) {
				$source_ids = array_merge( $source_ids, $fact['source_ids'] );
			}
			if ( ! in_array( $product['knowledge_entity_id'], $targets, true ) ) {
				throw new RuntimeException( $path . '.subject_mismatch' );
			}
			if ( ! in_array( $artifacts[ $record['evidence_artifact_id'] ]['source_id'], $source_ids, true ) ) {
				throw new RuntimeException( $path . '.evidence_source_mismatch' );
			}
			$market = $markets[ $record['market_id'] ];
			if ( $market['currency_id'] !== $record['currency_id'] ) {
				throw new RuntimeException( $path . '.market_currency_mismatch' );
			}
		}
		return $index;
	}

	private static function validate_landed_costs( $records, $skus, $markets, $currencies, $observations, $supplier_offers, $artifacts, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'landed_cost_scenarios' );
		foreach ( $records as $offset => $record ) {
			$path = 'landed_cost_scenarios.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'sku_id', 'destination_market_id', 'source_observation_id', 'supplier_offer_id', 'scenario_state', 'incoterm', 'order_quantity', 'sellable_units', 'source_subtotal_minor', 'converted_source_cost_minor', 'fx', 'cost_lines', 'shrinkage_rate_decimal', 'landed_cost_minor', 'currency_id', 'calculation_method', 'formula', 'version', 'review_at' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			self::assert_reference( $record['sku_id'], $skus, $path . '.sku_id' );
			self::assert_reference( $record['destination_market_id'], $markets, $path . '.destination_market_id' );
			self::assert_optional_reference( $record['source_observation_id'], $observations, $path . '.source_observation_id' );
			self::assert_optional_reference( $record['supplier_offer_id'], $supplier_offers, $path . '.supplier_offer_id' );
			if ( '' === $record['source_observation_id'] && '' === $record['supplier_offer_id'] ) {
				throw new RuntimeException( $path . '.source_required' );
			}
			self::assert_enum( $record['scenario_state'], $vocabulary['scenario_states'], $path . '.scenario_state' );
			self::assert_text( $record['incoterm'], $path . '.incoterm', 80, true );
			self::assert_decimal( $record['order_quantity'], $path . '.order_quantity', false );
			self::assert_decimal( $record['sellable_units'], $path . '.sellable_units', false );
			self::assert_nullable_minor_amount( $record['source_subtotal_minor'], $path . '.source_subtotal_minor' );
			self::assert_nullable_minor_amount( $record['converted_source_cost_minor'], $path . '.converted_source_cost_minor' );
			self::assert_fx( $record['fx'], $currencies, $path . '.fx' );
			self::assert_cost_lines( $record['cost_lines'], $currencies, $artifacts, $vocabulary['cost_line_states'], $path . '.cost_lines' );
			self::assert_rate( $record['shrinkage_rate_decimal'], $path . '.shrinkage_rate_decimal' );
			self::assert_nullable_minor_amount( $record['landed_cost_minor'], $path . '.landed_cost_minor' );
			self::assert_reference( $record['currency_id'], $currencies, $path . '.currency_id' );
			if ( $markets[ $record['destination_market_id'] ]['currency_id'] !== $record['currency_id'] ) {
				throw new RuntimeException( $path . '.destination_currency' );
			}
			if ( '' !== $record['source_observation_id'] && $record['sku_id'] !== $observations[ $record['source_observation_id'] ]['sku_id'] ) {
				throw new RuntimeException( $path . '.observation_sku' );
			}
			if ( '' !== $record['supplier_offer_id'] && $record['sku_id'] !== $supplier_offers[ $record['supplier_offer_id'] ]['sku_id'] ) {
				throw new RuntimeException( $path . '.supplier_offer_sku' );
			}
			self::assert_identifier( $record['calculation_method'], $path . '.calculation_method', 100 );
			self::assert_text( $record['formula'], $path . '.formula', 500 );
			self::assert_identifier( $record['version'], $path . '.version', 80 );
			self::assert_date( $record['review_at'], $path . '.review_at' );
			if ( 'approved' === $record['scenario_state'] ) {
				if ( null === $record['landed_cost_minor'] || null === $record['source_subtotal_minor'] || null === $record['converted_source_cost_minor']
					|| empty( $record['cost_lines'] ) || '' === $record['supplier_offer_id']
					|| 'sum_cost_lines_divided_by_sellable_units_ceiling_minor' !== $record['calculation_method'] ) {
					throw new RuntimeException( $path . '.approved_without_machine_costs' );
				}
				$supplier_offer = $supplier_offers[ $record['supplier_offer_id'] ];
				if ( ! in_array( $supplier_offer['state'], array( 'approved', 'active' ), true ) ) {
					throw new RuntimeException( $path . '.supplier_offer_state' );
				}
				if ( self::compare_decimals( $record['order_quantity'], $supplier_offer['minimum_quantity'] ) < 0 ) {
					throw new RuntimeException( $path . '.minimum_quantity' );
				}
				if ( '' !== $record['source_observation_id'] && 'recorded' !== $observations[ $record['source_observation_id'] ]['state'] ) {
					throw new RuntimeException( $path . '.observation_state' );
				}
				if ( '' !== $supplier_offer['incoterm'] && $record['incoterm'] !== $supplier_offer['incoterm'] ) {
					throw new RuntimeException( $path . '.incoterm_mismatch' );
				}
				$expected_source_subtotal = self::multiply_minor_by_decimal_ceiling( $supplier_offer['price_minor'], $record['order_quantity'], $path . '.source_subtotal_minor' );
				if ( $record['source_subtotal_minor'] !== $expected_source_subtotal ) {
					throw new RuntimeException( $path . '.source_subtotal_formula' );
				}
				$source_currency_id = $supplier_offer['currency_id'];
				if ( $source_currency_id === $record['currency_id'] ) {
					if ( self::fx_is_configured( $record['fx'] ) || $record['converted_source_cost_minor'] !== $record['source_subtotal_minor'] ) {
						throw new RuntimeException( $path . '.same_currency_conversion' );
					}
				} else {
					self::assert_fx_contract( $record['fx'], $source_currency_id, $record['currency_id'], $currencies, $path . '.fx' );
					$converted = self::convert_minor_half_up( $record['source_subtotal_minor'], $record['fx']['rate_decimal'], $currencies[ $source_currency_id ]['minor_unit_digits'], $currencies[ $record['currency_id'] ]['minor_unit_digits'], $path . '.converted_source_cost_minor' );
					if ( $record['converted_source_cost_minor'] !== $converted ) {
						throw new RuntimeException( $path . '.fx_formula' );
					}
				}
				self::assert_approved_cost_lines( $record['cost_lines'], $record['currency_id'], $artifacts, $path . '.cost_lines' );
				$source_line = self::find_cost_line( $record['cost_lines'], 'source_goods' );
				if ( ! is_array( $source_line ) || $source_line['amount_minor'] !== $record['converted_source_cost_minor']
					|| ! in_array( $source_line['evidence_artifact_id'], $supplier_offer['evidence_artifact_ids'], true ) ) {
					throw new RuntimeException( $path . '.source_goods_line' );
				}
				$total_cost = self::sum_cost_lines( $record['cost_lines'], $path . '.cost_lines' );
				$expected_landed = self::divide_minor_by_decimal_ceiling( $total_cost, $record['sellable_units'], $path . '.landed_cost_minor' );
				if ( $record['landed_cost_minor'] !== $expected_landed ) {
					throw new RuntimeException( $path . '.landed_cost_formula' );
				}
			}
		}
		return $index;
	}

	private static function validate_margin_scenarios( $records, $currencies, $cost_scenarios, $artifacts, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'margin_scenarios' );
		foreach ( $records as $offset => $record ) {
			$path = 'margin_scenarios.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'channel_offer_id', 'landed_cost_scenario_id', 'scenario_state', 'currency_id', 'net_revenue_minor', 'landed_cost_minor', 'revenue_adjustment_lines', 'variable_cost_lines', 'contribution_minor', 'margin_rate_decimal', 'break_even_units', 'formula', 'evidence_artifact_ids', 'version', 'review_at' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			self::assert_identifier( $record['channel_offer_id'], $path . '.channel_offer_id', 120 );
			self::assert_reference( $record['landed_cost_scenario_id'], $cost_scenarios, $path . '.landed_cost_scenario_id' );
			self::assert_enum( $record['scenario_state'], $vocabulary['scenario_states'], $path . '.scenario_state' );
			self::assert_reference( $record['currency_id'], $currencies, $path . '.currency_id' );
			foreach ( array( 'net_revenue_minor', 'landed_cost_minor', 'contribution_minor' ) as $money_key ) {
				self::assert_nullable_minor_amount( $record[ $money_key ], $path . '.' . $money_key );
			}
			self::assert_revenue_adjustment_lines( $record['revenue_adjustment_lines'], $currencies, $artifacts, $vocabulary['cost_line_states'], $path . '.revenue_adjustment_lines' );
			self::assert_cost_lines( $record['variable_cost_lines'], $currencies, $artifacts, $vocabulary['cost_line_states'], $path . '.variable_cost_lines' );
			self::assert_optional_rate( $record['margin_rate_decimal'], $path . '.margin_rate_decimal' );
			if ( ! is_int( $record['break_even_units'] ) || $record['break_even_units'] < 0 ) {
				throw new RuntimeException( $path . '.break_even_units' );
			}
			self::assert_text( $record['formula'], $path . '.formula', 500 );
			self::assert_reference_list( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids', true );
			self::assert_identifier( $record['version'], $path . '.version', 80 );
			self::assert_date( $record['review_at'], $path . '.review_at' );
			if ( 'approved' === $record['scenario_state'] ) {
				if ( null === $record['net_revenue_minor'] || null === $record['landed_cost_minor'] || null === $record['contribution_minor'] || null === $record['margin_rate_decimal'] ) {
					throw new RuntimeException( $path . '.approved_without_calculation' );
				}
				$cost_scenario = $cost_scenarios[ $record['landed_cost_scenario_id'] ];
				if ( 'approved' !== $cost_scenario['scenario_state']
					|| $record['currency_id'] !== $cost_scenario['currency_id']
					|| $record['landed_cost_minor'] !== $cost_scenario['landed_cost_minor'] ) {
					throw new RuntimeException( $path . '.landed_cost_contract' );
				}
				if ( $record['net_revenue_minor'] <= 0 ) {
					throw new RuntimeException( $path . '.net_revenue' );
				}
				self::assert_approved_adjustment_lines( $record['revenue_adjustment_lines'], $record['currency_id'], $artifacts, $path . '.revenue_adjustment_lines' );
				self::assert_approved_cost_lines( $record['variable_cost_lines'], $record['currency_id'], $artifacts, $path . '.variable_cost_lines' );
				self::assert_approval_evidence( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids' );
				$variable = 0;
				foreach ( $record['variable_cost_lines'] as $line ) {
					if ( $record['currency_id'] !== $line['currency_id'] ) {
						throw new RuntimeException( $path . '.variable_currency' );
					}
					$variable += $line['amount_minor'];
				}
				$expected = $record['net_revenue_minor'] - $record['landed_cost_minor'] - $variable;
				if ( $expected !== $record['contribution_minor'] ) {
					throw new RuntimeException( $path . '.contribution_formula' );
				}
				self::assert_margin_rate( $record['contribution_minor'], $record['net_revenue_minor'], $record['margin_rate_decimal'], $path . '.margin_rate_decimal' );
			}
		}
		return $index;
	}

	private static function validate_channel_offers( $records, $skus, $variants, $products, $markets, $tax_zones, $sellers, $channels, $connectors, $currencies, $supplier_offers, $cost_scenarios, $margin_scenarios, $artifacts, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'channel_offers' );
		foreach ( $records as $offset => $record ) {
			$path = 'channel_offers.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'sku_id', 'market_id', 'channel_id', 'customer_segment', 'price_tier', 'price_basis', 'currency_id', 'price_minor', 'tax_state', 'minimum_quantity', 'stock_policy', 'fulfillment_policy', 'state', 'approved_at', 'approved_by', 'woo_product_code', 'landed_cost_scenario_id', 'margin_scenario_id', 'evidence_artifact_ids', 'valid_from', 'valid_until', 'kiosk_projection' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			self::assert_reference( $record['sku_id'], $skus, $path . '.sku_id' );
			self::assert_reference( $record['market_id'], $markets, $path . '.market_id' );
			self::assert_reference( $record['channel_id'], $channels, $path . '.channel_id' );
			if ( ! in_array( $record['market_id'], $channels[ $record['channel_id'] ]['market_ids'], true ) ) {
				throw new RuntimeException( $path . '.channel_market' );
			}
			if ( $markets[ $record['market_id'] ]['currency_id'] !== $record['currency_id'] ) {
				throw new RuntimeException( $path . '.market_currency' );
			}
			self::assert_enum( $record['customer_segment'], $vocabulary['customer_segments'], $path . '.customer_segment' );
			self::assert_identifier( $record['price_tier'], $path . '.price_tier', 80 );
			self::assert_enum( $record['price_basis'], $vocabulary['price_bases'], $path . '.price_basis' );
			self::assert_reference( $record['currency_id'], $currencies, $path . '.currency_id' );
			self::assert_nullable_minor_amount( $record['price_minor'], $path . '.price_minor' );
			self::assert_enum( $record['tax_state'], $vocabulary['tax_states'], $path . '.tax_state' );
			self::assert_decimal( $record['minimum_quantity'], $path . '.minimum_quantity', false );
			self::assert_enum( $record['stock_policy'], $vocabulary['inventory_policies'], $path . '.stock_policy' );
			self::assert_identifier( $record['fulfillment_policy'], $path . '.fulfillment_policy', 100 );
			self::assert_enum( $record['state'], $vocabulary['channel_offer_states'], $path . '.state' );
			self::assert_optional_timestamp( $record['approved_at'], $path . '.approved_at' );
			self::assert_text( $record['approved_by'], $path . '.approved_by', 120, true );
			self::assert_text( $record['woo_product_code'], $path . '.woo_product_code', 80, true );
			self::assert_optional_reference( $record['landed_cost_scenario_id'], $cost_scenarios, $path . '.landed_cost_scenario_id' );
			self::assert_optional_reference( $record['margin_scenario_id'], $margin_scenarios, $path . '.margin_scenario_id' );
			self::assert_reference_list( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids', true );
			self::assert_optional_date( $record['valid_from'], $path . '.valid_from' );
			self::assert_optional_date( $record['valid_until'], $path . '.valid_until' );
			self::assert_date_window( $record['valid_from'], $record['valid_until'], $path . '.validity' );
			self::assert_kiosk_projection( $record['kiosk_projection'], $path . '.kiosk_projection' );
			if ( null !== $record['price_minor'] ) {
				foreach ( $record['kiosk_projection']['modifiers'] as $modifier ) {
					if ( $record['price_minor'] + $modifier['price_delta_minor'] < 0 ) {
						throw new RuntimeException( $path . '.modifier_effective_price' );
					}
				}
			}

			if ( in_array( $record['state'], array( 'approved', 'active' ), true ) ) {
				$sku = $skus[ $record['sku_id'] ];
				$variant = $variants[ $sku['variant_id'] ];
				$product = $products[ $variant['product_id'] ];
				$market = $markets[ $record['market_id'] ];
				$channel = $channels[ $record['channel_id'] ];
				$connector = $connectors[ $channels[ $record['channel_id'] ]['connector_profile_id'] ];
				if ( ! in_array( $sku['state'], array( 'verified_sku', 'active' ), true )
					|| ! in_array( $variant['state'], array( 'verified_variant', 'active' ), true )
					|| ! in_array( $product['state'], array( 'verified_product', 'active' ), true )
					|| 'cleared' !== $sku['compliance_state']
					|| '' === $sku['woo_product_code']
					|| $record['woo_product_code'] !== $sku['woo_product_code']
					|| null === $record['price_minor']
					|| $record['price_minor'] <= 0
					|| 'approved' !== $record['tax_state']
					|| '' === $record['landed_cost_scenario_id']
					|| '' === $record['margin_scenario_id']
					|| '' === $record['approved_at']
					|| '' === $record['approved_by']
					|| empty( $record['evidence_artifact_ids'] )
					|| ! in_array( $connector['binding_state'], array( 'internal_bound', 'bound' ), true ) ) {
					throw new RuntimeException( $path . '.approval_gate' );
				}
				$cost_scenario = $cost_scenarios[ $record['landed_cost_scenario_id'] ];
				$margin_scenario = $margin_scenarios[ $record['margin_scenario_id'] ];
				if ( $record['market_id'] !== $cost_scenario['destination_market_id'] ) {
					throw new RuntimeException( $path . '.destination_market' );
				}
				if ( 'approved' !== $cost_scenario['scenario_state']
					|| $record['sku_id'] !== $cost_scenario['sku_id']
					|| $record['currency_id'] !== $cost_scenario['currency_id']
					|| 'approved' !== $margin_scenario['scenario_state']
					|| $record['id'] !== $margin_scenario['channel_offer_id']
					|| $record['landed_cost_scenario_id'] !== $margin_scenario['landed_cost_scenario_id']
					|| $record['currency_id'] !== $margin_scenario['currency_id']
					|| $cost_scenario['landed_cost_minor'] !== $margin_scenario['landed_cost_minor'] ) {
					throw new RuntimeException( $path . '.scenario_gate' );
				}
				$net_revenue = $record['price_minor'] + self::sum_signed_lines( $margin_scenario['revenue_adjustment_lines'], $path . '.revenue_adjustment_lines' );
				if ( $net_revenue !== $margin_scenario['net_revenue_minor'] ) {
					throw new RuntimeException( $path . '.net_revenue_bridge' );
				}
				if ( 'gross_tax_inclusive' === $record['price_basis'] && ! self::has_line_code( $margin_scenario['revenue_adjustment_lines'], 'tax' ) ) {
					throw new RuntimeException( $path . '.tax_adjustment_required' );
				}
				$required_evidence = array_merge( $margin_scenario['evidence_artifact_ids'], $supplier_offers[ $cost_scenario['supplier_offer_id'] ]['evidence_artifact_ids'] );
				foreach ( array_merge( $cost_scenario['cost_lines'], $margin_scenario['variable_cost_lines'], $margin_scenario['revenue_adjustment_lines'] ) as $line ) {
					if ( '' !== $line['evidence_artifact_id'] ) {
						$required_evidence[] = $line['evidence_artifact_id'];
					}
				}
				$required_evidence = array_values( array_unique( $required_evidence ) );
				self::assert_approval_evidence( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids' );
				foreach ( $required_evidence as $artifact_id ) {
					if ( ! in_array( $artifact_id, $record['evidence_artifact_ids'], true ) ) {
						throw new RuntimeException( $path . '.evidence_chain' );
					}
				}
				if ( 'active' === $record['state'] ) {
					if ( 'active' !== $sku['state'] || 'active' !== $variant['state'] || 'active' !== $product['state']
						|| 'active' !== $market['state'] || 'active' !== $channel['state']
						|| 'woocommerce_managed' !== $record['stock_policy'] || 'woocommerce_managed' !== $sku['inventory_policy']
						|| '' === $market['seller_of_record_id'] || 'legally_verified' !== $sellers[ $market['seller_of_record_id'] ]['legal_identity_state'] ) {
						throw new RuntimeException( $path . '.activation_gate' );
					}
					foreach ( $market['tax_zone_ids'] as $tax_zone_id ) {
						if ( 'approved' !== $tax_zones[ $tax_zone_id ]['state'] ) {
							throw new RuntimeException( $path . '.market_tax_gate' );
						}
					}
				}
			}
		}
		return $index;
	}

	private static function validate_margin_offer_links( $margin_scenarios, $offers ) {
		foreach ( $margin_scenarios as $id => $scenario ) {
			if ( ! isset( $offers[ $scenario['channel_offer_id'] ] ) ) {
				throw new RuntimeException( 'margin_scenarios.' . $id . '.channel_offer_id' );
			}
			if ( $offers[ $scenario['channel_offer_id'] ]['margin_scenario_id'] !== $id ) {
				throw new RuntimeException( 'margin_scenarios.' . $id . '.reverse_link' );
			}
		}
	}

	private static function validate_bundles( $records, $skus, $markets, $channels, $offers, $artifacts, $vocabulary ) {
		self::index_records( $records, 'id', 'bundles' );
		foreach ( $records as $offset => $record ) {
			$path = 'bundles.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'name', 'state', 'market_ids', 'channel_ids', 'components', 'inventory_policy', 'channel_offer_id', 'evidence_artifact_ids', 'review_at' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			self::assert_translation( $record['name'], $path . '.name', 200 );
			self::assert_enum( $record['state'], $vocabulary['bundle_states'], $path . '.state' );
			self::assert_reference_list( $record['market_ids'], $markets, $path . '.market_ids', false );
			self::assert_reference_list( $record['channel_ids'], $channels, $path . '.channel_ids', false );
			self::assert_list( $record['components'], $path . '.components', false );
			$seen = array();
			foreach ( $record['components'] as $component_offset => $component ) {
				$item_path = $path . '.components.' . $component_offset;
				self::assert_exact_keys( $component, array( 'sku_id', 'quantity_decimal', 'unit_code', 'substitution_group', 'required' ), $item_path );
				self::assert_reference( $component['sku_id'], $skus, $item_path . '.sku_id' );
				if ( isset( $seen[ $component['sku_id'] ] ) ) {
					throw new RuntimeException( $item_path . '.duplicate_sku' );
				}
				$seen[ $component['sku_id'] ] = true;
				self::assert_decimal( $component['quantity_decimal'], $item_path . '.quantity_decimal', false );
				self::assert_identifier( $component['unit_code'], $item_path . '.unit_code', 40 );
				self::assert_identifier( $component['substitution_group'], $item_path . '.substitution_group', 80, true );
				if ( ! is_bool( $component['required'] ) ) {
					throw new RuntimeException( $item_path . '.required' );
				}
			}
			self::assert_enum( $record['inventory_policy'], $vocabulary['inventory_policies'], $path . '.inventory_policy' );
			self::assert_optional_reference( $record['channel_offer_id'], $offers, $path . '.channel_offer_id' );
			self::assert_reference_list( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids', true );
			self::assert_date( $record['review_at'], $path . '.review_at' );
			if ( in_array( $record['state'], array( 'approved', 'active' ), true ) ) {
				if ( '' === $record['channel_offer_id'] ) {
					throw new RuntimeException( $path . '.approved_without_offer' );
				}
				$offer = $offers[ $record['channel_offer_id'] ];
				$allowed_offer_states = 'active' === $record['state'] ? array( 'active' ) : array( 'approved', 'active' );
				if ( ! in_array( $offer['state'], $allowed_offer_states, true )
					|| ! in_array( $offer['market_id'], $record['market_ids'], true )
					|| ! in_array( $offer['channel_id'], $record['channel_ids'], true ) ) {
					throw new RuntimeException( $path . '.offer_scope' );
				}
				foreach ( $record['components'] as $component ) {
					$allowed_sku_states = 'active' === $record['state'] ? array( 'active' ) : array( 'verified_sku', 'active' );
					if ( ! in_array( $skus[ $component['sku_id'] ]['state'], $allowed_sku_states, true ) ) {
						throw new RuntimeException( $path . '.component_state' );
					}
				}
				self::assert_approval_evidence( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids' );
			}
		}
	}

	private static function validate_edges( $records, $skus, $artifacts, $vocabulary ) {
		self::index_records( $records, 'id', 'merchandising_edges' );
		foreach ( $records as $offset => $record ) {
			$path = 'merchandising_edges.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'type', 'source_sku_id', 'target_sku_id', 'reason', 'evidence_artifact_ids', 'state' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 120 );
			self::assert_enum( $record['type'], $vocabulary['edge_types'], $path . '.type' );
			self::assert_reference( $record['source_sku_id'], $skus, $path . '.source_sku_id' );
			self::assert_reference( $record['target_sku_id'], $skus, $path . '.target_sku_id' );
			if ( $record['source_sku_id'] === $record['target_sku_id'] ) {
				throw new RuntimeException( $path . '.self_reference' );
			}
			self::assert_translation( $record['reason'], $path . '.reason', 500 );
			self::assert_reference_list( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids', true );
			self::assert_enum( $record['state'], $vocabulary['edge_states'], $path . '.state' );
			if ( in_array( $record['state'], array( 'approved', 'active' ), true ) ) {
				$allowed_sku_states = 'active' === $record['state'] ? array( 'active' ) : array( 'verified_sku', 'active' );
				if ( ! in_array( $skus[ $record['source_sku_id'] ]['state'], $allowed_sku_states, true )
					|| ! in_array( $skus[ $record['target_sku_id'] ]['state'], $allowed_sku_states, true ) ) {
					throw new RuntimeException( $path . '.sku_state' );
				}
				self::assert_approval_evidence( $record['evidence_artifact_ids'], $artifacts, $path . '.evidence_artifact_ids' );
			}
		}
	}

	private static function validate_integration_consumers( $records, $connectors, $markets, $channels, $vocabulary ) {
		$index = self::index_records( $records, 'id', 'integration_consumers' );
		$key_ids = array();
		foreach ( $records as $offset => $record ) {
			$path = 'integration_consumers.' . $offset;
			self::assert_exact_keys( $record, array( 'id', 'key_id', 'connector_profile_id', 'market_ids', 'channel_ids', 'state', 'credential_version', 'notes' ), $path );
			self::assert_identifier( $record['id'], $path . '.id', 100 );
			self::assert_identifier( $record['key_id'], $path . '.key_id', 100 );
			if ( isset( $key_ids[ $record['key_id'] ] ) ) {
				throw new RuntimeException( $path . '.key_id_collision' );
			}
			$key_ids[ $record['key_id'] ] = true;
			self::assert_reference( $record['connector_profile_id'], $connectors, $path . '.connector_profile_id' );
			self::assert_reference_list( $record['market_ids'], $markets, $path . '.market_ids', false );
			self::assert_reference_list( $record['channel_ids'], $channels, $path . '.channel_ids', false );
			self::assert_enum( $record['state'], $vocabulary['integration_consumer_states'], $path . '.state' );
			if ( ! is_int( $record['credential_version'] ) || $record['credential_version'] < 1 ) {
				throw new RuntimeException( $path . '.credential_version' );
			}
			self::assert_translation( $record['notes'], $path . '.notes', 1000 );
			foreach ( $record['channel_ids'] as $channel_id ) {
				if ( $channels[ $channel_id ]['connector_profile_id'] !== $record['connector_profile_id']
					|| ! empty( array_diff( $channels[ $channel_id ]['market_ids'], $record['market_ids'] ) ) ) {
					throw new RuntimeException( $path . '.channel_scope' );
				}
			}
			if ( 'active' === $record['state'] && ! in_array( $connectors[ $record['connector_profile_id'] ]['binding_state'], array( 'internal_bound', 'bound' ), true ) ) {
				throw new RuntimeException( $path . '.connector_state' );
			}
		}
		return $index;
	}

	private static function validate_connector_channel_links( $connectors, $channels ) {
		foreach ( $connectors as $connector_id => $connector ) {
			foreach ( $connector['channel_ids'] as $channel_id ) {
				if ( ! isset( $channels[ $channel_id ] ) || $channels[ $channel_id ]['connector_profile_id'] !== $connector_id ) {
					throw new RuntimeException( 'connector_profiles.' . $connector_id . '.channel_links' );
				}
			}
		}
	}

	public static function assert_invariants() {
		$registry = self::registry( true );
		if ( self::is_error( $registry ) ) {
			throw new RuntimeException( 'culinary-commerce-registry-invariants' );
		}
		return true;
	}

	public static function status() {
		$registry = self::registry();
		if ( self::is_error( $registry ) ) {
			return array( 'ready' => false, 'registry_valid' => false, 'commerce_ready' => false, 'version' => '', 'products' => 0, 'skus' => 0, 'observations' => 0, 'active_offers' => 0, 'digest' => '' );
		}
		$today = gmdate( 'Y-m-d' );
		$active = array_filter(
			$registry['channel_offers'],
			static function ( $offer ) use ( $today ) {
				return self::offer_is_effective( $offer, $today );
			}
		);
		return array(
			'ready'          => true,
			'registry_valid' => true,
			'commerce_ready' => 0 < count( $active ),
			'version'        => $registry['version'],
			'products'       => count( $registry['products'] ),
			'skus'           => count( $registry['skus'] ),
			'observations'   => count( $registry['market_observations'] ),
			'active_offers'  => count( $active ),
			'digest'         => self::registry_digest( $registry ),
		);
	}

	public static function editorial_snapshot() {
		$registry = self::registry();
		if ( self::is_error( $registry ) ) {
			return array();
		}
		return array( 'digest' => self::registry_digest( $registry ), 'registry' => $registry );
	}

	/**
	 * Return public-safe source-market evidence for one culinary-science entity.
	 *
	 * This projection is deliberately separate from supplier offers, landed
	 * costs, margins, WooCommerce identity and channel offers. Its amounts are
	 * dated observations in the source market and are not Israeli sell prices.
	 *
	 * @param string $science_entity_id Culinary-science entity identifier.
	 * @param string $lang              Requested public language, he or en.
	 * @return array
	 */
	public static function public_market_context_for_science_entity( $science_entity_id, $lang = 'he' ) {
		if ( ! is_string( $science_entity_id )
			|| 1 !== preg_match( '/\A[a-z0-9][a-z0-9._:-]*\z/', $science_entity_id ) ) {
			return array();
		}

		$registry = self::registry();
		if ( self::is_error( $registry ) ) {
			return array();
		}

		$language  = is_string( $lang ) && 'en' === strtolower( $lang ) ? 'en' : 'he';
		$products  = self::index_records( $registry['products'], 'id', 'products' );
		$variants  = self::index_records( $registry['variants'], 'id', 'variants' );
		$skus      = self::index_records( $registry['skus'], 'id', 'skus' );
		$sellers   = self::index_records( $registry['sellers'], 'id', 'sellers' );
		$markets   = self::index_records( $registry['markets'], 'id', 'markets' );
		$currencies = self::index_records( $registry['currencies'], 'id', 'currencies' );
		$artifacts = self::index_records( $registry['evidence_artifacts'], 'id', 'evidence_artifacts' );
		$rows      = array();
		$scope_note = 'en' === $language
			? 'Dated evidence from the named source market in its original currency. Complete99 retail prices are presented in the store.'
			: 'תצפית מתועדת מהשוק הנקוב ובמטבע המקור. מחירי Complete99 לצרכן מוצגים בחנות.';

		foreach ( $registry['market_observations'] as $observation ) {
			if ( 'recorded' !== $observation['state']
				|| ! isset( $skus[ $observation['sku_id'] ], $sellers[ $observation['seller_id'] ], $markets[ $observation['market_id'] ], $currencies[ $observation['currency_id'] ], $artifacts[ $observation['evidence_artifact_id'] ] ) ) {
				continue;
			}

			$sku      = $skus[ $observation['sku_id'] ];
			$variant  = isset( $variants[ $sku['variant_id'] ] ) ? $variants[ $sku['variant_id'] ] : null;
			$product  = is_array( $variant ) && isset( $products[ $variant['product_id'] ] ) ? $products[ $variant['product_id'] ] : null;
			$market   = $markets[ $observation['market_id'] ];
			$artifact = $artifacts[ $observation['evidence_artifact_id'] ];
			$projection_policy = is_array( $variant )
				&& isset( $variant['attributes']['public_market_projection'] )
				&& 'public' === $variant['attributes']['public_market_projection']
				? 'public'
				: 'held';
			if ( ! is_array( $product )
				|| $science_entity_id !== $product['knowledge_entity_id']
				|| 'public' !== $projection_policy
				|| 'research_candidate' !== $product['state']
				|| 'research_candidate' !== $variant['state']
				|| 'research_candidate' !== $sku['state']
				|| 'source_price_observation' !== $market['purpose']
				|| 'source_observation' !== $market['state']
				|| ! in_array( $artifact['verification_state'], array( 'source_reviewed', 'snapshot_retained' ), true ) ) {
				continue;
			}

			$currency     = $currencies[ $observation['currency_id'] ];
			$product_name = self::localized_text( $product['name'], $language );
			$variant_name = self::localized_text( $variant['name'], $language );
			$label        = $product_name === $variant_name ? $product_name : $product_name . ', ' . $variant_name;
			$normalization = $observation['normalization'];
			$normalized_unit = $normalization['normalized_unit_code'];
			if ( '1' !== $normalization['normalized_quantity_decimal'] ) {
				$normalized_unit = $normalization['normalized_quantity_decimal'] . ' ' . $normalized_unit;
			}

			$rows[] = array(
				'label'             => $label,
				'amount'            => self::minor_amount_string( $observation['amount_minor'], $currency['minor_unit_digits'] ),
				'currency'          => $currency['code'],
				'normalized_amount' => null === $normalization['normalized_amount_minor']
					? null
					: self::minor_amount_string( $normalization['normalized_amount_minor'], $currency['minor_unit_digits'] ),
				'normalized_unit'   => $normalized_unit,
				'market'            => self::localized_text( $market['label'], $language ),
				'seller'            => self::localized_text( $sellers[ $observation['seller_id'] ]['name'], $language ),
				'observed_at'       => $observation['observed_at'],
				'availability'      => $observation['availability_state'],
				'comparability'     => $observation['comparability'],
				'tax_state'         => $observation['tax_state'],
				'shipping_state'    => $observation['shipping_state'],
				'source_url'        => $artifact['source_url'],
				'scope_note'        => $scope_note,
			);
		}

		usort(
			$rows,
			static function ( $left, $right ) {
				$date_compare = strcmp( $right['observed_at'], $left['observed_at'] );
				return 0 !== $date_compare ? $date_compare : strcmp( $left['label'], $right['label'] );
			}
		);
		return $rows;
	}

	public static function rest_editorial_snapshot() {
		$snapshot = self::editorial_snapshot();
		if ( empty( $snapshot ) ) {
			return self::error( 'complete99_commerce_registry_unavailable', 'The culinary commerce registry is unavailable.', 500 );
		}
		return rest_ensure_response(
			array(
				'schema'   => 'complete99-culinary-commerce-editorial/v1',
				'digest'   => $snapshot['digest'],
				'registry' => $snapshot['registry'],
			)
		);
	}

	/**
	 * Signed, vendor-neutral catalog projection for POS adapters.
	 *
	 * The endpoint never guesses a MyShop endpoint or payload. It exposes only
	 * active, approved channel offers from WooCommerce-owned catalog identity.
	 */
	public static function rest_pos_catalog( WP_REST_Request $request ) {
		$payload = json_decode( (string) $request->get_body(), true );
		if ( ! is_array( $payload ) ) {
			return self::error( 'complete99_pos_catalog_json', 'The POS catalog request must be valid JSON.', 400 );
		}
		try {
			self::assert_exact_keys( $payload, array( 'schema', 'consumer_id', 'market_id', 'channel_id', 'locale', 'cursor', 'limit' ), 'request' );
			if ( self::POS_REQUEST_SCHEMA !== $payload['schema'] ) {
				throw new RuntimeException( 'request.schema' );
			}
			self::assert_identifier( $payload['consumer_id'], 'request.consumer_id', 100 );
			self::assert_identifier( $payload['market_id'], 'request.market_id', 100 );
			self::assert_identifier( $payload['channel_id'], 'request.channel_id', 100 );
			self::assert_identifier( $payload['locale'], 'request.locale', 80 );
			if ( ! is_string( $payload['cursor'] ) || 1 !== preg_match( '/\A(?:|v1:[0-9]{1,8})\z/', $payload['cursor'] ) ) {
				throw new RuntimeException( 'request.cursor' );
			}
			if ( ! is_int( $payload['limit'] ) || $payload['limit'] < 1 || $payload['limit'] > self::MAX_PAGE_SIZE ) {
				throw new RuntimeException( 'request.limit' );
			}
		} catch ( Throwable $error ) {
			return self::error( 'complete99_pos_catalog_contract', 'The POS catalog request failed contract validation.', 422, array( 'path' => $error->getMessage() ) );
		}

		$registry = self::registry();
		if ( self::is_error( $registry ) ) {
			return $registry;
		}
		$consumer = self::find_record( $registry['integration_consumers'], $payload['consumer_id'] );
		if ( ! is_array( $consumer ) || 'active' !== $consumer['state']
			|| ! in_array( $payload['market_id'], $consumer['market_ids'], true )
			|| ! in_array( $payload['channel_id'], $consumer['channel_ids'], true ) ) {
			return self::error( 'complete99_pos_consumer_scope', 'The POS consumer is not authorized for this scope.', 403 );
		}
		$market = self::find_record( $registry['markets'], $payload['market_id'] );
		$channel = self::find_record( $registry['channels'], $payload['channel_id'] );
		if ( ! is_array( $market ) || ! is_array( $channel ) || ! in_array( $payload['market_id'], $channel['market_ids'], true ) || ! in_array( $payload['locale'], $market['locale_ids'], true ) ) {
			return self::error( 'complete99_pos_catalog_scope', 'The requested POS market, channel or locale is not configured.', 404 );
		}
		if ( ! in_array( $channel['channel_type'], array( 'kiosk', 'pos' ), true ) ) {
			return self::error( 'complete99_pos_catalog_channel', 'The requested channel is not a POS projection channel.', 422 );
		}

		$offset = '' === $payload['cursor'] ? 0 : (int) substr( $payload['cursor'], 3 );
		$items = self::pos_items( $registry, $payload['market_id'], $payload['channel_id'], $payload['locale'] );
		if ( self::is_error( $items ) ) {
			return $items;
		}
		$page = array_slice( $items, $offset, $payload['limit'] );
		$next_offset = $offset + count( $page );
		$next_cursor = $next_offset < count( $items ) ? 'v1:' . $next_offset : '';

		return rest_ensure_response(
			array(
				'schema'           => self::POS_RESPONSE_SCHEMA,
				'registry_version' => $registry['version'],
				'catalog_digest'   => self::registry_digest( $registry ),
				'consumer_id'      => $payload['consumer_id'],
				'market_id'        => $payload['market_id'],
				'channel_id'       => $payload['channel_id'],
				'locale'           => $payload['locale'],
				'count'            => count( $page ),
				'next_cursor'      => $next_cursor,
				'items'            => $page,
			)
		);
	}

	private static function pos_items( $registry, $market_id, $channel_id, $locale_id ) {
		$locale = self::find_record( $registry['locales'], $locale_id );
		$language = is_array( $locale ) && 'en' === $locale['language_code'] ? 'en' : 'he';
		$skus = self::index_records( $registry['skus'], 'id', 'skus' );
		$variants = self::index_records( $registry['variants'], 'id', 'variants' );
		$products = self::index_records( $registry['products'], 'id', 'products' );
		$currencies = self::index_records( $registry['currencies'], 'id', 'currencies' );
		$items = array();
		$today = gmdate( 'Y-m-d' );
		foreach ( $registry['channel_offers'] as $offer ) {
			if ( ! self::offer_is_effective( $offer, $today ) || $market_id !== $offer['market_id'] || $channel_id !== $offer['channel_id'] ) {
				continue;
			}
			$sku = $skus[ $offer['sku_id'] ];
			$variant = $variants[ $sku['variant_id'] ];
			$product = $products[ $variant['product_id'] ];
			$projection = $offer['kiosk_projection'];
			$runtime = self::woo_runtime_projection( $sku, $offer, $currencies[ $offer['currency_id'] ] );
			if ( self::is_error( $runtime ) ) {
				return $runtime;
			}
			$items[] = array(
				'offer_id'        => $offer['id'],
				'product_code'    => $sku['woo_product_code'],
				'sku'             => $sku['internal_code'],
				'name'            => $product['name'][ $language ],
				'variant_name'    => $variant['name'][ $language ],
				'price_minor'     => $offer['price_minor'],
				'currency_id'     => $offer['currency_id'],
				'tax_state'       => $offer['tax_state'],
				'category'        => $projection['category'][ $language ],
				'subcategory'     => $projection['subcategory'][ $language ],
				'image_url'       => '' !== $projection['image_url'] ? $projection['image_url'] : $runtime['image_url'],
				'food_tags'       => $projection['food_tags'],
				'allergens'       => $projection['allergens'],
				'modifiers'       => $projection['modifiers'],
				'availability'    => $runtime['availability'],
				'stock_status'    => $runtime['stock_status'],
				'stock_quantity'  => $runtime['stock_quantity'],
				'version'         => $projection['version'],
			);
		}
		usort(
			$items,
			static function ( $left, $right ) {
				return strcmp( $left['product_code'], $right['product_code'] );
			}
		);
		return $items;
	}

	private static function offer_is_effective( $offer, $date ) {
		return 'active' === $offer['state']
			&& ( '' === $offer['valid_from'] || $offer['valid_from'] <= $date )
			&& ( '' === $offer['valid_until'] || $offer['valid_until'] >= $date );
	}

	private static function woo_runtime_projection( $sku, $offer, $currency ) {
		if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! function_exists( 'wc_get_product' ) ) {
			return self::error( 'complete99_pos_woocommerce_unavailable', 'WooCommerce catalog authority is unavailable.', 503 );
		}
		$product_id = absint( wc_get_product_id_by_sku( $sku['woo_product_code'] ) );
		$product = $product_id > 0 ? wc_get_product( $product_id ) : false;
		if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_sku' ) )
			|| ! hash_equals( $sku['woo_product_code'], (string) $product->get_sku( 'edit' ) )
			|| 'publish' !== (string) $product->get_status( 'edit' ) ) {
			return self::error( 'complete99_pos_product_identity', 'An active POS offer does not match a published WooCommerce product.', 409 );
		}
		if ( function_exists( 'get_woocommerce_currency' ) && $currency['code'] !== (string) get_woocommerce_currency() ) {
			return self::error( 'complete99_pos_currency', 'The active POS offer currency does not match WooCommerce.', 409 );
		}
		$woo_price = self::decimal_price_to_minor( (string) $product->get_price( 'edit' ), $currency['minor_unit_digits'], 'woocommerce.price' );
		if ( self::is_error( $woo_price ) || $offer['price_minor'] !== $woo_price ) {
			return self::error( 'complete99_pos_price', 'The active POS offer price does not match WooCommerce.', 409 );
		}
		$is_in_stock = is_callable( array( $product, 'is_in_stock' ) ) && $product->is_in_stock();
		$is_purchasable = is_callable( array( $product, 'is_purchasable' ) ) && $product->is_purchasable();
		$stock_status = is_callable( array( $product, 'get_stock_status' ) ) ? (string) $product->get_stock_status( 'edit' ) : ( $is_in_stock ? 'instock' : 'outofstock' );
		$stock_quantity = is_callable( array( $product, 'get_stock_quantity' ) ) ? $product->get_stock_quantity( 'edit' ) : null;
		$image_url = '';
		if ( is_callable( array( $product, 'get_image_id' ) ) && function_exists( 'wp_get_attachment_image_url' ) ) {
			$image_id = absint( $product->get_image_id( 'edit' ) );
			$image_url = $image_id > 0 ? (string) wp_get_attachment_image_url( $image_id, 'full' ) : '';
		}
		return array(
			'availability'   => $is_in_stock && $is_purchasable ? 'in_stock' : 'out_of_stock',
			'stock_status'   => $stock_status,
			'stock_quantity' => is_numeric( $stock_quantity ) ? (string) $stock_quantity : null,
			'image_url'      => $image_url,
		);
	}

	private static function science_registry( $fresh = false ) {
		if ( ! class_exists( 'Complete99_Culinary_Science', false ) || ! is_callable( array( 'Complete99_Culinary_Science', 'registry' ) ) ) {
			return self::error( 'complete99_science_required', 'The culinary science registry is required.', 500 );
		}
		return Complete99_Culinary_Science::registry( $fresh );
	}

	private static function registry_digest( $registry ) {
		$canonical = self::canonical_value( $registry );
		$flags = defined( 'JSON_UNESCAPED_UNICODE' ) ? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES : 0;
		return hash( 'sha256', wp_json_encode( $canonical, $flags ) );
	}

	private static function canonical_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( self::is_list( $value ) ) {
			return array_map( array( __CLASS__, 'canonical_value' ), $value );
		}
		ksort( $value, SORT_STRING );
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonical_value( $item );
		}
		return $value;
	}

	private static function localized_text( $value, $language ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		if ( isset( $value[ $language ] ) && is_string( $value[ $language ] ) && '' !== trim( $value[ $language ] ) ) {
			return $value[ $language ];
		}
		$fallback = 'en' === $language ? 'he' : 'en';
		return isset( $value[ $fallback ] ) && is_string( $value[ $fallback ] ) ? $value[ $fallback ] : '';
	}

	private static function minor_amount_string( $minor_amount, $minor_unit_digits ) {
		$digits = (int) $minor_unit_digits;
		if ( 0 === $digits ) {
			return (string) $minor_amount;
		}
		$factor = self::power_of_ten( $digits, 'public_market_context.currency_precision' );
		return (string) intdiv( $minor_amount, $factor ) . '.' . str_pad( (string) ( $minor_amount % $factor ), $digits, '0', STR_PAD_LEFT );
	}

	private static function assert_normalization( $value, $path ) {
		self::assert_exact_keys( $value, array( 'quantity_decimal', 'unit_code', 'normalized_amount_minor', 'normalized_quantity_decimal', 'normalized_unit_code', 'formula' ), $path );
		self::assert_decimal( $value['quantity_decimal'], $path . '.quantity_decimal', false );
		self::assert_identifier( $value['unit_code'], $path . '.unit_code', 40 );
		self::assert_nullable_minor_amount( $value['normalized_amount_minor'], $path . '.normalized_amount_minor' );
		self::assert_decimal( $value['normalized_quantity_decimal'], $path . '.normalized_quantity_decimal', false );
		self::assert_identifier( $value['normalized_unit_code'], $path . '.normalized_unit_code', 40 );
		self::assert_text( $value['formula'], $path . '.formula', 500 );
	}

	private static function assert_fx( $value, $currencies, $path ) {
		self::assert_exact_keys( $value, array( 'pair', 'source_currency_id', 'destination_currency_id', 'direction', 'rounding', 'rate_decimal', 'source_url', 'observed_at' ), $path );
		if ( self::fx_is_configured( $value ) ) {
			self::assert_text( $value['pair'], $path . '.pair', 20 );
			self::assert_reference( $value['source_currency_id'], $currencies, $path . '.source_currency_id' );
			self::assert_reference( $value['destination_currency_id'], $currencies, $path . '.destination_currency_id' );
			if ( 'source_to_destination' !== $value['direction'] || 'half_up_minor' !== $value['rounding'] ) {
				throw new RuntimeException( $path . '.method' );
			}
			self::assert_decimal( $value['rate_decimal'], $path . '.rate_decimal', false );
			self::assert_https_url( $value['source_url'], $path . '.source_url' );
			self::assert_timestamp( $value['observed_at'], $path . '.observed_at' );
		} else {
			foreach ( $value as $item ) {
				if ( '' !== $item ) {
					throw new RuntimeException( $path . '.partial' );
				}
			}
		}
	}

	private static function fx_is_configured( $value ) {
		return is_array( $value ) && isset( $value['rate_decimal'] ) && '' !== $value['rate_decimal'];
	}

	private static function assert_fx_contract( $fx, $source_currency_id, $destination_currency_id, $currencies, $path ) {
		if ( ! self::fx_is_configured( $fx )
			|| $source_currency_id !== $fx['source_currency_id']
			|| $destination_currency_id !== $fx['destination_currency_id']
			|| $currencies[ $source_currency_id ]['code'] . '/' . $currencies[ $destination_currency_id ]['code'] !== $fx['pair']
			|| 'source_to_destination' !== $fx['direction']
			|| 'half_up_minor' !== $fx['rounding'] ) {
			throw new RuntimeException( $path . '.contract' );
		}
	}

	private static function assert_cost_lines( $records, $currencies, $artifacts, $states, $path ) {
		self::assert_list( $records, $path, true );
		if ( count( $records ) > 100 ) {
			throw new RuntimeException( $path . '.limit' );
		}
		$seen = array();
		foreach ( $records as $offset => $record ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $record, array( 'code', 'amount_minor', 'currency_id', 'basis', 'evidence_artifact_id', 'status', 'tax_recoverable' ), $item_path );
			self::assert_identifier( $record['code'], $item_path . '.code', 80 );
			if ( isset( $seen[ $record['code'] ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate_code' );
			}
			$seen[ $record['code'] ] = true;
			self::assert_minor_amount( $record['amount_minor'], $item_path . '.amount_minor' );
			self::assert_reference( $record['currency_id'], $currencies, $item_path . '.currency_id' );
			self::assert_text( $record['basis'], $item_path . '.basis', 300 );
			self::assert_optional_reference( $record['evidence_artifact_id'], $artifacts, $item_path . '.evidence_artifact_id' );
			self::assert_enum( $record['status'], $states, $item_path . '.status' );
			if ( ! is_bool( $record['tax_recoverable'] ) ) {
				throw new RuntimeException( $item_path . '.tax_recoverable' );
			}
		}
	}

	private static function assert_revenue_adjustment_lines( $records, $currencies, $artifacts, $states, $path ) {
		self::assert_list( $records, $path, true );
		if ( count( $records ) > 100 ) {
			throw new RuntimeException( $path . '.limit' );
		}
		$seen = array();
		foreach ( $records as $offset => $record ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $record, array( 'code', 'amount_minor_signed', 'currency_id', 'basis', 'evidence_artifact_id', 'status' ), $item_path );
			self::assert_identifier( $record['code'], $item_path . '.code', 80 );
			if ( isset( $seen[ $record['code'] ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate_code' );
			}
			$seen[ $record['code'] ] = true;
			self::assert_signed_minor_amount( $record['amount_minor_signed'], $item_path . '.amount_minor_signed' );
			self::assert_reference( $record['currency_id'], $currencies, $item_path . '.currency_id' );
			self::assert_text( $record['basis'], $item_path . '.basis', 300 );
			self::assert_optional_reference( $record['evidence_artifact_id'], $artifacts, $item_path . '.evidence_artifact_id' );
			self::assert_enum( $record['status'], $states, $item_path . '.status' );
		}
	}

	private static function assert_approval_evidence( $artifact_ids, $artifacts, $path ) {
		if ( empty( $artifact_ids ) ) {
			throw new RuntimeException( $path . '.required' );
		}
		foreach ( $artifact_ids as $artifact_id ) {
			if ( ! isset( $artifacts[ $artifact_id ] ) || true !== $artifacts[ $artifact_id ]['offer_approval_eligible'] ) {
				throw new RuntimeException( $path . '.ineligible' );
			}
		}
	}

	private static function assert_approved_cost_lines( $records, $currency_id, $artifacts, $path ) {
		foreach ( $records as $offset => $record ) {
			if ( $currency_id !== $record['currency_id'] || 'verified' !== $record['status'] || '' === $record['evidence_artifact_id'] ) {
				throw new RuntimeException( $path . '.' . $offset . '.approval' );
			}
			self::assert_approval_evidence( array( $record['evidence_artifact_id'] ), $artifacts, $path . '.' . $offset . '.evidence' );
		}
	}

	private static function assert_approved_adjustment_lines( $records, $currency_id, $artifacts, $path ) {
		foreach ( $records as $offset => $record ) {
			if ( $currency_id !== $record['currency_id'] || 'verified' !== $record['status'] || '' === $record['evidence_artifact_id'] ) {
				throw new RuntimeException( $path . '.' . $offset . '.approval' );
			}
			self::assert_approval_evidence( array( $record['evidence_artifact_id'] ), $artifacts, $path . '.' . $offset . '.evidence' );
		}
	}

	private static function find_cost_line( $records, $code ) {
		foreach ( $records as $record ) {
			if ( $code === $record['code'] ) {
				return $record;
			}
		}
		return null;
	}

	private static function has_line_code( $records, $code ) {
		foreach ( $records as $record ) {
			if ( $code === $record['code'] ) {
				return true;
			}
		}
		return false;
	}

	private static function sum_cost_lines( $records, $path ) {
		$total = 0;
		foreach ( $records as $offset => $record ) {
			if ( $record['amount_minor'] > PHP_INT_MAX - $total ) {
				throw new RuntimeException( $path . '.' . $offset . '.overflow' );
			}
			$total += $record['amount_minor'];
		}
		return $total;
	}

	private static function sum_signed_lines( $records, $path ) {
		$total = 0;
		foreach ( $records as $offset => $record ) {
			$amount = $record['amount_minor_signed'];
			if ( ( $amount > 0 && $total > PHP_INT_MAX - $amount ) || ( $amount < 0 && $total < PHP_INT_MIN - $amount ) ) {
				throw new RuntimeException( $path . '.' . $offset . '.overflow' );
			}
			$total += $amount;
		}
		return $total;
	}

	private static function assert_kiosk_projection( $value, $path ) {
		self::assert_exact_keys( $value, array( 'category', 'subcategory', 'image_url', 'food_tags', 'allergens', 'modifiers', 'availability', 'version' ), $path );
		self::assert_translation( $value['category'], $path . '.category', 100 );
		self::assert_translation( $value['subcategory'], $path . '.subcategory', 100 );
		if ( '' !== $value['image_url'] ) {
			self::assert_https_url( $value['image_url'], $path . '.image_url' );
		}
		self::assert_identifier_list( $value['food_tags'], $path . '.food_tags', true, 40 );
		self::assert_identifier_list( $value['allergens'], $path . '.allergens', true, 30 );
		self::assert_list( $value['modifiers'], $path . '.modifiers', true );
		foreach ( $value['modifiers'] as $offset => $modifier ) {
			$item_path = $path . '.modifiers.' . $offset;
			self::assert_exact_keys( $modifier, array( 'code', 'name', 'price_delta_minor' ), $item_path );
			self::assert_identifier( $modifier['code'], $item_path . '.code', 80 );
			self::assert_translation( $modifier['name'], $item_path . '.name', 120 );
			if ( ! is_int( $modifier['price_delta_minor'] ) ) {
				throw new RuntimeException( $item_path . '.price_delta_minor' );
			}
		}
		self::assert_identifier( $value['availability'], $path . '.availability', 80 );
		if ( ! is_int( $value['version'] ) || $value['version'] < 1 ) {
			throw new RuntimeException( $path . '.version' );
		}
	}

	private static function assert_external_ids( $records, $path ) {
		self::assert_list( $records, $path, true );
		$seen = array();
		foreach ( $records as $offset => $record ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $record, array( 'provider', 'value', 'verified_at' ), $item_path );
			self::assert_identifier( $record['provider'], $item_path . '.provider', 80 );
			self::assert_text( $record['value'], $item_path . '.value', 160 );
			self::assert_date( $record['verified_at'], $item_path . '.verified_at' );
			$key = $record['provider'] . '|' . $record['value'];
			if ( isset( $seen[ $key ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$seen[ $key ] = true;
		}
	}

	private static function assert_quantity( $value, $path ) {
		self::assert_exact_keys( $value, array( 'value_decimal', 'unit_code' ), $path );
		self::assert_decimal( $value['value_decimal'], $path . '.value_decimal', false );
		self::assert_identifier( $value['unit_code'], $path . '.unit_code', 40 );
	}

	private static function assert_scalar_map( $value, $path, $maximum ) {
		if ( ! is_array( $value ) || self::is_list( $value ) || count( $value ) > $maximum ) {
			throw new RuntimeException( $path );
		}
		foreach ( $value as $key => $item ) {
			self::assert_identifier( $key, $path . '.key', 80 );
			if ( ! is_string( $item ) || '' === trim( $item ) || strlen( $item ) > 500 ) {
				throw new RuntimeException( $path . '.' . $key );
			}
		}
	}

	private static function assert_source_ids( $ids, $science_sources, $path, $allow_empty ) {
		self::assert_list( $ids, $path, $allow_empty );
		foreach ( $ids as $offset => $id ) {
			if ( ! is_string( $id ) || ! isset( $science_sources[ $id ] ) ) {
				throw new RuntimeException( $path . '.' . $offset );
			}
		}
	}

	private static function assert_translation( $value, $path, $maximum ) {
		self::assert_exact_keys( $value, array( 'he', 'en' ), $path );
		self::assert_text( $value['he'], $path . '.he', $maximum );
		self::assert_text( $value['en'], $path . '.en', $maximum );
	}

	private static function assert_identifier_list( $value, $path, $allow_empty, $maximum ) {
		self::assert_list( $value, $path, $allow_empty );
		if ( count( $value ) > $maximum || count( array_unique( $value, SORT_STRING ) ) !== count( $value ) ) {
			throw new RuntimeException( $path );
		}
		foreach ( $value as $offset => $item ) {
			self::assert_identifier( $item, $path . '.' . $offset, 120 );
		}
	}

	private static function assert_reference_list( $value, $index, $path, $allow_empty ) {
		self::assert_list( $value, $path, $allow_empty );
		if ( count( array_unique( $value, SORT_STRING ) ) !== count( $value ) ) {
			throw new RuntimeException( $path . '.duplicates' );
		}
		foreach ( $value as $offset => $item ) {
			self::assert_reference( $item, $index, $path . '.' . $offset );
		}
	}

	private static function assert_url_list( $value, $path, $allow_empty ) {
		self::assert_list( $value, $path, $allow_empty );
		foreach ( $value as $offset => $item ) {
			self::assert_https_url( $item, $path . '.' . $offset );
		}
	}

	private static function index_records( $records, $key, $path ) {
		self::assert_list( $records, $path, true );
		$index = array();
		foreach ( $records as $offset => $record ) {
			if ( ! is_array( $record ) || ! isset( $record[ $key ] ) || ! is_string( $record[ $key ] ) || isset( $index[ $record[ $key ] ] ) ) {
				throw new RuntimeException( $path . '.' . $offset . '.' . $key );
			}
			$index[ $record[ $key ] ] = $record;
		}
		return $index;
	}

	private static function find_record( $records, $id ) {
		foreach ( $records as $record ) {
			if ( isset( $record['id'] ) && hash_equals( (string) $record['id'], (string) $id ) ) {
				return $record;
			}
		}
		return null;
	}

	private static function assert_reference( $id, $index, $path ) {
		if ( ! is_string( $id ) || ! isset( $index[ $id ] ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_optional_reference( $id, $index, $path ) {
		if ( '' !== $id ) {
			self::assert_reference( $id, $index, $path );
		}
	}

	private static function assert_enum( $value, $allowed, $path ) {
		if ( ! is_string( $value ) || ! in_array( $value, $allowed, true ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_identifier( $value, $path, $maximum, $allow_empty = false ) {
		if ( ! is_string( $value ) || ( '' === $value && ! $allow_empty ) || strlen( $value ) > $maximum || ( '' !== $value && 1 !== preg_match( '/\A[a-z0-9][a-z0-9._:-]*\z/', $value ) ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_text( $value, $path, $maximum, $allow_empty = false ) {
		if ( ! is_string( $value ) || ( '' === trim( $value ) && ! $allow_empty ) || strlen( $value ) > $maximum || false !== strpos( $value, "\0" ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_minor_amount( $value, $path ) {
		if ( ! is_int( $value ) || $value < 0 || $value > 999999999999 ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_nullable_minor_amount( $value, $path ) {
		if ( null !== $value ) {
			self::assert_minor_amount( $value, $path );
		}
	}

	private static function assert_signed_minor_amount( $value, $path ) {
		if ( ! is_int( $value ) || $value < -999999999999 || $value > 999999999999 ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_decimal( $value, $path, $allow_zero ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '/\A(?:0|[1-9][0-9]{0,11})(?:\.[0-9]{1,6})?\z/', $value ) || ( ! $allow_zero && 0.0 >= (float) $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function decimal_fraction( $value, $path ) {
		self::assert_decimal( $value, $path, true );
		$parts = explode( '.', $value, 2 );
		$fraction = isset( $parts[1] ) ? $parts[1] : '';
		$scale = self::power_of_ten( strlen( $fraction ), $path . '.scale' );
		$digits = ltrim( $parts[0] . $fraction, '0' );
		$numerator = '' === $digits ? 0 : (int) $digits;
		return array( $numerator, $scale );
	}

	private static function decimal_price_to_minor( $value, $minor_unit_digits, $path ) {
		try {
			list( $numerator, $scale ) = self::decimal_fraction( $value, $path );
			$factor = self::power_of_ten( (int) $minor_unit_digits, $path . '.minor_unit_digits' );
			$common = self::greatest_common_divisor( $factor, $scale );
			$factor = intdiv( $factor, $common );
			$scale = intdiv( $scale, $common );
			$scaled = self::safe_multiply( $numerator, $factor, $path . '.overflow' );
			$result = self::round_ratio_half_up( $scaled, $scale );
			self::assert_minor_amount( $result, $path );
			return $result;
		} catch ( Throwable $error ) {
			return self::error( 'complete99_pos_price_format', 'The WooCommerce price is not a valid minor-unit amount.', 409 );
		}
	}

	private static function compare_decimals( $left, $right ) {
		$normalize = static function ( $value ) {
			$parts = explode( '.', $value, 2 );
			$whole = ltrim( $parts[0], '0' );
			$whole = '' === $whole ? '0' : $whole;
			$fraction = isset( $parts[1] ) ? rtrim( $parts[1], '0' ) : '';
			return array( $whole, $fraction );
		};
		$left_parts = $normalize( $left );
		$right_parts = $normalize( $right );
		if ( strlen( $left_parts[0] ) !== strlen( $right_parts[0] ) ) {
			return strlen( $left_parts[0] ) < strlen( $right_parts[0] ) ? -1 : 1;
		}
		$whole_compare = strcmp( $left_parts[0], $right_parts[0] );
		if ( 0 !== $whole_compare ) {
			return $whole_compare < 0 ? -1 : 1;
		}
		$length = max( strlen( $left_parts[1] ), strlen( $right_parts[1] ) );
		$fraction_compare = strcmp( str_pad( $left_parts[1], $length, '0' ), str_pad( $right_parts[1], $length, '0' ) );
		return 0 === $fraction_compare ? 0 : ( $fraction_compare < 0 ? -1 : 1 );
	}

	private static function multiply_minor_by_decimal_ceiling( $minor, $decimal, $path ) {
		list( $numerator, $denominator ) = self::decimal_fraction( $decimal, $path );
		$common = self::greatest_common_divisor( $minor, $denominator );
		$minor = intdiv( $minor, $common );
		$denominator = intdiv( $denominator, $common );
		$product = self::safe_multiply( $minor, $numerator, $path . '.overflow' );
		return self::ceil_ratio( $product, $denominator );
	}

	private static function divide_minor_by_decimal_ceiling( $minor, $decimal, $path ) {
		list( $numerator, $scale ) = self::decimal_fraction( $decimal, $path );
		if ( $numerator <= 0 ) {
			throw new RuntimeException( $path . '.zero_divisor' );
		}
		$common = self::greatest_common_divisor( $scale, $numerator );
		$scale = intdiv( $scale, $common );
		$numerator = intdiv( $numerator, $common );
		$scaled_minor = self::safe_multiply( $minor, $scale, $path . '.overflow' );
		return self::ceil_ratio( $scaled_minor, $numerator );
	}

	private static function convert_minor_half_up( $source_minor, $rate_decimal, $source_digits, $destination_digits, $path ) {
		list( $rate_numerator, $rate_scale ) = self::decimal_fraction( $rate_decimal, $path . '.rate' );
		$numerators = array( $source_minor, $rate_numerator, self::power_of_ten( $destination_digits, $path . '.destination_digits' ) );
		$denominators = array( $rate_scale, self::power_of_ten( $source_digits, $path . '.source_digits' ) );
		foreach ( $denominators as $denominator_index => $denominator ) {
			foreach ( $numerators as $numerator_index => $numerator ) {
				$common = self::greatest_common_divisor( $numerator, $denominator );
				$numerators[ $numerator_index ] = intdiv( $numerator, $common );
				$denominator = intdiv( $denominator, $common );
			}
			$denominators[ $denominator_index ] = $denominator;
		}
		$numerator = 1;
		foreach ( $numerators as $factor ) {
			$numerator = self::safe_multiply( $numerator, $factor, $path . '.overflow' );
		}
		$denominator = 1;
		foreach ( $denominators as $factor ) {
			$denominator = self::safe_multiply( $denominator, $factor, $path . '.overflow' );
		}
		$result = self::round_ratio_half_up( $numerator, $denominator );
		self::assert_minor_amount( $result, $path );
		return $result;
	}

	private static function assert_margin_rate( $contribution, $net_revenue, $rate, $path ) {
		if ( ! is_string( $rate ) || 1 !== preg_match( '/\A(?:0\.[0-9]{6}|1\.000000)\z/', $rate ) ) {
			throw new RuntimeException( $path . '.precision' );
		}
		list( $rate_numerator, $rate_scale ) = self::decimal_fraction( $rate, $path );
		$scaled_contribution = self::safe_multiply( $contribution, $rate_scale, $path . '.overflow' );
		$expected_numerator = self::round_ratio_half_up( $scaled_contribution, $net_revenue );
		if ( $rate_numerator !== $expected_numerator ) {
			throw new RuntimeException( $path . '.formula' );
		}
	}

	private static function power_of_ten( $digits, $path ) {
		if ( ! is_int( $digits ) || $digits < 0 || $digits > 6 ) {
			throw new RuntimeException( $path );
		}
		$value = 1;
		for ( $offset = 0; $offset < $digits; $offset++ ) {
			$value *= 10;
		}
		return $value;
	}

	private static function greatest_common_divisor( $left, $right ) {
		$left = abs( (int) $left );
		$right = abs( (int) $right );
		while ( 0 !== $right ) {
			$remainder = $left % $right;
			$left = $right;
			$right = $remainder;
		}
		return max( 1, $left );
	}

	private static function safe_multiply( $left, $right, $path ) {
		if ( $left < 0 || $right < 0 || ( 0 !== $right && $left > intdiv( PHP_INT_MAX, $right ) ) ) {
			throw new RuntimeException( $path );
		}
		return $left * $right;
	}

	private static function ceil_ratio( $numerator, $denominator ) {
		$quotient = intdiv( $numerator, $denominator );
		return 0 === $numerator % $denominator ? $quotient : $quotient + 1;
	}

	private static function round_ratio_half_up( $numerator, $denominator ) {
		$quotient = intdiv( $numerator, $denominator );
		$remainder = $numerator % $denominator;
		return $remainder >= intdiv( $denominator, 2 ) + ( $denominator % 2 ) ? $quotient + 1 : $quotient;
	}

	private static function assert_rate( $value, $path ) {
		self::assert_decimal( $value, $path, true );
		if ( (float) $value > 1.0 ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_optional_rate( $value, $path ) {
		if ( null !== $value ) {
			self::assert_rate( $value, $path );
		}
	}

	private static function assert_date( $value, $path ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '/\A\d{4}-\d{2}-\d{2}\z/', $value ) ) {
			throw new RuntimeException( $path );
		}
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		if ( false === $parsed || $parsed->format( 'Y-m-d' ) !== $value ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_optional_date( $value, $path ) {
		if ( '' !== $value ) {
			self::assert_date( $value, $path );
		}
	}

	private static function assert_date_window( $valid_from, $valid_until, $path ) {
		if ( '' !== $valid_from && '' !== $valid_until && $valid_from > $valid_until ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_timestamp( $value, $path ) {
		if ( ! is_string( $value ) || '' === $value ) {
			throw new RuntimeException( $path );
		}
		try {
			$parsed = new DateTimeImmutable( $value );
		} catch ( Throwable $error ) {
			throw new RuntimeException( $path );
		}
		if ( false === strpos( $value, 'T' ) || '' === $parsed->format( DATE_ATOM ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_optional_timestamp( $value, $path ) {
		if ( '' !== $value ) {
			self::assert_timestamp( $value, $path );
		}
	}

	private static function assert_https_url( $value, $path ) {
		if ( ! is_string( $value ) || 0 !== strpos( $value, 'https://' ) || ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_list( $value, $path, $allow_empty ) {
		if ( ! is_array( $value ) || ! self::is_list( $value ) || ( ! $allow_empty && empty( $value ) ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_exact_keys( $value, $keys, $path ) {
		if ( ! is_array( $value ) || self::is_list( $value ) ) {
			throw new RuntimeException( $path );
		}
		$actual = array_keys( $value );
		sort( $actual, SORT_STRING );
		sort( $keys, SORT_STRING );
		if ( $actual !== $keys ) {
			throw new RuntimeException( $path . '.keys' );
		}
	}

	private static function is_list( $value ) {
		return is_array( $value ) && ( empty( $value ) || array_keys( $value ) === range( 0, count( $value ) - 1 ) );
	}

	private static function assert_no_em_dash( $value, $path ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				self::assert_no_em_dash( $item, $path . '.' . $key );
			}
			return;
		}
		if ( is_string( $value ) && false !== strpos( $value, "\xE2\x80\x94" ) ) {
			throw new RuntimeException( $path . '.em_dash' );
		}
	}

	private static function is_error( $value ) {
		return function_exists( 'is_wp_error' ) ? is_wp_error( $value ) : $value instanceof WP_Error;
	}

	private static function error( $code, $message, $status, $extra = array() ) {
		return new WP_Error( $code, $message, array_merge( array( 'status' => $status ), $extra ) );
	}
}
