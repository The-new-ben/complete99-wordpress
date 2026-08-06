# Complete99 Japanese Pantry Commercial Roadmap

Status: private internal planning document
Version date: 2026-08-06
Scope: Japanese dashi, premium pantry knowledge, commerce, channels and international expansion
Public audience: culinary consumers only
System authority: WordPress for publishing and WooCommerce for products, prices, inventory and orders

## Internal-use boundary

This document contains source-market observations, commercial hypotheses,
supplier and import gates, quality ladders, private economics and channel
architecture. It must not be rendered, linked, indexed or quoted as a public
Complete99 offer.

A dated retailer price in this document is evidence about an external listing.
It is not a supplier quote, landed cost, Complete99 selling price, stock promise
or active WooCommerce offer.

## 1. Executive decision

The Japanese pantry should launch as one connected consumer journey with
separate owners for separate tasks:

`Washoku hub -> ichiban dashi recipe -> kombu and katsuobushi references -> umami science -> Japanese pantry -> exact active SKU or bundle`

The operating decisions are:

1. Keep one stable knowledge graph and one stable commerce graph.
2. Let each indexable page own one query family and exclude sibling intents.
3. Publish educational facts only after source, editorial and visual review.
4. Activate commerce only from an exact WooCommerce SKU after all offer gates
   pass.
5. Keep supplier, cost, margin, purchase, inventory workflow, staff, POS and
   connector details private.
6. Treat the consumer application, POS and touch kiosk as channel projections
   of WordPress and WooCommerce, never as independent catalogues.
7. Expand to new countries through market, locale and channel adapters rather
   than copying entities or creating a second source of truth.

The smallest coherent commercial slice is classical dashi:

- one tested ichiban dashi preparation owner;
- one kombu ingredient owner;
- one katsuobushi ingredient owner;
- one umami-science owner;
- one exact kombu SKU;
- one exact katsuobushi SKU;
- one component-managed dashi bundle;
- one WooCommerce authority for price, stock, cart and order state.

Hon mirin, kioke shoyu, yuzu and fresh wasabi form the next premium-pantry
extension. Alcohol, cold-chain and import gates remain independent so one held
product does not block useful educational pages or other cleared products.

## 2. Current evidence baseline

### 2.1 Direct public observations on 2026-08-06

| Surface | Observed state | Roadmap treatment |
| --- | --- | --- |
| `https://complete99.co.il/store/` | HTTP 200; no `noindex` robots directive observed | Existing consumer store owner. A Japanese category or filter must not create duplicate ownership. |
| `https://complete99.co.il/en/store/` | HTTP 200; no `noindex` robots directive observed | English mirror of the same consumer store authority. |
| `/museum/japanese-culinary-science/` and English mirror | HTTP 200; `noindex, follow` | Keep as preview until long-form and cluster acceptance pass. |
| `/ingredients/kombu/` and English mirror | HTTP 200; `noindex, follow` | Protected ingredient owner. Do not let a recipe or SKU page retell its identity task. |
| Ichiban dashi, katsuobushi, umami synergy and hon mirin routes | HTTP 404 in Hebrew and English | Keep absent or in a private `noindex` preview until each owner contract is complete. Do not index placeholders. |

### 2.2 Private registry observations

The current Japanese commerce registry contains:

- 10 product candidates, 10 variants and 10 SKUs, all in
  `research_candidate` state;
- zero supplier offers;
- zero channel offers;
- zero landed-cost scenarios;
- zero margin scenarios;
- three draft bundles;
- five draft merchandising edges;
- Woo web and Woo B2B channels in `configured_no_offers` state for this cohort;
- MyShop kiosk and POS channels in `contract_required` state.

These facts come from
`plugin/complete99-platform/data/culinary-commerce-pilot.php`. They define what
may be planned privately. They do not authorize a public product, price or
availability statement.

## 3. Public facts and private commercial data

| Data class | May become public when verified | Must remain private |
| --- | --- | --- |
| Culinary identity | What an ingredient is, how it is used, sensory description, cultural context, storage and sourced science | Unreviewed notes, research prompts, disputed claims and editorial approval state |
| Recipe | Tested quantities, yield, method, timing, temperature, storage, allergens and useful substitutions | Kitchen experiment logs, production yield economics, food cost and internal process exceptions |
| Product | Exact name, maker, pack, ingredients, allergens, origin, storage, consumer price, stock and policies when the exact Woo offer is active | Supplier quote, supplier terms, landed cost, margin, target contribution, purchase order and internal lot decisions |
| Market evidence | Nothing by default. A source may be cited editorially only for a clearly useful and current comparison | Observation price, normalized price, exchange-rate conversion, sensitivity band and sourcing hypothesis |
| Inventory | Current consumer availability derived from WooCommerce after acceptance | Stock counts, reorder point, safety stock, shrinkage, receiving discrepancy and branch reconciliation |
| Channels | Working consumer action and truthful policy for an active channel | API contract, credentials, connector state, sync failures, private cache, idempotency and audit events |
| International market | Localized public catalogue, price, tax-inclusive display and policies after market launch | Seller-of-record contract, importer, duty model, private tax advice, market margin and launch checklist |

### 3.1 Consumer-facing language guidance

Public copy should answer, in this order:

1. What is this ingredient, recipe or product?
2. What does it taste like and what culinary task does it solve?
3. How is it selected, used and stored?
4. Which verified dish, recipe or complementary product is the useful next
   step?
5. What exact price, stock and fulfilment action is available now, if an active
   Woo offer exists?

Do not expose the words or concepts `project`, `pilot`, `registry`, `evidence
gate`, `source observation`, `margin`, `supplier contract`, `connector`,
`MyShop state`, `staff workflow`, `API readiness` or `inventory reconciliation`
on consumer pages.

Do not show a disabled pseudo-action or public explanation that a module is not
working. When a commercial action is not active, omit it and keep the page
useful through culinary explanation and real editorial links. Use an add-to-cart
action only when WooCommerce confirms the exact offer and stock state.

Public Japanese-pantry pages address culinary consumers. Institutional buying,
staff, kitchen operations and procurement remain authenticated private
functions.

## 4. Modular entity and commerce architecture

### 4.1 Entity layers

| Layer | Example | Authority | Public projection rule |
| --- | --- | --- | --- |
| Knowledge entity | `ingredient-kombu`, `preparation-ichiban-dashi` | Culinary science registry | May own an editorial URL after sources and review pass. Never carries a sell price. |
| Product family | `product-rishiri-kombu-100g` | Private product preparation until approved | Describes a commercial identity, not an offer. |
| Variant | `variant-fukumitsuya-hon-mirin-3y-720ml` | Commerce registry, then Woo | Separates age, format, pack or material. |
| SKU | `sku-honkarebushi-belly-200g` | WooCommerce after activation | Exact sellable identity. Product facts attach here, not to the ingredient category. |
| Evidence artifact | `evidence-honkarebushi-belly-200g-20260806` | Immutable private evidence record | Not public offer evidence. Its source may support internal review. |
| Market observation | `observation-rishiri-kombu-100g-20260806` | Private market ledger | Historical and immutable. Never copied into a Woo price. |
| Supplier offer | Future signed quote for an exact SKU | Private procurement | Required before sourcing approval. Never public. |
| Landed-cost scenario | SKU, route, freight, customs, tax and handling | Private finance and procurement | Required before pricing approval. Never public. |
| Margin scenario | Net revenue and contribution by market and channel | Private finance | Required before approval. Never public. |
| Woo channel offer | Exact price, currency, tax, stock and effective period | WooCommerce | Sole public price and availability authority. |
| Bundle | `bundle-dashi-foundation-draft` | Woo after approval | Public only when every component and stock rule are active. |
| Merchandising edge | Kombu to katsuobushi | Private approved graph, projected by Woo | Public only when relevant, evidenced and both targets are active. |
| Locale projection | `he-IL`, `en-IL` | Reviewed publishing layer | Translation is a reviewed revision, not runtime machine output. |
| Channel projection | Web, app, kiosk, POS or B2B | Channel adapter | Receives allowed fields only and cannot become a second authority. |
| Branch override | Local availability or pickup window | Private branch adapter | Narrow, dated and audited. It never changes global identity. |

### 4.2 Authoritative flow

`Knowledge entity -> product -> variant -> SKU -> supplier offer -> landed-cost and margin approval -> Woo offer -> channel projection -> order and inventory event back to Woo`

Rules:

- Stable IDs survive translation, market and channel expansion.
- A source-market retailer is not automatically a supplier.
- A market observation cannot skip the supplier-offer and landed-cost layers.
- WooCommerce owns the approved price, stock, cart, order and refund state.
- WordPress publishes consumer knowledge and curated discovery.
- App, kiosk and POS clients submit narrow authenticated commands and reconcile
  to Woo. They never write directly to the WordPress database.
- AI may assist behind the WordPress API boundary, but customers and staff do
  not need a ChatGPT account.

## 5. Channel architecture

| Channel | Current cohort state | Authority and allowed use | Activation requirement |
| --- | --- | --- | --- |
| WordPress editorial web | Japanese preview exists | Public culinary knowledge, query owners, links and consumer guidance | Source, editorial, visual, bilingual, SEO and accessibility acceptance |
| WooCommerce web, Israel | `configured_no_offers` for Japanese pilot | Catalog, price, inventory, cart and order authority | Exact approved SKUs, price book, tax, policies, checkout, refund and stock tests |
| Consumer application | Future projection | Same consumer catalogue and account/order actions through scoped APIs | Defined contract, authentication, privacy, reconciliation and release acceptance |
| MyShop touch kiosk | `contract_required` | Shallow consumer projection from Woo | Verified vendor contract, endpoint, credentials, mapping, idempotency and recovery tests |
| MyShop POS | `contract_required` | Staff transaction projection from Woo | Same contract controls plus order ingest, stock reconciliation and failure ownership |
| Woo B2B | `configured_no_offers` | Private institutional price and assortment projection | Separate approval, policies and access control. It is not public Japanese-pantry copy. |

No channel may invent an offline price or retain an undated availability claim.
Adding a Japanese SKU changes the accepted catalogue and requires a fresh
commerce acceptance receipt before public exposure.

## 6. SEO and page-owner matrix

### 6.1 Owner contracts

| Entity and canonical | Primary query owner | Role and parent | Excluded intents | Schema and index policy | Commercial continuation |
| --- | --- | --- | --- | --- | --- |
| `ingredient-kombu` at `/ingredients/kombu/` and `/en/ingredients/kombu/` | `קומבו לדאשי`, `מה זה קומבו`, `kombu for dashi` | `reference`; navigation parent `/ingredients/`; semantic relation to Washoku | Ichiban dashi recipe, exact Rishiri SKU, seaweed-health claims and general seaweed category | `Article` with a `DefinedTerm` main entity and `BreadcrumbList`; keep `noindex, follow` until review | Link after the answer to an exact active kombu SKU and to ichiban dashi |
| `preparation-ichiban-dashi` at `/knowledge/ichiban-dashi/` and English mirror | `איך מכינים דאשי`, `מתכון דאשי`, `ichiban dashi recipe` | `spoke`; navigation parent `/knowledge/`; semantic technique hub under Washoku | Instant dashi, vegetarian dashi, generic kombu or katsuobushi identity, umami science and exact SKU intent | `Recipe` and `BreadcrumbList`; private or `noindex` preview first. The extraction technique remains a section, not another URL | Place the dashi bundle after the full answer, never before the recipe |
| `ingredient-katsuobushi` at `/ingredients/katsuobushi/` and English mirror | `קצואובושי`, `קאטסואובושי`, `שבבי בוניטו`, `what is katsuobushi` | `reference`; parent `/ingredients/` | Dashi recipe, exact product, pet food, unsupported nutrition, health or kosher claims | `Article`, `DefinedTerm` and `BreadcrumbList`; noindex until fish identity, process and allergen review | Cross-link kombu, ichiban dashi and an exact active product |
| `guide-umami-synergy` at `/knowledge/umami-synergy-glutamate-imp/` and English mirror | `סינרגיית אומאמי`, `גלוטמט ו-IMP`, `dashi umami synergy` | `article`; parent `/knowledge/`; semantic food-science hub | Recipe, MSG-safety debate, supplements and medical benefits | `Article` and `BreadcrumbList`; no medical schema; index only after claim-level review | Contextual links to kombu, katsuobushi and dashi after the scientific answer |
| `ingredient-hon-mirin` at `/ingredients/hon-mirin/` and English mirror | `הון מירין יפני`, `מירין אמיתי`, `hon mirin vs aji mirin` | `reference`; parent `/ingredients/` | Exact Fukumitsuya SKU, general alcohol shopping, teriyaki recipe and health claims | `Article`, `DefinedTerm` and `BreadcrumbList`; noindex until process and alcohol review | Link to active 3-year and 10-year SKUs only after their independent gates pass |
| Proposed Japanese pantry category under `/store/` | `מזווה יפני לבישול`, `מוצרים יפניים פרימיום`, `Japanese premium pantry` | `category`; parent `/store/` | Ingredient definitions, recipes, exact-SKU queries, B2B and supplier sourcing | `CollectionPage`, `ItemList` and `BreadcrumbList`; create a distinct canonical only if it has durable utility and active assortment. Otherwise use a controlled store filter | Route consumers by task: make dashi, season sushi, use fresh wasabi |
| Future curated exact-product owner under the consumer store | Exact maker, variant, pack and buy query | `reference` commercial owner under `/store/` | Generic ingredient identity, recipe and broad category query | `Product`, exact `Offer` and `BreadcrumbList` only when visible facts match Woo | Cross-sell only active compatible products and useful knowledge owners |
| `bundle-dashi-foundation-draft` | `ערכת דאשי`, `ערכת קומבו וקצואובושי`, `dashi starter kit` | Future exact bundle owner under the Japanese pantry | Recipe, single product and vegetarian dashi | No public route or schema while draft. Use `Product` and `Offer` only for an active Woo bundle SKU | Component-managed stock from exact kombu and katsuobushi SKUs |

### 6.2 Breadcrumb and linking contract

The current Japanese topic hubs for ingredients, techniques and food science
are section entities owned by the Washoku page. They are not standalone public
URLs. A breadcrumb must never link to a missing section route.

Use visible paths that exist:

- `Home -> Ingredients -> Kombu`;
- `Home -> Ingredients -> Katsuobushi`;
- `Home -> Knowledge -> Ichiban dashi`;
- `Home -> Knowledge -> Umami synergy`;
- `Home -> Store -> Japanese pantry -> exact active product`, only after a
  durable store hierarchy exists.

Every owner links to its parent with a specific anchor. Parent hubs link to
approved children. Product modules appear after the substantive answer on
informational pages.

### 6.3 Activation evidence by owner

| Owner | Minimum evidence before index or commerce activation |
| --- | --- |
| Ichiban dashi | Reproducible kitchen test, exact weights, water, yield, time, temperature, storage, fish-allergen review, step imagery with rights, Hebrew and English review |
| Kombu | Ingredient identity, regional and process sources, storage review, consumer-safe visual, distinct boundary from the exact Rishiri product |
| Katsuobushi | Fish species and form, processing distinction, producer or label evidence, allergen and cross-contact review, storage and image rights |
| Umami synergy | Official or peer-reviewed claim sources, measurement context, uncertainty, scientific review and no health extrapolation |
| Hon mirin | Process source, exact distinction from mirin-style seasoning, alcohol classification, label or COA values, compliance review and bilingual copy |
| Japanese pantry category | Active Woo products that fulfil each visible task, useful filters, current stock, price, policies, no empty category promises and no duplicate canonical |
| Exact product | Supplier and item evidence, SKU or identifier, pack, label, ingredients, allergens, origin, price, tax, stock, image rights, fulfilment, returns, support, test order and refund |
| Bundle | Active components, component stock decrement, price calculation, substitution rule, test order, cancellation and refund |

## 7. Search and competitor pattern evidence

This is a lightweight observation, not a ranking guarantee. Search-provider
geography, device and personalization were not fully controlled. A real Israeli
Chrome sample and GSC query-page data remain required before an index release.

### 7.1 Observed patterns

- Hebrew dashi results mix definitions, recipes and ready-made product pages.
  OOMAME combines explanation, science, preparation and product links, while
  TevaMe and East West serve instant-product intent.
- Hebrew katsuobushi results mix official reference material with local product
  pages. This supports a separate ingredient owner and exact-SKU owner.
- English `ichiban dashi recipe` results are predominantly recipe and how-to
  pages with yield, ingredient quantities, ordered steps, imagery, storage and
  comparison with niban dashi.
- English Japanese-pantry guides commonly use prioritized lists and link to
  deeper ingredient guides.
- Exact honkarebushi product pages place maker, form, pack, price, availability,
  imagery and purchase action high on the page.

### 7.2 Evidence-backed inference

Complete99 can differentiate through a connected task graph instead of merging
every intent into one article. The recipe should answer the preparation task,
ingredient pages should explain selection, the science page should support
claims, and Woo should provide the only current product and offer state.

## 8. Dated source-market price observations

Captured at 2026-08-06T00:50:31+03:00. Every item remains a
`research_candidate`, and every evidence artifact has
`offer_approval_eligible: false`.

| Candidate SKU | Exact observed source price | Normalized observation | Tax and shipping observed | Availability observed | Current hold | Source |
| --- | ---: | ---: | --- | --- | --- | --- |
| Natural Rishiri kombu, 100 g | JPY 1,165 | JPY 11,650/kg | Tax included; domestic shipping excluded | Quantity selector visible | Import-label review | [Rishiri Kombu direct shop](https://www.rishirikonbu.com/items/4808577) |
| Honkarebushi belly block, approximately 200 g | USD 33.00 | approximately USD 165.00/kg | Final tax and shipping unknown | In stock | Fish-allergen and import review | [Japanese Taste](https://int.japanesetaste.com/products/honkarebushi-whole-japanese-katsuobushi-block-bonito-belly-200g) |
| Fukumitsuya Hon Mirin, 3 years, 720 ml | USD 39.99 | USD 55.54/l | Final tax and shipping unknown | In stock | Alcohol, age, licence and import review | [Japanese Taste](https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-3-years-traditionally-aged-sweet-rice-wine-720ml) |
| Fukumitsuya Hon Mirin, 10 years, 720 ml | USD 54.99 | USD 76.38/l | Final tax and shipping unknown | Listed for sale | Alcohol, age, licence and import review | [Japanese Taste](https://japanesetaste.com/products/fukumitsuya-junmai-hon-mirin-10-year-aged-sweet-rice-seasoning-720ml) |
| Yamaroku Tsuru-bishio, 500 ml | JPY 1,944 | JPY 3,888/l | Tax included; shipping unknown | Price listed | Soy, wheat, import and label review | [Yamaroku](https://yama-roku.net/product) |
| Fresh Japanese wasabi, 250 g | GBP 62.50 | GBP 250.00/kg | VAT included; shipping excluded | Out of stock | Cold-chain, phytosanitary and import review | [The Wasabi Company](https://www.thewasabicompany.co.uk/products/fresh-japanese-wasabi-250g) |
| Kito yuzu juice, 100 ml | JPY 734 | JPY 7,340/l | Tax included; shipping unknown | Add-to-cart visible | Import-label review | [Ogon no Mura](https://shop.ogonnomura.jp/view/item/000000000364) |
| Kito yuzu juice, 720 ml | JPY 3,780 | JPY 5,250/l | Tax included; shipping unknown | Price listed | Import-label review | [Ogon no Mura](https://shop.ogonnomura.jp/view/item/000000000199?category_page_id=ichiban) |
| Umezawa sawara-cypress hangiri, 36 cm | USD 129.00 | USD 129.00/item | Final tax unknown; shipping excluded | In stock | Food-contact material review | [Japanese Taste](https://japanesetaste.com/products/umezawa-sawara-cypress-hangiri-wooden-sushi-oke-bowl-36cm) |
| Large Hagane-zame grater | GBP 135.00 | GBP 135.00/item | VAT included; shipping calculated at checkout | Last few remaining | Food-contact and handling review | [The Wasabi Company](https://www.thewasabicompany.co.uk/products/hagane-zame-wasabi-grater?variant=49446664601881) |

### 8.1 Interpretation rules

- Re-observe every candidate before a sourcing or pricing decision. Preserve
  the old observation rather than overwriting history.
- Availability on an external retailer is not Complete99 inventory.
- Included foreign tax may not be recoverable or relevant to the Israeli
  import route.
- A normalized price supports like-for-like analysis only when product grade,
  origin, process, pack and shipping basis are comparable.
- Do not convert these values directly into WooCommerce prices.
- The 10-year mirin observation is USD 15.00, approximately 37.5 percent, above
  the three-year observation for the same maker and volume. This supports a
  private maturation-ladder hypothesis, not a public price or universal quality
  claim.
- The 720 ml yuzu observation is approximately 28.5 percent lower per litre
  than the 100 ml observation. This supports a private pack-size up-sell test,
  not a discount claim.

## 9. Quality ladders

A quality ladder must identify the dimension that changes. Higher price alone
does not prove higher quality.

| Family | Quality dimensions | Proposed ladder | Current observed anchor | Evidence required before public comparison |
| --- | --- | --- | --- | --- |
| Kombu | Species, region, harvest, grade, thickness, moisture, storage, lot and extraction behaviour | Verified culinary kombu -> named-origin selection -> lot-specific reserve with sensory and extraction record | Natural Rishiri kombu 100 g | Exact label, origin, harvest or grade documents, storage, lot and fair extraction method |
| Katsuobushi | Species, smoking, mold cycles, drying, arabushi/karebushi/honkarebushi form, block or shaving date | Verified pre-shaved convenience -> documented processed shavings -> whole honkarebushi service | Honkarebushi belly block, approximately 200 g | Species, maker, region, processing, label, fish allergen, lot and storage |
| Hon mirin | Legal category, ingredients, koji process, alcohol, sugar, maturation and use | Verified hon mirin -> 3-year maturation -> 10-year maturation | Fukumitsuya 3-year and 10-year, 720 ml | Exact labels, ABV, process, maker evidence, legal classification and tasting method |
| Shoyu | JAS type, brewing method, kioke use, maturation, nitrogen, salt, soy and wheat | Verified naturally brewed shoyu -> kioke-aged selection -> documented long-matured saishikomi | Yamaroku Tsuru-bishio 500 ml | Exact label, JAS evidence, maker process, allergens, lot and comparison basis |
| Yuzu | Fruit origin, juice percentage, harvest, pressing, heat treatment, aroma retention and pack | Discovery pack 100 ml -> regular-use pack 720 ml; this is a pack ladder, not automatically a quality ladder | Kito yuzu juice 100 ml and 720 ml | Ingredient statement, origin or GI evidence, processing, shelf life and comparable pack data |
| Fresh wasabi | Species, cultivar if known, origin, harvest date, grade, rhizome size and cold-chain age | Verified fresh rhizome -> selected grade and harvest -> complete fresh-wasabi service | Fresh Japanese wasabi 250 g | Species, source, harvest, phytosanitary route, cold chain, shelf life and receiving standard |
| Hangiri | Wood species, diameter, construction, finish, lid, care and food-contact suitability | Functional size -> craft/material selection -> professional capacity | Umezawa sawara cypress 36 cm | Maker, material, dimensions, food-contact evidence, care and capacity guidance |
| Wasabi grater | Material, surface, dimensions, handle, cleaning, food-contact and replacement | Functional tool -> exact large service tool -> professional workflow set | Large Hagane-zame | Exact material, dimensions, maker, care, safety and food-contact review |

Comparisons must be written around a cooking decision. For example, the mirin
page may explain how maturation changes concentration and use only after an
exact sensory and source review. It must not say that the highest-priced bottle
is best for every dish.

## 10. Cross-sell, up-sell and bundle architecture

### 10.1 Existing draft relations

| Starting point | Continuation | Type | Consumer value | Activation rule |
| --- | --- | --- | --- | --- |
| Rishiri kombu | Honkarebushi | Cross-sell | Completes the classical fish-based dashi pair | Both exact offers active and the recipe explains the pairing |
| Hon mirin, 3 years | Hon mirin, 10 years | Up-sell | Offers a longer-matured alternative from the same maker and volume | Both exact offers active; comparison states the actual maturation and use difference |
| Kito yuzu, 100 ml | Kito yuzu, 720 ml | Up-sell | Lower normalized source observation for a customer with sufficient usage | Both packs active; shelf-life and waste risk make the larger pack sensible |
| Fresh wasabi rhizome | Hagane-zame grater | Cross-sell | Supplies the tool required for correct fresh preparation | Both offers active; food-contact and care review complete |
| Kioke shoyu | Fresh wasabi | Cross-sell | Supports sushi and sashimi seasoning | Both offers active and culinary context is relevant |

### 10.2 Existing draft bundles

| Bundle | Components | Inventory policy | Hold state |
| --- | --- | --- | --- |
| Dashi foundations | Rishiri kombu 100 g + Honkarebushi belly block approximately 200 g | Component-managed | Draft; no channel offer |
| Sushi seasoning | Yamaroku Tsuru-bishio 500 ml + Fukumitsuya Hon Mirin 3-year 720 ml + Kito yuzu 100 ml | Component-managed | Draft; mirin alcohol and every import gate remain open |
| Fresh wasabi service | Fresh wasabi 250 g + Large Hagane-zame grater | Component-managed | Draft; wasabi is out of stock at the observed source and cold-chain review is open |

### 10.3 Merchandising controls

- Show the culinary answer before an organic product module on informational
  pages.
- Display a relation only when every target is compatible and active.
- Decrement component inventory for a virtual bundle. Use a separate bundle SKU
  only for a physically packed and audited kit.
- Do not use fake crossed-out prices, urgency, scarcity, ratings or reviews.
- Do not recommend a product solely because its private margin is higher.
- Suppress a bundle when a required component is unavailable.
- Link every active product back to its ingredient or equipment reference and
  to one useful recipe or technique.
- Record attachment rate, average order value, bundle completion, repeat
  purchase, returns, spoilage and contribution privately.

## 11. Private pricing and margin model

The approved retail price is a decision produced after evidence, not a currency
conversion of an external retail listing.

Private landed-cost formula:

`supplier product cost + origin handling + freight + insurance + customs and duty + non-recoverable tax + broker and inspection + label or repack + cold-chain or storage + expected shrinkage = landed product cost`

Private contribution formula:

`net revenue excluding applicable tax - landed product cost - packaging - payment and channel fees - pick and fulfilment - expected spoilage - returns and support = contribution margin`

Each scenario stores:

- SKU, supplier offer and effective dates;
- source currency and dated exchange-rate basis;
- freight route, incoterm, insurance and minimum order;
- tariff classification, duty, VAT treatment, broker and inspection;
- label, packaging, storage and cold-chain cost;
- spoilage, shelf life, expected returns and support;
- web, app, kiosk, POS or B2B channel fees;
- planned sell price, tax basis, contribution and approval state;
- sensitivity cases and explicit missing evidence.

No scenario value may populate Woo until it is approved for the exact market,
SKU and channel. Once approved, Woo becomes the public price and inventory
authority.

## 12. Hold gates

### 12.1 General offer gate

An exact Japanese-pantry SKU remains held until all applicable items pass:

1. Product, variant, SKU, pack and identifier are exact.
2. Supplier identity and current signed item evidence exist.
3. Label, ingredients, allergens, origin, storage, shelf life and images match
   the exact item.
4. Import classification, customs, duty, tax and regulatory treatment are
   reviewed for the real route.
5. Landed cost and contribution economics are approved privately.
6. Woo contains the approved price, currency, tax, stock and effective period.
7. Pickup or shipping, packaging, returns, cancellation, privacy, terms and
   support are operational.
8. Test order, payment, refund, transactional email and stock movement pass.
9. Visible product facts and `Product` or `Offer` schema match Woo exactly.
10. Every enabled channel reconciles to the same Woo revision.

Missing commerce evidence does not block an otherwise useful and reviewed
ingredient or recipe page. It blocks the product CTA, offer schema and sale.

### 12.2 Alcohol hold for hon mirin

`[COMPLIANCE_NOTE: Hon mirin is an alcoholic seasoning. Product classification,
exact ABV, age-18 verification, applicable business licence, permitted sale
conditions, tax, import, payment-provider acceptance, fulfilment and public
disclosure must be verified for the exact SKU before sale activation.]`

Required evidence includes:

- exact front, back and legal label;
- ABV and volume from the label or COA;
- legal classification in Israel;
- age-verification design and transaction acceptance;
- licence and permitted sale-condition review;
- importer, customs and tax treatment;
- fulfilment and handover controls;
- return, cancellation and support treatment;
- Woo, schema and transactional test parity.

The 3-year and 10-year candidates remain independent. Approval of one does not
approve the other.

### 12.3 Food-import holds

| Product class | Additional hold evidence |
| --- | --- |
| Kombu and yuzu | Exact product label, ingredient or species identity, origin, import route, storage and shelf life |
| Katsuobushi | Fish species, processing facility, fish-allergen declaration, cross-contact, import route and storage |
| Shoyu | Soy and wheat allergens, exact ingredients, JAS or process evidence, salt and other values only from the exact label or COA |
| Fresh wasabi | Species and origin, phytosanitary treatment, cold-chain route, shelf life, receiving standard and spoilage model |
| Hangiri and grater | Food-contact material, finish, care, handling and importer evidence appropriate to the exact item |

Use the Israeli National Food Service and Customs sources in the source ledger.
Regulatory review must be current for the actual product and route. A foreign
retailer page is not regulatory clearance.

## 13. International expansion architecture

### 13.1 Reusable core

The following are global and should not be copied per country:

- cuisine, ingredient, recipe, molecule, technique and equipment identities;
- product family, variant and manufacturer identities;
- typed culinary and merchandising relationships;
- source and evidence identities;
- visual asset identities and rights state.

### 13.2 Country adapter

Stores country code, regulatory references, import controls, restricted
classes, tax framework, required label languages, units, consumer-policy
requirements, timezone and retention constraints.

### 13.3 Market adapter

Stores seller of record, importer, currency, assortment eligibility, price
book, tax zone, fulfilment region, payment, returns, support and launch state.
A source-observation market is not a sales market.

The current Japan, United States and United Kingdom records are source markets
only. They do not authorize an offer in Israel or in those countries.

### 13.4 Locale adapter

Stores language, direction, transliteration, reviewed terminology, SEO owner,
canonical prefix, hreflang, currency display and measurement format.

`en-IL` is not a substitute for a future `en-US` or `en-GB` market projection.
Each market requires reviewed local policies, prices, fulfilment and search
intent even when the underlying English entity text is shared.

### 13.5 Channel and branch adapters

Web, application, kiosk, POS and B2B channels receive the same stable SKU with
only the fields allowed for that channel. Branches may override local stock and
pickup windows through narrow dated records. They never redefine the product or
price authority.

### 13.6 Market-entry sequence

1. Qualify consumer demand, source availability and the smallest useful cohort.
2. Establish seller of record, importer, payment, tax, fulfilment, returns and
   support.
3. Build the country, market and locale adapters.
4. Approve one complete vertical slice, such as dashi knowledge plus two SKUs
   and one bundle.
5. Materialize the exact offers in Woo for that market.
6. Verify web checkout, refund, stock, email, schema and consumer support.
7. Add channel projections only after the web authority is stable.
8. Expand the assortment from observed demand and operational evidence.

## 14. Gate-based delivery roadmap

| Phase | Scope | Exit evidence |
| --- | --- | --- |
| 0. Current private baseline | Preserve 10 observations, entity IDs, draft bundles and channel boundaries | Registry contract tests and immutable observation history |
| 1. Dashi editorial vertical | Ichiban dashi, katsuobushi and umami synergy in Hebrew and English; strengthen kombu | Owner contracts, sources, kitchen test, allergen review, images, bilingual review, raw and rendered SEO acceptance |
| 2. Dashi sourcing vertical | Obtain exact supplier evidence for kombu and katsuobushi | Supplier offers, labels, identifiers, import treatment, landed cost and margin approval |
| 3. Dashi web commerce | Create exact Woo offers and component-managed bundle | Product acceptance, policies, test order, refund, stock movement, email and schema parity |
| 4. Premium seasonings | Kioke shoyu, yuzu and hon mirin educational and commercial layers | Independent product gates; full alcohol gate for each mirin SKU |
| 5. Fresh and equipment | Fresh wasabi, grater and hangiri | Cold-chain, phytosanitary, food-contact, care, fulfilment and spoilage acceptance |
| 6. Application and MyShop | Project approved Woo catalogue to app, kiosk and POS | Signed contract, scoped credentials, mapping, idempotency, reconciliation, recovery and audit proof |
| 7. International market | Repeat one vertical slice through new adapters | Seller of record, local compliance, locale, price book, fulfilment, support and transaction acceptance |

No phase has a calendar promise in this document. Completion is evidence-based.
Useful editorial work may continue while a commercial gate remains held.

## 15. Measurement and audit

### 15.1 Public discovery measurements

- GSC impressions, clicks, average position and query-to-URL ownership;
- index state, canonical and reciprocal hreflang;
- ingredient-to-recipe and recipe-to-product click-through;
- product-module engagement after the substantive answer;
- no orphan owners and mobile link parity;
- correction, source and review freshness.

Record observations at 7, 14, 28 and 56 days after an index release. Until
those dates occur, the result is `pending`.

### 15.2 Private commercial measurements

- add-to-cart and checkout conversion;
- attachment rate by relationship;
- bundle completion and average order value;
- gross and contribution margin;
- inventory turns, days of stock, spoilage and shrinkage;
- refund, cancellation, return and support rate;
- channel sync delay, reconciliation failure and recovery time;
- repeat purchase by pack and family.

Targets, forecasts and private results are not public proof and must not appear
as consumer claims.

## 16. Source ledger

### 16.1 Internal authoritative sources

- `plugin/complete99-platform/data/culinary-science-pilot.php`
- `plugin/complete99-platform/data/culinary-commerce-pilot.php`
- `docs/complete99-commercial-entity-architecture.md`
- `docs/architecture.md`
- `docs/editorial-governance.md`
- `docs/complete99-operating-brief.md`
- `docs/complete99-myshop-connector-contract.md`

### 16.2 Culinary and scientific sources

- [MAFF, Dashi and Umami](https://www.maff.go.jp/e/policies/market/washoku-world-challenge/en/learning_03.html)
- [MAFF, An Introduction to Japanese Fermented Foods, part 2](https://www.maff.go.jp/j/keikaku/syokubunka/traditional-foods/files/user/pdf/an_Introduction_to_Japanese_fermented_foods_part2.pdf)
- [Katsuobushi dashi and mold process study](https://www.jstage.jst.go.jp/article/cookeryscience1968/19/4/19_285/_article)
- [Taste components and palatability of combined Japanese soup stock](https://www.jstage.jst.go.jp/article/cookeryscience1995/41/5/41_304/_article/-char/en)
- [Molecular mechanism for umami taste synergism](https://pmc.ncbi.nlm.nih.gov/articles/PMC2606899/)
- [JAS 1703 Soy Sauce](https://www.famic.go.jp/english/jas/_doc/jas1703.pdf)

### 16.3 Israeli regulatory and economic sources

- [National Food Service imported-food inspection unit](https://www.gov.il/he/departments/units/import-food-inspection-unit)
- [Business-licensing specification for alcohol sales](https://www.gov.il/BlobFolder/generalpage/regulation-group4-food/he/publications_business_license_4_Article%204.8.pdf)
- [Israeli Customs Tariff and import requirements](https://www.gov.il/he/service/customs-tariff)
- [Bank of Israel representative exchange rates](https://www.boi.org.il/en/economic-roles/financial-markets/exchange-rates/)

### 16.4 Search and competitor observations

- [OOMAME dashi guide](https://oomame.co.il/blogs/news/what-is-dashi)
- [TevaMe dashi product](https://www.tevame.co.il/%D7%9E%D7%96%D7%95%D7%9F-%D7%9E%D7%A2%D7%95%D7%9C%D7%9D/%D7%94%D7%9E%D7%96%D7%A8%D7%97-%D7%94%D7%A8%D7%97%D7%95%D7%A711/%D7%99%D7%A4%D7%9F/%D7%90%D7%91%D7%A7%D7%AA-%D7%93%D7%90%D7%A9%D7%99)
- [East West dashi product](https://ewi.co.il/products/%D7%A8%D7%98%D7%91%D7%99%D7%9D/%D7%93%D7%90%D7%A9%D7%99%D7%A0%D7%95%D7%9E%D7%95%D7%98%D7%95-2/)
- [MAFF katsuobushi reference](https://www.maff.go.jp/e/policies/market/dento_syoku/bunrui/husirui.html)
- [Kikkoman katsuobushi glossary](https://www.kikkoman.com/en/cookbook/glossary/katsuobushi.html)
- [Zojirushi ichiban dashi recipe](https://www.zojirushi.com/app/recipe/-i-ichiban-dashi-i-japanese-broth-)
- [Whole Foods dashi recipe](https://www.wholefoodsmarket.com/recipes/dashi)
- [Great British Chefs ichiban dashi recipe](https://www.greatbritishchefs.com/recipes/ichiban-dashi-recipe)
- [Mai Rice Japanese pantry guide](https://www.mai-rice.com/guides/japanese-pantry)
- [Just One Cookbook pantry guide](https://www.justonecookbook.com/essential-japanese-pantry-ingredients/comment-page-1/)

### 16.5 Platform and search architecture sources

- [WooCommerce REST API documentation](https://woocommerce.com/document/woocommerce-rest-api/)
- [Google ecommerce site structure](https://developers.google.com/search/docs/specialty/ecommerce/help-google-understand-your-ecommerce-site-structure)
- [Google Recipe structured data](https://developers.google.com/search/docs/appearance/structured-data/recipe)
- [Google Product structured data](https://developers.google.com/search/docs/appearance/structured-data/product)

## 17. Final control statement

The Japanese pantry becomes public commerce only when an exact product is
approved and active in WooCommerce. Until then, Complete99 may build and review
the knowledge, source, entity, relationship, visual and channel architecture
privately, while public pages remain useful, consumer-facing and truthful.
