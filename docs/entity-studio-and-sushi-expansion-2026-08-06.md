# Complete99 Entity Studio Phase 1 and Sushi Expansion Plan

Classification: private internal planning document. This is not public website copy, a supplier commitment, a product activation instruction, or authority to expose an offer.

Date: 2026-08-06

## 1. Decision summary

Complete99 remains one WordPress and WooCommerce system with several deliberately separated authorities:

1. The checked-in culinary science registry owns the reviewed knowledge graph, evidence links, editorial relationships, SEO ownership and publication controls.
2. The checked-in culinary commerce registry owns private source-market observations, seller and product identities, variants, SKUs and evidence pointers.
3. Entity Studio Phase 1 stores private, administrator-reviewed commercial dossiers as a copy-on-write overlay. It does not replace either checked-in registry and does not publish anything.
4. WooCommerce is the operational authority for an accepted product's public price, inventory, cart, order, refund and stock movements.
5. Public website, app, kiosk and POS surfaces consume approved projections. They do not become parallel sources of truth.

The current Phase 1 design uses one private custom post type, `c99_entity_dossier`, with retained WordPress revisions and a digest-linked revision history. It is a private review overlay inside WordPress. It is not a separate ledger, release system or public catalog authority.

The first expansion cohort is a sushi topic cluster beneath the existing Japanese Washoku cuisine graph. It must improve intent ownership and internal linking without creating a competing public canonical or implying that private products are available for purchase.

## 2. Repository baseline

The plan is grounded in the following current repository contracts:

| Authority | Current repository anchor | Current boundary |
|---|---|---|
| Culinary science graph | `plugin/complete99-platform/data/culinary-science-pilot.php` | Versioned bilingual entities, facts, sources, relationships, public projections, SEO ownership and publication flags |
| Science runtime | `plugin/complete99-platform/includes/class-complete99-culinary-science.php` | Immutable registry loading, validation and allowlisted public projection |
| Culinary commerce graph | `plugin/complete99-platform/data/culinary-commerce-pilot.php` | Ten private research candidates, dated source-market observations, five draft channel offers and zero active channel offers |
| Catalog evaluation seeds | `plugin/complete99-platform/data/catalog-product-seeds.php` | Private evaluation prices and stock; `public_sale_eligible=false`; `sale_state=held_until_acceptance` |
| Entity Studio Phase 1 | `plugin/complete99-platform/includes/class-complete99-entity-studio.php` | Private administrator dossier editor and authenticated API; no public projection and no WooCommerce write |
| Read-only editorial review | `plugin/complete99-platform/includes/class-complete99-review-lab.php` | Review Lab remains read-only |
| SEO ownership | `plugin/complete99-platform/includes/class-complete99-seo-registry.php` | Canonical, intent and language ownership collision detection |
| Private product planning | `c99_product_plan` in `plugin/complete99-platform/includes/class-complete99-commerce.php` | Non-public planning records |
| Evaluation materialization | `plugin/complete99-platform/includes/class-complete99-evaluation-catalog.php` | Private evaluation catalog only |
| Live WooCommerce materialization | `plugin/complete99-platform/includes/class-complete99-live-catalog.php` and `plugin/complete99-platform/data/live-catalog-prices.php` | Explicit load, preflight, dry-run, apply and status workflow; 36 accepted live product prices in the current release |
| Architecture policy | `docs/complete99-commercial-entity-architecture.md` and `docs/architecture.md` | WordPress, WooCommerce and private evidence responsibilities remain separate |

The science registry is schema v5, version `japanese-pilot-2026.08.06.v10`. The commerce registry is schema v2, version `japanese-commerce-pilot-2026.08.06.v4`. Current counts at the time of this plan are 73 science entities and 53 science sources, with 22 entities approved for public preview and 51 private-preview entities. The commerce registry contains 10 research-candidate products, five private channel offers in `draft` and zero channel offers in `active`. Entity Studio resolves 41 product subjects with a price basis: 36 live WooCommerce catalog prices plus five commerce-only private draft ILS plans. This is 41 of 41 product-subject price coverage, not 41 live offers. Public visibility, purchase state and search indexing remain separate.

## 3. Source-of-truth contract

### 3.1 Knowledge and editorial truth

The culinary science registry is the baseline for a knowledge entity's stable identity, bilingual name, evidence-backed facts, scientific limits, relationships, taxonomy, canonical owner and publication state. Entity Studio may attach a commercial dossier to that identity, but it must not rewrite scientific facts, medical or nutrition claims, canonical paths, publication state, evidence confidence or public link allowlists.

### 3.2 Research and market-observation truth

The culinary commerce registry is the baseline for source-market observations. Every observation retains its source URL, seller, market, currency, observed amount, tax state, shipping state, availability state, normalization formula, claim locator and checked date.

A source observation is not:

- a supplier quote;
- a current Israeli procurement cost;
- an importable quantity;
- a landed cost;
- a public retail price;
- proof of local inventory;
- an active channel offer.

The current commerce evidence artifacts are `source_reviewed`, retained as `source_pointer_only`, and have `offer_approval_eligible=false`. Source pages can change after the checked date. A retained snapshot or signed supplier document is a separate evidence upgrade.

### 3.3 Internal commercial-decision truth

Entity Studio owns the latest approved internal dossier revision for a stable subject. Its scope is commercial role, pricing applicability, planned price in minor units, planning market, planning channel, currency, quality tier, source-observation references, proposed cross-sells, proposed up-sells, bilingual value proposition, bilingual price rationale and a private note.

Entity Studio approval means only that the dossier passed its internal review contract. It does not alter science publication, SEO indexation, commerce readiness or WooCommerce state.

### 3.4 Operational commerce truth

WooCommerce owns the accepted public offer after all gates pass. For an activated SKU, WooCommerce is authoritative for public sell price, tax configuration, inventory quantity, stock status, cart behavior, order state, refund state and inventory movement.

The numeric presence of a value in a planning or price file does not, by itself, authorize public exposure. The catalog seed flags and the live-catalog acceptance workflow still govern activation.

### 3.5 Channel projections

The website, app, kiosk, POS and B2B interfaces receive channel-specific projections with stable IDs and revision metadata. They may submit authorized commands through validated APIs, but they must not silently create a second product, price or inventory record.

## 4. Entity Studio Phase 1 boundary

### 4.1 Included in Phase 1

Phase 1 provides infrastructure for reviewed internal dossiers:

- One private `c99_entity_dossier` post per stable graph subject.
- `public=false`, `publicly_queryable=false`, `exclude_from_search=true`, `show_in_rest=false`, no rewrite and no archive.
- Access through `manage_options` only, without creating or assigning a new role.
- A private administration screen at `/wp-admin/tools.php?page=complete99-entity-studio`.
- An authenticated custom REST route at `/wp-json/complete99/v1/editorial/entity-studio`, protected by the same administrator capability.
- Copy-on-write creation. Loading a bundled subject does not mutate the registry. The first save creates the private dossier.
- A scoped baseline envelope for the selected subject. Its SHA-256 digest covers schema, version, record kind, stable ID and that subject's scoped source payload, rather than the whole registry.
- A combined scoped baseline only when the catalog and commerce records have the same stable product identity.
- A strict versioned dossier schema with controlled values, bounded relation lists and exact bilingual fields.
- Integer minor-unit pricing, explicit market, channel and currency, and required source-observation references for benchmarked or owner-authorized price plans.
- Exact expected-revision and expected-base-digest checks to reject stale writes.
- Explicit rebase only when the reviewer requests it, and only into the `draft` workflow state. An approved payload must return to `draft` before it can change.
- A concise change reason and correlation identifier for every accepted write.
- SHA-256 payload, event and record digests, including prior record and prior event links.
- A database transaction for the post and metadata write, followed by metadata and record read-back verification before commit. A verification failure rolls the transaction back.
- Retention of all dossier revisions through the WordPress revision policy.
- Revision-chain verification of the root, revision sequence, prior record digest and prior event digest. History is reported as `verified`, `corrupt`, `truncated` or `incomplete`.
- Blocking of trash and deletion for dossiers and their revisions through capability and pre-delete controls.
- MySQL advisory locking, with a filesystem lock for the supported SQLite development path.

### 4.2 Explicitly excluded from Phase 1

Phase 1 does not:

- change the science or commerce PHP registries;
- create a public entity page;
- change canonical paths, breadcrumbs, schema markup, hreflang, sitemap inclusion or indexability;
- publish Entity Studio data through an unauthenticated REST route;
- create or modify a WooCommerce product, variation, SKU, price, inventory quantity, cart or order;
- call the live catalog materializer;
- approve a supplier, import route, label, certificate, cold chain, tax class, shipping class or return policy;
- calculate or claim procurement cost, landed cost, contribution margin or profitability;
- generate a public CTA from a proposed cross-sell or up-sell;
- synchronize with an app, kiosk, POS, delivery marketplace or payment provider;
- import existing dossiers automatically during activation or migration;
- operate a separate release artifact or activation service;
- permit trashing or hard deletion of dossier history.

### 4.3 Copy-on-write and baseline drift

The logical workspace identity is:

`domain + record_kind + stable_id`

The current implementation indexes subjects by `stable_id`, so stable IDs are globally unique across the loaded science, catalog and commerce subject set. `workspace_identity` records the contributing source identity, such as `catalog:product:{id}` or `catalog:product:{id}|commerce:product:{id}`. Catalog and commerce payloads merge only when they have the same stable ID and both are the same product identity. A collision with a science entity or any other record kind fails closed with `entity-studio-subject-identity-collision`.

The saved dossier pins the subject-scoped baseline schema, version and digest. If that checked-in source payload changes, Entity Studio marks the dossier baseline as stale. It does not silently rebase or copy new registry facts into the saved dossier. A reviewer must compare the change, request rebase explicitly, return the dossier to `draft` and save a new revision with a reason.

Stable IDs do not change when names, slugs, translations or routes change. Knowledge entity IDs, product IDs, variant IDs, SKU IDs, listing IDs, source-observation IDs and WooCommerce product codes remain different identifier classes.

### 4.4 Revision-chain boundary

Phase 1 stores a digest-linked event envelope inside each WordPress-backed dossier revision. Save validation checks the current scoped base digest, expected revision, allowed workflow transition, actor, source, reason, correlation ID and changed field paths. Persistence uses a transaction, verifies post metadata and the reconstructed record, and commits only after read-back succeeds. History verification checks the root and every prior-record and prior-event link.

This is revision-backed chained dossier history inside WordPress. It must not be described as an independent ledger, a catalog compiler or a release activation system. No such component is claimed by this plan.

Trash and deletion are blocked for the dossier and its revisions. Any future lifecycle-state change requires a separately reviewed contract and must preserve the existing chain.

## 5. Independent state machines

The interface and API must show these as separate controls. No state in one machine automatically advances another.

| Machine | States | Meaning |
|---|---|---|
| Studio workflow | `draft` -> `in_review` -> `approved` | Internal dossier completeness and reviewer decision |
| Evidence | `research_draft` -> `source_reviewed` -> `verified` | Strength and retention of evidence for a fact or observation |
| Editorial publication | `research_draft` -> `private_preview` -> `approved_public` | Eligibility for a public content projection; public page, public API and search index remain separate flags |
| Commerce product | `research_candidate` -> `verified_product` -> `active` -> `retired` | Product identity and verification state in the commerce registry |
| Commerce SKU | `research_candidate` -> `verified_sku` -> `active` -> `retired` | Exact SKU verification and lifecycle state |
| Channel offer | `draft` -> `approved` -> `active` -> `paused` -> `retired` | Market and channel price offer lifecycle; all five current offers are `draft` |
| WooCommerce materialization | `not_materialized` -> `dry_run_passed` -> `applied` -> `acceptance_passed` -> `active` | Operational creation and acceptance of the WooCommerce object |

Current commerce product, variant and SKU records use `research_candidate`. Current catalog seeds retain `held_until_acceptance` evaluation controls, while the separately accepted live catalog contains 36 public WooCommerce products. Those authorities must remain explicit and must not be silently renamed or collapsed.

Forbidden implications include:

- `source_reviewed` does not mean supplier verified.
- `approved` in Entity Studio does not mean `approved_public`.
- `approved_public` does not mean search indexed.
- `private_draft_plan` and the `owner-authorized-planning` tier do not mean an active WooCommerce price.
- `verified_sku` does not mean inventory is available.
- a WooCommerce product record does not mean an offer is active on every channel or in every country.

## 6. Sushi hub-and-spoke ownership map

### 6.1 Structural decision

The existing `cuisine-japanese-washoku` entity remains the Japanese cuisine authority. A proposed `hub-japanese-sushi` record should begin as a private, noindex editorial section beneath it. In the first cohort its route mode is `section`, its canonical owner is `cuisine-japanese-washoku`, and it has no independent public URL.

The sushi hub owns only the orientation intent: understanding the Complete99 sushi ecosystem and choosing the correct branch. It does not own the individual intent of a dish, recipe, ingredient, equipment guide, scientific guide, standard, comparison or product SKU.

| Entity or proposed entity | Intent owner | Current or proposed path | Current surface decision | Required relationship |
|---|---|---|---|---|
| `cuisine-japanese-washoku` | Japanese culinary science, culture and top-level navigation | `/museum/japanese-culinary-science/`; `/en/museum/japanese-culinary-science/` | Approved public preview, currently noindex | Parent of the private sushi section |
| `hub-japanese-sushi` | Sushi ecosystem orientation and branch selection | No independent path in Phase 1 | New private section, noindex, no public API | Contains only reviewed sushi spokes; canonical remains with Washoku |
| `dish-edomae-nigiri` | What Edomae nigiri is and how the dish system is structured | `/dishes/edomae-nigiri/`; `/en/dishes/edomae-nigiri/` | Private preview | Requires shari and species-specific handling; complements shoyu and wasabi; references yanagiba skill |
| `preparation-sushi-shari` | How sushi shari is specified, cooked, seasoned and controlled | `/knowledge/sushi-shari/`; `/en/knowledge/sushi-shari/` | Private preview | Uses rice, a reviewed sushi seasoning specification and hangiri; recipe and lot measurements remain separate |
| `ingredient-koshihikari-rice` | Koshihikari identity, origin, cultivar and product selection limits | `/ingredients/koshihikari-rice/`; `/en/ingredients/koshihikari-rice/` | Approved public preview, currently noindex | Possible shari ingredient; links to exact packs only after SKU acceptance |
| `ingredient-yakinori` | Premium yakinori identity, grades, use and storage | `/ingredients/premium-yakinori/`; `/en/ingredients/premium-yakinori/` | Private preview | Links to relevant sushi forms without claiming every nigiri requires nori |
| `ingredient-sushi-su` | Proposed owner for sushi vinegar or sushi seasoning identity | No route until evidence and collision review | New research draft, private, noindex | Must separate brewed vinegar identity, seasoning formula and measured recipe specification |
| `ingredient-kioke-shoyu` | Kioke shoyu identity, fermentation and product-specific limits | `/ingredients/kioke-shoyu/`; `/en/ingredients/kioke-shoyu/` | Approved public preview, currently noindex | Complements Edomae service; references JAS context without treating the standard as product approval |
| `standard-jas-shoyu-1703` | JAS 1703 shoyu classification intent | `/knowledge/jas-1703-shoyu-standard/`; `/en/knowledge/jas-1703-shoyu-standard/` | Private preview | Supports the shoyu category only; does not certify a product |
| `ingredient-fresh-wasabi` | Fresh wasabi rhizome identity, origin and lot variability | `/ingredients/fresh-wasabi-rhizome/`; `/en/ingredients/fresh-wasabi-rhizome/` | Approved public preview, currently noindex | Links to grater and AITC guide; exact product requires cold-chain acceptance |
| `guide-wasabi-aitc` | AITC and fresh-wasabi pungency science | `/knowledge/wasabi-aitc-pungency/`; `/en/knowledge/wasabi-aitc-pungency/` | Approved public preview, currently noindex | Connects molecule, ingredient, grating process and equipment without health promises |
| `equipment-wasabi-grater` | Wasabi grater purpose, material, dimensions, care and safety | `/knowledge/wasabi-grater-guide/`; `/en/knowledge/wasabi-grater-guide/` | Approved public preview, currently noindex | Links to fresh wasabi and exact equipment SKU only after food-contact review |
| `equipment-hangiri` | Hangiri selection, use, material, care and size | `/knowledge/hangiri-guide/`; `/en/knowledge/hangiri-guide/` | Private preview | Supports shari; exact tool listing remains separate |
| `equipment-yanagiba` | Yanagiba identity, geometry, use, care and safety | `/knowledge/yanagiba-guide/`; `/en/knowledge/yanagiba-guide/` | Private preview | Supports slicing skill; does not own steel comparison intent |
| `comparison-yanagiba-steels` | White 2 versus Blue 1 comparison intent | `/knowledge/yanagiba-white-2-vs-blue-1/`; `/en/knowledge/yanagiba-white-2-vs-blue-1/` | Private preview | Compares like-for-like specifications without inferring universal superiority |
| `ingredient-kito-yuzu` | Kito yuzu identity, origin eligibility and product-form distinctions | `/ingredients/kito-yuzu/`; `/en/ingredients/kito-yuzu/` | Approved public preview, currently noindex | Optional seasoning branch; fruit, juice and exact commercial pack remain separate |
| Future seafood species and handling entities | Exact species, cut, treatment, receiving and safety intent | No route until qualified | Later regulated cohort, private by default | Each species, product, lot and process must remain distinct |

### 6.2 Breadcrumb rule

Visible public breadcrumbs must use real, public route owners. Until a standalone sushi hub is approved and published, an entity keeps its existing section path, for example:

- Home -> Dishes -> Edomae nigiri
- Home -> Knowledge Centre -> Sushi shari
- Home -> Ingredients -> Koshihikari rice
- Home -> Knowledge Centre -> Wasabi grater guide

The interface must not display a breadcrumb to a missing `/sushi/` URL. A private section can organize the graph without becoming a public breadcrumb node.

### 6.3 Internal-link rule

Links are generated from reviewed semantic relations and intent ownership, not from keyword repetition. The minimum logic is:

1. The sushi section points to each approved spoke with a descriptive bilingual anchor.
2. Edomae nigiri points to shari, relevant ingredients, knife skill and food-safety context.
3. Shari points to rice, the future sushi-su entity, hangiri and the verified recipe specification.
4. Fresh wasabi links to AITC science and the grater guide. The grater guide links back to the ingredient.
5. Shoyu links to JAS context, while the standard clearly states that it does not approve a specific product.
6. A knowledge entity may link to an exact WooCommerce SKU only when that SKU is accepted for the user's market and channel.
7. Product modules may link back to the explanatory entity, but they may not replace its informational owner.
8. Every proposed public link must pass destination visibility, locale, canonical-owner and collision checks.

## 7. Live price baseline and research cohorts

### 7.1 Existing live WooCommerce products

The six products in the earlier draft of this section are already part of the 36-product live WooCommerce catalog. Their owner-authorized opening retail prices are recorded in `live-catalog-prices.php`. They must not be labeled private, held or non-sale in this plan.

| Live product identity | Current live ILS price |
|---|---:|
| `product-rishiri-kombu-100g` | 89 |
| `product-honkarebushi-200g` | 219 |
| `product-yamaroku-tsurubishio-500ml` | 149 |
| `product-kito-yuzu-juice-100ml` | 64 |
| `product-fresh-japanese-wasabi-250g` | 399 |
| `product-hagane-zame-large` | 699 |

Source observations and catalog evaluation metadata can still support review, provenance and refresh work for these six products. Their current public price and purchasability authority is WooCommerce and the accepted live catalog workflow, not the private research-candidate rules below.

### 7.2 Cohort A: five existing commerce-only research candidates

The commerce registry v4 already contains these five product subjects and five Israel web-channel offer plans. Every offer is `draft`, uses market `market-il-launch`, channel `channel-woo-web-il`, currency `ILS`, tier `owner-authorized-planning`, gross tax-inclusive price basis, `research_only` stock policy, `supplier_onboarding_required` fulfillment policy, held kiosk availability and an empty WooCommerce product code. All five evidence artifacts are `source_reviewed`, retained as `source_pointer_only`, and have `offer_approval_eligible=false`. There are zero active channel offers. Landed-cost and margin scenario IDs are empty.

| Commerce-only subject and draft offer | Source-market observation retained in v4 | Internal draft ILS plan | Required decision gates |
|---|---|---:|---|
| `product-honkarebushi-belly-200g`<br>`offer-plan-honkarebushi-belly-200g-il-web-v1` | `observation-honkarebushi-belly-200g-20260806` and `evidence-honkarebushi-belly-200g-20260806`. Approx. 200 g, USD 33.00, tax and shipping unknown, source marked in stock, approx. USD 165/kg, partially comparable. [Source](https://int.japanesetaste.com/products/honkarebushi-whole-japanese-katsuobushi-block-bonito-belly-200g) | 219 | Resolve likely overlap with live `product-honkarebushi-200g` before any second Woo object. Confirm supplier, exact pack, fish allergen, import label, nutrition, lot, expiry, storage, kosher status, tax, shipping, media and returns. [COMPLIANCE_NOTE: Fish allergen and import-label review are required.] |
| `product-fukumitsuya-hon-mirin-3y-720ml`<br>`offer-plan-fukumitsuya-hon-mirin-3y-720ml-il-web-v1` | `observation-fukumitsuya-hon-mirin-3y-720ml-20260806` and matching evidence ID. 720 ml, USD 39.99, tax and shipping unknown, source marked in stock, USD 55.54/l, like-for-like. The 13.5 to 14.5 percent ABV value is a listing claim. [Source](https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-3-years-traditionally-aged-sweet-rice-wine-720ml) | 249 | Confirm exact SKU, supplier documents, alcohol classification, age and license rules, import label, ingredients, allergens, ABV, nutrition, lot, expiry, storage, tax, shipping, kosher status and media. [COMPLIANCE_NOTE: Alcohol age, licensing and import review are required before activation.] |
| `product-fukumitsuya-hon-mirin-10y-720ml`<br>`offer-plan-fukumitsuya-hon-mirin-10y-720ml-il-web-v1` | `observation-fukumitsuya-hon-mirin-10y-720ml-20260806` and matching evidence ID. 720 ml, USD 54.99, tax and shipping unknown, listed for sale, USD 76.38/l, like-for-like. [Source](https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-10-year-aged-sweet-rice-seasoning-720ml) | 349 | Confirm exact SKU, supplier documents, alcohol classification, age and license rules, import label, ingredients, allergens, ABV, nutrition, lot, expiry, storage, tax, shipping, kosher status and media. [COMPLIANCE_NOTE: Alcohol age, licensing and import review are required before activation.] |
| `product-kito-yuzu-juice-720ml`<br>`offer-plan-kito-yuzu-juice-720ml-il-web-v1` | `observation-kito-yuzu-juice-720ml-20260806` and matching evidence ID. 720 ml, JPY 3,780 including tax, shipping unknown, price listed, JPY 5,250/l, partially comparable. [Source](https://shop.ogonnomura.jp/view/item/000000000199?category_page_id=ichiban) | 199 | Treat as a distinct 720 ml pack in the same family as the live 100 ml product. Confirm exact pack, supplier, barcode, ingredients, label, origin claims, allergens, nutrition, storage, lot, expiry, tax, shipping, kosher status and media. |
| `product-umezawa-hangiri-36cm`<br>`offer-plan-umezawa-hangiri-36cm-il-web-v1` | `observation-umezawa-hangiri-36cm-20260806` and matching evidence ID. One 36 cm sawara cypress hangiri without lid, USD 129.00, tax unknown, shipping excluded, source marked in stock, like-for-like. [Source](https://japanesetaste.com/products/umezawa-sawara-cypress-hangiri-wooden-sushi-oke-bowl-36cm) | 649 | Confirm supplier, model, dimensions, material, finish, food-contact documentation, cleaning and care, tax, shipping, returns, warranty and media. [COMPLIANCE_NOTE: Food-contact material review is required before activation.] |

### 7.3 Cohort B: next researched candidate queue

These are private research hypotheses that have not been added to the checked-in commerce registry. The proposed ILS amounts are internal planning hypotheses only. They are not supplier quotes, active offers, landed-cost calculations, margin claims or evidence-retained registry observations. Each net-new candidate must receive a stable product, variant and SKU decision, a dated observation, a retained evidence record and all named gates before import.

| Proposed candidate | Current web observation checked 2026-08-06 | Internal ILS hypothesis | Queue decision and gates |
|---|---|---:|---|
| Maruyama Nori Gokujo Kontobi, 5 full sheets<br>Provisional ID `product-maruyama-gokujo-kontobi-nori-5-sheets` | Current listing check surfaced JPY 1,350 including tax, five full sheets, product number `200660-C157`, domestic Japanese dried nori, six-month unopened shelf life and add-to-cart. The listing notes that the price changed, while an older cached view showed JPY 1,100. [Source](https://www.maruyamanori.com/c/kontobi_n/200660-C157) | 99 | Recheck the exact live amount at capture time. Confirm seller, product number, barcode, harvest, species, origin, grade, ingredients, allergen and cross-contact status, nutrition, moisture and storage, lot, expiry, import label, kosher status, tax, shipping and licensed media. |
| Tajima Jozo red sushi vinegar, 360 ml<br>Provisional ID `product-tajima-red-sushi-vinegar-360ml` | Japanese Taste listed JPY 761, variant `51024628613407`, in stock, with grain vinegar, sugar and salt. The producer separately identifies a 360 ml Red Vinegar Sushi Vinegar made from aged sake lees with Japanese sugar and salt. [Retail source](https://japanesetaste.jp/products/tajima-jozo-premium-akazu-aged-red-vinegar-for-sushi-360ml), [producer source](https://tajimajozo.co.jp/en/category/seasoned_vinegar/) | 119 | Hold for SKU and content-identity conflict. Do not model this as pure Akazu while the observed label describes a seasoned sushi vinegar. Resolve exact JAN or barcode, variant, formula, acidity, sugar, salt, nutrition, supplier, import label, storage, lot, expiry, kosher status, tax, shipping and media. |
| Minamigura Tamari Warabeuta, 200 ml<br>Provisional ID `product-minamigura-warabeuta-tamari-200ml` | Nishikidori displayed EUR 10.60 tax included, in stock, reference `NISTAMIN9`, with 200 ml, 720 ml and 1.8 l pack options. The page identifies whole soybeans, Umi no Sei sea salt, soy allergen, best-refrigerated storage and a three-year aging claim. For the 200 ml option the normalized display is EUR 53/l. [Source](https://www.nishikidori.com/en/1611-1899-soy-sauce-tamari-warabeuta.html) | 119 | Capture the selected 200 ml variant rather than the shared product page. Confirm exact seller SKU and barcode, pack, supplier, soy allergen, any wheat-free claim, ingredients, nutrition, salt, label, storage, lot, expiry, kosher status, tax, shipping and media. |
| Umezawa sawara hangiri, 36 cm<br>Existing ID `product-umezawa-hangiri-36cm` | The USD 129 observation and v4 evidence are already recorded in Cohort A. [Source](https://japanesetaste.com/products/umezawa-sawara-cypress-hangiri-wooden-sushi-oke-bowl-36cm) | 649 | Reconciliation overlap, not a sixth net-new subject. Do not create a duplicate product, variant, SKU, observation or plan. Complete the Cohort A gates only. |
| Yamaco bamboo makisu<br>Provisional family ID `product-yamaco-bamboo-makisu` | Australian reseller displayed AUD 28, add-to-cart, three left, 24 cm and 27 cm variants, bamboo and cotton, made in Japan. Shipping is calculated at checkout from Perth and duties and taxes are excluded. [Source](https://www.mujostore.com/products/bamboo-sushi-mat) | 129 | Select an exact size and SKU. If both sizes are kept, create separate variants under one verified product family. Confirm supplier, barcode or seller SKU, food-contact material and finish, care and hygiene, safety, tax, shipping, returns, warranty and media. |
| Sakai Takayuki Ginsan Yanagiba, nominal 270 mm<br>Provisional ID `product-sakai-takayuki-ginsan-yanagiba-270mm` | Australian reseller displayed AUD 399.95 for SKU `SATA-G3-YA270`. The exact page identifies a single-bevel Ginsan or Silver 3 knife, measured edge length 265 mm, HRC 60 to 62 and ho-wood handle. It was backordered and sold out, with pickup-only availability shown. [Source](https://www.knivesandstones.com.au/products/sakai-takayuki-ginsan-yanagiba-270mm) | 1,799 | Do not claim availability or performance superiority. Confirm exact line, steel, handedness, dimensions, SKU, supplier, stock, tax, import duties, shipping, sharp-tool safety, care, warranty, returns and media. [COMPLIANCE_NOTE: Sharp-tool handling, storage and age-policy review are required for the intended sales channel.] |

The price-coverage denominator remains the current Entity Studio subject index: 36 live catalog product prices plus five private Cohort A plans equals 41 of 41 product subjects with a price basis. Cohort B is outside that denominator until reviewed identities are imported. Because the Umezawa record is an overlap, Cohort B contains five possible net-new product subjects, not six.

## 8. Identity collision and reconciliation

The logical design distinguishes `domain + record_kind + stable_id`. The current Entity Studio implementation nevertheless indexes by stable ID alone and therefore enforces global stable-ID uniqueness. It fails closed when a catalog ID collides with a science ID. A commerce record can merge into an existing subject only when the existing subject is a product with the same stable product ID. That valid catalog-plus-commerce merge receives a combined scoped base digest. Similar names, URLs, pack sizes or prices never authorize a merge.

The immediate reconciliation queue is:

| Identity issue | Current fact | Required action |
|---|---|---|
| Honkarebushi | Live catalog product `product-honkarebushi-200g` is priced at ILS 219. Commerce-only `product-honkarebushi-belly-200g` has a private draft ILS 219 plan and points to the same approximate 200 g belly-block source listing. | Hold the private offer. Decide whether it is the same product, a distinct cut or an unresolved listing identity. If the same, retain one canonical live product and preserve the other ID as a reviewed alias. Do not create a second WooCommerce object. |
| Tajima red sushi vinegar | The retail title uses Akazu language, while the observed ingredients identify a seasoned vinegar with grain vinegar, sugar and salt. The producer also names the product Red Vinegar Sushi Vinegar. | Hold the candidate until the exact JAN, label, formulation and product class agree. Do not use a pure-Akazu identity or claim for a seasoned product. |
| Umezawa hangiri | `product-umezawa-hangiri-36cm` already exists in Cohort A with an observation, evidence pointer and draft plan. It also appeared in the next research queue. | Treat the second appearance as a reconciliation reminder. Do not create a duplicate subject, variant, SKU or plan. |
| Kito yuzu pack family | `product-kito-yuzu-juice-100ml` is live at ILS 64. `product-kito-yuzu-juice-720ml` is a commerce-only product with a private draft ILS 199 plan. | Preserve distinct pack identities and relate them under one product family after exact SKU review. Do not merge the 100 ml and 720 ml packs. |
| Yamaco makisu sizes | The observed source page offers both 24 cm and 27 cm sizes under one page. | Create one verified product family with exact size variants, or select one exact variant. Do not assign a sellable SKU to an unresolved size. |

Required alias record fields are:

- alias ID;
- canonical ID;
- source registry;
- match basis;
- reconciliation reason;
- reviewer;
- effective timestamp;
- superseded revision or digest.

This is not an instruction to delete unmatched records. Each record remains intact until a reviewer classifies it as a true duplicate, a distinct pack, a distinct variant, a source listing, or an unrelated candidate.

## 9. Modular international expansion

International scale must add adapters around stable global identities. It must not clone the knowledge graph for every country.

| Module | Owns | Must not own |
|---|---|---|
| Global identity | Stable entity, product family, scientific facts, universal relationships | Local sell price, local stock or local legal status |
| Country adapter | Import controls, restricted-product rules, label languages, food-safety requirements and permitted units | Supplier availability or channel inventory |
| Market adapter | Seller of record, currency, assortment, tax zone, fulfillment promise and market-specific offer state | Scientific identity or source-market observation history |
| Locale adapter | Reviewed translation, transliteration, synonyms, query variants, canonical locale and hreflang | Product activation or price |
| Channel adapter | Allowed web, app, kiosk, POS or B2B fields; revision and command permissions | Independent product identity or uncontrolled stock |
| Branch adapter | Narrow pickup, local stock and service overrides | Global facts, product-family identity or country policy |

Source market and sales market are separate dimensions. A Japanese listing observed in JPY remains a Japanese source-market observation even when it informs an Israeli ILS planning decision. A currency conversion is evidence attached to a date and formula, not a public price and not an offer.

The expansion key for an offer should include at least:

`product_id + variant_id + sku_id + market_id + channel_id + currency + revision`

Every country and channel can advance through commerce and WooCommerce states independently. A product may be an active offer in one market and reference-only in another without duplicating its global culinary entity.

## 10. Next gated cohorts

| Cohort | Scope | Exit gate before the next cohort |
|---|---|---|
| 1. Entity Studio infrastructure | Private dossier CPT, admin surface, authenticated API, subject-scoped bases, draft-only rebase, transactional read-back, revision-chain verification, deletion blocking and tests | No public route, no public REST data, no WooCommerce writes, valid capability and nonce controls, reproducible contract tests |
| 2. Sushi editorial core | Private sushi section, Edomae nigiri, shari, Koshihikari, nori, proposed sushi-su and exact SEO ownership | Bilingual editorial review, evidence completeness, no owner collisions, reviewed breadcrumbs and internal links |
| 3. Existing private offer review | Five Cohort A plans for honkarebushi belly, two hon mirin ages, 720 ml Kito yuzu and Umezawa hangiri | Exact identity and supplier documents, labels, allergens, storage, logistics and market-specific compliance; zero activation by dossier approval |
| 4. Next candidate research | Maruyama nori, Tajima red sushi vinegar, Minamigura tamari, Yamaco makisu and Sakai Takayuki yanagiba, with Umezawa treated only as overlap | Exact product, variant and SKU identities, dated observation capture, retained evidence, price-plan review and no unresolved duplicate |
| 5. Fresh wasabi service | Rhizome, grater, handling process and service bundle logic | Supplier and lot traceability, phytosanitary review, cold-chain evidence, food-contact review, waste and delivery tests |
| 6. Seafood and Edomae production | Species, cuts, treatments, receiving, cold chain, parasite controls and kitchen tests | Qualified suppliers, exact species and origin, validated local food-safety plan, allergen controls, lot records and culinary acceptance |
| 7. WooCommerce activation for net-new SKUs | Accepted products, variations, SKUs, price, tax, inventory, shipping and policies; the 36 existing live products remain the baseline | Successful preflight, dry run, apply, read-back, test order, stock decrement, email, refund and schema acceptance |
| 8. App, kiosk and POS projections | Authenticated channel projections and operational commands | Signed interface contracts, idempotency, revision checks, reconciliation, offline behavior and failure recovery |
| 9. International rollout | Country, market, locale, channel and branch adapters | Local legal review, seller-of-record approval, localized labels and policies, channel acceptance and rollback rehearsal |

## 11. Non-negotiable acceptance rules

1. No dossier approval can expose a page, product, price or inventory record.
2. No source listing can be treated as a supplier agreement or local-stock signal.
3. No public route can be created without one explicit SEO owner in Hebrew and English.
4. No public breadcrumb can point to a private or missing route.
5. No exact product CTA can appear until that SKU is accepted for the current market and channel.
6. No scientific category value can be copied to a product or lot without product-level evidence.
7. No identity merge can occur without an alias record, reviewer and reason.
8. No automatic migration may publish content, activate an offer or write to WooCommerce.
9. The checked-in registries and WooCommerce remain the active runtime authorities; Entity Studio remains a private overlay.
10. Every future public or commerce transition must be independently reversible under the existing WordPress deployment and rollback contract.

## 12. Immediate implementation sequence

1. Re-run the Entity Studio contract suite and verify subject-scoped digesting, explicit draft-only rebase, transaction and read-back behavior, chain verification and deletion blocking without broadening authority.
2. Produce the stable-ID reconciliation report and resolve the honkarebushi collision before any second product or WooCommerce write.
3. Preserve the five existing Cohort A offers as private drafts and complete product-specific supplier, compliance, media and logistics gates.
4. Convert each net-new Cohort B hypothesis into an exact product, variant, SKU and dated evidence record only after identity review. Do not duplicate Umezawa.
5. Create the private `hub-japanese-sushi` section and the draft `ingredient-sushi-su` entity in the checked-in graph through the normal reviewed code path.
6. Extend the SEO ownership registry with the sushi intents, aliases and collision tests before making any route public.
7. Use the existing WooCommerce preflight, dry-run, apply and acceptance workflow only for candidates that have passed every gate. Keep the 36 live products unchanged unless a separately reviewed update requires them.

This sequence preserves the current live system while creating a modular path from research, through review, to a separately authorized public and commercial release.
