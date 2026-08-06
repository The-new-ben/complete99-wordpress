# Complete99 wasabi public science SEO, source and claim ledger

Status: internal operating record

Date: 2026-08-06

Public audience: culinary consumers

This ledger governs the public science and discovery layer for the first wasabi commerce slice. It is not public page copy, a supplier agreement, a price approval, a stock declaration or a product safety certificate.

## Release boundary

The public science layer may explain the ingredient, pungency mechanism, molecule and preparation tool. It may bind a science entity to a WooCommerce product code so WordPress has one stable identity graph. For the two independently active WooCommerce offers in this slice, `public_offer_allowed=true` permits the science projection to emit the verified product link. This permission does not set or originate price, stock, cart or fulfillment state. Release 1.6.0's independent Woo catalog gate remains the sole owner of those commercial facts and actions.

All newly promoted entities remain `noindex_until_longform_review` with `search_index=false`. Section entities share their standalone owner's canonical URL and never receive an independent sitemap URL.

## Entity and route ownership

| Entity | Role | Route owner | Hebrew canonical | English canonical | Index state | Commerce boundary |
| --- | --- | --- | --- | --- | --- | --- |
| `ingredient-fresh-wasabi` | Standalone ingredient explainer | Self | `/ingredients/fresh-wasabi-rhizome/` | `/en/ingredients/fresh-wasabi-rhizome/` | Noindex | Science may emit the verified link to `product-fresh-japanese-wasabi-250g`; the independent Woo catalog gate owns price, stock, cart and fulfillment |
| `guide-wasabi-aitc` | Standalone science guide | Self | `/knowledge/wasabi-aitc-pungency/` | `/en/knowledge/wasabi-aitc-pungency/` | Noindex | Educational owner, not a product page |
| `molecule-allyl-isothiocyanate` | Owned molecular section | `guide-wasabi-aitc` | Same as guide | Same as guide | Noindex section | No product or price fields |
| `hub-japanese-equipment` | Japanese cuisine tools section | `cuisine-japanese-washoku` | `/museum/japanese-culinary-science/` | `/en/museum/japanese-culinary-science/` | Noindex section | Discovery only |
| `equipment-wasabi-grater` | Standalone selection guide | Self | `/knowledge/wasabi-grater-guide/` | `/en/knowledge/wasabi-grater-guide/` | Noindex | Science may emit the verified link to `product-hagane-zame-large`; the independent Woo catalog gate owns price, stock, cart and fulfillment |
| `listing-hagane-zame-large-20260806` | Dated market observation | Private listing record | None | None | `noindex_private` | Exact source-market observation stays private |

## Search-intent ownership contract

The Hebrew cells in this table are stored as valid UTF-8 text.

| Owner | Hebrew intent | English intent | Included variants | Protected exclusions |
| --- | --- | --- | --- | --- |
| `ingredient-fresh-wasabi` | להבין שורש וואסבי טרי, מקור, איכות וחריפות | Understand fresh wasabi rhizome, origin, quality and pungency | וואסבי יפני אמיתי, איזותיוציאנטים בוואסבי, real Japanese wasabi, wasabi isothiocyanates | Exact SKU price, stock promise, supplier terms and internal handling instructions |
| `guide-wasabi-aitc` | להבין כיצד נוצרת חריפות AITC בוואסבי | Understand AITC and fresh wasabi pungency | אליל איזותיוציאנט, מדע וואסבי, allyl isothiocyanate, wasabi pungency science | Medical claims, food-safety promises and generic product concentration |
| `molecule-allyl-isothiocyanate` | להסביר את המולקולה בתוך מדריך החריפות | Explain the molecule inside the pungency guide | איזותיוציאנטים, חריפות נדיפה, isothiocyanates, volatile pungency | Independent canonical, independent sitemap entry and a competing AITC page |
| `hub-japanese-equipment` | לגלות כלי מטבח יפניים לפי פעולת הכנה | Discover Japanese culinary tools by preparation task | כלי מטבח יפניים, Japanese culinary tools | Individual model specifications and transactional product intent |
| `equipment-wasabi-grater` | לבחור מגררת להכנת וואסבי טרי | Choose a grater for fresh wasabi | אורושי לוואסבי, Hagane-zame, oroshi wasabi tool, Hagane-zame grater | Exact product price, live inventory and supplier availability |
| WooCommerce product owners | לקנות את המוצר המדויק | Buy the exact product | Product name, pack size, price and availability only after activation | Science guide head terms and unsupported scientific claims |

This ownership model prevents the ingredient page, AITC guide, molecular section, equipment hub, grater guide and Woo product pages from competing for the same query.

No live SERP or Google Search Console result was used in this implementation slice. These targets are an architecture contract, not a ranking prediction. Index approval requires a later rendered-page, canonical, intent and evidence review.

## Source and atomic claim ledger

| Claim ID or use | Public claim boundary | Evidence class | Source IDs | Notes |
| --- | --- | --- | --- | --- |
| `fact-wasabi-itc-variation` | AITC and related isothiocyanates vary by sampled accession, plant organ and tested seasonal context | Peer-reviewed context | `wasabi-itc-2023` | Never convert a literature value into a product or lot measurement |
| `fact-wasabi-guide-itc` | A fixed AITC concentration cannot be assigned to every rhizome without measurement | Peer-reviewed context | `wasabi-itc-2023` | Category-level explanation only |
| `fact-wasabi-guide-enzyme-system` | Tissue disruption and the enzyme system relate glucosinolates to isothiocyanate formation | Peer-reviewed context | `wasabi-itc-2023` | Mechanism explanation, not a health claim |
| `fact-aitc-wasabi-variation` | AITC is represented as a variable molecule in the fresh-wasabi context | Peer-reviewed context | `wasabi-itc-2023` | The molecule remains a section of the guide |
| `fact-wasabi-grater-material-boundary` | Yamamoto Foods identifies Hagane-zame as a stainless-steel wasabi grater and separates model specifications | Official business source | `yamamoto-haganezame-spec` | Do not apply one model's measurements to every model |
| Equipment hub fact | Kappabashi is an official kitchenware context and Yamamoto provides model-specific grater specifications | Official organization plus official business | `kappabashi-official`, `yamamoto-haganezame-spec` | Supports the public discovery hierarchy, not a supplier relationship |
| Exact large-variant market observation | Retailer listing details and observed source-market price | Official market listing | `hagane-zame-large-listing-2026` | Private only. A retail observation is not a supplier quote or landed cost |

Primary source records:

- `wasabi-itc-2023`: Breeding Science, "Genetic and seasonal variation of isothiocyanates in wasabi," https://www.jstage.jst.go.jp/article/jsbbs/73/3/73_22080/_html/-char/en
- `yamamoto-haganezame-spec`: Yamamoto Foods Co., Ltd., "Hagane-zame wasabi grater specifications," https://www.yamamotofoods.co.jp/haganezame/jp/spec/
- `kappabashi-official`: Kappabashi Dougu Street Promotion Association, https://www.kappabashi.or.jp/en/overview/
- `hagane-zame-large-listing-2026`: The Wasabi Company dated retail observation, retained only for the private market record

## Source-backed relation contract

| From | Relation | To | Public basis | Public purpose |
| --- | --- | --- | --- | --- |
| `ingredient-fresh-wasabi` | `contains` | `molecule-allyl-isothiocyanate` | `wasabi-itc-2023` | Move from ingredient to the atomic pungency explanation |
| `ingredient-fresh-wasabi` | `references` | `guide-wasabi-aitc` | `wasabi-itc-2023` | Move from ingredient overview to the full mechanism guide |
| `ingredient-fresh-wasabi` | `complements` | `equipment-wasabi-grater` | `yamamoto-haganezame-spec` | Move from ingredient to appropriate preparation-tool guidance |
| `molecule-allyl-isothiocyanate` | `part_of` | `guide-wasabi-aitc` | `wasabi-itc-2023` | Enforce guide ownership and prevent a competing molecular route |
| `molecule-allyl-isothiocyanate` | `part_of` | `ingredient-fresh-wasabi` | `wasabi-itc-2023` | Preserve the botanical and culinary context |
| `equipment-wasabi-grater` | `used_in` | `ingredient-fresh-wasabi` | `yamamoto-haganezame-spec`, `wasabi-itc-2023` | Connect tool use to the ingredient and its scientific context |
| `equipment-wasabi-grater` | `references` | `guide-wasabi-aitc` | `wasabi-itc-2023` | Explain why grating belongs in the pungency journey |
| `guide-wasabi-aitc` | `references` | `equipment-wasabi-grater` | `yamamoto-haganezame-spec`, `wasabi-itc-2023` | Connect the scientific explanation to the consumer's preparation action |

Internal links are curated navigation. They are not proof of a claim by themselves. Public relations remain source-backed, and the relation target must also belong to the approved public cohort.

## Cross-sell and up-sell ownership logic

The science registry may hold candidate commerce relationships. The public science projection may expose only a verified product link when its science entity is explicitly allowed and the target offer is independently active and approved. It never projects price, stock, cart or fulfillment from science data.

- `ingredient-fresh-wasabi` owns educational context for the rhizome. Its candidate cross-sell to `equipment-wasabi-grater` expresses a preparation pairing, not inventory or availability.
- `equipment-wasabi-grater` owns tool-selection guidance. Its candidate cross-sell back to fresh wasabi expresses ingredient and tool complementarity.
- The WooCommerce product identified by `product-fresh-japanese-wasabi-250g` owns the exact pack, sell price, stock, fulfillment and add-to-cart action through release 1.6.0's independent catalog gate.
- The WooCommerce product identified by `product-hagane-zame-large` owns exact model specifications, sell price, stock, fulfillment and add-to-cart action through release 1.6.0's independent catalog gate.
- An up-sell may be created only between verified sellable variants with a clear quality, size, provenance or performance difference. The science guide must not invent an up-sell solely from a higher price.
- A cross-sell or up-sell becomes public only when both product owners are active offers, product-code resolution succeeds, the relationship is reviewed, and the public commerce gate allows it.

## Source-market and exchange-rate evidence

The source-market observation and the owner-approved WooCommerce sell price are separate records. The Bank of Israel page showed representative rates dated 2026-08-05 when checked on 2026-08-06: GBP 4.0450 ILS and JPY 1.9049 ILS per 100 yen. The Bank describes a representative rate as an indicator and not a legally binding transaction rate.

| Product | Dated source amount | Representative-rate calculation | Converted observation | Owner-approved WooCommerce price |
| --- | ---: | ---: | ---: | ---: |
| Fresh Japanese wasabi, 250 g | GBP 62.50 | `62.50 x 4.0450` | ILS 252.81 | ILS 399 |
| Hagane-zame Pro large grater | JPY 17,050 | `17,050 / 100 x 1.9049` | ILS 324.79 | ILS 699 |

Bank of Israel source: https://www.boi.org.il/en/economic-roles/financial-markets/exchange-rates/

## Private commercial sensitivity table

The following values are scenarios, not facts. They are private planning inputs and must not enter public science copy, public API projections, schema markup or indexable pages.

Source retail observations are not supplier costs. They do not include or prove freight, customs, VAT, shrinkage, cold-chain loss, packaging, payment fees, handling or final gross margin.

| Candidate product | Scenario retail price | Hypothetical landed cost | Scenario gross margin | Status |
| --- | ---: | ---: | ---: | --- |
| Fresh wasabi, 250 g | ILS 399 | ILS 220 | 44.9% | Scenario, not fact |
| Fresh wasabi, 250 g | ILS 399 | ILS 260 | 34.8% | Scenario, not fact |
| Fresh wasabi, 250 g | ILS 399 | ILS 300 | 24.8% | Scenario, not fact |
| Hagane-zame large grater | ILS 699 | ILS 360 | 48.5% | Scenario, not fact |
| Hagane-zame large grater | ILS 699 | ILS 420 | 39.9% | Scenario, not fact |
| Hagane-zame large grater | ILS 699 | ILS 480 | 31.3% | Scenario, not fact |

Gross margin scenario formula: `(scenario retail price - hypothetical landed cost) / scenario retail price`.

## Visual asset receipts

The four science assets below are approved registry receipts for the promoted science entities. The two generated product assets are owner-approved public catalog illustrations recorded by exact hash. Recording a product asset here does not originate price, stock or offer state and does not override release 1.6.0's independent catalog gate.

| Asset | Role | Dimensions | Bytes | SHA-256 | Registry state |
| --- | --- | ---: | ---: | --- | --- |
| `assets/images/science/c99-science-allyl-isothiocyanate-v01.webp` | Molecular section visual | 1536x1024 | 57,014 | `87fdf5927fd72ba282e97d72c948d87213f02fbdef2dd4a13ce607f042084ae6` | Approved science receipt |
| `assets/images/science/c99-science-japanese-professional-equipment-v01.webp` | Japanese tools hub visual | 1536x1024 | 317,978 | `1c36efbad8d50150c0147bb1064bba40abf60feb7ed036cdca9dcabbb6e80b12` | Approved science receipt |
| `assets/images/science/c99-science-wasabi-aitc-pungency-v01.webp` | AITC guide visual | 1536x1024 | 144,684 | `a74f67aaab227256031f2b0bd477bee76562b36ddf072338ccca69d1b894918c` | Approved science receipt |
| `assets/images/science/c99-science-wasabi-grater-v01.webp` | Grater guide visual | 1536x1024 | 200,008 | `be0f4f831f58efc4ab6b6c74fa1979aaa4797bf9e4f1be51a19b2afe6d9a1757` | Approved science receipt |
| `assets/images/generated/c99-equipment-hagane-zame-pro-v01.webp` | Public product visual | 1536x1024 | 252,714 | `0101ca2bd2dec07d7cfb47d99ce2b9202d41f98984f716c064967299c951bedc` | Owner-approved catalog illustration |
| `assets/images/generated/c99-ingredient-fresh-wasabi-250g-v01.webp` | Public product visual | 1536x1024 | 195,916 | `0927c2f5ce94433d97b8c7f5175c9c1f29dede80edbce3242265497cf94de5c3` | Owner-approved catalog illustration |

## Trust, review and correction contract

Who: Complete99's organization editorial process is the declared publisher. No named chef, scientist or medical reviewer is claimed in this slice.

How: Each public fact stores an evidence class, exact source ID, review date, value scope and public-safety state. Literature context is not converted into a product specification. Official maker information is not converted into supplier authorization.

Why: The pages help culinary consumers understand fresh wasabi, the origin of its pungency and the role of an appropriate grating tool. The science graph provides a coherent path to independently gated WooCommerce offers without allowing science data to declare price, stock or cart state.

Corrections: `/contact/` in Hebrew and `/en/contact/` in English.

Review triggers:

- The peer-reviewed source is corrected or superseded.
- Yamamoto Foods changes the model specification or source URL.
- A WooCommerce product binding changes.
- A supplier, landed cost, price, stock or fulfillment state is approved or withdrawn.
- A visual file changes bytes and therefore changes its SHA-256 receipt.
- Long-form copy, rendered layout, canonical behavior or index policy changes.

## Approval still required before indexing

- Rendered Hebrew and English content review with no mixed-language or internal-project language.
- Exact canonical and alternate-language verification on the live host.
- Long-form usefulness and duplication review against the ingredient, guide, molecule section, equipment hub, grater guide and store owners.
- Source-link and correction-path verification.
- Structured-data review with no product, nutrition, health or review claims beyond verified fields.
- Search Console inspection after publication and before changing `search_index` to true.
