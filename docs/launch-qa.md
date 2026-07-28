# Launch QA

## Automated gates

- PHP syntax passes for every production and bridge file.
- Twenty-six contract tests pass, including the rendered public-language boundary and
  pinned/read-only/serialized GitHub Actions contract.
- The explicit local-test transport accepts HTTP only on a loopback WordPress
  origin and rejects every remote or UPress staging host.
- Secret scan passes.
- Two independent package builds are byte-identical.
- ZIP has one `complete99-platform/` root, sorted forward-slash paths and normalized
  metadata.
- Package SHA-256 and size match the separate integrity metadata.
- Source SHA-256 is recomputed from the ZIP and matches integrity metadata.
- Secret-like filenames and credential signatures are rejected both before build and
  by independent archive validation without printing detected values.
- The public update manifest matches the plugin version and versioned GitHub package URL.
- The production package contains no reference-image path or development dependency.

## WordPress content checks

- Activation creates the Hebrew homepage, `/en/`, launch records and dish drafts
  once.
- Re-activation does not duplicate content.
- An editor-modified seed remains unchanged after an upgrade.
- Header has five primary routes plus one fit-review CTA.
- Command centre and campaign studio appear before the general service grid.
- English and Hebrew pages point to each other with canonical/hreflang.
- Dish records remain private and emit no Recipe schema until both languages reach
  5,000 substantive words, each has eight credible sources including two
  authoritative/primary sources, and all kitchen/allergen/image/editorial fields pass.
- Lead submission creates a private record and sends no email.
- Settings never print the stored sync secret.

## Browser matrix

Capture and review at minimum:

- desktop Hebrew homepage;
- desktop English homepage;
- 390px Hebrew and English mobile homepages;
- institutional service page;
- app tour and external Sites launch;
- proposal form success state;
- keyboard-only mobile menu;
- 200% zoom and reduced-motion preference.

Verify no horizontal overflow, clipped Hebrew labels, inaccessible focus, oversized
brand mark, duplicate navigation, layout shift from images or fixed controls covering
content.

## Live operational checks

- `complete99.co.il` is the final HTTPS production origin; no UPress staging site is used.
- UPress REST is enabled on that final site.
- Public health returns expected component, version and deployment ID.
- Public health `database_version` matches the plugin version; incomplete migrations
  return 503.
- Temporary deployment route returns 404 after every run.
- Cache is purged and a cache-busting GET renders the new body.
- Owned asset URLs resolve; no reference image is used.
- Sites app URL opens, but demo state is not presented as authenticated production.

## Native pipeline acceptance

The release pipeline was exercised on WordPress 6.9.4 with PHP 8.3.31 through real
Application Password and Code Snippets REST requests. Acceptance included:

- dry preflight and cleanup;
- normal first installation;
- update, rollback, exact database/plugin verification and redeploy;
- first-install failure after activation with complete database/file removal;
- forced database-capture failure;
- interruptions after preparation, after installation and halfway through rollback;
- committed-cleanup interruption;
- two simultaneous deployment reservations;
- deletion of the original route followed by successful recovery from a newly
  created bridge for both uncommitted and committed-cleanup states.

See [pipeline-acceptance-evidence.md](pipeline-acceptance-evidence.md) for the
non-secret proof IDs and outcomes.
