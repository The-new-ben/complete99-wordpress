# Complete99 benchmark and gap-map addendum

Date: 2026-07-31  
Status: internal evidence addendum, not a public release claim  
Parent benchmark: [Complete99 consumer culinary and ecommerce benchmark](consumer-culinary-ecommerce-benchmark-2026-07-29.md)

Release correction: candidate 1.3.0 was superseded by 1.3.1 after a registered
boolean metadata readback defect was reproduced during the live migration
checkpoint. References to 1.3.0 below preserve the benchmark snapshot; 1.3.1 is
the deployment candidate and retains the same consumer, catalog, pricing, stock,
asset, and public-safety scope.

Current correction: production reached 1.3.1 with the migration checkpoint and
rollback exercise complete. Release candidate 1.3.2 adds deterministic document
titles to plugin-owned public shells. The state bullets below remain the archived
benchmark snapshot rather than current production truth.

## Decision

The live site and the local release candidate must be described separately.

- The public site is still Complete99 Platform 1.2.1. That is the only current
  public truth.
- The local source is versioned 1.3.0 and now contains a food-first consumer
  implementation. It has not been deployed by this review.
- The local implementation contains 12 bilingual dish references, 7 menu
  filters, 9 dish badges, a consumer group-meal request route, 26 private
  evaluation products, and 50 held generated assets.
- None of the 26 evaluation products is eligible for public sale. Their
  evaluation price and quantity are private test data, not current customer
  price or stock.
- All 50 generated assets remain in `evaluation` review state and `held` usage
  state. None is recorded as a photograph of a current product presentation.
- The store remains closed, outside public commerce, and subject to the full
  merchant, payment, fulfilment, policy, support, product, inventory, test-order,
  refund, email, analytics, and security gate.

The winning public direction is therefore:

1. Lead with recognizable food and a literal path to the current ordering menu.
2. Place the 12 dishes before editorial explanation.
3. Let filters and badges help a person scan menu format and food family.
4. Offer a separate consumer journey for meals for groups and workplaces.
5. Continue from dishes into ingredients, food stories, and practical guides.
6. Keep the pantry absent from public navigation until real commerce passes.
7. Keep inventory controls, workers, suppliers, costs, campaigns, and operating
   screens in the private system.

Terms such as release candidate, evaluation, gate, migration, source registry,
and held asset are internal status terms. They must not appear in public
consumer copy.

## Evidence method

This addendum used:

- read-only browser inspection of the live Hebrew home, dishes, store, and
  proposal pages on 2026-07-31;
- the live health and public-catalog endpoints;
- local 1.3.0 source registries and rendering contracts;
- the current Sweetgreen, Wagamama, and Dishoom screenshots supplied for this
  review;
- the prior global and Israeli competitor contact sheet;
- official competitor pages listed in the source register;
- focused local contract tests, run without writing a pytest cache.

No form, order, account, cart, checkout, payment, or public-site write was
submitted.

The focused local test result was:

```text
60 passed, 169 subtests passed
```

This result proves source-level contracts only. It does not prove that 1.3.0 is
installed, migrated, cached correctly, or visible on the public host.

## Public 1.2.1 versus local 1.3.0

| Surface | Live Complete99 1.2.1 | Local Complete99 1.3.0 progress | Remaining release proof |
| --- | --- | --- | --- |
| Version | Health endpoint reports `1.2.1`. | Plugin header and local package target `1.3.0`. | Reviewed deployment, migration, cache purge, rollback exercise, live health, and rendered-page evidence. |
| Home hierarchy | Food imagery is present, but institutional services, operations, platform, campaigns, and proposal language dominate the journey. | Food-first header, current-order action, dish-led home sections, group meals, food stories, guides, and visit path are implemented in source. | Desktop and 390 CSS pixel Chrome evidence showing the food hierarchy and absence of private-system language. |
| Dish index | Public catalog has 0 sections and 0 items. The page displays an empty state and process-oriented editorial copy. | 12 bilingual dish references are present and marked for display in the local consumer menu. | Render all 12 exactly once in Hebrew and English, verify links, images, ordering continuation, canonicals, and mobile behavior. |
| Filters and badges | No dish cards, filters, or food-property badges are live. | 7 filters and 9 badges are wired to dish cards. Filters cover all, pita, plate, pots, vegetarian menu, meat, and fish. | Keyboard, touch, `aria-pressed`, result count, empty result, RTL/LTR, and no-JavaScript continuation checks. |
| Group meals | The live proposal page is framed as institutional fit and includes operational requirements. | A consumer route for groups and workplaces is implemented, with a dish-led teaser and a group-meal request form. | Confirm the staffed handling owner, response process, consent, retention, field validation, confirmation, and safe failure state. |
| Ordering | The live hierarchy does not make the restaurant ordering path the primary consumer continuation. | Hebrew and English actions point to the current Wolt restaurant route. Dish records use `provider_check` for availability. | Recheck both provider destinations at release time. Do not copy provider price, option, or availability into WordPress without a current authoritative feed. |
| Store | `/store/` is a `noindex` future architecture page with no products, cart, checkout, or payment. | The pantry is omitted from navigation unless commerce is ready or an administrator is in private preview. Native purchase surfaces remain fail-closed. | Every commerce gate, a real WooCommerce dependency action, real products, current inventory authority, test orders, refund proof, and live customer-support ownership. |
| Product preparation | No public product layer exists. | 26 private ingredient products have bilingual records, private market benchmarks, evaluation quantity `1`, closed approval gates, and held WebP bindings. | Merchant and supplier evidence, public-safe labels and images, allergens, tax, shipping, returns, payment, inventory, and explicit storefront approval. |
| Generated imagery | Live pages use the earlier archive set with limited documentary scope. | 50 generated assets are registered: 16 dish, 23 ingredient, and 11 supply assets. All are held for review. | Human visual review, entity fit, rights record, accurate alt and caption, responsive delivery, and explicit public-use approval. Generated images cannot prove the current dish, package, stock, branch, or service. |
| Public and private boundary | The live public header, footer, home, dish, store, and proposal pages expose operational and institutional concepts. | Consumer navigation is separate. Private inventory synchronization, evaluation data, and operating infrastructure remain out of public rendering. | Verify retired public routes, redirects or truthful removal, sitemap, internal links, structured data, search ownership, and absence of private terms. |

## The 12 local dish references

All 12 records are local 1.3.0 menu references. Their current price, options,
and availability are checked at the external ordering destination. The badge
labels describe menu format or food family. They are not allergen, nutrition,
health, kosher, or medical claims.

| Dish | Local slug | Filters | Visible badges |
| --- | --- | --- | --- |
| הסביח של 99 / The 99 Sabich | `sabich` | pita, plate, vegetarian | pita, vegetarian |
| קובה סלק / Beet Kubbeh | `beet-kubbeh` | pots, meat | pots, meat |
| שניצל / Israeli Schnitzel | `schnitzel` | pita, plate, meat | pita, plate |
| שקשוקה / Shakshuka | `shakshuka` | pita, plate, vegetarian | pan, vegetarian |
| קציצות ביתיות / Home-style Meatballs | `homemade-meatballs` | pita, plate, meat | plate, meat |
| קציצות דגים / Fish Patties | `fish-patties` | plate, fish | fish, picante |
| חזה עוף על הפלנצ׳ה / Griddled Chicken Breast | `grilled-chicken` | pita, plate, meat | griddled, plate |
| עיג׳ה, חביתת ירק / Aja Herb Omelette | `aja-herb-omelet` | pita, plate, vegetarian | pita, vegetarian |
| קוסקוס / Couscous | `couscous` | pots, meat | pots, meat |
| מרק בשר תימני / Yemenite Beef Soup | `yemenite-beef-soup` | pots, meat | pots, meat |
| סבטוחה / Sabtucha | `sabtucha` | pita, vegetarian | pita, vegetarian |
| כבד עוף / Chicken Liver | `chicken-liver` | plate, meat | plate, meat |

The dish card is only the first layer. Each dish still needs a precise
consumer-facing continuation with:

- one approved image and honest caption;
- the dish name and one concise sensory description;
- the same controlled badges used by the filter;
- an ingredient summary that matches the ordering record;
- a clear warning to ask the team about allergens or substitutions;
- related ingredients, food stories, and guides only when substantive;
- a current external ordering continuation;
- no copied current price, stock, delivery time, or option claim.

## Side-by-side benchmark

| Brand or surface | What the reviewed evidence does well | Friction or limit observed | Complete99 adaptation |
| --- | --- | --- | --- |
| Complete99 live 1.2.1 | Distinctive green and cream identity, bilingual routes, large food image on mobile, strong target sizes, and no first-load offer wall. | The live first screen and navigation mix food with institutional services, operations, platform, campaigns, and fit review. The dish path is empty. | Preserve the visual identity and accessibility behavior. Replace the public hierarchy with the local food-first structure only after the release is proven live. |
| Complete99 local 1.3.0 | Source now connects a food-first header, 12 dish cards, filters, badges, group meals, food stories, guides, visit details, and an external order path. | It has no public screenshot or deployment evidence in this review. Product and generated-asset data remain private or held. | Treat it as the next acceptance candidate, not as a public benchmark result. |
| Sweetgreen | The official menu exposes strong category hierarchy, large dish photography, ingredient descriptions, dietary markers, and item-level nutrition. Order access remains visible. | The supplied desktop opens with a large sourcing statement before the menu cards. The mobile privacy panel covers a large part of the first menu item. | Use clear cards and controlled traits. Keep Complete99 free of a first-load panel. Do not add nutrition or sourcing claims without owned evidence. |
| Wagamama | Full-bleed food establishes menu intent immediately. The category strip, vegan and vegetarian switches, and dietary-filter control make a large menu scannable on desktop and mobile. | The reviewed hero includes a regional menu continuation, and the mobile cookie panel begins to occupy the lower viewport. | Keep filters close to the dishes and preserve their state accessibly. Avoid region ambiguity, overloaded category strips, and unsupported dietary promises. |
| Dishoom | Documentary dining-room photography gives group dining a human occasion. The official page explains group size, shared feast formats, vegetarian and vegan variants, booking or enquiry, and practical FAQs. | The large cookie panel competes with the first content section. Its restaurant reservation model and service promises cannot be copied without equivalent capacity. | Make the Complete99 group-meal page food-led and occasion-led. Ask for diners, timing, serving format, preferences, and contact details, then confirm specifics through a staffed process. |
| Hummus Ashkara | The Hebrew menu is direct: food categories, concise item names, visible traits, a location, and literal Wolt, 10bis, and call continuations. | It relies on third parties for ordering and therefore does not provide a full owned product-detail or checkout graph. | Match its immediacy and local clarity. Keep the ordering handoff explicit and let the provider own current transactional facts. |
| Delitlv | The reviewed category and product pages provide filters, quantities, unit context, product details, ingredients, allergens, nutrition, heating guidance, order dates, related items, and cart action. Group-serving guidance helps people estimate a meal. | The depth requires real commerce operations, current product data, and careful taxonomy control. Checkout was not executed in this review. | Use it as the item-completeness bar after the Complete99 store passes. Do not expose the 26 private evaluation records as a shortcut. |
| R2M | Food, people, places, hospitality, and documentary imagery create local confidence. The group and hospitality paths connect food with real occasions. | The group ecosystem is broad and can make the shortest consumer decision less direct. | Add authored place and people evidence only when rights and facts are approved. Keep dishes and current ordering ahead of the broader story. |

## Gap map and priorities

| Priority | Gap | Evidence now | Required next result |
| --- | --- | --- | --- |
| P0 | Public release truth | Live health is 1.2.1. Local source is 1.3.0. | Do not call 1.3.0 live until the exact reviewed artifact is deployed and the live health, body, migration, cache, rollback, desktop, and mobile evidence pass. |
| P0 | Food-first hierarchy | The live header and home still market services and operations. The local header starts with Menu, Groups and workplaces, Food stories, Guides, Our story, and Visit. | Deploy the consumer hierarchy and prove that private-system routes and wording no longer appear in public navigation, body, footer, sitemap, schema, or search ownership. |
| P0 | Dishes before explanation | Live dish index has no items. Local source has 12 named dishes. | Render the 12 cards above long editorial material, with one clear order continuation and no duplicate or broken route. |
| P0 | Group-meal journey | Live proposal copy is institutional and operational. Local source has a consumer group-meal page and form. | Present meals for teams, meetings, families, and gatherings as a food request. Keep internal qualification and operating workflow out of the public page. |
| P0 | Filters and badges | Absent live. Implemented locally. | Verify every filter on desktop and mobile, keyboard and touch, Hebrew and English. Keep labels limited to verified format and food-family facts. |
| P1 | Dish transparency | Local cards contain concise menu descriptions and controlled traits. | Add ingredient summaries, careful allergen continuation, sources where editorial claims are made, and related food entities without copying provider transaction data. |
| P1 | Visual proof | 50 generated files exist, but all remain held. | Select only reviewed assets whose subject, composition, rights, caption, and entity relation are accurate. Continue using documentary archive images only within their recorded rights. |
| P1 | Local trust | The public read model contains one branch record, but the live consumer journey is not yet a complete location page. | Verify visible address, phone, access, current hours, ordering destination, responsible owner, and reciprocal language pages before expanding local search claims. |
| P2 | Commerce | Local evaluation has 26 complete private data paths, but public sale is false for every record. | Install and configure the real commerce dependency through its own controlled action, replace evaluation evidence with approved merchant and product evidence, complete checkout and refund acceptance, then open the pantry. |
| P2 | Content-commerce graph | Local source connects dishes and ingredient planning, but no public products can complete the graph. | After store approval, connect each real product to one useful dish, ingredient, recipe, or guide while preserving separate editorial and transaction intents. |

## Winning public hierarchy

The release target should read as a food website before it reads as a company
website.

```text
Header
|-- Menu
|-- Groups and workplaces
|-- Food stories
|-- Guides
|-- Our story
|-- Visit
`-- Current ordering action

Home
|-- Food hero and current ordering continuation
|-- Twelve dish cards
|-- Menu filters and controlled badges
|-- Group-meal invitation
|-- Ingredients, food stories, and guides
|-- Visit and contact
`-- Consumer policies

Dish
|-- Name, image, concise description, and badges
|-- Ingredient summary and allergen continuation
|-- Related ingredients and food stories
`-- Current external ordering continuation

Group meals
|-- Occasion and food formats
|-- Number of diners and timing
|-- Serving and packaging preference
|-- Group food preferences
`-- Consent-aware request form

Pantry
`-- Hidden from public navigation until the commerce gate passes
```

The public header and page copy should use food words that a diner understands.
It should not mention release versions, evaluation records, review labs,
materialization, synchronization, inventory controls, deployment, evidence
gates, or operating dashboards.

## Store gate

The 26 local product records and their 26 held image bindings make the data path
inspectable. They do not make a shop.

The store remains closed until all of the following are true:

- the merchant identity and customer-support route are verified;
- each product has approved identity, unit, positive customer price, tax
  treatment, current stock authority, public-safe images, ingredients,
  allergens, storage, handling, and fulfilment terms;
- payment, shipping or pickup, cancellation, returns, privacy, terms, and
  accessibility reflect the real service;
- WooCommerce is installed through a separately controlled and reversible
  dependency action;
- test order, failed payment, stock movement, transactional email, refund,
  analytics, and security checks pass;
- visible product facts and structured data match;
- the final release receipt and live browser evidence pass.

Until then:

- public sale eligibility remains false;
- private evaluation quantity is never presented as customer stock;
- private benchmark price is never presented as a current price;
- generated packaging or product-style imagery is never presented as a current
  sellable item;
- product, cart, checkout, account, and Store API surfaces remain blocked;
- the store remains `noindex` and outside the public sitemap.

## Release acceptance for this gap map

The next public review should not reuse local-source counts as proof. It should
collect fresh evidence for:

1. Live health reports version 1.3.0.
2. Hebrew and English home pages show food first.
3. The menu page renders all 12 expected dishes exactly once.
4. The 7 filters and their result count work with keyboard, pointer, and touch.
5. Dish cards display only the approved 9 badge types.
6. Every dish and ordering action reaches a valid continuation.
7. The group-meal route and form work in both languages without exposing
   internal operating questions.
8. The store is absent from public navigation and remains `noindex` while
   commerce is held.
9. No public product, price, stock, cart, checkout, account, or Store API
   surface is exposed.
10. No held generated asset is presented as current documentary product proof.
11. Desktop and 390 CSS pixel views have no overflow, clipping, broken image,
    hidden primary task, or first-party console error.
12. The legacy institutional and private-system wording is absent from public
    navigation, footer, body, structured data, sitemap, and SEO ownership.

## Local source evidence

```text
plugin/complete99-platform/data/consumer-menu.php
plugin/complete99-platform/data/culinary-facets.php
plugin/complete99-platform/data/consumer-content.php
plugin/complete99-platform/data/catalog-product-seeds.php
plugin/complete99-platform/data/generated-asset-manifest.php
plugin/complete99-platform/includes/class-complete99-consumer.php
plugin/complete99-platform/includes/class-complete99-commerce.php
plugin/complete99-platform/includes/class-complete99-evaluation-catalog.php
docs/consumer-commerce-runbook.md
tests/test_food_first_consumer_contracts.py
tests/test_consumer_commerce_contracts.py
tests/test_market_catalog_contracts.py
tests/test_generated_asset_manifest_contracts.py
tests/test_evaluation_catalog_contracts.py
```

## Screenshot evidence

Current 2026-07-31 benchmark captures:

```text
C:\Users\pro\Documents\websites\.codex-tmp\complete99-benchmark-2026-07-31\01-sweetgreen-menu-desktop.png
C:\Users\pro\Documents\websites\.codex-tmp\complete99-benchmark-2026-07-31\01-sweetgreen-menu-mobile.png
C:\Users\pro\Documents\websites\.codex-tmp\complete99-benchmark-2026-07-31\02-wagamama-menu-desktop.png
C:\Users\pro\Documents\websites\.codex-tmp\complete99-benchmark-2026-07-31\02-wagamama-menu-mobile.png
C:\Users\pro\Documents\websites\.codex-tmp\complete99-benchmark-2026-07-31\03-dishoom-group-feasts-desktop.png
```

Prior side-by-side competitor sheet:

```text
C:\Users\pro\Documents\websites\.codex-tmp\complete99-competitor-benchmark\complete99-competitor-contact-sheet.png
```

Complete99 live 1.2.1 and baseline captures reviewed:

```text
C:\Users\pro\Documents\websites\.codex-tmp\complete99-live-1.2.1\complete99-live-1.2.1-mobile-he-390.png
C:\Users\pro\Documents\websites\.codex-tmp\complete99-live-1.2.1\complete99-live-1.2.1-mobile-en-390.png
C:\Users\pro\Documents\websites\.codex-tmp\complete99-live-1.2.1\complete99-live-1.2.1-mobile-menu-he-390.png
C:\Users\pro\Documents\websites\.codex-tmp\complete99-consumer-benchmark-2026-07-29\complete99-current-2026-07-29-desktop.png
```

## Source URLs

Complete99 live:

- <https://a235232-tmp.s1242.upress.link/>
- <https://a235232-tmp.s1242.upress.link/en/>
- <https://a235232-tmp.s1242.upress.link/dishes/>
- <https://a235232-tmp.s1242.upress.link/store/>
- <https://a235232-tmp.s1242.upress.link/request-proposal/>
- <https://a235232-tmp.s1242.upress.link/wp-json/complete99/v1/health>
- <https://a235232-tmp.s1242.upress.link/wp-json/complete99/v1/public-catalog>

Current ordering continuation:

- <https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete>
- <https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete>

Global benchmark:

- <https://www.sweetgreen.com/menu>
- <https://www.wagamama.com/menu>
- <https://www.dishoom.com/group-feasts/>

Israeli benchmark:

- <https://hummus-ashkara.co.il/he/menu/>
- <https://hummus-ashkara.co.il/en/menu/>
- <https://www.delitlv.co.il/hosting.html>
- <https://www.delitlv.co.il/1701567.html>
- <https://www.r2m.co.il/>
- <https://www.r2m.co.il/r2m-b2b/>

## Closing position

Complete99 does not need more public complexity before it proves the local
consumer release. It needs one clear food hierarchy that works end to end:

`food -> dish -> useful traits -> group or individual continuation -> current ordering source`

The local 1.3.0 source is materially closer to Sweetgreen and Wagamama for menu
scanning, and to Dishoom and Delitlv for group-meal planning. The decisive
remaining gap is public proof. Until deployment and live acceptance succeed,
the public site remains 1.2.1, the 26 products remain private, the 50 generated
assets remain held, and the store remains closed.
