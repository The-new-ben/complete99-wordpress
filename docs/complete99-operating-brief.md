# Complete99 operating brief

Last updated: 2026-07-28  
Status: live foundation, first premium information-architecture release in progress

This is the private source of truth for the Complete99 public website, operating
platform, knowledge system, commerce foundation, growth work, and future
integrations. It is intentionally broader than a website brief. Public copy must
never expose this document's deployment, evidence-gate, cost, supplier, staff,
camera, credential, or internal-control language.

## 1. The business we are building

Complete99 combines four connected products:

1. Institutional foodservice for organizations.
2. A culinary knowledge system covering dishes, recipes, ingredients, and
   traditions.
3. A private, multi-location operating platform for opening routines, people,
   food production, exceptions, assets, and campaigns.
4. A future store for verified products, equipment, pantry goods, and useful
   foodservice tools.

The public promise is simple: food, operations, and responsible growth working
from one coherent foundation. The operating platform is a capability within the
service relationship, not a self-serve SaaS product at launch.

## 2. Audience decision

### Primary

- Procurement, facilities, HR, operations, and foodservice leaders at Israeli
  companies, offices, manufacturing sites, and logistics organizations.
- Owners, general managers, branch managers, chefs, and operational teams
  evaluating the Complete99 working model.

### Secondary

- International institutional buyers using the English site.
- Readers seeking sourced culinary knowledge, after dish dossiers pass their
  editorial, source, kitchen, image-rights, allergen, and translation checks.
- Retail customers, only after real products and fulfillment exist.

### Not addressed at launch

- Walk-in restaurant ordering.
- Medical nutrition or individual health advice.
- Education, minors, senior living, welfare, security, or regulated care
  verticals before qualified evidence and operating approval exist.
- Franchising and investment solicitation.
- Consumer checkout before products, merchant identity, tax, payment, shipping,
  privacy, returns, and customer-service ownership are operational.

## 3. Public and private boundaries

### Appropriate for the public site

- Services, audiences, working method, and an honest product capability tour.
- Verified dishes and ingredients with sources, authorship, review, and dates.
- Approved public locations, images, case studies, and certifications.
- Real products, prices, stock, delivery terms, and policies after commerce
  readiness is approved.

### Private-only

- Recipe production specifications, yields, food cost, margin, supplier terms,
  purchase orders, staff records, incidents, lead records, and internal tasks.
- Camera feeds, device commands, credentials, telemetry, access logs, and
  security controls.
- Draft content, failed checks, evidence workflows, migration/deployment terms,
  signing details, private campaign data, and AI drafts.

The acronym “BOM” is reserved for specialist platform material and must always
be paired with plain-language wording such as “recipe and production
specification.” It does not belong in general consumer navigation.

## 4. Public information architecture

Hebrew is the root language. English mirrors the same hierarchy under `/en/`.

| Hub | Canonical responsibility | Launch index policy |
| --- | --- | --- |
| `/services/` | What Complete99 delivers and how | Index |
| `/industries/` | Who the service fits and the constraints considered | Index when substantive |
| `/platform/` | Honest capability tour of the operating system | Index |
| `/dishes/` | Verified dish dossiers and dish discovery | Index hub; gate every dossier |
| `/ingredients/` | Ingredient identity, uses, storage, and sourced knowledge | Index hub; gate every article |
| `/traditions/` | Carefully sourced culinary and Jewish-tradition context | Index hub; gate every article |
| `/knowledge/` | Guides, recipes, methods, and editorial standards | Index |
| `/case-studies/` | Permissioned institutional evidence | Hold until a real approved case exists |
| `/locations/` | Genuine branches with verified NAP, hours, photos, and service facts | Hold until a branch record is verified |
| `/store/` | Commercial categories and real purchasable products | Hold/noindex until commerce is configured |
| `/about/` | Identity, responsibilities, editorial method, and team | Index |
| `/request-proposal/` | Institutional qualification and conversion | Index without competing with services |

### Entity ownership and cannibalization rules

- A dish dossier owns “what is this dish,” its identity, its verified context,
  and one primary tested recipe.
- A standalone recipe owns a genuinely distinct preparation intent. Print,
  scaling, and cook-mode variants are utility views and canonicalize to the
  owning dossier.
- An ingredient page owns ingredient facts and uses; it does not retell a dish
  dossier.
- A tradition page owns cultural and historical context and links to relevant
  dishes; it does not target ordering or service queries.
- A service page owns what is delivered. An industry page owns the audience's
  constraints and fit. The proposal page owns conversion.
- A product page owns a real SKU or variation family. Editorial pages may link
  to products but do not duplicate commercial intent.
- A location page exists only for a genuine location. Named institutions appear
  only in permissioned case studies, never in generated “client” pages.

Every indexable page has exactly one primary intent owner, one canonical path,
one locale in a translation group, one parent hub, an index policy, an evidence
profile, a schema profile, and editorial ownership.

## 5. Navigation and page composition

### Header

- One utility layer for language and future verified contact/account actions.
- Six or seven crawlable primary hubs, not a flat inventory of leaf pages.
- One persistent institutional action: request a fit assessment.
- One persistent discovery action: explore dishes and knowledge.
- A server-rendered mega menu with real links, full keyboard support, Escape,
  focus return, visible focus, and RTL/LTR parity.

### Footer

- Services and audiences.
- Dishes, ingredients, traditions, and knowledge.
- Platform and proposal paths.
- Store categories only when they are truthful and useful.
- About, contact, privacy, terms, accessibility, and commerce policies when
  applicable.
- Only verified social profiles and contact details.

### Dish dossier

1. Deep breadcrumb trail.
2. Bilingual dish identity and rights-cleared images.
3. Verified ingredients, allergens, nutrition, and availability at a glance.
4. Sourced origin and tradition context with claim-level citations.
5. A public-safe preparation summary.
6. Tested recipe, yield, serving scaling, and cook mode.
7. Ingredient encyclopedia links.
8. Institutional formats and proposal action.
9. Related dishes, recipes, and traditions.
10. Clearly separated relevant products, when real.
11. Sources, author, chef tester, editor, and review dates.

No word count, AI detector, or template completion can make a dossier
publishable by itself. The existing 5,000-word bilingual rule is a minimum
editorial gate, not a writing target and never permission to add filler.

## 6. Visual and experience direction

The benchmark combines:

- Ottolenghi's recipes, ingredient encyclopedia, and content-commerce graph.
- Eataly's editorial category landings.
- Sweetgreen's ingredient, nutrition, location, and menu transparency.
- Fooditude's institutional-buyer clarity.
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

## 7. The 20-role council

| Role | Public launch requirement | Private/near-term requirement | Claims held until proven |
| --- | --- | --- | --- |
| Owner/CEO | Clear commercial promise and audience hierarchy | Portfolio and branch growth model | Scale, leadership, revenue, or customer claims |
| COO | Honest operating capability tour | Real day opening, handovers, exceptions, audit trail | Decorative dashboards described as live |
| Chef | Dish hub and visible editorial method | Recipe versions, yield, kitchen test, chef sign-off | Untested recipes or origin claims |
| Food safety | Responsibility and allergen boundaries | Food-safety controls and incident workflow | Suitability, kosher, or compliance without evidence |
| Procurement | Sourcing and receiving method | Suppliers, items, substitutions, POs, discrepancies | Supplier names, prices, or endorsements |
| Finance | Clear business models and proposal path | Costing, budget, VAT, invoices, branch P&L | Savings or pricing without an approved model |
| Institutional sales | Strong qualification and proposal journey | CRM stages, owner, SLA, tender workspace | Availability, capacity, or customer claims |
| Marketing | Consistent brand and owned assets | Briefs, approvals, UTMs, calendar, measurement | Campaign outcomes without source data |
| Social | Reserved channel strategy | Real OAuth, scheduling, moderation, receipts | “Connected” or “published” before provider proof |
| SEO | Hubs, deep breadcrumbs, link graph, ownership registry | Search Console, SERP review, schema and orphan monitoring | Thousands of thin or overlapping pages |
| Commerce | Store architecture and readiness checklist | WooCommerce, products, tax, payment, shipping, orders | Checkout before operational readiness |
| Editorial | Authorship, sources, review, translation quality | Editorial calendar and claim-level citations | Padded, fabricated, or detector-driven prose |
| Design | Complete visual identity and food-rich system | Photography plan, icon family, reusable art direction | Assets without rights |
| UX/accessibility | Usable menu, search, footer, forms, mobile and keyboard QA | Filters, saved journeys, branch-aware navigation | Device controls without safe authenticated UX |
| Branch manager | Truthful location model | Hours, local menus, overrides, ownership | Locations before NAP and hours are verified |
| HR/training | Role and responsibility explanation | Training, competence, handover, acknowledgements | Staff records or certificates on public WordPress |
| Customer service | Clear contact and consent handling | Assigned inbox, notifications, status, retention | Unstaffed phone, email, WhatsApp, or service hours |
| Data/analytics | Define events and launch KPIs | Consent-aware analytics, funnel, attribution, CWV | Decorative numbers described as operating data |
| Security/privacy | Legal/privacy/accessibility foundation | DPA, vendors, access review, incidents | Cameras, people data, secrets, or raw telemetry |
| Automation/AI | Human accountability and honest scope | Permissioned tools, sources, logs, evaluations | AI approving claims, orders, safety, or public sends |

## 8. Store readiness

WooCommerce is the intended commerce engine; a custom cart is not. Installation
does not equal launch. The store remains a non-transactional architecture until
all of the following have an accountable owner and passing evidence:

- merchant identity and contact details;
- approved products, variations, images, descriptions, prices, inventory, and
  VAT treatment;
- payment provider and live-account verification;
- shipping/service regions, rates, packaging, delivery times, and failure
  handling;
- returns, cancellations, privacy, terms, accessibility, and customer support;
- test orders, refunds, taxes, emails, stock movement, analytics, and security;
- truthful Product/Offer structured data matching the visible page.

## 9. Integrations and future hardware

The public WordPress site is the discovery and publishing layer. Sensitive
operations belong behind an authenticated portal and API boundary.

- The UPress WordPress site is the long-term public/SEO source of truth.
- `https://complete99-public.benben777.chatgpt.site/` is a sanitized, public
  interim capability preview. It contains no private operating state.
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
