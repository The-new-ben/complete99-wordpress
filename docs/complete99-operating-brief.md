# Complete99 operating brief

Last updated: 2026-08-06
Status: source release target 1.10.0, with 12 dishes, 36 unchanged public products, five private draft planning offers and 22 public Japanese science entities across 17 page owners per language

This is the private source of truth for the Complete99 public website, operating
platform, knowledge system, commerce foundation, growth work, and future
integrations. It is intentionally broader than a website brief. Public copy must
never expose this document's deployment, evidence-gate, cost, supplier, staff,
camera, credential, or internal-control language.

## 1. The business we are building

Complete99 combines four connected layers:

1. A public bilingual culinary consumer website for the restaurant at 99 Ibn
   Gabirol, Tel Aviv.
2. A culinary knowledge system covering dishes, ingredients, traditions and
   practical guides.
3. A private operating platform for inventory, orders, suppliers, costs, tasks,
   campaigns, workers and future integrations.
4. A consumer ingredient shop backed by WooCommerce and linked to the food graph.

The public promise is food: what Complete99 cooks, what goes into it, the
traditions around it, and where a visitor can check the current menu and order.
The private operating platform has no place in the consumer website hierarchy or
copy.

## 2. Audience decision

### Primary

- People deciding what to eat from Complete99 in Tel Aviv.
- People looking for sabich, beet kubbeh, couscous, soups, pita dishes and
  home-style plates.
- Hebrew and English visitors who need a clear continuation to the current
  ordering menu.

### Secondary

- Readers interested in ingredients, traditions and practical food guidance.
- Store customers choosing pantry ingredients for pickup in Tel Aviv.

### Not addressed at launch

- Medical nutrition or individual health advice.
- Institutional services, procurement, workforce, inventory, supplier, cost,
  campaign or operating-system marketing.
- Franchising and investment solicitation.
- Electronic payment until provider credentials, exact supplier-label and
  applicable country-of-origin records, and live checkout acceptance are
  operational.

## 3. Public and private boundaries

### Appropriate for the public site

- Food, dishes, ingredients, traditions and practical culinary guidance.
- The verified restaurant address, telephone and external ordering continuation.
- Verified dishes and ingredients with sources, authorship, review and dates.
- Approved consumer images and real location facts.
- Real products, prices, stock, delivery terms, and policies after commerce
  readiness is approved.

### Private-only

- Institutional services, proposals, operating dashboards, private campaigns,
  recipe production specifications, yields, food cost, margin, supplier terms,
  purchase orders, staff records, incidents and internal tasks.
- Camera feeds, device commands, credentials, telemetry, access logs, and
  security controls.
- Draft content, failed checks, evidence workflows, migration and deployment
  terms, signing details and machine-generated drafts.

No Complete99 worker role is installed or assigned by release 1.6.0. Role
definitions remain dormant infrastructure. The commerce outbox has no worker
assignment.

## 4. Public information architecture

Hebrew is the root language. English mirrors the same hierarchy under `/en/`.

| Hub | Canonical responsibility | Launch index policy |
| --- | --- | --- |
| `/` | Food-first Hebrew home and ordering continuation | Index |
| `/en/` | Mirrored English home | Index |
| `/dishes/` | Dish discovery and approved live dish links | Index hub; gate every dossier |
| `/ingredients/` | Ingredients in the context of the food | Index |
| `/traditions/` | Homes, communities and cooking traditions | Index |
| `/knowledge/` | Practical guidance for choosing and understanding dishes | Index |
| `/store/` | Curated 36-product culinary store and cart | Index when the exact catalog receipt is ready |
| `/about/` | Restaurant story and consumer identity | Index |
| `/contact/` | Verified address, telephone and contact route | Index |
| `/privacy/`, `/terms/`, `/accessibility/` | Consumer policy foundation | Index |

### Entity ownership and cannibalization rules

- A dish dossier owns “what is this dish,” its identity, its verified context,
  and one primary tested recipe.
- A standalone recipe owns a genuinely distinct preparation intent. Print,
  scaling and cook-mode variants are utility views and canonicalize to the
  owning dossier.
- An ingredient page owns ingredient facts and uses; it does not retell a dish
  dossier.
- A tradition page owns cultural and historical context and links to relevant
  dishes. It does not target transaction queries.
- A product page owns a real SKU or variation family. Editorial pages may link
  to products but do not duplicate commercial intent.
- A location page exists only for a genuine consumer location with current
  address, hours and contact facts.

Every indexable page has exactly one primary intent owner, one canonical path,
one locale in a translation group, one parent hub, an index policy, an evidence
profile, a schema profile, and editorial ownership.

## 5. Navigation and page composition

### Header

- One utility layer for current ordering and language.
- Five consumer discovery links: Dishes, Ingredients, Traditions, Knowledge and
  Pantry.
- One persistent action to the verified language-specific Wolt menu.
- A server-rendered menu with real links, keyboard wrapping, Escape, focus return,
  visible focus and RTL/LTR parity.

### Footer

- Dishes, ingredients, traditions and knowledge.
- Store, cart and pickup path when the exact catalog receipt is ready.
- About, contact, privacy, terms, accessibility and commerce policies when
  applicable.
- Only verified social profiles and contact details.

### Dish dossier

1. Deep breadcrumb trail.
2. Bilingual dish identity and rights-cleared images.
3. Verified ingredients and allergen boundaries.
4. Sourced origin and tradition context with claim-level citations.
5. A public-safe preparation summary.
6. Tested recipe, yield, serving scaling, and cook mode.
7. Ingredient encyclopedia links.
8. Related dishes, recipes and traditions.
9. Clearly separated relevant products, when real.
10. Sources, author, chef tester, editor and review dates.

No word count, AI detector, or template completion can make a dossier
publishable by itself. The existing 5,000-word bilingual rule is a minimum
editorial gate, not a writing target and never permission to add filler.

## 6. Visual and experience direction

The benchmark combines:

- Hummus Ashkara's direct local menu and ordering continuation.
- Ottolenghi's recipes, ingredient encyclopedia, and content-commerce graph.
- Eataly's editorial category landings.
- Sweetgreen's ingredient, nutrition, location, and menu transparency.
- Farmer J's recognizable illustration language.

Complete99 must outperform common competitor defects:

- No first-load lead, discount, report, or cookie wall over the hero.
- No carousel dependence or autoplay.
- No generic SaaS gradients standing in for food and people.
- No tiny controls or missing keyboard focus.
- No invented proof tokens, customer logos, social accounts, or certification
  marks.

Acceptance targets include no horizontal overflow at 390 CSS pixels, primary
controls at least 44×44 CSS pixels, body copy at least 16 CSS pixels, a
high-contrast focus indicator at least two CSS pixels thick, reduced-motion
support, responsive AVIF/WebP imagery, explicit image dimensions, one optimized
eager LCP image, lazy loading below the fold, and zero first-party console
errors.

## 7. Infrastructure without imposed roles

Release 1.10.0 creates contracts and checks, not worker assignments.

- The private Entity Studio is an administrator-only WordPress tool backed by
  the existing culinary-science, authorized catalog and commerce registries.
- It creates no new role and does not assign workers, reviewers or operators.
- Its dossiers remain private and cannot become public pages or active offers
  through the Studio.

- WordPress keeps consumer publishing and commerce readiness metadata.
- WooCommerce is the product, cart and stock authority. It becomes the payment
  authority only after gateway credentials and live acceptance.
- The private outbox records order, refund and stock facts without direct
  customer contact or address data.
- Complete99 OS may consume the outbox later through authenticated access.
- `worker_assignment_mode` remains `unassigned_infrastructure`.
- No migration calls the dormant WordPress role installer.
- Any future owner, approval chain, supplier process or campaign workflow needs
  a separate operating decision.

### Public read-model integrity

Release 1.8.0 changes the integrity boundary only. It does not change the 12
public dishes or any of the 32 public catalog items.

- WordPress computes the read-model SHA-256 from a recursive canonical form.
- The top-level `digest` field is excluded from its own hash, ordered lists keep
  their order, and associative keys are sorted at every depth.
- Stored and recomputed digests are compared with `hash_equals` before a model
  can be fresh or influence any public menu, route, SEO row or sitemap entry.
- A missing, malformed, arbitrary or content-mismatched digest makes the model
  non-fresh and activates the approved packaged-menu fallback.
- The public health response exposes digest, version and generation time only
  when the complete stored transport envelope passes shape and causal hash
  validation. It never substitutes a newly computed value for stored state.
- WordPress persists the normalized OS transport envelope unchanged, then adds
  only its top-level digest. `generated_at` uses exact UTC millisecond form and
  every public item carries that byte-identical timestamp.
- A narrow one-time legacy gate recognizes the 1.7 envelope, verifies its old
  digest when present, preserves ID and slug ownership, and permits the stable
  12-item live fallback state without a digest only when its IDs and slugs match
  the packaged catalog in canonical order.

## 8. Store readiness

WooCommerce is the commerce engine; a custom cart is not. Release 1.9.0 opens
the exact 36-product catalog, classic cart and local-pickup continuation. The
electronic checkout remains closed until the payment-specific evidence below
passes:

Release 1.10.0 does not change that public catalog, cart or route surface. It
adds five private draft planning offers and zero active offers. Payment remains
disabled.

- merchant identity and contact details;
- payment provider and live-account verification;
- exact supplier-label, online allergen and applicable country-of-origin
  records for every product;
- checkout-specific cancellations, privacy, terms and customer support review;
- test orders, refunds, taxes, emails, stock movement, analytics, and security;
- truthful Product/Offer structured data matching the visible page.

### Commercial evidence and expansion model

Every current and future sellable entity carries a modular commercial record:

- a stable product code, locale-neutral identity and market;
- one or more dated source-market observations with currency, tax state,
  availability state and exact source URL;
- a documented exchange-rate source and arithmetic when the observation is not
  in ILS;
- the owner-authorized channel price, price effective date and sales channel;
- package quantity, comparable unit price and quality tier;
- opening stock policy, backorder policy, fulfilment class and stock authority;
- complementary products, premium alternatives and the culinary reason for
  each cross-sell or up-sell;
- procurement cost, freight, duty, tax, handling, waste, landed cost and gross
  margin fields that stay null until commercial evidence supports them.

Release 1.9.0 proves this pattern with four Japanese-foundation products. Their
public prices are ILS 149 for 2 kg Uozu Koshihikari rice, ILS 119 for 500 g
dried rice koji, ILS 109 for 20 g Chouhaku-kin starter culture and ILS 119 for
one 50 to 60 g Dutch-grown fresh wasabi rhizome. These are authorized opening
retail prices informed by the dated market observations. They are not claims
about supplier cost or achieved margin.

Release 1.10.0 extends private planning coverage to all 41 product identities.
The five new private plans are ILS 219 for 200 g Honkarebushi belly, ILS 249
for 720 ml three-year Fukumitsuya hon mirin, ILS 349 for 720 ml ten-year
Fukumitsuya hon mirin, ILS 199 for 720 ml Kito yuzu juice and ILS 649 for a
36 cm Umezawa hangiri. They remain draft planning values. They are not live
offers, supplier quotations, landed costs, achieved margins or availability
claims.

## 9. Integrations and future hardware

The public WordPress site is the discovery and publishing layer. Sensitive
operations belong behind an authenticated portal and API boundary.

- The UPress WordPress site is the long-term public/SEO source of truth.
- `https://complete99-public.benben777.chatgpt.site/` is a sanitized, public
  historical application reference. It is not the consumer website, is not an
  authoritative source for public claims and is not marketed or linked as a
  consumer feature.
- `https://complete99-os.benben777.chatgpt.site/` is the private owner
  prototype. It must remain access-controlled until server-side roles,
  per-record authorization, production identity, and security review are
  complete. Making that whole project public would expose the command surface.
- Social providers require real accounts, OAuth authorization, least privilege,
  scheduled content approval, and provider receipts.
- Supplier links require an approved supplier, API/file contract, reconciliation
  rules, failure ownership, and audit logs.
- Cameras and devices require an explicit security design, device inventory,
  consent/legal review, access control, encryption, retention, and human
  emergency controls. No feed or command is exposed publicly.
- Tablets and branches consume role-appropriate private views.
- AI agents use source-bounded tools, narrow permissions, approval thresholds,
  evaluations, and immutable action logs. They cannot self-approve public
  claims, regulated instructions, purchases, payments, or social publishing.
- Robots and future automation integrate through versioned private APIs and
  simulators before any physical action.

## 10. Delivery and proof

The live UPress deployment path is:

reviewed Git source → deterministic plugin ZIP and hash → protected main and
required CI → a temporary administrator-gated WordPress REST bridge → install
with overwrite → independent health and rendered-body verification → cache
flush → permanent bridge deletion and route-404 proof.

Every production release must preserve:

- exact commit and artifact identity;
- PHP lint, tests, package inspection, and secret scan;
- rendered-body positive markers and negative checks for superseded output;
- health endpoint, main routes, canonical/hreflang, and structured data;
- temporary snippet row absence and deployment route 404;
- desktop and mobile real-Chrome screenshots;
- keyboard navigation, visible focus, console, overflow, and control-size checks;
- a recoverable rollback path and a periodic exercised rollback.

Current live alias: `https://a235232-tmp.s1242.upress.link/`
Future canonical domain: `complete99.co.il` after registration and DNS/SSL.

## 11. Evidence-backed sources

- Google Search Central, ecommerce navigation and crawlable hierarchy:
  <https://developers.google.com/search/docs/specialty/ecommerce/help-google-understand-your-ecommerce-site-structure>
- Google Search Central, spam policies and scaled content:
  <https://developers.google.com/search/docs/essentials/spam-policies>
- Google Search Central, recipe structured data:
  <https://developers.google.com/search/docs/appearance/structured-data/recipe>
- Google Search Central, merchant listing structured data:
  <https://developers.google.com/search/docs/appearance/structured-data/merchant-listing>
- WordPress core sitemap provider hook:
  <https://developer.wordpress.org/reference/hooks/wp_sitemaps_add_provider/>
- WooCommerce setup and launch checklist:
  <https://woocommerce.com/document/woocommerce-setup-wizard/>
- WCAG 2.2 target size:
  <https://www.w3.org/WAI/WCAG22/Understanding/target-size-enhanced>
- WCAG 2.2 focus appearance:
  <https://www.w3.org/WAI/WCAG22/Understanding/focus-appearance.html>
- Core Web Vitals:
  <https://web.dev/articles/vitals>
