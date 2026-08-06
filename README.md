# Complete99 WordPress consumer site

This repository delivers the bilingual public Complete99 culinary website and
its WooCommerce catalog. The public website addresses culinary consumers only.
It presents dishes, ingredients, food traditions, practical knowledge, the
restaurant, a consumer store, an editable cart and current ordering paths.

Workers, inventory controls, suppliers, costs, campaigns, operating dashboards,
credentials and device controls belong in the private Complete99 OS. They are
not public website content or public search targets.

## Release target

The source tree targets Complete99 Platform `1.9.0`. Production truth comes
from the public health response, installed plugin digest, deployment audit and
fresh Chrome acceptance, not from a local version string alone.

Release 1.9.0 expands the approved WooCommerce catalog to exactly 36 products.
It adds Uozu Koshihikari rice, Hishiroku dried rice koji, Hishiroku Chouhaku-kin
koji starter and one 50 to 60 gram Dutch-grown fresh wasabi rhizome. Each offer
has a dated source-market observation, a current Bank of Israel conversion when
needed, an owner-authorized opening retail price, one unit of opening stock, no
backorders, an original unbranded public image and reciprocal science and
commercial links. Public prices are not presented as supplier costs. Landed
cost and gross margin remain unset until invoices, freight, tax and handling
evidence exist. Every payment gateway remains disabled.

Release 1.9.0 also expands the Japanese culinary-science registry to 73 bounded
entities. Twenty-two public entities are projected through 17 canonical page
owners per language, for 34 distinct Hebrew and English routes. Shared page
ownership prevents closely related entities from competing for the same search
intent. The current pages remain `noindex,follow` and outside the sitemap until
the separate long-form editorial and search-intent gate passes.

Previous release 1.8.0 introduced the integrity-only public read-model
boundary. The server derives the stored read-model SHA-256 from a
recursive canonical representation that excludes only the top-level `digest`
field, preserves list order and sorts associative keys at every depth. Public
freshness requires the stored digest to match that causal digest through
`hash_equals`. Missing, malformed, arbitrary or content-mismatched digests fail
closed, so public consumers use the approved packaged menu instead of trusting
the stored model. Health reports a digest, version and generation time only
when the complete transport envelope passes its shape and causal hash checks.
It never invents a replacement.

Previous release 1.7.0 introduced the modular culinary-science registry v5 and the
bilingual Japanese Foundations Lab. The Lab is a curated topic collection over
reviewed ingredient, food-science, technique and equipment entities. It owns
its narrow discovery intent without changing the canonical ownership of its
members. Public projections fail closed against supplier, cost, margin,
inventory-control and other private operational records. The Lab remains
noindex while its long-form editorial and search-intent acceptance is pending.

Release 1.6.1 is the live-acceptance accessibility hotfix for the store. It
expands the consumer breadcrumb and cart-status links to the project's strict
44 by 44 CSS-pixel target while preserving the 1.6.0 catalog and science data.

Release 1.6.0 extends the consumer store and Japanese science pilot with a
complete fresh-wasabi vertical slice. It adds typed food and equipment product
contracts, two owner-authorized WooCommerce offers, source-led pricing,
dedicated imagery and bilingual science routes. It provides:

- Hebrew at the root and a mirrored English hierarchy under `/en/`;
- exactly 12 public dish records and reciprocal bilingual dish pages;
- exactly 36 owner-authorized WooCommerce culinary products, including food
  ingredients and professional preparation equipment;
- one normal public WebP image for every product, without an archive label,
  disclaimer or unusual public treatment;
- researched price evidence dated between 2026-07-31 and 2026-08-06 and an
  owner-authorized opening retail price for every product;
- one unit of opening stock for every newly created product, managed by
  WooCommerce with backorders disabled;
- durable product, price, image, taxonomy and dish-relation receipts;
- a public bilingual store with filters, food-property badges, product links
  from dishes and ingredients, and an editable classic WooCommerce cart;
- visually exact pantry filters whose visible cards, result count and URL state
  remain synchronized;
- catalog and stock receipt identity that remains stable when a customer changes
  the cart language or removes an item;
- local pickup from 99 Ibn Gabirol Street in Tel Aviv;
- a signed private inventory synchronization route usable by Complete99 OS as
  soon as the catalog is verified;
- a bilingual, source-bound culinary-science graph with 73 Japanese-pilot,
  institutional, market, technique, ingredient, equipment and evidence entities;
- a modular commerce graph that separates knowledge, products, variants, SKUs,
  source-market observations, supplier offers, channel offers, landed cost,
  margin and bundles;
- administrator-only review endpoints and a signed, vendor-neutral POS catalog
  projection that publishes only fully approved active offers;
- a bilingual, projection-only Culinary Science Museum preview with exact
  canonical routes, breadcrumbs, citations, generated AVIF/WebP assets and
  source-market observations for the first reviewed Japanese entities;
- an explicit `noindex,follow` gate for the museum preview until long-form
  editorial review is approved, with sitemap exclusion enforced independently;
- reciprocal store and science links for fresh Japanese wasabi, its AITC
  chemistry, preparation guide and professional grater, using stable
  product-code anchors and safe public projections;
- 44 by 44 CSS-pixel targets for museum breadcrumbs and evidence citations;
- deterministic deployment, exact package verification, fail-closed recovery
  markers and independent live acceptance.

## Commerce boundary

WooCommerce `10.9.4` is installed as a separately pinned dependency from the
official WordPress package. The deployment verifies the package SHA-256, the
complete installed file tree, runtime version, REST runtime, catalog receipt
and exact gateway state.

The product catalog and cart are separate from electronic payment readiness.
Products can be discovered, filtered and added to the cart while electronic
checkout remains closed. The cart continues to the verified telephone order
path. No payment gateway is installed, enabled or configured by this release.
Electronic payment can be opened only after provider credentials, exact
supplier-label and country-of-origin records, and live checkout acceptance
evidence are supplied.

The public store is the curated consumer catalog. Native WooCommerce shop,
product and taxonomy URLs redirect to it so the website does not create a
second competing catalog. The native WooCommerce Shop page remains a separate,
non-indexed system page to avoid redirect loops.

## Inventory boundary

WooCommerce owns live stock. Complete99 OS can write an exact product quantity
through the signed, replay-protected private inventory route. Product bindings
are allowlisted by canonical product code, versions are monotonic, replay is
checked against current quantity and stock status, and payment readiness is not
required for inventory control.

## Local verification

```powershell
python scripts/lint-php.py
python -m pytest -q
python scripts/secret-scan.py
python scripts/build-plugin-zip.py --verify-reproducible
python scripts/validate-package.py --dist plugin-dist
```

The canonical builder creates a deterministic package under `plugin-dist/`.
`complete99-platform.json` is the update manifest.
`complete99-platform-integrity.json` records the immutable artifact name,
version, size, source digest and artifact digest. The ZIP that passes CI is the
ZIP that production must install.

## Production release contract

Production deployment requires:

- the exact protected `main` commit and a successful CI run for that commit;
- the GitHub `production` environment and exact allowed production host;
- the dedicated WordPress deployment administrator and site-specific
  Application Password;
- `WP_PRODUCTION_READY` set exactly to `true`;
- the scoped `complete99-deploy` Windows runner;
- health, package, source, database, cache, cleanup and rollback evidence;
- exact WooCommerce `10.9.4` package and installed-tree verification;
- a dry run, one owner-authenticated catalog apply and fresh strict readback;
- proof that all payment gateways remain disabled before and after the catalog
  apply;
- permanent deletion of every temporary deployment snippet and proof that its
  route returns `404`.

Credentials never belong in the repository, package, screenshots or audit
bundle.

## Editorial and evidence rules

Public copy states consumer facts only. It does not expose project language,
deployment state or private operations. It does not invent GTINs,
certifications, kosher status, nutrition panels, social accounts or supplier
pack facts. The public catalog uses owner-authorized generic ingredient and
allergen copy. Exact supplier-label and country-of-origin review remain
separate internal checkout gates until product-specific records are supplied.

Selected product images are published as ordinary catalog media. Internal
source and rights receipts remain private evidence and do not create a public
label or disclaimer.

## Documentation

- [Architecture](docs/architecture.md)
- [Complete99 operating brief](docs/complete99-operating-brief.md)
- [Consumer site and commerce runbook](docs/consumer-commerce-runbook.md)
- [Editorial and SEO governance](docs/editorial-governance.md)
- [Security and privacy](docs/security-and-privacy.md)
- [Launch QA](docs/launch-qa.md)
- [Deployment runbook](docs/deployment-runbook.md)
- [Emergency recovery](docs/recovery.md)
