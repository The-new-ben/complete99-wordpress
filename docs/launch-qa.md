# Launch QA

Release target: Complete99 Platform 1.3.3

## Automated gates

- PHP syntax passes for every plugin and bridge file.
- Python and JavaScript syntax pass.
- All contract tests and migration subtests pass.
- The package secret scan passes.
- Two independent package builds are byte-identical.
- The ZIP has one `complete99-platform/` root, sorted paths and normalized
  metadata.
- Package SHA-256, size and packaged-source SHA-256 match the separate integrity
  metadata.
- The public update manifest matches version 1.3.3 and its versioned package URL.
- The package contains no credential material, reference-image path or
  development dependency.
- The public source and documentation contain no em dash character.

## Public consumer boundary

- Hebrew root and English `/en/` address culinary consumers only.
- Header, footer, body copy, structured data, sitemap and SEO ownership contain
  no institutional, worker, supplier, inventory, cost, campaign, proposal or
  private-system marketing.
- Legacy Services, Industries, Platform, proposal and application seed records
  are private.
- No Complete99 worker role is installed or assigned.
- Every visible action has a working continuation.
- Wolt actions use the verified Hebrew or English restaurant URL and open with
  safe external-link attributes.
- Price and current dish availability are not copied from the private read
  model. Visitors continue to the ordering provider for current transaction
  facts.
- English telephone links use the international dialable number.

## Content and media

- Home, About, Dishes, Ingredients, Traditions and Knowledge have distinct
  bilingual consumer copy.
- Public copy explains food directly and does not narrate templates, publishing
  systems or future content mechanics.
- Archive images are described as archive references.
- A live read-model image appears only with approved provenance and public-use
  rights.
- The packaging visual is labelled as a concept and never presented as a real
  product photograph.
- Every generated or archive asset has a media-rights-register entry.
- Dish records remain private until bilingual editorial, kitchen, allergen,
  source and image checks pass.

## Read-model checks

- Signed model and item timestamps are valid and no more than 24 hours old.
- Only strict JSON booleans or exact `true` and `false` strings are accepted.
- Canonical item IDs and slugs cannot collide, change ownership or be silently
  renamed.
- Public item contract contains no price, currency, stock quantity or
  operational availability.
- Stale model, stale item, missing bilingual content, unapproved image rights or
  unverified publication state fails closed.
- Public catalog, live menu, dish URLs, SEO rows and sitemap entries agree.

## Pantry hold checks

These are mandatory for release 1.3.1 while no real products exist:

- WooCommerce is not installed by the Complete99 plugin bridge.
- Public pantry states that no products are sold on the site.
- No purchase button, cart, checkout or Product/Offer schema appears.
- Pantry is `noindex` and absent from the sitemap.
- Native product, cart, checkout, account and Store API surfaces fail closed.
- Public store status reports `external_ordering`.
- Hebrew and English food-order actions remain operational through Wolt.

## Future commerce launch checks

Apply only after the separate WooCommerce dependency and real product data are
approved:

- WooCommerce dependency version, official URL, size and SHA-256 are recorded.
- UPress files and database backup exists and rollback is proved.
- Only real approved simple physical products appear.
- Product name, description, price, weight, SKU, image, ingredients, allergens,
  storage and fulfilment facts are bilingual and visible.
- Every primary and gallery image has a reviewed public-safe classification,
  readable file SHA-256 and attachment metadata digest.
- WooCommerce owns managed positive stock and backorders are disabled.
- Merchant address, ILS currency, published cart, checkout, account, terms and
  privacy pages, gateway, shipping method and SSL pass.
- Accepted legal facts match the six bilingual public legal pages.
- Hebrew policy facts and pages are predominantly Hebrew, and English policy
  facts and pages are predominantly English.
- Two distinct recent orders created through classic checkout, one Hebrew and
  one English, each prove a live payment, observed confirmation, final
  subject-and-body email language, gateway partial refund, exact line-level
  stock reduction and complete fulfilment handoff.
- Private order, refund and stock events contain no direct customer contact or
  address data.
- Outbox storage lock, readback, acknowledgement and backpressure checks pass.
- Mutated event payloads, versions, timestamps and duplicate identifiers fail
  readback, and replay or acknowledgement does not rewrite a corrupt store.
- Launch audit version 3 still matches staged readiness, legal acceptance,
  checkout acceptance and both pantry page identities.
- The entire anonymous WooCommerce Store API returns 404 after launch as well
  as during the held state. The accepted customer flow remains classic
  checkout.
- Passing both checkout acceptance runs leaves the pantry held. A separate
  authenticated call to the store launch endpoint is required to open it.
- Launch occurs only through the authenticated store launch endpoint.

## Real Chrome matrix

Capture and review:

- Hebrew home at 1440 by 1000 CSS pixels;
- English home at 1440 by 1000 CSS pixels;
- Hebrew and English home at 390 by 844 CSS pixels;
- desktop and mobile navigation open states;
- Dishes, Ingredients, Traditions, Knowledge, Pantry, About, Contact, Privacy,
  Terms and Accessibility;
- one known archive dish;
- one unknown Hebrew and English dish route;
- future cart and checkout only when commerce is approved.

Verify:

- no horizontal overflow;
- no clipped Hebrew labels;
- no broken image or placeholder;
- no visible target below 44 by 44 CSS pixels on changed surfaces;
- keyboard menu wrap, Escape close and focus return;
- visible focus at 200 percent zoom;
- reduced-motion behavior;
- correct document language and direction;
- reciprocal language links, canonical and hreflang;
- zero first-party console errors;
- mobile menu and sticky actions do not cover content.

## Live operational checks

- Selected HTTPS origin is the exact live UPress alias or final canonical domain.
- UPress REST is enabled.
- Health returns expected component, version, deployment ID and database version.
- Temporary deployment row is absent and its route returns 404.
- Cache-busting requests render the new consumer body and release marker.
- `robots.txt`, sitemap and public catalog return their managed state.
- Public search does not expose products or private content types.
- Authenticated private readiness and outbox routes are not publicly readable.
- The private Complete99 OS is not marketed as a production-secure consumer
  application.

## Native deployment acceptance

The hardened pipeline has been exercised with Application Password and Code
Snippets REST requests. Release 1.3.1 also requires the pending-phase recovery
regressions to pass:

- failed stabilization can roll back from
  `installed_pending_stabilization`;
- interrupted cleanup can recover from `installed_pending_cleanup`;
- unconfirmed rollback is refused;
- prior database and plugin file fingerprints are verified;
- cleanup removes the temporary bridge and proves route 404.

See `docs/pipeline-acceptance-evidence.md` and the versioned release record for
the final non-secret evidence.
