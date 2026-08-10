<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owner-authorized, fail-closed search activation for the reviewed Museum.
 *
 * This policy does not modify the canonical v20 science registry. It binds an
 * exact, reviewed owner allowlist to that registry's canonical payload digest.
 * Runtime validation must succeed before any owner becomes indexable.
 */
return array(
	'schema'            => 'complete99-culinary-science-search-activation/v1',
	'version'           => 'culinary-science-search-activation-2026.08.11.v1',
	'activated_at'      => '2026-08-11',
	'authorization'     => array(
		'basis'       => 'owner_authorized_search_activation',
		'recorded_at' => '2026-08-11',
	),
	'registry_contract' => array(
		'schema'         => 'complete99-culinary-science-registry/v6',
		'version'        => 'culinary-science-2026.08.08.v20',
		'payload_sha256' => '677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54',
	),
	'activation'        => array(
		'state'       => 'approved',
		'owner_count' => 18,
		'route_count' => 36,
		'locales'     => array( 'he', 'en' ),
		'owner_ids'   => array(
			'cuisine-japanese-washoku',
			'cuisine-lebanese-regional',
			'cuisine-syrian-regional',
			'equipment-wasabi-grater',
			'guide-umami-synergy',
			'guide-wasabi-aitc',
			'hub-japanese-foundations-lab',
			'ingredient-fresh-dutch-wasabi',
			'ingredient-fresh-wasabi',
			'ingredient-hon-mirin',
			'ingredient-katsuobushi',
			'ingredient-kioke-shoyu',
			'ingredient-kito-yuzu',
			'ingredient-koji-starter-culture',
			'ingredient-kombu',
			'ingredient-kome-koji',
			'ingredient-koshihikari-rice',
			'museum-culinary-science',
		),
	),
	'exclusions'        => array(
		'owner_ids'       => array( 'preparation-ichiban-dashi' ),
		'owner_reason'    => 'culinary_test_not_verified',
		'section_state'   => 'owner_canonical_only',
		'query_state'     => 'noindex_follow',
		'nonpublic_state' => 'excluded',
	),
);
