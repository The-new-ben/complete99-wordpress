# Editorial and SEO governance

## Public audience

The public Complete99 website serves culinary consumers only. Public content may
help a visitor:

- understand a dish;
- explore an ingredient or cooking tradition;
- use a practical food guide;
- find verified restaurant contact information;
- continue to the current external ordering menu;
- evaluate and buy a real pantry product after commerce approval.

Institutional services, proposals, workers, suppliers, inventory operations,
costs, campaigns and operating-system capabilities are private. They must not
appear in public navigation, page copy, structured data, sitemaps or keyword
ownership.

## Keyword ownership

`plugin/complete99-platform/data/keyword-ownership.csv` is the launch ownership
contract. Each Hebrew and English primary intent has one canonical path,
secondary topics, forbidden competitors, an evidence gate and a publication
state. Administrators can inspect the same registry under
**Tools -> Complete99 SEO ownership**.

Before adding an indexable page:

1. Name the consumer question or decision the page alone will own.
2. Confirm that no existing canonical page owns the same intent.
3. Add or update the bilingual ownership rows before drafting.
4. Define the evidence, image-rights and schema gates.
5. Link the page from its real parent hub.
6. Keep thin, overlapping or unverified work as draft or `noindex`.

Intent boundaries:

- A dish dossier owns the identity and verified context of one dish, plus one
  primary tested recipe when approved.
- A standalone recipe exists only for a genuinely different preparation intent.
- An ingredient page owns ingredient identity, ordinary uses and storage.
- A tradition page owns carefully sourced cultural and historical context.
- A guide owns a practical culinary task that is not already answered by a dish
  or ingredient page.
- A verified location page owns current restaurant facts for one real location.
- A WooCommerce product page owns one real SKU or variation family.
- Editorial pages may support product discovery but must not duplicate a
  product page's purchase intent.

Utility views such as print, scaling and cook mode canonicalize to their owning
content page. Empty taxonomies, search utilities, private records and held store
surfaces stay outside the sitemap.

## Editorial workflow

1. Define the primary consumer intent and bilingual page pair.
2. Log sources and claim-level support.
3. Draft in the source language without filler or unsupported certainty.
4. Complete kitchen testing when a recipe is included.
5. Review allergens and cross-contact wording.
6. Review nutrition language when relevant. Do not make medical claims.
7. Clear every image and record its rights and allowed uses.
8. Complete Hebrew editing and English localisation by meaning, not literal
   substitution.
9. Check internal links, canonical, reciprocal `hreflang`, index state and
   structured data.
10. Record the responsible reviewer and review date.
11. Publish only after the visible page and its evidence agree.

The verification state is a control, not decoration. Automated word count,
template completion or writing-detector results cannot make content publishable.

## Dish research record

Every publishable dish dossier needs:

- names and meaningful variants;
- documented communities, regions and periods where those claims are made;
- source URLs or bibliographic references with access dates;
- consent for any family interview or personal account;
- ingredient story and ordinary preparation context;
- a repeatable recipe test when a recipe is published;
- private production specifications, yield and loss kept in Complete99 OS;
- allergen and cross-contact review;
- a source for any kosher claim;
- qualified review before health or suitability language;
- rights-cleared images, dimensions, captions and alt text;
- Hebrew and English editorial review;
- author, tester, editor, reviewer and review dates.

The existing bilingual long-form threshold remains a minimum gate. Each language
record also needs at least eight credible source URLs, including two
authoritative or primary sources, complete tested recipe fields where
applicable, and recorded kitchen, allergen, image, originality and bilingual
reviews. Repetition, generic prose and invented history fail review regardless
of length.

When sources disagree, show the uncertainty. Distinguish documented evidence,
oral tradition, adaptation and house method.

## Product editorial rules

A product page cannot publish as purchasable until the product is real and every
required commercial fact is verified. The visible Hebrew and English content
must agree with WooCommerce on:

- name, SKU and variation;
- price and currency;
- current purchasability;
- ingredients and allergen statement;
- weight and package facts;
- storage;
- delivery or pickup terms;
- images and rights;
- tax treatment and consumer policies.

Generated packaging concepts are not product media. Archive food photographs
cannot prove a product, package, current presentation or availability.

## Structured data

The public foundation may emit `Organization`, `WebSite`, `WebPage` and
`BreadcrumbList` only for visible, verified facts.

`Recipe` may appear only when the published dossier contains the same approved
ingredients, steps, yield, image and source facts. `Product` and `Offer` may
appear only after the store gate passes and their price, currency, availability
and product identity exactly match the visible page and WooCommerce.

Do not emit `Service` or `WebApplication` for the consumer website. Do not
generate ratings, reviews, clients, certifications, prices, offers, nutrition
values or availability from placeholders.

## Continuing review

Review public facts when the menu, location, legal copy, product, fulfilment
method or source changes. Expired signed menu items stop rendering as current.
Any product or policy change invalidates the relevant commerce acceptance until
it is checked again.
