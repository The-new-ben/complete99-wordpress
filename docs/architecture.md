# Architecture

## Product boundary

Complete99 has a strict public and private split.

1. **Public WordPress site**: a bilingual culinary consumer website for food,
   dishes, ingredients, traditions, practical guides, the restaurant story,
   contact information, ordering continuation and a held pantry.
2. **Private Complete99 OS on Sites**: operating infrastructure for inventory,
   orders, suppliers, costs, tasks, campaigns, workers and future device
   adapters. It is not marketed through the consumer website.
3. **Signed public read-model bridge**: the private system may send only
   explicitly allowed consumer-safe dish fields to WordPress. The payload is
   signed; WordPress rejects stale timestamps, reused nonces, invalid signatures,
   oversized data and unknown fields. Campaigns are rejected because they remain
   private. WordPress keeps publication, verification and media-rights evidence
   internally, then returns only a separate consumer-safe field projection from
   the public catalog endpoint.

Raw camera streams, telemetry, employee data, supplier costs, private production
specifications, tokens and social credentials do not belong in WordPress.

Release 1.3.1 does not install or assign Complete99 worker roles. The role
definitions remain dormant code. Commerce order, refund, fulfilment and stock
events use an unassigned private outbox until a later operating decision.

## WordPress content model

| Content type | Purpose |
| --- | --- |
| `c99_service` | Private legacy service records, removed from public routes |
| `c99_industry` | Private legacy sector records, removed from public routes |
| `c99_platform_feature` | Private operating-system records |
| `c99_dish` | Public dish research and tested recipe when approved |
| `c99_ingredient` | Ingredient source and use records |
| `c99_location` | Private until a consumer location contract is approved |
| `c99_guide` | Research-backed knowledge |
| `c99_case_study` | Private unless a later consumer purpose is approved |
| `c99_team_member` | Private unless a consumer-facing profile is permissioned |
| `c99_product_plan` | Private product preparation record, never a storefront product |
| WooCommerce `product` | A real purchasable pantry item after all commerce gates pass |

Legacy taxonomies for institutional and operating records remain private. Public
taxonomy and archive surfaces are limited to substantive consumer culinary
content. Thin archives are not part of the launch surface.

The signed sync boundary still accepts a `branches` array so the private system
does not need an immediate schema change. WordPress counts those records toward
the payload limit, then discards them. It does not store or publish branch data
until a verified consumer location contract is approved.

## Identity and language

Hebrew is the default root. English pages live under `/en/`. Consumer pages have
parallel deterministic paths such as:

- `/dishes/`
- `/en/dishes/`

Every pair carries a translation key, self-canonical, reciprocal `hreflang` and an
`x-default` pointing to Hebrew. No automatic geo/language redirect is used.

## Commerce containment and acceptance

WooCommerce owns products, carts, checkout, payment, refunds and stock. The
consumer pantry owns product discovery. Native shop, product and taxonomy pages
redirect to that curated pantry instead of creating a second catalogue.

The public Store API is not exposed, even after launch, because acceptance
covers the classic checkout path. Anonymous WordPress REST product types,
product taxonomies,
product-only core search and product oEmbed are also blocked. Ordinary public
WordPress search and REST search remove product and product-variation post
types. Administrators and WooCommerce managers retain the core product REST
access required for private administration. Anonymous core media REST is also
closed. Every approved product image must carry an explicit public-safe review,
and its file and attachment metadata digests are bound into acceptance.

Checkout acceptance uses `complete99-commerce-acceptance/v3`. It stores
separate Hebrew and English language entries from two different real order IDs.
Each entry is revalidated against its order, current product facts and a
configuration digest. The digest covers the Complete99 and WooCommerce
versions, order language, the full approved catalogue, localized page and
policy-copy hashes, tax configuration, live gateway state and settings digests,
and shipping method, zone location, global setting and instance-setting
digests. It also binds every material WooCommerce option, product post and term
fact, and the bytes and metadata of primary and gallery images.

Stock acceptance is line exact and order correlated. Each order line stores the
identifier of its own `inventory_order_stock_reduced` event, the line and
product identity, ordered quantity, and the stock values before and after
reduction. When WooCommerce fulfilments are enabled, several fulfilled records
may cover one order, but their quantities must sum to the complete quantity of
every line without omission or over-coverage. Every accepted stock and
fulfilment event is tied to the same order.

## Commerce state and operational handoff

The launch endpoint is a serialized state machine under a site-scoped advisory
lock. Opening first purges caches, evaluates staged readiness with the intended
enablement, pantry index state and customer-continuity marker, and verifies the
launch audit and both pantry page records. Only then does it commit the enabled
and continuity options. Committed readiness, cache invalidation and
customer-continuity state are read back. A failed stage or commit restores the
held snapshot. Launch audit version 3 stores and hashes the staged readiness,
acceptance receipt, legal receipt and exact bilingual pantry page identities.
Deleting, changing or mismatching that audit makes readiness fail closed.

Acceptance preview and acceptance recording use the same launch lock and always
commit a held, cache-verified state. Passing both languages never opens the
pantry. Closing writes the disabled option and held bilingual page state before
cache work. A cache failure reports manual recovery but does not reopen
checkout. Home, pantry, transaction and public store-status responses are
always no-store.

The private outbox has three bounded stores:

- a 500-event pending queue under the primary site-scoped advisory lock;
- a 500-record durable failure journal under its own advisory lock;
- a 5000-entry acknowledgement audit written before pending events are removed.

Error state is kept per code, so recovery of one path cannot hide another
unresolved fault. Audit compaction removes expired and then oldest unprotected
entries, while preserving every event identifier referenced by active checkout
acceptance. If the failure journal, audit, lock, cache or readback cannot be
verified, readiness fails closed. Each pending event stores an event version and
canonical payload digest. Its identifier is recomputed during every read.
Acknowledgement rows retain the same version and digest, so their identifiers
can also be recomputed. Invalid payloads, malformed timestamps or duplicate
identifiers are treated as corruption and are never rewritten from a filtered
queue.

## Idempotent seeding

Each seed stores a key, seed version and SHA-256 of title, excerpt and content.
On upgrade:

- missing records are created;
- an untouched prior seed may receive a reviewed seed update;
- an editor-modified record is preserved;
- deletion is never automatic.

The six dish records start as drafts with `verification_required`. They are
deliberately excluded from public Recipe schema until review is complete.

### One-time consumer audience reset

Release 1.3.1 introduces the durable public audience marker
`culinary_consumer_v1`. It applies only to the bilingual managed page seeds for:

- home;
- about;
- contact;
- dishes;
- ingredients;
- traditions;
- knowledge;
- store;
- privacy;
- terms;
- accessibility.

If one of those records does not contain the exact
`_complete99_public_audience=culinary_consumer_v1` marker, the migration replaces
its managed title, excerpt, content, slug, parent and required publication state
with the reviewed consumer seed. This exceptional reset also applies when the
old page was editor-modified. Its purpose is to remove the prior public
institutional audience and held external-ordering policy from the consumer
website in one controlled migration.

After the marker is stored, normal seed provenance resumes. A later migration
updates an untouched seed, preserves an editor-modified record and never deletes
the page. The reset is therefore one time per managed record while its audience
marker remains intact. Removing or changing the marker deliberately makes that
record eligible for the reset again.

Before deploying 1.3.1, capture the prior plugin directory and scoped database
state. The production workflow must exercise a real rollback to the prior
plugin and page state, verify it independently, then redeploy the identical
1.3.1 artifact. Migration acceptance verifies the audience marker, content
provenance, required private status of legacy records and database version.
Editorial changes that still belong on the consumer site can be reviewed and
reapplied only after the reset has completed.

## Deployment trust boundaries

Reviewed source -> deterministic ZIP and digest -> GitHub production environment
-> WordPress Application Password -> temporary Code Snippets route -> preflight
-> digest-verified upload -> isolated backup -> overwrite install -> fresh-request
migration stabilization -> independent health check -> finalize -> snippet
deletion -> route-404 proof.

The plugin also vendors Plugin Update Checker 5.6 and reads the versioned package
URL from `plugin-dist/complete99-platform.json`. That is a wp-admin fallback; it
does not replace the reviewed, digest-verified deployment path above. Artifact
integrity metadata is isolated in `complete99-platform-integrity.json`.

The package is uploaded directly by the authenticated workflow. No mutable
public raw-branch ZIP is used.
