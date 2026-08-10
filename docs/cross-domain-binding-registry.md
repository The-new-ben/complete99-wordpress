# Complete99 cross-domain binding registry

Status: private v3 architecture. This registry is not a publication, product,
price, stock, checkout or commerce approval surface.

## Purpose and boundary

Complete99 has four independently valid identity systems: 12 consumer-menu
dishes, 12 dish trees and their dish-scoped components, the Culinary Science
ontology, and 36 managed Woo product codes. The binding system can connect
exact identities across those systems without changing any source identity.

It never compares labels, translations or slugs across domains; never performs
fuzzy matching; and never infers a relationship through an intermediate entity.
A reciprocal Woo/science declaration creates private review evidence only. It
does not create or approve a binding.

## Version 3 source census

Version 3 is bound to Culinary Science schema
`complete99-culinary-science-registry/v6`, version
`culinary-science-2026.08.08.v20`. Its final canonical loaded-value JSON is
exactly 9,820,452 bytes with SHA-256
`677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54`.
The generated registry, runtime class and tests bind this exact payload as one
release unit.

The immutable seed contains exactly 95 unresolved subjects:

- 12 `menu_dish_science_dish` subjects;
- 47 dish-scoped `menu_component_science_entity` subjects;
- 36 `woo_product_science_entity` subjects.

For each dish, component subjects are the stable union of every recursive
`component_tree.children[].code` and every explicit
`relations.ingredient_codes[]` value. Duplicate codes are removed only inside
that dish. The same code under two dishes remains two distinct review decisions.

All 12 dishes and all 47 scoped components remain unresolved. No dish or
component candidate is generated because no exact, reciprocal,
machine-readable source edge exists. All 36 Woo subjects also remain unresolved.
Eleven Woo records contain one private `pending_review` candidate each because
the science entity declares an exact Woo product code and the live relation map
points that same product back to that same science entity.

## Candidate triage, not decisions

Ten reciprocal pairs are technically ready to be placed before an authorized
human reviewer. “Ready” means only that the exact reciprocal identifiers,
compatible entity type and seed evidence are present; every pair is still
unresolved and non-projecting:

| Woo subject | Science candidate |
| --- | --- |
| `product-fresh-japanese-wasabi-250g` | `ingredient-fresh-wasabi` |
| `product-fresh-wasabi-50-60g` | `ingredient-fresh-dutch-wasabi` |
| `product-hagane-zame-large` | `equipment-wasabi-grater` |
| `product-hishiroku-chouhaku-kin-20g` | `ingredient-koji-starter-culture` |
| `product-hishiroku-dried-rice-koji-500g` | `ingredient-kome-koji` |
| `product-honkarebushi-200g` | `ingredient-katsuobushi` |
| `product-kito-yuzu-juice-100ml` | `ingredient-kito-yuzu` |
| `product-koshihikari-uozu-2kg` | `ingredient-koshihikari-rice` |
| `product-rishiri-kombu-100g` | `ingredient-kombu` |
| `product-yamaroku-tsurubishio-500ml` | `ingredient-kioke-shoyu` |

The eleventh pair is intentionally marked `scope_mismatch`:
`product-bulgur-fine-500g` is a specific fine-bulgur product while
`ingredient-syrian-bulgur` is a broader Syrian-bulgur editorial identity. It
must not be promoted automatically as `retail_instance_of`. A future authorized
review must either use an explicit `reference_only` decision supported by the
evidence, or reconcile the science identity to a product-compatible exact
scope. Until then it remains a private pending candidate.

## Immutable seed and separate decision overlay

`data/cross-domain-bindings.php` is a deterministic decision-free seed. Every
record has `resolution_state=unresolved`, no target, empty bilingual decision
notes, an unreviewed receipt and empty validity dates. The generator must
reproduce all 95 records and all 11 pending candidates exactly.

`data/cross-domain-binding-decisions.php` is a separate strict overlay. The
current overlay contains exactly zero decisions because no recognized named
binding reviewer authority has been checked in. Empty reviewer fields must not
be replaced with placeholders.

The overlay is bound to:

- the canonical SHA-256 of the complete seed;
- the seed schema/version, record count and ordered-record-ID SHA-256;
- the canonical SHA-256 of all six seed input contracts;
- the canonical SHA-256 of the checked-in recognized-reviewer authority map;
- the canonical SHA-256 and explicit count of its decision list;
- for each future decision, `seed_record_sha256` over the complete original
  record, including its exact candidates and seed evidence references.

The release also pins the canonical overlay SHA-256 in runtime code. A changed
overlay therefore requires a reviewed code change and a regenerated release;
editing only the data file fails closed.

These hashes prove integrity and exact input binding. A self-digest is not identity authentication.
A future nonempty overlay also requires its
`reviewer_id` to resolve to a checked-in recognized authority with an evidence
receipt and validity window. The authority map is empty in v3, so any nonempty
decision is currently invalid even if its shape and self-digests are otherwise
correct.

## Decision contract

A future decision may resolve one exact seed record only as `linked` or
`no_match`. It must provide every required field, an exact seed-record digest,
complete candidate disposition, nonempty evidence references, substantive
Hebrew and English notes, a `verified` review with a recognized reviewer ID,
review and next-review dates, and a validity start date.

For a Woo candidate, the decision must explicitly move the exact seed candidate
to the target or retain it as `rejected`; reciprocity never means acceptance.
Targets and rejected candidates cannot conflict. Missing fields, unknown or
duplicate record IDs, extra envelope keys, omitted source inputs, stale digests,
candidate/evidence drift, unrecognized reviewers and contradictory targets all
invalidate the complete merged registry.

## Public fail-closed rule

A public index entry may exist only after the seed, overlay, sources, merged
records, evidence, recognized reviewer authority, relation type, projection
scope and target science publication state all validate. Otherwise all five
indexes are literal empty arrays.

In this release there are zero decisions, zero recognized reviewer authorities,
95 unresolved records and zero public projections. Invalid binding data does
not take down the independently valid menu, museum or held store; it only
removes cross-domain projections. The binding system cannot create Woo products,
approve prices or stock, publish science pages, enable payment methods or make
checkout ready.

Offline verification:

```text
php scripts/generate-cross-domain-binding-seed.php --check
pytest -q tests/test_cross_domain_binding_seed_contracts.py tests/test_cross_domain_binding_validator_contracts.py
```
