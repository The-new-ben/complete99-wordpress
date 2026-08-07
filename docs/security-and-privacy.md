# Security, privacy and integration boundary

## Public enquiry data

The form validates a nonce, honeypot, consent and required fields; rate limits by a
salted one-way network-address hash; stores a private `c99_lead` record; and performs
no external send. Do not request medical information, employee records, identity
documents or payment data.

Define a retention period with the business owner before public launch. Until a
policy is approved, access is restricted to WordPress administrators and deletion is
manual and auditable.

## OS read-model signature

The OS sends:

- `X-Complete99-Timestamp`: current Unix time;
- `X-Complete99-Nonce`: 16–128 URL-safe random characters;
- `X-Complete99-Signature`: lowercase HMAC-SHA256.

Canonical message:

```text
<timestamp>\n<nonce>\n<sha256-of-exact-request-body>
```

The shared secret is 32–4096 random characters stored in the GitHub `production`
environment and the Complete99 OS server-side secret store. The deliberate
deployment bridge initializes WordPress only when the option is absent or empty,
accepts an existing exact match, and refuses rotation or mismatch. It is never
rendered by WordPress. Requests expire after five minutes and a used nonce is
rejected for ten minutes.

Only explicitly allowed menu sections and menu items are stored. A `branches`
array is accepted for signed schema compatibility, counted toward the record
limit, then discarded. Branch data is not stored or published until a verified
consumer location contract is approved. Campaigns are private. The sync contract
accepts an omitted or empty `campaigns` member for schema compatibility and
rejects any nonempty value with HTTP 422. Unknown fields are rejected, record
counts and bytes are capped, and image names must use the owned `c99-` asset
namespace.

The public endpoint at `/wp-json/complete99/v1/public-catalog` uses a separate
consumer-safe projection. It includes only published section labels and verified
bilingual menu facts. It never returns branches, internal identifiers, section
links, sort or publication controls, verification state, media provenance, media
rights state, campaign data, or the stored digest. Public dish-menu items do not
accept price, currency, stock quantity or operational availability. The separate
verified WooCommerce catalog owns ingredient-product prices, stock and cart
state.

The packaged WordPress menu is the approved presentation contract. A fresh
synchronized model is reported as attested only when all 12 public slugs and
every approved display field match that contract exactly. WordPress keeps
the packaged order, filters, food badges and facets, and derives public section
labels from the approved dish records. A refreshed timestamp alone cannot
promote older or different consumer copy.

An image-bearing menu item must carry an internal approved provenance and
`approved_public_use` rights state. Public projections do not expose provenance
or internal review wording. Selected dish and product images render as normal
media without an archive label, disclaimer or unusual public treatment.

Boolean fields accept only JSON booleans or the exact strings `true` and `false`;
other values fail with HTTP 400. Menu-item identifiers permanently own their
sanitized canonical slugs, so a sync cannot silently rename an existing canonical
or reassign it to another identifier.

An accepted read model remains current for 24 hours from its verified WordPress
storage timestamp. Every individual menu item must also have been updated within
that window. If either freshness limit expires, or if the synchronized display
contract differs, public routes use the packaged approved menu while the sync
status remains visible to the closed operational checks.

## Commerce boundary

WooCommerce 10.9.4 is the product, stock and cart engine for release 1.16.0. The
curated 36-product store and classic cart become public when the exact catalog
receipt passes. Payment and electronic checkout remain closed until the
separate controlled checkout gate passes. Administrators can later exercise
real checkout transactions for acceptance evidence without changing catalog
readiness.

Entity Studio is an administrator-only planning surface. It registers a
private, non-queryable dossier post type, creates no role, maps all read and
write capabilities to `manage_options`, blocks deletion, and exposes only an
administrator-gated REST route. Dossiers, source observations, planning prices,
commercial rationale, revision history and workflow metadata are never public
content. The Studio cannot publish WooCommerce products, change live price or
stock, activate an offer or open payment.

Release 1.13.0 preserves the 36 public products and all 17 earlier private draft
offers. It adds three private Syrian planning-price observations without
creating a WooCommerce product, channel offer, stock record or supplier claim.
There are zero new active or draft offers. The 56 of 56 coverage metric means
every known product identity has either a current public price or a private
planning price: 36 live prices plus 20 private planning prices. It does not mean
every identity is publicly sellable. Public source-market projection requires
the exact explicit value `public`; missing, malformed, unknown and `held` values
remain private. Payment remains disabled and no role is installed or assigned.

Release 1.16.0 preserves all 56 product identities, the 36 public WooCommerce
products and the 20 private planning prices. It creates zero new active or draft
offers. Culinary-commerce registry version 10 is bound to culinary-science
registry version 16. The 86 new Syrian identities contain no retail listing or
price observation and are not product identities, offers, suppliers, stock
records or purchase routes.

The health response reports culinary-commerce registry validity separately
from active-offer readiness. Registry validation may be true while readiness is
false. This prevents a well-formed research graph from being mistaken for a
merchant, payment, inventory or fulfilment launch approval.

At release 1.13.0, the science registry contained 287 entities. The Syrian
module contributed 196 identities, including 56 dishes, 55 ingredients, 21
regional or topic hubs, 17 techniques, 17 traditions, 15 preparations,
markets, restaurants and hospitality institutions, plus three private held
market observations. Its one safe consumer gateway remained
`noindex,follow`; the other 195 Syrian entities remained private. Release
1.16.0 adds 86 more private Syrian identities and keeps the gateway unchanged.

The current science registry contains 551 entities. The 86-identity Syrian
expansion, 82-identity Lebanese foundation and 96-identity Iraqi foundation are
wholly private, `noindex` and reference-only. None of their facts, relations,
taxonomy, media or canonical
paths is eligible for a public page, public API projection, sitemap entry or
commerce activation. The
complete public science graph remains 23 entities resolving through 18
canonical page owners per language.

Four new Syrian records remain held for unresolved botanical, preservation or
exact identity questions. Family and community records are source-scoped and
cannot be promoted into universal regional claims. Institution, archive,
market and restaurant records do not imply partnership, endorsement, current
operation or image rights. Anonymous access to Entity Studio remains denied.

Every Lebanese identity references a central private Israel-Lebanon trade
control based on Israel Ministry of Economy and Industry Director-General
Instruction 2.4 dated 8 March 2026. The control treats the Lebanese records as
editorial sources or benchmarks only. It blocks supplier contact, sample
orders, payments, indirect third-party purchasing and representations of
delivery from Lebanon unless written legal and official authorization is
obtained first. A market observation, including one hosted outside Lebanon,
does not establish a lawful supply route into Israel. This fail-closed control
is not legal advice and does not assert that an exception exists.

Every Iraqi identity also references a central private Israel-Iraq trade
control. No Iraqi research record can create supplier contact, a sample, an
order, a payment, third-country purchasing, stock or a delivery claim without
current written official authorization. Food-safety records separately fail
closed for freshwater and dried fish, cooked rice, offal, overnight-cooked
foods, fermented vegetables, cultured dairy, concentrated date syrup, open
fire and wild plants.

Product discovery remains contained in the curated store. Payment and checkout
Store API routes remain blocked while payment is closed. Anonymous core product, variation,
product-taxonomy, product-only REST search and product oEmbed routes are also
blocked, while ordinary public search removes products. Private WooCommerce
administrators retain the core product access needed to manage the catalogue.
Anonymous media REST and product-linked attachment pages are closed. Because
upload files are public web assets, every selected product image is bound by
exact product code and SHA-256 and explicitly approved for normal public use.

The private launch gate requires reviewed bilingual product facts, exact online
supplier-label and applicable country-of-origin records, real images, positive
managed stock, ILS pricing, merchant and fulfilment settings, published
consumer policies, and working live payment and shipping configuration. Catalog
publication authorization does not satisfy the separate label and origin gates.
Checkout acceptance contract `complete99-commerce-acceptance/v3` requires two
different recent real orders, one Hebrew and one English. Each order must prove
an enabled refund-capable live gateway, a hashed processor transaction
identifier, a gateway partial refund, the order-received page, a customer
transaction email with final-content language evidence, exact order-correlated
stock events and complete fulfilment coverage. Email evidence version 4 requires
the declared script to dominate both the final subject and final visible body,
not merely to appear in them. Bilingual policy facts and all six legal pages
must likewise be primarily written in their declared language.

When an order is split across several fulfilments, accepted quantities must
cover every order line exactly and every event must name the same order. Stock
evidence also binds each line and stock delta to an event for that exact order.
The configuration digest includes live gateway state, the full approved
catalogue, localized policy and pantry pages, tax rates, shipping-zone
locations, global settings, instance settings, every material WooCommerce
option, product terms and post fields, and the SHA-256 of each reviewed image
file and attachment metadata. Changing an approved product or material checkout
configuration invalidates checkout acceptance.
Changing the accepted legal pages invalidates legal acceptance.

Home, pantry, transaction and public store-status responses always send
no-store headers, so a readiness failure cannot leave previously cached open
pantry or checkout HTML live.
Launch uses a site-scoped lock and precommit staging. It verifies cache purge,
staged readiness, bilingual pantry state and the launch audit before enabling
checkout. It then rereads committed readiness and purges caches again. Closing
writes the disabled state before cache work. A cache failure leaves checkout
closed and requires manual cache attention.

Order, refund, fulfilment and stock events enter a bounded private outbox for the
Complete99 OS. The payload excludes customer names, email, telephone, addresses,
notes and payment credentials. The outbox has no worker assignment. It records
unassigned infrastructure only.

The 500-event pending queue uses a site-scoped database advisory lock. Failed
writes retain the full event in a separate 500-record journal whose recording
and clearing use their own advisory lock and verified readback. Errors are
stored per code, so resolving a lock error cannot clear an unresolved cache,
capacity, failure-journal or audit error.

Acknowledged event identifiers and payload digests remain in a 5000-entry audit
written and verified before pending events are removed. Compaction discards
expired and oldest unprotected entries but preserves active checkout acceptance
evidence. If safe compaction is impossible, acknowledgement fails without
removing pending events. Any unresolved error, durable failure, corruption,
lock, cache, readback or capacity condition places the store on hold.
Every pending event and acknowledgement row contains the event version and
canonical payload digest used to derive its SHA-256 identifier. Readback
recomputes that identity and rejects malformed timestamps, changed payloads and
duplicate identifiers. Recovery and acknowledgement abort before writing if a
raw store is corrupt.

Customer contact and address details are available only through the
authenticated single-order operations endpoint. That response is private,
no-store and excludes credentials, payment tokens, IP addresses and user
agents.

## Cameras, devices and robots

WordPress is not a telemetry or video plane. Future integrations require a device
registry, per-device credentials, signed events, heartbeat, offline queue, retention,
role checks and an adapter owned by Complete99 OS. WordPress may receive a public
status statement only when it is explicitly approved for publication.

## Social networks and generated material

A campaign remains draft/approved until the real platform OAuth flow and account
permissions are complete. A sent state requires a provider receipt. Generated
material remains a proposal with source links and a human approval gate. It
cannot approve its own public claim, supplier order or regulated instruction.

## Deployment secrets

Application Passwords, route tokens, sync secrets and authorization headers never
enter Git, ZIPs, logs or audit artifacts. The temporary route exists only for one
transaction and requires both a WordPress administrator capability and an
unpredictable deployment token.
