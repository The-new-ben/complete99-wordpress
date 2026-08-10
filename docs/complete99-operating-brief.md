# Complete99 operating brief

Last updated: 2026-08-11
Status: source release target 1.21.0, with a read-only WordPress-native Complete99 OS P1 foundation, an exact seven-table private operations schema, table-aware rollback, digest-pinned search activation for 18 reviewed Science/Museum owners across 36 bilingual canonical routes, truthful Product/Offer gating, 36 unchanged public products and disabled payments; all held, private, section-only, query-state and untested-preparation boundaries remain fail closed

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
- All 82 Lebanese research identities, including their sources, institutional
  and market benchmarks, retail observations and trade-compliance record. They
  remain private, `noindex` and reference-only.
- All 96 Iraqi research identities, including regional, community, scientific,
  institutional, safety and trade-compliance records. They remain private,
  `noindex` and reference-only.

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

Release 1.12.0 creates contracts and checks, not worker assignments.

- The private Entity Studio is an administrator-only WordPress tool backed by
  the existing culinary-science, authorized catalog and commerce registries.
- It creates no new role and does not assign workers, reviewers or operators.
- Its dossiers remain private and cannot become public pages or active offers
  through the Studio.

Release 1.17.0 expands Entity Studio to 728 subjects: 672 science identities
plus the unchanged 56 product identities. Culinary science is registry version
17 and culinary commerce is registry version 11. The 121 new Lebanese
identities do not create a product, price observation, offer, supplier, stock
record or purchasing path. One reviewed Lebanese cuisine root is a public
noindex discovery gateway; all 121 new identities remain private. The public
graph contains 24 science entities across 19 canonical page owners per
language.

Release 1.18.0 keeps Entity Studio at 728 subjects and grows the reviewed public
science graph to 27 entities across the same 19 canonical page owners per
language and 38 bilingual routes. The 84-identity Japanese cluster contains 24
public and 60 private records. Controlled dashi extraction appears inside the ichiban-dashi owner,
while L-glutamate and inosine monophosphate appear inside the umami owner. The
culinary-commerce registry is version 12 and binds culinary-science version 18.
The catalog, prices, stock authority, disabled payments and private operating
boundary remain unchanged.

Release 1.19.0 keeps Entity Studio at 728 subjects and the science registry at
672 identities while increasing the source register to 374. Its frozen artifact
encoded seven Syrian records and seven science image sets as public/noindex,
producing 34 entities, 26 standalone owners per language, 52 bilingual routes,
and a Syrian split of 8 public/noindex to 274 private records. No owner receipt
was recorded and the artifact was never deployed, so the live graph stayed at
27 entities, 19 standalone owners and 38 routes. The science-asset collection
remained separate from the 60 catalog assets. Commerce v13 encoded the Syrian
bulgur relation without creating a product, price, supplier, stock record or
payment path; current v20 holds that relation from public projection.

Release 1.20.0 keeps Entity Studio at 728 subjects and the science registry at
672 identities while increasing the source register to 375. Culinary science
uses schema `complete99-culinary-science-registry/v6` and version
`culinary-science-2026.08.08.v20`; culinary commerce is version 14 and binds
science v20. Exactly five Japanese koji and shoyu identities become reviewed
private editorial candidates. Shoyu koji, kioke, the koji-hydrolysis guide and
JAS 1703 standard context are proposed standalone owners, while the
enzymatic-hydrolysis reaction is a proposed section of the guide. That release's
public graph remained exactly 27 entities, 19 standalone page owners per
language and 38 bilingual routes, while zero science records were indexable. The
Japanese cluster remains split into 24 public and 60 private records.

Release 1.21.0 keeps that v20 registry and its raw publication flags unchanged,
but binds a separate fail-closed activation to its exact canonical digest.
Exactly 18 reviewed standalone owners and 36 Hebrew/English canonical routes
become effectively indexable and enter the WordPress sitemap. The untested
ichiban-dashi owner, eight section-only entities, query states and every held,
private or draft record remain excluded or noindex. The release also begins the
private application migration with a WordPress-authenticated, read-only Today
shell and seven durable operations tables; it imports no legacy rows and enables
no write command in P1.

The release verifies exactly three private source-scoped `literature_context` ranges
from one 46-hour study of three *Aspergillus oryzae* strains: neutral protease
500-700 U/g, acidic protease 50-150 U/g and leucine aminopeptidase 50-250 U/g.
They are study measurements, not recipe, production or safety targets, and are
withheld from public projection with their parent candidate.
Cross-domain binding registry v3 records exactly 95 unresolved census records
and 11 private reciprocal Woo candidates. Its valid private decision overlay
contains zero decisions and zero recognized reviewer authorities. Its five
verified or public indexes remain literally empty; it publishes no relation and
mutates no science, commerce or WooCommerce record. The 36 products, prices,
stock authority, cart and disabled payment state remain unchanged.

The generated-asset manifest is an editorial evidence registry, not an
installed-file inventory. Local packaging does not authorize live publication.
All twelve science editorial candidates, seven Syrian and five Japanese, remain
held. Approval v2 requires a trusted owner key and signed receipt binding the
exact PNG source evidence separately from the four deployable WebP/AVIF variants
and complete bilingual content; neither a trusted key nor a receipt exists.

The default-deny Science media policy classifies exactly 47 stems and 175 files:
28 public-delivery stems, 18 held repository-only stems and one approved
superseded archive stem. A rebuilt package ships exactly 70 delivery files and
keeps exactly 105 files out of the ZIP: 78 held files, 24 active public PNG
source-evidence files and three archive files. All 60 held derivatives formerly
present in the 1.20 candidate package are excluded, while all 78 held files
remain retained as repository evidence.

The infrastructure candidate may proceed only through protected `main`, green
CI and the controlled WordPress workflow, without publishing held content. Any
later approved candidate needs a newly reviewed and rebuilt artifact containing
the four exact delivery variants and fresh acceptance. This brief makes no
deployment or live-publication claim.

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

Release 1.12.0 does not change that public catalog, cart or route surface. It
keeps all 17 earlier private draft offers inactive and adds three private Syrian
planning-price observations without creating a WooCommerce product, channel
offer, stock record or supplier claim. The result is 36 live prices and 20
private planning prices across 56 product identities. Payment remains disabled.

Release 1.20.0 likewise preserves the exact 36 WooCommerce products, 56 product
identities and 20 private planning prices. Commerce v14 changes only the
immutable science dependency to v20. The five held Japanese candidates and
11 private cross-domain candidates create no product, price, stock, supplier,
offer, checkout path or payment activation.

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

Release 1.11.0 extends coverage to all 53 product identities. It adds 12 exact
source-market candidates spanning premium nori, seasoned sushi vinegar, tamari,
dried shiitake, dried yuba, kudzu starch, sansho, Tenju matcha, a makisu,
yanagiba, Kamado-san and a maker-attributed chasen. Their source prices, Bank of
Israel conversions and ILS planning prices are documented in
`docs/japanese-premium-market-tranche-2026-08-06.md`. Every new candidate stays
private, held, at planning stock zero and outside WooCommerce until exact
supplier, landed-cost, compliance, media and acceptance gates pass.

Release 1.13.0 preserves coverage across all 56 product identities. Three Syrian
ingredient observations provide private planning prices only. They remain held,
have no WooCommerce product code, channel offer, stock, supplier, landed-cost or
margin claim, and cannot enter the public store or active POS projection. The
cumulative science registry contains 287 entities and Entity Studio resolves
343 subjects: 287 science identities plus 56 product identities. The Syrian
module contains 196 identities, including 56 dishes, 55 ingredients, 21
regional or topic hubs, 17 techniques, 17 traditions and 15 preparations, plus
the three private held market observations. One safe Syrian
consumer gateway is `noindex,follow`, the
other 195 Syrian entities remain private, and the public science graph contains
23 entities across 18 canonical page owners per language.

Release 1.14.0 preserves those Syria facts and expands the cumulative science
registry to 369 entities by adding an 82-entity Lebanese regional foundation.
All 82 Lebanese identities remain private, `noindex` and reference-only. The
foundation covers regional and community foodways, dishes, preparations,
ingredients, techniques, traditions, institutions, markets, restaurants, one
trade-compliance rule and six retail observations. Entity Studio now resolves
425 subjects: 369 science identities plus the same 56 product identities.
Price-basis coverage remains 36 public WooCommerce prices plus 20 private
planning prices. Release 1.14.0 creates zero new active or draft offers and
does not change the 36-product public catalog, payment-disabled state, stock or
public routes.

Every Lebanese entity is governed by the current private Israel-Lebanon trade
boundary. Israel Ministry of Economy and Industry Director-General Instruction
2.4 dated 8 March 2026 is recorded as broadly prohibiting direct or indirect
trade with enemy states and listing Lebanon. Complete99 must not contact a
Lebanese supplier, order a sample, make payment, route a purchase through a
third party or represent delivery from Lebanon without written legal and
official authorization. The six retail observations are research benchmarks,
not lawful supply routes, offers, landed-cost inputs or availability in Israel.
This is a fail-closed operating rule, not legal advice or an approved
exception.

Release 1.15.0 preserves the Syrian and Lebanese facts and expands the
cumulative science registry to 465 entities by adding a 96-identity Iraqi
regional and community foundation. Entity Studio resolves 521 subjects: 465
science identities plus the unchanged 56 product identities. All Iraqi
identities remain private, `noindex` and reference-only, and create no price
observation, product, offer, supplier, stock record, POS row or public route.

The Iraqi graph distinguishes Baghdad, Mosul and Ninewa, Basra and the Shatt
al-Arab, the Middle Euphrates, the southern marshes, Iraqi Kurdistan, Kirkuk and
Diyala. Jewish, Kurdish, Marsh Arab and family records remain within their
documented scope. Shared regional dishes are connected for comparison without
exclusive-origin claims. A central private trade rule and separate food-safety
gates fail closed before any future commercial or operational use.

Release 1.16.0 expands the Syrian graph from 196 to 282 identities and the
cumulative science registry from 465 to 551. Entity Studio resolves 607
subjects: 551 science identities plus the unchanged 56 product identities. The
86 new Syrian identities are divided into 30 west and central records, 31 east
and south records, and 25 community and institutional records. All remain
private, `noindex` and reference-only, and create no public page, price,
product, supplier, stock, POS row or order path.

The Syrian graph distinguishes Aleppo, Damascus, Homs, Hama, Idlib, Qadmus,
Kassab, Baniyas, Jableh, Qamishli, Deir ez-Zor, Al-Bukamal, Palmyra, Suwayda
and Hauran. Jewish family records from Aleppo, Damascus and diaspora archives
sit alongside Syrian-Armenian, Assyrian, Kurdish, Druze and other regional
records. No community record replaces the wider cuisine. Four unresolved
records remain held for botanical, preservation or exact identity review.

Release 1.17.0 expands the Lebanese graph from 82 to 203 identities and the
cumulative science registry from 551 to 672. Entity Studio resolves 728
subjects: 672 science identities plus the unchanged 56 product identities. The
121 new Lebanese identities are divided into 61 coastal and northern records
and 60 Bekaa, southern and community records. All remain private, `noindex` and
reference-only, and create no public page, price, product, supplier, stock, POS
row or order path.

The existing Lebanese cuisine root is separately reviewed and promoted as one
public noindex discovery gateway. Its purpose is consumer navigation between
the museum, Syria and Lebanon. It exposes no private child record, price,
supplier, offer, stock or commerce schema.

The Lebanese graph distinguishes Beirut, Mount Lebanon, Chouf, Aley, Tripoli,
Akkar, the northern coast, Bekaa, Zahle, Baalbek, Hermel, South Lebanon and
Jabal Amel. Jewish foodways sit alongside Druze, Christian, Muslim, Armenian
and Palestinian records. The expansion adds explicit molecular, reaction,
technique, equipment and safety layers. No community record replaces the wider
cuisine. Twelve unresolved evidence or handling records remain held.

The frozen 1.19.0 artifact encoded seven reviewed Syrian records around Aleppo
and the Aleppine kibbeh family as public/noindex candidates. They include Syrian
bulgur, cooked lamb and beef, hydration principles, fully cooked kibbeh methods
and source-scoped Aleppan Jewish foodways. No owner receipt existed and the
artifact was never deployed, so none enlarged the live one-record Syrian public
projection. Current v20 holds all seven as private editorial candidates. The
pending `dish-kibbeh-meshwiyyeh` record also stays private, with no raw-kibbeh
method or Recipe schema. Family testimony remains bounded to the named family
or community and never becomes a universal claim about Aleppo or Syria.

Release 1.20.0 prepared five reviewed Japanese koji and shoyu records as held
editorial candidates. Four are proposed standalone bilingual pages and the
reaction is a proposed section inside the koji-hydrolysis guide. Producer and
retail-listing identities remain private. The three verified literature
measurements, unverified reaction-to-product and shoyu-to-dish hypotheses, and
all cross-domain v3 unresolved or candidate records remain private. Before the
separate 1.21 activation, its public Science surface stayed at 27 entities, 19
standalone owners per language and 38 bilingual routes, with every route
`noindex,follow` and outside the sitemap.

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

Canonical production domain: `https://complete99.co.il/`
The former UPress temporary alias is historical deployment evidence only and
must not be presented as the current public address.

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
