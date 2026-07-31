# Complete99 operating brief

Last updated: 2026-07-31
Status: bilingual culinary release 1.3.8, 12 dishes and 26-product store candidate

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

No Complete99 worker role is installed or assigned by release 1.3.1. Role
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
| `/store/` | Curated 26-product ingredient store and cart | Index when the exact catalog receipt is ready |
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

Release 1.3.1 creates contracts and checks, not worker assignments.

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

## 8. Store readiness

WooCommerce is the commerce engine; a custom cart is not. Release 1.3.8 opens
the exact 26-product catalog, classic cart and local-pickup continuation. The
electronic checkout remains closed until the payment-specific evidence below
passes:

- merchant identity and contact details;
- payment provider and live-account verification;
- exact supplier-label, online allergen and applicable country-of-origin
  records for every product;
- checkout-specific cancellations, privacy, terms and customer support review;
- test orders, refunds, taxes, emails, stock movement, analytics, and security;
- truthful Product/Offer structured data matching the visible page.

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
