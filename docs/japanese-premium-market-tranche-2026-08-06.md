# Japanese premium market tranche

Date: 2026-08-06

## Decision and publication boundary

This tranche prepares a private, source-backed Japanese premium pantry and professional-tools layer for Complete99. It adds 12 source-specific retail-listing candidates, with exact-variant gates where unresolved, eight knowledge subjects, five draft bundles and nine draft merchandising relationships.

Nothing in this tranche is a public offer. Every new product remains `research_candidate`, every SKU uses `research_only`, every draft channel offer is held, and every planning stock quantity is zero. No WooCommerce product code, public route, payment state, role or live inventory value is created.

Every new variant also carries `public_market_projection=held`. The public source-market projection accepts only the exact explicit value `public`. A missing, malformed, unknown or held value is non-public. Nine preexisting source-market variants retain their previously intended output through an explicit allowlist; the preexisting hangiri candidate and all 12 new candidates are held. This prevents observations from leaking onto an existing public ingredient, equipment or hub page while the tranche is under review.

The planning prices are not approved selling prices. They are explicit commercial hypotheses for later landed-cost and margin review. A listing establishes only what that exact source displayed at the recorded retrieval date. It does not establish supplier approval, importability, stock in Israel, laboratory composition, partnership or endorsement.

## Evidence model

| Evidence class | Permitted use | Prohibited inference |
| --- | --- | --- |
| Exact dated product listing | Product identity, displayed pack, displayed source price and displayed availability | Israeli stock, final landed cost, regulatory approval, unlisted specifications or stable future price |
| Producer or official business page | Process or category context expressly stated by that source | Independent validation, lot result, Complete99 relationship or endorsement |
| Government or institutional source | Market, cultural, technique or regulatory context within its jurisdiction | Automatic application to another jurisdiction or an unstated commercial relationship |
| Peer-reviewed category study | Category-level scientific context | Exact SKU composition, health benefit, pH, concentration, certificate or lot result |
| Competitor page | Search-intent and market-position context | Price authority for a different SKU or proof that the Complete99 candidate is comparable |

## Foreign-exchange planning basis

The conversion evidence points to the [Bank of Israel official exchange-rates API](https://boi.org.il/PublicApi/GetExchangeRates). The API record used here reports `lastUpdate=2026-08-06T12:21:04Z`; the evidence artifact records retrieval at `2026-08-06T18:19:19Z`. The stored internal basis is:

- USD 1 = ILS 3.0130
- JPY 100 = ILS 1.9088, therefore JPY 1 = ILS 0.019088
- AUD 1 = ILS 2.1218

Each source equivalent is rounded to two decimal places after multiplication. Freight, insurance, breakage, duty, VAT, customs handling, storage, shrink, certification, payment cost and target margin are not silently folded into that source equivalent. The higher ILS planning value is stored separately and cannot activate without its gates.

## Twelve private commerce candidates

| Candidate | Exact source observation | ILS source equivalent | Held ILS plan | Principal activation gates |
| --- | --- | ---: | ---: | --- |
| [Maruyama Gokujo Kontobi nori, 5 sheets](https://www.maruyamanori.com/c/kontobi_n/200660-C157) | JPY 1,350 including tax | 25.77 | 99 | Exact identity, supplier quote, iodine, heavy metals, allergens, import label, moisture protection, landed cost, margin, Woo acceptance |
| [Tajima seasoned red sushi vinegar, 360 ml](https://japanesetaste.jp/products/tajima-jozo-premium-akazu-aged-red-vinegar-for-sushi-360ml) | JPY 761 | 14.53 | 119 | Exact identity, JAN, formula, acidity, sugar, salt, shelf life, supplier quote, import label, landed cost, margin, Woo acceptance |
| [Minamigura Gin Warabeuta tamari, 200 ml](https://japanesetaste.com/products/minamigura-tamari-shoyu-gluten-free-japanese-soy-sauce-gin-warabeuta-200ml) | USD 16.95 | 51.07 | 159 | Exact identity, supplier quote, soy allergen, gluten-free certification, ingredients, import label, landed cost, margin, Woo acceptance |
| [Sugimoto organic dried shiitake, 70 g](https://int.japanesetaste.com/products/sugimoto-organic-japanese-dried-shiitake-mushrooms-70g) | USD 14.29 | 43.06 | 149 | Exact identity, JAS scope, traceability, microbiology, mycotoxins, pesticides, heavy metals, supplier quote, import label, landed cost, margin, Woo acceptance |
| [Yubaya Kyoto dried yuba, 100 g](https://www.yubaya.co.jp/) | JPY 1,080 including tax from the current producer homepage | 20.62 | 89 | Exact identity, supplier quote, soy allergen, ingredients, shelf life, cross-contact, import label, landed cost, margin, Woo acceptance |
| [Ohsawa organic kudzu starch, 150 g](https://japanesetaste.com/products/ohsawa-organic-kudzu-starch-block-type-thickening-powder-150g) | USD 14.98 | 45.13 | 149 | Exact identity, 100 percent kudzu, organic scope, supplier quote, import label, no health claims, landed cost, margin, Woo acceptance |
| [Yawataya Isogoro sansho, 12 g](https://japanesetaste.com/products/yawataya-isogoro-sansho-pepper-japanese-pepper-12g) | USD 21.99 | 66.26 | 169 | Botanical species, origin, harvest, supplier quote, light, oxygen and moisture protection, import label, landed cost, margin, Woo acceptance |
| [Marukyu Koyamaen Tenju matcha, SKU 1111020C1, 20 g](https://www.marukyu-koyamaen.co.jp/motoan-shop/products/1111020c1/) | JPY 21,600, sold out, with irregular-selling or shortage context | 412.30 | 749 | Stock, allocation, exact identity, supplier quote, import label, no health claims, landed cost, margin, Woo acceptance |
| [Yamaco bamboo makisu](https://www.mujostore.com/products/bamboo-sushi-mat) | AUD 28; exact 27 cm variant still requires confirmation | 59.41 | 129 | Exact 27 cm variant, food-contact finish, cleaning, warranty, stock, supplier quote, landed cost, margin, Woo acceptance |
| [Sakai Takayuki Ginsan yanagiba, 270 mm](https://www.knivesandstones.com.au/products/sakai-takayuki-ginsan-yanagiba-270mm) | AUD 399.95; Sakai Takayuki brand and Aoki Hamono manufacturer | 848.61 | 1,799 | Availability, handedness, exact steel, HRC, dimensions, warranty, shipping, supplier quote, landed cost, margin, Woo acceptance |
| [Nagatanien Kamado-san ACT-01, three cups](https://store.igamono.jp/?pid=85075826) | JPY 16,500 including tax, scheduled for sequential shipment after late September (9月下旬以降) | 314.95 | 1,199 | Shipment timing, stove compatibility, breakage, thermal shock, lead and cadmium documentation, weight, supplier quote, landed cost, margin, Woo acceptance |
| [Kubo Komakichi Kazuho chasen](https://teaosakaya.theshop.jp/items/65610450) | JPY 5,830 and sold out | 111.28 | 249 | Maker identity, approximately 70 tines, Takayama origin, stock, food-contact material, supplier quote, landed cost, margin, Woo acceptance |

Special source decisions:

- The Tajima candidate is stored as seasoned grain vinegar, not silently relabeled as pure Akazu.
- The Yubaya homepage price takes precedence over a stale item-page price. The [item page](https://www.yubaya.co.jp/products/item30.html) remains process and identity context only.
- The Marukyu record is limited to official SKU 1111020C1, 20 g, JPY 21,600 and its observed sold-out and shortage context. Unsupported comparison narratives are excluded.
- The Nagatanien record is limited to ACT-01, the three-cup model. Its source wording is sequential shipment after late September (9月下旬以降).
- The Kubo Komakichi maker-attributed chasen is not merged with generic Kazuho listings.
- The Australian makisu and yanagiba observations introduce an Australia source market, AUD currency, `en-AU` locale and an unresolved tax treatment. They do not create an Australian selling market.

Every candidate carries explicit `compliance_note_he` and `compliance_note_en` fields, each preserving the `[COMPLIANCE_NOTE: ...]` wrapper, plus an explicit pipe-delimited activation-gate set inside the private variant attributes. The legacy `compliance_note` projection remains the bracketed English value for compatible internal consumers.

## Eight knowledge subjects

| Subject | Entity treatment | Primary grounding | Structural role |
| --- | --- | --- | --- |
| Toyosu Market | Existing entity enriched | [Tokyo Metropolitan Government market page](https://www.shijou.metro.tokyo.lg.jp/info/0/toyosu) and [2026 overview](https://www.shijou.metro.tokyo.lg.jp/documents/d/shijou/r8-overview-of-toyosu-market_japanese-pdf-1) | Institutional market reference under Japanese sourcing |
| Kappabashi supplier district | New private supplier-research entity | [Official Kappabashi site](https://www.kappabashi.or.jp/en/) | Equipment-source discovery without approving or endorsing a shop |
| Japanese Culinary Academy | Existing entity enriched | [Official English site](https://culinary-academy.jp/english), [terminology corpus](https://culinary-academy.jp/corpus) and [digital book](https://culinary-academy.jp/taizen_digital_book) | Institutional terminology and education reference without affiliation |
| Ginza Kyubey | New private restaurant-reference entity | [Official restaurant site](https://www.kyubey.jp/en/) | Institutional sushi reference without partnership, rating invention or signature-dish imitation |
| Edomae shari control | New private technique entity | Existing FDA sushi-rice guidance in the science registry | Separates rice identity, seasoning, cooling, time, temperature and regulatory context |
| Kombujime | New private technique entity | [MAFF regional reference](https://www.maff.go.jp/e/policies/market/k_ryouri/search_menu/3639/index.html) and [2025 peer-reviewed process research](https://www.sciencedirect.com/science/article/pii/S1878450X25000253) | Technique knowledge linked to ingredients and controlled process variables |
| Futomaki sushi | New private dish entity | [MAFF regional cuisine reference](https://www.maff.go.jp/e/policies/market/japan-cuisine/japan/2/index.html) | Dish spoke connected to nori, seasoning, makisu and sushi technique |
| Kaiseki hassun | New private dish entity | [MAFF English cuisine reference](https://www.maff.go.jp/j/shokusan/gaisyoku/pamphlet/pdf/14-25_english.pdf) | Seasonal composition spoke linked to a draft seasonal bundle |

The FDA pH threshold of 4.2 or lower is represented only as a specific United States regulatory context for acidified sushi rice. It is not a recipe target, product value, batch measurement or automatic statement of Israeli law.

## Category-science register and claim boundary

The following sources support category context. They do not support an exact SKU claim:

- [Nori category research, PubMed](https://pubmed.ncbi.nlm.nih.gov/39053276/)
- [Soy sauce and tamari category research, PubMed Central](https://pmc.ncbi.nlm.nih.gov/articles/PMC7581291/)
- [Dried shiitake category research, PubMed](https://pubmed.ncbi.nlm.nih.gov/39517140/)
- [Kudzu category research, PubMed](https://pubmed.ncbi.nlm.nih.gov/41519333/)
- [Sansho category research, Foods](https://www.mdpi.com/2304-8158/12/19/3589)
- [Matcha category research, PubMed study one](https://pubmed.ncbi.nlm.nih.gov/36234707/)
- [Matcha category research, PubMed study two](https://pubmed.ncbi.nlm.nih.gov/35624753/)
- [Matcha foam preparation research, J-STAGE](https://www.jstage.jst.go.jp/article/nskkk/59/3/59_109/_article)
- [Minamigura method page](https://minamigura.com/gin-warabeuta/) for producer process context
- [Tajima seasoned-vinegar category](https://tajimajozo.co.jp/en/category/seasoned_vinegar/) for producer category context
- [Ginsan steel index](https://www.knivesandstones.com.au/pages/steel/ginsan-silver-3) for steel-category context
- [Nagatanien availability reference](https://store.shopping.yahoo.co.jp/igamono-nagatanien/1196168994.html) for current availability reconciliation

No category source is copied into a product's exact listing evidence. No pH, Brix, iodine, heavy-metal concentration, polyphenol level, nutrient benefit, HRC, composition or certificate is invented. Exact values remain pending until a supplier document, certificate, label, test or lot record supports them.

## Hub, spoke and semantic architecture

The tranche uses the existing Japanese hub hierarchy as its ownership layer:

- Japanese cuisine hub
  - Sourcing and institutional references
    - Toyosu Market
    - Kappabashi supplier district
    - Japanese Culinary Academy
    - Ginza Kyubey
  - Technique spokes
    - Edomae shari control
    - Kombujime
  - Dish spokes
    - Futomaki sushi
    - Kaiseki hassun
  - Ingredient and equipment knowledge spokes
    - Existing nori, kombu, yanagiba and related entities
    - Twelve source-specific retail-listing observation entities, with exact-variant gates where unresolved
  - Commerce identity layers
    - Product
    - Variant
    - SKU
    - Market observation
    - Draft channel offer
    - Evidence artifact
    - Bundle and merchandising edge

Each layer has its own stable identifier. Listing observation, product, variant and SKU identities are not collapsed. This allows one product to support future variants, markets, tax contexts, suppliers, lots and languages without contaminating scientific evidence or public SEO ownership.

All new listing entities and reference subjects are non-public. Technique and dish subjects can be mapped to existing sections, but remain excluded from the public API, public page generation and search index until editorial, culinary, rights and SEO review gates pass. Existing Toyosu and Japanese Culinary Academy noindex policies are preserved rather than overwritten.

For a future public release, one canonical intent owner must be selected for each query family. Product pages should own exact transactional intent. Ingredient, technique and dish pages should own informational intent. Internal links should express actual preparation, composition, substitution, complement or equipment relationships. They must not manufacture an endorsement or imply that a cited restaurant, institution or shop recommends Complete99.

## Draft merchandising and monetization structure

Five component-managed bundles are prepared in `draft` state:

1. Edomae Sushi Lab: nori, seasoned red sushi vinegar, tamari, makisu and yanagiba.
2. Umami and Shojin Lab: dried shiitake, dried yuba, kudzu, sansho and the existing Rishiri kombu candidate.
3. Matcha Ritual: Tenju matcha and the maker-attributed Kazuho chasen.
4. Pro Sushi Tools: makisu, yanagiba, Kamado-san and the existing hangiri candidate.
5. Seasonal Hassun Capsule: yuba, shiitake, sansho and nori.

Nine draft merchandising edges express cross-sell paths. They connect nori to makisu, vinegar to nori, tamari to nori, shiitake to kombu, yuba to tamari, kudzu to sansho, matcha to chasen, makisu to yanagiba and Kamado-san to hangiri. Makisu and yanagiba are complementary tools, not substitute quality tiers, so their edge is a cross-sell. An edge is a merchandising hypothesis, not a live recommendation. Activation requires source review, availability, unit economics, operational fit and Woo acceptance.

## Israeli competitor context

These pages are recorded only to understand local search intent, assortment language and visible positioning. They are not used as exact-SKU price authority for this tranche:

- [TevaMe dashi product](https://www.tevame.co.il/%D7%9E%D7%96%D7%95%D7%9F-%D7%9E%D7%A2%D7%95%D7%9C%D7%9D/%D7%94%D7%9E%D7%96%D7%A8%D7%97-%D7%94%D7%A8%D7%97%D7%95%D7%A711/%D7%99%D7%A4%D7%9F/%D7%90%D7%91%D7%A7%D7%AA-%D7%93%D7%90%D7%A9%D7%99)
- [East West Dashi Nomoto](https://www.ewi.co.il/en/products/sauce-en/dashi-nomoto-3/)
- [GoJapan Israel Japanese Matcha Premium 20 g](https://il.gojapan.net/)
- [BioGaya Jammoka donabe](https://www.biogaya.co.il/donabe-pot)

The visible competitor assortment shows generic or differently specified alternatives. The tranche therefore preserves pack, maker, material, origin, allocation and evidence distinctions instead of claiming like-for-like equivalence.

## Activation sequence

No candidate advances merely because it has a planning price. The minimum sequence is:

1. Reconfirm the exact identity, variant and current supplier quote.
2. Capture dated source and supplier evidence with pack, stock, tax and shipping terms.
3. Resolve every candidate-specific food, allergen, food-contact, tool-safety and label gate.
4. Build landed-cost scenarios from documented freight, insurance, duty, VAT, breakage, shrink and handling inputs.
5. Approve margin and price governance separately from source-equivalent arithmetic.
6. Confirm bilingual labels, media rights, accessibility and claim review.
7. Create or connect the WooCommerce product only after approval.
8. Run staging acceptance for inventory, cart, tax, fulfillment, kiosk and API projections.
9. Activate a public offer only through the governed release path.

Until all required steps pass, the correct operational state is draft, held and stock zero.
