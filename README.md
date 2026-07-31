# Complete99 WordPress consumer site

This repository delivers the bilingual public Complete99 website and its
controlled commerce foundation. The public website addresses culinary consumers
only. It presents food, dishes, ingredients, traditions, practical guides, the
restaurant story, verified contact details, current ordering continuation and a
pantry that stays closed until real commerce is proven.

Institutional services, workers, inventory controls, suppliers, costs,
campaigns, operating dashboards, credentials and device controls belong in the
private Complete99 OS. They are not public website content or public search
targets.

## Release target

The source tree targets Complete99 Platform `1.3.0`. A source version, package or
successful installer response is not evidence that production is running that
release. Production truth comes from the public health response, the rendered
body, the installed plugin digest, the deployment audit and fresh Chrome
acceptance.

Release 1.3.0 provides:

- Hebrew at the root and a mirrored English hierarchy under `/en/`;
- a consumer-only header, footer, page hierarchy and SEO ownership registry;
- culinary pages for dishes, ingredients, traditions and practical knowledge;
- language-specific continuation to the verified Wolt restaurant menu;
- archive food imagery with explicit provenance and current-presentation limits;
- a held pantry with concept packaging imagery and no invented products;
- WooCommerce readiness gates, consumer policy acceptance, checkout acceptance
  from separate real Hebrew and English orders, and precommit launch staging;
- reviewed public-safe product media with file-level integrity, plus a private,
  bounded commerce outbox with recomputable event identities, per-code recovery
  state and no direct customer contact or address data;
- a signed and replay-protected public read-model sync with a 24-hour freshness
  limit;
- deterministic packaging, exact-commit CI admission, rollback recovery and
  independent live verification.

No Complete99 worker role is installed or assigned by this release. The dormant
role definitions remain infrastructure only.

## Commerce boundary

WooCommerce is the intended product, cart, checkout, payment and stock engine,
but it is a separate production dependency. The Complete99 deployment bridge is
restricted to the `complete99-platform` package and must not be broadened to
install WooCommerce.

The pantry remains held and `noindex` until real products, merchant facts,
payment, tax, shipping or pickup, returns, privacy, terms, support, checkout,
email, refund, inventory and security evidence all pass. Do not create sample
products, sample prices, sample stock or a pretend checkout on production.

The checkout receipt uses contract `complete99-commerce-acceptance/v3`. It
passes only after distinct real Hebrew and English orders independently prove a
live refund-capable gateway, order-correlated stock movement, customer
transaction messages whose final subject and body match the order language, and
complete fulfilment. Successful acceptance always returns the pantry to a
locked hold. Launch requires a separate authenticated endpoint call, a staged
readiness check and a verified cache purge.

See [Consumer site and commerce runbook](docs/consumer-commerce-runbook.md) for
the dependency candidate record, endpoint contracts, readiness sequence and
recovery requirements.

## Local verification

```powershell
python scripts/lint-php.py
python -m unittest discover -s tests -v
python scripts/secret-scan.py
python scripts/build-plugin-zip.py --verify-reproducible
python scripts/validate-package.py --dist plugin-dist
```

The canonical builder creates a deterministic package under `plugin-dist/`.
`complete99-platform.json` is the human update fallback manifest.
`complete99-platform-integrity.json` records the immutable artifact name,
version, size, source digest and artifact digest. The ZIP that passes CI is the
ZIP that production must install.

## Production release contract

Production deployment requires:

- the exact protected `main` commit;
- a successful WordPress CI run for that commit;
- the GitHub `production` environment;
- an exact allowed production host;
- a dedicated WordPress deployment administrator and site-specific Application
  Password;
- `WP_PRODUCTION_READY` set exactly to `true`;
- a 32 to 4096 character `COMPLETE99_WORDPRESS_SYNC_SECRET`;
- the scoped `complete99-deploy` Windows runner;
- a successful recovery probe, restorable plugin and database backups, a deploy
  lock and free-space checks;
- a real rollback and identical-artifact redeploy exercise for release `1.3.0`;
- health, rendered-body, installed-version, source, artifact, cache and cleanup
  evidence;
- permanent deletion of the temporary deployment helper and proof that its
  route returns `404`.

The transitional production alias is permitted only when
`WP_ALLOWED_DEPLOY_HOSTS` contains its exact hostname. Wildcards and other UPress
hosts are rejected. Credentials never belong in the repository, package,
screenshots or audit bundle.

Read [Deployment runbook](docs/deployment-runbook.md) and
[Emergency recovery](docs/recovery.md) before any live write.

## Editorial and evidence rules

Public copy may state only verified consumer facts. It must not invent a
customer, product, price, stock level, delivery promise, certification, kosher
status, health result, social account or integration.

Dish and knowledge records remain drafts until sources, kitchen testing,
allergen review, image rights, Hebrew editing, English localisation and schema
checks pass. Word count is a floor, never a reason to add filler.

Generated pantry imagery is a design concept, not a product photograph. It
cannot appear with a price, purchase control, Product schema or availability
claim.

## Documentation

- [Architecture](docs/architecture.md)
- [Consumer culinary and ecommerce benchmark](docs/consumer-culinary-ecommerce-benchmark-2026-07-29.md)
- [Consumer site and commerce runbook](docs/consumer-commerce-runbook.md)
- [Editorial and SEO governance](docs/editorial-governance.md)
- [Security and privacy](docs/security-and-privacy.md)
- [Launch QA](docs/launch-qa.md)
- [Deployment runbook](docs/deployment-runbook.md)
- [Historical 1.2.1 pipeline acceptance evidence](docs/pipeline-acceptance-evidence.md)
- [Emergency recovery](docs/recovery.md)
