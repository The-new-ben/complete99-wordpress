# Consumer site and commerce runbook

Last reviewed: 2026-08-07
Release target: Complete99 Platform 1.14.0

## Current 1.14.0 Lebanese cuisine foundation boundary

Release 1.14.0 expands the culinary-science registry from 287 to 369 entities
and Entity Studio from 343 to 425 subjects: 369 science identities plus the
same 56 product identities. The registry contracts are
`culinary-science-2026.08.07.v14` and
`culinary-commerce-2026.08.07.v8`. The public boundary remains exactly 23
science entities across 18 canonical page owners per language, and the public
WooCommerce catalog remains exactly 36 owner-authorized products.

The Lebanese foundation contains exactly 82 identities:

- 1 cuisine;
- 13 regional or topic hubs;
- 27 dishes;
- 2 preparations;
- 8 ingredients;
- 5 techniques;
- 9 traditions;
- 5 culinary institutions;
- 2 markets;
- 3 restaurants;
- 1 trade-compliance rule;
- 6 retail listings.

Every Lebanese identity is an `editorial_draft` with `noindex_private`,
`private_preview`, private-route and `reference_only` state. None has a public
page, public API row, search-index permission, public taxonomy projection,
WooCommerce product code, public offer, stock, supplier claim, cross-sell,
up-sell or POS row. Visual assets remain in rights review. The tranche adds no
product identity or private planning-price identity, so price-basis coverage
remains 56 of 56: 36 live WooCommerce prices and 20 private planning prices.

The six 2026-08-07 observations are non-comparable external benchmarks only:

- Mymoune pomegranate molasses, 250 ml, USD 11.49 at Spinneys Lebanon;
- Mymoune zaatar, 200 g, USD 8.49 at Spinneys Lebanon;
- Terroirs du Liban premium zaatar, 70 g, EUR 7.82 in its European store;
- Terroirs du Liban freekeh, 500 g, EUR 15.20 in its European store;
- Pereg zaatar baladi, ILS 88 per kilogram, as an Israeli comparison;
- organic pomegranate concentrate, 280 g, ILS 25.90 at Nitzat Haduvdevan, as
  an Israeli comparison distinct from pomegranate molasses.

Tax and shipping status remain unknown where the source does not establish
them. The observations are not Complete99 prices, offers, stock, supplier
terms, market averages, landed costs or evidence of Israeli availability.
Freekeh remains distinct from bulgur, concentrate remains distinct from
molasses, and a local comparison product is not called Lebanese without
origin-label evidence.

Every Lebanese record references the central Israel-Lebanon trade-compliance
rule based on Director-General Instruction 2.4 dated 2026-03-08. Direct or
indirect trade with Lebanon, supplier contact, samples, payment, third-party
routing and represented delivery remain blocked without written legal and
official authorization. The records are editorial sources and benchmarks, not
a purchasing workflow.

Shared Levantine families are connected for comparison without merging
identity or assigning exclusive origin. This applies to sayadiyah, samkeh
harra, kibbeh summakiyeh, mujaddara, bulgur, kishk, pomegranate molasses, sumac
and olive oil. Family and occasion evidence remains scoped to the named family,
community, place or ritual. Lebanese Jewish records do not turn shared dishes
into Jewish inventions, and Palestinian foodways in Lebanon are not reassigned
Lebanese origin or treated as commercial leads.

Food-safety records fail closed. Kibbeh nayyeh has no recipe, preparation
instructions or consumption recommendation. Kishk, ambarees, traditional
labneh and qawarma records require measured process controls such as pH, water
activity, microbiology, temperature, refrigeration, packaging, traceability or
HACCP review before any operational use. The Aley and Chouf wild-plant record
is bounded research, not permission to forage, self-identify or consume an
unverified plant.

## Historical 1.13.0 Syrian regional-depth boundary

Release 1.13.0 expands the science registry to 287 entities and Entity Studio
to 343 subjects: 287 science identities plus 56 product identities. The Syrian
module contains 196 identities, including 56 dishes, 55 ingredients, 21
regional or topic hubs, 17 techniques, 17 traditions, 15 preparations, markets,
restaurants and hospitality institutions, plus three private held market
observations. One safe Syrian consumer
gateway is projected as
`noindex,follow`; the other 195 Syrian entities remain private. The cumulative
public science projection contains 23 entities across 18 canonical page owners
per language.

Price-basis coverage is 56 of 56: 36 unchanged live WooCommerce prices and 20
private planning prices. The three Syrian product identities add private market
observations only. They have no WooCommerce product code, channel offer, stock,
supplier, landed-cost or margin claim. Release 1.13.0 creates no new public or
private WooCommerce offer, activates no payment gateway, installs no role and
assigns no worker. The 36-product public catalog, live stock and active POS
projection remain unchanged. Public Japanese science wording now answers what a
visitor can eat, cook, buy and learn instead of exposing internal registry or
construction language.

## Previous 1.11.0 Japanese premium-market boundary

Release 1.11.0 adds 12 private source-market candidates, eight knowledge
subjects, five draft bundles and nine draft merchandising relationships. The
commerce graph now contains 22 products, 22 variants, 22 SKUs, 23 evidence
artifacts, 22 market observations, 17 draft offers, eight bundles and 14
merchandising relationships. Entity Studio resolves 144 subjects and 53 product
identities with 36 live prices and 17 private planning prices.

All 12 new candidates are `research_candidate`, use planning stock zero, have
no WooCommerce product code and have no active channel offer. Their private
source-market projection is `held`. The projection layer accepts only the exact
explicit value `public`; missing, malformed or unknown values remain private.
The 36-product public catalog, public routes, live stock, POS projection and
disabled payment state remain unchanged.

## Current 1.10.0 private planning boundary

Release 1.10.0 adds Entity Studio under WordPress Tools for administrators. It
is private, absent from the public REST API, search, sitemap and public route
ownership, and creates no worker role. Its private REST surface requires
`manage_options` and is available at
`/wp-json/complete99/v1/editorial/entity-studio`.

The Studio presents one modular dossier contract over the culinary-science,
authorized catalog and commerce registries. It keeps current WooCommerce price
and stock authority separate from planning values. Each save uses an expected
revision and subject-scoped source digest, preserves review history, and fails
closed on stale, corrupt or mismatched records.

Price identity coverage is 41 of 41 product identities. Thirty-six are the
unchanged live public catalog. Five are private draft planning offers:

| Private planning identity | Planned price | State |
| --- | ---: | --- |
| Honkarebushi belly, 200 g | ILS 219 | Draft only |
| Fukumitsuya hon mirin, 3 years, 720 ml | ILS 249 | Draft only |
| Fukumitsuya hon mirin, 10 years, 720 ml | ILS 349 | Draft only |
| Kito yuzu juice, 720 ml | ILS 199 | Draft only |
| Umezawa hangiri, 36 cm | ILS 649 | Draft only |

There are zero new active offers. These values do not create WooCommerce
products or stock, do not enter the active POS projection, and do not represent
verified supplier cost, landed cost, gross margin or current availability.
Payment remains disabled. Release 1.10.0 changes no public UI or public route.

## Current 1.9.0 catalog boundary

Release 1.9.0 publishes exactly 36 WooCommerce offers and expands the modular
Japanese Foundations Lab over the reviewed public science graph. The four new
offers are Uozu Koshihikari rice, Hishiroku dried rice koji, Hishiroku
Chouhaku-kin starter culture and one 50 to 60 gram Dutch-grown fresh wasabi
rhizome. The Lab remains noindex and does not change the canonical owner or
commercial state of any member entity.

Each new offer has current dated market evidence, a documented Bank of Israel
conversion when needed, an owner-authorized opening price, opening stock 1, no
backorders and one exact public image. Supplier cost, landed cost and gross
margin are not published or inferred. They remain empty until invoice, freight,
tax and handling evidence exists. Payment remains disabled.

Release 1.6.1 preserves the 1.6.0 catalog and adds the strict 44 by 44
CSS-pixel target to the consumer breadcrumb and live cart-status links.

Release 1.6.0 extends the catalog with fresh Japanese wasabi and a professional
stainless-steel wasabi grater. Food and equipment use one WooCommerce source of
truth but retain type-specific facts, validation, rendering and schema. It
supersedes the earlier evaluation-only pantry state described below.

- WooCommerce 10.9.4 is installed from the exact pinned official package.
- The curated public store contains exactly 36 owner-authorized culinary products.
- Each new product begins with stock 1, no backorders and one normal public
  product image with no archive label or disclaimer.
- Prices are owner-authorized opening retail prices informed by the bound market
  observations dated between 2026-07-31 and 2026-08-06.
- The store and classic cart are public when the exact catalog receipt passes.
- Pantry filters hide every nonmatching product card and keep the visible result
  count and shareable URL filter in sync.
- The receipt identity reads unfiltered WooCommerce edit values. A shopper can
  switch the cart between Hebrew and English without making the catalog appear
  changed or closing the pantry route.
- Cart customers can edit their items and continue by telephone for order
  confirmation and pickup at 99 Ibn Gabirol Street.
- Electronic payment and checkout remain closed until gateway credentials,
  exact supplier-label and applicable country-of-origin records, and payment
  acceptance evidence are supplied.
- The signed private OS inventory route depends on catalog readiness, not
  payment readiness.
- Reapplying the catalog never resets operational stock.
- Complete99 OS can refresh the public read model without replacing approved
  consumer copy, ordering, filters or food badges. The synchronized source is
  reported as attested only after its full 12-dish display contract matches this
  release.
- Catalog materialization sets native WooCommerce visibility to live and, after
  the committed strict readback, clears and rechecks the recovery boundary,
  then requires successful UPress and LiteSpeed page-cache purge receipts. A
  purge failure restores the recovery boundary.

Sections that discuss a held pantry, hidden evaluation products or full
checkout acceptance remain historical evidence or requirements for the later
electronic payment launch. They do not describe the current public catalog and
cart state.

## Governing boundary

The public WordPress site serves culinary consumers. Its public routes cover
food, dishes, ingredients, traditions, practical guides, the pantry direction,
the restaurant story, contact information and consumer policies.

Institutional services, proposals, workers, tasks, inventory controls,
suppliers, costs, campaigns and operating dashboards stay private. Their legacy
seed records are migrated to private status and removed from public navigation,
search ownership and sitemaps.

No Complete99 worker role is installed or assigned by release 1.6.0. The private
commerce outbox reports `unassigned_infrastructure` until a later, separately
approved operating decision.

## System assumptions

The release and commerce controls assume:

- WordPress 6.4 or later and PHP 8.0 or later;
- HTTPS on one exact approved production host;
- Hebrew as the site default with deterministic English routes under `/en/`;
- working WordPress REST routes through either the normal `/wp-json/` transport
  or the equivalent `rest_route` query transport;
- MySQL or MariaDB support for the bounded outbox lock;
- a transactional engine for every table included in a transactional database
  rollback;
- enough disk space for the active plugin, incoming package, backup archive and
  temporary extraction at the same time;
- a cache layer that can be purged and independently checked;
- a dedicated deployment administrator and a site-specific Application
  Password stored outside Git;
- payment, mail, shipping and merchant credentials stored outside this
  repository;
- no production WooCommerce sample data;
- a direct-live UPress change only after local and CI gates, a recovery probe,
  identified backups and a deploy lock pass.

The UPress alias remains the canonical origin until a final domain migration is
verified. A future domain name in documentation is not permission to change
canonical URLs, redirects or TLS settings.

## Public release behavior

The Hebrew root and English `/en/` route use the same consumer hierarchy:

1. Home
2. Dishes
3. Ingredients
4. Traditions
5. Knowledge
6. Pantry shop
7. About
8. Contact
9. Privacy, terms and accessibility

The order action continues to the verified Wolt restaurant route for the
selected language. WordPress does not copy Wolt prices or make unsupported
availability claims.

Selected business-owned food images render as normal public dish media without
an archive label, disclaimer or unusual treatment. A fresh signed model applies
publication controls per dish only when provenance and public-use rights pass;
the packaged WordPress contract remains the source of display copy, order,
filters and food badges.

## Pantry state for 1.3.1

The live pantry remains held unless every launch check passes. A held pantry:

- has no purchase button;
- states that no products are currently sold on the site;
- labels the generated packaging image as a concept;
- remains `noindex` and outside the sitemap;
- sends food ordering to the active Wolt route;
- blocks native WooCommerce product, cart, checkout, account and Store API
  surfaces.

This is the expected production state while no real product catalogue,
merchant configuration or verified checkout evidence exists.

## WooCommerce dependency rule

Release 1.3.1 does not install WooCommerce through the Complete99 plugin bridge.
That bridge remains restricted to the `complete99-platform` slug and its small,
digest-verified package.

WooCommerce is a separate dependency because its package is much larger and has
its own database and operational effects. Installing it on production requires a
separate one-purpose workflow with all of these controls:

- an exact approved WooCommerce version and official download URL;
- a recorded SHA-256 and byte size checked before installation;
- authenticated WordPress, PHP, database, filesystem and free-space preflight;
- a full UPress files and database backup identified before mutation;
- inactive installation first;
- plugin file digest and version verification;
- activation only after the Complete99 store gates are present;
- proof that the public pantry remains held after activation;
- exact rollback to the prior files, database state and public surface;
- permanent removal of any temporary route or installation helper.

Do not increase the upload limit or slug allowlist of the existing Complete99
bridge to carry WooCommerce.

### Researched dependency candidate

The following record is research evidence only. It is not an approval or an
installation instruction.

| Field | Candidate value |
| --- | --- |
| Product | WooCommerce |
| Version | `10.9.4` |
| Official candidate URL | `https://downloads.wordpress.org/plugin/woocommerce.10.9.4.zip` |
| Exact byte size observed | `20545768` |
| SHA-256 observed | `6e58fc3ba9b18d1c9aee6b0227d3c3c09e4fe2c1332823bd2e0ac54ffcff64a9` |
| Research date | `2026-07-29` |
| Approval state | Unapproved candidate |

At execution time, download the candidate again from the official WordPress.org
origin. Recheck the release status, WordPress and PHP compatibility, archive
root, plugin header, byte size and SHA-256 before any live write. A changed
upstream file, redirect to an unexpected origin, incompatible runtime or
different digest stops the action and requires a new review record. Never treat
this recorded hash as permanent approval for a later date.

## Private product preparation

Use real WooCommerce simple physical products. Do not create sample or invented
products on production. Each product needs:

- a stable SKU;
- reviewed Hebrew and English name and description;
- a positive ILS price;
- a positive weight and a shipping class;
- real rights-approved primary and gallery images whose file bytes, metadata,
  captions and filenames are explicitly approved as public-safe even while the
  pantry is held;
- managed positive stock with backorders disabled;
- WooCommerce recorded as stock authority;
- Hebrew and English ingredients;
- Hebrew and English allergen statement;
- Hebrew and English storage instructions;
- Hebrew and English delivery or pickup terms;
- reviewed retail label, image rights and tax treatment;
- explicit Complete99 storefront approval.

One approved product that later fails a required check keeps the entire pantry
held. Product edits invalidate the prior checkout acceptance receipt. The
launch readiness check requires current positive stock. Acceptance preserves
the exact stock movement caused by each test order, so it does not require the
post-sale quantity to remain positive.

### Evaluation catalogue for release 1.3.1

Release 1.3.1 carries a private evaluation registry of 26 ingredient products.
Each record has:

- one canonical product code and ingredient code;
- a Hebrew and English product name;
- a researched Israeli market source URL, provider and check date;
- an observed price or range and one ILS evaluation price;
- evaluation stock quantity `1`;
- a package or weight description and price normalization;
- a product handling classification;
- a matched held WebP evaluation image;
- verified and candidate dish or ingredient relations;
- explicit supplier, label, allergen, nutrition, kosher, image, merchant,
  payment, shipping, tax, legal and checkout gates.

The price and stock values exist so an administrator can inspect the complete
data path. They are not public price or availability claims. Every product is
private, not publicly eligible, and held from sale. Fresh, variable-weight,
chilled, frozen and regulated records remain held even when their benchmark
price and evaluation stock are populated.

The migration materializes exactly 26 private ingredient records and 26 private
product plans. It does not create WooCommerce products inside the deployment
transaction. If WooCommerce is available later, a separate explicit checkpoint
may create exactly 26 hidden draft physical products after it verifies the
canonical SKU, source, price, stock, all closed gates and rollback coverage.

The administrator Review Lab is read-only. It must show both the registry
counts and the durable materialization receipt. Seed-file counts alone do not
prove that the database is ready.

Current market evidence comes from the exact source URLs stored with each
record. Government-controlled-price sources are used where applicable. Market
comparison sources are observations, not supplier quotations or a promise that
the same price is available at checkout.

### Private inventory synchronization

WordPress exposes a signed private inventory sync endpoint at
`/wp-json/complete99/v1/inventory/sync`. It accepts only explicit canonical
product codes, integer versions and quantities. It does not infer a sellable
unit from kitchen kilograms, eggs, portions or a product title.

The private Complete99 OS signs the canonical request on the server. The shared
secret never enters browser code. Requests have a five-minute timestamp window,
bounded item count, monotonic per-product versions, idempotent batch receipts
and no-store responses.

In evaluation mode, the bridge may update only private evaluation metadata and
an existing hidden draft product with every launch gate closed. Commerce mode
requires a completed platform migration, an existing exact product binding,
WooCommerce stock authority, sync enablement and a checkout-ready product. It
never creates a product and never resolves one by title, slug or approximate
SKU.

## Consumer policy acceptance

Before an on-site sale, the public Hebrew and English policy pages must describe
the actual:

- payment processor;
- payment methods;
- delivery and pickup terms;
- cancellation and refund terms;
- data retention rule;
- support contact.

The private legal acceptance endpoint records those bilingual facts and hashes
the six public legal pages. Every accepted fact must appear in the corresponding
public copy. A later public legal edit invalidates the receipt until it is
reviewed again. Hebrew facts and pages must be predominantly Hebrew, and
English facts and pages must be predominantly English. Copying one language
into both fields cannot pass.

## Checkout acceptance

Enable the private acceptance preview only as an authenticated WordPress
administrator. Preview stays private, no-store and noindex. The public pantry
remains closed while preview is active.

Acceptance contract `complete99-commerce-acceptance/v3` requires two distinct
recent real storefront orders:

1. one order created through the Hebrew storefront;
2. one different order created through the English storefront.

For each order, record and verify:

1. checkout through the public Complete99 classic checkout;
2. payment completion by the enabled live gateway;
3. a nonempty processor transaction identifier;
4. the order-received page;
5. a customer processing or completed-order email accepted for sending, with
   the exact expected locale and version 4 script-dominance evidence from its
   final subject and visible body;
6. a real gateway partial refund;
7. exact stock reduction for every order line;
8. whole-order fulfilment coverage;
9. a completed WooCommerce order status.

Each order must use ILS, have a positive total, contain a shipping line, have
complete billing email and address facts, and contain only approved simple
physical products. The gateway must be enabled, support refunds and prove live
mode. The payment-complete receipt stores the gateway ID and a hash of the
processor transaction identifier, never the identifier itself.

Each stock receipt is tied to the exact order ID. Every order line must have one
`inventory_order_stock_reduced` event with the same order entity, the exact
line-item ID, product and variation IDs, ordered quantity, stock source and
destination, and a stock delta equal to the ordered quantity. Missing,
duplicated, mismatched or cross-order stock evidence fails acceptance.

When WooCommerce fulfilments are enabled, one order may be split across several
fulfilled records. The sum of fulfilled quantity for each line must equal the
whole ordered quantity exactly. No line may be omitted or over-covered, and
every fulfilment event must be tied to that same order. When the fulfilments
feature is disabled, the completed order status is the fulfilment evidence.

The first recording of each language requires the correlated order snapshot,
gateway refund, stock and, when applicable, fulfilment events to be present in
the private outbox. Later acknowledgement is allowed because the bounded
acknowledgement audit preserves their identifiers and payload digests.
Both pending and acknowledged evidence also preserves the event version used to
recompute each event identifier.

The stored configuration digest covers the Complete99 and WooCommerce versions,
the order language, the full approved storefront catalogue, localized legal and
pantry pages, reviewed checkout policy text, tax classes and rates, the selected
shipping method, each zone's locations, global and instance settings, and every
payment gateway's live and refund configuration. It also covers every material
WooCommerce option, product post and term fact, and the file and attachment
metadata digests for primary and gallery images. A material configuration
change invalidates the complete acceptance receipt.

After the first valid order, the receipt status is
`pending_second_language` and preview remains enabled. After the second,
different-language order passes, the receipt status becomes `passed` and
preview is disabled. The pantry remains held in both cases. Only the locked
launch endpoint may reopen it. Each language entry records its administrator,
evidence and configuration digests. The complete receipt expires after 30 days
and both orders are rechecked against current order, product, configuration and
evidence state.

## REST endpoint contracts

All routes use namespace `complete99/v1`. No response may contain credentials,
private supplier data or worker assignments. Direct customer contact and address
data is excluded from every public response and from the outbox. It is available
only through the authenticated single-order operations route when fulfilment or
support work requires it.

| Method and route | Access | Contract |
| --- | --- | --- |
| `GET /health` | Public | Read-only plugin, database, deployment and signed read-model freshness evidence. Migration mismatch fails with `503`. |
| `GET /public-catalog` | Public | Read-only consumer-safe dish projection. Public branch and section labels may appear. Internal IDs, publication controls, operational status, provenance review state and campaigns are not public output. |
| `POST /sync/read-model` | Signed private producer | Accepts at most 524288 bytes with schema `complete99-public-read-model/v1`. Requires timestamp, nonce and HMAC headers. The clock window is 300 seconds. Nonces are reserved under a database lock, private campaign data is rejected, and an older generation timestamp cannot replace a newer model. |
| `GET /store/status` | Public | Returns only the held or checkout-ready state, approved product count, currency, bilingual current-order URLs and storefront contract version. |
| `GET /store/readiness` | Commerce manager or administrator | Returns the private readiness checklist and missing requirements. It does not mutate state. |
| `POST /store/acceptance-preview` | Administrator | Accepts a boolean `enabled`. The launch lock closes both pantry pages, verifies the held state, purges caches, and then enables authenticated no-store preview. Enabling it invalidates prior checkout acceptance. |
| `POST /store/legal-acceptance` | Administrator | Records distinct reviewed bilingual consumer facts, reviewer identity and exact hashes of the six public legal pages. Every fact must appear on its assigned policy page. Held external-ordering language in either language cannot pass. |
| `POST /store/acceptance` | Administrator | Evaluates one recent real WooCommerce order for its recorded Hebrew or English language. Version 3 reaches `passed` only when distinct orders cover both languages and each independently proves live gateway payment, order receipt, final email language content, gateway partial refund, exact order-correlated stock, approved product and whole-order fulfilment evidence. The locked result remains held until an explicit launch. Request-supplied surface claims are ignored. |
| `POST /store/launch` | Administrator | Accepts a boolean `enabled`. Opening uses a launch lock, precommit cache purge, staged readiness, bilingual page-state and audit readback, then commits enablement. A failed commit or postcommit cache purge rolls the launch back. Disabling closes first-party commerce before cache work and stays closed if cache invalidation needs manual attention. |
| `GET /store/operations/outbox` | Administrator | Returns pending private events, the locked durable failure journal, per-code unresolved errors, recovery state and the acknowledged-audit count. |
| `POST /store/operations/outbox` | Administrator | Accepts a nonempty `event_ids` array of at most 200 lowercase SHA-256 identifiers. Every ID must match a pending event. It writes and verifies a bounded acknowledgement audit before removal. Active acceptance evidence is protected during compaction. |
| `POST /store/operations/outbox/replay` | Administrator | Accepts up to 100 exact `failure_ids`, restores those events under the site-scoped database lock and removes a failed-event record only after readback succeeds. |
| `GET /store/operations/orders/{id}` | Commerce manager or administrator | Returns one private order record needed for fulfilment and support. It is `private, no-store` and omits credentials, payment tokens, IP address and user agent. |

WordPress REST authorization failures are final. The query-form REST transport
may be retried only after an HTML host or WAF `403`, never after a WordPress JSON
denial.

## Controlled public launch

Use the authenticated `/wp-json/complete99/v1/store/launch` endpoint. Do not set
the launch option or pantry metadata directly.

The endpoint serializes state changes with a site-scoped launch lock. To open
the pantry it:

1. verifies both published pantry pages;
2. purges public caches, with one immediate retry;
3. evaluates staged readiness while assuming the intended enablement, pantry
   index contract and verified customer-continuity marker;
4. writes and verifies the launch audit and bilingual pantry index state;
5. commits the enabled and customer-continuity options;
6. rereads both options and full readiness;
7. purges caches again.

This is precommit launch staging. The enabled option is not written until the
staged readiness, audit and bilingual page state pass. A staging, readback or
postcommit cache failure restores the held snapshot and returns an error.

To close the pantry, the endpoint writes and verifies the disabled option and
held bilingual page state before it purges caches. If both cache attempts fail,
the response identifies manual cache work, but `store_enabled` remains false.
Do not reopen the store to hide a cache failure.

After launch, perform real Chrome acceptance in Hebrew and English at desktop
and 390 CSS pixels. Check:

- pantry product facts and real images;
- add to cart;
- cart;
- delivery or pickup context;
- checkout and payment;
- confirmation and support;
- partial refund result;
- stock reduction;
- RTL and LTR price, quantity and form order;
- keyboard focus and error association;
- no horizontal overflow or console errors;
- Product and Offer structured data matching the visible facts.

## Private operations handoff

The authenticated outbox contains only order identifiers, status, totals,
payment method identifier, product IDs, SKUs, quantities, shipping method
identifiers, refund facts, fulfilment state and stock facts. It excludes direct
customer contact and address data.

The Complete99 OS may read and acknowledge event IDs in bounded batches. The
pending queue is limited to 500 events and uses a site-scoped database advisory
lock with write readback. Public launch is held at 450 pending events.

Every unresolved error is stored under its own code, including cache, primary
lock, capacity, readback, failure-journal lock, failure-journal capacity,
failure-journal readback, audit capacity and audit readback. Recovery clears
only the code that was independently resolved. It cannot erase an unrelated
unresolved error.

A failed pending-queue write keeps the full event in a durable journal limited
to 500 failures. Both recording and clearing that journal use a separate
site-scoped advisory lock and verified readback. Replay restores exact events to
the pending queue before it removes the failure record.

Acknowledged IDs and payload digests remain in an audit limited to 5000
entries. Acknowledgement first removes entries older than the 30-day acceptance
window and replaces duplicate acknowledgements. If the limit is still exceeded,
it removes the oldest entries that are not referenced by active checkout
acceptance. It never compacts away active acceptance evidence. If safe
compaction or audit readback is impossible, the pending events are not removed
and the store remains held.

Every pending event stores a canonical payload digest and an event version.
Readback recomputes its identifier and rejects changed payloads, malformed
timestamps and duplicates. Acknowledgement rows retain enough information to
recompute the same identity. Replay and acknowledgement stop before writing
when a raw queue, failure journal or audit is corrupt.

Corruption, any unresolved per-code error, any durable failure, or outbox
backpressure holds the public store.

Inventory authority remains WooCommerce. A future worker assignment, supplier
workflow or inventory operating screen requires a separate private-system
decision. None is imposed by this release.

## Recovery

To close the pantry safely, call the launch endpoint with `enabled: false`.
Confirm:

- the response reports `store_enabled: false`, including when manual cache
  purging is required;
- public status reports `external_ordering`;
- the pantry is `noindex`;
- native WooCommerce purchase surfaces redirect to the held pantry or return a
  held response;
- Wolt ordering still works in both languages;
- no private outbox data is exposed publicly.

### Backup requirements

Before the Complete99 1.3.1 deployment:

1. Record the active plugin version, directory digest, activation state,
   database version, deployment marker and rendered consumer marker.
2. Capture a restorable archive of the active Complete99 plugin directory.
3. Capture the plugin-owned database state through the deployment journal.
4. Confirm every table in the scoped restore uses a transactional engine.
5. Retain the host recovery path and enough disk space to hold both versions.

Before a separate WooCommerce installation or update:

1. Record the complete plugin list and activation states.
2. Identify a full UPress files and database backup created immediately before
   mutation.
3. Record WooCommerce-owned tables, options, scheduled actions and upload paths
   that the restore must cover.
4. Confirm that the public pantry is held before and after inactive
   installation.
5. Keep the backup until activation, held-state proof, checkout readiness work
   and any required rollback exercise are complete.

### Restore and rollback contract

For the Complete99 1.3.1 release, production must perform the real exercise:

1. Deploy the reviewed candidate and verify health, body, plugin digest,
   migration invariants, database state and cache.
2. Restore the prior plugin archive and its scoped database snapshot as one
   audited rollback operation.
3. Verify the prior health version, body marker, activation state, database
   marker and held pantry.
4. Redeploy the exact same 1.3.1 artifact with the same SHA-256.
5. Repeat the full verification and remove temporary recovery material only
   after the final result passes.

If either the file half or database half cannot be restored, stop in a
recovery-required state and retain all remaining backups and evidence. Do not
attempt to make a partial restore look successful.

If the separate WooCommerce dependency action fails, restore the identified
UPress files and database backup, purge caches and verify the prior plugin list,
database, health endpoint, public pages and held pantry before any retry.

The detailed Complete99 plugin procedure remains in `docs/recovery.md` and
`docs/deployment-runbook.md`.
