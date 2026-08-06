# Complete99 commercial entity architecture

Last updated: 2026-08-06
Status: private operator architecture, not public copy
Scope: global culinary-science, commerce, content, channel and operations data

This document turns the Complete99 commercial vision into a modular data and
governance contract. It is an internal operating document. It must not be
rendered as consumer copy, indexed, or exposed through a public API.

## 1. Operating doctrine

Complete99 is one connected culinary business with one authoritative data
foundation:

1. WordPress owns culinary entities, editorial content, evidence, media,
   translations, taxonomy, SEO ownership and public publication state.
2. WooCommerce owns sellable products, variations, SKUs, approved prices,
   inventory, carts, orders and refunds.
3. Private WordPress modules own commercial planning, suppliers, costs, margins,
   operational records, campaigns and connector state.
4. The public website, application, POS and touch kiosk consume bounded
   projections from this foundation. They do not maintain a competing catalog,
   price list or inventory master.

The architecture supports a very large graph of cuisines, dishes, recipes,
ingredients, molecules, techniques, equipment, chefs, institutions, markets,
suppliers and products. Scale must come from reusable entity types, typed
relationships and market adapters, not from copied country-specific databases.

The public audience remains culinary consumers. Internal operations,
institutional proposals, supplier terms, costs, margins, staff data and system
controls stay private. They may support the public experience without becoming
public language or navigation.

## 2. Source-of-truth matrix

| Domain | Authoritative system | Public projection | Must never become a second authority |
| --- | --- | --- | --- |
| Culinary entity identity and relationships | WordPress entity registry | Approved entity fields and links | Search index, app cache or POS catalog |
| Scientific and cultural claims | WordPress evidence and claim records | Reviewed claims with citations | Generated copy or connector payload |
| Bilingual content and SEO | WordPress editorial records | Published Hebrew or English page bundle | Client-side page configuration |
| Media and rights | WordPress media and rights registry | Approved renditions, alt text and captions | Device-local image folder |
| Product, variation and SKU | WooCommerce | Channel-safe catalog projection | Kiosk, POS or app product table |
| Approved sell price | WooCommerce, after commercial approval | Exact current channel price when offer-ready | Planning workbook or source observation |
| Inventory and stock movement | WooCommerce, with audited operations events | Availability state allowed for the channel | POS or kiosk local count |
| Orders and refunds | WooCommerce | Customer or operator view appropriate to access | Connector queue or payment-provider view |
| Source-market observations | Private WordPress commerce evidence | Optional dated market context with no offer semantics | WooCommerce price field |
| Supplier, landed cost and margin | Private WordPress commercial module | None | Public REST response, markup or schema |
| Staff, production, campaigns and devices | Private authenticated WordPress modules | None | Public site or public application |

When an external system disagrees with WordPress or WooCommerce, the external
record is treated as a reconciliation exception. It does not silently overwrite
the authority.

## 3. Surface separation

Every field and relationship has a surface classification. The default is
private until a specific public allowlist approves it.

### 3.1 Public consumer discovery

May include:

- approved entity identity, culinary explanation and cultural context;
- reviewed scientific facts with claim-level sources;
- approved images, alt text, captions and rights-safe renditions;
- public taxonomy, breadcrumbs, semantic links and language alternates;
- real WooCommerce product, price and availability only after offer readiness;
- dated source-market observations when explicitly labeled as observations and
  not presented as Complete99 prices.

Must exclude:

- suppliers, costs, margins, planned prices and procurement assumptions;
- editorial workflow, deployment, connector and approval language;
- staff, customers, campaigns, production controls, devices and credentials;
- any claim that a proposed integration or product is active.

### 3.2 Private editorial and research

Contains source snapshots, claim locators, confidence, contradictory evidence,
translations, drafts, molecular notes, image prompts, test results and review
history. It can create a public projection only after the relevant evidence,
language, visual and SEO gates pass.

### 3.3 Private commercial

Contains supplier candidates, quotes, currency references, landed-cost models,
price scenarios, margins, bundles, assortment decisions, commercial approvals
and channel readiness. No private commercial field is serialized into a public
entity response.

### 3.4 Private operations

Contains inventory movements, purchase orders, receiving, spoilage, production,
workers, tasks, campaigns, devices, POS reconciliation and incidents. Access is
authenticated, scoped and audited.

### 3.5 Future architecture

Contains schemas and disabled adapters that are not yet connected. A future
module cannot create public wording that implies it is live. In particular, the
MyShop POS and kiosk connector stays `contract_required` until a real vendor
contract, credentials, mapping and reconciliation acceptance exist.

## 4. Universal entity envelope

Every entity uses a stable ID that does not depend on a URL, language, country,
seller or channel. Slugs and translated names may change without changing the
identity. Each entity has the following modules.

### 4.1 Core identity

- `entity_id`, `entity_type` and `schema_version`;
- canonical name, alternate names, transliterations and controlled synonyms;
- locale records and a stable `translation_group_id`;
- parent entity, topic hub and typed relation edges;
- lifecycle state, creation date, revision and superseded record link;
- visibility class and field-level public allowlist;
- applicability flags instead of invented values for fields that do not apply.

### 4.2 Scientific module

- ingredient or material composition;
- relevant molecules, aroma compounds, taste compounds and reaction pathways;
- pH, Brix, water activity, moisture, fat, protein, sugar, salt and other
  measurements only with method, sample, unit, range and conditions;
- transformations such as fermentation, Maillard reaction, caramelization,
  enzymatic action, crystallization and gelation;
- allergens, cross-contact, storage and safety boundaries;
- evidence level, source IDs, claim locator, uncertainty and review date;
- a clear distinction between a literature result, producer specification,
  measured batch value and editorial inference.

No literature range is converted into an exact SKU specification without
batch-specific producer evidence.

### 4.3 Cultural module

- cuisine, geography, period, community and heritage context;
- historical names, language variants and documented preparation traditions;
- distinctions between documented history, oral tradition, regional variation
  and Complete99 interpretation;
- source-backed links to dishes, techniques, festivals, tools and institutions;
- culturally careful terminology and locale-specific editorial review.

### 4.4 Institutional module

- institution type, geography, dates and documented significance;
- chefs, restaurants, schools, markets, producers, specialist retailers and
  equipment districts connected through typed, dated edges;
- evidence for accolades, status and historical claims;
- relationship state such as editorial reference, market observation or
  verified commercial counterparty;
- an explicit rule that being mapped is not an endorsement, partnership or
  supplier relationship.

### 4.5 Economic and commercial module

Every entity receives an economic dossier, but not every entity receives a
price. A cuisine, molecule, chef or institution uses
`pricing_applicability=not_priceable` and records its commercial role, supported
offers, demand intent and revenue paths. A product, service, ingredient pack,
equipment item or bundle may enter the pricing state machine in section 6.

The module includes:

- priceability and offer type;
- source-market observations and comparable grade;
- normalized quantity and unit for like-for-like comparison;
- scarcity, perishability, shelf-life, cold-chain and spoilage factors;
- quality ladder, substitute ladder and value explanation;
- supplier and procurement state;
- landed-cost scenarios and missing inputs;
- planned prices, tax treatment, channel and market scope;
- private unit economics, margin scenarios and approval state;
- related bundles, cross-sells, up-sells and replenishment paths;
- commercial evidence, observation dates and expiry dates.

### 4.6 SEO and semantic module

- one primary intent owner for every indexable page;
- primary topic, supported secondary topics and forbidden competing topics;
- canonical path, locale, reciprocal hreflang and translation group;
- parent hub, spoke type and visible breadcrumb path;
- controlled Hebrew, English, source-language and transliterated synonyms;
- approved internal-link edges with relation type and anchor purpose;
- index policy and reason;
- evidence profile, schema profile, author and review dates;
- hub, category, entity and commerce responsibilities kept distinct.

An entity can exist in the graph without receiving an indexable page. Thin,
overlapping, draft or unverified entities remain private or `noindex` and can
still support taxonomy, relationships and later expansion.

### 4.7 Visual module

- stable `asset_id`, owning `entity_id`, role and locale;
- asset class such as hero, pack shot, ingredient macro, process, diagram, icon,
  map, equipment, comparison or future 3D model;
- original source or generation method, internal prompt and generation date;
- rights holder, license or generation receipt, approval and expiry;
- original file hash and derivative hashes;
- AVIF, WebP and fallback renditions with dimensions and color profile;
- art direction, crop-safe zones, background and channel variants;
- bilingual alt text, caption and optional ingredient annotations;
- visual-claim review when a picture could imply origin, certification, pack
  contents, scale or serving size.

The public projection exposes only approved renditions and approved text. It
does not expose internal prompts, rights receipts or review notes.

### 4.8 Compliance and operating module

- jurisdiction and market applicability;
- labeling, allergen, alcohol, age, import, phytosanitary, cold-chain, equipment
  and safety review states;
- structured `[COMPLIANCE_NOTE: ...]` content for the appropriate private or
  public-safe context;
- required evidence and next review date;
- fulfillment, storage, handling and failure controls;
- never a fabricated license or blanket declaration of legality.

Compliance notes permit research and structured planning to continue. They do
not turn an unapproved offer into a live offer.

## 5. Relationship graph

Relations are first-class records, not text links embedded by chance. Each edge
has an ID, source entity, target entity, relation type, direction, locale scope,
market scope, evidence, editorial reason, commercial state and revision.

Core relation types include:

- `contains`, `ingredient_of`, `equipment_for` and `technique_for`;
- `produces`, `served_by`, `taught_by`, `sold_by` and `observed_at`;
- `originates_in`, `regional_variant_of` and `substitute_for`;
- `supports_topic`, `belongs_to_hub` and `breadcrumb_parent`;
- `cross_sell`, `upsell`, `bundle_component` and `replenishment_of`;
- `product_for_entity`, `sku_variant_of` and `channel_projection_of`.

Public internal links are generated only from approved semantic edges. Private
commercial edges do not become links until both endpoints and the relationship
are appropriate for the public surface.

## 6. Pricing state machine

Pricing is an evidence chain. A later state never rewrites or hides an earlier
one.

| State | Meaning | Required minimum evidence | Public behavior |
| --- | --- | --- | --- |
| `source_observation` | A dated price seen for an exact item in a named source market | URL, seller, exact variant, amount, currency, tax, shipping, availability, retrieval date and claim locator | May appear only as dated market context |
| `fx_reference` | A dated conversion indicator linked to the observation | provider, currency pair or quoted unit, rate date, retrieval date and legal limitation | Never shown as a Complete99 price |
| `landed_cost_estimate` | Private estimate for acquiring and landing one sellable unit | supplier basis, quantity, Incoterm if applicable, freight, insurance, duty, tax treatment, broker, compliance, cold chain, packaging, wastage, allocation method, confidence and missing inputs | Never public |
| `planned_sell_price` | Private scenario for a market and channel | landed-cost scenario, tax basis, target economics, competitor context, rounding, validity period and approval state | Never public and never Product or Offer schema |
| `approved_woo_price` | An authorized price stored on the exact WooCommerce SKU or variation | commercial approval record, tax configuration, stock, product data, policy readiness and effective dates | Eligible for a channel projection, but not sufficient alone |
| `channel_offer_active` | A currently sellable offer derived from WooCommerce | active channel, payment or approved continuation, fulfillment, support, policies, stock reconciliation and rendered/schema parity | Exact visible offer and schema may be published |

### 6.1 Mandatory distinctions

- A source retail price is not a supplier quote.
- A currency conversion is not an executable bank rate.
- A sensitivity band is not a landed cost.
- A landed-cost estimate is not an accounting receipt.
- A planned sell price is not a WooCommerce price.
- An approved WooCommerce price is not a live offer until channel readiness
  passes.
- No price may silently cross markets, currencies, pack sizes, grades or tax
  bases.

### 6.2 Landed-cost components

The private estimate preserves every component separately:

`supplier unit basis + origin handling + international freight + insurance +
customs and duty + non-recoverable tax + broker and inspection + certification
or laboratory work + cold chain + domestic transport + packaging + expected
spoilage or yield loss + allocated receiving cost`

Recoverable VAT is tracked separately and not treated as margin or ordinary
unit cost without accounting review. Customs classification and import
requirements are SKU-specific. They must be checked against the Israeli Customs
Tariff and the actual import route before approval.

### 6.3 Private profitability model

Contribution economics are calculated privately by SKU, market and channel:

`net revenue excluding applicable tax - landed product cost - packaging -
payment and channel fees - pick and fulfillment cost - expected spoilage,
returns and support cost = contribution margin`

Store gross margin, contribution margin, target margin, break-even volume,
inventory turns, days of stock, shrinkage and cash-conversion assumptions as
private scenario fields. A public page may explain quality and value, but it
must never reveal private costs, supplier terms or margin targets.

No permanent organizational roles are imposed by this architecture. Approval
slots, accountable-owner IDs and escalation destinations may remain unassigned
until the business configures them. The state machine and audit requirements
still apply.

## 7. Current Japanese pilot market evidence

The following records are source-market observations captured on 2026-08-06.
They are not supplier quotes, landed costs, Complete99 prices, inventory promises
or active offers.

| Exact observed item | Source observation | Normalized source observation | Tax, shipping and availability at observation | Converted reference in ILS | Private sensitivity band in ILS |
| --- | ---: | ---: | --- | ---: | ---: |
| Natural Rishiri kombu, 100 g | JPY 1,165 | JPY 11,650/kg | Tax included; domestic shipping excluded; quantity selector visible | 22.19 | 30 to 40 |
| Yamaroku Tsuru-bishio, 500 ml | JPY 1,944 | JPY 3,888/l | Tax included; shipping unknown; price listed | 37.03 | 50 to 67 |
| Fresh Japanese wasabi, 250 g | GBP 62.50 | GBP 250/kg | VAT included; shipping excluded; out of stock | 252.81 | 341 to 455 |
| Kito yuzu juice, 100 ml | JPY 734 | JPY 7,340/l | Tax included; shipping unknown; add-to-cart visible | 13.98 | 19 to 25 |
| Kito yuzu juice, 720 ml | JPY 3,780 | JPY 5,250/l | Tax included; shipping unknown; price listed | 72.01 | 97 to 130 |

Conversion references use the Bank of Israel representative rates shown for
2026-08-05 and retrieved on 2026-08-06:

- JPY 100 = ILS 1.9049;
- GBP 1 = ILS 4.0450.

The Bank of Israel states that its representative rate is an indicator and is
not legally binding. The ILS conversion is therefore reference context only.

The sensitivity band is the converted source retail observation multiplied by
1.35 to 1.80 and rounded to whole shekels. This broad factor is deliberately
simple. It explores exposure to import, availability and handling before real
quotes exist. It is not a landed-cost estimate or a proposed retail price. It
inherits the source retailer's own margin while omitting verified supplier
terms, freight, insurance, customs classification, duties, VAT treatment,
brokerage, cold chain, inspection, packaging, spoilage, payment fees and
Complete99 margin. It must never populate a WooCommerce price or public Offer.

### 7.1 Exact market sources

- Rishiri Kombu direct shop, Natural Rishiri kombu 100 g:
  <https://www.rishirikonbu.com/items/4808577>
- Yamaroku Soy Sauce, product list for the 500 ml Tsuru-bishio observation:
  <https://yama-roku.net/product>
- The Wasabi Company, Fresh Japanese wasabi 250 g:
  <https://www.thewasabicompany.co.uk/products/fresh-japanese-wasabi-250g>
- Ogon no Mura, Kito yuzu juice 100 ml:
  <https://shop.ogonnomura.jp/view/item/000000000364>
- Ogon no Mura, Kito yuzu juice 720 ml:
  <https://shop.ogonnomura.jp/view/item/000000000199?category_page_id=ichiban>
- Bank of Israel representative exchange rates:
  <https://www.boi.org.il/en/economic-roles/financial-markets/exchange-rates/>
- Israeli Customs Tariff and import requirements:
  <https://www.gov.il/he/service/customs-tariff>

Every observation needs an expiry or review date. Re-observation creates a new
immutable evidence record rather than changing the historical value.

## 8. Monetization and merchandising

Commercial design follows culinary intent. A recommendation must help the
customer complete a preparation, improve quality, select a practical pack size
or replace an unavailable item. It must not be an arbitrary high-margin link.

### 8.1 Monetization units

- single ingredient or equipment SKU;
- quality-grade or maturation upgrade;
- household, professional and replenishment pack sizes;
- complete technique kit;
- dish-to-pantry bundle;
- equipment plus consumable attachment;
- discovery set or regional tasting set;
- replenishment reminder based on an actual prior order;
- private institutional case pack or recurring supply plan;
- editorial-assisted commerce measured without turning editorial facts into an
  advertisement.

### 8.2 Japanese pilot merchandising paths

These paths remain draft until their component offers are approved:

| Starting intent | Commercial continuation | Type | Value logic |
| --- | --- | --- | --- |
| Learn about kombu and dashi | Rishiri kombu plus appropriate katsuobushi | Bundle and cross-sell | Completes the classical dashi ingredient pair |
| Select kioke shoyu for sushi or sashimi | Fresh wasabi and Kito yuzu | Cross-sell | Connects complementary seasoning roles |
| Prepare fresh wasabi | Fresh rhizome plus an appropriate grater and storage guidance | Technique kit | Connects the perishable ingredient to required preparation |
| Try Kito yuzu juice | Move from 100 ml to 720 ml when usage justifies it | Up-sell | Source observations normalize to JPY 7,340/l and JPY 5,250/l respectively |
| Build a sushi seasoning pantry | Kioke shoyu, hon mirin and yuzu | Bundle | Creates a coherent pantry path without claiming that every component is active |

### 8.3 Commercial controls

- Link every bundle component to the exact SKU and inventory policy.
- Manage bundle stock from components unless a physical pre-pack has its own
  audited SKU.
- Preserve evidence for every comparison and quality upgrade.
- Do not create fake discounts, crossed-out reference prices or scarcity.
- Do not call a higher-priced item better without a defined quality dimension.
- Measure attachment rate, average order value, bundle completion, repeat
  purchase, spoilage, contribution and returns privately by market and channel.
- Deactivate a relation when an ingredient is unavailable, unsafe for the
  context, incompatible, or no longer supported by evidence.

## 9. Country, market, locale and channel adapters

Global growth is implemented through composable adapters around one entity and
commerce graph.

### 9.1 Country adapter

Defines country code, regulatory references, tax framework, import controls,
restricted classes, labeling languages, units, consumer-policy requirements,
timezone rules and data-retention constraints. It does not copy the catalog.

### 9.2 Market adapter

Defines market ID, country, currency, seller of record, assortment eligibility,
commercial price book, tax zone, fulfillment regions, return policy and launch
state. A country can contain multiple markets without changing core entities.

### 9.3 Locale adapter

Defines language, direction, translated fields, terminology, transliteration,
SEO ownership, canonical prefix, hreflang, currency display and measurement
format. Translation is a reviewed entity revision, not runtime machine output.

### 9.4 Channel adapter

Defines web, application, POS, kiosk, B2B or future marketplace projection,
including allowed fields, assortment, price authority, stock authority, image
profile, hierarchy depth, action contract, cache policy and connector state.
Channel differences are configuration, not duplicate products.

### 9.5 Branch adapter

Defines a stable branch ID, market, local availability, preparation capacity,
pickup windows and approved overrides. Overrides are narrow, dated and audited.
The branch never replaces the global entity or SKU identity.

## 10. POS, kiosk and application contract

The application, MyShop POS and touch kiosk are API clients of WordPress and
WooCommerce. Their read model is projection-only.

### 10.1 Read behavior

- request a channel-scoped catalog projection;
- receive stable entity, product, variation and SKU IDs;
- receive only the approved language, images, price and availability fields;
- use revision, ETag or cursor values for incremental synchronization;
- cache for resilience but label the cache revision and reconcile it;
- reject data outside the adapter's market, branch and channel scope.

### 10.2 Write behavior

Clients submit narrow commands such as cart, order, refund request or stock
movement through authenticated contracts. They do not write directly to the
WordPress database and do not decide the authoritative final state.

Every command carries:

- idempotency key;
- correlation ID;
- client, channel, market and branch IDs;
- expected source revision;
- timestamp and authenticated origin;
- line-level SKU and quantity;
- validation result, authority receipt and reconciliation state.

The authority validates the command, commits the result once, records an audit
event and returns the authoritative revision. Offline queues are private,
encrypted and bounded. A replay cannot create a duplicate order or stock move.

### 10.3 Failure behavior

- fail closed on unknown SKU, currency, tax, price or inventory revision;
- do not invent an offline price;
- quarantine mapping conflicts for reconciliation;
- retain last successful projection only as a visibly dated operational cache;
- alert privately without publishing system-status language to consumers;
- support a tested disconnect and recovery path.

No staff member or customer needs a ChatGPT account. AI services operate behind
the WordPress integration boundary through narrowly authorized APIs, evidence
rules, approval thresholds and immutable logs.

## 11. SEO architecture from the entity graph

The content model follows Hubs and Spokes and topic clusters:

`culinary museum -> cuisine hub -> category hub -> dish, ingredient, technique,
equipment or institution entity -> relevant product`

For each public candidate, the system must resolve:

1. primary search intent and one owning URL;
2. parent hub and breadcrumb path;
3. locale and reciprocal alternate;
4. source-backed topic coverage and approved synonyms;
5. typed internal links to parent, siblings, ingredients, techniques and
   appropriate products;
6. schema that matches the visible approved projection;
7. index state based on evidence and usefulness, not entity count;
8. sitemap inclusion only after the page is canonical, useful and indexable.

Informational, commercial and transactional intent remain distinct. An
ingredient encyclopedia page can support a product but does not pretend to be a
live product offer. A product page owns the exact SKU or variation family only
when commerce readiness passes. Filter and search views remain controlled
utilities unless they own a distinct useful intent.

## 12. Versioning, audit and recovery

The system records changes rather than erasing history.

### 12.1 Independent versions

- entity schema version;
- entity content revision;
- scientific claim and evidence revision;
- relationship graph revision;
- locale and translation revision;
- visual asset and rights revision;
- price evidence revision;
- WooCommerce offer revision;
- inventory event sequence;
- channel projection revision;
- connector contract and mapping revision.

### 12.2 Immutable audit event

Every material event records event ID, entity or SKU, prior revision, new
revision, changed fields, reason, source, timestamp, actor or service identity,
approval state, correlation ID and resulting receipt. Source snapshots and media
use hashes so later evidence can be compared exactly.

No hard delete is the normal operating path. A record is superseded, withdrawn
or tombstoned with a reason and recovery reference. Public removal does not
erase the private evidence or audit chain.

### 12.3 Safe expansion

- additive schema changes precede breaking changes;
- adapters declare compatible schema ranges;
- migration rehearsals produce counts and reconciliation reports;
- connector cursors and failed records are recoverable;
- rollbacks restore a coherent entity, offer and projection revision;
- every market launch starts with a small complete vertical slice before broad
  catalog expansion.

## 13. Commercial readiness gates

A priceable entity may become a live offer only when all applicable gates pass:

1. Exact product, variation, SKU, pack size and identifier are defined.
2. Supplier and item evidence are current.
3. Landed cost, tax, customs and compliance treatment are reviewed.
4. Planned economics pass the private approval threshold.
5. WooCommerce contains the approved price, inventory and tax state.
6. Images, descriptions, allergens, origin and labels match the exact item.
7. Shipping or pickup, returns, support and policies are operational.
8. A test order, refund, stock movement and transactional message are verified.
9. Visible price, availability and Product or Offer schema match.
10. Each enabled channel reconciles to the same WooCommerce authority.

Until then, the system may show an approved educational entity or a clearly
dated market observation. It may not show the planning value as if Complete99
were selling at that price.

## 14. Expansion sequence

1. Stabilize the universal entity envelope and typed relation registry.
2. Complete the Japanese public pilot as a bilingual, sourced, `noindex`
   vertical slice while long-form review is pending.
3. Add the private pricing evidence chain and scenario calculator without
   activating offers.
4. Convert a selected SKU only after supplier, landed cost, compliance,
   fulfillment and policy evidence pass.
5. Project that approved WooCommerce offer to the web application.
6. Bind MyShop only after its real API contract, identity, mapping,
   idempotency, reconciliation and recovery tests pass.
7. Repeat the same entity, market and adapter pattern for additional cuisines,
   countries, locales, branches and channels.

This sequence produces visible progress while preserving one source of truth,
commercial honesty and the ability to scale without rebuilding the system for
every market.
