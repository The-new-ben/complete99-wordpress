# Complete99 operating architecture

## Contents

1. Business system
2. Audience and launch boundaries
3. Public and private data boundary
4. Public information architecture
5. Entity ownership and page contracts
6. Navigation and page composition
7. Culinary knowledge and dish dossiers
8. Institutional conversion
9. Commerce readiness
10. Private operating platform
11. Branches and integrations
12. Campaigns, social, and analytics
13. AI and automation governance
14. Visual and experience benchmark
15. Stakeholder council
16. Verification and release evidence
17. Authoritative external references

## 1. Business system

Operate Complete99 as four connected products:

1. Institutional foodservice for organizations.
2. A sourced culinary knowledge system for dishes, recipes, ingredients, and traditions.
3. A private multi-location operating platform for opening routines, people, production, exceptions, assets, campaigns, and controls.
4. A future store for verified products, equipment, pantry goods, and foodservice tools.

Keep one simple public promise: food, operations, and responsible growth working from one coherent foundation.

Use the public WordPress site as the discovery and publishing layer. Use the authenticated platform and versioned private APIs for sensitive operations. Do not expose an internal control plane through a public WordPress template.

## 2. Audience and launch boundaries

### Primary audience

- Procurement, facilities, HR, operations, and foodservice leaders at Israeli companies, offices, manufacturing sites, and logistics organizations.
- Owners, general managers, branch managers, chefs, and operational teams evaluating the Complete99 model.

### Secondary audience

- International institutional buyers using the English site.
- Readers seeking sourced culinary knowledge after the relevant dossiers pass every gate.
- Retail customers only after products and fulfillment are real.

### Hold at launch

- Walk-in restaurant ordering.
- Medical nutrition or individual health advice.
- Education, minors, senior living, welfare, security, or regulated care verticals before qualified evidence and operating approval.
- Franchising and investment solicitation.
- Consumer checkout before full commerce readiness.

## 3. Public and private data boundary

| Class | May contain | Must not imply |
| --- | --- | --- |
| Public discovery | Verified services, audiences, methods, capability tour, approved dishes, approved ingredients, approved traditions, permissioned cases and locations, ready products and policies | Private controls or operational readiness without evidence |
| Private operations | Production specifications, yields, food cost, margin, suppliers, purchase orders, staff, incidents, leads, campaign details, tasks, cameras, devices, credentials, telemetry, access logs | Public availability |
| Editorial draft | Source notes, claims, recipes, images, translations, review status, failed checks, AI drafts | Published or approved status |
| Future architecture | Disabled modules, contracts, schemas, readiness checklists, safe mock data clearly labeled internally | Connected, staffed, certified, available, or live status |

Reserve `BOM` for specialist platform material and pair it with plain language such as “recipe and production specification.” Exclude it from general navigation and consumer copy.

Never expose migration, deployment, signing, evidence-gate, cost, credential, security-control, or internal campaign language on a public page.

## 4. Public information architecture

Make Hebrew the root language and mirror the hierarchy under `/en/`.

| Hub | Canonical responsibility | Initial index policy |
| --- | --- | --- |
| `/services/` | What Complete99 delivers and how | Index |
| `/industries/` | Audience fit, environment, and constraints | Index only when substantive |
| `/platform/` | Honest capability tour of the operating system | Index |
| `/dishes/` | Verified dish dossiers and discovery | Index hub; gate dossiers |
| `/ingredients/` | Ingredient identity, uses, storage, and knowledge | Index hub; gate articles |
| `/traditions/` | Sourced culinary and Jewish-tradition context | Index hub; gate articles |
| `/knowledge/` | Guides, distinct recipes, methods, and editorial standards | Index |
| `/case-studies/` | Permissioned institutional evidence | Hold until a real approved case |
| `/locations/` | Genuine branches with verified NAP, hours, images, and facts | Hold until a verified branch |
| `/store/` | Commercial categories and real products | Hold/noindex until commerce ready |
| `/about/` | Identity, responsibilities, editorial method, and team | Index |
| `/request-proposal/` | Institutional qualification and conversion | Index without competing with services |

Do not point canonical or hreflang to a future production domain until DNS, TLS, redirects, and the destination are verified. Keep every language alternate reciprocal and self-canonical.

Control WordPress sitemaps. Exclude users, internal types, private records, thin archives, empty taxonomies, drafts, held entities, utility views, and noindex commerce surfaces. Include only useful canonical URLs.

## 5. Entity ownership and page contracts

Store these fields for every indexable entity:

- stable entity ID;
- type;
- locale and translation-group ID;
- canonical path;
- parent hub;
- primary search intent;
- supported secondary topics;
- forbidden competing topics;
- index policy and reason;
- evidence profile;
- schema profile;
- editorial owner;
- source owner;
- review status and dates;
- related entities and approved anchor purposes.

Apply these ownership rules:

- Let a dish dossier own dish identity, verified context, and one primary tested recipe.
- Let a standalone recipe own a genuinely distinct preparation intent.
- Canonicalize print, scaling, cook-mode, and other utility recipe views to the owning page.
- Let an ingredient page own ingredient identity, uses, storage, and sourced facts.
- Let a tradition page own cultural and historical context, linking to dishes without targeting ordering or service queries.
- Let a service page own what is delivered.
- Let an industry page own audience constraints and fit.
- Let the proposal page own qualification and conversion.
- Let a product page own a real SKU or variation family.
- Let editorial pages support products without duplicating commercial intent.
- Create a location page only for a genuine location.
- Name an institution only in a permissioned case study; never generate client-name landing pages.

Reject a proposed indexable page when its primary intent, canonical, or content promise overlaps an existing owner. Reassign it as a section, supporting article, filtered utility, or private record.

## 6. Navigation and page composition

### Header and mega menu

Use six or seven crawlable primary hubs. A sound launch grouping is:

1. Services
2. Industries
3. Platform
4. Dishes
5. Culinary knowledge, grouping ingredients, traditions, and guides
6. About
7. Store only when truthful

Keep a persistent institutional action such as “request a fit assessment” and a discovery action for dishes and knowledge.

Render real links server-side. Support pointer, touch, keyboard, Tab, Shift+Tab, Enter, Space where appropriate, Escape, outside dismissal, focus return, visible focus, RTL/LTR parity, and reduced motion. Do not make the menu an exhaustive inventory.

### Footer

Cluster:

- services and audiences;
- dishes, ingredients, traditions, and knowledge;
- platform and proposal paths;
- store categories only when operationally truthful;
- about, contact, privacy, terms, and accessibility;
- shipping, returns, and commerce policies only when applicable;
- verified social profiles and staffed contact routes only.

### Breadcrumbs

Show and mark up the real hierarchy:

`Home → hub → category or tradition → entity`

Keep labels language-correct and align visible breadcrumbs with `BreadcrumbList`.

### Hub pages

Make each hub a useful index with:

- a unique promise and audience;
- concise orientation;
- curated child groups;
- meaningful editorial or operational explanation;
- related-hub links;
- one appropriate conversion or discovery action;
- no empty grid, fake count, or auto-generated filler.

## 7. Culinary knowledge and dish dossiers

Use a dish dossier in this order:

1. Deep breadcrumb.
2. Bilingual identity and rights-cleared images.
3. Ingredients, allergens, nutrition boundaries, and availability at a glance.
4. Sourced origin and tradition context with claim-level citations.
5. Public-safe preparation summary.
6. Tested recipe, yield, serving scaling, and cook mode.
7. Ingredient encyclopedia links.
8. Institutional formats and proposal action.
9. Related dishes, recipes, ingredients, and traditions.
10. Clearly separated relevant products when real.
11. Sources, author, chef tester, editor, and review dates.

Treat 5,000 useful words per language as a minimum gate, never a target. Word count cannot override:

- source quality;
- claim-level citation;
- authorship;
- kitchen testing;
- allergen and cross-contact review;
- image rights;
- translation review;
- editorial judgment;
- intent ownership;
- usefulness.

Never pad content, write to defeat an AI detector, fabricate origins, or turn uncertain tradition into fact. Distinguish evidence, oral tradition, adaptation, and house method.

Avoid medical promises. Explain ordinary culinary or nutritional context cautiously, with suitable sources and clear limitations.

## 8. Institutional conversion

Make the proposal journey qualify fit rather than collect an unowned generic lead. Capture only information with a declared purpose, consent basis, owner, retention rule, and response process.

Define:

- organization and site context;
- estimated population and service pattern;
- location and timing;
- decision role;
- current challenge;
- appropriate contact route;
- consent and privacy notice;
- lead owner and response expectation.

Do not publish a phone, email, WhatsApp link, response time, capacity, price, savings figure, or service region until it is staffed and verified.

## 9. Commerce readiness

Use WooCommerce instead of a custom cart. Treat installation as architecture, not launch.

Keep commerce disabled or noindex until accountable owners and evidence exist for:

- merchant identity and contact details;
- products, variations, identifiers, images, descriptions, prices, inventory, and VAT treatment;
- live payment-provider verification;
- shipping or service regions, rates, packaging, delivery windows, and failure handling;
- returns, cancellations, privacy, terms, accessibility, and customer support;
- test orders, refunds, taxes, transactional emails, stock movement, analytics, and security;
- visible Product and Offer facts that exactly match structured data.

Do not show fake inventory, crossed-out prices, scarcity, ratings, reviews, shipping promises, or checkout controls.

## 10. Private operating platform

Keep these modules authenticated and role-scoped:

- opening and closing routines;
- branch handovers and exceptions;
- recipes, production specifications, yields, substitutions, and versions;
- supplier, item, purchase-order, receiving, and discrepancy workflows;
- food-safety controls and incidents;
- staff, training, competence, and acknowledgements;
- costing, budgets, VAT, invoices, and branch performance;
- CRM stages, tenders, proposals, tasks, and service ownership;
- campaign briefs, approval, schedules, spend, UTMs, and results;
- cameras, devices, telemetry, commands, access, and audit logs;
- AI actions, sources, approvals, evaluations, and immutable logs.

Never render decorative private metrics as real operating data. Label prototypes internally and keep them out of public claims.

## 11. Branches and integrations

Model branches with stable IDs, approved name/address/phone, hours, service facts, images, local menus, local overrides, owner, timezone, review status, and history.

Publish a branch only after NAP, hours, service facts, rights, and ownership are verified. Avoid doorway pages and city pages without real presence.

Require the following before an integration is operational:

- approved counterparty or provider;
- real account and authorization;
- least-privilege credentials outside Git;
- API, webhook, or file contract;
- validation and reconciliation rules;
- retries, idempotency, failure ownership, and alerting;
- audit log and provider receipt;
- privacy, retention, and security review;
- safe disconnect and recovery path.

Cameras and devices additionally require inventory, consent/legal review, encryption, authenticated control, human emergency controls, and tested failure behavior. Never expose feeds or commands publicly.

Tablets and future robots consume role-appropriate private APIs. Simulate and evaluate physical actions before device access.

## 12. Campaigns, social, and analytics

Keep the public site, campaign system, and social channels connected through governed data rather than fabricated UI.

For each campaign define:

- objective and audience;
- approved claim and asset;
- landing-page intent owner;
- channel and real account;
- budget and approver;
- UTM convention;
- schedule and timezone;
- consent and audience rules;
- success metric and source;
- moderation and escalation owner;
- provider receipt and final state.

Do not claim “connected,” “scheduled,” “sent,” “published,” or a performance result without provider evidence.

Use consent-aware analytics. Define events before instrumenting them. Separate observed data from targets, estimates, and decorative examples.

## 13. AI and automation governance

Give agents:

- narrow role-specific tools;
- source-bounded retrieval;
- least privilege;
- explicit approval thresholds;
- input and output validation;
- evaluations and regression tests;
- immutable action logs;
- safe retry and rollback behavior.

Never allow an AI agent to self-approve public claims, regulated instructions, food-safety decisions, purchases, payments, device actions, customer commitments, or social publishing.

Make a human accountable for every automated business outcome.

## 14. Visual and experience benchmark

Combine useful patterns rather than copying a competitor:

- Ottolenghi: recipes, ingredient encyclopedia, and content-commerce graph.
- Eataly: editorial category landings.
- Sweetgreen: ingredient, nutrition, menu, and location transparency.
- Fooditude: institutional-buyer clarity.
- Farmer J: recognizable illustration language.

Outperform common defects:

- no first-load lead, discount, report, or cookie wall over the hero;
- no carousel dependence or autoplay;
- no generic SaaS gradient standing in for food and people;
- no tiny controls or missing keyboard focus;
- no invented customer logos, proof counters, social accounts, or certification marks.

Use a distinctive food-rich identity, strong typographic hierarchy, useful editorial imagery, consistent icon family, and clear institutional credibility. Pair critical icons with labels.

Minimum acceptance:

- no horizontal overflow at 390 CSS pixels;
- primary controls at least 44 by 44 CSS pixels;
- body copy at least 16 CSS pixels;
- high-contrast focus indicator at least two CSS pixels thick;
- reduced-motion support;
- responsive AVIF or WebP imagery with explicit dimensions;
- one optimized eager LCP image;
- below-fold lazy loading;
- zero first-party console errors.

## 15. Stakeholder council

Use this table to expand requirements without inventing public claims.

| Role | Public requirement | Private or near-term requirement | Hold until proven |
| --- | --- | --- | --- |
| Owner/CEO | Commercial promise and audience hierarchy | Portfolio and branch growth model | Scale, leadership, revenue, customer claims |
| COO | Honest capability tour | Opening, handovers, exceptions, audit trail | Decorative dashboards called live |
| Chef | Dish hub and editorial method | Recipe versions, yield, kitchen test, sign-off | Untested recipes or origins |
| Food safety | Responsibility and allergen boundaries | Controls and incident workflow | Suitability, kosher, compliance |
| Procurement | Sourcing and receiving method | Suppliers, substitutions, POs, discrepancies | Supplier names, prices, endorsements |
| Finance | Business model and proposal path | Costing, budgets, VAT, invoices, branch P&L | Savings or pricing |
| Institutional sales | Qualification journey | CRM, owner, SLA, tender workspace | Availability, capacity, customers |
| Marketing | Consistent brand and owned assets | Briefs, approvals, UTMs, calendar | Unsupported outcomes |
| Social | Honest channel strategy | OAuth, scheduling, moderation, receipts | Connected or published state |
| SEO | Hubs, hierarchy, links, ownership registry | Search Console, schema, orphan monitoring | Thin or overlapping scale |
| Commerce | Store architecture and readiness state | WooCommerce, fulfillment, orders | Checkout before readiness |
| Editorial | Sources, authorship, review, translation | Calendar and citations | Padded or fabricated prose |
| Design | Complete visual identity | Photography and icon system | Unlicensed assets |
| UX/accessibility | Menu, search, footer, forms, keyboard, mobile | Filters and branch-aware views | Unsafe device controls |
| Branch manager | Truthful location model | Menus, hours, overrides, owners | Unverified locations |
| HR/training | Responsibilities | Training and acknowledgements | Public staff records |
| Customer service | Consent-aware contact | Staffed inbox, status, retention | Unstaffed channels or hours |
| Data/analytics | Event and KPI definitions | Consent, funnels, attribution, CWV | Decorative numbers as data |
| Security/privacy | Legal, privacy, accessibility foundation | Vendors, access, incidents | Cameras, secrets, people data |
| Automation/AI | Human accountability and honest scope | Tools, approvals, logs, evaluations | Self-approved consequential actions |

## 16. Verification and release evidence

For user-facing changes, capture real-Chrome desktop and 390-pixel mobile evidence. Check:

- Hebrew root and English `/en/`;
- exact `קומפלט 99` and `Complete99` identity;
- server-rendered header and mega-menu links;
- keyboard opening, traversal, Escape, focus return, and visible focus;
- footer clusters and deep breadcrumbs;
- canonical, reciprocal hreflang, schema, sitemap, and index policy;
- overflow, clipping, touch targets, text size, images, reduced motion, and console;
- forms, consent, state changes, and honest confirmation;
- no private language or unverified claims.

For WordPress releases, use the `wordpress-agent-deploy` skill and retain evidence for:

- reviewed Git commit and exact deterministic artifact hash;
- required CI, lint, tests, package inspection, and secret scan;
- temporary administrator-gated deployment bridge;
- overwrite installation, activation, and cache purge;
- public health endpoint and rendered-body positive and negative checks;
- canonical routes and structured data;
- permanent deletion of the temporary snippet and route-404 proof;
- desktop and mobile Chrome screenshots;
- recoverable rollback and periodically exercised rollback.

Do not declare completion from an API response alone. Verify the independent public result.

## 17. Authoritative external references

- [Google ecommerce site structure](https://developers.google.com/search/docs/specialty/ecommerce/help-google-understand-your-ecommerce-site-structure)
- [Google spam policies and scaled content](https://developers.google.com/search/docs/essentials/spam-policies)
- [Google recipe structured data](https://developers.google.com/search/docs/appearance/structured-data/recipe)
- [Google merchant listing structured data](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing)
- [WordPress sitemap provider hook](https://developer.wordpress.org/reference/hooks/wp_sitemaps_add_provider/)
- [WooCommerce setup and launch checklist](https://woocommerce.com/document/woocommerce-setup-wizard/)
- [WCAG 2.2 target size](https://www.w3.org/WAI/WCAG22/Understanding/target-size-enhanced)
- [WCAG 2.2 focus appearance](https://www.w3.org/WAI/WCAG22/Understanding/focus-appearance.html)
- [Core Web Vitals](https://web.dev/articles/vitals)
