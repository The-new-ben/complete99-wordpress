# Iraqi regional and community cuisine foundation

Date: 2026-08-07
Release target: Complete99 Platform 1.15.0

## Outcome

Release 1.15.0 adds a private, bilingual and source-bound Iraqi culinary
foundation to the Complete99 knowledge graph. It expands culinary science from
369 to 465 identities and Entity Studio from 425 to 521 subjects. It does not
change the public website, public science projection, WooCommerce catalog,
prices, stock, cart, payment state, POS projection or user roles.

| Invariant | Release 1.15.0 value |
| --- | ---: |
| Culinary-science registry | `culinary-science-2026.08.07.v15` |
| Culinary-commerce registry | `culinary-commerce-2026.08.07.v9` |
| Science identities | 465 |
| Iraqi identities | 96 |
| Product identities | 56 |
| Entity Studio subjects | 521 |
| Public science identities | 23 |
| Public page owners per language | 18 |
| Public WooCommerce products | 36 |
| New Iraqi offers or price observations | 0 |

## Exact Iraqi identity distribution

- 1 cuisine root
- 16 regional or topic hubs
- 32 dishes
- 4 preparations
- 12 ingredients
- 8 techniques
- 10 traditions
- 5 culinary institutions
- 3 markets
- 2 restaurants
- 1 trade-compliance rule
- 2 guides

All 96 identities are `editorial_draft`, `noindex_private`, `private_preview`,
private-route and `reference_only`. Each has bilingual consumer-readable
editorial copy, structured taxonomy, source-bound facts, source-bound relations
and a private studio-photography prompt. A prompt is a private asset
specification, not a rights receipt or permission to publish an image.

## Regional model

The graph does not present one uniform Iraqi formula. It keeps the following
regional paths separate:

- Baghdad
- Mosul and Ninewa
- Basra and the Shatt al-Arab
- the Middle Euphrates, including Najaf and Karbala contexts
- the southern marshes
- Iraqi Kurdistan
- Kirkuk and Diyala

Each fact remains within the place, institution, community, family or study
that documented it. Regional paths can compare one another without overwriting
identity, authorship or provenance.

## Community scope

The foundation includes Iraqi Jewish foodways because they are an important
part of the historical and living record. It also covers the broader Iraqi
table, Kurdish contexts, Marsh Arab contexts, Muslim ritual hospitality,
Christian and other community contexts when supported by the retained source.
No community record is presented as a substitute for Iraqi cuisine as a whole.

Family records stay family-scoped. They may support a family variation, memory
or occasion, but do not establish exclusive origin for a shared dish. This is
especially important for kubba, dolma, yaprakh, biryani, qeema, tannour bread,
turshi, basturma, kebab, kofta and sayadiyah.

Sabich keeps its existing Complete99 public identity. The Iraqi foundation adds
historical breakfast context without creating a competing sabich page. Amba
links to the existing `ingredient-amba` identity and is not duplicated as a
second product or ingredient owner.

## Scientific and food-safety gates

The research graph records scientific context without turning an unvalidated
traditional description into an operating instruction. The following controls
fail closed:

- freshwater fish requires verified species, cold chain, parasite and pathogen
  controls, clean water, cooking validation and open-fire ventilation review;
- dried fish requires water-activity, salt, oxidation, biogenic-amine,
  microbiology, packaging and storage validation;
- cooked rice requires time and temperature controls for cooling, hot holding,
  refrigeration and reheating because of spore-forming hazards;
- pacha and other offal require approved sourcing, cold chain, cleaning,
  validated cooking and cross-contamination controls;
- overnight-cooked and held foods require documented time and temperature
  validation and cannot rely on tradition as proof of safety;
- turshi and other fermented vegetables require measured pH, salt, water
  activity, microbiology and packaging controls;
- geymar and cultured dairy require pasteurized milk and a validated cold-chain
  and microbiological process;
- date syrup requires measured soluble solids, water activity, thermal process,
  packaging and shelf-life validation;
- open-fire techniques require heat, smoke, ventilation, burn and polycyclic
  aromatic hydrocarbon controls;
- wild-plant knowledge is cultural evidence, not permission to forage,
  self-identify or consume an unverified plant.

No page makes a disease-treatment claim. Nutrient, molecular and health context
must remain within the limits of the cited evidence and the actual food matrix.

## Trade and commerce boundary

Every Iraqi identity references the central private
`compliance-iraq-trade-israel-2026` control. The current graph is research only.
It creates no supplier contact, sample, order, payment, third-country purchase,
availability promise, stock record, price, WooCommerce offer or POS row.

The control is fail closed. Commercial action requires current written official
authorization and a separate legal, supplier, label, origin, landed-cost,
food-safety and acceptance review. A cultural institution, market, restaurant
or external source is not treated as a supplier merely because it is mapped.
This operating control is not legal advice and does not assert that an
exception exists.

## SEO and route boundary

The Iraqi root owns the private cluster
`cluster-iraqi-regional-cuisine`. Regional, dish, ingredient, technique,
tradition, institution and guide identities inherit one deterministic parent
chain. The same chain produces canonical candidates, breadcrumbs and internal
link context.

All Iraqi routes remain private and absent from public APIs, WordPress search,
robots discovery, managed sitemaps and structured-data offers. Public search
activation requires a later bilingual long-form editorial, source, media,
search-intent, rights and Chrome acceptance gate. The present release is a
private knowledge and editorial foundation only.

## Release acceptance

Before deployment, require:

1. exact 96-identity membership and type counts;
2. full registry validation with no duplicate source, entity or observation ID;
3. every Iraqi identity private, noindex and reference-only;
4. every fact and relation bound to retained source IDs;
5. every identity linked to the central trade rule;
6. shared-family comparison without identity merging or exclusive-origin copy;
7. explicit safety gates for the risk families listed above;
8. no Iraqi retail listing, price, product code, offer, stock, supplier or POS
   row;
9. exact unchanged public counts of 36 store products, 23 public science
   identities and 18 page owners per language;
10. reproducible package, PHP lint, contract tests, secret scan and no em dash.

After deployment, require anonymous health readback for version 1.15.0, exact
registry counts, negative public evidence for Iraqi identities, unchanged store
and cart behavior, disabled payment state, real Chrome acceptance in Hebrew and
English, and complete bridge cleanup with rollback evidence.
