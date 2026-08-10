<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exact, evidence-bound links between menu, science and Woo identities.
 *
 * This class never infers a relationship from labels, slugs, shared words or
 * graph neighbours. Candidate records remain private editorial material. Only
 * an explicit linked, verified target may enter a public navigation index.
 */
final class Complete99_Cross_Domain_Bindings {
	const REGISTRY_SCHEMA  = 'complete99-cross-domain-binding-registry/v3';
	const REGISTRY_VERSION = 'complete99-cross-domain-bindings-2026.08.08.v3';
	const GENERATED_AT     = '2026-08-08';
	const DATA_FILE        = 'cross-domain-bindings.php';
	const DECISION_FILE    = 'cross-domain-binding-decisions.php';
	const DECISION_OVERLAY_SCHEMA  = 'complete99-cross-domain-binding-decision-overlay/v1';
	const DECISION_OVERLAY_VERSION = 'complete99-cross-domain-binding-decisions-2026.08.08.v1';
	const SEED_PAYLOAD_SHA256 = '1ab6190df1443ca3e7f31103ce9c9ecd07cf25e5000c216bbebe9ccf909a4928';
	const DECISION_OVERLAY_PAYLOAD_SHA256 = 'bf42940d9ef0104c1bbc67c6b61871f55b77b901ba4445d332778c59c7cea73c';
	const CULINARY_SCIENCE_PAYLOAD_SHA256 = '677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54';

	const EXPECTED_DISH_SUBJECTS      = 12;
	const EXPECTED_COMPONENT_SUBJECTS = 47;
	const EXPECTED_PRODUCT_SUBJECTS   = 36;

	private static $registry_cache = null;
	private static $seed_registry_cache = null;
	private static $decision_overlay_cache = null;
	private static $indexes_cache  = null;
	private static $status_cache   = null;
	private static $inputs_cache   = null;
	private static $source_index_cache = null;
	private static $input_digest_cache = array();
	private static $indexes_valid_cache = false;

	/**
	 * Load the immutable registry. No record is created or updated here.
	 *
	 * @param bool $fresh Bypass request-local caches.
	 * @return array|WP_Error
	 */
	public static function registry( $fresh = false ) {
		if ( $fresh ) {
			self::$registry_cache = null;
			self::$seed_registry_cache = null;
			self::$decision_overlay_cache = null;
			self::$indexes_cache  = null;
			self::$status_cache   = null;
			self::$inputs_cache   = null;
			self::$source_index_cache = null;
			self::$input_digest_cache = array();
			self::$indexes_valid_cache = false;
		}
		if ( is_array( self::$registry_cache ) ) {
			return self::$registry_cache;
		}
		if ( ! defined( 'COMPLETE99_PLATFORM_DIR' ) ) {
			return self::error( 'complete99_cross_domain_registry_missing', 'The cross-domain binding registry is unavailable.' );
		}
		$path = COMPLETE99_PLATFORM_DIR . 'data/' . self::DATA_FILE;
		if ( ! is_readable( $path ) ) {
			return self::error( 'complete99_cross_domain_registry_missing', 'The cross-domain binding registry is unavailable.' );
		}
		try {
			$seed = require $path;
			$valid_seed = self::validate_seed_registry( $seed );
			if ( self::is_error( $valid_seed ) ) {
				return $valid_seed;
			}
			self::$seed_registry_cache = $seed;
			$overlay = self::load_decision_overlay( $seed );
			$registry = self::validate_and_merge_overlay( $seed, $overlay, true );
			if ( self::is_error( $registry ) ) {
				return $registry;
			}
			self::$registry_cache = $registry;
			return self::$registry_cache;
		} catch ( Throwable $error ) {
			self::$registry_cache = null;
			self::$seed_registry_cache = null;
			self::$decision_overlay_cache = null;
			self::$indexes_cache  = null;
			self::$status_cache   = null;
			self::$indexes_valid_cache = false;
			return self::error( 'complete99_cross_domain_registry_invalid', 'The cross-domain binding registry failed validation.' );
		}
	}

	/**
	 * Validate the exact v3 merged shape, evidence, coverage and projection boundary.
	 *
	 * @param mixed $registry Candidate registry.
	 * @return true|WP_Error
	 */
	public static function validate_registry( $registry ) {
		try {
			self::assert_registry_valid( $registry );
			return true;
		} catch ( Throwable $error ) {
			return self::error(
				'complete99_cross_domain_registry_invalid',
				'The cross-domain binding registry failed validation.',
				array( 'path' => $error->getMessage() )
			);
		}
	}

	/**
	 * Validate that the checked-in registry remains a decision-free seed.
	 *
	 * @param mixed $registry Candidate seed registry.
	 * @return true|WP_Error
	 */
	public static function validate_seed_registry( $registry ) {
		try {
			self::assert_seed_registry_valid( $registry );
			return true;
		} catch ( Throwable $error ) {
			return self::error(
				'complete99_cross_domain_seed_invalid',
				'The cross-domain binding seed failed validation.',
				array( 'path' => $error->getMessage() )
			);
		}
	}

	/**
	 * Validate a decision overlay against an exact seed without applying it to
	 * request-local state.
	 *
	 * @param mixed $overlay Candidate decision overlay.
	 * @param mixed $seed    Exact seed registry.
	 * @return true|WP_Error
	 */
	public static function validate_decision_overlay( $overlay, $seed ) {
		$merged = self::validate_and_merge_overlay( $seed, $overlay, false );
		return self::is_error( $merged ) ? $merged : true;
	}

	/**
	 * Validate, then deterministically apply, an exact decision overlay.
	 *
	 * Runtime callers require the class-pinned overlay digest. The optional
	 * unpinned mode exists only for offline validation of a proposed overlay;
	 * it cannot alter the registry or any public index used by this request.
	 *
	 * @param mixed $seed                  Exact seed registry.
	 * @param mixed $overlay               Candidate decision overlay.
	 * @param bool  $require_pinned_digest Require the release-pinned digest.
	 * @return array|WP_Error
	 */
	public static function validate_and_merge_overlay( $seed, $overlay, $require_pinned_digest = false ) {
		try {
			self::assert_seed_registry_valid( $seed );
			self::assert_decision_overlay_valid( $overlay, $seed, $require_pinned_digest );
			$merged = self::merge_decisions( $seed, $overlay['decisions'] );
			self::assert_registry_valid( $merged );
			return $merged;
		} catch ( Throwable $error ) {
			return self::error(
				'complete99_cross_domain_decision_overlay_invalid',
				'The cross-domain binding decision overlay failed validation.',
				array( 'path' => $error->getMessage() )
			);
		}
	}

	private static function assert_registry_valid( $registry ) {
		self::assert_exact_keys(
			$registry,
			array( 'schema', 'version', 'generated_at', 'input_contracts', 'controlled_vocabulary', 'records' ),
			'registry'
		);
		if ( self::REGISTRY_SCHEMA !== $registry['schema'] ) {
			throw new RuntimeException( 'registry.schema' );
		}
		if ( self::REGISTRY_VERSION !== $registry['version'] ) {
			throw new RuntimeException( 'registry.version' );
		}
		if ( self::GENERATED_AT !== $registry['generated_at'] ) {
			throw new RuntimeException( 'registry.generated_at' );
		}

		$vocabulary = self::expected_vocabulary();
		self::assert_exact_keys( $registry['controlled_vocabulary'], array_keys( $vocabulary ), 'registry.controlled_vocabulary' );
		foreach ( $vocabulary as $key => $expected ) {
			self::assert_exact_list( $registry['controlled_vocabulary'][ $key ], $expected, 'registry.controlled_vocabulary.' . $key );
		}

		$inputs = self::load_inputs();
		self::validate_input_contracts( $registry['input_contracts'], $inputs );
		$source = self::build_source_indexes( $inputs );
		self::validate_records( $registry['records'], $source, $vocabulary );
	}

	private static function assert_seed_registry_valid( $registry ) {
		self::assert_registry_valid( $registry );
		$digest = self::canonical_payload_digest( $registry );
		if ( ! hash_equals( self::SEED_PAYLOAD_SHA256, $digest ) ) {
			throw new RuntimeException( 'seed.payload_sha256' );
		}

		$candidate_count = 0;
		foreach ( $registry['records'] as $offset => $record ) {
			$path = 'seed.records.' . $offset;
			if ( 'unresolved' !== $record['resolution_state']
				|| ! empty( $record['targets'] )
				|| array( 'he' => '', 'en' => '' ) !== $record['decision_note']
				|| array( 'state' => 'unreviewed', 'reviewer_id' => '', 'reviewed_at' => '', 'next_review_at' => '' ) !== $record['review']
				|| '' !== $record['valid_from']
				|| '' !== $record['valid_to'] ) {
				throw new RuntimeException( $path . '.decision_free' );
			}
			foreach ( $record['candidates'] as $candidate ) {
				++$candidate_count;
				$expected_reason = self::expected_candidate_reason( $record['subject']['entity_id'], $candidate['entity_id'] );
				if ( 'pending_review' !== $candidate['state'] || $expected_reason !== $candidate['reason_code'] ) {
					throw new RuntimeException( $path . '.candidate_state' );
				}
			}
		}
		if ( 11 !== $candidate_count ) {
			throw new RuntimeException( 'seed.records.candidate_count' );
		}
	}

	private static function load_decision_overlay( $seed ) {
		if ( is_array( self::$decision_overlay_cache ) ) {
			return self::$decision_overlay_cache;
		}
		$path = COMPLETE99_PLATFORM_DIR . 'data/' . self::DECISION_FILE;
		if ( ! is_readable( $path ) ) {
			throw new RuntimeException( 'decision_overlay.missing' );
		}
		$overlay = require $path;
		self::assert_decision_overlay_valid( $overlay, $seed, true );
		self::$decision_overlay_cache = $overlay;
		return self::$decision_overlay_cache;
	}

	private static function assert_decision_overlay_valid( $overlay, $seed, $require_pinned_digest ) {
		self::assert_exact_keys(
			$overlay,
			array( 'schema', 'version', 'generated_at', 'seed_contract', 'input_contracts_sha256', 'reviewer_authorities_sha256', 'decision_count', 'decisions_sha256', 'decisions' ),
			'decision_overlay'
		);
		if ( self::DECISION_OVERLAY_SCHEMA !== $overlay['schema'] ) {
			throw new RuntimeException( 'decision_overlay.schema' );
		}
		if ( self::DECISION_OVERLAY_VERSION !== $overlay['version'] ) {
			throw new RuntimeException( 'decision_overlay.version' );
		}
		if ( self::GENERATED_AT !== $overlay['generated_at'] ) {
			throw new RuntimeException( 'decision_overlay.generated_at' );
		}

		$seed_digest = self::canonical_payload_digest( $seed );
		$record_ids = array_column( $seed['records'], 'id' );
		self::assert_exact_keys(
			$overlay['seed_contract'],
			array( 'schema', 'version', 'payload_sha256', 'record_count', 'record_ids_sha256' ),
			'decision_overlay.seed_contract'
		);
		if ( self::REGISTRY_SCHEMA !== $overlay['seed_contract']['schema']
			|| self::REGISTRY_VERSION !== $overlay['seed_contract']['version']
			|| ! is_int( $overlay['seed_contract']['record_count'] )
			|| count( $seed['records'] ) !== $overlay['seed_contract']['record_count'] ) {
			throw new RuntimeException( 'decision_overlay.seed_contract.identity' );
		}
		self::assert_sha256( $overlay['seed_contract']['payload_sha256'], 'decision_overlay.seed_contract.payload_sha256' );
		self::assert_sha256( $overlay['seed_contract']['record_ids_sha256'], 'decision_overlay.seed_contract.record_ids_sha256' );
		if ( ! hash_equals( $seed_digest, $overlay['seed_contract']['payload_sha256'] )
			|| ! hash_equals( self::canonical_payload_digest( $record_ids ), $overlay['seed_contract']['record_ids_sha256'] ) ) {
			throw new RuntimeException( 'decision_overlay.seed_contract.digest' );
		}

		self::assert_sha256( $overlay['input_contracts_sha256'], 'decision_overlay.input_contracts_sha256' );
		if ( ! hash_equals( self::canonical_payload_digest( $seed['input_contracts'] ), $overlay['input_contracts_sha256'] ) ) {
			throw new RuntimeException( 'decision_overlay.input_contracts_sha256' );
		}
		self::assert_sha256( $overlay['reviewer_authorities_sha256'], 'decision_overlay.reviewer_authorities_sha256' );
		$reviewer_authorities = self::recognized_reviewer_authorities();
		self::validate_reviewer_authority_map( $reviewer_authorities, 'reviewer_authorities' );
		if ( ! hash_equals( self::canonical_payload_digest( $reviewer_authorities ), $overlay['reviewer_authorities_sha256'] ) ) {
			throw new RuntimeException( 'decision_overlay.reviewer_authorities_sha256' );
		}

		self::assert_list( $overlay['decisions'], 'decision_overlay.decisions', true );
		if ( ! is_int( $overlay['decision_count'] ) || count( $overlay['decisions'] ) !== $overlay['decision_count'] ) {
			throw new RuntimeException( 'decision_overlay.decision_count' );
		}
		self::assert_sha256( $overlay['decisions_sha256'], 'decision_overlay.decisions_sha256' );
		if ( ! hash_equals( self::canonical_payload_digest( $overlay['decisions'] ), $overlay['decisions_sha256'] ) ) {
			throw new RuntimeException( 'decision_overlay.decisions_sha256' );
		}
		if ( $require_pinned_digest && ! hash_equals( self::DECISION_OVERLAY_PAYLOAD_SHA256, self::canonical_payload_digest( $overlay ) ) ) {
			throw new RuntimeException( 'decision_overlay.payload_sha256' );
		}

		$seed_by_id = array();
		foreach ( $seed['records'] as $record ) {
			$seed_by_id[ $record['id'] ] = $record;
		}
		$previous_id = '';
		$seen_ids = array();
		foreach ( $overlay['decisions'] as $offset => $decision ) {
			$path = 'decision_overlay.decisions.' . $offset;
			self::assert_exact_keys(
				$decision,
				array( 'record_id', 'seed_record_sha256', 'resolution_state', 'targets', 'candidates', 'decision_evidence_refs', 'decision_note', 'review', 'valid_from', 'valid_to' ),
				$path
			);
			self::assert_identifier( $decision['record_id'], $path . '.record_id', 240 );
			if ( ! isset( $seed_by_id[ $decision['record_id'] ] ) ) {
				throw new RuntimeException( $path . '.record_id.unknown' );
			}
			if ( isset( $seen_ids[ $decision['record_id'] ] ) ) {
				throw new RuntimeException( $path . '.record_id.conflict' );
			}
			if ( '' !== $previous_id && strcmp( $previous_id, $decision['record_id'] ) >= 0 ) {
				throw new RuntimeException( $path . '.record_id.order' );
			}
			$seen_ids[ $decision['record_id'] ] = true;
			$previous_id = $decision['record_id'];

			self::assert_sha256( $decision['seed_record_sha256'], $path . '.seed_record_sha256' );
			if ( ! hash_equals( self::canonical_payload_digest( $seed_by_id[ $decision['record_id'] ] ), $decision['seed_record_sha256'] ) ) {
				throw new RuntimeException( $path . '.seed_record_sha256' );
			}
			if ( ! in_array( $decision['resolution_state'], array( 'linked', 'no_match' ), true ) ) {
				throw new RuntimeException( $path . '.resolution_state' );
			}
			self::assert_list( $decision['targets'], $path . '.targets', true );
			self::assert_list( $decision['candidates'], $path . '.candidates', true );
			self::validate_translation( $decision['decision_note'], $path . '.decision_note', false );
			self::validate_review( $decision['review'], self::expected_vocabulary(), $path . '.review' );
			if ( 'verified' !== $decision['review']['state'] ) {
				throw new RuntimeException( $path . '.review.state' );
			}
			self::assert_date( $decision['valid_from'], $path . '.valid_from' );
			self::assert_date( $decision['valid_to'], $path . '.valid_to', true );
			self::assert_seed_candidate_dispositions( $seed_by_id[ $decision['record_id'] ], $decision, $path );
		}
	}

	private static function assert_seed_candidate_dispositions( $seed_record, $decision, $path ) {
		$expected = self::relation_identity_tokens( $seed_record['candidates'], $path . '.seed_candidates' );
		$actual = self::relation_identity_tokens( array_merge( $decision['targets'], $decision['candidates'] ), $path . '.decision_relations' );
		foreach ( $decision['candidates'] as $offset => $candidate ) {
			if ( ! is_array( $candidate ) || 'rejected' !== ( $candidate['state'] ?? '' ) ) {
				throw new RuntimeException( $path . '.candidates.' . $offset . '.state' );
			}
		}
		if ( $expected !== $actual && ! empty( $expected ) ) {
			throw new RuntimeException( $path . '.candidate_dispositions' );
		}
		if ( 'woo-product--product-bulgur-fine-500g' === $seed_record['id'] ) {
			foreach ( $decision['targets'] as $offset => $target ) {
				if ( 'ingredient-syrian-bulgur' === ( $target['entity_id'] ?? '' )
					&& 'reference_only' !== ( $target['relation'] ?? '' ) ) {
					throw new RuntimeException( $path . '.targets.' . $offset . '.scope_mismatch' );
				}
			}
		}
	}

	private static function relation_identity_tokens( $relations, $path ) {
		$tokens = array();
		foreach ( $relations as $offset => $relation ) {
			if ( ! is_array( $relation ) || ! isset( $relation['registry'], $relation['entity_type'], $relation['entity_id'] ) ) {
				throw new RuntimeException( $path . '.' . $offset . '.identity' );
			}
			$token = $relation['registry'] . "\0" . $relation['entity_type'] . "\0" . $relation['entity_id'];
			if ( isset( $tokens[ $token ] ) ) {
				throw new RuntimeException( $path . '.' . $offset . '.duplicate' );
			}
			$tokens[ $token ] = true;
		}
		$result = array_keys( $tokens );
		sort( $result, SORT_STRING );
		return $result;
	}

	private static function merge_decisions( $seed, $decisions ) {
		$merged = $seed;
		$offset_by_id = array();
		foreach ( $merged['records'] as $offset => $record ) {
			$offset_by_id[ $record['id'] ] = $offset;
		}
		$fields = array( 'resolution_state', 'targets', 'candidates', 'decision_evidence_refs', 'decision_note', 'review', 'valid_from', 'valid_to' );
		foreach ( $decisions as $decision ) {
			$offset = $offset_by_id[ $decision['record_id'] ];
			foreach ( $fields as $field ) {
				$merged['records'][ $offset ][ $field ] = $decision[ $field ];
			}
		}
		return $merged;
	}

	private static function recognized_reviewer_authorities() {
		// Deliberately empty: no named binding reviewer authority is approved.
		// A future authority must be checked into reviewed code with an exact
		// identity/evidence receipt before any overlay decision can validate.
		return array();
	}

	private static function validate_reviewer_authority_map( $authorities, $path ) {
		if ( ! is_array( $authorities ) ) {
			throw new RuntimeException( $path );
		}
		$keys = array_keys( $authorities );
		$sorted_keys = $keys;
		sort( $sorted_keys, SORT_STRING );
		if ( $keys !== $sorted_keys ) {
			throw new RuntimeException( $path . '.order' );
		}
		foreach ( $authorities as $reviewer_id => $authority ) {
			self::assert_identifier( $reviewer_id, $path . '.reviewer_id', 120 );
			if ( 0 !== strpos( $reviewer_id, 'person:' ) && 0 !== strpos( $reviewer_id, 'wp-user:' ) ) {
				throw new RuntimeException( $path . '.reviewer_id.namespace' );
			}
			self::assert_exact_keys( $authority, array( 'authority', 'record_id', 'evidence_sha256', 'valid_from', 'valid_to' ), $path . '.' . $reviewer_id );
			self::assert_identifier( $authority['authority'], $path . '.' . $reviewer_id . '.authority', 100 );
			self::assert_identifier( $authority['record_id'], $path . '.' . $reviewer_id . '.record_id', 160 );
			self::assert_sha256( $authority['evidence_sha256'], $path . '.' . $reviewer_id . '.evidence_sha256' );
			self::assert_date( $authority['valid_from'], $path . '.' . $reviewer_id . '.valid_from' );
			self::assert_date( $authority['valid_to'], $path . '.' . $reviewer_id . '.valid_to', true );
			if ( '' !== $authority['valid_to'] && strcmp( $authority['valid_to'], $authority['valid_from'] ) < 0 ) {
				throw new RuntimeException( $path . '.' . $reviewer_id . '.validity' );
			}
		}
	}

	private static function assert_recognized_reviewer_authority( $review, $path ) {
		$authorities = self::recognized_reviewer_authorities();
		self::validate_reviewer_authority_map( $authorities, 'reviewer_authorities' );
		$reviewer_id = $review['reviewer_id'];
		if ( ! isset( $authorities[ $reviewer_id ] ) || ! is_array( $authorities[ $reviewer_id ] ) ) {
			throw new RuntimeException( $path . '.reviewer_authority' );
		}
		$authority = $authorities[ $reviewer_id ];
		self::assert_exact_keys( $authority, array( 'authority', 'record_id', 'evidence_sha256', 'valid_from', 'valid_to' ), $path . '.reviewer_authority' );
		self::assert_identifier( $authority['authority'], $path . '.reviewer_authority.authority', 100 );
		self::assert_identifier( $authority['record_id'], $path . '.reviewer_authority.record_id', 160 );
		self::assert_sha256( $authority['evidence_sha256'], $path . '.reviewer_authority.evidence_sha256' );
		self::assert_date( $authority['valid_from'], $path . '.reviewer_authority.valid_from' );
		self::assert_date( $authority['valid_to'], $path . '.reviewer_authority.valid_to', true );
		if ( strcmp( $review['reviewed_at'], $authority['valid_from'] ) < 0
			|| ( '' !== $authority['valid_to'] && strcmp( $review['reviewed_at'], $authority['valid_to'] ) > 0 ) ) {
			throw new RuntimeException( $path . '.reviewer_authority.validity' );
		}
	}

	/**
	 * Return deterministic linked indexes. Invalid and unresolved registries
	 * deliberately return five literal empty arrays.
	 *
	 * @param bool $fresh Bypass request-local caches.
	 * @return array
	 */
	public static function indexes( $fresh = false ) {
		if ( $fresh ) {
			self::$indexes_cache = null;
			self::$status_cache  = null;
			self::$indexes_valid_cache = false;
		}
		if ( is_array( self::$indexes_cache ) ) {
			return self::$indexes_cache;
		}
		$empty    = self::empty_indexes();
		$registry = self::registry( $fresh );
		if ( self::is_error( $registry ) ) {
			return $empty;
		}
		try {
			$inputs  = self::load_inputs();
			$source  = self::build_source_indexes( $inputs );
			$indexes = self::build_indexes( $registry, $source );
			self::$indexes_cache = $indexes;
			self::$indexes_valid_cache = true;
			return self::$indexes_cache;
		} catch ( Throwable $error ) {
			self::$indexes_valid_cache = false;
			return $empty;
		}
	}

	/**
	 * Bounded status without candidates, reviewers, notes, evidence or errors.
	 *
	 * @param bool $fresh Bypass request-local caches.
	 * @return array
	 */
	public static function status( $fresh = false ) {
		if ( $fresh ) {
			self::$status_cache = null;
		}
		if ( is_array( self::$status_cache ) ) {
			return self::$status_cache;
		}
		$status   = self::empty_status();
		$registry = self::registry( $fresh );
		if ( self::is_error( $registry ) ) {
			return $status;
		}
		$indexes = self::indexes();
		if ( ! self::$indexes_valid_cache ) {
			return $status;
		}
		$status['registry_valid'] = true;
		$status['record_count']   = count( $registry['records'] );
		foreach ( $registry['records'] as $record ) {
			if ( 'menu_dish_science_dish' === $record['kind'] ) {
				++$status['dish_subject_count'];
			} elseif ( 'menu_component_science_entity' === $record['kind'] ) {
				++$status['component_subject_count'];
			} elseif ( 'woo_product_science_entity' === $record['kind'] ) {
				++$status['product_subject_count'];
			}
			if ( 'linked' === $record['resolution_state'] ) {
				++$status['linked_count'];
			} elseif ( 'no_match' === $record['resolution_state'] ) {
				++$status['no_match_count'];
			} elseif ( 'unresolved' === $record['resolution_state'] ) {
				++$status['unresolved_count'];
			}
		}
		$status['decision_count'] = $status['linked_count'] + $status['no_match_count'];
		$status['recognized_reviewer_authority_count'] = count( self::recognized_reviewer_authorities() );
		$status['public_navigation_count']         = self::reverse_index_subject_count( $indexes['public_navigation'] );
		$status['public_product_navigation_count'] = self::reverse_index_subject_count( $indexes['public_product_navigation'] );
		self::$status_cache = $status;
		return self::$status_cache;
	}

	/**
	 * Private editorial material for a separately capability-gated consumer.
	 *
	 * @param bool $fresh Bypass request-local caches.
	 * @return array
	 */
	public static function editorial_snapshot( $fresh = false ) {
		$registry = self::registry( $fresh );
		if ( self::is_error( $registry ) ) {
			return array();
		}
		return array(
			'registry' => $registry,
			'decision_overlay' => array(
				'schema'                              => self::DECISION_OVERLAY_SCHEMA,
				'version'                             => self::DECISION_OVERLAY_VERSION,
				'valid'                               => is_array( self::$decision_overlay_cache ),
				'decision_count'                      => is_array( self::$decision_overlay_cache ) ? count( self::$decision_overlay_cache['decisions'] ) : 0,
				'recognized_reviewer_authority_count' => count( self::recognized_reviewer_authorities() ),
			),
			'indexes'  => self::indexes(),
			'status'   => self::status(),
		);
	}

	/**
	 * Resolve exact public targets for an exact subject identity only.
	 *
	 * @param string $kind            Binding kind.
	 * @param string $entity_id       Exact subject identifier.
	 * @param string $scope_entity_id Exact dish scope for component subjects.
	 * @return array
	 */
	public static function public_targets_for_subject( $kind, $entity_id, $scope_entity_id = '' ) {
		$indexes = self::indexes();
		if ( ! isset( $indexes[ $kind ] ) || ! is_array( $indexes[ $kind ] ) ) {
			return array();
		}
		$subject = self::subject_for_lookup( $kind, $entity_id, $scope_entity_id );
		if ( empty( $subject ) ) {
			return array();
		}
		$key = self::subject_key( $subject );
		if ( ! isset( $indexes[ $kind ][ $key ]['targets'] ) ) {
			return array();
		}
		$targets = array();
		foreach ( $indexes[ $kind ][ $key ]['targets'] as $target ) {
			if ( in_array( $target['projection_scope'], array( 'public_navigation', 'public_product_navigation' ), true ) ) {
				$targets[] = $target;
			}
		}
		return $targets;
	}

	private static function validate_input_contracts( $contracts, $inputs ) {
		$expected = self::input_contract_definitions();
		self::assert_exact_keys( $contracts, array_keys( $expected ), 'registry.input_contracts' );
		foreach ( $expected as $key => $definition ) {
			$path = 'registry.input_contracts.' . $key;
			self::assert_exact_keys( $contracts[ $key ], array( 'source_path', 'source_schema', 'source_version', 'payload_sha256' ), $path );
			foreach ( array( 'source_path', 'source_schema', 'source_version' ) as $field ) {
				if ( $definition[ $field ] !== $contracts[ $key ][ $field ] ) {
					throw new RuntimeException( $path . '.' . $field );
				}
			}
			self::assert_sha256( $contracts[ $key ]['payload_sha256'], $path . '.payload_sha256' );
			if ( isset( $definition['payload_sha256'] )
				&& ! hash_equals( $definition['payload_sha256'], $contracts[ $key ]['payload_sha256'] ) ) {
				throw new RuntimeException( $path . '.payload_sha256' );
			}
			if ( ! isset( self::$input_digest_cache[ $key ] ) ) {
				self::$input_digest_cache[ $key ] = self::canonical_payload_digest( $inputs[ $key ] );
			}
			$digest = self::$input_digest_cache[ $key ];
			if ( ! hash_equals( $digest, $contracts[ $key ]['payload_sha256'] ) ) {
				throw new RuntimeException( $path . '.payload_sha256' );
			}
		}
	}

	private static function validate_records( $records, $source, $vocabulary ) {
		self::assert_list( $records, 'registry.records', false );
		$expected_count = self::EXPECTED_DISH_SUBJECTS + self::EXPECTED_COMPONENT_SUBJECTS + self::EXPECTED_PRODUCT_SUBJECTS;
		if ( $expected_count !== count( $records ) ) {
			throw new RuntimeException( 'registry.records.count' );
		}

		$seen_ids      = array();
		$seen_subjects = array();
		$covered       = array();
		$previous_id   = '';
		foreach ( $records as $offset => $record ) {
			$path = 'registry.records.' . $offset;
			self::assert_exact_keys(
				$record,
				array( 'id', 'kind', 'subject', 'resolution_state', 'targets', 'candidates', 'decision_evidence_refs', 'decision_note', 'review', 'valid_from', 'valid_to' ),
				$path
			);
			self::assert_identifier( $record['id'], $path . '.id', 240 );
			if ( isset( $seen_ids[ $record['id'] ] ) ) {
				throw new RuntimeException( $path . '.id.duplicate' );
			}
			if ( '' !== $previous_id && strcmp( $previous_id, $record['id'] ) >= 0 ) {
				throw new RuntimeException( $path . '.id.order' );
			}
			$seen_ids[ $record['id'] ] = true;
			$previous_id                = $record['id'];

			self::assert_enum( $record['kind'], $vocabulary['binding_kinds'], $path . '.kind' );
			self::assert_enum( $record['resolution_state'], $vocabulary['resolution_states'], $path . '.resolution_state' );
			self::validate_subject( $record, $source, $path );
			$subject_key = $record['kind'] . "\0" . self::subject_key( $record['subject'] );
			if ( isset( $seen_subjects[ $subject_key ] ) ) {
				throw new RuntimeException( $path . '.subject.duplicate' );
			}
			$seen_subjects[ $subject_key ] = true;
			$covered[ $record['id'] ]      = true;

			self::validate_evidence_list( $record['decision_evidence_refs'], $source['evidence'], $path . '.decision_evidence_refs', true );
			self::validate_translation( $record['decision_note'], $path . '.decision_note', true );
			self::validate_review( $record['review'], $vocabulary, $path . '.review' );
			self::assert_date( $record['valid_from'], $path . '.valid_from', true );
			self::assert_date( $record['valid_to'], $path . '.valid_to', true );
			if ( '' !== $record['valid_to'] && ( '' === $record['valid_from'] || strcmp( $record['valid_to'], $record['valid_from'] ) < 0 ) ) {
				throw new RuntimeException( $path . '.validity' );
			}

			$targets    = self::validate_targets( $record, $source, $vocabulary, $path . '.targets' );
			$candidates = self::validate_candidates( $record, $source, $vocabulary, $path . '.candidates' );
			self::validate_state_rules( $record, $targets, $candidates, $path );
		}

		$expected_ids = array_keys( $source['expected_records'] );
		sort( $expected_ids, SORT_STRING );
		$actual_ids = array_keys( $covered );
		sort( $actual_ids, SORT_STRING );
		if ( $expected_ids !== $actual_ids ) {
			throw new RuntimeException( 'registry.records.coverage' );
		}
		self::validate_reciprocal_woo_coverage( $records, $source );
	}

	private static function validate_subject( $record, $source, $path ) {
		$subject = $record['subject'];
		self::assert_exact_keys( $subject, array( 'registry', 'entity_type', 'entity_id', 'scope_entity_id' ), $path . '.subject' );
		foreach ( array( 'registry', 'entity_type', 'entity_id' ) as $field ) {
			self::assert_identifier( $subject[ $field ], $path . '.subject.' . $field, 160 );
		}
		self::assert_identifier( $subject['scope_entity_id'], $path . '.subject.scope_entity_id', 160, true );

		$expected_id = self::expected_record_id( $record['kind'], $subject );
		if ( '' === $expected_id || $record['id'] !== $expected_id || ! isset( $source['expected_records'][ $expected_id ] ) ) {
			throw new RuntimeException( $path . '.subject.coverage' );
		}
		if ( self::subject_key( $source['expected_records'][ $expected_id ] ) !== self::subject_key( $subject ) ) {
			throw new RuntimeException( $path . '.subject.identity' );
		}
	}

	private static function validate_targets( $record, $source, $vocabulary, $path ) {
		self::assert_list( $record['targets'], $path, true );
		if ( count( $record['targets'] ) > 1 ) {
			throw new RuntimeException( $path . '.conflict' );
		}
		$seen    = array();
		$targets = array();
		foreach ( $record['targets'] as $offset => $target ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $target, array( 'registry', 'entity_type', 'entity_id', 'relation', 'projection_scope', 'evidence_refs' ), $item_path );
			if ( 'culinary_science' !== $target['registry'] ) {
				throw new RuntimeException( $item_path . '.registry' );
			}
			self::assert_enum( $target['entity_type'], $vocabulary['entity_types'], $item_path . '.entity_type' );
			self::assert_identifier( $target['entity_id'], $item_path . '.entity_id', 120 );
			self::assert_enum( $target['relation'], $vocabulary['relations'], $item_path . '.relation' );
			self::assert_enum( $target['projection_scope'], $vocabulary['projection_scopes'], $item_path . '.projection_scope' );
			if ( ! isset( $source['science_entities'][ $target['entity_id'] ] ) || $target['entity_type'] !== $source['science_entities'][ $target['entity_id'] ]['type'] ) {
				throw new RuntimeException( $item_path . '.target' );
			}
			if ( 'woo_product_science_entity' === $record['kind'] && ! isset( $source['reciprocal_woo_edges'][ $record['subject']['entity_id'] ][ $target['entity_id'] ] ) ) {
				throw new RuntimeException( $item_path . '.reciprocal_edge' );
			}
			if ( 'woo_product_science_entity' === $record['kind']
				&& 'product-bulgur-fine-500g' === $record['subject']['entity_id']
				&& 'ingredient-syrian-bulgur' === $target['entity_id']
				&& 'reference_only' !== $target['relation'] ) {
				throw new RuntimeException( $item_path . '.scope_mismatch' );
			}
			self::assert_target_compatibility( $record['kind'], $target, $item_path );
			self::validate_evidence_list( $target['evidence_refs'], $source['evidence'], $item_path . '.evidence_refs', false );
			if ( 'private_only' !== $target['projection_scope'] && ! self::is_public_science_entity( $source['science_entities'][ $target['entity_id'] ] ) ) {
				throw new RuntimeException( $item_path . '.public_target' );
			}
			$identity = $target['registry'] . "\0" . $target['entity_type'] . "\0" . $target['entity_id'];
			if ( isset( $seen[ $identity ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$seen[ $identity ] = true;
			$targets[ $identity ] = $target;
		}
		return $targets;
	}

	private static function validate_candidates( $record, $source, $vocabulary, $path ) {
		self::assert_list( $record['candidates'], $path, true );
		if ( 'woo_product_science_entity' !== $record['kind'] && ! empty( $record['candidates'] ) ) {
			throw new RuntimeException( $path . '.unsupported_kind' );
		}
		$seen       = array();
		$candidates = array();
		foreach ( $record['candidates'] as $offset => $candidate ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $candidate, array( 'registry', 'entity_type', 'entity_id', 'state', 'reason_code' ), $item_path );
			if ( 'culinary_science' !== $candidate['registry'] ) {
				throw new RuntimeException( $item_path . '.registry' );
			}
			self::assert_enum( $candidate['entity_type'], $vocabulary['entity_types'], $item_path . '.entity_type' );
			self::assert_identifier( $candidate['entity_id'], $item_path . '.entity_id', 120 );
			self::assert_enum( $candidate['state'], $vocabulary['candidate_states'], $item_path . '.state' );
			self::assert_enum( $candidate['reason_code'], $vocabulary['candidate_reason_codes'], $item_path . '.reason_code' );
			if ( ! isset( $source['science_entities'][ $candidate['entity_id'] ] ) || $candidate['entity_type'] !== $source['science_entities'][ $candidate['entity_id'] ]['type'] ) {
				throw new RuntimeException( $item_path . '.target' );
			}
			if ( ! isset( $source['reciprocal_woo_edges'][ $record['subject']['entity_id'] ][ $candidate['entity_id'] ] ) ) {
				throw new RuntimeException( $item_path . '.reciprocal_edge' );
			}
			$identity = $candidate['registry'] . "\0" . $candidate['entity_type'] . "\0" . $candidate['entity_id'];
			if ( isset( $seen[ $identity ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$seen[ $identity ]       = true;
			$candidates[ $identity ] = $candidate;
		}
		if ( ! empty( $candidates ) ) {
			$product_code = $record['subject']['entity_id'];
			foreach ( $candidates as $candidate ) {
				$expected_refs = array(
					'culinary_science_registry' . "\0" . $candidate['entity_id'],
					'live_catalog_products' . "\0" . $product_code,
					'live_catalog_relations' . "\0" . $product_code,
				);
				$actual_refs = array();
				foreach ( $record['decision_evidence_refs'] as $ref ) {
					$actual_refs[] = $ref['registry'] . "\0" . $ref['record_id'];
				}
				if ( $expected_refs !== $actual_refs ) {
					throw new RuntimeException( $path . '.decision_evidence' );
				}
				$expected_reason = self::expected_candidate_reason( $product_code, $candidate['entity_id'] );
				if ( 'pending_review' === $candidate['state'] && $expected_reason !== $candidate['reason_code'] ) {
					throw new RuntimeException( $path . '.pending_reason' );
				}
			}
		}
		return $candidates;
	}

	private static function expected_candidate_reason( $product_code, $entity_id ) {
		return 'product-bulgur-fine-500g' === $product_code && 'ingredient-syrian-bulgur' === $entity_id
			? 'scope_mismatch'
			: 'legacy_explicit_relation_requires_review';
	}

	private static function validate_reciprocal_woo_coverage( $records, $source ) {
		$covered = array();
		foreach ( $records as $record ) {
			if ( 'woo_product_science_entity' !== $record['kind'] ) {
				continue;
			}
			$product_code = $record['subject']['entity_id'];
			foreach ( array_merge( $record['targets'], $record['candidates'] ) as $relation ) {
				$key = $product_code . "\0" . $relation['entity_id'];
				if ( isset( $covered[ $key ] ) ) {
					throw new RuntimeException( 'registry.records.woo_edge_duplicate' );
				}
				$covered[ $key ] = true;
			}
		}
		$expected = array();
		foreach ( $source['reciprocal_woo_edges'] as $product_code => $entities ) {
			foreach ( array_keys( $entities ) as $entity_id ) {
				$expected[ $product_code . "\0" . $entity_id ] = true;
			}
		}
		$actual_keys   = array_keys( $covered );
		$expected_keys = array_keys( $expected );
		sort( $actual_keys, SORT_STRING );
		sort( $expected_keys, SORT_STRING );
		if ( $actual_keys !== $expected_keys ) {
			throw new RuntimeException( 'registry.records.woo_edge_coverage' );
		}
	}

	private static function validate_state_rules( $record, $targets, $candidates, $path ) {
		$state        = $record['resolution_state'];
		$review_state = $record['review']['state'];
		$has_evidence = ! empty( $record['decision_evidence_refs'] );
		$has_note     = '' !== trim( $record['decision_note']['he'] ) && '' !== trim( $record['decision_note']['en'] );

		foreach ( $targets as $identity => $target ) {
			if ( isset( $candidates[ $identity ] ) ) {
				throw new RuntimeException( $path . '.target_candidate_conflict' );
			}
		}
		if ( 'unresolved' === $state ) {
			if ( ! empty( $targets ) || 'verified' === $review_state || '' !== $record['valid_from'] || '' !== $record['valid_to'] ) {
				throw new RuntimeException( $path . '.unresolved_state' );
			}
			if ( ! empty( $candidates ) && ! $has_evidence ) {
				throw new RuntimeException( $path . '.candidate_evidence' );
			}
			return;
		}
		if ( 'no_match' === $state ) {
			if ( ! empty( $targets ) || in_array( 'pending_review', array_column( $record['candidates'], 'state' ), true ) || 'verified' !== $review_state || ! $has_evidence || ! $has_note || '' === $record['valid_from'] ) {
				throw new RuntimeException( $path . '.no_match_state' );
			}
			self::assert_recognized_reviewer_authority( $record['review'], $path . '.review' );
			self::assert_decision_evidence_mix( $record, $path );
			return;
		}
		if ( 'linked' !== $state || 1 !== count( $targets ) || in_array( 'pending_review', array_column( $record['candidates'], 'state' ), true ) || 'verified' !== $review_state || ! $has_evidence || ! $has_note || '' === $record['valid_from'] ) {
			throw new RuntimeException( $path . '.linked_state' );
		}
		self::assert_recognized_reviewer_authority( $record['review'], $path . '.review' );
		self::assert_decision_evidence_mix( $record, $path );
	}

	private static function assert_decision_evidence_mix( $record, $path ) {
		$registries = array_column( $record['decision_evidence_refs'], 'registry' );
		$has_science = in_array( 'culinary_science_sources', $registries, true ) || in_array( 'culinary_science_registry', $registries, true );
		if ( ! $has_science ) {
			throw new RuntimeException( $path . '.science_evidence' );
		}
		if ( 'woo_product_science_entity' === $record['kind'] ) {
			if ( ! in_array( 'catalog_product_seeds', $registries, true ) && ! in_array( 'live_catalog_products', $registries, true ) ) {
				throw new RuntimeException( $path . '.product_evidence' );
			}
		} elseif ( ! in_array( 'dish_source_registry', $registries, true ) ) {
			throw new RuntimeException( $path . '.dish_evidence' );
		}
	}

	private static function assert_target_compatibility( $kind, $target, $path ) {
		$type     = $target['entity_type'];
		$relation = $target['relation'];
		$scope    = $target['projection_scope'];
		if ( 'menu_dish_science_dish' === $kind ) {
			if ( 'dish' !== $type || ! in_array( $relation, array( 'same_dish_identity', 'house_expression_of', 'reference_only' ), true ) || 'public_product_navigation' === $scope ) {
				throw new RuntimeException( $path . '.compatibility' );
			}
			return;
		}
		if ( 'menu_component_science_entity' === $kind ) {
			$allowed = array(
				'ingredient'  => array( 'same_ingredient_identity', 'reference_only' ),
				'preparation' => array( 'same_preparation_identity', 'reference_only' ),
				'equipment'   => array( 'reference_only' ),
			);
			if ( ! isset( $allowed[ $type ] ) || ! in_array( $relation, $allowed[ $type ], true ) || 'public_product_navigation' === $scope ) {
				throw new RuntimeException( $path . '.compatibility' );
			}
			return;
		}
		if ( 'woo_product_science_entity' !== $kind || ! in_array( $type, array( 'ingredient', 'preparation', 'equipment' ), true ) || ! in_array( $relation, array( 'retail_instance_of', 'reference_only' ), true ) || 'public_navigation' === $scope ) {
			throw new RuntimeException( $path . '.compatibility' );
		}
	}

	private static function validate_review( $review, $vocabulary, $path ) {
		self::assert_exact_keys( $review, array( 'state', 'reviewer_id', 'reviewed_at', 'next_review_at' ), $path );
		self::assert_enum( $review['state'], $vocabulary['review_states'], $path . '.state' );
		self::assert_identifier( $review['reviewer_id'], $path . '.reviewer_id', 120, true );
		self::assert_date( $review['reviewed_at'], $path . '.reviewed_at', true );
		self::assert_date( $review['next_review_at'], $path . '.next_review_at', true );
		if ( 'unreviewed' === $review['state'] ) {
			if ( '' !== $review['reviewer_id'] || '' !== $review['reviewed_at'] || '' !== $review['next_review_at'] ) {
				throw new RuntimeException( $path . '.unreviewed' );
			}
			return;
		}
		if ( '' === $review['reviewer_id'] || '' === $review['reviewed_at'] || '' === $review['next_review_at'] || strcmp( $review['next_review_at'], $review['reviewed_at'] ) < 0 ) {
			throw new RuntimeException( $path . '.reviewed' );
		}
	}

	private static function validate_evidence_list( $refs, $evidence, $path, $allow_empty ) {
		self::assert_list( $refs, $path, $allow_empty );
		$seen = array();
		foreach ( $refs as $offset => $ref ) {
			$item_path = $path . '.' . $offset;
			self::assert_exact_keys( $ref, array( 'registry', 'record_id' ), $item_path );
			self::assert_identifier( $ref['registry'], $item_path . '.registry', 100 );
			self::assert_identifier( $ref['record_id'], $item_path . '.record_id', 160 );
			if ( ! isset( $evidence[ $ref['registry'] ][ $ref['record_id'] ] ) ) {
				throw new RuntimeException( $item_path . '.reference' );
			}
			$key = $ref['registry'] . "\0" . $ref['record_id'];
			if ( isset( $seen[ $key ] ) ) {
				throw new RuntimeException( $item_path . '.duplicate' );
			}
			$seen[ $key ] = true;
		}
	}

	private static function build_source_indexes( $inputs ) {
		if ( is_array( self::$source_index_cache ) ) {
			return self::$source_index_cache;
		}
		$menu = $inputs['consumer_menu'];
		self::assert_list( $menu, 'source.consumer_menu', false );
		if ( self::EXPECTED_DISH_SUBJECTS !== count( $menu ) ) {
			throw new RuntimeException( 'source.consumer_menu.count' );
		}
		$menu_by_id = array();
		$menu_slugs = array();
		foreach ( $menu as $offset => $dish ) {
			if ( ! is_array( $dish ) || ! isset( $dish['id'], $dish['slug'] ) ) {
				throw new RuntimeException( 'source.consumer_menu.' . $offset );
			}
			self::assert_identifier( $dish['id'], 'source.consumer_menu.' . $offset . '.id', 120 );
			self::assert_identifier( $dish['slug'], 'source.consumer_menu.' . $offset . '.slug', 100 );
			if ( isset( $menu_by_id[ $dish['id'] ] ) || isset( $menu_slugs[ $dish['slug'] ] ) ) {
				throw new RuntimeException( 'source.consumer_menu.' . $offset . '.duplicate' );
			}
			$menu_by_id[ $dish['id'] ] = $dish;
			$menu_slugs[ $dish['slug'] ] = $dish['id'];
		}

		$trees = $inputs['dish_entity_trees'];
		if ( ! is_array( $trees ) || 'complete99-dish-entity-tree-registry/v1' !== ( $trees['schema'] ?? '' ) || '2026-07-31' !== ( $trees['registry_reviewed_at'] ?? '' ) || ! isset( $trees['dishes'], $trees['source_registry'] ) ) {
			throw new RuntimeException( 'source.dish_entity_trees.contract' );
		}
		self::assert_list( $trees['dishes'], 'source.dish_entity_trees.dishes', false );
		if ( self::EXPECTED_DISH_SUBJECTS !== count( $trees['dishes'] ) ) {
			throw new RuntimeException( 'source.dish_entity_trees.count' );
		}

		$expected_records = array();
		$tree_dishes      = array();
		foreach ( $menu_by_id as $dish_id => $dish ) {
			$subject = array(
				'registry'        => 'consumer_menu',
				'entity_type'     => 'dish',
				'entity_id'       => $dish_id,
				'scope_entity_id' => '',
			);
			$expected_records[ 'menu-dish--' . $dish_id ] = $subject;
		}
		foreach ( $trees['dishes'] as $offset => $dish ) {
			$path = 'source.dish_entity_trees.dishes.' . $offset;
			if ( ! is_array( $dish ) || ! isset( $dish['dish_id'], $dish['source_record_slug'], $dish['component_tree']['children'], $dish['relations']['ingredient_codes'] ) ) {
				throw new RuntimeException( $path );
			}
			$dish_id = $dish['dish_id'];
			self::assert_identifier( $dish_id, $path . '.dish_id', 120 );
			if ( isset( $tree_dishes[ $dish_id ] ) || ! isset( $menu_by_id[ $dish_id ] ) || $dish['source_record_slug'] !== $menu_by_id[ $dish_id ]['slug'] ) {
				throw new RuntimeException( $path . '.identity' );
			}
			$tree_dishes[ $dish_id ] = true;
			$codes = array();
			self::collect_component_codes( $dish['component_tree']['children'], $codes, $path . '.component_tree.children', 0 );
			self::assert_list( $dish['relations']['ingredient_codes'], $path . '.relations.ingredient_codes', true );
			foreach ( $dish['relations']['ingredient_codes'] as $code_offset => $code ) {
				self::assert_identifier( $code, $path . '.relations.ingredient_codes.' . $code_offset, 120 );
				$codes[ $code ] = true;
			}
			foreach ( array_keys( $codes ) as $code ) {
				$type = 0 === strpos( $code, 'ingredient-' ) ? 'ingredient' : 'component';
				if ( 'component' === $type && 0 !== strpos( $code, 'component-' ) ) {
					throw new RuntimeException( $path . '.component_code' );
				}
				$subject = array(
					'registry'        => 'dish_entity_trees',
					'entity_type'     => $type,
					'entity_id'       => $code,
					'scope_entity_id' => $dish_id,
				);
				$record_id = 'menu-component--' . $dish_id . '--' . $code;
				if ( isset( $expected_records[ $record_id ] ) ) {
					throw new RuntimeException( $path . '.component_duplicate' );
				}
				$expected_records[ $record_id ] = $subject;
			}
		}
		if ( count( $tree_dishes ) !== count( $menu_by_id ) ) {
			throw new RuntimeException( 'source.dish_entity_trees.coverage' );
		}

		$live = $inputs['live_catalog_products'];
		if ( ! is_array( $live ) || 'complete99-live-catalog-products/v1' !== ( $live['schema'] ?? '' ) || '2026-08-06' !== ( $live['reviewed_at'] ?? '' ) || ! isset( $live['products'] ) ) {
			throw new RuntimeException( 'source.live_catalog_products.contract' );
		}
		self::assert_associative_array( $live['products'], 'source.live_catalog_products.products', false );
		if ( self::EXPECTED_PRODUCT_SUBJECTS !== count( $live['products'] ) ) {
			throw new RuntimeException( 'source.live_catalog_products.count' );
		}
		foreach ( $live['products'] as $product_code => $product ) {
			self::assert_identifier( $product_code, 'source.live_catalog_products.products.key', 140 );
			if ( ! is_array( $product ) ) {
				throw new RuntimeException( 'source.live_catalog_products.products.' . $product_code );
			}
			$subject = array(
				'registry'        => 'woocommerce',
				'entity_type'     => 'product',
				'entity_id'       => $product_code,
				'scope_entity_id' => '',
			);
			$expected_records[ 'woo-product--' . $product_code ] = $subject;
		}
		$catalog_seed_products = self::catalog_seed_evidence( $inputs['catalog_product_seeds'] );
		$catalog_seed_codes    = array_keys( $catalog_seed_products );
		$live_seed_codes       = array_keys( $live['products'] );
		sort( $catalog_seed_codes, SORT_STRING );
		sort( $live_seed_codes, SORT_STRING );
		if ( $catalog_seed_codes !== $live_seed_codes ) {
			throw new RuntimeException( 'source.catalog_product_seeds.product_coverage' );
		}

		$dish_count      = count( array_filter( array_keys( $expected_records ), static function ( $id ) { return 0 === strpos( $id, 'menu-dish--' ); } ) );
		$component_count = count( array_filter( array_keys( $expected_records ), static function ( $id ) { return 0 === strpos( $id, 'menu-component--' ); } ) );
		$product_count   = count( array_filter( array_keys( $expected_records ), static function ( $id ) { return 0 === strpos( $id, 'woo-product--' ); } ) );
		if ( self::EXPECTED_DISH_SUBJECTS !== $dish_count || self::EXPECTED_COMPONENT_SUBJECTS !== $component_count || self::EXPECTED_PRODUCT_SUBJECTS !== $product_count ) {
			throw new RuntimeException( 'source.subject_coverage' );
		}

		$science = $inputs['culinary_science'];
		if ( ! is_array( $science ) || 'complete99-culinary-science-registry/v6' !== ( $science['schema'] ?? '' ) || 'culinary-science-2026.08.08.v20' !== ( $science['version'] ?? '' ) || ! isset( $science['entities'], $science['sources'] ) ) {
			throw new RuntimeException( 'source.culinary_science.contract' );
		}
		self::assert_list( $science['entities'], 'source.culinary_science.entities', false );
		$science_entities = array();
		$science_registry_evidence = array();
		$science_woo_edges = array();
		foreach ( $science['entities'] as $offset => $entity ) {
			if ( ! is_array( $entity ) || ! isset( $entity['id'], $entity['type'] ) ) {
				throw new RuntimeException( 'source.culinary_science.entities.' . $offset );
			}
			self::assert_identifier( $entity['id'], 'source.culinary_science.entities.' . $offset . '.id', 120 );
			self::assert_identifier( $entity['type'], 'source.culinary_science.entities.' . $offset . '.type', 80 );
			if ( isset( $science_entities[ $entity['id'] ] ) ) {
				throw new RuntimeException( 'source.culinary_science.entities.' . $offset . '.duplicate' );
			}
			$science_entities[ $entity['id'] ] = $entity;
			$science_registry_evidence[ $entity['id'] ] = true;
			$woo_product_code = isset( $entity['commerce']['woo_product_code'] ) && is_string( $entity['commerce']['woo_product_code'] )
				? $entity['commerce']['woo_product_code']
				: '';
			if ( '' !== $woo_product_code ) {
				self::assert_identifier( $woo_product_code, 'source.culinary_science.entities.' . $offset . '.commerce.woo_product_code', 140 );
				if ( isset( $science_woo_edges[ $woo_product_code ] ) ) {
					throw new RuntimeException( 'source.culinary_science.entities.' . $offset . '.commerce.woo_product_code.duplicate' );
				}
				$science_woo_edges[ $woo_product_code ] = $entity['id'];
			}
			foreach ( isset( $entity['facts'] ) && is_array( $entity['facts'] ) ? $entity['facts'] : array() as $fact ) {
				if ( isset( $fact['id'] ) && is_string( $fact['id'] ) ) {
					$science_registry_evidence[ $fact['id'] ] = true;
				}
			}
		}

		$relations = $inputs['live_catalog_relations'];
		if ( ! is_array( $relations ) || 'complete99-live-catalog-relations/v1' !== ( $relations['schema'] ?? '' ) || '2026-08-06' !== ( $relations['reviewed_at'] ?? '' ) || ! isset( $relations['products'] ) ) {
			throw new RuntimeException( 'source.live_catalog_relations.contract' );
		}
		self::assert_associative_array( $relations['products'], 'source.live_catalog_relations.products', false );
		$live_product_codes = array_keys( $live['products'] );
		$relation_codes     = array_keys( $relations['products'] );
		sort( $live_product_codes, SORT_STRING );
		sort( $relation_codes, SORT_STRING );
		if ( $live_product_codes !== $relation_codes ) {
			throw new RuntimeException( 'source.live_catalog_relations.product_coverage' );
		}
		$relation_woo_edges = array();
		foreach ( $relations['products'] as $product_code => $relation ) {
			if ( ! is_array( $relation ) ) {
				throw new RuntimeException( 'source.live_catalog_relations.products.' . $product_code );
			}
			$entity_id = isset( $relation['science_entity_id'] ) && is_string( $relation['science_entity_id'] ) ? $relation['science_entity_id'] : '';
			if ( '' === $entity_id ) {
				continue;
			}
			self::assert_identifier( $entity_id, 'source.live_catalog_relations.products.' . $product_code . '.science_entity_id', 120 );
			if ( ! isset( $science_entities[ $entity_id ] ) || ! isset( $science_woo_edges[ $product_code ] ) || $science_woo_edges[ $product_code ] !== $entity_id ) {
				throw new RuntimeException( 'source.live_catalog_relations.products.' . $product_code . '.reciprocal_edge' );
			}
			$relation_woo_edges[ $product_code ] = $entity_id;
		}
		$science_edge_tokens  = array();
		$relation_edge_tokens = array();
		foreach ( $science_woo_edges as $product_code => $entity_id ) {
			$science_edge_tokens[] = $product_code . "\0" . $entity_id;
		}
		foreach ( $relation_woo_edges as $product_code => $entity_id ) {
			$relation_edge_tokens[] = $product_code . "\0" . $entity_id;
		}
		sort( $science_edge_tokens, SORT_STRING );
		sort( $relation_edge_tokens, SORT_STRING );
		if ( $science_edge_tokens !== $relation_edge_tokens ) {
			throw new RuntimeException( 'source.live_catalog_relations.reciprocal_coverage' );
		}
		$reciprocal_woo_edges = array();
		foreach ( $relation_woo_edges as $product_code => $entity_id ) {
			$reciprocal_woo_edges[ $product_code ] = array( $entity_id => true );
		}

		$evidence = array(
			'dish_source_registry'      => self::key_set( $trees['source_registry'], 'source.dish_entity_trees.source_registry' ),
			'culinary_science_sources'  => self::key_set( $science['sources'], 'source.culinary_science.sources' ),
			'culinary_science_registry' => $science_registry_evidence,
			'catalog_product_seeds'     => $catalog_seed_products,
			'live_catalog_products'     => self::key_set( $live['products'], 'source.live_catalog_products.products' ),
			'live_catalog_relations'    => self::key_set( $relations['products'], 'source.live_catalog_relations.products' ),
		);

		self::$source_index_cache = array(
			'expected_records' => $expected_records,
			'science_entities' => $science_entities,
			'reciprocal_woo_edges' => $reciprocal_woo_edges,
			'evidence'         => $evidence,
		);
		return self::$source_index_cache;
	}

	private static function collect_component_codes( $nodes, &$codes, $path, $depth ) {
		if ( $depth > 16 ) {
			throw new RuntimeException( $path . '.depth' );
		}
		self::assert_list( $nodes, $path, true );
		foreach ( $nodes as $offset => $node ) {
			$item_path = $path . '.' . $offset;
			if ( ! is_array( $node ) || ! isset( $node['code'], $node['children'] ) ) {
				throw new RuntimeException( $item_path );
			}
			self::assert_identifier( $node['code'], $item_path . '.code', 120 );
			$codes[ $node['code'] ] = true;
			self::collect_component_codes( $node['children'], $codes, $item_path . '.children', $depth + 1 );
		}
	}

	private static function build_indexes( $registry, $source ) {
		$indexes = self::empty_indexes();
		foreach ( $registry['records'] as $record ) {
			if ( 'linked' !== $record['resolution_state'] || 'verified' !== $record['review']['state'] ) {
				continue;
			}
			$targets = array();
			foreach ( $record['targets'] as $target ) {
				$descriptor = array(
					'entity_type'     => $target['entity_type'],
					'entity_id'       => $target['entity_id'],
					'relation'        => $target['relation'],
					'projection_scope' => $target['projection_scope'],
				);
				$targets[] = $descriptor;
			}
			usort( $targets, array( __CLASS__, 'compare_target_descriptors' ) );
			$key = self::subject_key( $record['subject'] );
			$indexes[ $record['kind'] ][ $key ] = array(
				'registry'        => $record['subject']['registry'],
				'entity_type'     => $record['subject']['entity_type'],
				'entity_id'       => $record['subject']['entity_id'],
				'scope_entity_id' => $record['subject']['scope_entity_id'],
				'targets'         => $targets,
			);

			foreach ( $record['targets'] as $target ) {
				$scope = $target['projection_scope'];
				if ( ! in_array( $scope, array( 'public_navigation', 'public_product_navigation' ), true ) || ! self::is_public_science_entity( $source['science_entities'][ $target['entity_id'] ] ) ) {
					continue;
				}
				$subject = array(
					'kind'            => $record['kind'],
					'registry'        => $record['subject']['registry'],
					'entity_type'     => $record['subject']['entity_type'],
					'entity_id'       => $record['subject']['entity_id'],
					'scope_entity_id' => $record['subject']['scope_entity_id'],
					'relation'        => $target['relation'],
				);
				if ( ! isset( $indexes[ $scope ][ $target['entity_id'] ] ) ) {
					$indexes[ $scope ][ $target['entity_id'] ] = array();
				}
				$indexes[ $scope ][ $target['entity_id'] ][] = $subject;
			}
		}
		foreach ( self::expected_vocabulary()['binding_kinds'] as $kind ) {
			ksort( $indexes[ $kind ], SORT_STRING );
		}
		foreach ( array( 'public_navigation', 'public_product_navigation' ) as $scope ) {
			ksort( $indexes[ $scope ], SORT_STRING );
			foreach ( $indexes[ $scope ] as &$subjects ) {
				usort( $subjects, array( __CLASS__, 'compare_subject_descriptors' ) );
			}
			unset( $subjects );
		}
		return $indexes;
	}

	private static function is_public_science_entity( $entity ) {
		if ( ! is_array( $entity ) || ! isset( $entity['type'], $entity['surface_class'], $entity['publication'], $entity['seo'], $entity['review'], $entity['visual'], $entity['trust'], $entity['facts'], $entity['index_policy'] ) ) {
			return false;
		}
		if ( in_array( $entity['type'], array( 'supplier', 'retail_listing', 'market_observation', 'guide_edition', 'visual_asset' ), true )
			|| 'public_discovery' !== $entity['surface_class']
			|| 'approved_public' !== ( $entity['publication']['state'] ?? '' )
			|| true !== ( $entity['publication']['public_api'] ?? false )
			|| true !== ( $entity['publication']['public_page'] ?? false )
			|| 'private' === ( $entity['seo']['route_mode'] ?? '' )
			|| 'reviewed_bilingual' !== ( $entity['review']['language_status'] ?? '' )
			|| ! in_array( $entity['review']['status'] ?? '', array( 'source_reviewed', 'verified' ), true )
			|| 'approved' !== ( $entity['visual']['asset_state'] ?? '' )
			|| ! in_array( $entity['visual']['rights_state'] ?? '', array( 'cleared_owned', 'cleared_generated', 'cleared_licensed' ), true )
			|| ! is_string( $entity['visual']['rights_receipt_digest'] ?? null )
			|| '' === $entity['visual']['rights_receipt_digest']
			|| 'pending_named_review' === ( $entity['trust']['attribution_state'] ?? '' ) ) {
			return false;
		}
		if ( 'dish' === $entity['type'] && 'tested' !== ( $entity['review']['culinary_test_status'] ?? '' ) ) {
			return false;
		}
		if ( 'preparation' === $entity['type'] && 'Recipe' === ( $entity['seo']['schema_type'] ?? '' ) && 'tested' !== ( $entity['review']['culinary_test_status'] ?? '' ) ) {
			return false;
		}
		if ( 'preparation' === $entity['type'] && 'not_applicable' === ( $entity['review']['culinary_test_status'] ?? '' ) && ( 'Recipe' === ( $entity['seo']['schema_type'] ?? '' ) || true === ( $entity['publication']['search_index'] ?? false ) ) ) {
			return false;
		}
		if ( true === ( $entity['publication']['search_index'] ?? false ) && ( 'index' !== $entity['index_policy'] || 'standalone' !== ( $entity['seo']['route_mode'] ?? '' ) ) ) {
			return false;
		}
		$has_public_fact = false;
		foreach ( $entity['facts'] as $fact ) {
			if ( true !== ( $fact['public_safe'] ?? false ) ) {
				continue;
			}
			$has_public_fact = true;
			if ( 'editorial_inference' === ( $fact['evidence_class'] ?? '' ) ) {
				return false;
			}
		}
		return $has_public_fact;
	}

	private static function load_inputs() {
		if ( is_array( self::$inputs_cache ) ) {
			return self::$inputs_cache;
		}
		if ( ! defined( 'COMPLETE99_PLATFORM_DIR' ) ) {
			throw new RuntimeException( 'source.platform_dir' );
		}
		$inputs = array();
		foreach ( self::input_contract_definitions() as $key => $definition ) {
			if ( 'culinary_science' === $key && class_exists( 'Complete99_Culinary_Science' ) && method_exists( 'Complete99_Culinary_Science', 'registry' ) ) {
				$science = Complete99_Culinary_Science::registry();
				if ( self::is_error( $science ) || ! is_array( $science ) ) {
					throw new RuntimeException( 'source.culinary_science.unavailable' );
				}
				$inputs[ $key ] = $science;
				continue;
			}
			$path = COMPLETE99_PLATFORM_DIR . $definition['source_path'];
			if ( ! is_readable( $path ) ) {
				throw new RuntimeException( 'source.' . $key . '.missing' );
			}
			$inputs[ $key ] = require $path;
			if ( ! is_array( $inputs[ $key ] ) ) {
				throw new RuntimeException( 'source.' . $key . '.payload' );
			}
		}
		self::$inputs_cache = $inputs;
		return self::$inputs_cache;
	}

	private static function catalog_seed_evidence( $registry ) {
		if ( ! is_array( $registry ) || 'complete99-catalog-product-seeds/v1' !== ( $registry['schema'] ?? '' ) || '2026-08-06' !== ( $registry['registry_reviewed_at'] ?? '' ) || ! isset( $registry['products'] ) ) {
			throw new RuntimeException( 'source.catalog_product_seeds.contract' );
		}
		self::assert_list( $registry['products'], 'source.catalog_product_seeds.products', false );
		$index = array();
		foreach ( $registry['products'] as $offset => $product ) {
			if ( ! is_array( $product ) || ! isset( $product['product_code'] ) ) {
				throw new RuntimeException( 'source.catalog_product_seeds.products.' . $offset );
			}
			self::assert_identifier( $product['product_code'], 'source.catalog_product_seeds.products.' . $offset . '.product_code', 140 );
			if ( isset( $index[ $product['product_code'] ] ) ) {
				throw new RuntimeException( 'source.catalog_product_seeds.products.' . $offset . '.duplicate' );
			}
			$index[ $product['product_code'] ] = true;
		}
		return $index;
	}

	private static function input_contract_definitions() {
		return array(
			'consumer_menu' => array(
				'source_path'    => 'data/consumer-menu.php',
				'source_schema'  => 'complete99-consumer-menu-array/v1',
				'source_version' => 'unversioned',
			),
			'dish_entity_trees' => array(
				'source_path'    => 'data/dish-entity-trees.php',
				'source_schema'  => 'complete99-dish-entity-tree-registry/v1',
				'source_version' => 'registry-reviewed-2026-07-31',
			),
			'catalog_product_seeds' => array(
				'source_path'    => 'data/catalog-product-seeds.php',
				'source_schema'  => 'complete99-catalog-product-seeds/v1',
				'source_version' => 'reviewed-2026-08-06',
			),
			'culinary_science' => array(
				'source_path'    => 'data/culinary-science-pilot.php',
				'source_schema'  => 'complete99-culinary-science-registry/v6',
				'source_version' => 'culinary-science-2026.08.08.v20',
				'payload_sha256' => self::CULINARY_SCIENCE_PAYLOAD_SHA256,
			),
			'live_catalog_products' => array(
				'source_path'    => 'data/live-catalog-products.php',
				'source_schema'  => 'complete99-live-catalog-products/v1',
				'source_version' => 'reviewed-2026-08-06',
			),
			'live_catalog_relations' => array(
				'source_path'    => 'data/live-catalog-relations.php',
				'source_schema'  => 'complete99-live-catalog-relations/v1',
				'source_version' => 'reviewed-2026-08-06',
			),
		);
	}

	private static function expected_vocabulary() {
		return array(
			'binding_kinds' => array( 'menu_dish_science_dish', 'menu_component_science_entity', 'woo_product_science_entity' ),
			'resolution_states' => array( 'linked', 'no_match', 'unresolved' ),
			'registries' => array( 'consumer_menu', 'dish_entity_trees', 'culinary_science', 'woocommerce' ),
			'entity_types' => array( 'dish', 'component', 'ingredient', 'preparation', 'equipment', 'product' ),
			'relations' => array( 'same_dish_identity', 'house_expression_of', 'reference_only', 'same_ingredient_identity', 'same_preparation_identity', 'retail_instance_of' ),
			'projection_scopes' => array( 'private_only', 'public_navigation', 'public_product_navigation' ),
			'review_states' => array( 'unreviewed', 'source_reviewed', 'verified' ),
			'candidate_states' => array( 'pending_review', 'rejected' ),
			'candidate_reason_codes' => array( 'legacy_explicit_relation_requires_review', 'insufficient_evidence', 'scope_mismatch', 'different_variant', 'component_is_composite', 'product_identity_unverified', 'target_type_mismatch', 'duplicate_conflict' ),
			'evidence_registries' => array( 'dish_source_registry', 'culinary_science_sources', 'culinary_science_registry', 'catalog_product_seeds', 'live_catalog_products', 'live_catalog_relations' ),
		);
	}

	private static function canonical_payload_digest( $payload ) {
		$canonical = self::canonicalize( $payload, 'payload' );
		$flags     = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		$json      = function_exists( 'wp_json_encode' ) ? wp_json_encode( $canonical, $flags ) : json_encode( $canonical, $flags );
		if ( ! is_string( $json ) ) {
			throw new RuntimeException( 'payload.json' );
		}
		return hash( 'sha256', $json );
	}

	private static function canonicalize( $value, $path ) {
		if ( is_array( $value ) ) {
			if ( self::is_list( $value ) ) {
				$result = array();
				foreach ( $value as $offset => $item ) {
					$result[] = self::canonicalize( $item, $path . '.' . $offset );
				}
				return $result;
			}
			$keys = array_keys( $value );
			foreach ( $keys as $key ) {
				if ( ! is_string( $key ) ) {
					throw new RuntimeException( $path . '.key' );
				}
			}
			ksort( $value, SORT_STRING );
			$result = array();
			foreach ( $value as $key => $item ) {
				$result[ $key ] = self::canonicalize( $item, $path . '.' . $key );
			}
			return $result;
		}
		if ( is_float( $value ) && ( is_nan( $value ) || is_infinite( $value ) ) ) {
			throw new RuntimeException( $path . '.number' );
		}
		if ( ! is_null( $value ) && ! is_bool( $value ) && ! is_int( $value ) && ! is_float( $value ) && ! is_string( $value ) ) {
			throw new RuntimeException( $path . '.type' );
		}
		return $value;
	}

	private static function expected_record_id( $kind, $subject ) {
		if ( 'menu_dish_science_dish' === $kind && 'consumer_menu' === $subject['registry'] && 'dish' === $subject['entity_type'] && '' === $subject['scope_entity_id'] ) {
			return 'menu-dish--' . $subject['entity_id'];
		}
		if ( 'menu_component_science_entity' === $kind && 'dish_entity_trees' === $subject['registry'] && in_array( $subject['entity_type'], array( 'component', 'ingredient' ), true ) && '' !== $subject['scope_entity_id'] ) {
			return 'menu-component--' . $subject['scope_entity_id'] . '--' . $subject['entity_id'];
		}
		if ( 'woo_product_science_entity' === $kind && 'woocommerce' === $subject['registry'] && 'product' === $subject['entity_type'] && '' === $subject['scope_entity_id'] ) {
			return 'woo-product--' . $subject['entity_id'];
		}
		return '';
	}

	private static function subject_for_lookup( $kind, $entity_id, $scope_entity_id ) {
		if ( ! is_string( $entity_id ) || ! is_string( $scope_entity_id ) ) {
			return array();
		}
		if ( 'menu_dish_science_dish' === $kind && '' === $scope_entity_id ) {
			return array( 'registry' => 'consumer_menu', 'entity_type' => 'dish', 'entity_id' => $entity_id, 'scope_entity_id' => '' );
		}
		if ( 'menu_component_science_entity' === $kind && '' !== $scope_entity_id ) {
			$type = 0 === strpos( $entity_id, 'ingredient-' ) ? 'ingredient' : 'component';
			return array( 'registry' => 'dish_entity_trees', 'entity_type' => $type, 'entity_id' => $entity_id, 'scope_entity_id' => $scope_entity_id );
		}
		if ( 'woo_product_science_entity' === $kind && '' === $scope_entity_id ) {
			return array( 'registry' => 'woocommerce', 'entity_type' => 'product', 'entity_id' => $entity_id, 'scope_entity_id' => '' );
		}
		return array();
	}

	private static function subject_key( $subject ) {
		$parts = array( $subject['registry'], $subject['entity_type'], $subject['scope_entity_id'], $subject['entity_id'] );
		$tokens = array();
		foreach ( $parts as $part ) {
			$tokens[] = strlen( $part ) . ':' . $part;
		}
		return implode( '|', $tokens );
	}

	private static function compare_target_descriptors( $left, $right ) {
		return strcmp( $left['entity_id'] . "\0" . $left['entity_type'] . "\0" . $left['relation'] . "\0" . $left['projection_scope'], $right['entity_id'] . "\0" . $right['entity_type'] . "\0" . $right['relation'] . "\0" . $right['projection_scope'] );
	}

	private static function compare_subject_descriptors( $left, $right ) {
		return strcmp( $left['kind'] . "\0" . self::subject_key( $left ) . "\0" . $left['relation'], $right['kind'] . "\0" . self::subject_key( $right ) . "\0" . $right['relation'] );
	}

	private static function reverse_index_subject_count( $index ) {
		$count = 0;
		foreach ( $index as $subjects ) {
			$count += count( $subjects );
		}
		return $count;
	}

	private static function key_set( $value, $path ) {
		self::assert_associative_array( $value, $path, false );
		$set = array();
		foreach ( array_keys( $value ) as $key ) {
			self::assert_identifier( $key, $path . '.key', 160 );
			$set[ $key ] = true;
		}
		return $set;
	}

	private static function empty_indexes() {
		return array(
			'menu_dish_science_dish'        => array(),
			'menu_component_science_entity' => array(),
			'woo_product_science_entity'     => array(),
			'public_navigation'              => array(),
			'public_product_navigation'      => array(),
		);
	}

	private static function empty_status() {
		return array(
			'schema'                          => self::REGISTRY_SCHEMA,
			'version'                         => self::REGISTRY_VERSION,
			'registry_valid'                  => false,
			'record_count'                    => 0,
			'dish_subject_count'              => 0,
			'component_subject_count'         => 0,
			'product_subject_count'           => 0,
			'linked_count'                    => 0,
			'no_match_count'                  => 0,
			'unresolved_count'                => 0,
			'decision_count'                  => 0,
			'recognized_reviewer_authority_count' => 0,
			'public_navigation_count'         => 0,
			'public_product_navigation_count' => 0,
		);
	}

	private static function validate_translation( $value, $path, $allow_empty ) {
		self::assert_exact_keys( $value, array( 'he', 'en' ), $path );
		self::assert_text( $value['he'], $path . '.he', 1200, $allow_empty );
		self::assert_text( $value['en'], $path . '.en', 1200, $allow_empty );
		if ( ( '' === trim( $value['he'] ) ) !== ( '' === trim( $value['en'] ) ) ) {
			throw new RuntimeException( $path . '.locale_parity' );
		}
	}

	private static function assert_exact_keys( $value, $expected, $path ) {
		self::assert_associative_array( $value, $path, false );
		$actual = array_keys( $value );
		sort( $actual, SORT_STRING );
		$expected = array_values( $expected );
		sort( $expected, SORT_STRING );
		if ( $actual !== $expected ) {
			throw new RuntimeException( $path . '.keys' );
		}
	}

	private static function assert_exact_list( $value, $expected, $path ) {
		self::assert_list( $value, $path, false );
		if ( array_values( $value ) !== array_values( $expected ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_associative_array( $value, $path, $allow_empty ) {
		if ( ! is_array( $value ) || ( ! $allow_empty && empty( $value ) ) || ( ! empty( $value ) && self::is_list( $value ) ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_list( $value, $path, $allow_empty ) {
		if ( ! is_array( $value ) || ( ! $allow_empty && empty( $value ) ) || ! self::is_list( $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_enum( $value, $allowed, $path ) {
		if ( ! is_string( $value ) || ! in_array( $value, $allowed, true ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_identifier( $value, $path, $maximum, $allow_empty = false ) {
		if ( ! is_string( $value ) || strlen( $value ) > $maximum || ( '' === $value && ! $allow_empty ) || ( '' !== $value && 1 !== preg_match( '/\A[a-z0-9][a-z0-9._:-]*\z/', $value ) ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_sha256( $value, $path ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $value ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_text( $value, $path, $maximum, $allow_empty = false ) {
		if ( ! is_string( $value ) || strlen( $value ) > $maximum || false !== strpos( $value, "\0" ) || ( '' === trim( $value ) && ! $allow_empty ) ) {
			throw new RuntimeException( $path );
		}
	}

	private static function assert_date( $value, $path, $allow_empty = false ) {
		if ( $allow_empty && '' === $value ) {
			return;
		}
		$date   = is_string( $value ) ? DateTimeImmutable::createFromFormat( '!Y-m-d', $value, new DateTimeZone( 'UTC' ) ) : false;
		$errors = DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $date->format( 'Y-m-d' ) !== $value ) {
			throw new RuntimeException( $path );
		}
	}

	private static function is_list( $value ) {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $value );
		}
		return is_array( $value ) && ( empty( $value ) || array_keys( $value ) === range( 0, count( $value ) - 1 ) );
	}

	private static function is_error( $value ) {
		return function_exists( 'is_wp_error' ) ? is_wp_error( $value ) : ( class_exists( 'WP_Error' ) && $value instanceof WP_Error );
	}

	private static function error( $code, $message, $data = array() ) {
		if ( ! class_exists( 'WP_Error' ) ) {
			throw new RuntimeException( $code );
		}
		$data = array_merge( array( 'status' => 500 ), $data );
		return new WP_Error( $code, $message, $data );
	}
}
