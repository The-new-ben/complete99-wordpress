# Science Museum search activation evidence

Date: 2026-08-11 (Asia/Jerusalem)

## Authorization and bounded decision

The owner explicitly instructed Complete99 to remove `noindex` from the Museum
and valuable public content and authorized the work. This record implements
that instruction as an owner search-policy activation, not as a rewrite of the
canonical culinary-science registry and not as blanket permission to expose
private, held, draft, section-only or unsafe material.

The approved result is exactly 18 standalone canonical owners and 36 bilingual
URLs. The activation is encoded in
`plugin/complete99-platform/data/culinary-science-search-activation.php` under
schema `complete99-culinary-science-search-activation/v1`, version
`culinary-science-search-activation-2026.08.11.v1`, with canonical policy
digest `0b191bef1612e56f2e97c1e4e5d15ab4f651d8e658e2eb742aea72cc2a2ac6e7`.

The policy is pinned to the unchanged canonical culinary-science registry:

- schema: `complete99-culinary-science-registry/v6`;
- version: `culinary-science-2026.08.08.v20`;
- canonical payload digest:
  `677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54`.

Every v20 `publication.search_index` value and `index_policy` value remains
unchanged. Effective search state is derived only after both the registry and
the separately pinned activation policy validate.

## Exact activated owners and routes

| Owner ID | Hebrew canonical | English canonical |
|---|---|---|
| `museum-culinary-science` | `/museum/` | `/en/museum/` |
| `cuisine-japanese-washoku` | `/museum/japanese-culinary-science/` | `/en/museum/japanese-culinary-science/` |
| `cuisine-lebanese-regional` | `/museum/lebanese-culinary-science/` | `/en/museum/lebanese-culinary-science/` |
| `cuisine-syrian-regional` | `/museum/syrian-culinary-science/` | `/en/museum/syrian-culinary-science/` |
| `hub-japanese-foundations-lab` | `/museum/japanese-culinary-science/foundations/` | `/en/museum/japanese-culinary-science/foundations/` |
| `ingredient-kombu` | `/ingredients/kombu/` | `/en/ingredients/kombu/` |
| `ingredient-katsuobushi` | `/ingredients/katsuobushi/` | `/en/ingredients/katsuobushi/` |
| `ingredient-kioke-shoyu` | `/ingredients/kioke-shoyu/` | `/en/ingredients/kioke-shoyu/` |
| `ingredient-kome-koji` | `/ingredients/kome-koji/` | `/en/ingredients/kome-koji/` |
| `ingredient-koji-starter-culture` | `/ingredients/koji-starter-culture/` | `/en/ingredients/koji-starter-culture/` |
| `ingredient-koshihikari-rice` | `/ingredients/koshihikari-rice/` | `/en/ingredients/koshihikari-rice/` |
| `ingredient-fresh-wasabi` | `/ingredients/fresh-wasabi-rhizome/` | `/en/ingredients/fresh-wasabi-rhizome/` |
| `ingredient-fresh-dutch-wasabi` | `/ingredients/dutch-grown-fresh-wasabi/` | `/en/ingredients/dutch-grown-fresh-wasabi/` |
| `ingredient-kito-yuzu` | `/ingredients/kito-yuzu/` | `/en/ingredients/kito-yuzu/` |
| `ingredient-hon-mirin` | `/ingredients/hon-mirin/` | `/en/ingredients/hon-mirin/` |
| `guide-umami-synergy` | `/knowledge/umami-synergy-glutamate-imp/` | `/en/knowledge/umami-synergy-glutamate-imp/` |
| `guide-wasabi-aitc` | `/knowledge/wasabi-aitc-pungency/` | `/en/knowledge/wasabi-aitc-pungency/` |
| `equipment-wasabi-grater` | `/knowledge/wasabi-grater-guide/` | `/en/knowledge/wasabi-grater-guide/` |

## Audit evidence and content-quality verdict

The pre-activation live census covered the exact 38 routes owned by the 19
approved-public standalone entities:

- 38 of 38 returned HTTP 200;
- 38 of 38 declared the exact self-canonical URL;
- 38 of 38 emitted Hebrew, English and `x-default` hreflang alternates;
- 38 of 38 rendered exactly one H1;
- 38 of 38 emitted JSON-LD;
- 38 of 38 were still `noindex, follow` before this activation;
- approximate rendered text ranged from 358 to 1,263 words. The Museum measured
  approximately 438 Hebrew words and 555 English words. The shortest observed
  page was the Hebrew Syrian regional owner at approximately 358 words.

Registry-level review found exactly 27 approved-public entities: 19 standalone
owners and eight section entities. Every approved-public entity has at least
one sourced public-safe fact, bilingual source review, an approved
rights-cleared asset receipt, organizational editorial attribution, a
correction path and a substantive update date.

Verdict: the 18 activated owners pass the current evidence, ownership,
localization, canonical, rights and standalone-content gates. They are not
filter results or duplicate section URLs. The lower-word-count regional page
is modest rather than empty: it has sourced facts, original bilingual framing,
structured data, a rights-cleared visual and useful internal navigation. It
should be expanded and measured, but the audit found no basis to keep the
qualified canonical owner hidden from search. This is an eligibility decision,
not a ranking guarantee.

## Exact exclusions

- `preparation-ichiban-dashi` remains public but `noindex, follow`; its
  `culinary_test_status` is `not_applicable`, so it fails the search activation
  culinary-test gate. Its two language routes do not enter the sitemap.
- All eight `route_mode=section` entities remain owner-canonical-only. They do
  not become standalone routes and do not enter the sitemap.
- All held, private and draft entities remain excluded or non-routable.
- Any query or filter state remains `noindex, follow`, even when its clean
  canonical owner is indexable.
- APIs and private editorial surfaces retain their existing noindex/access
  boundaries.

## Runtime and failure behavior

For an activated clean canonical request, the public projection and page bundle
derive `search_index=true`, `index_policy=index`, and WordPress robots
`index, follow` with large image previews. The custom WordPress sitemap provider
derives exactly the same 36 canonical URLs.

The activation fails closed. If the overlay is missing, altered, stale, bound
to a different registry digest, contains an extra owner, includes a section,
or substitutes the untested dashi preparation, the effective result is zero
indexable owners and zero sitemap URLs. Public pages continue to render but
remain `noindex, follow`. The migration invariant also fails, preventing a
release from claiming successful search activation when the overlay was not
packaged or validated.

## Verification contract

Focused automated evidence covers:

- exact v20 registry digest and unchanged raw noindex/search flags;
- exact policy digest, schema, version and authorization record;
- exact 18-owner / 36-route effective activation;
- 36 unique same-site sitemap URLs, each with `lastmod`, no query and no
  fragment;
- clean canonical robots `index, follow`;
- query/filter robots `noindex, follow`;
- dashi and section exclusion;
- missing/tampered overlay yielding zero effective routes;
- migration invariant rejection for missing/tampered overlays;
- PHP syntax validation for the policy and both runtime classes.

Post-deployment work remains operational rather than a code assumption: verify
the 36 live URLs and the WordPress sitemap after release, submit the sitemap in
authenticated Google Search Console, inspect canonical/indexing states, and
measure impressions and coverage at 7, 14, 28 and 56 days. Search Console
credentials were not available during the public audit, so no private Google
property result is claimed here.
