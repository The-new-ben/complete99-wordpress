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

## WordPress-native operations foundation

Release 1.21.0 begins the private application migration inside the plugin. The
Complete99 OS Today shell is an authenticated wp-admin surface governed by the
dedicated `complete99_view_operations` capability. Its read-only REST status
requires a WordPress session, the same capability and a valid WordPress REST
nonce. It never accepts an operational mutation in P1 and does not represent
legacy external records as imported.

Seven plugin-owned InnoDB tables establish durable identities for locations,
memberships, tasks, issues, commands, mutation receipts and append-only audit
events. Their schema marker and administrator capability are installed within
the platform migration boundary. Missing tables, wrong engines, missing unique
keys, capability drift or version drift fail the operations invariant and block
release stabilization. Exact legacy ChatGPT Sites defaults move to WordPress-
owned application and asset destinations, while an owner-configured canonical
HTTPS value is preserved.

Release 1.12.0 does not install or assign Complete99 worker roles. Commerce
order, refund, fulfilment and stock events use an unassigned private outbox
until a later operating decision.

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

Release 1.21.0 derives effective search state through a separate activation
policy pinned to the unchanged canonical v20 registry digest. The validated
allowlist contains exactly 18 standalone owners and yields 36 Hebrew and English
canonical sitemap URLs. The untested ichiban-dashi preparation stays noindex;
eight section entities remain owner-canonical-only; query/filter states and all
held, private or draft records remain excluded. A missing, altered, stale or
over-broad policy fails closed to zero indexable routes and fails the migration
invariant, so deployment cannot stabilize with a silently disabled overlay.

The public API is a projection, never the source registry. An entity enters that
projection only when it has reviewed bilingual content, reviewed evidence, at
least one public-safe fact, approved media, cleared rights with a SHA-256 receipt,
acceptable attribution and, for food preparations, a completed culinary test.
Public relations, parent chains, hubs, semantic links and breadcrumbs may target
only other public entities. Public taxonomy is emitted from explicit category,
attribute and tag allowlists. Prompts, cost plans and private evidence remain in
the administrator surface.

Schema v6 adds typed measurements to atomic facts. A measurement retains its
verification state, value or range, unit, assay method, specimen scope,
conditions, confidence, source reference and measurement date. Release v20
uses this model for exactly three verified `literature_context` assay ranges
from one 46-hour, three-strain *Aspergillus oryzae* study. Their parent Japanese
candidate is held without an owner publication receipt, so all three remain
private and public projection fails closed. They are literature evidence rather
than recipe, production or safety targets.

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

Release 1.17.0 uses culinary-commerce registry version 11 bound to
culinary-science registry version 17. The version change adds no product
identity or effective channel offer. The 121-identity Lebanese expansion
contains no price or retail observation. Its entities cannot become a price,
supplier, stock record, offer or import path by implication.

Release 1.18.0 uses culinary-commerce registry version 12 bound to
culinary-science registry version 18. The dependency update is versioned even
though it adds no product identity or effective channel offer. Three reviewed
Japanese records become public sections inside the existing dashi and umami
owners.

Release 1.18.1 keeps both registry versions unchanged and adds a presentation
layer over the same WooCommerce authority. The store listing state consists of
an allowlisted product type, a bounded page number and a fixed page size of
twelve. The same state drives rendering, product links, localized alternates,
canonical metadata, robots and Product schema. Filtered utility states are
`noindex,follow`; unfiltered pages keep the store's existing eligibility. No
presentation helper can write a product, price, stock value, supplier record,
order or payment setting.

Release 1.18.2 keeps that architecture and every registry, product and commerce
state unchanged. Its mobile-only presentation compacts the two store headings,
spacing and repeated supporting labels while retaining the complete copy in the
server-rendered document. The first product card must enter the 390 by 844
initial viewport, all interaction targets remain at least 44 by 44 CSS pixels,
and no desktop rule changes.

Release 1.19.0 uses culinary-commerce registry version 13 bound to
culinary-science registry version 19. The dependency adds one explicit
editorial-to-commerce edge from `ingredient-syrian-bulgur` to the existing
`product-bulgur-fine-500g` WooCommerce identity. The frozen artifact encoded the
ingredient as public/noindex, but it lacked owner approval and was never
deployed. The product remains the sole owner of Product and Offer facts,
including the live ILS 5.90 price. Current v20 holds the Syrian candidate and
emits no public navigation. Product, price, stock, supplier, cart and payment
state otherwise remain unchanged.

Release 1.20.0 uses culinary-commerce registry version 14 bound to
culinary-science schema v6 and registry version 20. It adds no product identity,
effective offer or public commercial edge. The exact 36 WooCommerce products,
20 private planning prices, prices, stock, cart and disabled payment state remain
unchanged. Eleven cross-domain Woo candidates remain private review inputs and
cannot create a Product or Offer projection.

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

Release 1.13.0 extends the private graph to 343 Entity Studio subjects: 287
science identities plus 56 product identities. Price-basis coverage remains 56 of
56: 36 unchanged public WooCommerce prices and 20 private planning prices. This
coverage is not a statement that 56 products are public or available. The 17
earlier draft offers remain inactive. The three new Syrian product identities
are private held market observations only, with no WooCommerce product code,
channel offer, stock, supplier, landed-cost or margin claim. They do not enter
the active POS projection. Only the exact explicit value `public` is eligible
for public source-market projection. Missing, malformed and unknown values
remain private. Payment remains disabled and no role is installed or assigned.

Release 1.15.0 extends Entity Studio to 521 subjects: 465 science identities
plus the same 56 product identities. Price-basis coverage remains 56 of 56: 36
unchanged public WooCommerce prices and 20 private planning prices. The 96 new
Iraqi science identities are private, `noindex`, reference-only subjects and
do not add a product code, channel offer, stock, supplier, landed-cost or margin
record. There are zero new active or draft offers. Payment remains disabled and
no role is installed or assigned.

Release 1.16.0 extends Entity Studio to 607 subjects: 551 science identities
plus the same 56 product identities. The 86 new Syrian identities are private,
`noindex` and reference-only. Four unresolved plant, preservation or identity
records remain held. None adds a product code, offer, stock, supplier,
landed-cost, margin or public route. Price-basis coverage remains 56 of 56: 36
unchanged public WooCommerce prices and 20 private planning prices. Payment
remains disabled and no role is installed or assigned.

Release 1.17.0 extends Entity Studio to 728 subjects: 672 science identities
plus the same 56 product identities. The 121 new Lebanese identities are
private, `noindex` and reference-only. Twelve unresolved evidence or handling
records remain held. None adds a product code, offer, stock, supplier,
landed-cost, margin or public route. Price-basis coverage remains 56 of 56: 36
unchanged public WooCommerce prices and 20 private planning prices. Payment
remains disabled and no role is installed or assigned.

Release 1.19.0 keeps Entity Studio at 728 subjects: the same 672 science
identities and 56 product identities. The source register grows from 370 to
374. Its frozen artifact encodes seven editorial asset records with public usage
in a dedicated science-asset collection, while the 60-entry catalog collection
and its product-image semantics remain unchanged. No owner publication receipt
was recorded and the artifact was never deployed. Current v20 holds those seven
assets with the five Japanese candidates.

Release 1.20.0 keeps Entity Studio at 728 subjects and the science registry at
672 identities. The source register grows from 374 to 375. Five Japanese koji
and shoyu identities join that dedicated collection as held editorial
candidates, bringing it to twelve assets separate from the 60 catalog assets.
Producer and retail-listing records remain private. Cross-domain v3 review
records do not become Entity Studio subjects and cannot write to their source
registries.

The generated-asset manifest is an editorial evidence registry, not an
installed-file inventory. Owner-publication approval v2 binds the exact PNG
source evidence separately from the four deployable WebP/AVIF variants and the
complete bilingual content. There is no trusted owner key or receipt, so all
twelve Syrian and Japanese candidates fail closed.

Science media export is a separate default-deny boundary. The 1.20 policy
classifies exactly 47 stems and 175 repository files: 28 public-delivery stems,
18 held repository-only stems and one approved superseded archive stem. The ZIP
allowlist contains exactly 70 delivery files. Exactly 105 files remain
repository-only: 78 held files, 24 active public PNG source-evidence files and
three archive files. The policy removes all 60 held derivatives present in the
earlier 1.20 candidate package without deleting the 78 held repository files.

Commerce status separates `registry_valid` from `commerce_ready`. A valid
private graph keeps health checks and migrations operational. It does not claim
active-offer readiness. In this release the registry is valid and culinary
commerce readiness is false because there are zero effective channel offers.

At release 1.13.0, the cumulative culinary-science registry contained 287
entities. The Syrian module contributed 196 identities, including 56 dishes, 55 ingredients, 21
regional or topic hubs, 17 techniques, 17 traditions, 15 preparations, markets,
restaurants and hospitality institutions, plus three private held market
observations. One safe consumer gateway is
projected as `noindex,follow`; the
other 195 Syrian entities remain private. Those Syria statements remain the
preserved 1.13.0 baseline.

At release 1.14.0, the cumulative registry contains 369 entities. The Syrian
module remains at 196 identities with its one `noindex,follow` gateway and 195
private identities unchanged. The Lebanese module contributes 82 identities:
one cuisine, 13 topic hubs, 27 dishes, two preparations, eight ingredients,
five techniques, nine traditions, five culinary institutions, two markets,
three restaurants, one compliance rule and six retail listings. Every Lebanese
identity has `editorial_draft`, `noindex_private`, `private_preview` and
`reference_only` boundaries. Across all cuisines, the public graph remains 23
science entities resolving through 18 canonical page owners per language.

At release 1.15.0, the cumulative registry contains 465 entities. The Iraqi
module contributes 96 private identities: one cuisine, 16 topic hubs, 32
dishes, four preparations, 12 ingredients, eight techniques, ten traditions,
five culinary institutions, three markets, two restaurants, one compliance
rule and two guides. Every Iraqi identity has `editorial_draft`,
`noindex_private`, `private_preview` and `reference_only` boundaries. The
public graph remains 23 science entities across 18 canonical page owners per
language.

At release 1.16.0, the cumulative registry contains 551 entities. The Syrian
graph grows from 196 to 282 identities through 86 new private records: 4 topic
hubs, 21 dishes, 14 ingredients, 13 techniques, 12 traditions, 7 guides, 1
preparation, 9 culinary institutions, 1 market and 4 restaurant benchmarks.
The expansion separates west, central, east and south regional evidence and
keeps Jewish, Syrian-Armenian, Assyrian, Kurdish, Druze and family records
within their documented scope. The Lebanese graph remains at 82 identities and
the Iraqi graph remains at 96. The public graph remains exactly 23 science
entities across 18 canonical page owners per language.

At release 1.17.0, the cumulative registry contains 672 entities. The Lebanese
graph grows from 82 to 203 identities through 121 new private records: 12 topic
hubs, 31 dishes, 15 ingredients, 3 molecules, 4 reactions, 14 techniques, 6
equipment records, 18 traditions, 4 markets, 10 culinary institutions, 3
restaurant benchmarks and 1 guide. The expansion separates coastal, northern,
mountain, Bekaa, southern and community evidence and keeps Jewish, Druze,
Christian, Muslim, Armenian and Palestinian records within their documented
scope. The Syrian graph remains at 282 identities and the Iraqi graph remains
at 96. One reviewed Lebanese cuisine root is promoted as a noindex public
gateway, while all 121 new records and the other 81 foundation records remain
private. The public graph therefore contains 24 science entities across 19
canonical page owners per language.

At release 1.18.0, the cumulative registry remains at 672 entities and Entity
Studio remains at 728 subjects. Controlled dashi extraction, L-glutamate and
inosine monophosphate become reviewed noindex public sections inside the
existing ichiban-dashi and umami page owners. The public graph therefore grows
to 27 science entities while remaining at 19 canonical page owners per language
and 38 bilingual routes. The 84-identity Japanese cluster contains 24 public and
60 private records. The WooCommerce catalog remains exactly 36 products,
and no product, price, stock, supplier, bundle or payment state is added.

Release 1.18.1 preserves those exact registry and public graph counts. It
changes only how the 36 WooCommerce products are discovered and rendered on the
public shelf: three pages of twelve products, server-rendered filters, stable
page-aware product anchors and progressively disclosed specifications.

Release 1.18.2 preserves the same counts, routes and shelf behavior. Its only
runtime change is mobile store spacing.

Release 1.19.0 keeps the cumulative registry at 672 identities and Entity
Studio at 728 subjects. Its frozen artifact encodes seven Syrian records as
public/noindex discoveries, producing 34 entities, 26 standalone page owners
per language, 52 bilingual routes, and a Syrian split of 8 public/noindex to 274
private records. Zero science entities are indexable and the science sitemap is
empty. No owner receipt existed and the artifact was never deployed, so the
live public graph remained exactly 27/19/38. Current v20 holds all seven. The
other cuisine totals and the exact 36-product WooCommerce catalog remain
unchanged.

Release 1.20.0 kept the cumulative registry at 672 identities and Entity
Studio at 728 subjects. Five Japanese identities become held editorial
candidates. Four are proposed standalone page owners and the
enzymatic-hydrolysis reaction is a proposed section of the koji-hydrolysis
guide. Its public graph remained exactly 27 entities, 19 standalone page owners
per language and 38 bilingual routes. The Japanese cluster remains at 24 public
and 60 private records. All 27 public entities remained outside the sitemap and
zero science entities were indexable before the separate 1.21 activation.

Release 1.21.0 leaves those 27 public entities, 19 raw standalone owners and the
entire v20 registry unchanged. The digest-pinned overlay activates exactly 18
qualified standalone owners and 36 canonical routes. The nineteenth owner,
`preparation-ichiban-dashi`, remains noindex because its culinary test is not
verified.

## Syria regional and community boundary

The Syrian graph is organized by source-scoped regions and communities rather
than a single undifferentiated national list. Aleppo, Damascus, Homs, Hama,
Idlib, Qadmus, Kassab, Baniyas, Jableh, Qamishli, Deir ez-Zor, Al-Bukamal,
Palmyra, Suwayda and Hauran retain separate parent and evidence paths. A family
testimony is evidence for that family record, not a universal rule for a city
or community.

Jewish foodways from Aleppo, Damascus and diaspora archives are represented as
separate family and community records alongside Syrian-Armenian, Assyrian,
Kurdish, Druze and other regional records. No community record replaces the
wider Syrian account or receives an exclusive-origin claim.

Institutions, archives, markets and restaurants are contextual entities and
external benchmarks. They do not imply partnership, endorsement, current
operation, recipe testing or image rights. Four unresolved records remain held
until botanical identity, toxicity reduction, preservation controls or exact
product identity can be verified. None of the 86 new records enters the public
API, public sitemap, WooCommerce catalog, POS catalog or ordering flow.

The frozen 1.19.0 artifact encodes a narrow public/noindex path through the
already-modeled Syrian graph: Aleppo, the Aleppine kibbeh family, Syrian bulgur,
cooked lamb and beef, bulgur hydration, fully cooked kibbeh methods and Aleppan
Jewish foodways. No owner receipt existed and the artifact was never deployed,
so only the pre-existing cuisine root remained live. Current v20 holds all seven. The
pending `dish-kibbeh-meshwiyyeh` record remains private with no Recipe schema.
Candidate cooking content prohibits raw kibbeh and keeps ground-meat guidance
within a fully cooked boundary. Source-scoped Jewish foodways sit alongside the
broader regional account and do not replace it.

## Iraq research and trade boundary

The Iraqi foundation is an editorial research graph, not a commerce or
procurement source. It keeps Baghdad, Mosul and Ninewa, Basra and the Shatt
al-Arab, the Middle Euphrates, the southern marshes, Iraqi Kurdistan, Kirkuk and
Diyala as distinct source-scoped paths. Iraqi Jewish, Kurdish, Marsh Arab and
family records remain within their documented community scope. Shared regional
dish families are linked for comparison without being merged or assigned an
exclusive national origin.

Every Iraqi identity references the central private
`compliance-iraq-trade-israel-2026` rule. The rule records the current
fail-closed trade boundary and permits no supplier contact, sample, payment,
third-country workaround, availability claim or ordering path without current
written official authorization. It is an operating control, not legal advice.

## Lebanon research and trade boundary

The Lebanese foundation is an editorial research graph, not a commerce or
procurement source. None of its 82 identities can enter the public API, create a
public page, appear in a sitemap, resolve to a WooCommerce product, or create a
cross-sell, up-sell, supplier, order or stock path. Its six retail listings are
dated market observations only and do not establish availability in Israel.

Every Lebanese identity references the central private
`compliance-lebanon-trade-israel-2026` rule. That rule records Israel Ministry
of Economy and Industry Director-General Instruction 2.4 dated 8 March 2026,
which lists Lebanon within the broad prohibition on direct or indirect trade
with enemy states. The system therefore blocks supplier contact, samples,
payments, third-party purchase routing and representations of delivery from
Lebanon unless written legal and official authorization is obtained first. The
record is a fail-closed operating control, not legal advice or evidence that an
exception has been approved.

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

Database rollback snapshots use an exact schema boundary. Historical snapshots
without operations tables retain manifest v1. Current snapshots use manifest v2
and add one component for the seven private `c99_ops_*` tables plus their schema
marker in the option journal. Candidate-created first-install tables are moved
atomically to deterministic quarantine names before the core WordPress rollback
transaction. They are dropped only after exact baseline readback. A table that
existed at baseline must remain unchanged, and any ambiguous retry or quarantine
residue blocks finalization and the next deployment.

The 1.21 artifact may proceed only through protected `main`, a green required CI
result and this controlled workflow. It activates only the exact reviewed
search-policy allowlist; it does not publish held Science content. A later valid
owner receipt requires a newly reviewed and rebuilt artifact with all four exact
approved delivery variants. Nothing in the source or package contract is
evidence that a deployment or live publication occurred.

## Cross-domain culinary bindings

The consumer menu, dish component trees, culinary-science registry and Woo
catalog remain separate authorities. The v3 binding registry pins Culinary
Science schema `complete99-culinary-science-registry/v6` and version
`culinary-science-2026.08.08.v20`; it may connect records, but it cannot rename,
merge or replace a source record. Its contract preserves exactly 12 menu
dishes, 47 dish-scoped component subjects and 36 Woo product subjects. The
binding status identifies itself as
`complete99-cross-domain-binding-registry/v3` and
`complete99-cross-domain-bindings-2026.08.08.v3`. The
component census is the stable union,
within each dish, of recursively declared component-tree codes and explicit
`relations.ingredient_codes`; the same global code may therefore appear under
more than one dish scope.

Every binding record is one of `linked`, `no_match` or `unresolved`. The v3 seed
contains exactly 95 unresolved records: 12 dishes, 47 dish-scoped components and
36 Woo products. Eleven reciprocal Woo candidates remain private review inputs
and are created
only from an explicit source edge. Names, slugs and labels never generate a
candidate, and no relation may be inferred transitively through another
registry. Reviewer identities, decision notes, evidence references and rejected
candidates stay on capability-gated editorial surfaces.

The private decision overlay uses schema
`complete99-cross-domain-binding-decision-overlay/v1` and version
`complete99-cross-domain-binding-decisions-2026.08.08.v1`. Its current valid
snapshot contains zero decisions and zero recognized reviewer authorities, so
all eleven candidates remain pending and no public or commerce projection can
be created.

The registry byte-binds six canonical logical payloads from the four source
systems. Product seed identity, live product policy and the live-catalog
relation map are separate contracts; the last supplies the reciprocal edge. A
missing record, extra record, stale source digest, duplicate subject, invalid
target, vocabulary change or incomplete review makes the binding index invalid.
Invalid or unresolved data yields five literally empty verified or public
indexes while the independently valid
menu, museum and store continue to render from their own authorities. Public
navigation is possible only for a verified `linked` record whose projection is
explicitly public and whose science target is independently approved for public
discovery. This fail-closed layer does not publish a dish, approve a product,
enable checkout or create a WooCommerce identity.
