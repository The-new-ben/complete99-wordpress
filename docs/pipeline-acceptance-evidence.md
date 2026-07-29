# Pipeline acceptance evidence

## Version 1.2.1 production acceptance

Date: 2026-07-29

Release: `complete99-platform` 1.2.1

Production source commit:
`20f455fd0c9505d925efb82aacc69bace9308172`

- Release PR:
  <https://github.com/The-new-ben/complete99-wordpress/pull/14>
- Release artifact: `complete99-platform-1.2.1.zip`
- Artifact SHA-256:
  `cd1eabced6e29383cd20ec760649feda07d5a78c58f0b3d93ee6a2e9742b46be`
- Packaged-source SHA-256:
  `33a4aca82ef95c6298391d735bde706960d3501579ea1434ead07c33bac6c5b8`
- Installed-plugin SHA-256:
  `0b46aaa86882c3722b5f1aa2151e3931437bd49de9683a059ee589029aa2f995`
- Artifact size: 2,719,567 bytes
- Independent archive validation: 162 entries

### Source and admission gates

Acceptance completed against the exact release source:

- 97 contract tests passed; the one anonymous public-host test remained
  intentionally skipped because `COMPLETE99_VERIFY_PUBLIC_URLS=1` was not set;
- source PHP lint passed;
- PHP lint passed for all 56 PHP files extracted from the exact release ZIP;
- source and package secret scans passed;
- two clean package builds were byte-for-byte identical;
- package shape, update metadata, integrity metadata, version and deployment ID
  agreed;
- working-tree whitespace validation passed.

The release adds a Complete99-owned bilingual 404 for unknown live-dish URLs.
The Hebrew response renders `he`/`rtl`; the English response renders `en`/`ltr`.
Both keep HTTP 404, declare `noindex`, omit canonical, and provide real
continuation links to the dish library and home. CI now also lints every PHP
file inside the exact checked-in release ZIP before admitting it.

Protected-main validation admitted the exact source commit before production
deployment:
<https://github.com/The-new-ben/complete99-wordpress/actions/runs/30417568767>

### Production deployment

- Current live base: <https://a235232-tmp.s1242.upress.link>
- Deployment ID: `c99-prod-30417601615-1`
- Deployment run:
  <https://github.com/The-new-ben/complete99-wordpress/actions/runs/30417601615>
- Production deployment completed at `2026-07-29T02:44:49Z`.
- Final health reported component `complete99-platform`, status `ok`, version
  `1.2.1`, database version `1.2.1`, sync configured, and the exact deployment
  ID.
- The deployed plugin tree matched installed-plugin SHA-256
  `0b46aaa86882c3722b5f1aa2151e3931437bd49de9683a059ee589029aa2f995`.
- The final rendered-home probe reported version `1.2.1` and the exact
  deployment ID.
- The final `robots.txt` probe returned HTTP 200 with SHA-256
  `e8b68b16cc0b5d15bf5f626b3881c20db37f566057c321c0ef0c610eb1b21040`.
- The temporary deployment snippet was inactive and permanently deleted; its
  row was absent and its temporary route returned 404.
- Finalization released the deployment lock and removed deployment state.

This patch did not repeat the rollback exercise. The immediately preceding
1.2.0 production release exercised restoration and redeployment successfully;
1.2.1 used the same fail-closed pipeline and independently passed recovery
probe, dry-run acceptance, install verification, cleanup and readback.

### Application-to-WordPress readback

After the 1.2.1 deployment, the private Complete99 OS connector independently
verified health and then performed a new signed sync with exact public
readback:

- read-model version: `complete99-os-v10`;
- read-model updated at: `2026-07-29T02:50:05+00:00`;
- freshness: `true`;
- public item count: `0`;
- public branch count: one, exact branch ID `store-99`, published;
- public campaign count: `0`;
- model digest:
  `c5dc6c34b9090382c4b99e343ca2ef50d5b1c699dcde1cef1ef9f61b4671d681`;
- exact public-catalog body/readback SHA-256:
  `de0bf7c9368fa655c3de1719a492f4f3e80ce8ca7f13691f5718f0843e981691`.

The connector did not change availability. Zero public dishes is the
authoritative current state because no dish has yet received the required
kitchen availability approval. The site therefore shows a truthful empty
state and the sitemap correctly contains no live-dish provider or `/menu/`
locations. No stale or estimated menu was restored.

### Live HTTP, SEO and Chrome acceptance

Read-only live acceptance after deployment and final sync proved:

- all 30 bilingual public-discovery routes returned HTTP 200 with no redirect,
  the exact 1.2.1/deployment markers, one H1, correct language/direction, exact
  canonical, reciprocal `he`/`en`/`x-default`, indexability and parseable
  `WebPage` JSON-LD;
- all 62 surfaced internal links returned HTTP 200 with no redirect;
- all 13 referenced image assets returned HTTP 200 as images with no redirect;
- the REST root, health endpoint, public catalog, robots file and sitemap
  returned their expected public contracts;
- both future store-architecture pages returned HTTP 200 with `noindex`, were
  absent from the sitemap and exposed no checkout;
- six public forms retained POST, five required fields, consent, nonce and
  honeypot; no form was submitted during acceptance;
- unknown Hebrew and English live-dish URLs returned HTTP 404, localized
  language/direction/title/H1, explicit `noindex`, no canonical, and two
  working recovery links.

Chrome acceptance covered the Hebrew and English home surfaces, the bilingual
dish empty state, mobile navigation and the changed bilingual 404 surface at
1440×1000 and 390×844 CSS viewports. The checked views had no horizontal
overflow, no visible interactive target below 44 pixels, correct version and
deployment markers, and no console errors. Mobile navigation opened, locked
body scrolling, closed with Escape and returned focus to its trigger.

### Evidence boundary

This evidence proves the reviewed 1.2.1 artifact is installed on the current
UPress production alias, the private OS can push and read back the exact public
model, and the public site truthfully represents that model.

It does not claim that a dish is currently available. Publishing the first live
dish remains intentionally blocked on real kitchen availability approval; no
availability, campaign publication or social publication was fabricated. The
future canonical domain `complete99.co.il` remains unconnected, so the current
canonical base remains the UPress alias.

## Version 1.2.0 production acceptance

Date: 2026-07-29

Release: `complete99-platform` 1.2.0

Production source commit:
`586668a409a3a40f134c93942a3a6998b508acab`

- Release artifact: `complete99-platform-1.2.0.zip`
- Artifact SHA-256:
  `089e8de17a2837235afdf1cfead7840df436d642156d67676a1f2f32b7271d12`
- Packaged-source SHA-256:
  `5cceade3263e33e879e2be0a90cef21efd5f8391a6c01783e20487cdc8e285dc`
- Installed-plugin SHA-256:
  `0b202e52e1ed4999008413c82daa1d7f7060ba9a2e789726a8594ba06d0cf54e`
- Artifact size: 2,718,441 bytes
- Independent archive validation: 161 entries

### Source and admission gates

Acceptance completed against the exact release source:

- all 95 contract tests passed; the one anonymous public-host test remained
  intentionally skipped because `COMPLETE99_VERIFY_PUBLIC_URLS=1` was not set;
- PHP lint passed for all 56 production/bridge source files and all 55 PHP files
  extracted independently from the candidate ZIP;
- source and package secret scans passed;
- two clean package builds were byte-for-byte identical;
- the checked update manifest, integrity metadata, version and deployment ID agree;
- working-tree whitespace validation passed.

The release includes strict boolean normalization, durable readback before a
successful sync response, supported UPress/LiteSpeed/object-cache invalidation
with explicit reporting, canonical slug collision protection, a seven-day
freshness gate, exact bilingual live-dish SEO ownership and the
`completedishes` WordPress sitemap provider.

Protected-main validation admitted the exact source commit before production
deployment:
<https://github.com/The-new-ben/complete99-wordpress/actions/runs/30414689530>

### Production deployment

- Current live base: <https://a235232-tmp.s1242.upress.link>
- Deployment ID: `c99-prod-30414737617-1`
- Deployment run:
  <https://github.com/The-new-ben/complete99-wordpress/actions/runs/30414737617>
- Production deployment completed at `2026-07-29T01:41:58Z`.
- Final health reported component `complete99-platform`, status `ok`, version
  `1.2.0`, database version `1.2.0`, and the exact deployment ID.
- The deployed plugin tree matched installed-plugin SHA-256
  `0b202e52e1ed4999008413c82daa1d7f7060ba9a2e789726a8594ba06d0cf54e`.
- The final rendered-home probe reported version `1.2.0` and the exact
  deployment ID.
- The final `robots.txt` probe returned HTTP 200 with SHA-256
  `e8b68b16cc0b5d15bf5f626b3881c20db37f566057c321c0ef0c610eb1b21040`.
- The temporary deployment snippet was inactive and permanently deleted; its
  row was absent and its temporary route returned 404.
- Finalization released the deployment lock and removed deployment state.

### Exercised rollback

The production run exercised rollback after the first 1.2.0 installation. The
audit verified restoration of the prior database and plugin files, then
redeployed the same exact 1.2.0 release. Final health, rendered-home, installed
plugin integrity, cleanup and route-404 checks passed after that exercise.

### Evidence boundary

This evidence proves the reviewed 1.2.0 artifact was installed on the current
UPress production alias, recovered through the exercised rollback path, and
left healthy with the temporary deployment bridge removed.

The first application-to-WordPress catalog sync, exact public readback,
post-sync bilingual dish sitemap membership, and final live-Chrome acceptance
are not recorded as complete in this document. Add those claims only after
independent verification. The future canonical domain `complete99.co.il`
remains unconnected; the current live base remains the UPress alias.

## Archived version 1.0.3 native acceptance

Date: 2026-07-28

Release: `complete99-platform` 1.0.3

Artifact SHA-256: `6fcc197035ec3f1759ea4c62e0b797e952870e6502b53d462b8143409383c18b`

Source SHA-256: `11bf717e9ea3fb5a6d4df8ab145be0e01ad3f8d5aac5cbb9e0cc42501b30c0b0`

### Native acceptance environment

- WordPress 7.0.2 from the official WordPress release archive
- PHP 8.3.31
- Code Snippets 3.9.6
- SQLite Database Integration 2.2.23
- real WordPress Application Password authentication
- real Code Snippets REST create/deactivate/permanent-delete lifecycle
- loopback-only native WordPress; no UPress staging or duplicate UPress site

### WordPress 7.0.2 executed proofs

| Deployment ID | Scenario | Observed result |
|---|---|---|
| `c99-local-wp702-first-103` | Injected pre-install lock/CAS incompatibility | Failed before plugin mutation; plugin absent; exact route row permanently deleted; route 404 |
| `c99-local-wp702-recover-103` | Authenticated owner discovery and recreated-route recovery | Exact reserved owner discovered; unstarted lock finalized; state removed; both temporary rows absent; both routes 404 |
| `c99-local-wp702-first-103b` | Normal first install | Version 1.0.3 installed and activated; independent health/body checks passed; lock/state removed; temporary row absent; route 404 |
| `c99-local-wp702-rollback-103` | Install -> health/body -> rollback -> prior health -> redeploy | Database and plugin restored to the prior exact release, then 1.0.3 redeployed; final health passed; route row absent; route 404 |
| `verify-chef-preservation.php` | Force migration after a chef-owned recipe edit | Edited recipe and prior provenance retained; migration committed 1.0.3 without failure |

The first exercise deliberately became a useful portability failure: MySQL's
binary compare syntax did not implement an exact SQLite compare. The bridge now
selects a MySQL binary compare on UPress and SQLite `COLLATE BINARY` on native
acceptance. The separate recovery command discovered and released the exact
orphaned owner before the corrected first-install exercise ran.

The Code Snippets 3.9.6 REST `DELETE` endpoint only trashes a row. Release 1.0.3
therefore retires temporary code through an admin-and-token-gated bridge callback
that invokes the plugin's permanent-delete API. Acceptance independently proved
the exact row is absent and then proved the route returns 404.

After the WordPress 7.0.2 exercises:

- no `complete99_deploy_lock` option remained;
- zero deployment state directories remained;
- zero `.complete99-restore-*` or `.complete99-displaced-*` directories remained;
- zero Complete99 temporary Code Snippets rows remained;
- every audited temporary route returned 404.

### Prior regression corpus retained

The earlier WordPress 6.9.4 acceptance corpus remains part of the recovery
regression suite: encrypted database capture failure, interruption after prepare,
interruption after install, interruption during rollback, interruption after
commit, first-install failure rollback, recreated-route rollback, recreated-route
commit cleanup, concurrent lock exclusion and canonical cross-platform ZIP proof.

### Static and public release gates

- all 61 contract tests pass; the network-gated public-host test was also run
  separately and passed;
- seven public platform/asset URL groups return anonymous HTTP 200;
- PHP lint passes for every plugin and bridge PHP file;
- Python compilation passes for deploy, recovery, build and release scripts;
- secret scan passes;
- two package builds are byte-for-byte identical;
- Windows and Linux text inputs canonicalize to the same LF package bytes;
- independent archive validation passes with 130 entries;
- the checked manifest targets WordPress 7.0 and requires PHP 8.0 / WordPress 6.4.

### Evidence boundary

This document proves the 1.0.3 package and deployment machinery on native
WordPress 7.0.2. Production acceptance still requires the live UPress alias dry
run, first install, health/body verification, permanent temporary-row deletion
and route-404 audit. No UPress staging site is required or allowed.
