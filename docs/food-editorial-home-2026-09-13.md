# Food editorial homepage implementation

Status: local implementation, not deployed. Branch: feat/food-editorial-experience-20260913. Baseline: ee7d8499927b8d69b166336a453f5d37a9e21ee5.

## Owner direction

Complete99 is a broad food discovery, ordering and knowledge product, not a redesign limited to sabich. Prepared meals and group orders lead the commercial journey. Preserve existing URLs, content ownership, canonical/hreflang, indexing and privacy settings. Awards and rankings are targets, not achieved claims. This homepage is one delivery slice, not completion of the full goal.

## Implementation map

- `includes/class-complete99-consumer.php`: existing homepage dispatch includes the new view. The superseded renderer is recoverable in Git, not shipped as unused code.
- `includes/views/food-home.php`: actual bilingual WordPress renderer, marker `food-editorial-v2`; food cover, occasion paths, real menu search, group enquiry, editorial hubs, location.
- `assets/css/consumer.css`: homepage-scoped editorial layer; cream, tomato red and green; desktop and mobile layouts.
- `assets/js/public.js`: menu search combines text and existing facets; homepage interactions do not create query URLs. Existing non-home filter behavior is preserved.
- `assets/images/editorial/`: generated conceptual food-cover image only. Existing menu photographs remain attached to their original dishes.
- `tests/fixtures/food-home-render.php`: local PHP fixture uses the actual renderer, header, footer and JS. It is not a WordPress environment and does not prove live CMS or form behavior.
- `tests/test_food_portal_home_contracts.py`: tests the included view rather than dead legacy markup.
- `tests/food-home-search.test.cjs`: executes shipped filter code; Hebrew marks, English case, combined filters, empty/reset, keyboard navigation and URL isolation.

## Focused competitor observations

Research inspected on 2026-09-13. Patterns were adapted, not copied assets or wording.

- https://www.delitlv.co.il/ and https://www.delitlv.co.il/deli-food/deli-food-meals.html: food photography, familiar meal occasions and direct category language. https://www.delitlv.co.il/deli_b2b separates the group/buyer path.
- https://ottolenghi.co.uk/ and https://ottolenghi.co.uk/pages/foodipedia: editorial imagery and connections between recipes, ingredients and commerce.
- https://www.farmerj.com/: short energetic headlines and direct menu/visit actions.

These are focused observations, not a completed market-wide assessment. No competitor technology stack was established.

## Asset provenance

`c99-shared-table-v01.webp` (1536x1024, 450032 bytes) and `c99-shared-table-v01-768.webp` (768x512, 111230 bytes) are optimized derivatives of one generated editorial still life. They are not photographs of products supplied by Complete99 and must not become product evidence or exact menu photographs.

Original is retained privately at `C:/Users/777/Downloads/codexmanager/complete99-editorial-shared-table-v01.png`. Generated through the built-in image tool on 2026-09-13, with no new external subscription. Prompt: a high-end editorial shared lunch on ivory linen with couscous, beet soup, roasted aubergine, chopped salad, pita and tahini; natural Tel Aviv daylight, no text, logos, people or watermarks; explicitly conceptual, not food offered for sale. Only format conversion/resizing was applied after generation.

## Observed acceptance

- Actual Chrome local Hebrew homepage at 1440x1000 and 390x844: screenshots displayed in chat, measured horizontal overflow false. Mobile open menu also measured overflow false; Escape closed it.
- Hebrew search for קובה returns one correct dish; pots returns three. URL remains clean.
- 47 focused Python contracts passed; Node executable search checks passed; PHP lint and JavaScript syntax checks passed.
- English source rendering tested. English visual acceptance, all image loading, target geometry, live route crawl, form acceptance and production screenshots remain to complete.

## Release blocker and continuation

Recovery-only production run 34765512228 failed with HTTP 409 `c99_candidate_resume_unproven_drift`. The prior activation has no exact committed checkpoint for the current database. Its temporary snippets 266 and 267 were independently removed. Do not force-clear locks, overwrite the state, restore an old database, or rerun blindly. Preserve all recovery journals and unrelated changes. The previous observed live runtime is 1.22.1; neither 1.23.1 nor this new homepage has been verified live.

Next: finish visual acceptance, package/review the new implementation separately, then resolve the exact pending recovery state through the existing reviewed deployment path. Do not report screenshots of localhost as a live redesign. Mapping, street/business discovery, broader content and SEO remain separate unfinished parts of the active goal.
