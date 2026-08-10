# Complete99 live search-visibility evidence

Observed: 2026-08-10 22:53:03-23:14:40, Asia/Jerusalem

Scope: public read-only HTTP, rendered Google result observation and local
WordPress source review. No Search Console property data was available because
the available browser was not authenticated to Google.

Post-audit release note: after this capture window, protected-main deployment
run `31429662234` completed successfully. Live health then reported plugin and
database 1.20.0 with deployment `c99-prod-31429662234-1`. The deployment
rollback exercise, identical-artifact redeploy, 36-product WooCommerce
readback, disabled-payment proof, cache flush and temporary-route cleanup all
passed. The visibility observations below retain their original capture-time
identity so the evidence remains reproducible.

## Direct live facts

| Surface | Result | Robots/index signal | Canonical |
|---|---:|---|---|
| `https://complete99.co.il/` | HTTP 200 | `max-image-preview:large`; no `noindex`; no `X-Robots-Tag` block | self |
| `https://complete99.co.il/store/` | HTTP 200 | `max-image-preview:large`; no `noindex` | self |
| `https://complete99.co.il/en/store/` | HTTP 200 | `max-image-preview:large`; no `noindex` | self |
| `/dishes/`, `/ingredients/`, `/knowledge/` | HTTP 200 | `max-image-preview:large`; no `noindex` | self |
| `/ingredients/kombu/` and EN mirror | HTTP 200 | `noindex, follow` | self |
| public health and store-status APIs | HTTP 200 | `X-Robots-Tag: noindex` | API, intentionally not a search page |

The site is therefore not globally `noindex`. An exact census of the current
WordPress sitemap found 48 of 48 URLs returning HTTP 200, 48 of 48
self-canonical, zero meta `noindex`, zero `X-Robots-Tag: noindex`, and no
redirect or error. The public home, store, dishes and main discovery hubs are
index-eligible. Culinary-science pages currently use an intentional `noindex,
follow` policy, and private/editorial/API surfaces are also excluded.

The live `robots.txt` is:

```text
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Sitemap: https://complete99.co.il/wp-sitemap.xml
```

It does not block the public site. The WordPress sitemap is HTTP 200 and
currently exposes two child sitemaps: pages and completed dishes. They contain
48 public URL entries in total: 24 bilingual pages and 24 bilingual dish URLs.
The common Yoast path `/sitemap_index.xml` is 404 because the site uses the
WordPress core sitemap at `/wp-sitemap.xml`.

The live HTML identifies WordPress 7.0.3, WooCommerce 10.9.4 and the Twenty
Twenty-Five base theme, with the Complete99 presentation supplied by the
plugin. The live health response identifies release 1.18.2 and deployment
`c99-prod-31240348918-1`. The store status reports 36 products,
`catalog_ready=true`, `cart_ready=true` and `checkout_ready=false`. Payments
remain disabled.

The public HE/EN store pages say that the store is open and expose 36 priced,
stocked product cards and add-to-cart links across three indexable pages.
However, the Woo product, cart and checkout Store API calls return HTTP 503
with `complete99_commerce_held`, the health response says
`culinary_commerce_ready=false`, and `/checkout/` returns to `/store/`. This is
a material wording/runtime inconsistency: catalog discovery is open, but
on-site transaction and payment are not.

Normal browser-like and Googlebot request bodies were byte-identical for the
sampled HE/EN home, store, Sabich and museum pages. No cloaking or
crawler-specific directive difference was observed.

## Direct Google observation

Real rendered, signed-out Israeli Google searches in HE and EN returned no
documents for `site:complete99.co.il` or `site:complete99.co.il Complete99`.
The Hebrew result stated:

> Your search - site:complete99.co.il - did not match any documents.

This is direct evidence that Google returned no indexed results for the domain
in those observations. A `site:` query is not a complete substitute for Search
Console, but the zero-result observations are consistent with the owner's
report that the site cannot be found. The sitemap `lastmod` dates are
2026-08-08, about two days before this audit.

The available browser reached the Search Console public landing page and then
offered Google sign-in. It was not authenticated, so the private Page Indexing,
URL Inspection, manual-action, security and sitemap-submission records could
not be read. The owner authorization permits that inspection, but it does not
supply or fabricate a Google credential.

No `google-site-verification` meta token was present in the sampled pages, and
the observed DNS TXT record was SPF only. That does not prove that no Search
Console property exists; it means public evidence does not prove its ownership
or submission state.

## Competing public host

The legacy public preview at
`https://complete99-public.benben777.chatgpt.site/` remains HTTP 200,
index-eligible and self-canonical, and publishes a separate sitemap. Current
WordPress source and live HTML still reference that host for legacy application
and asset URLs; the live WordPress homepage still loads its OG/Twitter image
from that host. This is a competing public copy and an external runtime
dependency. A localized `site:` check also returned no indexed documents for
the legacy host, so this is a duplication/canonical risk rather than proof that
the legacy copy currently outranks the production domain.

The private legacy OS host requires ChatGPT/Sites identity and is not a
WordPress-native staff application. It must be replaced, not treated as the
finished migration.

## Evidence-based conclusion

Direct fact: the primary WordPress homepage, store and main hubs are not being
hidden by `noindex` or `robots.txt`.

Direct fact: Google returned zero `site:` results during the check.

Direct fact: a second index-eligible, self-canonical public host remains live,
while the production domain is recent and still references legacy-host assets.

Inference: the most likely discovery problem is incomplete search onboarding
combined with a competing legacy host, a very recent sitemap/site release and
weak discovery signals. This cannot be converted into a definitive Google
crawl reason until the domain property is inspected in Search Console.

Unknown until authenticated Search Console inspection: whether Google regards
the home URL as unknown, crawled-not-indexed, discovered-not-indexed, duplicate,
blocked by a manual/security action, temporarily removed or assigned a
different canonical.

## Corrective sequence

1. Finish the WordPress-owned application and asset cutover so the production
   site no longer depends on the ChatGPT Sites host.
2. Redirect the legacy public preview to exact production equivalents where a
   one-to-one owner exists; otherwise return a deliberate retired response. Do
   not leave it indexable and self-canonical.
3. Verify the `complete99.co.il` Search Console domain property, inspect the
   homepage live and indexed states, and review manual actions and security
   issues.
4. Submit `https://complete99.co.il/wp-sitemap.xml` in the Sitemaps report and
   record its Google fetch result.
5. Request indexing for the homepage and the small set of primary canonical
   hubs after the duplicate-host cutover. Do not mass-submit filters, APIs or
   private/editorial pages.
6. Keep the 12 held Syrian/Japanese candidates private. Review the 27 currently
   public-but-noindex science owners individually for long-form search approval
   instead of changing the whole registry with one switch.
7. Recheck exact URL Inspection states, indexed count, canonical selection and
   search impressions after Google has processed the submission.

## Saved proof

The read-only evidence bundle is stored outside the repository at:

`C:\Users\pro\Documents\websites\.codex-tmp\live-visibility-store-audit-2026-08-10`

It includes signed-out HE/EN Google screenshots, rendered HE/EN home, store,
dish, knowledge and museum screenshots, plus raw headers and bodies for robots,
sitemaps, health, Store API, museum and the competing host.

Google's official references:

- <https://support.google.com/webmasters/answer/9012289>
- <https://support.google.com/webmasters/answer/7451001>
- <https://support.google.com/webmasters/answer/7474347>
- <https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag>
