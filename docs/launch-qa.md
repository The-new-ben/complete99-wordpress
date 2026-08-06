# Launch QA

Release target: Complete99 Platform 1.6.1

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
- The public update manifest matches version 1.6.1 and its versioned package URL.
- Consumer breadcrumb and live cart-status links expose a minimum 44 by 44
  CSS-pixel target.
- An exactly equivalent read-model retry repeats all public cache purges,
  reports `write_changed=false`, and can recover after a prior purge failure.
- A fresh model with older or changed dish copy falls back to the packaged menu;
  an exact approved model preserves packaged order, facets and food badges and
  reports the synchronized source.
- The WooCommerce visibility options read back as `no`, and the apply audit
  proves UPress completion when detected plus a LiteSpeed purge signal after
  the transaction commits.
- A page-cache purge failure restores the sealed recovery marker with durable
  readback; a failed marker restoration is a distinct deployment failure.
- Every plugin-owned shell emits exactly one escaped document title and one
  viewport declaration after competing `wp_head` callbacks are de-duplicated.
- Script, style, comment and accessible SVG title content remains byte-for-byte
  intact during head de-duplication.
- All four plugin-owned shells are rendered in regression tests with adversarial
  tag boundaries, quoted delimiters, raw text and inert template content.
- The package contains no credential material, reference-image path or
  development dependency.
- The public source and documentation contain no em dash character.
- The twenty-four bilingual museum-preview routes resolve from public projections,
  emit canonical and hreflang metadata, remain `noindex,follow`, and are absent
  from the museum sitemap provider until their separate index gate is approved.
- Public museum responses cannot expose visual prompts, supplier terms, landed
  cost, margins, connector state, approval identities or private workflow data.

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
- The 32 selected catalog images render as normal product media with no public
  archive label, caveat or unusual treatment.
- A live read-model image appears only with approved provenance and public-use
  rights.
- Every public catalog image has an exact source hash, product-code binding,
  owner approval and public-use receipt.
- Dish records remain private until bilingual editorial, kitchen, allergen,
  source and image checks pass.

## Read-model checks

- Signed model and item timestamps are valid and no more than 24 hours old.
- Only strict JSON booleans or exact `true` and `false` strings are accepted.
- Canonical item IDs and slugs cannot collide, change ownership or be silently
  renamed.
- Public item contract contains no price, currency, stock quantity or
  operational availability.
- A stale whole model returns the packaged 12-dish menu. Within a fresh model,
  a stale, missing, unpublished or unapproved dish is held per slug; different
  display copy is replaced by the packaged approved copy for that slug.
- Public catalog, live menu, dish URLs, SEO rows and sitemap entries agree.

## Catalog and cart checks

- WooCommerce 10.9.4 is installed from the pinned official package and the
  full installed tree matches the expected digest.
- The public store contains exactly 32 owner-authorized catalog products and is indexable.
- Every product has its exact SKU, researched opening price, normal image,
  category, tags, stock authority and a reciprocal dish or science-entity relation.
- New products begin with stock 1. Reapplying the catalog preserves operational
  stock and never resets it.
- Backorders are disabled and out-of-stock products have no active add button.
- The Hebrew and English store use working filters, visible cart state and an
  editable classic WooCommerce cart.
- Selecting a pantry filter visually hides every nonmatching product card. The
  result count, selected button and URL parameter remain synchronized.
- Changing the cart language to English, emptying the cart and returning to
  either store keeps all 32 products available. Receipt identity uses raw
  WooCommerce edit values and cannot change with customer-session filters.
- The public Complete99 store is never the native WooCommerce Shop page.
- Native product, taxonomy and Shop pages redirect to the curated store without
  a loop and remain noindex.
- Local pickup is enabled in the default zone and read back from the exact
  WooCommerce shipping-method row.
- Complete99 OS inventory sync requires catalog readiness, not payment
  readiness, and verifies quantity plus stock status on every replay.

## Electronic payment launch checks

Apply only after payment-provider credentials are supplied:

- Every product has its exact supplier label, online ingredient and allergen
  disclosure, and applicable country-of-origin record reviewed and stored.
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
- Payment and checkout Store API routes stay closed until launch. The catalog
  and classic cart remain independently usable.
- Passing both checkout acceptance runs leaves electronic checkout held. A separate
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
- the 12 known dish pages and at least one related product set;
- one unknown Hebrew and English dish route;
- the live cart, plus checkout only after payment is approved.

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
