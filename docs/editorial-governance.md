# Editorial and SEO governance

## Keyword ownership

`plugin/complete99-platform/data/keyword-ownership.csv` is the launch ownership
contract. Each Hebrew and English primary intent has one canonical path, a list of
secondary queries, pages prohibited from competing, an evidence gate and a
publication state. Administrators can inspect the same registry under
**Tools → Complete99 SEO ownership**.

Before adding a page:

1. identify the unique user decision it owns;
2. confirm no existing primary intent matches it;
3. add the ownership row before drafting;
4. define the evidence gate;
5. link from the owning hub without repeating its primary target.

Services answer “what and how.” Industries answer “for whom and under which
constraints.” Dish pages own the public history plus tested home recipe. The private
BOM is never indexed.

## Editorial workflow

Draft → sources logged → kitchen test → allergen review → nutrition/compliance review
when relevant → Hebrew edit → English localisation → schema/SEO QA → publication.

The page’s verification state is an editorial control, not decoration. “Verified”
means the visible content, underlying sources and structured data agree.

## Dish research record

Every dish file must eventually contain:

- names and variants;
- documented communities, regions and periods;
- source URLs or bibliographic references and access dates;
- a consented family interview when used;
- ingredient story;
- repeatable public recipe test;
- private BOM version, yield and loss in Complete99 OS;
- allergen review;
- kosher claim source when any kosher claim is made;
- qualified nutrition review before health or suitability language;
- original owned images and rights record;
- reviewer and last-reviewed date.

Publication is blocked unless both the Hebrew and English article contain at least
5,000 substantive words, each language record carries at least eight credible source
URLs including two authoritative/primary sources, the tested recipe fields are
complete, and kitchen, allergen, image, originality and bilingual editorial reviews
are recorded. Automated word count is a floor, not a quality score; repeated or padded
copy still fails editorial review.

Do not backfill unknown history with plausible prose. Parallel traditions should be
presented as parallel, with the uncertainty visible.

## Structured data

Organization, Service, WebApplication and Recipe markup must describe visible facts.
No ratings, clients, certifications, addresses, prices, offers or nutrition values
are generated from placeholders. Recipe schema stays off until sources, ingredients,
steps and yield are present and the dish is marked verified.
