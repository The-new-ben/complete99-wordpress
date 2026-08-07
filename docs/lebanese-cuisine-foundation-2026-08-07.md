# Lebanese cuisine foundation

Date: 2026-08-07

Release target: Complete99 Platform 1.14.0

Status: private editorial foundation, noindex, reference-only

## Release contract

Release 1.14.0 adds an 82-identity Lebanese regional cuisine foundation without
changing the public site or the live store. It expands the cumulative
culinary-science registry from 287 to 369 entities and Entity Studio from 343
to 425 subjects. Product identity and public commerce counts do not change.

| Invariant | Release 1.14.0 value |
| --- | ---: |
| Culinary-science registry | `culinary-science-2026.08.07.v14` |
| Culinary-commerce registry | `culinary-commerce-2026.08.07.v8` |
| Science identities | 369 |
| Lebanese identities | 82 |
| Syrian identities retained from 1.13.0 | 196 |
| Product identities | 56 |
| Entity Studio subjects | 425 |
| Public science entities | 23 |
| Public canonical page owners per language | 18 |
| Public WooCommerce products | 36 |
| Live WooCommerce prices | 36 |
| Private planning prices | 20 |
| Product price-basis coverage | 56 of 56 |
| New offers from the Lebanese tranche | 0 |

The exact Lebanese type distribution is:

| Type | Count |
| --- | ---: |
| Cuisine | 1 |
| Topic hubs | 13 |
| Dishes | 27 |
| Preparations | 2 |
| Ingredients | 8 |
| Techniques | 5 |
| Traditions | 9 |
| Culinary institutions | 5 |
| Markets | 2 |
| Restaurants | 3 |
| Compliance rules | 1 |
| Retail listings | 6 |
| Total | 82 |

## Surface and publication boundary

Every Lebanese identity has the same fail-closed baseline:

- `surface_class`: `editorial_draft`;
- `index_policy`: `noindex_private`;
- publication state: `private_preview`;
- `public_api`, `public_page` and `search_index`: false;
- SEO route mode: `private`;
- commerce state: `reference_only`;
- WooCommerce product code: blank;
- public offer allowed: false;
- no cross-sell, up-sell, stock, supplier or public taxonomy projection;
- visual asset state: `rights_review_required`;
- visual rights state: `pending`;
- every fact and relation is source-bound and not public-safe.

The private canonical candidates are rooted at:

- Hebrew: `/museum/lebanese-culinary-science/`;
- English: `/en/museum/lebanese-culinary-science/`.

These paths are ownership records, not permission to render pages. The root
`cuisine-lebanese-regional` and every child remain absent from public API,
search, sitemap, museum projection, catalog, POS and structured data. The
existing public science projection remains exactly 23 entities across 18
canonical page owners per language.

## Editorial ownership

The cuisine root owns a research map of Lebanese regions, dishes, pantry
systems, communities, institutions and markets. It does not present Lebanon as
one uniform national formula. Five regional hubs preserve Beirut, Mount Lebanon
and Chouf, North Lebanon and Tripoli, Bekaa and Baalbek-Hermel, and South
Lebanon and Jabal Amel as distinct contexts.

Topic owners separate:

- al-manouche practice;
- the regional kibbeh family;
- the mouneh system;
- community foodways;
- institutions and markets;
- plant and seafood tables;
- Armenian-Lebanese foodways in Bourj Hammoud;
- Palestinian foodways in Lebanon.

Community evidence stays within the source's actual scope:

- Lebanese Jewish records attach hamod, bazela, mahshi and karabij to named
  family or wedding testimony. They do not turn shared Levantine dishes into
  Jewish inventions or claim a uniform formula for all Lebanese Jews.
- Christian Lent records own an occasion context, not every dish eaten during
  Lent and not a uniform Christian or Maronite cuisine.
- South Lebanon Ashura records own a ritual-distribution context, not a general
  Shia cuisine catalog.
- Druze wild-plant evidence is a bounded study of communities in Aley and
  Chouf, not a definition of a Druze diet.
- Armenian-Lebanese evidence retains Armenian and diaspora identity.
- Palestinian foodways in Lebanon retain Palestinian identity. They are not
  assigned Lebanese origin and vulnerable community institutions are not
  treated as commercial leads.

No general Sunni or Shia cuisine is inferred from geography, a city, Ramadan
or Ashura. A new community claim requires source evidence at the household,
place, institution or occasion scope asserted by the page.

## Shared Levantine identity rule

Lebanese context does not establish exclusive origin. Regional variants and
shared Levantine families are separate canonical identities connected by
source-bound comparison relations. Release 1.14.0 explicitly preserves these
comparisons without merging:

| Lebanese identity | Compared identity |
| --- | --- |
| `dish-sayadiyah-lebanon` | `dish-sayadiyah-syrian-coast` |
| `dish-samkeh-harra-tripoli` | `dish-samaka-harra-baniyas` |
| `dish-kibbeh-summakiyeh-hermel` | `dish-kibbeh-somakiyya` |
| `dish-mujaddara-lebanon-family` | `preparation-mujadara-thursday-syrian-jewish` |
| `ingredient-lebanese-bulgur-context` | `ingredient-syrian-bulgur` |
| `ingredient-lebanese-kishk` | `ingredient-syrian-kishk` |
| `ingredient-lebanese-pomegranate-molasses` | `ingredient-syrian-pomegranate-molasses` |
| `ingredient-lebanese-sumac-context` | `ingredient-syrian-sumac` |
| `ingredient-lebanese-olive-oil-context` | `ingredient-syrian-olive-oil` |

The mujaddara family owns two Lebanese preparations without merging them:
`preparation-mujaddara-hamra-rmeish` and
`preparation-mudardara-rice-lebanon`. Tabbouleh and fattoush are context
records within wider Levantine families. They do not carry national invention
claims.

## Food-safety gates

### Raw ground meat

`dish-kibbeh-nayyeh-lebanon` is an identity and safety record only. It contains
no recipe, preparation instructions, consumption recommendation, holding time
or promotional serving direction. A CDC record of a Salmonella outbreak linked
to raw ground beef kibbeh supports the fail-closed boundary: [CDC archive](https://archive.cdc.gov/www_cdc_gov/salmonella/typhimurium-01-13/index.html).

No raw-meat culinary page or product can advance without a qualified
food-safety owner, a validated process and applicable Israeli regulatory
approval.

### Fermentation and preservation

Traditional description is not process validation. These records remain
private process maps:

- `ingredient-lebanese-kishk`;
- `ingredient-labneh-ambarees-shouf`;
- `ingredient-lebanese-qawarma`;
- `technique-kishk-fermentation-drying-lebanon`;
- `technique-labneh-ambarees-sirdele-fermentation`;
- `technique-qawarma-preservation-lebanon`.

Any operational recipe or product requires controls appropriate to the process,
including measured pH, water activity, microbiology, salt, temperature,
pasteurization or an equivalent control, refrigeration, packaging,
traceability, validated shelf life and HACCP review. Safe cooking alone does
not prove shelf stability. Cultural significance does not approve a process.

### Wild plants

`tradition-druze-wild-plant-knowledge-chouf-aley` records a study based on 50
interviews and 68 documented wild plant taxa. It is not permission to forage,
self-identify, sell or consume an unverified plant. Botanical identity, lawful
access, contamination, overharvesting and conservation require qualified local
review before any practical use.

## Trade and import compliance

The central private record is
`compliance-lebanon-trade-israel-2026`. Every other Lebanese identity has a
source-bound relation to it.

Israel Ministry of Economy and Industry Director-General Instruction 2.4,
published 2026-03-08, states a broad prohibition on direct or indirect trade
with enemy states and lists Lebanon: [official instruction](https://www.gov.il/BlobFolder/policy/economy_dgi_instructions_02_04/he/instructions_2-04_080326_2-4-08-03-26.pdf).

Until qualified counsel and the appropriate authority provide written approval:

- do not contact a Lebanese supplier for purchasing;
- do not request a commercial sample;
- do not place an order or make payment;
- do not route a purchase through a third party;
- do not represent delivery or availability from Lebanon to Israel;
- do not turn a Lebanese institution, restaurant or market into a Complete99
  supplier record.

This document records an operating gate, not legal advice. Any otherwise lawful
food import must also pass importer and product requirements. The current
non-animal food importer entry point is the [Israel Ministry of Health importer registration service](https://www.gov.il/en/service/non-animal-derived-food-importer-registration).

## Dated price observations

All six observations were recorded at
`2026-08-07T12:00:00+03:00`. Each uses a sample size of one, tax and shipping
status are unknown, comparability is `non_comparable`, and capture was a manual
review of a live retail page without a stored snapshot digest.

| Private identity | Observation | Source |
| --- | --- | --- |
| `listing-mymoune-pomegranate-molasses-250ml-spinneys-20260807` | Mymoune pomegranate molasses, 250 ml, USD 11.49 | [Spinneys Lebanon](https://www.spinneyslebanon.com/brands/mymoune) |
| `listing-mymoune-zaatar-200g-spinneys-20260807` | Mymoune zaatar, 200 g, USD 8.49 | [Spinneys Lebanon](https://www.spinneyslebanon.com/brands/mymoune) |
| `listing-terroirs-zaatar-70g-eu-20260807` | Terroirs du Liban premium zaatar, 70 g, EUR 7.82 | [Terroirs du Liban Europe](https://europe.terroirsduliban.com/) |
| `listing-terroirs-freekeh-500g-eu-20260807` | Terroirs du Liban freekeh, 500 g, EUR 15.20 | [Terroirs du Liban Europe](https://europe.terroirsduliban.com/) |
| `listing-pereg-zaatar-baladi-ils-20260807` | Pereg zaatar baladi, ILS 88 per kilogram | [Pereg](https://www.tavlineypereg.co.il/ProductInfo.asp?ProdId=179) |
| `listing-nitzat-pomegranate-concentrate-280g-ils-20260807` | Organic pomegranate concentrate, 280 g, ILS 25.90 | [Nitzat Haduvdevan](https://www.nizat.com/%D7%A8%D7%9B%D7%96-%D7%A8%D7%99%D7%9E%D7%95%D7%A0%D7%99%D7%9D-%D7%90%D7%95%D7%A8%D7%92%D7%A0%D7%99-%D7%9C%D7%9C%D7%90-%D7%AA%D7%95%D7%A1%D7%A4%D7%AA-%D7%A1%D7%95%D7%9B%D7%A8-280-%D7%9E%D7%9C%60-%D7%92%D7%95%D7%A8%D7%92%D7%90%D7%A1-%D7%A0%D7%98%D7%95%D7%A8%D7%9C-i25172) |

These are external benchmarks, not Complete99 products, prices, offers,
supplier terms, stock, market averages, import routes or landed-cost evidence.
They create no WooCommerce code and no active or draft offer. The two Israeli
comparisons are not called Lebanese without origin-label evidence. Freekeh is
not merged with bulgur, and pomegranate concentrate is not merged with
pomegranate molasses or compared on price without unit normalization.

## Historical Syria boundary

Release 1.14.0 preserves the 1.13.0 Syrian graph at exactly 196 identities,
including 56 dishes, 55 ingredients, 21 regional or topic hubs, 17 techniques,
17 traditions and 15 preparations, plus markets, restaurants, hospitality
institutions and three private held observations. One safe Syrian consumer
gateway remains `noindex,follow`; the other 195 Syrian identities remain
private. The detailed historical record remains in
`docs/syrian-regional-depth-2026-08-07.md` and the 1.13.0 section of
`docs/consumer-commerce-runbook.md`.

## Pre-deployment verification

Run the focused contract suite from the repository root:

```powershell
pytest -q tests/test_lebanese_cuisine_foundation_contracts.py
pytest -q tests/test_culinary_science_contracts.py tests/test_culinary_commerce_contracts.py tests/test_entity_studio_contracts.py tests/test_contracts.py
```

Then run the normal release package gates:

```powershell
python scripts/build-plugin-zip.py --verify-reproducible
python scripts/validate-package.py
```

Before authorizing deployment, verify:

- plugin header, deployment ID, manifest and package all report `1.14.0`;
- science v14 and commerce v8 validate against one another;
- cumulative counts are 369 science, 56 products and 425 Entity Studio
  subjects;
- Lebanon is exactly 82 with the type distribution in this document;
- Syria remains exactly 196;
- public science remains 23 entities and 18 page owners per language;
- the WooCommerce allowlist remains exactly 36 products;
- all 82 Lebanese records retain the private, noindex and reference-only
  baseline;
- all six observations have the exact timestamp and no offer projection;
- the source and documentation contain no U+2014 em dash;
- package secret, deterministic-build and integrity checks pass.

## Deployment and live verification

Deploy only through the reviewed Complete99 WordPress release pipeline. After
installation, cache purge and stabilization:

1. Verify anonymous health reports `1.14.0`, deployment ID `c99-wp-1.14.0` and
   the expected database version.
2. Verify public science still contains exactly 23 entities and 18 canonical
   page owners per language.
3. Verify all 82 Lebanese identities are absent from public REST, WordPress
   search, managed sitemap, museum sitemap, public catalog and POS projection.
4. Verify cache-busting requests to the Hebrew and English Lebanon canonical
   candidates do not render public pages or expose private child entities.
5. Verify public HTML and structured data contain no Lebanese benchmark price,
   source prompt, supplier claim, Product schema or Offer schema.
6. Verify authenticated private readback reports the exact 82 type counts, all
   six observation timestamps and zero WooCommerce codes or offers.
7. Verify the 36-product public store, price and stock authority, cart, filters
   and POS projection are unchanged. Payment remains in its prior held state.
8. Run real Chrome checks for the existing Hebrew and English home, culinary
   museum and pantry at desktop and 390 CSS pixels. Check language direction,
   canonical and hreflang, keyboard navigation, focus visibility, overflow,
   images and first-party console errors.
9. Confirm the temporary deployment row is absent, the temporary route returns
   404 and cache-busting requests show the 1.14.0 release marker.
10. Retain exact artifact, integrity, deployment, rollback and live negative
    evidence. An installer response by itself is not completion proof.

## Activation work still held

The foundation does not authorize public cuisine pages or products. Public
activation requires separate decisions and evidence for each page owner:

- non-overlapping consumer intent and complete Hebrew and English editing;
- claim-level source review and family or community scope review;
- tested recipe, yield, storage and allergen review where a recipe is proposed;
- qualified food-safety sign-off for raw, fermented, preserved or wild-plant
  content;
- rights-cleared final media with receipts;
- canonical, reciprocal hreflang, schema and internal-link review;
- an explicit public-exposure approval that does not change the trade boundary;
- a separate lawful commercial route and complete WooCommerce readiness before
  any product or offer exists.
