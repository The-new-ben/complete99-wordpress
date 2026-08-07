# Lebanese regional expansion 1.17.0

Date: 2026-08-07

Status: private source release, not a public launch

Plugin target: `1.17.0`

Deployment identity: `c99-wp-1.17.0`

## Release decision

Release 1.17.0 adds 121 private Lebanese culinary-science identities to the
existing 82-identity Lebanese foundation. The result is a 203-identity Lebanon
cluster inside the 672-identity science registry. The expansion is divided into
two independently counted modules:

- Coast and north: 61 identities backed by 31 new sources.
- Bekaa, south and community: 60 identities backed by 46 new sources.

The 77 source IDs and 77 source URLs are unique, mutually disjoint between the
two modules, disjoint from the foundation source ledger, and used by at least
one fact, relation or safety note. Source presence supports bounded research
claims only. It does not establish exclusive origin, present-day institutional
status, a tested formula, a lawful purchasing path or permission to contact a
person or organization.

Every new identity is an administrator-side research subject. Release 1.17.0
creates no public page, public API record, search result, sitemap row, menu item,
WooCommerce product, stock record, POS row, checkout path or outreach task.

## Exact release inventory

| Contract | Release 1.17.0 state |
| --- | ---: |
| New Lebanon identities | 121 |
| Coast and north module | 61 |
| Bekaa, south and community module | 60 |
| New sources | 77 |
| Coast and north sources | 31 |
| Bekaa, south and community sources | 46 |
| Complete Lebanon cluster | 203 |
| Complete science registry | 672 identities and 369 sources |
| Entity Studio | 728 subjects: 672 science identities plus the unchanged 56 product identities |
| Public science graph | 24 entities across 19 page owners per language, with one reviewed Lebanese cuisine root |
| Public WooCommerce catalog | Unchanged at exactly 36 products |
| Existing private planning records | Unchanged at 20 |
| New commercial records from this tranche | 0 |

Registry binding is exact: culinary science is
`culinary-science-2026.08.07.v17`; culinary commerce is
`culinary-commerce-2026.08.07.v11` and names science v17 as its knowledge
registry. The release changes research coverage and the public museum gateway,
not the WooCommerce catalog or payment boundary.

## New identity type counts

| Type | Coast and north | Bekaa, south and community | New total |
| --- | ---: | ---: | ---: |
| `topic_hub` | 5 | 7 | 12 |
| `dish` | 12 | 19 | 31 |
| `ingredient` | 8 | 7 | 15 |
| `molecule` | 3 | 0 | 3 |
| `reaction` | 4 | 0 | 4 |
| `technique` | 8 | 6 | 14 |
| `equipment` | 6 | 0 | 6 |
| `tradition` | 7 | 11 | 18 |
| `market` | 3 | 1 | 4 |
| `culinary_institution` | 3 | 7 | 10 |
| `restaurant` | 2 | 1 | 3 |
| `guide` | 0 | 1 | 1 |
| **Total** | **61** | **60** | **121** |

## Complete Lebanon cluster after 1.17.0

| Type | Count |
| --- | ---: |
| `cuisine` | 1 |
| `topic_hub` | 25 |
| `dish` | 58 |
| `preparation` | 2 |
| `ingredient` | 23 |
| `molecule` | 3 |
| `reaction` | 4 |
| `technique` | 19 |
| `equipment` | 6 |
| `tradition` | 27 |
| `culinary_institution` | 15 |
| `market` | 6 |
| `restaurant` | 6 |
| `compliance_rule` | 1 |
| `retail_listing` | 6 |
| `guide` | 1 |
| **Total** | **203** |

## Regional and community coverage

The graph is designed around questions a future reader might ask, such as
"What is this dish?", "How is this regional record related to another one?",
and "Which parts of the account are documented and which still need review?"
Those intents are recorded now so later editorial work has clear ownership.
They are not live search or ordering experiences in this release.

| Research frame | Bilingual geographic labels where useful | Consumer-intent boundary |
| --- | --- | --- |
| Beirut and the coast | Beirut / ביירות; Ras Beirut / ראס ביירות | Understand named urban, bakery, family and table contexts without treating one Beirut record as all Lebanese food. |
| Tripoli and the north | Tripoli / טריפולי; Old Souks / השווקים העתיקים; Akkar / עכאר; Minyara / מיניארה; Halba / חלבה | Compare northern dishes, sweets, agrarian records and institutional contexts while keeping unresolved identities held. |
| Mount Lebanon and the north coast | Chouf / שוף; Dahr el-Baydar / דהר אל-בידר; Beit Chabeb / בית שבאב; Batroun / בתרון; Jbeil-Byblos / ג'בייל-ביבלוס | Explain source-bounded family, bakery, equipment and coastal food contexts without an exclusive-origin verdict. |
| Central and northern Bekaa | Bekaa / בקעת הלבנון; Zahle / זחלה; Baalbek / בעלבכ; Hermel / הרמל; Aarsal / ערסאל | Distinguish ingredients, dishes, methods and occasion records, with alcohol, raw-meat and dairy gates kept explicit. |
| West Bekaa and Rashaya | West Bekaa / מערב הבקאע; Rashaya / רשאיא | Explain documented preserve, grain, village and wild-plant contexts without turning an archival record into a production instruction. |
| South Lebanon | Nabatieh / נבטיה; Jabal Amel / ג'בל עאמל; Jezzine / ג'זין; Sidon / צידון; Tyre / צור | Connect southern dish, fish, grain and ritual records for comparison while retaining separate identities and source limits. |

Community coverage is plural by design:

- Lebanese Jewish records remain named family and wedding-source records in the
  foundation and are linked into the wider regional graph. They are included,
  but are neither the sole account of Lebanese food nor evidence that a shared
  dish has one exclusive community origin.
- Armenian-Lebanese records, including Bourj Hammoud / בורג' חמוד context, stay
  bounded to their cited community and institutional evidence.
- Palestinian foodways in Lebanon remain archive, contributor and family
  records, including POHA material. They do not imply ownership transfer,
  consent for personal-data publication, institutional partnership or an
  outreach lead.
- Druze records in the foundation, a Melkite village family record, a
  Maronite-Shia Hsoun coexistence record, Christian Easter material, a Chouf
  family Eid source, an Orthodox women's cooperative source and a
  Shia-Christian hrisseh comparison retain their own documented scopes.
- Multi-community and regional records provide the connective layer. A place
  name never acts as a proxy for Sunni, Shia, Druze, Maronite, Melkite,
  Orthodox, Armenian, Palestinian or Jewish identity.

Shared Levantine dishes may be linked for comparison. A comparison edge is not
an origin verdict, a merger of identities or permission to generalize a family
account to a whole community.

Institutions, markets and restaurants are historical, documentary or business
benchmarks only. Their inclusion creates no endorsement, partnership, current
status claim or permission for outreach.

## Science, equipment and safety model

The expansion separates culinary context from scientific and operational
claims:

- Three molecule identities record carvacrol, thymol and p-cymene in
  `Origanum syriacum` context. Values can vary by geography, season, plant
  material and lot, so the records make no health promise and assign no value
  to an untested ingredient.
- Four reaction identities cover tahini hydration and phase change, crust
  browning in manouche, rice-starch gelatinization in Tripoli sweets, and
  lactic fermentation in ambarees or sirdeleh. They explain a mechanism, not a
  validated formula, heat curve, shelf life or production result.
- Six equipment identities distinguish convex saj, flat saj, tabouneh bakery
  oven, stone mortar with wooden pestle, calibrated seafood probe thermometer,
  and a historical dakoujeh clay storage vessel. Identity or historical use is
  not food-contact approval, an engineering specification, an operating
  instruction or commercial safety certification.

Every record remains source-bounded and all facts and relations are
`public_safe=false`. Safety notes are gates, not recipes. The release must retain
at least these exact control codes where applicable:

| Safety code | Required boundary |
| --- | --- |
| `alcohol-age-gate` | Age, service, labeling and legal review before any alcohol-related use. |
| `allergen-gluten` | Grain identity, gluten declaration and cross-contact review. |
| `allergen-nuts` | Nut identity, version-specific use and cross-contact review. |
| `allergen-sesame` | Tahini or sesame identity, declaration and cross-contact review. |
| `cold-chain-control` | Validated cooling, holding, storage and traceability. |
| `distillation-fire-safety` | Legal authority, qualified process design, ventilation, fire control and training. |
| `fish-food-safety` | Species identity, traceability, cold chain, separation, bone control, temperature and sanitation. |
| `food-grade-calcium-oxide` | Food-grade identity, concentration, handling, rinsing and residue validation. |
| `open-fire-safety` | Manufacturer or process specifications, ventilation, burn and fire controls, cleaning and training. |
| `raw-meat-food-safety` | Validated sourcing, hygiene, time-temperature control, separation and service limits. |
| `traditional-dairy-food-safety` | Lawful milk treatment, culture, salt, pH, water activity, microbiology and refrigeration. |
| `wild-plant-identification` | Professional botanical identification, lawful collection, traceability and cleaning; no self-identification instruction. |

Additional source-specific controls may be stricter. They may not weaken or
replace these named gates.

## Exact held identities

The release has exactly 12 entity-level holds. A record is held when its English
summary, commercial-purpose boundary, next-review trigger or safety note uses
the controlled `held` or `fail-closed` language. Safety-gated records outside
this list are not to be mislabeled as held.

| # | Entity ID | Hold trigger |
| ---: | --- | --- |
| 1 | `dish-kaak-orchali-beirut-context` | Dedicated evidence for exact identity and preparation context. |
| 2 | `dish-jazarieh-tripoli-context` | Dedicated evidence for exact identity and preparation context. |
| 3 | `market-halba-produce-system-historical-context` | Current legal identity, address, activity and documentary status. |
| 4 | `restaurant-akra-tripoli-breakfast-benchmark` | Current operation, legal identity, permissions and relationship review. |
| 5 | `restaurant-aal-baher-byblos-seafood-benchmark` | Current operation, legal identity, permissions and relationship review. |
| 6 | `dish-kebbit-el-arous-baalbek-held` | Exact dish evidence plus raw-meat safety review. |
| 7 | `dish-pumpkin-jam-west-bekaa-held` | Exact dish evidence plus firming-agent process review. |
| 8 | `dish-frakeh-south-lebanon-held` | Exact dish evidence plus raw-meat safety review. |
| 9 | `ingredient-zahle-arak-context` | Dedicated ingredient, alcohol and legal-context evidence. |
| 10 | `technique-zahle-arak-distillation-held` | Dedicated process evidence plus legal, fire and distillation review. |
| 11 | `technique-west-bekaa-pumpkin-lime-firming-held` | Food-grade material specification, process and residue validation. |
| 12 | `technique-baalbek-kebbeh-forming-raw-held` | Dedicated technique evidence plus raw-meat process validation. |

None may be reconstructed, operationalized, promoted, or used to imply a
present-day business relationship until its named trigger is independently
closed and the general private-release gates are also passed.

## SEO owner and route boundary

`cuisine-lebanese-regional` is the root of
`cluster-lebanese-regional-cuisine`. Every new parent chain must resolve to that
root without a cycle or missing parent. Within the private registry, each new
identity retains its own intent owner and keyword scope, while
`hub_entity_id=cuisine-lebanese-regional` keeps the cluster relationship clear.
Topic hubs own regional or thematic discovery questions; dish, ingredient,
technique, equipment, tradition, institution, market, restaurant and guide
records own only their narrow informational question.

Candidate Hebrew-root paths under
`/museum/lebanese-culinary-science/{slug}/` and English mirrors under
`/en/museum/lebanese-culinary-science/{slug}/` are internal SEO planning
metadata. They are not registered public routes. Every new identity must keep:

- `surface_class=editorial_draft`
- `index_policy=noindex_private`
- `publication.state=private_preview`
- `publication.public_api=false`
- `publication.public_page=false`
- `publication.search_index=false`
- `publication.approved_at` empty
- `seo.route_mode=private`

No canonical, hreflang, breadcrumb, schema, sitemap or internal-link output may
be emitted for these planned paths. A future publication decision must assign
one verified consumer intent owner per language and pass cultural, evidence,
rights, safety, editorial and technical review. It is not inherited from this
release.

## WordPress, WooCommerce and trade invariants

WordPress remains the future consumer publishing layer and WooCommerce remains
the only product, cart and stock authority. Entity Studio remains an
administrator-only research interface and installs no worker role. For every
new identity, the contract is exact:

- `commerce.state=reference_only`
- `commerce.woo_product_code` is empty
- `commerce.public_offer_allowed=false`
- `commerce.cross_sell_ids=[]`
- `commerce.up_sell_ids=[]`
- `commerce.business_model.pricing_state=research_required`
- `commerce.business_model.observation_entity_ids=[]`

The public catalog remains exactly 36 products, the 56 product-identity set is
unchanged, the existing 20 private planning records are unchanged, and payment
remains disabled. The expansion adds no product, price observation, channel
offer, supplier record, inventory, stock movement, POS projection, order path
or purchasing task.

Every one of the 121 new identities must have exactly one private `references`
relation to `compliance-lebanon-trade-israel-2026`, backed only by
`israel-enemy-states-trade-2026`. The documented Israel-Lebanon trade boundary
remains fail closed. This release does not authorize contact, sampling, payment,
indirect routing or any commercial action.

## Visual asset held state

All 121 new identity prompts are nonempty, private and unique. Their generated
concept state is `rights_review_required` with `rights_state=pending`. Prompts
must avoid copied source imagery, logos, flags, packaging, medical claims,
unsafe service cues and visual stereotypes.

The project-generated `c99-science-lebanese-regional-table-v01` evaluation
asset has PNG, WebP and AVIF integrity receipts plus Hebrew and English alt text
in the visual and rights registers. It remains held for private editorial
review. Its table arrangement is a regional study, not a recipe result, a
product photograph, an origin verdict or a claim that every pictured food
belongs to one locality.

Public use of that asset would require a separately approved page owner,
regional-accuracy review, accessibility review, rights-state promotion,
responsive rendering checks and explicit editorial approval. Clearing the
image alone would not clear any of the 121 identities or create a route.

## Exact release acceptance checks

Release 1.17.0 is acceptable only when every check below passes on the same
commit and packaged artifact:

1. Plugin metadata is exactly `1.17.0` and deployment identity is exactly
   `c99-wp-1.17.0`.
2. Science version is exactly `culinary-science-2026.08.07.v17`; commerce
   version is exactly `culinary-commerce-2026.08.07.v11` and binds to science
   v17.
3. The registry validates at exactly 672 entities and 369 sources. Entity
   Studio resolves exactly 728 subjects: 672 science plus the unchanged 56
   product identities.
4. Module entity counts are exactly `(61, 60)` and module source counts are
   exactly `(31, 46)`. Entity IDs and slugs are disjoint between modules.
5. The 77 new source IDs are disjoint from one another and the Lebanese
   foundation. All 77 source URLs are unique and every new source is cited by a
   fact, relation or compliance note.
6. New type counts match the 121-identity table above exactly. The complete
   cluster has exactly 203 identities and matches the complete type table
   above exactly.
7. Every new identity has the exact private publication and noncommercial
   fields listed in this document. Every fact and relation is
   `public_safe=false`.
8. Every parent exists and every new parent chain reaches
   `cuisine-lebanese-regional` without a cycle.
9. Every new identity has exactly one trade relation to
   `compliance-lebanon-trade-israel-2026`, and that relation uses exactly the
   source list `["israel-enemy-states-trade-2026"]`.
10. The observed held set equals the 12 IDs above, with no extra or missing
    held identity. The 12 named standard safety codes are present across the
    tranche.
11. The public science graph contains exactly 24 entities across 19 SEO page
    owners per language. The reviewed `cuisine-lebanese-regional` root is the
    only public Lebanon identity. The public WooCommerce catalog remains exactly
    36 products and no Lebanon expansion identity enters a public route or
    commercial projection.
12. All 121 visual prompts are nonempty and unique. The two module files and
    this release document contain no U+2014 character. The regional-table asset
    is cleared for the reviewed root gateway and its three format receipts match
    the rights register.
13. `python -m pytest -q tests/test_lebanese_expansion_1_17_contracts.py`
    passes, followed by the complete `python -m pytest -q` suite.
14. PHP lint passes for every plugin PHP file, `git diff --check` is clean, the
    secret scan is clean, and the deterministic ZIP passes package validation.
15. Before any later production deployment, anonymous live checks prove that
    the new identities remain absent from public API, rendered routes, search,
    sitemap, schema and store output. Existing public routes, bilingual
    canonical behavior, health identity and the 36-product catalog must remain
    unchanged. Any temporary deployment bridge must be deleted and return 404.

Failure of any one check holds the release. A count mismatch, source collision,
extra held record or accidental public exposure is not an editorial exception.

## Next-cuisine handoff principle

The next cuisine should inherit the control system, not Lebanese cultural
claims.

1. Start with a dedicated cuisine root, source ledger, exact entity budget and
   exact module split. Freeze IDs, slugs, types, source counts and held IDs in a
   cuisine-specific contract test before integration.
2. Separate regional depth from community and institutional depth when that
   improves source ownership. A community record must remain one bounded voice
   among regional, family and institutional records, never a proxy for the
   whole cuisine.
3. Carry forward the reusable private defaults: source-bounded facts,
   non-public relations, resolvable parents, one cluster root, private route
   mode, unique visual prompts, explicit rights state, named safety controls and
   fail-closed legal gates.
4. Do not copy Lebanese source IDs, claim text, entity identities, geographic
   assumptions, community attributions or trade conclusions into another
   cuisine. Research shared dishes comparatively and preserve separate
   identities unless evidence supports a narrower relationship.
5. Begin reference-only with no public or commercial inheritance. Publication,
   imagery, testing, commerce and live deployment each require their own
   evidence dossier and acceptance decision.

The handoff is successful when a future reader can understand what each record
means, where its evidence stops and why it remains private, without mistaking
research structure for a menu, product catalog or cultural verdict.
