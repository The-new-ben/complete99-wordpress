# Launch QA

Release target: Complete99 Platform 1.22.0

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
- The production bridge embeds the exact artifact SHA-256, artifact byte count,
  release version and installed-tree SHA-256. The driver uploads the artifact in
  sequential chunks of at most 1 MiB and records only a bounded staging receipt.
- The staging route rejects gaps, overlaps, changed replays, malformed base64,
  oversized chunks, incomplete final state, unsafe paths, symbolic links,
  duplicate ZIP entries and expansion beyond its ceiling.
- A PHP runtime test accepts an exact 1 MiB canonical chunk using bounded
  non-regex validation, rejects noncanonical encodings and proves cleanup
  without recording the token or chunk payload.
- The install route refuses `package_base64`, requires a completed staged
  artifact, and rechecks its exact size and SHA-256 before and after claiming the
  deployment lease.
- The database snapshot manifest accepts exact historical v1 and v2 records and
  exact v3 records. V2 adds the seven-table `ops_tables` component; v3 adds the
  seven-table `campaign_tables` component. Hybrids, missing/extra components and
  either protected-table count other than seven are rejected.
- Protected rollback capture includes the seven allowlisted `c99_ops_*` tables,
  the seven allowlisted Campaign Studio tables, both schema markers, normalized
  schema digests and rows ordered by `id`. Each table is capped at 5,000 rows;
  each seven-table cohort has an independent 8 MiB ceiling.
- Campaign/Operations writes use the same deployment advisory boundary and exact
  canonical ordering as the bridge. Runtime hard limits are 4,500 rows/table and
  4 MiB/cohort; `writeReady` reserves 64 rows/table and 256 KiB/cohort. Public
  events remain below a 2,500-row/2.5 MiB sub-cap with five-hour ephemeral
  retention and never return protected capacity details.
- `/run` drains the shared advisory lock immediately before its real baseline.
  A pre-reservation writer is included in baseline, a post-reservation writer is
  rejected with 423, and success/failure leaves no advisory-lock residue.
- Campaign Studio is accepted before mutation only when its marker is exactly
  `complete99-campaign-schema/v1` and all seven tables exist, or when both marker
  and tables are wholly absent. Any partial or mixed cohort fails preflight and
  `/run` with no plugin change.
- A first-install rollback proves the absent baseline, atomically quarantines all
  candidate-created operations and Campaign tables in one rename boundary before
  the core database transaction, restores the exact baseline, removes only the
  separately fingerprinted quarantine cohorts and leaves zero `c99rb_*` residue.
- Historical v1/v2 journals are authenticated in their original byte shape
  before absent protected components are synthesized for comparison. Retry,
  rollback and cleanup require both recorded forward cohort digests; ambiguity,
  mutation or residue fails closed before redeploy or finalization.
- The serialized WordPress `cron` option is deliberately outside the rollback
  journal and bridge mutation scope. Campaign migrations do not schedule hooks;
  durable job state is persisted first, and normal `init` only enqueues a generic
  no-argument worker. Bounded repeated worker invocations rebuild/correct exact
  three-argument hooks and schedule the earliest future retry. Normal plugin
  deactivation keyset-suspends every scheduled/active job, compensates failures
  and proves zero unsuspended rows.
- Campaign acceptance covers command-bound exact external adapter artifacts,
  persisted preview/copy/download, protected idempotent evidence upload/stream,
  receipt/result/unverified-signal truth, versioned moderation transitions,
  double-submit and cancelled-prompt guards, locked cancel/expiry readback/event
  races, and zero generic cache flushes from anonymous event traffic.
- The repository-scoped `complete99-deploy` and `complete99-monitor` Windows
  runners live outside the checkout and synchronized user folders, use separate
  labels and environments, run exactly one listener each and use the installed
  Python 3.11 runtime without `actions/setup-python`. The monitor's 15-minute
  schedule is treated as queueable rather than real-time; a fresh inspectable
  durable heartbeat is required before owned placements are enabled.
- The separate consumer-media-rights overlay authenticates exactly thirteen
  `complete99_archive` photo stems and leaves paid/Campaign use unauthorized with
  empty receipt digests. Tamper/removal fails closed, the frozen consumer-menu
  digest remains valid, archive dishes are absent from Campaign choices and the
  exact-digest built-in brand illustration remains available.
- Every fresh/status/interrupted-forward/stabilize/finalize checkpoint requires
  and calls the Complete99 operations, Campaign Studio and Culinary Science
  invariants. Missing protected storage/capability or a fail-closed zero-route
  Museum overlay cannot finalize after a database-version short circuit.
- The public update manifest matches version 1.22.0 and its versioned package URL.
- The generated-asset manifest is treated as an editorial evidence registry,
  never as proof that a file is installed in the package.
- The default-deny Science media policy inventories exactly 47 stems and 175
  files: 28 public-delivery stems, 18 held repository-only stems and one approved
  superseded archive stem. The ZIP contains exactly 70 public delivery files and
  none of the 105 repository-only files: 78 held files, 24 active public PNG
  source-evidence files and three archive files. All 60 held derivatives from the
  earlier 1.20 candidate are absent, while the repository retains all 78 held
  files as evidence.
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
- The unfiltered pantry resolves into exactly three server-rendered pages of
  twelve unique products, without omissions or duplicates.
- Product-type filters and pagination work as ordinary links without
  JavaScript, and every control exposes a minimum 44 by 44 CSS-pixel target.
- The mobile-only store masthead and shelf introduction use compact headings
  and omit repeated supporting labels from the narrow visual flow without
  changing the server-rendered copy, any control target or any desktop rule.
  Fresh Hebrew and English Chrome acceptance at 390 by 844 must place each first
  product-card top coordinate at or before 820 CSS pixels.
- A paired read-only 390 by 844 candidate-CSS preview moved the English first
  card from 982.39 to 696.19 CSS pixels and the Hebrew first card from 840.41 to
  609.92, with zero visible link or button target violations. Treat this as
  pre-deploy geometry evidence, never as post-deploy acceptance.
- Filtered pantry states are `noindex,follow`; unfiltered page states preserve
  the store's current search eligibility. Canonical and Hebrew, English and
  x-default alternates reflect the exact validated state.
- Product schema contains only the current page's products. Every first-party
  product continuation resolves to the page that contains its stable anchor.
- Held catalog products emit Product schema without Offer. Offer appears only
  when both checkout and cart readiness pass; checkout and payments remain
  disabled in the 1.21 release state.
- The first product image on each shelf page is eager and high priority; later
  product images remain lazy.
- Add-to-cart from a filtered or paginated shelf returns to that same shelf
  state and preserves the product anchor.
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
- The unchanged raw v20 graph resolves 38 bilingual culinary-science routes from
  exactly 27 public entities through 19 standalone page owners per language and
  emits canonical and hreflang metadata.
- The digest-pinned search activation makes exactly 18 reviewed standalone
  owners and 36 bilingual canonical routes effectively indexable and includes
  those exact URLs in the Museum sitemap. The policy schema/version/digest are
  `complete99-culinary-science-search-activation/v1`,
  `culinary-science-search-activation-2026.08.11.v1` and
  `0b191bef1612e56f2e97c1e4e5d15ab4f651d8e658e2eb742aea72cc2a2ac6e7`.
- The cumulative registries identify themselves exactly as
  `culinary-science-2026.08.08.v20` under schema
  `complete99-culinary-science-registry/v6` and
  `culinary-commerce-2026.08.08.v14`.
- The science registry contains exactly 672 entities and 375 sources. The
  Japanese cluster contains exactly 84 entities split into 24 public and 60
  private records, while the public totals are 19 standalone owners per
  language and 38 bilingual routes. Effective search activation is exactly 18
  owners and 36 routes. `preparation-ichiban-dashi` remains `noindex,follow`;
  all eight section-only entities, query states and held/private records remain
  excluded. Missing or tampered policy state yields zero indexable routes.
- The five held Japanese editorial candidates are exactly shoyu koji, kioke,
  the koji-hydrolysis guide, JAS 1703 shoyu standard context and the enzymatic-
  hydrolysis reaction. They expose no anonymous route, bundle, relation or
  asset. Producer and retail-listing records stay private.
- Exactly three verified `literature_context` measurements remain private:
  neutral protease 500-700 U/g, acidic protease 50-150 U/g and leucine
  aminopeptidase 50-250 U/g. Each retains method, specimen scope, conditions,
  confidence, source and measurement date; public projection renders none.
- Cross-domain binding v3 contains exactly 95 unresolved records, split into 12
  dishes, 47 dish-scoped components and 36 Woo products, plus 11 private
  reciprocal Woo candidates. All five verified or public indexes are literally
  empty and no source registry is mutated. Its valid private decision overlay
  reports zero decisions and zero recognized reviewer authorities. The status
  schema/version are `complete99-cross-domain-binding-registry/v3` and
  `complete99-cross-domain-bindings-2026.08.08.v3`; the overlay schema/version
  are `complete99-cross-domain-binding-decision-overlay/v1` and
  `complete99-cross-domain-binding-decisions-2026.08.08.v1`.
- Public museum responses cannot expose visual prompts, supplier terms, landed
  cost, margins, connector state, approval identities or private workflow data.
- Entity Studio remains private, requires `manage_options`, creates no role,
  exposes no public route and cannot delete dossier records or revisions.
- Entity Studio resolves exactly 728 subjects: 672 science identities plus 56
  product identities. Price-basis coverage is 56 of 56, comprising 36 current
  public WooCommerce prices and 20 private planning prices.
- The 17 earlier planning offers remain draft. The three Syrian product
  identities are private held market observations only and create no new offer,
  WooCommerce price, stock, supplier claim or active POS row.
- The Syrian graph contains exactly 282 identities, including 77 dishes, 69
  ingredients, 25 regional or topic hubs, 30 techniques, 29 traditions, 16
  preparations, 10 guides, 11 culinary institutions, 5 markets, 6 restaurants
  and the preserved three private market records.
  Exactly one Syrian cuisine root is a public `noindex,follow` discovery surface
  and the other 281 Syrian identities remain private.
- The seven held Syrian editorial candidates are exactly Aleppo, the Aleppine
  kibbeh family, Syrian bulgur, Syrian lamb and beef, bulgur hydration, fully
  cooked kibbeh methods and Aleppan Jewish foodways. Anonymous routes,
  breadcrumbs, canonical output and semantic links expose none of them.
- `dish-kibbeh-meshwiyyeh` remains private, has pending culinary-test status,
  creates no public link and emits no Recipe schema. Public content provides no
  raw kibbeh method.
- The private `ingredient-syrian-bulgur` candidate records its relation to the
  existing exact `product-bulgur-fine-500g` offer at ILS 5.90, but emits no
  public link. Broad editorial candidates emit no Product or Offer schema.
- The science-asset collection contains exactly twelve held, non-public
  editorial entries: seven Syrian sets and five Japanese sets. The separate
  generated catalog collection remains exactly 60 entries and retains its
  product-image contract unchanged. Approval v2 binds the exact PNG source
  evidence separately from all four deployable WebP/AVIF variants and complete
  bilingual content. No trusted owner key or receipt exists; file receipts do
  not imply owner approval.
- The 86-identity Syrian expansion is exact: 30 west and central identities, 31
  east and south identities, and 25 community and institutional identities.
  All 86 are private, noindex and reference-only, with zero price, supplier,
  stock, public page, POS or ordering path.
- The four exact held identities remain fail closed for unresolved Arum,
  preservation, Palmyra identity and Hauran identity questions.
- The Lebanese graph contains exactly 203 identities: 1 cuisine, 25 topic hubs,
  58 dishes, 2 preparations, 23 ingredients, 3 molecules, 4 reactions, 19
  techniques, 6 equipment records, 27 traditions, 15 culinary institutions, 6
  markets, 6 restaurants, 1 compliance rule, 6 retail listings and 1 guide.
- The 121-identity expansion is exact: 61 coastal and northern identities and
  60 Bekaa, southern and community identities, with 77 new source records and
  no duplicate source ID, URL, entity ID or slug.
- The reviewed Lebanese cuisine root is `public_discovery`, `approved_public`,
  standalone and `noindex,follow`. The other 202 Lebanese identities, including
  all 121 new records, remain private, noindex and reference-only. No Lebanese
  identity creates a WooCommerce product code, offer, stock, supplier,
  cross-sell or POS row.
- Each Lebanese identity references the central March 2026 Israel-Lebanon
  direct and indirect trade boundary. The six retail listings are dated
  external benchmarks observed on 2026-08-07 only, not offers, import routes,
  landed-cost evidence or availability promises.
- Shared Levantine dish and ingredient families remain separate country or
  regional identities linked for comparison. A Lebanese context record cannot
  merge with or establish exclusive origin over a Syrian or wider Levantine
  identity.
- The twelve exact Lebanese held identities remain fail closed. Fish, raw meat,
  traditional dairy, cold chain, allergens, open fire, distillation, wild
  plants, food-grade calcium oxide and ceramic food contact retain separate
  machine-readable safety controls.
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
- Release 1.17.0 preserves all 36 public products unchanged and adds no public,
  private or POS offer, stock, supplier claim, payment activation or role
  assignment.
- Release 1.18.0 preserves the same 36 public products and adds no public,
  private or POS offer, stock, supplier claim, payment activation or role
  assignment.
- The 12 new Japanese premium-market candidates remain private
  `research_candidate` records with planning stock zero, blank WooCommerce
  product codes, held projection and one held draft offer each.
- Entity Studio reports exactly 728 subjects, 56 product identities, 36 live
  prices, 20 private planning prices and 56 of 56 price-basis coverage.
- The three Syrian planning-price observations remain private and held, with no
  WooCommerce product code, channel offer, stock, supplier, landed-cost or
  margin claim.
- The six Lebanese retail observations remain science reference identities,
  not product identities or planning prices. They create zero active or draft
  offers and do not alter the 56-product identity count.
- The Iraqi foundation contains no retail or price observation and creates zero
  active or draft offers.
- The 86 new Syrian identities contain no retail or price observation and
  create zero active or draft offers.
- The 121 new Lebanese identities contain no retail or price observation and
  create zero active or draft offers.
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
- the versioned menu-stated component section on all 12 dish pages, including
  exactly seven rendered components on the Sabich page;
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

- No deployment or live-publication result is claimed by this checklist. Any
  infrastructure deployment must use protected `main`, green required CI and
  the controlled workflow, with all held Science content absent from the ZIP
  and private at runtime.
- Publication of any of the twelve held candidates remains blocked. Approval v2
  requires a trusted owner key and signed receipt binding the exact PNG source
  evidence separately from the four deployable WebP/AVIF variants and complete
  bilingual content. A later approved candidate requires a newly reviewed and
  rebuilt artifact containing those four exact delivery variants.

- Selected HTTPS origin is the canonical production domain.
- UPress REST is enabled.
- Health returns expected component, version, deployment ID and database version.
- Temporary deployment row is absent and its route returns 404.
- Cache-busting requests render the new consumer body and release marker.
- `robots.txt`, sitemap and public catalog return their managed state.
- Public search does not expose products or private content types.
- Anonymous public science readback contains exactly 27 entities across 19
  standalone page owners per language and 38 bilingual routes. Only the
  reviewed Lebanese cuisine root appears from the Lebanon graph. Only the
  pre-existing Syrian cuisine root appears from the Syrian graph; the seven
  Aleppo candidates remain absent. The three release-1.18 Japanese records
  appear only as reviewed sections inside their existing dashi and umami
  owners; the five release-1.20 Japanese candidates and all three private
  measurements remain absent. No private price, visual prompt,
  Product schema or Offer schema appears in public API, search, sitemap, page
  source, catalog or POS output.
- Cache-busting requests to the Hebrew and English Lebanon canonical paths
  render the reviewed cuisine gateway with reciprocal museum and Syria links,
  while child Lebanon paths remain unavailable publicly.
- Cache-busting requests to all seven proposed Hebrew and English Syrian owners
  must remain unavailable and render no candidate imagery, canonical, hreflang,
  semantic continuation or Product/Offer output. The private bulgur relation
  must not reach the existing offer publicly.
- Authenticated private readback proves 203 Lebanon identities with the exact
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
