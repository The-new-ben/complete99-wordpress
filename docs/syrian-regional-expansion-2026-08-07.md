# Syrian regional, community and institutional expansion

Release target: Complete99 Platform 1.16.0

## Purpose and boundary

Release 1.16.0 deepens the existing Syrian culinary graph without changing the
public consumer website or creating a product, supplier, import route, price,
stock record or order path. The release adds 86 bilingual, source-bound private
identities and brings the Syrian graph from 196 to 282 identities. The full
culinary-science registry reaches 551 identities, and Entity Studio resolves
607 subjects: 551 science identities plus the unchanged 56 product identities.

All 86 new identities remain private, noindex and reference-only. WordPress and
WooCommerce remain the source of truth. The public boundary stays at exactly 36
WooCommerce products, 23 public science identities and 18 canonical page owners
per language. Payment remains disabled.

## Exact release invariants

| Invariant | Release 1.16.0 value |
| --- | ---: |
| Culinary-science registry | `culinary-science-2026.08.07.v16` |
| Culinary-commerce registry | `culinary-commerce-2026.08.07.v10` |
| Science identities | 551 |
| Product identities | 56 |
| Entity Studio subjects | 607 |
| Syrian identities | 282 |
| New Syrian identities | 86 |
| Public WooCommerce products | 36 |
| Public science identities | 23 |
| Public page owners per language | 18 |
| New offers, stock or supplier records | 0 |

## Modular split

The expansion is divided into three independently testable modules.

| Module | Identities | Scope |
| --- | ---: | --- |
| West and central Syria | 30 | Aleppo, Damascus, Homs, Hama, Idlib, Qadmus, Kassab, Baniyas and Jableh |
| East and south Syria | 31 | Qamishli, Jazira, Al-Bukamal, Deir ez-Zor, Palmyra, Suwayda and Hauran |
| Communities and institutions | 25 | Archives, institutions, one market, restaurant benchmarks, Jewish family records and other community records |

Across the three modules, the 86 identities contain 4 topic hubs, 21 dishes,
14 ingredients, 13 techniques, 12 traditions, 7 guides, 1 preparation, 9
culinary institutions, 1 market and 4 restaurant benchmarks.

## Regional evidence map

Aleppo and Damascus receive a comparison guide and separate stuffing-method
techniques. One family testimony documents tomato paste inside the filling in
Aleppo and cumin and safflower in a Damascene filling with tomato paste in the
sauce. This is presented as a documented family comparison, not as a universal
rule for either city.

Homs receives separate shanklish and qareesheh identities, a controlled
fermentation and drying technique, and the asrouniyeh afternoon-meal tradition.
Hama receives a separate batersh smoke and tahini-emulsion technique, with gas,
charcoal and wood-fire observations kept distinct and no unsupported chemical
measurement.

Idlib and Harem receive jerneh, chili dakkah, aqras al-zawbaa, an olive-press
tradition, a local thyme identity, a pounding technique and a historical-only
raw-kibbeh comparison guide. Unsupported disease-prevention claims are excluded.

The coast is expanded through Qadmus, Kassab, Baniyas and Jableh. Qadmus gains a
regional hub, a pastry, dried figs, a New Year tradition and two held Arum
records. Kassab gains a Syrian-Armenian hub, Assumption Day hareesa, a long-cook
technique and two dishes. Raw-poultry washing from a source recipe is not carried
into any public or operational instruction. Baniyas and Jableh gain separately
addressed local cakes and sweets.

Qamishli receives an Assyrian foodways hub, an Akitu tradition, peeled barley
for dikhwa and a yogurt-stabilization technique. An unidentified local phrase
for spice seeds remains unidentified rather than being guessed.

Al-Bukamal and Deir ez-Zor receive a regional hub, Bedouin and urban foodways,
home saj and tannour technique, okra and molokhia ingredients, seasonal okra
handling, mshahmiyya, muhammara on saj, kileija and a kileija-patterning
tradition. An alternate name for an existing dish remains an alias rather than
a duplicate entity.

Palmyra receives date-palm, desert-truffle and wheat identities; hannaniyya,
desert truffle with saj, two bulgur dishes and their related techniques and
traditions. Clay-coated communal cooking remains a cultural and technical record
with food-contact, fire and lifting controls. A first-tooth boiled-wheat
tradition is cultural documentation and not infant-feeding advice.

Suwayda and Hauran receive a purslane identity and bounded guides for halqoum,
mleihi and regional qawarma forms. Differences are compared without inferring
historical priority from one ingredient choice.

## Communities, archives and benchmarks

The institutional layer adds source-bound records for Agricultural Voices
Syria, IFPO culinary documentation, the Syrian Academy of Gastronomy,
Smithsonian Syrian-Armenian foodways, Jewish Food Society family archives,
FOODISH at ANU Museum, Asif, the National Library of Israel and the Library of
Congress foodways archive.

These records identify what a source documents and what it does not prove.
They do not imply partnership, endorsement, current operation, recipe testing,
image rights, commercial availability or authority over an entire community.

The market layer adds the Al-Midan sweets corridor as a bounded place and
production context. Restaurant records for Imad's Syrian Kitchen, Le Petit
Alep, Abu Hagop and Old Ashtarak are external benchmarks only. Current status
remains dated or unverified where a fresh official source does not establish it.

Jewish foodways are represented through separate Aleppan, Damascene and family
diaspora records, including Passover kibbeh, ejjeh, heitaliyeh, Syrian string
cheese, chicken with mehshi sfeeha and macaroni chicken. These records document
named family or archive lineages. They do not replace the wider Syrian cuisine,
merge Aleppo and Damascus, or claim exclusive origin.

Additional community records cover a documented Syrian-Armenian doshka family,
Syrian-Armenian food enterprise in diaspora, an Assyrian cross-border evidence
boundary, Kurdish olive-oil memory from Afrin and a Suwayda bitter-coffee
hospitality technique.

## Fail-closed safety and identity controls

Four records remain held:

- `ingredient-loof-arum-qadmus-held`;
- `technique-qadmus-cooked-arum-preservation-held`;
- `guide-al-manzala-palmyra-identity-held`;
- `guide-halqoum-haurani-identity-held`.

Arum remains held because raw material can be toxic and requires verified
botanical identity and a validated toxicity-reduction process. The Palmyra and
Hauran guide identities remain held because the available sources do not yet
establish a sufficiently precise product or preparation identity.

Other controls cover raw meat, cooked meat and poultry, dairy, fermentation,
drying, water activity, pH, hot holding, fire and smoke, food-contact clay,
heavy equipment, allergens, wild plants, foraging and infant-related cultural
records. A source can establish cultural context without establishing a safe
recipe, a commercial specification or a shelf life.

## Visual asset boundary

Every new entity contains an original English studio or documentary visual
prompt. Prompts describe subject, light, angle, texture and regional context,
and prohibit text, logos, watermarks, copied archive composition and invented
brand packaging. An archive citation does not grant rights to copy its image.
Generated concepts remain in rights review until a human approves the asset and
records the required receipt.

## Core evidence register

The entity modules retain exact source IDs and retrieval dates. The principal
open sources include:

- [Agricultural Voices Syria](https://agricultural-voices.sussex.ac.uk/) for
  named family and regional testimony from Damascus, Homs, Hama, the coast,
  Deir ez-Zor, Palmyra and Suwayda;
- [The Aleppo Project cuisine study](https://www.thealeppoproject.com/wp-content/uploads/2017/08/Cuisine-Final.pdf)
  for Aleppan culinary context;
- IFPO foodways chapters for
  [Qamishli](https://create.ifrepo.world/static/ifcollectors/pdf/chapter_2.pdf),
  [Qadmus](https://create.ifrepo.world/static/ifcollectors/pdf/chapter_5.pdf),
  [Al-Bukamal and Deir ez-Zor](https://create.ifrepo.world/static/ifcollectors/pdf/chapter_6.pdf),
  [Idlib and Harem](https://create.ifrepo.world/static/ifcollectors/pdf/chapter_7.pdf),
  [Kassab](https://create.ifrepo.world/static/ifcollectors/pdf/chapter_9.pdf)
  and [Hauran](https://create.ifrepo.world/static/ifcollectors/pdf/chapter_10.pdf);
- [Smithsonian Syrian-Armenian foodways](https://folklife.si.edu/magazine/forklife-food-and-longing-armenian-diaspora),
  [Jewish Food Society](https://www.jewishfoodsociety.org/recipes),
  [FOODISH at ANU Museum](https://foodish.anumuseum.org.il/en/) and
  [Asif](https://asif.org/en/recipes/) for bounded archive and family records;
- [National Library of Israel, Aleppo tradition](https://www.nli.org.il/he/discover/music/jewish-music/piyut/traditions/heleb-aleppo)
  and [Damascus tradition](https://www.nli.org.il/he/discover/music/jewish-music/piyut/articles/introductions/traditions/musical-tradition-of-damascus-jewry)
  for community context rather than recipe evidence;
- [FDA water activity guidance](https://www.fda.gov/inspections-compliance-enforcement-and-criminal-investigations/inspection-technical-guides/water-activity-aw-foods),
  [FoodSafety.gov safe temperatures](https://www.foodsafety.gov/food-safety-charts/safe-minimum-internal-temperatures),
  [WHO household air pollution guidance](https://www.who.int/en/news-room/fact-sheets/detail/household-air-pollution-and-health)
  and [USDA raw-poultry washing guidance](https://ask.fsis.usda.gov/article/Should-I-wash-chicken-or-other-poultry-before-cooking)
  for fail-closed process boundaries;
- [Arum food-use review](https://pmc.ncbi.nlm.nih.gov/articles/PMC11859539/)
  and [Arum poisoning evidence](https://pubmed.ncbi.nlm.nih.gov/32296984/)
  for the held Qadmus records.

## Release acceptance

Before release, require exact membership and type counts for all three modules,
unique IDs and prompts, resolvable parents and relations, recognized source and
evidence vocabularies, four exact held records, no public projection, no prices,
no stock, no supplier claims, no new roles and no payment activation. The full
registry, Entity Studio, package, PHP syntax, secret scan and bilingual public
routes must pass. After deployment, require anonymous health readback for
version 1.16.0, the exact package and source digests, 36 public products, payment
disabled, clean robots and sitemap output, and desktop and mobile Chrome
acceptance in Hebrew and English.
