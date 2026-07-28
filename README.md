# Complete99 WordPress foundation

This directory is a self-contained WordPress delivery repository. It keeps the public
growth/content surface on WordPress while the operational application remains on its
existing Sites runtime:

- **WordPress:** bilingual institutional pages, public culinary knowledge, forms,
  structured data, editorial governance and the safe public read model.
- **Complete99 OS:** role-aware operations, recipes/BOM, inventory, campaigns,
  files and future device adapters.
- **Boundary:** an HMAC-signed, replay-protected public read-model sync. WordPress
  never receives private BOM costs, raw video, credentials or control commands.

## What is ready

- Plugin `complete99-platform` version `1.0.2`.
- Nine governed content types, seven taxonomies and four scoped editor roles.
- Twenty-two bilingual launch concepts (40 public records and four private
  sector records held back from launch).
- Six bilingual dish research files created as drafts with explicit verification gates.
- Hebrew root and deterministic English `/en/` hierarchy.
- Premium accessible RTL/LTR shell with the operations command centre and campaign
  studio visible in the first homepage sections.
- Truthful Organization, WebSite, WebPage, Breadcrumb, Service and WebApplication
  JSON-LD. Recipe JSON-LD is emitted only after the record is marked verified and
  contains a tested recipe and source URLs.
- Local private enquiry storage; no automatic email or marketing send.
- Admin-only Sites URL, owned-asset URL and sync-secret settings.
- Signed public read-model sync plus public filtered catalog endpoint.
- Machine-readable keyword ownership CSV and an administrator report under
  **Tools -> Complete99 SEO ownership**.
- Vendored Plugin Update Checker 5.6 (`v5p6`) supplies a guarded wp-admin
  update fallback from the public GitHub release manifest.
- Deterministic ZIP, source/artifact SHA-256 metadata, PHP lint, secret-safe package
  scanning, pinned GitHub Actions, exact-commit CI admission, temporary deployment
  bridge, encrypted database journal, atomic file rollback, interrupted-request
  recovery, independent health/body checks and cleanup-404 proof.

## Local verification

```powershell
python scripts/lint-php.py
python -m unittest discover -s tests -v
python scripts/secret-scan.py
python scripts/build-plugin-zip.py --verify-reproducible
python scripts/validate-package.py
```

The release artifact is `plugin-dist/complete99-platform-1.0.2.zip`. The public
WordPress update manifest is `plugin-dist/complete99-platform.json`; immutable
artifact digest, size and deployment metadata live separately in
`plugin-dist/complete99-platform-integrity.json`.

The deployment driver has an explicit `--local-test` mode for a disposable,
loopback-only WordPress proof. It never accepts an UPress temporary/staging hostname;
production remains locked to HTTPS on the final Complete99 domain.

## Required production configuration

Read [deployment-runbook.md](docs/deployment-runbook.md) before connecting UPress.
The pipeline intentionally does not contain credentials. Production requires a
dedicated WordPress deployment administrator, a site-specific Application Password,
an enabled UPress REST API and the GitHub `production` environment secrets:

- `WP_BASE_URL`
- `WP_DEPLOY_USER`
- `WP_APP_PASSWORD`

The repository variable `WP_PRODUCTION_READY` must be exactly `true` before the
manual production workflow can start. Keep it `false` until the final domain,
TLS, REST access and dedicated deployment identity have all been independently
verified. The workflow also refuses to deploy unless this exact `main` commit has
a successful WordPress CI run.

Deployment waits for the purchased final `complete99.co.il` domain; no UPress staging
or temporary public site is created. After activation, configure
**Settings -> Complete99 Platform**. The default app and
owned-asset origin is the existing Sites deployment
`https://complete99-os.benben777.chatgpt.site`; verify it in the live account before
publishing.

## Evidence boundaries

Seed text does not claim a customer, capacity, price, kosher status, licence,
certification, health outcome or external connector. Education and senior-living
records stay private. Dish content cannot publish until the bilingual long-form,
source, kitchen, allergen, image and editorial gates pass.

## Documentation

- [Architecture](docs/architecture.md)
- [Deployment and canary runbook](docs/deployment-runbook.md)
- [Editorial and SEO governance](docs/editorial-governance.md)
- [Security, privacy and integration boundary](docs/security-and-privacy.md)
- [Launch QA](docs/launch-qa.md)
- [Pipeline acceptance evidence](docs/pipeline-acceptance-evidence.md)
- [Emergency recovery](docs/recovery.md)
