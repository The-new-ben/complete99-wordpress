# Launch QA

Release target: Complete99 Platform 1.15.0

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
- The public update manifest matches version 1.15.0 and its versioned package URL.
- The stored public read-model digest equals SHA-256 of the recursive canonical
  model after removing only the top-level `digest` field.
- Canonicalization preserves ordered lists and sorts associative keys at every
  depth, so equivalent key insertion orders produce the same digest and retry
  result.
- Every digest comparison uses `hash_equals`. Missing, malformed, arbitrary and
  content-mismatched digests make the model non-fresh and activate the approved
  packaged-menu fallback.
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
- The thirty-six bilingual culinary-science routes resolve from 23 public
  entities through 18 canonical page owners per language,
  emit canonical and hreflang metadata, remain `noindex,follow`, and are absent
  from the museum sitemap provider until their separate index gate is approved.
- The cumulative registries identify themselves exactly as
  `culinary-science-2026.08.07.v15` and
  `culinary-commerce-2026.08.07.v9`.
- Public museum responses cannot expose visual prompts, supplier terms, landed
  cost, margins, connector state, approval identities or private workflow data.
- Entity Studio remains private, requires `manage_options`, creates no role,
  exposes no public route and cannot delete dossier records or revisions.
- Entity Studio resolves exactly 521 subjects: 465 science identities plus 56
  product identities. Price-basis coverage is 56 of 56, comprising 36 current
  public WooCommerce prices and 20 private planning prices.
- The 17 earlier planning offers remain draft. The three Syrian product
  identities are private held market observations only and create no new offer,
  WooCommerce price, stock, supplier claim or active POS row.
- The Syrian module contains exactly 196 identities, including 56 dishes, 55
  ingredients, 21 regional or topic hubs, 17 techniques, 17 traditions and 15
  preparations, plus markets, restaurants, hospitality institutions and three
  private held market observations.
  One safe consumer gateway is
  `noindex,follow`; the other 195 Syrian entities remain private.
- The Lebanese foundation contains exactly 82 identities: 1 cuisine, 13 topic
  hubs, 27 dishes, 2 preparations, 8 ingredients, 5 techniques, 9 traditions,
  5 culinary institutions, 2 markets, 3 restaurants, 1 compliance rule and 6
  retail listings.
- All 82 Lebanese identities remain `editorial_draft`, `noindex_private`,
  `private_preview`, private-route and `reference_only`. They create no public
  page, API row, sitemap entry, WooCommerce product code, offer, stock,
  supplier, cross-sell or POS row.
- Each Lebanese identity references the central March 2026 Israel-Lebanon
  direct and indirect trade boundary. The six retail listings are dated
  external benchmarks observed on 2026-08-07 only, not offers, import routes,
  landed-cost evidence or availability promises.
- Shared Levantine dish and ingredient families remain separate country or
  regional identities linked for comparison. A Lebanese context record cannot
  merge with or establish exclusive origin over a Syrian or wider Levantine
  identity.
- The Iraqi foundation contains exactly 96 identities: 1 cuisine, 16 topic
  hubs, 32 dishes, 4 preparations, 12 ingredients, 8 techniques, 10 traditions,
  5 culinary institutions, 3 markets, 2 restaurants, 1 compliance rule and 2
  guides.
- All 96 Iraqi identities remain `editorial_draft`, `noindex_private`,
  `private_preview`, private-route and `reference_only`. They create no public
  page, API row, sitemap entry, WooCommerce product code, price observation,
  offer, stock, supplier, cross-sell or POS row.
- Each Iraqi identity references the central Israel-Iraq trade boundary.
  Shared dish families remain separate comparison identities, sabich and amba
  reuse existing owners, and the Iraqi tranche makes no exclusive-origin claim.
- Kibbeh nayyeh exposes no recipe, preparation instruction or consumption
  recommendation. Kishk, ambarees, labneh and qawarma process records remain
  held until measured safety validation. Druze wild-plant evidence is not
  permission to forage, self-identify or consume an unverified plant.
- Entity Studio records reject stale revisions, stale subject-scoped source
  digests, corrupt history links, mismatched subject identity and failed write
  readback.

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
- The 36 selected catalog images render as normal product media with no public
  archive label, caveat or unusual treatment.
- A live read-model image appears only with approved provenance and public-use
  rights.
- Every public catalog image has an exact source hash, product-code binding,
  owner approval and public-use receipt.
- Dish records remain private until bilingual editorial, kitchen, allergen,
  source and image checks pass.

## Read-model checks

- Signed model and item timestamps are valid and no more than 24 hours old.
- A model can be fresh only when its stored digest causally matches its complete
  canonical content. A valid timestamp or 64-hex string alone is insufficient.
- Health exposes digest, version and generation time only after complete shape
  and causal hash validation. It does not compute a replacement that could
  disguise missing or altered persisted state.
- The stored model is the unchanged normalized OS transport envelope plus only
  its top-level digest. The fixed cross-language fixture must produce SHA-256
  `b183d09588cb21c1374b5ec75d6d90fac836a49f5e1dbe030f01aa9d85d35410`
  from 810 canonical UTF-8 bytes in PHP and the OS runtime.
- New `generated_at` values use exact `YYYY-MM-DDTHH:mm:ss.sssZ` form. Every
  public item uses that same byte-identical `updated_at`, and millisecond order
  is monotonic.
- Only strict JSON booleans are accepted. String coercion is rejected before
  storage because it would change the OS transport digest.
- The one-time 1.7 migration gate preserves item ID and slug ownership, checks
  the old insertion-order digest when present, and narrowly recognizes the
  known stable 12-item live model when the legacy digest is absent.
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
- The public store contains exactly 36 owner-authorized catalog products and is indexable.
- Release 1.15.0 preserves all 36 public products unchanged and adds no public,
  private or POS offer, stock, supplier claim, payment activation or role
  assignment.
- The 12 new Japanese premium-market candidates remain private
  `research_candidate` records with planning stock zero, blank WooCommerce
  product codes, held projection and one held draft offer each.
- Entity Studio reports exactly 521 subjects, 56 product identities, 36 live
  prices, 20 private planning prices and 56 of 56 price-basis coverage.
- The three Syrian planning-price observations remain private and held, with no
  WooCommerce product code, channel offer, stock, supplier, landed-cost or
  margin claim.
- The six Lebanese retail observations remain science reference identities,
  not product identities or planning prices. They create zero active or draft
  offers and do not alter the 56-product identity count.
- The Iraqi foundation contains no retail or price observation and creates zero
  active or draft offers.
- Public source-market projection emits only exact explicit `public` variants.
  Missing, malformed, unknown and `held` values emit no public row.
- Release 1.9.0 adds exactly four owner-authorized offers to the previous
  32-item allowlist: Uozu Koshihikari rice, dried rice koji, Chouhaku-kin koji
  starter and Dutch-grown fresh wasabi.
- Each new offer has its bound dated source URL, Bank of Israel conversion when
  required, owner-authorized ILS price, one unit of opening stock, no backorders
  and an exact generated-image receipt.
- The 50 to 60 gram wasabi offer stores 0.060 kg as its operational shipping
  weight and exposes a 0.050 to 0.060 kg range in Product structured data.
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
  either store keeps all 36 products available. Receipt identity uses raw
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
- Anonymous public science readback remains exactly 23 entities across 18
  canonical page owners per language. No Lebanese identity, observed price,
  visual prompt, Product schema or Offer schema appears in public API, search,
  sitemap, museum projection, page source, catalog or POS output.
- Cache-busting requests to the Hebrew and English Lebanon canonical candidates
  do not render a public page while all 82 Lebanon entities remain private.
- Authenticated private readback proves 82 Lebanon identities with the exact
  type counts, all six observation timestamps and no offer or WooCommerce code.
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
