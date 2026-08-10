<?php

/** Generated private cross-domain binding registry. */

defined( 'ABSPATH' ) || exit;

return array (
  'schema' => 'complete99-cross-domain-binding-registry/v3',
  'version' => 'complete99-cross-domain-bindings-2026.08.08.v3',
  'generated_at' => '2026-08-08',
  'input_contracts' =>
  array (
    'consumer_menu' =>
    array (
      'source_path' => 'data/consumer-menu.php',
      'source_schema' => 'complete99-consumer-menu-array/v1',
      'source_version' => 'unversioned',
      'payload_sha256' => '134da0d6cefe66790dc4551e4aa95453bfa58b80667c68749ec3d7791bca869f',
    ),
    'dish_entity_trees' =>
    array (
      'source_path' => 'data/dish-entity-trees.php',
      'source_schema' => 'complete99-dish-entity-tree-registry/v1',
      'source_version' => 'registry-reviewed-2026-07-31',
      'payload_sha256' => '4d7a19fba4e0cb4b17b86542bb0229341830bc79debf8ac13cb545ec2329c264',
    ),
    'catalog_product_seeds' =>
    array (
      'source_path' => 'data/catalog-product-seeds.php',
      'source_schema' => 'complete99-catalog-product-seeds/v1',
      'source_version' => 'reviewed-2026-08-06',
      'payload_sha256' => '6049f5d6d951df273481f6200dca6c1ba895817c0345e1b74a5424be2fb1b132',
    ),
    'culinary_science' =>
    array (
      'source_path' => 'data/culinary-science-pilot.php',
      'source_schema' => 'complete99-culinary-science-registry/v6',
      'source_version' => 'culinary-science-2026.08.08.v20',
      'payload_sha256' => '677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54',
    ),
    'live_catalog_products' =>
    array (
      'source_path' => 'data/live-catalog-products.php',
      'source_schema' => 'complete99-live-catalog-products/v1',
      'source_version' => 'reviewed-2026-08-06',
      'payload_sha256' => '56a8fbddade21570f874e19a2dc7f8562edf0ab6b11f9d14b79a95116391339f',
    ),
    'live_catalog_relations' =>
    array (
      'source_path' => 'data/live-catalog-relations.php',
      'source_schema' => 'complete99-live-catalog-relations/v1',
      'source_version' => 'reviewed-2026-08-06',
      'payload_sha256' => 'debdd5785e539c55ab9b0ab53c911ae3d7f842dc3ede9f077d59d4ab96c9faf5',
    ),
  ),
  'controlled_vocabulary' =>
  array (
    'binding_kinds' =>
    array (
      0 => 'menu_dish_science_dish',
      1 => 'menu_component_science_entity',
      2 => 'woo_product_science_entity',
    ),
    'resolution_states' =>
    array (
      0 => 'linked',
      1 => 'no_match',
      2 => 'unresolved',
    ),
    'registries' =>
    array (
      0 => 'consumer_menu',
      1 => 'dish_entity_trees',
      2 => 'culinary_science',
      3 => 'woocommerce',
    ),
    'entity_types' =>
    array (
      0 => 'dish',
      1 => 'component',
      2 => 'ingredient',
      3 => 'preparation',
      4 => 'equipment',
      5 => 'product',
    ),
    'relations' =>
    array (
      0 => 'same_dish_identity',
      1 => 'house_expression_of',
      2 => 'reference_only',
      3 => 'same_ingredient_identity',
      4 => 'same_preparation_identity',
      5 => 'retail_instance_of',
    ),
    'projection_scopes' =>
    array (
      0 => 'private_only',
      1 => 'public_navigation',
      2 => 'public_product_navigation',
    ),
    'review_states' =>
    array (
      0 => 'unreviewed',
      1 => 'source_reviewed',
      2 => 'verified',
    ),
    'candidate_states' =>
    array (
      0 => 'pending_review',
      1 => 'rejected',
    ),
    'candidate_reason_codes' =>
    array (
      0 => 'legacy_explicit_relation_requires_review',
      1 => 'insufficient_evidence',
      2 => 'scope_mismatch',
      3 => 'different_variant',
      4 => 'component_is_composite',
      5 => 'product_identity_unverified',
      6 => 'target_type_mismatch',
      7 => 'duplicate_conflict',
    ),
    'evidence_registries' =>
    array (
      0 => 'dish_source_registry',
      1 => 'culinary_science_sources',
      2 => 'culinary_science_registry',
      3 => 'catalog_product_seeds',
      4 => 'live_catalog_products',
      5 => 'live_catalog_relations',
    ),
  ),
  'records' =>
  array (
    0 =>
    array (
      'id' => 'menu-component--menu-reference-aja--component-herb-omelette',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-herb-omelette',
        'scope_entity_id' => 'menu-reference-aja',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    1 =>
    array (
      'id' => 'menu-component--menu-reference-aja--component-salads',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-salads',
        'scope_entity_id' => 'menu-reference-aja',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    2 =>
    array (
      'id' => 'menu-component--menu-reference-aja--component-sauces',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-sauces',
        'scope_entity_id' => 'menu-reference-aja',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    3 =>
    array (
      'id' => 'menu-component--menu-reference-aja--ingredient-egg',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-egg',
        'scope_entity_id' => 'menu-reference-aja',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    4 =>
    array (
      'id' => 'menu-component--menu-reference-aja--ingredient-herbs-unspecified',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-herbs-unspecified',
        'scope_entity_id' => 'menu-reference-aja',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    5 =>
    array (
      'id' => 'menu-component--menu-reference-beet-kubbeh--component-beet-soup',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-beet-soup',
        'scope_entity_id' => 'menu-reference-beet-kubbeh',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    6 =>
    array (
      'id' => 'menu-component--menu-reference-beet-kubbeh--component-kubbeh',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-kubbeh',
        'scope_entity_id' => 'menu-reference-beet-kubbeh',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    7 =>
    array (
      'id' => 'menu-component--menu-reference-beet-kubbeh--component-meat-filled-kubbeh',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-meat-filled-kubbeh',
        'scope_entity_id' => 'menu-reference-beet-kubbeh',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    8 =>
    array (
      'id' => 'menu-component--menu-reference-beet-kubbeh--ingredient-beet',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-beet',
        'scope_entity_id' => 'menu-reference-beet-kubbeh',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    9 =>
    array (
      'id' => 'menu-component--menu-reference-beet-kubbeh--ingredient-meat-unspecified',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-meat-unspecified',
        'scope_entity_id' => 'menu-reference-beet-kubbeh',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    10 =>
    array (
      'id' => 'menu-component--menu-reference-chicken-liver--component-warm-side-unspecified',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-warm-side-unspecified',
        'scope_entity_id' => 'menu-reference-chicken-liver',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    11 =>
    array (
      'id' => 'menu-component--menu-reference-chicken-liver--ingredient-chicken-liver',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-chicken-liver',
        'scope_entity_id' => 'menu-reference-chicken-liver',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    12 =>
    array (
      'id' => 'menu-component--menu-reference-couscous--component-changing-stew',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-changing-stew',
        'scope_entity_id' => 'menu-reference-couscous',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    13 =>
    array (
      'id' => 'menu-component--menu-reference-couscous--component-couscous',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-couscous',
        'scope_entity_id' => 'menu-reference-couscous',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    14 =>
    array (
      'id' => 'menu-component--menu-reference-couscous--component-vegetables',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-vegetables',
        'scope_entity_id' => 'menu-reference-couscous',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    15 =>
    array (
      'id' => 'menu-component--menu-reference-fish-patties--component-fish-patties',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-fish-patties',
        'scope_entity_id' => 'menu-reference-fish-patties',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    16 =>
    array (
      'id' => 'menu-component--menu-reference-fish-patties--component-tomato-sauce',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-tomato-sauce',
        'scope_entity_id' => 'menu-reference-fish-patties',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    17 =>
    array (
      'id' => 'menu-component--menu-reference-fish-patties--ingredient-fish',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-fish',
        'scope_entity_id' => 'menu-reference-fish-patties',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    18 =>
    array (
      'id' => 'menu-component--menu-reference-fish-patties--ingredient-tomato',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-tomato',
        'scope_entity_id' => 'menu-reference-fish-patties',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    19 =>
    array (
      'id' => 'menu-component--menu-reference-grilled-chicken--component-salads',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-salads',
        'scope_entity_id' => 'menu-reference-grilled-chicken',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    20 =>
    array (
      'id' => 'menu-component--menu-reference-grilled-chicken--component-sides-unspecified',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-sides-unspecified',
        'scope_entity_id' => 'menu-reference-grilled-chicken',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    21 =>
    array (
      'id' => 'menu-component--menu-reference-grilled-chicken--ingredient-chicken-breast',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-chicken-breast',
        'scope_entity_id' => 'menu-reference-grilled-chicken',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    22 =>
    array (
      'id' => 'menu-component--menu-reference-homemade-meatballs--component-meatballs',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-meatballs',
        'scope_entity_id' => 'menu-reference-homemade-meatballs',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    23 =>
    array (
      'id' => 'menu-component--menu-reference-homemade-meatballs--component-sauce-unspecified',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-sauce-unspecified',
        'scope_entity_id' => 'menu-reference-homemade-meatballs',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    24 =>
    array (
      'id' => 'menu-component--menu-reference-homemade-meatballs--component-warm-side-unspecified',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-warm-side-unspecified',
        'scope_entity_id' => 'menu-reference-homemade-meatballs',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    25 =>
    array (
      'id' => 'menu-component--menu-reference-sabich--component-hot-sauce',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-hot-sauce',
        'scope_entity_id' => 'menu-reference-sabich',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    26 =>
    array (
      'id' => 'menu-component--menu-reference-sabich--component-salad',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-salad',
        'scope_entity_id' => 'menu-reference-sabich',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    27 =>
    array (
      'id' => 'menu-component--menu-reference-sabich--ingredient-amba',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-amba',
        'scope_entity_id' => 'menu-reference-sabich',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    28 =>
    array (
      'id' => 'menu-component--menu-reference-sabich--ingredient-aubergine',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-aubergine',
        'scope_entity_id' => 'menu-reference-sabich',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    29 =>
    array (
      'id' => 'menu-component--menu-reference-sabich--ingredient-egg',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-egg',
        'scope_entity_id' => 'menu-reference-sabich',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    30 =>
    array (
      'id' => 'menu-component--menu-reference-sabich--ingredient-potato',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-potato',
        'scope_entity_id' => 'menu-reference-sabich',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    31 =>
    array (
      'id' => 'menu-component--menu-reference-sabich--ingredient-tahini',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-tahini',
        'scope_entity_id' => 'menu-reference-sabich',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    32 =>
    array (
      'id' => 'menu-component--menu-reference-sabtucha--component-sabich',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-sabich',
        'scope_entity_id' => 'menu-reference-sabtucha',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    33 =>
    array (
      'id' => 'menu-component--menu-reference-sabtucha--component-salads',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-salads',
        'scope_entity_id' => 'menu-reference-sabtucha',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    34 =>
    array (
      'id' => 'menu-component--menu-reference-sabtucha--component-shakshuka-egg',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-shakshuka-egg',
        'scope_entity_id' => 'menu-reference-sabtucha',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    35 =>
    array (
      'id' => 'menu-component--menu-reference-sabtucha--ingredient-aubergine',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-aubergine',
        'scope_entity_id' => 'menu-reference-sabtucha',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    36 =>
    array (
      'id' => 'menu-component--menu-reference-sabtucha--ingredient-egg',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-egg',
        'scope_entity_id' => 'menu-reference-sabtucha',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    37 =>
    array (
      'id' => 'menu-component--menu-reference-sabtucha--ingredient-potato',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-potato',
        'scope_entity_id' => 'menu-reference-sabtucha',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    38 =>
    array (
      'id' => 'menu-component--menu-reference-schnitzel--component-salads',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-salads',
        'scope_entity_id' => 'menu-reference-schnitzel',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    39 =>
    array (
      'id' => 'menu-component--menu-reference-schnitzel--component-sauces',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-sauces',
        'scope_entity_id' => 'menu-reference-schnitzel',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    40 =>
    array (
      'id' => 'menu-component--menu-reference-schnitzel--component-schnitzel',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-schnitzel',
        'scope_entity_id' => 'menu-reference-schnitzel',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    41 =>
    array (
      'id' => 'menu-component--menu-reference-shakshuka--component-tomato-sauce',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-tomato-sauce',
        'scope_entity_id' => 'menu-reference-shakshuka',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    42 =>
    array (
      'id' => 'menu-component--menu-reference-shakshuka--ingredient-egg',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-egg',
        'scope_entity_id' => 'menu-reference-shakshuka',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    43 =>
    array (
      'id' => 'menu-component--menu-reference-shakshuka--ingredient-tomato',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-tomato',
        'scope_entity_id' => 'menu-reference-shakshuka',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    44 =>
    array (
      'id' => 'menu-component--menu-reference-yemenite-soup--component-soup',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-soup',
        'scope_entity_id' => 'menu-reference-yemenite-soup',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    45 =>
    array (
      'id' => 'menu-component--menu-reference-yemenite-soup--component-yemenite-beef-soup',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'component',
        'entity_id' => 'component-yemenite-beef-soup',
        'scope_entity_id' => 'menu-reference-yemenite-soup',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    46 =>
    array (
      'id' => 'menu-component--menu-reference-yemenite-soup--ingredient-beef',
      'kind' => 'menu_component_science_entity',
      'subject' =>
      array (
        'registry' => 'dish_entity_trees',
        'entity_type' => 'ingredient',
        'entity_id' => 'ingredient-beef',
        'scope_entity_id' => 'menu-reference-yemenite-soup',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    47 =>
    array (
      'id' => 'menu-dish--menu-reference-aja',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-aja',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    48 =>
    array (
      'id' => 'menu-dish--menu-reference-beet-kubbeh',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-beet-kubbeh',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    49 =>
    array (
      'id' => 'menu-dish--menu-reference-chicken-liver',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-chicken-liver',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    50 =>
    array (
      'id' => 'menu-dish--menu-reference-couscous',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-couscous',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    51 =>
    array (
      'id' => 'menu-dish--menu-reference-fish-patties',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-fish-patties',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    52 =>
    array (
      'id' => 'menu-dish--menu-reference-grilled-chicken',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-grilled-chicken',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    53 =>
    array (
      'id' => 'menu-dish--menu-reference-homemade-meatballs',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-homemade-meatballs',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    54 =>
    array (
      'id' => 'menu-dish--menu-reference-sabich',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-sabich',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    55 =>
    array (
      'id' => 'menu-dish--menu-reference-sabtucha',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-sabtucha',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    56 =>
    array (
      'id' => 'menu-dish--menu-reference-schnitzel',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-schnitzel',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    57 =>
    array (
      'id' => 'menu-dish--menu-reference-shakshuka',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-shakshuka',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    58 =>
    array (
      'id' => 'menu-dish--menu-reference-yemenite-soup',
      'kind' => 'menu_dish_science_dish',
      'subject' =>
      array (
        'registry' => 'consumer_menu',
        'entity_type' => 'dish',
        'entity_id' => 'menu-reference-yemenite-soup',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    59 =>
    array (
      'id' => 'woo-product--product-amba-500g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-amba-500g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    60 =>
    array (
      'id' => 'woo-product--product-aubergine-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-aubergine-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    61 =>
    array (
      'id' => 'woo-product--product-beef-shank-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-beef-shank-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    62 =>
    array (
      'id' => 'woo-product--product-beetroot-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-beetroot-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    63 =>
    array (
      'id' => 'woo-product--product-breadcrumbs-500g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-breadcrumbs-500g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    64 =>
    array (
      'id' => 'woo-product--product-bulgur-fine-500g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-bulgur-fine-500g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-syrian-bulgur',
          'state' => 'pending_review',
          'reason_code' => 'scope_mismatch',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-syrian-bulgur',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-bulgur-fine-500g',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-bulgur-fine-500g',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    65 =>
    array (
      'id' => 'woo-product--product-chicken-breast-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-chicken-breast-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    66 =>
    array (
      'id' => 'woo-product--product-chicken-liver-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-chicken-liver-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    67 =>
    array (
      'id' => 'woo-product--product-chickpeas-dry-500g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-chickpeas-dry-500g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    68 =>
    array (
      'id' => 'woo-product--product-couscous-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-couscous-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    69 =>
    array (
      'id' => 'woo-product--product-cucumber-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-cucumber-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    70 =>
    array (
      'id' => 'woo-product--product-eggs-l-12',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-eggs-l-12',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    71 =>
    array (
      'id' => 'woo-product--product-fresh-japanese-wasabi-250g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-fresh-japanese-wasabi-250g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-fresh-wasabi',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-fresh-wasabi',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-fresh-japanese-wasabi-250g',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-fresh-japanese-wasabi-250g',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    72 =>
    array (
      'id' => 'woo-product--product-fresh-wasabi-50-60g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-fresh-wasabi-50-60g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-fresh-dutch-wasabi',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-fresh-dutch-wasabi',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-fresh-wasabi-50-60g',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-fresh-wasabi-50-60g',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    73 =>
    array (
      'id' => 'woo-product--product-ground-beef-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-ground-beef-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    74 =>
    array (
      'id' => 'woo-product--product-hagane-zame-large',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-hagane-zame-large',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'equipment',
          'entity_id' => 'equipment-wasabi-grater',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'equipment-wasabi-grater',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-hagane-zame-large',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-hagane-zame-large',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    75 =>
    array (
      'id' => 'woo-product--product-hawayej-soup-100g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-hawayej-soup-100g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    76 =>
    array (
      'id' => 'woo-product--product-hishiroku-chouhaku-kin-20g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-hishiroku-chouhaku-kin-20g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-koji-starter-culture',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-koji-starter-culture',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-hishiroku-chouhaku-kin-20g',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-hishiroku-chouhaku-kin-20g',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    77 =>
    array (
      'id' => 'woo-product--product-hishiroku-dried-rice-koji-500g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-hishiroku-dried-rice-koji-500g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-kome-koji',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-kome-koji',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-hishiroku-dried-rice-koji-500g',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-hishiroku-dried-rice-koji-500g',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    78 =>
    array (
      'id' => 'woo-product--product-honkarebushi-200g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-honkarebushi-200g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-katsuobushi',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-katsuobushi',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-honkarebushi-200g',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-honkarebushi-200g',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    79 =>
    array (
      'id' => 'woo-product--product-hot-sauce-60ml',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-hot-sauce-60ml',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    80 =>
    array (
      'id' => 'woo-product--product-kito-yuzu-juice-100ml',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-kito-yuzu-juice-100ml',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-kito-yuzu',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-kito-yuzu',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-kito-yuzu-juice-100ml',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-kito-yuzu-juice-100ml',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    81 =>
    array (
      'id' => 'woo-product--product-koshihikari-uozu-2kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-koshihikari-uozu-2kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-koshihikari-rice',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-koshihikari-rice',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-koshihikari-uozu-2kg',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-koshihikari-uozu-2kg',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    82 =>
    array (
      'id' => 'woo-product--product-olive-oil-750ml',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-olive-oil-750ml',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    83 =>
    array (
      'id' => 'woo-product--product-onion-dry-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-onion-dry-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    84 =>
    array (
      'id' => 'woo-product--product-parsley-100g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-parsley-100g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    85 =>
    array (
      'id' => 'woo-product--product-pickles-brine-320g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-pickles-brine-320g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    86 =>
    array (
      'id' => 'woo-product--product-pita-12x50g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-pita-12x50g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    87 =>
    array (
      'id' => 'woo-product--product-potato-white-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-potato-white-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    88 =>
    array (
      'id' => 'woo-product--product-rice-persian-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-rice-persian-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    89 =>
    array (
      'id' => 'woo-product--product-rishiri-kombu-100g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-rishiri-kombu-100g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-kombu',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-kombu',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-rishiri-kombu-100g',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-rishiri-kombu-100g',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    90 =>
    array (
      'id' => 'woo-product--product-tahini-500g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-tahini-500g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    91 =>
    array (
      'id' => 'woo-product--product-tilapia-fillet-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-tilapia-fillet-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    92 =>
    array (
      'id' => 'woo-product--product-tomato-1kg',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-tomato-1kg',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    93 =>
    array (
      'id' => 'woo-product--product-tomato-sauce-400g',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-tomato-sauce-400g',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
      ),
      'decision_evidence_refs' =>
      array (
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
    94 =>
    array (
      'id' => 'woo-product--product-yamaroku-tsurubishio-500ml',
      'kind' => 'woo_product_science_entity',
      'subject' =>
      array (
        'registry' => 'woocommerce',
        'entity_type' => 'product',
        'entity_id' => 'product-yamaroku-tsurubishio-500ml',
        'scope_entity_id' => '',
      ),
      'resolution_state' => 'unresolved',
      'targets' =>
      array (
      ),
      'candidates' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science',
          'entity_type' => 'ingredient',
          'entity_id' => 'ingredient-kioke-shoyu',
          'state' => 'pending_review',
          'reason_code' => 'legacy_explicit_relation_requires_review',
        ),
      ),
      'decision_evidence_refs' =>
      array (
        0 =>
        array (
          'registry' => 'culinary_science_registry',
          'record_id' => 'ingredient-kioke-shoyu',
        ),
        1 =>
        array (
          'registry' => 'live_catalog_products',
          'record_id' => 'product-yamaroku-tsurubishio-500ml',
        ),
        2 =>
        array (
          'registry' => 'live_catalog_relations',
          'record_id' => 'product-yamaroku-tsurubishio-500ml',
        ),
      ),
      'decision_note' =>
      array (
        'he' => '',
        'en' => '',
      ),
      'review' =>
      array (
        'state' => 'unreviewed',
        'reviewer_id' => '',
        'reviewed_at' => '',
        'next_review_at' => '',
      ),
      'valid_from' => '',
      'valid_to' => '',
    ),
  ),
);
