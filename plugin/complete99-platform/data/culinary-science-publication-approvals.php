<?php
/**
 * Fail-closed owner-publication candidates and receipts for held science work.
 *
 * A candidate records the exact asset, bilingual copy and registry source that
 * an accountable human owner may review. A candidate is not an approval. Only
 * a complete, exact and self-digested owner receipt can promote its entity.
 *
 * @package Complete99_Platform
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'complete99_owner_publication_exact_keys' ) ) {
	/**
	 * Determine whether an associative array has exactly the allowed keys.
	 *
	 * @param mixed $value Value to inspect.
	 * @param array $expected_keys Expected keys.
	 * @return bool
	 */
	function complete99_owner_publication_exact_keys( $value, $expected_keys ) {
		if ( ! is_array( $value ) ) {
			return false;
		}
		$actual_keys = array_keys( $value );
		$expected    = array_values( $expected_keys );
		sort( $actual_keys, SORT_STRING );
		sort( $expected, SORT_STRING );
		return $actual_keys === $expected;
	}
}

if ( ! function_exists( 'complete99_owner_publication_is_list' ) ) {
	/**
	 * PHP 7-compatible list check.
	 *
	 * @param mixed $value Value to inspect.
	 * @return bool
	 */
	function complete99_owner_publication_is_list( $value ) {
		if ( ! is_array( $value ) ) {
			return false;
		}
		if ( array() === $value ) {
			return true;
		}
		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}
}

if ( ! function_exists( 'complete99_owner_publication_canonicalize' ) ) {
	/**
	 * Recursively sort object-like arrays while preserving list order.
	 *
	 * @param mixed $value Value to canonicalize.
	 * @return mixed
	 */
	function complete99_owner_publication_canonicalize( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$is_list = complete99_owner_publication_is_list( $value );
		if ( ! $is_list ) {
			ksort( $value, SORT_STRING );
		}
		$canonical = array();
		foreach ( $value as $key => $item ) {
			$canonical[ $key ] = complete99_owner_publication_canonicalize( $item );
		}
		return $canonical;
	}
}

if ( ! function_exists( 'complete99_owner_publication_canonical_json' ) ) {
	/**
	 * Encode canonical JSON without escaping Unicode or slashes.
	 *
	 * @param mixed $value Value to encode.
	 * @return string
	 */
	function complete99_owner_publication_canonical_json( $value ) {
		$json = json_encode(
			complete99_owner_publication_canonicalize( $value ),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
		);
		return false === $json ? '' : $json;
	}
}

if ( ! function_exists( 'complete99_owner_publication_content_digest' ) ) {
	/**
	 * Digest the complete, bounded pre-gate entity content payload.
	 *
	 * The entity is supplied before any publication-state mutation. Hashing the
	 * complete entity binds localized scalar and list copy, measurements,
	 * sources, safety notes, SEO, links, taxonomy, visual specifications and
	 * commerce references rather than relying on a hand-maintained field list.
	 *
	 * @param array $entity Culinary-science entity.
	 * @return string
	 */
	function complete99_owner_publication_content_digest( $entity ) {
		return 'sha256:' . hash(
			'sha256',
			'complete99-owner-publication-pre-gate-content/v1|' . complete99_owner_publication_canonical_json( $entity )
		);
	}
}

if ( ! function_exists( 'complete99_owner_publication_candidate_digest' ) ) {
	/**
	 * Digest a candidate without its self-digest field.
	 *
	 * @param array $candidate Candidate record.
	 * @return string
	 */
	function complete99_owner_publication_candidate_digest( $candidate ) {
		unset( $candidate['candidate_sha256'] );
		return 'sha256:' . hash(
			'sha256',
			'complete99-owner-publication-candidate/v2|' . complete99_owner_publication_canonical_json( $candidate )
		);
	}
}

if ( ! function_exists( 'complete99_owner_publication_receipt_digest' ) ) {
	/**
	 * Digest a receipt without its self-digest field.
	 *
	 * @param array $receipt Receipt record.
	 * @return string
	 */
	function complete99_owner_publication_receipt_digest( $receipt ) {
		unset( $receipt['receipt_sha256'] );
		if ( isset( $receipt['owner'] ) && is_array( $receipt['owner'] ) ) {
			unset( $receipt['owner']['signature_base64'] );
		}
		return 'sha256:' . hash(
			'sha256',
			'complete99-owner-publication-approval-receipt/v2|' . complete99_owner_publication_canonical_json( $receipt )
		);
	}
}

if ( ! function_exists( 'complete99_owner_publication_status_digest' ) ) {
	/**
	 * Digest an approval status without its self-digest field.
	 *
	 * @param array $status Approval status.
	 * @return string
	 */
	function complete99_owner_publication_status_digest( $status ) {
		unset( $status['status_sha256'] );
		return 'sha256:' . hash(
			'sha256',
			'complete99-owner-publication-approval-status/v2|' . complete99_owner_publication_canonical_json( $status )
		);
	}
}

if ( ! function_exists( 'complete99_owner_publication_file_receipt_shape_is_valid' ) ) {
	/**
	 * Validate receipt metadata without touching the filesystem.
	 *
	 * @param mixed  $file_receipt File receipt.
	 * @param string $kind Expected receipt kind.
	 * @return bool
	 */
	function complete99_owner_publication_file_receipt_shape_is_valid( $file_receipt, $kind ) {
		if (
			! complete99_owner_publication_exact_keys( $file_receipt, array( 'relative_path', 'bytes', 'sha256' ) )
			|| ! is_string( $file_receipt['relative_path'] )
			|| false !== strpos( $file_receipt['relative_path'], '..' )
			|| ! is_int( $file_receipt['bytes'] )
			|| $file_receipt['bytes'] < 1
			|| ! is_string( $file_receipt['sha256'] )
			|| 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $file_receipt['sha256'] )
		) {
			return false;
		}

		$patterns = array(
			'source_asset'    => '#^assets/images/science/c99-science-[a-z0-9]+(?:-[a-z0-9]+)*-v[0-9]{2}\.png$#',
			'delivery_asset'  => '#^assets/images/science/c99-science-[a-z0-9]+(?:-[a-z0-9]+)*-v[0-9]{2}(?:-768)?\.(?:webp|avif)$#',
			'registry_source' => '#^data/culinary-science-pilot\.php$#',
			'installed_file'  => '#^(?:assets/images/science/c99-science-[a-z0-9]+(?:-[a-z0-9]+)*-v[0-9]{2}(?:-768)?\.(?:png|webp|avif)|data/culinary-science-pilot\.php)$#',
		);
		return isset( $patterns[ $kind ] )
			&& 1 === preg_match( $patterns[ $kind ], $file_receipt['relative_path'] );
	}
}

if ( ! function_exists( 'complete99_owner_publication_asset_metadata_is_valid' ) ) {
	/**
	 * Validate one source-evidence receipt and its four deployable variants.
	 *
	 * @param mixed $source_asset Source PNG receipt.
	 * @param mixed $delivery_files Deployable web receipts.
	 * @return bool
	 */
	function complete99_owner_publication_asset_metadata_is_valid( $source_asset, $delivery_files ) {
		if (
			! complete99_owner_publication_file_receipt_shape_is_valid( $source_asset, 'source_asset' )
			|| ! complete99_owner_publication_exact_keys( $delivery_files, array( 'webp', 'avif', 'webp_768', 'avif_768' ) )
		) {
			return false;
		}

		if ( 1 !== preg_match( '#^assets/images/science/(c99-science-[a-z0-9]+(?:-[a-z0-9]+)*-v[0-9]{2})\.png$#', $source_asset['relative_path'], $matches ) ) {
			return false;
		}
		$stem = $matches[1];
		$expected_paths = array(
			'webp'     => 'assets/images/science/' . $stem . '.webp',
			'avif'     => 'assets/images/science/' . $stem . '.avif',
			'webp_768' => 'assets/images/science/' . $stem . '-768.webp',
			'avif_768' => 'assets/images/science/' . $stem . '-768.avif',
		);
		$paths = array( $source_asset['relative_path'] );
		foreach ( $expected_paths as $key => $expected_path ) {
			if (
				! complete99_owner_publication_file_receipt_shape_is_valid( $delivery_files[ $key ], 'delivery_asset' )
				|| $expected_path !== $delivery_files[ $key ]['relative_path']
			) {
				return false;
			}
			$paths[] = $delivery_files[ $key ]['relative_path'];
		}
		return count( $paths ) === count( array_unique( $paths ) );
	}
}

if ( ! function_exists( 'complete99_owner_publication_deployment_policy' ) ) {
	/**
	 * Return the immutable quarantine/delivery policy bound by each candidate.
	 *
	 * @return array
	 */
	function complete99_owner_publication_deployment_policy() {
		return array(
			'source_asset'            => 'source_tree_only',
			'held_delivery_files'     => 'must_be_absent',
			'approved_delivery_files' => 'must_match_exactly',
		);
	}
}

if ( ! function_exists( 'complete99_owner_publication_safe_file_validation' ) ) {
	/**
	 * Verify one installed file without collapsing absence into digest mismatch.
	 *
	 * @param string $plugin_root Plugin directory.
	 * @param array  $file_receipt Relative path, bytes and SHA-256.
	 * @return string missing, mismatch or exact.
	 */
	function complete99_owner_publication_safe_file_validation( $plugin_root, $file_receipt ) {
		if ( ! complete99_owner_publication_file_receipt_shape_is_valid( $file_receipt, 'installed_file' ) ) {
			return 'mismatch';
		}

		$root = realpath( $plugin_root );
		if ( false === $root ) {
			return 'missing';
		}
		$path = realpath( $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $file_receipt['relative_path'] ) );
		if (
			false === $path
			|| 0 !== strpos( $path, $root . DIRECTORY_SEPARATOR )
			|| ! is_file( $path )
			|| ! is_readable( $path )
		) {
			return 'missing';
		}
		$bytes = filesize( $path );
		if ( false === $bytes ) {
			return 'missing';
		}
		if ( $bytes !== $file_receipt['bytes'] ) {
			return 'mismatch';
		}
		$digest = hash_file( 'sha256', $path );
		if ( ! is_string( $digest ) ) {
			return 'missing';
		}
		return hash_equals( substr( $file_receipt['sha256'], 7 ), $digest ) ? 'exact' : 'mismatch';
	}
}

if ( ! function_exists( 'complete99_owner_publication_safe_file_matches' ) ) {
	/**
	 * Backward-compatible exact-match predicate.
	 *
	 * @param string $plugin_root Plugin directory.
	 * @param array  $file_receipt Relative path, bytes and SHA-256.
	 * @return bool
	 */
	function complete99_owner_publication_safe_file_matches( $plugin_root, $file_receipt ) {
		return 'exact' === complete99_owner_publication_safe_file_validation( $plugin_root, $file_receipt );
	}
}

if ( ! function_exists( 'complete99_owner_publication_delivery_validation' ) ) {
	/**
	 * Validate all four web variants with deterministic missing precedence.
	 *
	 * @param string $plugin_root Plugin directory.
	 * @param array  $delivery_files Delivery receipts.
	 * @return string missing, mismatch or exact.
	 */
	function complete99_owner_publication_delivery_validation( $plugin_root, $delivery_files ) {
		$has_mismatch = false;
		foreach ( $delivery_files as $file_receipt ) {
			$validation = complete99_owner_publication_safe_file_validation( $plugin_root, $file_receipt );
			if ( 'missing' === $validation ) {
				return 'missing';
			}
			if ( 'mismatch' === $validation ) {
				$has_mismatch = true;
			}
		}
		return $has_mismatch ? 'mismatch' : 'exact';
	}
}

if ( ! function_exists( 'complete99_owner_publication_registry_shape_is_valid' ) ) {
	/**
	 * Validate the approval registry and all candidate shapes.
	 *
	 * @param array $registry Registry data.
	 * @param array $expected_entity_ids Fail-closed entity set from construction.
	 * @return bool
	 */
	function complete99_owner_publication_registry_shape_is_valid( $registry, $expected_entity_ids ) {
		if (
			! complete99_owner_publication_exact_keys(
				$registry,
				array( 'schema', 'generated_at', 'required_locales', 'required_entity_ids', 'trusted_owner_keys', 'candidates', 'receipts' )
			)
			|| 'complete99-owner-publication-approval-registry/v2' !== $registry['schema']
			|| ! is_string( $registry['generated_at'] )
			|| 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $registry['generated_at'] )
			|| array( 'he', 'en' ) !== $registry['required_locales']
			|| array_values( $expected_entity_ids ) !== $registry['required_entity_ids']
			|| ! is_array( $registry['trusted_owner_keys'] )
			|| ! is_array( $registry['candidates'] )
			|| ! is_array( $registry['receipts'] )
			|| array_keys( $registry['candidates'] ) !== array_values( $expected_entity_ids )
		) {
			return false;
		}

		$placeholder_values = array( 'anonymous', 'complete99', 'owner', 'pending', 'system', 'tbd', 'unknown' );
		foreach ( $registry['trusted_owner_keys'] as $key_id => $owner_key ) {
			if (
				! complete99_owner_publication_exact_keys(
					$owner_key,
					array( 'schema', 'key_id', 'owner_account_id', 'owner_display_name', 'owner_role', 'algorithm', 'public_key_base64', 'public_key_sha256', 'status', 'enrolled_at' )
				)
				|| ! is_string( $key_id )
				|| 1 !== preg_match( '/^owner-key-[a-z0-9]+(?:-[a-z0-9]+)*$/', $key_id )
				|| 'complete99-owner-signing-key/v1' !== $owner_key['schema']
				|| $key_id !== $owner_key['key_id']
				|| ! is_string( $owner_key['owner_account_id'] )
				|| 1 !== preg_match( '/^[a-z0-9][a-z0-9._-]{2,63}$/', $owner_key['owner_account_id'] )
				|| in_array( strtolower( trim( $owner_key['owner_account_id'] ) ), $placeholder_values, true )
				|| ! is_string( $owner_key['owner_display_name'] )
				|| strlen( trim( $owner_key['owner_display_name'] ) ) < 3
				|| 1 !== preg_match( '/\s/u', trim( $owner_key['owner_display_name'] ) )
				|| in_array( strtolower( trim( $owner_key['owner_display_name'] ) ), $placeholder_values, true )
				|| 'complete99_owner' !== $owner_key['owner_role']
				|| 'ed25519' !== $owner_key['algorithm']
				|| ! is_string( $owner_key['public_key_base64'] )
				|| ! is_string( $owner_key['public_key_sha256'] )
				|| 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $owner_key['public_key_sha256'] )
				|| 'active' !== $owner_key['status']
				|| ! is_string( $owner_key['enrolled_at'] )
				|| 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/', $owner_key['enrolled_at'] )
				|| false === strtotime( $owner_key['enrolled_at'] )
			) {
				return false;
			}
			$public_key = base64_decode( $owner_key['public_key_base64'], true );
			if (
				false === $public_key
				|| 32 !== strlen( $public_key )
				|| ! hash_equals( $owner_key['public_key_sha256'], 'sha256:' . hash( 'sha256', $public_key ) )
			) {
				return false;
			}
		}

		$promotion_scope = array(
			'surface_class'    => 'public_discovery',
			'index_policy'     => 'noindex_until_longform_review',
			'publication_state'=> 'approved_public',
			'public_api'       => true,
			'public_page'      => true,
			'search_index'     => false,
		);
		$deployment_policy = complete99_owner_publication_deployment_policy();
		$candidate_ids = array();
		foreach ( $registry['candidates'] as $entity_id => $candidate ) {
			if (
				! complete99_owner_publication_exact_keys(
					$candidate,
					array( 'schema', 'candidate_id', 'entity_id', 'state', 'source_asset', 'delivery_files', 'deployment_policy', 'bilingual_content', 'registry_source', 'promotion_scope', 'candidate_sha256' )
				)
				|| 'complete99-owner-publication-candidate/v2' !== $candidate['schema']
				|| 'publication-candidate-' . $entity_id !== $candidate['candidate_id']
				|| $entity_id !== $candidate['entity_id']
				|| 'held_pending_owner_approval' !== $candidate['state']
				|| isset( $candidate_ids[ $candidate['candidate_id'] ] )
				|| ! complete99_owner_publication_asset_metadata_is_valid( $candidate['source_asset'], $candidate['delivery_files'] )
				|| $deployment_policy !== $candidate['deployment_policy']
				|| ! complete99_owner_publication_exact_keys( $candidate['registry_source'], array( 'relative_path', 'bytes', 'sha256' ) )
				|| ! complete99_owner_publication_file_receipt_shape_is_valid( $candidate['registry_source'], 'registry_source' )
				|| ! complete99_owner_publication_exact_keys( $candidate['bilingual_content'], array( 'canonicalization', 'locales', 'sha256' ) )
				|| 'complete99-owner-publication-pre-gate-content/v1' !== $candidate['bilingual_content']['canonicalization']
				|| array( 'he', 'en' ) !== $candidate['bilingual_content']['locales']
				|| ! is_string( $candidate['bilingual_content']['sha256'] )
				|| 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $candidate['bilingual_content']['sha256'] )
				|| $promotion_scope !== $candidate['promotion_scope']
				|| ! is_string( $candidate['candidate_sha256'] )
				|| ! hash_equals( complete99_owner_publication_candidate_digest( $candidate ), $candidate['candidate_sha256'] )
			) {
				return false;
			}
			$candidate_ids[ $candidate['candidate_id'] ] = true;
		}

		$receipt_ids = array();
		foreach ( $registry['receipts'] as $entity_id => $receipt ) {
			if (
				! in_array( $entity_id, $expected_entity_ids, true )
				|| ! is_array( $receipt )
				|| ! isset( $receipt['receipt_id'] )
				|| ! is_string( $receipt['receipt_id'] )
				|| isset( $receipt_ids[ $receipt['receipt_id'] ] )
			) {
				return false;
			}
			$receipt_ids[ $receipt['receipt_id'] ] = true;
		}
		return true;
	}
}

if ( ! function_exists( 'complete99_owner_publication_receipt_is_valid' ) ) {
	/**
	 * Validate an explicit named-human owner receipt against one candidate.
	 *
	 * @param array $receipt Receipt record.
	 * @param array $candidate Exact candidate record.
	 * @param array $trusted_owner_keys Explicit recognized-owner key allowlist.
	 * @return bool
	 */
	function complete99_owner_publication_receipt_is_valid( $receipt, $candidate, $trusted_owner_keys ) {
		if (
			! complete99_owner_publication_exact_keys(
				$receipt,
				array( 'schema', 'receipt_id', 'candidate_id', 'candidate_sha256', 'entity_id', 'decision', 'source_asset', 'delivery_files', 'deployment_policy', 'bilingual_content', 'registry_source', 'promotion_scope', 'owner', 'approval_statement', 'approved_at', 'receipt_sha256' )
			)
			|| 'complete99-owner-publication-approval-receipt/v2' !== $receipt['schema']
			|| ! is_string( $receipt['receipt_id'] )
			|| 1 !== preg_match( '/^owner-publication-receipt-[a-z0-9]+(?:-[a-z0-9]+)*$/', $receipt['receipt_id'] )
			|| $candidate['candidate_id'] !== $receipt['candidate_id']
			|| $candidate['candidate_sha256'] !== $receipt['candidate_sha256']
			|| $candidate['entity_id'] !== $receipt['entity_id']
			|| 'approve_publication' !== $receipt['decision']
			|| $candidate['source_asset'] !== $receipt['source_asset']
			|| $candidate['delivery_files'] !== $receipt['delivery_files']
			|| $candidate['deployment_policy'] !== $receipt['deployment_policy']
			|| $candidate['bilingual_content'] !== $receipt['bilingual_content']
			|| $candidate['registry_source'] !== $receipt['registry_source']
			|| $candidate['promotion_scope'] !== $receipt['promotion_scope']
			|| ! complete99_owner_publication_exact_keys( $receipt['owner'], array( 'account_id', 'display_name', 'role', 'human_confirmation', 'signing_key_id', 'signature_base64' ) )
			|| ! is_string( $receipt['owner']['account_id'] )
			|| 1 !== preg_match( '/^[a-z0-9][a-z0-9._-]{2,63}$/', $receipt['owner']['account_id'] )
			|| ! is_string( $receipt['owner']['display_name'] )
			|| strlen( trim( $receipt['owner']['display_name'] ) ) < 3
			|| 1 !== preg_match( '/\s/u', trim( $receipt['owner']['display_name'] ) )
			|| 'complete99_owner' !== $receipt['owner']['role']
			|| true !== $receipt['owner']['human_confirmation']
			|| ! is_string( $receipt['owner']['signing_key_id'] )
			|| ! isset( $trusted_owner_keys[ $receipt['owner']['signing_key_id'] ] )
			|| ! is_string( $receipt['owner']['signature_base64'] )
			|| 'I approve the exact bound source evidence, deployable asset variants and bilingual content for Complete99 public discovery.' !== $receipt['approval_statement']
			|| ! is_string( $receipt['approved_at'] )
			|| 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/', $receipt['approved_at'] )
			|| false === strtotime( $receipt['approved_at'] )
			|| ! is_string( $receipt['receipt_sha256'] )
			|| ! hash_equals( complete99_owner_publication_receipt_digest( $receipt ), $receipt['receipt_sha256'] )
		) {
			return false;
		}

		$placeholder_values = array( 'anonymous', 'complete99', 'owner', 'pending', 'system', 'tbd', 'unknown' );
		if (
			in_array( strtolower( trim( $receipt['owner']['account_id'] ) ), $placeholder_values, true )
			|| in_array( strtolower( trim( $receipt['owner']['display_name'] ) ), $placeholder_values, true )
		) {
			return false;
		}

		$owner_key = $trusted_owner_keys[ $receipt['owner']['signing_key_id'] ];
		if (
			'active' !== $owner_key['status']
			|| $receipt['owner']['account_id'] !== $owner_key['owner_account_id']
			|| $receipt['owner']['display_name'] !== $owner_key['owner_display_name']
			|| $receipt['owner']['role'] !== $owner_key['owner_role']
			|| strtotime( $owner_key['enrolled_at'] ) > strtotime( $receipt['approved_at'] )
			|| ! function_exists( 'sodium_crypto_sign_verify_detached' )
		) {
			return false;
		}

		$signature = base64_decode( $receipt['owner']['signature_base64'], true );
		$public_key = base64_decode( $owner_key['public_key_base64'], true );
		return false !== $signature
			&& 64 === strlen( $signature )
			&& false !== $public_key
			&& 32 === strlen( $public_key )
			&& sodium_crypto_sign_verify_detached( $signature, $receipt['receipt_sha256'], $public_key );
	}
}

if ( ! function_exists( 'complete99_owner_publication_decision' ) ) {
	/**
	 * Return one fail-closed publication decision.
	 *
	 * @param array  $registry Approval registry.
	 * @param array  $expected_entity_ids Required held set.
	 * @param array  $entity Current science entity.
	 * @param string $plugin_root Plugin directory.
	 * @return array
	 */
	function complete99_owner_publication_decision( $registry, $expected_entity_ids, $entity, $plugin_root ) {
		$entity_id = isset( $entity['id'] ) && is_string( $entity['id'] ) ? $entity['id'] : '';
		$current_content_sha256 = complete99_owner_publication_content_digest( $entity );
		$held = array(
			'entity_id'                => $entity_id,
			'candidate_id'             => '',
			'candidate_sha256'         => '',
			'approved'                 => false,
			'state'                    => 'held_pending_owner_approval',
			'reason'                   => 'invalid_approval_registry',
			'current_content_sha256'   => $current_content_sha256,
			'candidate_content_sha256' => '',
			'receipt_id'               => '',
			'receipt_sha256'           => '',
			'approved_at'              => '',
			'delivery_validation'      => 'not_evaluated',
		);
		if ( ! complete99_owner_publication_registry_shape_is_valid( $registry, $expected_entity_ids ) ) {
			return $held;
		}
		if ( ! isset( $registry['candidates'][ $entity_id ] ) ) {
			$held['reason'] = 'missing_candidate';
			return $held;
		}
		$candidate            = $registry['candidates'][ $entity_id ];
		$held['candidate_id'] = $candidate['candidate_id'];
		$held['candidate_sha256'] = $candidate['candidate_sha256'];
		$held['candidate_content_sha256'] = $candidate['bilingual_content']['sha256'];
		if ( ! complete99_owner_publication_safe_file_matches( $plugin_root, $candidate['registry_source'] ) ) {
			$held['reason'] = 'registry_source_mismatch';
			return $held;
		}
		if ( ! hash_equals( $candidate['bilingual_content']['sha256'], complete99_owner_publication_content_digest( $entity ) ) ) {
			$held['reason'] = 'bilingual_content_mismatch';
			return $held;
		}
		if ( ! isset( $registry['receipts'][ $entity_id ] ) ) {
			$held['reason'] = 'missing_owner_receipt';
			return $held;
		}
		$receipt = $registry['receipts'][ $entity_id ];
		if ( ! complete99_owner_publication_receipt_is_valid( $receipt, $candidate, $registry['trusted_owner_keys'] ) ) {
			$held['reason'] = 'invalid_owner_receipt';
			return $held;
		}
		$held['receipt_id']     = $receipt['receipt_id'];
		$held['receipt_sha256'] = $receipt['receipt_sha256'];
		$held['approved_at']    = $receipt['approved_at'];
		$delivery_validation = complete99_owner_publication_delivery_validation( $plugin_root, $candidate['delivery_files'] );
		$held['delivery_validation'] = $delivery_validation;
		if ( 'missing' === $delivery_validation ) {
			$held['state']  = 'held_pending_exact_asset_delivery';
			$held['reason'] = 'approved_delivery_bundle_missing';
			return $held;
		}
		if ( 'mismatch' === $delivery_validation ) {
			$held['state']  = 'held_pending_exact_asset_delivery';
			$held['reason'] = 'approved_delivery_bundle_mismatch';
			return $held;
		}
		return array(
			'entity_id'                => $entity_id,
			'candidate_id'             => $candidate['candidate_id'],
			'candidate_sha256'         => $candidate['candidate_sha256'],
			'approved'                 => true,
			'state'                    => 'owner_approved_publication',
			'reason'                   => 'exact_owner_receipt_and_delivery_verified',
			'current_content_sha256'   => $current_content_sha256,
			'candidate_content_sha256' => $candidate['bilingual_content']['sha256'],
			'receipt_id'               => $receipt['receipt_id'],
			'receipt_sha256'           => $receipt['receipt_sha256'],
			'approved_at'              => $receipt['approved_at'],
			'delivery_validation'      => 'exact',
		);
	}
}

if ( ! function_exists( 'complete99_owner_publication_registry_status' ) ) {
	/**
	 * Produce a private, owner-safe status summary without owner PII.
	 *
	 * @param array  $registry Approval registry.
	 * @param array  $expected_entity_ids Required held set.
	 * @param array  $entities Current science entities.
	 * @param string $plugin_root Plugin directory.
	 * @return array
	 */
	function complete99_owner_publication_registry_status( $registry, $expected_entity_ids, $entities, $plugin_root ) {
		$entities_by_id = array();
		foreach ( $entities as $entity ) {
			if ( isset( $entity['id'] ) && is_string( $entity['id'] ) ) {
				$entities_by_id[ $entity['id'] ] = $entity;
			}
		}
		$decisions = array();
		$approved  = array();
		foreach ( $expected_entity_ids as $entity_id ) {
			$entity = isset( $entities_by_id[ $entity_id ] ) ? $entities_by_id[ $entity_id ] : array( 'id' => $entity_id );
			$decision = complete99_owner_publication_decision( $registry, $expected_entity_ids, $entity, $plugin_root );
			$decisions[ $entity_id ] = $decision;
			if ( true === $decision['approved'] ) {
				$approved[] = $entity_id;
			}
		}
		$status = array(
			'schema'              => 'complete99-owner-publication-approval-status/v2',
			'candidate_count'     => count( $expected_entity_ids ),
			'approved_count'      => count( $approved ),
			'held_count'          => count( $expected_entity_ids ) - count( $approved ),
			'approved_entity_ids' => $approved,
			'decisions'           => $decisions,
			'status_sha256'       => '',
		);
		$status['status_sha256'] = complete99_owner_publication_status_digest( $status );
		return $status;
	}
}

if ( ! function_exists( 'complete99_owner_publication_status_is_valid' ) ) {
	/**
	 * Validate a complete status snapshot before another runtime may consume it.
	 *
	 * @param mixed $status Status snapshot.
	 * @param array $expected_entity_ids Exact candidate IDs.
	 * @return bool
	 */
	function complete99_owner_publication_status_is_valid( $status, $expected_entity_ids ) {
		if (
			! complete99_owner_publication_exact_keys(
				$status,
				array( 'schema', 'candidate_count', 'approved_count', 'held_count', 'approved_entity_ids', 'decisions', 'status_sha256' )
			)
			|| 'complete99-owner-publication-approval-status/v2' !== $status['schema']
			|| ! complete99_owner_publication_is_list( $expected_entity_ids )
			|| ! complete99_owner_publication_is_list( $status['approved_entity_ids'] )
			|| ! is_array( $status['decisions'] )
			|| array_keys( $status['decisions'] ) !== array_values( $expected_entity_ids )
			|| ! is_int( $status['candidate_count'] )
			|| ! is_int( $status['approved_count'] )
			|| ! is_int( $status['held_count'] )
			|| count( $expected_entity_ids ) !== $status['candidate_count']
			|| $status['candidate_count'] !== $status['approved_count'] + $status['held_count']
			|| ! is_string( $status['status_sha256'] )
			|| 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $status['status_sha256'] )
			|| ! hash_equals( complete99_owner_publication_status_digest( $status ), $status['status_sha256'] )
		) {
			return false;
		}

		$approved = array();
		$decision_keys = array(
			'entity_id',
			'candidate_id',
			'candidate_sha256',
			'approved',
			'state',
			'reason',
			'current_content_sha256',
			'candidate_content_sha256',
			'receipt_id',
			'receipt_sha256',
			'approved_at',
			'delivery_validation',
		);
		$owner_pending_reasons = array(
			'invalid_approval_registry',
			'missing_candidate',
			'registry_source_mismatch',
			'bilingual_content_mismatch',
			'missing_owner_receipt',
			'invalid_owner_receipt',
		);
		foreach ( $status['decisions'] as $entity_id => $decision ) {
			if (
				! complete99_owner_publication_exact_keys( $decision, $decision_keys )
				|| $entity_id !== $decision['entity_id']
				|| ! is_string( $decision['candidate_id'] )
				|| ! is_string( $decision['candidate_sha256'] )
				|| ( '' !== $decision['candidate_sha256'] && 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $decision['candidate_sha256'] ) )
				|| ! is_bool( $decision['approved'] )
				|| ! is_string( $decision['current_content_sha256'] )
				|| 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $decision['current_content_sha256'] )
				|| ! is_string( $decision['candidate_content_sha256'] )
				|| ( '' !== $decision['candidate_content_sha256'] && 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $decision['candidate_content_sha256'] ) )
			) {
				return false;
			}

			if ( 'held_pending_owner_approval' === $decision['state'] ) {
				if (
					true === $decision['approved']
					|| ! in_array( $decision['reason'], $owner_pending_reasons, true )
					|| 'not_evaluated' !== $decision['delivery_validation']
					|| '' !== $decision['receipt_id']
					|| '' !== $decision['receipt_sha256']
					|| '' !== $decision['approved_at']
				) {
					return false;
				}
				continue;
			}

			$receipt_metadata_valid = is_string( $decision['receipt_id'] )
				&& 1 === preg_match( '/^owner-publication-receipt-[a-z0-9]+(?:-[a-z0-9]+)*$/', $decision['receipt_id'] )
				&& is_string( $decision['receipt_sha256'] )
				&& 1 === preg_match( '/^sha256:[a-f0-9]{64}$/', $decision['receipt_sha256'] )
				&& is_string( $decision['approved_at'] )
				&& 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/', $decision['approved_at'] )
				&& false !== strtotime( $decision['approved_at'] );
			if ( ! $receipt_metadata_valid ) {
				return false;
			}
			if ( 'held_pending_exact_asset_delivery' === $decision['state'] ) {
				$reason_matches = ( 'missing' === $decision['delivery_validation'] && 'approved_delivery_bundle_missing' === $decision['reason'] )
					|| ( 'mismatch' === $decision['delivery_validation'] && 'approved_delivery_bundle_mismatch' === $decision['reason'] );
				if ( true === $decision['approved'] || ! $reason_matches ) {
					return false;
				}
				continue;
			}
			if (
				'owner_approved_publication' !== $decision['state']
				|| true !== $decision['approved']
				|| 'exact_owner_receipt_and_delivery_verified' !== $decision['reason']
				|| 'exact' !== $decision['delivery_validation']
			) {
				return false;
			}
			$approved[] = $entity_id;
		}

		return $approved === $status['approved_entity_ids']
			&& count( $approved ) === $status['approved_count']
			&& count( $expected_entity_ids ) - count( $approved ) === $status['held_count'];
	}
}

if ( ! function_exists( 'complete99_owner_publication_cached_status' ) ) {
	/**
	 * Store or read the last pre-gate status for other private data builders.
	 *
	 * @param array|null $status Status to cache, or null to read it.
	 * @return array
	 */
	function complete99_owner_publication_cached_status( $status = null ) {
		static $cached = array();
		if ( is_array( $status ) ) {
			$cached = $status;
		}
		return $cached;
	}
}

if ( ! function_exists( 'complete99_owner_publication_cached_pre_gate_entities' ) ) {
	/**
	 * Store or read exact pre-gate candidate entities for private builders.
	 *
	 * @param array|null $entities Candidate entities keyed by entity ID.
	 * @return array
	 */
	function complete99_owner_publication_cached_pre_gate_entities( $entities = null ) {
		static $cached = array();
		if ( is_array( $entities ) ) {
			$cached = $entities;
		}
		return $cached;
	}
}

$c99_owner_publication_scope = array(
	'surface_class'     => 'public_discovery',
	'index_policy'      => 'noindex_until_longform_review',
	'publication_state' => 'approved_public',
	'public_api'        => true,
	'public_page'       => true,
	'search_index'      => false,
);

$c99_owner_publication_delivery_bundle = static function ( $stem, $png, $webp, $avif, $webp_768, $avif_768 ) {
	$file_receipt = static function ( $filename, $receipt ) {
		return array(
			'relative_path' => 'assets/images/science/' . $filename,
			'bytes'         => $receipt[0],
			'sha256'        => 'sha256:' . $receipt[1],
		);
	};
	return array(
		'source_asset'   => $file_receipt( $stem . '.png', $png ),
		'delivery_files' => array(
			'webp'     => $file_receipt( $stem . '.webp', $webp ),
			'avif'     => $file_receipt( $stem . '.avif', $avif ),
			'webp_768' => $file_receipt( $stem . '-768.webp', $webp_768 ),
			'avif_768' => $file_receipt( $stem . '-768.avif', $avif_768 ),
		),
	);
};

$c99_owner_publication_candidate = static function ( $entity_id, $asset_bundle, $content_sha256, $registry_bytes, $registry_sha256 ) use ( $c99_owner_publication_scope ) {
	$candidate = array(
		'schema'            => 'complete99-owner-publication-candidate/v2',
		'candidate_id'      => 'publication-candidate-' . $entity_id,
		'entity_id'         => $entity_id,
		'state'             => 'held_pending_owner_approval',
		'source_asset'      => $asset_bundle['source_asset'],
		'delivery_files'    => $asset_bundle['delivery_files'],
		'deployment_policy' => complete99_owner_publication_deployment_policy(),
		'bilingual_content' => array(
			'canonicalization' => 'complete99-owner-publication-pre-gate-content/v1',
			'locales'          => array( 'he', 'en' ),
			'sha256'           => $content_sha256,
		),
		'registry_source'   => array(
			'relative_path' => 'data/culinary-science-pilot.php',
			'bytes'         => $registry_bytes,
			'sha256'        => $registry_sha256,
		),
		'promotion_scope'   => $c99_owner_publication_scope,
		'candidate_sha256'  => '',
	);
	$candidate['candidate_sha256'] = complete99_owner_publication_candidate_digest( $candidate );
	return $candidate;
};

$c99_owner_publication_required_ids = array(
	'region-syria-aleppo',
	'hub-aleppine-kibbeh-family',
	'ingredient-syrian-bulgur',
	'ingredient-syrian-red-meat',
	'technique-syrian-bulgur-hydration',
	'technique-syrian-kibbeh-cooking',
	'tradition-aleppan-jewish-foodways',
	'ingredient-shoyu-koji',
	'equipment-kioke',
	'guide-koji-hydrolysis',
	'reaction-koji-enzymatic-hydrolysis',
	'standard-jas-shoyu-1703',
);

$c99_registry_source_bytes  = 399363;
$c99_registry_source_sha256 = 'sha256:7dfd7127bfe7069e625e4d75e65be6a878c19224856462d55da3a46b82312c11';

$c99_owner_publication_delivery_bundles = array(
	'region-syria-aleppo' => $c99_owner_publication_delivery_bundle(
		'c99-science-syrian-aleppo-table-v01',
		array( 2702757, '90e354e00d4a5e63f661f162284b1b5d0dc21fcada53e2f34d5f937fdd784042' ),
		array( 152402, '12be0704e4211ec5b39686ff291ee1b1c162cde2d93e6fc66b83c3316c1fd6c6' ),
		array( 66291, 'b76d2dbe8988e2ff77f3945a7a76ba406526a7f083a4c3ff98e77402af697a2c' ),
		array( 53280, 'bf33c3713d91c02314dcba5a12880fb4c1ff52a1517687d9de63556d1a66b73a' ),
		array( 21506, '5c988117343f91799e5901c761825510b1a43afc38711c9c76738cc81d87b12e' )
	),
	'hub-aleppine-kibbeh-family' => $c99_owner_publication_delivery_bundle(
		'c99-science-aleppine-kibbeh-family-v01',
		array( 3423946, '8ce08587469c4d1c6f18f3cd399c41b5fbfb2f6cc72b23a3046bca167ac8fbdc' ),
		array( 264090, 'ad8b1b3fdfc59eb22b7e06e443b1477ec152ab4f00985d44533ff7245d1f8d5b' ),
		array( 100486, 'e852acee075ced29382816cf72126596dc9be163c252f7e47e9da96673af3c85' ),
		array( 79274, 'aeeb41fdc4dfa5cdf5b55d391e8bb08a2823de92797d2ab4e0f2d48e5a5b34dd' ),
		array( 26650, 'bec787d4d6c518e2840b31f055d91189e928e14339e64e3350e781ec7c0429fe' )
	),
	'ingredient-syrian-bulgur' => $c99_owner_publication_delivery_bundle(
		'c99-science-syrian-bulgur-v01',
		array( 2690132, '765a8b844ce6b12448e81e612a74cbd97e8a2e86506002260b1a29af833050a2' ),
		array( 134840, '0abf067b8a84796b002103da6839b549c47adf37bd7821d35b5c0a66b15ab620' ),
		array( 61530, '751c0eba10bc368aa310678bb09e26efef563dba80eed83e40c80f9475b11608' ),
		array( 47448, '1e0ad172fac3ec7b1d9c14456f67322689153abc53e9eb2c98888598460db455' ),
		array( 18438, '626744dab964a0eaf42a61bfa6c1287f600513596e07a1ccadb3c0fa47ab3644' )
	),
	'ingredient-syrian-red-meat' => $c99_owner_publication_delivery_bundle(
		'c99-science-syrian-lamb-beef-family-v01',
		array( 2490567, 'f2da86bc9b38544c42a1608103ad8b78294ab7385ee4bc93851f5e4716a8337e' ),
		array( 115636, 'e87dc0d2d74bffe2b48156838a11f704fe35d0480380bd965b186df0affb67db' ),
		array( 49327, '93590f1431df04a989f7cc2fae9ca0f9c406241eba80ca5117808c96b4b1a21f' ),
		array( 38118, 'eb1c408619280d7475ac87aee0ce94253533c255576101c8f40231b877bb8c93' ),
		array( 15253, '190d8c7606cca9f18c9b307e95314695a6aee2d19c657ee2bcfb8322757c7d82' )
	),
	'technique-syrian-bulgur-hydration' => $c99_owner_publication_delivery_bundle(
		'c99-science-syrian-bulgur-hydration-v01',
		array( 2457045, '0839e05df007410bc9ef224683241ac653da4cd35bafe2adc17ac84a12674ccb' ),
		array( 102572, '762a91a1a3c00483b1c5a572f17b4659a328ee86cf832a281ef360aace6eecd1' ),
		array( 48474, 'b11d0a99c6fadf8ae2a65c442617226984e91b9468ba22f88ef600cb68056c5b' ),
		array( 34876, '4eade017a529f47d94fbb58044c06dbc6c1f79c836b2691b5dbe04af3fb927a1' ),
		array( 14353, '0f1013188fb0a543509e32e5d3096b510c098b7ff647d3fe459b54cc0eca9ddc' )
	),
	'technique-syrian-kibbeh-cooking' => $c99_owner_publication_delivery_bundle(
		'c99-science-syrian-kibbeh-cooking-v01',
		array( 2221839, '0c062a2d04ae4a0307f3af4366869f63e295d64bc99d10712fc016627693f0c3' ),
		array( 85324, 'c28467bb07a6900d22618b17791ae6c5eefee7d73bf3c5f1a75b4d4661db2d03' ),
		array( 39314, '3b688c7cc264306637f5ed05104d1b6e244db2a099344be86042870b11213720' ),
		array( 28304, '17bde0718851596d1499c5d9683da3c02e79e74ce7754207c37c9f3049667c2a' ),
		array( 12190, 'ec4c53ada52785a9fa6c105536980b151e52b8492cb6d9fd9dd45cb496cf8368' )
	),
	'tradition-aleppan-jewish-foodways' => $c99_owner_publication_delivery_bundle(
		'c99-science-aleppan-jewish-foodways-v01',
		array( 2680059, 'e3e3da6e28de043fa5f97e3803441c8e84798fab3ac8b810d70cccfe90c35418' ),
		array( 130346, '1160071fd8b80a9d11ad2792ed8b53643e2b086a4db3d81772ddedf98bba2797' ),
		array( 55988, '5302c91d7727a644426658560fa26e0c38ced0aeceaf25e1a1a3f93cb456e4f0' ),
		array( 38754, 'aef249e5496dc5b5b5c4c1d80ff2967da0eef8fe7d263a2822a88f3a7ccadfa8' ),
		array( 14808, '17a8e94db9630d7ffb1ecc8a860f3dcb46e7cbe16013b01ac51de4dc7144968a' )
	),
	'ingredient-shoyu-koji' => $c99_owner_publication_delivery_bundle(
		'c99-science-shoyu-koji-substrate-v01',
		array( 2677419, '5cff891c9801e1bcd3c274284f82c580134a841246da444737d41058ca6b509a' ),
		array( 151948, '1213fee79dbd9dfe3d597aaddb0011e57ed0bc014fdd13a83a23ccdf478f1319' ),
		array( 62876, '4ad56ccb9768dc462e510c7ed08dc59d8e24ea74e7bed5c65385d920bb88f684' ),
		array( 61826, 'a96cdda0342d6f4457a41b05833450a931ebb110f8855eae4ad7f844894e0a25' ),
		array( 24241, '92602dd7b9e17bb813b237552443c0da52b7994887b3b1c211dcf6757aa3293a' )
	),
	'equipment-kioke' => $c99_owner_publication_delivery_bundle(
		'c99-science-kioke-wooden-barrel-v01',
		array( 2498585, '43508327f310be0c1875d3dd00a9d35daa0502152c659c9b706ff6fa81413ddd' ),
		array( 101652, 'bafac22402602dbda38f512754a701a4388b262e89dda7e7cac68d6dd2616a23' ),
		array( 42001, '253829c672293dcff4e96064a0c765b881fd600aaf22f074c9ed61175f2b27a8' ),
		array( 27124, '9e1aded15a2ec45391299bbbeea6fc6ae6d84f4ac5e86020b49dfe4a3be19a0d' ),
		array( 11723, '13092eee69ecf4b0a6d6da64346e30f07afe89e4734154cf61492df1e37ecbb5' )
	),
	'guide-koji-hydrolysis' => $c99_owner_publication_delivery_bundle(
		'c99-science-koji-enzymes-hydrolysis-guide-v01',
		array( 2871799, '69fa18417864c71b7d2b95c30c9febacb87d7430e8da6155b5484b05c307e06a' ),
		array( 138348, '6bb6f6becd75c7e4fdeed3e76f70e616ed6fc8713b94163475f585c4ac0d1a77' ),
		array( 64625, '278b633bfea9dbc7ce394a3084f6e3755b35d275b700ed4f488b7dcb9457fd87' ),
		array( 47580, 'ba41228b267d28942a35125e16bd873f975f02af6c9f20faf06486582d86d428' ),
		array( 17226, 'a37aff4fe4cd6d9972a867347fd5e9e4f27bc55a79a586c1f1d7640441dd0365' )
	),
	'reaction-koji-enzymatic-hydrolysis' => $c99_owner_publication_delivery_bundle(
		'c99-science-koji-enzymatic-hydrolysis-v01',
		array( 2739263, '737f0c74b8f0abce9921231e2c9185d73d33a74c836c029452085724e5e7a357' ),
		array( 104658, '5978859d2161cbb6a41daddf52cc7402952a63068b9a0c0f684166237eee66a5' ),
		array( 50371, '88bca6d0f57293e85be2ba32100bce0a1d6eedd1612c2c9a94604b4dfb61be78' ),
		array( 36764, 'eb771026f662c8a664394b1d8a807cb65caec4b9951a3cd3fd821e584d3acee2' ),
		array( 17901, '0157d0ad3875ffdf36a90e5c5a82c3bafb331c87897f564e5f7dae05f66d1ba8' )
	),
	'standard-jas-shoyu-1703' => $c99_owner_publication_delivery_bundle(
		'c99-science-jas-1703-shoyu-standard-v01',
		array( 2660520, '4f0781828ba648106456db31012c6c664ed633104deea972493020925ba80672' ),
		array( 76394, '5a099802acabf8e704a03e5b254844519d2e32df0d8358d277b8594889e16ebf' ),
		array( 30904, '67cf96aca9c4ea71ebff0612ca9d1f12aa7708f9acad8eb8458cd4952c47e3a8' ),
		array( 16830, '72a7aefb5b09dbb550bf537d18d04294a7a32dd93243e137814c813df568016c' ),
		array( 8414, 'ee21cdaf8d480c7699072fed21a3bfe3e9aa49dcfb51711c82688591cb506d94' )
	),
);

$c99_owner_publication_candidates = array(
	'region-syria-aleppo' => $c99_owner_publication_candidate( 'region-syria-aleppo', $c99_owner_publication_delivery_bundles['region-syria-aleppo'], 'sha256:bf46196147c74ef528b9d6ce9f05535f0809d6a8fb1d6a3e49e9605d2a3d1b0a', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'hub-aleppine-kibbeh-family' => $c99_owner_publication_candidate( 'hub-aleppine-kibbeh-family', $c99_owner_publication_delivery_bundles['hub-aleppine-kibbeh-family'], 'sha256:40f6105f462796944802cb0873b1db144be4177ab47f98d270520554824f41a6', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'ingredient-syrian-bulgur' => $c99_owner_publication_candidate( 'ingredient-syrian-bulgur', $c99_owner_publication_delivery_bundles['ingredient-syrian-bulgur'], 'sha256:7e4596ac6ca571d4a64990227bea8ce390a67cb9de93648b2abb99c93bf9a412', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'ingredient-syrian-red-meat' => $c99_owner_publication_candidate( 'ingredient-syrian-red-meat', $c99_owner_publication_delivery_bundles['ingredient-syrian-red-meat'], 'sha256:1582de336952c4675d09d29de9d61b8753ad4f3dc70cb2a18d7e29d220380baf', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'technique-syrian-bulgur-hydration' => $c99_owner_publication_candidate( 'technique-syrian-bulgur-hydration', $c99_owner_publication_delivery_bundles['technique-syrian-bulgur-hydration'], 'sha256:c680a46d0fb2db51948fd61f91d5d3371b71f13990feed1eb9d4b6f6be416450', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'technique-syrian-kibbeh-cooking' => $c99_owner_publication_candidate( 'technique-syrian-kibbeh-cooking', $c99_owner_publication_delivery_bundles['technique-syrian-kibbeh-cooking'], 'sha256:27119a1344bf9a82f0912833cba7757b4747f5810cf2b0e658844d0371164851', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'tradition-aleppan-jewish-foodways' => $c99_owner_publication_candidate( 'tradition-aleppan-jewish-foodways', $c99_owner_publication_delivery_bundles['tradition-aleppan-jewish-foodways'], 'sha256:4333b3622953e2008fd4ea4eae1183dc0b94887278140c6dfbb4780f39cd4dbc', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'ingredient-shoyu-koji' => $c99_owner_publication_candidate( 'ingredient-shoyu-koji', $c99_owner_publication_delivery_bundles['ingredient-shoyu-koji'], 'sha256:dc8e270ead1cdaf6950f7eb8840095ebdf902e517e30191952791c2d3e82a2a2', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'equipment-kioke' => $c99_owner_publication_candidate( 'equipment-kioke', $c99_owner_publication_delivery_bundles['equipment-kioke'], 'sha256:0805f77c6e6fe49d4e75075c0446800d460fe675648f504b8f1f51f732ba3587', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'guide-koji-hydrolysis' => $c99_owner_publication_candidate( 'guide-koji-hydrolysis', $c99_owner_publication_delivery_bundles['guide-koji-hydrolysis'], 'sha256:24559d8b98674cce7323a24d2d2de623fa3622ddb311f9058ee71f26438e1073', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'reaction-koji-enzymatic-hydrolysis' => $c99_owner_publication_candidate( 'reaction-koji-enzymatic-hydrolysis', $c99_owner_publication_delivery_bundles['reaction-koji-enzymatic-hydrolysis'], 'sha256:1aeb794fcc6495b42f7d5d34df7ed9f5c42bc30a1fb878e5052837078e868490', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
	'standard-jas-shoyu-1703' => $c99_owner_publication_candidate( 'standard-jas-shoyu-1703', $c99_owner_publication_delivery_bundles['standard-jas-shoyu-1703'], 'sha256:6fcfac6893b516c71fc4e760d895181c3fd201116bae70574404c7c83cc98acd', $c99_registry_source_bytes, $c99_registry_source_sha256 ),
);

return array(
	'schema'              => 'complete99-owner-publication-approval-registry/v2',
	'generated_at'        => '2026-08-08',
	'required_locales'    => array( 'he', 'en' ),
	'required_entity_ids' => $c99_owner_publication_required_ids,
	// Owner signing keys require a separate authenticated enrollment ceremony.
	'trusted_owner_keys'  => array(),
	'candidates'          => $c99_owner_publication_candidates,
	// No named owner has approved these candidates. Keep receipts empty.
	'receipts'            => array(),
);
