# Pipeline acceptance evidence

## Version 1.2.0 local source candidate

Date: 2026-07-29

This section records the current local candidate only. It is not production,
protected-main CI, or live-browser evidence.

- Candidate artifact: `complete99-platform-1.2.0.zip`
- Artifact SHA-256:
  `089e8de17a2837235afdf1cfead7840df436d642156d67676a1f2f32b7271d12`
- Packaged-source SHA-256:
  `5cceade3263e33e879e2be0a90cef21efd5f8391a6c01783e20487cdc8e285dc`
- Artifact size: 2,718,441 bytes
- Independent archive validation: 161 entries

Local acceptance completed against the exact working-tree source:

- all 95 contract tests passed; the one anonymous public-host test remained
  intentionally skipped because `COMPLETE99_VERIFY_PUBLIC_URLS=1` was not set;
- PHP lint passed for all 56 production/bridge source files and all 55 PHP files
  extracted independently from the candidate ZIP;
- source and package secret scans passed;
- two clean package builds were byte-for-byte identical;
- the checked update manifest, integrity metadata, version and deployment ID agree;
- working-tree whitespace validation passed.

The candidate includes strict boolean normalization, durable readback before a
successful sync response, supported UPress/LiteSpeed/object-cache invalidation
with explicit reporting, canonical slug collision protection, a seven-day
freshness gate, exact bilingual live-dish SEO ownership and the
`completedishes` WordPress sitemap provider.

This candidate has not been staged, committed, pushed, admitted by protected-main
CI, deployed to UPress or accepted in live Chrome. No claim is made that version
1.2.0 is live; the last verified production release remains 1.1.5. Physical
`robots.txt` management and sync-secret initialization are deliberate deployment
pipeline capabilities and were contract-tested locally, but they were not run
against production for this candidate.

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
