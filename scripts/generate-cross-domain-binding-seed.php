<?php
/**
 * Build and verify the Complete99 private cross-domain binding v3 seed and
 * its separately checked-in decision overlay.
 *
 * The generator consumes IDs and reciprocal structured references only. It
 * never compares labels or slugs across domains and never traverses a third
 * entity to propose a binding.
 *
 * Usage:
 *   php scripts/generate-cross-domain-binding-seed.php --check
 *   php scripts/generate-cross-domain-binding-seed.php --json
 *   php scripts/generate-cross-domain-binding-seed.php
 */

declare(strict_types=1);

function c99_binding_seed_fail( string $message ): void {
	fwrite( STDERR, 'cross-domain binding seed: ' . $message . PHP_EOL );
	exit( 1 );
}

function c99_binding_seed_is_list( array $value ): bool {
	$expected = 0;
	foreach ( $value as $key => $_item ) {
		if ( $key !== $expected ) {
			return false;
		}
		++$expected;
	}
	return true;
}

/**
 * Apply the Complete99 canonical loaded-value convention.
 *
 * Associative keys are sorted bytewise and lists retain source order.
 * JSON_PRESERVE_ZERO_FRACTION is intentionally not used: this matches the
 * existing Entity Studio digest contract.
 *
 * @param mixed $value Value to canonicalize.
 * @return mixed
 */
function c99_binding_seed_canonicalize( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	if ( c99_binding_seed_is_list( $value ) ) {
		return array_map( 'c99_binding_seed_canonicalize', $value );
	}
	ksort( $value, SORT_STRING );
	foreach ( $value as $key => $item ) {
		$value[ $key ] = c99_binding_seed_canonicalize( $item );
	}
	return $value;
}

/** @param mixed $value Value to encode. */
function c99_binding_seed_canonical_json( $value ): string {
	$json = json_encode(
		c99_binding_seed_canonicalize( $value ),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	if ( ! is_string( $json ) ) {
		c99_binding_seed_fail( 'canonical JSON encoding failed' );
	}
	return $json;
}

/** @param mixed $value Value to digest. */
function c99_binding_seed_digest( $value ): string {
	return hash( 'sha256', c99_binding_seed_canonical_json( $value ) );
}

/**
 * @param array<string,mixed> $record Source record.
 * @param list<string>        $keys Required exact keys.
 */
function c99_binding_seed_assert_exact_keys( array $record, array $keys, string $path ): void {
	if ( array_keys( $record ) !== $keys ) {
		c99_binding_seed_fail( $path . ' has unexpected keys' );
	}
}

/**
 * @param mixed $value Value under review.
 */
function c99_binding_seed_nonempty_string( $value, string $path ): string {
	if ( ! is_string( $value ) || '' === $value ) {
		c99_binding_seed_fail( $path . ' must be a non-empty string' );
	}
	return $value;
}

/**
 * Recursively collect component codes, preserving first source occurrence.
 *
 * @param mixed               $children Component child list.
 * @param array<string,bool>  $seen Seen codes.
 * @param list<string>        $codes Ordered codes.
 */
function c99_binding_seed_collect_component_codes( $children, array &$seen, array &$codes, string $path ): void {
	if ( ! is_array( $children ) || ! c99_binding_seed_is_list( $children ) ) {
		c99_binding_seed_fail( $path . ' must be a list' );
	}
	foreach ( $children as $offset => $child ) {
		if ( ! is_array( $child ) ) {
			c99_binding_seed_fail( $path . '[' . $offset . '] must be an array' );
		}
		$code = c99_binding_seed_nonempty_string( $child['code'] ?? null, $path . '[' . $offset . '].code' );
		if ( ! isset( $seen[ $code ] ) ) {
			$seen[ $code ] = true;
			$codes[]       = $code;
		}
		if ( isset( $child['children'] ) ) {
			c99_binding_seed_collect_component_codes( $child['children'], $seen, $codes, $path . '[' . $offset . '].children' );
		}
	}
}

/**
 * @param list<array<string,string>> $candidates Candidate records.
 * @param list<array<string,string>> $evidence_refs Evidence records.
 * @return array<string,mixed>
 */
function c99_binding_seed_record(
	string $id,
	string $kind,
	string $registry,
	string $entity_type,
	string $entity_id,
	string $scope_entity_id,
	array $candidates = array(),
	array $evidence_refs = array()
): array {
	return array(
		'id'                     => $id,
		'kind'                   => $kind,
		'subject'                => array(
			'registry'        => $registry,
			'entity_type'     => $entity_type,
			'entity_id'       => $entity_id,
			'scope_entity_id' => $scope_entity_id,
		),
		'resolution_state'       => 'unresolved',
		'targets'                => array(),
		'candidates'             => $candidates,
		'decision_evidence_refs' => $evidence_refs,
		'decision_note'          => array( 'he' => '', 'en' => '' ),
		'review'                 => array(
			'state'          => 'unreviewed',
			'reviewer_id'    => '',
			'reviewed_at'    => '',
			'next_review_at' => '',
		),
		'valid_from'             => '',
		'valid_to'               => '',
	);
}

/** @return array<string,mixed> */
function c99_binding_seed_build( string $root ): array {
	$data_dir = $root . '/plugin/complete99-platform/data/';
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', $root . '/' );
	}

	$consumer_menu = require $data_dir . 'consumer-menu.php';
	$dish_trees    = require $data_dir . 'dish-entity-trees.php';
	$catalog_seeds = require $data_dir . 'catalog-product-seeds.php';
	$science       = require $data_dir . 'culinary-science-pilot.php';
	$products      = require $data_dir . 'live-catalog-products.php';
	$relations     = require $data_dir . 'live-catalog-relations.php';

	if ( ! is_array( $consumer_menu ) || ! c99_binding_seed_is_list( $consumer_menu ) ) {
		c99_binding_seed_fail( 'consumer menu adapter source must be an unwrapped list' );
	}
	if ( 'complete99-dish-entity-tree-registry/v1' !== ( $dish_trees['schema'] ?? '' )
		|| '2026-07-31' !== ( $dish_trees['registry_reviewed_at'] ?? '' ) ) {
		c99_binding_seed_fail( 'dish entity tree schema or review version drifted' );
	}
	if ( 'complete99-culinary-science-registry/v6' !== ( $science['schema'] ?? '' )
		|| 'culinary-science-2026.08.08.v20' !== ( $science['version'] ?? '' ) ) {
		c99_binding_seed_fail( 'culinary science schema or version drifted' );
	}
	$science_payload_json   = c99_binding_seed_canonical_json( $science );
	$science_payload_sha256 = hash( 'sha256', $science_payload_json );
	if ( 9820452 !== strlen( $science_payload_json )
		|| ! hash_equals( '677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54', $science_payload_sha256 ) ) {
		c99_binding_seed_fail( 'culinary science canonical payload drifted' );
	}
	if ( 'complete99-catalog-product-seeds/v1' !== ( $catalog_seeds['schema'] ?? '' )
		|| '2026-08-06' !== ( $catalog_seeds['registry_reviewed_at'] ?? '' ) ) {
		c99_binding_seed_fail( 'catalog product seed schema or review version drifted' );
	}
	if ( 'complete99-live-catalog-products/v1' !== ( $products['schema'] ?? '' )
		|| '2026-08-06' !== ( $products['reviewed_at'] ?? '' ) ) {
		c99_binding_seed_fail( 'live product schema or review version drifted' );
	}
	if ( 'complete99-live-catalog-relations/v1' !== ( $relations['schema'] ?? '' )
		|| '2026-08-06' !== ( $relations['reviewed_at'] ?? '' ) ) {
		c99_binding_seed_fail( 'live relation schema or review version drifted' );
	}

	$input_contracts = array(
		'consumer_menu'          => array(
			'source_path'    => 'data/consumer-menu.php',
			'source_schema'  => 'complete99-consumer-menu-array/v1',
			'source_version' => 'unversioned',
			'payload_sha256' => c99_binding_seed_digest( $consumer_menu ),
		),
		'dish_entity_trees'      => array(
			'source_path'    => 'data/dish-entity-trees.php',
			'source_schema'  => $dish_trees['schema'],
			'source_version' => 'registry-reviewed-' . $dish_trees['registry_reviewed_at'],
			'payload_sha256' => c99_binding_seed_digest( $dish_trees ),
		),
		'catalog_product_seeds'  => array(
			'source_path'    => 'data/catalog-product-seeds.php',
			'source_schema'  => $catalog_seeds['schema'],
			'source_version' => 'reviewed-' . $catalog_seeds['registry_reviewed_at'],
			'payload_sha256' => c99_binding_seed_digest( $catalog_seeds ),
		),
		'culinary_science'       => array(
			'source_path'    => 'data/culinary-science-pilot.php',
			'source_schema'  => $science['schema'],
			'source_version' => $science['version'],
			'payload_sha256' => $science_payload_sha256,
		),
		'live_catalog_products'  => array(
			'source_path'    => 'data/live-catalog-products.php',
			'source_schema'  => $products['schema'],
			'source_version' => 'reviewed-' . $products['reviewed_at'],
			'payload_sha256' => c99_binding_seed_digest( $products ),
		),
		'live_catalog_relations' => array(
			'source_path'    => 'data/live-catalog-relations.php',
			'source_schema'  => $relations['schema'],
			'source_version' => 'reviewed-' . $relations['reviewed_at'],
			'payload_sha256' => c99_binding_seed_digest( $relations ),
		),
	);

	$menu_by_id = array();
	foreach ( $consumer_menu as $offset => $dish ) {
		if ( ! is_array( $dish ) ) {
			c99_binding_seed_fail( 'consumer_menu[' . $offset . '] must be an array' );
		}
		$dish_id   = c99_binding_seed_nonempty_string( $dish['id'] ?? null, 'consumer_menu[' . $offset . '].id' );
		$dish_slug = c99_binding_seed_nonempty_string( $dish['slug'] ?? null, 'consumer_menu[' . $offset . '].slug' );
		if ( isset( $menu_by_id[ $dish_id ] ) ) {
			c99_binding_seed_fail( 'duplicate consumer menu dish ID ' . $dish_id );
		}
		$menu_by_id[ $dish_id ] = $dish_slug;
	}
	if ( 12 !== count( $menu_by_id ) ) {
		c99_binding_seed_fail( 'v3 requires exactly 12 consumer menu dishes' );
	}

	if ( ! isset( $dish_trees['dishes'] ) || ! is_array( $dish_trees['dishes'] ) || ! c99_binding_seed_is_list( $dish_trees['dishes'] ) ) {
		c99_binding_seed_fail( 'dish tree registry dishes must be a list' );
	}
	$components_by_dish = array();
	foreach ( $dish_trees['dishes'] as $offset => $dish_tree ) {
		if ( ! is_array( $dish_tree ) ) {
			c99_binding_seed_fail( 'dish tree record must be an array' );
		}
		$dish_id   = c99_binding_seed_nonempty_string( $dish_tree['dish_id'] ?? null, 'dish_trees.dishes[' . $offset . '].dish_id' );
		$dish_slug = c99_binding_seed_nonempty_string( $dish_tree['source_record_slug'] ?? null, 'dish_trees.dishes[' . $offset . '].source_record_slug' );
		if ( ! isset( $menu_by_id[ $dish_id ] ) || $menu_by_id[ $dish_id ] !== $dish_slug ) {
			c99_binding_seed_fail( 'dish tree does not exactly bind its consumer menu record: ' . $dish_id );
		}
		if ( isset( $components_by_dish[ $dish_id ] ) ) {
			c99_binding_seed_fail( 'duplicate dish tree for ' . $dish_id );
		}

		$codes = array();
		$seen  = array();
		c99_binding_seed_collect_component_codes(
			$dish_tree['component_tree']['children'] ?? null,
			$seen,
			$codes,
			'dish_trees.dishes[' . $offset . '].component_tree.children'
		);
		$ingredient_codes = $dish_tree['relations']['ingredient_codes'] ?? null;
		if ( ! is_array( $ingredient_codes ) || ! c99_binding_seed_is_list( $ingredient_codes ) ) {
			c99_binding_seed_fail( 'dish relation ingredient_codes must be a list' );
		}
		foreach ( $ingredient_codes as $ingredient_offset => $code ) {
			$code = c99_binding_seed_nonempty_string( $code, 'ingredient_codes[' . $ingredient_offset . ']' );
			if ( ! isset( $seen[ $code ] ) ) {
				$seen[ $code ] = true;
				$codes[]       = $code;
			}
		}
		$components_by_dish[ $dish_id ] = $codes;
	}
	if ( array_diff_key( $menu_by_id, $components_by_dish ) || array_diff_key( $components_by_dish, $menu_by_id ) ) {
		c99_binding_seed_fail( 'dish tree coverage differs from consumer menu coverage' );
	}
	$component_count = array_sum( array_map( 'count', $components_by_dish ) );
	if ( 47 !== $component_count ) {
		c99_binding_seed_fail( 'v3 requires exactly 47 dish-scoped component subjects' );
	}

	if ( ! isset( $products['products'] ) || ! is_array( $products['products'] ) || c99_binding_seed_is_list( $products['products'] ) ) {
		c99_binding_seed_fail( 'live products must be an associative map' );
	}
	$product_codes = array_keys( $products['products'] );
	if ( 36 !== count( $product_codes ) ) {
		c99_binding_seed_fail( 'v3 requires exactly 36 Woo product subjects' );
	}
	if ( ! isset( $catalog_seeds['products'] ) || ! is_array( $catalog_seeds['products'] )
		|| ! c99_binding_seed_is_list( $catalog_seeds['products'] ) ) {
		c99_binding_seed_fail( 'catalog product seeds must be a list' );
	}
	$seed_product_codes = array();
	foreach ( $catalog_seeds['products'] as $offset => $seed_product ) {
		if ( ! is_array( $seed_product ) ) {
			c99_binding_seed_fail( 'catalog product seed must be an array' );
		}
		$product_code = c99_binding_seed_nonempty_string( $seed_product['product_code'] ?? null, 'catalog_seeds.products[' . $offset . '].product_code' );
		if ( isset( $seed_product_codes[ $product_code ] ) ) {
			c99_binding_seed_fail( 'duplicate catalog product code ' . $product_code );
		}
		$seed_product_codes[ $product_code ] = true;
	}
	if ( array_diff_key( $products['products'], $seed_product_codes )
		|| array_diff_key( $seed_product_codes, $products['products'] ) ) {
		c99_binding_seed_fail( 'catalog seed product coverage differs from live products' );
	}
	if ( ! isset( $relations['products'] ) || ! is_array( $relations['products'] )
		|| array_diff_key( $products['products'], $relations['products'] )
		|| array_diff_key( $relations['products'], $products['products'] ) ) {
		c99_binding_seed_fail( 'live relation product coverage differs from live products' );
	}

	if ( ! isset( $science['entities'] ) || ! is_array( $science['entities'] ) || ! c99_binding_seed_is_list( $science['entities'] ) ) {
		c99_binding_seed_fail( 'science entities must be a list' );
	}
	$science_by_id = array();
	foreach ( $science['entities'] as $offset => $entity ) {
		if ( ! is_array( $entity ) ) {
			c99_binding_seed_fail( 'science entity must be an array' );
		}
		$entity_id = c99_binding_seed_nonempty_string( $entity['id'] ?? null, 'science.entities[' . $offset . '].id' );
		if ( isset( $science_by_id[ $entity_id ] ) ) {
			c99_binding_seed_fail( 'duplicate science entity ID ' . $entity_id );
		}
		$science_by_id[ $entity_id ] = $entity;
	}

	$reciprocal_candidates = array();
	foreach ( $science_by_id as $entity_id => $entity ) {
		$product_code = $entity['commerce']['woo_product_code'] ?? '';
		if ( '' === $product_code ) {
			continue;
		}
		$product_code = c99_binding_seed_nonempty_string( $product_code, 'science entity commerce.woo_product_code' );
		if ( ! isset( $products['products'][ $product_code ] ) ) {
			c99_binding_seed_fail( 'science Woo reference has no live product: ' . $product_code );
		}
		if ( ( $relations['products'][ $product_code ]['science_entity_id'] ?? '' ) !== $entity_id ) {
			c99_binding_seed_fail( 'science and live relation references are not reciprocal for ' . $product_code );
		}
		$entity_type = c99_binding_seed_nonempty_string( $entity['type'] ?? null, 'science entity type' );
		if ( ! in_array( $entity_type, array( 'ingredient', 'preparation', 'equipment' ), true ) ) {
			c99_binding_seed_fail( 'science candidate has unsupported target type ' . $entity_type );
		}
		if ( isset( $reciprocal_candidates[ $product_code ] ) ) {
			c99_binding_seed_fail( 'multiple science entities reference ' . $product_code );
		}
		$reciprocal_candidates[ $product_code ] = array( $entity_id, $entity_type );
	}
	foreach ( $relations['products'] as $product_code => $relation ) {
		$entity_id = $relation['science_entity_id'] ?? '';
		if ( '' === $entity_id ) {
			continue;
		}
		if ( ! isset( $reciprocal_candidates[ $product_code ] )
			|| $reciprocal_candidates[ $product_code ][0] !== $entity_id ) {
			c99_binding_seed_fail( 'live relation science reference is one-sided for ' . $product_code );
		}
	}
	if ( 11 !== count( $reciprocal_candidates ) ) {
		c99_binding_seed_fail( 'v3 requires exactly 11 reciprocal product-science candidates' );
	}

	$records = array();
	foreach ( $menu_by_id as $dish_id => $_dish_slug ) {
		$records[] = c99_binding_seed_record(
			'menu-dish--' . $dish_id,
			'menu_dish_science_dish',
			'consumer_menu',
			'dish',
			$dish_id,
			''
		);
	}
	foreach ( $components_by_dish as $dish_id => $component_codes ) {
		foreach ( $component_codes as $component_code ) {
			$records[] = c99_binding_seed_record(
				'menu-component--' . $dish_id . '--' . $component_code,
				'menu_component_science_entity',
				'dish_entity_trees',
				0 === strpos( $component_code, 'ingredient-' ) ? 'ingredient' : 'component',
				$component_code,
				$dish_id
			);
		}
	}
	foreach ( $product_codes as $product_code ) {
		$candidates    = array();
		$evidence_refs = array();
		if ( isset( $reciprocal_candidates[ $product_code ] ) ) {
			$science_entity = $reciprocal_candidates[ $product_code ];
			$reason_code = 'product-bulgur-fine-500g' === $product_code
				&& 'ingredient-syrian-bulgur' === $science_entity[0]
				? 'scope_mismatch'
				: 'legacy_explicit_relation_requires_review';
			$candidates[]   = array(
				'registry'    => 'culinary_science',
				'entity_type' => $science_entity[1],
				'entity_id'   => $science_entity[0],
				'state'       => 'pending_review',
				'reason_code' => $reason_code,
			);
			$evidence_refs = array(
				array( 'registry' => 'culinary_science_registry', 'record_id' => $science_entity[0] ),
				array( 'registry' => 'live_catalog_products', 'record_id' => $product_code ),
				array( 'registry' => 'live_catalog_relations', 'record_id' => $product_code ),
			);
		}
		$records[] = c99_binding_seed_record(
			'woo-product--' . $product_code,
			'woo_product_science_entity',
			'woocommerce',
			'product',
			$product_code,
			'',
			$candidates,
			$evidence_refs
		);
	}

	usort(
		$records,
		static function ( array $left, array $right ): int {
			return strcmp( $left['id'], $right['id'] );
		}
	);
	if ( 95 !== count( $records ) ) {
		c99_binding_seed_fail( 'v3 requires exactly 95 binding records' );
	}

	return array(
		'schema'                => 'complete99-cross-domain-binding-registry/v3',
		'version'               => 'complete99-cross-domain-bindings-2026.08.08.v3',
		'generated_at'          => '2026-08-08',
		'input_contracts'       => $input_contracts,
		'controlled_vocabulary' => array(
			'binding_kinds'          => array( 'menu_dish_science_dish', 'menu_component_science_entity', 'woo_product_science_entity' ),
			'resolution_states'      => array( 'linked', 'no_match', 'unresolved' ),
			'registries'             => array( 'consumer_menu', 'dish_entity_trees', 'culinary_science', 'woocommerce' ),
			'entity_types'           => array( 'dish', 'component', 'ingredient', 'preparation', 'equipment', 'product' ),
			'relations'              => array( 'same_dish_identity', 'house_expression_of', 'reference_only', 'same_ingredient_identity', 'same_preparation_identity', 'retail_instance_of' ),
			'projection_scopes'      => array( 'private_only', 'public_navigation', 'public_product_navigation' ),
			'review_states'          => array( 'unreviewed', 'source_reviewed', 'verified' ),
			'candidate_states'       => array( 'pending_review', 'rejected' ),
			'candidate_reason_codes' => array( 'legacy_explicit_relation_requires_review', 'insufficient_evidence', 'scope_mismatch', 'different_variant', 'component_is_composite', 'product_identity_unverified', 'target_type_mismatch', 'duplicate_conflict' ),
			'evidence_registries'    => array( 'dish_source_registry', 'culinary_science_sources', 'culinary_science_registry', 'catalog_product_seeds', 'live_catalog_products', 'live_catalog_relations' ),
		),
		'records'               => $records,
	);
}

$root     = str_replace( '\\', '/', dirname( __DIR__ ) );
$expected = c99_binding_seed_build( $root );

$arguments = array_slice( $argv, 1 );
foreach ( $arguments as $argument ) {
	if ( ! in_array( $argument, array( '--check', '--json' ), true ) ) {
		c99_binding_seed_fail( 'unknown argument ' . $argument );
	}
}
if ( count( $arguments ) > 1 ) {
	c99_binding_seed_fail( 'use only one output mode' );
}

$overlay_path = $root . '/plugin/complete99-platform/data/cross-domain-binding-decisions.php';
if ( ! is_readable( $overlay_path ) ) {
	c99_binding_seed_fail( 'decision overlay is missing' );
}
$overlay = require $overlay_path;
if ( ! is_array( $overlay ) ) {
	c99_binding_seed_fail( 'decision overlay must return an array' );
}
if ( ! defined( 'COMPLETE99_PLATFORM_DIR' ) ) {
	define( 'COMPLETE99_PLATFORM_DIR', $root . '/plugin/complete99-platform/' );
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;
		public function __construct( $code, $message, $data = array() ) {
			$this->code = $code;
			$this->message = $message;
			$this->data = $data;
		}
		public function get_error_code() { return $this->code; }
		public function get_error_message() { return $this->message; }
		public function get_error_data() { return $this->data; }
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}
}
require_once $root . '/plugin/complete99-platform/includes/class-complete99-cross-domain-bindings.php';
$merged = Complete99_Cross_Domain_Bindings::validate_and_merge_overlay( $expected, $overlay, true );
if ( is_wp_error( $merged ) ) {
	$data = $merged->get_error_data();
	$path = is_array( $data ) && isset( $data['path'] ) ? (string) $data['path'] : 'invalid';
	c99_binding_seed_fail( 'decision overlay failed closed at ' . $path );
}
$resolution_counts = array_count_values( array_column( $merged['records'], 'resolution_state' ) );
if ( 95 !== ( $resolution_counts['unresolved'] ?? 0 )
	|| 0 !== ( $resolution_counts['linked'] ?? 0 )
	|| 0 !== ( $resolution_counts['no_match'] ?? 0 )
	|| 0 !== count( $overlay['decisions'] ) ) {
	c99_binding_seed_fail( 'the current release requires exactly zero binding decisions' );
}

if ( in_array( '--check', $arguments, true ) ) {
	$checked_in = require $root . '/plugin/complete99-platform/data/cross-domain-bindings.php';
	$actual_json   = c99_binding_seed_canonical_json( $checked_in );
	$expected_json = c99_binding_seed_canonical_json( $expected );
	if ( ! hash_equals( hash( 'sha256', $expected_json ), hash( 'sha256', $actual_json ) )
		|| $expected_json !== $actual_json ) {
		c99_binding_seed_fail(
			'checked-in registry drifted; expected '
			. hash( 'sha256', $expected_json )
			. ', found '
			. hash( 'sha256', $actual_json )
		);
	}
	fwrite( STDOUT, 'cross-domain binding v3 verified: 95 unresolved records, 11 pending candidates, 0 decisions, 0 reviewer authorities' . PHP_EOL );
	exit( 0 );
}

if ( in_array( '--json', $arguments, true ) ) {
	fwrite( STDOUT, c99_binding_seed_canonical_json( $expected ) . PHP_EOL );
	exit( 0 );
}

$export = preg_replace( '/[ \t]+$/m', '', var_export( $expected, true ) );
if ( ! is_string( $export ) ) {
	c99_binding_seed_fail( 'PHP export formatting failed' );
}
fwrite(
	STDOUT,
	"<?php\n\n/** Generated private cross-domain binding registry. */\n\ndefined( 'ABSPATH' ) || exit;\n\nreturn "
	. $export
	. ";\n"
);
