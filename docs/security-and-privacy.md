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
rejects any nonempty value with HTTP 422. Unknown fields are dropped, record
counts and bytes are capped, and image names must use the owned `c99-` asset
namespace.

The public endpoint at `/wp-json/complete99/v1/public-catalog` uses a separate
consumer-safe projection. It includes only published section labels and verified
bilingual menu facts. It never returns branches, internal identifiers, section
links, sort or publication controls, verification state, media provenance, media
rights state, campaign data, or the stored digest. Public menu items do not
accept price, currency, stock quantity or operational availability. Current
price and availability remain with the active ordering provider until a verified
Complete99 commerce launch.

An image-bearing menu item must name approved provenance as Complete99 archive,
business owned or licensed, and its rights state must be
`approved_public_use`. An item without those two facts fails closed. Every
public item also carries its own verified update time.

Boolean fields accept only JSON booleans or the exact strings `true` and `false`;
other values fail with HTTP 400. Menu-item identifiers permanently own their
sanitized canonical slugs, so a sync cannot silently rename an existing canonical
or reassign it to another identifier.

An accepted read model remains public for 24 hours from its verified WordPress
storage timestamp. Every individual menu item must also have been updated within
that window. After either freshness limit expires, the catalog fails with HTTP
503 and the live menu, dish routes, dynamic SEO ownership rows and dish sitemap
entries all fail closed until a fresh signed sync is stored.

## Commerce boundary

WooCommerce becomes the public transaction engine only after the controlled
store launch gate passes. Before launch, a capability-gated administrator
preview can exercise real checkout transactions for acceptance evidence, while
Complete99 blocks native product, cart, checkout, account and Store API surfaces
from anonymous public use. The public pantry page remains nontransactional and
`noindex`.

Product discovery remains contained after launch. The entire public Store API
stays blocked because the accepted customer flow is the classic checkout.
Anonymous core product, variation,
product-taxonomy, product-only REST search and product oEmbed routes are also
blocked, while ordinary public search removes products. Private WooCommerce
administrators retain the core product access needed to manage the catalogue.
Anonymous media REST and product-linked attachment pages are closed. Because
upload files are public web assets, an approved product cannot pass readiness
until every primary and gallery image is explicitly reviewed as safe for public
exposure even while the pantry is held.

The private launch gate requires approved bilingual product facts, real images,
positive managed stock, ILS pricing, merchant and fulfilment settings, published
consumer policies, and working live payment and shipping configuration.
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
