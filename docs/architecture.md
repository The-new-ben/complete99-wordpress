# Architecture

## Product boundary

Complete99 has one authoritative WordPress and WooCommerce platform with a
strict public and private surface split.

1. **Public WordPress site**: a bilingual culinary consumer website for food,
   dishes, ingredients, traditions, practical guides, the restaurant story,
   contact information, ordering continuation and a curated pantry.
2. **Private WordPress control plane**: WooCommerce owns catalog identity, stock,
   carts and orders. The plugin owns the versioned culinary graph, evidence,
   publication gates, market observations, supplier and landed-cost models,
   margin scenarios, bundles and connector contracts. These surfaces require an
   administrator capability or a scoped signed request and are never public SEO
   targets.
3. **Connected clients and adapters**: the touch kiosk, POS, mobile application,
   kitchen tools, operations screens and campaign services consume versioned
   WordPress projections or submit narrowly scoped mutations. They may cache a
   projection for resilience, but they are not an independent catalog or stock
   source of truth. A vendor adapter is bound only after its exact contract,
   authentication, idempotency and retry behavior are verified.
4. **Consumer projection**: WordPress publishes only explicitly approved public
   fields. It rejects stale timestamps, reused nonces, invalid signatures,
   oversized data and unknown fields. Private costs, workflow state, image
   prompts and compliance review remain outside that projection.

Large raw media streams may use purpose-built storage, and credentials stay in
server-side secret stores. WordPress retains the canonical entity identity,
evidence reference, authorization state and publication decision.

Release 1.11.0 installs infrastructure only and does not install or assign
Complete99 worker roles. Commerce order, refund, fulfilment and stock events use
an unassigned private outbox until a later operating decision.

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
| `c99_entity_dossier` | Private commercial decision overlay with retained revisions, never a public entity or offer |
| WooCommerce `product` | A real purchasable pantry item after all commerce gates pass |

Legacy taxonomies for institutional and operating records remain private. Public
taxonomy and archive surfaces are limited to substantive consumer culinary
content. Thin archives are not part of the launch surface.

The signed sync boundary still accepts a `branches` array so the private system
does not need an immediate schema change. WordPress counts those records toward
the payload limit, then discards them. It does not store or publish branch data
until a verified consumer location contract is approved.

## Culinary knowledge and SEO graph

The culinary-science registry models every record as an independently addressed
entity. Cuisine, category, dish, preparation, ingredient, molecule, technique,
equipment, institution, market, supplier, restaurant, chef, source and market
observation can therefore be expanded without copying a whole page template.
Each entity carries bilingual identity, five evidence profiles, atomic facts,
relations, commerce intent, visual instructions, compliance notes, review state
and publication state.

SEO ownership is declared before publication. A cuisine pillar owns its topic
cluster. Standalone spokes own one canonical path and section spokes resolve to
one declared owner. Query variants, multilingual term variants, semantic entity
links, protected exclusions, expected children, breadcrumbs and contextual link
plans are validated as one graph. Duplicate query owners, broken parents,
cycles, unresolved links and conflicting canonical owners fail the registry.

The public API is a projection, never the source registry. An entity enters that
projection only when it has reviewed bilingual content, reviewed evidence, at
least one public-safe fact, approved media, cleared rights with a SHA-256 receipt,
acceptable attribution and, for food preparations, a completed culinary test.
Public relations, parent chains, hubs, semantic links and breadcrumbs may target
only other public entities. Public taxonomy is emitted from explicit category,
attribute and tag allowlists. Prompts, cost plans and private evidence remain in
the administrator surface.

## Commerce and monetization graph

Knowledge does not become a sellable item by implication. The commerce registry
keeps these identities and decisions separate:

`knowledge entity -> product -> variant -> SKU -> supplier offer -> landed cost -> margin scenario -> channel offer`

Market observations remain dated evidence and cannot become Israeli selling
prices by themselves. Approved offers require a verified SKU, cleared compliance,
retained source evidence, a supplier quote, an exact currency conversion, fully
evidenced cost lines, an approved tax state, an exact gross-to-net bridge and a
computed contribution margin. Cross-sell, upsell and bundle edges are separate
records with their own evidence and lifecycle state.

Money is stored in integer minor units. Decimal quantities and exchange rates are
canonical strings with explicit direction and rounding. This avoids floating
point drift and lets another country add its own market, currency, seller, tax
zone, locale, channel and supplier terms without changing the product identity.
Only an effective active offer can enter a channel projection, and the POS route
then rechecks the live WooCommerce SKU, publication status, currency, price,
stock, purchasability and image before returning it.

The bundled Japanese pilot is the migration and contract seed for this model.
International expansion must preserve the same versioned schemas, stable IDs,
digests and validation gates when records move into partitioned database storage
and persistent caches. WordPress and WooCommerce remain the authority through
that storage transition.

## Private Entity Studio

Release 1.10.0 adds Entity Studio inside WordPress as a copy-on-write commercial
overlay. It joins stable science, catalog and commerce identities without
rewriting their checked-in facts. Each dossier keeps pricing applicability,
commercial role, offer type, market, channel, currency, planned price, evidence
references, proposed cross-sells and up-sells, bilingual value propositions,
bilingual price rationale and a private note.

The post type is private, non-queryable, excluded from search and absent from
the public WordPress REST surface. Access requires `manage_options`; no new role
is created. A subject-scoped source digest and expected revision reject stale
writes. Source changes require an explicit rebase into draft. Post, metadata and
read-back verification run in one database transaction under a site-scoped
write lock. Rollback clears post and metadata caches. Revision history binds
payload, stable identity, record kind, workflow transition and prior digests.
Removed-source dossiers remain available through a direct private audit read and
an administrator orphan list.

The private REST collection is paged and capped at 100 subjects per response.
Observation identifiers fail closed on cross-registry collisions. Entity Studio
cannot create or update a WooCommerce product, price, stock quantity, cart,
order, public page, sitemap entry or active channel offer.

Release 1.11.0 extends the private graph to 144 Entity Studio subjects and 53
product identities. Price-basis coverage is 53 of 53: 36 unchanged public
WooCommerce prices and 17 private draft planning prices. This coverage is not a
statement that 53 products are public or available. The 17 plans create zero
active offers, carry no verified supplier or landed-cost claim and do not enter
the active POS projection. The 12 newest candidates have planning stock zero,
no WooCommerce product code and `public_market_projection=held`. Only the exact
explicit value `public` is eligible for public source-market projection. Missing,
malformed and unknown values remain private. Payment remains disabled.

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
